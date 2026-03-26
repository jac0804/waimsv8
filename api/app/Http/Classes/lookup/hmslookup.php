<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use Illuminate\Http\Request;
use App\Http\Requests;


class hmslookup
{
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
  }


  //A
  public function lookupratecode($config)
  {

    $title = 'List of Rate Codes';

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'tableentry',
      'action' => 'getskillsreq',
    );

    // lookup columns
    $cols = array(
      array('name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select line as keyid, line, code, description, isinactive from hmsratesetup where isinactive=0 order by description;";
    $data = $this->coreFunctions->opentable($qry);;

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function lookupqservice($config)
  {
    $plotting = array();
    $plottype = 'plothead';
    $title = 'Service';

    $plotting = array('servicedep' => 'code', 'serviceline' => 'line');
    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = [
      ['name' => 'servicedep', 'label' => 'Line', 'align' => 'left', 'field' => 'line', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'serviceline', 'label' => 'Service', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'description', 'label' => 'Description', 'align' => 'left', 'field' => 'description', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];
    // $qry = "select distinct date_format(dateid,'%m/%d/%Y') as dateid from journal where isok2 = 0 " . $branch . " order by dateid";
    $qry = "select '' as line, '' as code, '' as description
            union all
            select line, code, description from reqcategory where code <> '' and isservice = 1";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function lookupcounter($config)
  {
    $plotting = array();
    $plottype = 'plothead';
    $title = 'Counter';

    $plotting = array('counter' => 'code', 'counterline' => 'line');
    $lookupsetup = array(
        'type' => 'single',
        'title' => $title,
        'style' => 'width:900px;max-width:900px;'
      );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = [
      ['name' => 'counter', 'label' => 'Line', 'align' => 'left', 'field' => 'line', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'counterline', 'label' => 'Counter', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];
    // $qry = "select distinct date_format(dateid,'%m/%d/%Y') as dateid from journal where isok2 = 0 " . $branch . " order by dateid";
    $qry = "select '' as line, '' as code
            union all
            select line, code from reqcategory where code <> '' and iscounter = 1";

    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

}
