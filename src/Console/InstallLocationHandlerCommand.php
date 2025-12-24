<?php

namespace LBHurtado\FormHandlerLocation\Console;

use Illuminate\Console\Command;

class InstallLocationHandlerCommand extends Command
{
    protected $signature = 'location-handler:install {--force}';

    protected $description = 'Install location handler UI dependencies and assets';

    public function handle(): int
    {
        $this->info('Installing Location Handler...');

        // UI dependencies (card, button, alert) should already be installed
        // No shadcn components needed to install

        // Publish Vue components
        $this->call('vendor:publish', [
            '--tag' => 'location-handler-stubs',
            '--force' => $this->option('force'),
        ]);

        $this->info('✓ Location Handler installed successfully!');

        return self::SUCCESS;
    }
}
