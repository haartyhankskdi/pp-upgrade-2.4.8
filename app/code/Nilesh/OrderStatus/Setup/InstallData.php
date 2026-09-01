<?php

namespace Nilesh\OrderStatus\Setup;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class InstallData implements InstallDataInterface
{
    protected $statusFactory;
    protected $statusResourceFactory;
    protected $orderState;

    public function __construct(
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
        $this->orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->addCustomOrderStatus();
    }

    protected function addCustomOrderStatus()
    {
        $statusResource = $this->statusResourceFactory->create();
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => "approve",
            'label' => "Approved",
        ]);
        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }
        $status->assignState($this->orderState, false, false);

        // disapprove
        $statusResource = $this->statusResourceFactory->create();
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => "disapprove",
            'label' => "Disapproved",
        ]);
        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }
        $status->assignState($this->orderState, false, false);
    }
}