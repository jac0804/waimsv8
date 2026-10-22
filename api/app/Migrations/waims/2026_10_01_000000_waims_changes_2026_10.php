<?php

use Illuminate\Database\Migrations\Migration;
use App\Http\Classes\coreFunctions;

// WAIMS table/field changes for 2026_10
class WaimsChanges202610 extends Migration
{
        public function up()
        {
                $core = new coreFunctions;
                $core->LogConsole("-- running WaimsChanges202610");

                // ---- add this month's changes below ----

                $core->sbcaddcolumngrp(["serialin", "serialout"], ["weight"], "decimal(10,6) not null default '0.000000'");

                $core->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans", "cntnuminfo", "hcntnuminfo"], ["pricegrp"], "varchar(5) NOT NULL DEFAULT ''");

                // 2026 Oct 21 - FMM
                $core->sbcaddcolumngrp(["iteminfo"], ["dateid"], "DATETIME DEFAULT NULL");
        }

        public function down() {}
}
