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
 * @version   1.6.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\Helpdesk\Helper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Field extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory
     */
    protected $fieldCollectionFactory;

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $context;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param \Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory $fieldCollectionFactory
     * @param \Magento\Framework\App\Helper\Context                          $context
     * @param \Magento\Store\Model\StoreManagerInterface                     $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface           $localeDate
     * @param \Magento\Framework\View\Asset\Repository                       $assetRepo
     * @param \Magento\Framework\ObjectManagerInterface                      $objectManager
     */
    /**
     * The shared contact-form collection per store, for this request.
     * See getSharedContactFormCollection().
     *
     * @var array<int|string, \Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection> (a numeric-string key lands as an int)
     */
    private $contactFormFields = [];

    public function __construct(
        \Mirasvit\Helpdesk\Model\ResourceModel\Field\CollectionFactory $fieldCollectionFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->fieldCollectionFactory = $fieldCollectionFactory;
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->localeDate = $localeDate;
        $this->assetRepo = $assetRepo;
        $this->objectManager = $objectManager;
        parent::__construct($context);
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Field[]|\Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    public function getEditableCustomerCollection()
    {
        return $this->fieldCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('is_editable_customer', ['eq' => 1])
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->setOrder('sort_order', \Mirasvit\Helpdesk\Model\Config::DEFAULT_SORT_ORDER);
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Field[]|\Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    public function getVisibleCustomerCollection()
    {
        return $this->fieldCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('is_visible_customer', ['eq' => 1])
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->setOrder('sort_order', \Mirasvit\Helpdesk\Model\Config::DEFAULT_SORT_ORDER);
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Field[]|\Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    /**
     * The contact-form fields, as ONE collection shared for the request.
     *
     * /contact renders the form twice - the page form and the popup form - and each block asked for its
     * own collection, so the same select ran twice with identical bound values (measured with the page
     * profiler on a 2.4.7 store).
     *
     * STILL A COLLECTION, NOT AN ARRAY OF ITEMS, and that is the point: a collection is lazy. Returning
     * loaded items would make every caller pay the select even on a page whose template asks for the
     * fields and renders none - the customer ticket page is one that asks without always iterating.
     *
     * DO NOT ADD FILTERS TO WHAT THIS RETURNS - callers share it, so narrowing it narrows the form for
     * everybody. Take getContactFormCollection() below if you need a collection of your own.
     *
     * @return \Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    public function getSharedContactFormCollection()
    {
        $storeId = (string)$this->storeManager->getStore()->getId();

        if (!isset($this->contactFormFields[$storeId])) {
            $this->contactFormFields[$storeId] = $this->getContactFormCollection();
        }

        return $this->contactFormFields[$storeId];
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Field[]|\Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    public function getContactFormCollection()
    {
        return $this->fieldCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('is_visible_contact_form', ['eq' => 1])
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->setOrder('sort_order', \Mirasvit\Helpdesk\Model\Config::DEFAULT_SORT_ORDER);
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Field[]|\Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    public function getStaffCollection()
    {
        return $this->fieldCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->setOrder('sort_order', \Mirasvit\Helpdesk\Model\Config::DEFAULT_SORT_ORDER);
    }

    /**
     * @return \Mirasvit\Helpdesk\Model\Field[]|\Mirasvit\Helpdesk\Model\ResourceModel\Field\Collection
     */
    public function getActiveCollection()
    {
        return $this->fieldCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->setOrder('sort_order', \Mirasvit\Helpdesk\Model\Config::DEFAULT_SORT_ORDER);
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Field        $field
     * @param bool                                  $staff
     * @param false|\Mirasvit\Helpdesk\Model\Ticket $ticket
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getInputParams($field, $staff = true, $ticket = false)
    {
        $params = [
            'label'        => __($field->getName()),
            'name'         => $field->getCode(),
            'required'     => $staff ? $field->getIsRequiredStaff() : $field->getIsRequiredCustomer(),
            'value'        => $this->getFieldValue($ticket, $field),
            'checked'      => $ticket ? $ticket->getData($field->getCode()) : false,
            'values'       => $field->getValues(true),
            'note'         => $field->getDescription(),
            'date_format'  => 'yyyy-MM-dd',
            'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
        ];

        if ($staff && $field->getIsRequiredStaff()) {
            array_push($params, 'required');
            $params['class'] = 'required';
        }

        return $params;
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Field        $field
     * @param false|\Mirasvit\Helpdesk\Model\Ticket $ticket
     *
     * @return mixed
     */
    public function getFieldValue($ticket, $field)
    {
        if ($field->getType() == 'checkbox') {
            return 1;
        } elseif ($ticket && $field->getType() == 'date') {
            $raw = (string) $ticket->getData($field->getCode());
            if ($raw === '') {
                return '';
            }
            $timestamp = (new \IntlDateFormatter(
                $this->objectManager->create('Magento\Framework\Locale\ResolverInterface')
                    ->getLocale(),
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::NONE
            ))->parse($raw);
            if ($timestamp === false) {
                return '';
            }
            return date('Y-m-d', (int) $timestamp);
        }

        return $ticket ? $ticket->getData($field->getCode()) : '';
    }

    /**
     * @param \Mirasvit\Helpdesk\Model\Field $field
     *
     * @return string
     */
    public function getInputHtml($field)
    {
        $params = $this->getInputParams($field, false);
        $type = $field->getType();
        if ($type == 'date') {
            $type = 'text';
        }
        unset($params['label']);
        $className = '\Magento\Framework\Data\Form\Element\\' . ucfirst(strtolower($field->getType()));
        /** @var \Magento\Framework\Data\Form\Element\AbstractElement $element */
        $element = $this->objectManager->create($className);
        $element->setData($params);
        $element->setForm(new \Magento\Framework\DataObject());
        $element->setType($type);
        $element->setId($field->getCode());
        $element->setNoSpan(true);
        if ($field->getIsRequiredCustomer()) {
            $element->addClass('required-entry');
        }

        $html = $element->toHtml();
        return $html;
    }

    /**
     * @param array                           $post
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processPost($post, $ticket)
    {
        $collection = $this->getActiveCollection();
        foreach ($collection as $field) {
            if (isset($post[$field->getCode()])) {
                $value = $post[$field->getCode()];
                $ticket->setData($field->getCode(), $value);
            }
            if ($field->getType() == 'checkbox') {
                if (!isset($post[$field->getCode()])) {
                    $ticket->setData($field->getCode(), 0);
                }
            } elseif ($field->getType() == 'date' && isset($post[$field->getCode()])) {
                $value = (string) $ticket->getData($field->getCode());
                if ($value === '') {
                    $ticket->setData($field->getCode(), null);
                    continue;
                }
                try {
                    $value = $this->localeDate->formatDate(new \DateTime($value), \IntlDateFormatter::SHORT);
                } catch (\Exception $e) { //we have exception if input date is in incorrect format
                    $value = '';
                }
                $ticket->setData($field->getCode(), $value);
            }
        }
    }

      /**
     * @param \Mirasvit\Helpdesk\Model\Ticket $ticket
     * @param \Mirasvit\Helpdesk\Model\Field  $field
     *
     * @return bool|string
     */
    public function getValue($ticket, $field)
    {
        $value = $ticket->getData($field->getCode());

        if (!$value) {
            return false;
        }

        if ($field->getType() == 'checkbox') {
            // reachable only when $value is truthy (falsy returned false above), so always "Yes"
            $value = __('Yes');
        } elseif ($field->getType() == 'date') {
            $value = date('Y-m-d', (int) (new \IntlDateFormatter(
                $this->objectManager->create('Magento\Framework\Locale\ResolverInterface')
                    ->getLocale(),
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::NONE
            ))->parse((string) $ticket->getData($field->getCode())));
            try {
                $value = $this->localeDate->formatDate(new \DateTime($value), \IntlDateFormatter::MEDIUM);
            } catch (\Exception $e) { //we have exception if input date is in incorrect format
                $value = '';
            }
        } elseif ($field->getType() == 'select') {
            $values = $field->getValues();
            // if value was deleted but ticket still contains it, we return empty string
            $value = isset($values[$value]) ? $values[$value] : '';
        }

        return $value;
    }

    /**
     * @param string $code
     *
     * @return \Mirasvit\Helpdesk\Model\Field|null
     */
    public function getFieldByCode($code)
    {
        $field = $this->fieldCollectionFactory->create()
            ->addFieldToFilter('code', $code)
            ->getFirstItem();
        if ($field->getId()) {
            return $field;
        }

        return null;
    }
}
