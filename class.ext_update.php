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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Update class for the extension manager.
 *
 * @package    TYPO3
 * @subpackage metaseo
 */
class ext_update
{

    // ########################################################################
    // Attributes
    // ########################################################################

    /**
     * Message list
     *
     * @var array
     */
    protected $messageList = array();

    /**
     * Clear cache (after update)
     *
     * @var boolean
     */
    protected $clearCache = false;


    /**
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $databaseConnection;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Main update function called by the extension manager.
     *
     * @return string
     */
    public function main()
    {
        $this->processUpdates();

        $ret = $this->generateOutput();

        return $ret;
    }

    /**
     * Called by the extension manager to determine if the update menu entry
     * should be shown.
     *
     * @return bool
     */
    public function access()
    {
        return true;
    }


    /**
     * The actual update function. Add your update task in here.
     */
    protected function processUpdates()
    {
        // migration priorty->priority with v2.0.0
        $this->renameDatabaseTableField(
            'tx_metaseo_setting_root',
            'sitemap_priorty',
            'sitemap_priority'
        );
        $this->renameDatabaseTableField(
            'tx_metaseo_setting_root',
            'sitemap_priorty_depth_multiplier',
            'sitemap_priority_depth_multiplier'
        );
        $this->renameDatabaseTableField(
            'tx_metaseo_setting_root',
            'sitemap_priorty_depth_modificator',
            'sitemap_priority_depth_modificator'
        );

        $this->processClearCache();
    }

    /**
     * Clear cache
     */
    protected function processClearCache()
    {

        if ($this->clearCache) {

            // Init TCE
            $TCE        = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
            $TCE->admin = 1;
            $TCE->clear_cacheCmd('all');

            // Add msg
            $msgTitle  = 'Clearing TYPO3 cache';
            $msgStatus = FlashMessage::INFO;
            $msgText   = 'Cleared all caches due migration';

            $this->addMessage($msgStatus, $msgTitle, $msgText);
        }
    }

    /**
     * Add message
     *
     * @param integer $status  Status code
     * @param string  $title   Title
     * @param string  $message Message
     */
    protected function addMessage($status, $title, $message)
    {
        if (!empty($message) && is_array($message)) {
            $liStyle = 'style="margin-bottom: 0;"';

            $message = '<ul><li ' . $liStyle . '>' . implode('</li><li ' . $liStyle . '>', $message) . '</li></ul>';
        }

        $this->messageList[] = array($status, $title, $message);
    }

    /**
     * Generate message title from database row (using title and uid)
     *
     * @param   array $row Database row
     *
     * @return  string
     */
    protected function messageTitleFromRow(array $row)
    {
        $ret = array();

        if (!empty($row['title'])) {
            $ret[] = '"' . htmlspecialchars($row['title']) . '"';
        }

        if (!empty($row['uid'])) {
            $ret[] = '[UID #' . htmlspecialchars($row['uid']) . ']';
        }

        return implode(' ', $ret);
    }

    /**
     * Generates output by using flash messages
     *
     * @return string
     */
    protected function generateOutput()
    {
        $output = '';

        foreach ($this->messageList as $message) {
            $flashMessage = GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                $message[2],
                $message[1],
                $message[0]
            );
            $output .= $flashMessage->render();
        }

        return $output;
    }


    /**
     * Renames a tabled field and does some plausibility checks.
     *
     * @param  string $table
     * @param  string $oldFieldName
     * @param  string $newFieldName
     * @return int
     */
    protected function renameDatabaseTableField($table, $oldFieldName, $newFieldName)
    {
        $title = 'Renaming "' . $table . ':' . $oldFieldName . '" to "' . $table . ':' . $newFieldName . '": ';

        $currentTableFields = $this->databaseConnection->admin_get_fields($table);

        if ($currentTableFields[$newFieldName]) {
            $message = 'Field ' . $table . ':' . $newFieldName . ' already existing.';
            $status = FlashMessage::OK;
        } else {
            if (!$currentTableFields[$oldFieldName]) {
                $message = 'Field ' . $table . ':' . $oldFieldName . ' not existing';
                $status = FlashMessage::ERROR;
            } else {
                $sql = 'ALTER TABLE ' . $table . ' CHANGE COLUMN ' . $oldFieldName . ' ' . $newFieldName . ' ' .
                    $currentTableFields[$oldFieldName]['Type'];

                if ($this->databaseConnection->admin_query($sql) === false) {
                    $message = ' SQL ERROR: ' . $this->databaseConnection->sql_error();
                    $status = FlashMessage::ERROR;
                } else {
                    $message = 'OK!';
                    $status = FlashMessage::OK;
                }
            }
        }

        $this->addMessage($status, $title, $message);

        return $status;
    }
}
