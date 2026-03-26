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
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class entrypaymentadvance {
  private $fieldClass;
  private $tabClass;
  public $modulename = 'View Payments';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'standardsetupadv';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
  public $showclosebtn = false;
  private $reporter;
 

  public function __construct() {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;  
  }

  public function getAttrib(){
    $attrib = array('load'=>0
                    );
    return $attrib;
}

  public function createTab($config){
    $paymentdate=0;
    $cr = 1;
   
    $tab = [$this->gridname=>['gridcolumns'=>['paymentdate','cr']]];
    
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab,$stockbuttons);    

    $obj[0][$this->gridname]['columns'][$paymentdate]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$cr]['type'] = "label";    
    $obj[0][$this->gridname]['columns'][$cr]['label'] = "Payment";  

    $obj[0][$this->gridname]['columns'][$cr]['style'] = "'whiteSpace: normal;'";
    $obj[0][$this->gridname]['columns'][$cr]['align'] = "text-left";
    return $obj;
  }

  public function createtabbutton($config){
    return [];
  }
 
  public function loaddata($config){
    $empid = $config['params']['row']['empid'];
    $trno = $config['params']['row']['trno'];
    $qry = "select ss.empid,date(strans.dateid) as paymentdate,strans.cr
            from ".$this->table." as ss
            left join standardtransadv as strans on strans.trno = ss.trno
            left join paccount as p on p.line=ss.acnoid
            where ss.empid=? and ss.trno=? and strans.cr > 0
            order by strans.dateid";
            
    $data = $this->coreFunctions->opentable($qry,[$empid,$trno]);
    return $data;
  }

} //end class
