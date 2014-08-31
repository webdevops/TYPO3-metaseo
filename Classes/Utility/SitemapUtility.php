<?php
namespace Metaseo\Metaseo\Utility;

use Metaseo\Metaseo\Utility\DatabaseUtility;

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
 * Sitemap utility
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: SitemapUtility.php 81677 2013-11-21 12:32:33Z mblaschke $
 */
class SitemapUtility {

    // ########################################################################
    // Public methods
    // ########################################################################

    /**
     * Insert into sitemap
     *
     * @param   array $pageData   Page informations
     * @param   string $type       Parser type (page/link)
     */
    public static function index($pageData, $type) {
        static $cache = array();

        // do not index empty urls
        if( empty($pageData['page_url']) ) {
            return;
        }

        // calc page hash
        $pageData['page_hash'] = md5($pageData['page_url']);
        $pageHash = $pageData['page_hash'];

        // Escape/Quote data
        unset($pageDataValue);
        foreach ($pageData as &$pageDataValue) {
            if ($pageDataValue === NULL) {
                $pageDataValue = 'NULL';
            } elseif (is_int($pageDataValue) || is_numeric($pageDataValue)) {
                // Don't quote numeric/integers
                $pageDataValue = (int)$pageDataValue;
            } else {
                // String
                $pageDataValue = DatabaseUtility::quote($pageDataValue, 'tx_metaseo_sitemap');
            }
        }
        unset($pageDataValue);

        // only process each page once to keep sql-statements at a normal level
        if (empty($cache[$pageHash])) {

            // $pageData is already quoted

            $query = 'SELECT uid
                        FROM tx_metaseo_sitemap
                       WHERE page_uid      = ' . $pageData['page_uid'] . '
                         AND page_language = ' . $pageData['page_language'] . '
                         AND page_hash     = ' . $pageData['page_hash'];
            $sitemapUid = DatabaseUtility::getOne($query);

            if ( !empty($sitemapUid) ) {
                $query = 'UPDATE tx_metaseo_sitemap
                             SET tstamp                = ' . $pageData['tstamp'] . ',
                                 page_rootpid          = ' . $pageData['page_rootpid'] . ',
                                 page_language         = ' . $pageData['page_language'] . ',
                                 page_url              = ' . $pageData['page_url'] . ',
                                 page_depth            = ' . $pageData['page_depth'] . ',
                                 page_change_frequency = ' . $pageData['page_change_frequency'] . '
                            WHERE uid = ' . (int)$sitemapUid;
                DatabaseUtility::exec($query);
            } else {
                // #####################################
                // INSERT
                // #####################################
                $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                    'tx_metaseo_sitemap',
                    $pageData,
                    array_keys($pageData)
                );
            }

            $cache[$pageHash] = 1;
        }
    }

    /**
     * Clear outdated and invalid pages from sitemap table
     */
    public static function expire() {
        // #####################
        // Expired pages
        // #####################
        $expireDays = (int)\Metaseo\Metaseo\Utility\GeneralUtility::getExtConf('sitemap_pageSitemapExpireDays', 60);
        if( empty($expireDays) ) {
            $expireDays = 60;
        }

		// No negative days allowed
		$expireDays = abs($expireDays);

        $tstamp = time() - $expireDays * 24 * 60 * 60;

        $query = 'DELETE FROM tx_metaseo_sitemap
                        WHERE tstamp <= ' . (int)$tstamp . '
                          AND is_blacklisted = 0';
        DatabaseUtility::exec($query);

        // #####################
        //  Deleted or
        // excluded pages
        // #####################
        $query = 'SELECT
                        ts.uid
                    FROM
                        tx_metaseo_sitemap ts
                        LEFT JOIN pages p
                            ON p.uid = ts.page_uid
                           AND p.deleted = 0
                           AND p.hidden = 0
                           AND p.tx_metaseo_is_exclude = 0
                    WHERE
                        p.uid IS NULL';
        $deletedSitemapPages = DatabaseUtility::getColWithIndex($query);

        // delete pages
        if (!empty($deletedSitemapPages)) {
            $query = 'DELETE FROM tx_metaseo_sitemap
                            WHERE uid IN (' . implode(',', $deletedSitemapPages) . ')
                              AND is_blacklisted = 0';
            DatabaseUtility::exec($query);
        }
    }


    /**
     * Return list of sitemap pages
     *
     * @param   integer $rootPid        Root page id of tree
     * @param   integer $languageId     Limit to language id
     * @return  boolean|array
     */
    public static function getList($rootPid, $languageId = NULL) {
        $sitemapList = array();
        $pageList    = array();

        $typo3Pids     = array();

        $query = 'SELECT ts.*
                    FROM tx_metaseo_sitemap ts
                            INNER JOIN pages p
                              ON	p.uid = ts.page_uid
                                AND	p.deleted = 0
                                AND	p.hidden = 0
                                AND	p.tx_metaseo_is_exclude = 0
                   WHERE ts.page_rootpid = ' . (int)$rootPid . '
                     AND ts.is_blacklisted = 0';

        if ($languageId !== NULL) {
            $query .= ' AND ts.page_language = ' . (int)$languageId;
        }
        $query .= ' ORDER BY
                        ts.page_depth ASC,
                        p.pid ASC,
                        p.sorting ASC';
        $resultRows = DatabaseUtility::getAll($query);

        if (!$resultRows) {
            return FALSE;
        }

        foreach ($resultRows as $row) {
            $sitemapList[] = $row;

            $sitemapPageId             = $row['page_uid'];
            $typo3Pids[$sitemapPageId] = (int)$sitemapPageId;
        }

        if (!empty($typo3Pids)) {
            $query = 'SELECT *
                        FROM pages
                       WHERE '.DatabaseUtility::conditionIn('uid', $typo3Pids);
            $pageList = DatabaseUtility::getAllWithIndex($query, 'uid');

            if ( empty($pageList) ) {
                return FALSE;
            }
        }

        $ret = array(
            'tx_metaseo_sitemap' => $sitemapList,
            'pages'              => $pageList
        );

        return $ret;
    }
}
