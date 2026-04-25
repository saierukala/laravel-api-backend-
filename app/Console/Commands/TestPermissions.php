<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:test-permissions')]
#[Description('Command description')]
class TestPermissions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
