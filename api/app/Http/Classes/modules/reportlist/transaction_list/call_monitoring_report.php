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

class call_monitoring_report
{
    public $modulename = 'Call Monitoring Report';
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
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'calltype', 'source', 'sourcename', 'dagentname', 'industry'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
            ['label' => 'excel', 'value' => 'excel', 'color' => 'green']
        ]);
        data_set($col1, 'dclientname.label', 'Customer Name');
        data_set($col1, 'dclientname.lookupclass', 'lookupcustomer');
        data_set($col1, 'dagentname.label', 'Sales Person');
        data_set($col1, 'sourcename.required', false);
        data_set($col1, 'industry.type', 'lookup');
        data_set($col1, 'industry.class', 'csindustry sbccsreadonly');
        data_set($col1, 'industry.lookupclass', 'lookupindustry');
        data_set($col1, 'industry.action', 'lookuprandom');
        data_set($col1, 'industry.readonly', true);
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
    '' as clientid,
      '' as dclientname,
      '' as calltype,
      '' as source,
      '' as sourcename,
      '' as dagentname,
      '' as agent, 
      '' as agentid,
      '' as agentname,
      '' as industry,
      '' as industryid,
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
        $center      = $config['params']['dataparams']['center'];
        $start       = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end         = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filter      = "";   // for ophead/hophead
        $filter2     = "";   // for ophead/hophead source
        $filterQS    = "";   // for qshead/hqshead
        $customer    = $config['params']['dataparams']['clientname'];
        $calltype    = $config['params']['dataparams']['calltype'];
        $source      = $config['params']['dataparams']['source'];
        $sourcename  = $config['params']['dataparams']['sourcename'];
        $salesperson = $config['params']['dataparams']['agentname'];
        $industry    = $config['params']['dataparams']['industry'];
        $industryid  = $config['params']['dataparams']['industryid'];

        if (!empty($customer)) {
            $filter   .= " and client.clientname = '" . $customer . "' ";
            $filterQS .= " and client.clientname = '" . $customer . "' ";
        }

        if (!empty($calltype)) {
            $filter   .= " and calls.calltype = '" . $calltype . "' ";
            $filterQS .= " and calls.calltype = '" . $calltype . "' ";
        }

        if (!empty($source)) {
            $filter2 .= " and op.source = '" . $source . "' ";
        }

        if (!empty($sourcename)) {
            if ($source == 'Exhibit') {
                $filter2 .= " and ex.title = '" . $sourcename . "' ";
            } elseif ($source == 'Seminar') {
                $filter2 .= " and sem.title = '" . $sourcename . "' ";
            } elseif ($source == 'Others') {
                $filter2 .= " and source.description = '" . $sourcename . "' ";
            } elseif ($source == 'Principal Leads') {
                $filter2 .= " and projectx.name = '" . $sourcename . "' ";
            }
        }

        if (!empty($salesperson)) {
            $filter   .= " and agent.clientname = '" . $salesperson . "' ";
            $filterQS .= " and agent.clientname = '" . $salesperson . "' ";
        }

        if (!empty($industry)) {
            $filter   .= " and client.industryid = '" . $industryid . "' ";  // ophead/hophead
            $filterQS .= " and qs.industryid = '" . $industryid . "' ";    // qshead/hqshead 
        }


        $filter2_active = !empty($source) || !empty($sourcename);

        $qsUnion = $filter2_active ? "" : "
        union

        select qs.dateid, qs.docno, agent.clientname as agent, client.clientname,
        qs.industry, calls.contact, calls.calltype, '' as sourcename, calls.rem
        from qshead as qs
        left join client on client.client = qs.client
        left join qscalllogs as calls on calls.trno = qs.trno
        left join client as agent on agent.client = qs.agent
        where date(calls.dateid) between '$start' and '$end' $filterQS

        union all

        select qs.dateid, qs.docno, agent.clientname as agent, client.clientname,
        qs.industry, calls.contact, calls.calltype, '' as sourcename, calls.rem
        from hqshead as qs
        left join client on client.client = qs.client
        left join qscalllogs as calls on calls.trno = qs.trno
        left join client as agent on agent.client = qs.agent
        where date(calls.dateid) between '$start' and '$end' $filterQS
     ";

        $query = "select op.dateid, op.docno, agent.clientname as agent, client.clientname, op.industry, calls.contact, calls.calltype, case
                when op.source = 'Exhibit' then ex.title
                when op.source = 'Seminar' then sem.title
                when op.source = 'Others' then ifnull(source.description, ' ')
                when op.source = 'Principal Leads' then projectx.name
                else ' '
              end as sourcename, calls.rem
              from ophead as op
              left join client on client.client = op.client
              left join calllogs as calls on calls.trno = op.trno
              left join client as agent on agent.client = op.agent
              left join source as source on source.line = op.sourceid
              left join seminar as sem on op.sourceid = sem.line
              left join exhibit as ex on ex.line = op.sourceid
              left join projectmasterfile as projectx on projectx.line = op.sourceid
              where date(calls.dateid) between '$start' and '$end' $filter $filter2

              union

              select op.dateid, op.docno, agent.clientname as agent, client.clientname, op.industry, calls.contact, calls.calltype, case
                when op.source = 'Exhibit' then ex.title
                when op.source = 'Seminar' then sem.title
                when op.source = 'Others' then ifnull(source.description, ' ')
                when op.source = 'Principal Leads' then projectx.name
                else ' '
              end as sourcename, calls.rem
              from hophead as op
              left join client on client.client = op.client
              left join calllogs as calls on calls.trno = op.trno
              left join client as agent on agent.client = op.agent
              left join source as source on source.line = op.sourceid
              left join seminar as sem on op.sourceid = sem.line
              left join exhibit as ex on ex.line = op.sourceid
              left join projectmasterfile as projectx on projectx.line = op.sourceid
              where date(calls.dateid) between '$start' and '$end' $filter $filter2

              $qsUnion
              order by dateid, docno
             ";
        return $this->coreFunctions->opentable($query);
    }


    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $result = $this->reportDefault_query($config);
        $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
        $str = '';
        $layoutsize = '1200';
        $font = "Tahoma";
        $fontsize = "11";
        $border = "1px solid ";
        $customer    = $config['params']['dataparams']['clientname'];
        $calltype    = $config['params']['dataparams']['calltype'];
        $source      = $config['params']['dataparams']['source'];
        $sourcename  = $config['params']['dataparams']['sourcename'];
        $salesperson = $config['params']['dataparams']['agentname'];
        $industry    = $config['params']['dataparams']['industry'];




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
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, null, null, 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CALL MONITORING REPORT', null, null, false, '10px solid ', '', 'C', $font, '15', 'B', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer Name: ' . (!empty($customer) ? $customer : 'All'), '200', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Call Type: ' . (!empty($calltype) ? $calltype : 'All'), '200', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Source: ' . (!empty($source) ? $source : 'All'), '200', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Source Desc: ' . (!empty($sourcename) ? $sourcename : 'All'), '200', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Sales Person: ' . (!empty($salesperson) ? $salesperson : 'All'), '200', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Industry: ' . (!empty($industry) ? $industry : 'All'), '200', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '80', null, false, '2px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('SALES PERSON', '170', null, false, '2px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER NAME', '175', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('INDUSTRY', '125', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('CONTACT PERSON', '175', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('CALL TYPE', '125', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('SOURCE DESCRIPTION', '175', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('NOTES', '175', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
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
        $layoutsize = '1200';
        $font = 'Tahoma';
        $fontsize = "11";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $result = $this->reportDefault_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        $limitPerPage = 30;
        $rowCount = 0;


        foreach ($result as $row) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(date("m-d-Y", strtotime($row->dateid)), '80', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->agent, '170', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->clientname, '175', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->industry, '125', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->contact, '175', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->calltype, '125', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->sourcename, '175', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->rem, '175', null, false, '1px solid', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, '20', false, '1px solid', 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class
