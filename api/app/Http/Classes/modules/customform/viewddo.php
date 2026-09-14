<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sbcscript\sbcscript;
class viewddo
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'DDO HISTORY';
    public $gridname = 'customformacctg';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    public $style = 'width:100%;max-width:100%;height:100%;max-height:100%;';
    public $issearchshow = true;
    public $showclosebtn = true;
    private $sbcscript;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 6032);
        return $attrib;
    }

    public function createTab($config)
    {
        $this->modulename = 'DDO HISTORY - ' . $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$config['params']['clientid']]);
        $cols = ['divname','dateid', 'dateid2', 'docno'];
        $tab = [$this->gridname => ['gridcolumns' => $cols]];
        $stockbuttons = [];
        foreach ($cols as $key => $value) {
            $$value = $key;
        }

        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['totalfield'] = [];   

        $obj[0][$this->gridname]['columns'][$divname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$divname]['label'] = "Post History";

        $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateid]['label'] = "From";

        $obj[0][$this->gridname]['columns'][$dateid2]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$dateid2]['label'] = "To";

        $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$docno]['label'] = "DDO Number";

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);

        return $obj;
    }
    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    
    public function createHeadField($config)
    {
        $fields = ['refresh'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col2, 'refresh.action', 'pdc');

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable('select 1');
    }

    public function data($config)
    {
        return [];
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['clientid'];
        $qry = "select detail.empid,head.trno, head.docno, date(head.paydate) as dateid, date(head.paydate2) as dateid2, division.divname, detail.rate
        from hddhead as head
        join hdddetail as detail on detail.trno = head.trno
        left join division on division.divid = head.divid
        where detail.empid = $clientid 
        order by head.trno desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data, 'qry' => $qry];
    }

    public function sbcscript($config) //auto refresh
    {
        return $this->sbcscript->skcustomform($config);
    }









}