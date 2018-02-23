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

use oat\taoScheduler\model\scheduler\SchedulerService;
use oat\taoScheduler\model\runner\JobRunnerService;
use oat\taoScheduler\model\runner\JobRunnerPeriod;
use oat\oatbox\service\ServiceManager;
use common_report_Report;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;
use oat\tao\test\TaoPhpUnitTestRunner;

/**
 * Class JobRunnerServiceTest
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class JobRunnerServiceTest extends TaoPhpUnitTestRunner
{

    public function testRun()
    {
        $now = time();
        $serviceManager = $this->getServiceManager();
        $schedulerService = $serviceManager->get(SchedulerService::SERVICE_ID);
        $runnerService = $serviceManager->get(JobRunnerService::SERVICE_ID);

        $schedulerService->attach('FREQ=MONTHLY;COUNT=1', new \DateTime('@'.$now), ['callback/mock', 'myCallBack'], ['foo']);
        $schedulerService->attach('FREQ=MONTHLY;COUNT=1', new \DateTime('@'.($now+2)), ['errorcallback/mock', 'myCallBack'], ['foo', 'bar']);

        /** @var common_report_Report $report */
        $report = $runnerService->run(new \DateTime('@'.$now), new \DateTime('@'.($now+1)));
        $this->assertEquals(1, count($report->getSuccesses()));
        $this->assertEquals(0, count($report->getErrors()));

        $report = $runnerService->run(new \DateTime('@'.($now+2)), new \DateTime('@'.($now+3)));
        $this->assertEquals(1, count($report->getErrors()));
        $this->assertEquals(0, count($report->getSuccesses()));


        //test cron job syntax
        $dt10minAgo = new \DateTime('@'.($now-(10*60)));
        $dt10minAgo->setTime($dt10minAgo->format('G'), $dt10minAgo->format('i'), 0, 0);
        $schedulerService->attach('* * * * *', $dt10minAgo, ['callback/mock', 'myCallBack'], ['foo']);

        $dt8minAgo = new \DateTime('@'.(($dt10minAgo->getTimestamp()+(2*60))));

        $report = $runnerService->run($dt10minAgo, $dt8minAgo);
        $this->assertEquals(3, count($report->getSuccesses()));
        $this->assertEquals(0, count($report->getErrors()));
    }

    public function testGetLastLaunchPeriod()
    {
        $now = time();
        $serviceManager = $this->getServiceManager();
        /** @var SchedulerService $schedulerService */
        $schedulerService = $serviceManager->get(SchedulerService::SERVICE_ID);
        /** @var JobRunnerService $runnerService */
        $runnerService = $serviceManager->get(JobRunnerService::SERVICE_ID);

        $schedulerService->attach('FREQ=MONTHLY;COUNT=1', new \DateTime('@'.$now), ['callback/mock', 'myCallBack']);

        $runnerService->run(new \DateTime('@'.$now), new \DateTime('@'.($now+1)));
        $lastLaunchPeriod = $runnerService->getLastLaunchPeriod();
        $this->assertTrue($lastLaunchPeriod instanceof JobRunnerPeriod);
        $this->assertEquals($now, $lastLaunchPeriod->getFrom()->getTimestamp());
        $this->assertEquals($now+1, $lastLaunchPeriod->getTo()->getTimestamp());
    }

    /**
     * @return ServiceManager
     * @throws
     */
    private function getServiceManager()
    {
        $runner = new JobRunnerService([
            JobRunnerService::OPTION_PERSISTENCE => 'test'
        ]);
        $scheduler = new SchedulerService([
            SchedulerService::OPTION_JOBS_STORAGE => SchedulerRdsStorage::class,
            SchedulerService::OPTION_JOBS_STORAGE_PARAMS => ['test_scheduler'],
        ]);

        $persistenceManager = $this->getSqlMock('test_scheduler');
        $persistence = $persistenceManager->getPersistenceById('test_scheduler');
        SchedulerRdsStorage::install($persistence);

        $callbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $callbackMock->expects($this->any())
            ->method('myCallBack')
            ->with($this->equalTo('foo'));
        $callbackMock->method('myCallBack')->will($this->returnValue(true));

        $errorCallbackMock = $this->getMockBuilder('\oat\oatbox\service\ConfigurableService')
            ->setMethods(['myCallBack'])
            ->getMock();
        $errorCallbackMock->expects($this->any())
            ->method('myCallBack')
            ->with($this->equalTo('foo'), $this->equalTo('bar'))
            ->will($this->returnCallback(function () {
                throw new \Exception('foo');
            }));

        $persistenceManager = new \common_persistence_Manager([
            'persistences' => [
                'test' => [
                    'driver' => 'no_storage'
                ],
            ]
        ]);

        $pmReflection = new \ReflectionClass($persistenceManager);
        $property = $pmReflection->getProperty('persistences');
        $property->setAccessible(true);
        $persistences = $property->getValue($persistenceManager);
        $persistences['test_scheduler'] = $persistence;
        $property->setValue($persistenceManager, $persistences);

        $config = new \common_persistence_KeyValuePersistence([], new \common_persistence_InMemoryKvDriver());
        $config->set(\common_persistence_Manager::SERVICE_ID, $persistenceManager);
        $config->set(SchedulerService::SERVICE_ID, $scheduler);
        $config->set(JobRunnerService::SERVICE_ID, $runner);
        $config->set('generis/log', new \oat\oatbox\log\LoggerService([]));
        $config->set('callback/mock', $callbackMock);
        $config->set('errorcallback/mock', $errorCallbackMock);
        $serviceManager = new ServiceManager($config);
        $scheduler->setServiceLocator($serviceManager);
        return $serviceManager;
    }

}
