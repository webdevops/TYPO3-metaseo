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
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Sitemap utility
 */
class SitemapUtility
{

    const SITEMAP_TYPE_PAGE = 0;
    const SITEMAP_TYPE_FILE = 1;

    /* apply changes in Configuration/TypoScript/setup.txt */
    const PAGE_TYPE_SITEMAP_TXT = 841131; // sitemap.txt     (EXT:metaseo)
    const PAGE_TYPE_SITEMAP_XML = 841132; // sitemap.xml     (EXT:metaseo)
    const PAGE_TYPE_ROBOTS_TXT  = 841133; // robots.txt      (EXT:metaseo)

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * List of blacklisted doktypes (from table pages)
     * @var array
     */
    protected static $doktypeBlacklist = array(
        PageRepository::DOKTYPE_BE_USER_SECTION,  // Backend Section (TYPO3 CMS)
        PageRepository::DOKTYPE_SPACER,           // Menu separator  (TYPO3 CMS)
        PageRepository::DOKTYPE_SYSFOLDER,        // Folder          (TYPO3 CMS)
        PageRepository::DOKTYPE_RECYCLER,         // Recycler        (TYPO3 CMS)
        PageRepository::DOKTYPE_LINK,             // External Link   (TYPO3 CMS)
    );

    /**
     * List of blacklisted rendering PAGE typenum (typoscript object)
     *
     * @var array
     */
    protected static $pagetypeBlacklist = array(
        self::PAGE_TYPE_SITEMAP_TXT,   // sitemap.txt     (EXT:metaseo)
        self::PAGE_TYPE_SITEMAP_XML,   // sitemap.xml     (EXT:metaseo)
        self::PAGE_TYPE_ROBOTS_TXT,    // robots.txt      (EXT:metaseo)
    );


    // ########################################################################
    // Public methods
    // ########################################################################

    /**
     * Insert into sitemap
     *
     * @param   array $pageData page information
     */
    public static function index(array $pageData)
    {
        static $cache = array();

        // do not index empty urls
        if (empty($pageData['page_url'])) {
            return;
        }

        // Trim url
        $pageData['page_url'] = trim($pageData['page_url']);

        // calc page hash
        $pageData['page_hash'] = md5($pageData['page_url']);
        $pageHash              = $pageData['page_hash'];

        // set default type if not set
        if (!isset($pageData['page_type'])) {
            $pageData['page_type'] = self::SITEMAP_TYPE_PAGE;
        }

        // Escape/Quote data
        unset($pageDataValue);
        foreach ($pageData as &$pageDataValue) {
            if ($pageDataValue === null) {
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
            // TODO: INSERT INTO ... ON DUPLICATE KEY UPDATE?

            $query      = 'SELECT uid
                             FROM tx_metaseo_sitemap
                            WHERE page_uid      = ' . $pageData['page_uid'] . '
                              AND page_language = ' . $pageData['page_language'] . '
                              AND page_hash     = ' . $pageData['page_hash'] . '
                              AND page_type     = ' . $pageData['page_type'];
            $sitemapUid = DatabaseUtility::getOne($query);

            if (!empty($sitemapUid)) {
                $query = 'UPDATE tx_metaseo_sitemap
                             SET tstamp                = ' . $pageData['tstamp'] . ',
                                 page_rootpid          = ' . $pageData['page_rootpid'] . ',
                                 page_language         = ' . $pageData['page_language'] . ',
                                 page_url              = ' . $pageData['page_url'] . ',
                                 page_depth            = ' . $pageData['page_depth'] . ',
                                 page_change_frequency = ' . $pageData['page_change_frequency'] . ',
                                 page_type             = ' . $pageData['page_type'] . ',
                                 expire                = ' . $pageData['expire'] . '
                           WHERE uid = ' . (int)$sitemapUid;
                DatabaseUtility::exec($query);
            } else {
                // #####################################
                // INSERT
                // #####################################
                DatabaseUtility::connection()->exec_INSERTquery(
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
    public static function expire()
    {
        // #####################
        // Delete expired entries
        // #####################

        $query = 'DELETE FROM tx_metaseo_sitemap
                        WHERE is_blacklisted = 0
                          AND expire <= ' . (int)time();
        DatabaseUtility::exec($query);

        // #####################
        //  Deleted or
        // excluded pages
        // #####################
        $query = 'SELECT ts.uid
                    FROM tx_metaseo_sitemap ts
                         LEFT JOIN pages p
                            ON p.uid = ts.page_uid
                           AND p.deleted = 0
                           AND p.hidden = 0
                           AND p.tx_metaseo_is_exclude = 0
                           AND ' . DatabaseUtility::conditionNotIn('p.doktype', self::getDoktypeBlacklist()) . '
                   WHERE p.uid IS NULL';

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
     * Get list of blacklisted doktypes (from table pages)
     *
     * @return array
     */
    public static function getDoktypeBlacklist()
    {
        return self::$doktypeBlacklist;
    }

    /**
     * Get list of blacklisted PAGE typenum (typoscript object)
     *
     * @return array
     */
    public static function getPageTypeBlacklist()
    {
        $ret = self::$pagetypeBlacklist;

        // Fetch from SetupTS (comma separated list)
        if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.']['sitemap.']['index.']['pageTypeBlacklist'])
            && strlen(
                $GLOBALS['TSFE']
                    ->tmpl
                    ->setup['plugin.']['metaseo.']['sitemap.']['index.']['pageTypeBlacklist']
            ) >= 1
        ) {
            $pageTypeBlacklist = $GLOBALS['TSFE']->tmpl
                                     ->setup['plugin.']['metaseo.']['sitemap.']['index.']['pageTypeBlacklist'];
            $pageTypeBlacklist = Typo3GeneralUtility::trimExplode(',', $pageTypeBlacklist);

            $ret = array_merge($ret, $pageTypeBlacklist);
        }

        return $ret;
    }

    /**
     * Return list of sitemap pages
     *
     * @param   integer $rootPid    Root page id of tree
     * @param   integer $languageId Limit to language id
     *
     * @return  array
     */
    public static function getList($rootPid, $languageId = null)
    {
        $sitemapList = array();
        $pageList    = array();

        $typo3Pids = array();

        $query = 'SELECT ts.*
                    FROM tx_metaseo_sitemap ts
                            INNER JOIN pages p
                              ON    p.uid = ts.page_uid
                                AND p.deleted = 0
                                AND p.hidden = 0
                                AND p.tx_metaseo_is_exclude = 0
                                AND ' . DatabaseUtility::conditionNotIn('p.doktype', self::getDoktypeBlacklist()) . '
                   WHERE ts.page_rootpid = ' . (int)$rootPid . '
                     AND ts.is_blacklisted = 0';

        if ($languageId !== null) {
            $query .= ' AND ts.page_language = ' . (int)$languageId;
        }
        $query .= ' ORDER BY
                        ts.page_depth ASC,
                        p.pid ASC,
                        p.sorting ASC';
        $resultRows = DatabaseUtility::getAll($query);

        if (!$resultRows) {

            return self::getListArray(); //empty
        }

        foreach ($resultRows as $row) {
            $sitemapList[] = $row;

            $sitemapPageId             = $row['page_uid'];
            $typo3Pids[$sitemapPageId] = (int)$sitemapPageId;
        }

        if (!empty($typo3Pids)) {
            $query    = 'SELECT *
                           FROM pages
                          WHERE ' . DatabaseUtility::conditionIn('uid', $typo3Pids);
            $pageList = DatabaseUtility::getAllWithIndex($query, 'uid');

            if (empty($pageList)) {

                return self::getListArray(); //empty
            }
        }

        return self::getListArray($sitemapList, $pageList);
    }

    /**
     * Combines two arrays
     *
     * @param array $sitemapList list of metaseo sitemaps
     * @param array $pageList    list of pages for these sitemaps
     *
     * @return array
     */
    protected static function getListArray(array $sitemapList = array(), array $pageList = array())
    {
        return array(
            'tx_metaseo_sitemap' => $sitemapList,
            'pages'              => $pageList
        );
    }
}
