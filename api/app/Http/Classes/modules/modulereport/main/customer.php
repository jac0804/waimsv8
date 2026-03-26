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
        $fields = ['radioprint', 'start', 'radiocustreporttype', 'prepared', 'approved', 'received', 'print'];
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
                case 'rc':
                    $str = $this->reportdefaultRC($config, $data);
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
                case 'rc':
                    $str = $this->reportdefaultRC_PDF($config, $data);
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
        $layoutsize = '800';
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

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER LEDGER - ACCOUNTS RECEIVABLE ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Report Type :', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(strtoupper($reporttype), '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('View Accounts from :', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . $start, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Agent:', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Telephone No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Agent', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes ', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Debit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Credit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reference', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        foreach ($data as $key => $data) {
            $credit = number_format($data->cr, 2);
            $credit = $credit < 0 ? '-' : $credit;
            $debit = number_format($data->db, 2);
            $debit = $debit < 0 ? '-' : $debit;
            $balance = number_format($data->bal, 2);
            $balance = $balance < 0 ? '-' : $balance;

            if ($data->cr != 0) {
                if ($balance != '-') {
                    $data->bal = $data->bal * -1;
                } //end if
            } //end if

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->agent, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($debit, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($credit, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($balance, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->ref, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $totaldb = $totaldb + $data->db;
            $totalcr = $totalcr + $data->cr;
            $totalbal = $totalbal + $data->bal;
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('Grand Total :', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '150', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $str .=  '<br><br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .=  '<br>';
        $str .= $this->reporter->begintable($layoutsize);
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
        $layoutsize = '800';
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

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER LEDGER - ACCOUNTS PAYABLE', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Report Type :', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(strtoupper($reporttype), '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('View Accounts from :', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . $start, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Agent:', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Telephone No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Agent', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes ', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Debit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Credit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reference', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        foreach ($data as $key => $data) {
            $credit = number_format($data->cr, 2);
            $credit = $credit < 0 ? '-' : $credit;
            $debit = number_format($data->db, 2);
            $debit = $debit < 0 ? '-' : $debit;
            $balance = number_format($data->bal, 2);
            $balance = $balance < 0 ? '-' : $balance;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->agent, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($debit, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($credit, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($balance, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->ref, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $totaldb = $totaldb + $data->db;
            $totalcr = $totalcr + $data->cr;
            $totalbal = $totalbal + $data->bal;
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('Grand Total :', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '150', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');

        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $str .= '<br><br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
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
        $layoutsize = '800';
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


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER LEDGER - POSTDATED CHECKS ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Report Type :', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(strtoupper($reporttype), '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('View Accounts from :', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . $start, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Agent:', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(isset($data[0]->agentname) ? $data[0]->agentname : "", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Telephone No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Agent', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes ', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Debit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Credit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reference', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        foreach ($data as $key => $data1) {
            $credit = number_format($data1->cr, 2);
            $credit = $credit < 0 ? '-' : $credit;
            $debit = number_format($data1->db, 2);
            $debit = $debit < 0 ? '-' : $debit;

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data1->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(date('Y-m-d', strtotime($data1->checkdate)), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data1->agentname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data1->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($debit, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($credit, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data1->ref, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $totaldb += $data1->db;
            $totalcr += $data1->cr;
            $totalbal += $totaldb - $totalcr;
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('Grand Total :', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '150', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $str .= '<br><br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
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

    public function reportdefaultRC($config, $data)
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
        $layoutsize = '800';
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

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER LEDGER - RETURN CHECKS ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Report Type :', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(strtoupper($reporttype), '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('View Accounts from :', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . $start, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Agent:', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Telephone No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col((isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Agent', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Notes ', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Debit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Credit', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Balance', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Reference', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $totaldb = 0;
        $totalcr = 0;
        $totalbal = 0;
        foreach ($data as $key => $data) {
            $credit = number_format($data->cr, 2);
            $credit = $credit < 0 ? '-' : $credit;
            $debit = number_format($data->db, 2);
            $debit = $debit < 0 ? '-' : $debit;
            $balance = number_format($data->bal, 2);
            $balance = $balance < 0 ? '-' : $balance;
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->dateid, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->agent, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($debit, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($credit, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($balance, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data->ref, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $totaldb = $totaldb + $data->db;
            $totalcr = $totalcr + $data->cr;
            $totalbal = $totalbal + $data->bal;
            $str .= $this->reporter->endrow();
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('Grand Total :', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col(number_format($totalbal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('', '150', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();


        $str .= '<br><br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
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
        $layoutsize = '800';
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

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER LEDGER - INVENTORY ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Report Type :', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col(strtoupper($reporttype), '25', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('View Accounts from :', '150', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . $start, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Agent:', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Customer:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->clientname) ? $data[0]->clientname : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Telephone No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel) ? $data[0]->tel : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Address:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->addr) ? $data[0]->addr : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Fax No/s:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->fax) ? $data[0]->fax : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('TIN #:', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tin) ? $data[0]->tin : ''), 400, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->col('Mobile No/s.:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->tel2) ? $data[0]->tel2 : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Email Address:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->email) ? $data[0]->email : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('', 400, null, false, $border, '', 'L', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('Contact Person:', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '1px');
        $str .= $this->reporter->col('&nbsp;&nbsp;' . (isset($data[0]->contact) ? $data[0]->contact : ''), 250, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '1px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Run Date :' . date('M-d-Y h:i:s a', time()), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->printline();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Document #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Item Code', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Unit', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Discount ', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
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
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($data1->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(date('Y-m-d', strtotime($data1->dateid)), '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data1->barcode, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data1->itemname, '225', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data1->uom, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($data1->disc, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($cost, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($rrqty, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($isqty, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
        }

        $str .= $this->reporter->endtable();


        $str .= '<br><br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
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
        switch ($companyid) {
                // case 8:
                //     $this->reportheader->getheader($config);
                //     break;
            case 34:
                PDF::MultiCell(0, 0, "\n\n\n");
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                PDF::SetFont($font, '', 11);
                PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
                break;
            default:
                PDF::MultiCell(0, 0, "\n");
                $this->reportheader->getheader($config);
                PDF::MultiCell(0, 0, "\n");
                break;
        }

        PDF::SetFont($fontbold, '', 15);
        PDF::MultiCell(760, 30, "CUSTOMER LEDGER - ACCOUNTS RECEIVABLE", '', 'L', false);

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
        if ($companyid == 19) {
            PDF::MultiCell(170, 10, 'Customer ID/Customer', '', 'C', false, 0);
            PDF::MultiCell(70, 10, 'Date', '', 'C', false, 0);
            PDF::MultiCell(110, 10, 'Doc #', '', 'C', false, 0);
            PDF::MultiCell(75, 0, 'Type', '', 'C', false, 0);
            PDF::MultiCell(95, 0, 'Debit', '', 'R', false, 0);
            PDF::MultiCell(95, 0, 'Credit', '', 'R', false, 0);
            PDF::MultiCell(105, 0, 'Running Balance', '', 'R');
        } else {
            PDF::MultiCell(110, 10, "Document #", '', 'C', false, 0);
            PDF::MultiCell(70, 10, "Date", '', 'C', false, 0);
            PDF::MultiCell(110, 10, "Agent", '', 'C', false, 0);
            PDF::MultiCell(100, 10, "Notes", '', 'C', false, 0);
            PDF::MultiCell(90, 10, "Debit", '', 'R', false, 0);
            PDF::MultiCell(90, 10, "Credit", '', 'R', false, 0);
            PDF::MultiCell(90, 10, "Balance", '', 'R', false, 0);
            PDF::MultiCell(5, 10, "", '', 'R', false, 0);
            PDF::MultiCell(95, 10, "Reference", '', 'C', false);
        }

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
            $balance = number_format($data->bal, $decimalcurr);
            $debit = $debit < 0 ? '-' : $debit;
            $credit = $credit < 0 ? '-' : $credit;
            if ($data->cr != 0) {
                $balance = $balance < 0 ? '-' : $balance * -1;
            }
            $ref = $data->ref;

            if ($companyid == 19) {
                $client = $data->client . ' ' . $data->clientname;
                $doc = $data->doc;

                $arr_client = $this->reporter->fixcolumn([$client], '30', 0);
                $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
                $arr_docno = $this->reporter->fixcolumn([$docno], '25', 0);
                $arr_doc = $this->reporter->fixcolumn([$doc], '15', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '15', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '15', 0);
                $arr_balance = $this->reporter->fixcolumn([$balance], '15', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_client, $arr_dateid, $arr_docno, $arr_doc, $arr_debit, $arr_credit, $arr_balance]);

                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    PDF::MultiCell(170, 15, (isset($arr_client[$r]) ? $arr_client[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                    PDF::MultiCell(70, 15, (isset($arr_dateid[$r]) ? $arr_dateid[$r] : ''), '', 'C', 0, 0, '', '', true, 0, true, false);
                    PDF::MultiCell(110, 15, (isset($arr_docno[$r]) ? $arr_docno[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                    PDF::MultiCell(75, 15, (isset($arr_doc[$r]) ? $arr_doc[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
                    PDF::MultiCell(95, 15, (isset($arr_debit[$r]) ? $arr_debit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                    PDF::MultiCell(95, 15, (isset($arr_credit[$r]) ? $arr_credit[$r] : ''), '', 'R', 0, 0, '', '', true, 0, true, false);
                    PDF::MultiCell(105, 15, (isset($arr_balance[$r]) ? $arr_balance[$r] : ''), '', 'R', 0, 1, '', '', true, 0, true, false);
                }
            } else {
                $arr_docno = $this->reporter->fixcolumn([$docno], '16', 0);
                $arr_dateid = $this->reporter->fixcolumn([$dateid], '15', 0);
                $arr_agent = $this->reporter->fixcolumn([$agent], '16', 0);
                $arr_rem = $this->reporter->fixcolumn([$rem], '16', 0);
                $arr_debit = $this->reporter->fixcolumn([$debit], '13', 0);
                $arr_credit = $this->reporter->fixcolumn([$credit], '13', 0);
                $arr_balance = $this->reporter->fixcolumn([$balance], '13', 0);
                $arr_ref = $this->reporter->fixcolumn([$ref], '16', 0);

                $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_agent, $arr_rem, $arr_debit, $arr_credit, $arr_balance, $arr_ref]);

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
        if ($companyid == 19) {
            PDF::MultiCell(170, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(100, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(85, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(95, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(95, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(105, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
        } else {
            PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(70, 15, '', '', 'C', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(110, 15, '', '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(100, 15, 'Grand Total : ', '', 'L', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(90, 15, number_format($totaldb, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(90, 15, number_format($totalcr, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(90, 15, number_format($totalbal, 2), '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(5, 15, '', '', 'R', 0, 0, '', '', true, 0, true, false);
            PDF::MultiCell(95, 15, '', '', 'L', 0, 1, '', '', true, 0, false, false);
        }

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

        if ($companyid) {
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

        switch ($companyid) {
                // case 8:
                //     $this->reportheader->getheader($config);
                //     break;
            case 34:
                PDF::MultiCell(0, 0, "\n\n\n");
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                PDF::SetFont($font, '', 11);
                PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
                break;
            default:
                PDF::MultiCell(0, 0, "\n");
                $this->reportheader->getheader($config);
                PDF::MultiCell(0, 0, "\n");
                // PDF::SetFont($fontbold, '', 12);
                // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                // PDF::SetFont($font, '', 11);
                // PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
                break;
        }


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

        switch ($companyid) {
                // case 8:
                //     $this->reportheader->getheader($config);
                //     break;
            case 34:
                PDF::MultiCell(0, 0, "\n\n\n");
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                PDF::SetFont($font, '', 11);
                PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
                break;
            default:
                PDF::MultiCell(0, 0, "\n");
                $this->reportheader->getheader($config);
                PDF::MultiCell(0, 0, "\n");
                // PDF::SetFont($fontbold, '', 12);
                // PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                // PDF::SetFont($font, '', 11);
                // PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
                break;
        }

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
        switch ($companyid) {
            case 8:
                $this->reportheader->getheader($config);
                break;
            case 34:
                PDF::MultiCell(0, 0, "\n\n\n");
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                PDF::SetFont($font, '', 11);
                PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
                break;
            default:
                PDF::MultiCell(0, 0, "\n");
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                PDF::SetFont($font, '', 11);
                PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
                break;
        }


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
            default;
                PDF::SetFont($font, '', 9);
                PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
                break;
        }

        switch ($companyid) {
            case 8:
                $this->reportheader->getheader($config);
                break;
            case 34:
                PDF::MultiCell(0, 0, "\n\n\n");
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                PDF::SetFont($font, '', 11);
                PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
                break;
            default:
                PDF::MultiCell(0, 0, "\n");
                PDF::SetFont($fontbold, '', 12);
                PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
                PDF::SetFont($font, '', 11);
                PDF::MultiCell(0, 0, $headerdata[0]->address . "\n" . $headerdata[0]->tel . "\n\n\n", '', 'C');
                break;
        }

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
