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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class local_clearance_list_report
{
    public $modulename = 'Local Clearance List Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'start', 'end', 'street'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'street.type', 'lookup');
        data_set($col1, 'street.action', 'lookupstreet');
        data_set($col1, 'street.class', 'sbccsreadonly');
        data_set($col1, 'street.label', 'Street');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {

        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      date(now()) as end, 
    '' as client,
    '' as clientname,
    '0' as clientid,
    '' as street,
    '' as area,
      '' as dclientname,
      '" . $center . "' as center,
      '" . $dcenter[0]->dcentername . "' as dcentername,
      '" . $dcenter[0]->name . "' as centername,
      '' as prefix
      ";
        return $this->coreFunctions->opentable($paramstr);
    }


    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        return $this->reportDefault_Layout($config);
    }


    public function reportDefault_query($config)
    {
        $center = $config['params']['dataparams']['center'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $street = $config['params']['dataparams']['area'];
        $filter = "";


        if ($street != "") {
            $filter .= " and cl.area ='$street'";
        }

        $query = " select head.dateid, loccl.clearance as purpose,head.client as brgy_id, head.docno, cl.clientname, head.amount, cl.addr
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join client as cl on cl.client = head.client
        left join cntnum as cnum on cnum.trno = head.trno
        left join locclearance as  loccl on loccl.line = head.purposeid
        where cnum.doc = 'BD'  and date(head.dateid) between '$start' and '$end' $filter
        
        union all 
        
        select head.dateid, loccl.clearance as purpose,cl.client as brgy_id, head.docno, cl.clientname, head.amount, cl.addr
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join client as cl on cl.clientid = head.clientid
        left join cntnum as cnum on cnum.trno = head.trno
        left join locclearance as  loccl on loccl.line = head.purposeid
        where cnum.doc = 'BD'  and date(head.dateid) between '$start' and '$end' $filter
        order by dateid
        ";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }


    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date("F-j-Y", strtotime($config['params']['dataparams']['start']));
        $end = date("F-j-Y", strtotime($config['params']['dataparams']['end']));
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $street = $config['params']['dataparams']['area'];
        $result = $this->reportDefault_query($config);
        $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);


        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, null, null, 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LOCAL CLEARANCE LIST', null, null, false, '10px solid ', '', 'L', $font, '12', 'B', '#800000');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('DATE FROM: ' . $start . ' TO ' . $end, null, null, false, '3px solid', '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($street != '') {
            $str .= $this->reporter->col('STREET: ' . $street, null, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        } else {
            $str .= $this->reporter->col('STREET: All', null, null, false, '3px solid', '', 'L', $font, '10', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('FULLNAME', '220', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('BRGY. ID', '250', null, false, '3px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('ADDRESS', '250', null, false, '3px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('PURPOSE', '250', null, false, '3px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefault_Layout($config)
    {

        $str = '';
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filter = "";
        $layoutsize = '1000';
        $font = 'Tahoma';
        // $font = $this->companysetup->getrptfont($config['params']);
        // $font='Courier New';
        $fontsize = "11";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $result = $this->reportDefault_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);


        $totalRows = count($result);
        $currentRow = 0;
        foreach ($result as $row) {
            $currentRow++;
            $borderStyle = ($currentRow === $totalRows) ? '' : '1px dashed';

            // ITEM ROW 
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->clientname, '220', '40', false, $borderStyle, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col('', '10', '40', false, $borderStyle, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->brgy_id, '250', '40', false, $borderStyle, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col('', '10', '40', false, $borderStyle, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->addr, '250', '40', false, $borderStyle, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col('', '10', '40', false, $borderStyle, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->col($row->purpose, '250', '40', false, $borderStyle, 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#C4C0C0');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '220', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '250', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '250', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '250', null, false, '3px solid', 'T', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class
