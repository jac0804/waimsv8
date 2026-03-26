<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use Exception;

class entryshowbalance
{

    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'SHOW BALANCE';
    public $gridname = 'inventory';
    private $fields = ['barcode', 'itemname'];
    private $table = 'lastock';

    public $tablelogs = 'table_log';

    public $style = 'width:1100px;min-width:1100px;max-width:1100px;';
    public $showclosebtn = true;

    public function __construct()
    {
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->warehousinglookup = new warehousinglookup;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 3626);
        return $attrib;
    }

    public function createTab($config)
    {

        $column = ['action', 'wh', 'whname', 'loc', 'expiry', 'bal', 'uom'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $column]];

        $stockbuttons = ['showcolor'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$wh]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$whname]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$loc]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$expiry]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$bal]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';

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
        $trno = $config['params']['row']['itemid'];
        $company = $config['params']['companyid'];
        $doc = $config['params']['doc'];
        $filter = '';
        if ($company == 1) { //vitaline
            if ($doc == 'SJ') {
                $filter = 'and rrstatus.whid!=1831';
            }
        }
        $qry = "select wh.client as wh,wh.clientname as whname,rrstatus.loc,
        FORMAT(sum(rrstatus.bal),2) as bal,rrstatus.expiry,item.uom,il.min,il.max,
        ifnull(pallet.`name`,'') as pallet, ifnull(location.loc,'') as location
        from rrstatus
        left join client as wh on wh.clientid = rrstatus.whid
        left join item on item.itemid=rrstatus.itemid left join itemlevel as il on il.itemid = item.itemid and il.center = wh.client
        left join pallet on pallet.line=rrstatus.palletid
        left join location on location.line=rrstatus.locid
        where item.itemid = " . $trno . " and rrstatus.bal>0 $filter
        group by wh.client,wh.clientname,rrstatus.loc,rrstatus.expiry,item.uom,il.min,il.max,pallet.`name`,location.loc";

        return $this->coreFunctions->opentable($qry);
    }
}
