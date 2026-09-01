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
 * @package     Mageplaza_PromoBanner
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\PromoBanner\Helper;

use Exception;
use Magento\Framework\Exception\FileSystemException;
use Mageplaza\Core\Helper\Media;

/**
 * Class Image
 * @package Mageplaza\PrmoBanner\Helper
 */
class Image extends Media
{
    const TEMPLATE_MEDIA_PATH        = 'mageplaza/promobanner';
    const TEMPLATE_MEDIA_TYPE_BANNER = 'banner/image';

    /**
     * @param $data
     * @param string $fileName
     * @param null $oldImage
     *
     * @return $this
     * @throws FileSystemException
     */
    public function uploadSliderImage(&$data, $fileName = 'image', $oldImage = null)
    {
        $type = self::TEMPLATE_MEDIA_TYPE_BANNER;
        if (isset($data[$fileName]) && isset($data[$fileName]['delete']) && $data[$fileName]['delete']) {
            if ($oldImage) {
                $this->removeImage($oldImage, $type);
            }
            $data['image'] = '';
        } else {
            try {
                $uploader = $this->uploaderFactory->create(['fileId' => $fileName]);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->setAllowCreateFolders(true);

                $path = $this->getBaseMediaPath($type);

                $image = $uploader->save(
                    $this->mediaDirectory->getAbsolutePath($path)
                );

                if ($oldImage) {
                    $this->removeImage($oldImage, $type);
                }

                $data['image'] = $this->_prepareFile($image['file']);
            } catch (Exception $e) {
                $data['image'] = isset($data['image']) ? $data['image'] : '';
            }
        }

        return $this;
    }

    /**
     * @param $data
     * @param string $fileName
     * @param null $oldImage
     *
     * @return $this|Media
     * @throws FileSystemException
     */
    public function uploadBannerImage(&$data, $fileName = 'image', $oldImage = null)
    {
        $type = self::TEMPLATE_MEDIA_TYPE_BANNER;
        if (isset($data[$fileName]) && isset($data[$fileName]['delete']) && $data[$fileName]['delete']) {
            if ($oldImage) {
                $this->removeImage($oldImage, $type);
            }
            $data[$fileName] = '';
        } else {
            try {
                $uploader = $this->uploaderFactory->create(['fileId' => $fileName]);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->setAllowCreateFolders(true);

                $path = $this->getBaseMediaPath($type);

                $image = $uploader->save(
                    $this->mediaDirectory->getAbsolutePath($path)
                );

                if ($oldImage) {
                    $this->removeImage($oldImage, $type);
                }

                $data[$fileName] = $this->_prepareFile($image['file']);
            } catch (Exception $e) {
                $data[$fileName] = isset($data[$fileName]['value']) ? $data[$fileName]['value'] : '';
            }
        }

        return $this;
    }

    /**
     * @param $value
     *
     * @return string
     */
    public function getImageSrc($value)
    {
        $src = $this->getMediaPath($value, self::TEMPLATE_MEDIA_TYPE_BANNER);
        if (!preg_match("/^http\:\/\/|https\:\/\//", $src)) {
            $src = $this->getMediaUrl($src);
        }

        return $src;
    }
}
