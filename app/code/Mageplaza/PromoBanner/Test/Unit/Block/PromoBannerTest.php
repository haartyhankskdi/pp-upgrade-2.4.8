<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     ${MODULENAME}
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
namespace Mageplaza\PromoBanner\Test\Unit\Block;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DB\Select;
use Magento\Framework\Escaper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\Template\Context;
use Mageplaza\PromoBanner\Block\PromoBanner;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Helper\Image as HelperImage;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\Collection;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\CollectionFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class PromoBannerTest
 * @package Mageplaza\PromoBanner\Test\Unit\Block
 */
class PromoBannerTest extends TestCase
{
    /**
     * @var Context|PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var RequestInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var Escaper|PHPUnit_Framework_MockObject_MockObject
     */
    protected $escape;

    /**
     * @var ManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $_eventManager;

    /**
     * @var Select|PHPUnit_Framework_MockObject_MockObject
     */
    protected $selectMock;

    /**
     * @var CollectionFactory|PHPUnit_Framework_MockObject_MockObject
     */
    protected $bannerCollection;

    /**
     * @var Data|PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperData;

    /**
     * @var DateTime|PHPUnit_Framework_MockObject_MockObject
     */
    protected $date;

    /**
     * @var HttpContext|PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpContext;

    /**
     * @var Registry|PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var HelperImage|PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperImage;

    /**
     * @var PromoBanner
     */
    protected $block;

    protected function setUp()
    {
        $this->context          = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->helperData       = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->getMock();
        $this->date             = $this->getMockBuilder(DateTime::class)->disableOriginalConstructor()->getMock();
        $this->httpContext      = $this->getMockBuilder(HttpContext::class)->disableOriginalConstructor()->getMock();
        $this->registry         = $this->getMockBuilder(Registry::class)->disableOriginalConstructor()->getMock();
        $this->helperImage      = $this->getMockBuilder(HelperImage::class)->disableOriginalConstructor()->getMock();
        $this->bannerCollection = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->selectMock       = $this->getMockBuilder(Select::class)->disableOriginalConstructor()->getMock();
        $this->_eventManager    = $this->getMockBuilder(ManagerInterface::class)
            ->setMethods(['dispatch'])
            ->getMock();
        $this->request          = $this->getMockBuilder(Http::class)
            ->setMethods(['getParam', 'getPost', 'getFullActionName', 'getPostValue'])
            ->disableOriginalConstructor()->getMock();
        $this->escape           = $this->getMockBuilder(Escaper::class)->disableOriginalConstructor()->getMock();
        $this->context->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->context->expects($this->any())->method('getEscaper')->will($this->returnValue($this->escape));
        $this->context->expects($this->any())
            ->method('getEventManager')
            ->willReturn($this->returnValue($this->_eventManager));
        $this->selectMock->expects($this->any())
            ->method('joinLeft')
            ->willReturnSelf();

        $this->block = new PromoBanner(
            $this->context,
            $this->bannerCollection,
            $this->helperData,
            $this->date,
            $this->httpContext,
            $this->registry,
            $this->helperImage,
            []
        );
    }

    public function testAdminInstance()
    {
        $this->assertInstanceOf(PromoBanner::class, $this->block);
    }

    /**
     * @param $actionName
     * @param $position
     *
     * @dataProvider getParams
     */
    public function testGetPromoBannerCollection($actionName, $position)
    {
        $customerGroup = '1';
        $storeId       = '1';
        $datetime      = '2019-07-15 03:39:52';
        $productId     = 6;
        $categoryId    = 3;
        $urlImage      = 'http://192.168.1.200/tuvv/ce232/pub/media/mageplaza/promobanner/banner/image/0/2/02_bg_2.png';
        $content       = '<div class="mppromobanner-banner- mppromobanner-banner-style"><div class="mppromobanner-close">
                <div class="mppromobanner-close-btn" title="Close"></div>
            </div><div class="mppromobanner-container">
                                <a class="mppromobanner-url" href="http://192.168.1.200/tuvv/ce232"
                                target="_blank" rel="noopener noreferrer">
                                    <img class="mppromobanner-image img-responsive"
                                    src="http://192.168.1.200/tuvv/ce232/pub/media/mageplaza/promobanner/banner/image/0/2/02_bg_2.png" alt="0/2/02_bg_2.png">
                                </a>
                            </div></div>';
        $bannerData    = [
            'banner_id'          => '1',
            'status'             => '1',
            'store_ids'          => '1',
            'customer_group_ids' => '0,1,2,3',
            'from_date'          => '2019-07-11 00:00:00',
            'url'                => 'http://192.168.1.200/tuvv/ce232',
            'to_date'            => null,
            'type'               => 'banner_image',
            'banner_image'       => '0/2/02_bg_2.png',
            'category_ids'       => '3,4,5',
            'auto_close_time'    => '60',
            'auto_reopen_time'   => 'infinite'
        ];

        $this->httpContext->method('getValue')->with(CustomerContext::CONTEXT_GROUP)->willReturn($customerGroup);
        $this->helperData->method('getStoreId')->willReturn($storeId);
        $this->helperData->method('showCloseButton')->willReturn(1);
        $this->date->method('date')->willReturn($datetime);
        $this->request->method('getFullActionName')->willReturn($actionName);
        $this->block->setData('position', $position);

        $bannerModel = $this->getMockBuilder(Banner::class)
            ->disableOriginalConstructor()
            ->setMethods(['getType', 'getBannerImage', 'getUrl', 'setContent', 'getAutoCloseTime', 'getAutoReopenTime'])
            ->getMock();
        $collection  = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->bannerCollection->expects($this->any())->method('create')->willReturn($collection);
        $collection->expects($this->once())
            ->method('addActiveFilter')
            ->with($customerGroup, $storeId, $datetime)
            ->willReturnSelf();
        if ($actionName === 'catalog_product_view') {
            $product = $this->getMockBuilder(Product::class)
                ->disableOriginalConstructor()
                ->getMock();
            $product->method('getId')->willReturn($productId);
            $this->registry->method('registry')->with('current_product')->willReturn($product);
            $collection->expects($this->once())
                ->method('getCollectionByProductId')
                ->with($productId)
                ->willReturnSelf();
        } elseif ($actionName === 'catalog_category_view') {
            $category = $this->getMockBuilder(Category::class)
                ->disableOriginalConstructor()
                ->getMock();
            $category->method('getId')->willReturn($categoryId);
            $this->registry->method('registry')->with('current_category')->willReturn($category);
            $collection->expects($this->once())
                ->method('addFieldToFilter')
                ->with(['page', 'category_ids'], [['eq' => 0], ['finset' => $categoryId]])
                ->willReturnSelf();
        } else {
            $collection->expects($this->once())
                ->method('addFieldToFilter')
                ->with(['page', 'page_type'], [['eq' => 0], ['finset' => $actionName]])
                ->willReturnSelf();
        }

        $collection->expects($this->once())
            ->method('addPositionToFilter')
            ->with($position)
            ->willReturn([$bannerModel]);

        $bannerModel->method('getType')->willReturn($bannerData['type']);
        $bannerModel->method('getBannerImage')->willReturn($bannerData['banner_image']);
        $this->helperImage->method('getImageSrc')
            ->with($bannerData['banner_image'])
            ->willReturn($urlImage);
        $this->escape->expects($this->at(0))
            ->method('escapeUrl')
            ->with($urlImage)
            ->willReturn($urlImage);
        $bannerModel->method('getUrl')->willReturn($bannerData['url']);
        $this->escape->expects($this->at(1))
            ->method('escapeUrl')
            ->with($bannerData['url'])
            ->willReturn($bannerData['url']);
        $bannerModel->method('setContent')->with($content)->willReturnSelf();
        $bannerModel->method('getAutoCloseTime')->willReturn($bannerData['auto_close_time']);
        $bannerModel->method('getAutoReopenTime')->willReturn($bannerData['auto_reopen_time']);

        $this->assertEquals([$bannerModel], $this->block->getPromoBannerCollection());
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return [
            ['cms_index_index', 'content-top'],
            ['cms_index_index', 'page-top'],
            ['catalog_product_view', 'content-top'],
            ['catalog_product_view', 'page-top'],
            ['catalog_category_view', 'content-top'],
            ['catalog_category_view', 'page-top'],
        ];
    }
}
