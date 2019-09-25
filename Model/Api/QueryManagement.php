<?php

namespace Onfire\PaymarkOE\Model\Api;

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
     * Get Paymark transaction status and return url for the user to redirect to
     *
     * @return array|mixed|string
     */
    public function getTransactionStatus()
    {
        $order = $this->_checkoutSession->getLastRealOrder();

        switch ($order->getState()) {
            case Order::STATE_PROCESSING:
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