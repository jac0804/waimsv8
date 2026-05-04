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

class receivables_report
{
    public $modulename = 'Receivables Report';
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

        $filter = '';
        $query = '';

        $query = "select sum(beginning) as balance,sum(debit) as debit,sum(credit) as credit,
                sum(sales) as sales,sum(dmemo) as dmemo,sum(cmemo) as cmemo
                from (select sum(case when detail.db > 0 then detail.bal else detail.bal * -1 end) as beginning,
                0 as debit, 0 as credit, 0 as sales, 0 as dmemo, 0 as cmemo
                from arledger as detail
                left join glhead as head on head.trno = detail.trno
                left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
                left join coa on coa.acnoid = gdetail.acnoid
                where detail.bal <> 0  and left(coa.alias, 2) = 'AR'
                and date(detail.dateid) < '$start'
                union all
                select 0 as beginning, sum(detail.db) as debit, 0 as credit, 0 as sales, 0 as dmemo, 0 as cmemo
                from arledger as detail
                left join glhead as head on head.trno = detail.trno
                left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
                left join coa on coa.acnoid = gdetail.acnoid
                where detail.bal <> 0 and left(coa.alias, 2) = 'AR'
                and detail.db > 0  and date(detail.dateid) between '$start' and '$end'
                and head.doc not in ('SJ', 'GD')
                union all
                select 0 as beginning, 0 as debit, sum(detail.cr) as credit, 0 as sales, 0 as dmemo, 0 as cmemo
                from arledger as detail
                left join glhead as head on head.trno = detail.trno
                left join gldetail as gdetail on gdetail.trno = detail.trno and gdetail.line = detail.line
                left join coa on coa.acnoid = gdetail.acnoid
                where detail.bal <> 0 and left(coa.alias, 2) = 'AR' and detail.cr > 0
                and date(detail.dateid) between '$start' and '$end'
                and head.doc <> 'GC'
                union all
                
                select 0 as beginning, 0 as debit, 0 as credit,
                (select ifnull(sum(sj.amt),0) from glhead as h
                left join glstock as sj on sj.trno = h.trno
                where h.doc = 'SJ'
                and date(h.dateid) between '$start' and '$end') as sales,
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
        $printDate  = date("l, F j, Y");  
        $printTime  = date("g:i:s A");
        $startFormatted = date("F j", strtotime($start));
        $endFormatted = date("F j", strtotime($end));
      
        $str = '';
        $layoutsize = '900';
        $font = 'Tahoma';
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<br/><br/>';

      
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Receivables', '800', null, false, '', '', 'C', $font, '14', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date From :  ' . date("d-M-Y", strtotime($start)) . ' to ' . date("d-M-Y", strtotime($end)),'800',null,false,'','','C',$font,'11','');
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
          
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->pagenumber('Page', '700', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        return $str;

    }

    public function reportDefaultLayout($config, $result)
    {
        $layoutsize = '900';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "1px dotted ";
        $border1 = "3px double ";
        $companyid = $config['params']['companyid'];

        $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $balance_date = date("F j, Y", strtotime($start . ' -1 day'));

        // get data from query result
        $row = $result[0];
        $beginning  = number_format($row->balance + $row->debit, 2);
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
        $str .= $this->reporter->col('Balance As of ' . $balance_date, '600', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('P ' . $beginning, '300', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // debit transactions header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Add: Debit Transactions - ' . date("F Y", strtotime($start)), '400', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('', '200', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '300', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // sales invoice
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('Sales Invoice (SI)', '250', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('P ' . $sales, '200', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '350', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // debit memo
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, '11', '');
        $str .= $this->reporter->col('Debit Memo (DM)', '250', null, false, $border, '', 'L', $font, '11', '');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, '11', '');
        $str .= $this->reporter->col('P ' . $dmemo, '200', null, false,$border, 'B', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '350', null, false, $border, '', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // total debit + beginning subtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('P ' . $debit_plus_beginning, '200', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '150', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('P ' . $debit_plus_beginning, '200', null, false, $border, 'B', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '700', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('P ' . $total, '200', null, false, $border1, 'B', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // credit transactions header
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Less: Credit Transactions - ' . date("F Y", strtotime($start)), '400', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('', '200', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '300', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // official receipt
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('Receive Payment (OR)', '250', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('P ' . $credit, '200', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '350', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        
        // credit memo
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('Credit Memo (CM)', '250', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('P ' . $cmemo, '200', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '350', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // certificate of appreciation
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('Certificate of Appreciation (CA)', '250', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('', '50', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '150', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'B', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // total credit
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '350', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('P ' . $credit_minus_beginning, '200', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('', '150', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('P ' . $credit_minus_beginning, '200', null, false, $border, 'B', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        // ending balance
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Account Receivables as of ' . date("F j, Y", strtotime($end)), '500', null, false, '', '', 'L', $font, '11', '');
        $str .= $this->reporter->col('', '200', null, false, '', '', 'R', $font, '11', '');
        $str .= $this->reporter->col('P ' . $ending, '200', null, false, $border1, 'B', 'R', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
  
}//end class