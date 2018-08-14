<?php
use oat\taoScheduler\model\runner\JobRunnerService;

return new JobRunnerService([
    JobRunnerService::OPTION_PERSISTENCE => 'cache',
    JobRunnerService::OPTION_RDS_PERSISTENCE => 'default',
]);
