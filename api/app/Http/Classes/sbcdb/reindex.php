<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use Illuminate\Support\Str;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

use function PHPSTORM_META\type;

class reindex
{

    private $coreFunctions;
    private $companysetup;
    private $othersClass;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
    } //end fn

    public function reindex($config)
    {
        ini_set('max_execution_time', 0);

        $this->coreFunctions->createindex("arledger", "Index_TrNo", ['trno']);
        $this->coreFunctions->createindex("arledger", "Index_Line", ['line']);
        $this->coreFunctions->createindex("arledger", "Index_ClientID", ['clientid']);
        $this->coreFunctions->createindex("arledger", "Index_DateID", ['dateid']);
        $this->coreFunctions->createindex("arledger", "Index_AcnoID", ['acnoid']);
        $this->coreFunctions->createindex("arledger", "Index_Bal", ['bal']);
        $this->coreFunctions->createindex("arledger", "Index_DocNo", ['docno']);

        $this->coreFunctions->createindex("crledger", "Index_DepoDate", ['depodate']);

        $this->coreFunctions->createindex("hprhead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("hprhead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("hprhead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("hprhead", "Index_requestor", ['requestor']);
        $this->coreFunctions->createindex("hprhead", "Index_sano", ['sano']);
        $this->coreFunctions->createindex("hprhead", "Index_svsno", ['svsno']);
        $this->coreFunctions->createindex("hprhead", "Index_pono", ['pono']);
        $this->coreFunctions->createindex("hprhead", "Index_ourref", ['ourref']);

        $this->coreFunctions->createindex("prhead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("prhead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("prhead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("prhead", "Index_requestor", ['requestor']);
        $this->coreFunctions->createindex("prhead", "Index_sano", ['sano']);
        $this->coreFunctions->createindex("prhead", "Index_svsno", ['svsno']);
        $this->coreFunctions->createindex("prhead", "Index_pono", ['pono']);
        $this->coreFunctions->createindex("prhead", "Index_ourref", ['ourref']);

        $this->coreFunctions->createindex("hprstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("hprstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("hprstock", "Index_status", ['status']);
        $this->coreFunctions->createindex("hprstock", "Index_reqstat", ['reqstat']);
        $this->coreFunctions->createindex("hprstock", "Index_suppid", ['suppid']);

        $this->coreFunctions->createindex("prstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("prstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("prstock", "Index_status", ['status']);
        $this->coreFunctions->createindex("prstock", "Index_reqstat", ['reqstat']);
        $this->coreFunctions->createindex("prstock", "Index_suppid", ['suppid']);

        $this->coreFunctions->createindex("hcdhead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("hcdhead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("hcdhead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("hcdhead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("hcdhead", "Index_procid", ['procid']);

        $this->coreFunctions->createindex("cdhead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("cdhead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("cdhead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("cdhead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("cdhead", "Index_procid", ['procid']);

        $this->coreFunctions->createindex("hcdstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("hcdstock", "Index_reqtrno", ['reqtrno']);
        $this->coreFunctions->createindex("hcdstock", "Index_reqline", ['reqline']);
        $this->coreFunctions->createindex("hcdstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("hcdstock", "Index_suppid", ['suppid']);
        $this->coreFunctions->createindex("hcdstock", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("hcdstock", "Index_catid", ['catid']);
        $this->coreFunctions->createindex("hcdstock", "Index_sano", ['sano']);

        $this->coreFunctions->createindex("cdstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("cdstock", "Index_reqtrno", ['reqtrno']);
        $this->coreFunctions->createindex("cdstock", "Index_reqline", ['reqline']);
        $this->coreFunctions->createindex("cdstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("cdstock", "Index_suppid", ['suppid']);
        $this->coreFunctions->createindex("cdstock", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("cdstock", "Index_catid", ['catid']);
        $this->coreFunctions->createindex("cdstock", "Index_sano", ['sano']);

        $this->coreFunctions->createindex("hpohead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("hpohead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("hpohead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("hpohead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("hpohead", "Index_empid", ['empid']);

        $this->coreFunctions->createindex("pohead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("pohead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("pohead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("pohead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("pohead", "Index_empid", ['empid']);

        $this->coreFunctions->createindex("hpostock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("hpostock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("hpostock", "Index_stageid", ['stageid']);
        $this->coreFunctions->createindex("hpostock", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("hpostock", "Index_reqtrno", ['reqtrno']);
        $this->coreFunctions->createindex("hpostock", "Index_reqline", ['reqline']);

        $this->coreFunctions->createindex("postock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("postock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("postock", "Index_stageid", ['stageid']);
        $this->coreFunctions->createindex("postock", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("postock", "Index_reqtrno", ['reqtrno']);
        $this->coreFunctions->createindex("postock", "Index_reqline", ['reqline']);

        $this->coreFunctions->createindex("oqhead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("oqhead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("oqhead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("oqhead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("oqhead", "Index_empid", ['empid']);
        $this->coreFunctions->createindex("oqhead", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("oqhead", "Index_subproject", ['subproject']);
        $this->coreFunctions->createindex("oqhead", "Index_sotrno", ['sotrno']);

        $this->coreFunctions->createindex("hoqhead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("hoqhead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("hoqhead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("hoqhead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("hoqhead", "Index_empid", ['empid']);
        $this->coreFunctions->createindex("hoqhead", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("hoqhead", "Index_subproject", ['subproject']);
        $this->coreFunctions->createindex("hoqhead", "Index_sotrno", ['sotrno']);

        $this->coreFunctions->createindex("oqstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("oqstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("oqstock", "Index_stageid", ['stageid']);
        $this->coreFunctions->createindex("oqstock", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("oqstock", "Index_reqtrno", ['reqtrno']);
        $this->coreFunctions->createindex("oqstock", "Index_reqline", ['reqline']);
        $this->coreFunctions->createindex("oqstock", "Index_svsno", ['svsno']);
        $this->coreFunctions->createindex("oqstock", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("oqstock", "Index_suppid", ['suppid']);

        $this->coreFunctions->createindex("hoqstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("hoqstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("hoqstock", "Index_stageid", ['stageid']);
        $this->coreFunctions->createindex("hoqstock", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("hoqstock", "Index_reqtrno", ['reqtrno']);
        $this->coreFunctions->createindex("hoqstock", "Index_reqline", ['reqline']);
        $this->coreFunctions->createindex("hoqstock", "Index_svsno", ['svsno']);
        $this->coreFunctions->createindex("hoqstock", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("hoqstock", "Index_suppid", ['suppid']);

        $this->coreFunctions->createindex("omstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("omstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("omstock", "Index_stageid", ['stageid']);
        $this->coreFunctions->createindex("omstock", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("omstock", "Index_refx", ['refx']);
        $this->coreFunctions->createindex("omstock", "Index_linex", ['linex']);
        $this->coreFunctions->createindex("omstock", "Index_svsno", ['svsno']);
        $this->coreFunctions->createindex("omstock", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("omstock", "Index_suppid", ['suppid']);
        $this->coreFunctions->createindex("omstock", "Index_statid", ['statid']);

        $this->coreFunctions->createindex("homstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("homstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("homstock", "Index_stageid", ['stageid']);
        $this->coreFunctions->createindex("homstock", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("homstock", "Index_refx", ['refx']);
        $this->coreFunctions->createindex("homstock", "Index_linex", ['linex']);
        $this->coreFunctions->createindex("homstock", "Index_svsno", ['svsno']);
        $this->coreFunctions->createindex("homstock", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("homstock", "Index_suppid", ['suppid']);
        $this->coreFunctions->createindex("homstock", "Index_statid", ['statid']);

        $this->coreFunctions->createindex("lqhead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("lqhead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("lqhead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("lqhead", "Index_deptid", ['deptid']);
        // $this->coreFunctions->createindex("lqhead", "Index_empid", ['epid']);
        $this->coreFunctions->createindex("lqhead", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("lqhead", "Index_subproject", ['subproject']);
        $this->coreFunctions->createindex("lqhead", "Index_sotrno", ['sotrno']);

        $this->coreFunctions->createindex("hlqhead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("hlqhead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("hlqhead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("hlqhead", "Index_deptid", ['deptid']);
        // $this->coreFunctions->createindex("hlqhead", "Index_empid", ['epid']);
        $this->coreFunctions->createindex("hlqhead", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("hlqhead", "Index_subproject", ['subproject']);
        $this->coreFunctions->createindex("hlqhead", "Index_sotrno", ['sotrno']);

        $this->coreFunctions->createindex("lqstock", "Index_deptid", ['deptid']);

        $this->coreFunctions->createindex("hlqstock", "Index_deptid", ['deptid']);

        $this->coreFunctions->createindex("sohead", "Index_client", ['client']);
        $this->coreFunctions->createindex("sohead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("sohead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("sohead", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("sohead", "Index_sano", ['sano']);
        $this->coreFunctions->createindex("sohead", "Index_pono", ['pono']);
        $this->coreFunctions->createindex("sohead", "Index_statid", ['statid']);

        $this->coreFunctions->createindex("hsohead", "Index_client", ['client']);
        $this->coreFunctions->createindex("hsohead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("hsohead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("hsohead", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("hsohead", "Index_sano", ['sano']);
        $this->coreFunctions->createindex("hsohead", "Index_pono", ['pono']);
        $this->coreFunctions->createindex("hsohead", "Index_statid", ['statid']);

        $this->coreFunctions->createindex("sostock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("sostock", "Index_uom", ['uom']);
        $this->coreFunctions->createindex("sostock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("sostock", "Index_projectid", ['projectid']);

        $this->coreFunctions->createindex("hsostock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("hsostock", "Index_uom", ['uom']);
        $this->coreFunctions->createindex("hsostock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("hsostock", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("hsostock", "Index_qa", ['qa']);
        $this->coreFunctions->createindex("hsostock", "Index_void", ['void']);

        $this->coreFunctions->createindex("pchead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("pchead", "Index_agent", ['agent']);

        $this->coreFunctions->createindex("hpchead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("hpchead", "Index_agent", ['agent']);

        $this->coreFunctions->createindex("pcstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("pcstock", "Index_palletid", ['palletid']);
        $this->coreFunctions->createindex("pcstock", "Index_locid", ['locid']);
        $this->coreFunctions->createindex("pcstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("pcstock", "Index_projectid", ['projectid']);

        $this->coreFunctions->createindex("hpcstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("hpcstock", "Index_palletid", ['palletid']);
        $this->coreFunctions->createindex("hpcstock", "Index_locid", ['locid']);
        $this->coreFunctions->createindex("hpcstock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("hpcstock", "Index_projectid", ['projectid']);

        $this->coreFunctions->createindex("vrhead", "Index_driverid", ['driverid']);
        $this->coreFunctions->createindex("vrhead", "Index_vehicleid", ['vehicleid']);
        $this->coreFunctions->createindex("vrhead", "Index_deptid", ['deptid']);

        $this->coreFunctions->createindex("hvrhead", "Index_driverid", ['driverid']);
        $this->coreFunctions->createindex("hvrhead", "Index_vehicleid", ['vehicleid']);
        $this->coreFunctions->createindex("hvrhead", "Index_deptid", ['deptid']);

        $this->coreFunctions->createindex("vrstock", "Index_purposeid", ['purposeid']);
        $this->coreFunctions->createindex("vrstock", "Index_shipid", ['shipid']);
        $this->coreFunctions->createindex("vrstock", "Index_shipcontactid", ['shipcontactid']);

        $this->coreFunctions->createindex("hvrstock", "Index_purposeid", ['purposeid']);
        $this->coreFunctions->createindex("hvrstock", "Index_shipid", ['shipid']);
        $this->coreFunctions->createindex("hvrstock", "Index_shipcontactid", ['shipcontactid']);

        $this->coreFunctions->createindex("gphead", "Index_client", ['client']);
        $this->coreFunctions->createindex("gphead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("gphead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("gphead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("gphead", "Index_projectid", ['projectid']);

        $this->coreFunctions->createindex("hgphead", "Index_client", ['client']);
        $this->coreFunctions->createindex("hgphead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("hgphead", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("hgphead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("hgphead", "Index_projectid", ['projectid']);

        $this->coreFunctions->createindex("gpstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("gpstock", "Index_uom", ['uom']);
        $this->coreFunctions->createindex("gpstock", "Index_whid", ['whid']);

        $this->coreFunctions->createindex("hgpstock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("hgpstock", "Index_uom", ['uom']);
        $this->coreFunctions->createindex("hgpstock", "Index_whid", ['whid']);

        $this->coreFunctions->createindex("lahead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("lahead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("lahead", "Index_contra", ['contra']);
        $this->coreFunctions->createindex("lahead", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("lahead", "Index_subproject", ['subproject']);
        $this->coreFunctions->createindex("lahead", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("lahead", "Index_brtrno", ['brtrno']);
        $this->coreFunctions->createindex("lahead", "Index_sano", ['sano']);
        $this->coreFunctions->createindex("lahead", "Index_whref", ['whref']);

        $this->coreFunctions->createindex("glhead", "Index_branch", ['branch']);
        $this->coreFunctions->createindex("glhead", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("glhead", "Index_contra", ['contra']);
        $this->coreFunctions->createindex("glhead", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("glhead", "Index_subproject", ['subproject']);
        $this->coreFunctions->createindex("glhead", "Index_wh", ['whid']);
        $this->coreFunctions->createindex("glhead", "Index_brtrno", ['brtrno']);
        $this->coreFunctions->createindex("glhead", "Index_sano", ['sano']);
        $this->coreFunctions->createindex("glhead", "Index_whref", ['whref']);

        $this->coreFunctions->createindex("lastock", "Index_itemid", ['itemid']);
        $this->coreFunctions->createindex("lastock", "Index_palletid", ['palletid']);
        $this->coreFunctions->createindex("lastock", "Index_locid", ['locid']);
        $this->coreFunctions->createindex("lastock", "Index_whid", ['whid']);
        $this->coreFunctions->createindex("lastock", "Index_stageid", ['stageid']);
        $this->coreFunctions->createindex("lastock", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("lastock", "Index_reqtrno", ['reqtrno']);
        $this->coreFunctions->createindex("lastock", "Index_reqline", ['reqline']);

        $this->coreFunctions->createindex("glstock", "Index_palletid", ['palletid']);
        $this->coreFunctions->createindex("glstock", "Index_locid", ['locid']);
        $this->coreFunctions->createindex("glstock", "Index_stageid", ['stageid']);
        $this->coreFunctions->createindex("glstock", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("glstock", "Index_reqtrno", ['reqtrno']);
        $this->coreFunctions->createindex("glstock", "Index_reqline", ['reqline']);

        $this->coreFunctions->createindex("ladetail", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("ladetail", "Index_subproject", ['subproject']);
        $this->coreFunctions->createindex("ladetail", "Index_acnoid", ['acnoid']);

        $this->coreFunctions->createindex("gldetail", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("gldetail", "Index_subproject", ['subproject']);

        $this->coreFunctions->createindex("voiddetail", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("voiddetail", "Index_subproject", ['subproject']);
        $this->coreFunctions->createindex("voiddetail", "Index_acnoid", ['acnoid']);

        $this->coreFunctions->createindex("hvoiddetail", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("hvoiddetail", "Index_subproject", ['subproject']);

        $this->coreFunctions->createindex("headinfotrans", "Index_paymentid", ['paymentid']);
        $this->coreFunctions->createindex("headinfotrans", "Index_categoryid", ['categoryid']);
        $this->coreFunctions->createindex("headinfotrans", "Index_reqtypeid", ['reqtypeid']);
        $this->coreFunctions->createindex("headinfotrans", "Index_wh2", ['wh2']);

        $this->coreFunctions->createindex("hheadinfotrans", "Index_paymentid", ['paymentid']);
        $this->coreFunctions->createindex("hheadinfotrans", "Index_categoryid", ['categoryid']);
        $this->coreFunctions->createindex("hheadinfotrans", "Index_reqtypeid", ['reqtypeid']);
        $this->coreFunctions->createindex("hheadinfotrans", "Index_wh2", ['wh2']);

        $this->coreFunctions->createindex("stockinfotrans", "Index_durationid", ['durationid']);
        $this->coreFunctions->createindex("stockinfotrans", "Index_uom2", ['uom2']);
        $this->coreFunctions->createindex("stockinfotrans", "Index_uom3", ['uom3']);

        $this->coreFunctions->createindex("hstockinfotrans", "Index_durationid", ['durationid']);
        $this->coreFunctions->createindex("hstockinfotrans", "Index_uom2", ['uom2']);
        $this->coreFunctions->createindex("hstockinfotrans", "Index_uom3", ['uom3']);

        $this->coreFunctions->createindex("stockinfo", "Index_status1", ['status1']);
        $this->coreFunctions->createindex("stockinfo", "Index_status2", ['status2']);
        $this->coreFunctions->createindex("stockinfo", "Index_checkstat", ['checkstat']);

        $this->coreFunctions->createindex("hstockinfo", "Index_status1", ['status1']);
        $this->coreFunctions->createindex("hstockinfo", "Index_status2", ['status2']);
        $this->coreFunctions->createindex("hstockinfo", "Index_checkstat", ['checkstat']);

        $this->coreFunctions->createindex("transnum", "Index_statid", ['statid']);

        $this->coreFunctions->createindex("cntnum", "Index_statid", ['statid']);

        $this->coreFunctions->createindex("cntnuminfo", "Index_dropoffwh", ['dropoffwh']);
        $this->coreFunctions->createindex("cntnuminfo", "Index_incidentid", ['incidentid']);

        $this->coreFunctions->createindex("hcntnuminfo", "Index_dropoffwh", ['dropoffwh']);
        $this->coreFunctions->createindex("hcntnuminfo", "Index_incidentid", ['incidentid']);

        $this->coreFunctions->createindex("item", "Index_model", ['model']);
        $this->coreFunctions->createindex("item", "Index_projectid", ['projectid']);
        $this->coreFunctions->createindex("item", "Index_subcat", ['subcat']);
        $this->coreFunctions->createindex("item", "Index_part", ['part']);
        $this->coreFunctions->createindex("item", "Index_asset", ['asset']);
        $this->coreFunctions->createindex("item", "Index_liability", ['liability']);
        $this->coreFunctions->createindex("item", "Index_revenue", ['revenue']);
        $this->coreFunctions->createindex("item", "Index_expense", ['expense']);
        $this->coreFunctions->createindex("item", "Index_salesreturn", ['salesreturn']);
        $this->coreFunctions->createindex("item", "Index_supplier", ['supplier']);
        $this->coreFunctions->createindex("item", "Index_linkdept", ['linkdept']);

        $this->coreFunctions->createindex("iteminfo", "Index_purchaserid", ['purchaserid']);
        $this->coreFunctions->createindex("iteminfo", "Index_locid", ['locid']);
        $this->coreFunctions->createindex("iteminfo", "Index_empid", ['empid']);

        $this->coreFunctions->createindex("issueitem", "Index_clientid", ['clientid']);
        $this->coreFunctions->createindex("issueitem", "Index_locid", ['locid']);
        $this->coreFunctions->createindex("issueitem", "Index_trno", ['trno']);
        $this->coreFunctions->createindex("issueitem", "Index_itemid", ['itemid']);

        $this->coreFunctions->createindex("clientsano", "Index_clientid", ['clientid']);

        $this->coreFunctions->createindex("coa", "Index_acno", ['acno']);
        $this->coreFunctions->createindex("coa", "Index_parent", ['parent']);

        $this->coreFunctions->createindex("vrpassenger", "Index_passengerid", ['passengerid']);

        $this->coreFunctions->createindex("hvrpassenger", "Index_passengerid", ['passengerid']);

        $this->coreFunctions->createindex("client", "Index_agent", ['agent']);
        $this->coreFunctions->createindex("client", "Index_rev", ['rev']);
        $this->coreFunctions->createindex("client", "Index_ass", ['ass']);
        $this->coreFunctions->createindex("client", "Index_grpcode", ['grpcode']);
        $this->coreFunctions->createindex("client", "Index_category", ['category']);
        $this->coreFunctions->createindex("client", "Index_forexid", ['forexid']);
        $this->coreFunctions->createindex("client", "Index_truckid", ['truckid']);
        $this->coreFunctions->createindex("client", "Index_industryid", ['industryid']);
        $this->coreFunctions->createindex("client", "Index_ewtid", ['ewtid']);
        $this->coreFunctions->createindex("client", "Index_deptid", ['deptid']);
        $this->coreFunctions->createindex("client", "Index_empid", ['empid']);
        $this->coreFunctions->createindex("client", "Index_salesgroupid", ['salesgroupid']);
        $this->coreFunctions->createindex("client", "Index_branchid", ['branchid']);
        $this->coreFunctions->createindex("client", "Index_wh", ['wh']);
        $this->coreFunctions->createindex("client", "Index_dropoffwh", ['dropoffwh']);
        $this->coreFunctions->createindex("client", "Index_parent", ['parent']);
        $this->coreFunctions->createindex("client", "Index_deliverytype", ['deliverytype']);



        $this->coreFunctions->createindex("clientinfo", "Index_clientid", ['clientid']);
    }
}
