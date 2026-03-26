<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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
use DateTime;

class inhouse_sales_report
{
    public $modulename = 'Inhouse Sales Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1200'];

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
        $fields = ['radioprint', 'start', 'end', 'dcentername']; //, 'dbranchname'
        $col1 = $this->fieldClass->create($fields);
        // data_set($col1, 'station_rep.addedparams', ['branch']);
        // data_set($col1, 'station_rep.label', 'Branch');

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];

        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-360) as start,   
        left(now(),10) as end,
          '" . $defaultcenter[0]['center'] . "' as center,
          '" . $defaultcenter[0]['centername'] . "' as centername,
          '" . $defaultcenter[0]['dcentername'] . "' as dcentername");
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
        return $this->reportDefaultLayout($config);
    }



    public function reportDefault($config)
    {
        // QUERY
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        // $center = $config['params']['dataparams']['center'];

        // $station = $config['params']['dataparams']['stationline']; //line sa branchstation
        // $stationname = $config['params']['dataparams']['stationname']; //name ng station
        $center = $config['params']['dataparams']['center'];

        // var_dump($center);

        $filter = "";

        if ($center != "") {
            $filter .= "and center.code = '" . $center . "'  ";
        }

        $query = "select 'pos' as pos, head.branch, branch.clientname as branchname, date(head.dateid) as dateid, head.docno, head.station as terminalno,
                        head.openby as cashier, head.yourref, sum(head.amt) as amt,
                        sum(head.cash) as cash, sum(head.card + head.debit) as card, head.terminalid as cardtype,
                        sum(head.cheque) as finance, head.checktype,
                        ifnull((select sum(s.ext) from stock as s
                        left join item on item.itemid=s.itemid where item.channel='SERVICE' and date(s.dateid)
                        between '$start' and '$end' and s.station=head.station and s.trno=head.trno),0) as service,
                        0 as emppurch, head.deposit as layaway, head.depodetail as laytype, sum(head.debit) as debit

                        from head 
                        left join client on client.clientid=head.clientid
                        left join client as branch on branch.client=head.branch
                        left join center on center.branchid = branch.clientid

                        where head.doc='bp' and date(head.dateid) between '$start' and '$end' $filter

                        group by date(head.dateid), head.docno, head.station, head.openby, head.yourref, head.terminalid,
                        head.checktype,head.branch,branch.clientname,head.trno,head.deposit,head.depodetail
                        order by dateid, terminalno, docno";

        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }

    private function displayHeader($config)
    {
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $currentdate = $this->othersClass->getCurrentTimeStamp();
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        // $letter = $this->reporter->letterhead($center, $username, $config);
        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $border = '1px solid';
        $font = 'calibri';
        $font_size = '9';

        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $dt = new DateTime($currentdate);

        $date = $dt->format('n/j/Y');
        $time = $dt->format('g:i:sa'); //	7/30/2025 7:27:46PM
        $time = strtoupper($time); //  AM/PM (malaking letter)

        $currentdate = $date . ' ' . $time;

        $dates = new DateTime($start);
        $dates2 = new DateTime($end);
        $starts = $dates->format('F j, Y');
        $ends = $dates2->format('F j, Y'); //August 4, 2024 - July 30, 2025	

        $str = '';
        $layoutsize = '1200';
        $str .= $this->reporter->begintable($layoutsize);
        // $letter= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($headerdata[0]->name . ' ' . strtoupper($headerdata[0]->address), '700', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Print Date: ', '390', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($currentdate, '110', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('INHOUSE - SALES REPORT', '1100', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('User: ', '50', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col($username, '50', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col('Date: ', '20', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($starts . ' - ' . $ends, '150', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->pagenumber('Page', '1050', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date', '75', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Terminal No.', '75', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Cashier Name ', '100', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Document No. ', '100', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Cash Amount', '85', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Card Amount', '85', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '5', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Bank Terminal', '80', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Finance Amount', '85', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '5', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Finance Type', '80', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Service Amount', '85', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Employee Purchase', '85', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Layaway Amount', '85', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '5', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Layaway Type', '80', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Total Amount', '85', '', false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $border = '1px solid';
        $font = 'calibri';
        $font_size = '8';
        $count = 35;
        $page = 35;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str = '';

        $layoutsize = '1200';
        $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:75px');
        $str .= $this->displayHeader($config);

        $tlcash = 0;
        $tlcard = 0;
        $tlfinance = 0;
        $tlservice = 0;
        $tlemppurch = 0;
        $tllayaway = 0;
        $tlamt = 0;

        foreach ($result as $key => $data) {
            $bankterminal = '';

            if ($data->cardtype != '') {
                $terminal = $data->cardtype;
                $parts = explode('~', $terminal);
                $firstpart = $parts[2];
                $secondpart = $parts[3];
                $bankterminal = $firstpart . ' ' . $secondpart;
            }

            $ftype = '';

            if ($data->checktype != '') {
                $financetype = $data->checktype;
                $parts = explode('~', $financetype);
                $firstpartz = $parts[0];
                $ftype = $firstpartz;
            }

            $totalamount = $data->cash + $data->card + $data->finance + $data->service + $data->emppurch + $data->layaway;

            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->dateid, '75', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->terminalno, '75', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->cashier, '100', '', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col($data->docno, '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->cash == 0) ? '-' : number_format($data->cash, 2), '85', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->card == 0) ? '-' : number_format($data->card, 2), '85', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '5', '', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($bankterminal == '') ? ' ' : $bankterminal, '80', '', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->finance == 0) ? '-' : number_format($data->finance, 2), '85', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '5', '', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($ftype == '') ? ' ' : ' ' . $ftype, '80', '', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->service == 0) ? '-' : number_format($data->service, 2), '85', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->emppurch == 0) ? '-' : number_format($data->emppurch, 2), '85', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->layaway == 0) ? '-' : number_format($data->layaway, 2), '85', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '5', '', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($data->laytype == '') ? ' ' : ' ' . $data->laytype, '80', '', false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col(($totalamount == 0) ? '-' : number_format($totalamount, 2), '85', '', false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();

            $tlcash += $data->cash;
            $tlcard += $data->card;
            $tlfinance += $data->finance;
            $tlservice += $data->service;
            $tlemppurch += $data->emppurch;
            $tllayaway += $data->layaway;
            $tlamt += $totalamount;

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->displayHeader($config, $layoutsize);
                $page += $count;
            }
        } //end foreach
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('Grand Total: ', '75', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '75', '', false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(($tlcash == 0) ? '-' : number_format($tlcash, 2), '85', '', false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($tlcard == 0) ? '-' : number_format($tlcard, 2), '85', '', false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '5', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(($tlfinance == 0) ? '-' : number_format($tlfinance, 2), '85', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '5', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(($tlservice == 0) ? '-' : number_format($tlservice, 2), '85', '', false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($tlemppurch == 0) ? '-' : number_format($tlemppurch, 2), '85', '', false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($tllayaway == 0) ? '-' : number_format($tllayaway, 2), '85', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '5', '', false, $border, 'TB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', '', false, $border, 'TB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(($tlamt == 0) ? '-' : number_format($tlamt, 2), '85', '', false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class