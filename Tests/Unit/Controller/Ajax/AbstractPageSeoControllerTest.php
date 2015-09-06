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

use TYPO3\CMS\Core\Tests\UnitTestCase;

abstract class AbstractPageSeoControllerTest extends UnitTestCase
{
    /**
     * @var string expected Dao method name to be invoked
     */
    protected $expectedDaoMethod;

    public function setUp()
    {
        $this->setGlobals();
        $this->loginBackendUser();
        $_POST['pid'] = 1;
        $_POST['depth'] = 2;
        $_POST['sysLanguage'] = 0;
    }

    /**
     * @test
     */
    public function testIndex()
    {
        $this->expectedDaoMethod = 'index';
        $subject = $this->getSubject();
        $subject->indexAction(array(), $this->getAjaxRequestHandlerMock());
    }

    /**
     * @test
     */
    public function testUpdate()
    {
        $this->expectedDaoMethod = 'updatePageTableField';
        $subject = $this->getSubject();
        $subject->updateAction(array(), $this->getAjaxRequestHandlerMock());
    }

    /**
     * @test
     */
    public function testUpdateRecursive()
    {
        $this->expectedDaoMethod = 'updatePageTableField';
        $subject = $this->getSubject();
        $subject->updateRecursiveAction(array(), $this->getAjaxRequestHandlerMock());
    }

    /**
     * @return \Metaseo\Metaseo\Controller\Ajax\PageSeoInterface
     */
    abstract protected function getSubject();

    protected function getAjaxRequestHandlerMock()
    {
        return $this
            ->getMockBuilder('TYPO3\\CMS\\Core\\Http\\AjaxRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManagerMock()
    {
        $dependencyInjectionConfig = array(
            array(
                'Metaseo\\Metaseo\\DependencyInjection\Utility\\FrontendUtility',
                $this->getFrontendUtilityMock(),
            ),
            array(
                'TYPO3\\CMS\\Frontend\\Page\\PageRepository',
                $this->getPageRepositoryMock(),
            ),
            array(
                'Metaseo\\Metaseo\\Page\\Part\\PagetitlePart',
                $this->getPageTitlePartMock()
            ),
            array(
                'Metaseo\\Metaseo\\DependencyInjection\\Utility\\HttpUtility',
                $this->getHttpUtilityMock()
            ),
        );
        $objectManager = $this
            ->getMockBuilder('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')
            ->getMock();
        $objectManager
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap($dependencyInjectionConfig)
            );
        return $objectManager;
    }

    /**
     * @return \Metaseo\Metaseo\Dao\PageSeoDao
     */
    protected function getPageSeoDaoMock()
    {
        $listData = array(
            array(
                'uid' => 1,
                'lastupdated' => 1
            )
        );
        $mock = $this
            ->getMockBuilder('Metaseo\\Metaseo\\Dao\\PageSeoDao')
            ->getMock();
        $mock
            ->expects($this->any())
            ->method('index')
            ->will(
                $this->returnValue($listData)
            );
        $mock
            ->expects($this->any())
            ->method($this->expectedDaoMethod);
        $mock
            ->expects($this->any())
            ->method('getPageById')
            ->will($this->returnValue(array('uid' => 1)));
        $mock
            ->expects($this->any())
            ->method('checkForTemplateByUidList')
            ->will($this->returnValue(array('uid' => 1)));
        return $mock;
    }

    /**
     * @return array
     */

    /**
     * @return \TYPO3\CMS\Core\Http\AjaxRequestHandler
     */
    protected function getRequestHandlerMock()
    {
        $ajaxRequestHandler = $this->getAjaxRequestHandlerMock();
        $ajaxRequestHandler
            ->expects($this->exactly(1))
            ->method('setContentFormat');
        $ajaxRequestHandler
            ->expects($this->exactly(1))
            ->method('setContent');
        return $ajaxRequestHandler;
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

    /**
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected function getPageRepositoryMock()
    {
        return $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
    }

    /**
     * @return \Metaseo\Metaseo\Page\Part\PagetitlePart
     */
    protected function getPageTitlePartMock()
    {
        return $this->getMock('Metaseo\\Metaseo\\Page\\Part\\PagetitlePart');
    }

    /**
     * @return \Metaseo\Metaseo\DependencyInjection\Utility\FrontendUtility
     */
    protected function getFrontendUtilityMock()
    {
        return $this->getMock('Metaseo\\Metaseo\\DependencyInjection\\Utility\\FrontendUtility');
    }

    /**
     * @return \Metaseo\Metaseo\DependencyInjection\Utility\HttpUtility
     */
    protected function getHttpUtilityMock()
    {
        return $this->getMock('Metaseo\\Metaseo\\DependencyInjection\\Utility\\HttpUtility');
    }
}
