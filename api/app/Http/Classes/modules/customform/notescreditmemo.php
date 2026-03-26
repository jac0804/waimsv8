<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class notescreditmemo
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'Notes';
    public $gridname = 'tableentry';
    private $fields = ['trno', 'instructions'];
    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_table_log';

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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $trno = $config['params']['clientid'];

        $isposted = $this->othersClass->isposted2($trno, "transnum");
        $fields = ['instruct', 'refresh'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'instruct.type', 'textarea');
        data_set($col1, 'instruct.readonly', false);
        data_set($col1, 'refresh.label', 'Save');
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $trno = $config['params']['clientid'];

        $head = 'cntnuminfo';
        $hhead = 'hcntnuminfo';

        $qry = "select trno,ifnull(instructions,'') as instruct from cntnuminfo where trno=?
          union all 
          select trno, ifnull(instructions ,'') as instruct from hcntnuminfo where trno=?";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);

        if (empty($data)) {
            $data = $this->coreFunctions->opentable("select '" . $trno . "' as trno,'' as instruct");
        }

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
        $trno = $config['params']['dataparams']['trno'];

        $data = [
            'trno' => $trno,
            'instructions' => $config['params']['dataparams']['instruct']
        ];
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        $isposted = $this->othersClass->isposted2($trno, 'cntnum');
        $this->logger->sbcwritelog($trno, $config, 'CREATE INSTRUCTION', ' INSTRUCTION: ' . $data['instructions']);
        if ($isposted) {
            $this->coreFunctions->sbcupdate("hcntnuminfo", $data, ['trno' => $trno]);
        } else {
            $qry = "select trno as value from cntnuminfo where trno = ? LIMIT 1";
            $count = $this->coreFunctions->datareader($qry, [$trno]);

            if ($count != '') {
                $this->coreFunctions->sbcupdate("cntnuminfo", $data, ['trno' => $trno]);
            } else {
                $this->coreFunctions->insertGetId("cntnuminfo", $data);
                $this->logger->sbcwritelog($trno, $config, 'CREATE INSTRUCTION', ' INSTRUCTION: ' . $data['instructions']);
            }
        }

        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => []];
    }
}
