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

namespace Metaseo\Metaseo\Page\Part;

use Metaseo\Metaseo\Utility\DatabaseUtility;
use Metaseo\Metaseo\Utility\FrontendUtility;
use Metaseo\Metaseo\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Metatags generator
 */
class MetatagPart extends AbstractPart
{

    /**
     * List of stdWrap manipulations
     *
     * @var array
     */
    protected $stdWrapList = array();

    /**
     * Content object renderer
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * TypoScript Setup
     *
     * @var array
     */
    protected $tsSetup = array();

    /**
     * TypoScript Setup (subtree of plugin.metaseo)
     *
     * @var array|boolean
     */
    protected $tsSetupSeo = array();

    /**
     * Page meta data
     *
     * @var array
     */
    protected $pageMeta = array();

    /**
     * Page record
     *
     * @var array
     */
    protected $pageRecord = array();

    /**
     * Enable MetaTag DublinCore
     *
     * @var
     */
    protected $enableMetaDc = true;

    /**
     * Generated MetaTag list
     *
     * @var array
     */
    protected $metaTagList = array();

    /**
     * Initialize
     */
    protected function initialize()
    {
        $this->cObj       = $GLOBALS['TSFE']->cObj;
        if (ExtensionManagementUtility::isLoaded('fluidpages')) {
            // works around missing language overlay when fluidpages extension is used
            $this->cObj->start($GLOBALS['TSFE']->page, 'pages');
        }

        $this->tsSetup    = $GLOBALS['TSFE']->tmpl->setup;
        $this->pageRecord = $GLOBALS['TSFE']->page;
        $this->pageMeta   = array();

        $this->metaTagList = array();

        if (!empty($this->tsSetup['plugin.']['metaseo.']['metaTags.'])) {
            $this->tsSetupSeo = $this->tsSetup['plugin.']['metaseo.']['metaTags.'];

            // get stdwrap list
            if (!empty($this->tsSetupSeo['stdWrap.'])) {
                $this->stdWrapList = $this->tsSetupSeo['stdWrap.'];
            }

            if (empty($this->tsSetupSeo['enableDC'])) {
                $this->enableMetaDc = false;
            }
        } else {
            $this->tsSetupSeo = false;
        }
    }

    /**
     * Add MetaTags
     *
     * @return    string            XHTML Code with metatags
     */
    public function main()
    {
        // INIT
        $this->metaTagList = array();

        $this->initialize();

        $sysLanguageId = 0;
        if (!empty($this->tsSetup['config.']['sys_language_uid'])) {
            $sysLanguageId = $this->tsSetup['config.']['sys_language_uid'];
        }

        // Init News extension
        $this->initExtensionSupport();

        if ($this->tsSetupSeo) {
            $this->collectMetaDataFromPage();
            $customMetaTagList = $this->collectMetaDataFromConnector();

            // #####################################
            // Blacklists
            // #####################################

            // Check search engine indexing blacklist
            if (!empty($this->tsSetupSeo['robotsIndex.']['blacklist.'])) {
                // Page is blacklisted, set to noindex
                $this->tsSetupSeo['robotsIndex'] = 0;
            }

            // #####################################
            // Process StdWrap List
            // #####################################
            $stdWrapItemList = array(
                'title',
                'description',
                'keywords',
                'copyright',
                'email',
                'author',
                'publisher',
                'distribution',
                'rating',
                'lastUpdate',
            );
            foreach ($stdWrapItemList as $key) {
                $this->tsSetupSeo[$key] = $this->applyStdWrap($key, $this->tsSetupSeo[$key]);
            }

            // Call hook
            GeneralUtility::callHookAndSignal(
                __CLASS__,
                'metatagSetup',
                $this,
                $this->tsSetupSeo
            );

            // standard metatags
            $this->generateStandardMetaTags();

            // crawler (eg. robots)
            $this->generateCrawlerMetaTags();

            // geo position
            $this->generateGeoPosMetaTags();

            // services (eg. google plus)
            $this->generateServicesMetaTags();

            // user agent stuff
            $this->generateUserAgentMetaTags();

            // Link meta tags (eg. prev, next...)
            if (!empty($this->tsSetupSeo['linkGeneration'])) {
                $this->generateLinkMetaTags();
            }

            // canonical url
            $this->generateCanonicalUrlMetaTags();

            // OpenGraph
            if (!empty($this->tsSetupSeo['opengraph']) && !empty($this->tsSetupSeo['opengraph.'])) {
                $this->generateOpenGraphMetaTags();
            }

            // Advanced meta tags
            $this->advMetaTags($this->metaTagList, $this->pageRecord, $sysLanguageId, $customMetaTagList);
        }

        // #################
        // SOCIAL
        // #################
        if (!empty($this->tsSetup['plugin.']['metaseo.']['social.'])) {
            $this->tsSetupSeo = $this->tsSetup['plugin.']['metaseo.']['social.'];

            if (!empty($this->tsSetupSeo['googlePlus.']['profilePageId'])) {
                $this->metaTagList['social.googleplus.direct-connect'] = array(
                    'tag'        => 'link',
                    'attributes' => array(
                        'rel'  => 'publisher',
                        'href' => 'https://plus.google.com/' . $this->tsSetupSeo['googlePlus.']['profilePageId'],
                    ),
                );
            }
        }

        $this->processMetaTags($this->metaTagList);
        //todo: type mismatch array->string for $this->metaTagList
        $this->metaTagList = $this->renderMetaTags($this->metaTagList);

        return $this->metaTagList;
    }

    /**
     * Init extension support
     */
    protected function initExtensionSupport()
    {
        // Extension: news
        if (ExtensionManagementUtility::isLoaded('news')) {
            $this->initExtensionSupportNews();
        }
    }

    /**
     * Init extension support for "news" extension
     */
    protected function initExtensionSupportNews()
    {
        if (empty($GLOBALS['TSFE']->register)) {
            return;
        }

        /** @var \Metaseo\Metaseo\Connector $connector */
        $connector = $this->objectManager->get('Metaseo\\Metaseo\\Connector');

        if (isset($GLOBALS['TSFE']->register['newsTitle'])) {
            $connector->setMetaTag('title', $GLOBALS['TSFE']->register['newsTitle']);
        }

        if (isset($GLOBALS['TSFE']->register['newsAuthor'])) {
            $connector->setMetaTag('author', $GLOBALS['TSFE']->register['newsAuthor']);
        }

        if (isset($GLOBALS['TSFE']->register['newsAuthoremail'])) {
            $connector->setMetaTag('email', $GLOBALS['TSFE']->register['newsAuthoremail']);
        }

        if (isset($GLOBALS['TSFE']->register['newsAuthorEmail'])) {
            $connector->setMetaTag('email', $GLOBALS['TSFE']->register['newsAuthorEmail']);
        }

        if (isset($GLOBALS['TSFE']->register['newsKeywords'])) {
            $connector->setMetaTag('keywords', $GLOBALS['TSFE']->register['newsKeywords']);
        }

        if (isset($GLOBALS['TSFE']->register['newsTeaser'])) {
            $connector->setMetaTag('description', $GLOBALS['TSFE']->register['newsTeaser']);
        }
    }

    /**
     * Process stdWrap from stdWrap list
     *
     * @param    string $key   StdWrap-List key
     * @param    string $value Value
     *
     * @return   string
     */
    protected function applyStdWrap($key, $value)
    {
        $key .= '.';

        if (empty($this->stdWrapList[$key])) {
            return $value;
        }

        return $this->cObj->stdWrap($value, $this->stdWrapList[$key]);
    }

    /**
     * Check if page is configured as HTML5
     *
     * @return bool
     */
    protected function isHtml5()
    {
        return ($GLOBALS['TSFE']->config['config']['doctype'] === 'html5');
    }

    /**
     * Check if page is configured as XHTML
     *
     * @return bool
     */
    protected function isXhtml()
    {
        $ret = false;

        if (strpos($GLOBALS['TSFE']->config['config']['doctype'], 'xhtml') !== false) {
            // doctype xhtml
            $ret = true;
        }

        if (strpos($GLOBALS['TSFE']->config['config']['xhtmlDoctype'], 'xhtml') !== false) {
            // doctype xhtml doctype
            $ret = true;
        }

        return $ret;
    }


    /**
     * Generate a link via TYPO3-Api
     *
     * @param    integer|string $url       URL (id or string)
     * @param    array|NULL     $conf      URL configuration
     * @param    boolean        $disableMP Disable mountpoint linking
     *
     * @return   string                      URL
     */
    protected function generateLink($url, array $conf = null, $disableMP = false)
    {
        if ($conf === null) {
            $conf = array();
        }

        $mpOldConfValue = $GLOBALS['TSFE']->config['config']['MP_disableTypolinkClosestMPvalue'];
        if ($disableMP === true) {
            // Disable MP usage in typolink - link to the real page instead
            $GLOBALS['TSFE']->config['config']['MP_disableTypolinkClosestMPvalue'] = 1;
        }

        $conf['parameter'] = $url;

        $ret = $this->cObj->typoLink_URL($conf);
        // maybe baseUrlWrap is better? but breaks with realurl currently?
        $ret = GeneralUtility::fullUrl($ret);

        if ($disableMP === true) {
            // Restore old MP linking configuration
            $GLOBALS['TSFE']->config['config']['MP_disableTypolinkClosestMPvalue'] = $mpOldConfValue;
        }

        return $ret;
    }

    /**
     * Detect canonical page
     *
     * @param    array $tsConfig TypoScript config setup
     *
     * @return   null|array of (linkParam, linkConf, linkMpMode)
     */
    protected function detectCanonicalPage(array $tsConfig = array())
    {
        #####################
        # Fetch typoscript config
        #####################
        $strictMode = (bool)(int)$tsConfig['strict'];
        $noMpMode   = (bool)(int)$tsConfig['noMP'];
        $linkConf   = !empty($tsConfig['typolink.']) ? $tsConfig['typolink.'] : array();
        $blacklist  = !empty($tsConfig['blacklist.']) ? $tsConfig['blacklist.'] : array();

        $linkParam  = null;
        $linkMpMode = false;

        // Init link configuration
        if ($linkConf === null) {
            $linkConf = array();
        }

        // Fetch chash
        $pageHash = null;
        if (!empty($GLOBALS['TSFE']->cHash)) {
            $pageHash = $GLOBALS['TSFE']->cHash;
        }

        #####################
        # Blacklisting
        #####################
        if (FrontendUtility::checkPageForBlacklist($blacklist)) {
            if ($strictMode) {
                if ($noMpMode && GeneralUtility::isMountpointInRootLine()) {
                    // Mountpoint detected
                    $linkParam = $GLOBALS['TSFE']->id;

                    // Force removing of MP param
                    $linkConf['addQueryString'] = 1;
                    if (!empty($linkConf['addQueryString.']['exclude'])) {
                        $linkConf['addQueryString.']['exclude'] .= ',id,MP,no_cache';
                    } else {
                        $linkConf['addQueryString.']['exclude'] = ',id,MP,no_cache';
                    }

                    // disable mount point linking
                    $linkMpMode = true;
                } else {
                    // force canonical-url to page url (without any parameters)
                    $linkParam = $GLOBALS['TSFE']->id;
                }
            } else {
                // Blacklisted and no strict mode, we don't output canonical tag
                return null;
            }
        }

        #####################
        # No cached pages
        #####################

        if (!empty($GLOBALS['TSFE']->no_cache)) {
            if ($strictMode) {
                // force canonical-url to page url (without any parameters)
                $linkParam = $GLOBALS['TSFE']->id;
            }
        }

        #####################
        # Content from PID
        #####################

        if (!$linkParam && !empty($this->cObj->data['content_from_pid'])) {
            $linkParam = $this->cObj->data['content_from_pid'];
        }

        #####################
        # Mountpoint
        #####################

        if (!$linkParam && $noMpMode && GeneralUtility::isMountpointInRootLine()) {
            // Mountpoint detected
            $linkParam = $GLOBALS['TSFE']->id;

            // Force removing of MP param
            $linkConf['addQueryString'] = 1;
            if (!empty($linkConf['addQueryString.']['exclude'])) {
                $linkConf['addQueryString.']['exclude'] .= ',id,MP,no_cache';
            } else {
                $linkConf['addQueryString.']['exclude'] = ',id,MP,no_cache';
            }

            // disable mount point linking
            $linkMpMode = true;
        }

        #####################
        # Normal page
        #####################

        if (!$linkParam) {
            // Fetch pageUrl
            if ($pageHash !== null) {
                // Virtual plugin page, we have to use anchor or site script
                $linkParam = FrontendUtility::getCurrentUrl();
            } else {
                $linkParam = $GLOBALS['TSFE']->id;
            }
        }

        #####################
        # Fallback
        #####################

        if ($strictMode && empty($linkParam)) {
            $linkParam = $GLOBALS['TSFE']->id;
        }

        return array($linkParam, $linkConf, $linkMpMode);
    }

    /**
     * Advanced meta tags
     *
     * @param array   $metaTags          MetaTags
     * @param array   $pageRecord        TSFE Page
     * @param integer $sysLanguageId     Sys Language ID
     * @param array   $customMetaTagList Custom Meta Tag list
     */
    protected function advMetaTags(array &$metaTags, array $pageRecord, $sysLanguageId, array $customMetaTagList)
    {
        $pageRecordId = $pageRecord['uid'];
        $storeMeta = $this->getStoreMeta();
        $advMetaTagCondition = array();

        // #################
        // External Og tags (from connector)
        // #################
        if ($this->isAvailableExternalOgTags()) {
            // External OpenGraph support
            $advMetaTagCondition[] = 'tag_name NOT LIKE \'og:%\'';
            if (!empty($storeMeta['meta:og'])) { //overwrite known og tags
                $externalOgTags = $storeMeta['meta:og'];
                foreach ($externalOgTags as $tagName => $tagValue) {
                    if (array_key_exists('og.' . $tagName, $metaTags)) { //_only_ known og tags
                        $metaTags['og.' . $tagName] = array(
                            'tag'        => 'meta',
                            'attributes' => array(
                                'property'  => 'og:' . $tagName,
                                'content'   => $tagValue,
                            ),
                        );
                    }
                }
            }
        }
        if ($this->isAvailableExternalOgCustomTags()) {
            $ogTagKeys = array();
            if (!empty($storeMeta['custom:og'])) {
                $externalCustomOgTags = $storeMeta['custom:og'];
                foreach ($externalCustomOgTags as $tagName => $tagValue) { //take all tags
                    $metaTags['og.' . $tagName] = array(
                        'tag'        => 'meta',
                        'attributes' => array(
                            'property'  => 'og:' . $tagName,
                            'content'   => $tagValue,
                        ),
                    );
                    $ogTagKeys[] = 'og:' . $tagName;
                }
            }
            $advMetaTagCondition[] = DatabaseUtility::conditionNotIn('tag_name', $ogTagKeys, true);
        }

        // #################
        // Adv meta tags (from editor)
        // #################

        if (!empty($advMetaTagCondition)) {
            $advMetaTagCondition = '( ' . implode(') AND (', $advMetaTagCondition) . ' )';
        } else {
            $advMetaTagCondition = '1=1';
        }

        // Fetch list of meta tags from database
        $query          = 'SELECT tag_name,
                                  tag_value
                             FROM tx_metaseo_metatag
                            WHERE pid = ' . (int)$pageRecordId . '
                              AND sys_language_uid = ' . (int)$sysLanguageId . '
                              AND ' . $advMetaTagCondition;
        $advMetaTagList = DatabaseUtility::getList($query);

        // Add metadata to tag list
        foreach ($advMetaTagList as $tagName => $tagValue) {
            if (substr($tagName, 0, 3) === 'og:') {
                $metaTags['og.' . $tagName] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'property'  => $tagName,
                        'content'   => $tagValue,
                    ),
                );
            } else {
                $metaTags['adv.' . $tagName] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'rel'  => $tagName,  //todo: rel and href might not be suitable in every case
                        'href' => $tagValue,
                    ),
                );
            }
        }

        // #################
        // Custom meta tags (from connector)
        // #################
        foreach ($customMetaTagList as $tagName => $tagValue) {
            $metaTags['adv.' . $tagName] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'rel'  => $tagName,  //todo: rel and href might not be suitable in every case
                    'href' => $tagValue,
                ),
            );
        }
    }

    /**
     * Process meta tags
     *
     * @param array $tags
     */
    protected function processMetaTags(array &$tags)
    {
        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'metatagOutput', $this, $tags);

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
            if (!empty($tags[$key]['attributes'])) {
                foreach ($markerList as $marker => $value) {
                    unset($metaTagAttribute);
                    foreach ($tags[$key]['attributes'] as &$metaTagAttribute) {
                        // only replace markers if they are present
                        if (strpos($metaTagAttribute, $marker) !== false) {
                            $metaTagAttribute = str_replace($marker, $value, $metaTagAttribute);
                        }
                    }
                    unset($metaTagAttribute);
                }
            }
        }
    }


    /**
     * Render meta tags
     *
     * @param array $metaTags List of metatags with configuration (tag, attributes)
     *
     * @return string
     */
    protected function renderMetaTags(array $metaTags)
    {
        $ret = array();

        $isXhtml = $this->isXhtml();

        foreach ($metaTags as $metaTag) {
            $tag = $metaTag['tag'];

            $attributes = array();

            foreach ($metaTag['attributes'] as $key => $value) {
                $attributes[] = $key . '="' . htmlspecialchars($value) . '"';
            }

            if ($isXhtml) {
                $ret[] = '<' . $tag . ' ' . implode(' ', $attributes) . '/>';
            } else {
                $ret[] = '<' . $tag . ' ' . implode(' ', $attributes) . '>';
            }
        }

        $separator = "\n";

        return $separator . implode($separator, $ret) . $separator;
    }

    /**
     * Collect meta data (eg. from page via stdwrap)
     */
    protected function collectMetaDataFromPage()
    {
        // #################
        // Page meta
        // #################

        // description
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['description_page'],
            $this->tsSetupSeo['conf.']['description_page.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['description'] = $tmp;
        }

        // keywords
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['keywords_page'],
            $this->tsSetupSeo['conf.']['keywords_page.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['keywords'] = $tmp;
        }

        // title
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['title_page'],
            $this->tsSetupSeo['conf.']['title_page.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['title'] = $tmp;
        }

        // author
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['author_page'],
            $this->tsSetupSeo['conf.']['author_page.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['author'] = $tmp;
        }

        // email
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['email_page'],
            $this->tsSetupSeo['conf.']['email_page.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['email'] = $tmp;
        }

        // last-update
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['lastUpdate_page'],
            $this->tsSetupSeo['conf.']['lastUpdate_page.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['lastUpdate'] = $tmp;
        }

        // #################
        // Geo
        // #################

        // tx_metaseo_geo_lat
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['tx_metaseo_geo_lat'],
            $this->tsSetupSeo['conf.']['tx_metaseo_geo_lat.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['geoPositionLatitude'] = $tmp;
        }

        // tx_metaseo_geo_long
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['tx_metaseo_geo_long'],
            $this->tsSetupSeo['conf.']['tx_metaseo_geo_long.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['geoPositionLongitude'] = $tmp;
        }

        // tx_metaseo_geo_place
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['tx_metaseo_geo_place'],
            $this->tsSetupSeo['conf.']['tx_metaseo_geo_place.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['geoPlacename'] = $tmp;
        }

        // tx_metaseo_geo_region
        $tmp = $this->cObj->stdWrap(
            $this->tsSetupSeo['conf.']['tx_metaseo_geo_region'],
            $this->tsSetupSeo['conf.']['tx_metaseo_geo_region.']
        );
        if (!empty($tmp)) {
            $this->pageMeta['geoRegion'] = $tmp;
        }

        // #################
        // Process meta tags
        // #################

        // process page meta data
        foreach ($this->pageMeta as $metaKey => $metaValue) {
            $metaValue = trim($metaValue);

            if (!empty($metaValue)) {
                $this->tsSetupSeo[$metaKey] = $metaValue;
            }
        }
    }

    /**
     * Collect metadata from connector
     *
     * @return mixed
     */
    protected function collectMetaDataFromConnector()
    {
        $ret = array();

        $storeMeta = $this->getStoreMeta();

        // Std meta tags
        foreach ($storeMeta['meta'] as $metaKey => $metaValue) {
            if ($metaValue === null) {
                // Remove meta
                unset($this->tsSetupSeo[$metaKey]);
            } elseif (!empty($metaValue)) {
                $this->tsSetupSeo[$metaKey] = trim($metaValue);
            }
        }

        // Custom meta tags
        foreach ($storeMeta['custom'] as $metaKey => $metaValue) {
            if ($metaValue === null) {
                // Remove meta
                unset($ret[$metaKey]);
            } elseif (!empty($metaValue)) {
                $ret[$metaKey] = trim($metaValue);
            }
        }

        return $ret;
    }

    /**
     * Generate standard metatags
     */
    protected function generateStandardMetaTags()
    {
        // dc schema
        if ($this->enableMetaDc && !$this->isHtml5()) {
            //schema.DCTERMS not allowed in HTML5 according to W3C validator #18
            $this->metaTagList['meta.schema.dc'] = array(
                'tag'        => 'link',
                'attributes' => array(
                    'rel'  => 'schema.DCTERMS',
                    'href' => 'http://purl.org/dc/terms/',
                ),
            );
        }

        // title
        if (!empty($this->tsSetupSeo['title']) && $this->enableMetaDc) {
            $this->metaTagList['meta.title'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'DCTERMS.title',
                    'content' => $this->tsSetupSeo['title'],
                ),
            );
        }

        // description
        if (!empty($this->tsSetupSeo['description'])) {
            $this->metaTagList['meta.description'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'description',
                    'content' => $this->tsSetupSeo['description'],
                ),
            );

            if ($this->enableMetaDc) {
                $this->metaTagList['meta.description.dc'] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'name'    => 'DCTERMS.description',
                        'content' => $this->tsSetupSeo['description'],
                    ),
                );
            }
        }

        // keywords
        if (!empty($this->tsSetupSeo['keywords'])) {
            $this->metaTagList['meta.keywords'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'keywords',
                    'content' => $this->tsSetupSeo['keywords'],
                ),
            );

            if ($this->enableMetaDc) {
                $this->metaTagList['meta.keywords.dc'] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'name'    => 'DCTERMS.subject',
                        'content' => $this->tsSetupSeo['keywords'],
                    ),
                );
            }
        }

        // copyright
        if (!empty($this->tsSetupSeo['copyright'])) {
            $this->metaTagList['meta.copyright'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'copyright',
                    'content' => $this->tsSetupSeo['copyright'],
                ),
            );

            if ($this->enableMetaDc) {
                $this->metaTagList['meta.copyright.dc'] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'name'    => 'DCTERMS.rights',
                        'content' => $this->tsSetupSeo['copyright'],
                    ),
                );
            }
        }

        // email
        if (!empty($this->tsSetupSeo['email'])) {
            $this->metaTagList['meta.email.link'] = array(
                'tag'        => 'link',
                'attributes' => array(
                    'rev'  => 'made',
                    'href' => 'mailto:' . $this->tsSetupSeo['email'],
                ),
            );

            $this->metaTagList['meta.email.http'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'http-equiv' => 'reply-to',
                    'content'    => $this->tsSetupSeo['email'],
                ),
            );
        }

        // author
        if (!empty($this->tsSetupSeo['author'])) {
            $this->metaTagList['meta.author'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'author',
                    'content' => $this->tsSetupSeo['author'],
                ),
            );

            if ($this->enableMetaDc) {
                $this->metaTagList['meta.author.dc'] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'name'    => 'DCTERMS.creator',
                        'content' => $this->tsSetupSeo['author'],
                    ),
                );
            }
        }

        // author
        if (!empty($this->tsSetupSeo['publisher']) && $this->enableMetaDc) {
            $this->metaTagList['meta.publisher.dc'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'DCTERMS.publisher',
                    'content' => $this->tsSetupSeo['publisher'],
                ),
            );
        }

        // distribution
        if (!empty($this->tsSetupSeo['distribution'])) {
            $this->metaTagList['meta.distribution'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'distribution',
                    'content' => $this->tsSetupSeo['distribution'],
                ),
            );
        }

        // rating
        if (!empty($this->tsSetupSeo['rating'])) {
            $this->metaTagList['meta.rating'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'rating',
                    'content' => $this->tsSetupSeo['rating'],
                ),
            );
        }

        // last-update
        if (!empty($this->tsSetupSeo['useLastUpdate']) && !empty($this->tsSetupSeo['lastUpdate'])) {
            $this->metaTagList['meta.date'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'date',
                    'content' => $this->tsSetupSeo['lastUpdate'],
                ),
            );

            if ($this->enableMetaDc) {
                $this->metaTagList['meta.date.dc'] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'name'    => 'DCTERMS.date',
                        'content' => $this->tsSetupSeo['lastUpdate'],
                    ),
                );
            }
        }

        // expire
        if (!empty($this->tsSetupSeo['useExpire']) && !empty($this->pageRecord['endtime'])) {
            $this->metaTagList['meta.expire'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'googlebot',
                    'content' => 'unavailable_after: ' . date('d-M-Y H:i:s T', $this->pageRecord['endtime']),
                ),
            );
        }
    }

    /**
     * Generate crawler (eg. robots) MetaTags
     */
    protected function generateCrawlerMetaTags()
    {
        // robots
        if (!empty($this->tsSetupSeo['robotsEnable'])) {
            $crawlerOrder = array();

            if (!empty($this->tsSetupSeo['robotsIndex']) && empty($this->pageRecord['tx_metaseo_is_exclude'])) {
                $crawlerOrder['index'] = 'index';
            } else {
                $crawlerOrder['index'] = 'noindex';
            }

            if (!empty($this->tsSetupSeo['robotsFollow'])) {
                $crawlerOrder['follow'] = 'follow';
            } else {
                $crawlerOrder['follow'] = 'nofollow';
            }

            if (empty($this->tsSetupSeo['robotsArchive'])) {
                $crawlerOrder['archive'] = 'noarchive';
            }

            if (empty($this->tsSetupSeo['robotsSnippet'])) {
                $crawlerOrder['snippet'] = 'nosnippet';
            }

            if (!empty($this->tsSetupSeo['robotsNoImageindex']) && $this->tsSetupSeo['robotsNoImageindex'] === '1') {
                $crawlerOrder['noimageindex'] = 'noimageindex';
            }

            if (!empty($this->tsSetupSeo['robotsNoTranslate']) && $this->tsSetupSeo['robotsNoTranslate'] === '1') {
                $crawlerOrder['notranslate'] = 'notranslate';
            }

            if (empty($this->tsSetupSeo['robotsOdp'])) {
                $crawlerOrder['odp'] = 'noodp';
            }

            if (empty($this->tsSetupSeo['robotsYdir'])) {
                $crawlerOrder['ydir'] = 'noydir';
            }


            $this->metaTagList['crawler.robots'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'robots',
                    'content' => implode(',', $crawlerOrder),
                ),
            );
        }

        // revisit
        if (!empty($this->tsSetupSeo['revisit'])) {
            $this->metaTagList['crawler.revisit'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'revisit-after',
                    'content' => $this->tsSetupSeo['revisit'],
                ),
            );
        }
    }

    /**
     * Generate geo position MetaTags
     */
    protected function generateGeoPosMetaTags()
    {
        // Geo-Position
        if (!empty($this->tsSetupSeo['geoPositionLatitude']) && !empty($this->tsSetupSeo['geoPositionLongitude'])) {
            $this->metaTagList['geo.icmb']     = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'ICBM',
                    'content' => $this->tsSetupSeo['geoPositionLatitude'] . ', '
                        . $this->tsSetupSeo['geoPositionLongitude'],
                ),
            );
            $this->metaTagList['geo.position'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'geo.position',
                    'content' => $this->tsSetupSeo['geoPositionLatitude'] . ';'
                        . $this->tsSetupSeo['geoPositionLongitude'],
                ),
            );
        }

        // Geo-Region
        if (!empty($this->tsSetupSeo['geoRegion'])) {
            $this->metaTagList['geo.region'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'geo.region',
                    'content' => $this->tsSetupSeo['geoRegion'],
                ),
            );
        }

        // Geo Placename
        if (!empty($this->tsSetupSeo['geoPlacename'])) {
            $this->metaTagList['geo.placename'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'geo.placename',
                    'content' => $this->tsSetupSeo['geoPlacename'],
                ),
            );
        }
    }

    /**
     * Generate service (eg. google) MetaTags
     */
    protected function generateServicesMetaTags()
    {
        // Google Verification
        if (!empty($this->tsSetupSeo['googleVerification'])) {
            $this->metaTagList['service.verification.google'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'google-site-verification',
                    'content' => $this->tsSetupSeo['googleVerification'],
                ),
            );
        }

        // MSN Verification
        if (!empty($this->tsSetupSeo['msnVerification'])) {
            $this->metaTagList['service.verification.msn'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'msvalidate.01',
                    'content' => $this->tsSetupSeo['msnVerification'],
                ),
            );
        }

        // Yahoo Verification
        if (!empty($this->tsSetupSeo['yahooVerification'])) {
            $this->metaTagList['service.verification.yahoo'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'y_key',
                    'content' => $this->tsSetupSeo['yahooVerification'],
                ),
            );
        }

        // WebOfTrust Verification
        if (!empty($this->tsSetupSeo['wotVerification'])) {
            $this->metaTagList['service.verification.wot'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'name'    => 'wot-verification',
                    'content' => $this->tsSetupSeo['wotVerification'],
                ),
            );
        }


        // PICS label
        if (!empty($this->tsSetupSeo['picsLabel'])) {
            $this->metaTagList['service.pics'] = array(
                'tag'        => 'meta',
                'attributes' => array(
                    'http-equiv' => 'PICS-Label',
                    'content'    => $this->tsSetupSeo['picsLabel'],
                ),
            );
        }
    }

    /**
     * Generate user agent metatags
     */
    protected function generateUserAgentMetaTags()
    {
        if (!empty($this->tsSetupSeo['ieCompatibilityMode'])) {
            if (is_numeric($this->tsSetupSeo['ieCompatibilityMode'])) {
                $this->metaTagList['ua.msie.compat'] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'http-equiv' => 'X-UA-Compatible',
                        'content'    => 'IE=EmulateIE' . (int)$this->tsSetupSeo['ieCompatibilityMode'],
                    ),
                );
            } else {
                $this->metaTagList['ua.msie.compat'] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'http-equiv' => 'X-UA-Compatible',
                        'content'    => $this->tsSetupSeo['ieCompatibilityMode'],
                    ),
                );
            }
        }
    }

    /**
     * Generate Link (next, prev..) MetaTags
     */
    protected function generateLinkMetaTags()
    {
        $rootLine = GeneralUtility::getRootLine();

        $currentPage = end($rootLine);
        $rootPage    = reset($rootLine);

        $currentIsRootpage = ($currentPage['uid'] === $rootPage['uid']);

        // Only generate up, prev and next if NOT rootpage
        // to prevent linking to other domains
        // see https://github.com/mblaschke/TYPO3-metaseo/issues/5
        if (!$currentIsRootpage) {
            $startPage    = $GLOBALS['TSFE']->cObj->HMENU($this->tsSetupSeo['sectionLinks.']['start.']);
            $startPageUrl = null;
            if (!empty($startPage)) {
                $startPageUrl = $this->generateLink($startPage);
            }

            $prevPage    = $GLOBALS['TSFE']->cObj->HMENU($this->tsSetupSeo['sectionLinks.']['prev.']);
            $prevPageUrl = null;
            if (!empty($prevPage)) {
                $prevPageUrl = $this->generateLink($prevPage);
            }

            $nextPage    = $GLOBALS['TSFE']->cObj->HMENU($this->tsSetupSeo['sectionLinks.']['next.']);
            $nextPageUrl = null;
            if (!empty($nextPage)) {
                $nextPageUrl = $this->generateLink($nextPage);
            }
        }

        // Start (first page in rootline -> root page)
        if (!empty($startPageUrl)) {
            $this->metaTagList['link.rel.start'] = array(
                'tag'        => 'link',
                'attributes' => array(
                    'rel'  => 'start',
                    'href' => $startPageUrl,
                ),
            );
        }

        // Prev (previous page in rootline)
        if (!empty($prevPageUrl)) {
            $this->metaTagList['link.rel.prev'] = array(
                'tag'        => 'link',
                'attributes' => array(
                    'rel'  => 'prev',
                    'href' => $prevPageUrl,
                ),
            );
        }

        // Next (next page in rootline)
        if (!empty($nextPageUrl)) {
            $this->metaTagList['link.rel.next'] = array(
                'tag'        => 'link',
                'attributes' => array(
                    'rel'  => 'next',
                    'href' => $nextPageUrl,
                ),
            );
        }
    }

    /**
     * Generate CanonicalUrl MetaTags
     */
    protected function generateCanonicalUrlMetaTags()
    {
        $canonicalUrl = $this->generateCanonicalUrl();

        if (!is_null($canonicalUrl)) {
            $this->metaTagList['link.rel.canonical'] = array(
                'tag'        => 'link',
                'attributes' => array(
                    'rel'  => 'canonical',
                    'href' => $canonicalUrl,
                ),
            );
        }
    }

    /**
     * Generate CanonicalUrl for this page
     *
     * @return null|string   Url or null if Url is empty
     */
    protected function generateCanonicalUrl()
    {
        //User has specified a canonical URL in the page properties
        if (!empty($this->pageRecord['tx_metaseo_canonicalurl'])) {
            return $this->generateLink($this->pageRecord['tx_metaseo_canonicalurl']);
        }

        //Fallback to global settings to generate Url
        if (!empty($this->tsSetupSeo['canonicalUrl'])) {
            list($clUrl, $clLinkConf, $clDisableMpMode) = $this->detectCanonicalPage(
                $this->tsSetupSeo['canonicalUrl.']
            );
            if (!empty($clUrl) && isset($clLinkConf) && isset($clDisableMpMode)) {
                $url = $this->generateLink($clUrl, $clLinkConf, $clDisableMpMode);
                return $this->setFallbackProtocol(
                    $this->pageRecord['url_scheme'], //page properties protocol selection
                    $this->tsSetupSeo['canonicalUrl.']['fallbackProtocol'],
                    $url
                );
            }
        }

        return null;
    }

    /**
     * Replaces protocol in URL with a fallback protocol
     * Missing or unknown protocol will not be replaced
     *
     * @param string $pagePropertiesProtocol protocol from page properties
     * @param string $canonicalFallbackProtocol fallback protocol to go for if protocol in page properties is undefined
     * @param string $url
     *
     * @return string|null
     */
    protected function setFallbackProtocol($pagePropertiesProtocol, $canonicalFallbackProtocol, $url)
    {
        $url = ltrim($url);
        if (empty($url)) {
            return null;
        }

        if (empty($canonicalFallbackProtocol)) {
            // Fallback not defined
            return $url;
        }

        if (!empty($pagePropertiesProtocol)) {
            // Protocol is well-defined via page properties (default is '0' with type string).
            // User cannot request with wrong protocol. Canonical URL cannot be wrong.
            return $url;
        }

        //get length of protocol substring
        $protocolLength = $this->getProtocolLength($url);
        if (is_null($protocolLength)) {
            //unknown protocol
            return $url;
        }

        //replace protocol prefix
        return substr_replace($url, $canonicalFallbackProtocol . '://', 0, $protocolLength);
    }

    /**
     * Case-insensitive detection of the protocol used in an Url. Returns protocol length if found.
     *
     * @param $url
     *
     * @return int|null length of protocol or null for unknown protocol
     */
    protected function getProtocolLength($url)
    {
        if (substr_compare($url, 'http://', 0, 7, true) === 0) {
            return 7;
        }
        if (substr_compare($url, 'https://', 0, 8, true) === 0) {
            return 8;
        }
        if (substr_compare($url, '//', 0, 2, false) === 0) {
            return 2;
        }

        return null;
    }

    /**
     * Generate OpenGraph MetaTags
     */
    protected function generateOpenGraphMetaTags()
    {
        $tsSetupSeoOg = $this->tsSetupSeo['opengraph.'];

        // Get list of tags (filtered array)
        $ogTagNameList = array_keys($tsSetupSeoOg);
        $ogTagNameList = array_unique(
            array_map(
                function ($item) {
                    return rtrim($item, '.');
                },
                $ogTagNameList
            )
        );

        foreach ($ogTagNameList as $ogTagName) {
            $ogTagValue = null;

            // Check if TypoScript value is a simple one (eg. title = foo)
            // or it is a cObject
            if (!empty($tsSetupSeoOg[$ogTagName]) && !array_key_exists($ogTagName . '.', $tsSetupSeoOg)) {
                // Simple value
                $ogTagValue = $tsSetupSeoOg[$ogTagName];
            } elseif (!empty($tsSetupSeoOg[$ogTagName])) {
                // Content object (eg. TEXT)
                $ogTagValue = $this->cObj->cObjGetSingle(
                    $tsSetupSeoOg[$ogTagName],
                    $tsSetupSeoOg[$ogTagName . '.']
                );
            }

            if ($ogTagValue !== null && strlen($ogTagValue) >= 1) {
                $this->metaTagList['og.' . $ogTagName] = array(
                    'tag'        => 'meta',
                    'attributes' => array(
                        'property' => 'og:' . $ogTagName,
                        'content'  => $ogTagValue,
                    ),
                );
            }
        }
    }

    /**
     * @return bool true if external OpenGraph tags are available via the Connector, false otherwise
     */
    protected function isAvailableExternalOgTags()
    {
        $storeMeta = $this->getStoreMeta();

        return !empty($storeMeta['flag']['meta:og:external']);
    }

    /**
     * @return bool true if external custom OpenGraph tags are available via the Connector, false otherwise
     */
    protected function isAvailableExternalOgCustomTags()
    {
        $storeMeta = $this->getStoreMeta();

        return !empty($storeMeta['flag']['custom:og:external']);
    }

    /**
     * @return array with the meta tags from the connector
     */
    protected function getStoreMeta()
    {
        return $this->getConnector()->getStore();
    }

    /**
     * @return \Metaseo\Metaseo\Connector
     */
    protected function getConnector()
    {
        /** @var \Metaseo\Metaseo\Connector $connector */
        $connector = $this->objectManager->get('Metaseo\\Metaseo\\Connector');
        return $connector;
    }
}
