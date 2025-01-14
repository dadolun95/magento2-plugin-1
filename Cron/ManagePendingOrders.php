<?php

namespace Satispay\Satispay\Cron;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Satispay\Satispay\Model\Method\Satispay;

/**
 * Class ManagePendingOrders
 * @package Satispay\Satispay\Cron
 */
class ManagePendingOrders
{
    /**
     * @var Satispay
     */
    private $satispay;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * ManagePendingOrders constructor.
     * @param Satispay $satispay
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        Satispay $satispay,
        OrderCollectionFactory $orderCollectionFactory
    )
    {
        $this->satispay = $satispay;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }


    public function execute()
    {
        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter('status', 'pending');

        $orders->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method')
            )
            ->where('sop.method = ?', 'satispay');

        $orders = $orders->getItems();

        /**
         * @var \Magento\Sales\Model\Order $order
         */
        foreach ($orders as $order) {
            $satispayPayment = $this->satispay->checkPayment($order->getData('satispay_payment_id'));
            if (!empty($satispayPayment) && property_exists($satispayPayment, 'status')) {
                switch ($satispayPayment->status) {
                    case  Satispay::ACCEPTED_STATUS:
                        $successMessage = __('Payment checked with status %1.', Satispay::ACCEPTED_STATUS);
                        $this->satispay->acceptOrder($order, $satispayPayment, $successMessage);
                        break;
                    case  Satispay::CANCELED_STATUS:
                        $cancelMessage = __('Payment checked with status %1.', Satispay::CANCELED_STATUS);
                        $this->satispay->cancelOrder($order, $cancelMessage);
                        break;
                    case Satispay::PENDING_STATUS:
                        if ($satispayPayment->expired) {
                            $cancelMessage = __('Payment checked with status %1.', Satispay::CANCELED_STATUS);
                            $this->satispay->cancelOrder($order, $cancelMessage);
                        }
                        break;
                    default:
                        break;
                }
            }
        }
    }
}
