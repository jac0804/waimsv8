<?php

namespace App\Http\Classes\modules\ati;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\SBCPDF;

class prlisting
{
    public $modulename = 'ITEM REQUEST MONITORING';
    public $gridname = 'inventory';

    public $tablenum = 'cntnum';
    public $head = 'prhead';
    public $stock = 'prstock';

    public $hhead = 'hprhead';
    public $hstock = 'hprstock';

    public $tablelogs = 'table_log';

    private $btnClass;
    private $fieldClass;
    private $tabClass;

    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;
    private $helpClass;

    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = false;

    public $showfilterlabel = [
        // ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
        // ['val' => 'forclarification', 'label' => 'For Clarification', 'color' => 'primary'],
        // ['val' => 'rejected', 'label' => 'Rejected Canvass', 'color' => 'primary'],
        // ['val' => 'approved', 'label' => 'For PO (Approved Canvass)', 'color' => 'primary'],
        // ['val' => 'void', 'label' => 'Void Items', 'color' => 'primary']
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
        $this->helpClass = new helpClass;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 3746,
            'view' => 3746,
            'edit' => 3746
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $editcolor = $this->othersClass->checkAccess($config['params']['user'], 4190);

        $action = 0;
        $iscleared = 1;
        $ctrlno = 2;
        $deadline =  3;
        $category = 4;
        $createdate = 5;
        $docno =  6;
        $department = 7;
        $itemdesc = 8;
        $rrqty = 9;
        $uom = 10;
        $clientname = 11;
        $stat = 12;
        $ocrref = 13;
        $rrref = 14;
        $cvref = 15;
        $osiref2 = 16;
        $status = 17;
        $numdays = 18;
        $empname = 19;
        $po = 20;
        $supplier = 21;
        $terms = 22;
        $isadv = 23;

        $getcols = [
            'action', 'iscleared', 'ctrlno', 'deadline', 'category',  'createdate', 'docno', 'department', 'itemdesc', 'rrqty', 'uom', 'clientname', 'stat', 'ocrref', 'rrref', 'cvref', 'osiref2', 'ref', 'numdays', 'empname', 'po', 'supplier', 'terms', 'isadv'
        ];

        $stockbuttons = ['view', 'showtimeline']; //'customformupdateinfo', 
        if ($editcolor) {
            array_push($stockbuttons, 'assigncolor', 'removecolor', 'clearpr');
        }
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:140px;whiteSpace: normal;min-width:140px; max-width:140px;';
        $cols[$deadline]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $cols[$createdate]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $cols[$docno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$category]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$department]['style'] = 'width:130px;whiteSpace: normal;min-width:130px; max-width:130px;';
        $cols[$itemdesc]['style'] = 'width:200px;whiteSpace: normal;min-width:200px; max-width:200px;';
        $cols[$clientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$ocrref]['style'] = 'width:200px;whiteSpace: normal;min-width:200px; max-width:200px;';
        $cols[$rrref]['style'] = 'width:200px;whiteSpace: normal;min-width:200px; max-width:200px;';
        $cols[$cvref]['style'] = 'width:200px;whiteSpace: normal;min-width:200px; max-width:200px;';
        $cols[$osiref2]['style'] = 'width:200px;whiteSpace: normal;min-width:200px; max-width:200px;';
        $cols[$status]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $cols[$numdays]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; max-width:80px;';
        $cols[$rrqty]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; max-width:80px;';
        $cols[$supplier]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';

        $cols[$stat]['label'] = 'Module Status';
        $cols[$status]['label'] = 'Item Status';
        $cols[$status]['name'] = 'reqstat';
        $cols[$rrqty]['label'] = 'Qty Needed';
        $cols[$clientname]['label'] = 'Client/Project Name';
        $cols[$numdays]['label'] = 'Days Delayed';
        $cols[$empname]['label'] = 'Assigned Users';
        $cols[$po]['label'] = 'P.O. No.';
        $cols[$createdate]['label'] = 'Date Uploaded';

        if ($editcolor) {
            $cols[$action]['btns']['clearpr']['checkfield'] = "iscleared";
        }

        // if (!$editcolor) {
        $cols[$iscleared]['type'] = 'coldel';
        // }

        $cols = $this->tabClass->delcollisting($cols);
        return $cols;
    }

    public function createHeadbutton($config)
    {
        $btns = array(
            'load',
            'backlisting',
            'toggleup',
            'toggledown'
        );
        $buttons = $this->btnClass->create($btns);
        return $buttons;
    } // createHeadbutton

    public function loaddoclisting($config)
    {
        // - filter by dept
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', -1);
        $adminid = $config['params']['adminid'];

        if ($config['params']['date1'] == 'Invalid date') {
            $config['params']['date1'] =  $config['params']['date2'];
        }
        $date1 = $this->othersClass->datefilter($config['params']['date1']);
        $date2 = $this->othersClass->datefilter($config['params']['date2']);

        $leftjoin = '';
        $filter = '';
        $filterp1 = '';
        $filterp2 = '';
        $limit = '';

        $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : '';
        $colorstype = isset($config['params']['doclistingparam']['colorstype']) ? $config['params']['doclistingparam']['colorstype'] : '';
        $color = isset($config['params']['doclistingparam']['color']) ? $config['params']['doclistingparam']['color'] : '';
        $searchby = isset($config['params']['doclistingparam']['searchby']) ? $config['params']['doclistingparam']['searchby'] : '';

        switch ($itemfilter) {
            case 'approved':
                $leftjoin = " left join hcdstock as cd on cd.refx=stock.trno and cd.linex=stock.line";
                $filter = " and stock.void=0 and cd.status=1 and cd.qty > cd.qa and cd.void=0";
                break;
            case 'forclarification':
                $filter = " and stock.void=0 and stock.status=17";
                break;
            case 'rejected':
                $leftjoin = " left join hcdstock as cd on cd.refx=stock.trno and cd.linex=stock.line";
                $filter = " and stock.void=0 and cd.status=2 and stock.cdqa=0";
                break;
            case 'void':
                $filter = " and stock.void=1";
                break;
            case 'forrr':
                $filterp1 = " and 0=1";
                $filterp2 = " and stock.void=0 and stock.isforrr=1 and sinfo.isrr=0";
                break;
            case 'isrr':
                $filterp2 = " and sinfo.isrr=1";
                break;
            case 'forcv':
                $filterp1 = " and 0=1";
                $filterp2 = " and stock.void=0 and sinfo.isforpay=1 and sinfo.payreleased is null";
                break;
            case 'issued':
                $filter = " and stock.void=0 and stock.qty=(stock.qa+stock.voidqty)";
                break;
            case 'all':
                break;
            case 'sbc':
                $filter = " and stock.void=0 and stock.qty<>(stock.qa+stock.voidqty) and sinfo.iscleared=1";
                break;
            default:
                $filter = " and stock.void=0 and stock.qty<>(stock.qa+stock.voidqty) and sinfo.iscleared=0";
                break;
        }

        $viewall = $this->othersClass->checkAccess($config['params']['user'], 3868);

        if (!$viewall) {
            if ($adminid != 0) {
                $deptid = $this->coreFunctions->getfieldvalue("client", "deptid", "clientid=?", [$adminid], '', true);
                if ($deptid != 0) {
                    $filter .= " and head.deptid='" . $deptid . "'";
                }
            }
        }

        if ($colorstype == 'CATEGORY' && $color != '') {
            $filter .= " and sinfo.color='" . $color . "'";
        }

        $dateidfield = 'date(head.dateid)';

        $filtersearch = "";
        $filtersearchpo = "";
        if (isset($config['params']['search'])) {
            $searchfield = [];
            $searchfieldpo = [];

            switch ($searchby) {
                case 'Control No.':
                    array_push($searchfield, 'sinfo.ctrlno');
                    break;
                case 'Deadline':
                    array_push($searchfield, 'sinfo.deadline');
                    break;
                case 'Category':
                    array_push($searchfield, 'cat.category');
                    break;
                case 'Date Uploaded':
                    array_push($searchfield, 'head.createdate');
                    break;
                case 'Document#':
                    array_push($searchfield, 'head.docno');
                    break;
                case 'Department':
                    array_push($searchfield, 'dept.clientname');
                    break;
                case 'Item Name':
                    array_push($searchfield, 'sinfo.itemdesc');
                    break;
                case 'Qty Needed':
                    array_push($searchfield, 'stock.rrqty');
                    break;
                case 'UOM':
                    array_push($searchfield, 'sinfo.unit');
                    break;
                case 'Client/Project Name':
                    array_push($searchfield, 'head.clientname');
                    break;
                    // case 'Module Status':
                    //     array_push($searchfield, '');
                    //     break;
                    // case 'OCR Status':
                    //     array_push($searchfield, '');
                    //     break;
                case 'Item Status':
                    array_push($searchfield, 'trstat.status');
                    break;
                case 'Days Delayed':
                    array_push($searchfield, 'sinfo.deadline');
                    break;
                case 'Assigned Users':
                    array_push($searchfield, 'emp.clientname');
                    break;

                    // use in the qry that has loop for PO
                case 'P.O. No.':
                    array_push($searchfieldpo, 'pohead.docno');
                    break;
                case 'Supplier Name':
                    array_push($searchfieldpo, 'pohead.clientname');
                    break;
                case 'Terms':
                    array_push($searchfieldpo, 'pohead.terms');
                    break;
                case 'Post Date':
                    $dateidfield = 'date(num.postdate)';
                    $filter .= " and num.postdate is not null";
                    break;
                default:
                    $searchfield = ['sinfo.ctrlno', 'sinfo.deadline', 'cat.category', 'head.docno', 'dept.clientname', 'sinfo.itemdesc', 'sinfo.unit', 'head.clientname', 'trstat.status', 'sinfo.deadline', 'emp.clientname', 'stock.statrem'];
                    $searchfieldpo = ['pohead.docno', 'pohead.clientname', 'pohead.terms'];
                    break;
            }
            // po
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
                // $filtersearchpo = $this->othersClass->multisearch($searchfieldpo, $search);
            }

            $limit = '';
        }

        $sortby = ' order by deadline,category,department,ctrlno ';
        if ($itemfilter == 'forcv') {
            $sortby = " order by ifnull(podeadline,'9998-12-31'),deadline,category,department,ctrlno ";
        }
        if (isset($config['params']['doclistingparam']['sortcode'])) {
            if ($config['params']['doclistingparam']['sortcode'] != '') {
                $sortby = " order by  " . $config['params']['doclistingparam']['sortcode'] . ",ctrlno ";
            }
        }

        if ($config['params']['user'] == 'patriciaco') {
            if ($search == "") $limit = 'limit 500';
        }

        if ($config['params']['user'] == 'mrosello') {
            if ($search == "") $limit = 'limit 300';
        }

        $qry = "select 0 as posted, 'false' as otapproved, if(sinfo.iscleared=1,'true','false') as iscleared, stock.trno as clientid, head.docno as client, stock.trno, stock.line, head.docno, dept.clientname as department, date(ifnull(sinfo.deadline,'9998-12-31')) as deadline, ifnull(cat.category,'') as category, sinfo.itemdesc, 
                concat('Draft - ',(case when head.lockdate is not null then 'Locked' else 'PR' end)) as stat,'' as reqstat, (DATEDIFF(curdate(), date(sinfo.deadline))) as numdays,
                round(stock.rrqty," . $this->companysetup->getdecimal("qty", $config['params']) . ") as rrqty, head.clientname, 
                 sinfo.unit as uom, if(sinfo.color<>'',sinfo.color,'') as bgcolor, sinfo.ctrlno, ifnull(emp.clientname,'') as empname,stock.cdqa,'' as supplier, 
                0 as poqa, 'false' as isadv, '' as terms, 0 as isforrr, null as podeadline, sinfo.color, DATE_FORMAT(head.createdate, '%m-%d') as createdate, head.createdate as createdatesort, '' as cvref, '' as osiref,'' as ocrref,'' as osiref2,'' as rrref
                from prstock as stock 
                left join prhead as head on head.trno=stock.trno 
                left join stockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line 
                left join client as emp on emp.clientid=stock.suppid 
                left join client as dept on dept.clientid=head.deptid 
                left join reqcategory as cat on cat.line=head.ourref
                left join trxstatus as trstat on trstat.line=stock.reqstat
                left join transnum as num on num.trno=head.trno
                " . $leftjoin . "
                where " . $dateidfield . " between '" . $date1 . "' and '" . $date2 . "'" . $filter . $filterp1 . $filtersearch . "
                union all
                select 1 as posted, 'false' as otapproved, if(sinfo.iscleared=1,'true','false') as iscleared, stock.trno as clientid, head.docno as client, stock.trno, stock.line, head.docno, dept.clientname as department, date(ifnull(sinfo.deadline,'9998-12-31')) as deadline, ifnull(cat.category,'') as category, sinfo.itemdesc, 
                (case when stock.statrem='' then 'Posted - PR' else stock.statrem end) as stat, trstat.status as reqstat, (DATEDIFF(curdate(), date(sinfo.deadline))) as numdays,
                round(stock.rrqty," . $this->companysetup->getdecimal("qty", $config['params']) . ") as rrqty, head.clientname,  sinfo.unit as uom, if(sinfo.color<>'',sinfo.color,'') as bgcolor, sinfo.ctrlno, ifnull(emp.clientname,'') as empname,stock.cdqa,'' as supplier, 
                stock.poqa, if(stock.isadv=1,'true','false') as isadv, '' as terms, stock.isforrr, sinfo.podeadline, sinfo.color, DATE_FORMAT(head.createdate, '%m-%d') as createdate, head.createdate as createdatesort, sinfo.cvref,sinfo.osiref, sinfo.ocrref, sinfo.osiref2,sinfo.rrref
                from hprstock as stock 
                left join hprhead as head on head.trno=stock.trno 
                left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line 
                left join client as emp on emp.clientid=stock.suppid
                left join client as dept on dept.clientid=head.deptid 
                left join reqcategory as cat on cat.line=head.ourref 
                left join trxstatus as trstat on trstat.line=stock.reqstat
                left join transnum as num on num.trno=head.trno
                " . $leftjoin . "
                where " . $dateidfield . " between '" . $date1 . "' and '" . $date2 . "'" . $filter . $filterp2 . $filtersearch
            . $sortby . $limit;
        $data = $this->coreFunctions->opentable($qry);

        foreach ($data as $key => $value) {
            // if ($value->posted) {
            //     $ocrref = $this->coreFunctions->datareader(
            //         "select ifnull(group_concat(ref SEPARATOR '\r'),'') as value from (
            //             select concat(num.docno, ' - Draft') as ref from oqstock as s left join transnum as num on num.trno=s.trno where s.reqtrno=? and s.reqline=? group by num.docno,s.sono
            //             union all
            //             select concat(num.docno, ' - Posted') as ref from hoqstock as s left join transnum as num on num.trno=s.trno where s.reqtrno=? and s.reqline=? group by num.docno,s.sono) as s",
            //         [$data[$key]->trno, $data[$key]->line, $data[$key]->trno, $data[$key]->line]
            //     );
            //     $data[$key]->ocrref = $ocrref;

            //     $osiref = $this->coreFunctions->datareader(
            //         "select group_concat(docno,'\r (',sono,')') as value from (
            //         select concat(h.docno,' - Draft') as docno, group_concat(so.sono) as sono from omstock as s left join omso as so on so.trno=s.trno and so.line=s.line left join omhead as h on h.trno=s.trno where s.reqtrno=? and s.reqline=? group by h.docno
            //         union all
            //         select concat(h.docno,' - Posted') as docno, group_concat(so.sono) as sono from homstock as s left join homso as so on so.trno=s.trno and so.line=s.line left join homhead as h on h.trno=s.trno where s.reqtrno=? and s.reqline=? group by h.docno)
            //         as so",
            //         [$data[$key]->trno, $data[$key]->line, $data[$key]->trno, $data[$key]->line]
            //     );
            //     $data[$key]->osiref = $osiref;
            // }
            if ($colorstype != 'CATEGORY') {
                if ($value->numdays >= -1) {
                    $data[$key]->bgcolor = 'bg-red-2';
                }
            }
            if ($value->cdqa > 0 || $value->poqa) {

                $podata = $this->coreFunctions->opentable("
                        select pohead.clientname, pohead.dateid, pohead.terms, pohead.docno 
                        from postock 
                        left join pohead on pohead.trno=postock.trno 
                        where postock.reqtrno=? and postock.reqline=? " . $filtersearchpo . "
                        union all
                        select pohead.clientname, pohead.dateid, pohead.terms, pohead.docno 
                        from hpostock as postock 
                        left join hpohead as pohead on pohead.trno=postock.trno 
                        where postock.reqtrno=? and postock.reqline=? " . $filtersearchpo . "
                        order by dateid desc", [$data[$key]->trno, $data[$key]->line, $data[$key]->trno, $data[$key]->line]);
                if (!empty($podata)) {
                    $data[$key]->supplier = $podata[0]->clientname;
                    $data[$key]->terms = $podata[0]->terms;
                    $data[$key]->po = $podata[0]->docno;
                }
            }
        }

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.', 'qry' => $qry];
    }


    public function paramsdatalisting($config)
    {
        $editcolor = $this->othersClass->checkAccess($config['params']['user'], 4190);

        $fields = [['stat', 'sortby', 'searchby']]; //'searchby'
        $col1 = $this->fieldClass->create($fields);


        data_set($col1, 'stat.label', 'Status');
        data_set($col1, 'stat.type', 'lookup');
        data_set($col1, 'stat.action', 'lookupprlistingstatus');
        data_set($col1, 'stat.lookupclass', 'lookupprlistingstatus');

        data_set($col1, 'sortby.type', 'lookup');
        data_set($col1, 'sortby.readonly', true);
        data_set($col1, 'sortby.action', 'lookupsortby');
        data_set($col1, 'sortby.lookupclass', 'lookupsortbyprlisting');


        data_set($col1, 'searchby.type', 'lookup');
        data_set($col1, 'searchby.readonly', true);
        data_set($col1, 'searchby.action', 'lookupsearchby');
        data_set($col1, 'searchby.lookupclass', 'lookupsearchby');

        $fields = [['colorstype', 'color']];
        $col2 = $this->fieldClass->create($fields);


        $fields = [];
        if ($editcolor) {
            $fields = ['create'];
        }

        $col3 = $this->fieldClass->create($fields);
        data_set($col3, 'create.label', 'Reset colors');
        data_set($col3, 'create.type', 'actionbtn');
        data_set($col3, 'create.style', 'font-size:100%;position:absolute;right:20px;');
        data_set($col3, 'create.action', 'stockstatusposted');
        data_set($col3, 'create.lookupclass', 'stockstatusposted');
        data_set($col3, 'create.access', 'edit');
        data_set($col3, 'create.confirm', true);
        data_set($col3, 'create.confirmlabel', 'Do you want to reset all assigned colors?');

        $data = $this->coreFunctions->opentable("SELECT 'Pending' as stat, 'pending' as typecode, '' as sortby, '' as sortcode, 'DEADLINE' as colorstype, '' as color,'' as searchby");

        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3]];
    }


    public function createHeadField($config)
    {
        $fields = ['client', 'itemdesc', 'specs'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, "itemdesc.type",  "input");
        data_set($col1, "client.type",  "input");
        data_set($col1, "client.label",  "Document #");
        data_set($col1, "specs.type",  "textarea");

        $fields = ['sadesc', ['svsdesc', 'podesc'], 'rem'];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, "sadesc.type",  "input");
        data_set($col2, "svsdesc.type",  "input");
        data_set($col2, "podesc.type",  "input");
        data_set($col2, "rem.label",  "Notes");

        $fields = ['requestorname', ['dateid', 'uom'], ['isqty', 'poqty'], ['rrqty', 'issueqty']];
        $col3 = $this->fieldClass->create($fields);
        data_set($col3, "dateid.label",  "Date Requested");
        data_set($col3, "isqty.label",  "Qty Needed");
        data_set($col3, "requestorname.type",  "input");

        $fields = ['purpose', 'forrevision'];
        $col4 = $this->fieldClass->create($fields);

        data_set($col4, "purpose.label",  "Purpose");
        data_set($col4, "purpose.type",  "textarea");

        data_set($col4, "forrevision.label",  "Update Status");
        data_set($col4, "forrevision.action",  "customform");
        data_set($col4, "forrevision.addedparams",  ['trno', 'line']);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function createTab($access, $config)
    {
        $tab = [
            'stathistorytab' => ['action' => 'tableentry', 'lookupclass' => 'tabstathistory', 'label' => 'STATUS HISTORY', 'checkchanges' => 'tableentry']
        ];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function newclient($config)
    {
        $data = $this->resetdata($config['newclient']);
        return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
    }

    private function resetdata($client = '')
    {
        $data = [];
        $data[0]['clientid'] = 0;
        $data[0]['client'] = $client;
        return $data;
    }

    public function loadheaddata($config)
    {
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];

        $isposted = false;
        $qry = "select 0 as posted, stock.trno as clientid, head.docno as client, stock.trno, stock.line, head.docno, head.dateid, dept.clientname as deptname, date(sinfo.deadline) as deadline, ifnull(cat.category,'') as category, sinfo.itemdesc,
                sinfo.specs, sinfo.purpose,  sinfo.requestorname, head.sano, ifnull(sa.sano,'') as sadesc, head.svsno,ifnull(svs.sano,'') as svsdesc, head.pono,ifnull(po.sano,'') as podesc, sinfo.rem,
                round(stock.rrqty," . $this->companysetup->getdecimal("qty", $config['params']) . ") as isqty, sinfo.unit as uom, 
                round(stock.qa," . $this->companysetup->getdecimal("qty", $config['params']) . ") as poqty, 
                round(0," . $this->companysetup->getdecimal("qty", $config['params']) . ") as issueqty, 
                round(0," . $this->companysetup->getdecimal("qty", $config['params']) . ") as rrqty
                from prstock as stock left join prhead as head on head.trno=stock.trno left join stockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as dept on dept.clientid=head.deptid left join reqcategory as cat on cat.line=head.ourref 
                left join clientsano as sa on sa.line=head.sano left join clientsano as svs on svs.line=head.svsno left join clientsano as po on po.line=head.pono 
                where stock.trno=? and stock.line=?
                union all
                select 1 as posted, stock.trno as clientid, head.docno as client, stock.trno, stock.line, head.docno, head.dateid, dept.clientname as deptname, date(sinfo.deadline) as deadline, ifnull(cat.category,'') as category, sinfo.itemdesc,
                sinfo.specs, sinfo.purpose,  sinfo.requestorname, head.sano, ifnull(sa.sano,'') as sadesc, head.svsno,ifnull(svs.sano,'') as svsdesc, head.pono,ifnull(po.sano,'') as podesc, sinfo.rem,
                round(stock.rrqty," . $this->companysetup->getdecimal("qty", $config['params']) . ") as isqty, sinfo.unit as uom, 
                round(stock.qa," . $this->companysetup->getdecimal("qty", $config['params']) . ") as poqty, 
                round(0," . $this->companysetup->getdecimal("qty", $config['params']) . ") as issueqty, 
                round(0," . $this->companysetup->getdecimal("qty", $config['params']) . ") as rrqty
                from hprstock as stock left join hprhead as head on head.trno=stock.trno left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
                left join client as dept on dept.clientid=head.deptid left join reqcategory as cat on cat.line=head.ourref 
                left join clientsano as sa on sa.line=head.sano left join clientsano as svs on svs.line=head.svsno left join clientsano as po on po.line=head.pono 
                where stock.trno=? and stock.line=?";

        $head = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);

        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => $isposted, 'qq' => $trno];
        } else {
            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function generic($config)
    {
        $this->coreFunctions->LogConsole($config['params']['action']);

        switch ($config['params']['action']) {
            case 'assigncolor':
            case 'removecolor':
                $color = $config['params']['doclistingparam']['color'];
                $trno = $config['params']['row']['trno'];
                $line = $config['params']['row']['line'];

                if ($config['params']['action'] == 'removecolor') {
                    $color = '';
                } else {
                    if ($color == '') {
                        return ['status' => false, 'msg' => 'Please select valid color'];
                    }
                }

                $this->coreFunctions->sbcupdate("stockinfotrans", ['color' => $color], ['trno' => $trno, 'line' => $line]);
                $this->coreFunctions->sbcupdate("hstockinfotrans", ['color' => $color], ['trno' => $trno, 'line' => $line]);

                return ['status' => true, 'msg' => $color == '' ? 'Color has been removed.' : 'Color has been changed.', 'rowupdate' => ['bgcolor' => $color]];
                break;
            case 'clearpr':
                $iscleared = $this->coreFunctions->getfieldvalue(($config['params']['row']['posted'] == 1 ? 'h' : '') . 'stockinfotrans', "iscleared", "trno=? and line=?", [$config['params']['row']['trno'], $config['params']['row']['line']]);
                if ($iscleared == 0) {

                    $data = [
                        'editdate' => $this->othersClass->getCurrentTimeStamp(),
                        'editby' => $config['params']['user'],
                        'iscleared' => 1
                    ];
                    $this->coreFunctions->sbcupdate(($config['params']['row']['posted'] == 1 ? 'h' : '') . 'stockinfotrans', $data, ['trno' => $config['params']['row']['trno'], 'line' => $config['params']['row']['line']]);
                    $this->logger->sbcwritelog($config['params']['row']['trno'], $config, 'STOCK', 'Clear Item . Ctrl No. ' . $config['params']['row']['ctrlno'], "transnum_log");
                    return ['status' => true, 'msg' => 'Item Cleared.', 'rowupdate' => ['bgcolor' => 'bg-indigo-5']];
                } else {
                    return ['status' => false, 'msg' => 'Already tagged as clear.'];
                }

                break;
        }
    }

    public function stockstatusposted($config)
    {
        $this->coreFunctions->LogConsole(json_encode($config['params']['row']));

        if (isset($config['params']['lookupclass'])) {
            switch ($config['params']['lookupclass']) {
                case 'removecolor':
                    $this->coreFunctions->sbcupdate("stockinfotrans", ['color' => ''], []);
                    $this->coreFunctions->sbcupdate("hstockinfotrans", ['color' => ''], []);
                    return ['status' => true, 'msg' => 'Colors were removed.', 'reloadlist' => true];
                    break;
            }
        }
    }
}
