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

use Metaseo\Metaseo\Utility\GeneralUtility;
use Metaseo\Metaseo\Utility\SitemapUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Sitemap Indexer
 */
class SitemapIndexLinkHook extends SitemapIndexHook
{


    // ########################################################################
    // HOOKS
    // ########################################################################

    /**
     * Hook: Link Parser
     *
     * @param   array $pObj Object
     *
     * @return  void
     */
    public function hook_linkParse(array &$pObj)
    {
        if (!$this->checkIfSitemapIndexingIsEnabled('typolink')) {
            return;
        }

        // Check
        if (empty($pObj['finalTagParts']) || empty($pObj['conf']) || empty($pObj['finalTagParts']['url'])) {
            // no valid link
            return;
        }

        // Init link information
        $linkConf = $pObj['conf'];
        $linkUrl  = $pObj['finalTagParts']['url'];
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

        // #####################################
        // Page settings
        // #####################################

        // Fetch sysLanguage
        if (isset($addParameters['L'])) {
            $pageLanguage = (int)$addParameters['L'];
        } else {
            $pageLanguage = (int)GeneralUtility::getLanguageId();
        }

        if (!$this->checkIfTranslationExists($linkPageUid, $pageLanguage)) {
            //translation does not exist => don't index
            return;
        }

        // Index link
        $pageData = $this->generateSitemapPageData($linkUrl, $linkPageUid, $rootline, $pageLanguage, $linkType);
        if (!empty($pageData)) {
            SitemapUtility::index($pageData);
        }
    }

    /**
     * Returns True if translation exists for a chosen language (L=) parameter
     * Returns False if no translation exists
     *
     * @return bool
     */
    protected function checkIfTranslationExists($linkPageUid, $requestLanguage)
    {
        if ($requestLanguage === 0) {
            //default language always exists
            return true;
        }
        $translated =  BackendUtility::getRecordLocalization('pages', $linkPageUid, $requestLanguage);
        if ($translated === false || $translated === null) {
            //translation does not exist
            return false;
        }
        return true;
    }

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Generate sitemap page data
     *
     * @param string  $linkUrl      Link of current url
     * @param integer $linkPageUid  Link target page id
     * @param array   $rootline     Rootline of link
     * @param integer $pageLanguage Language id
     * @param integer $linkType     Link type
     *
     * @return array
     * @internal param string $pageUrl Page url
     *
     */
    protected function generateSitemapPageData($linkUrl, $linkPageUid, array $rootline, $pageLanguage, $linkType)
    {
        $tstamp = $_SERVER['REQUEST_TIME'];

        $rootPid = $rootline[0]['uid'];

        // Get page data from rootline
        $page = reset($rootline);

        $ret = array(
            'tstamp'                => $tstamp,
            'crdate'                => $tstamp,
            'page_rootpid'          => $rootPid,
            'page_uid'              => $linkPageUid,
            'page_language'         => $pageLanguage,
            'page_url'              => $this->getPageUrl($linkUrl),
            'page_depth'            => count($rootline),
            'page_change_frequency' => $this->getPageChangeFrequency($page),
            'page_type'             => $linkType,
            'expire'                => $this->indexExpiration,
        );

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'sitemapIndexLink', $this, $ret);

        return $ret;
    }

    /**
     * Parse uid and type from generated link (from config array)
     *
     * @param  array $conf Generated Link config array
     *
     * @return array
     */
    protected function parseLinkConf(array $conf)
    {
        $uid  = null;
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
                    $uid  = GeneralUtility::getRootPid();
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
    protected function checkIfFileIsWhitelisted($url)
    {
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
     * Get current page url
     *
     * @param string $linkUrl Link url
     *
     * @return null|string
     */
    protected function getPageUrl($linkUrl)
    {
        $linkParts = parse_url($linkUrl);

        // Remove left / (but only if not root page)
        if ($linkParts['path'] === '/') {
            // Link points to root page
            $ret = '/';
        } else {
            // Link points to another page, strip left /
            $ret = ltrim($linkParts['path'], '/');
        }

        // Add query
        if (!empty($linkParts['query'])) {
            $ret .= '?' . $linkParts['query'];
        }

        return $ret;
    }
}
