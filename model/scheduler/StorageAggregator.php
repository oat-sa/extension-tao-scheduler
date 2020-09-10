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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoScheduler\model\scheduler;


use oat\taoScheduler\model\job\JobInterface;
use oat\taoScheduler\model\SchedulerException;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class StorageAggregator
 * @package oat\taoScheduler\model\scheduler
 */
class StorageAggregator implements SchedulerStorageInterface
{
    use ServiceLocatorAwareTrait {
        setServiceLocator as traitSetServiceLocator;
    }
    /**
     * @var SchedulerStorageInterface
     */
    private $dynamicStorage;

    /**
     * @var SchedulerStorageInterface
     */
    private $configStorage;

    /**
     * StorageAggregator constructor.
     * @param SchedulerStorageInterface $dynamicStorage
     * @param SchedulerStorageInterface $configStorage
     */
    public function __construct(SchedulerStorageInterface $dynamicStorage, SchedulerStorageInterface $configStorage)
    {
        $this->dynamicStorage = $dynamicStorage;
        $this->configStorage = $configStorage;
    }

    /**
     * @inheritDoc
     */
    public function add(JobInterface $job, $dynamic = true)
    {
        if ($dynamic) {
            return $this->dynamicStorage->add($job);
        } else {
            return $this->configStorage->add($job);
        }
    }

    /**
     * @inheritDoc
     */
    public function remove(JobInterface $job)
    {
        try {
            $result = $this->dynamicStorage->remove($job);
        } catch (SchedulerException $e) {
            try {
                $result = $this->configStorage->remove($job);
            } catch (SchedulerException $e) {
                throw new SchedulerException('Job does not exist');
            }
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getJobs()
    {
        return array_merge($this->dynamicStorage->getJobs(), $this->configStorage->getJobs());
    }

    /**
     * Initialize log storage
     *
     * @return \common_report_Report
     */
    public function install()
    {
        $this->dynamicStorage->setServiceLocator($this->getServiceLocator());
        $this->configStorage->setServiceLocator($this->getServiceLocator());
        $this->dynamicStorage->install();
        $this->configStorage->install();
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Scheduler storage aggregator installed'));
    }

    /**
     * @return bool
     */
    public function refreshJobs(): bool
    {
        return $this->dynamicStorage->refreshJobs() && $this->configStorage->refreshJobs();
    }

    /**
     * @inheritDoc
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->traitSetServiceLocator($serviceLocator);
        $this->dynamicStorage->setServiceLocator($serviceLocator);
        $this->configStorage->setServiceLocator($serviceLocator);
    }

    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\PhpSerializable::__toPhpCode()
     */
    public function __toPhpCode()
    {
        return 'new ' . get_class($this) . '(' . PHP_EOL
            . \common_Utils::toHumanReadablePhpString($this->dynamicStorage) . ', ' . PHP_EOL
            . \common_Utils::toHumanReadablePhpString($this->configStorage) . PHP_EOL
            . ')';
    }
}