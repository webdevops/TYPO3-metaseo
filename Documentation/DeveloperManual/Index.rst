.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer-manual:

Developer Manual
================

TypoScript Setup
----------------

Advanced manipulations (stdWrap support)
----------------------------------------

If you want to modify some things you can use stdWraps

MetaTags
^^^^^^^^

=============================================   ==========================================================   ======================
TypoScript Node                                 Description                                                  Type
=============================================   ==========================================================   ======================
plugin.metaseo.metaTags.stdWrap.title           Manipulation for title                                       *stdWrap*

plugin.metaseo.metaTags.stdWrap.description     Manipulation for description                                 *stdWrap*

plugin.metaseo.metaTags.stdWrap.keywords        Manipulation for keywords                                    *stdWrap*

plugin.metaseo.metaTags.stdWrap.copyright       Manipulation for copyright                                   *stdWrap*

plugin.metaseo.metaTags.stdWrap.language        Manipulation for language                                    *stdWrap*

plugin.metaseo.metaTags.stdWrap.email           Manipulation for email                                       *stdWrap*

plugin.metaseo.metaTags.stdWrap.author          Manipulation for author                                      *stdWrap*

plugin.metaseo.metaTags.stdWrap.publisher       Manipulation for publisher                                   *stdWrap*

plugin.metaseo.metaTags.stdWrap.distribution    Manipulation for distribution                                *stdWrap*

plugin.metaseo.metaTags.stdWrap.rating          Manipulation for rating                                      *stdWrap*

plugin.metaseo.metaTags.stdWrap.lastUpdate      Manipulation for last update (date)                          *stdWrap*
=============================================   ==========================================================   ======================

PageTitle
^^^^^^^^^

=============================================   ==========================================================   ======================
TypoScript Node                                 Description                                                  Type
=============================================   ==========================================================   ======================
plugin.metaseo.pageTitle.caching                Enables caching of page title.                               *boolean*
                                                If the page is fully cacheable you don't have to enable
                                                this option.
                                                If you use USER_INTs and one USER plugin changes page
                                                title (eg. via Connector) than this is not working
                                                because the output of the USER plugin is cached and
                                                the Connector not called again with the second hit.
                                                The caching fixes that issue.

plugin.metaseo.pageTitle.stdWrap.before         Manipulation of the raw page title                           *stdWrap*
                                                (before MetaSEO processing)

plugin.metaseo.pageTitle.stdWrap.after          Manipulation of the processed page title                     *stdWrap*
                                                (after MetaSEO processing)

plugin.metaseo.pageTitle.stdWrap.sitetitle      Manipulation of the sitetitle                                *stdWrap*
                                                (from the TS-Setup-Template)
=============================================   ==========================================================   ======================


Google Analyitics / Piwik customizations
----------------------------------------

Customization codes (fast/simple)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You can add additional javascript code to the default Google Analytics and/or Piwik integration.

==========================================================   ==========================================================   ======================
TypoScript Node                                              Description                                                  Type
==========================================================   ==========================================================   ======================
plugin.metaseo.services.googleAnalytics.customizationCode    Customization code for Google Analytics                      TS-Content Object
                                                                                                                          (*TEXT*, *COA*, ...)

plugin.metaseo.services.piwik.customizationCode              Customization code for Piwik                                 TS-Content Object
                                                                                                                          (*TEXT*, *COA*, ...)
==========================================================   ==========================================================   ======================

Example for Google Analytics in TypoScript-Setup:

::

    plugin.metaseo.services.googleAnalytics.customizationCode = COA
    plugin.metaseo.services.googleAnalytics.customizationCode {
        10 = TEXT
        10.value (
            _gaq.push(['_setClientInfo', false]);
            _gaq.push(['_setAllowHash', false]);
            _gaq.push(['_setDetectFlash', false]);
            _gaq.push(['_setDetectTitle', false]);
        )
    }


Template customization (advanced)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The Google Analytics and Piwik integration is done by using a FLUIDTEMPLATE object in TypoScript. If you don't like the integration and want to modify the integration feel free to use your own templates and pass your own variables to FLUIDTEMPLATE.

==========================================================   ==========================================================   ======================
TypoScript Node                                              Description                                                  Type
==========================================================   ==========================================================   ======================
plugin.metaseo.services.googleAnalytics.template             Template rendering object for Google Analytics               *COA*
                                                             (contains a FLUIDTEMPLATE)

plugin.metaseo.services.piwik.template                       Template rendering object for Piwik                          *COA*
                                                             (contains a FLUIDTEMPLATE)
==========================================================   ==========================================================   ======================

It's quite easy, for more information read:

- https://docs.typo3.org/typo3cms/TyposcriptReference/ContentObjects/Fluidtemplate/Index.html
- https://typo3.org/documentation/article/the-fluidtemplate-cobject

Example for your own Google Analytics Template:

::

    ## Google Analytics template
    plugin.metaseo.services.googleAnalytics.template.10.file = fileadmin/templates/service-ga.html

    ## if you need some variables you also can set these:
    plugin.metaseo.services.googleAnalytics.template.10.variables {
      myOwnStuff = TEXT
      myOwnStuff.value = foobar
    }


Sitemap
-------
==========================================================   ==========================================================   ======================
TypoScript Node                                              Description                                                  Type
==========================================================   ==========================================================   ======================
plugin.metaseo.sitemap.index.blacklist                       List of Regular Expression for blacklisting URLs while       List
                                                             indexing, eg.

                                                             10 = /typo3/
                                                             20 = /foobar/
                                                             30 = /imprint/i
                                                             40 = /[a-z]/

plugin.metaseo.sitemap.index.pageTypeBlacklist               List of blacklisted page typeNums (SetupTS PAGE objects)     String, comma separated

plugin.metaseo.sitemap.index.fileExtension                   List of allowed file extensions for indexing                 List

                                                             Default:
                                                             1 = pdf
                                                             2 = doc,docx,xls,xlsx,ppt,pptx
                                                             3 = odt,odp,ods,odg

==========================================================   ==========================================================   ======================


Hooks
-----

All hooks are also available as Extbase Signal. Please use signals instead of hooks.

::

    <?php
    // ----------------------------------------------------------------------------
    // Example of MetaSEO Hooks
    //
    // Example integrations (eg. in localconf.php or ext_localconf.php):
    //
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['metatagSetup'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_metatagSetup';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['metatagOutput'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_metatagOutput';
    //
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['pageTitleSetup'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_pagetitleSetup';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['pageTitleOutput'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_pagetitleOutput';
    //
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['pageFooterSetup'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_pagefooterSetup';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['pageFooterOutput'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_pagefooterOutput';
    //
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['sitemapIndexPage'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_sitemapIndexPage';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['sitemapIndexLink'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_sitemapIndexLink';
    //
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['sitemapSetup'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_sitemapSetup';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['sitemapTextOutput'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_sitemapTextOutput';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['sitemapXmlIndexSitemapList'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_sitemapXmlIndexSitemapList';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['sitemapXmlIndexOutput'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_sitemapXmlIndexOutput';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['sitemapXmlPageOutput'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_sitemapXmlPageOutput';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['sitemapClear'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_sitemapClear';
    //
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['robotsTxtMarker'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_robotsTxtMarker';
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['robotsTxtOutput'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_robotsTxtOutput';
    //
    // $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['metaseo']['hooks']['httpHeaderOutput'][] = 'EXT:metaseo/examples/hooks.php:user_metaseo_hook->hook_httpHeaderOutput';
    // ----------------------------------------------------------------------------

    class user_metaseo_hook {

        public function hook_metatagSetup(&$args, $obj) {
            // Hook for metatag setup
        }

        public function hook_metatagOutput(&$args, $obj) {
            // Hook for metatag output
        }

        // ------------------------------------------------------------------------

        public function hook_pagetitleSetup(&$args, $obj) {
            // Hook for pagetitle setup
        }

        public function hook_pagetitleOutput(&$args, $obj) {
            // Hook for pagetitle output
        }

        // ------------------------------------------------------------------------

        public function hook_pagefooterSetup(&$args, $obj) {
            // Hook for page footer setup
        }

        public function hook_pagefooterOutput(&$args, $obj) {
            // Hook for page footer output
        }

        // ------------------------------------------------------------------------

        public function hook_sitemapIndexPage(&$args) {
            // Hook for sitemap page indexer
        }

        public function hook_sitemapIndexLink(&$args) {
            // Hook for sitemap link indexer
        }

        // ------------------------------------------------------------------------

        public function hook_sitemapSetup(&$args, $obj) {
            // Hook for sitemap setup
        }

        public function hook_sitemapTextOutput(&$args, $obj) {
            // Hook for xml text output
        }

        public function hook_sitemapXmlIndexOutput(&$args, $obj) {
            // Hook for xml index-page output
        }

        public function hook_sitemapXmlIndexSitemapList(&$args, $obj) {
            // Hook for manipulation sitemap.xml index page sitemap list
        }

        public function hook_sitemapXmlPageOutput(&$args, $obj) {
            // Hook for xml page output
        }

        public function hook_sitemapClear(&$args, $obj) {
            // Hook for sitemap clearing (truncating via clear-cache hook)
        }

        // ------------------------------------------------------------------------

        public function hook_robotsTxtMarker(&$args, $obj) {
            // Hook for robots.txt marker list
        }

        public function hook_robotsTxtOutput(&$args, $obj) {
            // Hook for robots.txt output
        }

        // ------------------------------------------------------------------------

        public function hook_httpHeaderOutput($args, $obj) {
            // Hook for http header output
        }

        // ------------------------------------------------------------------------
    }

