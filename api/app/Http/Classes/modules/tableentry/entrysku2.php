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

class entrysku2
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SUPPLIER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'sku';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['itemid', 'clientid', 'sku','uom2', 'disc', 'amt','issupplier'];
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
    $tableid = $config['params']['tableid'];
    $item = $this->othersClass->getitemname($tableid);
    $this->modulename = $this->modulename . ' ~ ' . $item[0]->barcode . ' ~ ' . $item[0]->itemname;
    $column = ['action', 'client', 'wh', 'sku','uom2', 'amt', 'disc','netamt'];
    $tab = [$this->gridname => ['gridcolumns' => $column]]; 
    $stockbuttons = ['save', 'delete'];

    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$client]['style'] = "width:100px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$wh]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$sku]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$amt]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$disc]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$netamt]['style'] = "width:100px;whiteSpace: normal;min-width:100px; text-align:right;";
    

    $obj[0][$this->gridname]['columns'][$client]['action'] = "lookupsetup";
   
    $obj[0][$this->gridname]['columns'][$client]['lookupclass'] = "lookupclient";
    $obj[0][$this->gridname]['columns'][$client]['label'] = "Code";

    $obj[0][$this->gridname]['columns'][$wh]['label'] = "Supplier";
    $obj[0][$this->gridname]['columns'][$wh]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$netamt]['label'] = "Net Price";
    $obj[0][$this->gridname]['columns'][$netamt]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$sku]['label'] = "Sku";
    $obj[0][$this->gridname]['columns'][$disc]['label'] = "Discount";
    $obj[0][$this->gridname]['columns'][$amt]['label'] = "Price";
    $obj[0][$this->gridname]['columns'][$uom2]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$uom2]['lookupclass'] = 'lookupuom';
    $obj[0][$this->gridname]['columns'][$uom2]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$uom2]['label'] = "Uom";
       
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['itemid'] = $config['params']['tableid'];
    $data['clientid'] = 0;
    $data['issupplier'] = 1;
    $data['client'] = '';
    $data['wh'] = '';
    $data['sku'] = '';
    $data['uom2'] = '';
    $data['amt'] = '0.00';
    $data['disc'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        
         if ($data[$key]['line'] == 0 && $data[$key]['wh'] != '') {
              $qry = "select cl.clientname from sku
                      left join client  as cl on cl.clientid=sku.clientid where sku.clientid = '" . $data[$key]['clientid'] . "' and sku.itemid=  '" . $data[$key]['itemid'] . "'  and sku.issupplier=1 limit 1";
              $opendata = $this->coreFunctions->opentable($qry);
              $resultdata =  json_decode(json_encode($opendata), true);
              if (!empty($resultdata[0]['clientname'])) {
                        if (trim($resultdata[0]['clientname']) == trim($data[$key]['wh'])) {
                            return ['status' => false, 'msg' => ' Supplier ( ' . $resultdata[0]['clientname'] . ' )' . ' already exist.', 'data' => [$resultdata]];
                        }
                    }
                
        }

         
        if ($data[$key]['line'] == 0) {
          $status = $this->coreFunctions->insertGetId($this->table, $data2);

          $params = $config;
          $params['params']['doc'] = strtoupper("skuentry_tab");
          $this->logger->sbcmasterlog(
            $tableid,
            $params,
            ' CREATE - LINE: ' . $status . ''
              . ', SUPPLIER: ' . $data[$key]['wh']
              . ', SKU: ' . $data[$key]['sku']
              . ', AMOUNT: ' . $data[$key]['amt']
              . ', UOM: ' . $data[$key]['uom2']
              . ', DISCOUNT: ' . $data[$key]['disc']
          );
        } else {

            if ($data[$key]['line'] != 0 && $data[$key]['wh'] != '') {
              $qry = "select cl.clientname,sku.line from sku
                      left join client  as cl on cl.clientid=sku.clientid where sku.clientid = '" . $data[$key]['clientid'] . "' and sku.itemid=  '" . $data[$key]['itemid'] . "'  and sku.issupplier=1  limit 1";
              $opendata = $this->coreFunctions->opentable($qry);
              $resultdata =  json_decode(json_encode($opendata), true);
              if (!empty($resultdata[0]['clientname'])) {
                            if (trim($resultdata[0]['clientname']) == trim($data[$key]['wh'])) {
                                if ($data[$key]['line'] == $resultdata[0]['line']) {
                                    goto update;
                                }
                                return ['status' => false, 'msg' => ' Supplier ( ' . $resultdata[0]['clientname'] . ' )' . ' already exist.', 'data' => [$resultdata], 'rowid' => [$data[$key]['line']  . ' -- ' . $resultdata[0]['line']]];
                            } else {
                                update:
                                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                                $data2['editby'] = $config['params']['user'];
                                $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                                $this->logger->sbcmasterlog($data[$key]['line'], $config, ' UPDATE - ' . $data[$key]['wh']);
                            }
                        } else {
                            goto update;
                        }
                
        }
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
    $tableid = $config['params']['tableid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

     if ($row['line'] == 0 && $row['wh'] != '') {
            $qry = "select cl.clientname from sku
                      left join client  as cl on cl.clientid=sku.clientid where sku.clientid = '" . $row['clientid'] . "' and sku.itemid=  '" . $row['itemid'] . "' and sku.issupplier=1  limit 1";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            if (!empty($resultdata[0]['clientname'])) {
                if (trim($resultdata[0]['clientname']) == trim($row['wh'])) {
                    return ['status' => false, 'msg' => 'Supplier ( ' . $resultdata[0]['clientname'] . ' )' . ' already exist.', 'data' => [$resultdata]];
                }
            }
        }
  

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
            . ', SUPPLIER: ' . $row['wh']
            . ', SKU: ' . $row['sku']
            . ', AMOUNT: ' . $row['amt']
            . ', UOM: ' . $row['uom2']
            . ', DISCOUNT: ' . $row['disc']
        );
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
       
       if ($row['line'] != 0 && $row['wh'] != '') {
                $qry = "select cl.clientname,sku.line from sku
                      left join client  as cl on cl.clientid=sku.clientid where sku.clientid = '" . $row['clientid'] . "'  and sku.itemid=  '" . $row['itemid'] . "' and sku.issupplier=1  limit 1";
                $opendata = $this->coreFunctions->opentable($qry);
                $resultdata =  json_decode(json_encode($opendata), true);
                if (!empty($resultdata[0]['clientname'])) {
                    if (trim($resultdata[0]['clientname']) == trim($row['wh'])) {
                        if ($row['line'] == $resultdata[0]['line']) {
                            goto update;
                        }
                        return ['status' => false, 'msg' => 'Supplier ( ' . $resultdata[0]['clientname'] . ' )' . ' already exist.', 'data' => [$resultdata], 'rowid' => [$row['line']  . ' -- ' . $resultdata[0]['line']]];
                    } else {
                        update:
                        $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                        $data2['editby'] = $config['params']['user'];

                        $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $row['line']]);
                        $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $row['wh']);
                    }
                } else  {
                    goto update;
                }
            }


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
        . ', SUPPLIER: ' . $row['wh']
        . ', SKU: ' . $row['sku']
        . ', AMOUNT: ' . $row['amt']
        . ', DISCOUNT: ' . $row['disc']
    );
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($config, $line)
  {

    $tableid = $config['params']['tableid'];
    $colfield = 'itemid';
          
    $qry = "select sku.line, sku.itemid, sku.clientid, sku.sku, format(sum(sku.amt),2) as amt,  sku.disc,  
         client.clientname as wh, '' as bgcolor,client.client,sku.uom2,
         format(sum(case when sku.disc like '%\%%'  then sku.amt - (sku.amt * cast(replace(sku.disc, '%', '') as decimal(12,2)) / 100)
         when sku.disc <> '' and sku.disc is not null  then sku.amt - cast(sku.disc as decimal(12,2)) else sku.amt end),2) as netamt,sku.issupplier
    from " . $this->table . " 
    left join client on client.clientid = sku.clientid
    left join item on item.itemid = sku.itemid
    where sku." . $colfield . " = " . $tableid . "  and sku.issupplier=1  and sku.line=?
    group by sku.line,  sku.itemid,     sku.clientid, sku.sku, client.clientname,sku.disc,client.client,sku.issupplier,sku.uom2
    order by sku.line";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $colfield = 'itemid';

    $qry = "select sku.line, sku.itemid, sku.clientid, sku.sku, format(sum(sku.amt),2) as amt,  sku.disc, 
         client.clientname as wh, '' as bgcolor ,client.client,sku.uom2,
         format(sum(case when sku.disc like '%\%%'  then sku.amt - (sku.amt * cast(replace(sku.disc, '%', '') as decimal(12,2)) / 100)
         when sku.disc <> '' and sku.disc is not null  then sku.amt - cast(sku.disc as decimal(12,2)) else sku.amt end),2) as netamt,sku.issupplier
    from " . $this->table . " 
    left join client on client.clientid = sku.clientid
    left join item on item.itemid = sku.itemid
    where sku." . $colfield . " = " . $tableid . "  and sku.issupplier=1  
    group by sku.line,  sku.itemid,     sku.clientid, sku.sku, client.clientname,sku.disc,client.client,sku.issupplier,sku.uom2
    order by sku.line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'lookupclient':
        return $this->lookupsupplier($config);
        break;
      case 'lookuplogs':
        return $this->lookuplogs($config);
      break;
      case 'lookupuom':
        return $this->lookupuom($config);
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
      'plotting' => ['uom2' => 'uom']
    );
    $cols = [['name' => 'uom', 'label' => 'Unit of Measurement', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;']];
    $qry = "select uom from uom where itemid=? and isinactive = 0";
    $data = $this->coreFunctions->opentable($qry, [$itemid]);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  }

  public function lookuplogs($config)
  {
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
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, $qry];
  }

  public function lookupsupplier($config)
  {
    $rowindex = $config['params']['index'];
    $lookupclass2 = $config['params']['lookupclass2'];

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Supplier',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => ['clientid' => 'clientid', 'wh' => 'clientname', 'client'=>'client']
    );

    $cols = array(
      array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select client, clientname, clientid from client
            where issupplier = 1 ";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }


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
