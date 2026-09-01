<?php 
namespace Haartyhanks\AuthReview\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
class Thumbnail extends Column
{
    const NAME = "thumbnail";
    const ALT_FIELD = "Image";
    protected $storeManager;
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource["data"]["items"])) {
            $fieldName = $this->getData("name");
            foreach ($dataSource["data"]["items"] as &$item) {
                $filename = $item[$fieldName];
                $item[$fieldName . "_src"] = $filename;
                $item[$fieldName . "_alt"] =  $filename;
                $item[$fieldName . "_orig_src"] = $filename;
            }
        }
        return $dataSource;
    }
    
}
