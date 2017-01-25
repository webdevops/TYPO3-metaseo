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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Sitemap Indexer
 */
abstract class SitemapIndexHook implements SingletonInterface
{

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * List of blacklisted page doktypes
     *
     * @var array
     */
    protected $doktypeBlacklist = array();

    /**
     * List of blacklisted page types (Setup PAGE object typeNum)
     *
     * @var array
     */
    protected $pageTypeBlacklist = array();

    /**
     * Page index status
     *
     * @var null|boolean
     */
    protected $pageIndexFlag;

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


    /**
     * Object manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;


    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Constructor
     */
    public function __construct()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $this->objectManager = Typo3GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
        );

        $this->initConfiguration();
    }

    /**
     * Init configuration
     */
    protected function initConfiguration()
    {
        $tsfe = self::getTsfe();

        // Get configuration
        if (!empty($tsfe->tmpl->setup['plugin.']['metaseo.'])) {
            $this->conf = $tsfe->tmpl->setup['plugin.']['metaseo.'];
        }

        // Store blacklist configuration
        if (!empty($this->conf['sitemap.']['index.']['blacklist.'])) {
            $this->blacklistConf = $this->conf['sitemap.']['index.']['blacklist.'];
        }

        // Store blacklist configuration
        if (!empty($this->conf['sitemap.']['index.']['fileExtension.'])) {
            # File extensions can be a comma separated list
            foreach ($this->conf['sitemap.']['index.']['fileExtension.'] as $fileExtListRaw) {
                $fileExtList       = Typo3GeneralUtility::trimExplode(',', $fileExtListRaw);
                $this->fileExtList = array_merge($this->fileExtList, $fileExtList);
            };
        }

        // Get expiration
        $expirationInDays = 60;
        if (!empty($this->conf['sitemap.']['expiration'])) {
            $expirationInDays = abs($this->conf['sitemap.']['expiration']);
        }

        $this->indexExpiration = $_SERVER['REQUEST_TIME'] + ($expirationInDays * 24 * 60 * 60);

        // Init blacklist for doktype (from table pages)
        $this->doktypeBlacklist = SitemapUtility::getDoktypeBlacklist();

        // Init blacklist for PAGE typenum
        $this->pageTypeBlacklist = SitemapUtility::getPageTypeBlacklist();
    }

    /**
     * Process/Clear link url
     *
     * @param   string $linkUrl Link url
     *
     * @return  string
     */
    protected static function processLinkUrl($linkUrl)
    {
        static $absRefPrefix = null;
        static $absRefPrefixLength = 0;
        $ret = $linkUrl;
        $tsfe = self::getTsfe();


        // Fetch abs ref prefix if available/set
        if ($absRefPrefix === null) {
            if (!empty($tsfe->tmpl->setup['config.']['absRefPrefix'])) {
                $absRefPrefix       = $tsfe->tmpl->setup['config.']['absRefPrefix'];
                $absRefPrefixLength = strlen($absRefPrefix);
            } else {
                $absRefPrefix = false;
            }
        }

        // remove abs ref prefix
        if ($absRefPrefix !== false && strpos($ret, $absRefPrefix) === 0) {
            $parsedUrl = parse_url($linkUrl);
            if ($parsedUrl !== false
                && $parsedUrl['path'] === $absRefPrefix
                && substr($absRefPrefix, -1) === '/'  //sanity check: must end with /
            ) {
                //for root pages: treat '/' like a suffix, not like a prefix => don't remove last '/' in that case!
                //This ensures that for an absRefPrefix = '/abc/' or '/' we return '/' instead of empty strings
                $absRefPrefixLength--;
            }

            $ret = substr($ret, $absRefPrefixLength);
        }

        return $ret;
    }

    /**
     * Return page change frequency
     *
     * @param array $page Page data
     *
     * @return integer
     */
    protected function getPageChangeFrequency(array $page)
    {
        $ret = 0;

        if (!empty($page['tx_metaseo_change_frequency'])) {
            $ret = (int)$page['tx_metaseo_change_frequency'];
        } elseif (!empty($this->conf['sitemap.']['changeFrequency'])) {
            $ret = (int)$this->conf['sitemap.']['changeFrequency'];
        }

        if (empty($pageChangeFrequency)) {
            $ret = 0;
        }

        return $ret;
    }

    /**
     * Check if sitemap indexing is enabled
     *
     * @param string $indexingType Indexing type (page or typolink)
     *
     * @return bool
     */
    protected function checkIfSitemapIndexingIsEnabled($indexingType)
    {
        // check if sitemap is enabled in root
        if (!GeneralUtility::getRootSettingValue('is_sitemap', true)
            || !GeneralUtility::getRootSettingValue('is_sitemap_' . $indexingType . '_indexer', true)
        ) {
            return false;
        }

        // check current page
        if (!$this->checkIfCurrentPageIsIndexable()) {
            return false;
        }

        return true;
    }

    /**
     * Check if current page is indexable
     *
     * Will do following checks:
     * - REQUEST_METHOD (must be GET)
     * - If there is a feuser session
     * - Page type blacklisting
     * - Exclusion from search engines
     * - If page is static cacheable
     * - If no_cache is not set
     *
     * (checks will be cached)
     *
     * @return bool
     */
    protected function checkIfCurrentPageIsIndexable()
    {
        // check caching status
        if ($this->pageIndexFlag !== null) {
            return $this->pageIndexFlag;
        }

        // by default page is not cacheable
        $this->pageIndexFlag = false;

        // ############################
        // Basic checks
        // ############################

        $cacheConf = array(
            'allowNoStaticCachable' => (bool)$this->conf['sitemap.']['index.']['allowNoStaticCachable'],
            'allowNoCache'          => (bool)$this->conf['sitemap.']['index.']['allowNoCache']
        );


        if (!FrontendUtility::isCacheable($cacheConf)) {
            return false;
        }

        $tsfe = self::getTsfe();

        // Check for type blacklisting (from typoscript PAGE object)
        if (in_array($tsfe->type, $this->pageTypeBlacklist)) {
            return false;
        }

        // Check if page is excluded from search engines
        if (!empty($tsfe->page['tx_metaseo_is_exclude'])) {
            return false;
        }

        // Check for doktype blacklisting (from current page record)
        if (in_array((int)$tsfe->page['doktype'], $this->doktypeBlacklist)) {
            return false;
        }

        // all checks successful, page is cacheable
        $this->pageIndexFlag = true;

        return true;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected static function getTsfe()
    {
        return $GLOBALS['TSFE'];
    }
}
