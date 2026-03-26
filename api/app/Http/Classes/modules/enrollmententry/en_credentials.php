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

class en_credentials
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STUDENT CREDENTIALS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_credentials';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = [
    'credentials', 'amt', 'particulars', 'percentdisc',
    'credentialcode', 'acnoid', 'feesid', 'subjectid', 'scheme'
  ];
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
      'load' => 921
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => [
      'action', 'credentialcode', 'credentials', 'particulars', 'amt', 'percentdisc',
      'acno',  'feescode'
    ]]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][5]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][5]['label'] = "Percent %";
    $obj[0][$this->gridname]['columns'][6]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][6]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][6]['action'] = "lookupsetup";

    $obj[0][$this->gridname]['columns'][7]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][7]['label'] = "Deduct To Fee";

    $obj[0][$this->gridname]['columns'][7]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][7]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][7]['lookupclass'] = "lookupfees";



    return $obj;
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
    $data['credentials'] = '';
    $data['credentialcode'] = '';
    $data['amt'] = '';
    $data['particulars'] = '';
    $data['percentdisc'] = '';
    $data['acnoid'] = 0;
    $data['acno'] = '';
    $data['feesid'] = 0;
    $data['feescode'] = '';
    $data['subjectid'] = 0;
    $data['subjectcode'] = '';
    $data['subjectname'] = '';
    $data['scheme'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = " c.line,c.amt,c.percentdisc,c.feesid,c.acnoid,c.subjectid,c.scheme,sb.subjectcode,sb.subjectname,f.feescode,c.particulars,coa.acno,coa.acnoname,c.credentials,c.credentialcode ";
    return $qry;
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
          $check = $this->checkCredential($data[$key], 'new');
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data2['credentialcode'];
          } else {
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
            $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['credentialcode']);
          }
        } else {
          $check = $this->checkCredential($data[$key]);
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data2['credentialcode'];
          } else {
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.'.$msg, 'data' => $returndata];
  } // end function  

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $check = $this->checkCredential($row, 'new');
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$data['credentialcode'], 'row' => []];
      } else {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($line);
          $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $data['credentialcode']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      $check = $this->checkCredential($row);
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$data['credentialcode'], 'row' => []];
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

  public function checkCredential($data, $type = 'update')
  {
    $qry = "select line as value from en_credentials where credentialcode='".$data['credentialcode']."'";
    if ($type == 'update') {
      $qry = "select line as value from en_credentials where credentialcode='".$data['credentialcode']."' and line<>".$data['line'];
    }
    $check = $this->coreFunctions->datareader($qry);
    if ($check != '') return true;
    return false;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];

    $line = $row['line'];
    $qry = "select v.trno as val, 'Fees' as t  from 
            (select trno from en_socredentials where credentialid=? union all
            select trno from en_glcredentials where credentialid=? ) as v";
    $exist = $this->coreFunctions->opentable($qry, [$line, $line]);
    if (!empty($exist)) {
      return ['line' => $line, 'status' => false, 'msg' => 'Unable to delete, it was already used as ' . $exist[0]->t];
    } else {
      $qry = "delete from " . $this->table . " where line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
      $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['credentialcode']);
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as c left join coa on coa.acnoid=c.acnoid left join en_fees as f on f.line=c.feesid left join en_subject as sb on sb.trno=c.subjectid where c.line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as c left join coa on coa.acnoid=c.acnoid left join en_fees as f on f.line=c.feesid left join en_subject as sb on sb.trno=c.subjectid order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'lookupdepartment':
        return $this->enrollmentlookup->lookupdepartment($config);
        break;
      case 'lookupdean':
        return $this->enrollmentlookup->lookupdean($config);
        break;
      case 'lookuprank':
        return $this->enrollmentlookup->lookuprank($config);
        break;
      case 'lookuplevel':
        return $this->enrollmentlookup->lookuplevel($config);
        break;
      case 'lookupacno':
        return $this->enrollmentlookup->lookupacno($config);
        break;
      case 'lookupfees':
        return $this->enrollmentlookup->lookupfees($config);
        break;
      case 'plotentrygrid_lookupsubject':
        return $this->enrollmentlookup->lookupsubject($config);
        break;
      case 'lookupsubject':
        return $this->enrollmentlookup->lookupcredentialsubject($config);
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
      'title' => 'Student Credential Logs',
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
      select c.line,c.amt,c.percentdisc,c.feesid,c.acnoid,
      c.subjectid,c.scheme,sb.subjectcode,sb.subjectname,
      f.feescode,c.particulars,coa.acno,
      coa.acnoname,c.credentials,c.credentialcode
      from en_credentials as c
      left join coa on coa.acnoid=c.acnoid
      left join en_fees as f on f.line=c.feesid 
      left join en_subject as sb on sb.trno=c.subjectid order by line
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
    PDF::SetMargins(10, 10);

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
    PDF::MultiCell(780, 20, 'STUDENT CREDENTIALS LIST', '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(97.5, 20, "CODE", 'B', 'L', false, 0);
    PDF::MultiCell(97.5, 20, "DESCRIPTION", 'B', 'L', false, 0);
    PDF::MultiCell(97.5, 20, "PARTICULARS", 'B', 'L', false, 0);
    PDF::MultiCell(97.5, 20, "ACCOUNT NAME", 'B', 'L', false, 0);
    PDF::MultiCell(97.5, 20, "SUBJECT NAME", 'B', 'L', false, 0);
    PDF::MultiCell(97.5, 20, "DEDUCT TO FEE", 'B', 'L', false, 0);
    PDF::MultiCell(97.5, 20, "PERCENTAGE", 'B', 'L', false, 0);
    PDF::MultiCell(97.5, 20, "AMOUNT", 'B', 'L', false);
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

    // PDF::MultiCell(0, 0, "\n\n\n\n");
    // PDF::MultiCell(1000, 0, "", 'T', 'L', false);
    PDF::SetFont($font, '', $fontsize);
    foreach ($data as $key => $value) {
      $i++;
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(97.5, 20, $value->credentialcode, '', 'L', false, 0);
      PDF::MultiCell(97.5, 20, $value->credentials, '', 'L', false, 0);
      PDF::MultiCell(97.5, 20, $value->particulars, '', 'L', false, 0);
      PDF::MultiCell(97.5, 20, $value->acnoname, '', 'L', false, 0);
      PDF::MultiCell(97.5, 20, $value->subjectname, '', 'L', false, 0);
      PDF::MultiCell(97.5, 20, $value->feescode, '', 'L', false, 0);
      PDF::MultiCell(97.5, 20, $value->percentdisc, '', 'L', false, 0);
      PDF::MultiCell(97.5, 20, $value->amt, '', 'L', false);

      if (intVal($i) + 1 == $page) {
        $this->PDF_default_header($data, $filters);
        $page += $count;
      }
    }

    for ($i = 0; $i < count($data); $i++) {
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
    $str .= $this->reporter->col('STUDENT CREDENTIALS LIST', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
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
    $str .= $this->reporter->col('PARTICULARS', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('ACCOUNT NAME', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('SUBJECT NAME', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DEDUCT TO FEE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('PERCENTAGE', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
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
      $str .= $this->reporter->col($value->credentialcode, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->credentials, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->particulars, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->acnoname, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->subjectname, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->feescode, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->percentdisc, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->amt, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');

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
