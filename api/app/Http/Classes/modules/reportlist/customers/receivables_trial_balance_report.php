<?php

namespace App\Http\Classes\modules\reportlist\customers;

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
use Illuminate\Support\Facades\URL;

class receivables_trial_balance_report
{
    public $modulename = 'Receivables Trial Balance Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'P', 'format' => 'letter', 'layoutSize' => '1200'];

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
    $fields = ['radioprint','start', 'end'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.type', 'date');
    data_set($col1, 'end.type', 'date');


    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
    }

     public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end

     ");
    }
    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
    }

    public function getloaddata($config)
    {
        return [];
    }
    
    public function reportplotting($config)
    {
        $data = $this->data_query($config);
        return $this->reportDefaultLayout($config, $data);
    }

    public function data_query($config)
    {
        $companyid = $config['params']['companyid'];
        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $query = '';

        $query = "select sum(beginning) as balance, sum(debit) as debit, sum(credit) as credit,
            sum(sales) as sales, sum(dmemo) as dmemo, sum(cmemo) as cmemo
            from (select sum(detail.db - detail.cr) as beginning,
            0 as debit, 0 as credit, 0 as sales, 0 as dmemo, 0 as cmemo
            from arledger as detail
            left join glhead as head on head.trno = detail.trno
            left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
            left join coa on coa.acnoid = gdetail.acnoid
            where left(coa.alias, 2) = 'AR'
            and date(detail.dateid) < '$start'
            union all
            select 0 as beginning, 0 as debit, 0 as credit,
            (select ifnull(sum(detail.db),0) from arledger as detail
            left join glhead as head on head.trno = detail.trno
            left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
            left join coa on coa.acnoid = gdetail.acnoid
            where head.doc = 'SJ' and left(coa.alias, 2) = 'AR'
            and date(head.dateid) between '$start' and '$end') as sales,
            (select ifnull(sum(detail.db),0) from arledger as detail
            left join glhead as head on head.trno = detail.trno
            left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
            left join coa on coa.acnoid = gdetail.acnoid
            where head.doc = 'GD'
            and date(head.dateid) between '$start' and '$end') as dmemo,
            (select ifnull(sum(detail.cr),0) from arledger as detail
            left join glhead as head on head.trno = detail.trno
            left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
            left join coa on coa.acnoid = gdetail.acnoid
            where head.doc = 'GC' and left(coa.alias, 2) = 'AR'
            and date(head.dateid) between '$start' and '$end') as cmemo
            union all
            select 0 as beginning,
            (select ifnull(sum(detail.db),0) from arledger as detail
            left join glhead as head on head.trno = detail.trno
            left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
            left join coa on coa.acnoid = gdetail.acnoid
            where left(coa.alias, 2) = 'AR'
            and detail.db > 0
            and head.doc not in ('SJ', 'GD')
            and date(detail.dateid) between '$start' and '$end') as debit,
            (select ifnull(sum(detail.cr),0) from arledger as detail
            left join glhead as head on head.trno = detail.trno
            left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
            left join coa on coa.acnoid = gdetail.acnoid
            where left(coa.alias, 2) = 'AR'
            and detail.cr > 0
            and head.doc not in ('GC')
            and date(detail.dateid) between '$start' and '$end') as credit,
            0 as sales, 0 as dmemo, 0 as cmemo
            ) as x";

        return $this->coreFunctions->opentable($query);
    }
    
    public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
      
        $str = '';
        $layoutsize = '900';
        $font = 'Tahoma';
        $fontsize = "14";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Receivables Trial Balance Report', '800', null, false, '', '', 'C', $font, '15', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date From :  ' . date("d-M-Y", strtotime($start)) . ' to ' . date("d-M-Y", strtotime($end)),'800',null,false,'','','C',$font,$fontsize,'');
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        return $str;

    }

    public function reportDefaultLayout($config, $result)
    {
        $layoutsize = '900';
        $font = 'Tahoma';
        $fontsize = "14";
        $border = "1px dotted ";
        $border1 = "3px double ";
        $companyid = $config['params']['companyid'];

        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $balance_date = date("F j, Y", strtotime($start . ' -1 day'));

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        // get data from query result
        $row = $result[0];
        $beginning  = number_format($row->balance , 2);
        $sales      = number_format($row->sales, 2);
        $dmemo      = number_format($row->dmemo, 2);
        $total = number_format($row->balance + $row->sales + $row->dmemo, 2);
        $debit_plus_beginning = number_format($row->sales + $row->dmemo, 2);
        $credit_minus_beginning = number_format($row->credit + $row->cmemo, 2);
        $credit     = number_format($row->credit, 2);
        $cmemo      = number_format($row->cmemo, 2);
        $ending = number_format($row->balance + $row->sales + $row->dmemo - $row->credit - $row->cmemo, 2);

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, count($result));

        // beginning balance
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Balance As of ' . $balance_date, '600', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $beginning, '300', null, false, '', '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // debit transactions header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Add: Debit Transactions - ' . date("F Y", strtotime($start)), '400', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '200', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '300', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // sales invoice
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('Sales Invoice (SI)', '250', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $sales, '250', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '300', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // debit memo
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('Debit Memo (DM)', '250', null, false, $border, '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $dmemo, '250', null, false,$border, 'B', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '300', null, false, $border, '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // total debit + beginning subtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $debit_plus_beginning, '250', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $debit_plus_beginning, '250', null, false, $border, 'B', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '650', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $total, '250', null, false, $border1, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // credit transactions header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Less: Credit Transactions - ' . date("F Y", strtotime($start)), '400', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '200', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '300', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // official receipt
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('Received Payment (OR/CR)', '250', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $credit, '250', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '300', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        
        // credit memo
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('Credit Memo (CM)', '250', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $cmemo, '250', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '300', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // certificate of appreciation
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('Certificate of Appreciation (CA)', '300', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '250', null, false, $border, 'B', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '250', null, false, $border, 'B', 'R', $font, $fontsize, '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // total credit
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $credit_minus_beginning, '250', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $credit_minus_beginning, '250', null, false, $border, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // ending balance
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Account Receivables as of ' . date("F j, Y", strtotime($end)), '500', null, false, '', '', 'L', $font, $fontsize, '');
        $str .= $this->reporter->col('', '150', null, false, '', '', 'R', $font, $fontsize, '');
        $str .= $this->reporter->col('P ' . $ending, '250', null, false, $border1, 'B', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
  
}//end class