<?php

namespace App\Http\Classes\modules\dashboard;

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

class postingsd
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SALARY DEDUCTIONS';
  public $gridname = 'tableentry';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;
 


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
    $attrib = array('load' =>224,'view'=> 224);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [
      'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrypostingsd', 'label' => 'LIST']
    ];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
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
    $fields = ['checkno', 'checkdate','dacnoname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'checkno.readonly', false);
    data_set($col1, 'checkdate.readonly', false);
    data_set($col1, 'checkdate.type', 'date');
    data_set($col1, 'dacnoname.lookupclass', 'dashboard');
    data_set($col1, 'dacnoname.doc', 'postingsd');
    data_set($col1, 'dacnoname.action', 'lookupdepositto');

    return array('col1' => $col1);

  }

  public function paramsdata($config)
  {
    $data = $this->coreFunctions->opentable("
      select '' as checkno,'' as checkdate,'' as dacnoname,'' as contra,'' as acnoname");


    if (!empty($data)) {
      return $data;
    } else {
      return [];
    }


  }

  public function data()
  {
    return [];
  }

  public function loaddata($config) 
  {
    
    $center = $config['params']['center'];
    $qry = "select '' as siref,'' as prref,
      date(head.dateid) as dateid,
      head.docno,head.trno,
      c.clientname,
      format(ifnull(sum(head.db),0),2) as amount,'tableentries/tableentry/postingsd' as url,ifnull(r.category,'')  as sbu
      from arledger as head left join cntnum as num on num.trno = head.trno
      left join heahead as app on app.trno = num.dptrno
      left join heainfo as info on info.trno = app.trno
      left join client as c on c.clientid=head.clientid
      left join reqcategory as r on r.line = info.sbuid
      where  num.dptrno<> 0 and head.dateid <=now() and head.bal <>0 and num.center='".$center."' and info.isselfemployed =1
      group by head.dateid,head.docno,head.trno,c.clientname,r.category
      order by head.dateid ";


    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
