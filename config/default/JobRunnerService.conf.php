<?php
use oat\taoScheduler\model\runner\JobRunnerService;

return new JobRunnerService([
    JobRunnerService::OPTION_PERSISTENCE => 'cache',
    JobRunnerService::OPTION_ACTION_INSPECTOR_PERSISTENCE => 'default',
]);
