<?php

/*
 *  Copyright notice
 *
 *  (c) 2014 - 2017 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
 *  (c) 2007 - 2013 Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de> (tq_seo)
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

use ReflectionMethod;
use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;

class ExtensionManagementUtility
{
    const AJAX_METHOD_NAME_SUFFIX = 'Action';
    const AJAX_METHOD_DELIMITER = '::';

    /**
     * Registers all public methods of a specified class with method name suffix 'Action' as ajax actions
     * The ajax method names have the form <ajaxPrefix>::<ajaxMethod> (with 'Action' removed from the method)
     * or <ajaxMethod> for empty/unspecified <ajaxPrefix>
     *
     * @param string $qualifiedClassName
     * @param string $ajaxPrefix
     *
     * @return array
     */
    public static function getAjaxRoutesOfClass($qualifiedClassName, $ajaxPrefix = '')
    {
        $ajaxRoutes = array();
        if (!empty($ajaxPrefix)) {
            $ajaxPrefix = $ajaxPrefix . self::AJAX_METHOD_DELIMITER;
        }
        self::removeBeginningBackslash($qualifiedClassName);
        $reflectionClass = self::getReflectionClass($qualifiedClassName);
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if (self::isAjaxMethod($methodName)) {
                $ajaxMethodName = self::extractAjaxMethod($methodName);
                $ajaxRoutes[$ajaxPrefix . $ajaxMethodName] = array(
                    'path' => $ajaxPrefix . $ajaxMethodName,
                    'target' => $qualifiedClassName . '::' . $ajaxMethodName . self::AJAX_METHOD_NAME_SUFFIX, //AjaxID
                );
            }
        }
        return $ajaxRoutes;
    }



    /**
     * @param array $qualifiedClassNames
     *
     * @return array Ajax routes to be registered
     */
    public static function registerAjaxRoutes(array $qualifiedClassNames)
    {
        $ajaxRoutes = array();
        foreach ($qualifiedClassNames as $ajaxPrefix => $qualifiedClassName) {
            $ajaxRoutes = array_merge($ajaxRoutes, self::getAjaxRoutesOfClass($qualifiedClassName, $ajaxPrefix));
        }
        return $ajaxRoutes;
    }

    /**
     * @param string $methodName
     *
     * @return bool
     */
    protected static function isAjaxMethod($methodName)
    {
        $suffixLength = strlen(self::AJAX_METHOD_NAME_SUFFIX);

        return strlen($methodName) > $suffixLength
            && self::AJAX_METHOD_NAME_SUFFIX === substr($methodName, -1 * $suffixLength);
    }

    /**
     * @param string $methodName
     *
     * @return string
     */
    protected static function extractAjaxMethod($methodName)
    {
        $suffixLength = strlen(self::AJAX_METHOD_NAME_SUFFIX);

        return substr(
            $methodName,
            0,
            strlen($methodName) - $suffixLength
        );
    }

    /**
     * @param $qualifiedClassName
     */
    protected static function removeBeginningBackslash(&$qualifiedClassName)
    {
        if ($qualifiedClassName[0] === '\\') {
            $qualifiedClassName = substr($qualifiedClassName, 1);
        }
    }

    /**
     * @param $qualifiedClassName
     *
     * @return \ReflectionClass
     */
    protected static function getReflectionClass($qualifiedClassName)
    {
        return Typo3GeneralUtility::makeInstance('ReflectionClass', $qualifiedClassName);
    }
}
