<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class wbreceived
{

    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'Received';
    public $gridname = 'tableentry';
    private $fields = ['rem'];
    private $table = 'cntnum';

    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_tablelog';

    public $style = 'width:100%;max-width:70%;';
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
        $attrib = array('load' => 12, 'save' => 15);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $trno = $config['params']['trno'];

        $fields = ['receivedate', 'rem2', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'UPDATE');
        data_set($col1, 'receivedate.readonly', false);

        $fields = [];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
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

    public function paramsdata($config)
    {
        $trno = $config['params']['trno'];
        $qry = "select trno, receivedate, rem2 from cntnuminfo where trno=? union all select trno, receivedate, rem2 from hcntnuminfo where trno=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }

    public function loaddata($config)
    {
        $data = [];
        $trno = $config['params']['dataparams']['trno'];
        $receivedate = $config['params']['dataparams']['receivedate'];
        $rem = $config['params']['dataparams']['rem2'];

        $isposted = $this->othersClass->isposted2($trno, $this->table);

        $tablenum = "cntnuminfo";
        if ($isposted) {
            $tablenum = "hcntnuminfo";
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $data['rem2'] = $this->othersClass->sanitizekeyfield("rem", $rem);
        $data['receivedate'] = $this->othersClass->sanitizekeyfield("receivedate", $receivedate);;
        $this->coreFunctions->sbcupdate($tablenum, $data, ['trno' => $trno]);

        $config['params']['trno'] = $trno;
        $txtdata = $this->paramsdata($config);
        return ['status' => true, 'msg' => 'Data has been updated.', 'data' => [], 'txtdata' => $txtdata];
    }
}
