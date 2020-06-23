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

use oat\generis\persistence\PersistenceManager;
use oat\generis\test\TestCase;
use oat\taoScheduler\model\job\Job;
use oat\taoScheduler\model\job\JobsRegistry;
use common_ext_ExtensionsManager as ExtensionsManager;
use common_ext_Extension as Extension;
use oat\taoScheduler\model\scheduler\SchedulerJobsRegistry;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;
use oat\taoScheduler\model\scheduler\SchedulerService;
use DateTime;

class SchedulerJobsRegistryTest extends TestCase
{
    const SERVICE_ID = 'taoScheduler/SchedulerJobsRegistry';

    public function testUpdate()
    {
        $service = $this->getInstance();
        $job = new Job('0 1 2 3 4', new DateTime('@'.time()), 'time', ['initial', 'job']);
        /** @var SchedulerService $scheduler */
        $scheduler = $service->getServiceLocator()->get(SchedulerService::SERVICE_ID);
        $this->assertCount(0, $scheduler->getJobs());
        $scheduler->attach($job->getRRule(), $job->getStartTime(), $job->getCallable(), $job->getParams());
        $this->assertCount(1, $scheduler->getJobs());

        $service->update();
        $this->assertCount(1, $scheduler->getJobs());
        $newJob = $scheduler->getJobs()[0];
        $this->assertEquals($newJob->getRRule(), '* * * * *');
    }

    /**
     * @return SchedulerJobsRegistry
     */
    private function getInstance()
    {
        $serviceLocator = $this->getServiceLocatorMock([
            ExtensionsManager::SERVICE_ID => $this->getExtensionManagerMock(),
            SchedulerService::SERVICE_ID => $this->getSchedulerMock(),
        ]);
        $instance = new SchedulerJobsRegistry();
        $instance->setServiceLocator($serviceLocator);
        return $instance;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getExtensionManagerMock()
    {
        $extensionFoo = $this->getMockBuilder(Extension::class)
            ->disableOriginalConstructor()
            ->getMock();
        $extensionFoo->method('getUpdater')
            ->willReturn(new class ($extensionFoo) extends \common_ext_ExtensionUpdater implements JobsRegistry {
                public function update($v){}
                public function getJobs(): array
                {
                    return [
                        new Job('* * * * *', new DateTime('@'.time()), 'time', ['foo', 'bar'])
                    ];
                }
            });
        $extensionsManagerMock = $this->getMockBuilder(ExtensionsManager::class)
            ->getMock();
        $extensionsManagerMock->method('getInstalledExtensions')
            ->willReturn([
                'foo' => $extensionFoo,
            ]);
        return $extensionsManagerMock;
    }

    private function getSchedulerMock()
    {
        $scheduler = new SchedulerService([
            SchedulerService::OPTION_JOBS_STORAGE => SchedulerRdsStorage::class,
            SchedulerService::OPTION_JOBS_STORAGE_PARAMS => ['test_scheduler'],
        ]);

        $persistenceManager = $this->getSqlMock('test_scheduler');
        $persistence = $persistenceManager->getPersistenceById('test_scheduler');

        SchedulerRdsStorage::install($persistence);
        $serviceLocator = $this->getServiceLocatorMock([
            PersistenceManager::SERVICE_ID => $persistenceManager
        ]);
        $scheduler->setServiceLocator($serviceLocator);
        return $scheduler;
    }
}
