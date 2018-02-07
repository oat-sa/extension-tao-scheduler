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

use DateTimeInterface;
use \oat\taoScheduler\model\job\Job;

/**
 * Interface SchedulerServiceInterface
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package oat\taoScheduler
 */
interface SchedulerServiceInterface
{
    const SERVICE_ID = 'taoScheduler/SchedulerService';
    const OPTION_JOBS = 'jobs';

    /**
     * Schedule an event
     *
     * @param string $rRule Recurrence rule (@see https://tools.ietf.org/html/rfc5545#section-3.3.10)
     * @param DateTimeInterface $startTime
     * @param $callback Callback to be executed.
     *                  Also can be an array with tao service identifier and method name (e.g. ['taoExt/MyService', 'doSomething'])
     * @return boolean
     */
    public function attach($rRule, DateTimeInterface $startTime, $callback);

    /**
     * Remove existing event from schedule
     *
     * @param string $rRule Recurrence rule (@see https://tools.ietf.org/html/rfc5545#section-3.3.10)
     * @param DateTimeInterface $startTime
     * @param $callback Callback to be executed.
     *                  Also can be an array with tao service identifier and method name (e.g. ['taoExt/MyService', 'doSomething'])
     * @return boolean
     */
    public function detach($rRule, DateTimeInterface $startTime, $callback);

    /**
     * Run cron jobs scheduled to period
     * @return Job[]
     */
    public function getJobs();
}