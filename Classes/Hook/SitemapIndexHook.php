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

use Metaseo\Metaseo\Utility\FrontendUtility;
use Metaseo\Metaseo\Utility\RootPageUtility;
use Metaseo\Metaseo\Utility\SitemapUtility;
use Metaseo\Metaseo\Utility\GeneralUtility;
use \TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Sitemap Indexer
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: SitemapIndexHook.php 84520 2014-03-28 10:33:24Z mblaschke $
 */
class SitemapIndexHook implements \TYPO3\CMS\Core\SingletonInterface {

    // ########################################################################
    // Attributes
    // ########################################################################

    protected $typeBlacklist = array();

    /**
     * Page index status
     *
     * @var null|boolean
     */
    protected $pageIndexFlag = null;

    /**
     * MetaSEO configuration
     *
     * @var array
     */
    protected $conf = array();

    /**
     * Blacklist configuration
     *
     * @var array
     */
    protected $blacklistConf = array();

    /**
     * File extension list
     *
     * @var array
     */
    protected $fileExtList = array();

    /**
     * Sitemap entry expiration
     *
     * @var integer
     */
    protected $indexExpiration;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Constructor
     */
    public function __construct() {
        $this->initConfiguration();
    }

    /**
     * Init configuration
     */
    protected function initConfiguration() {
        // Get configuration
        if (!empty($GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.'])) {
            $this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.'];
        }

        // Store blacklist configuration
        if (!empty($this->conf['sitemap.']['index.']['blacklist.'])) {
            $this->blacklistConf = $this->conf['sitemap.']['index.']['blacklist.'];
        }

        // Store blacklist configuration
        if (!empty($this->conf['sitemap.']['index.']['fileExtension.'])) {
            # File extensions can be a comma separated list
            foreach ($this->conf['sitemap.']['index.']['fileExtension.'] as $fileExtListRaw) {
                $fileExtList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fileExtListRaw);
                $this->fileExtList = array_merge($this->fileExtList, $fileExtList);
            };
        }

        // Get expiration
        $expirationInDays = 60;
        if (!empty($this->conf['sitemap.']['expiration'])) {
            $expirationInDays = abs($this->conf['sitemap.']['expiration']);
        }

        $this->indexExpiration = $_SERVER['REQUEST_TIME'] + ($expirationInDays * 24 * 60 * 60);

        // Init blacklist for page types
        $this->typeBlacklist = SitemapUtility::getPageTypeBlacklist();
    }

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

    /**
     * Process/Clear link url
     *
     * @param   string $linkUrl Link url
     *
     * @return  string
     */
    protected static function processLinkUrl($linkUrl) {
        static $absRefPrefix = null;
        static $absRefPrefixLength = 0;
        $ret = $linkUrl;

        // Fetch abs ref prefix if available/set
        if ($absRefPrefix === null) {
            if (!empty($GLOBALS['TSFE']->tmpl->setup['config.']['absRefPrefix'])) {
                $absRefPrefix = $GLOBALS['TSFE']->tmpl->setup['config.']['absRefPrefix'];
                $absRefPrefixLength = strlen($absRefPrefix);
            } else {
                $absRefPrefix = false;
            }
        }

        // remove abs ref prefix
        if ($absRefPrefix !== false && strpos($ret, $absRefPrefix) === 0) {
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


    /**
     * Hook: Link Parser
     *
     * @param   object $pObj Object
     *
     * @return  boolean|null
     */
    public function hook_linkParse(&$pObj) {
        // check if sitemap is enabled in root
        if (!GeneralUtility::getRootSettingValue('is_sitemap', true)
            || !GeneralUtility::getRootSettingValue('is_sitemap_typolink_indexer', true)
        ) {
            return true;
        }

        // check current page
        if (!$this->checkIfCurrentPageIsIndexable()) {
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
        $linkUrl = $pObj['finalTagParts']['url'];
        list($linkPageUid, $linkType) = $this->parseLinkConf($pObj);
        $linkUrl = $this->processLinkUrl($linkUrl);

        if ($linkType === null || empty($linkPageUid)) {
            // no valid link
            return;
        }

        // check blacklisting
        if (GeneralUtility::checkUrlForBlacklisting($linkUrl, $this->blacklistConf)) {
            return;
        }

        // ####################################
        //  Init
        // ####################################

        $addParameters = array();
        if (!empty($linkConf['additionalParams'])) {
            parse_str($linkConf['additionalParams'], $addParameters);
        }

        // #####################################
        // Check if link is cacheable
        // #####################################
        $isValid = false;

        // check if conf is valid
        if (!empty($linkConf['useCacheHash'])) {
            $isValid = true;
        }

        // check for typical typo3 params
        $addParamsCache = $addParameters;
        unset($addParamsCache['L']);
        unset($addParamsCache['type']);

        if (empty($addParamsCache)) {
            $isValid = true;
        }

        if (!$isValid) {
            // page is not cacheable, skip it
            return;
        }

        // #####################################
        // Rootline
        // #####################################
        $rootline = GeneralUtility::getRootLine($linkPageUid);

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
        } elseif (!empty($this->conf['sitemap.']['changeFrequency'])) {
            $pageChangeFrequency = (int)$this->conf['sitemap.']['changeFrequency'];
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
            'page_uid'              => $linkPageUid,
            'page_language'         => $pageLanguage,
            'page_url'              => $pageUrl,
            'page_depth'            => count($rootline),
            'page_change_frequency' => $pageChangeFrequency,
            'page_type'             => $linkType,
            'expire'                => $this->indexExpiration,
        );

        // Call hook
        GeneralUtility::callHook('sitemap-index-link', null, $pageData);

        if (!empty($pageData)) {
            \Metaseo\Metaseo\Utility\SitemapUtility::index($pageData);
        }

        return true;
    }

    /**
     * Parse uid and type from generated link (from config array)
     *
     * @param  array $conf Generated Link config array
     *
     * @return array
     */
    protected function parseLinkConf($conf) {
        $uid = null;
        $type = null;

        // Check link type
        switch ($conf['finalTagParts']['TYPE']) {
            // ##############
            // Page URL
            // ##############
            case 'page':

                // TODO: Add support for more parameter checks
                if (is_numeric($conf['conf']['parameter'])) {
                    $uid = $conf['conf']['parameter'];
                }

                $type = SitemapUtility::SITEMAP_TYPE_PAGE;
                break;

            // ##############
            // File URL
            // ##############
            case 'file':

                $fileUrl = $conf['finalTagParts']['url'];

                if ($this->checkIfFileIsWhitelisted($fileUrl)) {
                    // File will be registered from the root page
                    // to prevent duplicate urls
                    $uid = GeneralUtility::getRootPid();
                    $type = SitemapUtility::SITEMAP_TYPE_FILE;
                }
                break;
        }

        return array($uid, $type);
    }

    /**
     * Check if file is whitelisted
     *
     * Configuration specified in
     * plugin.metaseo.sitemap.index.fileExtension
     *
     * @param   string $url Url to file
     *
     * @return  boolean
     */
    protected function checkIfFileIsWhitelisted($url) {
        $ret = false;

        // check for valid url
        if (empty($url)) {
            return false;
        }

        // parse url to extract only path
        $urlParts = parse_url($url);
        $filePath = $urlParts['path'];

        // Extract last file extension
        if (preg_match('/\.([^\.]+)$/', $filePath, $matches)) {
            $fileExt = trim(strtolower($matches[1]));

            // Check if file extension is whitelisted
            foreach ($this->fileExtList as $allowedFileExt) {
                if ($allowedFileExt === $fileExt) {
                    // File is whitelisted, not blacklisted
                    $ret = true;
                    break;
                }
            }
        }

        return $ret;
    }

    /**
     * Check if current page is indexable
     *
     * Will do following checks:
     * - REQUEST_METHOD (must be GET)
     * - If there is a feuser session
     * - Page type blacklisting
     * - If page is static cacheable
     * - If no_cache is not set
     *
     * (checks will be cached)
     *
     * @return bool
     */
    protected function checkIfCurrentPageIsIndexable() {
        // check caching status
        if ($this->pageIndexFlag !== null) {
            return $this->pageIndexFlag;
        }

        // by default page is not cacheable
        $this->pageIndexFlag = false;

        // ############################
        // Basic checks
        // ############################

        // skip POST-calls and feuser login
        if ($_SERVER['REQUEST_METHOD'] !== 'GET'
            || !empty($GLOBALS['TSFE']->fe_user->user['uid'])
        ) {
            return false;
        }

        // Check for type blacklisting
        if (in_array($GLOBALS['TSFE']->type, $this->typeBlacklist)) {
            return false;
        }

        // ############################
        // Cache checks
        // ############################

        // dont parse if page is not cacheable
        if (!$GLOBALS['TSFE']->isStaticCacheble()) {
            return false;
        }

        // Skip no_cache-pages
        if (!empty($GLOBALS['TSFE']->no_cache)) {
            return false;
        }

        // all checks successfull, page is cacheable
        $this->pageIndexFlag = true;

        return true;
    }
}
