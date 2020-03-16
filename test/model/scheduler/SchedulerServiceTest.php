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
namespace oat\taoScheduler\test\model\scheduler;

use DateTime;
use oat\taoScheduler\model\scheduler\SchedulerService;
use oat\oatbox\service\ServiceManager;
use oat\taoScheduler\model\action\ActionInterface;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\oatbox\action\Action as TaoAction;

/**
 * Class SchedulerService
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerServiceTest extends TaoPhpUnitTestRunner
{

    protected function tearDown(): void
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'cache';
        if (file_exists($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                (is_dir($dir . DIRECTORY_SEPARATOR . $file) && !is_link($dir)) ?
                    delTree($dir . DIRECTORY_SEPARATOR . $file) :
                    unlink($dir . DIRECTORY_SEPARATOR . $file);
            }
            return rmdir($dir);
        }
    }

    public function testAttach()
    {
        $time = time();
        $scheduler = $this->getInstance();
        $scheduler->attach('FREQ=MONTHLY;COUNT=5', new \DateTime('@' . $time), 'foo/bar');

        $jobs = $scheduler->getJobs();
        $this->assertEquals(1, count($jobs));
        $this->assertTrue($jobs[0] instanceof \oat\taoScheduler\model\job\Job);
        $this->assertEquals('FREQ=MONTHLY;COUNT=5', $jobs[0]->getRRule());
        $this->assertEquals($time, $jobs[0]->getStartTime()->getTimestamp());
        $this->assertEquals('foo/bar', $jobs[0]->getCallable());
    }

    public function testDetach()
    {
        $time = time();
        $scheduler = $this->getInstance();

        $scheduler->attach('FREQ=MONTHLY;COUNT=5', new \DateTime('@' . $time), 'foo/bar');
        $jobs = $scheduler->getJobs();
        $this->assertEquals(1, count($jobs));

        $scheduler->detach('FREQ=MONTHLY;COUNT=5', new \DateTime('@' . $time), 'foo/bar');
        $jobs = $scheduler->getJobs();
        $this->assertEquals(0, count($jobs));
    }

    public function testGetJobs()
    {
        $scheduler = $this->getInstance();
        $time = time();

        $this->assertEquals(0, count($scheduler->getJobs()));

        $scheduler->attach('FREQ=MONTHLY;COUNT=5', new \DateTime('@' . $time), 'foo/bar');
        $scheduler->attach('FREQ=MONTHLY;COUNT=1', new \DateTime('@' . $time), 'foo/baz');

        $this->assertEquals(2, count($scheduler->getJobs()));
    }

    public function testGetScheduledActions()
    {
        $scheduler = $this->getInstance();
        $dt = new DateTime('now');
        $dt->setTime($dt->format('G'), $dt->format('i'), 0, 0);

        $scheduler->attach('FREQ=MINUTELY;COUNT=5', $dt, ['\DateTime', 'createFromFormat'], ['j-M-Y', '22-Feb-2018']);
        $scheduler->attach('* * * * *', $dt, ['\DateTime', 'createFromFormat'], ['j-M-Y', '22-Feb-2018']);
        $scheduler->attach('* * * * *', $dt, ActionMock::class);

        $actions = $scheduler->getScheduledActions($dt, new DateTime('@' . ($dt->getTimestamp() + (2 * 60))));

        $this->assertEquals(9, count($actions));

        foreach ($actions as $action) {
            $this->assertTrue($action instanceof ActionInterface);
        }
    }

    public function testGetRecurrences()
    {
        $scheduler = $this->getInstance();

        $dt = new DateTime('2018-03-01T21:00:00');
        $dtMinus5Minutes = new DateTime('2018-03-01T20:55:00');
        $dtMinus10Minutes = new DateTime('2018-03-01T20:50:00');
        $dtPlus5Minutes = new DateTime('2018-03-01T21:05:00');

        $scheduler->attach('* * * * *', $dt, 'foo/bar');
        $job = $scheduler->getJobs()[0];

        $recurrences = $scheduler->getRecurrences($job, $dtMinus10Minutes, $dtMinus5Minutes);
        $this->assertEquals([], $recurrences);

        $recurrences = $scheduler->getRecurrences($job, $dtMinus5Minutes, $dt);
        $this->assertEquals(1, count($recurrences));
        $this->assertEquals($dt, $recurrences[0]);

        $recurrences = $scheduler->getRecurrences($job, $dt, $dtPlus5Minutes);
        $this->assertEquals(6, count($recurrences));
        $this->assertEquals($dt, $recurrences[0]);
        $this->assertEquals($dt->getTimestamp()+60, $recurrences[1]->getTimestamp());
        $this->assertEquals($dtPlus5Minutes, $recurrences[5]);


        $scheduler->attach('FREQ=MINUTELY;COUNT=3;', $dt, 'foo/bar');
        $job = $scheduler->getJobs()[1];

        $recurrences = $scheduler->getRecurrences($job, $dtMinus10Minutes, $dtMinus5Minutes);
        $this->assertEquals([], $recurrences);

        $recurrences = $scheduler->getRecurrences($job, $dtMinus5Minutes, $dt);
        $this->assertEquals(1, count($recurrences));
        $this->assertEquals($dt, $recurrences[0]);

        $recurrences = $scheduler->getRecurrences($job, $dt, $dtPlus5Minutes);
        $this->assertEquals(3, count($recurrences));
        $this->assertEquals($dt, $recurrences[0]);
        $this->assertEquals($dt->getTimestamp()+60, $recurrences[1]->getTimestamp());
        $this->assertEquals($dt->getTimestamp()+120, $recurrences[2]->getTimestamp());
    }

    public function testGetNextRecurrence()
    {
        $scheduler = $this->getInstance();

        $dt = new DateTime('2018-03-01T21:00:00');
        $dtMinus1Second = new DateTime('2018-03-01T20:59:59');
        $dtPlus1Second = new DateTime('2018-03-01T21:00:01');
        $dtPlus120Second = new DateTime('2018-03-01T21:02:00');
        $dtPlus121Second = new DateTime('2018-03-01T21:02:01');
        $scheduler->attach('* * * * *', $dt, 'foo/bar');

        $job = $scheduler->getJobs()[0];

        $this->assertEquals($dt->getTimestamp(), $scheduler->getNextRecurrence($job, $dt)->getTimestamp());
        $this->assertEquals($dt->getTimestamp(), $scheduler->getNextRecurrence($job, $dtMinus1Second)->getTimestamp());
        $this->assertEquals($dt->getTimestamp()+60, $scheduler->getNextRecurrence($job, $dtPlus1Second)->getTimestamp());

        $scheduler->attach('FREQ=MINUTELY;COUNT=3', $dt, 'foo/bar');

        $job = $scheduler->getJobs()[1];

        $this->assertEquals($dt->getTimestamp(), $scheduler->getNextRecurrence($job, $dt)->getTimestamp());
        $this->assertEquals($dt->getTimestamp(), $scheduler->getNextRecurrence($job, $dtMinus1Second)->getTimestamp());
        $this->assertEquals($dt->getTimestamp()+60, $scheduler->getNextRecurrence($job, $dtPlus1Second)->getTimestamp());
        $this->assertEquals($dt->getTimestamp()+120, $scheduler->getNextRecurrence($job, $dtPlus120Second)->getTimestamp());
        $this->assertEquals(null, $scheduler->getNextRecurrence($job, $dtPlus121Second));
    }

    /**
     * @return \oat\taoScheduler\model\scheduler\SchedulerService
     * @throws
     */
    private function getInstance()
    {
        $scheduler = new SchedulerService([
            SchedulerService::OPTION_JOBS_STORAGE => SchedulerRdsStorage::class,
            SchedulerService::OPTION_JOBS_STORAGE_PARAMS => ['test_scheduler'],

        ]);

        $persistenceManager = $this->getSqlMock('test_scheduler');
        $persistence = $persistenceManager->getPersistenceById('test_scheduler');
        SchedulerRdsStorage::install($persistence);
        $config = new \common_persistence_KeyValuePersistence([], new \common_persistence_InMemoryKvDriver());
        $config->set(\common_persistence_Manager::SERVICE_ID, $persistenceManager);
        $serviceManager = new ServiceManager($config);
        $scheduler->setServiceLocator($serviceManager);
        return $scheduler;
    }
}


class ActionMock implements TaoAction
{
    public function __invoke($params)
    {
        return true;
    }
}