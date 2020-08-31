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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoScheduler\model\scheduler;

use oat\generis\persistence\PersistenceManager;
use oat\generis\test\TestCase;
use oat\oatbox\cache\SimpleCache;
use oat\oatbox\service\ServiceManager;
use oat\taoScheduler\model\job\JobInterface;
use oat\taoScheduler\model\job\Job;
use org\bovigo\vfs\vfsStream;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use oat\taoScheduler\model\SchedulerException;
use DateTime;

/**
 * Class SchedulerRdsStorage
 * @package oat\taoScheduler\model\scheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class StorageAggregatorTest extends TestCase
{
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

        $job = new Job('5 * * * *', new DateTime('@'.time()), 'time', ['foo', 'bar']);
        $this->assertTrue($storage->add($job, false));

        $this->assertEquals(2, count($storage->getJobs()));
    }

    public function testAddException()
    {
        $this->expectException(SchedulerException::class);
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
        $this->assertTrue($storage->add($job2, false));
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

    public function testRemoveException()
    {
        $this->expectException(SchedulerException::class);
        $storage = $this->getStorage();
        $callbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $job = new Job('* * * * *', new DateTime('now'), $callbackMock);
        //Attempt to remove nonexistent job
        $storage->remove($job);
    }

    private function getLocatorMock()
    {
        $persistenceManager = $this->getSqlMock('test_scheduler');
        $sqlPersistence = $persistenceManager->getPersistenceById('test_scheduler');

        vfsStream::setup('cache');
        $params = [
            'dir' => vfsStream::url('cache'),
        ];
        $driver = new \common_persistence_PhpFileDriver();
        $persistence = $driver->connect('test', $params);
        $persistenceManager = $this->getMockBuilder(PersistenceManager::class)->getMock();
        $persistenceManager->method('getPersistenceById')->will($this->returnValueMap([
            ['cache', $persistence],
            ['test_scheduler', $sqlPersistence]
        ]));
        $simpleCache = new \oat\oatbox\cache\KeyValueCache(array(
            'persistence' => 'cache'
        ));
        $serviceLocator = $this->getServiceLocatorMock([
            SimpleCache::SERVICE_ID => $simpleCache,
            PersistenceManager::SERVICE_ID => $persistenceManager
        ]);
        $simpleCache->setServiceLocator($serviceLocator);
        return $serviceLocator;
    }

    /**
     * @return StorageAggregator
     */
    private function getStorage()
    {
        $serviceLocator = $this->getLocatorMock();

        $rdsStorage = new SchedulerRdsStorage('test_scheduler');
        $cacheStorage = new SchedulerConfigStorage();
        $rdsStorage->setServiceLocator($serviceLocator);
        $cacheStorage->setServiceLocator($serviceLocator);
        $storageAggregator = new StorageAggregator($rdsStorage, $cacheStorage);
        $storageAggregator->setServiceLocator($serviceLocator);
        $storageAggregator->install();
        return $storageAggregator;
    }
}
