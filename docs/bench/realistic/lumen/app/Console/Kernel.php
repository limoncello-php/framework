<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * @inheritdoc
     */
    protected $commands = [];

    /**
     * @inheritdoc
     */
    protected function schedule(Schedule $schedule)
    {
    }
}
