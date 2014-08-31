<?php
namespace Metaseo\Metaseo\Backend\Module;

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
 * TYPO3 Backend module base
 *
 * @package     TYPO3
 * @subpackage  metaseo
 */
abstract class AbstractModule extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Backend Form Protection object
     *
     * @var \TYPO3\CMS\Core\FormProtection\BackendFormProtection
     */
    protected $_formProtection = NULL;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Initializes the controller before invoking an action method.
     *
     * @return void
     */
    protected function initializeAction() {

        // Init form protection instance
        $this->_formProtection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'
        );

    }

    /**
     * Translate key
     *
     * @param   string      $key        Translation key
     * @param   NULL|array  $arguments  Arguments (vsprintf)
     * @return  NULL|string
     */
    protected function _translate($key, $arguments = NULL) {
        $ret = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, $this->extensionName, $arguments);

        // Not translated handling
        if( $ret === NULL ) {
            $ret = '[-'.$key.'-]';
        }

        return $ret;
    }

    /**
     * Translate list
     *
     * @param   array $list   Translation keys
     * @return  array
     */
    protected function _translateList($list) {
        unset($token);
        foreach($list as &$token) {
            if( !empty($token) ) {
                if( is_array($token) ) {
                    $token = $this->_translateList($token);
                } else {
                    $token = $this->_translate($token);
                }
            }
        }
        unset($token);

        return $list;
    }

    /**
     * Create session token
     *
     * @param    string $formName    Form name/Session token name
     * @return    string
     */
    protected function _sessionToken($formName) {
        $token = $this->_formProtection->generateToken($formName);
        return $token;
    }

    /**
     * Ajax controller url
     *
     * @param   string  $ajaxCall Ajax Call
     * @return  string
     */
    protected function _ajaxControllerUrl($ajaxCall) {
        return $this->doc->backPath . 'ajax.php?ajaxID='.urlencode($ajaxCall);
    }

}