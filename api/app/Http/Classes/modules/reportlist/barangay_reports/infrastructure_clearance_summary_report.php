<?php

namespace App\Http\Classes\modules\reportlist\barangay_reports;

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

class infrastructure_clearance_summary_report
{
  public $modulename = 'Infrastructure Clearance Summary Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  // orientations: portrait=p, landscape=l
  // formats: letter, a4, legal
  // layoutsize: reportWidth
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
        $fields = ['radioprint','start', 'end','infratype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.type', 'date');
        data_set($col1, 'end.type', 'date');
        data_set($col1, 'infratype.lookupclass', 'lookupinfratype');

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
        left(now(),10) as end,
        '' as infratype

     ");
    }

    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating REPORT successfully', 'report' => $str, 'params' => $this->reportParams];
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
        $infratype = $config['params']['dataparams']['infratype'];

        $filter = '';
        $query = '';

        if ($infratype != '') {
            $filter .= " and cl.infratype = '$infratype'";
        }
        
      
        $query = "select head.dateid,concat(left(cl.client,1), right(cl.client,4)) as brgyid, head.docno as control, cl.clientname, head.amount
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join client as cl on cl.client = head.client
            left join cntnum as cnum on cnum.trno = head.trno
            where cl.isinfra = 1 and cnum.doc = 'BI'  and date(head.dateid) between '$start' and '$end' $filter    
            union all     
            select head.dateid,concat(left(cl.client,1), right(cl.client,4)) as brgyid, head.docno, cl.clientname, head.amount
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join client as cl on cl.clientid = head.clientid
            left join cntnum as cnum on cnum.trno = head.trno
            where cl.isinfra = 1 and cnum.doc = 'BI'  and date(head.dateid) between '$start' and '$end' $filter
            order by dateid";
        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $infratype = $config['params']['dataparams']['infratype'];
        $start = date("M-d-Y", strtotime($config['params']['dataparams']['start']));
        $end = date("M-d-Y", strtotime($config['params']['dataparams']['end']));
        $printDate = date("m/d/y");  
        $printTime = date("g:i:s A");
      
        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "12";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        // $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, null, null, 'L', $font, $fontsize , 'B', '', '');
        $str .= $this->reporter->col('', '430');
        $str .= $this->reporter->col('Print Date:', '100', null, false, '', '', 'R', $font, $fontsize ,'');
        $str .= $this->reporter->col($printDate . '  ' . $printTime, '170', null, false, '', '', 'R', $font, $fontsize );
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

      
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<span style="color:#8B0000;">INFRASTRUCTURE CLEARANCE SUMMARY REPORT</span>', '500', null, false, '10px solid', '', 'L', $font, $fontsize , 'B', '', '');
        $str .= $this->reporter->col('', '400');
        $str .= $this->reporter->pagenumber('Page', '100', null, false, $border, '', 'R', $font, $fontsize , '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE FROM :  ' . $start . '  TO  ' . $end, '350', null, false, '', '', 'L', $font, $fontsize , 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('INFRA TYPE :', '130', null, false, '', '', 'L', $font, $fontsize ,'B');
        $str .= $this->reporter->col($infratype == '' ? 'ALL INFRA TYPE' : strtoupper($infratype), '200', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col('', '670');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, '2px solid', 'T', 'R', $font, $fontsize , 'B');
        $str .= $this->reporter->col('DATE', '170', null, false, '2px solid', 'TB', 'C', $font, $fontsize , 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', 'T', 'R', $font, $fontsize , 'B');
        $str .= $this->reporter->col('CONTROL #', '145', null, false, '2px solid', 'TB', 'C', $font, $fontsize , 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', 'T', 'R', $font, $fontsize , 'B');
        $str .= $this->reporter->col('BRGY. ID', '145', null, false, '2px solid', 'TB', 'C', $font, $fontsize , 'B');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', 'T', 'R', $font, $fontsize , 'B');
        $str .= $this->reporter->col('FULLNAME', '270', null, false, '2px solid', 'TB', 'C', $font, $fontsize , 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, '2px solid', 'T', 'R', $font, $fontsize , 'B');
        $str .= $this->reporter->col('AMOUNT', '150', null, false, '2px solid', 'TB', 'C', $font, $fontsize , 'B', '', '');
        $str .= $this->reporter->col('', '50', null, false, '2px solid', 'T', 'R', $font, $fontsize , 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;

    }

    public function reportDefaultLayout($config, $result)
    {
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "1px solid ";
        $companyid = $config['params']['companyid'];

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $limitPerPage = 40;
        $rowCount = 0;
        $totalAmount = 0;
        $totalClearance = count($result);

        $currentDate = '';
        $TotalAmount = 0;
        $dateTotalClearance = 0;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:95px');
        $str .= $this->displayHeader($config, count($result));

        foreach ($result as $data) {

            $totalAmount += $data->amount;

            // Date changed — print subtotal for the PREVIOUS date
            if ($currentDate != '' && $currentDate != $data->dateid) {

                $str .= '<br>';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '25');
                $str .= $this->reporter->col('TOTAL CLEARANCE :', '185', null, false, '', '', 'L', $font, $fontsize, 'B');
                $str .= $this->reporter->col($dateTotalClearance, '160', null, false, '2px solid', 'TBLR', 'C', $font, $fontsize, 'B');
                $str .= $this->reporter->col('', '240');
                $str .= $this->reporter->col('TOTAL AMOUNT :', '200', null, false, '', '', 'R', $font, $fontsize, 'B');
                $str .= $this->reporter->col('', '5');
                $str .= $this->reporter->col(number_format($TotalAmount, 2), '160', null, false, '2px solid', 'TBLR', 'R', $font, $fontsize, 'B');
                $str .= $this->reporter->col('', '25');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // Reset per-date totals
                $TotalAmount = 0;
                $dateTotalClearance = 0;

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '100', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                $str .= $this->reporter->col('', '150', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                $str .= $this->reporter->col('', '150', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                $str .= $this->reporter->col('', '260', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                $str .= $this->reporter->col('', '260', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                $str .= $this->reporter->col('', '80', '20', false, '1px dashed', 'B', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }

            $dateCol = '';
            if ($currentDate != $data->dateid) {
                $dateCol = $data->dateid;
            }

            $currentDate = $data->dateid;

            // Accumulate AFTER reset so current row counts for new date
            $TotalAmount += $data->amount;
            $dateTotalClearance++;

            if ($rowCount > 0 && $rowCount % $limitPerPage == 0) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, count($result));
                $str .= $this->reporter->begintable($layoutsize);
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($dateCol != '' ? date('m-d-Y', strtotime($dateCol)) : '', '170', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col($data->control, '145', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col($data->brgyid, '145', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col($data->clientname, '270', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col(number_format($data->amount, 2), '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $rowCount++;
        }

        // Print subtotal for the LAST date group
        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '25');
        $str .= $this->reporter->col('TOTAL CLEARANCE :', '185', null, false, '', '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($totalClearance, '160', null, false, '2px solid', 'TBLR', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '240');
        $str .= $this->reporter->col('TOTAL AMOUNT :', '200', null, false, '', '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '5');
        $str .= $this->reporter->col(number_format($TotalAmount, 2), '160', null, false, '2px solid', 'TBLR', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '25');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
        $str .= $this->reporter->col('', '150', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
        $str .= $this->reporter->col('', '150', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
        $str .= $this->reporter->col('', '260', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
        $str .= $this->reporter->col('', '260', '20', false, '1px dashed', 'B', 'LT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
        $str .= $this->reporter->col('', '80', '20', false, '1px dashed', 'B', 'RT', $font, $fontsize, '', '', '', '', 0, '', 0, 0, '#757575');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // Grand Total
        // $str .= '<br>';
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '50');
        // $str .= $this->reporter->col('GRAND TOTAL CLEARANCE :', '185', null, false, '', '', 'L', $font, $fontsize, 'B');
        // $str .= $this->reporter->col($totalClearance, '200', null, false, '2px solid', 'TBLR', 'C', $font, $fontsize, 'B');
        // $str .= $this->reporter->col('', '110');
        // $str .= $this->reporter->col('GRAND TOTAL AMOUNT :', '200', null, false, '', '', 'R', $font, $fontsize, 'B');
        // $str .= $this->reporter->col('', '5');
        // $str .= $this->reporter->col(number_format($totalAmount, 2), '200', null, false, '2px solid', 'TBLR', 'R', $font, $fontsize, 'B');
        // $str .= $this->reporter->col('', '50');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }


} // end class