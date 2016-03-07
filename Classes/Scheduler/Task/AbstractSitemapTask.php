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

namespace Metaseo\Metaseo\Scheduler\Task;

use Metaseo\Metaseo\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;

/**
 * Scheduler Task Sitemap Base
 */
abstract class AbstractSitemapTask extends AbstractTask
{

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Sitemap base directory
     *
     * @var string
     */
    protected $sitemapDir;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Execute task
     */
    public function execute()
    {
        // Build sitemap

        $this->initialize();
        $rootPageList = $this->getRootPages();
        $this->cleanupDirectory();
        $this->initLanguages();

        foreach ($rootPageList as $uid => $page) {
            $this->initRootPage($uid);

            if (GeneralUtility::getRootSettingValue('is_sitemap_language_lock', false, $uid)) {
                foreach ($this->languageIdList as $languageId) {
                    $this->setRootPageLanguage($languageId);
                    $this->buildSitemap($uid, $languageId);
                }
            } else {
                $this->buildSitemap($uid, null);
            }
        }

        return true;
    }

    /**
     * Cleanup sitemap directory
     */
    protected function cleanupDirectory()
    {
        if (empty($this->sitemapDir)) {
            throw new \Exception('Basedir not set');
        }

        $fullPath = PATH_site . '/' . $this->sitemapDir;

        if (!is_dir($fullPath)) {
            Typo3GeneralUtility::mkdir($fullPath);
        }

        foreach (new \DirectoryIterator($fullPath) as $file) {
            if ($file->isFile() && !$file->isDot()) {
                $fileName = $file->getFilename();
                unlink($fullPath . '/' . $fileName);
            }
        }
    }

    /**
     * Build sitemap
     *
     * @param    integer $rootPageId Root page id
     * @param    integer $languageId Language id
     */
    abstract protected function buildSitemap($rootPageId, $languageId);


    // ########################################################################
    // Abstract Methods
    // ########################################################################

    /**
     * Generate sitemap link template
     *
     * @param    string $template File link template
     *
     * @return    string
     */
    protected function generateSitemapLinkTemplate($template)
    {
        $ret = null;

        // Set link template for index file
        $linkConf = array(
            'parameter' => $this->sitemapDir . '/' . $template,
        );

        if (strlen($GLOBALS['TSFE']->baseUrl) > 1) {
            $ret = $GLOBALS['TSFE']->baseUrlWrap($GLOBALS['TSFE']->cObj->typoLink_URL($linkConf));
        } elseif (strlen($GLOBALS['TSFE']->absRefPrefix) > 1) {
            $ret = $GLOBALS['TSFE']->absRefPrefix . $GLOBALS['TSFE']->cObj->typoLink_URL($linkConf);
        } else {
            $ret = $GLOBALS['TSFE']->cObj->typoLink_URL($linkConf);
        }

        return $ret;
    }
}
