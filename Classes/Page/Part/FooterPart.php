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

namespace Metaseo\Metaseo\Page\Part;

use Metaseo\Metaseo\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;

/**
 * Page Footer
 */
class FooterPart extends AbstractPart
{
    /**
     * Content object renderer
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * Add Page Footer
     *
     * @param    string $title Default page title (rendered by TYPO3)
     *
     * @return    string            Modified page title
     */
    public function main($title)
    {
        // INIT
        $ret        = array();
        $tsSetup    = $GLOBALS['TSFE']->tmpl->setup;
        $tsServices = array();

        $beLoggedIn = isset($GLOBALS['BE_USER']->user['username']);

        $disabledHeaderCode = false;
        if (!empty($tsSetup['config.']['disableAllHeaderCode'])) {
            $disabledHeaderCode = true;
        }

        if (!empty($tsSetup['plugin.']['metaseo.']['services.'])) {
            $tsServices = $tsSetup['plugin.']['metaseo.']['services.'];
        }

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'pageFooterSetup', $this, $tsServices);

        // #########################################
        // GOOGLE ANALYTICS
        // #########################################

        if (!empty($tsServices['googleAnalytics'])) {
            $gaConf = $tsServices['googleAnalytics.'];

            $gaEnabled = true;

            if ($disabledHeaderCode && empty($gaConf['enableIfHeaderIsDisabled'])) {
                $gaEnabled = false;
            }

            if ($gaEnabled && !(empty($gaConf['showIfBeLogin']) && $beLoggedIn)) {
                // Build Google Analytics service
                $ret['ga'] = $this->buildGoogleAnalyticsCode($tsServices, $gaConf);

                if (!empty($gaConf['trackDownloads']) && !empty($gaConf['trackDownloadsScript'])) {
                    $ret['ga.trackdownload'] = $this->serviceGoogleAnalyticsTrackDownloads($tsServices, $gaConf);
                }
            } elseif ($gaEnabled && $beLoggedIn) {
                // Disable caching
                $GLOBALS['TSFE']->set_no_cache('MetaSEO: Google Analytics code disabled, backend login detected');

                // Backend login detected, disable cache because this page is viewed by BE-users
                $ret['ga.disabled'] = '<!-- Google Analytics disabled, '
                    . 'Page cache disabled - Backend-Login detected -->';
            }
        }


        // #########################################
        // PIWIK
        // #########################################
        if (!empty($tsServices['piwik.'])
            && !empty($tsServices['piwik.']['url'])
            && !empty($tsServices['piwik.']['id'])
        ) {
            $piwikConf = $tsServices['piwik.'];

            $piwikEnabled = true;

            if ($disabledHeaderCode && empty($piwikConf['enableIfHeaderIsDisabled'])) {
                $piwikEnabled = false;
            }

            if ($piwikEnabled && !(empty($piwikConf['showIfBeLogin']) && $beLoggedIn)) {
                // Build Piwik service
                $ret['piwik'] = $this->buildPiwikCode($tsServices, $piwikConf);
            } elseif ($piwikEnabled && $beLoggedIn) {
                // Disable caching
                $GLOBALS['TSFE']->set_no_cache('MetaSEO: Piwik code disabled, backend login detected');

                // Backend login detected, disable cache because this page is viewed by BE-users
                $ret['piwik.disabled'] = '<!-- Piwik disabled, Page cache disabled - Backend-Login detected -->';
            }
        }

        // Call hook
        GeneralUtility::callHookAndSignal(__CLASS__, 'pageFooterOutput', $this, $ret);

        return implode("\n", $ret);
    }

    /**
     * Google analytics
     *
     * @param  array $tsServices SetupTS of services
     * @param  array $gaConf     Google Analytics configuration
     *
     * @return string
     */
    public function buildGoogleAnalyticsCode(array $tsServices, array $gaConf)
    {
        $ret        = array();
        $gaCodeList = Typo3GeneralUtility::trimExplode(',', $tsServices['googleAnalytics']);

        foreach ($gaCodeList as $gaCode) {
            $customCode = '';
            if (!empty($gaConf['customizationCode'])) {
                $customCode .= "\n" . $this->cObj->cObjGetSingle(
                    $gaConf['customizationCode'],
                    $gaConf['customizationCode.']
                );
            }

            $this->cObj->data['gaCode']                  = $gaCode;
            $this->cObj->data['gaIsAnonymize']           = (int)!empty($gaConf['anonymizeIp']);
            $this->cObj->data['gaDomainName']            = $gaConf['domainName'];
            $this->cObj->data['gaCustomizationCode']     = $customCode;
            $this->cObj->data['gaUseUniversalAnalytics'] = (int)!empty($gaConf['universalAnalytics']);

            // Build code
            $ret[] = $this->cObj->cObjGetSingle($gaConf['template'], $gaConf['template.']);
        }

        // Build all GA codes
        $ret = implode("\n", $ret);

        return $ret;
    }

    /**
     * Google analytics
     *
     * @param  array $tsServices SetupTS of services
     * @param  array $gaConf     Google Analytics configuration
     *
     * @return string
     * @todo $tsServices is never used
     */
    public function serviceGoogleAnalyticsTrackDownloads(array $tsServices, array $gaConf)
    {
        $jsFile = Typo3GeneralUtility::getFileAbsFileName($gaConf['trackDownloadsScript']);
        $jsfile = preg_replace('/^' . preg_quote(PATH_site, '/') . '/i', '', $jsFile);

        $ret = '<script type="text/javascript" src="' . htmlspecialchars($jsfile) . '"></script>';

        return $ret;
    }

    /**
     * Piwik
     *
     * @param  array $tsServices SetupTS of services
     * @param  array $piwikConf  Piwik configuration
     *
     * @return string
     */
    public function buildPiwikCode(array $tsServices, array $piwikConf)
    {
        $ret           = array();
        $piwikCodeList = Typo3GeneralUtility::trimExplode(',', $piwikConf['id']);

        foreach ($piwikCodeList as $piwikCode) {
            $customCode = '';
            if (!empty($piwikConf['customizationCode'])) {
                $customCode .= "\n";
                $customCode .= $this->cObj->cObjGetSingle(
                    $piwikConf['customizationCode'],
                    $piwikConf['customizationCode.']
                );
            }

            // remove last slash
            $piwikConf['url'] = rtrim($piwikConf['url'], '/');

            $this->cObj->data['piwikUrl']               = $piwikConf['url'];
            $this->cObj->data['piwikId']                = $piwikCode;
            $this->cObj->data['piwikDomainName']        = $piwikConf['domainName'];
            $this->cObj->data['piwikCookieDomainName']  = $piwikConf['cookieDomainName'];
            $this->cObj->data['piwikDoNotTrack']        = $piwikConf['doNotTrack'];
            $this->cObj->data['piwikCustomizationCode'] = $customCode;

            // Build code
            $ret[] = $this->cObj->cObjGetSingle($piwikConf['template'], $piwikConf['template.']);
        }

        // Build all piwik codes
        $ret = implode("\n", $ret);

        return $ret;
    }
}
