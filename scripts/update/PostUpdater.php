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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 *
 */

declare(strict_types = 1);

namespace oat\taoScheduler\scripts\update;

use oat\oatbox\extension\InstallAction;
use oat\taoScheduler\model\job\Job;
use oat\taoScheduler\model\job\JobsRegistry;
use oat\taoScheduler\scripts\install\RegisterJobs;
use oat\taoScheduler\scripts\tools\SchedulerHelper;
use DateTime;
use DateTimeZone;

/**
 * Class PostUpdater
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class PostUpdater extends InstallAction implements JobsRegistry
{
    public function __invoke($params)
    {
        $this->registerJobs();
    }

    private function registerJobs()
    {
        $action = new RegisterJobs();
        $action->setServiceLocator($this->getServiceManager());
        return $action([]);
    }

    /**
     * @inheritDoc
     */
    public function getJobs(): array
    {
        return [
            new Job(
                '0 0 * * *',
                new DateTime('now', new DateTimeZone('utc')),
                SchedulerHelper::class,
                ['removeExpiredJobs', false]
            ),
        ];
    }
}
