<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class lost
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    private $logger;
    public $modulename = 'Lost';
    public $gridname = 'customformacctg';
    private $fields = ['islost', 'lostdate', 'reason'];
    private $table = 'pxhead';
    private $htable = 'hpxhead';

    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';

    public $style = 'width:100px;max-width:200px;';
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
        $attrib = array('load' => 5376, 'edit' => 5377);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = ['reason'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'reason.label', 'Reason for Lost');

        $fields = ['refresh'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'refresh.label', 'Lost');
        data_set($col2, 'refresh.style', 'width:100%;height:100%;');

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function lost_query($trno)
    {
        $query = "select trno,reason,docno,islost from " . $this->table . " where trno = ?
        union all
        select trno,reason,docno,islost from " . $this->htable . " where trno = ?";
        $data = $this->coreFunctions->opentable($query, [$trno, $trno]);
        return $data;
    }

    public function getheaddata($config)
    {
        $trno = isset($config['params']['clientid']) ? $config['params']['clientid'] : $config['params']['dataparams']['trno'];
        $data = $this->lost_query($trno);

        return $data;
    }

    public function data($config)
    {
        return [];
    }

    public function createTab($config)
    {
        $obj = [];
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

        $head = $config['params']['dataparams'];

        if (empty($head['reason'])) {
            return ['status' => false, 'msg' => 'Please input Reason for Lost.'];
        }

        if ($head['islost']) {
            $reason = $this->coreFunctions->datareader("
            select reason as value from $this->table where trno= " . $head['trno'] . "
            union all 
            select reason as value from $this->htable where trno= " . $head['trno'] . "");
            if ($head['reason'] != "" && $head['reason'] != $reason) {
                return ['status' => false, 'msg' => 'This transaction is already marked as lost, and the reason cannot be modified.'];
            }
            return ['status' => false, 'msg' => 'This transaction is already marked as lost.'];
        }
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = [
            'islost' => 1,
            'lostdate' => $date,
            'reason' => $head['reason']
        ];
        foreach ($this->fields as $key) {
            if (isset($data[$key])) {
                $data[$key] = $data[$key];
                $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
            }
        }

        $data['editdate'] = $date;
        $data['editby'] = $config['params']['user'];
        $lock = $this->othersClass->islocked($config);
        if (!$lock) {
            $data['lockdate'] = $date;
        }
        $this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $head['trno']]);
        $txtdata = $this->getheaddata($config);
        return ['status' => true, 'msg' => 'Successfully update Lost.', 'trno' => $head['trno'], 'reloadhead' => true, 'txtdata' => $txtdata];
    }
}
