<?php

namespace App\Http\Classes\modules\reportlist\purchasing_reports;

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

class advances_monitoring
{
    public $modulename = 'Advances Monitoring';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1340'];

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
        $fields = ['radioprint', 'start', 'end'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'effectfromdate.required', true);
        data_set($col1, 'effecttodate.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];

        return $this->coreFunctions->opentable("select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                '0' as posttype
                ");
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
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = "
            select head.docno,stock.cvtrno,ifnull(cat.category,'') as categoryname,
            date(head.dateid) as podate,  head.yourref as pono,sum(stock.ext) as poamount,client.clientname as supplier,

            (select group_concat(si2 SEPARATOR ',') from (
            select d.trno,di.si2 from ladetail as d left join detailinfo as di on di.trno=d.trno and di.line=d.line where d.acnoid=4891
            union all
            select d.trno,di.si2 from gldetail as d left join hdetailinfo as di on di.trno=d.trno and di.line=d.line where d.acnoid=4891) as a where trno=stock.cvtrno) as sinumber,

            (select salestype from (
            select trno,salestype from lahead as lh
            union all
            select trno,salestype from glhead as lh ) as a where trno=stock.cvtrno) as paymentterms,

            (select group_concat(distinct sono SEPARATOR ',') from (
            select ms.reqtrno,ms.reqline,ifnull(so.sono,'') as sono from omstock as ms
            left join omso as so on so.trno=ms.trno and so.line=ms.line

            union all

            select ms.reqtrno,ms.reqline,ifnull(so.sono,'') as sono from homstock as ms
            left join homso as so on so.trno=ms.trno and so.line=ms.line) as a
            where reqtrno=stock.reqtrno and reqline=stock.reqline) as sonumber

            from hpohead as head

            left join hpostock as stock on stock.trno=head.trno
                left join client on head.client = client.client
            left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
            left join hprhead as pr on pr.trno=info.trno
            left join reqcategory as cat on cat.line=pr.ourref
                where head.dateid between '$start' and '$end' and stock.void<>1
            group by head.docno,stock.cvtrno,sinumber,cat.category,head.dateid,head.yourref,paymentterms,sonumber,client.clientname


            union all
            select head.trno,head.docno,ifnull(cat.category,'') as categoryname,
            date(head.dateid) as podate,  head.yourref as pono,sum(stock.ext) as poamount,client.clientname as supplier,
            '' as  sinumber,
            '' as paymentterms,
            (select group_concat(distinct sono SEPARATOR ',') from (
            select ms.reqtrno,ms.reqline,ifnull(so.sono,'') as sono from omstock as ms
            left join omso as so on so.trno=ms.trno and so.line=ms.line

            union all

            select ms.reqtrno,ms.reqline,ifnull(so.sono,'') as sono from homstock as ms
            left join homso as so on so.trno=ms.trno and so.line=ms.line) as a
            where reqtrno=stock.reqtrno and reqline=stock.reqline) as sonumber

            from pohead as head
            left join postock as stock on stock.trno=head.trno
            left join client on head.client = client.client
            left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
            left join hprhead as pr on pr.trno=info.trno
            left join reqcategory as cat on cat.line=pr.ourref
            left join transnum as num on num.trno=head.trno
            where num.postdate is null and head.lockdate is null and num.statid=39 and head.dateid between '$start' and '$end' and stock.void<>1
            group by head.trno,head.docno,cat.category,head.dateid,head.yourref,client.clientname,sinumber,paymentterms,sonumber";

        return $query;
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $posttype  = $config['params']['dataparams']['posttype'];
        $layoutsize = '1500';
        $size1 = '330';
        $size2 = '320';

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '26', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->categoryname, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sonumber, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->podate, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pono, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '26', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->supplier, $size1, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sinumber, $size2, null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->paymentterms, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '27', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '27', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '27', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col('', '27', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->poamount, $decimal), '120', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endtable();
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $posttype  = $config['params']['dataparams']['posttype'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $str = '';
        $layoutsize = '1500';

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
        $str .= $this->reporter->col('Advances Monitoring', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '700', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $posttype  = $config['params']['dataparams']['posttype'];
        $layoutsize = '1500';
        $size1 = '330';
        $size2 = '320';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '26', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Category', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SO #', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PO Date', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PO #', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '26', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Supplier', $size1, null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Invoice Number', $size2, null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Mode of Payment', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '27', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '27', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '27', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '27', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PO Amount', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class