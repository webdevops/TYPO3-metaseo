<?php
defined('TYPO3_MODE') or exit;

// ############################################################################
// TABLES
// ############################################################################

// ################
// Settings Root
// ################

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tx_metaseo_setting_root',
    'EXT:metaseo/Resources/Private/Language/locallang.tca.xml'
);

// allow pages which contain such a record to be copied by users without throwing errors
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_metaseo_setting_root');

// ############################################################################
// CONFIGURATION
// ############################################################################

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'MetaSEO');
