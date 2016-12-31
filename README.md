# MetaSEO - Search Engine Optimization for TYPO3

![stable v2.0.3](https://img.shields.io/badge/stable-v2.0.3-green.svg?style=flat)
![development v2.0.4](https://img.shields.io/badge/development-v2.0.4-red.svg?style=flat)
![License GPL3](https://img.shields.io/badge/license-GPL3-blue.svg?style=flat)


[![Average time to resolve an issue](https://isitmaintained.com/badge/resolution/mblaschke/typo3-metaseo.svg)](https://isitmaintained.com/project/mblaschke/typo3-metaseo "Average time to resolve an issue")
[![Percentage of issues still open](https://isitmaintained.com/badge/open/mblaschke/typo3-metaseo.svg)](https://isitmaintained.com/project/mblaschke/typo3-metaseo "Percentage of issues still open")


[![SensioLabsInsight](https://insight.sensiolabs.com/projects/19914ab4-1f0f-4be0-9215-410fba880af2/big.png)](https://insight.sensiolabs.com/projects/19914ab4-1f0f-4be0-9215-410fba880af2)


MetaSEO is an extension for TYPO3 CMS and provides an indexed google/xml-sitemap, enhanced metatag support
and pagetitle manipulation.
It's a replacement for the "metatag" extension and the successor of the discontinued extension "tq_seo".

* Manual:      https://docs.typo3.org/typo3cms/extensions/metaseo/
* Support:     https://github.com/mblaschke/TYPO3-metaseo/issues
* Source code: https://github.com/mblaschke/TYPO3-metaseo

## Version status

* Version **2.0.3**:

  + Branch **master**
  + TYPO3 Version: 6.2.x - 7.6.x
  + Composer: dev-master

* Version **2.0.4-dev**:

  + Branch **develop**
  + TYPO3 Version: 6.2.x - 7.6.x
  + Composer: dev-develop

For version specific information see [changelog for MetaSEO](CHANGELOG.md)


## Composer Support

The latest stable release of MetaSEO is available via [TYPO3 TER](https://typo3.org/extensions/repository/view/metaseo)
using TYPO3's extension manager or using composer:

    {
      "repositories": [
        { "type": "composer", "url": "https://composer.typo3.org/" }
      ],
      .......
      "require": {
        "php": ">=5.3.0",
        "typo3/cms-core": ">=6.2.0,<8.0",
        "typo3-ter/metaseo": "*"
      },
    }

As long as you are aware that our unstable branch can break at any time, feel free to preview coming releases by using
our unstable branch at Github:

    {
      "repositories": [
        { "type": "composer", "url": "https://composer.typo3.org/" },
        { "type": "vcs", "url": "https://github.com/mblaschke/TYPO3-metaseo.git" },
      ],
      .......
      "require": {
        "php": ">=5.3.0",
        "typo3/cms-core": ">=6.2.0,<8.0",
        "mblaschke/metaseo": "dev-master"
      }
    }

MetaSEO is also available via [packagist](https://packagist.org/packages/mblaschke/metaseo).

## Found a bug? Have questions?

Please feel free to file an issue in our [Bugtracker](https://github.com/mblaschke/TYPO3-metaseo/issues). To avoid feedback loops we suggest to provide

* MetaSEO version
* TYPO3 version
* RealUrl version (if used)
* PHP version
* Web server and version (optional)
* Operating system and version (optional)
* Hoster name (in rare cases)

In case of issues, please update to the latest version of MetaSEO first. We also strongly recommend to use recent
versions of TYPO3 CMS (6.2.28+, 7.6.12+) and RealUrl (2.1.5+)

## Contribute

MetaSEO is driven by the community and we're pleased to add new contributions.
If you want to provide improvements, please

- make sure that an [issue](https://github.com/mblaschke/TYPO3-metaseo/issues) exists so that it is clear what
  your contribution is supposed to do. Eventually, open a new issue.
- add a `Fixes #123` to the message of your first commit, whereas `#123` should be the issue number.
- add yourself to the [list of contributors](https://github.com/mblaschke/TYPO3-metaseo/blob/develop/Documentation/Introduction/Index.rst)
  when you send us your first pull request (PR).
- provide as many commits in your PR as necessary. There's no single-commit policy, but one PR should not affect more
  than one issue (if possible).

The coding style of MetaSEO's source code follows the
[PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
standard. Please enable PSR-2 support in your IDE or enable the editorconfig plugin.
See [.editorconfig](.editorconfig) for indentation.

