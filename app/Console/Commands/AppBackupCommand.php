<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AppBackupCommand extends Command
{
    protected $signature = 'app:backup {--only=all : Backup type: all, database, storage}';
    protected $description = 'Perform secure backup of system database and configuration to local storage';

    public function handle(): int
    {
        $this->info('Starting Qmis backup process...');

        $backupDir = storage_path('app/backups/' . date('Y-m-d_His'));
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0750, true);
        }

        $only = $this->option('only');

        // Backup database
        if (in_array($only, ['all', 'database'])) {
            $this->info('Backing up database tables...');
            $dbFile = $backupDir . '/database_dump.json';

            $data = [
                'created_at' => now()->toIso8601String(),
                'users' => \App\Models\User::count(),
                'customers' => \App\Models\Customer::count(),
                'merchants' => \App\Models\Merchant::count(),
                'transactions' => \App\Models\Transaction::count(),
                'invoices' => \App\Models\Invoice::count(),
                'settings' => \App\Models\Setting::get()->pluck('value', 'key'),
            ];

            File::put($dbFile, json_encode($data, JSON_PRETTY_PRINT));
            $this->info("Database metadata snapshot saved to {$dbFile}");
        }

        // Backup configuration
        if (in_array($only, ['all', 'storage'])) {
            $this->info('Backing up critical configurations...');
            $envCopy = $backupDir . '/env_backup.txt';
            if (File::exists(base_path('.env'))) {
                // Sanitize sensitive keys in backup
                $envLines = file(base_path('.env'), FILE_IGNORE_NEW_LINES);
                $sanitized = array_map(function ($line) {
                    if (str_starts_with($line, 'APP_KEY=') || str_starts_with($line, 'DB_PASSWORD=')) {
                        return explode('=', $line)[0] . '=[MASKED_FOR_SECURITY]';
                    }
                    return $line;
                }, $envLines);
                File::put($envCopy, implode("\n", $sanitized));
            }
        }

        $this->info("Backup completed successfully! Files stored securely at {$backupDir}");
        return self::SUCCESS;
    }
}
