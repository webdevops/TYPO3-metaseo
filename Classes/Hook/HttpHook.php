<?php
namespace Metaseo\Metaseo\Hook;

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
 * Http Header generator
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: HttpHook.php 81080 2013-10-28 09:54:33Z mblaschke $
 */
class HttpHook {

    /**
     * Add HTTP Headers
     */
    public function main() {
        // INIT
        $tsSetup  = $GLOBALS['TSFE']->tmpl->setup;
        $headers  = array();

        // dont send any headers if headers are already sent
        if (headers_sent()) {
            return;
        }

        if (!empty($GLOBALS['TSFE']->tmpl->loaded)) {
            // ##################################
            // Non-Cached page
            // ##################################

            if (!empty($tsSetup['plugin.']['metaseo.']['metaTags.'])) {
                $tsSetupSeo = $tsSetup['plugin.']['metaseo.']['metaTags.'];

                // ##################################
                // W3C P3P Tags
                // ##################################
                $p3pCP        = NULL;
                $p3pPolicyUrl = NULL;

                if (!empty($tsSetupSeo['p3pCP'])) {
                    $p3pCP = $tsSetupSeo['p3pCP'];
                }

                if (!empty($tsSetupSeo['p3pPolicyUrl'])) {
                    $p3pPolicyUrl = $tsSetupSeo['p3pPolicyUrl'];
                }

                if (!empty($p3pCP) || !empty($p3pPolicyUrl)) {
                    $p3pHeaders = array();

                    if (!empty($p3pCP)) {
                        $p3pHeader[] = 'CP="' . $p3pCP . '"';
                    }

                    if (!empty($p3pPolicyUrl)) {
                        $p3pHeader[] = 'policyref="' . $p3pPolicyUrl . '"';
                    }

                    $headers['P3P'] = implode(' ', $p3pHeader);

                    // cache informations
                    $curentTemplate     = end($GLOBALS['TSFE']->tmpl->hierarchyInfo);
                    $currentTemplatePid = $curentTemplate['pid'];
                    \Metaseo\Metaseo\Utility\CacheUtility::set($currentTemplatePid, 'http', 'p3p', $headers['P3P']);
                }
            }

        } else {
            // #####################################
            // Cached page
            // #####################################
            // build root pid list
            $rootPidList = array();
            foreach ($GLOBALS['TSFE']->rootLine as $pageRow) {
                $rootPidList[$pageRow['uid']] = $pageRow['uid'];
            }

            // fetch from cache
            $cacheList = \Metaseo\Metaseo\Utility\CacheUtility::getList('http', 'p3p');
            foreach ($rootPidList as $pageId) {
                if (!empty($cacheList[$pageId])) {
                    $headers['P3P'] = $cacheList[$pageId];
                    break;
                }
            }
        }

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('httpheader-output', $this, $headers);

        // #####################################
        // Sender headers
        // #####################################
        if (!empty($headers['P3P'])) {
            header('P3P: ' . $headers['P3P']);
        }

    }
}
