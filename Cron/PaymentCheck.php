<?php

namespace Paymark\PaymarkOE\Cron;

use Paymark\PaymarkOE\Model\Ui\ConfigProvider;

class PaymentCheck
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderFactory;

    /**
     * @var \Paymark\PaymarkOE\Helper\Helper
     */
    private $helper;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $factory,
        \Paymark\PaymarkOE\Helper\Helper $helper
    )
    {
        $this->orderFactory = $factory;
        $this->helper = $helper;
    }

    public function execute()
    {
        $orders = $this->getOrderCollection();
        if(!$orders->count()) {
            return false;
        }

        foreach($orders as $order) {
            try {
                $this->helper->processOrder($order, false);
            } catch (\Exception $e) {
                $this->helper->log(__METHOD__ . " " . $e->getMessage());
            }
        }
    }

    /**
     * Get all orders that used this payment method, are still pending and created within the past 10 minutes
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrderCollection()
    {
        //only check orders within that past 10 minutes
        $now = new \DateTime("-10 minutes");

        $collection = $this->orderFactory->create()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('status',
                ['in' => \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT]
            )->addFieldToFilter('created_at',
                ['gteq' => $now]
            );

        $collection->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                ['method']
            )
            ->where('sop.method = ?', ConfigProvider::CODE);

        $collection->setOrder(
            'created_at',
            'desc'
        );

        return $collection;
    }
}