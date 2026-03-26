<?php

namespace App\Http\Classes\modules\enrollmententry;

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
use App\Http\Classes\lookup\enrollmentlookup;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class en_fees
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'FEES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_fees';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['feescode', 'feesdesc', 'feestype', 'acnoid', 'vat', 'amount'];
  public $showclosebtn = false;
  private $enrollmentlookup;
  private $reporter;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->enrollmentlookup = new enrollmentlookup;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 934
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'feescode', 'feesdesc', 'feestype', 'acno', 'acnoname', 'vat', 'amount']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:85px;whiteSpace: normal;min-width:85px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][5]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][6]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][7]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][5]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][5]['label'] = 'Acccount Name';

    // $obj[0][$this->gridname]['columns'][3]['type'] = "lookup";
    // $obj[0][$this->gridname]['columns'][3]['action'] = "lookupsetup";
    // $obj[0][$this->gridname]['columns'][3]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][4]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][4]['action'] = "lookupsetup";

    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'defaults', 'saveallentry', 'print', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function adddefaults($config)
  {
    $exist = $this->coreFunctions->opentable("select line from en_fees where feescode in ('TF', 'REG', 'MISC')");
    if (!empty($exist)) {
      $data = $this->loaddata($config);
      return ['status' => false, 'msg' => 'Already have setup.', 'data' => $data];
    }
    $qry = "insert into en_fees(feescode, feesdesc, feestype) values('TF', 'Tuition Fee', 'TF'), ('REG', 'Registration Fee', 'REG'), ('MISC', 'Miscellaneous', 'MISC')";
    if ($this->coreFunctions->execqry($qry, 'insert') > 0) {
      $data = $this->loaddata($config);
      return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
    } else {
      $data = $this->loaddata($config);
      return ['status' => false, 'msg' => 'Saving failed', 'data' => $data];
    }
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['feescode'] = '';
    $data['feesdesc'] = '';
    $data['feestype'] = '';
    $data['acno'] = '';
    $data['acnoid'] = 0;
    $data['acnoname'] = '';
    $data['vat'] = '';
    $data['amount'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function checkFees($data, $type = 'update')
  {
    $qry = "select line as value from en_fees where feescode='".$data['feescode']."'";
    if ($type == 'update') {
      $qry = "select line as value from en_fees where feescode='".$data['feescode']."' and line<>".$data['line'];
    }
    $check = $this->coreFunctions->datareader($qry);
    if ($check == '') return false;
    return true;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $msg = '';
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $check = $this->checkFees($data[$key], 'new');
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data[$key]['feescode'];
          } else {
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
            $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['feescode']);
          }
        } else {
          $check = $this->checkFees($data[$key]);
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data[$key]['feescode'];
          } else {
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully. '.$msg, 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $check = $this->checkFees($row, 'new');
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$row['feescode'], 'row' => []];
      } else {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($line);
          $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $data['feescode']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      $check = $this->checkFees($row);
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$row['feescode'], 'row' => []];
      } else {
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($row['line']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    }
  } //end function


  public function delete($config)
  {
    $row = $config['params']['row'];

    $line = $row['line'];
    $qry = "select v.trno as val, 'Fees' as t  from 
            (select trno from en_atfees where feesid=? union all
            select trno from en_glfees where feesid=? union all
            select trno from en_sosummary where feesid=? union all 
            select trno from en_glsummary where feesid=? ) as v";
    $exist = $this->coreFunctions->opentable($qry, [$line, $line, $line, $line]);
    if (!empty($exist)) {
      return ['line' => $line, 'status' => false, 'msg' => 'Unable to delete, it was already used as ' . $exist[0]->t];
    } else {
      $qry = "delete from " . $this->table . " where line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
      $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['feescode']);
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select f.line, f.feescode, f.feesdesc, f.feestype, 
      coa.acno, coa.acnoname, f.vat, f.amount, f.acnoid, '' as bgcolor
      from " . $this->table . "  as f
      left join coa as coa on coa.acnoid = f.acnoid
      where f.line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select f.line, f.feescode, f.feesdesc, f.feestype, 
      coa.acno, coa.acnoname, f.vat, f.amount, f.acnoid, '' as bgcolor
      from " . $this->table . "  as f 
      left join coa as coa on coa.acnoid = f.acnoid
      order by f.line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupdepartment':
      case 'lookupparentdepartment':
        return $this->enrollmentlookup->lookupdepartment($config);
        break;
      case 'lookupdean':
        return $this->enrollmentlookup->lookupdean($config);
        break;
      case 'lookupcourse':
        return $this->enrollmentlookup->lookupcourse($config);
        break;
      case 'lookupacno':
        return $this->enrollmentlookup->lookupacno($config);
        break;
      case 'lookuptypeoffees':
        return $this->enrollmentlookup->lookuptypeoffees($config);
        break;
      case 'lookuplevel':
      case 'lookupdeptlevel':
        return $this->enrollmentlookup->lookuplevel($config);
        break;
      case 'whlog':
        return $this->lookuplogs($config);
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Fees Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      // array('name' => 'doc', 'label' => 'Doc', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }


  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter();
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "
      select f.line, f.feescode, f.feesdesc, f.feestype, 
      coa.acno, coa.acnoname, f.vat, f.amount, f.acnoid, '' as bgcolor
      from en_fees  as f 
      left join coa as coa on coa.acnoid = f.acnoid
      order by f.line
    ";
    $result = $this->coreFunctions->opentable($query);
    return $result;
  } //end fn

  // public function reportdata($config){
  //   $data = $this->report_default_query($config);
  //   $str = $this->rpt_DEFAULT_STATUS_MASTER_LAYOUT($data,$config);
  //   return ['status'=>true,'msg'=>'Generating report successfully.','report'=>$str];
  // }

  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_DEFAULT_STATUS_MASTER_LAYOUT($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->PDF_DEFAULT_STATUS_MASTER_LAYOUT($data, $config);
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function PDF_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];
    $companyid = $filters['params']['companyid'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    if ($companyid == 3) {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    } else {
      PDF::SetFont($font, '', 9);
      PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
    }

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 20, 'FEES LIST', '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 20, "ORDER", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "TYPE", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "ACCOUNT NAME", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "VAT", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "AMOUNT", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(160, 0, "", 'T', 'L', false);
  }

  private function PDF_DEFAULT_STATUS_MASTER_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);

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
    $this->PDF_default_header($data, $filters);
    $i = 0;
    foreach ($data as $key => $value) {
      $i++;

      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(100, 10, $value->feescode, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $value->feesdesc, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $value->feestype, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $value->acnoname, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $value->vat, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $value->amount, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

      if (intVal($i) + 1 == $page) {
        $this->PDF_default_header($data, $filters);
        $page += $count;
      }
    }
    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(760, 0, "", 'T', 'L', false);


    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  private function rpt_default_header($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);
    $layoutsize = '800';
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FEES LIST', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    // $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TYPE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('VAT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  private function rpt_DEFAULT_STATUS_MASTER_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);
    $layoutsize = '800';
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data, $filters);

    foreach ($data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->feescode, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->feesdesc, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->feestype, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->acnoname, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->vat, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->amount, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->rpt_default_header($data, $filters);

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn
































} //end class
