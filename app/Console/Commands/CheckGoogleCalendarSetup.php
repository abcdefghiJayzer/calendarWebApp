<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckGoogleCalendarSetup extends Command
{
    protected $signature = 'calendar:check-setup';
    protected $description = 'Check Google Calendar setup and credentials';

    public function handle()
    {
        $credentialsPath = storage_path('app/google-calendar/credentials.json');

        if (!file_exists($credentialsPath)) {
            $this->error('Google Calendar credentials not found!');
            $this->info('Please follow these steps:');
            $this->info('1. Go to Google Cloud Console: https://console.cloud.google.com/');
            $this->info('2. Create a project or select existing one');
            $this->info('3. Enable Google Calendar API');
            $this->info('4. Create OAuth 2.0 credentials');
            $this->info('5. Download credentials and save as:');
            $this->info("   {$credentialsPath}");
            return 1;
        }

        $this->info('Google Calendar credentials found!');
        return 0;
    }
}
