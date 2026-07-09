<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class financerates
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'FINANCE RATES';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = 'transnum';
    private $logger;

    public $tablelogs = 'item_log';

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
        $this->modulename = "FINANCE RATES";

        $fields = ['terms', 'dp', 'interest', 'rrfactor',];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'terms.lookupclass', 'financeterms');
        data_set($col1, 'rrfactor.readonly', false);

        $fields = ['penalty', 'fmiscfee', 'rebate'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'penalty.readonly', false);
        data_set($col2, 'fmiscfee.readonly', false);
        data_set($col2, 'rebate.readonly', false);

        $fields = ['refresh'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'refresh.label', 'SAVE');


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $itemid = $config['params']['clientid'];
        $select = "select $itemid as itemid, '' as terms,
               0 as dp, 0 as interest, 0 as rrfactor,'' as penalty, 0 as fmiscfee, 0 as rebate";

        $existing = $this->coreFunctions->datareader("select itemid as value from mcfinancerate where itemid = $itemid", [], '', true);

        if ($existing) {
            $select = "select itemid, terms, dp, interest, factor as rrfactor, penalty, miscfee as fmiscfee, rebate from mcfinancerate where itemid = $itemid";
        }
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
        $itemid = $config['params']['dataparams']['itemid'];
        $terms = $config['params']['dataparams']['terms'];
        $dp = $config['params']['dataparams']['dp'];
        $interest = $config['params']['dataparams']['interest'];
        $factor = $config['params']['dataparams']['rrfactor'];
        $penalty = $config['params']['dataparams']['penalty'];
        $miscfee = $config['params']['dataparams']['fmiscfee'];
        $rebate = $config['params']['dataparams']['rebate'];

        $existing = $this->coreFunctions->datareader("select itemid as value from mcfinancerate where itemid = $itemid", [], '', true);

        if ($existing) {
            $data = [
                'terms' => $terms,
                'dp' => $dp,
                'interest' => $interest,
                'factor' => $factor,
                'penalty' => $penalty,
                'miscfee' => $miscfee,
                'rebate' => $rebate,
                'editby' => $config['params']['user'],
                'editdate' => $this->othersClass->getCurrentTimeStamp()
            ];

            $this->coreFunctions->sbcupdate("mcfinancerate", $data, ['itemid' => $itemid]);
        } else {
            $data = [
                'itemid' => $itemid,
                'terms' => $terms,
                'dp' => $dp,
                'interest' => $interest,
                'factor' => $factor,
                'penalty' => $penalty,
                'miscfee' => $miscfee,
                'rebate' => $rebate,
                'encodedby' => $config['params']['user'],
                'encodeddate' => $this->othersClass->getCurrentTimeStamp()
            ];

            $this->coreFunctions->sbcinsert("mcfinancerate", $data);
        }

        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'reloadlisting' => true];
    }
}
