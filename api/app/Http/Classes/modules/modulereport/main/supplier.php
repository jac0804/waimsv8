<?php

namespace App\Http\Classes\modules\modulereport\main;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class supplier
{

    private $modulename;
    private $reportheader;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
        $this->reportheader = new reportheader;
    }

    public function createreportfilter($config)
    {
        $fields = ['start', 'radiocustreporttype', 'prepared', 'approved', 'received', 'print'];

        $col1 = $this->fieldClass->create($fields);
        data_set(
            $col1,
            'radiocustreporttype.options',
            [
                ["label" => "Accounts Receivable", "value" => "ar", 'color' => 'red'],
                ["label" => "Accounts Payable", "value" => "ap", 'color' => 'red'],
                ["label" => "Postdated Checks", "value" => "pdc", 'color' => 'red'],
                ["label" => "Inventory", "value" => "stock", 'color' => 'red']
            ]
        );
        if ($config['params']['companyid'] == 10) { // afti
            data_set($col1, 'prepared.readonly', true);
            data_set($col1, 'prepared.type', 'lookup');
            data_set($col1, 'prepared.action', 'lookupclient');
            data_set($col1, 'prepared.lookupclass', 'prepared');

            data_set($col1, 'approved.readonly', true);
            data_set($col1, 'approved.type', 'lookup');
            data_set($col1, 'approved.action', 'lookupclient');
            data_set($col1, 'approved.lookupclass', 'approved');

            data_set($col1, 'received.readonly', true);
            data_set($col1, 'received.type', 'lookup');
            data_set($col1, 'received.action', 'lookupclient');
            data_set($col1, 'received.lookupclass', 'received');
        }
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $user = $config['params']['user'];
        $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);

        $paramstr = "select
        'PDFM' as print,
        left(now(),10) as start,
        'ar' as reporttype,
        '' as prepared,
        '' as approved,
        '' as received ";

        if ($config['params']['companyid'] == 8) {
            $paramstr .= " , '$username' as prepared ";
        } else {
            $paramstr .= " ,'' as prepared ";
        }
        return $this->coreFunctions->opentable($paramstr);
    }

    public function generateResult($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        switch ($reporttype) {
            case 'ar':
                $query = $this->default_AR_QUERY($config);
                break;
            case 'ap':
                $query = $this->default_AP_QUERY($config);
                break;
            case 'pdc':
                $query = $this->default_PDC_QUERY($config);
                break;
            case 'stock':
                $query = $this->default_STOCK_QUERY($config);
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function reportplotting($config, $data)
    {
        $data = $this->generateResult($config);
        $reporttype = $config['params']['dataparams']['reporttype'];

        if ($config['params']['dataparams']['print'] == "default") {
            switch ($reporttype) {
                case 'ar':
                    $str = $this->reportdefaultAR($config, $data);
                    break;
                case 'ap':
                    $str = $this->reportdefaultAP($config, $data);
                    break;
                case 'pdc':
                    $str = $this->reportdefaultPDC($config, $data);
                    break;
                case 'stock':
                    $str = $this->reportdefaultSTOCK($config, $data);
                    break;
            }
        } else {
            switch ($reporttype) {
                case 'ar':
                    $str = $this->reportdefaultAR_PDF($config, $data);
                    break;
                case 'ap':
                    $str = $this->reportdefaultAP_PDF($config, $data);
                    break;
                case 'pdc':
                    $str = $this->reportdefaultPDC_PDF($config, $data);
                    break;
                case 'stock':
                    $str = $this->reportdefaultSTOCK_PDF($config, $data);
                    break;
            }
        }
        return $str;
    }

    public function default_AR_QUERY($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select t.trno, t.line, t.doc, t.docno, date_format(t.dateid,'%m/%d/%y') as dateid, t.db, t.cr, t.bal, t.ref,
        t.agent, t.rem, t.status,client.client,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,
        client.email,client.contact,client.fax from
        (
        select `cntnum`.`doc` as `doc`,arledger.`docno`,`arledger`.`trno` as `trno`,
        `arledger`.`line` as `line`,`arledger`.`dateid` as `dateid`,`arledger`.`db` as `db`,
        `arledger`.`cr` as `cr`,arledger.bal,
        `arledger`.`clientid` as `clientid`,`arledger`.`ref` as `ref`,`agent`.`client` as `agent`,
        (`detail`.`rem`) as `rem`,((case when (`arledger`.`db` > 0) then 1 else -(1) end) * `arledger`.`bal`) as `balance`,
        0 as `fbal`,`head`.`ourref` as `reference`,'posted' as `status` from ((((`arledger`
        left join `cntnum` on((`cntnum`.`trno` = `arledger`.`trno`))) left join `gldetail` as detail
        on(((`detail`.`trno` = `arledger`.`trno`) and (`detail`.`line` = `arledger`.`line`))))
        left join `glhead` as head on((`head`.`trno` = `cntnum`.`trno`))) left join `client` `agent`
        on((`agent`.`clientid` = `arledger`.`agentid`))) left join client on client.clientid = arledger.clientid where md5(arledger.clientid)= '$clientid'  and arledger.dateid>='$start'
        union all
        select `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
        `detail`.`db` as `db`,`detail`.`cr` as `cr`,round(abs((`detail`.`db` - `detail`.`cr`)),2) as `bal`,
        `client`.`clientid` as `clientid`,'' as `ref`,'' as `agent`,`detail`.`rem` as `rem`,
        abs((`detail`.`db` - `detail`.`cr`)) as `balance`,0 as `fbal`,'' as `reference`,'' as `status`
        from (((`lahead` as head left join `ladetail` as detail on((`detail`.`trno` = `head`.`trno`)))
        left join `client` on((`client`.`client` = `head`.`client`))) left join `coa` on((`coa`.`acnoid` = `detail`.`acnoid`)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)= '$clientid'  and head.dateid>='$start' and
        left(`coa`.`alias`,2) = 'ar'                                     union all
        select `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
        `detail`.`db` as `db`,`detail`.`cr` as `cr`,round(abs((`detail`.`db` - `detail`.`cr`)),2) as `bal`,
        `client`.`clientid` as `clientid`,'' as `ref`,'' as `agent`,`detail`.`rem` as `rem`,
        abs((`detail`.`db` - `detail`.`cr`)) as `balance`,0 as `fbal`,'' as `reference`,'' as `status` from
        (((`lbhead` as head left join `lbdetail` as detail on((`detail`.`trno` = `head`.`trno`))) left join `client`
        on((`client`.`client` = `head`.`client`))) left join `coa` on((`coa`.`acnoid` = `detail`.`acnoid`)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)= '$clientid'  and head.dateid>='$start'
        and left(`coa`.`alias`,2) = 'ar'
        union all
        select `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
        `detail`.`db` as `db`,`detail`.`cr` as `cr`,round(abs((`detail`.`db` - `detail`.`cr`)),2) as `bal`,
        `client`.`clientid` as `clientid`,'' as `ref`,'' as `agent`,`detail`.`rem` as `rem`,
        abs((`detail`.`db` - `detail`.`cr`)) as `balance`,0 as `fbal`,'' as `reference`,'' as `status`
        from (((`lchead` as head left join `lcdetail` as detail on((`detail`.`trno` = `head`.`trno`)))
        left join `client` on((`client`.`client` = `head`.`client`))) left join `coa` on((`coa`.`acnoid` = `detail`.`acnoid`)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)= '$clientid'  and head.dateid>='$start' and
        left(`coa`.`alias`,2) = 'ar'
        union all
        select `cntnum`.`doc` as `doc`,arledger.`docno`,`arledger`.`trno` as `trno`,`arledger`.`line` as `line`,
        `arledger`.`dateid` as `dateid`,`arledger`.`db` as `db`,`arledger`.`cr` as `cr`,arledger.bal,
        `arledger`.`clientid` as `clientid`,`arledger`.`ref` as `ref`,`agent`.`client` as `agent`,(`detail`.`rem`) as `rem`,
        ((case when (`arledger`.`db` > 0) then 1 else -(1) end) * `arledger`.`bal`) as `balance`,0 as `fbal`,
        `head`.`ourref` as `reference`,'posted' as `status` from hglhead as head
        left join hgldetail as detail on detail.trno = head.trno left join `cntnum` on `cntnum`.`trno` = `head`.`trno`
        left join arledger on arledger.trno = detail.trno and arledger.line = detail.line
        left join `client` `agent` on `agent`.`clientid` = `arledger`.`agentid` left join client on client.clientid = arledger.clientid where
        md5(arledger.clientid)= '$clientid'  and arledger.dateid>='$start'
        ) as t left join client on client.clientid = t.clientid
        order by dateid, docno";

        return $query;
    }

    public function default_AP_QUERY($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select t.yourref, t.trno, t.line, t.doc, t.docno, date_format(t.dateid,'%m/%d/%y') as dateid, t.db, t.cr, t.bal, t.ref, t.rem, t.status,client.client,client.agent,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,client.email,client.contact,client.fax from
        (select `head`.`yourref`, `cntnum`.`doc` as `doc`,apledger.`docno`,`apledger`.`trno` as `trno`,`apledger`.`line` as `line`,
        `apledger`.`dateid` as `dateid`,`apledger`.`db` as `db`,`apledger`.`cr` as `cr`,apledger.bal,
        `apledger`.`clientid` as `clientid`,`apledger`.`ref` as `ref`,'' as agent,
        (`detail`.`rem`) as `rem`,((case when (`apledger`.`db` > 0) then 1 else -(1) end) * `apledger`.`bal`) as `balance`,
        0 as `fbal`,`head`.`ourref` as `reference`,'posted' as `status` from ((((`apledger`
        left join `cntnum` on((`cntnum`.`trno` = `apledger`.`trno`))) left join `gldetail` as detail
        on(((`detail`.`trno` = `apledger`.`trno`) and (`detail`.`line` = `apledger`.`line`))))
        left join `glhead` as head on((`head`.`trno` = `cntnum`.`trno`)))) left join client on client.clientid = apledger.clientid where md5(apledger.clientid)='$clientid' and apledger.dateid>='$start'
        union all
        select `head`.`yourref`, `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
        `detail`.`db` as `db`,`detail`.`cr` as `cr`,round(abs((`detail`.`db` - `detail`.`cr`)),2) as `bal`,
        `client`.`clientid` as `clientid`,'' as `ref`,'' as `agent`,`detail`.`rem` as `rem`,
        abs((`detail`.`db` - `detail`.`cr`)) as `balance`,0 as `fbal`,'' as `reference`,'' as `status`
        from (((`lahead` as head left join `ladetail` as detail on((`detail`.`trno` = `head`.`trno`)))
        left join `client` on((`client`.`client` = `head`.`client`))) left join `coa` on((`coa`.`acnoid` = `detail`.`acnoid`)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)='$clientid' and head.dateid>='$start' and
        left(`coa`.`alias`,2) = 'ap'  
        union all
        select `head`.`yourref`, `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
        `detail`.`db` as `db`,`detail`.`cr` as `cr`,round(abs((`detail`.`db` - `detail`.`cr`)),2) as `bal`,
        `client`.`clientid` as `clientid`,'' as `ref`,'' as `agent`,`detail`.`rem` as `rem`,
        abs((`detail`.`db` - `detail`.`cr`)) as `balance`,0 as `fbal`,'' as `reference`,'' as `status` from
        (((`lbhead` as head left join `lbdetail` as detail on((`detail`.`trno` = `head`.`trno`))) left join `client`
        on((`client`.`client` = `head`.`client`))) left join `coa` on((`coa`.`acnoid` = `detail`.`acnoid`)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)='$clientid'  and head.dateid>='$start'
        and left(`coa`.`alias`,2) = 'ap' 
        union all
        select `head`.`yourref`, `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
        `detail`.`db` as `db`,`detail`.`cr` as `cr`,round(abs((`detail`.`db` - `detail`.`cr`)),2) as `bal`,
        `client`.`clientid` as `clientid`,'' as `ref`,'' as `agent`,`detail`.`rem` as `rem`,
        abs((`detail`.`db` - `detail`.`cr`)) as `balance`,0 as `fbal`,'' as `reference`,'' as `status`
        from (((`lchead` as head left join `lcdetail` as detail on((`detail`.`trno` = `head`.`trno`)))
        left join `client` on((`client`.`client` = `head`.`client`))) left join `coa` on((`coa`.`acnoid` = `detail`.`acnoid`)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)='$clientid' and head.dateid>='$start' and
        left(`coa`.`alias`,2) = 'ap' 
        union all
        select `head`.`yourref`, `cntnum`.`doc` as `doc`,apledger.`docno`,`apledger`.`trno` as `trno`,`apledger`.`line` as `line`,
        `apledger`.`dateid` as `dateid`,`apledger`.`db` as `db`,`apledger`.`cr` as `cr`,apledger.bal,
        `apledger`.`clientid` as `clientid`,`apledger`.`ref` as `ref`,'' as `agent`,(`detail`.`rem`) as `rem`,
        ((case when (`apledger`.`db` > 0) then 1 else -(1) end) * `apledger`.`bal`) as `balance`,0 as `fbal`,
        `head`.`ourref` as `reference`,'posted' as `status` from hglhead as head
        left join hgldetail as detail on detail.trno = head.trno left join `cntnum` on `cntnum`.`trno` = `head`.`trno`
        left join apledger on apledger.trno = detail.trno and apledger.line = detail.line left join client on client.clientid = apledger.clientid
        where  md5(apledger.clientid)='$clientid'  and apledger.dateid>='$start'
        ) as t left join client on client.clientid = t.clientid  order by dateid, docno";

        return $query;
    }

    public function default_PDC_QUERY($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select customerpdc.trno, customerpdc.doc, customerpdc.docno, customerpdc.checkno, customerpdc.checkdate, customerpdc.db, customerpdc.cr,ifnull(customerpdc.rem,'') as rem,client.client,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,client.email,client.contact,client.fax from (
            select glhead.doc,coa.alias,glhead.trno, glhead.docno, gldetail.checkno, gldetail.postdate as checkdate, gldetail.db,
            gldetail.cr,crledger.depodate,concat(`gldetail`.`rem`,'  ',`deposit`.`docno`) as rem,client.clientid
            from glhead left join gldetail on gldetail.trno=glhead.trno  left join crledger on crledger.trno=gldetail.trno
            left join client on client.clientid = gldetail.clientid left join coa on coa.acnoid=gldetail.acnoid left join deposit on deposit.refx = crledger.trno and deposit.linex = crledger.line where left(coa.alias,2)='cr' and crledger.depodate is null and glhead.doc='cr'
            union
            select lahead.doc,coa.alias,lahead.trno, lahead.docno, ladetail.checkno, ladetail.postdate, ladetail.db,
            ladetail.cr,null as depodate,ladetail.rem as rem,client.clientid
            from lahead left join ladetail on ladetail.trno=lahead.trno left join client on client.client = ladetail.client
            left join coa on coa.acnoid=ladetail.acnoid where left(coa.alias,2)='cr'
            ) as customerpdc left join client on client.clientid = customerpdc.clientid where md5(client.clientid) ='$clientid' and  checkdate>='$start'";

        return $query;
    }

    public function default_STOCK_QUERY($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "
        select * from (
        select `glhead`.`trno` as `trno`,`glhead`.`doc` as `doc`,`glhead`.`clientid` as `clientid`,
        `glhead`.`docno` as `docno`,`glhead`.`dateid` as `dateid`,
        `item`.`barcode` as `barcode`,`item`.`itemname` as `itemname`,`glstock`.`uom` as `uom`,
        `glstock`.`disc` as `disc`,`glstock`.`cost` as `cost`,`glstock`.`isamt` as `isamt`,
        `glstock`.`isqty` as `isqty`,`glstock`.`rrqty` as `rrqty`,
        client.client,client.clientname,client.addr,client.tel,client.tel2,client.email,client.tin,
        client.mobile,client.contact,client.rem,client.fax
        from `glstock` 
        left join `glhead` on`glstock`.`trno` = `glhead`.`trno`
        left join `item` on`item`.`itemid` = `glstock`.`itemid`
        left join client on client.clientid = glhead.clientid
        left join cntnum on cntnum.trno = glhead.trno
        where  md5(client.clientid) ='$clientid' and glhead.dateid>='$start' and cntnum.center ='$center'
        union all
        select `lahead`.`trno` as `trno`,`lahead`.`doc` as `doc`,`client`.`clientid` as `clientid`,
        `lahead`.`docno` as `docno`,`lahead`.`dateid` as `dateid`,`item`.`barcode` as `barcode`,
        `item`.`itemname` as `itemname`,`lastock`.`uom` as `uom`,`lastock`.`disc` as `disc`,
        `lastock`.`cost` as `cost`,`lastock`.`isamt` as `isamt`,`lastock`.`isqty` as `isqty`,lastock.rrqty as `rrqty`,
        client.client,client.clientname,client.addr,client.tel,client.tel2,client.email,client.tin,client.mobile,client.contact,client.rem,client.fax
        from `lastock` 
        left join `lahead` on`lastock`.`trno` = `lahead`.`trno`
        left join `item` on`item`.`itemid` = `lastock`.`itemid`
        left join `client` on`client`.`client` = `lahead`.`client`
        left join cntnum on cntnum.trno = lahead.trno
        where  md5(client.clientid) ='$clientid' and lahead.dateid>='$start' and cntnum.center ='$center'
        union all
        select `lbhead`.`trno` as `trno`,`lbhead`.`doc` as `doc`,`client`.`clientid` as `clientid`,
        `lbhead`.`docno` as `docno`,`lbhead`.`dateid` as `dateid`,`lbstock`.`barcode` as `barcode`,
        `lbstock`.`itemname` as `itemname`,`lbstock`.`uom` as `uom`,`lbstock`.`disc` as `disc`,
        `lbstock`.`cost` as `cost`,`lbstock`.`isamt` as `isamt`,`lbstock`.`isqty` as `isqty`,lbstock.rrqty as `rrqty`,
        client.client,client.clientname,client.addr,client.tel,client.tel2,client.email,client.tin,client.mobile,client.contact,client.rem,client.fax
        from `lbstock` 
        left join `lbhead` on`lbstock`.`trno` = `lbhead`.`trno`
        left join `client` on`client`.`client` = `lbhead`.`client`
        left join cntnum on cntnum.trno = lbhead.trno
        where  md5(client.clientid) ='$clientid' and lbhead.dateid>='$start' and cntnum.center ='$center'
        union all
        select `lchead`.`trno` as `trno`,`lchead`.`doc` as `doc`,`client`.`clientid` as `clientid`,
        `lchead`.`docno` as `docno`,`lchead`.`dateid` as `dateid`,`lcstock`.`barcode` as `barcode`,
        `lcstock`.`itemname` as `itemname`,`lcstock`.`uom` as `uom`,`lcstock`.`disc` as `disc`,
        `lcstock`.`cost` as `cost`,`lcstock`.`isamt` as `isamt`,`lcstock`.`isqty` as `isqty`,lcstock.rrqty as `rrqty`,
        client.client,client.clientname,client.addr,client.tel,client.tel2,client.email,client.tin,client.mobile,client.contact,client.rem,client.fax
        from `lcstock` 
        left join `lchead` on`lcstock`.`trno` = `lchead`.`trno`
        left join `client` on`client`.`client` = `lchead`.`client` 
        left join cntnum on cntnum.trno = lchead.trno
        where md5(client.clientid) ='$clientid' and lchead.dateid>='$start' and cntnum.center ='$center') as a
        group by
        trno, doc, clientid,
        docno, dateid,
        barcode, itemname, uom,
        disc, cost, isamt,
        isqty, rrqty,
        client,clientname,addr,tel,tel2,email,tin,
        mobile,contact,rem,fax";

        return $query;
    }

    //DEFAULT LAYOUT
    public function reportdefaultAR($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $str = '';
        $count = 55;
        $page = 54;
        $font =  "Verdana";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport();

        if ($companyid == 3) {
            $qry = "select name,address,tel from center where code = '" . $center . "'";
            $headerdata = $this->coreFunctions->opentable($qry);
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();

            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER LEDGER - ACCOUNTS RECEIVABLE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Report Type :', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(strtoupper($reporttype), '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('View Accounts from :', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col($start, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Agent:', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Telephone No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes ', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Debit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Credit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reference', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        foreach ($data as $key => $data1) {
            $credit = number_format($data1->cr, 2);
            $credit = $credit < 0 ? '-' : $credit;
            $debit = number_format($data1->db, 2);
            $debit = $debit < 0 ? '-' : $debit;
            $balance = number_format($data1->bal, 2);
            $balance = $balance < 0 ? '-' : $balance;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data1->docno, '100', null, false, $border, '', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->dateid, '100', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->rem, '100', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($debit, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($credit, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($balance, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->ref, '200', null, false, $border, '', 'C', $font, '9', '', '', '');
            $totaldb = $totaldb + $data1->db;
            $totalcr = $totalcr + $data1->cr;
            $totalbal = $totalbal + $data1->bal;
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col('Grand Total :', '100', null, false, '1px dotted ', 'T', 'C', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'C', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $str .= '<br><br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    public function reportdefaultAP($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $str = '';
        $count = 55;
        $page = 54;
        $font =  "Verdana";
        $fontsize = "11";
        $border = "1px solid ";
        $str .= $this->reporter->beginreport();

        if ($companyid == 3) {
            $qry = "select name,address,tel from center where code = '" . $center . "'";
            $headerdata = $this->coreFunctions->opentable($qry);
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();

            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER LEDGER - ACCOUNTS PAYABLE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Report Type :', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(strtoupper($reporttype), '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('View Accounts from :', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . $start, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Agent:', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Telephone No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes ', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Debit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Credit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reference', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        foreach ($data as $key => $data1) {
            $credit = number_format($data1->cr, 2);
            $credit = $credit < 0 ? '-' : $credit;
            $debit = number_format($data1->db, 2);
            $debit = $debit < 0 ? '-' : $debit;
            $balance = number_format($data1->bal, 2);
            $balance = $balance < 0 ? '-' : $balance;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data1->docno, '100', null, false, $border, '', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->dateid, '100', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->rem, '100', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($debit, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($credit, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($balance, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->ref, '200', null, false, $border, '', 'C', $font, '9', '', '', '');
            $totaldb = $totaldb + $data1->db;
            $totalcr = $totalcr + $data1->cr;
            $totalbal = $totalbal + $data1->bal;
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col('Grand Total :', '100', null, false, '1px dotted ', 'T', 'C', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'C', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $str .= '<br><br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By;', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    }

    public function reportdefaultPDC($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $str = '';
        $count = 55;
        $page = 54;
        $font =  "Verdana";
        $fontsize = "11";
        $border = "1px solid ";

        $str .= $this->reporter->beginreport();

        if ($companyid == 3) {
            $qry = "select name,address,tel from center where code = '" . $center . "'";
            $headerdata = $this->coreFunctions->opentable($qry);
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();

            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER LEDGER - POSTDATED CHECKS ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Report Type :', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(strtoupper($reporttype), '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('View Accounts from :', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col($start, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Agent:', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Telephone No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes ', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Debit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Credit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reference', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        foreach ($data as $key => $data1) {
            $credit = number_format($data1->cr, 2);
            $credit = $credit < 0 ? '-' : $credit;
            $debit = number_format($data1->db, 2);
            $debit = $debit < 0 ? '-' : $debit;
            $balance = number_format($data1->bal, 2);
            $balance = $balance < 0 ? '-' : $balance;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data1->docno, '100', null, false, $border, '', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->dateid, '100', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->rem, '100', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($debit, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($credit, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($balance, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->ref, '200', null, false, $border, '', 'C', $font, '9', '', '', '');
            $totaldb = $totaldb + $data1->db;
            $totalcr = $totalcr + $data1->cr;
            $totalbal = $totalbal + $data1->bal;
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col('Grand Total :', '100', null, false, '1px dotted ', 'T', 'C', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'C', $font, '9', 'B', '', '3px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $str .=  '<br><br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .=  '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportdefaultSTOCK($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $str = '';
        $count = 55;
        $page = 54;
        $font =  "Verdana";
        $fontsize = "11";
        $border = "1px solid ";
        $str .= $this->reporter->beginreport();


        if ($companyid == 3) {
            $qry = "select name,address,tel from center where code = '" . $center . "'";
            $headerdata = $this->coreFunctions->opentable($qry);
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();

            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } else {
            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->letterhead($center, $username);
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER LEDGER - INVENTORY ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Report Type :', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(strtoupper($reporttype), '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('View Accounts from :', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col($start, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Agent:', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Telephone No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Item Code', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Unit', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Discount ', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Unit Cost ', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Price', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('In', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Out', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        foreach ($data as $key => $data1) {
            $cost = number_format($data1->cost, 2);
            $cost = $cost < 0 ? '-' : $cost;
            $rrqty = number_format($data1->rrqty, 2);
            $rrqty = $rrqty < 0 ? '-' : $rrqty;
            $isqty = number_format($data1->isqty, 2);
            $isqty = $isqty < 0 ? '-' : $isqty;
            $rrcost = number_format($data1->cost, 2);
            $rrcost = $rrcost < 0 ? '-' : $rrcost;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data1->docno, '75', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->dateid, '75', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->barcode, '100', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->itemname, '200', null, false, $border, '', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->uom, '50', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($data1->disc, '50', null, false, $border, '', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->col($rrcost, '75', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($cost, '75', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($rrqty, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col($isqty, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
        }


        $str .= $this->reporter->endtable();


        $str .= '<br><br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($received, '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    //PDF Layout - not okay pa
    public function reportdefaultAR_PDF($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";






        if ($companyid == 8) {
            if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            }
        } else {
            if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
            }
        }


        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(20, 20);

        // if($companyid == 3){
        //     PDF::SetFont($font, '', 9);
        //     PDF::MultiCell(0, 0, $username.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s').'  '.strtoupper($headerdata[0]->name), '', 'L');


        //   }else
        //    {
        //     PDF::SetFont($font, '', 9);
        //     PDF::MultiCell(0, 0, $center.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s').'  '.$username, '', 'L');
        //   }

        switch ($companyid) {
            case 3:
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
                break;
            case 8:
                break;
            default:
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
                break;
        }

        // if ($companyid == 8) {
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");
        // } else {
        //     PDF::SetFont($fontbold, '', 12);
        //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        //     PDF::SetFont($fontbold, '', 11);
        //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
        // }

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "SUPPLIER LEDGER - ACCOUNTS RECEIVABLE", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(230, 20, "Agent : ", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(140, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(100, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(100, 10, "Balance", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(135, 10, "Reference", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;

        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        foreach ($data as $key => $data1) {
            $maxrow = 1;
            $docno = $data1->docno;
            $dateid = $data1->dateid;
            $rem = $data1->rem;
            $debit = number_format($data1->db, $decimalcurr);
            $credit = number_format($data1->cr, $decimalcurr);
            $balance = number_format($data1->bal, $decimalcurr);
            $ref = $data1->ref;
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;
            $balance = $balance < 0 ? '-' : $balance;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '16', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(140, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(135, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }

            // $credit = number_format($data1->cr, 2);
            // if ($credit < 1) {
            //     $credit = '-';
            // }
            // $debit = number_format($data1->db, 2);
            // if ($debit < 1) {
            //     $debit = '-';
            // }
            // $balance = number_format($data1->bal, 2);
            // if ($balance < 1) {
            //     $balance = '-';
            // }


            $totaldb = $totaldb + $data1->db;
            $totalcr = $totalcr + $data1->cr;
            $totalbal = $totalbal + $data1->bal;
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(140, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(135, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function reportdefaultAP_PDF($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";


        if ($companyid == 8) {
            if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            }
        } else {
            if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
            }
        }
        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(20, 20);

        switch ($companyid) {
            case 3:
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
                break;
            case 8:
                break;
            default:
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
                break;
        }
        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");
        // if ($companyid == 8) {
        //     $this->reportheader->getheader($config);
        // } else {
        //     PDF::MultiCell(0, 0, "\n");
        //     PDF::SetFont($fontbold, '', 12);
        //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        //     PDF::SetFont($fontbold, '', 11);
        //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
        // }

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "SUPPLIER LEDGER - ACCOUNTS PAYABLE", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(230, 20, "Agent : ", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(140, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(100, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(100, 10, "Balance", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(135, 10, "Reference", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");


        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;

        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        foreach ($data as $key => $data1) {
            $maxrow = 1;
            $docno = $data1->docno;
            $dateid = $data1->dateid;
            $rem = $data1->rem;
            $debit = number_format($data1->db, $decimalcurr);
            $credit = number_format($data1->cr, $decimalcurr);
            $balance = number_format($data1->bal, $decimalcurr);
            $ref = $data1->ref;
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;
            $balance = $balance < 0 ? '-' : $balance;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '16', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(140, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(135, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }

            // $credit = number_format($data1->cr, 2);
            // if ($credit < 1) {
            //     $credit = '-';
            // }
            // $debit = number_format($data1->db, 2);
            // if ($debit < 1) {
            //     $debit = '-';
            // }
            // $balance = number_format($data1->bal, 2);
            // if ($balance < 1) {
            //     $balance = '-';
            // }
            // $str .= $this->reporter->startrow();
            // $str .= $this->reporter->col($data1->docno, '100', null, false, $border, '', 'L', $font, '9', '', '', '');
            // $str .= $this->reporter->col($data1->dateid, '100', null, false, $border, '', 'C', $font, '9', '', '', '');
            // $str .= $this->reporter->col($data1->rem, '100', null, false, $border, '', 'C', $font, '9', '', '', '');
            // $str .= $this->reporter->col($debit, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col($credit, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col($balance, '100', null, false, $border, '', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col($data1->ref, '200', null, false, $border, '', 'C', $font, '9', '', '', '');

            $totaldb = $totaldb + $data1->db;
            $totalcr = $totalcr + $data1->cr;
            $totalbal = $totalbal + $data1->bal;
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(140, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(135, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function reportdefaultPDC_PDF($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";

        if ($companyid == 8) {

            if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            }
        } else {
            if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
            }
        }


        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(20, 20);

        switch ($companyid) {
            case 3:
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
                break;
            case 8:
                break;
            default:
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
                break;
        }
        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");
        // if ($companyid == 8) {
        //     $this->reportheader->getheader($config);
        // } else {
        //     PDF::MultiCell(0, 0, "\n");
        //     PDF::SetFont($fontbold, '', 12);
        //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        //     PDF::SetFont($font, '', 11);
        //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
        // }

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "SUPPLIER LEDGER - POSTDATED CHECKS", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(230, 20, "Agent : ", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(140, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(100, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(100, 10, "Balance", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(135, 10, "Reference", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;

        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        foreach ($data as $key => $data1) {
            $maxrow = 1;
            $docno = $data1->docno;
            $dateid = $data1->dateid;
            $rem = $data1->rem;
            $debit = number_format($data1->debit, $decimalcurr);
            $credit = number_format($data1->credit, $decimalcurr);
            $balance = number_format($data1->balance, $decimalcurr);
            $ref = $data1->ref;
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;
            $balance = $balance < 0 ? '-' : $balance;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '16', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(140, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(135, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }

            // $credit = number_format($data1->cr, 2);
            // if ($credit < 1) {
            //     $credit = '-';
            // }
            // $debit = number_format($data1->db, 2);
            // if ($debit < 1) {
            //     $debit = '-';
            // }
            // $balance = number_format($data1->bal, 2);
            // if ($balance < 1) {
            //     $balance = '-';
            // }


            $totaldb = $totaldb + $data1->db;
            $totalcr = $totalcr + $data1->cr;
            $totalbal = $totalbal + $data1->bal;
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(140, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(135, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function reportdefaultSTOCK_PDF($config, $data)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";

        if ($companyid == 8) {
            if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
            }
        } else {
            if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
                $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
                $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
            }
        }

        $qry = "select name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(20, 20);

        switch ($companyid) {
            case 3:
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
                break;
            case 8:
                break;
            default:
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
                break;
        }

        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");
        // if ($companyid == 8) {
        //     $this->reportheader->getheader($config);
        // } else {
        //     PDF::MultiCell(0, 0, "\n");
        //     PDF::SetFont($fontbold, '', 12);
        //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        //     PDF::SetFont($font, '', 11);
        //     PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
        // }

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "SUPPLIER LEDGER - INVENTORY", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(230, 20, "Agent : ", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 10);
        PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(110, 10, "Item Code", '', 'C', false, 0);
        PDF::MultiCell(160, 10, "Description", '', 'C', false, 0);
        PDF::MultiCell(50, 10, "Unit", '', 'C', false, 0);
        PDF::MultiCell(60, 10, "Discount", '', 'C', false, 0);
        PDF::MultiCell(80, 10, "Price", '', 'R', false, 0);
        PDF::MultiCell(60, 10, "In", '', 'R', false, 0);
        PDF::MultiCell(60, 10, "Out", '', 'R', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;

        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
        foreach ($data as $key => $data1) {
            $maxrow = 1;
            $docno = $data1->docno;
            $dateid = date('Y-m-d', strtotime($data1->dateid));
            $barcode = $data1->barcode;
            $itemname = $data1->itemname;
            $uom = $data1->uom;
            $disc = $data1->disc;
            $cost = number_format($data1->cost, $decimalcurr);
            $rrqty = number_format($data1->rrqty, $decimalqty);
            $isqty = number_format($data1->isqty, $decimalqty);
            $cost = $cost < 0 ? '-' : $cost;
            $rrqty = $rrqty < 0 ? '-' : $rrqty;
            $isqty = $isqty < 0 ? '-' : $isqty;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '16', 0);
            $arr_barcode = $this->reporter->fixcolumn([$barcode], '16', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
            $arr_cost = $this->reporter->fixcolumn([$cost], '13', 0);
            $arr_rrqty = $this->reporter->fixcolumn([$rrqty], '13', 0);
            $arr_isqty = $this->reporter->fixcolumn([$isqty], '13', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_barcode, $arr_itemname, $arr_uom, $arr_disc, $arr_cost, $arr_rrqty, $arr_isqty]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 15, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(160, 15, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(50, 15, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(60, 15, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(80, 15, (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(60, 15, (isset($arr_rrqty[$r]) ? $arr_rrqty[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(60, 15, (isset($arr_isqty[$r]) ? $arr_isqty[$r] : ''), '', 'R', 0, 1, '', '', true, 0, false, false);
            }

            // $cost = number_format($data1->cost, 2);
            // if ($cost < 1) {
            //     $cost = '-';
            // }
            // $rrqty = number_format($data1->rrqty, 2);
            // if ($rrqty < 1) {
            //     $rrqty = '-';
            // }
            // $isqty = number_format($data1->isqty, 2);
            // if ($isqty < 1) {
            //     $isqty = '-';
            // }
            // $rrcost = number_format($data1->cost, 2);
            // if ($rrcost < 1) {
            //     $rrcost = '-';
            // }

        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n\n");
        PDF::MultiCell(0, 0, "\n\n\n\n");
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        PDF::MultiCell(0, 0, "\n\n");
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
