<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewenddate
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $logger;
    private $warehousinglookup;

    public $modulename = 'END PERIOD DATE';
    public $gridname = 'inventory';
    private $fields = ['barcode', 'itemname'];
    private $table = 'stockrem';

    // public $tablelogs = 'table_log';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';

    public $style = 'width:100%;max-width:20%;';
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
        $fields = ['enddate', 'refresh'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'UPDATE');
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        $trno = $config['params']['row']['trno'];
        $date = $this->othersClass->getCurrentDate();
        return $this->coreFunctions->opentable("select date('$date') as enddate , $trno as trno ");
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
        $trno = $config['params']['dataparams']['trno'];
        $enddate = $config['params']['dataparams']['enddate'];

        $data = [
            'dateend' => $enddate,
            'editdate' => $this->othersClass->getCurrentTimeStamp(),
            'editby' => $config['params']['user']
        ];

        $this->coreFunctions->sbcupdate('allowsetup', $data, ['trno' => $trno]);
        return ['status' => true, 'msg' => 'Successfully update period end date', 'data' => []];
    }
}
