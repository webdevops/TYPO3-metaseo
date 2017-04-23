<?php

/*
 *  Copyright notice
 *
 *  (c) 2014 - 2017 Markus Blaschke <typo3@markus-blaschke.de> (metaseo)
 *  (c) 2007 - 2013 Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de> (tq_seo)
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

use PHPUnit_Framework_MockObject_MockObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

abstract class AbstractPageSeoControllerTest extends UnitTestCase
{
    /**
     * @var string expected Dao method name to be invoked
     */
    protected $expectedDaoMethod;

    /**
     * @var string one of the database field names which must be in the field list to proceed
     */
    protected $fieldForUpdate;

    /**
     * @var array POST parameters to be passed through the ServerRequest object
     */
    protected $postParameters;

    public function setUp()
    {
        $this->setPostParameters();
        $this->setGlobals();
        $this->loginBackendUser();
    }

    /**
     * @return string
     */
    abstract protected function getUpdateField();

    /**
     * @test
     */
    public function testIndex()
    {
        $this->expectedDaoMethod = 'index';
        $subject = $this->getSubject();
        $subject->indexAction($this->getRequestMock(), $this->getResponseMock());
    }

    /**
     * @test
     */
    public function testUpdate()
    {
        $this->expectedDaoMethod = 'updatePageTableField';
        $subject = $this->getSubject();
        $subject->updateAction($this->getRequestMock(), $this->getResponseMock());
    }

    /**
     * @test
     */
    public function testUpdateRecursive()
    {
        $this->expectedDaoMethod = 'updatePageTableField';
        $subject = $this->getSubject();
        $subject->updateRecursiveAction($this->getRequestMock(), $this->getResponseMock());
    }

    /**
     * @return \Metaseo\Metaseo\Controller\Ajax\PageSeoInterface
     */
    abstract protected function getSubject();

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
            array(
                'TYPO3\\CMS\\Frontend\\Page\\PageRepository',
                $this->getPageRepositoryMock()
            ),
            array(
                'Metaseo\\Metaseo\\Dao\\PageSeoDao',
                $this->getPageSeoDaoMock()
            ),
            array(
                'Metaseo\\Metaseo\\Dao\\TemplateDao',
                $this->getTemplateDaoMock()
            ),
            array(
                'TYPO3\\CMS\\Core\\DataHandling\\DataHandler',
                $this->getDataHandlerMock()
            ),
        );
        $mock = $this->createMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $mock
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($dependencyInjectionConfig));
        return $mock;
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function getResponseMock()
    {
        $mock = $this
            ->getMockBuilder('TYPO3\\CMS\\Core\\Http\\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->any())
            ->method('getBody')
            ->will($this->returnValue($this->getResponseBodyMock()));
        return $mock;
    }

    /**
     * @return \Psr\Http\Message\StreamInterface
     */
    protected function getResponseBodyMock()
    {
        $mock = $this
            ->getMockBuilder('TYPO3\\CMS\\Core\\Http\\Stream')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->any())
            ->method('write');
        return $mock;
    }

    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function getRequestMock()
    {
        $mock = $this
            ->getMockBuilder('TYPO3\\CMS\\Core\\Http\\ServerRequest')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
            ->expects($this->any())
            ->method('getParsedBody')
            ->will(
                $this->returnValue($this->postParameters)
            );
        return $mock;
    }

    /**
     * @return \Metaseo\Metaseo\Dao\PageSeoDao
     */
    protected function getPageSeoDaoMock()
    {
        $listData = array(
            1 => array(
                'uid' => 1,
                'lastUpdated' => 1
            )
        );
        $mock = $this->createMock('Metaseo\\Metaseo\\Dao\\PageSeoDao');
        $mock
            ->expects($this->any())
            ->method('index')
            ->will($this->returnValue($listData));
        $testFrequency = $this->any();
        if ($this->expectedDaoMethod !== 'getPageById') {
            $mock
                ->expects($this->atLeastOnce())
                ->method($this->expectedDaoMethod);
            $testFrequency = $this->atLeastOnce();
        }
        $mock
            ->expects($testFrequency)
            ->method('getPageById')
            ->will(
                $this->returnValue(
                    array(
                        1 => array(
                            'uid' => 1
                        )
                    )
                )
            );
        $mock
            ->expects($this->any())
            ->method('setDataHandler')
            ->will($this->returnSelf());
        $mock
            ->expects($this->any())
            ->method('setPageTreeView')
            ->will($this->returnSelf());
        return $mock;
    }

    /**
     * @return \Metaseo\Metaseo\Dao\PageSeoDao
     */
    protected function getTemplateDaoMock()
    {
        $mock = $this->createMock('Metaseo\\Metaseo\\Dao\\TemplateDao');
        $mock
            ->expects($this->any())
            ->method('checkForTemplateByUidList')
            ->will($this->returnValue(array('uid' => 1)));
        return $mock;
    }

    /**
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function getDataHandlerMock()
    {
        $mock = $this->createMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
        $mock
            ->expects($this->any())
            ->method('getExcludeListArray')
            ->will($this->returnValue(array()));
        return $mock;
    }

    /**
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    protected function getPageRepositoryMock()
    {
        return $this->createMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
    }

    /**
     * @return \Metaseo\Metaseo\Page\Part\PagetitlePart
     */
    protected function getPageTitlePartMock()
    {
        return $this->createMock('Metaseo\\Metaseo\\Page\\Part\\PagetitlePart');
    }

    /**
     * @return \Metaseo\Metaseo\DependencyInjection\Utility\FrontendUtility
     */
    protected function getFrontendUtilityMock()
    {
        $mock = $this->createMock('Metaseo\\Metaseo\\DependencyInjection\\Utility\\FrontendUtility');
        $mock
            ->expects($this->any())
            ->method('getTypoLinkUrl')
            ->will($this->returnValue('https://www.example.com/bingo'));
        $mock
            ->expects($this->any())
            ->method('setPageRepository')
            ->will($this->returnSelf());  //fluent setter
        return $mock;
    }

    /**
     * @return \Metaseo\Metaseo\DependencyInjection\Utility\HttpUtility
     */
    protected function getHttpUtilityMock()
    {
        $mock = $this->createMock('Metaseo\\Metaseo\\DependencyInjection\\Utility\\HttpUtility');
        $this->configureHttpUtilityMock($mock);
        return $mock;
    }

    /**
     * @param PHPUnit_Framework_MockObject_MockObject $mock
     */
    protected function configureHttpUtilityMock(PHPUnit_Framework_MockObject_MockObject &$mock)
    {
        $mock
            ->expects($this->never()) //never() indicates status 200 OK -> no exception has been thrown
            ->method('sendHttpHeader');
    }

    /**
     * Simulates valid backend session
     */
    protected function loginBackendUser()
    {
        $mock = $this->createMock(
            'TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication',
            array(),
            array(),
            '',
            false
        );
        $mock->user = array('uid' => $this->getUniqueId());
        $mock
            ->expects($this->any())
            ->method('check')
            ->will($this->returnValue(true));
        $mock
            ->expects($this->any())
            ->method('doesUserHaveAccess')
            ->will($this->returnValue(true));
        $GLOBALS['BE_USER'] = $mock;
        $GLOBALS['TYPO3_DB'] = $this->createMock(
            'TYPO3\\CMS\\Core\\Database\\DatabaseConnection',
            array(),
            array(),
            '',
            false
        );
    }

    /**
     * Set global variables which cannot be injected via objectManager
     */
    protected function setGlobals()
    {
        $GLOBALS['LANG'] = $this->createMock('TYPO3\\CMS\\Lang\\LanguageService');
        $GLOBALS['TYPO3_DB'] = $this
            ->getMockBuilder('TYPO3\\CMS\\Core\\Database\\DatabaseConnection')
            ->setConstructorArgs(array())
            ->getMock();
    }

    /**
     * Sets POST parameters to be passed through the ServerRequest object
     */
    protected function setPostParameters()
    {
        $this->postParameters = array(
            'pid' => 1,
            'field' => json_encode($this->getUpdateField()),
            'value' => '1',
            'depth' => 2,
            'sysLanguage' => 0
        );
    }
}
