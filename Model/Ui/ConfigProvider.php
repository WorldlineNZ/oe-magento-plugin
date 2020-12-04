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