<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class cvvrelease
{

    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'Release';
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
        $attrib = array('load' => 117, 'save' => 120);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = ['orno', 'ordate', 'releasedate', 'yourref', 'ourref', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'UPDATE');
        data_set($col1, 'releasedate.readonly', false);
        data_set($col1, 'ordate.readonly', false);
        data_set($col1, 'yourref.readonly', false);
        data_set($col1, 'ourref.readonly', false);
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
        $qry = "select head.trno, info.orno,date(info.ordate) as ordate,date(info.releasedate) as releasedate,
        head.yourref,head.ourref from lahead as head left join cntnuminfo as info on head.trno=info.trno
        where head.trno=?
        union all
        select head.trno, info.orno,date(info.ordate) as ordate,date(info.releasedate) as releasedate,
        head.yourref,head.ourref from glhead as head left join hcntnuminfo as info on head.trno=info.trno
        where head.trno=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }

    public function loaddata($config)
    {

        $data = [];
        $headdata = [];
        $trno = $config['params']['dataparams']['trno'];
        $releasedate = $config['params']['dataparams']['releasedate'];
        $orno = $config['params']['dataparams']['orno'];
        $ordate = $config['params']['dataparams']['ordate'];

        $yourref = $config['params']['dataparams']['yourref'];
        $ourref = $config['params']['dataparams']['ourref'];

        $isposted = $this->othersClass->isposted2($trno, $this->table);

        $tablenum = "cntnuminfo";
        $head = "lahead";
        if ($isposted) {
            $tablenum = "hcntnuminfo";
            $head = "glhead";
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $data['releasedate'] = $this->othersClass->sanitizekeyfield("releasedate", $releasedate);
        $data['orno'] = $this->othersClass->sanitizekeyfield("orno", $orno);
        $data['ordate'] = $this->othersClass->sanitizekeyfield("ordate", $ordate);

        $headdata['yourref'] = $this->othersClass->sanitizekeyfield("yourref", $yourref);
        $headdata['ourref'] = $this->othersClass->sanitizekeyfield("ourref", $ourref);

        $exist = $this->coreFunctions->getfieldvalue($tablenum, "trno", "trno=?", [$trno]);
        if (floatval($exist) != 0) {
            $this->coreFunctions->sbcupdate($tablenum, $data, ['trno' => $trno]);
        } else {
            $data['trno'] = $trno;
            $this->coreFunctions->sbcinsert($tablenum, $data);
        }

        $this->coreFunctions->sbcupdate($head, $headdata, ['trno' => $trno]);
        $config['params']['trno'] = $trno;

        $txtdata = $this->paramsdata($config);
        return ['status' => true, 'msg' => 'Data has been updated.', 'data' => [], 'txtdata' => $txtdata];
    }
}
