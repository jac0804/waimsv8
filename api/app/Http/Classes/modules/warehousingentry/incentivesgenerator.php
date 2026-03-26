<?php

namespace App\Http\Classes\modules\warehousingentry;

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

class incentivesgenerator
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'INCENTIVES GENERATOR';
    public $gridname = 'entrygrid';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%';
    public $issearchshow = true;
    public $showclosebtn = false;

    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 2518, 'view' => 2518);
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtab2($access, $config)
    {
        $agent = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewincentivesagent', 'totalfield' => []]];
        $agentannual = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewincentivesagentannual', 'totalfield' => []]];
        $dealer = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewincentivesdealer', 'totalfield' => []]];

        $return = [];
        $return['AGENT'] = ['icon' => 'fa fa-users', 'customform' => $agent];
        $return['AGENT - ANNUAL'] = ['icon' => 'fa fa-users', 'customform' => $agentannual];
        $return['DEALER CUSTOMER'] = ['icon' => 'fa fa-users', 'customform' => $dealer];
        return $return;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadbutton($config)
    {
        $btns = []; //actionload - sample of adding button in header - align with form/module name
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);

        $fields = [];
        $col2 = $this->fieldClass->create($fields);

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function data($config)
    {
        return [];
    }
}
