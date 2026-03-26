<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class otentryapplication
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'OT ENTRY APPLICATION';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = 'cntnum';
    private $table = 'vrhead';
    private $htable = 'hvrhead';

    public $tablelogs = 'transnum_log';

    public $style = 'width:30%;max-width:70%;';
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

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $stat = $config['params']['row']['otstatus'];

        if ($stat != 0) {
            $fields = ['dateid', ['othrs', 'ndiffot'], ['entryot', 'entryndiffot'], 'rem'];
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'entryot.readonly', true);
            data_set($col1, 'entryndiffot.readonly', true);
            data_set($col1, 'othrs.label', 'Computed OT Hrs');
            data_set($col1, 'ndiffot.label', 'Computed N-Diff OT Hrs');
        } else {
            $fields = ['dateid', ['othrs', 'ndiffot'], ['entryot', 'entryndiffot'], 'rem', 'refresh'];
            $col1 = $this->fieldClass->create($fields);
            data_set($col1, 'rem.readonly', false);
            data_set($col1, 'refresh.label', 'APPLY');
            data_set($col1, 'othrs.label', 'Computed OT Hrs');
            data_set($col1, 'ndiffot.label', 'Computed N-Diff OT Hrs');
        }


        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $line = $config['params']['row']['line'];
        $stat = $config['params']['row']['otstatus'];

        if ($stat != 0) {
            $select = "select line,dateid,schedin,schedout,actualin,actualout,othrs,ndiffhrs as ndiffot, 
                   entryot, entryndiffot,entryremarks as rem
                   from timecard where line = " . $line;
        } else {
            $select = "select line,dateid,schedin,schedout,actualin,actualout,othrs,ndiffhrs as ndiffot, 
                   othrs as entryot, ndiffhrs as entryndiffot,entryremarks as rem
                   from timecard where line = " . $line;
        }


        $data = $this->coreFunctions->opentable($select);
        return $data;
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

        $line = $config['params']['dataparams']['line'];
        $rem = $config['params']['dataparams']['rem'];
        if ($rem == '') {
            return ['status' => false, 'msg' => 'Please input valid remarks', 'data' => []];
        }
        $data = [
            'entryremarks' => $rem,
            'entryot' => $config['params']['dataparams']['entryot'],
            'entryndiffot' => $config['params']['dataparams']['entryndiffot']
        ];

        if ($config['params']['companyid'] == 44 || $config['params']['companyid'] == 53) { //stonepro || CAMERA SOUND
            $this->coreFunctions->sbcupdate('timecard', ['entryremarks' => $data['entryremarks'], 'entryot' => $data['entryot'], 'entryndiffot' => $data['entryndiffot'], 'otstatus' => 1, 'otstatus2' => 1], ['line' => $line]);
        } else {
            $this->coreFunctions->sbcupdate('timecard', ['entryremarks' => $data['entryremarks'], 'entryot' => $data['entryot'], 'entryndiffot' => $data['entryndiffot'], 'otstatus' => 1], ['line' => $line]);
        }

        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'reloadlisting' => true];
    }
}
