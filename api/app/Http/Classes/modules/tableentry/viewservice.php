<?php

namespace App\Http\Classes\modules\tableentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\modules\inventory\va;
use Exception;

class viewservice
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;
    private $logger;

    public $modulename = 'View Services';
    public $gridname = 'inventory';
    private $fields = ['counterline', 'serviceline'];
    private $table = 'counterservice';

    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';

    public $style = 'width:100%;max-width:80%;';
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
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createHeadField($config)
    {
        $fields = [];
        $col1 = $this->fieldClass->create($fields);

        return array('col1' => $col1);
    }

    public function paramsdata($config)
    {
        return $this->getheaddata($config);
    }

    public function getheaddata($config)
    {
        return [];
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $column = ['action', 'code', 'color'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $column]];

        $stockbuttons = ['delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$code]['label'] = 'Service';
        $obj[0][$this->gridname]['columns'][$code]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$color]['type'] = 'hidden';
        $obj[0][$this->gridname]['columns'][$color]['label'] = '';
        $obj[0][$this->gridname]['columns'][$color]['class'] = 'cscolor sbccsreadonly';

        // $obj[0][$this->gridname]['columns'][$code]['class'] = 'cscolor sbccsreadonly';
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['lookupclass'] = 'lookupservice';
        $obj[0]['action'] = 'lookupsetup';
        $obj[0]['label'] = 'Add Service';
        return $obj;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'lookupservice':
                return $this->lookupservice($config);
                break;
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }

    public function lookupcallback($config)
    {
        $id = $config['params']['tableid'];
        $row  = $config['params']['row'];
        $data = [];
        $returndata = [];
        $config['params']['row'] = [
            'counterline' => $config['params']['sourcerow']['line'],
            'serviceline' => $config['params']['row']['serviceline'],
            'code' => $config['params']['row']['code'],
            'bgcolor' => 'bg-blue-2'
        ];
        $return = $this->save($config);
        if ($return['status']) {
            $returndata = $return['row'][0];
            return ['status' => true, 'msg' => 'Successfully added.', 'data' => $returndata, 'reloadtableentry' => $return['sourcerow']];
        } else {
            return ['status' => false, 'msg' => $return['msg']];
        }
    } // end function

    public function lookupservice($config)
    {
        $source = $config['params']['sourcerow'];

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Services',
            'style' => 'width:80%;max-width:80%;height:700px'
        );
        $plotsetup = [
            'action' => 'addtogrid',
            'plottype' => 'tableentry'
        ];
        $cols = [
            ['name' => 'code', 'label' => 'Services', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;']
        ];


        $service_lines = [];
        $filter = "";

        $query = "select serviceline from counterservice where counterline = " . $source['line'];

        $service = $this->coreFunctions->opentable($query);

        foreach ($service as $d) {
            array_push($service_lines, $d->serviceline);
        }
        $line = !empty($service_lines) ? implode(",", $service_lines) : '0';
        if ($line != '0') {
            $filter = " and req.line not in (" . $line . ")";
        }
        $data = $this->coreFunctions->opentable("select req.line as serviceline,req.code,'' as bgcolor from reqcategory as req
         where req.isservice = 1 $filter");
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    }

    public function add($config)
    {
        $data = [];
        $data['counterline'] = 0;
        $data['serviceline'] = 0;
        $data['code'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    public function delete($config)
    {
        $row = $config['params']['row'];
        $this->coreFunctions->execqry("delete from " . $this->table . " where counterline=" . $row['counterline'] . " and serviceline=" . $row['serviceline'], 'delete');
        $sourcerow = $this->loadsourcerow($config, $row['counterline']);
        $this->logger->sbcmasterlog($row['counterline'], $config, ' DELETE - ' . $row['code'], 0, 0, $row['serviceline']);
        return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadtableentry' => $sourcerow];
    }

    public function loaddataperrecord($config, $counterline, $serviceline)
    {
        $data = $this->coreFunctions->opentable("select cs.counterline,req.line as serviceline,req.code,'' as bgcolor from counterservice as cs 
         left join reqcategory as req on req.line = cs.serviceline
         where cs.counterline = $counterline and cs.serviceline = $serviceline and req.isservice = 1
         ");
        return $data;
    }

    public function loaddata($config)
    {
        $line = isset($config['params']['row']['line']) ? $config['params']['row']['line'] : $config['params']['sourcerow']['line'];

        $query = "select cs.counterline,req.line as serviceline,req.code from counterservice as cs 
         left join reqcategory as req on req.line = cs.serviceline 
         where cs.counterline = $line and req.isservice = 1";
        $data = $this->coreFunctions->opentable($query);
        return $data;
    }

    public function loadsourcerow($config, $line)
    {
        $data = $this->coreFunctions->opentable("select req.line ,req.code,'' as bgcolor from reqcategory as req
        where iscounter=1");
        return $data;
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $srow = $config['params']['sourcerow'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        $query =  "select serviceline as value from counterservice where counterline = " . $srow['line'] . " and serviceline = " . $row['serviceline'] . "";
        $serviceline = $this->coreFunctions->datareader($query);
        if (!empty($serviceline)) {
            return ['status' => false, 'msg' => 'Service already exist.'];
        }

        $data['encodedate'] = $this->othersClass->getCurrentTimeStamp();
        $data['encodedby'] = $config['params']['user'];
        if ($this->coreFunctions->sbcinsert($this->table, $data) == 1) {
            $returnrow = $this->loaddataperrecord($config, $data['counterline'], $data['serviceline']);
            $sourcerow = $this->loadsourcerow($config, $data['counterline']);
            $this->logger->sbcmasterlog($row['counterline'], $config, ' ADD SERVICE - ' . $row['code'], 0, 0, $data['serviceline']);
            return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow, 'sourcerow' => $sourcerow];
        } else {
            return ['status' => false, 'msg' => 'Saving failed.'];
        }
    }
    public function lookuplogs($config)
    {
        $doc = $config['params']['doc'];
        $line = $config['params']['sourcerow']['line'];
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $trno = $config['params']['tableid'];


        $service_lines = [];
        $filter = "";

        $query = "select trno2 from " . $this->tablelogs . " where doc = '" . $doc . "'  and trno2 <> 0 and trno = " . $line;

        $service = $this->coreFunctions->opentable($query);

        foreach ($service as $d) {
            array_push($service_lines, $d->trno2);
        }
        $sline = !empty($service_lines) ? implode(",", $service_lines) : '0';
        if ($sline != '0') {
            $filter = " and log.trno2 in (" . $sline . ") and log.trno = $line";
        }

        $qry = "
          select trno, doc, task, log.user, dateid, 
          if(pic='','blank_user.png',pic) as pic
          from " . $this->tablelogs . " as log
          left join useraccess as u on u.username=log.user
          where log.doc = '" . $doc . "' $filter 
          union all
          select trno, doc, task, log.user, dateid, 
          if(pic='','blank_user.png',pic) as pic
          from  " . $this->tablelogs_del . " as log
          left join useraccess as u on u.username=log.user
          where log.doc = '" . $doc . "' " . " $filter ";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
}
