<?php

namespace App\Http\Classes\modules\payrollsetup;

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

class tax
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'WITHHOLDING TAX TABLE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'taxtab';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['paymode', 'teu', 'depnum', 'tax01', 'tax02', 'tax03', 'tax04', 'tax05', 'tax06'];
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
      'load' => 1520
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $paymode = 1;
    $teu = 2;
    $depnum = 3;
    $tax01 = 4;
    $tax02 = 5;
    $tax03 = 6;
    $tax04 = 7;
    $tax05 = 8;
    $tax06 = 9;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'paymode', 'teu', 'depnum', 'tax01', 'tax02', 'tax03', 'tax04', 'tax05', 'tax06']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$paymode]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$teu]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$depnum]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$tax01]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$tax02]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$tax03]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$tax04]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$tax05]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$tax06]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'defaults', 'print', 'masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['paymode'] = '';
    $data['teu'] = '';
    $data['depnum'] = 0;
    $data['tax01'] = 0;
    $data['tax02'] = 0;
    $data['tax03'] = 0;
    $data['tax04'] = 0;
    $data['tax05'] = 0;
    $data['tax06'] = 0;
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

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if (strlen($row['paymode']) > 1 || strlen($row['teu']) > 1) {
      return ['status' => false, 'msg' => 'Saving failed.'];
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog(
          $line,
          $config,
          'CREATE -' .
            ' PAYMODE: ' . $data['paymode'] .
            ' TAX1: ' . $data['tax01']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        // $this->logger->sbcmasterlog(
        // $row['line'],
        // $config,
        // 'UPDATE -' . 
        // ' PAYMODE: ' .$data['paymode'].
        // ' TAX1: '.$data['tax01']); 
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log(
      $row['line'],
      $config,
      'REMOVE -' .
        ' PAYMODE: ' . $row['paymode'] .
        ' TAX1: ' . $row['tax01']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
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
    $qry = "select " . $select . " from " . $this->table . "";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function adddefaults($config)
  {
    $qry = "INSERT INTO `taxtab` (`line`,`paymode`,`teu`,`depnum`,`tax01`,`tax02`,`tax03`,`tax04`,`tax05`,`tax06`,`tax07`,`tax08`,`tax09`,`tax10`,`tax11`,`tax12`,`tax13`,`tax14`,`tax15`) VALUES 
 (1,'W','A',0,'0.00','0.00','576.92','2500.00','9423.08','46346.15','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (2,'W','W',0,'1.00','4808.00','7692.00','15385.00','38462.00','153846.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (3,'W','P',0,'0.00','0.20','0.25','0.30','0.32','0.35','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (4,'S','A',0,'0.00','0.00','1250.00','5416.67','20416.67','100416.67','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (5,'S','S',0,'1.00','10417.00','16667.00','33333.00','83333.00','333333.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (6,'S','P',0,'0.00','0.20','0.25','0.30','0.32','0.35','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (7,'M','A',0,'0.00','0.00','2500.00','10833.33','40833.33','200833.33','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (8,'M','M',0,'1.00','20833.00','33333.00','66667.00','166667.00','666667.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (9,'M','P',0,'0.00','0.20','0.25','0.30','0.32','0.35','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (10,'D','D',0,'1.00','685.00','1096.00','2192.00','5479.00','6602.74','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (11,'D','P',0,'0.00','0.20','0.25','0.30','0.32','0.35','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00'),
 (12,'D','A',0,'0.00','0.00','82.19','356.16','1342.47','21918.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00');";

    $exist = $this->coreFunctions->datareader("select paymode as value from taxtab limit 1");

    if (!empty($exist)) {
      $data = app('App\Http\Classes\modules\payrollsetup\tax')->loaddata($config);
      return ['status' => false, 'msg' => 'Already have setup', 'data' => $data];
    }

    $exec = $this->coreFunctions->execqry($qry, 'insert');
    if ($exec == 1) {
      $data = app('App\Http\Classes\modules\payrollsetup\tax')->loaddata($config);
      return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
    } else {
      $data = app('App\Http\Classes\modules\payrollsetup\tax')->loaddata($config);
      return ['status' => false, 'msg' => 'Saving Failed', 'data' => $data];
    }
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        if (strlen($data[$key]['paymode']) > 1 || strlen($data[$key]['teu']) > 1) {
          return ['status' => false, 'msg' => 'Saving failed.', 'data' => $data];
        }

        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $this->logger->sbcmasterlog(
            $line,
            $config,
            'CREATE -' .
              ' PAYMODE: ' . $data[$key]['paymode'] .
              ' TAX1: ' . $data[$key]['tax01']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          // $this->logger->sbcmasterlog(
          // $data[$key]['line'],
          // $config,
          // 'UPDATE -' . 
          // ' PAYMODE: ' .$data[$key]['paymode'].
          // ' TAX1: '.$data[$key]['tax01']); 
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function 

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Logs',
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

  // -> print function
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
    $fields = ['prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
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
    $query = "select line, paymode, teu, depnum, tax01, tax02, tax03, tax04, tax05, tax06 from taxtab
      order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_withholdingtax_masterfile_layout($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_tax_PDF($data, $config);
    }
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_default_header($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('WITHHOLDING TAX TABLE', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Paymode', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Teu', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Dependents', '100', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Tax 1', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Tax 2', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Tax 3', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Tax 4', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Tax 5', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Tax 6', '100', null, false, '1px solid ', 'B', 'R', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_withholdingtax_masterfile_layout($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['paymode'], '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['teu'], '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['depnum'], '100', null, false, '1px solid ', '', 'C', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['tax01'], '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['tax02'], '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['tax03'], '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['tax04'], '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['tax05'], '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['tax06'], '100', null, false, '1px solid ', '', 'R', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  } //end fn  

  private function rpt_tax_PDF_header_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

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
    PDF::MultiCell(800, 20, "WITHHOLDING TAX TABLE LIST", '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    // PDF::MultiCell(300, 20, "Code", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Paymode", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "TEU", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Dependents", '', 'L', false, 0);
    PDF::MultiCell(60, 20, "Tax 1", '', 'C', false, 0);
    PDF::MultiCell(60, 20, "Tax 2", '', 'C', false, 0);
    PDF::MultiCell(60, 20, "Tax 3", '', 'C', false, 0);
    PDF::MultiCell(60, 20, "Tax 4", '', 'C', false, 0);
    PDF::MultiCell(60, 20, "Tax 5", '', 'C', false, 0);
    PDF::MultiCell(60, 20, "Tax 6", '', 'C', false, 0);
    PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'T', 'L', false);
  }

  private function rpt_tax_PDF($data, $filters)
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
    $this->rpt_tax_PDF_header_PDF($data, $filters);
    $i = 0;
    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      // PDF::MultiCell(300, 10, $data[$i]['stockgrp_code'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $data[$i]['paymode'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $data[$i]['teu'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $data[$i]['depnum'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(60, 10, $data[$i]['tax01'], '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(60, 10, $data[$i]['tax02'], '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(60, 10, $data[$i]['tax03'], '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(60, 10, $data[$i]['tax04'], '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(60, 10, $data[$i]['tax05'], '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(60, 10, $data[$i]['tax06'], '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

      if (intVal($i) + 1 == $page) {
        $this->rpt_tax_PDF_header_PDF($data, $filters);
        $page += $count;
      }
      $i++;
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
