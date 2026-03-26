<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use Datetime;
use DateInterval;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\lookup\constructionlookup;
use App\Http\Classes\lookup\warehousinglookup;

class  tabstocksoinfo
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'STOCK SO';
    public $tablenum = 'transnum';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'omstock';
    private $htable = 'homstock';
    private $othersClass;
    public $style = 'width:100%;max-width: 100%';
    private $fields = ['trno', 'line', 'rrqty', 'unit', 'uom', 'rrcost', 'ext', 'sodetails'];
    private $Stocksfields = ['trno', 'line', 'status', 'suppid'];
    public $showclosebtn = true;
    public $issearchshow = true;
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    private $constructionlookup;
    private $sqlquery;
    private $logger;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->constructionlookup = new constructionlookup;
        $this->sqlquery = new sqlquery;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = [
            'load' => 0
        ];
        return $attrib;
    }
    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1,);
    }

    public function createTab($config)
    {
        $action = 0;
        $rrqty = 1;
        $uom = 2;
        $rrcost = 3;
        $ext = 4;
        $sodetails = 5;
        $columns = ['action', 'rrqty', 'uom', 'rrcost', 'ext', 'sodetails'];
        $stockbuttons = ['viewentrysoposted'];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$rrqty]['type'] =  'label';
        $obj[0][$this->gridname]['columns'][$uom]['type'] =  'label';
        $obj[0][$this->gridname]['columns'][$rrcost]['type'] =  'label';
        $obj[0][$this->gridname]['columns'][$ext]['type'] =  'label';
        $obj[0][$this->gridname]['columns'][$sodetails]['type'] =  'label';
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
        $trno = $config['params']['tableid'];
        $qry = "select (select group_concat('SO#:',sono,' (Qty:',qty,')' SEPARATOR '') 
                        from homso 
                        where homso.trno=stock.trno and homso.line=stock.line) as sodetails,
                        stock.qty,FORMAT(stock.rrcost,5) as rrcost,FORMAT(stock.rrqty,2)  as rrqty ,
                        FORMAT(stock.ext,5) as ext,ifnull(xinfo.unit,'') as unit,stock.trno,stock.line
                from $this->htable as stock
                left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
                where stock.trno = ?  ";

        $data = $this->coreFunctions->opentable($qry, [$trno]);
        return $data;
    }
}
