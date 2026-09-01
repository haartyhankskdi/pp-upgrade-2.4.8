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
 * @package     Mageplaza_GoogleTagManager
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
declare(strict_types=1);

namespace Mageplaza\GoogleTagManager\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 * Class DefaultTemplate
 * @package Mageplaza\GoogleTagManager\Setup\Patch\Data
 */
class DefaultTemplate implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $buildInVar          = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "type": "PAGE_URL",
                "name": "Page URL"
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "type": "PAGE_HOSTNAME",
                "name": "Page Hostname"
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "type": "PAGE_PATH",
                "name": "Page Path"
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "type": "REFERRER",
                "name": "Referrer"
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "type": "EVENT",
                "name": "Event"
            }';
        $conversionTag       = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "9",
                "name": "Google Ads Conversion Tracking",
                "type": "awct",
                "parameter": [
                    {
                        "type": "TEMPLATE",
                        "key": "orderId",
                        "value": "\{\{conversion transaction_id\}\}"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "enableProductReporting",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "cssProvidedEnhancedConversionValue",
                        "value": "\{\{Enhanced Conversion\}\}"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "enableShippingData",
                        "value": "false"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "enableNewCustomerReporting",
                        "value": "false"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "enableConversionLinker",
                        "value": "true"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "conversionValue",
                        "value": "\{\{conversion value\}\}"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "enableEnhancedConversion",
                        "value": "true"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "conversionCookiePrefix",
                        "value": "_gcl"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "conversionId",
                        "value": "{{config.ads_conversion.conversionId}}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "currencyCode",
                        "value": "\{\{conversion currency\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "conversionLabel",
                        "value": "{{config.ads_conversion.conversionLabel}}"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "rdp",
                        "value": "false"
                    }
                ],
                "fingerprint": "1655091043240",
                "firingTriggerId": [
                    "11"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            }';
        $conversionLinkerTag = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "10",
                "name": "Conversion Linker",
                "type": "gclidw",
                "parameter": [
                    {
                        "type": "BOOLEAN",
                        "key": "enableCrossDomain",
                        "value": "false"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "enableUrlPassthrough",
                        "value": "false"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "enableCookieOverrides",
                        "value": "false"
                    }
                ],
                "fingerprint": "1656036794468",
                "firingTriggerId": [
                    "2147479553"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            }';
        $ga4Tag              = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "268",
                "name": "GA4 Configuration",
                "type": "gaawc",
                "parameter": [
                    {
                        "type": "BOOLEAN",
                        "key": "sendPageView",
                        "value": "true"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "enableSendToServerContainer",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "measurementId",
                        "value": "{{config.ga4.measurementId}}"
                    }
                ],
                "fingerprint": "1656036759163",
                "firingTriggerId": [
                    "13"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "279",
                "name": "GA4 Category",
                "type": "gaawe",
                "parameter": [
                    {
                        "type": "TEMPLATE",
                        "key": "eventName",
                        "value": "view_item_list"
                    },
                    {
                        "type": "LIST",
                        "key": "eventParameters",
                        "list": [
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "items"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Items\}\}"
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "type": "TAG_REFERENCE",
                        "key": "measurementId",
                        "value": "GA4 Configuration"
                    }
                ],
                "fingerprint": "1656037322837",
                "firingTriggerId": [
                    "278"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "281",
                "name": "GA4 Product",
                "type": "gaawe",
                "parameter": [
                    {
                        "type": "TEMPLATE",
                        "key": "eventName",
                        "value": "view_item"
                    },
                    {
                        "type": "LIST",
                        "key": "eventParameters",
                        "list": [
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "items"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Items\}\}"
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "type": "TAG_REFERENCE",
                        "key": "measurementId",
                        "value": "GA4 Configuration"
                    }
                ],
                "fingerprint": "1656037395106",
                "firingTriggerId": [
                    "280"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "283",
                "name": "GA4 Addtocart",
                "type": "gaawe",
                "parameter": [
                    {
                        "type": "TEMPLATE",
                        "key": "eventName",
                        "value": "add_to_cart"
                    },
                    {
                        "type": "LIST",
                        "key": "eventParameters",
                        "list": [
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "items"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Items\}\}"
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "type": "TAG_REFERENCE",
                        "key": "measurementId",
                        "value": "GA4 Configuration"
                    }
                ],
                "fingerprint": "1656037497200",
                "firingTriggerId": [
                    "282"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "285",
                "name": "Ga4 Removefromcart",
                "type": "gaawe",
                "parameter": [
                    {
                        "type": "TEMPLATE",
                        "key": "eventName",
                        "value": "remove_from_cart"
                    },
                    {
                        "type": "LIST",
                        "key": "eventParameters",
                        "list": [
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "items"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Items\}\}"
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "type": "TAG_REFERENCE",
                        "key": "measurementId",
                        "value": "GA4 Configuration"
                    }
                ],
                "fingerprint": "1656037602541",
                "firingTriggerId": [
                    "284"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "287",
                "name": "GA4 Checkout",
                "type": "gaawe",
                "parameter": [
                    {
                        "type": "TEMPLATE",
                        "key": "eventName",
                        "value": "begin_checkout"
                    },
                    {
                        "type": "LIST",
                        "key": "eventParameters",
                        "list": [
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "items"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Items\}\}"
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "type": "TAG_REFERENCE",
                        "key": "measurementId",
                        "value": "GA4 Configuration"
                    }
                ],
                "fingerprint": "1656037690465",
                "firingTriggerId": [
                    "286"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "288",
                "name": "GA4 Purchase",
                "type": "gaawe",
                "parameter": [
                    {
                        "type": "TEMPLATE",
                        "key": "eventName",
                        "value": "purchase"
                    },
                    {
                        "type": "LIST",
                        "key": "eventParameters",
                        "list": [
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "items"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Items\}\}"
                                    }
                                ]
                            },
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "transaction_id"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-TransactionID\}\}"
                                    }
                                ]
                            },
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "affiliation"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Affiliation\}\}"
                                    }
                                ]
                            },
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "value"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Value\}\}"
                                    }
                                ]
                            },
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "tax"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Tax\}\}"
                                    }
                                ]
                            },
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "shipping"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Shipping\}\}"
                                    }
                                ]
                            },
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "currency"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Currency\}\}"
                                    }
                                ]
                            },
                            {
                                "type": "MAP",
                                "map": [
                                    {
                                        "type": "TEMPLATE",
                                        "key": "name",
                                        "value": "coupon"
                                    },
                                    {
                                        "type": "TEMPLATE",
                                        "key": "value",
                                        "value": "\{\{Ga4-Ecommerce-Coupon\}\}"
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "type": "TAG_REFERENCE",
                        "key": "measurementId",
                        "value": "GA4 Configuration"
                    }
                ],
                "fingerprint": "1656037853306",
                "firingTriggerId": [
                    "11"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            }';
        $remarketingTag      = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "tagId": "292",
                "name": "Google Ads Remarketing Event",
                "type": "sp",
                "parameter": [
                    {
                        "type": "TEMPLATE",
                        "key": "eventItems",
                        "value": "\{\{Remarketing Items\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "eventValue",
                        "value": "\{\{Remarketing Value\}\}"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "enableDynamicRemarketing",
                        "value": "true"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "eventName",
                        "value": "\{\{Remarketing Event\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "conversionId",
                        "value": "{{config.ads_remarketing.conversionId}}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "customParamsFormat",
                        "value": "NONE"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "rdp",
                        "value": "false"
                    }
                    {% if config.ads_remarketing.conversionLabel %}
                    ,
                    {
                        "type": "TEMPLATE",
                        "key": "conversionLabel",
                        "value": "{{config.ads_remarketing.conversionLabel}}"
                    }
                    {% endif %}
                ],
                "fingerprint": "1656038176101",
                "firingTriggerId": [
                    "13"
                ],
                "tagFiringOption": "ONCE_PER_EVENT",
                "monitoringMetadata": {
                    "type": "MAP"
                },
                "consentSettings": {
                    "consentStatus": "NOT_SET"
                }
            }';
        $purchaseTrigger     = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "triggerId": "11",
                "name": "Purchase trigger",
                "type": "WINDOW_LOADED",
                "filter": [
                    {
                        "type": "CONTAINS",
                        "parameter": [
                            {
                                "type": "TEMPLATE",
                                "key": "arg0",
                                "value": "\{\{Page URL\}\}"
                            },
                            {
                                "type": "TEMPLATE",
                                "key": "arg1",
                                "value": "checkout/onepage/success"
                            }
                        ]
                    }
                ],
                "fingerprint": "1656037708819"
            }';
        $windowLoadedTrigger = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "triggerId": "13",
                "name": "Window Loaded",
                "type": "WINDOW_LOADED",
                "fingerprint": "1633418601113"
            }';
        $ga4Trigger          = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "triggerId": "278",
                "name": "Category trigger",
                "type": "WINDOW_LOADED",
                "filter": [
                    {
                        "type": "EQUALS",
                        "parameter": [
                            {
                                "type": "TEMPLATE",
                                "key": "arg0",
                                "value": "\{\{Ga4-Event\}\}"
                            },
                            {
                                "type": "TEMPLATE",
                                "key": "arg1",
                                "value": "view_item_list"
                            }
                        ]
                    }
                ],
                "fingerprint": "1656037291597"
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "triggerId": "280",
                "name": "Product trigger",
                "type": "WINDOW_LOADED",
                "filter": [
                    {
                        "type": "EQUALS",
                        "parameter": [
                            {
                                "type": "TEMPLATE",
                                "key": "arg0",
                                "value": "\{\{Ga4-Event\}\}"
                            },
                            {
                                "type": "TEMPLATE",
                                "key": "arg1",
                                "value": "view_item"
                            }
                        ]
                    }
                ],
                "fingerprint": "1656037390224"
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "triggerId": "282",
                "name": "Add To Cart Trigger",
                "type": "WINDOW_LOADED",
                "filter": [
                    {
                        "type": "EQUALS",
                        "parameter": [
                            {
                                "type": "TEMPLATE",
                                "key": "arg0",
                                "value": "\{\{Ga4-Event\}\}"
                            },
                            {
                                "type": "TEMPLATE",
                                "key": "arg1",
                                "value": "add_to_cart"
                            }
                        ]
                    }
                ],
                "fingerprint": "1656037488669"
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "triggerId": "284",
                "name": "Remove From Cart Trigger",
                "type": "WINDOW_LOADED",
                "filter": [
                    {
                        "type": "EQUALS",
                        "parameter": [
                            {
                                "type": "TEMPLATE",
                                "key": "arg0",
                                "value": "\{\{Ga4-Event\}\}"
                            },
                            {
                                "type": "TEMPLATE",
                                "key": "arg1",
                                "value": "remove_from_cart"
                            }
                        ]
                    }
                ],
                "fingerprint": "1656037573969"
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "triggerId": "286",
                "name": "Checkout Trigger",
                "type": "WINDOW_LOADED",
                "filter": [
                    {
                        "type": "EQUALS",
                        "parameter": [
                            {
                                "type": "TEMPLATE",
                                "key": "arg0",
                                "value": "\{\{Ga4-Event\}\}"
                            },
                            {
                                "type": "TEMPLATE",
                                "key": "arg1",
                                "value": "begin_checkout"
                            }
                        ]
                    }
                ],
                "fingerprint": "1656037676391"
            }';
        $conversionVar       = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "6",
                "name": "conversion value",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.value"
                    }
                ],
                "fingerprint": "1620716434659",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "7",
                "name": "conversion transaction_id",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.transaction_id"
                    }
                ],
                "fingerprint": "1620716487050",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "8",
                "name": "conversion currency",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.currency"
                    }
                ],
                "fingerprint": "1620716548962",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "258",
                "name": "Enhanced Conversion Email",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "enhanced.email"
                    }
                ],
                "fingerprint": "1655090055459",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "259",
                "name": "Enhanced Conversion Phone",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "enhanced.phone"
                    }
                ],
                "fingerprint": "1655090588318",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "260",
                "name": "Enhanced Conversion First Name",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "enhanced.first_name"
                    }
                ],
                "fingerprint": "1655090745488",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "261",
                "name": "Enhanced Conversion Last Name",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "enhanced.last_name"
                    }
                ],
                "fingerprint": "1655090898083",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "262",
                "name": "Enhanced Conversion Street",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "enhanced.street"
                    }
                ],
                "fingerprint": "1655090923256",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "263",
                "name": "Enhanced Conversion City",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "enhanced.city"
                    }
                ],
                "fingerprint": "1655090961599",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "264",
                "name": "Enhanced Conversion Region",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "enhanced.region"
                    }
                ],
                "fingerprint": "1655090984403",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "265",
                "name": "Enhanced Conversion Country",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "enhanced.country"
                    }
                ],
                "fingerprint": "1655091007475",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "266",
                "name": "Enhanced Conversion PostalCode",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "enhanced.postal_code"
                    }
                ],
                "fingerprint": "1655091034646",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "267",
                "name": "Enhanced Conversion",
                "type": "awec",
                "parameter": [
                    {
                        "type": "TEMPLATE",
                        "key": "mode",
                        "value": "MANUAL"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "country",
                        "value": "\{\{Enhanced Conversion Country\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "city",
                        "value": "\{\{Enhanced Conversion City\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "street",
                        "value": "\{\{Enhanced Conversion Street\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "last_name",
                        "value": "\{\{Enhanced Conversion Last Name\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "phone_number",
                        "value": "\{\{Enhanced Conversion Phone\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "region",
                        "value": "\{\{Enhanced Conversion Region\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "postal_code",
                        "value": "\{\{Enhanced Conversion PostalCode\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "first_name",
                        "value": "\{\{Enhanced Conversion First Name\}\}"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "email",
                        "value": "\{\{Enhanced Conversion Email\}\}"
                    }
                ],
                "fingerprint": "1655091038106",
                "formatValue": {}
            }';
        $ga4Var              = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "269",
                "name": "Ga4-Event",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ga4_event"
                    }
                ],
                "fingerprint": "1656036857087",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "270",
                "name": "Ga4-Ecommerce-Items",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.items"
                    }
                ],
                "fingerprint": "1656036937739",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "271",
                "name": "Ga4-Ecommerce-Affiliation",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.affiliation"
                    }
                ],
                "fingerprint": "1656036989203",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "272",
                "name": "Ga4-Ecommerce-Coupon",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.coupon"
                    }
                ],
                "fingerprint": "1656037016301",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "273",
                "name": "Ga4-Ecommerce-Currency",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.currency"
                    }
                ],
                "fingerprint": "1656037042662",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "274",
                "name": "Ga4-Ecommerce-Shipping",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.shipping"
                    }
                ],
                "fingerprint": "1656037086909",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "275",
                "name": "Ga4-Ecommerce-Tax",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.tax"
                    }
                ],
                "fingerprint": "1656037116560",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "276",
                "name": "Ga4-Ecommerce-TransactionID",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.transaction_id"
                    }
                ],
                "fingerprint": "1656037143680",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "277",
                "name": "Ga4-Ecommerce-Value",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "ecommerce.value"
                    }
                ],
                "fingerprint": "1656037166450",
                "formatValue": {}
            }';
        $remarketingVar      = '{
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "289",
                "name": "Remarketing Event",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "remarketing_event"
                    }
                ],
                "fingerprint": "1656038064504",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "290",
                "name": "Remarketing Value",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "value"
                    }
                ],
                "fingerprint": "1656038116461",
                "formatValue": {}
            },
            {
                "accountId": "6002117895",
                "containerId": "32567701",
                "variableId": "291",
                "name": "Remarketing Items",
                "type": "v",
                "parameter": [
                    {
                        "type": "INTEGER",
                        "key": "dataLayerVersion",
                        "value": "2"
                    },
                    {
                        "type": "BOOLEAN",
                        "key": "setDefaultValue",
                        "value": "false"
                    },
                    {
                        "type": "TEMPLATE",
                        "key": "name",
                        "value": "items"
                    }
                ],
                "fingerprint": "1656038139969",
                "formatValue": {}
            }';

        $data = [
            ['name' => 'conversion_tag', 'template' => $conversionTag],
            ['name' => 'conversion_linker', 'template' => $conversionLinkerTag],
            ['name' => 'ga4_tag', 'template' => $ga4Tag],
            ['name' => 'remarketing_tag', 'template' => $remarketingTag],
            ['name' => 'purchase_trigger', 'template' => $purchaseTrigger],
            ['name' => 'window_loaded', 'template' => $windowLoadedTrigger],
            ['name' => 'ga4_trigger', 'template' => $ga4Trigger],
            ['name' => 'conversion_var', 'template' => $conversionVar],
            ['name' => 'ga4_var', 'template' => $ga4Var],
            ['name' => 'remarketing_var', 'template' => $remarketingVar],
            ['name' => 'build_in_var', 'template' => $buildInVar]
        ];

        $this->moduleDataSetup->getConnection()->insertMultiple(
            $this->moduleDataSetup->getTable('mageplaza_gtm_export_template'),
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        return '2.0.1';
    }

}
