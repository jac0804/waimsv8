<?php

namespace App\Http\Classes\modules\warehousingentry;

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
use App\Http\Classes\builder\lookupclass;

class entryproductinquiry
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STATUS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'rrstatus';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
  public $showclosebtn = false;
  private $enrollmentlookup;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->lookupclass = new lookupclass;
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
    $tab = [$this->gridname => ['gridcolumns' => ['clientname', 'location', 'rem', 'bal']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][0]['label'] = 'Warehouse Name';
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][0]['align'] = 'text-left';

    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][1]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][1]['label'] = 'Location';

    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][2]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][2]['label'] = 'Pallet Name';

    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][3]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][3]['label'] = 'Balance';

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['incoming', 'compatible'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  private function selectqry()
  {
    $qry = " wh.clientname as clientname, location.loc as location, ifnull(pallet.name,'') as rem, round(sum(rs.bal),2) as bal  ";
    return $qry;
  }

  public function loaddata($config)
  {
    $itemid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from " . $this->table . " as rs
    left join client as wh on wh.clientid = rs.whid
    left join pallet on rs.palletid = pallet.line
    left join location on rs.locid = location.line
    where rs.itemid=? and rs.bal <> 0
    group by clientname, location.loc, pallet.name";
    return  $this->coreFunctions->opentable($qry, [$itemid]);
  }

  public function delete($config)
  {
    return [];
  }

  public function lookupsetup($config)
  {

    switch ($config['params']['lookupclass2']) {
      case 'lookupcompatible':
        return $this->compatiblelist($config);
        break;

      case 'lookupincoming':
        return $this->incominglist($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Invalid Lookup setup', 'data' => []];
        break;
    }
  }

  private function compatiblelist($config)
  {
    $itemid = $config['params']['tableid'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Compatible',
      'style' => 'width:800px;max-width:800px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'brand', 'label' => 'Brand', 'align' => 'left', 'field' => 'brand', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'model', 'label' => 'Model', 'align' => 'left', 'field' => 'model', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'classification', 'label' => 'Classification', 'align' => 'left', 'field' => 'classification', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $select = "i.line, i.itemid, c.line as cmodelid, c.brand, c.model, c.classification";
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " 
    from itemcmodels as i
    left join cmodels as c on i.cmodelid = c.line
    where i.itemid=? order by i.line";
    $data = $this->coreFunctions->opentable($qry, [$itemid]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  private function incominglist($config)
  {
    $itemid = $config['params']['tableid'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Incoming packing list',
      'style' => 'width:800px;max-width:800px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'PL Doc', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'PL Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Item Name', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'qty', 'label' => 'Quantity', 'align' => 'left', 'field' => 'qty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'pending', 'label' => 'Pending', 'align' => 'left', 'field' => 'pending', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select head.docno as docno, date(head.dateid) as dateid, i.itemname as itemname, round(stock.qty,2) as qty,
          round((stock.qty-stock.qa),2) as pending from hplhead as head
          left join hplstock as stock on head.trno = stock.trno
          left join item as i on i.itemid=stock.itemid
          left join uom on uom.itemid=i.itemid
          where i.itemid=" . $itemid . " and stock.qa<>stock.qty";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  public function save($config)
  {
    return [];
  }
} //end class
