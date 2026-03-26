<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewleadtimesetting
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'Lead Time Settings';
    public $gridname = 'tableentry';
    private $fields = ['leadfrom', 'leadto', 'leaddur', 'advised'];
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
        $fields = ['leadfrom', 'leadto', 'leaddur', 'advised'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");

        if (!$isposted) {
            if ($config['params']['doc'] == 'QS' || $config['params']['doc'] == 'OS') { // quotation for save button
                array_push($fields, 'refresh');
            }
        }

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'leadfrom.readonly', true);
        data_set($col1, 'leadto.readonly', true);
        data_set($col1, 'leaddur.readonly', true);
        data_set($col1, 'leaddur.class', 'csleaddur sbccsreadonly');
        data_set($col1, 'leaddur.type', 'lookup');
        data_set($col1, 'leaddur.action', 'lookuprandom');
        data_set($col1, 'leaddur.lookupclass', 'lookup_leadtimedur');
        data_set($col1, 'advised.readonly', true);
        data_set($col1, 'refresh.label', 'save');

        if (!$isposted) {
            if ($config['params']['doc'] == 'QS' || $config['params']['doc'] == 'OS') { // quotation for save button
                array_push($fields, 'refresh');
                data_set($col1, 'leadfrom.readonly', false);
                data_set($col1, 'leadto.readonly', false);
                data_set($col1, 'leaddur.readonly', true);
                data_set($col1, 'advised.readonly', false);
                data_set($col1, 'refresh.label', 'save');
            }
        }

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        $fields = [];
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

        $head = 'headinfotrans';
        $hhead = 'hheadinfotrans';

        switch ($doc) {
            case 'SQ':
                $tbl = strtolower($doc) . 'head';
                $htbl = 'h' . strtolower($doc) . 'head';
                break;

            case 'OS':
                $tbl = "oshead";
                $htbl = "hoshead";
                break;

            case 'AO':
                $tbl = "sshead";
                $htbl = "hsshead";
                break;
        }

        $select = "select hi.trno, ifnull(hi.leadfrom,'0') as leadfrom, ifnull(hi.leadto,'0') as leadto, 
                   ifnull(hi.leaddur,'') as leaddur, ifnull(hi.advised,'0') as advised";

        switch ($doc) {
            case 'QS':
            case 'QT':
            case 'OS':

                $qry = "" . $select . "
                from " . $head . " as hi
                where hi.trno = ?
                union all
                " . $select . "
                from " . $hhead . " as hi
                where hi.trno = ?";

                $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
                if (empty($data)) {
                    $data =  $this->coreFunctions->opentable("
                        select $trno as trno, 
                        '0' as leadfrom,
                        '0' as leadto, 
                        '' as leaddur, 
                        '0' as advised ");
                }
                return $data;
                break;

            default:

                switch ($doc) {
                    case 'SQ':
                        $ltbl = "hqshead";
                        break;

                    case 'AO':
                        $ltbl = "hsrhead";
                        break;
                }

                $qry = "" . $select . "
                from " . $hhead . " as hi
                left join " . $ltbl . " as qs on qs.trno = hi.trno
                left join " . $tbl . " as sq on sq.trno = qs.sotrno
                where sq.trno=? 
                union all
                " . $select . "
                from " . $hhead . " as hi
                left join " . $ltbl . " as qs on qs.trno = hi.trno
                left join " . $htbl . " as sq on sq.trno = qs.sotrno
                where sq.trno=? ";

                $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
                if (empty($data)) {
                    $data =  $this->coreFunctions->opentable("
                        select $trno as trno, 
                        '0' as leadfrom,
                        '0' as leadto, 
                        '' as leaddur, 
                        '0' as advised ");
                }
                return $data;
                break;
        }
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
        $leadfrom = $config['params']['dataparams']['leadfrom'];
        $leadto = $config['params']['dataparams']['leadto'];
        $leaddur = $config['params']['dataparams']['leaddur'];
        $advised = $config['params']['dataparams']['advised'];
        $editby = $config['params']['user'];
        $editdate = $this->othersClass->getCurrentTimeStamp();

        $data = [
            'trno' => $trno,
            'leadfrom' => $leadfrom,
            'leadto' => $leadto,
            'leaddur' => $leaddur,
            'advised' => $advised,
            'editby' => $editby,
            'editdate' => $editdate
        ];

        $tablename = 'headinfotrans';

        if ($this->othersClass->isposted2($trno, 'transnum')) {
            return ['status' => false, 'msg' => 'Failed to save; already posted.'];
        }

        if (!$this->checkdata($trno, $tablename)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key],'',$config['params']['companyid']);
            }
            $this->coreFunctions->sbcinsert($tablename, $data);
            $this->logger->sbcwritelog(
                $trno,
                $config,
                'CREATE LEADTIME',
                ' LEADFROM: ' . $data['leadfrom']
                    . ', LEADTO: ' . $data['leadto']
                    . ', LEADDUR: ' . $data['leaddur']
                    . ', ADVISED: ' . $data['advised']
            );
        } else {
            foreach ($data as $key => $value) {
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key],'',$config['params']['companyid']);
            }
            $this->coreFunctions->sbcupdate($tablename, $data, ['trno' => $trno]);
        }

        $txtdata = $this->paramsdata($config);

        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => [], 'txtdata' => $txtdata];
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
