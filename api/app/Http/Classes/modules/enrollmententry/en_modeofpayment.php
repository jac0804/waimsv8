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

class en_modeofpayment
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'MODE OF PAYMENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_modeofpayment';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = [
    'code', 'deductpercent', 'modeofpayment', 'months',
    'perc1', 'date1',
    'perc2', 'date2',
    'perc3', 'date3',
    'perc4', 'date4',
    'perc5', 'date5',
    'perc6', 'date6',
    'perc7', 'date7',
    'perc8', 'date8',
    'perc9', 'date9',
    'perc10', 'date10'
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
      'load' => 932
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => [
      'action',
      'code', 'modeofpayment', 'deductpercent', 'months',
      'perc1', 'date1',
      'perc2', 'date2',
      'perc3', 'date3',
      'perc4', 'date4',
      'perc5', 'date5',
      'perc6', 'date6',
      'perc7', 'date7',
      'perc8', 'date8',
      'perc9', 'date9',
      'perc10', 'date10'
    ]]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:85px;whiteSpace: normal;min-width:85px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][3]['label'] = "Interest %";
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][5]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][6]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][7]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][8]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][9]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][10]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][11]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][12]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][13]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][14]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][15]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][16]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][17]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][18]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][19]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][20]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][21]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][22]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][23]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][24]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

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
    $data['code'] = '';
    $data['deductpercent'] = '';
    $data['modeofpayment'] = '';
    $data['months'] = '';
    $data['perc1'] = '';
    $data['date1'] = '';
    $data['perc2'] = '';
    $data['date2'] = '';
    $data['perc3'] = '';
    $data['date3'] = '';
    $data['perc4'] = '';
    $data['date4'] = '';
    $data['perc5'] = '';
    $data['date5'] = '';
    $data['perc6'] = '';
    $data['date6'] = '';
    $data['perc7'] = '';
    $data['date7'] = '';
    $data['perc8'] = '';
    $data['date8'] = '';
    $data['perc9'] = '';
    $data['date9'] = '';
    $data['perc10'] = '';
    $data['date10'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $qry = "line";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    $qry = $qry;
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
          $check = $this->checkMOP($data[$key], 'new');
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data2['code'];
          } else {
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
            $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['code']);
          }
        } else {
          $check = $this->checkMOP($data[$key]);
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data2['code'];
          } else {
            $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data2['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          }
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function  

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $check = $this->checkMOP($row, 'new');
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$data['code'], 'row' => []];
      } else {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($line);
          $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $data['code']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      $check = $this->checkMOP($row);
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$data['code'], 'row' => []];
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

  public function checkMOP($data, $type = 'update')
  {
    $qry = "select line as value from en_modeofpayment where code='".$data['code']."'";
    if ($type == 'update') {
      $qry = "select line as value from en_modeofpayment where code='".$data['code']."' and line<>".$data['line'];
    }
    $check = $this->coreFunctions->datareader($qry);
    if ($check != '') return true;
    return false;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];

    $line = $row['line'];
    $code = $row['code'];
    $qry = "select v.trno as val, 'Mode of Payment' as t  from 
            (select trno from en_sohead where modeofpayment=? union all
            select trno from en_sjhead where modeofpayment=? union all
            select trno from en_adhead where modeofpayment=? union all
            select trno from en_glhead where modeofpayment=? ) as v";
    $exist = $this->coreFunctions->opentable($qry, [$code, $code, $code, $code]);
    if (!empty($exist)) {
      return ['line' => $line, 'status' => false, 'msg' => 'Unable to delete, it was already used as ' . $exist[0]->t];
    } else {
      $qry = "delete from " . $this->table . " where line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupschoolyear':
        return $this->enrollmentlookup->lookupschoolyear($config);
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
      'title' => 'Mode of Payment Logs',
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
      select 
      code, deductpercent, modeofpayment, months,
      perc1, date(date1) as date1,
      perc2, date(date2) as date2,
      perc3, date(date3) as date3,
      perc4, date(date4) as date4,
      perc5, date(date5) as date5,
      perc6, date(date6) as date6,
      perc7, date(date7) as date7,
      perc8, date(date8) as date8,
      perc9, date(date9) as date9,
      perc10, date(date10) as date10
      from en_modeofpayment
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
      $str = $this->rpt_DEFAULT_modeofpay_LAYOUT($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->PDF_DEFAULT_modeofpay_LAYOUT($data, $config);
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
    PDF::AddPage('p', [1000, 1000]);
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
    PDF::MultiCell(800, 20, 'MODE OF PAYMENT LIST', '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(41, 20, "CODE", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "INTEREST", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "# OF MONTHS", '', 'L', false, 0);

    PDF::MultiCell(41, 20, "1ST %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 1", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "2ND %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 2", '', 'L', false, 0);

    PDF::MultiCell(41, 20, "3RD %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 3", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "4TH %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 4", '', 'L', false, 0);

    PDF::MultiCell(41, 20, "5TH %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 5", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "6TH %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 6", '', 'L', false, 0);

    PDF::MultiCell(41, 20, "7TH %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 7", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "8TH %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 8", '', 'L', false, 0);

    PDF::MultiCell(41, 20, "9TH %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 9", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "10TH %", '', 'L', false, 0);
    PDF::MultiCell(41, 20, "DATE 10", '', 'L', false);





    // PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(1000, 0, "", '', 'L', false, 0);
    PDF::MultiCell(1000, 0, "", '', 'L', false, 0);
    PDF::MultiCell(1000, 0, "", '', 'L', false, 0);
    PDF::MultiCell(1000, 0, "", 'T', 'L', false);
  }

  private function PDF_DEFAULT_modeofpay_LAYOUT($data, $filters)
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
    $fontsize = "7";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->PDF_default_header($data, $filters);
    $i = 0;

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::MultiCell(1000, 0, "", 'T', 'L', false);
    foreach ($data as $key => $value) {
      $i++;

      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(41, 20, $value->code, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->modeofpayment, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->deductpercent, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->months, '', 'L', false, 0);

      PDF::MultiCell(41, 20, $value->perc1, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date1, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->perc2, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date2, '', 'L', false, 0);

      PDF::MultiCell(41, 20, $value->perc3, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date3, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->perc4, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date4, '', 'L', false, 0);

      PDF::MultiCell(41, 20, $value->perc5, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date5, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->perc6, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date6, '', 'L', false, 0);

      PDF::MultiCell(41, 20, $value->perc7, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date7, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->perc8, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date8, '', 'L', false, 0);

      PDF::MultiCell(41, 20, $value->perc9, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date9, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->perc10, '', 'L', false, 0);
      PDF::MultiCell(41, 20, $value->date10, '', 'L', false);


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
    $layoutsize = '1000';
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MODE OF PAYMENT LIST', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
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
    $str .= $this->reporter->col('INTEREST', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('# OF MONTHS', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('1ST %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 1', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('2ND %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 2', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('3RD %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 3', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('4TH %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 4', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('5TH %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 5', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('6TH %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 6', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('7TH %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 7', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('8TH %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 8', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('9TH %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 9', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('10TH %', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DATE 10', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '30px', '8px');
    return $str;
  }

  private function rpt_DEFAULT_modeofpay_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);
    $layoutsize = '1000';
    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data, $filters);

    foreach ($data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->code, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->modeofpayment, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->deductpercent, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->months, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc1, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date1, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc2, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date2, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc3, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date3, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc4, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date4, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc5, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date5, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc6, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date6, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc7, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date7, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc8, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date8, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc9, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date9, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->perc10, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');
      $str .= $this->reporter->col($value->date10, '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '2px');

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
