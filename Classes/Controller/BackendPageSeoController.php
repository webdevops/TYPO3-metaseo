<?php
namespace Metaseo\Metaseo\Controller;

use Metaseo\Metaseo\Utility\DatabaseUtility;

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
 * TYPO3 Backend module page seo
 *
 * @package     TYPO3
 * @subpackage  metaseo
 */
class BackendPageSeoController extends \Metaseo\Metaseo\Backend\Module\AbstractStandardModule {
    // ########################################################################
    // Attributes
    // ########################################################################

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Main action
     */
    public function mainAction() {
        return $this->_handleSubAction('metadata');
    }

    /**
     * Geo action
     */
    public function geoAction() {
        return $this->_handleSubAction('geo');
    }

    /**
     * searchengines action
     */
    public function searchenginesAction() {
        return $this->_handleSubAction('searchengines');
    }

    /**
     * url action
     */
    public function urlAction() {
        return $this->_handleSubAction('url');
    }

    /**
     * pagetitle action
     */
    public function pagetitleAction() {
        return $this->_handleSubAction('pagetitle');
    }

    /**
     * pagetitle action
     */
    public function pagetitlesimAction() {
        return $this->_handleSubAction('pagetitlesim');
    }

    protected function _handleSubAction($type) {
        $pageId		= (int)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');

        if( empty($pageId) ) {
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                $this->_translate('message.warning.no_valid_page.message'),
                $this->_translate('message.warning.no_valid_page.title'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
            \TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($message);
            return;
        }

        // Load PageTS
        $pageTsConf = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($pageId);


        // Build langauge list
        $defaultLanguageText = $this->_translate('default.language');

        $languageFullList = array(
            0 => array(
                'label'	=> $this->_translate('default.language'),
                'flag'	=> '',
            ),
        );

        if( !empty($pageTsConf['mod.']['SHARED.']['defaultLanguageFlag']) ) {
            $languageFullList[0]['flag'] = $pageTsConf['mod.']['SHARED.']['defaultLanguageFlag'];
        }


        if (!empty($pageTsConf['mod.']['SHARED.']['defaultLanguageLabel']) ) {
            $label = $pageTsConf['mod.']['SHARED.']['defaultLanguageLabel'];

            $languageFullList[0]['label'] = $this->_translate('default.language.named', array($label));

            $defaultLanguageText = $pageTsConf['mod.']['SHARED.']['defaultLanguageLabel'];
        }

        // Fetch other flags
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid, title, flag',
            'sys_language',
            'hidden = 0'
        );
        while( $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) ) {
            $languageFullList[$row['uid']] = array(
                'label'	=> htmlspecialchars($row['title']),
                'flag'	=> htmlspecialchars($row['flag']),
            );
        }

        // Langauges
        $languageList = array();

        foreach($languageFullList as $langId => $langRow) {
            $flag = '';

            // Flag (if available)
            if( !empty($langRow['flag']) ) {
                $flag .= '<span class="t3-icon t3-icon-flags t3-icon-flags-'.$langRow['flag'].' t3-icon-'.$langRow['flag'].'"></span>';
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

        $sysLangaugeDefault = (int)$GLOBALS['BE_USER']->getSessionData('MetaSEO.sysLanguage');

        if( empty($sysLangaugeDefault) ) {
            $sysLangaugeDefault = 0;
        }

        // ############################
        // HTML
        // ############################
        // FIXME: do we really need a template engine here?
        $this->template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
        $pageRenderer = $this->template->getPageRenderer();

        $pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/JavaScript/Ext.ux.plugin.FitToParent.js');
        $pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/JavaScript/MetaSeo.js');
        $pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/JavaScript/MetaSeo.overview.js');
        $pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/JavaScript/MetaSeo.metaeditor.js');
        $pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/JavaScript/MetaSeo.metaeditor.fields.js');
        $pageRenderer->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/Css/Default.css');

        $realUrlAvailable = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl');


        $metaSeoConf = array(
            'sessionToken'   => $this->_sessionToken('metaseo_metaseo_backend_ajax_pageajax'),
            'ajaxController' => $this->_ajaxControllerUrl('tx_metaseo_backend_ajax::page'),
            'pid'            => (int)$pageId,
            'renderTo'       => 'tx-metaseo-sitemap-grid',

            'pagingSize'     => 50,

            'depth'          => 2,
            'sortField'      => 'crdate',
            'sortDir'        => 'DESC',

            'filterIcon'     => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-tree-search-open'),

            'dataLanguage'   => $languageList,
            'sysLanguage'    => $sysLangaugeDefault,
            'listType'       => $type,

            'criteriaFulltext' => '',

            'realurlAvailable' => $realUrlAvailable,

            'sprite' => array(
                'edit'   => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open'),
                'info'   => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-info'),
                'editor' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-options-view'),
            ),
        );


        $metaSeoLang = array(
            'pagingMessage' => 'pager.results',
            'pagingEmpty'   => 'pager.noresults',

            'boolean_yes' => 'boolean.yes',
            'boolean_no'  => 'boolean.no',

            'button_save'  => 'button.save',
            'button_cancel' => 'button.cancel',

            'labelDepth' => 'label.depth',

            'labelSearchFulltext' => 'label.search.fulltext',
            'emptySearchFulltext' => 'empty.search.fulltext',

            'labelSearchPageLanguage' => 'label.search.page_language',
            'emptySearchPageLanguage' => '',

            'page_uid'          => 'header.sitemap.page_uid',
            'page_title'        => 'header.sitemap.page_title',
            'page_keywords'     => 'header.sitemap.page_keywords',
            'page_description'  => 'header.sitemap.page_description',
            'page_abstract'     => 'header.sitemap.page_abstract',
            'page_author'       => 'header.sitemap.page_author',
            'page_author_email' => 'header.sitemap.page_author_email',
            'page_lastupdated'  => 'header.sitemap.page_lastupdated',

            'page_geo_lat'    => 'header.sitemap.page_geo_lat',
            'page_geo_long'   => 'header.sitemap.page_geo_long',
            'page_geo_place'  => 'header.sitemap.page_geo_place',
            'page_geo_region' => 'header.sitemap.page_geo_region',

            'page_tx_metaseo_pagetitle'        => 'header.sitemap.page_tx_metaseo_pagetitle',
            'page_tx_metaseo_pagetitle_rel'    => 'header.sitemap.page_tx_metaseo_pagetitle_rel',
            'page_tx_metaseo_pagetitle_prefix' => 'header.sitemap.page_tx_metaseo_pagetitle_prefix',
            'page_tx_metaseo_pagetitle_suffix' => 'header.sitemap.page_tx_metaseo_pagetitle_suffix',

            'page_title_simulated' => 'header.pagetitlesim.title_simulated',

            'page_searchengine_canonicalurl'   => 'header.searchengine_canonicalurl',
            'page_searchengine_is_exclude'     => 'header.searchengine_is_excluded',
            'searchengine_is_exclude_disabled' => 'searchengine.is_exclude_disabled',
            'searchengine_is_exclude_enabled'  => 'searchengine.is_exclude_enabled',

            'page_sitemap_priority' => 'header.sitemap.priority',

            'page_url_scheme'               => 'header.url_scheme',
            'page_url_scheme_default'       => 'page.url_scheme_default',
            'page_url_scheme_http'          => 'page.url_scheme_http',
            'page_url_scheme_https'         => 'page.url_scheme_https',
            'page_url_alias'                => 'header.url_alias',
            'page_url_realurl_pathsegment'  => 'header.url_realurl_pathsegment',
            'page_url_realurl_pathoverride' => 'header.url_realurl_pathoverride',
            'page_url_realurl_exclude'      => 'header.url_realurl_exclude',

            'qtip_pagetitle_simulate' => 'qtip.pagetitle_simulate',
            'qtip_url_simulate'       => 'qtip.url_simulate',

            'metaeditor_title'         => 'metaeditor.title',
            'metaeditor_tab_opengraph' => 'metaeditor.tab.opengraph',
            'metaeditor_button_hin'    => 'metaeditor.button.hint',

            'value_from_base'    => 'value.from_base',
            'value_from_overlay' => 'value.from_overlay',
            'value_only_base'    => 'value.only_base',

            'value_default' => 'value_default',
        );

        // translate list
        $metaSeoLang = $this->_translateList($metaSeoLang);
        $metaSeoLang['emptySearchPageLanguage'] = $defaultLanguageText;

        // Include Ext JS inline code
        $pageRenderer->addJsInlineCode(
            'MetaSeo.overview',

            'Ext.namespace("MetaSeo.overview");
            MetaSeo.overview.conf = '.json_encode($metaSeoConf).';
            MetaSeo.overview.conf.lang = '.json_encode($metaSeoLang).';
        ');
    }

}
