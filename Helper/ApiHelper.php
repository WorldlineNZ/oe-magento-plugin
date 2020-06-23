<?php

namespace Paymark\PaymarkOE\Helper;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Paymark\PaymarkOE\Exception\ApiConflictException;
use Paymark\PaymarkOE\Model\OnlineEftposApi;
use Paymark\PaymarkOE\Model\Ui\ConfigProvider;

class ApiHelper extends AbstractHelper
{

    /**
     * @var \Paymark\PaymarkOE\Helper\Helper
     */
    private $_helper;

    /**
     * @var \Paymark\PaymarkOE\Model\OnlineEftposApi
     */
    private $_paymarkApi;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $_objectManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;

    /**
     * ApiHelper constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_storeManager = $this->_objectManager->create("\Magento\Store\Model\StoreManagerInterface");

        $this->_helper = $this->_objectManager->create("\Paymark\PaymarkOE\Helper\Helper");

        $this->_paymarkApi = $this->_objectManager->create("\Paymark\PaymarkOE\Model\OnlineEftposApi");
    }

    /**
     * Generate payment request with Paymark OE API
     *
     * @param \Magento\Sales\Model\Order\Payment\Interceptor $payment
     * @param $orderState
     * @param $paymentAction
     * @return mixed
     * @throws LocalizedException
     */
    public function createPaymentRequest(\Magento\Sales\Model\Order\Payment\Interceptor $payment, $orderState, $paymentAction)
    {
        $this->_helper->log(__METHOD__ . " Create payment request object");

        $order = $payment->getOrder();

        $paymentInformation = $payment->getAdditionalInformation();

        $paymentType = !empty($paymentInformation['payment_type']) ? $paymentInformation['payment_type'] : ConfigProvider::TYPE_STANDARD;
        if($paymentType == ConfigProvider::TYPE_AUTOPAY && empty($paymentInformation['selected_agreement'])) {

            // doing auto payment but the agreement is not selected
            $this->_helper->log(__METHOD__ . " Selected agreement missing for order " . $order->getIncrementId());
            throw new LocalizedException(__("Autopay agreement missing for order - please try again."));

        } else if($paymentType == ConfigProvider::TYPE_STANDARD && (empty($paymentInformation['mobile_number']) || empty($paymentInformation['selected_bank']))) {

            // doing normal payment but the mobile or bank is missing
            $this->_helper->log(__METHOD__ . " Mobile number or bank missing for order " . $order->getIncrementId());
            throw new LocalizedException(__("Mobile number or bank missing - please try again."));

        }

        try {

            // create callback url
            $callback = $this->_getUrl('paymarkoe/callback/response/', [
                'orderId' => $order->getIncrementId(),
                '_secure' => true
            ]);

            // multiply order value by 100 as per OE docs
            $total = bcmul($order->getBaseGrandTotal(), 100);

            $reference = $this->getStoreName() . ' OE Payment';

            $transactionType = OnlineEftposApi::TYPE_REGULAR;
            if ($paymentType == ConfigProvider::TYPE_AUTOPAY) {
                // if payment type uses autopay, find the token

                $agreementHelper = $this->_objectManager->create("\Paymark\PaymarkOE\Helper\AgreementHelper");
                $agreement = $agreementHelper->getAgreementById($paymentInformation['selected_agreement']);
                if($agreement->getCustomerId() !== $order->getCustomerId()) {
                    throw new LocalizedException(__("Agreement is not under the order customer account."));
                }

                // set payment details based on token instead
                $agreementDetails = json_decode($agreement->getTokenDetails());
                $paymentInformation['mobile_number'] = $agreementDetails->payer;
                $paymentInformation['selected_bank'] = $agreementDetails->bank;

                $transactionType = OnlineEftposApi::TYPE_TRUSTED;

            } elseif (!empty($paymentInformation['setup_autopay']) && $paymentInformation['setup_autopay']) {
                $transactionType = OnlineEftposApi::TYPE_TRUSTSETUP;
            }

            $this->_paymarkApi->login();
            $transaction = $this->_paymarkApi->createTransaction(
                $order->getIncrementId(),
                $total,
                $order->getOrderCurrencyCode(),
                $paymentInformation['mobile_number'],
                $paymentInformation['selected_bank'],
                $reference,
                $this->getStoreUrl(),
                $callback,
                $transactionType
            );

            $this->_helper->log(__METHOD__ . " Request created with ID " . $transaction->id);

        } catch (ApiConflictException $e) {
            $this->_helper->log(__METHOD__ . " Failed to generate payment request");
            $this->_helper->log($e->getMessage());

            throw new LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->_helper->log(__METHOD__ . " Failed to generate payment request");
            $this->_helper->log($e->getMessage());

            throw new LocalizedException(__("Failed to generate payment request - please check your errors logs or contact support"));
        }

        if (empty($transaction->id)) {
            throw new LocalizedException(__("Failed to generate payment request - please check your errors logs or contact support"));
        }

        $orderState->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $orderState->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $orderState->setIsNotified(false);

        $this->_helper->log(__METHOD__ . " Order set as pending");

        $order->setCanSendNewEmailFlag(false);
        $order->save();

        $this->_helper->log(__METHOD__ . " Order saved");

        return $transaction->id;
    }

    /**
     * Find transaction at Paymark using the $transactionId
     *
     * @param $transactionId
     * @return mixed
     * @throws LocalizedException
     */
    public function findTransaction($transactionId)
    {
        try {
            $this->_paymarkApi->login();
            $transaction = $this->_paymarkApi->getTransaction($transactionId);
        } catch (\Exception $e) {
            $this->_helper->log(__METHOD__ . " Failed to find payment request for id " . $transactionId);
            $this->_helper->log($e->getMessage());

            throw new LocalizedException(__("Failed to generate payment request - please check your errors logs or contact support"));
        }

        return $transaction;
    }

    /**
     * Delete autopay trust contract, using Paymark UUID
     *
     * @param $autopayId
     * @return mixed
     * @throws \Exception
     */
    public function deleteAutopay($autopayId)
    {
        $this->_paymarkApi->login();
        return $this->_paymarkApi->deleteAutopayContract($autopayId);
    }

    /**
     * Return store base URL for payment object
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    /**
     * Return store name for payment object
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }
}
