<?php

namespace App\Http\Classes\modules\warehousing;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\SBCPDF;

class warehousepicker
{

    public $modulename = 'WAREHOUSE PICKER';
    public $gridname = 'inventory';

    public $tablenum = 'cntnum';
    public $head = 'lahead';
    public $stock = 'lastock';
    public $detail = 'ladetail';

    public $hhead = 'glhead';
    public $hstock = 'glstock';
    public $hdetail = 'gldetail';

    public $tablelogs = 'table_log';
    public $htablelogs = 'htable_log';
    public $tablelogs_del = 'del_table_log';

    private $fields = ['checkerid', 'checkerlocid'];

    public $transdoc = "'SD', 'SE', 'SF', 'SH'";

    private $btnClass;
    private $fieldClass;
    private $tabClass;

    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;

    public $showfilteroption = true;
    public $showfilter = true;
    public $showcreatebtn = false;

    public $showfilterlabel = [
        ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
        ['val' => 'posted', 'label' => 'For Picker Drop', 'color' => 'primary'],
        ['val' => 'picked', 'label' => 'Dropped', 'color' => 'primary'],
        ['val' => 'complete', 'label' => 'Completed', 'color' => 'primary']
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
            'load' => 2028,
            'view' => 2029,
            'edit' => 2029
        );
        return $attrib;
    }

    public function createdoclisting($config)
    {
        $action = 0;
        $lblstatus = 1;
        $statname = 2;
        $added = 3;
        $isqty = 4;
        $barcode = 5;
        $itemdesc = 6;
        $model_name = 7;
        $brand_desc = 8;
        $partno = 9;
        $subcode = 10;
        $clientname = 11;
        $listdocument = 12;
        $ref = 13;
        $location = 14;
        $transtype = 15;
        $lockdate = 16;
        $pickerstart = 17;
        $checkerloc = 18;
        $agentname = 19;
        $rem = 20;


        $getcols = [
            'action', 'lblstatus', 'statname', 'added', 'isqty', 'barcode', 'itemdesc', 'model_name', 'brand_desc', 'partno',
            'subcode', 'clientname', 'listdocument', 'ref', 'location', 'transtype', 'lockdate', 'pickerstart', 'checkerloc', 'agentname', 'rem'
        ];
        $stockbuttons = ['view', 'pickerdrop', 'pickerdropall', 'voiditems'];
        $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

        $cols[$action]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$lblstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$statname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $cols[$added]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $cols[$isqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
        $cols[$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$itemdesc]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$brand_desc]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$model_name]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$partno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$subcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$clientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px; max-width:200px;';
        $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$ref]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$location]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$transtype]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$pickerstart]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$checkerloc]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
        $cols[$agentname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';

        $cols[$clientname]['name'] = 'customername';
        $cols[$clientname]['label'] = 'Customer';

        $cols[$agentname]['name'] = 'agname';
        $cols[$agentname]['field'] = 'agname';

        $cols[$ref]['label'] = 'SO#';

        $cols[$action]['btns']['pickerdrop']['checkfield'] = "void";

        $cols[$isqty]['align'] = "text-left";
        $cols[$partno]['align'] = "text-left";
        $cols[$partno]['label'] = "Part No.";
        $cols[$rem]['label'] = "Remarks";

        $cols[$action]['btns']['pickerdrop']['checkfield'] = "isdrop";
        $cols[$action]['btns']['pickerdropall']['checkfield'] = "isdrop";
        $cols[$action]['btns']['voiditems']['checkfield'] = "isdrop";

        return $cols;
    }

    public function paramsdatalisting($config)
    {
        $fields = ['sjtype', 'print'];
        $col1 = $this->fieldClass->create($fields);

        $fields = [];
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'docno.type', 'input');

        $data = $this->coreFunctions->opentable("select '' as sjtype");
        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
    }

    public function loaddoclisting($config)
    {
        $center = $config['params']['center'];
        $userid = $config['params']['adminid'];
        $option = $config['params']['itemfilter'];

        if ($userid == 0) {
            return ['data' => [], 'status' => false, 'msg' => 'Sorry, you`re not allowed to create transaction. Please setup first your Employee Code.'];
        }

        $filter2 = '';
        $filter3 = '';
        switch ($option) {
            case 'draft':
                $filter = " and (stock.pickerid = 0 or stock.pickerid = " . $userid . ") and stock.pickerstart is null";
                $filter2 = " and stock.returnid = 0";
                $filter3 = " and (rep.pickerid = 0 or rep.pickerid = " . $userid . ") and rep.pickerstart is null";
                break;

            case 'picked':
                $filter = " and stock.pickerid=" . $userid . "  and stock.pickerend is not null";
                $filter3 = " and rep.pickerid=" . $userid . "  and rep.pickerend is not null";
                break;

            case 'complete':
                $filter = " and stock.pickerid=" . $userid . "  and num.postdate is not null";
                $filter3 = " and rep.pickerid=" . $userid . "  and num.postdate is not null";
                break;

            default:
                $filter = " and stock.pickerid=" . $userid . "  and stock.pickerstart is not null and stock.pickerend is null";
                $filter3 = " and rep.pickerid=" . $userid . "  and rep.pickerstart is not null and rep.pickerend is null";
                break;
        }

        $qry = $this->selectqry($config, $filter, $filter2, $filter3);

        $qry .= " order by ifnull(psort,99), customername, docno, location, lockdate, itemdesc";
        $data = $this->coreFunctions->opentable($qry, [$center, $center, $center]);
        $data = $this->othersClass->updatetranstype($data);
        return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
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

    public function createHeadField($config)
    {
        $fields = ['barcode', 'itemname', 'isqty', 'partno', 'subcode'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'barcode.type', 'input');
        data_set($col1, 'barcode.class', 'csbarcode sbccsreadonly');
        data_set($col1, 'itemname.name', 'itemdesc');
        data_set($col1, 'isqty.label', 'DR Quantity');
        data_set($col1, 'isqty.name', 'drqty');
        data_set($col1, 'partno.label', 'Part No.');

        $fields = ['location', 'stat', 'replaceqty', 'soref'];
        $col2 = $this->fieldClass->create($fields);

        $fields = [];
        $col3 = $this->fieldClass->create($fields);

        $fields = [];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }


    public function createTab($access, $config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = ['scanlocation', 'changelocation', 'splitqtypicker'];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        $obj[0]['label'] = 'PICK ITEM';
        $obj[0]['addedparams'] = ['sjtype'];
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
        $data[0]['checker'] = '';
        $data[0]['checkerid'] = 0;
        $data[0]['checkerloc'] = '';
        $data[0]['checkerlocid'] = 0;
        $data[0]['itemdesc'] = '';
        $data[0]['stat'] = '';
        $data[0]['drqty'] = 0;

        return $data;
    }

    private function selectqry($config, $addonfilter, $addonfilter2 = '', $addonfilter3 = '', $loadhead = false)
    {
        $type = '';
        if (isset($config['params']['row']['sjtype'])) {
            $type = $config['params']['row']['sjtype'];
        }

        $qry = "";

        $tablehead = $this->head;
        $tablestock = $this->stock;
        $tablereplace = 'replacestock';
        $tablevoid = 'voidstock';
        $headcninfo = 'cntnuminfo';
        $agentleftjoin = 'ag.client = head.agent';

        if ($loadhead) {
            if ($config['params']['postdate']) {
                goto postedhere;
            }
        }

        if (isset($config['params']['itemfilter'])) {
            if ($config['params']['itemfilter'] == 'complete') {
                postedhere:
                $tablehead = $this->hhead;
                $tablestock = $this->hstock;
                $tablereplace = 'hreplacestock';
                $tablevoid = 'hvoidstock';
                $headcninfo = 'hcntnuminfo';
                $agentleftjoin = 'ag.clientid = head.agentid';
            }
        }

        $filterdoc = '';
        if (isset($config['params']['doclistingparam']['sjtype']['value'])) {
            if ($config['params']['doclistingparam']['sjtype']['value'] != '') {
                $filterdoc = " and head.doc='" . $config['params']['doclistingparam']['sjtype']['value'] . "'";
            }
        }

        if (isset($config['params']['params1']['sjtype']['value'])) {
            if ($config['params']['params1']['sjtype']['value'] != '') {
                $filterdoc = " and head.doc='" . $config['params']['params1']['sjtype']['value'] . "'";

                $userid = $config['params']['adminid'];
                $addonfilter = " and (stock.pickerid = 0 or stock.pickerid = " . $userid . ") and stock.pickerstart is null";
                $addonfilter2 = " and stock.returnid = 0";
                $addonfilter3 = " and (rep.pickerid = 0 or rep.pickerid = " . $userid . ") and rep.pickerstart is null";
            }
        }

        $filterdate = '';
        if (isset($config['params']['date1']) && isset($config['params']['date2'])) {
            $date1 = date('Y-m-d', strtotime($config['params']['date1']));
            $date2 = date('Y-m-d', strtotime($config['params']['date2']));
            $filterdate = " and CONVERT(head.dateid,DATE)>='" . $date1 . "' and CONVERT(head.dateid,DATE)<='" . $date2 . "'";
        }

        $search = '';
        $filtersearch = '';

        $filtersearch = "";
        if (isset($config['params']['search'])) {
            $searchfield = ['head.docno', 'head.clientname', 'cl.name', 'stock.ref', 'item.itemname', 'item.partno', 'item.subcode', 'item.barcode', 'brand.brand_desc', 'master.model_name'];

            $search = $config['params']['search'];
            if ($search != "") {
                $filtersearch = $this->othersClass->multisearch($searchfield, $search);
            }
        }

        if ($loadhead) {
            if ($type == 'REPLACEMENT') {
                goto replacementhere;
            }
        }

        $qry .= "
        select head.trno, head.doc, '' as transtype, head.trno as clientid, head.docno, head.docno as client,head.clientname,left(head.dateid,10) as dateid,
        ifnull(client.clientname,'') as checker, ifnull(cl.name,'') as checkerloc, item.barcode, item.itemname as itemdesc, stock.line, head.lockdate,
        round(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        round(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as drqty,
        round(stock.isamt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as isamt,
        round(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
        ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location, date_format(stock.pickerstart,'%m/%d/%Y %H:%i') as pickerstart,
        (case when stock.pickerid<>0 then 'true' else 'false' end) as added, if(head.customername<>'',head.customername,head.clientname) as customername, stock.ref,
        (case when num.postdate is not null then num.status when stock.pickerstart is null then 'FOR PICKING' else 'PICKED' end) as stat, 'false' as void, stock.pickerid, 0 as replaceqty, 'DR' as sjtype,
        brand.brand_desc, item.partno, item.subcode, stock.ref as soref, head.rem, mmaster.model_name,
        ifnull(ag.clientname, '') as agname, ifnull(stat.status,'') as statname, stat.psort, if(stock.pickerend is not null,'true','false') as isdrop, stock.refx, stock.linex
        from " . $tablehead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join " . $headcninfo . " as ci on ci.trno=head.trno 
        left join client on client.clientid=ci.checkerid
        left join checkerloc as cl on cl.line=ci.checkerlocid
        left join " . $tablestock . " as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join pallet on pallet.line=stock.palletid
        left join location on location.line=stock.locid
        left join frontend_ebrands as brand on item.brand = brand.brandid
        left join model_masterfile as mmaster on mmaster.model_id = item.model
        left join client as ag on " . $agentleftjoin . "
        left join trxstatus as stat on stat.line=head.statid
        where head.lockdate is not null and head.doc in (" . $this->transdoc . ") 
        and num.center = ? and num.crtldate is not null " . $addonfilter . $filterdoc . $filterdate . $filtersearch;

        if ($addonfilter2 != '') {
            $qry .= " union all
            select head.trno, head.doc, '' as transtype, head.trno as clientid, head.docno, head.docno as client,head.clientname,left(head.dateid,10) as dateid,
            ifnull(client.clientname,'') as checker, ifnull(cl.name,'') as checkerloc, item.barcode, item.itemname as itemdesc, stock.line, head.lockdate,
            round(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
            round(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as drqty,
            round(stock.isamt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as isamt,
            round(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
            ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location, date_format(stock.pickerstart,'%m/%d/%Y %H:%i') as pickerstart,
            'false' as added,  if(head.customername<>'',head.customername,head.clientname) as customername, stock.ref, 
            (case when num.postdate is not null then num.status else 'FOR RETURN' end) as stat, 'true' as void, stock.pickerid, 0 as replaceqty, 'VOID' as sjtype,
            brand.brand_desc, item.partno, item.subcode, stock.ref as soref, head.rem, mmaster.model_name,
            ifnull(ag.clientname, '') as agname, ifnull(stat.status,'') as statname, stat.psort, if(stock.pickerend is not null,'true','false') as isdrop, stock.refx, stock.linex
            from " . $tablehead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno
            left join " . $headcninfo . " as ci on ci.trno=head.trno 
            left join client on client.clientid=ci.checkerid
            left join checkerloc as cl on cl.line=ci.checkerlocid
            left join " . $tablevoid . " as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join pallet on pallet.line=stock.palletid
            left join location on location.line=stock.locid
            left join frontend_ebrands as brand on item.brand = brand.brandid
            left join model_masterfile as mmaster on mmaster.model_id = item.model
            left join client as ag on " . $agentleftjoin . "
            left join trxstatus as stat on stat.line=head.statid
            where head.lockdate is not null and head.doc in (" . $this->transdoc . ") and num.center = ? and num.crtldate is not null and stock.trno is not null 
            and stock.returndate is null and stock.pickerstart is not null " . $addonfilter2 . $filterdoc . $filterdate . $filtersearch;
        }


        $qry .= " union all ";

        replacementhere:
        $qry .= "
        select head.trno, head.doc, '' as transtype, head.trno as clientid, head.docno, head.docno as client,head.clientname,left(head.dateid,10) as dateid,
        ifnull(client.clientname,'') as checker, ifnull(cl.name,'') as checkerloc, item.barcode, item.itemname as itemdesc, stock.line, head.lockdate,
        round(rep.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        round(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as drqty,
        round(stock.isamt," . $this->companysetup->getdecimal('currency', $config['params']) . ") as isamt,
        round(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
        ifnull(pallet.name,'') as pallet, ifnull(location.loc,'') as location, date_format(rep.pickerstart,'%m/%d/%Y %H:%i') as pickerstart,
        (case when rep.pickerid<>0 then 'true' else 'false' end) as added,  if(head.customername<>'',head.customername,head.clientname) as customername, stock.ref,
        (case when num.postdate is not null then num.status when rep.pickerstart is null then 'FOR REPLACEMENT' else 'PICKED' end) as stat,
        'false' as void, rep.pickerid,
        round(ifnull(rep.isqty - rep.qa,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as replaceqty, 'REPLACEMENT' as sjtype,
        brand.brand_desc, item.partno, item.subcode, stock.ref as soref, head.rem, mmaster.model_name,
        ifnull(ag.clientname, '') as agname, ifnull(stat.status,'') as statname, stat.psort, if(stock.pickerend is not null,'true','false') as isdrop, stock.refx, stock.linex
        from " . $tablereplace . " as rep
        left join " . $tablestock . " as stock on stock.trno=rep.trno and stock.line=rep.line
        left join " . $tablehead . " as head on stock.trno=head.trno
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join " . $headcninfo . " as ci on ci.trno=head.trno 
        left join client on client.clientid=ci.checkerid
        left join checkerloc as cl on cl.line=ci.checkerlocid
        left join item on item.itemid=stock.itemid
        left join pallet on pallet.line=rep.palletid
        left join location on location.line=rep.locid
        left join frontend_ebrands as brand on item.brand = brand.brandid
        left join model_masterfile as mmaster on mmaster.model_id = item.model
        left join client as ag on " . $agentleftjoin . "
        left join trxstatus as stat on stat.line=head.statid
        where head.lockdate is not null and head.doc in (" . $this->transdoc . ") and num.center = ? and num.crtldate is not null 
        and stock.trno is not null " . $addonfilter3 . $filterdoc . $filterdate . $filtersearch;

        return $qry;
    }

    public function loadheaddata($config)
    {
        $type = '';
        $line = 0;
        $void = 0;

        if (isset($config['params']['clientid'])) {
            $trno = $config['params']['clientid'];
        } else {
            $trno = $config['params']['row']['clientid'];
        }

        if (isset($config['params']['row'])) {
            $line = $config['params']['row']['line'];
            $type = $config['params']['row']['sjtype'];
            $void = $config['params']['row']['void'];
        }

        $center = $config['params']['center'];
        $userid = $config['params']['adminid'];

        $filter = " and stock.trno=? and stock.line=?";
        $filter2 = '';
        $filter3 = ' and rep.trno=? and rep.line=?';

        if ($type == 'REPLACEMENT') {
            $filter = '';
        } else {
            $filter3 = '';
        }

        if ($void) {
            $filter2 = ' and stock.returnid = 0';
        }

        $isposted = false;
        $posted = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$trno]);
        $config['params']['postdate'] = $posted;
        $qry = $this->selectqry($config, $filter, $filter2, $filter3, true);

        $head = $this->coreFunctions->opentable($qry, [$center, $trno, $line, $center, $trno, $line]);

        if (!empty($head)) {
            $msg = 'Data Fetched Success';
            if (isset($config['msg'])) {
                $msg = $config['msg'];
            }

            if ($posted) {
                $hidetabbtn = ['btnscanlocation' => true, 'btnchangelocation' => true, 'btnsplitqty' => true];
                $isposted = true;
            } else {
                if ($type == 'REPLACEMENT') {
                    $picked = $this->coreFunctions->datareader("select pickerstart as value from replacestock where trno=? and line=?", [$trno, $line]);
                } else {
                    $picked = $this->coreFunctions->datareader("select pickerstart as value from lastock where trno=? and line=?", [$trno, $line]);
                }

                if ($picked) {
                    $hidetabbtn = ['btnscanlocation' => true, 'btnchangelocation' => true, 'btnsplitqty' => true];
                } else {
                    $hidetabbtn = ['btnscanlocation' => false, 'btnchangelocation' => false, 'btnsplitqty' => false];
                }
            }

            return  ['head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => $isposted, 'qq' => $trno, 'hidetabbtn' => $hidetabbtn];
        } else {
            $head = $this->resetdata();
            return ['status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
        }
    }

    public function stockstatusposted($config)
    {
        switch ($config['params']['action']) {
            case 'scanlocation':
                $trno = $config['params']['trno'];
                $line = $config['params']['line'];
                $user = $config['params']['adminid'];

                $tablename = 'lastock';
                $msg = 'Successfully updated.';

                $isvoid = $this->coreFunctions->getfieldvalue("voidstock", "line", "trno=? and line=? and voidddate is not null", [$trno, $line]);
                if ($isvoid) {
                    $tablename = 'voidstock';
                }

                $isreplacement = $this->coreFunctions->getfieldvalue("replacestock", "line", "trno=? and line=?", [$trno, $line]);
                if ($isreplacement) {
                    $tablename = 'replacestock';
                }

                $laqty = $this->coreFunctions->getfieldvalue($tablename, "isqty", "trno=? and line=?", [$trno, $line]);
                if ($laqty != $config['params']['qty']) {
                    return ['status' => true, 'msg' => 'Quantity doesn`t match'];
                }

                $current_timestamp = $this->othersClass->getCurrentTimeStamp();

                if ($isvoid) {
                    $data['returndate'] = $current_timestamp;
                    $data['returnid'] = $user;
                    $this->coreFunctions->sbcupdate('voidstock', $data, ['trno' => $trno, 'line' => $line]);

                    $pending_return = $this->coreFunctions->datareader("select line as value from voidstock where trno=? and returndate is null and pickerstart is not null limit 1", [$trno]);
                    if (!$pending_return) {
                        $cntnumstatus = $this->coreFunctions->getfieldvalue("cntnum", "status", "trno=?", [$trno]);
                        if ($cntnumstatus == 'VOID') {
                            $post =  $this->othersClass->posttranstock($config);
                            if ($post) {
                                $current_time = $this->othersClass->getCurrentTimeStamp();
                                $this->coreFunctions->execqry("update hcntnuminfo set status='PICKER VOID', logisticdate='" . $current_time . "', logisticby='" . $config['params']['user'] . "' where trno=?", 'update', [$trno]);
                                $this->coreFunctions->sbcupdate("cntnum", ['status' => 'VOID'], ['trno' => $trno]);
                                return ['status' => true, 'msg' => 'Successfully post void.'];
                            } else {
                                return ['status' => false, 'msg' => 'Posting void failed.'];
                            }
                        }
                    }

                    $msg = 'Successfully return';
                } else {
                    $ispick = $this->coreFunctions->datareader("select ifnull(pickerstart,'') as value from " . $tablename . " where pickerstart is not null and trno=? and line=?", [$trno, $line]);
                    if ($ispick !== '') {
                        return ['status' => false, 'msg' => 'Cannot continue; already picked.'];
                    }

                    $data['pickerstart'] = $current_timestamp;
                    $data['isqty2'] = $config['params']['qty'];
                    $data['pickerid'] = $user;
                    $this->coreFunctions->sbcupdate('lastock', $data, ['trno' => $trno, 'line' => $line]);
                }

                if ($isreplacement) {
                    $this->coreFunctions->execqry("update replacestock set pickerid=" . $user . ", pickerstart='" . $current_timestamp . "', qa=qa+" . $config['params']['qty'] . " where trno=? and line=?", 'update', [$trno, $line]);
                    $stock = $this->coreFunctions->opentable('select locid, palletid from lastock where trno=? and line=?', [$trno, $line]);
                    if (!empty($stock)) {
                        $rem = [];
                        $rem['trno'] = $trno;
                        $rem['line'] = $line;
                        $rem['palletid'] = $stock[0]->palletid;
                        $rem['locid'] = $stock[0]->locid;
                        $rem['splitdate'] = $this->othersClass->getCurrentTimeStamp();
                        $rem['user'] = $config['params']['user'];
                        $rem['remid'] = 0;
                        $rem['isqty'] = $config['params']['qty'];
                        $this->coreFunctions->sbcinsert('splitstock', $rem);
                    }

                    $msg = 'Successfully replace';
                }

                return ['status' => true, 'msg' => $msg];

                break;

            case 'pickerdrop':
                if ($config['params']['row']['pickerid'] == 0) {
                    return ['status' => true, 'msg' => 'Please assign picker.'];
                }

                $trno = $config['params']['row']['trno'];
                $line = $config['params']['row']['line'];

                $isreplacement = false;
                $stocktable = 'lastock';
                if ($config['params']['row']['sjtype'] == 'REPLACEMENT') {
                    $isreplacement = true;
                    $stocktable = 'replacestock';
                }

                $isdrop = $this->coreFunctions->datareader("select trno as value from " . $stocktable . " where pickerend is not null and trno=? and line=?", [$trno, $line]);
                if ($isdrop) {
                    return ['status' => false, 'msg' => 'Item was already drop'];
                }

                $checklocname = '';

                if (isset($config['params']['barcode'])) {

                    $checklocexist = $this->coreFunctions->getfieldvalue("checkerloc", "line", "name=?", [$config['params']['barcode']]);

                    if ($checklocexist) {
                        $this->coreFunctions->sbcupdate("cntnuminfo", ['checkerlocid' => $checklocexist], ['trno' => $trno]);
                        $checklocname = $config['params']['barcode'];
                        goto updatehere;
                    } else {
                        return ['status' => false, 'msg' => 'Checker location  ' . $config['params']['barcode'] . ' does not exist.'];
                    }
                } else {

                    $checkloc = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerlocid", "trno=?", [$trno]);
                    if (!$checkloc) {
                        return ['status' => false, 'msg' => ''];
                    } else {
                        $checklocname = $this->coreFunctions->getfieldvalue("checkerloc", "name", "line=?", [$checkloc]);
                    }
                }

                updatehere:
                $isvoid = $this->coreFunctions->getfieldvalue("voidstock", "line", "trno=? and line=? and voidddate is not null", [$trno, $line]);
                if ($isvoid) {
                    return ['status' => false, 'msg' => 'Picker drop is not applicable in return. You have to scan the location to return this item'];
                }

                $ispick = $this->coreFunctions->datareader("select trno as value from " . $stocktable . " where pickerstart is null and trno=? and line=?", [$trno, $line]);
                if ($ispick) {
                    return ['status' => false, 'msg' => 'Please scan location and barcode first.'];
                }

                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                $data['pickerend'] = $current_timestamp;

                $this->coreFunctions->sbcupdate($stocktable, $data, ['trno' => $trno, 'line' => $line], 'pickerstart is not null');

                if (!$isreplacement) {
                    $pending = $this->coreFunctions->datareader("select trno as value from lastock where pickerend is null and trno=?", [$trno]);
                    if (!$pending) {
                        $ischecker = $this->coreFunctions->datareader("select checkerid as value from cntnuminfo where trno=?", [$trno]);
                        if (!$ischecker) {
                            $this->coreFunctions->execqry("update cntnum set status='PICKED' where trno=" . $trno);
                            $this->coreFunctions->execqry("update cntnuminfo set status='PICKED' where trno=" . $trno);
                            $this->logger->sbcwritelog($trno, $config, 'PICKER', 'ALL ITEMS DROPPED TO DEPOSIT LOCATION');
                        }
                    }
                }

                return ['status' => true, 'msg' => 'Successfully updated.. Checker Location ' . $checklocname, 'action' => 'reloadlisting'];
                break;

            case 'batchpickerdrop':
                return ['status' => true, 'msg' => 'test', 'action' => 'reloadlisting'];
                break;

            case 'pickerdropall':
                if ($config['params']['row']['pickerid'] == 0) {
                    return ['status' => false, 'msg' => 'Please assign picker.'];
                }

                $trno = $config['params']['row']['trno'];
                $line = $config['params']['row']['line'];

                $isreplacement = false;
                $stocktable = 'lastock';
                if ($config['params']['row']['sjtype'] == 'REPLACEMENT') {
                    $isreplacement = true;
                    $stocktable = 'replacestock';
                }

                $isdrop = $this->coreFunctions->datareader("select trno as value from " . $stocktable . " where pickerend is not null and trno=? and line=?", [$trno, $line]);
                if ($isdrop) {
                    return ['status' => false, 'msg' => 'Item was already drop'];
                }

                $isvoid = $this->coreFunctions->getfieldvalue("voidstock", "line", "trno=? and line=? and voidddate is not null", [$trno, $line]);
                if ($isvoid) {
                    return ['status' => false, 'msg' => 'Picker drop is not applicable in return. You have to scan the location to return this item'];
                }

                $checklocid = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerlocid", "trno=?",  [$trno]);
                if ($checklocid == '') {
                    $checklocid = 0;
                }

                if ($checklocid == 0) {
                    return ['status' => false, 'msg' => 'Please drop atleast one item first'];
                }

                $checklocname = $this->coreFunctions->getfieldvalue("checkerloc", "name", "line=?", [$checklocid]);

                $ispickpending = $this->coreFunctions->datareader("select trno as value from " . $stocktable . " where pickerstart is null and trno=?", [$trno]);
                if ($ispickpending) {
                    return ['status' => false, 'msg' => 'Please pick all items first before using drop all items'];
                }

                $current_timestamp = $this->othersClass->getCurrentTimeStamp();
                $data['pickerend'] = $current_timestamp;

                $this->coreFunctions->sbcupdate($stocktable, $data, ['trno' => $trno], 'pickerstart is not null');

                if (!$isreplacement) {
                    $pending = $this->coreFunctions->datareader("select trno as value from lastock where pickerend is null and trno=?", [$trno]);
                    if (!$pending) {
                        $ischecker = $this->coreFunctions->datareader("select checkerid as value from cntnuminfo where trno=?", [$trno]);
                        if (!$ischecker) {
                            $this->coreFunctions->execqry("update cntnum set status='PICKED' where trno=" . $trno);
                            $this->coreFunctions->execqry("update cntnuminfo set status='PICKED' where trno=" . $trno);
                            $this->logger->sbcwritelog($trno, $config, 'PICKER', 'ALL ITEMS DROPPED TO DEPOSIT LOCATION (Batch)');
                        }
                    }
                }

                return ['status' => true, 'msg' => 'Items were successfully dropped on ' . $checklocid, 'action' => 'reloadlisting'];
                break;

            case 'voiditems':
                $row = $config['params']['row'];
                $trno = $config['params']['row']['trno'];
                $line = $config['params']['row']['line'];
                $user = $config['params']['adminid'];


                $isvoided = $this->coreFunctions->datareader("select void as value from voidstock where trno=? and line=? and void=1", [$trno, $line]);
                if ($isvoided) {
                    $msg .= $value['itemdesc'] . ' was already voided, ';
                    continue;
                }

                $qry = "insert into voidstock (trno, line, refx, linex, uom, disc, rem, rrcost, cost, rrqty, qty, isamt, amt, isqty, iss, ext, qa, ref, void, encodeddate, encodedby, editdate, editby, loc, loc2, sku, tstrno, tsline, comm, icomm, expiry, isqty2, iscomponent, outputid, iss2, agent, agent2, isextract, outputline, tsako, msako, itemcomm, itemhandling, kgs, isfromjo, original_qty, jotrno, joline, fcost, itemid, whid, rebate, stageid, palletid, locid, palletid2, locid2, pickerid, pickerstart, pickerend, forkliftid, isforklift, whmanid, whmandate, voidby, voidddate) 
                                select trno, line, refx, linex, uom, disc, rem, rrcost, cost, rrqty, qty, isamt, amt, isqty, iss, ext, qa, ref, 1, encodeddate, encodedby, editdate, editby, loc, loc2, sku, tstrno, tsline, comm, icomm, expiry, isqty2, iscomponent, outputid, iss2, agent, agent2, isextract, outputline, tsako, msako, itemcomm, itemhandling, kgs, isfromjo, original_qty, jotrno, joline, fcost, itemid, whid, rebate, stageid, palletid, locid, palletid2, locid2, pickerid, pickerstart, pickerend, forkliftid, isforklift, whmanid, whmandate,'" . $config['params']['user'] . "',now() from lastock where trno=" . $trno . " and line=" . $line;
                $result = $this->coreFunctions->execqry($qry);
                if ($result) {
                    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=? and line=?', 'delete', [$trno, $line]);
                    $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);


                    $path = 'App\Http\Classes\modules\warehousingentry\\entrywhcontroller';
                    app($path)->setserveditems($row['refx'], $row['linex'], $row['doc']);

                    $this->logger->sbcwritelog($trno, $config, 'VOID PICKER', $row['itemdesc'], 'table_log');

                    $pending = $this->coreFunctions->datareader("select trno as value from lastock where pickerend is null and trno=?", [$trno]);
                    if (!$pending) {
                        $cntnum_status = 'PICKED';

                        $pending = $this->coreFunctions->datareader("select trno as value from lastock where void=0 and trno=?", [$trno]);
                        if (!$pending) {
                            $cntnum_status = 'VOID';
                        }

                        $this->coreFunctions->execqry("update cntnum set status='" . $cntnum_status . "' where trno=" . $trno);
                        $this->coreFunctions->execqry("update cntnuminfo set status='" . $cntnum_status . "' where trno=" . $trno);
                    }
                }

                return ['status' => true, 'msg' => 'void items', 'action' => 'reloadlisting'];
                break;
        }
    }


    public function doclistingreport($config)
    {
        $companyid = $config['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $config['params']);
        $style = 'width:500px;max-width:500px;';
        $result = $this->loaddoclisting($config);
        if (!$result['status']) {
            return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => 'Failed to generate report', 'style' => $style, 'directprint' => true];
        }


        $data = $result['data'];


        $str = '';
        $count = 35;
        $page = 35;
        $font =  "Century Gothic";
        $fontsize = "16";
        $border = "1px solid ";

        $docno = '';
        $totalqty = 0;
        $totalamt = 0;
        $totalext = 0;


        $str .= "<div style = 'margin-left: -80px;'>";
        $str .= $this->reporter->beginreport();
        $str .= $this->default_header($config);

        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]->docno != $docno) {

                $str .= $this->reporter->begintable('1000');
                $str .= $this->reporter->startrow();

                $str .= '<br>';
                $str .= $this->reporter->col('Customer No: ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->customername, '380', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

                $str .= $this->reporter->col('Agent Name: ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->agname, '380', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable('1000');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Document: ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->docno, '470', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

                $str .= $this->reporter->col('Remarks: ', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->rem, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable('1000');
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('SO # : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->ref, '500', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
                $str .= $this->titlecolumn_header($config);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($i + 1, '30', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($data[$i]->isqty, $this->companysetup->getdecimal('qty', $config['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($data[$i]->isamt, $this->companysetup->getdecimal('currency', $config['params'])), '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($data[$i]->ext, $this->companysetup->getdecimal('currency', $config['params'])), '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

                $str .= $this->reporter->col($data[$i]->location, '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->barcode, '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->subcode, '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->partno, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->itemdesc, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->model_name, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
                $str .= $this->reporter->endrow();
                $docno = $data[$i]->docno;
            } else {
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($i + 1, '30', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($data[$i]->isqty, $this->companysetup->getdecimal('qty', $config['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($data[$i]->isamt, $this->companysetup->getdecimal('currency', $config['params'])), '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col(number_format($data[$i]->ext, $this->companysetup->getdecimal('currency', $config['params'])), '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->location, '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->barcode, '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->subcode, '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->partno, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->itemdesc, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col($data[$i]->model_name, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
                $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
                $str .= $this->reporter->endrow();
            }
            $totalqty = $totalqty + $data[$i]->isqty;
            $totalamt = $totalamt + $data[$i]->isamt;
            $totalext = $totalext + $data[$i]->ext;
            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->endtable();
                $str .= $this->reporter->page_break();
                $str .= $this->default_header($config);
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->printline();
                $page = $page + $count;
            }
        }

        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Total(s) : ' . $totalqty, '80', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalamt, $this->companysetup->getdecimal('qty', $config['params'])), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($totalext, $this->companysetup->getdecimal('qty', $config['params'])), '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '95', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= "</div>";

        return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => $str, 'style' => $style, 'directprint' => true];
    }

    public function default_header($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $border = "1px solid ";
        $font =  "Century Gothic";
        $fontsize = "14";

        $str = '';
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('For Picking Item List', '580', null, false, $border, '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br><br>';


        $str .= $this->reporter->begintable('1000');

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Covered: ' . $config['params']['date1'] . ' to ' . $config['params']['date2'], '580', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }


    public function titlecolumn_header($config)
    {
        $border = "1px solid ";
        $font =  "Century Gothic";
        $fontsize = "16";

        $str = '';
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '30', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('QTY', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('AMOUNT', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('TOTAL', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('LOCATION', '50', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('BARCODE', '50', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('OLD SKU', '50', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('PART NO.', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('DESCRIPTION', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('MODEL', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->endrow();
        return $str;
    }
}//end class
