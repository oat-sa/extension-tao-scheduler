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

namespace oat\taoScheduler\model\job;

use oat\oatbox\PhpSerializable;

/**
 * Class Job
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
interface JobInterface extends PhpSerializable, \JsonSerializable
{

    /**
     * @return string
     */
    public function getRRule();

    /**
     * @return callable
     */
    public function getCallable();

    /**
     * @return \DateTimeInterface
     */
    public function getStartTime();

    /**
     * @return array
     */
    public function getParams();

    /**
     * Check if jobs are equal
     * @param JobInterface $job
     * @return bool
     */
    public function equals(JobInterface $job):bool;

    /**
     * Restore Job from json
     * @param string $json
     * @return JobInterface
     */
    public static function restore($json);
}