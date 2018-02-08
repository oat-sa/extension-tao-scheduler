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

use \DateTimeInterface;
use oat\oatbox\PhpSerializable;
use oat\taoScheduler\model\SchedulerException;

/**
 * Class Job
 * @package oat\taoScheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class Job implements JobInterface
{

    /**
     * @var string
     */
    private $rRule;

    /**
     * @var DateTimeInterface
     */
    private $startTime;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @var array
     */
    private $params;

    /**
     * Schedule an event
     *
     * @param string $rRule Recurrence rule (@see https://tools.ietf.org/html/rfc5545#section-3.3.10)
     * @param DateTimeInterface $startTime
     * @param $callback Callback to be executed.
     *                  Also can be an array with tao service identifier and method name (e.g. ['taoExt/MyService', 'doSomething'])
     * @param array $params Parameters to be passed to callback
     */
    public function __construct($rRule, DateTimeInterface $startTime, $callback, $params = [])
    {
        $this->rRule = $rRule;
        $this->startTime = $startTime;
        $this->callback = $callback;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getRRule()
    {
        return $this->rRule;
    }

    /**
     * @return callable
     */
    public function getCallable()
    {
        return $this->callback;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @inheritdoc
     */
    public function __toPhpCode()
    {
        $callbackPhpCode = $this->getPhpCode($this->getCallable());
        return '[\'' . $this->getRRule() . '\', '
            . $this->getStartTime()->getTimestamp() . ','
            . $callbackPhpCode . ','
            . \common_Utils::toPHPVariableString($this->getParams())
        . ']';
    }

    /**
     * @param $callback
     * @return null|string
     * @throws SchedulerException
     * @throws \common_exception_Error
     */
    private function getPhpCode($callback)
    {
        $result = null;
        if (is_scalar($callback)) {
            $result = \common_Utils::toPHPVariableString($callback);
        } if (is_array($callback)) {
            if (is_scalar($callback[0])) {
                $result = \common_Utils::toPHPVariableString($callback);
            }

            if (is_object($callback[0]) && ($callback[0] instanceof PhpSerializable)) {
                $result = $callback;
                $result[0] = $callback[0]->__toPhpCode();
                $result = \common_Utils::toPHPVariableString($result);
            }
        }

        if ($result === null) {
            throw new SchedulerException('Callback cannot be converted to PHP code');
        }

        return $result;
    }

}