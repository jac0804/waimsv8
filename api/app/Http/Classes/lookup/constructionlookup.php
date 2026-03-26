<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use App\Http\Classes\companysetup;
use Illuminate\Http\Request;
use App\Http\Requests;
use Carbon\Carbon;

class constructionlookup
{
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;
  private $companysetup;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
    $this->companysetup = new companysetup;
  }


  public function lookupstage($config)
  {
    switch ($config['params']['lookupclass']) {
      case 'stagestock':
        $plottype = 'plotgrid';
        $plotting = array('stageid' => 'stage', 'stage' => 'stagename');
        break;
      case 'stageentry':
        $plottype = 'plotemit';
        $plotting = array('stageid' => 'stage', 'stage' => 'stagename');
        break;
      default:
        $plotting = array(
          'stageid' => 'stage',
          'stage' => 'stagename'
        );
        $plottype = 'plothead';
        break;
    }

    $title = 'List of Stages';

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
      ['name' => 'stagename', 'label' => 'Stage', 'align' => 'left', 'field' => 'stagename', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    switch ($config['params']['lookupclass']) {
      case 'stagestock':
        $htable = $config['docmodule']->head;
        $trno = $config['params']['row']['trno'];
        $client = $this->coreFunctions->getfieldvalue($htable, "subproject", "trno=?", [$trno]);
        if ($config['params']['doc'] == 'MT') {
          $client = $this->coreFunctions->getfieldvalue($htable, "subprojectto", "trno=?", [$trno]);
        }
        break;
      case 'stageentry':
        $htable = $config['docmodule']->head;
        $trno = $config['params']['trno'];
        $client = $this->coreFunctions->getfieldvalue($htable, "subproject", "trno=?", [$trno]);
        break;

      default:
        $client = $config['params']['addedparams'][0];
        break;
    }

    $qry = "select st.stage,s.description,s.stage as stagename from stages as st left join stagesmasterfile as s on s.line = st.stage where  st.subproject=?";
    $data = $this->coreFunctions->opentable($qry, [$client]);


    if ($config['params']['lookupclass'] == 'stagestock') {
      $table = $config['params']['table'];
      $rowindex = $config['params']['index'];
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
    } else {
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
  } //end function

  public function lookupsubstage($config)
  {
    switch ($config['params']['lookupclass']) {
      case 'lookupsubstage':
        $plottype = 'plotgrid';
        $plotting = array('substage' => 'line', 'substagename' => 'substagename');
        break;
    }

    $htable = $config['docmodule']->head;
    $trno = $config['params']['row']['trno'];
    $stageid = $this->coreFunctions->getfieldvalue($htable, "stageid", "trno=?", [$trno]);
    $title = 'List of Sub Stages';

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
      ['name' => 'substagename', 'label' => 'Sub Stage', 'align' => 'left', 'field' => 'substagename', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select ss.line,ss.substage as substagename from substages as ss where  ss.stage=?";
    $data = $this->coreFunctions->opentable($qry, [$stageid]);


    $table = $config['params']['table'];
    $rowindex = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
  } //end function


  public function lookupsubactivity($config)
  {
    switch ($config['params']['lookupclass']) {
      case 'lookupsubactivity':
        $plottype = 'plotgrid';
        $plotting = array('subactivity' => 'line', 'subactivityname' => 'subactivityname');
        break;
    }
    $htable = $config['docmodule']->head;
    $hstock = $config['docmodule']->stock;
    $trno = $config['params']['row']['trno'];
    $substage = $config['params']['row']['substage'];
    $stageid = $this->coreFunctions->getfieldvalue($htable, "stageid", "trno=?", [$trno]);
    $title = 'List of Sub Activity';

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
      ['name' => 'subactivityname', 'label' => 'Sub Activity', 'align' => 'left', 'field' => 'subactivityname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select sa.line,sa.subactivity as subactivityname,sa.description from subactivity as sa where  sa.stage=? and sa.substage =?";
    $data = $this->coreFunctions->opentable($qry, [$stageid, $substage]);


    $table = $config['params']['table'];
    $rowindex = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
  } //end function


  public function lookupcvpaymode($config)
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

    $qry = "select 'Debit Online' as mode
            union all
            select 'Online Payment' as mode
            union all
            select 'Check Payment' as mode";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupsubproject($config)
  {

    switch ($config['params']['lookupclass']) {
      case 'gridsubproj':
      case 'pvsubproj':
        $plotting = array(
          'subproject' => 'line',
          'subprojectname' => 'subproject'
        );
        $plottype = 'plotgrid';
        break;

      default:
        $plotting = array(
          'subproject' => 'line',
          'subprojectname' => 'subproject'
        );
        $plottype = 'plothead';
        break;
    }
    $title = 'List of Sub Project';

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
      ['name' => 'subproject', 'label' => 'Subproject', 'align' => 'left', 'field' => 'subproject', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    switch ($config['params']['lookupclass']) {
      case 'lookupsubproject':
        $qry = "select line,subproject from subproject";
        $data = $this->coreFunctions->opentable($qry);
        break;
      case 'pvsubproj':
        $projectid = $config['params']['row']['projectid'];

        $qry = "select '' as line, '' as subproject union select line,subproject from subproject where projectid=?";
        $data = $this->coreFunctions->opentable($qry, [$projectid]);
        break;
      case 'maxisubprojectname':
      case 'gridsubproj':
        if (isset($config['params']['trno'])) {
          $trno = $config['params']['trno'];
        } else {
          $trno = $config['params']['row']['trno'];
        }

        $qry = "select projectid as value from lahead where trno=?;";
        $projectid  = $this->coreFunctions->datareader($qry, [$trno]);

        $qry = "select line,subproject from subproject where projectid=?";
        $data = $this->coreFunctions->opentable($qry, [$projectid]);
        break;

      default:
        $client = $config['params']['addedparams'][0];
        $qry = "select line,subproject from subproject where projectid=?";
        $data = $this->coreFunctions->opentable($qry, [$client]);
        break;
    }

    if ($config['params']['lookupclass'] == 'gridsubproj' || $config['params']['lookupclass'] == 'pvsubproj') {
      $table = $config['params']['table'];
      $rowindex = $config['params']['index'];
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
    } else {
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
  } //end function


  public function pendingboqsummary($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'trno',
      'title' => 'List of Pending BOQ Summary',
      'btns' => ['summary' =>
      ['label' => 'Show Details', 'lookupclass' => 'lookupsetup', 'action' => 'pendingboqdetail']],
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getboqsummary'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'stage', 'label' => 'Stage', 'align' => 'left', 'field' => 'stage', 'sortable' => true, 'style' => 'font-size:16px;')
    );


    $data = $this->sqlquery->getpendingboqsummary($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function pendingboqdetail($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Pending BOQ Details',
      'btns' => ['summary' =>
      ['label' => 'Show Summary', 'lookupclass' => 'lookupsetup', 'action' => 'pendingboqsummary']],
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getboqdetails'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'stage', 'label' => 'Stage', 'align' => 'left', 'field' => 'stage', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subactid', 'label' => 'Subactivity ID', 'align' => 'left', 'field' => 'subactid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subactivity', 'label' => 'Subactivity', 'align' => 'left', 'field' => 'subactivity', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rrqty', 'label' => 'Order Qty', 'align' => 'left', 'field' => 'rrqty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'qa', 'label' => 'Served', 'align' => 'left', 'field' => 'qa', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'pending', 'label' => 'Pending', 'align' => 'left', 'field' => 'pending', 'sortable' => true, 'style' => 'font-size:16px;')
    );


    $data = $this->sqlquery->getpendingboqdetails($config);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingboqdetail_mi($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Items',
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getboqdetails'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'RR Document No.', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Customer/Subcon', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'stage', 'label' => 'Stage Name', 'align' => 'left', 'field' => 'stage', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'cost', 'label' => 'Cost', 'align' => 'left', 'field' => 'cost', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'bal', 'label' => 'Available Qty', 'align' => 'left', 'field' => 'bal', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $data = $this->sqlquery->getpendingboqdetails_mi($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingprrr($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Items',
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getprdetails'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document No.', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rrqty', 'label' => 'Request Qty', 'align' => 'left', 'field' => 'rrqty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'qa', 'label' => 'Served', 'align' => 'left', 'field' => 'qa', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'pending', 'label' => 'Pending', 'align' => 'left', 'field' => 'pending', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'prdocno', 'label' => 'PR Document #', 'align' => 'left', 'field' => 'prdocno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'stage', 'label' => 'Stage', 'align' => 'left', 'field' => 'stage', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rem', 'label' => 'Notes', 'align' => 'left', 'field' => 'rem', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $data = $this->sqlquery->getpendingprrr($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function unbilled($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Unbilled',
      'style' => 'width:1200px;max-width:1200px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getunbilledselected'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rem', 'label' => 'Notes', 'align' => 'left', 'field' => 'rem', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $data = $this->sqlquery->getunbilled($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupbrdocno($config)
  {
    //default
    $doc = $config['params']['doc'];
    $filter = " head.projectid =? and head.subproject =? and head.bltrno =0 and head.cvtrno =0 ";

    if ($doc == 'CV') {
      $filter = " head.projectid =? and head.cvtrno =0 ";
    }

    $plotting = array('brdocno' => 'docno', 'brtrno' => 'trno', 'bal' => 'brbal');
    $plottype = 'plothead';
    $title = 'List of Budget Request';

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
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'start', 'label' => 'Start Date', 'align' => 'left', 'field' => 'start', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'end', 'label' => 'End Date', 'align' => 'left', 'field' => 'end', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'projectname', 'label' => 'Project', 'align' => 'left', 'field' => 'projectname', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $projectid = $config['params']['addedparams'][0];
    $subproject = $config['params']['addedparams'][1];

    $lasttrno = $this->coreFunctions->datareader("select trno as value from blhead where projectid = $projectid and subproject = $subproject
      union all 
      select trno as value from hblhead where projectid = $projectid and subproject = $subproject order by value desc limit 1");
    // $checktrno = $this->coreFunctions->datareader("select trno as value from hblhead where trno = $lasttrno");

    if (!empty($lasttrno)) {
      $qry = "select head.trno,head.docno,head.dateid,head.start,head.end,p.name as projectname,
                  format(ifnull((select ((select sum(br.amount) from hbrstock as br where br.trno = hd.brtrno)+hd.bal)-sum(st.ext) 
                  from hblhead as hd 
                  left join hblstock as st on st.trno = hd.trno 
                  where hd.projectid = head.projectid and hd.subproject = head.subproject 
                  group by hd.brtrno,hd.bal,hd.dateid 
                  order by hd.dateid desc limit 1),0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as brbal,
                    format(sum(stock.ext)-ifnull((select sum(s.ext) 
                    from hblhead as h left join hblstock as s on s.trno=h.trno 
                    where h.projectid = head.projectid and h.subproject = head.subproject 
                  group by h.trno  
                  order by h.dateid desc limit 1),0),2) as curbal 
            from hbrhead as head 
            left join hbrstock as stock on stock.trno = head.trno 
            left join projectmasterfile as p on p.line = head.projectid 
            where  " . $filter . " 
            group by head.trno,head.docno,head.dateid,head.start,head.end,p.name,head.projectid,head.subproject ";
    } else {
      $qry = "select head.trno,head.docno,head.dateid,head.start,head.end,p.name as projectname,
                  format(ifnull((select ((select sum(br.amount) from hbrstock as br where br.trno = hd.brtrno)+hd.bal)-sum(st.ext) 
                  from blhead as hd 
                  left join blstock as st on st.trno = hd.trno 
                  where hd.projectid = head.projectid and hd.subproject = head.subproject 
                  group by hd.brtrno,hd.bal,hd.dateid 
                  order by hd.dateid desc limit 1),0)," . $this->companysetup->getdecimal('price', $config['params']) . ") as brbal,
                    format(sum(stock.ext)-ifnull((select sum(s.ext) 
                    from blhead as h left join blstock as s on s.trno=h.trno 
                    where h.projectid = head.projectid and h.subproject = head.subproject 
                  group by h.trno  
                  order by h.dateid desc limit 1),0),2) as curbal 
            from hbrhead as head 
            left join hbrstock as stock on stock.trno = head.trno 
            left join projectmasterfile as p on p.line = head.projectid 
            where  " . $filter . " 
            group by head.trno,head.docno,head.dateid,head.start,head.end,p.name,head.projectid,head.subproject ";
    }

    $data = $this->coreFunctions->opentable($qry, [$projectid, $subproject]);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function


  public function pendingjcsummary($config)
  {
    $summary = ['summary' => ['label' => 'Show Details', 'lookupclass' => 'lookupsetup', 'action' => 'pendingjcdetail']];

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'trno',
      'title' => 'List of Pending Job Completion Summary',
      'btns' => $summary,
      'style' => 'width:800px;max-width:800px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getjcsummary'
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'totalamt', 'label' => 'Amount', 'align' => 'left', 'field' => 'totalamt', 'sortable' => true, 'style' => 'font-size:16px;'));


    $data = $this->sqlquery->getpendingjcsummary($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingjcdetail($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Pending Job Completion Details',
      'btns' => [
        'summary' =>
        ['label' => 'Show Summary', 'lookupclass' => 'lookupsetup', 'action' => 'pendingjcsummary']
      ],
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getjcdetails'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'stage', 'label' => 'Stage', 'align' => 'left', 'field' => 'stage', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rrcost', 'label' => 'Amount', 'align' => 'left', 'field' => 'rrcost', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'disc', 'label' => 'Disc', 'align' => 'left', 'field' => 'disc', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rrqty', 'label' => 'OrderQty', 'align' => 'left', 'field' => 'rrqty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'qa', 'label' => 'Served', 'align' => 'left', 'field' => 'qa', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'pending', 'label' => 'Pending', 'align' => 'left', 'field' => 'pending', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $data = $this->sqlquery->getpendingjcdetails($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupconstructionorder($config)
  {
    // for testing CO
    $center = $config['params']['center'];
    $plotting = array(
      'codocno' => 'codocno',
      'cotrno' => 'cotrno',
      'projectcode' => 'projectcode',
      'phase' => 'phase',
      'housemodel' => 'housemodel',
      'blklot' => 'blklot',
      'lot' => 'lot',
      'projectid' => 'projectid',
      'phaseid' => 'phaseid',
      'modelid' => 'modelid',
      'blklotid' => 'blklotid'
    );
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'keyid',
      'title' => 'List of Construction Order',
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => $plotting
    );
    $cols = array(
      array('name' => 'codocno', 'label' => 'Document#', 'align' => 'left', 'field' => 'codocno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Client Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rem', 'label' => 'Remarks', 'align' => 'left', 'field' => 'rem', 'sortable' => true, 'style' => 'font-size:16px;'),
    );
    // lookup columns
    $qry = "select 0 as keyid ,0 as cotrno,'' as codocno,'' as dateid,'' as clientname,'' as rem,
            '' as projectcode,'' as phase,'' as housemodel,'' as blklot, '' as lot,0 as projectid,0 as  phaseid, 0 as modelid,0 as blklotid
            union all
            select head.trno as keyid ,head.trno as cotrno,head.docno as codocno,left(head.dateid,10) as dateid,head.clientname,head.rem,
            ifnull(project.code,'') as projectcode,
            ph.code as phase,hm.model as housemodel,
            bl.blk as blklot, bl.lot,
            head.projectid, head.phaseid, head.modelid, head.blklotid
            from hcohead as head 
            left join transnum as num on num.trno = head.trno
            left join projectmasterfile as project on project.line=head.projectid 
            left join phase as ph on ph.line = head.phaseid
            left join housemodel as hm on hm.line = head.modelid
            left join blklot as bl on bl.line = head.blklotid
            where head.doc='CC' and num.center = ? 
            and num.pctrno =0 ";
    $data = $this->coreFunctions->opentable($qry, [$center]);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupproductionorder($config)
  {
    // for testing po
    $center = $config['params']['center'];
    $plotting = array('productionorder' => 'podocno', 'potrno' => 'potrno');
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'keyid',
      'title' => 'List of Production Order',
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => $plotting
    );
    $cols = array(
      array('name' => 'podocno', 'label' => 'Document#', 'align' => 'left', 'field' => 'podocno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Client Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rem', 'label' => 'Remarks', 'align' => 'left', 'field' => 'rem', 'sortable' => true, 'style' => 'font-size:16px;'),
    );
    // lookup columns 
    $qry = "
            select 0 keyid , 0 as potrno, 'POOOOOOOOO1' as podocno,'' as dateid,'' as clientname,'' as rem";
    $data = $this->coreFunctions->opentable($qry, [$center]);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
} // class
