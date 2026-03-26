<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

use Datetime;

class finalinterview
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'Final Interview';
    public $gridname = 'inventory';
    private $fields = [];
    public $tablenum = '';
    public $head = 'app';

    public $tablelogs = 'masterfile_log';
    public $statlogs = 'app_stat';

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
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];

        $fields = ['dateid2', 'refresh'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'refresh.label', 'SAVE');
        data_set($col1, 'dateid2.label', 'Date');
        data_set($col1, 'dateid2.readonly', false);

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' =>  $col2);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $doc = $config['params']['doc'];
        $trno = $config['params']['trno'];
        $select = "select '' as dateid2, " . $trno . " as trno";

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
        $dateid2 = $config['params']['dataparams']['dateid2'];
        if ($dateid2 == '') {
            return ['status' => false, 'msg' => 'Kindly choose the date.', 'data' => []];
        }

        if ($this->coreFunctions->sbcupdate($this->head, ['statid' => 100], ['empid' => $trno])) {
            $this->logger->sbcstatlog($trno, $config, 'HEAD', 'Tag for Final Interview.', $this->statlogs, $dateid2);
            return ['status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
        } else {
            return ['status' => false, 'msg' => 'Failed to tag for the Final Interview.'];
        }


        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => [],  'backlisting' => true];
    }
}
