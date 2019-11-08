<?php

namespace Onfire\PaymarkOE\Helper;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Vault\Model\PaymentTokenFactory;
use Onfire\PaymarkOE\Model\Ui\ConfigProvider;

class AgreementHelper
{
    /**
     * @var EncryptorInterface
     */
    private $_encryptor;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $_paymentTokenRepository;

    /**
     * @var PaymentTokenFactory
     */
    private $_paymentTokenFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $_searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $_sortOrderBuilder;

    /**
     * @var DateTimeFactory
     */
    private $_dateTimeFactory;

    /**
     * @var DateTimeFactory
     */
    private $_apiHelper;

    const TOKEN_TYPE = 'account';

    /**
     * AgreementHelper constructor.
     *
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenFactory $paymentTokenFactory
     * @param EncryptorInterface $encryptor
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param DateTimeFactory $dateTimeFactory
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenFactory $paymentTokenFactory,
        EncryptorInterface $encryptor,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        DateTimeFactory $dateTimeFactory,
        ApiHelper $apiHelper
    )
    {
        $this->_paymentTokenRepository = $paymentTokenRepository;

        $this->_paymentTokenFactory = $paymentTokenFactory;

        $this->_encryptor = $encryptor;

        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;

        $this->_sortOrderBuilder = $sortOrderBuilder;

        $this->_dateTimeFactory = $dateTimeFactory;

        $this->_apiHelper = $apiHelper;
    }

    /**
     * Get agreement by entity id
     *
     * @param $entityId
     * @return PaymentTokenInterface
     */
    public function getAgreementById($entityId)
    {
        return $this->_paymentTokenRepository->getById($entityId);
    }

    /**
     * Get agreement by trustId
     *
     * @param $trustId
     * @return PaymentTokenInterface[]
     */
    public function getAgreementByToken($trustId)
    {
        $this->_searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::GATEWAY_TOKEN,
            $trustId
        );

        $searchResult = $this->_paymentTokenRepository->getList($this->_searchCriteriaBuilder->create());

        return $searchResult->getItems();
    }

    /**
     * Get all active customer Online EFTPOS agreements
     *
     * @param $customerId
     * @return PaymentTokenInterface[]
     */
    public function getCustomerAgreements($customerId)
    {
        $searchCriteria = $this->buildAgreementsSearch($customerId);

        $searchResult = $this->_paymentTokenRepository->getList($searchCriteria);

        return $searchResult->getItems();
    }

    /**
     * Create a new customer agreement in magento's vault
     *
     * @param $customerId
     * @param $token
     * @param $payerId
     * @param $bank
     * @return PaymentTokenInterface
     */
    public function createCustomerAgreement($customerId, $token, $payerId, $bank)
    {
        $tokenInterface = $this->_paymentTokenFactory->create(self::TOKEN_TYPE);

        $tokenInterface->setCustomerId($customerId);
        $tokenInterface->setGatewayToken($token);
        $tokenInterface->setPublicHash($this->generatePublicHash($tokenInterface));
        $tokenInterface->setPaymentMethodCode(ConfigProvider::VAULT_CODE);
        $tokenInterface->setTokenDetails(json_encode([
            'payer' => $payerId,
            'bank' => $bank
        ]));

        $this->_paymentTokenRepository->save($tokenInterface);

        return $tokenInterface;
    }

    /**
     * Delete customer Online EFTPOS agreement both locally and at Paymark
     *
     * @param $customerId
     * @param $agreementId
     * @return bool
     * @throws LocalizedException
     */
    public function deleteCustomerAgreement($customerId, $agreementId)
    {
        $token = $this->_paymentTokenRepository->getById($agreementId);

        if($token->getCustomerId() !== $customerId) {
            throw new LocalizedException(__('Agreement does not belong to this user'));
        }

        try {
            //delete remote agreement
            $this->_apiHelper->deleteAutopay($token->getGatewayToken());
        } catch (\Exception $e) {
            throw new LocalizedException(__('Failed to delete agreement: ' . $e->getMessage()));
        }

        //then delete local if ok
        return $this->_paymentTokenRepository->delete($token);
    }

    /**
     * Delete agreement from magento Vault
     *
     * @param $token
     * @return bool
     */
    public function deleteAgreementToken($token)
    {
        return $this->_paymentTokenRepository->delete($token);
    }

    /**
     * Builds search criteria to find available agreements
     *
     * @param $customerId
     * @return \Magento\Framework\Api\SearchCriteria
     */
    private function buildAgreementsSearch($customerId)
    {
        $this->_searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::CUSTOMER_ID,
            $customerId
        );

        $this->_searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::IS_VISIBLE,
            1
        );

        $this->_searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::IS_ACTIVE,
            1
        );

        // I don't think we need this as we don't have an expiry
        /*$this->_searchCriteriaBuilder->addFilter(
            PaymentTokenInterface::EXPIRES_AT,
            $this->_dateTimeFactory->create('now', new \DateTimeZone('UTC'))
                ->format('Y-m-d 00:00:00'),
            'gt'
        );*/

        $creationReverseOrder = $this->_sortOrderBuilder->setField(PaymentTokenInterface::CREATED_AT)
            ->setDescendingDirection()
            ->create();

        $this->_searchCriteriaBuilder->addSortOrder($creationReverseOrder);

        $searchCriteria = $this->_searchCriteriaBuilder->create();

        return $searchCriteria;
    }

    /**
     * Generate hash for the token
     *
     * @param $paymentToken
     * @return string
     */
    protected function generatePublicHash($paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->_encryptor->getHash($hashKey);
    }

}