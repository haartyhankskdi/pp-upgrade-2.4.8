<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_AbandonedCart
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\AbandonedCart\Ui\DataProvider;
 
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
 
class MailDataProvider extends Column
{
    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;
 
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        \Magento\Quote\Model\Quote $quoteModel,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->_quoteModel = $quoteModel;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
 
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $quoteId = $item['quote_id'];
                $quote = $this->_quoteModel->getCollection()
                                            ->addFieldToFilter('entity_id', $quoteId);
                foreach ($quote as $quoteItem) {
                    $name = $quoteItem->getCustomerFirstname().' '.$quoteItem->getCustomerLastname();
                }
                $item['name'] = $name;
            }
        }
        return $dataSource;
    }
}
