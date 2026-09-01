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

class Mirasvit_Ddeboer_Imap_Message_Headers
{
    /**
     * @var array
     */
    protected $array = array();
    /**
     * @var string
     */
    public $raw_text;

    /**
     * Mirasvit_Ddeboer_Imap_Message_Headers constructor.
     * @param stdClass $headers
     */
    public function __construct(stdClass $headers)
    {
        // Store all headers as lowercase
        $this->array = array_change_key_case((array) $headers);

        // Decode subject, as it may be UTF-8 encoded
        if (isset($headers->subject)) {
            $subject = '';
            foreach (imap_mime_header_decode($headers->subject) as $part) {
                // $part->charset can also be 'default', i.e. plain US-ASCII
                if (function_exists('iconv')) {
                    $charset = $part->charset == 'default' ? 'UTF-8' : $part->charset;
                    $subject .= iconv($charset, 'UTF-8//TRANSLIT//IGNORE', $part->text);
                } else {
                    $charset = $part->charset == 'default' ? 'auto' : $part->charset;
                    $subject .= mb_convert_encoding($part->text, 'UTF-8', $charset);
                }
            }
            $this->array['subject'] = $subject;
        }

//        $this->array['msgno'] = (int) $this->array['msgno'];

//        foreach (array('answered', 'deleted', 'draft') as $flag) {
//            $this->array[$flag] = (bool) trim($this->array[$flag]);
//        }

        if (isset($this->array['date'])) {
            $this->array['date'] = preg_replace('/(.*)\(.*\)/', '$1', $this->array['date']);
            // $this->array['date'] = new DateTime($this->array['date']);
        }

        if (isset($this->array['from'])) {
            $from = current($this->array['from']);
            if (isset($from->host) && isset($from->mailbox)) {
                $this->array['from'] = new Mirasvit_Ddeboer_Imap_Message_EmailAddress(
                    $from->mailbox,
                    $from->host,
                    isset($from->personal) ? imap_utf8($from->personal) : null
                );
            }
        }

        if (isset($this->array['to'])) {
            $recipients = array();
            foreach ($this->array['to'] as $to) {
                if (!isset($to->host) || !isset($to->mailbox)) {
                    continue;
                }
                $recipients[] = new Mirasvit_Ddeboer_Imap_Message_EmailAddress(
                    str_replace('\'', '', $to->mailbox),
                    str_replace('\'', '', $to->host),
                    isset($to->personal) ? imap_utf8($to->personal) : null
                );
            }
            $this->array['to'] = $recipients;
        } else {
            $this->array['to'] = array();
        }

        if (isset($this->array['reply_to'])) {
            $from = current($this->array['reply_to']);
            if (isset($from->host) && isset($from->mailbox)) {
                $this->array['reply_to'] = new Mirasvit_Ddeboer_Imap_Message_EmailAddress(
                    $from->mailbox,
                    $from->host,
                    isset($from->personal) ? imap_utf8($from->personal) : null
                );
            }
        }
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->array);
    }

    /**
     * @return int|string|null
     */
    public function key()
    {
        return key($this->array);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->array);
    }

    /**
     * @return bool
     */
    public function rewind()
    {
        return rewind($this->array);
    }

    /**
     * @return mixed
     */
    public function valid()
    {
        return valid($this->array);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        $key = strtolower($key);

        if (isset($this->array[$key])) {
            return $this->array[$key];
        }

        return null;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->raw_text;
    }
}
