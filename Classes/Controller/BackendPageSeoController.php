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

namespace Metaseo\Metaseo\Controller;

use Metaseo\Metaseo\Backend\Module\AbstractStandardModule;
use Metaseo\Metaseo\Controller\Ajax\AbstractPageSeoController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\GeoController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\MetaDataController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\PageTitleController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\PageTitleSimController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\SearchEnginesController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\UrlController;
use Metaseo\Metaseo\Utility\DatabaseUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 Backend module page seo
 */
class BackendPageSeoController extends AbstractStandardModule
{
    // ########################################################################
    // Attributes
    // ########################################################################

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Main action
     */
    public function mainAction()
    {
        $this->handleSubAction(MetaDataController::LIST_TYPE);
    }

    /**
     * Geo action
     */
    public function geoAction()
    {
        $this->handleSubAction(GeoController::LIST_TYPE);
    }

    /**
     * searchengines action
     */
    public function searchenginesAction()
    {
        $this->handleSubAction(SearchEnginesController::LIST_TYPE);
    }

    /**
     * url action
     */
    public function urlAction()
    {
        $this->handleSubAction(UrlController::LIST_TYPE);
    }

    /**
     * pagetitle action
     */
    public function pagetitleAction()
    {
        $this->handleSubAction(PageTitleController::LIST_TYPE);
    }

    /**
     * pagetitlesim action
     */
    public function pagetitlesimAction()
    {
        $this->handleSubAction(PageTitleSimController::LIST_TYPE);
    }

    /**
     * @param string $listType
     */
    protected function handleSubAction($listType)
    {
        $pageId = (int) GeneralUtility::_GP('id');

        if (empty($pageId)) {
            $this->addFlashMessage(
                $this->translate('message.warning.no_valid_page.message'),
                $this->translate('message.warning.no_valid_page.title'),
                FlashMessage::WARNING
            );

            return;
        }

        // Load PageTS
        $pageTsConf = BackendUtility::getPagesTSconfig($pageId);

        // Build language list
        $defaultLanguageText = $this->translate('default.language');

        $languageFullList = array(
            0 => array(
                'label' => $this->translate('default.language'),
                'flag'  => '',
            ),
        );

        if (!empty($pageTsConf['mod.']['SHARED.']['defaultLanguageFlag'])) {
            $languageFullList[0]['flag'] = $pageTsConf['mod.']['SHARED.']['defaultLanguageFlag'];
        }

        if (!empty($pageTsConf['mod.']['SHARED.']['defaultLanguageLabel'])) {
            $label = $pageTsConf['mod.']['SHARED.']['defaultLanguageLabel'];

            $languageFullList[0]['label'] = $this->translate('default.language.named', array($label));

            $defaultLanguageText = $pageTsConf['mod.']['SHARED.']['defaultLanguageLabel'];
        }

        // Fetch other flags
        $query   = 'SELECT uid,
                           title,
                           flag
                      FROM sys_language
                     WHERE hidden = 0';
        $rowList = DatabaseUtility::getAll($query);
        foreach ($rowList as $row) {
            $languageFullList[$row['uid']] = array(
                'label' => htmlspecialchars($row['title']),
                'flag'  => htmlspecialchars($row['flag']),
            );
        }

        // Languages
        $languageList = array();

        foreach ($languageFullList as $langId => $langRow) {
            $flag = '';

            // Flag (if available)
            if (!empty($langRow['flag'])) {
                $flag .= IconUtility::getSpriteIcon('flags-' . $langRow['flag']);
                $flag .= '&nbsp;';
            }

            // label
            $label = $langRow['label'];

            $languageList[] = array(
                $langId,
                $label,
                $flag
            );
        }

        $sysLanguageDefault = (int)$GLOBALS['BE_USER']->getSessionData('MetaSEO.sysLanguage');

        if (empty($sysLanguageDefault)) {
            $sysLanguageDefault = 0;
        }

        // ############################
        // HTML
        // ############################

        $realUrlAvailable = ExtensionManagementUtility::isLoaded('realurl');

        $ajaxController = AbstractPageSeoController::AJAX_PREFIX . $listType;

        if (!array_key_exists($ajaxController, AbstractPageSeoController::getBackendAjaxClassNames())) {
            throw new \RuntimeException('Ajax controller with this name was not registered by MetaSEO.');
        }

        $metaSeoConf = array(
            'ajaxController'   => $ajaxController,
            'pid'              => (int)$pageId,
            'renderTo'         => 'tx-metaseo-sitemap-grid',
            'pagingSize'       => $this->getUiPagingSize(),
            'depth'            => 2,
            'sortField'        => 'crdate',
            'sortDir'          => 'DESC',
            'filterIcon'       => IconUtility::getSpriteIcon(
                'actions-system-tree-search-open'
            ),
            'dataLanguage'     => $languageList,
            'sysLanguage'      => $sysLanguageDefault,
            'listType'         => $listType,
            'criteriaFulltext' => '',
            'realurlAvailable' => $realUrlAvailable,
            'sprite'           => array(
                'edit'   => $this->getIcon('actions-document-open'),
                'info'   => $this->getIcon('actions-document-info'),
                'editor' => $this->getIcon('actions-system-options-view'),
            ),
        );


        $metaSeoLang = array(
            'pagingMessage'                    => 'pager.results',
            'pagingEmpty'                      => 'pager.noresults',
            'boolean_yes'                      => 'boolean.yes',
            'boolean_no'                       => 'boolean.no',
            'button_save'                      => 'button.save',
            'button_saverecursively'           => 'button.saverecursively',
            'button_saverecursively_tooltip'   => 'button.saverecursively.tooltip',
            'button_cancel'                    => 'button.cancel',
            'labelDepth'                       => 'label.depth',
            'labelSearchFulltext'              => 'label.search.fulltext',
            'emptySearchFulltext'              => 'empty.search.fulltext',
            'labelSearchPageLanguage'          => 'label.search.page_language',
            'emptySearchPageLanguage'          => '',
            'page_uid'                         => 'header.sitemap.page_uid',
            'page_title'                       => 'header.sitemap.page_title',
            'page_keywords'                    => 'header.sitemap.page_keywords',
            'page_description'                 => 'header.sitemap.page_description',
            'page_abstract'                    => 'header.sitemap.page_abstract',
            'page_author'                      => 'header.sitemap.page_author',
            'page_author_email'                => 'header.sitemap.page_author_email',
            'page_lastUpdated'                 => 'header.sitemap.page_lastUpdated',
            'page_geo_lat'                     => 'header.sitemap.page_geo_lat',
            'page_geo_long'                    => 'header.sitemap.page_geo_long',
            'page_geo_place'                   => 'header.sitemap.page_geo_place',
            'page_geo_region'                  => 'header.sitemap.page_geo_region',
            'page_tx_metaseo_pagetitle'        => 'header.sitemap.page_tx_metaseo_pagetitle',
            'page_tx_metaseo_pagetitle_rel'    => 'header.sitemap.page_tx_metaseo_pagetitle_rel',
            'page_tx_metaseo_pagetitle_prefix' => 'header.sitemap.page_tx_metaseo_pagetitle_prefix',
            'page_tx_metaseo_pagetitle_suffix' => 'header.sitemap.page_tx_metaseo_pagetitle_suffix',
            'page_title_simulated'             => 'header.pagetitlesim.title_simulated',
            'page_searchengine_canonicalurl'   => 'header.searchengine_canonicalurl',
            'page_searchengine_is_exclude'     => 'header.searchengine_is_excluded',
            'searchengine_is_exclude_disabled' => 'searchengine.is_exclude_disabled',
            'searchengine_is_exclude_enabled'  => 'searchengine.is_exclude_enabled',
            'page_sitemap_priority'            => 'header.sitemap.priority',
            'page_url_scheme'                  => 'header.url_scheme',
            'page_url_scheme_default'          => 'page.url_scheme_default',
            'page_url_scheme_http'             => 'page.url_scheme_http',
            'page_url_scheme_https'            => 'page.url_scheme_https',
            'page_url_alias'                   => 'header.url_alias',
            'page_url_realurl_pathsegment'     => 'header.url_realurl_pathsegment',
            'page_url_realurl_pathoverride'    => 'header.url_realurl_pathoverride',
            'page_url_realurl_exclude'         => 'header.url_realurl_exclude',
            'qtip_pagetitle_simulate'          => 'qtip.pagetitle_simulate',
            'qtip_url_simulate'                => 'qtip.url_simulate',
            'metaeditor_title'                 => 'metaeditor.title',
            'metaeditor_tab_opengraph'         => 'metaeditor.tab.opengraph',
            'metaeditor_button_hin'            => 'metaeditor.button.hint',
            'value_from_base'                  => 'value.from_base',
            'value_from_overlay'               => 'value.from_overlay',
            'value_only_base'                  => 'value.only_base',
            'value_default'                    => 'value_default',
        );

        // translate list
        $metaSeoLang                            = $this->translateList($metaSeoLang);
        $metaSeoLang['emptySearchPageLanguage'] = $defaultLanguageText;

        $this->view->assign(
            'JavaScript',
            'Ext.namespace("MetaSeo.overview");
            MetaSeo.overview.conf      = ' . json_encode($metaSeoConf) . ';
            MetaSeo.overview.conf.lang = ' . json_encode($metaSeoLang) . ';
        '
        );
    }

    private function getIcon($iconName)
    {
        $html = IconUtility::getSpriteIcon($iconName);
        //Ugly workaround to make icons clickable in TYPO3 7.6.
        //It's deprecated anyways, therefore changes in the future when we drop support for 6.2.
        if (stripos('svg', $html)) {
            //not found
            return $html; //Typo3 6.2. That works.
        } else {
            //found => TYPO3 7 workaround must be applied.
            switch ($iconName) {
                case 'actions-document-open':
                    return '<span class="t3-icon t3-icon-actions '
                        . 't3-icon-actions-document t3-icon-document-open"></span>';
                case 'actions-document-info':
                    return '<span class="t3-icon t3-icon-actions '
                    . ' t3-icon-actions-document t3-icon-document-info"></span>';
                case 'actions-system-options-view':
                    return '<span class="t3-icon t3-icon-actions '
                    . 't3-icon-actions-document t3-icon-document-view"></span>';
            }
        }
    }
}
