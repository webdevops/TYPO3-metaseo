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

namespace Metaseo\Metaseo\Utility;

/**
 * Console utility
 */
class ConsoleUtility
{

    /**
     * Write output (without forcing newline)
     *
     * @param string  $message Message text
     * @param integer $padding Pad message
     */
    public static function write($message = null, $padding = null)
    {
        if ($padding > 0) {
            $message = str_pad($message, $padding, ' ');
        }

        self::stdOut($message);
    }

    /**
     * Send output to STD_OUT
     *
     * @param string $message Message text
     */
    public static function stdOut($message = null)
    {
        if (defined('TYPO3_cliMode')) {
            file_put_contents('php://stdout', $message);
        }
    }

    /**
     * Write output (forcing newline)
     *
     * @param string $message Message text
     */
    public static function writeLine($message = null)
    {
        self::stdOut($message . "\n");
    }

    /**
     * Write error (without forcing newline)
     *
     * @param string  $message Message text
     * @param integer $padding Pad message
     */
    public static function writeError($message = null, $padding = null)
    {
        if ($padding > 0) {
            $message = str_pad($message, $padding, ' ');
        }

        self::stdError($message);
    }

    /**
     * Send output to STD_ERR
     *
     * @param string $message Message text
     */
    public static function stdError($message = null)
    {
        if (defined('TYPO3_cliMode')) {
            file_put_contents('php://stderr', $message);
        }
    }

    /**
     * Write error (forcing newline)
     *
     * @param string $message Message
     */
    public static function writeErrorLine($message = null)
    {
        $message .= "\n";

        self::stdError($message);
    }

    /**
     * Exit cli script with return code
     *
     * @param integer $exitCode Exit code (0 = success)
     */
    public static function terminate($exitCode)
    {
        if (defined('TYPO3_cliMode')) {
            exit($exitCode);
        }
    }
}
