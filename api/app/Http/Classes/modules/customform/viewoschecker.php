<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewoschecker
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
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 22, 'edit' => 23);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $doc = $config['params']['doc'];

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
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        $doc = $config['params']['doc'];

        $usdtophp = $this->othersClass->getexchangerate('USD', 'PHP');
        $usdtosgd = $this->othersClass->getexchangerate('USD', 'SGD');
        $phptousd = $this->othersClass->getexchangerate('PHP', 'USD');
        $phptosgd = $this->othersClass->getexchangerate('PHP', 'SGD');
        $sgdtousd = $this->othersClass->getexchangerate('SGD', 'USD');
        $sgdtophp = $this->othersClass->getexchangerate('SGD', 'PHP');

        if (isset($config['params']['row'])) { // on load
            $trno = $config['params']['row']['trno'];

            $qty = $config['params']['row']['rrqty'];
            $trno = $config['params']['row']['trno'];
            $line = $config['params']['row']['line'];
            $itemname = $config['params']['row']['itemname'];
            $uomfactor = $config['params']['row']['uomfactor'];
            $disc = $config['params']['row']['disc'];

            $vcurrency = $this->getvendorcur($trno);
            $ccurrency = $this->getcustomercur($trno);

            $cur = isset($ccurrency[0]->cur) ? $ccurrency[0]->cur : '';
            $forex = isset($ccurrency[0]->curtopeso) ? $ccurrency[0]->curtopeso : 0;
            $vcur = isset($vcurrency[0]->cur) ? $vcurrency[0]->cur : '';
            $vforex = isset($vcurrency[0]->curtopeso) ? $vcurrency[0]->curtopeso : 0;

            $qry = "select  i.trno,i.line,
            i.customercur as cur,
            '0' as forex, i.vendorcostprice as tin,
            i.vendorcur as vcur, '0' as vforex,
            i.quantity as isqty, i.exchangerate as replaceqty,i.freight as contact,
            i.markup as tel, i.amt1 as minimum,

            " . $usdtophp . " as db, " . $usdtosgd . " as bal, " . $phptousd . " as cr, " . $phptosgd . " as endbal,
            " . $sgdtousd . " as begbal, " . $sgdtophp . " as clearbal, '0.8' as difference, 
            '" . $itemname . "' as itemname, " . $uomfactor . " as uomfactor, '" . $disc . "' as disc
            from stockinfotrans as i 
            where i.trno = ? and i.line = ?
            ";

            $data = $this->coreFunctions->opentable($qry, [$trno, $line]);


            $this->modulename = 'OUTSOURCE CHECKER - ' . $itemname;
        } else { // on compute
            $trno = $config['params']['dataparams']['trno'];
            $line = $config['params']['dataparams']['line'];
            $cur = $config['params']['dataparams']['cur'];
            $forex = $config['params']['dataparams']['forex'];
            $tin = $config['params']['dataparams']['tin'];
            $vcur = $config['params']['dataparams']['vcur'];
            $vforex = $config['params']['dataparams']['vforex'];
            $isqty = $config['params']['dataparams']['isqty'];
            $db = $config['params']['dataparams']['db'];
            $contact = (isset($config['params']['dataparams']['contact']) ? $config['params']['dataparams']['contact'] : 0);
            $tel = $config['params']['dataparams']['tel'];
            $minimum = $config['params']['dataparams']['minimum'];
            $itemname = $config['params']['dataparams']['itemname'];
            $uomfactor = $config['params']['dataparams']['uomfactor'];
            $disc = $config['params']['dataparams']['disc'];

            $data = $this->coreFunctions->opentable("select " . $trno . " as trno, " . $line . " as line, '" . $cur . "' as cur, 
                " . $forex . " as forex, " . $tin . " as tin, '" . $vcur . "' as vcur, " . $vforex . " as vforex, " . $isqty . " as isqty, '" . $db . "' as replaceqty,
                " . $contact . " as contact, " . $tel . " as tel, " . $minimum . " as minimum,
                " . $usdtophp . " as db, " . $usdtosgd . " as bal, " . $phptousd . " as cr, " . $phptosgd . " as endbal,
                " . $sgdtousd . " as begbal, " . $sgdtophp . " as clearbal, '0.8' as difference,
                '" . $itemname . "' as itemname, " . $uomfactor . " as uomfactor, '" . $disc . "' as disc
                ");
            $this->modulename = 'OUTSOURCE CHECKER - ' . $itemname;
        }

        return $data;
    }

    public function getheaddata($config, $doc)
    {
        return [];
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

        // reuse field
        //cur = customer currency
        //vcur = vendor currency
        //tin = cost price
        //contact = freight
        //isqty = quantity
        //tel = markup freight 0.8 default
        //minimum = selling price
        $trno = $data['trno'];
        $line = $data['line'];
        $cc = $data['cur'];
        $vc = $data['vcur'];
        $m = $data['tel'];


        $cp = floatval($data['tin']);
        $data['tin'] = $cp;
        $f = floatval($data['contact']);
        $q = floatval($data['isqty']);
        $sp = floatval($data['minimum']);

        // from stock
        $uomfactor = floatval($data['uomfactor']);
        $disc = $data['disc'];

        // $itemid = $data['clientid'];

        //$cc = $data['cur']; //cur = customercur

        //$vc = $data['vcur']; //vcur = vendorcur
        //$m = $data['tel']; //tel = markup freight 0.8 default

        //$cp = floatval($data['tin']); //tin = vendorcostprice
        //$data['tin'] = $cp;
        //$f = floatval($data['contact']); //contact = freight
        //$q = floatval($data['isqty']); //isqty = quantity
        //$sp = floatval($data['minimum']); //minimum = selling price

        switch ($action) {
            case 'update':
                if ($data['minimum'] == 0) {
                    return ['status' => false, 'msg' => 'Cant save, Please Compute First', 'data' => [], 'txtdata' => []];
                } else {
                    $qry = "select trno as value from stockinfotrans 
                    where trno='" . $trno . "' and line='" . $line . "'";
                    $val = $this->coreFunctions->datareader($qry);
                    if ($val == '') {
                        $val = 0;
                    }
                    $data = [
                        'trno' => $trno,
                        'line' => $line,
                        'customercur' => $cc,
                        'vendorcur' => $vc,
                        'vendorcostprice' => $cp,
                        'quantity' => $q,
                        'freight' => $f,
                        'markup' => $m,
                        'exchangerate' => $data['db'],
                        'amt1' => $sp,
                    ];

                    if ($val != 0) {
                        $this->coreFunctions->sbcupdate(
                            'stockinfotrans',
                            $data,
                            ['trno' => $trno, 'line' => $line]
                        );
                        $compute = $this->othersClass->computestock($sp, $disc, $q, $uomfactor); //amt, disc, qty, factor - uom
                        $updatestock = ["rrqty" => $q, "qty" => $compute['qty'], "rrcost" => $sp, "cost" => $compute['amt'], 'currency' => $cc, "ext" => $compute['ext']];

                        $tablename = "osstock";
                        $this->coreFunctions->sbcupdate($tablename, $updatestock, ['trno' => $trno, 'line' => $line]);

                        $path = 'App\Http\Classes\modules\purchase\\os';
                        $config['params']['trno'] = $trno;
                        $stock = app($path)->openstock($trno, $config);
                        return ['status' => true, 'msg' => 'Successfully updated.', 'data' => [], 'txtdata' => [], 'reloadgriddata' => ['inventory' => $stock]];
                    } else {
                        $this->coreFunctions->sbcinsert('stockinfotrans', $data);
                        $compute = $this->othersClass->computestock($sp, $disc, $q, $uomfactor); //amt, disc, qty, factor - uom
                        $updatestock = ["rrqty" => $q, "qty" => $compute['qty'], "rrcost" => $sp, "cost" => $compute['amt'], 'currency' => $cc, "ext" => $compute['ext']];

                        $tablename = "osstock";
                        $this->coreFunctions->sbcupdate($tablename, $updatestock, ['trno' => $trno, 'line' => $line]);

                        $path = 'App\Http\Classes\modules\purchase\\os';
                        $config['params']['trno'] = $trno;
                        $stock = app($path)->openstock($trno, $config);
                        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'txtdata' => [], 'reloadgriddata' => ['inventory' => $stock]];
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

                $data['minimum'] = round($sp, 2);
                $config['params']['dataparams'] = $data;
                $txtdata = $this->paramsdata($config);

                $path = 'App\Http\Classes\modules\purchase\\os';
                $config['params']['trno'] = $trno;
                $stock = app($path)->openstock($trno, $config);
                return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'txtdata' => $txtdata, 'reloadgriddata' => ['inventory' => $stock]];
                break;
        }
    }


    private function getcustomercur($trno)
    {
        $qry = "select f.cur, f.curtopeso from oshead
        left join client on oshead.customerid = client.clientid
        left join forex_masterfile as f on f.line = client.forexid
        where oshead.trno = $trno
        ";
        return $this->coreFunctions->opentable($qry);
    }

    private function getvendorcur($trno)
    {
        $qry = "select f.cur, f.curtopeso from oshead
        left join client on oshead.client = client.client
        left join forex_masterfile as f on f.line = client.forexid
        where oshead.trno = $trno
        ";
        return $this->coreFunctions->opentable($qry);
    }
}
