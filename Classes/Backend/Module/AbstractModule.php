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

namespace Metaseo\Metaseo\Backend\Module;

use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * TYPO3 Backend module base
 */
abstract class AbstractModule extends ActionController
{
    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Backend Form Protection object
     *
     * @var \TYPO3\CMS\Core\FormProtection\BackendFormProtection
     */
    protected $formProtection;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Initializes the controller before invoking an action method.
     *
     * Override this method to solve tasks which all actions have in
     * common.
     *
     * @return void
     * @api
     */
    protected function initializeAction()
    {
        $this->formProtection = FormProtectionFactory::get();
    }

    /**
     * Translate list
     *
     * @param   array $list Translation keys
     *
     * @return  array
     */
    protected function translateList(array $list)
    {
        unset($token);
        foreach ($list as &$token) {
            if (!empty($token)) {
                if (is_array($token)) {
                    $token = $this->translateList($token);
                } else {
                    $token = $this->translate($token);
                }
            }
        }
        unset($token);

        return $list;
    }

    /**
     * Translate key
     *
     * @param   string     $key       Translation key
     * @param   null|array $arguments Arguments (vsprintf)
     *
     * @return  null|string
     */
    protected function translate($key, array $arguments = null)
    {
        $ret = LocalizationUtility::translate($key, $this->extensionName, $arguments);

        // Not translated handling
        if ($ret === null) {
            $ret = '[-' . $key . '-]';
        }

        return $ret;
    }

    /**
     * Create session token
     *
     * @param    string $formName Form name/Session token name
     *
     * @return    string
     */
    protected function sessionToken($formName)
    {
        $token = $this->formProtection->generateToken($formName);

        return $token;
    }
}
