# MetaSEO - Changelog

## MetaSEO 2.0

- Fixed TYPO3 7.x support
- Implemented Signals
- Implemented blacklist of PAGE typeNum in SetupTS
- Implemented blacklist for index/noindex robots metatag
- Implemented blacklist for canonical url
- Implemented canonical url support for mounte pages, pointing to real page instead of mount path (disabled by default)
- Implemented expiry date for sitemap entries (customizable with SetupTS or Connector)
- Fixed several bugs
- Fixed coding style (added .editorconfig)
- Refactored whole extension

### Migrate from 1.x to 2.x

- TypoScript Constant `plugin.metaseo.metaTags.useCanonical` changed to `plugin.metaseo.metaTags.canonicalUrl`
- TypoScript Setup    `plugin.metaseo.metaTags.useCanonical` changed to `plugin.metaseo.metaTags.canonicalUrl`
- Names of Hooks changed, now camelCase
