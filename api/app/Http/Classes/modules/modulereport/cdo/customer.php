<?php

namespace App\Http\Classes\modules\modulereport\cdo;

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
use DateTime;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class customer
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
        $fields = ['radioprint', 'start', 'radiocustreporttype', 'sjdocno', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
        ]);

        if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti
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
            '' as sjdocno,
            0 as clientid,
            0 as trno,
            'ar' as reporttype,
            '' as approved,
            '' as received";

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
            case 'rc':
                $query = $this->default_RC_QUERY($config);
                break;
            case 'stock':
                $query = $this->default_STOCK_QUERY($config);
                break;
        }

        return $this->coreFunctions->opentable($query);
    }

    public function reportplotting($config, $data)
    {
        ini_set('memory_limit', '-1');
        $data = $this->generateResult($config);
        $reporttype = $config['params']['dataparams']['reporttype'];

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
            case 'rc':
                $str = $this->reportdefaultRC_PDF($config, $data);
                break;
            case 'stock':
                $str = $this->reportdefaultSTOCK_PDF($config, $data);
                break;
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

        $trno = $config['params']['dataparams']['trno'];

        switch ($reporttype) {
            case 'ar':
                $query = "select client.clientname, head.ourref as csi, head.yourref as dr,
                client.tel2 as contactno, head.address, terms.terms, terms.days, ifnull(hinfo.interestrate, 0) as interestrate,
                ifnull(hinfo.fmiscfee, 0) as miscfee, ifnull(hinfo.fma1, 0) as ma, ifnull(hinfo.penalty, 0) as penalty,
                stock.amt as srp, ifnull(hinfo.rebate, 0) as rebate, '' as due, head.dateid as hdate,
                ifnull((hinfo.fma1 + hinfo.rebate), 0) as current,  head.creditinfo as ci, concat(ifnull(item.itemname, ''), ifnull(sot.serial, ''), ifnull(sot.chassis, ''), 
                ifnull(sot.color, '')) as model,  mode.name as modeofsales,head.crref as cr, ifnull(hinfo.downpayment, 0) as downpayment,ifnull(hinfo.fmiscfee, 0) as miscfee,
                (select sum(stock.ext) as ext from glstock as stock where stock.trno = head.trno) as amt
                from lahead as head
                left join lastock as stock on stock.trno = head.trno
                left join terms on terms.terms = head.terms
                left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
                left join item on item.itemid = stock.itemid
                left join client on client.client = head.client
                left join cntnuminfo as hinfo on hinfo.trno = head.trno
                left join mode_masterfile as mode on mode.line = head.modeofsales  
                where md5(client.clientid) = '$clientid' and head.trno = $trno
                union all
                select client.clientname, head.ourref as csi, head.yourref as dr, client.tel2 as contactno,
                head.address, terms.terms, terms.days, ifnull(hinfo.interestrate, 0) as interestrate,
                ifnull(hinfo.fmiscfee, 0) as miscfee, ifnull(hinfo.fma1, 0) as ma, ifnull(hinfo.penalty, 0) as penalty,
                stock.amt as srp, ifnull(hinfo.rebate, 0) as rebate,
                (select date_format(ar.dateid, '%M %d, %Y') as dateid from arledger as ar left join coa on coa.acnoid = ar.acnoid where head.trno = ar.trno and coa.alias in ('AR1','AR2') limit 1) as due,  head.dateid as hdate,
                ifnull((hinfo.fma1 + hinfo.rebate), 0) as current, head.creditinfo as ci, concat(ifnull(item.itemname, ''), ifnull(sot.serial, ''), ifnull(sot.chassis, ''), 
                ifnull(sot.color, '')) as model,  mode.name as modeofsales,head.crref as cr, ifnull(hinfo.downpayment, 0) as downpayment,ifnull(hinfo.fmiscfee, 0) as miscfee,
                (select sum(stock.ext) as ext from glstock as stock where stock.trno = head.trno) as amt
                from glhead as head
                left join glstock as stock on stock.trno = head.trno
                left join terms on terms.terms = head.terms
                left join client on client.clientid = head.clientid
                left join cntnum as num on num.trno = head.trno
                left join hcntnuminfo as hinfo on hinfo.trno = head.trno
                left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
                left join item on item.itemid = stock.itemid
                left join mode_masterfile as mode on mode.line = head.modeofsales  
                where md5(client.clientid) = '$clientid' and head.trno = $trno and num.refrecon =0
                union all
                select client.clientname, shj.ourref as csi, shj.yourref as dr, client.tel2 as contactno,
                head.address, terms.terms, terms.days, ifnull(hinfo.interestrate, 0) as interestrate,
                ifnull(hinfo.fmiscfee, 0) as miscfee, ifnull(hinfo.fma1, 0) as ma, ifnull(sjinfo.penalty, 0) as penalty,
                stock.amt as srp, ifnull(sjinfo.rebate, 0) as rebate,
                (select date_format(ar.dateid, '%M %d, %Y') as dateid from arledger as ar left join coa on coa.acnoid = ar.acnoid where head.trno = ar.trno and coa.alias in ('AR1','AR2') limit 1) as due,  shj.dateid as hdate,
                ifnull((hinfo.fma1 + sjinfo.rebate), 0) as current, head.creditinfo as ci, concat(ifnull(item.itemname, ''), ifnull(sot.serial, ''), ifnull(sot.chassis, ''),
                ifnull(sot.color, '')) as model,  mode.name as modeofsales,head.crref as cr, ifnull(sjinfo.downpayment, 0) as downpayment,ifnull(sjinfo.fmiscfee, 0) as miscfee,
                (select sum(stock.ext) as ext from glstock as stock where stock.trno = shj.trno) as amt
                from glhead as head
                left join terms on terms.terms = head.terms
                left join client on client.clientid = head.clientid
                left join cntnum as num on num.trno = head.trno
                left join hcntnuminfo as hinfo on hinfo.trno = head.trno
                left join glhead as shj on shj.trno = num.recontrno
                left join glstock as stock on stock.trno = shj.trno
                left join hcntnuminfo as sjinfo on sjinfo.trno = num.recontrno
                left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
                left join item on item.itemid = stock.itemid
                left join mode_masterfile as mode on mode.line = shj.modeofsales
                where md5(client.clientid) = '$clientid' and num.recontrno = $trno ";
                break;

            default:
                $query = "select t.trno, t.line, t.doc, t.docno, date_format(t.dateid,'%m/%d/%y') as dateid, t.db, t.cr, t.bal, t.ref,
        t.agent, t.rem, t.status,client.client,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,
        client.email,client.contact,client.fax,t.dateid as dateid2 from
        (
        select cntnum.doc as doc,arledger.docno,arledger.trno as trno,
        arledger.line as line,arledger.dateid as dateid,arledger.db as db,
        arledger.cr as cr,arledger.bal,
        arledger.clientid as clientid,arledger.ref as ref,agent.client as agent,
        (detail.rem) as rem,((case when (arledger.db > 0) then 1 else -(1) end) * arledger.bal) as balance,
        0 as fbal,head.ourref as reference,'posted' as status from ((((arledger
        left join cntnum on((cntnum.trno = arledger.trno))) left join gldetail as detail
        on(((detail.trno = arledger.trno) and (detail.line = arledger.line))))
        left join glhead as head on((head.trno = cntnum.trno))) left join client agent
        on((agent.clientid = arledger.agentid))) left join client on client.clientid = arledger.clientid where md5(arledger.clientid)= '$clientid'  and arledger.dateid>='$start'
        and cntnum.center = '$center'
        union all
        select head.doc as doc,head.docno,head.trno as trno,detail.line as line,head.dateid as dateid,
        detail.db as db,detail.cr as cr,round(abs((detail.db - detail.cr)),2) as bal,
        client.clientid as clientid,'' as ref,'' as agent,detail.rem as rem,
        abs((detail.db - detail.cr)) as balance,0 as fbal,'' as reference,'' as status
        from (((lahead as head left join ladetail as detail on((detail.trno = head.trno)))
        left join client on((client.client = head.client))) left join coa on((coa.acnoid = detail.acnoid)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)= '$clientid'  and head.dateid>='$start' and
        left(coa.alias,2) = 'ar'  and cntnum.center = '$center'
        union all
        select cntnum.doc as doc,arledger.docno,arledger.trno as trno,arledger.line as line,
        arledger.dateid as dateid,arledger.db as db,arledger.cr as cr,arledger.bal,
        arledger.clientid as clientid,arledger.ref as ref,agent.client as agent,(detail.rem) as rem,
        ((case when (arledger.db > 0) then 1 else -(1) end) * arledger.bal) as balance,0 as fbal,
        head.ourref as reference,'posted' as status from hglhead as head
        left join hgldetail as detail on detail.trno = head.trno left join cntnum on cntnum.trno = head.trno
        left join arledger on arledger.trno = detail.trno and arledger.line = detail.line
        left join client agent on agent.clientid = arledger.agentid left join client on client.clientid = arledger.clientid where
        md5(arledger.clientid)= '$clientid'  and arledger.dateid>='$start'  and cntnum.center = '$center'
        ) as t left join client on client.clientid = t.clientid
        order by dateid2, docno";
                break;
        }

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

        $query = "select t.trno, t.line, t.doc, t.docno, date_format(t.dateid,'%m/%d/%y') as dateid, t.db, t.cr, t.bal, t.ref, t.rem, t.status,
        client.client,client.agent,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,client.email,client.contact,client.fax from
        (select cntnum.doc as doc,apledger.docno,apledger.trno as trno,apledger.line as line,
        apledger.dateid as dateid,apledger.db as db,apledger.cr as cr,apledger.bal,
        apledger.clientid as clientid,apledger.ref as ref,'' as agent,
        (detail.rem) as rem,((case when (apledger.db > 0) then 1 else -(1) end) * apledger.bal) as balance,
        0 as fbal,head.ourref as reference,'posted' as status from ((((apledger
        left join cntnum on((cntnum.trno = apledger.trno))) left join gldetail as detail
        on(((detail.trno = apledger.trno) and (detail.line = apledger.line))))
        left join glhead as head on((head.trno = cntnum.trno)))) left join client on client.clientid = apledger.clientid where md5(apledger.clientid)='$clientid' and apledger.dateid>='$start'
        and cntnum.center='$center'
        union all
        select head.doc as doc,head.docno,head.trno as trno,detail.line as line,head.dateid as dateid,
        detail.db as db,detail.cr as cr,round(abs((detail.db - detail.cr)),2) as bal,
        client.clientid as clientid,'' as ref,'' as agent,detail.rem as rem,
        abs((detail.db - detail.cr)) as balance,0 as fbal,'' as reference,'' as status
        from (((lahead as head left join ladetail as detail on((detail.trno = head.trno)))
        left join client on((client.client = head.client))) left join coa on((coa.acnoid = detail.acnoid)))
        left join cntnum on cntnum.trno = head.trno where md5(client.clientid)='$clientid' and head.dateid>='$start' and
        left(coa.alias,2) = 'ap'  and md5(cntnum.center) = '$center'
        union all
        select cntnum.doc as doc,apledger.docno,apledger.trno as trno,apledger.line as line,
        apledger.dateid as dateid,apledger.db as db,apledger.cr as cr,apledger.bal,
        apledger.clientid as clientid,apledger.ref as ref,'' as agent,(detail.rem) as rem,
        ((case when (apledger.db > 0) then 1 else -(1) end) * apledger.bal) as balance,0 as fbal,
        head.ourref as reference,'posted' as status from hglhead as head
        left join hgldetail as detail on detail.trno = head.trno left join cntnum on cntnum.trno = head.trno
        left join apledger on apledger.trno = detail.trno and apledger.line = detail.line left join client on client.clientid = apledger.clientid
        where  md5(apledger.clientid)='$clientid'  and apledger.dateid>='$start'  and md5(cntnum.center) = '$center'
        ) as t left join client on client.clientid = t.clientid  order by dateid, docno";

        return $query;
    }

    public function default_PDC_QUERY($config)
    {
        ini_set('max_execution_time', 0);
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select customerpdc.trno, customerpdc.doc, customerpdc.docno, customerpdc.checkno, customerpdc.checkdate, customerpdc.db,
        customerpdc.cr,ifnull(customerpdc.rem,'') as rem,client.client,client.clientname,client.addr,client.tel,client.tel2,client.tin,client.mobile,client.email,client.contact,client.fax, customerpdc.ref, customerpdc.agentname
        from (
        select glhead.doc,coa.alias,glhead.trno, glhead.docno, gldetail.checkno, gldetail.postdate as checkdate, gldetail.db,
        gldetail.cr,crledger.depodate,concat(gldetail.rem,'  ',deposit.docno) as rem,client.clientid, gldetail.ref,
        agent.clientname as agentname
        from glhead
        left join gldetail on gldetail.trno=glhead.trno
        left join crledger on crledger.trno=gldetail.trno
        left join client on client.clientid = gldetail.clientid
        left join coa on coa.acnoid=gldetail.acnoid
        left join client as agent on glhead.agentid = client.clientid
        left join deposit on deposit.refx = crledger.trno and deposit.linex = crledger.line where left(coa.alias,2)='cr' and crledger.depodate is null and glhead.doc='cr'
        union all
        select lahead.doc,coa.alias,lahead.trno, lahead.docno, ladetail.checkno, ladetail.postdate, ladetail.db,
        ladetail.cr,null as depodate,ladetail.rem as rem,client.clientid, ladetail.ref,
        agent.clientname as agentname
        from lahead
        left join ladetail on ladetail.trno=lahead.trno
        left join client on client.client = ladetail.client
        left join client as agent on agent.client = lahead.agent
        left join coa on coa.acnoid=ladetail.acnoid where
        left(coa.alias,2)='cr'
        ) as customerpdc
        left join client on client.clientid = customerpdc.clientid where md5(client.clientid) ='$clientid' and  checkdate>='$start'";

        return $query;
    }

    public function default_RC_QUERY($config)
    {
        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = md5($config['params']['dataid']);

        $reporttype = $config['params']['dataparams']['reporttype'];
        $prepared   = $config['params']['dataparams']['prepared'];
        $approved   = $config['params']['dataparams']['approved'];
        $received   = $config['params']['dataparams']['received'];
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

        $query = "select cntnum.doc as doc,arledger.docno as docno,arledger.trno as trno,arledger.line as line,
        arledger.dateid as dateid,arledger.db as db,
        arledger.cr as cr,(case when (arledger.bal = 0) then 'applied' else ltrim(arledger.bal) end) as bal,arledger.clientid as clientid,
        arledger.ref as ref,agent.client as agent,gldetail.rem as rem,
        arledger.bal as balance
        from arledger
        left join glhead on arledger.trno = glhead.trno
        left join cntnum on cntnum.trno = arledger.trno
        left join coa on coa.acnoid = arledger.acnoid
        left join gldetail on gldetail.trno = arledger.trno
        and gldetail.line = arledger.line
        left join client as agent on agent.clientid = arledger.agentid
        where coa.alias = 'arb'
        and md5(arledger.clientid)= '$clientid'
        and glhead.dateid>='$start'
        and cntnum.center = '$center'
        union all
        select lahead.doc as doc,lahead.docno as docno,lahead.trno as trno,ladetail.line as line,lahead.dateid as dateid,ladetail.db as db,
        ladetail.cr as cr,abs((ladetail.db - ladetail.cr)) as bal,client.clientid as clientid,ladetail.ref as ref,'' as agent,
        ladetail.rem as rem,abs((ladetail.db - ladetail.cr)) as balance
        from lahead
        left join ladetail on ladetail.trno = lahead.trno
        left join client on client.client = ladetail.client
        left join coa on coa.acnoid = ladetail.acnoid
        left join cntnum on cntnum.trno = lahead.trno
        where coa.alias = 'arb'
        and md5(client.clientid)= '$clientid'
        and lahead.dateid>='$start'
        and cntnum.center = '$center'";

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

        $query = "select glhead.trno as trno,
        concat(cntnum.bref,cntnum.seq) as docno,
        glhead.dateid as dateid,item.itemname,
        glstock.uom as uom,
        glstock.disc as disc,glstock.isamt as cost,
        glstock.isqty as isqty,glstock.rrqty as rrqty,
        client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.contact,client.fax, item.barcode
        from glstock
        left join glhead on glstock.trno = glhead.trno
        left join item on item.itemid = glstock.itemid
        left join client on client.clientid = glhead.clientid
        left join cntnum on cntnum.trno = glhead.trno
        where md5(client.clientid) ='$clientid'
        and glhead.dateid>='$start'
        and cntnum.center ='$center'
        union all
        select lahead.trno as trno,
        concat(cntnum.bref,cntnum.seq) as docno,
        lahead.dateid as dateid,
        item.itemname as itemname,lastock.uom as uom,lastock.disc as disc,
        lastock.isamt as cost,lastock.isqty as isqty,lastock.rrqty as rrqty,
        client.client,client.clientname,client.addr,client.tel,client.email,client.tin,client.contact,client.fax, item.barcode
        from lastock
        left join lahead on lastock.trno = lahead.trno
        left join item on item.itemid=lastock.itemid
        left join client on client.client = lahead.client
        left join cntnum on cntnum.trno = lahead.trno
        where  md5(client.clientid) ='$clientid'
        and lahead.dateid>='$start'
        and cntnum.center ='$center'
        group by lahead.trno,
        cntnum.bref,cntnum.seq,lahead.dateid,
        item.itemname,lastock.uom,lastock.disc,
        lastock.isamt,lastock.isqty,lastock.rrqty,
        client.client,client.clientname,client.addr,
        client.tel,client.email,client.tin,
        client.contact,client.fax, item.barcode";

        return $query;
    }

    //DEFAULT LAYOUT
    //PDF Layout - not okay pa
    // public function reportdefaultAR_PDF($config, $data)
    // {
    //     $center   = $config['params']['center'];
    //     $username = $config['params']['user'];
    //     $clientid = $config['params']['dataid'];
    //     $companyid = $config['params']['companyid'];


    //     $reporttype = $config['params']['dataparams']['reporttype'];
    //     $prepared   = $config['params']['dataparams']['prepared'];
    //     $approved   = $config['params']['dataparams']['approved'];
    //     $received   = $config['params']['dataparams']['received'];
    //     $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));

    //     $count = 55;
    //     $page = 54;
    //     $fontsize = "11";
    //     $font = "";
    //     $fontbold = "";


    //     if ($companyid == 8) {

    //         if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
    //             $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
    //             $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    //         }
    //     } else {
    //         if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
    //             $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
    //             $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
    //         }
    //     }

    //     $qry = "select name,address,tel from center where code = '" . $center . "'";
    //     $headerdata = $this->coreFunctions->opentable($qry);
    //     $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    //     PDF::SetTitle($this->modulename);
    //     PDF::SetAuthor('Solutionbase Corp.');
    //     PDF::SetCreator('Solutionbase Corp.');
    //     PDF::SetSubject($this->modulename . ' Module Report');
    //     PDF::setPageUnit('px');
    //     PDF::AddPage('p', [800, 1000]);
    //     PDF::SetMargins(20, 20);




    //     PDF::SetFont($font, '', 9);
    //     PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');


    //     PDF::MultiCell(0, 0, "\n");
    //     $this->reportheader->getheader($config);
    //     PDF::MultiCell(0, 0, "\n");


    //     PDF::SetFont($fontbold, '', 15);
    //     PDF::MultiCell(760, 30, "CUSTOMER LEDGER - ACCOUNTS RECEIVABLE", '', 'L', false);

    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(230, 20, "Agent : ", '', 'L', false);

    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(100, 20, "Customer : ", '', 'L', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(280, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'R', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(250, 20, (isset($data[0]->tel) ? $data[0]->tel : ''), '', 'L', false);

    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(100, 20, "Address : ", '', 'L', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(280, 20, (isset($data[0]->addr) ? $data[0]->addr : ''), '', 'L', false, 1);

    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(100, 20, "TIN # : ", '', 'L', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(280, 20, (isset($data[0]->tin) ? $data[0]->tin : ''), '', 'L', false, 0);
    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(130, 20, "Fax No/s : ", '', 'R', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'R', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(100, 20, "", '', 'L', false, 0);
    //     PDF::MultiCell(280, 20, '', '', 'L', false, 0);
    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(130, 20, "Contact Person : ", '', 'R', false, 0);
    //     PDF::SetFont($fontbold, '', 11);
    //     PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

    //     PDF::SetFont($font, '', 11);
    //     PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

    //     PDF::SetFont($font, '', 5);
    //     PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    //     PDF::MultiCell(60, 0, "", 'T', 'L', false);

    //     PDF::SetFont($fontbold, '', 10);

    //     PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
    //     PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
    //     PDF::MultiCell(110, 10, "Agent", '', 'C', false, 0);
    //     PDF::MultiCell(100, 10, "Notes", '', 'C', false, 0);
    //     PDF::MultiCell(90, 10, "Debit", '', 'R', false, 0);
    //     PDF::MultiCell(90, 10, "Credit", '', 'R', false, 0);
    //     PDF::MultiCell(90, 10, "Balance", '', 'R', false, 0);
    //     PDF::MultiCell(5, 10, "", '', 'R', false, 0);
    //     PDF::MultiCell(95, 10, "Reference", '', 'C', false);


    //     PDF::SetFont($font, '', 5);
    //     PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    //     PDF::MultiCell(60, 0, "", 'B', 'L', false);

    //     PDF::MultiCell(0, 0, "\n");

    //     $totaldb = 0;
    //     $totalcr = 0;
    //     $totalbal = 0;
    //     $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    //     foreach ($data as $key => $data) {
    //         $maxrow = 1;
    //         $docno = $data->docno;
    //         $dateid = $data->dateid;
    //         $agent = $data->agent;
    //         $rem = $data->rem;
    //         $debit = number_format($data->db, $decimalcurr);
    //         $credit = number_format($data->cr, $decimalcurr);
    //         $balance = number_format($data->bal, $decimalcurr);
    //         $debit = $debit < 0 ? '-' : $debit;
    //         $credit = $credit < 0 ? '-' : $credit;
    //         if ($data->cr != 0) {
    //             $balance = $balance < 0 ? '-' : $balance * -1;
    //         }
    //         $ref = $data->ref;

    //         if ($companyid == 19) {
    //             $client = $data->client . ' ' . $data->clientname;
    //             $doc = $data->doc;

    //             $arr_client = $this->reporter->fixcolumn([$client], '30', 0);
    //             $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
    //             $arr_docno = $this->reporter->fixcolumn([$docno], '25', 0);
    //             $arr_doc = $this->reporter->fixcolumn([$doc], '15', 0);
    //             $arr_debit = $this->reporter->fixcolumn([$debit], '15', 0);
    //             $arr_credit = $this->reporter->fixcolumn([$credit], '15', 0);
    //             $arr_balance = $this->reporter->fixcolumn([$balance], '15', 0);

    //             $maxrow = $this->othersClass->getmaxcolumn([$arr_client, $arr_dateid, $arr_docno, $arr_doc, $arr_debit, $arr_credit, $arr_balance]);

    //             for ($r = 0; $r < $maxrow; $r++) {
    //                 PDF::SetFont($font, '', $fontsize);
    //                 PDF::MultiCell(170, 15, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(75, 15, (isset($arr_doc[$r]) ? $arr_doc[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(95, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(95, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(105, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 1, '', '', true, 0, true, false);
    //             }
    //         } else {
    //             $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
    //             $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
    //             $arr_agent = $this->reporter->fixcolumn([$agent], '16', 0);
    //             $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
    //             $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
    //             $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
    //             $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
    //             $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

    //             $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_agent, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref]);

    //             for ($r = 0; $r < $maxrow; $r++) {
    //                 PDF::SetFont($font, '', $fontsize);
    //                 PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(110, 15, (isset($arr_agent[$r]) ? $arr_agent[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(100, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(90, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(90, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(90, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
    //                 PDF::MultiCell(95, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
    //             }
    //         }


    //         $totaldb = $totaldb + $data->db;
    //         $totalcr = $totalcr + $data->cr;
    //         $totalbal = $totalbal + $data->bal;
    //     }

    //     PDF::SetFont($font, '', 5);
    //     PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    //     PDF::MultiCell(60, 0, "", 'B', 'L', false);

    //     PDF::MultiCell(0, 0, "\n");
    //     PDF::SetFont($fontbold, '', $fontsize);
    //     if ($companyid == 19) {
    //         PDF::MultiCell(170, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(100, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(85, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(95, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(95, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(105, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
    //     } else {
    //         PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(90, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
    //         PDF::MultiCell(95, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);
    //     }

    //     PDF::MultiCell(0, 0, "\n\n\n\n");
    //     PDF::SetFont($font, '', $fontsize);
    //     PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    //     PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    //     PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    //     PDF::MultiCell(0, 0, "\n\n");
    //     PDF::SetFont($fontbold, '', $fontsize);
    //     PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
    //     PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
    //     PDF::MultiCell(254, 0, $approved, '', 'L');

    //     return PDF::Output($this->modulename . '.pdf', 'S');
    // }


    public function getdetail($config, $data2)
    {
        $trno = $config['params']['dataparams']['trno'];

        if (!empty($data2)) {
            foreach ($data2 as $key => $data) {
            }
        }
        $reporttype = $config['params']['dataparams']['reporttype'];

        if ($data->modeofsales != 'INHOUSE INSTALLMENT') {
            $qry2 = "select '' as rem, '' as postdate, '0' as interest, sum(stock.ext) as principal, '' as docno,'' as crno
            from lahead as head 
            left join lastock as stock on stock.trno = head.trno 
            where head.trno= $trno
            having sum(stock.ext) > 0
            union all 
            select '' as rem, date(cn.postdate) as postdate, '0' as interest, sum(stock.ext) as principal, 
            ifnull((select group_concat(distinct docno separator ', ') from
            (select h.docno, d.refx, d.linex
            from gldetail as d
            left join glhead as h on h.trno = d.trno
            where h.doc = 'CR'
            union all
            select h.docno, d.refx, d.linex
            from ladetail as d
            left join lahead as h on h.trno = d.trno
            where h.doc = 'CR') as a
            where refx = stock.trno and linex = stock.line), '') as docno,
            ifnull((select yourref from (
            select h.yourref, d.refx, d.linex
            from gldetail as d
            left join glhead as h on h.trno = d.trno
            where h.doc = 'CR'
            union all
            select h.yourref, d.refx, d.linex
            from ladetail as d
            left join lahead as h on h.trno = d.trno
            where h.doc = 'CR' ) as a where a.refx = detail.trno and a.linex = detail.line limit 1 ), ''  ) as crno
            from glhead as head 
            left join glstock as stock on stock.trno = head.trno 
            left join gldetail as detail on detail.trno = head.trno
            left join cntnum as cn on cn.trno=head.trno
            where head.trno= $trno
            group by postdate,docno,crno,detail.trno,detail.line";
        } else { //installment 
            if ($reporttype == 'ar') { //for customer
                $qry2 = "
                    SELECT rem, postdate,trno,interest,principal
                    FROM (SELECT group_concat(distinct detail.rem ) as rem,CASE WHEN coa.alias IN ('AR1', 'AR2') THEN DATE(detail.postdate) END AS postdate,
                    head.trno,sum(i.interest) as interest,sum(i.principal) as principal
                    FROM  glhead AS head  LEFT JOIN gldetail AS detail ON detail.trno = head.trno LEFT JOIN coa ON coa.acnoid = detail.acnoid
                    LEFT JOIN cntnum AS cn ON cn.trno = head.trno
                    left join hdetailinfo as i on i.trno = detail.trno and i.line = detail.line
                    WHERE coa.alias IN ('AR1', 'AR2') AND head.trno = $trno and cn.refrecon=0
                    group by postdate,trno 
                    union all
                    SELECT group_concat(distinct detail.rem ) as rem, CASE WHEN coa.alias IN ('AR1', 'AR2') THEN DATE(detail.postdate) END AS postdate,
                    head.trno,sum(i.interest) as interest,sum(i.principal) as principal
                    FROM glhead AS head LEFT JOIN gldetail AS detail ON detail.trno = head.trno
                    LEFT JOIN coa ON coa.acnoid = detail.acnoid LEFT JOIN cntnum AS cn ON cn.trno = head.trno
                    left join hdetailinfo as i on i.trno = detail.trno and i.line = detail.line
                    WHERE coa.alias IN ('AR1', 'AR2') AND cn.recontrno = $trno and detail.refx=0 group by postdate,trno 
                    ) AS a group by rem,postdate,trno,interest,principal order by postdate";
            } else { //default
                $qry2 = "
                SELECT rem, postdate,trno,interest,principal
                FROM (
                    SELECT
                        group_concat(distinct detail.rem ) as rem,
                        CASE WHEN coa.alias IN ('AR1', 'AR2') THEN DATE(detail.postdate) END AS postdate,head.trno,sum(i.interest) as interest,sum(i.principal) as principal
                    FROM
                        glhead AS head
                        LEFT JOIN gldetail AS detail ON detail.trno = head.trno
                        LEFT JOIN coa ON coa.acnoid = detail.acnoid
                        LEFT JOIN cntnum AS cn ON cn.trno = head.trno
                        left join hdetailinfo as i on i.trno = detail.trno and i.line = detail.line
                    WHERE
                        coa.alias IN ('AR1', 'AR2') AND cn.recontrno = 210 and detail.refx=0
                        group by postdate,trno ) AS a
                   GROUP BY postdate,rem,trno,interest,principal";
            }
        }

        
        $result2 = json_decode(json_encode($this->coreFunctions->opentable($qry2)), true);
        return $result2;
    }

    public function getdetail2($config)
    {
        $trno = $config['params']['dataparams']['trno'];
        // $data3 = $this->report_default_query($config);
        if (!empty($data3)) {
            foreach ($data3 as $key => $data) {
            }
        }

        //default 
        if ($data['modeofsales'] == 'INHOUSE INSTALLMENT') {
            $qry3 = "
      
      select rem, postdate,trno  from (
          select detail.rem as rem, head.dateid as postdate,head.trno
                  from lahead as head
                  left join ladetail as detail on detail.trno = head.trno
                  left join cntnum as cn on cn.trno=head.trno
                  where  head.doc = 'GJ' and detail.refx=0 and cn.recontrno= $trno 
                  group by head.dateid,detail.rem,head.trno) as a
           group by postdate,rem,trno
        
        union all
        select group_concat(distinct rem) as rem, postdate,trno
        from (
        select case when coa.alias in ('AR1', 'AR2') then detail.rem end as rem,
        case when coa.alias in ('AR1', 'AR2') then date(detail.postdate) end as postdate,head.trno
      
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        left join cntnum as cn on cn.trno=head.trno 
        where  coa.alias in ('AR1', 'AR2') and  head.doc = 'GJ' and detail.refx=0 and cn.recontrno= $trno ) as a
       
      group by postdate,trno";
        } else { //other mode
            $qry3 = "select '' as rem, '' as postdate, '0' as interest, sum(stock.ext) as principal, '' as docno,'' as crno
        from lahead as head 
        left join lastock as stock on stock.trno = head.trno 
        where head.trno= $trno
        having sum(stock.ext) > 0
        union all 
        select '' as rem, date(cn.postdate) as postdate, '0' as interest, sum(stock.ext) as principal, 
        ifnull((select group_concat(distinct docno separator ', ') from
        (select h.docno, d.refx, d.linex
        from gldetail as d
        left join glhead as h on h.trno = d.trno
        where h.doc = 'CR'
        union all
        select h.docno, d.refx, d.linex
        from ladetail as d
        left join lahead as h on h.trno = d.trno
        where h.doc = 'CR') as a
        where refx = stock.trno and linex = stock.line), '') as docno,
        ifnull((select yourref from (
          select h.yourref, d.refx, d.linex
          from gldetail as d
          left join glhead as h on h.trno = d.trno
          where h.doc = 'CR'
          union all
          select h.yourref, d.refx, d.linex
          from ladetail as d
          left join lahead as h on h.trno = d.trno
          where h.doc = 'CR' ) as a where a.refx = detail.trno and a.linex = detail.line limit 1 ), ''  ) as crno
        from glhead as head 
        left join glstock as stock on stock.trno = head.trno 
        left join gldetail as detail on detail.trno = head.trno
        left join cntnum as cn on cn.trno=head.trno
        where head.trno= $trno
        group by postdate,docno,crno,detail.trno,detail.line";
        }

        $result3 = json_decode(json_encode($this->coreFunctions->opentable($qry3)), true);
        return $result3;
    }

    public function PDF_customer($config, $data)
    {


        $center   = $config['params']['center'];
        $username = $config['params']['user'];
        $clientid = $config['params']['dataid'];
        $companyid = $config['params']['companyid'];
        $data2 = $this->getdetail($config, $data);
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

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');


        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");


        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - ACCOUNTS RECEIVABLE", '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "Name :", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(160, 20, (isset($data[0]->clientname) ? $data[0]->clientname : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(200, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(200, 20, "", '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "Address :", '', 'L', false, 0);
        // PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(280, 20, (isset($data[0]->addre) ? $data[0]->address : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(130, 20, "", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(250, 20, '', '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "Model :", '', 'L', false, 0);
        // PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(500, 20, (isset($data[0]->model) ? $data[0]->model : ''), '', 'L', false, 1);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "Terms :", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(280, 20, (isset($data[0]->terms) ? $data[0]->terms : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(130, 20, "", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(250, 20, '', '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "Mode of Payment :", '', 'L', false, 0);
        // PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(280, 20, (isset($data[0]->modeofsales) ? $data[0]->modeofsales : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(130, 20, "", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(250, 20, '', '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "Contact No :", '', 'L', false, 0);
        PDF::MultiCell(280, 20, (isset($data[0]->contactno) ? $data[0]->contactno : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(130, 20, "", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(250, 20, '', '', 'L', false);
        $date = new DateTime($data[0]->due);
        $firstdue = $date->format('d-M-y');
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "First Due :", '', 'L', false, 0);
        PDF::MultiCell(280, 20, (isset($firstdue) ? $firstdue : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(130, 20, "", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(250, 20, '', '', 'L', false);

        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "M/A :", '', 'L', false, 0);
        PDF::MultiCell(280, 20, (isset($data[0]->ma) ? number_format($data[0]->ma + $data[0]->rebate, 2) : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(130, 20, "", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(250, 20, '', '', 'L', false);
        $locale = 'en_US';
        $nf = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(100, 20, "Due Date :", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(280, 20, $nf->format(date('d', strtotime($data[0]->hdate))), '', 'L', false, 0);
        PDF::SetFont($font, '', 9);
        PDF::MultiCell(130, 20, "", '', 'R', false, 0);
        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(250, 20, '', '', 'L', false);

        // PDF::SetFont($font, '', 11);
        // PDF::MultiCell(760, 20, "", '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 9);

        PDF::MultiCell(60, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Particular", '', 'C', false, 0);
        PDF::MultiCell(80, 10, "Amt. Finance", '', 'R', false, 0);
        PDF::MultiCell(60, 10, "Interest", '', 'C', false, 0);
        PDF::MultiCell(60, 10, "Principal", '', 'C', false, 0);
        PDF::MultiCell(60, 10, "OF.In", '', 'C', false, 0);
        PDF::MultiCell(60, 10, "OR#", '', 'C', false, 0);
        PDF::MultiCell(60, 10, "Amount", '', 'L', false, 0);
        PDF::MultiCell(60, 10, "Rebate", '', 'L', false, 0);
        PDF::MultiCell(60, 10, "Penalty", '', 'L', false, 0);
        PDF::MultiCell(60, 10, "Current", '', 'L', false, 0);
        PDF::MultiCell(80, 10, "A/R", '', 'L', false);


        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);
        PDF::MultiCell(0, 0, "\n");
    }

    public function ordinal($number)
    {
        $suffix = 'th';
        if (!in_array($number % 100, [11, 12, 13])) {
            $suffixes = ['th', 'st', 'nd', 'rd'];
            $suffix = $suffixes[($number % 10 < 4) ? $number % 10 : 0];
        }
        return $number . $suffix;
    }

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
        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
        }

        if (empty($data)) {
            return $this->no_transaction_selected();
        }

        $data2 = $this->getdetail($config, $data);
        if (!empty($data2)) {
            $postdate = '';
            $rem = '';
            $crno = '';
            $ma = 0;
            $rebate = 0;
            $penalty = '';
            $current = 0;
            $totalar = 0;
            $i = 0;
            $prevamt = 0;
            $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
            $this->PDF_customer($config, $data);
            $financeamt = ($data[0]->amt + $data[0]->miscfee) - $data[0]->downpayment;
            $tpartial = ($data[0]->ma + $data[0]->rebate);
            foreach ($data as $key => $value) {
                $maxrow = 1;
                $privfamt = number_format($financeamt, 2);
                $crno = $data[$key]->cr;

                $downpayment = $data[$key]->downpayment;
                $rebate = $data[$key]->rebate;
                $penalty = '';
                $terms = $data[$key]->days;
                if ($downpayment != 0 && $downpayment != '') {
                    $rem = "Downpayment";
                    $postdate = $data[$key]->hdate;
                    $ma = $data[$key]->downpayment;
                } else {
                    $rem = isset($data2[0]['rem']) ? $data2[0]['rem'] : '';
                    $postdate = $data[$key]->due;
                    $ma = $data[$key]->ma;
                }

                $current = $data[$key]->current;
                $totalar = $current * $terms;
                $arr_date = $this->reporter->fixcolumn([$postdate], '25', 0);
                $arr_rem = $this->reporter->fixcolumn([$rem], '35', 0);
                $arr_orno = $this->reporter->fixcolumn([$crno], '35', 0);
                $arr_financeamt = $this->reporter->fixcolumn([$privfamt], '25', 0);
                $arr_downpayment = $this->reporter->fixcolumn([number_format($downpayment, 2)], '25', 0);
                $arr_ma = $this->reporter->fixcolumn([number_format($ma, 2)], '25', 0);
                $arr_rebate = $this->reporter->fixcolumn([number_format($rebate, 2)], '25', 0);
                $arr_penalty = $this->reporter->fixcolumn([$penalty], '25', 0);
                $arr_current = $this->reporter->fixcolumn([number_format($current, 2)], '25', 0);
                $arr_totalar = $this->reporter->fixcolumn([number_format($totalar, 2)], '25', 0);
                $maxrow = $this->othersClass->getmaxcolumn([$arr_date, $arr_rem, $arr_financeamt, $arr_downpayment, $arr_ma, $arr_rebate, $arr_penalty, $arr_current, $arr_totalar, $arr_orno]);

                // . (isset($arr_totalar[$j]) ? $arr_totalar[$j] : '')
                for ($j = 0; $j < $maxrow; $j++) {
                    PDF::SetFont($font, '', 8);
                    PDF::MultiCell(60, 0, '' . (isset($arr_date[$j]) ? date("j-M-y", strtotime($arr_date[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(100, 0, '' . (isset($arr_rem[$j]) ? $arr_rem[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(80, 0, '' . (isset($arr_financeamt[$j]) ? $arr_financeamt[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(60, 0, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(60, 0, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(60, 0, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(60, 0, '' . (isset($arr_orno[$j]) ? $arr_orno[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(60, 0, '' . (isset($arr_ma[$j]) ? $arr_ma[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(60, 0, '' , '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(60, 0, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(60, 0, '' . (isset($arr_current[$j]) ? $arr_current[$j] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                    PDF::MultiCell(80, 0, '' . (isset($arr_totalar[$j]) ? $arr_totalar[$j] : ''), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                }
            }
            $c = 1;
            $privfamt = 0;
            $privamt = $financeamt;
            for ($i = 0; $i < count($data2); $i++) {
                $maxrow2 = 1;
                $post = $data2[$i]['postdate'];
                $trr = $data2[$i]['trno'];
                $qryh = " select trno,yourref,docno,sum(db) as db,postdate,refx,sum(rebate) as rebate,ardate,penalty from (select  head.trno, head.yourref,head.docno,detail.cr as db,0 as rebate,head.dateid as postdate,detail.refx,
                    (select sum(d.cr-d.db) as amt from ladetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6' and d.type = 'P' and left(d.podate,10)='$post') as penalty,
                    detail.postdate as ardate
                    from lahead as head
                    left join ladetail as detail on detail.trno=head.trno      
                    left join coa on coa.acnoid = detail.acnoid       
                    where detail.refx = $trr and left(detail.postdate,10)='$post'  and coa.alias in ('AR1','AR2')
                    union all
                    select   head.trno, head.yourref,head.docno ,detail.cr as db,0 as rebate,head.dateid as postdate,detail.refx,
                    (select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'P' and left(d.podate,10)='$post') as penalty,detail.postdate as ardate
                    from glhead as head
                    left join gldetail as detail on detail.trno=head.trno 
                    left join coa on coa.acnoid = detail.acnoid          
                    where  detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR1','AR2')
                    union all
                    select  head.trno, head.yourref,head.docno,0 as db,detail.cr as rebate,head.dateid as postdate,detail.refx,
                    (select sum(d.cr-d.db) as amt from ladetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'P' and left(d.podate,10)='$post') as penalty,
                    detail.postdate as ardate
                    from lahead as head
                    left join ladetail as detail on detail.trno=head.trno
                    left join coa on coa.acnoid = detail.acnoid
                    where detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR5')
                    union all
                    select   head.trno, head.yourref,head.docno ,0 as db,detail.cr as rebate,head.dateid as postdate,detail.refx,
                    (select sum(d.cr-d.db) as amt from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = head.trno and c.alias ='SA6'  and d.type = 'P' and left(d.podate,10)='$post') as penalty,detail.postdate as ardate
                    from glhead as head
                    left join gldetail as detail on detail.trno=head.trno left join coa on coa.acnoid = detail.acnoid
                    where  detail.refx = $trr and left(detail.postdate,10)='$post' and coa.alias in ('AR5')) as a 
                    group by trno,yourref,docno,postdate,refx,ardate,penalty";

                $crd = $this->coreFunctions->opentable($qryh);//payments
            
                if (isset($data2[$i]['rem'])) {
                    $rem2 = $data2[$i]['rem'];
                } else {
                    $rem2 = '';
                }


                $crno2 = '';
                $postdate2 = '';
                $ma2 = 0;
                $rebate2 = 0;
                $penalty2 = 0;
                $particulars = '';
                $current2 = $current;
                $nopayfamt = 0;

                // $privamt = ;
                if (!empty($crd)) {

                    $prevpost = '';
                    $prevma2 = 0;
                    foreach ($crd as $key => $crnoo) {
                        $crno2 = isset($crnoo->yourref) ? $crnoo->yourref : '';
                        $postdate2 = isset($crnoo->postdate) ? $crnoo->postdate : '';
                        $ma2 = isset($crnoo->db) ? $crnoo->db : 0;
                        $rebate2 = $crnoo->rebate;
                        $rempartial = '';
                        if ($crnoo->ardate < $crnoo->postdate) {
                            $rebate2 = 0;
                            $ma2 = isset($crnoo->db) ? $crnoo->db + $crnoo->rebate : 0;

                            if ($tpartial != ($ma2 + $prevma2)) {
                                $rempartial = '(PARTIAL)';
                            } else {
                                if ($prevpost == $data2[$i]['postdate']) {
                                    if ($tpartial == ($ma2 + $prevma2)) {
                                        $rempartial = '(FULL)';
                                    }
                                }
                            }
                        }
                        $dayselapse = date_diff(date_create($crnoo->postdate), date_create($crnoo->ardate));
                        if (intval($dayselapse->format("%a")) > 5) {
                            if ($crnoo->penalty != 0) {
                                $penalty2 = number_format($crnoo->penalty, 2);
                            }
                        }

                        $arr_date2 = $this->reporter->fixcolumn([$postdate2], '25', 0);
                        $arr_rem2 = $this->reporter->fixcolumn([$rem2], '25', 0);
                        $arr_crno2 = $this->reporter->fixcolumn([$crno2], '25', 0);
                        $arr_ma2 = $this->reporter->fixcolumn([number_format($ma2, 2)], '25', 0);
                        $arr_rebate2 = $this->reporter->fixcolumn([number_format($rebate2, 2)], '25', 0);
                        $arr_penalty2 = $this->reporter->fixcolumn([$penalty2], '25', 0);
                        $arr_current2 = $this->reporter->fixcolumn([number_format($current2, 2)], '25', 0);

                        $maxrow2 = $this->othersClass->getmaxcolumn([$arr_date2, $arr_rem2, $arr_crno2, $arr_ma2, $arr_rebate2, $arr_penalty2, $arr_current2]);

                        for ($j = 0; $j < $maxrow2; $j++) {
                            $totalar -= ($ma2 + $rebate2);
                            PDF::SetFont($font, '', 8);
                            PDF::MultiCell(760, 10, "", '', 'L', false);
                            PDF::SetFont($font, '', 8);
                            PDF::MultiCell(60, 0, '' . (isset($arr_date2[$j]) ? date("j-M-y", strtotime($arr_date2[$j])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                            PDF::MultiCell(100, 0, '' . (isset($arr_rem2[$j]) ? $this->ordinal($i + 1) . " payment_" . date("M-y", strtotime($data2[$i]['postdate'])) : '') . $rempartial, '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                            PDF::MultiCell(80, 0, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                            PDF::MultiCell(60, 0, '' . number_format($data2[$i]['interest'], 2), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                            PDF::MultiCell(60, 0, '' . number_format($data2[$i]['principal'], 2), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                            PDF::MultiCell(60, 0, '' . ($c), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                            PDF::MultiCell(60, 0, '' . (isset($arr_crno2[$j]) ? $arr_crno2[$j] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                            PDF::MultiCell(60, 0, '' . (isset($arr_ma2[$j]) ? $arr_ma2[$j] : '-'), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                            PDF::MultiCell(60, 0, '' . (isset($arr_rebate2[$j]) ? $arr_rebate2[$j] : '-'), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                            PDF::MultiCell(60, 0, '' . (isset($arr_penalty2[$j]) ? $arr_penalty2[$j] : '-'), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                            PDF::MultiCell(60, 0, '' . (isset($arr_current2[$j]) ? $arr_current2[$j] : '-'), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                            $total = $totalar == 0 ? '-' : number_format(abs($totalar), 2);
                            PDF::MultiCell(80, 0, '' . $total, '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                        }
                        $c++;
                        $prevma2 = $ma2;
                        $prevpost = $data2[$i]['postdate'];
                    }
                } else {
                    $maxrow3 = 1;
                    // wala pa bayad
                    $current2 = $ma2;
                    $arr_date2 = $this->reporter->fixcolumn([$postdate2], '25', 0);
                    $arr_rem2 = $this->reporter->fixcolumn([$rem2], '25', 0);
                    $arr_crno2 = $this->reporter->fixcolumn([$crno2], '25', 0);
                    $arr_ma2 = $this->reporter->fixcolumn([number_format($ma2, 2)], '25', 0);
                    $arr_rebate2 = $this->reporter->fixcolumn([number_format($rebate2, 2)], '25', 0);
                    $arr_penalty2 = $this->reporter->fixcolumn([$penalty2], '25', 0);
                    $arr_current2 = $this->reporter->fixcolumn([number_format($current2, 2)], '25', 0);
                    $maxrow3 = $this->othersClass->getmaxcolumn([$arr_date2, $arr_rem2, $arr_crno2, $arr_ma2, $arr_rebate2, $arr_penalty2, $arr_current2]);

                    for ($k = 0; $k < $maxrow3; $k++) {
                        $totalar -= $current2;
                        PDF::SetFont($font, '', 8);
                        PDF::MultiCell(760, 10, "", '', 'L', false);
                        PDF::SetFont($font, '', 8);
                        PDF::MultiCell(60, 0, '' . (isset($arr_date2[$k]) ? date("j-M-y", strtotime($arr_date2[$k])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(100, 0, '' . (isset($arr_rem2[$k]) ? $this->ordinal($i + 1) . " payment_" . date("M-y", strtotime($data2[$i]['postdate'])) : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(80, 0, '', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                        PDF::MultiCell(60, 0, '' . number_format($data2[$i]['interest'], 2), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(60, 0, '' . number_format($data2[$i]['principal'], 2), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);

                        PDF::MultiCell(60, 0, '' . $c, '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(60, 0, '' . (isset($arr_crno2[$k]) ? $arr_crno2[$k] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(60, 0, '' . (isset($arr_ma2[$k]) ? $arr_ma2[$k] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(60, 0, '' . (isset($arr_rebate2[$k]) ? $arr_rebate2[$k] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(60, 0, '' . (isset($arr_penalty2[$k]) ? $arr_penalty2[$k] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(60, 0, '' . (isset($arr_current2[$k]) ? $arr_current2[$k] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
                        PDF::MultiCell(80, 0, $totalar == 0 ? '-' : number_format($totalar, 2), '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
                    }
                    $c++;
                }
            }
        }
        PDF::SetFont($font, '', 5);
        // PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        // PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);

        // PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        // PDF::MultiCell(95, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);


        // PDF::MultiCell(0, 0, "\n\n\n\n");
        // PDF::SetFont($font, '', $fontsize);
        // PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
        // PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
        // PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

        // PDF::MultiCell(0, 0, "\n\n");
        // PDF::SetFont($fontbold, '', $fontsize);
        // PDF::MultiCell(253, 0, $prepared, '', 'L', false, 0);
        // PDF::MultiCell(253, 0, $received, '', 'L', false, 0);
        // PDF::MultiCell(254, 0, $approved, '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }

    public function no_transaction_selected()
    {
        $count = 55;
        $page = 54;
        $fontsize = "11";
        $font = "";
        $fontbold = "";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);
        PDF::SetFont($fontbold, '', 20);
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(720, 0, 'No Transaction Selected', '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
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


        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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



        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');



        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");
        // PDF::SetFont($fontbold, '', 12);
        // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        // PDF::SetFont($font, '', 11);
        // PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');


        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - ACCOUNTS PAYABLE", '', 'L', false);

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
        PDF::MultiCell(110, 10, "Agent", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(90, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Balance", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(95, 10, "Reference", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        foreach ($data as $key => $data) {
            $maxrow = 1;
            $docno = $data->docno;
            $dateid = $data->dateid;
            $agent = $data->agent;
            $rem = $data->rem;
            $debit = number_format($data->db, $decimalcurr);
            $credit = number_format($data->cr, $decimalcurr);
            $balance = number_format($data->balance, $decimalcurr);
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;
            $balance = $balance < 0 ? '-' : $balance;
            $ref = $data->ref;
            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_agent = $this->reporter->fixcolumn([$agent], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_agent, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref, $arr_dateid]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 15, (isset($arr_agent[$r]) ? $arr_agent[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(95, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }


            $totaldb = $totaldb + $data->db;
            $totalcr = $totalcr + $data->cr;
            $totalbal = $totalbal + $data->bal;
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(95, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

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

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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


        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');


        PDF::MultiCell(0, 0, "\n");
        $this->reportheader->getheader($config);
        PDF::MultiCell(0, 0, "\n");
        // PDF::SetFont($fontbold, '', 12);
        // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        // PDF::SetFont($font, '', 11);
        // PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');


        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - POSTDATED CHECKS", '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Report Type : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(100, 20, strtoupper($reporttype), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "View Accounts from : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(200, 20, $start, '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(80, 20, "Agent : ", '', 'L', false, 0);
        PDF::MultiCell(150, 20, isset($data[0]->agentname) ? $data[0]->agentname : "", '', 'L', false);

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
        PDF::MultiCell(110, 10, "Agent", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(90, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(185, 10, "Reference", '', 'C', false);

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
            $checkdate = date('Y-m-d', strtotime($data1->checkdate));
            $agentname = $data1->agentname;
            $rem = $data1->rem;
            $debit = number_format($data1->db, $decimalcurr);
            $credit = number_format($data1->cr, $decimalcurr);
            $ref = $data1->ref;
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_checkdate = $this->reporter->fixcolumn([$checkdate], '16', 0);
            $arr_agentname = $this->reporter->fixcolumn([$agentname], '16', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_checkdate, $arr_agentname, $arr_rem, $arr_debit, $arr_credit, $arr_ref]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_checkdate[$r]) ? $arr_checkdate[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 15, (isset($arr_agentname[$r]) ? $arr_agentname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(185, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }


            $totaldb += $data1->db;
            $totalcr += $data1->cr;
            $totalbal += $totaldb - $totalcr;
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(185, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

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

    public function reportdefaultRC_PDF($config, $data)
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

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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



        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');



        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - RETURN CHECKS", '', 'L', false);

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
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'L', false, 0);
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
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'L', false, 0);
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
        PDF::MultiCell(110, 10, "Agent", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Notes", '', 'C', false, 0);
        PDF::MultiCell(90, 10, "Debit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Credit", '', 'R', false, 0);
        PDF::MultiCell(90, 10, "Balance", '', 'R', false, 0);
        PDF::MultiCell(5, 10, "", '', 'R', false, 0);
        PDF::MultiCell(95, 10, "Reference", '', 'C', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;

        $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
        foreach ($data as $key => $data) {
            $maxrow = 1;
            $docno = $data->docno;
            $dateid = $data->dateid;
            $agent = $data->agent;
            $rem = $data->rem;
            $debit = number_format($data->db, $decimalcurr);
            $credit = number_format($data->cr, $decimalcurr);
            $balance = number_format($data->balance, $decimalcurr);
            $ref = $data->ref;
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;
            $balance = $balance < 0 ? '-' : $balance;

            $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
            $arr_dateid = $this->reporter->fixcolumn([$dateid], '16', 0);
            $arr_agent = $this->reporter->fixcolumn([$agent], '16', 0);
            $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
            $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
            $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
            $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
            $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_agent, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 15, (isset($arr_agent[$r]) ? $arr_agent[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 15, (isset($arr_rem[$r]) ? $arr_rem[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(90, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(95, 15, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
            }


            $totaldb = $totaldb + $data->db;
            $totalcr = $totalcr + $data->cr;
            $totalbal = $totalbal + $data->bal;
            $str .= $this->reporter->endrow();
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'B', 'L', false);

        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', $fontsize);

        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(90, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(95, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);

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
        $fontsize = 11;
        $font = "";
        $fontbold = "";

        if (Storage::disk('sbcpath')->exists('/fonts/verdana.ttf')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdana.ttf');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/verdanab.ttf');
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


        PDF::SetFont($font, '', 9);
        PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');



        PDF::MultiCell(0, 0, "\n");
        PDF::SetFont($fontbold, '', 12);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');


        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - INVENTORY", '', 'L', false);

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
        PDF::MultiCell(130, 20, "Telephone No/s : ", '', 'L', false, 0);
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
        PDF::MultiCell(130, 20, "Fax No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->fax) ? $data[0]->fax : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "Email Address : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(280, 20, (isset($data[0]->email) ? $data[0]->email : ''), '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Mobile No/s : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->tel2) ? $data[0]->tel2 : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(100, 20, "", '', 'L', false, 0);
        PDF::MultiCell(280, 20, '', '', 'L', false, 0);
        PDF::SetFont($font, '', 11);
        PDF::MultiCell(130, 20, "Contact Person : ", '', 'L', false, 0);
        PDF::SetFont($fontbold, '', 11);
        PDF::MultiCell(250, 20, (isset($data[0]->contact) ? $data[0]->contact : ''), '', 'L', false);

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(760, 20, "Run Date : " . date('M-d-Y h:i:s a', time()), '', 'L', false);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
        PDF::MultiCell(60, 0, "", 'T', 'L', false);

        PDF::SetFont($fontbold, '', 9);
        PDF::MultiCell(70, 10, "Document #", '', 'C', false, 0);
        PDF::MultiCell(100, 10, "Date", '', 'C', false, 0);
        PDF::MultiCell(110, 10, "Item Code", '', 'C', false, 0);
        PDF::MultiCell(270, 10, "Description", '', 'C', false, 0);
        PDF::MultiCell(40, 10, "Unit", '', 'C', false, 0);
        PDF::MultiCell(50, 10, "Discount", '', 'C', false, 0);
        PDF::MultiCell(40, 10, "Price", '', 'R', false, 0);
        PDF::MultiCell(40, 10, "In", '', 'R', false, 0);
        PDF::MultiCell(40, 10, "Out", '', 'R', false);

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
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '65', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
            $arr_cost = $this->reporter->fixcolumn([$cost], '13', 0);
            $arr_rrqty = $this->reporter->fixcolumn([$rrqty], '13', 0);
            $arr_isqty = $this->reporter->fixcolumn([$isqty], '13', 0);

            $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_dateid, $arr_barcode, $arr_itemname, $arr_uom, $arr_disc, $arr_cost, $arr_rrqty, $arr_isqty]);

            for ($r = 0; $r < $maxrow; $r++) {
                PDF::SetFont($font, '', $fontsize - 3);
                PDF::MultiCell(70, 18, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(100, 18, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(110, 18, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(270, 18, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(40, 18, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(50, 18, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(40, 18, (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(40, 18, (isset($arr_rrqty[$r]) ? $arr_rrqty[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                PDF::MultiCell(40, 18, (isset($arr_isqty[$r]) ? $arr_isqty[$r] : ''), '', 'R', 0, 1, '', '', true, 0, true, false);
            }
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
