<?php

namespace App\Http\Classes\modules\tableentry;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryplantype
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PLAN TYPE MASTERFILE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'plantype';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['code', 'name', 'amount', 'annual', 'cash', 'semi', 'monthly', 'quarterly', 'processfee', 'plangrpid'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 4038,
      'save' => 4038
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'code', 'name', 'amount', 'cash', 'annual', 'semi', 'quarterly', 'monthly', 'processfee']
      ]
    ];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:250px;whiteSpace: normal;min-width:250px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

    $obj[0][$this->gridname]['columns'][1]['error'] = false;
    $obj[0][$this->gridname]['columns'][3]['label'] = 'Contract Amount';
    $this->modulename .= " - [ PLAN GROUP : " . $this->getproject($config) . " ]- ALL AMOUNTS SHOULD BE VAT INCLUSIVE";
    return $obj;
  }

  private function getproject($config)
  {
    if (isset($config['params']['row']['line'])) {
      $projectid = $config['params']['row']['line'];
    } else {
      $projectid = $config['params']['sourcerow']['line'];
    }

    $qry = "select code as value from plangrp where line = ?";
    return $this->coreFunctions->datareader($qry, [$projectid]);
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'print', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['code'] = '';
    $data['name'] = '';
    $data['amount'] = 0;
    $data['cash'] = 0;
    $data['annual'] = 0;
    $data['semi'] = 0;
    $data['quarterly'] = 0;
    $data['monthly'] = 0;
    $data['processfee'] = 0;
    $data['plangrpid'] = $config['params']['sourcerow']['line'];
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line,code, name, format(amount,2) as amount,format(annual,2) as annual,format(cash,2) as cash,format(semi,2) as semi,format(monthly,2) as monthly,format(quarterly,2) as quarterly,
    format(processfee,2) as processfee,plangrpid";
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $config['params']['doc'] = 'ENTRYPLANTYPE';
    $companyid = $config['params']['companyid'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data[$key]['name']);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

  public function save($config)
  {
    $data = [];
    $config['params']['doc'] = 'ENTRYPLANTYPE';
    $row = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['name']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $config['params']['doc'] = 'ENTRYPLANTYPE';
    $data = $this->loaddataperrecord($config, $row['line']);
    $qry = "select trno as value from (select trno from eahead as head where plangrpid =? and planid=? union all select trno from heahead where plangrpid =? and planid =?) as a limit 1";
    $count = $this->coreFunctions->datareader($qry, [$row['plangrpid'], $row['line'], $row['plangrpid'], $row['line']]);

    if ($count != '') {
      return ['clientid' => $row['line'], 'status' => false, 'msg' => 'Already have transaction...'];
    }
    $qry = "delete from " . $this->table . " where plangrpid = ? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['plangrpid'], $row['line']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['name'] . 'Plan groupid' . $row['plangrpid']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($config, $line)
  {
    $grpid = isset($config['params']['row']['plangrpid']) ? $config['params']['row']['plangrpid'] : $config['params']['row']['line'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where plangrpid = ? and line=?";
    $data = $this->coreFunctions->opentable($qry, [$grpid, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $filtersearch = "";
    $searcfield = $this->fields;
    $limit = "1000";
    $search = '';
    $grpid = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];

    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }


    if ($search != "") {
      $l = '';
    } else {
      $l = " limit " . $limit;
    }

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where plangrpid = ? " . $filtersearch . " order by code "  . $l;
    $data = $this->coreFunctions->opentable($qry, [$grpid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['lookupclass'];
    $grpid = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log 
    left join useraccess as u on u.username=log.user
    left join $this->table as t on t.line = log.trno
    where log.doc = '" . $doc . "'  and t.plangrpid =" . $grpid . "
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    left join $this->table as t on t.line = log.trno
    where log.doc = '" . $doc . "' and t.plangrpid =" . $grpid;

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }


  // -> Print Function
  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter($config)
  {
    $fields = ['prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {

    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
    $paramstr =
      "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received";
    $paramstr .= " ,'' as prepared ";

    return $this->coreFunctions->opentable($paramstr);
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select line, code, name, amount,annual,cash,semi,monthly,quarterly,processfee from plantype
      order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    $str = $this->rpt_model_PDF($data, $config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_model_PDF_header_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 20, $this->modulename, '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(700, 20, "Page " . PDF::PageNo() . "  ", '', 'R', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, "Plan code", '', 'L', false, 0);
    PDF::MultiCell(200, 20, "Plan Name", '', 'L', false, 0);
    PDF::MultiCell(200, 20, "Amount", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'T', 'L', false);
  }

  private function rpt_model_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->rpt_model_PDF_header_PDF($data, $filters);

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $code = $this->reporter->fixcolumn([$data[$i]['code']], '50');
      $name = $this->reporter->fixcolumn([$data[$i]['name']], '50');
      $amount = $this->reporter->fixcolumn([$data[$i]['amount']], '50');

      $maxrow = $this->othersClass->getmaxcolumn([$code, $amount, $name]);;

      if ($data[$i]['name'] == '') {
      } else {
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
          PDF::MultiCell(200, 10, isset($code[$r]) ? $code[$r] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(200, 10, isset($name[$r]) ? $name[$r] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(200, 10, isset($amount[$r]) ? $amount[$r] : '', '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);
        }
      }

      if (intVal($i) + 1 == $page) {
        $this->rpt_model_PDF_header_PDF($data, $filters);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn


} //end class
