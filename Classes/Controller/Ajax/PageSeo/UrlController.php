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
use Metaseo\Metaseo\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class UrlController extends AbstractPageSeoSimController
{
    const LIST_TYPE = 'url';

    /**
     * @inheritDoc
     */
    protected function initFieldList()
    {
        $fieldList = array(
            'title',
            'url_scheme',
            'alias',
        );
        if (ExtensionManagementUtility::isLoaded('realurl')) {
            $fieldList[] = 'tx_realurl_pathsegment';
            $fieldList[] = 'tx_realurl_pathoverride';
            $fieldList[] = 'tx_realurl_exclude';
        }
        $this->fieldList = $fieldList;
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
                '[0x4FBF3C0A]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        $page = $this->getPageSeoDao()->getPageById($pid);

        if (empty($page)) {

            throw new AjaxException(
                'message.error.typo3_page_not_found',
                '[0x4FBF3C0B]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        if (ExtensionManagementUtility::isLoaded('realurl')) {
            // Disable caching for url
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['enableUrlDecodeCache'] = 0;
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['enableUrlEncodeCache'] = 0;
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT']['disablePathCache']     = 1;
        }

        $this->getFrontendUtility()->initTsfe($page, null, $page, null);

        $ret = $this->getFrontendUtility()->getTypoLinkUrl(array('parameter' => $page['uid']));

        if (!empty($ret)) {
            $ret = GeneralUtility::fullUrl($ret);
        }

        if (empty($ret)) {

            throw new AjaxException(
                'message.error.url_generation_failed',
                '[0x4FBF3C01]',
                HttpUtility::HTTP_STATUS_BAD_REQUEST
            );
        }

        return array(
            'url' => $ret,
        );
    }
}
