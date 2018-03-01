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

/**
 * Class JobRunner
 *
 *
 * @package oat\taoScheduler\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerHelper extends AbstractAction
{
    use LoggerAwareTrait;

    static $validMethods = ['show'];

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
