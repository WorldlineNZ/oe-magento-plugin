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
     * @return mixed
     * @throws LocalizedException
     */
    public function createPaymentRequest(\Magento\Sales\Model\Order\Payment\Interceptor $payment, $orderState)
    {
        $this->_helper->log(__METHOD__ . " Create payment request object");

        $order = $payment->getOrder();

        try {
            // multiply order value by 100 as per OE docs
            $total = bcmul($order->getBaseGrandTotal(), 100);

            // login to the API to start
            $this->_paymarkApi->login();

            $session = $this->_paymarkApi->createOpenSession(
                $order->getIncrementId(),
                $total,
                $order->getOrderCurrencyCode(),
                $this->_helper->canUseAutopay(),
                $this->_helper->getCustomerTrustIds()
            );

            $this->_helper->log(__METHOD__ . " Request created with ID " . $session->id);

        } catch (ApiConflictException $e) {
            $this->_helper->log(__METHOD__ . " Failed to generate payment request");
            $this->_helper->log($e->getMessage());

            throw new LocalizedException(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->_helper->log(__METHOD__ . " Failed to generate payment request");
            $this->_helper->log($e->getMessage());

            throw new LocalizedException(__("Failed to generate payment request - please check your errors logs or contact support"));
        }

        if (empty($session->id)) {
            throw new LocalizedException(__("Failed to generate payment request - please check your errors logs or contact support"));
        }

        // set order as pending payment
        $orderState->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $orderState->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $orderState->setIsNotified(false);

        $this->_helper->log(__METHOD__ . " Order set as pending");

        $order->setCanSendNewEmailFlag(false);
        $order->save();

        $this->_helper->log(__METHOD__ . " Order saved");

        return $session->id;
    }

    /**
     * Query a current Openjs session
     *
     * @param $transactionId
     * @return mixed
     * @throws \Exception
     */
    public function querySession($transactionId)
    {
        $this->_paymarkApi->login();
        return $this->_paymarkApi->queryOpenSession($transactionId);
    }

    /**
     * Get a completed Openjs transaction using the $transactionId
     *
     * @param $transactionId
     * @return mixed
     * @throws \Exception
     */
    public function getTransaction($transactionId)
    {
        $this->_paymarkApi->login();
        return $this->_paymarkApi->getOpenTransaction($transactionId);
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
