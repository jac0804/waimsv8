<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use Exception;

class entrywhchecker
{
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'ITEM DETAILS';
    public $gridname = 'inventory';
    private $fields = ['barcode', 'itemname'];
    private $table = 'lastock';

    public $tablelogs = 'table_log';

    public $style = 'width:100%;';
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
        $attrib = array('load' => 2030, 'edit' => 2030);
        return $attrib;
    }

    public function createTab($config)
    {
        $action = 0;
        $replaceqty = 1;
        $isqty = 2;
        $barcode = 3;
        $itemdesc = 4;
        $uom = 5;
        $uom = 5;
        $uom = 5;
        $subcode = 6;
        $partno = 7;
        $model_name = 8;
        $dqty = 9;

        $column = ['action', 'replaceqty', 'isqty', 'barcode', 'itemdesc', 'uom', 'subcode', 'partno', 'model_name', 'dqty'];
        $sortcolumn = ['action', 'replaceqty', 'isqty', 'barcode', 'itemdesc', 'uom', 'subcode', 'partno', 'model_name', 'dqty'];

        $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $sortcolumn]];

        $trno = $config['params']['tableid'];
        $posted = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$trno]);
        if ($posted) {
            $stockbuttons = [];
        } else {
            $stockbuttons = ['save'];
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px; max-width:40px;';
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $obj[0][$this->gridname]['columns'][$barcode]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:250px;whiteSpace: normal;min-width:250px; max-width:250px;';
        $obj[0][$this->gridname]['columns'][$itemdesc]['align'] = 'text-left';

        $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'text-align:right;width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $obj[0][$this->gridname]['columns'][$isqty]['align'] = 'text-right';
        $obj[0][$this->gridname]['columns'][$isqty]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$isqty]['label'] = 'DR Qty';

        $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:50px;whiteSpace: normal;min-width:50px; max-width:50px;';
        $obj[0][$this->gridname]['columns'][$uom]['align'] = 'text-left';
        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$replaceqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';

        $obj[0]['inventory']['columns'][$partno]['label'] = 'Part No.';
        $obj[0]['inventory']['columns'][$partno]['type'] = 'label';
        $obj[0]['inventory']['columns'][$partno]['align'] = 'left';
        $obj[0]['inventory']['columns'][$partno]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

        $obj[0]['inventory']['columns'][$subcode]['label'] = 'Old SKU';
        $obj[0]['inventory']['columns'][$subcode]['type'] = 'label';
        $obj[0]['inventory']['columns'][$subcode]['align'] = 'left';
        $obj[0]['inventory']['columns'][$subcode]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

        $obj[0]['inventory']['columns'][$dqty]['label'] = 'Per Box QTY';
        $obj[0]['inventory']['columns'][$dqty]['type'] = 'label';
        $obj[0]['inventory']['columns'][$dqty]['align'] = 'left';
        $obj[0]['inventory']['columns'][$dqty]['style'] = 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px';

        $obj[0]['inventory']['columns'][$model_name]['type'] = 'label';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    private function selectqry($config)
    {
        $dec = $this->companysetup->getdecimal('qty', $config['params']);
        $qry = "stock.line,stock.trno,stock.itemid,item.barcode,item.itemname as itemdesc,
        round(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        round(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss, stock.uom,
        stock.isamt, stock.disc,
        (case when stock.void = 1 then 'true' else 'false' end) as void, stock.whid,
        stock.pickerid, ifnull(client.clientname,'') as picker, stock.locid, loc.loc as location, 
        stock.palletid, pallet.name as pallet, head.doc, stock.refx, stock.linex,
        (case when stock.pickerstart is null then 'false' else 'true' end) as ispicked, 
        round(ifnull((select sum(isqty) from replacestock as rep where rep.trno=stock.trno and rep.line=stock.line and rep.isaccept=0),0)," . $dec . ") as replaceqty, 
        round(ifnull((select sum(qa) from replacestock as rep where rep.trno=stock.trno and rep.line=stock.line and rep.isaccept=0),0)," . $dec . ") as qa, 
        item.subcode, item.partno, ifnull(model.model_name,'') as model_name,
        round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as dqty";
        return $qry;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['tableid'];

        $posted = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$trno]);

        $stocktable = $this->table;
        $headtable = 'lahead';
        $voidtable = 'voidstock';
        if ($posted) {
            $stocktable = 'glstock';
            $headtable = 'glhead';
            $voidtable = 'hvoidstock';
        }

        $select = $this->selectqry($config);
        $select = $select . ",'' as bgcolor ";

        $qry = "
        select " . $select . ", null as returndate 
        from " . $stocktable . "  as stock
        left join item on item.itemid=stock.itemid
        left join client on client.clientid=stock.pickerid
        left join location as loc on loc.line=stock.locid
        left join pallet on pallet.line=stock.palletid
        left join model_masterfile as model on model.model_id = item.model
        left join " . $headtable . " as head on head.trno=stock.trno
        where stock.trno=? 
        union all
        select " . $select . ", stock.returndate 
        from " . $voidtable . "  as stock
        left join item on item.itemid=stock.itemid
        left join client on client.clientid=stock.pickerid
        left join location as loc on loc.line=stock.locid
        left join pallet on pallet.line=stock.palletid
        left join model_masterfile as model on model.model_id = item.model
        left join " . $headtable . " as head on head.trno=stock.trno
        where stock.trno=? and stock.void=0
        order by line";
        return $this->coreFunctions->opentable($qry, [$trno, $trno]);
    }

    public function save($config)
    {
        $row = $config['params']['row'];

        $ispick = $this->coreFunctions->datareader("select  ifnull(checkerrcvdate,'') as value from cntnuminfo where trno=?", [$row['trno']]);
        if ($ispick === '') {
            return ['status' => false, 'msg' => 'Please click the PICK FROM LOCATION button first to proceed.'];
        }

        $replaceqty = $this->othersClass->sanitizekeyfield('qty', $row['replaceqty']);
        $isqty = $this->othersClass->sanitizekeyfield('qty', $row['isqty']);

        if ($replaceqty > $isqty) {
            return ['status' => false, 'msg' => 'Replacement qty mmust greater than DR qty.'];
        }

        if ($row['qa'] != 0) {
            return ['status' => false, 'msg' => 'Unable to update replacement qty; it has already been processed by the picker.'];
        }

        $data = [
            'trno' => $row['trno'],
            'line' => $row['line'],
            'isqty' => $replaceqty,
            'locid' => $row['locid'],
            'palletid' => $row['palletid'],
            'dateid' => $this->othersClass->getCurrentTimeStamp(),
            'user' => $config['params']['user']
        ];

        $exist = $this->coreFunctions->opentable('select trno from replacestock where trno=? and line=?', [$row['trno'], $row['line']]);
        if ($exist) {
            if ($replaceqty == 0) {
                $this->coreFunctions->execqry('delete from replacestock where trno=? and line=?', 'delete', [$row['trno'], $row['line']]);
            } else {
                $this->coreFunctions->sbcupdate('replacestock', ['isqty' => $replaceqty], ['trno' => $row['trno'], 'line' => $row['line']]);
            }
        } else {
            $this->coreFunctions->sbcinsert('replacestock', $data);
        }

        $returnrow = $this->loaddataperrecord($config, $row['trno'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    }

    public function loaddataperrecord($config, $trno, $line)
    {
        $select = $this->selectqry($config);
        $select = $select . ",'' as bgcolor ";

        $qry = "
        select " . $select . ", null as returndate 
        from " . $this->table . "  as stock
        left join item on item.itemid=stock.itemid
        left join client on client.clientid=stock.pickerid
        left join location as loc on loc.line=stock.locid
        left join pallet on pallet.line=stock.palletid
        left join lahead as head on head.trno=stock.trno
        left join replacestock as rep on rep.trno=stock.trno and rep.line=stock.line
        left join model_masterfile as model on model.model_id = item.model
        where stock.trno=? and stock.line=?
        union all
        select " . $select . ", stock.returndate 
        from voidstock  as stock
        left join item on item.itemid=stock.itemid
        left join client on client.clientid=stock.pickerid
        left join location as loc on loc.line=stock.locid
        left join pallet on pallet.line=stock.palletid
        left join lahead as head on head.trno=stock.trno
        left join replacestock as rep on rep.trno=stock.trno and rep.line=stock.line
        left join model_masterfile as model on model.model_id = item.model
        where stock.trno=? and stock.line=?
        order by line";
        return $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    }
}
