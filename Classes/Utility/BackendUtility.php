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
use TYPO3\CMS\Backend\Routing\UriBuilder;

/**
 * Backend utility
 */
class BackendUtility
{

    /**
     * Fetch list of root pages (is_siteroot) in TYPO3 (cached)
     *
     * @return  array
     */
    public static function getRootPageList()
    {
        static $cache = null;

        if ($cache === null) {
            $query = 'SELECT uid,
                             pid,
                             title
                        FROM pages
                       WHERE is_siteroot = 1
                         AND deleted = 0';
            $cache = DatabaseUtility::getAllWithIndex($query, 'uid');
        }

        return $cache;
    }

    /**
     * Fetch list of setting entries
     *
     * @return  array
     */
    public static function getRootPageSettingList()
    {
        static $cache = null;

        if ($cache === null) {
            $query = 'SELECT seosr.*
                        FROM tx_metaseo_setting_root seosr
                             INNER JOIN pages p
                                 ON p.uid = seosr.pid
                                AND p.is_siteroot = 1
                                AND p.deleted = 0
                       WHERE seosr.deleted = 0';
            $cache = DatabaseUtility::getAllWithIndex($query, 'pid');
        }

        return $cache;
    }

    /**
     * Returns the Ajax URL for a given AjaxID including a CSRF token.
     *
     * This function is mostly copied from the core, because it should not be used directly.
     * You need to test it at first usage in this project and eventually use the latest version from the core.
     * See #147 to learn about TYPO3's deprecation concept and our refactoring concept.
     *
     * Ajax URLs of all registered backend Ajax handlers are automatically published
     * to JavaScript inline settings: TYPO3.settings.ajaxUrls['ajaxId']
     *
     * @param string $ajaxIdentifier Identifier of the AJAX callback
     * @param array $urlParameters URL parameters that should be added as key value pairs
     * @param bool $returnAbsoluteUrl If set to TRUE, the URL returned will be absolute,
     *                                $backPathOverride will be ignored in this case
     *
     * @return string Calculated URL
     * @internal
     */
    public static function getAjaxUrl(
        $ajaxIdentifier,
        array $urlParameters = array(),
        $returnAbsoluteUrl = false
    ) {
        return self::getUriBuilder()
            ->buildUriFromAjaxId(
                $ajaxIdentifier,
                $urlParameters,
                $returnAbsoluteUrl ? UriBuilder::ABSOLUTE_URL : UriBuilder::ABSOLUTE_PATH
            );
    }

    /**
     * @return UriBuilder
     */
    public static function getUriBuilder()
    {
        return Typo3GeneralUtility::makeInstance('\\TYPO3\\CMS\\Backend\\Routing\\UriBuilder');
    }
}
