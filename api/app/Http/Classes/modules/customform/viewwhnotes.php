<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewwhnotes
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'Notes';
    public $gridname = 'tableentry';
    private $fields = ['rem'];
    private $table = 'whdoc';

    public $tablelogs = 'client_log';

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
        $fields = ['rem', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'rem.readonly', false);
        data_set($col1, 'rem.type', 'wysiwyg');
        data_set($col1, 'refresh.label', 'UPDATE');
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {

        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        $clientid = $config['params']['clientid'];
        $lookupclass = '';
        if (isset($config['params']['row'])) {
            $line = $config['params']['row']['line'];
            $lookupclass = $config['params']['row']['tabtype'];

            switch ($lookupclass) {
                case 'whdoc':
                    $this->modulename = 'DOCUMENTS NOTES';
                    break;
                case 'whnods';
                    $this->modulename = 'NODS NOTES';
                    break;
                case 'whjobreq':
                    $this->modulename = 'JOB REQUESTS NOTES';
                    break;
            }
        } else {
            $line = $config['params']['dataparams']['line'];
            $lookupclass = $config['params']['dataparams']['tabtype'];
        }
        $table = $lookupclass;
        $qry = "select line, rem, '" . $lookupclass . "' as tabtype from " .  $table . " where whid=? and line=?";

        return $this->coreFunctions->opentable($qry, [$clientid, $line]);
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
        $clientid  = $config['params']['clientid'];
        if (isset($config['params']['dataparams'])) {

            $table = $config['params']['dataparams']['tabtype'];
            if ($table != '') {
                $data = [
                    'rem' => $config['params']['dataparams']['rem'],
                    'editby' => $config['params']['user'],
                    'editdate' => $this->othersClass->getCurrentTimeStamp()
                ];
                $this->coreFunctions->sbcupdate($table, $data, ['whid' => $clientid, 'line' => $config['params']['dataparams']['line']]);
            }
        }

        $data = $this->getheaddata($config);
        return ['status' => true, 'msg' => 'Successfully updated.', 'data' => $data];
    }
}
