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


// @codingStandardsIgnoreFile
// namespace Mirasvit_Ddeboer\Imap\Message;

/**
 * An e-mail attachment.
 */
class Mirasvit_Ddeboer_Imap_Message_Attachment extends Mirasvit_Ddeboer_Imap_Message_Part
{
    /**
     * @var
     */
    protected $filename;

    /**
     * @var
     */
    protected $data;

    /**
     * @var
     */
    protected $contentType;

    /**
     * @var
     */
    protected $size;

    /**
     * Mirasvit_Ddeboer_Imap_Message_Attachment constructor.
     * @param mixed $stream
     * @param int $messageNumber
     * @param null $partNumber
     * @param null $structure
     */
    public function __construct($stream, $messageNumber, $partNumber = null, $structure = null)
    {
        parent::__construct($stream, $messageNumber, $partNumber, $structure);
    }

    /**
     * Get attachment filename.
     *
     * @return string
     */
    public function getFilename()
    {
        $name = $this->parameters->get('filename');
        if (!$name) {
            $name = $this->parameters->get('name');
        }
        $name = iconv_mime_decode($name, 0, 'UTF-8');

        return $name;
    }

    /**
     * Get attachment file size.
     *
     * @return int Number of bytes
     */
    public function getSize()
    {
        return strlen($this->getDecodedContent());
        //return $this->bytes;
    }
}
