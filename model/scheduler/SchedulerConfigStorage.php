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
use oat\taoNccer\model\registry\eventAnonymizer\AbstractEventAnonymizer;
use oat\taoScheduler\model\job\JobInterface;
use oat\taoScheduler\model\job\Job;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use oat\taoScheduler\model\SchedulerException;
use Psr\SimpleCache\InvalidArgumentException;
use common_ext_ExtensionsManager as ExtensionsManager;
use common_ext_Extension as Extension;

/**
 * Class SchedulerConfigStorage
 * Storage fetches scheduled jobs from installed extensions configuration
 *
 * @package oat\taoScheduler\model\scheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerConfigStorage implements SchedulerStorageInterface
{
    use ServiceLocatorAwareTrait;

    const CACHE_KEY = 'SchedulerJobsStorage';
    const MANIFEST_KEY = 'scheduledJobs';

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
        if ($this->exists($job)) {
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
        if (!$this->exists($job)) {
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
    private function exists(JobInterface $job)
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
     * @inheritDoc
     */
    public function refreshJobs(): bool
    {
        $this->getPersistence()->delete(self::CACHE_KEY);
        $extManager = $this->getServiceLocator()->get(ExtensionsManager::SERVICE_ID);
        /** @var Extension $extension */
        foreach ($extManager->getInstalledExtensions() as $extension) {
            $jobsConfigClass = $extension->getManifest()->getExtra()[self::MANIFEST_KEY] ?? null;
            if (!is_subclass_of($jobsConfigClass, JobsConfig::class)) {
                throw new SchedulerException(sprintf('Jobs config must extend %s interface', JobsConfig::class));
            }
            $jobsConfig = new $jobsConfigClass();
            foreach ($jobsConfig->getJobs() as $job) {
                $this->add($job);
            }
        }
        return true;
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
