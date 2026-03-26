<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class customformrevisionom
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'REVISION';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = 'transnum';
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
        $this->modulename = "REVISION REMARKS";

        $fields = ['oraclecode', 'itemname', 'ctrlno', 'rem', 'refresh'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'refresh.label', 'SAVE');
        data_set($col1, 'ctrlno.label', 'Ctrl No.');
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
        $select = "select stock.trno,stock.line,head.docno, stock.oraclecode,info.itemdesc as itemname,info.ctrlno,stock.statid,stat2.status
                    from omstock as stock
                    left join omhead as head on head.trno=stock.trno
                    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
                    left join trxstatus as stat2 on stat2.line=stock.statid
                    where stock.trno = $trno and stock.line= $line";
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
        $oraclecode = $config['params']['dataparams']['oraclecode'];
        $ctrlno = $config['params']['dataparams']['ctrlno'];
        $statid = $config['params']['dataparams']['statid'];
        $status = $config['params']['dataparams']['status'];
        $rem = $config['params']['dataparams']['rem'];
        $line = $config['params']['dataparams']['line'];
        $statcur = '';
        if ($rem == '') {
            return ['status' => false, 'msg' => 'Please input valid remarks', 'data' => []];
        }
        $user = $config['params']['user'];
        switch ($statid) {
            case 0: //Draft 
                return ['status' => false, 'msg' => 'Not allow. - Current Status: For Receiving', 'data' => [], 'reloadlisting' => true];
                break;
            case 46: //For SO

                $chksono = $this->coreFunctions->opentable("select sono from omso where trno=? and line=? and rtno= ''", [$trno, $line]);
                if (empty($chksono)) {
                    $this->coreFunctions->sbcupdate("omstock", ['statid' => 0, 'rrdate' => NULL], ['trno' => $trno, 'line' => [$line]]);
                    $statcur = 'For Oracle Receiving';
                } else {
                    return ['status' => false, 'msg' => 'No revisions permitted. Already have a SO#.', 'data' => [], 'reloadlisting' => true];
                }

                break;
            case 39: //For Posting
                $this->coreFunctions->sbcupdate("omstock", ['statid' => 46], ['trno' => $trno, 'line' => [$line]]);
                $statcur = 'For SO';
                break;
            case 12: //Posted
                return ['status' => false, 'msg' => 'Not allow. - Current Status: Posted', 'data' => [], 'reloadlisting' => true];
                break;
        }

        $this->logger->sbcwritelog($trno, $config, 'REVISION', 'Oracle Code: ' . $oraclecode . ' , Notes : ' . $rem, 'transnum_log');
        $this->logger->sbcwritelog($trno, $config, 'STATUS UPDATE', $status . ' -> ' . $statcur, 'transnum_log');
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [], 'reloadlisting' => true];
    }
}
