<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class MakeMonthlyMigration extends Command
{
    protected $signature = 'make:monthly-migration {--month= : Format YYYY-MM (default: current month)}';

    protected $description = 'Create this month\'s migration file (skips if it already exists)';

    public function handle()
    {
        $date = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : Carbon::now('Asia/Manila')->startOfMonth();

        $ym    = $date->format('Y_m');                 // 2026_10
        $name  = "waims_changes_{$ym}";                // waims_changes_2026_10
        $class = 'WaimsChanges' . $date->format('Ym'); // WaimsChanges202610
        $dir   = app_path('Migrations/waims');

        // create the folder if it doesn't exist
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
            $this->info('Created folder: ' . $dir);
        }

        // skip if this month's file already exists
        if (count(File::glob("{$dir}/*_{$name}.php")) > 0) {
            $this->info("Skipped: {$name} already exists.");
            return 0;
        }

        $file = "{$dir}/{$date->format('Y_m_d')}_000000_{$name}.php";
        File::put($file, $this->stub($class, $ym));

        $this->info('Created: ' . basename($file));
        return 0;
    }

    private function stub($class, $ym)
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use App\Http\Classes\coreFunctions;

// WAIMS table/field changes for {$ym}
class {$class} extends Migration
{
    public function up()
    {
        \$core = new coreFunctions;
        \$core->LogConsole("-- running {$class}");

        // ---- add this month's changes below ----

        // \$core->sbccreatetable("tablename", \$qry);
        // \$core->sbcaddcolumngrp(["lahead", "glhead"], ["newfield"], "varchar(100) NOT NULL DEFAULT ''", 0);
        // \$core->sbcaddcolumn("item", "newcol", "tinyint(1) NOT NULL DEFAULT '0'", 0);
    }

    public function down()
    {
    }
}
PHP;
    }
}
