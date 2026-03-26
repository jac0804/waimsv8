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
use Exception;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class sss
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SSS TABLE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'ssstab';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['bracket', 'range1', 'range2', 'sssee', 'ssser', 'eccer', 'mpfee', 'mpfer', 'ssstotal'];
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
      'load' => 1449,
      'save' => 1449,
      'view' => 1449
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $columns = ['action', 'bracket', 'range1', 'range2', 'sssee', 'ssser', 'eccer', 'mpfee', 'mpfer', 'ssstotal'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$bracket]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$range1]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";
    $obj[0][$this->gridname]['columns'][$range2]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";
    $obj[0][$this->gridname]['columns'][$sssee]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";
    $obj[0][$this->gridname]['columns'][$ssser]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";
    $obj[0][$this->gridname]['columns'][$eccer]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";
    $obj[0][$this->gridname]['columns'][$mpfee]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";
    $obj[0][$this->gridname]['columns'][$mpfer]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";
    $obj[0][$this->gridname]['columns'][$ssstotal]['style'] = "width:130px;whiteSpace: normal;min-width:130px;";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'print', 'masterfilelogs', 'uploadexcel']; //'defaults'
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['bracket'] = 0;
    $data['range1'] = 0;
    $data['range2'] = 0;
    $data['sssee'] = 0;
    $data['ssser'] = 0;
    $data['eccer'] = 0;
    $data['mpfee'] = 0;
    $data['mpfer'] = 0;
    $data['ssstotal'] = 0;
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
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        $this->logger->sbcmasterlog(
          $line,
          $config,
          'CREATE -' .
            ' BRACKET: ' . $data['bracket'] .
            ' EE: ' . $data['sssee'] .
            ' ER: ' . $data['ssser'] .
            ' EC: ' . $data['eccer'] .
            ' TOTAL: ' . $data['ssstotal']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data2['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        // $this->logger->sbcmasterlog(
        //   $row['line'],
        //   $config,
        //   'UPDATE -' . 
        //   ' BRACKET: ' .$data['bracket'].
        //   ' EE: '.$data['sssee']. 
        //   ' ER: '.$data['ssser']. 
        //   ' EC: '.$data['eccer']. 
        //   ' TOTAL: '.$data['ssstotal']); 
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);

          $this->logger->sbcmasterlog(
            $line,
            $config,
            'CREATE -' .
              ' BRACKET: ' . $data[$key]['bracket'] .
              ' EE: ' . $data[$key]['sssee'] .
              ' ER: ' . $data[$key]['ssser'] .
              ' EC: ' . $data[$key]['eccer'] .
              ' TOTAL: ' . $data[$key]['ssstotal']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);

          // $this->logger->sbcmasterlog(
          // $data[$key]['line'],
          // $config,
          // 'UPDATE -' . 
          // ' BRACKET: ' .$data[$key]['bracket'].
          // ' EE: '.$data[$key]['sssee']. 
          // ' ER: '.$data[$key]['ssser']. 
          // ' EC: '.$data[$key]['eccer']. 
          // ' TOTAL: '.$data[$key]['ssstotal']);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata, 'row' => $returndata];
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log(
      $row['line'],
      $config,
      'REMOVE -' .
        ' BRACKET: ' . $row['bracket'] .
        ' EE: ' . $row['sssee'] .
        ' ER: ' . $row['ssser'] .
        ' EC: ' . $row['eccer'] .
        ' TOTAL: ' . $row['ssstotal']
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
    $qry = "select " . $select . " from " . $this->table . " order by bracket";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

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

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'uploadexcel':
        return $this->uploadsssexcel($config);
        break;
    }
  }


  private function uploadsssexcel($config)
  {
    $status = true;
    $msg = '';

    try {

      foreach ($config['params']['data'] as $key => $value) {
        $this->othersClass->logConsole(json_encode($value));
        $data = [
          'bracket' => $value['Bracket'],
          'range1' => $value['Range1'],
          'range2' => $value['Range2'],
          'ssser' => $value['SSS_ER'],
          'sssee' => $value['SSS_EE'],
          'eccer' => $value['ECC_ER'],
          'ssstotal' => $value['SSS_TOTAL'],
        ];

        $exist = $this->coreFunctions->datareader("select line as value from ssstab where bracket=" . $value['Bracket']);
        if ($exist == '') {
          $this->coreFunctions->sbcinsert('ssstab', $data);
        } else {
          $data['editby'] = $config['params']['user'];
          $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $this->coreFunctions->sbcupdate('ssstab', $data, ['bracket' => $value['Bracket']]);
        }
      }
    } catch (Exception $e) {
      $status = false;
      $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
    }

    $data = $this->loaddata($config);

    return ['status' => $status, 'msg' => $msg, 'data' => $data];
  }

  public function adddefaults($config)
  {
    $qry = "INSERT INTO `ssstab` (`line`,`bracket`,`range1`,`range2`,`sssee`,`ssser`,`eccer`,`ssstotal`) VALUES 
 (1,0,'0.01','2249.99','80.00','160.00','10.00','250.00'),
 (2,1,'2250.00','2749.99','100.00','200.00','10.00','310.00'),
 (3,2,'2750.00','3249.99','120.00','240.00','10.00','370.00'),
 (4,3,'3250.00','3749.99','140.00','280.00','10.00','430.00'),
 (5,4,'3750.00','4249.99','160.00','320.00','10.00','490.00'),
 (6,5,'4250.00','4749.99','180.00','360.00','10.00','550.00'),
 (7,6,'4750.00','5249.99','200.00','400.00','10.00','610.00'),
 (8,7,'5250.00','5749.99','220.00','440.00','10.00','670.00'),
 (9,8,'5750.00','6249.99','240.00','480.00','10.00','730.00'),
 (10,9,'6250.00','6749.99','260.00','520.00','10.00','790.00'),
 (11,10,'6750.00','7249.99','280.00','560.00','10.00','850.00'),
 (12,11,'7250.00','7749.99','300.00','600.00','10.00','910.00'),
 (13,12,'7750.00','8249.99','320.00','640.00','10.00','970.00'),
 (14,13,'8250.00','8749.99','340.00','680.00','10.00','1030.00'),
 (15,14,'8750.00','9249.99','360.00','720.00','10.00','1090.00'),
 (16,15,'9250.00','9749.99','380.00','760.00','10.00','1150.00'),
 (17,16,'9750.00','10249.99','400.00','800.00','10.00','1210.00'),
 (18,17,'10250.00','10749.99','420.00','840.00','10.00','1270.00'),
 (19,18,'10750.00','11249.99','440.00','880.00','10.00','1330.00'),
 (20,19,'11250.00','11749.99','460.00','920.00','10.00','1390.00'),
 (21,20,'11750.00','12249.99','480.00','960.00','10.00','1450.00'),
 (22,21,'12250.00','12749.99','500.00','1000.00','10.00','1510.00'),
 (23,22,'12750.00','13249.99','520.00','1040.00','10.00','1570.00'),
 (24,23,'13250.00','13749.99','540.00','1080.00','10.00','1630.00'),
 (25,24,'13750.00','14249.99','560.00','1120.00','10.00','1690.00'),
 (26,25,'14250.00','14749.99','580.00','1160.00','10.00','1750.00'),
 (27,26,'14750.00','15249.99','600.00','1200.00','30.00','1830.00'),
 (28,27,'15250.00','15749.99','620.00','1240.00','30.00','1890.00'),
 (29,28,'15750.00','16249.99','640.00','1280.00','30.00','1950.00'),
 (30,29,'16250.00','16749.99','660.00','1320.00','30.00','2010.00'),
 (31,30,'16750.00','17249.99','680.00','1360.00','30.00','2070.00'),
 (32,31,'17250.00','17749.99','700.00','1400.00','30.00','2130.00'),
 (33,32,'17750.00','18249.99','720.00','1440.00','30.00','2190.00'),
 (34,33,'18250.00','18749.99','740.00','1480.00','30.00','2250.00'),
 (35,34,'18750.00','19249.99','760.00','1520.00','30.00','2310.00'),
 (36,35,'19250.00','19749.99','780.00','1560.00','30.00','2370.00'),
 (37,36,'19750.00','9999999.00','800.00','1600.00','30.00','2430.00'),
 (38,37,'9999999.01','999999.99','800.00','1600.00','30.00','2430.00');";

    $exist = $this->coreFunctions->datareader("select bracket as value from ssstab limit 1");

    if (!empty($exist)) {
      $data = app('App\Http\Classes\modules\payrollsetup\sss')->loaddata($config);
      return ['status' => false, 'msg' => 'Already have setup', 'data' => $data];
    }

    $exec = $this->coreFunctions->execqry($qry, 'insert');
    if ($exec == 1) {
      $data = app('App\Http\Classes\modules\payrollsetup\sss')->loaddata($config);
      return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
    } else {
      $data = app('App\Http\Classes\modules\payrollsetup\sss')->loaddata($config);
      return ['status' => false, 'msg' => 'Saving Failed', 'data' => $data];
    }
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
    $query = "
      select bracket, range1, range2, sssee, ssser, eccer, ssstotal from ssstab
    ";
    $result = $this->coreFunctions->opentable($query);
    return $result;
  } //end fn

  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_DEFAULT_sss_MASTER_LAYOUT($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_sss_PDF($data, $config);
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
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($filters['params']);
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SSS TABLE LIST', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BRACKET', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('RANGE 1', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('RANGE 2', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('SSS EE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('SSS ER', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('SSS EC', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    return $str;
  }

  private function rpt_DEFAULT_sss_MASTER_LAYOUT($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $layoutsize = '1000';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data, $filters);

    foreach ($data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->bracket, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->range1, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->range2, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->sssee, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->ssser, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->eccer, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($value->ssstotal, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');

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
    $str .= $this->reporter->printline();

    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function rpt_sss_PDF_header_PDF($data, $filters)
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
    PDF::MultiCell(800, 20, "SSS TABLE LIST", '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    // PDF::MultiCell(300, 20, "Code", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Bracket", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "Range 1", '', 'C', false, 0);
    PDF::MultiCell(100, 20, "Range 2", '', 'C', false, 0);
    PDF::MultiCell(100, 20, "SSS EE", '', 'C', false, 0);
    PDF::MultiCell(100, 20, "SSS ER", '', 'C', false, 0);
    PDF::MultiCell(100, 20, "SSS EC", '', 'C', false, 0);
    PDF::MultiCell(100, 20, "Multiplier(%)", '', 'C', false, 0);
    PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'T', 'L', false);
  }

  private function rpt_sss_PDF($data, $filters)
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
    $this->rpt_sss_PDF_header_PDF($data, $filters);
    $i = 0;
    foreach ($data as $key => $value) {
      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      // PDF::MultiCell(300, 10, $data[$i]['stockgrp_code'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $value->bracket, '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $value->range1, '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $value->range2, '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, number_format($value->sssee, $decimal), '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, number_format($value->ssser, $decimal), '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, number_format($value->eccer, $decimal), '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, $value->ssstotal, '', 'C', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

      if (intVal($i) + 1 == $page) {
        $this->rpt_sss_PDF_header_PDF($data, $filters);
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
