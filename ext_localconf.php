<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['metaseo']);

// ##############################################
// BACKEND
// ##############################################
if (TYPO3_MODE == 'BE') {
    // AJAX
    $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_metaseo_backend_ajax::sitemap'] = 'Metaseo\\Metaseo\\Backend\\Ajax\SitemapAjax->main';
    $GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_metaseo_backend_ajax::page']    = 'Metaseo\\Metaseo\\Backend\\Ajax\PageAjax->main';

    // Field validations
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_metaseo_backend_validation_float'] = 'EXT:metaseo/Classes/Backend/Validator/ValidatorImport.php';

    // Hooks
    //$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][]  = 'Metaseo\\Metaseo\\Hook\\ClearCacheHook->main';
}

// ##############################################
// SEO
// ##############################################

$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tx_metaseo_pagetitle,tx_metaseo_pagetitle_rel,tx_metaseo_pagetitle_prefix,tx_metaseo_pagetitle_suffix,tx_metaseo_canonicalurl';
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_metaseo_pagetitle_prefix,tx_metaseo_pagetitle_suffix,tx_metaseo_inheritance';

//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc'][] = 'EXT:metaseo/lib/class.linkparser.php:user_metaseo_linkparser->main';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc'][] = 'Metaseo\\Metaseo\\Hook\\SitemapIndexHook->hook_linkParse';

// HTTP Header extension
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['metaseo'] = 'Metaseo\\Metaseo\\Hook\\HttpHook->main';


// ##############################################
// SITEMAP
// ##############################################
// Frontend indexed
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'][] = 'Metaseo\\Metaseo\\Hook\\SitemapIndexHook';

// ##############################################
// HOOKS
// ##############################################

// EXT:tt_news
if (!empty($confArr['enableIntegrationTTNews'])) {
    // Metatag fetch hook
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraItemMarkerHook']['metaseo'] = 'Metaseo\\Metaseo\\Hook\\Extension\\TtnewsExtension';
}

// EXT:news
if (!empty($confArr['enableIntegrationNews'])) {
    // Metatag fetch hook
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['news']['hooks']['listAction']['metaseo'] = 'Metaseo\\Metaseo\\Hook\\Extension\\NewsExtension->listActionHook';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['news']['hooks']['detailAction']['metaseo'] = 'Metaseo\\Metaseo\\Hook\\Extension\\NewsExtension->detailActionHook';
}

// ############################################################################
// CLI
// ############################################################################

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Metaseo\\Metaseo\\Command\\MetaseoCommandController';

// ##############################################
// SCHEDULER
// ##############################################

// Cleanup task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Metaseo\\Metaseo\\Scheduler\\Task\\GarbageCollectionTask'] = array(
    'extension'   => $_EXTKEY,
    'title'       => 'Sitemap garbage collection',
    'description' => 'Cleanup old sitemap entries'
);

// Sitemap XML task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Metaseo\\Metaseo\\Scheduler\\Task\\SitemapXmlTask'] = array(
    'extension'   => $_EXTKEY,
    'title'       => 'Sitemap.xml builder',
    'description' => 'Build sitemap xml as static file (in uploads/tx_metaseo/sitemap-xml/)'
);

// Sitemap TXT task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Metaseo\\Metaseo\\Scheduler\\Task\\SitemapTxtTask'] = array(
    'extension'   => $_EXTKEY,
    'title'       => 'Sitemap.txt builder',
    'description' => 'Build sitemap txt as static file (in uploads/tx_metaseo/sitemap-txt/)'
);
