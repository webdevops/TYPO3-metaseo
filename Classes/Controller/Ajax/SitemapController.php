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
use Metaseo\Metaseo\DependencyInjection\Utility\HttpUtility;
use Metaseo\Metaseo\Exception\Ajax\AjaxException;
use Metaseo\Metaseo\Utility\DatabaseUtility;
use Metaseo\Metaseo\Utility\SitemapUtility;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;

/**
 * TYPO3 Backend ajax module sitemap
 */
class SitemapController extends AbstractAjaxController implements SitemapInterface
{
    const AJAX_PREFIX = 'tx_metaseo_controller_ajax_sitemap';

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
     * Return sitemap entry list for root tree
     *
     * @return    array
     */
    protected function executeIndex()
    {
        // Init
        $rootPid      = (int)$this->postVar['pid'];
        $offset       = (int)$this->postVar['start'];
        $itemsPerPage = (int)$this->postVar['pagingSize'];

        $searchFulltext      = trim((string)$this->postVar['criteriaFulltext']);
        $searchPageUid       = trim((int)$this->postVar['criteriaPageUid']);
        $searchPageLanguage  = trim((string)$this->postVar['criteriaPageLanguage']);
        $searchPageDepth     = trim((string)$this->postVar['criteriaPageDepth']);
        $searchIsBlacklisted = (bool)trim((string)$this->postVar['criteriaIsBlacklisted']);

        // ############################
        // Criteria
        // ############################
        $where = array();

        // Root pid limit
        $where[] = 's.page_rootpid = ' . (int)$rootPid;

        // Fulltext
        if (!empty($searchFulltext)) {
            $where[] = 's.page_url LIKE ' . DatabaseUtility::quote('%' . $searchFulltext . '%', 'tx_metaseo_sitemap');
        }

        // Page id
        if (!empty($searchPageUid)) {
            $where[] = 's.page_uid = ' . (int)$searchPageUid;
        }

        // Language
        if ($searchPageLanguage != -1 && strlen($searchPageLanguage) >= 1) {
            $where[] = 's.page_language = ' . (int)$searchPageLanguage;
        }

        // Depth
        if ($searchPageDepth != -1 && strlen($searchPageDepth) >= 1) {
            $where[] = 's.page_depth = ' . (int)$searchPageDepth;
        }

        if ($searchIsBlacklisted) {
            $where[] = 's.is_blacklisted = 1';
        }

        // Filter blacklisted page types
        $where[] = DatabaseUtility::conditionNotIn(
            'p.doktype',
            SitemapUtility::getDoktypeBlacklist()
        );

        // Build where
        $where = DatabaseUtility::buildCondition($where);

        // ############################
        // Pager
        // ############################

        // Fetch total count of items with this filter settings
        $query     = 'SELECT COUNT(*) AS count
                        FROM tx_metaseo_sitemap s
                             INNER JOIN pages p ON p.uid = s.page_uid
                       WHERE ' . $where;
        $itemCount = DatabaseUtility::getOne($query);

        // ############################
        // Sort
        // ############################
        // default sort
        $sort = 's.page_depth ASC, s.page_uid ASC';

        if (!empty($this->sortField) && !empty($this->sortDir)) {
            // already filtered
            $sort = $this->sortField . ' ' . $this->sortDir;
        }

        // ############################
        // Fetch sitemap
        // ############################
        $query = 'SELECT s.uid,
                        s.page_rootpid,
                        s.page_uid,
                        s.page_language,
                        s.page_url,
                        s.page_depth,
                        s.page_type,
                        s.is_blacklisted,
                        p.tx_metaseo_is_exclude,
                        FROM_UNIXTIME(s.tstamp) as tstamp,
                        FROM_UNIXTIME(s.crdate) as crdate
                   FROM tx_metaseo_sitemap s
                        INNER JOIN pages p ON p.uid = s.page_uid
                  WHERE ' . $where . '
               ORDER BY ' . $sort . '
                  LIMIT ' . (int)$offset . ', ' . (int)$itemsPerPage;
        $list  = DatabaseUtility::getAll($query);

        return array(
            'results' => $itemCount,
            'rows'    => $list,
        );
    }

    /**
     * @inheritDoc
     */
    public function blacklistAction($params = array(), AjaxRequestHandler &$ajaxObj = null)
    {
        try {
            $this->init();
            $ajaxObj->setContent($this->executeBlacklist());
        } catch (Exception $exception) {
            $this->ajaxExceptionHandler($exception, $ajaxObj);
        }

        $ajaxObj->setContentFormat(self::CONTENT_FORMAT_JSON);
        $ajaxObj->render();
    }

    /*
     * Blacklist sitemap entries
     *
     * @return array
     *
     * @throws AjaxException
     */
    protected function executeBlacklist()
    {
        $uidList = $this->postVar['uidList'];
        $rootPid = (int)$this->postVar['pid'];

        $uidList = DatabaseUtility::connection()->cleanIntArray($uidList);

        if (empty($uidList) || empty($rootPid)) {

            throw new AjaxException(
                'message.warning.incomplete_data_received.message',
                '[0x4FBF3C10]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $where   = array();
        $where[] = 'page_rootpid = ' . (int)$rootPid;
        $where[] = DatabaseUtility::conditionIn('uid', $uidList);
        $where   = DatabaseUtility::buildCondition($where);

        $query = 'UPDATE tx_metaseo_sitemap
                     SET is_blacklisted = 1
                   WHERE ' . $where;
        DatabaseUtility::exec($query);

        return array();
    }

    /**
     * @inheritDoc
     */
    public function whitelistAction($params = array(), AjaxRequestHandler &$ajaxObj = null)
    {
        try {
            $this->init();
            $ajaxObj->setContent($this->executeWhitelist());
        } catch (Exception $exception) {
            $this->ajaxExceptionHandler($exception, $ajaxObj);
        }

        $ajaxObj->setContentFormat(self::CONTENT_FORMAT_JSON);
        $ajaxObj->render();
    }

    /*
     * Whitelist sitemap entries
     *
     * @return array
     *
     * @throws AjaxException
     */
    protected function executeWhitelist()
    {
        $uidList = $this->postVar['uidList'];
        $rootPid = (int)$this->postVar['pid'];

        $uidList = DatabaseUtility::connection()->cleanIntArray($uidList);

        if (empty($uidList) || empty($rootPid)) {

            throw new AjaxException(
                'message.warning.incomplete_data_received.message',
                '[0x4FBF3C12]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $where   = array();
        $where[] = 'page_rootpid = ' . (int)$rootPid;
        $where[] = DatabaseUtility::conditionIn('uid', $uidList);
        $where   = DatabaseUtility::buildCondition($where);

        $query = 'UPDATE tx_metaseo_sitemap
                     SET is_blacklisted = 0
                   WHERE ' . $where;
        DatabaseUtility::exec($query);

        return array();
    }

    /**
     * @inheritDoc
     */
    public function deleteAction($params = array(), AjaxRequestHandler &$ajaxObj = null)
    {
        try {
            $this->init();
            $ajaxObj->setContent($this->executeDelete());
        } catch (Exception $exception) {
            $this->ajaxExceptionHandler($exception, $ajaxObj);
        }

        $ajaxObj->setContentFormat(self::CONTENT_FORMAT_JSON);
        $ajaxObj->render();
    }

    /**
     * Delete sitemap entries
     *
     * @return array
     *
     * @throws AjaxException
     */
    protected function executeDelete()
    {
        $uidList = $this->postVar['uidList'];
        $rootPid = (int)$this->postVar['pid'];

        $uidList = DatabaseUtility::connection()->cleanIntArray($uidList);

        if (empty($uidList) || empty($rootPid)) {

            throw new AjaxException(
                'message.warning.incomplete_data_received.message',
                '[0x4FBF3C11]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $where   = array();
        $where[] = 'page_rootpid = ' . (int)$rootPid;
        $where[] = DatabaseUtility::conditionIn('uid', $uidList);
        $where   = DatabaseUtility::buildCondition($where);

        $query = 'DELETE FROM tx_metaseo_sitemap
                         WHERE ' . $where;
        DatabaseUtility::exec($query);

        return array();
    }

    /**
     * @inheritDoc
     */
    public function deleteAllAction($params = array(), AjaxRequestHandler &$ajaxObj = null)
    {
        try {
            $this->init();
            $ajaxObj->setContent($this->executeDeleteAll());
        } catch (Exception $exception) {
            $this->ajaxExceptionHandler($exception, $ajaxObj);
        }

        $ajaxObj->setContentFormat(self::CONTENT_FORMAT_JSON);
        $ajaxObj->render();
    }

    /**
     * Delete all sitemap entries
     *
     * @return array
     *
     * @throws AjaxException
     */
    protected function executeDeleteAll()
    {
        $rootPid = (int)$this->postVar['pid'];

        if (empty($rootPid)) {

            throw new AjaxException(
                'message.warning.incomplete_data_received.message',
                '[0x4FBF3C12]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $where   = array();
        $where[] = 'page_rootpid = ' . (int)$rootPid;
        $where   = DatabaseUtility::buildCondition($where);

        $query = 'DELETE FROM tx_metaseo_sitemap
                         WHERE ' . $where;
        DatabaseUtility::exec($query);

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
     * @return array
     */
    public static function getBackendAjaxClassNames()
    {
        return array(
            self::AJAX_PREFIX => __CLASS__,
        );
    }
}
