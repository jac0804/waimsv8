<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use Exception;

class entryshowserialnocolor
{

    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'ENGINE#/CHASSIS#';
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

        $column = ['serial', 'chassis','pnp','csr'];

        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $column]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        
        $obj[0][$this->gridname]['columns'][$serial]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$serial]['label'] = 'Engine No.';
        $obj[0][$this->gridname]['columns'][$chassis]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$chassis]['label'] = 'Chassis No.';
        $obj[0][$this->gridname]['columns'][$pnp]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$csr]['type'] = 'label';
        $this->modulename = 'ENGINE#/CHASSIS# - '. $config['params']['row']['barcode'];
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
        $color = $config['params']['row']['color'];
        $wh = $config['params']['row']['whid'];
        
        $company = $config['params']['companyid'];
        $doc = $config['params']['doc'];
        $filter = '';
        
        $qry = "select sin.serial,sin.chassis,sin.pnp,sin.csr
        from rrstatus
        left join serialin as sin on sin.trno = rrstatus.trno and sin.line=rrstatus.line
        left join item on item.itemid=rrstatus.itemid

        where item.barcode = '" . $item . "' and sin.color='".$color."'  and rrstatus.whid = ".$wh."
        and sin.outline =0 $filter
        group by sin.serial,sin.chassis,sin.pnp,sin.csr
        order by serial";

        $this->coreFunctions->create_Elog($qry);
        return $this->coreFunctions->opentable($qry);
    }
}
