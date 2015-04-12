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

use Metaseo\Metaseo\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Scheduler Task Sitemap Base
 */
abstract class AbstractTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Backend Form Protection object
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager = null;

    /**
     * Language lock
     *
     * @var integer
     */
    protected $languageLock = false;

    /**
     * Language list
     *
     * @var array
     */
    protected $languageIdList = null;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Initialize task
     */
    protected function initialize() {
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
    }

    /**
     * Get list of root pages in current typo3
     *
     * @return  array
     */
    protected function getRootPages() {
        $ret = array();

        $query
            = 'SELECT uid
                    FROM pages
                   WHERE is_siteroot = 1
                     AND deleted = 0';
        $ret = DatabaseUtility::getColWithIndex($query);

        return $ret;
    }


    /**
     * Get list of root pages in current typo3
     *
     * @return  array
     */
    protected function initLanguages() {
        $this->languageIdList[0] = 0;

        $query
            = 'SELECT uid
                    FROM sys_language
                   WHERE hidden = 0';
        $this->languageIdList = DatabaseUtility::getColWithIndex($query);
    }

    /**
     * Set root page language
     */
    protected function setRootPageLanguage($languageId) {
        $GLOBALS['TSFE']->tmpl->setup['config.']['sys_language_uid'] = $languageId;
        $this->languageLock = $languageId;
    }

    /**
     * Initalize root page (TSFE and stuff)
     *
     * @param   integer $rootPageId $rootPageId
     */
    protected function initRootPage($rootPageId) {
        \Metaseo\Metaseo\Utility\FrontendUtility::init($rootPageId);
    }

    /**
     * Write content to file
     *
     * @param   string $file    Filename/path
     * @param   string $content Content
     *
     * @throws  \Exception
     */
    protected function writeToFile($file, $content) {
        if (!function_exists('gzopen')) {
            throw new \Exception('metaseo needs zlib support');
        }

        $fp = gzopen($file, 'w');

        if ($fp) {
            gzwrite($fp, $content);
            gzclose($fp);
        } else {
            throw new \Exception('Could not open ' . $file . ' for writing');
        }
    }
}
