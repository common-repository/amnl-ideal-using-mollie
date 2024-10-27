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

namespace AMNL\WordPress;

use AMNL\WooCommerce\IDeal\PlugIn as WooCommercePlugin;

/**
 * 
 *
 * @author Arno Moonen <info@arnom.nl>
 */
class IDealPlugin
{

    protected static $rootFile;

    public static function getRootFile()
    {
        return self::$rootFile;
    }

    public static function initialize($root_file)
    {

        // Store root file reference
        self::$rootFile = $root_file;

        // Load plug-in text domain
        $i18nPath = dirname(plugin_basename(self::$rootFile)) . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR;
        load_plugin_textdomain('amnl_mollie_ideal', false, $i18nPath);

        // Register seperate plug-ins / add-ons
        WooCommercePlugin::initialize();
    }

}
