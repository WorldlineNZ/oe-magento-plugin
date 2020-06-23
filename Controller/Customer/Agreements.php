<?php

namespace Paymark\PaymarkOE\Controller\Customer;

class Agreements extends \Magento\Framework\App\Action\Action
{
    /**
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);

        $this->customerSession = $this->_objectManager->get('\Magento\Customer\Model\Session');
    }

    public function execute()
    {
        // redirect if not logged in
        if (!$this->customerSession->isLoggedIn()) {
            return $this->_redirect('customer/account/login');
        }

        $resultPage = $this->resultPageFactory->create();

        $block = $resultPage->getLayout()->getBlock('customer.account.link.back');

        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }

        return $resultPage;
    }
}
