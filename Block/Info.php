<?php

namespace Paymark\PaymarkOE\Block;

use Magento\Payment\Block\ConfigurableInfo;

class Info extends ConfigurableInfo
{

    /**
     * Prepare payment information for display
     *
     * @param null $transport
     * @return \Magento\Framework\DataObject|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);

        $fields = [
            'TransactionID' => 'Transaction ID',
            'mobile_number' => 'Phone Number',
            'selected_bank' => 'Bank'
        ];

        $payment = $this->getInfo();
        foreach ($fields as $fieldKey => $fieldName) {
            if ($payment->getAdditionalInformation($fieldKey) !== null) {
                $this->setDataToTransfer(
                    $transport,
                    $fieldName,
                    $payment->getAdditionalInformation($fieldKey)
                );
            }
        }

        return $transport;
    }
}
