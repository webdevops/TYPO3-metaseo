<?php

use TYPO3\CMS\Core\Messaging\FlashMessage;

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
 * Update class for the extension manager.
 *
 * @package    TYPO3
 * @subpackage metaseo
 */
class ext_update {

    // ########################################################################
    // Attributs
    // ########################################################################

    /**
     * Message list
     *
     * @var array
     */
    protected $_messageList = array();

    /**
     * Clear cache (after update)
     *
     * @var boolean
     */
    protected $_clearCache = FALSE;

    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Main update function called by the extension manager.
     *
     * @return string
     */
    public function main() {
        $this->_processUpdates();

        $ret = $this->_generateOutput();

        return $ret;
    }

    /**
     * Called by the extension manager to determine if the update menu entry
     * should by showed.
     *
     * @return bool
     * @todo find a better way to determine if update is needed or not.
     */
    public function access() {
        return TRUE;
    }


    /**
     * The actual update function. Add your update task in here.
     */
    protected function _processUpdates() {
        //$this->_processUpdateTypoScriptIncludes();
        //$this->_processUpdateScheduler();

        $this->_processClearCache();
    }

    /**
     * Clear cache
     */
    protected function _processClearCache() {

        if( $this->_clearCache ) {

            // Init TCE
            $TCE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
            $TCE->admin = 1;
            $TCE->clear_cacheCmd('all');

            // Add msg
            $msgTitle  = 'Clearing TYPO3 cache';
            $msgStatus = FlashMessage::INFO;
            $msgText   = 'Cleared all caches due migration';

            $this->_addMessage($msgStatus, $msgTitle, $msgText);
        }
    }

    /**
     * Add message
     *
     * @param integer $status   Status code
     * @param string  $title    Title
     * @param string  $message  Message
     */
    protected function _addMessage($status, $title, $message) {
        if( !empty($message) && is_array($message) ) {
            $liStyle = 'style="margin-bottom: 0;"';

            $message = '<ul><li '.$liStyle.'>'.implode('</li><li '.$liStyle.'>', $message).'</li></ul>';
        }

        $this->_messageList[] = array($status, $title, $message);
    }

    /**
     * Generate message title from database row (using title and uid)
     *
     * @param   array   $row    Database row
     * @return  string
     */
    protected function _messageTitleFromRow($row) {
        $ret = array();

        if( !empty($row['title']) ) {
            $ret[] = '"'.htmlspecialchars($row['title']).'"';
        }

        if( !empty($row['uid']) ) {
            $ret[] = '[UID #'.htmlspecialchars($row['uid']).']';
        }

        return implode(' ', $ret);
    }

    /**
     * Generates output by using flash messages
     *
     * @return string
     */
    protected function _generateOutput() {
        $output = '';

        foreach ($this->_messageList as $message) {
            $flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                $message[2],
                $message[1],
                $message[0]);
            $output .= $flashMessage->render();
        }

        return $output;
    }

}
