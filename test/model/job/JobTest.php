<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoScheduler\test\model\job;

use \DateTime;
use oat\generis\test\TestCase;
use oat\taoScheduler\model\job\Job;
use oat\taoScheduler\model\SchedulerException;

/**
 * Class JobTest
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class JobTest extends TestCase
{

    public function testGetRRule()
    {
        $time = time();
        $callbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $job = new Job('FREQ=MONTHLY;COUNT=5', new DateTime('@' . $time), $callbackMock);
        $this->assertEquals('FREQ=MONTHLY;COUNT=5', $job->getRRule());
    }

    public function testGetCallable()
    {
        $time = time();
        $callbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $job = new Job('FREQ=MONTHLY;COUNT=5', new DateTime('@' . $time), $callbackMock);
        $this->assertEquals('FREQ=MONTHLY;COUNT=5', $job->getRRule());
    }

    public function testGetStartTime()
    {
        $time = time();
        $callbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $job = new Job('FREQ=MONTHLY;COUNT=5', new DateTime('@' . $time), [$callbackMock, 'myCallBack']);
        $this->assertInstanceOf('\DateTimeInterface', $job->getStartTime());
        $this->assertEquals($time, $job->getStartTime()->getTimestamp());
    }

    /**
     * @inheritdoc
     */
    public function testToPhpCode()
    {
        $unserializedJob = null;
        $time = time();

        $job = new Job('FREQ=MONTHLY;COUNT=5', new DateTime('@' . $time), 'time');
        $jobPhpCode = $job->__toPhpCode();
        eval('$unserializedJob = ' . $jobPhpCode.';');
        $this->assertEquals(['FREQ=MONTHLY;COUNT=5', $time, 'time', [], Job::TYPE_CONFIG], $unserializedJob);

        $job = new Job('FREQ=MONTHLY;COUNT=5', new DateTime('@' . $time), ['common_Utils', 'toPHPVariableString'], ['foo', 'bar']);
        $jobPhpCode = $job->__toPhpCode();
        eval('$unserializedJob = ' . $jobPhpCode.';');
        $this->assertEquals(
            ['FREQ=MONTHLY;COUNT=5', $time, ['common_Utils', 'toPHPVariableString'], ['foo', 'bar'], Job::TYPE_CONFIG],
            $unserializedJob
        );


        $phpSerializableCallbackMock = $this->getMockBuilder('\oat\oatbox\PhpSerializable')
            ->setMethods(['__toPhpCode', '__toString'])
            ->getMock();
        $phpSerializableCallbackMock->method('__toPhpCode')
            ->willReturn('foo');
        $phpSerializableCallbackMock->method('__toString')
            ->willReturn('foo');

        $time = time();
        $job = new Job('FREQ=MONTHLY;COUNT=5', new DateTime('@' . $time), [$phpSerializableCallbackMock, 'bar']);
        $jobPhpCode = $job->__toPhpCode();
        eval('$unserializedJob = ' . $jobPhpCode.';');
        $this->assertEquals(['FREQ=MONTHLY;COUNT=5', $time, ['foo', 'bar'], [], Job::TYPE_CONFIG], $unserializedJob);
    }

    public function testToPhpCodeException()
    {
        $this->expectException(SchedulerException::class);
        $job = new Job('FREQ=MONTHLY;COUNT=5', new DateTime('now'), function () {return 'foo';});
        $job->__toPhpCode();
    }

}