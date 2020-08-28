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
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */

namespace oat\taoScheduler\scripts\install;

use oat\generis\persistence\PersistenceManager;
use oat\oatbox\extension\AbstractAction;
use common_report_Report;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoScheduler\model\inspector\RdsActionInspector;
use oat\taoScheduler\model\scheduler\SchedulerCacheStorage;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;
use oat\taoScheduler\model\scheduler\SchedulerService;
use oat\taoScheduler\model\scheduler\StorageAggregator;
use oat\taoScheduler\model\runner\JobRunnerService;

/**
 * Class RegisterRdsStorage
 * @package oat\taoScheduler\scripts\install
 */
class RegisterRdsStorage extends AbstractAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        try {
            $schedulerService = $this->getServiceManager()->get(SchedulerService::SERVICE_ID);
        } catch (ServiceNotFoundException $e) {
            $schedulerService = new SchedulerService([
                SchedulerService::OPTION_JOBS_STORAGE => StorageAggregator::class,
                SchedulerService::OPTION_JOBS_STORAGE_PARAMS => [
                    new SchedulerRdsStorage('default'),
                    new SchedulerCacheStorage(),
                ],
            ]);
        }

        $schedulerStorageClass = $schedulerService->getOption(SchedulerService::OPTION_JOBS_STORAGE);
        $schedulerStorageOptions = $schedulerService->getOption(SchedulerService::OPTION_JOBS_STORAGE_PARAMS);
        $storage = new $schedulerStorageClass(...$schedulerStorageOptions);
        $storage->setServiceLocator($this->getServiceLocator());
        $storage->install();
        $this->initActionInspector();
        $this->getServiceManager()->register(SchedulerService::SERVICE_ID, $schedulerService);
        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Scheduler job storage successfully created'));
    }

    private function initActionInspector()
    {
        $persistenceManager = $this->getServiceManager()->get(PersistenceManager::SERVICE_ID);
        $actionInspectorPersistenceId = $this->getServiceManager()->get(JobRunnerService::SERVICE_ID)
            ->getOption(JobRunnerService::OPTION_ACTION_INSPECTOR_PERSISTENCE);

        $persistence = $persistenceManager->getPersistenceById($actionInspectorPersistenceId);
        RdsActionInspector::initDatabase($persistence);
    }
}
