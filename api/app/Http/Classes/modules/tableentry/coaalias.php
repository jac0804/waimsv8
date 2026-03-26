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
use App\Http\Classes\tableentryClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class coaalias
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'COA Default Alias';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  public $tablelogs = '';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
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
      'load' => 2
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $acno = 0;
    $alias = 1;

    $columns = ['acno', 'alias'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$acno]['label'] = "Account Name";
    $obj[0][$this->gridname]['columns'][$acno]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$alias]['type'] = "label";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['acno'] = '';
    $data['alias'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }


  public function saveallentry($config)
  {
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => []];
  } // end function 

  public function loaddata($config)
  {
    $data = [];
    array_push($data, array('acno' => 'AR Trade', 'alias' => 'AR1'));
    array_push($data, array('acno' => 'AP Trade', 'alias' => 'AP1'));
    array_push($data, array('acno' => 'Cash Accounts', 'alias' => 'CA'));
    array_push($data, array('acno' => 'Cash in Bank', 'alias' => 'CB'));
    array_push($data, array('acno' => 'Check for Deposit', 'alias' => 'CR'));
    array_push($data, array('acno' => 'COGS', 'alias' => 'CG1'));
    array_push($data, array('acno' => 'Gain/Loss', 'alias' => 'GL1'));
    array_push($data, array('acno' => 'Purchase Discount (periodic)', 'alias' => 'PD1'));
    array_push($data, array('acno' => 'Inventory', 'alias' => 'IN1'));
    array_push($data, array('acno' => 'Sales Return & Allowances (periodic)', 'alias' => 'SR1'));
    array_push($data, array('acno' => 'Sales', 'alias' => 'SA1'));
    array_push($data, array('acno' => 'Sales Discount', 'alias' => 'SD1'));
    array_push($data, array('acno' => 'Purchases (asset) (periodic)', 'alias' => 'PS1'));
    array_push($data, array('acno' => 'Purch. Ret. & Allowances (periodic)', 'alias' => 'PR1'));
    array_push($data, array('acno' => 'Input VAT', 'alias' => 'TX1'));
    array_push($data, array('acno' => 'Output VAT', 'alias' => 'TX2'));
    array_push($data, array('acno' => 'Capital', 'alias' => 'IS1'));
    array_push($data, array('acno' => 'Withholding Tax Payable', 'alias' => 'APWT1'));
    array_push($data, array('acno' => 'Creditable Withholding Tax (Consider as Receivable)', 'alias' => 'ARWT1'));
    array_push($data, array('acno' => 'Work in Process (Production)', 'alias' => 'WIP'));
    array_push($data, array('acno' => 'Raw Materials', 'alias' => 'RM'));
    return $data;
  }
} //end class
