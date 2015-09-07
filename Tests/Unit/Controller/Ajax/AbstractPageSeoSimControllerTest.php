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

namespace Metaseo\Metaseo\Tests\Unit\Controller\Ajax;

use Exception;

abstract class AbstractPageSeoSimControllerTest extends AbstractPageSeoControllerTest
{
    /**
     * @test
     */
    public function testSimulate()
    {
        $this->expectedDaoMethod = 'getPageById';
        $subject = $this->getSubject();
        $subject->simulateAction(array(), $this->getAjaxRequestHandlerMock());
    }

    /**
     * @return \Metaseo\Metaseo\Controller\Ajax\PageSeoSimulateInterface
     *
     * @throws Exception
     */
    protected function getSubject()
    {
        //there's no way to overwrite an abstract function with an abstract function just to specialize type hints.
        //see https://bugs.php.net/bug.php?id=36601
        throw new Exception('You need to overwrite this function!');
    }
}
