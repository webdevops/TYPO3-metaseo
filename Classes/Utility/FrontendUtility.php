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

namespace Metaseo\Metaseo\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;

/**
 * General utility
 */
class FrontendUtility
{

    /**
     * Init TSFE with all needed classes eg. for backend usage ($GLOBALS['TSFE'])
     *
     * @param integer      $pageUid      PageUID
     * @param null|array   $rootLine     Rootline
     * @param null|array   $pageData     Page data array
     * @param null|array   $rootlineFull Full rootline
     * @param null|integer $sysLanguage  Sys language uid
     */
    public static function init(
        $pageUid,
        $rootLine = null,
        $pageData = null,
        $rootlineFull = null,
        $sysLanguage = null
    ) {
        static $cacheTSFE = array();
        static $lastTsSetupPid = null;

        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        $objectManager = Typo3GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
        );

        // Fetch page if needed
        if ($pageData === null) {
            /** @var \TYPO3\CMS\Frontend\Page\PageRepository $sysPageObj */
            $sysPageObj                   = $objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
            $sysPageObj->sys_language_uid = $sysLanguage;

            $pageData = $sysPageObj->getPage_noCheck($pageUid);
        }

        // create time tracker if needed
        if (empty($GLOBALS['TT'])) {
            /** @var \TYPO3\CMS\Core\TimeTracker\NullTimeTracker $timeTracker */
            $timeTracker = $objectManager->get('TYPO3\\CMS\\Core\\TimeTracker\\NullTimeTracker');

            $GLOBALS['TT'] = $timeTracker;
            $GLOBALS['TT']->start();
        }

        if ($rootLine === null) {
            /** @var \TYPO3\CMS\Frontend\Page\PageRepository $sysPageObj */
            $sysPageObj                   = $objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
            $sysPageObj->sys_language_uid = $sysLanguage;
            $rootLine                     = $sysPageObj->getRootLine($pageUid);

            // save full rootline, we need it in TSFE
            $rootlineFull = $rootLine;
        }

        // Only setup tsfe if current instance must be changed
        if ($lastTsSetupPid !== $pageUid) {
            // Cache TSFE if possible to prevent reinit (is still slow but we need the TSFE)
            if (empty($cacheTSFE[$pageUid])) {
                // suppress http redirect headers via BackendCompliantTsfeController, loaded via ext_localconf.php
                /** @var \Metaseo\Metaseo\Frontend\Controller\BackendCompliantTsfeController $tsfeController */
                $tsfeController                   = $objectManager->get(
                    'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
                    $GLOBALS['TYPO3_CONF_VARS'],
                    $pageUid,
                    0
                );
                $tsfeController->sys_language_uid = $sysLanguage;

                /** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObjRenderer */
                $cObjRenderer = $objectManager->get('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

                /** @var \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService $TSObj */
                $TSObj = $objectManager->get('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');

                $TSObj->tt_track = 0;
                $TSObj->init();
                $TSObj->runThroughTemplates($rootLine);
                $TSObj->generateConfig();

                $_GET['id'] = $pageUid;

                // Init TSFE
                $GLOBALS['TSFE']       = $tsfeController;
                $GLOBALS['TSFE']->cObj = $cObjRenderer;
                $GLOBALS['TSFE']->initFEuser();
                $GLOBALS['TSFE']->determineId();

                if (empty($GLOBALS['TSFE']->tmpl)) {
                    $GLOBALS['TSFE']->tmpl = new \stdClass();
                }

                $GLOBALS['TSFE']->tmpl->setup = $TSObj->setup;
                $GLOBALS['TSFE']->initTemplate();
                $GLOBALS['TSFE']->getConfigArray();

                $GLOBALS['TSFE']->baseUrl = $GLOBALS['TSFE']->config['config']['baseURL'];

                $cacheTSFE[$pageUid] = $GLOBALS['TSFE'];
            }

            $GLOBALS['TSFE'] = $cacheTSFE[$pageUid];

            $lastTsSetupPid = $pageUid;
        }

        $GLOBALS['TSFE']->page       = $pageData;
        $GLOBALS['TSFE']->rootLine   = $rootlineFull;
        $GLOBALS['TSFE']->cObj->data = $pageData;
    }

    /**
     * Check current page for blacklisting
     *
     * @param  array $blacklist Blacklist configuration
     *
     * @return bool
     */
    public static function checkPageForBlacklist(array $blacklist)
    {
        return GeneralUtility::checkUrlForBlacklisting(self::getCurrentUrl(), $blacklist);
    }

    /**
     * Check if frontend page is cacheable
     *
     * @param array|null $conf Configuration
     * @return bool
     */
    public static function isCacheable($conf = null)
    {
        $TSFE = self::getTsfe();

        if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !empty($TSFE->fe_user->user['uid'])) {
            return false;
        }

        // don't parse if page is not cacheable
        if (empty($conf['allowNoStaticCachable']) && !$TSFE->isStaticCacheble()) {
            return false;
        }

        // Skip no_cache-pages
        if (empty($conf['allowNoCache']) && !empty($TSFE->no_cache)) {
            return false;
        }

        return true;
    }

    /**
     * Return current URL
     *
     * @return null|string
     */
    public static function getCurrentUrl()
    {
        $ret = null;

        $TSFE = self::getTsfe();

        if (!empty($TSFE->anchorPrefix)) {
            $ret = (string)$TSFE->anchorPrefix;
        } else {
            $ret = (string)$TSFE->siteScript;
        }

        return $ret;
    }

    /**
     * Get TSFE
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    public static function getTsfe()
    {
        return $GLOBALS['TSFE'];
    }
}
