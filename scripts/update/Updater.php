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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoScheduler\scripts\update;

use common_ext_ExtensionUpdater;
use oat\taoScheduler\model\inspector\RdsActionInspector;
use oat\taoScheduler\model\runner\JobRunnerService;
use oat\taoScheduler\model\scheduler\SchedulerService;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;

/**
 * Class Updater
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class Updater extends common_ext_ExtensionUpdater
{
    public function update($initialVersion)
    {
        $this->skip('0.1.0', '0.2.0');

        if ($this->isVersion('0.2.0')) {
            $this->getServiceManager()->register(
                JobRunnerService::SERVICE_ID,
                new JobRunnerService([
                    JobRunnerService::OPTION_PERSISTENCE => 'cache',
                ])
            );
            $this->getServiceManager()->register(
                SchedulerService::SERVICE_ID,
                new SchedulerService([
                    SchedulerService::OPTION_JOBS => []
                ])
            );
            $this->setVersion('0.3.0');
        }

        $this->skip('0.3.0', '0.4.0');

        if ($this->isVersion('0.4.0')) {
            $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
            /** @var SchedulerService $scheduler */
            $scheduler = $this->getServiceManager()->get(SchedulerService::SERVICE_ID);
            $jobs = $scheduler->getOption('jobs');
            $persistence = $persistenceManager->getPersistenceById('default');
            SchedulerRdsStorage::install($persistence);
            $scheduler->setOptions([
                SchedulerService::OPTION_JOBS_STORAGE => SchedulerRdsStorage::class,
                SchedulerService::OPTION_JOBS_STORAGE_PARAMS => ['default'],
            ]);

            foreach ($jobs as $job) {
                $scheduler->attach($job[0], new \DateTime('@'.$job[1]), $job[2], $job[3]);
            }

            $this->getServiceManager()->register(SchedulerService::SERVICE_ID, $scheduler);
            $this->setVersion('0.5.1');
        }

        $this->skip('0.5.1', '0.8.2');

        if ($this->isVersion('0.8.2')) {
            $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
            $persistence = $persistenceManager->getPersistenceById('default');
            $jobRunnerService = $this->getServiceManager()->get(JobRunnerService::SERVICE_ID);
            $jobRunnerService->setOption(JobRunnerService::OPTION_ACTION_INSPECTOR_PERSISTENCE, 'default');
            $this->getServiceManager()->register(JobRunnerService::SERVICE_ID, $jobRunnerService);
            RdsActionInspector::initDatabase($persistence);
            $this->setVersion('0.9.0');
        }
    }
}
