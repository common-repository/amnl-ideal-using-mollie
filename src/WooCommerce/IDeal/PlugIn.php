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

namespace AMNL\WooCommerce\IDeal;

/**
 * 
 *
 * @author Arno Moonen <info@arnom.nl>
 */
class PlugIn
{

    public static function initialize()
    {
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'addGateway'));
    }

    public static function addGateway($gateways)
    {
        $gateways[] = 'AMNL\WooCommerce\IDeal\Gateway';
        return $gateways;
    }

}
