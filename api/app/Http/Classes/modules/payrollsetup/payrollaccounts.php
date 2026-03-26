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
use App\Http\Classes\tableentryClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class payrollaccounts
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PAYROLL ACCOUNTS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $tableentryClass;
  private $table = 'paccount';
  public $tablelogs = 'masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['code', 'codename', 'alias', 'type', 'uom', 'seq', 'qty', 'acnoid', 'istax', 'alias2', 'aaid', 'penalty', 'is13th', 'ispayroll'];
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
    $this->tableentryClass = new tableentryClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 1490
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $columns = ['action', 'code',  'codename', 'alias', 'type', 'uom', 'seq', 'qty', 'penalty', 'istax', 'ispayroll', 'acno', 'acnoname', 'alias2', 'accountno'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$code]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$codename]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$alias]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$type]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$uom]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$seq]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$penalty]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$istax]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$ispayroll]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$acno]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$acnoname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$alias2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$accountno]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

    $obj[0][$this->gridname]['columns'][$uom]['lookupclass'] = "lookupuompay";
    $obj[0][$this->gridname]['columns'][$uom]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$acno]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$acno]['label'] = "COA Code";
    $obj[0][$this->gridname]['columns'][$acno]['lookupclass'] = "lookupcoa";
    $obj[0][$this->gridname]['columns'][$acno]['action'] = "lookupsetup";

    $obj[0][$this->gridname]['columns'][$acnoname]['label'] = "COA Name";
    $obj[0][$this->gridname]['columns'][$acnoname]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$acnoname]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$accountno]['label'] = "Account Name";
    $obj[0][$this->gridname]['columns'][$accountno]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$accountno]['lookupclass'] = "lookupaaccount";
    $obj[0][$this->gridname]['columns'][$accountno]['action'] = "lookupsetup";

    $obj[0][$this->gridname]['columns'][$acnoname]['type'] = 'coldel';
    $obj[0][$this->gridname]['columns'][$acno]['type'] = 'coldel';
    if ($companyid != 43) {
      $obj[0][$this->gridname]['columns'][$alias2]['type'] = 'coldel';
    }

    if ($companyid != 58) { //cdo
      $obj[0][$this->gridname]['columns'][$ispayroll]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'][$penalty]['readonly'] = false;

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
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
    //'code', 'codename', 'alias', 'type', 'uom', 'seq', 'qty', 'acno', 'acnoname'
    $data['line'] = 0;
    $data['code'] = '';
    $data['codename'] = '';
    $data['alias'] = '';
    $data['alias2'] = '';
    $data['type'] = '';
    $data['uom'] = '';
    $data['seq'] = 0;
    $data['qty'] = 0;
    $data['acnoid'] = 0;
    $data['acno'] = '';
    $data['acnoname'] = '';
    $data['istax'] = 'false';
    $data['ispayroll'] = 'false';
    $data['aaid'] = 0;
    $data['penalty'] = 0;
    $data['is13th'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function adddefaults($config)
  {

    if ($this->companysetup->istimekeeping($config['params'])) {
      $qry = "INSERT INTO `paccount` (`line`,`code`,`codename`,`alias`,`type`,`uom`,`seq`,`qty`,`istax`,`pseq`) 
              VALUES
              (37,'PT58','SERVICE INCENTIVE LEAVE','SIL','','PESO',33,'1.0000',1,'23'),
              (46,'PT8','SICK LEAVE','SL','','DAYS',42,'1.0000',1,'22'),
              (47,'PT9','VACATION LEAVE','VL','MDS','DAYS',11,'1.0000',1,'24'),
              (54,'PT85','EMERGENCY LEAVE','VIL','','PESO',33,'1.0000',1,'26'),
              (55,'PT86','BIRTHDAY LEAVE','BL','','DAYS',33,'1.0000',0,'25'),
              (67,'PT103','LEAVE BALANCE','LB','','PESO',8,'1.0000',0,'34')";
    } else {
      $qry = "INSERT INTO `paccount` (`line`,`code`,`codename`,`alias`,`type`,`uom`,`seq`,`qty`,`istax`,`pseq`) VALUES
              (1,'PT1','BASIC RATE','RATE','','PESO',1,'0.0000',0,'1'),
              (2,'PT10','CASH LOAN','LOAN','','PESO',32,'-1.0000',0,'56'),
              (3,'PT11','PAGIBIG LOAN','LOAN','','PESO',27,'-1.0000',0,'55'),
              (4,'PT12','CALAMITY LOAN','LOAN','','PESO',28,'-1.0000',0,'64'),
              (5,'PT13','SSS LOAN','LOAN','','PESO',26,'-1.0000',0,'54'),
              (6,'PT14','CANTEEN','CA','','PESO',25,'-1.0000',0,'62'),
              (7,'PT15','REGULAR OT','OTREG','MDS','HRS',6,'1.2500',0,'5'),
              (8,'PT16','RESTDAY-SUN','RESTDAY','MDS','HRS',8,'1.3000',0,'8'),
              (9,'PT17','SUNDAY OT ','OTRES','MDS','HRS',8,'1.6900',0,'21'),
              (10,'PT18','LEGAL HOLIDAY ','LEG','MDS','HRS',10,'2.0000',0,'7'),
              (11,'PT2','MONTHLY DUE','DUE','','PESO',2,'0.0000',0,'70'),
              (12,'PT29','ADJUSTMENT','ADJUSTMENT','','PESO',38,'1.0000',0,'31'),
              (13,'PT3','WORKING HRS','WORKING','MDS','HRS',3,'0.0000',0,'0'),
              (14,'PT30','OTHER EARNINGS','EARNINGS','MDS','PESO',1,'1.0000',0,'29'),
              (15,'PT31','ALLOWANCE','ALLOWANCE','','PESO',31,'1.0000',0,'14'),
              (16,'PT32','BACK PAY','BACKPAY','','PESO',39,'1.0000',0,'32'),
              (17,'PT33','OVERPAYMENT','OVERPAYMENT','','PESO',40,'-1.0000',0,'69'),
              (18,'PT34','13TH MONTH PAY','13PAY','MDS','PESO',41,'1.0000',0,'33'),
              (19,'PT35','OTHER DEDUCTION','DEDUCTION','MDS','PESO',1,'-1.0000',0,'67'),
              (20,'PT36','PO CARD','DEDUCTION','MDS','PESO',36,'-1.0000',0,'60'),
              (21,'PT37','HOSPITAL LOAN','DEDUCTION','MDS','PESO',1,'-1.0000',0,'57'),
              (22,'PT4','ALLOWANCE2','COLA1','','PESO',12,'1.0000',0,'12'),
              (23,'PT42','WITHHOLDING TAX PAYABLE','YWT','MDS','PESO',13,'0.0000',0,'52'),
              (24,'PT44','SSS-EMPLOYEE','YSE','','PESO',15,'0.0000',0,'50'),
              (25,'PT45','SSS-EMPLOYER','YSR','','PESO',16,'0.0000',0,'0'),
              (26,'PT46','EC-EMPLOYER','YER','','PESO',17,'0.0000',0,'0'),
              (27,'PT48','PHILHEALTH-EMPLOYEE','YME','','PESO',18,'0.0000',0,'51'),
              (28,'PT49','PHILHEALTH-EMPLOYER','YMR','','PESO',19,'0.0000',0,'0'),
              (29,'PT5','ABSENT','ABSENT','MDS','HRS',5,'-1.0000',1,'2'),
              (30,'PT51','PAG-IBIG EMPLOYEE','YPE','','PESO',20,'0.0000',0,'53'),
              (31,'PT52','PAG-IBIG EMPLOYER','YPR','','PESO',21,'0.0000',0,'0'),
              (32,'PT53','SSS EMPLOYER SHARE','YIS','','PESO',22,'0.0000',0,'0'),
              (33,'PT54','PHILHEALTH EMPLOYER SHARE','YIM','','PESO',23,'0.0000',0,'0'),
              (34,'PT55','PAG-IBIG EMPLOYER SHARE','YIP','','PESO',24,'0.0000',0,'0'),
              (35,'PT56','PAYROLL PAYABLE','PPBLE','','PESO',44,'0.0000',0,'0'),
              (36,'PT57','BASIC SALARIES','BSA','','PESO',45,'1.0000',1,'1'),
              (37,'PT58','SERVICE INCENTIVE LEAVE','SIL','','PESO',33,'1.0000',1,'23'),
              (38,'PT6','LATE/TARDINESS','LATE','MDS','HRS',4,'-1.0000',1,'3'),
              (39,'PT64','SPECIAL HOLIDAY ','SP','MDS','HRS',9,'1.3000',0,'6'),
              (40,'PT67','MEAL ALLOWANCE','ALLOWANCE3','','PESO',35,'1.0000',0,'13'),
              (41,'PT69','CASH ADVANCE','DEDUCTION','MDS','PESO',34,'-1.0000',0,'61'),
              (42,'PT7','UNDERTIME','UNDERTIME','MDS','HRS',4,'-1.0000',1,'4'),
              (43,'PT70','STOCKS VALE','DEDUCTION','','PESO',37,'-1.0000',0,'68'),
              (44,'PT71','HMO/PENSION PLAN','DEDUCTION','','PESO',29,'-1.0000',0,'65'),
              (45,'PT76','NIGHT DIFF OT','NDIFF','MDS','HRS',7,'0.1000',1,'11'),
              (46,'PT8','SICK LEAVE','SL','','DAYS',42,'1.0000',1,'22'),
              (47,'PT9','VACATION LEAVE','VL','MDS','DAYS',11,'1.0000',1,'24'),
              (48,'PT79','COLA','COLA','','PESO',11,'1.0000',0,'15'),
              (49,'PT80','LEGAL OT','LEGALOT','MDS','HRS',10,'1.6900',0,'17'),
              (50,'PT81','SPECIAL OT','SPECIALOT','MDS','HRS',10,'1.6900',0,'16'),
              (51,'PT82','SPECIAL HOLIDAY UNWORK','SPUN','','DAYS',46,'1.0000',0,'18'),
              (52,'PT83','LEGAL HOLIDAY UNWORK','LEGUN','','DAYS',47,'1.0000',1,'19'),
              (53,'PT28','BONUS','BON','','PESO',48,'1.0000',0,'30'),
              (54,'PT85','EMERGENCY LEAVE','VIL','','PESO',33,'1.0000',1,'26'),
              (55,'PT86','BIRTHDAY LEAVE','BL','','DAYS',33,'1.0000',0,'25'),
              (56,'PT87','RESTDAY-SAT','RESTDAYSAT','MDS','HRS',8,'1.2500',0,'9'),
              (57,'PT88','SATURDAY OT','OTSAT','MDS','HRS',8,'1.3000',0,'20'),
              (58,'PT09','OTHER LOAN','DEDUCTION','','PESO',12,'-1.0000',0,'59'),
              (59,'PT89','OTHER DEDUCTION2','DEDUCTION','MDS','PESO',1,'-1.0000',0,'66'),
              (60,'PT90','OTHER INCOME TAXABLE','EARNINGS','','PESO',30,'1.0000',1,'28'),
              (61,'PT91','OTHER INCOME N-TAXABLE','EARNINGS1','','PESO',30,'1.0000',0,'27'),
              (62,'PT92','INSURANCE LOAN','DEDUCTION','','PESO',32,'-1.0000',0,'58'),
              (63,'PT93','PAG CALAMITY LOAN','DEDUCTION','','PESO',32,'-1.0000',1,'63'),
              (64,'PT100','YEARS OF SERVICE','YOS','','PESO',8,'1.0000',0,'37'),
              (65,'PT101','MONTHS OF SERVICE','MOS','','PESO',8,'1.0000',0,'36'),
              (66,'PT102','DAYS OF SERVICE','DOS','','PESO',8,'1.0000',0,'35'),
              (67,'PT103','LEAVE BALANCE','LB','','PESO',8,'1.0000',0,'34'),
              (68,'PT200','PIECE SALARY','PIECE','','PESO',1,'1.0000',1,'1'),
              (69,'PT104','NDIFF HRS','NDIFFS','MDS','HRS',7,'0.1000',1,'10'),
              (70,'PT105','SPECIAL HOL (100%)','SP100','MDS','HRS',9,'1.0000',0,'9');";
    }


    $exist = $this->coreFunctions->datareader("select code as value from paccount limit 1");

    if (!empty($exist)) {
      $data = $this->loaddata($config);
      return ['status' => false, 'msg' => 'Already have accounts setup', 'data' => $data];
    }

    $exec = $this->coreFunctions->execqry($qry, 'insert');
    if ($exec == 1) {
      $data = $this->loaddata($config);
      return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
    } else {
      $data = $this->loaddata($config);
      return ['status' => false, 'msg' => 'Saving Failed', 'data' => $data];
    }
  }

  private function selectqry()
  {
    $qry = "p.line, coa.acno, coa.acnoname,acc.codename as accountno";
    foreach ($this->fields as $key => $value) {
      switch ($value) {
        case 'istax':
        case 'ispayroll':
          $qry = $qry . ",(case when p." . $value . "=1 then 'true' else 'false' end) as " . $value;
          break;
        default:
          $qry = $qry . ',p.' . $value;
          break;
      }
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
            ' CODE: ' . $data['code'] .
            ' CODENAME: ' . $data['codename'] .
            ' ALIAS: ' . $data['alias']
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
        // ' CODE: ' .$data['code'].
        // ' CODENAME: ' .$data['codename'].
        // ' ALIAS: '.$data['alias']); 
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
              ' CODE: ' . $data[$key]['code'] .
              ' CODENAME: ' . $data[$key]['codename'] .
              ' ALIAS: ' . $data[$key]['alias']
          );
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          // $this->logger->sbcmasterlog(
          // $data[$key]['line'],
          // $config,
          // 'UPDATE -' . 
          // ' CODE: ' .$data[$key]['code'].
          // ' CODENAME: ' .$data[$key]['codename'].
          // ' ALIAS: '.$data[$key]['alias']); 
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function 

  public function delete($config)
  {
    $row = $config['params']['row'];

    $qry1 = "
      select acno as value from paytrancurrent where acno=?
      union all
      select acno as value from paytranhistory where acno=?
    ";
    $count = $this->coreFunctions->datareader($qry1, [$row['acno'], $row['acno']]);

    if ($count != '') {
      return ['clientid' => $row['acno'], 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as p 
    left join coa on coa.acnoid=p.acnoid 
    left join aaccount as acc on acc.line = p.aaid
    where p.line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $filter = '';
    if ($this->companysetup->istimekeeping($config['params'])) {
      $filter = " and p.codename like '%LEAVE%'";
    }
    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as p 
    left join coa on coa.acnoid=p.acnoid
    left join aaccount as acc on acc.line = p.aaid
    where 1=1 $filter order by p.line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];

    switch ($lookupclass) {
      case 'lookupuompay':
        return $this->lookupuompay($config);
        break;
      case 'lookupcoa':
        return $this->lookupcoa($config);
        break;
      case 'lookupaaccount':
        return $this->lookupaccountingaccount($config);
        break;
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
    }
  }

  public function lookupuompay($config)
  {
    $plotting = array('uom' => 'uom');
    $plottype = 'plotgrid';
    $title = 'List of Unit';

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
      ['name' => 'uom', 'label' => 'UOM', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select 'MINS' as uom union all select 'HRS' as uom union all select 'DAYS' as uom union all select 'PESO' as uom  ";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookupcoa($config)
  {
    $plotting = array('acnoid' => 'acnoid', 'acno' => 'acno', 'acnoname' => 'acnoname');
    $plottype = 'plotgrid';
    $title = 'Chart of Account';

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
      ['name' => 'acno', 'label' => 'ACNO', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'acnoname', 'label' => 'ACNONAME', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select acnoid,acno,acnoname from coa  ";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function
  public function lookupaccountingaccount($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Accounting Accounts',
      'style' => 'width:100%;max-width:80%;'
    );
    $plotting = array('aaid' => 'aaid', 'accountno' => 'accountno');
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'codename', 'label' => 'Code Name', 'align' => 'left', 'field' => 'codename', 'sortable' => true, 'style' => 'font-size:16px;')
    );
    $qry = "select 0 as aaid,'' as code, '' as accountno,'' as codename 
    union all  
   select line as aaid,code, codename as accountno,codename from aaccount";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }
  public function lookuplogs($config)
  {
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'show',
      'action' => '',
      'callbackfieldhead' => [],
      'callbackfieldlookup' => [],
      'plotting' => []
    );

    $trno = $config['params']['tableid'];
    $doc = $config['params']['doc'];

    $cols = [
      ['name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;']

    ];

    $qry = "
      select trno, doc, task, dateid, user
      from " . $this->tablelogs . "
      where doc = ?
      order by dateid desc
    ";

    $data = $this->coreFunctions->opentable($qry, [$doc]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'refresh'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'default' as print,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];

    $query = "select pa.line, pa.code, pa.codename, pa.alias, pa.type, pa.uom, pa.seq, pa.qty, acc.code as acno, acc.codename as acnoname
              from paccount as pa
              left join aaccount as acc on acc.line = pa.aaid
              order by pa.line";
    //left join coa on pa.acnoid = coa.acnoid

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    $str = $this->rpt_payrollaccount_masterfile_layout($data, $config);
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
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAYROLL ACCOUNTS', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Code', '75', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Codename', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Alias', '75', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Type', '75', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('UOM', '75', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Seq', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Multiplier', '75', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Account Code', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->col('Account Name', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_payrollaccount_masterfile_layout($data, $filters)
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
    $count = 36;
    $page = 38;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['code'], '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['codename'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['alias'], '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['type'], '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['uom'], '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['seq'], '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['qty'], '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['acno'], '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($data[$i]['acnoname'], '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
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
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  } //end fn































} //end class
