<?php

namespace Paymark\PaymarkOE\Model\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;

class StatusManagement extends AbstractManagement
{

    /**
     * Get Paymark session status and return session id to frontend
     *
     * @return string
     * @throws \Exception
     */
    public function getTransactionStatus()
    {
        //@todo how can we make this more robust?
        $order = $this->getCheckoutSession()->getLastRealOrder();

        $helper = $this->getHelper();

        if (!$order) {
            $helper->log(__METHOD__ . " no last order?");
            return;
        }

        // check the order is in pending state
        if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
            throw new LocalizedException(__('Payment has already started'));
        }

        /** @var \Magento\Sales\Model\Order\Interceptor $order */
        $order = $helper->getOrderByIncrementId($order->getIncrementId());

        return $helper->checkTransactionSession($order)->id;
    }
}