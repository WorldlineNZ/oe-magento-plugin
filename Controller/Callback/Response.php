<?php

namespace Onfire\PaymarkOE\Controller\Callback;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Response extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{

    // disable CSRF protection on these inbound routes
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Handle callback response from Paymark
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $helper = $objectManager->create("\Onfire\PaymarkOE\Helper\Helper");

        $helper->log(__METHOD__ . " execute Paymark OE callback");

        $params = $this->getRequest()->getParams();

        if(empty($params) || empty($params['orderId'])) {
            //something has gone wrong
            $helper->log(__METHOD__. " order not found in callback - params missing");
            return false;
        }

        // confirm signature is correct
        $valid = $helper->validateSignature([
            'merchantOrderId' => $params['merchantOrderId'],
            'status' => $params['status'],
            'transactionId' => $params['transactionId']
        ], $params['signature']);

        if(!$valid) {
            $helper->log(__METHOD__. " signature validation failed for callback/response with order id: " . $params['orderId']);
            return false;
        }

        $order = $helper->getOrderByIncrementId($params['orderId']);
        $helper->processOrder($order, false);
    }
}
