<?php
namespace Metaseo\Metaseo\Backend\Ajax;

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
 * TYPO3 Backend ajax module page
 *
 * @package      TYPO3
 * @subpackage   metaseo
 */
class PageAjax extends \Metaseo\Metaseo\Backend\Ajax\AbstractAjax {

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * List of page uids which have templates
     *
     * @var    array
     */
    protected $_templatePidList = array();

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Return overview entry list for root tree
     *
     * @return    array
     */
    protected function _executeGetList() {
        // Init
        $list = array();

        $pid          = (int)$this->_postVar['pid'];
        $offset       = (int)$this->_postVar['start'];
        $limit        = (int)$this->_postVar['limit'];
        $itemsPerPage = (int)$this->_postVar['pagingSize'];
        $depth        = (int)$this->_postVar['depth'];
        $sysLanguage  = (int)$this->_postVar['sysLanguage'];
        $listType     = (string)$this->_postVar['listType'];

        // Store last selected language
        $GLOBALS['BE_USER']->setAndSaveSessionData('MetaSEO.sysLanguage', $sysLanguage);

        if (!empty($pid)) {
            $page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pid);

            $fieldList = array();

            switch ($listType) {
                case 'metadata':
                    $fieldList = array_merge(
                        $fieldList,
                        array(
                            'keywords',
                            'description',
                            'abstract',
                            'author',
                            'author_email',
                            'lastupdated',
                        )
                    );

                    $list = $this->_listDefaultTree($page, $depth, $sysLanguage, $fieldList);

                    unset($row);
                    foreach ($list as &$row) {
                        if (!empty($row['lastupdated'])) {
                            $row['lastupdated'] = date('Y-m-d', $row['lastupdated']);
                        } else {
                            $row['lastupdated'] = '';
                        }
                    }
                    unset($row);
                    break;

                case 'geo':
                    $fieldList = array_merge(
                        $fieldList,
                        array(
                            'tx_metaseo_geo_lat',
                            'tx_metaseo_geo_long',
                            'tx_metaseo_geo_place',
                            'tx_metaseo_geo_region'
                        )
                    );

                    $list = $this->_listDefaultTree($page, $depth, $sysLanguage, $fieldList);
                    break;

                case 'searchengines':
                    $fieldList = array_merge(
                        $fieldList,
                        array(
                            'tx_metaseo_canonicalurl',
                            'tx_metaseo_is_exclude',
                            'tx_metaseo_priority',
                        )
                    );

                    $list = $this->_listDefaultTree($page, $depth, $sysLanguage, $fieldList);
                    break;

                case 'url':
                    $fieldList = array_merge(
                        $fieldList,
                        array(
                            'title',
                            'url_scheme',
                            'alias',
                            'tx_realurl_pathsegment',
                            'tx_realurl_pathoverride',
                            'tx_realurl_exclude',
                        )
                    );

                    $list = $this->_listDefaultTree($page, $depth, $sysLanguage, $fieldList);
                    break;


                case 'advanced':
                    $fieldList = array_merge(
                        $fieldList,
                        array(
                            // Maybe we need more fields later
                        )
                    );

                    $list = $this->_listDefaultTree($page, $depth, $sysLanguage, $fieldList, true);
                    break;

                case 'pagetitle':
                    $fieldList = array_merge(
                        $fieldList,
                        array(
                            'tx_metaseo_pagetitle',
                            'tx_metaseo_pagetitle_rel',
                            'tx_metaseo_pagetitle_prefix',
                            'tx_metaseo_pagetitle_suffix',
                        )
                    );

                    $list = $this->_listDefaultTree($page, $depth, $sysLanguage, $fieldList);
                    break;

                case 'pagetitlesim':
                    $buildTree = FALSE;
                    $list      = $this->_listPageTitleSim($page, $depth, $sysLanguage);
                    break;

                default:
                    // Not defined
                    return;
                    break;
            }
        }

        $ret = array(
            'results' => count($list),
            'rows'    => array_values($list),
        );

        return $ret;
    }

    /**
     * Generate simulated title for one page
     *
     * @return    string
     */
    protected function _executeGenerateSimulatedTitle() {
        // Init
        $ret = '';

        $pid = (int)$this->_postVar['pid'];

        if (!empty($pid)) {
            $page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pid);

            if (!empty($page)) {
                // Load TYPO3 classes
                $this->_initTsfe($page, NULL, $page, NULL);

                $pagetitle = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'Metaseo\\Metaseo\\Page\\Part\\PagetitlePart'
                );
                $ret = $pagetitle->main($page['title']);
            }
        }

        $ret = array(
            'title' => $ret,
        );

        return $ret;
    }

    /**
     * Generate simulated title for one page
     *
     * @return    string
     */
    protected function _executeGenerateSimulatedUrl() {
        // Init
        $ret = '';

        $pid = (int)$this->_postVar['pid'];

        if (!empty($pid)) {
            $page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pid);

            if (!empty($page)) {

                if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl')) {
                    // Disable caching for url
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['enableUrlDecodeCache'] = 0;
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['enableUrlEncodeCache'] = 0;
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['disablePathCache']     = 1;
                }

                $this->_initTsfe($page, NULL, $page, NULL);

                $ret = $GLOBALS['TSFE']->cObj->typolink_URL(array('parameter' => $page['uid']));

                if (!empty($ret)) {
                    $ret = \Metaseo\Metaseo\Utility\GeneralUtility::fullUrl($ret);
                }
            }
        }

        if (!empty($ret)) {
            $ret = array(
                'url' => $ret,
            );
        } else {
            $ret = array(
                'error' => $GLOBALS['LANG']->getLL('error.url_generation_failed'),
            );
        }

        return $ret;
    }


    /**
     * Return default tree
     *
     * @param   array        $page               Root page
     * @param   integer      $depth              Depth
     * @param   integer      $sysLanguage        System language
     * @param   array        $fieldList          Field list
     * @param   boolean      $enableAdvMetaTags  Enable adv. meta tags
     * @return  array
     */
    protected function _listDefaultTree($page, $depth, $sysLanguage, $fieldList, $enableAdvMetaTags = false) {
        $rootPid = $page['uid'];

        $list = array();

        $fieldList[] = 'pid';
        $pageIdList  = array();

        // ###########################
        // Build tree
        // ############################

        // Init tree
        /** @var \TYPO3\CMS\Backend\Tree\View\PageTreeView $tree */
        $tree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
        foreach ($fieldList as $field) {
            $tree->addField($field, TRUE);
        }
        $tree->init('AND doktype IN (1,4) AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));

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

        $rootLineRaw[$rootPid] = NULL;

        // overlay status "current"
        $defaultOverlayStatus = 0;
        if( !empty($sysLanguage) ) {
            // overlay status "only available from base"
            $defaultOverlayStatus = 2;
        }


        unset($row);
        foreach ($list as &$row) {

            // Set field as main fields
            foreach($fieldList as $fieldName) {
                $row['_overlay'][$fieldName] = $defaultOverlayStatus;
                $row['_base'][$fieldName]    = $row[$fieldName];
            }

            $row['_depth'] = $this->_listCalcDepth($row['uid'], $rootLineRaw);
        }
        unset($row);

        // ############################
        // Language overlay
        // ############################

        if( !empty($sysLanguage) && !empty($pageIdList) ) {

            // Fetch all overlay rows for current page list
            $overlayFieldList = array();
            foreach($fieldList as $fieldName) {
                if( $this->_isFieldInTcaTable('pages_language_overlay', $fieldName) ) {
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

            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                implode(',', $queryFieldList),
                'pages_language_overlay',
                'pid IN('.implode(',',$pageIdList).') AND sys_language_uid = '.(int)$sysLanguage
            );

            // update all overlay status field to "from base"
            unset($row);
            foreach ($list as &$row) {
                foreach($overlayFieldList as $fieldName) {
                    $row['_overlay'][$fieldName] = 0;
                }
            }
            unset($row);

            while($overlayRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                $pageOverlayId  = $overlayRow['uid'];
                $pageOriginalId = $overlayRow['pid'];

                // Dont use uid and pid
                unset($overlayRow['uid'], $overlayRow['pid']);

                // inject title
                $fieldName = 'title';
                if( !empty($overlayRow[$fieldName]) ) {
                    $list[$pageOriginalId][$fieldName] = $overlayRow[$fieldName];
                }

                // inject all other fields
                foreach($fieldList as $fieldName) {
                    if( !empty($overlayRow[$fieldName]) ) {
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
     * @param  integer  $pageUid        Page UID
     * @param  array    $rootLineRaw    Root line (raw list)
     * @param  integer  $depth          Current depth
     * @return integer
     */
    protected function _listCalcDepth($pageUid, $rootLineRaw, $depth = NULL) {

        if ($depth === NULL) {
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
            $depth = $this->_listCalcDepth($pagePid, $rootLineRaw, $depth);
        }


        return $depth;
    }


    /**
     * Return simulated page title
     *
     * @param   array   $page         Root page
     * @param   integer $depth        Depth
     * @param   integer $sysLanguage  Sys language
     * @return  array
     */
    protected function _listPageTitleSim($page, $depth, $sysLanguage) {
        // Init
        $list = array();

        $pid = $page['uid'];

        $fieldList = array(
            'title',
            'tx_metaseo_pagetitle',
            'tx_metaseo_pagetitle_rel',
            'tx_metaseo_pagetitle_prefix',
            'tx_metaseo_pagetitle_suffix',
        );

        $list = $this->_listDefaultTree($page, $depth, $sysLanguage, $fieldList);

        $uidList = array_keys($list);

        if (!empty($uidList)) {
            // Check which pages have templates (for caching and faster building)
            $this->_templatePidList = array();

            $query = 'SELECT pid
                        FROM sys_template
                       WHERE pid IN (' . implode(',', $uidList) . ')
                         AND deleted = 0
                         AND hidden = 0';
            $pidList = DatabaseUtility::getCol($query);
            foreach($pidList as $pid) {
                $this->_templatePidList[$pid] = $pid;
            }

            // Build simulated title
            foreach ($list as &$row) {
                $row['title_simulated'] = $this->_simulateTitle($row, $sysLanguage);
            }
        }

        return $list;
    }

    /**
     * Generate simluated page title
     *
     * @param   array   $page        Page
     * @param   integer $sysLanguage System language
     * @return  string
     */
    protected function _simulateTitle($page, $sysLanguage) {
        $this->_initTsfe($page, NULL, $page, NULL, $sysLanguage);

        $pagetitle = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Metaseo\\Metaseo\\Page\\Part\\PagetitlePart');
        $ret       = $pagetitle->main($page['title']);

        return $ret;
    }


    /**
     * Init TSFE (for simulated pagetitle)
     *
     * @param   array        $page         Page
     * @param   NULL|array   $rootLine     Rootline
     * @param   NULL|array   $pageData     Page data (recursive generated)
     * @param   NULL|array   $rootlineFull Rootline full
     * @param   NULL|integer $sysLanguage  System language
     * @return  void
     */
    protected function _initTsfe($page, $rootLine = NULL, $pageData = NULL, $rootlineFull = NULL, $sysLanguage = NULL) {
        static $cacheTSFE = array();
        static $lastTsSetupPid = NULL;

        $pageUid = (int)$page['uid'];

        if ($rootLine === NULL) {
            $sysPageObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Frontend\\Page\\PageRepository'
            );
            $rootLine   = $sysPageObj->getRootLine($pageUid);

            // save full rootline, we need it in TSFE
            $rootlineFull = $rootLine;
        }

        // check if current page has a ts-setup-template
        // if not, we go down the tree to the parent page
        if (count($rootLine) >= 2 && !empty($this->_templatePidList) && empty($this->_templatePidList[$pageUid])) {
            // go to parent page in rootline
            reset($rootLine);
            next($rootLine);
            $prevPage = current($rootLine);

            // strip current page from rootline
            reset($rootLine);
            $currPageIndex = key($rootLine);
            unset($rootLine[$currPageIndex]);

            \Metaseo\Metaseo\Utility\FrontendUtility::init($prevPage['uid'], $rootLine, $pageData, $rootlineFull, $sysLanguage);
        }

        \Metaseo\Metaseo\Utility\FrontendUtility::init($page['uid'], $rootLine, $pageData, $rootlineFull, $sysLanguage);
    }

    /**
     * Update page field
     */
    protected function _executeUpdatePageField() {
        if (empty($this->_postVar['pid'])
            || empty($this->_postVar['field'])
        ) {
            return;
        }

        $pid         = (int)$this->_postVar['pid'];
        $fieldName   = strtolower((string)$this->_postVar['field']);
        $fieldValue  = (string)$this->_postVar['value'];
        $sysLanguage = (int)$this->_postVar['sysLanguage'];
        $mode        = (string)$this->_postVar['mode'];

        // validate field name
        $fieldName = preg_replace('/[^-_a-zA-Z0-9:]/i', '', $fieldName);

        if (empty($fieldName)) {
            return;
        }

        // ############################
        // Security checks
        // ############################


        // check if user is able to modify pages
        if (!$GLOBALS['BE_USER']->check('tables_modify', 'pages')) {
            // No access
            return array(
                'error' => $GLOBALS['LANG']->getLL('error.access_denied') . ' [0x4FBF3BE2]',
            );
        }

        $page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pid);

        // check if page exists and user can edit this specific record
        if (empty($page) || !$GLOBALS['BE_USER']->doesUserHaveAccess($page, 2)) {
            // No access
            return array(
                'error' => $GLOBALS['LANG']->getLL('error.access_denied') . ' [0x4FBF3BCF]',
            );
        }

        // check if user is able to modify the field of pages
        if (!$GLOBALS['BE_USER']->check('non_exclude_fields', 'pages:' . $fieldName)) {
            // No access
            return array(
                'error' => $GLOBALS['LANG']->getLL('error.access_denied') . ' [0x4FBF3BD9]',
            );
        }

        // also check for sys langauge
        if( !empty($sysLanguage) ) {
            // check if user is able to modify pages
            if (!$GLOBALS['BE_USER']->check('tables_modify', 'pages_language_overlay')) {
                // No access
                return array(
                    'error' => $GLOBALS['LANG']->getLL('error.access_denied') . ' [0x4FBF3BE2]',
                );
            }

            // check if user is able to modify the field of pages
            if (!$GLOBALS['BE_USER']->check('non_exclude_fields', 'pages_language_overlay:' . $fieldName)) {
                // No access
                return array(
                    'error' => $GLOBALS['LANG']->getLL('error.access_denied') . ' [0x4FBF3BD9]',
                );
            }
        }

        // ############################
        // Transformations
        // ############################

        switch ($fieldName) {
            case 'lastupdated':
                // transform to unix timestamp
                $fieldValue = strtotime($fieldValue);
                break;
        }


        // ############################
        // Update
        // ############################
        $ret = $this->_updatePageTableField($pid, $sysLanguage, $fieldName, $fieldValue);

        return $ret;
    }


    /**
     * Load meta data
     */
    protected function _executeLoadAdvMetaTags() {
        if (empty($this->_postVar['pid']) ) {
            return;
        }

        $ret = array();

        $pid         = (int)$this->_postVar['pid'];
        $sysLanguage = (int)$this->_postVar['sysLanguage'];


        // check uid of pages language overlay
        $query = 'SELECT tag_name, tag_value
                    FROM tx_metaseo_metatag
                   WHERE pid = '.(int)$pid.'
                     AND sys_language_uid = '.(int)$sysLanguage;
        $rowList = DatabaseUtility::getAll($query);
        foreach($rowList as $row) {
            $ret[ $row['tag_name'] ] = $row['tag_value'];
        }

        return $ret;
    }

    /**
     * Update page field
     */
    protected function _executeUpdateAdvMetaTags() {
        if (empty($this->_postVar['pid'])
            || empty($this->_postVar['metaTags'])
        ) {
            return;
        }

        $pid         = (int)$this->_postVar['pid'];
        $metaTagList = (array)$this->_postVar['metaTags'];
        $sysLanguage = (int)$this->_postVar['sysLanguage'];


        $this->_clearMetaTags($pid, $sysLanguage);
        $metaTagGroup = 2;
        foreach($metaTagList as $metaTagName => $metaTagValue) {

            if( is_scalar($metaTagValue) ) {
                $metaTagValue = trim($metaTagValue);

                if( strlen($metaTagValue) > 0 ) {
                    $this->_updateMetaTag($pid, $sysLanguage, $metaTagName, $metaTagValue);
                }
            } elseif( is_array($metaTagValue) ) {
                foreach($metaTagValue as $subTagName => $subTagValue) {
                    $this->_updateMetaTag($pid, $sysLanguage, array($metaTagName,$subTagName), $subTagValue, $metaTagGroup++);
                }
            }
        }

        return 1;
    }

    /**
     * Clear all meta tags for one page
     *
     * @param integer        $pid           PID
     * @param integer|NULL   $sysLanguage   System language id
     */
    protected function _clearMetaTags($pid, $sysLanguage) {
        $query = 'DELETE FROM tx_metaseo_metatag
                        WHERE pid = '.(int)$pid.'
                          AND sys_language_uid = '.(int)$sysLanguage;
        DatabaseUtility::exec($query);
    }

    /**
     * @param integer        $pid               PID
     * @param integer|NULL   $sysLanguage       System language id
     * @param string|array   $metaTag           MetaTag name
     * @param string         $value             MetaTag value
     * @param integer        $tagGroup          MetaTag group
     */
    protected function _updateMetaTag($pid, $sysLanguage, $metaTag, $value, $tagGroup = NULL) {
        $tstamp   = time();
        $crdate   = time();
        $cruserId = $GLOBALS['BE_USER']->user['uid'];

        $subTagName = '';

        if( is_array($metaTag) ) {
            list($metaTag, $subTagName) = $metaTag;
        }

        if( $tagGroup === NULL ) {
            $tagGroup = 1;
        }

        $query = 'INSERT INTO tx_metaseo_metatag
                              (pid, tstamp, crdate, cruser_id, sys_language_uid, tag_name, tag_subname, tag_value, tag_group)
                        VALUES (
                              '.(int)$pid.',
                              '.(int)$tstamp.',
                              '.(int)$crdate.',
                              '.(int)$cruserId.',
                              '.(int)$sysLanguage.',
                              '.DatabaseUtility::quote($metaTag).',
                              '.DatabaseUtility::quote($subTagName).',
                              '.DatabaseUtility::quote($value).',
                              '.(int)$tagGroup.'
                        ) ON DUPLICATE KEY UPDATE
                                tstamp    = VALUES(tstamp),
                                tag_value = VALUES(tag_value)';
        DatabaseUtility::execInsert($query);
    }

    /**
     * Update field in page table
     *
     * @param integer        $pid           PID
     * @param integer|NULL   $sysLanguage   System language id
     * @param string         $fieldName     Field name
     * @param string         $fieldValue    Field value
     * @return array
     */
    protected function _updatePageTableField($pid, $sysLanguage, $fieldName, $fieldValue) {
        $tableName = 'pages';

        if( !empty($sysLanguage) ) {
            // check if field is in overlay
            if( $this->_isFieldInTcaTable( 'pages_language_overlay', $fieldName ) ) {
                // Field is in pages language overlay
                $tableName = 'pages_language_overlay';
            }
        }

        switch($tableName) {
            case 'pages_language_overlay':
                // Update field in pages overlay (also logs update event and clear cache for this page)

                // check uid of pages language overlay
                $query = 'SELECT uid
                            FROM pages_language_overlay
                           WHERE pid = '.(int)$pid.'
                             AND sys_language_uid = '.(int)$sysLanguage;
                $overlayId = DatabaseUtility::getOne($query);

                if ( !empty($overlayId) ) {
                    // ################
                    // UPDATE
                    // ################

                    $this->_tce()->updateDB(
                        'pages_language_overlay',
                        (int)$overlayId,
                        array(
                            $fieldName => $fieldValue
                        )
                    );
                } else {
                    // No access
                    return array(
                        'error' => $GLOBALS['LANG']->getLL('error.no_language_overlay_found'),
                    );
                }
                break;

            case 'pages':
                // Update field in page (also logs update event and clear cache for this page)
                $this->_tce()->updateDB(
                    'pages',
                    (int)$pid,
                    array(
                        $fieldName => $fieldValue
                    )
                );
                break;
        }
    }

}
