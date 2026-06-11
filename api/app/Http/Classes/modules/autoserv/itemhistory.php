<?php

namespace App\Http\Classes\modules\autoserv;

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

class itemhistory
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'ITEM HISTORY';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sqlquery;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $detail = '';
    public $prefix = '';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = '';


    private $fields = [];
    private $except = [];
    public $showfilteroption = false;
    public $showfilter = false;
    public $showcreatebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->sqlquery = new sqlquery;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5912,
            'view' => 5913,
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $getcols = ['listdocument', 'dateid', 'clientname', 'barcode', 'itemname', 'plateno',  'vehicle', 'isqty', 'amount', 'ext'];
        foreach ($getcols as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = [];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[0]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$clientname]['label'] = 'Customer Name';
        $cols[$itemname]['label'] = 'Itemname';
        $cols[$itemname]['type'] = 'label';
        $cols[$dateid]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$amount]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$ext]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;';
        $cols[$isqty]['style'] = 'text-align:right;';
        $cols[$ext]['style'] = 'text-align:right;';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['docno', 'clientname', 'barcode', 'itemname', 'plateno',  'vehicle'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }
        $fsearch = "";

        if ($filtersearch != "") {
            $fsearch =  'where 1=1 ' . $filtersearch;
        }
        $qry = " 
        select docno,dateid,clientname,barcode,itemname,plateno,vehicle,isqty,uom,amount,ext from (
        select head.docno,date(head.dateid) as dateid, cl.clientname, item.barcode, item.itemname, '' as plateno, car.carname as vehicle, FORMAT(stock.isqty,2) as isqty, stock.uom,FORMAT(stock.isamt,2) as amount, FORMAT(stock.ext,2) as ext from " . $this->head . " as head
        left join client as cl on cl.client = head.client
        left join ptstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join cmake as car on car.id = head.carid
        where  head.doc ='AM' 
        union all 
        select head.docno,date(head.dateid) as dateid, cl.clientname, item.barcode, item.itemname, '' as plateno, car.carname as vehicle, FORMAT(stock.isqty,2) as isqty, stock.uom,FORMAT(stock.isamt,2) as amount, FORMAT(stock.ext,2) as ext from " . $this->hhead . " as head
        left join client as cl on cl.clientid = head.clientid
        left join ptstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join cmake as car on car.id = head.carid
        where  head.doc ='AM' 
        ) as x $fsearch
        order by docno";
        $data = $this->coreFunctions->opentable($qry);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        return [];
    } // createHeadbutton

    public function createTab($access, $config) {}

    public function createtabbutton($config)
    {

        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {
        $fields = [];
        return array();
    }
} //end class
