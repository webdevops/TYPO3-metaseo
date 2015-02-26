<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$extPath    = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);
$extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY);

// ############################################################################
// TABLES
// ############################################################################

// ################
// Pages
// ################

$tempColumns = array(
    'tx_metaseo_pagetitle'        => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_pagetitle',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_pagetitle_rel'    => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_pagetitle_rel',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_pagetitle_prefix' => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_pagetitle_prefix',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_pagetitle_suffix' => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_pagetitle_suffix',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_inheritance'      => array(
        'exclude' => 1,
        'label'   => 'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_inheritance',
        'config'  => array(
            'type'     => 'select',
            'items'    => array(
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_inheritance.I.0',
                    0
                ),
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_inheritance.I.1',
                    1
                ),
            ),
            'size'     => 1,
            'maxitems' => 1
        )
    ),
    'tx_metaseo_is_exclude'       => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_is_exclude',
        'exclude' => 1,
        'config'  => array(
            'type' => 'check'
        )
    ),
    'tx_metaseo_canonicalurl'     => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_canonicalurl',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
            'wizards'  => Array(
                '_PADDING' => 2,
                'link'     => Array(
                    'type'         => 'popup',
                    'title'        => 'Link',
                    'icon'         => 'link_popup.gif',
                    'script'       => 'browse_links.php?mode=wizard&act=url',
                    'params'       => array(
                        'blindLinkOptions' => 'mail',
                    ),
                    'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
                ),
            ),
        )
    ),
    'tx_metaseo_priority'         => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_priority',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'int',
        )
    ),
    'tx_metaseo_change_frequency' => array(
        'exclude' => 1,
        'label'   => 'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_change_frequency',
        'config'  => array(
            'type'     => 'select',
            'items'    => array(
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_change_frequency.I.0',
                    0
                ),
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_change_frequency.I.1',
                    1
                ),
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_change_frequency.I.2',
                    2
                ),
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_change_frequency.I.3',
                    3
                ),
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_change_frequency.I.4',
                    4
                ),
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_change_frequency.I.5',
                    5
                ),
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_change_frequency.I.6',
                    6
                ),
                array(
                    'LLL:EXT:metaseo/locallang_db.php:pages.tx_metaseo_change_frequency.I.7',
                    7
                ),
            ),
            'size'     => 1,
            'maxitems' => 1
        )
    ),
    'tx_metaseo_geo_lat'          => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_geo_lat',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_geo_long'         => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_geo_long',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_geo_place'        => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_geo_place',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_geo_region'       => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_geo_region',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),

);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns, 1);

// TCA Palettes
$TCA['pages']['palettes']['tx_metaseo_pagetitle'] = array(
    'showitem'       => 'tx_metaseo_pagetitle,--linebreak--,tx_metaseo_pagetitle_prefix,tx_metaseo_pagetitle_suffix,--linebreak--,tx_metaseo_inheritance',
    'canNotCollapse' => 1
);

$TCA['pages']['palettes']['tx_metaseo_crawler'] = array(
    'showitem'       => 'tx_metaseo_is_exclude,--linebreak--,tx_metaseo_canonicalurl',
    'canNotCollapse' => 1
);

$TCA['pages']['palettes']['tx_metaseo_sitemap'] = array(
    'showitem'       => 'tx_metaseo_priority,--linebreak--,tx_metaseo_change_frequency',
    'canNotCollapse' => 1
);

$TCA['pages']['palettes']['tx_metaseo_geo'] = array(
    'showitem'       => 'tx_metaseo_geo_lat,--linebreak--,tx_metaseo_geo_long,--linebreak--,tx_metaseo_geo_place,--linebreak--,tx_metaseo_geo_region',
    'canNotCollapse' => 1
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    'tx_metaseo_pagetitle_rel',
    '1,4,7,3',
    'after:title'
);

// Put it for standard page
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:metaseo/locallang_tca.xml:pages.tab.seo;,--palette--;LLL:EXT:metaseo/locallang_tca.xml:pages.palette.pagetitle;tx_metaseo_pagetitle,--palette--;LLL:EXT:metaseo/locallang_tca.xml:pages.palette.geo;tx_metaseo_geo,--palette--;LLL:EXT:metaseo/locallang_tca.xml:pages.palette.crawler;tx_metaseo_crawler,--palette--;LLL:EXT:metaseo/locallang_tca.xml:pages.palette.sitemap;tx_metaseo_sitemap',
    '1,4,7,3',
    'after:author_email'
);

// ################
// Page overlay (lang)
// ################

$tempColumns = array(
    'tx_metaseo_pagetitle'        => array(
        'exclude' => 1,
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages_language_overlay.tx_metaseo_pagetitle',
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_pagetitle_rel'    => array(
        'exclude' => 1,
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages_language_overlay.tx_metaseo_pagetitle_rel',
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_pagetitle_prefix' => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_pagetitle_prefix',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_pagetitle_suffix' => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_pagetitle_suffix',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
        )
    ),
    'tx_metaseo_canonicalurl'     => array(
        'label'   => 'LLL:EXT:metaseo/locallang_db.xml:pages.tx_metaseo_canonicalurl',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
            'wizards'  => Array(
                '_PADDING' => 2,
                'link'     => Array(
                    'type'         => 'popup',
                    'title'        => 'Link',
                    'icon'         => 'link_popup.gif',
                    'script'       => 'browse_links.php?mode=wizard&act=url',
                    'params'       => array(
                        'blindLinkOptions' => 'mail',
                    ),
                    'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
                ),
            ),
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages_language_overlay', $tempColumns, 1);

// TCA Palettes
$TCA['pages_language_overlay']['palettes']['tx_metaseo_pagetitle'] = array(
    'showitem'       => 'tx_metaseo_pagetitle,--linebreak--,tx_metaseo_pagetitle_prefix,tx_metaseo_pagetitle_suffix',
    'canNotCollapse' => 1
);

$TCA['pages_language_overlay']['palettes']['tx_metaseo_crawler'] = array(
    'showitem'       => 'tx_metaseo_canonicalurl',
    'canNotCollapse' => 1
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages_language_overlay',
    'tx_metaseo_pagetitle_rel',
    '',
    'after:title'
);

// Put it for standard page overlay
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages_language_overlay',
    '--div--;LLL:EXT:metaseo/locallang_tca.xml:pages.tab.seo;,--palette--;LLL:EXT:metaseo/locallang_tca.xml:pages.palette.pagetitle;tx_metaseo_pagetitle,--palette--;LLL:EXT:metaseo/locallang_tca.xml:pages.palette.crawler;tx_metaseo_crawler',
    '',
    'after:author_email'
);

// ################
// Domains
// ################

/*
$tempColumns = array (
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_domain',$tempColumns,1);
*/

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
