<?php

namespace Paymark\PaymarkOE\Model\Ui;

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
     * @var \Paymark\PaymarkOE\Helper\Helper
     */
    private $_helper;

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
     * ConfigProvider constructor.
     *
     * @param \Magento\Framework\View\Asset\Repository $assetRepo
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_assetRepo = $assetRepo;

        $this->_objectManager = $objectManager;

        $this->_helper = $this->_objectManager->get('\Paymark\PaymarkOE\Helper\Helper');
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'allow_autopay' => $this->_helper->canUseAutopay(),
                    'production' => $this->_helper->isProdMode(),
                    'logo' => $this->getOnlineEftposLogo()
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
        $url =  $this->_assetRepo->getUrl("Paymark_PaymarkOE::images/logo.svg");;
        return $url;
    }
}