<?php

namespace Onfire\PaymarkOE\Controller\Customer;

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

    /**
     * Redirect to login if no user session
     */
    private function checkLoggedIn()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $this->_redirect('customer/account/login');
        }
    }

    public function execute()
    {
        // redirect if not logged in
        $this->checkLoggedIn();

        $resultPage = $this->resultPageFactory->create();

        $block = $resultPage->getLayout()->getBlock('customer.account.link.back');

        if ($block) {
            $block->setRefererUrl($this->_redirect->getRefererUrl());
        }

        return $resultPage;
    }
}
