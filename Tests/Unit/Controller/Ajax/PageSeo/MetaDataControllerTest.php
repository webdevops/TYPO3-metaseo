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

namespace Metaseo\Metaseo\Tests\Unit\Controller\Ajax\PageSeo;

use Metaseo\Metaseo\Controller\Ajax\PageSeo\MetaDataController;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class MetaDataControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testIndex()
    {
        $this->setGlobals();
        $this->loginBackendUser();
        $ajaxRequestHandler = $this->getAjaxRequestHandlerMock();
        $ajaxRequestHandler
            ->expects($this->exactly(1))
            ->method('setContentFormat');
        $ajaxRequestHandler
            ->expects($this->exactly(1))
            ->method('setContent');

        $subject = new MetaDataController();
        $subject
            ->setObjectManager($this->getObjectManagerMock());
        $subject
            ->indexAction(array(), $ajaxRequestHandler);
    }

    /**
     * @test
     */
    public function testUpdate()
    {
        $this->setGlobals();
        $this->loginBackendUser();
        $ajaxRequestHandler = $this->getAjaxRequestHandlerMock();
        $ajaxRequestHandler
            ->expects($this->exactly(1))
            ->method('setContentFormat');
        $ajaxRequestHandler
            ->expects($this->exactly(1))
            ->method('setContent');

        $subject = new MetaDataController();
        $subject
            ->setObjectManager($this->getObjectManagerMock());
        $subject
            ->updateAction(array(), $ajaxRequestHandler);
    }

    /**
     * @test
     */
    public function testUpdateRecursive()
    {
        $this->setGlobals();
        $this->loginBackendUser();
        $ajaxRequestHandler = $this->getAjaxRequestHandlerMock();
        $ajaxRequestHandler
            ->expects($this->exactly(1))
            ->method('setContentFormat');
        $ajaxRequestHandler
            ->expects($this->exactly(1))
            ->method('setContent');

        $subject = new MetaDataController();
        $subject
            ->setObjectManager($this->getObjectManagerMock());
        $subject
            ->updateRecursiveAction(array(), $ajaxRequestHandler);
    }

    protected function getAjaxRequestHandlerMock()
    {
        return $this
            ->getMockBuilder('TYPO3\CMS\Core\Http\AjaxRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManagerMock()
    {
        return $this
            ->getMockBuilder('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
            ->getMock();
    }

    /**
     * @return \TYPO3\CMS\Core\FormProtection\BackendFormProtection
     */
    protected function getFormProtectionMock()
    {
        return $this
            ->getMockBuilder('TYPO3\\CMS\\Core\\FormProtection\\BackendFormProtection')
            ->getMock();
    }

    protected function loginBackendUser()
    {
        $GLOBALS['BE_USER'] = $this->getMock(
            'TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication',
            array(),
            array(),
            '',
            false
        );
        $GLOBALS['BE_USER']->user = array('uid' => $this->getUniqueId());
        $GLOBALS['TYPO3_DB'] = $this->getMock(
            'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
            array(),
            array(),
            '',
            false
        );
    }

    protected function setGlobals()
    {
        $GLOBALS['LANG'] = $this
            ->getMockBuilder('TYPO3\\CMS\\Lang\\LanguageService')
            ->getMock();
        $GLOBALS['TYPO3_DB'] = $this
            ->getMockBuilder('TYPO3\\CMS\\Core\\Database\\DatabaseConnection')
            ->setConstructorArgs(array())
            ->getMock();
    }
}
