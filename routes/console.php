<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('creditos:respaldar')->dailyAt(config('creditos.backup_time'))->withoutOverlapping();
