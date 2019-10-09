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
     * @var \Onfire\PaymarkOE\Helper\Helper
     */
    private $_helper;

    const CODE = 'paymarkoe';

    /**
     * All bank details
     */
    const BANKS = [
        'ASB' => [
            'title' => 'ASB',
            'short' => 'ASB',
            'image' => 'popup-asb'
        ],
        'HEARTLAND' => [
            'title' => 'Heartland Bank',
            'short' => 'Heartland',
            'image' => 'popup-heartland'
        ],
        'COOPERATIVE' => [
            'title' => 'The Co-operative Bank',
            'short' => 'Co-operative',
            'image' => 'popup-cooperative'
        ],
        'WESTPAC' => [
            'title' => 'Westpac',
            'short' => 'Westpac',
            'image' => 'popup-westpac'
        ],
    ];

    /**
     * Banks that are allowed to use Autopay
     */
    CONST AUTOPAY_BANKS = [];

    /**
     * ConfigProvider constructor.
     *
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ){
        $this->_assetRepo = $assetRepo;

        $this->_objectManager = $objectManager;

        $this->_helper = $this->_objectManager->get('\Onfire\PaymarkOE\Helper\Helper');
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $allowAutopay = $this->_helper->getConfig('allow_autopay');

        return [
            'payment' => [
                self::CODE => [
                    'allow_autopay' => $allowAutopay == 1 ? true : false,
                    'available_banks' => self::BANKS,
                    'autopay_banks' => self::AUTOPAY_BANKS,
                    'logo' => $this->getOnlineEftposLogo(),
                    'popup_images' => $this->getPopupImages()
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
     * Get each popup image for each bank
     *
     * @return array
     */
    public function getPopupImages()
    {
        $images = [];

        foreach(self::BANKS as $key => $value) {
            $images[$key] = $this->_assetRepo->getUrl("Onfire_PaymarkOE::images/" . $value['image'] . ".png");
        }

        return $images;
    }
}