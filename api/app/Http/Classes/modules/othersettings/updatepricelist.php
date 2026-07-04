<?php

namespace App\Http\Classes\modules\othersettings;

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
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class updatepricelist
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'UPDATE PRICE LIST';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;max-width:100%;';
    public $tablelogs = 'masterfile_log';
    public $issearchshow = true;
    public $showclosebtn = false;
    private $logger;
    private $sbcscript;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
        $this->btnClass = new buttonClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5890,
            'save' => 5890
        );
        return $attrib;
    }

    public function createTab($config)
    {
        return [];
    }


    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createHeadbutton($config)
    {
        // $btns = array(
        //     'logs'
        // );
        // $buttons = $this->btnClass->create($btns);
        // return $buttons;

        return [];
    }

    public function createHeadField($config)
    {
        $fields = ['selectprefix', 'type', 'rate', 'operator'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, "selectprefix.label", 'Price Level');
        data_set($col1, "saleprice.label", 'Price Basis');
        data_set($col1, 'selectprefix.options', [
            ['label' => 'Dealer2 Price', 'value' => 'dealer2'],
            ['label' => 'Industrial Price', 'value' => 'industrial'],
            ['label' => 'Walk-in Price', 'value' => 'walkin']
        ]);

        data_set($col1, 'operator.options', [
            ['label' => 'Dealer Price', 'value' => 'price'],
            ['label' => 'Latest Cost', 'value' => 'cost']
        ]);
        data_set($col1, "operator.label", 'Price Basis');

        data_set($col1, "type.lookupclass", 'lookuptype');
        data_set($col1, "type.action", 'lookuptype');
        $fields = ['update'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, "update.label", "UPDATE PRICE LIST");
        data_set($col2, "update.action", "updatepricelist");
        data_set($col2, "update.confirmlabel", "Do you want to update item price?");
        data_set($col2, "update.confirm", true);

        $fields = [];
        $col3 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }
    public function paramsdata($config)
    {
        $data = $this->coreFunctions->opentable("select '' as selectprefix,'0.0' as rate,'' as type,'' as operator");
        return $data[0];
    }

    public function data($config)
    {
        return $this->paramsdata($config);
    }
    public function headtablestatus($config)
    {
        $action = $config['params']["action2"];
        switch ($action) {
            case 'updatepricelist':
                return $this->pricelist($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Please check headtablestatus (' . $action . ')'];
                break;
        }
    }
    public function pricelist($config)
    {
        $params = $config['params']['dataparams'];

        $pricelevel = '';
        if (!empty($params['selectprefix'])) {
            $pricelevel = $params['selectprefix']['value'];
        }
        $basis = '';
        if (!empty($params['operator'])) {
            $basis = $params['operator']['value'];
        }
        $rate = $params['rate'];
        $type = $params['type'];


        if ($pricelevel == '') {
            return ['status' => false, 'msg' => 'Please select Price Level.'];
        }
        if ($rate == '') {
            return ['status' => false, 'msg' => 'Please enter Rate.'];
        }
        if ($type == '') {
            return ['status' => false, 'msg' => 'Please select Type.'];
        }
        if ($basis == '') {
            return ['status' => false, 'msg' => 'Please select Price Basis.'];
        }

        switch ($pricelevel) {
            case 'dealer2':
                $amt = 'amt2';
                break;
            case 'industrial':
                $amt = 'famt';
                break;
            case 'walkin':
                $amt = 'amt4';
                break;
        }
        $result = $this->updatepricelist($config, $type, $amt, $rate, $basis, $pricelevel);

        return ['status' => $result['status'], 'msg' => $result['msg'], 'action' => 'load'];
    }
    public function updatepricelist($config, $type, $amt, $rate, $basis, $pricelevel)
    {
        $items = [];
        $query = "select $amt,amt,itemid,uom from item";
        $data = $this->coreFunctions->opentable($query);

        foreach ($data as $key => $value) {

            if ($basis == 'cost') { //lastescost option
                $price = $this->coreFunctions->datareader("select rr.cost as value from rrstatus as rr
                left join cntnum as num on num.trno = rr.trno 
                where rr.itemid = ? and rr.uom =? and num.doc = 'RR' 
                order by rr.dateid desc limit 1", [$value->itemid, $value->uom], '', true);
            } else {
                $price = $value->amt; //dealer option
            }
            switch ($type) {
                case 'LOWER':
                    $operator = '-';
                    break;
                case 'UPPER':
                    $operator = '+';
                    break;
            }
            if ($operator == '+') {
                $newprice = $price + ($price * ($rate / 100));
            } else {
                $newprice = $price - ($price * ($rate / 100));
            }

            array_push($items, [
                'itemid' => $value->itemid,
                $amt    => $newprice
            ]);
        }
        $chunks = array_chunk($items, 100);

        $msg = "Update Records Successfully";
        $status = true;
        foreach ($chunks as $k => $item) {
            foreach ($item as $row) {
                $update = $this->coreFunctions->sbcupdate('item', [$amt => $row[$amt]], ['itemid' => $row['itemid']]);
                $this->coreFunctions->LogConsole('Update records: ' . $update);
                if (!$update) {
                    $status = false;
                    $msg = "Update Record Failed";
                    break;
                }
            }
            $this->coreFunctions->LogConsole('Processed 100 records.');
        }
        $this->logger->sbcmasterlog(0, $config,  $msg . ' - Price Level: ' . $pricelevel . ' - Rate: ' . $rate . ' - Type: ' . $type . ' - Price Basis: ' . $basis);
        return ['status' => $status, 'msg' => $msg];
    }
} //end class
