.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

Administrator Manual
====================

Installation
------------

- Install Extension via Extension Manager.
- Include “static extension template” (Template → Info/Modify → Edit the whole template record → “Include static (from extensions):” and select “MetaSEO”)
- *Optional:* If you want to import your settings from the predecessor "tq_seo" install the "metaseo_tqseo_import" extension and run the importer.
- Modify your metatags via constants editor

Indexed Sitemap
---------------

The sitemap will automatically collect all cacheable sites and provides a xml- and plaintext-output – that's why it is “indexed”.

The XML-Sitemap (eg. for Google) is available with: index.php?type=841132
The TXT-Sitemap is available with: index.php?type=841131

If you have more than one tree in your TYPO3 you will have to add the root-PID to your Sitemap, eg:

- Tree #1 with PID 123: index.php?id=123&type=841132
- Tree #2 with PID 234: index.php?id=234&type=841132

If you have also enabled “sitemap_ObeySysLanguage” in the extension configuration  you also have to add the language-id for your Sitemap - eg. for seperated language-domain eg. example.com (only english sites) and example.de (only german sites).

The sitemap will index ALL cacheable pages with full extension support (like tt_news and all other “clean” extensions).
If your extension doesn't use cHash or use no_cache the outwill WILL NOT included in the sitemap (and also will not be indexed by index_search).

Also the sitemap indexes all generated “typolink” (BETA).

**Warning:**
The TQ Seo Sitemap relies on the TYPO3 caching system. If any extension (or configuration – eg. RealURL configuration) break the caching system and makes TSFE non-cacheable (TSFE->no_cache) the sites will NOT INDEXED!
Make sure no extension will set no_cache and the cHash of your link is valid. This is the only way to get only valid URLs into your sitemap.

This sitemap supports both, pibase- and extbase-Extensions without problems. However the developer must take care of the cHash-handling.

Robots.txt
----------
The robots.txt can be gerated with type 841133, eg.:
index.php?type=841133

If possible and enabled the robots.txt buidler will automatically add the link to the sitemap generator or the static sitemap files (will require TYPO3 Scheduler task to generate the static sitemap).

Scheduler Tasks
---------------

=============================================   ===============================================================   ======================
Scheduler Task                                  Description                                                       Frequency
=============================================   ===============================================================   ======================
MetaSEO Cleanup                                 This task cleans up old database entries in the                   One run per day
                                                tx_metaseo_sitemap table.

MetaSEO sitemap.txt builder                     This task builds a real sitemap.txt file in the                   One run per day
                                                upload directory.

                                                - Directory: uploads/tx_metaseo/sitemap_txt/
                                                - Sitemap: sitemap-r{ROOTPID}.txt.gz

                                                If language domain support is active:

                                                - Sitemap: sitemap-r{ROOTPID}-l{LANG}.txt.gz

                                                {ROOTPID} is the Page-UID from the root pages in
                                                your TYPO3 installations.

                                                {LANG} is the language id (only active if language
                                                domains are active).

                                                Hint: These files are already gziped.

MetaSEO sitemap.xml builder                     This task builds a real sitemap.xml files in the                  One run per day
                                                upload directory.

                                                - Directory: uploads/tx_metaseo/sitemap_xml/
                                                - Sitemap-Index: index-r{ROOTPID}.xml.gz
                                                - Sitemap-Page: sitemap-r{ROOTPID}-p{PAGE}.xml.gz

                                                If language domain support is active:

                                                - Sitemap-Index: index-r{ROOTPID}-l{LANG}.xml.gz
                                                - Sitemap-Page: sitemap-r{ROOTPID}-l{LANG}-p{PAGE}.xml.gz

                                                {ROOTPID} is the Page-UID from the root pages in your
                                                TYPO3 installations.

                                                {PAGE} is the current page of the sitemap.

                                                {LANG} is the language id (only active if language
                                                domains are active).

                                                The index will refer to all page sitemaps so you only
                                                have to reference to the sitemap index.

                                                Hint: These files are already gziped.
=============================================   ===============================================================   ======================


RealURL Configuration
---------------------

f you want to activiate “real” sitemap.xml feature (eg. http://example.com/sitemap.xml), configure realurl like this:

::

        <?php
        $TYPO3_CONF_VARS['EXTCONF']['realurl']['_DEFAULT'] = array(

        'init' => array(
            // ...
        ),


        'preVars' => array(
            // ...
        ),

        'fixedPostVars' => array(
            // ...
        ),

        'postVarSets' => array(
            '_DEFAULT' => array(

                // TT-NEWS (example configuration)
                'date' => array(
                    array(
                        'GETvar' => 'tx_ttnews[year]' ,
                    ),
                    array(
                        'GETvar' => 'tx_ttnews[month]' ,
                        'valueMap' => array(
                            'january' => '01',
                            'february' => '02',
                            'march' => '03',
                            'april' => '04',
                            'may' => '05',
                            'june' => '06',
                            'july' => '07',
                            'august' => '08',
                            'september' => '09',
                            'october' => '10',
                            'november' => '11',
                            'december' => '12',
                        ),
                    ),
                    array(
                        'GETvar' => 'tx_ttnews[day]',
                    ),
                ),

                // news pagebrowser
                'browse' => array(
                    array(
                        'GETvar' => 'tx_ttnews[pointer]',
                    ),
                ),

                // news categories
                'news-category' => array (
                    array(
                        'GETvar' => 'tx_ttnews[cat]',
                        'lookUpTable' => array(
                            'table' => 'tt_news_cat',
                            'id_field' => 'uid',
                            'alias_field' => 'title',
                            'addWhereClause' => ' AND NOT deleted',
                            'useUniqueCache' => 1,
                            'useUniqueCache_conf' => array(
                                'strtolower' => 1,
                                'spaceCharacter' => '-',
                            ),
                        ),
                    ),
                ),

                // news articles
                'article' => array(
                    array(
                        'GETvar' => 'tx_ttnews[tt_news]',
                        'lookUpTable' => array(
                            'table' => 'tt_news',
                            'id_field' => 'uid',
                            'alias_field' => 'title',
                            'addWhereClause' => ' AND NOT deleted',
                            'useUniqueCache' => 1,
                            'useUniqueCache_conf' => array(
                                'strtolower' => 1,
                                'spaceCharacter' => '-',
                            ),
                        ),
                    ),
                ),

                // ... other extensions ...
            ),
        ),

        'fileName' => array(
            'defaultToHTMLsuffixOnPrev' => 1,
            'index' => array(
                // ...

                'sitemap.xml' => array(
                    'keyValues' => array(
                        'type' => 841132,
                    ),
                ),

                'sitemap.txt' => array(
                    'keyValues' => array(
                        'type' => 841131,
                    ),
                ),

                'robots.txt' => array(
                    'keyValues' => array(
                        'type' => 841133,
                    ),
                ),

                '_DEFAULT' => array(
                    'keyValues' => array(
                        'type' => 0,
                    )
                ),

            ),
        ),

        'pagePath' => array(
            'type' => 'user',
            'userFunc' => 'EXT:realurl/class.tx_realurl_advanced.php:&tx_realurl_advanced->main',
            'spaceCharacter'	=> '-',
            'segTitleFieldList'	=> 'tx_realurl_pathsegment,alias,nav_title,title',
            'languageGetVar'	=> 'L',
            'expireDays'	=> 30,
            'rootpage_id'	=> 1,
        ),

        );
