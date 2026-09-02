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

class locationentry
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Location List';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'tenantloc';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['clientid', 'locid', 'isinactive', 'dateid', 'encodeddate', 'encodedby'];
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
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $columns = ['action', 'code', 'area', 'odostart', 'odoend', 'isinactive'];
        $tab = [$this->gridname => ['gridcolumns' => $columns]];
        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        // action

        $obj[0][$this->gridname]['columns'][$odostart]['label'] = "Electric Meter#";
        $obj[0][$this->gridname]['columns'][$odoend]['label'] = "Water Meter#";
        $obj[0][$this->gridname]['columns'][$isinactive]['label'] = "Inactive";

        $obj[0][$this->gridname]['columns'][$code]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$area]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$odostart]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$odoend]['type'] = "label";

        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$area]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$odostart]['style'] = "width:150px;whiteSpace: normal;min-width:150px;text-align:left;";
        $obj[0][$this->gridname]['columns'][$odoend]['style'] = "width:150px;whiteSpace: normal;min-width:150px;text-align:left;";

        $obj[0][$this->gridname]['columns'][$isinactive]['style'] = "width:300px;whiteSpace: normal;min-width:300px;text-align:left;";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addloc', 'saveallentry', 'masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }


    public function add($config)
    {
        $data = [];
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $tableid = $config['params']['tableid'];
        
        $companyid = $config['params']['companyid'];
        $dateTables = [$this->table];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2],$lookups);
                }
                $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                $data2['editby'] = $config['params']['user'];
                unset($data2['clientid']);
                unset($data2['locid']);
                unset($data2['dateid']);
                unset($data2['encodeddate']);
                unset($data2['encodedby']);
                if ($data2['isinactive'] == '1') {
                    $this->coreFunctions->sbcupdate('loc', ['isserve' => 0], ['line' => $data[$key]['locid']]);
                } else {
                    $this->coreFunctions->sbcupdate('loc', ['isserve' => 1], ['line' => $data[$key]['locid']]);
                }
                $this->coreFunctions->sbcupdate($this->table, $data2, ['locid' => $data[$key]['locid'], 'clientid' => $tableid]);
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $tableid = $config['params']['tableid'];
        
        $companyid = $config['params']['companyid'];
        $dateTables = [$this->table];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value],$lookups);
        }

        if ($row['locid'] != 0 && $row['bgcolor'] == '') {
            $qry = "select locid,name from tenantloc 
                     left join loc on loc.line = tenantloc.locid
                     where tenantloc.clientid = " . $tableid . "  and tenantloc.locid = '" . $row['locid'] . "'";
            $opendata = $this->coreFunctions->opentable($qry);
            $resultdata =  json_decode(json_encode($opendata), true);
            $resultlocid = 0;
            if (!empty($resultdata[0]['locid'])) {
                $resultlocid = $resultdata[0]['locid'];
            }

            if ($row['locid'] == $resultlocid) {
                return ['status' => false, 'msg' => '( ' . $resultdata[0]['name'] . ' )', 'data' => [$resultdata], 'rowid' => [$row['locid']  . ' -- ' . $resultdata[0]['locid']]];
            } else {

                $line = $this->coreFunctions->sbcinsert($this->table, $data); //locid
                if ($line != 0) {
                    $nline = $this->coreFunctions->getfieldvalue($this->table, 'locid', 'clientid=? and locid=?', [$tableid, $row['locid']]);
                    if ($nline != 0) {
                        $this->coreFunctions->sbcupdate('loc', ['isserve' => 1], ['line' => $row['locid']]);
                        $returnrow = $this->loaddataperrecord($config, $nline);
                        $config['params']['doc'] = strtoupper("locationentry_tab");
                        $this->logger->sbcmasterlog(
                            $tableid,
                            $config,
                            ' CREATE - LOCID: ' . $row['locid'] . '' . ', Code: ' . $row['code'] . ', Inactive: ' . $row['isinactive']
                        );
                        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
                    }
                } else {
                    return ['status' => false, 'msg' => 'Saving failed.'];
                }
            }
        } else {

            if ($data['isinactive'] == '1') {
                $this->coreFunctions->sbcupdate('loc', ['isserve' => 0], ['line' => $data['locid']]);
            } else {
                $this->coreFunctions->sbcupdate('loc', ['isserve' => 1], ['line' => $data['locid']]);
            }
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $config['params']['doc'] = strtoupper("locationentry_tab");
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['locid' => $row['locid'], 'clientid' => $row['clientid']]) == 1) {
                $returnrow = $this->loaddataperrecord($config, $row['locid']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $tableid = $config['params']['tableid'];
        $row = $config['params']['row'];
        $data = $this->loaddataperrecord($config, $row['locid']);

        $qry = "delete from " . $this->table . " where locid=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['locid']]);

        $this->coreFunctions->sbcupdate('loc', ['isserve' => 0], ['line' => $row['locid']]);

        $params = $config;
        $params['params']['doc'] = strtoupper("locationentry_tab");
        $qry = "select tenantloc.locid,loc.name from tenantloc 
                     left join loc on loc.line = tenantloc.locid
                     where tenantloc.clientid = " . $tableid . " and tenantloc.locid = '" . $row['locid'] . "'";
        $opendata = $this->coreFunctions->opentable($qry);
        $resultdata =  json_decode(json_encode($opendata), true);
        $name = '';
        if (!empty($resultdata[0]['name'])) {
            $name = $resultdata[0]['name'];
        }
        $this->logger->sbcmasterlog($tableid,  $params,  ' DELETE - LOCID: ' . $row['locid'] . 'LOCNAME:' . $name);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    private function loaddataperrecord($config, $line)
    {
        $tableid = $config['params']['tableid'];
        $qry = "select loc.code,loc.area,loc.name, loc.emeter as odostart, loc.wmeter as odoend,tenantloc.clientid,tenantloc.locid,
        case when tenantloc.isinactive=0 then 'false' else 'true' end as isinactive, '' as bgcolor,
        tenantloc.dateid,tenantloc.encodeddate, tenantloc.encodedby
        from " . $this->table . " 
        left join loc on  loc.line = tenantloc.locid
        where tenantloc.clientid = " . $tableid . " and tenantloc.locid=?";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $qry = "select loc.code,loc.area,loc.name, loc.emeter as odostart, loc.wmeter as odoend,tenantloc.clientid,tenantloc.locid,
        case when tenantloc.isinactive=0 then 'false' else 'true' end as isinactive, '' as bgcolor,
        tenantloc.dateid,tenantloc.dateid,tenantloc.encodeddate, tenantloc.encodedby
        from " . $this->table . " 
        left join loc on  loc.line = tenantloc.locid
        where tenantloc.clientid = " . $tableid . "";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {

            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            case 'addloc':
                return $this->addloc($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }


    public function lookuplogs($config)
    {
        $doc = strtoupper("locationentry_tab");
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

    public function addloc($config)
    {
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'locid',
            'title' => 'List of Locations',
            'style' => 'width:800px;max-width:800px;'
        );

        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'addtogrid'
        );

        // lookup columns
        $cols = [
            ['name' => 'code', 'label' => 'Location Code', 'align' => 'left', 'field' => 'code', 'sortable' => true],
            ['name' => 'area', 'label' => 'Area(SQM)', 'align' => 'left', 'field' => 'area', 'sortable' => true],
            ['name' => 'emeter', 'label' => 'Electric Meter#', 'align' => 'left', 'field' => 'emeter', 'sortable' => true],
            ['name' => 'wmeter', 'label' => 'Water Meter#', 'align' => 'left', 'field' => 'wmeter', 'sortable' => true]
        ];
        $qry = "select line as locid,code, area,emeter, wmeter from loc where isserve =0 order by code";
        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function lookupcallback($config)
    {
        $id = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $data = [];
        $returndata = [];
        $errors = [];
        $msg = 'Successfully added.';
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        foreach ($row  as $key2 => $value) {
            $config['params']['row']['clientid'] = $config['params']['tableid'];
            $config['params']['row']['locid'] = $row[$key2]['locid'];
            $config['params']['row']['code'] = $row[$key2]['code'];
            $config['params']['row']['isinactive'] = 'false';
            $config['params']['row']['dateid'] =  $this->othersClass->getCurrentDate();
            $config['params']['row']['encodeddate'] = $current_timestamp;
            $config['params']['row']['encodedby'] = $config['params']['user'];
            $config['params']['row']['bgcolor'] = '';
            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            } else {
                $errors[] = $return['msg'];
            }
        }

        $status = count($returndata) > 0;
        $msg = $status ? 'Successfully added.'  : 'No location were saved. ';

        if (!empty($errors)) {
            $msg .= implode(', ', $errors) . ' location  already exist.';
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
    } // end function


    // -> Print Function
    public function reportsetup($config)
    {
        return [];
    }


    public function createreportfilter()
    {
        return [];
    }

    public function reportparamsdata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        return [];
    }
} //end class
