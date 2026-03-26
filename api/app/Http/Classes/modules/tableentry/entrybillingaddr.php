<?php

namespace App\Http\Classes\modules\tableentry;

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
use App\Http\Classes\modules\construction\mi;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use PhpParser\Node\Stmt\Break_;

class entrybillingaddr
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Billing/Shipping Address Setup';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'billingaddr';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['clientid', 'contact', 'contactno',  'addr',  'isbilling', 'isshipping', 'isinactive', 'addrtype',  'addrline1', 'addrline2',  'city',  'province', 'country', 'zipcode',   'fax',  'deptid',  'address1',  'cperson',  'contactno2'];
  public $showclosebtn = false;
  private $reporter;
  private $logger;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $companyid = $config['params']['companyid'];
    $clientid = $config['params']['tableid'];
    $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);

    if ($companyid == 19) { //housegem
      $this->modulename = 'Shipping Address Setup' . ' - ' . $customername;
    } else {
      $this->modulename = $this->modulename . ' - ' . $customername;
    }


    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $action = 0;
    $isshipping = 1;
    $isbilling = 2;
    $isinactive = 3;
    $client = 4;
    $clientname = 5;
    $addr = 6;
    $addrtype = 7;
    $addrline1 = 8;
    $addrline2 = 9;
    $city = 10;
    $province = 11;
    $country = 12;
    $zipcode = 13;
    $contactno = 14;
    $fax = 15;
    $deptname = 16;
    $address1 = 17;
    $cperson = 18;
    $contactno2 = 19;

    $tab = [$this->gridname => ['gridcolumns' => ['action', 'isshipping', 'isbilling', 'isinactive', 'client', 'clientname', 'addr', 'addrtype', 'addrline1', 'addrline2', 'city', 'province', 'country', 'zipcode', 'contactno', 'fax', 'deptname', 'address1', 'cperson', 'contactno2']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$isbilling]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$isshipping]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$isinactive]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";

    $obj[0][$this->gridname]['columns'][$client]['style'] = "width:180px;whiteSpace: normal;min-width:180px;";
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$addr]['type'] = "textarea";
    $obj[0][$this->gridname]['columns'][$addr]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$addrtype]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$addrline1]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$addrline2]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$city]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$province]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$country]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$zipcode]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$contactno]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$fax]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$zipcode]['type'] = "cinput";
    $obj[0][$this->gridname]['columns'][$zipcode]['maxlength'] = "10";
    $obj[0][$this->gridname]['columns'][$action]['btns']['save']['checkfield'] = "isallowed";
    $obj[0][$this->gridname]['columns'][$action]['btns']['delete']['checkfield'] = "isallowed";

    $obj[0][$this->gridname]['columns'][$address1]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$cperson]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$contactno2]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";

    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
      case 'SUPPLIER':
        $obj[0][$this->gridname]['columns'][$client]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = "coldel";
        $obj[0][$this->gridname]['columns'][$contactno]['label'] = "Phone #";
        $obj[0][$this->gridname]['columns'][$addr]['label'] = "Address Title";
        break;

      default:
        $obj[0][$this->gridname]['columns'][$client]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$client]['lookupclass'] = "client";
        $obj[0][$this->gridname]['columns'][$client]['label'] = "Client Code";
        $obj[0][$this->gridname]['columns'][$client]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Client Name";
        break;
    }

    if (strtoupper($systemtype) == 'VSCHED' || strtoupper($systemtype) == 'ATI') {
      $obj[0][$this->gridname]['columns'][$isbilling]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$isshipping]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$client]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$clientname]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$addrtype]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$addrline1]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$addrline2]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$city]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$province]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$country]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$zipcode]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$contactno]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$fax]['type'] = "coldel";

      $obj[0][$this->gridname]['columns'][$deptname]['label'] = "Assigned Department";
      $obj[0][$this->gridname]['columns'][$deptname]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$deptname]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$deptname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    } else {
      $obj[0][$this->gridname]['columns'][$deptname]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$zipcode]['required'] = true;
    }


    if (strtoupper($config['params']['doc'] == 'CUSTOMER') && $companyid == 19) { //housegem
      $obj[0][$this->gridname]['columns'][$addr]['label'] = "Address";
      $obj[0][$this->gridname]['columns'][$addr]['style'] = "width:1000px;whiteSpace: normal;min-width:1000px;";

      $obj[0][$this->gridname]['columns'][$isbilling]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$isshipping]['type'] = "coldel";

      $obj[0][$this->gridname]['columns'][$client]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$clientname]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$addrtype]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$addrline1]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$addrline2]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$city]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$province]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$country]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$zipcode]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$contactno]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$fax]['type'] = "coldel";
    }

    //rwen 3/20/2026

    if ((strtoupper($config['params']['doc']) == 'CUSTOMER' || strtoupper($config['params']['doc']) == 'SUPPLIER')  && $companyid != 10   && $companyid != 12) {
      $obj[0][$this->gridname]['columns'][$address1]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$cperson]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$contactno2]['type'] = "coldel";
    }


    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $access = 1;
    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
        if ($config['params']['companyid'] == 16) { //ati
          $access = $this->othersClass->checkAccess($config['params']['user'], 3744);
        } else {
          $access = $this->othersClass->checkAccess($config['params']['user'], 23);
        }
        break;
      case 'SUPPLIER':
        $access = $this->othersClass->checkAccess($config['params']['user'], 33);
        break;
    }

    if ($access == 0) {
      $tbuttons = ['print', 'whlog'];
    } else {
      $tbuttons = ['addrecord', 'saveallentry', 'print', 'whlog'];
    }

    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = strtoupper($config['params']['doc']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $data = [];
    $data['line'] = 0;
    $addrtitle = '';
    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
      case 'SUPPLIER':
        $data['clientid'] = $config['params']['tableid'];
        break;

      default:
        $data['clientid'] = 0;
        break;
    }
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $clientid = $config['params']['tableid'];
        $result = $this->getclient($clientid);
        $addrtitle = $result[0]->clientname;
        break;

      default:
        $addrtitle = '';
        break;
    }

    $data['client'] = '';
    $data['clientname'] = '';
    $data['addr'] = $addrtitle;
    $data['contactno'] = '';
    $data['contact'] = '';
    $data['isbilling'] = 'false';
    if ($systemtype == 'ATI' || $systemtype == 'VSCHED' || ($companyid == 19 && $doc == 'CUSTOMER')) {
      $data['isshipping'] = 'true';
    } else {
      $data['isshipping'] = 'false';
    }
    $data['isinactive'] = 'false';
    $data['addrtype'] = '';
    $data['addrline1'] = '';
    $data['addrline2'] = '';
    $data['city'] = '';
    $data['province'] = '';
    $data['country'] = '';
    $data['zipcode'] = '';
    $data['fax'] = '';
    if ($companyid == 16) { //ati
      $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$config['params']['adminid']]);
      $data['deptid'] = $deptid;
    } else {
      $data['deptid'] = '0';
    }
    $data['deptname'] = '';
    $data['bgcolor'] = 'bg-blue-2';

    $data['address1'] = '';
    $data['cperson'] = '';
    $data['contactno2'] = '';


    return $data;
  }

  private function selectqry()
  {
    $qry = "b.line as line, c.clientid as clientid, c.client as client, c.clientname as clientname,
    b.addr as addr, b.contact as contact, b.contactno as contactno,
    case when b.isbilling=0 then 'false' else 'true' end as isbilling,
    case when b.isshipping=0 then 'false' else 'true' end as isshipping,
    case when b.isinactive=0 then 'false' else 'true' end as isinactive,
    b.addrtype, b.addrline1, b.addrline2,
    b.city, b.province, b.country, b.zipcode, b.fax, b.deptid, ifnull(dept.clientname, '') as deptname, ifnull(b.address1,'') as address1, ifnull(b.cperson,'') as cperson,ifnull(b.contactno2,'') as contactno2
    ";
    return $qry;
  }

  public function saveallentry($config)
  {
    $companyid = $config['params']['companyid'];

    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $params = $config;
    $data = $config['params']['data'];
    unset($data['isallowed']);
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if (strtoupper($systemtype) != 'VSCHED' && strtoupper($systemtype) != 'ATI') {

          switch ($companyid) {
            case 19: //housegem
              break;
            default:
              if ($data[$key]['zipcode'] == "") {
                $returndata = $this->loaddata($config);
                return ['status' => false, 'msg' => 'Zipcode is required', 'data' => $returndata];
              }
              break;
          }
        }

        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
          $cdata = $this->getclient($data[$key]['clientid']);

          $params['params']['doc'] = strtoupper("billing_add_tab");
          $this->logger->sbcmasterlog($config['params']['tableid'], $params, ' CREATE - '
            . ", LINE: " . $line
            . " " . $cdata[0]->client . '~' . $cdata[0]->clientname
            . ", SHIPPING: " . $data2['isshipping']
            . ", BILLING: " . $data2['isbilling']
            . ", INACTIVE: " . $data2['isinactive']
            . ", ADDR: " . $data2['addr']
            . ", ADDR TYPE: " . $data2['addrtype']
            . ", ADDR LINE1: " . $data2['addrline1']
            . ", ADDR LINE2: " . $data2['addrline2']
            . ", CITY: " . $data2['city']
            . ", PROVINCE: " . $data2['province']
            . ", COUNTRY: " . $data2['country']
            . ", ZIP: " . $data2['zipcode']
            . ", FAX: " . $data2['fax']
            . ", CONTACT PERSON: " . $data2['cperson']
            . ", COLLECTION ADDR: " . $data2['address1']
            . ", COLLECTION CONTACT#: " . $data2['contactno2']);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function  

  public function save($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $data = [];
    $params = $config;
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if (strtoupper($systemtype) != 'VSCHED' && strtoupper($systemtype) != 'ATI') {
      if ($row['zipcode'] == "") {
        return ['status' => false, 'msg' => 'Zipcode is required'];
      }
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line, $config);
        $cdata = $this->getclient($data['clientid']);

        $params['params']['doc'] = strtoupper("billing_add_tab");
        $this->logger->sbcmasterlog(
          $config['params']['tableid'],
          $params,
          ' CREATE - '
            . ", LINE: " . $line
            . " " . $cdata[0]->client . '~' . $cdata[0]->clientname
            . ", SHIPPING: " . $data['isshipping']
            . ", BILLING: " . $data['isbilling']
            . ", INACTIVE: " . $data['isinactive']
            . ", ADDR: " . $data['addr']
            . ", ADDR TYPE: " . $data['addrtype']
            . ", ADDR LINE1: " . $data['addrline1']
            . ", ADDR LINE2: " . $data['addrline2']
            . ", CITY: " . $data['city']
            . ", PROVINCE: " . $data['province']
            . ", COUNTRY: " . $data['country']
            . ", ZIP: " . $data['zipcode']
            . ", FAX: " . $data['fax']
            . ", CONTACT PERSON: " . $data['cperson']
            . ", COLLECTION ADDR: " . $data['address1']
            . ", COLLECTION CONTACT#: " . $data['contactno2']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line'], $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];

    $exist = $this->coreFunctions->datareader("select trno as value from lahead where shipid=? or billid=?
                union all
                select trno as value from glhead where shipid=? or billid=?
                union all    
                select trno from vrstock where shipid=?
                union all
                select trno from hvrstock where shipid=?", [$row['line'], $row['line'], $row['line'], $row['line'], $row['line'], $row['line']]);

    if ($exist) {
      return ['status' => false, 'msg' => 'Cannot delete, already used in transaction'];
    }

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['client'] . '~' . $row['clientname']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line, $config = [])
  {
    $clientid = '';
    $condition = '';

    $access = 1;

    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
        if ($config['params']['companyid'] == 16) { //ati
          $access = $this->othersClass->checkAccess($config['params']['user'], 3744);
        } else {
          $access = $this->othersClass->checkAccess($config['params']['user'], 23);
        }
        $clientid = $config['params']['tableid'];
        break;
      case 'SUPPLIER':
        $access = $this->othersClass->checkAccess($config['params']['user'], 33);
        $clientid = $config['params']['tableid'];
        break;

      default:
        $clientid = '';
        break;
    }

    if ($clientid != '') {
      $condition = " and c.clientid = " . $clientid . " ";
    }

    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor, case " . $access . " when 0 then 'true' else 'false' end as isallowed ";
    $qry = "select " . $select . " from " . $this->table . " as b
    left join client as c on b.clientid = c.clientid left join client as dept on dept.clientid=b.deptid
    where b.line=? " . $condition . " ";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $clientid = '';
    $condition = '';
    $access = 1;
    switch (strtoupper($config['params']['doc'])) {
      case 'CUSTOMER':
        if ($config['params']['companyid'] == 16) { //ati
          $access = $this->othersClass->checkAccess($config['params']['user'], 3744);
        } else {
          $access = $this->othersClass->checkAccess($config['params']['user'], 23);
        }
        $clientid = $config['params']['tableid'];
        break;
      case 'SUPPLIER':
        $access = $this->othersClass->checkAccess($config['params']['user'], 33);
        $clientid = $config['params']['tableid'];
        break;

      default:
        $clientid = '';
        break;
    }

    if ($clientid != '') {
      $condition = " where c.clientid = " . $clientid . " ";
    }

    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor, case " . $access . " when 0 then 'true' else 'false' end as isallowed ";
    $qry = "select " . $select . " from " . $this->table . " as b
    left join client as c on b.clientid = c.clientid  left join client as dept on dept.clientid=b.deptid
    " . $condition;

    if ($config['params']['companyid'] == 16) { //ati
      $limitview = $this->othersClass->checkAccess($config['params']['user'], 3745);
      if ($limitview) {
        $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$config['params']['adminid']]);
        $qry .= " and b.deptid=" . $deptid;
      }
    }

    $qry .= " order by b.line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      case 'client':
        $rowindex = $config['params']['index'];
        $lookupsetup = array(
          'type' => 'single',
          'title' => 'List of Customer/Supplier',
          'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
          'plottype' => 'plotgrid',
          'plotting' => array(
            'clientid' => 'clientid',
            'client' => 'client',
            'clientname' => 'clientname',
          )
        );

        $cols = array(
          array('name' => 'client', 'label' => 'Client Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
          array('name' => 'clientname', 'label' => 'Client Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;')

        );
        $qry = "select clientid, client, clientname from client 
        where client.iscustomer=1 or client.issupplier=1 and client.isinactive =0 
        order by client";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
        break;
      case 'addrtype':
        $rowindex = $config['params']['index'];
        $plotting = array('addrtype' => 'addrtype');
        $plottype = 'plotgrid';
        $title = 'List of Address Type';

        $lookupsetup = array(
          'type' => 'single',
          'title' => $title,
          'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
          'plottype' => $plottype,
          'action' => '',
          'plotting' => $plotting
        );
        // lookup columns
        $cols = [
          ['name' => 'addrtype', 'label' => 'Address Type', 'align' => 'left', 'field' => 'addrtype', 'sortable' => true, 'style' => 'font-size:16px;']
        ];


        $qry = "select '' as addrtype union all select 'Billing'  as addrtype union all select 'Shipping' as addrtype union all select 'Head Office' as addrtype union all select 'BIR 2303' as addrtype";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookuplogs($config)
  {
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Billing and Shipping Address Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];
    $doc = strtoupper("billing_add_tab");

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  private function getclient($clientid)
  {
    $qry = "select client, clientname from client where clientid = ? ";
    $res = $this->coreFunctions->opentable($qry, [$clientid]);
    return $res;
  }

  // -> print function
  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter();
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
        'default' as print,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  private function report_default_query($config)
  {
    $select = $this->selectqry();
    $select = $select . ", '' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as b
      left join client as c on b.clientid = c.clientid
      order by b.line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $data = $this->report_default_query($config);
    $str = $this->rpt_forex_masterfile_layout($data, $config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_default_header($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Client Code', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Client Name', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Address', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Billing', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Shipping', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->col('Inactive', '300', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_forex_masterfile_layout($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['client'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['clientname'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['addr'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['isbilling'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['isshipping'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->col($data[$i]['isinactive'], '300', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn


} //end class
