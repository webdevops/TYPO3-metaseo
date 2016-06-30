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

namespace Metaseo\Metaseo\Tests\Unit\Utility;


use Metaseo\Metaseo\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Tests\UnitTestCase;

class DatabaseUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function conditionIn()
    {
        $field = 'field';
        $values = array('abc', 'xy_z', 'uVw');

        $testCases = array (
            array(
                'field' => $field,
                'values' => $values,
                'required' => true,
                'expectedResult' => 'field IN (\'xX1\',\'xX1\',\'xX1\')',
                'expectedCalls' => 3,
            ),
            array(
                'field' => $field,
                'values' => array(),
                'required' => true,
                'expectedResult' => '1=0',
                'expectedCalls' => 0,
            ),
            array(
                'field' => $field,
                'values' => array(),
                'required' => false,
                'expectedResult' => '1=1',
                'expectedCalls' => 0,
            ),
        );
        foreach ($testCases as $testCase) {
            $this->getDB($testCase['expectedCalls']);
            $this->assertEquals(
                $testCase['expectedResult'],
                DatabaseUtility::conditionIn(
                    $testCase['field'],
                    $testCase['values'],
                    $testCase['required']
                )
            );

            $this->getDB($testCase['expectedCalls']);
            $this->assertEquals(
                str_replace('IN', 'NOT IN', $testCase['expectedResult']),
                DatabaseUtility::conditionNotIn(
                    $testCase['field'],
                    $testCase['values'],
                    $testCase['required']
                )
            );
        }
    }

    /**
     * @test
     */
    public function testConditionInDefault()
    {
        $this->getDB(0);
        $this->assertEquals(
            '1=0',
            DatabaseUtility::conditionIn(
                'abc',
                array()
            )
        );
        $this->assertEquals(
            '1=0',
            DatabaseUtility::conditionNotIn(
                'abc',
                array()
            )
        );
    }

    /**
     * @expectedException \TypeError
     */
    public function testConditionInException()
    {
        $this->getDB(0);
        DatabaseUtility::conditionIn(
            'abc',
            'x' //not an array
        );
    }

    /**
     * @param $expectedCalls
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDB($expectedCalls)
    {
        $db = $GLOBALS['TYPO3_DB'] = $this
            ->getMockBuilder('TYPO3\\CMS\\Core\\Database\\DatabaseConnection')
            ->getMock();
        $db->expects($this->exactly($expectedCalls))
            ->method('fullQuoteStr')
            ->will(
                $this->returnValue('\'xX1\'')
            );

        return $db;
    }
}
