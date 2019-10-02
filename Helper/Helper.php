<?php

namespace Onfire\PaymarkOE\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Store\Model\ScopeInterface;

class Helper
{

    /**
     * @var ScopeConfigInterface
     */
    private $_config;

    /**
     * @var ObjectManager
     */
    private $_objectManager;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    private $_transactionBuilder;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * @var HistoryFactory
     */
    private $_orderHistoryFactory;

    /**
     * @var  \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    private $_orderSender;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $_quoteRepository;

    /**
     * @var \Onfire\PaymarkOE\Logger\PaymentLogger
     */
    private $_logger;

    const CONFIG_PREFIX = 'payment/paymarkoe/';

    const PAYMENT_NEW = 'NEW';

    const PAYMENT_SUBMITTED = 'SUBMITTED';

    const PAYMENT_AUTHORISED = 'AUTHORISED';

    const RESULT_SUCCESS = 'success';

    const RESULT_FAILED = 'failed';

    /**
     * Helper constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param HistoryFactory $orderHistoryFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        HistoryFactory $orderHistoryFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        $this->_config = $scopeConfig;

        $this->_orderHistoryFactory = $orderHistoryFactory;

        $this->_orderSender = $orderSender;

        $this->_objectManager = ObjectManager::getInstance();

        $this->_quoteRepository = $quoteRepository;

        $this->_transactionBuilder = $this->_objectManager->get('\Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface');

        $this->_checkoutSession = $this->_objectManager->get('\Magento\Checkout\Model\Session');

        $this->_logger = $this->_objectManager->get("\Onfire\PaymarkOE\Logger\PaymentLogger");
    }

    /**
     * Log to Paymark file
     *
     * @param $message
     */
    public function log($message)
    {
        if (!$this->_logger) {
            return;
        }

        // only log info if debug_log is true
        if($this->getConfig('debug_log')) {
            $this->_logger->info($message);
        }
    }

    /**
     * Get Paymark system config
     *
     * @param $path
     * @return mixed
     */
    public function getConfig($path)
    {
        return $this->_config->getValue(
            self::CONFIG_PREFIX . $path,
            ScopeInterface::SCOPE_STORE
        );
    }


    /**
     * Get order by increment id
     *
     * @param $incrementId
     * @return mixed
     */
    public function getOrderByIncrementId($incrementId)
    {
        $collection = $this->_objectManager->create('Magento\Sales\Model\Order');
        $orderInfo = $collection->loadByIncrementId($incrementId);

        return $orderInfo->getId() ? $orderInfo : null;
    }

    /**
     * Find Paymark transaction id from order
     *
     * @param $order
     * @return null
     */
    public function getTransactionIdFromOrder($order) {
        $payment = $order->getPayment();
        $additionalInfo = $payment->getAdditionalInformation();
        return !empty($additionalInfo["TransactionID"]) ? $additionalInfo["TransactionID"] : null;
    }

    /**
     * Check transaction status and update order if accepted/declined
     *
     * @param $transaction
     * @return bool
     */
    public function checkTransactionAndProcess($transaction)
    {
        $this->log(__METHOD__. " check transaction");

        if(empty($transaction->status) || empty($transaction->transaction->orderId)) {
            $this->log(__METHOD__. " no response status or increment id, something has gone quite wrong.");
            return false;
        }

        if($transaction->status == self::PAYMENT_NEW || $transaction->status == self::PAYMENT_SUBMITTED) {
            //order is not ready yet - do nothing
            return false;
        }

        return $this->processTransaction($transaction);
    }

    /**
     * Process response from Paymark, updating order as required
     *
     * @param $transaction
     * @return bool
     */
    public function processTransaction($transaction) {
        $this->log(__METHOD__. " handle transaction");

        $incrementId = $transaction->transaction->orderId;
        $success = ($transaction->status == self::PAYMENT_AUTHORISED ? true : false);

        // find order from increment id
        $order = $this->getOrderByIncrementId($incrementId);
        if(!$order) {
            //no order, what happened?
            $this->log(__METHOD__. " cant find order for increment id: " . $incrementId);
            return false;
        }

        // grab payment object from order
        $payment = $order->getPayment();

        try {
            if($success) {
                // payment completed
                $this->log(__METHOD__. " " . $incrementId . " order status complete");

                $this->_orderSuccess($order, $payment, $transaction);

                $this->log(__METHOD__. " " . $incrementId . " payment complete");

                $this->_setPaymentInformation($payment, $transaction);

                $this->log(__METHOD__. " " . $incrementId . " set payment info back on order");

                $this->sendOrderEmail($order);

                return self::RESULT_SUCCESS;

            } else {
                // payment failed
                $this->log(__METHOD__. " " . $incrementId . " order status failed with " . $transaction->status);

                $this->_orderFailed($order);

                $this->log(__METHOD__. " " . $incrementId . " quote rolled back, ready to redirect to cart");

                return self::RESULT_FAILED;
            }

        } catch (\Exception $e) {
            $this->log(__METHOD__. " Payment failed with error: " . $e->getMessage());
            return self::RESULT_FAILED;
        }

    }

    /**
     * Send order completed / paid email to customer
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    public function sendOrderEmail(\Magento\Sales\Model\Order $order)
    {
        if ($order->getCanSendNewEmailFlag()) {
            try {
                $this->_orderSender->send($order);
            } catch (\Exception $e) {
                $this->log(__METHOD__. " " . $order->getIncrementId() . " failed to send order email");
                $this->log($e->getMessage());
            }
        }
    }

    /**
     * Handle successful order (OE only does 'capture')
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param $transaction
     * @return \Magento\Sales\Model\Order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _orderSuccess(\Magento\Sales\Model\Order $order, \Magento\Sales\Model\Order\Payment $payment, $transaction)
    {
        $transID = $transaction->id;
        $amount = bcdiv($transaction->transaction->amount, 100); //convert back to decimal

        $order->setCanSendNewEmailFlag(true);

        // prepare invoice and update order status
        $invoice = $order->prepareInvoice();
        $invoice->getOrder()->setIsInProcess(true);

        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);

        $invoice->setTransactionId($transID);
        $invoice->register()->pay()->save();

        // create a capture transaction
        $this->_createTransaction($order, $payment, $transID, true);

        $order->save();

        // add comment to order history
        $message = __(
            'Captured and invoiced amount of %1 for transaction %2',
            $amount,
            $transID
        );

        $history = $this->_orderHistoryFactory->create()
            ->setComment($message)
            ->setEntityName('order')
            ->setOrder($order);

        $history->save();

        return $order;
    }

    /**
     * Order failed, cancel order and reinstate quote
     *
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Sales\Model\Order
     * @throws \Exception
     */
    private function _orderFailed(\Magento\Sales\Model\Order $order)
    {
        // reset cart back into current session to retry
        if ($this->_restoreQuoteFromOrder($order)) {
            $this->log(__METHOD__ . " Quote has been rolled back.");
        } else {
            $this->log(__METHOD__ . " Unable to rollback quote.");
        }

        $order->setActionFlag(\Magento\Sales\Model\Order::ACTION_FLAG_CANCEL, true);
        $order->cancel()->save();

        return $order;
    }

    /**
     * Restore quote from order when the payment failed
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    private function _restoreQuoteFromOrder(\Magento\Sales\Model\Order $order)
    {
        try {

            $quote = $this->_quoteRepository->get($order->getQuoteId());
            $quote->setIsActive(1)->setReservedOrderId(null);
            $this->_quoteRepository->save($quote);
            $this->_checkoutSession->replaceQuote($quote)->unsLastRealOrderId();
            return true;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        }

        return false;
    }

    /**
     * Update payment info with additional response data
     *
     * @param $payment
     * @param $transaction
     */
    private function _setPaymentInformation($payment, $transaction)
    {
        $info = $payment->getAdditionalInformation();

        $info['Status'] = $transaction->status;

        $payment->unsAdditionalInformation();
        $payment->setAdditionalInformation($info);

        $payment->save();
    }

    /**
     * Create order transaction
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @param $transId
     * @param $completed
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    private function _createTransaction(
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\Order\Payment $payment,
        $transId,
        $completed)
    {
        $transaction = $this->_transactionBuilder
            ->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($transId)
            ->setFailSafe(true)
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

        $transaction->setIsClosed($completed);

        $transaction->save();

        return $transaction;
    }

    /**
     * Find assoc array param case insensitively (paymark responses are randomly upper/lower)
     *
     * @param $needle
     * @param $haystack
     * @return bool
     */
    public function getParamInsensitive($needle, $haystack) {
        foreach ($haystack as $key => $value) {
            if (strtolower($needle) == strtolower($key)) {
                return $value;
            }
        }
        return null;
    }

}