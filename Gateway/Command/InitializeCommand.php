<?php

namespace Onfire\PaymarkOE\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Model\Order\Payment\Interceptor;
use Onfire\PaymarkOE\Helper\Helper;

/**
 * InitializeCommand
 */
class InitializeCommand implements CommandInterface
{

    /**
     * Create payment request with Paymark OE
     *
     * @param array $commandSubject
     * @return \Magento\Payment\Gateway\Command\ResultInterface|null|void
     * @throws LocalizedException
     */
    public function execute(array $commandSubject)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create("\Onfire\PaymarkOE\Helper\Helper");
        $helper->log(__METHOD__. ' execute');

        /** @var \Onfire\PaymarkOE\Helper\ApiHelper $apiHelper */
        $apiHelper = $objectManager->create("\Onfire\PaymarkOE\Helper\ApiHelper");

        $orderState = $commandSubject['stateObject'];

        $paymentAction = $commandSubject['paymentAction'];
        $helper->log(__METHOD__. ' action:' . $paymentAction);

        /** @var PaymentDataObject $paymentDO */
        $paymentDO = $commandSubject['payment'];

        /** @var OrderAdapterInterface $order */
        $order = $paymentDO->getOrder();

        /** @var Interceptor $payment */
        $payment = $paymentDO->getPayment();

        $helper->log(__METHOD__. " create payment request for orderId: {$order->getOrderIncrementId()}");

        try {
            // create remote payment request with Paymark
            $transactionId = $apiHelper->createPaymentRequest($payment, $orderState, $paymentAction);

            // save to additionalInformation for later
            $additionalInfo = $payment->getAdditionalInformation();
            $additionalInfo["TransactionID"] = $transactionId;

            $payment->unsAdditionalInformation();
            $payment->setAdditionalInformation($additionalInfo);

            $helper->log(__METHOD__. " set payment info with remote transaction id");
        } catch(\Exception $e) {
            $helper->log(__METHOD__. " initialize exception");
            throw new LocalizedException(__($e->getMessage()));
        }

        try {
            // check if the payment is already complete
            $result = $helper->checkTransactionAndProcess($apiHelper->findTransaction($transactionId), $orderState);

            if($result && $result == Helper::RESULT_FAILED) {
                throw new \Exception('Payment was declined');
            }

        } catch(\Exception $e) {
            $helper->log(__METHOD__. " initialize exception");
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
