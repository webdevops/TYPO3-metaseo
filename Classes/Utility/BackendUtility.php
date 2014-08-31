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
 * Backend utility
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: BackendUtility.php 81080 2013-10-28 09:54:33Z mblaschke $
 */
class BackendUtility {

    /**
     * Fetch list of root pages (is_siteroot) in TYPO3 (cached)
     *
     * @return  array
     */
    public static function getRootPageList() {
        static $cache = NULL;

        if ($cache === NULL) {
            $query = 'SELECT uid,
                             pid,
                             title
                        FROM pages
                       WHERE is_siteroot = 1
                         AND deleted = 0';
            $cache = DatabaseUtility::getAllWithIndex($query, 'uid');
        }

        return $cache;
    }

    /**
     * Fetch list of setting entries
     *
     * @return  array
     */
    public static function getRootPageSettingList() {
        static $cache = NULL;

        if ($cache === NULL) {
            $query = 'SELECT seosr.*
                        FROM tx_metaseo_setting_root seosr
                             INNER JOIN pages p
                                ON  p.uid = seosr.pid
                                AND p.is_siteroot = 1
                                AND p.deleted = 0
                        WHERE seosr.deleted = 0';
            $cache = DatabaseUtility::getAllWithIndex($query, 'pid');
        }

        return $cache;
    }

}
