<?php

namespace App\Http\Classes\modules\customform;

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

class posregistration
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'POS Registration';
    public $gridname = 'entrygrid';
    public $head = '';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $fields = [];
    public $style = 'width:100%;max-width:100%;';
    public $issearchshow = false;
    public $showclosebtn = false;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 5940
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createHeadbutton($config)
    {
        return [];
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function createHeadField($config)
    {

        $fields = ['divname', 'company', 'regcode', ['crtype', 'type'], ['branch', 'station'], 'serialno', 'licenseno', 'rem', 'refresh'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'divname.label', 'Provider');
        data_set($col1, 'regcode.label', 'Registration Key');
        data_set($col1, 'licenseno.label', 'Access Key');
        data_set($col1, 'type.label', 'Serial Type');
        data_set($col1, 'crtype.label', 'Access Limit');
        data_set($col1, 'refresh.label', 'REGISTER');

        data_set($col1, 'company.type', 'input');
        data_set($col1, 'serialno.type', 'input');
        data_set($col1, 'station.type', 'input');
        data_set($col1, 'type.type', 'qselect');
        data_set($col1, 'crtype.type', 'qselect');
        data_set($col1, 'divname.type', 'qselect');

        $types = [
            ['label' => 'Serial', 'value' => 'Serial'],
            ['label' => 'Volume', 'value' => 'Volume']
        ];
        data_set($col1, 'type.options', $types);

        $crtypes = [
            ['label' => 'DEMO', 'value' => 'DEMO'],
            ['label' => 'LICENSED', 'value' => 'LICENSED']
        ];
        data_set($col1, 'crtype.options', $crtypes);

        data_set($col1, 'branch.readonly', false);
        data_set($col1, 'station.readonly', false);
        data_set($col1, 'rem.readonly', false);
        data_set($col1, 'licenseno.readonly', true);

        data_set($col1, 'refresh.style', 'width:100%');

        $centers = [];
        $center = $this->coreFunctions->opentable("select code, name from center order by name");
        foreach ($center as $key => $value) {
            array_push($centers, ['label' => $value->name, 'value' => $value->code]);
        }
        data_set($col1, 'divname.options', $centers);

        return array('col1' => $col1);
    }

    public function data($config)
    {
        return $this->paramsdata($config);
    }


    public function paramsdata($config)
    {
        $data = $this->coreFunctions->opentable("
      select 
      '' as divname,
      '' as company,
      '' as regcode,
      '' as branch,
      '' as station,
      '' as serialno,
      '' as licenseno,
      '' as crtype,
      '' as type,
      '' as rem
    ");
        if (!empty($data)) {
            return $data[0];
        } else {
            return [];
        }
    }


    public function headtablestatus($config)
    {
        // should return action
        $action = $config['params']["action2"];

        switch ($action) {

            default:
                return ['status' => false, 'msg' => 'Data is not yet setup in the headtablestatus.'];
                break;
        }
    }
}
