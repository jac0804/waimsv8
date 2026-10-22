<?php

namespace App\Http\Classes\modules\reportlist\items;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

class dispatch_qty_report
{
    public $modulename = 'Dispatch Qty Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

    public function __construct($params = null)
    {
        $this->fieldClass = new txtfieldClass($params);
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dprojectname'];

        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');

        $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
        $col2 = $this->fieldClass->create($fields);

        data_set(
            $col2,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );
        data_set(
            $col2,
            'radioreporttype.options',
            [
                ['label' => 'Summarized', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Detailed', 'value' => '1', 'color' => 'teal']
            ]
        );

        $fields = ['print'];

        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "
    select 'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '2' as posttype,
    '1' as reporttype,
    'ASC' as sorting,
    '' as client,
    '0' as clientid,
    '' as dclientname,
    '' as clientname,
    '' as dprojectname,
    '' as projectname, 0 as projectid, '' as projectname, '' as projectcode,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername";

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
        $reporttype = $config['params']['dataparams']['reporttype'];

        if ($reporttype == 0) {
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
        } else {
            $result = $this->reportDefaultLayout_DETAILED($config);
        }

        return $result;
    }

    // QUERY
    public function reportDefault($config)
    {
        $companyid    = $config['params']['companyid'];
        $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $dprojectname = $config['params']['dataparams']['dprojectname'];
        $client       = $config['params']['dataparams']['client'];
        $clientid     = (int)$config['params']['dataparams']['clientid'];
        $posttype     = $config['params']['dataparams']['posttype'];
        $reporttype   = $config['params']['dataparams']['reporttype'];
        $sorting      = $config['params']['dataparams']['sorting'];

        $sorting = strtoupper($sorting) == 'DESC' ? 'DESC' : 'ASC';

        $filter = '';

        if ($dprojectname != "") {
            $projid = (int)$config['params']['dataparams']['projectid'];
            $filter .= " and head.projectid = '$projid' ";
        }

        if ($client != "") {
            $filter .= " and client.clientid = '$clientid' ";
        }

        if ($reporttype == 0) {
            // summarized
            $fields  = "head.docno, '' as itemname, sum(stock.isqty) as dispatch_qty, sum(stock.isqty2) as customer_qty,
                        sum(stock.isqty - stock.isqty2) as variance";
            $groupby = " group by head.docno";
        } else {
            // detailed
            $fields  = "head.docno, left(head.dateid,10) as dateid, client.clientname as customer, i.itemname,
                        stock.isqty as dispatch_qty, stock.isqty2 as customer_qty,
                        (stock.isqty - stock.isqty2) as variance";
            $groupby = "";
        }

        $unposted = "select $fields
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join client on client.client = head.client
        left join item as i on i.itemid = stock.itemid
        where head.doc = 'SJ' and stock.isqty <> 0 and date(head.dateid) between '$start' and '$end' $filter $groupby";

        $posted = "select $fields
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join client on client.clientid = head.clientid
        left join item as i on i.itemid = stock.itemid
        where head.doc = 'SJ' and stock.isqty <> 0 and date(head.dateid) between '$start' and '$end' $filter $groupby";

        switch ($posttype) {
            case 0: // posted
                $query = $posted . " order by docno " . $sorting;
                break;
            case 1: // unposted
                $query = $unposted . " order by docno " . $sorting;
                break;
            default: // all
                $query = $unposted . " union all " . $posted . " order by docno " . $sorting;
                break;
        }
        logger($query);
        return $this->coreFunctions->opentable($query);
    }

    private function default_displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $sorting    = $config['params']['dataparams']['sorting'];

        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end   = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        switch ($posttype) {
            case 0:
                $posttypeLabel = 'Posted';
                break;
            case 1:
                $posttypeLabel = 'Unposted';
                break;
            default:
                $posttypeLabel = 'All';
                break;
        }

        $reporttypeLabel = $reporttype == 0 ? 'Summarized' : 'Detailed';
        $sortingLabel = strtoupper($sorting) == 'DESC' ? 'Descending' : 'Ascending';

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DISPATCH QTY REPORT (' . $reporttypeLabel . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' - ' . $end, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttypeLabel, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Sorting By: ' . $sortingLabel, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    private function summarized_tableheader($config)
    {
        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DOCUMENT', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DISPATCH QTY', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER QTY', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DIFF', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    private function detailed_tableheader($config)
    {
        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEMNAME', '320', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DISPATCH QTY', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CUSTOMER QTY', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DIFF', '160', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    private function detailed_total($config, $dispatch, $customer, $variance)
    {
        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total:', '320', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($dispatch, 2), '160', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($customer, 2), '160', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($variance, 2), '160', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    private $maxlines = 50;
    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);
        $str .= $this->summarized_tableheader($config);

        $lines = 0;

        foreach ($result as $key => $data) {
            // page is full: break and reprint the headers
            if ($lines >= $this->maxlines) {
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);
                $str .= $this->summarized_tableheader($config);
                $lines = 0;
            }

            $dispatch = (float)$data->dispatch_qty;
            $customer = (float)$data->customer_qty;
            $variance = (float)$data->variance;

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($dispatch, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($customer, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($variance, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $lines++;
        }

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function reportDefaultLayout_DETAILED($config)
    {
        $result = $this->reportDefault($config);

        $str = '';
        $layoutsize = '800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->printline();

        $docno = "";
        $total_dispatch = 0;
        $total_customer = 0;
        $total_variance = 0;
        $lines = 0;

        foreach ($result as $key => $data) {
            $newdoc = ($docno == "" || $docno != $data->docno);

            // close the previous document
            if ($docno != "" && $newdoc) {
                $str .= $this->detailed_total($config, $total_dispatch, $total_customer, $total_variance);
                $str .= '<br />';
                $lines += 2;
            }

            $need = $newdoc ? 5 : 1;
            if ($lines + $need > $this->maxlines) {
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);
                $str .= $this->reporter->printline();
                $lines = 0;

                if (!$newdoc) {
                    $str .= $this->detailed_tableheader($config);
                    $lines += 1;
                }
            }

            if ($newdoc) {
                $docno = $data->docno;
                $total_dispatch = 0;
                $total_customer = 0;
                $total_variance = 0;

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
                $str .= $this->reporter->col('Date: ' . $data->dateid, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
                $str .= $this->reporter->endrow();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Customer: ' . $data->customer, '800', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->detailed_tableheader($config);
                $lines += 3;
            }

            $dispatch = (float)$data->dispatch_qty;
            $customer = (float)$data->customer_qty;
            $variance = (float)$data->variance;

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->itemname, '320', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($dispatch, 2), '160', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($customer, 2), '160', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($variance, 2), '160', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $lines++;

            $total_dispatch += $dispatch;
            $total_customer += $customer;
            $total_variance += $variance;
        }

        // total for the last document
        $str .= $this->detailed_total($config, $total_dispatch, $total_customer, $total_variance);

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class