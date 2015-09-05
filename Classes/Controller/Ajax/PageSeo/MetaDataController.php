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

use Metaseo\Metaseo\Controller\Ajax\AbstractPageSeoController;
use Metaseo\Metaseo\Controller\Ajax\PageSeoInterface;

class MetaDataController extends AbstractPageSeoController implements PageSeoInterface
{
    const LIST_TYPE = 'metadata';

    protected function initFieldList()
    {
        $this->fieldList = array(
            'keywords',
            'description',
            'abstract',
            'author',
            'author_email',
            'lastupdated',
        );
    }

    /**
     * @inheritDoc
     */
    protected function getIndex(array $page, $depth, $sysLanguage)
    {
        $list = $this->pageSeoDao->index($page, $depth, $sysLanguage, $this->fieldList);

        unset($row);
        foreach ($list as &$row) {
            if (!empty($row['lastupdated'])) {
                $row['lastupdated'] = date('Y-m-d', $row['lastupdated']);
            } else {
                $row['lastupdated'] = '';
            }
        }
        unset($row);

        return $list;
    }
}
