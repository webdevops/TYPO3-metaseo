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

/**
 * Sitemap XML generator
 */
class XmlGenerator extends AbstractGenerator
{

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Create sitemap index
     *
     * @return  string
     */
    public function sitemapIndex()
    {
        $pageLimit = 10000;

        if (isset($this->tsSetup['pageLimit']) && $this->tsSetup['pageLimit'] != '') {
            $pageLimit = (int)$this->tsSetup['pageLimit'];
        }

        $sitemaps  = array();
        $pageItems = count($this->sitemapPages);
        $pageCount = ceil($pageItems / $pageLimit);

        $linkConf = array(
            'parameter'        => GeneralUtility::getCurrentPid() . ',' . $GLOBALS['TSFE']->type,
            'additionalParams' => '',
            'useCacheHash'     => 1,
        );

        for ($i = 0; $i < $pageCount; $i++) {
            if ($this->indexPathTemplate) {
                $link = GeneralUtility::fullUrl(str_replace('###PAGE###', $i, $this->indexPathTemplate));

                $sitemaps[] = $link;
            } else {
                $linkConf['additionalParams'] = '&page=' . ($i + 1);

                $sitemaps[] = GeneralUtility::fullUrl($GLOBALS['TSFE']->cObj->typoLink_URL($linkConf));
            }
        }

        $ret = '<?xml version="1.0" encoding="UTF-8"?>';
        $ret .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $ret .= ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 '
            . 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'sitemapXmlIndexSitemapList', $this, $sitemaps);

        foreach ($sitemaps as $sitemapPage) {
            $ret .= '<sitemap><loc>' . htmlspecialchars($sitemapPage) . '</loc></sitemap>';
        }

        $ret .= '</sitemapindex>';

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'sitemapXmlIndexOutput', $this, $ret);

        return $ret;
    }

    /**
     * Create sitemap (for page)
     *
     * @param   integer $page Page
     *
     * @return  string
     */
    public function sitemap($page = null)
    {
        $ret = '';

        $pageLimit = 10000;

        if (isset($this->tsSetup['pageLimit']) && $this->tsSetup['pageLimit'] != '') {
            $pageLimit = (int)$this->tsSetup['pageLimit'];
        }

        $pageItems     = count($this->sitemapPages);
        $pageItemBegin = $pageLimit * ($page - 1);

        if ($pageItemBegin <= $pageItems) {
            $this->sitemapPages = array_slice($this->sitemapPages, $pageItemBegin, $pageLimit);

            $ret = $this->createSitemapPage();
        }

        return $ret;
    }

    /**
     * Create Sitemap Page
     *
     * @return string
     */
    protected function createSitemapPage()
    {
        $ret = '<?xml version="1.0" encoding="UTF-8"?>';
        $ret .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $ret .= ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"';
        $ret .= ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9';
        $ret .= ' http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        $pagePriorityDefaultValue     = (float)GeneralUtility::getRootSettingValue('sitemap_priority', 0);
        $pagePriorityDepthMultiplier  = (float)GeneralUtility::getRootSettingValue(
            'sitemap_priority_depth_multiplier',
            0
        );
        $pagePriorityDepthModificator = (float)GeneralUtility::getRootSettingValue(
            'sitemap_priority_depth_modificator',
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
            // Page information
            // #####################################

            // page Url
            $pageUrl = GeneralUtility::fullUrl($sitemapPage['page_url']);

            // Page modification date
            $pageModificationDate = date('c', $sitemapPage['tstamp']);

            // Page change frequency
            $pageChangeFrequency = null;
            if (!empty($page['tx_metaseo_change_frequency'])) {
                // from page
                $pageChangeFrequency = (int)$page['tx_metaseo_change_frequency'];
            } elseif (!empty($sitemapPage['page_change_frequency'])) {
                // from sitemap settings
                $pageChangeFrequency = (int)$sitemapPage['page_change_frequency'];
            } elseif (!empty($this->tsSetup['changeFrequency'])) {
                // default from SetupTS
                $pageChangeFrequency = (int)$this->tsSetup['changeFrequency'];
            }

            // translate change frequency
            if (!empty($pageChangeFrequency) && !empty($this->pageChangeFrequency[$pageChangeFrequency])) {
                $pageChangeFrequency = $this->pageChangeFrequency[$pageChangeFrequency];
            } else {
                $pageChangeFrequency = null;
            }

            // #####################################
            // Sitemal page output
            // #####################################
            $ret .= '<url>';
            $ret .= '<loc>' . htmlspecialchars($pageUrl) . '</loc>';
            $ret .= '<lastmod>' . $pageModificationDate . '</lastmod>';

            if (!empty($pageChangeFrequency)) {
                $ret .= '<changefreq>' . htmlspecialchars($pageChangeFrequency) . '</changefreq>';
            }

            $ret .= '<priority>' . $pagePriority . '</priority>';

            $ret .= '</url>';
        }


        $ret .= '</urlset>';

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'sitemapXmlPageOutput', $this, $ret);

        return $ret;
    }
}
