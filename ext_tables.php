<?php
defined('TYPO3_MODE') or exit();

$extPath    = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);
$extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY);

// ############################################################################
// TABLES
// ############################################################################

// ################
// Settings Root
// ################
//\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToInsertRecords('tx_metaseo_setting_root');
//\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_metaseo_setting_root');

$TCA['tx_metaseo_setting_root'] = array(
    'ctrl'        => array(
        'title'             => 'LLL:EXT:metaseo/locallang_db.xml:tx_metaseo_setting_root',
        'label'             => 'uid',
        'adminOnly'         => true,
        'dynamicConfigFile' => $extPath . 'Configuration/TCA/MetaseoSettingRoot.php',
        'iconfile'          => 'page',
        'hideTable'         => true,
        'dividers2tabs'     => true,
    ),
    'feInterface' => array(),
    'interface'   => array(
        'always_description' => true,
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tx_metaseo_setting_root',
    'EXT:metaseo/locallang_csh_setting_root.xml'
);

/*
$TCA['tx_metaseo_setting_page'] = array(
	'ctrl' => array(
		'title'				=> 'LLL:EXT:metaseo/locallang_db.xml:tx_metaseo_setting_page',
		'label'				=> 'uid',
		'adminOnly'			=> 1,
		'dynamicConfigFile'	=> $extPath.'tca.php',
		'iconfile'			=> 'page',
		'hideTable'			=> TRUE,
	),
	'feInterface' => array (
	),
	'interface' => array(
	),
);
*/

// ############################################################################
// Backend
// ############################################################################

if (TYPO3_MODE == 'BE') {

    // ####################################################
    // Module category "WEB"
    // ####################################################

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Metaseo.' . $_EXTKEY,
        'web',
        'pageseo',
        '', # Position
        array('BackendPageSeo' => 'main,metadata,geo,searchengines,url,pagetitle,pagetitlesim'), # Controller array
        array(
            'access' => 'user,group',
            'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Backend/Icons/ModuleSeo.png',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.pageseo.xml',
        )
    );

    // ####################################################
    // Module category "SEO"
    // ####################################################

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        $_EXTKEY,
        'metaseo',
        '',
        '',
        array(),
        array(
            'access' => 'user,group',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.main.xml',
        )
    );


    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Metaseo.' . $_EXTKEY,
        'metaseo',
        'controlcenter',
        '',
        array('BackendControlCenter' => 'main'),
        array(
            'access' => 'user,group',
            'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Backend/Icons/ModuleControlCenter.png',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.controlcenter.xml',
        )
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Metaseo.' . $_EXTKEY,
        'metaseo',
        'sitemap',
        'after:controlcenter',
        array('BackendSitemap' => 'main,sitemap'),
        array(
            'access' => 'user,group',
            'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Backend/Icons/ModuleSitemap.png',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.sitemap.xml',
        )
    );
}

// ############################################################################
// CONFIGURATION
// ############################################################################

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'MetaSEO');
