<?php
namespace Metaseo\Metaseo\Page\Part;

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
 * Page Title Changer
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: PagetitlePart.php 81080 2013-10-28 09:54:33Z mblaschke $
 */
class PagetitlePart {

    /**
     * Add SEO-Page Title
     *
     * @param    string $title    Default page title (rendered by TYPO3)
     * @return    string            Modified page title
     */
    public function main($title) {
        // INIT
        $ret              = $title;
        $rawTitel         = !empty($GLOBALS['TSFE']->altPageTitle) ? $GLOBALS['TSFE']->altPageTitle : $GLOBALS['TSFE']->page['title'];
        $tsSetup          = $GLOBALS['TSFE']->tmpl->setup;
        $tsSeoSetup       = array();
        $rootLine         = $GLOBALS['TSFE']->rootLine;
        $currentPid       = $GLOBALS['TSFE']->id;
        $skipPrefixSuffix = FALSE;
        $applySitetitle   = TRUE;

        $pageTitelPrefix = FALSE;
        $pageTitelSuffix = FALSE;

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
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('pagetitle-setup', $this, $tsSeoSetup);

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
                $applySitetitle = FALSE;
            }

            $skipPrefixSuffix = TRUE;
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

                        if ($pageTitelPrefix !== FALSE || $pageTitelSuffix !== FALSE) {
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
            $connector = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Metaseo\\Metaseo\\Connector');
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

                    $pageTitelPrefix = FALSE;
                    $pageTitelSuffix = FALSE;

                    if (empty($tsSeoSetup['pageTitle.']['applySitetitleToPagetitle'])) {
                        $applySitetitle = FALSE;
                    }
                }

                if (isset($store['pagetitle.sitetitle'])) {
                    $sitetitle = $store['pagetitle.sitetitle'];
                }
            }

            // Apply prefix and suffix
            if ($pageTitelPrefix !== FALSE || $pageTitelSuffix !== FALSE) {
                $ret = $rawTitel;

                if ($pageTitelPrefix !== FALSE) {
                    $ret = $pageTitelPrefix . ' ' . $ret;
                }

                if ($pageTitelSuffix !== FALSE) {
                    $ret .= ' ' . $pageTitelSuffix;
                }

                if (!empty($tsSeoSetup['pageTitle.']['applySitetitleToPrefixSuffix'])) {
                    $applySitetitle = TRUE;
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

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('pagetitle-output', $this, $ret);

        return $ret;
    }
}
