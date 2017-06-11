.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _changelog:

Changelog
=========


+-------------+----------------------------------------------------------------------------------------------------+
| Version     | Changes                                                                                            |
+=============+====================================================================================================+
| **3.0.0**   | **Major release** (2017-06-11)                                                                     |
|             |                                                                                                    |
|             | - Added TYPO3 CMS 8.7 LTS support                                                                  |
|             | - TCA usage is now on par with TYPO3 CMS 8.7                                                       |
|             | - Removed support for TYPO3 CMS 6.2 LTS and 7.6 LTS                                                |
|             | - Fully migrated to PSR-7 and AJAX UriBuilder routes                                               |
|             | - Contains the new features which were backported into 2.1.0                                       |
|             | - Added SVG icons                                                                                  |
|             | - Streamlined layout                                                                               |
|             | - Deprecation log is now supposed to stay empty (except for Doctrine DBAL messages)                |
|             | - metaseo 3.0.0 runs with and without the compatibility7 extension (uninstall it if possible)      |
|             |                                                                                                    |
|             | **Migration to 3.0.0:**                                                                            |
|             |                                                                                                    |
|             | - Please upgrade to metaseo 2.1.0 in 7.6 LTS before upgrading to 8.7 LTS                           |
|             | - Clear caches and compare/update the schema via install tool                                      |
|             | - For details in respect to the schema selection (http/https) feature removal, please visit the    |
|             |   `Manual <https://docs.typo3.org/typo3cms/extensions/metaseo/stable/AdministratorManual/>`_       |
|             |                                                                                                    |
|             | `Milestone 3.0.0 <https://github.com/mblaschke/TYPO3-metaseo/milestone/6?closed=1>`_               |
|             | `Changes in 3.0.0 <https://github.com/mblaschke/TYPO3-metaseo/compare/2.1.0...3.0.0>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **2.1.0**   | **New feature and Bugfix release** (2017-06-11)                                                    |
|             |                                                                                                    |
|             | - Added support for OpenGraph images                                                               |
|             | - Display custom page doktypes in SEO/Metatags section                                             |
|             |                                                                                                    |
|             | **Migration to 2.1.0:**                                                                            |
|             |                                                                                                    |
|             | - Compare and update the database schema in the install tool.                                      |
|             | - Page title prefix/suffix inheritance has changed. Please check your titles.                      |
|             |                                                                                                    |
|             | `Milestone 2.1.0 <https://github.com/mblaschke/TYPO3-metaseo/milestone/5?closed=1>`_               |
|             | `Changes in 2.1.0 <https://github.com/mblaschke/TYPO3-metaseo/compare/2.0.4...2.1.0>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **2.0.4**   | **New feature and Bugfix release** (2017-04-15)                                                    |
|             |                                                                                                    |
|             | - Sitemap: Tags `lastmod` and `priority` now can be disabled via configuration                     |
|             |                                                                                                    |
|             | `Milestone 2.0.4 <https://github.com/mblaschke/TYPO3-metaseo/milestone/17?closed=1>`_              |
|             | `Changes in 2.0.4 <https://github.com/mblaschke/TYPO3-metaseo/compare/2.0.3...2.0.4>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **2.0.3**   | **New feature and Bugfix release** (2016-12-31)                                                    |
|             |                                                                                                    |
|             | - Number of entries shown in sitemap now can be changed via extension manager                      |
|             |                                                                                                    |
|             | `Milestone 2.0.3 <https://github.com/mblaschke/TYPO3-metaseo/milestone/16?closed=1>`_              |
|             | `Changes in 2.0.3 <https://github.com/mblaschke/TYPO3-metaseo/compare/2.0.2...2.0.3>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **2.0.2**   | **Bugfix release** (2016-12-30)                                                                    |
|             |                                                                                                    |
|             | **Migration to 2.0.2:**                                                                            |
|             |                                                                                                    |
|             | - Non-admin backend users can now to edit page properties, if permission is given by TYPO3         |
|             | - Link generation (start/prev/next) now uses first element of group for start                      |
|             |                                                                                                    |
|             | `Milestone 2.0.2 <https://github.com/mblaschke/TYPO3-metaseo/milestone/8?closed=1>`_               |
|             | `Changes in 2.0.2 <https://github.com/mblaschke/TYPO3-metaseo/compare/2.0.1...2.0.2>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **2.0.1**   | **Bugfix release** (2016-11-06)                                                                    |
|             |                                                                                                    |
|             | `Milestone 2.0.1 <https://github.com/mblaschke/TYPO3-metaseo/milestone/7?closed=1>`_               |
|             | `Changes in 2.0.1 <https://github.com/mblaschke/TYPO3-metaseo/compare/2.0.0...2.0.1>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **2.0.0**   | **Major release** (2016-03-07)                                                                     |
|             |                                                                                                    |
|             | - Added TYPO3 CMS 7.6 support                                                                      |
|             |   (still backwards compatible to TYPO3 CMS 6.2 and PHP 5.3)                                        |
|             | - Refactoring of Ajax Requests: PSR-7, Exception Handling, OOP                                     |
|             | - Changed coding style: Now uses PSR-2, added `.editorconfig` file                                 |
|             | - Refactored large portions of the codebase                                                        |
|             | - Implemented signals                                                                              |
|             | - Implemented blacklist of PAGE typeNum in SetupTS                                                 |
|             | - Implemented blacklist for index/noindex robots metatag                                           |
|             | - Implemented blacklist for canonical url                                                          |
|             | - Implemented canonical url support for mounted pages,                                             |
|             |   pointing to real page instead of mount path (disabled by default)                                |
|             | - Implemented expiry date for sitemap entries (customizable with SetupTS or Connector)             |
|             | - Implemented pagetitle caching (if there is any `USER_INT` on the current page)                   |
|             | - Removed own caching solution, using TYPO3 caching framework now                                  |
|             | - Added fallback for schema selection for canonical Urls in case protocol is undefined             |
|             |   in page properties (via `plugin.metaseo.metaTags.canonicalUrl.fallbackProtocol`)                 |
|             | - Bugfixes and improvements                                                                        |
|             |                                                                                                    |
|             | **Migration from 1.0.x to 2.0.0:**                                                                 |
|             |                                                                                                    |
|             | - Link generation (`start/prev/next`) is now disabled by default                                   |
|             | - TypoScript Constant `plugin.metaseo.metaTags.useCanonical`                                       |
|             |   changed to `plugin.metaseo.metaTags.canonicalUrl`                                                |
|             | - TypoScript Setup `plugin.metaseo.metaTags.useCanonical`                                          |
|             |   changed to `plugin.metaseo.metaTags.canonicalUrl`                                                |
|             | - Changed names of hooks, use camelCase now                                                        |
|             |                                                                                                    |
|             | `Milestone 2.0.0 <https://github.com/mblaschke/TYPO3-metaseo/milestone/2?closed=1>`_               |
|             | `Changes in 2.0.0 <https://github.com/mblaschke/TYPO3-metaseo/compare/1.0.8...2.0.0>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **1.0.8**   | **Bugfix release** (2014-04-25)                                                                    |
|             |                                                                                                    |
|             | `Milestone 1.0.8 <https://github.com/mblaschke/TYPO3-metaseo/milestone/4?closed=1>`_               |
|             | `Changes in 1.0.8 <https://github.com/mblaschke/TYPO3-metaseo/compare/1.0.7...1.0.8>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **1.0.7**   | **Bugfix release** (2015-04-18)                                                                    |
|             |                                                                                                    |
|             | `Milestone 1.0.7 <https://github.com/mblaschke/TYPO3-metaseo/milestone/3?closed=1>`_               |
|             | `Changes in 1.0.7 <https://github.com/mblaschke/TYPO3-metaseo/compare/1.0.6...1.0.7>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **1.0.6**   | **Bugfix release** (2015-02-26)                                                                    |
|             |                                                                                                    |
|             | `Milestone 1.0.6 <https://github.com/mblaschke/TYPO3-metaseo/milestone/14?closed=1>`_              |
|             | `Changes in 1.0.6 <https://github.com/mblaschke/TYPO3-metaseo/compare/1.0.5...1.0.6>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **1.0.5**   | **Bugfix release** (2014-10-24)                                                                    |
|             |                                                                                                    |
|             | `Milestone 1.0.5 <https://github.com/mblaschke/TYPO3-metaseo/milestone/1?closed=1>`_               |
|             | `Changes in 1.0.5 <https://github.com/mblaschke/TYPO3-metaseo/compare/1.0.4...1.0.5>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **1.0.4**   | **Bugfix release** (2014-09-15)                                                                    |
|             |                                                                                                    |
|             | `Milestone 1.0.4 <https://github.com/mblaschke/TYPO3-metaseo/milestone/13?closed=1>`_              |
|             | `Changes in 1.0.4 <https://github.com/mblaschke/TYPO3-metaseo/compare/1.0.3...1.0.4>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **1.0.3**   | **Bugfix release** (2014-09-10)                                                                    |
|             |                                                                                                    |
|             | `Milestone 1.0.3 <https://github.com/mblaschke/TYPO3-metaseo/milestone/12?closed=1>`_              |
|             | `Changes in 1.0.3 <https://github.com/mblaschke/TYPO3-metaseo/compare/1.0.2...1.0.3>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **1.0.2**   | **Bugfix release** (2014-09-04)                                                                    |
|             |                                                                                                    |
|             | `Milestone 1.0.2 <https://github.com/mblaschke/TYPO3-metaseo/milestone/11?closed=1>`_              |
|             | `Changes in 1.0.2 <https://github.com/mblaschke/TYPO3-metaseo/compare/1.0.1...1.0.2>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **1.0.1**   | **Bugfix release** (2014-09-03)                                                                    |
|             |                                                                                                    |
|             | `Milestone 1.0.1 <https://github.com/mblaschke/TYPO3-metaseo/milestone/10?closed=1>`_              |
|             | `Changes in 1.0.1 <https://github.com/mblaschke/TYPO3-metaseo/compare/1.0.0...1.0.1>`_             |
+-------------+----------------------------------------------------------------------------------------------------+
| **1.0.0**   | **Major release** (2014-08-31)                                                                     |
|             |                                                                                                    |
|             | - Forked from predecessor "tq_seo"                                                                 |
|             | - Major improvements of features and codebase                                                      |
|             | - Fixed several major and minor bugs                                                               |
|             | - Fixed and improved documentation (now reStructuredText)                                          |
|             | - Fixed sitemap url generation in TYPO3 scheduler                                                  |
|             |                                                                                                    |
|             | `Milestone 1.0.0 <https://github.com/mblaschke/TYPO3-metaseo/milestone/9?closed=1>`_               |
+-------------+----------------------------------------------------------------------------------------------------+


Latest information also is available in the list of
`milestones <https://github.com/mblaschke/TYPO3-metaseo/milestones>`_ and tagged
`releases <https://github.com/mblaschke/TYPO3-metaseo/releases>`_.
