<?php
use oat\taoScheduler\model\scheduler\SchedulerService;
use oat\taoScheduler\model\scheduler\SchedulerRdsStorage;

return new SchedulerService([
    SchedulerService::OPTION_JOBS_STORAGE => SchedulerRdsStorage::class,
    SchedulerService::OPTION_JOBS_STORAGE_PARAMS => ['default'],
]);
