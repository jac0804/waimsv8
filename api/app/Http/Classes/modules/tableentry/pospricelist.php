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
use DateTime;
use DateInterval;

class pospricelist
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Price List';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    private $table = 'pricelist';
    private $othersClass;
    public $style = 'width:100%';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    private $fields = ['itemid', 'clientid', 'startdate', 'enddate', 'amount', 'amount2', 'cost', 'disc', 'remarks'];
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
            'load' => 5018
        );
        return $attrib;
    }
    //aa
    public function createTab($config)
    {

        $itemid = $config['params']['tableid'];
        $item = $this->othersClass->getitemname($itemid);
        $this->modulename .= ' - Itemcode: ' .  $item[0]->barcode . ' ~ ItemName: ' . $item[0]->itemname;

        $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4876);
        $companyid = $config['params']['companyid'];
        $tableid = $config['params']['tableid'];
        $columns = ['action', 'remarks', 'amount', 'amount2', 'cost', 'disc', 'startdate', 'enddate', 'createdate', 'createby', 'status', 'client', 'clientname',];
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
        $obj[0][$this->gridname]['columns'][$client]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$client]['action'] = "lookupsetup";
        $obj[0][$this->gridname]['columns'][$client]['lookupclass'] = "customerlist";
        $obj[0][$this->gridname]['columns'][$client]['label'] = "Code";
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$clientname]['label'] = "Customer";
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:150px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$createdate]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$createby]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$status]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$startdate]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$startdate]['label'] = 'Start Date (M-D-Y)';
        $obj[0][$this->gridname]['columns'][$startdate]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$enddate]['type'] = 'input';
        $obj[0][$this->gridname]['columns'][$enddate]['label'] = 'End Date (M-D-Y)';
        $obj[0][$this->gridname]['columns'][$enddate]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$amount]['label'] = "Price 1";
        $obj[0][$this->gridname]['columns'][$amount]['style'] = "width:50px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$amount]['align'] = "text-right";
        $obj[0][$this->gridname]['columns'][$amount2]['style'] = "width:50px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$cost]['label'] = "Cost";
        $obj[0][$this->gridname]['columns'][$cost]['style'] = "width:50px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$cost]['readonly'] = false;
        return $obj;
    }


    public function createtabbutton($config)
    {

        $tbuttons = ['addrecord', 'saveallentry', 'masterfilelogs'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }


    public function add($config)
    {
        // date('Y-m-d');
        $data = [];
        $data['line'] = 0;
        $data['clientid'] = 0;
        $data['client'] = '';
        $data['clientname'] = '';
        $data['startdate'] = date_format(date_create($this->othersClass->getCurrentDate()), 'm/d/Y');
        $data['enddate'] =  date('12/31/9990');
        $data['remarks'] = '';
        $data['amount'] = '0.00';
        $data['amount2'] = '0.00';
        $data['cost'] = '0.00';
        $data['disc'] = '';
        $data['createdate'] = '';
        $data['createby'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        $data['itemid'] = $config['params']['tableid'];
        return $data;
    }

    public function saveallentry($config)
    {
        $data = $config['params']['data'];
        $tableid = $config['params']['tableid'];
        $msg = '';
        $editblocked = false;
        $editSaved = false;

        foreach ($data as $key => $value) {

            if ($data[$key]['bgcolor'] != '') {
                if (isset($data[$key]['status']) && $data[$key]['status'] != '') {
                    $editblocked = true;
                    continue;
                }
            }

            $data2 = [];
            if ($data[$key]['bgcolor'] != '') {
                $editSaved = true;
                if (empty($data[$key]['startdate']) || empty($data[$key]['enddate'])) {
                    return ['status' => false, 'msg' => 'Saving failed. Startdate or Enddate is empty'];
                }

                $datehere = $data[$key]['startdate']; // date('m-d-Y');
                $newdate = str_replace('-', '/', $datehere); //date('m/d/Y');
                $datethis = explode('/', $newdate);

                $month = $datethis[0];
                $day = $datethis[1];
                $year = $datethis[2];

                $checkdate = checkdate($month, $day, $year);
                if (!$checkdate) {
                    return ['status' => false, 'msg' => "Start Date Format is invalid"];
                }

                $data[$key]['startdate'] = $year . '-' . $month . '-' . $day;


                $endhere = $data[$key]['enddate']; // date('m-d-Y');
                $newend = str_replace('-', '/', $endhere); //date('m/d/Y');
                $dateend = explode('/', $newend);
                $month2 = $dateend[0];
                $day2 = $dateend[1];
                $year2 = $dateend[2];

                $checkdate2 = checkdate($month2, $day2, $year2);
                if (!$checkdate2) {
                    return ['status' => false, 'msg' => "End Date Format is invalid"];
                }
                $data[$key]['enddate'] = $year2 . '-' . $month2 . '-' . $day2;


                foreach ($this->fields as $key2 => $value2) {
                    $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
                }

                if ($data[$key]['line'] == 0) {
                    $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['createby'] = $config['params']['user'];

                    $line = $this->coreFunctions->insertGetId($this->table, $data2);
                    $code = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$data[$key]['clientid']]);
                    $config['params']['doc'] = 'POSPRICELIST';
                    $this->logger->sbcmasterlog(
                        $tableid,
                        $config,
                        ' CREATE - LINE :' . $line . ''
                            . ' , Start Date: ' . $data[$key]['startdate']
                            . ' , End Date: ' . $data[$key]['enddate']
                            . ' , Remarks: ' . $data[$key]['remarks']
                            . ' , Price 1: ' . $data[$key]['amount']
                            . ' , Price 2: ' . $data[$key]['amount2']
                            . ' , Cost: ' . $data[$key]['cost']
                            . ' , Discount: ' . $data[$key]['disc']
                            . ' , Client: ' . $code
                    );

                    $startdates = date('Y-m-d', strtotime($data2['startdate']));;

                    $dateqry = "select line  from pricelist where startdate < '" . $startdates . "' and enddate >= '" . $startdates . "' and itemid = '$tableid' ";
                    $new = $this->coreFunctions->opentable($dateqry);

                    if (!empty($new)) {
                        $enddate = (new DateTime($startdates))->modify('-1 day')->format('Y-m-d');
                        $this->coreFunctions->sbcupdate($this->table,  ['enddate' => $enddate],  ['itemid' => $tableid, 'line' => $new[0]->line]);
                    }

                    $datenow = $this->othersClass->getCurrentDate();
                    $test = "select amount, amount2,cost from pricelist  where itemid = '$tableid' and clientid='0' and  '$datenow' >= startdate and '$datenow'<=enddate";
                    $new2 = $this->coreFunctions->opentable($test);
                    if (!empty($new2)) {
                        $this->coreFunctions->sbcupdate('item', ['amt' => $new2[0]->amount, 'avecost' => $new2[0]->cost, 'amt2' => $new2[0]->amount2], ['itemid' => $tableid]);
                    }
                } else {

                    $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
                    $data2['editby'] = $config['params']['user'];

                    $datehere = $data2['startdate']; // date('m-d-Y');
                    $startdateshere = (new DateTime($datehere))->format('Y-m-d'); //'2025-05-21'
                    $newdate = str_replace('-', '/', $startdateshere); //date('m/d/Y');
                    $datethis = explode('/', $newdate);

                    $year = $datethis[0];
                    $month = $datethis[1];
                    $day = $datethis[2];

                    $checkdate = checkdate($month, $day, $year);
                    if (!$checkdate) {
                        return ['status' => false, 'msg' => "Start Date Format is invalid"];
                    }

                    $data2['startdate'] = $year . '-' . $month . '-' . $day;

                    $endhere = $data2['enddate']; // date('m-d-Y');
                    $enddateshere = (new DateTime($endhere))->format('Y-m-d'); //'2025-05-21'
                    $newend = str_replace('-', '/', $enddateshere); //date('m/d/Y');
                    $dateend = explode('/', $newend);

                    $year2 = $dateend[0];
                    $month2 = $dateend[1];
                    $day2 = $dateend[2];

                    $checkdate2 = checkdate($month2, $day2, $year2);
                    if (!$checkdate2) {
                        return ['status' => false, 'msg' => "End Date Format is invalid"];
                    }
                    $data2['enddate'] = $year2 . '-' . $month2 . '-' . $day2;

                    $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);

                    $startdates = date('Y-m-d', strtotime($data2['startdate']));;

                    $dateqry = "select line  from pricelist where startdate < '" . $startdates . "' and enddate >= '" . $startdates . "' and itemid = '$tableid' ";
                    $new = $this->coreFunctions->opentable($dateqry);

                    if (!empty($new)) {
                        $enddate = (new DateTime($startdates))->modify('-1 day')->format('Y-m-d');
                        $this->coreFunctions->sbcupdate($this->table,  ['enddate' => $enddate],  ['itemid' => $tableid, 'line' => $new[0]->line]);
                    }

                    $datenow = $this->othersClass->getCurrentDate();
                    $test = "select amount,amount2, cost from pricelist  where itemid = '$tableid' and clientid='0' and  '$datenow' >= startdate and '$datenow'<=enddate";
                    $new2 = $this->coreFunctions->opentable($test);

                    if (!empty($new2)) {
                        $this->coreFunctions->sbcupdate('item', ['amt' => $new2[0]->amount, 'avecost' => $new2[0]->cost, 'amt2' => $new2[0]->amount2], ['itemid' => $tableid]);
                    }
                }
            }
        }

        $datenow = $this->othersClass->getCurrentDate();
        $this->coreFunctions->sbcupdate('item', ['dlock' => $datenow], ['itemid' => $tableid]);


        if ($editSaved && $editblocked) {
            $msg = 'Some items were saved.<br>Edit blocked. Already applied.';
        } elseif ($editblocked) {
            $msg = 'Edit blocked. Already applied.';
        } elseif ($editSaved) {
            $msg = 'All saved successfully.';
        }

        $returndata = $this->loaddata($config);
        return ['status' => true,  'msg' => $msg, 'data' => $returndata, 'reloadhead' => true, 'itemid' => $tableid];
    }

    public function delete($config)
    {
        $tableid = $config['params']['tableid'];
        $row = $config['params']['row'];

        if ($row['status'] != '') {
            return ['status' => false, 'msg' => "Deletion not allowed. This entry has already been applied."];
        }

        $qry = "delete from " . $this->table . " where line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
        $config['params']['doc'] = 'POSPRICELIST';
        $this->logger->sbcmasterlog(
            $tableid,
            $config,
            ' DELETE: ' . $row['line'] . ''
                . ' , Remarks: ' . $row['remarks']
                . ' , Price 1: ' . $row['amount']
                . ' , Price 2: ' . $row['amount2']
                . ' , Cost: ' . $row['cost']
                . ' , Discount: ' . $row['disc']
                . ' , Customer: ' . $row['clientname']
        );
        return ['status' => true, 'msg' => 'Successfully deleted.'];
    }


    public function loaddata($config)
    {
        $tableid = $config['params']['tableid'];
        $date = date('Y-m-d');
        $qry = "select pl.line,pl.itemid,format(pl.amount,2) as amount,format(pl.amount2,2) as amount2,format(pl.cost,2) as cost,ifnull(pl.disc,'') as disc,pl.clientid,cl.client,cl.clientname,
        date_format(pl.startdate,'%m/%d/%Y') as startdate, date_format(pl.enddate,'%m/%d/%Y') as enddate,pl.createdate,pl.createby,pl.remarks,
        case when date(pl.startdate) <= '$date' then 'APPLIED' else '' end as status,'' as bgcolor
        from pricelist as pl
        left join client as cl on cl.clientid = pl.clientid
        where pl.itemid = " . $tableid . "  order by line";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }


    public function lookupsetup($config)
    {
        $lookupclass2 = $config['params']['lookupclass2'];
        switch ($lookupclass2) {
            case 'customerlist':
                return $this->lookupcustomer($config);
                break;
            case 'lookuplogs':
                return $this->lookuplogs($config);
                break;
            default:
                return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
                break;
        }
    }

    public function lookuplogs($config)
    {
        // $doc = $config['params']['doc'];
        $doc = strtoupper("POSPRICELIST");
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
        where log.doc = 'POSPRICELIST' and log.trno = '" . $trno . "'
        union all
        select trno, doc, oldversion as task, log.userid as user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from item_log as log
        left join useraccess as u on u.username=log.userid
        where log.field = 'pricelist' and log.trno = '" . $trno . "'
        union all
        select trno, doc, task, log.user, dateid, 
        if(pic='','blank_user.png',pic) as pic
        from  " . $this->tablelogs_del . " as log
        left join useraccess as u on u.username=log.user
        where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

        $qry = $qry . " order by dateid desc";
        // var_dump($qry);
        $data = $this->coreFunctions->opentable($qry);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, $qry];
    }

    public function lookupcustomer($config)
    {
        $rowindex = $config['params']['index'];
        $lookupclass2 = $config['params']['lookupclass2'];

        $lookupsetup = array(
            'type' => 'single',
            'title' => 'List of Customer',
            'style' => 'width:900px;max-width:900px;'
        );

        $plotsetup = array(
            'plottype' => 'plotgrid',
            'plotting' => ['clientid' => 'clientid', 'client' => 'client', 'clientname' => 'clientname']
        );

        $cols = array(
            array('name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'),
            array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
        );

        $qry = "select client, clientname, clientid from client
            where isbranch = 1
            ";

        $data = $this->coreFunctions->opentable($qry);

        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
    }
} //end class
