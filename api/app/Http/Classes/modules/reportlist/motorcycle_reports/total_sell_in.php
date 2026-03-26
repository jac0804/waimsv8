<?php

namespace App\Http\Classes\modules\reportlist\motorcycle_reports;

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

class total_sell_in
{
    public $modulename = 'Total Sell In';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1800'];

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
        $fields = ['radioprint', 'start', 'end', 'dclientname'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'default', 'color' => 'red'],
        ]);

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
                '' as client,
                '' as clientname,
                '' as dclientname
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
        $client       = $config['params']['dataparams']['client'];
        $filter = "";

        if ($client != "") {
            $filter .= " and client.client='$client'";
        }

        $query = "
                    select date(head.dateid) as date,ifnull(stock.ref,'') as pono,head.docno as drno,
                    wh.clientname as location,i.itemname as modelname,head.dateid,
                    ss.chassis as chassis,ss.serial as serial,ss.pnp as pnpno,
                    ss.csr as csrno,
                    stock.rrcost as cost,head.yourref,stock.rrqty as qty                   
                    from lahead as head
                    left join lastock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join model_masterfile as model on model.model_id = i.model
                    left join itemcategory as grp on grp.line = i.category
                    left join client on client.client = head.client
                    left join client as wh on wh.clientid = stock.whid
                    left join serialin as ss on ss.trno = stock.trno and ss.line = stock.line
                    where head.doc='RR' and grp.name='MC UNIT' and head.dateid between '$start' and '$end'   $filter 
                    
                    union all
                    
                    select date(head.dateid) as date,ifnull(stock.ref,'') as pono,head.docno as drno,
                    wh.clientname as location,i.itemname as modelname,head.dateid,
                    ss.chassis as chassis,ss.serial as serial,ss.pnp as pnpno,
                    ss.csr as csrno,
                    stock.rrcost as cost,head.yourref,stock.rrqty as qty               
                    from glhead as head
                    left join glstock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=stock.itemid
                    left join model_masterfile as model on model.model_id = i.model
                    left join itemcategory as grp on grp.line = i.category
                    left join client on client.clientid = head.clientid
                    left join client as wh on wh.clientid = stock.whid
                    left join serialin as ss on ss.trno = stock.trno and ss.line = stock.line
                    where head.doc='RR' and grp.name='MC UNIT' and head.dateid between '$start' and '$end'   $filter  order by dateid,modelname ";

        return $query;
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);

        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $layoutsize = '1800';
        $totalcost = 0;


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
                $str .= $this->reporter->col($data->date, '80', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pono, '175', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->drno, '175', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->location, '140', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->modelname, '210', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->chassis, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->serial, '160', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pnpno, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->csrno, '190', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->qty, $decimal), '120', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col(number_format($data->cost, $decimal), '120', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $totalcost = $totalcost + $data->cost;
            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total: ', '1656', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalcost, $decimal), '144', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client       = $config['params']['dataparams']['clientname'];

        $str = '';
        $layoutsize = '1800';

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
        $str .= $this->reporter->col($this->modulename, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '1800', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        if ($client == '') {
            $str .= $this->reporter->col('Supplier: ALL', '1800', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Supplier: ' . $client, '1800', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        }
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
        $layoutsize = '1800';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PO#', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DR #', '175', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('LOCATION', '140', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MODEL', '210', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CHASSIS', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ENGINE', '160', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PNP', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CSR', '190', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('YOURREF', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TOTAL UNIT', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('COST', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class