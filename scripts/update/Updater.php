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
use oat\taoScheduler\model\runner\JobRunnerService;
use oat\taoScheduler\model\scheduler\SchedulerService;

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
    }
}