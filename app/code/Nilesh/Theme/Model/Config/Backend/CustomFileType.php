<?php
 
namespace Nilesh\Theme\Model\Config\Backend;
 
class CustomFileType extends \Magento\Config\Model\Config\Backend\Image
{
    /**
     * The tail part of directory path for uploading
     *
     */
    // const UPLOAD_DIR = 'homepage'; // Folder save image

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throw \Magento\Framework\Exception\LocalizedException
     */
    // protected function _getUploadDir()
    // {
    //     return $this->_mediaDirectory->getAbsolutePath($this->_appendScopeInfo(self::UPLOAD_DIR));
    // }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    /**
     * Getter for allowed extensions of uploaded files.
     *
     * @return string[]
     */
    protected function _getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'gif', 'png'];
    }
}

// extends \Magento\Config\Model\Config\Backend\File
// {
//     /**
//      * @return string[]
//      */
//     public function getAllowedExtensions() {
//         return ['png', 'jpg','jpeg','gif','svg'];
//     }
// }