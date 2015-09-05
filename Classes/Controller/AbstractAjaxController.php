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

namespace Metaseo\Metaseo\Controller;

use Metaseo\Metaseo\Exception\Ajax\AjaxException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * TYPO3 Backend ajax module base
 */
abstract class AbstractAjaxController
{
    const HTTP_CONTENT_TYPE_JSON = 'Content-type: application/json;charset=UTF-8';

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
    protected $postVar;

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
     */
    protected $tce;

    /**
     * TYPO3 Object manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * to be used for unit tests ONLY!
     *
     * @var boolean
     */
    protected $returnAsArray;

    // ########################################################################
    // Methods
    // ########################################################################

    public function __construct()
    {
        $this->postVar = array();
        $this->returnAsArray = false;
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
        $this->fetchParams();

        // Include ajax local lang
        $this->getLanguageService()->includeLLFile('EXT:metaseo/Resources/Private/Language/locallang.xlf');

        if (!isset($this->objectManager)) {
            $this->objectManager = GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
            );
        }
    }

    /**
     * @return string
     */
    abstract protected function getAjaxPrefix();

    /**
     * Create an (cached) instance of t3lib_TCEmain
     *
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function getTce()
    {

        if (!isset($this->tce)) {
            /** @var \TYPO3\CMS\Core\DataHandling\DataHandler tce */
            $this->tce = $this->objectManager->get('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
            $this->tce->start(null, null);
        }

        return $this->tce;
    }

    /**
     * Check if field is in table (TCA)
     *
     * @param string $table Table
     * @param string $field Field
     *
     * @return boolean
     */
    protected function isFieldInTcaTable($table, $field)
    {
        return isset($GLOBALS['TCA'][$table]['columns'][$field]);
    }

    /**
     * @param AjaxException $ajaxException
     *
     * @return array
     *
     * @throws AjaxException
     */
    protected function ajaxExceptionHandler(AjaxException $ajaxException)
    {
        $httpStatus = $ajaxException->getHttpStatus();
        if (!headers_sent()) {
            header('HTTP/1.0 ' . $httpStatus . ' ' . $this->httpStatus[$httpStatus]);
        }

        $responseArray = array(
            self::JSON_ERROR => $ajaxException->getMessage()
        );

        $errorCode = (string) $ajaxException->getCode();
        if (!empty($errorCode)) {
            $responseArray[self::JSON_ERROR_NUMBER] = $ajaxException->getCode();
        }

        if ($this->returnAsArray) {
            //to be used for unit tests ONLY!
            throw $ajaxException;
        }

        return $this->renderExit($responseArray);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function ajaxSuccess(array $data = array())
    {
        $data[self::JSON_SUCCESS] = true;

        return $this->renderExit($data);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function renderExit(array $data)
    {
        if ($this->returnAsArray === true) {
            return $data;
        }
        if (!headers_sent()) {
            header(self::HTTP_CONTENT_TYPE_JSON);
        }
        echo json_encode($data);
        exit;
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

    /**
     * to be used for unit tests ONLY!
     *
     * @param boolean $returnAsArray
     *
     * @return $this
     */
    public function setReturnAsArray($returnAsArray = true)
    {
        $this->returnAsArray = $returnAsArray;

        return $this;
    }

    /**
     * @param ObjectManager $objectManager
     *
     * @return $this
     */
    public function setObjectManager(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;

        return $this;
    }
}
