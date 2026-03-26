<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\warehousing\forklift;
use Exception;
use Hamcrest\Type\IsNumeric;

class viewsortline
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'EDIT SORTING';
    public $gridname = 'inventory';
    private $fields = ['barcode', 'itemname'];
    private $table = 'stockrem';

    public $tablelogs = 'table_log';

    public $style = 'width:100%;max-width:80%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function createHeadField($config)
    {
        $fields = [['sortline'], 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'update');

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        if (isset($config['params']['row'])) {
            $trno = $config['params']['row']['trno'];
            $line = $config['params']['row']['line'];
        } else {
            $trno = $config['params']['dataparams']['trno'];
            $line = $config['params']['dataparams']['line'];
        }

        return $this->getheaddata($trno, $line, $config['params']['doc']);
    }

    public function getheaddata($trno, $line, $doc)
    {
        $tablename = 'lastock';
        switch ($doc) {
            case 'PO':
            case 'SO':
            case 'PC':
            case 'CD':
            case 'PR':
            case 'OS':
            case 'PX':
                $tablename = strtolower($doc . 'stock');
                $qry = "select stock.trno, stock.line, stock.sortline, stock.sortline as origline from " . $tablename . " as stock where stock.trno=? and stock.line=?";
                return $this->coreFunctions->opentable($qry, [$trno, $line]);
                break;
            case 'JB':
                $tablename = "jostock";
                $qry = "select stock.trno, stock.line, stock.sortline, stock.sortline as origline from " . $tablename . " as stock where stock.trno=? and stock.line=?";
                return $this->coreFunctions->opentable($qry, [$trno, $line]);
                break;
            case 'PV':
            case 'CV':
            case 'AP':
            case 'AR':
            case 'CR':
            case 'GJ':
            case 'DS':
            case 'GD':  
            case 'GC':  
                $tablename = 'ladetail';
                $qry = "select stock.trno, stock.line, stock.sortline, stock.sortline as origline from " . $tablename . " as stock where stock.trno=? and stock.line=?";
                return $this->coreFunctions->opentable($qry, [$trno, $line]);
                break;
            case 'QS':
                $qry = "select stock.trno, stock.line, stock.sortline, stock.sortline as origline,'qsstock' as tblname from qsstock as stock where stock.trno=? and stock.line=? 
                union all 
                select stock.trno, stock.line, stock.sortline, stock.sortline as origline,'qtstock' as tblname from qtstock as stock where stock.trno=? and stock.line=?";
                return $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
                break;
            case 'CH':
                $tablename = "sistock";
                $qry = "select stock.trno, stock.line, stock.sortline, stock.sortline as origline from " . $tablename . " as stock where stock.trno=? and stock.line=?";
                return $this->coreFunctions->opentable($qry, [$trno, $line]);
                break;    
            default:
                $qry = "select stock.trno, stock.line, stock.sortline, stock.sortline as origline from " . $tablename . " as stock where stock.trno=? and stock.line=?";
                return $this->coreFunctions->opentable($qry, [$trno, $line]);
                break;
        }
    }

    public function data()
    {
        return [];
    }


    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $doc = $config['params']['doc'];
        $sortline = $config['params']['dataparams']['sortline'];
        $trno = $config['params']['dataparams']['trno'];
        $line = $config['params']['dataparams']['line'];
        $origline = $config['params']['dataparams']['origline'];
        $tblname = isset($config['params']['dataparams']['tblname']) ? $config['params']['dataparams']['tblname'] : '';

        $sortline = $this->othersClass->sanitizekeyfield("qty", $sortline);
        $sortline = $this->othersClass->val($sortline);
        if ($sortline == 0) {
            return ['status' => false, 'msg' => 'Please encode valid number.'];
        }

        $data = [
            'trno' => $trno,
            'line' => $line,
            'sortline' => $sortline
        ];

        $tablename = 'lastock';
        switch ($doc) {
            case 'QS':
                $dataorig = $this->coreFunctions->opentable("select line, sortline,'qsstock' as tblname from qsstock where trno=? 
                union all 
                select line, sortline,'qtstock' as tblname from qtstock where trno=? order by sortline", [$trno, $trno]);
                $tablename = $tblname;
                break;
            default:
                switch ($doc) {
                    case 'PO':
                    case 'PC':
                    case 'SO':
                    case 'CD':
                    case 'PR':
                    case 'OS': 
                        $tablename = strtolower($doc . 'stock');
                        break;
                    case 'JB':
                        $tablename = "jostock";
                        break;
                    case 'CH':
                        $tablename = "sistock";
                        break;    
                    case 'PV':
                    case 'AP':
                    case 'AR':
                    case 'CR':
                    case 'GJ':
                    case 'DS':
                    case 'CV':
                    case 'GD':
                    case 'GC':     
                        $tablename = 'ladetail';
                        break;
                }

                $dataorig = $this->coreFunctions->opentable("select line, sortline,'" . $tablename . "' as tblname from " . $tablename . " where trno=? order by sortline", [$trno]);
                break;
        }


        if (intval($data['sortline']) > count($dataorig)) {
            return ['status' => false, 'msg' => 'Sort number must not greater than row count'];
        }

        $i = 1;
        foreach ($dataorig as $key => $valorig) {
            if ($valorig->line != $line) {
                if ($i == $data['sortline']) {
                    $i += 1;
                }
                if ($doc == 'QS') {
                    $this->coreFunctions->sbcupdate($valorig->tblname, ['sortline' => $i], ['trno' => $trno, 'line' => $valorig->line]);
                } else {
                    $this->coreFunctions->sbcupdate($tablename, ['sortline' => $i], ['trno' => $trno, 'line' => $valorig->line]);
                }

                $i += 1;
            }
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno, 'line' => $line])) {
        }

        $doc = $config['params']['doc'];
        $modtype = $config['params']['moduletype'];
        $path = 'App\Http\Classes\modules\\' . strtolower($modtype) . '\\' . strtolower($doc);
        $config['params']['trno'] = $trno;

        $gridname = 'inventory';
        if ($tablename == 'ladetail') {
            $gridname = 'accounting';
            $stock = app($path)->opendetail($trno, $config);
        } else {
            $stock = app($path)->openstock($trno, $config);
        }
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'reloadgriddata' => [$gridname => $stock]];
    }
}
