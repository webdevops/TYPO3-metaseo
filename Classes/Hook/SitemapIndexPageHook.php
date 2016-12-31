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
class SitemapIndexPageHook extends SitemapIndexHook
{


    /**
     * Init configuration
     */
    protected function initConfiguration()
    {
        parent::initConfiguration();

        // Check custom index expiration (from connector)
        /** @var \Metaseo\Metaseo\Connector $connector */
        $connector    = $this->objectManager->get('Metaseo\\Metaseo\\Connector');
        $sitemapStore = $connector->getStore('sitemap');

        // Set new expiration date
        if (!empty($sitemapStore['expiration'])) {
            $this->indexExpiration = $_SERVER['REQUEST_TIME'] + ($sitemapStore['expiration'] * 24 * 60 * 60);
        }
    }

    // ########################################################################
    // HOOKS
    // ########################################################################

    /**
     * Hook: Index Page Content
     */
    public function hook_indexContent()
    {
        $this->addPageToSitemapIndex();

        $possibility = (int)GeneralUtility::getExtConf('sitemap_clearCachePossibility', 0);

        if ($possibility > 0) {
            $clearCacheChance = ceil(mt_rand(0, $possibility));
            if ($clearCacheChance == 1) {
                SitemapUtility::expire();
            }
        }
    }

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Add Page to sitemap table
     *
     * @return void
     */
    public function addPageToSitemapIndex()
    {
        if (!$this->checkIfSitemapIndexingIsEnabled('page')) {
            return;
        }

        if (!$this->checkIfNoLanguageFallback()) {
            // got content in fallback language => don't index
            return;
        }

        $pageUrl = $this->getPageUrl();

        // check blacklisting
        if (GeneralUtility::checkUrlForBlacklisting($pageUrl, $this->blacklistConf)) {
            return;
        }

        // Index page
        $pageData = $this->generateSitemapPageData($pageUrl);
        if (!empty($pageData)) {
            SitemapUtility::index($pageData);
        }
    }

    /**
     * Returns True if language chosen by L= parameter matches language of content
     * Returns False if content is in fallback language
     *
     * @return bool
     */
    protected function checkIfNoLanguageFallback()
    {
        $tsfe = self::getTsfe();
        // Check if we have fallen back to a default language
        if (GeneralUtility::getLanguageId() !== $tsfe->sys_language_uid) {
            return false; //don't index untranslated page
        }
        return true;
    }

    /**
     * Generate sitemap page data
     *
     * @param string $pageUrl Page url
     *
     * @return array
     */
    protected function generateSitemapPageData($pageUrl)
    {
        $page = $GLOBALS['TSFE']->page;

        $tstamp = $_SERVER['REQUEST_TIME'];

        $ret = array(
            'tstamp'                => $tstamp,
            'crdate'                => $tstamp,
            'page_rootpid'          => GeneralUtility::getRootPid(),
            'page_uid'              => $GLOBALS['TSFE']->id,
            'page_language'         => GeneralUtility::getLanguageId(),
            'page_url'              => $pageUrl,
            'page_depth'            => count($GLOBALS['TSFE']->rootLine),
            'page_change_frequency' => $this->getPageChangeFrequency($page),
            'page_type'             => SitemapUtility::SITEMAP_TYPE_PAGE,
            'expire'                => $this->indexExpiration,
        );

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'sitemapIndexPage', $this, $ret);

        return $ret;
    }

    /**
     * Get current page url
     *
     * @return null|string
     */
    protected function getPageUrl()
    {
        // Fetch chash
        $pageHash = null;
        if (!empty($GLOBALS['TSFE']->cHash)) {
            $pageHash = $GLOBALS['TSFE']->cHash;
        }


        // Fetch pageUrl
        if ($pageHash !== null) {
            $ret = FrontendUtility::getCurrentUrl();
        } else {
            $linkConf = array(
                'parameter' => $GLOBALS['TSFE']->id,
            );

            $ret = $GLOBALS['TSFE']->cObj->typoLink_URL($linkConf);
            $ret = $this->processLinkUrl($ret);
        }

        return $ret;
    }
}
