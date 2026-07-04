<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\posClass;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Support\Collection;

use Exception;
use Carbon\Carbon;

class DLMirrorMasters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sbcupdate:dlmirrormasters';
    private $coreFunction;
    private $posClass;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SBC Web Service';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $this->coreFunction = new coreFunctions;
        $this->posClass = new posClass;

        date_default_timezone_set('Asia/Singapore');
        $currentdate = date('Y-m-d');

        try {

            $processSyncing = $this->coreFunction->getfieldvalue("profile", "pvalue", "doc='IOU' and psection='MIRROR'");

            if ($processSyncing == '') {
                $this->coreFunction->sbclogger("Starting database mirror", 'MIRROR');

                $syncing = ['doc' => 'IOU', 'psection' => 'MIRROR', 'pvalue' => 1];
                $this->coreFunction->sbcinsert("profile", $syncing);

                $this->coreFunction->sbclogger("Mirror - Extract files", 'MIRROR');
                // $this->coreFunction->LogConsole("Mirror - Extract files...");
                if ($this->posClass->ftpextractmirrorfiles()) {
                    $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);

                    $this->coreFunction->execqry("delete from pos_log where e_detail in ('MIRROR','MIRROR2') and date(date_executed)<'" . $currentdate . "'");

                    $this->coreFunction->sbclogger("Database mirror completed", 'MIRROR');
                }
            } else {

                $this->coreFunction->sbclogger("Process already running", 'MIRROR2');

                $lastlog = $this->coreFunction->datareader("select date_executed as value from pos_log where e_detail='MIRROR' order by e_id desc limit 1");
                if ($lastlog != '') {
                    $lastlog = Carbon::parse($lastlog);
                    $current_logtime = date('Y-m-d H:i:s');
                    $current_logtime =   Carbon::parse($current_logtime);

                    $idletime =  $lastlog->diffInMinutes($current_logtime, false);
                    if (abs($idletime) >= 30) {
                        $this->coreFunction->sbclogger("Reset mirror after " . abs($idletime) . " minutes", 'MIRROR2');

                        $this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);
                    }
                }
            }
        } catch (Exception $e) {
            $msg = substr($e, 0, 1000);
            $this->coreFunction->sbclogger('UpdateUtilitiesMirror - ' . $msg);
            $this->coreFunction->LogConsole($msg);

            //$this->coreFunction->execqry("delete from profile where doc=? and psection=?", 'delete', ['IOU', 'MIRROR']);
        }

        // //$this->line('write file');

    } // end function



    //DO NOT REMOVE
    //Calling in terminal
    //php artisan sbcupdate:utilities


    // must remove drop the primary of the backup database
    // ALTER TABLE terms MODIFY line INT NOT NULL;
    // ALTER TABLE terms DROP PRIMARY KEY;

    // ALTER TABLE ewtlist MODIFY line INT NOT NULL;
    // ALTER TABLE ewtlist DROP PRIMARY KEY;

    // ALTER TABLE centeraccess MODIFY line INT NOT NULL;
    // ALTER TABLE centeraccess DROP PRIMARY KEY;



    //  $this->coreFunction->LogConsole('test..........');

    //         // Get files from FTP (like SELECT * FROM ftp_files)
    //         $files = Storage::disk('ftpmirror')->files('/MIRROR/MIRROR1/download/');

    //         // SQL-like query using Collections
    //         $groupedFiles = collect($files)
    //             ->map(function ($file) {
    //                 // Like SELECT filename, prefix FROM files
    //                 $filename = basename($file);
    //                 return (object) [
    //                     'filename' => $filename,
    //                     'fullpath' => $file,
    //                     'prefix' => explode('~', $filename)[0]   // First 2 letters
    //                 ];
    //             })
    //             ->groupBy('prefix')  // LIKE GROUP BY prefix
    //             ->map(function ($group, $prefix) {
    //                 // LIKE SELECT prefix, COUNT(*), GROUP_CONCAT(filename)
    //                 return (object) [
    //                     'prefix' => $prefix,
    //                     'count' => $group->count(),
    //                     'files' => $group->pluck('filename')->toArray(),
    //                     'fullpaths' => $group->pluck('fullpath')->toArray()
    //                 ];
    //             })
    //             ->sortBy('prefix');  // LIKE ORDER BY prefix ASC
    //         // ->values();  // Reset keys

    //         $prefixes = $groupedFiles->pluck('prefix');

    //         // If you want a plain PHP array instead of a Collection:
    //         $prefixes = $groupedFiles->pluck('prefix')->toArray();

    //         // If you just want to see them for debugging:
    //         // $this->coreFunction->LogConsole(json_encode($prefixes, JSON_PRETTY_PRINT));


    //         // $priodoc = ['PO', 'RR', 'DM', 'SO', 'SJ', 'CM', 'IS', 'AJ', 'TS'];
    //         // $toSort = ['SJ', 'SO', 'AJ', 'RR'];

    //         // usort($toSort, function ($a, $b) use ($priodoc) {
    //         //     return array_search($a, $priodoc) - array_search($b, $priodoc);
    //         // });


    //         $transGroup = $groupedFiles->get('trans');
    //         $transFullpaths = $transGroup ? $transGroup->files : [];

    //         $this->coreFunction->LogConsole(json_encode($transFullpaths, JSON_PRETTY_PRINT));

    //         return response()->json(['message' => 'No files found']);




}//end class