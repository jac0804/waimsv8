<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use App\Http\Classes\companysetup;
use Illuminate\Http\Request;
use App\Http\Requests;
use DateTime;

class productportallookup
{
    private $othersClass;
    private $sqlquery;
    private $coreFunctions;
    private $companysetup;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->sqlquery = new sqlquery;
        $this->companysetup = new companysetup;
    }
    public function lookupcarbrand($config)
    {
        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Car Brand',
            'style' => 'width:900px;max-width:900px;'
        );
        $plotting = ['carbrand' => 'carbrand', 'carid' => 'carid'];

        $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => $plotting
        );

        // lookup columns
        $cols = array(
            array('name' => 'carbrand', 'label' => 'Car Brand', 'align' => 'left', 'field' => 'carbrand', 'sortable' => true, 'style' => 'font-size:16px;'),
        );

        $qry = "
        select 0 as carid, '' as carbrand union all
        select id as carid, brand as carbrand from carbrand";

        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
    public function lookupposition($config)
    {
        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Position',
            'style' => 'width:900px;max-width:900px;'
        );
        $plotting = ['position' => 'position', 'positionid' => 'id'];

        $plotsetup = array(
            'plottype' => 'plothead',
            'plotting' => $plotting
        );

        // lookup columns
        $cols = array(
            array('name' => 'position', 'label' => 'Position', 'align' => 'left', 'field' => 'position', 'sortable' => true, 'style' => 'font-size:16px;'),
        );

        $qry = "
        select 0 as id, '' as position union all
        select id, positions as position from positions";

        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }
}
