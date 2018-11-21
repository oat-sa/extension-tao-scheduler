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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA
 *
 */
namespace oat\taoScheduler\scripts\install;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\InstallAction;
use oat\tao\helpers\UserHelper;
use oat\taoScheduler\scripts\tools\QueueHeartbeat;
use oat\taoScheduler\model\scheduler\SchedulerServiceInterface;
use oat\tao\model\accessControl\AclProxy;
use oat\oatbox\user\User;
use common_report_Report as Report;

/**
 * Class ScheduleHeartbeatJob
 *
 * Schedule a job to put heartbeat tasks to the each configured task queue.
 *
 * param 1 - user uri who will be owner of the task in the task queue.
 * It's needed to be able to get all the tasks of that users from the Task Queue Rest Api.
 * param 2 - recurrence rule (@see https://tools.ietf.org/html/rfc5545#section-3.3.10) or Cron syntax
 * Default value - *\/30 * * * * (once per 30 minutes)
 *
 * Run example:
 * ```
 * sudo -u www-data php index.php '\oat\taoScheduler\scripts\install\ScheduleHeartbeatJob' 'http://sample/first.rdf#i1534774676184875' '* * * * *'
 * ```
 *
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ScheduleHeartbeatJob extends InstallAction
{
    use OntologyAwareTrait;

    /**
     * @param $params
     * @return Report
     * @throws \common_exception_InconsistentData
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        $user = $this->getUser($params);
        $rRule = $this->getRRule($params);
        if (isset($params[1])) {
            $rRule = $params[1];
        }
        if ($this->isJobExists()) {
            $report = Report::createFailure(__('Job already scheduled'));
        } else {
            $schedulerService = $this->getServiceManager()->get(SchedulerServiceInterface::class);
            $schedulerService->attach(
                $rRule,
                new \DateTime('now', new \DateTimeZone('utc')),
                QueueHeartbeat::class,
                [$user->getIdentifier()]
            );
            $report = Report::createSuccess(__('Job successfully scheduled'));
        }
        return $report;
    }

    /**
     * @param array $params
     * @return mixed|string
     */
    public function getRRule(array $params)
    {
        $rRule = '*/30 * * * *';
        if (isset($params[1])) {
            $rRule = $params[1];
        }
        return $rRule;
    }

    /**
     * @param array $params
     * @return User
     * @throws \common_exception_InconsistentData
     */
    private function getUser(array $params)
    {
        if (!isset($params[0]) || !\common_Utils::isUri($params[0])) {
            throw new \common_exception_InconsistentData(__('First parameter must be existing user\'s uri'));
        }

        $userResource = $this->getResource($params[0]);

        if (!$userResource->exists()) {
            throw new \common_exception_InconsistentData(__('User with given uri does not exist'));
        }

        $user = UserHelper::getUser($userResource->getUri());

        if (!AclProxy::hasAccess($user, \tao_actions_TaskQueueWebApi::class, 'getAll', [])) {
            throw new \common_exception_InconsistentData(__('User does not have access to the task queue rest API'));
        }

        return $user;
    }

    /**
     * Check if job already scheduled
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    private function isJobExists()
    {
        $schedulerService = $this->getServiceManager()->get(SchedulerServiceInterface::class);
        $jobs = $schedulerService->getJobs();
        foreach ($jobs as $job) {
            if ($job->getCallable() === QueueHeartbeat::class) {
                return true;
            }
        }
        return false;
    }
}
