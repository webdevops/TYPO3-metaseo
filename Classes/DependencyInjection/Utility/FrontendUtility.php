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

namespace Metaseo\Metaseo\DependencyInjection\Utility;

use Metaseo\Metaseo\Utility\FrontendUtility as MetaseoFrontendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Page\PageRepository;

class FrontendUtility implements SingletonInterface
{
    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * Init TSFE (for simulated pagetitle)
     *
     * @param   array        $page         Page
     * @param   null|array   $rootLine     Rootline
     * @param   null|array   $pageData     Page data (recursive generated)
     * @param   null|array   $rootlineFull Rootline full
     * @param   null|integer $sysLanguage  System language
     *
     * @return  void
     */
    public function initTsfe(
        array $page,
        array $rootLine = null,
        array $pageData = null,
        array $rootlineFull = null,
        $sysLanguage = null
    ) {
        $pageUid = (int)$page['uid'];

        if ($rootLine === null) {
            $rootLine   = $this->pageRepository->getRootLine($pageUid);

            // save full rootline, we need it in TSFE
            $rootlineFull = $rootLine;
        }

        // check if current page has a ts-setup-template
        // if not, we go down the tree to the parent page
        if (count($rootLine) >= 2 && !empty($this->templatePidList) && empty($this->templatePidList[$pageUid])) {
            // go to parent page in rootline
            reset($rootLine);
            next($rootLine);
            $prevPage = current($rootLine);

            // strip current page from rootline
            reset($rootLine);
            $currPageIndex = key($rootLine);
            unset($rootLine[$currPageIndex]);

            MetaseoFrontendUtility::init(
                $prevPage['uid'],
                $rootLine,
                $pageData,
                $rootlineFull,
                $sysLanguage
            );
        }

        MetaseoFrontendUtility::init($page['uid'], $rootLine, $pageData, $rootlineFull, $sysLanguage);
    }

    /**
     * @param array $conf
     *
     * @return string
     */
    public function getTypoLinkUrl(array $conf)
    {
        return $this->getTSFE()->cObj->typolink_URL($conf);
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTSFE()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @param PageRepository $pageRepository
     *
     * @return $this
     */
    public function setPageRepository(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;

        return $this;
    }
}
