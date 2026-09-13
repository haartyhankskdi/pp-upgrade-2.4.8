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

class Storeview extends \Magento\Framework\App\Helper\AbstractHelper
{
    private const LOG_PAYLOAD_LIMIT = 4096;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $phpSerializer;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Framework\Serialize\Serializer\Serialize $phpSerializer,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->storeManager  = $storeManager;
        $this->serializer    = $serializer;
        $this->phpSerializer = $phpSerializer;

        parent::__construct($context);
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @param string                        $field
     * @param string                        $value
     *
     * @return void
     */
    public function setStoreViewValue($object, $field, $value)
    {
        $storeId = (int) $object->getStoreId();
        $serializedValue = $object->getData($field);
        $arr = $this->unserialize($serializedValue);

        // If $value is itself an already-serialized storeview array, extract the
        // plain scalar for the current store rather than double-encoding it.
        if (is_string($value) && $value !== '') {
            $decoded = $this->unserialize($value);
            if ($decoded !== [0 => $value]) {
                $value = $decoded[$storeId] ?? $decoded[0] ?? $value;
            }
        }
        if ($storeId === 0) {
            $arr[0] = $value;
        } else {
            $arr[$storeId] = $value;
            if (!isset($arr[0])) {
                $arr[0] = $value;
            }
        }
        $object->setData($field, $this->serializer->serialize($arr));
    }

    /**
     * @param \Magento\Framework\DataObject $object
     * @param string $field
     *
     * @return string
     */
    public function getStoreViewValue($object, $field)
    {
        $storeId = $object->getStoreId();
        if (is_array($storeId)) {
            $storeId = reset($storeId);
        }
        if (!$storeId) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        $serializedValue = $object->getData($field);
        $arr = $this->unserialize($serializedValue);
        $defaultValue = null;
        if (isset($arr[0])) {
            $defaultValue = $arr[0];
        }

        if (isset($arr[$storeId])) {
            $localizedValue = $arr[$storeId];
        } else {
            $localizedValue = $defaultValue;
        }

        return $localizedValue;
    }

    /**
     * @param string $string
     *
     * @return array
     */
    public function unserialize($string)
    {
        if (!is_string($string) || $string === '') {
            return [];
        }

        try {
            $result = $this->serializer->unserialize($string);
        } catch (\Exception $e) {
            // Fall back to the Magento PHP-serialize wrapper for legacy values (e.g. a:1:{i:0;s:5:"EWDWG";})
            if (strpos($string, 'a:') === 0) {
                try {
                    $result = $this->phpSerializer->unserialize($string);
                    if (is_array($result)) {
                        return $result;
                    }
                } catch (\Exception $e2) {
                    // fall through to warning
                }
            }

            $this->_logger->debug(
                'Mirasvit\\Helpdesk Storeview: legacy non-JSON storeview value cannot be decoded. '
                . substr($string, 0, self::LOG_PAYLOAD_LIMIT),
                ['exception' => $e]
            );

            return [0 => $string];
        }

        return is_array($result) ? $result : [0 => $string];
    }
}
