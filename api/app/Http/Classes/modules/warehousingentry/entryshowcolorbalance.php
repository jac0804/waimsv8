<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use Exception;

class entryshowcolorbalance
{

    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'COLOR BALANCE';
    public $gridname = 'inventory';
    private $fields = ['barcode', 'itemname'];
    private $table = 'lastock';

    public $tablelogs = 'table_log';
    private $logger;

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
        $attrib = array('load' => 4452);
        return $attrib;
    }

    public function createTab($config)
    {

        $column = ['action', 'color', 'bal', 'barcode'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $column]];

        $stockbuttons = ['listingshowserialnocolor'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'hidden';
        $obj[0][$this->gridname]['columns'][$barcode]['label'] = '';
        $obj[0][$this->gridname]['columns'][$color]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$bal]['type'] = 'label';
        $itemname = $this->coreFunctions->getfieldvalue("item", "itemname", "barcode=?", [$config['params']['row']['barcode']]);
        $this->modulename = 'COLOR BALANCE - ' . $config['params']['row']['barcode'] . '-' . $itemname;
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
        $item = $config['params']['row']['barcode'];
        $wh = $config['params']['row']['wh'];
        $company = $config['params']['companyid'];
        $doc = $config['params']['doc'];
        $filter = '';

        $qry = "select item.barcode,sin.color,count(sin.color) as bal,rrstatus.whid
        from rrstatus
        left join serialin as sin on sin.trno = rrstatus.trno and sin.line=rrstatus.line
        left join item on item.itemid=rrstatus.itemid
        left join client as wh on wh.clientid = rrstatus.whid

        where item.barcode = '" . $item . "' and wh.client ='" . $wh . "' and sin.outline =0 $filter
        group by item.barcode,sin.color,rrstatus.whid
        order by color";

        $this->coreFunctions->create_Elog($qry);
        return $this->coreFunctions->opentable($qry);
    }
}
