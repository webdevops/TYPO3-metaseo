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
use Metaseo\Metaseo\Utility\CacheUtility;

/**
 * Page Title Changer
 */
class PagetitlePart extends \Metaseo\Metaseo\Page\Part\AbstractPart {

    /**
     * Add SEO-Page Title
     *
     * @param    string $title Default page title (rendered by TYPO3)
     *
     * @return    string            Modified page title
     */
    public function main($title) {
        $ret = null;

        // ############################
        // Fetch from cache
        // ############################

        $pageTitleCachingEnabled = $this->checkIfPageTitleCachingEnabled();
        if ($pageTitleCachingEnabled) {
            $cacheIdentification = $GLOBALS['TSFE']->id . '_' . substr(sha1(FrontendUtility::getCurrentUrl()),10,30) . '_title';

            /** @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager */
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
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
            $ret = $this->generatePageTitle($title);

            // Cache page title (if page is not cacheable)
            if ($pageTitleCachingEnabled) {
                $cache->set($cacheIdentification, $ret, array('pageId_' . $GLOBALS['TSFE']->id));
            }
        }

        // ############################
        // Output
        // ############################

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHookAndSignal(__CLASS__, 'pageTitleOutput', $this, $ret);

        return $ret;
    }

    /**
     * Check if page title caching is enabled
     *
     * @return bool
     */
    protected function checkIfPageTitleCachingEnabled() {
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
     */
    public function generatePageTitle($title) {
        // INIT
        $ret              = $title;
        $rawTitel         = !empty($GLOBALS['TSFE']->altPageTitle) ? $GLOBALS['TSFE']->altPageTitle : $GLOBALS['TSFE']->page['title'];
        $tsSetup          = $GLOBALS['TSFE']->tmpl->setup;
        $tsSeoSetup       = array();
        $rootLine         = $GLOBALS['TSFE']->rootLine;
        $currentPid       = $GLOBALS['TSFE']->id;
        $skipPrefixSuffix = false;
        $applySitetitle   = true;

        $pageTitelPrefix = false;
        $pageTitelSuffix = false;

        $stdWrapList = array();

        $sitetitle = $tsSetup['sitetitle'];

        // get configuration
        if (!empty($tsSetup['plugin.']['metaseo.'])) {
            $tsSeoSetup = $tsSetup['plugin.']['metaseo.'];
        }

        // Use browsertitle if available
        if (!empty($GLOBALS['TSFE']->page['tx_metaseo_pagetitle_rel'])) {
            $rawTitel = $GLOBALS['TSFE']->page['tx_metaseo_pagetitle_rel'];
        }

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHookAndSignal(__CLASS__, 'pageTitleSetup', $this, $tsSeoSetup);

        // get stdwrap list
        if (!empty($tsSeoSetup['pageTitle.']['stdWrap.'])) {
            $stdWrapList = $tsSeoSetup['pageTitle.']['stdWrap.'];
        }

        // Apply stdWrap before
        if (!empty($stdWrapList['before.'])) {
            $rawTitel = $this->cObj->stdWrap($rawTitel, $stdWrapList['before.']);
        }

        // #######################################################################
        // RAW PAGE TITEL
        // #######################################################################
        if (!empty($GLOBALS['TSFE']->page['tx_metaseo_pagetitle'])) {
            $ret = $GLOBALS['TSFE']->page['tx_metaseo_pagetitle'];

            // Add template prefix/suffix
            if (empty($tsSeoSetup['pageTitle.']['applySitetitleToPagetitle'])) {
                $applySitetitle = false;
            }

            $skipPrefixSuffix = true;
        }


        // #######################################################################
        // PAGE TITEL PREFIX/SUFFIX
        // #######################################################################
        if (!$skipPrefixSuffix) {
            foreach ($rootLine as $page) {
                switch ((int)$page['tx_metaseo_inheritance']) {
                    case 0:
                        // ###################################
                        // Normal
                        // ###################################
                        if (!empty($page['tx_metaseo_pagetitle_prefix'])) {
                            $pageTitelPrefix = $page['tx_metaseo_pagetitle_prefix'];
                        }

                        if (!empty($page['tx_metaseo_pagetitle_suffix'])) {
                            $pageTitelSuffix = $page['tx_metaseo_pagetitle_suffix'];
                        }

                        if ($pageTitelPrefix !== false || $pageTitelSuffix !== false) {
                            // pagetitle found - break foreach
                            break 2;
                        }
                        break;

                    case 1:
                        // ###################################
                        // Skip
                        // (don't herit from this page)
                        // ###################################
                        if ((int)$page['uid'] != $currentPid) {
                            continue 2;
                        }

                        if (!empty($page['tx_metaseo_pagetitle_prefix'])) {
                            $pageTitelPrefix = $page['tx_metaseo_pagetitle_prefix'];
                        }

                        if (!empty($page['tx_metaseo_pagetitle_suffix'])) {
                            $pageTitelSuffix = $page['tx_metaseo_pagetitle_suffix'];
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
                    $rawTitel = $store['pagetitle.title'];
                }

                if (isset($store['pagetitle.prefix'])) {
                    $pageTitelPrefix = $store['pagetitle.prefix'];
                }

                if (isset($store['pagetitle.suffix'])) {
                    $pageTitelSuffix = $store['pagetitle.suffix'];
                }

                if (isset($store['pagetitle.absolute'])) {
                    $ret      = $store['pagetitle.absolute'];
                    $rawTitel = $store['pagetitle.absolute'];

                    $pageTitelPrefix = false;
                    $pageTitelSuffix = false;

                    if (empty($tsSeoSetup['pageTitle.']['applySitetitleToPagetitle'])) {
                        $applySitetitle = false;
                    }
                }

                if (isset($store['pagetitle.sitetitle'])) {
                    $sitetitle = $store['pagetitle.sitetitle'];
                }
            }

            // Apply prefix and suffix
            if ($pageTitelPrefix !== false || $pageTitelSuffix !== false) {
                $ret = $rawTitel;

                if ($pageTitelPrefix !== false) {
                    $ret = $pageTitelPrefix . ' ' . $ret;
                }

                if ($pageTitelSuffix !== false) {
                    $ret .= ' ' . $pageTitelSuffix;
                }

                if (!empty($tsSeoSetup['pageTitle.']['applySitetitleToPrefixSuffix'])) {
                    $applySitetitle = true;
                }
            } else {
                $ret = $rawTitel;
            }
        }

        // #######################################################################
        // APPLY SITETITLE (from setup)
        // #######################################################################
        if ($applySitetitle) {
            $pageTitleGlue    = ':';
            $glueSpacerBefore = '';
            $glueSpacerAfter  = '';

            // Overwrite sitetitle with the one from ts-setup (if available)
            if (!empty($tsSeoSetup['pageTitle.']['sitetitle'])) {
                $sitetitle = $tsSeoSetup['pageTitle.']['sitetitle'];
            }

            // Apply stdWrap after
            if (!empty($stdWrapList['sitetitle.'])) {
                $sitetitle = $this->cObj->stdWrap($sitetitle, $stdWrapList['sitetitle.']);
            }


            if (isset($tsSeoSetup['pageTitle.']['sitetitleGlue'])) {
                $pageTitleGlue = $tsSeoSetup['pageTitle.']['sitetitleGlue'];
            }

            if (!empty($tsSeoSetup['pageTitle.']['sitetitleGlueSpaceBefore'])) {
                $glueSpacerBefore = ' ';
            }

            if (!empty($tsSeoSetup['pageTitle.']['sitetitleGlueSpaceAfter'])) {
                $glueSpacerAfter = ' ';
            }

            $sitetitlePosition = 0;
            if (isset($tsSeoSetup['pageTitle.']['sitetitlePosition'])) {
                $sitetitlePosition = (int)$tsSeoSetup['pageTitle.']['sitetitlePosition'];
            } elseif (isset($tsSetup['config.']['pageTitleFirst'])) {
                $sitetitlePosition = (int)$tsSetup['config.']['pageTitleFirst'];
            }

            // add overall pagetitel from template/ts-setup
            if ($sitetitlePosition) {
                // suffix
                $ret .= $glueSpacerBefore . $pageTitleGlue . $glueSpacerAfter . $sitetitle;
            } else {
                // prefix (default)
                $ret = $sitetitle . $glueSpacerBefore . $pageTitleGlue . $glueSpacerAfter . $ret;
            }
        }

        // Apply stdWrap after
        if (!empty($stdWrapList['after.'])) {
            $ret = $this->cObj->stdWrap($ret, $stdWrapList['after.']);
        }

        return $ret;
    }
}
