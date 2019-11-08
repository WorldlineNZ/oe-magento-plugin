<?php

namespace Onfire\PaymarkOE\Controller\Customer;

use Magento\Framework\Exception\LocalizedException;

class Delete extends \Magento\Framework\App\Action\Action
{

    /**
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $_resultPageFactory;

    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     *
     * @var \Onfire\PaymarkOE\Helper\ApiHelper
     */
    private $_helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->_resultPageFactory = $resultPageFactory;

        parent::__construct($context);

        $this->_customerSession = $this->_objectManager->get('\Magento\Customer\Model\Session');

        $this->_helper = $this->_objectManager->get("\Onfire\PaymarkOE\Helper\AgreementHelper");
    }

    public function execute()
    {
        $agreementId = $this->getRequest()->getParam("id");
        $customerId = $this->_customerSession->getCustomerId();

        try {
            $this->_helper->deleteCustomerAgreement($customerId, $agreementId);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirect("paymarkoe/customer/agreements");
    }
}
