<?php

namespace App\Http\Classes\modules\pos;

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

use Carbon\Carbon;


class pospaymentsetup
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'POS PAYMENT SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'profile';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['pvalue'];
  public $showclosebtn = false;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib =
      array(
        'load' => 0,
        'view' => 2574,
      );
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $psection = 1;
    $codename = 2;
    $itemname = 3;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'psection', 'codename', 'itemname']]];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:20%;whiteSpace: normal;min-width:20%;";
    $obj[0][$this->gridname]['columns'][$psection]['label'] = "ACCOUNT NAME";
    $obj[0][$this->gridname]['columns'][$codename]['label'] = "ACCOUNT";
    $obj[0][$this->gridname]['columns'][$codename]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][$codename]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][$codename]['lookupclass'] = "account";

    $obj[0][$this->gridname]['columns'][$psection]['style'] = "width:40%;whiteSpace: normal;min-width:40%";
    $obj[0][$this->gridname]['columns'][$codename]['style'] = "width:40%;whiteSpace: normal;min-width:40%";
    return $obj;
  }

  // NO FUNCTION FOR ADD BUTTON
  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  private function selectqry()
  {
    $qry = " 
        p.line, p.doc, p.master as psection,
        p.pvalue
    ";
    return $qry;
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
        $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
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
    if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
      $returnrow = $this->loaddataperrecord($row['line']);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Saving failed.'];
    }
  } //end function

  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor, concat(c.acno,'~',c.acnoname) as codename, '' as itemname ";
    $qry = "select " . $select . " from " . $this->table . " as p
    left join coa as c on p.pvalue = c.acnoid
    where p.psection = 'ACCT' and line= ? order by p.master";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $accounts = $this->accounts();
    foreach ($accounts as $key => $value) {
      $chkqry = "select psection as value from profile where doc = ? and psection = ? "; // checking
      $pval = $this->coreFunctions->datareader($chkqry, [$key, 'ACCT']);
      if (empty($pval)) {
        $profiledata = ['doc' => $value['doc'], 'psection' => 'ACCT', 'pvalue' => '', 'master' => $value['title']];
        $this->coreFunctions->sbcinsert('profile', $profiledata);
      }
    }

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor, concat(c.acno,'~',c.acnoname) as codename ";
    $qry = "select " . $select . " from " . $this->table . " as p
    left join coa as c on p.pvalue = c.acnoid
    where p.psection = 'ACCT' order by p.master ";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }


  private function accounts()
  {
    $accounts = [
      'CSH' => ['doc' => 'CSH', 'title' => 'CASH SALES'],
      'CHK' => ['doc' => 'CHK', 'title' => 'CHEQUE SALES'],
      'CRD' => ['doc' => 'CRD', 'title' => 'CARD SALES'],
      'DEBIT' => ['doc' => 'DEBIT', 'title' => 'DEBIT SALES'],
      'AR' => ['doc' => 'AR', 'title' => 'CREDIT (AR) SALES'],
      'VC' => ['doc' => 'VC', 'title' => 'VOUCHER SALES'],
      'LP' => ['doc' => 'LP', 'title' => 'LOYALTY POINTS'],
      'EPLUS' => ['doc' => 'EPLUS', 'title' => 'E-PLUS'],
      'SMAC' => ['doc' => 'SMAC', 'title' => 'SMAC'],
      'ONLINE' => ['doc' => 'ONLINE', 'title' => 'ONLINE DEALS'],
      'GC' => ['doc' => 'GC', 'title' => 'GIFT CARDS'],
      'SA' => ['doc' => 'SA', 'title' => 'SALES'],
      'SR' => ['doc' => 'SR', 'title' => 'RETURN'],
      'VAT' => ['doc' => 'VAT', 'title' => 'OUTPUT VAT'],
      'VATEX' => ['doc' => 'VATEX', 'title' => 'VAT EXEMPT SALES'],
      'DISC' => ['doc' => 'DISC', 'title' => 'REGULAR DISCOUNT'],
      'SC' => ['doc' => 'SC', 'title' => 'SENIOR DISCOUNT'],
      'PWD' => ['doc' => 'PWD', 'title' => 'PWD DISCOUNT'],
      'EMP' => ['doc' => 'EMP', 'title' => 'EMPLOYEE DISCOUNT'],
      'VIPDISC' => ['doc' => 'VIPDISC', 'title' => 'VIP DISCOUNT'],
      'ODISC' => ['doc' => 'ODISC', 'title' => 'ONLINE DISCOUNT'],
      'SMACDISC' => ['doc' => 'SMACDISC', 'title' => 'SMAC DISCOUNT'],
      'SN' => ['doc' => 'SN', 'title' => 'SERVICE CHARGE'],
    ];
    return $accounts;
  }


  public function lookupsetup($config)
  {
    return $this->lookupaccount($config);
  }

  public function lookupaccount($config)
  {
    //default
    $plotting = array('pvalue' => 'acnoid', 'codename' => 'codename');
    $plottype = 'plotgrid';
    $title = 'List of Accounts';
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

    $qry = "select acno, acnoid, acnoname, concat(acno,'~',acnoname) as codename  from coa order by acno";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function



} //end class
