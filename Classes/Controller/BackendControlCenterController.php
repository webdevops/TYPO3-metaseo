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
 * TYPO3 Backend module root settings
 *
 * @package     TYPO3
 * @subpackage  metaseo
 */
class BackendControlCenterController extends \Metaseo\Metaseo\Backend\Module\AbstractStandardModule {
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
        // #################
        // Root page list
        // #################

        $rootPageList   = \Metaseo\Metaseo\Utility\BackendUtility::getRootPageList();
        $rootIdList     = array_keys($rootPageList);

        $rootPidCondition = NULL;
        if( !empty($rootIdList) ) {
            $rootPidCondition = 'p.uid IN ('.implode(',', $rootIdList).')';
        } else {
            $rootPidCondition = '1=0';
        }

        // #################
        // Root setting list (w/ automatic creation)
        // #################

        // check which root lages have no root settings
        $query = 'SELECT p.uid
                    FROM pages p
                         LEFT JOIN tx_metaseo_setting_root seosr
                            ON   seosr.pid = p.uid
                             AND seosr.deleted = 0
                    WHERE '.$rootPidCondition.'
                      AND seosr.uid IS NULL';
        $res = $GLOBALS['TYPO3_DB']->sql_query($query);
        while( $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) ) {
            $tmpUid = $row['uid'];
            $query = 'INSERT INTO tx_metaseo_setting_root (pid, tstamp, crdate, cruser_id)
                            VALUES ('.(int)$tmpUid.',
                                    '.(int)time().',
                                    '.(int)time().',
                                    '.(int)$GLOBALS['BE_USER']->user['uid'].')';
            DatabaseUtility::execInsert($query);
        }

        $rootSettingList  = \Metaseo\Metaseo\Utility\BackendUtility::getRootPageSettingList();

        // #################
        // Domain list
        // ##################

        // Fetch domain name
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            'uid, pid, domainName, forced',
            'sys_domain',
            'hidden = 0',
            '',
            'forced DESC, sorting'
        );

        $domainList = array();
        while( $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) ) {
            $domainList[ $row['pid'] ][ $row['uid'] ] = $row;
        }

        // #################
        // Build root page list
        // #################

        unset($page);
        foreach($rootPageList as $pageId => &$page) {
            // Domain list
            $page['domainList'] = '';
            if( !empty($domainList[$pageId]) ) {
                $page['domainList'] = $domainList[$pageId];
            }

            // Settings
            $page['rootSettings'] = array();
            if( !empty($rootSettingList[$pageId]) ) {
                $page['rootSettings'] = $rootSettingList[$pageId];
            }

            // Settings available
            $page['settingsLink'] = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[tx_metaseo_setting_root]['.$rootSettingList[$pageId]['uid'].']=edit',$this->doc->backPath);


            $page['sitemapLink']   = \Metaseo\Metaseo\Utility\RootPageUtility::getSitemapIndexUrl($pageId);
            $page['robotsTxtLink'] = \Metaseo\Metaseo\Utility\RootPageUtility::getRobotsTxtUrl($pageId);
        }
        unset($page);

        // check if there is any root page
        if( empty($rootPageList) ) {
            $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                $this->_translate('message.warning.noRootPage.message'),
                $this->_translate('message.warning.noRootPage.title'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
            \TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($message);
        }

        // ############################
        // Page/JS
        // ############################

        // FIXME: do we really need a template engine here?
        $this->template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
        $pageRenderer = $this->template->getPageRenderer();

        $basePathJs  = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/JavaScript';
        $basePathCss = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('metaseo') . 'Resources/Public/Backend/Css';
        $pageRenderer->addCssFile($basePathCss.'/Default.css');

        $this->view->assign('RootPageList', $rootPageList);
    }

}
