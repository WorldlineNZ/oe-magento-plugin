<?php

namespace Onfire\PaymarkOE\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $_assetRepo;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $_session;

    /**
     * @var \Onfire\PaymarkOE\Helper\Helper
     */
    private $_helper;

    /**
     * @var \Onfire\PaymarkOE\Helper\AgreementHelper
     */
    private $_agreementHelper;

    const CODE = 'paymarkoe';

    const VAULT_CODE = 'paymarkoe_vault';

    /**
     * All bank details
     */
    const BANKS = [
        'ASB' => [
            'title' => 'ASB',
            'short' => 'ASB',
            'image' => 'popup-asb',
            'logo' => 'logo-asb'
        ],
        'HEARTLAND' => [
            'title' => 'Heartland Bank',
            'short' => 'Heartland',
            'image' => 'popup-heartland',
            'logo' => 'logo-heartland'
        ],
        'COOPERATIVE' => [
            'title' => 'The Co-operative Bank',
            'short' => 'Co-operative',
            'image' => 'popup-cooperative',
            'logo' => 'logo-cooperative'
        ],
        'WESTPAC' => [
            'title' => 'Westpac',
            'short' => 'Westpac',
            'image' => 'popup-westpac',
            'logo' => 'logo-westpac'
        ],
    ];

    /**
     * Banks that are allowed to use Autopay
     */
    const AUTOPAY_BANKS = [
        'ASB'
    ];

    const TYPE_STANDARD = 'standard';

    const TYPE_AUTOPAY = 'autopay';

    /**
     * ConfigProvider constructor.
     *
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Customer\Model\Session $session
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Customer\Model\Session $session
    )
    {
        $this->_assetRepo = $assetRepo;

        $this->_objectManager = $objectManager;

        $this->_session = $session;

        $this->_helper = $this->_objectManager->get('\Onfire\PaymarkOE\Helper\Helper');

        $this->_agreementHelper = $this->_objectManager->get('\Onfire\PaymarkOE\Helper\AgreementHelper');
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $allowAutopay = ($this->_session->isLoggedIn() && $this->_helper->getConfig('allow_autopay') == 1);

        return [
            'payment' => [
                self::CODE => [
                    'allow_autopay' => $allowAutopay,
                    'available_banks' => self::BANKS,
                    'autopay_banks' => self::AUTOPAY_BANKS,
                    'logo' => $this->getOnlineEftposLogo(),
                    'bank_logos' => $this->getLogoImages(),
                    'popup_images' => $this->getPopupImages(),
                    'agreements' => $this->getAgreements(),
                    'type_standard' => self::TYPE_STANDARD,
                    'type_autopay' => self::TYPE_AUTOPAY
                ]
            ]
        ];
    }

    /**
     * Get absolute path to the Online EFTPOS logo
     *
     * @return string
     */
    public function getOnlineEftposLogo()
    {
        $url =  $this->_assetRepo->getUrl("Onfire_PaymarkOE::images/logo.png");;
        return $url;
    }

    /**
     * Get each logo image for each bank
     *
     * @return array
     */
    public function getLogoImages()
    {
        $images = [];

        foreach(self::BANKS as $key => $value) {
            $images[$key] = $this->getImagePath($value['logo'] . ".svg");
        }

        return $images;
    }

    /**
     * Get each popup image for each bank
     *
     * @return array
     */
    public function getPopupImages()
    {
        $images = [];

        foreach(self::BANKS as $key => $value) {
            $images[$key] = $this->getImagePath($value['image'] . ".png");
        }

        return $images;
    }

    /**
     * Get agreements available for frontend
     *
     * @return array
     */
    public function getAgreements()
    {
        $agreements = $this->_agreementHelper->getCustomerAgreements($this->_session->getCustomerId());
        $list = [];

        foreach ($agreements as $agreement) {
            $details = json_decode($agreement->getTokenDetails());

            $list[$agreement->getEntityId()] = [
                'id' => $agreement->getEntityId(),
                'bank' => $details->bank,
                'payer' => $details->payer
            ];
        }

        return $list;
    }

    /**
     * Return absolute path to the themed image asset
     *
     * @param $image
     * @return string
     */
    private function getImagePath($image)
    {
        return $this->_assetRepo->getUrl("Onfire_PaymarkOE::images/" . $image);
    }
}