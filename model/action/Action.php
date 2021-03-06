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

namespace oat\taoScheduler\model\action;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use oat\oatbox\action\Action as TaoAction;
use common_report_Report as Report;

/**
 * Class Action
 * @package Task
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class Action implements ActionInterface
{

    use ServiceLocatorAwareTrait;

    private $callback;
    private $params;
    private $startTime;

    /**
     * Action constructor.
     * @param $callback
     * @param mixed $params
     */
    public function __construct($callback, $params = null)
    {
        $this->callback = $callback;
        $this->params = $params;
    }

    /**
     * @return mixed|void
     */
    public function __invoke()
    {
        $callback = $this->callback;
        $params = $this->params;

        if (is_string($callback) && is_subclass_of($callback, TaoAction::class)) {
            /** @var TaoAction $callback */
            $callback = new $callback();
            if ($callback instanceof ServiceLocatorAwareInterface) {
                $callback->setServiceLocator($this->getServiceLocator());
            }
            $params = $this->params === null ? [null] : [$this->params];
        }

        if (is_array($callback) && count($callback) == 2) {
            list($key, $function) = $callback;
            if (is_string($key) && !class_exists($key) && $this->getServiceLocator()->has($key)) {
                $service = $this->getServiceLocator()->get($key);
                $callback = [$service, $function];
            }
        }

        $result = call_user_func_array($callback, $params);
        if ($result instanceof Report) {
            $result = json_encode($result);
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @inheritdoc
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @inheritdoc
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @inheritdoc
     */
    public function setStartTime(\DateTimeInterface $startTime)
    {
        $this->startTime = $startTime;
    }
}
