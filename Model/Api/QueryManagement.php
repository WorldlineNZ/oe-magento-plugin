<?php

namespace Paymark\PaymarkOE\Model\Api;

class QueryManagement extends AbstractManagement
{

    /**
     * Get Paymark transaction details and return order status to frontend
     *
     * @return bool|false|string|void
     * @throws \Exception
     */
    public function getTransactionDetails()
    {
        //@todo how can we make this more robust? maybe it could be passed back through
        $order = $this->getCheckoutSession()->getLastRealOrder();

        $helper = $this->getHelper();

        if(!$order) {
            $helper->log(__METHOD__. " no last order?");
            return;
        }

        // double check order to see if it's already completed
        if($response = $this->handleResponse($order)) {
            return $response;
        }

        $order = $helper->getOrderByIncrementId($order->getIncrementId());

        $helper->processOrder($order);

        return $this->handleResponse($order);
    }
}