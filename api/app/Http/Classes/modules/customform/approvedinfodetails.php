<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

use Datetime;

class approvedinfodetails
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'UPDATE INFO DETAILS';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = 'hrisnum';
    private $table = 'vrhead';
    private $htable = 'hvrhead';

    public $tablelogs = 'hrisnum_log';

    public $style = 'width:100%;max-width:30%;';
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
        $fields = ['jobtitle', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'jobtitle.type', 'lookup');
        data_set($col1, 'jobtitle.class', 'csjobtitle sbccsreadonly');
        data_set($col1, 'jobtitle.action', 'lookupjobtitle');
        data_set($col1, 'jobtitle.lookupclass', 'lookupjob');
        data_set($col1, 'refresh.label', 'Update');
        data_set($col1, 'refresh.style', 'width:100%;max-width:70%;');
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $companyid = $config['params']['companyid'];

        $select = "select hq.job,hq.job as jobcode, jt.jobtitle,hq.trno
        from hpersonreq as hq 
        left join jobthead as jt on jt.docno = hq.job                 
        where hq.trno=?";
        $data = $this->coreFunctions->opentable($select, [$trno]);


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
        $backlisting = false;
        $trno = $config['params']['dataparams']['trno'];

        $info = [
            'job' => $config['params']['dataparams']['job'],
            'editdate' => $this->othersClass->getCurrentTimeStamp(),
            'editby' => $config['params']['user']
        ];

        $checking = $this->coreFunctions->opentable("select qa,date(enddate) as enddate,status3 from hpersonreq as d  where d.trno=? ", [$trno], '', true);
        if ($checking[0]->qa != 0 || $checking[0]->enddate != null || $checking[0]->status3 != '') {
            return ['status' => false, 'msg' => 'Updating the job title failed, Transaction has already Applicant', 'data' => [],  'backlisting' => $backlisting, 'reloadhead' => true];
        }
        $this->coreFunctions->sbcupdate('hpersonreq', $info, ['trno' => $trno]);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [],  'backlisting' => $backlisting, 'reloadhead' => true];
    }
}
