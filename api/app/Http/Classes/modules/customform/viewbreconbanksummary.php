<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class viewbreconbanksummary
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'BANK RECON. SUMMARY';
    public $gridname = 'customformlisting';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%px;';
    public $issearchshow = true;
    public $showclosebtn = false;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function createTab($config)
    {
        $tab = [
            $this->gridname => ['gridcolumns' => ['dateid', 'bal', 'adjust']]
        ];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][0]['label'] = 'Transaction Date';
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = ['contra', 'acnoname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'contra.lookupclass', 'BANKBOOK');
        data_set($col1, 'acnoname.readonly', true);

        $fields = ['start', 'end'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['refresh'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $acno = $config['params']['addedparams'][0];
        if ($acno != '') {
            $acno = '\\' . $acno;
        }
        $acnoname = $config['params']['addedparams'][1];
        $date1 = $config['params']['addedparams'][2];
        $date2 = $config['params']['addedparams'][3];

        $qry = "select '" . $acno . "' as contra, '" . $acnoname . "' as acnoname,
        '" . $date1 . "' as `start`, '" . $date2 . "' as `end` ";
        return $this->coreFunctions->opentable($qry);
    }

    public function data()
    {
        return [];
    }

    public function loaddata($config)
    {
        $acno = $config['params']['dataparams']['contra'];
        if ($acno != '') {
            $acno = '\\' . $acno;
        }
        $acnoname = $config['params']['dataparams']['acnoname'];

        $start = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['start']);
        $end = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['end']);

        $txtqry = "select '" . $acno . "' as contra, '" . $acnoname . "' as acnoname,
        '" . $start . "' as `start`, '" . $end . "' as `end`";
        $txtdata = $this->coreFunctions->opentable($txtqry);

        if ($acno == '') {
            return ['status' => false, 'msg' => 'Select valid account', 'data' => [], 'txtdata' => $txtdata];
        }

        if ($start == '') {
            return ['status' => false, 'msg' => 'Select valid start date', 'data' => [], 'txtdata' => $txtdata];
        }

        if ($end == '') {
            return ['status' => false, 'msg' => 'Select valid end date', 'data' => [], 'txtdata' => $txtdata];
        }

        $start = $this->othersClass->sbcdateformat($start);
        $end = $this->othersClass->sbcdateformat($end);

        if ($start > $end) {
            return ['status' => false, 'msg' => 'Invalid date range. Start date must not be greater then end date', 'data' => [], 'txtdata' => $txtdata];
        }

        $data = $this->openBankBook($config);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'txtdata' => $txtdata];
    } //end function

    public function openBankBook($config)
    {
        $acno = $config['params']['dataparams']['contra'];

        $date1 = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['start']);
        $date2 = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['end']);

        $date1 = $this->othersClass->sbcdateformat($date1);
        $date2 = $this->othersClass->sbcdateformat($date2);

        $sql = "select left(dateid,10)as dateid, format(bal,2) as bal, format(adjust,2) as adjust
        from brecon where acno='\\" . $acno . "' and date(dateid) between '$date1' and '$date2' and line=3 order by dateid";

        return $this->coreFunctions->openTable($sql);
    }
}
