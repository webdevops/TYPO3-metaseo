<?php
namespace Metaseo\Metaseo\Page\Part;

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
 * Page Footer
 *
 * @package     metaseo
 * @subpackage  lib
 * @version     $Id: FooterPart.php 84520 2014-03-28 10:33:24Z mblaschke $
 */
class FooterPart {

    /**
     * Add Page Footer
     *
     * @param    string $title    Default page title (rendered by TYPO3)
     * @return    string            Modified page title
     */
    public function main($title) {
        // INIT
        $ret        = array();
        $tsSetup    = $GLOBALS['TSFE']->tmpl->setup;
        $tsServices = array();

        $beLoggedIn = isset($GLOBALS['BE_USER']->user['username']);

        $disabledHeaderCode = FALSE;
        if (!empty($tsSetup['config.']['disableAllHeaderCode'])) {
            $disabledHeaderCode = TRUE;
        }

        if (!empty($tsSetup['plugin.']['metaseo.']['services.'])) {
            $tsServices = $tsSetup['plugin.']['metaseo.']['services.'];
        }

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('pagefooter-setup', $this, $tsServices);

        // #########################################
        // GOOGLE ANALYTICS
        // #########################################

        if (!empty($tsServices['googleAnalytics'])) {
            $gaConf = $tsServices['googleAnalytics.'];

            $gaEnabled = TRUE;

            if ($disabledHeaderCode && empty($gaConf['enableIfHeaderIsDisabled'])) {
                $gaEnabled = FALSE;
            }

            if ($gaEnabled && !(empty($gaConf['showIfBeLogin']) && $beLoggedIn)) {
                $tmp = '';

                $customCode = '';
                if (!empty($gaConf['customizationCode'])) {
                    $customCode .= "\n" . $this->cObj->cObjGetSingle(
                            $gaConf['customizationCode'],
                            $gaConf['customizationCode.']
                        );
                }

                $this->cObj->data['gaCode']                  = $tsServices['googleAnalytics'];
                $this->cObj->data['gaIsAnonymize']           = (int)!empty($gaConf['anonymizeIp']);
                $this->cObj->data['gaDomainName']            = $gaConf['domainName'];
                $this->cObj->data['gaCustomizationCode']     = $customCode;
                $this->cObj->data['gaUseUniversalAnalytics'] = (int)!empty($gaConf['universalAnalytics']);

                // Build code
                $ret['ga'] = $this->cObj->cObjGetSingle($gaConf['template'], $gaConf['template.']);

                if (!empty($gaConf['trackDownloads']) && !empty($gaConf['trackDownloadsScript'])) {
                    $jsFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
                        $gaConf['trackDownloadsScript']
                    );
                    $jsfile = preg_replace('/^' . preg_quote(PATH_site, '/') . '/i', '', $jsFile);

                    $ret['ga.trackdownload'] = '<script type="text/javascript" src="' . htmlspecialchars(
                            $jsfile
                        ) . '"></script>';
                }
            } elseif ($gaEnabled && $beLoggedIn) {
                // Backend login detected, disable cache because this page is viewed by BE-users
                $ret['ga.disabled'] = '<!-- Google Analytics disabled - Backend-Login detected -->';
            }
        }


        // #########################################
        // PIWIK
        // #########################################
        if (!empty($tsServices['piwik.']) && !empty($tsServices['piwik.']['url']) && !empty($tsServices['piwik.']['id'])) {
            $piwikConf = $tsServices['piwik.'];

            $piwikEnabled = TRUE;

            if ($disabledHeaderCode && empty($piwikConf['enableIfHeaderIsDisabled'])) {
                $piwikEnabled = FALSE;
            }

            if ($piwikEnabled && !(empty($piwikConf['showIfBeLogin']) && $beLoggedIn)) {
                $tmp = '';

                $customCode = '';
                if (!empty($piwikConf['customizationCode'])) {
                    $customCode .= "\n" . $this->cObj->cObjGetSingle(
                            $piwikConf['customizationCode'],
                            $piwikConf['customizationCode.']
                        );
                }

                // remove last slash
                $piwikConf['url'] = rtrim($piwikConf['url'], '/');

                $this->cObj->data['piwikUrl']               = $piwikConf['url'];
                $this->cObj->data['piwikId']                = $piwikConf['id'];
                $this->cObj->data['piwikDomainName']        = $piwikConf['domainName'];
                $this->cObj->data['piwikCookieDomainName']  = $piwikConf['cookieDomainName'];
                $this->cObj->data['piwikDoNotTrack']        = $piwikConf['doNotTrack'];
                $this->cObj->data['piwikCustomizationCode'] = $customCode;

                // Build code
                $ret['piwik'] = $this->cObj->cObjGetSingle($piwikConf['template'], $piwikConf['template.']);

            } elseif ($piwikEnabled && $beLoggedIn) {
                // Backend login detected, disable cache because this page is viewed by BE-users
                $ret['piwik.disabled'] = '<!-- Piwik disabled - Backend-Login detected -->';
            }
        }

        // Call hook
        \Metaseo\Metaseo\Utility\GeneralUtility::callHook('pagefooter-output', $this, $ret);

        return implode("\n", $ret);
    }
}
