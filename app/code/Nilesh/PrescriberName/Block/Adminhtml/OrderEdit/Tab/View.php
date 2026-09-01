<?php
namespace Nilesh\PrescriberName\Block\Adminhtml\OrderEdit\Tab;

/**
 * Order Prescriber tab
 *
 */
class View extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = 'tab/view/pre_order_info.phtml';

    protected $_prescriberName;

    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Nilesh\PrescriberName\Model\PrescriberNameFactory $prescriberNameFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_prescriberNameFactory = $prescriberNameFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Prescriber Name');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Prescriber Name');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getPrescriberName()
    {
        return $this->getOrder()->getData('prescriber_name');
    }

    public function getPrescriberList()
    {
        $return = array();
        $model = $this->_prescriberNameFactory->create();
        $collection = $model->getCollection();
        $collection->addFieldToFilter("status", "1");
        return $collection->getData();
        // foreach ($collection->getData() as $row) {
        //     if($row['status'] == '1'){
        //         $return[] = array(
        //             'value' => $row['prescribername_id'],
        //             'name' => $row['name']
        //         );
        //     }
        // }
        // return $return;
    }

}