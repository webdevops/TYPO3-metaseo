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

namespace Metaseo\Metaseo\Exception\Ajax;

use Exception;

class AjaxException extends Exception
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var int
     */
    protected $httpStatus;

    /**
     * @param string $messageTranslationKey    The error message translation KEY
     * @param string $code       Custom alphanumeric error code
     * @param int    $httpStatus http status code, e.g. 500 for internal server error.
     */
    public function __construct($messageTranslationKey = '', $code = '', $httpStatus = 500)
    {
        parent::__construct();
        $this->message    = (string) $messageTranslationKey;
        $this->code       = (string) $code;
        $this->httpStatus = (int)    $httpStatus;
    }

    public function getHttpStatus()
    {
        return $this->httpStatus;
    }
}
