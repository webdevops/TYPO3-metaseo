# MetaSEO - Changelog

## MetaSEO 2.0

- Added TYPO3 7.x support
- Implemented Signals
- Implemented blacklist of PAGE typeNum in SetupTS
- Implemented blacklist for index/noindex robots metatag
- Implemented blacklist for canonical url
- Implemented canonical url support for mounted pages, pointing to real page instead of mount path (disabled by default)
- Implemented expiry date for sitemap entries (customizable with SetupTS or Connector)
- Implemented pagetitle caching (if there is any USER_INT on the current page)
- Removed own caching solution, using TYPO3 caching framework now
- Fixed many bugs and issues
- Fixed coding style (added .editorconfig)
- Refactored whole extension

## Beta features

- If you have any issues with cached pagetitle: set `plugin.metaseo.pageTitle.caching = 0` to disable this feature.

### Migrate from 1.x to 2.x

- TypoScript Constant `plugin.metaseo.metaTags.useCanonical` changed to `plugin.metaseo.metaTags.canonicalUrl`
- TypoScript Setup    `plugin.metaseo.metaTags.useCanonical` changed to `plugin.metaseo.metaTags.canonicalUrl`
- Names of Hooks changed, now camelCase


## MetaSEO 1.0 (2014-04-20)

- Version 1.0.0
- Fork of metaseo
- Fixed several bugs and improved codebase
- Fixed and improved manual (now reStructuredText)
