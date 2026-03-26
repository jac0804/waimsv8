<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Illuminate\Support\Facades\Storage;

class viewobapplication
{
  private $fieldClass;
  private $tabClass;
  private $logger;
  public $modulename = 'OB APPLICATION DETAILED - ';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $linkemail;
  public $tablelogs = 'payroll_log';
  public $style = 'width:90%;max-width:90%;';
  public $issearchshow = true;
  public $showclosebtn = true;
  public $fields = ['status', 'status2', 'approverem', 'disapproved_remarks2', 'approvedby', 'approvedate', 'disapprovedby', 'disapprovedate', 'approvedby2', 'approvedate2', 'disapprovedby2', 'disapprovedate2'];


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->linkemail = new linkemail;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $obj = [];


    if ($companyid == 51) { //ulitc
      $gridcolumns = ['purpose', 'destination', 'leadfrom', 'leadto', 'contact'];

      foreach ($gridcolumns as $key => $value) {
        $$value = $key;
      }
      $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
      $stockbuttons = [];
      $obj = $this->tabClass->createtab($tab, $stockbuttons);

      $obj[0][$this->gridname]['columns'][$purpose]['label'] = 'Purpose of Travel';
      $obj[0][$this->gridname]['columns'][$purpose]['type'] = 'label';

      $obj[0][$this->gridname]['columns'][$leadfrom]['style'] = 'text-align:right;';
      $obj[0][$this->gridname]['columns'][$leadfrom]['label'] = 'Time From';
      $obj[0][$this->gridname]['columns'][$leadfrom]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$leadto]['style'] = 'text-align:right;';
      $obj[0][$this->gridname]['columns'][$leadto]['label'] = 'Time To';
      $obj[0][$this->gridname]['columns'][$leadto]['type'] = 'label';

      $obj[0][$this->gridname]['columns'][$leadto]['style'] =  'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;text-align:right;';
      $obj[0][$this->gridname]['columns'][$leadfrom]['style'] =  'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;text-align:right;';

      $obj[0][$this->gridname]['columns'][$destination]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;text-align:left;';
      $obj[0][$this->gridname]['columns'][$purpose]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;text-align:left;';
    }
    if ($companyid == 53) { // camera
      $this->modulename = 'OB APPLICATION IMAGE - ';
    }
    $this->modulename .= '' . $config['params']['row']['clientname'];
    return $obj;
  }

  public function createtabbutton($config)
  {
    $obj = [];
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = [];
    if ($companyid == 53) {
      array_push($fields, 'picture');
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'picture.style', 'height:300px;width:90%; max-width: 100%;');
    data_set($col1, 'picture.type', 'imageview');
    $fields = [];
    $col2 = $this->fieldClass->create($fields);
    $fields = [];
    $col3 = $this->fieldClass->create($fields);
    $fields = [];
    $col4 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $result = $this->coreFunctions->opentable("select ob.line,  client.client, client.clientname, date(ob.dateid) as dateid, date(ob.dateid) as datetime,
    ob.type,ob.approverem as remarks,ob.rem,ob.disapproved_remarks2 as remark,date(ob.createdate) as createdate,
    date(ob.scheddate) as scheddate,dayname(ob.scheddate) as dayname,emp.email,date_format(ob.dateid, '%Y-%m-%d %h:%i %p') as schedin,ob.location,
    ob.ontrip,if(ob.picture = '','/images/employee/default_emp_portal.png',ob.picture) as picture
    from obapplication as ob 
    left join client on client.clientid=ob.empid
    left join employee as emp on emp.empid = client.clientid
    where approvedate is null and disapprovedate is null and ob.line=?", [$config['params']['row']['line']]);

    foreach ($result as $key => $value) {
      if ($value->picture != '') {
        Storage::disk('public')->url($value->picture);
      }
    }
    return $result;
  }

  public function data($config)
  {
    $line = $config['params']['row']['line'];
    $qry = "
        select detail.trno,detail.line,detail.purpose,detail.destination,
        date_format(detail.leadfrom,'%H:%i') as leadfrom,date_format(detail.leadto,'%H:%i') as leadto,contact
        from obdetail as detail 
        where detail.trno = ? order by line desc";

    return  $this->coreFunctions->opentable($qry, [$line]);
  }

  public function loaddata($config)
  {
    return [];
  }
} //end class
