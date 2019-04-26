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
 * Copyright (c) 2018  (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoScheduler\scripts\update\dbMigrations;

use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\extension\AbstractAction;
use oat\taoScheduler\model\runner\JobRunnerService;
use oat\taoScheduler\model\inspector\RdsActionInspector;
use Doctrine\DBAL\Types\Type;
use common_report_Report as Report;

/**
 * Class Version20190422114045
 *
 * NOTE! Do not change this file. If you need to change schema of storage create new version of this class.
 *
 * @package \oat\taoScheduler\scripts\update\dbMigrations
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class Version20190422114045 extends AbstractAction
{

    /**
     * @param $params
     * @return \common_report_Report
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        $service = $this->getServiceManager()->get(JobRunnerService::SERVICE_ID);
        $persistence = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById(
            $service->getOption(JobRunnerService::OPTION_ACTION_INSPECTOR_PERSISTENCE)
        );

        try {
            $this->alterTable($persistence);
        } catch (SchemaException $e) {
            return Report::createFailure(self::class . ' migration was not applied. Error message: '.$e->getMessage());
        }

        return Report::createSuccess(self::class . ' migration successfully applied');
    }

    /**
     * @param \common_persistence_SqlPersistence $persistence
     * @throws SchemaException
     */
    protected function alterTable(\common_persistence_SqlPersistence $persistence)
    {
        $schemaManager = $persistence->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        $table = $schema->getTable(RdsActionInspector::TABLE_NAME);
        $table->addColumn(RdsActionInspector::COLUMN_CREATED_AT, TYPE::DATETIME, ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP']);
        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }
}
