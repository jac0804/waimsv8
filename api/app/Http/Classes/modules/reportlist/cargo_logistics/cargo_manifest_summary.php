<?php

namespace App\Http\Classes\modules\reportlist\cargo_logistics;

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

class cargo_manifest_summary
{
    public $modulename = 'Cargo Manifest Summary';
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
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'start', 'end', 'dwhfrom', 'dwhto'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS

        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,'' as dwhfrom, '' as whfrom, '' as whfromname,
        '' as dwhto, '' as whto, '' as whtoname";

        return $this->coreFunctions->opentable($paramstr);
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
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }


    public function default_QUERY($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $filter = "";

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $whfrom = $config['params']['dataparams']['dwhfrom'];
        $whto = $config['params']['dataparams']['dwhto'];

        if ($whfrom != "") {
            $from = $config['params']['dataparams']['whfrom'];
            $filter .= " and whfrom.client = '$from' ";
        }
        if ($whto != "") {
            $to = $config['params']['dataparams']['whto'];
            $filter .= " and whto.client = '$to' ";
        }

        $query = "select head.trno,head.docno,date(head.dateid) as dateid,head.ourref,
                        whto.client as whto,whto.clientname as whtoname,
                        whfrom.client as whfrom,whfrom.clientname as whfromname,info.plateno,
                        info.vessel,info.unit
                from lahead as head
                left join lastock as stock on stock.trno=head.trno
                left join cntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join client as whfrom on whfrom.clientid=info.whfromid
                where head.doc='LL' and date(head.dateid) between '$start' and '$end' $filter
                group by head.trno,head.docno,head.dateid,head.ourref,whto.client,whto.clientname,
                        whfrom.client,whfrom.clientname,info.plateno,info.vessel,info.unit
                union all
                select head.trno,head.docno,date(head.dateid) as dateid,head.ourref,
                        whto.client as whtocode,whto.clientname as whto,
                        whfrom.client as whfromcode,whfrom.clientname as whfrom,info.plateno,
                        info.vessel,info.unit
                from glhead as head
                left join glstock as stock on stock.trno=head.trno
                left join hcntnuminfo as info on info.trno = head.trno
                left join client as whto on whto.clientid=info.whtoid
                left join client as whfrom on whfrom.clientid=info.whfromid
                where head.doc='LL' and date(head.dateid) between '$start' and '$end' $filter
                group by head.trno,head.docno,head.dateid,head.ourref,whto.client,whto.clientname,
                        whfrom.client,whfrom.clientname,info.plateno,info.vessel,info.unit";

        return $query;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $count = 71;
        $page = 70;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize14 = 10;
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->header_DEFAULT($config, $layoutsize);
        $str .= $this->header_table($config, $layoutsize);

        $i = 1;
        $total = 0;
        $ctr = 0;
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->ourref, '150', null, false, $border, '', 'CT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->docno, '140', null, false, $border, '', 'CT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->plateno, '120', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->vessel, '140', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');
                $str .= $this->reporter->col($data->unit, '150', null, false, $border, '', 'LT', $font, $fontsize14, 'R', '', '');

                $str .= $this->reporter->endrow($layoutsize);

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config, $layoutsize);
                    $str .= $this->header_table($config, $layoutsize);
                    $page = $page + $count;
                } //end if

                $ctr = $i++;
            }
        }
        $str .= $this->reporter->begintable($layoutsize);

        // $str .= '</br>';
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page', '100', null, '', $border, 'T', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $fontsize14, 'R', '', '');
        $str .= $this->reporter->col('No. of Trnx: ' . $ctr, '140', null, false, $border, 'T', 'C', $font, $fontsize14, 'R', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'R', $font, $fontsize14, 'B', '', '8px');
        $str .= $this->reporter->col('', '140', null, false, $border, 'T', 'R', $font, $fontsize14, 'B', '', '8px');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'R', $font, $fontsize14, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_table($config, $layoutsize)
    {
        $str = "";
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize14 = 10;
        $border = "1px solid";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Cargo M', '150', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Doc No', '140', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Van No/Plate', '120', null, false, $border, 'TB', 'L', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Vessel', '140', null, false, $border, 'TB', 'L', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Unit', '150', null, false, $border, 'TB', 'L', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }

    public function header_DEFAULT($config, $layoutsize)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $whfrom = $config['params']['dataparams']['dwhfrom'];
        $whto = $config['params']['dataparams']['dwhto'];

        if ($whfrom != "") {
            $from = $config['params']['dataparams']['whfromname'];
        } else {
            $from = 'ALL';
        }
        if ($whto != "") {
            $to = $config['params']['dataparams']['whtoname'];
        } else {
            $to = 'ALL';
        }

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize14 = 10;
        $fontsize16 = 16;
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, null, null, false, $border, '', 'L', $font, 10, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', $font, 11, 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CARGO MANIFEST SUMMARY', null, null, false, $border, '', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($start . ' - ' . $end, null, null, false, $border, '', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From : ' . $from, null, null, false, $border, '', 'L', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('To : ' . $to, null, null, false, $border, '', 'L', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class