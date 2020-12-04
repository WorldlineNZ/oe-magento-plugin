<?php

namespace Paymark\PaymarkOE\Model\Api;

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
            $this->addMessageError('Payment already completed');

            return json_encode([
                'status' => 'failed',
                'redirect' => $this->getUrlInterface()->getUrl("checkout/cart", [
                    "_secure" => true
                ])
            ]);
        }

        /** @var \Magento\Sales\Model\Order\Interceptor $order */
        $order = $helper->getOrderByIncrementId($order->getIncrementId());

        return $helper->checkTransactionSession($order)->id;
    }
}