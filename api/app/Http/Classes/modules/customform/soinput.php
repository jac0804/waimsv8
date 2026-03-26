<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class soinput
{

    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'SO input';
    public $gridname = 'tableentry';

    private $field = ['proformainvoice'];
    private $table = 'headinfotrans';


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
        $isposted = $this->othersClass->isposted2($trno, 'transnum');

        $fields = ['proformainvoice'];
        if (!$isposted) {
            array_push($fields, 'refresh');
        }
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'proformainvoice.readonly', false);
        data_set($col1, 'proformainvoice.label', 'SO #');
        data_set($col1, 'refresh.label', 'UPDATE');

        if ($isposted) {
            data_set($col1, 'proformainvoice.readonly', true);
        }

        return array('col1' => $col1);
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
        $qry = "select trno, proformainvoice from headinfotrans  where trno=? 
        union all
        select trno, proformainvoice from hheadinfotrans 
        where trno=?  ";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }

    public function loaddata($config)
    {
        $data = [];
        $trno = $config['params']['dataparams']['trno'];
        $proformainvoice = $config['params']['dataparams']['proformainvoice'];
        $isposted = $this->othersClass->isposted2($trno, 'transnum');

        $msg = 'Data has been updated.';

        $table = "headinfotrans";
        if ($isposted) {
            $msg = 'Cannot change; already posted.';
            goto exithere;
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $data['proformainvoice'] = $this->othersClass->sanitizekeyfield("proformainvoice", $proformainvoice);

        $this->coreFunctions->sbcupdate($table, $data, ['trno' => $trno]);
        exithere:
        $config['params']['trno'] = $trno;
        $txtdata = $this->paramsdata($config);
        return ['status' => true, 'msg' => $msg, 'data' => [], 'txtdata' => $txtdata];
    }
}
