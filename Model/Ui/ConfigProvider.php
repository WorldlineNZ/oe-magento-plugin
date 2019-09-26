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

    const CODE = 'paymarkoe';

    const BANKS = [
        'ASB' => 'ASB',
        'HEARTLAND' => 'Heartland Bank',
        'COOPERATIVE' => 'The Co-operative Bank',
        'WESTPAC' => 'Westpac'
    ];

    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo
    ){
        $this->_assetRepo = $assetRepo;
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
                    'allow_autopay' => false,
                    'available_banks' => self::BANKS,
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
        $url =  $this->_assetRepo->getUrl("Onfire_PaymarkOE::images/logo.png");;
        return $url;
    }
}