<?php

namespace Paymark\PaymarkOE\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action implements HttpGetActionInterface
{

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var \Paymark\PaymarkOE\Helper\AgreementHelper
     */
    private $_helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    )
    {
        parent::__construct($context);

        $this->_formKeyValidator = $formKeyValidator;

        $this->_customerSession = $this->_objectManager->get('\Magento\Customer\Model\Session');

        $this->_helper = $this->_objectManager->get("\Paymark\PaymarkOE\Helper\AgreementHelper");
    }

    public function execute()
    {
        if (!$this->_customerSession->isLoggedIn()) {
            return $this->_redirect('customer/account/login');
        }

        if(!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid form key'));
            return $this->resultRedirectFactory->create()->setPath('*/*/agreements');
        }

        try {
            $agreementId = $this->getRequest()->getParam("id");
            $customerId = $this->_customerSession->getCustomerId();

            $this->_helper->deleteCustomerAgreement($customerId, $agreementId);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/agreements');
    }
}
