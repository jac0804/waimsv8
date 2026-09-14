<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class viewothercharges
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'OTHER CHARGES';
    private $head = 'lahead';
    private $hhead = 'glhead';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    public $style = 'width:1200px;max-width:1200px;';
    public $issearchshow = true;
    public $showclosebtn = true;
    private $othersClass;




    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
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

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $trno = $config['params']['clientid'];
        $isposted = $this->othersClass->isposted2($trno, "cntnum");
        $fields = ['ied', 'bankcharges', 'interest', 'brokerfee', 'arrastre'];
        if ($companyid == 71) {
            $fields = ['ied', 'bankcharges', 'brokerfee', 'interest', 'arrastre', 'percentsales'];
        }
        $col1 = $this->fieldClass->create($fields);
        if ($isposted) {
            data_set($col1, 'ied.readonly', true);
            data_set($col1, 'bankcharges.readonly', true);
            data_set($col1, 'interest.readonly', true);
            data_set($col1, 'brokerfee.readonly', true);
            data_set($col1, 'arrastre.readonly', true);
            if ($companyid == 71) {
                data_set($col1, 'percentsales.readonly', true);
                data_set($col1, 'totalcharges.readonly', true);
            }
            $fields = [];
        } else {
            data_set($col1, 'ied.readonly', false);
            data_set($col1, 'bankcharges.readonly', false);
            data_set($col1, 'interest.readonly', false);
            data_set($col1, 'brokerfee.readonly', false);
            data_set($col1, 'arrastre.readonly', false);
            if ($companyid == 71) {
                data_set($col1, 'percentsales.readonly', true);
                data_set($col1, 'totalcharges.readonly', false);
            }
            $fields = ['refresh'];
        }

        data_set($col1, 'interest.label', 'Interest');

        if ($companyid == 71) { // buenatech
            data_set($col1, 'ied.label', 'BOC'); // ied in DB
            data_set($col1, 'interest.label', 'VAT'); // interest in DB
            data_set($col1, 'arrastre.label', 'Purchase'); // arrastre in DB
            data_set($col1, 'percentsales.label', 'Percentage'); // charges in DB
            data_set($col1, 'percentsales.class', 'sbccsreadonly');
        }

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'refresh.label', 'SAVE');

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $trno = $config['params']['clientid'];
        $companyid = $config['params']['companyid'];

        $qryselect = '';
        $extraFields = '';
        if ($companyid == 71) {
            $qryselect = ', format(charges, 2) as percentsales, format(overdue, 2) as totalcharges, forex';
            $extraFields = ', charges, overdue, forex';
        }

        return $this->coreFunctions->opentable("select trno, format(ied, 2) as ied, format(bankcharges, 2) as bankcharges, format(interest, 2) as interest, format(brokerfee, 2) as brokerfee, format(arrastre, 2) as arrastre".$qryselect." from (
        select trno, ied, bankcharges, interest, brokerfee, arrastre".$extraFields." from ".$this->head." where trno=".$trno."
        union all
        select trno, ied, bankcharges, interest, brokerfee, arrastre".$extraFields." from ".$this->hhead." where trno=".$trno."
        ) as t limit 1");
    }

    public function data()
    {
        return [];
    }

    // public function loaddata($config)
    // {
    //     $dataparams = $config['params']['dataparams'];
    //     $companyid = $config['params']['companyid'];
    //     $isposted = $this->othersClass->isposted2($dataparams['trno'], "cntnum");
    //     if ($isposted)
    //     return ['status' => false, 'msg' => 'Transaction already posted.'];

    //     $qry = "update " . $this->head . " set ied='" . $dataparams['ied'] . "', bankcharges='" . $dataparams['bankcharges'] . "', interest='" . $dataparams['interest'] . "', brokerfee='" . $dataparams['brokerfee'] . "', arrastre='" . $dataparams['arrastre'] . "'";

    //     if ($companyid == 71) {
    //         $qry .= ", percentsales='" . $dataparams['percentsales'] . "', totalcharges='" . $dataparams['totalcharges'] . "'";
    //     }

    //     $qry .= " where trno=" . $dataparams['trno'];

    //     if ($this->coreFunctions->execqry($qry, 'update')) {
    //         return ['status' => true, 'msg' => 'Record updated', 'closecustomform' => true];
    //     }
    //     return ['status' => false, 'msg' => 'Error updating record'];
    // }

    public function loaddata($config)
    {
        $dataparams = $config['params']['dataparams'];
        $companyid = $config['params']['companyid'];
        $isposted = $this->othersClass->isposted2($dataparams['trno'], "cntnum");
        if ($isposted) return ['status' => false, 'msg' => 'Transaction already posted.'];

        $doc = $config['params']['doc'] ?? 'OTHERCHARGES'; // confirm actual doc value used for this form
        $dateTables = [];
        $lookups = $this->othersClass->buildSanitizeLookups($doc, $companyid, [], false, $dateTables);

        foreach (['ied', 'bankcharges', 'brokerfee', 'arrastre', 'overdue', 'charges'] as $key) {
            $lookups['number'][$key] = true;
        }

        $ied = $this->othersClass->sanitizekeyfieldFast('ied', $dataparams['ied'], $lookups);
        $bankcharges = $this->othersClass->sanitizekeyfieldFast('bankcharges', $dataparams['bankcharges'], $lookups);
        $interest = $this->othersClass->sanitizekeyfieldFast('interest', $dataparams['interest'], $lookups);
        $brokerfee = $this->othersClass->sanitizekeyfieldFast('brokerfee', $dataparams['brokerfee'], $lookups);
        $arrastre = $this->othersClass->sanitizekeyfieldFast('arrastre', $dataparams['arrastre'], $lookups);
        $overdue = $this->othersClass->sanitizekeyfieldFast('overdue', $dataparams['totalcharges'], $lookups);

        $qry = "update ".$this->head." set ied='".$ied."', bankcharges='".$bankcharges."', interest='".$interest."', brokerfee='".$brokerfee."', arrastre='".$arrastre."', overdue='".$overdue."'";
        
        if ($companyid == 71) {
            $forexRow = $this->coreFunctions->opentable("select forex from ".$this->head." where trno=".$dataparams['trno']." limit 1");
            $forex = isset($forexRow[0]->forex) ? floatval($forexRow[0]->forex) : 0;

            $boc = floatval($ied);
            $bankchargesVal = floatval($bankcharges);
            $brokerfeeVal = floatval($brokerfee);
            $vat = floatval($interest);
            $arrastreVal = floatval($arrastre);

            $denominator = $arrastreVal * $forex;
            $percentsales = $denominator != 0 ? ((($boc + $bankchargesVal + $brokerfeeVal) - $vat) / $denominator) : 0;
            $charges = $this->othersClass->sanitizekeyfieldFast('charges', $percentsales, $lookups);
            $qry .= ", charges='".$charges."'";
        }

        $qry .= " where trno=".$dataparams['trno'];

        if ($this->coreFunctions->execqry($qry, 'update')) {
            return ['status' => true, 'msg' => 'Record updated'];
        }
        return ['status' => false, 'msg' => 'Error updating record'];
    }
} //end class
