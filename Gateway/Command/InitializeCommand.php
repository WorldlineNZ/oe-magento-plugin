<?php

namespace Paymark\PaymarkOE\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment\Interceptor;
use Paymark\PaymarkOE\Helper\Helper;

/**
 * InitializeCommand
 */
class InitializeCommand implements CommandInterface
{

    /**
     * Create new OpenJS session with Paymark
     *
     * @param array $commandSubject
     * @return \Magento\Payment\Gateway\Command\ResultInterface|null|void
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create("\Paymark\PaymarkOE\Helper\Helper");
        $helper->log(__METHOD__. ' execute');

        /** @var \Paymark\PaymarkOE\Helper\ApiHelper $apiHelper */
        $apiHelper = $objectManager->create("\Paymark\PaymarkOE\Helper\ApiHelper");

        $orderState = $commandSubject['stateObject'];

        /** @var PaymentDataObject $paymentDO */
        $paymentDO = $commandSubject['payment'];

        /** @var OrderAdapterInterface $order */
        $order = $paymentDO->getOrder();

        /** @var Interceptor $payment */
        $payment = $paymentDO->getPayment();

        $helper->log(__METHOD__. " create payment request for orderId: {$order->getOrderIncrementId()}");

        try {
            // create remote OpenJS payment session with Paymark
            $transactionId = $apiHelper->createPaymentRequest($payment, $orderState);

            // save session id to additionalInformation for later
            $additionalInfo = $payment->getAdditionalInformation();
            $additionalInfo["TransactionID"] = $transactionId;

            $payment->unsAdditionalInformation();
            $payment->setAdditionalInformation($additionalInfo);

            $helper->log(__METHOD__. " set payment info with remote transaction id");
        } catch(\Exception $e) {
            $helper->log(__METHOD__. " initialize exception");
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
