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

class viewannualsales
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Annual Sales Statistics';
    public $gridname = 'inventory';
    private $coreFunctions;
    public $style = 'width:80px;max-width:80px';
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
         $columns = ['year', 'total', 'totalcost'];

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
                round(sum(amt) / 12, 2) as totalcost
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