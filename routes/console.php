<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sheets:import')
    ->dailyAt('02:00')
    ->withoutOverlapping(120);
