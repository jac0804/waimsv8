<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewrrstockinfo
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'ITEMS';
  public $gridname = 'inventory';
  private $fields = ['barcode', 'itemname', 'payrem', 'isapproved'];
  private $table = 'stockrem';

  public $tablelogs = 'table_log';

  public $style = 'width:100%;max-width:80%;';
  public $issearchshow = true;
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 117
    );
    return $attrib;
  }

  public function createHeadField($config)
  {
    $fields = [];
    $col1 = $this->fieldClass->create($fields);

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    if (isset($config['params']['row'])) {
      $trno = $config['params']['row']['trno'];
    } else {
      $trno = $config['params']['dataparams']['trno'];
    }

    return $this->getheaddata($trno, $config['params']['doc']);
  }

  public function getheaddata($trno, $doc)
  {
    return [];
  }

  public function data()
  {
    return [];
  }

  public function createTab($config)
  {
    $trno = $config['params']['row']['trno'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");

    $admin = $this->othersClass->checkAccess($config['params']['user'], 4387);

    $ispaid = 0;
    $docno = 1;
    $ctrlno = 2;
    $barcode = 3;
    $itemdesc = 4;
    $rrqty = 5;
    $rrcost = 6;
    $disc = 7;
    $ext = 8;
    $amt1 = 9;
    $amt2 = 10;
    $amt3 = 11;
    $amt4 = 12;
    $amt5 = 13;
    $specs = 14;
    $ref = 15;
    $payrem = 16;
    $clientname = 17;
    $krdoc = 18;
    $pono = 19;
    $isapproved = 20;

    $column = ['ispaid', 'docno', 'ctrlno', 'barcode', 'itemdesc', 'rrqty', 'rrcost', 'disc', 'ext', 'amt1', 'amt2', 'amt3', 'amt4', 'amt5', 'specs', 'ref', 'payrem', 'clientname', 'krdoc', 'pono', 'isapproved'];
    $tab = [$this->gridname => ['gridcolumns' => $column]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$ispaid]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
    $obj[0][$this->gridname]['columns'][$disc]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
    $obj[0][$this->gridname]['columns'][$ext]['style'] = 'width:140px;whiteSpace: normal;min-width:140px;max-width:140px;';
    $obj[0][$this->gridname]['columns'][$payrem]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;max-width:200px;';
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
    $obj[0][$this->gridname]['columns'][$ctrlno]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;';

    $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$disc]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ref]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$pono]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$krdoc]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$ext]['type'] = 'input';


    $obj[0][$this->gridname]['columns'][$ref]['label'] = 'Payment Reference';
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Project';
    $obj[0][$this->gridname]['columns'][$krdoc]['label'] = 'PO Docno';

    $obj[0][$this->gridname]['columns'][$rrqty]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$rrcost]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$amt1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$amt2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$amt3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$amt4]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$amt5]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$amt1]['label'] = 'Delivery Fee';
    $obj[0][$this->gridname]['columns'][$amt2]['label'] = 'Diagnostic Fee';
    $obj[0][$this->gridname]['columns'][$amt3]['label'] = 'Installation Fee';
    $obj[0][$this->gridname]['columns'][$amt4]['label'] = 'Consultation Fee';
    $obj[0][$this->gridname]['columns'][$amt5]['label'] = 'Misc. Fee';

    if (!$admin) {
      $obj[0][$this->gridname]['columns'][$isapproved]['type'] = "coldel";
    }

    if ($isposted) {
      $obj[0][$this->gridname]['columns'][$payrem]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$ispaid]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $trno = $config['params']['row']['trno'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");
    if ($isposted) {
      $tbuttons = [];
    } else {
      $tbuttons = ['saveallentry'];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['row']['refx'];

    $data = $this->coreFunctions->opentable("select s.trno, s.line, h.docno, item.barcode, item.itemname, 
        FORMAT(s.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
        FORMAT(s.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost, 
        FORMAT(s.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
        info.itemdesc, info.specs,(case when sinfo.paytrno<>0 then 'true' else 'false' end) as ispaid, 
        pay.docno as ref, sinfo.payrem, '' as bgcolor, if(sinfo.isapproved<>0,'true','false') as isapproved,
        s.reqtrno,pr.docno,pr.clientname,s.refx,po.yourref as pono,po.docno as krdoc,'1' as isposted,info.ctrlno,
        poinfo.amt1,poinfo.amt2,poinfo.amt3,poinfo.amt4,poinfo.amt5
        from glstock as s left join item on item.itemid=s.itemid
        left join glhead as h on h.trno=s.trno
        left join hstockinfo as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
        left join cntnum as pay on pay.trno=sinfo.paytrno
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline 
        left join hprhead as pr on pr.trno=s.reqtrno
        left join hpohead as po on po.trno=s.refx
        left join hpostock as pos on pos.trno=s.refx and pos.line=s.linex
        left join hstockinfotrans as poinfo on poinfo.trno=pos.trno and poinfo.line=pos.line
        where s.trno=? union all
        select s.trno, s.line, h.docno, item.barcode, item.itemname, 
        FORMAT(s.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty, 
        FORMAT(s.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost, 
        FORMAT(s.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
        info.itemdesc, info.specs,(case when sinfo.paytrno<>0 then 'true' else 'false' end) as ispaid, 
        pay.docno as ref, sinfo.payrem, '' as bgcolor, if(sinfo.isapproved<>0,'true','false') as isapproved,
        s.reqtrno,pr.docno,pr.clientname,s.refx,po.yourref as pono,po.docno as krdoc,'0' as isposted,info.ctrlno,
        poinfo.amt1,poinfo.amt2,poinfo.amt3,poinfo.amt4,poinfo.amt5
        from lastock as s left join item on item.itemid=s.itemid
        left join lahead as h on h.trno=s.trno
        left join stockinfo as sinfo on sinfo.trno=s.trno and sinfo.line=s.line
        left join cntnum as pay on pay.trno=sinfo.paytrno
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline 
        left join hprhead as pr on pr.trno=s.reqtrno
        left join hpohead as po on po.trno=s.refx
        left join hpostock as pos on pos.trno=s.refx and pos.line=s.linex
        left join hstockinfotrans as poinfo on poinfo.trno=pos.trno and poinfo.line=pos.line
        where s.trno=?", [$trno, $trno]);

    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $paytrno = 0;
        if ($data[$key]['ispaid'] == "true") {
          $paytrno = $config['params']['row']['trno'];
        }
        switch ($data[$key]['isposted']) {
          case "1":
            $this->coreFunctions->sbcupdate("hstockinfo", [
              'paytrno' => $paytrno,
              'payrem' => $data2['payrem'],
              'isapproved' => $data2['isapproved'],
              'editdate' => $this->othersClass->getCurrentTimeStamp(),
              'editby' => $config['params']['user']
            ], ["trno" => $data[$key]['trno'], "line" => $data[$key]['line']]);
            break;
          case "0":
            $this->coreFunctions->sbcupdate("stockinfo", [
              'paytrno' => $paytrno,
              'payrem' => $data2['payrem'],
              'isapproved' => $data2['isapproved'],
              'editdate' => $this->othersClass->getCurrentTimeStamp(),
              'editby' => $config['params']['user']
            ], ["trno" => $data[$key]['trno'], "line" => $data[$key]['line']]);
            break;
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 
}
