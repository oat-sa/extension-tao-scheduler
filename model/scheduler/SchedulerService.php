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
use Scheduler\Scheduler;
use Scheduler\Job\Job as SchedulerJob;
use oat\taoScheduler\model\action\Action;

/**
 * Class SchedulerService
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerService extends ConfigurableService implements SchedulerServiceInterface
{
    /**
     * @deprecated
     */
    const OPTION_JOBS = 'jobs';
    const OPTION_JOBS_STORAGE = 'jobs_storage';
    const OPTION_JOBS_STORAGE_PARAMS = 'jobs_storage_params';

    use LoggerAwareTrait;

    /** @var SchedulerStorageInterface */
    private $storage;

    /**
     * @inheritdoc
     */
    public function attach($rRule, DateTimeInterface $startTime, $callback, $params = [])
    {
        $job = new Job($rRule, $startTime, $callback, $params);
        return $this->getStorage()->add($job);
    }

    /**
     * @inheritdoc
     */
    public function detach($rRule, DateTimeInterface $startTime, $callback, $params = [])
    {
        $jobToRemove = new Job($rRule, $startTime, $callback, $params);
        return $this->getStorage()->remove($jobToRemove);
    }

    /**
     * Return array of all the scheduled jobs
     * @return Job[]
     */
    public function getJobs()
    {
        return $this->getStorage()->getJobs();
    }

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @return \oat\taoScheduler\model\action\ActionInterface[]
     */
    public function getScheduledActions(DateTimeInterface $from, DateTimeInterface $to)
    {
        $result = [];

        /** @var Job[] $taoJobs */
        $taoJobs = $this->getJobs();
        $scheduler = new Scheduler();
        foreach ($taoJobs as $taoJob) {
            $action = $this->getAction($taoJob->getCallable(), $taoJob->getParams());
            $schedulerJob = SchedulerJob::createFromString($taoJob->getRrule(), $taoJob->getStartTime(), $action);
            $scheduler->addJob($schedulerJob);
        }
        $scheduledActions = $scheduler->getIterator($from, $to, true);

        foreach ($scheduledActions as $scheduledAction) {
            /** @var Action $action */
            $action = clone($scheduledAction->getJob()->getCallable());
            $action->setStartTime($scheduledAction->getTime());
            $result[] = $action;
        }

        return $result;
    }

    /**
     * @param $callable
     * @param $params
     * @return Action
     */
    private function getAction($callable, $params)
    {
        $action = new Action($callable, $params);
        $action->setServiceLocator($this->getServiceLocator());
        return $action;
    }

    /**
     * @return SchedulerStorageInterface
     */
    private function getStorage()
    {
        if ($this->storage === null) {
            $storageClass = $this->getOption(self::OPTION_JOBS_STORAGE);
            $storageParams = $this->getOption(self::OPTION_JOBS_STORAGE_PARAMS);
            $this->storage = new $storageClass(...$storageParams);
            $this->storage->setServiceLocator($this->getServiceLocator());
        }
        return $this->storage;
    }
}
