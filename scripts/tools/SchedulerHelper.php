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

namespace oat\taoScheduler\scripts\tools;

use oat\oatbox\extension\AbstractAction;
use DateTime;
use oat\oatbox\log\LoggerAwareTrait;
use oat\taoScheduler\model\scheduler\SchedulerServiceInterface as TaoScheduler;
use common_report_Report as Report;
use oat\taoScheduler\model\runner\JobRunnerService;
use oat\taoScheduler\model\SchedulerException;

/**
 * Class SchedulerHelper
 *
 *
 * @package oat\taoScheduler\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerHelper extends AbstractAction
{
    use LoggerAwareTrait;

    private static $validMethods = ['show', 'removeExpiredJobs'];

    /** @var array */
    private $params;

    /** @var string */
    private $method;

    /**
     * @inheritdoc
     */
    public function __invoke($params = [])
    {
        $this->init($params);
        return call_user_func_array([$this, $this->method], $this->params);
    }

    /**
     * @param array $params
     * @throws
     */
    private function init($params = [])
    {
        if (!isset($params[0]) || !in_array($params[0], self::$validMethods)) {
            throw new \InvalidArgumentException('Wrong method parameter. Available methods: ' . implode(',', self::$validMethods));
        }
        $this->method = $params[0];
        $this->params = array_slice($params, 1);
    }

    /**
     * Show scheduled tasks
     *
     * Run example:
     * ```
     * sudo php index.php '\oat\taoScheduler\scripts\tools\SchedulerHelper' show 1519890883 1519899883
     * ```
     *
     * @param $from
     * @param $to
     * @return Report
     * @throws
     */
    private function show($from = null, $to = null)
    {
        $from = $this->getDateTime($from);
        $to = $this->getDateTime($to);

        /** @var TaoScheduler $taoSchedulerService */
        $taoSchedulerService = $this->getServiceLocator()->get(TaoScheduler::SERVICE_ID);
        $actions = $taoSchedulerService->getScheduledActions($from, $to);
        $report = new Report(Report::TYPE_INFO, 'Tasks scheduled from ' . $from->format(DateTime::ISO8601) . ' to ' . $to->format(DateTime::ISO8601));
        $count = 0;
        foreach ($actions as $action) {
            $count++;
            $report->add(
                new Report(
                    Report::TYPE_INFO,
                    'Task #' . $count . ':' . PHP_EOL .
                    '  Execution Time: ' . $action->getStartTime()->format(DateTime::ISO8601) . PHP_EOL .
                    '  Callback: ' . \common_Utils::toPHPVariableString($action->getCallback()) . PHP_EOL .
                    '  Params: ' . \common_Utils::toPHPVariableString($action->getParams()) . PHP_EOL
                )
            );
        }

        $report->add(new Report(Report::TYPE_INFO, $count . ' tasks scheduled'));
        return $report;
    }

    /**
     * Remove expired jobs from storage.
     *
     * if `$expiredAfterTime` parameter is not given then the last launch time of job runner will be used,
     * so all jobs which will not be executed by JobRunner will be removed from scheduler storage.
     *
     * Run example:
     * ```
     * //remove jobs from  scheduler storage which will not be executed anymore after 1519890884:
     * sudo php index.php '\oat\taoScheduler\scripts\tools\SchedulerHelper' removeExpiredJobs false 1519890884
     * ```
     *
     * @param $dryRun
     * @param integer $expiredAfterTime timestamp
     * @return Report
     * @throws \common_exception_Error
     */
    private function removeExpiredJobs($dryRun, $expiredAfterTime = null)
    {
        $dryRun = filter_var($dryRun, FILTER_VALIDATE_BOOLEAN);
        $taoSchedulerService = $this->getServiceLocator()->get(TaoScheduler::SERVICE_ID);

        if ($expiredAfterTime === null) {
            /** @var JobRunnerService $jobRunner */
            $jobRunner = $this->getServiceLocator()->get(JobRunnerService::SERVICE_ID);
            /** @var TaoScheduler $taoSchedulerService */
            $lastLaunch = $jobRunner->getLastLaunchPeriod();
            if ($lastLaunch === null) {
                return Report::createFailure('Job runner does not have last launch period. Impossible to determine expired tasks.');
            }
            $expiredAfterTime = new DateTime('@'.($lastLaunch->getFrom()->getTimestamp()-1));
        } else {
            $expiredAfterTime = new DateTime('@'.$expiredAfterTime);
        }
        $report = new Report(Report::TYPE_INFO, 'Search for tasks expired after ' . $expiredAfterTime->format(DateTime::ISO8601));
        $jobs = $taoSchedulerService->getJobs();
        $found = false;
        foreach ($jobs as $job) {
            if ($taoSchedulerService->getNextRecurrence($job, $expiredAfterTime) !== null) {
                continue;
            }
            $found = true;
            $removeReport = new Report(Report::TYPE_WARNING, 'Job to be removed:');
            $removeReport->add(
                new Report(
                    Report::TYPE_INFO,
                    '- RRule: ' . $job->getRRule() . PHP_EOL .
                    '    - StartTime: ' . $job->getStartTime()->format(DateTime::ISO8601) . PHP_EOL .
                    '    - Callable: ' . \common_Utils::toPHPVariableString($job->getCallable()) . PHP_EOL .
                    '    - Params: ' . \common_Utils::toPHPVariableString($job->getParams())
                )
            );
            if (!$dryRun) {
                try {
                    $taoSchedulerService->detach($job->getRRule(), $job->getStartTime(), $job->getCallable(), $job->getParams());
                    $removeReport->add(Report::createSuccess('Job successfully removed'));
                } catch (SchedulerException $e) {
                    $removeReport->add(Report::createFailure('Cannot remove job'));
                }
            }
            $report->add($removeReport);
        }
        if (!$found) {
            $report->add(Report::createInfo('No expired jobs found.'));
        }
        return $report;
    }

    /**
     * Get DateTime instance from timestamp.
     * If null given then current time will be used.
     * Timezonde is allways UTC
     * @param $time
     * @return DateTime
     */
    private function getDateTime($time)
    {
        $utcTz = new \DateTimeZone('UTC');
        if ($time === null) {
            $time = new DateTime('now', $utcTz);
        } else {
            $time = new DateTime('@'.$time, $utcTz);
        }
        return $time;
    }
}
