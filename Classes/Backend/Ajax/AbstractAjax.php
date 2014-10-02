<?php
namespace Metaseo\Metaseo\Backend\Ajax;

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
 * TYPO3 Backend ajax module base
 *
 * @package     TYPO3
 * @subpackage  metaseo
 */
abstract class AbstractAjax {

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * POST vars (transformed from json)
     *
     * @var array
     */
    protected $_postVar = array();

    /**
     * Sorting field
     */
    protected $_sortField = NULL;

    /**
     * Sorting dir
     *
     * @var string
     */
    protected $_sortDir = NULL;

    /**
     * TCE
     *
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected $_tce = NULL;

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
     * Execute ajax call
     */
    public function main() {
        $ret = NULL;


        // Try to find method
        $function = '';
        if (!empty($_GET['cmd'])) {
            // GET-param
            $function = (string)$_GET['cmd'];

            // security
            $function = strtolower(trim($function));
            $function = preg_replace('[^a-z]', '', $function);
        }

        // Call function
        if (!empty($function)) {
            $method = '_execute' . $function;
            $call   = array($this, $method);

            if (is_callable($call)) {
                $this->_fetchParams();

                $this->_init();
                if ($this->_checkSessionToken()) {
                    $ret = $this->$method();
                }

            }
        }

        // Output json data
        header('Content-type: application/json');
        echo json_encode($ret);
        exit;
    }


    /**
     * Init
     */
    protected function _init() {
        // Include ajax local lang
        $GLOBALS['LANG']->includeLLFile('EXT:metaseo/locallang_ajax.xml');

        // Init form protection instance
        $this->_formProtection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'
        );
    }

    /**
     * Collect and process POST vars and stores them into $this->_postVars
     */
    protected function _fetchParams() {
        $rawPostVarList = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST();
        foreach ($rawPostVarList as $key => $value) {
            $this->_postVar[$key] = json_decode($value);
        }

        // Sorting data
        if (!empty($rawPostVarList['sort'])) {
            $this->_sortField = $this->_escapeSortField((string)$rawPostVarList['sort']);
        }

        if (!empty($rawPostVarList['dir'])) {
            switch (strtoupper($rawPostVarList['dir'])) {
                case 'ASC':
                    $this->_sortDir = 'ASC';
                    break;

                case 'DESC':
                    $this->_sortDir = 'DESC';
                    break;
            }
        }

    }

    /**
     * Escape for sql sort fields
     *
     * @param    string $value    Sort value
     * @return    string
     */
    protected function _escapeSortField($value) {
        return preg_replace('[^_a-zA-Z]', '', $value);
    }

    /**
     * Create an (cached) instance of t3lib_TCEmain
     *
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function _tce() {

        if ($this->_tce === NULL) {
            $this->_tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\DataHandling\\DataHandler'
            );
            $this->_tce->start(null, null);
        }

        return $this->_tce;
    }


    /**
     * Check if field is in table (TCA)
     *
     * @param   string $table  Table
     * @param   string $field  Field
     * @return  boolean
     */
    protected function _isFieldInTcaTable($table, $field) {
        return isset($GLOBALS['TCA'][$table]['columns'][$field]);
    }


    /**
     * Create session token
     *
     * @param   string $formName    Form name/Session token name
     * @return  string
     */
    protected function _sessionToken($formName) {
        $token = $this->_formProtection->generateToken($formName);
        return $token;
    }

    /**
     * Check session token
     *
     * @return    boolean
     */
    protected function _checkSessionToken() {

        if (empty($this->_postVar['sessionToken'])) {
            // No session token exists
            return FALSE;
        }

        $className = strtolower(str_replace('\\', '_', get_class($this)));

        $sessionToken = $this->_sessionToken($className);

        if ($this->_postVar['sessionToken'] === $sessionToken) {
            return TRUE;
        }

        return FALSE;
    }

}
