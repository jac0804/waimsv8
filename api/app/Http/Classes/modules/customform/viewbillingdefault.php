<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewbillingdefault
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $warehousinglookup;

  public $modulename = 'Default Billing/Shipping Adrress';
  public $gridname = 'tableentry';
  private $fields = ['shipid', 'billid'];
  private $table = 'client';
  private $logger;
  public $tablelogs = 'client_log';

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
    $attrib = array('load' => 22, 'edit' => 23);
    return $attrib;
  }

  public function createHeadField($config)
  {

    $clientid = $config['params']['clientid'];
    $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);
    $this->modulename = $this->modulename . ' - ' . $customername;

    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];

    $access = 1;
    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
        $access = $this->othersClass->checkAccess($config['params']['user'], 23);
        break;
      case 'SUPPLIER':
        $access = $this->othersClass->checkAccess($config['params']['user'], 33);
        break;
    }

    if ($access == 0) {
      $fields = [
        ['lblshipping', 'shipid'], 'shipping',
        ['tasktitle', 'designation'], ['leadfrom', 'industry'], ['partno', 'year'], ['payor', 'prepared'], ['shipcontactname', 'shipcontactno']
      ];
    } else {
      $fields = [
        ['lblshipping', 'shipid'], 'shipping',
        ['tasktitle', 'designation'], ['leadfrom', 'industry'], ['partno', 'year'], ['payor', 'prepared'], ['shipcontactname', 'shipcontactno'], 'refresh'
      ];
    }



    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if (strtoupper($systemtype) == 'VSCHED' || strtoupper($systemtype) == 'ATI') {
      $fields = [['lblshipping', 'shipid'], 'shipping', ['shipcontactname', 'shipcontactno'], 'refresh'];
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'shipping.type', 'input');

    if ($companyid == 10) { //afti
      switch ($doc) {
        case 'VT':
        case 'VS':
          data_set($col1, 'shipid.type', 'input');
          data_set($col1, 'shipid.readonly', true);

          data_set($col1, 'shipcontactname.type', 'input');
          data_set($col1, 'shipcontactname.readonly', true);

          data_set($col1, 'refresh.type', 'hidden');
          break;
      }
    }

    data_set($col1, 'tasktitle.label', 'Address Type');
    data_set($col1, 'tasktitle.type', 'input');
    data_set($col1, 'tasktitle.class', 'cstasktitle sbccsreadonly');

    data_set($col1, 'designation.label', 'Zipcode');
    data_set($col1, 'designation.type', 'input');
    data_set($col1, 'designation.class', 'csdesignation sbccsreadonly');
    data_set($col1, 'designation.readonly', true);

    data_set($col1, 'payor.label', 'Country');
    data_set($col1, 'payor.type', 'input');
    data_set($col1, 'payor.class', 'cspayor sbccsreadonly');
    data_set($col1, 'payor.readonly', true);

    data_set($col1, 'partno.label', 'City/Town');
    data_set($col1, 'partno.type', 'input');
    data_set($col1, 'partno.class', 'cspartno sbccsreadonly');
    data_set($col1, 'partno.readonly', true);

    data_set($col1, 'prepared.label', 'Fax');
    data_set($col1, 'prepared.type', 'input');
    data_set($col1, 'prepared.class', 'csprepared sbccsreadonly');
    data_set($col1, 'prepared.readonly', true);

    data_set($col1, 'year.label', 'Province');
    data_set($col1, 'year.type', 'input');
    data_set($col1, 'year.class', 'csyear sbccsreadonly');
    data_set($col1, 'year.readonly', true);

    data_set($col1, 'leadfrom.class', 'csleadfrom sbccsreadonly');
    data_set($col1, 'leadfrom.readonly', true);
    data_set($col1, 'leadfrom.label', 'Address Line 1');

    data_set($col1, 'industry.label', 'Address Line 2');
    data_set($col1, 'industry.class', 'csindustry sbccsreadonly ');
    data_set($col1, 'industry.readonly', true);

    data_set($col1, 'refresh.label', 'Update');


    $fields = [
      ['lblbilling', 'billid'], 'billing',
      ['assignto', 'contactname'], ['leadto', 'agentcno'], ['subcode', 'month'], ['position', 'approved'], ['billcontactname', 'billcontactno']
    ];

    if (strtoupper($systemtype) == 'VSCHED' || strtoupper($systemtype) == 'ATI') {
      $fields = [];
    }
    $col2 = $this->fieldClass->create($fields);

    if ($companyid == 10) { //afti
      switch ($doc) {
        case 'VT':
        case 'VS':
          data_set($col2, 'billid.type', 'input');
          data_set($col2, 'billid.readonly', true);

          data_set($col2, 'billcontactname.type', 'input');
          data_set($col2, 'billcontactname.readonly', true);
          break;
      }
    }

    data_set($col2, 'billing.type', 'input');

    data_set($col2, 'assignto.label', 'Address Type');
    data_set($col2, 'assignto.type', 'input');
    data_set($col2, 'assignto.class', 'csassignto sbccsreadonly');

    data_set($col2, 'contactname.label', 'Zipcode');
    data_set($col2, 'contactname.type', 'input');
    data_set($col2, 'contactname.class', 'cscontactname sbccsreadonly');
    data_set($col2, 'contactname.readonly', true);

    data_set($col2, 'subcode.label', 'City/Town');
    data_set($col2, 'subcode.type', 'input');
    data_set($col2, 'subcode.class', 'cssubcode sbccsreadonly');
    data_set($col2, 'subcode.readonly', true);

    data_set($col2, 'approved.label', 'Fax');
    data_set($col2, 'approved.type', 'input');
    data_set($col2, 'approved.class', 'csapproved sbccsreadonly');
    data_set($col2, 'approved.readonly', true);

    data_set($col2, 'position.label', 'Country');
    data_set($col2, 'position.type', 'input');
    data_set($col2, 'position.class', 'csposition sbccsreadonly');
    data_set($col2, 'position.readonly', true);

    data_set($col2, 'month.label', 'Province');
    data_set($col2, 'month.type', 'input');
    data_set($col2, 'month.class', 'csmonth sbccsreadonly');
    data_set($col2, 'month.readonly', true);

    data_set($col2, 'leadto.class', 'csleadto sbccsreadonly');
    data_set($col2, 'leadto.readonly', true);
    data_set($col2, 'leadto.label', 'Address Line 1');

    data_set($col2, 'agentcno.label', 'Address Line 2');
    data_set($col2, 'agentcno.readonly', true);
    data_set($col2, 'agentcno.class', 'csagentcno sbccsreadonly');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $clientid = $config['params']['clientid'];

    if ($companyid == 10) { //afti
      switch ($doc) {
        case 'VT':
        case 'VS':
          if ($doc == "VT") {
            $table = "vthead";
            $htable = "hvthead";
          } else if ($doc == "VS") {
            $table = "vshead";
            $htable = "hvshead";
          }

          $client = $this->coreFunctions->datareader(
            "
            select client as value from " . $table . " where trno = '" . $config['params']['clientid'] . "' LIMIT 1
            union all
            select client as value from " . $htable . " where trno = '" . $config['params']['clientid'] . "' LIMIT 1"
          );

          $clientid = $this->coreFunctions->datareader(
            "
            select clientid as value from client where client = '" . $client . "' LIMIT 1"
          );

          break;
      }
    }

    $qry = "select client.shipid, ifnull(s.addr,'') as shipping, 
      client.billid, ifnull(b.addr,'') as billing,
      ifnull(s.contact,'') as contact, ifnull(s.contactno,'') as mobileno,
      ifnull(b.contact,'') as forex, ifnull(b.contactno,'') as tax,
      ifnull(s.addrtype, '') as tasktitle, ifnull(s.addrline1, '') as leadfrom, ifnull(s.addrline2, '') as industry, 
      ifnull(s.city, '') as partno, ifnull(s.province, '') as year, ifnull(s.country, '') as payor, 
      ifnull(s.zipcode, '') as designation, ifnull(s.fax, '') as prepared,
      ifnull(b.addrtype, '') as assignto, ifnull(b.addrline1, '') as leadto, ifnull(b.addrline2, '') as agentcno, 
      ifnull(b.city, '') as subcode, ifnull(b.province, '') as month, ifnull(b.country, '') as position, 
      ifnull(b.zipcode, '') as contactname, ifnull(b.fax, '') as approved,
      concat(sc.lname,', ',sc.fname,' ',sc.mname) as shipcontactname, sc.contactno as shipcontactno,
      concat(bc.lname,', ',bc.fname,' ',bc.mname) as billcontactname, bc.contactno as billcontactno,
      client.billcontactid, client.shipcontactid
      from client 
      left join billingaddr as s on s.line=client.shipid
      left join billingaddr as b on b.line=client.billid
      left join contactperson as bc on bc.line=client.billcontactid
      left join contactperson as sc on sc.line=client.shipcontactid
      where client.clientid=?";

    return $this->coreFunctions->opentable($qry, [$clientid]);
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
    $clientid  = $config['params']['clientid'];
    $data = [
      'shipid' => $config['params']['dataparams']['shipid'],
      'billid' => $config['params']['dataparams']['billid'],
      'billcontactid' => $config['params']['dataparams']['billcontactid'],
      'shipcontactid' => $config['params']['dataparams']['shipcontactid']
    ];
    $this->coreFunctions->sbcupdate("client", $data, ['clientid' => $clientid]);
    $this->logger->sbcwritelog(
      $clientid,
      $config,
      'DEFAULT ADDRESS',
      ' SHIPPING: ' . $data['shipid'] . "-" . $config['params']['dataparams']['shipping'] . ',
          BILLING: ' . $data['billid'] . "-" . $config['params']['dataparams']['billing'] . ',
          BILL CONTACT:' . $data['billcontactid'] . "-" . $config['params']['dataparams']['shipcontactname'] . ',
          SHIP CONTACT:' . $data['shipcontactid'] . "-" . $config['params']['dataparams']['billcontactname']
    );
    $data = $this->getheaddata($config);
    return ['status' => true, 'msg' => 'Successfully updated.', 'data' => $data];
  }
}
