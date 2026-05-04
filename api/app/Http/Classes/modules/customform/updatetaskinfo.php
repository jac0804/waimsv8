<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\inventory\pc;
use App\Http\Classes\sqlquery;
use Exception;

use Datetime;
use Carbon\Carbon;

class updatetaskinfo
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;
    private $sqlquery;

    public $modulename = "UPDATE TASK INFO";
    public $gridname = 'inventory';
    private $fields = [];
    private $head = 'hdailytask';
    public $style = 'width:100%;max-width:30%;';
    public $issearchshow = false;
    public $showclosebtn = true;
    public $tablelogs = 'task_log';
    public $tablelogs_del = 'del_task_log';

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = ['amount', 'jono', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'Save');
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        $trno = $config['params']['trno'];
        $query = "select trno,apvtrno,amt as amount,jono,userid from hdailytask where trno = $trno ";

        return $this->coreFunctions->opentable($query);
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

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['clientid'];

        $amount = $config['params']['dataparams']['amount'];
        $jono = $config['params']['dataparams']['jono'];
        $apvtrno = $config['params']['dataparams']['apvtrno'];


        if ($apvtrno == 0) {
            $data = [
                'jono' => $jono,
                'amt' => $amount,
            ];
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $clientid]);
            return ['status' => true, 'msg' => 'Task info updated successfully.', 'closecustomform' => true, 'reloadhead' => true];
            $this->logger->sbcmasterlog($clientid, $config, ' Amount: ' . $amount . ' JO#: ' . $jono);
        } else {
            return ['status' => false, 'msg' => 'Error assigning user, Please try again.'];
        }

        // return [];

    }
}
