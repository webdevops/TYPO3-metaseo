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

namespace Metaseo\Metaseo\Dao;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\SingletonInterface;

class Dao implements SingletonInterface
{
    /**
     * DataHandler (TCE)
     *
     * @var \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected $dataHandler;

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
     * Return (cached) instance of t3lib_TCEmain
     *
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function getDataHandler()
    {
        $this->dataHandler->start(null, null);

        return $this->dataHandler;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @param DataHandler $dataHandler
     *
     * @return $this
     */
    public function setDataHandler(DataHandler $dataHandler)
    {
        $this->dataHandler = $dataHandler;

        return $this;
    }
}
