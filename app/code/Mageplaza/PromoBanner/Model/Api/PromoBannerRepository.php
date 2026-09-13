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
 * @category  Mageplaza
 * @package   Mageplaza_PromoBanner
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Model\Api;

use Magento\Cms\Model\ResourceModel\Block\Collection as CmsCollection;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validator\Url as UrlValidator;
use Mageplaza\PromoBanner\Api\Data\ConfigInterfaceFactory;
use Mageplaza\PromoBanner\Api\Data\FloatingConfigInterfaceFactory;
use Mageplaza\PromoBanner\Api\Data\GeneralConfigInterfaceFactory;
use Mageplaza\PromoBanner\Api\Data\PopupConfigInterfaceFactory;
use Mageplaza\PromoBanner\Api\Data\PromoBannerInterface;
use Mageplaza\PromoBanner\Api\Data\PromoBannerSearchResultsInterface;
use Mageplaza\PromoBanner\Api\Data\SliderConfigInterfaceFactory;
use Mageplaza\PromoBanner\Api\PromoBannerRepositoryInterface;
use Mageplaza\PromoBanner\Helper\Data;
use Mageplaza\PromoBanner\Helper\Image;
use Mageplaza\PromoBanner\Model\Banner;
use Mageplaza\PromoBanner\Model\BannerFactory;
use Mageplaza\PromoBanner\Model\Config\Source\AbstractSource;
use Mageplaza\PromoBanner\Model\Config\Source\AutoClose;
use Mageplaza\PromoBanner\Model\Config\Source\Frequency;
use Mageplaza\PromoBanner\Model\Config\Source\Page;
use Mageplaza\PromoBanner\Model\Config\Source\PopupResponsive;
use Mageplaza\PromoBanner\Model\Config\Source\Position;
use Mageplaza\PromoBanner\Model\Config\Source\Type;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner as BannerResource;
use Mageplaza\PromoBanner\Model\ResourceModel\Banner\CollectionFactory;

/**
 * Class PromoBannerRepository
 * @package Mageplaza\PromoBanner\Model\Api
 */
class PromoBannerRepository implements PromoBannerRepositoryInterface
{
    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var Collection
     */
    private $customerGroupCollection;

    /**
     * @var BannerFactory
     */
    private $bannerFactory;

    /**
     * @var BannerResource
     */
    private $bannerResource;

    /**
     * @var Position
     */
    private $positionSource;

    /**
     * @var UrlValidator
     */
    private $urlValidator;

    /**
     * @var AutoClose
     */
    private $autoCloseSource;

    /**
     * @var Frequency
     */
    private $frequencySource;

    /**
     * @var Image
     */
    private $imageHelper;

    /**
     * @var Type
     */
    private $typeSource;

    /**
     * @var PopupResponsive
     */
    private $popupResponsiveSource;

    /**
     * @var CmsCollection
     */
    private $cmsCollection;

    /**
     * @var Page
     */
    private $pageTypeSource;

    /**
     * @var ConfigInterfaceFactory
     */
    private $configFactory;

    /**
     * @var GeneralConfigInterfaceFactory
     */
    private $generalConfigFactory;

    /**
     * @var SliderConfigInterfaceFactory
     */
    private $sliderConfigFactory;

    /**
     * @var PopupConfigInterfaceFactory
     */
    private $popupConfigFactory;

    /**
     * @var FloatingConfigInterfaceFactory
     */
    private $floatingConfigFactory;

    /**
     * PromoBannerRepository constructor.
     *
     * @param Data $helperData
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param Collection $customerGroupCollection
     * @param BannerFactory $bannerFactory
     * @param BannerResource $bannerResource
     * @param Position $positionSource
     * @param UrlValidator $urlValidator
     * @param AutoClose $autoCloseSource
     * @param Frequency $frequencySource
     * @param Image $imageHelper
     * @param Type $typeSource
     * @param PopupResponsive $popupResponsiveSource
     * @param CmsCollection $cmsCollection
     * @param Page $pageTypeSource
     * @param ConfigInterfaceFactory $configFactory
     * @param GeneralConfigInterfaceFactory $generalConfigFactory
     * @param SliderConfigInterfaceFactory $sliderConfigFactory
     * @param PopupConfigInterfaceFactory $popupConfigFactory
     * @param FloatingConfigInterfaceFactory $floatingConfigFactory
     */
    public function __construct(
        Data $helperData,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        SearchResultsInterfaceFactory $searchResultsFactory,
        Collection $customerGroupCollection,
        BannerFactory $bannerFactory,
        BannerResource $bannerResource,
        Position $positionSource,
        UrlValidator $urlValidator,
        AutoClose $autoCloseSource,
        Frequency $frequencySource,
        Image $imageHelper,
        Type $typeSource,
        PopupResponsive $popupResponsiveSource,
        CmsCollection $cmsCollection,
        Page $pageTypeSource,
        ConfigInterfaceFactory $configFactory,
        GeneralConfigInterfaceFactory $generalConfigFactory,
        SliderConfigInterfaceFactory $sliderConfigFactory,
        PopupConfigInterfaceFactory $popupConfigFactory,
        FloatingConfigInterfaceFactory $floatingConfigFactory
    ) {
        $this->helperData = $helperData;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->customerGroupCollection = $customerGroupCollection;
        $this->bannerFactory = $bannerFactory;
        $this->bannerResource = $bannerResource;
        $this->positionSource = $positionSource;
        $this->urlValidator = $urlValidator;
        $this->autoCloseSource = $autoCloseSource;
        $this->frequencySource = $frequencySource;
        $this->imageHelper = $imageHelper;
        $this->typeSource = $typeSource;
        $this->popupResponsiveSource = $popupResponsiveSource;
        $this->cmsCollection = $cmsCollection;
        $this->pageTypeSource = $pageTypeSource;
        $this->configFactory = $configFactory;
        $this->generalConfigFactory = $generalConfigFactory;
        $this->sliderConfigFactory = $sliderConfigFactory;
        $this->popupConfigFactory = $popupConfigFactory;
        $this->floatingConfigFactory = $floatingConfigFactory;
    }

    /**
     * @throws LocalizedException
     */
    public function checkEnable()
    {
        if (!$this->helperData->isEnabled()) {
            throw new LocalizedException(__('The module is disabled'));
        }
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null)
    {
        $this->checkEnable();

        if ($searchCriteria === null) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        }

        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SearchResultsInterface $searchResult */
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    /**
     * @inheritDoc
     */
    public function delete($id)
    {
        $this->checkEnable();

        $banner = $this->bannerFactory->create();
        $this->bannerResource->load($banner, $id);

        if (!$banner->getId()) {
            throw new LocalizedException(__('Banner does not exits'));
        }

        $this->bannerResource->delete($banner);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function save($promoBanner)
    {
        $this->checkEnable();

        $bannerId = $promoBanner->getBannerId();

        $bannerModel = $this->bannerFactory->create();

        if ($bannerId) {
            $this->bannerResource->load($bannerModel, $bannerId);

            if (!$bannerModel->getId()) {
                throw new NoSuchEntityException(__('No such entity ID'));
            }
        }

        $modelData = $bannerModel->getData();
        $data = $promoBanner->getData();
        $mergedData = array_merge($modelData, $data);
        $this->validateData($mergedData);
        $bannerModel->addData($mergedData);

        $this->bannerResource->save($bannerModel);
        $this->bannerResource->load($bannerModel, $bannerModel->getId());

        $this->helperData->setAutoTime($bannerModel);

        return $bannerModel;
    }

    /**
     * @param array $data
     *
     * @throws InputException
     * @throws FileSystemException
     */
    private function validateData(&$data)
    {
        $requiredFields = [
            PromoBannerInterface::NAME,
            PromoBannerInterface::STATUS,
            PromoBannerInterface::STORE_IDS,
            PromoBannerInterface::CUSTOMER_GROUP_IDS,
            PromoBannerInterface::POSITION,
            PromoBannerInterface::PAGE,
            PromoBannerInterface::TYPE,
            PromoBannerInterface::AUTO_REOPEN_TIME,
            PromoBannerInterface::AUTO_CLOSE_TIME,
        ];

        $missingFields = [];
        foreach ($requiredFields as $requiredField) {
            if (!isset($data[$requiredField]) || $data[$requiredField] === null
                || (is_string($data[$requiredField]) && trim($data[$requiredField]) === '')) {
                $missingFields[] = $requiredField;
            }
        }

        if (!empty($missingFields)) {
            throw new InputException(__('Please specific field(s): %1', implode(',', $missingFields)));
        }

        $validPosition = array_keys($this->positionSource->toArray());
        if (!in_array($data[PromoBannerInterface::POSITION], $validPosition, true)
        ) {
            throw new InputException(__(
                'Please specific %1 field. Valid position must be one of values: %2',
                PromoBannerInterface::POSITION,
                implode(',', $validPosition)
            ));
        }

        if (isset($data[PromoBannerInterface::PAGE_TYPE])) {
            $pageType = explode(',', $data[PromoBannerInterface::PAGE_TYPE]);
            $validPageType = array_keys($this->pageTypeSource->toArray());

            $invalidPageType = array_diff($pageType, $validPageType);
            if (!empty($invalidPageType)) {
                throw new InputException(__(
                    'Please specific %1 field. Valid value must be one of values: %2',
                    PromoBannerInterface::PAGE_TYPE,
                    implode(',', $validPageType)
                ));
            }
        }

        $validPage = [0, 1];

        if (isset($data[PromoBannerInterface::PAGE])
            && !in_array((int)$data[PromoBannerInterface::PAGE], $validPage, true)
        ) {
            throw new InputException(__(
                'Please specific %1 field. Valid page must be one of values: %2',
                PromoBannerInterface::PAGE,
                implode(',', $validPage)
            ));
        }

        if (isset($data[PromoBannerInterface::PRIORITY])) {
            if ($data[PromoBannerInterface::PRIORITY] < 0) {
                throw new InputException(__('Priority is not negative number'));
            }
            $data[PromoBannerInterface::PRIORITY] = (int)$data[PromoBannerInterface::PRIORITY];
        }

        $storeIds = explode(',', $data[PromoBannerInterface::STORE_IDS]);
        $validStoreIds = array_keys($this->helperData->getStoreManager()->getStores());
        $validStoreIds[] = 0;

        $invalidStore = array_diff($storeIds, $validStoreIds);
        if (!empty($invalidStore)) {
            throw new InputException(__(
                'Please specific %1 field. Valid store must be one of values: %2',
                PromoBannerInterface::STORE_IDS,
                implode(',', $validStoreIds)
            ));
        }

        $customerGroupIds = explode(',', $data[PromoBannerInterface::CUSTOMER_GROUP_IDS]);
        $validGroupIds = $this->customerGroupCollection->getAllIds();

        $invalidGroups = array_diff($customerGroupIds, $validGroupIds);
        if (!empty($invalidGroups)) {
            throw new InputException(__(
                'Please specific %1 field. Valid group must be one of values: %2',
                PromoBannerInterface::CUSTOMER_GROUP_IDS,
                implode(',', $validGroupIds)
            ));
        }

        if (isset($data[PromoBannerInterface::URL])
            && $data[PromoBannerInterface::URL]
            && !$this->urlValidator->isValid($data[PromoBannerInterface::URL])
        ) {
            throw new InputException(__(
                'Please specific %1 field. It is not a valid URL',
                PromoBannerInterface::URL
            ));
        }
        $this->validateField(
            PromoBannerInterface::AUTO_CLOSE_TIME,
            $data[PromoBannerInterface::AUTO_CLOSE_TIME],
            $this->autoCloseSource
        );
        $this->validateField(
            PromoBannerInterface::AUTO_REOPEN_TIME,
            $data[PromoBannerInterface::AUTO_REOPEN_TIME],
            $this->frequencySource
        );

        $data[PromoBannerInterface::STATUS] = (bool)$data[PromoBannerInterface::STATUS];

        $this->validateField(
            PromoBannerInterface::TYPE,
            $data[PromoBannerInterface::TYPE],
            $this->typeSource
        );
        if (($data[PromoBannerInterface::TYPE] === Type::SLIDER) && isset($data[PromoBannerInterface::SLIDER_IMAGES])) {
            $sliderImages = $this->helperData->unserialize($data[PromoBannerInterface::SLIDER_IMAGES]);
            foreach ($sliderImages as $id => $sliderImage) {
                if (isset($sliderImage['url'])
                    && $sliderImage['url']
                    && !$this->urlValidator->isValid($sliderImage['url'])
                ) {
                    throw new InputException(__(
                        'Please specific %1 field. Some image URL is not a valid URL',
                        PromoBannerInterface::SLIDER_IMAGES
                    ));
                }
                $name = 'slider_images' . $id . '_image';
                $this->imageHelper->uploadSliderImage($data, $name);
            }
        } elseif ($data[PromoBannerInterface::TYPE] === Type::HTML_TEXT
            || $data[PromoBannerInterface::TYPE] === Type::CMS_BLOCK
        ) {
            if ($data['type'] === Type::CMS_BLOCK) {
                $validCmsBlock = array_column($this->cmsCollection->toOptionArray(), 'value');
                if (!isset($data[PromoBannerInterface::CMS_BLOCK_ID])
                    || !in_array($data[PromoBannerInterface::CMS_BLOCK_ID], $validCmsBlock, false)
                ) {
                    throw new InputException(__(
                        'Please specific %1 field. Valid time must be one of values: %2',
                        PromoBannerInterface::CMS_BLOCK_ID,
                        implode(',', $validCmsBlock)
                    ));
                }
            }
            unset($data[PromoBannerInterface::SLIDER_IMAGES]);
        } else {
            if ($data[PromoBannerInterface::TYPE] === Type::POPUP) {
                $this->validateField(
                    PromoBannerInterface::POPUP_RESPONSIVE,
                    $data[PromoBannerInterface::POPUP_RESPONSIVE],
                    $this->popupResponsiveSource
                );
            }
            $this->imageHelper->uploadBannerImage($data, $data[PromoBannerInterface::TYPE]);
            unset($data[PromoBannerInterface::SLIDER_IMAGES]);
        }
    }

    /**
     * @param string $field
     * @param string|int|mixed $value
     * @param AbstractSource $validSource
     *
     * @throws InputException
     */
    protected function validateField($field, $value, $validSource)
    {
        $validValues = array_keys($validSource->toArray());
        if (!in_array($value, $validValues, false)
        ) {
            throw new InputException(__(
                'Please specific %1 field. Valid value must be one of values: %2',
                $field,
                implode(',', $validValues)
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function getPromoBannersByPage($actionName, SearchCriteriaInterface $searchCriteria = null)
    {
        $this->checkEnable();

        if ($searchCriteria === null) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        }

        $collection = $this->helperData->getPromoBannerCollection();
        $collection->addFieldToFilter(['page', 'page_type'], [['eq' => 0], ['finset' => $actionName]]);

        $this->collectionProcessor->process($searchCriteria, $collection);

        $items = [];
        foreach ($collection as $banner) {
            /** @var Banner $banner */
            $bannerType = $banner->getType();
            if ($bannerType !== Type::SLIDER) {
                $banner->setContent($this->helperData->getBannerHtml($banner));
            }
            $this->helperData->processBannerData($banner);
            $items[] = $banner;
        }

        /** @var SearchResultsInterface|PromoBannerSearchResultsInterface $searchResult */
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($items);
        $searchResult->setTotalCount($collection->getSize());

        return $searchResult;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        $this->checkEnable();
        $generalConfigData = [
            'promo_category' => $this->helperData->serialize($this->helperData->getCategoryList()),
            'show_close_btn' => $this->helperData->showCloseButton(),
            'auto_close_time' => $this->helperData->getAutoCloseTime(),
            'auto_reopen_time' => $this->helperData->getAutoOpenTime()
        ];
        $sliderConfigData = [
            'show_buttons' => $this->helperData->showNav(),
            'change_time' => $this->helperData->getChangeTimeOut(),
        ];
        $popupConfigData = [
            'popup_responsive' => $this->helperData->getPopupResponsive(),
            'popup_width' => $this->helperData->getConfigPopup('popup_width'),
            'popup_height' => $this->helperData->getConfigPopup('popup_height'),
            'show_popup_checkbox' => $this->helperData->getConfigPopup('show_popup_checkbox'),
            'checkbox_label' => $this->helperData->getConfigPopup('checkbox_label'),
        ];
        $floatingConfigData = [
            'float_width' => $this->helperData->getConfigFloating('float_width'),
            'float_height' => $this->helperData->getConfigFloating('float_height'),
        ];
        $generalConfig = $this->generalConfigFactory->create(['data' => $generalConfigData]);
        $sliderConfig = $this->sliderConfigFactory->create(['data' => $sliderConfigData]);
        $popupConfig = $this->popupConfigFactory->create(['data' => $popupConfigData]);
        $floatingConfig = $this->floatingConfigFactory->create(['data' => $floatingConfigData]);

        $config = $this->configFactory->create([
            'data' => [
                'general' => $generalConfig,
                'slider_setting' => $sliderConfig,
                'popup_setting' => $popupConfig,
                'floating_setting' => $floatingConfig,
            ]
        ]);

        return $config;
    }
}
