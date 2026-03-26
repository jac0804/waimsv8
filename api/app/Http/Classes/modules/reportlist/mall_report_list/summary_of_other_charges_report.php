<?php

namespace App\Http\Classes\modules\reportlist\mall_report_list;

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

class summary_of_other_charges_report
{
    public $modulename = 'Summary of Other Charges Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];



    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint', 'radioreporttype', 'month', 'byear', 'chargedesc'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ]);

        data_set($col1, 'month.type', 'lookup');
        data_set($col1, 'month.readonly', true);
        data_set($col1, 'month.action', 'lookuprandom');
        data_set($col1, 'month.lookupclass', 'lookup_month');
        data_set($col1, 'byear.readonly', false);
        data_set($col1, 'byear.name', 'byear');
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS

        $paramstr = "select 
        'default' as print,
          date_format(now(), '%M') as month,
            month(now()) as bmonth,
            year(now()) as byear,
            '' as chargedesc,
            '' as line,
            '0' as reporttype
           ";

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
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }

    public function reportplotting($config)
    {
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case 0: // summarized
                $result = $this->reportDefaultLayout_summarized($config);
                break;
            case 1: // detailed
                $result = $this->reportDefaultLayout_detailed($config);
                break;
        }

        return $result;
    }

    public function report_qry_Default_detailed($config)
    {
        $bmonth = $config['params']['dataparams']['bmonth'];
        $year = $config['params']['dataparams']['byear'];
        $chargess = $config['params']['dataparams']['chargedesc'];
        $line = $config['params']['dataparams']['line'];
        $filter1 = "";

        if ($chargess != "") {
            $filter1 = "and o.line='$line'";
        }

        $query = "  select description,tenantcode,tenantname,loc,sum(amt) as amt  from (
                     select o.line,o.description,client.client as tenantcode, client.clientname as tenantname,
                     loc.code as loc,charge.amt from chargesbilling as charge
                            left join ocharges as o on o.line = charge.cline
                            left join client on client.clientid=charge.clientid
                            left join loc as loc on loc.line = client.locid  where charge.bmonth='" . $bmonth . "' and charge.byear='" . $year . "'
                            and ifnull(o.description,'')<>'' $filter1
                            group by o.line,o.description,client.client, client.clientname ,loc.code,charge.amt
        
                            union all
                
                      select o.line,o.description,client.client as tenantcode, client.clientname as tenantname,
                      loc.code as loc,hcharge.amt from hchargesbilling as hcharge
                            left join ocharges as o on o.line = hcharge.cline
                            left join client on client.clientid=hcharge.clientid
                            left join loc as loc on loc.line = client.locid  where hcharge.bmonth='" . $bmonth . "' and hcharge.byear='" . $year . "'
                            and ifnull(o.description,'')<>'' $filter1
                            group by o.line,o.description,client.client, client.clientname ,loc.code,hcharge.amt) as s
                            group by description,tenantcode,tenantname,loc";

        return $this->coreFunctions->opentable($query);
    }



    private function default_header_detailed($config)
    {

        $center     = $config['params']['center'];
        $str = '';
        $layoutsize = '1000';
        $font = "Arial";
        $fontsize = "10";
        $border = "1px solid ";
        $bmonth = $config['params']['dataparams']['month'];
        $year = $config['params']['dataparams']['byear'];


        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '1000', null, false, '1px solid ', '', 'C', $font, '15', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), '1000', null, false, '1px solid ', '', 'C', $font, '12', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUMMARY OF OTHER CHARGES', '1000', null, false, $border, '', 'C', $font, '15', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('for the month of ' . $bmonth . ',' . $year, '1000', null, false, $border, '', 'C', $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br><br>';
        $date1 = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date :' . date('m/d/Y h:i:s a', strtotime($date1)), '500', null, false, $border, '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, '', 'R', $font, '10', '', 'b', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Description', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Tenant Code', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Tenant Name', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Loc', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Amount', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
    private function reportDefaultLayout_detailed($config)
    {
        $result     = $this->report_qry_Default_detailed($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $str = '';
        $count = 60; #60
        $page = 59; #59
        $layoutsize = '1000';
        $font = "Arial";
        //  change to 10 $fontsize = "11";
        $fontsize = "10";
        $border = "1px solid ";
        $total = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header_detailed($config);
        $charge = '';
        $i = 0;
        foreach ($result as $key => $data) {
            if ($charge != '' && $charge != ($data->description)) {
                TotalHere:
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '450', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', 'b', '');
                $str .= $this->reporter->col($charge . ' ' . 'Total:', '250', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, $font, $fontsize, 'B');
                $str .= $this->reporter->col(number_format($total, 2), '300', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= '<br/>';
                $total = 0;
                if ($i == (count((array)$result) - 1)) {
                    break;
                }
                $str .= $this->reporter->addline();
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header_detailed($config);
                    $page = $page + $count;
                }
            }

            if ($charge == '' || $charge != ($data->description)) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->description, '300', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '700', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->addline();
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header_detailed($config);
                    $page = $page + $count;
                }
            }

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->tenantcode, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->tenantname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->amt, '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $total += $data->amt;
            $charge = $data->description;
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_header_detailed($config);
                $page = $page + $count;
            }

            if ($i == (count((array)$result) - 1)) {
                goto TotalHere;
            }
            $i++;
        }
        $str .= $this->reporter->endreport();
        return $str;
    }


    public function report_qry_Default_summary($config)
    {
        $bmonth = $config['params']['dataparams']['bmonth'];
        $year = $config['params']['dataparams']['byear'];
        $chargess = $config['params']['dataparams']['chargedesc'];
        $line = $config['params']['dataparams']['line'];


        $filter1 = "";

        if ($chargess != "") {
            $filter1 = "and o.line='$line'";
        }
        $query = " select description,sum(amt) as amt 
                from (
                select o.description,sum(charge.amt) as amt from chargesbilling as charge
                                left join ocharges as o on o.line = charge.cline
                                where charge.bmonth='" . $bmonth . "' and charge.byear='" . $year . "'
                                and ifnull(o.description,'')<>''  $filter1
                                group by o.description
                                union all
                select o.description,sum(hcharge.amt) as amt from hchargesbilling as hcharge
                                left join ocharges as o on o.line = hcharge.cline
                                where hcharge.bmonth='" . $bmonth . "' and hcharge.byear='" . $year . "'
                                 and ifnull(o.description,'')<>''  $filter1
                                group by o.description
                                ) as s
                                group by description";
        return $this->coreFunctions->opentable($query);
    }


    private function displayHeader_summarized($config)
    {

        $center     = $config['params']['center'];
        $str = '';
        $layoutsize = '1000';
        $font = "Arial";
        $fontsize = "10";
        $border = "1px solid ";

        $bmonth = $config['params']['dataparams']['month'];
        $year = $config['params']['dataparams']['byear'];


        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), '1000', null, false, '1px solid ', '', 'C', $font, '15', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), '1000', null, false, '1px solid ', '', 'C', $font, '12', '', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUMMARY OF OTHER CHARGES', '1000', null, false, $border, '', 'C', $font, '15', 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('for the month of ' . $bmonth . ',' . $year, '1000', null, false, $border, '', 'C',  $font, '11', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $date1 = $this->othersClass->getCurrentTimeStamp();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Run Date :' . date('m/d/Y h:i:s a', strtotime($date1)), '500', null, false, $border, '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->pagenumber('Page', '500', null, false, $border, '', 'R', $font, '10', '', 'b', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= '<br><br>';



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUMMARY', '500', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Description', '500', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }



    public function reportDefaultLayout_summarized($config)
    {
        $result     = $this->report_qry_Default_summary($config);
        $str = '';
        $layoutsize = '1000';
        $font = "Arial";
        $fontsize = "10";
        $border = "1px solid ";
        $count = 51;
        $page = 50;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader_summarized($config);

        $charge = '';
        $gtotal = 0;
        foreach ($result as $key => $data) {
            if ($charge == '' || ($charge == $data->description && $data->description != '')) {
                if ($charge != $data->description) {
                    starthere:
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->description, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->amt, '250', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '500', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $gtotal += $data->amt;
                } else if ($charge == $data->clientname) {
                    $charge = $data->clientname;
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->description, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col($data->amt, '250', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->col('', '500', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                    $gtotal += $data->amt;
                } else {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('<br>', null, null, false, $border, 'LR', 'L', $font, '12', 'B');
                    $str .= $this->reporter->endrow();
                }
            } else {
                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->displayHeader_summarized($config);
                    goto   starthere;
                } else {
                    $str .= $this->reporter->page_break();
                    $str .= $this->displayHeader_summarized($config);
                    goto   starthere;
                }
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Grandtotal', '250', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($gtotal, 2), '250', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '500', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}
