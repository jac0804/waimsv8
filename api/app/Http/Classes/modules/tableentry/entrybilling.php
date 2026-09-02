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
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class entrybilling
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'Billing Master';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'billingmaster';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['assetid', 'revenueid', 'billtype', 'sched'];
    public $showclosebtn = false;
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
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
    } // end function

    public function getAttrib()
    {
        $attrib = array(
            'load' => 5947,
            'save' => 5947,
        );
        return $attrib;
    } // end function

    public function createTab($config)
    {
        $action = 0;
        $code = 1;
        $description = 2;

        $columns = ['action', 'code', 'billtype', 'scheddate', 'assetaccount', 'revenueaccount'];

        $tab = [
            $this->gridname => [
                'gridcolumns' => $columns
            ]
        ];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['save', 'delete'];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$action]['style'] = "width:10%;whiteSpace: normal;min-width:100%;";
        $obj[0][$this->gridname]['columns'][$code]['style'] = "width:25px;whiteSpace: normal;min-width:25px;";
        $obj[0][$this->gridname]['columns'][$code]['type'] = "hidden";
        $obj[0][$this->gridname]['columns'][$code]['label'] = " ";

        $obj[0][$this->gridname]['columns'][$assetaccount]['lookupclass'] = 'assetlookup';
        $obj[0][$this->gridname]['columns'][$assetaccount]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$assetaccount]['style'] = "width:25%;whiteSpace: normal;min-width:100%;";

        $obj[0][$this->gridname]['columns'][$revenueaccount]['lookupclass'] = 'revenuelookup';
        $obj[0][$this->gridname]['columns'][$revenueaccount]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$revenueaccount]['style'] = "width:25%;whiteSpace: normal;min-width:100%;";

        $obj[0][$this->gridname]['columns'][$scheddate]['label'] = 'Schedule';
        $obj[0][$this->gridname]['columns'][$scheddate]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$scheddate]['lookupclass'] = 'lookupsched';
        $obj[0][$this->gridname]['columns'][$scheddate]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$scheddate]['style'] = "width:20%;whiteSpace: normal;min-width:100%;";

        $obj[0][$this->gridname]['columns'][$billtype]['type'] = 'lookup';
        $obj[0][$this->gridname]['columns'][$billtype]['lookupclass'] = 'lookupbilltype';
        $obj[0][$this->gridname]['columns'][$billtype]['action'] = 'lookupsetup';
        $obj[0][$this->gridname]['columns'][$billtype]['style'] = "width:20%;whiteSpace: normal;min-width:100%;";

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    } // end function

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        return $obj;
    } // end function

    public function add($config)
    {
        $data = [];
        $data['line'] = 0;
        $data['billtype'] = '';
        $data['sched'] = '';
        $data['scheddate'] = '';
        $data['assetid'] = '';
        $data['assetaccount'] = '';
        $data['revenueid'] = '';
        $data['revenueaccount'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    } // end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];

        $dateTables = ['billingmaster'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
                }

                if ($data[$key]['line'] == 0) {
                    $exists = $this->coreFunctions->datareader(
                        "select count(*) as value from billingmaster where billtype = ? and sched = ?",
                        [$data2['billtype'], $data2['sched']]
                    );
                    if ($exists > 0) {
                        continue; // duplicate combination, skip
                    }

                    $data2['encodedby'] = $config['params']['user'];
                    $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();

                    $line = $this->coreFunctions->insertGetId($this->table, $data2);

                    $this->logger->sbcmasterlog(
                        $line,
                        $config,
                        'INSERT BILL' . ' - TYPE: ' . $data[$key]['billtype'] . ' - SCHED: ' . $data[$key]['scheddate'] . ' - ASSET: ' . $data[$key]['assetid'] . ' - REVENUE: ' . $data[$key]['revenueid']
                    );
                } else {
                    $exists = $this->coreFunctions->datareader(
                        "select count(*) as value from billingmaster where billtype = ? and sched = ? and line <> ?",
                        [$data2['billtype'], $data2['sched'], $data[$key]['line']]
                    );
                    if ($exists > 0) {
                        continue; // duplicate combination, skip
                    }

                    $data2['editby'] = $config['params']['user'];
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);

                    $this->logger->sbcmasterlog(
                        $data[$key]['line'],
                        $config,
                        'UPDATE BILL' . ' - TYPE: ' . $data[$key]['billtype'] . ' - SCHED: ' . $data[$key]['scheddate'] . ' - ASSET: ' . $data[$key]['assetid'] . ' - REVENUE: ' . $data[$key]['revenueid']
                    );
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata, 'row' => $returndata];
    }

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];

        $dateTables = ['billingmaster'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
        }

        if ($row['line'] == 0) {
            $exists = $this->coreFunctions->datareader(
                "select count(*) as value from billingmaster where billtype = ? and sched = ?",
                [$data['billtype'], $data['sched']]
            );
            if ($exists > 0) {
                return ['status' => false, 'msg' => 'This Bill Type and Schedule combination already exists.'];
            }

            $qry = "select line as value from " . $this->table . " order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, []);
            if (!$line) {
                $line = 0;
            }
            $line = $line + 1;
            $data["line"] = $line;

            $data['encodedby'] = $config['params']['user'];
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();

            if ($this->coreFunctions->sbcinsert($this->table, $data)) {
                $returnrow = $this->loaddataperrecord($line, $config);

                $this->logger->sbcmasterlog(
                    $line,
                    $config,
                    'CREATE BILL' . ' - TYPE: ' . $data['billtype'] . ' - SCHED: ' . $data['sched'] . ' - ASSET: ' . $data['assetid'] . ' - REVENUE: ' . $data['revenueid'] . ' - LINE' . $line
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $exists = $this->coreFunctions->datareader(
                "select count(*) as value from billingmaster where billtype = ? and sched = ? and line <> ?",
                [$data['billtype'], $data['sched'], $row['line']]
            );
            if ($exists > 0) {
                return ['status' => false, 'msg' => 'This Bill Type and Schedule combination already exists.'];
            }

            $data['editby'] = $config['params']['user'];
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
                $returnrow = $this->loaddataperrecord($row['line'], $config);

                $this->logger->sbcmasterlog(
                    $row['line'],
                    $config,
                    'UPDATE BILL' . ' - TYPE: ' . $data['billtype'] . ' - SCHED: ' . $data['sched'] . ' - ASSET: ' . $data['assetid'] . ' - REVENUE: ' . $data['revenueid'] . ' - LINE' . $row['line'],
                    1
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    }

    public function delete($config)
    {
        $row = $config['params']['row'];

        $inuse = $this->coreFunctions->datareader(
            "select count(*) as value from clbilling where bline = ?",
            [$row['line']]
        );

        if ($inuse > 0) {
            return ['status' => false, 'msg' => 'This billing type is already in use and cannot be deleted.'];
        }

        $this->logger->sbcmasterlog(
            $row['line'],
            $config,
            'DELETE BILL' . ' - TYPE: ' . $row['billtype'] . ' - SCHED: ' . $row['scheddate'] . ' - ASSET: ' . $row['assetid'] . ' - REVENUE: ' . $row['revenueid'] . ' - LINE' . $row['line']
        );

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    } // end function

    private function selectqry()
    {
        $qry = "bm.line, bm.billtype, bm.sched,
        case when bm.sched = 0 then 'MONTHLY'
        when bm.sched = 1 then 'ANNUAL'
        else '' end as scheddate,
        bm.assetid, ca.acno as assetaccount,
        bm.revenueid, cr.acno as revenueaccount";
        return $qry;
    } // end function

    public function loaddata($config)
    {
        $select = $this->selectqry();
        $select = $select . ", '' as bgcolor ";

        $qry = "select " . $select . " from " . $this->table . " as bm
        left join coa as ca on ca.acnoid = bm.assetid
        left join coa as cr on cr.acnoid = bm.revenueid
        order by bm.line ";
        $data = $this->coreFunctions->opentable($qry);

        return $data;
    } // end function

    public function loaddataperrecord($line, $config)
    {
        $select = $this->selectqry();
        $select = $select . ", '' as bgcolor ";

        $qry = "select " . $select . " from " . $this->table . " as bm
        left join coa as ca on ca.acnoid = bm.assetid
        left join coa as cr on cr.acnoid = bm.revenueid
        where bm.line = ?";
        $data = $this->coreFunctions->opentable($qry, [$line]);

        return $data;
    } // end function

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            case 'lookupsched':
                return $this->lookupsched($config);
                break;
            case 'lookupbilltype':
                return $this->lookupbilltype($config);
                break;
            case 'assetlookup':
                return $this->lookupasset($config);
                break;
            case 'revenuelookup':
                return $this->lookuprevenue($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    } // end function

    public function lookuplogs($config)
    {
        $doc = $config['params']['doc'];
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Billing Master Logs',
            'style' => 'width:1000px;max-width:1000px;'
        );

        // lookup columns
        $cols = array(
            array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

        );

        // $trno = $config['params']['tableid'];

        $qry = "
        select trno, doc, task, log.user, dateid, 
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
    } // end function

    public function lookupsched($config)
    {
        $plotting = array('scheddate' => 'scheddate', 'sched' => 'sched');
        $plottype = 'plotgrid';

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'Schedule Lookup',
            'style' => 'width:400px;max-width:400px;'
        );

        $plotsetup = array(
            'plotting' => $plotting,
            'action' => '',
            'plottype' => $plottype
        );

        // lookup columns
        $cols = array(
            array('name' => 'scheddate', 'label' => 'Description', 'align' => 'left', 'field' => 'scheddate', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $qry = "select 0 as sched ,'MONTHLY' as scheddate
                union all
                select 1 as sched, 'ANNUAL' as scheddate";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } // end function

    public function lookupbilltype($config)
    {
        $plotting = array('billtype' => 'billtype');
        $plottype = 'plotgrid';

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'Bill Type Lookup',
            'style' => 'width:400px;max-width:400px;'
        );

        $plotsetup = array(
            'plotting' => $plotting,
            'action' => '',
            'plottype' => $plottype
        );

        // lookup columns
        $cols = array(
            array('name' => 'billtype', 'label' => 'Bill Type', 'align' => 'left', 'field' => 'billtype', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $qry = "select 'RETAINERS' as billtype
                union all
                select 'HOSTING' as billtype";
        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } // end function

    public function lookupasset($config)
    {
        $plotting = array('assetid' => 'acnoid', 'assetaccount' => 'acno');
        $plottype = 'plotgrid';

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'Asset Account Lookup',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plotting' => $plotting,
            'action' => '',
            'plottype' => $plottype
        );

        // lookup columns
        $cols = [
            ['name' => 'acno', 'label' => 'Account No.', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'alias', 'label' => 'Alias', 'align' => 'left', 'field' => 'alias', 'sortable' => true, 'style' => 'font-size:16px;']
        ];

        $qry = "select acnoid,acno,acnoname,alias from coa where cat = 'A'";

        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } // end function

    public function lookuprevenue($config)
    {
        $plotting = array('revenueid' => 'acnoid', 'revenueaccount' => 'acno');
        $plottype = 'plotgrid';

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'Revenue Account Lookup',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plotting' => $plotting,
            'action' => '',
            'plottype' => $plottype
        );

        // lookup columns
        $cols = [
            ['name' => 'acno', 'label' => 'Account No.', 'align' => 'left', 'field' => 'acno', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'acnoname', 'label' => 'Account Name', 'align' => 'left', 'field' => 'acnoname', 'sortable' => true, 'style' => 'font-size:16px;'],
            ['name' => 'alias', 'label' => 'Alias', 'align' => 'left', 'field' => 'alias', 'sortable' => true, 'style' => 'font-size:16px;']
        ];

        $qry = "select acnoid,acno,acnoname,alias from coa where cat = 'R'";

        $data = $this->coreFunctions->opentable($qry);
        $index = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
    } // end function
} //end class