.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

Frequently Asked Questions (FAQ)
================================

General
-------

| **Problem:**
|     Is this extension mutli-language and/or multi-tree ready?
| **Solution:**
|     Check it out, there should be no problems at all with multi-language multi-tree TYPO3-installations.
|
| **Problem:**
|     In my TYPO3 the feature XYZ of this extension doesn't work. What's wrong?
| **Solution:**
|     The metaseo-Extension was carefully written. We want to deliver correct pages and extensions will the power of the TYPO3 caching system (and those extensions are the fastest). For some features (eg. sitemap, canonical-tag) we have to trust in the caching-system – it's the only way to make sure that we don't deliver wrong informations to our search engines. If our extension doesn't work correct in your TYPO3 installation maybe there is something wrong – maybe the configuration, your setupTS or one of your extension.

Indexed Sitemap
---------------

| **Problem:**
|    The sitemap is empy.
| **Solution:**
|     Check if you created the “tx_metaseo_sitemap_pages” database-table! We're using InnoDB as MySQL-Engine so you have to make sure that you have InnoDB-Support in your Hosting-Account (if not you should switch your hoster, InnoDB is a Standard-Feature in MySQL). As with 2.0.1 there is an error-message if the table doesn't exist.
|
| **Problem:**
|     My sitemap is still empty, but the database is ok.
| **Solution:**
|     Maybe you disabled the whole TYPO3 cache (config.no_cache=1 or somehthing else)? Enable the cache – this will also speed up your TYPO3 installation.
|
| **Problem:**
|     The generated pages from my extension are not included in the sitemap!
| **Solution:**
|     You have to make sure that all generated pages are cacheable. The extension has to pass a valid cHash-token!
|
| **Problem:**
|     There is only one site in my XML-Sitemap, what's wrong? In the sitemap-database are thousands of pages.
| **Solution:**
|     That's correct. MetaSEO always uses “Sitemap Groups” (as defined in http://www.sitemaps.org/protocol.php#index), each group can contain about 50.000 pages/urls so if we have more than 50.000 urls we have to use sitemap groups. Visit the url defined in the LOC-tag and you will see that the sub-sitemap will contain all your stored URLs.
|
| **Problem:**
|     The generated pages from my extension still are not included in the sitemap!
| **Solution:**
|     You've enabled RealURL? Please check the RealURL configuration if you have specified values that are not passed with your url. All variables that are not passed with the url will result in a NO-CACHE.
|
| **Problem:**
|     The sitemap is still not working! No page is indexed and the table tx_metaseo_sitemap_pages is empty!
| **Solution:**
|     Double check your installation and disable all third-party extensions, make sure that no extension disables the TYPO3-cache! RealUrl (if properly configured) and TemplaVoila are working wonderfull with MetaSEO Sitemap but some old extensions might break the TYPO3 caching system and you will not notice it. Our sitemap indexer relies on the indexing system to make sure that only valid urls are stored and delivered to the search engines like google.
|
| **Problem:**
|     I want to limit each sitemap to it's domain (eg. example.com for english pages, example.de for german pages). Is this possible?
| **Solution:**
|     Yes, just enable the “Enable language-domain support” in seo control center in your website/rootpage settings.(replaces the old extension configuration sitemap_ObeySysLanguage and TypoScript constants setting).
|
| **Problem:**
|     My tt_news entries are not indexed, what's wrong?
| **Solution:**
|     You're using realurl? Then check your realurl_conf.php. Errors (or misconfiguration) in the realurl-configuration will produce uncacheable sites (and you will not notice it). The most common issue is the configuration of the parameter “tx_ttnews[swords]” in the postVarSets-area. Remove it, you don't need it.


Others
------

| **Problem:**
|     I want to customize my google Analytics and/or piwik integration.
| **Solution:**
|     You can modify the code of the google analytics and piwiki integration with typoscript. Feel free to use plugin.metaseo.services.googleAnalytics.customizationCode (STDWRAP) or plugin.metaseo.services.piwik.customizationCode (STDWRAP). Also you can modify the FLUIDTEMPLATE and assign custom variables.
