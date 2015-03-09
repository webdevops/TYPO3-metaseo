<?php
namespace Metaseo\Metaseo\Utility;

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
 * General utility
 *
 * @package     metaseo
 * @subpackage  Utility
 * @version     $Id: GeneralUtility.php 81677 2013-11-21 12:32:33Z mblaschke $
 */
class FrontendUtility {

    /**
     * Init TSFE with all needed classes eg. for backend usage ($GLOBALS['TSFE'])
     *
     * @param integer      $pageUid      PageUID
     * @param null|array   $rootLine     Rootline
     * @param null|array   $pageData     Page data array
     * @param null|array   $rootlineFull Full rootline
     * @param null|integer $sysLanguage  Sys language uid
     */
    public static function init($pageUid, $rootLine = NULL, $pageData = NULL, $rootlineFull = NULL, $sysLanguage = NULL) {
        static $cacheTSFE = array();
        static $lastTsSetupPid = NULL;

		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        // FIXME: add sys langauge or check if sys langauge is needed

        // Fetch page if needed
        if ($pageData === NULL ) {
            $sysPageObj = $objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');

            $pageData = $sysPageObj->getPage_noCheck($pageUid);
        }

        // create time tracker if needed
        if (empty($GLOBALS['TT'])) {
			/** @var \TYPO3\CMS\Core\TimeTracker\NullTimeTracker $timeTracker */
			$timeTracker = $objectManager->get('TYPO3\\CMS\\Core\\TimeTracker\\NullTimeTracker');

            $GLOBALS['TT'] = $timeTracker;
            $GLOBALS['TT']->start();
        }

        if ($rootLine === NULL) {
			/** @var \TYPO3\CMS\Frontend\Page\PageRepository $sysPageObj */
            $sysPageObj = $objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
            $rootLine   = $sysPageObj->getRootLine($pageUid);

            // save full rootline, we need it in TSFE
            $rootlineFull = $rootLine;
        }

        // Only setup tsfe if current instance must be changed
        if ($lastTsSetupPid !== $pageUid) {

            // Cache TSFE if possible to prevent reinit (is still slow but we need the TSFE)
            if (empty($cacheTSFE[$pageUid])) {
				/** @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $tsfeController */
				$tsfeController = $objectManager->get(
					'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
					$GLOBALS['TYPO3_CONF_VARS'],
					$pageUid,
					0
				);

				/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $cObjRenderer */
				$cObjRenderer = $objectManager->get('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

				/** @var \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService  $TSObj */
                $TSObj = $objectManager->get('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
                $TSObj->tt_track = 0;
                $TSObj->init();
                $TSObj->runThroughTemplates($rootLine);
                $TSObj->generateConfig();

                $_GET['id'] = $pageUid;

				// Init TSFE
				$GLOBALS['TSFE'] = $tsfeController;
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
     * Return current URL
     *
     * @return null|string
     */
    public static function getCurrentUrl() {
        $ret = NULL;
        if (!empty($GLOBALS['TSFE']->anchorPrefix)) {
            $ret = (string)$GLOBALS['TSFE']->anchorPrefix;
        } else {
            $ret = (string)$GLOBALS['TSFE']->siteScript;
        }

        return $ret;
    }

    /**
     * Check current page for blacklisting
     *
     * @param  array $blacklist Blacklist configuration
     * @return bool
     */
    public static function checkPageForBlacklist($blacklist) {
        return \Metaseo\Metaseo\Utility\GeneralUtility::checkUrlForBlacklisting(
            self::getCurrentUrl(),
            $blacklist
        );
    }

}
