<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;

class copyprev_item
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;

    public $modulename = 'COPY PREVIOUS ITEMS';
    public $gridname = 'inventory';
    private $logger;

    public $tablelogs = 'transnum_log';
    public $style = 'width:30%;max-width:500px;';
    public $issearchshow = false;
    public $showclosebtn = true;

    // doc types supported by othersClass->copypreviousstock
    private $docs = array(
        'PC' => 'Physical Count',
        'SO' => 'Sales Order'
    );

    public function __construct($params = null)
    {
        $this->fieldClass    = new txtfieldClass;
        $this->tabClass      = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup  = new companysetup;
        $this->othersClass   = new othersClass;
        $this->logger        = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    private function getdoc($config)
    {
        if (isset($config['params']['doc']) && isset($this->docs[$config['params']['doc']])) {
            return $config['params']['doc'];
        }
        if (isset($config['params']['dataparams']['doc'])) {
            return $config['params']['dataparams']['doc'];
        }
        return isset($config['params']['doc']) ? $config['params']['doc'] : '';
    }

    public function createHeadField($config)
    {
        $doc = $this->getdoc($config);
        $name = isset($this->docs[$doc]) ? $this->docs[$doc] : $doc;

        $fields = ['docno', 'refresh'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'docno.type', 'input');
        data_set($col1, 'docno.label', 'Document#');
        data_set($col1, 'docno.placeholder', 'Enter ' . $doc . ' docno to copy from');

        data_set($col1, 'refresh.label', 'Copy Prev ' . $doc);

        return ['col1' => $col1];
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }

    public function paramsdata($config)
    {
        $result = $this->getheaddata($config);
        return $result;
    }

    public function getheaddata($config)
    {
        $trno = isset($config['params']['trno']) ? (int)$config['params']['trno'] : 0;
        $doc = $this->getdoc($config);


        $qry = "select $trno as trno, '' as docno, ? as doc";
        $data = $this->coreFunctions->opentable($qry, array($doc));

        return $data;
    }

    public function loaddata($config)
    {
        $docno = isset($config['params']['dataparams']['docno']) ? trim($config['params']['dataparams']['docno']) : '';

        if ($docno == '') {
            return ['status' => false, 'msg' => 'Please enter the source document#.'];
        }

        $doc = $this->getdoc($config);
        if (!isset($this->docs[$doc])) {
            return ['status' => false, 'msg' => 'Copy previous is not available for ' . $doc . '.'];
        }
        $config['params']['doc'] = $doc;

        if (!isset($config['params']['trno'])) {
            if (isset($config['params']['dataparams']['trno'])) {
                $config['params']['trno'] = (int)$config['params']['dataparams']['trno'];
            } else if (isset($config['params']['addedparams']['trno'])) {
                $config['params']['trno'] = (int)$config['params']['addedparams']['trno'];
            } else {
                return ['status' => false, 'msg' => 'Transaction not found. Please save the document first.'];
            }
        }

        if ((int)$config['params']['trno'] == 0) {
            return ['status' => false, 'msg' => 'Please save the ' . $this->docs[$doc] . ' first.'];
        }

        $config['params']['sourcedocno'] = $docno;

        $result = $this->othersClass->copypreviousstock($config);

        if (!empty($result['status'])) {
            $result['closecustomform'] = true;
        }

        return $result;
    }

    public function data($config)
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
}
