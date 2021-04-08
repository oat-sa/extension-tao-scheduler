<?php

declare(strict_types=1);

namespace oat\taoScheduler\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\oatbox\event\EventManager;
use oat\taoScheduler\model\job\Job;
use oat\taoScheduler\model\scheduler\SchedulerConfigStorage;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;
use oat\taoScheduler\model\scheduler\SchedulerService;
use oat\taoScheduler\model\scheduler\StorageAggregator;
use oat\taoScheduler\scripts\tools\SchedulerHelper;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202008311040343488_taoScheduler extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Register even listener of common_ext_event_ExtensionInstalled event';
    }

    public function up(Schema $schema): void
    {
        $schedulerService = new SchedulerService([
            SchedulerService::OPTION_JOBS_STORAGE => StorageAggregator::class,
            SchedulerService::OPTION_JOBS_STORAGE_PARAMS => [
                new SchedulerRdsStorage('default'),
                new SchedulerConfigStorage(),
            ],
        ]);

        $this->getServiceManager()->register(SchedulerService::SERVICE_ID, $schedulerService);
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->attach(\common_ext_event_ExtensionInstalled::class, [SchedulerService::SERVICE_ID, 'refreshJobs']);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        //remove config job from RDS storage
        foreach ($schedulerService->getJobs() as $job) {
            if ($job->getCallable() === SchedulerHelper::class) {
                $schedulerService->detach($job->getRRule(), $job->getStartTime(), $job->getCallable(), $job->getParams());
            }
            break;
        }
    }

    public function down(Schema $schema): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->detach(\common_ext_event_ExtensionInstalled::class, [SchedulerService::SERVICE_ID, 'refreshJobs']);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        $schedulerService = new SchedulerService([
            'jobs_storage' => SchedulerRdsStorage::class,
            'jobs_storage_params' => ['default'],
        ]);
        $this->getServiceManager()->register(SchedulerService::SERVICE_ID, $schedulerService);

        //attach config job to RDS storage
        $schedulerService->attach(
            '0 0 * * *',
            new \DateTime('now', new \DateTimeZone('utc')),
            SchedulerHelper::class,
            ['removeExpiredJobs', false]
        );

    }
}
