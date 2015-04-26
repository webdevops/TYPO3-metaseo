<?php
namespace Metaseo\Metaseo\Utility;

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
 * General utility
 *
 * @package     metaseo
 * @subpackage  Utility
 * @version     $Id: GeneralUtility.php 81677 2013-11-21 12:32:33Z mblaschke $
 */
class GeneralUtility {

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Page Select
     *
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected static $sysPageObj = NULL;

    /**
     * Rootline cache
     *
     * @var array
     */
    protected static $rootlineCache = array();


    // ########################################################################
    // Public methods
    // ########################################################################

    /**
     * Get current language id
     *
     * @return  integer
     */
    public static function getLanguageId() {
        $ret = 0;

        if (!empty($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'])) {
            $ret = (int)$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
        }

        return $ret;
    }

    /**
     * Get current root pid
     *
     * @param   integer|null $uid    Page UID
     * @return  integer
     */
    public static function getRootPid($uid = NULL) {
        static $cache = array();
        $ret = NULL;

        if ($uid === NULL) {
            #################
            # Current root PID
            #################
            $rootline = self::getRootLine();
            if (!empty($rootline[0])) {
                $ret = $rootline[0]['uid'];
            }
        } else {
            #################
            # Other root PID
            #################
            if (!isset($cache[$uid])) {
                $cache[$uid] = NULL;
                $rootline    = self::getRootLine($uid);

                if (!empty($rootline[0])) {
                    $cache[$uid] = $rootline[0]['uid'];
                }
            }

            $ret = $cache[$uid];
        }

        return $ret;
    }

    /**
     * Get current pid
     *
     * @return  integer
     */
    public static function getCurrentPid() {
        return $GLOBALS['TSFE']->id;
    }

    /**
     * Get current root line
     *
     * @param   integer|null $uid    Page UID
     * @return  array
     */
    public static function getRootLine($uid = NULL) {
        if ($uid === NULL) {
            #################
            # Current rootline
            #################
            if (empty(self::$rootlineCache['__CURRENT__'])) {
                // Current rootline
                $rootline = $GLOBALS['TSFE']->tmpl->rootLine;

                // Filter rootline by siteroot
                $rootline = self::filterRootlineBySiteroot((array)$rootline);

                self::$rootlineCache['__CURRENT__'] = $rootline;
            }

            $ret = self::$rootlineCache['__CURRENT__'];
        } else {
            #################
            # Other rootline
            #################
            if (empty(self::$rootlineCache[$uid])) {
                // Fetch full rootline to TYPO3 root (0)
                $rootline = self::_getSysPageObj()->getRootLine($uid);

                // Filter rootline by siteroot
                $rootline = self::filterRootlineBySiteroot((array)$rootline);

                self::$rootlineCache[$uid] = $rootline;
            }

            $ret = self::$rootlineCache[$uid];
        }

        return $ret;
    }

    /**
     * Filter rootline to get the real one up to siteroot page
     *
     * @param $rootline
     * @return array
     */
    protected static function filterRootlineBySiteroot(array $rootline) {
        $ret = array();

        // Make sure sorting is right (first root, last page)
        ksort($rootline, SORT_NUMERIC);

        //reverse rootline
        $rootline = array_reverse($rootline);

        foreach ($rootline as $page) {
            $ret[] = $page;
            if (!empty($page['is_siteroot'])) {
                break;
            }
        }
        $ret = array_reverse($ret);
        return $ret;
    }

    /**
     * Get domain
     *
     * @return  array
     */
    public static function getSysDomain() {
        static $ret = NULL;

        if ($ret !== NULL) {
            return $ret;
        }

        $host = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');
        $rootPid = self::getRootPid();

        $query = 'SELECT *
                    FROM sys_domain
                   WHERE pid = ' . (int)$rootPid . '
                     AND domainName = ' . DatabaseUtility::quote( $host, 'sys_domain' ) . '
                     AND hidden = 0';
        $ret = DatabaseUtility::getRow($query);

        return $ret;
    }

    /**
     * Get root setting row
     *
     * @param   integer $rootPid    Root Page Id
     * @return  array
     */
    public static function getRootSetting($rootPid = NULL) {
        static $ret = NULL;

        if ($ret !== NULL) {
            return $ret;
        }

        if ($rootPid === NULL) {
            $rootPid = self::getRootPid();
        }

        $query = 'SELECT *
                    FROM tx_metaseo_setting_root
                   WHERE pid = ' . (int)$rootPid.'
                     AND deleted = 0
                   LIMIT 1';
        $ret = DatabaseUtility::getRow($query);

        return $ret;
    }

    /**
     * Get root setting value
     *
     * @param   string       $name           Name of configuration
     * @param   mixed|NULL   $defaultValue   Default value
     * @param   integer|NULL $rootPid        Root Page Id
     * @return  array
     */
    public static function getRootSettingValue($name, $defaultValue = NULL, $rootPid = NULL) {
        $setting = self::getRootSetting($rootPid);

        if (isset($setting[$name])) {
            $ret = $setting[$name];
        } else {
            $ret = $defaultValue;
        }

        return $ret;
    }

    /**
     * Get extension configuration
     *
     * @param   string $name       Name of config
     * @param   boolean $default    Default value
     * @return  mixed
     */
    public static function getExtConf($name, $default = NULL) {
        static $conf = NULL;
        $ret = $default;

        if ($conf === NULL) {
            // Load ext conf
            $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['metaseo']);
            if (!is_array($conf)) {
                $conf = array();
            }
        }

        if (isset($conf[$name])) {
            $ret = $conf[$name];
        }


        return $ret;
    }

    /**
     * Call hook
     *
     * @param   string     $name   Name of hook
     * @param   boolean    $obj    Object
     * @param   mixed|NULL $args   Args
     * @return  mixed
     */
    public static function callHook($name, $obj, &$args = NULL) {
        static $hookConf = NULL;

        // Fetch hooks config for metaseo, minimize array lookups
        if ($hookConf === NULL) {
            $hookConf = array();
            if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks'])
                && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks'])
            ) {
                $hookConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks'];
            }
        }

        // Call hooks
        if (!empty($hookConf[$name]) && is_array($hookConf[$name])) {
            foreach ($hookConf[$name] as $_funcRef) {
                if ($_funcRef) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $args, $obj);
                }
            }
        }
    }

    /**
     * Full url
     *g
     * Make sure the url is absolute (http://....)
     *
     * @param   string $url    URL
     * @param   string $domain Domain
     * @return  string
     */
    public static function fullUrl($url, $domain = NULL) {
        if (!preg_match('/^https?:\/\//i', $url)) {

            // Fix for root page link
            if ($url === '/') {
                $url = '';
            }

            // remove first /
            if (strpos($url, '/') === 0) {
                $url = substr($url, 1);
            }

            if( $domain !== NULL ) {
                // specified domain
                $url = 'http://'.$domain.'/'.$url;
            } else {
                // domain from env
                $url = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$url;
            }
        }


        // Fix url stuff
        $url = str_replace('?&', '?', $url);

        // Removed by request of https://forge.typo3.org/issues/61845
        // replace double slashes but not before a : (eg. http://)
        //$url = preg_replace('_(?<!:)\//_', '/', $url);

        // Fallback
        //if( !empty($GLOBALS['TSFE']) && !preg_match('/^https?:\/\//i', $url ) ) {
        //	$url = $GLOBALS['TSFE']->baseUrlWrap($url);
        //}

        return $url;
    }

    // ########################################################################
    // Protected methods
    // ########################################################################

    /**
     * Get sys page object
     *
     * @return  \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected static function _getSysPageObj() {
        if (self::$sysPageObj === NULL) {
            self::$sysPageObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Frontend\\Page\\PageRepository'
            );
        }
        return self::$sysPageObj;
    }

}
