<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 18/01/2019
 * Time: 9:41
 */

define('SAGEPAY_SDK_PATH', dirname(__FILE__));

include_once SAGEPAY_SDK_PATH . '/constants.php';

/**
 * Autoload function for Sagepay Classes
 *
 * @param string $class
 */
function sagepayAutoloader($class)
{
    if (substr($class, 0, 7) !== 'Sagepay')
    {
        return;
    }
    $class = explode("\\",$class);
    $className = @$class[2];

    $filename = SAGEPAY_SDK_PATH . '/classes/' . $className . '.php';
    if (file_exists($filename))
    {
        include $filename;
    }
}

spl_autoload_register('sagepayAutoloader');
