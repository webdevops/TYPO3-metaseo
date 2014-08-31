<?php
namespace Metaseo\Metaseo\Scheduler\Task;

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
 * Scheduler Task Sitemap Base
 *
 * @package     metaseo
 * @subpackage  Sitemap
 * @version     $Id: class.sitemap_base.php 78237 2013-07-23 14:50:31Z mblaschke $
 */
abstract class AbstractSitemapTask extends \Metaseo\Metaseo\Scheduler\Task\AbstractTask {

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Sitemap base directory
     *
     * @var string
     */
    protected $_sitemapDir = NULL;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Execute task
     */
    public function execute() {
        // Build sitemap

        $rootPageList = $this->_getRootPages();

        $this->_cleanupDirectory();

        $this->_initLanguages();


        foreach ($rootPageList as $uid => $page) {
            $this->_initRootPage($uid);

            if (\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue('is_sitemap_language_lock', FALSE, $uid)) {
                foreach ($this->_languageIdList as $languageId) {
                    $this->_setRootPageLanguage($languageId);
                    $this->_buildSitemap($uid, $languageId);
                }
            } else {
                $this->_buildSitemap($uid, NULL);
            }
        }

        return TRUE;
    }

    /**
     * Cleanup sitemap directory
     */
    protected function _cleanupDirectory() {
        if (empty($this->_sitemapDir)) {
            throw new \Exception('Basedir not set');
        }

        $fullPath = PATH_site . '/' . $this->_sitemapDir;

        if (!is_dir($fullPath)) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($fullPath);
        }

        foreach (new \DirectoryIterator($fullPath) as $file) {
            if ($file->isFile() && !$file->isDot()) {
                $fileName = $file->getFilename();
                unlink($fullPath . '/' . $fileName);
            }
        }
    }

    /**
     * Generate sitemap link template
     *
     * @param    string $template    File link template
     * @return    string
     */
    protected function _generateSitemapLinkTemplate($template) {
        $ret = NULL;

        // Set link template for index file
        $linkConf = array(
            'parameter' => $this->_sitemapDir . '/' . $template,
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


    // ########################################################################
    // Abstract Methods
    // ########################################################################

    /**
     * Build sitemap
     *
     * @param    integer $rootPageId    Root page id
     * @param    integer $languageId    Language id
     */
    abstract protected function _buildSitemap($rootPageId, $languageId);

}
