MetaSEO - Search Engine Optimization for TYPO3
==============================================

This extension provides an indexed google/xml-sitemap, enhanced metatag-support and pagetitel-manipulations for TYPO3.
It's an replacement of the "metatag"-extension and the successor of "tq_seo".

* Manual:     http://docs.typo3.org/typo3cms/extensions/metaseo/
* Git:        https://github.com/mblaschke/TYPO3-metaseo
* Support:    http://forge.typo3.org/projects/extension-metaseo/
* Bugtracker: http://forge.typo3.org/projects/extension-metaseo/issues


Composer Support
----------------

MetaSEO (stable) is available in TYPO3 TER and also available with composer:

    {
        "repositories": [
            { "type": "composer", "url": "http://composer.typo3.org/" }
        ],
        .......
        "require": {
            "typo3/cms": "6.2.*",
            "typo3-ter/metaseo": "*"
        }
    }

Or (unstable, don't blame me for bugs - but feel free to report bugs) directly from Github:

    {
        "repositories": [
            { "type": "composer", "url": "http://composer.typo3.org/" },
            { "type": "vcs", "url": "https://github.com/mblaschke/TYPO3-metaseo.git" },
        ],
        .......
        "require": {
            "typo3/cms": "6.2.*",
            "mblaschke/metaseo": "dev-master"
        }
    }

