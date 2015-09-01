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


namespace Metaseo\Metaseo\Controller\Ajax;

use Metaseo\Metaseo\Controller\AbstractAjaxController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\GeoController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\MetaDataController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\PageTitleController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\PageTitleSimController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\SearchEnginesController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\UrlController;
use Metaseo\Metaseo\Exception\Ajax\AjaxException;
use Metaseo\Metaseo\Utility\DatabaseUtility;
use Metaseo\Metaseo\Utility\FrontendUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * TYPO3 Backend ajax module page
 */
abstract class AbstractPageSeoController extends AbstractAjaxController implements PageSeoInterface
{
    const LIST_TYPE = 'undefined';
    const AJAX_PREFIX = 'tx_metaseo_controller_ajax_pageseo_';

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * List of page uids which have templates
     *
     * @var    array
     */
    protected $templatePidList;

    /**
     * @var array
     */
    protected $fieldList;

    // ########################################################################
    // Methods
    // ########################################################################

    public function __construct()
    {
        parent::__construct();
        $this->templatePidList = array();
        $this->initFieldList();
    }

    abstract protected function initFieldList();

    /**
     * @inheritDoc
     */
    public function indexAction()
    {
        try {
            $this->init();
            $ret = $this->executeIndex();
        } catch (AjaxException $ajaxException) {
            return $this->ajaxExceptionHandler($ajaxException);
        }

        return $this->ajaxSuccess($ret);
    }

    /**
     * @return array
     *
     * @throws AjaxException
     */
    protected function executeIndex()
    {
        $pid         = (int)$this->postVar['pid'];
        $depth       = (int)$this->postVar['depth'];
        $sysLanguage = (int)$this->postVar['sysLanguage'];

        // Store last selected language
        $this->getBackendUserAuthentication()
            ->setAndSaveSessionData('MetaSEO.sysLanguage', $sysLanguage);

        if (empty($pid)) {

            throw new AjaxException(
                $this->translate('message.error.typo3_page_not_found'),
                '[0x4FBF3C0C]',
                self::HTTP_STATUS_BAD_REQUEST
            );
        }

        $page = BackendUtility::getRecord('pages', $pid);

        $list = $this->getIndex($page, $depth, $sysLanguage);

        return array(
            'results' => count($list),
            'rows'    => array_values($list),
        );
    }

    /**
     * Return default tree
     *
     * This function is made for list manipulation in subclasses (method template design pattern)
     *
     * @param   array   $page              Root page
     * @param   integer $depth             Depth
     * @param   integer $sysLanguage       System language
     *
     * @return  array
     */
    protected function getIndex(array $page, $depth, $sysLanguage)
    {
        return $this->index($page, $depth, $sysLanguage, $this->fieldList);
    }

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
    protected function index(array $page, $depth, $sysLanguage, $fieldList = array())
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
        $tree = $this->objectManager->get('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
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
     * Init TSFE (for simulated pagetitle)
     *
     * @param   array        $page         Page
     * @param   null|array   $rootLine     Rootline
     * @param   null|array   $pageData     Page data (recursive generated)
     * @param   null|array   $rootlineFull Rootline full
     * @param   null|integer $sysLanguage  System language
     *
     * @return  void
     */
    protected function initTsfe(
        array $page,
        array $rootLine = null,
        array $pageData = null,
        array $rootlineFull = null,
        $sysLanguage = null
    ) {
        $pageUid = (int)$page['uid'];

        if ($rootLine === null) {
            $sysPageObj = $this->objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
            $rootLine   = $sysPageObj->getRootLine($pageUid);

            // save full rootline, we need it in TSFE
            $rootlineFull = $rootLine;
        }

        // check if current page has a ts-setup-template
        // if not, we go down the tree to the parent page
        if (count($rootLine) >= 2 && !empty($this->templatePidList) && empty($this->templatePidList[$pageUid])) {
            // go to parent page in rootline
            reset($rootLine);
            next($rootLine);
            $prevPage = current($rootLine);

            // strip current page from rootline
            reset($rootLine);
            $currPageIndex = key($rootLine);
            unset($rootLine[$currPageIndex]);

            FrontendUtility::init(
                $prevPage['uid'],
                $rootLine,
                $pageData,
                $rootlineFull,
                $sysLanguage
            );
        }

        FrontendUtility::init($page['uid'], $rootLine, $pageData, $rootlineFull, $sysLanguage);
    }

    /**
     * @inheritDoc
     */
    public function updateAction()
    {
        try {
            $this->init();
            $ret = $this->executeUpdate();

        } catch (AjaxException $ajaxException) {
            return $this->ajaxExceptionHandler($ajaxException);
        }

        return $this->ajaxSuccess($ret);
    }

    /**
     * @return array
     *
     * @throws AjaxException
     */
    protected function executeUpdate()
    {
        if (empty($this->postVar['pid']) || empty($this->postVar['field'])) {

            throw new AjaxException(
                $this->translate('message.warning.incomplete_data_received.message'),
                '[0x4FBF3C02]',
                self::HTTP_STATUS_BAD_REQUEST
            );
        }

        $pid         = (int)$this->postVar['pid'];
        $fieldName   = strtolower((string)$this->postVar['field']);
        $fieldValue  = (string)$this->postVar['value'];
        $sysLanguage = (int)$this->postVar['sysLanguage'];

        // validate field name: must match exactly to list of known field names
        if (!in_array($fieldName, array_merge($this->fieldList, array('title')))) {
            throw new AjaxException(
                $this->translate('message.warning.incomplete_data_received.message'),
                '[0x4FBF3C23]',
                self::HTTP_STATUS_BAD_REQUEST
            );
        }

        if (empty($fieldName)) {

            throw new AjaxException(
                $this->translate('message.warning.incomplete_data_received.message'),
                '[0x4FBF3C03]',
                self::HTTP_STATUS_BAD_REQUEST
            );
        }

        // ############################
        // Security checks
        // ############################


        // check if user is able to modify pages
        if (!$this->getBackendUserAuthentication()->check('tables_modify', 'pages')) {
            // No access

            throw new AjaxException(
                $this->translate('message.error.access_denied'),
                '[0x4FBF3BE2]',
                self::HTTP_STATUS_UNAUTHORIZED
            );
        }

        $page = BackendUtility::getRecord('pages', $pid);

        // check if page exists and user can edit this specific record
        if (empty($page) || !$this->getBackendUserAuthentication()->doesUserHaveAccess($page, 2)) {
            // No access

            throw new AjaxException(
                $this->translate('message.error.access_denied'),
                '[0x4FBF3BCF]',
                self::HTTP_STATUS_UNAUTHORIZED
            );
        }

        // check if user is able to modify the field of pages
        if (!$this->getBackendUserAuthentication()
            ->check('non_exclude_fields', 'pages:' . $fieldName)
        ) {
            // No access

            throw new AjaxException(
                $this->translate('message.error.access_denied'),
                '[0x4FBF3BD9]',
                self::HTTP_STATUS_UNAUTHORIZED
            );
        }

        // also check for sys language
        if (!empty($sysLanguage)) {
            // check if user is able to modify pages
            if (!$this->getBackendUserAuthentication()
                ->check('tables_modify', 'pages_language_overlay')
            ) {
                // No access

                throw new AjaxException(
                    $this->translate('message.error.access_denied'),
                    '[0x4FBF3BE2]',
                    self::HTTP_STATUS_UNAUTHORIZED
                );
            }

            // check if user is able to modify the field of pages
            if (!$this->getBackendUserAuthentication()
                ->check('non_exclude_fields', 'pages_language_overlay:' . $fieldName)
            ) {
                // No access

                throw new AjaxException(
                    $this->translate('message.error.access_denied'),
                    '[0x4FBF3BD9]',
                    self::HTTP_STATUS_UNAUTHORIZED
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

        return $this->updatePageTableField($pid, $sysLanguage, $fieldName, $fieldValue);
    }

    /**
     * @inheritDoc
     */
    public function updateRecursiveAction()
    {
        try {
            $this->init();
            $ret = $this->executeUpdateRecursive();
        } catch (AjaxException $ajaxException) {
            return $this->ajaxExceptionHandler($ajaxException);
        }

        return $this->ajaxSuccess($ret);
    }

    /**
     * @return array
     *
     * @throws AjaxException
     */
    protected function executeUpdateRecursive()
    {
        if (empty($this->postVar['pid']) || empty($this->postVar['field'])) {

            throw new AjaxException(
                $this->translate('message.warning.incomplete_data_received.message'),
                '[0x4FBF3C04]',
                self::HTTP_STATUS_BAD_REQUEST
            );
        }

        $pid         = $this->postVar['pid'];
        $sysLanguage = (int)$this->postVar['sysLanguage'];

        $page = BackendUtility::getRecord('pages', $pid);
        $list = $this->index($page, 999, $sysLanguage, array());

        $count = 0;
        foreach ($list as $key => $page) {
            $this->postVar['pid'] = $key;
            $this->executeUpdate();
            $count++;
        }

        return array(
            'updateCount' => $count,  //not in use yet
        );
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
    protected function updatePageTableField($pid, $sysLanguage, $fieldName, $fieldValue)
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
                        $this->translate('message.error.no_language_overlay_found'),
                        '[0x4FBF3C05]',
                        self::HTTP_STATUS_BAD_REQUEST
                    );
                }

                // ################
                // UPDATE
                // ################

                $this->tce()->updateDB(
                    'pages_language_overlay',
                    (int)$overlayId,
                    array(
                        $fieldName => $fieldValue
                    )
                );
                break;
            case 'pages':
                // Update field in page (also logs update event and clear cache for this page)
                $this->tce()->updateDB(
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
     * @inheritDoc
     */
    protected function getAjaxPrefix()
    {
        return self::AJAX_PREFIX;
    }

    /**
     * Returns array of classes which contain Ajax controllers with <ajaxPrefix> => <className)
     *
     * @todo replace class concatenation with e.g. 'UrlController::class' as of PHP 5.5  (renders $namespace obsolete)
     *
     * @return array
     */
    public static function getBackendAjaxClassNames()
    {
        $nameSpace = __NAMESPACE__ . '\\PageSeo';
        $ajaxPrefix = self::AJAX_PREFIX;

        return array(
            //$ajaxPrefix . AdvancedController::LIST_TYPE      => $nameSpace . '\\' . 'AdvancedController',//unused
            $ajaxPrefix . GeoController::LIST_TYPE           => $nameSpace . '\\' . 'GeoController',
            $ajaxPrefix . MetaDataController::LIST_TYPE      => $nameSpace . '\\' . 'MetaDataController',
            $ajaxPrefix . PageTitleController::LIST_TYPE     => $nameSpace . '\\' . 'PageTitleController',
            $ajaxPrefix . PageTitleSimController::LIST_TYPE  => $nameSpace . '\\' . 'PageTitleSimController',
            $ajaxPrefix . SearchEnginesController::LIST_TYPE => $nameSpace . '\\' . 'SearchEnginesController',
            $ajaxPrefix . UrlController::LIST_TYPE           => $nameSpace . '\\' . 'UrlController',
        );
    }
}
