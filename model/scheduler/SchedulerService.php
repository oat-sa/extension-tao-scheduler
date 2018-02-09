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
use oat\taoScheduler\model\job\Job;
use DateTimeInterface;
use oat\oatbox\log\LoggerAwareTrait;

/**
 * Class SchedulerService
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerService extends ConfigurableService implements SchedulerServiceInterface
{

    use LoggerAwareTrait;

    /**
     * @inheritdoc
     */
    public function attach($rRule, DateTimeInterface $startTime, $callback, $params = [])
    {
        $jobs = $this->getOption(self::OPTION_JOBS);
        $job = new Job($rRule, $startTime, $callback, $params);
        $jobs[] = $job;
        $this->setOption(self::OPTION_JOBS, $jobs);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function detach($rRule, DateTimeInterface $startTime, $callback, $params = [])
    {
        $jobs = $this->getJobs();
        $jobToRemove = new Job($rRule, $startTime, $callback, $params);
        $result = false;
        if (($key = array_search($jobToRemove, $jobs)) !== false) {
            unset($jobs[$key]);
        }
        $this->setOption(self::OPTION_JOBS, $jobs);
        return $result;
    }

    /**
     * Return array of all the scheduled jobs
     * @return Jobs[]
     */
    public function getJobs()
    {
        $jobs = $this->getOption(self::OPTION_JOBS);
        $result = [];
        foreach ($jobs as $job) {
            if (is_array($job)) {
                $result[] = new Job($job[0], new \DateTime('@'.$job[1], new \DateTimeZone('UTC')), $job[2], $job[3]);
            } else {
                $result[] = $job;
            }
        }
        return $result;
    }
}