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
use Metaseo\Metaseo\Utility\BackendUtility;
use Metaseo\Metaseo\Utility\DatabaseUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility as Typo3BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * TYPO3 Backend module root settings
 */
class BackendRootSettingsController extends AbstractStandardModule
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
        // #################
        // Root page list
        // #################

        $rootPageList = BackendUtility::getRootPageList();
        $rootIdList   = array_keys($rootPageList);

        $rootPidCondition = null;
        if (!empty($rootIdList)) {
            $rootPidCondition = 'p.uid IN (' . implode(',', $rootIdList) . ')';
        } else {
            $rootPidCondition = '1=0';
        }

        // #################
        // Root setting list (w/ automatic creation)
        // #################

        // check which root pages have no root settings
        $query   = 'SELECT p.uid
                      FROM pages p
                           LEFT JOIN tx_metaseo_setting_root seosr
                                ON seosr.pid = p.uid
                               AND seosr.deleted = 0
                     WHERE ' . $rootPidCondition . '
                       AND seosr.uid IS NULL';
        $uidList = DatabaseUtility::getCol($query);
        foreach ($uidList as $tmpUid) {
            $query = 'INSERT INTO tx_metaseo_setting_root (pid, tstamp, crdate, cruser_id)
                           VALUES (' . (int)$tmpUid . ',
                                   ' . (int)time() . ',
                                   ' . (int)time() . ',
                                   ' . (int)$GLOBALS['BE_USER']->user['uid'] . ')';
            DatabaseUtility::execInsert($query);
        }

        $rootSettingList = BackendUtility::getRootPageSettingList();

        // #################
        // Domain list
        // ##################

        // Fetch domain name
        $query   = 'SELECT uid,
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
            $domainList[$row['pid']][$row['uid']] = $row;
        }

        // #################
        // Build root page list
        // #################

        unset($page);
        foreach ($rootPageList as $pageId => &$page) {
            // Domain list
            $page['domainList'] = '';
            if (!empty($domainList[$pageId])) {
                $page['domainList'] = $domainList[$pageId];
            }

            // Settings
            $page['rootSettings'] = array();
            if (!empty($rootSettingList[$pageId])) {
                $page['rootSettings'] = $rootSettingList[$pageId];
            }

            // Settings available
            $page['settingsLink'] = Typo3BackendUtility::editOnClick(
                '&edit[tx_metaseo_setting_root][' . $rootSettingList[$pageId]['uid'] . ']=edit'
            );
        }
        unset($page);

        // check if there is any root page
        if (empty($rootPageList)) {
            $this->addFlashMessage(
                $this->translate('message.warning.noRootPage.message'),
                $this->translate('message.warning.noRootPage.title'),
                FlashMessage::WARNING
            );
        }

        $this->view->assign('RootPageList', $rootPageList);
    }
}
