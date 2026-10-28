<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sheets:import')
    ->dailyAt('02:00')
    ->timezone('Europe/Tirane')
    ->withoutOverlapping(120);
