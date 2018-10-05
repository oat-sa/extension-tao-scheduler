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

use oat\oatbox\extension\AbstractAction;
use common_report_Report;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoScheduler\model\inspector\RdsActionInspector;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;
use oat\taoScheduler\model\scheduler\SchedulerService;

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
                SchedulerService::OPTION_JOBS_STORAGE => SchedulerRdsStorage::class,
                SchedulerService::OPTION_JOBS_STORAGE_PARAMS => ['default'],
            ]);
        }

        $persistenceManager = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
        $schedulerStorageClass = $schedulerService->getOption(SchedulerService::OPTION_JOBS_STORAGE);
        $schedulerStorageOptions = $schedulerService->getOption(SchedulerService::OPTION_JOBS_STORAGE_PARAMS);
        $persistence = $persistenceManager->getPersistenceById($schedulerStorageOptions[0]);
        call_user_func_array([$schedulerStorageClass, 'install'], [$persistence]);
        RdsActionInspector::initDatabase($persistence);
        $this->getServiceManager()->register(SchedulerService::SERVICE_ID, $schedulerService);
        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Scheduler job storage successfully created'));
    }
}
