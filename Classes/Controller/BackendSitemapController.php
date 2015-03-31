<?php
namespace Metaseo\Metaseo\Controller;

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

use Metaseo\Metaseo\Backend\Module\AbstractStandardModule;
use Metaseo\Metaseo\Utility\BackendUtility;
use Metaseo\Metaseo\Utility\DatabaseUtility;
use Metaseo\Metaseo\Utility\SitemapUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityTypo3;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 Backend module sitemap
 *
 * @package     TYPO3
 * @subpackage  metaseo
 */
class BackendSitemapController extends AbstractStandardModule
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
        // Init
        $rootPageList    = BackendUtility::getRootPageList();
        $rootSettingList = BackendUtility::getRootPageSettingList();

        // ############################
        // Fetch
        // ############################

        // Get statistics
        $query = 'SELECT s.page_rootpid,
                 COUNT(*) as sum_total,
                 COUNT(s.page_uid) as sum_pages
            FROM tx_metaseo_sitemap s
                 INNER JOIN pages p
                    ON p.uid = s.page_uid
                   AND p.deleted = 0
                   AND '
        . DatabaseUtility::conditionNotIn(
            'p.doktype',
            SitemapUtility::getPageTypeBlacklist()
        ) . ' GROUP BY page_rootpid';
        $statsList = DatabaseUtility::getAllWithIndex($query, 'page_rootpid');

        // Fetch domain name
        $query = 'SELECT uid,
                         pid,
                         domainName,
                         forced
                    FROM sys_domain
                   WHERE hidden = 0
                ORDER BY forced DESC,
                         sorting';
        $rowList = DatabaseUtility::getAll($query);

        $domainList = array();
        foreach ($rowList as $row) {
            $pid = $row['pid'];

            if (!empty($row['forced'])) {
                $domainList[$pid] = $row['domainName'];
            } elseif (empty($domainList[$pid])) {
                $domainList[$pid] = $row['domainName'];
            }
        }

        // #################
        // Build root page list
        // #################


        unset($page);
        foreach ($rootPageList as $pageId => &$page) {
            $stats = array(
                'sum_pages'     => 0,
                'sum_total'     => 0,
                'sum_xml_pages' => 0,
            );

            // Get domain
            $domain = null;
            if (!empty($domainList[$pageId])) {
                $domain = $domainList[$pageId];
            }

            // Setting row
            $settingRow = array();
            if (!empty($rootSettingList[$pageId])) {
                $settingRow = $rootSettingList[$pageId];
            }


            // Calc stats
            if (!empty($statsList[$pageId])) {
                foreach ($statsList[$pageId] as $statsKey => $statsValue) {
                    $stats[$statsKey] = $statsValue;
                }
            }

            // Root statistics
            $query = 'SELECT COUNT(s.page_uid)
                        FROM tx_metaseo_sitemap s
                             INNER JOIN pages p
                                ON p.uid = s.page_uid
                               AND '
                . DatabaseUtility::conditionNotIn(
                    'p.doktype',
                    SitemapUtility::getPageTypeBlacklist()
                )
                . ' WHERE s.page_rootpid = ' . (int)$pageId;
            $stats['sum_pages'] = DatabaseUtility::getOne($query);

            $pagesPerXmlSitemap = 1000;
            if (!empty($settingRow['sitemap_page_limit'])) {
                $pagesPerXmlSitemap = $settingRow['sitemap_page_limit'];
            }
            $sumXmlPages = ceil($stats['sum_total'] / $pagesPerXmlSitemap);
            $stats['sum_xml_pages'] = sprintf($this->translate('sitemap.xml.pages.total'), $sumXmlPages);


            $page['stats'] = $stats;
        }
        unset($page);


        // check if there is any root page
        if (empty($rootPageList)) {
            $message = $this->objectManager->get(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                $this->translate('message.warning.noRootPage.message'),
                $this->translate('message.warning.noRootPage.title'),
                FlashMessage::WARNING
            );
            FlashMessageQueue::addMessage($message);
        }

        $this->view->assign('RootPageList', $rootPageList);
    }

    /**
     * Sitemap action
     */
    public function sitemapAction()
    {
        $params  = GeneralUtility::_GP('tx_metaseo_metaseometaseo_metaseositemap');
        $rootPid = $params['pageId'];

        if (empty($rootPid)) {
            return '';
        }

        $rootPageList = BackendUtility::getRootPageList();
        $rootPage    = $rootPageList[$rootPid];

        // ###############################
        // Fetch
        // ###############################
        $pageTsConf = BackendUtilityTypo3::getPagesTSconfig($rootPid);

        $languageFullList = array(
            0 => array(
                'label'    => $this->translate('default.language'),
                'flag'    => '',
            ),
        );

        if (!empty($pageTsConf['mod.']['SHARED.']['defaultLanguageFlag'])) {
            $languageFullList[0]['flag'] = $pageTsConf['mod.']['SHARED.']['defaultLanguageFlag'];
        }

        if (!empty($pageTsConf['mod.']['SHARED.']['defaultLanguageLabel'])) {
            $languageFullList[0]['label'] = $pageTsConf['mod.']['SHARED.']['defaultLanguageLabel'];
        }

        // Fetch domain name
        $query = 'SELECT uid,
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

        // Langauges
        $languageList = array();
        $languageList[] =    array(
            -1,
            $this->translate('empty.search.page_language'),
        );

        foreach ($languageFullList as $langId => $langRow) {
            $flag = '';

            // Flag (if available)
            if (!empty($langRow['flag'])) {
                $flag .= '<span class="t3-icon t3-icon-flags t3-icon-flags-';
                $flag .= $langRow['flag'] . ' t3-icon-' . $langRow['flag'] . '"></span>';
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

        // Depth
        $depthList = array();
        $depthList[] =    array(
            -1,
            $this->translate('empty.search.page_depth'),
        );

        $query = 'SELECT DISTINCT page_depth
                    FROM tx_metaseo_sitemap
                   WHERE page_rootpid = ' . (int)$rootPid;
        foreach (DatabaseUtility::getCol($query) as $depth) {
            $depthList[] = array(
                $depth,
                $depth,
            );
        }

        // ###############################
        // Page/JS
        // ###############################

        // FIXME: do we really need a template engine here?
        $this->template = $this->objectManager->get('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
        $pageRenderer = $this->template->getPageRenderer();

        $basePathJs  = ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/JavaScript';
        $basePathCss = ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/Css';

        $pageRenderer->addJsFile($basePathJs.'/MetaSeo.js');
        $pageRenderer->addJsFile($basePathJs.'/Ext.ux.plugin.FitToParent.js');
        $pageRenderer->addJsFile($basePathJs.'/MetaSeo.sitemap.js');
        $pageRenderer->addCssFile($basePathCss.'/Default.css');



        $metaSeoConf = array(
            'sessionToken'      => $this->sessionToken('metaseo_metaseo_backend_ajax_sitemapajax'),
            'ajaxController'    => $this->ajaxControllerUrl('tx_metaseo_backend_ajax::sitemap'),
            'pid'               => (int)$rootPid,
            'renderTo'          => 'tx-metaseo-sitemap-grid',
            'pagingSize'        => 50,

            'sortField'         => 'crdate',
            'sortDir'           => 'DESC',

            'filterIcon'        => IconUtility::getSpriteIcon('actions-system-tree-search-open'),

            'dataLanguage'      => $languageList,
            'dataDepth'         => $depthList,

            'criteriaFulltext'       => '',
            'criteriaPageUid'        => '',
            'criteriaPageLanguage'   => '',
            'criteriaPageDepth'      => '',
            'criteriaIsBlacklisted'  => 0,

            'languageFullList'      => $languageFullList,
        );

        $metaSeoLang = array(
            'title' => 'title.sitemap.list',

            'pagingMessage' => 'pager.results',
            'pagingEmpty'   => 'pager.noresults',

            'sitemap_page_uid'            => 'header.sitemap.page_uid',
            'sitemap_page_url'            => 'header.sitemap.page_url',
            'sitemap_page_type'           => 'header.sitemap.page_type',
            'sitemap_page_depth'          => 'header.sitemap.page_depth',
            'sitemap_page_language'       => 'header.sitemap.page_language',
            'sitemap_page_is_blacklisted' => 'header.sitemap.page_is_blacklisted',

            'page_tx_metaseo_is_exclude'  => 'header.sitemap.page_tx_metaseo_is_exclude',

            'sitemap_tstamp' => 'header.sitemap.tstamp',
            'sitemap_crdate' => 'header.sitemap.crdate',

            'labelSearchFulltext' => 'label.search.fulltext',
            'emptySearchFulltext' => 'empty.search.fulltext',

            'labelSearchPageUid' => 'label.search.page_uid',
            'emptySearchPageUid' => 'empty.search.page_uid',

            'labelSearchPageLanguage' => 'label.search.page_language',
            'emptySearchPageLanguage' => 'empty.search.page_language',

            'labelSearchPageDepth' => 'label.search.page_depth',
            'emptySearchPageDepth' => 'empty.search.page_depth',

            'labelSearchIsBlacklisted' => 'label.search.is_blacklisted',

            'labelYes' => 'label.yes',
            'labelNo'  => 'label.no',

            'buttonYes' => 'button.yes',
            'buttonNo'  => 'button.no',

            'buttonDelete'     => 'button.delete',
            'buttonDeleteHint' => 'button.delete.hint',

            'buttonBlacklist'     => 'button.blacklist',
            'buttonBlacklistHint' => 'button.blacklist.hint',
            'buttonWhitelist'     => 'button.whitelist',
            'buttonWhitelistHint' => 'button.whitelist.hint',

            'buttonDeleteAll' => 'button.delete_all',

            'messageDeleteTitle'    => 'message.delete.title',
            'messageDeleteQuestion' => 'message.delete.question',

            'messageDeleteAllTitle'    => 'message.delete_all.title',
            'messageDeleteAllQuestion' => 'message.delete_all.question',

            'messageBlacklistTitle'    => 'message.blacklist.title',
            'messageBlacklistQuestion' => 'message.blacklist.question',

            'messageWhitelistTitle'    => 'message.whitelist.title',
            'messageWhitelistQuestion' => 'message.whitelist.question',

            'errorDeleteFailedMessage' => 'message.delete.failed_body',

            'errorNoSelectedItemsBody' => 'message.no_selected_items',

            'today'     => 'today',
            'yesterday' => 'yesterday',

            'sitemapPageType' => array(
                0 => 'sitemap.pagetype.0',
                1 => 'sitemap.pagetype.1',
            ),
        );

        // translate list
        $metaSeoLang = $this->translateList($metaSeoLang);
        $metaSeoLang['title'] = sprintf($metaSeoLang['title'], $rootPage['title'], $rootPid);

        // Include Ext JS inline code
        $pageRenderer->addJsInlineCode(
            'MetaSeo.sitemap',
            'Ext.namespace("MetaSeo.sitemap");
            MetaSeo.sitemap.conf      = ' . json_encode($metaSeoConf) . ';
            MetaSeo.sitemap.conf.lang = ' . json_encode($metaSeoLang) . ';
        '
        );
    }
}
