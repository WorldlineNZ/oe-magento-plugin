<?php

namespace Onfire\PaymarkOE\Controller\Callback;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\Order;

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

        $apiHelper = $objectManager->get("\Onfire\PaymarkOE\Helper\ApiHelper");
        $helper = $objectManager->create("\Onfire\PaymarkOE\Helper\Helper");

        $helper->log(__METHOD__ . " execute Paymark OE callback");

        $params = $this->getRequest()->getParams();

        if(empty($params) || empty($params['orderId'])) {
            //something has gone wrong
            $helper->log(__METHOD__. " order not found in callback - params missing");
            return false;
        }

        $order = $helper->getOrderByIncrementId($params['orderId']);

        if(!$order || !($transactionId = $helper->getTransactionIdFromOrder($order))) {
            //something has gone wrong
            $helper->log(__METHOD__. " order or remote transaction not found in callback " . $params['orderId']);
            return false;
        }

        // if already completed for whatever reason, just stop
        if($order->getState() == Order::STATE_PROCESSING || $order->getState() == Order::STATE_CANCELED) {
            $helper->log(__METHOD__. " order already completed " . $params['orderId']);
            return;
        }

        $helper->log(__METHOD__. " order found");

        $transaction = $apiHelper->findTransaction($transactionId);

        $helper->log(__METHOD__. " check and process transaction");

        $result = $helper->checkTransactionAndProcess($transaction);

        $helper->log(__METHOD__. " process transaction result: " . $result);
    }
}
