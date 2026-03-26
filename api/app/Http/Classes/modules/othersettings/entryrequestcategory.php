<?php

namespace App\Http\Classes\modules\othersettings;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entryrequestcategory
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Request Category';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'reqcategory';
    private $othersClass;
    public $style = 'width:100%;';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['category',  'isoracle', 'iscat', 'isnsi', 'iscldetails', 'isss', 'isreassigned'];
    public $showclosebtn = false;
    private $reporter;
    private $logger;


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
            'load' => 3742,
            'save' => 3742
        );
        return $attrib;
    }

    public function createTab($config)
    {
        $companyid =  $config['params']['companyid'];
        $column = ['action', 'category', 'isoracle', 'isnsi', 'iscldetails', 'isss'];

        if ($companyid == 58 || $companyid == 25) { //cdo
            $column = ['action', 'category'];
        }

        foreach ($column as $key => $value) {
            $$value = $key;
        }

        $tab = [
            $this->gridname => [
                'gridcolumns' => $column
            ]
        ];

        $stockbuttons = ['save', 'delete'];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        // action
        $obj[0][$this->gridname]['columns'][0]['style'] = "width:10%;whiteSpace: normal;min-width:10%;";
        $obj[0][$this->gridname]['columns'][1]['style'] = "width:70%;whiteSpace: normal;min-width:70%;";
        $obj[0][$this->gridname]['columns'][2]['style'] = "width:10%;whiteSpace: normal;min-width:10%;";
        $obj[0][$this->gridname]['columns'][3]['style'] = "width:10%;whiteSpace: normal;min-width:10%;";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'print', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    }


    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['category'] = '';
        $data['isoracle'] = 'false';
        $data['isnsi'] = 'false';
        $data['iscldetails'] = 'false';
        $data['iscat'] = 1;
        $data['isss'] = 'false';
        $data['isreassigned'] = 0;
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    }

    private function selectqry()
    {
        $qry = "
        head.line, head.category, head.iscat,
        case when isoracle=0 then 'false' else 'true' end as isoracle,
        case when isnsi=0 then 'false' else 'true' end as isnsi,
        case when iscldetails=0 then 'false' else 'true' end as iscldetails,
        case when isss=0 then 'false' else 'true' end as isss
        ";
        return $qry;
    }

    public function saveallentry($config)
    {

        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];
        foreach ($data as $key => $value) {
            $data2 = [];

            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }
                $data2['isoracle'] = $this->othersClass->sanitizekeyfield('isoracle', $data[$key]['isoracle']);

                if ($companyid == 58) { //cdo 
                    $data2['isreassigned'] = 1;
                }

                if ($data[$key]['line'] == 0) {
                    $project_id = $this->coreFunctions->insertGetId($this->table, $data2);
                    $this->logger->sbcmasterlog($project_id, $config, ' CREATE - ' . $data[$key]['category']);
                } else {
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
    } // end function

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];
        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        if ($companyid == 58) { //cdo
            $data['isreassigned'] = 1;
        }

        if ($row['line'] == 0) {
            $line = $this->coreFunctions->insertGetId($this->table, $data);
            if ($line != 0) {
                $returnrow = $this->loaddataperrecord($line);
                $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['category']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line']);
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } //end function

    public function delete($config)
    {
        $row = $config['params']['row'];

        $used = $this->coreFunctions->opentable("select docno from prhead where ourref=?
                union all
                select docno from hprhead where ourref=? limit 1", [$row['line'], $row['line']]);

        $msg = 'Successfully deleted.';
        if ($used) {
            $msg = 'Unable to delete ' . $row['category'] . ' already used in ' . $used[0]->docno;
            return ['status' => false, 'msg' => $msg];
        } else {
            $qry = "delete from " . $this->table . " where line=?";
            $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
            $this->logger->sbcdelmaster_log($row['line'], $config, 'REMOVE - ' . $row['category']);
            return ['status' => true, 'msg' => $msg];
        }
    }

    private function loaddataperrecord($line)
    {
        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " 
            from " . $this->table . " as head
            where line=? and iscat = 1";
        $data = $this->coreFunctions->opentable($qry, [$line]);
        return $data;
    }

    public function loaddata($config)
    {
        $filtersearch = "";
        $searcfield = ['head.category'];
        $limit = "1000";

        if (isset($config['params']['filter'])) {
            $search = $config['params']['filter'];
            foreach ($searcfield as $key => $sfield) {
                if ($filtersearch == "") {
                    $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
                } else {
                    $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
                } //end if
            }
            $filtersearch .= ")";
        }

        $select = $this->selectqry();
        $select = $select . ",'' as bgcolor ";
        $qry = "select " . $select . " 
        from " . $this->table . " as head
        where iscat = 1  " . $filtersearch . "
        order by head.line limit " . $limit;
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;

            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    }

    public function lookuplogs($config)
    {
        $doc = $config['params']['doc'];
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

        $qry = "select trno, doc, task, log.user, dateid, 
                if(pic='','blank_user.png',pic) as pic
                from " . $this->tablelogs . " as log
                left join useraccess as u on u.username=log.user
                where log.doc = '" . $doc . "'
                union all
                select trno, doc, task, log.user, dateid, 
                if(pic='','blank_user.png',pic) as pic
                from  " . $this->tablelogs_del . " as log
                left join useraccess as u on u.username=log.user
                where log.doc = '" . $doc . "'";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    }
} //end class
