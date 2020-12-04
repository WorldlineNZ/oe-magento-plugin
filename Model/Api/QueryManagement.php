<?php

namespace Paymark\PaymarkOE\Model\Api;

use Magento\Sales\Model\Order;

class QueryManagement
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $_urlInterface;

    /**
    *  @var \Magento\Checkout\Model\Session
    */
    private $_checkoutSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $_messageManager;

    /**
     * QueryManagement constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\UrlInterface $urlInterface
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlInterface
    )
    {
        $this->_urlInterface = $urlInterface;
        $this->_checkoutSession = $checkoutSession;
        $this->_messageManager = $messageManager;
    }

    /**
     * Get Paymark transaction details and return order status to frontend
     *
     * @todo should get renamed?
     *
     * @return bool|false|string|void
     * @throws \Exception
     */
    public function getTransactionStatus()
    {
        //@todo how can we make this more robust? maybe it could be passed back through
        $order = $this->_checkoutSession->getLastRealOrder();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Paymark\PaymarkOE\Helper\Helper $helper */
        $helper = $objectManager->create("\Paymark\PaymarkOE\Helper\Helper");

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

    /**
     * Handle order response to client
     *
     * @param $order
     * @return bool|false|string
     */
    private function handleResponse($order) {
        switch ($order->getState()) {
            case Order::STATE_PROCESSING:
            case Order::STATE_COMPLETE:
                return json_encode([
                    'status' => 'success',
                    'redirect' => $this->_urlInterface->getUrl("checkout/onepage/success", [
                        "_secure" => true
                    ])
                ]);
                break;
            case Order::STATE_CANCELED:
                // set error message for cart page
                $this->addMessageError('Payment was declined');

                return json_encode([
                    'status' => 'failed',
                    'redirect' => $this->_urlInterface->getUrl("checkout/cart", [
                        "_secure" => true
                    ])
                ]);
                break;
            case Order::STATE_PENDING_PAYMENT:
                // if pending - wait
                break;
        }

        return false;
    }

    /**
     * Add error message to session to display back to user
     *
     * @param $errorMessage
     */
    public function addMessageError($errorMessage) {
        $this->_messageManager->addErrorMessage($errorMessage);
    }
}