<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use Exception;

class entrywhcheckerreplacement
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
        $attrib = array('load' => 2030, 'edit' => 2030);
        return $attrib;
    }

    public function createTab($config)
    {
        $isaccept = 0;
        $ispicked = 1;
        $replaceqty = 2;
        $barcode = 3;
        $itemdesc = 4;
        $uom = 5;

        $column = ['isaccept', 'ispicked', 'replaceqty', 'barcode', 'itemdesc', 'uom'];
        $sortcolumn = ['isaccept', 'ispicked', 'replaceqty', 'barcode', 'itemdesc', 'uom'];

        $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $sortcolumn]];

        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$replaceqty]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$isaccept]['style'] = 'width:30px;min-width:30px;max-width:30px;';
        $obj[0][$this->gridname]['columns'][$ispicked]['style'] = 'width:30px;min-width:30px;max-width:30px;';
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:100px;min-width:100px;max-width:100px;';
        $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:200px;min-width:200px;max-width:200px;';
        $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:40px;min-width:40px;max-width:40px;';

        $obj[0][$this->gridname]['columns'][$replaceqty]['align'] = 'text-left';

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['saveallentry'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    private function selectqry($config)
    {
        $qry = "stock.line,stock.trno,stock.itemid,item.barcode,item.itemname as itemdesc,stock.uom,
        (case when rep.isaccept = 1 then 'true' else 'false' end) as isaccept,
        (case when rep.isqty <> rep.qa then 'false' else 'true' end) as ispicked,
        round(ifnull(rep.isqty,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as replaceqty,
        ifnull(rep.qa,0) as qa";
        return $qry;
    }

    public function loaddata($config)
    {
        if (isset($config['params']['row']['trno'])) {
            $trno = $config['params']['row']['trno'];
        } else {
            if (isset($config['params']['data'][0]['trno'])) {
                $trno = $config['params']['data'][0]['trno'];
            } else {
                $trno = 0;
            }
        }

        $select = $this->selectqry($config);
        $select = $select . ",'' as bgcolor ";

        $qry = "
        select " . $select . "
        from replacestock as rep
        left join " . $this->table . "  as stock on stock.trno=rep.trno and stock.line=rep.line
        left join item on item.itemid=stock.itemid
        left join client on client.clientid=stock.pickerid
        left join location as loc on loc.line=stock.locid
        left join pallet on pallet.line=stock.palletid
        left join lahead as head on head.trno=stock.trno
        where rep.trno=? and rep.isaccept=0
        order by line";
        return $this->coreFunctions->opentable($qry, [$trno]);
    }

    public function saveallentry($config)
    {
        $row = $config['params']['data'];
        $msg = '';
        $status = true;
        $selected = false;
        foreach ($row as $key => $value) {

            if ($value['bgcolor'] != '') {
                $selected = true;
                if ($value['ispicked'] != 'true') {
                    $msg = 'Item ' . $value['itemdesc'] . ' is currently on picking.';
                    $status = false;
                } else {
                    $this->coreFunctions->sbcupdate('replacestock', ['isaccept' => 1], ['trno' => $value['trno'], 'line' => $value['line']]);
                    if ($this->coreFunctions->execqry("insert into hreplacestock (trno, line, isqty, palletid, locid, dateid, user, pickerid, pickerstart, pickerend, remid, qa, isaccept)
                        select trno, line, isqty, palletid, locid, dateid, user, pickerid, pickerstart, pickerend, remid, qa, isaccept from replacestock where trno=? and line=?", '', [$value['trno'], $value['line']])) {
                        $this->coreFunctions->execqry("delete from replacestock where trno=? and line=?", 'delete', [$value['trno'], $value['line']]);
                    }
                }
            }
        }
        $row = $this->loaddata($config);
        if ($msg == '') {
            $msg = 'Successfully saved.';
        }
        if (!$selected) {
            $msg = 'No changes made';
        }
        if (empty($row)) {
            return ['status' => $status, 'msg' => $msg, 'data' => $row];
        }
        return ['status' => $status, 'msg' => $msg, 'data' => $row];
    }
}
