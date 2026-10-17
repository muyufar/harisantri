<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('hsn:release-expired')->everyMinute()->withoutOverlapping();
