<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-helpdesk
 * @version   1.1.154
 * @copyright Copyright (C) 2021 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Ui\Component\Listing\Columns;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Mirasvit\Helpdesk\Helper\Timezone;
use Mirasvit\Helpdesk\Model\Config;

class CustomerNameColumn extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var Repository
     */
    private $assetRepo;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var TimezoneInterface
     */
    private $localeDate;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Timezone
     */
    private $timezoneHelper;

    /**
     * CustomerNameColumn constructor.
     * @param Config $config
     * @param Timezone $timezoneHelper
     * @param TimezoneInterface $localeDate
     * @param Repository $assetRepo
     * @param RequestInterface $request
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        Config $config,
        Timezone $timezoneHelper,
        TimezoneInterface $localeDate,
        Repository $assetRepo,
        RequestInterface $request,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);

        $this->assetRepo      = $assetRepo;
        $this->config         = $config;
        $this->localeDate     = $localeDate;
        $this->request        = $request;
        $this->timezoneHelper = $timezoneHelper;
    }

    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($this->getData('name'), $item);
            }
        }

        return $dataSource;
    }


    /**
     * Format data.
     *
     * @param string $fieldName
     * @param array  $item
     *
     * @return string
     */
    protected function prepareItem($fieldName, array $item)
    {
        $customerName = $item[$fieldName];
        $return       = "<span>" . $customerName . '</span>';

        if (!$this->config->getIsShowCustomerTime()) {
            return $return;
        }

        $localTime    = $this->getLocalTime($item);
        if ($localTime) {
            $return .= '<div class="_local-time">';
            if ($this->isLocalNight($item)) {
                $src = $this->getViewFileUrl('Mirasvit_Helpdesk::images/night.svg');
            } else {
                $src = $this->getViewFileUrl('Mirasvit_Helpdesk::images/day.svg');
            }
            $return .= '<span><img src="' . $src . '"></span>';
            $return .= '<span>' . $localTime . '</span>';
            $return .= '</div>';
        }

        return $return;
    }

    /**
     * @param array $item
     *
     * @return string
     * @throws \Exception
     */
    public function getLocalTime($item)
    {
        $timezone = $this->timezoneHelper->getCustomerTimezone($item['ticket_id'], $item['customer_email']);

        if (!$timezone) {
            return "";
        }

        $tz   = new \DateTimeZone($timezone);
        $date = new \DateTime("now", $tz);
        $utc  = $date->format("T");

        return (string)__("%1, %2", $this->formatTime(
            $date,
            \IntlDateFormatter::SHORT
        ), $utc);
    }

    /**
     * Retrieve url of a view file
     *
     * @param string $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);
            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            return "";
//            return $this->_getNotFoundUrl();
        }
    }

    /**
     * @param \DateTime|string|null $time
     * @param int                   $format
     * @param bool                  $showDate
     *
     * @return  string
     */
    private function formatTime(
        $time = null,
        $format = \IntlDateFormatter::SHORT,
        $showDate = false
    ) {
        $time = $time instanceof \DateTimeInterface ? $time : new \DateTime($time);

        return $this->localeDate->formatDateTime(
            $time,
            $showDate ? $format : \IntlDateFormatter::NONE,
            $format
        );
    }

    /**
     * @param array $item
     *
     * @return bool
     * @throws \Exception
     */
    private function isLocalNight($item)
    {
        $timezone = $this->timezoneHelper->getCustomerTimezone($item['ticket_id'], $item['customer_email']);

        if (!$timezone) {
            return false;
        }

        return $this->timezoneHelper->isLocalNight($timezone);
    }
}
