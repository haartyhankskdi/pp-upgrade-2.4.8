<?php

namespace Nilesh\PrescriberNotes\Block\Adminhtml\PrescriberNotes;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes\CollectionFactory;

class RemoteNotesGrid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_coreRegistry = null;

    protected $_collectionFactory;

    public function __construct(
        Context $context,
        Data $backendHelper,
        CollectionFactory $collectionFactory,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_collectionFactory = $collectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('view_custom_grid');
        $this->setDefaultSort('created_at', 'desc');
        $this->setSortable(false);
        $this->setPagerVisibility(true);
        $this->setFilterVisibility(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->_collectionFactory->create()
            ->addFieldToFilter("connect_id",
                $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID)
            );
        $this->setCollection($collection);

        if (!$this->_isExport) {
            $this->getCollection()->load();
            $this->_afterLoadCollection();
        }
        return parent::_prepareCollection();
    }

    /**
     * Get collection object
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function getCollection()
    {
        return $this->_collectionFactory->create();
    }


    protected function _prepareColumns()
    {
        $this->addColumn(
            'prescribernotes_id',
            ['header' => __('ID'), 'index' => 'prescribernotes_id', 'type' => 'number', 'width' => '100px']
        );
        $this->addColumn(
            'id',
            [
                'header' => __('Connect ID'),
                'index' => 'connect_id',
                'type' => 'text'
            ]
        );
        $this->addColumn(
            'subject',
            [
                'header' => __('Subject'),
                'index' => 'subject',
                'type' => 'text'
            ]
        );
        $this->addColumn(
            'customer_name',
            [
                'header' => __('Customer Name'),
                'index' => 'customer_name',
                'type' => 'text'
            ]
        );
        $this->addColumn(
            'created_at',
            [
                'header' => __('Created At'),
                'index' => 'created_at',
                'type' => 'text'
            ]
        );
        return parent::_prepareColumns();
    }

    public function getHeadersVisibility()
    {
        return $this->getCollection()->getSize() >= 0;
    }

    public function getRowUrl($row)
    {
        return false;
//        return $this->getUrl(\Nilesh\PrescriberNotes\Ui\Component\Listing\Column\PrescriberNotesActions::URL_PATH_EDIT, ['id' => $row->getPrescribernotesId()]);
    }
}
