.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _faq-manual:

Frequently Asked Questions (FAQ)
================================

General
-------

| **Problem:**
|     Is this extension multi-language and/or multi-tree ready?
| **Solution:**
|     Check it out, there should be no problems at all with multi-language multi-tree installations of TYPO3 CMS.
|
| **Problem:**
|     In my TYPO3 the feature XYZ of this extension doesn't work. What's wrong?
| **Solution:**
|     The metaseo-Extension has been written carefully. We want to deliver correct pages and extensions with the power of TYPO3's caching system (and those extensions are the fastest). For some features (e.g. sitemap, canonical-tag) we have to trust in the caching-system – it's the only way to make sure that we don't deliver wrong information to search engines. If our extension doesn't work correctly, something could also be wrong with the configuration of your TYPO3 – the configuration variables, setupTS or a conflict or a problem with other extensions.

Indexed Sitemap
---------------

| **Problem:**
|    When I want to open my sitemap I get an error "The page is not configured! `[type=841131|841132|841133][]`. This means that there is no TypoScript object of type PAGE with `typeNum=841131|841132|841133` configured."
| **Solution:**
|    Make sure you have configured MetaSEO "Include static" as described in the installation manual.
|
| **Problem:**
|    The sitemap is empty.
| **Solution:**
|     Check if the “tx_metaseo_sitemap” database-table was created! We're using MySQL's InnoDB table engine so you have to make sure that your MySQL server comes with InnoDB-support (if your hoster does not provide InnoDB you should look out for another hoster!).
|
| **Problem:**
|     My sitemap is still empty, but the database is ok.
| **Solution:**
|     Maybe you disabled the whole TYPO3 cache (config.no_cache=1 or something else)? Enable the cache – this will also speed up your TYPO3 instance.
|
| **Problem:**
|     The pages which my extension created are not available in the sitemap!
| **Solution:**
|     You have to make sure that all generated pages are cacheable. The extension must be able to pass a valid cHash-token!
|
| **Problem:**
|     In the sitemap-database are thousands of pages but there is only one site in my XML-Sitemap.
| **Solution:**
|     That's correct. MetaSEO always uses “Sitemap Groups” (as defined in http://www.sitemaps.org/protocol.html#index), each group can contain about 50.000 pages/URLs so if we have more than 50.000 URLs we have to use sitemap groups. Visit the URL defined in the LOC-tag and you will see that the sub-sitemap will contain all your stored URLs.
|
| **Problem:**
|     The generated pages from my extension still are not included in the sitemap!
| **Solution:**
|     Have you enabled RealURL? Please check the RealURL configuration if you have specified values that are not passed with your URL. All variables that are not passed with the URL will result in a NO-CACHE.
|
| **Problem:**
|     The sitemap is still not working! No page is indexed and the table tx_metaseo_sitemap is empty!
| **Solution:**
|     Double check your installation and disable all third party extensions. Make sure that no extension disables the TYPO3-cache! RealUrl (if properly configured) and TemplaVoila are working perfectly together with MetaSEO sitemap but some old extensions might break the TYPO3 caching system and you will not notice it. Our sitemap indexer relies on the indexing system to make sure that only valid urls are stored and delivered to search engines like google.
|
| **Problem:**
|     I want to limit each sitemap to its domain (eg. example.com for english pages, example.de for german pages). Is this possible?
| **Solution:**
|     Yes, just enable the “Enable language-domain support” in seo control center in your website/rootpage settings (replaces the old extension configuration sitemap_ObeySysLanguage and TypoScript constants setting).
|
| **Problem:**
|     My tt_news entries are not indexed, what's wrong?
| **Solution:**
|     You're using realurl? Then check your realurl_conf.php. Errors (or misconfiguration) in the realurl-configuration will produce uncacheable sites (and you will not notice it). The most common issue is the configuration of the parameter “tx_ttnews[swords]” in the postVarSets-area. Remove it, you don't need it.


Others
------

| **Problem:**
|     I want to customize my Google Analytics and/or Piwik integration.
| **Solution:**
|     You can modify the code of the Google Analytics and Piwik integration with TypoScript. Feel free to use plugin.metaseo.services.googleAnalytics.customizationCode (STDWRAP) or plugin.metaseo.services.piwik.customizationCode (STDWRAP). Also you can modify the FLUIDTEMPLATE and assign custom variables.
