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

namespace Metaseo\Metaseo\Page;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract page
 */
abstract class AbstractPage
{
    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;

    /**
     * TypoScript Setup
     *
     * @var array
     */
    protected $tsSetup = array();

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Constructor
     */
    public function __construct()
    {
        // Init object manager
        $this->objectManager = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Object\\ObjectManager'
        );
    }

    /**
     * Main
     *
     * @return mixed
     */
    abstract public function main();

    /**
     * Show error
     *
     * @param    string $msg Message
     */
    protected function showError($msg = null)
    {
        if ($msg === null) {
            $msg = 'Sitemap is not available, please check your configuration';
        }

        header('HTTP/1.0 503 Service Unavailable');
        $GLOBALS['TSFE']->pageErrorHandler(true, null, $msg);
        exit;
    }
}
