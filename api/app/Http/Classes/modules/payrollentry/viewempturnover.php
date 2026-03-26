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

class viewempturnover {
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TURNOVER/RETURN ITEMS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  private $tabledetail = '';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
  public $showclosebtn = false;
 

  public function __construct() {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib(){
    $attrib = array('load'=>1300
                    );
    return $attrib;
}


  public function createTab($config) {
    $tr = 0;
    $docno = 1;
    $status = 2;
    $dateid = 3;
    $itemname = 4;
    $amt = 5;
    $rem = 6;

    $tab = [$this->gridname=>['gridcolumns'=>['tr','docno','status','dateid','itemname','amt','rem']]];
    
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab,$stockbuttons);    
    $obj[0][$this->gridname]['columns'][$tr]['style']="width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$tr]['type']='label';

    $obj[0][$this->gridname]['columns'][$dateid]['type']='label';
    $obj[0][$this->gridname]['columns'][$dateid]['style']="width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$docno]['style']="width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$docno]['align']="text-left";
    $obj[0][$this->gridname]['columns'][$docno]['type']='label';

    $obj[0][$this->gridname]['columns'][$status]['label'] = "Type";
    $obj[0][$this->gridname]['columns'][$status]['type']='label';
    $obj[0][$this->gridname]['columns'][$status]['align']="text-left";
    $obj[0][$this->gridname]['columns'][$status]['style']= "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Description";
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

    $obj[0][$this->gridname]['columns'][$amt]['label'] = "Estimated Value";
    $obj[0][$this->gridname]['columns'][$amt]['type']='label';
    $obj[0][$this->gridname]['columns'][$amt]['style']="width:100px;whiteSpace: normal;min-width:100px; text-align: right;";

    $obj[0][$this->gridname]['columns'][$rem]['label'] = "Remarks";
    $obj[0][$this->gridname]['columns'][$rem]['type']= 'label';
    $obj[0][$this->gridname]['columns'][$rem]['style']="width:100px;whiteSpace: normal;min-width:100px;";

    return $obj;
  }


  public function createtabbutton($config){
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function loaddata($config){
    $tableid = $config['params']['tableid'];
    $qry = "select 'UNPOSTED' as tr,'TURN OVER' as `status`,th.docno,date(th.dateid) as dateid,td.itemname,
    td.amt,td.rem 
    from turnoveritemhead as th
    left join turnoveritemdetail as td on td.trno = th.trno where th.empid=?
    union all
    select 'POSTED' as tr,'TURN OVER' as `status`,th.docno,date(th.dateid) as dateid,td.itemname,td.amt,td.rem 
    from hturnoveritemhead as th
    left join hturnoveritemdetail as td on td.trno = th.trno where th.empid=?
    union all
    select 'UNPOSTED' as tr,'RETURN ITEM' as `status`,th.docno,date(th.dateid) as dateid,td.itemname,td.amt,
    td.rem 
    from returnitemhead as th
    left join returnitemdetail as td on td.trno = th.trno where th.empid=?
    union all
    select 'POSTED' as tr,'RETURN ITEM' as `status`,th.docno,date(th.dateid) as dateid,td.itemname,td.amt,
    td.rem 
    from hreturnitemhead as th
    left join hreturnitemdetail as td on td.trno = th.trno where th.empid=?";
    $data = $this->coreFunctions->opentable($qry,[$tableid,$tableid,$tableid,$tableid]);
    return $data;
  }
































} //end class
