<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class sjrelease
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
        $companyid = $config['params']['companyid'];
        $isposted = $this->othersClass->isposted2($config['params']['trno'], $this->table);

        $fields = [];
        switch ($companyid) {
            case 37: //mega crystal
                $this->modulename = '';
                if ($isposted) $fields = ['rem', 'refresh'];
                break;
            default:
                $fields = ['releasedate', 'rem2', 'refresh'];
                break;
        }

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'rem.readonly', false);
        data_set($col1, 'refresh.label', 'UPDATE');
        data_set($col1, 'releasedate.readonly', false);

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
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['trno'];

        switch ($companyid) {
            case 37: //mega crystal
                $qry = "select trno, rem from glhead where trno=?";
                break;
            default:
                $qry = "select trno, releasedate, rem2 from cntnuminfo where trno=? union all select trno, releasedate, rem2 from hcntnuminfo where trno=?";
                break;
        }

        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }

    public function loaddata($config)
    {
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['dataparams']['trno'];

        $data = [];
        $isposted = $this->othersClass->isposted2($trno, $this->table);

        switch ($companyid) {
            case 37: //mega crystal
                $rem = $config['params']['dataparams']['rem'];
                $tablenum = $isposted ? 'glhead' : '';
                $data['rem'] = $this->othersClass->sanitizekeyfield("rem", $rem);
                break;
            default:
                $releasedate = $config['params']['dataparams']['releasedate'];
                $rem = $config['params']['dataparams']['rem2'];
                $tablenum = $isposted ? 'hcntnuminfo' : 'cntnuminfo';
                $data['rem2'] = $this->othersClass->sanitizekeyfield('rem', $rem);
                $data['releasedate'] = $this->othersClass->sanitizekeyfield('releasedate', $releasedate);
                break;
        }

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $this->coreFunctions->sbcupdate($tablenum, $data, ['trno' => $trno]);

        $config['params']['trno'] = $trno;
        $txtdata = $this->paramsdata($config);

        return ['status' => true, 'msg' => 'Data has been updated.', 'data' => [], 'txtdata' => $txtdata, 'reloadhead' => true];
    }
}
