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

class entryassummary
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SUMMARY LIST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_sosummary';
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
    $tab = [$this->gridname => ['gridcolumns' => ['feestype','amt']]];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:80px;whiteSpace: normal;min-width:280px;"; //action
    $obj[0][$this->gridname]['columns'][0]['type'] = "label"; //action
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:180px;whiteSpace: normal;min-width:180px;"; //action
    $obj[0][$this->gridname]['columns'][1]['type'] = "label"; //action
    
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }
  
  private  function selectqry()
  {
    return  "select stock.trno,stock.line,stock.amt,stock.feesid,stock.schemeid,f.feescode,f.feestype,s.scheme, 
    '' as bgcolor,
    '' as errcolor ";
  }

  public function loaddata($config){  
    $tableid = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    $htable = "en_glsummary";

    switch ($doc) {
      case 'ER':
          $head = "en_sjhead";
          $subject = "en_sjsubject";
          $credentials = "en_sjcredentials";
          $otherfees = "en_sjotherfees";
          $assessment = "en_sjsummary";
          $hhead = "glhead";
          $hsubject = "glsubject";
          $hcredentials = "glcredentials";
          $hotherfees = "glotherfees";
          $hassessment = "glsummary";
          break;
      case 'EA': case 'EI':
        $head = "en_sohead";
        $subject = "en_sosubject";
        $credentials = "en_socredentials";
        $otherfees = "en_sootherfees";
        $assessment = "en_sosummary";
        $hhead = "en_glhead";
        $hsubject = "en_glsubject";
        $hcredentials = "en_glcredentials";
        $hotherfees = "en_glotherfees";
        $hassessment = "en_glsummary";
          break;
      case 'ED':
        $head = "en_adhead";
        $subject = "en_adsubject";
        $credentials = "en_adcredentials";
        $otherfees = "en_adotherfees";
        $assessment = "en_adsummary";
        $hhead = "glhead";
        $hsubject = "glsubject";
        $hcredentials = "glcredentials";
        $hotherfees = "glotherfees";
        $hassessment = "glsummary";
      break;
  }

  $mop = $this->coreFunctions->datareader("select modeofpayment as value from (select modeofpayment from ".$head."  as mop where trno=? union all select modeofpayment from ".$hhead."  as mop where trno=?) as mop",[$tableid,$tableid]);
  $interestpercent = $this->coreFunctions->datareader("select deductpercent  as value from en_modeofpayment  as mop where code=?",[$mop]);

  if ($interestpercent >0){
    $qryinterest = "union all 
    select 1 as grp,'Add Interest:' as feestype,sum(amt)*(".$interestpercent."/100) from
      (select  grp,s.feestype,s.amt from
      (select 3 as grp,'Total Fees' as feestype,isamt as amt from ".$otherfees." as a  where a.trno=".$tableid."
      union all select 3 as grp,'Total Fees' as feestype,isamt from ".$hotherfees." as a  where a.trno=".$tableid." ) as s

      union all select  grp,feestype,sum(s.amt)*-1 as amt from
      (select 2 as grp,'Less Credentials' as feestype,camt as amt from ".$credentials." as a  where a.trno=".$tableid."
      union all select 2 as grp,'Less Credentials' as feestype,camt from ".$hcredentials." as a  where a.trno=".$tableid.") as s
      group by s.feestype,grp

      union all select  grp,s.feestype,s.amt from
      (select 0 as grp,f.feestype,a.amt as amt from ".$assessment." as a  left join en_fees as f on f.line=a.feesid  where a.trno=".$tableid."
      union all select 0 as grp,f.feestype,a.amt from ".$hassessment." as a  left join en_fees as f on f.line=a.feesid  where a.trno=".$tableid.") as s 
      where s.feestype not in ('OTHERS','LAB')) as x
      group by x.feestype,grp

      union all
      select 4 as grp,'Total Balance:' as feestype,sum(amt) from
      (select  grp,s.feestype,s.amt+(s.amt*(".$interestpercent."/100)) as amt from
      (select 3 as grp,'Total Fees' as feestype,isamt as amt from ".$otherfees." as a  where a.trno=".$tableid."
      union all select 3 as grp,'Total Fees' as feestype,isamt from ".$hotherfees." as a  where a.trno=".$tableid." ) as s

      union all select  grp,feestype,(sum(s.amt)+(sum(s.amt)*(".$interestpercent."/100)))*-1 as amt from
      (select 2 as grp,'Less Credentials' as feestype,camt as amt from ".$credentials." as a  where a.trno=".$tableid."
      union all select 2 as grp,'Less Credentials' as feestype,camt from ".$hcredentials." as a  where a.trno=".$tableid.") as s
      group by s.feestype,grp

      union all select  grp,s.feestype,s.amt+((s.amt)*(".$interestpercent."/100)) as amt from
      (select 0 as grp,f.feestype,a.amt as amt from ".$assessment." as a  left join en_fees as f on f.line=a.feesid  where a.trno=".$tableid."
      union all select 0 as grp,f.feestype,a.amt from ".$hassessment." as a  left join en_fees as f on f.line=a.feesid  where a.trno=".$tableid.") as s  
      where s.feestype not in ('OTHERS','LAB')
      
      
      union all
      select  grp,feestype,sum(s.amt)*-1 from
      (select '4' as grp,'Total Balance' as feestype,disc as amt from ".$head." as a  where a.trno=".$tableid."
      union all select '4' as grp,'Total Balance' as feestype,disc from ".$hhead." as a  where a.trno=".$tableid.") as s
      group by s.feestype,grp
      
      union all
      select  grp,s.feestype,sum(s.amt) as amt
      from (select '4' as grp,'Total Balance' as feestype,a.amt as amt from en_sohead as h left join en_glbooks as a  on h.curriculumtrno=a.trno  where h.trno=".$tableid."
      union all
      select '4' as grp,'Total Balance' as feestype,a.amt as amt from en_glhead as h left join en_glbooks as a  on h.curriculumtrno=a.trno  where h.trno=".$tableid."
      ) as s
      group by s.feestype,grp
      ) as x group by x.feestype,grp";
  }else{
    $qryinterest = "union all 
      select 4 as grp,'Total Balance:' as feestype,sum(amt) from
      (select  grp,s.feestype,s.amt as amt from
      (select 3 as grp,'Total Fees' as feestype,isamt as amt from ".$otherfees." as a  where a.trno=".$tableid."
      union all select 3 as grp,'Total Fees' as feestype,isamt from ".$hotherfees." as a  where a.trno=".$tableid." ) as s

      union all select  grp,feestype,sum(s.amt)*-1 as amt from
      (select 2 as grp,'Less Credentials' as feestype,camt as amt from ".$credentials." as a  where a.trno=".$tableid."
      union all select 2 as grp,'Less Credentials' as feestype,camt from ".$hcredentials." as a  where a.trno=".$tableid.") as s
      group by s.feestype,grp

      union all select  grp,s.feestype,s.amt as amt from
      (select 0 as grp,f.feestype,a.amt as amt from ".$assessment." as a  left join en_fees as f on f.line=a.feesid  where a.trno=".$tableid."
      union all select 0 as grp,f.feestype,a.amt from ".$hassessment." as a  left join en_fees as f on f.line=a.feesid  where a.trno=".$tableid.") as s  
      where s.feestype not in ('OTHERS','LAB')
      
      
      union all
      select  grp,feestype,sum(s.amt)*-1 from
      (select '4' as grp,'Total Balance' as feestype,disc as amt from ".$head." as a  where a.trno=".$tableid."
      union all select '4' as grp,'Total Balance disc' as feestype,disc from ".$hhead." as a  where a.trno=".$tableid.") as s
      group by s.feestype,grp
      
      union all
      select  grp,s.feestype,sum(s.amt) as amt
      from (select '4' as grp,'Total Balance' as feestype,a.amt as amt from en_sohead as h left join en_glbooks as a  on h.curriculumtrno=a.trno  where h.trno=".$tableid."
      union all
      select '4' as grp,'Total Balance' as feestype,a.amt as amt from en_glhead as h left join en_glbooks as a  on h.curriculumtrno=a.trno  where h.trno=".$tableid."
      ) as s
      group by s.feestype,grp
      ) as x group by x.feestype,grp";
  }

  $qry = "select grp as feescode,feestype,round(sum(amt),2) as amt
    from (select grp,feestype,amt from
    (select grp,feestype,sum(amt) as amt from (
    select '3' as grp,'Other Fees' as feestype,isamt as amt from ".$otherfees." as a where a.trno=?
    union all select '3' as grp,'Other Fees' as feestype,isamt from ".$hotherfees." as a where a.trno=?) as s 
    group by s.feestype,grp

    union all
    select  grp,s.feestype,sum(s.amt) as amt
    from (select '0' as grp,'Books' as feestype,a.amt as amt from en_sohead as h left join en_glbooks as a  on h.curriculumtrno=a.trno  where h.trno=".$tableid."
    union all
    select '0' as grp,'Books' as feestype,a.amt as amt from en_glhead as h left join en_glbooks as a  on h.curriculumtrno=a.trno  where h.trno=".$tableid.") as s
    group by s.feestype,grp

    union all select  grp,s.feestype,sum(s.amt) as amt
    from (select '0' as grp,f.feestype,a.amt as amt from ".$assessment." as a  left join en_fees as f on f.line=a.feesid  where a.trno=?
    union all select '0' as grp,f.feestype,a.amt from ".$hassessment." as a  left join en_fees as f on f.line=a.feesid  where a.trno=?) as s  
    where s.feestype not in ('OTHERS','LAB') group by s.feestype,grp 

    union all
    select  grp,feestype,sum(s.amt)*-1 from
    (select '2' as grp,'Less Credentials' as feestype,camt as amt from ".$credentials." as a  where a.trno=?
    union all select '2' as grp,'Less Credentials' as feestype,camt from ".$hcredentials." as a  where a.trno=?) as s
    group by s.feestype,grp
    
    
    union all
    select  grp,feestype,sum(s.amt)*-1 from
    (select '3' as grp,'Less Discount' as feestype,disc as amt from ".$head." as a  where a.trno=".$tableid."
    union all select '3' as grp,'Less Discount' as feestype,disc from ".$hhead." as a  where a.trno=".$tableid.") as s
    group by s.feestype,grp


    ".$qryinterest.") as y
    ) as z group by feestype,grp order by grp ";

    $data = $this->coreFunctions->opentable($qry, [$tableid, $tableid, $tableid, $tableid, $tableid, $tableid]);
    return $data;
  }









} //end class

