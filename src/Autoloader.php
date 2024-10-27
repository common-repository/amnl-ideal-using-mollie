<?php

/**
 * This file is part of AMNL iDeal using Mollie.
 *
 * (c) Arno Moonen <info@arnom.nl>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @author Arno Moonen <info@arnom.nl>
 * @copyright Copyright (c) 2012, Arno Moonen <info@arnom.nl>
 * @package AMNL_iDeal_using_Mollie
 */

namespace AMNL;

/**
 * 
 *
 * @author Arno Moonen <info@arnom.nl>
 */
class Autoloader
{

    static public function load($name)
    {
        if (strpos($name, __NAMESPACE__) == 0) {
            $subNS = substr($name, strlen(__NAMESPACE__) + 1);
            $subNS = str_replace('\\', DIRECTORY_SEPARATOR, $subNS);
            $subNS = str_replace('_', DIRECTORY_SEPARATOR, $subNS);

            $fileName = dirname(__FILE__) . DIRECTORY_SEPARATOR . $subNS . '.php';
            if (is_file($fileName)) {
                require_once($fileName);
            }
        } elseif ($name === 'iDEAL_Payment') {
            self::loadMollieClass();
        }
    }

    static public function loadMollieClass()
    {
        if (!class_exists('iDEAL_Payment')) {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'external' . DIRECTORY_SEPARATOR . 'ideal.class.php');
        }
    }

    static public function register()
    {
        spl_autoload_register(__NAMESPACE__ . '\Autoloader::load');
    }

}
