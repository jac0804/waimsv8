<?php

namespace App\Http\Classes\modules\reportlist\pos_reports;

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


class transaction_pos_report
{
    public $modulename = 'POS Report';
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
        $companyid = $config['params']['companyid'];
        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);


        $fields = ['start', 'end'];
        // for date filter
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'start.required', true);
        data_set($col2, 'end.required', true);
        // for signatory
        $fields = ['prepared', 'received', 'approved'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'prepared.label', 'Prepared By');
        data_set($col3, 'received.label', 'Received By');
        data_set($col3, 'approved.label', 'Approved By');

        // for filter
        $fields = ['dcentername'];
        $col4 = $this->fieldClass->create($fields);
        // Branch
        data_set($col4, 'dcentername.lookupclass', 'getmultibranch');

        $fields = ['print'];
        $col5 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4, 'col5' => $col5);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      date(now()) as end, 
      '' as center,
      '' as dcentername,
      '' as centername,
      '' as prepared,
      '' as received,
    '' as approved,
      '' as prefix,
      '0' as reporttype,
      '0' as clientid,
      '' as customer,
      '' as pos_station,
      '0' as groupid,
      '' as stock_groupname,
      '' as stationname,
      '' as clientname,
    '0' as brandid,
      '' as brandname
      ";
        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
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
        $reporttype = $config['params']['dataparams']['reporttype'];
        return $this->transaction_pos_layout($config);
    }


    public function transaction_pos_qry($config)
    {
        $center = $config['params']['dataparams']['center'];
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $groupid = $config['params']['dataparams']['groupid'];
        $groupName = $config['params']['dataparams']['stock_groupname'];
        $station = $config['params']['dataparams']['pos_station'];
        $brandid = $config['params']['dataparams']['brandid'];


        $filter = '';


        if ($brandid != "0") {
            $filter .= " and item.brand = $brandid";
        }

        if ($center != '') {
            $filter .= " and cntnum.center = '$center'";
        }

        $query = "select h.docno, head.dateid, client.clientname, wh.client as wh,
round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ext, stock.rem
from lahead as head
left join lastock as stock on stock.trno = head.trno
left join client on client.client = head.client
left join client as wh on wh.client = head.wh
left join head as h on h.webtrno = head.trno and h.docno = stock.ref
left join stockinfo as si on si.trno = stock.trno and si.line = stock.line
left join cntnum on cntnum.trno = head.trno
where h.bref in ('SI', 'RT', 'V') and date(head.dateid) between '$start' and '$end' $filter
group by h.docno, head.dateid, client.clientname, wh.client, stock.rem

union all

select h.docno, head.dateid, client.clientname, wh.client as wh,
round(sum((stock.ext - si.lessvat - si.sramt - si.soloamt - si.pwdamt) * if(cntnum.doc = 'CM', -1, 1)),2) as ext, stock.rem
from glhead as head
left join glstock as stock on stock.trno = head.trno
left join client on client.clientid = head.clientid
left join client as wh on wh.clientid = head.whid
left join head as h on h.webtrno = head.trno and h.docno = stock.ref
left join hstockinfo as si on si.trno = stock.trno and si.line = stock.line
left join cntnum on cntnum.trno = head.trno
where h.bref in ('SI', 'RT', 'V') and date(head.dateid) between '$start' and '$end' $filter
group by h.docno, head.dateid, client.clientname, wh.client, stock.rem
order by dateid";
        $data = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $data;
    }

    public function transaction_pos_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start     = $config['params']['dataparams']['start'];
        $end     = $config['params']['dataparams']['end'];
        $dcentername     = $config['params']['dataparams']['dcentername'];
        $station = $config['params']['dataparams']['pos_station'];
        // $data = $this->summarized_salesreport_query($config);

        $qry = "select code,name,address,tel, tin from center where code = '" . $center . "'";
        $qry2 = "select bn.comptel from branchstation as bn left join client on client.clientid = bn.clientid
         where bn.comptel <> 0 and client.center = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $headerdata2 = $this->coreFunctions->opentable($qry2);

        $gnrtdate = date('m-d-Y H:i:s A');
        // $system = '';
        $srno = '';
        $machineid = '';
        $postrmnl = '';
        // $username = '';

        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "9";
        $border = "1px dashed ";

        if ($config['params']['dataparams']['dcentername'] == '') {
            $dcentername = '-';
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '3px solid', '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->address, null, null, false, '3px solid', '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata2[0]->comptel, null, '20', false, '3px solid', '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TRANSACTION LIST - POINT-OF-SALE', null, '', false, $border, '', 'C', 'Times New Roman', '12', 'B', 'blue', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Covered: <b>' . $start . '</b> to <b>' . $end . '</b>', '', null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document#', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Date', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Name', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('W.House', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Prepared by', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Approved by', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Amount', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('Remarks', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    public function transaction_pos_layout($config)
    {
        $str = '';
        $layoutsize = '1000';
        // $font = $this->companysetup->getrptfont($config['params']);
        $font = "Century Gothic";
        $fontsize = "10";
        $border = "1px solid ";
        $count = 35;
        $page = 35;

        $str .= $this->reporter->beginreport();
        $str .= $this->transaction_pos_header($config);
        $data = $this->transaction_pos_qry($config);


        $grandtotal = 0;

        for ($i = 0; $i < count($data); $i++) {

            // report details
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data[$i]['docno'], '125', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col(date('m/d/Y', strtotime($data[$i]['dateid'])), '125', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['clientname'], '125', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['wh'], '125', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col('', '125', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['ext'] != 0 ? number_format($data[$i]['ext'], 2) : '-', '125', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->col($data[$i]['rem'] != 0 ? number_format($data[$i]['rem'], 2) : '----------------', '125', null, false, $border, '', 'LB', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $grandtotal += $data[$i]['ext'];
        }

        //grandtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total', '125', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col($grandtotal != 0 ? number_format($grandtotal, 2) : '-', '125', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '', '', 0, '', 0, 0, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared by:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Received by:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Approved by:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('', '25', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($config['params']['dataparams']["prepared"], '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($config['params']['dataparams']["approved"], '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($config['params']['dataparams']["received"], '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '62.5', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}
