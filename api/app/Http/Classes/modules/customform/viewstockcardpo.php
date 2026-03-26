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

class viewstockcardpo
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Purchase Order';
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
     //po pohistory button in po module -transpower
    if($config['params']['companyid'] == 60 && $config['params']['doc'] == 'PO' || $config['params']['companyid'] == 60 && $config['params']['moduletype'] == 'INQUIRY'){
      $row=$config['params']['row'];
      $itemid=$row['itemid'];
    }else{
      $itemid = $config['params']['clientid'];
    }

    $item = $this->othersClass->getitemname($itemid);
    $companyid = $config['params']['companyid'];
    if ($companyid == 8) { //maxipro
      $modulename = 'Purchase Order / Job Order ';
    } else {
      $modulename = $this->modulename;
    }
    $this->modulename = $modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;

    $docno = 0;
    $dateid = 1;
    $listclientname = 2;
    $isqty = 3;
    $qa = 4;
    $bal = 5;
    $whname = 6;
    $void = 7;
    $rem = 8;

    $columns = ['docno', 'dateid', 'listclientname', 'isqty', 'qa', 'bal', 'whname', 'void', 'rem'];
    $tab = [$this->gridname => ['gridcolumns' => $columns]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['totalfield'] = [];

    $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][$docno]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][$dateid]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$listclientname]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $obj[0][$this->gridname]['columns'][$listclientname]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$isqty]['label'] = 'Ordered';
    $obj[0][$this->gridname]['columns'][$isqty]['name']   = 'qty';
    $obj[0][$this->gridname]['columns'][$isqty]['field'] = 'qty';
    $obj[0][$this->gridname]['columns'][$isqty]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';

    $obj[0][$this->gridname]['columns'][$qa]['label'] = 'Received';
    $obj[0][$this->gridname]['columns'][$qa]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$qa]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';

    $obj[0][$this->gridname]['columns'][$bal]['label'] = 'Pending';
    $obj[0][$this->gridname]['columns'][$bal]['name']   = 'balance';
    $obj[0][$this->gridname]['columns'][$bal]['field'] = 'balance';
    $obj[0][$this->gridname]['columns'][$bal]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$bal]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';

    $obj[0][$this->gridname]['columns'][$void]['label'] = 'Void';
    $obj[0][$this->gridname]['columns'][$void]['type']   = 'input';
    $obj[0][$this->gridname]['columns'][$void]['align'] = 'left';
    $obj[0][$this->gridname]['columns'][$void]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';

    $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:90px;whiteSpace: normal;min-width:90px;';

    if ($companyid == 47) { // kitchenstar
      $obj[0][$this->gridname]['columns'][$whname]['align'] = 'text-left';
      $obj[0][$this->gridname]['columns'][$whname]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;text-align:left;';
    } else {
      $obj[0][$this->gridname]['columns'][$whname]['type'] = 'coldel';
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
    $fields = [['dateid', 'luom']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);
    data_set($col1, 'luom.lookupclass', 'uomledger');

    if($config['params']['companyid'] == 60 && $config['params']['doc'] == 'PO' || $config['params']['companyid'] == 60 && $config['params']['moduletype'] == 'INQUIRY'){
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
    if($config['params']['companyid'] == 60 && $config['params']['doc'] == 'PO' || $config['params']['companyid'] == 60 && $config['params']['moduletype'] == 'INQUIRY'){
       $itemid = $config['params']['row']['itemid'];
    }else{
       $itemid = $config['params']['clientid'];
    }
    
    $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'itemid=?', [$itemid]);
  
    if ($config['params']['companyid'] == 17 || $config['params']['companyid'] == 28 || $config['params']['companyid'] ==  60) { //unihome & xcomp & transpower
      $wh = '';
    } else {
      $wh = $this->companysetup->getwh($config['params']);
    }

     $addf="";
    if($config['params']['companyid'] == 60 && $config['params']['doc'] == 'PO' ||  $config['params']['companyid'] == 60 && $config['params']['moduletype'] == 'INQUIRY'){
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
      return $this->coreFunctions->opentable("select adddate(left(now(),10),-360) as dateid,'$wh' as wh, '$uom' as uom $addf");
    }
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $sort = " order by dateid";
    if($companyid  == 60 && $config['params']['doc'] == 'PO' || $companyid  == 60 && $config['params']['moduletype'] == 'INQUIRY'){
       $itemid = $config['params']['dataparams']['itemid'];       
    }else{
       $itemid = $config['params']['itemid'];
    }

    if($companyid  == 60){//transpower
      $sort = " order by dateid desc";
    }
  
    $center = $config['params']['center'];
    $date = date("Y-m-d", strtotime($config['params']['dataparams']['dateid']));
    $uom = $config['params']['dataparams']['uom'];
    $wh = $config['params']['dataparams']['wh'];

    $filter = '';
    if ($wh == '') {
      $filter = "";
    } else {
      $filter = " and wh.client ='$wh' ";
    }

    $addqry = '';
    if ($companyid == 8) { //maxipro
      $addqry = " union all select johead.trno, johead.doc, johead.docno,left(johead.dateid,10) as dateid, johead.clientname,
        round((jostock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qty,
         round((qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qa,
       round((jostock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,
        johead.rem, case(jostock.void) when 1 then 'YES' else 'NO' end as void,wh.clientname as whname
        from ((jostock left join johead on johead.trno=jostock.trno)
        left join item on item.itemid=jostock.itemid)
        left join uom on uom.itemid=item.itemid and uom.uom='$uom'
        left join transnum as cntnum on cntnum.trno = johead.trno
        left join client as wh on wh.clientid=jostock.whid
        where item.itemid='$itemid' and johead.dateid>='$date' and cntnum.center ='$center' $filter
        UNION ALL
        select hjohead.trno, hjohead.doc, hjohead.docno,left(hjohead.dateid,10) as dateid, hjohead.clientname,
        round((hjostock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qty,
        round((qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qa,
        round((hjostock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,
        hjohead.rem, case(hjostock.void) when 1 then 'YES' else 'NO' end as void,wh.clientname as whname
        from ((hjostock left join hjohead on hjohead.trno=hjostock.trno)
        left join item on item.itemid=hjostock.itemid) left join uom on uom.itemid=item.itemid
        and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = hjohead.trno
        left join client as wh on wh.clientid=hjostock.whid where item.itemid='$itemid'
        and hjohead.dateid>='$date' and cntnum.center ='$center' $filter";
    }

    $qry = "select pohead.trno, pohead.doc, pohead.docno,left(pohead.dateid,10) as dateid, pohead.clientname,
        round((postock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qty,
         round((qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qa,
       round((postock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,
        pohead.rem, case(postock.void) when 1 then 'YES' else 'NO' end as void,wh.clientname as whname
        from ((postock left join pohead on pohead.trno=postock.trno)
        left join item on item.itemid=postock.itemid)
        left join uom on uom.itemid=item.itemid and uom.uom='$uom'
        left join transnum as cntnum on cntnum.trno = pohead.trno
        left join client as wh on wh.clientid=postock.whid
        where item.itemid='$itemid' and pohead.dateid>='$date' and cntnum.center ='$center' $filter
        UNION ALL
        select hpohead.trno, hpohead.doc, hpohead.docno,left(hpohead.dateid,10) as dateid, hpohead.clientname,
        round((hpostock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qty,
        round((qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qa,
        round((hpostock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,
        hpohead.rem, case(hpostock.void) when 1 then 'YES' else 'NO' end as void,wh.clientname as whname
        from ((hpostock left join hpohead on hpohead.trno=hpostock.trno)
        left join item on item.itemid=hpostock.itemid) left join uom on uom.itemid=item.itemid
        and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = hpohead.trno
        left join client as wh on wh.clientid=hpostock.whid where item.itemid='$itemid'
        and hpohead.dateid>='$date' and cntnum.center ='$center' $filter " . $addqry . $sort;

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
