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

use Exception;
use Metaseo\Metaseo\Controller\AbstractAjaxController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo as PageSeo;
use Metaseo\Metaseo\DependencyInjection\Utility\HttpUtility;
use Metaseo\Metaseo\Exception\Ajax\AjaxException;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;

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
    public function indexAction($params = array(), AjaxRequestHandler &$ajaxObj = null)
    {
        try {
            $this->init();
            $ajaxObj->setContent($this->executeIndex());
        } catch (Exception $exception) {
            $this->ajaxExceptionHandler($exception, $ajaxObj);
        }

        $ajaxObj->setContentFormat(self::CONTENT_FORMAT_JSON);
        $ajaxObj->render();
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
                'message.error.typo3_page_not_found',
                '[0x4FBF3C0C]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $page = $this->getPageSeoDao()->getPageById($pid);

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
        return $this->getPageSeoDao()->index($page, $depth, $sysLanguage, $this->fieldList);
    }

    /**
     * @inheritDoc
     */
    public function updateAction($params = array(), AjaxRequestHandler &$ajaxObj = null)
    {
        try {
            $this->init();
            $ajaxObj->setContent($this->executeUpdate());
        } catch (Exception $exception) {
            $this->ajaxExceptionHandler($exception, $ajaxObj);
        }

        $ajaxObj->setContentFormat(self::CONTENT_FORMAT_JSON);
        $ajaxObj->render();
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
                'message.warning.incomplete_data_received.message',
                '[0x4FBF3C02]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $pid         = (int)$this->postVar['pid'];
        $fieldName   = (string)$this->postVar['field'];
        $fieldValue  = (string)$this->postVar['value'];
        $sysLanguage = (int)$this->postVar['sysLanguage'];

        // validate field name: must match exactly to list of known field names
        if (!in_array($fieldName, array_merge($this->fieldList, array('title')))) {
            throw new AjaxException(
                'message.warning.incomplete_data_received.message',
                '[0x4FBF3C23]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        if (empty($fieldName)) {

            throw new AjaxException(
                'message.warning.incomplete_data_received.message',
                '[0x4FBF3C03]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        // ############################
        // Security checks
        // ############################

        // generates the excludelist, based on TCA/exclude-flag and non_exclude_fields for the user:
        $excludedTablesAndFields = array_flip($this->getDataHandler()->getExcludeListArray());

        // check if user is able to modify pages
        if (!$this->getBackendUserAuthentication()->check('tables_modify', 'pages')) {
            // No access

            throw new AjaxException(
                'message.error.access_denied',
                '[0x4FBF3BE2]',
                HttpUtility::HTTP_STATUS_UNAUTHORIZED
            );
        }

        $page = $this->getPageSeoDao()->getPageById($pid);

        // check if page exists and user can edit this specific record
        if (empty($page) || !$this->getBackendUserAuthentication()->doesUserHaveAccess($page, 2)) {
            // No access

            throw new AjaxException(
                'message.error.access_denied',
                '[0x4FBF3BCF]',
                HttpUtility::HTTP_STATUS_UNAUTHORIZED
            );
        }

        // check if user is able to modify the field of pages
        if (isset($excludedTablesAndFields['pages-' . $fieldName])
            && !$this->getBackendUserAuthentication()
                ->check('non_exclude_fields', 'pages:' . $fieldName)
        ) {
            // No access

            throw new AjaxException(
                'message.error.access_denied',
                '[0x4FBF3BD9]',
                HttpUtility::HTTP_STATUS_UNAUTHORIZED
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
                    'message.error.access_denied',
                    '[0x4FBF3BE2]',
                    HttpUtility::HTTP_STATUS_UNAUTHORIZED
                );
            }

            // check if user is able to modify the field of pages
            if (isset($excludedTablesAndFields['pages_language_overlay-' . $fieldName])
                && !$this->getBackendUserAuthentication()
                    ->check('non_exclude_fields', 'pages_language_overlay:' . $fieldName)
            ) {
                // No access

                throw new AjaxException(
                    'message.error.access_denied',
                    '[0x4FBF3BD9]',
                    HttpUtility::HTTP_STATUS_UNAUTHORIZED
                );
            }
        }

        // ############################
        // Transformations
        // ############################

        switch ($fieldName) {
            case 'lastUpdated':
                // transform to unix timestamp
                $fieldValue = strtotime($fieldValue);
                break;
        }

        // ############################
        // Update
        // ############################

        return $this->getPageSeoDao()->updatePageTableField($pid, $sysLanguage, $fieldName, $fieldValue);
    }

    /**
     * @inheritDoc
     */
    public function updateRecursiveAction($params = array(), AjaxRequestHandler &$ajaxObj = null)
    {
        try {
            $this->init();
            $ajaxObj->setContent($this->executeUpdateRecursive());
        } catch (Exception $exception) {
            $this->ajaxExceptionHandler($exception, $ajaxObj);
        }

        $ajaxObj->setContentFormat(self::CONTENT_FORMAT_JSON);
        $ajaxObj->render();
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
                'message.warning.incomplete_data_received.message',
                '[0x4FBF3C04]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $pid         = $this->postVar['pid'];
        $sysLanguage = (int)$this->postVar['sysLanguage'];

        $page = $this->getPageSeoDao()->getPageById($pid);

        $list = $this->getPageSeoDao()->index($page, 999, $sysLanguage, array());

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
     * @inheritDoc
     */
    protected function getAjaxPrefix()
    {
        return self::AJAX_PREFIX;
    }

    /**
     * @return \Metaseo\Metaseo\Dao\PageSeoDao
     */
    protected function getPageSeoDao()
    {
        return $this
            ->objectManager
            ->get('Metaseo\\Metaseo\\Dao\\PageSeoDao')
            ->setPageTreeView($this->getPageTreeView())
            ->setDataHandler($this->getDataHandler());
    }

    /**
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function getDataHandler()
    {
        return $this->objectManager->get('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
    }

    /**
     * @return \TYPO3\CMS\Backend\Tree\View\PageTreeView
     */
    protected function getPageTreeView()
    {
        return $this->objectManager->get('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
    }

    /**
     * @return \Metaseo\Metaseo\DependencyInjection\Utility\FrontendUtility
     */
    protected function getFrontendUtility()
    {
        return $this
            ->objectManager
            ->get('Metaseo\\Metaseo\\DependencyInjection\\Utility\\FrontendUtility')
            ->setPageRepository($this->getPageRepository());
    }

    /**
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected function getPageRepository()
    {
        $pageRepository = $this->objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
        $pageRepository->versioningPreview = true; //enable preview mode
        return $pageRepository;
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
            //$ajaxPrefix . PageSeo\AdvancedController::LIST_TYPE
            //  => $nameSpace . '\\' . 'AdvancedController',//unused
            $ajaxPrefix . PageSeo\GeoController::LIST_TYPE           => $nameSpace . '\\' . 'GeoController',
            $ajaxPrefix . PageSeo\MetaDataController::LIST_TYPE      => $nameSpace . '\\' . 'MetaDataController',
            $ajaxPrefix . PageSeo\PageTitleController::LIST_TYPE     => $nameSpace . '\\' . 'PageTitleController',
            $ajaxPrefix . PageSeo\PageTitleSimController::LIST_TYPE  => $nameSpace . '\\' . 'PageTitleSimController',
            $ajaxPrefix . PageSeo\SearchEnginesController::LIST_TYPE => $nameSpace . '\\' . 'SearchEnginesController',
            $ajaxPrefix . PageSeo\UrlController::LIST_TYPE           => $nameSpace . '\\' . 'UrlController',
        );
    }
}
