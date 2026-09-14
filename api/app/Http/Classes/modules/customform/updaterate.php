<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class updaterate
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;

    public $modulename = 'UPDATE RATE';
    public $gridname = 'inventory';
    private $logger;

    public $tablelogs = 'hrisnum_log';

    public $style = 'width:30%;max-width:500px;';
    public $issearchshow = false;
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
        $fields = array('divname', 'rate', 'cola', 'allowance', 'refresh');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'divname.label', 'Detachment');
        data_set($col1, 'divname.readonly', true);
        data_set($col1, 'rate.label', 'RATE');
        data_set($col1, 'cola.label', 'COLA');
        data_set($col1, 'cola.readonly', true);
        data_set($col1, 'allowance.label', 'ALLOWANCE');
        data_set($col1, 'allowance.readonly', true);
        data_set($col1, 'refresh.label', 'SAVE CHANGES');

        return array('col1' => $col1);
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function paramsdata($config)
    {
        $result = $this->getheaddata($config);
        return $result;
    }

    public function getheaddata($config)
    {
        $trno = $config['params']['trno'];

        $qry = "select head.trno, division.divname, info.salary as rate, info.cola as cola, info.incentive as allowance
        from ddhead as head
        left join division on division.divid = head.divid
        left join divinfo as info on info.divid = head.divid
        where head.trno = ?";

        $data = $this->coreFunctions->opentable($qry,  array($trno));
        return $data;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['dataparams']['trno'];
        $rate = $config['params']['dataparams']['rate'];
        $user = $config['params']['user'];

        $divid = $this->coreFunctions->getfieldvalue('ddhead', 'divid', 'trno=?', array($trno));

        $data = array(
            'salary' => $rate,
            'editby' => $user,
            'editdate' => $this->othersClass->getCurrentTimeStamp()
        );

        $this->coreFunctions->sbcupdate('divinfo', $data, array('divid' => $divid));

        $detailData = array(
            'rate' => $rate,
            'editby' => $user,
            'editdate' => $this->othersClass->getCurrentTimeStamp()
        );

        $this->coreFunctions->sbcupdate('dddetail', $detailData, array('trno' => $trno));

        $config['params']['doc'] = 'DD';
        $this->logger->sbcwritelog($trno, $config, 'HEAD', 'UPDATE RATE - New Rate:' . $rate . ' (applied to all employees under this DDO)');

        return array('status' => true, 'msg' => 'Rate successfully updated for all employees.', 'reloadhead' => true);
    }

    public function data($config)
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
}
