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

class viewcredentials
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CREDENTIALS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_socredentials';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'credentialid', 'acnoid', 'amt', 'camt', 'feesid', 'percentdisc'];
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
    $tab = [$this->gridname => ['gridcolumns' => ['credentialcode','credentials','particulars','amt','percentdisc','camt','feescode']]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;"; //action
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true; //subjectcode
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true; //subjectname
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true; //units
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true; //lecture

    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

 

  private function loaddataperrecord($trno,$line){
    $select = $this->selectqry();
    $qry = $select." FROM en_socredentials as soc left join transnum as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid   where soc.trno=? and soc.line=? 
      union all 
      ".$select." FROM  en_glcredentials as soc left join transnum as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid   where soc.trno=? and soc.line=?  ";
    $data = $this->coreFunctions->opentable($qry,[$trno,$line,$trno,$line]);    
    return $data;
  }

  private  function selectqry()
  {
    return  "select soc.trno, soc.line, soc.credentialid,soc.amt,soc.particulars,soc.percentdisc,soc.acnoid,soc.feesid,coa.acno,coa.acnoname,c.credentials,c.credentialcode,s.subjectcode,s.subjectname,soc.camt,f.feescode as feescode,soc.scheme,f.feestype,
    '' as bgcolor,
    '' as errcolor ";
  }

  public function loaddata($config){  
    $tableid = $config['params']['tableid'];
    $doc = $config['params']['doc'];
  
    switch ($doc) {
      case 'ER':
          $credentials = "en_sjcredentials";
          $hcredentials = "glcredentials";
          break;
      case 'EA': case 'EI':
        $credentials = "en_socredentials";
        $hcredentials = "en_glcredentials";
          break;
      case 'ED':
        $credentials = "en_adcredentials";
        $hcredentials = "glcredentials";
      break;
  }


    $select = $this->selectqry();
    $qry =  $select." FROM  ".$credentials." as soc left join transnum as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid   where soc.trno=? 
      union all 
      ".$select." FROM  ".$hcredentials." as soc left join transnum as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid   where soc.trno=? ";
    $data = $this->coreFunctions->opentable($qry,[$tableid,$tableid]);
    return $data;
  }









} //end class

