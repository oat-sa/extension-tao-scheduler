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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */

namespace oat\taoScheduler\scripts\install;

use oat\oatbox\extension\AbstractAction;
use common_report_Report as Report;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoScheduler\model\job\JobsRegistry;
use oat\taoScheduler\model\scheduler\SchedulerService;
use oat\taoScheduler\scripts\tools\SchedulerHelper;
use DateTime;
use DateTimeZone;
use common_ext_ExtensionsManager as ExtensionsManager;
use common_ext_Extension as Extension;
use oat\oatbox\service\exception\InvalidServiceManagerException;

/**
 * Class RegisterJobs
 * @package oat\taoScheduler\scripts\install
 */
class RegisterJobs extends AbstractAction
{
    /**
     * @return Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        $this->detachAllJobs();
        /** @var ExtensionsManager $extManager */
        $extManager = $this->getServiceManager()->get(ExtensionsManager::SERVICE_ID);
        $scheduler = $this->getScheduler();
        /** @var Extension $extension */
        foreach ($extManager->getInstalledExtensions() as $extension) {
            $postUpdaterClass = $extension->getManifest()->getPostUpdateHandler();
            if (is_subclass_of($postUpdaterClass, JobsRegistry::class)) {
                $postUpdater = new $postUpdaterClass($extension);
                foreach ($postUpdater->getJobs() as $job) {
                    $scheduler->attach($job->getRRule(), $job->getStartTime(), $job->getCallable(), $job->getParams());
                }
            }
        }
    }

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
        return $this->getServiceManager()->get(SchedulerService::SERVICE_ID);
    }
}
