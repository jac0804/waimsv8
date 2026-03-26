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

class viewstockcardpr
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Purchase Requisition';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  public $style = 'width:1500px;max-width:1500px;';
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
    $itemid = $config['params']['clientid'];
    $item = $this->othersClass->getitemname($itemid);
    $companyid = $config['params']['companyid'];

    $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;

    $docno = 0;
    $dateid = 1;
    $listclientname = 2;
    $isqty = 3;
    $qa = 4;
    $bal = 5;
    $void = 6;
    $rem = 7;

    $columns = ['docno', 'dateid', 'listclientname', 'isqty', 'qa', 'bal', 'void', 'rem'];
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

    $obj[0][$this->gridname]['columns'][$isqty]['label'] = 'Request Qty';
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
    $itemid = $config['params']['clientid'];
    $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'itemid=?', [$itemid]);
    $wh = $this->companysetup->getwh($config['params']);

    $data = $this->getbal($config, $itemid, $wh, $uom);
    if (!empty($data)) {
      return $this->coreFunctions->opentable("
      	select adddate(left(now(),10),-360) as dateid,
      	'$wh' as wh,
      	'$uom' as uom
      ");
    } else {
      return $this->coreFunctions->opentable("select adddate(left(now(),10),-360) as dateid,'$wh' as wh, '$uom' as uom");
    }
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $itemid = $config['params']['itemid'];
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
    $qry = "select prhead.trno, prhead.doc, prhead.docno,left(prhead.dateid,10) as dateid, prhead.clientname,
        round((prstock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qty,
         round((qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qa,
         round((prstock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,
        prhead.rem, case(prstock.void) when 1 then 'YES' else 'NO' end as void
        from ((prstock left join prhead on prhead.trno=prstock.trno)
        left join item on item.itemid=prstock.itemid)
        left join uom on uom.itemid=item.itemid and  uom.uom='$uom'
        left join transnum as cntnum on cntnum.trno = prhead.trno
        left join client as wh on wh.clientid=prstock.whid
        where item.itemid='$itemid' and prhead.dateid>='$date' and cntnum.center ='$center' $filter
        UNION ALL
        select hprhead.trno, hprhead.doc, hprhead.docno,left(hprhead.dateid,10) as dateid, hprhead.clientname,
        round((hprstock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qty,
        round((qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as qa,
        round((hprstock.qty/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)) - (qa/(case when ifnull(uom.factor, 0)=0 then 1 else uom.factor end)),2) as balance,hprhead.rem, case(hprstock.void) when 1 then 'YES' else 'NO' end as void
        from ((hprstock left join hprhead on hprhead.trno=hprstock.trno)
        left join item on item.itemid=hprstock.itemid) left join uom on uom.itemid=item.itemid
        and uom.uom='$uom' left join transnum as cntnum on cntnum.trno = hprhead.trno
        left join client as wh on wh.clientid=hprstock.whid where item.itemid='$itemid'
        and hprhead.dateid>='$date' and cntnum.center ='$center'  $filter   order by dateid;";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  } //end function

  public function getbal($config, $itemid, $wh, $uom)
  {
    $qry = "";
  } //end function

} //end class
