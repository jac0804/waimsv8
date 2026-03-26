<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use Illuminate\Support\Facades\Storage;

class pendingexplanation
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'EXPLANATION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $linkemail;
    public $tablelogs = 'hrisnum_log';
    public $style = 'width:90%;max-width:90%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    public $tablenum = 'hrisnum';
    public $head = 'disciplinary';
    public $hhead = 'hdisciplinary';
    public $tablelogs_del = 'del_hrisnum_log';

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->linkemail = new linkemail;
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
        $row = $config['params']['row'];
        $approver = $row['approver'];

        // var_dump($approver);

         $cols = ['action', 'code', 'articlename', 'section', 'description','reason' ];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $cols]];

    
        if($approver != ''){
            $stockbuttons = ['jumpmodule'];
        }else{
            $stockbuttons = ['approve'];
        }
     
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        
         $obj[0][$this->gridname]['columns'][$code]['type'] = 'label';
         $obj[0][$this->gridname]['columns'][$articlename]['type'] = 'label';
         $obj[0][$this->gridname]['columns'][$section]['type'] = 'label';
         $obj[0][$this->gridname]['columns'][$description]['type'] = 'label';
         $obj[0][$this->gridname]['columns'][$reason]['label'] = 'Explanation';
        //  $obj[0][$this->gridname]['columns'][$reason]['type'] = 'textarea';
         $obj[0][$this->gridname]['columns'][$reason]['style'] = 'width:400px;whiteSpace: normal;min-width:400px; max-width:400px;';
         $obj[0][$this->gridname]['columns'][$code]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
         $obj[0][$this->gridname]['columns'][$description]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
         $obj[0][$this->gridname]['columns'][$section]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';

         if($approver != ''){
             $obj[0][$this->gridname]['columns'][0]['btns']['jumpmodule']['label'] = 'View';
             $obj[0][$this->gridname]['columns'][$reason]['type'] = 'label';
         }else{
             $obj[0][$this->gridname]['columns'][0]['btns']['approve']['label'] = 'Save';
         }

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {

        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {
        return array('col1' => []);
    }

    public function paramsdata($config)
    {
        return [];
    }

    public function data($config)
    {
        return [];
    }



    public function loaddata($config)
    {
        // $row = $config['params']['row'];
        // $approver = $row['approver'];
        $url = "/module/hris/";
        $adminid = $config['params']['adminid'];
       
            $qry = "select head.docno, m.modulename as doc,head.trno,m.sbcpendingapp,'$url' as url,
                   'DBTODO' as tabtype,'module' as moduletype, '' as bgcolor,
                    ir.docno as irno, chead.code,chead.description as articlename,
                    cdetail.description,cdetail.section,head.explanation as reason,head.empid,inhead.fempid as reporterid
                   from disciplinary as head
                   left join pendingapp as p on p.trno=head.trno  and p.doc='HD'
                   left join moduleapproval as m on m.modulename=p.doc
                   left join hrisnum as num on num.trno=head.trno
                   left join hincidenthead as ir on head.refx=ir.trno
                   left join codehead as chead on chead.artid=head.artid
                   left join codedetail as cdetail on head.sectionno=cdetail.line and chead.artid=cdetail.artid
                   left join hincidenthead as inhead on inhead.trno=head.refx
                   where p.clientid=" . $adminid . " ";
      

        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    
    public function updateapp($config, $status)
    {   
     
        $row = $config['params']['row'];
        $trno = $row['trno'];
        $inputexp=$row['reason'];
        $reporterid=$row['reporterid'];
        $label = '';
        //save explanation 
        if($status == 'A'){
             
             if($inputexp != ''){ //pag hindi empty yung reason
                 $expl = $this->coreFunctions->sbcupdate('disciplinary', ['explanation' => $inputexp], ['trno' => $trno]);
                 $label=' saved.';
                 if($expl){ //pag nasave na sa disciplinary update sa pending app change id ng nag report
                 $this->coreFunctions->sbcupdate('pendingapp', ['clientid' => $reporterid,'approver'=>'NEWEXPLANATION'], ['trno' => $trno]);
              } 
             }else{
                return ['status' => false, 'msg' => 'Please input your explanation.'];
             }
             
        }

           return ['status' => true, 'msg' => 'Successfully ' . $label . ' ', 'data' => [], 'reloadsbclist' => true, 'action' => 'gapplications', 'deleterow' => true];
  }

} //end class