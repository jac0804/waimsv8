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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class entrysku
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SKU';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'sku';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['itemid', 'clientid', 'sku', 'disc', 'amt'];
  public $showclosebtn = false;
  private $reporter;


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
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4876);
    $companyid = $config['params']['companyid'];
    $tableid = $config['params']['tableid'];
    $doc = $config['params']['doc'];

    switch (strtoupper($doc)) {
      case 'STOCKCARD':
         if($companyid==63){//ericco
           $this->modulename='BARCODE LIST';
         }
        $item = $this->othersClass->getitemname($tableid);
        $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;
        break;

      default:
       if($companyid==63){//ericco
         switch($doc){
          case 'SUPPLIER':
             $this->modulename='ITEM LIST';
            break;
         }}
        $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$tableid]);
        $this->modulename = $this->modulename . ' - ' . $customername;
        break;
    }

  
    switch (strtoupper($config['params']['doc'])) {
      case 'STOCKCARD':
        $tab = [$this->gridname => [ 'gridcolumns' => ['action', 'client', 'shipto', 'sku', 'amt', 'disc'] ] ];
        if($config['params']['companyid']==63){//ericco
         $tab = [$this->gridname => ['gridcolumns' => ['action', 'wh', 'sku', 'amt', 'disc','uom2','itemdesc']]]; } 
         break;

      case 'CUSTOMER':
      case 'FINANCINGPARTNER':
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'wh', 'sku', 'amt', 'disc']]];
        break;
      case 'SUPPLIER': //
       if($config['params']['companyid']==63){//ericco
         $tab = [$this->gridname => ['gridcolumns' => ['action','wh', 'sku','uom2', 'amt', 'disc','netamt']]]; 
       }
        break;
    }

    $stockbuttons = ['save', 'delete'];

    if ($companyid == 21) { // kinggeorge
      if (!$allow_update) {
        $stockbuttons = [];
      }
    }

    

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:100px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";

    $obj[0][$this->gridname]['columns'][1]['action'] = "lookupsetup";
    switch (strtoupper($config['params']['doc'])) {
      case 'STOCKCARD':
        $obj[0][$this->gridname]['columns'][1]['lookupclass'] = "lookupclient";
        $obj[0][$this->gridname]['columns'][1]['label'] = "Customer";

        if ($companyid != 22 && $companyid !=63) { //not eipi and not ericco
          $obj[0][$this->gridname]['columns'][2]['type'] = "coldel";
          $obj[0][$this->gridname]['columns'][2]['label'] = "";
        }
    
        if($config['params']['companyid']==63){//ericco
              $obj[0][$this->gridname]['columns'][1]['lookupclass'] = "lookupgroup";
              // $obj[0][$this->gridname]['columns'][5]['type'] = "lookup";
              // $obj[0][$this->gridname]['columns'][5]['lookupclass'] = "lookupstock";
              $obj[0][$this->gridname]['columns'][7]['type'] = "label";
              // $obj[0][$this->gridname]['columns'][5]['action'] = "lookupsetup";
              $obj[0][$this->gridname]['columns'][7]['label'] = "Name";
              $obj[0][$this->gridname]['columns'][1]['label'] = "Outlet";
              $obj[0][$this->gridname]['columns'][3]['label'] = "Price";
              $obj[0][$this->gridname]['columns'][4]['label'] = "Discount";
              $obj[0][$this->gridname]['columns'][5]['type'] = 'lookup';
              $obj[0][$this->gridname]['columns'][5]['lookupclass'] = 'lookupuom';
              $obj[0][$this->gridname]['columns'][5]['action'] = 'lookupsetup';
              $obj[0][$this->gridname]['columns'][5]['label'] = "Uom";
        }
        break;

      case 'CUSTOMER':
      case 'FINANCINGPARTNER':
             $obj[0][$this->gridname]['columns'][1]['lookupclass'] = "lookupstock";
             $obj[0][$this->gridname]['columns'][1]['label'] = "Item";
        break;
       case 'SUPPLIER':
              $obj[0][$this->gridname]['columns'][1]['lookupclass'] = "lookupstock";
              $obj[0][$this->gridname]['columns'][1]['label'] = "Itemname";
              $obj[0][$this->gridname]['columns'][2]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
              $obj[0][$this->gridname]['columns'][1]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
              $obj[0][$this->gridname]['columns'][5]['style'] = "width:100px;whiteSpace: normal;min-width:100px;text-align:right;";
              $obj[0][$this->gridname]['columns'][2]['label'] = "Sku";
              $obj[0][$this->gridname]['columns'][6]['type'] = "label";
              $obj[0][$this->gridname]['columns'][3]['type'] = 'lookup';
              $obj[0][$this->gridname]['columns'][3]['lookupclass'] = 'lookupuom';
              $obj[0][$this->gridname]['columns'][3]['action'] = 'lookupsetup';
              $obj[0][$this->gridname]['columns'][3]['label'] = "Uom";
        break;
    }

    if ($companyid == 22) { //EIPI     
      $obj[0][$this->gridname]['columns'][4]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][4]['label'] = "";
    }


    return $obj;
  }


  public function createtabbutton($config)
  {
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4876);
    $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
    if ($config['params']['companyid'] == 21) { // kinggeorge
      if (!$allow_update) {
        $tbuttons = array_slice($tbuttons, 2, 1);
      }
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    switch (strtoupper($config['params']['doc'])) {
      case 'STOCKCARD':
        $data['itemid'] = $config['params']['tableid'];
        $data['clientid'] = 0;
         if($config['params']['companyid']==63){//ericco
            $data['issku'] = 1;
            $data['itemdesc'] = '';
            $data['groupid'] = '';
            $data['uom2'] = ''; }
        break;

      case 'CUSTOMER':
      case 'FINANCINGPARTNER':
        $data['clientid'] = $config['params']['tableid'];
        $data['itemid'] = 0;
        break;
      case 'SUPPLIER':
        $data['clientid'] = $config['params']['tableid'];
        $data['itemid'] = 0;
        $data['issupplier'] = 1;
        $data['uom2'] = '';
        break;
    }
    $data['client'] = '';
    $data['wh'] = '';
    $data['sku'] = '';
    $data['amt'] = '0.00';
    $data['disc'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $doc=$config['params']['doc'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
         
       if($config['params']['companyid']==63){//ericco
        switch ($doc){
             case 'STOCKCARD';
              $data2['groupid'] = $data[$key]['groupid'];
              $data2['issku'] = $data[$key]['issku'];
              $data2['uom2'] = $data[$key]['uom2'];

               if ($data[$key]['line'] == 0 && $data[$key]['wh'] != '') {
              $qry = "select sku.groupid as outlet from sku where sku.itemid = '" .  $data[$key]['itemid'] . "' and sku.groupid=  '" .  $data[$key]['groupid'] . "'  and sku.issku=1 limit 1";
              $opendata = $this->coreFunctions->opentable($qry);
              $resultdata =  json_decode(json_encode($opendata), true);
              if (!empty($resultdata[0]['outlet'])) {
                        if (trim($resultdata[0]['outlet']) == trim($data[$key]['wh'])) {
                            return ['status' => false, 'msg' => ' Outlet ( ' . $resultdata[0]['outlet'] . ' )' . ' already exist.', 'data' => [$resultdata]];
                        }
                    }
                
               }
               
             break;
             case 'SUPPLIER':
              $data2['issupplier'] = $data[$key]['issupplier'];
              $data2['uom2'] = $data[$key]['uom2'];
              
               if ($data[$key]['line'] == 0 && $data[$key]['wh'] != '') {
              $qry = "select i.itemname from sku
                      left join item  as i on i.itemid=sku.itemid where sku.itemid = '" . $data[$key]['itemid'] . "' and sku.clientid=  '" . $data[$key]['clientid'] . "'  and sku.issupplier=1 limit 1";
              $opendata = $this->coreFunctions->opentable($qry);
              $resultdata =  json_decode(json_encode($opendata), true);
              if (!empty($resultdata[0]['itemname'])) {
                        if (trim($resultdata[0]['itemname']) == trim($data[$key]['wh'])) {
                            return ['status' => false, 'msg' => ' Supplier ( ' . $resultdata[0]['itemname'] . ' )' . ' already exist.', 'data' => [$resultdata]];
                        }
                    }
                
               }
             break;
        }  }


        if ($data[$key]['line'] == 0) {
          $status = $this->coreFunctions->insertGetId($this->table, $data2);

          $params = $config;
          $params['params']['doc'] = strtoupper("skuentry_tab");
          $this->logger->sbcmasterlog(
            $tableid,
            $params,
            ' CREATE - LINE: ' . $status . ''
              . ', ITEM: ' . $data[$key]['wh']
              . ', SKU: ' . $data[$key]['sku']
              . ', AMOUNT: ' . $data[$key]['amt']
              . ', DISCOUNT: ' . $data[$key]['disc']
          );
        } else {
        
          if($config['params']['companyid']==63){//ericco
             switch ($doc){
              case 'SUPPLIER':
                   if ($data[$key]['line'] != 0 && $data[$key]['wh'] != '') {
              $qry = "select i.itemname,sku.line from sku
                      left join item  as i on i.itemid=sku.itemid where sku.itemid = '" . $data[$key]['itemid'] . "' and sku.clientid=  '" . $data[$key]['clientid'] . "'  and sku.issupplier=1  limit 1";
              $opendata = $this->coreFunctions->opentable($qry);
              $resultdata =  json_decode(json_encode($opendata), true);
              if (!empty($resultdata[0]['itemname'])) {
                            if (trim($resultdata[0]['itemname']) == trim($data[$key]['wh'])) {
                                if ($data[$key]['line'] == $resultdata[0]['line']) {
                                    goto update;
                                }
                                return ['status' => false, 'msg' => ' Supplier ( ' . $resultdata[0]['itemname'] . ' )' . ' already exist.', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
                            } else {
                              goto  update;
                            }
                        } else {
                            goto update;
                        }

                      }
                      break;
            case 'STOCKCARD':

              if ($data[$key]['line'] != 0 && $data[$key]['wh'] != '') {
              $qry = "select sku.groupid as outlet,sku.line from sku where sku.itemid = '" . $data[$key]['itemid'] . "' and sku.groupid=  '" . $data[$key]['groupid'] . "'  and sku.issku=1 limit 1";
              $opendata = $this->coreFunctions->opentable($qry);
              $resultdata =  json_decode(json_encode($opendata), true);
              if (!empty($resultdata[0]['outlet'])) {
                            if (trim($resultdata[0]['outlet']) == trim($data[$key]['wh'])) {
                                if ($data[$key]['line'] == $resultdata[0]['line']) {
                                    goto update;
                                }
                                return ['status' => false, 'msg' => ' Outlet ( ' . $resultdata[0]['outlet'] . ' )' . ' already exist.', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
                            } else {
                              goto  update;
                            }
                        } else {
                            goto update;
                        }

                      }
              break;          

                
          }
        }
          
        
          update:
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
          $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['wh']);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    // var_dump($row);
    $tableid = $config['params']['tableid'];
    $doc=$config['params']['doc'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

     if($config['params']['companyid']==63){//ericco
        switch($doc){
          case 'STOCKCARD';
           $data['groupid'] = $row['groupid'];
           $data['issku'] = $row['issku'];
           $data['uom2'] = $row['uom2'];
    

            if ($row['line'] == 0 && $row['wh'] != '') {
            $qry = "select sku.groupid as outlet from sku where sku.itemid = '" . $row['itemid'] . "' and sku.groupid=  '" . $row['groupid'] . "'  and sku.issku=1 limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['outlet'])) {
                if (trim($resultdata[0]['outlet']) == trim($row['wh'])) {
                    return ['status' => false, 'msg' => ' Outlet ( ' . $resultdata[0]['outlet'] . ' )' . ' already exist.', 'data' => [$resultdata]];
                }
            }
          }

          break;
          case 'SUPPLIER':
           $data['issupplier'] = $row['issupplier'];
           $data['uom2'] = $row['uom2'];
          if ($row['line'] == 0 && $row['wh'] != '') {
            $qry = "select  i.itemname from sku
                    left join item  as i on i.itemid=sku.itemid where sku.itemid = '" . $row['itemid'] . "' and sku.clientid=  '" . $row['clientid'] . "'  and sku.issupplier=1 limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['itemname'])) {
                if (trim($resultdata[0]['itemname']) == trim($row['wh'])) {
                    return ['status' => false, 'msg' => 'Itemname ( ' . $resultdata[0]['itemname'] . ' )' . ' already exist.', 'data' => [$resultdata]];
                }
            }
          }
          break;
        }}
  
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);

        $params = $config;
        $params['params']['doc'] = strtoupper("skuentry_tab");
        $this->logger->sbcmasterlog(
          $tableid,
          $params,
          ' CREATE - LINE: ' . $line . ''
            . ', ITEM: ' . $row['wh']
            . ', SKU: ' . $row['sku']
            . ', AMOUNT: ' . $row['amt']
            . ', DISCOUNT: ' . $row['disc']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
     
      if($config['params']['companyid']==63){//ericco
           switch($doc){
            case 'SUPPLIER':
         if ($row['line'] != 0 && $row['wh'] != '') {
                $qry = "select i.itemname,sku.line from sku
                        left join item  as i on i.itemid=sku.itemid where sku.itemid = '" . $row['itemid'] . "' and sku.clientid=  '" . $row['clientid'] . "'  and sku.issupplier=1 limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($opendata), true);
                if (!empty($resultdata[0]['itemname'])) {
                    if (trim($resultdata[0]['itemname']) == trim($row['wh'])) {
                        if ($row['line'] == $resultdata[0]['line']) {
                            goto update;
                        }
                        return ['status' => false, 'msg' => 'Itemname ( ' . $resultdata[0]['itemname'] . ' )' . 'already exist.', 'data' => [$resultdata], 'rowid' => [$row['line']  . ' -- ' . $resultdata[0]['line']]];
                    } else {
                       goto update;
                    }
                } else {
                    goto update;
                }
            }
          
          break;
          case 'STOCKCARD':
            if ($row['line'] != 0 && $row['wh'] != '') {
               $qry = "select sku.groupid as outlet,sku.line from sku where sku.itemid = '" . $row['itemid'] . "' and sku.groupid=  '" . $row['groupid'] . "'  and sku.issku=1 limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($opendata), true);
                if (!empty($resultdata[0]['outlet'])) {
                    if (trim($resultdata[0]['outlet']) == trim($row['wh'])) {
                        if ($row['line'] == $resultdata[0]['line']) {
                            goto update;
                        }
                        return ['status' => false, 'msg' => 'Outlet ( ' . $resultdata[0]['outlet'] . ' )' . ' already exist.', 'data' => [$resultdata], 'rowid' => [$row['line']  . ' -- ' . $resultdata[0]['line']]];
                    } else {
                       goto update;
                    }
                } else {
                    goto update;
                }
            }
            break;
          }

      }
    
      update:
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $tableid = $config['params']['tableid'];
    $row = $config['params']['row'];
    $data = $this->loaddataperrecord($config, $row['line']);

    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $params = $config;
    $params['params']['doc'] = strtoupper("skuentry_tab");
    $this->logger->sbcmasterlog(
      $tableid,
      $params,
      ' DELETE - LINE: ' . $row['line'] . ''
        . ', ITEM: ' . $row['wh']
        . ', SKU: ' . $row['sku']
        . ', AMOUNT: ' . $row['amt']
        . ', DISCOUNT: ' . $row['disc']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($config, $line)
  {

    $tableid = $config['params']['tableid'];
    $doc=strtoupper($config['params']['doc']);
    $addfilter='';
    $addf=", item.itemname as wh";
    $grp="";
    $orderby="order by line";
    switch ($doc) {
      case 'STOCKCARD':
        $colfield = 'itemid';
          if($config['params']['companyid']==63){//ericco
           $addfilter=' and sku.issku=1';
           $addf=", sku.groupid as wh, item.itemname as itemdesc,sku.issku,sku.groupid,sku.uom2,sku.uom3,item.itemid"; }
        break;
      case 'CUSTOMER':
      case 'FINANCINGPARTNER':
        $colfield = 'clientid';
        break;
      case 'SUPPLIER':
          $colfield = 'clientid';
         if($config['params']['companyid']==63){//ericco
          $addfilter=' and sku.issupplier=1';
          $addf=", item.itemname as wh, sku.issupplier, format(sum(case when sku.disc like '%\%%'  then sku.amt - (sku.amt * cast(replace(sku.disc, '%', '') as decimal(12,2)) / 100)
                   when sku.disc <> '' and sku.disc is not null  then sku.amt - cast(sku.disc as decimal(12,2)) else sku.amt end),2) as netamt,sku.uom2 ";
          $grp=" group by  sku.line, sku.itemid, sku.clientid, sku.sku, sku.disc, client.clientname, item.itemname, sku.issupplier,sku.amt,client.addr2,sku.uom2";   
          $orderby="order by sku.line";      
         }
        break;

    }

    $qry = "select sku.line, sku.itemid, sku.clientid, sku.sku, format(sku.amt,2) as amt, sku.disc, 
    client.clientname as client, '' as bgcolor $addf
    from " . $this->table . " 
    left join client on client.clientid = sku.clientid
    left join item on item.itemid = sku.itemid
    where sku." . $colfield . " = " . $tableid . "  $addfilter and sku.line=?
    $grp $orderby";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $addfilter='';
    $addf=", item.itemname as wh";
    $grp="";
    $orderby="order by line";
    
    $doc=strtoupper($config['params']['doc']);
    switch ($doc) {
      case 'STOCKCARD':
        $colfield = 'itemid';
        if($config['params']['companyid']==63){//ericco
        $addfilter=' and sku.issku=1';
        $addf=", sku.groupid as wh, item.itemname as itemdesc,sku.issku,sku.groupid,sku.uom2,sku.uom3,item.itemid";}
        break;
      case 'CUSTOMER':
      case 'FINANCINGPARTNER':
        $colfield = 'clientid';
        break;
    case 'SUPPLIER':
          $colfield = 'clientid';
         if($config['params']['companyid']==63){//ericco
          $addfilter=' and sku.issupplier=1';
          $addf=", item.itemname as wh, sku.issupplier, format(sum(case when sku.disc like '%\%%'  then sku.amt - (sku.amt * cast(replace(sku.disc, '%', '') as decimal(12,2)) / 100)
                   when sku.disc <> '' and sku.disc is not null  then sku.amt - cast(sku.disc as decimal(12,2)) else sku.amt end),2) as netamt,sku.uom2  ";
          $grp=" group by  sku.line, sku.itemid, sku.clientid, sku.sku, sku.disc, client.clientname, item.itemname, sku.issupplier,sku.amt,client.addr2,sku.uom2";   
          $orderby="order by sku.line";  
         }
       break;
    }

    $qry = "select sku.line, sku.itemid, sku.clientid, sku.sku, format(sku.amt,2) as amt, sku.disc, 
    client.clientname as client,client.addr2 as shipto, '' as bgcolor $addf
    from " . $this->table . " 
    left join client on client.clientid = sku.clientid
    left join item on item.itemid = sku.itemid
    where sku." . $colfield . " = " . $tableid . "  $addfilter  $grp $orderby";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookupclient':
        return $this->lookupcustomer($config);
        break;
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;
           
     case 'lookupgroup':
        return $this->lookupgroup($config);
        break;

      case 'lookupstock':
        return $this->lookupstock($config);  // to be follow
        break;
       case 'lookupuom':
        return $this->lookupuom($config);
        break;  

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

    public function lookupuom($config)
  {
    $itemid = $config['params']['row']['itemid'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Unit of Measurement',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['uom2' => 'uom','uom3'=>'uom']
    );
    $cols = [['name' => 'uom', 'label' => 'Unit of Measurement', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;']];
    $qry = "select uom from uom where itemid=? and isinactive = 0";
    $data = $this->coreFunctions->opentable($qry, [$itemid]);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }



  public function lookuplogs($config)
  {
    $main_doc = $config['params']['doc'];
    $doc = strtoupper("skuentry_tab");
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    if ($main_doc == "CUSTOMER" || $main_doc == "FINANCINGPARTNER" || $main_doc == "SUPPLIER" ) {
      $qry = "
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from " . $this->tablelogs . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno2 = '" . $trno . "' OR log.trno = '" . $trno . "'
      union all
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from  " . $this->tablelogs_del . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno2 = '" . $trno . "' OR log.trno = '" . $trno . "'";

      $qry = $qry . " order by dateid desc";
    } else {
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
    }
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, $qry];
  }

  public function lookupcustomer($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Customer',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['clientid' => 'clientid', 'client' => 'clientname']
    );

    $cols = array(
      array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select client, clientname, clientid from client
            where iscustomer = 1
            ";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupstock($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Item',
      'style' => 'width:900px;max-width:900px;'
    );

    $wh='wh';
    if($config['params']['companyid']==63){//ericco
    if($config['params']['doc']=='CUSTOMER'){
       $wh='itemdesc';
    }}
   
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['itemid' => 'itemid', $wh => 'itemname']
    );

    $cols = array(
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select barcode, itemname, itemid from item";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }


   public function lookupgroup($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];


     $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Group',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
          'plottype' => 'plotgrid',
          'plotting' => ['groupid' => 'groupid','wh' => 'groupid']
        );

    // lookup columns
    $cols = [ ['name' => 'groupid', 'label' => 'Group', 'align' => 'left', 'field' => 'groupid', 'sortable' => true, 'style' => 'font-size:16px;']];
    $qry = "select '' as groupid union all select distinct groupid from client where groupid<>'' order by groupid";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  } //end function

  // -> Print Function
  public function reportsetup($config)
  {
    return [];
  }


  public function createreportfilter()
  {
    return [];
  }

  public function reportparamsdata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    return [];
  }
} //end class
