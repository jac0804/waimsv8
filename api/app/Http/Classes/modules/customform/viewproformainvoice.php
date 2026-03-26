<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewproformainvoice
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'Proforma Invoice';
    public $gridname = 'tableentry';
    private $fields = ['proformainvoice', 'proformadate'];
    private $table = 'headinfotrans';

    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';

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
        $trno = $config['params']['clientid'];
        $fields = ['proformainvoice', 'proformadate'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");
        array_push($fields, 'refresh');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'GENERATE PROFORMA INV.');
        data_set($col1, 'proformainvoice.readonly', true);
        data_set($col1, 'proformadate.readonly', true);

        $fields = [['mop1', 'dp'], ['mop2', 'cod'], 'close'];
        $col2 = $this->fieldClass->create($fields);

        if (!$isposted) {
            array_push($fields, 'close');
        }

        data_set($col2, 'close.label', 'Save');
        data_set($col2, 'dp.label', '');
        data_set($col2, 'cod.label', '');
        data_set($col2, 'dp.readonly', false);

        $fields = ['outstanding'];
        $col3 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        $doc = $config['params']['doc'];

        if (!isset($config['params']['dataparams']['trno'])) {
            $trno = $config['params']['clientid'];
        } else {
            $trno = $config['params']['dataparams']['trno'];
        }

        $isposted = $this->othersClass->isposted2($trno, "transnum");
        $headinfotable = $isposted == false ? "headinfotrans" : "hheadinfotrans";

        $data =  $this->coreFunctions->opentable("
            select $trno as trno,
            '' as proformainvoice,
            left(now(),10) as proformadate,
            '0' as dp,
            '0' as cod,
            '0' as outstanding,
            '' as mop1,
            '' as mop2
            ");

        $tablename = 'proformainv';

        $qry = "select pro.trno, ifnull(pro.docno,'') as proformainvoice, ifnull(pro.dateid,left(now(),10)) as proformadate
         from " . $tablename . " as pro
        where pro.trno=? ";
        $res = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($res)) {
            $data[0]->trno = $res[0]->trno;
            $data[0]->proformainvoice = $res[0]->proformainvoice;
            $data[0]->proformadate = $res[0]->proformadate;
        }

        $qry = "select ifnull(headinfo.dp,0) as dp, ifnull(headinfo.cod,0) as cod, ifnull(headinfo.outstanding,0) as outstanding, 
        headinfo.mop1, headinfo.mop2 from " . $headinfotable . " as headinfo where headinfo.trno = ?";

        $res = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($res)) {
            $data[0]->dp = $res[0]->dp;
            $data[0]->cod = $res[0]->cod;
            $data[0]->outstanding = $res[0]->outstanding;
            $data[0]->mop1 = $res[0]->mop1;
            $data[0]->mop2 = $res[0]->mop2;
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
        $doc = $config['params']['doc'];
        $trno = $config['params']['dataparams']['trno'];
        $btnaction = $config['params']['action2'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");
        $headinfotable = $isposted == false ? "headinfotrans" : "hheadinfotrans";
        switch ($btnaction) {
            case 'close':
                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                $data = [
                    'trno' => $trno,
                    'dp' => $config['params']['dataparams']['dp'],
                    'cod' => $config['params']['dataparams']['cod'],
                    'outstanding' => $config['params']['dataparams']['outstanding'],
                    'mop1' => $config['params']['dataparams']['mop1'],
                    'mop2' => $config['params']['dataparams']['mop2'],
                    'editby' => $config['params']['user'],
                    'editdate' => $current_timestamp
                ];

                $qry = "
                select trno as value 
                from headinfotrans where trno = ? 
                union all
                select trno as value 
                from hheadinfotrans where trno = ? 
                LIMIT 1";
                $count = $this->coreFunctions->datareader($qry, [$trno, $trno]);

                if ($count != '') {
                    $this->coreFunctions->sbcupdate($headinfotable, $data, ['trno' => $trno]);
                } else {
                    $this->coreFunctions->insertGetId($headinfotable, $data);
                }


                $txtdata = $this->paramsdata($config);
                return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'txtdata' => $txtdata];

                break;

            default:
                $lastseq = 0;
                if ($config['params']['dataparams']['proformainvoice'] == "") {
                    $curyr = date("Y");
                    $lastseq = $this->coreFunctions->getfieldvalue('proformainv', 'seq', 'year=?', [$curyr], 'year desc,seq desc') + 1;
                    $invlength = 5;

                    if (floatval($lastseq) == 0) {
                        $lastseq = 1;
                    }
                    $curseq = 'P' . $lastseq;
                    $newinvno = str_replace('P', '', $curyr . '-' . $this->othersClass->Padj($curseq, $invlength));
                    $proformainvoice = $newinvno;
                    $proformadate = date("Y-m-d");

                    $data = [
                        'trno' => $trno,
                        'docno' => $proformainvoice,
                        'dateid' => $proformadate,
                        'seq' => $lastseq,
                        'year' => $curyr,
                    ];
                    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data['editby'] = $config['params']['user'];
                    $tablename = 'proformainv';
                    $this->coreFunctions->sbcinsert($tablename, $data);
                    $this->logger->sbcwritelog(
                        $trno,
                        $config,
                        'PROFORMA INVOICE',
                        ' PROFORMA INVOICE NUMBER: ' . $data['docno']
                            . ', PROFORMA DATE: ' . $data['dateid']
                    );
                }

                $txtdata = $this->paramsdata($config);

                return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'txtdata' => $txtdata, 'ww' => $lastseq];
                break;
        }
    }

    public function checkdata($trno, $tablename)
    {
        $data =  $this->coreFunctions->opentable("select trno from " . $tablename . " where trno = ? ", [$trno]);
        if ($data) {
            return true;
        } else {
            return false;
        }
    }
}
