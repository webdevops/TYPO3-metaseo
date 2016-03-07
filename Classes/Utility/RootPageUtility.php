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
 * Root page utility
 */
class RootPageUtility
{

    /**
     * Domain cache
     *
     * array(
     *   rootPid => domainName
     * );
     *
     * @var array
     */
    protected static $domainCache = array();

    /**
     * Get sitemap index url
     *
     * @param  integer $rootPid Root PID
     *
     * @return string
     */
    public static function getSitemapIndexUrl($rootPid)
    {
        return self::getFrontendUrl($rootPid, SitemapUtility::PAGE_TYPE_SITEMAP_XML);
    }

    /**
     * Build a frontend url
     *
     * @param integer $rootPid Root Page ID
     * @param integer $typeNum Type num
     *
     * @return string
     */
    public static function getFrontendUrl($rootPid, $typeNum)
    {
        $domain = self::getDomain($rootPid);
        if (!empty($domain)) {
            $domain = 'http://' . $domain . '/';
        } else {
            $domain = Typo3GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        }
        // "build", TODO: use typolink to use TYPO3 internals
        $url = $domain . 'index.php?id=' . (int)$rootPid . '&type=' . (int)$typeNum;

        return $url;
    }

    /**
     * Get domain
     *
     * @param   integer $rootPid Root PID
     *
     * @return  null|string
     */
    public static function getDomain($rootPid)
    {
        // Use cached one if exists
        if (isset(self::$domainCache[$rootPid])) {
            return self::$domainCache[$rootPid];
        }

        // Fetch domain name
        $query = 'SELECT domainName
                    FROM sys_domain
                   WHERE pid = ' . (int)$rootPid . '
                     AND hidden = 0
                ORDER BY forced DESC,
                         sorting
                   LIMIT 1';
        $ret   = DatabaseUtility::getOne($query);

        // Remove possible slash at the end
        $ret = rtrim($ret, '/');

        // Cache entry
        self::$domainCache[$rootPid] = $ret;

        return $ret;
    }

    /**
     * Get robots.txt url
     *
     * @param  integer $rootPid Root PID
     *
     * @return string
     */
    public static function getRobotsTxtUrl($rootPid)
    {
        return self::getFrontendUrl($rootPid, SitemapUtility::PAGE_TYPE_ROBOTS_TXT);
    }
}
