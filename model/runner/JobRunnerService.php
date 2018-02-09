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

namespace oat\taoScheduler\model\runner;

use oat\oatbox\service\ConfigurableService;
use oat\oatbox\log\LoggerAwareTrait;
use oat\taoScheduler\model\job\JobInterface;
use oat\taoScheduler\model\scheduler\SchedulerServiceInterface as TaoScheduler;
use oat\taoScheduler\model\job\JobInterface as TaoJob;
use common_report_Report;
use Scheduler\JobRunner\JobRunner;
use Scheduler\Job\RRule;
use Scheduler\Job\Job;
use Scheduler\Action\Report;
use Scheduler\Scheduler;
use DateTimeInterface;
use DateTime;

/**
 * Class JobRunner
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class JobRunnerService extends ConfigurableService
{
    use LoggerAwareTrait;

    const SERVICE_ID = 'taoScheduler/JobRunnerService';
    const OPTION_PERSISTENCE = 'persistence';
    const PERIOD_KEY = 'taoScheduler:lastLaunchPeriod';

    private $report;

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @return common_report_Report
     * @throws
     */
    public function run(DateTimeInterface $from, DateTimeInterface $to)
    {
        $this->updateLastLaunchPeriod($from, $to);
        $this->logInfo('Run tasks scheduled from ' . $from->format(DateTime::ISO8601) . ' to ' . $to->format(DateTime::ISO8601));
        $this->report = common_report_Report::createInfo(
            'Run tasks scheduled from ' . $from->format(DateTime::ISO8601) . ' to ' . $to->format(DateTime::ISO8601)
        );

        /** @var TaoScheduler $scheduler */
        $taoSchedulerService = $this->getServiceLocator()->get(TaoScheduler::SERVICE_ID);
        /** @var TaoJob[] $taoJobs */
        $taoJobs = $taoSchedulerService->getJobs();
        $scheduler = new Scheduler();
        foreach ($taoJobs as $taoJob) {
            $internalJob = new Job(
                new RRule($taoJob->getRrule(), $taoJob->getStartTime()),
                $this->getCallback($taoJob)
            );
            $scheduler->addJob($internalJob);
        }
        $jobRunner = new JobRunner();
        $results = $jobRunner->run($scheduler, $from, $to, true);
        $this->processResults($results);

        return $this->report;
    }

    /**
     * @return mixed|JobRunnerPeriod
     */
    public function getLastLaunchPeriod()
    {
        $serializedPeriod = $this->getPersistence()->get(self::PERIOD_KEY);
        $result = null;
        if ($serializedPeriod) {
            $result = unserialize($serializedPeriod);
        }
        return $result;
    }

    /**
     * @param Report[] $results
     */
    private function processResults(array $results = [])
    {
        foreach ($results as $result) {
            if ($result->getResult() instanceof common_report_Report) {
                $report = $result->getResult();
            } else  {
                $report = new common_report_Report(
                    $result->getType() === Report::TYPE_SUCCESS ? common_report_Report::TYPE_SUCCESS : common_report_Report::TYPE_ERROR,
                    is_string($result->getResult()) ? $result->getResult() : json_encode($result->getResult())
                );
            }
            $this->report->add($report);
            $this->logInfo($report->getMessage());
        }
    }

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @throws \common_Exception
     */
    private function updateLastLaunchPeriod(DateTimeInterface $from, DateTimeInterface $to)
    {
        $period = new JobRunnerPeriod($from, $to);
        $this->getPersistence()->set(self::PERIOD_KEY, serialize($period));
    }

    /**
     * @param JobInterface $job
     * @return array|callable
     */
    private function getCallback(JobInterface $job)
    {
        $callback = $job->getCallable();
        if (is_array($callback) && count($callback) == 2) {
            list($key, $function) = $callback;
            if (is_string($key) && !class_exists($key) && $this->getServiceLocator()->has($key)) {
                $service = $this->getServiceLocator()->get($key);
                $callback = [$service, $function];
            }
        }
        return function () use($callback, $job) {
            return call_user_func_array($callback, $job->getParams());
        };
    }

    /**
     * @return \common_persistence_KeyValuePersistence
     * @throws
     */
    private function getPersistence()
    {
        if (!$this->hasOption(self::OPTION_PERSISTENCE)) {
            throw new \InvalidArgumentException('Persistence for ' . self::SERVICE_ID . ' is not configured');
        }
        $persistenceId = $this->getOption(self::OPTION_PERSISTENCE);
        return $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById($persistenceId);
    }

}