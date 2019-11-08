<?php

namespace Onfire\PaymarkOE\Block\Customer;

use Onfire\PaymarkOE\Model\Ui\ConfigProvider;

class Agreements extends \Magento\Framework\View\Element\Template
{
    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    private $_customerSession;

    /**
     * @var \Onfire\PaymarkOE\Helper\Helper
     */
    private $_helper;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $_assetRepo;

    /**
     *
     * @var array
     */
    private $_agreements;

    protected function _construct()
    {
        parent::_construct();
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_customerSession = $objectManager->get("\Magento\Customer\Model\Session");

        $this->_helper = $objectManager->get("\Onfire\PaymarkOE\Helper\AgreementHelper");

        $this->_assetRepo = $objectManager->get("\Magento\Framework\View\Asset\Repository");
    }

    /**
     * Get all saved agreements for the customer
     *
     * @return array
     */
    public function getSavedAgreements()
    {
        if (!empty($this->_agreements)) {
            return $this->_agreements;
        }

        $customerId = $this->_customerSession->getCustomerId();
        $agreements = $this->_helper->getCustomerAgreements($customerId);

        foreach ($agreements as $agreement) {
            $details = json_decode($agreement->getTokenDetails());
            $bankDetails = $this->getBankDetail($details->bank);

            $this->_agreements[$agreement->getEntityId()] = [
                "logo" => $this->getLogoPath($bankDetails['logo']),
                "bank" => $bankDetails['title'],
                "payer" => $details->payer,
                "delete" => $this->_createDeleteUrl($agreement->getEntityId())
            ];
        }

        return $this->_agreements;
    }

    /**
     * Get array of bank information
     *
     * @param $bank
     * @return mixed
     */
    private function getBankDetail($bank)
    {
        return ConfigProvider::BANKS[$bank];
    }

    /**
     * Return absolute path to the themed image asset
     *
     * @param $image
     * @return string
     */
    private function getLogoPath($image)
    {
        return $this->_assetRepo->getUrl("Onfire_PaymarkOE::images/" . $image . ".png");
    }

    /**
     * Generate URL for deleting the token
     *
     * @param $id
     * @return string
     */
    private function _createDeleteUrl($id)
    {
        $url = $this->getUrl(
            'paymarkoe/customer/delete', [
                '_secure' => true,
                'id' => $id
            ]
        );
        return $url;
    }
}
