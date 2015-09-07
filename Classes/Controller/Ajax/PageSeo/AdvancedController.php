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

namespace Metaseo\Metaseo\Controller\Ajax\PageSeo;

use Metaseo\Metaseo\Controller\Ajax\AbstractPageSeoController;
use Metaseo\Metaseo\DependencyInjection\Utility\HttpUtility;
use Metaseo\Metaseo\Exception\Ajax\AjaxException;
use Metaseo\Metaseo\Utility\DatabaseUtility;

class AdvancedController extends AbstractPageSeoController
{
    const LIST_TYPE = 'advanced';

    /**
     * @inheritDoc
     */
    protected function initFieldList()
    {
        $this->fieldList = array();
    }

    /**
     * @inheritDoc
     */
    public function executeIndex()
    {
        if (empty($this->postVar['pid'])) {

            throw new AjaxException(
                'message.error.typo3_page_not_found',
                '[0x4FBF3C0E]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $ret = array();

        $pid         = (int)$this->postVar['pid'];
        $sysLanguage = (int)$this->postVar['sysLanguage'];


        // check uid of pages language overlay
        $query   = 'SELECT tag_name,
                           tag_value
                      FROM tx_metaseo_metatag
                     WHERE pid = ' . (int)$pid . '
                       AND sys_language_uid = ' . (int)$sysLanguage;
        $rowList = DatabaseUtility::getAll($query);
        foreach ($rowList as $row) {
            $ret[$row['tag_name']] = $row['tag_value'];
        }

        return array(
            'results' => count($ret),
            'rows'    => array_values($ret),
        );
    }

    /**
     * @inheritDoc
     */
    protected function executeUpdate()
    {
        if (empty($this->postVar['pid']) || empty($this->postVar['metaTags'])) {

            throw new AjaxException(
                'message.error.typo3_page_not_found',
                '[0x4FBF3C0F]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $pid         = (int)$this->postVar['pid'];
        $metaTagList = (array)$this->postVar['metaTags'];
        $sysLanguage = (int)$this->postVar['sysLanguage'];


        $this->clearMetaTags($pid, $sysLanguage);
        $metaTagGroup = 2;
        foreach ($metaTagList as $metaTagName => $metaTagValue) {
            if (is_scalar($metaTagValue)) {
                $metaTagValue = trim($metaTagValue);

                if (strlen($metaTagValue) > 0) {
                    $this->updateMetaTag($pid, $sysLanguage, $metaTagName, $metaTagValue);
                }
            } elseif (is_array($metaTagValue)) {
                foreach ($metaTagValue as $subTagName => $subTagValue) {
                    $this->updateMetaTag(
                        $pid,
                        $sysLanguage,
                        array($metaTagName, $subTagName),
                        $subTagValue,
                        $metaTagGroup++
                    );
                }
            }
        }

        return array();
    }

    /**
     * Clear all meta tags for one page
     *
     * @param integer      $pid         PID
     * @param integer|null $sysLanguage system language id
     */
    protected function clearMetaTags($pid, $sysLanguage)
    {
        $query = 'DELETE FROM tx_metaseo_metatag
                        WHERE pid = ' . (int)$pid . '
                          AND sys_language_uid = ' . (int)$sysLanguage;
        DatabaseUtility::exec($query);
    }

    /**
     * @param integer      $pid         PID
     * @param integer|NULL $sysLanguage System language id
     * @param string|array $metaTag     MetaTag name
     * @param string       $value       MetaTag value
     * @param integer      $tagGroup    MetaTag group
     */
    protected function updateMetaTag($pid, $sysLanguage, $metaTag, $value, $tagGroup = null)
    {
        $tstamp   = time();
        $crdate   = time();
        $cruserId = $this->getBackendUserAuthentication()->user['uid'];

        $subTagName = '';

        if (is_array($metaTag)) {
            list($metaTag, $subTagName) = $metaTag;
        }

        if ($tagGroup === null) {
            $tagGroup = 1;
        }

        $query = 'INSERT INTO tx_metaseo_metatag
                              (pid, tstamp, crdate, cruser_id, sys_language_uid,
                                  tag_name, tag_subname, tag_value, tag_group)
                       VALUES (
                             ' . (int)$pid . ',
                             ' . (int)$tstamp . ',
                             ' . (int)$crdate . ',
                             ' . (int)$cruserId . ',
                             ' . (int)$sysLanguage . ',
                             ' . DatabaseUtility::quote($metaTag) . ',
                             ' . DatabaseUtility::quote($subTagName) . ',
                             ' . DatabaseUtility::quote($value) . ',
                             ' . (int)$tagGroup . '
                       ) ON DUPLICATE KEY UPDATE
                               tstamp    = VALUES(tstamp),
                               tag_value = VALUES(tag_value)';
        DatabaseUtility::execInsert($query);
    }
}
