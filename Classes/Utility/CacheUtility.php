<?php
namespace Metaseo\Metaseo\Utility;

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
 * Cache utility
 *
 * @package     metaseo
 * @subpackage  Utility
 * @version     $Id: CacheUtility.php 81080 2013-10-28 09:54:33Z mblaschke $
 */
class CacheUtility {

    /**
     * Get cache entry
     *
     * @param   integer $pageId     Page UID
     * @param   string  $section    Cache section
     * @param   string  $identifier Cache identifier
     * @return  string
     */
    static public function get($pageId, $section, $identifier) {
        $ret = NULL;

        $query = 'SELECT cache_content FROM tx_metaseo_cache
                    WHERE page_uid = ' . (int)$pageId . '
                      AND cache_section = ' . DatabaseUtility::quote($section, 'tx_metaseo_cache') . '
                      AND cache_identifier = ' . DatabaseUtility::quote($identifier, 'tx_metaseo_cache');
        $ret = DatabaseUtility::getOne($query);

        return $ret;
    }

    /**
     * Set cache entry
     *
     * @param   integer $pageId     Page UID
     * @param   string  $section    Cache section
     * @param   string  $identifier Cache identifier
     * @param   string  $value      Cache content
     * @return  boolean
     */
    static public function set($pageId, $section, $identifier, $value) {

        try {
            $query = 'INSERT INTO tx_metaseo_cache (page_uid, cache_section, cache_identifier, cache_content)
                        VALUES(
                            ' . (int)$pageId . ',
                            ' . DatabaseUtility::quote($section, 'tx_metaseo_cache') . ',
                            ' . DatabaseUtility::quote($identifier, 'tx_metaseo_cache') . ',
                            ' . DatabaseUtility::quote($value, 'tx_metaseo_cache') . '
                        ) ON DUPLICATE KEY UPDATE cache_content = ' . DatabaseUtility::quote($value, 'tx_metaseo_cache');
            DatabaseUtility::exec($query);
        } catch ( \Exception $e ) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Get cache list
     *
     * @param   string $section    Cache section
     * @param   string $identifier Cache identifier
     * @return  array
     */
    static public function getList($section, $identifier) {
        $query = 'SELECT page_uid, cache_content FROM tx_metaseo_cache
                    WHERE cache_section = ' . DatabaseUtility::quote($section, 'tx_metaseo_cache') . '
                      AND cache_identifier = ' . DatabaseUtility::quote($identifier, 'tx_metaseo_cache');
        $ret = DatabaseUtility::getList($query);

        return $ret;
    }

    /**
     * Clear cache entry
     *
     * @param   integer $pageId     Page UID
     * @param   string  $section    Cache section
     * @param   string  $identifier Cache identifier
     * @return  boolean
     */
    static public function remove($pageId, $section, $identifier) {
        $pageId     = (int)$pageId;

        try {
            $query = 'DELETE FROM tx_metaseo_cache
                    WHERE page_uid = ' . (int)$pageId . '
                      AND cache_section = ' . DatabaseUtility::quote($section, 'tx_metaseo_cache') . '
                      AND cache_identifier = ' . DatabaseUtility::quote($identifier, 'tx_metaseo_cache');
            DatabaseUtility::exec($query);
        } catch ( \Exception $e ) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Clear cache by page
     *
     * @param   integer $pageId Page UID
     * @return  boolean
     */
    static public function clearByPage($pageId) {
        try {
            $query = 'DELETE FROM tx_metaseo_cache
                            WHERE page_uid = ' . (int)$pageId;
            DatabaseUtility::exec($query);
        } catch ( \Exception $e ) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Clear cache by section
     *
     * @param   string $section    Cache section
     * @return  boolean
     */
    static public function clearBySection($section) {
        try {
            $query = 'DELETE FROM tx_metaseo_cache
                            WHERE cache_section = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($section, 'tx_metaseo_cache');
            DatabaseUtility::exec($query);
        } catch ( \Exception $e ) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Clear all cache
     *
     * @return boolean
     */
    static public function clearAll() {
        try {
            $query = 'TRUNCATE tx_metaseo_cache';
            DatabaseUtility::exec($query);
        } catch ( \Exception $e ) {
            return FALSE;
        }

        return TRUE;
    }


    /**
     * Clear expired entries
     *
     * @return boolean
     */
    static public function expire() {

        // not supported currently

        return TRUE;
    }

}
