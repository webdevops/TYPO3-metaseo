<?php
namespace Metaseo\Metaseo\Page;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
 *  (c) 2013 Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de> (tq_seo)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Sitemap xml page
 *
 * @package     metaseo
 * @subpackage  Page
 * @version     $Id: class.robots_txt.php 62700 2012-05-22 15:53:22Z mblaschke $
 */
class SitemapXmlPage extends \Metaseo\Metaseo\Page\AbstractPage {

    // ########################################################################
    // Attributes
    // ########################################################################


    // ########################################################################
    // Methods
    // ########################################################################

    /**
     * Build sitemap xml
     *
     * @return  string
     */
    public function main() {
        // INIT
        $this->tsSetup = $GLOBALS['TSFE']->tmpl->setup['plugin.']['metaseo.']['sitemap.'];

        // check if sitemap is enabled in root
        if (!\Metaseo\Metaseo\Utility\GeneralUtility::getRootSettingValue('is_sitemap', TRUE)) {
            $this->showError('Sitemap is not available, please check your configuration [control-center]');
        }

        $ret = $this->_build();

        return $ret;
    }

    /**
     * Build sitemap index or specific page
     *
     * @return mixed
     */
    protected function _build() {
        $page = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('page');

        $generator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'Metaseo\\Metaseo\\Sitemap\\Generator\\XmlGenerator'
        );

        if (empty($page) || $page == 'index') {
            $ret = $generator->sitemapIndex();
        } else {
            $ret = $generator->sitemap($page);
        }

        return $ret;
    }

}
