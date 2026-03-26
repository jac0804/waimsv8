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

class deposit_slips
{
    public $modulename = 'Deposit Slip Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
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
        $fields = ['radioprint', 'start', 'end', 'dcentername'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dcentername.required', true);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['radioreporttype'];
        $col2 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS

        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        return $this->coreFunctions->opentable("select 
            'default' as print,
            adddate(left(now(),10),-360) as start,
            left(now(),10) as end,
            '0' as reporttype,
            '" . $defaultcenter[0]['center'] . "' as center,
            '" . $defaultcenter[0]['centername'] . "' as centername,
            '" . $defaultcenter[0]['dcentername'] . "' as dcentername");
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

    public function reportplotting($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];

        switch ($reporttype) {
            case '0': // SUMMARIZED
                $result = $this->reportDefaultLayout_SUMMARIZED($config);
                break;
            case '1': // DETAILED
                $result = $this->reportDefaultLayout_DETAILED($config);
                break;
        }
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case '0': // SUMMARIZED
                $query = $this->default_QUERY_SUMMARIZED($config);
                break;
            case '1': // DETAILED
                $query = $this->default_QUERY_DETAILED($config);
                break;
        }


        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY_DETAILED($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $fcenter    = $config['params']['dataparams']['center'];

        $filter = "";

        if ($fcenter != "") {
            $filter .= " and num.center = '$fcenter'";
        }

        $query = "select head.docno,date(head.dateid) as dateid,head.yourref,
                    head.ourref,head.amount as hamount,head.rem,coa.acnoname,
                    ce.trno,ce.docno as cedoc,ce.clientname,date(ce.dateid) as cedateid,
                    date(ce.checkdate) as checkdate,ce.checkinfo,ce.amount as ceamount,ce.rem as cerem
                    from dxhead as head
                left join transnum as num on num.dstrno=head.trno
                left join hcehead as ce on ce.trno=num.trno
                left join coa on coa.acnoid=head.bank 
                where head.doc='DX' and date(head.dateid) between '$start' and '$end' $filter 
                group by head.docno,head.dateid,head.yourref,
                    head.ourref,head.amount,head.rem,coa.acnoname,
                    ce.trno,ce.docno,ce.clientname,ce.dateid,
                    ce.checkdate,ce.checkinfo,ce.amount,ce.rem

                union all

                select head.docno,date(head.dateid) as dateid,head.yourref,
                    head.ourref,head.amount as hamount,head.rem,coa.acnoname,
                    ce.trno,ce.docno as cedoc,ce.clientname,date(ce.dateid) as cedateid,
                    date(ce.checkdate) as checkdate,ce.checkinfo,ce.amount as ceamount,ce.rem as cerem

                    from hdxhead as head
                left join transnum as num on num.dstrno=head.trno
                left join hcehead as ce on ce.trno=num.trno
                left join coa on coa.acnoid=head.bank
                 where head.doc='DX' and date(head.dateid) between '$start' and '$end' $filter 
                   group by head.docno,head.dateid,head.yourref,
                    head.ourref,head.amount,head.rem,coa.acnoname,
                    ce.trno,ce.docno,ce.clientname,ce.dateid,
                    ce.checkdate,ce.checkinfo,ce.amount,ce.rem
                    order by docno";
        return $query;
    }

    public function default_QUERY_SUMMARIZED($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $fcenter    = $config['params']['dataparams']['center'];

        $filter = "";
        if ($fcenter != "") {
            $filter .= " and num.center = '$fcenter'";
        }

        $query = "select docno, dateid, acnoname, yourref,ourref,rem,amount
                from (
                select head.docno,date(head.dateid) as dateid,coa.acnoname,head.yourref,head.ourref,head.rem,head.amount
                from dxhead as head
                left join coa on coa.acnoid=head.bank
                left join transnum  as num on num.trno=head.trno
                where head.doc='dx' and date(head.dateid) between '$start' and '$end' $filter 
                union all
                select head.docno,date(head.dateid) as dateid,coa.acnoname,head.yourref,head.ourref,head.rem,head.amount
                from hdxhead as head
                left join coa on coa.acnoid=head.bank
                left join transnum  as num on num.trno=head.trno
                where head.doc='dx' and date(head.dateid) between '$start' and '$end' $filter 
                order by dateid,docno) as dx 
                order by docno";

        return $query;
    }

    public function detailed_header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $str = '';
        $layoutsize = '1200';
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
        $str .= $this->reporter->col('Deposit Slip Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_DETAILED($config)
    {
        $result = $this->reportDefault($config);
        $count = 26;
        $page = 25;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '1200';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $fontsizes = "13";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->detailed_header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);
        $docno = '';
        $total = 0;
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                if ($docno == '' || $docno != $data->docno) {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->docno, '1000', null, false, $border, 'T', 'L', $font, $fontsizes, 'B', '', '');
                    $docno = $data->docno;
                    $str .= $this->reporter->endrow();
                }
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                // $str .= $this->reporter->col($data->docno, '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->acnoname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->yourref, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ourref, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->cedoc, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->cedateid, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkdate, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkinfo, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->clientname, '190', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->cerem, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ceamount, '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $total += $data->ceamount;
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->detailed_header_DEFAULT($config, $layoutsize);
                    $page = $page + $count;
                } //end if
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        // $str .= $this->reporter->col('', '80', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('Grand Total: ', '190', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, '1px dotted', 'T', '', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($total, 2), '80', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Notes: ', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col($data->rem, '700', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->endreport();

        return $str;
    }


    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);
        $count = 8;
        $page = 7;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
        $str .= $this->summarized_header_table($config, $layoutsize);
        $total = 0;

        if (!empty($result)) {
            foreach ($result as $key => $data) {

                $str .= $this->reporter->addline();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
                $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
                $str .= $this->reporter->col($data->acnoname, '340', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
                $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
                $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
                $str .= $this->reporter->col($data->rem, '150', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
                $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');

                $total += $data->amount;
                $str .= $this->reporter->endrow($layoutsize);
                $str .= $this->reporter->endtable();

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
                    $str .= $this->summarized_header_table($config, $layoutsize);
                    $page = $page + $count;
                } //end if
            }
        }

        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('', '340', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col('Grand Total:', '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function summarized_header_table($config, $layoutsize)
    {
        $str = "";
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Bank', '340', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Yourref', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Ourref', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Rem', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function summarized_header_DEFAULT($config, $layoutsize)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
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
        $str .= $this->reporter->col('Deposit Slip Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('Date', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        // $str .= $this->reporter->col('Docno', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Bank', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Yourref', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Ourref', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Document#', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Checkdate', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Check Info', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Name', '190', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '80', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class