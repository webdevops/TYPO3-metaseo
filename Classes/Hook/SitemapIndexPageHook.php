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

namespace Metaseo\Metaseo\Hook;

use Metaseo\Metaseo\Utility\FrontendUtility;
use Metaseo\Metaseo\Utility\GeneralUtility;
use Metaseo\Metaseo\Utility\SitemapUtility;

/**
 * Sitemap Indexer
 */
class SitemapIndexPageHook extends SitemapIndexHook {

    // ########################################################################
    // HOOKS
    // ########################################################################

    /**
     * Hook: Index Page Content
     *
     * @param    object $pObj Object
     */
    public function hook_indexContent(&$pObj) {
        $this->addPageToSitemapIndex();

        $possibility = (int)GeneralUtility::getExtConf('sitemap_clearCachePossibility', 0);

        if ($possibility > 0) {

            $clearCacheChance = ceil(mt_rand(0, $possibility));
            if ($clearCacheChance == 1) {
                \Metaseo\Metaseo\Utility\SitemapUtility::expire();
            }
        }
    }

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Add Page to sitemap table
     */
    public function addPageToSitemapIndex() {
        // check if sitemap is enabled in root
        if (!GeneralUtility::getRootSettingValue('is_sitemap', true)
            || !GeneralUtility::getRootSettingValue('is_sitemap_page_indexer', true)
        ) {
            return true;
        }

        // check current page
        if (!$this->checkIfCurrentPageIsIndexable()) {
            return;
        }

        // Fetch chash
        $pageHash = null;
        if (!empty($GLOBALS['TSFE']->cHash)) {
            $pageHash = $GLOBALS['TSFE']->cHash;
        }

        // Fetch sysLanguage
        $pageLanguage = GeneralUtility::getLanguageId();

        // Fetch page changeFrequency
        $pageChangeFrequency = 0;
        if (!empty($GLOBALS['TSFE']->page['tx_metaseo_change_frequency'])) {
            $pageChangeFrequency = (int)$GLOBALS['TSFE']->page['tx_metaseo_change_frequency'];
        } elseif (!empty($this->conf['sitemap.']['changeFrequency'])) {
            $pageChangeFrequency = (int)$this->conf['sitemap.']['changeFrequency'];
        }

        if (empty($pageChangeFrequency)) {
            $pageChangeFrequency = 0;
        }

        // Fetch pageUrl
        if ($pageHash !== null) {
            $pageUrl = FrontendUtility::getCurrentUrl();
        } else {
            $linkConf = array(
                'parameter' => $GLOBALS['TSFE']->id,
            );

            $pageUrl = $GLOBALS['TSFE']->cObj->typoLink_URL($linkConf);
            $pageUrl = $this->processLinkUrl($pageUrl);
        }

        // check blacklisting
        if (GeneralUtility::checkUrlForBlacklisting($pageUrl, $this->blacklistConf)) {
            return;
        }

        $tstamp = $_SERVER['REQUEST_TIME'];

        $pageData = array(
            'tstamp'                => $tstamp,
            'crdate'                => $tstamp,
            'page_rootpid'          => GeneralUtility::getRootPid(),
            'page_uid'              => $GLOBALS['TSFE']->id,
            'page_language'         => $pageLanguage,
            'page_url'              => $pageUrl,
            'page_depth'            => count($GLOBALS['TSFE']->rootLine),
            'page_change_frequency' => $pageChangeFrequency,
            'page_type'             => SitemapUtility::SITEMAP_TYPE_PAGE,
            'expire'                => $this->indexExpiration,
        );

        // Call hook
        GeneralUtility::callHook('sitemap-index-page', null, $pageData);

        if (!empty($pageData)) {
            \Metaseo\Metaseo\Utility\SitemapUtility::index($pageData, 'page');
        }

        return true;
    }
}