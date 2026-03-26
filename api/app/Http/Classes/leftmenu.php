<?php

namespace App\Http\Classes;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;

use Exception;
use Throwable;
use Session;



// last attribute - 4645

// -------------------------- Available Attribute For Reuse ------------------------------------

// Attribute Code groupings
// 1 Masterfile
// 2 Payable
// 3 Recivable
// 4 Purchases
// 5 Sales
// 6 Inventory
// 7 Acctg
// 8 System
// 9 Reports
// 10  Dashboard
// 11  Item Master
// 12  School Setup
// 13  Announcement
// 14  School System
// 15  Issuance
// 16  Customer Support
// 17  HRIS
// 18  HRIS Transaction
// 19  Payroll Setup
// 20  Payroll Transaction
// 21  Account Utilities
// 22  Project Management
// 23  Construction
// 24  Warehousing
// 25  Projectsetup
// 26  Payrollportal
// 28  Consignment
// 29  CRM
// 42  PCF
// A   Hris Report
// B   Payroll Report
// 32 POS
// 33 VEHICLE SCHEDULING
// 90 ANALYTIC DASHBOARD

// adding new module, pls add also prefix in setprefixdoc.php


//TRANSACTION DOC LISTS
// PURCHASES
// PR - Purchase Requisition
// CD - Canvass Sheet
// PO - Purchase Order
// RR - Receiving Report
// DM - Purchase Return
// SN - Supplier Invoice
// PAYABLES
// AP - AP Setup
// PV - AP Voucher
// CV - Cash/Check Voucher
// CHECKRELEASe - Check Releasing
// SALES
// PA - Price Scheme
// SO - Sales Order
// RO - Request Order
// SJ - Sales Journal
// CM - Sales Return
// RECEIVABLES
// AR - AR Setup
// CR - Received Payment
// KR - Counter Receipt
// ACCOUNTING
// GJ - General Journal
// DS - Deposit Slip
// BANKRECON - Bank Reconciliation
// INVENTORY
// PC - Physical Count
// AJ - Inventory Adjustment
// TS - Transfer Slip
// IS - Inventory Setup
// ISSUANCE
// TR - Stock Request
// TRAPPROVAL - Stock Request Approval
// ST - Stock Transfer
// SS - Stock Issuance
// CUSTOMER SUPPORT SYSTEM
// CA - CREATE TICKET
// CB - UPDATE TICKET
// CC - TICKET HISTORY
// ENROLLMENT
// EC - Curriculum
// EA - College Assesment
// EI - GRADESCHOOL Assesment
// ET - ASSESSMENT SETUP
// ES - SCHEDULE
// ED - ADD/DROP
// EF - GRADE SETUP
// EG - STDENT GRADE ENTRY
// ER - REGISTRATION
// EH - GRADE ENTRY
// EM - ATTENDANCE ENTRY

// HRIS
// HC - Clearance
// AL - Applicant
// HA - REQUEST TRAINING AND DEVELOPMENT
// HN - NOTICE TO EXPLAIN
//CONSTRUCTION
// PM - PROJECT MANAGEMENT
// PB - PROGRESS BILLING
// JO - JOB ORDER
// JC - JOB COMPLETION
// BQ - BILL OF QUANTITY
// RQ - Purchase Requisition
// MR - Material Request
// MI - Material issuance
// MT - Material Transfer
// Warehousing
// PL - PackingList
// RP - PackingList Receiving
// SA - Sales Order Agent
// SB - Sales Order Branch
// SC - Sales Order Online
// SD - Sales Journal Agent
// SE - Sales Journal Branch
// SF - Sales Journal Online
// SG - Special Part Request
// SH - Special Part Issuance
// SI - Special Part Return
// WA - Warranty Request
// WB - Warranty Receipt

// CONSIGNMENT
// CN - Consignment Request
// CO - Consignment DR
// CS - Consignment SALES

//CONSTRUCTION
// CN - Construction Order

//FAMS
// GP  - Gatepass
// FC - Convert to Asset

// CRM
// LD - LEAD

// PCF
// 

// SERVICE TICKETING
// TA - SERVICE APPLICATION
// WO - WORK ORDER

//available attributes


class leftmenu
{
    private $coreFunctions;
    private $companysetup;

    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
    } //end function


    public function insertattribute($params, $qry)
    {
        $qry1 = "insert into `attributes` (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) values ";
        $qry = str_replace("\\", '\\\\', $qry);
        $this->coreFunctions->execqry($qry1 . $qry);
    }

    public function parentsales($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $icon = 'trending_up';

        $label = 'SALES';
        switch ($params['companyid']) {
            case 48: //seastar
                $label = 'CARGO LOGISTICS';
                $icon = 'local_shipping';
                break;
        }

        $qry = "(150,0,'" . $label . "','',0,'$parent','\\',0,'',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);


        switch ($params['companyid']) {
            case 48: //seastar
                return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'" . $label . "',$sort,'" . $icon . "',',SJ'," . $params['levelid'] . ")";
                break;
            case 12: //afti usd
                return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'SALES',$sort,'" . $icon . "',',SO'," . $params['levelid'] . ")";
                break;
            default:
                return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'" . $label . "',$sort,'" . $icon . "',',SO,SJ,CM'," . $params['levelid'] . ")";
                break;
        }
    } //end function

    public function pa($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2318,0,'Price Scheme','',0,'\\24027','$parent',0,'0',0," . $params['levelid'] . "),
        (2319,0,'Allow View Transaction PS','PA',0,'\\2402701','\\24027',0,'0',0," . $params['levelid'] . "),
        (2320,0,'Allow Click Edit Button PS','',0,'\\2402702','\\24027',0,'0',0," . $params['levelid'] . "),
        (2321,0,'Allow Click New  Button PS','',0,'\\2402703','\\24027',0,'0',0," . $params['levelid'] . "),
        (2322,0,'Allow Click Save Button PS','',0,'\\2402704','\\24027',0,'0',0," . $params['levelid'] . "),
        (2324,0,'Allow Click Delete Button PS','',0,'\\2402706','\\24027',0,'0',0," . $params['levelid'] . "),
        (2325,0,'Allow Click Print Button PS','',0,'\\2402707','\\24027',0,'0',0," . $params['levelid'] . "),
        (2326,0,'Allow Click Lock Button PS','',0,'\\2402708','\\24027',0,'0',0," . $params['levelid'] . "),
        (2327,0,'Allow Click UnLock Button PS','',0,'\\2402709','\\24027',0,'0',0," . $params['levelid'] . "),
        (2328,0,'Allow Click Post Button PS','',0,'\\2402710','\\24027',0,'0',0," . $params['levelid'] . "),
        (2329,0,'Allow Click UnPost  Button PS','',0,'\\2402711','\\24027',0,'0',0," . $params['levelid'] . "),
        (2330,1,'Allow Click Add Item PS','',0,'\\2402712','\\24027',0,'0',0," . $params['levelid'] . "),
        (2331,1,'Allow Click Edit Item PS','',0,'\\2402713','\\24027',0,'0',0," . $params['levelid'] . "),
        (2332,1,'Allow Click Delete Item PS','',0,'\\2402714','\\24027',0,'0',0," . $params['levelid'] . "),
        (3621,1,'Allow Click Void Promotion','',0,'\\24027145','\\24027',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'PA','/module/pos/pa','Price Scheme','fa fa-tags sub_menu_ico',2318," . $params['levelid'] . ")";
    } //end function

    public function so($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $folder = 'sales';
        $modulename = 'Sales Order';
        switch ($params['companyid']) {
            case 20: //proline
                $folder = 'proline';
                $modulename = 'Job Order';
                break;
            case 50: //unitech
                $folder = 'unitechindustry';
                break;
            case 60: //transpower
                $folder = 't70e33c92835b1ef8cd37fb7d031d02db';
                break;
            case 59: //roosevelt
                $modulename = 'Order Form';
                break;
        }

        $qry = " (151,0,'" . $modulename . "','',0,'\\501','$parent',0,'0',0," . $params['levelid'] . "),
        (152,0,'Allow View Transaction SO','SO',0,'\\50101','\\501',0,'0',0," . $params['levelid'] . "),
        (153,0,'Allow Click Edit Button SO','',0,'\\50102','\\501',0,'0',0," . $params['levelid'] . "),
        (154,0,'Allow Click New  Button SO','',0,'\\50103','\\501',0,'0',0," . $params['levelid'] . "),
        (155,0,'Allow Click Save Button SO','',0,'\\50104','\\501',0,'0',0," . $params['levelid'] . "),
        (157,0,'Allow Click Delete Button SO','',0,'\\50106','\\501',0,'0',0," . $params['levelid'] . "),
        (158,0,'Allow Click Print Button SO','',0,'\\50107','\\501',0,'0',0," . $params['levelid'] . "),
        (159,0,'Allow Click Lock Button SO','',0,'\\50108','\\501',0,'0',0," . $params['levelid'] . "),
        (160,0,'Allow Click UnLock Button SO','',0,'\\50109','\\501',0,'0',0," . $params['levelid'] . "),
        (161,0,'Allow Change Amount  SO','',0,'\\50110','\\501',0,'0',0," . $params['levelid'] . "),
        (162,0,'Allow Check Credit Limit SO','',0,'\\50111','\\501',0,'0',0," . $params['levelid'] . "),
        (163,0,'Allow Click Post Button SO','',0,'\\50112','\\501',0,'0',0," . $params['levelid'] . "),
        (164,0,'Allow Click UnPost  Button SO','',0,'\\50113','\\501',0,'0',0," . $params['levelid'] . "),
        (805,1,'Allow Click Add Item SO','',0,'\\50114','\\501',0,'0',0," . $params['levelid'] . "),
        (806,1,'Allow Click Edit Item SO','',0,'\\50115','\\501',0,'0',0," . $params['levelid'] . "),
        (807,1,'Allow Click Delete Item SO','',0,'\\50116','\\501',0,'0',0," . $params['levelid'] . "),
        (3593,1,'Allow Void Button','',0,'\\50118','\\501',0,'0',0," . $params['levelid'] . ")";
        switch ($params['companyid']) {
            case 17: //unihome
                //case 39: // CBBSI
                $qry = $qry . ",(2995,1,'Allow Post Non Cash','',0,'\\50117','\\501',0,'0',0," . $params['levelid'] . ")";
                break;
            case 19: // hgc
                $qry = $qry . ",(3889,1,'Allow View WH Info','',0,'\\50118','\\501',0,'0',0," . $params['levelid'] . ")";
                $qry = $qry . ",(3890,1,'Allow Click Approved Button','',0,'\\50119','\\501',0,'0',0," . $params['levelid'] . ")";
                $qry = $qry . ",(3891,1,'Allow Click Revision Button','',0,'\\50120','\\501',0,'0',0," . $params['levelid'] . ")";
                $qry = $qry . ",(4849,1,'Allow Duplicate SO','',0,'\\50122','\\501',0,'0',0," . $params['levelid'] . ")";
                $qry = $qry . ",(5347,1,'Allow View All Sales Order Transactions','',0,'\\50123','\\501',0,'0',0," . $params['levelid'] . ")";
                break;
            case 21: //kinggeorge
                $qry = $qry . ",(4037,1,'Allow Change Discount SO','',0,'\\50121','\\501',0,'0',0," . $params['levelid'] . ")";
                break;
            case 28: //xcomp
            case 36: //rozlab
                $qry = $qry . ",(4037,1,'Allow Change Discount SO','',0,'\\50121','\\501',0,'0',0," . $params['levelid'] . ")";
                break;
            case 60: //transpower
                $qry .=  ",(5489,0,'Allow Click Change Code Button','',0,'\\50122','\\501',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'SO','/module/" . $folder . "/so','" . $modulename . "','fa fa-clipboard-list sub_menu_ico',151," . $params['levelid'] . ")";
    } //end function

    public function ro($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $folder = 'm1f0e3dad99908345f7439f8ffabdffc4';
        $modulename = 'Request Order';


        $qry = " (3869,0,'" . $modulename . "','',0,'\\522','$parent',0,'0',0," . $params['levelid'] . "),
        (3870,0,'Allow View Transaction RO','RO',0,'\\52201','\\522',0,'0',0," . $params['levelid'] . "),
        (3871,0,'Allow Click Edit Button RO','',0,'\\52202','\\522',0,'0',0," . $params['levelid'] . "),
        (3872,0,'Allow Click New  Button RO','',0,'\\52203','\\522',0,'0',0," . $params['levelid'] . "),
        (3873,0,'Allow Click Save Button RO','',0,'\\52204','\\522',0,'0',0," . $params['levelid'] . "),
        (3874,0,'Allow Click Delete Button RO','',0,'\\52206','\\522',0,'0',0," . $params['levelid'] . "),
        (3875,0,'Allow Click Print Button RO','',0,'\\52207','\\522',0,'0',0," . $params['levelid'] . "),
        (3876,0,'Allow Click Lock Button RO','',0,'\\52208','\\522',0,'0',0," . $params['levelid'] . "),
        (3877,0,'Allow Click UnLock Button RO','',0,'\\52209','\\522',0,'0',0," . $params['levelid'] . "),
        (3878,0,'Allow Change Amount  RO','',0,'\\52210','\\522',0,'0',0," . $params['levelid'] . "),
        (3879,0,'Overwrite Capacity Checking','',0,'\\52211','\\522',0,'0',0," . $params['levelid'] . "),
        (3880,0,'Allow Click Post Button RO','',0,'\\52212','\\522',0,'0',0," . $params['levelid'] . "),
        (3881,0,'Allow Click UnPost  Button RO','',0,'\\52213','\\522',0,'0',0," . $params['levelid'] . "),
        (3882,1,'Allow Click Add Item RO','',0,'\\52214','\\522',0,'0',0," . $params['levelid'] . "),
        (3883,1,'Allow Click Edit Item RO','',0,'\\52215','\\522',0,'0',0," . $params['levelid'] . "),
        (3884,1,'Allow Click Delete Item RO','',0,'\\52216','\\522',0,'0',0," . $params['levelid'] . "),
        (3885,1,'Allow Void Button','',0,'\\52218','\\522',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'RO','/module/" . $folder . "/ro','" . $modulename . "','fa fa-list sub_menu_ico',3869," . $params['levelid'] . ")";
    } //end function

    public function sj($params, $parent, $sort)
    {
        $label = 'Sales Journal';
        switch ($params['companyid']) {
            case 48: //seastar
                $label = 'Waybill';
                break;
        }

        $p = $parent;
        $doc = 'sj';
        $parent = '\\' . $parent;
        $qry = "(168,0,'" . $label . "','',0,'\\502','$parent',0,'0',0," . $params['levelid'] . "),
        (169,0,'Allow View Transaction','SJ',0,'\\50201','\\502',0,'0',0," . $params['levelid'] . "),
        (170,0,'Allow Click Edit Button','',0,'\\50202','\\502',0,'0',0," . $params['levelid'] . "),
        (171,0,'Allow Click New  Button','',0,'\\50203','\\502',0,'0',0," . $params['levelid'] . "),
        (172,0,'Allow Click Save Button','',0,'\\50204','\\502',0,'0',0," . $params['levelid'] . "),
      
        (174,0,'Allow Click Delete Button','',0,'\\50206','\\502',0,'0',0," . $params['levelid'] . "),
        (175,0,'Allow Click Print Button','',0,'\\50207','\\502',0,'0',0," . $params['levelid'] . "),
        (176,0,'Allow Click Lock Button','',0,'\\50208','\\502',0,'0',0," . $params['levelid'] . "),
        (177,0,'Allow Click UnLock Button','',0,'\\50209','\\502',0,'0',0," . $params['levelid'] . "),
        (178,0,'Allow Click Post Button','',0,'\\50210','\\502',0,'0',0," . $params['levelid'] . "),
        (179,0,'Allow Click UnPost Button','',0,'\\50211','\\502',0,'0',0," . $params['levelid'] . "),
        (180,0,'Allow Change Amount','',0,'\\50213','\\502',0,'0',0," . $params['levelid'] . "),
        (181,0,'Allow Check Credit Limit','',0,'\\50214','\\502',0,'0',0," . $params['levelid'] . "),
        (182,0,'Allow SJ Amount Auto-Compute on UOM Change','',0,'\\50215','\\502',0,'0',0," . $params['levelid'] . "),
        (183,0,'Allow View Transaction Accounting','',0,'\\50216','\\502',0,'0',0," . $params['levelid'] . "),
        (802,1,'Allow Click Add Item','',0,'\\50217','\\502',0,'0',0," . $params['levelid'] . "),
        (803,1,'Allow Click Edit Item','',0,'\\50218','\\502',0,'0',0," . $params['levelid'] . "),
        (804,1,'Allow Click Delete Item','',0,'\\50219','\\502',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 15: //nathina
            case 17: //unihome
            case 20: //proline
            case 28: //xcomp
            case 39: //CBBSI
                $qry = $qry . ",(2994,1,'Allow Click Release','',0,'\\50220','\\502',0,'0',0," . $params['levelid'] . ")";
                break;
            case 26: // bee
                $qry .= ", (3886,1,'Allow Cancel Button','',0,'\\50222','\\502',0,'0',0," . $params['levelid'] . ")";
                break;
            case 10: //afti
                $qry .= ", (3578,1,'Allow Click Make Payment','',0,'\\50221','\\502',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (5482,1,'Allow Enter Expenses','',0,'\\50228','\\502',0,'0',0," . $params['levelid'] . ")";
                break;
            case 19: //housegem
                $qry .=  ",(3959,1,'Allow View WH Info','',0,'\\50223','\\502',0,'0',0," . $params['levelid'] . ")";
                break;
            case 24: //goodfound
                $qry .=  ",(2509,1,'Allow View Fields for Gate 2 Users SJ','',0,'\\50224','\\502',0,'0',0," . $params['levelid'] . ")";
                $qry .=  ",(4219,1,'Allow Overwrite due SO','',0,'\\50225','\\502',0,'0',0," . $params['levelid'] . ")";
                break;
            case 43: //mighty
                $qry .= ", (4488,1,'Allow Access Tripping Tab','',0,'\\50220','\\502',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4489,1,'Allow Access Dispatch Tab','',0,'\\50221','\\502',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4494,1,'Allow Trip Approved','',0,'\\50222','\\502',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4738,1,'Allow Trip Disapproved','',0,'\\50223','\\502',0,'0',0," . $params['levelid'] . ")";
                break;
            case 40: //cdocycles
                $qry .=  ",(4606,1,'Allow View Financing','',0,'\\50226','\\502',0,'0',0," . $params['levelid'] . ")";
                $qry .=  ",(4607,1,'Allow Edit Financing','',0,'\\50227','\\502',0,'0',0," . $params['levelid'] . ")";
                break;
            case 60: //transpower
                $qry .=  ",(5487,0,'Allow Click Change Code Button','',0,'\\50205','\\502',0,'0',0," . $params['levelid'] . ")";
                break;
        }
        $this->insertattribute($params, $qry);

        $folder = 'sales';
        switch ($params['companyid']) {
            case 26: // bee healthy
                $folder = 'bee';
                break;
            case 40: //cdo
                $folder = 'cdo';
                $doc = 'mj';
                break;
            case 47: //kitchenstar
                $folder = 'kitchenstar';
                break;
            case 48: //seastar
                $folder = 'seastar';
                break;
            case 10: //afti
                $folder = 'afti';
                break;
            case 60: //transpower
                $folder = 't70e33c92835b1ef8cd37fb7d031d02db';
                break;
            case 63: //ericco
                $label = 'Delivery Receipt';
                $folder = 'e4c3fe3674108174825a187099e7349f6';
                break;
            case 59: //roosevelt
                $label = 'Sales Invoice';
                break;
        }

        return "($sort,$p,'SJ','/module/" . $folder . "/" . $doc . "','" . $label . "','fa fa-file-invoice sub_menu_ico',168," . $params['levelid'] . ")";
    } //end function

    public function dr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4246,0,'Delivery Receipt','',0,'\\523','$parent',0,'0',0," . $params['levelid'] . "),
        (4247,0,'Allow View Transaction DR','DR',0,'\\52301','\\523',0,'0',0," . $params['levelid'] . "),
        (4248,0,'Allow Click Edit Button DR','',0,'\\52302','\\523',0,'0',0," . $params['levelid'] . "),
        (4249,0,'Allow Click New  Button DR','',0,'\\52303','\\523',0,'0',0," . $params['levelid'] . "),
        (4250,0,'Allow Click Save Button DR','',0,'\\52304','\\523',0,'0',0," . $params['levelid'] . "),
        (4251,0,'Allow Click Delete Button DR','',0,'\\52305','\\523',0,'0',0," . $params['levelid'] . "),
        (4252,0,'Allow Click Print Button DR','',0,'\\52306','\\523',0,'0',0," . $params['levelid'] . "),
        (4253,0,'Allow Click Lock Button DR','',0,'\\52307','\\523',0,'0',0," . $params['levelid'] . "),
        (4254,0,'Allow Click UnLock Button DR','',0,'\\52308','\\523',0,'0',0," . $params['levelid'] . "),
        (4255,0,'Allow Click Post Button DR','',0,'\\52309','\\523',0,'0',0," . $params['levelid'] . "),
        (4256,0,'Allow Click UnPost  Button DR','',0,'\\52310','\\523',0,'0',0," . $params['levelid'] . "),
        (4257,0,'Allow Change Amount DR','',0,'\\52311','\\523',0,'0',0," . $params['levelid'] . "),
        (4258,0,'Allow Check Credit Limit DR','',0,'\\52312','\\523',0,'0',0," . $params['levelid'] . "),
        (4259,0,'Allow Amount Auto-Compute on UOM Change','',0,'\\52313','\\523',0,'0',0," . $params['levelid'] . "),
        (4260,0,'Allow View Transaction Accounting DR','',0,'\\52314','\\523',0,'0',0," . $params['levelid'] . "),
        (4261,1,'Allow Click Add Item DR','',0,'\\52315','\\523',0,'0',0," . $params['levelid'] . "),
        (4262,1,'Allow Click Edit Item DR','',0,'\\52316','\\523',0,'0',0," . $params['levelid'] . "),
        (4263,1,'Allow Click Delete Item DR','',0,'\\52317','\\523',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DR','/module/cbbsi/dr','Delivery Receipt','fa fa-file-invoice sub_menu_ico',4246," . $params['levelid'] . ")";
    } //end function


    public function sk($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4311,0,'Sales Invoice','',0,'\\526','$parent',0,'0',0," . $params['levelid'] . "),
        (4312,0,'Allow View Transaction SK','SK',0,'\\52601','\\526',0,'0',0," . $params['levelid'] . "),
        (4313,0,'Allow Click Edit Button SK','',0,'\\52602','\\526',0,'0',0," . $params['levelid'] . "),
        (4314,0,'Allow Click New  Button SK','',0,'\\52603','\\526',0,'0',0," . $params['levelid'] . "),
        (4315,0,'Allow Click Save Button SK','',0,'\\52604','\\526',0,'0',0," . $params['levelid'] . "),
        (4316,0,'Allow Click Delete Button SK','',0,'\\52605','\\526',0,'0',0," . $params['levelid'] . "),
        (4317,0,'Allow Click Print Button SK','',0,'\\52606','\\526',0,'0',0," . $params['levelid'] . "),
        (4318,0,'Allow Click Lock Button SK','',0,'\\52607','\\526',0,'0',0," . $params['levelid'] . "),
        (4319,0,'Allow Click UnLock Button SK','',0,'\\52608','\\526',0,'0',0," . $params['levelid'] . "),
        (4320,0,'Allow Click Post Button SK','',0,'\\52609','\\526',0,'0',0," . $params['levelid'] . "),
        (4321,0,'Allow Click UnPost  Button SK','',0,'\\52610','\\526',0,'0',0," . $params['levelid'] . "),
        (4322,0,'Allow Change Amount SK','',0,'\\52611','\\526',0,'0',0," . $params['levelid'] . "),
        (4323,0,'Allow Check Credit Limit SK','',0,'\\52612','\\526',0,'0',0," . $params['levelid'] . "),
        (4324,0,'Allow Amount Auto-Compute on UOM Change','',0,'\\52613','\\526',0,'0',0," . $params['levelid'] . "),
        (4325,0,'Allow View Transaction Accounting SK','',0,'\\52614','\\526',0,'0',0," . $params['levelid'] . "),
        (4326,1,'Allow Click Add Item SK','',0,'\\52615','\\526',0,'0',0," . $params['levelid'] . "),
        (4327,1,'Allow Click Edit Item SK','',0,'\\52616','\\526',0,'0',0," . $params['levelid'] . "),
        (4328,1,'Allow Click Delete Item SK','',0,'\\52617','\\526',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SK','/module/cbbsi/sk','Sales Invoice','fa fa-file-invoice sub_menu_ico',4311," . $params['levelid'] . ")";
    } //end function

    public function dn($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4286,0,'DR Return','',0,'\\524','$parent',0,'0',0," . $params['levelid'] . "),
        (4287,0,'Allow View Transaction DN','DN',0,'\\52401','\\524',0,'0',0," . $params['levelid'] . "),
        (4288,0,'Allow Click Edit Button DN','',0,'\\52402','\\524',0,'0',0," . $params['levelid'] . "),
        (4289,0,'Allow Click New  Button DN','',0,'\\52403','\\524',0,'0',0," . $params['levelid'] . "),
        (4290,0,'Allow Click Save  Button DN','',0,'\\52404','\\524',0,'0',0," . $params['levelid'] . "),
        (4291,0,'Allow Click Delete Button DN','',0,'\\52406','\\524',0,'0',0," . $params['levelid'] . "),
        (4292,0,'Allow Click Print  Button DN','',0,'\\52407','\\524',0,'0',0," . $params['levelid'] . "),
        (4293,0,'Allow Click Lock Button DN','',0,'\\52408','\\524',0,'0',0," . $params['levelid'] . "),
        (4294,0,'Allow Click UnLock Button DN','',0,'\\52409','\\524',0,'0',0," . $params['levelid'] . "),
        (4295,0,'Allow Click Post Button DN','',0,'\\52410','\\524',0,'0',0," . $params['levelid'] . "),
        (4296,0,'Allow Click UnPost  Button DN','',0,'\\52411','\\524',0,'0',0," . $params['levelid'] . "),
        (4297,0,'Allow View Transaction Accounting DN','',0,'\\52412','\\524',0,'0',0," . $params['levelid'] . "),
        (4298,0,'Allow Change Amount DN','',0,'\\52413','\\524',0,'0',0," . $params['levelid'] . "),
        (4299,1,'Allow Click Add Item DN','',0,'\\52414','\\524',0,'0',0," . $params['levelid'] . "),
        (4300,1,'Allow Click Edit Item DN','',0,'\\52415','\\524',0,'0',0," . $params['levelid'] . "),
        (4301,1,'Allow Click Delete Item DN','',0,'\\52416','\\524',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'DN','/module/cbbsi/dn','DR Return','fa fa-sync sub_menu_ico',4286," . $params['levelid'] . ")";
    } //end function


    public function di($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4329,0,'Discrepancy Notice','',0,'\\527','$parent',0,'0',0," . $params['levelid'] . "),
        (4330,0,'Allow View Transaction DI','DI',0,'\\52618','\\527',0,'0',0," . $params['levelid'] . "),
        (4331,0,'Allow Click Edit Button DI','',0,'\\52619','\\527',0,'0',0," . $params['levelid'] . "),
        (4332,0,'Allow Click New  Button DI','',0,'\\52620','\\527',0,'0',0," . $params['levelid'] . "),
        (4333,0,'Allow Click Save  Button DI','',0,'\\52621','\\527',0,'0',0," . $params['levelid'] . "),
        (4334,0,'Allow Click Delete Button DI','',0,'\\52622','\\527',0,'0',0," . $params['levelid'] . "),
        (4335,0,'Allow Click Print  Button DI','',0,'\\52623','\\527',0,'0',0," . $params['levelid'] . "),
        (4336,0,'Allow Click Lock Button DI','',0,'\\52624','\\527',0,'0',0," . $params['levelid'] . "),
        (4337,0,'Allow Click UnLock Button DI','',0,'\\52625','\\527',0,'0',0," . $params['levelid'] . "),
        (4338,0,'Allow Change Amount DI','',0,'\\52626','\\527',0,'0',0," . $params['levelid'] . "),
        (4339,0,'Allow Click Post Button DI','',0,'\\52627','\\527',0,'0',0," . $params['levelid'] . "),
        (4340,0,'Allow Click UnPost Button DI','',0,'\\52628','\\527',0,'0',0," . $params['levelid'] . "),
        (4341,0,'Allow Click Add Item DI','',0,'\\52629','\\527',0,'0',0," . $params['levelid'] . "),
        (4342,0,'Allow Click Edit Item DI','',0,'\\52630','\\527',0,'0',0," . $params['levelid'] . "),
        (4343,0,'Allow Click Delete Item DI','',0,'\\52631','\\527',0,'0',0," . $params['levelid'] . "),
        (4344,0,'Allow View Amount DI','',0,'\\52632','\\527',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DI','/module/cbbsi/di','Discrepancy Notice','fa fa-boxes sub_menu_ico',4329," . $params['levelid'] . ")";
    } //end function


    public function rt($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4345,0,'Temporary RR','',0,'\\528','$parent',0,'0',0," . $params['levelid'] . "),
        (4346,0,'Allow View Transaction RT','RT',0,'\\52633','\\528',0,'0',0," . $params['levelid'] . "),
        (4347,0,'Allow Click Edit Button RT','',0,'\\52634','\\528',0,'0',0," . $params['levelid'] . "),
        (4348,0,'Allow Click New  Button RT','',0,'\\52635','\\528',0,'0',0," . $params['levelid'] . "),
        (4349,0,'Allow Click Save  Button RT','',0,'\\52636','\\528',0,'0',0," . $params['levelid'] . "),
        (4350,0,'Allow Click Delete Button RT','',0,'\\52637','\\528',0,'0',0," . $params['levelid'] . "),
        (4351,0,'Allow Click Print  Button RT','',0,'\\52638','\\528',0,'0',0," . $params['levelid'] . "),
        (4352,0,'Allow Click Lock Button RT','',0,'\\52639','\\528',0,'0',0," . $params['levelid'] . "),
        (4353,0,'Allow Click UnLock Button RT','',0,'\\52640','\\528',0,'0',0," . $params['levelid'] . "),
        (4354,0,'Allow Change Amount RT','',0,'\\52641','\\528',0,'0',0," . $params['levelid'] . "),
        (4355,0,'Allow Click Post Button RT','',0,'\\52642','\\528',0,'0',0," . $params['levelid'] . "),
        (4356,0,'Allow Click UnPost Button RT','',0,'\\52643','\\528',0,'0',0," . $params['levelid'] . "),
        (4357,0,'Allow Click Add Item RT','',0,'\\52644','\\528',0,'0',0," . $params['levelid'] . "),
        (4358,0,'Allow Click Edit Item RT','',0,'\\52645','\\528',0,'0',0," . $params['levelid'] . "),
        (4359,0,'Allow Click Delete Item RT','',0,'\\52646','\\528',0,'0',0," . $params['levelid'] . "),
        (4360,0,'Allow View Amount RT','',0,'\\52647','\\528',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RT','/module/cbbsi/rt','Temporary RR','fa fa-tasks sub_menu_ico',4345," . $params['levelid'] . ")";
    } //end function

    public function ck($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = " (4265,0,'Request For Sales Return','',0,'\\525','$parent',0,'0',0," . $params['levelid'] . "),
        (4266,0,'Allow View Transaction CK','CK',0,'\\52501','\\525',0,'0',0," . $params['levelid'] . "),
        (4267,0,'Allow Click Edit Button CK','',0,'\\52502','\\525',0,'0',0," . $params['levelid'] . "),
        (4268,0,'Allow Click New  Button CK','',0,'\\52503','\\525',0,'0',0," . $params['levelid'] . "),
        (4269,0,'Allow Click Save Button CK','',0,'\\52504','\\525',0,'0',0," . $params['levelid'] . "),
        (4270,0,'Allow Click Delete Button CK','',0,'\\52506','\\525',0,'0',0," . $params['levelid'] . "),
        (4271,0,'Allow Click Print Button CK','',0,'\\52507','\\525',0,'0',0," . $params['levelid'] . "),
        (4272,0,'Allow Click Lock Button CK','',0,'\\52508','\\525',0,'0',0," . $params['levelid'] . "),
        (4273,0,'Allow Click UnLock Button CK','',0,'\\52509','\\525',0,'0',0," . $params['levelid'] . "),
        (4274,0,'Allow Change Amount  CK','',0,'\\52510','\\525',0,'0',0," . $params['levelid'] . "),
        (4275,0,'Allow Check Credit Limit CK','',0,'\\52511','\\525',0,'0',0," . $params['levelid'] . "),
        (4276,0,'Allow Click Post Button CK','',0,'\\52512','\\525',0,'0',0," . $params['levelid'] . "),
        (4277,0,'Allow Click UnPost  Button CK','',0,'\\52513','\\525',0,'0',0," . $params['levelid'] . "),
        (4278,1,'Allow Click Add Item CK','',0,'\\52514','\\525',0,'0',0," . $params['levelid'] . "),
        (4279,1,'Allow Click Edit Item CK','',0,'\\52515','\\525',0,'0',0," . $params['levelid'] . "),
        (4280,1,'Allow Click Delete Item CK','',0,'\\52516','\\525',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'CK','/module/cbbsi/ck','Request For Sales Return','fa fa-clipboard-list sub_menu_ico',4265," . $params['levelid'] . ")";
    } //end function



    public function dp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = " (4370,0,'Dispatch Schedule','',0,'\\529','$parent',0,'0',0," . $params['levelid'] . "),
        (4371,0,'Allow View Transaction DP','DP',0,'\\52901','\\529',0,'0',0," . $params['levelid'] . "),
        (4372,0,'Allow Click Edit Button DP','',0,'\\52902','\\529',0,'0',0," . $params['levelid'] . "),
        (4373,0,'Allow Click New  Button DP','',0,'\\52903','\\529',0,'0',0," . $params['levelid'] . "),
        (4374,0,'Allow Click Save Button DP','',0,'\\52904','\\529',0,'0',0," . $params['levelid'] . "),
        (4375,0,'Allow Click Delete Button DP','',0,'\\52906','\\529',0,'0',0," . $params['levelid'] . "),
        (4376,0,'Allow Click Print Button DP','',0,'\\52907','\\529',0,'0',0," . $params['levelid'] . "),
        (4377,0,'Allow Click Lock Button DP','',0,'\\52908','\\529',0,'0',0," . $params['levelid'] . "),
        (4378,0,'Allow Click UnLock Button DP','',0,'\\52909','\\529',0,'0',0," . $params['levelid'] . "),
        (4379,0,'Allow Change Amount  DP','',0,'\\52910','\\529',0,'0',0," . $params['levelid'] . "),
        (4380,0,'Allow Click Credit Limit DP','',0,'\\52911','\\529',0,'0',0," . $params['levelid'] . "),
        (4381,0,'Allow Click Post Button DP','',0,'\\52912','\\529',0,'0',0," . $params['levelid'] . "),
        (4382,0,'Allow Click UnPost  Button DP','',0,'\\52913','\\529',0,'0',0," . $params['levelid'] . "),
        (4383,1,'Allow Click Add Item DP','',0,'\\52914','\\529',0,'0',0," . $params['levelid'] . "),
        (4384,1,'Allow Click Edit Item DP','',0,'\\52915','\\529',0,'0',0," . $params['levelid'] . "),
        (4385,1,'Allow Click Delete Item DP','',0,'\\52916','\\529',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'DP','/module/cbbsi/dp','Dispatch Schedule','fa fa-clipboard-list sub_menu_ico',4370," . $params['levelid'] . ")";
    } //end function

    public function bo($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3774,0,'Bad Order','',0,'\\519','$parent',0,'0',0," . $params['levelid'] . "),
        (3775,0,'Allow View Transaction BO','BO',0,'\\51901','\\519',0,'0',0," . $params['levelid'] . "),
        (3776,0,'Allow Click Edit Button BO','',0,'\\51902','\\519',0,'0',0," . $params['levelid'] . "),
        (3777,0,'Allow Click New  Button BO','',0,'\\51903','\\519',0,'0',0," . $params['levelid'] . "),
        (3778,0,'Allow Click Save Button BO','',0,'\\51904','\\519',0,'0',0," . $params['levelid'] . "),
        (3779,0,'Allow Click Delete Button BO','',0,'\\51906','\\519',0,'0',0," . $params['levelid'] . "),
        (3780,0,'Allow Click Print Button BO','',0,'\\51907','\\519',0,'0',0," . $params['levelid'] . "),
        (3781,0,'Allow Click Lock Button BO','',0,'\\51908','\\519',0,'0',0," . $params['levelid'] . "),
        (3782,0,'Allow Click UnLock Button BO','',0,'\\51909','\\519',0,'0',0," . $params['levelid'] . "),
        (3783,0,'Allow Click Post Button BO','',0,'\\51910','\\519',0,'0',0," . $params['levelid'] . "),
        (3784,0,'Allow Click UnPost  Button BO','',0,'\\51911','\\519',0,'0',0," . $params['levelid'] . "),
        (3785,0,'Allow Change Amount  BO','',0,'\\51913','\\519',0,'0',0," . $params['levelid'] . "),
        (3786,0,'Allow BO Amount Auto-Compute on UOM Change','',0,'\\51915','\\519',0,'0',0," . $params['levelid'] . "),
        (3787,0,'Allow View Transaction Accounting BO','',0,'\\51916','\\519',0,'0',0," . $params['levelid'] . "),
        (3788,1,'Allow Click Add Item BO','',0,'\\51917','\\519',0,'0',0," . $params['levelid'] . "),
        (3789,1,'Allow Click Edit Item BO','',0,'\\51918','\\519',0,'0',0," . $params['levelid'] . "),
        (3790,1,'Allow Click Delete Item BO','',0,'\\51919','\\519',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'BO','/module/sales/bo','Bad Order','fa fa-file-invoice sub_menu_ico',3774," . $params['levelid'] . ")";
    } //end function

    public function cm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(189,0,'Sales Return','',0,'\\503','$parent',0,'0',0," . $params['levelid'] . "),
        (190,0,'Allow View Transaction SR','CM',0,'\\50301','\\503',0,'0',0," . $params['levelid'] . "),
        (191,0,'Allow Click Edit Button SR','',0,'\\50302','\\503',0,'0',0," . $params['levelid'] . "),
        (192,0,'Allow Click New  Button SR','',0,'\\50303','\\503',0,'0',0," . $params['levelid'] . "),
        (193,0,'Allow Click Save  Button SR','',0,'\\50304','\\503',0,'0',0," . $params['levelid'] . "),
        (195,0,'Allow Click Delete Button SR','',0,'\\50306','\\503',0,'0',0," . $params['levelid'] . "),
        (196,0,'Allow Click Print  Button SR','',0,'\\50307','\\503',0,'0',0," . $params['levelid'] . "),
        (197,0,'Allow Click Lock Button SR','',0,'\\50308','\\503',0,'0',0," . $params['levelid'] . "),
        (198,0,'Allow Click UnLock Button SR','',0,'\\50309','\\503',0,'0',0," . $params['levelid'] . "),
        (199,0,'Allow Click Post Button SR','',0,'\\50310','\\503',0,'0',0," . $params['levelid'] . "),
        (200,0,'Allow Click UnPost  Button SR','',0,'\\50311','\\503',0,'0',0," . $params['levelid'] . "),
        (201,0,'Allow View Transaction Accounting SR','',0,'\\50312','\\503',0,'0',0," . $params['levelid'] . "),
        (202,0,'Allow Change Amount SR','',0,'\\50313','\\503',0,'0',0," . $params['levelid'] . "),
        (817,1,'Allow Click Add Item SR','',0,'\\50314','\\503',0,'0',0," . $params['levelid'] . "),
        (818,1,'Allow Click Edit Item SR','',0,'\\50315','\\503',0,'0',0," . $params['levelid'] . "),
        (819,1,'Allow Click Delete Item SR','',0,'\\50316','\\503',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5490,0,'Allow Click Change Code Button','',0,'\\50317','\\503',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $folder = 'sales';
        switch ($params['companyid']) {
            case 26: // bee healthy
                $folder = 'bee';
                break;
            case 50: //unitech
                $folder = 'unitechindustry';
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'CM','/module/" . $folder . "/cm','Sales Return','fa fa-sync sub_menu_ico',189," . $params['levelid'] . ")";
    } //end function

    public function qt($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2132,0,'Quotation','',0,'\\504','$parent',0,'0',0," . $params['levelid'] . ") ,";
        if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti, afti usd
            $qry = "(2132,0,'Service Quotation','',0,'\\504','$parent',0,'0',0," . $params['levelid'] . ") ,";
        }
        $qry = $qry . "(2133,0,'Allow View Transaction QT','QT',0,'\\50401','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2134,0,'Allow Click Edit Button QT','',0,'\\50402','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2135,0,'Allow Click New  Button QT','',0,'\\50403','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2136,0,'Allow Click Save Button QT','',0,'\\50404','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2138,0,'Allow Click Delete Button QT','',0,'\\50406','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2139,0,'Allow Click Print Button QT','',0,'\\50407','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2140,0,'Allow Click Lock Button QT','',0,'\\50408','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2141,0,'Allow Click UnLock Button QT','',0,'\\50409','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2142,0,'Allow Change Amount QT','',0,'\\50410','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2143,0,'Allow Click Post Button QT','',0,'\\50412','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2144,0,'Allow Click UnPost  Button QT','',0,'\\50413','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2145,1,'Allow Click Add Item QT','',0,'\\50414','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2146,1,'Allow Click Edit Item QT','',0,'\\50415','\\504',0,'0',0," . $params['levelid'] . ") ,
        (2147,1,'Allow Click Delete Item QT','',0,'\\50416','\\504',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
                return "($sort,$p,'QT','/module/sales/qt','Service Quotation','fa fa-receipt sub_menu_ico',2132," . $params['levelid'] . ")";
                break;
            case 20: //proline
                return "($sort,$p,'QT','/module/proline/qt','Quotation','fa fa-receipt sub_menu_ico',2132," . $params['levelid'] . ")";
                break;
            case 39: //cbbsi
                return "($sort,$p,'QT','/module/cbbsi/qt','Quotation','fa fa-receipt sub_menu_ico',2132," . $params['levelid'] . ")";
                break;
            default:
                return "($sort,$p,'QT','/module/sales/qt','Quotation','fa fa-receipt sub_menu_ico',2132," . $params['levelid'] . ")";
                break;
        }
    } //end function

    public function qs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2452,0,'Quotation','',0,'\\505','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2453,0,'Allow View Transaction QS','QS',0,'\\50501','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2454,0,'Allow Click Edit Button QS','',0,'\\50502','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2455,0,'Allow Click New  Button QS','',0,'\\50503','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2456,0,'Allow Click Save Button QS','',0,'\\50504','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2458,0,'Allow Click Delete Button QS','',0,'\\50506','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2459,0,'Allow Click Print Button QS','',0,'\\50507','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2460,0,'Allow Click Lock Button QS','',0,'\\50508','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2461,0,'Allow Click UnLock Button QS','',0,'\\50509','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2462,0,'Allow Change Amount QS','',0,'\\50510','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2463,0,'Allow Click Post Button QS','',0,'\\50512','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2464,0,'Allow Click UnPost  Button QS','',0,'\\50513','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2465,1,'Allow Click Add Item QS','',0,'\\50514','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2466,1,'Allow Click Edit Item QS','',0,'\\50515','\\505',0,'0',0," . $params['levelid'] . ") ,
        (2467,1,'Allow Click Delete Item QS','',0,'\\50516','\\505',0,'0',0," . $params['levelid'] . "),
        (2863,1,'Allow View Terms, Taxes and Charges Tab','',0,'\\50517','\\505',0,'0',0," . $params['levelid'] . "),
        (3688,1,'Allow Edit VAT Rate on Terms, Taxes and Charges Tab','',0,'\\50518','\\505',0,'0',0," . $params['levelid'] . "),
        (4162,1,'Allow View all Terms','',0,'\\50519','\\505',0,'0',0," . $params['levelid'] . "),
        (4163,1,'Allow override PO Date','',0,'\\50520','\\505',0,'0',0," . $params['levelid'] . "),
        (4050,1,'Allow View Proforma Invoice Tab','',0,'\\50521','\\505',0,'0',0," . $params['levelid'] . "),
        (4626,1,'Allow View Duplicate QS','',0,'\\50522','\\505',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'QS','/module/sales/qs','Quotation','fa fa-receipt sub_menu_ico',2452," . $params['levelid'] . ")";
    } //end function      

    public function sq($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2468,0,'Sales Order','',0,'\\506','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2469,0,'Allow View Transaction SO','SQ',0,'\\50601','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2470,0,'Allow Click Edit Button SO','',0,'\\50602','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2471,0,'Allow Click New  Button SO','',0,'\\50603','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2472,0,'Allow Click Save Button SO','',0,'\\50604','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2474,0,'Allow Click Delete Button SO','',0,'\\50606','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2475,0,'Allow Click Print Button SO','',0,'\\50607','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2476,0,'Allow Click Lock Button SO','',0,'\\50608','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2477,0,'Allow Click UnLock Button SO','',0,'\\50609','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2478,0,'Allow Click Post Button SO','',0,'\\50612','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2479,0,'Allow Click UnPost  Button SO','',0,'\\50613','\\506',0,'0',0," . $params['levelid'] . ") ,
        (2872,0,'Allow Click Make PO Button','',0,'\\50614','\\506',0,'0',0," . $params['levelid'] . ") , 
        (2874,0,'Allow Click Delivery Date','',0,'\\50615','\\506',0,'0',0," . $params['levelid'] . "), 
        (3718,0,'Allow Click Delete Items SO','',0,'\\50616','\\506',0,'0',0," . $params['levelid'] . ") ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SQ','/module/sales/sq','Sales Order','fa fa-file sub_menu_ico',2468," . $params['levelid'] . ")";
    } //end function

    public function vt($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2763,0,'Void Sales Order','',0,'\\509','$parent',0,'0',0," . $params['levelid'] . "),
        (2764,0,'Allow View Transaction VT','VT',0,'\\50901','\\509',0,'0',0," . $params['levelid'] . "),
        (2765,0,'Allow Click Edit Button VT','',0,'\\50902','\\509',0,'0',0," . $params['levelid'] . "),
        (2766,0,'Allow Click New Button VT','',0,'\\50903','\\509',0,'0',0," . $params['levelid'] . "),
        (2767,0,'Allow Click Save Button VT','',0,'\\50904','\\509',0,'0',0," . $params['levelid'] . "),
        (2769,0,'Allow Click Delete Button VT','',0,'\\50906','\\509',0,'0',0," . $params['levelid'] . "),
        (2770,0,'Allow Click Print Button VT','',0,'\\50907','\\509',0,'0',0," . $params['levelid'] . "),
        (2771,0,'Allow Click Lock Button VT','',0,'\\50908','\\509',0,'0',0," . $params['levelid'] . "),
        (2772,0,'Allow Click UnLock Button VT','',0,'\\50909','\\509',0,'0',0," . $params['levelid'] . "),
        (2773,0,'Allow Change Amount VT','',0,'\\50910','\\509',0,'0',0," . $params['levelid'] . "),
        (2774,0,'Allow Check Credit Limit VT','',0,'\\50911','\\509',0,'0',0," . $params['levelid'] . "),
        (2775,0,'Allow Click Post Button VT','',0,'\\50912','\\509',0,'0',0," . $params['levelid'] . "),
        (2776,0,'Allow Click UnPost  Button VT','',0,'\\50913','\\509',0,'0',0," . $params['levelid'] . "),
        (2777,1,'Allow Click Add Item VT','',0,'\\50914','\\509',0,'0',0," . $params['levelid'] . "),
        (2778,1,'Allow Click Edit Item VT','',0,'\\50915','\\509',0,'0',0," . $params['levelid'] . "),
        (2779,1,'Allow Click Delete Item VT','',0,'\\50916','\\509',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'VT','/module/sales/vt','Void Sales Order','fa fa-clipboard-list sub_menu_ico',2763," . $params['levelid'] . ")";
    } //end function

    public function vs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2780,0,'Void Service Sales Order','',0,'\\510','$parent',0,'0',0," . $params['levelid'] . "),
        (2781,0,'Allow View Transaction VS','VS',0,'\\51001','\\510',0,'0',0," . $params['levelid'] . "),
        (2782,0,'Allow Click Edit Button VS','',0,'\\51002','\\510',0,'0',0," . $params['levelid'] . "),
        (2783,0,'Allow Click New Button VS','',0,'\\51003','\\510',0,'0',0," . $params['levelid'] . "),
        (2784,0,'Allow Click Save Button VS','',0,'\\51004','\\510',0,'0',0," . $params['levelid'] . "),
        (2786,0,'Allow Click Delete Button VS','',0,'\\51006','\\510',0,'0',0," . $params['levelid'] . "),
        (2787,0,'Allow Click Print Button VS','',0,'\\51007','\\510',0,'0',0," . $params['levelid'] . "),
        (2788,0,'Allow Click Lock Button VS','',0,'\\51008','\\510',0,'0',0," . $params['levelid'] . "),
        (2789,0,'Allow Click UnLock Button VS','',0,'\\51009','\\510',0,'0',0," . $params['levelid'] . "),
        (2790,0,'Allow Change Amount VS','',0,'\\51010','\\510',0,'0',0," . $params['levelid'] . "),
        (2791,0,'Allow Check Credit Limit VS','',0,'\\51011','\\510',0,'0',0," . $params['levelid'] . "),
        (2792,0,'Allow Click Post Button VS','',0,'\\51012','\\510',0,'0',0," . $params['levelid'] . "),
        (2793,0,'Allow Click UnPost  Button VS','',0,'\\51013','\\510',0,'0',0," . $params['levelid'] . "),
        (2794,1,'Allow Click Add Item VS','',0,'\\51014','\\510',0,'0',0," . $params['levelid'] . "),
        (2795,1,'Allow Click Edit Item VS','',0,'\\51015','\\510',0,'0',0," . $params['levelid'] . "),
        (2796,1,'Allow Click Delete Item VS','',0,'\\51016','\\510',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'VS','/module/sales/vs','Void Service Sales Order','fa fa-clipboard-list sub_menu_ico',2780," . $params['levelid'] . ")";
    } //end function

    public function su($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2807,0,'Stock Issuance','',0,'\\511','$parent',0,'0',0," . $params['levelid'] . "),
        (2808,0,'Allow View Transaction SU','SU',0,'\\51101','\\511',0,'0',0," . $params['levelid'] . "),
        (2809,0,'Allow Click Edit Button SU','',0,'\\51102','\\511',0,'0',0," . $params['levelid'] . "),
        (2810,0,'Allow Click New Button SU','',0,'\\51103','\\511',0,'0',0," . $params['levelid'] . "),
        (2811,0,'Allow Click Save Button SU','',0,'\\51104','\\511',0,'0',0," . $params['levelid'] . "),
        (2813,0,'Allow Click Delete Button SU','',0,'\\51106','\\511',0,'0',0," . $params['levelid'] . "),
        (2814,0,'Allow Click Print Button SU','',0,'\\51107','\\511',0,'0',0," . $params['levelid'] . "),
        (2815,0,'Allow Click Lock Button SU','',0,'\\51108','\\511',0,'0',0," . $params['levelid'] . "),
        (2816,0,'Allow Click UnLock Button SU','',0,'\\51109','\\511',0,'0',0," . $params['levelid'] . "),
        (2817,0,'Allow Change Amount SU','',0,'\\51110','\\511',0,'0',0," . $params['levelid'] . "),
        (2818,0,'Allow Check Credit Limit SU','',0,'\\51111','\\511',0,'0',0," . $params['levelid'] . "),
        (2819,0,'Allow Click Post Button SU','',0,'\\51112','\\511',0,'0',0," . $params['levelid'] . "),
        (2820,0,'Allow Click UnPost  Button SU','',0,'\\51113','\\511',0,'0',0," . $params['levelid'] . "),
        (2821,1,'Allow Click Add Item SU','',0,'\\51114','\\511',0,'0',0," . $params['levelid'] . "),
        (2822,1,'Allow Click Edit Item SU','',0,'\\51115','\\511',0,'0',0," . $params['levelid'] . "),
        (2823,1,'Allow Click Delete Item SU','',0,'\\51116','\\511',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SU','/module/sales/su','Stock Issuance','fa fa-clipboard-list sub_menu_ico',2807," . $params['levelid'] . ")";
    } //end function

    public function rf($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2824,0,'Request for Replacement/Return','',0,'\\512','$parent',0,'0',0," . $params['levelid'] . "),
        (2825,0,'Allow View Transaction RF','RF',0,'\\51201','\\512',0,'0',0," . $params['levelid'] . "),
        (2826,0,'Allow Click Edit Button RF','',0,'\\51202','\\512',0,'0',0," . $params['levelid'] . "),
        (2827,0,'Allow Click New Button RF','',0,'\\51203','\\512',0,'0',0," . $params['levelid'] . "),
        (2828,0,'Allow Click Save Button RF','',0,'\\51204','\\512',0,'0',0," . $params['levelid'] . "),
        (2830,0,'Allow Click Delete Button RF','',0,'\\51206','\\512',0,'0',0," . $params['levelid'] . "),
        (2831,0,'Allow Click Print Button RF','',0,'\\51207','\\512',0,'0',0," . $params['levelid'] . "),
        (2832,0,'Allow Click Lock Button RF','',0,'\\51208','\\512',0,'0',0," . $params['levelid'] . "),
        (2833,0,'Allow Click UnLock Button RF','',0,'\\51209','\\512',0,'0',0," . $params['levelid'] . "),
        (2834,0,'Allow Change Amount RF','',0,'\\51210','\\512',0,'0',0," . $params['levelid'] . "),
        (2835,0,'Allow Check Credit Limit RF','',0,'\\51211','\\512',0,'0',0," . $params['levelid'] . "),
        (2836,0,'Allow Click Post Button RF','',0,'\\51212','\\512',0,'0',0," . $params['levelid'] . "),
        (2837,0,'Allow Click UnPost  Button RF','',0,'\\51213','\\512',0,'0',0," . $params['levelid'] . "),
        (2838,1,'Allow Click Add Item RF','',0,'\\51214','\\512',0,'0',0," . $params['levelid'] . "),
        (2839,1,'Allow Click Edit Item RF','',0,'\\51215','\\512',0,'0',0," . $params['levelid'] . "),
        (2840,1,'Allow Click Delete Item RF','',0,'\\51216','\\512',0,'0',0," . $params['levelid'] . "),
        (2841,1,'Allow View Return to Supplier Tab RF','',0,'\\51217','\\512',0,'0',0," . $params['levelid'] . "),
        (2842,1,'Allow View Return to Customer Tab RF','',0,'\\51218','\\512',0,'0',0," . $params['levelid'] . "),
        (3586,1,'Allow Edit RFN No.','',0,'\\51219','\\512',0,'0',0," . $params['levelid'] . "),
        (3587,1,'Allow View RFR Cost','',0,'\\51220','\\512',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RF','/module/sales/rf','Request for Replacement/Return','fa fa-undo-alt sub_menu_ico',2824," . $params['levelid'] . ")";
    } //end function

    public function ao($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2659,0,'Service Sales Order','',0,'\\507','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2660,0,'Allow View Transaction SO','AO',0,'\\50701','\\507',0,'0',0," . $params['levelid'] . ") ,
        (2661,0,'Allow Click Edit Button SO','',0,'\\50702','\\507',0,'0',0," . $params['levelid'] . ") ,
        (2662,0,'Allow Click New  Button SO','',0,'\\50703','\\507',0,'0',0," . $params['levelid'] . ") ,
        (2663,0,'Allow Click Save Button SO','',0,'\\50704','\\507',0,'0',0," . $params['levelid'] . ") ,
        (2665,0,'Allow Click Delete Button SO','',0,'\\50706','\\507',0,'0',0," . $params['levelid'] . ") ,
        (2666,0,'Allow Click Print Button SO','',0,'\\50707','\\507',0,'0',0," . $params['levelid'] . ") ,
        (2667,0,'Allow Click Lock Button SO','',0,'\\50708','\\507',0,'0',0," . $params['levelid'] . ") ,
        (2668,0,'Allow Click UnLock Button SO','',0,'\\50709','\\507',0,'0',0," . $params['levelid'] . ") ,
        (2669,0,'Allow Click Post Button SO','',0,'\\50712','\\507',0,'0',0," . $params['levelid'] . ") ,
        (2670,0,'Allow Click UnPost  Button SO','',0,'\\50713','\\507',0,'0',0," . $params['levelid'] . "),
        (3720,0,'Allow Click Delete Items SO','',0,'\\50714','\\507',0,'0',0," . $params['levelid'] . ") ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AO','/module/sales/ao','Service Sales Order','fa fa-file sub_menu_ico',2659," . $params['levelid'] . ")";
    } //end function     

    public function ai($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2706,0,'Service Invoice','',0,'\\508','$parent',0,'0',0," . $params['levelid'] . "),
        (2707,0,'Allow View Transaction SI','AI',0,'\\50801','\\508',0,'0',0," . $params['levelid'] . "),
        (2708,0,'Allow Click Edit Button SI','',0,'\\50802','\\508',0,'0',0," . $params['levelid'] . "),
        (2709,0,'Allow Click New  Button SI','',0,'\\50803','\\508',0,'0',0," . $params['levelid'] . "),
        (2710,0,'Allow Click Save Button SI','',0,'\\50804','\\508',0,'0',0," . $params['levelid'] . "),
        (2712,0,'Allow Click Delete Button SI','',0,'\\50806','\\508',0,'0',0," . $params['levelid'] . "),
        (2713,0,'Allow Click Print Button SI','',0,'\\50807','\\508',0,'0',0," . $params['levelid'] . "),
        (2714,0,'Allow Click Lock Button SI','',0,'\\50808','\\508',0,'0',0," . $params['levelid'] . "),
        (2715,0,'Allow Click UnLock Button SI','',0,'\\50809','\\508',0,'0',0," . $params['levelid'] . "),
        (2716,0,'Allow Click Post Button SI','',0,'\\50810','\\508',0,'0',0," . $params['levelid'] . "),
        (2717,0,'Allow Click UnPost  Button SI','',0,'\\50811','\\508',0,'0',0," . $params['levelid'] . "),
        (2718,0,'Allow Change Amount  SI','',0,'\\50813','\\508',0,'0',0," . $params['levelid'] . "),
        (2719,0,'Allow Check Credit Limit SI','',0,'\\50814','\\508',0,'0',0," . $params['levelid'] . "),
        (2720,0,'Allow SI Amount Auto-Compute on UOM Change','',0,'\\50815','\\508',0,'0',0," . $params['levelid'] . "),
        (2721,0,'Allow View Transaction Accounting SI','',0,'\\50816','\\508',0,'0',0," . $params['levelid'] . "),
        (2722,1,'Allow Click Add Item SI','',0,'\\50817','\\508',0,'0',0," . $params['levelid'] . "),
        (2723,1,'Allow Click Edit Item SI','',0,'\\50818','\\508',0,'0',0," . $params['levelid'] . "),
        (2724,1,'Allow Click Delete Item SI','',0,'\\50819','\\508',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AI','/module/sales/ai','Service Invoice','fa fa-file-invoice sub_menu_ico',2706," . $params['levelid'] . ")";
    } //end function

    public function comm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2991,0,'Commission','',0,'\\50820','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'COMMISSION','/headtable/sales/comm','Commission','fa fa-calculator sub_menu_ico',2991," . $params['levelid'] . ")";
    }
    public function parentwarehousing($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1858,0,'WAREHOUSING','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'WAREHOUSING',$sort,'fa fa-warehouse',',warehousing'," . $params['levelid'] . ")";
    } //end function

    public function pallet($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1875,1,'Pallet Masterfile','',0,'\\2401','$parent',0,0,0," . $params['levelid'] . "),
               (1876,0,'Allow View Pallet Masterfile','',0,'\\240101','\\2401',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrypallet','/tableentries/warehousingentry/entrypallet','Pallet Setup','fa fa-pallet sub_menu_ico',1875," . $params['levelid'] . ")";
    } //end function

    public function forwarder($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Delivery';

        $systype = $this->companysetup->getsystemtype($params);
        if ($systype  == 'VSCHED' || $systype  == 'ATI') {
            $label = 'Vehicle';
        }
        if ($params['companyid'] == 19) { //housegem
            $label = 'Truck';
        }

        $qry = "(1877,1,'" . $label . "','',0,'\\2402','$parent',0,0,0," . $params['levelid'] . "),
        (1878,0,'Allow View $label','',0,'\\240201','\\2402',0,'0',0," . $params['levelid'] . "),
        (1879,0,'Allow Click Edit Button $label','',0,'\\240202','\\2402',0,'0',0," . $params['levelid'] . "),
        (1880,0,'Allow Click New Button $label','',0,'\\240203','\\2402',0,'0',0," . $params['levelid'] . "),
        (1881,0,'Allow Click Save Button $label','',0,'\\240204','\\2402',0,'0',0," . $params['levelid'] . "),
        (1882,0,'Allow Click Change $label Code','',0,'\\240205','\\2402',0,'0',0," . $params['levelid'] . "),
        (1883,0,'Allow Click Delete Button $label','',0,'\\240206','\\2402',0,'0',0," . $params['levelid'] . "),
        (1884,0,'Allow Click Print Button $label','',0,'\\240207','\\2402',0,'0',0," . $params['levelid'] . ")";

        if ($params['companyid'] == 19) { //housegem
            $qry .= ",(5030,0,'Allow View Dashboard $label ','',0,'\\240208','\\2402',0,'0',0," . $params['levelid'] . ")";
        }


        $this->insertattribute($params, $qry);
        return "($sort,$p,'forwarder','/ledgergrid/warehousing/forwarder','" . $label . "','fa fa-truck sub_menu_ico',1877," . $params['levelid'] . ")";
    } //end function

    public function partrequesttype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2130,1,'Part Request Type','',0,'\\24022','$parent',0,0,0," . $params['levelid'] . "),
        (2131,0,'Allow View Part Request Type','',0,'\\2402201','\\24022',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrypartrequest','/tableentries/warehousingentry/entrypartrequest','Part Request Type','fa fa-tasks sub_menu_ico',2130," . $params['levelid'] . ")";
    } //end function


    public function checkerlocation($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2026,1,'Deposit Location','',0,'\\24012','$parent',0,0,0," . $params['levelid'] . "),
        (2027,0,'Allow View Deposit Location','',0,'\\2401201','\\24012',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrycheckerlocation','/tableentries/warehousingentry/entrycheckerlocation','Deposit Location','fa fa-users sub_menu_ico',2026," . $params['levelid'] . ")";
    } //end function


    public function pi($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2032,0,'Product Inquiry','',0,'\\24015','$parent',0,'0',0," . $params['levelid'] . "),
        (2033,0,'Allow View Product Inquiry','PI',0,'\\2401501','\\24015',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PI','/ledgergrid/warehousing/pi','Product Inquiry','fa fa-shopping-cart  sub_menu_ico',2032," . $params['levelid'] . ")";
    } //end function

    public function pl($params, $parent, $sort)
    {

        $folder = "warehousing";
        if ($params['companyid'] == 59) { // roosevelt
            $folder = "rc952c55ab9eb85660b7cab413fa7c803";
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1859,0,'Packing List','',0,'\\2403','$parent',0,'0',0," . $params['levelid'] . "),
        (1860,0,'Allow View Transaction PL','PL',0,'\\240301','\\2403',0,'0',0," . $params['levelid'] . "),
        (1861,0,'Allow Click Edit Button PL','',0,'\\240302','\\2403',0,'0',0," . $params['levelid'] . "),
        (1862,0,'Allow Click New Button PL','',0,'\\240303','\\2403',0,'0',0," . $params['levelid'] . "),
        (1863,0,'Allow Click Save Button PL','',0,'\\240304','\\2403',0,'0',0," . $params['levelid'] . "),
        (1865,0,'Allow Click Delete Button PL','',0,'\\240306','\\2403',0,'0',0," . $params['levelid'] . "),
        (1866,0,'Allow Click Print Button PL','',0,'\\240307','\\2403',0,'0',0," . $params['levelid'] . "),
        (1867,0,'Allow Click Lock Button PL','',0,'\\240308','\\2403',0,'0',0," . $params['levelid'] . "),
        (1868,0,'Allow Click UnLock Button PL','',0,'\\240309','\\2403',0,'0',0," . $params['levelid'] . "),
        (1870,0,'Allow Click Post Button PL','',0,'\\240312','\\2403',0,'0',0," . $params['levelid'] . "),
        (1871,0,'Allow Click UnPost  Button PL','',0,'\\240313','\\2403',0,'0',0," . $params['levelid'] . "),
        (1872,1,'Allow Click Add Item PL','',0,'\\240314','\\2403',0,'0',0," . $params['levelid'] . "),
        (1873,1,'Allow Click Edit Item PL','',0,'\\240315','\\2403',0,'0',0," . $params['levelid'] . "),
        (1874,1,'Allow Click Delete Item PL','',0,'\\240316','\\2403',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PL','/module/" . $folder . "/pl','Packing List','fa fa-tasks sub_menu_ico',1859," . $params['levelid'] . ")";
    } //end function

    public function rp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1886,0,'Packing List Receiving','',0,'\\2404','$parent',0,'0',0," . $params['levelid'] . "),
        (1887,0,'Allow View Transaction PL Receiving','RP',0,'\\240401','\\2404',0,'0',0," . $params['levelid'] . "),
        (1888,0,'Allow Click Edit Button PL Receiving','',0,'\\240402','\\2404',0,'0',0," . $params['levelid'] . "),
        (1889,0,'Allow Click New Button PL Receiving','',0,'\\240403','\\2404',0,'0',0," . $params['levelid'] . "),
        (1890,0,'Allow Click Save Button PL Receiving','',0,'\\240404','\\2404',0,'0',0," . $params['levelid'] . "),
        (1892,0,'Allow Click Delete Button PL Receiving','',0,'\\240406','\\2404',0,'0',0," . $params['levelid'] . "),
        (1893,0,'Allow Click Print Button PL Receiving','',0,'\\240407','\\2404',0,'0',0," . $params['levelid'] . "),
        (1894,0,'Allow Click Lock Button PL Receiving','',0,'\\240408','\\2404',0,'0',0," . $params['levelid'] . "),
        (1895,0,'Allow Click UnLock Button PL Receiving','',0,'\\240409','\\2404',0,'0',0," . $params['levelid'] . "),
        (1896,0,'Allow Click Post Button PL Receiving','',0,'\\240412','\\2404',0,'0',0," . $params['levelid'] . "),
        (1897,0,'Allow Click UnPost  Button PL Receiving','',0,'\\240413','\\2404',0,'0',0," . $params['levelid'] . "),
        (1898,1,'Allow Click Add Item PL Receiving','',0,'\\240414','\\2404',0,'0',0," . $params['levelid'] . "),
        (1899,1,'Allow Click Edit Item PL Receiving','',0,'\\240415','\\2404',0,'0',0," . $params['levelid'] . "),
        (1900,1,'Allow Click Delete Item PL Receiving','',0,'\\240416','\\2404',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RP','/module/warehousing/rp','Packing List Receiving','fa fa-boxes sub_menu_ico',1886," . $params['levelid'] . ")";
    } //end function

    public function forklift($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2090,0,'Forklift','',0,'\\24018','$parent',0,'0',0," . $params['levelid'] . "),
        (2091,0,'Allow View Forklift','forklift',0,'\\2401801','\\24018',0,'0',0," . $params['levelid'] . "),
        (2092,0,'Allow Edit Forklift','',0,'\\2401802','\\24018',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'forklift','/ledgergrid/warehousing/forklift','Forklift','fa fa-dolly sub_menu_ico',2090," . $params['levelid'] . ")";
    } //end function


    public function warehouseman($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2127,0,'Warehouse Man','',0,'\\24021','$parent',0,'0',0," . $params['levelid'] . "),
        (2128,0,'Allow View Warehouse Man','whman',0,'\\2402101','\\24021',0,'0',0," . $params['levelid'] . "),
        (2129,0,'Allow Edit Warehouse Man','',0,'\\2402102','\\24021',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehouseman','/ledgergrid/warehousing/warehouseman','Warehouse Man','fa fa-warehouse sub_menu_ico',2127," . $params['levelid'] . ")";
    } //end function


    public function sa($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1901,0,'Sales Order Dealer','',0,'\\2405','$parent',0,'0',0," . $params['levelid'] . "),
        (1902,0,'Allow View Transaction SO','SO',0,'\\240501','\\2405',0,'0',0," . $params['levelid'] . "),
        (1903,0,'Allow Click Edit Button SO','',0,'\\240502','\\2405',0,'0',0," . $params['levelid'] . "),
        (1904,0,'Allow Click New  Button SO','',0,'\\240503','\\2405',0,'0',0," . $params['levelid'] . "),
        (1905,0,'Allow Click Save Button SO','',0,'\\240504','\\2405',0,'0',0," . $params['levelid'] . "),
        (1907,0,'Allow Click Delete Button SO','',0,'\\240506','\\2405',0,'0',0," . $params['levelid'] . "),
        (1908,0,'Allow Click Print Button SO','',0,'\\240507','\\2405',0,'0',0," . $params['levelid'] . "),
        (1909,0,'Allow Click Lock Button SO','',0,'\\240508','\\2405',0,'0',0," . $params['levelid'] . "),
        (1910,0,'Allow Click UnLock Button SO','',0,'\\240509','\\2405',0,'0',0," . $params['levelid'] . "),
        (1911,0,'Allow Change Amount  SO','',0,'\\2405010','\\2405',0,'0',0," . $params['levelid'] . "),
        (1912,0,'Allow Check Credit Limit SO','',0,'\\2405011','\\2405',0,'0',0," . $params['levelid'] . "),
        (1913,0,'Allow Change of Discount','',0,'\\2405012','\\2405',0,'0',0," . $params['levelid'] . "),
        (1914,0,'Allow Click Post Button SO','',0,'\\2405013','\\2405',0,'0',0," . $params['levelid'] . "),
        (1915,0,'Allow Click UnPost  Button SO','',0,'\\2405014','\\2405',0,'0',0," . $params['levelid'] . "),
        (1916,1,'Allow Click Add Item SO','',0,'\\2405015','\\2405',0,'0',0," . $params['levelid'] . "),
        (1917,1,'Allow Click Edit Item SO','',0,'\\2405016','\\2405',0,'0',0," . $params['levelid'] . "),
        (1918,1,'Allow Click Delete Item SO','',0,'\\2405017','\\2405',0,'0',0," . $params['levelid'] . "),
        (2216,1,'Admin','',0,'\\2405018','\\2405',0,'0',0," . $params['levelid'] . "),
        (3597,1,'Allow Void Button','',0,'\\2405019','\\2405',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SA','/module/warehousing/sa','Sales Order Dealer','fa fa-tasks sub_menu_ico',1901," . $params['levelid'] . ")";
    } //end function

    public function sd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1919,0,'Sales Journal Dealer','',0,'\\2406','$parent',0,'0',0," . $params['levelid'] . "),
        (1920,0,'Allow View Transaction SJ','SJ',0,'\\240601','\\2406',0,'0',0," . $params['levelid'] . "),
        (1921,0,'Allow Click Edit Button SJ','',0,'\\240602','\\2406',0,'0',0," . $params['levelid'] . "),
        (1922,0,'Allow Click New  Button SJ','',0,'\\240603','\\2406',0,'0',0," . $params['levelid'] . "),
        (1923,0,'Allow Click Save Button SJ','',0,'\\240604','\\2406',0,'0',0," . $params['levelid'] . "),
        (1925,0,'Allow Click Delete Button SJ','',0,'\\240606','\\2406',0,'0',0," . $params['levelid'] . "),
        (1926,0,'Allow Click Print Button SJ','',0,'\\240607','\\2406',0,'0',0," . $params['levelid'] . "),
        (1927,0,'Allow Click Lock Button SJ','',0,'\\240608','\\2406',0,'0',0," . $params['levelid'] . "),
        (1928,0,'Allow Click UnLock Button SJ','',0,'\\240609','\\2406',0,'0',0," . $params['levelid'] . "),
        (1929,0,'Allow Click Post Button SJ','',0,'\\2406010','\\2406',0,'0',0," . $params['levelid'] . "),
        (1930,0,'Allow Click UnPost  Button SJ','',0,'\\2406011','\\2406',0,'0',0," . $params['levelid'] . "),
        (1931,0,'Allow Change Amount  SJ','',0,'\\2406012','\\2406',0,'0',0," . $params['levelid'] . "),
        (1932,0,'Allow Check Credit Limit SJ','',0,'\\2406013','\\2406',0,'0',0," . $params['levelid'] . "),
        (1933,0,'Allow SJ Amount Auto-Compute on UOM Change','',0,'\\2406014','\\2406',0,'0',0," . $params['levelid'] . "),
        (1934,0,'Allow View Transaction Accounting SJ','',0,'\\2406015','\\2406',0,'0',0," . $params['levelid'] . "),
        (1935,1,'Allow Click Add Item SJ','',0,'\\2406016','\\2406',0,'0',0," . $params['levelid'] . "),
        (1936,1,'Allow Click Edit Item SJ','',0,'\\2406017','\\2406',0,'0',0," . $params['levelid'] . "),
        (1937,1,'Allow Click Delete Item SJ','',0,'\\2406018','\\2406',0,'0',0," . $params['levelid'] . "),
        (2725,1,'Admin','',0,'\\2406020','\\2406',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SD','/module/warehousing/sd','Sales Journal Dealer','fa fa-tasks sub_menu_ico',1919," . $params['levelid'] . ")";
    } //end function

    public function sb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1938,0,'Sales Order Branch','',0,'\\2407','$parent',0,'0',0," . $params['levelid'] . "),
        (1939,0,'Allow View Transaction SO','SO',0,'\\240701','\\2407',0,'0',0," . $params['levelid'] . "),
        (1940,0,'Allow Click Edit Button SO','',0,'\\240702','\\2407',0,'0',0," . $params['levelid'] . "),
        (1941,0,'Allow Click New  Button SO','',0,'\\240703','\\2407',0,'0',0," . $params['levelid'] . "),
        (1942,0,'Allow Click Save Button SO','',0,'\\240704','\\2407',0,'0',0," . $params['levelid'] . "),
        (1944,0,'Allow Click Delete Button SO','',0,'\\240706','\\2407',0,'0',0," . $params['levelid'] . "),
        (1945,0,'Allow Click Print Button SO','',0,'\\240707','\\2407',0,'0',0," . $params['levelid'] . "),
        (1946,0,'Allow Click Lock Button SO','',0,'\\240708','\\2407',0,'0',0," . $params['levelid'] . "),
        (1947,0,'Allow Click UnLock Button SO','',0,'\\240709','\\2407',0,'0',0," . $params['levelid'] . "),
        (1948,0,'Allow Change Amount  SO','',0,'\\2407010','\\2407',0,'0',0," . $params['levelid'] . "),
        (1949,0,'Allow Check Credit Limit SO','',0,'\\2407011','\\2407',0,'0',0," . $params['levelid'] . "),
        (1950,0,'Allow Change of Discount','',0,'\\2407012','\\2407',0,'0',0," . $params['levelid'] . "),
        (1951,0,'Allow Click Post Button SO','',0,'\\2407013','\\2407',0,'0',0," . $params['levelid'] . "),
        (1952,0,'Allow Click UnPost  Button SO','',0,'\\2407014','\\2407',0,'0',0," . $params['levelid'] . "),
        (1953,1,'Allow Click Add Item SO','',0,'\\2407015','\\2407',0,'0',0," . $params['levelid'] . "),
        (1954,1,'Allow Click Edit Item SO','',0,'\\2407016','\\2407',0,'0',0," . $params['levelid'] . "),
        (1955,1,'Allow Click Delete Item SO','',0,'\\2407017','\\2407',0,'0',0," . $params['levelid'] . "),
        (2217,1,'Admin','',0,'\\2407018','\\2407',0,'0',0," . $params['levelid'] . "),
        (3598,1,'Allow Void Button','',0,'\\2407019','\\2407',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SB','/module/warehousing/sb','Sales Order Branch','fa fa-tasks sub_menu_ico',1938," . $params['levelid'] . ")";
    } //end function

    public function se($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1956,0,'Sales Journal Branch','',0,'\\2408','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1957,0,'Allow View Transaction SJ','SJ',0,'\\240801','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1958,0,'Allow Click Edit Button SJ','',0,'\\240802','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1959,0,'Allow Click New  Button SJ','',0,'\\240803','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1960,0,'Allow Click Save Button SJ','',0,'\\240804','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1962,0,'Allow Click Delete Button SJ','',0,'\\240806','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1963,0,'Allow Click Print Button SJ','',0,'\\240807','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1964,0,'Allow Click Lock Button SJ','',0,'\\240808','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1965,0,'Allow Click UnLock Button SJ','',0,'\\240809','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1966,0,'Allow Click Post Button SJ','',0,'\\2408010','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1977,0,'Allow Click UnPost  Button SJ','',0,'\\2408011','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1978,0,'Allow Change Amount  SJ','',0,'\\2408012','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1979,0,'Allow Check Credit Limit SJ','',0,'\\2408013','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1980,0,'Allow SJ Amount Auto-Compute on UOM Change','',0,'\\2408014','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1981,0,'Allow View Transaction Accounting SJ','',0,'\\2408015','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1982,1,'Allow Click Add Item SJ','',0,'\\2408016','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1983,1,'Allow Click Edit Item SJ','',0,'\\2408017','\\2408',0,'0',0," . $params['levelid'] . ") ,
        (1984,1,'Allow Click Delete Item SJ','',0,'\\2408018','\\2408',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SE','/module/warehousing/se','Sales Journal Branch','fa fa-tasks sub_menu_ico',1956," . $params['levelid'] . ")";
    } //end function

    public function sc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1985,0,'Sales Order Online','',0,'\\2409','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1986,0,'Allow View Transaction SO','SO',0,'\\240901','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1987,0,'Allow Click Edit Button SO','',0,'\\240902','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1988,0,'Allow Click New  Button SO','',0,'\\240903','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1989,0,'Allow Click Save Button SO','',0,'\\240904','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1991,0,'Allow Click Delete Button SO','',0,'\\240906','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1992,0,'Allow Click Print Button SO','',0,'\\240907','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1993,0,'Allow Click Lock Button SO','',0,'\\240908','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1994,0,'Allow Click UnLock Button SO','',0,'\\240909','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1995,0,'Allow Change Amount  SO','',0,'\\2409010','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1996,0,'Allow Check Credit Limit SO','',0,'\\2409011','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1997,0,'Allow Change of Discount','',0,'\\2409012','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1998,0,'Allow Click Post Button SO','',0,'\\2409013','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (1999,0,'Allow Click UnPost  Button SO','',0,'\\2409014','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (2000,1,'Allow Click Add Item SO','',0,'\\2409015','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (2001,1,'Allow Click Edit Item SO','',0,'\\2409016','\\2409',0,'0',0," . $params['levelid'] . ") ,
        (2002,1,'Allow Click Delete Item SO','',0,'\\2409017','\\2409',0,'0',0," . $params['levelid'] . "),
        (2218,1,'Admin','',0,'\\2409018','\\2409',0,'0',0," . $params['levelid'] . "),
        (3599,1,'Allow Void Button','',0,'\\2409019','\\2409',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SC','/module/warehousing/sc','Sales Order Online','fa fa-tasks sub_menu_ico',1985," . $params['levelid'] . ")";
    } //end function


    public function sf($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2003,0,'Sales Journal Online','',0,'\\24010','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2004,0,'Allow View Transaction SJ','SJ',0,'\\2401001','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2005,0,'Allow Click Edit Button SJ','',0,'\\2401002','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2006,0,'Allow Click New  Button SJ','',0,'\\2401003','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2007,0,'Allow Click Save Button SJ','',0,'\\2401004','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2009,0,'Allow Click Delete Button SJ','',0,'\\2401006','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2010,0,'Allow Click Print Button SJ','',0,'\\2401007','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2011,0,'Allow Click Lock Button SJ','',0,'\\2401008','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2012,0,'Allow Click UnLock Button SJ','',0,'\\2401009','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2013,0,'Allow Click Post Button SJ','',0,'\\24010010','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2014,0,'Allow Click UnPost  Button SJ','',0,'\\24010011','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2015,0,'Allow Change Amount  SJ','',0,'\\24010012','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2016,0,'Allow Check Credit Limit SJ','',0,'\\24010013','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2017,0,'Allow SJ Amount Auto-Compute on UOM Change','',0,'\\24010014','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2018,0,'Allow View Transaction Accounting SJ','',0,'\\24010015','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2019,1,'Allow Click Add Item SJ','',0,'\\24010016','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2020,1,'Allow Click Edit Item SJ','',0,'\\24010017','\\24010',0,'0',0," . $params['levelid'] . ") ,
        (2021,1,'Allow Click Delete Item SJ','',0,'\\24010018','\\24010',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SF','/module/warehousing/sf','Sales Journal Online','fa fa-tasks sub_menu_ico',2003," . $params['levelid'] . ")";
    } //end function

    public function sg($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2150,0,'Special Parts Request','',0,'\\24024','$parent',0,'0',0," . $params['levelid'] . "),
        (2151,0,'Allow View Transaction','SG',0,'\\2402401','\\24024',0,'0',0," . $params['levelid'] . "),
        (2152,0,'Allow Click Edit Button','',0,'\\2402402','\\24024',0,'0',0," . $params['levelid'] . "),
        (2153,0,'Allow Click New  Button','',0,'\\2402403','\\24024',0,'0',0," . $params['levelid'] . "),
        (2154,0,'Allow Click Save Button','',0,'\\2402404','\\24024',0,'0',0," . $params['levelid'] . "),
        (2156,0,'Allow Click Delete Button','',0,'\\2402406','\\24024',0,'0',0," . $params['levelid'] . "),
        (2157,0,'Allow Click Print Button','',0,'\\2402407','\\24024',0,'0',0," . $params['levelid'] . "),
        (2158,0,'Allow Click Lock Button','',0,'\\2402408','\\24024',0,'0',0," . $params['levelid'] . "),
        (2159,0,'Allow Click UnLock Button','',0,'\\2402409','\\24024',0,'0',0," . $params['levelid'] . "),
        (2160,0,'Allow Change Amount','',0,'\\2402410','\\24024',0,'0',0," . $params['levelid'] . "),
        (2161,0,'Allow Check Credit Limit','',0,'\\2402411','\\24024',0,'0',0," . $params['levelid'] . "),
        (2162,0,'Allow Change of Discount','',0,'\\2402412','\\24024',0,'0',0," . $params['levelid'] . "),
        (2163,0,'Allow Click Post Button','',0,'\\2402413','\\24024',0,'0',0," . $params['levelid'] . "),
        (2164,0,'Allow Click UnPost  Button','',0,'\\2402414','\\24024',0,'0',0," . $params['levelid'] . "),
        (2165,1,'Allow Click Add Item','',0,'\\2402415','\\24024',0,'0',0," . $params['levelid'] . "),
        (2166,1,'Allow Click Edit Item','',0,'\\2402416','\\24024',0,'0',0," . $params['levelid'] . "),
        (2167,1,'Allow Click Delete Item','',0,'\\2402417','\\24024',0,'0',0," . $params['levelid'] . "),
        (2219,1,'Admin','',0,'\\2402418','\\24024',0,'0',0," . $params['levelid'] . "),
        (3622,1,'Allow Void Button','',0,'\\2402419','\\24024',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SG','/module/warehousing/sg','Special Parts Request','fa fa-tasks sub_menu_ico',2150," . $params['levelid'] . ")";
    } //end function

    public function sh($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2168,0,'Special Parts Issuance','',0,'\\24025','$parent',0,'0',0," . $params['levelid'] . "),
        (2169,0,'Allow View Transaction','SH',0,'\\2402501','\\24025',0,'0',0," . $params['levelid'] . "),
        (2170,0,'Allow Click Edit Button','',0,'\\2402502','\\24025',0,'0',0," . $params['levelid'] . "),
        (2171,0,'Allow Click New  Button','',0,'\\2402503','\\24025',0,'0',0," . $params['levelid'] . "),
        (2172,0,'Allow Click Save Button','',0,'\\2402504','\\24025',0,'0',0," . $params['levelid'] . "),
        (2174,0,'Allow Click Delete Button','',0,'\\2402506','\\24025',0,'0',0," . $params['levelid'] . "),
        (2175,0,'Allow Click Print Button','',0,'\\2402507','\\24025',0,'0',0," . $params['levelid'] . "),
        (2176,0,'Allow Click Lock Button','',0,'\\2402508','\\24025',0,'0',0," . $params['levelid'] . "),
        (2177,0,'Allow Click UnLock Button','',0,'\\2402509','\\24025',0,'0',0," . $params['levelid'] . "),
        (2178,0,'Allow Click Post Button','',0,'\\2402510','\\24025',0,'0',0," . $params['levelid'] . "),
        (2179,0,'Allow Click UnPost  Button','',0,'\\2402511','\\24025',0,'0',0," . $params['levelid'] . "),
        (2180,0,'Allow Change Amount','',0,'\\2402512','\\24025',0,'0',0," . $params['levelid'] . "),
        (2181,0,'Allow Check Credit Limit','',0,'\\2402513','\\24025',0,'0',0," . $params['levelid'] . "),
        (2182,0,'Allow Amount Auto-Compute on UOM Change','',0,'\\2402514','\\24025',0,'0',0," . $params['levelid'] . "),
        (2183,0,'Allow View Transaction Accounting','',0,'\\2402515','\\24025',0,'0',0," . $params['levelid'] . "),
        (2184,1,'Allow Click Add Item','',0,'\\2402516','\\24025',0,'0',0," . $params['levelid'] . "),
        (2185,1,'Allow Click Edit Item','',0,'\\2402517','\\24025',0,'0',0," . $params['levelid'] . "),
        (2186,1,'Allow Click Delete Item','',0,'\\2402518','\\24025',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SH','/module/warehousing/sh','Special Parts Issuance','fa fa-tasks sub_menu_ico',2168," . $params['levelid'] . ")";
    } //end function

    public function si($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2187,0,'Special Parts Return','',0,'\\24026','$parent',0,'0',0," . $params['levelid'] . "),
        (2188,0,'Allow View Transaction','SI',0,'\\2402601','\\24026',0,'0',0," . $params['levelid'] . "),
        (2189,0,'Allow Click Edit Button','',0,'\\2402602','\\24026',0,'0',0," . $params['levelid'] . "),
        (2190,0,'Allow Click New  Button','',0,'\\2402603','\\24026',0,'0',0," . $params['levelid'] . "),
        (2191,0,'Allow Click Save  Button','',0,'\\2402604','\\24026',0,'0',0," . $params['levelid'] . "),
        (2193,0,'Allow Click Delete Button','',0,'\\2402606','\\24026',0,'0',0," . $params['levelid'] . "),
        (2194,0,'Allow Click Print  Button','',0,'\\2402607','\\24026',0,'0',0," . $params['levelid'] . "),
        (2195,0,'Allow Click Lock Button','',0,'\\2402608','\\24026',0,'0',0," . $params['levelid'] . "),
        (2196,0,'Allow Click UnLock Button','',0,'\\2402609','\\24026',0,'0',0," . $params['levelid'] . "),
        (2197,0,'Allow Click Post Button','',0,'\\2402610','\\24026',0,'0',0," . $params['levelid'] . "),
        (2198,0,'Allow Click UnPost  Button','',0,'\\2402611','\\24026',0,'0',0," . $params['levelid'] . "),
        (2199,0,'Allow View Transaction Accounting','',0,'\\2402612','\\24026',0,'0',0," . $params['levelid'] . "),
        (2200,0,'Allow Change Amount','',0,'\\2402613','\\24026',0,'0',0," . $params['levelid'] . "),
        (2201,1,'Allow Click Add Item','',0,'\\2402614','\\24026',0,'0',0," . $params['levelid'] . "),
        (2202,1,'Allow Click Edit Item','',0,'\\2402615','\\24026',0,'0',0," . $params['levelid'] . "),
        (2203,1,'Allow Click Delete Item','',0,'\\2402616','\\24026',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SI','/module/warehousing/si','Special Parts Return','fa fa-sync sub_menu_ico',2188," . $params['levelid'] . ")";
    } //end function

    public function warehousecontroller($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2022,0,'Inventory Controller','',0,'\\24011','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2023,0,'Allow View Inventory Controller','whctrl',0,'\\2401101','\\24011',0,'0',0," . $params['levelid'] . ") ,
        (2024,0,'Allow Edit Inventory Controller','',0,'\\2401102','\\24011',0,'0',0," . $params['levelid'] . ") ,
        (2025,0,'Allow Save Inventory Controller','',0,'\\2401104','\\24011',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehousecontroller','/ledgergrid/warehousing/warehousecontroller','Inventory Controller','fa fa-pallet sub_menu_ico',2022," . $params['levelid'] . ")";
    } //end function

    public function warehousepicker($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2028,0,'Warehouse Picker','',0,'\\24013','$parent',0,'0',0," . $params['levelid'] . "),
        (2029,0,'Allow View Warehouse Picker','whpckr',0,'\\2401301','\\24013',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehousepicker','/ledgergrid/warehousing/warehousepicker','Warehouse Picker','fa fa-box-open sub_menu_ico',2028," . $params['levelid'] . ")";
    } //end function

    public function warehousechecker($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2030,0,'Warehouse Checker','',0,'\\24014','$parent',0,'0',0," . $params['levelid'] . "),
        (2031,0,'Allow View Warehouse Picker','whchcr',0,'\\2401401','\\24014',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehousechecker','/ledgergrid/warehousing/warehousechecker','Warehouse Checker','fa fa-user-check sub_menu_ico',2030," . $params['levelid'] . ")";
    } //end function

    public function replenishpallet($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2542,0,'Replenish per Pallet','',0,'\\24030','$parent',0,'0',0," . $params['levelid'] . "),
        (2543,0,'Allow View Replenish per Pallet','reppallet',0,'\\2403001','\\24030',0,'0',0," . $params['levelid'] . "),
        (2544,0,'Allow Post Replenish per Pallet','',0,'\\2403002','\\24030',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'replenishpallet','/ledgergrid/warehousing/replenishpallet','Replenish per Pallet','fa fa-pallet sub_menu_ico', 2542," . $params['levelid'] . ")";
    } //end function

    public function replenishitem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2545,0,'Replenish per Item','',0,'\\24031','$parent',0,'0',0," . $params['levelid'] . "),
        (2546,0,'Allow View Replenish per Item','repitem',0,'\\2403101','\\24031',0,'0',0," . $params['levelid'] . "),
        (2547,0,'Allow Post Replenish per Item','',0,'\\2403102','\\24031',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'replenishitem','/ledgergrid/warehousing/replenishitem','Replenish per Item','fa fa-list-alt sub_menu_ico', 2545," . $params['levelid'] . ")";
    } //end function

    public function dispatching($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2034,0,'Dispatching','',0,'\\24016','$parent',0,'0',0," . $params['levelid'] . "),
        (2035,0,'Allow View Dispatching','dispatch',0,'\\2401601','\\24016',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'dispatching','/ledgergrid/warehousing/dispatching','Dispatching','fa fa-box sub_menu_ico',2034," . $params['levelid'] . ")";
    } //end function


    public function logistics($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2036,0,'Logistics','',0,'\\24017','$parent',0,'0',0," . $params['levelid'] . "),
        (2037,0,'Allow View Logistics','whctrl',0,'\\2401701','\\24017',0,'0',0," . $params['levelid'] . "),
        (2038,0,'Allow Edit Logistics','',0,'\\2401702','\\24017',0,'0',0," . $params['levelid'] . "),
        (2451,0,'Allow Print Logistics','',0,'\\2401703','\\24017',0,'0',0," . $params['levelid'] . "),
        (2039,0,'Allow Post Logistics','',0,'\\2401704','\\24017',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'logistics','/ledgergrid/warehousing/logistics','Logistics','fa fa-truck-loading sub_menu_ico',2036," . $params['levelid'] . ")";
    } //end function

    public function wa($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2093,0,'Warranty Request','',0,'\\24019','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2094,0,'Allow View Transaction WR','WA',0,'\\2401901','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2095,0,'Allow Click Edit Button WR','',0,'\\2401902','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2096,0,'Allow Click New Button WR','',0,'\\2401903','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2097,0,'Allow Click Save Button WR','',0,'\\2401904','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2099,0,'Allow Click Delete Button WR','',0,'\\2401906','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2100,0,'Allow Click Print Button WR','',0,'\\2401907','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2101,0,'Allow Click Lock Button WR','',0,'\\2401908','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2102,0,'Allow Click UnLock Button WR','',0,'\\2401909','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2103,0,'Allow Change Cost WR','',0,'\\2401910','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2104,0,'Allow Click Post Button WR','',0,'\\2401912','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2105,0,'Allow Click UnPost  Button WR','',0,'\\2401913','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2106,1,'Allow Click Add Item WR','',0,'\\2401914','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2107,1,'Allow Click Edit Item WR','',0,'\\2401915','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2108,1,'Allow Click Delete Item WR','',0,'\\2401916','\\24019',0,'0',0," . $params['levelid'] . ") ,
        (2109,1,'Allow View Cost','',0,'\\2401917','\\24019',0,'0',0," . $params['levelid'] . "),
        (3596,1,'Allow Void Button','',0,'\\2401918','\\24019',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'WA','/module/warehousing/wa','Warranty Request','fa fa-tasks sub_menu_ico',2093," . $params['levelid'] . ")";
    } //end function

    public function wb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2110,0,'Warranty Receiving','',0,'\\24020','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2111,0,'Allow View Transaction WRR','WB',0,'\\2402001','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2112,0,'Allow Click Edit Button WRR','',0,'\\2402002','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2113,0,'Allow Click New Button WRR','',0,'\\2402003','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2114,0,'Allow Click Save Button WRR','',0,'\\2402004','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2116,0,'Allow Click Delete Button WRR','',0,'\\2402006','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2117,0,'Allow Click Print Button WRR','',0,'\\2402007','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2118,0,'Allow Click Lock Button WRR','',0,'\\2402008','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2119,0,'Allow Click UnLock Button WRR','',0,'\\2402009','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2120,0,'Allow Click Post Button WRR','',0,'\\2402010','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2121,0,'Allow Click UnPost Button WRR','',0,'\\2402011','\\402',0,'0',0," . $params['levelid'] . ") ,
        (2122,0,'Allow View Transaction accounting WRR','',0,'\\2402012','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2123,0,'Allow Change Amount WRR','',0,'\\2402013','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2124,1,'Allow Click Add Item WRR','',0,'\\2402014','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2125,1,'Allow Click Edit Item WRR','',0,'\\2402015','\\24020',0,'0',0," . $params['levelid'] . ") ,
        (2126,1,'Allow Click Delete Item WRR','',0,'\\2402016','\\24020',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'WB','/module/warehousing/wb','Warranty Receiving','fa fa-tasks sub_menu_ico',2110," . $params['levelid'] . ")";
    } //end function

    public function incentivesgenerator($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2518,0,'Incentives Generator','',0,'\\24033','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'incentivesgenerator','/headtable/warehousingentry/incentivesgenerator','Incentives Generator','fa fa-calculator sub_menu_ico',2518," . $params['levelid'] . ")";
    } //end function

    public function parentconsignment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2333,0,'CONSIGNMENT','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'CONSIGNMENT',$sort,'fa fa-clipboard-check',',consignment'," . $params['levelid'] . ")";
    } //end function

    public function cn($params, $parent, $sort)
    { // consignment request
        $p = $parent;
        $parent = '\\' . $parent; // 2334
        $qry = " (2334,0,'Consignment Request','',0,'\\2801','$parent',0,'0',0," . $params['levelid'] . "),
        (2335,0,'Allow View Consignment Request','CN',0,'\\280101','\\2801',0,'0',0," . $params['levelid'] . "),
        (2336,0,'Allow Click Edit Button CN','',0,'\\280102','\\2801',0,'0',0," . $params['levelid'] . "),
        (2337,0,'Allow Click New  Button CN','',0,'\\280103','\\2801',0,'0',0," . $params['levelid'] . "),
        (2338,0,'Allow Click Save Button CN','',0,'\\280104','\\2801',0,'0',0," . $params['levelid'] . "),
        (2340,0,'Allow Click Delete Button CN','',0,'\\280106','\\2801',0,'0',0," . $params['levelid'] . "),
        (2341,0,'Allow Click Print Button CN','',0,'\\280107','\\2801',0,'0',0," . $params['levelid'] . "),
        (2342,0,'Allow Click Lock Button CN','',0,'\\280108','\\2801',0,'0',0," . $params['levelid'] . "),
        (2343,0,'Allow Click UnLock Button CN','',0,'\\280109','\\2801',0,'0',0," . $params['levelid'] . "),
        (2344,0,'Allow Change Amount  CN','',0,'\\280110','\\2801',0,'0',0," . $params['levelid'] . "),
        (2345,0,'Allow Check Credit Limit CN','',0,'\\280111','\\2801',0,'0',0," . $params['levelid'] . "),
        (2346,0,'Allow Click Post Button CN','',0,'\\280112','\\2801',0,'0',0," . $params['levelid'] . "),
        (2347,0,'Allow Click UnPost  Button CN','',0,'\\280113','\\2801',0,'0',0," . $params['levelid'] . "),
        (2348,1,'Allow Click Add Item CN','',0,'\\280114','\\2801',0,'0',0," . $params['levelid'] . "),
        (2349,1,'Allow Click Edit Item CN','',0,'\\280115','\\2801',0,'0',0," . $params['levelid'] . "),
        (2350,1,'Allow Click Delete Item CN','',0,'\\280116','\\2801',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CN','/module/consignment/cn','Consignment Request','fa fa-toolbox sub_menu_ico',2334," . $params['levelid'] . ")";
    } //end function

    public function co($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2351,0,'Consignment DR','',0,'\\2802','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2352,0,'Allow View Transaction CO','CO',0,'\\280201','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2353,0,'Allow Click Edit Button  CO','',0,'\\280202','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2354,0,'Allow Click New Button CO','',0,'\\280203','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2355,0,'Allow Click Save Button CO','',0,'\\280204','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2357,0,'Allow Click Delete Button CO','',0,'\\280206','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2358,0,'Allow Click Print Button CO','',0,'\\280207','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2359,0,'Allow Click Lock Button CO','',0,'\\280208','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2360,0,'Allow Click UnLock Button CO','',0,'\\280209','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2361,0,'Allow Click Post Button CO','',0,'\\280210','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2362,0,'Allow Click UnPost Button CO','',0,'\\280211','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2363,1,'Allow Click Add Item CO','',0,'\\280212','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2364,1,'Allow Click Edit Item CO','',0,'\\280213','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2365,1,'Allow Click Delete Item CO','',0,'\\280215','\\2802',0,'0',0," . $params['levelid'] . ") ,
        (2366,1,'Allow Change Amount CO','',0,'\\280216','\\2802',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CO','/module/consignment/co','Consignment DR','fa fa-dolly-flatbed sub_menu_ico',2351," . $params['levelid'] . ")";
    } //end function

    public function cs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2382,0,'Consignment Sales','',0,'\\2803','$parent',0,'0',0," . $params['levelid'] . "),
        (2383,0,'Allow View Transaction CS','CS',0,'\\280301','\\2803',0,'0',0," . $params['levelid'] . "),
        (2384,0,'Allow Click Edit Button CS','',0,'\\280302','\\2803',0,'0',0," . $params['levelid'] . "),
        (2385,0,'Allow Click New  Button CS','',0,'\\280303','\\2803',0,'0',0," . $params['levelid'] . "),
        (2386,0,'Allow Click Save Button CS','',0,'\\280304','\\2803',0,'0',0," . $params['levelid'] . "),
        (2388,0,'Allow Click Delete Button CS','',0,'\\280306','\\2803',0,'0',0," . $params['levelid'] . "),
        (2389,0,'Allow Click Print Button CS','',0,'\\280307','\\2803',0,'0',0," . $params['levelid'] . "),
        (2390,0,'Allow Click Lock Button CS','',0,'\\280308','\\2803',0,'0',0," . $params['levelid'] . "),
        (2391,0,'Allow Click UnLock Button CS','',0,'\\280309','\\2803',0,'0',0," . $params['levelid'] . "),
        (2392,0,'Allow Click Post Button CS','',0,'\\280310','\\2803',0,'0',0," . $params['levelid'] . "),
        (2393,0,'Allow Click UnPost  Button CS','',0,'\\280311','\\2803',0,'0',0," . $params['levelid'] . "),
        (2394,0,'Allow Change Amount  CS','',0,'\\280313','\\2803',0,'0',0," . $params['levelid'] . "),
        (2395,0,'Allow Check Credit Limit CS','',0,'\\280314','\\2803',0,'0',0," . $params['levelid'] . "),
        (2396,0,'Allow CS Amount Auto-Compute on UOM Change','',0,'\\280315','\\2803',0,'0',0," . $params['levelid'] . "),
        (2397,0,'Allow View Transaction Accounting CS','',0,'\\280316','\\2803',0,'0',0," . $params['levelid'] . "),
        (2398,1,'Allow Click Add Item CS','',0,'\\280317','\\2803',0,'0',0," . $params['levelid'] . "),
        (2399,1,'Allow Click Edit Item CS','',0,'\\280318','\\2803',0,'0',0," . $params['levelid'] . "),
        (2400,1,'Allow Click Delete Item CS','',0,'\\280319','\\2803',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CS','/module/consignment/cs','Consignment Sales','fa fa-file-invoice sub_menu_ico',2382," . $params['levelid'] . ")";
    } //end function


    public function cc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4685,0,'Construction Order','',0,'\\2804','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4686,0,'Allow View Transaction CC','CO',0,'\\280401','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4687,0,'Allow Click Edit Button  CC','',0,'\\280402','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4688,0,'Allow Click New Button CC','',0,'\\280403','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4689,0,'Allow Click Save Button CC','',0,'\\280404','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4690,0,'Allow Click Delete Button CC','',0,'\\280405','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4691,0,'Allow Click Print Button CC','',0,'\\280406','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4692,0,'Allow Click Lock Button CC','',0,'\\280407','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4693,0,'Allow Click UnLock Button CC','',0,'\\280408','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4694,0,'Allow Click Post Button CC','',0,'\\280409','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4695,0,'Allow Click UnPost Button CC','',0,'\\280410','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4696,1,'Allow Click Add Item CC','',0,'\\280411','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4697,1,'Allow Click Edit Item CC','',0,'\\280412','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4698,1,'Allow Click Delete Item CC','',0,'\\280413','\\2804',0,'0',0," . $params['levelid'] . ") ,
        (4699,1,'Allow Change Amount CC','',0,'\\280414','\\2804',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CC','/module/realestate/cc','Construction Order','fa fa-dolly-flatbed sub_menu_ico',4685," . $params['levelid'] . ")";
    } //end function

    public function deliverytype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $label = 'Delivery Type Masterfile';
        if ($params['companyid'] == 19) { //housegem
            $label = 'Truck Type';
        }

        $qry = "(2148,1,'" . $label . "','',0,'\\24023','$parent',0,0,0," . $params['levelid'] . "),
        (2149,0,'Allow View " . $label . "','',0,'\\2402301','\\24023',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrydeliverytype','/tableentries/warehousingentry/entrydeliverytype','" . $label . "','fas fa-shipping-fast sub_menu_ico',2148," . $params['levelid'] . ")";
    } //end function

    public function whrem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2401,1,'Warehouse Remarks','',0,'\\24029','$parent',0,0,0," . $params['levelid'] . "),
        (2402,0,'Allow View Warehouse Remarks','',0,'\\2402901','\\24029',0,'0',0," . $params['levelid'] . "),
        (2403,0,'Allow Edit Warehouse Remarks','',0,'\\2402902','\\24029',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrywhrem','/tableentries/warehousingentry/entrywhrem','Warehouse Remarks','fas fa-tasks sub_menu_ico', 2401," . $params['levelid'] . ")";
    } //end function

    public function parentmasterfile($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1,0,'MASTER FILE','',0,'$parent','\\',0,'',0," . $params['levelid'] . ")";

        if ($params['companyid'] == 34) { //evergreen
            $qry .= ",(1730,1,'View Attach Documents','',0,'\\810','$parent',0,'0',0," . $params['levelid'] . "),
            (1731,1,'Attach Documents','',0,'\\81001','\\810',0,'0',0," . $params['levelid'] . "),
            (1732,1,'Download Documents','',0,'\\81002','\\810',0,'0',0," . $params['levelid'] . "),
            (1733,1,'Delete Documents','',0,'\\81003','\\810',0,'0',0," . $params['levelid'] . "),
            (3687,0,'Allow View To Do','',0,'\\814','$parent',0,'0',0," . $params['levelid'] . "),            
            (1729,1,'Allow Override Plan Limit','',0,'\\811','$parent',0,'0',0," . $params['levelid'] . "),
            (3723,0,'Restrict IP','',0,'\\818','$parent',0,'0',0," . $params['levelid'] . "),
            (4077,0,'Allow View All Application/Contracts','',0,'\\822','$parent',0,'0',0," . $params['levelid'] . "),
            (4098,0,'Allow to search & view transactions','',0,'\\823','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'MASTERFILE',$sort,'description',',coa,customer,supplier,agent,warehouse,stockcard,facard,fbrmanager,part,model,stockgrp,department,itemquery,productinquiry'," . $params['levelid'] . ")";
    } //end function

    public function parentreportslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(100,0,'REPORT LIST','',0,'$parent','\\',0,'',0," . $params['levelid'] . ")";

        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'REPORT LIST',$sort,'description',',,'," . $params['levelid'] . ")";
    } //end function

    public function adashboard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $parent2 = '\\2111';
        $folder = "ahris";

        if ($params['companyid'] == 29) { //sbc
            $folder = "s966bcd74e8482da1569c6b839996c0dd";
        }

        $qry = "(5222,0,'Analytic Dashboard','',0,'$parent2','$parent',0,'',0," . $params['levelid'] . "),
        (5223,1,'Allow View Analytics Dashboard','',0,'\\90001','$parent2',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'adashboard','/amodule/" . $folder . "/adashboard','Analytic Dashboard','fas fa-chart-pie sub_menu_ico',5223," . $params['levelid'] . ")";
    } //end function


    public function itemcategory($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        switch ($companyid) {
            case 22: //EIPI
                $fieldLabel = 'Item Category 1';
                break;
            default:
                $fieldLabel = 'Item Category';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2373,1,'" . $fieldLabel . "','',0,'\\24028','$parent',0,0,0," . $params['levelid'] . "),
        (2374,0,'Allow View Item Category','',0,'\\2402801','\\24028',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'itemcategory','/tableentries/warehousingentry/entryitemcategory','" . $fieldLabel . "','fa fa-dolly-flatbed sub_menu_ico',2373," . $params['levelid'] . ")";
    } //end function

    public function itemsubcategory($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        switch ($companyid) {
            case 22: //EIPI
                $fieldLabel = 'Item Category 3';
                break;
            default:
                $fieldLabel = 'Item Sub-category';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2516,1,'" . $fieldLabel . "','',0,'\\24032','$parent',0,0,0," . $params['levelid'] . "),
        (2517,0,'Allow View Item Sub-category','',0,'\\2403201','\\24032',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'itemsubcategory','/tableentries/tableentry/entryitemsubcategory','" . $fieldLabel . "','fa fa-dolly-flatbed sub_menu_ico',2516," . $params['levelid'] . ")";
    } //end function


    public function customer($params, $parent, $sort)
    {
        $p = $parent;
        $label = "Customer";
        $parent = '\\' . $parent;
        if ($this->companysetup->getsystemtype($params) == 'LENDING') {
            $label = "Borrower";
        }
        $qry = "(21,0,'" . $label . " Ledger','',0,'\\103','$parent',0,'0',0," . $params['levelid'] . "),
        (22,0,'Allow View " . $label . " Ledger','CUSTOMER',0,'\\10301','\\103',0,'0',0," . $params['levelid'] . "),
        (23,0,'Allow Click Edit Button CL','',0,'\\10302','\\103',0,'0',0," . $params['levelid'] . "),
        (24,0,'Allow Click New Button CL','',0,'\\10303','\\103',0,'0',0," . $params['levelid'] . "),
        (25,0,'Allow Click Save Button CL','',0,'\\10304','\\103',0,'0',0," . $params['levelid'] . "),        
        (27,0,'Allow Click Delete Button CL','',0,'\\10306','\\103',0,'0',0," . $params['levelid'] . "),
        (28,0,'Allow Click Print Button CL','',0,'\\10307','\\103',0,'0',0," . $params['levelid'] . "),";


        if ($params['companyid'] != 34) { //not evergreen
            $qry .= "(2734,0,'Allow View SKU Entry','',0,'\\10308','\\103',0,'0',0," . $params['levelid'] . "),
            (2735,0,'Allow View AR History','',0,'\\10309','\\103',0,'0',0," . $params['levelid'] . "),
            (2736,0,'Allow View AP History','',0,'\\103010','\\103',0,'0',0," . $params['levelid'] . "),
            (2737,0,'Allow View PDC History','',0,'\\103011','\\103',0,'0',0," . $params['levelid'] . "),
            (2738,0,'Allow View Returned Checks History','',0,'\\103012','\\103',0,'0',0," . $params['levelid'] . "),
            (2739,0,'Allow View Inventory History','',0,'\\103013','\\103',0,'0',0," . $params['levelid'] . "),
            (2740,0,'Allow View Default Shipping/Billing Address','',0,'\\103014','\\103',0,'0',0," . $params['levelid'] . "),
            (2741,0,'Allow View Shipping/Billing Address Setup','',0,'\\103015','\\103',0,'0',0," . $params['levelid'] . "),
            (2992,0,'Allow View Unpaid AR','',0,'\\103016','\\103',0,'0',0," . $params['levelid'] . "),
            (3744,0,'Allow View Contact Person Setup','',0,'\\103017','\\103',0,'0',0," . $params['levelid'] . ")";
            if ($this->companysetup->customerperagent($params)) {
                $qry .= ",(4077,0,'Allow View All Customers','',0,'\\103020','\\103',0,'0',0," . $params['levelid'] . ")";
            }
        } else {
            $qry .= "(2735,0,'Allow View AR History','',0,'\\10309','\\103',0,'0',0," . $params['levelid'] . "),
            (2736,0,'Allow View AP History','',0,'\\103010','\\103',0,'0',0," . $params['levelid'] . "),
            (2737,0,'Allow View PDC History','',0,'\\103011','\\103',0,'0',0," . $params['levelid'] . "),
            (2738,0,'Allow View Returned Checks History','',0,'\\103012','\\103',0,'0',0," . $params['levelid'] . "),
            (2992,0,'Allow View Unpaid AR','',0,'\\103016','\\103',0,'0',0," . $params['levelid'] . ")";
        }

        if ($params['companyid'] == 16) { //ati
            $qry .= ", (3745,0,'Limit Customer Details','',0,'\\103018','\\103',0,'0',0," . $params['levelid'] . ")";
        }

        if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti, afti usd
            $qry .= ",(3768,0,'Allow Edit Customer Credit Limit','',0,'\\103018','\\103',0,'0',0," . $params['levelid'] . "),
            (3769,0,'Allow Edit Customer Notes(Acctg)','',0,'\\103019','\\103',0,'0',0," . $params['levelid'] . ")";
        }

        if ($params['companyid'] == 55) { // afli lending
            $qry .= ",(4997,0,'Allow View Loan History','',0,'\\10320','\\103',0,'0',0," . $params['levelid'] . "),
            (5019,0,'Allow View Loan Schedule','',0,'\\10321','\\103',0,'0',0," . $params['levelid'] . ")";
        }

        if ($params['companyid'] == 60) { // transpower
            $qry .= ",(5349,0,'Allow View Other Info','',0,'\\10322','\\103',0,'0',0," . $params['levelid'] . ")";
        }
        $this->insertattribute($params, $qry);
        return "($sort,$p,'customer','/ledgergrid/masterfile/customer','" . $label . "','fa fa-address-card sub_menu_ico',21," . $params['levelid'] . ")";
    } //end function

    public function supplier($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(31,0,'Supplier Ledger','',0,'\\104','$parent',0,'0',0," . $params['levelid'] . "),
        (32,0,'Allow View Supplier Ledger','SU',0,'\\10401','\\104',0,'0',0," . $params['levelid'] . "),
        (33,0,'Allow Click Edit Button SL','',0,'\\10402','\\104',0,'0',0," . $params['levelid'] . "),
        (34,0,'Allow Click New Button SL','',0,'\\10403','\\104',0,'0',0," . $params['levelid'] . "),
        (35,0,'Allow Click Save Button SL','',0,'\\10404','\\104',0,'0',0," . $params['levelid'] . "),
        (36,0,'Allow Click Change Supplier Code SL','',0,'\\10405','\\104',0,'0',0," . $params['levelid'] . "),
        (37,0,'Allow Click Delete  Button SL','',0,'\\10406','\\104',0,'0',0," . $params['levelid'] . "),
        (38,0,'Allow Click Print Button SL','',0,'\\10407','\\104',0,'0',0," . $params['levelid'] . "),

        (2742,0,'Allow View AR History','',0,'\\10408','\\104',0,'0',0," . $params['levelid'] . "),
        (2743,0,'Allow View AP History','',0,'\\10409','\\104',0,'0',0," . $params['levelid'] . "),
        (2744,0,'Allow View Inventory History','',0,'\\10410','\\104',0,'0',0," . $params['levelid'] . "),
        (2745,0,'Allow Allow View Default Shipping/Billing Address','',0,'\\10411','\\104',0,'0',0," . $params['levelid'] . "),
        (2746,0,'Allow Allow View Shipping/Billing Address Setup','',0,'\\10412','\\104',0,'0',0," . $params['levelid'] . "),
        (2993,0,'Allow Allow View Unpaid AP','',0,'\\10413','\\104',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 56: //homeworks
                $qry  .= ", (5017,0,'Allow Allow View Commission List','',0,'\\10414','\\104',0,'0',0," . $params['levelid'] . ")";
                $qry  .= ", (5024,0,'Allow Allow View Item List','',0,'\\10415','\\104',0,'0',0," . $params['levelid'] . ")";
                $qry  .= ", (5028,0,'Allow Click Inactive Supplier and List of Items Button SL','',0,'\\10416','\\104',0,'0',0," . $params['levelid'] . ")";
                break;
            case 19: //housegem
                $qry  .= ", (5117,0,'Allow Allow View Entry Item','',0,'\\10417','\\104',0,'0',0," . $params['levelid'] . ")";
                break;
        }
        $this->insertattribute($params, $qry);
        return "($sort,$p,'supplier','/ledger/masterfile/supplier','Supplier','fa fa-user-tie sub_menu_ico',31," . $params['levelid'] . ")";
    } //end function

    public function employeemaster($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(868,0,'Employee Ledger','',0,'\\107','$parent',0,'0',0," . $params['levelid'] . "),
        (869,0,'Allow View Employee Ledger','',0,'\\10701','\\107',0,'0',0," . $params['levelid'] . "),
        (870,0,'Allow Click Edit Button EMP','',0,'\\10702','\\107',0,'0',0," . $params['levelid'] . "),
        (871,0,'Allow Click New Button EMP','',0,'\\10703','\\107',0,'0',0," . $params['levelid'] . "),
        (872,0,'Allow Click Save Button EMP','',0,'\\10704','\\107',0,'0',0," . $params['levelid'] . "),
        (873,0,'Allow Click Change Code EMP','',0,'\\10705','\\107',0,'0',0," . $params['levelid'] . "),
        (874,0,'Allow Click Delete Button EMP','',0,'\\10706','\\107',0,'0',0," . $params['levelid'] . "),
        (875,0,'Allow Click Print Button EMP','',0,'\\10707','\\107',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'employee','/ledger/masterfile/employee','Employee','fa fa-user sub_menu_ico',868," . $params['levelid'] . ")";
    } //end function

    public function departmentmaster($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(860,0,'Department Ledger','',0,'\\108','$parent',0,'0',0," . $params['levelid'] . "),
        (861,0,'Allow View Department Ledger','',0,'\\10801','\\108',0,'0',0," . $params['levelid'] . "),
        (862,0,'Allow Click Edit Button DEPT','',0,'\\10802','\\108',0,'0',0," . $params['levelid'] . "),
        (863,0,'Allow Click New Button DEPT','',0,'\\10803','\\108',0,'0',0," . $params['levelid'] . "),
        (864,0,'Allow Click Save Button DEPT','',0,'\\10804','\\108',0,'0',0," . $params['levelid'] . "),
        (865,0,'Allow Click Change Code DEPT','',0,'\\10805','\\108',0,'0',0," . $params['levelid'] . "),
        (866,0,'Allow Click Delete Button DEPT','',0,'\\10806','\\108',0,'0',0," . $params['levelid'] . "),
        (867,0,'Allow Click Print Button DEPT','',0,'\\10807','\\108',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'department','/ledger/masterfile/department','Department','fa fa-code-branch sub_menu_ico',860," . $params['levelid'] . ")";
    } //end function

    public function stockcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Stockcard';
        if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti, afti usd
            $label = 'Main Items';
        }
        $qry = "(11,0,'" . $label . "','',0,'\\102','$parent',0,'0',0," . $params['levelid'] . "),
        (12,0,'Allow View " . $label . "','SK',0,'\\10201','\\102',0,'0',0," . $params['levelid'] . "),
        (13,0,'Allow Click Edit Button SK','',0,'\\10202','\\102',0,'0',0," . $params['levelid'] . "),
        (14,0,'Allow Click New Button SK','',0,'\\10203','\\102',0,'0',0," . $params['levelid'] . "),
        (15,0,'Allow Click Save Button SK','',0,'\\10204','\\102',0,'0',0," . $params['levelid'] . "),
        (16,0,'Allow Click Change Barcode SK','',0,'\\10205','\\102',0,'0',0," . $params['levelid'] . "),
        (17,0,'Allow Click Delete Button SK','',0,'\\10206','\\102',0,'0',0," . $params['levelid'] . "),
        (18,0,'Allow Print Button SK','',0,'\\10207','\\102',0,'0',0," . $params['levelid'] . "),
        (19,0,'Allow View SRP Button SK','',0,'\\10208','\\102',0,'0',0," . $params['levelid'] . "),
        (3689,0,'Allow Edit UOM factor','',0,'\\10209','\\102',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 21: // kinggeorge
                $qry .= ",(4860,0,'Allow View Item Price','',0,'\\10212','\\102',0,'0',0," . $params['levelid'] . "),
                (4861,0,'Allow Update Item Price','',0,'\\10213','\\102',0,'0',0," . $params['levelid'] . "),
                (4862,0,'Allow View History','',0,'\\10214','\\102',0,'0',0," . $params['levelid'] . "),
                (4863,0,'Allow View In Transaction','',0,'\\10215','\\102',0,'0',0," . $params['levelid'] . "),
                (4864,0,'Allow View Unit of Measurement','',0,'\\10216','\\102',0,'0',0," . $params['levelid'] . "),
                (4865,0,'Allow Add and Update Unit of Measurement','',0,'\\10217','\\102',0,'0',0," . $params['levelid'] . "),
                (4866,0,'Allow View Balance Per Warehouse','',0,'\\10218','\\102',0,'0',0," . $params['levelid'] . "),
                (4867,0,'Allow View Purchase Order/ Job Order History','',0,'\\10219','\\102',0,'0',0," . $params['levelid'] . "),
                (4868,0,'Allow View Sales Order History','',0,'\\10220','\\102',0,'0',0," . $params['levelid'] . "),
                (4869,0,'Allow View Component','',0,'\\10221','\\102',0,'0',0," . $params['levelid'] . "),
                (4870,0,'Allow Add and Update Component','',0,'\\10222','\\102',0,'0',0," . $params['levelid'] . "),
                (4871,0,'Allow View Stock Level','',0,'\\10223','\\102',0,'0',0," . $params['levelid'] . "),
                (4872,0,'Allow Add and Update Stock Level','',0,'\\10224','\\102',0,'0',0," . $params['levelid'] . "),
                (4873,0,'Allow View Compatible','',0,'\\10225','\\102',0,'0',0," . $params['levelid'] . "),
                (4874,0,'Allow Add and Update Compatible','',0,'\\10226','\\102',0,'0',0," . $params['levelid'] . "),
                (4875,0,'Allow View Equivalent Sku','',0,'\\10227','\\102',0,'0',0," . $params['levelid'] . "),
                (4876,0,'Allow Add and Update Equivalent Sku','',0,'\\10228','\\102',0,'0',0," . $params['levelid'] . ")";
                break;
            case 39: //cbbsi
                $qry .= ",(4803,0,'Allow Change Barcode','',0,'\\10211','\\102',0,'0',0," . $params['levelid'] . ")";
                break;
            case 56: //homeworks
                $qry .= ",(5016,0,'Allow View Suppiler List','',0,'\\10229','\\102',0,'0',0," . $params['levelid'] . "),
                          (5018,0,'Allow View Price List','',0,'\\10230','\\102',0,'0',0," . $params['levelid'] . "),
                          (5390,0,'Allow View Price Scheme','',0,'\\10231','\\102',0,'0',0," . $params['levelid'] . "),
                          (5391,0,'Allow View Promo Per Item','',0,'\\10232','\\102',0,'0',0," . $params['levelid'] . ")";
                break;
            case 60: //transpower
                $qry  .= ", (5488,0,'Allow View Cost,Distributor and Lowest Price','',0,'\\10234','\\102',0,'0',0," . $params['levelid'] . ")
                , (5485,0,'Allow View Supplier Field','',0,'\\10233','\\102',0,'0',0," . $params['levelid'] . ")
                , (5508,0,'Allow Duplicate Item Info','',0,'\\10235','\\102',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        if ($this->companysetup->isrecalc($params)) {
            $qry .= ",(3690,0,'Allow Recalc','',0,'\\10210','\\102',0,'0',0," . $params['levelid'] . ")";
        }
        $this->insertattribute($params, $qry);

        return "($sort,$p,'stockcard','/ledgergrid/masterfile/stockcard','" . $label . "','fa fa-list-alt sub_menu_ico',11," . $params['levelid'] . ")";
    } //end function

    public function itemquery($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3625,0,'Item Query','',0,'\\123','$parent',0,'0',0," . $params['levelid'] . "),
        (3626,0,'Allow View Item Query','',0,'\\12301','\\123',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'stockcard','/ledgergrid/inquiry/stockcard','Item Query','fa fa-list-alt sub_menu_ico',3625," . $params['levelid'] . ")";
    }


    public function infra($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5605,0,'Infrastructure Clearance Ledger','',0,'\\109','$parent',0,'0',0," . $params['levelid'] . "),
        (5606,0,'Allow View Infrastructure Clearance Ledger','',0,'\\10901','\\109',0,'0',0," . $params['levelid'] . "),
        (5630,0,'Allow Click Edit Button IF','',0,'\\10902','\\109',0,'0',0," . $params['levelid'] . "),
        (5631,0,'Allow Click New Button IF','',0,'\\10903','\\109',0,'0',0," . $params['levelid'] . "),
        (5632,0,'Allow Click Save Button IF','',0,'\\10904','\\109',0,'0',0," . $params['levelid'] . "),        
        (5633,0,'Allow Click Delete Button IF','',0,'\\10905','\\109',0,'0',0," . $params['levelid'] . "),
        (5634,0,'Allow Click Print Button IF','',0,'\\10906','\\109',0,'0',0," . $params['levelid'] . "),
        (5635,0,'Allow Click Load Button IF','',0,'\\10907','\\109',0,'0',0," . $params['levelid'] . "),
        (5636,0,'Allow Click Change Button IF','',0,'\\10908','\\109',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'infra','/ledgergrid/masterfile/infra','Infrastructure Clearance Ledger','fa fa-user-tie sub_menu_ico',5605," . $params['levelid'] . ")";
    }


    public function productinquiry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4451,0,'MC Unit Inquiry','',0,'\\125','$parent',0,'0',0," . $params['levelid'] . "),
        (4452,0,'Allow View MC Unit Inquiry','',0,'\\12501','\\125',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'stockcard','/ledgergrid/productinquiry/stockcard','MC Unit Inquiry','fa fa-list-alt sub_menu_ico',4451," . $params['levelid'] . ")";
    }



    public function facard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2223,0,'Fixed Asset Card','',0,'\\109','$parent',0,'0',0," . $params['levelid'] . "),
          (2224,0,'Allow View FA Card','FA',0,'\\10901','\\109',0,'0',0," . $params['levelid'] . "),
          (2225,0,'Allow Click Edit Button FA','',0,'\\10902','\\109',0,'0',0," . $params['levelid'] . "),
          (2226,0,'Allow Click New Button FA','',0,'\\10903','\\109',0,'0',0," . $params['levelid'] . "),
          (2227,0,'Allow Click Save Button FA','',0,'\\10904','\\109',0,'0',0," . $params['levelid'] . "),
          (2228,0,'Allow Click Change Barcode FA','',0,'\\10905','\\109',0,'0',0," . $params['levelid'] . "),
          (2229,0,'Allow Click Delete Button FA','',0,'\\10906','\\109',0,'0',0," . $params['levelid'] . "),
          (2230,0,'Allow Print Button FA','',0,'\\10907','\\109',0,'0',0," . $params['levelid'] . "),
          (2231,0,'Allow View SRP Button FA','',0,'\\10908','\\109',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'facard','/ledgergrid/fixedasset/stockcard','Fixed Asset Card','fa fa-list-alt sub_menu_ico',2223," . $params['levelid'] . ")";
    }

    public function role($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2404,0,'Role Masterfile','',0,'\\110','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'role','/tableentries/tableentry/entryrole','Role Masterfile','fa fa-tasks sub_menu_ico',2404," . $params['levelid'] . ")";
    } //end function
    public function biometric($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4503,0,'Biometric Terminal','',0,'\\1148','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'biometric','/tableentries/tableentry/entrybiometric','Biometric Terminal','fa fa-tasks sub_menu_ico',4503," . $params['levelid'] . ")";
    } //end function



    public function agent($params, $parent, $sort)
    {
        $companyid = $params['companyid'];

        switch ($companyid) {
            case 34: //evergreen
                $modulename = 'Employee';
                break;
            default:
                $modulename = 'Agent';
                break;
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(41,0,'" . $modulename . " Ledger','',0,'\\105','$parent',0,'0',0," . $params['levelid'] . "),
        (42,0,'Allow View " . $modulename . " Ledger','AG',0,'\\10501','\\105',0,'0',0," . $params['levelid'] . "),
        (43,0,'Allow Click Edit Button AL','',0,'\\10502','\\105',0,'0',0," . $params['levelid'] . "),
        (44,0,'Allow Click New Button AL','',0,'\\10503','\\105',0,'0',0," . $params['levelid'] . "),
        (45,0,'Allow Click Save Button AL','',0,'\\10504','\\105',0,'0',0," . $params['levelid'] . "),        
        (47,0,'Allow Click Delete Button AL','',0,'\\10506','\\105',0,'0',0," . $params['levelid'] . "),
        (48,0,'Allow Click Print Button AL','',0,'\\10507','\\105',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        if ($companyid == 34) { //evergreen
            return "($sort,$p,'agent','/ledger/masterfile/agent','Employee','fa fa-id-card-alt sub_menu_ico',41," . $params['levelid'] . ")";
        } else {
            return "($sort,$p,'agent','/ledger/masterfile/agent','Agent','fa fa-id-card-alt sub_menu_ico',41," . $params['levelid'] . ")";
        }
    } //end function

    public function warehouse($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(51,0,'Warehouse Ledger','',0,'\\106','$parent',0,'0',0," . $params['levelid'] . "),
        (52,0,'Allow View Warehouse','WH',0,'\\10601','\\106',0,'0',0," . $params['levelid'] . "),
        (53,0,'Allow Click Edit Button WL','',0,'\\10602','\\106',0,'0',0," . $params['levelid'] . "),
        (54,0,'Allow Click New Button WL','',0,'\\10603','\\106',0,'0',0," . $params['levelid'] . "),
        (55,0,'Allow Click Save Button WL','',0,'\\10604','\\106',0,'0',0," . $params['levelid'] . "),
        (56,0,'Allow Click Change Warehouse Code  WL','',0,'\\10605','\\106',0,'0',0," . $params['levelid'] . "),
        (57,0,'Allow Click Delete Button WL','',0,'\\10606','\\106',0,'0',0," . $params['levelid'] . "),
        (58,0,'Allow Click Print Button WL','',0,'\\10607','\\106',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 3: //conto
            case 43: //mighty
                $qry .= ", (2731,0,'Allow View Document Tab','',0,'\\10608','\\106',0,'0',0," . $params['levelid'] . "),
                     (2732,0,'Allow View NODS Tab','',0,'\\10609','\\106',0,'0',0," . $params['levelid'] . "),
                     (2733,0,'Allow View Job Request Tab','',0,'\\106010','\\106',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehouse','/ledgergrid/masterfile/warehouse','Warehouse','fa fa-warehouse sub_menu_ico',51," . $params['levelid'] . ")";
    } //end function

    public function branchledger($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2585,0,'Branch Ledger','',0,'\\111','$parent',0,'0',0," . $params['levelid'] . "),
        (2586,0,'Allow View Branch','BH',0,'\\11101','\\111',0,'0',0," . $params['levelid'] . "),
        (2587,0,'Allow Click Edit Button BH','',0,'\\11102','\\111',0,'0',0," . $params['levelid'] . "),
        (2588,0,'Allow Click New Button BH','',0,'\\11103','\\111',0,'0',0," . $params['levelid'] . "),
        (2589,0,'Allow Click Save Button BH','',0,'\\11104','\\111',0,'0',0," . $params['levelid'] . "),
        (2590,0,'Allow Click Change Branch Code BH','',0,'\\11105','\\111',0,'0',0," . $params['levelid'] . "),
        (2591,0,'Allow Click Delete Button BH','',0,'\\11106','\\111',0,'0',0," . $params['levelid'] . "),
        (2592,0,'Allow Click Print Button BH','',0,'\\11107','\\111',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'branch','/ledgergrid/masterfile/branch','Branch','fa fa-network-wired sub_menu_ico',2585," . $params['levelid'] . ")";
    } //end function

    public function parentpurchase($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(61,0,'PURCHASES','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        if ($params['companyid'] == 12) { //afti usd
            return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PURCHASES',$sort,'shopping_basket',',PO'," . $params['levelid'] . ")";
        } elseif ($params['companyid'] == 32) { //3m
            return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PURCHASES',$sort,'shopping_basket',',PO,RR,DM'," . $params['levelid'] . ")";
        } else {
            return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PURCHASES',$sort,'shopping_basket',',PR,PO,RR,DM'," . $params['levelid'] . ")";
        }
    } //end function

    public function ph($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4392,0,'Price Change','',0,'\\420','$parent',0,'0',0," . $params['levelid'] . "),
            (4393,0,'Allow View Transaction PH','PH',0,'\\42001','\\420',0,'0',0," . $params['levelid'] . "),
            (4394,0,'Allow Click Edit Button PH','',0,'\\42002','\\420',0,'0',0," . $params['levelid'] . "),
            (4395,0,'Allow Click New Button PH','',0,'\\42003','\\420',0,'0',0," . $params['levelid'] . "),
            (4396,0,'Allow Click Save Button PH','',0,'\\42004','\\420',0,'0',0," . $params['levelid'] . "),
            (4397,0,'Allow Click Delete Button PH','',0,'\\42005','\\420',0,'0',0," . $params['levelid'] . "),
            (4398,0,'Allow Click Print Button PH','',0,'\\42006','\\420',0,'0',0," . $params['levelid'] . "),
            (4399,0,'Allow Click Lock Button PH','',0,'\\42007','\\420',0,'0',0," . $params['levelid'] . "),
            (4400,0,'Allow Click UnLock Button PH','',0,'\\42008','\\420',0,'0',0," . $params['levelid'] . "),
            (4401,0,'Allow Click Post Button PH','',0,'\\42009','\\420',0,'0',0," . $params['levelid'] . "),
            (4402,0,'Allow Click UnPost Button PH','',0,'\\42010','\\420',0,'0',0," . $params['levelid'] . "),
            (4403,0,'Allow Click Add Item PH','',0,'\\42011','\\420',0,'0',0," . $params['levelid'] . "),
            (4404,0,'Allow Click Edit Item PH','',0,'\\42012','\\420',0,'0',0," . $params['levelid'] . "),
            (4405,0,'Allow Click Delete Item PH','',0,'\\42013','\\420',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PH','/module/purchase/ph','Price Change','fa fa-tasks sub_menu_ico',4392," . $params['levelid'] . ")";
    }

    public function po($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        switch ($params['companyid']) {
            case 24: //goodfound
                $modulename = 'Supplies Purchase Order';
                break;
            default:
                $modulename = 'Purchase Order';
                break;
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(62,0,'" . $modulename . "','',0,'\\401','$parent',0,'0',0," . $params['levelid'] . "),
        (63,0,'Allow View Transaction PO','PO',0,'\\40101','\\401',0,'0',0," . $params['levelid'] . "),
        (64,0,'Allow Click Edit Button PO','',0,'\\40102','\\401',0,'0',0," . $params['levelid'] . "),
        (65,0,'Allow Click New Button PO','',0,'\\40103','\\401',0,'0',0," . $params['levelid'] . "),
        (66,0,'Allow Click Save Button PO','',0,'\\40104','\\401',0,'0',0," . $params['levelid'] . "),
        (68,0,'Allow Click Delete Button PO','',0,'\\40106','\\401',0,'0',0," . $params['levelid'] . "),
        (69,0,'Allow Click Print Button PO','',0,'\\40107','\\401',0,'0',0," . $params['levelid'] . "),
        (70,0,'Allow Click Lock Button PO','',0,'\\40108','\\401',0,'0',0," . $params['levelid'] . "),
        (71,0,'Allow Click UnLock Button PO','',0,'\\40109','\\401',0,'0',0," . $params['levelid'] . "),
        (72,0,'Allow Change Amount PO','',0,'\\40110','\\401',0,'0',0," . $params['levelid'] . "),
        (73,0,'Allow Click Post Button PO','',0,'\\40112','\\401',0,'0',0," . $params['levelid'] . "),
        (74,0,'Allow Click UnPost  Button PO','',0,'\\40113','\\401',0,'0',0," . $params['levelid'] . "),
        (808,1,'Allow Click Add Item PO','',0,'\\40114','\\401',0,'0',0," . $params['levelid'] . "),
        (809,1,'Allow Click Edit Item PO','',0,'\\40115','\\401',0,'0',0," . $params['levelid'] . "),
        (810,1,'Allow Click Delete Item PO','',0,'\\40116','\\401',0,'0',0," . $params['levelid'] . "),
        (843,1,'Allow View Amount','',0,'\\40117','\\401',0,'0',0," . $params['levelid'] . "),
        (3592,1,'Allow Void Button','',0,'\\40119','\\401',0,'0',0," . $params['levelid'] . ")";

        if ($this->companysetup->getispr($params)) {
            $qry .= ", (2548,1,'Allow Click PR Button','',0,'\\40118','\\401',0,'0',0," . $params['levelid'] . ")";
        }

        switch ($companyid) {
            case 16: //ati
                $qry .= ", (4009,1,'Allow Clicked Approved','',0,'\\40120','\\401',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4122,1,'Allow Multiple Printing','',0,'\\40121','\\401',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4164,1,'Allow Click Ordered Button','',0,'\\40122','\\401',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4310,1,'Allow Update Posted Details','',0,'\\40123','\\401',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4480,1,'Allow View All Warehouse','',0,'\\40124','\\401',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4368,1,'Allow Generate Temp. Barcode','',0,'\\40125','\\401',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4848,1,'Allow Export Only','',0,'\\40126','\\401',0,'0',0," . $params['levelid'] . ")";
                break;
            case 3: //conti
                $qry .= ", (4192,1,'Allow Click Canvass Button PO','',0,'\\40123','\\401',0,'0',0," . $params['levelid'] . ")";
                break;
            case 56: //homeworks
                $qry .= ",(5301,1,'Allow Update Posted Details','',0,'\\40127','\\401',0,'0',0," . $params['levelid'] . ")";
                break;
            case 60: //transpower
                $qry .=  ",(5491,0,'Allow Click Change Code Button','',0,'\\40128','\\401',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
            case 59: //roosevolt
                $folder = 'rc952c55ab9eb85660b7cab413fa7c803';
                break;
        }
        return "($sort,$p,'PO','/module/" . $folder . "/po','" . $modulename . "','fa fa-tasks sub_menu_ico',62," . $params['levelid'] . ")";
    } //end function

    public function rr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Receiving Report';
        switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
                $label = 'Purchase Receiving';
                break;
            case 24: //goodfound
                $label = 'Supplies Receiving Report';
                break;
            case 28: //xcomp
                $label = 'Receiving Report';
                break;
        }
        $qry = "(78,0,'" . $label . "','',0,'\\402','$parent',0,'0',0," . $params['levelid'] . "),
        (79,0,'Allow View Transaction RR','RR',0,'\\40201','\\402',0,'0',0," . $params['levelid'] . "),
        (80,0,'Allow Click Edit Button RR','',0,'\\40202','\\402',0,'0',0," . $params['levelid'] . "),
        (81,0,'Allow Click New Button RR','',0,'\\40203','\\402',0,'0',0," . $params['levelid'] . "),
        (82,0,'Allow Click Save Button RR','',0,'\\40204','\\402',0,'0',0," . $params['levelid'] . "),
        (84,0,'Allow Click Delete Button RR','',0,'\\40206','\\402',0,'0',0," . $params['levelid'] . "),
        (85,0,'Allow Click Print Button RR','',0,'\\40207','\\402',0,'0',0," . $params['levelid'] . "),
        (86,0,'Allow Click Lock Button RR','',0,'\\40208','\\402',0,'0',0," . $params['levelid'] . "),
        (87,0,'Allow Click UnLock Button RR','',0,'\\40209','\\402',0,'0',0," . $params['levelid'] . "),
        (88,0,'Allow Click Post Button RR','',0,'\\40210','\\402',0,'0',0," . $params['levelid'] . "),
        (89,0,'Allow Click UnPost Button RR','',0,'\\40211','\\402',0,'0',0," . $params['levelid'] . "),
        (90,0,'Allow View Transaction accounting RR','',0,'\\40212','\\402',0,'0',0," . $params['levelid'] . "),
        (91,0,'Allow Change Amount RR','',0,'\\40213','\\402',0,'0',0," . $params['levelid'] . "),
        (811,1,'Allow Click Add Item RR','',0,'\\40214','\\402',0,'0',0," . $params['levelid'] . "),
        (812,1,'Allow Click Edit Item RR','',0,'\\40215','\\402',0,'0',0," . $params['levelid'] . "),
        (813,1,'Allow Click Delete Item RR','',0,'\\40216','\\402',0,'0',0," . $params['levelid'] . ")";

        $systype = $this->companysetup->getsystemtype($params);
        if ($systype == 'CAIMS') {
            $qry = $qry . ",(2232,1,'Allow View All transaction RR','',0,'\\40217','\\402',0,'0',0," . $params['levelid'] . ")";
        }

        if ($systype == 'FAMS') {
            $qry .= ", (3619,1,'Allow Generate Asset Tag','',0,'\\40220','\\402',0,'0',0," . $params['levelid'] . ")";
        }

        switch ($params['companyid']) {
            case 3: //conti
                $qry .= ", (2728,1,'Allow Click Received','',0,'\\40218','\\402',0,'0',0," . $params['levelid'] . "),
                       (2729,1,'Allow Click Unreceived','',0,'\\40219','\\402',0,'0',0," . $params['levelid'] . ")";
                break;
            case 10: //afti
                $qry .= ", (3577,1,'Allow Click Make Payment','',0,'\\40218','\\402',0,'0',0," . $params['levelid'] . ")";
                break;
            case 16: //ati
                $qry .= ", (3619,1,'Allow Generate Asset Tag','',0,'\\40220','\\402',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4031,1,'Allow View All WH','',0,'\\40221','\\402',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4140,1,'Allow Click for Checking Button','',0,'\\40222','\\402',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4608,1,'Allow Generate Temp. Barcode','',0,'\\40223','\\402',0,'0',0," . $params['levelid'] . ")";
                break;
            case 8: //maxipro
                $qry .= ", (4449,1,'Allow Update Transaction Type','',0,'\\40218','\\402',0,'0',0," . $params['levelid'] . ")";
                break;
            case 43: //mighty
                $qry .= ", (4482,1,'Allow Access Tripping Tab','',0,'\\40217','\\402',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4483,1,'Allow Access Arrived Tab','',0,'\\40218','\\402',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4484,1,'Allow Trip Approved','',0,'\\40219','\\402',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4642,1,'Allow Trip Disapproved','',0,'\\40220','\\402',0,'0',0," . $params['levelid'] . ")";
                break;
            case 40: //cdo
                $qry .= ",(4609,1,'Allow Update Posted Details','',0,'\\40224','\\402',0,'0',0," . $params['levelid'] . ")";
                break;
            case 56: //homeworks
                $qry .= ",(5221,1,'Allow Generate APV','',0,'\\40225','\\402',0,'0',0," . $params['levelid'] . ")";
                break;
            case 60: //transpower
                $qry .=  ",(5492,0,'Allow Click Change Code Button','',0,'\\40226','\\402',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $folder = 'purchase';
        switch ($params['companyid']) {
            case 16: //ati
                $folder = 'ati';
                break;

            case 26: // bee healthy
                $folder = 'bee';
                $label = 'Purchase Journal';
                break;
            case 47: //kstar
                $folder = 'kitchenstar';
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'RR','/module/" . $folder . "/rr','" . $label . "','fa fa-people-carry sub_menu_ico',78," . $params['levelid'] . ")";
    } //end function

    public function fa($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Fixed Asset Schedule';

        $qry = "(4786,0,'" . $label . "','',0,'\\402','$parent',0,'0',0," . $params['levelid'] . "),
        (4787,0,'Allow View Transaction','FA',0,'\\40201','\\402',0,'0',0," . $params['levelid'] . "),
        (4788,0,'Allow Click Edit Button','',0,'\\40202','\\402',0,'0',0," . $params['levelid'] . "),
        (4789,0,'Allow Click New Button','',0,'\\40203','\\402',0,'0',0," . $params['levelid'] . "),
        (4790,0,'Allow Click Save Button','',0,'\\40204','\\402',0,'0',0," . $params['levelid'] . "),
        (4791,0,'Allow Click Delete Button','',0,'\\40206','\\402',0,'0',0," . $params['levelid'] . "),
        (4792,0,'Allow Click Print Button','',0,'\\40207','\\402',0,'0',0," . $params['levelid'] . "),
        (4793,0,'Allow Click Lock Button','',0,'\\40208','\\402',0,'0',0," . $params['levelid'] . "),
        (4794,0,'Allow Click UnLock Button','',0,'\\40209','\\402',0,'0',0," . $params['levelid'] . "),
        (4795,0,'Allow Click Post Button','',0,'\\40210','\\402',0,'0',0," . $params['levelid'] . "),
        (4796,0,'Allow Click UnPost Button','',0,'\\40211','\\402',0,'0',0," . $params['levelid'] . "),
        (4797,0,'Allow Change Amount','',0,'\\40213','\\402',0,'0',0," . $params['levelid'] . "),
        (4798,1,'Allow Click Add Item','',0,'\\40214','\\402',0,'0',0," . $params['levelid'] . "),
        (4799,1,'Allow Click Edit Item','',0,'\\40215','\\402',0,'0',0," . $params['levelid'] . "),
        (4800,1,'Allow Click Delete Item','',0,'\\40216','\\402',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'FA','/module/seastar/fa','" . $label . "','fa fa-list-alt sub_menu_ico',4786," . $params['levelid'] . ")";
    } //end function

    public function sn($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2239,0,'Supplier Invoice','',0,'\\406','$parent',0,'0',0," . $params['levelid'] . "),
        (2240,0,'Allow View Transaction','SN',0,'\\40601','\\406',0,'0',0," . $params['levelid'] . "),
        (2241,0,'Allow Click Edit Button','',0,'\\40602','\\406',0,'0',0," . $params['levelid'] . "),
        (2242,0,'Allow Click New Button','',0,'\\40603','\\406',0,'0',0," . $params['levelid'] . "),
        (2243,0,'Allow Click Save Button','',0,'\\40604','\\406',0,'0',0," . $params['levelid'] . "),
        (2245,0,'Allow Click Delete Button','',0,'\\40606','\\406',0,'0',0," . $params['levelid'] . "),
        (2246,0,'Allow Click Print Button','',0,'\\40607','\\406',0,'0',0," . $params['levelid'] . "),
        (2247,0,'Allow Click Lock Button','',0,'\\40608','\\406',0,'0',0," . $params['levelid'] . "),
        (2248,0,'Allow Click UnLock Button','',0,'\\40609','\\406',0,'0',0," . $params['levelid'] . "),
        (2249,0,'Allow Click Post Button','',0,'\\40610','\\406',0,'0',0," . $params['levelid'] . "),
        (2250,0,'Allow Click UnPost Button','',0,'\\40611','\\406',0,'0',0," . $params['levelid'] . "),
        (2251,0,'Allow View Transaction accounting','',0,'\\40612','\\406',0,'0',0," . $params['levelid'] . "),
        (2252,1,'Allow Click Add RR','',0,'\\40613','\\406',0,'0',0," . $params['levelid'] . "),
        (2253,1,'Allow Click Delete RR','',0,'\\40614','\\406',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'SN','/module/purchase/sn','Supplier Invoice','fa fa-file-invoice sub_menu_ico',2239," . $params['levelid'] . ")";
    } //end function


    public function dm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(97,0,'Purchase Return','',0,'\\403','$parent',0,'0',0," . $params['levelid'] . "),
        (98,0,'Allow View Transaction DM','DM',0,'\\40301','\\403',0,'0',0," . $params['levelid'] . "),
        (99,0,'Allow Click Edit Button DM','',0,'\\40302','\\403',0,'0',0," . $params['levelid'] . "),
        (100,0,'Allow Click New Button DM','',0,'\\40303','\\403',0,'0',0," . $params['levelid'] . "),
        (101,0,'Allow Click Save Button DM','',0,'\\40304','\\403',0,'0',0," . $params['levelid'] . "),
        (103,0,'Allow Click Delete Button DM','',0,'\\40306','\\403',0,'0',0," . $params['levelid'] . "),
        (104,0,'Allow Click Print Button DM','',0,'\\40307','\\403',0,'0',0," . $params['levelid'] . "),
        (105,0,'Allow Click Lock Button DM','',0,'\\40308','\\403',0,'0',0," . $params['levelid'] . "),
        (106,0,'Allow Click UnLock Button DM','',0,'\\40309','\\403',0,'0',0," . $params['levelid'] . "),
        (107,0,'Allow Click Post Button DM','',0,'\\40310','\\403',0,'0',0," . $params['levelid'] . "),
        (108,0,'Allow Click UnPost Button DM','',0,'\\40311','\\403',0,'0',0," . $params['levelid'] . "),
        (109,0,'Allow View Transaction accounting DM','',0,'\\40312','\\403',0,'0',0," . $params['levelid'] . "),
        (110,0,'Allow Change Amount DM','',0,'\\40313','\\403',0,'0',0," . $params['levelid'] . "),
        (820,1,'Allow Click Add Item DM','',0,'\\40314','\\403',0,'0',0," . $params['levelid'] . "),
        (821,1,'Allow Click Edit Item DM','',0,'\\40315','\\403',0,'0',0," . $params['levelid'] . "),
        (822,1,'Allow Click Delete Item DM','',0,'\\40316','\\403',0,'0',0," . $params['levelid'] . ")";

        $systype = $this->companysetup->getsystemtype($params);
        if ($systype == 'CAIMS') {
            $qry = $qry . ",(2233,1,'Allow View All transaction DM','',0,'\\40317','\\403',0,'0',0," . $params['levelid'] . ")";
        }

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5493,0,'Allow Click Change Code Button','',0,'\\40318','\\403',0,'0',0," . $params['levelid'] . ")";
                break;
        }


        $folder = 'purchase';
        switch ($params['companyid']) {
            case 16: //ati
                $folder = 'ati';
                break;
            case 26: // bee healthy
                $folder = 'bee';
                break;
        }
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DM','/module/" . $folder . "/dm','Purchase Return','fa fa-retweet sub_menu_ico',97," . $params['levelid'] . ")";
    } //end function

    public function pr($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        $systemtype = $this->companysetup->getsystemtype($params);
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(618,0,'Purchase Requisition','',0,'\\404','$parent',0,'0',0," . $params['levelid'] . "),
        (619,0,'Allow View Transaction PR','PR',0,'\\40401','\\404',0,'0',0," . $params['levelid'] . "),
        (620,0,'Allow Click Edit Button PR','',0,'\\40402','\\404',0,'0',0," . $params['levelid'] . "),
        (621,0,'Allow Click New Button PR','',0,'\\40403','\\404',0,'0',0," . $params['levelid'] . "),
        (622,0,'Allow Click Save Button PR','',0,'\\40404','\\404',0,'0',0," . $params['levelid'] . "),
        (624,0,'Allow Click Delete Button PR','',0,'\\40406','\\404',0,'0',0," . $params['levelid'] . "),
        (625,0,'Allow Click Print Button PR','',0,'\\40407','\\404',0,'0',0," . $params['levelid'] . "),
        (626,0,'Allow Click Lock Button PR','',0,'\\40408','\\404',0,'0',0," . $params['levelid'] . "),
        (627,0,'Allow Click UnLock Button PR','',0,'\\40409','\\404',0,'0',0," . $params['levelid'] . "),
        (630,0,'Allow Click Post Button PR','',0,'\\40410','\\404',0,'0',0," . $params['levelid'] . "),
        (631,0,'Allow Click UnPost Button PR','',0,'\\40411','\\404',0,'0',0," . $params['levelid'] . "),
        (628,0,'Allow Change Amount PR','',0,'\\40413','\\404',0,'0',0," . $params['levelid'] . "),
        (814,1,'Allow Click Add Item PR','',0,'\\40414','\\404',0,'0',0," . $params['levelid'] . "),
        (815,1,'Allow Click Edit Item PR','',0,'\\40415','\\404',0,'0',0," . $params['levelid'] . "),
        (816,1,'Allow Click Delete Item PR','',0,'\\40416','\\404',0,'0',0," . $params['levelid'] . "),
        (3601,1,'Allow Void Button','',0,'\\40418','\\404',0,'0',0," . $params['levelid'] . ")";

        switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
                $qry .= ",(2873,1,'Allow Click Make PO Button','',0,'\\40417','\\404',0,'0',0," . $params['levelid'] . ")";
                $qry .= ",(3984,1,'Allow Click Make JO Button','',0,'\\40419','\\404',0,'0',0," . $params['levelid'] . ")";
                break;
            case 16: //ati
                $qry .= ",(3868,1,'Allow View All','',0,'\\40420','\\404',0,'0',0," . $params['levelid'] . ")";
                $qry .= ",(4029,1,'Allow Update Posted Details','',0,'\\40421','\\404',0,'0',0," . $params['levelid'] . ")";
                $qry .= ",(4190,1,'Allow Edit Colors','',0,'\\40422','\\404',0,'0',0," . $params['levelid'] . ")";
                break;
            case 40: //cdo
                $qry .= ",(4453,1,'Allow View all Branch PR','',0,'\\40423','\\404',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
            case 40: //cdo
                $folder = 'cdo';
                break;
            default:
                if ($systemtype == 'REALESTATE') {
                    if ($this->companysetup->isconstruction) {
                        $folder = 'realestate';
                    }
                }
                break;
        }

        return "($sort,$p,'PR','/module/" . $folder . "/pr','Purchase Requisition','fa fa-list sub_menu_ico',618," . $params['levelid'] . ")";
    } //end function

    public function cd($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1427,0,'Canvass Sheet','',0,'\\405','$parent',0,'0',0," . $params['levelid'] . "),
        (1428,0,'Allow View Canvass Sheet','',0,'\\40501','\\405',0,'0',0," . $params['levelid'] . "),
        (1429,0,'Allow Click Edit Button Canvass Sheet','',0,'\\40502','\\405',0,'0',0," . $params['levelid'] . "),
        (1430,0,'Allow Click New Button Canvass Sheet','',0,'\\40503','\\405',0,'0',0," . $params['levelid'] . "),
        (1431,0,'Allow Click Save Button Canvass Sheet','',0,'\\40504','\\405',0,'0',0," . $params['levelid'] . "),
        (1432,0,'Allow Click Change Canvass Sheet','',0,'\\40505','\\405',0,'0',0," . $params['levelid'] . "),
        (1433,0,'Allow Click Delete Button Canvass Sheet','',0,'\\40506','\\405',0,'0',0," . $params['levelid'] . "),
        (1434,0,'Allow Click Print Button Canvass Sheet','',0,'\\40507','\\405',0,'0',0," . $params['levelid'] . "),
        (1435,0,'Allow Click Lock Button Canvass Sheet','',0,'\\40508','\\405',0,'0',0," . $params['levelid'] . "),
        (1436,0,'Allow Click UnLock Button Canvass Sheet','',0,'\\40509','\\405',0,'0',0," . $params['levelid'] . "),
        (1437,0,'Allow Change Amount Canvass Sheet','',0,'\\40510','\\405',0,'0',0," . $params['levelid'] . "),
        (1438,0,'Allow Click Post Button Canvass Sheet','',0,'\\40512','\\405',0,'0',0," . $params['levelid'] . "),
        (1439,0,'Allow Click UnPost  Button Canvass Sheet','',0,'\\40513','\\405',0,'0',0," . $params['levelid'] . "),
        (1440,1,'Allow Click Add Item Canvass Sheet','',0,'\\40514','\\405',0,'0',0," . $params['levelid'] . "),
        (1441,1,'Allow Click Edit Item Canvass Sheet','',0,'\\40515','\\405',0,'0',0," . $params['levelid'] . "),
        (1442,1,'Allow Click Delete Item Canvass Sheet','',0,'\\40516','\\405',0,'0',0," . $params['levelid'] . "),
        (1447,0,'Canvass Approval','',0,'\\40517','\\405',0,'0',0," . $params['levelid'] . "),
        (3600,0,'Allow Void Button','',0,'\\40518','\\405',0,'0',0," . $params['levelid'] . "),
        (3767,0,'Administrator','',0,'\\40519','\\405',0,'0',0," . $params['levelid'] . ")";

        if ($companyid == 16) { //ati
            $qry .= "
            ,(4008,0,'Approved Canvass','',0,'\\40520','\\405',0,'0',0," . $params['levelid'] . ")
            ,(4010,0,'Allow Click Done Checking','',0,'\\40521','\\405',0,'0',0," . $params['levelid'] . ")
            ,(4102,0,'Allow Click For Revision','',0,'\\40522','\\405',0,'0',0," . $params['levelid'] . ")
            ,(4166,0,'View Dashboard - Canvass for PO','',0,'\\40523','\\405',0,'0',0," . $params['levelid'] . ")
            ,(4218,0,'Waived Request Qty','',0,'\\40524','\\405',0,'0',0," . $params['levelid'] . ")
            ,(4481,0,'Allow Update Posted Details','',0,'\\40525','\\405',0,'0',0," . $params['levelid'] . ")";
        }

        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
        }

        return "($sort,$p,'CD','/module/" . $folder . "/cd','Canvass Sheet','fa fa-list sub_menu_ico',1427," . $params['levelid'] . ")";
    } //end function

    public function cd2($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'CD2','/actionlisting/actionlisting/canvassapproval','Canvass Approval','fa fa-check-double sub_menu_ico',1447," . $params['levelid'] . ")";
    } //end function

    public function cd3($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'CD3','/actionlisting/actionlisting/canvassapproval2','Approved Canvass','fa fa-check-double sub_menu_ico',4008," . $params['levelid'] . ")";
    } //end function

    public function sr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2643,0,'Service Receiving','',0,'\\408','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2644,0,'Allow View Transaction SR','SR',0,'\\40801','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2645,0,'Allow Click Edit Button SR','',0,'\\40802','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2646,0,'Allow Click New  Button SR','',0,'\\40803','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2647,0,'Allow Click Save Button SR','',0,'\\40804','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2649,0,'Allow Click Delete Button SR','',0,'\\40806','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2650,0,'Allow Click Print Button SR','',0,'\\40807','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2651,0,'Allow Click Lock Button SR','',0,'\\40808','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2652,0,'Allow Click UnLock Button SR','',0,'\\40809','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2653,0,'Allow Click Post Button SR','',0,'\\40810','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2654,0,'Allow Click UnPost  Button SR','',0,'\\40811','\\408',0,'0',0," . $params['levelid'] . "),
        (2655,0,'Allow Click Change Amount SR','',0,'\\40812','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2656,0,'Allow Click Add Item SR','',0,'\\40813','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2657,0,'Allow Click Edit Item SR','',0,'\\40814','\\408',0,'0',0," . $params['levelid'] . ") ,
        (3618,0,'Allow Edit Insurance','',0,'\\40816','\\408',0,'0',0," . $params['levelid'] . ") ,
        (2658,0,'Allow Click Delete Item SR','',0,'\\40815','\\408',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SR','/module/purchase/sr','Service Receiving','fa fa-people-carry sub_menu_ico',2643," . $params['levelid'] . ")";
    } //end function

    public function jb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2596,0,'Job Order','',0,'\\409','$parent',0,'0',0," . $params['levelid'] . "),
        (2597,0,'Allow View Transaction JB','JB',0,'\\40901','\\409',0,'0',0," . $params['levelid'] . "),
        (2598,0,'Allow Click Edit Button JB','',0,'\\40902','\\409',0,'0',0," . $params['levelid'] . "),
        (2599,0,'Allow Click New Button JB','',0,'\\40903','\\409',0,'0',0," . $params['levelid'] . "),
        (2600,0,'Allow Click Save Button JB','',0,'\\40904','\\409',0,'0',0," . $params['levelid'] . "),
        (2602,0,'Allow Click Delete Button JB','',0,'\\40906','\\409',0,'0',0," . $params['levelid'] . "),
        (2603,0,'Allow Click Print Button JB','',0,'\\40907','\\409',0,'0',0," . $params['levelid'] . "),
        (2604,0,'Allow Click Lock Button JB','',0,'\\40908','\\409',0,'0',0," . $params['levelid'] . "),
        (2605,0,'Allow Click UnLock Button JB','',0,'\\40909','\\409',0,'0',0," . $params['levelid'] . "),
        (2606,0,'Allow Change Amount JB','',0,'\\40910','\\409',0,'0',0," . $params['levelid'] . "),
        (2607,0,'Allow Click Post Button JB','',0,'\\40911','\\409',0,'0',0," . $params['levelid'] . "),
        (2608,0,'Allow Click UnPost  Button JB','',0,'\\40912','\\409',0,'0',0," . $params['levelid'] . "),
        (2609,1,'Allow Click Add Item JB','',0,'\\40913','\\409',0,'0',0," . $params['levelid'] . "),
        (2610,1,'Allow Click Edit Item JB','',0,'\\40914','\\409',0,'0',0," . $params['levelid'] . "),
        (2611,1,'Allow Click Delete Item JB','',0,'\\40915','\\409',0,'0',0," . $params['levelid'] . "),
        (4003,1,'Allow Click PR Button','',0,'\\40917','\\409',0,'0',0," . $params['levelid'] . "),
        (2612,1,'Allow View Amount JB','',0,'\\40916','\\409',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JB','/module/purchase/jb','Job Order','fa fa-tasks sub_menu_ico',2596," . $params['levelid'] . ")";
    } //end function

    public function ac($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2613,0,'Job Completion','',0,'\\410','$parent',0,'0',0," . $params['levelid'] . "),
        (2614,0,'Allow View Transaction AC','AC',0,'\\41001','\\410',0,'0',0," . $params['levelid'] . "),
        (2615,0,'Allow Click Edit Button AC','',0,'\\41002','\\410',0,'0',0," . $params['levelid'] . "),
        (2616,0,'Allow Click New Button AC','',0,'\\41003','\\410',0,'0',0," . $params['levelid'] . "),
        (2617,0,'Allow Click Save Button AC','',0,'\\41004','\\410',0,'0',0," . $params['levelid'] . "),
        (2619,0,'Allow Click Delete Button AC','',0,'\\41006','\\410',0,'0',0," . $params['levelid'] . "),
        (2620,0,'Allow Click Print Button AC','',0,'\\41007','\\410',0,'0',0," . $params['levelid'] . "),
        (2621,0,'Allow Click Lock Button AC','',0,'\\41008','\\410',0,'0',0," . $params['levelid'] . "),
        (2622,0,'Allow Click UnLock Button AC','',0,'\\41009','\\410',0,'0',0," . $params['levelid'] . "),
        (2623,0,'Allow Click Post Button AC','',0,'\\41010','\\410',0,'0',0," . $params['levelid'] . "),
        (2624,0,'Allow Click UnPost Button AC','',0,'\\41011','\\410',0,'0',0," . $params['levelid'] . "),
        (2625,0,'Allow View Transaction accounting AC','',0,'\\41012','\\410',0,'0',0," . $params['levelid'] . "),
        (2626,0,'Allow Change Amount AC','',0,'\\41013','\\410',0,'0',0," . $params['levelid'] . "),
        (2627,1,'Allow Click Add Item AC','',0,'\\41014','\\410',0,'0',0," . $params['levelid'] . "),
        (2628,1,'Allow Click Edit Item AC','',0,'\\41015','\\410',0,'0',0," . $params['levelid'] . "),
        (2629,1,'Allow Click Delete Item AC','',0,'\\41016','\\410',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'AC','/module/purchase/ac','Job Completion','fa fa-check-double sub_menu_ico',2613," . $params['levelid'] . ")";
    } //end function

    public function te($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2672,0,'Task/Errand','',0,'\\411','$parent',0,'0',0," . $params['levelid'] . "),
        (2673,0,'Allow View Transaction TE','TE',0,'\\41101','\\411',0,'0',0," . $params['levelid'] . "),
        (2674,0,'Allow Click Edit Button TE','',0,'\\41102','\\411',0,'0',0," . $params['levelid'] . "),
        (2675,0,'Allow Click New Button TE','',0,'\\41103','\\411',0,'0',0," . $params['levelid'] . "),
        (2676,0,'Allow Click Save Button TE','',0,'\\41104','\\411',0,'0',0," . $params['levelid'] . "),
        (2678,0,'Allow Click Delete Button TE','',0,'\\41106','\\411',0,'0',0," . $params['levelid'] . "),
        (2679,0,'Allow Click Print Button TE','',0,'\\41107','\\411',0,'0',0," . $params['levelid'] . "),
        (2680,0,'Allow Click Lock Button TE','',0,'\\41108','\\411',0,'0',0," . $params['levelid'] . "),
        (2681,0,'Allow Click UnLock Button TE','',0,'\\41109','\\411',0,'0',0," . $params['levelid'] . "),
        (2682,0,'Allow Change Amount TE','',0,'\\41110','\\411',0,'0',0," . $params['levelid'] . "),
        (2683,0,'Allow Click Post Button TE','',0,'\\41112','\\411',0,'0',0," . $params['levelid'] . "),
        (2684,0,'Allow Click UnPost  Button TE','',0,'\\41113','\\411',0,'0',0," . $params['levelid'] . "),
        (2685,1,'Allow Click Add Item TE','',0,'\\41114','\\411',0,'0',0," . $params['levelid'] . "),
        (2686,1,'Allow Click Edit Item TE','',0,'\\41115','\\411',0,'0',0," . $params['levelid'] . "),
        (2687,1,'Allow Click Delete Item TE','',0,'\\41116','\\411',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'TE','/module/sales/te','Task/Errand','fa fa-tasks sub_menu_ico',2672," . $params['levelid'] . ")";
    } //end function


    public function parentinventory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(556,0,'INVENTORY','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'INVENTORY',$sort,'fa fa-box-open',',IS,PC,AJ,TS,VA'," . $params['levelid'] . ")";
    } //end function

    public function pc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(275,0,'Physical Count','',0,'\\602','$parent',0,'0',0," . $params['levelid'] . ") ,
        (276,0,'Allow View Transaction PC','',0,'\\60201','\\602',0,'0',0," . $params['levelid'] . ") ,
        (277,0,'Allow Click Edit Button  PC','',0,'\\60202','\\602',0,'0',0," . $params['levelid'] . ") ,
        (278,0,'Allow Click New Button PC','',0,'\\60203','\\602',0,'0',0," . $params['levelid'] . ") ,
        (279,0,'Allow Click Save Button PC','',0,'\\60204','\\602',0,'0',0," . $params['levelid'] . ") ,
        (280,0,'Allow Adjust PC','',0,'\\60205','\\602',0,'0',0," . $params['levelid'] . ") ,
        (281,0,'Allow Click Delete Button PC','',0,'\\60206','\\602',0,'0',0," . $params['levelid'] . ") ,
        (282,0,'Allow Click Print Button PC','',0,'\\60207','\\602',0,'0',0," . $params['levelid'] . ") ,
        (283,0,'Allow Click Lock Button PC','',0,'\\60208','\\602',0,'0',0," . $params['levelid'] . ") ,
        (284,0,'Allow Click UnLock Button PC','',0,'\\60209','\\602',0,'0',0," . $params['levelid'] . ") ,
        (285,0,'Allow Click Post Button PC','',0,'\\60210','\\602',0,'0',0," . $params['levelid'] . ") ,
        (286,0,'Allow Click UnPost Button PC','',0,'\\60211','\\602',0,'0',0," . $params['levelid'] . ") ,
        (837,1,'Allow Click Delete Item PC','',0,'\\60214','\\602',0,'0',0," . $params['levelid'] . ") ,
        (836,1,'Allow Click Edit Item PC','',0,'\\60213','\\602',0,'0',0," . $params['levelid'] . ") ,
        (835,1,'Allow Click Add Item PC','',0,'\\60212','\\602',0,'0',0," . $params['levelid'] . ") ,
        (838,1,'Allow Change Amount PC','',0,'\\60215','\\602',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5494,0,'Allow Click Change Code Button','',0,'\\60216','\\602',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'PC','/module/inventory/pc','Physical Count','fa fa-list-ol sub_menu_ico',275," . $params['levelid'] . ")";
    } //end function

    public function at($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4905,0,'Actual Count','',0,'\\605','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4906,0,'Allow View Transaction AT','',0,'\\60501','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4907,0,'Allow Click Edit Button AT','',0,'\\60502','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4908,0,'Allow Click New Button AT','',0,'\\60503','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4909,0,'Allow Click Save Button AT','',0,'\\60504','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4911,0,'Allow Click Delete Button AT','',0,'\\60506','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4912,0,'Allow Click Print Button AT','',0,'\\60507','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4913,0,'Allow Click Lock Button AT','',0,'\\60508','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4914,0,'Allow Click UnLock Button AT','',0,'\\60509','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4915,0,'Allow Click Post Button AT','',0,'\\60510','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4916,0,'Allow Click UnPost Button AT','',0,'\\60511','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4917,1,'Allow Click Delete Item AT','',0,'\\60512','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4918,1,'Allow Click Edit Item AT','',0,'\\60513','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4919,1,'Allow Click Add Item AT','',0,'\\60514','\\605',0,'0',0," . $params['levelid'] . ") ,
        (4920,1,'Allow Change Amount AT','',0,'\\60515','\\605',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'AT','/module/inventory/at','Actual Count','fa fa-list-ol sub_menu_ico',4905," . $params['levelid'] . ")";
    } //end function

    public function aj($params, $parent, $sort)
    {

        switch ($params['companyid']) {
            case 39: //cbbsi
                $folder = 'cbbsi';
                break;
            case 40: //cdo
                $folder = 'cdo';
                break;
            default:
                $folder = 'inventory';
                break;
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(290,0,'Inventory Adjustment','',0,'\\603','$parent',0,'0',0," . $params['levelid'] . ") ,
        (291,0,'Allow View Transaction AJ','AJ',0,'\\60301','\\603',0,'0',0," . $params['levelid'] . ") ,
        (292,0,'Allow Click Edit Button  AJ','',0,'\\60302','\\603',0,'0',0," . $params['levelid'] . ") ,
        (293,0,'Allow Click New Button AJ','',0,'\\60303','\\603',0,'0',0," . $params['levelid'] . ") ,
        (294,0,'Allow Click Save Button AJ','',0,'\\60304','\\603',0,'0',0," . $params['levelid'] . ") ,
        (296,0,'Allow Click Delete Button AJ','',0,'\\60306','\\603',0,'0',0," . $params['levelid'] . ") ,
        (297,0,'Allow Click Print Button AJ','',0,'\\60307','\\603',0,'0',0," . $params['levelid'] . ") ,
        (298,0,'Allow Click Lock Button AJ','',0,'\\60308','\\603',0,'0',0," . $params['levelid'] . ") ,
        (299,0,'Allow Click UnLock Button AJ','',0,'\\60309','\\603',0,'0',0," . $params['levelid'] . ") ,
        (300,0,'Allow Click Post Button AJ','',0,'\\60310','\\603',0,'0',0," . $params['levelid'] . ") ,
        (301,0,'Allow Click UnPost Button AJ','',0,'\\60311','\\603',0,'0',0," . $params['levelid'] . ") ,
        (302,0,'Allow View Transaction Accounting AJ','',0,'\\60312','\\603',0,'0',0," . $params['levelid'] . ") ,
        (823,1,'Allow Click Add Item AJ','',0,'\\60313','\\603',0,'0',0," . $params['levelid'] . ") ,
        (824,1,'Allow Click Edit Item AJ','',0,'\\60314','\\603',0,'0',0," . $params['levelid'] . ") ,
        (825,1,'Allow Click Delete Item AJ','',0,'\\60315','\\603',0,'0',0," . $params['levelid'] . ") ,
        (826,1,'Allow Change Amount AJ','',0,'\\60316','\\603',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5495,0,'Allow Click Change Code Button','',0,'\\60317','\\603',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'AJ','/module/" . $folder . "/aj','Inventory Adjustment','fa fa-exchange-alt sub_menu_ico',290," . $params['levelid'] . ")";
    } //end function


    public function ts($params, $parent, $sort)
    {
        switch ($params['companyid']) {
            case 40: //cdo
                $folder = 'cdo';
                $label = 'Location Transfer';
                break;
            default:
                $folder = 'inventory';
                $label = 'Transfer Slip';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(308,0,'" . $label . "','',0,'\\604','$parent',0,'0',0," . $params['levelid'] . ") ,
        (309,0,'Allow View Transaction TS','TS',0,'\\60401','\\604',0,'0',0," . $params['levelid'] . ") ,
        (310,0,'Allow Click Edit Button  TS','',0,'\\60402','\\604',0,'0',0," . $params['levelid'] . ") ,
        (311,0,'Allow Click New Button TS','',0,'\\60403','\\604',0,'0',0," . $params['levelid'] . ") ,
        (312,0,'Allow Click Save Button TS','',0,'\\60404','\\604',0,'0',0," . $params['levelid'] . ") ,
        (314,0,'Allow Click Delete Button TS','',0,'\\60406','\\604',0,'0',0," . $params['levelid'] . ") ,
        (315,0,'Allow Click Print Button TS','',0,'\\60407','\\604',0,'0',0," . $params['levelid'] . ") ,
        (316,0,'Allow Click Lock Button TS','',0,'\\60408','\\604',0,'0',0," . $params['levelid'] . ") ,
        (317,0,'Allow Click UnLock Button TS','',0,'\\60409','\\604',0,'0',0," . $params['levelid'] . ") ,
        (318,0,'Allow Click Post Button TS','',0,'\\60410','\\604',0,'0',0," . $params['levelid'] . ") ,
        (319,0,'Allow Click UnPost Button TS','',0,'\\60411','\\604',0,'0',0," . $params['levelid'] . ") ,
        (831,1,'Allow Click Add Item TS','',0,'\\60412','\\604',0,'0',0," . $params['levelid'] . ") ,
        (832,1,'Allow Click Edit Item TS','',0,'\\60413','\\604',0,'0',0," . $params['levelid'] . ") ,
        (833,1,'Allow Click Delete Item TS','',0,'\\60414','\\604',0,'0',0," . $params['levelid'] . ") ,
        (834,1,'Allow Change Amount TS','',0,'\\60415','\\604',0,'0',0," . $params['levelid'] . "),
        (3719,1,'Allow View Dashbaord Incoming Deliveries TS','',0,'\\60416','\\604',0,'0',0," . $params['levelid'] . "),
        (5257,1,'Allow Click SO Button','',0,'\\60417','\\604',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 43: //mighty
                $qry .= ", (4492,1,'Allow Access Tripping Tab','',0,'\\60417','\\604',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4493,1,'Allow Access Dispatch Tab','',0,'\\60418','\\604',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4496,1,'Allow Trip Approved','',0,'\\60419','\\604',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4739,1,'Allow Trip Disapproved','',0,'\\60419','\\604',0,'0',0," . $params['levelid'] . ")";
                break;
            case 60: //transpower
                $qry .=  ",(5496,0,'Allow Click Change Code Button','',0,'\\60420','\\604',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'TS','/module/" . $folder . "/ts','" . $label . "','fa fa-dolly-flatbed sub_menu_ico',308," . $params['levelid'] . ")";
    } //end function

    public function is($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(257,0,'Inventory Setup','',0,'\\601','$parent',0,'0',0," . $params['levelid'] . ") ,
        (258,0,'Allow View Transaction IS','IS',0,'\\60101','\\601',0,'0',0," . $params['levelid'] . ") ,
        (259,0,'Allow Click Edit Button  IS','',0,'\\60102','\\601',0,'0',0," . $params['levelid'] . ") ,
        (260,0,'Allow Click New Button IS','',0,'\\60103','\\601',0,'0',0," . $params['levelid'] . ") ,
        (261,0,'Allow Click Save Button IS','',0,'\\60104','\\601',0,'0',0," . $params['levelid'] . ") ,
        (263,0,'Allow Click Delete Button IS','',0,'\\60106','\\601',0,'0',0," . $params['levelid'] . ") ,
        (264,0,'Allow Click Print Button IS','',0,'\\60107','\\601',0,'0',0," . $params['levelid'] . ") ,
        (265,0,'Allow Click Lock Button IS','',0,'\\60108','\\601',0,'0',0," . $params['levelid'] . ") ,
        (266,0,'Allow Click UnLock Button IS','',0,'\\60109','\\601',0,'0',0," . $params['levelid'] . ") ,
        (267,0,'Allow Click Post Button IS','',0,'\\60110','\\601',0,'0',0," . $params['levelid'] . ") ,
        (268,0,'Allow Click UnPost Button IS','',0,'\\60111','\\601',0,'0',0," . $params['levelid'] . ") ,
        (269,0,'Allow View Transaction Accounting IS','',0,'\\60112','\\601',0,'0',0," . $params['levelid'] . ") ,
        (827,1,'Allow Click Add Item IS','',0,'\\60113','\\601',0,'0',0," . $params['levelid'] . ") ,
        (828,1,'Allow Click Edit Item IS','',0,'\\60114','\\601',0,'0',0," . $params['levelid'] . ") ,
        (829,1,'Allow Click Delete Item IS','',0,'\\60115','\\601',0,'0',0," . $params['levelid'] . ") ,
        (830,1,'Allow Change Amount IS','',0,'\\60116','\\601',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5497,0,'Allow Click Change Code Button','',0,'\\60117','\\601',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'IS','/module/inventory/is','Inventory Setup','fa fa-truck-loading sub_menu_ico',257," . $params['levelid'] . ")";
    } //end function

    public function va($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2204,0,'Voyage Report','',0,'\\605','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2205,0,'Allow View Transaction VA','',0,'\\60501','\\605',0,'0',0," . $params['levelid'] . ") ,
        (2206,0,'Allow Click Edit Button  VA','',0,'\\60502','\\605',0,'0',0," . $params['levelid'] . ") ,
        (2207,0,'Allow Click New Button VA','',0,'\\60503','\\605',0,'0',0," . $params['levelid'] . ") ,
        (2208,0,'Allow Click Save Button VA','',0,'\\60504','\\605',0,'0',0," . $params['levelid'] . ") ,
        (2210,0,'Allow Click Delete Button VA','',0,'\\60506','\\605',0,'0',0," . $params['levelid'] . ") ,
        (2211,0,'Allow Click Print Button VA','',0,'\\60507','\\605',0,'0',0," . $params['levelid'] . ") ,
        (2212,0,'Allow Click Lock Button VA','',0,'\\60508','\\605',0,'0',0," . $params['levelid'] . ") ,
        (2213,0,'Allow Click UnLock Button VA','',0,'\\60509','\\605',0,'0',0," . $params['levelid'] . ") ,
        (2214,0,'Allow Click Post Button VA','',0,'\\60510','\\605',0,'0',0," . $params['levelid'] . ") ,
        (2215,0,'Allow Click UnPost Button VA','',0,'\\60511','\\605',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'VOYAGEREPORT','/module/inventory/va','Voyage Report','fa fa-chalkboard-teacher sub_menu_ico',2204," . $params['levelid'] . ")";
    } //end function

    public function parentpayable($params, $parent, $sort)
    {
        $p = $parent;
        $companyid = $params['companyid'];
        $parent = '\\' . $parent;
        $qry = "(547,0,'PAYABLE','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PAYABLES',$sort,'fa fa-university',',AP,PV,CV'," . $params['levelid'] . ")";
        } elseif ($companyid == 8) { //maxipro
            return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PAYABLES',$sort,'fa fa-university',',AP,PV,CV'," . $params['levelid'] . ")";
        } else {
            return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PAYABLES',$sort,'fa fa-university',',AP,PV,CV,PQ,SV,CHECKRELEASE'," . $params['levelid'] . ")";
        }
    } //end function

    public function ap($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(133,0,'Payable Setup','',0,'\\201','$parent',0,'0',0," . $params['levelid'] . ") ,
        (134,0,'Allow View Transaction AP','AP',0,'\\20101','\\201',0,'0',0," . $params['levelid'] . ") ,
        (135,0,'Allow Click Edit Button  AP','',0,'\\20102','\\201',0,'0',0," . $params['levelid'] . ") ,
        (136,0,'Allow Click New Button AP','',0,'\\20103','\\201',0,'0',0," . $params['levelid'] . ") ,
        (137,0,'Allow Click Save Button AP','',0,'\\20104','\\201',0,'0',0," . $params['levelid'] . ") ,
        (139,0,'Allow Click Delete Button AP','',0,'\\20106','\\201',0,'0',0," . $params['levelid'] . ") ,
        (140,0,'Allow Click Print Button AP','',0,'\\20107','\\201',0,'0',0," . $params['levelid'] . ") ,
        (141,0,'Allow Click Lock Button AP','',0,'\\20108','\\201',0,'0',0," . $params['levelid'] . ") ,
        (142,0,'Allow Click UnLock Button AP','',0,'\\20109','\\201',0,'0',0," . $params['levelid'] . ") ,
        (143,0,'Allow Click Post Button AP','',0,'\\20110','\\201',0,'0',0," . $params['levelid'] . ") ,
        (144,0,'Allow Click UnPost Button AP','',0,'\\20111','\\201',0,'0',0," . $params['levelid'] . ") ,
        (145,0,'Allow Click Add Account AP','',0,'\\20112','\\201',0,'0',0," . $params['levelid'] . ") ,
        (146,0,'Allow Click Edit Account AP','',0,'\\20113','\\201',0,'0',0," . $params['levelid'] . ") ,
        (147,0,'Allow Click Delete Account AP','',0,'\\20114','\\201',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5498,0,'Allow Click Change Code Button','',0,'\\20115','\\201',0,'0',0," . $params['levelid'] . ")";
                break;
        }
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AP','/module/payable/ap','AP Setup','fa fa-coins sub_menu_ico',133," . $params['levelid'] . ")";
    } //end function

    public function pv($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(370,0,'Accounts Payable Voucher','',0,'\\202','$parent',0,'0',0," . $params['levelid'] . ") ,
        (371,0,'Allow View Transaction PV','APV',0,'\\20201','\\202',0,'0',0," . $params['levelid'] . ") ,
        (372,0,'Allow Click Edit Button  PV','',0,'\\20202','\\202',0,'0',0," . $params['levelid'] . ") ,
        (373,0,'Allow Click New Button PV','',0,'\\20203','\\202',0,'0',0," . $params['levelid'] . ") ,
        (374,0,'Allow Click Save Button PV','',0,'\\20204','\\202',0,'0',0," . $params['levelid'] . ") ,
        (376,0,'Allow Click Delete Button PV','',0,'\\20206','\\202',0,'0',0," . $params['levelid'] . ") ,
        (377,0,'Allow Click Print Button PV','',0,'\\20207','\\202',0,'0',0," . $params['levelid'] . ") ,
        (378,0,'Allow Click Lock Button PV','',0,'\\20208','\\202',0,'0',0," . $params['levelid'] . ") ,
        (379,0,'Allow Click UnLock Button PV','',0,'\\20209','\\202',0,'0',0," . $params['levelid'] . ") ,
        (380,0,'Allow Click Post Button PV','',0,'\\20210','\\202',0,'0',0," . $params['levelid'] . ") ,
        (381,0,'Allow Click UnPost Button PV','',0,'\\20211','\\202',0,'0',0," . $params['levelid'] . ") ,
        (382,0,'Allow Click Add Account PV','',0,'\\20212','\\202',0,'0',0," . $params['levelid'] . ") ,
        (383,0,'Allow Click Edit Account PV','',0,'\\20213','\\202',0,'0',0," . $params['levelid'] . ") ,
        (384,0,'Allow Click Delete Account PV','',0,'\\20214','\\202',0,'0',0," . $params['levelid'] . ") ";

        switch ($params['companyid']) {
            case 10: //afti  
                $qry .= ", (3579,1,'Allow Click Make Payment','',0,'\\20215','\\202',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (3591,1,'Allow Add Item','',0,'\\20216','\\202',0,'0',0," . $params['levelid'] . ")";
                break;
            case 60: //transpower
                $qry .=  ",(5499,0,'Allow Click Change Code Button','',0,'\\20217','\\202',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'PV','/module/payable/pv','Accounts Payable Voucher','fa fa-credit-card sub_menu_ico',370," . $params['levelid'] . ")";
    } //end function


    public function cv($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(116,0,'Cash/Check Voucher','',0,'\\203','$parent',0,'0',0," . $params['levelid'] . ") ,
        (117,0,'Allow View Transaction CV','CV',0,'\\20301','\\203',0,'0',0," . $params['levelid'] . ") ,
        (118,0,'Allow Click Edit Button  CV','',0,'\\20302','\\203',0,'0',0," . $params['levelid'] . ") ,
        (119,0,'Allow Click New Button CV','',0,'\\20303','\\203',0,'0',0," . $params['levelid'] . ") ,
        (120,0,'Allow Click Save Button CV','',0,'\\20304','\\203',0,'0',0," . $params['levelid'] . ") ,
        (122,0,'Allow Click Delete Button CV','',0,'\\20306','\\203',0,'0',0," . $params['levelid'] . ") ,
        (123,0,'Allow Click Print Button CV','',0,'\\20307','\\203',0,'0',0," . $params['levelid'] . ") ,
        (124,0,'Allow Click Lock Button CV','',0,'\\20308','\\203',0,'0',0," . $params['levelid'] . ") ,
        (125,0,'Allow Click UnLock Button CV','',0,'\\20309','\\203',0,'0',0," . $params['levelid'] . ") ,
        (126,0,'Allow Click Post Button CV','',0,'\\20310','\\203',0,'0',0," . $params['levelid'] . ") ,
        (127,0,'Allow Click UnPost Button CV','',0,'\\20311','\\203',0,'0',0," . $params['levelid'] . ") ,
        (128,0,'Allow Click Add Account CV','',0,'\\20312','\\203',0,'0',0," . $params['levelid'] . ") ,
        (129,0,'Allow Click Edit Account CV','',0,'\\20313','\\203',0,'0',0," . $params['levelid'] . ") ,
        (130,0,'Allow Click Delete Account CV','',0,'\\20314','\\203',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 16: //ati
                $qry .= ",(3985,0,'Allow Click Approved CV','',0,'\\20315','\\203',0,'0',0," . $params['levelid'] . "),
                        (3986,0,'Allow Click Initial Checking CV','',0,'\\20316','\\203',0,'0',0," . $params['levelid'] . "),
                        (3987,0,'Allow Click Final Checking CV','',0,'\\20317','\\203',0,'0',0," . $params['levelid'] . "),
                        (3988,0,'Allow Click Payment Released CV','',0,'\\20318','\\203',0,'0',0," . $params['levelid'] . "),
                        (3989,0,'Allow Click Forwarded to Encoder CV','',0,'\\20319','\\203',0,'0',0," . $params['levelid'] . "),
                        (3990,0,'Allow Click Forwarded to WH CV','',0,'\\20320','\\203',0,'0',0," . $params['levelid'] . "),
                        (3991,0,'Allow Click Items Collected CV','',0,'\\20321','\\203',0,'0',0," . $params['levelid'] . "),
                        (3992,0,'Allow Click Forwarded to OP CV','',0,'\\20322','\\203',0,'0',0," . $params['levelid'] . "),
                        (3993,0,'Allow Click Forwarded to Asset CV','',0,'\\20323','\\203',0,'0',0," . $params['levelid'] . "),
                        (3994,0,'Allow Click For Liquidation CV','',0,'\\20324','\\203',0,'0',0," . $params['levelid'] . "),
                        (3995,0,'Allow Click Forwarded to Acctg CV','',0,'\\20325','\\203',0,'0',0," . $params['levelid'] . "),
                        (3996,0,'Allow Click For Checking CV','',0,'\\20326','\\203',0,'0',0," . $params['levelid'] . "),
                        (3997,0,'Allow Click Check Issued CV','',0,'\\20327','\\203',0,'0',0," . $params['levelid'] . "),
                        (3998,0,'Allow Click Paid CV','',0,'\\20328','\\203',0,'0',0," . $params['levelid'] . "),
                        (3999,0,'Allow Click Checked CV','',0,'\\20329','\\203',0,'0',0," . $params['levelid'] . "),
                        (4000,0,'Allow Click Advances Clearead CV','',0,'\\20330','\\203',0,'0',0," . $params['levelid'] . "),
                        (4001,0,'Allow Click SOA Received CV','',0,'\\20331','\\203',0,'0',0," . $params['levelid'] . "),
                        (4002,0,'Allow Click For Posting CV','',0,'\\20332','\\203',0,'0',0," . $params['levelid'] . "),
                        (4004,0,'Allow Click For Revision CV','',0,'\\20333','\\203',0,'0',0," . $params['levelid'] . "),
                        (4143,0,'Allow Edit Amount CV','',0,'\\20334','\\203',0,'0',0," . $params['levelid'] . "),
                        (4144,0,'Allow Edit Approved CV','',0,'\\20335','\\203',0,'0',0," . $params['levelid'] . "),
                        (4195,0,'Allow Generate Surcharge','',0,'\\20336','\\203',0,'0',0," . $params['levelid'] . "),
                        (4196,0,'Allow Void Entry CV','',0,'\\20337','\\203',0,'0',0," . $params['levelid'] . "),
                        (4387,0,'Admin','',0,'\\20338','\\203',0,'0',0," . $params['levelid'] . "),
                        (4406,0,'Allow Remove Tagging Payment Released','',0,'\\20339','\\203',0,'0',0," . $params['levelid'] . ")";
                break;
            case 39: //cbbsi
                $qry .= ",(4391,0,'Allow Update Release','',0,'\\20315','\\203',0,'0',0," . $params['levelid'] . ")";
                break;
            case 55: //afli
                $qry .= ",(5085,0,'Allow Void Transaction','',0,'\\20316','\\203',0,'0',0," . $params['levelid'] . ")";
                break;
            case 60: //transpower
                $qry .=  ",(5500,0,'Allow Click Change Code Button','',0,'\\20340','\\203',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);

        $folder = 'payable';
        switch ($params['companyid']) {
            case 16: //ati
                $folder = 'ati';
                break;
            case 55: //lending afli
                $folder = 'lending';
                break;
            case 59://roosevelt
                $folder ='rc952c55ab9eb85660b7cab413fa7c803';
                break;
        }

        return "($sort,$p,'CV','/module/" . $folder . "/cv','Cash/Check Voucher','fa fa-money-check-alt sub_menu_ico',116," . $params['levelid'] . ")";
    } //end function


    public function checkrelease($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4024,0,'Check Releasing','',0,'\\210','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4025,0,'Allow View Transaction','CHECKRELEASE',0,'\\21001','\\210',0,'0',0," . $params['levelid'] . ") ,
        (4026,0,'Allow Click Release Button','',0,'\\21002','\\210',0,'0',0," . $params['levelid'] . "),
        (4027,0,'Allow Click Print Button','',0,'\\21003','\\210',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'CHECKRELEASE','/headtable/payable/checkrelease','Check Releasing','fa fa-file-invoice-dollar sub_menu_ico',4024," . $params['levelid'] . ")";
    } //end function

    public function pq($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2060,0,'Petty Cash Request','',0,'\\204','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2061,0,'Allow View Transaction PCR','PCR',0,'\\20401','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2062,0,'Allow Click Edit Button  PCR','',0,'\\20402','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2063,0,'Allow Click New Button PCR','',0,'\\20403','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2064,0,'Allow Click Save Button PCR','',0,'\\20404','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2066,0,'Allow Click Delete Button PCR','',0,'\\20406','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2067,0,'Allow Click Print Button PCR','',0,'\\20407','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2068,0,'Allow Click Lock Button PCR','',0,'\\20408','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2069,0,'Allow Click UnLock Button PCR','',0,'\\20409','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2070,0,'Allow Click Post Button PCR','',0,'\\20410','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2071,0,'Allow Click UnPost Button PCR','',0,'\\20411','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2072,0,'Allow Click Add Account PCR','',0,'\\20412','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2073,0,'Allow Click Edit Account PCR','',0,'\\20413','\\204',0,'0',0," . $params['levelid'] . ") ,
        (2074,0,'Allow Click Delete Account PCR','',0,'\\20414','\\204',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PQ','/module/payable/pq','Petty Cash Request','fa fa-money-check-alt sub_menu_ico',2060," . $params['levelid'] . ")";
    } //end function

    public function sv($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2075,0,'Petty Cash Voucher','',0,'\\205','$parent',0,'0',0," . $params['levelid'] . ") ,
       (2076,0,'Allow View Transaction PCV','PCV',0,'\\20501','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2077,0,'Allow Click Edit Button  PCV','',0,'\\20502','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2078,0,'Allow Click New Button PCV','',0,'\\20503','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2079,0,'Allow Click Save Button PCV','',0,'\\20504','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2081,0,'Allow Click Delete Button PCV','',0,'\\20506','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2082,0,'Allow Click Print Button PCV','',0,'\\20507','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2083,0,'Allow Click Lock Button PCV','',0,'\\20508','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2084,0,'Allow Click UnLock Button PCV','',0,'\\20509','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2085,0,'Allow Click Post Button PCV','',0,'\\20510','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2086,0,'Allow Click UnPost Button PCV','',0,'\\20511','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2087,0,'Allow Click Add Account PCV','',0,'\\20512','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2088,0,'Allow Click Edit Account PCV','',0,'\\20513','\\205',0,'0',0," . $params['levelid'] . ") ,
       (2089,0,'Allow Click Delete Account PCV','',0,'\\20514','\\205',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SV','/module/payable/sv','Petty Cash Voucher','fa fa-money-check-alt sub_menu_ico',2075," . $params['levelid'] . ")";
    } //end function

    public function parentreceivable($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(546,0,'RECEIVABLES','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'RECEIVABLES',$sort,'fa fa-hand-holding-usd',',AR,CR,KR'," . $params['levelid'] . ")";
    } //end function

    public function dc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4409,0,'Daily Collection Report','',0,'\\312','$parent',0,'0',0," . $params['levelid'] . "),
        (4410,0,'Allow View Transaction DC','DC',0,'\\31201','\\312',0,'0',0," . $params['levelid'] . "),
        (4411,0,'Allow Click Edit Button DC','',0,'\\31202','\\312',0,'0',0," . $params['levelid'] . "),
        (4412,0,'Allow Click New Button DC','',0,'\\31203','\\312',0,'0',0," . $params['levelid'] . "),
        (4413,0,'Allow Click Save Button DC','',0,'\\31204','\\312',0,'0',0," . $params['levelid'] . "),
        (4414,0,'Allow Click Delete Button DC','',0,'\\31205','\\312',0,'0',0," . $params['levelid'] . "),
        (4415,0,'Allow Click Print Button DC','',0,'\\31206','\\312',0,'0',0," . $params['levelid'] . "),
        (4416,0,'Allow Click Lock Button DC','',0,'\\31207','\\312',0,'0',0," . $params['levelid'] . "),
        (4417,0,'Allow Click UnLock Button DC','',0,'\\31208','\\312',0,'0',0," . $params['levelid'] . "),
        (4418,0,'Allow Click Post Button DC','',0,'\\31209','\\312',0,'0',0," . $params['levelid'] . "),
        (4419,0,'Allow Click Add Account DC','',0,'\\31210','\\312',0,'0',0," . $params['levelid'] . "),
        (4420,0,'Allow Click Edit Account DC','',0,'\\31211','\\312',0,'0',0," . $params['levelid'] . "),
        (4421,0,'Allow Click Delete Account DC','',0,'\\31212','\\312',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DC','/module/receivable/dc','Daily Collection Report','fa fa-money-bill-alt sub_menu_ico',4409," . $params['levelid'] . ")";
    }

    public function ar($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(239,0,'Receivable Setup','',0,'\\301','$parent',0,'0',0," . $params['levelid'] . ") ,
        (240,0,'Allow View Transaction RS','AR',0,'\\30101','\\301',0,'0',0," . $params['levelid'] . ") ,
        (241,0,'Allow Click Edit Button  RS','',0,'\\30102','\\301',0,'0',0," . $params['levelid'] . ") ,
        (242,0,'Allow Click New Button RS','',0,'\\30103','\\301',0,'0',0," . $params['levelid'] . ") ,
        (243,0,'Allow Click Save Button RS','',0,'\\30104','\\301',0,'0',0," . $params['levelid'] . ") ,
        (245,0,'Allow Click Delete Button RS','',0,'\\30106','\\301',0,'0',0," . $params['levelid'] . ") ,
        (246,0,'Allow Click Print Button RS','',0,'\\30107','\\301',0,'0',0," . $params['levelid'] . ") ,
        (247,0,'Allow Click Lock Button RS','',0,'\\30108','\\301',0,'0',0," . $params['levelid'] . ") ,
        (248,0,'Allow Click UnLock Button RS','',0,'\\30109','\\301',0,'0',0," . $params['levelid'] . ") ,
        (249,0,'Allow Click Post Button RS','',0,'\\30110','\\301',0,'0',0," . $params['levelid'] . ") ,
        (250,0,'Allow Click UnPost Button RS','',0,'\\30111','\\301',0,'0',0," . $params['levelid'] . ") ,
        (251,0,'Allow Click Add Account RS','',0,'\\30112','\\301',0,'0',0," . $params['levelid'] . ") ,
        (252,0,'Allow Click Edit Account RS','',0,'\\30113','\\301',0,'0',0," . $params['levelid'] . ") ,
        (253,0,'Allow Click Delete Account RS','',0,'\\30114','\\301',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 40: //cdo
                $qry .= ",(4633,0,'Allow Click Reconstruct','',0,'\\30115','\\301',0,'0',0," . $params['levelid'] . ") ";
                break;
            case 60: //transpower
                $qry .=  ",(5501,0,'Allow Click Change Code Button','',0,'\\30116','\\301',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'AR','/module/receivable/ar','AR Setup','fa fa-money-bill-alt sub_menu_ico',239," . $params['levelid'] . ")";
    } //end function


    public function cr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(223,0,'Received Payment','',0,'\\303','$parent',0,'0',0," . $params['levelid'] . ") ,
        (224,0,'Allow View Transaction CR ','CR',0,'\\30301','\\303',0,'0',0," . $params['levelid'] . ") ,
        (225,0,'Allow Click Edit Button  CR ','',0,'\\30302','\\303',0,'0',0," . $params['levelid'] . ") ,
        (226,0,'Allow Click New Button CR ','',0,'\\30303','\\303',0,'0',0," . $params['levelid'] . ") ,
        (227,0,'Allow Click Save Button CR ','',0,'\\30304','\\303',0,'0',0," . $params['levelid'] . ") ,
        (229,0,'Allow Click Delete Button CR ','',0,'\\30306','\\303',0,'0',0," . $params['levelid'] . ") ,
        (230,0,'Allow Click Print Button CR ','',0,'\\30307','\\303',0,'0',0," . $params['levelid'] . ") ,
        (231,0,'Allow Click Lock Button CR ','',0,'\\30308','\\303',0,'0',0," . $params['levelid'] . ") ,
        (232,0,'Allow Click UnLock Button CR ','',0,'\\30309','\\303',0,'0',0," . $params['levelid'] . ") ,
        (233,0,'Allow Click Post Button CR ','',0,'\\30310','\\303',0,'0',0," . $params['levelid'] . ") ,
        (234,0,'Allow Click UnPost Button CR ','',0,'\\30311','\\303',0,'0',0," . $params['levelid'] . ") ,
        (235,0,'Allow Click Add Account CR','',0,'\\30312','\\303',0,'0',0," . $params['levelid'] . ") ,
        (236,0,'Allow Click Edit Account CR','',0,'\\30313','\\303',0,'0',0," . $params['levelid'] . ") ,
        (4501,0,'Allow Click Void transaction CR','',0,'\\30315','\\303',0,'0',0," . $params['levelid'] . ") ,
        (237,0,'Allow Click Delete Account CR','',0,'\\30314','\\303',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5502,0,'Allow Click Change Code Button','',0,'\\30316','\\303',0,'0',0," . $params['levelid'] . ")";
                break;
        }
        $this->insertattribute($params, $qry);

        $folder = 'receivable';
        switch ($params['companyid']) {
            case 55: //lending afli
                $folder = 'lending';
                break;
            case 0: //bms
                $folder = 'barangay';
                break;
        }

        return "($sort,$p,'CR','/module/" . $folder . "/cr','Received Payment','fa fa-file-invoice sub_menu_ico',223," . $params['levelid'] . ")";
    } //end function

    public function kr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(208,0,'Counter Receipt','',0,'\\302','$parent',0,'0',0," . $params['levelid'] . ") ,
        (209,0,'Allow View Transaction KR ','KR',0,'\\30201','\\302',0,'0',0," . $params['levelid'] . ") ,
        (210,0,'Allow Click Edit Button  KR ','',0,'\\30202','\\302',0,'0',0," . $params['levelid'] . ") ,
        (211,0,'Allow Click New Button KR ','',0,'\\30203','\\302',0,'0',0," . $params['levelid'] . ") ,
        (212,0,'Allow Click Save Button KR ','',0,'\\30204','\\302',0,'0',0," . $params['levelid'] . ") ,
        (214,0,'Allow Click Delete Button KR ','',0,'\\30206','\\302',0,'0',0," . $params['levelid'] . ") ,
        (215,0,'Allow Click Print Button KR ','',0,'\\30207','\\302',0,'0',0," . $params['levelid'] . ") ,
        (216,0,'Allow Click Lock Button KR ','',0,'\\30208','\\302',0,'0',0," . $params['levelid'] . ") ,
        (217,0,'Allow Click UnLock Button KR ','',0,'\\30209','\\302',0,'0',0," . $params['levelid'] . ") ,
        (218,0,'Allow Click Post Button KR ','',0,'\\30210','\\302',0,'0',0," . $params['levelid'] . ") ,
        (219,0,'Allow Click UnPost Button KR ','',0,'\\30211','\\302',0,'0',0," . $params['levelid'] . ") ,
        (220,0,'Allow Click Add Account KR','',0,'\\30212','\\302',0,'0',0," . $params['levelid'] . ") ,
        (221,0,'Allow Click Edit Account KR','',0,'\\30213','\\302',0,'0',0," . $params['levelid'] . ") ,
        (222,0,'Allow Click Delete Account KR','',0,'\\30214','\\302',0,'0',0," . $params['levelid'] . ")";
        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5503,0,'Allow Click Change Code Button','',0,'\\30215','\\302',0,'0',0," . $params['levelid'] . ")";
                break;
        }
        $this->insertattribute($params, $qry);
        return "($sort,$p,'KR','/module/receivable/kr','Counter Receipt','fa fa-calculator sub_menu_ico',208," . $params['levelid'] . ")";
    } //end function

    public function ka($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4220,0,'AR Audit','',0,'\\311','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4221,0,'Allow View Transaction','KA',0,'\\31101','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4222,0,'Allow Click Edit Button','',0,'\\31102','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4223,0,'Allow Click New Button','',0,'\\31103','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4224,0,'Allow Click Save Button','',0,'\\31104','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4225,0,'Allow Click Delete Button','',0,'\\31106','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4226,0,'Allow Click Print Button','',0,'\\31107','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4227,0,'Allow Click Lock Button','',0,'\\31108','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4228,0,'Allow Click UnLock Button','',0,'\\31109','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4229,0,'Allow Click Post Button','',0,'\\31110','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4230,0,'Allow Click UnPost Button','',0,'\\31111','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4231,0,'Allow Click Add Account','',0,'\\31112','\\311',0,'0',0," . $params['levelid'] . ") ,
        (4232,0,'Allow Click Delete Account','',0,'\\31113','\\311',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'KA','/module/cbbsi/ka','AR Audit','fa fa-calculator sub_menu_ico',4220," . $params['levelid'] . ")";
    } //end function

    public function py($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4233,0,'Payment Listing','',0,'\\206','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4234,0,'Allow View Transaction','PY',0,'\\20601','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4235,0,'Allow Click Edit Button','',0,'\\20602','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4236,0,'Allow Click New Button','',0,'\\20603','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4237,0,'Allow Click Save Button','',0,'\\20604','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4238,0,'Allow Click Delete Button','',0,'\\20606','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4239,0,'Allow Click Print Button','',0,'\\20607','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4240,0,'Allow Click Lock Button','',0,'\\20608','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4241,0,'Allow Click UnLock Button','',0,'\\20609','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4242,0,'Allow Click Post Button','',0,'\\20610','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4243,0,'Allow Click UnPost Button','',0,'\\20611','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4244,0,'Allow Click Add Account','',0,'\\20612','\\206',0,'0',0," . $params['levelid'] . ") ,
        (4245,0,'Allow Click Delete Account','',0,'\\20613','\\206',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PY','/module/cbbsi/py','Payment Listing','fa fa-calculator sub_menu_ico',4233," . $params['levelid'] . ")";
    } //end function

    public function ps($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4422,0,'Payment Listing Summary','',0,'\\207','$parent',0,'0',0," . $params['levelid'] . "),
        (4423,0,'Allow View Transaction PS','PS',0,'\\20701','\\207',0,'0',0," . $params['levelid'] . "),
        (4424,0,'Allow Click Edit Button PS','',0,'\\20702','\\207',0,'0',0," . $params['levelid'] . "),
        (4425,0,'Allow click New Button PS','',0,'\\20703','\\207',0,'0',0," . $params['levelid'] . "),
        (4426,0,'Allow Click Save Button PS','',0,'\\20704','\\207',0,'0',0," . $params['levelid'] . "),
        (4427,0,'Allow Click Delete Button PS','',0,'\\20705','\\207',0,'0',0," . $params['levelid'] . "),
        (4428,0,'Allow Click Print Button PS','',0,'\\20706','\\207',0,'0',0," . $params['levelid'] . "),
        (4429,0,'Allow Click Lock Button PS','',0,'\\20707','\\207',0,'0',0," . $params['levelid'] . "),
        (4430,0,'Allow Click UnLock Button PS','',0,'\\20708','\\207',0,'0',0," . $params['levelid'] . "),
        (4431,0,'Allow Click Post Button PS','',0,'\\20709','\\207',0,'0',0," . $params['levelid'] . "),
        (4432,0,'Allow Click UnPost Button PS','',0,'\\20710','\\207',0,'0',0," . $params['levelid'] . "),
        (4433,0,'Allow Click Add Account PS','',0,'\\20711','\\207',0,'0',0," . $params['levelid'] . "),
        (4434,0,'Allow Click Delete Account PS','',0,'\\20712','\\207',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PS','/module/cbbsi/ps','Payment Listing Summary','fa fa-calculator sub_menu_ico',4422," . $params['levelid'] . ")";
    }

    public function parentaccounting($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(548,0,'ACCOUNTING','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'ACCOUNTING',$sort,'local_atm',',GJ,DS,bankrecon'," . $params['levelid'] . ")";
    } //end function

    public function coa($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2,0,'Chart of Accounts','',0,'\\701','$parent',0,'0',0," . $params['levelid'] . ") ,
        (3,0,'Allow View Chart of Accounts','COA',0,'\\70101','\\701',0,'0',0," . $params['levelid'] . ") ,
        (4,0,'Allow Click Edit Button  COA','',0,'\\70102','\\701',0,'0',0," . $params['levelid'] . ") ,
        (5,0,'Allow Click New Button COA','',0,'\\70103','\\701',0,'0',0," . $params['levelid'] . ") ,
        (6,0,'Allow Click Save Button COA','',0,'\\70104','\\701',0,'0',0," . $params['levelid'] . ") ,
        (7,0,'Allow Click Delete Button COA','',0,'\\70105','\\701',0,'0',0," . $params['levelid'] . ") ,
        (8,0,'Allow Click Print Button COA','',0,'\\70106','\\701',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'coa','/uniquecoa/unique/coa','Chart of Accounts','fa fa-file sub_menu_ico',2," . $params['levelid'] . ")";
    } //end function

    public function coaalias($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'coaalias','/tableentries/tableentry/coaalias','COA Default Alias','fa fa-file sub_menu_ico',4578," . $params['levelid'] . ")";
    }

    public function gj($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(343,0,'General Journal','',0,'\\702','$parent',0,'0',0," . $params['levelid'] . ") ,
       (344,0,'Allow View Transaction GJ','GJ',0,'\\70201','\\702',0,'0',0," . $params['levelid'] . ") ,
       (345,0,'Allow Click Edit Button  GJ','',0,'\\70202','\\702',0,'0',0," . $params['levelid'] . ") ,
       (346,0,'Allow Click New Button GJ','',0,'\\70203','\\702',0,'0',0," . $params['levelid'] . ") ,
       (347,0,'Allow Click Save Button GJ','',0,'\\70204','\\702',0,'0',0," . $params['levelid'] . ") ,
       (349,0,'Allow Click Delete Button GJ','',0,'\\70206','\\702',0,'0',0," . $params['levelid'] . ") ,
       (350,0,'Allow Click Print Button GJ','',0,'\\70207','\\702',0,'0',0," . $params['levelid'] . ") ,
       (351,0,'Allow Click Lock Button GJ','',0,'\\70208','\\702',0,'0',0," . $params['levelid'] . ") ,
       (352,0,'Allow Click UnLock Button GJ','',0,'\\70209','\\702',0,'0',0," . $params['levelid'] . ") ,
       (353,0,'Allow Click Post Button GJ','',0,'\\70210','\\702',0,'0',0," . $params['levelid'] . ") ,
       (354,0,'Allow Click UnPost Button GJ','',0,'\\70211','\\702',0,'0',0," . $params['levelid'] . ") ,
       (355,0,'Allow Click Add Account GJ','',0,'\\70212','\\702',0,'0',0," . $params['levelid'] . ") ,
       (356,0,'Allow Click Edit Account GJ','',0,'\\70213','\\702',0,'0',0," . $params['levelid'] . ") ,
       (357,0,'Allow Click Delete Account GJ','',0,'\\70214','\\702',0,'0',0," . $params['levelid'] . ")";
        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5504,0,'Allow Click Change Code Button','',0,'\\70215','\\702',0,'0',0," . $params['levelid'] . ")";
                break;
        }
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GJ','/module/accounting/gj','General Journal','fa fa-book sub_menu_ico',343," . $params['levelid'] . ")";
    } //end function


    public function gd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1740,0,'Debit Memo','',0,'\\703','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1741,0,'Allow View Transaction GD','GD',0,'\\70301','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1742,0,'Allow Click Edit Button  GD','',0,'\\70302','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1743,0,'Allow Click New Button GD','',0,'\\70303','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1744,0,'Allow Click Save Button GD','',0,'\\70304','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1746,0,'Allow Click Delete Button GD','',0,'\\70306','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1747,0,'Allow Click Print Button GD','',0,'\\70307','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1748,0,'Allow Click Lock Button GD','',0,'\\70308','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1749,0,'Allow Click UnLock Button GD','',0,'\\70309','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1750,0,'Allow Click Post Button GD','',0,'\\70310','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1751,0,'Allow Click UnPost Button GD','',0,'\\70311','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1752,0,'Allow Click Add Account GD','',0,'\\70312','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1753,0,'Allow Click Edit Account GD','',0,'\\70313','\\703',0,'0',0," . $params['levelid'] . ") ,
        (1754,0,'Allow Click Delete Account GD','',0,'\\70314','\\703',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5506,0,'Allow Click Change Code Button','',0,'\\70315','\\703',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'GD','/module/accounting/gd','Debit Memo','fa fa-folder-plus sub_menu_ico',1740," . $params['levelid'] . ")";
    } //end function

    public function gc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1760,0,'Credit Memo','',0,'\\704','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1761,0,'Allow View Transaction GC','GC',0,'\\70401','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1762,0,'Allow Click Edit Button  GC','',0,'\\70402','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1763,0,'Allow Click New Button GC','',0,'\\70403','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1764,0,'Allow Click Save Button GC','',0,'\\70404','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1766,0,'Allow Click Delete Button GC','',0,'\\70406','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1767,0,'Allow Click Print Button GC','',0,'\\70407','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1768,0,'Allow Click Lock Button GC','',0,'\\70408','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1769,0,'Allow Click UnLock Button GC','',0,'\\70409','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1770,0,'Allow Click Post Button GC','',0,'\\70410','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1771,0,'Allow Click UnPost Button GC','',0,'\\70411','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1772,0,'Allow Click Add Account GC','',0,'\\70412','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1773,0,'Allow Click Edit Account GC','',0,'\\70413','\\704',0,'0',0," . $params['levelid'] . ") ,
        (1774,0,'Allow Click Delete Account GC','',0,'\\70414','\\704',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5507,0,'Allow Click Change Code Button','',0,'\\70415','\\704',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'GC','/module/accounting/gc','Credit Memo','fa fa-folder-minus sub_menu_ico',1760," . $params['levelid'] . ")";
    } //end function

    public function ds($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(326,0,'Deposit Slip','',0,'\\304','$parent',0,'0',0," . $params['levelid'] . ") ,
        (327,0,'Allow View Transaction DS','DS',0,'\\30401','\\304',0,'0',0," . $params['levelid'] . ") ,
        (328,0,'Allow Click Edit Button  DS','',0,'\\30402','\\304',0,'0',0," . $params['levelid'] . ") ,
        (329,0,'Allow Click New Button DS','',0,'\\30403','\\304',0,'0',0," . $params['levelid'] . ") ,
        (330,0,'Allow Click Save Button DS','',0,'\\30404','\\304',0,'0',0," . $params['levelid'] . ") ,
        (332,0,'Allow Click Delete Button DS','',0,'\\30406','\\304',0,'0',0," . $params['levelid'] . ") ,
        (333,0,'Allow Click Print Button DS','',0,'\\30407','\\304',0,'0',0," . $params['levelid'] . ") ,
        (334,0,'Allow Click Lock Button DS','',0,'\\30408','\\304',0,'0',0," . $params['levelid'] . ") ,
        (335,0,'Allow Click UnLock Button DS','',0,'\\30409','\\304',0,'0',0," . $params['levelid'] . ") ,
        (336,0,'Allow Click Post Button DS','',0,'\\30410','\\304',0,'0',0," . $params['levelid'] . ") ,
        (337,0,'Allow Click UnPost Button DS','',0,'\\30411','\\304',0,'0',0," . $params['levelid'] . ") ,
        (338,0,'Allow Click Add Account DS','',0,'\\30412','\\304',0,'0',0," . $params['levelid'] . ") ,
        (339,0,'Allow Click Edit Account DS','',0,'\\30413','\\304',0,'0',0," . $params['levelid'] . ") ,
        (340,0,'Allow Click Delete Account DS','',0,'\\30414','\\304',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 60: //transpower
                $qry .=  ",(5505,0,'Allow Click Change Code Button','',0,'\\30415','\\304',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'DS','/module/accounting/ds','Deposit Slip','fa fa-edit sub_menu_ico',326," . $params['levelid'] . ")";
    } //end function

    public function bankrecon($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2367,0,'Bank Reconciliation','',0,'\\305','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2368,0,'Allow View Transaction','BANKRECON',0,'\\30501','\\305',0,'0',0," . $params['levelid'] . ") ,
        (2369,0,'Allow Click Clear Date Button','',0,'\\30502','\\305',0,'0',0," . $params['levelid'] . "),
        (3623,0,'Allow Click Print Button','',0,'\\30503','\\305',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BANKRECON','/headtable/accounting/bankrecon','Bank Reconciliation','fa fa-file-invoice-dollar sub_menu_ico',2367," . $params['levelid'] . ")";
    } //end function

    public function budget($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2370,0,'Budget Setup','',0,'\\306','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2371,0,'Allow View Transaction','BUDGET',0,'\\30601','\\306',0,'0',0," . $params['levelid'] . ") ,
        (2372,0,'Allow Create Budget','',0,'\\30602','\\306',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BUDGET','/headtable/accounting/entrybudget','Budget Setup','fa fa-piggy-bank sub_menu_ico',2370," . $params['levelid'] . ")";
    } //end function

    public function checksetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2581,1,'Check Series Setup','',0,'\\307','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrychecksetup','/tableentries/tableentry/entrychecksetup','Check Series Setup','fa fa-check sub_menu_ico',2581," . $params['levelid'] . ")";
    } //end function

    public function exchangerate($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2582,1,'Exchange Rate Setup','',0,'\\308','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entryexchangerate','/tableentries/tableentry/entryexchangerate','Exchange Rate Setup','fa fa-file-invoice-dollar sub_menu_ico',2582," . $params['levelid'] . ")";
    } //end function

    public function postdep($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2236,0,'Depreciation Schedule','',0,'\\705','$parent',0,'0',0," . $params['levelid'] . "),
           (2237,0,'Allow View Transaction Depreciation','',0,'\\70501','\\705',0,'0',0," . $params['levelid'] . "),
           (2238,0,'Allow Click Post Button','',0,'\\70502','\\705',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'postdep','/tableentries/fixedasset/postdep','Depreciation Schedule','fa fa-edit sub_menu_ico',2236," . $params['levelid'] . ")";
    } //end function

    public function parentitemmaster($params, $parent, $sort)
    {
        $systype = $this->companysetup->getsystemtype($params);
        $companyid = $params['companyid'];
        $modules = "',model,stockgroup,brand,itemclass,categories,project,part'";
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $modules = "',model,stockgroup,brand,itemclass,categories,project,part,industry'";
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1054,0,'OTHER MASTERS','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'OTHER MASTERS',$sort,'list_alt'," . $modules . "," . $params['levelid'] . ")";
    } //end function

    public function model($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        switch ($companyid) {
            case 14: //majesty
                $fieldLabel = 'Item Generic';
                break;
            case 22: //EIPI
                $fieldLabel = 'Item Category 4';
                break;
            default:
                $fieldLabel = 'Item Model';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(852,1,'" . $fieldLabel . "','',0,'\\1102','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'model','/tableentries/tableentry/entrymodel','" . $fieldLabel . "','fa fa-list sub_menu_ico',852," . $params['levelid'] . ")";
    } //end function


    public function part($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        switch ($companyid) {
            case 14: //majesty
                $fieldLabel = 'Item Principal';
                break;
            case 22: //EIPI
                $fieldLabel = 'Item Category 5';
                break;
            default:
                $fieldLabel = 'Item Part';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(853,1,'" . $fieldLabel . "','',0,'\\1103','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'part','/tableentries/tableentry/entrypart','" . $fieldLabel . "','fa fa-list sub_menu_ico',853," . $params['levelid'] . ")";
    } //end function

    public function stockgroup($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        switch ($companyid) {
            case 14: //majesty
                $fieldLabel = 'Item Division';
                break;
            case 22: //EIPI
                $fieldLabel = 'Item Category 2';
                break;
            default:
                $fieldLabel = 'Item Group';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(857,1,'" . $fieldLabel . "','*129',0,'\\1105','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'stockgroup','/tableentries/tableentry/entrystockgroup','" . $fieldLabel . "','fa fa-list sub_menu_ico',857," . $params['levelid'] . ")";
    } //end function

    public function brand($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(856,1,'Item Brand','*129',0,'\\1101','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'brand','/tableentries/tableentry/entrybrand','Item Brand','fa fa-list sub_menu_ico',856," . $params['levelid'] . ")";
    } //end function

    public function itemclass($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(855,1,'Item Class','*129',0,'\\1104','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'itemclass','/tableentries/tableentry/entryitemclass','Item Class','fa fa-list sub_menu_ico',855," . $params['levelid'] . ")";
    } //end function

    public function loantype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(4967,1,'Loan Type','',0,'\\1108','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'loantype','/tableentries/tableentry/entryloantype','Loan Type','fa fa-list sub_menu_ico',4967," . $params['levelid'] . ")";
    } //end function

    public function clientcategories($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
            case 22: //eipi
                $qry = "(858,1,'Business Style','*129',0,'\\1106','$parent',0,'0',0," . $params['levelid'] . ")";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'categories','/tableentries/tableentry/entrycategories','Business Style','fa fa-list sub_menu_ico',858," . $params['levelid'] . ")";
                break;

            default:
                $qry = "(858,1,'Cust/Supp Categories','*129',0,'\\1106','$parent',0,'0',0," . $params['levelid'] . ")";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'categories','/tableentries/tableentry/entrycategories','Cust/Supp Categories','fa fa-list sub_menu_ico',858," . $params['levelid'] . ")";
                break;
        }
    } //end function

    public function industry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(4476,1,'Industry','*129',0,'\\1109','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'industry','/tableentries/tableentry/entryclientindustry','Industry','fa fa-list sub_menu_ico',4476," . $params['levelid'] . ")";
    } //end function


    public function project($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
                $qry = "(859,1,'Item Group','*129',0,'\\1107','$parent',0,'0',0," . $params['levelid'] . ")";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'project','/tableentries/tableentry/entryproject','Item Group','fa fa-list sub_menu_ico',859," . $params['levelid'] . ")";
                break;
            case 26: //bee healthy
                $qry = "(859,1,'Business Unit','*129',0,'\\1107','$parent',0,'0',0," . $params['levelid'] . ")";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'project','/tableentries/tableentry/entryproject','Business Unit','fa fa-list sub_menu_ico',859," . $params['levelid'] . ")";
                break;
            default:
                $qry = "(859,1,'Project','*129',0,'\\1107','$parent',0,'0',0," . $params['levelid'] . ")";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'project','/tableentries/tableentry/entryproject','Project','fa fa-list sub_menu_ico',859," . $params['levelid'] . ")";
                break;
        }
    } //end function

    public function phase($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2601,1,'Phase','*129',0,'\\112','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'phase','/tableentries/mallentry/entryphase','Phase','fa fa-list sub_menu_ico',2601," . $params['levelid'] . ")";
    } //end function

    public function mms_section($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2618,1,'Section','*129',0,'\\114','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'section','/tableentries/mallentry/entrysection','Section','fa fa-list sub_menu_ico',2618," . $params['levelid'] . ")";
    } //end function

    public function electric_rate_category($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2677,1,'Electricity Rate Category','*129',0,'\\115','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'electricratecat','/tableentries/mallentry/entryelectricratecat','Electricity Rate Category','fa fa-lightbulb sub_menu_ico',2677," . $params['levelid'] . ")";
    } //end function

    public function water_rate_category($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(295,1,'Water Rate Category','*129',0,'\\116','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'waterratecat','/tableentries/mallentry/entrywaterratecat','Water Rate Category','fa fa-water sub_menu_ico',295," . $params['levelid'] . ")";
    } //end function

    public function waterrate($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(313,1,'Water Rate','*129',0,'\\117','$parent',0,'0',0," . $params['levelid'] . "),
        (4170,1,'Allow Click Save all Entry','',0,'\\11701','\\117',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'waterrate','/headtable/mallcustomform/waterrate','Water Rate','fa fa-tint sub_menu_ico',313," . $params['levelid'] . ")";
    } //end function

    public function electricityrate($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(262,1,'Electricity Rate','*129',0,'\\118','$parent',0,'0',0," . $params['levelid'] . "),
        (4168,1,'Allow Click Save all Entry','',0,'\\11801','\\118',0,0,0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'electricityrate','/headtable/mallcustomform/electricityrate','Electricity Rate','fa fa-plug sub_menu_ico',262," . $params['levelid'] . ")";
    } //end function

    public function storage_electricityrate($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2209,1,'Storage Electricity Rate','*129',0,'\\119','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'storage_electricityrate','/headtable/mallcustomform/storage_electricityrate','Storage Electricity Rate','fa fa-plug sub_menu_ico',2209," . $params['levelid'] . ")";
    } //end function

    public function location_ledger($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(375,1,'Location Ledger','',0,'\\1120','$parent',0,0,0," . $params['levelid'] . ") ,
      (121,0,'Allow View Location Ledger','',0,'\\112001','\\1120',0,'0',0," . $params['levelid'] . ") ,
      (2065,0,'Allow Click Edit Button Location Ledger','',0,'\\112002','\\1120',0,'0',0," . $params['levelid'] . ") ,
      (2080,0,'Allow Click New Button Location Ledger','',0,'\\112003','\\1120',0,'0',0," . $params['levelid'] . ") ,
      (244,0,'Allow Click Save Button Location Ledger','',0,'\\112004','\\1120',0,'0',0," . $params['levelid'] . ") ,
      (228,0,'Allow Click Delete Button Location Ledger','',0,'\\112005','\\1120',0,'0',0," . $params['levelid'] . ") ,
      (213,0,'Allow Click Print Button Location Ledger','',0,'\\112007','\\1120',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'locationledger','/ledgergrid/masterfile/locationledger','Location Ledger','fa fa-map-pin sub_menu_ico',375," . $params['levelid'] . ")";
    } //end function

    public function tenant($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(348,0,'Tenant Ledger','',0,'\\1121','$parent',0,'0',0," . $params['levelid'] . "),
      (1745,0,'Allow View Tenant Ledger','',0,'\\112101','\\1121',0,'0',0," . $params['levelid'] . "),
      (1765,0,'Allow Click Edit Button Tenant Ledger','',0,'\\112102','\\1121',0,'0',0," . $params['levelid'] . "),
      (331,0,'Allow Click New Button Tenant Ledger','',0,'\\112103','\\1121',0,'0',0," . $params['levelid'] . "),
      (789,0,'Allow Click Save Button Tenant Ledger','',0,'\\112104','\\1121',0,'0',0," . $params['levelid'] . "),
      (1685,0,'Allow Click Change Code Tenant Ledger','',0,'\\112105','\\1121',0,'0',0," . $params['levelid'] . "),
      (886,0,'Allow Click Delete Button Tenant Ledger','',0,'\\112106','\\1121',0,'0',0," . $params['levelid'] . "),
      (902,0,'Allow Click Print Button Tenant Ledger','',0,'\\112107','\\1121',0,'0',0," . $params['levelid'] . "),
      (4213,0,'Allow View AR History','',0,'\\112108','\\1121',0,'0',0," . $params['levelid'] . "),
      (4214,0,'Allow View AP History','',0,'\\112109','\\1121',0,'0',0," . $params['levelid'] . "),
      (4215,0,'Allow View PDC History','',0,'\\112110','\\1121',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tenant','/ledger/masterfile/tenant','Tenant','fa fa-house-user sub_menu_ico',348," . $params['levelid'] . ")";
    } //end function
    public function lp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1781,0,'Lease Provision','',0,'\\1122','$parent',0,'0',0," . $params['levelid'] . "),
      (2260,0,'Allow View Lease Provision','',0,'\\112201','\\1122',0,'0',0," . $params['levelid'] . "),
      (2278,0,'Allow Click Edit Button Lease Provision','',0,'\\112202','\\1122',0,'0',0," . $params['levelid'] . "),
      (1801,0,'Allow Click New Button Lease Provision','',0,'\\112203','\\1122',0,'0',0," . $params['levelid'] . "),
      (623,0,'Allow Click Save Button Lease Provision','',0,'\\112204','\\1122',0,'0',0," . $params['levelid'] . "),
      (2432,0,'Allow Click Change Code Lease Provision','',0,'\\112205','\\1122',0,'0',0," . $params['levelid'] . "),
      (1816,0,'Allow Click Delete Button Lease Provision','',0,'\\112206','\\1122',0,'0',0," . $params['levelid'] . "),
      (1831,0,'Allow Click Print Button Lease Provision','',0,'\\112207','\\1122',0,'0',0," . $params['levelid'] . "),
      (4051,0,'Allow Click Approve Button Lease Provision','',0,'\\112208','\\1122',0,'0',0," . $params['levelid'] . "),
      (4052,0,'Allow Click Post Button Lease Provision','',0,'\\112209','\\1122',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'LP','/module/operation/lp','Lease Provision','fa fa-street-view sub_menu_ico',1781," . $params['levelid'] . ")";
    } //end function

    public function waterreading($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2556,1,'Water Reading','*129',0,'\\1208','$parent',0,'0',0," . $params['levelid'] . "),
        (2568,1,'Allow Click Save all Entry','',0,'\\120801','\\1208',0,0,0," . $params['levelid'] . "),
        (4202,1,'Allow Edit Previous Reading','',0,'\\120802','\\1208',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'waterreading','/headtable/mallcustomform/waterreading','Water Reading','fa fa-faucet sub_menu_ico',2556," . $params['levelid'] . ")";
    } //end function

    public function electricityreading($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2869,1,'Electricity Reading','*129',0,'\\1220','$parent',0,'0',0," . $params['levelid'] . "),
        (2870,1,'Allow Click Save all Entry','',0,'\\122001','\\1220',0,0,0," . $params['levelid'] . "),
        (4207,1,'Allow Edit Previous Reading','',0,'\\122002','\\1220',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'electricityreading','/headtable/mallcustomform/electricityreading','Electricity Reading','fa fa-bolt sub_menu_ico',2869," . $params['levelid'] . ")";
    } //end function

    public function gb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(4204,1,'Generate Billing','GB',0,'\\1221','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GB','/headtable/mall/gb','Generate Billing','fa fa-receipt sub_menu_ico',4204," . $params['levelid'] . ")";
    } //end function

    public function mb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4206,0,'Billing Entry','',0,'\\1222','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4207,0,'Allow View Transaction MB','MB',0,'\\122201','\\1222',0,'0',0," . $params['levelid'] . ") ,
        (4208,0,'Allow Click New Button MB','',0,'\\122203','\\1222',0,'0',0," . $params['levelid'] . ") ,
        (4209,0,'Allow Click Delete Button MB','',0,'\\122206','\\1222',0,'0',0," . $params['levelid'] . ") ,
        (4210,0,'Allow Click Print Button MB','',0,'\\122207','\\1222',0,'0',0," . $params['levelid'] . ") ,
        (4211,0,'Allow Click Post Button MB','',0,'\\122210','\\1222',0,'0',0," . $params['levelid'] . ") ,
        (4212,0,'Allow Click UnPost Button MB','',0,'\\122211','\\1222',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'MB','/module/mall/mb','Billing Entry','fa fa-book sub_menu_ico',4206," . $params['levelid'] . ")";
    } //end function

    public function compatible($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1885,1,'Compatible Setup','*129',0,'\\1108','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'compatible','/tableentries/tableentry/entrycompatible','Compatible Setup','fa fa-list sub_menu_ico',1885," . $params['levelid'] . ")";
    } //end function



    public function parentissuance($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1052,0,'ISSUANCE','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'ISSUANCE',$sort,'fa fa-dolly',',TR,'," . $params['levelid'] . ")";
    } //end function

    public function tr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(784,0,'Stock Request','',0,'\\1501','$parent',0,'0',0," . $params['levelid'] . ") ,
        (785,0,'Allow View Transaction S. Request','TR',0,'\\150101','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (786,0,'Allow Click Edit Button  S. Request','',0,'\\150102','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (787,0,'Allow Click New Button S. Request','',0,'\\150103','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (788,0,'Allow Click Save Button S. Request','',0,'\\150104','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (790,0,'Allow Click Delete Button S. Request','',0,'\\150106','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (791,0,'Allow Click Print Button S. Request','',0,'\\150107','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (792,0,'Allow Click Lock Button S. Request','',0,'\\150108','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (793,0,'Allow Click UnLock Button S. Request','',0,'\\150109','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (794,0,'Allow Click Post Button S. Request','',0,'\\150110','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (795,0,'Allow Click UnPost Button S. Request','',0,'\\150111','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (839,1,'Allow Click Add Item S. Request','',0,'\\150112','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (840,1,'Allow Click Edit Item S. Request','',0,'\\150113','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (841,1,'Allow Click Delete Item S. Request','',0,'\\150114','\\1501',0,'0',0," . $params['levelid'] . ") ,
        (842,1,'Allow Change Amount S. Request','',0,'\\150115','\\1501',0,'0',0," . $params['levelid'] . "),
        (3589,1,'Allow Click Disapproved','',0,'\\150116','\\1501',0,'0',0," . $params['levelid'] . ")";

        if ($params['companyid'] == 40) { //CDO
            $qry .= ",(4454,1,'Allow View all Branch TR','',0,'\\150117','\\1501',0,'0',0," . $params['levelid'] . ")";
        }

        $this->insertattribute($params, $qry);
        $systemtype = $this->companysetup->getsystemtype($params);
        $folder = 'issuance';
        switch ($systemtype) {
            case 'MANUFACTURING':
                $folder = 'production';
                break;
        }

        if ($params['companyid'] == 40) { //cdo
            $folder = 'cdo';
        }
        return "($sort,$p,'TR','/module/" . $folder . "/tr','Stock Request','fa fa-list sub_menu_ico',784," . $params['levelid'] . ")";
    } //end function

    public function trapproval($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1680,0,'Stock Request Approval','',0,'\\1504','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1681,0,'Allow View Transaction S. Request Approval','SS',0,'\\150401','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1682,0,'Allow Click Edit Button  S. Request Approval','',0,'\\150402','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1683,0,'Allow Click New Button S. Request Approval','',0,'\\150403','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1684,0,'Allow Click Save Button S. Request Approval','',0,'\\150404','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1686,0,'Allow Click Delete Button S. Request Approval','',0,'\\150406','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1687,0,'Allow Click Print Button S. Request Approval','',0,'\\150407','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1688,0,'Allow Click Lock Button S. Request Approval','',0,'\\150408','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1689,0,'Allow Click UnLock Button S. Request Approval','',0,'\\150409','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1690,0,'Allow Click Post Button S. Request Approval','',0,'\\150410','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1691,0,'Allow Click UnPost Button S. Request Approval','',0,'\\150411','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1692,1,'Allow Click Add Item S. Request Approval','',0,'\\150412','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1693,1,'Allow Click Delete Item S. Request Approval','',0,'\\150414','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1694,1,'Allow Change Amount S. Request Approval','',0,'\\150415','\\1504',0,'0',0," . $params['levelid'] . ") ,
        (1695,1,'Allow Click Edit Item S. Request Approval','',0,'\\150413','\\1504',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        $systemtype = $this->companysetup->getsystemtype($params);
        $folder = 'issuance';
        switch ($systemtype) {
            case 'MANUFACTURING':
                $folder = 'production';
                break;
        }
        return "($sort,$p,'TRAPPROVAL','/module/" . $folder . "/trapproval','Stock Request Approval','fa fa-check sub_menu_ico',1680," . $params['levelid'] . ")";
    } //end function


    public function st($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $folder = 'issuance';
        $qry = "(881,0,'Stock Transfer','',0,'\\1502','$parent',0,'0',0," . $params['levelid'] . ") ,
        (882,0,'Allow View Transaction S. Transfer','ST',0,'\\150201','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (883,0,'Allow Click Edit Button  S. Transfer','',0,'\\150202','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (884,0,'Allow Click New Button S. Transfer','',0,'\\150203','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (885,0,'Allow Click Save Button S. Transfer','',0,'\\150204','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (887,0,'Allow Click Delete Button S. Transfer','',0,'\\150206','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (888,0,'Allow Click Print Button S. Transfer','',0,'\\150207','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (889,0,'Allow Click Lock Button S. Transfer','',0,'\\150208','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (890,0,'Allow Click UnLock Button S. Transfer','',0,'\\150209','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (891,0,'Allow Click Post Button S. Transfer','',0,'\\150210','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (892,0,'Allow Click UnPost Button S. Transfer','',0,'\\150211','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (893,1,'Allow Click Add Item S. Transfer','',0,'\\150212','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (896,1,'Allow Click Edit Item S. Transfer','',0,'\\150213','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (894,1,'Allow Click Delete Item S. Transfer','',0,'\\150214','\\1502',0,'0',0," . $params['levelid'] . ") ,
        (895,1,'Allow Change Amount S. Transfer','',0,'\\150215','\\1502',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        switch ($params['companyid']) {
            case 39: //cbbsi
                $folder = 'cbbsi';
                break;
            case 40: //cdo
                $folder = 'cdo';
                break;
        }

        return "($sort,$p,'ST','/module/" . $folder . "/st','Stock Transfer','fa fa-dolly-flatbed sub_menu_ico',881," . $params['levelid'] . ")";
    } //end function

    public function ss($params, $parent, $sort)
    {
        $systemtype = $this->companysetup->getsystemtype($params);

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(897,0,'Stock Issuance','',0,'\\1503','$parent',0,'0',0," . $params['levelid'] . ") ,
        (898,0,'Allow View Transaction S. Issuance','SS',0,'\\150301','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (899,0,'Allow Click Edit Button  S. Issuance','',0,'\\150302','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (900,0,'Allow Click New Button S. Issuance','',0,'\\150303','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (901,0,'Allow Click Save Button S. Issuance','',0,'\\150304','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (903,0,'Allow Click Delete Button S. Issuance','',0,'\\150306','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (904,0,'Allow Click Print Button S. Issuance','',0,'\\150307','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (905,0,'Allow Click Lock Button S. Issuance','',0,'\\150308','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (906,0,'Allow Click UnLock Button S. Issuance','',0,'\\150309','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (907,0,'Allow Click Post Button S. Issuance','',0,'\\150310','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (908,0,'Allow Click UnPost Button S. Issuance','',0,'\\150311','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (909,1,'Allow Click Add Item S. Issuance','',0,'\\150312','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (910,1,'Allow Click Delete Item S. Issuance','',0,'\\150314','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (911,1,'Allow Change Amount S. Issuance','',0,'\\150315','\\1503',0,'0',0," . $params['levelid'] . ") ,
        (912,1,'Allow Click Edit Item S. Issuance','',0,'\\150316','\\1503',0,'0',0," . $params['levelid'] . ")";

        if ($systemtype == "ATI") {
            $qry .= ", (4174,1,'Allow View All WH','',0,'\\150317','\\1503',0,'0',0," . $params['levelid'] . ")";
            $qry .= ", (4386,1,'Override Restrictions','',0,'\\150318','\\1503',0,'0',0," . $params['levelid'] . ")";
        }

        $this->insertattribute($params, $qry);

        $folder = 'issuance';
        switch ($systemtype) { // companyid = 16
            case 'ATI':
                $folder = 'ati';
                break;
        }
        return "($sort,$p,'SS','/module/" . $folder . "/ss','Stock Issuance','fa fa-people-carry sub_menu_ico',897," . $params['levelid'] . ")";
    } //end function

    public function parentschoolsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1055,0,'SCHOOL SETUP','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'SCHOOL SETUP',$sort,'cast_for_education',',schoolyear,'," . $params['levelid'] . ")";
    } //end function

    public function levels($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(914,0,'Levels','',0,'\\1202','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'LEVELS','/tableentries/enrollmententry/en_levels','Levels','fa fa-layer-group sub_menu_ico',914," . $params['levelid'] . ")";
    } //end function

    public function semester($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(915,0,'Semester','',0,'\\1203','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SEMESTER','/tableentries/enrollmententry/en_semester','Semester','fa fa-calendar-week sub_menu_ico',915," . $params['levelid'] . ")";
    } //end function


    public function roomlist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(933,0,'Room List','',0,'\\1216','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1452,0,'Allow Edit Room List','',0,'\\121601','\\1216',0,'0',0," . $params['levelid'] . ") ,
        (1453,0,'Allow View Room List','',0,'\\121602','\\1216',0,'0',0," . $params['levelid'] . ") ,
        (1454,0,'Allow New Room List','',0,'\\121603','\\1216',0,'0',0," . $params['levelid'] . ") ,
        (1455,0,'Allow Save Room List','',0,'\\121604','\\1216',0,'0',0," . $params['levelid'] . ") ,
        (1316,0,'Allow Change Student List','',0,'\\121607','\\1216',0,'0',0," . $params['levelid'] . ") ,
        (1456,0,'Allow Delete Room List','',0,'\\121605','\\1216',0,'0',0," . $params['levelid'] . ") ,
        (1457,0,'Allow Print Room List','',0,'\\121606','\\1216',0,'0',0," . $params['levelid'] . ") ,
        (1327,0,'Allow Click Add Item Room List','',0,'\\121608','\\1216',0,'0',0," . $params['levelid'] . ") ,
        (1328,0,'Allow Click Edit Item Room List','',0,'\\121609','\\1216',0,'0',0," . $params['levelid'] . ") ,
        (1329,0,'Allow Click Delete Item Room List','',0,'\\121610','\\1216',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ROOMLIST','/ledgergrid/enrollmententry/en_roomlist','Room List','fa fa-chalkboard-teacher sub_menu_ico',933," . $params['levelid'] . ")";
    } //end function

    public function subject($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(920,0,'Subject List','',0,'\\1209','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1308,0,'Allow Edit Subject List','',0,'\\120901','\\1209',0,'0',0," . $params['levelid'] . ") ,
        (1309,0,'Allow View Subject List','',0,'\\120902','\\1209',0,'0',0," . $params['levelid'] . ") ,
        (1310,0,'Allow New Subject List','',0,'\\120903','\\1209',0,'0',0," . $params['levelid'] . ") ,
        (1311,0,'Allow Save Subject List','',0,'\\120904','\\1209',0,'0',0," . $params['levelid'] . ") ,
        (1317,0,'Allow Change Subject List','',0,'\\120907','\\1209',0,'0',0," . $params['levelid'] . ") ,
        (1312,0,'Allow Delete Subject List','',0,'\\120905','\\1209',0,'0',0," . $params['levelid'] . ") ,
        (1313,0,'Allow Print Subject List','',0,'\\120906','\\1209',0,'0',0," . $params['levelid'] . ") ,
        (1324,0,'Allow Click Add Item Subject List','',0,'\\120910','\\1209',0,'0',0," . $params['levelid'] . ") ,
        (1325,0,'Allow Click Edit Item Subject List','',0,'\\120908','\\1209',0,'0',0," . $params['levelid'] . ") ,
        (1326,0,'Allow Click Delete Item Subject List','',0,'\\120909','\\1209',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SUBJECT','/ledgergrid/enrollmententry/en_subject','Subject List','fa fa-book sub_menu_ico',920," . $params['levelid'] . ")";
    } //end function

    public function student($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(922,0,'Student List','',0,'\\1211','$parent',0,'0',0," . $params['levelid'] . ") ,
        (923,0,'Allow Edit Student List','',0,'\\121101','\\1211',0,'0',0," . $params['levelid'] . ") ,
        (924,0,'Allow View Student List','',0,'\\121102','\\1211',0,'0',0," . $params['levelid'] . ") ,
        (925,0,'Allow New Student List','',0,'\\121103','\\1211',0,'0',0," . $params['levelid'] . ") ,
        (926,0,'Allow Save Student List','',0,'\\121104','\\1211',0,'0',0," . $params['levelid'] . ") ,
        (1315,0,'Allow Change Student List','',0,'\\121107','\\1211',0,'0',0," . $params['levelid'] . ") ,
        (927,0,'Allow Delete Student List','',0,'\\121105','\\1211',0,'0',0," . $params['levelid'] . ") ,
        (928,0,'Allow Print Student List','',0,'\\121106','\\1211',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'student','/ledgergrid/enrollmententry/en_student','Student List','fa fa-user sub_menu_ico',922," . $params['levelid'] . ")";
    } //end function

    public function new_student_requirement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(929,0,'New Student Requirements','',0,'\\1212','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'NEW_STUDENT_REQUIREMENTS','/tableentries/enrollmententry/en_new_student_requirements','New Student Requirements','fa fa-copy sub_menu_ico',929," . $params['levelid'] . ")";
    } //end function

    public function transfer_requirement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(930,0,'Transferee Requirements','',0,'\\1213','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'TRANSFEREE_REQUIREMENTS','/tableentries/enrollmententry/en_transferee_requirements','Transferee Requirements','fa fa-sticky-note sub_menu_ico',930," . $params['levelid'] . ")";
    } //end function


    public function instructor($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(917,0,'Instructor List','',0,'\\1206','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1727,0,'Allow View Instructor List','',0,'\\120602','\\1206',0,'0',0," . $params['levelid'] . ") ,
        (1721,0,'Allow Edit Instructor List','',0,'\\120601','\\1206',0,'0',0," . $params['levelid'] . ") ,
        (1722,0,'Allow New Instructor List','',0,'\\120603','\\1206',0,'0',0," . $params['levelid'] . ") ,
        (1723,0,'Allow Save Instructor List','',0,'\\120604','\\1206',0,'0',0," . $params['levelid'] . ") ,
        (1724,0,'Allow Change Instructor List','',0,'\\120607','\\1206',0,'0',0," . $params['levelid'] . ") ,
        (1725,0,'Allow Delete Instructor List','',0,'\\120605','\\1206',0,'0',0," . $params['levelid'] . ") ,
        (1726,0,'Allow Print Instructor List','',0,'\\120606','\\1206',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'INSTRUCTOR','/ledgergrid/enrollmententry/en_instructor','Instructor List','fa fa-chalkboard-teacher sub_menu_ico',917," . $params['levelid'] . ")";
    } //end function

    public function schoolyear($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(913,0,'School Year','',0,'\\1201','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SCHOOLYEAR','/tableentries/enrollmententry/en_schoolyear','School Year','far fa-calendar-check sub_menu_ico',913," . $params['levelid'] . ")";
    } //end function

    public function cardremarks($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2519, 0, 'Card Remarks', '', 0, '\\1218', '$parent', 0, '0', 0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'CARD REMARKS', '/tableentries/enrollmententry/en_cardremarks', 'Card Remarks', 'fa fa-sticky-note sub_menu_ico', 2519," . $params['levelid'] . ")";
    }

    public function attendancesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2730, 0, 'Attendance Setup', '', 0, '\\1219', '$parent', 0, '0', 0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'Attendance Setup', '/tableentries/enrollmententry/en_attendancesetup', 'Attendance Setup', 'fa fa-sticky-note sub_menu_ico', 2730," . $params['levelid'] . ")";
    }

    public function scheme($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(931,0,'Scheme','',0,'\\1214','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SCHEME','/tableentries/enrollmententry/en_scheme','Scheme','fa fa-sticky-note sub_menu_ico',931," . $params['levelid'] . ")";
    } //end function

    public function period($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(916,0,'Period','',0,'\\1204','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PERIOD','/tableentries/enrollmententry/en_period','Period','fas fa-user-clock sub_menu_ico',916," . $params['levelid'] . ")";
    } //end function

    public function course($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(918,0,'Course List','',0,'\\1207','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1458,0,'Allow Edit Course List','',0,'\\120701','\\1207',0,'0',0," . $params['levelid'] . ") ,
        (1459,0,'Allow View Course List','',0,'\\120702','\\1207',0,'0',0," . $params['levelid'] . ") ,
        (1460,0,'Allow New Course List','',0,'\\120703','\\1207',0,'0',0," . $params['levelid'] . ") ,
        (1461,0,'Allow Save Course List','',0,'\\120704','\\1207',0,'0',0," . $params['levelid'] . ") ,
        (1314,0,'Allow Change Course List','',0,'\\120707','\\1207',0,'0',0," . $params['levelid'] . ") ,
        (1462,0,'Allow Delete Course List','',0,'\\120705','\\1207',0,'0',0," . $params['levelid'] . ") ,
        (1463,0,'Allow Print Course List','',0,'\\120706','\\1207',0,'0',0," . $params['levelid'] . ") ,
        (1330,0,'Allow Click Add Item Course List','',0,'\\120708','\\1207',0,'0',0," . $params['levelid'] . ") ,
        (1331,0,'Allow Click Edit Item Course List','',0,'\\120709','\\1207',0,'0',0," . $params['levelid'] . ") ,
        (1332,0,'Allow Click Delete Item Course List','',0,'\\120710','\\1207',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'COURSE','/ledgergrid/enrollmententry/en_course','Course List','fas fa-scroll sub_menu_ico',918," . $params['levelid'] . ")";
    } //end function


    public function fees($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(934,0,'Fees','',0,'\\1217','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'FEES','/tableentries/enrollmententry/en_fees','Fees','fas fa-asterisk sub_menu_ico',934," . $params['levelid'] . ")";
    } //end function

    public function credentials($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(921,0,'Student Credentials','',0,'\\1210','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CREDENTIALS','/tableentries/enrollmententry/en_credentials','Credential List','fas fa-asterisk sub_menu_ico',921," . $params['levelid'] . ")";
    } //end function

    public function mode_of_payment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(932,0,'Mode of Payment','',0,'\\1215','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'MODE_OF_PAYMENT','/tableentries/enrollmententry/en_modeofpayment','Mode Of Payment','fas fa-asterisk sub_menu_ico',932," . $params['levelid'] . ")";
    } //end function

    public function grade_component($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(935,0,'Grade Component','',0,'\\1301','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GRADE COMPONENT','/tableentries/enrollmentgradeentry/en_gradecomponent','Grade Component','fa fa-sticky-note sub_menu_ico',935," . $params['levelid'] . ")";
    } //end function

    public function grade_equivalent($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(936,0,'Grade Equivalent','',0,'\\1302','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GRADE EQUIVALENT','/tableentries/enrollmentgradeentry/en_gradeequivalent','Grade Equivalent','fa fa-sticky-note sub_menu_ico',936," . $params['levelid'] . ")";
    } //end function

    public function grade_equivalentletters($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2726, 0, 'Grade Equivalent Letters', '', 0, '\\1307', '$parent', 0, '0', 0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'GRADE EQUIVALENT LETTERS', '/tableentries/enrollmentgradeentry/en_gradeequivalentletters', 'Grade Equivalent Letters', 'fa fa-sticky-note sub_menu_ico', 2726," . $params['levelid'] . ")";
    }


    public function grade_setup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(937,0,'Grade Setup','',0,'\\1303','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GRADE SETUP','/module/enrollment/ef','Grade Setup','fa fa-sticky-note sub_menu_ico',937," . $params['levelid'] . ")";
    } //end function

    public function quarter_setup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2506,0,'Quarter Setup','',0,'\\1304','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'QUARTER SETUP','/tableentries/enrollmentgradeentry/en_quartersetup','Quarter Setup','fa fa-sticky-note sub_menu_ico',2506," . $params['levelid'] . ")";
    } //end function

    public function honorroll_criteria($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2507,0,'Honor Roll Criteria','',0,'\\1305','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HONOR ROLL CRITERIA','/tableentries/enrollmentgradeentry/en_honorrollcriteria','Honor Roll Criteria','fa fa-sticky-note sub_menu_ico',2507," . $params['levelid'] . ")";
    } //end function

    public function conduct_grade($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2508,0,'Conduct Grade','',0,'\\1306','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CONDUCT GRADE','/tableentries/enrollmentgradeentry/en_conductgrade','Conduct Grade','fa fa-sticky-note sub_menu_ico',2508," . $params['levelid'] . ")";
    } //end function

    public function attendance_type($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(919,0,'Attendance Type','',0,'\\1205','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ATTENDANCE TYPE', '/tableentries/enrollmententry/en_attendancetype', 'Attendance Type', 'fa fa-sticky-note sub_menu_ico', 919," . $params['levelid'] . ")";
    } //end function

    public function parentschoolsystem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1057,0,'SCHOOL SYSTEM','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'SCHOOL SYSTEM',$sort,'cast_for_education',',schoolyear,'," . $params['levelid'] . ")";
    } //end function


    public function reportcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2520, 0, 'Report Card Setup', '', 0, '\\1414', '$parent', 0, '0', 0," . $params['levelid'] . "),
        (2521, 0, 'Allow Edit Report Card', '', 0, '\\141401', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2522, 0, 'Allow View Report Card', '', 0, '\\141402', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2523, 0, 'Allow New Report Card', '', 0, '\\141403', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2524, 0, 'Allow Save Report Card', '', 0, '\\141404', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2525, 0, 'Allow Delete Report Card', '', 0, '\\141405', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2526, 0, 'Allow Change Code Report Card', '', 0, '\\141406', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2527, 0, 'Allow Lock Report Card', '', 0, '\\141407', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2528, 0, 'Allow UnLock Report Card', '', 0, '\\141408', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2529, 0, 'Allow Print Report Card', '', 0, '\\141409', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2530, 0, 'Allow Click Add Item Report Card', '', 0, '\\141410', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2531, 0, 'Allow Click Edit Item Report Card', '', 0, '\\141411', '\\1414', 0, '0', 0," . $params['levelid'] . "),
        (2532, 0, 'Allow Click Delete Item Report Card', '', 0, '\\141412', '\\1414', 0, '0', 0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'reportcard', '/module/enrollment/ej', 'Report Card Setup', 'fa fa-user sub_menu_ico', 2520," . $params['levelid'] . ")";
    }

    public function ec($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(938,0,'Curriculum Setup','',0,'\\1401','$parent',0,'0',0," . $params['levelid'] . ") ,
        (939,0,'Allow Edit Curriculum Setup','',0,'\\140101','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (940,0,'Allow View Curriculum Setup','',0,'\\140102','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (941,0,'Allow New Curriculum Setup','',0,'\\140103','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (942,0,'Allow Save Curriculum Setup','',0,'\\140104','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (943,0,'Allow Delete Curriculum Setup','',0,'\\140105','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (944,0,'Allow Change Code Curriculum Setup','',0,'\\140106','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (945,0,'Allow Post Curriculum Setup','',0,'\\140107','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (946,0,'Allow UnPost Curriculum Setup','',0,'\\140108','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (947,0,'Allow Lock Curriculum Setup','',0,'\\140109','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (948,0,'Allow UnLock Curriculum Setup','',0,'\\140110','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (1318,0,'Allow Click Add Item Curriculum Setup','',0,'\\140111','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (1319,0,'Allow Click Edit Item Curriculum Setup','',0,'\\140112','\\1401',0,'0',0," . $params['levelid'] . ") ,
        (1320,0,'Allow Click Delete Item Curriculum Setup','',0,'\\140113','\\1401',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EC','/module/enrollment/ec','Curriculum Setup','fa fa-user sub_menu_ico',938," . $params['levelid'] . ")";
    } //end function

    public function assessmentsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(960,0,'Assessment Setup','',0,'\\1403','$parent',0,'0',0," . $params['levelid'] . ") ,
        (961,0,'Allow Edit Assessment Setup','',0,'\\140301','\\1403',0,'0',0," . $params['levelid'] . ") ,
        (962,0,'Allow View Assessment Setup','',0,'\\140302','\\1403',0,'0',0," . $params['levelid'] . ") ,
        (963,0,'Allow New Assessment Setup','',0,'\\140303','\\1403',0,'0',0," . $params['levelid'] . ") ,
        (964,0,'Allow Save Assessment Setup','',0,'\\140304','\\1403',0,'0',0," . $params['levelid'] . ") ,
        (965,0,'Allow Delete Assessment Setup','',0,'\\140305','\\1403',0,'0',0," . $params['levelid'] . ") ,
        (966,0,'Allow Change Code Assessment Setup','',0,'\\140306','\\1403',0,'0',0," . $params['levelid'] . ") ,
        (967,0,'Allow Post Assessment Setup','',0,'\\140307','\\1403',0,'0',0," . $params['levelid'] . ") ,
        (968,0,'Allow UnPost Assessment Setup','',0,'\\140308','\\1403',0,'0',0," . $params['levelid'] . ") ,
        (969,0,'Allow Lock Assessment Setup','',0,'\\140309','\\1403',0,'0',0," . $params['levelid'] . ") ,
        (970,0,'Allow UnLock Assessment Setup','',0,'\\140310','\\1403',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'assesmentsetup','/module/enrollment/et','Assessment Setup','fa fa-user sub_menu_ico',960," . $params['levelid'] . ")";
    } //end function


    public function schedule($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(949,0,'Schedule Setup','',0,'\\1402','$parent',0,'0',0," . $params['levelid'] . ") ,
        (950,0,'Allow Edit Schedule Setup','',0,'\\140201','\\1402',0,'0',0," . $params['levelid'] . ") ,
        (951,0,'Allow View Schedule Setup','',0,'\\140202','\\1402',0,'0',0," . $params['levelid'] . ") ,
        (952,0,'Allow New Schedule Setup','',0,'\\140203','\\1402',0,'0',0," . $params['levelid'] . ") ,
        (953,0,'Allow Save Schedule Setup','',0,'\\140204','\\1402',0,'0',0," . $params['levelid'] . ") ,
        (954,0,'Allow Delete Schedule Setup','',0,'\\140205','\\1402',0,'0',0," . $params['levelid'] . ") ,
        (955,0,'Allow Change Code Schedule Setup','',0,'\\140206','\\1402',0,'0',0," . $params['levelid'] . ") ,
        (956,0,'Allow Post Schedule Setup','',0,'\\140207','\\1402',0,'0',0," . $params['levelid'] . ") ,
        (957,0,'Allow UnPost Schedule Setup','',0,'\\140208','\\1402',0,'0',0," . $params['levelid'] . ") ,
        (958,0,'Allow Lock Schedule Setup','',0,'\\140209','\\1402',0,'0',0," . $params['levelid'] . ") ,
        (959,0,'Allow UnLock Schedule Setup','',0,'\\140210','\\1402',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SCHEDULE','/module/enrollment/es','Schedule','fa fa-user sub_menu_ico',949," . $params['levelid'] . ")";
    } //end function

    public function grade_school_assessment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry =  "(4013,0,'Grade School Assessment','',0,'\\1404','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4014,0,'Allow Edit Grade School Assessment','',0,'\\140401','\\1404',0,'0',0," . $params['levelid'] . ") ,
        (4015,0,'Allow View Grade School Assessment','',0,'\\140402','\\1404',0,'0',0," . $params['levelid'] . ") ,
        (4490,0,'Allow New Grade School Assessment','',0,'\\140403','\\1404',0,'0',0," . $params['levelid'] . ") ,
        (4016,0,'Allow Save Grade School Assessment','',0,'\\140404','\\1404',0,'0',0," . $params['levelid'] . ") ,
        (4017,0,'Allow Delete Grade School Assessment','',0,'\\140405','\\1404',0,'0',0," . $params['levelid'] . ") ,
        (4018,0,'Allow Change Code Grade School Assessment','',0,'\\140406','\\1404',0,'0',0," . $params['levelid'] . ") ,
        (4019,0,'Allow Post Grade School Assessment','',0,'\\140407','\\1404',0,'0',0," . $params['levelid'] . ") ,
        (4020,0,'Allow UnPost Grade School Assessment','',0,'\\140408','\\1404',0,'0',0," . $params['levelid'] . ") ,
        (4021,0,'Allow Lock Grade School Assessment','',0,'\\140409','\\1404',0,'0',0," . $params['levelid'] . ") ,
        (4022,0,'Allow UnLock Grade School Assessment','',0,'\\140410','\\1404',0,'0',0," . $params['levelid'] . "),
        (5086,0,'Allow Extract Curriculum','',0,'\\140411','\\1404',0,'0',0," . $params['levelid'] . "),
        (5092,0,'Allow Print Grade School Assessment','',0,'\\140412','\\1404',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'elemassesment','/module/enrollment/ei','Grades School Assessment','fa fa-user sub_menu_ico',4013," . $params['levelid'] . ")";
    } //end function

    public function college_assessment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry =  "(971,0,'College Assessment','',0,'\\1407','$parent',0,'0',0," . $params['levelid'] . ") ,
        (972,0,'Allow Edit College Assessment','',0,'\\140701','\\1407',0,'0',0," . $params['levelid'] . ") ,
        (973,0,'Allow View College Assessment','',0,'\\140702','\\1407',0,'0',0," . $params['levelid'] . ") ,
        (974,0,'Allow New College Assessment','',0,'\\140703','\\1407',0,'0',0," . $params['levelid'] . ") ,
        (975,0,'Allow Save College Assessment','',0,'\\140704','\\1407',0,'0',0," . $params['levelid'] . ") ,
        (976,0,'Allow Delete College Assessment','',0,'\\140705','\\1407',0,'0',0," . $params['levelid'] . ") ,
        (977,0,'Allow Change Code College Assessment','',0,'\\140706','\\1407',0,'0',0," . $params['levelid'] . ") ,
        (978,0,'Allow Post College Assessment','',0,'\\140707','\\1407',0,'0',0," . $params['levelid'] . ") ,
        (979,0,'Allow UnPost College Assessment','',0,'\\140708','\\1407',0,'0',0," . $params['levelid'] . ") ,
        (980,0,'Allow Lock College Assessment','',0,'\\140709','\\1407',0,'0',0," . $params['levelid'] . ") ,
        (981,0,'Allow UnLock College Assessment','',0,'\\140710','\\1407',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'assesment','/module/enrollment/ea','College Assessment','fa fa-user sub_menu_ico',971," . $params['levelid'] . ")";
    } //end function

    public function registration($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(982,0,'Student Registration','',0,'\\1405','$parent',0,'0',0," . $params['levelid'] . ") ,
        (983,0,'Allow Edit Student Registration','',0,'\\140501','\\1405',0,'0',0," . $params['levelid'] . ") ,
        (984,0,'Allow View Student Registration','',0,'\\140502','\\1405',0,'0',0," . $params['levelid'] . ") ,
        (985,0,'Allow New Student Registration','',0,'\\140503','\\1405',0,'0',0," . $params['levelid'] . ") ,
        (986,0,'Allow Save Student Registration','',0,'\\140504','\\1405',0,'0',0," . $params['levelid'] . ") ,
        (987,0,'Allow Delete Student Registration','',0,'\\140505','\\1405',0,'0',0," . $params['levelid'] . ") ,
        (988,0,'Allow Change Code Student Registration','',0,'\\140506','\\1405',0,'0',0," . $params['levelid'] . ") ,
        (989,0,'Allow Post Student Registration','',0,'\\140507','\\1405',0,'0',0," . $params['levelid'] . ") ,
        (990,0,'Allow UnPost Student Registration','',0,'\\140508','\\1405',0,'0',0," . $params['levelid'] . ") ,
        (991,0,'Allow Lock Student Registration','',0,'\\140509','\\1405',0,'0',0," . $params['levelid'] . ") ,
        (992,0,'Allow UnLock Student Registration','',0,'\\140510','\\1405',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'registration','/module/enrollment/er','Student Registration','fa fa-user sub_menu_ico',982," . $params['levelid'] . ")";
    } //end function

    public function addordrop($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(993,0,'Add / Drop','',0,'\\1406','$parent',0,'0',0," . $params['levelid'] . ") ,
        (994,0,'Allow Edit Add / Drop','',0,'\\140601','\\1406',0,'0',0," . $params['levelid'] . ") ,
        (995,0,'Allow View Add / Drop','',0,'\\140602','\\1406',0,'0',0," . $params['levelid'] . ") ,
        (996,0,'Allow New Add / Drop','',0,'\\140603','\\1406',0,'0',0," . $params['levelid'] . ") ,
        (997,0,'Allow Save Add / Drop','',0,'\\140604','\\1406',0,'0',0," . $params['levelid'] . ") ,
        (998,0,'Allow Delete Add / Drop','',0,'\\140605','\\1406',0,'0',0," . $params['levelid'] . ") ,
        (999,0,'Allow Change Code Add / Drop','',0,'\\140606','\\1406',0,'0',0," . $params['levelid'] . ") ,
        (1000,0,'Allow Post Add / Drop','',0,'\\140607','\\1406',0,'0',0," . $params['levelid'] . ") ,
        (1001,0,'Allow UnPost Add / Drop','',0,'\\140608','\\1406',0,'0',0," . $params['levelid'] . ") ,
        (1002,0,'Allow Lock Add / Drop','',0,'\\140609','\\1406',0,'0',0," . $params['levelid'] . ") ,
        (1003,0,'Allow UnLock Add / Drop','',0,'\\140610','\\1406',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'addordrop','/module/enrollment/ed','Add / Drop','fa fa-user sub_menu_ico',993," . $params['levelid'] . ")";
    } //end function


    public function gradeentry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1015,0,'Grade Entry','',0,'\\1411','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1016,0,'Allow Edit Grade Entry','',0,'\\141101','\\1411',0,'0',0," . $params['levelid'] . ") ,
        (1017,0,'Allow View Grade Entry','',0,'\\141102','\\1411',0,'0',0," . $params['levelid'] . ") ,
        (1018,0,'Allow New Grade Entry','',0,'\\141103','\\1411',0,'0',0," . $params['levelid'] . ") ,
        (1019,0,'Allow Save Grade Entry','',0,'\\141104','\\1411',0,'0',0," . $params['levelid'] . ") ,
        (1020,0,'Allow Delete Grade Entry','',0,'\\141105','\\1411',0,'0',0," . $params['levelid'] . ") ,
        (1021,0,'Allow Change Code Grade Entry','',0,'\\141106','\\1411',0,'0',0," . $params['levelid'] . ") ,
        (1022,0,'Allow Post Grade Entry','',0,'\\141107','\\1411',0,'0',0," . $params['levelid'] . ") ,
        (1023,0,'Allow UnPost Grade Entry','',0,'\\141108','\\1411',0,'0',0," . $params['levelid'] . ") ,
        (1024,0,'Allow Lock Grade Entry','',0,'\\141109','\\1411',0,'0',0," . $params['levelid'] . ") ,
        (1025,0,'Allow UnLock Grade Entry','',0,'\\141110','\\1411',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'gradeentry','/module/enrollment/eh','Grade Entry','fa fa-user sub_menu_ico',1015," . $params['levelid'] . ")";
    } //end function

    public function studentgradeentry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1026,0,'Student Grade Entry','',0,'\\1412','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1027,0,'Allow Edit Student Grade Entry','',0,'\\141201','\\1412',0,'0',0," . $params['levelid'] . ") ,
        (1028,0,'Allow View Student Grade Entry','',0,'\\141202','\\1412',0,'0',0," . $params['levelid'] . ") ,
        (1029,0,'Allow New Student Grade Entry','',0,'\\141203','\\1412',0,'0',0," . $params['levelid'] . ") ,
        (1030,0,'Allow Save Student Grade Entry','',0,'\\141204','\\1412',0,'0',0," . $params['levelid'] . ") ,
        (1031,0,'Allow Delete Student Grade Entry','',0,'\\141205','\\1412',0,'0',0," . $params['levelid'] . ") ,
        (1032,0,'Allow Change Code Student Grade Entry','',0,'\\141206','\\1412',0,'0',0," . $params['levelid'] . ") ,
        (1033,0,'Allow Post Student Grade Entry','',0,'\\141207','\\1412',0,'0',0," . $params['levelid'] . ") ,
        (1034,0,'Allow UnPost Student Grade Entry','',0,'\\141208','\\1412',0,'0',0," . $params['levelid'] . ") ,
        (1035,0,'Allow Lock Student Grade Entry','',0,'\\141209','\\1412',0,'0',0," . $params['levelid'] . ") ,
        (1036,0,'Allow UnLock Student Grade Entry','',0,'\\141210','\\1412',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'studentgradeentry','/module/enrollment/eg','Student Grade Entry','fa fa-user sub_menu_ico',1026," . $params['levelid'] . ")";
    } //end function

    public function attendanceentry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1037,0,'Attendance Entry','',0,'\\1413','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1038,0,'Allow Edit Attendance Entry','',0,'\\141301','\\1413',0,'0',0," . $params['levelid'] . ") ,
        (1039,0,'Allow View Attendance Entry','',0,'\\141302','\\1413',0,'0',0," . $params['levelid'] . ") ,
        (1040,0,'Allow New Attendance Entry','',0,'\\141303','\\1413',0,'0',0," . $params['levelid'] . ") ,
        (1041,0,'Allow Save Attendance Entry','',0,'\\141304','\\1413',0,'0',0," . $params['levelid'] . ") ,
        (1042,0,'Allow Delete Attendance Entry','',0,'\\141305','\\1413',0,'0',0," . $params['levelid'] . ") ,
        (1043,0,'Allow Change Code Attendance Entry','',0,'\\141306','\\1413',0,'0',0," . $params['levelid'] . ") ,
        (1044,0,'Allow Post Attendance Entry','',0,'\\141307','\\1413',0,'0',0," . $params['levelid'] . ") ,
        (1045,0,'Allow UnPost Attendance Entry','',0,'\\141308','\\1413',0,'0',0," . $params['levelid'] . ") ,
        (1046,0,'Allow Lock Attendance Entry','',0,'\\141309','\\1413',0,'0',0," . $params['levelid'] . ") ,
        (1047,0,'Allow UnLock Attendance Entry','',0,'\\141310','\\1413',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'attendanceentry','/module/enrollment/en','Attendance Entry','fa fa-user sub_menu_ico',1037," . $params['levelid'] . ")";
    } //end function

    public function en_levelup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2446,0,'Student Level Up','',0,'\\1414','$parent',0,'0',0," . $params['levelid'] . "),
        (2447,0,'Allow View Student Level Up','',0,'\\141401','\\1414',0,'0',0," . $params['levelid'] . "),
        (2448,0,'Allow Print Student Level Up','',0,'\\141402','\\1414',0,'0',0," . $params['levelid'] . "),
        (2449,0,'Allow Approved Student Level Up','',0,'\\141403','\\1414',0,'0',0," . $params['levelid'] . "),
        (2450,0,'Allow Disapproved Student Level Up','',0,'\\141404','\\1414',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'STUDENT LEVEL UP','/module/enrollment/en_levelup','Student Level Up','fa fa-sticky-note sub_menu_ico',2446," . $params['levelid'] . ")";
    } //end function

    public function parentroommanagement($params, $parent, $sort)
    {
        $p = $parent;
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'ROOM MANAGEMENT',$sort,'fa fa-hotel',',roommanagement,'," . $params['levelid'] . ")";
    } //end function

    public function hmsratecode($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'ratecode','/tableentries/hmsentry/ratecodesetup','Rate Code Setup','fa fa-coins sub_menu_ico',4589," . $params['levelid'] . ")";
    } //end function


    public function hmsroomtype($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'roomtype','/ledgergrid/hmsentry/roomtype','Room Type Setup','fas fa-door-open sub_menu_ico',21," . $params['levelid'] . ")";
    } //end function

    public function hmsothercharges($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'otherchanges','/tableentries/hmsentry/othercharges','Other Charges','fa fa-concierge-bell sub_menu_ico',4588," . $params['levelid'] . ")";
    } //end function

    public function hmspackagesetup($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'packagesetup','/tableentries/hmsentry/packagesetup','Package Setup','fa fa-tags sub_menu_ico',4587," . $params['levelid'] . ")";
    } //end function

    public function parentfrontdesk($params, $parent, $sort)
    {
        $p = $parent;
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'FRONT DESK',$sort,'fa fa-hotel',',frontdesk,'," . $params['levelid'] . ")";
    } //end function

    public function hmsreservation($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'reservation','/ledgergrid/hms/reservation','Reservation','fa fa-coins sub_menu_ico',4586," . $params['levelid'] . ")";
    } //end function


    public function hmstempreservation($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'tempreservation','/ledgergrid/hms/arrival','Pendinmg Reservation','fa fa-coins sub_menu_ico',4585," . $params['levelid'] . ")";
    } //end function

    public function hmswalkin($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'walkin','/ledgergrid/hms/walkin','Walk-in','fa fa-coins sub_menu_ico',4584," . $params['levelid'] . ")";
    } //end function

    public function hmsroomplan($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'roomplan','/uniqueroomplan/unique/roomplan','Room Plan','fa fa-tags sub_menu_ico',168," . $params['levelid'] . ")";
    } //end function

    public function parentcustomersupport($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1075,0,'CUSTOMER SERVICE','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'CUSTOMER SERVICE',$sort,'support_agent',',customersupport,'," . $params['levelid'] . ")";
    } //end function

    public function create_ticket($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1059,0,'Create Ticket','',0,'\\1601','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1060,0,'Allow View Create Ticket','',0,'\\160101','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1061,0,'Allow Click Edit Button Create Ticket','',0,'\\160102','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1062,0,'Allow Click New Button Create Ticket','',0,'\\160103','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1063,0,'Allow Click Save Button Create Ticket','',0,'\\160104','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1064,0,'Allow Click Change Code Create Ticket','',0,'\\160105','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1065,0,'Allow Click Delete Button Create Ticket','',0,'\\160106','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1066,0,'Allow Click Print Button Create Ticket','',0,'\\160107','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1067,0,'Allow Click Lock Button Create Ticket','',0,'\\160108','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1068,0,'Allow Click UnLock Button Create Ticket','',0,'\\160109','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1069,0,'Allow Click Change Amount Create Ticket','',0,'\\160110','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1070,0,'Allow Click Post Button Create Ticket','',0,'\\160111','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1071,0,'Allow Click UnPost Button Create Ticket','',0,'\\160112','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1072,0,'Allow Click Add Item Create Ticket','',0,'\\160113','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1073,0,'Allow Click Edit Item Create Ticket','',0,'\\160114','\\1601',0,'0',0," . $params['levelid'] . ") ,
        (1074,0,'Allow Click Delete Item Create Ticket','',0,'\\160115','\\1601',0,'0',0," . $params['levelid'] . "),

        (4732,0,'Allow View Open Ticket','',0,'\\160116','\\1601',0,'0',0," . $params['levelid'] . "),
        (4733,0,'Allow View In-Progress Ticket','',0,'\\160117','\\1601',0,'0',0," . $params['levelid'] . "),
        (4734,0,'Allow View Resolved Ticket','',0,'\\160118','\\1601',0,'0',0," . $params['levelid'] . "),

        (4845,1,'View Dashboard Total Ticket Per Type','',0,'\\160119','\\1601',0,'0',0," . $params['levelid'] . "),
        (4846,1,'View Dashboard Caller Gender','',0,'\\160120','\\1601',0,'0',0," . $params['levelid'] . "),
        (4847,1,'View Dashboard Total Ticket Status','',0,'\\160121','\\1601',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'TICKET','/module/customerservice/ca','Create Ticket','fa fa-sticky-note sub_menu_ico',1059," . $params['levelid'] . ")";
    } //end function


    public function update_ticket($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1076,0,'Update Ticket','',0,'\\1602','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1077,0,'Allow View Update Ticket','',0,'\\160201','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1078,0,'Allow Click Edit Button Update Ticket','',0,'\\160202','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1079,0,'Allow Click New Button Update Ticket','',0,'\\160203','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1080,0,'Allow Click Save Button Update Ticket','',0,'\\160204','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1081,0,'Allow Click Change Code Update Ticket','',0,'\\160205','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1082,0,'Allow Click Delete Button Update Ticket','',0,'\\160206','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1083,0,'Allow Click Print Button Update Ticket','',0,'\\160207','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1084,0,'Allow Click Lock Button Update Ticket','',0,'\\160208','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1085,0,'Allow Click UnLock Button Update Ticket','',0,'\\160209','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1086,0,'Allow Click Change Amount Update Ticket','',0,'\\160210','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1087,0,'Allow Click Post Button Update Ticket','',0,'\\160211','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1088,0,'Allow Click UnPost Button Update Ticket','',0,'\\160212','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1089,0,'Allow Click Add Item Update Ticket','',0,'\\160213','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1090,0,'Allow Click Edit Item Update Ticket','',0,'\\160214','\\1602',0,'0',0," . $params['levelid'] . ") ,
        (1091,0,'Allow Click Delete Item Update Ticket','',0,'\\160215','\\1602',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'UPDATE TICKET','/module/customerservice/cb','Update Ticket','fa fa-sticky-note sub_menu_ico',1076," . $params['levelid'] . ")";
    } //end function

    public function ticket_history($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1092,0,'Ticket History','',0,'\\1603','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1093,0,'Allow View Ticket History','',0,'\\160301','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1094,0,'Allow Click Edit Button Ticket History','',0,'\\160302','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1095,0,'Allow Click New Button Ticket History','',0,'\\160303','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1096,0,'Allow Click Save Button Ticket History','',0,'\\160304','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1097,0,'Allow Click Change Code Ticket History','',0,'\\160305','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1098,0,'Allow Click Delete Button Ticket History','',0,'\\160306','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1099,0,'Allow Click Print Button Ticket History','',0,'\\160307','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1100,0,'Allow Click Lock Button Ticket History','',0,'\\160308','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1101,0,'Allow Click UnLock Button Ticket History','',0,'\\160309','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1102,0,'Allow Click Change Amount Ticket History','',0,'\\160310','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1103,0,'Allow Click Post Button Ticket History','',0,'\\160311','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1104,0,'Allow Click UnPost Button Ticket History','',0,'\\160312','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1105,0,'Allow Click Add Item Ticket History','',0,'\\160313','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1106,0,'Allow Click Edit Item Ticket History','',0,'\\160314','\\1603',0,'0',0," . $params['levelid'] . ") ,
        (1107,0,'Allow Click Delete Item Ticket History','',0,'\\160315','\\1603',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'TICKET HISTORY','/module/customerservice/cc','Ticket History','fa fa-sticky-note sub_menu_ico',1092," . $params['levelid'] . ")";
    } //end function

    public function parenthrissetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1259,0,'HRIS SETUP','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'HRIS SETUP',$sort,'fa fa-users',',compcodeconduct,empstatusmaster,statchangemaster,skillreqmaster,jobtitlemaster,empreqmaster,preemptest'," . $params['levelid'] . ")";
    } //end function


    public function parenttimekeeping($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4645,0,'TIMEKEEPING','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'TIMEKEEPING',$sort,'fa fa-clock',''," . $params['levelid'] . ")";
    } //end function

    public function code_of_conduct($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1260,1,'Company Code of Conduct','',0,'\\1801','$parent',0,0,0," . $params['levelid'] . ") ,
        (1261,0,'Allow View Company Code of Conduct','',0,'\\180101','\\1801',0,'0',0," . $params['levelid'] . ") ,
        (1262,0,'Allow Click Edit Button Company Code of Conduct','',0,'\\180102','\\1801',0,'0',0," . $params['levelid'] . ") ,
        (1263,0,'Allow Click New Button Company Code of Conduct','',0,'\\180103','\\1801',0,'0',0," . $params['levelid'] . ") ,
        (1264,0,'Allow Click Save Button Company Code of Conduct','',0,'\\180104','\\1801',0,'0',0," . $params['levelid'] . ") ,
        (1265,0,'Allow Click Delete Button Company Code of Conduct','',0,'\\180105','\\1801',0,'0',0," . $params['levelid'] . ") ,
        (1336,0,'Allow Click Change Code Company Code of Conduct','',0,'\\180106','\\1801',0,'0',0," . $params['levelid'] . ") ,
        (1337,0,'Allow Click Print Button Item Company Code of Conduct','',0,'\\180107','\\1801',0,'0',0," . $params['levelid'] . ") ,
        (1338,0,'Allow Click Add Item Company Code of Conduct','',0,'\\180108','\\1801',0,'0',0," . $params['levelid'] . ") ,
        (1339,0,'Allow Click Edit Item Company Code of Conduct','',0,'\\180109','\\1801',0,'0',0," . $params['levelid'] . ") ,
        (1340,0,'Allow Click Delete Item Company Code of Conduct','',0,'\\180110','\\1801',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CODE OF CONDUCT','/ledgergrid/hrisentry/codeconduct','Code of Conduct','fa fa-user sub_menu_ico',1260," . $params['levelid'] . ")";
    } //end function

    public function employment_status($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1280,1,'Employment Status Master Entry','',0,'\\1803','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EMPLOYMENT STATUS MASTER ENTRY','/tableentries/hrisentry/empstatusmaster','Employment Status Master Entry','fa fa-user sub_menu_ico',1280," . $params['levelid'] . ")";
    } //end function


    public function status_change($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1281,1,'Status Change Master','',0,'\\1804','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'STATUS CHANGE MASTER','/tableentries/hrisentry/statchangemaster','Status Change Master','fa fa-user sub_menu_ico',1281," . $params['levelid'] . ")";
    } //end function

    public function skill_requirement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1282,1,'Skill Requirements Master','',0,'\\1805','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SKILL REQUIREMENT MASTER','/tableentries/hrisentry/skillreqmaster','Skill Requirements Master','fa fa-user sub_menu_ico',1282," . $params['levelid'] . ")";
    } //end function

    public function job_title($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1270,1,'Job Title Master','',0,'\\1802','$parent',0,0,0," . $params['levelid'] . ") ,
        (1271,0,'Allow View Job Title Master','',0,'\\180201','\\1802',0,'0',0," . $params['levelid'] . ") ,
        (1272,0,'Allow Click Edit Button Job Title Master','',0,'\\180202','\\1802',0,'0',0," . $params['levelid'] . ") ,
        (1273,0,'Allow Click New Button Job Title Master','',0,'\\180203','\\1802',0,'0',0," . $params['levelid'] . ") ,
        (1274,0,'Allow Click Save Button Job Title Master','',0,'\\180204','\\1802',0,'0',0," . $params['levelid'] . ") ,
        (1275,0,'Allow Click Delete Button Job Title Master','',0,'\\180205','\\1802',0,'0',0," . $params['levelid'] . ") ,
        (1717,0,'Allow Change Code Button Job Title Master','',0,'\\180206','\\1802',0,'0',0," . $params['levelid'] . ") ,
        (1718,0,'Allow Click Print Button Job Title Master','',0,'\\180207','\\1802',0,'0',0," . $params['levelid'] . ") ,
        (1341,0,'Allow Click Add Item Job Title Master','',0,'\\180208','\\1802',0,'0',0," . $params['levelid'] . ") ,
        (1342,0,'Allow Click Edit Item Job Title Master','',0,'\\180209','\\1802',0,'0',0," . $params['levelid'] . ") ,
        (1343,0,'Allow Click Delete Item Job Title Master','',0,'\\180210','\\1802',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JOB TITLE MASTER','/ledgergrid/hris/jobtitlemaster','Job Title Master','fa fa-user sub_menu_ico',1270," . $params['levelid'] . ")";
    } //end function

    public function employmentrequirements($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1283,1,'Employment Requirements Master','',0,'\\1806','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EMPLOYMENT REQUIREMENTS MASTER','/tableentries/hrisentry/empreqmaster','Employment Requirements Master','fa fa-user sub_menu_ico',1283," . $params['levelid'] . ")";
    } //end function

    public function pre_employment_test($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1284,1,'Pre Employment Test','',0,'\\1807','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PRE EMPLOYMENT TEST','/tableentries/hrisentry/preemptest','Pre Employment Test','fa fa-user sub_menu_ico',1284," . $params['levelid'] . ")";
    } //end function


    public function parenthris($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1152,0,'HRIS','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'HRIS',$sort,'fa fa-fingerprint',',applicantledger,reqtrainingdev,trainentry,turnoveritems,returnitems,empstatusentrychange,incidentreport,noticeexplain,noticedeciplinary,clearance'," . $params['levelid'] . ")";
    } //end function

    public function parentrecruitment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5229,0,'RECRUITMENT','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'RECRUITMENT',$sort,'fa fa-user-plus',''," . $params['levelid'] . ")";
    } //end function

    public function parentemployment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5230,0,'EMPLOYMENT','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'EMPLOYMENT',$sort,'fa fa-address-book',''," . $params['levelid'] . ")";
    } //end function


    public function parentdiscipline($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5231,0,'DISCIPLINE','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'DISCIPLINE',$sort,'fa fa-book',''," . $params['levelid'] . ")";
    } //end function

    public function parentbenefits($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5236,0,'BENEFITS','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'BENEFITS',$sort,'fa fa-percent sub_menu_ico',''," . $params['levelid'] . ")";
    } //end function

    public function parentmonitoring($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5237,0,'MONITORING','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'MONITORING',$sort,'fa fa-list sub_menu_ico',''," . $params['levelid'] . ")";
    } //end function

    public function parenttrainingdev($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5239,0,'TRAINING & DEVELOPMENT','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'TRAINING & DEVELOPMENT',$sort,'fa fa-users sub_menu_ico',''," . $params['levelid'] . ")";
    } //end function

    public function parentmasterrecruitment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5366,0,'MASTERFILE - Recruitment','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'MASTERFILE - Recruitment',$sort,'fa fa-list sub_menu_ico',''," . $params['levelid'] . ")";
    } //end function

    public function parentmasteremployment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5367,0,'MASTERFILE - Employment','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'MASTERFILE - Employment',$sort,'fa fa-list sub_menu_ico',''," . $params['levelid'] . ")";
    } //end function

    public function parentmastertimekeeping($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5368,0,'MASTERFILE - Timekeeping','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'MASTERFILE - Timekeeping',$sort,'fa fa-list sub_menu_ico',''," . $params['levelid'] . ")";
    } //end function

    public function parentmasterpayroll($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5369,0,'MASTERFILE - Payroll','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'MASTERFILE - Payroll',$sort,'fa fa-list sub_menu_ico',''," . $params['levelid'] . ")";
    } //end function

    public function parentcontractmonitoring($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5416,0,'CONTRACT MONITORING','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'CONTRACT MONITORING',$sort,'fa fa-list sub_menu_ico',''," . $params['levelid'] . ")";
    } //end function


    public function applicant($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1108,0,'Applicant Ledger','',0,'\\1701','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1109,0,'Allow View Applicant Ledger','',0,'\\170101','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1110,0,'Allow Click Edit Button Applicant Ledger','',0,'\\170102','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1111,0,'Allow Click New Button Applicant Ledger','',0,'\\170103','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1112,0,'Allow Click Save Button Applicant Ledger','',0,'\\170104','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1113,0,'Allow Click Change Code Applicant Ledger','',0,'\\170105','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1114,0,'Allow Click Delete Button Applicant Ledger','',0,'\\170106','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1115,0,'Allow Click Print Button Applicant Ledger','',0,'\\170107','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1116,0,'Allow Click Post Button Applicant Ledger','',0,'\\170108','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1117,0,'Allow Click UnPost Button Applicant Ledger','',0,'\\170109','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1670,0,'Allow Click Lock Button Applicant Ledger','',0,'\\170110','\\1701',0,'0',0," . $params['levelid'] . ") ,
        (1671,0,'Allow Click UnLock Button Applicant Ledger','',0,'\\170111','\\1701',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 58: //cdo
                $qry .= ",(5201,0,'Allow Click Pre-Employment Exam','',0,'\\170112','\\1701',0,'0',0," . $params['levelid'] . "),
                        (5202,0,'Allow Click Background Checking','',0,'\\170113','\\1701',0,'0',0," . $params['levelid'] . "),
                        (5203,0,'Allow Click Final Interview','',0,'\\170114','\\1701',0,'0',0," . $params['levelid'] . "),
                        (5204,0,'Allow Click Hiring & Pre-Employment Requirements','',0,'\\170115','\\1701',0,'0',0," . $params['levelid'] . "),
                        (5205,0,'Allow Click For Job Offer','',0,'\\170116','\\1701',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'APPLICANT LEDGER','/ledgergrid/hris/applicantledger','Applicant Ledger','fa fa-sticky-note sub_menu_ico',1108," . $params['levelid'] . ")";
    } //end function

    public function personnel_requisition($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(1239,0,'Personnel Requisition','',0,'\\1702','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1240,0,'Allow View Personnel Requisition','',0,'\\170201','\\1702',0,'0',0," . $params['levelid'] . ") ,
        (1241,0,'Allow Click Edit Button Personnel Requisition','',0,'\\170202','\\1702',0,'0',0," . $params['levelid'] . ") ,
        (1242,0,'Allow Click New Button Personnel Requisition','',0,'\\170203','\\1702',0,'0',0," . $params['levelid'] . ") ,
        (1243,0,'Allow Click Save Button Personnel Requisition','',0,'\\170204','\\1702',0,'0',0," . $params['levelid'] . ") ,
        (1245,0,'Allow Click Delete Button Personnel Requisition','',0,'\\170206','\\1702',0,'0',0," . $params['levelid'] . ") ,
        (1246,0,'Allow Click Print Button Personnel Requisition','',0,'\\170207','\\1702',0,'0',0," . $params['levelid'] . ") ,
        (1247,0,'Allow Click Post Button Personnel Requisition','',0,'\\170208','\\1702',0,'0',0," . $params['levelid'] . ") ,
        (1248,0,'Allow Click UnPost Button Personnel Requisition','',0,'\\170209','\\1702',0,'0',0," . $params['levelid'] . ") ,
        (1711,0,'Allow Click Lock Button Personnel Requisition','',0,'\\170210','\\1702',0,'0',0," . $params['levelid'] . ") ,
        (1712,0,'Allow Click UnLock Button Personnel Requisition','',0,'\\170211','\\1702',0,'0',0," . $params['levelid'] . ")";

        if ($params['companyid'] == 58) { //cdo
            $qry .= ", (5437,0,'Allow To View All Employees','',0,'\\170212','\\1702',0,'0',0," . $params['levelid'] . ")";
            $qry .= ", (5451,0,'Allow To Add Applicants','',0,'\\170213','\\1702',0,'0',0," . $params['levelid'] . ")";
            $qry .= ", (5486,0,'Allow view per Department','',0,'\\170214','\\1702',0,'0',0," . $params['levelid'] . ")";
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'PERSONNEL REQUISITION','/module/hris/HQ','Personnel Requisition','fa fa-sticky-note sub_menu_ico',1239," . $params['levelid'] . ")";
    } //end function

    public function job_offer($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(1249,0,'Job Offer','',0,'\\1703','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1250,0,'Allow View Job Offer','',0,'\\170301','\\1703',0,'0',0," . $params['levelid'] . ") ,
        (1251,0,'Allow Click Edit Button Job Offer','',0,'\\170302','\\1703',0,'0',0," . $params['levelid'] . ") ,
        (1252,0,'Allow Click New Button Job Offer','',0,'\\170303','\\1703',0,'0',0," . $params['levelid'] . ") ,
        (1253,0,'Allow Click Save Button Job Offer','',0,'\\170304','\\1703',0,'0',0," . $params['levelid'] . ") ,
        (1255,0,'Allow Click Delete Button Job Offer','',0,'\\170306','\\1703',0,'0',0," . $params['levelid'] . ") ,
        (1256,0,'Allow Click Print Button Job Offer','',0,'\\170307','\\1703',0,'0',0," . $params['levelid'] . ") ,
        (1257,0,'Allow Click Post Button Job Offer','',0,'\\170308','\\1703',0,'0',0," . $params['levelid'] . ") ,
        (1713,0,'Allow Click Lock Button Job Offer','',0,'\\170310','\\1703',0,'0',0," . $params['levelid'] . ") ,
        (1714,0,'Allow Click UnLock Button Job Offer','',0,'\\170311','\\1703',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JOB OFFER','/module/hris/HJ','Job Offer','fa fa-sticky-note sub_menu_ico',1249," . $params['levelid'] . ")";
    } //end function

    public function ha($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1128,0,'Request For Training And Development','',0,'\\1705','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1129,0,'Allow View Request For Training And Development','',0,'\\170501','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1130,0,'Allow Click Edit Button Request For Training And Development','',0,'\\170502','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1131,0,'Allow Click New Button Request For Training And Development','',0,'\\170503','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1132,0,'Allow Click Save Button Request For Training And Development','',0,'\\170504','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1133,0,'Allow Click Change Code Request For Training And Development','',0,'\\170505','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1134,0,'Allow Click Delete Button Request For Training And Development','',0,'\\170506','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1135,0,'Allow Click Print Button Request For Training And Development','',0,'\\170507','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1136,0,'Allow Click Post Button Request For Training And Development','',0,'\\170508','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1137,0,'Allow Click UnPost Button Request For Training And Development','',0,'\\170509','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1674,0,'Allow Click Lock Button Request For Training And Development','',0,'\\170510','\\1705',0,'0',0," . $params['levelid'] . ") ,
        (1675,0,'Allow Click UnLock Button Request For Training And Development','',0,'\\170511','\\1705',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HA','/module/hris/HA','Request Training and Development','fa fa-sticky-note sub_menu_ico',1128," . $params['levelid'] . ")";
    } //end function

    public function ht($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1138,0,'Training Entry','',0,'\\1706','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1139,0,'Allow View Training Entry','',0,'\\170601','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1140,0,'Allow Click Edit Button Training Entry','',0,'\\170602','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1141,0,'Allow Click New Button Training Entry','',0,'\\170603','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1142,0,'Allow Click Save Button Training Entry','',0,'\\170604','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1143,0,'Allow Click Change Code Training Entry','',0,'\\170605','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1144,0,'Allow Click Delete Button Training Entry','',0,'\\170606','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1145,0,'Allow Click Print Button Training Entry','',0,'\\170607','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1146,0,'Allow Click Post Button Training Entry','',0,'\\170608','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1147,0,'Allow Click UnPost Button Training Entry','',0,'\\170609','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1676,0,'Allow Click Lock Button Training Entry','',0,'\\170610','\\1706',0,'0',0," . $params['levelid'] . ") ,
        (1677,0,'Allow Click UnLock Button Training Entry','',0,'\\170611','\\1706',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HT','/module/hris/HT','Training Entry','fa fa-sticky-note sub_menu_ico',1138," . $params['levelid'] . ")";
    } //end function


    public function ho($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1118,0,'Turn Over Of Items','',0,'\\1704','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1119,0,'Allow View Turn Over Of Items','',0,'\\170401','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1120,0,'Allow Click Edit Button Turn Over Of Items','',0,'\\170402','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1121,0,'Allow Click New Button Turn Over Of Items','',0,'\\170403','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1122,0,'Allow Click Save Button Turn Over Of Items','',0,'\\170404','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1123,0,'Allow Click Change Code Turn Over Of Items','',0,'\\170405','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1124,0,'Allow Click Delete Button Turn Over Of Items','',0,'\\170406','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1125,0,'Allow Click Print Button Turn Over Of Items','',0,'\\170407','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1126,0,'Allow Click Post Button Turn Over Of Items','',0,'\\170408','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1127,0,'Allow Click UnPost Button Turn Over Of Items','',0,'\\170409','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1672,0,'Allow Click Lock Button Turn Over Of Items','',0,'\\170410','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1673,0,'Allow Click UnLock Button Turn Over Of Items','',0,'\\170411','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1321,0,'Allow Click Add Item Turn Over Of Items','',0,'\\170412','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1322,0,'Allow Click Edit Item Turn Over Of Items','',0,'\\170413','\\1704',0,'0',0," . $params['levelid'] . ") ,
        (1323,0,'Allow Click Delete Item Turn Over Of Items','',0,'\\170414','\\1704',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HO','/module/hris/HO','Turn Over of Items','fa fa-sticky-note sub_menu_ico',1118," . $params['levelid'] . ")";
    } //end function

    public function hr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1158,0,'Return Of Items','',0,'\\1707','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1159,0,'Allow View Return Of Items','',0,'\\170701','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1160,0,'Allow Click Edit Button Return Of Items','',0,'\\170702','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1161,0,'Allow Click New Button Return Of Items','',0,'\\170703','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1162,0,'Allow Click Save Button Return Of Items','',0,'\\170704','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1163,0,'Allow Click Change Code Return Of Items','',0,'\\170705','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1164,0,'Allow Click Delete Button Return Of Items','',0,'\\170706','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1165,0,'Allow Click Print Button Return Of Items','',0,'\\170707','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1166,0,'Allow Click Post Button Return Of Items','',0,'\\170708','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1167,0,'Allow Click UnPost Button Return Of Items','',0,'\\170709','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1678,0,'Allow Click Lock Button Return Of Items','',0,'\\170710','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1679,0,'Allow Click UnLock Button Return Of Items','',0,'\\170711','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1333,0,'Allow Click Add Item Return Of Items','',0,'\\170712','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1334,0,'Allow Click Edit Item Return Of Items','',0,'\\170713','\\1707',0,'0',0," . $params['levelid'] . ") ,
        (1335,0,'Allow Click Delete Item Return Of Items','',0,'\\170714','\\1707',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HR','/module/hris/HR','Return of Items','fa fa-sticky-note sub_menu_ico',1158," . $params['levelid'] . ")";
    } //end function

    public function hi($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1178,0,'Incident Report','',0,'\\1708','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1179,0,'Allow View Incident Report','',0,'\\170801','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1180,0,'Allow Click Edit Button Incident Report','',0,'\\170802','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1181,0,'Allow Click New Button Incident Report','',0,'\\170803','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1182,0,'Allow Click Save Button Incident Report','',0,'\\170804','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1183,0,'Allow Click Change Code Incident Report','',0,'\\170805','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1184,0,'Allow Click Delete Button Incident Report','',0,'\\170806','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1185,0,'Allow Click Print Button Incident Report','',0,'\\170807','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1186,0,'Allow Click Post Button Incident Report','',0,'\\170808','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1187,0,'Allow Click UnPost Button Incident Report','',0,'\\170809','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1703,0,'Allow Click Lock Button Incident Report','',0,'\\170810','\\1708',0,'0',0," . $params['levelid'] . ") ,
        (1704,0,'Allow Click UnLock Button Incident Report','',0,'\\170811','\\1708',0,'0',0," . $params['levelid'] . "),
        (5227,0,'Allow To View All Employees','',0,'\\170812','\\1708',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HI','/module/hris/HI','Incident Report','fa fa-sticky-note sub_menu_ico',1178," . $params['levelid'] . ")";
    } //end function

    public function hn($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1208,0,'Notice to Explain','',0,'\\1709','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1209,0,'Allow View Notice to Explain','',0,'\\170901','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1210,0,'Allow Click Edit Button Notice to Explain','',0,'\\170902','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1211,0,'Allow Click New Button Notice to Explain','',0,'\\170903','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1212,0,'Allow Click Save Button Notice to Explain','',0,'\\170904','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1213,0,'Allow Click Change Code Notice to Explain','',0,'\\170905','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1214,0,'Allow Click Delete Button Notice to Explain','',0,'\\170906','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1215,0,'Allow Click Print Button Notice to Explain','',0,'\\170907','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1216,0,'Allow Click Post Button Notice to Explain','',0,'\\170908','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1217,0,'Allow Click UnPost Button Notice to Explain','',0,'\\170909','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1705,0,'Allow Click Lock Button Notice to Explain','',0,'\\170910','\\1709',0,'0',0," . $params['levelid'] . ") ,
        (1706,0,'Allow Click UnLock Button Notice to Explain','',0,'\\170911','\\1709',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HN','/module/hris/HN','Notice to Explain','fa fa-sticky-note sub_menu_ico',1208," . $params['levelid'] . ")";
    } //end function

    public function hd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1198,0,'Notice of Disciplinary Action','',0,'\\1710','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1199,0,'Allow View Notice of Disciplinary Action','',0,'\\171001','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1200,0,'Allow Click Edit Button Notice of Disciplinary Action','',0,'\\171002','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1201,0,'Allow Click New Button Notice of Disciplinary Action','',0,'\\171003','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1202,0,'Allow Click Save Button Notice of Disciplinary Action','',0,'\\171004','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1203,0,'Allow Click Change Code Notice of Disciplinary Action','',0,'\\171005','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1204,0,'Allow Click Delete Button Notice of Disciplinary Action','',0,'\\171006','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1205,0,'Allow Click Print Button Notice of Disciplinary Action','',0,'\\171007','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1206,0,'Allow Click Post Button Notice of Disciplinary Action','',0,'\\171008','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1207,0,'Allow Click UnPost Button Notice of Disciplinary Action','',0,'\\171009','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1707,0,'Allow Click Lock Button Notice of Disciplinary Action','',0,'\\171010','\\1710',0,'0',0," . $params['levelid'] . ") ,
        (1708,0,'Allow Click UnLock Button Notice of Disciplinary Action','',0,'\\171011','\\1710',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HD','/module/hris/HD','Notice of Disciplinary Action','fa fa-sticky-note sub_menu_ico',1198," . $params['levelid'] . ")";
    } //end function


    public function hc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1228,0,'Clearance','',0,'\\1711','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1229,0,'Allow View Clearance','',0,'\\171101','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1230,0,'Allow Click Edit Button Clearance','',0,'\\171102','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1231,0,'Allow Click New Button Clearance','',0,'\\171103','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1232,0,'Allow Click Save Button Clearance','',0,'\\171104','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1233,0,'Allow Click Change Code Clearance','',0,'\\171105','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1234,0,'Allow Click Delete Button Clearance','',0,'\\171106','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1235,0,'Allow Click Print Button Clearance','',0,'\\171107','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1236,0,'Allow Click Post Button Clearance','',0,'\\171108','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1237,0,'Allow Click UnPost Button Clearance','',0,'\\171109','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1709,0,'Allow Click Lock Button Clearance','',0,'\\171110','\\1711',0,'0',0," . $params['levelid'] . ") ,
        (1710,0,'Allow Click UnLock Button Clearance','',0,'\\171111','\\1711',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HC','/module/hris/HC','Clearance','fa fa-sticky-note sub_menu_ico',1228," . $params['levelid'] . ")";
    } //end function

    public function hs($params, $parent, $sort)
    {
        $label = 'Employment Status Entry / Change';
        switch ($params['companyid']) {
            case 58: //camera
                $label = 'Personnel Action Form';
                break;
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1168,0,'$label','',0,'\\1712','$parent',0,'0',0," . $params['levelid'] . ") ,
        (1169,0,'Allow View $label','',0,'\\171201','\\1712',0,'0',0," . $params['levelid'] . ") ,
        (1170,0,'Allow Click Edit Button $label','',0,'\\171202','\\1712',0,'0',0," . $params['levelid'] . ") ,
        (1171,0,'Allow Click New Button $label','',0,'\\171203','\\1712',0,'0',0," . $params['levelid'] . ") ,
        (1172,0,'Allow Click Save Button $label','',0,'\\171204','\\1712',0,'0',0," . $params['levelid'] . ") ,
        (1173,0,'Allow Click Change Code $label','',0,'\\171205','\\1712',0,'0',0," . $params['levelid'] . ") ,
        (1174,0,'Allow Click Delete Button $label','',0,'\\171206','\\1712',0,'0',0," . $params['levelid'] . ") ,
        (1175,0,'Allow Click Print Button $label','',0,'\\171207','\\1712',0,'0',0," . $params['levelid'] . ")";

        switch ($params['companyid']) {
            case 58: //cdo
                break;
            default:
                $qry .= ", (1176,0,'Allow Click Post Button Employment Status Entry / Change','',0,'\\171208','\\1712',0,'0',0," . $params['levelid'] . ") ,
                           (1177,0,'Allow Click UnPost Button Employment Status Entry / Change','',0,'\\171209','\\1712',0,'0',0," . $params['levelid'] . "),
                           (1701,0,'Allow Click Lock Button Employment Status Entry / Change','',0,'\\171210','\\1712',0,'0',0," . $params['levelid'] . "),
                           (1702,0,'Allow Click UnLock Button Employment Status Entry / Change','',0,'\\171211','\\1712',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $qry .= "";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'hs','/module/hris/hs','" . $label . "','fa fa-sticky-note sub_menu_ico',1168," . $params['levelid'] . ")";
    } //end function

    public function empndahistory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1448,0,'Employment NDA History','',0,'\\1713','$parent',0,'0',0," . $params['levelid'] . "),
        (1344,0,'Allow View Employment NDA History','',0,'\\171301','\\1713',0,'0',0," . $params['levelid'] . ")";

        if ($params['companyid'] == 58) { //cdo
            $qry .= ", (5436,0,'Allow To View All Employees','',0,'\\171302','\\1713',0,'0',0," . $params['levelid'] . ")";
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'empndahistory','/ledgergrid/hris/empndahistory','Employment NDA History','fa fa-sticky-note sub_menu_ico',1448," . $params['levelid'] . ")";
    } //end function

    public function empchangehistory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1450,0,'Employment Change History','',0,'\\1714','$parent',0,'0',0," . $params['levelid'] . "),
              (1345,0,'Allow View Employment Change History','',0,'\\171401','\\1714',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'empchangehistory','/ledgergrid/hris/empchangehistory','Employment Change History','fa fa-sticky-note sub_menu_ico',1450," . $params['levelid'] . ")";
    } //end function

    public function parentpayrollsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1269,0,'PAYROLL SETUP','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PAYROLL SETUP',$sort,'fa fa-coins',',,'," . $params['levelid'] . ")";
    } //end function


    public function division($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1410,1,'Company','',0,'\\1901','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'division','/tableentries/payrollsetup/division','Company','fa fa-boxes sub_menu_ico',1410," . $params['levelid'] . ")";
    } //end function

    public function rank($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2630,1,'Rank','',0,'\\1904','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entryrank','/tableentries/payrollsetup/entryrank','Rank','fa fa-boxes sub_menu_ico',2630," . $params['levelid'] . ")";
    } //end function

    public function section($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1470,1,'Section','',0,'\\1902','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'section','/tableentries/payrollsetup/section','Section','fa fa-boxes sub_menu_ico',1470," . $params['levelid'] . ")";
    } //end function

    public function paygroup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1480,1,'Pay Group','',0,'\\1903','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'paygroup','/tableentries/payrollsetup/paygroup','Pay Group','fa fa-users sub_menu_ico',1480," . $params['levelid'] . ")";
    } //end function

    public function annualtax($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1500,1,'Annual Tax','',0,'\\1905','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'annualtax','/tableentries/payrollsetup/annualtax','Annual Tax','fa fa-percent sub_menu_ico',1500," . $params['levelid'] . ")";
    } //end function

    public function philhealth($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1510,1,'Philhealth','',0,'\\1906','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'philhealth','/tableentries/payrollsetup/philhealth','Philhealth','fa fa-percent sub_menu_ico',1510," . $params['levelid'] . ")";
    } //end function


    public function sss($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1449,1,'SSS','',0,'\\1907','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'sss','/tableentries/payrollsetup/sss','SSS','fa fa-percent sub_menu_ico',1449," . $params['levelid'] . ")";
    } //end function

    public function pagibig($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1451,1,'Pag-ibig','',0,'\\1908','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pagibig','/tableentries/payrollsetup/pagibig','Pag-ibig','fa fa-percent sub_menu_ico',1451," . $params['levelid'] . ")";
    } //end function

    public function tax($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1520,1,'Tax','',0,'\\1909','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tax','/tableentries/payrollsetup/tax','Withholding Tax','fa fa-percent sub_menu_ico',1520," . $params['levelid'] . ")";
    } //end function

    public function holiday($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1530,1,'Holiday','',0,'\\1910','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'holiday','/tableentries/payrollsetup/holiday','Holiday','fa fa-boxes sub_menu_ico',1530," . $params['levelid'] . ")";
    } //end function

    public function holidayloc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5216,1,'Holiday Location','',0,'\\1917','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'holidayloc','/tableentries/payrollsetup/holidayloc','Holiday Location','fa fa-holly-berry sub_menu_ico',5216," . $params['levelid'] . ")";
        //<i class="fa-solid fa-holly-berry"></i>
    } //end function

    public function leavesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1540,1,'Leave Setup','',0,'\\1911','$parent',0,0,0," . $params['levelid'] . ") ,
        (1541,1,'Allow Click New Button Leave Setup','',0,'\\191101','\\1911',0,0,0," . $params['levelid'] . ") ,
        (1542,1,'Allow Click Save Button Leave Setup','',0,'\\191102','\\1911',0,0,0," . $params['levelid'] . ") ,
        (1543,1,'Allow Click Delete Button Leave Setup','',0,'\\191103','\\1911',0,0,0," . $params['levelid'] . ") ,
        (1544,1,'Allow Click Print Button Leave Setup','',0,'\\191104','\\1911',0,0,0," . $params['levelid'] . ") ,
        (1545,1,'Allow View Leave Setup','',0,'\\191105','\\1911',0,0,0," . $params['levelid'] . "),
        (1728,1,'Allow Click Edit Button Leave Setup','',0,'\\191106','\\1911',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leavesetup','/ledgergrid/payrollsetup/leavesetup','Leave Setup','fa fa-calendar-alt sub_menu_ico',1540," . $params['levelid'] . ")";
    } //end function

    public function leavebatchcreation($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2797,1,'Leave Batch Creation','',0,'\\1914','$parent',0,0,0," . $params['levelid'] . ") ,
        (2798,1,'Allow Click New Button Leave Batch Creation','',0,'\\191401','\\1914',0,0,0," . $params['levelid'] . ") ,
        (2799,1,'Allow Click Save Button Leave Batch Creation','',0,'\\191402','\\1914',0,0,0," . $params['levelid'] . ") ,
        (2800,1,'Allow Click Delete Button Leave Batch Creation','',0,'\\191403','\\1914',0,0,0," . $params['levelid'] . ") ,
        (2801,1,'Allow Click Print Button Leave Batch Creation','',0,'\\191404','\\1914',0,0,0," . $params['levelid'] . ") ,
        (2802,1,'Allow View Leave Batch Creation','',0,'\\191405','\\1914',0,0,0," . $params['levelid'] . ") ,
        (2803,1,'Allow Click Edit Button Leave Batch Creation','',0,'\\191406','\\1914',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leavebatchcreation','/headtable/payrollcustomform/leavebatchcreation','Leave Batch Creation','fa fa-calendar-alt sub_menu_ico',2797," . $params['levelid'] . ")";
    } //end function


    public function ls($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5408,0,'Leave Batch Creation','',0,'\\1920','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ls','/tableentries/tableentry/ls','Leave Batch Creation','fa fa-calendar-alt sub_menu_ico',5408," . $params['levelid'] . ")";
    } //end function  


    public function payrollaccount($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1490,1,'Payroll Accounts','',0,'\\191107','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'payrollaccount','/tableentries/payrollsetup/payrollaccounts','Payroll Accounts','fa fa-list sub_menu_ico',1490," . $params['levelid'] . ")";
    } //end function

    public function ratesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1550,1,'Rate Setup','',0,'\\1912','$parent',0,0,0," . $params['levelid'] . ") ,
        (1551,1,'Allow Click Save Button Rate Setup','',0,'\\191201','\\1912',0,0,0," . $params['levelid'] . ") ,
        (1552,1,'Allow Click Print Button Rate Setup','',0,'\\191202','\\1912',0,0,0," . $params['levelid'] . ") ,
        (1553,1,'Allow View Rate Setup','',0,'\\191203','\\1912',0,0,0," . $params['levelid'] . ") ,
        (1554,1,'Allow Click New Button Rate Setup','',0,'\\191204','\\1912',0,0,0," . $params['levelid'] . ") ,
        (1555,1,'Allow Click Delete Button Rate Setup','',0,'\\191205','\\1912',0,0,0," . $params['levelid'] . ") ,
        (1556,1,'Allow Click Edit Button Rate Setup','',0,'\\191206','\\1912',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ratesetup','/ledgergrid/payrollsetup/ratesetup','Rate Setup','fa fa-money-bill sub_menu_ico',1550," . $params['levelid'] . ")";
    } //end function

    public function shiftsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1560,1,'Shift Setup','',0,'\\1913','$parent',0,0,0," . $params['levelid'] . ") ,
        (1561,1,'Allow Click Save Button Shift Setup','',0,'\\191301','\\1913',0,0,0," . $params['levelid'] . ") ,
        (1562,1,'Allow Click Print Button Shift Setup','',0,'\\191302','\\1913',0,0,0," . $params['levelid'] . ") ,
        (1563,1,'Allow Click New Button Shift Setup','',0,'\\191303','\\1913',0,0,0," . $params['levelid'] . ") ,
        (1564,1,'Allow Click Delete Button Shift Setup','',0,'\\191304','\\1913',0,0,0," . $params['levelid'] . ") ,
        (1565,1,'Allow View Shift Setup','',0,'\\191305','\\1913',0,0,0," . $params['levelid'] . ") ,
        (1346,1,'Allow Click Edit Button Shift Setup','',0,'\\191306','\\1913',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'shiftsetup','/ledgergrid/payrollsetup/shiftsetup','Shift Setup','fa fa-user-clock sub_menu_ico',1560," . $params['levelid'] . ")";
    } //end function

    public function parentpayrolltransaction($params, $parent, $sort)
    {
        $label = 'PAYROLL TRANSACTION';
        switch ($params['companyid']) {
            case 58: //camera
                $label = 'PAYROLL';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1266,0,'$label','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'$label',$sort,'fa fa-hand-holding-usd',',,'," . $params['levelid'] . ")";
    } //end function


    public function parentpayrollportal($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(150,0,'PORTAL','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PORTAL',$sort,'fa fa-house-user',',,'," . $params['levelid'] . ")";
    } //end function


    public function employeepayroll($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $iswindows = $this->companysetup->getiswindowspayroll($params);

        $add =
            "(1302,1,'Allow Click Edit Button EMP','',0,'\\201013','\\2010',0,'0',0," . $params['levelid'] . ") ,
        (1303,1,'Allow Click New Button EMP','',0,'\\201014','\\2010',0,'0',0," . $params['levelid'] . ") ,
        (1304,1,'Allow Click Save Button EMP','',0,'\\201015','\\2010',0,'0',0," . $params['levelid'] . ") ,
        (1305,1,'Allow Click Change Code EMP','',0,'\\201016','\\2010',0,'0',0," . $params['levelid'] . ") ,
        (1306,1,'Allow Click Delete Button EMP','',0,'\\201017','\\2010',0,'0',0," . $params['levelid'] . ") ,";
        if ($iswindows) {
            $add = "";
        }

        $qry = "(1720,1,'Employee','',0,'\\2010','$parent',0,0,0," . $params['levelid'] . ") ,
        (1290,1,'Allow Click Button General','',0,'\\201001','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1291,1,'Allow Click Button Dependents','',0,'\\201002','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1292,1,'Allow Click Button Education','',0,'\\201003','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1293,1,'Allow Click Button Employment','',0,'\\201004','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1294,1,'Allow Click Button Rate','',0,'\\201005','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1295,1,'Allow Click Button Loans','',0,'\\201006','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1296,1,'Allow Click Button Advances','',0,'\\201007','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1297,1,'Allow Click Button Contract','',0,'\\201008','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1298,1,'Allow Click Button Allowance','',0,'\\201009','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1299,1,'Allow Click Button Training','',0,'\\201010','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1300,1,'Allow Click Button Turn Over and Return Items','',0,'\\201011','\\2010',0,0,0," . $params['levelid'] . ") ,
        (1301,1,'Allow View Employee Ledger','',0,'\\201012','\\2010',0,'0',0," . $params['levelid'] . ") ,
        $add
        (1307,1,'Allow Click Print Button EMP','',0,'\\201018','\\2010',0,'0',0," . $params['levelid'] . "),

        (2410,1,'Payroll Level 1','',0,'\\201019','\\2010',0,0,0," . $params['levelid'] . "),
        (2411,1,'Payroll Level 2','',0,'\\201020','\\2010',0,0,0," . $params['levelid'] . "),
        (2412,1,'Payroll Level 3','',0,'\\201021','\\2010',0,0,0," . $params['levelid'] . "),
        (2413,1,'Payroll Level 4','',0,'\\201022','\\2010',0,0,0," . $params['levelid'] . "),
        (2414,1,'Payroll Level 5','',0,'\\201023','\\2010',0,0,0," . $params['levelid'] . "),
        (2415,1,'Payroll Level 6','',0,'\\201024','\\2010',0,0,0," . $params['levelid'] . "),
        (2416,1,'Payroll Level 7','',0,'\\201025','\\2010',0,0,0," . $params['levelid'] . "),
        (2417,1,'Payroll Level 8','',0,'\\201026','\\2010',0,0,0," . $params['levelid'] . "),
        (2418,1,'Payroll Level 9','',0,'\\201027','\\2010',0,0,0," . $params['levelid'] . "),
        (2419,1,'Payroll Level 10','',0,'\\201028','\\2010',0,0,0," . $params['levelid'] . "),
        (5228,1,'Allow To View All Employees','',0,'\\201029','\\2010',0,'0',0," . $params['levelid'] . "),
        (5300,1,'Allow To View Rate','',0,'\\201030','\\2010',0,'0',0," . $params['levelid'] . "),
        (5435,1,'Allow To View Approver Setup','',0,'\\201031','\\2010',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'employeemasterfile','/ledgergrid/payroll/employee','Employee','fa fa-user sub_menu_ico',1720," . $params['levelid'] . ")";
    } //end function

    public function myinfo($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2804,1,'My Info','',0,'\\2016','$parent',0,0,0," . $params['levelid'] . ") ,
        (2805,1,'Allow View My Info','',0,'\\201601','\\2016',0,0,0," . $params['levelid'] . ") ,
        (2806,1,'Allow Click Button Print My Info','',0,'\\201602','\\2016',0,0,0," . $params['levelid'] . ") ";

        switch ($params['companyid']) {
            case 58: //cdo
                $qry .= ",(5302,1,'Allow Update My Info','',0,'\\201603','\\2016',0,0,0," . $params['levelid'] . ")";
                break;
        }
        $qry .= ",(5343,1,'Allow View Rate My Info','',0,'\\201604','\\2016',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'myinfo','/ledgergrid/payroll/myinfo','My Info','fa fa-file-alt sub_menu_ico',2804," . $params['levelid'] . ")";
    } //end function


    public function earningdeductionsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1580,1,'Earning And Deduction Setup','',0,'\\2002','$parent',0,0,0," . $params['levelid'] . ") ,
        (1581,1,'Allow Click Save Button Earning And Deduction Setup','',0,'\\200201','\\2002',0,0,0," . $params['levelid'] . ") ,
        (1582,1,'Allow Click Print Button Earning And Deduction Setup','',0,'\\200202','\\2002',0,0,0," . $params['levelid'] . ") ,
        (1583,1,'Allow Click New Button Earning And Deduction Setup','',0,'\\200203','\\2002',0,0,0," . $params['levelid'] . ") ,
        (1584,1,'Allow Click Delete Button Earning And Deduction Setup','',0,'\\200204','\\2002',0,0,0," . $params['levelid'] . ") ,
        (1585,1,'Allow View Earning And Deduction Setup','',0,'\\200205','\\2002',0,0,0," . $params['levelid'] . "),
        (1586,1,'Allow Edit Earning And Deduction Setup','',0,'\\200206','\\2002',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'earningdeductionsetup','/ledgergrid/payrollsetup/earningdeductionsetup','Earning And Deduction Setup','fa fa-coins sub_menu_ico',1580," . $params['levelid'] . ")";
    } //end function

    public function advancesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2636,1,'Advance Setup','',0,'\\2005','$parent',0,0,0," . $params['levelid'] . ") ,
        (2637,1,'Allow Click Save Button Advance Setup','',0,'\\200501','\\2005',0,0,0," . $params['levelid'] . ") ,
        (2638,1,'Allow Click Print Button Advance Setup','',0,'\\200502','\\2005',0,0,0," . $params['levelid'] . ") ,
        (2639,1,'Allow Click New Button Advance Setup','',0,'\\200503','\\2005',0,0,0," . $params['levelid'] . ") ,
        (2640,1,'Allow Click Delete Button Advance Setup','',0,'\\200504','\\2005',0,0,0," . $params['levelid'] . ") ,
        (2641,1,'Allow View Advance Setup','',0,'\\200505','\\2005',0,0,0," . $params['levelid'] . "),
        (2642,1,'Allow Edit Advance Setup','',0,'\\200506','\\2005',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'advancesetup','/ledgergrid/payrollsetup/advancesetup','Advance Setup','fa fa-coins sub_menu_ico',2636," . $params['levelid'] . ")";
    } //end function

    public function leaveapplication($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1590,1,'Leave Application','',0,'\\2003','$parent',0,0,0," . $params['levelid'] . ") ,
        (1591,1,'Allow Click Save Button Leave Application','',0,'\\200301','\\2003',0,0,0," . $params['levelid'] . ") ,
        (1592,1,'Allow Click Print Button Leave Application','',0,'\\200302','\\2003',0,0,0," . $params['levelid'] . ") ,
        (1593,1,'Allow Click New Button Leave Application','',0,'\\200303','\\2003',0,0,0," . $params['levelid'] . ") ,
        (1594,1,'Allow Click Delete Button Leave Application','',0,'\\200304','\\2003',0,0,0," . $params['levelid'] . ") ,
        (1595,1,'Allow View Leave Application','',0,'\\200305','\\2003',0,0,0," . $params['levelid'] . ") ,
        (1596,1,'Allow Click Edit Button Leave Application','',0,'\\200306','\\2003',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leaveapplication','/ledgergrid/payroll/leaveapplication','Leave Application','fa fa-calendar-alt sub_menu_ico',1590," . $params['levelid'] . ")";
    } //end function

    public function pieceentry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1600,1,'Piece Entry','',0,'\\2004','$parent',0,0,0," . $params['levelid'] . ") ,
        (1601,1,'Allow View Piece Entry','',0,'\\200401','\\2004',0,0,0," . $params['levelid'] . ") ,
        (1602,1,'Allow Click Create Button','',0,'\\200402','\\2004',0,0,0," . $params['levelid'] . ") ,
        (1603,1,'Allow Edit Piece Entry','',0,'\\200403','\\2004',0,0,0," . $params['levelid'] . ") ,
        (1604,1,'Allow CLick Delete Button','',0,'\\200404','\\2004',0,0,0," . $params['levelid'] . ") ,
        (1605,1,'Allow Click Print Button','',0,'\\200405','\\2004',0,0,0," . $params['levelid'] . ") ,
        (1606,1,'Allow Click Save all Entry','',0,'\\200406','\\2004',0,0,0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pieceentry','/headtable/payrollcustomform/pieceentry','Piece Entry','fa fa-file-alt sub_menu_ico',1600," . $params['levelid'] . ")";
    } //end function

    public function batchsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1570,1,'Batch Setup','',0,'\\2001','$parent',0,0,0," . $params['levelid'] . ") ,
        (1347,1,'Allow Click Save Button Batch Setup','',0,'\\200101','\\2001',0,0,0," . $params['levelid'] . ") ,
        (1348,1,'Allow Click Print Button Batch Setup','',0,'\\200102','\\2001',0,0,0," . $params['levelid'] . ") ,
        (1349,1,'Allow Click New Button Batch Setup','',0,'\\200103','\\2001',0,0,0," . $params['levelid'] . ") ,
        (1350,1,'Allow Click Delete Button Batch Setup','',0,'\\200104','\\2001',0,0,0," . $params['levelid'] . ") ,
        (1351,1,'Allow View Batch Setup','',0,'\\200105','\\2001',0,0,0," . $params['levelid'] . ") ,
        (1352,1,'Allow Click Edit Button Batch Setup','',0,'\\200106','\\2001',0,0,0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'batchsetup','/ledger/payroll/batchsetup','Batch Setup','fa fa-list sub_menu_ico',1570," . $params['levelid'] . ")";
    } //end function

    public function emptimecard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1620,1,'Employee`s Timecard','',0,'\\2006','$parent',0,0,0," . $params['levelid'] . ") ,
        (1621,1,'Allow View Employee`s Timecard','',0,'\\200601','\\2006',0,0,0," . $params['levelid'] . ") ,
        (1622,1,'Allow Click Button Save Employee`s Timecard','',0,'\\200602','\\2006',0,0,0," . $params['levelid'] . ") ,
        (1623,1,'Allow Click Button Print Employee`s Timecard','',0,'\\200603','\\2006',0,0,0," . $params['levelid'] . ") ,
        (1624,1,'Allow Click Button Edit Employee`s Timecard','',0,'\\200604','\\2006',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'emptimecard','/headtable/payrollcustomform/emptimecard','Employee`s Timecard','fa fa-calendar-week sub_menu_ico',1620," . $params['levelid'] . ")";
    } //end function


    public function timecardsetup($params, $parent, $sort)
    {
        $label = 'Payroll Process';
        if ($this->companysetup->istimekeeping($params)) {
            $label = 'Timekeeping Process';
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2480,1,'" . $label . "','',0,'\\2007','$parent',0,0,0," . $params['levelid'] . ") ,
        (2481,1,'Allow View " . $label . "','',0,'\\200701','\\2007',0,0,0," . $params['levelid'] . ") ,
        (2482,1,'Allow Click Button Save " . $label . "','',0,'\\200702','\\2007',0,0,0," . $params['levelid'] . ") ,
        (2483,1,'Allow Click Button Print " . $label . "','',0,'\\200703','\\2007',0,0,0," . $params['levelid'] . ") ,
        (2484,1,'Allow Click Button Edit " . $label . "','',0,'\\200704','\\2007',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'timecardsetup','/headtable/payrollcustomform/payrollprocess','" . $label . "','fa fa-calculator sub_menu_ico',2480," . $params['levelid'] . ")";
    } //end function

    public function timerec($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4623,1,'Time In/Out','',0,'\\2026','$parent',0,0,0," . $params['levelid'] . ") ,
        (4624,1,'Allow View Time In/Out','',0,'\\202601','\\2026',0,0,0," . $params['levelid'] . ") ,
        (4625,1,'Allow Click Button Save','',0,'\\202602','\\2026',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'timerec','/headtable/payrollcustomform/timerec','TIME IN/OUT','fa fa-calendar-day sub_menu_ico',4623," . $params['levelid'] . ")";
    }

    public function otapproval($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1630,1,'Overtime Approval','',0,'\\2008','$parent',0,0,0," . $params['levelid'] . "),
        (1631,1,'Allow View Overtime Approval','',0,'\\200801','\\2008',0,0,0," . $params['levelid'] . "),
        (1632,1,'Allow Click Button Save Overtime Approval','',0,'\\200802','\\2008',0,0,0," . $params['levelid'] . "),
        (1633,1,'Allow Click Button Print Overtime Approval','',0,'\\200803','\\2008',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'otapproval','/headtable/payrollentry/entryotapproval','Overtime Approval','fa fa-clock sub_menu_ico',1630," . $params['levelid'] . ")";
    } //end function

    public function payrollsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1650,1,'Payroll Entry','',0,'\\2009','$parent',0,0,0," . $params['levelid'] . ") ,
        (1651,1,'Allow View Payroll Entry','',0,'\\200901','\\2009',0,0,0," . $params['levelid'] . ") ,
        (1652,1,'Allow Click Button Entry Payroll Entry','',0,'\\200902','\\2009',0,0,0," . $params['levelid'] . ") ,
        (1653,1,'Allow Click Button Process Payroll Entry','',0,'\\200903','\\2009',0,0,0," . $params['levelid'] . ") ,
        (1353,1,'Allow Click Button Save Payroll Entry','',0,'\\200904','\\2009',0,0,0," . $params['levelid'] . ") ,
        (1354,1,'Allow Click Button Edit Payroll Entry','',0,'\\200905','\\2009',0,0,0," . $params['levelid'] . ") ,
        (1355,1,'Allow Click Add Item Payroll Entry','',0,'\\200906','\\2009',0,0,0," . $params['levelid'] . ") ,
        (1356,1,'Allow Click Button Print Payroll Entry','',0,'\\200907','\\2009',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'payrollsetup','/headtable/payrollcustomform/payrollentry','Payroll Entry','fa fa-sticky-note sub_menu_ico',1650," . $params['levelid'] . ")";
    } //end function

    public function obapplication($params, $parent, $sort)
    {
        $label = 'OB Application';
        switch ($params['companyid']) {
            case 53: //camera
                $label = 'OB/Offset Application';
                break;
            case 58: //cdo
                $label = 'Tracking Application';
                break;
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2535,1,'$label','',0,'\\2011','$parent',0,0,0," . $params['levelid'] . ") ,
        (2536,1,'Allow Click Save Button $label','',0,'\\2011001','\\2011',0,0,0," . $params['levelid'] . ") ,
        (2537,1,'Allow Click Print Button $label','',0,'\\2011002','\\2011',0,0,0," . $params['levelid'] . ") ,
        (2538,1,'Allow Click New Button $label','',0,'\\2011003','\\2011',0,0,0," . $params['levelid'] . ") ,
        (2539,1,'Allow Click Delete Button $label','',0,'\\2011004','\\2011',0,0,0," . $params['levelid'] . ") ,
        (2540,1,'Allow View $label','',0,'\\2011005','\\2011',0,0,0," . $params['levelid'] . "),
        (2541,1,'Allow Click Edit Button $label','',0,'\\2011006','\\2011',0,0,0," . $params['levelid'] . "),
        (3627,1,'Allow View Dashboard $label','',0,'\\2011007','\\2011',0,0,0," . $params['levelid'] . "),
        (5032,1,'Allow View Dashboard $label Initial','',0,'\\2011008','\\2011',0,0,0," . $params['levelid'] . "),
        (5033,1,'Allow Disapproved $label','',0,'\\2011009','\\2011',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'obapplication','/ledgergrid/payroll/obapplication','" . $label . "','fa fa-coins sub_menu_ico',2535," . $params['levelid'] . ")";
    } //end function

    public function otapplication($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3576,1,'OT Application','',0,'\\2017','$parent',0,0,0," . $params['levelid'] . ") ,
        (3581,1,'Allow View OT Application','',0,'\\2017005','\\2017',0,0,0," . $params['levelid'] . "),
        (5029,1,'Allow Delete OT Application','',0,'\\2017006','\\2017',0,0,0," . $params['levelid'] . "),
        (3628,1,'Allow View Dashboard OT Application','',0,'\\2017007','\\2017',0,0,0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'otapplication','/ledger/payroll/otapplication','OT Application','fa fa-coins sub_menu_ico',3576," . $params['levelid'] . ")";
    } //end function
    public function otapplicationadv($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4839,1,'OT Application','',0,'\\3206','$parent',0,0,0," . $params['levelid'] . ") ,
        (4840,1,'Allow View OT Application','',0,'\\3206001','\\3206',0,0,0," . $params['levelid'] . "),
        (5029,1,'Allow Delete OT Application','',0,'\\3206002','\\3206',0,0,0," . $params['levelid'] . "),
        (4841,1,'Allow View Dashboard OT Application','',0,'\\3206003','\\3206',0,0,0," . $params['levelid'] . "),
        (5035,1,'Allow Disapproved OT','',0,'\\3206004','\\3206',0,0,0," . $params['levelid'] . "),
        (5450,1,'Allow Click Print Button','',0,'\\3206005','\\3206',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'otapplicationadv','/ledger/payroll/otapplicationadv','OT Application','fa fa-coins sub_menu_ico',4839," . $params['levelid'] . ")";
    } //end function
    public function leaveapplicationportal($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2375,1,'Leave Application','',0,'\\\\2014','$parent',0,0,0," . $params['levelid'] . ") ,
      (2376,1,'Allow Click Save Button Leave Application','',0,'\\201401','\\\\2014',0,0,0," . $params['levelid'] . ") ,
      (2377,1,'Allow Click Print Button Leave Application','',0,'\\201402','\\\\2014',0,0,0," . $params['levelid'] . ") ,
      (2378,1,'Allow Click New Button Leave Application','',0,'\\201403','\\\\2014',0,0,0," . $params['levelid'] . ") ,
      (2379,1,'Allow Click Delete Button Leave Application','',0,'\\201404','\\\\2014',0,0,0," . $params['levelid'] . ") ,
      (2380,1,'Allow View Leave Application','',0,'\\201405','\\\\2014',0,0,0," . $params['levelid'] . ") ,
      (2381,1,'Allow Click Edit Button Leave Application','',0,'\\201406','\\\\2014',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leaveapplicationportal','/ledgergrid/payroll/leaveapplicationportal','Leave Application','fa fa-calendar-alt sub_menu_ico',2375," . $params['levelid'] . ")";
    } //end function

    public function leaveapplicationportalapproval($params, $parent, $sort)
    {
        $label = 'Leave Application Portal Approval';
        if ($params['companyid'] == 58) { //cdo-hris
            $label = 'Leave Application Portal Status';
        }
        $p = $parent;
        $parent = '\\' . $parent;

        if ($params['companyid'] == 58) { //cdo-hris
            $qry = "(2864,1,'" . $label . "','',0,'\\2015','$parent',0,0,0," . $params['levelid'] . ") ,
                (2865,1,'Allow View " . $label . "','',0,'\\201501','\\2015',0,0,0," . $params['levelid'] . ")";
        } else {
            $qry = "(2864,1,'" . $label . "','',0,'\\2015','$parent',0,0,0," . $params['levelid'] . ") ,
                (2865,1,'Allow View " . $label . "','',0,'\\201501','\\2015',0,0,0," . $params['levelid'] . ") ,
                (2866,1,'Allow Click Save Button " . $label . "','',0,'\\201502','\\2015',0,0,0," . $params['levelid'] . ") ,
                (2867,1,'Allow Click Print Button " . $label . "','',0,'\\201503','\\2015',0,0,0," . $params['levelid'] . "),
                (3629,1,'Allow View Dashboard Leave Application Portal','',0,'\\201504','\\2015',0,0,0," . $params['levelid'] . ")";
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'leaveapplicationportalapproval','/headtable/payrollentry/leaveapplicationportalapproval','" . $label . "','fa fa-calendar-alt sub_menu_ico',2864," . $params['levelid'] . ")";
    } //end function

    public function loanapplication($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2420,1,'Loan Application','',0,'\\2012','$parent',0,0,0," . $params['levelid'] . ") ,
        (2421,1,'Allow Click Save Button Loan Application','',0,'\\201201','\\2012',0,0,0," . $params['levelid'] . ") ,
        (2422,1,'Allow Click Print Button Loan Application','',0,'\\201202','\\2012',0,0,0," . $params['levelid'] . ") ,
        (2423,1,'Allow Click New Button Loan Application','',0,'\\201203','\\2012',0,0,0," . $params['levelid'] . ") ,
        (2424,1,'Allow Click Delete Button Loan Application','',0,'\\201204','\\2012',0,0,0," . $params['levelid'] . ") ,
        (2425,1,'Allow View Loan Application','',0,'\\201205','\\2012',0,0,0," . $params['levelid'] . "),
        (2426,1,'Allow Click Edit Button Loan Application','',0,'\\201206','\\2012',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EL','/ledgergrid/payroll/loanapplication','Loan Application','fa fa-coins sub_menu_ico',2420," . $params['levelid'] . ")";
    } //end function

    public function loanapplicationportal($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4778,1,'Loan Application','',0,'\\2013','$parent',0,0,0," . $params['levelid'] . ") ,
        (4779,1,'Allow Click Save Button Loan Application','',0,'\\201301','\\2013',0,0,0," . $params['levelid'] . ") ,
        (4780,1,'Allow Click Print Button Loan Application','',0,'\\201302','\\2013',0,0,0," . $params['levelid'] . ") ,
        (4781,1,'Allow Click New Button Loan Application','',0,'\\201303','\\2013',0,0,0," . $params['levelid'] . ") ,
        (4782,1,'Allow Click Delete Button Loan Application','',0,'\\201304','\\2013',0,0,0," . $params['levelid'] . ") ,
        (4783,1,'Allow View Loan Application','',0,'\\201305','\\2013',0,0,0," . $params['levelid'] . "),
        (4784,1,'Allow Click Edit Button Loan Application','',0,'\\201306','\\2013',0,0,0," . $params['levelid'] . "),
        (3630,1,'Allow View Dashboard Loan Application','',0,'\\201307','\\2013',0,0,0," . $params['levelid'] . "),
        (5037,1,'Allow Disapproved Loan','',0,'\\201308','\\2013',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'loanapplicationportal','/ledgergrid/payroll/loanapplicationportal','Loan Application','fa fa-coins sub_menu_ico',4778," . $params['levelid'] . ")";
    } //end function

    public function portalreports($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2780,1,'Portal Reports','',0,'\\2015','$parent',0,0,0," . $params['levelid'] . ") ,
        (2781,1,'Allow View Portal Reports','',0,'\\201501','\\2015',0,0,0," . $params['levelid'] . ") ,
        (2782,1,'Allow Click Button Print Portal Reports','',0,'\\201502','\\2015',0,0,0," . $params['levelid'] . ") ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'portalreports','/headtable/payrollcustomform/portalreports','Payroll Register','fa fa-file-alt sub_menu_ico',2780," . $params['levelid'] . ")";
    } //end function

    public function parentprojectsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1775,0,'PROJECT SETUP','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PROJECT SETUP',$sort,'fa fa-sitemap sub_menu_ico',',,'," . $params['levelid'] . ")";
    } //end function

    public function pm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1776,0,'Project Management','',0,'\\2201','$parent',0,'0',0," . $params['levelid'] . "),
        (1777,0,'Allow View Transaction PM','PM',0,'\\220101','\\2201',0,'0',0," . $params['levelid'] . "),
        (1778,0,'Allow Click Edit Button PM','',0,'\\220102','\\2201',0,'0',0," . $params['levelid'] . "),
        (1779,0,'Allow Click New Button PM','',0,'\\220103','\\2201',0,'0',0," . $params['levelid'] . "),
        (1780,0,'Allow Click Save Button PM','',0,'\\220104','\\2201',0,'0',0," . $params['levelid'] . "),
        (1782,0,'Allow Click Delete Button PM','',0,'\\220106','\\2201',0,'0',0," . $params['levelid'] . "),
        (1783,0,'Allow Click Print Button PM','',0,'\\220107','\\2201',0,'0',0," . $params['levelid'] . "),
        (1784,0,'Allow Click Lock Button PM','',0,'\\220108','\\2201',0,'0',0," . $params['levelid'] . "),
        (1785,0,'Allow Click UnLock Button PM','',0,'\\220109','\\2201',0,'0',0," . $params['levelid'] . "),
        (1786,0,'Allow Click Post Button PM','',0,'\\220110','\\2201',0,'0',0," . $params['levelid'] . "),
        (1787,0,'Allow Click UnPost  Button PM','',0,'\\220111','\\2201',0,'0',0," . $params['levelid'] . "),
        (1788,1,'Allow Click Add Subproject PM','',0,'\\220112','\\2201',0,'0',0," . $params['levelid'] . "),
        (1789,1,'Allow Click Edit Subproject PM','',0,'\\220113','\\2201',0,'0',0," . $params['levelid'] . "),
        (1790,1,'Allow Click Delete Subproject PM','',0,'\\220114','\\2201',0,'0',0," . $params['levelid'] . "),
        (1791,0,'Allow View Ref. Documents','',0,'\\220115','\\2201',0,'0',0," . $params['levelid'] . "),
        (1792,0,'Allow View Attachment PM','',0,'\\220116','\\2201',0,'0',0," . $params['levelid'] . "),
        (1793,0,'Allow View Summary PM','',0,'\\220117','\\2201',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PM','/module/construction/pm','Project Management','fa fa-tasks sub_menu_ico',1776," . $params['levelid'] . ")";
    } //end function


    public function stages($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1857,0,'Stage Setup','',0,'\\2202','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        $systemtype = $this->companysetup->getsystemtype($params);
        $label = 'Stage Setup';
        switch ($systemtype) {
            case 'MANUFACTURING':
                $label = 'Process';
                break;
        }
        return "($sort,$p,'stages','/tableentries/tableentry/entrystages','" . $label . "','fa fa-boxes sub_menu_ico',1857," . $params['levelid'] . ")";
    } //end function

    public function parentconstruction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1795,0,'CONSTRUCTION','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'CONSTRUCTION SYSTEM',$sort,'fa fa-hard-hat',',,'," . $params['levelid'] . ")";
    } //end function

    public function al($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'AL','/actionlisting/actionlisting/approvallist','Approval List','fa fa-calendar-check sub_menu_ico',2235," . $params['levelid'] . ")";
    } //end function


    public function br($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2255,0,'Budget Request','',0,'\\2308','$parent',0,'0',0," . $params['levelid'] . "),
        (2256,0,'Allow View Transaction BR','BR',0,'\\230801','\\2308',0,'0',0," . $params['levelid'] . "),
        (2257,0,'Allow Click Edit Button BR','',0,'\\230802','\\2308',0,'0',0," . $params['levelid'] . "),
        (2258,0,'Allow Click New Button BR','',0,'\\230803','\\2308',0,'0',0," . $params['levelid'] . "),
        (2259,0,'Allow Click Save Button BR','',0,'\\230804','\\2308',0,'0',0," . $params['levelid'] . "),
        (2261,0,'Allow Click Delete Button BR','',0,'\\230806','\\2308',0,'0',0," . $params['levelid'] . "),
        (2262,0,'Allow Click Print Button BR','',0,'\\230807','\\2308',0,'0',0," . $params['levelid'] . "),
        (2263,0,'Allow Click Lock Button BR','',0,'\\230808','\\2308',0,'0',0," . $params['levelid'] . "),
        (2264,0,'Allow Click UnLock Button BR','',0,'\\230809','\\2308',0,'0',0," . $params['levelid'] . "),
        (2265,0,'Allow Click Post Button BR','',0,'\\230810','\\2308',0,'0',0," . $params['levelid'] . "),
        (2266,0,'Allow Click UnPost  Button BR','',0,'\\230811','\\2308',0,'0',0," . $params['levelid'] . "),
        (2267,1,'Allow Click Add Item BR','',0,'\\230812','\\2308',0,'0',0," . $params['levelid'] . "),
        (2268,1,'Allow Click Edit Item BR','',0,'\\230813','\\2308',0,'0',0," . $params['levelid'] . "),
        (2269,1,'Allow Click Delete Item BR','',0,'\\230814','\\2308',0,'0',0," . $params['levelid'] . "),
        (2270,1,'Allow Approve BR','',0,'\\230815','\\2308',0,'0',0," . $params['levelid'] . "),
        (2271,1,'Allow View All transaction BR','',0,'\\230816','\\2308',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BR','/module/construction/br','Budget Request','fa fa-hand-holding-usd sub_menu_ico',2255," . $params['levelid'] . ")";
    } //end function

    public function bl($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2273,0,'Budget Liquidation','',0,'\\2309','$parent',0,'0',0," . $params['levelid'] . "),
        (2274,0,'Allow View Transaction BL','BL',0,'\\230901','\\2309',0,'0',0," . $params['levelid'] . "),
        (2275,0,'Allow Click Edit Button BL','',0,'\\230902','\\2309',0,'0',0," . $params['levelid'] . "),
        (2276,0,'Allow Click New Button BL','',0,'\\230903','\\2309',0,'0',0," . $params['levelid'] . "),
        (2277,0,'Allow Click Save Button BL','',0,'\\230904','\\2309',0,'0',0," . $params['levelid'] . "),
        (2279,0,'Allow Click Delete Button BL','',0,'\\230906','\\2309',0,'0',0," . $params['levelid'] . "),
        (2280,0,'Allow Click Print Button BL','',0,'\\230907','\\2309',0,'0',0," . $params['levelid'] . "),
        (2281,0,'Allow Click Lock Button BL','',0,'\\230908','\\2309',0,'0',0," . $params['levelid'] . "),
        (2282,0,'Allow Click UnLock Button BL','',0,'\\230909','\\2309',0,'0',0," . $params['levelid'] . "),
        (2283,0,'Allow Click Post Button BL','',0,'\\230910','\\2309',0,'0',0," . $params['levelid'] . "),
        (2284,0,'Allow Click UnPost  Button BL','',0,'\\230911','\\2309',0,'0',0," . $params['levelid'] . "),
        (2285,1,'Allow Click Add Item BL','',0,'\\230912','\\2309',0,'0',0," . $params['levelid'] . "),
        (2286,1,'Allow Click Edit Item BL','',0,'\\230913','\\2309',0,'0',0," . $params['levelid'] . "),
        (2287,1,'Allow Click Delete Item BL','',0,'\\230914','\\2309',0,'0',0," . $params['levelid'] . "),
        (3575,1,'Allow View All transaction BL','',0,'\\230915','\\2309',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BL','/module/construction/bl','Budget Liquidation','fa fa-marker sub_menu_ico',2273," . $params['levelid'] . ")";
    } //end function

    public function prlisting($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3746,0,'Item Request Monitoring','',0,'\\2314','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'prlisting','/ledgergrid/ati/prlisting','Item Request Monitoring','fa fa-boxes sub_menu_ico',3746," . $params['levelid'] . ")";
    }

    public function barcodeassigning($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3887,0,'Item Barcode Assigning','',0,'\\2315','$parent',0,'0',0," . $params['levelid'] . "),
                (4388,0,'Allow View Only','',0,'\\231501','\\2315',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'barcodeassigning','/headtable/ati/barcodeassigning','Item Barcode Assigning','fa fa-barcode sub_menu_ico',3887," . $params['levelid'] . ")";
    }

    public function solisting($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5020,0,'Pending Sales Order Monitoring','',0,'\\2316','$parent',0,'0',0," . $params['levelid'] . "),
        (5021,0,'Allow View Remarks History','BL',0,'\\231601','\\2316',0,'0',0," . $params['levelid'] . "),
        (5022,0,'Allow Input Remarks','',0,'\\231602','\\2316',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'solisting','/ledgergrid/m1f0e3dad99908345f7439f8ffabdffc4/solisting','Pending Sales Order Monitoring','fa fa-list sub_menu_ico',5020," . $params['levelid'] . ")";
    }

    public function approversetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3888,0,'Approver Setup','',0,'\\897','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entryapprovers','/tableentries/tableentry/entryapprovers','Approver Setup','fa fa-list sub_menu_ico',3888," . $params['levelid'] . ")";
    }

    public function linearapprover($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4724,0,'Linear Approver','',0,'\\898','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrylinearapprovers','/tableentries/tableentry/entrylinearapprovers','Linear Approver','fa fa-list sub_menu_ico',4724," . $params['levelid'] . ")";
    }

    public function packhouseloading($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3772,0,'Pack House Loading','',0,'\\517','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'packhouseloading','/module/sales/packhouseloading','Pack House Loading','fa fa-boxes sub_menu_ico',3772," . $params['levelid'] . ")";
    }

    public function released($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3773,0,'Releasing','',0,'\\518','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'released','/module/sales/released','Releasing','fa fa-check sub_menu_ico',3773," . $params['levelid'] . ")";
    }

    public function bq($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1796,0,'Bill of Quantity','',0,'\\2301','$parent',0,'0',0," . $params['levelid'] . "),
        (1797,0,'Allow View Transaction BQ','BQ',0,'\\230101','\\2301',0,'0',0," . $params['levelid'] . "),
        (1798,0,'Allow Click Edit Button BQ','',0,'\\230102','\\2301',0,'0',0," . $params['levelid'] . "),
        (1799,0,'Allow Click New Button BQ','',0,'\\230103','\\2301',0,'0',0," . $params['levelid'] . "),
        (1800,0,'Allow Click Save Button BQ','',0,'\\230104','\\2301',0,'0',0," . $params['levelid'] . "),
        (1802,0,'Allow Click Delete Button BQ','',0,'\\230106','\\2301',0,'0',0," . $params['levelid'] . "),
        (1803,0,'Allow Click Print Button BQ','',0,'\\230107','\\2301',0,'0',0," . $params['levelid'] . "),
        (1804,0,'Allow Click Lock Button BQ','',0,'\\230108','\\2301',0,'0',0," . $params['levelid'] . "),
        (1805,0,'Allow Click UnLock Button BQ','',0,'\\230109','\\2301',0,'0',0," . $params['levelid'] . "),
        (1806,0,'Allow Click Post Button BQ','',0,'\\230110','\\2301',0,'0',0," . $params['levelid'] . "),
        (1807,0,'Allow Click UnPost  Button BQ','',0,'\\230111','\\2301',0,'0',0," . $params['levelid'] . "),
        (1808,1,'Allow Click Add Item BQ','',0,'\\230112','\\2301',0,'0',0," . $params['levelid'] . "),
        (1809,1,'Allow Click Edit Item BQ','',0,'\\230113','\\2301',0,'0',0," . $params['levelid'] . "),
        (1810,1,'Allow Click Delete Item BQ','',0,'\\230114','\\2301',0,'0',0," . $params['levelid'] . "),
        (3595,1,'Allow Void Button','',0,'\\230115','\\2301',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BQ','/module/construction/bq','Bill of Quantity','fa fa-user sub_menu_ico',1796," . $params['levelid'] . ")";
    } //end function

    public function constructionpr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(618,0,'Purchase Requisition','',0,'\\407','$parent',0,'0',0," . $params['levelid'] . "),
        (619,0,'Allow View Transaction PR','PR',0,'\\40701','\\407',0,'0',0," . $params['levelid'] . "),
        (620,0,'Allow Click Edit Button PR','',0,'\\40702','\\407',0,'0',0," . $params['levelid'] . "),
        (621,0,'Allow Click New Button PR','',0,'\\40703','\\407',0,'0',0," . $params['levelid'] . "),
        (622,0,'Allow Click Save Button PR','',0,'\\40704','\\407',0,'0',0," . $params['levelid'] . "),
        (624,0,'Allow Click Delete Button PR','',0,'\\40706','\\407',0,'0',0," . $params['levelid'] . "),
        (625,0,'Allow Click Print Button PR','',0,'\\40707','\\407',0,'0',0," . $params['levelid'] . "),
        (626,0,'Allow Click Lock Button PR','',0,'\\40708','\\407',0,'0',0," . $params['levelid'] . "),
        (627,0,'Allow Click UnLock Button PR','',0,'\\40709','\\407',0,'0',0," . $params['levelid'] . "),
        (630,0,'Allow Click Post Button PR','',0,'\\40710','\\407',0,'0',0," . $params['levelid'] . "),
        (631,0,'Allow Click UnPost Button PR','',0,'\\40711','\\407',0,'0',0," . $params['levelid'] . "),
        (628,0,'Allow Change Amount PR','',0,'\\40713','\\407',0,'0',0," . $params['levelid'] . "),
        (814,1,'Allow Click Add Item PR','',0,'\\40714','\\407',0,'0',0," . $params['levelid'] . "),
        (815,1,'Allow Click Edit Item PR','',0,'\\40715','\\407',0,'0',0," . $params['levelid'] . "),
        (816,1,'Allow Click Delete Item PR','',0,'\\40716','\\407',0,'0',0," . $params['levelid'] . "),
        (2235,0,'Approval List','',0,'\\2312','$parent',0,'0',0," . $params['levelid'] . "),
        (2254,0,'Allow Approve PR','',0,'\\40717','\\407',0,'0',0," . $params['levelid'] . "),
        (2272,1,'Allow View All transaction PR','',0,'\\40718','\\407',0,'0',0," . $params['levelid'] . "),
        (3602,1,'Allow Void Button','',0,'\\40719','\\407',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PR','/module/construction/rq','Purchase Requisition','fa fa-list sub_menu_ico',618," . $params['levelid'] . ")";
    } //end function

    public function jr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' .
            $qry = "(2427,0,'Job Request','',0,'\\2311','$parent',0,'0',0," . $params['levelid'] . "),
        (2428,0,'Allow View Transaction JR','JR',0,'\\231101','\\2311',0,'0',0," . $params['levelid'] . "),
        (2429,0,'Allow Click Edit Button JR','',0,'\\231102','\\2311',0,'0',0," . $params['levelid'] . "),
        (2430,0,'Allow Click New Button JR','',0,'\\231103','\\2311',0,'0',0," . $params['levelid'] . "),
        (2431,0,'Allow Click Save Button JR','',0,'\\231104','\\2311',0,'0',0," . $params['levelid'] . "),
        (2433,0,'Allow Click Delete Button JR','',0,'\\231106','\\2311',0,'0',0," . $params['levelid'] . "),
        (2434,0,'Allow Click Print Button JR','',0,'\\231107','\\2311',0,'0',0," . $params['levelid'] . "),
        (2435,0,'Allow Click Lock Button JR','',0,'\\231108','\\2311',0,'0',0," . $params['levelid'] . "),
        (2436,0,'Allow Click UnLock Button JR','',0,'\\231109','\\2311',0,'0',0," . $params['levelid'] . "),
        (2437,0,'Allow Click Post Button JR','',0,'\\231110','\\2311',0,'0',0," . $params['levelid'] . "),
        (2438,0,'Allow Click UnPost Button JR','',0,'\\231111','\\2311',0,'0',0," . $params['levelid'] . "),
        (2439,0,'Allow Change Amount JR','',0,'\\231113','\\2311',0,'0',0," . $params['levelid'] . "),
        (2440,1,'Allow Click Add Item JR','',0,'\\231114','\\2311',0,'0',0," . $params['levelid'] . "),
        (2441,1,'Allow Click Edit Item JR','',0,'\\231115','\\2311',0,'0',0," . $params['levelid'] . "),
        (2442,1,'Allow Click Delete Item JR','',0,'\\231116','\\2311',0,'0',0," . $params['levelid'] . "),
        (2444,0,'Allow Approve JR','',0,'\\231117','\\2311',0,'0',0," . $params['levelid'] . "),
        (2445,1,'Allow View All transaction JR','',0,'\\231118','\\2311',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JR','/module/construction/jr','Job Request','fa fa-list sub_menu_ico',2427," . $params['levelid'] . ")";
    } //end function

    public function jo($params, $parent, $sort)
    {

        $folder = 'construction';
        $label = 'Job Order';
        switch ($params['companyid']) {
            case 43: // mighty
                $folder = 'mighty';
                $label = 'Job/Repair Order';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1811,0,'" . $label . "','',0,'\\2302','$parent',0,'0',0," . $params['levelid'] . "),
        (1812,0,'Allow View Transaction JO','JO',0,'\\230201','\\2302',0,'0',0," . $params['levelid'] . "),
        (1813,0,'Allow Click Edit Button JO','',0,'\\230202','\\2302',0,'0',0," . $params['levelid'] . "),
        (1814,0,'Allow Click New Button JO','',0,'\\230203','\\2302',0,'0',0," . $params['levelid'] . "),
        (1815,0,'Allow Click Save Button JO','',0,'\\230204','\\2302',0,'0',0," . $params['levelid'] . "),
        (1817,0,'Allow Click Delete Button JO','',0,'\\230206','\\2302',0,'0',0," . $params['levelid'] . "),
        (1818,0,'Allow Click Print Button JO','',0,'\\230207','\\2302',0,'0',0," . $params['levelid'] . "),
        (1819,0,'Allow Click Lock Button JO','',0,'\\230208','\\2302',0,'0',0," . $params['levelid'] . "),
        (1820,0,'Allow Click UnLock Button JO','',0,'\\230209','\\2302',0,'0',0," . $params['levelid'] . "),
        (1821,0,'Allow Click Post Button JO','',0,'\\230210','\\2302',0,'0',0," . $params['levelid'] . "),
        (1822,0,'Allow Click UnPost  Button JO','',0,'\\230211','\\2302',0,'0',0," . $params['levelid'] . "),
        (1823,1,'Allow Click Add Item JO','',0,'\\230212','\\2302',0,'0',0," . $params['levelid'] . "),
        (1824,1,'Allow Click Edit Item JO','',0,'\\230213','\\2302',0,'0',0," . $params['levelid'] . "),
        (1825,1,'Allow Click Delete Item JO','',0,'\\230214','\\2302',0,'0',0," . $params['levelid'] . "),        
        (3594,1,'Allow Void Button','',0,'\\230215','\\2302',0,'0',0," . $params['levelid'] . ")";


        $this->insertattribute($params, $qry);
        return "($sort,$p,'JO','/module/" . $folder . "/jo','" . $label . "','fa fa-tasks sub_menu_ico',1811," . $params['levelid'] . ")";
    } //end function


    public function jc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1826,0,'Job Completion','',0,'\\2303','$parent',0,'0',0," . $params['levelid'] . "),
        (1827,0,'Allow View Transaction JC','JC',0,'\\230301','\\2303',0,'0',0," . $params['levelid'] . "),
        (1828,0,'Allow Click Edit Button JC','',0,'\\230302','\\2303',0,'0',0," . $params['levelid'] . "),
        (1829,0,'Allow Click New Button JC','',0,'\\230303','\\2303',0,'0',0," . $params['levelid'] . "),
        (1830,0,'Allow Click Save Button JC','',0,'\\230304','\\2303',0,'0',0," . $params['levelid'] . "),
        (1832,0,'Allow Click Delete Button JC','',0,'\\230306','\\2303',0,'0',0," . $params['levelid'] . "),
        (1833,0,'Allow Click Print Button JC','',0,'\\230307','\\2303',0,'0',0," . $params['levelid'] . "),
        (1834,0,'Allow Click Lock Button JC','',0,'\\230308','\\2303',0,'0',0," . $params['levelid'] . "),
        (1835,0,'Allow Click UnLock Button JC','',0,'\\230309','\\2303',0,'0',0," . $params['levelid'] . "),
        (1836,0,'Allow Click Post Button JC','',0,'\\230310','\\2303',0,'0',0," . $params['levelid'] . "),
        (1837,0,'Allow Click UnPost  Button JC','',0,'\\230311','\\2303',0,'0',0," . $params['levelid'] . "),
        (1838,1,'Allow Click Add Item JC','',0,'\\230312','\\2303',0,'0',0," . $params['levelid'] . "),
        (1839,1,'Allow Click Edit Item JC','',0,'\\230313','\\2303',0,'0',0," . $params['levelid'] . "),
        (1840,1,'Allow Click Delete Item JC','',0,'\\230314','\\2303',0,'0',0," . $params['levelid'] . "),
        (1841,0,'Allow View Transaction accounting JC','',0,'\\230315','\\2303',0,'0',0," . $params['levelid'] . "),
        (4896,0,'Allow View All Transaction JC','',0,'\\230316','\\2303',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JC','/module/construction/jc','Job Completion','fa fa-check-double sub_menu_ico',1826," . $params['levelid'] . ")";
    } //end function

    public function mr($params, $parent, $sort)
    {
        $systemtype = $this->companysetup->getsystemtype($params);
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2288,0,'Material Request','',0,'\\2306','$parent',0,'0',0," . $params['levelid'] . "),
        (2289,0,'Allow View Transaction MR','MR',0,'\\230601','\\2306',0,'0',0," . $params['levelid'] . "),
        (2290,0,'Allow Click Edit Button MR','MR',0,'\\230602','\\2306',0,'0',0," . $params['levelid'] . "),
        (2291,0,'Allow Click New  Button MR','MI',0,'\\230603','\\2306',0,'0',0," . $params['levelid'] . "),
        (2292,0,'Allow Click Save Button MR','MR',0,'\\230604','\\2306',0,'0',0," . $params['levelid'] . "),
        (2294,0,'Allow Click Delete Button MR','MR',0,'\\230606','\\2306',0,'0',0," . $params['levelid'] . "),
        (2295,0,'Allow Click Print Button MR','MR',0,'\\230607','\\2306',0,'0',0," . $params['levelid'] . "),
        (2296,0,'Allow Click Lock Button MR','MR',0,'\\230608','\\2306',0,'0',0," . $params['levelid'] . "),
        (2297,0,'Allow Click UnLock Button MR','MR',0,'\\230609','\\2306',0,'0',0," . $params['levelid'] . "),
        (2298,0,'Allow Click Post Button MR','MR',0,'\\230610','\\2306',0,'0',0," . $params['levelid'] . "),
        (2299,0,'Allow Click UnPost  Button MR','MR',0,'\\230611','\\2306',0,'0',0," . $params['levelid'] . "),
        (2300,1,'Allow Click Add Item MR','',0,'\\230613','\\2306',0,'0',0," . $params['levelid'] . "),
        (2301,1,'Allow Click Edit Item MR','',0,'\\230614','\\2306',0,'0',0," . $params['levelid'] . "),
        (2302,1,'Allow Click Delete Item MR','',0,'\\230615','\\2306',0,'0',0," . $params['levelid'] . ")";
        $folder = 'construction';
        if ($systemtype == 'REALESTATE') {
            $folder = 'realestate';
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'MR','/module/" . $folder . "/mr','Material Request','fa fa-tasks sub_menu_ico',2288," . $params['levelid'] . ")";
    } //end function

    public function wc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2303,0,'Work Accomplishment','',0,'\\2310','$parent',0,'0',0," . $params['levelid'] . "),
        (2304,0,'Allow View Transaction WC','WC',0,'\\231001','\\2310',0,'0',0," . $params['levelid'] . "),
        (2305,0,'Allow Click Edit Button WC','',0,'\\231002','\\2310',0,'0',0," . $params['levelid'] . "),
        (2306,0,'Allow Click New Button WC','',0,'\\231003','\\2310',0,'0',0," . $params['levelid'] . "),
        (2307,0,'Allow Click Save Button WC','',0,'\\231004','\\2310',0,'0',0," . $params['levelid'] . "),
        (2309,0,'Allow Click Delete Button WC','',0,'\\231006','\\2310',0,'0',0," . $params['levelid'] . "),
        (2310,0,'Allow Click Print Button WC','',0,'\\231007','\\2310',0,'0',0," . $params['levelid'] . "),
        (2311,0,'Allow Click Lock Button WC','',0,'\\231008','\\2310',0,'0',0," . $params['levelid'] . "),
        (2312,0,'Allow Click UnLock Button WC','',0,'\\231009','\\2310',0,'0',0," . $params['levelid'] . "),
        (2313,0,'Allow Click Post Button WC','',0,'\\231010','\\2310',0,'0',0," . $params['levelid'] . "),
        (2314,0,'Allow Click UnPost  Button WC','',0,'\\231011','\\2310',0,'0',0," . $params['levelid'] . "),
        (2315,1,'Allow Click Add Item WC','',0,'\\231012','\\2310',0,'0',0," . $params['levelid'] . "),
        (2316,1,'Allow Click Edit Item WC','',0,'\\231013','\\2310',0,'0',0," . $params['levelid'] . "),
        (2317,1,'Allow Click Delete Item WC','',0,'\\231014','\\2310',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'WC','/module/construction/wc','Work Accomplishment','fa fa-tasks sub_menu_ico',2303," . $params['levelid'] . ")";
    } //end function


    public function mi($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Material Issuance';
        switch ($params['companyid']) {
            case 50: //unitech
                $label = 'Raw Material Issuance';
                break;
        }
        $qry = "(768,0,'" . $label . "','',0,'\\2307','$parent',0,'0',0," . $params['levelid'] . "),
        (769,0,'Allow View Transaction MI','MI',0,'\\230701','\\2307',0,'0',0," . $params['levelid'] . "),
        (770,0,'Allow Click Edit Button MI','MI',0,'\\230702','\\2307',0,'0',0," . $params['levelid'] . "),
        (771,0,'Allow Click New  Button MI','MI',0,'\\230703','\\2307',0,'0',0," . $params['levelid'] . "),
        (772,0,'Allow Click Save Button MI','MI',0,'\\230704','\\2307',0,'0',0," . $params['levelid'] . "),
        (774,0,'Allow Click Delete Button MI','MI',0,'\\230706','\\2307',0,'0',0," . $params['levelid'] . "),
        (775,0,'Allow Click Print Button MI','MI',0,'\\230707','\\2307',0,'0',0," . $params['levelid'] . "),
        (776,0,'Allow Click Lock Button MI','MI',0,'\\230708','\\2307',0,'0',0," . $params['levelid'] . "),
        (777,0,'Allow Click UnLock Button MI','MI',0,'\\230709','\\2307',0,'0',0," . $params['levelid'] . "),
        (778,0,'Allow Click Post Button MI','MI',0,'\\230710','\\2307',0,'0',0," . $params['levelid'] . "),
        (779,0,'Allow Click UnPost  Button MI','MI',0,'\\230711','\\2307',0,'0',0," . $params['levelid'] . "),
        (783,0,'Allow View Transaction Accounting MI','MI',0,'\\230712','\\2307',0,'0',0," . $params['levelid'] . "),
        (2057,1,'Allow Click Add Item MI','',0,'\\230713','\\2307',0,'0',0," . $params['levelid'] . "),
        (2058,1,'Allow Click Edit Item MI','',0,'\\230714','\\2307',0,'0',0," . $params['levelid'] . "),
        (2059,1,'Allow Click Delete Item MI','',0,'\\230715','\\2307',0,'0',0," . $params['levelid'] . ")";

        $systype = $this->companysetup->getsystemtype($params);
        if ($systype == 'CAIMS') {
            $qry = $qry . ",(2234,1,'Allow View All transaction MI','',0,'\\230716','\\2307',0,'0',0," . $params['levelid'] . ")";
        }

        switch ($params['companyid']) {
            case 43: //mighty
                $qry .= ", (4490,1,'Allow Access Tripping Tab','',0,'\\230716','\\2307',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4491,1,'Allow Access Dispatch Tab','',0,'\\230717','\\2307',0,'0',0," . $params['levelid'] . ")";
                $qry .= ", (4495,1,'Allow Trip Approved','',0,'\\230718','\\2307',0,'0',0," . $params['levelid'] . ")";
                break;
        }

        $this->insertattribute($params, $qry);

        $folder = 'construction';
        if ($systype == 'REALESTATE') {
            $folder = 'realestate';
        } else {
            switch ($params['companyid']) {
                case 50: // unitech
                    $folder = 'unitechindustry';
                    break;
                case 19: // housegem
                    $folder = 'm1f0e3dad99908345f7439f8ffabdffc4';
                    break;
            }
        }

        return "($sort,$p,'MI','/module/" . $folder . "/mi','" . $label . "','fa fa-people-carry sub_menu_ico',768," . $params['levelid'] . ")";
    } //end function

    public function mt($params, $parent, $sort)
    {
        $systemtype = $this->companysetup->getsystemtype($params);
        $label = 'Stock Transfer';
        if ($this->companysetup->isconstruction($params)) {
            $label = 'Material Transfer';
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2040,0,'" . $label . "','',0,'\\2305','$parent',0,'0',0," . $params['levelid'] . "),
        (2041,0,'Allow View Transaction TS','TS',0,'\\230501','\\2305',0,'0',0," . $params['levelid'] . "),
        (2042,0,'Allow Click Edit Button  TS','',0,'\\230502','\\2305',0,'0',0," . $params['levelid'] . "),
        (2043,0,'Allow Click New Button TS','',0,'\\230503','\\2305',0,'0',0," . $params['levelid'] . "),
        (2044,0,'Allow Click Save Button TS','',0,'\\230504','\\2305',0,'0',0," . $params['levelid'] . "),
        (2046,0,'Allow Click Delete Button TS','',0,'\\230506','\\2305',0,'0',0," . $params['levelid'] . "),
        (2047,0,'Allow Click Print Button TS','',0,'\\230507','\\2305',0,'0',0," . $params['levelid'] . "),
        (2048,0,'Allow Click Lock Button TS','',0,'\\230508','\\2305',0,'0',0," . $params['levelid'] . "),
        (2049,0,'Allow Click UnLock Button TS','',0,'\\230509','\\2305',0,'0',0," . $params['levelid'] . "),
        (2050,0,'Allow Click Post Button TS','',0,'\\230510','\\2305',0,'0',0," . $params['levelid'] . "),
        (2051,0,'Allow Click UnPost Button TS','',0,'\\230511','\\2305',0,'0',0," . $params['levelid'] . "),
        (2052,1,'Allow Click Add Item TS','',0,'\\230512','\\2305',0,'0',0," . $params['levelid'] . "),
        (2053,1,'Allow Click Edit Item TS','',0,'\\230513','\\2305',0,'0',0," . $params['levelid'] . "),
        (2054,1,'Allow Click Delete Item TS','',0,'\\230514','\\2305',0,'0',0," . $params['levelid'] . "),
        (2055,1,'Allow Change Amount TS','',0,'\\230515','\\2305',0,'0',0," . $params['levelid'] . "),
        (2056,0,'Allow View Transaction accounting TS','',0,'\\230516','\\2305',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        $folder = 'construction';
        if ($this->companysetup->isconstruction($params)) {
            if ($systemtype == 'REALESTATE') {
                $folder = 'realestate';
            }
        }
        return "($sort,$p,'MT','/module/" . $folder . "/mt','" . $label . "','fa fa-dolly-flatbed sub_menu_ico',2040," . $params['levelid'] . ")";
    } //end function

    public function pb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1842,0,'Progress Billing','',0,'\\2304','$parent',0,'0',0," . $params['levelid'] . "),
        (1843,0,'Allow View Transaction PB','PB',0,'\\230401','\\2304',0,'0',0," . $params['levelid'] . "),
        (1844,0,'Allow Click Edit Button  PB','',0,'\\230402','\\2304',0,'0',0," . $params['levelid'] . "),
        (1845,0,'Allow Click New Button PB','',0,'\\230403','\\2304',0,'0',0," . $params['levelid'] . "),
        (1846,0,'Allow Click Save Button PB','',0,'\\230404','\\2304',0,'0',0," . $params['levelid'] . "),
        (1848,0,'Allow Click Delete Button PB','',0,'\\230406','\\2304',0,'0',0," . $params['levelid'] . "),
        (1849,0,'Allow Click Print Button PB','',0,'\\230407','\\2304',0,'0',0," . $params['levelid'] . "),
        (1850,0,'Allow Click Lock Button PB','',0,'\\230408','\\2304',0,'0',0," . $params['levelid'] . "),
        (1851,0,'Allow Click UnLock Button PB','',0,'\\230409','\\2304',0,'0',0," . $params['levelid'] . "),
        (1852,0,'Allow Click Post Button PB','',0,'\\230410','\\2304',0,'0',0," . $params['levelid'] . "),
        (1853,0,'Allow Click UnPost Button PB','',0,'\\230411','\\2304',0,'0',0," . $params['levelid'] . "),
        (1854,0,'Allow Click Add Account PB','',0,'\\230412','\\2304',0,'0',0," . $params['levelid'] . "),
        (1855,0,'Allow Click Edit Account PB','',0,'\\230413','\\2304',0,'0',0," . $params['levelid'] . "),
        (1856,0,'Allow Click Delete Account PB','',0,'\\230414','\\2304',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PB','/module/construction/pb','Progress Billing','fa fa-file-invoice-dollar sub_menu_ico',1842," . $params['levelid'] . ")";
    } //end function

    public function ba($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2747,0,'Billing Accomplishment','',0,'\\2313','$parent',0,'0',0," . $params['levelid'] . "),
        (2748,0,'Allow View Transaction BA','BS',0,'\\231301','\\2313',0,'0',0," . $params['levelid'] . "),
        (2749,0,'Allow Click Edit Button BA','',0,'\\231302','\\2313',0,'0',0," . $params['levelid'] . "),
        (2750,0,'Allow Click New Button BA','',0,'\\231303','\\2313',0,'0',0," . $params['levelid'] . "),
        (2751,0,'Allow Click Save Button BA','',0,'\\231304','\\2313',0,'0',0," . $params['levelid'] . "),
        (2753,0,'Allow Click Delete Button BA','',0,'\\231306','\\2313',0,'0',0," . $params['levelid'] . "),
        (2754,0,'Allow Click Print Button BA','',0,'\\231307','\\2313',0,'0',0," . $params['levelid'] . "),
        (2755,0,'Allow Click Lock Button BA','',0,'\\231308','\\2313',0,'0',0," . $params['levelid'] . "),
        (2756,0,'Allow Click UnLock Button BA','',0,'\\231309','\\2313',0,'0',0," . $params['levelid'] . "),
        (2757,0,'Allow Click Post Button BA','',0,'\\231310','\\2313',0,'0',0," . $params['levelid'] . "),
        (2758,0,'Allow Click UnPost  Button BA','',0,'\\231311','\\2313',0,'0',0," . $params['levelid'] . "),
        (2759,1,'Allow Click Add Item BA','',0,'\\231312','\\2313',0,'0',0," . $params['levelid'] . "),
        (2760,1,'Allow Click Edit Item BA','',0,'\\231313','\\2313',0,'0',0," . $params['levelid'] . "),
        (2761,1,'Allow Click Delete Item BA','',0,'\\231314','\\2313',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BA','/module/construction/ba','Billing Accomplishment','fa fa-user sub_menu_ico',2747," . $params['levelid'] . ")";
    } //end function

    public function parentannouncement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(385,0,'ANNOUNCEMENT','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'ANNOUNCEMENT',$sort,'fa fa-bullhorn',',,'," . $params['levelid'] . ")";
    } //end function


    public function notice($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1362,0,'NOTICE','',0,'\\13001','$parent',0,'0',0," . $params['levelid'] . "),
        (5638,0,'Allow View All','',0,'\\13002','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'NOTICE','/tableentries/announcemententry/entrynotice','Notice','fa fa-calendar-day sub_menu_ico',1362," . $params['levelid'] . ")";
    } //end function

    public function event($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1363,0,'EVENT','',0,'\\13002','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EVENT','/tableentries/announcemententry/entryevent','Event','fa fa-calendar-day sub_menu_ico',1363," . $params['levelid'] . ")";
    } //end function

    public function holidayannouncement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1364,0,'HOLIDAY','',0,'\\13003','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HOLIDAY','/tableentries/announcemententry/entryholiday','Holiday','fa fa-calendar-day sub_menu_ico',1364," . $params['levelid'] . ")";
    } //end function

    public function parenttransactionutilities($params, $parent, $sort)
    {
        $systemtype = $this->companysetup->getsystemtype($params);

        $p = $parent;
        $parent = '\\' . $parent;
        switch ($systemtype) {
            case 'EAPPLICATION':
                $qry = "(360,0,'TRANSACTION UTILITIES','',0,'$parent','\\',0,'0',0," . $params['levelid'] . "),                
                (1730,1,'View Attach Documents','',0,'\\810','$parent',0,'0',0," . $params['levelid'] . "),
                (1731,1,'Attach Documents','',0,'\\81001','\\810',0,'0',0," . $params['levelid'] . "),
                (1732,1,'Download Documents','',0,'\\81002','\\810',0,'0',0," . $params['levelid'] . "),
                (1733,1,'Delete Documents','',0,'\\81003','\\810',0,'0',0," . $params['levelid'] . "),
                (4077,0,'Allow View All Application/Contracts','',0,'\\822','$parent',0,'0',0," . $params['levelid'] . "),
                (1729,1,'Allow Override Plan Limit','',0,'\\811','$parent',0,'0',0," . $params['levelid'] . "),
                (4098,0,'Allow to search & view transactions','',0,'\\823','$parent',0,'0',0," . $params['levelid'] . ")";
                break;
            default:
                $qry = "(360,0,'TRANSACTION UTILITIES','',0,'$parent','\\',0,'0',0," . $params['levelid'] . "),
                (368,0,'Allow View Transaction Cost','',0,'\\809','$parent',0,'0',0," . $params['levelid'] . "),
                (1729,1,'Allow Override Credit Limit','',0,'\\811','$parent',0,'0',0," . $params['levelid'] . "),
                (1730,1,'View Attach Documents','',0,'\\810','$parent',0,'0',0," . $params['levelid'] . "),
                (1731,1,'Attach Documents','',0,'\\81001','\\810',0,'0',0," . $params['levelid'] . "),
                (1732,1,'Download Documents','',0,'\\81002','\\810',0,'0',0," . $params['levelid'] . "),
                (1733,1,'Delete Documents','',0,'\\81003','\\810',0,'0',0," . $params['levelid'] . "),
                (1736,0,'Allow Override Below Cost','',0,'\\813','$parent',0,'0',0," . $params['levelid'] . "),
                (3687,0,'Allow View To Do','',0,'\\814','$parent',0,'0',0," . $params['levelid'] . "),
                (3723,0,'Restrict IP','',0,'\\818','$parent',0,'0',0," . $params['levelid'] . ")";

                switch ($params['companyid']) {
                    case 40: //cdo
                        $qry .= ",(4850,1,'Not allow to Edit Transaction Date Sales','',0,'\\824','$parent',0,'0',0," . $params['levelid'] . ")";
                        $qry .= ",(4851,1,'Not allow to Edit Transaction Date Purchase','',0,'\\825','$parent',0,'0',0," . $params['levelid'] . ")";
                        $qry .= ",(4852,1,'Not allow to Edit Transaction Date Inventory','',0,'\\826','$parent',0,'0',0," . $params['levelid'] . ")";
                        $qry .= ",(4853,1,'Not allow to Edit Transaction Date Accounting','',0,'\\827','$parent',0,'0',0," . $params['levelid'] . ")";
                        break;
                    case 58: //cdo hrispayroll
                        $qry .= ",(5207,1,'Allow view Questionnaire','',0,'\\829','$parent',0,'0',0," . $params['levelid'] . ")";
                        break;
                    case 21: //kinggeorge
                        $qry .= ",(5451,1,'Allow Show Item Balance','',0,'\\830','$parent',0,'0',0," . $params['levelid'] . ")";
                        break;
                }
                break;
        }


        if ($this->companysetup->getserial($params)) {
            $qry .= ",(2999,0,'Allow Access enter Serial','',0,'\\815','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        if ($params['companyid'] == 6) { //mitsukoshi
            $qry .= ",
            (2631,1,'Allow View SJ SRP','',0,'\\81004','$parent',0,'0',0," . $params['levelid'] . "),
            (2632,1,'Allow View SJ Lowest Price','',0,'\\81005','$parent',0,'0',0," . $params['levelid'] . "),
            (2633,1,'Allow View SJ Lower Price','',0,'\\81006','$parent',0,'0',0," . $params['levelid'] . "),
            (2634,1,'Allow View SJ Low Price','',0,'\\81007','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        if ($this->companysetup->getmultibranch($params)) {
            $qry .= ",(4165,1,'Allow View All Branches (Reports)','',0,'\\81008','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        $this->insertattribute($params, $qry);

        $modules = '';
        switch ($systemtype) {
            case 'VSCHED':
            case 'ATI':
                $modules = "',docprefix,audittrail,audittrail' ";
                break;
            case 'EAPPLICATION':
                $modules = "',docprefix,terms,audittrail,'";
                break;
            default:
                $modules = "',docprefix,terms,changeitem,audittrail,notification,othersettings,ipsetup,coagrouping,updatestd'";
                break;
        }
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'TRANSACTION UTILITIES',$sort,'fa fa-cogs'," . $modules . "," . $params['levelid'] . ")";
    } //end function

    public function terms($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(598,0,'Terms','',0,'\\802','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'terms','/tableentries/tableentry/entryterms','Manage Terms','fa fa-calendar-check sub_menu_ico',598," . $params['levelid'] . ")";
    } //end function

    public function updatestd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3743,0,'Update SGD Rates','',0,'\\820','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'updatestd','/headtable/othersettings/updatestd','Update SGD Rates','fas fa-funnel-dollar sub_menu_ico',3743," . $params['levelid'] . ")";
    } //end function

    public function ipsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3721,0,'IP Setup','',0,'\\817','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ipsetup','/tableentries/tableentry/ipsetup','IP Setup','fa fa-calendar-check sub_menu_ico',3721," . $params['levelid'] . ")";
    } //end function

    public function coagrouping($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3612,0,'COA Grouping','',0,'\\819','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'coagrouping','/tableentries/tableentry/coagrouping','COA Grouping','fa fa-calendar-check sub_menu_ico',3612," . $params['levelid'] . ")";
    } //end function


    public function prefix($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(599,0,'Document Prefix','',0,'\\803','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'prefix','/tableentries/tableentry/entryprefix','Manage Prefixes','fab fa-autoprefixer sub_menu_ico',599," . $params['levelid'] . ")";
    } //end function

    public function ewtsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(854,1,'EWT Setup','*129',0,'\\812','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ewtsetup','/tableentries/tableentry/entryewt','EWT Setup','fab fa-elementor sub_menu_ico',854," . $params['levelid'] . ")";
    } //end function

    public function audittrail($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(633,0,'Allow View Audit Trail','',0,'\\805','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'audittrail','/headtable/customformlisting/audittrail','Audit Trail','fa fa-user-shield sub_menu_ico ',633," . $params['levelid'] . ")";
    } //end function

    public function unposted_transaction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(652,0,'Unposted Transactions','',0,'\\806','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'unposted_transaction','/tableentries/tableentry/entryunposted_transaction','Unposted Transaction','fa fa-unlink  sub_menu_ico ',652," . $params['levelid'] . ")";
    } //end function

    public function forex($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1051,0,'Forex','',0,'\\807','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'forex','/tableentries/tableentry/entryforex','Forex','fas fa-funnel-dollar sub_menu_ico',1051," . $params['levelid'] . ")";
    } //end function


    public function executionlog($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4579,0,'Execution Logs','',0,'\\808','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'executionlog','/tableentries/tableentry/executionlog','Execution Logs','fas fa-check-double sub_menu_ico',4579," . $params['levelid'] . ")";
    } //end function

    public function changeitem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(632,0,'Change Item','',0,'\\804','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'changeitem','/tableentries/tableentry/entrychangeitem','Change Item','fa fa-undo sub_menu_ico',632," . $params['levelid'] . ")";
    } //end function


    public function parentdashboard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $companyid = $params['companyid'];
        $lblsalesyrly = "Sales Yearly Graph Dashboard";

        $systype = $this->companysetup->getsystemtype($params);
        if ($companyid == 47) { //kitchenstar
            $lblsalesyrly = "Month to Date Sales Dashboard";
        }

        $qry = "";
        $acctgdashboard = false;
        if (strstr($systype, "AIMS")) $acctgdashboard = true;
        if (strstr($systype, "AMS")) $acctgdashboard = true;
        if (strstr($systype, "ATI")) $acctgdashboard = true;
        if (strstr($systype, "ALL")) $acctgdashboard = true;

        $payable = "(879,1,'Account Payable Dashboard','',0,'\\10104','$parent',0,'0',0," . $params['levelid'] . "),";
        $receivable = "(880,1,'Account Receivable Dashboard','',0,'\\10105','$parent',0,'0',0," . $params['levelid'] . "),";
        $rr = "(876,1,'RR Transaction Dashboard','',0,'\\10101','$parent',0,'0',0," . $params['levelid'] . "),";
        $dm = "(877,1,'DM Transaction Dashboard','',0,'\\10102','$parent',0,'0',0," . $params['levelid'] . "),";

        if (strstr($systype, "MIS")) {
            $payable = "";
            $receivable = "";
        }

        if (strstr($systype, "AMS")) {
            $rr = "";
            $dm = "";
        }

        if ($acctgdashboard) {
            $qry = "(1053,0,'DASHBOARD','',0,'$parent','\\',0,'0',0," . $params['levelid'] . "),
                $rr
                $dm                
                (878,1,'CV Transaction Dashboard','',0,'\\10103','$parent',0,'0',0," . $params['levelid'] . "),
                (5031,1,'CR Transaction Dashboard','',0,'\\10106','$parent',0,'0',0," . $params['levelid'] . "),
                $payable 
                $receivable
                (1734,1,'Purchase Yearly Graph Dashboard','',0,'\\10126','$parent',0,'0',0," . $params['levelid'] . "),
                (1735,1,'" . $lblsalesyrly . "','',0,'\\10107','$parent',0,'0',0," . $params['levelid'] . "),
                (3808,1,'Pending Canvass Sheet Dashboard','',0,'\\10109','$parent',0,'0',0," . $params['levelid'] . ")                                
                ";
        }


        if ($systype == 'LENDING') {
            $qry = "(1053,0,'DASHBOARD','',0,'$parent','\\',0,'0',0," . $params['levelid'] . "),
                (878,1,'CV Transaction Dashboard','',0,'\\10103','$parent',0,'0',0," . $params['levelid'] . "),
                
                (5090,1,'Collection Yearly Graph Dashboard','',0,'\\10106','$parent',0,'0',0," . $params['levelid'] . "),   

                (5031,1,'CR Transaction Dashboard','',0,'\\10126','$parent',0,'0',0," . $params['levelid'] . "),
                (879,1,'Account Payable Dashboard','',0,'\\10104','$parent',0,'0',0," . $params['levelid'] . "),
                (880,1,'Account Receivable Dashboard','',0,'\\10105','$parent',0,'0',0," . $params['levelid'] . "),
                (5089,1,'PDC Dashboard','',0,'\\10127','$parent',0,'0',0," . $params['levelid'] . "),
                (5346,1,'Loan Release Dashboard','',0,'\\10129','$parent',0,'0',0," . $params['levelid'] . "),
                (5345,1,'For Loan Approval Dashboard','',0,'\\10128','$parent',0,'0',0," . $params['levelid'] . "),
                (3894,1,'View Report Dashboard','',0,'\\10111','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        if ($companyid == 10) { //afti
            $qry = $qry . ",(3807,1,'Month To Date Sales Dashboard','',0,'\\10108','$parent',0,'0',0," . $params['levelid'] . "),
                (4011,1,'Sales per Branch Dashboard','',0,'\\10112','$parent',0,'0',0," . $params['levelid'] . "),
                (4012,1,'Monthly Sales Dashboard','',0,'\\10113','$parent',0,'0',0," . $params['levelid'] . "),
                (3863,1,'Year To Date Sales Dashboard','',0,'\\10110','$parent',0,'0',0," . $params['levelid'] . "),
                (3894,1,'View Report Dashboard','',0,'\\10111','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        if ($systype == 'EAPPLICATION') {
            $qry = "(1053,0,'DASHBOARD','',0,'$parent','\\',0,'0',0," . $params['levelid'] . "),                
                (880,1,'Account Receivable Dashboard','',0,'\\10105','$parent',0,'0',0," . $params['levelid'] . "),
                (1735,1,'Sales Yearly Graph Dashboard','',0,'\\10107','$parent',0,'0',0," . $params['levelid'] . "),
                (4100,1,'Notice','',0,'\\10114','$parent',0,'0',0," . $params['levelid'] . "),
                (4101,1,'Calendar','',0,'\\10115','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        if ($companyid == 40) { //cdo
            $qry = $qry . ", (4455,1,'Incoming Transfers Dashboard','',0,'\\10118','$parent',0,'0',0," . $params['levelid'] . "),
          (4456,1,'Request to Transfers Dashboard','',0,'\\10116','$parent',0,'0',0," . $params['levelid'] . "),
          (4457,1,'Request to PO Dashboard','',0,'\\10117','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        if ($companyid == 47) { //kstar
            $qry = $qry . ", 
             (4842,1,'Outgoing Deliveries Dashboard','',0,'\\10119','$parent',0,'0',0," . $params['levelid'] . "),
             (4877,1,'SO Transaction Dashboard','',0,'\\10120','$parent',0,'0',0," . $params['levelid'] . "),
             (4857,1,'SJ Transaction Dashboard','',0,'\\10121','$parent',0,'0',0," . $params['levelid'] . "),
             (4858,1,'PO Transaction Dashboard','',0,'\\10122','$parent',0,'0',0," . $params['levelid'] . "),
             (4859,1,'Total Sales Dashboard','',0,'\\10123','$parent',0,'0',0," . $params['levelid'] . "),
             (4894,1,'AJ Transaction Dashboard','',0,'\\10124','$parent',0,'0',0," . $params['levelid'] . "),
             (4895,1,'TS Transaction Dashboard','',0,'\\10125','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        if ($companyid == 56) { //homeworks
            $qry = $qry . ", (4455,1,'Incoming Transfers Dashboard','',0,'\\10118','$parent',0,'0',0," . $params['levelid'] . ")";
        }

        if ($companyid == 57) { //cdo financing
            $qry = "(1053,0,'DASHBOARD','',0,'$parent','\\',0,'0',0," . $params['levelid'] . "),
            (5089,1,'PDC Dashboard','',0,'\\10103','$parent',0,'0',0," . $params['levelid'] . "),
            (5090,1,'Collection Yearly Graph Dashboard','',0,'\\10106','$parent',0,'0',0," . $params['levelid'] . ")                            
            ";
        }

        if ($qry != "") {
            $this->insertattribute($params, $qry);
        }
        return "";
    } //end function




    public function parentbranch($params, $parent, $sort)
    {
        if ($params['companyid'] == 24) return ""; //goodfound

        if ($this->companysetup->getmultibranch($params)) {
            $p = $parent;
            $parent = '\\' . $parent;
            $qry = "(796,0,'BRANCH','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
            $this->insertattribute($params, $qry);
            return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'BRANCH',$sort,'fa fa-home',',branch'," . $params['levelid'] . ")";
        } else {
            return "";
        }
    } //end function

    public function branch($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        if ($this->companysetup->getmultibranch($params)) {
            $qry = "(798,0,'Branch Masterfile','',0,'\\2103','$parent',0,'0',0," . $params['levelid'] . ")";
            $this->insertattribute($params, $qry);
            return "($sort,$p,'branch','/tableentries/tableentry/entrycenter','Branch Masterfile','fa fa-boxes sub_menu_ico',798," . $params['levelid'] . ")";
        } else {
            return "";
        }
    } //end function

    public function parentaccountutilities($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1700,0,'ACCOUNT UTILITIES','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        $modules = "',useraccess,branchaccess,projectaccess,rg' ";

        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'ACCOUNT UTILITIES',$sort,'fa fa-users-cog'," . $modules . "," . $params['levelid'] . ")";
    } //end function



    public function rg($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4968,0,'Company Rules and Guidelines','',0,'\\2105','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4969,0,'Allow View Transaction RG','',0,'\\210501','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4970,0,'Allow Click Edit Button RG','',0,'\\210502','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4971,0,'Allow Click New Button RG','',0,'\\210503','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4972,0,'Allow Click Save Button RG','',0,'\\210504','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4973,0,'Allow Click Delete Button RG','',0,'\\210505','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4974,0,'Allow Click Print Button RG','',0,'\\210506','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4975,0,'Allow Click Lock Button RG','',0,'\\210507','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4976,0,'Allow Click UnLock Button RG','',0,'\\210508','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4977,0,'Allow Click Post Button RG','',0,'\\210509','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4978,0,'Allow Click UnPost Button RG','',0,'\\210510','\\2105',0,'0',0," . $params['levelid'] . "),
        (4982,1,'Allow Click Add Document RG','',0,'\\210511','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4983,1,'Allow Click View Document RG','',0,'\\210512','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4984,1,'Allow Click Download Document RG','',0,'\\210513','\\2105',0,'0',0," . $params['levelid'] . ") ,
        (4985,1,'Allow Click Delete Document RG','',0,'\\210514','\\2105',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RG','/module/companyrules/rg','COMPANY RULES AND GUIDELINES','fa fa-list-ol sub_menu_ico',4968," . $params['levelid'] . ")";
    } //end function

    public function useraccess($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(362,0,'Manage useraccess','',0,'\\2101','$parent',0,'0',0," . $params['levelid'] . "),
        (2580,0,'Manage Masterfile User Accounts','',0,'\\2104','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'useraccess','/utilityuser/accutilities/useraccess','Manage useraccess','fa fa-users sub_menu_ico',362," . $params['levelid'] . ")";
    } //end function

    public function companyinfoaccess($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(798,0,'Company Information','',0,'\\2102','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'companyinfo','/tableentries/tableentry/entrycenter','Company Information','fa fa-sitemap sub_menu_ico',798," . $params['levelid'] . ")";
    }

    public function branchaccess($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(797,0,'Branch Access','',0,'\\2102','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'branchaccess','/utilitybranch/accutilities/branchaccess','Branch Access','fa fa-building sub_menu_ico',797," . $params['levelid'] . ")";
    } //end function

    public function projectaccess($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2162,0,'Project Access','',0,'\\2103','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'branchaccess','/headtable/construction/entryprojectaccess','Project Access','fa fa-building sub_menu_ico',2162," . $params['levelid'] . ")";
    } //end function

    public function othersettings($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;


        $qry = "(2221,0,'Other Settings','',0,'\\891','$parent',0,'0',0," . $params['levelid'] . "),
        (2222,0,'Allow Update System Lock Date','',0,'\\89101','\\891',0,'0',0," . $params['levelid'] . ")";

        if ($params['companyid'] == 16) { //ati
            $qry .= ", (4581,0,'Allow Update Surcharge','',0,'\\89102','\\891',0,'0',0," . $params['levelid'] . ")";
        };

        if ($params['companyid'] == 21) { //kinggeorge
            $qry .= ", (5209,0,'Allow Edit Add SJ Date','',0,'\\89103','\\891',0,'0',0," . $params['levelid'] . ")";
        };

        $this->insertattribute($params, $qry);
        return "($sort,$p,'othersettings','/headtable/othersettings/othersettings','Other Settings','fa fa-cog sub_menu_ico',2221," . $params['levelid'] . ")";
    } //end function

    public function gradeutility($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2727, 0, 'Grade Utility', '', 0, '\\893', '$parent', 0, '0', 0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'gradeutility','/tableentries/enrollmentgradeentry/en_gradeutility','Grade Utility','fa fa-sticky-note sub_menu_ico',2727," . $params['levelid'] . ")";
    }

    public function uploadingutility($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2584,0,'Uploading Utility','',0,'\\892','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'uploadingutility','/headtable/othersettings/uploadingutility','Uploading Utility','fa fa-upload sub_menu_ico',2584," . $params['levelid'] . ")";
    } //end function       

    public function apiutility($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3813,0,'Downloading Utility','',0,'\\893','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'terms','/headtable/othersettings/downloadapi','Downloading Utility','fa fa-calendar-check sub_menu_ico',3813," . $params['levelid'] . ")";
    } //end function

    public function poterms($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2871,0,'PO Terms','',0,'\\894','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'poterms','/tableentries/tableentry/poterms','PO Terms','fa fa-upload sub_menu_ico',2871," . $params['levelid'] . ")";
    } //end function    

    public function billableitemssetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(138,0,'Billable Items Setup','',0,'\\814','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'billableitemssetup','/tableentries/mallsetup/billableitemssetup','Billable Items Setup','fa fa-coins sub_menu_ico',138," . $params['levelid'] . ")";
    } //end function 

    public function parentcrm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2549,0,'CRM','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'CRM',$sort,'fa fa-people-arrows',',crm'," . $params['levelid'] . ")";
    } //end function

    public function parentoperation($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2293,0,'OPERATION','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'OPERATION',$sort,'fa fa-solid fa-splotch',',operation'," . $params['levelid'] . ")";
    } //end function

    public function ld($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2551,0,'Lead','',0,'\\2901','$parent',0,'0',0," . $params['levelid'] . "),
        (2552,0,'Allow View Transaction LD','LD',0,'\\290101','\\2901',0,'0',0," . $params['levelid'] . "),
        (2553,0,'Allow Click Edit Button LD','',0,'\\290102','\\2901',0,'0',0," . $params['levelid'] . "),
        (2554,0,'Allow Click New Button LD','',0,'\\290103','\\2901',0,'0',0," . $params['levelid'] . "),
        (2555,0,'Allow Click Save Button LD','',0,'\\290104','\\2901',0,'0',0," . $params['levelid'] . "),
        (2557,0,'Allow Click Delete Button LD','',0,'\\290106','\\2901',0,'0',0," . $params['levelid'] . "),
        (2558,0,'Allow Click Print Button LD','',0,'\\290107','\\2901',0,'0',0," . $params['levelid'] . "),
        (2559,0,'Allow Click Lock Button LD','',0,'\\290108','\\2901',0,'0',0," . $params['levelid'] . "),
        (2560,0,'Allow Click UnLock Button LD','',0,'\\290109','\\2901',0,'0',0," . $params['levelid'] . "),
        (2561,0,'Allow Click Post Button LD','',0,'\\290111','\\2901',0,'0',0," . $params['levelid'] . "),
        (2562,0,'Allow Click UnPost Button LD','',0,'\\290112','\\2901',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'LD','/module/crm/ld','Lead','fa fa-tasks sub_menu_ico',2551," . $params['levelid'] . ")";
    } //end function

    public function op($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2563,0,'Sales Activity Module','',0,'\\2902','$parent',0,'0',0," . $params['levelid'] . "),
        (2564,0,'Allow View Transaction SA','OP',0,'\\290201','\\2902',0,'0',0," . $params['levelid'] . "),
        (2565,0,'Allow Click Edit Button SA','',0,'\\290202','\\2902',0,'0',0," . $params['levelid'] . "),
        (2566,0,'Allow Click New  Button SA','',0,'\\290203','\\2902',0,'0',0," . $params['levelid'] . "),
        (2567,0,'Allow Click Save Button SA','',0,'\\290204','\\2902',0,'0',0," . $params['levelid'] . "),
        (2569,0,'Allow Click Delete Button SA','',0,'\\290206','\\2902',0,'0',0," . $params['levelid'] . "),
        (2570,0,'Allow Click Print Button SA','',0,'\\290207','\\2902',0,'0',0," . $params['levelid'] . "),
        (2571,0,'Allow Click Lock Button SA','',0,'\\290208','\\2902',0,'0',0," . $params['levelid'] . "),
        (2572,0,'Allow Click UnLock Button SA','',0,'\\290209','\\2902',0,'0',0," . $params['levelid'] . "),
        (2573,0,'Allow Change Amount  SA','',0,'\\290210','\\2902',0,'0',0," . $params['levelid'] . "),
        (2575,0,'Allow Click Post Button SA','',0,'\\290212','\\2902',0,'0',0," . $params['levelid'] . "),
        (2576,0,'Allow Click UnPost  Button SA','',0,'\\290213','\\2902',0,'0',0," . $params['levelid'] . "),
        (2577,1,'Allow Click Add Item SA','',0,'\\290214','\\2902',0,'0',0," . $params['levelid'] . "),
        (2578,1,'Allow Click Edit Item SA','',0,'\\290215','\\2902',0,'0',0," . $params['levelid'] . "),
        (2579,1,'Allow Click Delete Item SA','',0,'\\290216','\\2902',0,'0',0," . $params['levelid'] . "),
        (2875,1,'Allow Create Profile','',0,'\\290217','\\2902',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'OP','/module/crm/op','Sales Activity','fa fa-people-arrows sub_menu_ico',2563," . $params['levelid'] . ")";
    } //end function


    public function salesgroup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2593,0,'Sales Group','',0,'\\2903','$parent',0,'0',0," . $params['levelid'] . "),
        (2843,0,'Allow Add','',0,'\\290301','\\2903',0,'0',0," . $params['levelid'] . "),
        (2844,0,'Allow Save All','',0,'\\290302','\\2903',0,'0',0," . $params['levelid'] . "),
        (2845,0,'Allow Print','',0,'\\290303','\\2903',0,'0',0," . $params['levelid'] . "),
        (2846,0,'Allow Save','',0,'\\290304','\\2903',0,'0',0," . $params['levelid'] . "),
        (2847,0,'Allow Delete','',0,'\\290305','\\2903',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SALESGROUP','/tableentries/crm/salesgroup','Sales Group','fa fa-sitemap sub_menu_ico',2593," . $params['levelid'] . ")";
    } //end function

    public function seminar($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2594,0,'Seminar','',0,'\\2904','$parent',0,'0',0," . $params['levelid'] . "),
        (2848,0,'Allow Add','',0,'\\290401','\\2904',0,'0',0," . $params['levelid'] . "),
        (2849,0,'Allow Save All','',0,'\\290402','\\2904',0,'0',0," . $params['levelid'] . "),
        (2850,0,'Allow Print','',0,'\\290403','\\2904',0,'0',0," . $params['levelid'] . "),
        (2851,0,'Allow Save','',0,'\\290404','\\2904',0,'0',0," . $params['levelid'] . "),
        (2852,0,'Allow Delete','',0,'\\290405','\\2904',0,'0',0," . $params['levelid'] . "),
        (3666,0,'Allow Editing of Marketing Remarks','',0,'\\290406','\\2904',0,'0',0," . $params['levelid'] . "),
        (3667,0,'Allow Editing of Sales Remarks','',0,'\\290407','\\2904',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SEMINAR','/tableentries/crm/seminar','Seminar','fa fa-sitemap sub_menu_ico',2594," . $params['levelid'] . ")";
    } //end function

    public function source($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2762,0,'Source','',0,'\\2906','$parent',0,'0',0," . $params['levelid'] . "),
        (2858,0,'Allow Add','',0,'\\290601','\\2906',0,'0',0," . $params['levelid'] . "),
        (2859,0,'Allow Save All','',0,'\\290602','\\2906',0,'0',0," . $params['levelid'] . "),
        (2860,0,'Allow Print','',0,'\\290603','\\2906',0,'0',0," . $params['levelid'] . "),
        (2861,0,'Allow Save','',0,'\\290604','\\2906',0,'0',0," . $params['levelid'] . "),
        (2862,0,'Allow Delete','',0,'\\290605','\\2906',0,'0',0," . $params['levelid'] . "),
        (3668,0,'Allow Editing of Marketing Remarks','',0,'\\290606','\\2906',0,'0',0," . $params['levelid'] . "),
        (3669,0,'Allow Editing of Sales Remarks','',0,'\\290607','\\2906',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SOURCE','/tableentries/crm/source','Source','fa fa-sitemap sub_menu_ico',2762," . $params['levelid'] . ")";
    } //end function

    public function exhibit($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2595,0,'Exhibit','',0,'\\2905','$parent',0,'0',0," . $params['levelid'] . "),
        (2853,0,'Allow Add','',0,'\\290501','\\2905',0,'0',0," . $params['levelid'] . "),
        (2854,0,'Allow Save All','',0,'\\290502','\\2905',0,'0',0," . $params['levelid'] . "),
        (2855,0,'Allow Print','',0,'\\290503','\\2905',0,'0',0," . $params['levelid'] . "),
        (2856,0,'Allow Save','',0,'\\290504','\\2905',0,'0',0," . $params['levelid'] . "),
        (2857,0,'Allow Delete','',0,'\\290505','\\2905',0,'0',0," . $params['levelid'] . "),
        (3670,0,'Allow Editing of Marketing Remarks','',0,'\\290506','\\2905',0,'0',0," . $params['levelid'] . "),
        (3671,0,'Allow Editing of Sales Remarks','',0,'\\290507','\\2905',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EXHIBIT','/tableentries/crm/exhibit','Exhibit','fa fa-sitemap sub_menu_ico',2595," . $params['levelid'] . ")";
    } //end function

    public function parentdocumentmanagement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2485,0,'DOCUMENT MANAGEMENT','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'DOCUMENT MANAGEMENT',$sort,'description',',documententry,issueslist,industrylist,documenttype,detailslist,divisionlist'," . $params['levelid'] . ")";
    } //end function

    public function documententry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2486,0,'Document Entry','',0,'\\3101','$parent',0,'0',0," . $params['levelid'] . "),
                (2487,0,'Allow View Transaction DE','DE',0,'\\ 3101001','\\3101',0,'0',0," . $params['levelid'] . "),
                (2488,0,'Allow Click Edit Button DE','',0,'\\3101002','\\3101',0,'0',0," . $params['levelid'] . "),
                (2489,0,'Allow Click New Button DE','',0,'\\310103','\\3101',0,'0',0," . $params['levelid'] . "),
                (2490,0,'Allow Click Save Button DE','',0,'\\310104','\\3101',0,'0',0," . $params['levelid'] . "),
                (2492,0,'Allow Click Delete Button DE','',0,'\\310106','\\3101',0,'0',0," . $params['levelid'] . "),
                (2493,0,'Allow Click Print Button DE','',0,'\\310107','\\3101',0,'0',0," . $params['levelid'] . "),
                (2494,0,'Allow Click Lock Button DE','',0,'\\310108','\\3101',0,'0',0," . $params['levelid'] . "),
                (2495,0,'Allow Click UnLock Button DE','',0,'\\310109','\\3101',0,'0',0," . $params['levelid'] . "),
                (2496,0,'Allow Click Post Button DE','',0,'\\310110','\\3101',0,'0',0," . $params['levelid'] . "),
                (2497,0,'Allow Click UnPost Button DE','',0,'\\310111','\\3101',0,'0',0," . $params['levelid'] . "),
                (2498,1,'Allow Click Add Item DE','',0,'\\310114','\\3101',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DT','/module/documentmanagement/dt','Document entry','fa fa-user sub_menu_ico',2486," . $params['levelid'] . ")";
    } //end function

    public function issueslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2499,0,'Issues List','',0,'\\3102','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ISSUESLIST','/tableentries/documentmanagement/dt_issues','Issues List','far fa-calendar-check sub_menu_ico',2499," . $params['levelid'] . ")";
    } //end function

    public function industrylist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2500,0,'Industry List','',0,'\\3103','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'INDUSTRYLIST','/tableentries/documentmanagement/dt_industry','Industry List','far fa-calendar-check sub_menu_ico',2500," . $params['levelid'] . ")";
    } //end function

    public function documenttype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2501,0,'Document Type List','',0,'\\3104','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DOCUMENTTYPE','/tableentries/documentmanagement/dt_documenttype','Document Type List','far fa-calendar-check sub_menu_ico',2501," . $params['levelid'] . ")";
    } //end function

    public function detailslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2502,0,'Details List','',0,'\\3105','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DETAILSLIST','/tableentries/documentmanagement/dt_details','Details List','far fa-calendar-check sub_menu_ico',2502," . $params['levelid'] . ")";
    } //end function

    public function divisionlist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2503,0,'Division List','',0,'\\3106','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DIVISION LIST','/tableentries/documentmanagement/dt_division','Division List','far fa-calendar-check sub_menu_ico',2503," . $params['levelid'] . ")";
    } //end function

    public function statuslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2504,0,'Status List','',0,'\\3107','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'STATUS LIST','/tableentries/documentmanagement/dt_status','Status List','far fa-calendar-check sub_menu_ico',2504," . $params['levelid'] . ")";
    } //end function

    public function statusaccesslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2505,0,'Status Access List','',0,'\\3108','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'STATUS ACCESS LIST','/tableentries/documentmanagement/dt_statusaccess','Status Access List','far fa-calendar-check sub_menu_ico',2505," . $params['levelid'] . ")";
    }

    public function parentpos($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2514,0,'POS','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'POS',$sort,'fa fa-cash-register',',branchledger,pospaymentsetup,extraction'," . $params['levelid'] . ")";
    } //end function

    public function extraction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2515,0,'End of Day','',0,'\\3201','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'END OF DAY','/headtable/pos/extraction','End of Day','fa fa-upload sub_menu_ico',2515," . $params['levelid'] . ")";
    }

    public function otherextraction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5121,0,'Other Extraction','',0,'\\3225','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'OTHER EXTRACTION','/headtable/pos/otherextraction','Other Extraction','fa fa-upload sub_menu_ico',5121," . $params['levelid'] . ")";
    }

    public function pospaymentsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2574,0,'POS Payment Setup','',0,'\\3202','$parent',0,'0',0," . $params['levelid'] . "),
        (2868,0,'Allow View POS Payment Setup','',0,'\\320201','\\3202',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pospaymentsetup','/tableentries/pos/pospaymentsetup','POS Payment Setup','fa fa-cog sub_menu_ico',2574," . $params['levelid'] . ")";
    } //end function

    public function sjpos($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2323,0,'Sales Journal POS','',0,'\\513','$parent',0,'0',0," . $params['levelid'] . "),
      (156,0,'Allow View Transaction SJ POS','SJ',0,'\\51301','\\513',0,'0',0," . $params['levelid'] . "),
      (173,0,'Allow Click Edit Button SJ POS','',0,'\\51302','\\513',0,'0',0," . $params['levelid'] . "),
      (194,0,'Allow Click New  Button SJ POS','',0,'\\51303','\\513',0,'0',0," . $params['levelid'] . "),
      (2137,0,'Allow Click Save Button SJ POS','',0,'\\51304','\\513',0,'0',0," . $params['levelid'] . "),
      (2473,0,'Allow Click Delete Button SJ POS','',0,'\\51306','\\513',0,'0',0," . $params['levelid'] . "),
      (2768,0,'Allow Click Print Button SJ POS','',0,'\\51307','\\513',0,'0',0," . $params['levelid'] . "),
      (2785,0,'Allow Click Lock Button SJ POS','',0,'\\51308','\\513',0,'0',0," . $params['levelid'] . "),
      (2812,0,'Allow Click UnLock Button SJ POS','',0,'\\51309','\\513',0,'0',0," . $params['levelid'] . "),
      (2829,0,'Allow Click Post Button SJ POS','',0,'\\51310','\\513',0,'0',0," . $params['levelid'] . "),
      (2664,0,'Allow Click UnPost  Button SJ POS','',0,'\\51311','\\513',0,'0',0," . $params['levelid'] . "),
      (2711,0,'Allow Change Amount  SJ POS','',0,'\\51313','\\513',0,'0',0," . $params['levelid'] . "),
      (1864,0,'Allow Check Credit Limit SJ POS','',0,'\\51314','\\513',0,'0',0," . $params['levelid'] . "),
      (1891,0,'Allow SJ POS Amount Auto-Compute on UOM Change','',0,'\\51315','\\513',0,'0',0," . $params['levelid'] . "),
      (1906,0,'Allow View Transaction Accounting SJ POS','',0,'\\51316','\\513',0,'0',0," . $params['levelid'] . "),
      (1924,1,'Allow Click Add Item SJ POS','',0,'\\51317','\\513',0,'0',0," . $params['levelid'] . "),
      (1943,1,'Allow Click Edit Item SJ POS','',0,'\\51318','\\513',0,'0',0," . $params['levelid'] . "),
      (1961,1,'Allow Click Delete Item SJ POS','',0,'\\51319','\\513',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SJ','/module/pos/sj','Sales Journal POS','fa fa-file-invoice sub_menu_ico',2323," . $params['levelid'] . ")";
    } //end function

    public function cmpos($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1990,0,'Sales Return POS','',0,'\\514','$parent',0,'0',0," . $params['levelid'] . "),
      (2008,0,'Allow View Transaction SR POS','CM',0,'\\51401','\\514',0,'0',0," . $params['levelid'] . "),
      (2155,0,'Allow Click Edit Button SR POS','',0,'\\51402','\\514',0,'0',0," . $params['levelid'] . "),
      (2173,0,'Allow Click New  Button SR POS','',0,'\\51403','\\514',0,'0',0," . $params['levelid'] . "),
      (2192,0,'Allow Click Save  Button SR POS','',0,'\\51404','\\514',0,'0',0," . $params['levelid'] . "),
      (2098,0,'Allow Click Delete Button SR POS','',0,'\\51406','\\514',0,'0',0," . $params['levelid'] . "),
      (2115,0,'Allow Click Print  Button SR POS','',0,'\\51407','\\514',0,'0',0," . $params['levelid'] . "),
      (2339,0,'Allow Click Lock Button SR POS','',0,'\\51408','\\514',0,'0',0," . $params['levelid'] . "),
      (2356,0,'Allow Click UnLock Button SR POS','',0,'\\51409','\\514',0,'0',0," . $params['levelid'] . "),
      (2387,0,'Allow Click Post Button SR POS','',0,'\\51410','\\514',0,'0',0," . $params['levelid'] . "),
      (67,0,'Allow Click UnPost  Button SR POS','',0,'\\51411','\\514',0,'0',0," . $params['levelid'] . "),
      (83,0,'Allow View Transaction Accounting SR POS','',0,'\\51412','\\514',0,'0',0," . $params['levelid'] . "),
      (2244,0,'Allow Change Amount SR POS','',0,'\\51413','\\514',0,'0',0," . $params['levelid'] . "),
      (102,1,'Allow Click Add Item SR POS','',0,'\\51414','\\514',0,'0',0," . $params['levelid'] . "),
      (623,1,'Allow Click Edit Item SR POS','',0,'\\51415','\\514',0,'0',0," . $params['levelid'] . "),
      (2648,1,'Allow Click Delete Item SR POS','',0,'\\51416','\\514',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CM','/module/pos/cm','Sales Return POS','fa fa-sync sub_menu_ico',1990," . $params['levelid'] . ")";
    } //end function

    public function crpos($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5122,0,'Received Payment','',0,'\\3226','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5123,0,'Allow View Transaction CR ','CR',0,'\\322601','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5124,0,'Allow Click Edit Button  CR ','',0,'\\322602','\\303',0,'0',0," . $params['levelid'] . ") ,
        (5125,0,'Allow Click New Button CR ','',0,'\\322603','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5126,0,'Allow Click Save Button CR ','',0,'\\322604','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5127,0,'Allow Click Delete Button CR ','',0,'\\322606','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5128,0,'Allow Click Print Button CR ','',0,'\\322607','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5129,0,'Allow Click Lock Button CR ','',0,'\\322608','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5130,0,'Allow Click UnLock Button CR ','',0,'\\322609','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5131,0,'Allow Click Post Button CR ','',0,'\\322610','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5132,0,'Allow Click UnPost Button CR ','',0,'\\322611','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5133,0,'Allow Click Add Account CR','',0,'\\322612','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5134,0,'Allow Click Edit Account CR','',0,'\\322613','\\3226',0,'0',0," . $params['levelid'] . ") ,
        (5135,0,'Allow Click Delete Account CR','',0,'\\322614','\\3226',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'CR','/module/pos/cr','Received Payment POS','fa fa-file-invoice sub_menu_ico',5122," . $params['levelid'] . ")";
    } //end function

    public function outsourcestockcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2583,0,'Outsource Items','',0,'\\113','$parent',0,'0',0," . $params['levelid'] . "),
        (2671,0,'Allow View Outsource Items','A',0,'\\11301','\\113',0,'0',0," . $params['levelid'] . "),
        (131,0,'Allow Click Edit Button Outsource Items','A',0,'\\11302','\\113',0,'0',0," . $params['levelid'] . "),
        (132,0,'Allow Click New Button Outsource Items','A',0,'\\11303','\\113',0,'0',0," . $params['levelid'] . "),
        (148,0,'Allow Click Save Button Outsource Items','A',0,'\\11304','\\113',0,'0',0," . $params['levelid'] . "),
        (149,0,'Allow Click Change Barcode Outsource Items','A',0,'\\11305','\\113',0,'0',0," . $params['levelid'] . "),
        (165,0,'Allow Click Delete Button Outsource Items','A',0,'\\11306','\\113',0,'0',0," . $params['levelid'] . "),
        (166,0,'Allow Print Button Outsource Items','A',0,'\\11307','\\113',0,'0',0," . $params['levelid'] . "),
        (167,0,'Allow View SRP Button Outsource Items','A',0,'\\11308','\\113',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'outsourcestockcard','/ledgergrid/outsource/stockcard','Outsource Items','fa fa-box-open sub_menu_ico',2583," . $params['levelid'] . ")";
    } //end function

    public function os($params, $parent, $sort)
    { // Outsource
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2520,0,'Outsource Items','',0,'\\412','$parent',0,'0',0," . $params['levelid'] . "),
        (2521,0,'Allow View Transaction OS','OS',0,'\\41201','\\412',0,'0',0," . $params['levelid'] . "),
        (2522,0,'Allow Click Edit Button OS','',0,'\\41202','\\412',0,'0',0," . $params['levelid'] . "),
        (2523,0,'Allow Click New Button OS','',0,'\\41203','\\412',0,'0',0," . $params['levelid'] . "),
        (2524,0,'Allow Click Save Button OS','',0,'\\41204','\\412',0,'0',0," . $params['levelid'] . "),
        (2526,0,'Allow Click Delete Button OS','',0,'\\41206','\\412',0,'0',0," . $params['levelid'] . "),
        (2527,0,'Allow Click Print Button OS','',0,'\\41207','\\412',0,'0',0," . $params['levelid'] . "),
        (2528,0,'Allow Click Lock Button OS','',0,'\\41208','\\412',0,'0',0," . $params['levelid'] . "),
        (2529,0,'Allow Click UnLock Button OS','',0,'\\41209','\\412',0,'0',0," . $params['levelid'] . "),
        (2530,0,'Allow Change Amount OS','',0,'\\41210','\\412',0,'0',0," . $params['levelid'] . "),
        (2531,0,'Allow Click Post Button OS','',0,'\\41212','\\412',0,'0',0," . $params['levelid'] . "),
        (2532,0,'Allow Click UnPost  Button OS','',0,'\\41213','\\412',0,'0',0," . $params['levelid'] . "),
        (2533,1,'Allow Click Add Item OS','',0,'\\41214','\\412',0,'0',0," . $params['levelid'] . "),
        (2534,1,'Allow Click Edit Item OS','',0,'\\41215','\\412',0,'0',0," . $params['levelid'] . "),
        (2688,1,'Allow Click Delete Item OS','',0,'\\41216','\\412',0,'0',0," . $params['levelid'] . "),
        (2689,1,'Allow View Amount','',0,'\\41217','\\412',0,'0',0," . $params['levelid'] . "),
        (2690,1,'Allow Click PR Button','',0,'\\41218','\\412',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'OS','/module/purchase/os','Outsource','fa fa-border-none sub_menu_ico',2520," . $params['levelid'] . ")";
    } //end function

    public function studentreportcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2691, 0, 'Student Report Card', '', 0, '\\1415', '$parent', 0, '0', 0," . $params['levelid'] . "),
        (2692, 0, 'Allow Edit Student Report Card', '', 0, '\\141501', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2693, 0, 'Allow View Student Report Card', '', 0, '\\141502', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2694, 0, 'Allow New Student Report Card', '', 0, '\\141503', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2695, 0, 'Allow Save Student Report Card', '', 0, '\\141504', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2696, 0, 'Allow Delete Student Report Card', '', 0, '\\141505', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2697, 0, 'Allow Change Code Student Report Card', '', 0, '\\141506', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2698, 0, 'Allow Lock Student Report Card', '', 0, '\\141507', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2699, 0, 'Allow UnLock Student Report Card', '', 0, '\\141508', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2700, 0, 'Allow Post Student Report Card', '', 0, '\\141509', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2701, 0, 'Allow UnPost Student Report Card', '', 0, '\\141510', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2702, 0, 'Allow Print Student Report Card', '', 0, '\\141509', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2703, 0, 'Allow Click Add Item Student Report Card', '', 0, '\\141510', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2704, 0, 'Allow Click Edit Item Student Report Card', '', 0, '\\141511', '\\1415', 0, '0', 0," . $params['levelid'] . "),
        (2705, 0, 'Allow Click Delete Item Student Report Card', '', 0, '\\141512', '\\1415', 0, '0', 0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'studentreportcard', '/module/enrollment/ek', 'Student Report Card', 'fa fa-user sub_menu_ico', 2691," . $params['levelid'] . ")";
    }

    public function othercharges($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2308,0,'Other Charges','',0,'\\3001','$parent',0,'0',0," . $params['levelid'] . "),
        (773,0,'Allow Click Edit Button Other Charges','',0,'\\300101','\\3001',0,'0',0," . $params['levelid'] . ") ,
        (2045,0,'Allow Click New Button Other Charges','',0,'\\300102','\\3001',0,'0',0," . $params['levelid'] . ") ,
        (1847,0,'Allow Click Save Button Other Charges','',0,'\\300103','\\3001',0,'0',0," . $params['levelid'] . ") ,
        (2752,0,'Allow Click Delete Button Other Charges','',0,'\\300104','\\3001',0,'0',0," . $params['levelid'] . ") ,
        (2491,0,'Allow Click Post Button Other Charges','',0,'\\300105','\\3001',0,'0',0," . $params['levelid'] . ") ,
        (2525,0,'Allow Click UnPost Button Other Charges','',0,'\\300106','\\3001',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'othercharges','/headtable/operation/othercharges','Other Charges','fa fa-money-bill-wave sub_menu_ico',2308," . $params['levelid'] . ")";
    } //end function

    public function tenancy($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4216,0,'TACRF','',0,'\\1223','$parent',0,'0',0," . $params['levelid'] . "),
        (4217,0,'Allow View TACRF','',0,'\\122301','\\1223',0,'0',0," . $params['levelid'] . "),
        (4303,0,'Allow Edit TACRF','',0,'\\122302','\\1223',0,'0',0," . $params['levelid'] . "),
        (4304,0,'Allow Release TACRF','',0,'\\122303','\\1223',0,'0',0," . $params['levelid'] . "),
        (4305,0,'Allow Approve TACRF','',0,'\\122304','\\1223',0,'0',0," . $params['levelid'] . "),
        (4306,0,'Allow Cancel TACRF','',0,'\\122305','\\1223',0,'0',0," . $params['levelid'] . ") ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tenancy','/ledgergrid/operation/tacrf','Create Tacrf','fa fa-address-card sub_menu_ico',4216," . $params['levelid'] . ")";
    } //end function

    public function cardtypes($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2635,1,'Card Types','',0,'\\515','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'cardtypes','/tableentries/tableentry/cardtypes','Card Types','fa fa-list sub_menu_ico',2635," . $params['levelid'] . ")";
    } //end function

    public function paymenttype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4502,1,'Payment Type','',0,'\\516','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'paymenttype','/tableentries/tableentry/paymenttype','Payment Type','fa fa-list sub_menu_ico',4502," . $params['levelid'] . ")";
    } //end function

    public function parentvehiclescheduling($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2876,0,'VEHICLE SCHEDULING','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'VEHICLE SCHEDULING',$sort,'fa fa-truck-pickup',',driver'," . $params['levelid'] . ")";
    } //end function

    public function driver($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2877,0,'Driver Ledger','',0,'\\3301','$parent',0,'0',0," . $params['levelid'] . "),
        (2878,0,'Allow View Driver Ledger','',0,'\\330101','\\3301',0,'0',0," . $params['levelid'] . "),
        (2879,0,'Allow Click Edit Button DL','',0,'\\330102','\\3301',0,'0',0," . $params['levelid'] . "),
        (2880,0,'Allow Click New Button DL','',0,'\\330103','\\3301',0,'0',0," . $params['levelid'] . "),
        (2881,0,'Allow Click Save Button DL','',0,'\\330104','\\3301',0,'0',0," . $params['levelid'] . "),
        (2882,0,'Allow Click Change Code DL','',0,'\\330105','\\3301',0,'0',0," . $params['levelid'] . "),
        (2883,0,'Allow Click Delete Button DL','',0,'\\330106','\\3301',0,'0',0," . $params['levelid'] . "),
        (2884,0,'Allow Click Print Button DL','',0,'\\330107','\\3301',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'driver','/ledger/vehiclescheduling/driver','Driver','fa fa-car sub_menu_ico',2877," . $params['levelid'] . ")";
    } //end function

    public function passenger($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2885,0,'Passenger Ledger','',0,'\\3302','$parent',0,'0',0," . $params['levelid'] . "),
        (2886,0,'Allow View Passenger Ledger','',0,'\\330201','\\3302',0,'0',0," . $params['levelid'] . "),
        (2887,0,'Allow Click Edit Button PL','',0,'\\330202','\\3302',0,'0',0," . $params['levelid'] . "),
        (2888,0,'Allow Click New Button PL','',0,'\\330203','\\3302',0,'0',0," . $params['levelid'] . "),
        (2889,0,'Allow Click Save Button PL','',0,'\\330204','\\3302',0,'0',0," . $params['levelid'] . "),
        (2890,0,'Allow Click Change Code PL','',0,'\\330205','\\3302',0,'0',0," . $params['levelid'] . "),
        (2891,0,'Allow Click Delete Button PL','',0,'\\330206','\\3302',0,'0',0," . $params['levelid'] . "),
        (2892,0,'Allow Click Print Button PL','',0,'\\330207','\\3302',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'passenger','/ledger/vehiclescheduling/passenger','Passenger','fa fa-user-plus sub_menu_ico',2885," . $params['levelid'] . ")";
    } //end function

    public function vr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2893,0,'Vehicle Scheduling Request','',0,'\\3303','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2894,0,'Allow View VR','',0,'\\330301','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2895,0,'Allow Click Edit Button VR','',0,'\\330302','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2896,0,'Allow Click New Button VR','',0,'\\330303','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2897,0,'Allow Click Save Button VR','',0,'\\330304','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2898,0,'Allow Click Change Code VR','',0,'\\330305','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2899,0,'Allow Click Delete Button VR','',0,'\\330306','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2900,0,'Allow Click Print Button VR','',0,'\\330307','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2901,0,'Allow Click Post Button VR','',0,'\\330308','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2902,0,'Allow Click UnPost Button VR','',0,'\\330309','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2903,0,'Allow Click Lock Button VR','',0,'\\330310','\\3303',0,'0',0," . $params['levelid'] . ") ,
        (2904,0,'Allow Click UnLock Button VR','',0,'\\330311','\\3303',0,'0',0," . $params['levelid'] . "),
        (2905,1,'Allow Click Add Item VR','',0,'\\330312','\\3303',0,'0',0," . $params['levelid'] . "),
        (2906,1,'Allow Click Edit Item VR','',0,'\\330313','\\3303',0,'0',0," . $params['levelid'] . "),
        (2907,1,'Allow Click Delete Item VR','',0,'\\330314','\\3303',0,'0',0," . $params['levelid'] . "),
        (3590,1,'Allow View All Request','',0,'\\330315','\\3303',0,'0',0," . $params['levelid'] . "),
        (3722,1,'Allow View Dashboard Request','',0,'\\330316','\\3303',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'VR','/module/vehiclescheduling/vr','Vehicle Scheduling Request','fa fa-truck-moving sub_menu_ico',2893," . $params['levelid'] . ")";
    } //end function

    public function vl($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2929,0,'Logistics','',0,'\\3305','$parent',0,'0',0," . $params['levelid'] . ") ,
        (2930,0,'Allow View VL','',0,'\\330501','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2931,0,'Allow Click Edit Button VL','',0,'\\330502','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2932,0,'Allow Click New Button VL','',0,'\\330503','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2933,0,'Allow Click Save Button VL','',0,'\\330504','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2934,0,'Allow Click Change Code VL','',0,'\\330505','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2935,0,'Allow Click Delete Button VL','',0,'\\330506','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2936,0,'Allow Click Print Button VL','',0,'\\330507','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2937,0,'Allow Click Post Button VL','',0,'\\330508','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2938,0,'Allow Click UnPost Button VL','',0,'\\330509','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2939,0,'Allow Click Lock Button VL','',0,'\\330510','\\3305',0,'0',0," . $params['levelid'] . ") ,
        (2940,0,'Allow Click UnLock Button VL','',0,'\\330511','\\3305',0,'0',0," . $params['levelid'] . "),
        (2941,1,'Allow Click Add Item VL','',0,'\\330512','\\3305',0,'0',0," . $params['levelid'] . "),
        (2942,1,'Allow Click Edit Item VL','',0,'\\330513','\\3305',0,'0',0," . $params['levelid'] . "),
        (2943,1,'Allow Click Delete Item VL','',0,'\\330514','\\3305',0,'0',0," . $params['levelid'] . "),
        (3715,1,'View Dashboard Approved w/out Vehicle','',0,'\\330515','\\3305',0,'0',0," . $params['levelid'] . "),
        (3716,1,'View Dashboard Approved w/ Vehicle','',0,'\\330516','\\3305',0,'0',0," . $params['levelid'] . "),
        (3717,1,'Allow View All Dashboard Requests','',0,'\\330517','\\3305',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'VL','/module/vehiclescheduling/vl','Logistics','fa fa-truck-loading sub_menu_ico',2929," . $params['levelid'] . ")";
    } //end function

    public function vrapproval($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2911,0,'VR Approval List','',0,'\\3304','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'vrapproval','/ledgergrid/ati/requestapproval','Vehicle Request Approval List','fa fa-calendar-check sub_menu_ico',2911," . $params['levelid'] . ")";
    } //end function

    public function vehiclesched($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2996,1,'Vehicle Schedule','',0,'\\3306','$parent',0,0,0," . $params['levelid'] . "),
        (2997,1,'Create Vehicle Schedule','',0,'\\330601','\\3306',0,'0',0," . $params['levelid'] . "),
        (2998,1,'View Dashboard Vehicle Status','',0,'\\330602','\\3306',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'emptimecard','/headtable/vehiclescheduling/vehiclesched','Vehicle Schedule','fa fa-calendar-day sub_menu_ico',2996," . $params['levelid'] . ")";
    } //end function

    public function parentfams($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2908,0,'FAMS','',0,'$parent','\\',0,'',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'FAMS',$sort,'fa fa-warehouse',',issueitems'," . $params['levelid'] . ")";
    } //end function

    public function issueitems($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2909,0,'Issue Items','',0,'\\3401','$parent',0,'0',0," . $params['levelid'] . "),
        (4155,0,'Allow View Transaction','FI',0,'\\340101','\\3401',0,'0',0," . $params['levelid'] . "),
        (4156,0,'Allow Click Edit Button','',0,'\\340102','\\3401',0,'0',0," . $params['levelid'] . "),
        (4157,0,'Allow Click New Button','',0,'\\340103','\\3401',0,'0',0," . $params['levelid'] . "),
        (4158,0,'Allow Click Save Button','',0,'\\340104','\\3401',0,'0',0," . $params['levelid'] . "),
        (4159,0,'Allow Click Delete Button','',0,'\\340105','\\3401',0,'0',0," . $params['levelid'] . "),
        (4160,0,'Allow Click Post Button','',0,'\\340106','\\3401',0,'0',0," . $params['levelid'] . "),
        (4161,0,'Allow Click Unpost Button','',0,'\\340107','\\3401',0,'0',0," . $params['levelid'] . "),
        (4200,0,'Allow Click Delete Item','',0,'\\340109','\\3401',0,'0',0," . $params['levelid'] . "),
        (4201,0,'Allow Click Add Item','',0,'\\340110','\\3401',0,'0',0," . $params['levelid'] . "),
        (4205,0,'Allow Click Print Button','',0,'\\340111','\\3401',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'FI','/module/fams/fi','Issue Items','fa fa-list sub_menu_ico',2909," . $params['levelid'] . ")";
    } //end function

    public function returnitems($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3965,0,'Return Items','',0,'\\3402','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'returnitems','/ledgergrid/fams/returnitems','Return Items','fa fa-retweet sub_menu_ico',3965," . $params['levelid'] . ")";
    } //end function

    public function generalitem($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2910,1,'General Item','',0,'\\2601','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'generalitem','/tableentries/tableentry/entrygeneralitem','General Item','fa fa-list sub_menu_ico',2910," . $params['levelid'] . ")";
    } //end function

    public function gp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2913,0,'Gate Pass OUT','',0,'\\3403','$parent',0,'0',0," . $params['levelid'] . "),
      (2914,0,'Allow View Transaction GP','GP',0,'\\340301','\\3403',0,'0',0," . $params['levelid'] . "),
      (2915,0,'Allow Click Edit Button GP','',0,'\\340302','\\3403',0,'0',0," . $params['levelid'] . "),
      (2916,0,'Allow Click New  Button GP','',0,'\\340303','\\3403',0,'0',0," . $params['levelid'] . "),
      (2917,0,'Allow Click Save Button GP','',0,'\\340304','\\3403',0,'0',0," . $params['levelid'] . "),
      (2918,0,'Allow Click Delete Button GP','',0,'\\340305','\\3403',0,'0',0," . $params['levelid'] . "),
      (2919,0,'Allow Click Print Button GP','',0,'\\340306','\\3403',0,'0',0," . $params['levelid'] . "),
      (2920,0,'Allow Click Lock Button GP','',0,'\\340307','\\3403',0,'0',0," . $params['levelid'] . "),
      (2921,0,'Allow Click UnLock Button GP','',0,'\\340310','\\3403',0,'0',0," . $params['levelid'] . "),
      (2922,0,'Allow Change Amount  GP','',0,'\\340311','\\3403',0,'0',0," . $params['levelid'] . "),
      (2923,0,'Allow Check Credit Limit GP','',0,'\\340312','\\3403',0,'0',0," . $params['levelid'] . "),
      (2924,0,'Allow Click Post Button GP','',0,'\\340313','\\3403',0,'0',0," . $params['levelid'] . "),
      (2925,0,'Allow Click UnPost  Button GP','',0,'\\340314','\\3403',0,'0',0," . $params['levelid'] . "),
      (2926,1,'Allow Click Add Item GP','',0,'\\340315','\\3403',0,'0',0," . $params['levelid'] . "),
      (2927,1,'Allow Click Edit Item GP','',0,'\\340316','\\3403',0,'0',0," . $params['levelid'] . "),
      (2928,1,'Allow Click Delete Item GP','',0,'\\340317','\\3403',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GP','/module/fams/gp','Gate Pass OUT','fa fa-clipboard-list sub_menu_ico',2913," . $params['levelid'] . ")";
    } //end function

    public function gatepassreturn($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2944,0,'Gate Pass Return','',0,'\\3404','$parent',0,'0',0," . $params['levelid'] . "),
        (2945,0,'Allow View Gate Pass Return','gpr',0,'\\340401','\\3404',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehousepicker','/ledgergrid/fams/gatepassreturn','Gate Pass Return','fa fa-clipboard-list sub_menu_ico',2944," . $params['levelid'] . ")";
    } //end function


    public function fc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3966,0,'Convert to Asset','',0,'\\3405','$parent',0,'0',0," . $params['levelid'] . "),
        (3967,0,'Allow View Transaction FC','FC',0,'\\340501','\\3405',0,'0',0," . $params['levelid'] . "),
        (3968,0,'Allow Click Edit Button FC','FC',0,'\\340502','\\3405',0,'0',0," . $params['levelid'] . "),
        (3969,0,'Allow Click New  Button FC','FC',0,'\\340503','\\3405',0,'0',0," . $params['levelid'] . "),
        (3970,0,'Allow Click Save Button FC','FC',0,'\\340504','\\3405',0,'0',0," . $params['levelid'] . "),
        (3971,0,'Allow Click Delete Button FC','FC',0,'\\340505','\\3405',0,'0',0," . $params['levelid'] . "),
        (3972,0,'Allow Click Print Button FC','FC',0,'\\340506','\\3405',0,'0',0," . $params['levelid'] . "),
        (3973,0,'Allow Click Lock Button FC','FC',0,'\\340507','\\3405',0,'0',0," . $params['levelid'] . "),
        (3974,0,'Allow Click UnLock Button FC','FC',0,'\\340508','\\3405',0,'0',0," . $params['levelid'] . "),
        (3975,0,'Allow Click Post Button FC','FC',0,'\\340509','\\3405',0,'0',0," . $params['levelid'] . "),
        (3976,0,'Allow Click UnPost  Button FC','FC',0,'\\340510','\\3405',0,'0',0," . $params['levelid'] . "),
        (3977,0,'Allow View Transaction Accounting FA','FC',0,'\\340511','\\3405',0,'0',0," . $params['levelid'] . "),
        (3978,1,'Allow Click Add Item FC','',0,'\\340512','\\3405',0,'0',0," . $params['levelid'] . "),
        (3979,1,'Allow Click Edit Item FC','',0,'\\340513','\\3405',0,'0',0," . $params['levelid'] . "),
        (3980,1,'Allow Click Delete Item FC','',0,'\\340514','\\3405',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'FC','/module/fams/fc','Convert to Asset','fa fa-boxes sub_menu_ico',3966," . $params['levelid'] . ")";
    } //end function

    public function gpal($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3981,0,'Gate Pass Asset Logs','',0,'\\3406','$parent',0,'0',0," . $params['levelid'] . "),
        (3982,0,'Allow View Gate Pass Asset Logs','gpal',0,'\\340601','\\3406',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'gpal','/ledgergrid/fams/gpal','Gate Pass Asset Logs','fa fa-clipboard-list sub_menu_ico',3982," . $params['levelid'] . ")";
    } //end function

    public function pf($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2946,0,'Purchase Order - General Item','',0,'\\25014','$parent',0,'0',0," . $params['levelid'] . "),
        (2947,0,'Allow View Transaction PO','PO',0,'\\2501401','\\25014',0,'0',0," . $params['levelid'] . "),
        (2948,0,'Allow Click Edit Button PO','',0,'\\2501402','\\25014',0,'0',0," . $params['levelid'] . "),
        (2949,0,'Allow Click New Button PO','',0,'\\2501403','\\25014',0,'0',0," . $params['levelid'] . "),
        (2950,0,'Allow Click Save Button PO','',0,'\\2501404','\\25014',0,'0',0," . $params['levelid'] . "),
        (2951,0,'Allow Click Delete Button PO','',0,'\\2501406','\\25014',0,'0',0," . $params['levelid'] . "),
        (2952,0,'Allow Click Print Button PO','',0,'\\2501407','\\25014',0,'0',0," . $params['levelid'] . "),
        (2953,0,'Allow Click Lock Button PO','',0,'\\2501408','\\25014',0,'0',0," . $params['levelid'] . "),
        (2954,0,'Allow Click UnLock Button PO','',0,'\\2501409','\\25014',0,'0',0," . $params['levelid'] . "),
        (2955,0,'Allow Change Amount PO','',0,'\\2501410','\\25014',0,'0',0," . $params['levelid'] . "),
        (2956,0,'Allow Click Post Button PO','',0,'\\2501412','\\25014',0,'0',0," . $params['levelid'] . "),
        (2957,0,'Allow Click UnPost  Button PO','',0,'\\2501413','\\25014',0,'0',0," . $params['levelid'] . "),
        (2958,1,'Allow Click Add Item PO','',0,'\\2501414','\\25014',0,'0',0," . $params['levelid'] . "),
        (2959,1,'Allow Click Edit Item PO','',0,'\\2501415','\\25014',0,'0',0," . $params['levelid'] . "),
        (2960,1,'Allow Click Delete Item PO','',0,'\\2501416','\\25014',0,'0',0," . $params['levelid'] . "),
        (2961,1,'Allow View Amount','',0,'\\2501417','\\25014',0,'0',0," . $params['levelid'] . "),
        (2962,1,'Allow Click PR Button','',0,'\\2501418','\\25014',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PF','/module/purchase/pf','Purchase Order - General Item','fa fa-tasks sub_menu_ico',2946," . $params['levelid'] . ")";
    } //end function

    public function ra($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Receiving Report - General Item';
        $qry = "(2963,0,'" . $label . "','',0,'\\413','$parent',0,'0',0," . $params['levelid'] . "),
        (2964,0,'Allow View Transaction RR','RR',0,'\\41301','\\413',0,'0',0," . $params['levelid'] . "),
        (2965,0,'Allow Click Edit Button RR','',0,'\\41302','\\413',0,'0',0," . $params['levelid'] . "),
        (2966,0,'Allow Click New Button RR','',0,'\\41303','\\413',0,'0',0," . $params['levelid'] . "),
        (2967,0,'Allow Click Save Button RR','',0,'\\41304','\\413',0,'0',0," . $params['levelid'] . "),
        (2968,0,'Allow Click Delete Button RR','',0,'\\41306','\\413',0,'0',0," . $params['levelid'] . "),
        (2969,0,'Allow Click Print Button RR','',0,'\\41307','\\413',0,'0',0," . $params['levelid'] . "),
        (2970,0,'Allow Click Lock Button RR','',0,'\\41308','\\413',0,'0',0," . $params['levelid'] . "),
        (2971,0,'Allow Click UnLock Button RR','',0,'\\41309','\\413',0,'0',0," . $params['levelid'] . "),
        (2972,0,'Allow Click Post Button RR','',0,'\\41310','\\413',0,'0',0," . $params['levelid'] . "),
        (2973,0,'Allow Click UnPost Button RR','',0,'\\41311','\\413',0,'0',0," . $params['levelid'] . "),
        (2974,0,'Allow View Transaction accounting RR','',0,'\\40212','\\413',0,'0',0," . $params['levelid'] . "),
        (2975,0,'Allow Change Amount RR','',0,'\\41313','\\413',0,'0',0," . $params['levelid'] . "),
        (2976,1,'Allow Click Add Item RR','',0,'\\41314','\\413',0,'0',0," . $params['levelid'] . "),
        (2977,1,'Allow Click Edit Item RR','',0,'\\41315','\\413',0,'0',0," . $params['levelid'] . "),
        (2978,1,'Allow Click Delete Item RR','',0,'\\41316','\\413',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RA','/module/purchase/ra','" . $label . "','fa fa-people-carry sub_menu_ico',2963," . $params['levelid'] . ")";
    } //end function

    public function genericitem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Generic Item';
        $qry = "(2979,0,'" . $label . "','',0,'\\414','$parent',0,'0',0," . $params['levelid'] . "),
        (2980,0,'Allow View " . $label . "','Generic Item',0,'\\41401','\\414',0,'0',0," . $params['levelid'] . "),
        (2981,0,'Allow Click Edit Button Generic Item','',0,'\\41402','\\414',0,'0',0," . $params['levelid'] . "),
        (2982,0,'Allow Click New Button Generic Item','',0,'\\41403','\\414',0,'0',0," . $params['levelid'] . "),
        (2983,0,'Allow Click Save Button Generic Item','',0,'\\41404','\\414',0,'0',0," . $params['levelid'] . "),
        (2984,0,'Allow Click Change Barcode Generic Item','',0,'\\41405','\\414',0,'0',0," . $params['levelid'] . "),
        (2985,0,'Allow Click Delete Button Generic Item','',0,'\\41406','\\414',0,'0',0," . $params['levelid'] . "),
        (2986,0,'Allow Print Button Generic Item','',0,'\\41407','\\414',0,'0',0," . $params['levelid'] . "),
        (2987,0,'Allow View SRP Button Generic Item','',0,'\\41408','\\414',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'genericitem','/ledgergrid/genericitem/stockcard','" . $label . "','fa fa-list-alt sub_menu_ico',2979," . $params['levelid'] . ")";
    } //end function


    public function parentkwhmonitoring($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4055,0,'KWH MONITORING','',0,'$parent','\\',0,'',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'KWH MONITORING',$sort,'bolt',','," . $params['levelid'] . ")";
    } //end function

    public function powerconsumption($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4056,0,'Power Consumption Category','',0,'\\3601','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'powerconsumption','/tableentries/kwhmonitoring/powerconsumption','Power Consumption Category','fa fa-list sub_menu_ico',4056," . $params['levelid'] . ")";
    } //end function

    public function kwhratesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4123,0,'Rate Setup','',0,'\\3603','$parent',0,'0',0," . $params['levelid'] . "),
        (4140,0,'Allow delete rate','',0,'\\360301','\\3603',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'kwhratesetup','/headtable/kwhmonitoring/kwhratesetup','Rate Setup','fa fa-money-bill sub_menu_ico',4123," . $params['levelid'] . ")";
    }

    public function pw($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $folder = 'kwhmonitoring';
        $modulename = 'Power Consumption Entry';

        $qry = " (4078,0,'" . $modulename . "','',0,'\\3602','$parent',0,'0',0," . $params['levelid'] . "),
        (4079,0,'Allow View Transaction PCE','PW',0,'\\360201','\\3602',0,'0',0," . $params['levelid'] . "),
        (4080,0,'Allow Click Edit Button PCE','',0,'\\360202','\\3602',0,'0',0," . $params['levelid'] . "),
        (4081,0,'Allow Click New  Button PCE','',0,'\\360203','\\3602',0,'0',0," . $params['levelid'] . "),
        (4082,0,'Allow Click Save Button PCE','',0,'\\360204','\\3602',0,'0',0," . $params['levelid'] . "),
        (4083,0,'Allow Click Delete Button PCE','',0,'\\360206','\\3602',0,'0',0," . $params['levelid'] . "),
        (4084,0,'Allow Click Print Button PCE','',0,'\\360207','\\3602',0,'0',0," . $params['levelid'] . "),
        (4085,0,'Allow Click Lock Button PCE','',0,'\\360208','\\3602',0,'0',0," . $params['levelid'] . "),
        (4086,0,'Allow Click UnLock Button PCE','',0,'\\360209','\\3602',0,'0',0," . $params['levelid'] . "),
        (4087,0,'Allow Change Amount  PCE','',0,'\\360210','\\3602',0,'0',0," . $params['levelid'] . "),
        (4088,0,'Allow Click Post Button PCE','',0,'\\360212','\\3602',0,'0',0," . $params['levelid'] . "),
        (4089,0,'Allow Click UnPost  Button PCE','',0,'\\360213','\\3602',0,'0',0," . $params['levelid'] . "),
        (4090,1,'Allow Click Edit Item PCE','',0,'\\360215','\\3602',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PW','/module/" . $folder . "/pw','" . $modulename . "','fa fa-clipboard-list sub_menu_ico',4078," . $params['levelid'] . ")";
    } //end function


    public function parentwaterbilling($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4103,0,'WATER BILLING','',0,'$parent','\\',0,'',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'WATER BILLING',$sort,'receipt',','," . $params['levelid'] . ")";
    } //end function

    public function purpose($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2988,0,'Purpose','',0,'\\1110','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'purpose','/tableentries/vehiclescheduling/entrypurpose','Purpose','fa fa-calendar-check sub_menu_ico',2988," . $params['levelid'] . ")";
    } //end function

    public function qtybracket($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2989,0,'Price Bracket Setup','',0,'\\895','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'qtybracket','/tableentries/othersettings/entryqtybracket','Quantity Bracket Setup','fa fa-list sub_menu_ico',2989," . $params['levelid'] . ")";
    } //end function

    public function pricelist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2990,0,'Price List','',0,'\\896','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pricelist','/headtable/othersettings/pricelist','Price List','fa fa-list sub_menu_ico',2990," . $params['levelid'] . ")";
    } //end function

    public function duration($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4580,0,'Duration Setup','',0,'\\816','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'audittrail','/tableentries/othersettings/entryduration','Duration','fa fa-calendar-day sub_menu_ico',4580," . $params['levelid'] . ")";
    } //end function


    public function requestcategory($params, $parent, $sort)
    {
        $label = 'Request Category';
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3742,0,'" . $label . "','',0,'\\820','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'requestcategory','/tableentries/othersettings/entryrequestcategory','" . $label . "','fa fa-list sub_menu_ico',3742," . $params['levelid'] . ")";
    } //end function


    public function reassignmentcategory($params, $parent, $sort)
    {
        $label = 'Employee Category';
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5265,0,'" . $label . "','',0,'\\844','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'reassignmentcategory','/tableentries/othersettings/entryreassignmentcategory','" . $label . "','fa fa-list sub_menu_ico',5265," . $params['levelid'] . ")";
    } //end function

    public function sp($params, $parent, $sort)
    {

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3560,0,'Stock Return','',0,'\\1505','$parent',0,'0',0," . $params['levelid'] . ") ,
        (3561,0,'Allow View Transaction S. Return','SS',0,'\\150501','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3562,0,'Allow Click Edit Button  S. Return','',0,'\\150502','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3563,0,'Allow Click New Button S. Return','',0,'\\150503','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3564,0,'Allow Click Save Button S. Return','',0,'\\150504','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3565,0,'Allow Click Delete Button S. Return','',0,'\\150505','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3566,0,'Allow Click Print Button S. Return','',0,'\\150506','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3567,0,'Allow Click Lock Button S. Return','',0,'\\150507','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3568,0,'Allow Click UnLock Button S. Return','',0,'\\150508','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3569,0,'Allow Click Post Button S. Return','',0,'\\150509','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3570,0,'Allow Click UnPost Button S. Return','',0,'\\150510','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3571,1,'Allow Click Add Item S. Return','',0,'\\150511','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3572,1,'Allow Click Delete Item S. Return','',0,'\\150512','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3573,1,'Allow Change Amount S. Return','',0,'\\150513','\\1505',0,'0',0," . $params['levelid'] . ") ,
        (3574,1,'Allow Click Edit Item S. Return','',0,'\\150514','\\1505',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        $systemtype = $this->companysetup->getsystemtype($params);
        $folder = 'ati';

        return "($sort,$p,'SP','/module/" . $folder . "/sp','Stock Return','fa fa-sync sub_menu_ico',3560," . $params['levelid'] . ")";
    } //end function


    public function oq($params, $parent, $sort)
    {
        $companyid = $params['companyid'];

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3603,0,'Oracle Code Request','',0,'\\415','$parent',0,'0',0," . $params['levelid'] . "),
        (3604,0,'Allow View Transaction OQ','OQ',0,'\\41501','\\415',0,'0',0," . $params['levelid'] . "),
        (3605,0,'Allow Click Edit Button OQ','',0,'\\41502','\\415',0,'0',0," . $params['levelid'] . "),
        (3606,0,'Allow Click New Button OQ','',0,'\\41503','\\415',0,'0',0," . $params['levelid'] . "),
        (3607,0,'Allow Click Save Button OQ','',0,'\\41504','\\415',0,'0',0," . $params['levelid'] . "),
        (3608,0,'Allow Click Delete Button OQ','',0,'\\41506','\\415',0,'0',0," . $params['levelid'] . "),
        (3609,0,'Allow Click Print Button OQ','',0,'\\41507','\\415',0,'0',0," . $params['levelid'] . "),
        (3610,0,'Allow Click Lock Button OQ','',0,'\\41508','\\415',0,'0',0," . $params['levelid'] . "),
        (3611,0,'Allow Click UnLock Button OQ','',0,'\\41509','\\415',0,'0',0," . $params['levelid'] . "),
        (3613,0,'Allow Click Post Button OQ','',0,'\\41512','\\415',0,'0',0," . $params['levelid'] . "),
        (3614,0,'Allow Click UnPost  Button OQ','',0,'\\41513','\\415',0,'0',0," . $params['levelid'] . "),
        (3615,1,'Allow Click Add Item OQ','',0,'\\41514','\\415',0,'0',0," . $params['levelid'] . "),
        (3616,1,'Allow Click Edit Item OQ','',0,'\\41515','\\415',0,'0',0," . $params['levelid'] . "),
        (3617,1,'Allow Click Delete Item OQ','',0,'\\41516','\\415',0,'0',0," . $params['levelid'] . "),
        (3620,1,'Allow Click For Revision','',0,'\\41517','\\415',0,'0',0," . $params['levelid'] . "),
        (4030,1,'Allow Price Edit','',0,'\\41518','\\415',0,'0',0," . $params['levelid'] . "),
        (4169,1,'Allow Clicked Approved','',0,'\\41519','\\415',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
        }
        return "($sort,$p,'OQ','/module/" . $folder . "/oq','Oracle Code Request','fa fa-database sub_menu_ico',3603," . $params['levelid'] . ")";
    } //end function

    public function om($params, $parent, $sort)
    {
        $companyid = $params['companyid'];

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4176,0,'OSI','',0,'\\419','$parent',0,'0',0," . $params['levelid'] . "),
        (4177,0,'Allow View Transaction OSI','OM',0,'\\41901','\\419',0,'0',0," . $params['levelid'] . "),
        (4178,0,'Allow Click Edit Button OSI','',0,'\\41902','\\419',0,'0',0," . $params['levelid'] . "),
        (4179,0,'Allow Click New Button OSI','',0,'\\41903','\\419',0,'0',0," . $params['levelid'] . "),
        (4180,0,'Allow Click Save Button OSI','',0,'\\41904','\\419',0,'0',0," . $params['levelid'] . "),
        (4181,0,'Allow Click Delete Button OSI','',0,'\\41905','\\419',0,'0',0," . $params['levelid'] . "),
        (4182,0,'Allow Click Print Button OSI','',0,'\\41906','\\419',0,'0',0," . $params['levelid'] . "),
        (4183,0,'Allow Click Lock Button OSI','',0,'\\41907','\\419',0,'0',0," . $params['levelid'] . "),
        (4184,0,'Allow Click UnLock Button OSI','',0,'\\41908','\\419',0,'0',0," . $params['levelid'] . "),
        (4185,0,'Allow Click Post Button OSI','',0,'\\41909','\\419',0,'0',0," . $params['levelid'] . "),
        (4186,0,'Allow Click UnPost  Button OSI','',0,'\\41910','\\419',0,'0',0," . $params['levelid'] . "),
        (4187,1,'Allow Click Add Item OSI','',0,'\\41911','\\419',0,'0',0," . $params['levelid'] . "),
        (4188,1,'Allow Click Edit Item OSI','',0,'\\41912','\\419',0,'0',0," . $params['levelid'] . "),
        (4189,1,'Allow Click Delete Item OSI','',0,'\\41913','\\419',0,'0',0," . $params['levelid'] . "),
        (4197,1,'Allow Click For Receiving','',0,'\\41914','\\419',0,'0',0," . $params['levelid'] . "),
        (4198,1,'Allow Click For SO','',0,'\\41915','\\419',0,'0',0," . $params['levelid'] . "),
        (4120,1,'Allow Click For Posting','',0,'\\41916','\\419',0,'0',0," . $params['levelid'] . "),
        (4121,1,'Allow Input SO','',0,'\\41917','\\419',0,'0',0," . $params['levelid'] . "),
        (4508,1,'Allow Update Details','',0,'\\41918','\\419',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
        }
        return "($sort,$p,'OM','/module/" . $folder . "/om','O S I','fa fa-database sub_menu_ico',4176," . $params['levelid'] . ")";
    } //end function




    public function lq($params, $parent, $sort)
    {
        $companyid = $params['companyid'];

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3724,0,'Cash Liquidation Form ','',0,'\\416','$parent',0,'0',0," . $params['levelid'] . "),
        (3725,0,'Allow View Transaction LQ','LQ',0,'\\41601','\\416',0,'0',0," . $params['levelid'] . "),
        (3726,0,'Allow Click Edit Button LQ','',0,'\\41602','\\416',0,'0',0," . $params['levelid'] . "),
        (3727,0,'Allow Click New Button LQ','',0,'\\41603','\\416',0,'0',0," . $params['levelid'] . "),
        (3728,0,'Allow Click Save Button LQ','',0,'\\41604','\\416',0,'0',0," . $params['levelid'] . "),
        (3729,0,'Allow Click Delete Button LQ','',0,'\\41605','\\416',0,'0',0," . $params['levelid'] . "),
        (3730,0,'Allow Click Print Button LQ','',0,'\\41606','\\416',0,'0',0," . $params['levelid'] . "),
        (3731,0,'Allow Click Lock Button LQ','',0,'\\41607','\\416',0,'0',0," . $params['levelid'] . "),
        (3732,0,'Allow Click UnLock Button LQ','',0,'\\41608','\\416',0,'0',0," . $params['levelid'] . "),
        (3733,0,'Allow Change Amount LQ','',0,'\\41609','\\416',0,'0',0," . $params['levelid'] . "),
        (3734,0,'Allow Click Post Button LQ','',0,'\\41610','\\416',0,'0',0," . $params['levelid'] . "),
        (3735,0,'Allow Click UnPost  Button LQ','',0,'\\41611','\\416',0,'0',0," . $params['levelid'] . "),
        (3736,1,'Allow Click Add Item LQ','',0,'\\41612','\\416',0,'0',0," . $params['levelid'] . "),
        (3737,1,'Allow Click Edit Item LQ','',0,'\\41613','\\416',0,'0',0," . $params['levelid'] . "),
        (3738,1,'Allow Click Delete Item LQ','',0,'\\41614','\\416',0,'0',0," . $params['levelid'] . "),
        (3739,1,'Allow View Amount','',0,'\\41615','\\416',0,'0',0," . $params['levelid'] . "),
        (3740,1,'Allow Click PR Button','',0,'\\41616','\\416',0,'0',0," . $params['levelid'] . "),
        (3741,1,'Allow Void Button','',0,'\\41617','\\416',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
        }
        return "($sort,$p,'LQ','/module/" . $folder . "/lq','Cash Liquidation Form','fa fa-money-bill sub_menu_ico',3724," . $params['levelid'] . ")";
    } //end function

    public function parentproduction($params, $parent, $sort)
    {
        $modules = "'prodinstruction,prodorder,finishgoodsentry'";
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3631,0,'PRODUCTION','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PRODUCTION',$sort,'list_alt'," . $modules . "," . $params['levelid'] . ")";
    } //end function

    public function prodinstruction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3632,0,'Production Instruction','',0,'\\2701','$parent',0,'0',0," . $params['levelid'] . "),
            (3633,0,'Allow View Transaction PI','PI',0,'\\270101','\\2701',0,'0',0," . $params['levelid'] . "),
            (3634,0,'Allow Click Edit Button PI','',0,'\\270102','\\2701',0,'0',0," . $params['levelid'] . "),
            (3635,0,'Allow Click New Button PI','',0,'\\270103','\\2701',0,'0',0," . $params['levelid'] . "),
            (3636,0,'Allow Click Save Button PI','',0,'\\270104','\\2701',0,'0',0," . $params['levelid'] . "),
            (3637,0,'Allow Click Delete Button PI','',0,'\\270105','\\2701',0,'0',0," . $params['levelid'] . "),
            (3638,0,'Allow Click Print Button PI','',0,'\\270106','\\2701',0,'0',0," . $params['levelid'] . "),
            (3639,0,'Allow Click Lock Button PI','',0,'\\270107','\\2701',0,'0',0," . $params['levelid'] . "),
            (3640,0,'Allow Click UnLock Button PI','',0,'\\270108','\\2701',0,'0',0," . $params['levelid'] . "),
            (3641,0,'Allow Click Post Button PI','',0,'\\270109','\\2701',0,'0',0," . $params['levelid'] . "),
            (3642,0,'Allow Click UnPost Button PI','',0,'\\270110','\\2701',0,'0',0," . $params['levelid'] . "),
            (3643,0,'Allow Click Add Item PI','',0,'\\270111','\\2701',0,'0',0," . $params['levelid'] . "),
            (3644,0,'Allow Click Delete Item PI','',0,'\\270112','\\2701',0,'0',0," . $params['levelid'] . "),
            (3645,0,'Allow Change Amount PI','',0,'\\270113','\\2701',0,'0',0," . $params['levelid'] . "),
            (3646,0,'Allow Click Edit Item PI','',0,'\\270114','\\2701',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PI','/module/production/pi','Production Instruction','fa fa-sync sub_menu_ico',3632," . $params['levelid'] . ")";
    }

    public function rm($params, $parent, $sort)
    {
        switch ($params['companyid']) {
            case 24: //goodfound
                $label = 'Raw Material Issuance';
                break;
            default:
                $label = 'Raw Material Usage';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3650,0,'" . $label . "','',0,'\\2704','$parent',0,'0',0," . $params['levelid'] . "),
            (3651,0,'Allow View Transaction RM','RM',0,'\\270401','\\2704',0,'0',0," . $params['levelid'] . "),
            (3652,0,'Allow Click Edit Button RM','',0,'\\270402','\\2704',0,'0',0," . $params['levelid'] . "),
            (3653,0,'Allow Click New Button RM','',0,'\\270403','\\2704',0,'0',0," . $params['levelid'] . "),
            (3654,0,'Allow Click Save Button RM','',0,'\\270404','\\2704',0,'0',0," . $params['levelid'] . "),
            (3655,0,'Allow Click Delete Button RM','',0,'\\270405','\\2704',0,'0',0," . $params['levelid'] . "),
            (3656,0,'Allow Click Print Button RM','',0,'\\270406','\\2704',0,'0',0," . $params['levelid'] . "),
            (3657,0,'Allow Click Lock Button RM','',0,'\\270407','\\2704',0,'0',0," . $params['levelid'] . "),
            (3658,0,'Allow Click UnLock Button RM','',0,'\\270408','\\2704',0,'0',0," . $params['levelid'] . "),
            (3659,0,'Allow Click Post Button RM','',0,'\\270409','\\2704',0,'0',0," . $params['levelid'] . "),
            (3660,0,'Allow Click UnPost Button RM','',0,'\\270410','\\2704',0,'0',0," . $params['levelid'] . "),
            (3661,0,'Allow Click Add Item RM','',0,'\\270411','\\2704',0,'0',0," . $params['levelid'] . "),
            (3662,0,'Allow Click Delete Item RM','',0,'\\270412','\\2704',0,'0',0," . $params['levelid'] . "),
            (3663,0,'Allow Change Amount RM','',0,'\\270413','\\2704',0,'0',0," . $params['levelid'] . "),
            (3664,0,'Allow Click Edit Item RM','',0,'\\270414','\\2704',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RM','/module/production/rm','" . $label . "','fa fa-sync sub_menu_ico',3650," . $params['levelid'] . ")";
    }

    public function rn($params, $parent, $sort)
    {
        $label = 'Supplies Issuance';

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3791,0,'" . $label . "','',0,'\\2705','$parent',0,'0',0," . $params['levelid'] . "),
            (3792,0,'Allow View Transaction SI','RN',0,'\\270501','\\2705',0,'0',0," . $params['levelid'] . "),
            (3793,0,'Allow Click Edit Button SI','',0,'\\270502','\\2705',0,'0',0," . $params['levelid'] . "),
            (3794,0,'Allow Click New Button SI','',0,'\\270503','\\2705',0,'0',0," . $params['levelid'] . "),
            (3795,0,'Allow Click Save Button SI','',0,'\\270504','\\2705',0,'0',0," . $params['levelid'] . "),
            (3796,0,'Allow Click Delete Button SI','',0,'\\270505','\\2705',0,'0',0," . $params['levelid'] . "),
            (3797,0,'Allow Click Print Button SI','',0,'\\270506','\\2705',0,'0',0," . $params['levelid'] . "),
            (3798,0,'Allow Click Lock Button SI','',0,'\\270507','\\2705',0,'0',0," . $params['levelid'] . "),
            (3799,0,'Allow Click UnLock Button SI','',0,'\\270508','\\2705',0,'0',0," . $params['levelid'] . "),
            (3800,0,'Allow Click Post Button SI','',0,'\\270509','\\2705',0,'0',0," . $params['levelid'] . "),
            (3801,0,'Allow Click UnPost Button SI','',0,'\\270510','\\2705',0,'0',0," . $params['levelid'] . "),
            (3802,0,'Allow Click Add Item SI','',0,'\\270511','\\2705',0,'0',0," . $params['levelid'] . "),
            (3803,0,'Allow Click Delete Item SI','',0,'\\270512','\\2705',0,'0',0," . $params['levelid'] . "),
            (3804,0,'Allow Change Amount SI','',0,'\\270513','\\2705',0,'0',0," . $params['levelid'] . "),
            (3805,0,'Allow Click Edit Item SI','',0,'\\270514','\\2705',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RN','/module/production/rn','" . $label . "','fa fa-sync sub_menu_ico',3791," . $params['levelid'] . ")";
    }

    public function prodorder($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3672,0,'Production Order','',0,'\\3002','$parent',0,'0',0," . $params['levelid'] . "),
            (3673,0,'Allow View Transaction PD','PD',0,'\\300201','\\3002',0,'0',0," . $params['levelid'] . "),
            (3674,0,'Allow Click Edit Button PD','',0,'\\300202','\\3002',0,'0',0," . $params['levelid'] . "),
            (3675,0,'Allow Click New Button PD','',0,'\\300203','\\3002',0,'0',0," . $params['levelid'] . "),
            (3676,0,'Allow Click Save Button PD','',0,'\\300204','\\3002',0,'0',0," . $params['levelid'] . "),
            (3677,0,'Allow Click Delete Button PD','',0,'\\300205','\\3002',0,'0',0," . $params['levelid'] . "),
            (3678,0,'Allow Click Print Button PD','',0,'\\300206','\\3002',0,'0',0," . $params['levelid'] . "),
            (3679,0,'Allow Click Lock Button PD','',0,'\\300207','\\3002',0,'0',0," . $params['levelid'] . "),
            (3680,0,'Allow Click UnLock Button PD','',0,'\\300208','\\3002',0,'0',0," . $params['levelid'] . "),
            (3681,0,'Allow Click Post Button PD','',0,'\\300209','\\3002',0,'0',0," . $params['levelid'] . "),
            (3682,0,'Allow Click UnPost Button PD','',0,'\\300210','\\3002',0,'0',0," . $params['levelid'] . "),
            (3683,0,'Allow Click Add Item PD','',0,'\\300211','\\3002',0,'0',0," . $params['levelid'] . "),
            (3684,0,'Allow Click Delete Item PD','',0,'\\300212','\\3002',0,'0',0," . $params['levelid'] . "),
            (3685,0,'Allow Click Edit Item PD','',0,'\\300213','\\3002',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PD','/module/production/pd','Production Order','fa fa-sync sub_menu_ico',3672," . $params['levelid'] . ")";
    }

    public function finishgoodsentry($params, $parent, $sort)
    {
        switch ($params['companyid']) {
            case 24: //goodfound
                $label = 'Finish Items';
                break;
            default:
                $label = 'Finish Goods Entry';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3691,0,'" . $label . "','',0,'\\3003','$parent',0,'0',0," . $params['levelid'] . "),
            (3692,0,'Allow View Transaction FG','FG',0,'\\300301','\\3003',0,'0',0," . $params['levelid'] . "),
            (3693,0,'Allow Click Edit Button FG','',0,'\\300302','\\3003',0,'0',0," . $params['levelid'] . "),
            (3694,0,'Allow Click New Button FG','',0,'\\300303','\\3003',0,'0',0," . $params['levelid'] . "),
            (3695,0,'Allow Click Save Button FG','',0,'\\300304','\\3003',0,'0',0," . $params['levelid'] . "),
            (3696,0,'Allow Click Delete Button FG','',0,'\\300305','\\3003',0,'0',0," . $params['levelid'] . "),
            (3697,0,'Allow Click Print Button FG','',0,'\\300306','\\3003',0,'0',0," . $params['levelid'] . "),
            (3698,0,'Allow Click Lock Button FG','',0,'\\300307','\\3003',0,'0',0," . $params['levelid'] . "),
            (3699,0,'Allow Click UnLock Button FG','',0,'\\300308','\\3003',0,'0',0," . $params['levelid'] . "),
            (3700,0,'Allow Click Post Button FG','',0,'\\300309','\\3003',0,'0',0," . $params['levelid'] . "),
            (3701,0,'Allow Click UnPost Button FG','',0,'\\300310','\\3003',0,'0',0," . $params['levelid'] . "),
            (3702,0,'Allow Click Add Item FG','',0,'\\300311','\\3003',0,'0',0," . $params['levelid'] . "),
            (3703,0,'Allow Click Delete Item FG','',0,'\\300312','\\3003',0,'0',0," . $params['levelid'] . "),
            (3704,0,'Allow Change Amount FG','',0,'\\300313','\\3003',0,'0',0," . $params['levelid'] . "),
            (3705,0,'Allow Click Edit Item FG','',0,'\\300314','\\3003',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'FG','/module/production/fg','" . $label . "','fa fa-box sub_menu_ico',3691," . $params['levelid'] . ")";
    }

    public function bom($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3814,0,'Finished Goods - BOM','',0,'\\3004','$parent',0,'0',0," . $params['levelid'] . "),
            (3815,0,'Allow View Transaction BOM','PI',0,'\\300401','\\3004',0,'0',0," . $params['levelid'] . "),
            (3816,0,'Allow Click Edit Button BOM','',0,'\\300402','\\3004',0,'0',0," . $params['levelid'] . "),
            (3817,0,'Allow Click Save Button BOM','',0,'\\300403','\\3004',0,'0',0," . $params['levelid'] . "),
            (3818,0,'Allow Click Add Item BOM','',0,'\\300404','\\3004',0,'0',0," . $params['levelid'] . "),
            (3819,0,'Allow Click Delete Item BOM','',0,'\\300405','\\3004',0,'0',0," . $params['levelid'] . "),
            (3820,0,'Allow Change Amount BOM','',0,'\\300406','\\3004',0,'0',0," . $params['levelid'] . "),
            (3821,0,'Allow Click Edit Item BOM','',0,'\\300407','\\3004',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BOM','/ledgergrid/production/bom','Finished Goods - BOM','fa fa-list-alt sub_menu_ico',3814," . $params['levelid'] . ")";
    }

    public function jp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3822,0,'Job Order','',0,'\\3005','$parent',0,'0',0," . $params['levelid'] . "),
        (3823,0,'Allow View Transaction JO','JP',0,'\\300501','\\3005',0,'0',0," . $params['levelid'] . "),
        (3824,0,'Allow Click Edit Button JO','JP',0,'\\300502','\\3005',0,'0',0," . $params['levelid'] . "),
        (3825,0,'Allow Click New  Button JO','JP',0,'\\300503','\\3005',0,'0',0," . $params['levelid'] . "),
        (3826,0,'Allow Click Save Button JO','JP',0,'\\300504','\\3005',0,'0',0," . $params['levelid'] . "),
        (3827,0,'Allow Click Delete Button JO','JP',0,'\\300506','\\3005',0,'0',0," . $params['levelid'] . "),
        (3828,0,'Allow Click Print Button JO','JP',0,'\\300507','\\3005',0,'0',0," . $params['levelid'] . "),
        (3829,0,'Allow Click Lock Button JO','JP',0,'\\300508','\\3005',0,'0',0," . $params['levelid'] . "),
        (3830,0,'Allow Click UnLock Button JO','JP',0,'\\300509','\\3005',0,'0',0," . $params['levelid'] . "),
        (3831,0,'Allow Click Post Button JO','JP',0,'\\300510','\\3005',0,'0',0," . $params['levelid'] . "),
        (3832,0,'Allow Click UnPost  Button JO','JP',0,'\\300511','\\3005',0,'0',0," . $params['levelid'] . "),
        (3833,0,'Allow View Transaction Accounting JO','JP',0,'\\300512','\\3005',0,'0',0," . $params['levelid'] . "),
        (3834,1,'Allow Click Add Item JO','',0,'\\300513','\\3005',0,'0',0," . $params['levelid'] . "),
        (3835,1,'Allow Click Edit Item JO','',0,'\\300514','\\3005',0,'0',0," . $params['levelid'] . "),
        (3836,1,'Allow Click Delete Item JO','',0,'\\300515','\\3005',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'JP','/module/production/jp','Job Order','fa fa-people-carry sub_menu_ico',3822," . $params['levelid'] . ")";
    } //end function

    public function pg($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3837,0,'Production Input','',0,'\\3006','$parent',0,'0',0," . $params['levelid'] . ") ,
        (3838,0,'Allow View Transaction Prod. Input','JP',0,'\\300601','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3839,0,'Allow Click Edit Button  Prod. Input','',0,'\\300602','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3840,0,'Allow Click New Button Prod. Input','',0,'\\300603','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3841,0,'Allow Click Save Button Prod. Input','',0,'\\300604','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3842,0,'Allow Click Delete Button Prod. Input','',0,'\\300606','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3843,0,'Allow Click Print Button Prod. Input','',0,'\\300607','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3844,0,'Allow Click Lock Button Prod. Input','',0,'\\300608','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3845,0,'Allow Click UnLock Button Prod. Input','',0,'\\300609','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3846,0,'Allow Click Post Button Prod. Input','',0,'\\300610','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3847,0,'Allow Click UnPost Button Prod. Input','',0,'\\300611','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3848,0,'Allow View Transaction Accounting Prod. Input','',0,'\\300612','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3849,1,'Allow Click Add Item Prod. Input','',0,'\\300613','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3850,1,'Allow Click Edit Item Prod. Input','',0,'\\300614','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3851,1,'Allow Click Delete Item Prod. Input','',0,'\\300615','\\3006',0,'0',0," . $params['levelid'] . ") ,
        (3852,1,'Allow Change Amount Prod. Input','',0,'\\300616','\\3006',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PG','/module/production/pg','Production Input','fa fa-truck-loading sub_menu_ico',3837," . $params['levelid'] . ")";
    } //end function

    public function payments($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3756,1,'Payments','*129',0,'\\1111','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'payments','/tableentries/ati/entrypayments','Payments','fa fa-money-check-alt sub_menu_ico',3756," . $params['levelid'] . ")";
    } //end function

    public function uomlist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4499,1,'UOM List','',0,'\\1112','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'uomlist','/tableentries/ati/entryuomlist','UOM List','fa fa-list sub_menu_ico',4499," . $params['levelid'] . ")";
    } //end function

    public function conversionuom($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4500,1,'Conversion UOM','',0,'\\1113','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'conversionuom','/tableentries/ati/entryconversionuom','Conversion UOM','fa fa-list sub_menu_ico',4500," . $params['levelid'] . ")";
    } //end function

    public function allowancesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3757,1,'Allowance Setup','',0,'\\2018','$parent',0,0,0," . $params['levelid'] . ") ,
        (3758,1,'Allow Click Save Button Allowance Setup','',0,'\\201801','\\2018',0,0,0," . $params['levelid'] . ") ,
        (3759,1,'Allow Click Print Button Rate Setup','',0,'\\201802','\\2018',0,0,0," . $params['levelid'] . ") ,
        (3760,1,'Allow View Rate Setup','',0,'\\201803','\\2018',0,0,0," . $params['levelid'] . ") ,
        (3761,1,'Allow Click New Button Allowance Setup','',0,'\\201804','\\2018',0,0,0," . $params['levelid'] . ") ,
        (3762,1,'Allow Click Delete Button Allowance Setup','',0,'\\201805','\\2018',0,0,0," . $params['levelid'] . ") ,
        (3763,1,'Allow Click Edit Button Allowance Setup','',0,'\\201806','\\2018',0,0,0," . $params['levelid'] . "),
        (5599,1,'Allow Click end Button Allowance Setup','',0,'\\201807','\\2018',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'allowancesetup','/ledgergrid/payroll/allowancesetup','Allowance Setup','fa fa-money-bill sub_menu_ico',3757," . $params['levelid'] . ")";
    } //end function

    public function requesttype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3765,0,'Request Type','',0,'\\821','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'requesttype','/tableentries/othersettings/entryrequesttype','Request Type','fa fa-tags sub_menu_ico',3765," . $params['levelid'] . ")";
    } //end function

    public function itemgroupqoutasetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3857,0,'Item Group Qouta Setup','',0,'\\520','$parent',0,'0',0," . $params['levelid'] . ") ,
        (3858,0,'Allow View Transaction','Item Group Qouta Setup',0,'\\52001','\\520',0,'0',0," . $params['levelid'] . ") ,
        (3859,0,'Allow Create Item Group Qouta','',0,'\\52002','\\520',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ITEMGROUP','/headtable/sales/itemgroupqoutasetup','Item Group Qouta Setup','fa fa-solid fa-object-ungroup sub_menu_ico',3857," . $params['levelid'] . ")";
    } //end function

    public function salesgroupqouta($params, $parent, $sort) // sales per item group per sales agent
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3860,0,'Sales Person Qouta','',0,'\\521','$parent',0,'0',0," . $params['levelid'] . ") ,
        (3861,0,'Allow View Transaction','Sales Group Qouta',0,'\\52101','\\521',0,'0',0," . $params['levelid'] . ") ,
        (3862,0,'Allow Create Sales Group Qouta','',0,'\\52102','\\521',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SALESGROUP','/headtable/sales/salesgroupqouta','Sales Person Quota','fa fa-object-ungroup sub_menu_ico',3860," . $params['levelid'] . ")";
    } //end function

    public function costcodes($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3867,1,'Cost Codes','*129',0,'\\1112','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'costcodes','/tableentries/tableentry/entrycostcodes','Cost Codes','fa fa-list sub_menu_ico',3867," . $params['levelid'] . ")";
    } //end function


    public function emptimecardperday($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4582,1,'Employee`s Timecard Per Day','',0,'\\2027','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'emptimecardperday','/headtable/payrollcustomform/emptimecardperday','Employee`s Timecard Per Day','fa fa-calendar-day sub_menu_ico',4582," . $params['levelid'] . ")";
    } //end function

    public function empprojectlog($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4525,1,'Daily Deployment Record - A','',0,'\\2019','$parent',0,0,0," . $params['levelid'] . ") ,
        (4526,1,'Allow View Daily Deployment Record','',0,'\\201901','\\2019',0,0,0," . $params['levelid'] . ") ,
        (4527,1,'Allow Click Button Save','',0,'\\201902','\\2019',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'empprojectlog','/headtable/payrollcustomform/empprojectlog','Daily Deployment Record - A','fa fa-calendar-day sub_menu_ico',4525," . $params['levelid'] . ")";
    } //end function
    public function empprojectlogb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4602,1,'Daily Deployment Record - B','',0,'\\2025','$parent',0,0,0," . $params['levelid'] . ") ,
        (4603,1,'Allow View Daily Deployment Record','',0,'\\202501','\\2025',0,0,0," . $params['levelid'] . ") ,
        (4604,1,'Allow Click Button Save','',0,'\\202502','\\2025',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'empprojectlogb','/headtable/payrollcustomform/empprojectlogb','Daily Deployment Record - B','fa fa-calendar-day sub_menu_ico',4602," . $params['levelid'] . ")";
    } //end function
    public function fs($params, $parent, $sort) // financing setup
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3892,0,'Financing Setup','',0,'\\310','$parent',0,'0',0," . $params['levelid'] . ") ,
        (3893,0,'Allow View Transaction FS','FS',0,'\\31001','\\706',0,'0',0," . $params['levelid'] . ") ,
        (3895,0,'Allow Click Edit Button  FS','',0,'\\31002','\\706',0,'0',0," . $params['levelid'] . ") ,
        (3896,0,'Allow Click New Button FS','',0,'\\31003','\\706',0,'0',0," . $params['levelid'] . ") ,
        (3897,0,'Allow Click Save Button FS','',0,'\\31004','\\706',0,'0',0," . $params['levelid'] . ") ,
        (3898,0,'Allow Click Delete Button FS','',0,'\\31006','\\706',0,'0',0," . $params['levelid'] . ") ,
        (3899,0,'Allow Click Print Button FS','',0,'\\31007','\\706',0,'0',0," . $params['levelid'] . ") ,
        (3900,0,'Allow Click Lock Button FS','',0,'\\31008','\\706',0,'0',0," . $params['levelid'] . ") ,
        (3901,0,'Allow Click UnLock Button FS','',0,'\\31009','\\706',0,'0',0," . $params['levelid'] . ") ,
        (3902,0,'Allow Click Post Button FS','',0,'\\31010','\\706',0,'0',0," . $params['levelid'] . ") ,
        (3903,0,'Allow Click UnPost Button FS','',0,'\\31011','\\706',0,'0',0," . $params['levelid'] . "),
        (3905,0,'Allow Click Delete Accounts FS','',0,'\\31012','\\706',0,'0',0," . $params['levelid'] . "),
        (3904,0,'Allow Click Generate Sched Button FS','',0,'\\31013','\\706',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'FS','/module/receivable/fs','Financing Setup','fas fa-wallet sub_menu_ico',3892," . $params['levelid'] . ")";
    } //end function

    public function rc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3906,0,'Received Checks','',0,'\\309','$parent',0,'0',0," . $params['levelid'] . ") ,
        (3907,0,'Allow View Transaction RC ','RC',0,'\\30901','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3908,0,'Allow Click Edit Button  RC ','',0,'\\30902','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3909,0,'Allow Click New Button RC ','',0,'\\30903','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3910,0,'Allow Click Save Button RC ','',0,'\\30904','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3911,0,'Allow Click Delete Button RC ','',0,'\\30906','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3912,0,'Allow Click Print Button RC ','',0,'\\30907','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3913,0,'Allow Click Lock Button RC ','',0,'\\30908','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3914,0,'Allow Click UnLock Button RC ','',0,'\\30909','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3915,0,'Allow Click Post Button RC ','',0,'\\30910','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3916,0,'Allow Click UnPost Button RC ','',0,'\\30911','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3917,0,'Allow Click Add Account RC','',0,'\\30912','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3918,0,'Allow Click Edit Account RC','',0,'\\30913','\\309',0,'0',0," . $params['levelid'] . ") ,
        (3919,0,'Allow Click Delete Account RC','',0,'\\30914','\\309',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RC','/module/receivable/rc','Received Checks','fa fa-credit-card sub_menu_ico',3906," . $params['levelid'] . ")";
    } //end function

    public function rh($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5421,0,'Received Cash','',0,'\\316','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5422,0,'Allow View Transaction Received Cash ','RC',0,'\\31601','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5423,0,'Allow Click Edit Button Received Cash ','',0,'\\31602','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5424,0,'Allow Click New Button Received Cash ','',0,'\\31603','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5425,0,'Allow Click Save Button Received Cash ','',0,'\\31604','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5426,0,'Allow Click Delete Button Received Cash ','',0,'\\31605','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5427,0,'Allow Click Print Button Received Cash ','',0,'\\31606','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5428,0,'Allow Click Lock Button Received Cash ','',0,'\\31607','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5429,0,'Allow Click UnLock Button Received Cash ','',0,'\\31608','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5430,0,'Allow Click Post Button Received Cash ','',0,'\\31609','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5431,0,'Allow Click UnPost Button Received Cash ','',0,'\\31610','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5432,0,'Allow Click Add Account Received Cash','',0,'\\31611','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5433,0,'Allow Click Edit Account Received Cash','',0,'\\31612','\\316',0,'0',0," . $params['levelid'] . ") ,
        (5434,0,'Allow Click Delete Account Received Cash','',0,'\\31613','\\316',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RH','/module/receivable/rh','Received Cash','fa fa-money-bill-alt sub_menu_ico',5421," . $params['levelid'] . ")";
    } //end function

    public function rd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5303,0,'Deposit Slip','',0,'\\313','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5304,0,'Allow View Transaction RD ','RD',0,'\\31301','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5305,0,'Allow Click Edit Button RD ','',0,'\\31302','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5306,0,'Allow Click New Button RD ','',0,'\\31303','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5307,0,'Allow Click Save Button RD ','',0,'\\31304','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5308,0,'Allow Click Delete Button RD ','',0,'\\31306','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5309,0,'Allow Click Print Button RD ','',0,'\\31307','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5310,0,'Allow Click Lock Button RD ','',0,'\\31308','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5311,0,'Allow Click UnLock Button RD ','',0,'\\31309','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5312,0,'Allow Click Post Button RD ','',0,'\\31310','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5313,0,'Allow Click UnPost Button RD ','',0,'\\31311','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5314,0,'Allow Click Received Check Button','',0,'\\31312','\\313',0,'0',0," . $params['levelid'] . ") ,
        (5315,0,'Allow Click Delete Check','',0,'\\31314','\\313',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RD','/module/receivable/rd','Deposit Slip','fa fa-edit sub_menu_ico',5303," . $params['levelid'] . ")";
    } //end function

    public function be($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5316,0,'Bounced Cheque Entry','',0,'\\314','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5317,0,'Allow View Transaction BE ','BE',0,'\\31401','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5318,0,'Allow Click Edit Button BE ','',0,'\\31402','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5319,0,'Allow Click New Button BE ','',0,'\\31403','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5320,0,'Allow Click Save Button BE ','',0,'\\31404','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5321,0,'Allow Click Delete Button BE ','',0,'\\31406','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5322,0,'Allow Click Print Button BE ','',0,'\\31407','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5323,0,'Allow Click Lock Button BE ','',0,'\\31408','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5324,0,'Allow Click UnLock Button BE ','',0,'\\31409','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5325,0,'Allow Click Post Button BE ','',0,'\\31410','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5326,0,'Allow Click UnPost Button BE ','',0,'\\31411','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5327,0,'Allow Click Add Cheque BE','',0,'\\31412','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5328,0,'Allow Click Edit Cheque BE','',0,'\\31413','\\314',0,'0',0," . $params['levelid'] . ") ,
        (5329,0,'Allow Click Delete Cheque BE','',0,'\\31414','\\314',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BE','/module/receivable/be','Bounced Cheque Entry','fa fa-credit-card sub_menu_ico',5316," . $params['levelid'] . ")";
    } //end function

    public function re($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5330,0,'Replacement Cheque','',0,'\\315','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5331,0,'Allow View Transaction RE ','KR',0,'\\31501','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5332,0,'Allow Click Edit Button RE ','',0,'\\31502','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5333,0,'Allow Click New Button RE','',0,'\\31503','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5334,0,'Allow Click Save Button RE ','',0,'\\31504','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5335,0,'Allow Click Delete Button RE ','',0,'\\31506','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5336,0,'Allow Click Print Button RE ','',0,'\\31507','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5337,0,'Allow Click Lock Button RE ','',0,'\\31508','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5338,0,'Allow Click UnLock Button RE ','',0,'\\31509','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5339,0,'Allow Click Post Button RE ','',0,'\\31510','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5340,0,'Allow Click UnPost Button RE ','',0,'\\31511','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5341,0,'Allow Click Bounced Cheque Button','',0,'\\31512','\\315',0,'0',0," . $params['levelid'] . ") ,
        (5342,0,'Allow Click Delete Bounced Cheque','',0,'\\31513','\\315',0,'0',0," . $params['levelid'] . "),
        (5344,0,'Allow Click Save Account Button','',0,'\\31514','\\315',0,'0',0," . $params['levelid'] . ") ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RE','/module/receivable/re','Replacement Cheque','fa fa-edit sub_menu_ico',5330," . $params['levelid'] . ")";
    } //end function

    public function pu($params, $parent, $sort)
    {
        $companyid = $params['companyid'];

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3922,0,'Material Purchase Order','',0,'\\417','$parent',0,'0',0," . $params['levelid'] . "),
        (3923,0,'Allow View Transaction PU','PU',0,'\\41701','\\417',0,'0',0," . $params['levelid'] . "),
        (3924,0,'Allow Click Edit Button PU','',0,'\\41702','\\417',0,'0',0," . $params['levelid'] . "),
        (3925,0,'Allow Click New Button PU','',0,'\\41703','\\417',0,'0',0," . $params['levelid'] . "),
        (3926,0,'Allow Click Save Button PU','',0,'\\41704','\\417',0,'0',0," . $params['levelid'] . "),
        (3927,0,'Allow Click Delete Button PU','',0,'\\41706','\\417',0,'0',0," . $params['levelid'] . "),
        (3928,0,'Allow Click Print Button PU','',0,'\\41707','\\417',0,'0',0," . $params['levelid'] . "),
        (3929,0,'Allow Click Lock Button PU','',0,'\\41708','\\417',0,'0',0," . $params['levelid'] . "),
        (3930,0,'Allow Click UnLock Button PU','',0,'\\41709','\\417',0,'0',0," . $params['levelid'] . "),
        (3931,0,'Allow Change Amount PU','',0,'\\41710','\\417',0,'0',0," . $params['levelid'] . "),
        (3932,0,'Allow Click Post Button PU','',0,'\\41712','\\417',0,'0',0," . $params['levelid'] . "),
        (3933,0,'Allow Click UnPost  Button PU','',0,'\\41713','\\417',0,'0',0," . $params['levelid'] . "),
        (3934,1,'Allow Click Add Item PU','',0,'\\41714','\\417',0,'0',0," . $params['levelid'] . "),
        (3935,1,'Allow Click Edit Item PU','',0,'\\41715','\\417',0,'0',0," . $params['levelid'] . "),
        (3936,1,'Allow Click Delete Item PU','',0,'\\41716','\\417',0,'0',0," . $params['levelid'] . "),
        (3937,1,'Allow View Amount PU','',0,'\\41717','\\417',0,'0',0," . $params['levelid'] . "),
        (4592,1,'Allow Click PR Button PU','',0,'\\41718','\\417',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        return "($sort,$p,'PU','/module/" . $folder . "/pu','Material Purchase Order','fa fa-folder sub_menu_ico',3922," . $params['levelid'] . ")";
    } //end function

    public function ru($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Material Receiving Report';

        $qry = "(3938,0,'" . $label . "','',0,'\\418','$parent',0,'0',0," . $params['levelid'] . "),
        (3939,0,'Allow View Transaction RU','RU',0,'\\41801','\\418',0,'0',0," . $params['levelid'] . "),
        (3940,0,'Allow Click Edit Button RU','',0,'\\41802','\\418',0,'0',0," . $params['levelid'] . "),
        (3941,0,'Allow Click New Button RU','',0,'\\41803','\\418',0,'0',0," . $params['levelid'] . "),
        (3942,0,'Allow Click Save Button RU','',0,'\\41804','\\418',0,'0',0," . $params['levelid'] . "),
        (3943,0,'Allow Click Delete Button RU','',0,'\\41806','\\418',0,'0',0," . $params['levelid'] . "),
        (3944,0,'Allow Click Print Button RU','',0,'\\41807','\\418',0,'0',0," . $params['levelid'] . "),
        (3945,0,'Allow Click Lock Button RU','',0,'\\41808','\\418',0,'0',0," . $params['levelid'] . "),
        (3946,0,'Allow Click UnLock Button RU','',0,'\\41809','\\418',0,'0',0," . $params['levelid'] . "),
        (3947,0,'Allow Click Post Button RU','',0,'\\41810','\\418',0,'0',0," . $params['levelid'] . "),
        (3948,0,'Allow Click UnPost Button RU','',0,'\\41811','\\418',0,'0',0," . $params['levelid'] . "),
        (3949,0,'Allow View Transaction accounting RU','',0,'\\41812','\\418',0,'0',0," . $params['levelid'] . "),
        (3950,0,'Allow Change Amount RU','',0,'\\41813','\\418',0,'0',0," . $params['levelid'] . "),
        (3951,1,'Allow Click Add Item RU','',0,'\\41814','\\418',0,'0',0," . $params['levelid'] . "),
        (3952,1,'Allow Click Edit Item RU','',0,'\\41815','\\418',0,'0',0," . $params['levelid'] . "),
        (3953,1,'Allow Click Delete Item RU','',0,'\\41816','\\418',0,'0',0," . $params['levelid'] . ")";

        $folder = 'purchase';

        $this->insertattribute($params, $qry);
        return "($sort,$p,'RU','/module/" . $folder . "/ru','" . $label . "','fa fa-folder-open sub_menu_ico',3938," . $params['levelid'] . ")";
    } //end function

    public function plangroup($params, $parent, $sort)
    {

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4038,1,'Plan Group','',0,'\\1113','$parent',0,'0',0," . $params['levelid'] . "),
        (4097,0,'Allow Add Plan Types','AR',0,'\\111301','\\1113',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'plangroup','/tableentries/tableentry/entryplangroup','Plan Group','fa fa-tasks sub_menu_ico',4038," . $params['levelid'] . ")";
    } //end function


    public function af($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4039,0,'Application Form','',0,'\\3007','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4040,0,'Allow View Transaction AF','AR',0,'\\300701','\\3007',0,'0',0," . $params['levelid'] . ") ,
        (4041,0,'Allow Click Edit Button  AF','',0,'\\300702','\\3007',0,'0',0," . $params['levelid'] . ") ,
        (4042,0,'Allow Click New Button AF','',0,'\\300703','\\3007',0,'0',0," . $params['levelid'] . ") ,
        (4043,0,'Allow Click Save Button AF','',0,'\\300704','\\3007',0,'0',0," . $params['levelid'] . ") ,
        (4044,0,'Allow Click Delete Button AF','',0,'\\300706','\\3007',0,'0',0," . $params['levelid'] . ") ,
        (4045,0,'Allow Click Print Button AF','',0,'\\300707','\\3007',0,'0',0," . $params['levelid'] . ") ,
        (4046,0,'Allow Click Lock Button AF','',0,'\\300708','\\3007',0,'0',0," . $params['levelid'] . ") ,
        (4047,0,'Allow Click UnLock Button AF','',0,'\\300709','\\3007',0,'0',0," . $params['levelid'] . ") ,
        (4048,0,'Allow Click Post Button AF','',0,'\\300710','\\3007',0,'0',0," . $params['levelid'] . ") ,
        (4049,0,'Allow Click UnPost Button AF','',0,'\\300711','\\3007',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AF','/module/operation/af','Application Form','fa fa-file-alt sub_menu_ico',4039," . $params['levelid'] . ")";
    } //end function

    public function cp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4073,0,'Life Plan Agreement','',0,'\\3008','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4060,0,'Allow View Transaction CP','CP',0,'\\300801','\\3008',0,'0',0," . $params['levelid'] . ") ,
        (4061,0,'Allow Click Edit Button  CP','',0,'\\300802','\\3008',0,'0',0," . $params['levelid'] . ") ,
        (4062,0,'Allow Click New Button CP','',0,'\\300803','\\3008',0,'0',0," . $params['levelid'] . ") ,
        (4063,0,'Allow Click Save Button CP','',0,'\\300804','\\3008',0,'0',0," . $params['levelid'] . ") ,
        (4064,0,'Allow Click Delete Button CP','',0,'\\300806','\\3008',0,'0',0," . $params['levelid'] . ") ,
        (4065,0,'Allow Click Print Button CP','',0,'\\300807','\\3008',0,'0',0," . $params['levelid'] . ") ,
        (4066,0,'Allow Click Lock Button CP','',0,'\\300808','\\3008',0,'0',0," . $params['levelid'] . ") ,
        (4067,0,'Allow Click UnLock Button CP','',0,'\\300809','\\3008',0,'0',0," . $params['levelid'] . ") ,
        (4068,0,'Allow Click Post Button CP','',0,'\\300810','\\3008',0,'0',0," . $params['levelid'] . ") ,
        (4069,0,'Allow Click UnPost Button CP','',0,'\\300811','\\3008',0,'0',0," . $params['levelid'] . "),
        (4172,0,'Allow Click Print Certificate of Full Payment Button CP','',0,'\\300811','\\3008',0,'0',0," . $params['levelid'] . ") ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CP','/module/operation/cp','Life Plan Agreement','fa fa-clipboard-list sub_menu_ico',4073," . $params['levelid'] . ")";
    } //end function

    public function aquastockcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Meter Master';

        $qry = "(4104,0,'" . $label . "','',0,'\\124','$parent',0,'0',0," . $params['levelid'] . "),
        (4105,0,'Allow View " . $label . "','SK',0,'\\12401','\\124',0,'0',0," . $params['levelid'] . "),
        (4106,0,'Allow Click Edit Button Meter Master','',0,'\\12402','\\124',0,'0',0," . $params['levelid'] . "),
        (4107,0,'Allow Click New Button Meter Master','',0,'\\12403','\\124',0,'0',0," . $params['levelid'] . "),
        (4108,0,'Allow Click Save Button Meter Master','',0,'\\12404','\\124',0,'0',0," . $params['levelid'] . "),
        (4110,0,'Allow Click Delete Button Meter Master','',0,'\\12406','\\124',0,'0',0," . $params['levelid'] . "),
        (4111,0,'Allow Print Button Meter Master','',0,'\\12407','\\124',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);

        return "($sort,$p,'stockcard','/ledgergrid/waterbilling/stockcard','" . $label . "','fas fa-tachometer-alt sub_menu_ico',4104," . $params['levelid'] . ")";
    } //end function

    public function wn($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        $modulename = 'Water Connection';

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4112,0,'" . $modulename . "','',0,'\\3009','$parent',0,'0',0," . $params['levelid'] . "),
        (4113,0,'Allow View Transaction WN','WN',0,'\\300901','\\3009',0,'0',0," . $params['levelid'] . "),
        (4114,0,'Allow Click Edit Button WN','',0,'\\300902','\\3009',0,'0',0," . $params['levelid'] . "),
        (4115,0,'Allow Click New Button WN','',0,'\\300903','\\3009',0,'0',0," . $params['levelid'] . "),
        (4116,0,'Allow Click Save Button WN','',0,'\\300904','\\3009',0,'0',0," . $params['levelid'] . "),
        (4117,0,'Allow Click Delete Button WN','',0,'\\300906','\\3009',0,'0',0," . $params['levelid'] . "),
        (4118,0,'Allow Click Print Button WN','',0,'\\300907','\\3009',0,'0',0," . $params['levelid'] . "),
        (4119,0,'Allow Click Lock Button WN','',0,'\\300908','\\3009',0,'0',0," . $params['levelid'] . "),
        (4120,0,'Allow Click UnLock Button WN','',0,'\\300909','\\3009',0,'0',0," . $params['levelid'] . "),
        (4121,0,'Allow Click Post Button WN','',0,'\\300910','\\3009',0,'0',0," . $params['levelid'] . "),
        (4109,0,'Allow Click UnPost  Button WN','',0,'\\300911','\\3009',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);

        return "($sort,$p,'wn','/module/waterbilling/wn','" . $modulename . "','fa fa-shower sub_menu_ico',4112," . $params['levelid'] . ")";
    } //end function

    public function wm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4124,0,'Consumption','',0,'\\3010','$parent',0,'0',0," . $params['levelid'] . "),
        (4125,0,'Allow View Transaction','WM',0,'\\301001','\\3010',0,'0',0," . $params['levelid'] . "),
        (4126,0,'Allow Click Edit Button','',0,'\\301002','\\3010',0,'0',0," . $params['levelid'] . "),
        (4127,0,'Allow Click New  Button','',0,'\\301003','\\3010',0,'0',0," . $params['levelid'] . "),
        (4128,0,'Allow Click Save Button','',0,'\\301004','\\3010',0,'0',0," . $params['levelid'] . "),
        (4129,0,'Allow Click Delete Button','',0,'\\301006','\\3010',0,'0',0," . $params['levelid'] . "),
        (4130,0,'Allow Click Print Button','',0,'\\301007','\\3010',0,'0',0," . $params['levelid'] . "),
        (4131,0,'Allow Click Lock Button','',0,'\\301008','\\3010',0,'0',0," . $params['levelid'] . "),
        (4132,0,'Allow Click UnLock Button','',0,'\\301009','\\3010',0,'0',0," . $params['levelid'] . "),
        (4133,0,'Allow Click Post Button','',0,'\\301010','\\3010',0,'0',0," . $params['levelid'] . "),
        (4134,0,'Allow Click UnPost Button','',0,'\\301011','\\3010',0,'0',0," . $params['levelid'] . "),
        (4135,0,'Allow Change Amount','',0,'\\301013','\\3010',0,'0',0," . $params['levelid'] . "),
        (4136,0,'Allow View Transaction Accounting','',0,'\\301016','\\3010',0,'0',0," . $params['levelid'] . "),
        (4137,1,'Allow Click Add Item','',0,'\\301017','\\3010',0,'0',0," . $params['levelid'] . "),
        (4138,1,'Allow Click Edit Item','',0,'\\301018','\\3010',0,'0',0," . $params['levelid'] . "),
        (4139,1,'Allow Click Delete Item','',0,'\\301019','\\3010',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);

        return "($sort,$p,'WM','/module/waterbilling/wm','Consumption','fas fa-tachometer-alt sub_menu_ico',4124," . $params['levelid'] . ")";
    } //end function


    public function mm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4436,0,'Merging Barcode','',0,'\\3026','$parent',0,'0',0," . $params['levelid'] . "),
        (4437,0,'Allow View Transaction','MM',0,'\\302601','\\3026',0,'0',0," . $params['levelid'] . "),
        (4438,0,'Allow Click Edit Button','',0,'\\302602','\\3026',0,'0',0," . $params['levelid'] . "),
        (4439,0,'Allow Click New  Button','',0,'\\302603','\\3026',0,'0',0," . $params['levelid'] . "),
        (4440,0,'Allow Click Save Button','',0,'\\302604','\\3026',0,'0',0," . $params['levelid'] . "),
        (4441,0,'Allow Click Delete Button','',0,'\\302605','\\3026',0,'0',0," . $params['levelid'] . "),
        (4442,0,'Allow Click Print Button','',0,'\\302606','\\3026',0,'0',0," . $params['levelid'] . "),
        (4443,0,'Allow Click Lock Button','',0,'\\302607','\\3026',0,'0',0," . $params['levelid'] . "),
        (4444,0,'Allow Click UnLock Button','',0,'\\302608','\\3026',0,'0',0," . $params['levelid'] . "),
        (4445,0,'Allow Click Post Button','',0,'\\302609','\\3026',0,'0',0," . $params['levelid'] . "),
        (4446,0,'Allow Click UnPost Button','',0,'\\302610','\\3026',0,'0',0," . $params['levelid'] . "),
        (4447,1,'Allow Click Add Item','',0,'\\302611','\\3026',0,'0',0," . $params['levelid'] . "),
        (4448,1,'Allow Click Delete Item','',0,'\\302612','\\3026',0,'0',0," . $params['levelid'] . "),
        (4450,1,'Allow Click Edit Item','',0,'\\302613','\\3026',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);

        return "($sort,$p,'MM','/module/ati/mm','Merging Barcode','fas fa-clipboard-list sub_menu_ico',4436," . $params['levelid'] . ")";
    } //end function

    public function ci($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4458,0,'Spare Parts Issuance','',0,'\\3088','$parent',0,'0',0," . $params['levelid'] . "),
        (4459,0,'Allow View Transaction CI','CI',0,'\\308801','\\3088',0,'0',0," . $params['levelid'] . "),
        (4460,0,'Allow Click Edit Button CI','',0,'\\308802','\\3088',0,'0',0," . $params['levelid'] . "),
        (4461,0,'Allow Click New  Button CI','',0,'\\308803','\\3088',0,'0',0," . $params['levelid'] . "),
        (4462,0,'Allow Click Save Button CI','',0,'\\308804','\\3088',0,'0',0," . $params['levelid'] . "),
        (4463,0,'Allow Click Delete Button CI','',0,'\\308805','\\3088',0,'0',0," . $params['levelid'] . "),
        (4464,0,'Allow Click Print Button CI','',0,'\\308806','\\3088',0,'0',0," . $params['levelid'] . "),
        (4465,0,'Allow Click Lock Button CI','',0,'\\308807','\\3088',0,'0',0," . $params['levelid'] . "),
        (4466,0,'Allow Click UnLock Button CI','',0,'\\308808','\\3088',0,'0',0," . $params['levelid'] . "),
        (4467,0,'Allow Click Post Button CI','',0,'\\308809','\\3088',0,'0',0," . $params['levelid'] . "),
        (4468,0,'Allow Click UnPost  Button CI','',0,'\\308810','\\3088',0,'0',0," . $params['levelid'] . "),
        (4469,0,'Allow Change Amount  CI','',0,'\\308811','\\3088',0,'0',0," . $params['levelid'] . "),
        (4470,0,'Allow Check Credit Limit CI','',0,'\\308812','\\3088',0,'0',0," . $params['levelid'] . "),
        (4471,0,'Allow CI Amount Auto-Compute on UOM Change','',0,'\\308813','\\3088',0,'0',0," . $params['levelid'] . "),
        (4472,0,'Allow View Transaction Accounting CI','',0,'\\308814','\\3088',0,'0',0," . $params['levelid'] . "),
        (4473,1,'Allow Click Add Item CI','',0,'\\308815','\\3088',0,'0',0," . $params['levelid'] . "),
        (4474,1,'Allow Click Edit Item CI','',0,'\\308816','\\3088',0,'0',0," . $params['levelid'] . "),
        (4475,1,'Allow Click Delete Item CI','',0,'\\308817','\\3088',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'CI','/module/cdo/ci','Spare Parts Issuance','fa fa-boxes sub_menu_ico',4458," . $params['levelid'] . ")";
    } //end function

    public function ti($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4511,0,'Tripping Incentive','',0,'\\2019','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4512,0,'Allow View Transaction TI ','TI',0,'\\201901','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4513,0,'Allow Click Edit Button  TI ','',0,'\\201902','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4514,0,'Allow Click New Button TI ','',0,'\\201903','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4515,0,'Allow Click Save Button TI ','',0,'\\201904','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4516,0,'Allow Click Delete Button TI ','',0,'\\201905','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4517,0,'Allow Click Print Button TI ','',0,'\\201906','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4518,0,'Allow Click Lock Button TI ','',0,'\\201907','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4519,0,'Allow Click UnLock Button TI ','',0,'\\201908','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4520,0,'Allow Click Post Button TI ','',0,'\\201909','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4521,0,'Allow Click UnPost Button TI ','',0,'\\201910','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4522,0,'Allow Click Add Account TI','',0,'\\201911','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4523,0,'Allow Click Edit Account TI','',0,'\\201912','\\2019',0,'0',0," . $params['levelid'] . ") ,
        (4524,0,'Allow Click Delete Account TI','',0,'\\201913','\\2019',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'TI','/module/payroll/ti','Tripping Incentive','fa fa-calculator sub_menu_ico',4511," . $params['levelid'] . ")";
    } //end function



    public function sm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4529,0,'Supplier Invoice','',0,'\\2020','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4530,0,'Allow View Transaction SM ','SM',0,'\\202001','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4531,0,'Allow Click Edit Button  SM ','',0,'\\202002','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4532,0,'Allow Click New Button SM ','',0,'\\202003','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4533,0,'Allow Click Save Button SM ','',0,'\\202004','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4534,0,'Allow Click Delete Button SM ','',0,'\\202005','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4535,0,'Allow Click Print Button SM ','',0,'\\202006','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4536,0,'Allow Click Lock Button SM ','',0,'\\202007','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4537,0,'Allow Click UnLock Button SM ','',0,'\\202008','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4538,0,'Allow Click Post Button SM ','',0,'\\202009','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4539,0,'Allow Click UnPost Button SM ','',0,'\\202010','\\2020',0,'0',0," . $params['levelid'] . ") ,
        (4540,0,'Allow View Transaction accounting','',0,'\\202011','\\2020',0,'0',0," . $params['levelid'] . "),
        (4541,1,'Allow Click Add RR','',0,'\\202012','\\2020',0,'0',0," . $params['levelid'] . "),
        (4542,1,'Allow Click Delete Item','',0,'\\202013','\\2020',0,'0',0," . $params['levelid'] . "),
        (4572,1,'Allow Click Edit Item','',0,'\\202014','\\2020',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'SM','/module/cbbsi/sm','Supplier Invoice','fa fa-calculator sub_menu_ico',4529," . $params['levelid'] . ")";
    } //end function
    public function activitymaster($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4543,0,'Activity Master','',0,'\\2021','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'activitymaster','/tableentries/tableentry/entryactivitymaster','Activity Master','fa fa-tasks sub_menu_ico',4543," . $params['levelid'] . ")";
    } //end function
    public function eq($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4544,0,'Equipment Monitoring','',0,'\\2022','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4545,0,'Allow View Transaction EQ ','EQ',0,'\\202201','\\2022',0,'0',0," . $params['levelid'] . ") ,
        (4546,0,'Allow Click Edit Button  EQ ','',0,'\\202202','\\2022',0,'0',0," . $params['levelid'] . ") ,
        (4547,0,'Allow Click New Button EQ ','',0,'\\202203','\\2022',0,'0',0," . $params['levelid'] . ") ,
        (4548,0,'Allow Click Save Button EQ ','',0,'\\202204','\\2022',0,'0',0," . $params['levelid'] . ") ,
        (4549,0,'Allow Click Delete Button EQ ','',0,'\\202205','\\2022',0,'0',0," . $params['levelid'] . ") ,
        (4550,0,'Allow Click Print Button EQ ','',0,'\\202206','\\2022',0,'0',0," . $params['levelid'] . ") ,
        (4551,0,'Allow Click Lock Button EQ ','',0,'\\202207','\\2022',0,'0',0," . $params['levelid'] . ") ,
        (4552,0,'Allow Click UnLock Button EQ ','',0,'\\202208','\\2022',0,'0',0," . $params['levelid'] . ") ,
        (4553,0,'Allow Click Post Button EQ ','',0,'\\202209','\\2022',0,'0',0," . $params['levelid'] . ") ,
        (4554,0,'Allow Click UnPost Button EQ ','',0,'\\202210','\\2022',0,'0',0," . $params['levelid'] . "),
        (4555,1,'Allow Click Add Activity EQ','',0,'\\202211','\\2022',0,'0',0," . $params['levelid'] . "),
        (4556,1,'Allow Click Edit Activity EQ','',0,'\\202212','\\2022',0,'0',0," . $params['levelid'] . "),
        (4557,1,'Allow Click Delete Activity EQ','',0,'\\202213','\\2022',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'EQ','/module/sales/eq','Equipment Monitoring','fa fa-calculator sub_menu_ico',4544," . $params['levelid'] . ")";
    } //end function

    public function oi($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4558,0,'Operator Incentive','',0,'\\2023','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4559,0,'Allow View Transaction OI ','OI',0,'\\202301','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4560,0,'Allow Click Edit Button  OI ','',0,'\\202302','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4561,0,'Allow Click New Button OI ','',0,'\\202303','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4562,0,'Allow Click Save Button OI ','',0,'\\202304','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4563,0,'Allow Click Delete Button OI ','',0,'\\202305','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4564,0,'Allow Click Print Button OI ','',0,'\\202306','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4565,0,'Allow Click Lock Button OI ','',0,'\\202307','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4566,0,'Allow Click UnLock Button OI ','',0,'\\202308','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4567,0,'Allow Click Post Button OI ','',0,'\\202309','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4568,0,'Allow Click UnPost Button OI ','',0,'\\202310','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4569,0,'Allow Click Add Account OI','',0,'\\202311','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4570,0,'Allow Click Edit Account OI','',0,'\\202312','\\2023',0,'0',0," . $params['levelid'] . ") ,
        (4571,0,'Allow Click Delete Account OI','',0,'\\202313','\\2023',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'OI','/module/payroll/oi','Operator Incentive','fa fa-street-view sub_menu_ico',4558," . $params['levelid'] . ")";
    } //end function
    public function changeshiftapplication($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        // (2804,1,'My Info','',0,'\\2016','$parent',0,0,0," . $params['levelid'] . ") ,
        $qry = "(4596,1,'Change Shift Application Portal','',0,'\\2024','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4597,1,'Allow Click Edit Button  CS ','',0,'\\202401','\\2024',0,'0',0," . $params['levelid'] . ") ,
        (4598,1,'Allow Click New Button CS ','',0,'\\202402','\\2024',0,'0',0," . $params['levelid'] . ") ,
        (4599,1,'Allow Click Save Button CS ','',0,'\\202403','\\2024',0,'0',0," . $params['levelid'] . ") ,
        (4600,1,'Allow Click Delete Button CS ','',0,'\\202404','\\2024',0,'0',0," . $params['levelid'] . "),
        (4605,1,'Allow View Dashboard CS','',0,'\\202405','\\2024',0,0,0," . $params['levelid'] . "),
        (5036,1,'Allow View Dashboard Approved CS','',0,'\\202406','\\2024',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'changeshiftapplication','/ledgergrid/payroll/changeshiftapplication','Change Shift Application Portal','fa fa-calendar-alt sub_menu_ico',4596," . $params['levelid'] . ")";
    } //end function

    public function mc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4610,0,'MC Collection','',0,'\\2025','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4611,0,'Allow View Transaction MC', 'MC',0,'\\202501','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4612,0,'Allow Click Edit Button MC ','',0,'\\202502','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4613,0,'Allow Click New Button MC ','',0,'\\202503','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4614,0,'Allow Click Save Button MC ','',0,'\\202504','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4615,0,'Allow Click Delete Button MC ','',0,'\\202505','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4616,0,'Allow Click Print Button MC ','',0,'\\202506','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4617,0,'Allow Click Lock Button MC ','',0,'\\202507','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4618,0,'Allow Click UnLock Button MC ','',0,'\\202508','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4619,0,'Allow Click Post Button MC ','',0,'\\202509','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4620,0,'Allow Click UnPost Button MC ','',0,'\\202510','\\2025',0,'0',0," . $params['levelid'] . "),
        (4621,0,'Allow Click Add Item MC ','',0,'\\202511','\\2025',0,'0',0," . $params['levelid'] . ") ,
        (4622,0,'Allow Click Add Delete Item MC ','',0,'\\202512','\\2025',0,'0',0," . $params['levelid'] . "),
        
        (4735,0,'Allow Click view Dashboard MC ','',0,'\\202513','\\2025',0,'0',0," . $params['levelid'] . ")";
        // (4605,1,'Allow View Dashboard CS','',0,'\\202405','\\2024',0,0,0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'MC','/module/cdo/mc','MC Collection','fa fa-money-bill-alt sub_menu_ico',4610," . $params['levelid'] . ")";
    } //end function

    public function cdsummary($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4627,0,'Canvass Summary','',0,'\\2026','$parent',0,'0',0," . $params['levelid'] . "),
        (4628,0,'Allow View Transaction ', 'CY',0,'\\202601','\\2026',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'cdsummary','/actionlisting/actionlisting/entrycanvasssummary','Canvass Summary','fa fa-list sub_menu_ico',4627," . $params['levelid'] . ")";
    } //end function
    public function cdapprovalsummary($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4629,0,'Canvass Approval Summary','',0,'\\2027','$parent',0,'0',0," . $params['levelid'] . "),
        (4630,0,'Allow View Transaction ', '',0,'\\202701','\\2027',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'cdapprovalsummary','/tableentries/ati/entrycanvassapproval','Canvass Approval Summary','fa fa-check-double sub_menu_ico',4629," . $params['levelid'] . ")";
    } //end function
    public function modeoftransction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4644,1,'Mode of Transaction','',0,'\\2028','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'modeoftransction','/tableentries/tableentry/entrymodeoftransaction','Mode of Transaction','fa fa-code-branch sub_menu_ico',4644," . $params['levelid'] . ")";
    } //end function
    public function timein($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4646,0,'Time In','',0,'\\2029','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4647,0,'Allow View Transaction TI ','',0,'\\202901','\\2029',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'timein','/actionlisting/actionlisting/timein','Time In','fa fa-calendar-alt sub_menu_ico',4646," . $params['levelid'] . ")";
    } //end function
    public function area($params, $parent, $sort)
    {

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4651,1,'Area','',0,'\\2030','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'area','/tableentries/tableentry/entryarea','Area','fas fa-map-marked-alt sub_menu_ico',4651," . $params['levelid'] . ")";
    } //end function

    public function financingpartner($params, $parent, $sort)
    {

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4652, 0, 'Financing Partner', '', 0, '\\2031', '$parent', 0, '0', 0, " . $params['levelid'] . "),
            (4653, 0, 'Allow View Financing Partner', 'FINANCING PARTNER', 0, '\\203101', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4654, 0, 'Allow Click Edit Button FP', '', 0, '\\203102', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4655, 0, 'Allow Click New Button FP', '', 0, '\\203103', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4656, 0, 'Allow Click Save Button FP', '', 0, '\\203104', '\\2031', 0, '0', 0, " . $params['levelid'] . "),        
            (4657, 0, 'Allow Click Delete Button FP', '', 0, '\\203105', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4658, 0, 'Allow Click Print Button FP', '', 0, '\\203106', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4659, 0, 'Allow View AR History', '', 0, '\\203107', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4660, 0, 'Allow View AP History', '', 0, '\\203108', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4661, 0, 'Allow View PDC History', '', 0, '\\203109', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4662, 0, 'Allow View Returned Checks History', '', 0, '\\203110', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4663, 0, 'Allow View Inventory History', '', 0, '\\203111', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4664, 0, 'Allow View Unpaid AR', '', 0, '\\203112', '\\2031', 0, '0', 0, " . $params['levelid'] . "),
            (4665, 0, 'Allow View Contact Person Setup', '', 0, '\\203113', '\\2031', 0, '0', 0, " . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort, $p, 'financingpartner', '/ledgergrid/masterfile/financingpartner', 'Financing Partner', 'fa fa-handshake sub_menu_ico', 4652, " . $params['levelid'] . ")";
    } //end function

    public function ct($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4669,0,'Construction Instruction','',0,'\\2034','$parent',0,'0',0," . $params['levelid'] . "),
        (4670,0,'Allow View Transaction CT','CT',0,'\\203401','\\2034',0,'0',0," . $params['levelid'] . "),
        (4671,0,'Allow Click Edit Button CT','',0,'\\203402','\\2034',0,'0',0," . $params['levelid'] . "),
        (4672,0,'Allow Click New  Button CT','',0,'\\203403','\\2034',0,'0',0," . $params['levelid'] . "),
        (4673,0,'Allow Click Save  Button CT','',0,'\\203404','\\2034',0,'0',0," . $params['levelid'] . "),
        (4674,0,'Allow Click Delete Button CT','',0,'\\203405','\\2034',0,'0',0," . $params['levelid'] . "),
        (4675,0,'Allow Click Print  Button CT','',0,'\\203406','\\2034',0,'0',0," . $params['levelid'] . "),
        (4676,0,'Allow Click Lock Button CT','',0,'\\203407','\\2034',0,'0',0," . $params['levelid'] . "),
        (4677,0,'Allow Click UnLock Button CT','',0,'\\203408','\\2034',0,'0',0," . $params['levelid'] . "),
        (4678,0,'Allow Click Post Button CT','',0,'\\203409','\\2034',0,'0',0," . $params['levelid'] . "),
        (4679,0,'Allow Click UnPost Button CT','',0,'\\203410','\\2034',0,'0',0," . $params['levelid'] . "),
        (4680,0,'Allow Click Add Item CT','',0,'\\203411','\\2034',0,'0',0," . $params['levelid'] . "),
        (4681,0,'Allow Click Edit Item CT','',0,'\\203412','\\2034',0,'0',0," . $params['levelid'] . "),
        (4682,0,'Allow Click Delete Item CT','',0,'\\203413','\\2034',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CT','/module/realestate/ct','Construction Instruction','fa fa-boxes sub_menu_ico',4669," . $params['levelid'] . ")";
    } //end function
    public function amenities($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "
            (4683,1,'Amenities','',0,'\\114','$parent',0,'0',0," . $params['levelid'] . "),
            (4684, 0, 'Allow View Sub-Amenities', '', 0, '\\11401', '\\114', 0, '0', 0, " . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'amenities','/tableentries/realestate/entryamenities','Amenities','fa fa-list sub_menu_ico',4683," . $params['levelid'] . ")";
    } //end function


    public function pn($params, $parent, $sort)
    {
        //2550
        $label = 'Project Completion';
        $folder = 'realestate';
        $companyid = $params['companyid'];
        switch ($companyid) {
            case 50: //unitech
                $folder = 'unitechindustry';
                $label = 'Production Completion';
                break;
        }
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4707,0,'" . $label . "','',0,'\\2035','$parent',0,'0',0," . $params['levelid'] . "),
        (4708,0,'Allow View Transaction PN','PN',0,'\\203501','\\2035',0,'0',0," . $params['levelid'] . "),
        (4709,0,'Allow Click Edit Button PN','',0,'\\203502','\\2035',0,'0',0," . $params['levelid'] . "),
        (4710,0,'Allow Click New  Button PN','',0,'\\203503','\\2035',0,'0',0," . $params['levelid'] . "),
        (4711,0,'Allow Click Save  Button PN','',0,'\\203504','\\2035',0,'0',0," . $params['levelid'] . "),
        (4712,0,'Allow Click Delete Button PN','',0,'\\203505','\\2035',0,'0',0," . $params['levelid'] . "),
        (4713,0,'Allow Click Print  Button PN','',0,'\\203506','\\2035',0,'0',0," . $params['levelid'] . "),
        (4714,0,'Allow Click Lock Button PN','',0,'\\203507','\\2035',0,'0',0," . $params['levelid'] . "),
        (4715,0,'Allow Click UnLock Button PN','',0,'\\203508','\\2035',0,'0',0," . $params['levelid'] . "),
        (4716,0,'Allow Change Amount PN','',0,'\\203509','\\2035',0,'0',0," . $params['levelid'] . "),
        (4717,0,'Allow Click Post Button PN','',0,'\\203510','\\2035',0,'0',0," . $params['levelid'] . "),
        (4718,0,'Allow Click UnPost Button PN','',0,'\\203511','\\2035',0,'0',0," . $params['levelid'] . "),
        (4719,0,'Allow Click Add Item PN','',0,'\\203512','\\2035',0,'0',0," . $params['levelid'] . "),
        (4720,0,'Allow Click Edit Item PN','',0,'\\203513','\\2035',0,'0',0," . $params['levelid'] . "),
        (4721,0,'Allow Click Delete Item PN','',0,'\\203514','\\2035',0,'0',0," . $params['levelid'] . "),
        (4722,0,'Allow View Amount PN','',0,'\\203515','\\2035',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PN','/module/" . $folder . "/pn','" . $label . "','fa fa-tasks sub_menu_ico',4707," . $params['levelid'] . ")";
    } //end function

    public function ll($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4740,0,'Loading List','',0,'\\503','$parent',0,'0',0," . $params['levelid'] . "),
        (4741,0,'Allow View Transaction LL','LL',0,'\\50301','\\503',0,'0',0," . $params['levelid'] . "),
        (4742,0,'Allow Click Edit Button LL','',0,'\\50302','\\503',0,'0',0," . $params['levelid'] . "),
        (4743,0,'Allow Click New  Button LL','',0,'\\50303','\\503',0,'0',0," . $params['levelid'] . "),
        (4744,0,'Allow Click Save  Button LL','',0,'\\50304','\\503',0,'0',0," . $params['levelid'] . "),
        (4745,0,'Allow Click Delete Button LL','',0,'\\50306','\\503',0,'0',0," . $params['levelid'] . "),
        (4746,0,'Allow Click Print  Button LL','',0,'\\50307','\\503',0,'0',0," . $params['levelid'] . "),
        (4747,0,'Allow Click Lock Button LL','',0,'\\50308','\\503',0,'0',0," . $params['levelid'] . "),
        (4748,0,'Allow Click UnLock Button LL','',0,'\\50309','\\503',0,'0',0," . $params['levelid'] . "),
        (4749,0,'Allow Click Post Button LL','',0,'\\50310','\\503',0,'0',0," . $params['levelid'] . "),
        (4750,0,'Allow Click UnPost  Button LL','',0,'\\50311','\\503',0,'0',0," . $params['levelid'] . "),
        (4751,0,'Allow View Transaction Accounting LL','',0,'\\50312','\\503',0,'0',0," . $params['levelid'] . "),
        (4752,0,'Allow Change Amount LL','',0,'\\50313','\\503',0,'0',0," . $params['levelid'] . "),
        (4753,1,'Allow Click Add Item LL','',0,'\\50314','\\503',0,'0',0," . $params['levelid'] . "),
        (4754,1,'Allow Click Edit Item LL','',0,'\\50315','\\503',0,'0',0," . $params['levelid'] . "),
        (4755,1,'Allow Click Delete Item LL','',0,'\\50316','\\503',0,'0',0," . $params['levelid'] . ")";

        $folder = 'seastar';

        $this->insertattribute($params, $qry);
        return "($sort,$p,'LL','/module/" . $folder . "/ll','Loading List','fa fa-tasks sub_menu_ico',4740," . $params['levelid'] . ")";
    } //end function

    public function closingmccollection($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4728,0,'Closing MC Collection','',0,'\\2037','$parent',0,'0',0," . $params['levelid'] . "),
        (4729,0,'Allow View Transaction ', '',0,'\\203701','\\2037',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'closingmccollection','/tableentries/cdo/entryclosingmccollection','Closing MC Collection','fa fa-check-double sub_menu_ico',4728," . $params['levelid'] . ")";
    } //end function


    public function ordertype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4730,1,'Order Type','',0,'\\3200','$parent',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'ordertype','/tableentries/tableentry/ordertype','Order Type','fa fa-tags sub_menu_ico',4730," . $params['levelid'] . ")";
    } //end function


    public function channel($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4731,1,'Channel','',0,'\\3203','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'channel','/tableentries/tableentry/entrychannel','Channel','fa fa-tags sub_menu_ico',4731," . $params['levelid'] . ")";
    } //end function

    public function undertime($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4771,1,'Undertime Application','',0,'\\3204','$parent',0,0,0," . $params['levelid'] . ") ,
        (4772,1,'Allow Click Save Button ','',0,'\\320401','\\3204',0,0,0," . $params['levelid'] . ") ,
        (4773,1,'Allow Click Print Button ','',0,'\\320402','\\3204',0,0,0," . $params['levelid'] . ") ,
        (4774,1,'Allow Click New Button ','',0,'\\320403','\\3204',0,0,0," . $params['levelid'] . ") ,
        (4775,1,'Allow Click Delete Button ','',0,'\\320404','\\3204',0,0,0," . $params['levelid'] . ") ,   
        (4776,1,'Allow View Undertime Application','',0,'\\320405','\\3204',0,0,0," . $params['levelid'] . "),
        (4777,1,'Allow Click Edit Button ','',0,'\\320406','\\3204',0,0,0," . $params['levelid'] . "),
        (4801,1,'Allow View Dashboard Undertime Application','',0,'\\320407','\\3204',0,0,0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'undertime','/ledger/payroll/undertime','Undertime Application','fa fa-clock sub_menu_ico',4771," . $params['levelid'] . ")";
    } //end function
    public function accountingaccount($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4804,1,'Accounting Accounts','',0,'\\3205','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'accountingaccount','/tableentries/payrollsetup/accountingaccount','Accounting Accounts','fa fa-chalkboard-teacher sub_menu_ico',4804," . $params['levelid'] . ")";
    } //end function\

    public function pe($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4806,0,'Production Request','',0,'\\3205','$parent',0,'0',0," . $params['levelid'] . "),
        (4807,0,'Allow View Transaction PE','PE',0,'\\320501','\\3205',0,'0',0," . $params['levelid'] . "),
        (4808,0,'Allow Click Edit Button PE','',0,'\\320502','\\3205',0,'0',0," . $params['levelid'] . "),
        (4809,0,'Allow Click New Button PE','',0,'\\320503','\\3205',0,'0',0," . $params['levelid'] . "),
        (4810,0,'Allow Click Save Button PE','',0,'\\320504','\\3205',0,'0',0," . $params['levelid'] . "),
        (4811,0,'Allow Click Delete Button PE','',0,'\\320505','\\3205',0,'0',0," . $params['levelid'] . "),
        (4812,0,'Allow Click Print Button PE','',0,'\\320506','\\3205',0,'0',0," . $params['levelid'] . "),
        (4813,0,'Allow Click Lock Button PE','',0,'\\320507','\\3205',0,'0',0," . $params['levelid'] . "),
        (4814,0,'Allow Click UnLock Button PE','',0,'\\320508','\\3205',0,'0',0," . $params['levelid'] . "),
        (4815,0,'Allow Click Post Button PE','',0,'\\320509','\\3205',0,'0',0," . $params['levelid'] . "),
        (4816,0,'Allow Click UnPost Button PE','',0,'\\320510','\\3205',0,'0',0," . $params['levelid'] . "),
        (4817,0,'Allow Change Amount PE','',0,'\\320511','\\3205',0,'0',0," . $params['levelid'] . "),
        (4818,1,'Allow Click Add Item PE','',0,'\\320512','\\3205',0,'0',0," . $params['levelid'] . "),
        (4819,1,'Allow Click Edit Item PE','',0,'\\320513','\\3205',0,'0',0," . $params['levelid'] . "),
        (4820,1,'Allow Click Delete Item PE','',0,'\\320514','\\3205',0,'0',0," . $params['levelid'] . "),
        (4821,1,'Allow Void Button','',0,'\\40418','\\404',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'PE','/module/unitechindustry/pe','Production Request','fa fa-list sub_menu_ico',4806," . $params['levelid'] . ")";
    } //end function

    public function pk($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4822,0,'Production Return','',0,'\\3206','$parent',0,'0',0," . $params['levelid'] . "),
        (4823,0,'Allow View Transaction PK','PK',0,'\\320601','\\3206',0,'0',0," . $params['levelid'] . "),
        (4824,0,'Allow Click Edit Button PK','',0,'\\320602','\\3206',0,'0',0," . $params['levelid'] . "),
        (4825,0,'Allow Click New Button PK','',0,'\\320603','\\3206',0,'0',0," . $params['levelid'] . "),
        (4826,0,'Allow Click Save Button PK','',0,'\\320604','\\3206',0,'0',0," . $params['levelid'] . "),
        (4827,0,'Allow Click Delete Button PK','',0,'\\320606','\\3206',0,'0',0," . $params['levelid'] . "),
        (4828,0,'Allow Click Print Button PK','',0,'\\320607','\\3206',0,'0',0," . $params['levelid'] . "),
        (4829,0,'Allow Click Lock Button PK','',0,'\\320608','\\3206',0,'0',0," . $params['levelid'] . "),
        (4830,0,'Allow Click UnLock Button PK','',0,'\\320609','\\3206',0,'0',0," . $params['levelid'] . "),
        (4831,0,'Allow Click Post Button PK','',0,'\\320610','\\3206',0,'0',0," . $params['levelid'] . "),
        (4832,0,'Allow Click UnPost Button PK','',0,'\\320611','\\3206',0,'0',0," . $params['levelid'] . "),
        (4833,0,'Allow View Transaction accounting PK','',0,'\\320612','\\3206',0,'0',0," . $params['levelid'] . "),
        (4834,0,'Allow Change Amount PK','',0,'\\320613','\\3206',0,'0',0," . $params['levelid'] . "),
        (4835,1,'Allow Click Add Item PK','',0,'\\320614','\\3206',0,'0',0," . $params['levelid'] . "),
        (4836,1,'Allow Click Edit Item PK','',0,'\\320615','\\3206',0,'0',0," . $params['levelid'] . "),
        (4837,1,'Allow Click Delete Item PK','',0,'\\320616','\\3206',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'PK','/module/unitechindustry/pk','Production Return','fa fa-retweet sub_menu_ico',4822," . $params['levelid'] . ")";
    } //end function

    public function pt($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4879,0,'Production Instruction','',0,'\\3207','$parent',0,'0',0," . $params['levelid'] . "),
        (4880,0,'Allow View Transaction PI','PI',0,'\\320701','\\3207',0,'0',0," . $params['levelid'] . "),
        (4881,0,'Allow Click Edit Button PI','',0,'\\320702','\\3207',0,'0',0," . $params['levelid'] . "),
        (4882,0,'Allow Click New  Button PI','',0,'\\320703','\\3207',0,'0',0," . $params['levelid'] . "),
        (4883,0,'Allow Click Save  Button PI','',0,'\\320704','\\3207',0,'0',0," . $params['levelid'] . "),
        (4884,0,'Allow Click Delete Button PI','',0,'\\320705','\\3207',0,'0',0," . $params['levelid'] . "),
        (4885,0,'Allow Click Print  Button PI','',0,'\\320706','\\3207',0,'0',0," . $params['levelid'] . "),
        (4886,0,'Allow Click Lock Button PI','',0,'\\320707','\\3207',0,'0',0," . $params['levelid'] . "),
        (4887,0,'Allow Click UnLock Button PI','',0,'\\320708','\\3207',0,'0',0," . $params['levelid'] . "),
        (4888,0,'Allow Click Post Button PI','',0,'\\320709','\\3207',0,'0',0," . $params['levelid'] . "),
        (4889,0,'Allow Click UnPost Button PI','',0,'\\320710','\\3207',0,'0',0," . $params['levelid'] . "),
        (4890,0,'Allow Click Add Item PI','',0,'\\320711','\\3207',0,'0',0," . $params['levelid'] . "),
        (4891,0,'Allow Click Edit Item PI','',0,'\\320712','\\3207',0,'0',0," . $params['levelid'] . "),
        (4892,0,'Allow Click Delete Item PI','',0,'\\320713','\\3207',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PI','/module/unitechindustry/pi','Production Instruction','fa fa-boxes sub_menu_ico',4879," . $params['levelid'] . ")";
    } //end function
    public function cutoffinventory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4910,0,'Cut Off Inventory','',0,'\\3208','$parent',0,'0',0," . $params['levelid'] . "),
        (4922,0,'Allow View Transaction RX','PI',0,'\\320801','\\3208',0,'0',0," . $params['levelid'] . "),
        (4923,0,'Allow Click Save Button RX','',0,'\\320802','\\3208',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'INVCUTOFF','/headtable/inventory/cutoffinvbal','Cut Off Inventory','fa fa-chart-bar sub_menu_ico',4910," . $params['levelid'] . ")";
    }

    public function parentserviceticketing($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3925,0,'SERVICE TICKETING ','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'SERVICE TICKETING ',$sort,'support_agent',',serviceticketing,'," . $params['levelid'] . ")";
    } //end function

    public function ta($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4926,0,'Ticket Application','',0,'\\3209','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4927,0,'Allow View Transaction TA','TA',0,'\\320901','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4928,0,'Allow Click Edit Button TA','',0,'\\320902','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4929,0,'Allow Click New Button TA','',0,'\\320903','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4930,0,'Allow Click Save Button TA','',0,'\\320904','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4931,0,'Allow Click Change Code TA','',0,'\\320905','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4932,0,'Allow Click Delete Button TA','',0,'\\320906','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4933,0,'Allow Click Print Button TA','',0,'\\320907','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4934,0,'Allow Click Lock Button TA','',0,'\\320908','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4935,0,'Allow Click UnLock Button TA','',0,'\\320909','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4936,0,'Allow Click Change Amount TA','',0,'\\320910','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4937,0,'Allow Click Post Button TA','',0,'\\320911','\\3209',0,'0',0," . $params['levelid'] . ") ,
        (4938,0,'Allow Click UnPost Button TA','',0,'\\320912','\\3209',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'TA','/module/serviceticketing/ta','Ticket Application','fa fa-sticky-note sub_menu_ico',4926," . $params['levelid'] . ")";
    } //end function

    public function work_order($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4939,0,'Work Order','',0,'\\3210','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4940,0,'Allow View Work Order','',0,'\\321001','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4941,0,'Allow Click Edit Button Work Order','',0,'\\321002','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4942,0,'Allow Click New Button Work Order','',0,'\\321003','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4943,0,'Allow Click Save Button Work Order','',0,'\\321004','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4944,0,'Allow Click Change Code Work Order','',0,'\\321005','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4945,0,'Allow Click Delete Button Work Order','',0,'\\321006','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4946,0,'Allow Click Print Button Work Order','',0,'\\321007','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4947,0,'Allow Click Lock Button Work Order','',0,'\\321008','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4948,0,'Allow Click UnLock Button Work Order','',0,'\\321009','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4949,0,'Allow Click Change Amount Work Order','',0,'\\321010','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4950,0,'Allow Click Post Button Work Order','',0,'\\321011','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4951,0,'Allow Click UnPost Button Work Order','',0,'\\321012','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4952,0,'Allow Click Add Item Work Order','',0,'\\321013','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4953,0,'Allow Click Edit Item Work Order','',0,'\\321014','\\3210',0,'0',0," . $params['levelid'] . ") ,
        (4954,0,'Allow Click Delete Item Work Order','',0,'\\321015','\\3210',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'WORK ORDER','/module/serviceticketing/wo','Work Order','fa fa-tasks sub_menu_ico',4939," . $params['levelid'] . ")";
    } //end function


    public function parentlending($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4955,0,'LENDING','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'LENDING',$sort,'fas fa-hands-helping',',,'," . $params['levelid'] . ")";
    } //end function 

    public function le($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4956,0,'Application Form ','',0,'\\3211','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4957,0,'Allow View Transaction LE','LE',0,'\\321101','\\3211',0,'0',0," . $params['levelid'] . ") ,
        (4958,0,'Allow Click Edit Button  LE','',0,'\\321102','\\3211',0,'0',0," . $params['levelid'] . ") ,
        (4959,0,'Allow Click New Button LE','',0,'\\321103','\\3211',0,'0',0," . $params['levelid'] . ") ,
        (4960,0,'Allow Click Save Button LE','',0,'\\321104','\\3211',0,'0',0," . $params['levelid'] . ") ,
        (4961,0,'Allow Click Delete Button LE','',0,'\\321105','\\3211',0,'0',0," . $params['levelid'] . ") ,
        (4962,0,'Allow Click Print Button LE','',0,'\\321106','\\3211',0,'0',0," . $params['levelid'] . ") ,
        (4963,0,'Allow Click Lock Button LE','',0,'\\321107','\\3211',0,'0',0," . $params['levelid'] . ") ,
        (4964,0,'Allow Click UnLock Button LE','',0,'\\321108','\\3211',0,'0',0," . $params['levelid'] . ") ,
        (4965,0,'Allow Click Post Button LE','',0,'\\321109','\\3211',0,'0',0," . $params['levelid'] . ") ,
        (4966,0,'Allow Click UnPost Button LE','',0,'\\321110','\\3211',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'LE','/module/lending/le','Application Form','fa fa-file-alt sub_menu_ico',4956," . $params['levelid'] . ")";
    } //end function


    public function cutoffaccounting($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4979,0,'Cut Off Accounting','',0,'\\3212','$parent',0,'0',0," . $params['levelid'] . "),
        (4980,0,'Allow View Transaction CTA','PI',0,'\\321201','\\3212',0,'0',0," . $params['levelid'] . "),
        (4981,0,'Allow Click Save Button CTA','',0,'\\321202','\\3212',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ACCTGCUTOFF','/headtable/accounting/cutoffacctgbal','Cut Off Accounting','fa fa-chart-bar sub_menu_ico',4979," . $params['levelid'] . ")";
    }


    public function la($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4986,0,'Loan Approval','',0,'\\3213','$parent',0,'0',0," . $params['levelid'] . ") ,
        (4987,0,'Allow View Transaction LA','LA',0,'\\321301','\\3213',0,'0',0," . $params['levelid'] . ") ,
        (4988,0,'Allow Click Edit Button  LA','',0,'\\321302','\\3213',0,'0',0," . $params['levelid'] . ") ,
        (4989,0,'Allow Click New Button LA','',0,'\\321303','\\3213',0,'0',0," . $params['levelid'] . ") ,
        (4990,0,'Allow Click Save Button LA','',0,'\\321304','\\3213',0,'0',0," . $params['levelid'] . ") ,
        (4991,0,'Allow Click Delete Button LA','',0,'\\321305','\\3213',0,'0',0," . $params['levelid'] . ") ,
        (4992,0,'Allow Click Print Button LA','',0,'\\321306','\\3213',0,'0',0," . $params['levelid'] . ") ,
        (4993,0,'Allow Click Lock Button LA','',0,'\\321307','\\3213',0,'0',0," . $params['levelid'] . ") ,
        (4994,0,'Allow Click UnLock Button LA','',0,'\\321308','\\3213',0,'0',0," . $params['levelid'] . ") ,
        (4995,0,'Allow Click Post Button LA','',0,'\\321309','\\3213',0,'0',0," . $params['levelid'] . ") ,
        (4996,0,'Allow Click UnPost Button LA','',0,'\\321310','\\3213',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'LA','/module/lending/la','Loan Approval','fa fa-clipboard-check sub_menu_ico',4986," . $params['levelid'] . ")";
    } //end function

    public function pp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $subparent = '\\3214';
        $qry = "(5002,0,'Promo Per Item','',0,'\\3214','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5003,0,'Allow View Transaction PP','PP',0,'\\321401','$subparent',0,'0',0," . $params['levelid'] . ") ,
        (5004,0,'Allow Click Edit Button  PP','',0,'\\321402','$subparent',0,'0',0," . $params['levelid'] . ") ,
        (5005,0,'Allow Click New Button PP','',0,'\\321403','$subparent',0,'0',0," . $params['levelid'] . ") ,
        (5006,0,'Allow Click Save Button PP','',0,'\\321404','$subparent',0,'0',0," . $params['levelid'] . ") ,
        (5007,0,'Allow Click Delete Button PP','',0,'\\321405','$subparent',0,'0',0," . $params['levelid'] . ") ,
        (5008,0,'Allow Click Print Button PP','',0,'\\321406','$subparent',0,'0',0," . $params['levelid'] . ") ,
        (5009,0,'Allow Click Lock Button PP','',0,'\\321407','$subparent',0,'0',0," . $params['levelid'] . ") ,
        (5010,0,'Allow Click UnLock Button PP','',0,'\\321408','$subparent',0,'0',0," . $params['levelid'] . ") ,
        (5011,0,'Allow Click Post Button PP','',0,'\\321409','$subparent',0,'0',0," . $params['levelid'] . ") ,
        (5012,0,'Allow Click UnPost Button PP','',0,'\\321410','$subparent',0,'0',0," . $params['levelid'] . "),
        (5013,0,'Allow Click Add Item PP','',0,'\\321411','$subparent',0,'0',0," . $params['levelid'] . "),
        (5014,0,'Allow Click Edit Item PP','',0,'\\32112','$subparent',0,'0',0," . $params['levelid'] . "),
        (5015,0,'Allow Click Delete Item PP','',0,'\\321413','$subparent',0,'0',0," . $params['levelid'] . "),
        (5350,0,'Allow Click Void Promotion','',0,'\\321414','$subparent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PP','/module/pos/pp','Promo Per Item','fa fa-percent sub_menu_ico',5002," . $params['levelid'] . ")";
    } //end function

    public function temppossales($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5359,1,'Uploaded Sales','',0,'\\3227','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entryuploadedpos','/tableentries/pos/entryuploadedpos','Uploaded Sales','fa fa-tags sub_menu_ico',5359," . $params['levelid'] . ")";
    }

    public function reasoncodesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = "Reason Code Setup";
        if ($params['companyid'] == 10) {
            $label = "Reason Setup";
        }
        $qry = "(5025,1,'" . $label . "','',0,'\\3215','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'reasoncodesetup','/tableentries/tableentry/entryreasoncodesetup','" . $label . "','fa fa-comments sub_menu_ico',5025," . $params['levelid'] . ")";
    } //end function


    public function transactiontype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5038,1,'Transaction Type','',0,'\\3216','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'transactiontype','/tableentries/tableentry/entrytransactiontype','Transaction Type','fa fa-regular fa-chart-bar sub_menu_ico',5038," . $params['levelid'] . ")";
    } //end function


    public function modeofpayment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5040,1,'Mode of Payment','',0,'\\3218','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'modeofpayment','/tableentries/tableentry/entrymodeofpayment','Mode of Payment','fa fa-regular fa-credit-card sub_menu_ico',5040," . $params['levelid'] . ")";
    } //end function

    public function dx($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5055,0,'Deposit Slip','',0,'\\3220','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5056,0,'Allow View Transaction DX','DX',0,'\\322001','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5057,0,'Allow Click Edit Button  DX','',0,'\\322002','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5058,0,'Allow Click New Button DX','',0,'\\322003','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5059,0,'Allow Click Save Button DX','',0,'\\322004','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5060,0,'Allow Click Delete Button DX','',0,'\\322006','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5061,0,'Allow Click Print Button DX','',0,'\\322007','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5062,0,'Allow Click Lock Button DX','',0,'\\322008','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5063,0,'Allow Click UnLock Button DX','',0,'\\322009','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5064,0,'Allow Click Post Button DX','',0,'\\322010','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5065,0,'Allow Click UnPost Button DX','',0,'\\322011','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5066,0,'Allow Click Add Account DX','',0,'\\322012','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5067,0,'Allow Click Edit Account DX','',0,'\\322013','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5068,0,'Allow Click Delete Account DX','',0,'\\322014','\\3220',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'DX','/module/accounting/dx','Deposit Slip','fa fa-solid fa-money-check sub_menu_ico',5055," . $params['levelid'] . ")";
    } //end function


    public function purposeofpayment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5039,1,'Purpose of Payment','',0,'\\3217','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'purposeofpayment','/tableentries/tableentry/entrypurposeofpayment','Purpose of Payment','fa fa-money-check sub_menu_ico',5039," . $params['levelid'] . ")";
    } //end function

    public function parentcashier($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5069,0,'CASHIER','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'CASHIER',$sort,'fa fa-cash-register',',cashier,'," . $params['levelid'] . ")";
    } //end function

    public function ce($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5041,0,'Cashier Entry','',0,'\\3219','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5042,0,'Allow View Transaction CE', 'CE',0,'\\321901','\\3219',0,'0',0," . $params['levelid'] . ") ,
        (5043,0,'Allow Click Edit Button CE ','',0,'\\321902','\\3219',0,'0',0," . $params['levelid'] . ") ,
        (5044,0,'Allow Click New Button CE ','',0,'\\321903','\\3219',0,'0',0," . $params['levelid'] . ") ,
        (5045,0,'Allow Click Save Button CE ','',0,'\\321904','\\3219',0,'0',0," . $params['levelid'] . ") ,
        (5046,0,'Allow Click Delete Button CE ','',0,'\\321905','\\3219',0,'0',0," . $params['levelid'] . ") ,
        (5047,0,'Allow Click Print Button CE ','',0,'\\321906','\\3219',0,'0',0," . $params['levelid'] . ") ,
        (5048,0,'Allow Click Lock Button CE ','',0,'\\321907','\\3219',0,'0',0," . $params['levelid'] . ") ,
        (5049,0,'Allow Click UnLock Button CE ','',0,'\\321908','\\3219',0,'0',0," . $params['levelid'] . ") ,
        (5050,0,'Allow Click Post Button CE ','',0,'\\321909','\\3219',0,'0',0," . $params['levelid'] . ") ,
        (5051,0,'Allow Click UnPost Button CE ','',0,'\\321910','\\3219',0,'0',0," . $params['levelid'] . "),
        (5052,0,'Allow Click Add Item CE ','',0,'\\321911','\\3219',0,'0',0," . $params['levelid'] . "),
        (5053,0,'Allow Click Add Delete Item CE ','',0,'\\321912','\\3219',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'ce','/module/cashier/ce','Cashier Entry','fa fa-money-bill-alt sub_menu_ico',5041," . $params['levelid'] . ")";
    } //end function

    public function dlcoll($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5093,0,'Downloading Utility','',0,'\\3221','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'terms','/headtable/cashier/downloadcoll','Downloading from WAIMS','fa fa-download sub_menu_ico',5093," . $params['levelid'] . ")";
    } //end function

    public function endofday($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5109,0,'End of Day','',0,'\\3223','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'terms','/headtable/cashier/endofday','End Of Day','fa fa-hourglass-end sub_menu_ico',5109," . $params['levelid'] . ")";
    } //end function

    public function parentothertransaction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5054,0,'OTHER TRANSACTION','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'OTHER TRANSACTION',$sort,'fab fa-hubspot',',,'," . $params['levelid'] . ")";
    } //end function
    public function violation($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5070,0,'Violation','',0,'\\3220','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5071,0,'Allow View Transaction VI', 'VI',0,'\\322001','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5072,0,'Allow Click Edit Button VI ','',0,'\\322002','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5073,0,'Allow Click New Button VI ','',0,'\\322003','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5074,0,'Allow Click Save Button VI ','',0,'\\322004','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5075,0,'Allow Click Delete Button VI ','',0,'\\322005','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5076,0,'Allow Click Print Button VI ','',0,'\\322006','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5077,0,'Allow Click Lock Button VI ','',0,'\\322007','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5078,0,'Allow Click UnLock Button VI ','',0,'\\322008','\\3220',0,'0',0," . $params['levelid'] . "),
        (5079,0,'Allow Click Post Button VI ','',0,'\\322009','\\3220',0,'0',0," . $params['levelid'] . ") ,
        (5080,0,'Allow Click UnPost Button VI ','',0,'\\322010','\\3220',0,'0',0," . $params['levelid'] . "),
        (5091,0,'Allow View Dashboard Violation VI ','',0,'\\322011','\\3220',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'VI','/module/othertransaction/vi','Violation','fa fa-exclamation-triangle sub_menu_ico',5070," . $params['levelid'] . ")";
    } //end function
    public function createportaltempschedule($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5081,1,'Create Portal Schedule','',0,'\\3221','$parent',0,0,0," . $params['levelid'] . ") ,
        (5082,1,'Allow View Create Portal Schedule','',0,'\\322101','\\3221',0,0,0," . $params['levelid'] . ") ,
        (5083,1,'Allow Click Button Save Create Portal Schedule','',0,'\\322102','\\3221',0,0,0," . $params['levelid'] . ") ,
        (5084,1,'Allow Click Button Edit Create Portal Schedule','',0,'\\322103','\\3221',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'createportaltempschedule','/headtable/payrollcustomform/createtempschedule','Create Portal Schedule','fa fa-weight sub_menu_ico',5081," . $params['levelid'] . ")";
    } //end function


    public function tc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5094,0,'Petty Cash Entry','',0,'\\3222','$parent',0,'0',0," . $params['levelid'] . "),
        (5095,0,'Allow View Transaction TC','TC',0,'\\322201','\\3222',0,'0',0," . $params['levelid'] . "),
        (5096,0,'Allow Click Edit Button TC','',0,'\\322202','\\3222',0,'0',0," . $params['levelid'] . "),
        (5097,0,'Allow Click New  Button TC','',0,'\\322203','\\3222',0,'0',0," . $params['levelid'] . "),
        (5098,0,'Allow Click Save  Button TC','',0,'\\322204','\\3222',0,'0',0," . $params['levelid'] . "),
        (5099,0,'Allow Click Delete Button TC','',0,'\\322205','\\3222',0,'0',0," . $params['levelid'] . "),
        (5100,0,'Allow Click Print  Button TC','',0,'\\322206','\\3222',0,'0',0," . $params['levelid'] . "),
        (5101,0,'Allow Click Lock Button TC','',0,'\\322207','\\3222',0,'0',0," . $params['levelid'] . "),
        (5102,0,'Allow Click UnLock Button TC','',0,'\\322208','\\3222',0,'0',0," . $params['levelid'] . "),
        (5103,0,'Allow Click Post Button TC','',0,'\\322209','\\3222',0,'0',0," . $params['levelid'] . "),
        (5104,0,'Allow Click UnPost Button TC','',0,'\\322210','\\3222',0,'0',0," . $params['levelid'] . "),
        (5105,0,'Allow Click Add Item TC','',0,'\\322211','\\3222',0,'0',0," . $params['levelid'] . "),
        (5106,0,'Allow Click Edit Item TC','',0,'\\322212','\\3222',0,'0',0," . $params['levelid'] . "),
        (5107,0,'Allow Click Delete Item TC','',0,'\\322213','\\3222',0,'0',0," . $params['levelid'] . "),
        (5108,0,'Allow View Reflenishment','',0,'\\322214','\\3222',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'TC','/module/cashier/tc','Petty Cash Entry','fa fa-money-bill-wave sub_menu_ico',5094," . $params['levelid'] . ")";
        //<i class="fas fa-money-bill-wave"></i>
    } //end function
    public function expiration($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5118,1,'Expiration','*129',0,'\\3224','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'expiration','/tableentries/tableentry/entryexpiration','Expiration','fa fa-hourglass-half sub_menu_ico',5118," . $params['levelid'] . ")";
    } //end function
    public function restday($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5136,1,'Rest Day Form','',0,'\\2032','$parent',0,'0',0," . $params['levelid'] . "),
        (5137,1,'Allow Click Edit Button  CS ','',0,'\\203201','\\2032',0,'0',0," . $params['levelid'] . "),
        (5138,1,'Allow Click New Button CS ','',0,'\\203202','\\2032',0,'0',0," . $params['levelid'] . "),
        (5139,1,'Allow Click Save Button CS ','',0,'\\203203','\\2032',0,'0',0," . $params['levelid'] . ") ,
        (5140,1,'Allow Click Delete Button CS ','',0,'\\203204','\\2032',0,'0',0," . $params['levelid'] . "),
        (5153,1,'Allow Click Print Button CS','',0,'\\203205','\\2032',0,0,0," . $params['levelid'] . "),
        (5155,1,'Allow View Dashboard Rest Day Form','',0,'\\203206','\\2032',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'restday','/ledgergrid/payroll/restday','Rest Day Form','fa fa-calendar-alt sub_menu_ico',5136," . $params['levelid'] . ")";
    } //end function

    public function word($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5148,1,'Work On Rest Day Form','',0,'\\2034','$parent',0,'0',0," . $params['levelid'] . "),
        (5149,1,'Allow Click Edit Button  CS ','',0,'\\203401','\\2034',0,'0',0," . $params['levelid'] . "),
        (5150,1,'Allow Click New Button CS ','',0,'\\203402','\\2034',0,'0',0," . $params['levelid'] . "),
        (5151,1,'Allow Click Save Button CS ','',0,'\\203403','\\2034',0,'0',0," . $params['levelid'] . ") ,
        (5152,1,'Allow Click Delete Button CS ','',0,'\\203404','\\2034',0,'0',0," . $params['levelid'] . "),
        (5154,1,'Allow Click Print Button CS','',0,'\\203405','\\2034',0,0,0," . $params['levelid'] . "),
        (5159,1,'Allow View Dashboard Work On Rest Day Form','',0,'\\203406','\\2034',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'word','/ledgergrid/payroll/word','Work On Rest Day Form','fa fa-calendar-alt sub_menu_ico',5148," . $params['levelid'] . ")";
    } //end function

    public function leavecancellation($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5156,1,'Leave Cancellation','',0,'\\2035','$parent',0,'0',0," . $params['levelid'] . "),
        (5157,1,'Allow Click Print Button LC','',0,'\\203501','\\2035',0,0,0," . $params['levelid'] . "),
        (5166,1,'Allow Click Save Button LC ','',0,'\\203502','\\2035',0,'0',0," . $params['levelid'] . "),
        (5158,1,'Allow View Dashboard Leave Cancellation','',0,'\\203503','\\2035',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leavecancellation','/ledgergrid/payroll/lcc','Leave Cancellation','fa fa-calendar-times sub_menu_ico',5156," . $params['levelid'] . ")";
    } //end function
    public function temptimecard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5161,1,'Portal Schedule','',0,'\\1915','$parent',0,0,0," . $params['levelid'] . ") ,
        (5162,1,'Allow View Portal Schedule','',0,'\\191501','\\1915',0,0,0," . $params['levelid'] . ") ,
        (5163,1,'Allow Click Button Save Portal Schedule','',0,'\\191502','\\1915',0,0,0," . $params['levelid'] . ") ,
        (5164,1,'Allow Click Button Edit Portal Schedule','',0,'\\191503','\\1915',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'temptimecard','/headtable/payrollcustomform/ttc','Portal Schedule','fa fa-calendar-check sub_menu_ico',5161," . $params['levelid'] . ")";
    } //end function 


    public function rs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5143,0,'Re-Assignment','',0,'\\1715','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5144,0,'Allow View Re-Assignment','',0,'\\171501','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5145,0,'Allow Click Edit Button Re-Assignment','',0,'\\171502','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5146,0,'Allow Click New Button Re-Assignment','',0,'\\171503','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5147,0,'Allow Click Save Button Re-Assignment','',0,'\\171504','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5167,0,'Allow Click Change Code Re-Assignment','',0,'\\171505','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5168,0,'Allow Click Delete Button Re-Assignment','',0,'\\171506','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5169,0,'Allow Click Print Button Re-Assignment','',0,'\\171507','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5170,0,'Allow Click Post Button Re-Assignment','',0,'\\171508','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5171,0,'Allow Click UnPost Button Re-Assignment','',0,'\\171509','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5172,0,'Allow Click Lock Button Re-Assignment','',0,'\\171510','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5173,0,'Allow Click UnLock Button Re-Assignment','',0,'\\171511','\\1715',0,'0',0," . $params['levelid'] . "),
      
        (5180,0,'Allow Click Add Employee Button Re-Assignment','',0,'\\171512','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5181,0,'Allow Click Edit Employee Button Re-Assignment','',0,'\\171513','\\1715',0,'0',0," . $params['levelid'] . ") ,
        (5182,0,'Allow Click Delete Employee Button Re-Assignment','',0,'\\171514','\\1715',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'rs','/module/hris/rs','Re-Assignment','fa fa-sticky-note sub_menu_ico',5143," . $params['levelid'] . ")";
    } //end function
    public function undertimecancellation($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5176,1,'Undertime Cancellation','',0,'\\2036','$parent',0,'0',0," . $params['levelid'] . "),
        (5177,1,'Allow Click Print Button UC','',0,'\\203601','\\2036',0,0,0," . $params['levelid'] . "),
        (5178,1,'Allow Click Save Button UC ','',0,'\\203602','\\2036',0,'0',0," . $params['levelid'] . "),
        (5179,1,'Allow View Dashboard Undertime Cancellation','',0,'\\203603','\\2036',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'undertimecancellation','/ledgergrid/payroll/ucc','Undertime Cancellation','fa fa-user-clock sub_menu_ico',5176," . $params['levelid'] . ")";
    } //end function

    public function appport($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5165,0,'Applicant Portfolio','',0,'\\1716','$parent',0,'0',0," . $params['levelid'] . "),
        (5175,0,'Access to set Applicant Status','',0,'\\171601','\\1716',0,'0',0," . $params['levelid'] . ")";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'appport','/module/hris/appport','Applicant Portfolio','fa fa-list sub_menu_ico',5165," . $params['levelid'] . ")";
    } //end function

    public function dellogs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5174,1,'Allow View Delete Logs','',0,'\\828','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'dellogs','/headtable/customformlisting/dellogs','Delete Logs','fa fa-user-shield sub_menu_ico',5174," . $params['levelid'] . ")";
    }

    public function updatelogs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5415,1,'Allow View Update Logs','',0,'\\829','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'updatelogs','/headtable/customformlisting/updatelogs','Update Logs','fa fa-user-shield sub_menu_ico',5415," . $params['levelid'] . ")";
    }

    public function questionaire($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5189,0,'Questionaire Setup','',0,'\\1808','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5190,0,'Allow View Questionaire Setup','',0,'\\180801','\\1808',0,'0',0," . $params['levelid'] . ") ,
        (5191,0,'Allow Click Edit Questionaire Setup','',0,'\\180802','\\1808',0,'0',0," . $params['levelid'] . ") ,
        (5192,0,'Allow Click New Questionaire Setup','',0,'\\180803','\\1808',0,'0',0," . $params['levelid'] . ") ,
        (5193,0,'Allow Click Save Questionaire Setup','',0,'\\180804','\\1808',0,'0',0," . $params['levelid'] . ") ,
        (5194,0,'Allow Click Delete Questionaire Setup','',0,'\\180806','\\1808',0,'0',0," . $params['levelid'] . ") ,
        (5195,0,'Allow Click Print Questionaire Setup','',0,'\\180807','\\1808',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'qn','/ledgergrid/hris/qn','Questionaire Setup','fa fa-list sub_menu_ico',5189," . $params['levelid'] . ")";
    }
    public function obcancellation($params, $parent, $sort)
    {
        $label = 'OB Cancellation';
        switch ($params['companyid']) {
            case 58: //cdo
                $label = 'Tracking Cancellation';
                break;
        }

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5197,1,'$label','',0,'\\2038','$parent',0,'0',0," . $params['levelid'] . "),
        (5198,1,'Allow Click Print Button $label','',0,'\\203801','\\2038',0,0,0," . $params['levelid'] . "),
        (5199,1,'Allow Click Save Button $label ','',0,'\\203802','\\2038',0,'0',0," . $params['levelid'] . "),
        (5200,1,'Allow View Dashboard $label','',0,'\\203803','\\2038',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'obcancellation','/ledgergrid/payroll/occ','" . $label . "','fa fa-calendar-minus sub_menu_ico',5197," . $params['levelid'] . ")";
    } //end function
    public function leavecategory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5027,1,'Leave Category','',0,'\\1916','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leavecategory','/tableentries/payrollsetup/lvcat','Leave Category','fa fa-layer-group sub_menu_ico',5027," . $params['levelid'] . ")";
    } //end function 

    public function questionaire2($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5207,0,'Allow view Questionaire','',0,'\\1809','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'qnn','/headtable/hris/qnn','Questionaire','fa fa-list sub_menu_ico',5207," . $params['levelid'] . ")";
    }

    public function contractmonitoring($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5210,0,'Contract Monitoring','',0,'\\1717','$parent',0,'0',0," . $params['levelid'] . "),
                (5466,0,'Allow View Evaluators','',0,'\\171701','\\1717',0,'0',0," . $params['levelid'] . "),
                (5467,0,'Allow Add Evaluators','',0,'\\171702','\\1717',0,'0',0," . $params['levelid'] . "),(5468,0,'Allow View All','',0,'\\171703','\\1717',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'contractmonitoring','/amodule/ahris/contractmonitoring','Contract Monitoring','fa fa-user-clock sub_menu_ico',5210," . $params['levelid'] . ")";
    }
    public function sbu($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5215,1,'SBU Masterfile','',0,'\\120','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'sbu','/tableentries/tableentry/entrysbu','SBU Masterfile','fa fa-chart-line sub_menu_ico',5215," . $params['levelid'] . ")";
    } //end function 
    public function timeadjustment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5217,1,'Time Adjustment','',0,'\\1918','$parent',0,0,0," . $params['levelid'] . ") ,
        (5218,1,'Allow View Time Adjustment','',0,'\\191801','\\1918',0,0,0," . $params['levelid'] . ") ,
        (5219,1,'Allow Click Button Save Time Adjustment','',0,'\\191802','\\1918',0,0,0," . $params['levelid'] . ") ,
        (5220,1,'Allow Click Button Edit Time Adjustment','',0,'\\191804','\\1918',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'timeadjustment','/headtable/payrollcustomform/timeadj','Time Adjustment','fa fa-user-clock sub_menu_ico',5217," . $params['levelid'] . ")";
    } //end function 

    public function generationmaster($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5225,1,'Generation Master','',0,'\\1810','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'generationmaster','/tableentries/hrisentry/generationmaster','Generation Master','fa fa-user sub_menu_ico',5225," . $params['levelid'] . ")";
    } //end function

    public function regularizationprocess($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5226,1,'Regularization Process','',0,'\\1811','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'regularizationprocess','/tableentries/hrisentry/regularizationprocess','Regularization Process','fa fa-user sub_menu_ico',5226," . $params['levelid'] . ")";
    } //end function

    public function brgyofficial($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5266,1,'Barangay Official Setup','',0,'\\24034','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'brgyofficial','/tableentries/tableentry/brgyofficial','Barangay Official Setup','fa fa-user sub_menu_ico',5266," . $params['levelid'] . ")";
    } //end function

    public function streetsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5278,1,'Street List Setup','',0,'\\24035','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'streetsetup','/tableentries/tableentry/streetsetup','Street List Setup','fa fa-list sub_menu_ico',5278," . $params['levelid'] . ")";
    } //end function

    public function clearancetype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5279,1,'Local Clearance Type Setup','',0,'\\24036','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'clearancetype','/tableentries/tableentry/clearancetype','Local Clearance Type Setup','fa fa-list sub_menu_ico',5279," . $params['levelid'] . ")";
    } //end function

    public function businesstype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5280,1,'Business Type Setup','',0,'\\24037','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'businesstype','/tableentries/tableentry/businesstype','Business Type Setup','fa fa-list sub_menu_ico',5280," . $params['levelid'] . ")";
    } //end function

    // public function samplepo($params, $parent, $sort)
    // {
    //     $p = $parent;
    //     $parent = '\\' . $parent;
    //     $qry = "(5224,0,'Purchase Order2','',0,'\\401','$parent',0,'0',0," . $params['levelid'] . "),
    //     (5225,0,'Allow View Transaction PO','PO',0,'\\40101','\\401',0,'0',0," . $params['levelid'] . ")";
    //     $this->insertattribute($params, $qry);
    //     return "($sort,$p,'PO','/amodule/purchase/PO2','Purchase Order2','fa fa-tasks sub_menu_ico',5224,".$params['levelid'].")";
    // } //end function

    // public function samplepo2($params, $parent, $sort)
    // {
    //     $p = $parent;
    //     $parent = '\\'.$parent;
    //     $qry = "(5226,0,'Purchase Order3','',0,'\\402','$parent',0,'0',0,".$params['levelid']."),
    //     (5227,0,'Allow View Transaction PO3','PO',0,'\\42101','\\402',0,'0',0,".$params['levelid'].")";
    //     $this->insertattribute($params, $qry);
    //     return "($sort,$p,'PO','/amodule/purchase/PO3','Purchase Order3','fa fa-tasks sub_menu_ico',5226,".$params['levelid'].")";
    // }

    public function kp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5240,0,'Counter Receipt (AP)','',0,'\\208','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5241,0,'Allow View Transaction KP ','KP',0,'\\20801','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5242,0,'Allow Click Edit Button  KP ','',0,'\\20802','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5243,0,'Allow Click New Button KP ','',0,'\\20803','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5244,0,'Allow Click Save Button KP ','',0,'\\20804','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5245,0,'Allow Click Delete Button KP ','',0,'\\20805','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5246,0,'Allow Click Print Button KP ','',0,'\\20806','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5247,0,'Allow Click Lock Button KP ','',0,'\\20807','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5248,0,'Allow Click UnLock Button KP ','',0,'\\20808','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5249,0,'Allow Click Post Button KP ','',0,'\\20809','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5250,0,'Allow Click UnPost Button KP ','',0,'\\20810','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5251,0,'Allow Click Add Account KP','',0,'\\20811','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5252,0,'Allow Click Edit Account KP','',0,'\\20812','\\208',0,'0',0," . $params['levelid'] . ") ,
        (5253,0,'Allow Click Delete Account KP','',0,'\\20813','\\208',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'KP','/module/payable/kp','Counter Receipt','fa fa-calculator sub_menu_ico',5240," . $params['levelid'] . ")";
    } //end function

    public function bg($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5258,0,'Barangay Member Ledger','',0,'\\103','$parent',0,'0',0," . $params['levelid'] . "),
        (5259,0,'Allow View Barangay Member Ledger','BG',0,'\\10301','\\103',0,'0',0," . $params['levelid'] . "),
        (5260,0,'Allow Click Edit Button BG','',0,'\\10302','\\103',0,'0',0," . $params['levelid'] . "),
        (5261,0,'Allow Click New Button BG','',0,'\\10303','\\103',0,'0',0," . $params['levelid'] . "),
        (5262,0,'Allow Click Save Button BG','',0,'\\10304','\\103',0,'0',0," . $params['levelid'] . "),        
        (5263,0,'Allow Click Delete Button BG','',0,'\\10306','\\103',0,'0',0," . $params['levelid'] . "),
        (5264,0,'Allow Click Print Button BG','',0,'\\10307','\\103',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'bg','/ledgergrid/masterfile/bg','Barangay Member','fa fa-address-card sub_menu_ico',5258," . $params['levelid'] . ")";
    } //end function

    public function bu($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5282,0,'Business Ledger','',0,'\\121','$parent',0,'0',0," . $params['levelid'] . "),
        (5283,0,'Allow View Business Ledger','BU',0,'\\12101','\\121',0,'0',0," . $params['levelid'] . "),
        (5284,0,'Allow Click Edit Button BL','',0,'\\12102','\\121',0,'0',0," . $params['levelid'] . "),
        (5285,0,'Allow Click New Button BL','',0,'\\12103','\\121',0,'0',0," . $params['levelid'] . "),
        (5286,0,'Allow Click Save Button BL','',0,'\\12104','\\121',0,'0',0," . $params['levelid'] . "),        
        (5287,0,'Allow Click Delete Button BL','',0,'\\12105','\\121',0,'0',0," . $params['levelid'] . "),
        (5288,0,'Allow Click Print Button BL','',0,'\\12106','\\121',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'bg','/ledgergrid/masterfile/bu','Business Ledger','fa fa-address-card sub_menu_ico',5282," . $params['levelid'] . ")";
    } //end function

    public function parentbarangay($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5281,0,'BARANGAY OPERATION','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'BARANGAY OPERATION',$sort,'fas fa-chalkboard-teacher',',,'," . $params['levelid'] . ")";
    } //end function
    public function bd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5267,0,'Local Clearance','',0,'\\4101','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5268,0,'Allow View Transaction BD ','BD',0,'\\410101','\\4101',0,'0',0," . $params['levelid'] . ") ,
        (5269,0,'Allow Click Edit Button  BD ','',0,'\\410102','\\4101',0,'0',0," . $params['levelid'] . ") ,
        (5270,0,'Allow Click New Button BD ','',0,'\\410103','\\4101',0,'0',0," . $params['levelid'] . ") ,
        (5271,0,'Allow Click Save Button BD ','',0,'\\410104','\\4101',0,'0',0," . $params['levelid'] . ") ,
        (5272,0,'Allow Click Delete Button BD ','',0,'\\410105','\\4101',0,'0',0," . $params['levelid'] . ") ,
        (5273,0,'Allow Click Print Button BD ','',0,'\\410106','\\4101',0,'0',0," . $params['levelid'] . ") ,
        (5274,0,'Allow Click Lock Button BD ','',0,'\\410107','\\4101',0,'0',0," . $params['levelid'] . ") ,
        (5275,0,'Allow Click UnLock Button BD ','',0,'\\410108','\\4101',0,'0',0," . $params['levelid'] . ") ,
        (5276,0,'Allow Click Post Button BD ','',0,'\\410109','\\4101',0,'0',0," . $params['levelid'] . ") ,
        (5277,0,'Allow Click UnPost Button BD ','',0,'\\410110','\\4101',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BD','/module/barangay/bd','Local Clearance','fa fa-clipboard sub_menu_ico',5267," . $params['levelid'] . ")";
    } //end function


    public function bc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5289,0,'Business Clearance','',0,'\\4102','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5290,0,'Allow View Transaction BC ','BC',0,'\\410201','\\4102',0,'0',0," . $params['levelid'] . ") ,
        (5291,0,'Allow Click Edit Button  BC ','',0,'\\410202','\\4102',0,'0',0," . $params['levelid'] . ") ,
        (5292,0,'Allow Click New Button BC ','',0,'\\410203','\\4102',0,'0',0," . $params['levelid'] . ") ,
        (5293,0,'Allow Click Save Button BC ','',0,'\\410204','\\4102',0,'0',0," . $params['levelid'] . ") ,
        (5294,0,'Allow Click Delete Button BC ','',0,'\\410205','\\4102',0,'0',0," . $params['levelid'] . ") ,
        (5295,0,'Allow Click Print Button BC ','',0,'\\410206','\\4102',0,'0',0," . $params['levelid'] . ") ,
        (5296,0,'Allow Click Lock Button BC ','',0,'\\410207','\\4102',0,'0',0," . $params['levelid'] . ") ,
        (5297,0,'Allow Click UnLock Button BC ','',0,'\\410208','\\4102',0,'0',0," . $params['levelid'] . ") ,
        (5298,0,'Allow Click Post Button BC ','',0,'\\410209','\\4102',0,'0',0," . $params['levelid'] . ") ,
        (5299,0,'Allow Click UnPost Button BC ','',0,'\\410210','\\4102',0,'0',0," . $params['levelid'] . ");";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BC','/module/barangay/bc','Business Clearance','fa fa-clipboard sub_menu_ico',5289," . $params['levelid'] . ")";
    } //end function
    public function itinerary($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5351,0,'Travel Application','',0,'\\2039','$parent',0,0,0," . $params['levelid'] . ") ,
        (5352,0,'Allow View Travel Application','',0,'\\203905','\\2039',0,0,0," . $params['levelid'] . "),
        (5353,0,'Allow Click New Button Travel Application','',0,'\\203903','\\2039',0,0,0," . $params['levelid'] . ") ,
        (5354,0,'Allow Click Save Button Travel Application','',0,'\\203901','\\2039',0,0,0," . $params['levelid'] . ") ,
        (5355,0,'Allow Click Delete Button Travel Application','',0,'\\203904','\\2039',0,0,0," . $params['levelid'] . "),
        (5356,0,'Allow Click Print Button Travel Application','',0,'\\203902','\\2039',0,0,0," . $params['levelid'] . ") ,
        (5357,0,'Allow Click Edit Button Travel Application','',0,'\\203906','\\2039',0,0,0," . $params['levelid'] . "),
        (5358,0,'Allow View Dashboard Travel Application','',0,'\\203907','\\2039',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'itinerary','/ledgergrid/payroll/itinerary','Travel Application','fa fa-money-check sub_menu_ico',5351," . $params['levelid'] . ")";
    } //end function 

    public function locationsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5362,1,'Location Setup','',0,'\\1919','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'emploc','/tableentries/payrollsetup/locationsetup','Location Setup','fa fa-boxes sub_menu_ico',5362," . $params['levelid'] . ")";
    } //end function
    public function moduleapproval($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5363,0,'Module Approval','',0,'\\899','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'moduleapproval','/tableentries/tableentry/moduleapproval','Module Approval','fa fa-upload sub_menu_ico',5363," . $params['levelid'] . ")";
    } //end function       

    public function pos_log($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5364,0,'POS Log','',0,'\\517','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'POS LOG','/headtable/pos/poslog','POS LOG','fa fa-upload sub_menu_ico',5364," . $params['levelid'] . ")";
    } //end function  


    public function parentpcf($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(5372,0,'PCF','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'PCF',$sort,'list_alt',',pcf,pces'," . $params['levelid'] . ")";
    } //end function


    public function pces($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5374,0,'Proj Costing Expenses Setup','',0,'\\4201','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pces','/tableentries/tableentry/entrypces','Proj Costing Expenses Setup','fa fa-money-check sub_menu_ico',5374," . $params['levelid'] . ")";
    } //end function

    public function pcfcur($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5412,0,'PCF Currency','',0,'\\4203','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pcfcur','/headtable/customform/pcfcurrency','PCF Currency Setup','fa fa-money-check sub_menu_ico',5412," . $params['levelid'] . ")";
    } //end function


    public function px($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5375,0,'Project Costing Form','',0,'\\4202','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5376,0,'Allow View Transaction PX','PX',0,'\\420201','\\4202',0,'0',0," . $params['levelid'] . ") ,
        (5377,0,'Allow Click Edit Button PX','',0,'\\420202','\\4202',0,'0',0," . $params['levelid'] . ") ,
        (5378,0,'Allow Click New Button PX','',0,'\\420203','\\4202',0,'0',0," . $params['levelid'] . ") ,
        (5379,0,'Allow Click Save Button PX','',0,'\\420204','\\4202',0,'0',0," . $params['levelid'] . ") ,
        (5380,0,'Allow Click Delete Button PX','',0,'\\420204','\\4202',0,'0',0," . $params['levelid'] . ") ,
        (5381,0,'Allow Click Print Button PX','',0,'\\420205','\\4202',0,'0',0," . $params['levelid'] . ") ,
        (5382,0,'Allow Click Lock Button PX','',0,'\\420206','\\4202',0,'0',0," . $params['levelid'] . ") ,
        (5383,0,'Allow Click UnLock Button PX','',0,'\\420207','\\4202',0,'0',0," . $params['levelid'] . ") ,
        (5384,0,'Allow Click Post Button PX','',0,'\\420208','\\4202',0,'0',0," . $params['levelid'] . ") ,
        (5385,0,'Allow Click UnPost Button PX','',0,'\\420209','\\4202',0,'0',0," . $params['levelid'] . "),
        (5386,0,'Allow Click Delete Item PX','',0,'\\420210','\\4202',0,'0',0," . $params['levelid'] . "),
        (5387,1,'Allow Click Add Item PX','',0,'\\420211','\\4202',0,'0',0," . $params['levelid'] . "),
        (5388,1,'Allow Click Edit Item PX','',0,'\\420212','\\4202',0,'0',0," . $params['levelid'] . "),
        (5389,0,'PCF Administrator','',0,'\\420213','\\4202',0,'0',0," . $params['levelid'] . "),
        (5413,0,'View PCF Summary Report as Product Head','',0,'\\420214','\\4202',0,'0',0," . $params['levelid'] . "),
        (5554,0,'View PCF Summary Report as Sales Head','',0,'\\420215','\\4202',0,'0',0," . $params['levelid'] . ") ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PX','/module/pcf/px','Project Costing Form','fa fa-file sub_menu_ico',5375," . $params['levelid'] . ")";
    } //end function     


    public function ep($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5392,1,'Employee Record','',0,'\\4205','$parent',0,0,0," . $params['levelid'] . ") ,
        (5393,1,'Allow Click Button Dependents','',0,'\\420501','\\4205',0,0,0," . $params['levelid'] . ") ,
        (5394,1,'Allow Click Button Education','',0,'\\420502','\\4205',0,0,0," . $params['levelid'] . ") ,
        (5395,1,'Allow Click Button Advances','',0,'\\420503','\\4205',0,0,0," . $params['levelid'] . ") ,
        (5396,1,'Allow Click Button Loans','',0,'\\420504','\\4205',0,0,0," . $params['levelid'] . ") ,
        (5397,1,'Allow Click Button Employment','',0,'\\420505','\\4205',0,0,0," . $params['levelid'] . ") ,
        (5398,1,'Allow Click Button Allowance','',0,'\\420506','\\4205',0,0,0," . $params['levelid'] . ") ,
        (5399,1,'Allow Click Button Training','',0,'\\420507','\\4205',0,0,0," . $params['levelid'] . ") ,
        (5400,1,'Allow Click Button Turn Over and Return Items','',0,'\\420508','\\4205',0,0,0," . $params['levelid'] . ") ,

        (5401,1,'Allow View Employee Record','',0,'\\420509','\\4205',0,'0',0," . $params['levelid'] . ") ,
        (5402,1,'Allow Click Edit Button EMP','',0,'\\420510','\\4205',0,'0',0," . $params['levelid'] . ") ,
        (5403,1,'Allow Click New Button EMP','',0,'\\420511','\\4205',0,'0',0," . $params['levelid'] . ") ,
        (5404,1,'Allow Click Save Button EMP','',0,'\\420512','\\4205',0,'0',0," . $params['levelid'] . ") ,
        (5405,1,'Allow Click Change Code EMP','',0,'\\420513','\\4205',0,'0',0," . $params['levelid'] . ") ,
        (5406,1,'Allow Click Delete Button EMP','',0,'\\420514','\\4205',0,'0',0," . $params['levelid'] . ") ,
        (5407,1,'Allow Click Print Button EMP','',0,'\\420515','\\4205',0,'0',0," . $params['levelid'] . "),
        
        (2410,1,'Payroll Level 1','',0,'\\420516','\\4205',0,0,0," . $params['levelid'] . "),
        (2411,1,'Payroll Level 2','',0,'\\420516','\\4205',0,0,0," . $params['levelid'] . "),
        (2412,1,'Payroll Level 3','',0,'\\420517','\\4205',0,0,0," . $params['levelid'] . "),
        (2413,1,'Payroll Level 4','',0,'\\420518','\\4205',0,0,0," . $params['levelid'] . "),
        (2414,1,'Payroll Level 5','',0,'\\420519','\\4205',0,0,0," . $params['levelid'] . "),
        (2415,1,'Payroll Level 6','',0,'\\420520','\\4205',0,0,0," . $params['levelid'] . "),
        (2416,1,'Payroll Level 7','',0,'\\420521','\\4205',0,0,0," . $params['levelid'] . "),
        (2417,1,'Payroll Level 8','',0,'\\420522','\\4205',0,0,0," . $params['levelid'] . "),
        (2418,1,'Payroll Level 9','',0,'\\420523','\\4205',0,0,0," . $params['levelid'] . "),
        (2419,1,'Payroll Level 10','',0,'\\420524','\\4205',0,0,0," . $params['levelid'] . "),
        (5228,1,'Allow To View All Employees','',0,'\\420525','\\4205',0,'0',0," . $params['levelid'] . "),
        (5300,1,'Allow To View Rate','',0,'\\420526','\\4205',0,'0',0," . $params['levelid'] . ")
        
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EM','/ledgergrid/payroll/ep','Employee Record','fa fa-user sub_menu_ico',5392," . $params['levelid'] . ")";
    } //end function

    public function certrate($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5447,1,'Certificate Rate Setup','',0,'\\4204','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'certrate','/tableentries/tableentry/entrycertrate','Certificate Rate Setup','fa fa-list sub_menu_ico',5447," . $params['levelid'] . ")";
    } //end function

    public function parenttm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(5458,0,'TASK MONITORING','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'TASK MONITORING',$sort,'fa fa-address-book',',tm,tasktype'," . $params['levelid'] . ")";
    } //end function

    public function tm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5459,1,'Task Setup','',0,'\\4206','$parent',0,0,0," . $params['levelid'] . ") ,
        (5460,1,'Allow View Task Setup','',0,'\\420601','\\4206',0,0,0," . $params['levelid'] . ") ,
        (5461,1,'Allow Click New Button Task Setup','',0,'\\420605','\\4206',0,0,0," . $params['levelid'] . ") ,
        (5462,1,'Allow Click Edit Button Task Setup','',0,'\\420602','\\4206',0,0,0," . $params['levelid'] . "),
        (5463,1,'Allow Click Save Button Task Setup','',0,'\\420603','\\4206',0,0,0," . $params['levelid'] . ") ,
        (5464,1,'Allow Click Print Button Task Setup','',0,'\\420604','\\4206',0,0,0," . $params['levelid'] . ") ,        
        (5465,1,'Allow Click Delete Button Task Setup','',0,'\\420606','\\4206',0,0,0," . $params['levelid'] . "),
        (5470,1,'Allow Click Add Task Details','',0,'\\420607','\\4206',0,0,0," . $params['levelid'] . ") , 
        (5471,1,'Allow Click Add Attachment Task Setup','',0,'\\420608','\\4206',0,0,0," . $params['levelid'] . ") , 
        (5472,1,'Allow Click View Attachment Task Setup','',0,'\\420609','\\4206',0,0,0," . $params['levelid'] . "),
        (5478,1,'Allow Click Reassign Task','',0,'\\420610','\\4206',0,0,0," . $params['levelid'] . "),
        (5480,1,'Allow View Rate','',0,'\\4206011','\\4206',0,0,0," . $params['levelid'] . "),
        (5572,0,'Allow Click Delete Button Per Task','',0,'\\4206012','\\4206',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tm','/ledgergrid/taskmonitoring/tm','Task Setup','fa fa-user-clock sub_menu_ico',5459," . $params['levelid'] . ")";
    } //end function

    public function tasktype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5469,0,'Type Setup','',0,'\\4207','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tasktype','/tableentries/tableentry/entrytasktype','Type Setup','fa fa-money-check sub_menu_ico',5469," . $params['levelid'] . ")";
    } //end function

    public function tk($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5473,1,'Task Monitoring','',0,'\\4208','$parent',0,0,0," . $params['levelid'] . ") ,
        (5474,1,'Allow View Task Monitoring','',0,'\\420801','\\4208',0,0,0," . $params['levelid'] . "),
        (5475,1,'Allow Click Edit Button Task Monitoring','',0,'\\420802','\\4208',0,0,0," . $params['levelid'] . "),
        (5476,1,'Allow Click Save Button Shift SetupTask Monitoring','',0,'\\420803','\\4208',0,0,0," . $params['levelid'] . "),
        (5477,0,'Allow View Dashboard Task Monitoring','',0,'\\420804','\\4208',0,0,0," . $params['levelid'] . "),
        (5479,0,'Allow View All Task Monitoring','',0,'\\420805','\\4208',0,0,0," . $params['levelid'] . "),
        (5481,0,'Allow View Complete Button Task Monitoring','',0,'\\420806','\\4208',0,0,0," . $params['levelid'] . "),
        (5483,0,'Allow View Dashboard Pending task','',0,'\\420807','\\4208',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tk','/ledgergrid/taskmonitoring/tk','Task Monitoring','fa fa-user-clock sub_menu_ico',5473," . $params['levelid'] . ")";
    } //end function

    public function ear($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5483,1,'Employee Activity Report','',0,'\\370','$parent',0,0,0," . $params['levelid'] . ") ,
        (5484,1,'Allow View Employee Activity Report','',0,'\\37001','\\370',0,0,0," . $params['levelid'] . "),
        (5511,0,'Allow View All Employee Activity Report','',0,'\\37002','\\370',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ear','/ledgergrid/c2753870b24c0fedf7c4f4ea67d0d1934/ear','Employee Activity Report','fa fa-clone sub_menu_ico',5483," . $params['levelid'] . ")";
    } //end function <i class="fab fa-buffer"></i>


    public function nb($params, $parent, $sort)
    {
        $label = 'Biometric Uploading';
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5509,1,'" . $label . "','',0,'\\4209','$parent',0,0,0," . $params['levelid'] . ") ,
        (5510,1,'Allow View " . $label . "','',0,'\\420901','\\4209',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'nb','/headtable/payrollcustomform/nb','" . $label . "','fa fa-calculator sub_menu_ico',5509," . $params['levelid'] . ")";
    } //end function
    public function changeschedule($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5512,1,'Change Schedule','',0,'\\2033','$parent',0,0,0," . $params['levelid'] . ") ,
        (5513,1,'Allow View Change Schedule','',0,'\\203301','\\2033',0,0,0," . $params['levelid'] . ") ,
        (5514,1,'Allow Click Button Save Change Schedule','',0,'\\203302','\\2033',0,0,0," . $params['levelid'] . "),
        (5515,1,'Allow Click Button Edit Change Schedule','',0,'\\203303','\\2006',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'changeschedule','/headtable/payrollcustomform/changeschedule','Change Schedule Timecard','fa fa-id-card-alt sub_menu_ico',5512," . $params['levelid'] . ")";
    } //end function <i class="fas fa-id-card-alt"></i>

    public function ch($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5516,0,'Consign Invoice','',0,'\\530','$parent',0,'0',0," . $params['levelid'] . "),
        (5517,0,'Allow View Transaction Consign Inv.','SK',0,'\\53001','\\530',0,'0',0," . $params['levelid'] . "),
        (5518,0,'Allow Click Edit Button Consign Inv.','',0,'\\53002','\\530',0,'0',0," . $params['levelid'] . "),
        (5519,0,'Allow Click New  Button Consign Inv.','',0,'\\53003','\\530',0,'0',0," . $params['levelid'] . "),
        (5520,0,'Allow Click Save Button Consign Inv.','',0,'\\53004','\\530',0,'0',0," . $params['levelid'] . "),
        (5521,0,'Allow Click Delete Button Consign Inv.','',0,'\\53005','\\530',0,'0',0," . $params['levelid'] . "),
        (5522,0,'Allow Click Print Button Consign Inv.','',0,'\\53006','\\530',0,'0',0," . $params['levelid'] . "),
        (5523,0,'Allow Click Lock Button Consign Inv.','',0,'\\53007','\\530',0,'0',0," . $params['levelid'] . "),
        (5524,0,'Allow Click UnLock Button Consign Inv.','',0,'\\53008','\\530',0,'0',0," . $params['levelid'] . "),
        (5525,0,'Allow Click Post Button Consign Inv.','',0,'\\53009','\\530',0,'0',0," . $params['levelid'] . "),
        (5526,0,'Allow Click UnPost  Button Consign Inv.','',0,'\\53010','\\530',0,'0',0," . $params['levelid'] . "),
        (5527,0,'Allow Change Amount Consign Inv.','',0,'\\53011','\\530',0,'0',0," . $params['levelid'] . "),
        (5528,0,'Allow View Transaction Accounting Consign Inv.','',0,'\\53014','\\530',0,'0',0," . $params['levelid'] . "),
        (5529,1,'Allow Click Add Item Consign Inv.','',0,'\\53015','\\530',0,'0',0," . $params['levelid'] . "),
        (5530,1,'Allow Click Edit Item Consign Inv.','',0,'\\53016','\\530',0,'0',0," . $params['levelid'] . "),
        (5531,1,'Allow Click Delete Item Consign Inv.','',0,'\\53017','\\530',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CH','/module/e4c3fe3674108174825a187099e7349f6/ch','Consign Invoice','fa fa-file-invoice sub_menu_ico',5516," . $params['levelid'] . ")";
    } //end function

    public function on($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5532,0,'Outright Invoice','',0,'\\531','$parent',0,'0',0," . $params['levelid'] . "),
        (5533,0,'Allow View Transaction Outright Inv.','SK',0,'\\53101','\\531',0,'0',0," . $params['levelid'] . "),
        (5534,0,'Allow Click Edit Button Outright Inv.','',0,'\\53102','\\531',0,'0',0," . $params['levelid'] . "),
        (5535,0,'Allow Click New  Button Outright Inv.','',0,'\\53103','\\531',0,'0',0," . $params['levelid'] . "),
        (5536,0,'Allow Click Save Button Outright Inv.','',0,'\\53104','\\531',0,'0',0," . $params['levelid'] . "),
        (5537,0,'Allow Click Delete Button Outright Inv.','',0,'\\53105','\\531',0,'0',0," . $params['levelid'] . "),
        (5538,0,'Allow Click Print Button Outright Inv.','',0,'\\53106','\\531',0,'0',0," . $params['levelid'] . "),
        (5539,0,'Allow Click Lock Button Outright Inv.','',0,'\\53107','\\531',0,'0',0," . $params['levelid'] . "),
        (5540,0,'Allow Click UnLock Button Outright Inv.','',0,'\\53108','\\531',0,'0',0," . $params['levelid'] . "),
        (5541,0,'Allow Click Post Button Outright Inv.','',0,'\\53109','\\531',0,'0',0," . $params['levelid'] . "),
        (5542,0,'Allow Click UnPost  Button Outright Inv.','',0,'\\53110','\\531',0,'0',0," . $params['levelid'] . "),
        (5543,0,'Allow Change Amount Outright Inv.','',0,'\\53111','\\531',0,'0',0," . $params['levelid'] . "),
        (5544,0,'Allow View Transaction Accounting Outright Inv.','',0,'\\53114','\\531',0,'0',0," . $params['levelid'] . "),
        (5545,1,'Allow Click Add Item Outright Inv.','',0,'\\53115','\\531',0,'0',0," . $params['levelid'] . "),
        (5546,1,'Allow Click Edit Item Outright Inv.','',0,'\\53116','\\531',0,'0',0," . $params['levelid'] . "),
        (5547,1,'Allow Click Delete Item Outright Inv.','',0,'\\53117','\\531',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ON','/module/e4c3fe3674108174825a187099e7349f6/on','Outright Invoice','fa fa-file-invoice sub_menu_ico',5532," . $params['levelid'] . ")";
    } //end function

    public function repacker($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5549,1,'Repacker Setup','',0,'\\24038','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'repacker','/tableentries/tableentry/entryrepacker','Repacker Setup','fa fa-box sub_menu_ico',5549," . $params['levelid'] . ")";
    } //end function

    public function timesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5552,1,'Time Setup','',0,'\\2040','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'timesetup','/tableentries/payrollentry/entrytimesetup','Time Setup','fa fa-user-clock sub_menu_ico',5552," . $params['levelid'] . ")";
    } //end function

    public function dy($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5557,1,'Daily Task','',0,'\\2041','$parent',0,0,0," . $params['levelid'] . ") ,
        (5558,1,'Allow Click Save Button Daily Task','',0,'\\204101','\\2041',0,0,0," . $params['levelid'] . ") ,
        (5559,1,'Allow Click Print Button Daily Task','',0,'\\204102','\\2041',0,0,0," . $params['levelid'] . ") ,
        (5560,1,'Allow Click New Button Daily Task','',0,'\\204103','\\2041',0,0,0," . $params['levelid'] . ") ,
        (5561,1,'Allow Click Delete Button Daily Task','',0,'\\204104','\\2041',0,0,0," . $params['levelid'] . ") ,
        (5562,1,'Allow View Daily Task','',0,'\\204105','\\2041',0,0,0," . $params['levelid'] . ") ,
        (5563,1,'Allow Click Edit Button Daily Task','',0,'\\204106','\\2041',0,0,0," . $params['levelid'] . "),
        (5564,1,'Allow View Dashboard Daily Task','',0,'\\204107','\\2041',0,0,0," . $params['levelid'] . "),
        (5567,1,'Allow Click Add Attachment Daily Task','',0,'\\204108','\\2041',0,0,0," . $params['levelid'] . "),
        (5584,0,'Allow View All Daily Task','',0,'\\204109','\\2041',0,0,0," . $params['levelid'] . "),
        (5601,0,'Allow bypass of checker requirements','',0,'\\204110','\\2041',0,0,0," . $params['levelid'] . ") ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'dy','/ledger/taskmonitoring/dy','Daily Task','fa fa-calendar-day sub_menu_ico',5557," . $params['levelid'] . ")";
    } //end function

    public function entryreasonforhiring($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5570,1,'Reason For Hiring Setup','',0,'\\1812','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entryreasonforhiring','/tableentries/payrollsetup/entryreasonforhiring','Reason For Hiring Setup','fa fa-user-plus sub_menu_ico',5570," . $params['levelid'] . ")";
    } //end function

    public function empstatustypeentry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5571,1,'Employee Status Type','',0,'\\1813','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'empstatustypeentry','/tableentries/payrollsetup/empstatustypeentry','Employee Status Type','fa fa-user sub_menu_ico',5571," . $params['levelid'] . ")";
    } //end function

    public function parentqs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(5577,0,'QUEUING','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'QUEUING',$sort,'fa fa-hourglass-half',',counter,service,ctr,display,ticketing,closequeue'," . $params['levelid'] . ")";
    } //end function

    public function counter($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5579,0,'Counter Setup','',0,'\\4210','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'counter','/tableentries/tableentry/entrycounter','Counter Setup','fa fa-users sub_menu_ico',5579," . $params['levelid'] . ")";
    } //end function

    public function service($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5580,0,'Service Setup','',0,'\\4211','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'service','/tableentries/tableentry/entryservice','Service Setup','fa fa-spinner sub_menu_ico',5580," . $params['levelid'] . ")";
    } //end function

    public function ctr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5581,0,'Counter Screen','',0,'\\4212','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ctr','/queuing/counter','Counter Screen','fa fa-address-card sub_menu_ico',5581," . $params['levelid'] . ")";
    } //end function

    public function display($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5582,0,'Display Screen','',0,'\\4213','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'display','/queuing/display','Display Screen','fa fa-tv sub_menu_ico',5582," . $params['levelid'] . ")";
    } //end function

    public function ticketing($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5583,0,'Ticketing','',0,'\\4214','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ticketing','/queuing/service','Ticketing','fa fa-hashtag sub_menu_ico',5583," . $params['levelid'] . ")";
    } //end function

    public function closequeue($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5624,0,'Close Queuing Day','',0,'\\4215','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'closequeue','/tableentries/queuing/closequeue','Close Queuing','fa fa-lock sub_menu_ico',5624," . $params['levelid'] . ")";
    } //end function

    public function employeeoverview($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $parent2 = '\\2112';
        $qry = "(5593,0,'Employee Overview','',0,'$parent2','$parent',0,'',0," . $params['levelid'] . "),
        (5594,1,'Allow View Employee Overview','',0,'\\90002','$parent2',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'employeeoverview','/tableentries/hrisentry/employeeoverview','Employee Overview','fas fa-suitcase sub_menu_ico',5593," . $params['levelid'] . ")";
    } //end function


    public function taskcategory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5600,0,'Task Category Setup','',0,'\\4215','$parent',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'taskcategory','/tableentries/taskentry/entrytaskcategory','Task Category Setup','fa fa-list sub_menu_ico',5600," . $params['levelid'] . ")";
    } //end function
    public function parentbmssetup($params, $parent, $sort)
    {
        $modules = "',brgyofficial,streetsetup,clearancetype,businesstype'";
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5607,0,'BARANGAY SETUP','',0,'$parent','\\',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc,levelid) values($p,'BARANGAY SETUP',$sort,'list_alt'," . $modules . "," . $params['levelid'] . ")";
    } //end function

    public function tl($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5610,0,'T.R.U Ledger','',0,'\\103','$parent',0,'0',0," . $params['levelid'] . "),
        (5611,0,'Allow View T.R.U Ledger','TL',0,'\\10301','\\103',0,'0',0," . $params['levelid'] . "),
        (5612,0,'Allow Click Edit Button TL','',0,'\\10302','\\103',0,'0',0," . $params['levelid'] . "),
        (5613,0,'Allow Click New Button TL','',0,'\\10303','\\103',0,'0',0," . $params['levelid'] . "),
        (5614,0,'Allow Click Save Button TL','',0,'\\10304','\\103',0,'0',0," . $params['levelid'] . "),        
        (5615,0,'Allow Click Delete Button TL','',0,'\\10306','\\103',0,'0',0," . $params['levelid'] . "),
        (5616,0,'Allow Click Print Button TL','',0,'\\10307','\\103',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tl','/ledgergrid/masterfile/tl','T.R.U Ledger','fa fa-address-card sub_menu_ico',5610," . $params['levelid'] . ")";
    } //end function

    public function trutype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5621,1,'TRU Type Setup','',0,'\\1114','$parent',0,0,0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'trutype','/tableentries/barangayentry/trutype','TRU Type Setup',
        'fa fa-list sub_menu_ico',5621," . $params['levelid'] . ")";
    } //end function

    public function bonafide($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5623,0,'Bonafide Setup','',0,'\\4216','$parent',0,'0',0," . $params['levelid'] . ")
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'bonafide','/tableentries/barangayentry/entrybonafide','Bonafide Setup','fa fa-list  sub_menu_ico',5623," . $params['levelid'] . ")";
    } //end function

    public function bt($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(5617,0,'T.R.U Clearance','',0,'\\4103','$parent',0,'0',0," . $params['levelid'] . ") ,
        (5618,0,'Allow View Transaction BT', 'BT',0,'\\410301','\\4103',0,'0',0," . $params['levelid'] . ") ,
        (5619,0,'Allow Click Edit Button  BT','',0,'\\410302','\\4103',0,'0',0," . $params['levelid'] . ") ,
        (5620,0,'Allow Click New Button BT','',0,'\\410303','\\4103',0,'0',0," . $params['levelid'] . ") ,
        (5621,0,'Allow Click Save Button BT','',0,'\\410304','\\4103',0,'0',0," . $params['levelid'] . ") ,
        (5622,0,'Allow Click Delete Button BT','',0,'\\410305','\\4103',0,'0',0," . $params['levelid'] . ") ,
        (5623,0,'Allow Click Print Button BT','',0,'\\410306','\\4103',0,'0',0," . $params['levelid'] . ") ,
        (5624,0,'Allow Click Lock Button BT','',0,'\\410307','\\4103',0,'0',0," . $params['levelid'] . ") ,
        (5625,0,'Allow Click UnLock Button BT','',0,'\\410308','\\4103',0,'0',0," . $params['levelid'] . ") ,
        (5626,0,'Allow Click Post Button BT','',0,'\\410309','\\4103',0,'0',0," . $params['levelid'] . ") ,
        (5627,0,'Allow Click UnPost Button BT','',0,'\\410310','\\4103',0,'0',0," . $params['levelid'] . ")";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BT','/module/barangay/bt','T.R.U Clearance','fa fa-clipboard sub_menu_ico',5617," . $params['levelid'] . ")";
    } //end function
}//end 