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
use common_persistence_Manager;
use common_report_Report;

/**
 * Class JobRunnerServiceTest
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class JobRunnerServiceTest extends \PHPUnit_Framework_TestCase
{

    public function testRun()
    {
        $now = time();
        $serviceManager = $this->getServiceManager();
        $schedulerService = $serviceManager->get(SchedulerService::SERVICE_ID);
        $runnerService = $serviceManager->get(JobRunnerService::SERVICE_ID);

        $callbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $callbackMock->expects($this->once())
            ->method('myCallBack')
            ->will($this->returnValue(true));

        $errorCallbackMock = $this->getMockBuilder('\oat\oatbox\service\ConfigurableService')
            ->setMethods(['myCallBack'])
            ->getMock();
        $errorCallbackMock->expects($this->once())
            ->method('myCallBack')
            ->will($this->returnCallback(function () {
                throw new \Exception('foo');
            }));

        $serviceManager->register('callback/service', $errorCallbackMock);

        $schedulerService->attach('FREQ=MONTHLY;COUNT=1', new \DateTime('@'.$now), [$callbackMock, 'myCallBack']);
        $schedulerService->attach('FREQ=MONTHLY;COUNT=1', new \DateTime('@'.($now+2)), ['callback/service', 'myCallBack']);

        /** @var common_report_Report $report */
        $report = $runnerService->run(new \DateTime('@'.$now), new \DateTime('@'.($now+1)));
        $this->assertEquals(1, count($report->getSuccesses()));
        $this->assertEquals(0, count($report->getErrors()));

        $report = $runnerService->run(new \DateTime('@'.($now+2)), new \DateTime('@'.($now+3)));
        $this->assertEquals(1, count($report->getErrors()));
        $this->assertEquals(0, count($report->getSuccesses()));
    }

    public function testGetLastLaunchPeriod()
    {
        $now = time();
        $serviceManager = $this->getServiceManager();
        /** @var SchedulerService $schedulerService */
        $schedulerService = $serviceManager->get(SchedulerService::SERVICE_ID);
        /** @var JobRunnerService $runnerService */
        $runnerService = $serviceManager->get(JobRunnerService::SERVICE_ID);

        $callbackMock = $this->getMockBuilder('\oat\oatbox\service\ConfigurableService')
            ->setMethods(['myCallBack'])
            ->getMock();
        $callbackMock->expects($this->once())
            ->method('myCallBack')
            ->will($this->returnValue(true));

        $schedulerService->attach('FREQ=MONTHLY;COUNT=1', new \DateTime('@'.$now), [$callbackMock, 'myCallBack']);

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
        $driver = new \common_persistence_InMemoryKvDriver();
        $driver->connect('test', []);
        $config = new \common_persistence_KeyValuePersistence([], $driver);
        $serviceManager = new ServiceManager($config);


        $scheduler = new SchedulerService([]);
        $runner = new JobRunnerService([
            JobRunnerService::OPTION_PERSISTENCE => 'test'
        ]);

        $serviceManager->register(SchedulerService::SERVICE_ID, $scheduler);
        $serviceManager->register(JobRunnerService::SERVICE_ID, $runner);
        $serviceManager->register(common_persistence_Manager::SERVICE_ID, new common_persistence_Manager([
            'persistences' => [
                'test' => [
                    'driver' => 'no_storage'
                ],
            ]
        ]));

        return $serviceManager;
    }

}