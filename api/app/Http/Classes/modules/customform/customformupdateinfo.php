<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class customformupdateinfo
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'UPDATE';
    public $gridname = 'inventory';
    private $fields = ['editby', 'editdate', 'color'];
    public $tablenum = 'cntnum';
    private $table = 'vrhead';
    private $htable = 'hvrhead';
    private $logger;

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
        $fields = ['color', ['refresh', 'frefresh']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, "refresh.label", "SAVE CHANGES");
        data_set($col1, "frefresh.label", "REMOVE ALL COLORS");
        data_set($col1, "frefresh.type", "button");

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];
        $color = $config['params']['row']['color'];

        $select = "select '' as rem, " . $trno . " as trno, " . $line . " as line, '" . $color . "' as color";
        $data = $this->coreFunctions->opentable($select);
        return $data;
        return [];
    }


    public function data()
    {
        return $this->coreFunctions->opentable("select 'text' as clientname");
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['clientname']]];
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
        $trno = $config['params']['dataparams']['trno'];
        $line = $config['params']['dataparams']['line']; //fcalc
        $user = $config['params']['user'];

        $action = $config['params']['action2'];
        $data = [
            'editby' => $user,
            'editdate' => $this->othersClass->getCurrentTimeStamp(),
            'color' => $config['params']['dataparams']['color']
        ];

        switch ($action) {
            case 'fcalc':
                $data['color'] = '';
                $this->coreFunctions->sbcupdate("stockinfotrans", $data, []);
                $this->coreFunctions->sbcupdate("hstockinfotrans", $data, []);
                break;
            default:
                $this->coreFunctions->sbcupdate("stockinfotrans", $data, ['trno' => $trno, 'line' => $line]);
                $this->coreFunctions->sbcupdate("hstockinfotrans", $data, ['trno' => $trno, 'line' => $line]);
                break;
        }


        $result = $this->data($config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $result, 'reloadlisting' => true];
    }
}
