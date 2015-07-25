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

namespace Metaseo\Metaseo\Sitemap\Generator;

use Metaseo\Metaseo\Utility\GeneralUtility;
use Metaseo\Metaseo\Utility\SitemapUtility;

/**
 * Sitemap abstract generator
 */
abstract class AbstractGenerator
{
    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Current root pid
     *
     * @var integer
     */
    public $rootPid;

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
     * Replacement marker ###PAGE### for page-uid
     *
     * @var string|boolean
     */
    public $indexPathTemplate = false;
    /**
     * Extension configuration
     *
     * @var array
     */
    protected $extConf = array();

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Fetch sitemap information and generate sitemap
     */
    public function __construct()
    {
        // INIT
        $this->rootPid = GeneralUtility::getRootPid();
        $sysLanguageId = null;

        $this->tsSetup = $GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.']['sitemap.'];

        // Language limit via setupTS
        if (GeneralUtility::getRootSettingValue('is_sitemap_language_lock', false)) {
            $sysLanguageId = GeneralUtility::getLanguageId();
        }

        // Fetch sitemap list/pages
        $list = SitemapUtility::getList($this->rootPid, $sysLanguageId);

        $this->sitemapPages = $list['tx_metaseo_sitemap'];
        $this->pages        = $list['pages'];

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'sitemapSetup', $this);
    }

    /**
     * Return page count
     *
     * @return integer
     */
    public function pageCount()
    {
        $pageLimit = GeneralUtility::getRootSettingValue('sitemap_page_limit', null);

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
     * @param   integer $page Page
     *
     * @return  string
     */
    abstract public function sitemap($page = null);
}
