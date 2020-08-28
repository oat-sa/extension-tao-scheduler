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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoScheduler\model\scheduler;

use oat\oatbox\cache\SimpleCache;
use oat\taoScheduler\model\job\JobInterface;
use oat\taoScheduler\model\job\Job;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use oat\taoScheduler\model\SchedulerException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class SchedulerRdsStorage
 * @package oat\taoScheduler\model\scheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerCacheStorage implements SchedulerStorageInterface
{
    use ServiceLocatorAwareTrait;

    const CACHE_KEY = 'SchedulerJobsStorage';

    /**
     * @inheritDoc
     * @param JobInterface $job
     * @return bool|void
     * @throws InvalidArgumentException
     * @throws SchedulerException
     */
    public function add(JobInterface $job)
    {
        $jobToAdd = json_encode($job);
        if ($this->isExists($job)) {
            throw new SchedulerException('Job already exists');
        }

        $jobs = $this->getEncodedJobs();
        $jobs[] = $jobToAdd;
        return $this->getPersistence()->set(self::CACHE_KEY, json_encode($jobs));
    }

    /**
     * @inheritdoc
     */
    public function remove(JobInterface $job)
    {
        $jobToDelete = json_encode($job);
        if (!$this->isExists($job)) {
            throw new SchedulerException('Job does not exist');
        }

        $jobs = $this->getEncodedJobs();
        $key = array_search($jobToDelete, $jobs);
        unset($jobs[$key]);
        return $this->getPersistence()->set(self::CACHE_KEY, json_encode($jobs));
    }

    /**
     * @inheritdoc
     */
    public function getJobs()
    {
        $jobs = [];
        foreach ($this->getEncodedJobs() as $job) {
            $jobs[] = Job::restore($job);
        }
        return $jobs;
    }

    /**
     * Check if job exists in the storage
     * @param JobInterface $job
     * @return bool
     */
    private function isExists(JobInterface $job)
    {
        $jobs = $this->getEncodedJobs();
        return in_array(json_encode($job), $jobs);
    }

    /**
     * @return SimpleCache
     */
    private function getPersistence()
    {
        return $this->getServiceLocator()->get(SimpleCache::SERVICE_ID);
    }

    /**
     * Initialize log storage
     *
     * @param $persistence
     * @return \common_report_Report
     */
    public function install()
    {
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Cache scheduler storage installed'));
    }

    /**
     * get array of json encoded jobs
     * @return array
     */
    private function getEncodedJobs()
    {
        try {
            $jobs = json_decode($this->getPersistence()->get(self::CACHE_KEY));
        } catch (InvalidArgumentException $e) {
            $jobs = [];
        }
        if ($jobs === null) {
            $jobs = [];
        }
        return $jobs;
    }

    /**
     * @return string
     */
    public function __toPhpCode()
    {
        return 'new ' . get_class($this) . '()';
    }
}
