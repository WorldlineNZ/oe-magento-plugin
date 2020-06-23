<?php

namespace Paymark\PaymarkOE\Model\Adminhtml\Source;

/**
 * Class PaymentAction
 */
class PaymentAction implements \Magento\Framework\Data\OptionSourceInterface
{
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    /**
     * OE can only capture at the moment
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorise and Capture')
            ]
        ];
    }
}
