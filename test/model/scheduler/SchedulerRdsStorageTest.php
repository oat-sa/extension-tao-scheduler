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

namespace oat\taoScheduler\test\model\scheduler;

use oat\taoScheduler\model\job\Job;
use DateTime;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\oatbox\service\ServiceManager;

/**
 * Class SchedulerRdsStorageTest
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerRdsStorageTest extends TaoPhpUnitTestRunner
{
    private $persistence;
    private $service;

    public function testAdd()
    {
        $storage = $this->getStorage();
        $job = new Job('* * * * *', new DateTime('@'.time()), 'time', ['foo', 'bar']);

        $this->assertEquals(0, count($storage->getJobs()));
        $this->assertTrue($storage->add($job));
        $this->assertEquals(1, count($storage->getJobs()));
        $this->assertEquals($job->getParams(), $storage->getJobs()[0]->getParams());
        $this->assertEquals($job->getStartTime(), $storage->getJobs()[0]->getStartTime());
        $this->assertEquals($job->getRRule(), $storage->getJobs()[0]->getRRule());
        $this->assertEquals($job->getCallable(), $storage->getJobs()[0]->getCallable());
    }

    /**
     * @expectedException \oat\taoScheduler\model\SchedulerException
     */
    public function testAddException()
    {
        $storage = $this->getStorage();
        $callbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $job = new Job('* * * * *', new DateTime('now'), $callbackMock);
        $storage->add($job);
        //Attempt to add the same job
        $storage->add($job);
    }

    public function testRemove()
    {
        $storage = $this->getStorage();
        $job1 = new Job('* * * * *', new DateTime('@'.time()), 'time', ['foo', 'bar']);
        $job2 = new Job('* 2 * * *', new DateTime('@'.time()), 'time', ['foo', 'bar']);

        $this->assertEquals(0, count($storage->getJobs()));
        $this->assertTrue($storage->add($job1));
        $this->assertTrue($storage->add($job2));
        $this->assertEquals(2, count($storage->getJobs()));
        $this->assertTrue($storage->remove($job2));
        $this->assertEquals(1, count($storage->getJobs()));
        $this->assertEquals($job1->getParams(), $storage->getJobs()[0]->getParams());
        $this->assertEquals($job1->getStartTime(), $storage->getJobs()[0]->getStartTime());
        $this->assertEquals($job1->getRRule(), $storage->getJobs()[0]->getRRule());
        $this->assertEquals($job1->getCallable(), $storage->getJobs()[0]->getCallable());
        $this->assertTrue($storage->remove($job1));
        $this->assertEquals(0, count($storage->getJobs()));
    }

    /**
     * @expectedException \oat\taoScheduler\model\SchedulerException
     */
    public function testRemoveException()
    {
        $storage = $this->getStorage();
        $callbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $job = new Job('* * * * *', new DateTime('now'), $callbackMock);
        //Attempt to remove nonexistent job
        $storage->remove($job);
    }

    /**
     * @throws
     * @return SchedulerRdsStorage
     */
    protected function getStorage()
    {
        if ($this->service === null) {
            $persistenceManager = $this->getSqlMock('test_scheduler');
            $this->persistence = $persistenceManager->getPersistenceById('test_scheduler');
            SchedulerRdsStorage::install($this->persistence);
            $this->service = new SchedulerRdsStorage('test_scheduler');
            $config = new \common_persistence_KeyValuePersistence([], new \common_persistence_InMemoryKvDriver());
            $config->set(\common_persistence_Manager::SERVICE_ID, $persistenceManager);
            $serviceManager = new ServiceManager($config);
            $this->service->setServiceLocator($serviceManager);
        }
        return $this->service;
    }
}