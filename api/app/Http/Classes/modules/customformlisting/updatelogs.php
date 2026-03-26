<?php

namespace App\Http\Classes\modules\customformlisting;

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



class updatelogs
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = "UPDATE LOGS";
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = true;
  public $showclosebtn = false;
  public $reporter;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 22,
      'edit' => 23,
      'new' => 24,
      'save' => 25,
      'change' => 26,
      'delete' => 27,
      'print' => 28
    );
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {

    $tab = [];

    $stockbuttons = [];

    $obj = [];
  
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['rem'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'rem.label', 'Updates');
    data_set($col1, 'rem.type', 'textarea');
    data_set($col1, 'rem.style', 'height: 800px; width: 1000px');
   
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid){
        case 55://afli
            $data['rem'] =$this->afliupdates();
            break;
    }

    if (!empty($data)) {
      return $data;
    } else {
      return [];
    }
  }

  private function afliupdates(){
    return '
    09/11/2025
    - Application of Advance Payment : Apply to current & succeeding month, reversal of income applicable only up to current month, succeeding months will be manually reversed thru JV entry
    - Application of Balloon Payment : Apply to current month then remaining amount will be applied to the last MA onwards , reversal of income applicable only up to current month, succeeding months will be manually reversed thru JV entry
    09/15/2025
    - Loan Agreement printout : Display Monthly Amortization instead of Principal amount on payment schedule 
    - Borrower Ledger printout : remove contract price,discount, outstanding bal,penalty, processing fee on header
    09/16/2025
    - Takeout Fees Entry : Sum up total takeout fees on Other Inc. -Other receivable
    - Application of takeout fees entry base on arrangement from excel, only other income account will be automatically reversed to revenue upon collection
    - Deposit for Processing Fees will be manually reversed once used for corresponding expense account as per Mam Judith.
    09/18/2025
    - Additional takeout fees
        ** Annotation of Special Power of Attorney
        ** Articles of Inc. & By Laws
        ** Annotation expenses
        ** Transfer of ownership
        ** RPT
        ** Documentary Stamp
        ** Handling Fee
        ** Appraisal Fee
        ** Processing Fee/ Filling Fee
        ** Cancellation : Sec 4 Rule 74
        ** Cancellation : Sec 4 Rule 74
        ** Annotation of correct tech description
        ** Annotation of Aff of one and the same person
        ** Cancellation: ULAMA
    - All additional takeout fees is added to Unearned Other Income - Others
    09/24/2025
    - Additional DST and MRI on Salary/Vehicle/Working Capital Loan';
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    return [];
  }

  
  public function setupreport($config)
  {
    return [];
  }

  public function createreportfilter($config)
  {
       return [];
  }

  public function reportparamsdata($config)
  {
   return [];
  }

  public function reportdata($config)
  {
    return [];
  }

  public function loaddata($config)
  {
    return [];
  }


 
} //end class
