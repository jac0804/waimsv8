<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class customformrem
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'REMARKS';
    public $gridname = 'inventory';
    private $fields = ['returnby', 'returndate', 'returnrem'];
    public $tablenum = 'cntnum';
    private $table = 'vrhead';
    private $htable = 'hvrhead';
    private $logger;

    public $tablelogs = 'transnum_log';

    public $style = 'width:30%;max-width:70%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $this->modulename = "RETURN REMARKS";

        $fields = ['barcode', 'itemname', 'rem', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'barcode.type', 'input');
        data_set($col1, 'refresh.label', 'SAVE');
        data_set($col1, 'rem.readonly', false);

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];
        $select = "select '' as rem, stock.trno,stock.line,stock.itemid, item.itemname,item.barcode 
                 from issueitemstock as stock
                  left join item as item on item.itemid=stock.itemid where stock.trno=$trno and stock.line=$line";
        $data = $this->coreFunctions->opentable($select);
        return $data;
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [];
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

    public function loaddata($config)
    {
        $trno = $config['params']['dataparams']['trno'];
        $rem = $config['params']['dataparams']['rem'];
        $itemid = $config['params']['dataparams']['itemid'];

        $line = $config['params']['dataparams']['line'];
        if ($rem == '') {
            return ['status' => false, 'msg' => 'Please input valid remarks', 'data' => []];
        }
        $user = $config['params']['user'];

        $empid  = $this->coreFunctions->datareader("select clientid as value 
        from issueitem  as i
        left join issueitemstock as iss on iss.trno=i.trno 
        where i.trno=" . $trno . " and iss.itemid = '" . $itemid . "' ");

        $data = [
            'returnby' => $user,
            'returndate' => $this->othersClass->getCurrentTimeStamp(),
            'returnrem' => $rem
        ];
        foreach ($this->fields as $key) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $this->coreFunctions->sbcupdate("issueitemstock", $data, ['trno' => $trno, 'line' => [$line]]);
        $this->coreFunctions->sbcupdate("iteminfo", ['empid' => 0, 'locid' => 0], ['itemid' => $itemid, 'empid' => $empid]);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'reloadlisting' => true];
    }
}
