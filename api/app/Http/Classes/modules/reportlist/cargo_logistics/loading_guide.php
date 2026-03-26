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

class loading_guide
{
    public $modulename = 'Loading Guide';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];



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

        $fields = ['radioprint', 'dwhfrom', 'dwhto', 'dprojectname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dwhto.label', 'Destination (to)');

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
        '' as dwhfrom, '' as whfrom, '' as whfromname,
        '' as dwhto, '' as whto, '' as whtoname,'' as dprojectname, '' as projectname, '' as projectcode";
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
        $whfrom = $config['params']['dataparams']['whfrom'];
        $whto = $config['params']['dataparams']['dwhto'];
        $dprojectname = $config['params']['dataparams']['dprojectname'];

        if ($whto != "") {
            $to = $config['params']['dataparams']['whto'];
            $filter .= " and whto.client = '$to' ";
        }
        if ($whfrom != "") {
            $from = $config['params']['dataparams']['whfrom'];
            $filter .= " and whfrom.client = '$from' ";
        }
        if ($dprojectname != "") {
            $projid = $config['params']['dataparams']['projectid'];
            $filter .= " and head.projectid = '$projid' ";
        }

        $query = "select trno,docno,dateid,whto,whtoname,isqty,consignee,pending,unit,qa,sum(llisqty) as llisqty
                  from (select head.trno,head.docno,date(head.dateid) as dateid,head.whto,
                               whto.clientname as whtoname,
                               round(sum(stock.isqty)) as isqty,cs.clientname as consignee,
                               round(sum(stock.isqty-stock.qa)) as pending,sinfo.unit,stock.qa,
                               lls.isqty as llisqty,llh.docno as lldocno
                        from glhead as head
                        left join glstock as stock on stock.trno=head.trno
                        left join hcntnuminfo as info on info.trno=head.trno
                        left join client as whto on whto.client=head.whto
                        left join client as whfrom on whfrom.clientid=head.whid
                        left join hstockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                        left join client as cs on cs.clientid=head.consigneeid
                        left join lastock as lls on lls.refx=stock.trno and lls.linex=stock.line
                        left join lahead as llh on llh.trno=lls.trno
                    where head.doc='SJ'  $filter 
                    group by head.trno,head.docno,head.dateid,head.whto,whto.client,whto.clientname,
                            cs.clientname,sinfo.unit,stock.qa,lls.isqty,llh.docno
                    union all
                    select head.trno,head.docno,date(head.dateid) as dateid,head.whto,
                                whto.clientname as whtoname,
                                round(sum(stock.isqty)) as isqty,cs.clientname as consignee,
                                round(sum(stock.isqty-stock.qa)) as pending,sinfo.unit,stock.qa,
                                lls.isqty as llisqty,llh.docno as lldocno
                        from glhead as head
                        left join glstock as stock on stock.trno=head.trno
                        left join hcntnuminfo as info on info.trno=head.trno
                        left join client as whto on whto.client=head.whto
                        left join client as whfrom on whfrom.clientid=head.whid
                        left join hstockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                        left join client as cs on cs.clientid=head.consigneeid
                    left join glstock as lls on lls.refx=stock.trno and lls.linex=stock.line
                    left join glhead as llh on llh.trno=lls.trno
                        where head.doc='SJ' $filter 
                        group by head.trno,head.docno,head.dateid,head.whto,whto.client,whto.clientname,
                                cs.clientname,sinfo.unit,stock.qa,lls.isqty,llh.docno
                        union all

                        select head.trno,head.docno,date(head.dateid) as dateid,head.whto,
                               whto.clientname as whtoname,
                               round(sum(stock.isqty)) as isqty,cs.clientname as consignee,
                               round(sum(stock.isqty-stock.qa)) as pending,sinfo.unit,stock.qa,
                               lls.isqty as llisqty,llh.docno as lldocno
                        from lahead as head
                        left join lastock as stock on stock.trno=head.trno
                        left join cntnuminfo as info on info.trno=head.trno
                        left join client as whto on whto.client=head.whto
                        left join client as whfrom on whfrom.client=head.wh
                        left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                        left join client as cs on cs.clientid=head.consigneeid
                        left join lastock as lls on lls.refx=stock.trno and lls.linex=stock.line
                        left join lahead as llh on llh.trno=lls.trno
                where head.doc='SJ'  $filter 
                group by head.trno,head.docno,head.dateid,head.whto,whto.client,whto.clientname,
                        cs.clientname,sinfo.unit,stock.qa,lls.isqty,llh.docno
                        union all
                select head.trno,head.docno,date(head.dateid) as dateid,head.whto,whto.clientname as whtoname,
                            round(sum(stock.isqty)) as isqty,cs.clientname as consignee,
                            round(sum(stock.isqty-stock.qa)) as pending,sinfo.unit,stock.qa,lls.isqty as llisqty,llh.docno as lldocno
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join cntnuminfo as info on info.trno=head.trno
                    left join client as whto on whto.client=head.whto
                    left join client as whfrom on whfrom.client=head.wh
                    left join stockinfo as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                    left join client as cs on cs.clientid=head.consigneeid
                    left join glstock as lls on lls.refx=stock.trno and lls.linex=stock.line
                left join glhead as llh on llh.trno=lls.trno
                    where head.doc='SJ' $filter 
                    group by head.trno,head.docno,head.dateid,head.whto,whto.client,whto.clientname,
                            cs.clientname,sinfo.unit,stock.qa,lls.isqty,llh.docno) as k
                                where pending <> 0
                                group by trno,docno,dateid,whto,whtoname,isqty,consignee,pending,unit,qa
                                order by whtoname,docno";
        return $query;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $count = 71;
        $page = 70;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = 1000;
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize14 = 11;
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);

        $str .= $this->header_DEFAULT($config, $layoutsize);
        $str .= $this->header_table($config, $layoutsize);

        $i = 1;
        $total = 0;
        $gtotal = 0;
        $ctr = 0;
        $whtoname = '';
        $pending = '';
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                //start 
                if ($whtoname != "" && $whtoname != $data->whtoname) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '340', null, false, $border, '', 'CT', $font, $fontsize14, '', '', '');
                    $str .= $this->reporter->col(number_format($total, 0), '120', null, false, $border, 'T', 'RT', $font, $fontsize14, '', '', '');
                    $str .= $this->reporter->col('', '540', null, false, $border, '', 'CT', $font, $fontsize14, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->printline();
                } //end if

                if ($whtoname == "" || $whtoname != $data->whtoname) {
                    $whtoname = $data->whtoname;
                    $total = 0;

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->whtoname, '1000', null, false, $border, '', 'L', $font, $fontsize14, 'B', '', '');
                    $str .= $this->reporter->endrow();
                }

                //end 
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'CT', $font, $fontsize14, '', '', '');
                $str .= $this->reporter->col($data->dateid, '120', null, false, $border, '', 'CT', $font, $fontsize14, '', '', '');
                if ($data->qa > 0) {
                    $pending = 'BAL';
                } else {
                    $pending = '';
                    $data->pending = $data->isqty;
                }
                $str .= $this->reporter->col($pending, '70', null, false, $border, '', 'CT', $font, $fontsize14, '', '', '');

                $str .= $this->reporter->col(number_format($data->pending, 0), '120', null, false, $border, '', 'RT', $font, $fontsize14, '', '', '');
                $str .= $this->reporter->col($data->unit, '120', null, false, $border, '', 'CT', $font, $fontsize14, '', '', '');
                $str .= $this->reporter->col($data->consignee, '420', null, false, $border, '', 'LT', $font, $fontsize14, '', '', '');
                $str .= $this->reporter->endrow($layoutsize);

                //start
                if ($whtoname == $data->whtoname) {
                    $total += $data->pending;
                }
                $str .= $this->reporter->endtable();

                if ($i == (count((array)$result))) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '340', null, false, $border, '', 'CT', $font, $fontsize14, 'R', '', '');
                    $str .= $this->reporter->col(number_format($total, 0), '120', null, false, $border, 'T', 'RT', $font, $fontsize14, 'R', '', '');
                    $str .= $this->reporter->col('', '540', null, false, $border, '', 'CT', $font, $fontsize14, 'R', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }
                $i++;

                //end
                $gtotal += $data->pending;
            }
        }
        $str .= '</br>';
        $str .= $this->reporter->begintable($layoutsize);

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page', '340', null, '', $border, 'T', 'L', $font, $fontsize14, '', '', '');
        $str .= $this->reporter->col(number_format($gtotal, 0), '120', null, false, $border, 'T', 'RT', $font, $fontsize14, 'R', '', '');
        $str .= $this->reporter->col('', '540', null, false, $border, 'T', 'CT', $font, $fontsize14, 'R', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_table($config, $layoutsize)
    {
        $str = "";
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize14 = 11;
        $border = "1px solid";
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Waybill', '150', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('WB Date', '120', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Pending', '120', null, false, $border, 'TB', 'R', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Unit', '120', null, false, $border, 'TB', 'C', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->col('Consignee', '420', null, false, $border, 'TB', 'L', $font, $fontsize14, 'B', '', '');
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
        $whto = $config['params']['dataparams']['dwhto'];
        $dprojectname  = $config['params']['dataparams']['dprojectname'];

        if ($whto != "") {
            $to = $config['params']['dataparams']['whtoname'];
        } else {
            $to = 'ALL';
        }

        if ($dprojectname != "") {
            $projname = $config['params']['dataparams']['projectname'];
            $project = $projname;
        } else {
            $project = 'ALL';
        }

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize14 = 11;
        $fontsize16 = 12;
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, null, null, false, $border, '', 'L', $font, $fontsize16, '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('LOADING GUIDE', null, null, false, $border, '', 'C', $font, $fontsize16, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(date("Y-m-d"), null, null, false, $border, '', 'C', $font, $fontsize14, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DESTINATION : ' . $to, null, null, false, $border, '', 'L', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PROJECT : ' . $project, null, null, false, $border, '', 'L', $font, $fontsize14, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class