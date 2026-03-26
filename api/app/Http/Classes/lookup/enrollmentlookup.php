<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use Illuminate\Http\Request;
use App\Http\Requests;
use Carbon\Carbon;

class enrollmentlookup
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

  public function lookupschoolyear($config)
  {
    $plotting = array('sy' => 'sy', 'syid' => 'line');
    $plottype = 'plotgrid';
    $title = 'List of School Year';

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
      ['name' => 'sy', 'label' => 'School Year', 'align' => 'left', 'field' => 'sy', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select line, sy, issy from en_schoolyear";
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 913, '/tableentries/enrollmententry/en_schoolyear');
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'btnadd' => $btnadd];
  }

  public function lookupdepartment($config)
  {
    //default
    $plotting = array('deptcode' => 'deptcode', 'department' => 'deptname');
    $plottype = 'plotgrid';
    $title = 'List of Department';

    $lookupclass = isset($config['params']['lookupclass2']) ? $config['params']['lookupclass2'] : $config['params']['lookupclass'];
    switch ($lookupclass) {
      case 'lookupparentdepartment':
        $plotting = array('parentcode' => 'deptcode', 'parentname' => 'deptname');
        break;
      case 'coursedeptlookup':
      case 'insdeptlookup':
        $plottype = 'plothead';
        $plotting = array('deptid' => 'clientid', 'deptcode' => 'deptcode', 'department' => 'deptname', 'deancode' => 'code', 'deanname' => 'deanname');
        break;
      case 'lookupdeptgrid':
        $plotting = array('deptcode' => 'deptcode', 'departid' => 'clientid');
        break;
      default:
        $plotting = array('deptcode' => 'deptcode', 'department' => 'deptname');
        break;
    }

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
    $cols = array();
    $col = array('name' => 'deptcode', 'label' => 'Code', 'align' => 'left', 'field' => 'deptcode', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'deptname', 'label' => 'Name', 'align' => 'left', 'field' => 'deptname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select client.client as deptcode, client.clientname as deptname, client.clientid, dean.code,dean.clientname as deanname
            from client left join client as dean on dean.client=client.code
            where client.isdepartment = 1";

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 860, '/ledger/masterfile/department');

    switch ($lookupclass) {
      case 'coursedeptlookup':
      case 'insdeptlookup':
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
        break;
      case 'lookupdeptgrid':
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex, 'btnadd' => $btnadd];
        break;
      default:
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
        break;
    }
  } // end function

  public function enlookupdean($config)
  {
    //default
    $plotting = array(
      'deanname' => 'teachername',
    );
    $plottype = '';
    $title = 'List of Dean';

    $lookupclass = isset($config['params']['lookupclass2']) ? $config['params']['lookupclass2'] : $config['params']['lookupclass'];

    switch ($lookupclass) {
      case 'coursedeanlookup':
        $plottype = 'plothead';
        break;
      case 'inslookupdean':
        $plottype = 'plothead';
        $plotting = array(
          'deancode' => 'teachercode',
          'deanname' => 'teachername',
        );
        break;
      default:
        $plottype = 'plotgrid';
        break;
    }

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
    $cols = array();
    $col = array('name' => 'teachercode', 'label' => 'Code', 'align' => 'left', 'field' => 'teachercode', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'teachername', 'label' => 'Name', 'align' => 'left', 'field' => 'teachername', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'department', 'label' => 'Department', 'align' => 'left', 'field' => 'department', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select client.client as teachercode, client.clientname as teachername
            from client where isinstructor=1 order by client.client,client.clientname";

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 917, '/ledgergrid/enrollmententry/en_instructor');
    switch ($lookupclass) {
      case 'coursedeanlookup':
      case 'inslookupdean':
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
        break;
      default:
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
        break;
    }
  } // end function

  public function lookuprank($config)
  {
    //default
    $plotting = array('rank' => 'rank');
    $plottype = 'plotgrid';
    $title = 'List of Rank';
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
    $cols = array();
    $col = array('name' => 'rank', 'label' => 'Rank', 'align' => 'left', 'field' => 'rank', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "SELECT DISTINCT rank 
            FROM en_instructor 
            WHERE rank <> '' 
            ORDER BY rank";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function

  public function lookupcourse($config)
  {
    $plottype = 'plothead';
    switch (strtolower($config['params']['doc'])) {
      case 'ec':
        switch ($config['params']['lookupclass']) {
          case 'lookuplevelupgrid':
            $plottype = 'plotgrid';
            $plotting = array('levelup' => 'coursecode');
            break;
          default:
            $plotting = array('courseid' => 'line', 'coursecode' => 'coursecode', 'coursename' => 'coursename', 'dlevel' => 'levels', 'levelid' => 'levelline', 'ischinese' => 'ischinese');
            break;
        }

        break;
      case 'ea':
      case 'ed':
      case 'er':
        $plotting = array('courseid' => 'line', 'coursecode' => 'coursecode', 'coursename' => 'coursename', 'deptcode' => 'deptcode', 'dlevel' => 'levels', 'levelid' => 'level');
        break;
      case 'et':
        $plottype = 'plotgrid';
        $plotting = array('coursecode' => 'coursecode', 'courseid' => 'line');
        break;
      default:
        switch ($config['params']['lookupclass']) {
          case 'lookuplevelup':
            $plotting = array('levelup' => 'line', 'levelupcode' => 'coursecode');
            break;
          case 'lookupchineselevelup':
            $plotting = array('chineselevelup' => 'line', 'chineselevelupcode' => 'coursecode');
            break;
          case 'lookupchinesecourse':
            $plotting = array('chinesecourseid' => 'line', 'chinesecourse' => 'coursecode', 'chinesecoursename' => 'coursename');
            break;
          default:
            $plotting = array('course' => 'coursecode', 'coursename' => 'coursename', 'courseid' => 'line');
            break;
        }
        break;
    }

    $title = 'List of Course';

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
      ['name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'levels', 'label' => 'Level', 'align' => 'left', 'field' => 'levels', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $added = '';
    switch ($config['params']['doc']) {
      case 'EC':
        $added = '';
        break;
      default:
        switch ($config['params']['lookupclass']) {
          case 'lookupchinesecourse':
          case 'lookupchineselevelup':
            $added = ' and c.ischinese=1';
            break;
          default:
            $added = ' and c.ischinese=0';
            break;
        }
        break;
    }


    $qry = "select c.line,c.coursecode,c.coursename,c.level,c.deptcode,l.levels,l.line as levelline,case when c.ischinese = 1 then '1' else '0' end as ischinese from en_course as c left join en_levels as l on l.line=c.levelid where c.isinactive=0" . $added;

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 3640, '/ledgergrid/enrollmententry/en_course');

    if ($plottype == 'plotgrid') {
      $table = $config['params']['table'];
      $rowindex = $config['params']['index'];
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd, 'table' => $table, 'rowindex' => $rowindex];
    } else {
      return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
    }
  } //end function  

  public function lookupacno($config)
  {
    //default
    $plottype = 'plotgrid';
    $title = 'List of Account';

    $lookupclass = isset($config['params']['lookupclass2']) ? $config['params']['lookupclass2'] : $config['params']['lookupclass'];
    switch ($lookupclass) {
      case 'courseaccountlookup':
        $plotting = array('tfaccount' => 'acno', 'acnoname' => 'acnoname');
        break;
      case 'coursetfaccountlookup':
        $plottype = 'plothead';
        $plotting = array('tfaccount' => 'acno', 'acnoname' => 'acnoname');
        break;
      default:
        $plotting = array('acno' => 'acno', 'acnoname' => 'acnoname', 'acnoid' => 'acnoid');
        break;
    }

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
    $cols = array();
    $col = array('name' => 'acno', 'label' => 'Account Code', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select acno, acnoname, acnoid
            from coa 
            order by acno;";

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 2, '/unique/coa');
    switch ($lookupclass) {
      case 'coursetfaccountlookup':
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
        break;
      default:
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'btnadd' => $btnadd];
        break;
    }
  } // end function  

  public function lookuptypeoffees($config)
  {
    //default
    $plotting = array('feestype' => 'feestype');
    $plottype = 'plotgrid';
    $title = 'List of Type';

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
    $cols = array();
    $col = array('name' => 'feestype', 'label' => 'Type', 'align' => 'left', 'field' => 'feestype', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "SELECT line, feescode, feesdesc, feestype, acno, vat, amount FROM en_fees WHERE feestype <> '' ORDER BY feestype";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function

  public function lookupfees($config)
  {
    //default
    $plotting = array('feescode' => 'feescode', 'feesid' => 'line', 'rate' => 'amount');
    $plottype = 'plotgrid';
    $title = 'List of Fees';

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
    $cols = array();
    array_push($cols, array('name' => 'feescode', 'label' => 'Code', 'align' => 'left', 'field' => 'feescode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'feesdesc', 'label' => 'Description', 'align' => 'left', 'field' => 'feesdesc', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'feestype', 'label' => 'Type', 'align' => 'left', 'field' => 'feestype', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "SELECT line, feescode, feesdesc, feestype, acno, vat, amount FROM en_fees ORDER BY feestype";

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 934, '/tableentries/enrollmententry/en_fees');

    switch (($config['params']['doc'])) {
      case 'EN_CREDENTIALS':
        $index = $config['params']['index'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $rowindex, 'btnadd' => $btnadd];
        break;
      default:
        $index = $config['params']['index'];
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'table' => $table, 'rowindex' => $rowindex, 'btnadd' => $btnadd];
        break;
    }
  }

  public function lookupbooks($config)
  {

    $title = 'List of Items';

    switch (strtolower($config['params']['doc'])) {
      case 'ei':
        $lookupsetup = array(
          'type' => 'single',
          'title' => $title,
          'style' => 'width:900px;max-width:900px;'
        );
        break;
      default:
        $lookupsetup = array(
          'type' => 'multi',
          'rowkey' => 'itemid',
          'title' => $title,
          'style' => 'width:900px;max-width:900px;'
        );
        break;
    }

    $plotsetup = array(
      'plottype' => 'tableentry',
      'plotting' => [
        'line' => 'line',
        'itemid' => 'itemid',
        'barcode' => 'barcode',
        'itemname' => 'itemname',
        'uom' => 'uom',
        'isamt' => 'amount',
        'disc' => 'disc',
      ],
      'action' => 'getotherfees',
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'barcode', 'label' => 'Item Code', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'itemname', 'label' => 'Description', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'uom', 'label' => 'Uom', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'amt', 'label' => 'Amount', 'align' => 'left', 'field' => 'amt', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'disc', 'label' => 'Discount', 'align' => 'left', 'field' => 'disc', 'sortable' => true, 'style' => 'font-size:16px;'));

    switch (strtolower($config['params']['doc'])) {
      case 'ei':
        array_push($cols, array('name' => 'ext', 'label' => 'Total', 'align' => 'left', 'field' => 'ext', 'sortable' => true, 'style' => 'font-size:16px;'));
        break;
    }


    switch (strtolower($config['params']['doc'])) {
      case 'ei':
        $trno = $config['params']['trno'];
        $yr = $this->coreFunctions->datareader("select yr as value from en_sohead where doc='EI' and trno=? limit 1", [$trno]);

        $qry = "select item.barcode,item.itemname,item.uom,item.amt,item.disc,books.ext from en_sohead as head  
        left join  en_glyear as y on y.trno=head.curriculumtrno
        left join en_glbooks as books on books.trno=head.curriculumtrno  and y.line=books.cline left join item on item.itemid=books.itemid
        where head.trno=? and y.year=? ";

        $data = $this->coreFunctions->opentable($qry, [$trno, $yr]);
        break;
      default:
        $qry = "select  {$config['params']['row']['line']} as line, itemid, barcode, itemname, uom, amt, disc from item order by itemname";
        $data = $this->coreFunctions->opentable($qry);
        break;
    }

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupscheme($config)
  {
    //default
    $plotting = array('scheme' => 'scheme', 'schemeid' => 'line');
    $plottype = 'plotgrid';
    $title = 'List of Scheme';

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
    $cols = array();
    array_push($cols, array('name' => 'scheme', 'label' => 'Scheme', 'align' => 'left', 'field' => 'scheme', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line, scheme, orderscheme from en_scheme ORDER BY scheme";
    $btnadd = $this->sqlquery->checksecurity($config, 910, '/tableentries/enrollmententry/en_scheme');
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    $table = $config['params']['table'];
    $rowindex = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'table' => $table, 'rowindex' => $rowindex, 'btnadd' => $btnadd];
  }

  public function lookupsubcomponent($config)
  {
    $plotting = [];
    $title = 'List of Subcomponents';
  }

  public function lookupcomponent($config)
  {
    $plotting = [];
    $title = 'List of Components';
    $action = '';
    $table = '';
    $rowindex = 0;
    if (isset($config['params']['table'])) $table = $config['params']['table'];
    if (isset($config['params']['index'])) $rowindex = $config['params']['index'];
    if (isset($config['params']['lookupclass2'])) {
      $lookupclass = $config['params']['lookupclass2'];
    } else {
      if (isset($config['params']['lookupclass'])) $lookupclass = $config['params']['lookupclass'];
    }
    if (isset($config))
      $plottype = 'callback';
    $action = 'addtogrid';
    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getcomponents'
    );
    $lookupsetup = [
      'type' => 'multi',
      'rowkey' => 'line',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    ];
    $cols = [
      ['name' => 'gccode', 'label' => 'Code', 'align' => 'left', 'field' => 'gccode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'gcname', 'label' => 'Name', 'align' => 'left', 'field' => 'gcname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'gcpercent', 'label' => 'Percent', 'align' => 'left', 'field' => 'gcpercent', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select line, line as compid, gccode, gcname, gcpercent from en_gradecomponent order by line";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex, 'table' => $table, 'rowindex' => $rowindex];
  }


  public function lookupgecomponent($config)
  {
    $plotting = [];
    $title = 'List of Components';
    $action = '';
    $table = '';
    $rowindex = 0;
    if (isset($config['params']['table'])) $table = $config['params']['table'];
    if (isset($config['params']['index'])) $rowindex = $config['params']['index'];
    if (isset($config['params']['lookupclass2'])) {
      $lookupclass = $config['params']['lookupclass2'];
    } else {
      if (isset($config['params']['lookupclass'])) $lookupclass = $config['params']['lookupclass'];
    }
    if (isset($config))
      $plottype = 'callback';
    $action = 'addtogrid';
    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getcomponents'
    );
    $lookupsetup = [
      'type' => 'multi',
      'rowkey' => 'gcsubcode',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    ];
    $cols = [
      ['name' => 'gcsubcode', 'label' => 'Code', 'align' => 'left', 'field' => 'gcsubcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'topic', 'label' => 'Name', 'align' => 'left', 'field' => 'topic', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'noofitems', 'label' => 'Percent', 'align' => 'left', 'field' => 'noofitems', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $schedtrno = $config['params']['addedparams'][0];
    $schedline = $config['params']['addedparams'][1];
    $qry = "select head.trno, line, line as compid, gcsubcode, topic, noofitems from en_gshead as head left join en_gssubcomponent as gs on head.trno=gs.trno where head.schedtrno=? and head.schedline=?";
    $data = $this->coreFunctions->opentable($qry, [$schedtrno, $schedline]);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex, 'table' => $table, 'rowindex' => $rowindex];
  }

  public function lookupgecomp($config)
  {
    $plotting = [];
    $title = 'List of Grade Components';
    $action = '';
    $table = '';
    $rowindex = 0;
    if (isset($config['params']['table'])) $table = $config['params']['table'];
    if (isset($config['params']['index'])) $rowindex = $config['params']['index'];
    if (isset($config['params']['lookupclass2'])) {
      $lookupclass = $config['params']['lookupclass2'];
    } else {
      if (isset($config['params']['lookupclass'])) $lookupclass = $config['params']['lookupclass'];
    }
    if (isset($config))
      $plottype = 'callback';
    $action = 'addtogrid';
    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getcomp'
    );
    $lookupsetup = [
      'type' => 'multi',
      'rowkey' => 'trno',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    ];
    $cols = [
      ['name' => 'docno', 'label' => 'Document No.', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'scheddocno', 'label' => 'Sched Document', 'align' => 'left', 'field' => 'scheddocno', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'subjectcode', 'label' => 'Subject Code', 'align' => 'left', 'field' => 'subjectcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'subjectname', 'label' => 'Subject Name', 'align' => 'left', 'field' => 'subjectname', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $syid = $config['params']['addedparams'][0];
    $periodid = $config['params']['addedparams'][1];
    $courseid = $config['params']['addedparams'][2];
    $qry = "select head.trno, head.docno, head.scheddocno, sub.subjectcode, sub.subjectname 
    from en_gshead as head left join en_subject as sub on sub.trno=head.subjectid where head.syid=? and head.periodid=? and head.courseid=? and head.trno in (select trno from en_gssubcomponent)";
    $data = $this->coreFunctions->opentable($qry, [$syid, $periodid, $courseid]);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex, 'table' => $table, 'rowindex' => $rowindex];
  }


  public function lookupsubject($config)
  {
    //default
    $plotting = array('subjectid' => 'trno', 'subjectcode' => 'subjectcode', 'subjectname' => 'subjectname');
    $title = 'List of Subject';
    $action = '';
    $table = '';
    if (isset($config['params']['table'])) {
      $table = $config['params']['table'];
    }
    $rowindex = 0;
    if (isset($config['params']['index'])) {
      $rowindex = $config['params']['index'];
    }

    if (isset($config['params']['lookupclass2'])) {
      $lookupclass = $config['params']['lookupclass2'];
    } else {
      if (isset($config['params']['lookupclass'])) {
        $lookupclass = $config['params']['lookupclass'];
      }
    }

    $index = [];
    $plottype = 'plothead';
    switch ($lookupclass) {
      case 'lookupcoreq':
        $plotting = array(
          'coreq' => 'trno',
          'dcoreq' => 'subjectname'
        );
        break;
      case 'lookupprereq1':
        $plotting = array(
          'prereq1' => 'trno',
          'dprereq1' => 'subjectname'
        );
        break;
      case 'lookupprereq2':
        $plotting = array(
          'prereq2' => 'trno',
          'dprereq2' => 'subjectname'
        );
        break;
      case 'lookupprereq3':
        $plotting = array(
          'prereq3' => 'trno',
          'dprereq3' => 'subjectname'
        );
        break;
      case 'lookupprereq4':
        $plotting = array(
          'prereq4' => 'trno',
          'dprereq4' => 'subjectname'
        );
        break;
      case 'addsubject': //used in tableentry - entrysubject
        $plottype = 'callback';
        $action = 'addtogrid';
        $plotting = array(
          'subjectid' => 'trno',
          'subjectcode' => 'subjectcode',
          'subjectname' => 'subjectname'
        );
        break;
      case 'lookupsubject': // used in curriculum module
        $trno = $config['params']['trno'];
        $plotsetup = array(
          'plottype' => 'callback',
          'action' => 'getsubject'
        );
        $lookupsetup = array(
          'type' => 'multi',
          'rowkey' => 'trno',
          'title' => $title,
          'style' => 'width:100%;max-width:100%;'
        );
        break;
      case 'lookupasssubject':
        $plotsetup = array(
          'plottype' => 'callback',
          'action' => 'getsubject'
        );
        $lookupsetup = array(
          'type' => 'multi',
          'rowkey' => 'trno',
          'title' => $title,
          'style' => 'width:100%;max-width:100%;'
        );
        break;

      case 'lookupgridcoreq': // used in curriculum module
        $plottype = 'plotgrid';
        $plotting = array('coreq' => 'subjectcode', 'coreqid' => 'trno');
        break;

      case 'lookupgridpre1':
        $plottype = 'plotgrid';
        $plotting = array('pre1' => 'subjectcode', 'pre1id' => 'trno');
        break;
      case 'lookupgridpre2':
        $plottype = 'plotgrid';
        $plotting = array('pre2' => 'subjectcode', 'pre2id' => 'trno');
        break;
      case 'lookupgridpre3':
        $plottype = 'plotgrid';
        $plotting = array('pre3' => 'subjectcode', 'pre3id' => 'trno');
        break;
      case 'lookupgridpre4':
        $plottype = 'plotgrid';
        $plotting = array('pre4' => 'subjectcode', 'pre4id' => 'trno');
        break;

      case 'lookupsubjectgrid':
        $plottype = 'plotgrid';
        $plotting = array('subjectcode' => 'subjectcode', 'subjectid' => 'trno');
        break;

      case 'lookupsubjectassess':
        $title = 'List of Subject Equivalent Schedule';
        $plottype = 'plotgrid';
        $plotting = array('subjectcode' => 'subjectcode', 'subjectname' => 'subjectname', 'subjectid' => 'subjectid', 'instructorid' => 'instructorid', 'schedday' => 'schedday', 'schedstarttime' => 'schedstarttime', 'schedendtime' => 'schedendtime', 'linstructorcode' => 'client', 'instructorname' => 'clientname', 'refx' => 'trno', 'linex' => 'line', 'lbldgcode' => 'bldgcode', 'roomcode' => 'roomcode', 'roomid' => 'roomid', 'bldgid' => 'bldgid', 'hours' => 'hours', 'units' => 'units', 'lecture' => 'lecture', 'laboratory' => 'laboratory', 'screfx' => 'trno', 'sclinex' => 'line', 'schedref' => 'docno', 'refx' => 'refx', 'linex' => 'linex', 'ref' => 'ref', 'origtrno' => 'ertrno', 'origline' => 'erline', 'origsubjectid' => 'ersubjectid');
        break;
      case 'lookupsubjectreport':
        $plottype = 'plothead';
        $plotting = array(
          'subjectcode' => 'subjectcode',
          'subject' => 'subjectname',
          'subjtrno' => 'trno'
        );
        $lookupsetup = array(
          'type' => 'single',
          'title' => $title,
          'style' => 'width:100%;max-width:100%;'
        );
        break;

      default:
        $plottype = 'plotgrid';
        $plotting = array(
          'subjectcode' => 'subjectcode',
          'subjectname' => 'subjectname'
        );
        break;
    }


    switch ($lookupclass) {
      case 'lookupsubject':
      case 'lookupasssubject':
        $plotsetup = array(
          'plottype' => 'callback',
          'action' => 'getsubject'
        );
        $lookupsetup = array(
          'type' => 'multi',
          'rowkey' => 'trno',
          'title' => $title,
          'style' => 'width:100%;max-width:100%;'
        );

        break;
      case 'lookupsubject2':
        $trno = $config['params']['trno'];
        $plotsetup = ['plottype' => 'tableentry', 'action' => 'getsubject'];
        $lookupsetup = [
          'type' => 'multi',
          'rowkey' => 'trno',
          'title' => $title,
          'style' => 'width:100%;max-width:100%;'
        ];
        break;
      default:
        $lookupsetup = array(
          'type' => 'single',
          'title' => $title,
          'style' => 'width:100%;max-width:100%;'
        );

        $plotsetup = array(
          'plottype' => $plottype,
          'action' => $action,
          'plotting' => $plotting
        );
        break;
    }

    // lookup columns
    $cols = [
      ['name' => 'subjectcode', 'label' => 'Code', 'align' => 'left', 'field' => 'subjectcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'subjectname', 'label' => 'Name', 'align' => 'left', 'field' => 'subjectname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'units', 'label' => 'Units', 'align' => 'left', 'field' => 'units', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'lecture', 'label' => 'Lecture', 'align' => 'left', 'field' => 'lecture', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'laboratory', 'label' => 'Laboratory', 'align' => 'left', 'field' => 'laboratory', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'hours', 'label' => 'Hours', 'align' => 'left', 'field' => 'hours', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'client', 'label' => 'Instructor Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'clientname', 'label' => 'Instructor', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'bldgcode', 'label' => 'Bldg Code', 'align' => 'left', 'field' => 'bldgcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'roomcode', 'label' => 'Room Code', 'align' => 'left', 'field' => 'roomcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'schedday', 'label' => 'Sched Day', 'align' => 'left', 'field' => 'schedday', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'schedstarttime', 'label' => 'Sched Start', 'align' => 'left', 'field' => 'schedstarttime', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'schedend', 'label' => 'Sched End', 'align' => 'left', 'field' => 'schedendtime', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    switch ($lookupclass) {
      case 'lookupsubject2':
        $qry = "select trno, {$config['params']['row']['line']} as line, subjectcode, subjectname, units, lecture, laboratory, hours from en_subject";
        break;
      case 'lookupsubjectreport':
        $qry = "select * from en_subject order by subjectname";
        break;
      default:
        $qry = "select trno, subjectcode, subjectname, units, lecture, laboratory, hours from en_subject";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    switch ($lookupclass) {
      case 'lookupgridcoreq':
      case 'lookupgridpre1':
      case 'lookupgridpre2':
      case 'lookupgridpre3':
      case 'lookupgridpre4':
      case 'lookupsubjectgrid':
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex, 'table' => $table, 'rowindex' => $rowindex];
        break;
      case 'lookupasssubject':
        $schedcode = $this->coreFunctions->getfieldvalue("en_sohead", "schedcode", "trno=?", [$trno]);
        $qry = "select s.trno, e.subjectcode, e.subjectname, ss.units, ss.lecture, ss.laboratory, ss.hours from en_glsubject as ss left join en_glhead as s on s.trno = ss.trno left join en_subject as e on e.trno=ss.subjectid where s.docno =?";

        $data = $this->coreFunctions->opentable($qry, [$schedcode]);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
        break;
      case 'lookupsubjectassess':
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];
        $screfx = $config['params']['row']['screfx'];
        $sclinex = $config['params']['row']['sclinex'];
        $periodid = $this->coreFunctions->getfieldvalue("en_sjhead", "periodid", "trno=?", [$trno]);
        $subjectid = $this->coreFunctions->getfieldvalue("en_sjsubject", "subjectid", "trno=? and line=?", [$trno, $line]);
        $qry = "select s.trno, e.subjectcode, e.subjectname, ss.units, ss.lecture, ss.laboratory,ss.hours,i.client,i.clientname,ss.schedday, ss.schedstarttime,ss.schedendtime,ss.line,ss.roomid,ss.bldgid,r.roomcode,b.bldgcode,ss.instructorid,ss.subjectid,e.trno as subjectid,s.docno,0 as refx,0 as linex,'' as ref," . $screfx . " as ertrno," . $sclinex . " as erline," . $subjectid . " as ersubjectid
          from en_glsubject as ss left join en_glhead as s on s.trno = ss.trno 
          left join en_subject as e on e.trno=ss.subjectid left join client as i on i.clientid=ss.instructorid left join en_rooms as r on r.line=ss.roomid
          left join en_subjectequivalent as q on q.subjectid=ss.subjectid
          left join en_bldg as b on b.line=ss.bldgid where s.doc='ES' and s.periodid =? and q.subjectmain=?";

        $data = $this->coreFunctions->opentable($qry, [$periodid, $subjectid]);
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'table' => $table, 'rowindex' => $rowindex];
        break;
      case 'lookupsubjectreport':
        $btnadd = $this->sqlquery->checksecurity($config, 3640, '/tableentries/enrollmententry/en_subject');
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
        break;
      default:
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
        break;
    }
  } // end function

  public function lookupcredentialsubject($config)
  {
    //default
    $title = 'List of Subject';
    $action = '';
    $table = 'en_credentials';
    $rowindex = 0;
    if (isset($config['params']['index'])) {
      $rowindex = $config['params']['index'];
    }

    if (isset($config['params']['lookupclass2'])) {
      $lookupclass = $config['params']['lookupclass2'];
    } else {
      if (isset($config['params']['lookupclass'])) {
        $lookupclass = $config['params']['lookupclass'];
      }
    }

    $index = [];
    $plottype = 'plotgrid';
    $plotting = array('subjectcode' => 'subjectcode', 'subjectid' => 'trno', 'subjectname' => 'subjectname');

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
    $cols = array();
    array_push($cols, array('name' => 'subjectcode', 'label' => 'Code', 'align' => 'left', 'field' => 'subjectcode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'subjectname', 'label' => 'Name', 'align' => 'left', 'field' => 'subjectname', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'units', 'label' => 'Units', 'align' => 'left', 'field' => 'units', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'lecture', 'label' => 'Lecture', 'align' => 'left', 'field' => 'lecture', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'laboratory', 'label' => 'Laboratory', 'align' => 'left', 'field' => 'laboratory', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'hours', 'label' => 'Hours', 'align' => 'left', 'field' => 'hours', 'sortable' => true, 'style' => 'font-size:16px;'));



    $qry = "select trno, subjectcode, subjectname, units, lecture, laboratory, hours from en_subject";

    $data = $this->coreFunctions->opentable($qry);

    $table = "en_credentials";
    $rowindex = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
  } // end function

  public function lookupaddotherfees($config)
  {

    $title = 'List of Other Fees';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'line',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'plotting' => [
        'line' => 'line',
        'acnoid' => 'acnoid',
        'amount' => 'amount',
        'scheme' => 'scheme',
        'feestype' => 'feestype',
      ],
      'action' => 'getotherfees',
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'feescode', 'label' => 'Fees Code', 'align' => 'left', 'field' => 'feescode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'feestype', 'label' => 'Fees Type', 'align' => 'left', 'field' => 'feestype', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'acno', 'label' => 'Account', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'amount', 'label' => 'Amount', 'align' => 'left', 'field' => 'amount', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line, feescode, feestype, acnoid, amount, scheme from en_fees where feestype='OTHERS' ";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupaddstudents($config)
  {

    $title = 'List of Registered Students';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'clientid',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'plotting' => [
        'client' => 'client',
        'clientname' => 'clientname',
        'docno' => 'docno',
        'dateid' => 'dateid',
      ],
      'action' => 'getotherfees',
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'clientname', 'label' => 'Student Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'docno', 'label' => 'Document #', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'));

    $tableid = $config['params']['tableid'];
    $qry = "select head.trno,head.docno,head.yr,head.curriculumdocno,head.adviserid,head.courseid,head.periodid,head.syid,head.semid,head.sectionid from en_glhead as head where head.doc='es' and head.trno=?";
    $datahead = $this->coreFunctions->opentable($qry, [$tableid]);

    foreach ($datahead as $key => $value) {
      if (!empty($datahead[$key]->trno)) {
        $docno = $datahead[$key]->docno;
        $curriculumdocno = $datahead[$key]->curriculumdocno;
        $adviserid = $datahead[$key]->adviserid;
        $courseid = $datahead[$key]->courseid;
        $periodid = $datahead[$key]->periodid;
        $syid = $datahead[$key]->syid;
        $semid = $datahead[$key]->semid;
        $sectionid = $datahead[$key]->sectionid;
        $yr = $datahead[$key]->yr;

        switch ($config['params']['lookupclass']) {
          case 'entrystudlevelup':
            $qry =  "select distinct head.docno,head.dateid,client.client,client.clientname,client.clientid 
            from glhead as head left join client on client.clientid=head.clientid left join glsubject as subject on subject.trno=head.trno
            left join en_glhead as h on h.trno=subject.screfx left join en_glhead as hs on hs.docno=h.curriculumdocno
            left join en_glyear as ys on ys.trno=hs.trno and ys.year=head.yr left join en_studentinfo as si on si.clientid=client.clientid
            where subject.screfx=? and head.doc='ER'  and head.syid=? and head.periodid=? and head.courseid=? and head.semid=? and head.sectionid=? and head.yr=? and si.levelup<>ys.levelup";

            $data = $this->coreFunctions->opentable($qry, [$tableid, $syid, $periodid, $courseid, $semid, $sectionid, $yr]);
            break;
          default:
            $qry =  "select distinct head.docno,head.dateid,client.client,client.clientname,client.clientid from glhead as head left join client on client.clientid=head.clientid left join glsubject as subject on subject.trno=head.trno
            where subject.screfx=0 and head.doc='ER'  and head.syid=? and head.periodid=? and head.courseid=? and subject.semid=? and head.sectionid=? and head.yr=?";

            $data = $this->coreFunctions->opentable($qry, [$syid, $periodid, $courseid, $semid, $sectionid, $yr]);
            break;
        }
      }
    }

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupaddcredentials($config)
  {
    // $plottype = 'plotgrid';
    $title = 'List of Credentialx';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'line',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'plotting' => [
        'credentialid' => 'line',
        'credentialcode' => 'credentialcode',
        'acnoid' => 'acnoid',
        'feesid' => 'feesid',
        'amt' => 'amt',
        'camt' => 'camt',
        'percentdisc' => 'percentdisc'
      ],
      'action' => 'getcredentials',
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'credentialcode', 'label' => 'Code', 'align' => 'left', 'field' => 'credentialcode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'credentials', 'label' => 'Description', 'align' => 'left', 'field' => 'credentials', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line, credentials, amt, particulars, percentdisc, case when percentdisc <>'' then percentdisc/100 else amt end as camt, credentialcode, acno, acnoname, feescode, scheme, subjectcode, subjectname, acnoid, feesid from en_credentials order by credentials";

    $data = $this->coreFunctions->opentable($qry);
    // $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } // end function  

  public function lookupcredential($config)
  {
    //default
    $plotting = array(
      'line' => 'credentialid',
      'credentialcode' => 'credentialcode',
      'credentials' => 'credentials'
    );
    // $plottype = 'plotgrid';
    $title = 'List of Credential';

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
    array_push($cols, array('name' => 'credentialcode', 'label' => 'Code', 'align' => 'left', 'field' => 'credentialcode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'credentials', 'label' => 'Description', 'align' => 'left', 'field' => 'credentials', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line, credentials, amt, particulars, percentdisc, credentialcode, acno, acnoname, feescode, scheme, subjectcode, subjectname from en_credentials order by credentials";

    $data = $this->coreFunctions->opentable($qry);
    // $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } // end function  


  public function lookupsection($config)
  {

    $plotting = array('section' => 'section', 'sectionid' => 'line');
    $plottype = 'plothead';
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
      ['name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;'],
      // ['name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $courseid = $config['params']['addedparams'][0];

    $qry = "select * from en_section where courseid=?";
    $data = $this->coreFunctions->opentable($qry, [$courseid]);
    if($config['params']['lookupclass'] == 'lookupsection2') {
      if ($courseid != null && $courseid != '') {
        $qry = "select * from en_section where courseid=?";
        $data = $this->coreFunctions->opentable($qry, [$courseid]);
      } else {
        $data = [];
      }
    }
    $btnadd = $this->sqlquery->checksecurity($config, 918, '/ledgergrid/enrollmententry/en_course');

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  } //end function

  public function lookupyr($config)
  {
    $plottype = 'plothead';
    $title = 'List of Grade/Year';
    switch ($config['params']['doc']) {
      case 'ES':
      case 'EA':
      case 'EI':
        $plotting = ['yr' => 'year', 'semid' => 'semid', 'terms' => 'term'];
        break;
      default:
        $plotting = ['yr' => 'yearnum'];
        break;
    }
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
    switch ($config['params']['doc']) {
      case 'ES':
      case 'EA':
      case 'EI':
        $cols = [
          ['name' => 'year', 'label' => 'Year', 'align' => 'left', 'field' => 'year', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        $curriculumdocno = $config['params']['addedparams'][0];
        $trno = $this->coreFunctions->datareader("select trno as value from en_glhead where docno=? and doc='EC'", [$curriculumdocno]);
        $data = [];
        if ($trno != '') {
          $qry = "select yr.year, yr.semid, sem.term from en_glyear as yr left join en_term as sem on sem.line=yr.semid where trno=?";
          $data = $this->coreFunctions->opentable($qry, [$trno]);
        }
        break;
      default:
        switch ($config['params']['lookupclass']) {
          case 'report':
            $cols = [
              ['name' => 'yearnum', 'label' => 'Year', 'align' => 'left', 'field' => 'yearnum', 'sortable' => true, 'style' => 'font-size:16px;']
            ];
            $courseid = $config['params']['addedparams'][0];
            $qry = "select distinct year as yearnum from en_glyear as year left join en_glhead as head on head.trno=year.trno where head.courseid=?";
            $data = $this->coreFunctions->opentable($qry, [$courseid]);
            break;
          default:
            $cols = [
              ['name' => 'year', 'label' => 'Grade/Year', 'align' => 'left', 'field' => 'yearnum', 'sortable' => true, 'style' => 'font-size:16px;']
            ];
            $courseid = $config['params']['addedparams'][0];
            $qry = "select  distinct stock.yearnum from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno
                    where head.doc='EC' and stock.courseid=?";
            $data = $this->coreFunctions->opentable($qry, [$courseid]);
            break;
        }

        break;
    }
    // $btnadd = $this->sqlquery->checksecurity($config, 920, '/ledgergrid/enrollmententry/en_subject');

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function



  public function lookupperiod($config)
  {
    switch (strtoupper($config['params']['doc'])) {
      case 'ET':
      case 'ES':
      case 'EA':
      case 'ER':
      case 'ED':
      case 'EF':
      case 'EH':
      case 'EM':
        $plotting = array('period' => 'code', 'periodid' => 'line');
        break;

      default:
        $plotting = array('period' => 'code');
        break;
    }

    $plottype = 'plothead';
    $title = 'List of Period';

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
      ['name' => 'period', 'label' => 'Period', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select * from en_period";
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 3640, '/tableentries/enrollmententry/en_period');

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  } //end function

  public function lookupinstructor($config)
  {
    $table = '';
    if (isset($config['params']['table'])) {
      $table = $config['params']['table'];
    }
    $rowindex = 0;
    if (isset($config['params']['index'])) {
      $rowindex = $config['params']['index'];
    }

    if (isset($config['params']['lookupclass2'])) {
      $lookupclass = $config['params']['lookupclass2'];
    } else {
      if (isset($config['params']['lookupclass'])) {
        $lookupclass = $config['params']['lookupclass'];
      }
    }
    switch ($lookupclass) {
      case 'lookupadviser':
        $plotting = array('adviserid' => 'clientid', 'advisercode' => 'client', 'advisername' => 'clientname');
        $plottype = 'plothead';
        break;
      case 'lookupschedinstructor':
        $plotting = array(
          'instructorid' => 'clientid', 'eainstructorcode' => 'client', 'instructorname' => 'clientname',
          'bldgid' => 'bldgid', 'roomid' => 'roomid', 'schedday' => 'schedday', 'schedstarttime' => 'schedstarttime',
          'schedendtime' => 'schedendtime', 'lbldgcode' => 'bldgcode', 'roomcode' => 'roomcode', 'refx' => 'trno', 'linex' => 'line'
        );
        $plottype = 'plotgrid';
        break;
      case 'lookupprincipal':
        $plotting = array('principalid' => 'clientid', 'instructorcode' => 'client', 'instructorname' => 'clientname');
        $plottype = 'plotgrid';
        break;
      default:
        $plotting = array('instructorid' => 'clientid', 'linstructorcode' => 'client', 'instructorname' => 'clientname');
        $plottype = 'plotgrid';
        break;
    }

    switch ($lookupclass) {
      case 'lookupschedinstructor':
        $title = 'List of Instructor Schedule';
        break;
      case 'lookupprincipal':
        $title = 'List of Principal';
        break;
      default:
        $title = 'List of Instructor';
        break;
    }

    // if ($config['params']['lookupclass'] == 'lookupschedinstructor') {
    //   $lookupsetup = array(
    //     'type' => 'single',
    //     'title' => $title,
    //     'style' => 'width:100%;max-width:100%;'
    //   );
    // } else {
    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    // }

    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns

    switch ($lookupclass) {
      case 'lookupschedinstructor':

        $cols = [
          ['name' => 'client', 'label' => 'Instructor Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'clientname', 'label' => 'Instructor Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'yr', 'label' => 'Year', 'align' => 'left', 'field' => 'yr', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'subjectcode', 'label' => 'Subject Code', 'align' => 'left', 'field' => 'subjectcode', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'subjectname', 'label' => 'Subject Desc', 'align' => 'left', 'field' => 'subjectname', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'schedday', 'label' => 'Sched Day', 'align' => 'left', 'field' => 'schedday', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'schedstarttime', 'label' => 'Start Time', 'align' => 'left', 'field' => 'schedstarttime', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'schedendtime', 'label' => 'End Time', 'align' => 'left', 'field' => 'schedendtime', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'bldgcode', 'label' => 'Bldg', 'align' => 'left', 'field' => 'bldgcode', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'roomcode', 'label' => 'Room', 'align' => 'left', 'field' => 'roomcode', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];

        $subjectid = $config['params']['row']['subjectid'];

        $qry = "select i.clientid,i.client,i.clientname, concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,head.docno,c.coursecode,c.coursename,head.yr,t.term, head.section,s.subjectcode,s.subjectname,stock.schedday, stock.schedstarttime,stock.schedendtime,b.bldgcode,r.roomcode,stock.bldgid,stock.roomid
        from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno left join en_course as c on c.line=head.courseid
        left join en_subject as s on s.trno=stock.subjectid left join en_bldg as b on b.line=stock.bldgid left join en_rooms as r on r.line=stock.roomid
        left join client as i on i.clientid=stock.instructorid 
        left join en_term as t on t.line=head.semid where head.doc = 'es' and stock.subjectid=" . $subjectid;
        break;
      default:
        $cols = [
          ['name' => 'client', 'label' => 'Instructor Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'clientname', 'label' => 'Instructor Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'department', 'label' => 'Department', 'align' => 'left', 'field' => 'department', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        $qry = "select client.clientid,client.client,client.clientname,client.department from client where isinstructor=1 order by clientname ";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 917, '/ledgergrid/enrollmententry/en_instructor');


    $index = [];
    switch ($lookupclass) {
      case 'lookupadviser':
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
        break;
      case 'lookupprincipal':
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd, 'index' => $rowindex];
        break;
      default:
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'table' => $table, 'rowindex' => $rowindex, 'btnadd' => $btnadd];
        break;
    }
  } //end function

  public function lookuprooms2($config)
  {
    $lookupclass = '';
    if (isset($config['params']['lookupclass2'])) $lookupclass = $config['params']['lookupclass2'];
    if (isset($config['params']['lookupclass'])) $lookupclass = $config['params']['lookupclass'];

    $plotting = array('roomid' => 'roomid', 'roomcode' => 'roomcode');
    $plottype = 'plothead';
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Rooms',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = [
      ['name' => 'bldgcode', 'label' => 'Bldg Code', 'align' => 'left', 'field' => 'bldgcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'bldgname', 'label' => 'Bldg Name', 'align' => 'left', 'field' => 'bldgname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'roomcode', 'label' => 'Room Code', 'align' => 'left', 'field' => 'roomcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'roomname', 'label' => 'Room Name', 'align' => 'left', 'field' => 'roomname', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select b.line as bldgid, r.line as roomid, b.bldgcode, b.bldgname, r.roomcode, r.roomname from en_bldg as b left join en_rooms as r on r.bldgid = b.line where b.line = '{$config['params']['addedparams'][0]}'";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuprooms($config)
  {
    $table = '';
    if (isset($config['params']['table'])) {
      $table = $config['params']['table'];
    }
    $rowindex = 0;
    if (isset($config['params']['index'])) {
      $rowindex = $config['params']['index'];
    }

    if (isset($config['params']['lookupclass2'])) {
      $lookupclass = $config['params']['lookupclass2'];
    } else {
      if (isset($config['params']['lookupclass'])) {
        $lookupclass = $config['params']['lookupclass'];
      }
    }

    switch ($config['params']['doc']) {
      case 'EF':
      case 'EH':
      case 'EM':
        $plotting = array('roomcode' => 'roomcode', 'bldgcode' => 'bldgcode', 'roomid' => 'roomid', 'bldgid' => 'bldgid');
        $plottype = 'plothead';
        $title = 'List of Buildings';
        break;
      default:
        $plotting = array('roomcode' => 'roomcode', 'lbldgcode' => 'bldgcode', 'roomid' => 'roomid', 'bldgid' => 'bldgid');
        $plottype = 'plotgrid';
        $title = 'List of Rooms';
        break;
    }


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
      ['name' => 'bldgcode', 'label' => 'Bldg Code', 'align' => 'left', 'field' => 'bldgcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'bldgname', 'label' => 'Bldg Name', 'align' => 'left', 'field' => 'bldgname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'roomcode', 'label' => 'Room Code', 'align' => 'left', 'field' => 'roomcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'roomname', 'label' => 'Room Name', 'align' => 'left', 'field' => 'roomname', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select b.line as bldgid,r.line as roomid,b.bldgcode,b.bldgname,r.roomcode,r.roomname from en_bldg as b left join en_rooms as r on r.bldgid=b.line";
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 933, '/ledgergrid/enrollmententry/en_roomlist');


    $index = [];
    $table = '';
    if (isset($config['params']['table'])) {
      $table = $config['params']['table'];
    }
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'table' => $table, 'rowindex' => $rowindex, 'btnadd' => $btnadd];
  } //end function

  public function lookupdsection($config)
  {

    $plotting = array('section' => 'section', 'sectionid' => 'line');
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
      ['name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;'],
      // ['name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select * from en_section";
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 3640, '/tableentries/enrollmententry/en_section');


    $index = [];
    $table = $config['params']['table'];
    $rowindex = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'table' => $table, 'rowindex' => $rowindex];
  } //end function


  public function lookupsemester($config)
  {
    $title = 'List of Semester';

    $lookupclass = $config['params']['lookupclass'];
    switch ($lookupclass) {
      case 'lookupsemestergrid':
        $plottype = 'plotgrid';
        $plotting = array('term' => 'term', 'semid' => 'line');
        break;

      default:
        switch ($config['params']['doc']) {
          case 'EK':
            $plotting = array('term' => 'term', 'semid' => 'line');
            $plottype = 'plotgrid';
            break;
          default:
            $plotting = array('terms' => 'term', 'semid' => 'line');
            $plottype = 'plothead';
            break;
        }
        break;
    }

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
      ['name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select * from en_term";
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 915, '/tableentries/enrollmententry/en_semester');

    switch ($lookupclass) {
      case 'lookupsemestergrid':
        $rowindex = $config['params']['index'];
        if ($config['params']['doc'] == 'EN_PERIOD') {
          return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd, 'index' => $rowindex];
        } else {
          $table = $config['params']['table'];
          return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd, 'table' => $table, 'rowindex' => $rowindex];
        }
        break;
      default:
        switch ($config['params']['doc']) {
          case 'EK':
            $index = $config['params']['index'];
            return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index, 'btnadd' => $btnadd];
            break;
          default:
            return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
            break;
        }
        break;
    }
  } //end function

  public function lookupdean($config)
  {
    //default
    $plotting = array(
      'code' => 'teachercode',
      'rem2' => 'teachername'
    );
    $plottype = 'plothead';
    $title = 'List of Dean';
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
    $cols = array();
    $col = array('name' => 'teachercode', 'label' => 'Code', 'align' => 'left', 'field' => 'teachercode', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'teachername', 'label' => 'Name', 'align' => 'left', 'field' => 'teachername', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);
    $col = array('name' => 'department', 'label' => 'Department', 'align' => 'left', 'field' => 'department', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select client.client as teachercode, client.clientname as teachername
            from client where isinstructor=1 order by client.client,client.clientname";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } // end function

  public function lookuplevel($config)
  {
    $plottype = 'plothead';

    switch (strtolower($config['params']['doc'])) {
      case 'en_subject':
      case 'ec':
      case 'er':
        $plotting = array('levelid' => 'line', 'dlevel' => 'levels', 'level' => 'line');
        break;
      case 'eg':
      case 'ea':
      case 'ed':
      case 'ei':
      case 'ej':
      case 'ek':
        $plotting = ['levelid' => 'line', 'levels' => 'levels'];
        break;
      case 'en_course':
        $plotting = array('levelid' => 'line', 'level' => 'levels');
        break;
      case 'en_instructor':
        $plotting = array('levels' => 'levels');
        break;
      case 'et':
      case 'en_attendancesetup':
        $plottype = 'plotgrid';
        $plotting = array('levelid' => 'line', 'levels' => 'levels');
        break;
      default:
        $plotting = array('groupid' => 'levels');
        break;
    }


    $title = 'List of Level';

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
    $cols = array();
    $col = array('name' => 'levels', 'label' => 'Level', 'align' => 'left', 'field' => 'levels', 'sortable' => true, 'style' => 'font-size:16px;');
    array_push($cols, $col);

    $qry = "select line, levels 
            FROM en_levels 
            WHERE levels <> '' 
            ORDER BY orderlevels";

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 914, '/tableentries/enrollmententry/en_levels');
    switch (strtolower($config['params']['doc'])) {
      case 'en_attendancesetup':
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'rowindex' => $rowindex, 'index' => $rowindex, 'btnadd' => $btnadd];
        break;
      case 'et':
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex, 'btnadd' => $btnadd];
        break;
      default:
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
        break;
    }
  } // end function

  public function lookupsy($config)
  {

    switch (strtoupper($config['params']['doc'])) {
      case 'ET':
      case 'ES':
      case 'EC':
      case 'EG':
      case 'EF':
      case 'EH':
      case 'EM':
        $plotting = array('syid' => 'line', 'sy' => 'sy');
        break;

      default:
        switch ($config['params']['lookupclass']) {
          case 'report':
            $plotting = array('syid' => 'line', 'sy' => 'sy');
            break;
          default:
            $plotting = array('sy' => 'sy');
            break;
        }

        break;
    }


    $plottype = 'plothead';
    $title = 'List of School Year';

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
      ['name' => 'sy', 'label' => 'School Year', 'align' => 'left', 'field' => 'sy', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select * from en_schoolyear";
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 3640, '/tableentries/enrollmententry/en_schoolyear');

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  } //end function


  public function lookupcoursecode($config)
  {
    $plotting = array('courseid' => 'line', 'coursecode' => 'coursecode', 'coursename' => 'coursename', 'ischinese' => 'ischinese');
    if ($config['params']['doc'] == 'EJ') {
      $plotting = array('courseid' => 'line', 'coursecode' => 'coursecode', 'coursename' => 'coursename', 'ischinese' => 'ischinese', 'levels' => 'level');
    }
    $plottype = 'plothead';
    $title = 'List of Course';

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
      ['name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select line,coursecode,coursename,level,case when ischinese=1 then '1' else '0' end as ischinese from en_course where isinactive=0";

    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 918, '/ledgergrid/enrollmententry/en_course');

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  } //end function


  public function lookupcurriculum($config)
  {
    //default

    switch ($config['params']['doc']) {
      case 'EI':
        $plotting = array('curriculumcode' => 'curriculumcode', 'curriculumname' => 'curriculumname', 'curriculumdocno' => 'curriculumdocno', 'curriculumtrno' => 'curriculumtrno', 'levelid' => 'levelid', 'levels' => 'level');
        break;
      default:
        $plotting = array('curriculumcode' => 'curriculumcode', 'curriculumname' => 'curriculumname', 'curriculumdocno' => 'curriculumdocno', 'curriculumtrno' => 'curriculumtrno');
        break;
    }

    $title = 'List of Curriculum';
    $action = '';
    $table = '';
    if (isset($config['params']['table'])) {
      $table = $config['params']['table'];
    }
    $rowindex = 0;
    if (isset($config['params']['index'])) {
      $rowindex = $config['params']['index'];
    }

    if (isset($config['params']['lookupclass2'])) {
      $lookupclass = $config['params']['lookupclass2'];
    } else {
      if (isset($config['params']['lookupclass'])) {
        $lookupclass = $config['params']['lookupclass'];
      }
    }

    $index = [];
    switch ($config['params']['doc']) {
      case 'ES':
      case 'EN_STUDENT':
      case 'EI':
        $plottype = 'plothead';
        $setupaction = '';
        $type = 'single';
        break;
      default:
        $plottype = 'callback';
        $setupaction = 'generatecurriculum';
        $type = 'multi';
        break;
    }
    $plotsetup = array(
      'plotting' => $plotting,
      'plottype' => $plottype,
      'action' => $setupaction
    );

    switch ($config['params']['doc']) {
      case 'EG':
        $lookupsetup = array(
          'type' => $type,
          'rowkey' => 'yearnum',
          'title' => $title,
          'style' => 'width:900px;max-width:900px;'
        );
        break;
      default:
        $lookupsetup = array(
          'type' => $type,
          'rowkey' => 'docno',
          'title' => $title,
          // 'style' => 'width:900px;max-width:900px;'
          'style' => 'width:100%;max-width:100%;'
        );
        break;
    }



    // lookup columns
    switch ($config['params']['doc']) {
      case 'EC':
        $cols = [
          ['name' => 'docno', 'label' => 'Document #', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'curriculumcode', 'label' => 'Curriculum Code', 'align' => 'left', 'field' => 'curriculumcode', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'curriculumname', 'label' => 'Curriculum Name', 'align' => 'left', 'field' => 'curriculumname', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'level', 'label' => 'Level', 'align' => 'left', 'field' => 'level', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        break;
      default:
        $cols = [
          ['name' => 'docno', 'label' => 'Document #', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'curriculumcode', 'label' => 'Curriculum Code', 'align' => 'left', 'field' => 'curriculumcode', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'curriculumname', 'label' => 'Curriculum Name', 'align' => 'left', 'field' => 'curriculumname', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'level', 'label' => 'Level', 'align' => 'left', 'field' => 'level', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'sy', 'label' => 'School Year', 'align' => 'left', 'field' => 'sy', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'yearnum', 'label' => 'Year', 'align' => 'left', 'field' => 'yearnum', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'terms', 'label' => 'Semester', 'align' => 'left', 'field' => 'terms', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        break;
    }

    $data = $this->sqlquery->getcurriculum($config);
    $btnadd = $this->sqlquery->checksecurity($config, 598, '/tableentries/enrollmententry/entryterms');

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function lookupsched($config)
  {
    $type = '';
    switch ($config['params']['doc']) {
      case 'EF':
      case 'EH':
      case 'EM':
      case 'EK':
      case 'EN':
        $type = 'single';
        $plotsetup = [
          'plottype' => 'plothead',
          'action' => '',
          'plotting' => [
            'scheddocno' => 'scheddocno',
            'sectionid' => 'sectionid',
            'section' => 'section',
            'coursecode' => 'coursecode',
            'coursename' => 'coursename',
            'courseid' => 'courseid',
            'curriculumcode' => 'curriculumcode',
            'curriculumdocno' => 'curriculumdocno',
            'subjectid' => 'subjectid',
            'subjectcode' => 'subjectcode',
            'subjectname' => 'subjectname',
            'roomid' => 'roomid',
            'bldgid' => 'bldgid',
            'roomcode' => 'roomcode',
            'bldgcode' => 'bldgcode',
            'schedday' => 'schedday',
            'schedtime' => 'schedtime',
            'yr' => 'yr',
            'semid' => 'semid',
            'terms' => 'term',
            'schedtrno' => 'trno',
            'schedline' => 'line',
            'ischinese' => 'ischinese',
            'levelid' => 'levelid',
            'levels' => 'level'
          ]
        ];
        break;
      case 'EN_STUDENT':
        $type = 'single';
        $plotsetup = [
          'plottype' => 'plothead',
          'action' => '',
          'plotting' => [
            'schedcode' => 'docno',
            'schedtrno' => 'trno',
            'section' => 'section',
            'sectionid' => 'sectionid'
          ]
        ];
        break;
      default:
        $type = 'multi';
        $plotsetup = array(
          'plottype' => 'callback',
          'action' => 'schedsummary'
        );
        break;
    }
    $lookupsetup = array(
      'type' => $type,
      'rowkey' => 'trno',
      'title' => 'Lists of Schedules',
      'btns' => ['summary' => ['label' => 'Show Details', 'lookupclass' => 'lookupsetup', 'action' => 'scheddetail']],
      // 'style' => 'width:800px;max-width:800px;'
      'style' => 'width:100%;max-width:100%;'
    );

    // lookup columns
    switch ($config['params']['doc']) {
      case 'EF':
      case 'EH':
      case 'EM':
      case 'EK':
      case 'EN':
        $cols = [
          ['name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'subjectcode', 'label' => 'Subject Code', 'align' => 'left', 'field' => 'subjectcode', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'subjectname', 'label' => 'Subject Name', 'align' => 'left', 'field' => 'subjectname', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'yr', 'label' => 'Year', 'align' => 'left', 'field' => 'yr', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'levels', 'label' => 'Level', 'align' => 'left', 'field' => 'level', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        break;
      default:
        $cols = array(
          array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'yr', 'label' => 'Year', 'align' => 'left', 'field' => 'yr', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;')
        );
        break;
    }

    $trno = 0;
    if (isset($config['params']['trno'])) $trno = $config['params']['trno'];
    switch ($config['params']['doc']) {
      case 'EA':
        $head = "en_sohead";
        break;
      case 'ED':
        $head = "en_adhead";
        break;
      case 'ER':
        $head = "en_sjhead";
        break;
      case 'EN_STUDENT':
        $head = "en_studentinfo";
        break;
      case 'EN':
        $head = "en_athead";
        break;
    }
    switch ($config['params']['doc']) {
      case 'EM':
        $adviserid = $config['params']['addedparams'][0];
        $periodid = $config['params']['addedparams'][3];
        $syid = $config['params']['addedparams'][4];
        $qry = "select concat(head.trno, s.subjectid) as keyid, head.trno, head.docno, head.docno as scheddocno, head.curriculumcode,
          head.curriculumdocno, c.coursecode, head.courseid, head.sectionid, c.coursename, head.yr, t.term, sec.section, s.subjectid, s.line,
          sub.subjectcode, sub.subjectname, s.roomid, s.bldgid, s.schedday, concat(time(s.schedstarttime),'-',time(s.schedendtime)) as schedtime, room.roomcode, bldg.bldgcode, head.semid
          from en_glhead as head
          left join en_course as c on c.line=head.courseid
          left join en_term as t on t.line=head.semid
          left join en_glsubject as s on s.trno=head.trno
          left join en_subject as sub on sub.trno=s.subjectid
          left join en_section as sec on sec.line = head.sectionid
          left join en_bldg as bldg on bldg.line = s.bldgid
          left join en_rooms as room on room.line = s.roomid
          where head.doc = 'es' and head.syid = ? and head.periodid = ? and s.instructorid = ?";
        $data = $this->coreFunctions->opentable($qry, [$syid, $periodid, $adviserid]);
        break;
      case 'EF':
      case 'EH':
      case 'EK':
      case 'EN':
        $adviserid = $config['params']['addedparams'][0];
        $yr = $config['params']['addedparams'][1];
        $semid = $config['params']['addedparams'][2];
        $periodid = $config['params']['addedparams'][3];
        $syid = $config['params']['addedparams'][4];
        $qry = "select concat(head.trno, s.subjectid) as keyid, head.trno, head.docno, head.docno as scheddocno, head.curriculumcode,
          head.curriculumdocno, c.coursecode, head.courseid, head.sectionid, c.coursename, head.yr, t.term, sec.section, s.subjectid, s.line,
          sub.subjectcode, sub.subjectname, s.roomid, s.bldgid, s.schedday, concat(time(s.schedstarttime),'-',time(s.schedendtime)) as schedtime, room.roomcode, bldg.bldgcode, head.semid, case when head.ischinese=1 then '1' else '0' end ischinese, c.levelid, c.level
          from en_glhead as head
          left join en_course as c on c.line=head.courseid
          left join en_term as t on t.line=head.semid
          left join en_glsubject as s on s.trno=head.trno
          left join en_subject as sub on sub.trno=s.subjectid
          left join en_section as sec on sec.line = head.sectionid
          left join en_bldg as bldg on bldg.line = s.bldgid
          left join en_rooms as room on room.line = s.roomid
          left join en_levels as level on level.line = head.levelid
          where head.doc = 'es' and head.syid = ? and head.periodid = ? and s.instructorid = ?"; //and head.yr = ? and head.semid = ? 

        $data = $this->coreFunctions->opentable($qry, [$syid, $periodid, $adviserid]); // $yr, $semid,
        break;
      case 'EA':
        $coursecode = $this->coreFunctions->getfieldvalue($head, "courseid", "trno=?", [$trno]);
        $period = $this->coreFunctions->getfieldvalue($head, "periodid", "trno=?", [$trno]);
        $yr = $this->coreFunctions->getfieldvalue($head, "yr", "trno=?", [$trno]);
        $semid = $this->coreFunctions->getfieldvalue($head, "semid", "trno=?", [$trno]);
        $sectionid = $this->coreFunctions->getfieldvalue($head, "sectionid", "trno=?", [$trno]);
        $qry = "select head.trno, head.docno, head.curriculumcode, head.curriculumdocno, c.coursecode, c.coursename, head.yr, t.term, sec.section
          from en_glhead as head
          left join en_course as c on c.line=head.courseid
          left join en_term as t on t.line=head.semid left join en_section as sec on sec.line=head.sectionid 
          where head.doc = 'es' and head.courseid = ? and head.periodid = ? and head.yr = ? and head.semid = ? and head.sectionid = ?";
        $data = $this->coreFunctions->opentable($qry, [$coursecode, $period, $yr, $semid, $sectionid]);
        break;
      case 'EN_STUDENT':
        $coursecode = $this->coreFunctions->getfieldvalue($head, "courseid", "clientid=?", [$trno]);
        $period =  $this->coreFunctions->getfieldvalue('en_period', 'line', 'isactive=1');
        $yr = $this->coreFunctions->getfieldvalue($head, "yr", "clientid=?", [$trno]);
        $qry = "select head.trno, head.docno, head.curriculumcode, head.curriculumdocno, c.coursecode, c.coursename, head.yr, t.term, sec.section, head.sectionid
          from en_glhead as head
          left join en_course as c on c.line=head.courseid
          left join en_term as t on t.line=head.semid left join en_section as sec on sec.line=head.sectionid 
          where head.doc = 'es' and head.courseid = ? and head.periodid = ? and head.yr = ? ";
        $data = $this->coreFunctions->opentable($qry, [$coursecode, $period, $yr]);
        break;
      default:
        $coursecode = $this->coreFunctions->getfieldvalue($head, "courseid", "trno=?", [$trno]);
        $period = $this->coreFunctions->getfieldvalue($head, "periodid", "trno=?", [$trno]);
        $yr = $this->coreFunctions->getfieldvalue($head, "yr", "trno=?", [$trno]);
        $semid = $this->coreFunctions->getfieldvalue($head, "semid", "trno=?", [$trno]);
        $sectionid = $this->coreFunctions->getfieldvalue($head, "sectionid", "trno=?", [$trno]);
        $qry = "select head.trno, head.docno, head.curriculumcode, head.curriculumdocno, c.coursecode, c.coursename, head.yr, t.term, sec.section
          from en_glhead as head
          left join en_course as c on c.line=head.courseid
          left join en_term as t on t.line=head.semid left join en_section as sec on sec.line=head.sectionid 
          where head.doc = 'es' and head.courseid = ? and head.periodid = ? and head.yr = ? and head.semid = ? and head.sectionid = ?";
        $data = $this->coreFunctions->opentable($qry, [$coursecode, $period, $yr, $semid, $sectionid]);
        break;
    }
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function lookupregstudent($config)
  {

    $type = 'multi';
    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'schedsummary'
    );

    $lookupsetup = array(
      'type' => $type,
      'rowkey' => 'clientid',
      'title' => 'Enrolled Students',
      'style' => 'width:800px;max-width:800px;'
    );

    // lookup columns

    $cols = array(
      array('name' => 'client', 'label' => 'Student Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Student Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'docno', 'label' => 'Registration #', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;')
    );


    $trno = 0;
    if (isset($config['params']['trno'])) $trno = $config['params']['trno'];

    $qry = "select distinct client.clientid,head.docno,head.dateid,client.client,client.clientname from glhead as head left join client on client.clientid=head.clientid left join glsubject as stock on stock.trno=head.trno
        where head.doc='ER' and stock.screfx=?";

    $data = $this->coreFunctions->opentable($qry, [$trno]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupattendancetype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'keyid',
      'title' => 'Attendance Type',
      // 'style' => 'width:1200px;max-width:1200px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['attendancetype' => 'attendancetype', 'attendancetypeline' => 'attendancetypeline'],
      'action' => ''
    );
    $cols = [
      ['name' => 'attendancetype', 'label' => 'Type', 'align' => 'left', 'field' => 'attendancetype', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'color', 'label' => 'Color', 'align' => 'left', 'field' => 'color', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $data = $this->coreFunctions->opentable("select line as attendancetypeline, type as attendancetype, color from en_attendancetype order by line");
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $config['params']['index']];
  }


  public function lookupquarter($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'keyid',
      'title' => 'Quarter List',
      // 'style' => 'width:1200px;max-width:1200px;'
    );
    switch ($config['params']['doc']) {
      case 'EK':
        $plottype = 'plotgrid';
        break;
      default:
        $plottype = 'plothead';
        break;
    }
    $plotsetup = array(
      'plottype' => $plottype,
      'plotting' => ['quartercode' => 'quartercode', 'quarterid' => 'line', 'quartername' => 'quartername'],
      'action' => ''
    );
    $cols = [
      ['name' => 'quartercode', 'label' => 'Code', 'align' => 'left', 'field' => 'quartercode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'quartername', 'label' => 'NAme', 'align' => 'left', 'field' => 'quartername', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    // $table = $config['params']['table'];
    $data = $this->coreFunctions->opentable("select line, name as quartername, code as quartercode from en_quartersetup order by code");
    switch ($config['params']['doc']) {
      case 'EK':
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index];
        break;
      default:
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
        break;
    }
    

  }


  public function lookupreg($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Schedule Details',
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'regdetail'
    );

    // lookup columns
    $cols = array(
      array('name' => 'subjectcode', 'label' => 'Subject Code', 'align' => 'left', 'field' => 'subjectcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subjectname', 'label' => 'Subject Desc', 'align' => 'left', 'field' => 'subjectname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedday', 'label' => 'Sched Day', 'align' => 'left', 'field' => 'schedday', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedstarttime', 'label' => 'Start Time', 'align' => 'left', 'field' => 'schedstarttime', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedendtime', 'label' => 'End Time', 'align' => 'left', 'field' => 'schedendtime', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'bldgcode', 'label' => 'Bldg', 'align' => 'left', 'field' => 'bldgcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'roomcode', 'label' => 'Room', 'align' => 'left', 'field' => 'roomcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'yr', 'label' => 'Year', 'align' => 'left', 'field' => 'yr', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $trno = $config['params']['trno'];
    $coursecode = $this->coreFunctions->getfieldvalue("en_adhead", "courseid", "trno=?", [$trno]);
    $period = $this->coreFunctions->getfieldvalue("en_adhead", "periodid", "trno=?", [$trno]);
    $yr = $this->coreFunctions->getfieldvalue("en_adhead", "yr", "trno=?", [$trno]);
    $semid = $this->coreFunctions->getfieldvalue("en_adhead", "semid", "trno=?", [$trno]);
    $client = $this->coreFunctions->getfieldvalue("en_adhead", "client", "trno=?", [$trno]);


    $qry = "select  concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,head.docno,c.coursecode,c.coursename,head.yr,t.term, head.section,s.subjectcode,s.subjectname,stock.schedday, stock.schedstarttime,stock.schedendtime,b.bldgcode,r.roomcode
      from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno left join en_course as c on c.line=head.courseid
      left join en_subject as s on s.trno=stock.subjectid left join en_bldg as b on b.line=stock.bldgid left join en_rooms as r on r.line=stock.roomid
      left join en_term as t on t.line=head.semid left join client on client.clientid=head.clientid 
      where head.doc = 'er' and head.periodid =? and head.yr =? and client.client=? and stock.qa=0";

    $data = $this->coreFunctions->opentable($qry, [$period, $yr, $client]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }



  public function lookupassess($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Assessment',
      'style' => 'width:1000px;max-width:1000px;'
    );


    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('assessref' => 'docno', 'coursecode' => 'coursecode', 'coursename' => 'coursename', 'yr' => 'yr', 'terms' => 'term', 'section' => 'section', 'semid' => 'semid', 'modeofpayment' => 'modeofpayment', 'levelid' => 'levelid', 'syid' => 'syid', 'deptid' => 'deptid', 'dlevel' => 'levels', 'sy' => 'sy', 'courseid' => 'courseid', 'deptcode' => 'dept', 'sotrno' => 'trno')
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'course', 'label' => 'Course Code', 'align' => 'left', 'field' => 'course', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'yr', 'label' => 'Year', 'align' => 'left', 'field' => 'yr', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;')
    );


    $client = $config['params']['addedparams'][0];
    $period = $config['params']['addedparams'][1];

    $qry = "select head.trno,head.docno,head.curriculumcode,head.curriculumdocno,head.courseid,head.deptid,head.levelid,head.syid,head.semid,c.coursecode,c.coursename,head.yr,t.term,head.section,head.modeofpayment,l.levels,sy.sy,d.client as dept
from en_glhead as head left join en_course as c on c.line=head.courseid left join en_term as t on t.line=head.semid left join client on client.clientid=head.clientid left join en_levels as l on l.line=head.levelid left join en_schoolyear as sy on sy.line=head.syid left join client as d on d.clientid=head.deptid
where head.doc = 'ea' and head.periodid =? and client.client=?
     ";

    $data = $this->coreFunctions->opentable($qry, [$period, $client]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupassessment($config)
  {

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'trno',
      'title' => 'List of Assessment Summary',
      // 'btns' => ['summary' =>
      // ['label' => 'Show Details', 'lookupclass' => 'lookupsetup', 'action' => 'assessdetail']],
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'assesssummary'
    );



    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'yr', 'label' => 'Year', 'align' => 'left', 'field' => 'yr', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $trno = $config['params']['trno'];
    $coursecode = $this->coreFunctions->getfieldvalue("en_sjhead", "courseid", "trno=?", [$trno]);
    $period = $this->coreFunctions->getfieldvalue("en_sjhead", "periodid", "trno=?", [$trno]);
    $yr = $this->coreFunctions->getfieldvalue("en_sjhead", "yr", "trno=?", [$trno]);
    $semid = $this->coreFunctions->getfieldvalue("en_sjhead", "semid", "trno=?", [$trno]);
    $client = $this->coreFunctions->getfieldvalue("en_sjhead", "client", "trno=?", [$trno]);
    $assessref = $this->coreFunctions->getfieldvalue("en_sjhead", "assessref", "trno=?", [$trno]);


    $qry = "select head.trno,head.docno,head.curriculumcode,head.curriculumdocno,c.coursecode,c.coursename,head.yr,t.term,head.section from en_glhead as head left join en_course as c on c.line=head.courseid left join en_term as t on t.line=head.semid left join client on client.clientid=head.clientid 
       where head.doc = 'ea'  and head.isenrolled=0  and client.client=? and head.docno=?
     ";
    $data = $this->coreFunctions->opentable($qry, [$client, $assessref]);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function assessdetail($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Assessment Details',
      'btns' => ['summary' =>
      ['label' => 'Show Summary', 'lookupclass' => 'lookupsetup', 'action' => 'lookupsched']],
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'scheddetail'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'yr', 'label' => 'Year', 'align' => 'left', 'field' => 'yr', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subjectcode', 'label' => 'Subject Code', 'align' => 'left', 'field' => 'subjectcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subjectname', 'label' => 'Subject Desc', 'align' => 'left', 'field' => 'subjectname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedday', 'label' => 'Sched Day', 'align' => 'left', 'field' => 'schedday', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedstarttime', 'label' => 'Start Time', 'align' => 'left', 'field' => 'schedstarttime', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedendtime', 'label' => 'End Time', 'align' => 'left', 'field' => 'schedendtime', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'bldgcode', 'label' => 'Bldg', 'align' => 'left', 'field' => 'bldgcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'roomcode', 'label' => 'Room', 'align' => 'left', 'field' => 'roomcode', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $trno = $config['params']['trno'];
    $coursecode = $this->coreFunctions->getfieldvalue("en_sjhead", "courseid", "trno=?", [$trno]);
    $period = $this->coreFunctions->getfieldvalue("en_sjhead", "periodid", "trno=?", [$trno]);
    $yr = $this->coreFunctions->getfieldvalue("en_sjhead", "yr", "trno=?", [$trno]);
    $semid = $this->coreFunctions->getfieldvalue("en_sjhead", "semid", "trno=?", [$trno]);
    $assessref = $this->coreFunctions->getfieldvalue("en_sjhead", "assessref", "trno=?", [$trno]);


    $qry = "select  concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,head.docno,c.coursecode,c.coursename,head.yr,t.term, head.section,s.subjectcode,s.subjectname,stock.schedday, stock.schedstarttime,stock.schedendtime,b.bldgcode,r.roomcode
        from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno left join en_course as c on c.line=head.courseid
        left join en_subject as s on s.trno=stock.subjectid left join en_bldg as b on b.line=stock.bldgid left join en_rooms as r on r.line=stock.roomid
        left join en_term as t on t.line=head.semid where head.doc = 'ea' and head.periodid =? and head.yr =? and head.semid =? and head.isenrolled=0";

    $data = $this->coreFunctions->opentable($qry, [$period, $yr, $semid]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function scheddetail($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Schedule Details',
      'btns' => ['summary' =>
      ['label' => 'Show Summary', 'lookupclass' => 'lookupsetup', 'action' => 'lookupsched']],
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'scheddetail'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'coursecode', 'label' => 'Course Code', 'align' => 'left', 'field' => 'coursecode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'coursename', 'label' => 'Course Name', 'align' => 'left', 'field' => 'coursename', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'yr', 'label' => 'Year', 'align' => 'left', 'field' => 'yr', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'term', 'label' => 'Semester', 'align' => 'left', 'field' => 'term', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'section', 'label' => 'Section', 'align' => 'left', 'field' => 'section', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subjectcode', 'label' => 'Subject Code', 'align' => 'left', 'field' => 'subjectcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subjectname', 'label' => 'Subject Desc', 'align' => 'left', 'field' => 'subjectname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedday', 'label' => 'Sched Day', 'align' => 'left', 'field' => 'schedday', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedstarttime', 'label' => 'Start Time', 'align' => 'left', 'field' => 'schedstarttime', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedendtime', 'label' => 'End Time', 'align' => 'left', 'field' => 'schedendtime', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'bldgcode', 'label' => 'Bldg', 'align' => 'left', 'field' => 'bldgcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'roomcode', 'label' => 'Room', 'align' => 'left', 'field' => 'roomcode', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $trno = $config['params']['trno'];
    switch ($config['params']['doc']) {
      case 'EA':
        $head = "en_sohead";
        break;

      case 'ED':
        $head = "en_adhead";
        break;
    }
    $coursecode = $this->coreFunctions->getfieldvalue($head, "courseid", "trno=?", [$trno]);
    $period = $this->coreFunctions->getfieldvalue($head, "periodid", "trno=?", [$trno]);
    $yr = $this->coreFunctions->getfieldvalue($head, "yr", "trno=?", [$trno]);
    $semid = $this->coreFunctions->getfieldvalue($head, "semid", "trno=?", [$trno]);


    $qry = "select  concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,head.docno,c.coursecode,c.coursename,head.yr,t.term, head.section,s.subjectcode,s.subjectname,stock.schedday, stock.schedstarttime,stock.schedendtime,b.bldgcode,r.roomcode
from en_glhead as head left join en_glsubject as stock on stock.trno=head.trno left join en_course as c on c.line=head.courseid
left join en_subject as s on s.trno=stock.subjectid left join en_bldg as b on b.line=stock.bldgid left join en_rooms as r on r.line=stock.roomid
left join en_term as t on t.line=head.semid where head.doc = 'es' and head.periodid =? and head.yr =? and head.semid =?";

    $data = $this->coreFunctions->opentable($qry, [$period, $yr, $semid]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function lookupmodeofpay($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Mode of Payment',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array(
        'modeofpayment' => 'code'
      )
    );

    // lookup columns
    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'modeofpayment', 'label' => 'Mode of Payment', 'align' => 'left', 'field' => 'modeofpayment', 'sortable' => true, 'style' => 'font-size:16px;')
    );


    $qry = "select code,modeofpayment from en_modeofpayment";
    $data = $this->coreFunctions->opentable($qry);
    $btnadd = $this->sqlquery->checksecurity($config, 932, '/tableentries/enrollmententry/en_modeofpayment');
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
  }

  public function generateenbilling($config)
  {
    $msg = $this->sqlquery->generateenbilling($config);
    return ['status' => false, 'msg' => $msg['msg']];
  }

  public function lookupreportcardsetup($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Report Card Subjects',
      'style' => 'width:400px;max-width:400px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array('rctrno' => 'rctrno', 'rcline' => 'rcline', 'rctitle' => 'rctitle')
    );

    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'title', 'label' => 'Title', 'align' => 'left', 'field' => 'rctitle', 'sortable' => true, 'style' => 'font-size:16px;')
    );
    $qry = "select rc.trno as rctrno, rc.line as rcline, rc.code, rc.title as rctitle from en_rcdetail as rc left join en_rchead as head on head.trno=rc.trno where head.courseid=?";
    $index = $config['params']['index'];
    $data = $this->coreFunctions->opentable($qry, [$config['params']['row']['courseid']]);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index, 'rowindex' => $index];
  }
} // class
