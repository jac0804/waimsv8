<?php

namespace App\Http\Classes\modules\cdo;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class mc
{
    private $btnClass;
    private $fieldClass;
    private $tabClass;
    public $modulename = 'MC COLLECTION';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
    public $tablenum = 'transnum';
    public $head = 'mchead';
    public $hhead = 'hmchead';
    public $detail = 'mcdetail';
    public $hdetail = 'hmcdetail';
    public $tablelogs = 'transnum_log';
    public $tablelogs_del = 'del_transnum_log';
    public $htablelogs = 'htransnum_log';
    private $fields = [
        'trno', 'docno', 'dateid', 'clientid', 'clientname', 'yourref', 'ourref', 'amount',
        'rem', 'address', 'checkinfo', 'modeofpayment', 'checkdate', 'trnxtype', 'rem2', 'sicsino', 'drno', 'chsino', 'swsno'
    ];
    private $except = ['trno', 'dateid'];
    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = true;
    private $reporter;
    private $helpClass;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
        ['val' => 'locked', 'label' => 'Locked', 'color' => 'red'],
        ['val' => 'posted', 'label' => 'Posted', 'color' => 'orange'],
        ['val' => 'all', 'label' => 'All', 'color' => 'green']
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
        $this->helpClass = new helpClass;
    }

    public function getAttrib()
    {
        $attrib = array(
            'view' => 4611,
            'edit' => 4612,
            'new' => 4613,
            'save' => 4614,
            'delete' => 4615,
            'print' => 4616,
            'lock' => 4617,
            'unlock' => 4618,
            'post' => 4619,
            'unpost' => 4620,
            'additem' => 4621,
            'deleteitem' => 4622
        );
        return $attrib;
    }
    public function createHeadbutton($config)
    {
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
            'toggledown',
            'help'
        );

        if ($this->companysetup->getclientlength($config['params']) != 0) {
            array_push($btns, 'others');
        }
        $buttons = $this->btnClass->create($btns);
        $step1 = $this->helpClass->getFields(['btnnew', 'department', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
        $step2 = $this->helpClass->getFields(['btnedit', 'department', 'dateid', 'cswhname', 'yourref', 'csrem', 'btnsave']);
        $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
        $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
        $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
        $step6 = $this->helpClass->getFields(['btndelete']);

        $buttons['others']['items'] = [
            'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
            'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
            'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
            'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
        ];
        return $buttons;
        // createHeadbutton
    }
    public function createHeadField($config)
    {
        $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4850);
        $fields = ['docno', 'client', 'clientname', 'address', 'trnxtype'];

        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'docno.label', 'Transaction#');
        data_set($col1, 'address.class', 'sbccsreadonly');
        data_set($col1, 'trnxtype.label', 'Transaction Type');

        data_set($col1, 'client.lookupclass', 'lookupclient');
        $fields = ['dateid', 'modeofpayment', ['yourref', 'ourref'], 'amount', ['checkinfo', 'checkdate']];

        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'modeofpayment.label', 'Mode of Payment');
        data_set($col2, 'modeofpayment.lookupclass', 'modeofpayment');
        data_set($col2, 'modeofpayment.action', 'lookuprandom');
        data_set($col2, 'yourref.label', 'CR #');
        data_set($col2, 'ourref.label', 'RF#');
        data_set($col2, 'amount.required', true);

        if ($noeditdate) {
            data_set($col2, 'dateid.class', 'sbccsreadonly');
        }

        $fields = ['rem', ['sicsino', 'drno'], ['chsino', 'swsno']];
        $col3 = $this->fieldClass->create($fields);

        $fields = ['rem2'];
        $col4 = $this->fieldClass->create($fields);
        data_set($col4, 'rem2.label', 'Other');
        return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
    }

    public function createTab($access, $config)
    {
        $action = 0;
        $dateid = 1;
        $ref = 2;
        $dr = 3;
        $si = 4;
        $amount = 5;
        $vat = 6;
        $penalty = 7;

        $tab = [
            $this->gridname => [
                'gridcolumns' => [
                    'action',
                    'dateid', 'ref', 'yourref', 'ourref', 'amount','vat', 'penalty'
                ],
                'headgridbtns' => ['view_items']
            ]
        ];

        $stockbuttons = ['delete', 'showbalance'];


        $obj = $this->tabClass->createtab($tab, $stockbuttons);


        $obj[0][$this->gridname]['columns'][$ref]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0][$this->gridname]['columns'][$ref]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amount]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$vat]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$vat]['align'] = 'text-right';
        $obj[0][$this->gridname]['columns'][$penalty]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dr]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$si]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$dr]['label'] = 'SI#';
        $obj[0][$this->gridname]['columns'][$si]['label'] = 'DR#';

        $obj[0][$this->gridname]['columns'][$vat]['style'] = 'text-align:right;width:80px;whiteSpace: normal;min-width:80px;';
        

        $obj[0][$this->gridname]['descriptionrow'] = [];
        $obj[0][$this->gridname]['label'] = ['ACCOUNTING'];
        $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
        $obj[0][$this->gridname]['totalfield'] = 'tamount';

        $obj[0][$this->gridname]['columns'][$action]['btns']['showbalance']['label'] = 'View Items';
        return $obj;
    }
    public function createtab2($access, $config)
    {
        $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
        $obj = $this->tabClass->createtab($tab, []);
        $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
        return $return;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['dp', 'unpaidmccollection', 'deleteallitem'];

        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }
    public function createdoclisting()
    {
        $action = 0;
        $liststatus = 1;
        $listdocument = 2;
        $listdate = 3;
        $listclientname = 4;
        $yourref = 5;
        $ourref = 6;
        $postdate = 7;
        $listpostedby = 8;
        $listcreateby = 9;
        $listeditby = 10;
        $listviewby = 11;

        $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
        $stockbuttons = ['view'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
        $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
        $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
        $cols[$yourref]['align'] = 'text-left';
        $cols[$ourref]['align'] = 'text-left';
        $cols[$postdate]['label'] = 'Post Date';
        $cols[$yourref]['label'] = 'CR#';
        $cols[$ourref]['label'] = 'RF#';
        return $cols;
    }
    public function loaddoclisting($config)
    {
        $date1 = date('Y-m-d', strtotime($config['params']['date1']));
        $date2 = date('Y-m-d', strtotime($config['params']['date2']));
        $itemfilter = $config['params']['itemfilter'];
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $condition = '';
        $limit = 'limit 150';
        $searchfield = [];
        $filtersearch = "";
        $search = $config['params']['search'];

        $join = '';
        $hjoin = '';
        $addparams = '';


        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'cl.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby'];
            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        } else {
            $limit = 'limit 25';
        }

        $status = "(case when head.lockdate is not null and num.postdate is null then 'Locked'
                when num.postdate is not null then 'Posted' else 'DRAFT' end)";
        switch ($itemfilter) {
            case 'draft':
                $status = "'DRAFT'";
                $condition .= ' and num.postdate is null and head.lockdate is null ';
                break;

            case 'locked':
                $status = "'Locked'";
                $condition .= ' and num.postdate is null and head.lockdate is not null ';
                break;

            case 'posted':
                $status = "'Posted'";
                $condition .= ' and num.postdate is not null ';
                break;
        }
        $qry = "select head.trno,head.doc,head.docno,head.clientname,head.rem,head.yourref,head.ourref,
                $status as status,
                left(head.dateid,10) as dateid,date(num.postdate) as postdate,
                head.createby,head.editby,num.postedby,left(head.createdate,10)  as createdate
                from " . $this->head . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno
                left join client as cl on cl.clientid = head.clientid

                where head.doc=? and num.center=? and (CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=?
                or CONVERT(head.createdate,DATE)>=? and CONVERT(head.createdate,DATE)<=?) " . $condition . " " . $filtersearch . " 
                union all
                select head.trno,head.doc,head.docno,head.clientname,head.rem,head.yourref,head.ourref,
                $status as stat,
                left(head.dateid,10) as dateid,date(num.postdate) as postdate,
                head.createby,head.editby,num.postedby,left(head.createdate,10)  as createdate
                from " . $this->hhead . " as head 
                left join " . $this->tablenum . " as num on num.trno=head.trno
                left join client as cl on cl.clientid = head.clientid

                where head.doc=? and num.center=? and (CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=?
                or CONVERT(head.createdate,DATE)>=? and CONVERT(head.createdate,DATE)<=?) " . $condition . " " . $filtersearch . " order by docno desc $limit";

        $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $date1, $date2, $doc, $center, $date1, $date2, $date1, $date2]);

        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
    }
    public function createnewtransaction($docno, $params)
    {
        $data = [];
        $data[0]['trno'] = 0;
        $data[0]['docno'] = $docno;
        $data[0]['dateid'] = $this->othersClass->getCurrentDate();
        $data[0]['clientid'] = 0;
        $data[0]['clientname'] = '';
        $data[0]['client'] = '';
        $data[0]['checkinfo'] = '';
        $data[0]['amount'] = '0.00';
        $data[0]['address'] = '';
        $data[0]['yourref'] = '';
        $data[0]['ourref'] = '';
        $data[0]['trnxtype'] = '';
        $data[0]['rem'] = '';
        $data[0]['modeofpayment'] = '';
        $data[0]['rem2'] = '';

        $data[0]['sicsino'] = '';
        $data[0]['drno'] = '';
        $data[0]['chsino'] = '';
        $data[0]['swsno'] = '';
        return $data;
    }
    public function updatehead($config, $isupdate)
    {
        $head = $config['params']['head'];
        $data = [];
        if ($isupdate) {
            unset($this->fields[1]);
            unset($head['docno']);
        }

        foreach ($this->fields as $key) {
            if (array_key_exists($key, $head)) {
                $data[$key] = $head[$key];
                if (!in_array($key, $this->except)) {
                    $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
                } //end if    
            }
        }
        if ($isupdate) {
            $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['editby'] = $config['params']['user'];
            $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
        } else {
            $data['doc'] = $config['params']['doc'];
            $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
            $data['createby'] = $config['params']['user'];
            $this->coreFunctions->sbcinsert($this->head, $data);
            $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
        }
    } // end function

    public function loadheaddata($config)
    {
        $doc = $config['params']['doc'];
        $center = $config['params']['center'];
        $trno = $config['params']['trno'];
        if ($trno == 0) {
            $trno = $this->othersClass->readprofile('TRNO', $config);
            if ($trno == '') {
                $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
            } else {
                $t = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where trno = ? and center=? order by trno desc limit 1", [$trno, $center]);
                if ($t == '') {
                    $trno = 0;
                }
            }
            $config['params']['trno'] = $trno;
        } else {
            $this->othersClass->checkprofile('TRNO', $trno, $config);
        }
        $head = [];
        $islocked = $this->othersClass->islocked($config);
        $isposted = $this->othersClass->isposted($config);
        $table = $this->head;
        $htable = $this->hhead;
        $tablenum = $this->tablenum;

        $qryselect = "
        select head.trno,head.doc,head.docno,left(head.dateid,10) as dateid,head.clientname, cl.clientid ,
        cl.client ,head.rem,head.yourref,head.ourref,head.address,head.checkinfo,format(head.amount,2) as amount,head.modeofpayment,head.checkdate,head.trnxtype,head.rem2,head.sicsino,head.drno,head.chsino,head.swsno ";

        $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client as cl on cl.clientid = head.clientid
         where head.trno = ? and num.center=? 
        union all 
        " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client as cl on cl.clientid =  head.clientid
         where head.trno = ? and num.center=? ";
        $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
        if (!empty($head)) {
            $stock = $this->opendetail($trno, $config);
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }
            return  ['head' => $head, 'griddata' => [$this->gridname => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
        } else {
            $head[0]['trno'] = 0;
            $head[0]['docno'] = '';
            return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => [$this->gridname => []], 'msg' => 'Data Head Fetched Failed'];
        }
    }
    private function getdetailselect($config)
    {
        $sqlselect =
            "select detail.trno,detail.line,left(detail.dateid,10) as dateid,format(detail.amount,2) as amount,format(detail.amount+detail.penalty,2) as tamount,
            format(detail.penalty,2) as penalty,detail.interest,detail.principal,detail.ref,detail.refx,case sj.doc when 'MC' then '' else sj.yourref end as yourref,case sj.doc when 'MC' then '' else sj.ourref end as ourref
            ";
        return $sqlselect;
    }
    public function opendetail($trno, $config)
    {

        $sqlselect = $this->getdetailselect($config);

        $qry = $sqlselect . " ,case when sj.tax=12 then format((detail.amount/1.12)*0.12,2) else 0 end as vat,  
             '' bgcolor,'' as errcolor 
        FROM  " . $this->detail . "  as detail  left join glhead as sj on sj.trno = detail.refx      
        where sj.doc in ('MJ','CI') and detail.trno = ?
        union all 
        " . $sqlselect . ",case when sj.tax=12 then format((detail.amount/1.12)*0.12,2) else 0 end as vat,  
             '' bgcolor,'' as errcolor  FROM  " . $this->hdetail . "  as detail 
        left join glhead as sj on sj.trno = detail.refx        
        where sj.doc in ('MJ','CI') and detail.trno = ? 
        union all " .
            $sqlselect . " ,case when sj.tax=12 then format((detail.amount/1.12)*0.12,2) else 0 end as vat,  
             '' bgcolor,'' as errcolor 
        FROM  " . $this->detail . "  as detail  
        left join glhead as gj on gj.trno = detail.refx
        left join cntnum as num on num.trno = gj.trno
        left join cntnum as sjnum on sjnum.trno = num.recontrno
        left join glhead as sj on sj.trno = sjnum.trno        
        where gj.doc ='GJ' and detail.trno = ?
        union all
        " . $sqlselect . " ,case when sj.tax=12 then format((detail.amount/1.12)*0.12,2) else 0 end as vat,  
             '' bgcolor,'' as errcolor  FROM  " . $this->hdetail . "  as detail 
        left join glhead as gj on gj.trno = detail.refx
        left join cntnum as num on num.trno = gj.trno
        left join cntnum as sjnum on sjnum.trno = num.recontrno
        left join glhead as sj on sj.trno = sjnum.trno       
        where gj.doc ='GJ' and detail.trno = ? 
        union all
        " . $sqlselect . " ,0 as vat,  
             '' bgcolor,'' as errcolor  FROM  hmcdetail  as detail 
        left join glhead as gj on gj.trno = detail.refx
        left join transnum as num on num.trno = detail.trno
        left join hmchead as sj on sj.trno = num.trno
        where detail.refx=0 and detail.trno = ?
        union all
        " . $sqlselect . " ,0 as vat,  
             '' bgcolor,'' as errcolor  FROM  mcdetail  as detail 
        left join glhead as gj on gj.trno = detail.refx
        left join transnum as num on num.trno = detail.trno
        left join mchead as sj on sj.trno = num.trno
        where detail.refx=0 and detail.trno = ?";

        $stock = $this->coreFunctions->opentable($qry, [$trno, $trno, $trno, $trno, $trno, $trno]);
        return $stock;
    }
    public function opendetailline($trno, $line, $config)
    {
        $sqlselect = $this->getdetailselect($config);
        $qry = $sqlselect . " ,case when sj.tax=12 then (detail.amount/1.12)*0.12 else 0 end as vat,  
             '' bgcolor,'' as errcolor 
        FROM  " . $this->detail . "  as detail left join glhead as sj on sj.trno = detail.refx               
        where detail.trno = ? and detail.line =?
        union all 
        " . $sqlselect . " ,case when sj.tax=12 then (detail.amount/1.12)*0.12 else 0 end as vat,  
             '' bgcolor,'' as errcolor  FROM  " . $this->hdetail . "  as detail left join glhead as sj on sj.trno = detail.refx             
        where detail.trno = ? and detail.line =? ";
        $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
        return $stock;
    }
    public function stockstatus($config)
    {

        switch ($config['params']['action']) {
            case 'getunpaidselected':
                return $this->getunpaidmccollection($config);
                break;
            case 'deleteallitem':
                return $this->deleteallitem($config);
                break;
            case 'deleteitem':
                return $this->deleteitem($config);
                break;
            case 'getdp':
                return $this->getdp($config);
                break;
            case 'getdsundepositedcol':
                return $this->getdsundepositedcol($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
                break;
        }
    }
    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {

            case 'navigation':
                return $this->othersClass->navigatedocno($config);
                break;
            default:
                return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
                break;
        }
    }

    public function getdsundepositedcol($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $data = $config['params']['rows'];
        $status = true;
        foreach ($data as $key => $value) {
            $qry = "update hmchead set dstrno = " . $trno . " where trno = ? ";
            $return = $this->coreFunctions->execqry($qry, "update", [$data[$key]['trno']]);
            if ($return == 1) {
                $row = $this->opendetail($data[$key]['trno'], $config);
                if (!empty($row)) {
                    array_push($rows, $row[0]);
                }
            }
        } //end foreach
        return ['rows' => $rows, 'status' => true, 'reloadhead' => true, 'msg' => 'Added Accounts Successfull...'];
    } //end function

    public function getunpaidmccollection($config)
    {
        $trno = $config['params']['trno'];
        $rows = [];
        $total = 0;
        $headdate = $this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno]);
        $mode = $this->coreFunctions->getfieldvalue($this->head, "trnxtype", "trno=?", [$trno]);
        $penalty = 0;
        $ar = 0;
        $lastpday =  0;
        $daysdue = 0;

        $data = $config['params']['rows'];
        $crref = $data[0]['crno'];
        $rfno = $data[0]['rfno'];
        $swsno = $data[0]['swsno'];
        $chsino = $data[0]['chsino'];
        $drno = $data[0]['yourref'];
        $sino = $data[0]['ourref'];

        foreach ($data as $key => $value) {
            $lastpday =  0;
            $penalty = 0;
            $config['params']['data']['amount'] = $data[$key]['bal'];
            $config['params']['data']['ref'] = $data[$key]['docno'];
            $config['params']['data']['dateid'] = $data[$key]['dateid'];
            $config['params']['data']['refx'] = $data[$key]['trno'];
            $config['params']['data']['principal'] = $data[$key]['principal'];
            $config['params']['data']['interest'] = $data[$key]['interest'];
            $config['params']['data']['rem'] = $data[$key]['rem'];

            //compute penalty
            $dayselapse = date_diff(date_create($headdate), date_create($data[$key]['dateid']));

            $lastpaydate = $this->coreFunctions->datareader("select ifnull(dateid,'') as value from (select h.dateid from mchead as h left join mcdetail as d on d.trno = h.trno where d.refx = " . $data[$key]['trno'] . " and date(d.dateid) = '" . date('Y-m-d', strtotime($data[$key]['dateid'])) . "' and d.penalty <> 0
            union all 
            select h.dateid from hmchead as h left join hmcdetail as d on d.trno = h.trno where d.refx = " . $data[$key]['trno'] . " and date(d.dateid) = '" . date('Y-m-d', strtotime($data[$key]['dateid'])) . "' and d.penalty <>0) as a order by dateid desc limit 1");

            $qry = "select ifnull(dateid,'') as value from (select h.dateid from mchead as h left join mcdetail as d on d.trno = h.trno where d.refx = " . $data[$key]['trno'] . " and date(d.dateid) = '" . date('Y-m-d', strtotime($data[$key]['dateid'])) . "' and d.penalty <> 0
            union all 
            select h.dateid from hmchead as h left join hmcdetail as d on d.trno = h.trno where d.refx = " . $data[$key]['trno'] . " and date(d.dateid) = '" . date('Y-m-d', strtotime($data[$key]['dateid'])) . "' and d.penalty <>0) as a order by dateid desc limit 1";

            $this->coreFunctions->LogConsole($qry);
            $this->coreFunctions->LogConsole($dayselapse->format("%a") . 'elapse');
            if ($data[$key]['penalty'] != '') {
                if ($headdate >= $data[$key]['dateid']) {
                    if ($lastpaydate != "") {
                        $lastpday =  date('d', strtotime($lastpaydate));
                    }

                    $dueday = date('d', strtotime($data[$key]['dateid']));
                    $payday = date('d', strtotime($headdate));
                    $mo = date('m', strtotime($data[$key]['dateid']));
                    $yr = date('Y', strtotime($data[$key]['dateid']));
                    $daysinmo = cal_days_in_month(CAL_GREGORIAN, $mo, $yr);

                    $this->coreFunctions->LogConsole('Last payment:' . $lastpaydate);
                    $this->coreFunctions->LogConsole('Last pay day:' . $lastpday);
                    $this->coreFunctions->LogConsole('Due:' . $dueday);
                    $this->coreFunctions->LogConsole('Coll. Date:' . $headdate);

                    //still for finalizing process
                    if (intval($dayselapse->format("%a")) > 5) { // 5 days grace period
                        $dayselapse =  intval($dayselapse->format("%a"));
                        $daysdue = $dayselapse;
                        $this->coreFunctions->LogConsole($daysdue);
                        if ($lastpday <> 0) { //if with last payment
                            $daysdue = ($daysinmo - $lastpday) + $dueday;
                            if ($payday > $dueday) {
                                $daysdue = $daysdue + ($payday - $dueday);
                            }
                        }
                    } else {
                        $daysdue = 0;
                    }

                    $this->coreFunctions->LogConsole($daysdue);

                    if ($daysdue <> 0) {
                        $penalty = ($daysdue / 30) * ($data[$key]['penalty'] / 100) * ($data[$key]['bal'] + $data[$key]['rebate']);
                    }
                }
            }

            $penalty = ceil($penalty);
            $config['params']['data']['penalty'] = $penalty;
            $config['params']['data']['daysdue'] = $daysdue;

            $return = $this->additem('insert', $config);
            if ($return['status']) {
                array_push($rows, $return['row'][0]);
                if (strtoupper($mode) == "SPAREPARTS") {
                    $this->coreFunctions->execqry("update caledger set mctrno = " . $trno . " where trno = ? and line =?", "update", [$data[$key]['trno'], $data[$key]['line']]);
                }
            }

            $total = $total + $penalty + $data[$key]['bal'];
        } //end foreach
        $this->coreFunctions->execqry("update mchead set amount =" . $total . " where trno = ?", "update", [$trno]);

        return ['row' => $rows, 'status' => true, 'msg' => 'Added accounts Successfull...', 'reloadhead' => true];
    }

    public function getdp($config)
    {
        $trno = $config['params']['trno'];
        $amt = $this->coreFunctions->getfieldvalue($this->head, "amount", "trno=?", [$trno]);
        $dateid = $this->coreFunctions->getfieldvalue($this->head, "dateid", "trno=?", [$trno]);
        $rows = [];

        $config['params']['data']['trno'] = $trno;
        $config['params']['data']['amount'] = $amt;
        $config['params']['data']['ref'] = $this->coreFunctions->getfieldvalue("coa", "acnoname", "alias ='APCA'");
        $config['params']['data']['acnoid'] = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias ='APCA'");
        $config['params']['data']['dateid'] = $dateid;
        $config['params']['data']['refx'] = 0;
        $config['params']['data']['principal'] = 0;
        $config['params']['data']['interest'] = 0;
        $config['params']['data']['penalty'] = 0;
        $return = $this->additem('insert', $config);
        if ($return['status']) {
            array_push($rows, $return['row'][0]);
        }

        return ['row' => $rows, 'status' => true, 'msg' => 'Added accounts Successfull...', 'reloadhead' => true];
    }

    public function additem($action, $config)
    {
        $trno = $config['params']['trno'];
        $amount = $config['params']['data']['amount'];
        $interest = $config['params']['data']['interest'];
        $principal = $config['params']['data']['principal'];
        $penalty = $config['params']['data']['penalty'];
        $ref = $config['params']['data']['ref'];
        $dateid = $config['params']['data']['dateid'];
        $refx = 0;
        $acnoid = 0;
        $rem = '';
        $daysdue = 0;

        if (isset($config['params']['data']['refx'])) {
            $refx = $config['params']['data']['refx'];
        }

        if (isset($config['params']['data']['acnoid'])) {
            $acnoid = $config['params']['data']['acnoid'];
        }

        if (isset($config['params']['data']['rem'])) {
            $rem = $config['params']['data']['rem'];
        }
        if (isset($config['params']['data']['daysdue'])) {
            $daysdue = $config['params']['data']['daysdue'];
        }


        $line = 0;
        if ($action == 'insert') {
            $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
            $line = $this->coreFunctions->datareader($qry, [$trno]);
            if ($line == '') {
                $line = 0;
            }
            $line = $line + 1;
            $config['params']['line'] = $line;
        } elseif ($action == 'update') {
            $config['params']['line'] = $config['params']['data']['line'];
            $line = $config['params']['data']['line'];
            $config['params']['line'] = $line;
        }
        $data = [
            'trno' => $trno,
            'line' => $line,
            'amount' => $amount,
            'interest' => $interest,
            'principal' => $principal,
            'penalty' => $penalty,
            'dateid' => $dateid,
            'refx' => $refx,
            'ref' => $ref,
            'acnoid' => $acnoid,
            'rem' => $rem,
            'daysdue' => $daysdue
        ];
        foreach ($data as $key => $value) {
            $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['editdate'] = $current_timestamp;
        $data['editby'] = $config['params']['user'];
        $msg = '';
        $status = true;

        if ($action == 'insert') {
            $data['encodedby'] = $config['params']['user'];
            $data['encodeddate'] = $current_timestamp;
            if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
                $msg = 'Account was successfully added.';
                $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' Ref:' . $ref . ' Amount:' . $amount . ' Penalty:' . $penalty);
                if ($refx != 0) {
                    //update mctrno
                    $this->coreFunctions->execqry("update gldetail set  mctrno = " . $trno . " where trno =? and postdate =? ", "update", [$refx, $dateid]);
                }

                $row = $this->opendetailline($trno, $line, $config);
                return ['row' => $row, 'status' => true, 'msg' => $msg];
            } else {
                return ['status' => false, 'msg' => 'Add Account Failed'];
            }
        } elseif ($action == 'update') {
            $return = true;
            if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
                if ($refx != 0) {
                    $this->coreFunctions->execqry("update gldetail set  mctrno = " . $trno . " where trno =? and postdate =? ", "update", [$refx, $dateid]);
                }
            } else {
                $return = false;
            }
            return ['status' => $return, 'msg' => ''];
        }
    } // end function

    public function deleteallitem($config)
    {
        $trno = $config['params']['trno'];
        $data = $this->coreFunctions->opentable('select detail.refx,left(detail.dateid,10) as dateid from ' . $this->detail . ' as detail where detail.trno=?', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
        foreach ($data as $key => $value) {
            $this->coreFunctions->execqry("update gldetail set  mctrno = 0 where trno =? and left(postdate,10) =?", "update", [$data[$key]->refx, $data[$key]->dateid]);
            $this->coreFunctions->execqry("update caledger set  mctrno = 0 where trno =? and left(dateid,10) =?", "update", [$data[$key]->refx, $data[$key]->dateid]);
        }
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
        return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }



    public function deleteitem($config)
    {
        $config['params']['trno'] = $config['params']['row']['trno'];
        $config['params']['line'] = $config['params']['row']['line'];
        $config['params']['dateid'] = $config['params']['row']['dateid'];

        $trno = $config['params']['trno'];
        $line = $config['params']['line'];
        $data = $this->opendetailline($trno, $line, $config);
        $qry = "delete from " . $this->detail . " where trno=? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);

        if (floatval($data[0]->refx) != 0) {
            $qry = "update gldetail set mctrno = 0 where trno = " . $data[0]->refx . " and left(postdate,10) ='" . $data[0]->dateid . "' ";
            $this->coreFunctions->execqry($qry, "update");
            $this->coreFunctions->execqry("update caledger set  mctrno = 0 where trno =? and left(dateid,10) =?", "update", [$data[0]->refx, $data[0]->dateid]);
        }
        $data = json_decode(json_encode($data), true);
        $this->logger->sbcwritelog(
            $trno,
            $config,
            'DETAILINFO',
            'DELETE - Line:' . $line
                . ' Reference:' . $config['params']['row']['ref']
        );
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' Reference:' . $data[0]['ref'] . ' Date:' . $data[0]['dateid'] . ' Amount:' . $data[0]['amount']);
        return ['status' => true, 'msg' => 'Account was successfully deleted.'];
    } // end function
    public function posttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

        $qry = "insert into " . $this->hhead . "(trno,doc,docno,clientid,address,dateid,rem,amount,checkinfo,
                    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,modeofpayment,checkdate,trnxtype,rem2,sicsino,drno,chsino,swsno,clientname)
                    SELECT head.trno,head.doc, head.docno,head.clientid,head.address,
                    head.dateid as dateid, head.rem,head.amount,head.checkinfo,
                    head.yourref, head.ourref,head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.modeofpayment,head.checkdate,head.trnxtype,head.rem2,head.sicsino,head.drno,head.chsino,head.swsno,head.clientname
                    FROM " . $this->head . " as head 
                    where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

        if ($posthead) {
            $qry = "insert into " . $this->hdetail . "(trno,line,ref,refx,dateid,encodedby,encodeddate,amount,penalty,interest,editby,editdate,acnoid,rem,daysdue)
                    SELECT trno,line,ref,refx,dateid,encodedby,encodeddate,amount,penalty,interest,editby,editdate,acnoid,rem,daysdue
                    FROM " . $this->detail . " 
                    where trno=?";
            $postdetail = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
            if ($postdetail) {
                $date = $this->othersClass->getCurrentTimeStamp();
                $data = ['postdate' => $date, 'postedby' => $user];
                $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
                $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Detail'];
            }
        } else {
            $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting Head'];
        }
    } //end function
    public function unposttrans($config)
    {
        $trno = $config['params']['trno'];
        $user = $config['params']['user'];
        $cr = $this->coreFunctions->getfieldvalue($this->hhead, "crtrno", "trno = ?", [$trno]);
        if (floatval($cr) != 0) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to unpost. Already have Received Payment.'];
        }

        $isok = $this->coreFunctions->getfieldvalue($this->hhead, "isok", "trno = ?", [$trno]);
        if (floatval($cr) != 0) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to unpost. Already Close.'];
        }

        $isdl = $this->coreFunctions->getfieldvalue($this->tablenum, "isdownloaded", "trno = ?", [$trno]);
        if (floatval($isdl) != 0) {
            return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to unpost. Already Downloaded to financing.'];
        }


        $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);
        $qry = "insert into " . $this->head . "(trno,doc,docno,clientid,address,dateid,rem,amount,checkinfo,
                    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,modeofpayment,checkdate,trnxtype,rem2,sicsino,drno,chsino,swsno,clientname)
                    SELECT head.trno,head.doc, head.docno,head.clientid,head.address,
                    head.dateid as dateid, head.rem,head.amount,head.checkinfo,
                    head.yourref, head.ourref,head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.modeofpayment,head.checkdate,head.trnxtype,head.rem2,head.sicsino,head.drno,head.chsino,head.swsno,head.clientname
                    FROM " . $this->hhead . " as head 
                    where head.trno=? limit 1";
        $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);


        if ($posthead) {
            $qry = "insert into " . $this->detail . "(trno,line,ref,refx,dateid,encodedby,encodeddate,amount,penalty,interest,editby,editdate,acnoid,rem,daysdue)
                    SELECT trno,line,ref,refx,dateid,encodedby,encodeddate,amount,penalty,interest,editby,editdate,acnoid,rem,daysdue
                    FROM " . $this->hdetail . " 
                    where trno=?";
            $postdetail = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
            if ($postdetail) {
                $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null,postedby='' where trno=?", 'update', [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->hdetail . " where trno=?", "delete", [$trno]);
                $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
                return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
            } else {
                $this->coreFunctions->execqry("delete from " . $this->detail . " where trno=?", "delete", [$trno]);
                $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
                return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Unposting Detail'];
            }
        } else {
            $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
            return ['trno' => $trno, 'status' => false, 'msg' => 'Error on unposting head'];
        }
    } //end function
    public function deletetrans($config)
    {
        $trno = $config['params']['trno'];
        $doc = $config['params']['doc'];
        $table = $config['docmodule']->tablenum;
        $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
        $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
        $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
        $this->deleteallitem($config);
        $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
        $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
        $this->othersClass->deleteattachments($config);
        $this->logger->sbcdel_log($trno, $config, $docno);
        return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    } //end function
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
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['noted'])) $this->othersClass->writeSignatories($config, 'noted', $dataparams['noted']);

        $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
        $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
    }
} //end class
