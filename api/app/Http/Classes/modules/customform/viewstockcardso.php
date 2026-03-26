<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sbcscript\sbcscript;

class viewstockcardso
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Sales Order';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  public $style = 'width:1500px;max-width:1500px;';
  public $issearchshow = true;
  public $showclosebtn = true;
  private $sbcscript;
  private $othersClass;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sbcscript = new sbcscript;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
     if($config['params']['companyid'] == 60  && $config['params']['moduletype'] == 'INQUIRY'){
        $itemid = $config['params']['row']['itemid'];
        }else{
        $itemid = $config['params']['clientid'];
        }
    $item = $this->othersClass->getitemname($itemid);
    $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;

    $tab = [$this->gridname => [
      'gridcolumns' => [
        'docno', 'dateid',
        'listclientname',
        'isqty', 'qa',
        'bal', 'whname', 'void', 'rem'
      ]
    ]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];

    // status
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    // dateid
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;text-align:center;';

    // listclientname
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';

    // iss
    $obj[0][$this->gridname]['columns'][3]['label'] = 'Ordered';
    $obj[0][$this->gridname]['columns'][3]['name']   = 'iss';
    $obj[0][$this->gridname]['columns'][3]['field'] = 'iss';
    $obj[0][$this->gridname]['columns'][3]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align: right;';


    // qa
    $obj[0][$this->gridname]['columns'][4]['label'] = 'Sold';
    $obj[0][$this->gridname]['columns'][4]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][4]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align: right;';


    // bal
    $obj[0][$this->gridname]['columns'][5]['label'] = 'Pending';
    $obj[0][$this->gridname]['columns'][5]['name']   = 'balance';
    $obj[0][$this->gridname]['columns'][5]['field'] = 'balance';
    $obj[0][$this->gridname]['columns'][5]['align'] = 'right';
    $obj[0][$this->gridname]['columns'][5]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align: right;';

    // void
    $obj[0][$this->gridname]['columns'][7]['label'] = 'Void';
    $obj[0][$this->gridname]['columns'][7]['type']   = 'input';
    $obj[0][$this->gridname]['columns'][7]['align'] = 'center';
    $obj[0][$this->gridname]['columns'][7]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;text-align: center;';

    // rem
    $obj[0][$this->gridname]['columns'][8]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';


    if ($companyid == 47) { // kitchenstar
      $obj[0][$this->gridname]['columns'][6]['align'] = 'text-left';
      $obj[0][$this->gridname]['columns'][6]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;text-align:left;';
    } else {
      $obj[0][$this->gridname]['columns'][6]['type'] = 'coldel';
    }
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
    $moduletype = $config['params']['moduletype'];
    $fields = [['dateid', 'luom']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);
    data_set($col1, 'luom.lookupclass', 'uomledger');
    
    if($config['params']['companyid'] == 60 && $moduletype=='INQUIRY'){
        data_set($col1, 'luom.addedparams', ['itemid']);
      }

    $fields = ['wh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'wh.lookupclass', 'whledger');


    $fields = [['refresh']];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'refresh.action', 'history');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    if($config['params']['companyid']==60 && $config['params']['moduletype']=='INQUIRY'){//transpower
       $itemid = $config['params']['row']['itemid'];
    }else{
       $itemid = $config['params']['clientid'];
    }
    $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'itemid=?', [$itemid]);
    if ($config['params']['companyid'] == 17 || $config['params']['companyid'] == 28 || $config['params']['companyid'] == 60) { //unihome & xcomp & transpower
      $wh = '';
    } else {
      $wh = $this->companysetup->getwh($config['params']);
    }

      $addf="";
    if($config['params']['companyid'] == 60 &&  $config['params']['moduletype'] == 'INQUIRY'){
       $row = $config['params']['row']['itemid'];
      $addf= ", " . $row . "   as itemid" ;
    }

    $data = $this->getbal($config, $itemid, $wh, $uom);
    if (!empty($data)) {
      return $this->coreFunctions->opentable("
      	select adddate(left(now(),10),-360) as dateid,
      	'$wh' as wh, 
      	'$uom' as uom $addf
      ");
    } else {
      return $this->coreFunctions->opentable("select 
      	adddate(left(now(),10),-360) as dateid,
      	'$wh' as wh, 
      	'$uom' as uom $addf");
    }
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    // $itemid = $config['params']['itemid'];
    $center = $config['params']['center'];
    $date = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $uom = $config['params']['dataparams']['uom'];
    $wh = $config['params']['dataparams']['wh'];
   
    if (isset($config['params']['dataparams']['itemid'])) {
      $itemid = $config['params']['dataparams']['itemid'];
    }else{
      $itemid = $config['params']['itemid'];
    }

    $filter = '';
    if ($wh == '') {
      $filter = "";
    } else {
      $filter = " and wh.client ='$wh' ";
    }

    $qry = "select sohead.rem,sohead.trno, sohead.doc, sohead.docno, left(sohead.dateid,10) as dateid,
        sohead.clientname, 
        round(sostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end),2) as iss,
        round(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end,2) as qty,
        round((sostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (sostock.qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,
        round(sostock.qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end),2) as qa, 
        case(sostock.void) when 1 then 'YES' else 'NO' end as void,wh.clientname as whname
        from sostock
        left join sohead on sohead.trno=sostock.trno
        left join item on item.itemid=sostock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
        left join transnum as cntnum on cntnum.trno = sohead.trno
        left join client as wh on wh.clientid=sostock.whid
        where item.itemid=" . $itemid . " and sohead.dateid>='" . $date . "' and cntnum.center ='" . $center . "' $filter
        union all
        select hsohead.rem,hsohead.trno, hsohead.doc, hsohead.docno, left(hsohead.dateid,10) as dateid,
        hsohead.clientname, 
        round(hsostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end),2) as iss,
        round(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end,2) as qty,
        round((hsostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (hsostock.qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,
        round(hsostock.qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end),2) as qa,
        case(hsostock.void) when 1 then 'YES' else 'NO' end as void,wh.clientname as whname
        from hsostock
        left join hsohead on hsohead.trno=hsostock.trno
        left join item on item.itemid=hsostock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
        left join transnum as cntnum on cntnum.trno = hsohead.trno
        left join client as wh on wh.clientid=hsostock.whid
        where item.itemid=" . $itemid . "
        and hsohead.dateid>='" . $date . "' and cntnum.center ='" . $center . "' $filter
        union all
        select head.rem,sohead.trno, sohead.doc, sohead.docno, left(sohead.dateid,10) as dateid,
        head.clientname, 
        round(sostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end),2) as iss,
        round(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end,2) as qty,
        round((sostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (sostock.qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,
        round(sostock.qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end),2) as qa, 
        case(sostock.void) when 1 then 'YES' else 'NO' end as void,wh.clientname as whname
        from hqsstock as sostock
        left join hqshead as head on head.trno = sostock.trno
        left join sqhead as sohead on sohead.trno=head.sotrno
        left join item on item.itemid=sostock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
        left join transnum as cntnum on cntnum.trno = sohead.trno
        left join client as wh on wh.clientid=sostock.whid
        where item.itemid=" . $itemid . " and sohead.dateid>='" . $date . "' and cntnum.center ='" . $center . "' $filter
        union all
        select head.rem,hsohead.trno, hsohead.doc, hsohead.docno, left(hsohead.dateid,10) as dateid,
        head.clientname, 
        round(hsostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end),2) as iss,
        round(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end,2) as qty,
        round((hsostock.iss/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (hsostock.qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,
        round(hsostock.qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end),2) as qa,
        case(hsostock.void) when 1 then 'YES' else 'NO' end as void,wh.clientname as whname
        from hqsstock as hsostock
        left join hqshead as head on head.trno = hsostock.trno
        left join hsqhead as hsohead on hsohead.trno=head.sotrno
        left join item on item.itemid=hsostock.itemid
        left join uom on uom.itemid=item.itemid and uom.uom='" . $uom . "'
        left join transnum as cntnum on cntnum.trno = hsohead.trno
        left join client as wh on wh.clientid=hsostock.whid
        where item.itemid=" . $itemid . "
        and hsohead.dateid>='" . $date . "' and cntnum.center ='" . $center . "' $filter order by dateid";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  } //end function

  public function getbal($config, $itemid, $wh, $uom)
  {
    $qry = "";
  } //end function

  public function sbcscript($config){
    if ($config['params']['companyid'] == 60) { //transpower
      return $this->sbcscript->skcustomform($config);
    } else {
      return true;
    }   
  }

} //end class
