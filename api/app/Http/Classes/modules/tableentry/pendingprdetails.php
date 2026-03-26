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
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class pendingprdetails
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PR DETAILS';
  public $gridname = 'inventory';
  public $stock = 'hprstock';
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  private $othersClass;
  public $style = 'width:1100px;max-width:1100px;';
  private $fields = [];
  public $showclosebtn = true;
  public $issearchshow = false;
  public $logger;
  public $sqlquery;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2052
    );
    return $attrib;
  }


  public function createTab($config)
  {
   $columns =['action', 'barcode','itemname', 'rrqty', 'qa', 'pending'];

    foreach ($columns as $key => $value) {
        $$value = $key;
    }

    $tab = [
      $this->gridname => ['gridcolumns' => $columns] 
    ];

    $stockbuttons = ['addpritem'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$qa]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$qa]['label'] = 'Served';
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$rrqty]['type'] = 'label';
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = []; //saveallentry
    $obj = $this->tabClass->createtabbutton($tbuttons);
   
    return $obj;
  }

    public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $rqtrno = $this->coreFunctions->getfieldvalue("lahead","rqtrno","trno=?",[$trno]);
    $wh = $this->coreFunctions->getfieldvalue("lahead","wh","trno=?",[$trno]);

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
    FORMAT(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,stock.disc,
    FORMAT(stock.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
    FORMAT(((stock.qa+stock.siqa) / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    FORMAT(((stock.qty-(stock.qa+stock.siqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,
    stock.loc,head.yourref,s.stage,stock.rem,item.itemid,head.projectid,head.subproject,'".$wh."' as source,stock.uom,stock.stageid,".$trno." as mtrno
    from hprhead as head
    right join hprstock as stock on stock.trno = head.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join transnum on transnum.trno = head.trno
    left join client as wh on wh.clientid=stock.whid
    left join stagesmasterfile as s on s.line = stock.stageid
    left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
    where head.trno = ? and stock.qa<>stock.qty";

    $data = $this->coreFunctions->opentable($qry, [$rqtrno]);
    return $data;
  }

  public function sbcscript($config){
    return [
      'functtableentry2close' => '
        console.log("xxx", this.gettrno)
        '
    ];

  }

 
} //end class
