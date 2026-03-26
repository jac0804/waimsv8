<?php

namespace App\Http\Classes\modules\reportlist\queuing_reports;

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
use App\Http\Classes\modules\warehousing\forklift;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class queuing_summary_per_user_report
{
  public $modulename = 'Queuing Summary Per User Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $batch;
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
        $fields = ['radioprint', 'username','start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.type', 'date');
        data_set($col1, 'end.type', 'date');
        data_set($col1, 'username.lookupclass','lookupusers2');


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
        '' as username

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
        $username = $config['params']['dataparams']['username'];

        $filter = '';
        $query = '';
        
        if($username != ''){
            $filter .= "and cur.users = '$username'";
        }

        $query = "select date,service,users, count(customer) as customer_count,
                    sum(case when PRIORITY = 0 then serve else 0 end) as Regular_Serve,
                    sum(case when PRIORITY = 0 then cancel else 0 end) as Regular_Cancel,
                    sum(case when PRIORITY = 1 then serve else 0 end) as Priority_Serve,
                    sum(case when PRIORITY = 1 then cancel else 0 end) as Priority_Cancel,sum(waittime) as waittime
                from ( select IFNULL(reqq.code, '') as service,
                        IFNULL(cur.users, '') as users,
                        IFNULL(cur.ctr, '') as customer,
                        IFNULL(cur.isdone, 0) as serve,
                        IFNULL(cur.iscancel, 0) as cancel,
                        IFNULL(cur.ispwd, 0) as PRIORITY,
                        date(cur.dateid) as date,ifnull(timestampdiff(minute,cur.enddate,cur.startdate),0) as waittime
                    from currentservice as cur
                    left join reqcategory as req on cur.counterline = req.line and req.iscounter = 1 
                    left join reqcategory as reqq on reqq.line = cur.serviceline and reqq.isservice = 1 
                    where  date(cur.dateid) between '" . $start . "' and '" . $end . "' $filter
                    group by service, users, customer, serve, cancel, priority, date,cur.enddate,cur.startdate
                    union all
                    select IFNULL(reqq.code, '') as service,
                        IFNULL(cur.users, '') as users,
                        IFNULL(cur.ctr, '') as customer,
                        IFNULL(cur.isdone, 0) as serve,
                        IFNULL(cur.iscancel, 0) as cancel,
                        IFNULL(cur.ispwd, 0) as PRIORITY,
                        date(cur.dateid) as date,ifnull(timestampdiff(minute,cur.enddate,cur.startdate),0) as waittime
                    from hcurrentservice as cur
                    left join reqcategory as req  on cur.counterline = req.line and req.iscounter = 1 
                    left join reqcategory as reqq on reqq.line = cur.serviceline and reqq.isservice = 1 
                    where date(cur.dateid) between '" . $start . "' and '" . $end . "' $filter
                    group by service, users, customer, serve, cancel, priority, date,cur.enddate,cur.startdate
                ) as x
                where service <> '' and users <> ''
                group by service, users, date";

        return $this->coreFunctions->opentable($query);
    }

    public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $start = date("m-d-Y", strtotime($config['params']['dataparams']['start']));
        $end = date("m-d-Y", strtotime($config['params']['dataparams']['end']));
        $user = $config['params']['dataparams']['username'];
      
        $str = '';
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

      
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Queuing Summary Per User ', '700', null, false, '10px solid ', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('From:', '50', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($start . ' to ' . $end, '200', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->col('', '250');
        $str .= $this->reporter->col('User :', '50', null, false, '', '', 'L', $font, $fontsize,'B');
        $str .= $this->reporter->col($user == '' ? 'ALL USER' : strtoupper($user), '200', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->pagenumber('Page', '230', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '250', null, false, $border, 'LTB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Regular', '450', null, false, $border, 'RLTB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Priority', '300', null, false, $border, 'RTB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('User', '250', null, false, $border, 'TBL', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('# of Customers', '150', null, false, $border, 'LTB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Served', '150', null, false, $border, 'LTB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Cancel', '150', null, false, $border, 'LTB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Served', '150', null, false, $border, 'LTBR', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Cancel', '150', null, false, $border, 'TBR', 'C', $font, $fontsize, 'B');
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

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config, count($result));

        $grand_total_customer = 0;
        $grand_total_reg_served = 0;
        $grand_total_reg_cancel = 0;
        $grand_total_pri_served = 0;
        $grand_total_pri_cancel = 0;

        // Group ito per user
        $grouped = [];
        foreach ($result as $row) {
            $grouped[$row->date][$row->users][] = $row;

            if (!isset($summary_per_user[$row->users])) {
            $summary_per_user[$row->users] = [
                'serve' => 0,
                'cancel' => 0,
                'waittime' => 0,
                ];
            }
            $summary_per_user[$row->users]['serve'] += $row->Regular_Serve + $row->Priority_Serve;
            $summary_per_user[$row->users]['cancel'] += $row->Regular_Cancel + $row->Priority_Cancel;
            $summary_per_user[$row->users]['waittime'] += $row->waittime;

        }

        foreach ($grouped as $date => $users) {

        // Date
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($date, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // User
        foreach ($users as $user => $rows) {

            // Header
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('   ' . $user, '1000', null, false, $border, '', 'L', $font, $fontsize, 'B');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // Subtotal (PER USER)
            $total_customer = 0;
            $total_reg_served = 0;
            $total_reg_cancel = 0;
            $total_pri_served = 0;
            $total_pri_cancel = 0;

            foreach ($rows as $data) {

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '50', null, false, $border,'', '', $font, $fontsize);
                $str .= $this->reporter->col('' . $data->service, '200', null, false, $border,'', '', $font, $fontsize);
                $str .= $this->reporter->col($data->customer_count, '150', null, false, $border,'', 'C', $font, $fontsize);
                $str .= $this->reporter->col($data->Regular_Serve, '150', null, false, $border,'', 'C', $font, $fontsize);
                $str .= $this->reporter->col($data->Regular_Cancel, '150', null, false, $border,'', 'C', $font, $fontsize);
                $str .= $this->reporter->col($data->Priority_Serve, '150', null, false, $border,'', 'C', $font, $fontsize);
                $str .= $this->reporter->col($data->Priority_Cancel, '150', null, false, $border,'', 'C', $font, $fontsize);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // Totals
                $total_customer += $data->customer_count;
                $total_reg_served += $data->Regular_Serve;
                $total_reg_cancel += $data->Regular_Cancel;
                $total_pri_served += $data->Priority_Serve;
                $total_pri_cancel += $data->Priority_Cancel;
            }

            // Subtotal
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '250', null, false, $border,'', 'R', $font, $fontsize, 'B');
            $str .= $this->reporter->col($total_customer, '150', null, false, $border,'TB', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->col($total_reg_served, '150', null, false, $border,'TB', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->col($total_reg_cancel, '150', null, false, $border,'TB', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->col($total_pri_served, '150', null, false, $border,'TB', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->col($total_pri_cancel, '150', null, false, $border,'TB', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // Add to grand totals
            $grand_total_customer += $total_customer;
            $grand_total_reg_served += $total_reg_served;
            $grand_total_reg_cancel += $total_reg_cancel;
            $grand_total_pri_served += $total_pri_served;
            $grand_total_pri_cancel += $total_pri_cancel;
            }
        }

        // GRAND TOTAL
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', '250', null, false, $border,'T', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col($grand_total_customer, '150', null, false, $border,'', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col($grand_total_reg_served, '150', null, false, $border,'', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col($grand_total_reg_cancel, '150', null, false, $border,'', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col($grand_total_pri_served, '150', null, false, $border,'', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col($grand_total_pri_cancel, '150', null, false, $border,'', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br/><br/>';

        // SUMMARY PER USER
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUMMARY PER USER', '150', null, false, $border,'TB', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total Serve', '150', null, false, $border,'TB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Total Cancel', '150', null, false, $border,'TB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('Avg Service Time(mins)', '100', null, false, $border,'TB', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('', '400', null, false, $border,'', 'C', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();

        foreach ($summary_per_user as $user => $totals) {
            $wait = 0;
            if($totals['waittime']!=0){
                $wait = round($totals['waittime']/$totals['serve'],0);
            }

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($user, '150', null, false, $border,'', 'L', $font, $fontsize);
            $str .= $this->reporter->col($totals['serve'], '150', null, false, $border,'', 'C', $font, $fontsize);
            $str .= $this->reporter->col($totals['cancel'], '150', null, false, $border,'', 'C', $font, $fontsize);
            $str .= $this->reporter->col($wait, '100', null, false, $border,'', 'C', $font, $fontsize);
            $str .= $this->reporter->col('', '400', null, false, $border,'', 'C', $font, $fontsize, 'B');
            $str .= $this->reporter->endrow();
        }
       
        $str .= $this->reporter->endreport();
        return $str;
    }

    

}//end class