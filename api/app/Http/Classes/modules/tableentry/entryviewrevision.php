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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\customersupport;

class entryviewrevision
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LIST OF VERSIONS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'cntnum';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [];
  public $showclosebtn = true;

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
      'load' => 857
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'docno', 'dateid']
      ]
    ];

    $stockbuttons = ['jumpmodule'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true;
    $obj[0]['params']['trno'] = $config['params']['tableid'];
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }





  private function selectqry()
  {
    $qry = "";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $url = $this->checkdoc('so');
    $qry = "select head.trno,head.doc,'' as bgcolor,'" . $url . "' as url,'module' as moduletype,head.docno,head.dateid
        from hqthead as head where head.doc ='QT' and head.trno in
        (select ltrno from transnum where trno=?)
        union all
        select head.trno,head.doc,'' as bgcolor,'" . $url . "' as url,'module' as moduletype,head.docno,head.dateid
        from hqthead as head left join transnum as num on num.trno=head.trno where head.doc ='QT' and num.ltrno<>0 and num.ltrno in
        (select ltrno from transnum where trno=?)
        union all
        select head.trno,head.doc,'' as bgcolor,'" . $url . "' as url,'module' as moduletype,head.docno,head.dateid
        from qthead as head left join transnum as num on num.trno=head.trno where head.doc ='QT'  and num.ltrno =?
        union all
        select head.trno,head.doc,'' as bgcolor,'" . $url . "' as url,'module' as moduletype,head.docno,head.dateid
        from hqthead as head left join transnum as num on num.trno=head.trno where head.doc ='QT'  and num.ltrno =?
        union all
        select head.trno,head.doc,'' as bgcolor,'" . $url . "' as url,'module' as moduletype,head.docno,head.dateid
        from qthead as head where head.trno = ? order by trno";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno, $trno, $trno, $trno]);
    return $data;
  } //end function

  public function checkdoc($doc)
  {
    $url = '';
    switch (strtolower($doc)) {
      case 'so':
      case 'sj':
      case 'cm':
      case 'qt':
        $url = "/module/sales/";
        break;
      case 'ar':
      case 'cr':
        $url = "/module/receivable/";
        break;
      case 'dm':
      case 'rr':
        $url = "/module/purchase/";
        break;
      case 'ap':
      case 'cv':
      case 'pv':
        $url = "/module/payable/";
        break;
      case 'ds':
      case 'gc':
      case 'gd':
      case 'gj':
        $url = "/module/accounting/";
        break;
    }
    return $url;
  }
} //end class
