<?php

namespace oat\taoScheduler\model\action;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use DateTimeInterface;

/**
 * Class Action
 * @package Task
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class Action implements ActionInterface, ServiceLocatorAwareInterface
{

    use ServiceLocatorAwareTrait;

    private $callback;
    private $params;
    private $startTime;

    /**
     * Action constructor.
     * @param DateTimeInterface $startTime
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
        if (is_array($callback) && count($callback) == 2) {
            list($key, $function) = $callback;
            if (is_string($key) && !class_exists($key) && $this->getServiceLocator()->has($key)) {
                $service = $this->getServiceLocator()->get($key);
                $callback = [$service, $function];
            }
        }
        return call_user_func_array($callback, $this->params);
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