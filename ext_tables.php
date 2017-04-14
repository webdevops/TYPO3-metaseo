<?php
defined('TYPO3_MODE') or exit;

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
            'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Backend/Icons/ModuleSeo.svg',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/ModuleSeo/locallang.xlf',
        )
    );

    // ####################################################
    // Module category "SEO"
    // ####################################################

    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\IconRegistry');
    $iconRegistry->registerIcon(
        'module-seo',
        'TYPO3\\CMS\\Core\\Imaging\\IconProvider\\FontawesomeIconProvider',
        array(
            'name' => 'bullseye'
        )
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        $_EXTKEY,
        'metaseo',
        '',
        '',
        array(),
        array(
            'access' => 'user,group',
            'icon' => '',
            'iconIdentifier' => 'module-seo',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/ModuleMain/locallang.xlf',
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
            'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Backend/Icons/ModuleControlCenter.svg',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/ModuleControlCenter/locallang.xlf',
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
            'icon'   => 'EXT:' . $_EXTKEY . '/Resources/Public/Backend/Icons/ModuleSitemap.svg',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/ModuleSitemap/locallang.xlf',
        )
    );
}

