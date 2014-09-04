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
 * Root page utility
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: SitemapUtility.php 81677 2013-11-21 12:32:33Z mblaschke $
 */
class RootPageUtility {

    /**
     * Get domain
     *
     * @param   integer $rootPid    Root PID
     * @return  null|string
     */
    public static function getDomain($rootPid) {
        // Fetch domain name
        $query = 'SELECT domainName
                    FROM sys_domain
                   WHERE hidden = 0 AND pid = ' . (int)$rootPid.'
                ORDER BY forced DESC, sorting';
        $ret = DatabaseUtility::getOne($query);

        return $ret;
    }

    /**
     * Get sitemap index url
     *
     * @param  integer   $rootPid    Root PID
     * @return string
     */
    public static function getSitemapIndexUrl($rootPid) {
        $domain = self::getDomain($rootPid);

        if( !empty($domain) ) {
            $domain = 'http://' . $domain . '/';
        } else {
            $domain = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        }

        // Add sitemap
        $url = $domain . 'index.php?id=' . (int)$rootPid . '&type=841132';

        return $url;
    }

    /**
     * Get robots.txt url
     *
     * @param  integer   $rootPid    Root PID
     * @return string
     */
    public static function getRobotsTxtUrl($rootPid) {
        $domain = self::getDomain($rootPid);

        if( !empty($domain) ) {
            $domain = 'http://' . $domain . '/';
        } else {
            $domain = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        }

        // Add sitemap
        $url = $domain . 'index.php?id=' . (int)$rootPid . '&type=841133';

        return $url;
    }

}