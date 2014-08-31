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
 * Sitemap XML generator
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: XmlGenerator.php 81080 2013-10-28 09:54:33Z mblaschke $
 */
class XmlGenerator extends \Metaseo\Metaseo\Sitemap\Generator\AbstractGenerator {

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Create sitemap index
     *
     * @return  string
     */
    public function sitemapIndex() {
        $pageLimit = 10000;

        if (isset($this->tsSetup['pageLimit']) && $this->tsSetup['pageLimit'] != '') {
            $pageLimit = (int)$this->tsSetup['pageLimit'];
        }

        $sitemaps  = array();
        $pageItems = count($this->sitemapPages);
        $pageCount = ceil($pageItems / $pageLimit);

        $linkConf = array(
            'parameter'        => \Metaseo\Metaseo\Utility\GeneralUtility::getCurrentPid() . ',' . $GLOBALS['TSFE']->type,
            'additionalParams' => '',
            'useCacheHash'     => 1,
        );

        for ($i = 0; $i < $pageCount; $i++) {
            if ($this->indexPathTemplate) {
                $link = \Metaseo\Metaseo\Utility\GeneralUtility::fullUrl(
                    str_replace('###PAGE###', $i, $this->indexPathTemplate)
                );

                $sitemaps[] = $link;
            } else {
                $linkConf['additionalParams'] = '&page=' . ($i + 1);

                $sitemaps[] = \Metaseo\Metaseo\Utility\GeneralUtility::fullUrl(
                    $GLOBALS['TSFE']->cObj->typoLink_URL($linkConf)
                );
            }
        }

        $ret = '<?xml version="1.0" encoding="UTF-8"?>';
        $ret .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $ret .= ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('sitemap-xml-index-sitemap-list', $this, $sitemaps);

        foreach ($sitemaps as $sitemapPage) {
            $ret .= '<sitemap><loc>' . htmlspecialchars($sitemapPage) . '</loc></sitemap>';
        }

        $ret .= '</sitemapindex>';

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('sitemap-xml-index-output', $this, $ret);

        return $ret;
    }

    /**
     * Create sitemap (for page)
     *
     * @param   integer $page   Page
     * @return  string
     */
    public function sitemap($page = NULL) {
        $ret = '';

        $pageLimit = 10000;

        if (isset($this->tsSetup['pageLimit']) && $this->tsSetup['pageLimit'] != '') {
            $pageLimit = (int)$this->tsSetup['pageLimit'];
        }

        $pageItems     = count($this->sitemapPages);
        $pageItemBegin = $pageLimit * ($page - 1);
        $pageCount     = ceil($pageItems / $pageLimit);


        if ($pageItemBegin <= $pageItems) {
            $this->sitemapPages = array_slice($this->sitemapPages, $pageItemBegin, $pageLimit);
            $ret                = $this->createSitemapPage($page);
        }

        return $ret;
    }

    /**
     * Create Sitemap Page
     *
     * @return string
     */
    protected function createSitemapPage() {
        $ret = '<?xml version="1.0" encoding="UTF-8"?>';
        $ret .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $ret .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $ret .= ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9';
        $ret .= ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        $pagePriorityDefaultValue     = (float)\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue(
            'sitemap_priorty',
            0
        );
        $pagePriorityDepthMultiplier  = (float)\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue(
            'sitemap_priorty_depth_multiplier',
            0
        );
        $pagePriorityDepthModificator = (float)\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue(
            'sitemap_priorty_depth_modificator',
            0
        );

        if ($pagePriorityDefaultValue == 0) {
            $pagePriorityDefaultValue = 1;
        }

        if ($pagePriorityDepthMultiplier == 0) {
            $pagePriorityDepthMultiplier = 1;
        }

        if ($pagePriorityDepthModificator == 0) {
            $pagePriorityDepthModificator = 1;
        }


        // #####################
        // SetupTS conf
        // #####################

        foreach ($this->sitemapPages as $sitemapPage) {
            if (empty($this->pages[$sitemapPage['page_uid']])) {
                // invalid page
                continue;
            }

            $page = $this->pages[$sitemapPage['page_uid']];

            // #####################################
            // Page priority
            // #####################################
            $pageDepth     = $sitemapPage['page_depth'];
            $pageDepthBase = 1;

            if (!empty($sitemapPage['page_hash'])) {
                // page has module-content - trade as subpage
                ++$pageDepth;
            }

            $pageDepth -= $pagePriorityDepthModificator;


            if ($pageDepth > 0.1) {
                $pageDepthBase = 1 / $pageDepth;
            }

            $pagePriority = $pagePriorityDefaultValue * ($pageDepthBase * $pagePriorityDepthMultiplier);
            if (!empty($page['tx_metaseo_priority'])) {
                $pagePriority = $page['tx_metaseo_priority'] / 100;
            }

            $pagePriority = number_format($pagePriority, 2);

            if ($pagePriority > 1) {
                $pagePriority = '1.00';
            } elseif ($pagePriority <= 0) {
                $pagePriority = '0.00';
            }

            // #####################################
            // Page informations
            // #####################################

            // page Url
            $pageUrl = \Metaseo\Metaseo\Utility\GeneralUtility::fullUrl($sitemapPage['page_url']);

            // Page modification date
            $pageModifictionDate = date('c', $sitemapPage['tstamp']);

            // Page change frequency
            $pageChangeFrequency = NULL;
            if (!empty($page['tx_metaseo_change_frequency'])) {
                $pageChangeFrequency = (int)$page['tx_metaseo_change_frequency'];
            } elseif (!empty($sitemapPage['page_change_frequency'])) {
                $pageChangeFrequency = (int)$sitemapPage['page_change_frequency'];
            }

            if (!empty($pageChangeFrequency) && !empty($this->pageChangeFrequency[$pageChangeFrequency])) {
                $pageChangeFrequency = $this->pageChangeFrequency[$pageChangeFrequency];
            } else {
                $pageChangeFrequency = NULL;
            }


            // #####################################
            // Sitemal page output
            // #####################################
            $ret .= '<url>';
            $ret .= '<loc>' . htmlspecialchars($pageUrl) . '</loc>';
            $ret .= '<lastmod>' . $pageModifictionDate . '</lastmod>';

            if (!empty($pageChangeFrequency)) {
                $ret .= '<changefreq>' . htmlspecialchars($pageChangeFrequency) . '</changefreq>';
            }

            $ret .= '<priority>' . $pagePriority . '</priority>';

            $ret .= '</url>';
        }


        $ret .= '</urlset>';

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('sitemap-xml-page-output', $this, $ret);

        return $ret;
    }

}
