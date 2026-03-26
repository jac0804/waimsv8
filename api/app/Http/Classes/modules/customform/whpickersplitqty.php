<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use Exception;

class whpickersplitqty
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'AVAILABLE LOCATIONS (Split Qty.)';
    public $gridname = 'tableentry';
    private $fields = ['barcode', 'itemname'];
    private $table = 'lastock';

    public $tablelogs = 'table_log';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->warehousinglookup = new warehousinglookup;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 2028, 'edit' => 2029);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = ['barcode', 'itemname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'barcode.type', 'input');
        data_set($col1, 'barcode.class', 'csbarcode sbccsreadonly');
        data_set($col1, 'itemname.name', 'itemdesc');

        $fields = ['isqty', 'stat'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'isqty.readonly', true);

        $fields = ['replaceqty', 'location'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'replaceqty.readonly', true);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $trno = $config['params']['addedparams'][0];
        $line = $config['params']['addedparams'][1];
        $sjtype = $config['params']['addedparams'][2];

        return $this->getheaddata($config, $trno, $line, $sjtype);
    }

    public function getheaddata($config, $trno, $line, $sjtype)
    {
        if ($sjtype == 'REPLACEMENT') {
            $qry = "select rep.trno, rep.line, s.itemid, item.barcode, item.itemname as itemdesc,
            round(s.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, rep.pickerstart,
            case when rep.pickerstart is null then 'FOR PICKING' else 'PICKED' end as stat, s.whid, 'splitqty' as type,
            rep.locid, rep.palletid, '" . $sjtype . "' as sjtype, ifnull(location.loc,'') as location,
            round(rep.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as replaceqty
            from replacestock as rep
            left join lastock as s on s.trno=rep.trno and s.line=rep.line
            left join item on item.itemid=s.itemid
            left join location on location.line=s.locid
            where rep.trno=? and rep.line=?";
        } else {
            $qry = "select s.trno, s.line, s.itemid, item.barcode, item.itemname as itemdesc,
            round(s.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, s.pickerstart,
            case when s.pickerstart is null then 'FOR PICKING' else 'PICKED' end as stat, s.whid, 'splitqty' as type,
            s.locid, s.palletid, '" . $sjtype . "' as sjtype, ifnull(location.loc,'') as location, 0 as replaceqty
            from lastock as s
            left join item on item.itemid=s.itemid
            left join location on location.line=s.locid
            where s.trno=? and s.line=?";
        }

        return $this->coreFunctions->opentable($qry, [$trno, $line]);
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [
            'tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrywhavailablestocks', 'label' => 'LIST']
        ];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }
}
