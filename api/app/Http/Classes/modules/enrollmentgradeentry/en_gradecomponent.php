<?php

namespace App\Http\Classes\modules\enrollmentgradeentry;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class en_gradecomponent
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'GRADE COMPONENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_gradecomponent';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['gccode', 'gcname', 'gcpercent', 'isconduct'];
  public $showclosebtn = false;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 935
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'gccode', 'gcname', 'gcpercent', 'isconduct']
      ]
    ];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog', 'print'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['gccode'] = '';
    $data['gcname'] = '';
    $data['gcpercent'] = 0;
    $data['isconduct'] = 'false';
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

  public function checkGradecomp($data, $type = 'update')
  {
    $qry = "select line as value from en_gradecomponent where gccode='".$data['gccode']."'";
    if ($type == 'update') {
      $qry = "select line as value from en_gradecomponent where gccode='".$data['gccode']."' and line<>".$data['line'];
    }
    $check = $this->coreFunctions->datareader($qry);
    if ($check == '') return false;
    return true;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $check = $this->checkGradecomp($row, 'new');
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$row['gccode'], 'row' => []];
      } else {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($line != 0) {
          $returnrow = $this->loaddataperrecord($line);
          $this->logger->sbcmasterlog($row['line'], $config, ' CREATE - ' . $data['gccode']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    } else {
      $check = $this->checkGradecomp($row);
      if ($check) {
        return ['status' => false, 'msg' => 'Duplicate entry for '.$row['gccode'], 'row' => []];
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
          $check = $this->checkGradecomp($data[$key], 'new');
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data[$key]['gccode'];
          } else {
            $line = $this->coreFunctions->insertGetId($this->table, $data2);
            $this->logger->sbcmasterlog($data[$key]['line'], $config, ' CREATE - ' . $data[$key]['gccode']);
          }
        } else {
          $check = $this->checkGradecomp($data[$key]);
          if ($check) {
            $msg .= "\n Duplicate entry for ".$data[$key]['gccode'];
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
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Grade Component Logs',
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

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['gccode']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    if (!empty($data)) {
      if ($data[0]->isconduct == 0) {
        $data[0]->isconduct = 'false';
      } else {
        $data[0]->isconduct = 'true';
      }
    }
    return $data;
  }

  public function loaddata($config)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " order by line";
    $data = $this->coreFunctions->opentable($qry);
    if (!empty($data)) {
      foreach ($data as $d) {
        if ($d->isconduct == 0) {
          $d->isconduct = 'false';
        } else {
          $d->isconduct = 'true';
        }
      }
    }
    return $data;
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
      "select 'PDFM' as print, '' as prepared, '' as approved, '' as received");
  }

  public function reportdata($config)
  {
    $data = $this->loaddata($config);
    $str = $this->PDF_DEFAULT_STATUS_MASTER_LAYOUT($data, $config);
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

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(780, 20, 'GRADE COMPONENT LIST', '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(780, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(200, 20, "CODE", 'B', 'L', false, 0);
    PDF::MultiCell(360, 20, "DESCRIPTION", 'B', 'L', false, 0);
    PDF::MultiCell(200, 20, 'PERCENTAGE', 'B', 'L', false);
  }

  private function PDF_DEFAULT_STATUS_MASTER_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',  $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 65;
    $page = 65;
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
      PDF::MultiCell(200, 0, $value->gccode, '', 'L', false, 0);
      PDF::MultiCell(360, 0, $value->gcname, '', 'L', false, 0);
      PDF::MultiCell(200, 0, $value->gcpercent, '', 'L', false);

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
} //end class
