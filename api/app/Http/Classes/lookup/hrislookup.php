<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use Illuminate\Http\Request;
use App\Http\Requests;


class hrislookup
{
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
  }

  //HRIS

  public function lookupledgerapplicant($config)
  {
    $lookupsetup = array(
      'type' => 'singlesearch',
      'actionsearch' => 'searchledgerapplicant',
      'title' => 'List of Applicants',
      'style' => 'width:900px;max-width:900px;'
    );


    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'loadledgerdata'
    );
    // lookup columns
    $cols = array();
    $col = array('name' => 'empcode', 'label' => 'Code', 'align' => 'left', 'field' => 'empcode', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $col =  array('name' => 'empname', 'label' => 'Name', 'align' => 'left', 'field' => 'empname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $col = array('name' => 'address', 'label' => 'Address', 'align' => 'left', 'field' => 'address', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    return ['status' => true, 'msg' => 'ok', 'data' => [], 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }



  public function lookupatype($config)
  {
    $companyid = $config['params']['companyid'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Type',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('type' => 'type')
    );

    switch ($config['params']['lookupclass']) {
      case 'ttypelookup':
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array('ttype' => 'type')
        );
        break;
    }


    // lookup columns
    $cols = array(
      array('name' => 'type', 'label' => 'Type', 'align' => 'left', 'field' => 'type', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'DIRECT' as type union all select 'INDIRECT' as type";

    if ($companyid == 62) { // one sky
      $qry .= " union all 
                select 'TEMPORARY' as type";
    }
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupjstatus($config)
  {
    $title = 'Status';
    switch ($config['params']['doc']) {
      case 'HJ':
      case 'EMPLOYEE':
        $title = 'Employment Status';
        break;
    }
    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:400px;max-width:400px;'
    );

    switch ($config['params']['lookupclass']) {
      case 'lookup_jostatus':
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array('empstat' => 'status')
        );
        break;
      case 'empstatus':
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array('empstatus' => 'line', 'empdesc' => 'status')
        );
        break;
      case 'jostatus':
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array('empstat' => 'line', 'empdesc' => 'status')
        );
        break;
      case 'lookupappstat':
        $plotsetup = array(
          'plottype' => 'plotledger',
          'plotting' => array('jstatus' => 'status')
        );
        break;

      default:
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array('jstatus' => 'status')
        );
        break;
    }

    switch ($config['params']['lookupclass']) {
      case 'jostatus':
      case 'empstatus':
        $cols = array(
          array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'status', 'label' => 'Status', 'align' => 'left', 'field' => 'status', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $qry = "select line, code, empstatus as status from empstatentry";
        break;

      default:
        // lookup columns
        $cols = array(
          array('name' => 'status', 'label' => 'Status', 'align' => 'left', 'field' => 'status', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $qry = "select 'JOB OFFER' as status union all select 'NO SHOW' as status union all select 'KIP' as status union all select 'FAILED' as status union all select 'BACK OUT' as status";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupjobtitle($config)
  {
    $plotting = array(
      'jobid' => 'line',
      'jobcode' => 'docno',
      'jobtitle' => 'jobtitle',
      'jobdesc' => 'jobdesc',
      'emptitle' => 'docno',
      'job' => 'docno',
    );
    $plottype = 'plothead';

    $title = 'List of Jobs';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    switch ($config['params']['lookupclass']) {
      case 'empstatlookup':
        $plotting = array(
          'tjobcode' => 'docno',
          'jobtitle' => 'jobtitle',
          'tjobname' => 'jobtitle',
        );
        break;
      case 'newdesignation':
        $plotting = array(
          'ndesid' => 'line',
          'jobcode' => 'jobtitle'
        );
        $plottype = 'plotledger';
        break;
      case 'epjob':
        $plotting = array(
          'jobid2' => 'line',
          'jobcode' => 'docno',
          'jobtitle' => 'jobtitle',
          'jobdesc' => 'jobdesc'
        );
        break;
      case 'lookupjobtitlerep':
        $plotting = array(
          'jobid' => 'line',
          'jobcode' => 'jobtitle',
          'jobtitle' => 'docno'
        );
        break;
      case 'lookupjob':
        $plottype = 'plotledger';
        break;
    }

    $plotsetup = array(
      'plottype' => $plottype,
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'docno', 'label' => 'Code', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'jobtitle', 'label' => 'Job Title', 'align' => 'left', 'field' => 'jobtitle', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    switch ($config['params']['lookupclass']) {
      case 'empstatlookup':
        $qry = "select 0 as selectid,0 as orderid, docno, jobtitle, trno
          from jobthead
          ORDER BY docno";
        break;
      default:
        $qry = "select j.line,j.docno,j.jobtitle,ifnull(group_concat(jt.description),'') as jobdesc 
                from jobthead as j 
                left join jobtdesc as jt on jt.trno = j.line  
                group by j.line,j.docno,j.jobtitle order by j.docno";
        break;
    }
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 1270, '/ledgergrid/hris/jobtitlemaster');
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  } //end function


  public function lookuprequirements($config)
  {
    //default
    $plotting = array(
      'reqid' => 'line',
      'pin' => 'code',
      'reqs' => 'req'
    );

    $title = 'List of Requirements';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'req', 'label' => 'Requirement', 'align' => 'left', 'field' => 'req', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line, code,req from emprequire order by code";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } // end function  

  public function lookuprequirements_appledger($config)
  {
    $title = 'List of Requirements';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'action' => 'getrequirements',
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'req', 'label' => 'Requirement', 'align' => 'left', 'field' => 'req', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line as keyid, line, code,req from emprequire order by code";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } // end function  

  public function lookuppreemptest($config)
  {
    //default
    $plotting = array(
      'emptestid' => 'line',
      'pin' => 'code',
      'preemptest' => 'test'
    );

    $title = 'List of Test';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'test', 'label' => 'Test', 'align' => 'left', 'field' => 'test', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line, code,test from preemp order by code";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } // end function  

  public function lookuppreemptest_appledger($config)
  {
    $title = 'List of Test';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'action' => 'gettest',
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'test', 'label' => 'Test', 'align' => 'left', 'field' => 'test', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line as keyid, line, code,test from preemp order by code";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } // end function  

  public function lookuptrainingtype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Type',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('type' => 'type')
    );

    // lookup columns
    $cols = array(
      array('name' => 'type', 'label' => 'Type', 'align' => 'left', 'field' => 'type', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    if ($config['params']['companyid'] == 10) {
      $qry = "select 'Individual' as type 
              union all 
            select 'Company' as type";
    } else {
      $qry = "select 'Seminar/Workshop' as type 
              union all 
            select 'Convention/Conference' as type";
    }
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function lookupexpensetype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Expense Type',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('expensetype' => 'expensetype')
    );

    // lookup columns
    $cols = array(
      array('name' => 'expensetype', 'label' => 'Select Expenses', 'align' => 'left', 'field' => 'expensetype', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select '' as expensetype 
            union all 
            select 'Expected Transportation Expenses' as expensetype 
            union all 
            select 'Gasoline' as expensetype";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuptrackingtype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Select Tracking',
      'style' => 'width:500px;max-width:500px;height:600px'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('trackingtype' => 'tracking')
    );

    // lookup columns
    $cols = array(
      array('name' => 'tracking', 'label' => 'Tracking', 'align' => 'left', 'field' => 'tracking', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'DIRECT FIELD IN ONLY' as tracking 
            union all 
            select 'DIRECT FIELD OUT ONLY' as tracking
            union all 
            select 'EARLY TIME OUT' as tracking
            union all 
            select 'LATE TIME IN' as tracking
            union all 
            select 'KEY CUSTODIANS LATE' as tracking
            union all 
            select 'BLACK OUT (1 ATTLOG)' as tracking
            union all 
            select 'BLACK OUT WHOLEDAY' as tracking
            union all 
            select 'RELIEVER FOR CASHIER (WHOLE DAY)' as tracking
            union all 
            select 'DAMAGE BIOMETRIC' as tracking
            union all 
            select 'PRORATE' as tracking
            union all 
            select 'NEW EMPLOYEE' as tracking";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function lookuptrainingentrytype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Type',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('ttype' => 'ttype')
    );

    // lookup columns
    $cols = array(
      array('name' => 'ttype', 'label' => 'Type', 'align' => 'left', 'field' => 'ttype', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'Seminar/Workshop' as ttype 
              union all 
            select 'Convention/Conference' as ttype";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function incidentreportlookup($config)
  {
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Incident Report',
      'style' => 'width:800px;max-width:800px;'
    );

    switch ($doc) {
      case 'HD':
        if ($companyid == 58) { //cdo
          $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => array(
              'refx' => 'trno',
              'irno' => 'docno',
              'irdesc' => 'idescription',
              'empid' => 'tempid',
              'empcode' => 'tclientcode',
              'empname' => 'tempname',
              'empjob' => 'tjtjobtitle',
              'jobtitle' => 'tjtjobtitle',
              'fempid' => 'fempid',
              'fempcode' => 'fclientcode',
              'fempname' => 'fempname',
              'fempjob' => 'fjtjobtitle',
              'deptid' => 'deptid',
              'dept' => 'dept',
              'hplace' => 'iplace',
              'htime' => 'htime',
              'hdatetime' => 'hdatetime',

              'artcode' => 'artcode',
              'articlename' => 'articlename',
              'sectioncode' => 'sectioncode',
              'sectionname' => 'sectionname',
              'artid' => 'artid',
              'sectionno' => 'sectid',
              'empname' => 'empname',
              'empcode' => 'empcode',
              'jobtitle' => 'jobtitle',
              'dept' => 'dept',
              'empid' => 'empid',
              'deptid' => 'deptid',

              'violationno' => 'violationno',
              'penalty' => 'penalty',
              'numdays' => 'numdays',
              'findings' => 'explanation'
            )
          );
        } else {
          $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => array(
              'refx' => 'trno',
              'irno' => 'docno',
              'irdesc' => 'idescription',
              'empid' => 'tempid',
              'empcode' => 'tclientcode',
              'empname' => 'tempname',
              'empjob' => 'tjtjobtitle',
              'jobtitle' => 'tjtjobtitle',
              'fempid' => 'fempid',
              'fempcode' => 'fclientcode',
              'fempname' => 'fempname',
              'fempjob' => 'fjtjobtitle',
              'deptid' => 'deptid',
              'dept' => 'dept',
              'hplace' => 'iplace',
              'htime' => 'htime',
              'hdatetime' => 'hdatetime'
            )
          );
        }
        break;
      case 'HN':
        if ($companyid == 58) { //cdo
          $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => array(
              'refx' => 'trno',
              'irno' => 'docno',
              'irdesc' => 'idescription',
              'fempid' => 'fempid',
              'fempcode' => 'fclientcode',
              'fempname' => 'fempname',
              'fjobtitle' => 'fjtjobtitle',
              // 'hplace' => 'iplace',
              // 'htime' => 'htime',
              'artcode' => 'artcode',
              'articlename' => 'articlename',
              'sectioncode' => 'sectioncode',
              'sectionname' => 'sectionname',
              'artid' => 'artid',
              'line' => 'sectid',
              'empname' => 'empname',
              'empcode' => 'empcode',
              'empjob' => 'jobtitle',
              'dept' => 'dept',
              'empid' => 'empid',
              'deptid' => 'deptid'
            )
          );
        } else {
          $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => array(
              'refx' => 'trno',
              'irno' => 'docno',
              'irdesc' => 'idescription',
              'fempid' => 'fempid',
              'fempcode' => 'fclientcode',
              'fempname' => 'fempname',
              'fjobtitle' => 'fjtjobtitle',

              'hplace' => 'iplace',
              'htime' => 'htime',
            )
          );
        }
        break;

      default:
        $hdatetime = "'hdatetime' => 'hdatetime'";

        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array(
            'refx' => 'trno',
            'irno' => 'docno',
            'irdesc' => 'idescription',
            'fempid' => 'fempid',
            'fempcode' => 'fclientcode',
            'fempname' => 'fempname',
            'fempjob' => 'fjtjobtitle',

            'hplace' => 'iplace',
            'htime' => 'htime',
            $hdatetime
          )
        );
        break;
    }

    // lookup columns

    if ($companyid == 58) { //cdo
      $cols = array(
        array('name' => 'docno', 'label' => 'Docno', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
        array('name' => 'empname', 'label' => 'Person Involve', 'align' => 'left', 'field' => 'empname', 'sortable' => true, 'style' => 'font-size:16px;'),
        array('name' => 'articlename', 'label' => 'Article Description', 'align' => 'left', 'field' => 'articlename', 'sortable' => true, 'style' => 'font-size:16px;')
      );
    } else {
      $cols = array(
        array('name' => 'docno', 'label' => 'Docno', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
        array('name' => 'idescription', 'label' => 'Description', 'align' => 'left', 'field' => 'idescription', 'sortable' => true, 'style' => 'font-size:16px;')
      );
    }


    $filter_h = '';
    $filter_d = '';

    if ($companyid == 58) { //cdo

      switch ($doc) {
        case 'HN':
          $filter_h = ' and (head.tempid not in (select empid from notice_explain union all select empid from hnotice_explain) or
                        head.trno not in (select refx from notice_explain union all select refx from hnotice_explain))';
          $filter_d = ' and (detail.empid not in (select empid from notice_explain union all select empid from hnotice_explain) or
                        head.trno not in (select refx from notice_explain union all select refx from hnotice_explain))';
          break;
        case 'HD':
          $filter_h = ' and (head.tempid not in (select empid from disciplinary union all select empid from hdisciplinary) or
                        head.trno not in (select refx from disciplinary union all select refx from hdisciplinary))';
          $filter_d = ' and (detail.empid not in (select empid from disciplinary union all select empid from hdisciplinary) or
                        head.trno not in (select refx from disciplinary union all select refx from hdisciplinary))';
          break;
      }

      $qry = "select 0 AS selectid,0 AS orderid, head.trno,head.docno,head.dateid,head.idescription,
                    head.iplace, date(head.idate) as hdatetime, time(head.idate) as htime,
                    concat(temp.empfirst, ' ',temp.empmiddle, ' ', temp.emplast) as empname,
                    tcl.client as empcode,head.tempid as empid,head.fempid,head.tempjobid,
                    tjt.jobtitle ,temp.deptid,dept.clientname as dept ,head.artid,head.sectid,
                    chead.code as artcode,chead.description as articlename,
                    cdetail.section as sectioncode,cdetail.description as sectionname,head.fempjobid,
                  (case when head.fempid = 0 then head.fempname else concat(femp.empfirst, ' ',femp.empmiddle, ' ', femp.emplast) end) as fempname,
                  (case when head.fempjobid = 0 then head.fjobtitle else fjt.jobtitle end) as fjtjobtitle,
                  fcl.client as fclientcode,(select violationno from notice_explain where refx=head.trno
                  union all select violationno from hnotice_explain where refx=head.trno limit 1) as violationno,
                  (select penalty from notice_explain where refx=head.trno
                  union all select penalty from hnotice_explain where refx=head.trno limit 1) as penalty,
                  (select numdays from notice_explain where refx=head.trno
                  union all select numdays from hnotice_explain where refx=head.trno limit 1) as numdays,
                  (select explanation from notice_explain where refx=head.trno
                  union all select explanation from hnotice_explain where refx=head.trno limit 1) as explanation
              from hincidenthead as head
              left join hrisnum as num on num.trno=head.trno
              left join employee as temp on temp.empid=head.tempid
              left join client as tcl on tcl.clientid = head.tempid
              left join jobthead as tjt on tjt.line = head.tempjobid
              left join client as dept on dept.clientid = temp.deptid
              left join employee as femp on femp.empid=head.fempid
            left join client as fcl on fcl.clientid = head.fempid
            left join jobthead as fjt on fjt.line = head.fempjobid
              left join codehead as chead on chead.artid=head.artid
              left join codedetail as cdetail on cdetail.line=head.sectid and cdetail.artid=chead.artid
              where num.center='" . $config['params']['center'] . "' $filter_h
              union all
              select 0 AS selectid,0 AS orderid, head.trno,head.docno,head.dateid,head.idescription,
                    head.iplace, date(head.idate) as hdatetime, time(head.idate) as htime,
                    concat(temp.empfirst, ' ',temp.empmiddle, ' ', temp.emplast) as empname,
                    tcl.client as empcode,detail.empid,head.fempid,detail.jobid as tempjobid,
                    tjt.jobtitle,temp.deptid,dept.clientname as dept ,head.artid,head.sectid,
                    chead.code as artcode,chead.description as articlename,
                    cdetail.section as sectioncode,cdetail.description as sectionname,head.fempjobid,
                  (case when head.fempid = 0 then head.fempname else concat(femp.empfirst, ' ',femp.empmiddle, ' ', femp.emplast) end) as fempname,
                  (case when head.fempjobid = 0 then head.fjobtitle else fjt.jobtitle end) as fjtjobtitle,
                  fcl.client as fclientcode,(select violationno from notice_explain where refx=head.trno
                  union all select violationno from hnotice_explain where refx=head.trno limit 1) as violationno,
                  (select penalty from notice_explain where refx=head.trno
                  union all select penalty from hnotice_explain where refx=head.trno limit 1) as penalty,
                  (select numdays from notice_explain where refx=head.trno
                  union all select numdays from hnotice_explain where refx=head.trno limit 1) as numdays,
                  (select explanation from notice_explain where refx=head.trno
                  union all select explanation from hnotice_explain where refx=head.trno limit 1) as explanation
              from hincidenthead as head
              left join hincidentdtail as detail on detail.trno=head.trno
              left join hrisnum as num on num.trno=head.trno
              left join employee as temp on temp.empid=detail.empid
              left join client as tcl on tcl.clientid = detail.empid
              left join jobthead as tjt on tjt.line = detail.jobid
              left join client as dept on dept.clientid = temp.deptid
              left join employee as femp on femp.empid=head.fempid
            left join client as fcl on fcl.clientid = head.fempid
            left join jobthead as fjt on fjt.line = head.fempjobid
              left join codehead as chead on chead.artid=head.artid
              left join codedetail as cdetail on cdetail.line=head.sectid and cdetail.artid=chead.artid
              where num.center='" . $config['params']['center'] . "' $filter_d and detail.empid is not null
              order by docno";
    } else {
      $qry = "select 0 AS selectid,0 AS orderid, head.trno,head.docno,head.dateid,head.idescription,
                  head.iplace, date(head.idate) as hdatetime, time(head.idate) as htime,
                  concat(temp.empfirst, ' ',temp.empmiddle, ' ', temp.emplast) as tempname,
                  tcl.client as tclientcode,head.tempid,head.fempid,head.tempjobid, 
                  tjt.jobtitle as tjtjobtitle,head.fempjobid,
                  (case when head.fempid = 0 then head.fempname else concat(femp.empfirst, ' ',femp.empmiddle, ' ', femp.emplast) end) as fempname,
                  (case when head.fempjobid = 0 then head.fjobtitle else fjt.jobtitle end) as fjtjobtitle,
                  fcl.client as fclientcode,temp.deptid,dept.clientname as dept 
            from hincidenthead as head
            left join hrisnum as num on num.trno=head.trno
            left join employee as temp on temp.empid=head.tempid
            left join client as tcl on tcl.clientid = head.tempid
            left join jobthead as tjt on tjt.line = head.tempjobid
            left join client as dept on dept.clientid = temp.deptid
            left join employee as femp on femp.empid=head.fempid
            left join client as fcl on fcl.clientid = head.fempid
            left join jobthead as fjt on fjt.line = head.fempjobid
            where num.center='" . $config['params']['center'] . "'
      ";
    }

    // if ($config['params']['companyid'] == 58) { //cdo
    //   $addselect = ",head.artid,head.sectid,chead.code as artcode,chead.description as articlename,
    //                 cdetail.section as sectioncode,cdetail.description as sectionname";
    //   $leftjoin = " left join codehead as chead on chead.artid=head.artid
    //                 left join codedetail as cdetail on cdetail.line=head.sectid and cdetail.artid=chead.artid";
    // }


    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function articlelookup($config)
  {
    $fields = "";

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Article',
      'style' => 'width:80%;max-width:80%;'
    );

    switch ($config['params']['lookupclass']) {
      case 'hnarticle':
        $fields = ", '' as sectioncode, '' as sectionname ";
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array(
            'artid' => 'artid',
            'artcode' => 'code',
            'articlename' => 'description',
            'sectioncode' => 'sectioncode',
            'sectionname' => 'sectionname'
          )
        );
        break;
      case 'hiarticle':
        $lookupsetup = array(
          'type' => 'single',
          'title' => 'Code of Conduct',
          'style' => 'width:100%;max-width:100%;height:80%'
        );
        $fields = ", '' as sectioncode, '' as sectionname ";
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array(
            'artid' => 'artid',
            'artcode' => 'articledesc',
            'sectid' => 'sectid',
            'sectioncode' => 'sectioncode',
            'sectionname' => 'sectionname'
          )
        );
        break;
      default:
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array('artid' => 'artid', 'artcode' => 'code', 'articlename' => 'description')
        );
        break;
    }

    // lookup columns

    switch ($config['params']['lookupclass']) {
      case 'hiarticle':
        $cols = array(
          array('name' => 'artcode', 'label' => 'Article Code', 'align' => 'left', 'field' => 'artcode', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'articledesc', 'label' => 'Article Description', 'align' => 'left', 'field' => 'articledesc', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'sectioncode', 'label' => 'Section Code', 'align' => 'left', 'field' => 'sectioncode', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'sectionname', 'label' => 'Section Description', 'align' => 'left', 'field' => 'sectionname', 'sortable' => true, 'style' => 'font-size:16px;')
        );
        break;

      default:
        $cols = array(
          array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;')
        );
        break;
    }

    switch ($config['params']['lookupclass']) {
      case 'hiarticle':
        $qry = "SELECT 0 AS selectid,0 as orderid,head.artid,head.code as artcode,head.description as articledesc,
                      detail.section as sectioncode,detail.description as sectionname,detail.line as sectid
                FROM codehead as head
                left join codedetail as detail on detail.artid=head.artid";
        break;
      default:
        $qry = "SELECT 0 AS selectid,0 as orderid,artid,code,description $fields FROM codehead";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 1260, '/ledgergrid/hrisentry/codeconduct');
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  }

  public function sectionlookup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Section',
      'style' => 'width:80%;max-width:80%;'
    );

    $plotting = array(
      'sectionno' => 'line',
      'sectioncode' => 'section',
      'sectionname' => 'description'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => $plotting
    );
    switch ($config['params']['lookupclass']) {
      case 'hnsection':
        if ($config['params']['companyid'] == 58) { //cdo
          $plotting['violationno'] = 'timesviolated';
          $plotting['numdays'] = 'nodayspenalty';
          $plotting['penalty'] = 'panaltydesc';
        }

        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => $plotting
        );
        break;
    }

    // lookup columns
    $cols = array(
      array('name' => 'section', 'label' => 'Code', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $artcode = $config['params']['addedparams'][0];
    $qry = "SELECT 0 AS selectid,0 as orderid,artid,section,description,line, 0 as timesviolated, 0 as nodayspenalty, '' as panaltydesc,
            d1a,d1b,d2a,d2b,d3a,d3b,d4a,d4b,d5a,d5b
            from codedetail where artid= (select artid from codehead where code = '" . $artcode . "')";
    $data = $this->coreFunctions->opentable($qry);

    if ($config['params']['companyid'] == 58) { //cdo
      foreach ($data as $key => $value) {
        $violationqry = "select count(trno) as value from (
                  select trno from disciplinary where empid=" . $config['params']['addedparams'][2] . " and artid=" . $config['params']['addedparams'][1] . " and sectionno=" . $value->line . "
                  union all
                  select trno from hdisciplinary where empid=" . $config['params']['addedparams'][2] . " and artid=" . $config['params']['addedparams'][1] . " and sectionno=" . $value->line . ") as nda";
        $violations = $this->coreFunctions->datareader($violationqry, [], '', true);
        $violations = $violations + 1;

        switch ($violations) {
          case 1:
            $timesviolated = 1;
            $nodayspenalty = $value->d1b;
            $penaltydesc = $value->d1a;
            break;
          case 2:
            $timesviolated = 2;
            $nodayspenalty = $value->d2b;
            $penaltydesc = $value->d2a;
            break;
          case 3:
            $timesviolated = 3;
            $nodayspenalty = $value->d3b;
            $penaltydesc = $value->d3a;
            break;
          case 4:
            $timesviolated = 4;
            $nodayspenalty = $value->d4b;
            $penaltydesc = $value->d4a;
            break;
          case 5:
            $timesviolated = 5;
            $nodayspenalty = $value->d5b;
            $penaltydesc = $value->d5a;
            break;
          default:
            $timesviolated = $violations;
            $nodayspenalty = 0;
            $penaltydesc = '';
            break;
        }

        $data[$key]->timesviolated = $timesviolated;
        $data[$key]->nodayspenalty = $nodayspenalty;
        $data[$key]->panaltydesc = $penaltydesc;
      }
    }


    $btnadd = $this->sqlquery->checksecurity($config, 1470, '/tableentries/payrollsetup/section');
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  }

  public function violationlookup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Violation',
      'style' => 'width:80%;max-width:80%;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'violationno' => 'violationno',
        'penalty' => 'penalty',
        'numdays' => 'numdays'
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'violationno', 'label' => '# of Violation', 'align' => 'left', 'field' => 'violationno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'penalty', 'label' => 'Penalty', 'align' => 'left', 'field' => 'penalty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'numdays', 'label' => '# of Days', 'align' => 'left', 'field' => 'numdays', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $artcode = $config['params']['addedparams'][0];
    $sectcode = $config['params']['addedparams'][1];
    $qry = "
      select 0 AS selectid,0 AS orderid,'1' as violationno, d1a as penalty, d1b as numdays
      from codedetail
      where artid=(select artid from codehead
      where code='" . $artcode . "') and section='" . $sectcode . "'
      union all
      select 0 AS selectid,0 AS orderid, '2' as violationno, d2a as penalty, d2b as numdays
      from codedetail
      where artid=(select artid from codehead
      where code='" . $artcode . "') and section='" . $sectcode . "'
      union all
      select 0 AS selectid,0 AS orderid, '3' as violationno,d3a as penalty,d3b as numdays
      from codedetail
      where artid=(select artid from codehead
      where code='" . $artcode . "') and section='" . $sectcode . "'
      union all select 0 AS selectid,0 AS orderid, '4' as violationno, d4a as penalty, d4b as numdays
      from codedetail
      where artid=(select artid from codehead
      where code='" . $artcode . "') and section='" . $sectcode . "'
      union all
      select 0 AS selectid,0 AS orderid, '5' as violationno, d5a as penalty, d5b as numdays
      from codedetail
      where artid=(select artid from codehead
      where code='" . $artcode . "') and section='" . $sectcode . "'
     ";
    // var_dump($qry);
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }




  //payroll

  public function lookuppaymode($config)
  {
    $title = 'Status';
    switch ($config['params']['doc']) {
      case 'HJ':
        $title = 'Mode of Payment';
        break;
    }

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:400px;max-width:400px;'
    );

    $plotting = array('paymode' => 'paymode');

    switch ($config['params']['doc']) {
      case 'PAYROLLPROCESS':
      case 'LEAVEBATCHCREATION':
        $plotting = array('paymodeemp' => 'paymode', 'paymode' => 'paycode');
        break;
    }

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array(
      array('name' => 'paymode', 'label' => 'Mode of Payment', 'align' => 'left', 'field' => 'paymode', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'Daily' as paymode, 'D' as paycode
    union all 
    select 'Semi-monthly' as paymode, 'S' as paycode 
    union all 
    select 'Monthly' as paymode, 'M' as paycode 
    union all select 
    'Weekly' as paymode, 'W' as paycode
    union all 
    select 'Piece Rate' as paymode, 'P' as paycode";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupempdivision($config)
  {
    //default
    switch ($config['params']['lookupclass']) {
      case 'lookup_jodiv':
        $plotting = array(
          'divid' => 'divid',
          'dcode' => 'divcode',
          'dname' => 'divname'
        );
        break;
      case 'lookupempdivisionprocess':
        $plotting = array('empdivid' => 'divid', 'empdivname' => 'divname');
        break;
      case 'lookupcontributecomp':
        $plotting = array('contricompid' => 'divid', 'divrep' => 'divname');
        break;
      default:
        $plotting = array(
          'divid' => 'divid',
          'division' => 'divcode',
          'divname' => 'divname'
        );
        break;
    }


    $title = 'List of Company';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'divcode', 'label' => 'Code', 'align' => 'left', 'field' => 'divcode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'divname', 'label' => 'Name', 'align' => 'left', 'field' => 'divname', 'sortable' => true, 'style' => 'font-size:16px;'));


    $qry = "select divid, divcode,divname from division order by divcode";

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 1410, '/tableentries/payrollsetup/division');

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  } // end function

  public function lookupbatchrep($config)
  {
    //default
    $empid =  $config['params']['adminid'];
    $companyid =  $config['params']['companyid'];

    $lookupclass = $config['params']['lookupclass'];
    $user = $config['params']['user'];
    switch ($lookupclass) {
      case 'look':
        break;
      case 'leavebatch':
        $plotting = array(
          'batchid' => 'line',
          'batch' => 'batch',
        );
        break;
      default:
        $plotting = array(
          'line' => 'line',
          'batch' => 'batch',
          'ispickupdate' => 'ispickupdate'
        );
        break;
    }

    $style = 'width:1000px;max-width:1000px;';


    $title = 'Payroll Batch';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => $style
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => $plotting
    );
    $filter = '';
    if ($lookupclass == 'dashboard') {
      $plotsetup['plottype'] = 'plotledger';
      $plotsetup['plotting'] = ['batch' => 'batch', 'batchid' => 'line'];
      if (isset($config['params']['addedparams'])) {
        $filter = "where divid = " . $config['params']['addedparams'][0] . " and postdate is null";
      }
    }
    $companylist = [44, 51, 53]; //ulitc,stonepro,camera
    if (in_array($companyid, $companylist)) {
      $batch = $this->coreFunctions->opentable("select divid,deptid from employee where empid = ?", [$empid]);
      if (!empty($batch)) {
        if ($lookupclass == 'lookupbatchrep') {
          if ($user == 'sbc') {
            $filter = "where postdate is not null ";
          } else {
            $filter = "where postdate is not null and divid = '" . $batch[0]->divid . "' ";

            if ($companyid == 53) { // camera
              if ($batch[0]->divid == 0) {
                return ['status' => false, 'msg' => 'No Company Tagged'];
              }
              $filter = "where isportal = 1 and divid = '" . $batch[0]->divid . "' and deptid = '" . $batch[0]->deptid . "'";
            }
          }
        } else {
          $effdate = isset($config['params']['addedparams']) ? $config['params']['addedparams'][0] : '';
          $date = $this->othersClass->sbcdateformat($effdate);
          if ($companyid == 51) { //ulitc
            $filter = "where postdate is null and divid = '" . $batch[0]->divid . "' and '$date' between startdate and enddate ";
          } else {
            $filter = "where postdate is null and divid = '" . $batch[0]->divid . "' ";
          }
        }
      }
    }

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'batch', 'label' => 'Batch', 'align' => 'left', 'field' => 'batch', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'startdate', 'label' => 'Start Date', 'align' => 'left', 'field' => 'startdate', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'enddate', 'label' => 'End Date', 'align' => 'left', 'field' => 'enddate', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'paymode', 'label' => 'Pay Mode', 'align' => 'left', 'field' => 'paymode', 'sortable' => true, 'style' => 'font-size:16px;'));
    if ($companyid == 58) { //cdohris
      array_push($cols, array('name' => 'branch', 'label' => 'Branch', 'align' => 'left', 'field' => 'branch', 'sortable' => true, 'style' => 'font-size:16px;'));
      array_push($cols, array('name' => 'divname', 'label' => 'Company', 'align' => 'left', 'field' => 'divname', 'sortable' => true, 'style' => 'font-size:16px;'));
    }

    switch ($lookupclass) {
      case 'lookupbatchrepcdo':
        $branchid = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : 0;
        $divid = isset($config['params']['addedparams'][1]) ? $config['params']['addedparams'][1] : 0;

        $filter2 = ' where 1=1 ';
        if ($branchid) {
          $filter2 .= ' and batch.branchid = ' . $branchid;
        }

        if ($divid) {
          $filter2 .= ' and batch.divid = ' . $divid;
        }

        $qry = "select batch.line, batch.batch, date_format(batch.startdate,'%m-%d-%Y') as startdate,
                      date_format(batch.enddate,'%m-%d-%Y') as enddate,
                      concat(date_format(batch.startdate,'%m-%d-%Y'),' - ',date_format(batch.enddate,'%m-%d-%Y')) as ispickupdate,
                      batch.paymode,batch.divid,batch.branchid,comp.divname,br.clientname as branch, batch.enddate as sortdate
                from batch
                left join division as comp on comp.divid=batch.divid
                left join client as br on br.clientid=batch.branchid
                $filter2 order by sortdate desc";
        break;
      case 'lookupbatchempcdo':
        $qry = "select line, batch, date_format(startdate,'%m-%d-%Y') as startdate, 
                     date_format(enddate,'%m-%d-%Y') as enddate,
                     concat(date_format(startdate,'%m-%d-%Y'),' - ',date_format(enddate,'%m-%d-%Y')) as ispickupdate, 
                     paymode,divid, enddate as sortdate 
                from batch 
                left join paytranhistory as pay on pay.batchid=batch.line
                where batch.postdate is not null and pay.empid=$empid
                group by batch.line,batch.batch,batch.startdate,batch.enddate,batch.paymode,batch.divid
                $filter order by sortdate desc";
        break;

      default:
        $qry = "select line, batch, date_format(startdate,'%m-%d-%Y') as startdate, 
                     date_format(enddate,'%m-%d-%Y') as enddate,
                     concat(date_format(startdate,'%m-%d-%Y'),' - ',date_format(enddate,'%m-%d-%Y')) as ispickupdate, 
                     paymode,divid, enddate as sortdate from batch $filter order by sortdate desc";
        break;
    }


    if ($lookupclass == 'lookupbatchrepcdo') {
    } else {
      if ($lookupclass = 'lookupbatchempcdo') {
        $filter = 'where postdate is not null';
      } else {
      }
    }


    $data = $this->coreFunctions->opentable($qry);
    if (empty($data)) {
      return ['status' => false, 'msg' => 'No batch created.'];
    }

    $btnadd = $this->sqlquery->checksecurity($config, 1410, '/tableentries/payrollsetup/division');

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  } // end function


  public function lookupempsection($config)
  {
    //default
    $plotting = array(
      'sectid' => 'sectid',
      'orgsection' => 'sectcode',
      'sectname' => 'sectname'
    );
    $title = 'List of Section';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'sectcode', 'label' => 'Code', 'align' => 'left', 'field' => 'sectcode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'sectname', 'label' => 'Name', 'align' => 'left', 'field' => 'sectname', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select sectid, sectcode,sectname from section order by sectcode";

    $data = $this->coreFunctions->opentable($qry);

    $btnadd = $this->sqlquery->checksecurity($config, 1470, '/tableentries/payrollsetup/section');
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  } // end function


  public function lookupemplevel($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Level',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('level' => 'level')
    );

    // lookup columns
    $cols = array(
      array('name' => 'level', 'label' => 'Level', 'align' => 'left', 'field' => 'level', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select '1' as level union all select '2' as level  union all select '3' as level  union all select '4' as level  union all select '5' as level  union all select '6' as level  union all select '7' as level  union all select '8' as level  union all select '9' as level  union all select '10' as level";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupempbatch($config)
  {
    //default
    $plotting = array(
      'lastbatch' => 'batch'
    );
    $title = 'List of Batch';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'batch', 'label' => 'Batch', 'align' => 'left', 'field' => 'batch', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'startdate', 'label' => 'Start Date', 'align' => 'left', 'field' => 'startdate', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'enddate', 'label' => 'End Date', 'align' => 'left', 'field' => 'enddate', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select batch, startdate,enddate from batch order by batch";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } // end function


  public function lookupclassrate($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Class Rate',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('classrate' => 'class')
    );

    // lookup columns
    $cols = array(
      array('name' => 'class', 'label' => 'Class Rate', 'align' => 'left', 'field' => 'class', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select 'Daily' as class union all 
            select 'Monthly' as class
            ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupdays($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Select',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('hours' => 'hours')
    );

    // lookup columns
    $cols = array(
      array('name' => 'hours', 'label' => 'No. of Day', 'align' => 'left', 'field' => 'hours', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select 'Whole day' as hours union all select 'Half day' as hours";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupapplicant($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Applicants',
      'style' => 'width:700px;max-width:700px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => [
        'empid' => 'empid',
        'empcode' => 'empcode',
        'emplast' => 'emplast',
        'empfirst' => 'empfirst',
        'empmiddle' => 'empmiddle',
        'empname' => 'empname',
        'client' => 'empcode',
        'clientname' => 'empname'
      ]
    );

    // lookup columns
    $cols = array(
      array('name' => 'empcode', 'label' => 'Applicant Code', 'align' => 'left', 'field' => 'empcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'empname', 'label' => 'Applicant Name', 'align' => 'left', 'field' => 'empname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select empid, empcode, emplast, empfirst, empmiddle, concat(emplast,', ',empfirst,' ',empmiddle) as empname from app where jstatus = 'JOB OFFER' ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupapplicanthj($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Applicants',
      'style' => 'width:800px;max-width:800px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => [
        'empid' => 'empid',
        'empcode' => 'empcode',
        'emplast' => 'emplast',
        'empfirst' => 'empfirst',
        'empmiddle' => 'empmiddle',
        'empname' => 'empname',
        'emptitle' => 'jobcode',
        'jobtitle' => 'jobtitle',
        'jobdesc' => 'jobdesc',
      ]
    );

    // lookup columns
    $cols = array(
      array('name' => 'empcode', 'label' => 'Applicant Code', 'align' => 'left', 'field' => 'empcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'empname', 'label' => 'Applicant Name', 'align' => 'left', 'field' => 'empname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'jobcode', 'label' => 'Job Code', 'align' => 'left', 'field' => 'jobcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'jobtitle', 'label' => 'Job Title', 'align' => 'left', 'field' => 'jobtitle', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select empid, empcode, emplast, empfirst, empmiddle, 
      concat(emplast,', ',empfirst,' ',empmiddle) as empname, 
      jobcode, jobtitle, jobdesc
      from app 
      where jstatus = 'JOB OFFER' and idno='' ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupallapplicant($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of applicants',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => [
        'empid' => 'empid',
        'empcode' => 'empcode',
        'emplast' => 'emplast',
        'empfirst' => 'empfirst',
        'empmiddle' => 'empmiddle',
        'empname' => 'empname',
        'client' => 'empcode',
        'clientname' => 'empname'
      ]
    );

    // lookup columns
    $cols = array(
      array('name' => 'empcode', 'label' => 'Applicant Code', 'align' => 'left', 'field' => 'empcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'empname', 'label' => 'Applicant Name', 'align' => 'left', 'field' => 'empname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select empid, empcode, emplast, empfirst, empmiddle, concat(emplast,', ',empfirst,' ',empmiddle) as empname from app ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function lookupqtype($config)
  {

    $lookupsetup = array(
      'type' => 'single',
      'title' => "Type",
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' =>  ['qtype' => 'field1']
    );

    $cols = [['name' => 'field1', 'label' => 'Type', 'align' => 'left', 'field' => 'field1', 'sortable' => true, 'style' => 'font-size:16px;']];

    $qry = "select 'Support & Clerical Exam' as field1 
                union all
                select 'Sales Marketing Exam' as field1 
                union all
                select 'Intelligence Quotient Exam' as field1 
                union all
                select 'Emotional Quotient Exam' as field1 
                union all
                select 'Adversity Quotient Exam' as field1 
                union all
                select 'Sentence completion' as field1";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  //A
  public function pendingturnoveritems($config)
  {
    $trno = $config['params']['tableid'];

    $title = 'List of Turn Over of Items';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'action' => 'getturnoveritems',
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'amt', 'label' => 'Estimated Amount', 'align' => 'left', 'field' => 'amt', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select empid as value from returnitemhead where trno=?";
    $empid = $this->coreFunctions->datareader($qry, [$trno]);

    $qry = "select concat(d.trno,d.line) as keyid,h.docno, date(h.dateid) as dateid, d.line, d.trno, itemname, d.amt, d.rem 
   from hturnoveritemdetail as d left join hturnoveritemhead as h on h.trno=d.trno 
   left join hrisnum as c on c.trno=h.trno
   where h.empid=? and d.qa=0";

    $data = $this->coreFunctions->opentable($qry, [$empid]);;

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingturnoveritems2($config)
  {
    $trno = $config['params']['trno'];

    $title = 'List of Turn Over of Items';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getturnoveritems',
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'amt', 'label' => 'Estimated Amount', 'align' => 'left', 'field' => 'amt', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select empid as value from returnitemhead where trno=?";
    $empid = $this->coreFunctions->datareader($qry, [$trno]);

    $qry = "select concat(d.trno,d.line) as keyid,h.docno, date(h.dateid) as dateid, d.line, d.trno, itemname, d.amt, d.rem 
        from hturnoveritemdetail as d left join hturnoveritemhead as h on h.trno=d.trno 
        left join hrisnum as c on c.trno=h.trno
        where h.empid=? and d.qa=0";
    $data = $this->coreFunctions->opentable($qry, [$empid]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  //A
  public function lookupskillreq($config)
  {

    $title = 'List of Skill Requirements';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'action' => 'getskillsreq',
    );

    // lookup columns
    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Description', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select line as keyid, line, code, skill as itemname from skillrequire order by skill;";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupapplists($config)
  {

    $title = 'Applicant Lists';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'action' => 'getapplists',
    );

    // lookup columns
    $cols = array(
      array('name' => 'empname', 'label' => 'Applicant', 'align' => 'left', 'field' => 'empname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select empid as keyid, empid as appid, concat(empfirst,' ',empmiddle,' ',emplast) as empname
            from app where idno <> '' order by emplast";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  //A
  public function lookupempgrid($config)
  {
    $title = 'List of Employees';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'action' => 'addempgrid',
    );

    // lookup columns
    $cols = array(
      array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'jobtitle', 'label' => 'Job Title', 'align' => 'left', 'field' => 'jobtitle', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select c.clientid as keyid, c.clientid, c.client, c.clientname, ifnull(e.jobid, 0) as jobid, j.jobtitle
    from client as c 
    left join employee as e on e.empid=c.clientid 
    left join jobthead as j on j.line=e.jobid
    where c.isemployee=1 order by c.clientname";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function lookupempgrids($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Employees',
      'style' => 'width:900px;max-width:900px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'jobtitle', 'label' => 'Job Title', 'align' => 'left', 'field' => 'jobtitle', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addempgrid',
    );

    if ($config['params']['companyid'] == 58 && $config['params']['doc'] == 'RS') { //cdo
      $qry = "select e.empid as keyid,e.empid as clientid,c.client,
                   if(e.jobid<>0,e.jobid,e.jobid2) as jobid,concat(e.emplast,',',e.empfirst,' ',e.empmiddle) as clientname,
                   j.jobtitle,if(e.branchid<>0,e.branchid,e.branchid2) as branchid,e.supervisorid,role.deptid,if(e.roleid<>0,e.roleid,e.roleid2) as roleid
          from employee as e
          left join client as c on c.clientid=e.empid
          left join jobthead as j on j.line=e.jobid2
          left join rolesetup as role on role.line=e.roleid2
          where e.isactive=1 and e.resigned is null order by e.emplast,e.empfirst,e.empmiddle";
    } else {
      $qry = "select e.empid as keyid,e.empid as clientid,c.client,
                   e.jobid,concat(e.emplast,',',e.empfirst,' ',e.empmiddle) as clientname,j.jobtitle,
                   e.branchid,e.supervisorid,e.deptid,e.roleid
          from employee as e
          left join client as c on c.clientid=e.empid
          left join jobthead as j on j.line=e.jobid
          where e.isactive=1 and e.resigned is null order by e.emplast,e.empfirst,e.empmiddle";
    }


    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } // end function

  public function lookupclassification($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Classification',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('class' => 'classification')
    );

    // lookup columns
    $cols = array(
      array('name' => 'classification', 'label' => 'Classification', 'align' => 'left', 'field' => 'classification', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select '' as 'classification' 
            union all 
            select 'Additional' as classification
            union all 
            select 'Replacement' as classification
            union all 
            select 'New' as classification
            union all 
            select 'Reliever' as classification
            union all 
            select 'Contructual' as classification
            union all 
            select 'New Job Function/Position' as classification
            union all 
            select 'OJT' as classification                        
            ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuphpref($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Hiring Preference',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('hpref' => 'hpref')
    );

    // lookup columns
    $cols = array(
      array('name' => 'hpref', 'label' => 'Preference', 'align' => 'left', 'field' => 'hpref', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select '' as 'hpref' 
            union all 
            select 'Internal' as hpref
            union all 
            select 'External' as hpref
            ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupreasonhire($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Reason for Hiring',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('reasontype' => 'reason', 'reason' => 'line')
    );

    // lookup columns
    $cols = array(
      array('name' => 'reason', 'label' => 'Reason for Hiring', 'align' => 'left', 'field' => 'reason', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select line,category as reason from reqcategory where isreasonhiring=1";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupempstatus($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Employment status upon hiring',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('empstattype' => 'empstatus', 'empstatusid' => 'line')
    );

    // lookup columns
    $cols = array(
      array('name' => 'empstatus', 'label' => 'Employment Status', 'align' => 'left', 'field' => 'empstatus', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select line,category as empstatus from reqcategory where isempstatus=1";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuprank($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Rank',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('rank' => 'rank')
    );

    // lookup columns
    $cols = array(
      array('name' => 'rank', 'label' => 'Rank', 'align' => 'left', 'field' => 'rank', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select '' as 'rank' 
            union all 
            select 'Managerial' as rank
            union all 
            select 'Supervisory' as rank
            union all 
            select 'Office Staff' as rank
            union all 
            select 'Skilled/Rank & File' as rank
            ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupemployee($config)
  {

    $companyid = $config['params']['companyid'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Employee',
      'style' => 'width:900px;max-width:900px;height:700px'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'empid' => 'empid',
        'empcode' => 'empcode',
        'empname' => 'clientname',
        'jobcode' => 'jobcode',
        'jobtitle' => 'jobtitle',
        'dept' => 'deptcode',
        'deptid' => 'deptid'
      )
    );

    $lookupclass = $config['params']['lookupclass'];
    switch (strtoupper($config['params']['doc'])) {
      case 'CONTRACTMONITORING':
        $plotsetup = [
          'plottype' => 'callback',
          'action' => 'saveevaluator'
        ];
        break;
      case 'HI':
        switch ($lookupclass) {
          case 'hifromjtitle':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'fempid' => 'empid',
                'fempcode' => 'empcode',
                'fempname' => 'clientname',
                'jobcode' => 'jobcode',
                'jobtitle' => 'jobtitle',
                'dept' => 'deptcode',
                'fjobtitle' => 'jobtitle',
                'deptid' => 'deptid',
                'fempjobid' => 'jobid'
              )
            );
            break;
          case 'hitojtitle':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'tempid' => 'empid',
                'tempcode' => 'empcode',
                'tempname' => 'clientname',
                'jobcode' => 'jobcode',
                'jobtitle' => 'jobtitle',
                'dept' => 'deptcode',
                'tjobtitle' => 'jobtitle',
                'deptid' => 'deptid',
                'tempjobid' => 'jobid'
              )
            );
            break;
          case 'emp1lookup':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'notedid' => 'empid',
                'notedby1' => 'clientname'
              )
            );
            break;
        }
        break;
      case 'HN':
        switch ($lookupclass) {
          case 'fromemployee':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'fempid' => 'empid',
                'fempcode' => 'empcode',
                'fempname' => 'clientname',
                'fempjob' => 'jobtitle',
                'fempjobid' => 'jobid'
              )
            );
            break;
          default:
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'empid' => 'empid',
                'empcode' => 'empcode',
                'empname' => 'clientname',
                'jobcode' => 'jobcode',
                'empjob' => 'jobtitle',
                'dept' => 'deptname',
                'deptid' => 'deptid',
                'jobid' => 'jobid'
              )
            );
            break;
        }
        break;
      case 'HC':
        switch ($lookupclass) {
          case 'witnessnamelookup':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'witness' => 'empid',
                'witnessname' => 'clientname'
              )
            );
            break;
          case 'witnessnamelookup2':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'witness2' => 'empid',
                'witnessname2' => 'clientname'
              )
            );
            break;
          default:
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'empid' => 'empid',
                'empcode' => 'empcode',
                'empname' => 'clientname',
                'jobcode' => 'jobcode',
                'jobtitle' => 'jobtitle',
                'dept' => 'deptcode',
                'deptid' => 'deptid',
                'hired' => 'hireddate',
                'empheadid' => 'empheadid',
                'emphead' => 'emphead',
                'empheadname' => 'empheadname',
                'lastdate' => 'resigned'
              )
            );
            break;
        }
        break;
      case 'HS':
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array(
            'empid' => 'empid',
            'empcode' => 'empcode',
            'empname' => 'clientname',
            'jobcode' => 'jobcode',
            'jobtitle' => 'jobtitle',
            'jobid' => 'jobid',
            'dept' => 'deptcode',
            'deptname' => 'deptname',
            'deptid' => 'deptid',
            'ftype' => 'emptype',
            'flevel' => 'level',
            'fjobcode' => 'jobcode',
            'fjobname' => 'jobtitle',
            'fempstatcode' => 'empstatus',
            'fempstatname' => 'empstatname',
            'frank' => 'emprank',
            'fjobgrade' => 'jgrade',
            'fdeptcode' => 'deptcode',
            'fdeptname' => 'fdeptname',
            'flocation' => 'emploc',
            'fpaymode' => 'paymode',
            'fpaygroup' => 'paygroup',
            'fpaygroupname' => 'paygroup',
            'fpayrate' => 'payrate',
            'fallowrate' => 'allowance',
            'fbasicrate' => 'basicsalary',
            'fcola' => 'cola',
            'froleid' => 'roleid',
            'frolename' => 'rolename',
            'fdivid' => 'fdivid',
            'fdivname' => 'fdivname',
            'fsectid' => 'fsectid',
            'fsectname' => 'fsectname',
            'isactive' => 'isactive',
            'frprojectid' => 'projectid',
            'frprojectname' => 'projectname',
            'ftrucknameid' => 'itemid',
            'ftruckname' => 'barcode',
            'fsalarytype' => 'salarytype',
            'fhsperiod' => 'payrate',
            'feffdate' => 'effectdate',
          )
        );
        break;
      case 'RS':
        switch ($lookupclass) {
          case 'lookupsuperior':
            $plotsetup = array(
              'plottype' => 'plotledger',
              'plotting' => array(
                'supid' => 'empid',
                'supervisor' => 'clientname'
              )
            );
            break;
          case 'emp1lookup':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'notedid' => 'empid',
                'notedby1' => 'clientname'
              )
            );
            break;
        }
        break;
      case 'HQ':
        switch ($lookupclass) {
          case 'emp1lookup':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'notedid' => 'empid',
                'notedby1' => 'clientname'
              )
            );
            break;
          case 'emp2lookup':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'supid' => 'empid',
                'supervisor' => 'clientname'
              )
            );
            break;
          case 'emp3lookup':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'manid' => 'empid',
                'mmname' => 'clientname'
              )
            );
            break;
          case 'recoapproval':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'recappid' => 'empid',
                'recommendapp' => 'clientname'
              )
            );
            break;
          case 'approvedby':
            $plotsetup = array(
              'plottype' => 'plothead',
              'plotting' => array(
                'appdisid' => 'empid',
                'approvedby' => 'clientname'
              )
            );
            break;
        }
        break;
      case 'PR':
        $plotsetup = array(
          'plottype' => 'plotgrid',
          'plotting' => array(
            'suppid' => 'empid',
            'empname' => 'clientname'
          )
        );
        break;
      case 'TTC':
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array(
            'empid' => 'empid',
            'empname' => 'clientname',
            'empcode' => 'empcode',
            'company' => 'company',
            'department' => 'department',
            'sectionname' => 'sectionname'
          )
        );
        break;
    }

    // lookup columns
    $cols = array(
      array('name' => 'empcode', 'label' => 'Code', 'align' => 'left', 'field' => 'empcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'jobtitle', 'label' => 'Job Title', 'align' => 'left', 'field' => 'jobtitle', 'sortable' => true, 'style' => 'font-size:16px;'),
    );
    switch (strtoupper($config['params']['doc'])) {
      case 'HA':
      case 'HO':
      case 'HR':
      case 'HI':
      case 'HN':
      case 'HD':
      case 'HS':
        $col = array('name' => 'deptcode', 'label' => 'Department Code', 'align' => 'left', 'field' => 'deptcode', 'sortable' => true, 'style' => 'font-size:16px;');
        array_push($cols, $col);
        break;
      case 'HC':
        $col = array('name' => 'deptcode', 'label' => 'Department Code', 'align' => 'left', 'field' => 'deptcode', 'sortable' => true, 'style' => 'font-size:16px;');
        $col1 = array('name' => 'hireddate', 'label' => 'Hired Date', 'align' => 'left', 'field' => 'hireddate', 'sortable' => true, 'style' => 'font-size:16px;');
        array_push($cols, $col);
        array_push($cols, $col1);
        break;
      case 'TTC':
        $cols = array(
          array('name' => 'empcode', 'label' => 'Code', 'align' => 'left', 'field' => 'empcode', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;')
        );
        break;
    }

    $condition = '';
    if ((strtoupper($config['params']['doc'] == 'HQ') && $lookupclass == 'emp1lookup') || $lookupclass == 'lookupsuperior') {
      $condition = ' and e.issupervisor = 1';
    }

    switch (strtoupper($config['params']['doc'])) {
      case 'HC':
        $emplvl = $this->othersClass->checksecuritylevel($config);

        $hcfilter = " and e.isactive = 1";
        if ($companyid == 58) { //cdo
          if ($lookupclass == 'witnessnamelookup' || $lookupclass == 'witnessnamelookup2') {
            $hcfilter = " and e.resigned is null";
          } else {
            $hcfilter = " and e.resigned is not null";
          }
        }

        $qry = "
          select e.empid, client.client as empcode,
          CONCAT(e.emplast, ', ', e.empfirst, ' ', e.empmiddle) AS clientname,
          jt.docno as jobcode, jt.jobtitle,e.deptid,c.client as deptcode,
          c.client as dept, jt.line as jobid,
          date(e.hired) as hireddate,
          supervisor.clientid as empheadid, supervisor.client as emphead,
          supervisor.clientname as empheadname,e.resigned
          from employee as e
          left join client on client.clientid=e.empid
          left join app as a on e.aplid=a.empid
          left join client as c on c.clientid=e.deptid
          left join jobthead as jt on jt.line=e.jobid
          left join client as supervisor on supervisor.clientid = e.supervisorid
          where e.level IN $emplvl $hcfilter
          order by e.emplast,e.empfirst,e.empmiddle";

        break;
      case 'HS':
        $emplvl = $this->othersClass->checksecuritylevel($config);

        $p_label = '';
        if ($companyid == 58) { //cdo
          $p_label = 'PACKAGE RATE';
        }

        $qry = "
          select 0 AS selectid,0 AS orderid,ifnull(employee.empid, 0) as empid,client.client as empcode,
          CONCAT(emplast, ', ', empfirst, ' ', empmiddle) AS clientname,
          jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid,
          dp.clientname as deptname, dp.client as deptcode, ifnull(dp.clientid, 0) as deptid,
          date(employee.hired) as hired,
          employee.emptype,employee.jgrade,
          employee.empstatus, employee.cola,
          empstat.empstatus as empstatname,
          employee.emprank,employee.level,
          employee.resigned,employee.emploc, employee.paygroup,
          ifnull((select allowance from allowsetup where allowsetup.empid=employee.empid and date(dateend)='9999-12-31' order by trno desc limit 1),0) as allowance,
          ifnull((select basicrate from ratesetup where ratesetup.empid=employee.empid and date(dateend)='9999-12-31' order by trno desc limit 1),0) as basicsalary, 
          ifnull(employee.roleid, 0) as roleid, rs.name as rolename,  ifnull(employee.projectid, 0) as projectid, project.name as projectname,
          ifnull(employee.itemid,0) as itemid,ifnull(i.barcode,'') as barcode,
          ifnull(fsect.sectid, 0) as fsectid,
          fsect.sectname as fsectname,
          ifnull(fdiv.divid, 0) as fdivid,
          fdiv.divname as fdivname,
          dp.clientname as fdeptname,cast(employee.isactive as char) as isactive,
          case 
            when employee.paymode = 'S' then 'Semi-monthly' 
            when employee.paymode = 'W' then 'Weekly' 
            when employee.paymode = 'M' then 'Monthly' 
            when employee.paymode = 'D' then 'Daily' 
            when employee.paymode = 'P' then 'Piece Rate' 
            else ''
          end as paymode,
          case 
            when employee.classrate = 'D' then 'Daily' 
            when employee.classrate = 'M' then 'Monthly'
            when employee.classrate = 'P' then '" . $p_label . "'  
            else ''
          end as payrate,
          employee.salarytype,date(employee.effectdate) as effectdate
          FROM employee 
          left join client on client.clientid=employee.empid
          left join client as dp on dp.clientid=employee.deptid
          left join jobthead as jt on employee.jobid = jt.line
          left join rolesetup as rs on rs.line = employee.roleid
          left join section as fsect on fsect.sectid = employee.sectid
          left join division as fdiv on fdiv.divid = employee.divid
          left join empstatentry as empstat on empstat.line = employee.empstatus
          left join projectmasterfile as project on project.line=employee.projectid
          left join item as i on i.itemid=employee.itemid
          where employee.level IN $emplvl and employee.isactive = 1 order by employee.emplast,employee.empfirst,employee.empmiddle
         
        ";
        break;
      case 'HN':
        $irtrno = $config['params']['addedparams'][0];
        $emplvl = $this->othersClass->checksecuritylevel($config);

        $qry = "select head.tempid as empid,client.client as empcode,client.clientname,
                      c.client as deptcode,jt.jobtitle, ifnull(e.deptid, 0) as deptid,c.clientname as deptname
                from hincidenthead as head
                left join employee as e on e.empid=head.tempid
                left join client on client.clientid=head.tempid
                left join client as c on c.clientid=e.deptid
                left join jobthead as jt on e.jobid=jt.line
                where e.isactive =1 and head.trno=$irtrno
                union all
                select detail.empid,client.client as empcode,client.clientname,c.client as deptcode,
                        jt.jobtitle, ifnull(e.deptid, 0) as deptid,c.clientname as deptname
                from hincidenthead as head
                left join hincidentdtail as detail on detail.trno=head.trno
                left join employee as e on e.empid=detail.empid
                left join client on client.clientid=detail.empid
                left join client as c on c.clientid=e.deptid
                left join jobthead as jt on e.jobid=jt.line
                where e.isactive =1 and head.trno=$irtrno
        ";
        break;
      case 'TTC':
        $adminid = $config['params']['adminid'];
        $approver = $this->othersClass->checkapproversetup($config, $adminid, 'PORTAL SCHEDULE', 'e');

        $filter = "";
        $left = "";
        if ($approver['filter'] != "") {
          $filter .= $approver['filter'];
        }
        if ($approver['leftjoin'] != "") {
          $left .= $approver['leftjoin'];
        }
        $leftjoin = "left join section as sect on sect.sectid =e.sectid ";
        $fields = 'sect.sectname';
        if ($companyid == 53) { //camera
          $leftjoin = 'left join rateexempt as sect on sect.line = e.emprate';
          $fields = 'sect.area';
        }
        $qry = "select ifnull(e.empid, 0) as empid, client.client as empcode, e.empid,
        concat(e.emplast, ', ', e.empfirst, ' ', e.empmiddle) as clientname,dept.clientname as department,
        divi.divname as company,ifnull($fields, '') as sectionname
        from employee as e 
        left join client on client.clientid=e.empid
        left join client as dept on dept.clientid = e.deptid 
        left join division as divi on divi.divid = e.divid 
         $left
         $leftjoin
        where e.isactive =1 $filter
        order by e.emplast,e.empfirst,e.empmiddle";
        break;
      case 'CONTRACTMONITORING':
        $regline = $config['params']['dataparams']['line'];
        $qry = "select ifnull(e.empid, 0) as empid, client.client as empcode, 
        CONCAT(e.emplast, ', ', e.empfirst, ' ', e.empmiddle) AS clientname,
        jt.docno as jobcode, jt.jobtitle, ifnull(e.deptid, 0) as deptid, 
        c.client as deptcode,
        c.client as dept, ifnull(jt.line, 0) as jobid,
        date(e.hired) as hireddate, " . $regline . " as regline
        from employee as e 
        left join client on client.clientid=e.empid
        left join app as a on e.aplid=a.empid
        left join client as c on c.clientid=e.deptid
        left join jobthead as jt on e.jobid=jt.line
        where e.isactive =1 $condition
        order by e.emplast,e.empfirst,e.empmiddle 
        ";
        break;
      default:
        $emplvl = $this->othersClass->checksecuritylevel($config);

        $qry = "select ifnull(e.empid, 0) as empid, client.client as empcode, 
        CONCAT(e.emplast, ', ', e.empfirst, ' ', e.empmiddle) AS clientname,
        jt.docno as jobcode, jt.jobtitle, ifnull(e.deptid, 0) as deptid, 
        c.client as deptcode,
        c.client as dept, ifnull(jt.line, 0) as jobid,
        date(e.hired) as hireddate
        from employee as e 
        left join client on client.clientid=e.empid
        left join app as a on e.aplid=a.empid
        left join client as c on c.clientid=e.deptid
        left join jobthead as jt on e.jobid=jt.line
        where e.isactive =1 $condition
        order by e.emplast,e.empfirst,e.empmiddle 
        ";


        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    if (strtoupper($config['params']['doc']) == 'CONTRACTMONITORING') {
      $access = $this->othersClass->checkAccess($config['params']['user'], 5467);
      if ($access == 0) return ['status' => false, 'msg' => 'Invalid Access'];
    }
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuppersonsinvolved($config)
  {
    //default
    $plotting = array('client' => 'client', 'clientname' => 'clientname');
    $plottype = 'plothead';
    $title = 'List of Persons Involved';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = [
      array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
    ];

    $qry = "select * from (
    select demp.client, demp.clientname
      from incidentdtail as detail
      left join client as demp on demp.clientid = detail.empid
      union all
    select demp.client, demp.clientname
      from hincidentdtail as detail
      left join client as demp on demp.clientid = detail.empid
      ) as a
    group by a.client,a.clientname;";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function attentionlookup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Employee',
      'style' => 'width:900px;max-width:900px;'
    );

    $lookupclass = $config['params']['lookupclass'];
    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'attentionid' => 'empid',
        'attention_code' => 'empcode',
        'attention_name' => 'clientname',
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'empcode', 'label' => 'Code', 'align' => 'left', 'field' => 'empcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'jobtitle', 'label' => 'Job Title', 'align' => 'left', 'field' => 'jobtitle', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    switch (strtoupper($config['params']['doc'])) {
      case 'HS':
        $qry = "
          SELECT 0 AS selectid,0 AS orderid,employee.empid,client.client as empcode,
          CONCAT(emplast, ', ', empfirst, ' ', empmiddle) AS clientname,
          jt.jobtitle as jobtitle, jt.docno as jobcode, jt.line as jobid,
          dp.clientname as deptname, dp.client as deptcode, ifnull(dp.clientid, 0) as deptid,
          date(employee.hired) as hired,
          employee.emptype,employee.jgrade,employee.empstatus,employee.emprank,employee.level,
          employee.resigned,employee.emploc,employee.paymode, employee.paygroup,employee.classrate as payrate,
          ifnull((select basicrate from allowsetup where empid=employee.empid and dateend='9999-12-31' limit 1),0) as allowance,
          ifnull((select basicrate from ratesetup where empid=employee.empid and dateend='9999-12-31' limit 1),0) as basicsalary
          FROM employee 
          left join client on client.clientid=employee.empid
          left join client as dp on dp.clientid=employee.deptid
          left join jobthead as jt on employee.jobid = jt.line
        ";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function statcodelookup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Status',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'statcode' => 'code',
        'statdesc' => 'stat',
        'statid' => 'line'
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'stat', 'label' => 'Status', 'align' => 'left', 'field' => 'stat', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "SELECT 0 AS selectid,0 AS orderid,code,stat from statchange";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function emplevellookup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Level',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'tlevel' => 'level'
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'level', 'label' => 'Level', 'align' => 'left', 'field' => 'level', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "
      SELECT 0 AS selectid,0 AS orderid, a.level
      from (
        select 1 as level union all select 2 as level
        union all
        select 3 as level
        union all
        select 4 as level
        union all
        select 5 as level
        union all
        select 6 as level
        union all
        select 7 as level
        union all
        select 8 as level
        union all
        select 9 as level
        union all
        select 10 as level) as a
    ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function empstatlookup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Employment Status',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'tempstatcode' => 'code'
      )
    );

    switch ($config['params']['lookupclass']) {
      case 'empstatlookup':
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array(
            'empstatus' => 'empstatus',
            'empstatusid' => 'line',
            'tempstatcode' => 'code',
            'tempstatname' => 'empstatus',
          )
        );
        break;
    }

    // lookup columns
    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'empstatus', 'label' => 'Status', 'align' => 'left', 'field' => 'empstatus', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "
      select 0 as selectid,0 as orderid ,line,code,empstatus
      from empstatentry;
    ";

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 1280, '/tableentries/hrisentry/empstatusmaster');
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  }

  public function lookupmodeofpayment($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Mode of Payment',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'tpaymode' => 'mode'
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'mode', 'label' => 'Mode', 'align' => 'left', 'field' => 'mode', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "
      SELECT 0 AS selectid,0 AS orderid,a.mode
      from (
        select 'Weekly' as mode
        union all
        select 'Semi-Monthly' as mode
        union all
        select 'Monthly' as mode
        union all
        select 'Daily' as mode
        union all
        select 'Piece Rate' as mode) as a
    ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookuppaytype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Payment Type ',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'paytype' => 'ptype'
      )
    );
    $cols = array(
      array('name' => 'ptype', 'label' => 'List of Payment Type', 'align' => 'left', 'field' => 'ptype', 'sortable' => true, 'style' => 'font-size:16px;')
    );
    $qry = "
      SELECT 0 AS selectid,0 AS orderid,a.ptype
      from (
        select 'Cash' as ptype
        union all
        select 'Cheque' as ptype
       ) as a
    ";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function paygrouplookup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Pay Group',
      'style' => 'width:900px;max-width:900px;'
    );

    $plottype = 'plothead';

    $plotsetup = array(
      'plottype' => $plottype,
      'plotting' => array(
        'tpaygroup' => 'paygroup',
        'paygroupid' => 'line'
      )
    );

    switch ($config['params']['lookupclass']) {
      case 'batchsetuppaygroup':
        $plotsetup = array(
          'plottype' => $plottype,
          'plotting' => array(
            'pgroup' => 'line',
            'tpaygroupname' => 'paygroup',
            'paycode' => 'code'
          )
        );
        break;

      default:
        switch ($config['params']['doc']) {
          case 'PAYROLLPROCESS':
            $plotsetup = array(
              'plottype' => $plottype,
              'plotting' => array(
                'paygroup' => 'line',
                'tpaygroup' => 'paygroup'
              )
            );
            break;
          case 'EMPLOYEE':
            $plotsetup = array(
              'plottype' => $plottype,
              'plotting' => array(
                'paygroup' => 'line',
                'paygroupname' => 'paygroup'
              )
            );
            break;
          case 'EP':
            $plotsetup = array(
              'plottype' => $plottype,
              'plotting' => array(
                'paygroup' => 'line',
                'paygroupname' => 'paygroup',
                'tpaygroupname' => 'paygroup'
              )
            );
            break;

          case 'HS':
            $plotsetup = array(
              'plottype' => $plottype,
              'plotting' => array(
                'tpaygroup' => 'line',
                'tpaygroupname' => 'paygroup'
              )
            );
            break;
        }

        break;
    }


    // lookup columns
    $cols = array(
      array('name' => 'paygroup', 'label' => 'Pay Group', 'align' => 'left', 'field' => 'paygroup', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "
      SELECT 0 AS selectid,0 AS orderid, line, paygroup,code from paygroup order by paygroup
    ";
    $btnadd = $this->sqlquery->checksecurity($config, 1480, '/tableentries/payrollsetup/paygroup');
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  }

  public function payratelookup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Pay Rate',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'tpayrate' => 'mode'
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'mode', 'label' => 'Mode', 'align' => 'left', 'field' => 'mode', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "
      SELECT 0 AS selectid,0 AS orderid,a.mode 
      from (
      select 'Monthly' as mode 
      union all 
      select 'Daily' as mode) as a
    ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuppacno($config)
  {
    $companyid = $config['params']['companyid'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Payroll Accounts',
      'style' => 'width:600px;max-width:600px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('acno' => 'acno', 'acnoname' => 'acnoname', 'acnoid' => 'line')
    );

    // lookup columns
    $cols = array(
      array('name' => 'acno', 'label' => 'Accounts', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $filter = '';
    switch ($config['params']['doc']) {
      case 'LEAVESETUP';
      case 'LEAVEBATCHCREATION':
        $filter = " and codename like '%LEAVE%'";
        break;
      case 'EARNINGDEDUCTIONSETUP';
        $filter = " and code <> 'PT69'";
        break;
      case 'TIMEADJ':
        $filter = " and type like '%MDS%'";
        break;
      default:
        switch ($config['params']['lookupclass']) {
          case 'lookuploanapp_account':
            $lookupsetup['style'] = 'width:800px;max-width:800px;';
            $filter = " and (alias like '%DEDUCTION%' or alias like '%LOAN%')";
            // and alias in ('DEDUCTION','LOAN')
            if ($companyid == 51) { //ulitc
              $filter .= " and isportalloan = 1";
            }
            break;
          default:
            $filter = '';
            break;
        }
        break;
    }


    $qry = "select code as acno,codename as  acnoname, line from paccount where ''=''" . $filter . " order by codename";
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 1490, '/tableentries/payrollsetup/payrollaccounts');

    return [
      'status' => true,
      'msg' => 'ok',
      'data' => $data,
      'lookupsetup' => $lookupsetup,
      'cols' => $cols,
      'plotsetup' => $plotsetup,
      'btnadd' => $btnadd
    ];
  }

  public function empranklookup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Rank',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'trank' => 'rank',
        'rank' => 'rank'
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'rank', 'label' => 'Rank', 'align' => 'left', 'field' => 'rank', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "
      SELECT 0 AS selectid,0 AS orderid, rank from rank order by rank
    ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupbatchsetuppaymode($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Mode of Payment',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'paymode' => 'mode'
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'mode', 'label' => 'Mode', 'align' => 'left', 'field' => 'mode', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "
      SELECT 0 AS selectid,0 AS orderid,a.mode
      from (
        select 'Weekly' as mode
        union all
        select 'Semi-Monthly' as mode
        union all
        select 'Monthly' as mode
        union all
        select 'Piece' as mode
        union all
        select 'Last Pay' as mode) as a
    ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuppaymodetype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Status',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('paymodetype' => 'paymodetype', 'val' => 'val')
    );

    // lookup columns
    $cols = array(
      array('name' => 'paymodetype', 'label' => 'Type', 'align' => 'left', 'field' => 'paymodetype', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $paymode = substr($config['params']['addedparams'][0], 0, 1);
    if ($paymode == '') {
      return ['status' => false, 'msg' => 'select pay mode first'];
    }
    switch (strtoupper($paymode)) {
      case 'W':
      case 'P':
      case 'L':
        $qry = "
          select '' as paymodetype, '' as val
          union all
          select 'W1' as paymodetype, '01' as val
          union all
          select 'W2' as paymodetype, '02' as val
          union all
          select 'W3' as paymodetype, '03' as val
          union all
          select 'W4' as paymodetype, '04' as val
          union all
          select 'W5' as paymodetype, '05' as val
          union all
          select '13th' as paymodetype, '13' as val
        ";
        break;
      case 'S':
      case 'M':
        $qry = "
          select '' as paymodetype, '' as val
          union all
          select '1st Half' as paymodetype, '02' as val
          union all
          select '2nd Half' as paymodetype, '04' as val
          union all
          select '13th' as paymodetype, '13' as val
        ";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupleavestatus($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Status',
      'style' => 'width:900px;max-width:900px;'
    );

    $lookupclass = isset($config['params']['lookupclass2']) ? $config['params']['lookupclass2'] : '';
    if ($lookupclass == '') {
      $lookupclass = $config['params']['lookupclass'];
    }

    switch ($lookupclass) {
      case 'lookupgridleavestatus':
        $rowindex = $config['params']['index'];
        $plotsetup = array(
          'plottype' => 'plotgrid',
          'plotting' => array(
            'status' => 'status'
          )
        );
        break;
      case 'lookupleavesetstatus':
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array(
            'setempstat' => 'status'
          )
        );
        break;
      default:
        $plotsetup = array(
          'plottype' => 'plothead',
          'plotting' => array(
            'status' => 'status'
          )
        );
        break;
    }

    // lookup columns
    $cols = array(
      array('name' => 'status', 'label' => 'Status', 'align' => 'left', 'field' => 'status', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "
      select 'APPROVED' as status
      union all
      select 'ENTRY' as status
      union all
      select 'ON-HOLD' as status
      union all
      select 'PROCESSED' as status
    ";
    if (($config['params']['companyid'] == 44 || $config['params']['companyid'] == 53) && $config['params']['doc'] == 'LEAVEAPPLICATIONPORTALAPPROVAL') { //stonepro | CAMERA SOUND
      $qry = "
      select 'APPROVED' as status
      union all
      select 'ENTRY' as status";
    }


    $data = $this->coreFunctions->opentable($qry);
    switch ($lookupclass) {
      case 'lookupgridleavestatus':
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
        break;

      default:
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
        break;
    }
  }

  public function lookupleavetransdocno($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Leave Setup',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'docno' => 'docno',
        'acnoid' => 'acnoid',
        'acno' => 'acno',
        'acnoname' => 'acnoname',
        'prdstart' => 'prdstart',
        'prdend' => 'prdend',
        'days' => 'days',
        'bal' => 'bal',
        'trno' => 'trno'
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Docno', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'empname', 'label' => 'Name', 'align' => 'left', 'field' => 'empname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'days', 'label' => 'Entitled', 'align' => 'left', 'field' => 'days', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'bal', 'label' => 'Remaining', 'align' => 'left', 'field' => 'bal', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $empid = $config['params']['addedparams'][0];
    $qry = "
      select ls.trno, ls.docno, ls.dateid, e.empid,
      CONCAT(e.emplast,', ',e.empfirst,' ',e.empmiddle) AS empname, 
      ls.remarks, coa.code as acno, coa.codename as acnoname, coa.line as acnoid,
      ls.days, ls.bal, ls.prdstart, ls.prdend
      from leavesetup as ls
      left join paccount as coa on coa.line = ls.acnoid
      left join employee as e on e.empid = ls.empid
      where ls.empid = '" . $empid . "'
    ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuppayrollsetupbatch($config)
  {
    $plottype = 'plothead';
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Batch Setup',
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => $plottype,
      'plotting' => array(
        'batch' => 'batch',
        'startdate' => 'startdate',
        'enddate' => 'enddate',
        'sdate1' => 'startdate',
        'sdate2' => 'enddate',
        'batchid' => 'line',
        'batchdate' => 'dateid',
        'pgroup' => 'pgroup',
        'is13' => 'is13',
        '13start' => '13start',
        '13end' => '13end',
        'adjustm' => 'adjustm',
        'paymode' => 'paymode',
        'empcode' => 'empcode',
        'empname' => 'empname',
        'checkall' => 'checkall'
      )
    );
    $lookupclass = $config['params']['lookupclass'];
    switch ($lookupclass) {
      case 'lookuppayrollsetupbatch':
        switch ($config['params']['doc']) {
          case 'PAYROLLSETUP':
            $plotsetup = array(
              'plottype' => 'plotledger',
              'plotting' => array(
                'batch' => 'batch',
                'startdate' => 'startdate',
                'enddate' => 'enddate',
                'batchid' => 'line',
                'paymode' => 'paymode'
              )
            );
            break;
          case 'PAYROLLPROCESS':
            $plotsetup['plotting']['fullwordpaymode'] = 'fullwordpaymode';
            $plotsetup['plotting']['pgroup'] = 'pgroup';
            $plotsetup['plotting']['tpaygroupname'] = 'paygroup';

            $plotsetup['plotting']['divid'] = 'divid';
            $plotsetup['plotting']['divname'] = 'divname';
            $plotsetup['plotting']['branchid'] = 'branchid';
            $plotsetup['plotting']['branchname'] = 'branchname';
            break;
          case 'PAYROLLENTRY':
            $plotsetup['plotting']['fullwordpaymode'] = 'fullwordpaymode';
            $plotsetup['plotting']['tpaygroupname'] = 'paygroup';
            break;
        }
        break;
    }

    // lookup columns
    $cols = array(
      array('name' => 'batch', 'label' => 'Batch', 'align' => 'left', 'field' => 'batch', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'startdate', 'label' => 'Start date', 'align' => 'left', 'field' => 'startdate', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'enddate', 'label' => 'End date', 'align' => 'left', 'field' => 'enddate', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'paymode', 'label' => 'Paymode Code', 'align' => 'left', 'field' => 'paymode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'fullwordpaymode', 'label' => 'Description', 'align' => 'left', 'field' => 'fullwordpaymode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'paygroup', 'label' => 'Pay Group', 'align' => 'left', 'field' => 'paygroup', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'postdate', 'label' => 'Closed Date', 'align' => 'left', 'field' => 'postdate', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'postby', 'label' => 'Closed By', 'align' => 'left', 'field' => 'postby', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    if ($config['params']['companyid'] == 58) {
      array_push($cols, array('name' => 'divname', 'label' => 'Company', 'align' => 'left', 'field' => 'divname', 'sortable' => true, 'style' => 'font-size:16px;'));
      array_push($cols, array('name' => 'branchname', 'label' => 'Branch', 'align' => 'left', 'field' => 'branchname', 'sortable' => true, 'style' => 'font-size:16px;'));
    }

    $qry = "
      select b.line, b.batch, date(b.dateid) as dateid, b.paymode, b.pgroup, b.startdate, b.enddate,'' as client,'' as clientid, '0' as checkall,
      case
        when b.paymode = 'm' then 'Monthly'
        when b.paymode = 's' then 'Semi-Monthly'
        when b.paymode = 'w' then 'Weekly'
        when b.paymode = 'p' then 'Pierce'
        when b.paymode = 'l' then 'Last Pay'
      end as fullwordpaymode,
      date(b.startdate) as startdate, date(b.enddate) as enddate, b.is13, b.adjustm, b.13start, b.13end, b.postdate, b.postby, pay.paygroup, b.divid, ifnull(d.divname,'') as divname, b.branchid, ifnull(br.clientname,'') as branchname
      from batch as b left join paygroup as pay on pay.line=b.pgroup left join division as d on d.divid=b.divid left join client as br on br.clientid=b.branchid order by enddate desc
    ";
    $btnadd = $this->sqlquery->checksecurity($config, 1570, '/ledger/payroll/batchsetup');
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  }

  public function lookupottype($config)
  {

    $companyid = $config['params']['companyid'];

    $plotting = array('ottype' => 'ottype');
    $plottype = 'plotledger';
    $title = 'Types of Overtime';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'ottype', 'label' => 'Types', 'align' => 'left', 'field' => 'ottype', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    switch ($companyid) {
      case 62: //onesky
        $qry = "select 'EARLY OT' as ottype union all
            select 'REGULAR OT' as ottype union all 
            select 'NIGHT DIFF OT' union all
            select 'NIGHT DIFF' union all
            select 'RESTDAY' union all
            select 'RESTDAY OT' union all
            select 'SPECIAL HOLIDAY' union all
            select 'SPECIAL OT' union all
            select 'LEGAL HOLIDAY' union all 
            select 'LEGAL OT' union all
            select 'DOUBLE HOLIDAY' union all
            select 'DOUBLE HOLIDAY OT'
          ";
        break;
      default:
        $qry = "select 'REGULAR OT' as ottype union all 
            select 'NIGHT DIFF OT' union all
            select 'NIGHT DIFF' union all
            select 'RESTDAY' union all
            select 'RESTDAY OT' union all
            select 'SPECIAL HOLIDAY' union all
            select 'SPECIAL OT' union all
            select 'LEGAL HOLIDAY' union all 
            select 'LEGAL OT'
          ";
        break;
    }


    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function


  public function lookupdcode($config)
  {

    $plotting = array('dcode' => 'barcode', 'dname' => 'itemname', 'drate' => 'amt');
    $plottype = 'plotledger';
    $title = 'List of Items';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'itemname', 'label' => 'Item Name', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'amt', 'label' => 'Amount', 'align' => 'left', 'field' => 'amt', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select barcode, itemname, round(amt,2) as amt from item";
    $data = $this->coreFunctions->opentable($qry);
    $btnadds = isset($btnadd) ? $btnadd : "";
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadds];
  } //end function

  public function lookupdsectionhris($config)
  {

    $plotting = array('section' => 'sectcode', 'description' => 'sectname', 'sectid' => 'sectid');
    $plottype = 'plotgrid';
    $title = 'List of Section';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'sectcode', 'label' => 'Code', 'align' => 'left', 'field' => 'sectcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'sectname', 'label' => 'Name', 'align' => 'left', 'field' => 'sectname', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select sectid, sectcode, sectname from section";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function


  public function lookuprole($config)
  {

    $plotting = array(
      'roleid' => 'roleid',
      'rolename' => 'rolename',
      'divid' => 'divid',
      'divname' => 'divname',
      'sectid' => 'sectionid',
      'deptid' => 'deptid',
      'sectname' => 'sectname',
      'deptname' => 'deptname',
      'supervisor' => 'supervisorname',
      'supervisorcode' => 'supervisorcode',
      'supervisorid' => 'supervisorid',
      'dcode' => 'division',
      'dname' => 'divname',
      'division' => 'division',
      'orgsection' => 'orgsection',
      'sectionname' => 'sectname',
      'ddivname' => 'divname',
      'troleid' => 'roleid',
      'trolename' => 'rolename',
      'joddivname' => 'divname',
      'tdeptcode' => 'deptcode',
      'tdeptname' => 'deptname',
      'tdivid' => 'divid',
      'tsectid' => 'sectionid',
      'tdivname' => 'divname',
      'tsectname' => 'sectname',
      'supid' => 'supervisorid'
    );
    switch ($config['params']['lookupclass']) {
      case 'rsrole':
        $plottype = 'plotledger';
        $plotting = array(
          'roleid' => 'roleid',
          'rolename' => 'rolename',
          'divid' => 'divid',
          'divname' => 'divname',
          'sectid' => 'sectionid',
          'deptid' => 'deptid',
          'sectionname' => 'sectname',
          'deptname' => 'deptname',
          'supervisor' => 'supervisorname',
          'supervisorcode' => 'supervisorcode',
          'supervisorid' => 'supervisorid',
          'supid' => 'supervisorid'
        );
        break;

      case 'eprole':
        $plottype = 'plothead';
        $plotting = array(
          'roleid2' => 'roleid',
          'rolename' => 'rolename',
          // 'divid' => 'divid',
          'divname' => 'divname',
          // 'sectid' => 'sectionid',
          // 'deptid' => 'deptid',
          'sectionname' => 'sectname',
          'deptname' => 'deptname',

          // 'supervisor' => 'supervisorname',
          // 'supervisorcode' => 'supervisorcode',
          // 'supervisorid' => 'supervisorid',
          // 'supid' => 'supervisorid'
        );
        break;

      default:
        $plottype = 'plothead';
        break;
    }

    $title = 'List of Role';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:80%;max-width:80%;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'rolename', 'label' => 'Roles', 'align' => 'left', 'field' => 'rolename', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'divname', 'label' => 'Company', 'align' => 'left', 'field' => 'divname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'deptname', 'label' => 'Department', 'align' => 'left', 'field' => 'deptname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'sectname', 'label' => 'Section', 'align' => 'left', 'field' => 'sectname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'supervisorname', 'label' => 'Supervisor', 'align' => 'left', 'field' => 'supervisorname', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select role.line as roleid, role.name as rolename, role.divid, role.deptid, 
            role.sectionid, role.supervisorid, divs.divname, depart.clientname as deptname, 
            depart.client as deptcode,
            sec.sectname, super.clientname as supervisorname, divs.divcode as division, sec.sectcode as orgsection,
            super.client as supervisorcode, super.clientid as supervisorid,
            concat(divcode,'~',divs.divname) as ddivname,
            concat(sectcode,'~',sec.sectname) as dsectionname
            from rolesetup as role
            left join division as divs on divs.divid = role.divid
            left join client as depart on depart.clientid = role.deptid
            left join section as sec on sec.sectid = role.sectionid
            left join client as super on super.clientid = role.supervisorid
            order by role.line
          ";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function lookuprsbranch($config)
  {
    $plotting = array(
      'tobranchid' => 'clientid',
      'tobranchcode' => 'client',
      'tobranchname' => 'clientname'
    );

    $plottype = 'plotledger';

    $title = 'List of Role';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:80%;max-width:80%;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'client', 'label' => 'Branch Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'clientname', 'label' => 'Branch Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));


    $qry = "select 0 clientid,'' as client,'' as clientname 
            union all 
            select client.clientid,client.client,client.clientname
                from client where isbranch=1 and isinactive =0 order by client ";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function



  public function lookupreqtrain($config)
  {

    $plotting = array(
      'reqtrain' => 'trno',
      'reqtrainname' => 'docno',
      'cost' => 'budget',
      'title' => 'title',
      'venue' => 'venue',
      'ttype' => 'type',
      'tdate1' => 'date1',
      'tdate2' => 'date2'
    );
    $plottype = 'plothead';
    $title = 'List of Request Training and Development';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'docno', 'label' => 'docno', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'title', 'label' => 'Title', 'align' => 'left', 'field' => 'title', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'type', 'label' => 'Type of Training', 'align' => 'left', 'field' => 'type', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select trno,docno,title,type,venue,budget,date1,date2,req from(
      select ha.trno, ha.docno, ha.title, ha.type, ha.venue, ha.budget, ha.date1, ha.date2,
      (case when ifnull(ht.reqtrain,0)=0 then 0 else ht.reqtrain end) as req
      from htraindev as ha
      left join traininghead as ht on ht.reqtrain=ha.trno) as tb
      where req=0";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function
  public function lookuprxscompany($config)
  {
    //default
    $companyid = $config['params']['companyid'];
    $plotting = array();
    $plotting = array(
      'compcode' => 'compcode',
      'projectcode' => 'projectcode',
      'projectname' => 'projectname',
      'subprojcode' => 'subprojcode',
      'subprojectname' => 'subprojectname',
      'blocklotcode' => 'blocklotcode',
      'blocklotroxas' => 'blocklotroxas',
      'amenitycode' => 'amenitycode',
      'amenity' => 'amenity',
      'subamenitycode' => 'subamenitycode',
      'subamenity' => 'subamenity',
      'deptcode' => 'deptcode',
      'department' => 'department'
    );
    $plottype = 'plotledger';
    $title = 'Company Name';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:80%;max-width:100%;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns

    $cols = [
      ['name' => 'compcode', 'label' => 'Company Name', 'align' => 'left', 'field' => 'compcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'projectcode', 'label' => 'Project Code', 'align' => 'left', 'field' => 'projectcode', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];
    $qry = "select distinct compcode, '' as projectname,'' as projectcode,
    '' as subprojcode,'' as subprojectname,'' as blocklotroxas,'' as blocklotcode,
      '' as amenitycode ,'' as amenity,'' as subamenitycode,'' as subamenity
     from projectroxas";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function lookuprjroxas($config)
  {

    $compcode = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
    $projectcode = isset($config['params']['addedparams'][1]) ? $config['params']['addedparams'][1] : '';
    //default
    $plotting = array();
    $plotting = array(
      'compcode' => 'compcode',
      'projectcode' => 'projectcode',
      'projectname' => 'projectname',
      'subprojcode' => 'subprojcode',
      'subprojectname' => 'subprojectname',
      'blocklotcode' => 'blocklotcode',
      'blocklotroxas' => 'blocklotroxas',
    );

    $plottype = 'plotledger';
    $title = 'List of Projects';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:80%;max-width:100%;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = [
      ['name' => 'compcode', 'label' => 'Company Name', 'align' => 'left', 'field' => 'compcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'projectcode', 'label' => 'Project Code', 'align' => 'left', 'field' => 'projectcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'projectname', 'label' => 'Project Name', 'align' => 'left', 'field' => 'projectname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'subprojcode', 'label' => 'Subproject Code', 'align' => 'left', 'field' => 'subprojcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'subprojectname', 'label' => 'Subproject Name', 'align' => 'left', 'field' => 'subprojectname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'blocklotcode', 'label' => 'Blocklot Code', 'align' => 'left', 'field' => 'blocklotcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'blocklotroxas', 'label' => 'Blocklot Name', 'align' => 'left', 'field' => 'blocklotroxas', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select roxas.compcode ,roxas.line as roxasline, roxas.code as projectcode, 
                  roxas.name as projectname,concat(lotroxas.phase,', ',lotroxas.block,' ',lotroxas.lot) as blocklotroxas,
                  ifnull(lotroxas.code,'') as blocklotcode,ifnull(subroxas.code,'') as subprojcode,
                  subroxas.name as subprojectname
            from projectroxas  as roxas
            left join subprojectroxas as subroxas on subroxas.parent = roxas.code
            left join blocklotroxas as lotroxas on lotroxas.subprojectcode = subroxas.code
            order by compcode,projectname,subprojectname,blocklotroxas";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function
  public function lookupsubpjroxascode($config)
  {

    $compcode = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
    $projectcode = isset($config['params']['addedparams'][1]) ? $config['params']['addedparams'][1] : '';
    //default
    $plotting = array();

    $title = 'Sub Project';
    $plotting = array('subprojcode' => 'code', 'subprojectname' => 'name', 'subprojline' => 'line');
    $plottype = 'plotledger';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:500px;max-width:500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = [
      ['name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select compcode,line, code, name from subprojectroxas where compcode= '" . $compcode . "' and parent= '" . $projectcode  . "'";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function
  public function lookupblocklotroxas($config)
  {


    $compcode = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
    $subprojectcode = isset($config['params']['addedparams'][1]) ? $config['params']['addedparams'][1] : '';

    $plotting = array();

    $title = 'Blocklot';
    $plotting = array('blocklotcode' => 'code', 'blocklotroxas' => 'name', 'blocklotline' => 'line');
    $plottype = 'plotledger';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:500px;max-width:500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select compcode,line, code,
            concat(phase,', ',block,' ',lot) as name
    from blocklotroxas   where compcode= '" . $compcode . "' and subprojectcode= '" . $subprojectcode . "'";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function
  public function lookupamenityroxascode($config)
  {
    $companyid = $config['params']['companyid'];
    $compcode = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
    //default
    $plotting = array();

    $title = 'Amenity';
    $plotting = array('amenitycode' => 'amenitycode', 'amenity' => 'name', 'amenity' => 'amenity', 'amenityline' => 'line', 'subamenitycode' => 'subamenitycode', 'subamenity' => 'subamenity');
    $plottype = 'plotledger';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:80%;max-width:100%;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array(
      array('name' => 'amenity', 'label' => 'Amenity Name', 'align' => 'left', 'field' => 'amenity', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'amenitycode', 'label' => 'Amenity Code', 'align' => 'left', 'field' => 'amenitycode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subamenity', 'label' => 'Subamenity Name', 'align' => 'left', 'field' => 'subamenity', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subamenitycode', 'label' => 'Subamenity Code', 'align' => 'left', 'field' => 'subamenitycode', 'sortable' => true, 'style' => 'font-size:16px;'),
    );
    $qry = "select amenity.code as amenitycode ,amenity.name as amenity,
                  ifnull(subamenity.code,'') as subamenitycode,subamenity.name as subamenity
                  from amenityroxas as amenity
                  left join subamenityroxas as subamenity on subamenity.compcode = amenity.compcode and subamenity.parent = amenity.code
                  where  amenity.compcode = '" . $compcode . "' ";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function
  public function lookupsubamenityroxascode($config)
  {
    //default
    $compcode = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
    $amenitycode = isset($config['params']['addedparams'][1]) ? $config['params']['addedparams'][1] : '';
    $plotting = array();

    $title = 'Sub Amenity';
    if ($config['params']['companyid'] == 45 && $config['params']['doc'] == 'EMPPROJECTLOGB') { //pdpi payroll
      $plotting = array(
        'code' => 'code',
        'subamenityroxascode' => 'subamenityroxascode',
        'subamntline' => 'line',
        'amenityroxascode' => 'amenityroxascode'
      );
      $plottype = 'plotgrid';
    } else {
      $plotting = array('subamenitycode' => 'code', 'subamenity' => 'name', 'subamntline' => 'line');
      $plottype = 'plotledger';
    }

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:100%;max-width:90%;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array(
      array('name' => 'amenityroxascode', 'label' => 'Amenity Code', 'align' => 'left', 'field' => 'amenityroxascode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'amenity', 'label' => 'Amenity Name', 'align' => 'left', 'field' => 'amenity', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'code', 'label' => 'Sub Amenity Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subamenityroxascode', 'label' => 'Sub Amenity Name', 'align' => 'left', 'field' => 'subamenityroxascode', 'sortable' => true, 'style' => 'font-size:16px;')
    );
    if ($config['params']['companyid'] == 45 && $config['params']['doc'] == 'EMPPROJECTLOGB') { //pdpi payroll
      $compcode = $config['params']['row']['compcode'];
      $qry = "select amenity.code as amenityroxascode ,amenity.name as amenity,
                  subamenity.code,subamenity.name as subamenityroxascode
                  from amenityroxas as amenity
                  left join subamenityroxas as subamenity on subamenity.compcode = amenity.compcode and subamenity.parent = amenity.code
                  where amenity.compcode = '" . $compcode . "' and subamenity.code <> ''";
      $index = $config['params']['index'];
      $table = $config['params']['table'];

      $data = $this->coreFunctions->opentable($qry);
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $index];
    } else {
      $qry = "select compcode,line, code, name from subamenityroxas where compcode= '" . $compcode . "' and parent= '" . $amenitycode . "'";
      $data = $this->coreFunctions->opentable($qry);
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
  } //end function
  public function lookupdepartmentroxascode($config)
  {
    $compcode = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
    //default
    $plotting = array();
    $title = 'Department';
    $plotting = array('deptcode' => 'code', 'department' => 'name', 'deptline' => 'line');
    $plottype = 'plotledger';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:500px;max-width:500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select compcode,line, code, name from departmentroxas where compcode= '" . $compcode . "'";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function lookupleavecategory($config)
  {
    //default
    $plotting = array();
    $title = 'List of Leave Category';
    $plotting = array('category' => 'category', 'catid' => 'line');
    $plottype = 'plothead';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:500px;max-width:500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array(
      array('name' => 'category', 'label' => 'Category Name', 'align' => 'left', 'field' => 'category', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select line,category from leavecategory";
    $data = $this->coreFunctions->opentable($qry);

    if ($config['params']['lookupclass'] = 'dashboard') {
      $plotsetup['plottype'] = 'plotledger';
      $btnadd = $this->sqlquery->checksecurity($config, 5027, '/tableentries/payrollsetup/lvcat');
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
    }
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupontrip($config)
  {
    //default
    $plotting = array();
    $title = 'List of Log Types';
    $plotting = array('ontrip' => 'ontrip');
    $plottype = 'plothead';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:500px;max-width:500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array(
      array('name' => 'ontrip', 'label' => 'Log type', 'align' => 'left', 'field' => 'ontrip', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'LOG' as ontrip
           union all
           select 'ON TRIP' as ontrip";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupresigned($config)
  {
    $doc = $config['params']['doc'];
    $plotting = array();

    $title = 'List of Resign Type';

    $plotting = array('resigned' => 'resigned');

    switch ($config['params']['lookupclass']) {
      case 'lookupresignedEP':
      case 'lookupresignedHC':
        $plotting = array('resignedtype' => 'resigned');
        break;
      case 'lookupresigned':
        if ($doc == 'EP' && $config['params']['companyid'] == 58) { //cdo
          $title = 'Employee Status';
        }
        break;
    }
    $plottype = 'plothead';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:500px;max-width:500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array(
      array('name' => 'resigned', 'label' => 'Resign Type', 'align' => 'left', 'field' => 'resigned', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    switch ($config['params']['lookupclass']) {
      case 'lookupresigned':
        $addunion = '';
        if ($doc == 'EP') {
          $addunion = " union all select 'ALL' as resigned ";
        }
        $qry = "select 'Active' as  resigned union all
              select 'Inactive' as  resigned union all 
              select stat as resigned from statchange" . $addunion;
        break;

      default:
        $qry = "select '' as resigned union all select stat as resigned from statchange order by resigned";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupbanktype($config)
  {
    $doc = $config['params']['doc'];
    $plotting = array();

    $title = 'List of Bank';

    $plotting = array('bank' => 'bank');

    $plottype = 'plothead';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:500px;max-width:500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array(
      array('name' => 'bank', 'label' => 'Bank', 'align' => 'left', 'field' => 'bank', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'CASH' as  bank union all
              select 'METROBANK' as  bank union all 
              select 'BPI' as bank";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupsalarytype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Salary Type',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('salarytype' => 'salarytype')
    );

    $cols = array(
      array('name' => 'salarytype', 'label' => 'Salary Type', 'align' => 'left', 'field' => 'salarytype', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'STARTING RATE' as salarytype
            union all
            select 'REGULARIZATION' as salarytype
            union all
            select 'NOMINATION OF ALLOWANCES' as salarytype
            union all
            select 'PROMOTION' as salarytype
            union all
            select 'NEW MINIMUM WAGE ORDER' as salarytype
            union all
            select 'SALARY ADJUSTMENT' as salarytype
            union all
            select 'LATERAL TRANSFER' as salarytype";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuphsperiod($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Period',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('hsperiod' => 'hsperiod')
    );

    $cols = array(
      array('name' => 'hsperiod', 'label' => 'Period', 'align' => 'left', 'field' => 'hsperiod', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'DAILY RATE' as hsperiod
            union all
            select 'HOURLY RATE' as hsperiod
            union all
            select 'PACKAGE RATE' as hsperiod";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function lookuplocname($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Location',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plotledger',
      'plotting' => array('locid' => 'line', 'locname' => 'locname')
    );

    $cols = array(
      array('name' => 'locname', 'label' => 'Location', 'align' => 'left', 'field' => 'locname', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select line,locname from emploc";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuptotalterms($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Total Terms',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('totalterms' => 'totalterms')
    );

    $cols = array(
      array('name' => 'totalterms', 'label' => 'Total Terms', 'align' => 'left', 'field' => 'totalterms', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select '0' as totalterms 
            union all
            select '6' as totalterms 
            union all
            select '12' as totalterms 
            union all
            select '18' as totalterms 
            union all
            select '24' as totalterms 
            union all
            select '30' as totalterms 
            union all
            select '36' as totalterms 
            ";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuphbranch($config)
  {
    $lookupclass = $config['params']['lookupclass'];

    $title = 'List of Branch';
    $addonfield = ", '' as manpower";
    $condition = " where client.isbranch=1 order by client.client ";
    $btnadd = $this->sqlquery->checksecurity($config, 2588, '/ledger/masterfile/branch');
    $plottype = 'plothead';

    $plotting = array(
      'branchcode' => 'client',
      'branch' => 'clientid',
      'branchid' => 'clientid',
      'branchname' => 'clientname',
      'manpower' => 'manpower'
    );
    if ($config['params']['doc'] == 'APPLICANTLEDGER') {
      $plotting = array(
        'branchcode' => 'branchcode',
        'branchid' => 'branchid',
        'branchname' => 'branchname',
        'hqtrno' => 'trno'
      );
    }

    $cols = array();
    array_push($cols, array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'addr', 'label' => 'Address', 'align' => 'left', 'field' => 'addr', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select client.clientid,client.client,client.clientname,client.addr,'' as manpower, 0 as allocation
            from client where client.isbranch=1";

    if ($config['params']['doc'] == 'APPLICANTLEDGER') {
      $cols = array();
      array_push($cols, array('name' => 'branchname', 'label' => 'Branch', 'align' => 'left', 'field' => 'branchname', 'sortable' => true, 'style' => 'font-size:16px;'));
      array_push($cols, array('name' => 'docno', 'label' => 'Request No.', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'));

      $qry = "select trno, docno, clientid as branchid, client as branchcode, branchname from(
      SELECT head.trno, head.docno, branch.clientid, branch.client, ifnull(branch.clientname,'') as branchname
      from hpersonreq as head
      left join hrisnum as num on num.trno = head.trno
      left join jobthead as jh on jh.docno=head.job
      left join client as branch on branch.clientid = head.branchid
      where head.headcount>head.qa and jh.line=" . $config['params']['addedparams'][0] . " and head.status2='A'
      ) as k group by trno, docno, branchname, clientid, client
      order by branchname";
    }

    $data = $this->coreFunctions->opentable($qry);

    if ($config['params']['doc'] == 'HQ') {
      if ($config['params']['companyid'] == 58) { //cdo

        $cols = array();
        array_push($cols, array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'allocation', 'label' => 'Allocation', 'align' => 'left', 'field' => 'allocation', 'sortable' => true, 'style' => 'font-size:16px;'));
        array_push($cols, array('name' => 'addr', 'label' => 'Address', 'align' => 'left', 'field' => 'addr', 'sortable' => true, 'style' => 'font-size:16px;'));

        foreach ($data as $d => $v) {
          $q = "select group_concat(manpower separator '\n') as value from (
              select concat(count(e.empid),' - ',job.jobtitle) as manpower from employee as e left join jobthead as job on job.line=e.jobid
              where e.branchid=" . $v->clientid . " and e.isactive=1 group by job.jobtitle) as m";
          $v->manpower = $this->coreFunctions->datareader($q);

          if (isset($config['params']['addedparams'][0])) {
            $q2 = "select cl.qty as value from cljobs as cl join jobthead as job on job.line=cl.jobid where cl.clientid=" . $v->clientid . " and job.docno='" . $config['params']['addedparams'][0] . "'";
            $v->allocation = $this->coreFunctions->datareader($q2, [], '', true);
          }
        }

        $plotting['headcount'] = 'allocation';

        if (isset($config['params']['addedparams'][1])) $title = $title . ' - ' . $config['params']['addedparams'][1];
      }
    }

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:80%;max-width:80%;height:700px'
    );


    $plotsetup = array(
      'plottype' => $plottype,
      'plotting' => $plotting
    );

    $btnadds = isset($btnadd) ? $btnadd : "";

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadds];
  }
  public function lookuphqdocno($config)
  {

    $jobcode = $config['params']['addedparams'][0];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Docno',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('hqdocno' => 'hqdocno', 'hqtrno' => 'hqtrno')
    );

    $cols = array(
      array('name' => 'hqdocno', 'label' => 'Docno', 'align' => 'left', 'field' => 'hqdocno', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select trno as hqtrno,docno as hqdocno from hpersonreq where job = '" . $jobcode . "'";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
}//end class



# GLEN 01 18 2021
# nag add ng function lookupdsectionhris para sa section lookup ng code of conduct module