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
use oat\taoScheduler\scripts\tools\SchedulerHelper;
use oat\taoScheduler\scripts\update\dbMigrations\Version20190422114045;
use DateTime;
use DateTimeZone;

/**
 * Class Updater
 *
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 */
class RegisterJobs
{
    public function __invoke()
    {
        $time = (new DateTime())->format('Y-m-d\TH:i:s');
        file_put_contents('./time', $time);
        return $time;
    }
}
