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

use Metaseo\Metaseo\Controller\AbstractAjaxController;
use Metaseo\Metaseo\Controller\Ajax\PageSeo\MetaDataController;
use Metaseo\Metaseo\Exception\Ajax\AjaxException;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class MetaDataControllerTest extends UnitTestCase
{
    /**
     * @expectedException \TYPO3\CMS\Core\Error\Exception
     */
    public function testMissingBackendSession()
    {
        $this->setGlobals();
        $subject = new MetaDataController();
        $subject
            ->setReturnAsArray()
            ->indexAction();
    }

    /**
     * @expectedException \Metaseo\Metaseo\Exception\Ajax\AjaxException
     */
    public function testIndexMissingSessionToken()
    {
        $this->setGlobals();
        $this->loginBackendUser();
        $subject = new MetaDataController();
        try {
            $subject
                ->setObjectManager($this->getObjectManagerMock())
                ->setFormProtection($this->getFormProtectionMock());

            /*
             @todo We use 'exit' in the code which is a bad idea when dealing with unit test.
            $subject
                ->indexAction();
            $this->assertTrue($this->hasOutput());
            $jsonOutput = $this->getActualOutput();
            $jsonArray = json_decode($jsonOutput);
            $this->assertArrayHasKey('error', $jsonArray);
            */

            $subject
                ->setReturnAsArray(true) //passes exception through for testing
                ->indexAction();
        } catch (AjaxException $ajaxException) {
            $this->assertEquals('[0x4FBF3C06]', $ajaxException->getCode());
            $this->assertEquals(AbstractAjaxController::HTTP_STATUS_UNAUTHORIZED, $ajaxException->getHttpStatus());
            throw $ajaxException;
        }
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
