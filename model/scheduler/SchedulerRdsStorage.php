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

use oat\taoScheduler\model\job\JobInterface;
use oat\taoScheduler\model\job\Job;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use oat\taoScheduler\model\SchedulerException;
use Doctrine\DBAL\Schema\SchemaException;

/**
 * Class SchedulerRdsStorage
 * @package oat\taoScheduler\model\scheduler
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class SchedulerRdsStorage implements SchedulerStorageInterface
{

    use ServiceLocatorAwareTrait;

    private $persistenceId;

    const TABLE_NAME = 'scheduler_jobs';
    const COLUMN_JOB = 'job';

    /**
     * SchedulerStorage constructor.
     * @param $persistenceId
     */
    public function __construct($persistenceId)
    {
        $this->persistenceId = $persistenceId;
    }

    /**
     * @inheritdoc
     * @throws SchedulerException if job already exists
     */
    public function add(JobInterface $job)
    {
        $json = json_encode($job);
        $data = [
            self::COLUMN_JOB => $json
        ];
        if ($this->isExists($job)) {
            throw new SchedulerException('Job already exists');
        }
        return $this->getPersistence()->insert(self::TABLE_NAME, $data) === 1;
    }

    /**
     * @inheritdoc
     */
    public function remove(JobInterface $job)
    {
        $json = json_encode($job);
        if (!$this->isExists($job)) {
            throw new SchedulerException('Job does not exist');
        }
        $queryBuilder = $this->getPersistence()->getPlatForm()->getQueryBuilder();
        $queryBuilder->delete(self::TABLE_NAME);
        $queryBuilder->where(self::COLUMN_JOB . ' = ?');
        $queryBuilder->setParameters([$json]);
        $stmt = $this->getPersistence()->query($queryBuilder->getSQL(), $queryBuilder->getParameters());
        return $stmt->execute();
    }

    /**
     * @inheritdoc
     */
    public function getJobs()
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('*');
        $result = [];
        $stmt = $this->getPersistence()->query($queryBuilder->getSQL(), $queryBuilder->getParameters());
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($data as $job) {
            //todo: retrieve jobs from factory
            $result[] = Job::restore($job[self::COLUMN_JOB]);
        }
        return $result;
    }

    /**
     * Check if job exists in the storage
     * @param JobInterface $job
     * @return bool
     */
    private function isExists(JobInterface $job)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->where(self::COLUMN_JOB . ' = ?');
        $queryBuilder->setParameters([json_encode($job)]);

        $stmt = $this->getPersistence()->query($queryBuilder->getSQL(), $queryBuilder->getParameters());
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return !empty($data);
    }

    /**
     * @return \common_persistence_SqlPersistence
     * @throws
     */
    private function getPersistence()
    {
        $persistenceManager = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID);
        return $persistenceManager->getPersistenceById($this->persistenceId);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws
     */
    private function getQueryBuilder()
    {
        return $this->getPersistence()->getPlatForm()->getQueryBuilder()->from(self::TABLE_NAME, 'r');
    }

    /**
     * Initialize log storage
     *
     * @param \common_persistence_Persistence $persistence
     * @return \common_report_Report
     */
    public static function install($persistence)
    {
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $table = $schema->createTable(self::TABLE_NAME);
            $table->addOption('engine', 'InnoDB');
            $table->addColumn(static::COLUMN_JOB, "text", ["notnull" => true]);
        } catch(SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }

        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);

        foreach ($queries as $query) {
            $persistence->exec($query);
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('RDS scheduler storage successfully installed'));
    }
}
