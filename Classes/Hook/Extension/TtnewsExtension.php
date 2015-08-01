<?php

/*
 *  Copyright notice
 *
 *  (c) 2015 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
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
 */

namespace Metaseo\Metaseo\Hook\Extension;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Plugin\AbstractPlugin;

/**
 * EXT:tt_news hook for metatags
 */
class TtnewsExtension
{

    /**
     * Extra item marker hook for metatag fetching
     *
     * @param   array          $markerArray Marker array
     * @param   array          $row         Current tt_news row
     * @param   array          $lConf       Local configuration
     * @param   AbstractPlugin $ttnewsObj   Pi-object from tt_news
     *
     * @return  array                Marker array (not changed)
     */
    public function extraItemMarkerProcessor(array $markerArray, array $row, array $lConf, AbstractPlugin $ttnewsObj)
    {
        $theCode = (string)strtoupper(trim($ttnewsObj->theCode));

        $connector = GeneralUtility::makeInstance('Metaseo\\Metaseo\\Connector');

        switch ($theCode) {
            case 'SINGLE':
            case 'SINGLE2':
                // Title
                if (!empty($row['title'])) {
                    $connector->setMetaTag('title', $row['title']);
                }

                // Description
                if (!empty($row['short'])) {
                    $connector->setMetaTag('description', $row['short']);
                }

                // Keywords
                if (!empty($row['keywords'])) {
                    $connector->setMetaTag('keywords', $row['keywords']);
                }

                // Short/Description
                if (!empty($row['short'])) {
                    $connector->setMetaTag('description', $row['short']);
                }

                // Author
                if (!empty($row['author'])) {
                    $connector->setMetaTag('author', $row['author']);
                }

                // E-Mail
                if (!empty($row['author_email'])) {
                    $connector->setMetaTag('email', $row['author_email']);
                }
                break;
        }

        return $markerArray;
    }
}
