<?php

namespace App\Http\Classes\modules\actionlisting;

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

class barcodeassigning
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'BARCODE ASSIGNING';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'hcdstock';
    public $tablelogs = 'transnum_log';
    private $othersClass;
    private $logger;
    public $style = 'width:100%;';
    private $fields = ['terms', 'days'];
    public $showclosebtn = false;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 1447,
            'save' => 1447,
            'view' => 1447
        );
        return $attrib;
    }

    public function createTab($config)
    {

        $fields = ['moduledesc', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'moduledesc.type', 'qselect');
        data_set($col1, 'moduledesc.readonly', false);
        data_set($col1, 'moduledesc.class', 'csmoduledesc');
        data_set(
            $col1,
            'moduledesc.options',
            array(
                ['label' => ''],
                ['label' => 'Purchase Requisition'],
                ['label' => 'Canvass Sheet'],
                ['label' => 'Purchase Order'],
                ['label' => 'Receiving Report']
            )
        );

        $fields = ['clientname'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'clientname.label', 'Search');
        data_set($col2, 'clientname.type', 'input');
        data_set($col2, 'clientname.readonly', false);

        $fields = ['refresh'];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'refresh.type', 'actionbtn');

        $fields = ['barcode', 'blstockcard'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'barcode.label', 'Assigned barcode');
        data_set($col4, 'barcode.lookupclass', 'lookupitem');

        $gridheadinput = ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];

        $otapproved = 0;
        $itemdesc = 1;
        $specs = 2;
        $docno = 3;
        $tab = [$this->gridname => [
            'gridcolumns' => ['otapproved', 'itemdesc', 'specs', 'docno'],
            'gridheadinput' => $gridheadinput
        ]];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = ['ITEMS'];

        $obj[0][$this->gridname]['columns'][$otapproved]['label'] = 'Select';
        $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = 'Item Description';

        $obj[0][$this->gridname]['columns'][$otapproved]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px';
        $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px';

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        return $obj;
    }

    public function gridheaddata($config)
    {
        $moduledesc = '';
        if (isset($config['params']['gridheaddata']['moduledesc']['label'])) {
            $moduledesc = $config['params']['gridheaddata']['moduledesc']['label'];
        } else {
            if (isset($config['params']['gridheaddata']['moduledesc'])) {
                $moduledesc = $config['params']['gridheaddata']['moduledesc'];
            }
        }
        $start = isset($config['params']['gridheaddata']['start']) ? "'" . $config['params']['gridheaddata']['start'] . "'" : 'curdate()';
        $end = isset($config['params']['gridheaddata']['end']) ? "'" . $config['params']['gridheaddata']['end'] . "'" : 'curdate()';
        $clientname = isset($config['params']['gridheaddata']['clientname']) ? $config['params']['gridheaddata']['clientname'] : '';

        return $this->coreFunctions->opentable("select '" . $moduledesc . "' as moduledesc, $start as start, $end as end, '" . $clientname . "' as clientname, '' barcode, 0 as itemid, '' as itemname");
    }

    public function createtabbutton($config)
    {
        $systemtype = $this->companysetup->getsystemtype($config['params']);
        $tbuttons = ['saveallentry', 'unmarkall', 'assignbarcode', 'downloadexcel', 'uploadexcel'];

        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = 'Select ALL';
        $obj[0]['lookupclass'] = 'loaddata';
        $obj[0]['icon'] = 'check';


        $obj[0]['label'] = '';
        $obj[1]['label'] = '';
        return $obj;
    }

    public function loadData($config, $select = 'false')
    {
        $moduledesc = '';
        if (isset($config['params']['gridheaddata']['moduledesc']['label'])) {
            $moduledesc = $config['params']['gridheaddata']['moduledesc']['label'];
        } else {
            if (isset($config['params']['gridheaddata']['moduledesc'])) {
                $moduledesc = $config['params']['gridheaddata']['moduledesc'];
            }
        }

        $itemid = isset($config['params']['gridheaddata']['itemid']) ? $config['params']['gridheaddata']['itemid'] : '';
        $searchtxt = isset($config['params']['gridheaddata']['clientname']) ? $config['params']['gridheaddata']['clientname'] : '';
        $date1 = isset($config['params']['gridheaddata']['start']) ? date('Y-m-d', strtotime($config['params']['gridheaddata']['start'])) : '';
        $date2 = isset($config['params']['gridheaddata']['end']) ? date('Y-m-d', strtotime($config['params']['gridheaddata']['end'])) : '';

        if ($moduledesc == '') {
            return [];
        }

        $filter = '';
        if ($searchtxt != "") {
            $filter = " and (info.itemdesc like '%" . $searchtxt . "%' or info.specs like '%" . $searchtxt . "%' or h.docno like '%" . $searchtxt . "%')";
        }

        $doc = '';
        switch ($moduledesc) {
            case 'Purchase Requisition':
                $doc = 'PR';
                break;
            case 'Canvass Sheet':
                $doc = 'CD';
                break;
            case 'Purchase Order':
                $doc = 'PO';
                break;
            case 'Receiving Report':
                $doc = 'RR';
                break;
        }

        if ($moduledesc == '') {
            return [];
        }

        switch ($doc) {
            case 'PR':
                $query = "select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'U' as status
              from " . strtolower($doc) . "head as h left join " . strtolower($doc) . "stock as s on s.trno=h.trno left join stockinfotrans as info on info.trno=s.trno and info.line=s.line 
              left join item on item.itemid=s.itemid
              where h.doc='" . $doc . "' and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "'" . $filter . "
              union all
              select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'P' as status
              from h" . strtolower($doc) . "head as h left join h" . strtolower($doc) . "stock as s on s.trno=h.trno left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line 
              left join item on item.itemid=s.itemid
              where h.doc='" . $doc . "' and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "'" . $filter;
                break;
            case 'CD':
            case 'PO':
                $query = "select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'U' as status
              from " . strtolower($doc) . "head as h left join " . strtolower($doc) . "stock as s on s.trno=h.trno left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline 
              left join item on item.itemid=s.itemid
              where h.doc='" . $doc . "' and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "'" . $filter . "
              union all
              select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'P' as status
              from h" . strtolower($doc) . "head as h left join h" . strtolower($doc) . "stock as s on s.trno=h.trno left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline 
              left join item on item.itemid=s.itemid
              where h.doc='" . $doc . "' and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "'" . $filter . "";
                break;
            case 'RR':
                $query = "select s.trno, s.line, h.doc, h.docno, s.itemid, item.barcode, item.itemname, info.itemdesc, info.specs, item.uom, '" . $select . "' as otapproved, 'U' as status
              from lahead as h left join lastock as s on s.trno=h.trno 
              left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
              left join item on item.itemid=s.itemid
              where h.doc='" . $doc . "' and s.itemid=0 and date(h.dateid) between '" . $date1 . "' and '" . $date2 . "'" . $filter;
                break;
        }


        return $this->coreFunctions->opentable($query);
    }


    public  function saveallentry($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'saveallentry':
                foreach ($config['params']['data'] as $key => $value) {
                    $config['params']['data'][$key]['otapproved'] = 'true';
                }
                break;
            case 'assignbarcode':
                $itemid = $config['params']['gridheaddata']['itemid'];
                if ($itemid == 0) {
                    return ['status' => false, 'msg' => 'Please select valid barcode', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
                }
                foreach ($config['params']['data'] as $key => $value) {
                    if ($config['params']['data'][$key]['otapproved'] == 'true') {

                        $table = '';
                        if ($value['status'] == 'P') {
                            $table = 'h';
                        }
                        switch ($value['doc']) {
                            case 'PR':
                                $table .= 'prstock';
                                break;
                            case 'CD':
                                $table .= 'cdstock';
                                break;
                            case 'PO':
                                $table .= 'postock';
                                break;
                            case 'RR':
                                $table = 'lastock';
                                break;
                        }
                        if ($table != '') {
                            $this->coreFunctions->sbcupdate($table, ['itemid' => $itemid], ['trno' => $value['trno'], 'line' => $value['line']]);
                        }
                    }
                }
                break;
        }



        $gridheaddata = $this->gridheaddata($config);

        return ['status' => true, 'msg' => '', 'action' => 'load', 'data' => $config['params']['data'], 'gridheaddata' => $gridheaddata];
    }

    public function tableentrystatus($config)
    {

        switch ($config['params']['action2']) {
            case 'unmarkall':

                foreach ($config['params']['data'] as $key => $value) {
                    $config['params']['data'][$key]['otapproved'] = 'false';
                }

                $gridheaddata = $this->gridheaddata($config);

                return ['status' => true, 'msg' => '', 'action' => 'load', 'data' => $config['params']['data'], 'gridheaddata' => $gridheaddata];
                break;
        }
    }
}
