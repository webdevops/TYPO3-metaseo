<?php
namespace Metaseo\Metaseo\Page;

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
 * Abstract page
 *
 * @package     metaseo
 * @subpackage  Page
 * @version     $Id: class.robots_txt.php 62700 2012-05-22 15:53:22Z mblaschke $
 */
abstract class AbstractPage {

    abstract public function main();

    /**
     * Show error
     *
     * @param    string $msg            Message
     */
    protected function showError($msg = NULL) {
        if ($msg === NULL) {
            $msg = 'Sitemap is not available, please check your configuration';
        }

        header('HTTP/1.0 503 Service Unavailable');
        $GLOBALS['TSFE']->pageErrorHandler(TRUE, NULL, $msg);
        exit;
    }

}