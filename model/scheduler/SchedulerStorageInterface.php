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

use \oat\taoScheduler\model\job\JobInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Interface SchedulerStorageInterface
 * @package oat\taoScheduler\model\scheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
interface SchedulerStorageInterface extends ServiceLocatorAwareInterface
{

    /**
     * Add job to the storage
     * @param JobInterface $job
     * @return boolean
     */
    public function add(JobInterface $job);

    /**
     * Remove job from storage
     * @param JobInterface $job
     * @return boolean
     */
    public function remove(JobInterface $job);

    /**
     * @return JobInterface[]
     */
    public function getJobs();

    /**
     * @param $persistence
     * @return mixed
     */
    public static function install($persistence);
}