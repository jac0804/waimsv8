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
use DateTime;


class accounts_receivable_reports
{
    public $modulename = 'Accounts Receivable Reports';
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

        $fields = ['radioprint', 'start',  'dclientname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.label', 'As of');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'dclientname.lookupclass', 'rcustomer');

        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1,  'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-30) as start,
        '' as client,
        '' as clientname,
        '' as dclientname";

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
        $result = $this->reportDefaultLayout($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->default_query($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_query($config)
    {

        $asof      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $client    = $config['params']['dataparams']['client'];
        $filter = "";

        if ($client != "") {
            $filter .= " and client.client='$client'";
        }

        // if ($filtercenter != "") {
        //     $filter .= " and cntnum.center='$filtercenter'";
        // }

        $query = "select clientname,sum(balance) as balance from (
              select client.clientname, sum(stock.ext) as balance
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              where head.doc ='cm' and head.dateid <= '" . $asof . "' " . $filter . " 
              group by client.clientname
              union all
              select client.clientname,sum(detail.db-detail.cr) as balance
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno
              where left(coa.alias,2)='AR' and head.dateid <= '" . $asof . "' " . $filter . " 
              group by client.clientname
              union all
              select client.clientname,
              sum(case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance
              from arledger as detail
              left join client on client.clientid=detail.clientid
              left join cntnum on cntnum.trno=detail.trno
              left join glhead as head on head.trno=detail.trno
              where detail.bal<>0 and iscustomer = 1 and head.dateid <= '" . $asof . "' " . $filter . " 
              group by client.clientname
              order by clientname) as x
           group by clientname
           order by clientname";
        return $query;
    }

    private function default_displayHeader($config)
    {
        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '12';


        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $str = '';
        $layoutsize = '1000';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Accounts Receivable Reports', null, null, false, $border, '', 'C', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $startdate = $start;
        $startt = new DateTime($startdate);
        $start = $startt->format('F d, Y'); // September 13,2025

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font,  $font_size + 2, '', '');
        $str .= $this->reporter->col('As of ' . $start, null, null, '', $border, '', 'C', $font,  $font_size + 2, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER', '500', '', '', $border, 'TB', 'L', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->col('ACCOUNT BALANCE', '250', '', '', $border, 'TB', 'R', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->col('EFFECTIVE BALANCE', '250', '', '', $border, 'TB', 'R', $font,  $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();

        return $str;
    }

    public function reportDefaultLayout($config)
    {

        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '11';

        $result = $this->reportDefault($config);
        $count = 50;
        $page = 50;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_displayHeader($config);

        foreach ($result as $key => $data) {
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->clientname, '500', null, false, '1px dotted ', '', 'L', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->balance, 2), '250', null, false, '1px dotted ', '', 'R', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->col(number_format($data->balance, 2), '250', null, false, '1px dotted ', '', 'R', $font, $font_size, '', '', '', '');
            $str .= $this->reporter->endrow();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_displayHeader($config);
                $page = $page + $count;
            }
        }
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class