<?php

namespace Onfire\PaymarkOE\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{

    const CODE = 'paymarkoe';

    const BANKS = [
        'ASB' => 'ASB',
        'HEARTLAND' => 'Heartland Bank',
        'COOPERATIVE' => 'The Co-operative Bank',
        'WESTPAC' => 'Westpac'
    ];

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
                    'available_banks' => self::BANKS
                ]
            ]
        ];
    }
}