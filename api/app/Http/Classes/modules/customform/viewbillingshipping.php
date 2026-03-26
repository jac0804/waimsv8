<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\inventory\va;
use Exception;

class viewbillingshipping
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'Billing/Shipping Address';
  public $gridname = 'tableentry';
  private $fields = ['shipid', 'billid'];
  private $table = 'client';

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $logger;
  public $style = 'width:100%;max-width:70%;';
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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createHeadField($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['clientid'];
    $companyid = $config['params']['companyid'];

    if ($doc == "SJ" || $doc == 'SU' || $doc == 'AI') {
      $isposted = $this->othersClass->isposted2($trno, "cntnum");
    } else {
      $isposted = $this->othersClass->isposted2($trno, "transnum");
    }


    switch ($doc) {
      case 'PO':
      case 'RR':
      case 'DM':
      case 'JB':
      case 'AC':
      case 'OS':
      case 'OP':
      case 'SQ':
      case 'CM':
      case 'AO':
      case 'TE':
      case 'VS':
      case 'VT':
        $fields = [['lblbilling'], 'billing', ['billcontactname', 'billcontactno'], ['billemail', 'billmobile'], ['lblshipping'], 'shipping', ['shipcontactname', 'shipcontactno'], ['shipemail', 'shipmobile']];
        break;

      default:
        $fields = [['lblbilling', 'billid'], 'billing', ['billcontactname', 'billcontactno'], ['billemail', 'billmobile'], ['lblshipping', 'shipid'], 'shipping', ['shipcontactname', 'shipcontactno'], ['shipemail', 'shipmobile']];
        break;
    }

    if (!$isposted) {
      switch ($doc) {
        case 'QS':
        case 'RF':
        case 'SU': // button for qs and rf
        case 'SJ':
        case 'SR':
        case 'AI':
          array_push($fields, 'refresh');
          break;
      }
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'lblbilling.label', 'BILLING ADDRESS');
    data_set($col1, 'lblshipping.label', 'SHIPPING ADDRESS');
    data_set($col1, 'billing.type', 'textarea');
    data_set($col1, 'shipping.type', 'textarea');
    data_set($col1, 'refresh.label', 'Save');

    switch ($doc) {
      case 'PO':
      case 'RR':
      case 'DM':
      case 'JB':
      case 'AC':
      case 'OS':
      case 'OP':
      case 'SQ':
      case 'CM':
      case 'AO':
      case 'TE':
      case 'VS':
      case 'VT':
        data_set($col1, 'billcontactname.type', 'input');
        data_set($col1, 'shipcontactname.type', 'input');
        data_set($col1, 'billcontactname.readonly', true);
        data_set($col1, 'shipcontactname.readonly', true);
        break;
    }

    $fields = [];
    if ($companyid = 10 || $companyid == 12) {
      switch ($doc) {
        case 'QS':
          $fields = ['lblgrossprofit', 'conaddr', ['contactname', 'contactno'], 'rem2'];
          break;
      }
    }

    $col2 = $this->fieldClass->create($fields);

    if ($companyid = 10 || $companyid == 12) {
      switch ($doc) {
        case 'QS':
          data_set($col2, 'lblgrossprofit.label', 'COLLECTION DETAILS');
          data_set($col2, 'lblgrossprofit.style', 'font-weight:bold;font-size:15px');
          data_set($col2, 'conaddr.label', 'Address');
          data_set($col2, 'conaddr.type', 'lookup');
          data_set($col2, 'conaddr.lookupclass', 'lookupcollectiondetails');
          data_set($col2, 'conaddr.action', 'lookupcollectiondetails');
          data_set($col2, 'contactname.maxlength', '100');
          data_set($col2, 'contactno.label', 'Contact#');
          data_set($col2, 'contactno.readonly', true);
          data_set($col2, 'conaddr.readonly', true);
          data_set($col2, 'contactname.readonly', true);
          data_set($col2, 'rem2.label', 'Collection Notes');
          data_set($col2, 'rem2.maxlength', '500');
          break;
      }
    }

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {
    $trno = $config['params']['clientid'];
    $doc = $config['params']['doc'];
    $head = 'lahead';
    $hhead = 'glhead';
    $hleftjoin = " left join client on client.clientid = head.clientid";
    $addfields = "";
    switch ($doc) {
      case 'QT':
      case 'SQ':
      case 'SR':
      case 'PO':
      case 'OS':
      case 'RF':
      case 'VS':
      case 'VT':
        $head = strtolower($config['params']['doc']) . 'head';
        $hhead = 'h' . strtolower($config['params']['doc']) . 'head';
        $hleftjoin = " left join client on client.client = head.client";
        break;
      case 'AO':
        $head = 'sshead';
        $hhead = 'hsshead';
        $hleftjoin = " left join client on client.client = head.client";
        break;
      case 'JB': // JOB ORDER
        $head = 'johead';
        $hhead = 'hjohead';
        $hleftjoin = " left join client on client.client = head.client";
        break;
      case 'QS':
        $head = strtolower($config['params']['doc']) . 'head';
        $hhead = 'h' . strtolower($config['params']['doc']) . 'head';
        $hleftjoin = " left join client on client.client = head.client";
        $addfields = ",head.address1 as conaddr, head.cperson as contactname,head.contactno,if(head.rem2 !='',head.rem2,client.rem2) as rem2";
        break;
    }

    $qry = "select " . $trno . " as trno, head.shipid, ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
        head.billid, ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        head.shipcontactid as shipcontactid, head.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail $addfields
        from " . $head . " as head
        left join client on client.client = head.client
        left join billingaddr as s on s.line=head.shipid and s.clientid = client.clientid
        left join billingaddr as b on b.line=head.billid and b.clientid = client.clientid
        left join contactperson as bc on bc.line=head.billcontactid and bc.clientid = client.clientid
        left join contactperson as sc on sc.line=head.shipcontactid and sc.clientid = client.clientid
        where head.trno=?
        union all 
        select " . $trno . " as trno, head.shipid, ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
        head.billid, ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        head.shipcontactid as shipcontactid, head.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail $addfields
        from " . $hhead . " as head
        " . $hleftjoin . "
        left join billingaddr as s on s.line=head.shipid and s.clientid = client.clientid
        left join billingaddr as b on b.line=head.billid and b.clientid = client.clientid
        left join contactperson as bc on bc.line=head.billcontactid and bc.clientid = client.clientid
        left join contactperson as sc on sc.line=head.shipcontactid and sc.clientid = client.clientid
        where head.trno=?";

    if ($doc == 'SQ') {
      $qry = "select " . $trno . " as trno, ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
         ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        qt.shipcontactid as shipcontactid, qt.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail
        from " . $head . " as head
        left join hqshead as qt on qt.sotrno=head.trno
        left join billingaddr as s on s.line=qt.shipid
        left join billingaddr as b on b.line=qt.billid
        left join contactperson as bc on bc.line=qt.billcontactid
        left join contactperson as sc on sc.line=qt.shipcontactid
        where head.trno=?
        union all 
        select " . $trno . " as trno, ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
        ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        qt.shipcontactid as shipcontactid, qt.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail
        from " . $hhead . " as head
        left join hqshead as qt on qt.sotrno=head.trno
        left join billingaddr as s on s.line=qt.shipid
        left join billingaddr as b on b.line=qt.billid
        left join contactperson as bc on bc.line=qt.billcontactid
        left join contactperson as sc on sc.line=qt.shipcontactid
        where head.trno=?";
    }

    if ($doc == 'VT') {
      $qry = "select " . $trno . " as trno, ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
         ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        qt.shipcontactid as shipcontactid, qt.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail
        from " . $head . " as head
        left join hqshead as qt on qt.sotrno=head.sotrno
        left join billingaddr as s on s.line=qt.shipid
        left join billingaddr as b on b.line=qt.billid
        left join contactperson as bc on bc.line=qt.billcontactid
        left join contactperson as sc on sc.line=qt.shipcontactid
        where head.trno=?
        union all 
        select " . $trno . " as trno, ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
        ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        qt.shipcontactid as shipcontactid, qt.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail
        from " . $hhead . " as head
        left join hqshead as qt on qt.sotrno=head.sotrno
        left join billingaddr as s on s.line=qt.shipid
        left join billingaddr as b on b.line=qt.billid
        left join contactperson as bc on bc.line=qt.billcontactid
        left join contactperson as sc on sc.line=qt.shipcontactid
        where head.trno=?";
    }

    if ($doc == 'AO') {
      $qry = "select " . $trno . " as trno,  ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
        ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        qt.shipcontactid as shipcontactid, qt.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail
        from " . $head . " as head
        left join hsrhead as qt on qt.sotrno=head.trno
        left join billingaddr as s on s.line=qt.shipid
        left join billingaddr as b on b.line=qt.billid
        left join contactperson as bc on bc.line=qt.billcontactid
        left join contactperson as sc on sc.line=qt.shipcontactid
        where head.trno=?
        union all 
        select " . $trno . " as trno,  ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
        ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        qt.shipcontactid as shipcontactid, qt.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail
        from " . $hhead . " as head
        left join hsrhead as qt on qt.sotrno=head.trno
        left join billingaddr as s on s.line=qt.shipid
        left join billingaddr as b on b.line=qt.billid
        left join contactperson as bc on bc.line=qt.billcontactid
        left join contactperson as sc on sc.line=qt.shipcontactid
        where head.trno=?";
    }

    if ($doc == 'VS') {
      $qry = "select " . $trno . " as trno,  ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
        ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        qt.shipcontactid as shipcontactid, qt.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail
        from " . $head . " as head
        left join hsrhead as qt on qt.sotrno=head.sotrno
        left join billingaddr as s on s.line=qt.shipid
        left join billingaddr as b on b.line=qt.billid
        left join contactperson as bc on bc.line=qt.billcontactid
        left join contactperson as sc on sc.line=qt.shipcontactid
        where head.trno=?
        union all 
        select " . $trno . " as trno,  ifnull(concat(s.addrline1,' ',s.addrline2,' ',s.city,' ',s.province,' ',s.country,' ',s.zipcode),'') as shipping, 
        ifnull(concat(b.addrline1,' ',b.addrline2,' ',b.city,' ',b.province,' ',b.country,' ',b.zipcode),'') as billing,
        concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
        concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
        qt.shipcontactid as shipcontactid, qt.billcontactid as billcontactid,bc.mobile as billmobile,sc.mobile as shipmobile,bc.email as billemail,sc.email as shipemail
        from " . $hhead . " as head
        left join hsrhead as qt on qt.sotrno=head.sotrno
        left join billingaddr as s on s.line=qt.shipid
        left join billingaddr as b on b.line=qt.billid
        left join contactperson as bc on bc.line=qt.billcontactid
        left join contactperson as sc on sc.line=qt.shipcontactid
        where head.trno=?";
    }


    return $this->coreFunctions->opentable($qry, [$trno, $trno]);
  }

  public function data()
  {
    return [];
  }

  public function createTab($config)
  {
    $tab = [];
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

  public function loaddata($config)
  {
    $head = 'lahead';
    $data = [
      'shipid' => $config['params']['dataparams']['shipid'],
      'shipcontactid' => $config['params']['dataparams']['shipcontactid'],
      'billid' => $config['params']['dataparams']['billid'],
      'billcontactid' => $config['params']['dataparams']['billcontactid'],
      'editby' => $config['params']['user'],
      'editdate' => $this->othersClass->getCurrentTimeStamp()
    ];

    if ($config['params']['doc'] == 'QS') {
      $data['address1'] = $config['params']['dataparams']['conaddr'];
      $data['cperson'] = $config['params']['dataparams']['contactname'];
      $data['contactno'] = $config['params']['dataparams']['contactno'];
      $data['rem2'] = $config['params']['dataparams']['rem2'];
    }


    switch ($config['params']['doc']) {
      case 'PO':
      case 'OS':
      case 'SR':
        $head = strtolower($config['params']['doc']) . 'head';
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($config['params']['doc']);
        break;
      case 'QS':
      case 'QT':
      case 'OP':
      case 'RF':
        $head = strtolower($config['params']['doc']) . 'head';
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($config['params']['doc']);
        break;
    }

    $this->coreFunctions->sbcupdate($head, $data, ['trno' => $config['params']['dataparams']['trno']]);
    return ['status' => true, 'msg' => 'Successfully updated.', 'reloadhead' => true, 'trno' => $config['params']['dataparams']['trno'], 'data' => []];
  }
}
