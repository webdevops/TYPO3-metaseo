<?php
namespace Metaseo\Metaseo\Page\Part;

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
 * Metatags generator
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: MetatagPart.php 84520 2014-03-28 10:33:24Z mblaschke $
 */
class MetatagPart {

    /**
     * List of stdWrap manipulations
     * @var array
     */
    protected $_stdWrapList = array();

    /**
     * Add MetaTags
     *
     * @return    string            XHTML Code with metatags
     */
    public function main() {
        // INIT
        $ret      = array();

        /** @var array $tsSetup */
        $tsSetup  = $GLOBALS['TSFE']->tmpl->setup;
        $cObj     = $GLOBALS['TSFE']->cObj;
        $pageMeta = array();

        /** @var array $tsfePage */
        $tsfePage = $GLOBALS['TSFE']->page;

        $sysLanguageId = 0;
        if( !empty($tsSetup['config.']['sys_language_uid']) ) {
            $sysLanguageId = $tsSetup['config.']['sys_language_uid'];
        }

        $customMetaTagList = array();
        $enableMetaDc      = TRUE;

        // Init News extension
        $this->_initExtensionSupport();

        if (!empty($tsSetup['plugin.']['metaseo.']['metaTags.'])) {
            $tsSetupSeo = $tsSetup['plugin.']['metaseo.']['metaTags.'];

            // get stdwrap list
            if (!empty($tsSetupSeo['stdWrap.'])) {
                $this->_stdWrapList = $tsSetupSeo['stdWrap.'];
            }

            if (empty($tsSetupSeo['enableDC'])) {
                $enableMetaDc = FALSE;
            }

            // #####################################
            // FETCH METADATA FROM PAGE
            // #####################################

            // #################
            // Page meta
            // #################

            // description
            $tmp = $cObj->stdWrap($tsSetupSeo['conf.']['description_page'], $tsSetupSeo['conf.']['description_page.']);
            if (!empty($tmp)) {
                $pageMeta['description'] = $tmp;
            }

            // keywords
            $tmp = $cObj->stdWrap($tsSetupSeo['conf.']['keywords_page'], $tsSetupSeo['conf.']['keywords_page.']);
            if (!empty($tmp)) {
                $pageMeta['keywords'] = $tmp;
            }

            // title
            $tmp = $cObj->stdWrap($tsSetupSeo['conf.']['title_page'], $tsSetupSeo['conf.']['title_page.']);
            if (!empty($tmp)) {
                $pageMeta['title'] = $tmp;
            }

            // author
            $tmp = $cObj->stdWrap($tsSetupSeo['conf.']['author_page'], $tsSetupSeo['conf.']['author_page.']);
            if (!empty($tmp)) {
                $pageMeta['author'] = $tmp;
            }

            // email
            $tmp = $cObj->stdWrap($tsSetupSeo['conf.']['email_page'], $tsSetupSeo['conf.']['email_page.']);
            if (!empty($tmp)) {
                $pageMeta['email'] = $tmp;
            }

            // last-update
            $tmp = $cObj->stdWrap($tsSetupSeo['conf.']['lastUpdate_page'], $tsSetupSeo['conf.']['lastUpdate_page.']);
            if (!empty($tmp)) {
                $pageMeta['lastUpdate'] = $tmp;
            }

            // #################
            // Geo
            // #################

            // tx_metaseo_geo_lat
            $tmp = $cObj->stdWrap($tsSetupSeo['conf.']['tx_metaseo_geo_lat'], $tsSetupSeo['conf.']['tx_metaseo_geo_lat.']);
            if (!empty($tmp)) {
                $pageMeta['geoPositionLatitude'] = $tmp;
            }

            // tx_metaseo_geo_long
            $tmp = $cObj->stdWrap(
                $tsSetupSeo['conf.']['tx_metaseo_geo_long'],
                $tsSetupSeo['conf.']['tx_metaseo_geo_long.']
            );
            if (!empty($tmp)) {
                $pageMeta['geoPositionLongitude'] = $tmp;
            }

            // tx_metaseo_geo_place
            $tmp = $cObj->stdWrap(
                $tsSetupSeo['conf.']['tx_metaseo_geo_place'],
                $tsSetupSeo['conf.']['tx_metaseo_geo_place.']
            );
            if (!empty($tmp)) {
                $pageMeta['geoPlacename'] = $tmp;
            }

            // tx_metaseo_geo_region
            $tmp = $cObj->stdWrap(
                $tsSetupSeo['conf.']['tx_metaseo_geo_region'],
                $tsSetupSeo['conf.']['tx_metaseo_geo_region.']
            );
            if (!empty($tmp)) {
                $pageMeta['geoRegion'] = $tmp;
            }

            // #################
            // Misc
            // #################

            // language
            if (!empty($tsSetupSeo['useDetectLanguage'])
                && !empty($tsSetup['config.']['language'])
            ) {
                $pageMeta['language'] = $tsSetup['config.']['language'];
            }

            // #################
            // Process meta tags
            // #################

            // process page meta data
            foreach ($pageMeta as $metaKey => $metaValue) {
                $metaValue = trim($metaValue);

                if (!empty($metaValue)) {
                    $tsSetupSeo[$metaKey] = $metaValue;
                }
            }

            // #################
            // Process meta tags from access point
            // #################

            /** @var \Metaseo\Metaseo\Connector $connector */
            $connector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Metaseo\\Metaseo\\Connector');
            $storeMeta = $connector->getStore();

            // Std meta tags
            foreach ($storeMeta['meta'] as $metaKey => $metaValue) {
                $metaValue = trim($metaValue);

                if ($metaValue === NULL) {
                    // Remove meta
                    unset($tsSetupSeo[$metaKey]);
                } elseif (!empty($metaValue)) {
                    $tsSetupSeo[$metaKey] = $metaValue;
                }
            }

            // Custom meta tags
            foreach ($storeMeta['custom'] as $metaKey => $metaValue) {
                $metaValue = trim($metaValue);

                if ($metaValue === NULL) {
                    // Remove meta
                    unset($customMetaTagList[$metaKey]);
                } elseif (!empty($metaValue)) {
                    $customMetaTagList[$metaKey] = $metaValue;
                }
            }

            // #####################################
            // Process StdWrap List
            // #####################################
            $stdWrapItemList = array(
                'title',
                'description',
                'keywords',
                'copyright',
                'language',
                'email',
                'author',
                'publisher',
                'distribution',
                'rating',
                'lastUpdate',
            );
            foreach ($stdWrapItemList as $key) {
                $tsSetupSeo[$key] = $this->_applyStdWrap($key, $tsSetupSeo[$key]);
            }

            // Call hook
            \Metaseo\Metaseo\Utility\GeneralUtility::callHook('metatag-setup', $this, $tsSetupSeo);

            // #####################################
            // Generate MetaTags
            // #####################################

            // title
            if (!empty($tsSetupSeo['title']) && $enableMetaDc) {
                $ret['meta.title'] = '<meta name="DC.title" content="' . htmlspecialchars($tsSetupSeo['title']) . '">';
            }

            // description
            if (!empty($tsSetupSeo['description'])) {
                $ret['meta.description'] = '<meta name="description" content="' . htmlspecialchars($tsSetupSeo['description']) . '">';

                if ($enableMetaDc) {
                    $ret['meta.description.dc'] = '<meta name="DC.Description" content="' . htmlspecialchars($tsSetupSeo['description']) . '">';
                }
            }

            // keywords
            if (!empty($tsSetupSeo['keywords'])) {
                $ret['meta.keywords'] = '<meta name="keywords" content="' . htmlspecialchars($tsSetupSeo['keywords']) . '">';

                if ($enableMetaDc) {
                    $ret['meta.keywords.dc'] = '<meta name="DC.Subject" content="' . htmlspecialchars($tsSetupSeo['keywords']) . '">';
                }
            }

            // copyright
            if (!empty($tsSetupSeo['copyright'])) {
                $ret['meta.copyright'] = '<meta name="copyright" content="' . htmlspecialchars($tsSetupSeo['copyright']) . '">';

                if ($enableMetaDc) {
                    $ret['meta.copyright.dc'] = '<meta name="DC.Rights" content="' . htmlspecialchars($tsSetupSeo['copyright']) . '">';
                }
            }

            // language
            if (!empty($tsSetupSeo['language'])) {
                $ret['meta.language'] = '<meta http-equiv="content-language" content="' . htmlspecialchars($tsSetupSeo['language']) . '">';

                if ($enableMetaDc) {
                    $ret['meta.language.dc'] = '<meta name="DC.Language" scheme="NISOZ39.50" content="' . htmlspecialchars($tsSetupSeo['language']) . '">';
                }
            }

            // email
            if (!empty($tsSetupSeo['email'])) {
                $ret['meta.email.link'] = '<link rev="made" href="mailto:' . htmlspecialchars($tsSetupSeo['email']) . '">';
                $ret['meta.email.http'] = '<meta http-equiv="reply-to" content="' . htmlspecialchars($tsSetupSeo['email']) . '">';
            }

            // author
            if (!empty($tsSetupSeo['author'])) {
                $ret['meta.author'] = '<meta name="author" content="' . htmlspecialchars($tsSetupSeo['author']) . '">';

                if ($enableMetaDc) {
                    $ret['meta.author.dc'] = '<meta name="DC.Creator" content="' . htmlspecialchars($tsSetupSeo['author']) . '">';
                }
            }

            // author
            if (!empty($tsSetupSeo['publisher']) && $enableMetaDc) {
                $ret['meta.publisher.dc'] = '<meta name="DC.Publisher" content="' . htmlspecialchars($tsSetupSeo['publisher']) . '">';
            }

            // distribution
            if (!empty($tsSetupSeo['distribution'])) {
                $ret['meta.distribution'] = '<meta name="distribution" content="' . htmlspecialchars($tsSetupSeo['distribution']) . '">';
            }

            // rating
            if (!empty($tsSetupSeo['rating'])) {
                $ret['meta.rating'] = '<meta name="rating" content="' . htmlspecialchars($tsSetupSeo['rating']) . '">';
            }

            // last-update
            if (!empty($tsSetupSeo['useLastUpdate']) && !empty($tsSetupSeo['lastUpdate'])) {
                $ret['meta.date'] = '<meta name="date" content="' . htmlspecialchars($tsSetupSeo['lastUpdate']) . '">';

                if ($enableMetaDc) {
                    $ret['meta.date.dc'] = '<meta name="DC.date" content="' . htmlspecialchars($tsSetupSeo['lastUpdate']) . '">';
                }
            }

            // expire
            if (!empty($tsSetupSeo['useExpire']) && !empty($tsfePage['endtime'])) {
                $ret['meta.expire'] = '<meta name="googlebot" content="unavailable_after: ' . date('d-M-Y H:i:s T',$tsfePage['endtime']) . '" > ';
            }

            // #################
            // CRAWLER ORDERS
            // #################

            // robots
            if( !empty($tsSetupSeo['robotsEnable']) ) {
                $crawlerOrder = array();

                if (!empty($tsSetupSeo['robotsIndex']) && empty($tsfePage['tx_metaseo_is_exclude'])) {
                    $crawlerOrder['index'] = 'index';
                } else {
                    $crawlerOrder['index'] = 'noindex';
                }

                if (!empty($tsSetupSeo['robotsFollow'])) {
                    $crawlerOrder['follow'] = 'follow';
                } else {
                    $crawlerOrder['follow'] = 'nofollow';
                }

                if (empty($tsSetupSeo['robotsArchive'])) {
                    $crawlerOrder['archive'] = 'noarchive';
                }

                if (empty($tsSetupSeo['robotsSnippet'])) {
                    $crawlerOrder['snippet'] = 'nosnippet';
                }

                if (!empty($tsSetupSeo['robotsNoImageindex']) && $tsSetupSeo['robotsNoImageindex'] === '1') {
                    $crawlerOrder['noimageindex'] = 'noimageindex';
                }

                if (!empty($tsSetupSeo['robotsNoTranslate']) && $tsSetupSeo['robotsNoTranslate'] === '1') {
                    $crawlerOrder['notranslate'] = 'notranslate';
                }

                if (empty($tsSetupSeo['robotsOdp'])) {
                    $crawlerOrder['odp'] = 'noodp';
                }

                if (empty($tsSetupSeo['robotsYdir'])) {
                    $crawlerOrder['ydir'] = 'noydir';
                }

                $ret['crawler.robots'] = '<meta name="robots" content="' . implode(',', $crawlerOrder) . '">';
            }

            // revisit
            if (!empty($tsSetupSeo['revisit'])) {
                $ret['crawler.revisit'] = '<meta name="revisit-after" content="' . htmlspecialchars($tsSetupSeo['revisit']) . '">';
            }

            // #################
            // GEO POSITION
            // #################

            // Geo-Position
            if (!empty($tsSetupSeo['geoPositionLatitude']) && !empty($tsSetupSeo['geoPositionLongitude'])) {
                $ret['geo.icmb']     = '<meta name="ICBM" content="' . htmlspecialchars($tsSetupSeo['geoPositionLatitude']) . ', ' . htmlspecialchars($tsSetupSeo['geoPositionLongitude']) . '">';
                $ret['geo.position'] = '<meta name="geo.position" content="' . htmlspecialchars($tsSetupSeo['geoPositionLatitude']) . ';' . htmlspecialchars($tsSetupSeo['geoPositionLongitude']) . '">';
            }

            // Geo-Region
            if (!empty($tsSetupSeo['geoRegion'])) {
                $ret['geo.region'] = '<meta name="geo.region" content="' . htmlspecialchars($tsSetupSeo['geoRegion']) . '">';
            }

            // Geo Placename
            if (!empty($tsSetupSeo['geoPlacename'])) {
                $ret['geo.placename'] = '<meta name="geo.placename" content="' . htmlspecialchars($tsSetupSeo['geoPlacename']) . '">';
            }

            // #################
            // MISC (Vendor specific)
            // #################

            // Google Verification
            if (!empty($tsSetupSeo['googleVerification'])) {
                $ret['service.verification.google'] = '<meta name="google-site-verification" content="' . htmlspecialchars($tsSetupSeo['googleVerification']) . '">';
            }

            // MSN Verification
            if (!empty($tsSetupSeo['msnVerification'])) {
                $ret['service.verification.msn'] = '<meta name="msvalidate.01" content="' . htmlspecialchars($tsSetupSeo['msnVerification']) . '">';
            }

            // Yahoo Verification
            if (!empty($tsSetupSeo['yahooVerification'])) {
                $ret['service.verification.yahoo'] = '<meta name="y_key" content="' . htmlspecialchars($tsSetupSeo['yahooVerification']) . '">';
            }

            // WebOfTrust Verification
            if (!empty($tsSetupSeo['wotVerification'])) {
                $ret['service.verification.wot'] = '<meta name="wot-verification" content="' . htmlspecialchars($tsSetupSeo['wotVerification']) . '">';
            }


            // PICS label
            if (!empty($tsSetupSeo['picsLabel'])) {
                $ret['service.pics'] = '<meta http-equiv="PICS-Label" content="' . htmlspecialchars($tsSetupSeo['picsLabel']) . '">';
            }

            // #################
            // UserAgent
            // #################

            // IE compatibility mode
            if (!empty($tsSetupSeo['ieCompatibilityMode'])) {
                if (is_numeric($tsSetupSeo['ieCompatibilityMode'])) {
                    $ret['ua.msie.compat'] = '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE' . (int)$tsSetupSeo['ieCompatibilityMode'] . '">';
                } else {
                    $ret['ua.msie.compat'] = '<meta http-equiv="X-UA-Compatible" content="' . htmlspecialchars($tsSetupSeo['ieCompatibilityMode']) . '">';
                }
            }

            // #################
            // Link-Tags
            // #################
            if (!empty($tsSetupSeo['linkGeneration'])) {
                $rootLine = $GLOBALS['TSFE']->rootLine;
                ksort($rootLine);

                $currentPage = end($rootLine);

                $rootPage    = reset($rootLine);
                $rootPageUrl = NULL;
                if (!empty($rootPage)) {
                    $rootPageUrl = $this->_generateLink($rootPage['uid']);
                }

                $upPage    = $currentPage['pid'];
                $upPageUrl = NULL;
                if (!empty($upPage)) {
                    $upPage    = $this->_getRelevantUpPagePid($upPage);
                    $upPageUrl = $this->_generateLink($upPage);
                }

                $prevPage    = $GLOBALS['TSFE']->cObj->HMENU($tsSetupSeo['sectionLinks.']['prev.']);
                $prevPageUrl = NULL;
                if (!empty($prevPage)) {
                    $prevPageUrl = $this->_generateLink($prevPage);
                }

                $nextPage    = $GLOBALS['TSFE']->cObj->HMENU($tsSetupSeo['sectionLinks.']['next.']);
                $nextPageUrl = NULL;
                if (!empty($nextPage)) {
                    $nextPageUrl = $this->_generateLink($nextPage);
                }

                // Root (First page in rootline)
                if (!empty($rootPageUrl)) {
                    $ret['link.rel.start'] = '<link rel="start" href="' . htmlspecialchars($rootPageUrl) . '">';
                }

                // Up (One page up in rootline)
                if (!empty($upPageUrl)) {
                    $ret['link.rel.up'] = '<link rel="up" href="' . htmlspecialchars($upPageUrl) . '">';
                }

                // Next (Next page in rootline)
                if (!empty($nextPageUrl)) {
                    $ret['link.rel.next'] = '<link rel="next" href="' . htmlspecialchars($nextPageUrl) . '">';
                }

                // Prev (Previous page in rootline)
                if (!empty($prevPageUrl)) {
                    $ret['link.rel.prev'] = '<link rel="prev" href="' . htmlspecialchars($prevPageUrl) . '">';
                }
            }

            // Canonical URL
            $canonicalUrl = NULL;

            if (!empty($tsfePage['tx_metaseo_canonicalurl'])) {
                $canonicalUrl = $tsfePage['tx_metaseo_canonicalurl'];
            } elseif (!empty($tsSetupSeo['useCanonical'])) {
                $strictMode   = (bool)(int)$tsSetupSeo['useCanonical.']['strict'];
                $canonicalUrl = $this->_detectCanonicalPage($strictMode);
            }

            if (!empty($canonicalUrl)) {
                $canonicalUrl = $this->_generateLink($canonicalUrl);

                if (!empty($canonicalUrl)) {
                    $ret['link.rel.canonical'] = '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl) . '">';
                }
            }

            // #################
            // Advanced meta tags
            // #################
            $this->_advMetaTags($ret, $tsfePage, $sysLanguageId, $customMetaTagList);
        }

        // #################
        // SOCIAL
        // #################
        if (!empty($tsSetup['plugin.']['metaseo.']['social.'])) {
            $tsSetupSeo = $tsSetup['plugin.']['metaseo.']['social.'];

            if (!empty($tsSetupSeo['googlePlus.']['profilePageId'])) {
                $ret['social.googleplus.direct-connect'] = '<link href="https://plus.google.com/' . htmlspecialchars($tsSetupSeo['googlePlus.']['profilePageId']) . '" rel="publisher">';
            }
        }

        $this->_processMetaTags($ret);

        $separator = "\n";
        return $separator . implode($separator, $ret) . $separator;
    }


    /**
     * Advanced meta tags
     *
     * @param array   $metaTags          MetaTags
     * @param array   $tsfePage          TSFE Page
     * @param integer $sysLanguageId     Sys Language ID
     * @param array   $customMetaTagList Custom Meta Tag list
     */
    protected function _advMetaTags(&$metaTags, $tsfePage, $sysLanguageId, $customMetaTagList) {
        $tsfePageId    = $tsfePage['uid'];

        $connector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Metaseo\\Metaseo\\Connector');
        $storeMeta = $connector->getStore();

        // #################
        // Adv meta tags (from editor)
        // #################
        $advMetaTagList      = array();
        $advMetaTagCondition = array();

        if( !empty($storeMeta['flag']['meta:og:external']) ) {
            // External OpenGraph support
            $advMetaTagCondition[] = 'tag_name NOT LIKE \'og:%\'';

            // Add external og-tags to adv meta tag list
            if( !empty($storeMeta['meta:og']) ) {
                $advMetaTagList = array_merge($advMetaTagList, $storeMeta['meta:og']);
            }
        }

        if( !empty($advMetaTagCondition) ) {
            $advMetaTagCondition = '( '.implode(') AND (', $advMetaTagCondition).' )';

        } else {
            $advMetaTagCondition = '1=1';
        }

        // Fetch list of meta tags from database
        $query = 'SELECT tag_name, tag_value
                    FROM tx_metaseo_metatag
                   WHERE pid = '.(int)$tsfePageId.'
                     AND sys_language_uid = '.(int)$sysLanguageId.'
                     AND '.$advMetaTagCondition;
        $advMetaTagList = DatabaseUtility::getList($query);

        // Add metadata to tag list
        foreach($advMetaTagList as $tagName => $tagValue) {
            $metaTags['adv.' . $tagName] = '<meta name="' . htmlspecialchars($tagName) . '" content="' . htmlspecialchars($tagValue) . '">';
        }

        // #################
        // Custom meta tags (from connector)
        // #################
        foreach ($customMetaTagList as $tagName => $tagValue) {
            $ret['adv.' . $tagName] = '<meta name="' . htmlspecialchars($tagName) . '" content="' . htmlspecialchars($tagValue) . '">';
        }
    }


    /**
     * Init extension support
     */
    protected function _initExtensionSupport() {

        // Extension: news
        if( \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news') ) {
            $this->_initExtensionSupportNews();
        }

    }


    /**
     * Init extension support for "news" extension
     */
    protected function _initExtensionSupportNews() {
        if( empty($GLOBALS['TSFE']->register) ) {
            return;
        }

        /** @var \Metaseo\Metaseo\Connector $connector */
        $connector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Metaseo\\Metaseo\\Connector');

        if( isset($GLOBALS['TSFE']->register['newsTitle']) ) {
            $connector->setMetaTag('title', $GLOBALS['TSFE']->register['newsTitle']);
        }

        if( isset($GLOBALS['TSFE']->register['newsAuthor']) ) {
            $connector->setMetaTag('author', $GLOBALS['TSFE']->register['newsAuthor']);
        }

        if( isset($GLOBALS['TSFE']->register['newsAuthoremail']) ) {
            $connector->setMetaTag('email', $GLOBALS['TSFE']->register['newsAuthoremail']);
        }

        if( isset($GLOBALS['TSFE']->register['newsAuthorEmail']) ) {
            $connector->setMetaTag('email', $GLOBALS['TSFE']->register['newsAuthorEmail']);
        }

        if( isset($GLOBALS['TSFE']->register['newsKeywords']) ) {
            $connector->setMetaTag('keywords', $GLOBALS['TSFE']->register['newsKeywords']);
        }

        if( isset($GLOBALS['TSFE']->register['newsTeaser']) ) {
            $connector->setMetaTag('description', $GLOBALS['TSFE']->register['newsTeaser']);
        }
    }


    /**
     * Generate a link via TYPO3-Api
     *
     * @param    integer|string $url    URL (id or string)
     * @param    array|NULL     $conf   URL configuration
     * @return   string                 URL
     */
    protected function _generateLink($url, $conf = NULL) {
        if ($conf === NULL) {
            $conf = array();
        }

        $conf['parameter'] = $url;

        $ret = $GLOBALS['TSFE']->cObj->typoLink_URL($conf);
        // maybe baseUrlWrap is better? but breaks with realurl currently?
        $ret = \Metaseo\Metaseo\Utility\GeneralUtility::fullUrl($ret);

        return $ret;
    }

    /**
     * Get relevant up page pid
     *
     * @param   int $uid    Page ID
     * @return  int
     */
    protected function _getRelevantUpPagePid($uid){
        /** @var \TYPO3\CMS\Frontend\Page\PageRepository  $sysPageObj */
        $sysPageObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Frontend\\Page\\PageRepository'
        );

        $page = $sysPageObj->getPage_noCheck($uid);

        if ($page['nav_hide'] === '1') {
          $uid = $page['pid'];
          $page =  $sysPageObj->getPage_noCheck($uid);
            if ($page['nav_hide'] === '1') {
               $uid = $this->_getRelevantUpPagePid($uid);
            }
        }

        return $uid;
    }

    /**
     * Detect canonical page
     *
     * @param    boolean $strictMode        Enable strict mode
     * @return   string                     Page Id or url
     */
    protected function _detectCanonicalPage($strictMode = FALSE) {
        // Skip no_cache-pages
        if (!empty($GLOBALS['TSFE']->no_cache)) {
            if ($strictMode) {
                // force canonical-url to page url (without any parameters)
                return $GLOBALS['TSFE']->id;
            } else {
                return NULL;
            }
        }

        // Fetch chash
        $pageHash = NULL;
        if (!empty($GLOBALS['TSFE']->cHash)) {
            $pageHash = $GLOBALS['TSFE']->cHash;
        }

        if (!empty($this->cObj->data['content_from_pid'])) {
            // ###############################
            // Content from pid
            // ###############################
            $ret = $this->cObj->data['content_from_pid'];
        } else {
            // Fetch pageUrl
            if ($pageHash !== NULL) {
                // Virtual plugin page, we have to use achnor or site script
                if (!empty($GLOBALS['TSFE']->anchorPrefix)) {
                    $ret = $GLOBALS['TSFE']->anchorPrefix;
                } else {
                    $ret = $GLOBALS['TSFE']->siteScript;
                }
            } else {
                $ret = $GLOBALS['TSFE']->id;
            }
        }

        // Fallback to main page if strict mode is active
        if ($strictMode && empty($ret)) {
            $ret = $GLOBALS['TSFE']->id;
        }

        return $ret;
    }

    /**
     * Process meta tags
     */
    protected function _processMetaTags(&$tags) {
        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('metatag-output', $this, $tags);

        // Add marker
        $markerList = array(
            '%YEAR%' => date('Y'),
        );

        $keyList = array(
            'meta.title',
            'meta.description',
            'meta.description.dc',
            'meta.keywords',
            'meta.keywords.dc',
            'meta.copyright',
            'meta.copyright.dc',
            'meta.publisher.dc',
        );

        foreach ($keyList as $key) {
            if (!empty($tags[$key])) {
                foreach ($markerList as $marker => $value) {
                    if (strpos($tags[$key], $marker)) {
                        $tags[$key] = str_replace($marker, $value, $tags[$key]);
                    }
                }
            }
        }

    }

    /**
     * Process stdWrap from stdWrap list
     *
     * @param    string $key    StdWrap-List key
     * @param    string $value  Value
     * @return   string
     */
    protected function _applyStdWrap($key, $value) {
        $key .= '.';

        if (empty($this->_stdWrapList[$key])) {
            return $value;
        }

        return $this->cObj->stdWrap($value, $this->_stdWrapList[$key]);
    }
}
