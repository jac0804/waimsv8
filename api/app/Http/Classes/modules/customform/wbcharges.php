<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class wbcharges
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'Charges';
    public $gridname = 'tableentry';
    private $fields = ['rem'];
    private $table = 'cntnum';

    public $tablelogs = 'table_log';
    public $tablelogs_del = 'del_tablelog';

    public $style = 'width:100%;max-width:100%;';
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
        $isposted = $this->othersClass->isposted2($trno, $this->table);

        $fields = ['weight', 'amount', 'qty', 'delcharge', 'ext', ['refresh']];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'SAVE');
        data_set($col1, 'amount.label', 'Value');
        data_set($col1, 'qty.label', 'Cu. MSMT');
        data_set($col1, 'delcharge.label', 'Delivery');
        data_set($col1, 'ext.label', 'Total: (display)');
        data_set($col1, 'qty.readonly', false);

        if ($isposted) {
            data_set($col1, 'weight.readonly', true);
            data_set($col1, 'amount.readonly', true);
            data_set($col1, 'qty.readonly', true);
            data_set($col1, 'delcharge.readonly', true);
        }

        $fields = [];
        $col2 = $this->fieldClass->create($fields);
        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
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
        $qry = "select trno,weight,amount,qty,delcharge,sum(weight+amount+qty+delcharge) as ext from (
                select trno, weight, valamt as amount,cumsmt as qty,delivery as delcharge 
                from cntnuminfo where trno=? 
                union all 
                select trno, weight, valamt as amount,cumsmt as qty,delivery as delcharge 
                from hcntnuminfo where trno=?) as k
                group by trno,weight,amount,qty,delcharge";
        $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
        return $data;
    }

    public function loaddata($config)
    {
        $data = [];
        $trno = $config['params']['dataparams']['trno'];
        $weight = $config['params']['dataparams']['weight'];
        $valamt = $config['params']['dataparams']['amount'];
        $cumsmt = $config['params']['dataparams']['qty'];
        $del = $config['params']['dataparams']['delcharge'];

        $isposted = $this->othersClass->isposted2($trno, $this->table);
        $tablenum = "cntnuminfo";
        if ($isposted) {
            $tablenum = "hcntnuminfo";
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        $data['weight'] = $this->othersClass->sanitizekeyfield("weight", $weight);
        $data['valamt'] = $this->othersClass->sanitizekeyfield("valamt", $valamt);
        $data['cumsmt'] = $this->othersClass->sanitizekeyfield("cumsmt", $cumsmt);
        $data['delivery'] = $this->othersClass->sanitizekeyfield("delivery", $del);

        $this->coreFunctions->sbcupdate($tablenum, $data, ['trno' => $trno]);

        $config['params']['trno'] = $trno;
        $txtdata = $this->paramsdata($config);
        return ['status' => true, 'msg' => 'Data has been updated.', 'data' => [], 'txtdata' => $txtdata];
    }
}
