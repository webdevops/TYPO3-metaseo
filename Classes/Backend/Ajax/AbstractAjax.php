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

namespace Metaseo\Metaseo\Backend\Ajax;

use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 Backend ajax module base
 */
abstract class AbstractAjax
{
    /**
     * Json status indicators
     */
    const JSON_SUCCESS      = 'success';
    const JSON_ERROR        = 'error';
    const JSON_ERROR_NUMBER = 'errorNumber';

    /**
     * Http Status Codes for Ajax
     *
     * @link https://dev.twitter.com/overview/api/response-codes
     */
    const HTTP_STATUS_BAD_REQUEST           = 400;
    const HTTP_STATUS_UNAUTHORIZED          = 401;
    const HTTP_STATUS_FORBIDDEN             = 403;
    const HTTP_STATUS_NOT_FOUND             = 404;
    const HTTP_STATUS_NOT_ACCEPTABLE        = 406;
    const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;
    const HTTP_STATUS_SERVICE_UNAVAILABLE   = 503;

    /**
     * @var array key/value pairs of Http Status Codes
     */
    protected $httpStatus = array(
        self::HTTP_STATUS_BAD_REQUEST           => 'Bad Request',
        self::HTTP_STATUS_UNAUTHORIZED          => 'Unauthorized',
        self::HTTP_STATUS_FORBIDDEN             => 'Forbidden',
        self::HTTP_STATUS_NOT_FOUND             => 'Not Found',
        self::HTTP_STATUS_NOT_ACCEPTABLE        => 'Not Acceptable',
        self::HTTP_STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
        self::HTTP_STATUS_SERVICE_UNAVAILABLE   => 'Service Unavailable',
    );


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
    protected $sortField;

    /**
     * Sorting dir
     *
     * @var string
     */
    protected $sortDir;

    /**
     * TCE
     *
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler
     * @inject
     */
    protected $tce;

    /**
     * TYPO3 Object manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * Backend Form Protection object
     *
     * @var \TYPO3\CMS\Core\FormProtection\BackendFormProtection
     * @inject
     */
    protected $formProtection;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Execute ajax call
     */
    public function main()
    {
        $ajaxMethodName = $this->extractAjaxMethodName();

        $ret = $this->callAjaxMethod($ajaxMethodName);

        // Output json data
        header('Content-type: application/json;charset=UTF-8');
        echo json_encode($ret);
        exit;
    }

    /**
     * @return string
     */
    protected function extractAjaxMethodName()
    {
        // Try to find method
        $ajaxMethodName = '';
        if (!empty($_GET['cmd'])) {
            // GET-param
            $ajaxMethodName = (string)$_GET['cmd'];

            // security
            $ajaxMethodName = strtolower(trim($ajaxMethodName));
            $ajaxMethodName = preg_replace('[^a-z]', '', $ajaxMethodName);
        }

        return $ajaxMethodName;
    }

    /**
     * @param string $ajaxMethodName
     *
     * @return array
     */
    protected function callAjaxMethod($ajaxMethodName)
    {
        //check if empty
        if (empty($ajaxMethodName)) {

            return $this->ajaxErrorTranslate(
                'message.warning.ajax_method_name_not_exist.message',
                '[0x4FBF3C00]',
                self::HTTP_STATUS_BAD_REQUEST
            );
        }

        $methodName = 'execute' . $ajaxMethodName;
        $call       = array($this, $methodName);

        //check if not callable
        if (!is_callable($call)) {

            return $this->ajaxErrorTranslate(
                'message.warning.ajax_method_name_not_exist.message',
                '[0x4FBF3C07]',
                self::HTTP_STATUS_BAD_REQUEST
            );
        }

        //init
        $this->fetchParams();
        $this->init();

        if (!$this->checkSessionToken()) {

            return $this->ajaxErrorTranslate(
                'message.error.access_denied',
                '[0x4FBF3C06]',
                self::HTTP_STATUS_UNAUTHORIZED
            );
        }

        // Call function
        try {
            $ajaxArray = $this->$methodName();
        } catch (Exception $e) {
            //todo: log exception?

            return $this->ajaxErrorTranslate(
                'message.error.internal_server_error',
                '[0x4FBF3C07]',
                self::HTTP_STATUS_INTERNAL_SERVER_ERROR
            );
        }

        return $ajaxArray;
    }

    /**
     * Collect and process POST vars and stores them into $this->postVars
     */
    protected function fetchParams()
    {
        $rawPostVarList = GeneralUtility::_POST();
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
     * @param    string $value Sort value
     *
     * @return    string
     */
    protected function escapeSortField($value)
    {
        return preg_replace('[^_a-zA-Z]', '', $value);
    }

    /**
     * Init
     */
    protected function init()
    {
        // Include ajax local lang
        $this->getLanguageService()->includeLLFile('EXT:metaseo/Resources/Private/Language/locallang.xlf');

        $this->objectManager = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
        );

        // Init form protection instance
        $this->formProtection = $this->objectManager->get('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection');
    }

    /**
     * Check session token
     *
     * @return    boolean
     */
    protected function checkSessionToken()
    {

        if (empty($this->postVar['sessionToken'])) {
            // No session token exists
            return false;
        }

        $className = strtolower(str_replace('\\', '_', get_class($this)));

        $sessionToken = $this->sessionToken($className);

        if ($this->postVar['sessionToken'] === $sessionToken) {
            return true;
        }

        return false;
    }

    /**
     * Create session token
     *
     * @param   string $formName Form name/Session token name
     *
     * @return  string
     */
    protected function sessionToken($formName)
    {
        $token = $this->formProtection->generateToken($formName);

        return $token;
    }

    /**
     * Create an (cached) instance of t3lib_TCEmain
     *
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function tce()
    {

        if ($this->tce === null) {
            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler tce */
            $this->tce = $this->objectManager->get('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
            $this->tce->start(null, null);
        }

        return $this->tce;
    }

    /**
     * Check if field is in table (TCA)
     *
     * @param   string $table Table
     * @param   string $field Field
     *
     * @return  boolean
     */
    protected function isFieldInTcaTable($table, $field)
    {
        return isset($GLOBALS['TCA'][$table]['columns'][$field]);
    }

    /**
     * @param string $messageKey
     * @param string $errorNumber
     * @param int    $httpStatus
     *
     * @return array
     */
    protected function ajaxErrorTranslate($messageKey = '', $errorNumber = '', $httpStatus = 400)
    {
        return $this->ajaxError(
            $this->translate($messageKey),
            $errorNumber,
            $httpStatus
        );
    }

    /**
     * @param string $errorMessage
     * @param string $errorNumber
     * @param int    $httpStatus
     *
     * @return array
     */
    protected function ajaxError($errorMessage = '', $errorNumber = '', $httpStatus = 400)
    {
        $httpStatus = (int)$httpStatus;
        header('HTTP/1.0 ' . $httpStatus . ' ' . $this->httpStatus[$httpStatus]);

        $responseArray = array(
            self::JSON_ERROR => $errorMessage
        );

        if (!empty($errorNumber)) {
            $responseArray[self::JSON_ERROR_NUMBER] = $errorNumber;
        }

        return $responseArray;
    }

    protected function ajaxSuccess(array $data = array())
    {
        $data[self::JSON_SUCCESS] = true;

        return $data;
    }

    /**
     * Translate a key to the current chosen language
     *
     * @param $messageKey string
     *
     * @return string
     */
    protected function translate($messageKey)
    {
        return $this
            ->getLanguageService()
            ->getLL($messageKey);
    }

    /**
     * Get the TYPO3 CMS LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
