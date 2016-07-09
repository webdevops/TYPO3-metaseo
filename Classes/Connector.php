<?php

/*
 *  Copyright notice
 *
 *  (c) 2015 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
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
 */

namespace Metaseo\Metaseo;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Connector
 */
class Connector implements SingletonInterface
{

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Data store
     *
     * @var array
     */
    protected static $store = array(
        'flag'      => array(),
        'meta'      => array(),
        'meta:og'   => array(),
        'custom'    => array(),
        'custom:og' => array(),
        'pagetitle' => array(),
        'sitemap'   => array(),
    );

    // ########################################################################
    // Page title methods
    // ########################################################################

    /**
     * Set page title
     *
     * @param   string  $value      Page title
     * @param   boolean $updateTsfe Update TSFE values
     */
    public static function setPageTitle($value, $updateTsfe = true)
    {
        $value = (string)$value;

        if ($updateTsfe && !empty($GLOBAL['TSFE'])) {
            $GLOBAL['TSFE']->page['title']   = $value;
            $GLOBAL['TSFE']->indexedDocTitle = $value;
        }

        self::$store['pagetitle']['pagetitle.title'] = $value;
    }

    /**
     * Set page title suffix
     *
     * @param   string $value Page title suffix
     */
    public static function setPageTitleSuffix($value)
    {
        self::$store['pagetitle']['pagetitle.suffix'] = $value;
    }

    /**
     * Set page title prefix
     *
     * @param   string $value Page title Prefix
     */
    public static function setPageTitlePrefix($value)
    {
        self::$store['pagetitle']['pagetitle.prefix'] = $value;
    }

    /**
     * Set page title (absolute)
     *
     * @param   string  $value      Page title
     * @param   boolean $updateTsfe Update TSFE values
     */
    public static function setPageTitleAbsolute($value, $updateTsfe = true)
    {
        if ($updateTsfe && !empty($GLOBALS['TSFE'])) {
            $GLOBALS['TSFE']->page['title']   = $value;
            $GLOBALS['TSFE']->indexedDocTitle = $value;
        }

        self::$store['pagetitle']['pagetitle.absolute'] = $value;
    }

    /**
     * Set page title sitetitle
     *
     * @param   string $value Page title
     */
    public static function setPageTitleSitetitle($value)
    {
        self::$store['pagetitle']['pagetitle.sitetitle'] = $value;
    }

    // ########################################################################
    // MetaTag methods
    // ########################################################################

    /**
     * Set meta tag
     *
     * @param   string $key   Metatag name
     * @param   string $value Metatag value
     */
    public static function setMetaTag($key, $value)
    {
        $key   = (string)$key;
        $value = (string)$value;

        if (strpos($key, 'og:') === 0) {
            self::setOpenGraphTag($key, $value);
        }

        self::$store['meta'][$key] = $value;
    }

    /**
     * Set opengraph tag
     *
     * @param   string $key   Metatag name
     * @param   string $value Metatag value
     */
    public static function setOpenGraphTag($key, $value)
    {
        $key   = (string)$key;
        $value = (string)$value;

        self::$store['flag']['meta:og:external'] = true;
        self::$store['meta:og'][$key]            = $value;
    }

    /**
     * Set meta tag
     *
     * @param   string $key   Metatag name
     * @param   string $value Metatag value
     */
    public static function setCustomMetaTag($key, $value)
    {
        $key   = (string)$key;
        $value = (string)$value;

        self::$store['custom'][$key] = $value;
    }

    /**
     * Set custom opengraph tag
     *
     * @param   string $key   Metatag name
     * @param   string $value Metatag value
     */
    public static function setCustomOpenGraphTag($key, $value)
    {
        $key   = (string)$key;
        $value = (string)$value;

        self::$store['flag']['custom:og:external'] = true;
        self::$store['custom:og'][$key] = $value;
    }

    /**
     * Disable meta tag
     *
     * @param   string $key Metatag name
     */
    public static function disableMetaTag($key)
    {
        $key = (string)$key;

        self::$store['meta'][$key] = null;
    }

    // ########################################################################
    // Sitemap methods
    // ########################################################################

    /**
     * Set sitemap index expiration in days
     *
     * @param integer $days Entry expiration in days
     */
    public static function setSitemapIndexExpiration($days)
    {
        self::$store['sitemap']['expiration'] = abs($days);
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
     * @param   string $key Store key (optional, if empty whole store is returned)
     *
     * @return  array
     */
    public static function getStore($key = null)
    {
        $ret = null;

        if ($key !== null) {
            if (isset(self::$store[$key])) {
                $ret = self::$store[$key];
            }
        } else {
            $ret = self::$store;
        }

        return $ret;
    }
}
