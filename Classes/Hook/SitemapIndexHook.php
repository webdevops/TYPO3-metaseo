<?php
namespace Metaseo\Metaseo\Hook;

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
 * Sitemap Indexer
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: SitemapIndexHook.php 84520 2014-03-28 10:33:24Z mblaschke $
 */
class SitemapIndexHook {

    // ########################################################################
    // Attributes
    // ########################################################################

    static protected $_typeBlacklist = array(
        6,      // Backend Section (TYPO3 CMS)
        199,    // Menu separator  (TYPO3 CMS)
        254,    // Folder          (TYPO3 CMS)
        255,    // Recycler        (TYPO3 CMS)
        841131, // sitemap.txt     (metaseo)
        841132, // sitemap.xml     (metaseo)
        841133, // robots.txt      (metaseo)
    );

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Add Page to sitemap table
     */
    public function addPageToSitemapIndex() {
        // check if sitemap is enabled in root
        if (!\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue('is_sitemap', TRUE)
            || !\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue('is_sitemap_page_indexer', TRUE)
        ) {
            return TRUE;
        }

        // check current page
        if( !self::_checkIfCurrentPageIsIndexable() ) {
            return;
        }

        // Fetch chash
        $pageHash = NULL;
        if (!empty($GLOBALS['TSFE']->cHash)) {
            $pageHash = $GLOBALS['TSFE']->cHash;
        }

        // Fetch sysLanguage
        $pageLanguage = \Metaseo\Metaseo\Utility\GeneralUtility::getLanguageId();

        // Fetch page changeFrequency
        $pageChangeFrequency = 0;
        if (!empty($GLOBALS['TSFE']->page['tx_metaseo_change_frequency'])) {
            $pageChangeFrequency = (int)$GLOBALS['TSFE']->page['tx_metaseo_change_frequency'];
        } elseif (!empty($GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.']['sitemap.']['changeFrequency'])) {
            $pageChangeFrequency = (int)$GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.']['sitemap.']['changeFrequency'];
        }

        if (empty($pageChangeFrequency)) {
            $pageChangeFrequency = 0;
        }

        // Fetch pageUrl
        if ($pageHash !== NULL) {
            $pageUrl = $GLOBALS['TSFE']->anchorPrefix;
        } else {
            $linkConf = array(
                'parameter' => $GLOBALS['TSFE']->id,
            );

            $pageUrl = $GLOBALS['TSFE']->cObj->typoLink_URL($linkConf);
            $pageUrl = self::_processLinkUrl($pageUrl);
        }

        $tstamp = $_SERVER['REQUEST_TIME'];

        $pageData = array(
            'tstamp'                => $tstamp,
            'crdate'                => $tstamp,
            'page_rootpid'          => \Metaseo\Metaseo\Utility\GeneralUtility::getRootPid(),
            'page_uid'              => $GLOBALS['TSFE']->id,
            'page_language'         => $pageLanguage,
            'page_url'              => $pageUrl,
            'page_depth'            => count($GLOBALS['TSFE']->rootLine),
            'page_change_frequency' => $pageChangeFrequency,
        );

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('sitemap-index-page', NULL, $pageData);

        if (!empty($pageData)) {
            \Metaseo\Metaseo\Utility\SitemapUtility::index($pageData, 'page');
        }

        return TRUE;
    }

    /**
     * Process/Clear link url
     *
     * @param   string  $linkUrl    Link url
     * @return  string
     */
    protected static function _processLinkUrl($linkUrl) {
        static $absRefPrefix = NULL;
        static $absRefPrefixLength = 0;
        $ret = $linkUrl;

        // Fetch abs ref prefix if available/set
        if ($absRefPrefix === NULL) {
            if (!empty($GLOBALS['TSFE']->tmpl->setup['config.']['absRefPrefix'])) {
                $absRefPrefix       = $GLOBALS['TSFE']->tmpl->setup['config.']['absRefPrefix'];
                $absRefPrefixLength = strlen($absRefPrefix);
            } else {
                $absRefPrefix = FALSE;
            }
        }

        // remove abs ref prefix
        if ($absRefPrefix !== FALSE && strpos($ret, $absRefPrefix) === 0) {
            $ret = substr($ret, $absRefPrefixLength);
        }

        return $ret;
    }

    // ########################################################################
    // HOOKS
    // ########################################################################

    /**
     * Hook: Index Page Content
     *
     * @param    object $pObj    Object
     */
    public function hook_indexContent(&$pObj) {
        $this->addPageToSitemapIndex();

        $possibility = (int)\Metaseo\Metaseo\Utility\GeneralUtility::getExtConf('sitemap_clearCachePossibility', 0);

        if ($possibility > 0) {

            $clearCacheChance = ceil(mt_rand(0, $possibility));
            if ($clearCacheChance == 1) {
                \Metaseo\Metaseo\Utility\SitemapUtility::expire();
            }
        }
    }


    /**
     * Hook: Link Parser
     *
     * @param   object          $pObj    Object
     * @return  boolean|null
     */
    public static function hook_linkParse(&$pObj) {
        // check if sitemap is enabled in root
        if (!\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue('is_sitemap', TRUE)
            || !\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue('is_sitemap_typolink_indexer', TRUE)
        ) {
            return TRUE;
        }

        // check current page
        if( !self::_checkIfCurrentPageIsIndexable() ) {
            return;
        }

        // Check
        if (empty($pObj['finalTagParts'])
            || empty($pObj['conf'])
            || empty($pObj['finalTagParts']['url'])
        ) {
            // no valid link
            return;
        }

        // Init link informations
        $linkConf = $pObj['conf'];
        $linkUrl  = $pObj['finalTagParts']['url'];
        $linkUrl  = self::_processLinkUrl($linkUrl);

        if (!is_numeric($linkConf['parameter'])) {
            // not valid internal link
            return;
        }

        if( empty($linkUrl) ) {
            // invalid url? should be never empty!
            return;
        }

        // ####################################
        //  Init
        // ####################################
        $uid = $linkConf['parameter'];

        $addParameters = array();
        if (!empty($linkConf['additionalParams'])) {
            parse_str($linkConf['additionalParams'], $addParameters);
        }

        // #####################################
        // Check if link is cacheable
        // #####################################
        $isValid = FALSE;

        // check if conf is valid
        if (!empty($linkConf['useCacheHash'])) {
            $isValid = TRUE;
        }

        // check for typical typo3 params
        $addParamsCache = $addParameters;
        unset($addParamsCache['L']);
        unset($addParamsCache['type']);

        if (empty($addParamsCache)) {
            $isValid = TRUE;
        }

        if (!$isValid) {
            // page is not cacheable, skip it
            return;
        }

        // #####################################
        // Rootline
        // #####################################
        $rootline = \Metaseo\Metaseo\Utility\GeneralUtility::getRootLine($uid);

        if (empty($rootline)) {
            return;
        }

        $page = reset($rootline);

        // #####################################
        // Build relative url
        // #####################################
        $linkParts = parse_url($linkUrl);

        // Remove left / (but only if not root page)
        if ($linkParts['path'] === '/') {
            // Link points to root page
            $pageUrl = '/';
        } else {
            // Link points to another page, strip left /
            $pageUrl = ltrim($linkParts['path'], '/');
        }

        // Add query
        if (!empty($linkParts['query'])) {
            $pageUrl .= '?' . $linkParts['query'];
        }

        // #####################################
        // Page settings
        // #####################################
        // Fetch page changeFrequency
        $pageChangeFrequency = 0;
        if (!empty($page['tx_metaseo_change_frequency'])) {
            $pageChangeFrequency = (int)$page['tx_metaseo_change_frequency'];
        } elseif (!empty($GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.']['sitemap.']['changeFrequency'])) {
            $pageChangeFrequency = (int)$GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.']['sitemap.']['changeFrequency'];
        }

        // Fetch sysLanguage
        $pageLanguage = 0;
        if (isset($addParameters['L'])) {
            $pageLanguage = (int)$addParameters['L'];
        } elseif (!empty($GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'])) {
            $pageLanguage = (int)$GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'];
        }

        // #####################################
        // Indexing
        // #####################################
        $tstamp = $_SERVER['REQUEST_TIME'];

        $pageData = array(
            'tstamp'                => $tstamp,
            'crdate'                => $tstamp,
            'page_rootpid'          => $rootline[0]['uid'],
            'page_uid'              => $linkConf['parameter'],
            'page_language'         => $pageLanguage,
            'page_url'              => $pageUrl,
            'page_depth'            => count($rootline),
            'page_change_frequency' => $pageChangeFrequency,
        );

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('sitemap-index-link', NULL, $pageData);

        if (!empty($pageData)) {
            \Metaseo\Metaseo\Utility\SitemapUtility::index($pageData, 'link');
        }

        return TRUE;
    }


    /**
     * Check current page
     *
     * @return bool
     */
    protected static function _checkIfCurrentPageIsIndexable() {
        // skip POST-calls and feuser login
        if ($_SERVER['REQUEST_METHOD'] !== 'GET'
            || !empty($GLOBALS['TSFE']->fe_user->user['uid'])
        ) {
            return FALSE;
        }

        // Check for type blacklisting
        if( in_array($GLOBALS['TSFE']->type, self::$_typeBlacklist) ) {
            return FALSE;
        }

        // dont parse if page is not cacheable
        if (!$GLOBALS['TSFE']->isStaticCacheble()) {
            return FALSE;
        }

        // Skip no_cache-pages
        if (!empty($GLOBALS['TSFE']->no_cache)) {
            return FALSE;
        }

        return TRUE;
    }
}
