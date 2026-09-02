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
use App\Http\Classes\moduleClass;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\modules\customform;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class billingsetup
{
    private $fieldClass;
    private $tabClass;
    private $logger;
    public $modulename = 'Billing Setup';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = 'clbilling';
    private $othersClass;
    public $style = 'width:100%;';
    private $fields = ['clientid', 'bline', 'amt', 'isvat', 'isinactive', 'startdate', 'enddate'];
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
            'load' => 0
        );
        return $attrib;
    } // end function

    public function createTab($config)
    {

        $columns = ['action', 'code', 'billtype', 'scheddate', 'assetaccount', 'revenueaccount', 'amt', 'startdate', 'enddate', 'isvat', 'isinactive'];

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

        $obj[0][$this->gridname]['columns'][$assetaccount]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$assetaccount]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$assetaccount]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$revenueaccount]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$revenueaccount]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$revenueaccount]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$scheddate]['label'] = 'Schedule';
        $obj[0][$this->gridname]['columns'][$scheddate]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$scheddate]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'][$startdate]['label'] = 'Start Date';

        $obj[0][$this->gridname]['columns'][$billtype]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$billtype]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$startdate]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$enddate]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        return $obj;
    } // end function

    public function createtabbutton($config)
    {
        $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
        $obj = $this->tabClass->createtabbutton($tbuttons);

        $obj[0]['icon'] = 'table_chart';
        $obj[0]['label'] = 'Add Billing';
        $obj[0]['lookupclass'] = 'lookupclbilling';
        $obj[0]['action'] = 'lookupsetup';

        return $obj;
    } // end function

    public function add($config)
    {
        $data = [];
        $data['clientid'] = $config['params']['tableid'];
        $data['bline'] = 0;
        $data['billtype'] = '';
        $data['sched'] = '';
        $data['scheddate'] = '';
        $data['assetid'] = '';
        $data['assetaccount'] = '';
        $data['revenueid'] = '';
        $data['revenueaccount'] = '';
        $data['amt'] = '';
        $data['startdate'] = '';
        $data['enddate'] = '';
        $data['isvat'] = 'false';
        $data['isinactive'] = 'false';
        $data['bgcolor'] = 'bg-blue-2';
        return $data;
    } // end function

    public function save($config)
    {
        $data = [];
        $row = $config['params']['row'];
        $companyid = $config['params']['companyid'];
        $startdate = $this->othersClass->getCurrentTimeStamp();

        $dateTables = ['clbilling'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfieldFast($value, $row[$value], $lookups);
        }

        $sched = $this->coreFunctions->datareader("select sched as value from billingmaster where line = ?", [$data['bline']]);

        if ($sched == 1 && trim($data['startdate']) != '') {
            $data['enddate'] = date('Y-m-d', strtotime($data['startdate'] . ' +1 year -1 day'));
        } else {
            $data['enddate'] = '9990-12-31';
        }

        $exists = $this->coreFunctions->datareader(
            "select count(*) as value from clbilling where clientid = ? and bline = ?",
            [$data['clientid'], $data['bline']]
        );

        if ($exists == 0) {
            $data['encodedby'] = $config['params']['user'];
            $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();

            if ($this->coreFunctions->sbcinsert($this->table, $data)) {
                $returnrow = $this->loaddataperrecord($data['bline'], $config);

                $this->logger->sbcmasterlog(
                    $data['clientid'],
                    $config,
                    'CREATE BILLING' . ' - BLINE: ' . $data['bline'] . ' - AMT: ' . $data['amt'] . ' - START DATE: ' . $data['startdate'] . ' - END DATE: ' . $data['enddate']
                    . ' - ISVAT: ' . $data['isvat'] . ' - ISINACTIVE: ' . $data['isinactive'],
                    0,
                    1
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        } else {
            $data['editby'] = $config['params']['user'];
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();

            if ($this->coreFunctions->sbcupdate($this->table, $data, ['clientid' => $data['clientid'], 'bline' => $data['bline']]) == 1) {
                $returnrow = $this->loaddataperrecord($data['bline'], $config);

                $this->logger->sbcmasterlog(
                    $data['clientid'],
                    $config,
                    'UPDATE BILLING' . ' - BLINE: ' . $data['bline'] . ' - AMT: ' . $data['amt'] . ' - START DATE: ' . $data['startdate'] .
                    ' - END DATE: ' . $data['enddate'] . ' - ISVAT: ' . $data['isvat'] . ' - ISINACTIVE: ' . $data['isinactive'],
                    1,
                    1
                );
                return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
            } else {
                return ['status' => false, 'msg' => 'Saving failed.'];
            }
        }
    } // end function

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $companyid = $config['params']['companyid'];

        $dateTables = ['clbilling'];
        $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

        foreach ($data as $key => $value) {
            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfieldFast($value2, $data[$key][$value2], $lookups);
                }

                $sched = $this->coreFunctions->datareader("select sched as value from billingmaster where line = ?", [$data2['bline']]);

                if (trim($data2['enddate']) == '') {
                    if ($sched == 1 && trim($data2['startdate']) != '') {
                        $data2['enddate'] = date('Y-m-d', strtotime($data2['startdate'] . ' +1 year -1 day'));
                    } else {
                        $data2['enddate'] = '9990-12-31';
                    }
                }

                $exists = $this->coreFunctions->datareader(
                    "select count(*) as value from clbilling where clientid = ? and bline = ?",
                    [$data2['clientid'], $data2['bline']]
                );

                if ($exists == 0) {
                    $data2['encodedby'] = $config['params']['user'];
                    $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();

                    $this->coreFunctions->sbcinsert($this->table, $data2);

                    $this->logger->sbcmasterlog(
                        $data2['clientid'],
                        $config,
                        'INSERT BILLING' . ' - BLINE: ' . $data2['bline'] . ' - AMT: ' . $data2['amt'] . ' - START DATE: ' . $data2['startdate'] . ' - END DATE: ' . $data2['enddate']
                        . ' - ISVAT: ' . $data2['isvat'] . ' - ISINACTIVE: ' . $data2['isinactive'],
                        0,
                        1
                    );
                } else {
                    $data2['editby'] = $config['params']['user'];
                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $this->coreFunctions->sbcupdate($this->table, $data2, ['clientid' => $data2['clientid'], 'bline' => $data2['bline']]);

                    $this->logger->sbcmasterlog(
                        $data2['clientid'],
                        $config,
                        'UPDATE BILLING' . ' - BLINE: ' . $data2['bline'] . ' - AMT: ' . $data2['amt'] . ' - START DATE: ' . $data2['startdate'] . ' - END DATE: ' . $data2['enddate']
                        . ' - ISVAT: ' . $data2['isvat'] . ' - ISINACTIVE: ' . $data2['isinactive'],
                        1,
                        1
                    );
                }
            } // end if
        } // foreach
        $returndata = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata, 'row' => $returndata];
    } // end function

    public function delete($config)
    {
        $row = $config['params']['row'];
        $clientid = $config['params']['tableid'];

        $qry = "delete from " . $this->table . " where clientid=? and bline=?";
        $this->coreFunctions->execqry($qry, 'delete', [$clientid, $row['bline']]);

        $this->logger->sbcdelmaster_log(
            $clientid,
            $config,
            'REMOVE - Billing Type : ' . $row['billtype'] .
            ' - Schedule : ' . $row['scheddate'] .
            ' - Amount : ' . $row['amt'] .
            ' - Start Date : ' . $row['startdate'] .
            ' - End Date : ' . $row['enddate'] .
            ' - Is VAT : ' . $row['isvat'] .
            ' - Is Inactive : ' . $row['isinactive'], 1
        );

        return ['status' => true, 'msg' => 'Successfully deleted.'];
    } // end function

    private function selectqry()
    {
        $qry = "cb.clientid, bm.billtype, bm.sched, case when bm.sched = 0 then 'MONTHLY'
        when bm.sched = 1 then 'ANNUAL' else '' end as scheddate,
        bm.assetid, ca.acno as assetaccount, bm.revenueid, cr.acno as revenueaccount,
        cb.amt, (case when cb.isvat=0 then 'false' else 'true' end) as isvat, 
        (case when cb.isinactive=0 then 'false' else 'true' end) as isinactive,
        cb.bline, date(cb.startdate) as startdate, date(cb.enddate) as enddate";
        return $qry;
    } // end function

    public function loaddata($config)
    {
        $clientid = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ", '' as bgcolor ";

        $qry = "select " . $select . " from " . $this->table . " as cb
        left join billingmaster as bm on bm.line = cb.bline
        left join coa as ca on ca.acnoid = bm.assetid
        left join coa as cr on cr.acnoid = bm.revenueid
        where cb.clientid = ?
        order by cb.bline ";
        $data = $this->coreFunctions->opentable($qry, [$clientid]);

        return $data;
    } // end function

    public function loaddataperrecord($bline, $config)
    {
        $clientid = $config['params']['tableid'];
        $select = $this->selectqry();
        $select = $select . ", '' as bgcolor ";

        $qry = "select " . $select . " from " . $this->table . " as cb
        left join billingmaster as bm on bm.line = cb.bline
        left join coa as ca on ca.acnoid = bm.assetid
        left join coa as cr on cr.acnoid = bm.revenueid
        where cb.clientid = ? and cb.bline = ?";
        $data = $this->coreFunctions->opentable($qry, [$clientid, $bline]);

        return $data;
    } // end function

    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'whlog':
                return $this->lookuplogs($config);
                break;
            case 'lookupclbilling':
                return $this->lookupclbilling($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
                break;
        }
    } // end function

    public function lookupclbilling($config)
    {
        $lookupsetup = array(
            'type' => 'multi',
            'rowkey' => 'line',
            'title' => 'Billing Types',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'tableentry',
            'action' => 'addtogrid',
        );

        $cols = array(
            array('name' => 'billtype', 'label' => 'Bill Type', 'align' => 'left', 'field' => 'billtype', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'scheddate', 'label' => 'Sched', 'align' => 'left', 'field' => 'scheddate', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'assetaccount', 'label' => 'Asset', 'align' => 'left', 'field' => 'assetaccount', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'revenueaccount', 'label' => 'Revenue', 'align' => 'left', 'field' => 'revenueaccount', 'sortable' => true, 'style' => 'font-size:16px;')
        );

        $query = "select bm.line, bm.billtype, bm.sched,
        case when bm.sched = 0 then 'MONTHLY'
        when bm.sched = 1 then 'ANNUAL' else '' end as scheddate,
        bm.assetid, ca.acno as assetaccount,
        bm.revenueid, cr.acno as revenueaccount from billingmaster as bm
        left join coa as ca on ca.acnoid = bm.assetid
        left join coa as cr on cr.acnoid = bm.revenueid";

        $data = $this->coreFunctions->opentable($query, [$config['params']['tableid']]);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    } // end function

    public function lookuplogs($config)
    {
        $clientid = $config['params']['tableid'];
        $doc = 'BILLINGSETUP';
        $lookupsetup = array(
            'type' => 'show',
            'title' => 'Billing Setup Logs',
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
        where log.doc = '" . $doc . "' and log.trno = '" . $clientid . "'";

        $qry = $qry . " order by dateid desc";
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
    } // end function

    public function lookupcallback($config)
    {
        $clientid = $config['params']['tableid'];
        $row = $config['params']['rows'];
        $returndata = [];
        $status = true;
        $msg = 'Successfully added.';
        $currentDate = $this->othersClass->getCurrentTimeStamp();

        foreach ($row as $key2 => $value) {
            $bline = $row[$key2]['line'];
            $sched = $row[$key2]['sched'];

            $activeexists = $this->coreFunctions->datareader(
                "select count(*) as value from clbilling where clientid = ? and bline = ? and isinactive = 0",
                [$clientid, $bline]
            );

            if ($activeexists > 0) {
                $status = false;
                $msg = 'This billing type is already active for this client.';
                continue;
            }

            $config['params']['row']['line'] = 0;
            $config['params']['row']['clientid'] = $clientid;
            $config['params']['row']['bline'] = $bline;
            $config['params']['row']['billtype'] = $row[$key2]['billtype'];
            $config['params']['row']['scheddate'] = $row[$key2]['scheddate'];
            $config['params']['row']['assetaccount'] = $row[$key2]['assetaccount'];
            $config['params']['row']['revenueaccount'] = $row[$key2]['revenueaccount'];
            $config['params']['row']['amt'] = 0;
            $config['params']['row']['startdate'] = $sched == 1 ? $currentDate : '';
            $config['params']['row']['enddate'] = '';
            $config['params']['row']['isvat'] = 'false';
            $config['params']['row']['isinactive'] = 'false';
            $config['params']['row']['bgcolor'] = '';

            $return = $this->save($config);
            if ($return['status']) {
                array_push($returndata, $return['row'][0]);
            } else {
                $status = false;
                $msg = $return['msg'];
            }
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $returndata];
    } // end function

} //end class