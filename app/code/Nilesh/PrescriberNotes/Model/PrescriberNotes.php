<?php
/**
 * Copyright © Nilesh Dubey All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Nilesh\PrescriberNotes\Model;

use Magento\Framework\Model\AbstractModel;
use Nilesh\PrescriberNotes\Api\Data\PrescriberNotesInterface;

class PrescriberNotes extends AbstractModel implements PrescriberNotesInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Nilesh\PrescriberNotes\Model\ResourceModel\PrescriberNotes::class);
    }

    /**
     * @inheritDoc
     */
    public function getPrescribernotesId()
    {
        return $this->getData(self::PRESCRIBERNOTES_ID);
    }

    /**
     * @inheritDoc
     */
    public function setPrescribernotesId($prescribernotesId)
    {
        return $this->setData(self::PRESCRIBERNOTES_ID, $prescribernotesId);
    }

    /**
     * @inheritDoc
     */
    public function getConnectId()
    {
        return $this->getData(self::CONNECTID);
    }

    /**
     * @inheritDoc
     */
    public function setConnectId($id)
    {
        return $this->setData(self::CONNECTID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        return $this->getData(self::SUBJECT);
    }

    /**
     * @inheritDoc
     */
    public function setSubject($subject)
    {
        return $this->setData(self::SUBJECT, $subject);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedBy()
    {
        return $this->getData(self::CREATED_BY);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedBy($getCreatedBy)
    {
        return $this->setData(self::CREATED_BY, $getCreatedBy);
    }

    /**
     * @inheritDoc
     */
    public function getNote()
    {
        return $this->getData(self::NOTE);
    }

    /**
     * @inheritDoc
     */
    public function setNote($note)
    {
        return $this->setData(self::NOTE, $note);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerName()
    {
        return $this->getData(self::CUSTOMER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerName($customerName)
    {
        return $this->setData(self::CUSTOMER_NAME, $customerName);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getMediaUrl($file)
    {        
        $_objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of\Magento\Framework\App\ObjectManager
        $storeManager = $_objectManager->get('Magento\Store\Model\StoreManagerInterface'); 
        $currentStore = $storeManager->getStore();
        return $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $file;
    }

    /**
     * Retrieve prescribernotes_upload image url
     * @return string
     */
    public function getPrescribernotesUpload()
    {                
        if (!$this->hasData('prescribernotes_upload')) {
            if ($file = $this->getData('prescribernotes_upload')) {
                $image = $this->getMediaUrl($file);
            } else {
                $image = $this->getMediaUrl($file);
            }
            $this->setData('prescribernotes_upload', $image);
        }

        return $this->getData('prescribernotes_upload');
    }

    /**
     * Retrieve prescribernotes_upload2 image url
     * @return string
     */
    public function getPrescribernotesUpload2()
    {        
        if (!$this->hasData('prescribernotes_upload2')) {
            if ($file = $this->getData('prescribernotes_upload2')) {
                $image = $this->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('prescribernotes_upload2', $image);
        }

        return $this->getData('prescribernotes_upload2');
    }

    /**
     * Retrieve prescribernotes_upload3 image url
     * @return string
     */
    public function getPrescribernotesUpload3()
    {
        if (!$this->hasData('prescribernotes_upload3')) {
            if ($file = $this->getData('prescribernotes_upload3')) {
                $image = $this->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('prescribernotes_upload3', $image);
        }

        return $this->getData('prescribernotes_upload3');
    }

    /**
     * Retrieve prescribernotes_upload4 image url
     * @return string
     */
    public function getPrescribernotesUpload4()
    {
        if (!$this->hasData('prescribernotes_upload4')) {
            if ($file = $this->getData('prescribernotes_upload4')) {
                $image = $this->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('prescribernotes_upload4', $image);
        }

        return $this->getData('prescribernotes_upload4');
    }

    /**
     * Retrieve prescribernotes_upload5 image url
     * @return string
     */
    public function getPrescribernotesUpload5()
    {
        if (!$this->hasData('prescribernotes_upload5')) {
            if ($file = $this->getData('prescribernotes_upload5')) {
                $image = $this->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('prescribernotes_upload5', $image);
        }

        return $this->getData('prescribernotes_upload5');
    }
}

