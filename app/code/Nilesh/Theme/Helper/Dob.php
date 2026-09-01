<?php

namespace Nilesh\Theme\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Dob extends AbstractHelper
{
        /**
     * Get day
     *
     * @return string|bool
     */
    public function getDay($date)
    {
        if(!empty($date)){
            $date = str_replace("/","-",$date);
        }
        $date = strtotime($date);
        return $date ? date('d', $date) : '';
    }

    /**
     * Get month
     *
     * @return string|bool
     */
    public function getMonth($date)
    {   
        if(!empty($date)){
            $date = str_replace("/","-",$date);
        }
        $date = strtotime($date);
        return $date ? date('m', $date) : '';
    }

    /**
     * Get year
     *
     * @return string|bool
     */
    public function getYear($date)
    {
        if(!empty($date)){
            $date = str_replace("/","-",$date);
        }
        $date = strtotime($date);
        return $date ? date('Y', $date) : '';
    }
    
    /**
     * Get year
     *
     * @return string|bool
     */
    public function getFormatedDob($date)
    {
        if(!empty($date)){
            $date = str_replace("/","-",$date);
        }
        $date = strtotime($date);
        return $date ? date('d/m/Y', $date) : '';
    }
}