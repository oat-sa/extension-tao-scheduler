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

use oat\dtms\DateTime;
use oat\taoScheduler\model\scheduler\SchedulerService;
use oat\oatbox\service\ServiceManager;
use oat\taoScheduler\model\action\ActionInterface;

/**
 * Class SchedulerService
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerServiceTest extends \PHPUnit_Framework_TestCase
{

    protected function tearDown()
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
        $scheduler->attach('FREQ=MONTHLY;COUNT=5', new \DateTime('@'.$time), 'foo/bar');

        $jobs = $scheduler->getOption(SchedulerService::OPTION_JOBS);
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

        $scheduler->attach('FREQ=MONTHLY;COUNT=5', new \DateTime('@'.$time), 'foo/bar');
        $jobs = $scheduler->getOption(SchedulerService::OPTION_JOBS);
        $this->assertEquals(1, count($jobs));

        $scheduler->detach('FREQ=MONTHLY;COUNT=5', new \DateTime('@'.$time), 'foo/bar');
        $jobs = $scheduler->getOption(SchedulerService::OPTION_JOBS);
        $this->assertEquals(0, count($jobs));
    }

    public function testGetJobs()
    {
        $scheduler = $this->getInstance();
        $time = time();

        $this->assertEquals(0, count($scheduler->getJobs()));

        $scheduler->attach('FREQ=MONTHLY;COUNT=5', new \DateTime('@'.$time), 'foo/bar');
        $scheduler->attach('FREQ=MONTHLY;COUNT=1', new \DateTime('@'.$time), 'foo/baz');

        $this->assertEquals(2, count($scheduler->getJobs()));

    }

    public function testGetScheduledActions()
    {
        $scheduler = $this->getInstance();
        $dt = new DateTime('now');
        $dt->setTime($dt->format('G'), $dt->format('i'), 0, 0);

        $callbackMock = $this->getMockBuilder('\stdClass')
            ->setMethods(['myCallBack'])
            ->getMock();
        $callbackMock->expects($this->any())
            ->method('myCallBack')
            ->with($this->equalTo('foo'));
        $callbackMock->method('myCallBack')->will($this->returnValue(true));


        $scheduler->attach('FREQ=MINUTELY;COUNT=5', $dt, [$callbackMock, 'myCallBack']);
        $scheduler->attach('* * * * *', $dt, [$callbackMock, 'myCallBack']);

        $actions = $scheduler->getScheduledActions($dt, new DateTime('@'.($dt->getTimestamp()+(2*60))));

        $this->assertEquals(6, count($actions));

        foreach ($actions as $action) {
            $this->assertTrue($action instanceof ActionInterface);
        }
    }

    /**
     * @return \oat\taoScheduler\model\scheduler\SchedulerService
     */
    private function getInstance()
    {
        $scheduler = new SchedulerService([]);
        $params = [
            'dir' => __DIR__ . DIRECTORY_SEPARATOR . 'cache',
            'humanReadable' => true
        ];
        $driver = new \common_persistence_PhpFileDriver();
        $driver->connect('test', $params);
        $config = new \common_persistence_KeyValuePersistence([], $driver);
        $serviceManager = new ServiceManager($config);
        $scheduler->setServiceLocator($serviceManager);
        return $scheduler;
    }

}