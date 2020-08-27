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
     * @var int
     * Job of this type is part of configuration of application.
     * Must be scheduled during installation of some extension.
     * For example: termination of expired test sessions.
     */
    public const TYPE_CONFIG = 0;

    /**
     * @var int
     * Job of this type can be dynamically added during program execution.
     * For example: resend results in 5 minutes in case if previous submission was failed.
     */
    public const TYPE_DYNAMIC = 1;

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
     * @return mixed
     */
    public function getType();

    /**
     * Restore Job from json
     * @param string $json
     * @return JobInterface
     */
    public static function restore($json);
}