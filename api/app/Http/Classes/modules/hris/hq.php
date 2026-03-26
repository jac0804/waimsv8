<?php

namespace App\Http\Classes\modules\hris;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\sbcscript\sbcscript;

class hq
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'PERSONNEL REQUISITION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $sbcscript;
    public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
    public $tablenum = 'hrisnum';
    public $head = 'personreq';
    public $hhead = 'hpersonreq';
    public $detail = '';
    public $hdetail = 'hpersonreqdetail';
    public $tablelogs = 'hrisnum_log';
    public $tablelogs_del = 'del_hrisnum_log';

    private $fields = ['trno', 'docno',  'dateid',  'dept',  'personnel', 'dateneed', 'job', 'class', 'manpower', 'headcount', 'hpref',  'agerange', 'gpref', 'rank', 'reason', 'remark',   'qualification', 'empid', 'empstatusid', 'startdate', 'enddate', 'branchid',  'amount', 'skill',  'educlevel', 'civilstatus', 'jobsumm',  'hirereason', 'prdstart', 'prdend', 'empmonths',   'empdays', 'notedid',  'recappid', 'appdisid'];
    private $except = ['trno'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
        ['val' => 'all', 'label' => 'All', 'color' => 'primary']
    ];


    public function __construct()
    {
        $this->btnClass = new buttonClass;
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->sbcscript = new sbcscript;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 1240,
            'edit' => 1241,
            'new' => 1242,
            'save' => 1243,
            'delete' => 1245,
            'print' => 1246,
            'post' => 1247,
            'unpost' => 1248,
            'lock' => 1711,
            'unlock' => 1712,
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        if ($config['params']['companyid'] == 58) { //cdo
            $this->showfilterlabel = [
                ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
                ['val' => 'posted', 'label' => 'Approved', 'color' => 'primary'],
                ['val' => 'disapproved', 'label' => 'Disapproved', 'color' => 'primary'],
                ['val' => 'inprocess', 'label' => 'In Process', 'color' => 'primary'],
                ['val' => 'completed', 'label' => 'Completed', 'color' => 'primary']
            ];
        }

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'empcode', 'empname', 'startdate', 'enddate', 'remarks'];

        foreach ($getcols as $key => $value) {
            $$value = $key;
        }

        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$listdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$empcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
        $cols[$empname]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
        $cols[$startdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$enddate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$remarks]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';
        $cols[$liststatus]['align'] = 'text-left';
        $cols[$startdate]['label'] = 'Date Start';
        $cols[$enddate]['label'] = 'Date Completed';
        return $cols;
    }

    public function loaddoclisting($config)
    {
        $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5437);
        $id = $config['params']['adminid'];
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $searchfilter = $config['params']['search'];
        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['num.docno', 'c.clientname', 'c.client'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        $poststat = 'POSTED';
        switch ($itemfilter) {
            case 'draft':
                $condition = ' and num.postdate is null ';
                break;
            case 'posted':
                $poststat = 'APPROVED';
                $condition = " and num.postdate is not null and (h.status1<>'D' and h.status2<>'D' and h.status3<>'D') and h.isapplied=0";
                break;
            case 'disapproved':
                $poststat = 'DISAPPROVED';
                $condition = " and num.postdate is not null and (h.status1='D' or h.status2='D' or h.status3='D') ";
                break;
            case 'inprocess':
                $poststat = 'IN-PROCESS';
                $condition = " and num.postdate is not null and (h.status1<>'D' and h.status2<>'D' and h.status3<>'D') and h.enddate is null  and h.isapplied=1";
                break;
            case 'completed':
                $poststat = 'COMPLETED';
                $condition = " and num.postdate is not null and (h.status1<>'D' and h.status2<>'D' and h.status3<>'D') and h.enddate is not null ";
                break;
        }

        if ($config['params']['companyid'] == 58) { //cdo
            if ($id != 0) {
                if ($viewaccess == '0') {
                    $condition .= " and h.empid=$id ";
                }
            }
        }

        $qry = "select h.trno, num.docno, date(h.dateid) as dateid, 
        c.client as empcode, c.clientname as empname, if(h.lockdate is not null, 'LOCKED', 'DRAFT') as status,date(h.startdate) as startdate,
        date(h.enddate) as enddate,h.remark as remarks
        from " . $this->head . " as h left join client as c on c.client=h.personnel 
        left join " . $this->tablenum . " as num on num.trno=h.trno
        where num.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
        union all
        select h.trno, num.docno, date(h.dateid) as dateid, 
        c.client as empcode, c.clientname as empname, '" . $poststat . "' as status,date(h.startdate) as startdate,
        date(h.enddate) as enddate,h.remark as remarks
        from " . $this->hhead . " as h left join client as c on c.client=h.personnel 
        left join " . $this->tablenum . " as num on num.trno=h.trno
        where num.doc=? and num.center = ? and CONVERT(h.dateid,DATE)>=? and CONVERT(h.dateid,DATE)<=? " . $condition . "  " . $filtersearch . "
        order by docno desc";

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }

    public function createHeadbutton($config)
    {
        $companyid = $config['params']['companyid'];
        switch ($companyid) {
            case 58: //cdo
                $btns = array(
                    'load',
                    'new',
                    'save',
                    'delete',
                    'cancel',
                    'print',
                    'post',
                    'unpost',
                    'lock',
                    'unlock',
                    'logs',
                    'edit',
                    'backlisting',
                    'toggleup',
                    'toggledown'
                );
                break;
            default:
                $btns = array(
                    'load',
                    'new',
                    'save',
                    'delete',
                    'cancel',
                    'print',
                    'post',
                    'unpost',
                    'lock',
                    'unlock',
                    'logs',
                    'edit',
                    'backlisting',
                    'toggleup',
                    'toggledown'
                );
                break;
        }

        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton 

    public function createTab($access, $config)
    {
        $companyid = $config['params']['companyid'];

        $tab = [];

        if ($companyid == 58) { //cdo
            $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5451);
            if ($viewaccess) {
                $tab['applisttab'] = ['action' => 'hrisentry', 'lookupclass' => 'entryapplist', 'label' => 'APPLICANT LISTS'];
            }
        } else {
            $tab = [
                'jobdesctab' => [
                    'action' => 'hrisentry',
                    'lookupclass' => 'entryjobdesc_viewing',
                    'label' => 'JOB DESCRIPTION'
                ],
                'skilldesctab' => [
                    'action' => 'hrisentry',
                    'lookupclass' => 'entryskillreq_viewing',
                    'label' => 'SKILL REQUIREMENTS'
                ]
            ];
        }

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        return $obj;
    }

    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entryhrisnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }

    public function createtabbutton($config)
    {
        // if ($config['params']['companyid'] == 58) { //cdo
        //     $tbuttons = ['addempgrid', 'deleteallitem'];
        //     $obj = $this->tabClass->createtabbutton($tbuttons);
        //     $obj[0]['action'] = 'addempgrid';
        //     $obj[1]['label'] = 'Delete all';
        //     return $obj;
        // }
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];
        $fields = [
            'docno',
            'dateid',
            'ddeptname',
            'dpersonel',
            'notedby1',
            'recommendapp',
            'approvedby',
            'due',
            'jobtitle',
            'dbranchname',
            'hpref',
            ['headcount', 'amount']
        ];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Requesting Department');
        data_set($col1, 'dpersonel.addedparams', ['deptid']);
        data_set($col1, 'dpersonel.type', 'input');
        data_set($col1, 'dpersonel.required', true);
        data_set($col1, 'notedby1.readonly', true);
        data_set($col1, 'notedby1.type', 'lookup');
        data_set($col1, 'notedby1.action', 'lookupemployee');
        data_set($col1, 'notedby1.lookupclass', 'emp1lookup');
        data_set($col1, 'notedby1.label', 'Noted/Endorsed by (Manager | Supervisor)');
        data_set($col1, 'notedby1.class', 'csnotedby sbccsreadonly');

        data_set($col1, 'due.name', 'dateneed');
        data_set($col1, 'due.label', 'Date needed');
        data_set($col1, 'jobtitle.type', 'lookup');
        data_set($col1, 'jobtitle.class', 'csjobtitle sbccsreadonly');
        data_set($col1, 'jobtitle.action', 'lookupjobtitle');
        data_set($col1, 'jobtitle.lookupclass', 'job');

        data_set($col1, 'dbranchname.name', 'branchname');
        data_set($col1, 'dbranchname.label', 'Place of Assignment');
        data_set($col1, 'dbranchname.addedparams', ['job', 'jobtitle']);

        data_set($col1, 'headcount.type', 'cinput');
        data_set($col1, 'headcount.label', 'No. of personnel needed');
        data_set($col1, 'amount.label', 'Salary');
        if ($companyid == 58) { //cdo
            data_set($col1, 'hpref.required', false);
        }

        $fields = [
            'lblacquisition',
            'reasontype',
            'hirereason',
            'lbllocation',
            ['prdstart', 'prdend'],
            'lbldepreciation',
            'empstattype',
            'lblvehicleinfo',
            ['empmonths', 'empdays']
        ];

        if ($companyid == 58) { //cdo
            array_push($fields, 'manpower');
        }
        $col2 = $this->fieldClass->create($fields);

        if ($companyid == 58) { //cdo
            data_set($col2, 'reasontype.required', true);
            data_set($col2, 'hirereason.required', true);
            data_set($col2, 'empstattype.required', true);
        }
        data_set($col2, 'lblacquisition.label', 'Reason for Hiring:');
        data_set($col2, 'lblacquisition.style', 'font-weight:bold;font-size:15px');

        data_set($col2, 'lbllocation.label', 'For Leave of absence:');
        data_set($col2, 'lbllocation.style', 'font-size:15px');

        data_set($col2, 'lblvehicleinfo.label', 'For Contractual/Temporary or Project:');
        data_set($col2, 'lblvehicleinfo.style', 'font-size:15px');

        data_set($col2, 'prdstart.label', 'From');
        data_set($col2, 'prdstart.class', 'csprdstart sbccsreadonly');
        data_set($col2, 'prdend.class', 'csprdend sbccsreadonly');
        data_set($col2, 'prdstart.readonly', true);
        data_set($col2, 'prdend.readonly', true);

        data_set($col2, 'empmonths.class', 'csempmonths sbccsreadonly');
        data_set($col2, 'empdays.class', 'csempdays sbccsreadonly');
        data_set($col2, 'empmonths.readonly', true);
        data_set($col2, 'empdays.readonly', true);

        data_set($col2, 'lbldepreciation.label', 'Employment status upon hiring:');
        data_set($col2, 'lbldepreciation.style', 'font-weight:bold;font-size:15px');

        $fields = [['agerange', 'gpref'], ['educlevel', 'civilstatus'], 'qualification'];

        if ($companyid == 58) { //cdo
            array_push($fields, 'jobsumm', 'skill');
        }

        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'agerange.type', 'cinput');
        data_set($col3, 'gpref.required', false);
        data_set($col3, 'qualification.type', 'ctextarea');

        if ($companyid == 58) { //cdo
            data_set($col3, 'jobsumm.type', 'ctextarea');
            data_set($col3, 'skill.type', 'ctextarea');
            data_set($col3, 'agerange.required', true);
            data_set($col3, 'gpref.required', true);
            data_set($col3, 'educlevel.required', true);
            data_set($col3, 'civilstatus.required', true);
            data_set($col3, 'qualification.required', true);
            data_set($col3, 'jobsumm.required', true);
            data_set($col3, 'skill.required', true);
        }

        $fields = [
            ['startdate', 'enddate'],
            'remark',
            'updatepostedinfo',
            'approvedby1',
            'approvedby2',
            'disapprovedby1',
            'disapprovedby2',
            'namt4',
            'namt5',
            'disapproved_remarks'
        ]; //, 'hqapprovedby
        if ($companyid == 58) { //cdohris
            $fields = [
                ['startdate', 'enddate'],
                'remark',
                'updatepostedinfo',
                'approvedinfodetails',
                'approvedby1',
                'approvedby2',
                'disapprovedby1',
                'disapprovedby2',
                'namt4',
                'namt5',
                'disapproved_remarks'
            ];
        }

        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'startdate.label', 'Date Start');
        data_set($col4, 'startdate.required', false);
        data_set($col4, 'enddate.label', 'Date Completed');
        data_set($col4, 'remark.type', 'ctextarea');

        data_set($col4, 'startdate.readonly', true);
        data_set($col4, 'enddate.readonly', true);
        data_set($col4, 'remark.readonly', true);
        data_set($col4, 'startdate.class', 'csstartdate sbccsreadonly');
        data_set($col4, 'enddate.class', 'csenddate sbccsreadonly');
        data_set($col4, 'remark.class', 'csremark sbccsreadonly');

        data_set($col4, 'approvedby1.label', 'Approved by: ( Manager / Supervisor )');
        data_set($col4, 'approvedby2.label', 'Approved by: ( General Manager )');
        data_set($col4, 'disapprovedby1.label', 'Disapproved by: ( Manager / Supervisor )');
        data_set($col4, 'disapprovedby2.label', 'Disapproved by: ( General Manager )');
        data_set($col4, 'namt4.label', 'Approved by: ( Recommending Approver )');
        data_set($col4, 'namt5.label', 'Disapproved by: ( Recommending Approver )');
        data_set($col4, 'namt4.maxlength', '100');
        data_set($col4, 'namt5.maxlength', '100');

        data_set($col4, 'updatepostedinfo.label', 'UPDATE INFO');


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }


    public function sbcscript($config)
    {
        return $this->sbcscript->hq($config);
    }

    public function createnewtransaction($docno, $params)
    {
        return $this->resetdata($docno, $params);
    }

    public function resetdata($docno = '', $params)
    {
        if (isset($params['adminid'])) {
            $userid = $params['adminid'];
        } else {
            $userid = $params['params']['adminid'];
        }

        $personelcode = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$userid]);
        $personelname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$userid]);

        $deptid = $this->coreFunctions->getfieldvalue("employee", "deptid", "empid=?", [$userid]);
        $deptcode = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$deptid]);
        $deptname = $this->coreFunctions->getfieldvalue("client", "clientname", "clientid=?", [$deptid]);


        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['dept'] = $deptcode;
        $data[0]['deptname'] = $deptname;
        $data[0]['empid'] = $userid;
        $data[0]['personnel'] = $personelcode;
        $data[0]['personnelname'] = $personelname;
        $data[0]['dateneed'] = $this->othersClass->getCurrentDate();
        $data[0]['class'] = '';
        $data[0]['headcount'] = 0;
        $data[0]['hpref'] = '';
        $data[0]['agerange'] = '';
        $data[0]['gref'] = '';
        $data[0]['rank'] = '';
        $data[0]['empstatus'] = '';
        $data[0]['reason'] = '';
        $data[0]['reasontype'] = '';
        $data[0]['empstattype'] = '';
        $data[0]['hirereason'] = '';

        $data[0]['prdstart'] = NULL;
        $data[0]['prdend'] = NULL;
        $data[0]['empmonths'] = '';
        $data[0]['empdays'] = '';

        $data[0]['remark'] = '';
        $data[0]['qualification'] = '';
        $data[0]['job'] = '';
        $data[0]['gpref'] = '';

        $data[0]['empstatusid'] = 0;
        $data[0]['startdate'] = NULL;
        $data[0]['enddate'] = NULL;

        $data[0]['branchid'] = 0;
        $data[0]['branchcode'] = '';
        $data[0]['branchname'] = '';

        $data[0]['amount'] = 0;
        $data[0]['skill'] = '';
        $data[0]['educlevel'] = '';
        $data[0]['civilstatus'] = '';
        $data[0]['jobsumm'] = '';

        $data[0]['notedid'] = 0;
        $data[0]['notedby1'] = '';

        $data[0]['recappid'] = 0;
        $data[0]['recommendapp'] = '';

        $data[0]['appdisid'] = 0;
        $data[0]['approvedby'] = '';

        $data[0]['status1'] = '';
        $data[0]['status2'] = '';
        $data[0]['status3'] = '';

        $data[0]['manpower'] = '';
        return $data;
    }

    public function loadheaddata($config)
    {
        $id = $config['params']['adminid'];
        $doc = $config['params']['doc'];
        $trno = $this->othersClass->val($config['params']['trno']);
        $companyid = $config['params']['companyid'];
        $center = $config['params']['center'];

        // if ($trno == 0) $trno = $this->getlasttrno();
        // $config['params']['trno'] = $trno;

        $head = [];
        $hideobj = [];


        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;
        $tablenum = $this->tablenum;
        $condition = '';

        if ($config['params']['companyid'] == 58) { //cdo
            $viewaccess = $this->othersClass->checkAccess($config['params']['user'], 5437);
            $viewaccessdept = $this->othersClass->checkAccess($config['params']['user'], 5486);

            if ($id != 0) {
                if ($viewaccess == '0') {
                    $checkapprover = $this->coreFunctions->getfieldvalue("pendingapp", "trno", "doc='HQ' and trno=? and clientid=?", [$trno, $id], '', true);
                    if ($checkapprover != 0) {
                    } else {
                        $condition = " and head.empid=$id ";
                    }

                    if ($viewaccessdept) {
                        $deptid = $this->coreFunctions->getfieldvalue("employee", "deptid", "empid=?", [$id], '', true);
                        if ($deptid != 0) {
                            $condition = " and d.clientid=" . $deptid;
                        }
                    }
                }
            }
        }



        $addselect = '';
        $leftjoin = '';
        $empstat = ',empstat.empstatus, empstat.line as empstatusid ';

        $addselect = ",ifnull(branch.clientname,'') as branchname,ifnull(branch.client,'') as branchcode, 
                           head.branchid,head.amount,head.skill,head.educlevel,civilstatus,head.jobsumm,
                           note.clientname as notedby1, note.clientname as disapprovedby1,head.notedid,
                           reco.clientname as recommendapp,head.recappid,
                           app.clientname as approvedby,app.clientname as disapprovedby2,head.appdisid,head.status1,head.status2,
                           em.clientid as personnelid,note.clientname as approvedby1,app.clientname as approvedby2,
                           reco.clientname as namt4,  reco.clientname as namt5,head.status3
                            ";
        $leftjoin = "left join client as branch on branch.clientid = head.branchid";
        $empstat = ',sreq.category as empstattype,head.empstatusid';


        $qryselect = "select num.trno,head.docno,  head.dateid, ifnull(head.dept,'') as dept, 
                            head.personnel, dateneed, head.job, head.class, headcount, hpref, agerange, 
                            gpref, `rank`, head.reason,rreq.category as reasontype $empstat, hirereason,
                            prdstart,prdend,empmonths,empdays, remark, refx, qualification,
                            d.clientname as deptname, em.clientname as personnelname, head.empid, 
                            head.job as jobcode, jt.jobtitle, d.client as dept, d.clientid as deptid,
                            head.startdate,head.enddate,head.manpower,head.disapproved_remarks $addselect ";

        $qry = $qryselect . " from " . $table . " as head  
        left join client as em on em.client = head.personnel
        left join empstatentry as empstat on empstat.line = head.empstatusid
        left join jobthead as jt on jt.docno = head.job
        left join client as d on d.client = head.dept
        left join reqcategory as rreq on rreq.line= head.reason
        left join reqcategory as sreq on sreq.line= head.empstatusid
        left join client as note on note.clientid=head.notedid
        left join client as reco on reco.clientid=head.recappid
        left join client as app on app.clientid=head.appdisid
        left join $tablenum as num on num.trno = head.trno $leftjoin 
        where num.trno = ? and num.doc='" . $doc . "' and num.center=? $condition
        union all " . $qryselect . " from " . $htable . " as head
        left join client as em on em.clientid = head.empid
        left join empstatentry as empstat on empstat.line = head.empstatusid
        left join jobthead as jt on jt.docno = head.job
        left join client as d on d.client = head.dept
        left join reqcategory as rreq on rreq.line= head.reason
        left join reqcategory as sreq on sreq.line= head.empstatusid
        left join client as note on note.clientid=head.notedid
        left join client as reco on reco.clientid=head.recappid
        left join client as app on app.clientid=head.appdisid
        left join $tablenum as num on num.trno = head.trno $leftjoin  
        where num.trno = ? and num.doc='" . $doc . "' and num.center=? $condition ";

        $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);

        if (!empty($head)) {
            $stock = [];
            $viewdate = $this->othersClass->getCurrentTimeStamp();
            $viewby = $config['params']['user'];
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            $adminid = $config['params']['adminid'];
            // $donedate = $this->coreFunctions->getfieldvalue('hrisnumtodo', 'donedate', 'trno=? and clientid=?', [$trno, $adminid]);

            $hideobj['updatepostedinfo'] = false;
            $hideobj['approvedinfodetails'] = false;
            $hideobj['hqapprovedby'] = true;

            $hideobj['approvedby1'] = true;
            $hideobj['disapprovedby1'] = true;
            $hideobj['approvedby2'] = true;
            $hideobj['disapprovedby2'] = true;

            $hideobj['namt4'] = true;
            $hideobj['namt5'] = true;
            $enddate = $head[0]->enddate;

            if (!$isposted) {
                $hideobj['updatepostedinfo'] = true;
            }

            $status1 = $head[0]->status1;
            $status2 = $head[0]->status2;
            $status3 = $head[0]->status3;

            if ($enddate != null || $status3 == 'A') {
                $hideobj['approvedinfodetails'] = true;
            }

            if ($status1 != '') { //manager
                if ($status1 == 'A') {
                    $hideobj['approvedby1'] = false;
                } else {
                    $hideobj['disapprovedby1'] = false;
                }
            }

            if ($status3 != '') { //manager
                if ($status3 == 'A') {
                    $hideobj['namt4'] = false; //aproved
                } else {
                    $hideobj['namt5'] = false;
                }
            }

            if ($status2 != '') { //general manager
                if ($status2 == 'A') {
                    $hideobj['approvedby2'] = false;
                } else {
                    $hideobj['disapprovedby2'] = false;
                }
            }

            $hideobj['disapproved_remarks'] = $head[0]->disapproved_remarks != '' ? false : true;

            $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
            return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
        } else {
            $head = $this->resetdata($docno = '', $config);

            $hideobj['updatepostedinfo'] = true;
            $hideobj['approvedinfodetails'] = true;

            $hideobj['hqapprovedby'] = true;
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed', 'hideobj' => $hideobj];
        }
    }

    public function getlasttrno()
    {
        $last_id = $this->coreFunctions->datareader("
        select trno as value 
        from " . $this->head . " 
        union all
        select trno as value 
        from " . $this->hhead . " 
        order by value DESC LIMIT 1");

        return $last_id;
    }

    public function updatehead($config, $isupdate)
    {

        $head = $config['params']['head'];
        $data = [];
        if ($isupdate) {
            unset($this->fields['docno']);
        }

        if ($head['approvedby'] == '') $head['appdisid'] = 0;
        if ($head['recommendapp'] == '') $head['recappid'] = 0;

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if    
            }
        }
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($isupdate) {
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];

            $this->coreFunctions->sbcinsert($this->head, $data);
            //WAG IDELETE: NI COMMENT LANG MUNA PARA MAUPDATE KAY CDO
            // $url = 'App\Http\Classes\modules\hris\\' . 'hq';
            // $this->othersClass->insertPendingapp(0, $head['trno'], 'HQ', $data, $url, $config, $head['notedid']);

            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
        }
    } // end function  

    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);

        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from pendingapp where trno=?', 'delete', [$trno]);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function

    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $doc = $config['params']['doc'];

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
        $msg = '';
        $qry = "insert into " . $this->hhead . " (trno,docno,dateid,dept,personnel,empid,dateneed,job,class,
                        headcount,hpref,agerange,gpref,`rank`,empstatusid,reason,hirereason,prdstart,prdend,empmonths,empdays,remark,qualification, 
                        createby,createdate,editby,editdate,lockdate,lockuser,viewdate,viewby,
                        amount,skill,educlevel,civilstatus,jobsumm,branchid,notedid,appdisid,status1,status2,status3,recappid,manpower,disapproved_remarks)
                select trno,docno,dateid,dept,personnel,empid,dateneed,job,class,headcount,hpref,
                       agerange,gpref,`rank`,empstatusid,reason,hirereason,prdstart,prdend,empmonths,empdays,remark,qualification,createby,createdate,
                       editby,editdate,lockdate,lockuser,viewdate,viewby,amount,skill,educlevel,
                       civilstatus,jobsumm,branchid,notedid,appdisid,status1,status2,status3,recappid,manpower,disapproved_remarks
                from " . $this->head . " where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
        if ($result === 1) {
        } else {
            $msg = "Posting failed. Kindly check the head data.";
        }

        if ($msg === '') {
            $date = $this->othersClass->getCurrentTimeStamp();
            $data = ['postdate' => $date, 'postedby' => $user];
            $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }
    } //end function

    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $doc = $config['params']['doc'];
        $msg = '';

        $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->head . " (trno, docno, dateid, dept, personnel, empid, dateneed, job, 
                        class, headcount, hpref,agerange,gpref,rank,empstatusid,reason,hirereason,prdstart,prdend,empmonths,empdays,remark,qualification, 
                        createby, createdate, editby, editdate, lockdate, lockuser, viewdate, viewby,
                        amount,skill,educlevel,civilstatus,jobsumm,branchid,notedid,appdisid,status1,status2,status3,recappid,manpower,disapproved_remarks)
                select trno, docno, dateid, dept, personnel, empid, dateneed, job, class, headcount, hpref, 
                        agerange, gpref, rank, empstatusid,reason,hirereason,prdstart,prdend,empmonths,empdays, remark, qualification, createby, 
                        createdate, editby, editdate, lockdate, lockuser, viewdate, viewby,
                        amount,skill,educlevel,civilstatus,jobsumm,branchid,notedid,appdisid,status1,status2,status3,recappid,manpower,disapproved_remarks
                from " . $this->hhead . " where trno=?";
        $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($result === 1) {
        } else {
            $msg = "Unposting failed. Kindly check the head data.";
        }

        if ($msg === '') {
            $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
            $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
            $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
            return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
        } else {
            $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => $msg];
        }
    } //end function

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'hqapprovedby':
                return $this->forapproval($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }


    public function forapproval($config) {}

    // report startto


    public function reportsetup($config)
    {
        $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
        $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

        $modulename = $this->modulename;
        $data = [];
        $style = 'width:500px;max-width:500px;';
        return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
    }


    public function reportdata($config)
    {
        $this->logger->sbcviewreportlog($config);
        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
}
