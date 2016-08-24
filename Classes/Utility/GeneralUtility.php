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

namespace Metaseo\Metaseo\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;

/**
 * General utility
 */
class GeneralUtility
{

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Page Select
     *
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected static $sysPageObj;

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
    public static function getLanguageId()
    {
        $ret = 0;

        if (!empty($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'])) {
            $ret = (int)$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
        }

        return $ret;
    }

    /**
     * Get current pid
     *
     * @return  integer
     */
    public static function getCurrentPid()
    {
        return $GLOBALS['TSFE']->id;
    }

    /**
     * Check if there is any mountpoint in rootline
     *
     * @param   integer|null $uid Page UID
     *
     * @return  boolean
     */
    public static function isMountpointInRootLine($uid = null)
    {
        $ret = false;

        // Performance check, there must be an MP-GET value
        if (Typo3GeneralUtility::_GET('MP')) {
            // Possible mount point detected, let's check the rootline
            foreach (self::getRootLine($uid) as $page) {
                if (!empty($page['_MOUNT_OL'])) {
                    // Mountpoint detected in rootline
                    $ret = true;
                }
            }
        }

        return $ret;
    }

    /**
     * Get current root line
     *
     * @param   integer|null $uid Page UID
     *
     * @return  array
     */
    public static function getRootLine($uid = null)
    {
        if ($uid === null) {
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
                $rootline = self::getSysPageObj()->getRootLine($uid);

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
     *
     * @return array
     */
    protected static function filterRootlineBySiteroot(array $rootline)
    {
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
     * Get sys page object
     *
     * @return  \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected static function getSysPageObj()
    {
        if (self::$sysPageObj === null) {
            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = Typo3GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
            );

            /** @var \TYPO3\CMS\Frontend\Page\PageRepository $sysPageObj */
            $sysPageObj = $objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');

            self::$sysPageObj = $sysPageObj;
        }

        return self::$sysPageObj;
    }

    /**
     * Get domain
     *
     * @return  array
     */
    public static function getSysDomain()
    {
        static $ret = null;

        if ($ret !== null) {
            return $ret;
        }

        $host    = Typo3GeneralUtility::getIndpEnv('HTTP_HOST');
        $rootPid = self::getRootPid();

        $query = 'SELECT *
                    FROM sys_domain
                   WHERE pid = ' . (int)$rootPid . '
                     AND domainName = ' . DatabaseUtility::quote($host, 'sys_domain') . '
                     AND hidden = 0';
        $ret   = DatabaseUtility::getRow($query);

        return $ret;
    }

    /**
     * Get current root pid
     *
     * @param   integer|null $uid Page UID
     *
     * @return  integer
     */
    public static function getRootPid($uid = null)
    {
        static $cache = array();
        $ret = null;

        if ($uid === null) {
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
                $cache[$uid] = null;
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
     * Get root setting value
     *
     * @param   string       $name         Name of configuration
     * @param   mixed|NULL   $defaultValue Default value
     * @param   integer|NULL $rootPid      Root Page Id
     *
     * @return  array
     */
    public static function getRootSettingValue($name, $defaultValue = null, $rootPid = null)
    {
        $setting = self::getRootSetting($rootPid);

        if (isset($setting[$name])) {
            $ret = $setting[$name];
        } else {
            $ret = $defaultValue;
        }

        return $ret;
    }

    /**
     * Get root setting row
     *
     * @param   integer $rootPid Root Page Id
     *
     * @return  array
     */
    public static function getRootSetting($rootPid = null)
    {
        static $ret = null;

        if ($ret !== null) {
            return $ret;
        }

        if ($rootPid === null) {
            $rootPid = self::getRootPid();
        }

        $query = 'SELECT *
                    FROM tx_metaseo_setting_root
                   WHERE pid = ' . (int)$rootPid . '
                     AND deleted = 0
                   LIMIT 1';
        $ret   = DatabaseUtility::getRow($query);

        return $ret;
    }

    /**
     * Get extension configuration
     *
     * @param   string  $name    Name of config
     * @param   boolean $default Default value
     *
     * @return  mixed
     */
    public static function getExtConf($name, $default = null)
    {
        static $conf = null;
        $ret = $default;

        if ($conf === null) {
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
     * Call hook and signal
     *
     * @param   string     $class Name of the class containing the signal
     * @param   string     $name  Name of hook
     * @param   mixed $obj Reference to be passed along (typically "$this"
     *                     - being a reference to the calling object) (REFERENCE!)
     * @param   mixed|NULL $args  Args
     *
     * @return  mixed
     */
    public static function callHookAndSignal($class, $name, $obj, &$args = null)
    {
        static $hookConf = null;
        static $signalSlotDispatcher = null;

        // Fetch hooks config for metaseo, minimize array lookups
        if ($hookConf === null) {
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
                    Typo3GeneralUtility::callUserFunction($_funcRef, $args, $obj);
                }
            }
        }

        // Call signal
        if ($signalSlotDispatcher === null) {
            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = Typo3GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
            );

            /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
            $signalSlotDispatcher = $objectManager->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
        }
        list($args) = $signalSlotDispatcher->dispatch($class, $name, array($args, $obj));
    }


    /**
     * Generate full url
     *
     * Makes sure the url is absolute (http://....)
     *
     * @param   string $url    URL
     * @param   string $domain Domain
     *
     * @return  string
     */
    public static function fullUrl($url, $domain = null)
    {
        if (!preg_match('/^https?:\/\//i', $url)) {
            // Fix for root page link
            if ($url === '/') {
                $url = '';
            }

            // remove first /
            if (strpos($url, '/') === 0) {
                $url = substr($url, 1);
            }

            if ($domain !== null) {
                // specified domain
                $url = 'http://' . $domain . '/' . $url;
            } else {
                // domain from env
                $url = Typo3GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $url;
            }
        }

        // Fix url stuff
        $url = str_replace('?&', '?', $url);

        return $url;
    }

    // ########################################################################
    // Protected methods
    // ########################################################################

    /**
     * Check if url is blacklisted
     *
     * @param  string $url           URL
     * @param  array  $blacklistConf Blacklist configuration (list of regexp)
     *
     * @return bool
     */
    public static function checkUrlForBlacklisting($url, array $blacklistConf)
    {
        // check for valid url
        if (empty($url)) {
            return true;
        }

        $blacklistConf = (array)$blacklistConf;
        foreach ($blacklistConf as $blacklistRegExp) {
            if (preg_match($blacklistRegExp, $url)) {
                return true;
            }
        }

        return false;
    }
}
