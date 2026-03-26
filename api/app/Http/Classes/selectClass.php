<?php

namespace App\Http\Classes;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\companysetup;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\Logger;

class selectClass
{
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $companysetup;


  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->companysetup = new companysetup;
    $this->logger = new Logger;
  }

  public function searchselect($config) {
    $str = $config['params']['val'];
    $action = $config['params']['action'];
    $selectclass = $config['params']['selectclass'];
    $s = [];
    $qry = "";
    switch($action) {
      case 'clientselect':
        switch($selectclass) {
          case 'customer':
            $qry = 'select clientname as label, tel as description, addr as description2, clientid, client, clientname, tel, addr, clientname as client2 from client where iscustomer=1 and clientname like "%'.$str.'%" order by clientname limit 50';
            break;
        }
        break;
      case 'allclienthead':
        $qry = "select client.clientname as label, client.tel as description, client.addr as description2, client.clientid, client.client, client.clientname, client.tel, client.addr, client.clientname as client2, ifnull(terms.days,0) as days, client.terms from client left join terms on terms.terms = client.terms where (client.iscustomer=1 or client.issupplier=1 or client.isemployee=1) and client.isinactive=0 and client.clientname like '%".$str."%' order by client.clientname limit 50";
        break;
      case 'lookupclientmod':
        $qry = "select client.clientname as label, client.tel as description, client.addr as description2, client.clientid, client.client, client.clientname, client.tel, client.addr, client.clientname as client2, ifnull(terms.days,0) as days, client.terms from client left join terms on terms.terms = client.terms where client.iscustomer=1 and client.isinactive=0 and client.clientname like '%".$str."%' order by client.clientname limit 50";
        break;
      case 'allsupplierhead':
        $qry = "select client.clientname as label, client.tel as description, client.addr as description2, client.clientid, client.client, client.clientname, client.tel, client.addr, client.clientname as client2, ifnull(terms.days,0) as days, client.terms from client left join terms on terms.terms = client.terms where client.issupplier=1 and client.isinactive=0 and client.clientname like '%".$str."%' order by client.clientname limit 50";
        break;
      case 'accountselect':
        $qry = "select acnoid, acno as description, acnoname as label, left(alias,2) as description2, acno, acnoname, left(alias,2) as alias from coa where (acnoname like '%".$str."%' or acno like '%".$str."%') order by acnoname limit 50";
        break;
      case 'businessunit':
        $qry = "select line as projectid, code as projectcode, name as projectname, concat(code,'~',name) as description from projectmasterfile where name like '%".$str."%' or code like '%".$str."%' order by line";
        break;
      case 'costcode':
        $qry = "select line as costcodeid, code as costcode, name as costcodename, concat(code,'~',name) as description from costcode_masterfile where name like '%".$str."%' or code like '%".$str."%' order by line";
        break;
      case 'positem':
        $qry = "select itemname as description, barcode as description2, sizeid, barcode, item.itemid as itemid, item.category,
          grp.stockgrp_name as groupid, itemname, uom1.uom, uom1.factor,round(item.amt,2) as amt,brand,ifnull(cls.cl_name,'') as class,
          body, ifnull(part.part_name,'') as part,ifnull(model.model_name,'') as model,item.disc, item.partno,
          round(item.amt - (item.amt * (REPLACE(item.disc,'%','')/100)),2) as netprice
          from item
          left join item_class as cls on cls.cl_id=item.class
          left join uom as uom1 on item.itemid = uom1.itemid and uom1.uom = item.uom
          left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
          left join model_masterfile as model on model.model_id = item.model
          left join part_masterfile as part on part.part_id = item.part
          left join frontend_ebrands as brand on brand.brandid = item.brand
          where (item.itemname like '%".$str."%' or item.barcode like '%".$str."%')
          limit 50";
        break;
    }
    if($str != '') $s = $this->coreFunctions->opentable($qry);
    $ss = [];
    if(!empty($s)) {
      $ss = json_decode(json_encode($s), true);
    }
    return $ss;
  }
}
