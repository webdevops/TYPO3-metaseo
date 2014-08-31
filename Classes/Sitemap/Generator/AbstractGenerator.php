<?php
namespace Metaseo\Metaseo\Sitemap\Generator;

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
 * Sitemap abstract generator
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: AbstractGenerator.php 81677 2013-11-21 12:32:33Z mblaschke $
 */
abstract class AbstractGenerator {
    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Current root pid
     *
     * @var integer
     */
    public $rootPid = NULL;

    /**
     * Sitemap pages
     *
     * @var array
     */
    public $sitemapPages = array();

    /**
     * Page lookups
     *
     * @var array
     */
    public $pages = array();

    /**
     * Extension configuration
     *
     * @var array
     */
    protected $extConf = array();

    /**
     * Extension setup configuration
     *
     * @var array
     */
    public $tsSetup = array();

    /**
     * Page change frequency definition list
     *
     * @var array
     */
    public $pageChangeFrequency = array(
        1 => 'always',
        2 => 'hourly',
        3 => 'daily',
        4 => 'weekly',
        5 => 'monthly',
        6 => 'yearly',
        7 => 'never',
    );

    /**
     * Link template for sitemap index
     *
     * Replacemennt marker ###PAGE### for page-uid
     *
     * @var string|boolean
     */
    public $indexPathTemplate = FALSE;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Fetch sitemap information and generate sitemap
     */
    public function __construct() {
        // INIT
        $this->rootPid = \Metaseo\Metaseo\Utility\GeneralUtility::getRootPid();
        $sysLanguageId = NULL;

        $this->tsSetup = $GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.']['sitemap.'];

        // Language limit via setupTS
        if (\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue('is_sitemap_language_lock', FALSE)) {
            $sysLanguageId = \Metaseo\Metaseo\Utility\GeneralUtility::getLanguageId();
        }

        // Fetch sitemap list/pages
        $list = \Metaseo\Metaseo\Utility\SitemapUtility::getList($this->rootPid, $sysLanguageId);

        $this->sitemapPages = $list['tx_metaseo_sitemap'];
        $this->pages        = $list['pages'];

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('sitemap-setup', $this);
    }

    /**
     * Return page count
     *
     * @return integer
     */
    public function pageCount() {
        $pageLimit = \Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue('sitemap_page_limit', NULL);

        if (empty($pageLimit)) {
            $pageLimit = 1000;
        }

        $pageItems = count($this->sitemapPages);
        $pageCount = ceil($pageItems / $pageLimit);

        return $pageCount;
    }

    // ########################################################################
    // Abstract methods
    // ########################################################################

    /**
     * Create sitemap index
     *
     * @return string
     */
    abstract public function sitemapIndex();

    /**
     * Create sitemap (for page)
     *
     * @param   integer $page   Page
     * @return  string
     */
    abstract public function sitemap($page = NULL);

}
