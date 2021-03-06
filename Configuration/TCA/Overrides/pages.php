<?php
defined('TYPO3_MODE') || die();

$tempColumns = array(
    'tx_metaseo_pagetitle'        => array(
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_pagetitle',
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
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_pagetitle_rel',
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
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_pagetitle_prefix',
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
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_pagetitle_suffix',
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
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_inheritance',
        'config'  => array(
            'type'     => 'select',
            'renderType' => 'selectSingle',
            'items'    => array(
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_inheritance.I.0',
                    0
                ),
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_inheritance.I.1',
                    1
                ),
            ),
            'size'     => 1,
            'maxitems' => 1
        )
    ),
    'tx_metaseo_opengraph_image' => array(
        'exclude' => 1,
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_opengraph_image',
        'config'  => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('tx_metaseo_opengraph_image', array(
                'maxitems' => 1
            )
        )
    ),
    'tx_metaseo_is_exclude'       => array(
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_is_exclude',
        'exclude' => 1,
        'config'  => array(
            'type' => 'check'
        )
    ),
    'tx_metaseo_canonicalurl'     => array(
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_canonicalurl',
        'exclude' => 1,
        'config'  => array(
            'type'     => 'input',
            'size'     => '30',
            'max'      => '255',
            'checkbox' => '',
            'eval'     => 'trim',
            'renderType' => 'inputLink',
            'fieldControl' => array(
                'linkPopup' => array(
                    'options' => array(
                        'blindLinkOptions' => 'mail',
                    ),
                ),
            ),
        )
    ),
    'tx_metaseo_priority'         => array(
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_priority',
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
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_change_frequency',
        'config'  => array(
            'type'     => 'select',
            'renderType' => 'selectSingle',
            'items'    => array(
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:'
                    . 'pages.tx_metaseo_change_frequency.I.0',
                    0
                ),
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:'
                    . 'pages.tx_metaseo_change_frequency.I.1',
                    1
                ),
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:'
                    . 'pages.tx_metaseo_change_frequency.I.2',
                    2
                ),
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:'
                    . 'pages.tx_metaseo_change_frequency.I.3',
                    3
                ),
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:'
                    . 'pages.tx_metaseo_change_frequency.I.4',
                    4
                ),
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:'
                    . 'pages.tx_metaseo_change_frequency.I.5',
                    5
                ),
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:'
                    . 'pages.tx_metaseo_change_frequency.I.6',
                    6
                ),
                array(
                    'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:'
                    . 'pages.tx_metaseo_change_frequency.I.7',
                    7
                ),
            ),
            'size'     => 1,
            'maxitems' => 1
        )
    ),
    'tx_metaseo_geo_lat'          => array(
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_geo_lat',
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
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_geo_long',
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
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_geo_place',
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
        'label'   => 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tx_metaseo_geo_region',
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


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);

// TCA Palettes
$GLOBALS['TCA']['pages']['palettes']['tx_metaseo_pagetitle'] = array(
    'showitem'       => 'tx_metaseo_pagetitle,--linebreak--,tx_metaseo_pagetitle_prefix,'
        . 'tx_metaseo_pagetitle_suffix,--linebreak--,tx_metaseo_inheritance',
    'canNotCollapse' => 1
);

$GLOBALS['TCA']['pages']['palettes']['tx_metaseo_opengraph'] = array(
    'showitem'       => 'tx_metaseo_opengraph_image',
    'canNotCollapse' => 1
);

$GLOBALS['TCA']['pages']['palettes']['tx_metaseo_crawler'] = array(
    'showitem'       => 'tx_metaseo_is_exclude,--linebreak--,tx_metaseo_canonicalurl',
    'canNotCollapse' => 1
);

$GLOBALS['TCA']['pages']['palettes']['tx_metaseo_sitemap'] = array(
    'showitem'       => 'tx_metaseo_priority,--linebreak--,tx_metaseo_change_frequency',
    'canNotCollapse' => 1
);

$GLOBALS['TCA']['pages']['palettes']['tx_metaseo_geo'] = array(
    'showitem'       => 'tx_metaseo_geo_lat,--linebreak--,tx_metaseo_geo_long,--linebreak--,'
        . 'tx_metaseo_geo_place,--linebreak--,tx_metaseo_geo_region',
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
    '--div--;LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.tab.seo;,--palette--;'
    . 'LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.palette.pagetitle;tx_metaseo_pagetitle,'
    . '--palette--;LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.palette.opengraph;tx_metaseo_opengraph,'
    . '--palette--;LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.palette.geo;tx_metaseo_geo,'
    . '--palette--;LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:pages.palette.crawler;'
    . 'tx_metaseo_crawler,--palette--;LLL:EXT:metaseo/Resources/Private/Language/TCA/locallang.xlf:'
    . 'pages.palette.sitemap;tx_metaseo_sitemap',
    '1,4,7,3',
    'after:author_email'
);
