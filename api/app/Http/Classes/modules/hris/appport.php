<?php

namespace App\Http\Classes\modules\hris;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\SBCPDF;

class appport
{
    public $modulename = 'Applicant Portfolio';
    public $gridname = 'inventory';
    public $tablenum = 'cntnum';
    public $head = 'app';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_client_log';
    private $fields = [];
    public $transdoc = "";
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $reporter;
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = false;
    private $helpClass;
    private $logger;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'PENDING', 'color' => 'primary'],
        ['val' => 'noshow', 'label' => 'NO SHOW', 'color' => 'primary'],
        ['val' => 'kip', 'label' => 'KEEP IN PROFILE (KIP)', 'color' => 'primary'],
        ['val' => 'failed', 'label' => 'FAILED', 'color' => 'primary'],
        ['val' => 'backout', 'label' => 'BACK OUT', 'color' => 'primary'],
        ['val' => 'joboffer', 'label' => 'JOB OFFER', 'color' => 'primary']
    ];

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->helpClass = new helpClass;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5165,
            'view' => 5165,
            'edit' => 5165
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['action', 'listempcode', 'listempname', 'listapplied', 'listjobapplied'];
        $stockbuttons = ['view'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }


        $stockbuttons = [];
        $allownew = $this->othersClass->checkAccess($config['params']['user'], 5175);
        if ($allownew == '1') {
            $stockbuttons = ['customformappstat'];
        }

        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:5px;whiteSpace: normal;min-width:5px;';
        $cols[$listempcode]['style'] = 'width:130px;whiteSpace: normal;min-width:130px;text-align:left';
        $cols[$listempname]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;text-align:left';
        $cols[$listapplied]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;text-align:left';
        $cols[$listjobapplied]['style'] = 'width:450px;whiteSpace: normal;min-width:450px;text-align:left';

        $cols[$listempcode]['label'] = 'Applicant Code';
        $cols[$action]['btns']['customformappstat']['label'] = "Applicant Status";
        $cols[$action]['btns']['customformappstat']['checkfield'] = "stat";

        if ($allownew != '1') {
            $cols[$action]['type'] = 'coldel';
        }

        $cols = $this->tabClass->delcollisting($cols);

        return $cols;
    }

    public function loaddoclisting($config)
    {
        ini_set('memory_limit', '-1');

        $center = $config['params']['center'];
        $option = $config['params']['itemfilter'];

        if ($config['params']['date1'] == 'Invalid date') {
            $config['params']['date1'] =  $config['params']['date2'];
        }

        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $filter = '';
        $condition = '';

        switch ($option) {
            case 'draft':
                // if ($config['params']['companyid'] == 58) { //cdo
                //     $condition = ' and statid in (98,101)';
                // }
                $filter = " and date(appdate) between '$date1' and '$date2' and jstatus = '' $condition";
                break;
            case 'joboffer':
                $filter = " and date(appdate) between '$date1' and '$date2' and jstatus = 'JOB OFFER'";
                break;
            case 'noshow':
                $filter = " and date(appdate) between '$date1' and '$date2' and jstatus = 'NO SHOW'";
                break;
            case 'kip':
                $filter = " and date(appdate) between '$date1' and '$date2' and jstatus = 'KIP'";
                break;
            case 'backout':
                $filter = " and date(appdate) between '$date1' and '$date2' and jstatus = 'BACK OUT'";
                break;
            case 'failed':
                $filter = " and date(appdate) between '$date1' and '$date2' and jstatus = 'FAILED'";
                break;
        }

        $qry = $this->selectqry($config, $filter);
        $data = $this->coreFunctions->opentable($qry);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.', $qry];
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function createHeadField($config)
    {
        return [];
    }

    public function createTab($access, $config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        return [];
    }

    private function selectqry($config, $addonfilter, $loadhead = false)
    {
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = [
                'empcode',
                'emplast',
                'empfirst',
                'empmiddle',
                'appdate',
                'jobtitle'
            ];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $qry = "select empid,empcode,concat(emplast,', ',empfirst,' ',empmiddle) as empname,
                    jobtitle, date(appdate) as appdate,
                     if(idno = '','false','true') as stat
                from app  
                where 1=1 " . $filtersearch . " $addonfilter
                order by concat(emplast,', ',empfirst,' ',empmiddle)";
        return $qry;
    }

    public function loadheaddata($config)
    {
        return [];
    }

    public function stockstatusposted($config) {}
}//end class
