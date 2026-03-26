<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use Illuminate\Http\Request;
use App\Http\Requests;


class poslookup
{
    private $coreFunctions;
    private $othersClass;
    private $sqlquery;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions();
        $this->othersClass = new othersClass();
        $this->sqlquery = new sqlquery();
    }

    public function lookupcashier($config)
    {

        $branchid = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
        // $center = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';

        $plotting = array('stationline' => 'line',  'stationid' => 'clientid', 'cashier' => 'cashier');
        $plottype = 'plothead';
        $title = 'List of Cashier';

        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'cashier', 'label' => 'Cashier', 'align' => 'left', 'field' => 'cashier', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        // var_dump($config['params']['lookupclass']);
        switch ($config['params']['lookupclass']) {
            case 'cashier':
                $plotting = array('stationline' => 'line',  'stationid' => 'clientid', 'cashier' => 'cashier');
                $query =  "select '' as cashier
          union all
          select distinct openby  as cashier
          from head
          where openby <>''
          ";
                // var_dump($plotting, $plottype);
                $data = $this->coreFunctions->opentable($query);
                break;
            default:
                $qry = "
            select 0 as line,  0 as clientid, '' as station
            union all
            select line, clientid,station from branchstation
            where clientid = ? ";
                $data = $this->coreFunctions->opentable($qry, [$branchid]);
                break;
        }
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    } //end function

    public function lookupcustomer($config)
    {


        $plotting = array('customer' => 'customer', 'clientname' => 'clientname');
        $plottype = 'plothead';
        $title = 'List of Customer';
        // $callbackfieldhead = array('dateid');
        // $callbackfieldlookup = array('terms', 'days', 'client');
        // $condition = " where client.iscutomer=1 and client.isinactive =0 order by client.client";


        $lookupsetup = array(
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        );
        $plotsetup = array(
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        );
        // lookup columns
        $cols = [
            ['name' => 'customer', 'label' => 'Customer Code', 'align' => 'left', 'field' => 'customer', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'customer', 'label' => 'Customer', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;']
        ];
        $query =  "select '' as customer,  '' as clientname
          union all
          select  client, clientname  as customer
          from client
          where iscustomer = 1
          ";
        // var_dump($plotting, $plottype);
        $data = $this->coreFunctions->opentable($query);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    } //end function



    public function pos_doctype_lookup($config)
    {
        // $branchid = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
        // $center = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';

        $plotting = array('posdoctype' => 'doctype');
        $plottype = 'plothead';
        $title = 'Document Type';

        $lookupsetup = [
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        ];
        $plotsetup = [
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        ];
        $cols = [
            ['name' => 'doctype', 'label' => 'Document Type', 'align' => 'left', 'field' => 'doctype', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        $query = "select '' as doctype union all
              select 'SI' as doctype union all
              select 'RT' as doctype union all
              select 'V' as doctype";
        $data = $this->coreFunctions->opentable($query);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function pos_station_lookup($config)
    {
        // $branchid = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
        // $center = isset($config['params']['addedparams'][0]) ? $config['params']['addedparams'][0] : '';
        $center = $config['params']['center'];
        $plotting = array('pos_station' => 'station');
        $plottype = 'plothead';
        $title = 'Station';

        $lookupsetup = [
            'type' => 'single',
            'title' => $title,
            'style' => 'width:900px;max-width:900px;'
        ];
        $plotsetup = [
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        ];
        $cols = [
            ['name' => 'station', 'label' => 'Station', 'align' => 'left', 'field' => 'station', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        $query = "select distinct station from cntnum
                where cntnum.center = '" . $center . "';";
        $data = $this->coreFunctions->opentable($query);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function pos_paymentmethod_lookup($config)
    {
        $plotting = ['pospayment' => 'label', 'paymentcond' => 'cond'];
        $plottype = 'plothead';

        $lookupsetup = [
            'type' => 'single',
            'title' => 'Payment Type',
            'style' => 'width:900px;max-width:900px;'
        ];

        $plotsetup = [
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        ];

        $cols = [
            ['name' => 'label', 'label' => 'Payment Type', 'align' => 'left', 'field' => 'label', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];

        $data = $this->paymentTypes();

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
    //payment lookup helper

    private function paymentTypes()
    {
        return [
            ['label' => '', 'cond' => ''],

            // SINGLE
            ['label' => 'CASH', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'VOUCHER', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CASH/CARD', 'cond' => "and h.cash<>0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CHEQUE', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CREDIT', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/LOYALTY POINTS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/VOUCHER', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/DEBIT', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/SMAC', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/E-PLUS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CASH/ONLINE DEALS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CARD/CHEQUE', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CREDIT', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/LOYALTY POINTS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/VOUCHER', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/DEBIT', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/SMAC', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/E-PLUS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CARD/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CHEQUE/CREDIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/LOYALTY POINTS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/VOUCHER', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CREDIT/LOYALTY POINTS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/VOUCHER', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'LOYALTY POINTS/VOUCHER', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'VOUCHER/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'VOUCHER/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'VOUCHER/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'VOUCHER/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'DEBIT/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'DEBIT/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'DEBIT/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'SMAC/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'SMAC/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'E-PLUS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals<>0"],
            ['label' => 'CASH/CARD/CHEQUE', 'cond' => "and h.cash<>0 and h.card<>0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CARD/CREDIT', 'cond' => "and h.cash<>0 and h.card<>0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CARD/LOYALTY POINTS', 'cond' => "and h.cash<>0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CARD/VOUCHER', 'cond' => "and h.cash<>0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CARD/DEBIT', 'cond' => "and h.cash<>0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CARD/SMAC', 'cond' => "and h.cash<>0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CARD/E-PLUS', 'cond' => "and h.cash<>0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CASH/CARD/ONLINE DEALS', 'cond' => "and h.cash<>0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CASH/CHEQUE/CREDIT', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque<>0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CHEQUE/LOYALTY POINTS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CHEQUE/VOUCHER', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CHEQUE/DEBIT', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CHEQUE/SMAC', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CHEQUE/E-PLUS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CASH/CHEQUE/ONLINE DEALS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CASH/CREDIT/LOYALTY POINTS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CREDIT/VOUCHER', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CREDIT/DEBIT', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CREDIT/SMAC', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/CREDIT/E-PLUS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CASH/CREDIT/ONLINE DEALS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CASH/LOYALTY POINTS/VOUCHER', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/LOYALTY POINTS/DEBIT', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/LOYALTY POINTS/SMAC', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/LOYALTY POINTS/E-PLUS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CASH/LOYALTY POINTS/ONLINE DEALS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CASH/VOUCHER/DEBIT', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/VOUCHER/SMAC', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/VOUCHER/E-PLUS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CASH/VOUCHER/ONLINE DEALS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CASH/DEBIT/SMAC', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CASH/DEBIT/E-PLUS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CASH/DEBIT/ONLINE DEALS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CASH/SMAC/E-PLUS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CASH/SMAC/ONLINE DEALS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CASH/E-PLUS/ONLINE DEALS', 'cond' => "and h.cash<>0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals<>0"],
            ['label' => 'CARD/CHEQUE/CREDIT', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque<>0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CHEQUE/LOYALTY POINTS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque<>0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CHEQUE/VOUCHER', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CHEQUE/DEBIT', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CHEQUE/SMAC', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CHEQUE/E-PLUS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CARD/CHEQUE/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CARD/CREDIT/LOYALTY POINTS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr<>0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CREDIT/VOUCHER', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CREDIT/DEBIT', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CREDIT/SMAC', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/CREDIT/E-PLUS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CARD/CREDIT/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CARD/LOYALTY POINTS/VOUCHER', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/LOYALTY POINTS/DEBIT', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/LOYALTY POINTS/SMAC', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/LOYALTY POINTS/E-PLUS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CARD/LOYALTY POINTS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CARD/VOUCHER/DEBIT', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/VOUCHER/SMAC', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/VOUCHER/E-PLUS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CARD/VOUCHER/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CARD/DEBIT/SMAC', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CARD/DEBIT/E-PLUS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CARD/DEBIT/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CARD/SMAC/E-PLUS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CARD/SMAC/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CARD/E-PLUS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card<>0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals<>0"],
            ['label' => 'CHEQUE/CREDIT/LOYALTY POINTS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr<>0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/CREDIT/VOUCHER', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr<>0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/CREDIT/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/CREDIT/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/CREDIT/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/CREDIT/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CHEQUE/LOYALTY POINTS/VOUCHER', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp<>0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/LOYALTY POINTS/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/LOYALTY POINTS/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/LOYALTY POINTS/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/LOYALTY POINTS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CHEQUE/VOUCHER/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/VOUCHER/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/VOUCHER/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/VOUCHER/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CHEQUE/DEBIT/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/DEBIT/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/DEBIT/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CHEQUE/SMAC/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CHEQUE/SMAC/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CHEQUE/E-PLUS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque<>0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals<>0"],
            ['label' => 'CREDIT/LOYALTY POINTS/VOUCHER', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp<>0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/LOYALTY POINTS/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp<>0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/LOYALTY POINTS/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/LOYALTY POINTS/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/LOYALTY POINTS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CREDIT/VOUCHER/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher<>0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/VOUCHER/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/VOUCHER/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/VOUCHER/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CREDIT/DEBIT/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/DEBIT/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/DEBIT/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CREDIT/SMAC/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'CREDIT/SMAC/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'CREDIT/E-PLUS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr<>0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals<>0"],
            ['label' => 'LOYALTY POINTS/VOUCHER/DEBIT', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher<>0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/VOUCHER/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher<>0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/VOUCHER/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/VOUCHER/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'LOYALTY POINTS/DEBIT/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit<>0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/DEBIT/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/DEBIT/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'LOYALTY POINTS/SMAC/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'LOYALTY POINTS/SMAC/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'LOYALTY POINTS/E-PLUS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp<>0 and h.voucher=0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals<>0"],
            ['label' => 'VOUCHER/DEBIT/SMAC', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit<>0 and h.smac<>0 and h.eplus=0 and h.onlinedeals=0"],
            ['label' => 'VOUCHER/DEBIT/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit<>0 and h.smac=0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'VOUCHER/DEBIT/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit<>0 and h.smac=0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'VOUCHER/SMAC/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac<>0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'VOUCHER/SMAC/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac<>0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'VOUCHER/E-PLUS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher<>0 and h.debit=0 and h.smac=0 and h.eplus<>0 and h.onlinedeals<>0"],
            ['label' => 'DEBIT/SMAC/E-PLUS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac<>0 and h.eplus<>0 and h.onlinedeals=0"],
            ['label' => 'DEBIT/SMAC/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac<>0 and h.eplus=0 and h.onlinedeals<>0"],
            ['label' => 'DEBIT/E-PLUS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit<>0 and h.smac=0 and h.eplus<>0 and h.onlinedeals<>0"],
            ['label' => 'SMAC/E-PLUS/ONLINE DEALS', 'cond' => "and h.cash=0 and h.card=0 and h.cheque=0 and h.cr=0 and h.lp=0 and h.voucher=0 and h.debit=0 and h.smac<>0 and h.eplus<>0 and h.onlinedeals<>0"],
        ];
    }

    public function lookupuserss($config)
    {
        $plotting = array('usernamee' => 'username');
        $plottype = 'plothead';

        $lookupsetup = [
            'type' => 'single',
            'title' => 'Payment Type',
            'style' => 'width:900px;max-width:900px;'
        ];

        $plotsetup = [
            'plottype' => $plottype,
            'action' => '',
            'plotting' => $plotting
        ];

        $cols = [
            [
                'name' => 'username',
                'label' => 'Username',
                'align' => 'left',
                'field' => 'username',
                'sortable' => true,
                'style' => 'font-size:16px;'
            ],
        ];

        $data = $this->coreFunctions->opentable("select distinct(userid) as username from table_log
                                            union all
                                            select distinct(userid) as username from htable_log
                                            union all
                                            select distinct(userid) as username from del_table_log;");
        // var_dump($data);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
}
