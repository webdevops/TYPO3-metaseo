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

namespace Metaseo\Metaseo\Hook;

use Metaseo\Metaseo\Utility\FrontendUtility;
use Metaseo\Metaseo\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;

/**
 * Http Header generator
 */
class HttpHook
{

    /**
     * Add HTTP Headers
     */
    public function main()
    {
        // INIT
        $tsSetup = $GLOBALS['TSFE']->tmpl->setup;
        $headers = array();

        // don't send any headers if headers are already sent
        if (headers_sent()) {
            return;
        }

        // Init caches
        $cacheIdentification = sprintf(
            '%s_%s_http',
            $GLOBALS['TSFE']->id,
            substr(sha1(FrontendUtility::getCurrentUrl()), 10, 30)
        );

        /** @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager */
        $objectManager = Typo3GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
        );
        $cacheManager  = $objectManager->get('TYPO3\\CMS\\Core\\Cache\\CacheManager');
        $cache         = $cacheManager->getCache('cache_pagesection');

        if (!empty($GLOBALS['TSFE']->tmpl->loaded)) {
            // ##################################
            // Non-Cached page
            // ##################################

            if (!empty($tsSetup['plugin.']['metaseo.']['metaTags.'])) {
                $tsSetupSeo = $tsSetup['plugin.']['metaseo.']['metaTags.'];

                // ##################################
                // W3C P3P Tags
                // ##################################
                $p3pCP        = null;
                $p3pPolicyUrl = null;

                if (!empty($tsSetupSeo['p3pCP'])) {
                    $p3pCP = $tsSetupSeo['p3pCP'];
                }

                if (!empty($tsSetupSeo['p3pPolicyUrl'])) {
                    $p3pPolicyUrl = $tsSetupSeo['p3pPolicyUrl'];
                }

                if (!empty($p3pCP) || !empty($p3pPolicyUrl)) {
                    $p3pHeader = array();

                    if (!empty($p3pCP)) {
                        $p3pHeader[] = 'CP="' . $p3pCP . '"';
                    }

                    if (!empty($p3pPolicyUrl)) {
                        $p3pHeader[] = 'policyref="' . $p3pPolicyUrl . '"';
                    }

                    $headers['P3P'] = implode(' ', $p3pHeader);
                }
            }

            // Store headers into cache
            $cache->set($cacheIdentification, $headers, array('pageId_' . $GLOBALS['TSFE']->id));
        } else {
            // #####################################
            // Cached page
            // #####################################

            // Fetched cached headers
            $cachedHeaders = $cache->get($cacheIdentification);

            if (!empty($cachedHeaders)) {
                $headers = $cachedHeaders;
            }
        }

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'httpHeaderOutput', $this, $headers);

        // #####################################
        // Sender headers
        // #####################################
        if (!empty($headers['P3P'])) {
            header('P3P: ' . $headers['P3P']);
        }
    }
}
