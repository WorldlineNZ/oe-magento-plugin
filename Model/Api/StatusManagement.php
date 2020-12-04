<?php

namespace Paymark\PaymarkOE\Model\Api;

use Magento\Sales\Model\Order;

class StatusManagement
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
     * Get Paymark session status and return session id to frontend
     *
     * @return string|void
     * @throws \Exception
     */
    public function getTransactionStatus()
    {
        //@todo how can we make this more robust?
        $order = $this->_checkoutSession->getLastRealOrder();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Paymark\PaymarkOE\Helper\Helper $helper */
        $helper = $objectManager->create("\Paymark\PaymarkOE\Helper\Helper");

        if (!$order) {
            $helper->log(__METHOD__ . " no last order?");
            return;
        }

        // check the order is in pending state
        if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
            $this->_messageManager->addErrorMessage('Payment already completed');

            return json_encode([
                'status' => 'failed',
                'redirect' => $this->_urlInterface->getUrl("checkout/cart", [
                    "_secure" => true
                ])
            ]);
        }

        /** @var \Magento\Sales\Model\Order\Interceptor $order */
        $order = $helper->getOrderByIncrementId($order->getIncrementId());

        return $helper->checkTransactionSession($order)->id;
    }
}