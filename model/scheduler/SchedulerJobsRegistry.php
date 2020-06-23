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
namespace oat\taoScheduler\model\scheduler;

use oat\oatbox\service\ConfigurableService;
use oat\taoScheduler\model\job\JobsRegistry;
use common_ext_ExtensionsManager as ExtensionsManager;
use common_ext_Extension as Extension;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\taoScheduler\model\job\JobInterface;

/**
 * Class SchedulerJobsRegistry
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerJobsRegistry extends ConfigurableService
{
    const SERVICE_ID = 'taoScheduler/SchedulerJobsRegistry';

    /**
     * @return JobInterface[]
     * @throws InvalidServiceManagerException
     */
    public function update()
    {
        $jobs = [];
        $this->detachAllJobs();
        /** @var ExtensionsManager $extManager */
        $extManager = $this->getServiceLocator()->get(ExtensionsManager::SERVICE_ID);
        $scheduler = $this->getScheduler();
        /** @var Extension $extension */
        foreach ($extManager->getInstalledExtensions() as $extension) {
            try {
                $updater = $extension->getUpdater();
            } catch (\common_ext_ManifestException $e) {
                //updater not found
                continue;
            }
            if ($updater instanceof JobsRegistry) {
                foreach ($updater->getJobs() as $job) {
                    $jobs[] = $jobs;
                    $scheduler->attach($job->getRRule(), $job->getStartTime(), $job->getCallable(), $job->getParams());
                }
            }
        }
        return $jobs;
    }

    /**
     * Detach all registered jobs
     * @throws InvalidServiceManagerException
     */
    private function detachAllJobs()
    {
        $scheduler = $this->getScheduler();
        foreach ($scheduler->getJobs() as $job) {
            $scheduler->detach($job->getRRule(), $job->getStartTime(), $job->getCallable(), $job->getParams());
        }
    }

    /**
     * @return SchedulerService
     * @throws InvalidServiceManagerException
     */
    private function getScheduler()
    {
        return $this->getServiceLocator()->get(SchedulerService::SERVICE_ID);
    }
}
