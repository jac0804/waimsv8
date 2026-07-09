<?php

namespace App\Http\Classes;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use App\Http\Classes\Logger;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use App\Http\Classes\posClass;

use Illuminate\Support\Facades\Storage;
use Datetime;
use DateInterval;
use Exception;
use Illuminate\Support\Str;

use Carbon\Carbon;

class mirrorClass
{
    private $othersClass;
    private $coreFunctions;
    private $logger;
    private $companysetup;
    private $sqlquery;
    private $posClass;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
        $this->logger = new Logger;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->sqlquery = new sqlquery;
        $this->posClass = new posClass;
    } //end fn

    function createTempTables()
    {

        $tables = $this->posClass->getMasterTables();

        if (empty($tables)) {
            return true;
        }

        foreach ($tables as $table) {
            $mirrorTable = $table . '_mirror';
            $qry = "CREATE TABLE {$mirrorTable} like {$table}";
            $this->coreFunctions->sbccreatetable($mirrorTable, $qry);
        }

        return true;
    }

    public function masterfilemirror($table, $uniquefield)
    {

        $this->coreFunctions->sbclogger("Creating " . $table . " file", 'MIRROR');

        try {
            $sql = "select * from " . $table . " where ismirror=0 order by " . $uniquefield[0] . ' limit 10000';
            $item = $this->coreFunctions->opentable($sql);
            $item2 = json_decode(json_encode($item), true);

            $this->coreFunctions->sbclogger($table . ' records: ' . count($item), "MIRROR");

            $batchSize = 5000;
            $totalRowsItem = count($item);
            $counter = 1;

            for ($offset = 0; $offset < $totalRowsItem; $offset += $batchSize) {
                $batch = array_slice($item2, $offset, $batchSize);
                $rowCount = count($batch);

                $this->coreFunctions->sbclogger("creating " . $table . " csv string batch " . $counter, "MIRROR");

                // --- Time CSV string creation ---
                $csvStart = microtime(true);
                $csv = $this->createCSV($table, $batch);
                $csvElapsed = round(microtime(true) - $csvStart, 3);

                $this->coreFunctions->sbclogger(
                    "creating " . $table . " csv string batch " . $counter .
                        " (" . $rowCount . " rows) took " . $this->formatElapsed($csvElapsed),
                    'MIRROR'
                );

                // --- Time CSV file write ---
                $fileStart = microtime(true);
                $this->ftpcreatefilelocal($csv, $table, 1, ".b" . $counter);
                $fileElapsed = round(microtime(true) - $fileStart, 3);

                $this->coreFunctions->sbclogger(
                    "creating " . $table . " csv file batch " . $counter .
                        " took " . $this->formatElapsed($fileElapsed),
                    'MIRROR'
                );

                $this->markBatchMirrored($table, $uniquefield, $batch);

                $counter += 1;
            }
        } catch (\Throwable $e) {
            $msg = substr($e->getMessage(), 0, 1000);
            $this->coreFunctions->sbclogger('masterfilemirror - ' . $msg, 'MIRROR');
            $this->coreFunctions->LogConsole($msg);
        }
    } //end function

    private function markBatchMirrored($table, $uniquefield, $rows)
    {
        $updateChunkSize = 500; // keep SQL size reasonable for composite-key OR groups
        $chunks = array_chunk($rows, $updateChunkSize);

        foreach ($chunks as $chunk) {
            $orGroups = [];
            foreach ($chunk as $row) {
                $andParts = [];
                foreach ($uniquefield as $uf) {
                    $andParts[] = $uf . " = '" . addslashes($row[$uf]) . "'";
                }
                $orGroups[] = "(" . implode(" and ", $andParts) . ")";
            }

            $qryupdate = "update " . $table . " set ismirror=1 where " . implode(" or ", $orGroups);
            $this->coreFunctions->execqry($qryupdate);
        }
    }

    /**
     * Formats an elapsed-seconds float as "Xs" if under 60s,
     * or "Ym Z.zzzs" if 60s or more.
     */
    private function formatElapsed($seconds)
    {
        $seconds = round($seconds, 3);

        if ($seconds < 60) {
            return $seconds . "s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = round($seconds - ($minutes * 60), 3);

        return $minutes . "m " . $remainingSeconds . "s";
    }

    public function createCSV($table, $nums)
    {
        if (empty($nums)) {
            return '';
        }

        $columns = null;
        $rows = [];

        $arrDateCols = $this->coreFunctions->opentable("select lcase(COLUMN_NAME) as col_name  FROM information_schema.columns WHERE table_schema = '" . env('DB_DATABASE') . "' and  table_name = '" . $table . "' and data_type in ('date', 'datetime') ORDER BY ordinal_position");
        $arrDateCols = array_column(json_decode(json_encode($arrDateCols), true), 'col_name');
        $invalidDates = ['',  '0', '0.000000', '0000-00-00', '0000-00-00 00:00:00', 'NULL', 'null'];

        foreach ($nums as $nn) {
            $arr1 = (array)$nn;

            foreach ($arr1 as $arrkey => $arr) {
                // Check null BEFORE trim, not after
                if ($arr1[$arrkey] === null) {
                    $arr1[$arrkey] = "NULL";
                } else {
                    $arr1[$arrkey] = $this->posClass->removeNewlines(trim($arr1[$arrkey]));
                }

                if($table == 'client'){
                    if($arrkey == 'userid') {
                        if($arr1[$arrkey] == '') $arr1[$arrkey] = 0;
                    }
                }

                if(in_array(strtolower($arrkey), $arrDateCols)){
                    if (in_array($arr1[$arrkey], $invalidDates)) {
                        $arr1[$arrkey] = "NULL";
                    }
                }
            }

            // Capture columns once, from the first row
            if ($columns === null) {
                $columns = array_keys($arr1);
            }

            // Build the value tuple directly here instead of a second pass
            $values = array_map(function ($col) use ($arr1) {
                return "'" . $arr1[$col] . "'";
            }, $columns);
            $rows[] = '(' . implode(',', $values) . ')';
        }

        if (empty($rows)) {
            return '';
        }

        // Wrap reserved-word columns in backticks
        $columnList = implode(',', array_map(function ($col) {
            return $this->isMysqlReservedWord($col) ? "`{$col}`" : $col;
        }, $columns));

        $sql = 'insert into ' . $table . '_mirror(' . $columnList . ')values' . implode(',', $rows);

        return '"' . $sql . '"' . "\n" . "ENDFILE";
    }

    public function transactionsmirror($doc)
    {
        $this->coreFunctions->sbclogger("Mirror - Creating " . $doc . " transactions file", "DLOCK");

        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $numtable = '';
        $tables = [];

        try {

            if ($doc == '') {
                $docs = $this->posClass->getalltransdoc($doc, $numtable);
            } else {
                $docs = $this->posClass->gettransdoc($doc, $numtable);
            }

            if (!empty($docs)) {
                $this->coreFunctions->sbclogger('Found transactions to mirror: ' . count($docs), 'MIRROR');

                $transCtr = 1;

                foreach ($docs as $dkey => $doc1) {

                    $start = Carbon::parse($this->othersClass->getCurrentTimeStamp());

                    $postedTables = $this->posClass->getPostedTables($doc1->doc);
                    $numtable = $postedTables['numtable'];
                    $tables = $postedTables['tables'];

                    if ($numtable == '') {
                        $this->coreFunctions->LogConsole('No numtable found for doc:' . $doc1->doc);
                        continue;
                    }

                    $queries = [];
                    foreach ($tables as $t) {
                        $qry = '';
                        if ($doc1->unposted) {
                            $qry = "delete from " . $t . " where trno=" . $doc1->trno;
                        } else {
                            $qry = $this->posClass->gettransactionsqry($t, $doc1->trno);
                        }
                        if ($qry != '') array_push($queries, $qry);
                    }
                    $csv = $this->posClass->createtranscsv($queries);

                    $csvtype = 'trans';
                    if ($doc1->unposted) $csvtype = 'unposted';

                    $this->ftpcreatefiletranslocal($csv, $doc1->doc, $doc1->docno, $doc1->trno, $csvtype, true);
                    if ($doc1->unposted) {
                        $this->coreFunctions->execqry("delete from unpostedtrans where trno=" . $doc1->trno . " and doc='" . $doc1->doc . "'");
                    } else {
                        $this->coreFunctions->sbcupdate($numtable, ['iscsv' => 1], ['trno' => $doc1->trno]);
                    }

                    $end = Carbon::parse($this->othersClass->getCurrentTimeStamp());
                    $elapsed = $start->diffInSeconds($end);
                    $this->coreFunctions->sbclogger('Created csv('. $transCtr.'):' . $doc1->doc . ', docno:' . $doc1->docno . ', trno:' . $doc1->trno . ' - ' . $elapsed . ' seconds', 'MIRROR');

                    $transCtr += 1;
                }
            } else {
                $this->coreFunctions->LogConsole('No transaction(s) found.');
            }
        } catch (\Throwable $e) {
            $msg = substr($e, 0, 1000);
            $this->coreFunctions->sbclogger('transactionsmirror - ' . $msg);
            $this->coreFunctions->LogConsole($msg);
        }
    }

    function isMysqlReservedWord($word)
    {
        static $reserved = null;

        if ($reserved === null) {
            // Core commonly-hit reserved words (not exhaustive — see note below)
            $reserved = array_flip([
                'lock', 'rank', 'order', 'group', 'key', 'table', 'select', 'insert',
                'update', 'delete', 'where', 'from', 'index', 'primary', 'foreign',
                'references', 'values', 'set', 'read', 'write', 'range', 'row',
                'rows', 'limit', 'offset', 'union', 'join', 'left', 'right', 'outer',
                'inner', 'on', 'as', 'and', 'or', 'not', 'null', 'default', 'check',
                'constraint', 'unique', 'column', 'database', 'schema', 'view',
                'trigger', 'procedure', 'function', 'grant', 'revoke', 'user',
                'password', 'timestamp', 'date', 'time', 'datetime', 'year',
                'condition', 'case', 'when', 'then', 'else', 'end', 'is', 'in',
                'between', 'like', 'exists', 'all', 'any', 'some', 'distinct',
                'having', 'over', 'partition', 'window',
            ]);
        }

        return isset($reserved[strtolower($word)]);
    }

    public function ftpextractmirrorfiles()
    {
        try {
            return $this->ftpcheckmirrorfiletoextract_v2();
        } catch (Exception $ex) {
            $this->coreFunctions->sbclogger('ftpextractmirrorfiles - ' . substr($ex, 0, 1000));
            return ['status' => false, 'msg' => substr($ex, 0, 1000)];
        }
    }

    public function ftpcheckmirrorfiletoextract_v2()
    {
        $status = false;
        $failures = 0;

        try {
            date_default_timezone_set('Asia/Singapore');

            $files = Storage::disk('local')->files('/mirror');

            $sbcFiles = array_filter($files, function ($file) {
                return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'sbc';
            });

            $totalFiles = count($files);
            $this->coreFunctions->sbclogger('Total files found: ' . $totalFiles, 'MIRROR');

            $groupedFiles = collect($sbcFiles)
                ->map(function ($file) {
                    $filename = basename($file);
                    return (object) [
                        'filename' => $filename,
                        'fullpath' => $file,
                        'prefix' => explode('~', $filename)[0]
                    ];
                })
                ->groupBy('prefix')
                ->map(function ($group, $prefix) {
                    return (object) [
                        'prefix' => $prefix,
                        'count' => $group->count(),
                        'items' => $group->values(), // keep filename+fullpath paired together
                    ];
                })
                ->sortBy('prefix');

            $prioFile = [
                'item',
                'uom',
                'iteminfo',
                'client',
                'clientinfo',
                'model_masterfile',
                'part_masterfile',
                'stockgrp_masterfile',
                'frontend_ebrands',
                'item_class',
                'category_masterfile',
                'projectmasterfile',
                'itemcategory',
                'itemsubcategory',
                'coa',
                'useraccess',
                'users',
                'moduleaccess',
                'center',
                'centeraccess',
                'ewtlist',
                'terms',
                'trans',
                'unposted'
            ];

            $prioDoc = ['PR', 'PO', 'RR', 'DM', 'SO', 'SJ', 'MJ', 'CI', 'CM', 'MC', 'IS', 'AJ', 'TS', 'ST', 'AP', 'PV', 'CV', 'AR', 'KR', 'CR', 'DS'];

            $sortedPrefixes = $this->sortByPriority($groupedFiles->keys()->toArray(), $prioFile);

            foreach ($sortedPrefixes as $prefix) {
                $prefixGroup = $groupedFiles->get($prefix);
                $items = $prefixGroup ? $prefixGroup->items : collect();

                $this->coreFunctions->sbclogger('Found ' . $items->count() . ' file(s) for prefix: ' . $prefix, 'MIRROR');

                if ($prefix == 'trans') {
                    $transByType = $items
                        ->map(function ($item) {
                            $parts = explode('~', $item->filename);
                            $item->doc = isset($parts[1]) ? $parts[1] : null;
                            return $item;
                        })
                        ->groupBy('doc');

                    $sortedDocs = $this->sortByPriority($transByType->keys()->toArray(), $prioDoc);

                    foreach ($sortedDocs as $doc) {
                        $docItems = $transByType->get($doc)
                            ->sortBy(function ($item) {
                                return $this->extractSortKey($item->filename);
                            })
                            ->values();

                        foreach ($docItems as $item) {
                            // $this->coreFunctions->LogConsole('File ' . $item->filename . ' - ' . $item->fullpath);
                            if (!$this->processMirrorFile($prefix, $item->filename, $item->fullpath)) {
                                $this->coreFunctions->sbclogger('Failed to extract ' . $item->filename, 'MIRROR');
                                $failures++;
                            }
                        }
                    }
                } else {
                    $sortedItems = $items
                        ->sortBy(function ($item) {
                            return $this->extractSortKey($item->filename);
                        })
                        ->values();

                    foreach ($sortedItems as $item) {
                        // $this->coreFunctions->LogConsole('File ' . $item->filename . ' - ' . $item->fullpath);
                        if (!$this->processMirrorFile($prefix, $item->filename, $item->fullpath)) {
                            $this->coreFunctions->sbclogger('Failed to extract ' . $item->filename, 'MIRROR');
                            $failures++;
                        }
                    }
                }
            }
            $status = ($failures === 0); // true only if every file processed cleanly
        } catch (Exception $ex) {
            $status = false;
            $this->coreFunctions->sbclogger('ftpcheckmirrorfiletoextract - ' . substr($ex->getMessage(), 0, 1000));
            throw new \Exception('Exception message (ftpcheckmirrorfiletoextract) => ' . $ex->getMessage(), 0, $ex);
        }

        return ['status' => $status, 'failures' => $failures];
    }

    private function extractSortKey($filename)
    {
        $parts = explode('~', $filename);
        $last = end($parts); // last "~" segment, e.g. "2026-07-0122.20.04.sbc"
        return preg_replace('/\.sbc$/i', '', $last); // strip extension only
    }

    /**
     * Sort a list of values by their position in a priority list.
     * Unmatched values are pushed to the end instead of jumping to the front.
     */
    private function sortByPriority(array $values, array $priorityList)
    {
        usort($values, function ($a, $b) use ($priorityList) {
            $posA = array_search($a, $priorityList);
            $posB = array_search($b, $priorityList);

            $posA = ($posA === false) ? PHP_INT_MAX : $posA;
            $posB = ($posB === false) ? PHP_INT_MAX : $posB;

            return $posA - $posB;
        });

        return $values;
    }

    /**
     * Process a single mirrored file: check, extract, then delete on success.
     * Returns true if the file was fully processed and deleted, false otherwise.
     */
    private function processMirrorFile($table, $filename, $fullpath)
    {
        $this->coreFunctions->sbclogger('Processing file: ' . $filename, 'MIRROR');

        if (Str::substr($filename, -3) !== 'sbc') {
            return false;
        }

        $arrline = $this->ftpfilecheckendfile($fullpath);

        if (!is_array($arrline)) {
            return false;
        }

        if (!$this->extractionlinerecord($fullpath, $table, $filename)) {
            return false;
        }

        try {
            return $this->moveToDownloadedFolder($filename);
        } catch (Exception $ex) {
            $this->coreFunctions->sbclogger("MIRROR - deleting failed " . $filename . ' ' . substr($ex->getMessage(), 0, 1000));
            return false;
        }
    }

    private function extractionlinerecord($path, $table, $filename)
    {
        $status = true;
        try {

            $start = Carbon::parse($this->othersClass->getCurrentTimeStamp());

            $file = Storage::disk('local')->get($path);
            $qrys = explode("\n", $file);

            if ($table == 'trans' || $table == 'unposted') {
                $f = explode('/', $path);
                $f = explode('~', $f[1]);
                $trno = $f[3];
                $doc = $f[1];
                $numtable = '';
                $tables = [];

                $postedTables = $this->posClass->getPostedTables($doc);
                $numtable = $postedTables['numtable'];
                $tables = $postedTables['tables'];

                if ($numtable == '') {
                    $this->coreFunctions->LogConsole('No numtable found for doc:' . $doc);
                    return ['status' => false];
                }

                $arap = [];

                if ($table == 'unposted') {
                    if (($key = array_search('gldetail', $tables)) !== false) {
                        $arap = $this->coreFunctions->opentable("select d.refx,d.linex,coa.acno from gldetail as d left join coa on coa.acnoid=d.acnoid where d.refx<>0 and d.trno=" . $trno);
                    }
                } else {
                    $rec = $this->coreFunctions->opentable("select trno from $numtable where trno=" . $trno);
                    if (!empty($rec)) {
                        foreach ($tables as $t) {
                            $this->coreFunctions->execqry("delete from $t where trno=" . $trno, 'delete');
                        }
                    }
                }

                foreach ($qrys as $qry) {
                    if ($qry != 'ENDFILE' && $qry != '') {
                        $qry = str_replace("'NULL'", "NULL", $qry);
                        if ($this->coreFunctions->execqry(str_replace('"', '', $qry))) {
                            $this->coreFunctions->LogConsole("success insert");
                        } else {
                            $this->coreFunctions->LogConsole("failed insert");
                            $status = false;
                        }
                    }
                }

                if (!$status) {
                    foreach ($tables as $t) {
                        $this->coreFunctions->execqry("delete from $t where trno=" . $trno, 'delete');
                    }
                } else {
                    //update ar/ap
                    if ($arap && count($arap) > 0) {
                        $config = [];
                        $config['params']['doc'] = $doc;
                        $config['params']['trno'] = $trno;
                        $config['params']['user'] = 'MIRROR';
                        foreach ($arap as $key_arap => $val_arap) {
                            $this->sqlquery->setupdatebal($val_arap->refx, $val_arap->linex, $val_arap->acno, $config);
                        }
                    }

                    $servedqa = [];
                    switch ($doc) {
                        case 'RR':
                            $servedqa = $this->coreFunctions->opentable("select refx,linex from glstock where trno=" . $trno . " and refx<>0");
                            foreach ($servedqa as $keyqa => $valqa) {
                                if ($this->othersClass->setserveditemsRR($valqa->refx, $valqa->linex, "qty") == 0) {
                                    $this->coreFunctions->sbclogger('extractionlinerecord - failed to update setserveditems ' . $path);
                                    return false;
                                }
                            }
                            break;
                        case 'DM':
                            $servedqa = $this->coreFunctions->opentable("select refx,linex from glstock where trno=" . $trno . " and refx<>0");
                            foreach ($servedqa as $keyqa => $valqa) {
                                if (app('App\Http\Classes\modules\purchase\dm')->setserveditems($valqa->refx, $valqa->linex, "qty") == 0) {
                                    $this->coreFunctions->sbclogger('extractionlinerecord - failed to update setserveditems ' . $path);
                                    return false;
                                }
                            }
                            break;
                        case 'SJ':
                            $servedqa = $this->coreFunctions->opentable("select refx,linex from glstock where trno=" . $trno . " and refx<>0");
                            foreach ($servedqa as $keyqa => $valqa) {
                                if (app('App\Http\Classes\modules\sales\sj')->setserveditems($valqa->refx, $valqa->linex, "iss") == 0) {
                                    $this->coreFunctions->sbclogger('extractionlinerecord - failed to update setserveditems ' . $path);
                                    return false;
                                }
                            }
                            break;
                        case 'CM':
                            $servedqa = $this->coreFunctions->opentable("select refx,linex from glstock where trno=" . $trno . " and refx<>0");
                            foreach ($servedqa as $keyqa => $valqa) {
                                if (app('App\Http\Classes\modules\sales\cm')->setserveditems($valqa->refx, $valqa->linex) == 0) {
                                    $this->coreFunctions->sbclogger('extractionlinerecord - failed to update setserveditems ' . $path);
                                    return false;
                                }
                            }
                            break;
                    }
                }
            } else {

                foreach ($qrys as $qry) {
                    if ($qry != 'ENDFILE' && $qry != '') {
                        $qry = str_replace("'NULL'", "NULL", $qry);
                        $this->coreFunctions->sbclogger($filename . ' - Processing query started', 'MIRROR');
                        if ($this->coreFunctions->execqry(str_replace('"', '', $qry))) {
                            $this->coreFunctions->sbclogger($filename . ' - Processing query completed', 'MIRROR');

                            if ($this->updateTempToLiveTable($table, $filename)) {
                                if ($this->insertTempToLiveTable($table, $filename)) {
                                    $this->truncateTempTable($table);
                                } else {
                                    $status = false;
                                }
                            } else {
                                $status = false;
                            }
                        } else {
                            $status = false;
                        }
                    }
                }
            }

            if ($status) {
                $end = Carbon::parse($this->othersClass->getCurrentTimeStamp());
                $elapsed = $start->diffInSeconds($end);
                $this->coreFunctions->sbclogger('extracted csv:' . $path . ' - ' . $elapsed . ' seconds', "MIRROR");
            } else {
                $this->coreFunctions->sbclogger('failed to extract csv:' . $path);
            }
        } catch (Exception $ex) {
            $status = false;
            $this->coreFunctions->sbclogger(
                'extractionlinerecord - ' . substr($ex->getMessage(), 0, 1000)
                    . ' | FilePath: ' . $path
                    . ' | File: ' . $ex->getFile()
                    . ' | Line: ' . $ex->getLine()
            );
        }

        return $status;
    }

    function updateTempToLiveTable($table, $filename)
    {
        $this->coreFunctions->sbclogger($filename . ' - Processing update query', 'MIRROR');

        $primaryKey = $this->getPrimaryKey($table);
        if (empty($primaryKey)) {
            $this->coreFunctions->sbclogger($filename . ' - No primary key defined for table, aborting', 'MIRROR');
            return false;
        }

        $qry = "select COLUMN_NAME FROM information_schema.columns WHERE table_schema = '" . env('DB_DATABASE') . "' and table_name = '" . $table . "' ORDER BY ordinal_position";
        $a = $this->coreFunctions->opentable($qry);

        $columns = array_map(function ($row) {
            return $row->COLUMN_NAME;
        }, $a);

        $leftJoin = '';
        foreach ($primaryKey as $key) {
            if ($leftJoin == '') {
                $leftJoin = "t." . $key . " = m." . $key;
            } else {
                $leftJoin .= " and t." . $key . " = m." . $key;
            }
        }

        $qryupdate = "update " . $table . " as t join " . $table . "_mirror as m on " . $leftJoin;

        // Build SET clause for all columns except the primary key(s)
        $setParts = [];
        foreach ($columns as $col) {
            if (in_array($col, $primaryKey)) {
                continue;
            }
            $setParts[] = "t." . $col . " = m." . $col;
        }

        if (empty($setParts)) {
            $this->coreFunctions->sbclogger($filename . ' - No updatable columns found, aborting', 'MIRROR');
            return false;
        }

        $qryupdate .= " set " . implode(',', $setParts);

        try {
            $this->coreFunctions->execqry($qryupdate);
            $this->coreFunctions->sbclogger($filename . ' - Update query completed successfully', 'MIRROR');
            return true;
        } catch (\Throwable $e) {
            $this->coreFunctions->sbclogger($filename . ' - Update query failed: ' . $e->getMessage());
            return false;
        }
    }

    function insertTempToLiveTable($table, $filename)
    {
        $this->coreFunctions->sbclogger($filename . ' - Processing insert query', 'MIRROR');

        $primaryKey = $this->getPrimaryKey($table);
        if (empty($primaryKey)) {
            $this->coreFunctions->sbclogger($filename . ' - No primary key defined for table, aborting', 'MIRROR');
            return false;
        }

        $matchConditions = [];
        foreach ($primaryKey as $key) {
            $matchConditions[] = "t.{$key} = m.{$key}";
        }
        $matchClause = implode(' and ', $matchConditions);

        $qryinsert = "insert into " . $table . " 
                select * from " . $table . "_mirror m
                where not exists (
                    select 1 from " . $table . " t where " . $matchClause . "
                )";

        try {
            $this->coreFunctions->execqry($qryinsert);
            $this->coreFunctions->sbclogger($filename . ' - Insert query completed successfully', 'MIRROR');
            return true;
        } catch (\Throwable $e) {
            $this->coreFunctions->sbclogger($filename . ' - Insert query failed: ' . $e->getMessage(), 'MIRROR');
            return false;
        }
    }

    function truncateTempTable($table){
        try {
            $this->coreFunctions->execqry("truncate table " . $table . "_mirror");
            return true;
        } catch (\Throwable $e) {
            $this->coreFunctions->sbclogger(' - Truncate ' . $table . '_mirror failed: ' . $e->getMessage());
            return false;
        }
    }

    function checkPendingTempTables(){
 
        $tables = $this->posClass->getMasterTables();

        if (empty($tables)) {
           return true;
        }

        $selects = array_map(function ($table) {
            $mirrorTable = $table . '_mirror';
            return "select count(1), '{$table}' as tblname from {$mirrorTable} having count(1)>0";
        }, $tables);

        $sql = implode(' union all ' , $selects) . ';';

        $tables = $this->coreFunctions->opentable($sql);
        foreach ($tables as $key => $value) {
            if($this->updateTempToLiveTable($value->tblname, $value->tblname)){
                if($this->insertTempToLiveTable($value->tblname, $value->tblname)){
                    $this->truncateTempTable($value->tblname);
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }

        return true;
    }

    function getPrimaryKey($table){
        $primaryKey = [];
        switch ($table) {
            case 'item':
                $primaryKey = ['barcode'];
                break;
            case 'uom':
                $primaryKey = ['itemid', 'uom'];
                break;
            case 'iteminfo':
                $primaryKey = ['itemid'];
                break;
            case 'client':
                $primaryKey = ['client'];
                break;
            case 'clientinfo':
                $primaryKey = ['clientid'];
                break;
            case 'model_masterfile':
                $primaryKey = ['model_id'];
                break;
            case 'part_masterfile':
                $primaryKey = ['part_id'];
                break;
            case 'stockgrp_masterfile':
                $primaryKey = ['stockgrp_id'];
                break;
            case 'frontend_ebrands':
                $primaryKey = ['brandid'];
                break;
            case 'item_class':
                $primaryKey = ['cl_id'];
                break;
            case 'category_masterfile':
                $primaryKey = ['cat_id'];
                break;
            case 'projectmasterfile':
                $primaryKey = ['line'];
                break;
            case 'itemcategory':
                $primaryKey = ['line'];
                break;
            case 'itemsubcategory':
                $primaryKey = ['line'];
                break;
            case 'coa':
                $primaryKey = ['acnoid'];
                break;
            case 'useraccess':
                $primaryKey = ['userid'];
                break;
            case 'users':
                $primaryKey = ['idno'];
                break;
            case 'center':
                $primaryKey = ['line'];
                break;
            case 'ewtlist':
                $primaryKey = ['line'];
                break;
            case 'terms':
                $primaryKey = ['line'];
                break;
            default:
                return [];
                break;
        }

        return  $primaryKey;
    }


    public function ftpcreatefilelocal($csv, $type, $iscurtime = 1, $batch = '')
    {
        date_default_timezone_set('Asia/Singapore');
        $current_timestamp = date('Y-m-dH.i.s');
        if ($csv != '') {
            if ($iscurtime == 1) {
                $this->ftpwritefilelocal('/mirror/' . $type . '~' . $current_timestamp . $batch, $csv);
            } else {
                $this->ftpdeletefilelocal('mirror/' . $type . '.sbc');
                $this->ftpwritefilelocal('/mirror/' . $type, $csv);
            }
        }
        return 'true';
    }

    public function ftpcreatefiletranslocal($csv, $doc, $docno, $trno, $filetype = 'trans', $mirror = false)
    {
        date_default_timezone_set('Asia/Singapore');
        $current_timestamp = date('Y-m-dH.i.s');
        if ($csv != '') {
            $this->ftpwritefilelocal('/mirror/' . $filetype . '~' . $doc . '~' . $docno . '~' . $trno . '~' . $current_timestamp, $csv, $mirror);
        }
        return 'true';
    }    

    public function ftpwritefilelocal($filename, $content)
    {
        $ftp = 'local';

        Storage::disk($ftp)->put($filename . '.tmp', $content);
        if (is_array($this->ftpfilecheckendfile($filename . '.tmp'))) {
            Storage::disk($ftp)->move($filename . '.tmp', $filename . '.sbc');
        } else {
            Storage::disk($ftp)->delete($filename . '.tmp');
        }
        return ['status' => true];
    } //end function

    public function ftpdeletefilelocal($filename)
    {
        $ftp = 'local';

        if (Storage::disk($ftp)->exists($filename)) {
            Storage::disk($ftp)->delete($filename);
        }

        return ['status' => true];
    } //end function

    public function ftpfilecheckendfile($path)
    {
        try {
            $arrline = $this->ftpgetarrayfromfile($path);
            if (!empty($arrline)) {
                if (count($arrline) == 1) {
                    $this->coreFunctions->sbclogger('INVALID TXTFILE: ' . $path);
                    return '';
                }
                for ($i = count($arrline) - 1; $i <= count($arrline) - 1; $i--) {
                    if (trim($arrline[$i]) == 'ENDFILE') {
                        return $arrline;
                    }
                }
            }
            return false;
        } catch (Exception $ex) {
            throw new \Exception('Exception message (ftpfilecheckendfile) => ' . $path . ' - ' . $ex);
        }
    }

    public function ftpgetarrayfromfile($path)
    {
        //========================================
        // Important Comments, do not remove
        // October 5, 2025
        // Files can have different line endings: (Deepseek reference)
        // \n (Linux/macOS)
        // \r\n (Windows)
        // \r (Old Mac)
        // PHP_EOL is the line ending for the current OS the file might have been created.
        // Always use \n for file storage
        //========================================
        $file = Storage::disk('local')->get($path);
        $arrline = explode("\n", $file); //PHP_EOL: previously used
        return $arrline;
    }

    public function ftpcreatefolder($subfolder = null)
    {
        $ftp = 'local';
        $path = $subfolder ? 'mirror/' . $subfolder : 'mirror';

        if (!Storage::disk($ftp)->exists($path)) {
            Storage::disk($ftp)->makeDirectory($path);
        }

        return ['status' => true];
    }


    public function processMirrorFolder()
    {
        $status = true;

        $localDisk = 'local';
        $ftpDisk = 'ftpmirror';
        $sourceFolder = 'mirror';
        $remoteFolder = 'MIRROR';
        $allowedExtension = 'sbc';

        $yearMonth = \Carbon\Carbon::now('Asia/Manila')->format('Y-m');
        $archiveFolder = $sourceFolder . '/uploaded/' . $yearMonth;

        // Get all files, then filter to .sbc only
        $allFiles = Storage::disk($localDisk)->files($sourceFolder);
        $files = array_filter($allFiles, function ($filePath) use ($allowedExtension) {
            return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === $allowedExtension;
        });

        if (empty($files)) {
            $this->coreFunctions->sbclogger('No .sbc files to process.', "MIRROR3");
            return ['status' => $status];
        }

        // Ensure archive folder exists before moving anything into it
        if (!Storage::disk($localDisk)->exists($archiveFolder)) {
            Storage::disk($localDisk)->makeDirectory($archiveFolder);
        }

        $counter = 1;

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            $remotePath = $remoteFolder . '/' . $filename; // e.g. MIRROR/center~2026-07-04...sbc
            $stream = null;

            try {
                if (!Storage::disk($localDisk)->exists($filePath)) {
                    $this->coreFunctions->sbclogger('file missing before upload: ' . $filename, "MIRROR3");
                    continue;
                }

                $stream = Storage::disk($localDisk)->readStream($filePath);
                $uploaded = Storage::disk($ftpDisk)->put($remotePath, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                    $stream = null;
                }

                if ($uploaded) {
                    $destination = $archiveFolder . '/' . $filename;
                    Storage::disk($localDisk)->move($filePath, $destination);
                    $this->coreFunctions->sbclogger('uploaded (' . $counter . ') ' . $filename, "MIRROR3");
                } else {
                    $this->coreFunctions->sbclogger('upload failed (' . $counter . ') ' . $filename, "MIRROR3");
                    $status = false;
                }
            } catch (\Exception $e) {
                $status = false;
                $this->coreFunctions->sbclogger('processMirrorFolder - ' . $filename . ' - ' . $e->getMessage(), "MIRROR3");
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $counter += 1;
        }

        return ['status' => $status];
    }


    public function downloadFromFtp()
    {
        $status = true;

        $localDisk = 'local';
        $ftpDisk = 'ftpmirror';
        $remoteFolder = 'MIRROR';
        $destinationFolder = 'mirror';
        $allowedExtension = 'sbc';

        $ftpHost = config('filesystems.disks.ftpmirror.host');
        $this->coreFunctions->sbclogger('ftp host: ' . $ftpHost, 'MIRROR');

        // Ensure local destination folder exists
        if (!Storage::disk($localDisk)->exists($destinationFolder)) {
            Storage::disk($localDisk)->makeDirectory($destinationFolder);
        }

        // Get files from the MIRROR folder on FTP, filtered to .sbc only
        $allRemoteFiles = Storage::disk($ftpDisk)->files($remoteFolder);
        $remoteFiles = array_filter($allRemoteFiles, function ($filePath) use ($allowedExtension) {
            return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === $allowedExtension;
        });

        if (empty($remoteFiles)) {
            $this->coreFunctions->sbclogger('No .sbc files found on FTP MIRROR folder to download.', "MIRROR");
            return ['status' => $status];
        }

        $totalFiles = count($remoteFiles);
        $this->coreFunctions->sbclogger('Total files found: ' . $totalFiles, 'MIRROR');

        foreach ($remoteFiles as $remotePath) {
            $filename = basename($remotePath);
            $localPath = $destinationFolder . '/' . $filename;
            $stream = null;

            try {
                if (!Storage::disk($ftpDisk)->exists($remotePath)) {
                    $this->coreFunctions->sbclogger('remote file missing before download: ' . $filename, "MIRROR");
                    continue;
                }

                $stream = Storage::disk($ftpDisk)->readStream($remotePath);
                $written = Storage::disk($localDisk)->put($localPath, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                    $stream = null;
                }

                if (!$written) {
                    $this->coreFunctions->sbclogger('download failed ' . $filename, "MIRROR");
                    $status = false;
                    continue;
                }

                // Verify the downloaded file is complete (ends with ENDFILE)
                if (!$this->ftpfilecheckendfile($localPath)) {
                    $this->coreFunctions->sbclogger('download incomplete (no ENDFILE): ' . $filename, "MIRROR");
                    $status = false;
                    continue;
                }

                // File confirmed complete — safe to delete from FTP
                Storage::disk($ftpDisk)->delete($remotePath);
                $this->coreFunctions->sbclogger('downloaded and removed from ftp ' . $filename, "MIRROR");
            } catch (\Exception $e) {
                $status = false;
                $this->coreFunctions->sbclogger('downloadFromFtp - ' . $filename . ' - ' . $e->getMessage(), "MIRROR");
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        return ['status' => $status];
    }


    public function moveToDownloadedFolder($filename)
    {
        $localDisk = 'local';
        $sourceFolder = 'mirror';

        $yearMonth = \Carbon\Carbon::now('Asia/Manila')->format('Y-m');
        $archiveFolder = $sourceFolder . '/downloaded/' . $yearMonth;

        $sourcePath = $sourceFolder . '/' . $filename;
        $destinationPath = $archiveFolder . '/' . $filename;

        if (!Storage::disk($localDisk)->exists($sourcePath)) {
            $this->coreFunctions->sbclogger('file missing before move to downloaded: ' . $filename, "MIRROR");
            return false;
        }

        // Ensure archive folder exists before moving
        if (!Storage::disk($localDisk)->exists($archiveFolder)) {
            Storage::disk($localDisk)->makeDirectory($archiveFolder);
        }

        try {
            Storage::disk($localDisk)->move($sourcePath, $destinationPath);
            $this->coreFunctions->sbclogger('moved to downloaded: ' . $filename, "MIRROR");
            return true;
        } catch (\Exception $e) {
            $this->coreFunctions->sbclogger('moveToDownloadedFolder - ' . $filename . ' - ' . $e->getMessage(), "MIRROR");
            return false;
        }
    }

}
