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

namespace Metaseo\Metaseo\Dao;

use Metaseo\Metaseo\DependencyInjection\Utility\HttpUtility;
use Metaseo\Metaseo\Exception\Ajax\AjaxException;
use Metaseo\Metaseo\Utility\DatabaseUtility;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class PageSeoDao extends Dao
{
    /**
     * @var PageTreeView
     */
    protected $pageTreeView;

    /**
     * Return default tree
     *
     * @param   array   $page              Root page
     * @param   integer $depth             Depth
     * @param   integer $sysLanguage       System language
     * @param   array   $fieldList         Field list
     *
     * @return  array
     */
    public function index(array $page, $depth, $sysLanguage, $fieldList = array())
    {
        $rootPid = $page['uid'];

        $list = array();

        $fieldList[] = 'pid';
        $pageIdList  = array();

        // ###########################
        // Build tree
        // ############################

        // Init tree
        /** @var \TYPO3\CMS\Backend\Tree\View\PageTreeView $tree */
        $tree = $this->pageTreeView;
        foreach ($fieldList as $field) {
            $tree->addField($field, true);
        }
        $tree->init(
            'AND doktype IN (1,4) AND ' . $this->getBackendUserAuthentication()->getPagePermsClause(1)
        );

        $tree->tree[] = array(
            'row'           => $page,
            'invertedDepth' => 0,
        );

        $tree->getTree($rootPid, $depth, '');

        // Build tree list
        foreach ($tree->tree as $row) {
            $tmp               = $row['row'];
            $list[$tmp['uid']] = $tmp;

            $pageIdList[$tmp['uid']] = $tmp['uid'];
        }

        // Calc depth
        $rootLineRaw = array();
        foreach ($list as $row) {
            $rootLineRaw[$row['uid']] = $row['pid'];
        }

        $rootLineRaw[$rootPid] = null;

        // overlay status "current"
        $defaultOverlayStatus = 0;
        if (!empty($sysLanguage)) {
            // overlay status "only available from base"
            $defaultOverlayStatus = 2;
        }

        unset($row);
        foreach ($list as &$row) {
            // Set field as main fields
            foreach ($fieldList as $fieldName) {
                $row['_overlay'][$fieldName] = $defaultOverlayStatus;
                $row['_base'][$fieldName]    = $row[$fieldName];
            }

            $row['_depth'] = $this->listCalcDepth($row['uid'], $rootLineRaw);
        }
        unset($row);

        // ############################
        // Language overlay
        // ############################

        if (!empty($sysLanguage) && !empty($pageIdList)) {
            // Fetch all overlay rows for current page list
            $overlayFieldList = array();
            foreach ($fieldList as $fieldName) {
                if ($this->isFieldInTcaTable('pages_language_overlay', $fieldName)) {
                    $overlayFieldList[$fieldName] = $fieldName;
                }
            }

            // Build list of fields which we need to query
            $queryFieldList = array(
                'uid',
                'pid',
                'title',
            );
            $queryFieldList = array_merge($queryFieldList, $overlayFieldList);

            $res = DatabaseUtility::connection()->exec_SELECTquery(
                implode(',', $queryFieldList),
                'pages_language_overlay',
                'pid IN(' . implode(',', $pageIdList) . ') AND sys_language_uid = ' . (int)$sysLanguage
            );

            // update all overlay status field to "from base"
            unset($row);
            foreach ($list as &$row) {
                foreach ($overlayFieldList as $fieldName) {
                    $row['_overlay'][$fieldName] = 0;
                }
            }
            unset($row);

            while ($overlayRow = DatabaseUtility::connection()->sql_fetch_assoc($res)) {
                $pageOriginalId = $overlayRow['pid'];

                // Don't use uid and pid
                unset($overlayRow['uid'], $overlayRow['pid']);

                // inject title
                $fieldName = 'title';
                if (!empty($overlayRow[$fieldName])) {
                    $list[$pageOriginalId][$fieldName] = $overlayRow[$fieldName];
                }

                // inject all other fields
                foreach ($fieldList as $fieldName) {
                    if (!empty($overlayRow[$fieldName])) {
                        $list[$pageOriginalId][$fieldName] = $overlayRow[$fieldName];

                        // update overlay status field to "from overlay"
                        $list[$pageOriginalId]['_overlay'][$fieldName] = 1;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * Calculate the depth of a page
     *
     * @param  integer $pageUid     Page UID
     * @param  array   $rootLineRaw Root line (raw list)
     * @param  integer $depth       Current depth
     *
     * @return integer
     */
    protected function listCalcDepth($pageUid, array $rootLineRaw, $depth = null)
    {
        if ($depth === null) {
            $depth = 1;
        }

        if (empty($rootLineRaw[$pageUid])) {
            // found root page
            return $depth;
        }

        // we must be at least in the first depth
        ++$depth;

        $pagePid = $rootLineRaw[$pageUid];

        if (!empty($pagePid)) {
            // recursive
            $depth = $this->listCalcDepth($pagePid, $rootLineRaw, $depth);
        }

        return $depth;
    }

    /**
     * Update field in page table
     *
     * @param integer      $pid         PID
     * @param integer|NULL $sysLanguage System language id
     * @param string       $fieldName   Field name
     * @param string       $fieldValue  Field value
     *
     * @return array
     *
     * @throws AjaxException
     */
    public function updatePageTableField($pid, $sysLanguage, $fieldName, $fieldValue)
    {
        $tableName = 'pages';

        if (!empty($sysLanguage)) {
            // check if field is in overlay
            if ($this->isFieldInTcaTable('pages_language_overlay', $fieldName)) {
                // Field is in pages language overlay
                $tableName = 'pages_language_overlay';
            }
        }

        switch ($tableName) {
            case 'pages_language_overlay':
                // Update field in pages overlay (also logs update event and clear cache for this page)

                // check uid of pages language overlay
                $query     = 'SELECT uid
                                FROM pages_language_overlay
                               WHERE pid = ' . (int)$pid . '
                                 AND sys_language_uid = ' . (int)$sysLanguage;
                $overlayId = DatabaseUtility::getOne($query);

                if (empty($overlayId)) {
                    // No access

                    throw new AjaxException(
                        'message.error.no_language_overlay_found',
                        '[0x4FBF3C05]',
                        HttpUtility::HTTP_STATUS_BAD_REQUEST
                    );
                }

                // ################
                // UPDATE
                // ################

                $this->getDataHandler()->updateDB(
                    'pages_language_overlay',
                    (int)$overlayId,
                    array(
                        $fieldName => $fieldValue
                    )
                );
                break;
            case 'pages':
                // Update field in page (also logs update event and clear cache for this page)
                $this->getDataHandler()->updateDB(
                    'pages',
                    (int)$pid,
                    array(
                        $fieldName => $fieldValue
                    )
                );
                break;
        }

        return array();
    }


    /**
     * @param $pid
     *
     * @return array|NULL
     */
    public function getPageById($pid)
    {
        return $this->getRecord('pages', $pid);
    }

    /**
     * Gets record with uid = $uid from $table
     * You can set $field to a list of fields (default is '*')
     * Additional WHERE clauses can be added by $where (fx. ' AND blabla = 1')
     * Will automatically check if records has been deleted and if so, not return anything.
     * $table must be found in $GLOBALS['TCA']
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @param int $uid UID of record
     * @param string $fields List of fields to select
     * @param string $where Additional WHERE clause, eg. " AND blablabla = 0
     * @param bool $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
     *
     * @return array|NULL Returns the row if found, otherwise NULL
     */
    protected function getRecord($table, $uid, $fields = '*', $where = '', $useDeleteClause = true)
    {
        return BackendUtility::getRecord($table, $uid, $fields, $where, $useDeleteClause);
    }

    /**
     * @param $pageTreeView
     *
     * @return $this
     */
    public function setPageTreeView($pageTreeView)
    {
        $this->pageTreeView = $pageTreeView;

        return $this;
    }
}
