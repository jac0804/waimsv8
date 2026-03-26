<?php

namespace App\Http\Classes\modules\tableentry;

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
use App\Http\Classes\SBCPDF;

class itementry
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Entry Item';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'supplieritem';
    private $othersClass;
    public $style = 'width:100%';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['itemid'];
    public $showclosebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->reporter = new SBCPDF;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5117
        );
        return $attrib;
    }
    //aa
    public function createTab($config)
    {

        $columns = ['action', 'barcode', 'itemname'];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        $stockbuttons = ['delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:5px;whiteSpace: normal;min-width:5px;";
        $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$barcode]['label'] = "Barcode";
        $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$barcode]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item Name";
        $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = true;
        return $obj;
    }


    public function createtabbutton($config)
    {

        $tbuttons = ['addsubitem', 'saveallentry', 'masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }



    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['itemid'] = 0;
        $data['barcode'] = '';
        $data['itemname'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        $data['clientid'] = $config['params']['tableid'];
        return $data;
    }



    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $qry = "select sp.line, sp.itemid, sp.clientid,
        item.itemname,item.barcode,sp.createdate,sp.createby,'' as bgcolor
        from " . $this->table . " as sp
        left join item on item.itemid = sp.itemid
        where sp.clientid = " . $tableid . " order by line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }




    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'addsubitem':
                return $this->lookupitem($config);
                break;
            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }


    public function lookupcallback($config)
    {
        switch ($config['params']['lookupclass2']) {
            case 'addtogrid':
                $row = $config['params']['row'];
                $data = [];
                $data['line'] = 0;
                $data['itemid'] = $row['itemid'];
                $data['barcode'] = $row['barcode'];
                $data['itemname'] = $row['itemname'];
                $data['bgcolor'] = 'bg-blue-2';
                return ['status' => true, 'msg' => 'Item was successfully added.', 'data' => $data];
                break;
        }
    } // end function

    public function lookupitem($config)
    {
        $lookupsetup = array(
            'type' => 'single',
            'rowkey' => 'itemid',
            'title' => 'List of Items',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'callback',
            'action' => 'addtogrid'

        );

        $cols = array(
            array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
        );

        $qry = "select itemid, barcode, itemname from item";

        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup]; //, 'index' => $rowindex
    }

    public function saveallentry($config)
    {

        $data = $config['params']['data'];
        $tableid = $config['params']['tableid'];
        foreach ($data as $key => $value) {
            $data2 = [];
            if (isset($value['itemname'])) {
                $itemname = $value['itemname'];
            }
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $data2['clientid'] = $tableid;
                $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['createby'] = $config['params']['user'];

                if ($data[$key]['line'] == 0 && $data[$key]['itemid'] != 0) {
                    $qry = "select  sup.itemid,i.itemname from supplieritem as sup
                            left join item as i on i.itemid=sup.itemid where sup.clientid = '" . $tableid . "' limit 1";
                    $opendata = $this->coreFunctions->opentable($qry);
                    $resultdata =  json_decode(json_encode($opendata), true);
                    if (!empty($resultdata[0]['itemid'])) {
                        if (trim($resultdata[0]['itemid']) == trim($data[$key]['itemid'])) {
                            return ['status' => false, 'msg' => $resultdata[0]['itemname'] . '  already exist', 'data' => [$resultdata]];
                        }
                    }
                }

                if ($data[$key]['line'] == 0) {
                    $status = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($tableid, $config, ' CREATE - LINE: ' . $status . '' . ', Item Name: ' . $itemname);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function



    public function delete($config)
    {
        $tableid = $config['params']['tableid'];
        $row = $config['params']['row'];
        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $this->logger->sbcmasterlog($tableid,  $config, ' DELETE - LINE: ' . $row['line'] . '' . ', Itemname: ' . $row['itemname']);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }



    public function lookuplogs($config)
    {
        $doc = $config['params']['doc'];
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'List of Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );

        $trno = $config['params']['tableid'];
        $qry = "
        select trno, doc, task, log.user, dateid,
        if(pic='','blank_user.png',pic) as pic
        from " . $this->tablelogs . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
        union all
        select trno, doc, task, log.user, dateid,
        if(pic='','blank_user.png',pic) as pic
        from  " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

        $qry = $qry . " order by dateid desc";

        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, $qry];
    }
} //end class
