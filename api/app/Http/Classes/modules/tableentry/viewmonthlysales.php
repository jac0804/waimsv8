<?php

namespace App\Http\Classes\modules\tableentry;

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

class viewmonthlysales
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Monthly Sales Statistics';
    public $gridname = 'inventory';
    private $coreFunctions;
    public $style = 'width:100%';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $showclosebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
         $columns = ['year', 'total', 'totalcost', 'janamt', 'febamt', 'maramt', 'apramt', 'mayamt', 'junamt', 'julamt', 'augamt', 'sepamt', 'octamt', 'novamt', 'decamt'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns ]];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
    

        $obj[0][$this->gridname]['columns'][$year]['label'] = "Year";
        $obj[0][$this->gridname]['columns'][$year]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$year]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$year]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$total]['label'] = "Total Sales";
        $obj[0][$this->gridname]['columns'][$total]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$total]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$total]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;';

        $obj[0][$this->gridname]['columns'][$totalcost]['label'] = "Ave. Sales";
        $obj[0][$this->gridname]['columns'][$totalcost]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$totalcost]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$totalcost]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$janamt]['label'] = "Jan";
        $obj[0][$this->gridname]['columns'][$janamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$janamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$janamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$febamt]['label'] = "Feb";
        $obj[0][$this->gridname]['columns'][$febamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$febamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$febamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$maramt]['label'] = "Mar";
        $obj[0][$this->gridname]['columns'][$maramt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$maramt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$maramt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$apramt]['label'] = "Apr";
        $obj[0][$this->gridname]['columns'][$apramt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$apramt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$apramt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$mayamt]['label'] = "May";
        $obj[0][$this->gridname]['columns'][$mayamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$mayamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$mayamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$junamt]['label'] = "Jun";
        $obj[0][$this->gridname]['columns'][$junamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$junamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$junamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$julamt]['label'] = "Jul";
        $obj[0][$this->gridname]['columns'][$julamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$julamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$julamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$augamt]['label'] = "Aug";
        $obj[0][$this->gridname]['columns'][$augamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$augamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$augamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$sepamt]['label'] = "Sep";
        $obj[0][$this->gridname]['columns'][$sepamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$sepamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$sepamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$octamt]['label'] = "Oct";
        $obj[0][$this->gridname]['columns'][$octamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$octamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$octamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$novamt]['label'] = "Nov";
        $obj[0][$this->gridname]['columns'][$novamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$novamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$novamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$decamt]['label'] = "Dec";
        $obj[0][$this->gridname]['columns'][$decamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$decamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$decamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['tableid'];
        $qry = "select
                year(dateid) as year,
                round(sum(amt), 2) as total,
                round(sum(amt) / 12, 2) as totalcost,
                case when sum(if(month(dateid) = 1,  amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 1,  amt, 0)), 2) end as janamt,
                case when sum(if(month(dateid) = 2,  amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 2,  amt, 0)), 2) end as febamt,
                case when sum(if(month(dateid) = 3,  amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 3,  amt, 0)), 2) end as maramt,
                case when sum(if(month(dateid) = 4,  amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 4,  amt, 0)), 2) end as apramt,
                case when sum(if(month(dateid) = 5,  amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 5,  amt, 0)), 2) end as mayamt,
                case when sum(if(month(dateid) = 6,  amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 6,  amt, 0)), 2) end as junamt,
                case when sum(if(month(dateid) = 7,  amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 7,  amt, 0)), 2) end as julamt,
                case when sum(if(month(dateid) = 8,  amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 8,  amt, 0)), 2) end as augamt,
                case when sum(if(month(dateid) = 9,  amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 9,  amt, 0)), 2) end as sepamt,
                case when sum(if(month(dateid) = 10, amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 10, amt, 0)), 2) end as octamt,
                case when sum(if(month(dateid) = 11, amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 11, amt, 0)), 2) end as novamt,
                case when sum(if(month(dateid) = 12, amt, 0)) = 0 then '-' else round(sum(if(month(dateid) = 12, amt, 0)), 2) end as decamt
            from (
                select stock.ext as amt, head.dateid as dateid
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join client as cl on cl.client = head.client
                where head.doc = 'SJ' and cl.clientid = $clientid

                union all

                select if(head.doc = 'CM', -stock.ext, stock.ext) as amt, head.dateid as dateid
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join client as cl on cl.clientid = head.clientid
                where head.doc in ('SJ', 'CM') and cl.clientid = $clientid
            ) as base
            group by year(dateid)
            order by year(dateid)";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class