# Tao task scheduler

[RRULE](https://tools.ietf.org/html/rfc5545) standard is used to configure time and recurrence rule of each job.

##Install

```bash
$ composer require oat-sa/extension-tao-scheduler
```

##Usage

###attach a job:

```
use oat\taoScheduler\model\scheduler\SchedulerServiceInterface;
$schedulerService = $this->getServiceManager()->get(SchedulerServiceInterface::SERVICE_ID);
$schedulerService->attach(
    'FREQ=MONTHLY;COUNT=5',                     //Reccurrence rule (repeat monthly, 5 times)  
    new \DateTime('2017-12-12 20:00:00'),       //Start date (time of first execution) 
    ['taoExtension/ServiceName', 'methodName']  //Callback to be called.
);
```
_Note_: 
> You can use any instance of callable type as callback except functions. In case if object is used ([$object, 'method']) make sure that object is instance of `PhpSerializable`.  

###detach a job:

```
use oat\taoScheduler\model\scheduler\SchedulerServiceInterface;
$schedulerService = $this->getServiceManager()->get(SchedulerServiceInterface::SERVICE_ID);
$schedulerService->detach(
    'FREQ=MONTHLY;COUNT=5',                     //Reccurrence rule (repeat monthly, 5 times)  
    new \DateTime('2017-12-12 20:00:00'),       //Start date (time of first execution) 
    ['taoExtension/ServiceName', 'methodName']  //Callback to be called.
);
```

Also cron notation may be used:
```
$schedulerService->attach(
    '* * * * *',                                //Reccurrence rule (repeat minutely)  
    new \DateTime('2017-12-12 20:00:00'),       //Start date (time of first execution) 
    ['taoExtension/ServiceName', 'methodName']  //Callback to be called.
);
```

All given parameters to `detach` function should be the same as in existing job.

###Run JobRunner

```
sudo -u www-data php index.php '\oat\taoScheduler\scripts\JobRunner' 1518006301 PT10S
```

- First parameter is a timestamp from which scheduler should start to look up scheduled tasks. 
All the tasks scheduled before this time will be ignored.

_Note_: 
> Scheduled tasks may be executed even if their start date is later than timestamp given to job runner because they may be recurred and their start date is just a time of the first occurrence      

If this parameter is omitted or less than 0 then start time will be taken from the storage (time of last seeking of scheduled job or time of last job runner iteration).  
If there is no last iteration time in the storage current timestamp will be used.

- Second parameter is time between Job Runner iterations. Default value is 60 seconds. This time may be longer than configured because execution of tasks found during current iteration may take some time. 

###Discover tasks to me run

```
sudo -u www-data php index.php '\oat\taoScheduler\scripts\tools\SchedulerHelper' show 1518097355 1518100955 
```

###Warnings

1. Current version of scheduler cannot be in parallel in two or more instances.
2. Please do not schedule tasks which execution of which requires a lot of resource or take long time. All the time/resources consuming tasks should create appropriate tasks in the task queue. Ideally scheduled tasks should do nothing except adding tasks to the task queue.
 
