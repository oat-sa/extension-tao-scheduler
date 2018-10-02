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

namespace oat\taoScheduler\model\inspector;

use Scheduler\ActionInspector\RdsActionInspector as ParentRdsActionInspector;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Class RdsActionInspector
 * @package \oat\taoScheduler\model\inspector\RdsActionInspector
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RdsActionInspector extends ParentRdsActionInspector implements ServiceLocatorAwareInterface
{

    use ServiceLocatorAwareTrait;

    /** @var \common_persistence_SqlPersistence */
    private $persistence;

    /**
     * RdsActionInspector constructor.
     * @param \common_persistence_SqlPersistence $persistence
     */
    public function __construct(\common_persistence_SqlPersistence $persistence)
    {
        $this->persistence = $persistence;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function getQueryBuilder()
    {
        /**@var \common_persistence_sql_pdo_mysql_Driver $driver */
        return $this->getPersistence()->getPlatform()->getQueryBuilder();
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        return $this->persistence;
    }

    /**
     * @param \common_persistence_SqlPersistence $persistence
     */
    public static function initDatabase(\common_persistence_SqlPersistence $persistence)
    {
        $schemaManager = $persistence->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn(static::COLUMN_ID, 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn(static::COLUMN_STATE, 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn(static::COLUMN_REPORT, 'text', ['notnull' => false]);
        $table->setPrimaryKey([static::COLUMN_ID]);
        $table->addIndex([static::COLUMN_ID], 'IDX_' . static::TABLE_NAME . '_' . static::COLUMN_ID);
        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }

    /**
     * @param \common_persistence_SqlPersistence $persistence
     */
    public static function dropDatabase(\common_persistence_SqlPersistence $persistence)
    {
        $schemaManager = $persistence->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;
        $schema->dropTable(self::TABLE_NAME);
        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }
}
