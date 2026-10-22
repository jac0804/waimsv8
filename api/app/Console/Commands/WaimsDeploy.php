<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WaimsDeploy extends Command
{
    protected $signature = 'waims:deploy';
    protected $description = 'Deployment: prepare migrations and run monthly migration files';

    public function handle()
    {
        $dir         = app_path('Migrations/waims');
        $migratePath = 'app/Migrations/waims';   // relative to the project root

        // 1. create the folder if it doesn't exist
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
            $this->info('Created folder: ' . $dir);
        }

        // 1. delete the 2 default migration files
        $defaults = array(
            '2014_10_12_000000_create_users_table.php',
            '2014_10_12_100000_create_password_resets_table.php',
            '0001_01_01_000000_create_users_table.php',
            '0001_01_01_000001_create_cache_table.php',
            '0001_01_01_000002_create_jobs_table.php'
        );
        foreach ($defaults as $f) {
            if (File::exists($dir . '/' . $f)) {
                File::delete($dir . '/' . $f);
                $this->info('Deleted default file: ' . $f);
            }
        }

        // 2. create the migrations table manually if it doesn't exist
        if (!Schema::hasTable('migrations')) {
            DB::statement("CREATE TABLE `migrations` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `migration` varchar(255) NOT NULL,
                `batch` int(11) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            $this->info('Created migrations table.');
        }

        // 3. check the migrations folder
        $files = collect(File::glob($dir . '/*.php'))
            ->map(function ($p) {
                return basename($p, '.php');
            })
            ->sort()->values();

        if ($files->isEmpty()) {
            $this->warn('No migration files found. Nothing to do.');
            return 0;
        }

        // 4. run pending migrations
        $this->info('Running pending migrations...');
        $this->call('migrate', array('--path' => $migratePath, '--force' => true));

        // 5. always re-run the last 2 files (previous month + latest month)
        $rerun = $files->slice(-2)->values();
        $this->info('Re-running ' . $rerun->count() . ' file(s)');

        foreach ($rerun as $file) {
            $this->info('Re-running: ' . $file);

            require_once $dir . '/' . $file . '.php';
            $class = Str::studly(substr($file, 18));   // 2026_10_01_000000_waims_changes_2026_10 -> WaimsChanges202610

            if (!class_exists($class)) {
                $this->error('Class ' . $class . ' not found in ' . $file);
                return 1;
            }

            try {
                $migration = new $class;
                $migration->up();
                $this->info('Done: ' . $file);
            } catch (\Exception $e) {
                $this->error('Failed on ' . $file . ': ' . $e->getMessage());
                return 1;
            }
        }

        $this->info('waims:deploy finished.');
        return 0;
    }
}
