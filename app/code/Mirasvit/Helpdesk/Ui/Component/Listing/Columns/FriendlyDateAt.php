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

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;

class FriendlyDateAt extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var DataBundle
     */
    private $dataBundle;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Mirasvit\Helpdesk\Helper\StringUtil
     */
    private $helpdeskString;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Mirasvit\Helpdesk\Helper\StringUtil $helpdeskString,
        TimezoneInterface $timezone,
        ResolverInterface $localeResolver,
        DataBundle $dataBundle,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->helpdeskString = $helpdeskString;
        $this->timezone       = $timezone;
        $this->localeResolver = $localeResolver;
        $this->locale         = $this->localeResolver->getLocale();
        $this->dataBundle     = $dataBundle;
    }

    /**
     * @inheritdoc
     * @since 101.1.1
     */
    public function prepare()
    {
        $config = $this->getData('config');
        if (isset($config['filter'])) {
            $config['filter'] = [
                'filterType' => 'dateRange',
                'templates'  => [
                    'date' => [
                        'options' => [
                            'dateFormat' => $this->timezone->getDateFormatWithLongYear(),
                        ],
                    ],
                ],
            ];
        }

        $localeData = $this->dataBundle->get($this->locale);

        if (!isset($config['dateFormat'])) {
            $config['dateFormat'] = $this->timezone->getDateTimeFormat(\IntlDateFormatter::MEDIUM);
        }
        $this->setData('config', $config);

        parent::prepare();
    }

    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = $this->helpdeskString->nicetime(strtotime($item[$fieldName]));
                }
            }
        }

        return $dataSource;
    }
}
