<?php

declare(strict_types=1);

namespace oat\taoScheduler\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\model\migrations\MigrationsService;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoScheduler\model\scheduler\SchedulerJobsRegistry;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202006231121433488_taoScheduler extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Register even listener of common_ext_event_ExtensionInstalled event';
    }

    public function up(Schema $schema): void
    {
        $this->getServiceManager()->register(SchedulerJobsRegistry::SERVICE_ID, new SchedulerJobsRegistry());
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->attach(\common_ext_event_ExtensionInstalled::class, [SchedulerJobsRegistry::SERVICE_ID, 'update']);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->detach(\common_ext_event_ExtensionInstalled::class, [SchedulerJobsRegistry::SERVICE_ID, 'update']);
        $this->getServiceManager()->unregister(SchedulerJobsRegistry::SERVICE_ID);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }
}
