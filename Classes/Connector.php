<?php
namespace MetaSeo\MetaSeo;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
 *  (c) 2013 Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de> (tq_seo)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Connector
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: Connector.php 84267 2014-03-14 13:39:05Z mblaschke $
 */
class Connector {

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Data store
     *
     * @var array
     */
    protected static $_store = array(
        'flag'      => array(),
        'meta'      => array(),
        'meta:og'   => array(),
        'custom'    => array(),
        'pagetitle' => array(),
    );

    // ########################################################################
    // Page title methods
    // ########################################################################

    /**
     * Set page title
     *
     * @param   string $value      Page title
     * @param   boolean $updateTsfe Update TSFE values
     */
    public static function setPageTitle($value, $updateTsfe = TRUE) {
        $value = (string)$value;

        if ($updateTsfe && !empty($GLOBAL['TSFE'])) {
            $GLOBAL['TSFE']->page['title']   = $value;
            $GLOBAL['TSFE']->indexedDocTitle = $value;
        }

        self::$_store['pagetitle']['pagetitle.title'] = $value;
    }

    /**
     * Set page title suffix
     *
     * @param   string $value  Page title suffix
     */
    public static function setPageTitleSuffix($value) {
        self::$_store['pagetitle']['pagetitle.suffix'] = $value;
    }

    /**
     * Set page title prefix
     *
     * @param   string $value  Page title Prefix
     */
    public static function setPageTitlePrefix($value) {
        self::$_store['pagetitle']['pagetitle.prefix'] = $value;
    }

    /**
     * Set page title (absolute)
     *
     * @param   string $value        Page title
     * @param   boolean $updateTsfe   Update TSFE values
     */
    public static function setPageTitleAbsolute($value, $updateTsfe = TRUE) {
        if ($updateTsfe && !empty($GLOBALS['TSFE'])) {
            $GLOBALS['TSFE']->page['title']   = $value;
            $GLOBALS['TSFE']->indexedDocTitle = $value;
        }

        self::$_store['pagetitle']['pagetitle.absolute'] = $value;
    }

    /**
     * Set page title sitetitle
     *
     * @param   string $value  Page title
     */
    public static function setPageTitleSitetitle($value) {
        self::$_store['pagetitle']['pagetitle.sitetitle'] = $value;
    }

    // ########################################################################
    // MetaTag methods
    // ########################################################################

    /**
     * Set meta tag
     *
     * @param   string $key    Metatag name
     * @param   string $value  Metatag value
     */
    public static function setMetaTag($key, $value) {
        $key   = (string)$key;
        $value = (string)$value;

        if( strpos($key, 'og:') === 0 ) {
            return self::setOpenGraphTag($key, $value);
        }

        self::$_store['meta'][$key] = $value;
    }

    /**
     * Set opengraph tag
     *
     * @param   string $key    Metatag name
     * @param   string $value  Metatag value
     */
    public static function setOpenGraphTag($key, $value) {
        $key   = (string)$key;
        $value = (string)$value;

        self::$_store['flag']['meta:og:external'] = true;
        self::$_store['meta:og'][$key] = $value;
    }

    /**
     * Set meta tag
     *
     * @param   string $key    Metatag name
     * @param   string $value  Metatag value
     */
    public static function setCustomMetaTag($key, $value) {
        $key   = (string)$key;
        $value = (string)$value;

        self::$_store['custom'][$key] = $value;
    }

    /**
     * Disable meta tag
     *
     * @param   string $key    Metatag name
     */
    public static function disableMetaTag($key) {
        $key = (string)$key;

        self::$_store['meta'][$key] = NULL;
    }

    // ########################################################################
    // Control methods
    // ########################################################################

    // TODO


    // ########################################################################
    // General methods
    // ########################################################################

    /**
     * Get store
     *
     * @param   string $key    Store key (optional, if empty whole store is returned)
     * @return  array
     */
    public static function getStore($key = NULL) {
        $ret = NULL;

        if ($key !== NULL) {
            if (isset(self::$_store[$key])) {
                $ret = self::$_store[$key];
            }
        } else {
            $ret = self::$_store;
        }

        return $ret;
    }

}
