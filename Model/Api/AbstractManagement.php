<?php

namespace Paymark\PaymarkOE\Model\Api;

use Magento\Sales\Model\Order;

class AbstractManagement
{

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    private $_request;

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
     * AbstractManagement constructor.
     *
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\UrlInterface $urlInterface
     */
    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlInterface
    )
    {
        $this->_request = $request;
        $this->_urlInterface = $urlInterface;
        $this->_checkoutSession = $checkoutSession;
        $this->_messageManager = $messageManager;
    }

    /**
     * @return \Magento\Framework\Webapi\Rest\Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @return \Magento\Framework\Message\ManagerInterface
     */
    public function getMessageManager()
    {
        return $this->_messageManager;
    }

    /**
     * @return \Magento\Framework\UrlInterface
     */
    public function getUrlInterface()
    {
        return $this->_urlInterface;
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    public function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
     * @return \Paymark\PaymarkOE\Helper\Helper
     */
    public function getHelper()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        return $objectManager->create("\Paymark\PaymarkOE\Helper\Helper");
    }

    /**
     * Handle order response to client
     *
     * @param $order
     * @return bool|false|string
     */
    public function handleResponse($order) {
        switch ($order->getState()) {
            case Order::STATE_PROCESSING:
            case Order::STATE_COMPLETE:
                return json_encode([
                    'status' => 'success',
                    'redirect' => $this->getUrlInterface()->getUrl("checkout/onepage/success", [
                        "_secure" => true
                    ])
                ]);
                break;
            case Order::STATE_CANCELED:
                // set error message for cart page
                $this->addMessageError('Payment was declined');

                return json_encode([
                    'status' => 'failed',
                    'redirect' => $this->getUrlInterface()->getUrl("checkout/cart", [
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