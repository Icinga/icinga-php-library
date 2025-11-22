<?php

use Cron\CronExpression;

if (! CronExpression::supportsAlias('@minutely')) {
    CronExpression::registerAlias('@minutely', '* * * * *');
}

if (! CronExpression::supportsAlias('@quarterly')) {
    CronExpression::registerAlias('@quarterly', '0 0 1 */3 *');
}
