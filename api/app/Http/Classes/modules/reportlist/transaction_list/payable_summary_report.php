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

class payable_summary_report
{
    public $modulename = 'Payable Summary Report';
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
        $fields = ['radioprint', 'start', 'end', 'acnoname5'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select
            'default' as print,
            adddate(left(now(), 10),-360) as start,
            date_format(date(now()),'%Y-%m-%d') as end,
            '' as contra5,
            '' as acnoname5,
            '0' as acnoid5
        ");
    }

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
        return $this->reportDefault_Layout($config);
    }

    public function reportDefault_query($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $bank = isset($config['params']['dataparams']['acnoname5']) ? trim($config['params']['dataparams']['acnoname5']) : '';
        $filter = "";

        if ($bank != "") {
            $filter .= " and bankcoa.acnoname = '" . addslashes($bank) . "'";
        }

        $query = "select head.docno as cvno,
                ifnull(client.clientname, 'no name') as payee,
                detailcoa.acnoname as description,
                ifnull(nullif(detail.ref, ''), '---------') as drsi,
                ifnull(date_format(refhead.dateid, '%m/%d/%Y'), '---------') as drsidate,
                bankline.checkno as checkno,
                date_format(detail.postdate, '%m/%d/%Y') as checkdate,
                detail.db as amount,
                substring_index(trim(bankcoa.acnoname), ' ', 1) as bank
            from glhead as head
            left join cntnum on cntnum.trno = head.trno
            join gldetail as detail on detail.trno = head.trno
                and detail.db > 0
                and detail.void = 0
            left join coa as detailcoa on detailcoa.acnoid = detail.acnoid
            left join client on client.clientid = detail.clientid
            left join glhead as refhead on refhead.trno = detail.refx
            left join gldetail as bankline on bankline.trno = head.trno
                and bankline.cr > 0
                and bankline.checkno <> ''
            left join coa as bankcoa on bankcoa.acnoid = bankline.acnoid
            where left(detailcoa.alias, 2) = 'AP'
                and date(head.dateid) between '$start' and '$end' $filter
            order by head.docno, client.clientname, detail.line";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config)
    {
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $bank = isset($config['params']['dataparams']['acnoname5']) ? trim($config['params']['dataparams']['acnoname5']) : '';

        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "10";
        $border = "1px solid ";

        $str = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Payable Summary Report', '1000', null, false, $border, '', 'C', $font, '13', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('from ' . $start . ' to ' . $end, '1000', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Bank: ' . ($bank != '' ? $bank : 'All'), '1000', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PAYEE', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DESCRIPTION', '240', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DR#/SI#', '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DR#/SI# DATE', '90', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CHECK #', '110', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CHECK DATE', '90', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BANK', '90', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefault_Layout($config)
    {
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "10";
        $this->reporter->linecounter = 0;

        $result = $this->reportDefault_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        $total = count($result);
        $currentcv = '';

        for ($i = 0; $i < $total; $i++) {
            $row = $result[$i];
            
            if ($currentcv != $row->cvno) {
                $currentcv = $row->cvno;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('<u>' . $row->cvno . '</u>', '1000', null, false, '', '', 'L', $font, $fontsize, 'B', '', '8px 0 4px 0');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $islast = ($i == $total - 1) || ($result[$i + 1]->cvno != $row->cvno);
            $borderStyle = $islast ? '1px dotted' : '';
            $borderEdge = $islast ? 'B' : '';

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->payee, '200', null, false, $borderStyle, $borderEdge, 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->description, '240', null, false, $borderStyle, $borderEdge, 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->drsi, '80', null, false, $borderStyle, $borderEdge, 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->drsidate, '90', null, false, $borderStyle, $borderEdge, 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->checkno, '110', null, false, $borderStyle, $borderEdge, 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->checkdate, '90', null, false, $borderStyle, $borderEdge, 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($row->amount, 2), '100', null, false, $borderStyle, $borderEdge, 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->bank, '90', null, false, $borderStyle, $borderEdge, 'CT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class