<?php

namespace App\Http\Classes\modules\dashboard;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class viewpiclogs
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Picture Logs';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:500px;max-width:500px;';
    public $issearchshow = true;
    public $showclosebtn = true;



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
        return [];
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = [];
        return $obj;
    }

    public function createHeadField($config)
    {

        $fields = ['picture'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'picture.type', 'imageview');
        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        $idbarcode = $config['params']['row']['idbarcode'];
        $query = "select concat('/images',picture) as picture from timerec where userid = '" . $idbarcode . "' and date(timeinout)='" . $this->othersClass->getCurrentDate() . "'";
        return $this->coreFunctions->opentable($query);
    }

    public function data($config)
    {
        $idbarcode = $config['params']['row']['idbarcode'];
        $qry = "select concat('/images',picture) as picture from timerec where userid=? and date(timeinout)='" . $this->othersClass->getCurrentDate() . "'";
        $data = $this->coreFunctions->opentable($qry, [$idbarcode]);
        return $data;
    }
} //end class
