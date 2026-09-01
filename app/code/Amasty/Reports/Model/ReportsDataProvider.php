<?php

declare(strict_types=1);

namespace Amasty\Reports\Model;

use Amasty\Reports\Api\Data\RuleInterface;
use Amasty\Reports\Api\RuleRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Psr\Log\LoggerInterface;

class ReportsDataProvider
{
    public const NEW_RULE_VALUE = 'new_rule';
    public const DASHBOARD_VALUE = 'dashboard';
    public const SALES_VALUE = 'sales';
    public const OVERVIEW_VALUE = 'overview';
    public const OVERVIEW_COMPARE_VALUE = 'overview_compare';
    public const ORDERS_VALUE = 'orders';
    public const ORDER_ITEMS_VALUE = 'order_items';
    public const SALES_BY_VALUE = 'sales_by';
    public const BY_HOUR_VALUE = 'by_hour';
    public const BY_DAY_VALUE = 'by_day';
    public const BY_COUNTRY_VALUE = 'by_country';
    public const BY_COUNTRY_STATE_VALUE = 'by_country_state';
    public const BY_PAYMENT_VALUE = 'by_payment';
    public const BY_GROUP_VALUE = 'by_group';
    public const BY_COUPON_VALUE = 'by_coupon';
    public const BY_CATEGORY_VALUE = 'by_category';
    public const BY_CART_PRICE_RULE_VALUE = 'by_cart_price_rule';
    public const CATALOG_VALUE = 'catalog';
    public const BY_PRODUCT_VALUE = 'by_product';
    public const BY_ATTRIBUTES_VALUE = 'by_attributes';
    public const BY_BRANDS_VALUE = 'by_brands';
    public const PRODUCT_PERFORMANCE_VALUE = 'product_performance';
    public const BESTSELLERS_VALUE = 'bestsellers';
    public const CUSTOMERS_ALL_VALUE = 'customers_all';
    public const CUSTOMERS_VALUE = 'customers';
    public const RETURNING_VALUE = 'returning';
    public const ABANDONED_VALUE = 'abandoned';
    public const CONVERSION_RATE_VALUE = 'conversion_rate';
    public const CUSTOM_VALUE = 'custom';
    public const MORE_ANALYTICS = 'more_analytics';
    public const QUOTE_VALUE = 'quote';

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var Repository
     */
    private $assetRepo;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $customSalesAndOrdersReports;

    /**
     * @var array
     */
    private $customSalesByReports;

    /**
     * @var array
     */
    private $customCategoriesReports;

    /**
     * @var array
     */
    private $customCustomersReports;

    public function __construct(
        Manager $moduleManager,
        Repository $assetRepo,
        UrlInterface $urlBuilder,
        RuleRepositoryInterface $ruleRepository,
        LoggerInterface $logger,
        $customSalesAndOrdersReports = [],
        $customSalesByReports = [],
        $customCategoriesReports = [],
        $customCustomersReports = []
    ) {
        $this->moduleManager = $moduleManager;
        $this->assetRepo = $assetRepo;
        $this->urlBuilder = $urlBuilder;
        $this->ruleRepository = $ruleRepository;
        $this->logger = $logger;
        $this->customSalesAndOrdersReports = $customSalesAndOrdersReports;
        $this->customSalesByReports = $customSalesByReports;
        $this->customCategoriesReports = $customCategoriesReports;
        $this->customCustomersReports = $customCustomersReports;
    }

    public function getConfig(): array
    {
        $dashboardReports = [
            self::DASHBOARD_VALUE => [
                'title'     => __('Dashboard'),
                'url'       => 'amasty_reports/report',
                'children'  => [],
                'resource'  => 'Amasty_Reports::reports'
            ]
        ];

        $customReports = [
            self::CUSTOM_VALUE => [
                'title' => __('Custom Reports'),
                'resource' => 'Amasty_Reports::rule',
                'class' => 'amreports-newrule-container',
                'children' => $this->getCustomReports()
            ]
        ];

        $this->disableCustomReportsIfNeed();

        return array_merge(
            $dashboardReports,
            $this->getSalesAndOrdersReports(),
            $this->getSalesByReports(),
            $this->getCatalogReports(),
            $this->getCustomersReports(),
            $customReports,
            $this->getMoreAnalyticsReports()
        );
    }

    private function disableCustomReportsIfNeed()
    {
        $this->disableReports($this->customSalesAndOrdersReports);
        $this->disableReports($this->customSalesByReports);
        $this->disableReports($this->customCategoriesReports);
        $this->disableReports($this->customCustomersReports);
    }

    private function disableReports(array &$reports): void
    {
        foreach ($reports as $key => $report) {
            if (isset($report['module']) && $report['module'] && !$this->moduleManager->isEnabled($report['module'])) {
                unset($reports[$key]);
            }
        }
    }

    private function getSalesAndOrdersReports(): array
    {
        $reports = [
            self::OVERVIEW_VALUE => [
                'title' => __('Overview'),
                'url' => 'amasty_reports/report_sales/overview',
                'resource'  => 'Amasty_Reports::reports_sales_overview'
            ],
            self::OVERVIEW_COMPARE_VALUE => [
                'title' => __('Sales Comparison'),
                'url' => 'amasty_reports/report_sales/compare',
                'resource'  => 'Amasty_Reports::reports_sales_quote'
            ],
            self::ORDERS_VALUE => [
                'title' => __('Orders'),
                'url' => 'amasty_reports/report_sales/orders',
                'resource'  => 'Amasty_Reports::reports_sales_orders'
            ],
            self::ORDER_ITEMS_VALUE => [
                'title' => __('Order Items'),
                'url' => 'amasty_reports/report_sales/orderItems',
                'resource'  => 'Amasty_Reports::reports_sales_order_items'
            ],
        ];

        return [
            self::SALES_VALUE => [
                'title'     => __('Sales and Orders'),
                'resource'  => 'Amasty_Reports::reports_sales',
                'children'  => array_merge($reports, $this->customSalesAndOrdersReports)
            ]
        ];
    }

    private function getSalesByReports(): array
    {
        $reports = [
            self::BY_HOUR_VALUE => [
                'title' => __('Hour'),
                'url' => 'amasty_reports/report_sales/hour',
                'resource'  => 'Amasty_Reports::reports_sales_hour'
            ],
            self::BY_DAY_VALUE => [
                'title' => __('Week'),
                'url' => 'amasty_reports/report_sales/weekday',
                'resource'  => 'Amasty_Reports::reports_sales_weekday'
            ],
            self::BY_COUNTRY_VALUE => [
                'title' => __('Country'),
                'url' => 'amasty_reports/report_sales/country',
                'resource'  => 'Amasty_Reports::reports_sales_country'
            ],
            self::BY_COUNTRY_STATE_VALUE => [
                'title' => __('Country - State'),
                'url' => 'amasty_reports/report_sales/state',
                'resource'  => 'Amasty_Reports::reports_sales_state'
            ],
            self::BY_PAYMENT_VALUE => [
                'title' => __('Payment Type'),
                'url' => 'amasty_reports/report_sales/payment',
                'resource'  => 'Amasty_Reports::reports_sales_payment'
            ],
            self::BY_GROUP_VALUE => [
                'title' => __('Customer Group'),
                'url' => 'amasty_reports/report_sales/group',
                'resource'  => 'Amasty_Reports::reports_sales_group'
            ],
            self::BY_COUPON_VALUE => [
                'title' => __('Coupon'),
                'url' => 'amasty_reports/report_sales/coupon',
                'resource'  => 'Amasty_Reports::reports_sales_coupon'
            ],
            self::BY_CATEGORY_VALUE => [
                'title' => __('Category'),
                'url' => 'amasty_reports/report_sales/category',
                'resource'  => 'Amasty_Reports::reports_sales_category'
            ],
            self::BY_CART_PRICE_RULE_VALUE => [
                'title' => __('Cart Price Rules'),
                'url' => 'amasty_reports/report_sales/cartRule',
                'resource'  => 'Amasty_Reports::reports_sales_cartRule'
            ],
        ];

        return [
            self::SALES_BY_VALUE => [
                'title'     => __('Sales By'),
                'resource'  => 'Amasty_Reports::reports_sales_by',
                'children'  => array_merge($reports, $this->customSalesByReports),
            ]
        ];
    }

    private function getCatalogReports(): array
    {
        $reports = [
            self::BY_PRODUCT_VALUE => [
                'title' => __('By Product'),
                'url' => 'amasty_reports/report_catalog/byProduct',
                'resource'  => 'Amasty_Reports::reports_catalog_by_product'
            ],
            self::BY_ATTRIBUTES_VALUE => [
                'title' => __('By Product Attributes'),
                'url' => 'amasty_reports/report_catalog/byAttributes',
                'resource'  => 'Amasty_Reports::reports_catalog_by_attributes'
            ],
            self::BY_BRANDS_VALUE => [
                'title' => __('By Brands'),
                'url' => 'amasty_reports/report_catalog/byBrands',
                'resource'  => 'Amasty_Reports::reports_catalog_by_brands'
            ],
            self::PRODUCT_PERFORMANCE_VALUE => [
                'title' => __('Product Performance'),
                'url' => 'amasty_reports/report_catalog/productPerformance',
                'resource'  => 'Amasty_Reports::reports_catalog_product_performance'
            ],
            self::BESTSELLERS_VALUE => [
                'title' => __('Bestsellers'),
                'url' => 'amasty_reports/report_catalog/bestsellers',
                'resource'  => 'Amasty_Reports::reports_catalog_bestsellers'
            ],
        ];

        return [
            self::CATALOG_VALUE => [
                'title'     => __('Catalog'),
                'resource'  => 'Amasty_Reports::reports_catalog',
                'children'  => array_merge($reports, $this->customCategoriesReports)
            ]
        ];
    }

    private function getCustomersReports(): array
    {
        $reports = [
            self::CUSTOMERS_VALUE => [
                'title' => __('Customers'),
                'url' => 'amasty_reports/report_customers/customers',
                'resource'  => 'Amasty_Reports::reports_customers_customers'
            ],
            self::RETURNING_VALUE => [
                'title' => __('New vs Returning Customers'),
                'url' => 'amasty_reports/report_customers/returning',
                'resource'  => 'Amasty_Reports::reports_customers_returning'
            ],
            self::ABANDONED_VALUE => [
                'title' => __('Abandoned Carts'),
                'url' => 'amasty_reports/report_customers/abandoned',
                'resource'  => 'Amasty_Reports::reports_customers_abandoned'
            ],
            self::CONVERSION_RATE_VALUE => [
                'title' => __('Conversion Rate'),
                'url' => 'amasty_reports/report_customers/conversionRate',
                'resource'  => 'Amasty_Reports::reports_customers_conversion_rate'
            ],
        ];

        return [
            self::CUSTOMERS_ALL_VALUE => [
                'title'     => __('Customers'),
                'resource'  => 'Amasty_Reports::reports_customers',
                'children'  => array_merge($reports, $this->customCustomersReports)
            ]
        ];
    }

    private function getMoreAnalyticsReports(): array
    {
        return [
            self::MORE_ANALYTICS => [
                'title'     => __('More Analytics'),
                'resource'  => 'Amasty_Reports::reports',
                'children'  => [
                    'advanced_search' => [
                        'title' => __('Advanced Search'),
                        'url' => 'amsearch/report/index',
                        'class' => 'amreports-more-analytic',
                        'isInstalled' => $this->moduleManager->isEnabled('Amasty_Xsearch'),
                        'resource'  => 'Amasty_Reports::reports',
                        'tooltip_description'  => __('Stay ahead of competitors by understanding the demand.'),
                        'img' => $this->assetRepo->getUrl('Amasty_Reports::img/more-analytic/advanced-search.png'),
                        'adv_url' => 'https://amasty.com/advanced-search-for-magento-2.html'
                            . '?utm_source=demo&utm_medium=gotopage&utm_campaign=advanced-search_m2',
                        'adv_m_url' => 'https://marketplace.magento.com/amasty-xsearch.html',
                    ],
                    'out_of_stock_notification' => [
                        'title' => __('Out Of Stock Notification'),
                        'url' => 'xnotif/analytics/index',
                        'class' => 'amreports-more-analytic',
                        'isInstalled' => $this->moduleManager->isEnabled('Amasty_Xnotif'),
                        'resource'  => 'Amasty_Reports::reports',
                        'tooltip_description'  => __('Stay ahead of competitors by understanding the demand.'),
                        'img' => $this->assetRepo->getUrl('Amasty_Reports::img/more-analytic/osn.png'),
                        'adv_url' => 'https://amasty.com/out-of-stock-notification-for-magento-2.html'
                            . '?utm_source=demo&utm_medium=gotopage&utm_campaign=out-of-stock-notification_m2',
                        'adv_m_url' => 'https://marketplace.magento.com/amasty-xnotif.html',
                    ],
                    'one_step_checkout' => [
                        'title' => __('One Step Checkout'),
                        'url' => 'amasty_checkout/reports/',
                        'class' => 'amreports-more-analytic',
                        'isInstalled' => $this->moduleManager->isEnabled('Amasty_Checkout'),
                        'resource'  => 'Amasty_Reports::reports',
                        'tooltip_description'  => __('Stay ahead of competitors by understanding the demand.'),
                        'img' => $this->assetRepo->getUrl('Amasty_Reports::img/more-analytic/one-step-checkout.png'),
                        'adv_url' => 'https://amasty.com/one-step-checkout-for-magento-2.html'
                            . '?utm_source=demo&utm_medium=gotopage&utm_campaign=one-step-checkout_m2',
                        'adv_m_url' => 'https://marketplace.magento.com/amasty-module-single-step-checkout.html',
                    ],
                    'social_login' => [
                        'title' => __('Social Login'),
                        'url' => 'customer/index/',
                        'class' => 'amreports-more-analytic',
                        'isInstalled' => $this->moduleManager->isEnabled('Amasty_SocialLogin'),
                        'resource'  => 'Amasty_Reports::reports',
                        'tooltip_description'  => __('Stay ahead of competitors by understanding the demand.'),
                        'img' => $this->assetRepo->getUrl('Amasty_Reports::img/more-analytic/social-login.png'),
                        'adv_url' => 'http://amasty.com/social-login-for-magento-2.html'
                            . '?utm_source=demo&utm_medium=gotopage&utm_campaign=social-login_m2',
                        'adv_m_url' => 'https://marketplace.magento.com/amasty-social-login-meta.html',
                    ],
                ]
            ]
        ];
    }

    private function getCustomReports(): array
    {
        $result = [];
        try {
            /** @var RuleInterface $rule */
            foreach ($this->ruleRepository->getPinnedRules()->getItems() as $rule) {
                $result[$rule->getEntityId()] = [
                    'title' => $rule->getTitle(),
                    'resource' => 'Amasty_Reports::reports_catalog_by_product',
                    'url' => $this->urlBuilder->getUrl('amasty_reports/report_catalog/byProduct')
                        . '?amreports[rule]=' . $rule->getEntityId()
                ];
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->debug($e->getMessage());
        }
        $result[self::NEW_RULE_VALUE] = [
            'title' => '+ ' . __('New Rule'),
            'resource' => 'Amasty_Reports::reports_catalog_by_product',
            'class' => 'amreports-newrule',
            'url' => $this->urlBuilder->getUrl('amasty_reports/rule')
        ];

        return $result;
    }
}
