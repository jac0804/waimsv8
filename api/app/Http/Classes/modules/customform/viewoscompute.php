<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewoscompute
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = '';
    public $gridname = 'tableentry';
    private $fields = ['itemdescription', 'accessories'];
    private $table = 'iteminfo';

    public $tablelogs = 'item_log';
    public $tablelogs_del = 'del_item_log';

    public $style = 'width:50%;max-width:50%;';
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
        $attrib = array('load' => 2671, 'edit' => 131);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $doc = $config['params']['doc'];
        // $isposted = $this->othersClass->isposted2($trno, "transnum");
        // $trno = $config['params']['row']['trno'];

        $fields = [
            ['cur'], ['tin', 'vcur'], ['isqty', 'replaceqty'], ['contact', 'tel'], ['refresh', 'update'], 'minimum', 'lblshipping',
            ['db', 'bal'], ['cr', 'endbal'], ['begbal', 'clearbal'], 'difference'
        ];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'cur.label', 'Customer Currency');
        data_set($col1, 'cur.lookupclass', 'oschecker1');
        data_set($col1, 'vcur.lookupclass', 'oschecker2');
        data_set($col1, 'tin.label', 'Vendor Cost Price');
        data_set($col1, 'isqty.label', 'Quantity');
        data_set($col1, 'replaceqty.label', 'Current Exchange Rate');
        data_set($col1, 'replaceqty.readonly', true);
        data_set($col1, 'contact.label', 'Freight');
        data_set($col1, 'tel.label', 'Markup');
        data_set($col1, 'contact.readonly', false);
        data_set($col1, 'tel.readonly', false);
        data_set($col1, 'refresh.label', 'Compute');
        data_set($col1, 'update.label', 'Save');
        data_set($col1, 'minimum.label', 'Selling Price');
        data_set($col1, 'minimum.maxlength', 100);
        data_set($col1, 'lblshipping.label', 'Exchange Rate');


        data_set($col1, 'db.label', 'USD->PHP');
        data_set($col1, 'bal.label', 'USD->SGD');

        data_set($col1, 'cr.label', 'PHP->USD');
        data_set($col1, 'endbal.label', 'PHP->SGD');
        data_set($col1, 'endbal.readonly', true);

        data_set($col1, 'begbal.label', 'SGD->USD');
        data_set($col1, 'clearbal.label', 'SGD->PHP');
        data_set($col1, 'begbal.readonly', true);
        data_set($col1, 'clearbal.readonly', true);

        data_set($col1, 'difference.label', 'Markup');

        $fields = [];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        $doc = $config['params']['doc'];

        if (isset($config['params']['dataparams']['clientid'])) { //on compute
            $itemid = $config['params']['dataparams']['clientid'];
            $cur = $config['params']['dataparams']['cur'];
            $forex = $config['params']['dataparams']['forex'];
            $tin = $config['params']['dataparams']['tin'];
            $vcur = $config['params']['dataparams']['vcur'];
            $vforex = $config['params']['dataparams']['vforex'];
            $isqty = $config['params']['dataparams']['isqty'];
            $db = $config['params']['dataparams']['db'];
            $contact = $config['params']['dataparams']['contact'];
            $tel = $config['params']['dataparams']['tel'];
            $minimum = $config['params']['dataparams']['minimum'];
            $itemname = $config['params']['dataparams']['itemname'];
        } else { //on load
            $itemid = $config['params']['clientid'];
            $cur = '';
            $forex = 0;
            $tin = 0;
            $vcur = '';
            $vforex = 0;
            $isqty = 0;
            $contact = 0;
            $tel = 0.8;
            $minimum = 0;
            $data = $this->getheaddata($config, $doc);
        }


        $dataitem = $this->othersClass->getitemname($itemid);
        $itemname = $dataitem[0]->itemname;
        $usdtophp = $this->othersClass->getexchangerate('USD', 'PHP');
        $usdtosgd = $this->othersClass->getexchangerate('USD', 'SGD');
        $phptousd = $this->othersClass->getexchangerate('PHP', 'USD');
        $phptosgd = $this->othersClass->getexchangerate('PHP', 'SGD');
        $sgdtousd = $this->othersClass->getexchangerate('SGD', 'USD');
        $sgdtophp = $this->othersClass->getexchangerate('SGD', 'PHP');


        if (empty($data)) {
            $data = $this->coreFunctions->opentable("select  " . $itemid . " as clientid, '" . $cur . "' as cur, 
                '" . $forex . "' as forex, '" . $tin . "' as tin, '" . $vcur . "' as vcur, '" . $vforex . "' as vforex, '" . $isqty . "' as isqty, '" . $db . "' as replaceqty,
                '" . $contact . "' as contact, '" . $tel . "' as tel, '" . $minimum . "' as minimum,

                " . $usdtophp . " as db, " . $usdtosgd . " as bal, " . $phptousd . " as cr, " . $phptosgd . " as endbal,
                " . $sgdtousd . " as begbal, " . $sgdtophp . " as clearbal, '0.8' as difference,
                '" . $itemname . "' as itemname
                ");
        }

        $this->modulename = 'OUTSOURCE ITEMS - ' . $itemname;

        return $data;
    }

    public function getheaddata($config, $doc)
    {

        if (isset($config['params']['clientid'])) {
            $itemid = $config['params']['clientid'];
        } else {
            $itemid = $config['params']['itemid'];
        }
        switch ($doc) {
            case 'STOCKCARD':
                $clientid = $itemid;
                break;
        }
        $dataitem = $this->othersClass->getitemname($itemid);
        $itemname = $dataitem[0]->itemname;
        $usdtophp = $this->othersClass->getexchangerate('USD', 'PHP');
        $usdtosgd = $this->othersClass->getexchangerate('USD', 'SGD');
        $phptousd = $this->othersClass->getexchangerate('PHP', 'USD');
        $phptosgd = $this->othersClass->getexchangerate('PHP', 'SGD');
        $sgdtousd = $this->othersClass->getexchangerate('SGD', 'USD');
        $sgdtophp = $this->othersClass->getexchangerate('SGD', 'PHP');


        $qry = "
        select  i.itemid as clientid, i.customercur as cur, 
        '0' as forex, i.vendorcostprice as tin, 
        i.vendorcur as vcur, '0' as vforex, 
        i.quantity as isqty, i.exchangerate as replaceqty,i.freight as contact, 
        i.markup as tel, '0.8' as minimum,

        " . $usdtophp . " as db, " . $usdtosgd . " as bal, " . $phptousd . " as cr, " . $phptosgd . " as endbal,
        " . $sgdtousd . " as begbal, " . $sgdtophp . " as clearbal, '0.8' as difference,
        '" . $itemname . "' as itemname
        from iteminfo as i where i.itemid=?
        ";


        return $this->coreFunctions->opentable($qry, [$itemid]);
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
        $data = $config['params']['dataparams'];
        $action = $config['params']['action2'];

        $itemid = $data['clientid'];

        $cc = $data['cur']; //cur = customercur

        $vc = $data['vcur']; //vcur = vendorcur
        $m = $data['tel']; //tel = markup freight 0.8 default

        $cp = floatval($data['tin']); //tin = vendorcostprice
        $data['tin'] = $cp;
        $f = floatval($data['contact']); //contact = freight
        $q = floatval($data['isqty']); //isqty = quantity
        $sp = floatval($data['minimum']); //minimum = selling price

        // from stock
        switch ($action) {
            case 'update':
                if ($data['minimum'] == 0) {
                    return ['status' => false, 'msg' => 'Cant save, Please Compute First', 'data' => [], 'txtdata' => []];
                } else {
                    $qry = "select itemid as value from " . $this->table . " where itemid='" . $itemid . "'";
                    $item = $this->coreFunctions->datareader($qry);
                    if ($item == '') {
                        $item = 0;
                    }
                    $data = [
                        'itemid' => $itemid,
                        'customercur' => $cc,
                        'vendorcur' => $vc,
                        'vendorcostprice' => $cp,
                        'quantity' => $q,
                        'freight' => $f,
                        'markup' => $m,
                        'exchangerate' => $data['db'],
                    ];
                    if ($item != 0) {
                        $this->coreFunctions->sbcupdate($this->table, $data, ['itemid' => $item]);
                        $this->coreFunctions->sbcupdate("item", ['amt' => $sp], ['itemid' => $itemid]);
                        return ['status' => true, 'msg' => 'Successfully updated.', 'data' => [], 'txtdata' => []];
                    } else {
                        $this->coreFunctions->sbcinsert($this->table, $data);
                        $this->coreFunctions->sbcupdate("item", ['amt' => $sp], ['itemid' => $itemid]);
                        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'txtdata' => []];
                    }
                }

                break;

            default:
                if (strtoupper($vc) == 'USD' && strtoupper($cc) == 'USD') {
                    if (floatval($f) <> 0) {
                        $sp = round((($cp + ($f / $q)) / $m), 6);
                    } else {
                        $sp = round($cp / $m, 6);
                    }
                } else if (strtoupper($vc) == 'USD' && strtoupper($cc) == 'PHP') {
                    $er = $this->othersClass->getexchangerate(strtoupper($vc), strtoupper($cc));
                    if (floatval($f) <> 0) {
                        $sp = round((((($cp + ($f / $q)) / $m) * 1.05) * 1.05) * $er, 6);
                    } else {
                        $sp = round((($cp / $m) * 1.05) * 1.05 * $er, 6);
                    }
                    // $sp = ($cp + ($f/$q));
                    // $sp = $sp / $m;
                    // $sp = $sp * 1.05;
                    // $sp = $sp * 1.05;
                    // $sp = $sp * $er;
                } else if (strtoupper($vc) == 'USD' && strtoupper($cc) == 'SGD') {
                    $er = $this->othersClass->getexchangerate(strtoupper($vc), strtoupper($cc));
                    if (floatval($f) <> 0) {
                        $sp = round((($cp + ($f / $q)) / $m) * $er, 6);
                    } else {
                        $sp =  round(($cp) / $m * $er, 6);
                    }
                    // $sp = ($cp + ($f/$q));
                    // $sp = $sp / $m;
                    // $sp = $sp * $er;
                } else if (strtoupper($vc) == 'SGD' && strtoupper($cc) == 'USD') {
                    $er = $this->othersClass->getexchangerate(strtoupper($vc), strtoupper($cc));
                    if (floatval($f) <> 0) {
                        $sp =  round(((($cp + ($f / $q)) / $m) * $er), 6);
                    } else {
                        $sp = round(($cp / $m) * $er, 6);
                    }
                    // $sp = ($cp + ($f/$q));
                    // $sp = $sp / $m;
                    // $sp = $sp * $er;
                } else if (strtoupper($vc) == 'SGD' && strtoupper($cc) == 'SGD') {
                    if (floatval($f) <> 0) {
                        $sp = round((($cp + ($f / $q)) / $m), 6);
                    } else {
                        $sp = round(($cp / $m), 6);
                    }
                    // $sp = ($cp + ($f/$q));
                    // $sp = $sp / $m;
                } else if (strtoupper($vc) == 'SGD' && strtoupper($cc) == 'PHP') {
                    $er = $this->othersClass->getexchangerate(strtoupper($vc), strtoupper($cc));
                    if (floatval($f) <> 0) {
                        $sp = round((((($cp + ($f / $q)) / $m) * 1.05) * 1.05) * $er, 6);
                    } else {
                        $sp = round(((($cp / $m) * 1.05) * 1.05) * $er, 6);
                    }
                    // $sp = ($cp + ($f/$q));
                    // $sp = $sp / $m;
                    // $sp = $sp * 1.05;
                    // $sp = $sp * 1.05;
                    // $sp = $sp * $er;
                } else if (strtoupper($vc) == 'PHP' && strtoupper($cc) == 'USD') {
                    $er = $this->othersClass->getexchangerate(strtoupper($vc), strtoupper($cc));
                    if (floatval($f) <> 0) {
                        $sp = round((($cp + ($f / $q)) / $m) * $er, 6);
                    } else {
                        $sp = round(($cp / $m) * $er, 6);
                    }
                    // $sp = ($cp + ($f/$q));
                    // $sp = $sp / $m;
                    // $sp = $sp * $er;
                } else if (strtoupper($vc) == 'PHP' && strtoupper($cc) == 'SGD') {
                    $er = $this->othersClass->getexchangerate(strtoupper($vc), strtoupper($cc));
                    if (floatval($f) <> 0) {
                        $sp = round((($cp + ($f / $q)) / $m) * $er, 6);
                    } else {
                        $sp = round(($cp / $m) * $er, 6);
                    }
                    // $sp = ($cp + ($f/$q));
                    // $sp = $sp / $m;
                    // $sp = $sp * $er;
                } else if (strtoupper($vc) == 'PHP' && strtoupper($cc) == 'PHP') {
                    if (floatval($f) <> 0) {
                        $sp = round((($cp / $m + ($f / $q))), 6);
                    } else {
                        $sp = round($cp / $m, 6);
                    }
                    // $sp = ($cp + ($f/$q));
                    // $sp = $sp / $m;
                    // $sp = $sp * 1;
                } else {
                    return ['status' => true, 'msg' => 'Please Setup Exchange Rate', 'data' => [], 'txtdata' => []];
                }

                $tablename = "item";

                $data['minimum'] = round($sp, 2);
                $config['params']['dataparams'] = $data;

                $txtdata = $this->paramsdata($config);
                return ['status' => true, 'msg' => 'Compute Amount Item Success', 'data' => [], 'txtdata' => $txtdata];
                break;
        }
    }
}
