<?php

namespace App\Http\Classes\modules\customform;

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

class viewnoticetoexplain
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'NOTICE TO EXPLAIN';
  public $gridname = 'viewrefgrid';
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

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['dateid', 'docno', 'irref', 'idate', 'violation']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][1]['label'] = 'NTE. Ref';
    $obj[0][$this->gridname]['columns'][2]['label'] = 'IR. Ref';


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
    $fields = ['start'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.readonly', false);

    $fields = ['end'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'end.readonly', false);

    $fields = ['refresh'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'ar');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('
      select adddate(left(now(),10),-360) as start,
      adddate(left(now(),10),0) as end
    ');
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $clientid = $config['params']['clientid'];
    $center = $config['params']['center'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $qry = "select '' as bgcolor, date(a.dateid) as dateid, a.ntedoc as docno, a.irdoc as irref, date(a.idate) as idate, a.violation
      from (
        select ne.dateid,ne.docno as ntedoc,
        (select docno from incidenthead where trno=ne.refx 
          union all 
          select docno from hincidenthead where trno=ne.refx) as irdoc,
          (select dateid from incidenthead where trno=ne.refx 
            union all
            select dateid from hincidenthead where trno=ne.refx) as idate,concat(chead.description,' ',cdetail.description) as violation 
          from notice_explain as ne
          left join client as emp on emp.clientid=ne.empid
          left join codehead as chead on chead.artid=ne.artid
          left join codedetail as cdetail on ne.line=cdetail.line and chead.artid=cdetail.artid
            where emp.clientid=?
        UNION ALL
        select ne.dateid,ne.docno as ntedoc,
        (select docno from incidenthead where trno=ne.refx 
          union all
          select docno from hincidenthead where trno=ne.refx) as irdoc,
        (select dateid from incidenthead where trno=ne.refx 
          union all 
          select dateid from hincidenthead where trno=ne.refx) as idate,concat(chead.description,' ',cdetail.description) as violation
        from hnotice_explain as ne
        left join client as emp on emp.clientid=ne.empid
        left join codehead as chead on chead.artid=ne.artid
        left join codedetail as cdetail on ne.line=cdetail.line and chead.artid=cdetail.artid
        where emp.clientid=?) as a 
        where date(a.dateid) between '" . $start . "' and '" . $end . "'
        order by a.ntedoc";

    $data = $this->coreFunctions->opentable($qry, [$clientid, $clientid]);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }
} //end class
