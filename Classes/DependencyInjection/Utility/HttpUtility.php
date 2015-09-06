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

namespace Metaseo\Metaseo\DependencyInjection\Utility;

use Exception;
use TYPO3\CMS\Core\SingletonInterface;

class HttpUtility implements SingletonInterface
{
    /**
     * Http Status Codes for Ajax
     *
     * @link https://dev.twitter.com/overview/api/response-codes
     */
    const HTTP_STATUS_OK                    = 200;
    const HTTP_STATUS_BAD_REQUEST           = 400;
    const HTTP_STATUS_UNAUTHORIZED          = 401;
    const HTTP_STATUS_FORBIDDEN             = 403;
    const HTTP_STATUS_NOT_FOUND             = 404;
    const HTTP_STATUS_NOT_ACCEPTABLE        = 406;
    const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;
    const HTTP_STATUS_SERVICE_UNAVAILABLE   = 503;

    /**
     * @param $httpStatusCode
     *
     * @return string
     *
     * @throws Exception
     */
    public function getHttpStatusMessage($httpStatusCode)
    {
        switch ($httpStatusCode) {
            case self::HTTP_STATUS_OK:
                return 'OK';
            case self::HTTP_STATUS_BAD_REQUEST:
                return 'Bad Request';
            case self::HTTP_STATUS_UNAUTHORIZED:
                return 'Unauthorized';
            case self::HTTP_STATUS_FORBIDDEN:
                return 'Forbidden';
            case self::HTTP_STATUS_NOT_FOUND:
                return 'Not Found';
            case self::HTTP_STATUS_NOT_ACCEPTABLE:
                return 'Not Acceptable';
            case self::HTTP_STATUS_INTERNAL_SERVER_ERROR:
                return 'Internal Server Error';
            case self::HTTP_STATUS_SERVICE_UNAVAILABLE:
                return 'Service Unavailable';
            default:
                throw new Exception('Http status message is not available.');
        }
    }

    /**
     * @param $httpStatusCode
     */
    public function sendHttpHeader($httpStatusCode)
    {
        if (!headers_sent()) {
            header('HTTP/1.0 ' . $httpStatusCode . ' ' . $this->getHttpStatusMessage($httpStatusCode));
        }
    }
}
