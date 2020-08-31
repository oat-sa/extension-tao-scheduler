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

use oat\taoScheduler\scripts\tools\SchedulerHelper;
use oat\taoScheduler\model\scheduler\JobsConfig as JobsConfigInterface;
use DateTime;
use DateTimeZone;
use oat\taoScheduler\model\job\Job;
/**
 * Class RegisterJobs
 * @package oat\taoScheduler\scripts\install
 */
class JobsConfig implements JobsConfigInterface
{
    /**
     * @inheritDoc
     */
    public function getJobs():array
    {
        return [
            new Job(
                '0 0 * * *',
                new DateTime('now', new DateTimeZone('utc')),
                SchedulerHelper::class,
                ['removeExpiredJobs', false]
            )
        ];
    }
}
