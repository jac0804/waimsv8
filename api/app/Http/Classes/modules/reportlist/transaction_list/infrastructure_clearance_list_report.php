<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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
use DateTime;

class infrastructure_clearance_list_report
{
    public $modulename = 'Infrastructure Clearance List Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1200'];

    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['start','end','infratype'];
            $col1 = $this->fieldClass->create($fields);
            data_set($col1,'start.type','date');
            data_set($col1,'end.type','date');
            data_set($col1, 'infratype.lookupclass', 'lookupinfratype'); 
            $fields = ['print'];
            $col2 = $this->fieldClass->create($fields);
        return array('col1'=>$col1, 'col2'=> $col2);
    }

    public function paramsdata($config)//data parameters; the default values of the input fields
    { // 'names' or 'alias'
        $center = $config['params']['center'];
        return $this->coreFunctions->opentable( "select 
            'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end,
            '' as infratype
        ");
    }

    public function getloaddata($config)
    {
        return [];
    }

      public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status'=>true, 'msg'=>'Msg works', 'report'=>$str,'params'=>$this->reportParams];
    }

    public function reportplotting($config)// Type of Report (radio option) case connection
    {
        $data=$this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)  // Query for Detailed Report
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $infra= $config['params']['dataparams']['infratype'];
        $filter = "";

        if ($infra!= ''){
        $filter .= " and cl.infratype = '$infra' ";
        }

        $query = '';
        $query = "select head.dateid, cl.client as brgyid, cl.clientname, cl.infratype, head.address
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join client as cl on cl.client = head.client
            left join cntnum as cnum on cnum.trno = head.trno
            where cl.isinfra = 1 and cnum.doc = 'BI' and date(head.dateid) between '$start' and '$end' $filter    
            union all
                select head.dateid, cl.client as brgyid, cl.clientname, cl.infratype, head.address
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join client as cl on cl.clientid = head.clientid
            left join cntnum as cnum on cnum.trno = head.trno
            where cl.isinfra = 1 and cnum.doc = 'BI' and date(head.dateid) between '$start' and '$end' $filter    
            order by dateid;";
        return $this->coreFunctions->openTable($query);  
    }

    public function DefaultHeader($config, $result)
    {
        $center     = $config['params']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $printDate = date('m/d/Y g:i a');
        $str = ''; // required
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "14";
        $border = "2px solid ";
        $infratype = $config['params']['dataparams']['infratype'];
        
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        if ($infratype == '') {
            $infratype = 'ALL';
        }   

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name),'500' , null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Print Date : '.$printDate, '500', null, false, $border, '', 'R', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('INFRASTRUCTURE CLEARANCE LIST','500' , null, false, $border, '', '', $font, '15', 'B', '#8B0000', '');
        $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, '', 'R', $font, '12', '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE FROM ' . $start . ' TO ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('INFRA TYPE: ' . $infratype,'250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('','1000', null, false, $border, 'B', '', $font, $fontsize, 'B', '', ''); 
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('FULL NAME', '240', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('INFRA CODE', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('ADDRESS', '240', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('INFRA TYPE', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config, $result)
    {
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "13";
        $border = "1px dotted ";

        if (empty($result)) {
          return $this->othersClass->emptydata($config);
        }
        
        $str = ''; // required
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->DefaultHeader($config, $result);

        foreach($result as $key => $data)  // client loop
        {

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->clientname, '240', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '', 'T');
            $str .= $this->reporter->col('', '10',null, false, $border, 'B','C', 'Times New Roman', '14', 'B','','');
            $str .= $this->reporter->col($data->brgyid, '140', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('', '10',null, false, $border, 'B','C', 'Times New Roman', '14', 'B','','');
            $str .= $this->reporter->col($data->address, '240', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col('', '10',null, false, $border, 'B','C', 'Times New Roman', '14', 'B','','');
            $str .= $this->reporter->col($data->infratype, '200', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '', ''); 
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= '<br/><br/>';
        $border = "2px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '850', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', ''); 
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '850', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', ''); 
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        

        $str .= $this->reporter->endreport();
        $str .= $this->reporter->endreport();
        return $str;
    }

}