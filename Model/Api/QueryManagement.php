<?php

namespace Paymark\PaymarkOE\Model\Api;

class QueryManagement extends AbstractManagement
{

    /**
     * Get Paymark transaction details and return order status to frontend
     *
     * @return bool|false|string
     * @throws \Exception
     */
    public function getTransactionDetails()
    {
        $params = $this->getRequest()->getParams();
        $orderId = !empty($params['id']) ? $params['id'] : null;

        $helper = $this->getHelper();

        if(!$orderId) {
            $helper->log(__METHOD__ . " no order id passed back to query api");
            return false;
        }

        try {
            $order = $helper->getOrderById($orderId);
        } catch (\Exception $e) {
            $this->addMessageError('Order missing');

            return $this->failedResponse();
        }

        // double check order to see if it's already completed
        if ($response = $this->handleResponse($order)) {
            return $response;
        }

        try {
            $helper->processOrder($order);

            return $this->handleResponse($order);
        } catch (\Exception $e) {
            $this->addMessageError($e->getMessage());

            return $this->failedResponse();
        }
    }

    /**
     * Failure response
     *
     * @return string
     */
    public function failedResponse()
    {
        return json_encode([
            'status' => 'failed',
            'redirect' => $this->getUrlInterface()->getUrl("checkout/cart", [
                "_secure" => true
            ])
        ]);
    }

}