<?php

namespace App\Http\Classes\modules\actionlisting;

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

class approvallist
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'APPROVAL LIST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'lahead';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['terms', 'days'];
  public $showclosebtn = false;
  public $showfilteroption = true;
  public $showfilter = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2235
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'clientname', 'docno', 'dateid', 'project']
      ]
    ];

    $stockbuttons = ['approvedtrans'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][3]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
    $obj[0][$this->gridname]['columns'][3]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][4]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][4]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][1]['label'] = 'Module';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }


  public function add($config)
  {
    $data = [];
    return $data;
  }

  private function selectqry()
  {
    $qry = "select head.trno,left(head.dateid,10) as dateid,head.docno,p.name as project,'Purchase Request' as clientname,head.doc,'approvepr' as lookupclass, 'customform' as action from prhead as head
left join projectmasterfile as p on p.line = head.projectid
left join prstock as stock on stock.trno = head.trno
 where head.doc='RQ' and head.lockdate is not null and (stock.rrqty-stock.rqty)<>0 and stock.status =0 group by  head.trno,head.dateid,head.docno,p.name,head.doc
 union all
 select head.trno,left(head.dateid,10) as dateid,head.docno,p.name as project,'Job Request' as clientname,head.doc,'approvejr' as lookupclass, 'customform' as action from prhead as head
left join projectmasterfile as p on p.line = head.projectid
left join prstock as stock on stock.trno = head.trno
 where head.doc='JR' and head.lockdate is not null and (stock.rrqty-stock.rqty)<>0 and stock.status =0 group by  head.trno,head.dateid,head.docno,p.name,head.doc
union all
select head.trno,left(head.dateid,10) as dateid,head.docno,p.name as project,'Budget Request' as clientname,head.doc,'approvebr' as lookupclass, 'customform' as action from brhead as head left join projectmasterfile as p on p.line = head.projectid left join brstock as stock on stock.trno = head.trno
 where head.doc='BR' and head.lockdate is not null and stock.amount=0 and stock.status =0 group by  head.trno,head.dateid,head.docno,p.name,head.doc order by dateid";

    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $qry = $this->selectqry();
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
} //end class
