<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Onfire\PaymarkOE\Observer;

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

            $paymentInfo->setAdditionalInformation(
                'mobile_number',
                $additional['mobile_number']
            );

            $paymentInfo->setAdditionalInformation(
                'selected_bank',
                $additional['selected_bank']
            );
        }
    }
}
