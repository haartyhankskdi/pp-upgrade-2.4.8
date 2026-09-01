<?php
/**
* @author Amasty Team
* @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
* @package Custom Form Base for Magento 2
*/

namespace Amasty\Customform\Helper;

use Amasty\Customform\Model\Utils\MagentoEdition;
use Magento\Eav\Helper\Data as EavData;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Json\EncoderInterface;

class Messages extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const CUSTOMER_FIRST_NAME = '{firstname}';
    public const CUSTOMER_LAST_NAME = '{lastname}';
    public const CUSTOMER_EMAIL = '{email}';
    public const CUSTOMER_COMPANY = '{company}';
    public const CUSTOMER_PHONE_NUMBER = '{telephone}';
    public const CUSTOMER_FAX = '{fax}';
    public const CUSTOMER_STREET_ADDRESS = '{street}';
    public const CUSTOMER_CITY = '{city}';
    public const CUSTOMER_REGION = '{region}';
    public const CUSTOMER_POST_CODE = '{postcode}';

    public const PRODUCT_URL = '{product_url}';
    public const PRODUCT_PRICE = '{product_price}';
    public const PRODUCT_FINAL_PRICE = '{product_final_price}';

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var EavData
     */
    private $eavData;

    /**
     * @var MagentoEdition
     */
    private $magentoEdition;

    public function __construct(
        Context $context,
        EavData $eavData,
        EncoderInterface $jsonEncoder,
        MagentoEdition $magentoEdition
    ) {
        parent::__construct($context);
        $this->jsonEncoder = $jsonEncoder;
        $this->eavData = $eavData;
        $this->magentoEdition = $magentoEdition;

        $listingLink = 'https://docs.magento.com/user-guide/stores/attributes-product.html#storefront-properties';
        $this->messages = [
            'addOption' => __('Add Option +'),
            'allFieldsRemoved' => __('All fields were removed.'),
            'allowSelect' => __('Allow Select'),
            'allowMultipleFiles' => __('Allow users to upload multiple files'),
            'autocomplete' => __('Autocomplete'),
            'button' => __('Button'),
            'cannotBeEmpty' => __('This field cannot be empty'),
            'checkboxGroup' => __('Checkbox Group'),
            'checkbox' => __('Checkbox'),
            'checkboxes' => __('Checkboxes'),
            'className' => __('Class'),
            'clearAllMessage' => __('Are you sure you want to clear all fields?'),
            'clearAll' => __('Clear'),
            'close' => __('Close'),
            'content' => __('Content'),
            'copy' => __('Copy To Clipboard'),
            'copyButton' => __('&#43;'),
            'copyButtonTooltip' => __('Copy'),
            'dateField' => __('Date Field'),
            'description' => __('Tooltip'),
            'descriptionField' => __('Description'),
            'devMode' => __('Developer Mode'),
            'editNames' => __('Edit Names'),
            'editorTitle' => __('Form Elements'),
            'editXML' => __('Edit XML'),
            'enableOther' => __('Enable &quot;Other&quot;'),
            'enableOtherMsg' => __('Let users to enter an unlisted option'),
            'fieldDeleteWarning' => false,
            'fieldVars' => __('Field Variables'),
            'fieldNonEditable' => __('This field cannot be edited.'),
            'fieldRemoveWarning' => __('Are you sure you want to remove this field?'),
            'fileUpload' => __('File Upload'),
            'formUpdated' => __('Form Updated'),
            'getStarted' => __('Drag a field from the right to this area'),
            'googlemap' => __('Google Map'),
            'header' => __('Header'),
            'hide' => __('Edit'),
            'hidden' => __('Hidden Input'),
            'label' => __('Field Title'),
            'labelEmpty' => __('Field Label cannot be empty'),
            'limitRole' => __('Limit access to one or more of the following roles:'),
            'mandatory' => __('Mandatory'),
            'maxlength' => __('Max Length'),
            'minOptionMessage' => __('This field requires a minimum of 2 options'),
            'multipleFiles' => __('Multiple Files'),
            'allowed_extension' => __('Allowed Extensions'),
            'max_file_size' => __('Max. File Size (MB)'),
            'name' => __('Code'),
            'no' => __('No'),
            'number' => __('Number'),
            'off' => __('Off'),
            'on' => __('On'),
            'option' => __('Option'),
            'star' => __('Star'),
            'comment' => __('Comment'),
            'optional' => __('optional'),
            'optionLabelPlaceholder' => __('Label'),
            'optionValuePlaceholder' => __('Value'),
            'optionEmpty' => __('Option value required'),
            'other' => __('Other'),
            'paragraph' => __('Paragraph'),
            'placeholder' => __('Placeholder'),
            'placeholders' => [
                'value' => __('Value'),
                'label' => __('Label'),
                'text' => '',
                'textarea' => '',
                'email' => __('Enter you email'),
                'placeholder' => '',
                'className' => __('space separated classes'),
                'password' => __('Enter your password')
            ],
            'preview' => __('Preview'),
            'radioGroup' => __('Radio Group'),
            'radio' => __('Radio'),
            'rating' => __('Rating'),
            'removeMessage' => __('Remove Element'),
            'removeOption' => __('Remove Option'),
            'remove' => __('&#215;'),
            'required' => __('Required'),
            'richText' => __('Rich Text Editor'),
            'roles' => __('Access'),
            'save' => __('Save'),
            'selectOptions' => __('Options'),
            'select' => __('Select'),
            'selectColor' => __('Select Color'),
            'selectionsMessage' => __('Allow Multiple Selections'),
            'size' => __('Size'),
            'sizes' => [
                'xs' => __('Extra Small'),
                'sm' => __('Small'),
                'm'  => __('Default'),
                'lg' => __('Large')
            ],
            'layout' => __('Layout'),
            'layouts' => [
                ['value' => 'one', 'label' => __('One Column')],
                ['value' => 'two', 'label' => __('Two Column')],
                ['value' => 'three', 'label' => __('Three Column')]
            ],
            'style' => __('Custom Style'),
            'styles' => [
                'btn' => [
                    'default' => __('Default'),
                    'danger'  => __('Danger'),
                    'info'    => __('Info'),
                    'primary' => __('Primary'),
                    'success' => __('Success'),
                    'warning' => __('Warning')
                ]
            ],
            'validation' => __('Validation'),
            'regexp' => __('RegExp'),
            'errorMessage' => __('Invalidation Message'),
            'subtype' => __('Type'),
            'text' => __('Text Field'),
            'textArea' => __('Text Area'),
            'toggle' => __('Toggle'),
            'warning' => __('Warning!'),
            'value' => __('Default Value'),
            'viewJSON' => __('{  }'),
            'viewXML' => __('&lt;/&gt;'),
            'yes' => __('Yes'),
            'dependencyTitle' => __('Dependency'),
            'notes' => [
                'value' => [
                    'customerNote' => [
                        'label' => __('Add Variable for Logged In Customer'),
                        'allowedEntityType' => 'address',
                        'values' => [
                            __('First Name - %1', self::CUSTOMER_FIRST_NAME),
                            __('Last Name - %1', self::CUSTOMER_LAST_NAME),
                            __('Email -  %1', self::CUSTOMER_EMAIL),
                            __('Company -  %1', self::CUSTOMER_COMPANY),
                            __('Phone Number -  %1', self::CUSTOMER_PHONE_NUMBER),
                            __('Street Address -  %1', self::CUSTOMER_STREET_ADDRESS),
                            __('City -  %1', self::CUSTOMER_CITY),
                            __('State/Province -  %1', self::CUSTOMER_REGION),
                            __('Zip/Postal Code -  %1', self::CUSTOMER_POST_CODE),
                        ],
                    ],
                    'productNote' => [
                        'label' => __('Add Product Page Form Variable'),
                        'allowedEntityType' => 'product',
                        'values' => [
                            __('You can use the following variables on the product page forms:'),
                            __('Product Paget Url -  %1', self::PRODUCT_URL),
                            __('Regular Price -  %1', self::PRODUCT_PRICE),
                            __('Actual Price -  %1', self::PRODUCT_FINAL_PRICE),
                            __('Attribute Value, e.g. {product_color} - {product_ATTRIBUTE%CODE}'),
                            __(
                                'Please make sure that the attribute is used in the %1product listing%2',
                                sprintf('<a href="%s" target="_blank">', $listingLink),
                                '</a>'
                            )
                        ],
                    ]
                ]
            ]
        ];

        $this->appendCustomerAttributesMessage();
    }

    private function appendCustomerAttributesMessage(): void
    {
        if ($this->magentoEdition->isEnterpriseVersion()) {
            $this->messages['notes']['value']['customerNote']['values'][] = __(
                'Any Text Field|Text Area|Date|Dropdown|Multiple Select|Yes/No Customer or Customer Address '
                . 'attribute code is supported here.'
            );
        }
    }

    /**
     * @return string
     */
    public function getMessages()
    {
        $validations = $this->eavData->getFrontendClasses(null);
        $validations[] = ['value' => 'pattern', 'label' => __('Regular Expression')];
        if (isset($validations[0]['value']) && !$validations[0]['value']) {
            $this->messages['validations'][0]['value'] = ' ';
        }

        $this->messages['validations'] = $validations;

        return $this->jsonEncoder->encode($this->messages);
    }
}
