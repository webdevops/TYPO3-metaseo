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

use Exception;
use Metaseo\Metaseo\DependencyInjection\Utility\HttpUtility;
use Metaseo\Metaseo\Exception\Ajax\AjaxException;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * TYPO3 Backend ajax module base
 */
abstract class AbstractAjaxController
{
    const CONTENT_FORMAT_JSON = 'jsonbody';

    /**
     * Json status indicators
     */
    const JSON_ERROR        = 'error';
    const JSON_ERROR_NUMBER = 'errorNumber';

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
     * TYPO3 Object manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    // ########################################################################
    // Methods
    // ########################################################################

    public function __construct()
    {
        $this->postVar = array();
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
     * Returns a json formatted error response with http error status specified in the Exception
     * The message is created with $ajaxObj->setContent instead of setError because $ajaxObj->setError
     * always sets http status 500 and does not respect content format.
     *
     * @param Exception $exception
     * @param AjaxRequestHandler $ajaxObj
     *
     * @return void
     */
    protected function ajaxExceptionHandler(Exception $exception, AjaxRequestHandler &$ajaxObj)
    {
        $responseArray = array();
        if ($exception instanceof AjaxException) {
            $responseArray[self::JSON_ERROR] = $this->translate($exception->getMessage());
            $this->getHttpUtility()->sendHttpHeader($exception->getHttpStatus());
            $errorCode = $exception->getCode();
            if (!empty($errorCode)) {
                $responseArray[self::JSON_ERROR_NUMBER] = $exception->getCode();
            }
        } else {
            $responseArray[self::JSON_ERROR] = $exception->getMessage();
            $this->getHttpUtility()->sendHttpHeader(HttpUtility::HTTP_STATUS_INTERNAL_SERVER_ERROR);
        }
        $ajaxObj->setContent($responseArray);
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
     * @return HttpUtility
     */
    protected function getHttpUtility()
    {
        return $this->objectManager->get('Metaseo\\Metaseo\\DependencyInjection\\Utility\\HttpUtility');
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
