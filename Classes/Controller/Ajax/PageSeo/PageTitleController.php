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

namespace Metaseo\Metaseo\Controller\Ajax\PageSeo;

use Metaseo\Metaseo\Controller\Ajax\AbstractPageSeoSimController;
use Metaseo\Metaseo\DependencyInjection\Utility\HttpUtility;
use Metaseo\Metaseo\Exception\Ajax\AjaxException;
use TYPO3\CMS\Core\Utility\GeneralUtility as Typo3GeneralUtility;

class PageTitleController extends AbstractPageSeoSimController
{
    const LIST_TYPE = 'pagetitle';

    /**
     * @inheritDoc
     */
    protected function initFieldList()
    {
        $this->fieldList = array(
            'tx_metaseo_pagetitle',
            'tx_metaseo_pagetitle_rel',
            'tx_metaseo_pagetitle_prefix',
            'tx_metaseo_pagetitle_suffix',
        );
    }

    /**
     * @inheritDoc
     */
    protected function executeSimulate()
    {
        $pid = (int)$this->postVar['pid'];

        if (empty($pid)) {

            throw new AjaxException(
                'message.error.typo3_page_not_found',
                '[0x4FBF3C08]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $page = $this->getPageSeoDao()->getPageById($pid);

        if (empty($page)) {

            throw new AjaxException(
                'message.error.typo3_page_not_found',
                '[0x4FBF3C09]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        // Load TYPO3 classes
        $this->getFrontendUtility()->initTsfe($page, null, $page, null);

        $pagetitle = Typo3GeneralUtility::makeInstance(
            'Metaseo\\Metaseo\\Page\\Part\\PagetitlePart'
        );

        return array(
            'title' => $pagetitle->main($page['title']),
        );
    }
}
