<?php

namespace Amasty\Reports\Block\Adminhtml;

use Amasty\Reports\Model\ReportsDataProvider;
use Magento\Backend\Block\Template;

class Navigation extends Template
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var ReportsDataProvider
     */
    protected $dataProvider;

    public function __construct(
        Template\Context $context,
        ReportsDataProvider $dataProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataProvider = $dataProvider;
    }

    /**
     * @return string
     */
    public function getCurrentTitle()
    {
        if (!$this->title) {
            $config = $this->dataProvider->getConfig();
            foreach ($config as $groupKey => &$group) {
                foreach ($group['children'] as $childKey => &$child) {
                    if (isset($child['url']) && $this->isUrlActive($child['url'])) {
                        $this->title = $child['title'];
                    }
                }
            }
        }

        return $this->title;
    }

    /**
     * @return array|mixed
     */
    public function getMenu()
    {
        $config = $this->dataProvider->getConfig();

        foreach ($config as $groupKey => &$group) {
            if (isset($group['resource']) && !$this->_authorization->isAllowed($group['resource'])) {
                unset($config[$groupKey]);
                continue;
            }

            if (isset($group['url']) && $this->isUrlActive($group['url'])) {
                $group['active'] = true;
            }

            foreach ($group['children'] as $childKey => &$child) {
                if (isset($child['resource']) && !$this->_authorization->isAllowed($child['resource'])) {
                    unset($group['children'][$childKey]);
                    continue;
                }

                if (isset($child['url']) && $this->isUrlActive($child['url'])) {
                    $child['active'] = true;
                    $group['active'] = true;
                }
            }
            unset($child);
        }

        return $config;
    }

    /**
     * @param $url
     * @return bool
     */
    protected function isUrlActive($url)
    {
        $url = $this->normalizeUrl($url);

        return (false !== strpos($this->getRequest()->getPathInfo(), "/$url/"));
    }

    /**
     * @param $url
     * @return string
     */
    protected function normalizeUrl($url)
    {
        $parts = explode('/', $url);

        while (count($parts) < 3) {
            $parts []= 'index';
        }

        return implode('/', $parts);
    }
}
