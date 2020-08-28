<?php
use oat\taoScheduler\model\scheduler\SchedulerService;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;
use oat\taoScheduler\model\scheduler\StorageAggregator;
use oat\taoScheduler\model\scheduler\SchedulerCacheStorage;

return new SchedulerService([
    SchedulerService::OPTION_JOBS_STORAGE => StorageAggregator::class,
    SchedulerService::OPTION_JOBS_STORAGE_PARAMS => [
        new SchedulerRdsStorage('default'),
        new SchedulerCacheStorage(),
    ],
]);
