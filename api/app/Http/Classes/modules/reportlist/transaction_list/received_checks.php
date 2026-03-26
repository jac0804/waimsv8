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


class received_checks
{
    public $modulename = 'Received Checks';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
        $fields = ['radioprint', 'start', 'end', 'dcentername', 'dclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');

        $fields = ['radioreporttype', 'print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $qry = "select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                '0' as reporttype,
                '" . $defaultcenter[0]['center'] . "' as center,
                '" . $defaultcenter[0]['centername'] . "' as centername,
                '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                '' as dclientname,
                '' as client,
                '' as clientname,
                '' as clientid";

        return $this->coreFunctions->opentable($qry);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }


    public function default_query($filters)
    {
        $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
        $client = $filters['params']['dataparams']['dclientname'];
        $clientid = $filters['params']['dataparams']['clientid'];
        $center = $filters['params']['dataparams']['center'];
        $reporttype  = $filters['params']['dataparams']['reporttype'];
        $filter = "";

        if ($center != '') {
            $filter .= " and transnum.center='" . $center . "' ";
        }

        if ($client != '') {
            $filter .= " and client.clientid= $clientid  ";
        }


        $query = "select head.trno,head.docno,head.client,head.clientname,
                                date(detail.checkdate) as checkdate,detail.checkno,detail.amount,
                                date(head.dateid) as dateid,detail.bank
                        from rchead as head
                        left join rcdetail as detail on detail.trno=head.trno
                        left join client on client.client=head.client
                        left join transnum on transnum.trno=head.trno
                        where date(head.dateid) between '$start' and '$end' $filter 
                        union all
                        select head.trno,head.docno,head.client,head.clientname,
                                date(detail.checkdate) as checkdate,detail.checkno,detail.amount,
                                date(head.dateid) as dateid,detail.bank
                        from hrchead as head
                        left join hrcdetail as detail on detail.trno=head.trno
                        left join client on client.client=head.client
                        left join transnum on transnum.trno=head.trno
                        where date(head.dateid) between '$start' and '$end'  $filter 
                        order by clientname,checkdate";

        $data = $this->coreFunctions->opentable($query);
        return $data;
    }

    public function reportplotting($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        $result = $this->default_query($config);

        switch ($reporttype) {
            case '0': // SUMMARIZED
                $reportdata =  $this->RC_SUMMARY($config, $result);
                break;
            case '1': // DETAILED
                $reportdata =  $this->RC_DETAILED($config, $result);
                break;
        }

        return $reportdata;
    }


    //start -> summary
    public function RC_header_summary($config, $layoutsize)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client    = $config['params']['dataparams']['dclientname'];
        $dcenter = $config['params']['dataparams']['dcentername'];

        if ($client == '') {
            $client = 'ALL';
        }

        if ($dcenter == '') {
            $dcenter = 'ALL';
        }
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Received Checks Report - Summarized', 1000, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Branch: ' . $dcenter, 500, null, false, $border, '', '', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer: ' . $client, 1000, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function RC_summ_header_table($config, $layoutsize)
    {
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";
        $str = "";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', 120, null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Docno', 140, null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Customer Name', 260, null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Bank', 120, null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Check #', 100, null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Check Date', 120, null, false, $border, 'TB', 'CT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', 140, null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function RC_SUMMARY($config, $result)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $count = 46;
        $page = 45;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = 1000;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->RC_header_summary($config, $layoutsize);
        $str .= $this->RC_summ_header_table($config, $layoutsize);

        $i = 0;
        $docno = "";
        $supplier = "";
        $total = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $total += $data->amount;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, 120, null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->docno, 140, null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, 260, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->bank, 120, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkno, 100, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkdate, 120, null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amount, 2), 140, null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->RC_header_summary($config, $layoutsize);
                    $str .= $this->RC_summ_header_table($config, $layoutsize);
                    $page = $page + $count;
                } //end if
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        // $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        // $str .= $this->reporter->col("<div style='height:10px;'></div>", '200', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        // $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col("<div style='height:10px;'></div>", 860, null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col("<div style='height:10px;'></div>", 140, null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        // $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('Grand Total:', 860, null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($total, 2), 140, null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
        // $str .= $this->reporter->col('', '280', null, false, $border, '', 'CT', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    //end ->summary


    //start -> detailed
    public function RC_header_detailed($config, $layoutsize)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client    = $config['params']['dataparams']['dclientname'];
        $dcenter = $config['params']['dataparams']['dcentername'];

        if ($client == '') {
            $client = 'ALL';
        }

        if ($dcenter == '') {
            $dcenter = 'ALL';
        }


        $str = '';
        $layoutsize = 800;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Received Checks Report - Detailed', 800, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, 400, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Branch: ' . $dcenter, 400, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer: ' . $client, 800, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function RC_DETAILED($config, $result)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $count = 15;
        $page = 14;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = 800;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->RC_header_detailed($config, $layoutsize);


        $str .= $this->reporter->printline();
        $i = 0;
        $docno = "";
        $supplier = "";
        $total = 0;
        $grandtotal = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                if ($docno != "" && $docno != $data->docno) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total:', 470, null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(number_format($total, 2), 330, null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', 800, null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                if ($docno == "" || $docno != $data->docno) {
                    $docno = $data->docno;
                    $total = 0;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, 400, null, false, $border, '', '', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, 400, null, false, $border, '', '', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<b>' . 'Customer: ' . '</b>' . $data->clientname, 800, null, false, $border, '', '', $font, $fontsize, '', '', '2px');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Bank', 180, null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Check Date', 140, null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Check #', 150, null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->col('Amount', 330, null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->bank, 180, null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkdate, 140, null, false, '10px solid ', '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkno, 150, null, false, '10px solid ', '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->amount, 2), 330, null, false, '10px solid ', '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();

                if ($docno == $data->docno) {
                    $total += $data->amount;
                    $grandtotal += $data->amount;
                }
                $str .= $this->reporter->endtable();

                if ($i == (count((array)$result) - 1)) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total: ', 470, null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->col(number_format($total, 2), 330, null, false, $font, 'T', 'RT', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', 800, null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $i++;
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->RC_header_detailed($config, $layoutsize);
                    $page = $page + $count;
                } //end if
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grand Total: ', 470, null, false, '1px dotted', '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($grandtotal, 2), 330, null, false, $font, '', 'RT', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    //end

}//end class