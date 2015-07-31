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

use Metaseo\Metaseo\Utility\FrontendUtility;
use Metaseo\Metaseo\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;

/**
 * Page Title Changer
 */
class PagetitlePart extends AbstractPart
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
     * @var array
     */
    protected $tsSetupSeo = array();

    /**
     * Page rootline
     *
     * @var array
     */
    protected $rootLine = array();

    /**
     * Add SEO-Page Title
     *
     * @param    string $title Default page title (rendered by TYPO3)
     *
     * @return    string            Modified page title
     */
    public function main($title)
    {
        $ret = null;

        // ############################
        // Fetch from cache
        // ############################

        $pageTitleCachingEnabled = $this->checkIfPageTitleCachingEnabled();
        if ($pageTitleCachingEnabled === true) {
            $cacheIdentification = sprintf(
                '%s_%s_title',
                $GLOBALS['TSFE']->id,
                substr(sha1(FrontendUtility::getCurrentUrl()), 10, 30)
            );

            /** @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager */
            $objectManager = Typo3GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
            );
            $cacheManager  = $objectManager->get('TYPO3\\CMS\\Core\\Cache\\CacheManager');
            $cache         = $cacheManager->getCache('cache_pagesection');

            $cacheTitle = $cache->get($cacheIdentification);
            if (!empty($cacheTitle)) {
                $ret = $cacheTitle;
            }
        }

        // ############################
        // Generate page title
        // ############################

        // Generate page title if not set
        // also fallback
        if (empty($ret)) {
            $this->initialize();
            $ret = $this->generatePageTitle($title);

            // Cache page title (if page is not cacheable)
            if ($pageTitleCachingEnabled === true && isset($cache) && isset($cacheIdentification)) {
                $cache->set($cacheIdentification, $ret, array('pageId_' . $GLOBALS['TSFE']->id));
            }
        }

        // ############################
        // Output
        // ############################

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'pageTitleOutput', $this, $ret);

        return $ret;
    }

    /**
     * Initialize
     */
    protected function initialize()
    {
        $this->cObj       = $GLOBALS['TSFE']->cObj;
        $this->tsSetup    = $GLOBALS['TSFE']->tmpl->setup;
        $this->rootLine   = GeneralUtility::getRootLine();

        if (!empty($this->tsSetup['plugin.']['metaseo.'])) {
            $this->tsSetupSeo = $this->tsSetup['plugin.']['metaseo.'];

            // get stdwrap list
            if (!empty($this->tsSetupSeo['stdWrap.'])) {
                $this->stdWrapList = $this->tsSetupSeo['pageTitle.']['stdWrap.'];
            }
        } else {
            $this->tsSetupSeo = array();
        }
    }

    /**
     * Check if page title caching is enabled
     *
     * @return bool
     */
    protected function checkIfPageTitleCachingEnabled()
    {
        $cachingEnabled = !empty($GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.']['pageTitle.']['caching']);

        // Enable caching only if caching is enabled in SetupTS
        // And if there is any USER_INT on the current page
        //
        // -> USER_INT will break Connector pagetitle setting
        //    because the plugin output is cached but not the whole
        //    page. so the Connector will not be called again
        //    and the default page title will be shown
        //    which is wrong
        // -> if the page is fully cacheable we don't have anything
        //    to do
        return $cachingEnabled && !FrontendUtility::isCacheable();
    }

    /**
     * Add SEO-Page Title
     *
     * @param    string $title Default page title (rendered by TYPO3)
     *
     * @return    string            Modified page title
     * @todo: split up function (too long)
     */
    public function generatePageTitle($title)
    {
        // INIT
        $ret              = $title;
        $rawTitle         = !empty($GLOBALS['TSFE']->altPageTitle) ?
            $GLOBALS['TSFE']->altPageTitle : $GLOBALS['TSFE']->page['title'];
        $currentPid       = $GLOBALS['TSFE']->id;
        $skipPrefixSuffix = false;
        $applySitetitle   = true;

        $pageTitlePrefix = false;
        $pageTitleSuffix = false;

        $this->stdWrapList = array();

        $sitetitle = $this->tsSetup['sitetitle'];

        // Use browsertitle if available
        if (!empty($GLOBALS['TSFE']->page['tx_metaseo_pagetitle_rel'])) {
            $rawTitle = $GLOBALS['TSFE']->page['tx_metaseo_pagetitle_rel'];
        }

        // Call hook
        GeneralUtility::callHookAndSignal(
            __CLASS__,
            'pageTitleSetup',
            $this,
            $this->tsSetupSeo
        );

        // get stdwrap list
        if (!empty($this->tsSetupSeo['pageTitle.']['stdWrap.'])) {
            $this->stdWrapList = $this->tsSetupSeo['pageTitle.']['stdWrap.'];
        }

        // Apply stdWrap before
        if (!empty($this->stdWrapList['before.'])) {
            $rawTitle = $this->cObj->stdWrap($rawTitle, $this->stdWrapList['before.']);
        }

        // #######################################################################
        // RAW PAGE TITLE
        // #######################################################################
        if (!empty($GLOBALS['TSFE']->page['tx_metaseo_pagetitle'])) {
            $ret = $GLOBALS['TSFE']->page['tx_metaseo_pagetitle'];

            // Add template prefix/suffix
            if (empty($this->tsSetupSeo['pageTitle.']['applySitetitleToPagetitle'])) {
                $applySitetitle = false;
            }

            $skipPrefixSuffix = true;
        }


        // #######################################################################
        // PAGE TITLE PREFIX/SUFFIX
        // #######################################################################
        if (!$skipPrefixSuffix) {
            foreach ($this->rootLine as $page) {
                switch ((int)$page['tx_metaseo_inheritance']) {
                    case 0:
                        // ###################################
                        // Normal
                        // ###################################
                        if (!empty($page['tx_metaseo_pagetitle_prefix'])) {
                            $pageTitlePrefix = $page['tx_metaseo_pagetitle_prefix'];
                        }

                        if (!empty($page['tx_metaseo_pagetitle_suffix'])) {
                            $pageTitleSuffix = $page['tx_metaseo_pagetitle_suffix'];
                        }

                        if ($pageTitlePrefix !== false || $pageTitleSuffix !== false) {
                            // pagetitle found - break foreach
                            break 2;
                        }
                        break;
                    case 1:
                        // ###################################
                        // Skip
                        // (don't inherit from this page)
                        // ###################################
                        if ((int)$page['uid'] != $currentPid) {
                            continue 2;
                        }

                        if (!empty($page['tx_metaseo_pagetitle_prefix'])) {
                            $pageTitlePrefix = $page['tx_metaseo_pagetitle_prefix'];
                        }

                        if (!empty($page['tx_metaseo_pagetitle_suffix'])) {
                            $pageTitleSuffix = $page['tx_metaseo_pagetitle_suffix'];
                        }

                        break 2;
                        break;
                }
            }

            // #################
            // Process settings from access point
            // #################
            $connector = $this->objectManager->get('Metaseo\\Metaseo\\Connector');
            $store     = $connector->getStore('pagetitle');

            if (!empty($store)) {
                if (isset($store['pagetitle.title'])) {
                    $rawTitle = $store['pagetitle.title'];
                }

                if (isset($store['pagetitle.prefix'])) {
                    $pageTitlePrefix = $store['pagetitle.prefix'];
                }

                if (isset($store['pagetitle.suffix'])) {
                    $pageTitleSuffix = $store['pagetitle.suffix'];
                }

                if (isset($store['pagetitle.absolute'])) {
                    $ret      = $store['pagetitle.absolute'];
                    $rawTitle = $store['pagetitle.absolute'];

                    $pageTitlePrefix = false;
                    $pageTitleSuffix = false;

                    if (empty($this->tsSetupSeo['pageTitle.']['applySitetitleToPagetitle'])) {
                        $applySitetitle = false;
                    }
                }

                if (isset($store['pagetitle.sitetitle'])) {
                    $sitetitle = $store['pagetitle.sitetitle'];
                }
            }

            // Apply prefix and suffix
            if ($pageTitlePrefix !== false || $pageTitleSuffix !== false) {
                $ret = $rawTitle;

                if ($pageTitlePrefix !== false) {
                    $ret = $pageTitlePrefix . ' ' . $ret;
                }

                if ($pageTitleSuffix !== false) {
                    $ret .= ' ' . $pageTitleSuffix;
                }

                if (!empty($this->tsSetupSeo['pageTitle.']['applySitetitleToPrefixSuffix'])) {
                    $applySitetitle = true;
                }
            } else {
                $ret = $rawTitle;
            }
        }

        // #######################################################################
        // APPLY SITETITLE (from setup)
        // #######################################################################
        if ($applySitetitle) {
            $ret = $this->applySitetitleToPagetitle($sitetitle, $ret);
        }

        // Apply stdWrap after
        if (!empty($this->stdWrapList['after.'])) {
            $ret = $this->cObj->stdWrap($ret, $this->stdWrapList['after.']);
        }

        return $ret;
    }

    /**
     * Apply sitetitle (from sys_template) to pagetitle
     *
     * @param string $sitetitle Sitetitle
     * @param string $title     Page title
     *
     * @return string
     */
    protected function applySitetitleToPagetitle($sitetitle, $title)
    {
        $pageTitleGlue    = ':';
        $glueSpacerBefore = '';
        $glueSpacerAfter  = '';

        $ret = $title;

        // Overwrite sitetitle with the one from ts-setup (if available)
        if (!empty($this->tsSetupSeo['pageTitle.']['sitetitle'])) {
            $sitetitle = $this->tsSetupSeo['pageTitle.']['sitetitle'];
        }

        // Apply stdWrap after
        if (!empty($this->stdWrapList['sitetitle.'])) {
            $sitetitle = $this->cObj->stdWrap($sitetitle, $this->stdWrapList['sitetitle.']);
        }


        if (isset($this->tsSetupSeo['pageTitle.']['sitetitleGlue'])) {
            $pageTitleGlue = $this->tsSetupSeo['pageTitle.']['sitetitleGlue'];
        }

        if (!empty($this->tsSetupSeo['pageTitle.']['sitetitleGlueSpaceBefore'])) {
            $glueSpacerBefore = ' ';
        }

        if (!empty($this->tsSetupSeo['pageTitle.']['sitetitleGlueSpaceAfter'])) {
            $glueSpacerAfter = ' ';
        }

        $sitetitlePosition = 0;
        if (isset($this->tsSetupSeo['pageTitle.']['sitetitlePosition'])) {
            $sitetitlePosition = (int)$this->tsSetupSeo['pageTitle.']['sitetitlePosition'];
        } elseif (isset($this->tsSetup['config.']['pageTitleFirst'])) {
            $sitetitlePosition = (int)$this->tsSetup['config.']['pageTitleFirst'];
        }

        // add overall pagetitle from template/ts-setup
        if ($sitetitlePosition) {
            // suffix
            $ret .= $glueSpacerBefore . $pageTitleGlue . $glueSpacerAfter . $sitetitle;

            return $ret;
        } else {
            // prefix (default)
            $ret = $sitetitle . $glueSpacerBefore . $pageTitleGlue . $glueSpacerAfter . $ret;

            return $ret;
        }
    }
}
