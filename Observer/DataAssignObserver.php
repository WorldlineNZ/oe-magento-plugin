<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Paymark\PaymarkOE\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;

class DataAssignObserver extends AbstractDataAssignObserver
{

    /**
     * Grab mobile and bank type from request and add them to the payment info
     *
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $method = $this->readMethodArgument($observer);
        $data = $this->readDataArgument($observer);

        $paymentInfo = $method->getInfoInstance();

        if ($data->getDataByKey('additional_data') !== null) {
            $additional = $data->getDataByKey('additional_data');

            if(!empty($additional['mobile_number'])) {
                $paymentInfo->setAdditionalInformation(
                    'mobile_number',
                    $additional['mobile_number']
                );
            }

            if(!empty($additional['selected_bank'])) {
                $paymentInfo->setAdditionalInformation(
                    'selected_bank',
                    $additional['selected_bank']
                );
            }

            // can't use empty here as we need to also catch false
            if(isset($additional['setup_autopay'])) {
                $paymentInfo->setAdditionalInformation(
                    'setup_autopay',
                    $additional['setup_autopay']
                );
            }

            if(!empty($additional['payment_type'])) {
                $paymentInfo->setAdditionalInformation(
                    'payment_type',
                    $additional['payment_type']
                );
            }

            if(!empty($additional['selected_agreement'])) {
                $paymentInfo->setAdditionalInformation(
                    'selected_agreement',
                    $additional['selected_agreement']
                );
            }
        }
    }
}
