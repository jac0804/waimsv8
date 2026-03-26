<?php
namespace App\Http\Classes\modules\payrollentry;

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

class viewempadvances {
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ADVANCES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'standardsetupadv';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['docno','dateid','acnoname','effdate','amt','payment','balance','amortization'];
  public $showclosebtn = false;
 

  public function __construct() {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib(){
    $attrib = array('load'=>1296);
    return $attrib;
}


  public function createTab($config){

    $action = 0;
    $docno = 1;
    $dateid = 2;
    $acnoname = 3;
    $effdate = 4;
    $amt = 5;
    $cr = 6;
    $balance = 7;
    $amortization = 8;

    $tab = [$this->gridname=>['gridcolumns'=>['action','docno','dateid','acnoname','effdate','amt','cr', 'balance','amortization']]];
    
    $stockbuttons = ['view_advance'];

    $obj = $this->tabClass->createtab($tab,$stockbuttons);    

    $obj[0][$this->gridname]['columns'][$action]['label'] = "View Payment";
    $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$acnoname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$effdate]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$amt]['type'] = "label"; 
    $obj[0][$this->gridname]['columns'][$cr]['type'] = "label";    
    $obj[0][$this->gridname]['columns'][$cr]['label'] = "Payment";  
    $obj[0][$this->gridname]['columns'][$balance]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$amortization]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$amt]['style'] = "'width:100px;whiteSpace: normal;min-width:100px;text-align: right;'";
    // $obj[0][$this->gridname]['columns'][$paymentdate]['style'] = "'width:100px;whiteSpace: normal;min-width:100px;text-align: right;'";
    $obj[0][$this->gridname]['columns'][$cr]['style'] = "'width:100px;whiteSpace: normal;min-width:100px;text-align: right;'";
    $obj[0][$this->gridname]['columns'][$balance]['style'] = "'width:100px;whiteSpace: normal;min-width:100px;text-align: right;'";
    $obj[0][$this->gridname]['columns'][$amortization]['style'] = "'width:100px;whiteSpace: normal;min-width:100px;text-align: right;'";

    $obj[0][$this->gridname]['columns'][$acnoname]['label'] = "Loan";
    $obj[0][$this->gridname]['columns'][$amt]['label'] = "Principal";
    return $obj;
  }


  public function createtabbutton($config){
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function loaddata($config){
    $tableid = $config['params']['tableid'];
    $qry = "select empid,trno,docno,dateid,effdate,amt,cr,amortization,acnoname,(amt - ifnull(cr,0)) as balance from (
            select ss.empid,ss.trno,ss.docno,date(ss.dateid) as dateid, date(ss.effdate) as effdate,
                  ss.amt,sum(strans.cr) as cr,
                  ss.amortization, p.codename as acnoname
            from ".$this->table." as ss 
            left join standardtransadv as strans on strans.trno = ss.trno
            left join paccount as p on p.line=ss.acnoid 
            where ss.empid=? 
            group by ss.trno,ss.docno,ss.dateid,ss.effdate,ss.amt,ss.amortization,p.codename,ss.empid) as a
            order by docno";
    $data = $this->coreFunctions->opentable($qry,[$tableid]);
    return $data;
  }
































} //end class
