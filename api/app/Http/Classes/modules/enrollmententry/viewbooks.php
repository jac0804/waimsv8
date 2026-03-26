<?php

namespace App\Http\Classes\modules\enrollmententry;

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
use App\Http\Classes\lookup\enrollmentlookup;

class viewbooks
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'BOOKS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_glbooks';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['year','term','barcode','itemname','uom','isqty','isamt','disc','ext'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->enrollmentlookup = new enrollmentlookup;
  }

  public function getAttrib(){
    $attrib = array('load'=>0
                    );
    return $attrib;
}

  public function createTab($config)
  {
    $doc = $config['params']['doc'];
    switch ($doc) {
      case 'EI':
        $tab = [$this->gridname => ['gridcolumns' => ['barcode','description','uom','isqty','isamt','disc','ext']]];
        break;
      default:
        $tab = [$this->gridname => ['gridcolumns' => ['year','term','barcode','description','uom','isqty','isamt','disc','ext']]];
        break;
    }
    

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    switch ($doc) {
      case 'EI':
        $tab = [$this->gridname => ['gridcolumns' => ['barcode','description','uom','isqty','isamt','disc','ext']]];

        $obj[0][$this->gridname]['columns'][0]['type'] = "label";
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:120px;"; //action
        $obj[0][$this->gridname]['columns'][1]['type'] = "label";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:40px;whiteSpace: normal;min-width:230px;"; //action
        $obj[0][$this->gridname]['columns'][2]['type'] = "label";
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
        $obj[0][$this->gridname]['columns'][3]['type'] = "label";
        $obj[0][$this->gridname]['columns'][3]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
        $obj[0][$this->gridname]['columns'][4]['type'] = "label";
        $obj[0][$this->gridname]['columns'][4]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
        $obj[0][$this->gridname]['columns'][5]['type'] = "label";
        $obj[0][$this->gridname]['columns'][5]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
        break;
      default:
          $obj[0][$this->gridname]['columns'][0]['type'] = "label";
          $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:80px;"; //action
          $obj[0][$this->gridname]['columns'][1]['type'] = "label";
          $obj[0][$this->gridname]['columns'][1]['style'] = "width:40px;whiteSpace: normal;min-width:120px;"; //action
          $obj[0][$this->gridname]['columns'][2]['type'] = "label";
          $obj[0][$this->gridname]['columns'][2]['style'] = "width:40px;whiteSpace: normal;min-width:120px;"; //action
          $obj[0][$this->gridname]['columns'][3]['type'] = "label";
          $obj[0][$this->gridname]['columns'][3]['style'] = "width:40px;whiteSpace: normal;min-width:230px;"; //action
          $obj[0][$this->gridname]['columns'][4]['type'] = "label";
          $obj[0][$this->gridname]['columns'][4]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
          $obj[0][$this->gridname]['columns'][5]['type'] = "label";
          $obj[0][$this->gridname]['columns'][5]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
          $obj[0][$this->gridname]['columns'][6]['type'] = "label";
          $obj[0][$this->gridname]['columns'][6]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
          $obj[0][$this->gridname]['columns'][7]['type'] = "label";
          $obj[0][$this->gridname]['columns'][7]['style'] = "width:40px;whiteSpace: normal;min-width:100px;"; //action
        break;
    }
   

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function loaddata($config){  
    $doc = $config['params']['doc'];
    $tableid = $config['params']['tableid'];

    switch ($doc) {
      case 'EI':
        $yr = $this->coreFunctions->datareader("select yr as value from en_glhead where doc='EI' and trno=? limit 1", [$tableid]);
        $qry =  " select item.barcode,item.itemname as description,item.uom,item.amt as isamt,item.disc,books.ext from en_glhead as head 
        left join  en_glyear as y on y.trno=head.curriculumtrno
        left join en_glbooks as books on books.trno=head.curriculumtrno and y.line=books.cline left join item on item.itemid=books.itemid
        where head.trno=? and y.year=?  ";

        $data = $this->coreFunctions->opentable($qry,[$tableid,$yr]);
        break;
      default:
          $qry =  " select cs.trno,cy.year,sem.term,s.barcode,s.itemname as description,cs.isqty,cs.isamt,cs.disc,cs.ext,cs.uom
          FROM en_glbooks as cs left join item as s on s.itemid=cs.itemid
          left join en_glyear as cy on cy.trno=cs.trno and cy.line=cs.cline left join en_term as sem on sem.line=cy.semid where cs.trno=? order by cy.year,sem.term,s.itemname";
          $data = $this->coreFunctions->opentable($qry,[$tableid]);
      break;
    }


    return $data;
  }




























} //end class
