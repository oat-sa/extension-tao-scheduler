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

namespace oat\taoScheduler\scripts;

use oat\taoScheduler\model\runner\JobRunnerService;
use oat\oatbox\extension\AbstractAction;
use common_report_Report as Report;
use DateInterval;
use DateTime;
use oat\oatbox\log\LoggerAwareTrait;

/**
 * Class JobRunner
 * @package oat\taoScheduler\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class JobRunner extends AbstractAction
{
    use LoggerAwareTrait;

    const DEFAULT_ITERATION_INTERVAL = 'PT60S';

    /** @var DateTime */
    private $from;

    /** @var DateInterval */
    private $interval;

    /** @var bool */
    private $shutdown = false;

    /**
     * @inheritdoc
     */
    public function __invoke($params = [])
    {
        $this->init($params);
        /** @var JobRunnerService $runnerService */
        $runnerService = $this->getServiceLocator()->get(JobRunnerService::SERVICE_ID);

        $from = clone($this->from);

        while ($this->isRunning()) {
            $to = new DateTime('now');
            $runnerService->run($from, $to);
            $from = clone($to);
            $from->add(new DateInterval('PT1S'));
            sleep($this->getSeconds($this->interval));
        }
    }

    /**
     * @return bool
     */
    private function isRunning()
    {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }

        if ($this->shutdown) {
            return false;
        }

        return true;
    }

    /**
     * @param DateInterval $interval
     * @return int
     */
    private function getSeconds(DateInterval $interval)
    {
        $date = new \DateTimeImmutable('now');
        $date2 = $date->add($interval);
        return $date2->getTimestamp() - $date->getTimestamp();
    }

    /**
     * @param array $params
     * @throws
     */
    private function init($params = [])
    {
        $now = time();
        if (isset($params[0]) and intval($params[0] > 0)) {
            $this->from = new DateTime('@'.$params[0]);
        } else {
            /** @var JobRunnerService $taoJobRunnerService */
            $taoJobRunnerService = $this->getServiceLocator()->get(JobRunnerService::SERVICE_ID);
            $lastLaunchPeriod = $taoJobRunnerService->getLastLaunchPeriod();
            if ($lastLaunchPeriod === null) {
                $this->from = new DateTime('@'.$now);
            } else {
                $this->from = $lastLaunchPeriod->getTo()->add(new DateInterval('PT1S'));
            }
        }

        if (isset($params[1])) {
            $this->interval = new \DateInterval($params[1]);
        } else {
            $this->interval = new \DateInterval(self::DEFAULT_ITERATION_INTERVAL);
        }

        $this->registerSigHandlers();
    }

    /**
     * Register signal handlers that a worker should respond to.
     *
     * TERM/INT/QUIT: Shutdown after the current job is finished then exit.
     */
    private function registerSigHandlers()
    {
        if (!function_exists('pcntl_signal')) {
            $this->logWarning('taoScheduler runner ran without pcntl');
            return;
        }

        declare(ticks = 1);

        pcntl_signal(SIGTERM, [$this, 'shutdown']);
        pcntl_signal(SIGINT, [$this, 'shutdown']);
        pcntl_signal(SIGQUIT, [$this, 'shutdown']);

        $this->logDebug('Finished setting up signal handlers');
    }

    /**
     * Set marker to shutdown after finishing current iteration
     */
    public function shutdown()
    {
        $this->logDebug('TERM/INT/QUIT received; shutting down gracefully...');
        $this->shutdown = true;
    }

}