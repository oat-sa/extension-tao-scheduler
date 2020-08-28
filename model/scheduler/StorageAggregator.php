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


use oat\taoScheduler\model\job\JobInterface;
use oat\taoScheduler\model\SchedulerException;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Class StorageAggregator
 * @package oat\taoScheduler\model\scheduler
 */
class StorageAggregator implements SchedulerStorageInterface
{
    use ServiceLocatorAwareTrait;
    /**
     * @var SchedulerStorageInterface
     */
    private $permanentStorage;

    /**
     * @var SchedulerStorageInterface
     */
    private $cacheStorage;

    /**
     * StorageAggregator constructor.
     * @param SchedulerStorageInterface $permanentStorage
     * @param SchedulerStorageInterface $cacheStorage
     */
    public function __construct(SchedulerStorageInterface $permanentStorage, SchedulerStorageInterface $cacheStorage)
    {
        $this->permanentStorage = $permanentStorage;
        $this->cacheStorage = $cacheStorage;
    }

    /**
     * @inheritDoc
     */
    public function add(JobInterface $job, $permanent = true)
    {
        if ($permanent) {
            return $this->permanentStorage->add($job);
        } else {
            return $this->cacheStorage->add($job);
        }
    }

    /**
     * @inheritDoc
     */
    public function remove(JobInterface $job)
    {
        try {
            $result = $this->cacheStorage->remove($job);
        } catch (SchedulerException $e) {
            try {
                $result =  $this->permanentStorage->remove($job);
            } catch (SchedulerException $e) {
                throw new SchedulerException('Job does not exist');
            }
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getJobs()
    {
        return array_merge($this->permanentStorage->getJobs(), $this->cacheStorage->getJobs());
    }

    /**
     * Initialize log storage
     *
     * @return \common_report_Report
     */
    public function install()
    {
        $this->permanentStorage->install();
        $this->cacheStorage->install();
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Scheduler storage aggregator installed'));
    }
}