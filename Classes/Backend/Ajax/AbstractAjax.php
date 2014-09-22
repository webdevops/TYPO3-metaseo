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
    protected $postVar = array();

    /**
     * Sorting field
     */
    protected $sortField = NULL;

    /**
     * Sorting dir
     *
     * @var string
     */
    protected $sortDir = NULL;

    /**
     * TCE
     *
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler
     * @inject
     */
    protected $tce = NULL;

    /**
     * TYPO3 Object manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager = NULL;

    /**
     * Backend Form Protection object
     *
     * @var \TYPO3\CMS\Core\FormProtection\BackendFormProtection
     * @inject
     */
    protected $formProtection = NULL;

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
            $method = 'execute' . $function;
            $call   = array($this, $method);

            if (is_callable($call)) {
                $this->fetchParams();

                $this->init();
                if ($this->checkSessionToken()) {
                    $ret = $this->$method();
                }

            }
        }

        // Output json data
        header('Content-type: application/json;charset=UTF-8');
        echo json_encode($ret);
        exit;
    }


    /**
     * Init
     */
    protected function init() {
        // Include ajax local lang
        $GLOBALS['LANG']->includeLLFile('EXT:metaseo/locallang_ajax.xml');

        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

        // Init form protection instance
        $this->formProtection = $this->objectManager->get(
            'TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection'
        );
    }

    /**
     * Collect and process POST vars and stores them into $this->postVars
     */
    protected function fetchParams() {
        $rawPostVarList = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST();
        foreach ($rawPostVarList as $key => $value) {
            $this->postVar[$key] = json_decode($value);
        }

        // Sorting data
        if (!empty($rawPostVarList['sort'])) {
            $this->sortField = $this->escapeSortField((string)$rawPostVarList['sort']);
        }

        if (!empty($rawPostVarList['dir'])) {
            switch (strtoupper($rawPostVarList['dir'])) {
                case 'ASC':
                    $this->sortDir = 'ASC';
                    break;

                case 'DESC':
                    $this->sortDir = 'DESC';
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
    protected function escapeSortField($value) {
        return preg_replace('[^_a-zA-Z]', '', $value);
    }

    /**
     * Create an (cached) instance of t3lib_TCEmain
     *
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function tce() {

        if ($this->tce === NULL) {
            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler tce */
            $this->tce = $this->objectManager->get('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
            $this->tce->start(null, null);
        }

        return $this->tce;
    }


    /**
     * Check if field is in table (TCA)
     *
     * @param   string $table  Table
     * @param   string $field  Field
     * @return  boolean
     */
    protected function isFieldInTcaTable($table, $field) {
        return isset($GLOBALS['TCA'][$table]['columns'][$field]);
    }


    /**
     * Create session token
     *
     * @param   string $formName    Form name/Session token name
     * @return  string
     */
    protected function sessionToken($formName) {
        $token = $this->formProtection->generateToken($formName);
        return $token;
    }

    /**
     * Check session token
     *
     * @return    boolean
     */
    protected function checkSessionToken() {

        if (empty($this->postVar['sessionToken'])) {
            // No session token exists
            return FALSE;
        }

        $className = strtolower(str_replace('\\', '_', get_class($this)));

        $sessionToken = $this->sessionToken($className);

        if ($this->postVar['sessionToken'] === $sessionToken) {
            return TRUE;
        }

        return FALSE;
    }

}
