<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

class entrypackingstock
{
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;

    public $modulename = 'PACKING LIST DETAILS';
    public $gridname = 'inventory';
    private $fields = ['rrqty', 'qty'];
    private $table = 'plstock';

    public $style = 'width:100%;;max-width:70%;';
    public $showclosebtn = true;

    public function __construct()
    {
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 1860, 'edit' => 1861);
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'barcode', 'itemname', 'rrqty', 'uom']]];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
        $obj[0][$this->gridname]['columns'][1]['type'] = "label";
        $obj[0][$this->gridname]['columns'][2]['label'] = "Item Name";
        $obj[0][$this->gridname]['columns'][2]['type'] = "label";
        $obj[0][$this->gridname]['columns'][4]['type'] = "label";

        $obj[0][$this->gridname]['columns'][3]['align'] = "text-center";

        $obj[0][$this->gridname]['columns'][4]['align'] = "center";
        $obj[0][$this->gridname]['columns'][4]['style'] = "text-align:center";

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
        $qry = "stock.line,stock.trno,stock.itemid,item.barcode,item.itemname,stock.refx,stock.linex,stock.uom,
        round(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
        round(stock.qty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qty";
        return $qry;
    }

    public function loaddata($config)
    {
        $trno = $config['params']['row']['trno'];
        $refx = $config['params']['row']['refx'];
        $podocno = $config['params']['row']['docno'];
        $select = $this->selectqry($config);
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . "  as stock
        left join item on item.itemid=stock.itemid
        where stock.trno=? and stock.refx=? order by stock.line";

        return $this->coreFunctions->opentable($qry, [$trno, $refx]);
    }

    private function loaddataperrecord($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];

        $select = $this->selectqry($config);
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " from " . $this->table . "  as stock
        left join item on item.itemid=stock.itemid
        where stock.trno=? and stock.line=? order by stock.line";

        return $this->coreFunctions->opentable($qry, [$trno,  $line]);
    }

    public function saveallentry($config)
    {
        $msg = "";
        $data = $config['params']['data'];
        foreach ($data as $key => $value) {
            $result = $this->saveperline($value);
            if (!$result['status']) {
                $msg .= $result['msg'];
            }
        }

        if ($msg != '') {
            $msg = "All saved successfully.";
        }

        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => $msg, 'data' => $returndata];
    }

    public function save($config)
    {
        $msg = 'Successfully saved.';
        $result = $this->saveperline($config['params']['row']);
        $status = $result['status'];
        if (!$status) {
            $msg = $result['msg'];
        }

        $returnrow = $this->loaddataperrecord($config);
        return ['status' => $status, 'msg' => $msg, 'row' => $returnrow];
    }


    private function saveperline($value)
    {
        $status = true;
        $msg = '';

        if ($value['bgcolor'] != '') {
            reinserthere:
            $insertdata = [];
            $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
            $item = $this->coreFunctions->opentable($qry, [$value['uom'], $value['itemid']]);

            $factor = 1;
            $amt = 0;
            if (!empty($item)) {
                $item[0]->factor = $this->othersClass->val($item[0]->factor);
                if ($item[0]->factor !== 0) $factor = $item[0]->factor;
            }

            $computedata = $this->othersClass->computestock($amt, '', $value['rrqty'], $factor);

            $value['qty'] = $computedata['qty'];

            foreach ($this->fields as $k => $v) {
                $insertdata[$v] = $value[$v];
            }

            $this->coreFunctions->sbcupdate($this->table, $insertdata, ['trno' => $value['trno'], 'line' => $value['line']]);
            $result = $this->othersClass->setserveditemsRR($value['refx'], $value['linex'], 'qty');
            if (!$result) {
                $value['rrqty'] = 0;
                $value['qty'] = 0;
                $status = false;
                $msg = 'Failed to update PO served.';
                goto reinserthere;
            }
        }

        return ['status' => $status, 'msg' => $msg];
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
        $this->othersClass->setserveditemsRR($row['refx'], $row['linex'], 'qty');
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }
}
