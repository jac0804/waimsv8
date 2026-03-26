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

class viewsupplierinvoicerritems
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'INVENTORY';
    public $gridname = 'inventory';

    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $hhead = 'glhead';
    public $stock = 'lastock';
    public $hstock = 'glstock';
    public $detail = 'ladetail';
    public $hdetail = 'gldetail';

    public $dqty = 'rrqty';
    public $hqty = 'qty';
    public $damt = 'rrcost';
    public $hamt = 'cost';

    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:1200px;max-width:1200px;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 2240, 'view' => 2240);
        return $attrib;
    }

    public function createTab($config)
    {
        $companyid = $config['params']['companyid'];
        $isexpiry = $this->companysetup->getisexpiry($config['params']);
        $isproject = $this->companysetup->getisproject($config['params']);
        $ispallet = $this->companysetup->getispallet($config['params']);
        $isfa = $this->companysetup->getisfixasset($config['params']);
        $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);

        $docno = 0;
        $itemdesc = 1;
        $rrqty = 2;
        $uom = 3;
        $rrcost = 4;
        $disc = 5;
        $ext = 6;
        $wh = 7;
        $ref = 8;
        $rem = 9;
        $loc = 10;
        $expiry = 11;
        $stage = 12;
        $pallet = 13;
        $location = 14;
        $itemname = 15;

        $column = ['docno', 'itemdesc', 'rrqty', 'uom', 'rrcost', 'disc', 'ext', 'wh', 'ref', 'rem', 'loc', 'expiry', 'stage', 'pallet', 'location', 'itemname'];
        $sortcolumn =  ['docno', 'itemdesc', 'rrqty', 'uom', 'rrcost', 'disc', 'ext', 'wh', 'ref', 'rem', 'loc', 'expiry', 'stage', 'pallet', 'location', 'itemname'];


        $tab = [$this->gridname => [
            'gridcolumns' => $column, 'sortcolumns' => $sortcolumn,
            'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext']
        ]];

        $stockbuttons = ['referencemodule'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0]['inventory']['columns'][$docno]['type'] = 'label';
        $obj[0]['inventory']['columns'][$rrqty]['type'] = 'label';
        $obj[0]['inventory']['columns'][$uom]['type'] = 'label';
        $obj[0]['inventory']['columns'][$rrcost]['type'] = 'label';
        $obj[0]['inventory']['columns'][$disc]['type'] = 'label';
        $obj[0]['inventory']['columns'][$ext]['type'] = 'label';
        $obj[0]['inventory']['columns'][$wh]['type'] = 'label';
        $obj[0]['inventory']['columns'][$ref]['type'] = 'label';
        $obj[0]['inventory']['columns'][$rem]['type'] = 'label';
        $obj[0]['inventory']['columns'][$loc]['type'] = 'label';
        $obj[0]['inventory']['columns'][$expiry]['type'] = 'label';
        $obj[0]['inventory']['columns'][$stage]['type'] = 'label';
        $obj[0]['inventory']['columns'][$pallet]['type'] = 'label';
        $obj[0]['inventory']['columns'][$location]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $obj[0][$this->gridname]['columns'][$disc]['style'] = 'width:50px;whiteSpace: normal;min-width:50px;';

        $obj[0]['inventory']['columns'][$rrcost]['align'] = 'left';

        if ($viewcost == '0') {
            $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
        }

        if (!$isexpiry) {
            $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
            $obj[0]['inventory']['columns'][$expiry]['type'] = 'coldel';
        } else {
            $obj[0]['inventory']['columns'][$loc]['readonly'] = true;
            $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
        }

        if (!$isproject) {
            $obj[0]['inventory']['columns'][$stage]['type'] = 'coldel';
        }

        $obj[0]['inventory']['columns'][$pallet]['type'] = 'coldel';
        if (!$ispallet) {
            $obj[0]['inventory']['columns'][$location]['type'] = 'coldel';
        }

        $obj[0]['inventory']['columns'][$ref]['lookupclass'] = 'refrr';

        $obj[0]['inventory']['columns'][$rrcost]['readonly'] = true;
        $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
        $obj[0]['inventory']['columns'][$stage]['readonly'] = true;

        $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);

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
        return $this->openstock($trno, $config);
    }

    public function openstock($trno, $config)
    {
        $sqlselect = $this->getstockselect($config);

        $qry = $sqlselect . "
      FROM cntnum left join
      $this->hstock as stock on stock.trno=cntnum.trno
      left join item on item.itemid=stock.itemid
      left join model_masterfile as mm on mm.model_id = item.model
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join client as warehouse on warehouse.clientid=stock.whid
      left join pallet on pallet.line=stock.palletid
      left join location on location.line=stock.locid
      left join stagesmasterfile as st on st.line = stock.stageid where cntnum.svnum =? order by line";

        return $this->coreFunctions->opentable($qry, [$trno, $trno]);
    } //end function

    private function getstockselect($config)
    {
        $sqlselect = "select cntnum.doc, cntnum.docno,
      item.brand as brand,
      ifnull(mm.model_name,'') as model,
      item.itemid,
      stock.trno,
      stock.line,
      stock.refx,
      stock.linex,
      item.barcode,
      item.itemname,
      item.itemname as itemdesc,
      stock.uom,
      stock." . $this->hamt . ",
      stock." . $this->hqty . " as qty,
      FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as " . $this->damt . ",
      FORMAT(stock." . $this->dqty . "," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as " . $this->dqty . ",
      FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
      left(stock.encodeddate,10) as encodeddate,
      stock.disc,
      stock.void,
      round((stock." . $this->hqty . "-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
      stock.ref,
      stock.whid,
      warehouse.client as wh,
      warehouse.clientname as whname,
      stock.loc,
      stock.expiry,
      item.brand,
      stock.rem,
      stock.palletid,
      stock.locid,
      ifnull(pallet.name,'') as pallet,
      ifnull(location.loc,'') as location,
      ifnull(uom.factor,1) as uomfactor,stock.fcost,ifnull(stock.stageid,0) as stageid ,ifnull(st.stage,'') as stage,
      '' as bgcolor,
      '' as errcolor, 'SUPPINVOICE' as tabtype ";
        return $sqlselect;
    }
} //end class
