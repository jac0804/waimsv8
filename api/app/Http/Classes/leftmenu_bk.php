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



// last attribute - 4496

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
// A   Hris Report
// B   Payroll Report
//32 POS
// 33 VEHICLE SCHEDULING


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

//FAMS
// GP  - Gatepass
// FC - Convert to Asset

// CRM
// LD - LEAD

//available attributes


class leftmenu_bk
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
        $qry1 = "insert into `attributes` (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`) values ";
        $qry = str_replace("\\", '\\\\', $qry);
        $this->coreFunctions->execqry($qry1 . $qry);
    }

    public function parentsales($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(150,0,'SALES','',0,'$parent','\\',0,'',0)";
        $this->insertattribute($params, $qry);
        if ($params['companyid'] == 12) { //afti usd
            return "insert into left_parent(id,name,seq,class,doc) values($p,'SALES',$sort,'trending_up',',SO')";
        } else {
            return "insert into left_parent(id,name,seq,class,doc) values($p,'SALES',$sort,'trending_up',',SO,SJ,CM')";
        }
    } //end function

    public function pa($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2318,0,'Price Scheme','',0,'\\24027','$parent',0,'0',0),
        (2319,0,'Allow View Transaction PS','PA',0,'\\2402701','\\24027',0,'0',0),
        (2320,0,'Allow Click Edit Button PS','',0,'\\2402702','\\24027',0,'0',0),
        (2321,0,'Allow Click New  Button PS','',0,'\\2402703','\\24027',0,'0',0),
        (2322,0,'Allow Click Save Button PS','',0,'\\2402704','\\24027',0,'0',0),
        (2324,0,'Allow Click Delete Button PS','',0,'\\2402706','\\24027',0,'0',0),
        (2325,0,'Allow Click Print Button PS','',0,'\\2402707','\\24027',0,'0',0),
        (2326,0,'Allow Click Lock Button PS','',0,'\\2402708','\\24027',0,'0',0),
        (2327,0,'Allow Click UnLock Button PS','',0,'\\2402709','\\24027',0,'0',0),
        (2328,0,'Allow Click Post Button PS','',0,'\\2402710','\\24027',0,'0',0),
        (2329,0,'Allow Click UnPost  Button PS','',0,'\\2402711','\\24027',0,'0',0),
        (2330,1,'Allow Click Add Item PS','',0,'\\2402712','\\24027',0,'0',0),
        (2331,1,'Allow Click Edit Item PS','',0,'\\2402713','\\24027',0,'0',0),
        (2332,1,'Allow Click Delete Item PS','',0,'\\2402714','\\24027',0,'0',0),
        (3621,1,'Allow Void Button','',0,'\\24027145','\\24027',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'PA','/module/pos/pa','Price Scheme','fa fa-tags sub_menu_ico',2318)";
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
        }

        $qry = " (151,0,'" . $modulename . "','',0,'\\501','$parent',0,'0',0),
        (152,0,'Allow View Transaction SO','SO',0,'\\50101','\\501',0,'0',0),
        (153,0,'Allow Click Edit Button SO','',0,'\\50102','\\501',0,'0',0),
        (154,0,'Allow Click New  Button SO','',0,'\\50103','\\501',0,'0',0),
        (155,0,'Allow Click Save Button SO','',0,'\\50104','\\501',0,'0',0),
        (157,0,'Allow Click Delete Button SO','',0,'\\50106','\\501',0,'0',0),
        (158,0,'Allow Click Print Button SO','',0,'\\50107','\\501',0,'0',0),
        (159,0,'Allow Click Lock Button SO','',0,'\\50108','\\501',0,'0',0),
        (160,0,'Allow Click UnLock Button SO','',0,'\\50109','\\501',0,'0',0),
        (161,0,'Allow Change Amount  SO','',0,'\\50110','\\501',0,'0',0),
        (162,0,'Allow Check Credit Limit SO','',0,'\\50111','\\501',0,'0',0),
        (163,0,'Allow Click Post Button SO','',0,'\\50112','\\501',0,'0',0),
        (164,0,'Allow Click UnPost  Button SO','',0,'\\50113','\\501',0,'0',0),
        (805,1,'Allow Click Add Item SO','',0,'\\50114','\\501',0,'0',0),
        (806,1,'Allow Click Edit Item SO','',0,'\\50115','\\501',0,'0',0),
        (807,1,'Allow Click Delete Item SO','',0,'\\50116','\\501',0,'0',0),
        (3593,1,'Allow Void Button','',0,'\\50118','\\501',0,'0',0)";
        switch ($params['companyid']) {
            case 17: //unihome
                //case 39: // CBBSI
                $qry = $qry . ",(2995,1,'Allow Post Non Cash','',0,'\\50117','\\501',0,'0',0)";
                break;
            case 19: //housegem
                $qry = $qry . ",(3889,1,'Allow View WH Info','',0,'\\50118','\\501',0,'0',0)";
                $qry = $qry . ",(3890,1,'Allow Click Approved Button','',0,'\\50119','\\501',0,'0',0)";
                $qry = $qry . ",(3891,1,'Allow Click Revision Button','',0,'\\50120','\\501',0,'0',0)";
                break;
            case 21: //kinggeorge
            case 28: //xcomp
            case 36: //rozlab
                $qry = $qry . ",(4037,1,'Allow Change Discount SO','',0,'\\50121','\\501',0,'0',0)";
                break;
        }
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SO','/module/" . $folder . "/so','" . $modulename . "','fa fa-clipboard-list sub_menu_ico',151)";
    } //end function

    public function ro($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $folder = 'sales';
        $modulename = 'Request Order';


        $qry = " (3869,0,'" . $modulename . "','',0,'\\522','$parent',0,'0',0),
        (3870,0,'Allow View Transaction RO','RO',0,'\\52201','\\522',0,'0',0),
        (3871,0,'Allow Click Edit Button RO','',0,'\\52202','\\522',0,'0',0),
        (3872,0,'Allow Click New  Button RO','',0,'\\52203','\\522',0,'0',0),
        (3873,0,'Allow Click Save Button RO','',0,'\\52204','\\522',0,'0',0),
        (3874,0,'Allow Click Delete Button RO','',0,'\\52206','\\522',0,'0',0),
        (3875,0,'Allow Click Print Button RO','',0,'\\52207','\\522',0,'0',0),
        (3876,0,'Allow Click Lock Button RO','',0,'\\52208','\\522',0,'0',0),
        (3877,0,'Allow Click UnLock Button RO','',0,'\\52209','\\522',0,'0',0),
        (3878,0,'Allow Change Amount  RO','',0,'\\52210','\\522',0,'0',0),
        (3879,0,'Overwrite Capacity Checking','',0,'\\52211','\\522',0,'0',0),
        (3880,0,'Allow Click Post Button RO','',0,'\\52212','\\522',0,'0',0),
        (3881,0,'Allow Click UnPost  Button RO','',0,'\\52213','\\522',0,'0',0),
        (3882,1,'Allow Click Add Item RO','',0,'\\52214','\\522',0,'0',0),
        (3883,1,'Allow Click Edit Item RO','',0,'\\52215','\\522',0,'0',0),
        (3884,1,'Allow Click Delete Item RO','',0,'\\52216','\\522',0,'0',0),
        (3885,1,'Allow Void Button','',0,'\\52218','\\522',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'RO','/module/" . $folder . "/ro','" . $modulename . "','fa fa-list sub_menu_ico',3869)";
    } //end function

    public function sj($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(168,0,'Sales Journal','',0,'\\502','$parent',0,'0',0),
        (169,0,'Allow View Transaction SJ','SJ',0,'\\50201','\\502',0,'0',0),
        (170,0,'Allow Click Edit Button SJ','',0,'\\50202','\\502',0,'0',0),
        (171,0,'Allow Click New  Button SJ','',0,'\\50203','\\502',0,'0',0),
        (172,0,'Allow Click Save Button SJ','',0,'\\50204','\\502',0,'0',0),
        (174,0,'Allow Click Delete Button SJ','',0,'\\50206','\\502',0,'0',0),
        (175,0,'Allow Click Print Button SJ','',0,'\\50207','\\502',0,'0',0),
        (176,0,'Allow Click Lock Button SJ','',0,'\\50208','\\502',0,'0',0),
        (177,0,'Allow Click UnLock Button SJ','',0,'\\50209','\\502',0,'0',0),
        (178,0,'Allow Click Post Button SJ','',0,'\\50210','\\502',0,'0',0),
        (179,0,'Allow Click UnPost  Button SJ','',0,'\\50211','\\502',0,'0',0),
        (180,0,'Allow Change Amount  SJ','',0,'\\50213','\\502',0,'0',0),
        (181,0,'Allow Check Credit Limit SJ','',0,'\\50214','\\502',0,'0',0),
        (182,0,'Allow SJ Amount Auto-Compute on UOM Change','',0,'\\50215','\\502',0,'0',0),
        (183,0,'Allow View Transaction Accounting SJ','',0,'\\50216','\\502',0,'0',0),
        (802,1,'Allow Click Add Item SJ','',0,'\\50217','\\502',0,'0',0),
        (803,1,'Allow Click Edit Item SJ','',0,'\\50218','\\502',0,'0',0),
        (804,1,'Allow Click Delete Item SJ','',0,'\\50219','\\502',0,'0',0)";

        switch ($params['companyid']) {
            case 15: //nathina
            case 17: //unihome
            case 20: //proline
            case 28: //xcomp
            case 39: //CBBSI
                $qry = $qry . ",(2994,1,'Allow Click Release','',0,'\\50220','\\502',0,'0',0)";
                break;
            case 26: // bee
                $qry .= ", (3886,1,'Allow Cancel Button','',0,'\\50222','\\502',0,'0',0)";
                break;
            case 10: //afti
                $qry .= ", (3578,1,'Allow Click Make Payment','',0,'\\50221','\\502',0,'0',0)";
                break;
            case 19: //housegem
                $qry .=  ",(3959,1,'Allow View WH Info','',0,'\\50223','\\502',0,'0',0)";
                break;
            case 24: //goodfound
                $qry .=  ",(2509,1,'Allow View Fields for Gate 2 Users SJ','',0,'\\50224','\\502',0,'0',0)";
                $qry .=  ",(4219,1,'Allow Overwrite due SO','',0,'\\50225','\\502',0,'0',0)";
                break;
            case 43: //mighty
                $qry .= ", (4488,1,'Allow Access Tripping Tab','',0,'\\50220','\\502',0,'0',0)";
                $qry .= ", (4489,1,'Allow Access Dispatch Tab','',0,'\\50221','\\502',0,'0',0)";
                $qry .= ", (4494,1,'Allow Trip Approved','',0,'\\50222','\\502',0,'0',0)";
                break;
        }
        $this->insertattribute($params, $qry);

        $folder = 'sales';
        switch ($params['companyid']) {
            case 26: // bee healthy
                $folder = 'bee';
                break;
        }

        return "($sort,$p,'SJ','/module/" . $folder . "/sj','Sales Journal','fa fa-file-invoice sub_menu_ico',168)";
    } //end function

    public function dr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4246,0,'Delivery Receipt','',0,'\\523','$parent',0,'0',0),
        (4247,0,'Allow View Transaction DR','DR',0,'\\52301','\\523',0,'0',0),
        (4248,0,'Allow Click Edit Button DR','',0,'\\52302','\\523',0,'0',0),
        (4249,0,'Allow Click New  Button DR','',0,'\\52303','\\523',0,'0',0),
        (4250,0,'Allow Click Save Button DR','',0,'\\52304','\\523',0,'0',0),
        (4251,0,'Allow Click Delete Button DR','',0,'\\52305','\\523',0,'0',0),
        (4252,0,'Allow Click Print Button DR','',0,'\\52306','\\523',0,'0',0),
        (4253,0,'Allow Click Lock Button DR','',0,'\\52307','\\523',0,'0',0),
        (4254,0,'Allow Click UnLock Button DR','',0,'\\52308','\\523',0,'0',0),
        (4255,0,'Allow Click Post Button DR','',0,'\\52309','\\523',0,'0',0),
        (4256,0,'Allow Click UnPost  Button DR','',0,'\\52310','\\523',0,'0',0),
        (4257,0,'Allow Change Amount DR','',0,'\\52311','\\523',0,'0',0),
        (4258,0,'Allow Check Credit Limit DR','',0,'\\52312','\\523',0,'0',0),
        (4259,0,'Allow Amount Auto-Compute on UOM Change','',0,'\\52313','\\523',0,'0',0),
        (4260,0,'Allow View Transaction Accounting DR','',0,'\\52314','\\523',0,'0',0),
        (4261,1,'Allow Click Add Item DR','',0,'\\52315','\\523',0,'0',0),
        (4262,1,'Allow Click Edit Item DR','',0,'\\52316','\\523',0,'0',0),
        (4263,1,'Allow Click Delete Item DR','',0,'\\52317','\\523',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DR','/module/cbbsi/dr','Delivery Receipt','fa fa-file-invoice sub_menu_ico',4246)";
    } //end function


    public function sk($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4311,0,'Sales Invoice','',0,'\\526','$parent',0,'0',0),
        (4312,0,'Allow View Transaction SK','SK',0,'\\52601','\\526',0,'0',0),
        (4313,0,'Allow Click Edit Button SK','',0,'\\52602','\\526',0,'0',0),
        (4314,0,'Allow Click New  Button SK','',0,'\\52603','\\526',0,'0',0),
        (4315,0,'Allow Click Save Button SK','',0,'\\52604','\\526',0,'0',0),
        (4316,0,'Allow Click Delete Button SK','',0,'\\52605','\\526',0,'0',0),
        (4317,0,'Allow Click Print Button SK','',0,'\\52606','\\526',0,'0',0),
        (4318,0,'Allow Click Lock Button SK','',0,'\\52607','\\526',0,'0',0),
        (4319,0,'Allow Click UnLock Button SK','',0,'\\52608','\\526',0,'0',0),
        (4320,0,'Allow Click Post Button SK','',0,'\\52609','\\526',0,'0',0),
        (4321,0,'Allow Click UnPost  Button SK','',0,'\\52610','\\526',0,'0',0),
        (4322,0,'Allow Change Amount SK','',0,'\\52611','\\526',0,'0',0),
        (4323,0,'Allow Check Credit Limit SK','',0,'\\52612','\\526',0,'0',0),
        (4324,0,'Allow Amount Auto-Compute on UOM Change','',0,'\\52613','\\526',0,'0',0),
        (4325,0,'Allow View Transaction Accounting SK','',0,'\\52614','\\526',0,'0',0),
        (4326,1,'Allow Click Add Item SK','',0,'\\52615','\\526',0,'0',0),
        (4327,1,'Allow Click Edit Item SK','',0,'\\52616','\\526',0,'0',0),
        (4328,1,'Allow Click Delete Item SK','',0,'\\52617','\\526',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SK','/module/cbbsi/sk','Sales Invoice','fa fa-file-invoice sub_menu_ico',4311)";
    } //end function

    public function dn($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4286,0,'DR Return','',0,'\\524','$parent',0,'0',0),
        (4287,0,'Allow View Transaction DN','DN',0,'\\52401','\\524',0,'0',0),
        (4288,0,'Allow Click Edit Button DN','',0,'\\52402','\\524',0,'0',0),
        (4289,0,'Allow Click New  Button DN','',0,'\\52403','\\524',0,'0',0),
        (4290,0,'Allow Click Save  Button DN','',0,'\\52404','\\524',0,'0',0),
        (4291,0,'Allow Click Delete Button DN','',0,'\\52406','\\524',0,'0',0),
        (4292,0,'Allow Click Print  Button DN','',0,'\\52407','\\524',0,'0',0),
        (4293,0,'Allow Click Lock Button DN','',0,'\\52408','\\524',0,'0',0),
        (4294,0,'Allow Click UnLock Button DN','',0,'\\52409','\\524',0,'0',0),
        (4295,0,'Allow Click Post Button DN','',0,'\\52410','\\524',0,'0',0),
        (4296,0,'Allow Click UnPost  Button DN','',0,'\\52411','\\524',0,'0',0),
        (4297,0,'Allow View Transaction Accounting DN','',0,'\\52412','\\524',0,'0',0),
        (4298,0,'Allow Change Amount DN','',0,'\\52413','\\524',0,'0',0),
        (4299,1,'Allow Click Add Item DN','',0,'\\52414','\\524',0,'0',0),
        (4300,1,'Allow Click Edit Item DN','',0,'\\52415','\\524',0,'0',0),
        (4301,1,'Allow Click Delete Item DN','',0,'\\52416','\\524',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'DN','/module/cbbsi/dn','DR Return','fa fa-sync sub_menu_ico',4286)";
    } //end function


    public function di($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4329,0,'Discrepancy Notice','',0,'\\527','$parent',0,'0',0),
        (4330,0,'Allow View Transaction DI','DI',0,'\\52618','\\527',0,'0',0),
        (4331,0,'Allow Click Edit Button DI','',0,'\\52619','\\527',0,'0',0),
        (4332,0,'Allow Click New  Button DI','',0,'\\52620','\\527',0,'0',0),
        (4333,0,'Allow Click Save  Button DI','',0,'\\52621','\\527',0,'0',0),
        (4334,0,'Allow Click Delete Button DI','',0,'\\52622','\\527',0,'0',0),
        (4335,0,'Allow Click Print  Button DI','',0,'\\52623','\\527',0,'0',0),
        (4336,0,'Allow Click Lock Button DI','',0,'\\52624','\\527',0,'0',0),
        (4337,0,'Allow Click UnLock Button DI','',0,'\\52625','\\527',0,'0',0),
        (4338,0,'Allow Change Amount DI','',0,'\\52626','\\527',0,'0',0),
        (4339,0,'Allow Click Post Button DI','',0,'\\52627','\\527',0,'0',0),
        (4340,0,'Allow Click UnPost Button DI','',0,'\\52628','\\527',0,'0',0),
        (4341,0,'Allow Click Add Item DI','',0,'\\52629','\\527',0,'0',0),
        (4342,0,'Allow Click Edit Item DI','',0,'\\52630','\\527',0,'0',0),
        (4343,0,'Allow Click Delete Item DI','',0,'\\52631','\\527',0,'0',0),
        (4344,0,'Allow View Amount DI','',0,'\\52632','\\527',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DI','/module/cbbsi/di','Discrepancy Notice','fa fa-boxes sub_menu_ico',4329)";
    } //end function


    public function rt($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4345,0,'Temporary RR','',0,'\\528','$parent',0,'0',0),
        (4346,0,'Allow View Transaction RT','RT',0,'\\52633','\\528',0,'0',0),
        (4347,0,'Allow Click Edit Button RT','',0,'\\52634','\\528',0,'0',0),
        (4348,0,'Allow Click New  Button RT','',0,'\\52635','\\528',0,'0',0),
        (4349,0,'Allow Click Save  Button RT','',0,'\\52636','\\528',0,'0',0),
        (4350,0,'Allow Click Delete Button RT','',0,'\\52637','\\528',0,'0',0),
        (4351,0,'Allow Click Print  Button RT','',0,'\\52638','\\528',0,'0',0),
        (4352,0,'Allow Click Lock Button RT','',0,'\\52639','\\528',0,'0',0),
        (4353,0,'Allow Click UnLock Button RT','',0,'\\52640','\\528',0,'0',0),
        (4354,0,'Allow Change Amount RT','',0,'\\52641','\\528',0,'0',0),
        (4355,0,'Allow Click Post Button RT','',0,'\\52642','\\528',0,'0',0),
        (4356,0,'Allow Click UnPost Button RT','',0,'\\52643','\\528',0,'0',0),
        (4357,0,'Allow Click Add Item RT','',0,'\\52644','\\528',0,'0',0),
        (4358,0,'Allow Click Edit Item RT','',0,'\\52645','\\528',0,'0',0),
        (4359,0,'Allow Click Delete Item RT','',0,'\\52646','\\528',0,'0',0),
        (4360,0,'Allow View Amount RT','',0,'\\52647','\\528',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RT','/module/cbbsi/rt','Temporary RR','fa fa-tasks sub_menu_ico',4345)";
    } //end function

    public function ck($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = " (4265,0,'Request For Sales Return','',0,'\\525','$parent',0,'0',0),
        (4266,0,'Allow View Transaction CK','CK',0,'\\52501','\\525',0,'0',0),
        (4267,0,'Allow Click Edit Button CK','',0,'\\52502','\\525',0,'0',0),
        (4268,0,'Allow Click New  Button CK','',0,'\\52503','\\525',0,'0',0),
        (4269,0,'Allow Click Save Button CK','',0,'\\52504','\\525',0,'0',0),
        (4270,0,'Allow Click Delete Button CK','',0,'\\52506','\\525',0,'0',0),
        (4271,0,'Allow Click Print Button CK','',0,'\\52507','\\525',0,'0',0),
        (4272,0,'Allow Click Lock Button CK','',0,'\\52508','\\525',0,'0',0),
        (4273,0,'Allow Click UnLock Button CK','',0,'\\52509','\\525',0,'0',0),
        (4274,0,'Allow Change Amount  CK','',0,'\\52510','\\525',0,'0',0),
        (4275,0,'Allow Check Credit Limit CK','',0,'\\52511','\\525',0,'0',0),
        (4276,0,'Allow Click Post Button CK','',0,'\\52512','\\525',0,'0',0),
        (4277,0,'Allow Click UnPost  Button CK','',0,'\\52513','\\525',0,'0',0),
        (4278,1,'Allow Click Add Item CK','',0,'\\52514','\\525',0,'0',0),
        (4279,1,'Allow Click Edit Item CK','',0,'\\52515','\\525',0,'0',0),
        (4280,1,'Allow Click Delete Item CK','',0,'\\52516','\\525',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'CK','/module/cbbsi/ck','Request For Sales Return','fa fa-clipboard-list sub_menu_ico',4265)";
    } //end function



    public function dp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = " (4370,0,'Dispatch Schedule','',0,'\\529','$parent',0,'0',0),
        (4371,0,'Allow View Transaction DP','DP',0,'\\52901','\\529',0,'0',0),
        (4372,0,'Allow Click Edit Button DP','',0,'\\52902','\\529',0,'0',0),
        (4373,0,'Allow Click New  Button DP','',0,'\\52903','\\529',0,'0',0),
        (4374,0,'Allow Click Save Button DP','',0,'\\52904','\\529',0,'0',0),
        (4375,0,'Allow Click Delete Button DP','',0,'\\52906','\\529',0,'0',0),
        (4376,0,'Allow Click Print Button DP','',0,'\\52907','\\529',0,'0',0),
        (4377,0,'Allow Click Lock Button DP','',0,'\\52908','\\529',0,'0',0),
        (4378,0,'Allow Click UnLock Button DP','',0,'\\52909','\\529',0,'0',0),
        (4379,0,'Allow Change Amount  DP','',0,'\\52910','\\529',0,'0',0),
        (4380,0,'Allow Click Credit Limit DP','',0,'\\52911','\\529',0,'0',0),
        (4381,0,'Allow Click Post Button DP','',0,'\\52912','\\529',0,'0',0),
        (4382,0,'Allow Click UnPost  Button DP','',0,'\\52913','\\529',0,'0',0),
        (4383,1,'Allow Click Add Item DP','',0,'\\52914','\\529',0,'0',0),
        (4384,1,'Allow Click Edit Item DP','',0,'\\52915','\\529',0,'0',0),
        (4385,1,'Allow Click Delete Item DP','',0,'\\52916','\\529',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'DP','/module/cbbsi/dp','Dispatch Schedule','fa fa-clipboard-list sub_menu_ico',4370)";
    } //end function

    public function bo($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3774,0,'Bad Order','',0,'\\519','$parent',0,'0',0),
        (3775,0,'Allow View Transaction BO','BO',0,'\\51901','\\519',0,'0',0),
        (3776,0,'Allow Click Edit Button BO','',0,'\\51902','\\519',0,'0',0),
        (3777,0,'Allow Click New  Button BO','',0,'\\51903','\\519',0,'0',0),
        (3778,0,'Allow Click Save Button BO','',0,'\\51904','\\519',0,'0',0),
        (3779,0,'Allow Click Delete Button BO','',0,'\\51906','\\519',0,'0',0),
        (3780,0,'Allow Click Print Button BO','',0,'\\51907','\\519',0,'0',0),
        (3781,0,'Allow Click Lock Button BO','',0,'\\51908','\\519',0,'0',0),
        (3782,0,'Allow Click UnLock Button BO','',0,'\\51909','\\519',0,'0',0),
        (3783,0,'Allow Click Post Button BO','',0,'\\51910','\\519',0,'0',0),
        (3784,0,'Allow Click UnPost  Button BO','',0,'\\51911','\\519',0,'0',0),
        (3785,0,'Allow Change Amount  BO','',0,'\\51913','\\519',0,'0',0),
        (3786,0,'Allow BO Amount Auto-Compute on UOM Change','',0,'\\51915','\\519',0,'0',0),
        (3787,0,'Allow View Transaction Accounting BO','',0,'\\51916','\\519',0,'0',0),
        (3788,1,'Allow Click Add Item BO','',0,'\\51917','\\519',0,'0',0),
        (3789,1,'Allow Click Edit Item BO','',0,'\\51918','\\519',0,'0',0),
        (3790,1,'Allow Click Delete Item BO','',0,'\\51919','\\519',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'BO','/module/sales/bo','Bad Order','fa fa-file-invoice sub_menu_ico',3774)";
    } //end function

    public function cm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(189,0,'Sales Return','',0,'\\503','$parent',0,'0',0),
        (190,0,'Allow View Transaction SR','CM',0,'\\50301','\\503',0,'0',0),
        (191,0,'Allow Click Edit Button SR','',0,'\\50302','\\503',0,'0',0),
        (192,0,'Allow Click New  Button SR','',0,'\\50303','\\503',0,'0',0),
        (193,0,'Allow Click Save  Button SR','',0,'\\50304','\\503',0,'0',0),
        (195,0,'Allow Click Delete Button SR','',0,'\\50306','\\503',0,'0',0),
        (196,0,'Allow Click Print  Button SR','',0,'\\50307','\\503',0,'0',0),
        (197,0,'Allow Click Lock Button SR','',0,'\\50308','\\503',0,'0',0),
        (198,0,'Allow Click UnLock Button SR','',0,'\\50309','\\503',0,'0',0),
        (199,0,'Allow Click Post Button SR','',0,'\\50310','\\503',0,'0',0),
        (200,0,'Allow Click UnPost  Button SR','',0,'\\50311','\\503',0,'0',0),
        (201,0,'Allow View Transaction Accounting SR','',0,'\\50312','\\503',0,'0',0),
        (202,0,'Allow Change Amount SR','',0,'\\50313','\\503',0,'0',0),
        (817,1,'Allow Click Add Item SR','',0,'\\50314','\\503',0,'0',0),
        (818,1,'Allow Click Edit Item SR','',0,'\\50315','\\503',0,'0',0),
        (819,1,'Allow Click Delete Item SR','',0,'\\50316','\\503',0,'0',0)";

        $folder = 'sales';
        switch ($params['companyid']) {
            case 26: // bee healthy
                $folder = 'bee';
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'CM','/module/" . $folder . "/cm','Sales Return','fa fa-sync sub_menu_ico',189)";
    } //end function

    public function qt($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2132,0,'Quotation','',0,'\\504','$parent',0,'0',0) ,";
        if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti, afti usd
            $qry = "(2132,0,'Service Quotation','',0,'\\504','$parent',0,'0',0) ,";
        }
        $qry = $qry . "(2133,0,'Allow View Transaction QT','QT',0,'\\50401','\\504',0,'0',0) ,
        (2134,0,'Allow Click Edit Button QT','',0,'\\50402','\\504',0,'0',0) ,
        (2135,0,'Allow Click New  Button QT','',0,'\\50403','\\504',0,'0',0) ,
        (2136,0,'Allow Click Save Button QT','',0,'\\50404','\\504',0,'0',0) ,
        (2138,0,'Allow Click Delete Button QT','',0,'\\50406','\\504',0,'0',0) ,
        (2139,0,'Allow Click Print Button QT','',0,'\\50407','\\504',0,'0',0) ,
        (2140,0,'Allow Click Lock Button QT','',0,'\\50408','\\504',0,'0',0) ,
        (2141,0,'Allow Click UnLock Button QT','',0,'\\50409','\\504',0,'0',0) ,
        (2142,0,'Allow Change Amount QT','',0,'\\50410','\\504',0,'0',0) ,
        (2143,0,'Allow Click Post Button QT','',0,'\\50412','\\504',0,'0',0) ,
        (2144,0,'Allow Click UnPost  Button QT','',0,'\\50413','\\504',0,'0',0) ,
        (2145,1,'Allow Click Add Item QT','',0,'\\50414','\\504',0,'0',0) ,
        (2146,1,'Allow Click Edit Item QT','',0,'\\50415','\\504',0,'0',0) ,
        (2147,1,'Allow Click Delete Item QT','',0,'\\50416','\\504',0,'0',0)";
        $this->insertattribute($params, $qry);
        switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
                return "($sort,$p,'QT','/module/sales/qt','Service Quotation','fa fa-receipt sub_menu_ico',2132)";
                break;

            case 20: //proline
                return "($sort,$p,'QT','/module/proline/qt','Quotation','fa fa-receipt sub_menu_ico',2132)";
                break;

            case 39: //cbbsi
                return "($sort,$p,'QT','/module/cbbsi/qt','Quotation','fa fa-receipt sub_menu_ico',2132)";
                break;

            default:
                return "($sort,$p,'QT','/module/sales/qt','Quotation','fa fa-receipt sub_menu_ico',2132)";
                break;
        }
    } //end function

    public function qs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2452,0,'Quotation','',0,'\\505','$parent',0,'0',0) ,
        (2453,0,'Allow View Transaction QS','QS',0,'\\50501','\\505',0,'0',0) ,
        (2454,0,'Allow Click Edit Button QS','',0,'\\50502','\\505',0,'0',0) ,
        (2455,0,'Allow Click New  Button QS','',0,'\\50503','\\505',0,'0',0) ,
        (2456,0,'Allow Click Save Button QS','',0,'\\50504','\\505',0,'0',0) ,
        (2458,0,'Allow Click Delete Button QS','',0,'\\50506','\\505',0,'0',0) ,
        (2459,0,'Allow Click Print Button QS','',0,'\\50507','\\505',0,'0',0) ,
        (2460,0,'Allow Click Lock Button QS','',0,'\\50508','\\505',0,'0',0) ,
        (2461,0,'Allow Click UnLock Button QS','',0,'\\50509','\\505',0,'0',0) ,
        (2462,0,'Allow Change Amount QS','',0,'\\50510','\\505',0,'0',0) ,
        (2463,0,'Allow Click Post Button QS','',0,'\\50512','\\505',0,'0',0) ,
        (2464,0,'Allow Click UnPost  Button QS','',0,'\\50513','\\505',0,'0',0) ,
        (2465,1,'Allow Click Add Item QS','',0,'\\50514','\\505',0,'0',0) ,
        (2466,1,'Allow Click Edit Item QS','',0,'\\50515','\\505',0,'0',0) ,
        (2467,1,'Allow Click Delete Item QS','',0,'\\50516','\\505',0,'0',0),
        (2863,1,'Allow View Terms, Taxes and Charges Tab','',0,'\\50517','\\505',0,'0',0),
        (3688,1,'Allow Edit VAT Rate on Terms, Taxes and Charges Tab','',0,'\\50518','\\505',0,'0',0),
        (4162,1,'Allow View all Terms','',0,'\\50519','\\505',0,'0',0),
        (4163,1,'Allow override PO Date','',0,'\\50520','\\505',0,'0',0),
        (4050,1,'Allow View Proforma Invoice Tab','',0,'\\50521','\\505',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'QS','/module/sales/qs','Quotation','fa fa-receipt sub_menu_ico',2452)";
    } //end function      

    public function sq($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2468,0,'Sales Order','',0,'\\506','$parent',0,'0',0) ,
        (2469,0,'Allow View Transaction SO','SQ',0,'\\50601','\\506',0,'0',0) ,
        (2470,0,'Allow Click Edit Button SO','',0,'\\50602','\\506',0,'0',0) ,
        (2471,0,'Allow Click New  Button SO','',0,'\\50603','\\506',0,'0',0) ,
        (2472,0,'Allow Click Save Button SO','',0,'\\50604','\\506',0,'0',0) ,
        (2474,0,'Allow Click Delete Button SO','',0,'\\50606','\\506',0,'0',0) ,
        (2475,0,'Allow Click Print Button SO','',0,'\\50607','\\506',0,'0',0) ,
        (2476,0,'Allow Click Lock Button SO','',0,'\\50608','\\506',0,'0',0) ,
        (2477,0,'Allow Click UnLock Button SO','',0,'\\50609','\\506',0,'0',0) ,
        (2478,0,'Allow Click Post Button SO','',0,'\\50612','\\506',0,'0',0) ,
        (2479,0,'Allow Click UnPost  Button SO','',0,'\\50613','\\506',0,'0',0) ,
        (2872,0,'Allow Click Make PO Button','',0,'\\50614','\\506',0,'0',0) , 
        (2874,0,'Allow Click Delivery Date','',0,'\\50615','\\506',0,'0',0), 
        (3718,0,'Allow Click Delete Items SO','',0,'\\50616','\\506',0,'0',0) ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SQ','/module/sales/sq','Sales Order','fa fa-file sub_menu_ico',2468)";
    } //end function

    public function vt($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2763,0,'Void Sales Order','',0,'\\509','$parent',0,'0',0),
        (2764,0,'Allow View Transaction VT','VT',0,'\\50901','\\509',0,'0',0),
        (2765,0,'Allow Click Edit Button VT','',0,'\\50902','\\509',0,'0',0),
        (2766,0,'Allow Click New Button VT','',0,'\\50903','\\509',0,'0',0),
        (2767,0,'Allow Click Save Button VT','',0,'\\50904','\\509',0,'0',0),
        (2769,0,'Allow Click Delete Button VT','',0,'\\50906','\\509',0,'0',0),
        (2770,0,'Allow Click Print Button VT','',0,'\\50907','\\509',0,'0',0),
        (2771,0,'Allow Click Lock Button VT','',0,'\\50908','\\509',0,'0',0),
        (2772,0,'Allow Click UnLock Button VT','',0,'\\50909','\\509',0,'0',0),
        (2773,0,'Allow Change Amount VT','',0,'\\50910','\\509',0,'0',0),
        (2774,0,'Allow Check Credit Limit VT','',0,'\\50911','\\509',0,'0',0),
        (2775,0,'Allow Click Post Button VT','',0,'\\50912','\\509',0,'0',0),
        (2776,0,'Allow Click UnPost  Button VT','',0,'\\50913','\\509',0,'0',0),
        (2777,1,'Allow Click Add Item VT','',0,'\\50914','\\509',0,'0',0),
        (2778,1,'Allow Click Edit Item VT','',0,'\\50915','\\509',0,'0',0),
        (2779,1,'Allow Click Delete Item VT','',0,'\\50916','\\509',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'VT','/module/sales/vt','Void Sales Order','fa fa-clipboard-list sub_menu_ico',2763)";
    } //end function

    public function vs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2780,0,'Void Service Sales Order','',0,'\\510','$parent',0,'0',0),
        (2781,0,'Allow View Transaction VS','VS',0,'\\51001','\\510',0,'0',0),
        (2782,0,'Allow Click Edit Button VS','',0,'\\51002','\\510',0,'0',0),
        (2783,0,'Allow Click New Button VS','',0,'\\51003','\\510',0,'0',0),
        (2784,0,'Allow Click Save Button VS','',0,'\\51004','\\510',0,'0',0),
        (2786,0,'Allow Click Delete Button VS','',0,'\\51006','\\510',0,'0',0),
        (2787,0,'Allow Click Print Button VS','',0,'\\51007','\\510',0,'0',0),
        (2788,0,'Allow Click Lock Button VS','',0,'\\51008','\\510',0,'0',0),
        (2789,0,'Allow Click UnLock Button VS','',0,'\\51009','\\510',0,'0',0),
        (2790,0,'Allow Change Amount VS','',0,'\\51010','\\510',0,'0',0),
        (2791,0,'Allow Check Credit Limit VS','',0,'\\51011','\\510',0,'0',0),
        (2792,0,'Allow Click Post Button VS','',0,'\\51012','\\510',0,'0',0),
        (2793,0,'Allow Click UnPost  Button VS','',0,'\\51013','\\510',0,'0',0),
        (2794,1,'Allow Click Add Item VS','',0,'\\51014','\\510',0,'0',0),
        (2795,1,'Allow Click Edit Item VS','',0,'\\51015','\\510',0,'0',0),
        (2796,1,'Allow Click Delete Item VS','',0,'\\51016','\\510',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'VS','/module/sales/vs','Void Service Sales Order','fa fa-clipboard-list sub_menu_ico',2780)";
    } //end function

    public function su($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2807,0,'Stock Issuance','',0,'\\511','$parent',0,'0',0),
        (2808,0,'Allow View Transaction SU','SU',0,'\\51101','\\511',0,'0',0),
        (2809,0,'Allow Click Edit Button SU','',0,'\\51102','\\511',0,'0',0),
        (2810,0,'Allow Click New Button SU','',0,'\\51103','\\511',0,'0',0),
        (2811,0,'Allow Click Save Button SU','',0,'\\51104','\\511',0,'0',0),
        (2813,0,'Allow Click Delete Button SU','',0,'\\51106','\\511',0,'0',0),
        (2814,0,'Allow Click Print Button SU','',0,'\\51107','\\511',0,'0',0),
        (2815,0,'Allow Click Lock Button SU','',0,'\\51108','\\511',0,'0',0),
        (2816,0,'Allow Click UnLock Button SU','',0,'\\51109','\\511',0,'0',0),
        (2817,0,'Allow Change Amount SU','',0,'\\51110','\\511',0,'0',0),
        (2818,0,'Allow Check Credit Limit SU','',0,'\\51111','\\511',0,'0',0),
        (2819,0,'Allow Click Post Button SU','',0,'\\51112','\\511',0,'0',0),
        (2820,0,'Allow Click UnPost  Button SU','',0,'\\51113','\\511',0,'0',0),
        (2821,1,'Allow Click Add Item SU','',0,'\\51114','\\511',0,'0',0),
        (2822,1,'Allow Click Edit Item SU','',0,'\\51115','\\511',0,'0',0),
        (2823,1,'Allow Click Delete Item SU','',0,'\\51116','\\511',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SU','/module/sales/su','Stock Issuance','fa fa-clipboard-list sub_menu_ico',2807)";
    } //end function

    public function rf($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2824,0,'Request for Replacement/Return','',0,'\\512','$parent',0,'0',0),
        (2825,0,'Allow View Transaction RF','RF',0,'\\51201','\\512',0,'0',0),
        (2826,0,'Allow Click Edit Button RF','',0,'\\51202','\\512',0,'0',0),
        (2827,0,'Allow Click New Button RF','',0,'\\51203','\\512',0,'0',0),
        (2828,0,'Allow Click Save Button RF','',0,'\\51204','\\512',0,'0',0),
        (2830,0,'Allow Click Delete Button RF','',0,'\\51206','\\512',0,'0',0),
        (2831,0,'Allow Click Print Button RF','',0,'\\51207','\\512',0,'0',0),
        (2832,0,'Allow Click Lock Button RF','',0,'\\51208','\\512',0,'0',0),
        (2833,0,'Allow Click UnLock Button RF','',0,'\\51209','\\512',0,'0',0),
        (2834,0,'Allow Change Amount RF','',0,'\\51210','\\512',0,'0',0),
        (2835,0,'Allow Check Credit Limit RF','',0,'\\51211','\\512',0,'0',0),
        (2836,0,'Allow Click Post Button RF','',0,'\\51212','\\512',0,'0',0),
        (2837,0,'Allow Click UnPost  Button RF','',0,'\\51213','\\512',0,'0',0),
        (2838,1,'Allow Click Add Item RF','',0,'\\51214','\\512',0,'0',0),
        (2839,1,'Allow Click Edit Item RF','',0,'\\51215','\\512',0,'0',0),
        (2840,1,'Allow Click Delete Item RF','',0,'\\51216','\\512',0,'0',0),
        (2841,1,'Allow View Return to Supplier Tab RF','',0,'\\51217','\\512',0,'0',0),
        (2842,1,'Allow View Return to Customer Tab RF','',0,'\\51218','\\512',0,'0',0),
        (3586,1,'Allow Edit RFN No.','',0,'\\51219','\\512',0,'0',0),
        (3587,1,'Allow View RFR Cost','',0,'\\51220','\\512',0,'0',0)
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RF','/module/sales/rf','Request for Replacement/Return','fa fa-undo-alt sub_menu_ico',2824)";
    } //end function

    public function ao($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2659,0,'Service Sales Order','',0,'\\507','$parent',0,'0',0) ,
        (2660,0,'Allow View Transaction SO','AO',0,'\\50701','\\507',0,'0',0) ,
        (2661,0,'Allow Click Edit Button SO','',0,'\\50702','\\507',0,'0',0) ,
        (2662,0,'Allow Click New  Button SO','',0,'\\50703','\\507',0,'0',0) ,
        (2663,0,'Allow Click Save Button SO','',0,'\\50704','\\507',0,'0',0) ,
        (2665,0,'Allow Click Delete Button SO','',0,'\\50706','\\507',0,'0',0) ,
        (2666,0,'Allow Click Print Button SO','',0,'\\50707','\\507',0,'0',0) ,
        (2667,0,'Allow Click Lock Button SO','',0,'\\50708','\\507',0,'0',0) ,
        (2668,0,'Allow Click UnLock Button SO','',0,'\\50709','\\507',0,'0',0) ,
        (2669,0,'Allow Click Post Button SO','',0,'\\50712','\\507',0,'0',0) ,
        (2670,0,'Allow Click UnPost  Button SO','',0,'\\50713','\\507',0,'0',0),
        (3720,0,'Allow Click Delete Items SO','',0,'\\50714','\\507',0,'0',0) ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AO','/module/sales/ao','Service Sales Order','fa fa-file sub_menu_ico',2659)";
    } //end function     

    public function ai($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2706,0,'Service Invoice','',0,'\\508','$parent',0,'0',0),
        (2707,0,'Allow View Transaction SI','AI',0,'\\50801','\\508',0,'0',0),
        (2708,0,'Allow Click Edit Button SI','',0,'\\50802','\\508',0,'0',0),
        (2709,0,'Allow Click New  Button SI','',0,'\\50803','\\508',0,'0',0),
        (2710,0,'Allow Click Save Button SI','',0,'\\50804','\\508',0,'0',0),
        (2712,0,'Allow Click Delete Button SI','',0,'\\50806','\\508',0,'0',0),
        (2713,0,'Allow Click Print Button SI','',0,'\\50807','\\508',0,'0',0),
        (2714,0,'Allow Click Lock Button SI','',0,'\\50808','\\508',0,'0',0),
        (2715,0,'Allow Click UnLock Button SI','',0,'\\50809','\\508',0,'0',0),
        (2716,0,'Allow Click Post Button SI','',0,'\\50810','\\508',0,'0',0),
        (2717,0,'Allow Click UnPost  Button SI','',0,'\\50811','\\508',0,'0',0),
        (2718,0,'Allow Change Amount  SI','',0,'\\50813','\\508',0,'0',0),
        (2719,0,'Allow Check Credit Limit SI','',0,'\\50814','\\508',0,'0',0),
        (2720,0,'Allow SI Amount Auto-Compute on UOM Change','',0,'\\50815','\\508',0,'0',0),
        (2721,0,'Allow View Transaction Accounting SI','',0,'\\50816','\\508',0,'0',0),
        (2722,1,'Allow Click Add Item SI','',0,'\\50817','\\508',0,'0',0),
        (2723,1,'Allow Click Edit Item SI','',0,'\\50818','\\508',0,'0',0),
        (2724,1,'Allow Click Delete Item SI','',0,'\\50819','\\508',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AI','/module/sales/ai','Service Invoice','fa fa-file-invoice sub_menu_ico',2706)";
    } //end function

    public function comm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2991,0,'Commission','',0,'\\50820','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'COMMISSION','/headtable/sales/comm','Commission','fa fa-calculator sub_menu_ico',2991)";
    }
    public function parentwarehousing($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1858,0,'WAREHOUSING','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'WAREHOUSING',$sort,'fa fa-warehouse',',warehousing')";
    } //end function

    public function pallet($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1875,1,'Pallet Masterfile','',0,'\\2401','$parent',0,0,0),
               (1876,0,'Allow View Pallet Masterfile','',0,'\\240101','\\2401',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrypallet','/tableentries/warehousingentry/entrypallet','Pallet Setup','fa fa-pallet sub_menu_ico',1875)";
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

        $qry = "(1877,1,'" . $label . "','',0,'\\2402','$parent',0,0,0),
        (1878,0,'Allow View $label','',0,'\\240201','\\2402',0,'0',0),
        (1879,0,'Allow Click Edit Button $label','',0,'\\240202','\\2402',0,'0',0),
        (1880,0,'Allow Click New Button $label','',0,'\\240203','\\2402',0,'0',0),
        (1881,0,'Allow Click Save Button $label','',0,'\\240204','\\2402',0,'0',0),
        (1882,0,'Allow Click Change $label Code','',0,'\\240205','\\2402',0,'0',0),
        (1883,0,'Allow Click Delete Button $label','',0,'\\240206','\\2402',0,'0',0),
        (1884,0,'Allow Click Print Button $label','',0,'\\240207','\\2402',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'forwarder','/ledgergrid/warehousing/forwarder','" . $label . "','fa fa-truck sub_menu_ico',1877)";
    } //end function

    public function partrequesttype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2130,1,'Part Request Type','',0,'\\24022','$parent',0,0,0),
        (2131,0,'Allow View Part Request Type','',0,'\\2402201','\\24022',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrypartrequest','/tableentries/warehousingentry/entrypartrequest','Part Request Type','fa fa-tasks sub_menu_ico',2130)";
    } //end function


    public function checkerlocation($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2026,1,'Deposit Location','',0,'\\24012','$parent',0,0,0),
        (2027,0,'Allow View Deposit Location','',0,'\\2401201','\\24012',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrycheckerlocation','/tableentries/warehousingentry/entrycheckerlocation','Deposit Location','fa fa-users sub_menu_ico',2026)";
    } //end function


    public function pi($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2032,0,'Product Inquiry','',0,'\\24015','$parent',0,'0',0),
        (2033,0,'Allow View Product Inquiry','PI',0,'\\2401501','\\24015',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PI','/ledgergrid/warehousing/pi','Product Inquiry','fa fa-shopping-cart  sub_menu_ico',2032)";
    } //end function

    public function pl($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1859,0,'Packing List','',0,'\\2403','$parent',0,'0',0),
        (1860,0,'Allow View Transaction PL','PL',0,'\\240301','\\2403',0,'0',0),
        (1861,0,'Allow Click Edit Button PL','',0,'\\240302','\\2403',0,'0',0),
        (1862,0,'Allow Click New Button PL','',0,'\\240303','\\2403',0,'0',0),
        (1863,0,'Allow Click Save Button PL','',0,'\\240304','\\2403',0,'0',0),
        (1865,0,'Allow Click Delete Button PL','',0,'\\240306','\\2403',0,'0',0),
        (1866,0,'Allow Click Print Button PL','',0,'\\240307','\\2403',0,'0',0),
        (1867,0,'Allow Click Lock Button PL','',0,'\\240308','\\2403',0,'0',0),
        (1868,0,'Allow Click UnLock Button PL','',0,'\\240309','\\2403',0,'0',0),
        (1870,0,'Allow Click Post Button PL','',0,'\\240312','\\2403',0,'0',0),
        (1871,0,'Allow Click UnPost  Button PL','',0,'\\240313','\\2403',0,'0',0),
        (1872,1,'Allow Click Add Item PL','',0,'\\240314','\\2403',0,'0',0),
        (1873,1,'Allow Click Edit Item PL','',0,'\\240315','\\2403',0,'0',0),
        (1874,1,'Allow Click Delete Item PL','',0,'\\240316','\\2403',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PL','/module/warehousing/pl','Packing List','fa fa-tasks sub_menu_ico',1859)";
    } //end function

    public function rp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1886,0,'Packing List Receiving','',0,'\\2404','$parent',0,'0',0),
        (1887,0,'Allow View Transaction PL Receiving','RP',0,'\\240401','\\2404',0,'0',0),
        (1888,0,'Allow Click Edit Button PL Receiving','',0,'\\240402','\\2404',0,'0',0),
        (1889,0,'Allow Click New Button PL Receiving','',0,'\\240403','\\2404',0,'0',0),
        (1890,0,'Allow Click Save Button PL Receiving','',0,'\\240404','\\2404',0,'0',0),
        (1892,0,'Allow Click Delete Button PL Receiving','',0,'\\240406','\\2404',0,'0',0),
        (1893,0,'Allow Click Print Button PL Receiving','',0,'\\240407','\\2404',0,'0',0),
        (1894,0,'Allow Click Lock Button PL Receiving','',0,'\\240408','\\2404',0,'0',0),
        (1895,0,'Allow Click UnLock Button PL Receiving','',0,'\\240409','\\2404',0,'0',0),
        (1896,0,'Allow Click Post Button PL Receiving','',0,'\\240412','\\2404',0,'0',0),
        (1897,0,'Allow Click UnPost  Button PL Receiving','',0,'\\240413','\\2404',0,'0',0),
        (1898,1,'Allow Click Add Item PL Receiving','',0,'\\240414','\\2404',0,'0',0),
        (1899,1,'Allow Click Edit Item PL Receiving','',0,'\\240415','\\2404',0,'0',0),
        (1900,1,'Allow Click Delete Item PL Receiving','',0,'\\240416','\\2404',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RP','/module/warehousing/rp','Packing List Receiving','fa fa-boxes sub_menu_ico',1886)";
    } //end function

    public function forklift($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2090,0,'Forklift','',0,'\\24018','$parent',0,'0',0),
        (2091,0,'Allow View Forklift','forklift',0,'\\2401801','\\24018',0,'0',0),
        (2092,0,'Allow Edit Forklift','',0,'\\2401802','\\24018',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'forklift','/ledgergrid/warehousing/forklift','Forklift','fa fa-dolly sub_menu_ico',2090)";
    } //end function


    public function warehouseman($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2127,0,'Warehouse Man','',0,'\\24021','$parent',0,'0',0),
        (2128,0,'Allow View Warehouse Man','whman',0,'\\2402101','\\24021',0,'0',0),
        (2129,0,'Allow Edit Warehouse Man','',0,'\\2402102','\\24021',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehouseman','/ledgergrid/warehousing/warehouseman','Warehouse Man','fa fa-warehouse sub_menu_ico',2127)";
    } //end function


    public function sa($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1901,0,'Sales Order Dealer','',0,'\\2405','$parent',0,'0',0),
        (1902,0,'Allow View Transaction SO','SO',0,'\\240501','\\2405',0,'0',0),
        (1903,0,'Allow Click Edit Button SO','',0,'\\240502','\\2405',0,'0',0),
        (1904,0,'Allow Click New  Button SO','',0,'\\240503','\\2405',0,'0',0),
        (1905,0,'Allow Click Save Button SO','',0,'\\240504','\\2405',0,'0',0),
        (1907,0,'Allow Click Delete Button SO','',0,'\\240506','\\2405',0,'0',0),
        (1908,0,'Allow Click Print Button SO','',0,'\\240507','\\2405',0,'0',0),
        (1909,0,'Allow Click Lock Button SO','',0,'\\240508','\\2405',0,'0',0),
        (1910,0,'Allow Click UnLock Button SO','',0,'\\240509','\\2405',0,'0',0),
        (1911,0,'Allow Change Amount  SO','',0,'\\2405010','\\2405',0,'0',0),
        (1912,0,'Allow Check Credit Limit SO','',0,'\\2405011','\\2405',0,'0',0),
        (1913,0,'Allow Change of Discount','',0,'\\2405012','\\2405',0,'0',0),
        (1914,0,'Allow Click Post Button SO','',0,'\\2405013','\\2405',0,'0',0),
        (1915,0,'Allow Click UnPost  Button SO','',0,'\\2405014','\\2405',0,'0',0),
        (1916,1,'Allow Click Add Item SO','',0,'\\2405015','\\2405',0,'0',0),
        (1917,1,'Allow Click Edit Item SO','',0,'\\2405016','\\2405',0,'0',0),
        (1918,1,'Allow Click Delete Item SO','',0,'\\2405017','\\2405',0,'0',0),
        (2216,1,'Admin','',0,'\\2405018','\\2405',0,'0',0),
        (3597,1,'Allow Void Button','',0,'\\2405019','\\2405',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SA','/module/warehousing/sa','Sales Order Dealer','fa fa-tasks sub_menu_ico',1901)";
    } //end function

    public function sd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1919,0,'Sales Journal Dealer','',0,'\\2406','$parent',0,'0',0),
        (1920,0,'Allow View Transaction SJ','SJ',0,'\\240601','\\2406',0,'0',0),
        (1921,0,'Allow Click Edit Button SJ','',0,'\\240602','\\2406',0,'0',0),
        (1922,0,'Allow Click New  Button SJ','',0,'\\240603','\\2406',0,'0',0),
        (1923,0,'Allow Click Save Button SJ','',0,'\\240604','\\2406',0,'0',0),
        (1925,0,'Allow Click Delete Button SJ','',0,'\\240606','\\2406',0,'0',0),
        (1926,0,'Allow Click Print Button SJ','',0,'\\240607','\\2406',0,'0',0),
        (1927,0,'Allow Click Lock Button SJ','',0,'\\240608','\\2406',0,'0',0),
        (1928,0,'Allow Click UnLock Button SJ','',0,'\\240609','\\2406',0,'0',0),
        (1929,0,'Allow Click Post Button SJ','',0,'\\2406010','\\2406',0,'0',0),
        (1930,0,'Allow Click UnPost  Button SJ','',0,'\\2406011','\\2406',0,'0',0),
        (1931,0,'Allow Change Amount  SJ','',0,'\\2406012','\\2406',0,'0',0),
        (1932,0,'Allow Check Credit Limit SJ','',0,'\\2406013','\\2406',0,'0',0),
        (1933,0,'Allow SJ Amount Auto-Compute on UOM Change','',0,'\\2406014','\\2406',0,'0',0),
        (1934,0,'Allow View Transaction Accounting SJ','',0,'\\2406015','\\2406',0,'0',0),
        (1935,1,'Allow Click Add Item SJ','',0,'\\2406016','\\2406',0,'0',0),
        (1936,1,'Allow Click Edit Item SJ','',0,'\\2406017','\\2406',0,'0',0),
        (1937,1,'Allow Click Delete Item SJ','',0,'\\2406018','\\2406',0,'0',0),
        (2725,1,'Admin','',0,'\\2406020','\\2406',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SD','/module/warehousing/sd','Sales Journal Dealer','fa fa-tasks sub_menu_ico',1919)";
    } //end function

    public function sb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1938,0,'Sales Order Branch','',0,'\\2407','$parent',0,'0',0),
        (1939,0,'Allow View Transaction SO','SO',0,'\\240701','\\2407',0,'0',0),
        (1940,0,'Allow Click Edit Button SO','',0,'\\240702','\\2407',0,'0',0),
        (1941,0,'Allow Click New  Button SO','',0,'\\240703','\\2407',0,'0',0),
        (1942,0,'Allow Click Save Button SO','',0,'\\240704','\\2407',0,'0',0),
        (1944,0,'Allow Click Delete Button SO','',0,'\\240706','\\2407',0,'0',0),
        (1945,0,'Allow Click Print Button SO','',0,'\\240707','\\2407',0,'0',0),
        (1946,0,'Allow Click Lock Button SO','',0,'\\240708','\\2407',0,'0',0),
        (1947,0,'Allow Click UnLock Button SO','',0,'\\240709','\\2407',0,'0',0),
        (1948,0,'Allow Change Amount  SO','',0,'\\2407010','\\2407',0,'0',0),
        (1949,0,'Allow Check Credit Limit SO','',0,'\\2407011','\\2407',0,'0',0),
        (1950,0,'Allow Change of Discount','',0,'\\2407012','\\2407',0,'0',0),
        (1951,0,'Allow Click Post Button SO','',0,'\\2407013','\\2407',0,'0',0),
        (1952,0,'Allow Click UnPost  Button SO','',0,'\\2407014','\\2407',0,'0',0),
        (1953,1,'Allow Click Add Item SO','',0,'\\2407015','\\2407',0,'0',0),
        (1954,1,'Allow Click Edit Item SO','',0,'\\2407016','\\2407',0,'0',0),
        (1955,1,'Allow Click Delete Item SO','',0,'\\2407017','\\2407',0,'0',0),
        (2217,1,'Admin','',0,'\\2407018','\\2407',0,'0',0),
        (3598,1,'Allow Void Button','',0,'\\2407019','\\2407',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SB','/module/warehousing/sb','Sales Order Branch','fa fa-tasks sub_menu_ico',1938)";
    } //end function

    public function se($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1956,0,'Sales Journal Branch','',0,'\\2408','$parent',0,'0',0) ,
        (1957,0,'Allow View Transaction SJ','SJ',0,'\\240801','\\2408',0,'0',0) ,
        (1958,0,'Allow Click Edit Button SJ','',0,'\\240802','\\2408',0,'0',0) ,
        (1959,0,'Allow Click New  Button SJ','',0,'\\240803','\\2408',0,'0',0) ,
        (1960,0,'Allow Click Save Button SJ','',0,'\\240804','\\2408',0,'0',0) ,
        (1962,0,'Allow Click Delete Button SJ','',0,'\\240806','\\2408',0,'0',0) ,
        (1963,0,'Allow Click Print Button SJ','',0,'\\240807','\\2408',0,'0',0) ,
        (1964,0,'Allow Click Lock Button SJ','',0,'\\240808','\\2408',0,'0',0) ,
        (1965,0,'Allow Click UnLock Button SJ','',0,'\\240809','\\2408',0,'0',0) ,
        (1966,0,'Allow Click Post Button SJ','',0,'\\2408010','\\2408',0,'0',0) ,
        (1977,0,'Allow Click UnPost  Button SJ','',0,'\\2408011','\\2408',0,'0',0) ,
        (1978,0,'Allow Change Amount  SJ','',0,'\\2408012','\\2408',0,'0',0) ,
        (1979,0,'Allow Check Credit Limit SJ','',0,'\\2408013','\\2408',0,'0',0) ,
        (1980,0,'Allow SJ Amount Auto-Compute on UOM Change','',0,'\\2408014','\\2408',0,'0',0) ,
        (1981,0,'Allow View Transaction Accounting SJ','',0,'\\2408015','\\2408',0,'0',0) ,
        (1982,1,'Allow Click Add Item SJ','',0,'\\2408016','\\2408',0,'0',0) ,
        (1983,1,'Allow Click Edit Item SJ','',0,'\\2408017','\\2408',0,'0',0) ,
        (1984,1,'Allow Click Delete Item SJ','',0,'\\2408018','\\2408',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SE','/module/warehousing/se','Sales Journal Branch','fa fa-tasks sub_menu_ico',1956)";
    } //end function

    public function sc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1985,0,'Sales Order Online','',0,'\\2409','$parent',0,'0',0) ,
        (1986,0,'Allow View Transaction SO','SO',0,'\\240901','\\2409',0,'0',0) ,
        (1987,0,'Allow Click Edit Button SO','',0,'\\240902','\\2409',0,'0',0) ,
        (1988,0,'Allow Click New  Button SO','',0,'\\240903','\\2409',0,'0',0) ,
        (1989,0,'Allow Click Save Button SO','',0,'\\240904','\\2409',0,'0',0) ,
        (1991,0,'Allow Click Delete Button SO','',0,'\\240906','\\2409',0,'0',0) ,
        (1992,0,'Allow Click Print Button SO','',0,'\\240907','\\2409',0,'0',0) ,
        (1993,0,'Allow Click Lock Button SO','',0,'\\240908','\\2409',0,'0',0) ,
        (1994,0,'Allow Click UnLock Button SO','',0,'\\240909','\\2409',0,'0',0) ,
        (1995,0,'Allow Change Amount  SO','',0,'\\2409010','\\2409',0,'0',0) ,
        (1996,0,'Allow Check Credit Limit SO','',0,'\\2409011','\\2409',0,'0',0) ,
        (1997,0,'Allow Change of Discount','',0,'\\2409012','\\2409',0,'0',0) ,
        (1998,0,'Allow Click Post Button SO','',0,'\\2409013','\\2409',0,'0',0) ,
        (1999,0,'Allow Click UnPost  Button SO','',0,'\\2409014','\\2409',0,'0',0) ,
        (2000,1,'Allow Click Add Item SO','',0,'\\2409015','\\2409',0,'0',0) ,
        (2001,1,'Allow Click Edit Item SO','',0,'\\2409016','\\2409',0,'0',0) ,
        (2002,1,'Allow Click Delete Item SO','',0,'\\2409017','\\2409',0,'0',0),
        (2218,1,'Admin','',0,'\\2409018','\\2409',0,'0',0),
        (3599,1,'Allow Void Button','',0,'\\2409019','\\2409',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SC','/module/warehousing/sc','Sales Order Online','fa fa-tasks sub_menu_ico',1985)";
    } //end function


    public function sf($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2003,0,'Sales Journal Online','',0,'\\24010','$parent',0,'0',0) ,
        (2004,0,'Allow View Transaction SJ','SJ',0,'\\2401001','\\24010',0,'0',0) ,
        (2005,0,'Allow Click Edit Button SJ','',0,'\\2401002','\\24010',0,'0',0) ,
        (2006,0,'Allow Click New  Button SJ','',0,'\\2401003','\\24010',0,'0',0) ,
        (2007,0,'Allow Click Save Button SJ','',0,'\\2401004','\\24010',0,'0',0) ,
        (2009,0,'Allow Click Delete Button SJ','',0,'\\2401006','\\24010',0,'0',0) ,
        (2010,0,'Allow Click Print Button SJ','',0,'\\2401007','\\24010',0,'0',0) ,
        (2011,0,'Allow Click Lock Button SJ','',0,'\\2401008','\\24010',0,'0',0) ,
        (2012,0,'Allow Click UnLock Button SJ','',0,'\\2401009','\\24010',0,'0',0) ,
        (2013,0,'Allow Click Post Button SJ','',0,'\\24010010','\\24010',0,'0',0) ,
        (2014,0,'Allow Click UnPost  Button SJ','',0,'\\24010011','\\24010',0,'0',0) ,
        (2015,0,'Allow Change Amount  SJ','',0,'\\24010012','\\24010',0,'0',0) ,
        (2016,0,'Allow Check Credit Limit SJ','',0,'\\24010013','\\24010',0,'0',0) ,
        (2017,0,'Allow SJ Amount Auto-Compute on UOM Change','',0,'\\24010014','\\24010',0,'0',0) ,
        (2018,0,'Allow View Transaction Accounting SJ','',0,'\\24010015','\\24010',0,'0',0) ,
        (2019,1,'Allow Click Add Item SJ','',0,'\\24010016','\\24010',0,'0',0) ,
        (2020,1,'Allow Click Edit Item SJ','',0,'\\24010017','\\24010',0,'0',0) ,
        (2021,1,'Allow Click Delete Item SJ','',0,'\\24010018','\\24010',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SF','/module/warehousing/sf','Sales Journal Online','fa fa-tasks sub_menu_ico',2003)";
    } //end function

    public function sg($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2150,0,'Special Parts Request','',0,'\\24024','$parent',0,'0',0),
        (2151,0,'Allow View Transaction','SG',0,'\\2402401','\\24024',0,'0',0),
        (2152,0,'Allow Click Edit Button','',0,'\\2402402','\\24024',0,'0',0),
        (2153,0,'Allow Click New  Button','',0,'\\2402403','\\24024',0,'0',0),
        (2154,0,'Allow Click Save Button','',0,'\\2402404','\\24024',0,'0',0),
        (2156,0,'Allow Click Delete Button','',0,'\\2402406','\\24024',0,'0',0),
        (2157,0,'Allow Click Print Button','',0,'\\2402407','\\24024',0,'0',0),
        (2158,0,'Allow Click Lock Button','',0,'\\2402408','\\24024',0,'0',0),
        (2159,0,'Allow Click UnLock Button','',0,'\\2402409','\\24024',0,'0',0),
        (2160,0,'Allow Change Amount','',0,'\\2402410','\\24024',0,'0',0),
        (2161,0,'Allow Check Credit Limit','',0,'\\2402411','\\24024',0,'0',0),
        (2162,0,'Allow Change of Discount','',0,'\\2402412','\\24024',0,'0',0),
        (2163,0,'Allow Click Post Button','',0,'\\2402413','\\24024',0,'0',0),
        (2164,0,'Allow Click UnPost  Button','',0,'\\2402414','\\24024',0,'0',0),
        (2165,1,'Allow Click Add Item','',0,'\\2402415','\\24024',0,'0',0),
        (2166,1,'Allow Click Edit Item','',0,'\\2402416','\\24024',0,'0',0),
        (2167,1,'Allow Click Delete Item','',0,'\\2402417','\\24024',0,'0',0),
        (2219,1,'Admin','',0,'\\2402418','\\24024',0,'0',0),
        (3622,1,'Allow Void Button','',0,'\\2402419','\\24024',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SG','/module/warehousing/sg','Special Parts Request','fa fa-tasks sub_menu_ico',2150)";
    } //end function

    public function sh($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2168,0,'Special Parts Issuance','',0,'\\24025','$parent',0,'0',0),
        (2169,0,'Allow View Transaction','SH',0,'\\2402501','\\24025',0,'0',0),
        (2170,0,'Allow Click Edit Button','',0,'\\2402502','\\24025',0,'0',0),
        (2171,0,'Allow Click New  Button','',0,'\\2402503','\\24025',0,'0',0),
        (2172,0,'Allow Click Save Button','',0,'\\2402504','\\24025',0,'0',0),
        (2174,0,'Allow Click Delete Button','',0,'\\2402506','\\24025',0,'0',0),
        (2175,0,'Allow Click Print Button','',0,'\\2402507','\\24025',0,'0',0),
        (2176,0,'Allow Click Lock Button','',0,'\\2402508','\\24025',0,'0',0),
        (2177,0,'Allow Click UnLock Button','',0,'\\2402509','\\24025',0,'0',0),
        (2178,0,'Allow Click Post Button','',0,'\\2402510','\\24025',0,'0',0),
        (2179,0,'Allow Click UnPost  Button','',0,'\\2402511','\\24025',0,'0',0),
        (2180,0,'Allow Change Amount','',0,'\\2402512','\\24025',0,'0',0),
        (2181,0,'Allow Check Credit Limit','',0,'\\2402513','\\24025',0,'0',0),
        (2182,0,'Allow Amount Auto-Compute on UOM Change','',0,'\\2402514','\\24025',0,'0',0),
        (2183,0,'Allow View Transaction Accounting','',0,'\\2402515','\\24025',0,'0',0),
        (2184,1,'Allow Click Add Item','',0,'\\2402516','\\24025',0,'0',0),
        (2185,1,'Allow Click Edit Item','',0,'\\2402517','\\24025',0,'0',0),
        (2186,1,'Allow Click Delete Item','',0,'\\2402518','\\24025',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SH','/module/warehousing/sh','Special Parts Issuance','fa fa-tasks sub_menu_ico',2168)";
    } //end function

    public function si($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2187,0,'Special Parts Return','',0,'\\24026','$parent',0,'0',0),
        (2188,0,'Allow View Transaction','SI',0,'\\2402601','\\24026',0,'0',0),
        (2189,0,'Allow Click Edit Button','',0,'\\2402602','\\24026',0,'0',0),
        (2190,0,'Allow Click New  Button','',0,'\\2402603','\\24026',0,'0',0),
        (2191,0,'Allow Click Save  Button','',0,'\\2402604','\\24026',0,'0',0),
        (2193,0,'Allow Click Delete Button','',0,'\\2402606','\\24026',0,'0',0),
        (2194,0,'Allow Click Print  Button','',0,'\\2402607','\\24026',0,'0',0),
        (2195,0,'Allow Click Lock Button','',0,'\\2402608','\\24026',0,'0',0),
        (2196,0,'Allow Click UnLock Button','',0,'\\2402609','\\24026',0,'0',0),
        (2197,0,'Allow Click Post Button','',0,'\\2402610','\\24026',0,'0',0),
        (2198,0,'Allow Click UnPost  Button','',0,'\\2402611','\\24026',0,'0',0),
        (2199,0,'Allow View Transaction Accounting','',0,'\\2402612','\\24026',0,'0',0),
        (2200,0,'Allow Change Amount','',0,'\\2402613','\\24026',0,'0',0),
        (2201,1,'Allow Click Add Item','',0,'\\2402614','\\24026',0,'0',0),
        (2202,1,'Allow Click Edit Item','',0,'\\2402615','\\24026',0,'0',0),
        (2203,1,'Allow Click Delete Item','',0,'\\2402616','\\24026',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SI','/module/warehousing/si','Special Parts Return','fa fa-sync sub_menu_ico',2188)";
    } //end function

    public function warehousecontroller($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2022,0,'Inventory Controller','',0,'\\24011','$parent',0,'0',0) ,
        (2023,0,'Allow View Inventory Controller','whctrl',0,'\\2401101','\\24011',0,'0',0) ,
        (2024,0,'Allow Edit Inventory Controller','',0,'\\2401102','\\24011',0,'0',0) ,
        (2025,0,'Allow Save Inventory Controller','',0,'\\2401104','\\24011',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehousecontroller','/ledgergrid/warehousing/warehousecontroller','Inventory Controller','fa fa-pallet sub_menu_ico',2022)";
    } //end function

    public function warehousepicker($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2028,0,'Warehouse Picker','',0,'\\24013','$parent',0,'0',0),
        (2029,0,'Allow View Warehouse Picker','whpckr',0,'\\2401301','\\24013',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehousepicker','/ledgergrid/warehousing/warehousepicker','Warehouse Picker','fa fa-box-open sub_menu_ico',2028)";
    } //end function

    public function warehousechecker($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2030,0,'Warehouse Checker','',0,'\\24014','$parent',0,'0',0),
        (2031,0,'Allow View Warehouse Picker','whchcr',0,'\\2401401','\\24014',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehousechecker','/ledgergrid/warehousing/warehousechecker','Warehouse Checker','fa fa-user-check sub_menu_ico',2030)";
    } //end function

    public function replenishpallet($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2542,0,'Replenish per Pallet','',0,'\\24030','$parent',0,'0',0),
        (2543,0,'Allow View Replenish per Pallet','reppallet',0,'\\2403001','\\24030',0,'0',0),
        (2544,0,'Allow Post Replenish per Pallet','',0,'\\2403002','\\24030',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'replenishpallet','/ledgergrid/warehousing/replenishpallet','Replenish per Pallet','fa fa-pallet sub_menu_ico', 2542)";
    } //end function

    public function replenishitem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2545,0,'Replenish per Item','',0,'\\24031','$parent',0,'0',0),
        (2546,0,'Allow View Replenish per Item','repitem',0,'\\2403101','\\24031',0,'0',0),
        (2547,0,'Allow Post Replenish per Item','',0,'\\2403102','\\24031',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'replenishitem','/ledgergrid/warehousing/replenishitem','Replenish per Item','fa fa-list-alt sub_menu_ico', 2545)";
    } //end function

    public function dispatching($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2034,0,'Dispatching','',0,'\\24016','$parent',0,'0',0),
        (2035,0,'Allow View Dispatching','dispatch',0,'\\2401601','\\24016',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'dispatching','/ledgergrid/warehousing/dispatching','Dispatching','fa fa-box sub_menu_ico',2034)";
    } //end function


    public function logistics($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2036,0,'Logistics','',0,'\\24017','$parent',0,'0',0),
        (2037,0,'Allow View Logistics','whctrl',0,'\\2401701','\\24017',0,'0',0),
        (2038,0,'Allow Edit Logistics','',0,'\\2401702','\\24017',0,'0',0),
        (2451,0,'Allow Print Logistics','',0,'\\2401703','\\24017',0,'0',0),
        (2039,0,'Allow Post Logistics','',0,'\\2401704','\\24017',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'logistics','/ledgergrid/warehousing/logistics','Logistics','fa fa-truck-loading sub_menu_ico',2036)";
    } //end function

    public function wa($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2093,0,'Warranty Request','',0,'\\24019','$parent',0,'0',0) ,
        (2094,0,'Allow View Transaction WR','WA',0,'\\2401901','\\24019',0,'0',0) ,
        (2095,0,'Allow Click Edit Button WR','',0,'\\2401902','\\24019',0,'0',0) ,
        (2096,0,'Allow Click New Button WR','',0,'\\2401903','\\24019',0,'0',0) ,
        (2097,0,'Allow Click Save Button WR','',0,'\\2401904','\\24019',0,'0',0) ,
        (2099,0,'Allow Click Delete Button WR','',0,'\\2401906','\\24019',0,'0',0) ,
        (2100,0,'Allow Click Print Button WR','',0,'\\2401907','\\24019',0,'0',0) ,
        (2101,0,'Allow Click Lock Button WR','',0,'\\2401908','\\24019',0,'0',0) ,
        (2102,0,'Allow Click UnLock Button WR','',0,'\\2401909','\\24019',0,'0',0) ,
        (2103,0,'Allow Change Cost WR','',0,'\\2401910','\\24019',0,'0',0) ,
        (2104,0,'Allow Click Post Button WR','',0,'\\2401912','\\24019',0,'0',0) ,
        (2105,0,'Allow Click UnPost  Button WR','',0,'\\2401913','\\24019',0,'0',0) ,
        (2106,1,'Allow Click Add Item WR','',0,'\\2401914','\\24019',0,'0',0) ,
        (2107,1,'Allow Click Edit Item WR','',0,'\\2401915','\\24019',0,'0',0) ,
        (2108,1,'Allow Click Delete Item WR','',0,'\\2401916','\\24019',0,'0',0) ,
        (2109,1,'Allow View Cost','',0,'\\2401917','\\24019',0,'0',0),
        (3596,1,'Allow Void Button','',0,'\\2401918','\\24019',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'WA','/module/warehousing/wa','Warranty Request','fa fa-tasks sub_menu_ico',2093)";
    } //end function

    public function wb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2110,0,'Warranty Receiving','',0,'\\24020','$parent',0,'0',0) ,
        (2111,0,'Allow View Transaction WRR','WB',0,'\\2402001','\\24020',0,'0',0) ,
        (2112,0,'Allow Click Edit Button WRR','',0,'\\2402002','\\24020',0,'0',0) ,
        (2113,0,'Allow Click New Button WRR','',0,'\\2402003','\\24020',0,'0',0) ,
        (2114,0,'Allow Click Save Button WRR','',0,'\\2402004','\\24020',0,'0',0) ,
        (2116,0,'Allow Click Delete Button WRR','',0,'\\2402006','\\24020',0,'0',0) ,
        (2117,0,'Allow Click Print Button WRR','',0,'\\2402007','\\24020',0,'0',0) ,
        (2118,0,'Allow Click Lock Button WRR','',0,'\\2402008','\\24020',0,'0',0) ,
        (2119,0,'Allow Click UnLock Button WRR','',0,'\\2402009','\\24020',0,'0',0) ,
        (2120,0,'Allow Click Post Button WRR','',0,'\\2402010','\\24020',0,'0',0) ,
        (2121,0,'Allow Click UnPost Button WRR','',0,'\\2402011','\\402',0,'0',0) ,
        (2122,0,'Allow View Transaction accounting WRR','',0,'\\2402012','\\24020',0,'0',0) ,
        (2123,0,'Allow Change Amount WRR','',0,'\\2402013','\\24020',0,'0',0) ,
        (2124,1,'Allow Click Add Item WRR','',0,'\\2402014','\\24020',0,'0',0) ,
        (2125,1,'Allow Click Edit Item WRR','',0,'\\2402015','\\24020',0,'0',0) ,
        (2126,1,'Allow Click Delete Item WRR','',0,'\\2402016','\\24020',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'WB','/module/warehousing/wb','Warranty Receiving','fa fa-tasks sub_menu_ico',2110)";
    } //end function

    public function incentivesgenerator($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2518,0,'Incentives Generator','',0,'\\24033','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'incentivesgenerator','/headtable/warehousingentry/incentivesgenerator','Incentives Generator','fa fa-calculator sub_menu_ico',2518)";
    } //end function

    public function parentconsignment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2333,0,'CONSIGNMENT','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'CONSIGNMENT',$sort,'fa fa-clipboard-check',',consignment')";
    } //end function

    public function cn($params, $parent, $sort)
    { // consignment request
        $p = $parent;
        $parent = '\\' . $parent; // 2334
        $qry = " (2334,0,'Consignment Request','',0,'\\2801','$parent',0,'0',0),
        (2335,0,'Allow View Consignment Request','CN',0,'\\280101','\\2801',0,'0',0),
        (2336,0,'Allow Click Edit Button CN','',0,'\\280102','\\2801',0,'0',0),
        (2337,0,'Allow Click New  Button CN','',0,'\\280103','\\2801',0,'0',0),
        (2338,0,'Allow Click Save Button CN','',0,'\\280104','\\2801',0,'0',0),
        (2340,0,'Allow Click Delete Button CN','',0,'\\280106','\\2801',0,'0',0),
        (2341,0,'Allow Click Print Button CN','',0,'\\280107','\\2801',0,'0',0),
        (2342,0,'Allow Click Lock Button CN','',0,'\\280108','\\2801',0,'0',0),
        (2343,0,'Allow Click UnLock Button CN','',0,'\\280109','\\2801',0,'0',0),
        (2344,0,'Allow Change Amount  CN','',0,'\\280110','\\2801',0,'0',0),
        (2345,0,'Allow Check Credit Limit CN','',0,'\\280111','\\2801',0,'0',0),
        (2346,0,'Allow Click Post Button CN','',0,'\\280112','\\2801',0,'0',0),
        (2347,0,'Allow Click UnPost  Button CN','',0,'\\280113','\\2801',0,'0',0),
        (2348,1,'Allow Click Add Item CN','',0,'\\280114','\\2801',0,'0',0),
        (2349,1,'Allow Click Edit Item CN','',0,'\\280115','\\2801',0,'0',0),
        (2350,1,'Allow Click Delete Item CN','',0,'\\280116','\\2801',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CN','/module/consignment/cn','Consignment Request','fa fa-toolbox sub_menu_ico',2334)";
    } //end function

    public function co($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2351,0,'Consignment DR','',0,'\\2802','$parent',0,'0',0) ,
        (2352,0,'Allow View Transaction CO','CO',0,'\\280201','\\2802',0,'0',0) ,
        (2353,0,'Allow Click Edit Button  CO','',0,'\\280202','\\2802',0,'0',0) ,
        (2354,0,'Allow Click New Button CO','',0,'\\280203','\\2802',0,'0',0) ,
        (2355,0,'Allow Click Save Button CO','',0,'\\280204','\\2802',0,'0',0) ,
        (2357,0,'Allow Click Delete Button CO','',0,'\\280206','\\2802',0,'0',0) ,
        (2358,0,'Allow Click Print Button CO','',0,'\\280207','\\2802',0,'0',0) ,
        (2359,0,'Allow Click Lock Button CO','',0,'\\280208','\\2802',0,'0',0) ,
        (2360,0,'Allow Click UnLock Button CO','',0,'\\280209','\\2802',0,'0',0) ,
        (2361,0,'Allow Click Post Button CO','',0,'\\280210','\\2802',0,'0',0) ,
        (2362,0,'Allow Click UnPost Button CO','',0,'\\280211','\\2802',0,'0',0) ,
        (2363,1,'Allow Click Add Item CO','',0,'\\280212','\\2802',0,'0',0) ,
        (2364,1,'Allow Click Edit Item CO','',0,'\\280213','\\2802',0,'0',0) ,
        (2365,1,'Allow Click Delete Item CO','',0,'\\280215','\\2802',0,'0',0) ,
        (2366,1,'Allow Change Amount CO','',0,'\\280216','\\2802',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CO','/module/consignment/co','Consignment DR','fa fa-dolly-flatbed sub_menu_ico',2351)";
    } //end function

    public function cs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2382,0,'Consignment Sales','',0,'\\2803','$parent',0,'0',0),
        (2383,0,'Allow View Transaction CS','CS',0,'\\280301','\\2803',0,'0',0),
        (2384,0,'Allow Click Edit Button CS','',0,'\\280302','\\2803',0,'0',0),
        (2385,0,'Allow Click New  Button CS','',0,'\\280303','\\2803',0,'0',0),
        (2386,0,'Allow Click Save Button CS','',0,'\\280304','\\2803',0,'0',0),
        (2388,0,'Allow Click Delete Button CS','',0,'\\280306','\\2803',0,'0',0),
        (2389,0,'Allow Click Print Button CS','',0,'\\280307','\\2803',0,'0',0),
        (2390,0,'Allow Click Lock Button CS','',0,'\\280308','\\2803',0,'0',0),
        (2391,0,'Allow Click UnLock Button CS','',0,'\\280309','\\2803',0,'0',0),
        (2392,0,'Allow Click Post Button CS','',0,'\\280310','\\2803',0,'0',0),
        (2393,0,'Allow Click UnPost  Button CS','',0,'\\280311','\\2803',0,'0',0),
        (2394,0,'Allow Change Amount  CS','',0,'\\280313','\\2803',0,'0',0),
        (2395,0,'Allow Check Credit Limit CS','',0,'\\280314','\\2803',0,'0',0),
        (2396,0,'Allow CS Amount Auto-Compute on UOM Change','',0,'\\280315','\\2803',0,'0',0),
        (2397,0,'Allow View Transaction Accounting CS','',0,'\\280316','\\2803',0,'0',0),
        (2398,1,'Allow Click Add Item CS','',0,'\\280317','\\2803',0,'0',0),
        (2399,1,'Allow Click Edit Item CS','',0,'\\280318','\\2803',0,'0',0),
        (2400,1,'Allow Click Delete Item CS','',0,'\\280319','\\2803',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CS','/module/consignment/cs','Consignment Sales','fa fa-file-invoice sub_menu_ico',2382)";
    } //end function



    public function deliverytype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $label = 'Delivery Type Masterfile';
        if ($params['companyid'] == 19) { //housegem
            $label = 'Truck Type';
        }

        $qry = "(2148,1,'" . $label . "','',0,'\\24023','$parent',0,0,0),
        (2149,0,'Allow View " . $label . "','',0,'\\2402301','\\24023',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrydeliverytype','/tableentries/warehousingentry/entrydeliverytype','" . $label . "','fas fa-shipping-fast sub_menu_ico',2148)";
    } //end function

    public function whrem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2401,1,'Warehouse Remarks','',0,'\\24029','$parent',0,0,0),
        (2402,0,'Allow View Warehouse Remarks','',0,'\\2402901','\\24029',0,'0',0),
        (2403,0,'Allow Edit Warehouse Remarks','',0,'\\2402902','\\24029',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrywhrem','/tableentries/warehousingentry/entrywhrem','Warehouse Remarks','fas fa-tasks sub_menu_ico', 2401)";
    } //end function

    public function parentmasterfile($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1,0,'MASTER FILE','',0,'$parent','\\',0,'',0)";

        if ($params['companyid'] == 34) { //evergreen
            $qry .= ",(1730,1,'View Attach Documents','',0,'\\810','$parent',0,'0',0),
            (1731,1,'Attach Documents','',0,'\\81001','\\810',0,'0',0),
            (1732,1,'Download Documents','',0,'\\81002','\\810',0,'0',0),
            (1733,1,'Delete Documents','',0,'\\81003','\\810',0,'0',0),
            (3687,0,'Allow View To Do','',0,'\\814','$parent',0,'0',0),
            (4077,0,'Allow View All Application/Contracts','',0,'\\822','$parent',0,'0',0),
            (1729,1,'Allow Override Plan Limit','',0,'\\811','$parent',0,'0',0),
            (3723,0,'Restrict IP','',0,'\\818','$parent',0,'0',0),
            (4098,0,'Allow to search & view transactions','',0,'\\823','$parent',0,'0',0)";
        }

        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'MASTERFILE',$sort,'description',',coa,customer,supplier,agent,warehouse,stockcard,facard,fbrmanager,part,model,stockgrp,department,itemquery,productinquiry')";
    } //end function

    public function parentreportslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(100,0,'REPORT LIST','',0,'$parent','\\',0,'',0)";

        return "insert into left_parent(id,name,seq,class,doc) values($p,'REPORT LIST',$sort,'description',',,')";
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
        $qry = "(2373,1,'" . $fieldLabel . "','',0,'\\24028','$parent',0,0,0),
        (2374,0,'Allow View Item Category','',0,'\\2402801','\\24028',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'itemcategory','/tableentries/warehousingentry/entryitemcategory','" . $fieldLabel . "','fa fa-dolly-flatbed sub_menu_ico',2373)";
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
        $qry = "(2516,1,'" . $fieldLabel . "','',0,'\\24032','$parent',0,0,0),
        (2517,0,'Allow View Item Sub-category','',0,'\\2403201','\\24032',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'itemsubcategory','/tableentries/tableentry/entryitemsubcategory','" . $fieldLabel . "','fa fa-dolly-flatbed sub_menu_ico',2516)";
    } //end function


    public function customer($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(21,0,'Customer Ledger','',0,'\\103','$parent',0,'0',0),
        (22,0,'Allow View Customer Ledger','CUSTOMER',0,'\\10301','\\103',0,'0',0),
        (23,0,'Allow Click Edit Button CL','',0,'\\10302','\\103',0,'0',0),
        (24,0,'Allow Click New Button CL','',0,'\\10303','\\103',0,'0',0),
        (25,0,'Allow Click Save Button CL','',0,'\\10304','\\103',0,'0',0),        
        (27,0,'Allow Click Delete Button CL','',0,'\\10306','\\103',0,'0',0),
        (28,0,'Allow Click Print Button CL','',0,'\\10307','\\103',0,'0',0),";


        if ($params['companyid'] != 34) { //not evergreen
            $qry .= "(2734,0,'Allow View SKU Entry','',0,'\\10308','\\103',0,'0',0),
            (2735,0,'Allow View AR History','',0,'\\10309','\\103',0,'0',0),
            (2736,0,'Allow View AP History','',0,'\\103010','\\103',0,'0',0),
            (2737,0,'Allow View PDC History','',0,'\\103011','\\103',0,'0',0),
            (2738,0,'Allow View Returned Checks History','',0,'\\103012','\\103',0,'0',0),
            (2739,0,'Allow View Inventory History','',0,'\\103013','\\103',0,'0',0),
            (2740,0,'Allow View Default Shipping/Billing Address','',0,'\\103014','\\103',0,'0',0),
            (2741,0,'Allow View Shipping/Billing Address Setup','',0,'\\103015','\\103',0,'0',0),
            (2992,0,'Allow View Unpaid AR','',0,'\\103016','\\103',0,'0',0),
            (3744,0,'Allow View Contact Person Setup','',0,'\\103017','\\103',0,'0',0)";
        } else {
            $qry .= "(2735,0,'Allow View AR History','',0,'\\10309','\\103',0,'0',0),
            (2736,0,'Allow View AP History','',0,'\\103010','\\103',0,'0',0),
            (2737,0,'Allow View PDC History','',0,'\\103011','\\103',0,'0',0),
            (2738,0,'Allow View Returned Checks History','',0,'\\103012','\\103',0,'0',0),
            (2992,0,'Allow View Unpaid AR','',0,'\\103016','\\103',0,'0',0)";
        }

        if ($params['companyid'] == 16) { //ati
            $qry .= ", (3745,0,'Limit Customer Details','',0,'\\103018','\\103',0,'0',0)";
        }

        if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti, afti usd
            $qry .= ",(3768,0,'Allow Edit Customer Credit Limit','',0,'\\103018','\\103',0,'0',0),
            (3769,0,'Allow Edit Customer Notes(Acctg)','',0,'\\103019','\\103',0,'0',0)";
        }
        $this->insertattribute($params, $qry);
        return "($sort,$p,'customer','/ledgergrid/masterfile/customer','Customer','fa fa-address-card sub_menu_ico',21)";
    } //end function

    public function supplier($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(31,0,'Supplier Ledger','',0,'\\104','$parent',0,'0',0),
        (32,0,'Allow View Supplier Ledger','SU',0,'\\10401','\\104',0,'0',0),
        (33,0,'Allow Click Edit Button SL','',0,'\\10402','\\104',0,'0',0),
        (34,0,'Allow Click New Button SL','',0,'\\10403','\\104',0,'0',0),
        (35,0,'Allow Click Save Button SL','',0,'\\10404','\\104',0,'0',0),
        (36,0,'Allow Click Change Supplier Code SL','',0,'\\10405','\\104',0,'0',0),
        (37,0,'Allow Click Delete  Button SL','',0,'\\10406','\\104',0,'0',0),
        (38,0,'Allow Click Print Button SL','',0,'\\10407','\\104',0,'0',0),

        (2742,0,'Allow View AR History','',0,'\\10408','\\104',0,'0',0),
        (2743,0,'Allow View AP History','',0,'\\10409','\\104',0,'0',0),
        (2744,0,'Allow View Inventory History','',0,'\\10410','\\104',0,'0',0),
        (2745,0,'Allow Allow View Default Shipping/Billing Address','',0,'\\10411','\\104',0,'0',0),
        (2746,0,'Allow Allow View Shipping/Billing Address Setup','',0,'\\10412','\\104',0,'0',0),
        (2993,0,'Allow Allow View Unpaid AP','',0,'\\10413','\\104',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'supplier','/ledger/masterfile/supplier','Supplier','fa fa-user-tie sub_menu_ico',31)";
    } //end function

    public function employeemaster($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(868,0,'Employee Ledger','',0,'\\107','$parent',0,'0',0),
        (869,0,'Allow View Employee Ledger','',0,'\\10701','\\107',0,'0',0),
        (870,0,'Allow Click Edit Button EMP','',0,'\\10702','\\107',0,'0',0),
        (871,0,'Allow Click New Button EMP','',0,'\\10703','\\107',0,'0',0),
        (872,0,'Allow Click Save Button EMP','',0,'\\10704','\\107',0,'0',0),
        (873,0,'Allow Click Change Code EMP','',0,'\\10705','\\107',0,'0',0),
        (874,0,'Allow Click Delete Button EMP','',0,'\\10706','\\107',0,'0',0),
        (875,0,'Allow Click Print Button EMP','',0,'\\10707','\\107',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'employee','/ledger/masterfile/employee','Employee','fa fa-user sub_menu_ico',868)";
    } //end function

    public function departmentmaster($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(860,0,'Department Ledger','',0,'\\108','$parent',0,'0',0),
        (861,0,'Allow View Department Ledger','',0,'\\10801','\\108',0,'0',0),
        (862,0,'Allow Click Edit Button DEPT','',0,'\\10802','\\108',0,'0',0),
        (863,0,'Allow Click New Button DEPT','',0,'\\10803','\\108',0,'0',0),
        (864,0,'Allow Click Save Button DEPT','',0,'\\10804','\\108',0,'0',0),
        (865,0,'Allow Click Change Code DEPT','',0,'\\10805','\\108',0,'0',0),
        (866,0,'Allow Click Delete Button DEPT','',0,'\\10806','\\108',0,'0',0),
        (867,0,'Allow Click Print Button DEPT','',0,'\\10807','\\108',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'department','/ledger/masterfile/department','Department','fa fa-code-branch sub_menu_ico',860)";
    } //end function

    public function stockcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Stockcard';
        if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti, afti usd
            $label = 'Main Items';
        }
        $qry = "(11,0,'" . $label . "','',0,'\\102','$parent',0,'0',0),
        (12,0,'Allow View " . $label . "','SK',0,'\\10201','\\102',0,'0',0),
        (13,0,'Allow Click Edit Button SK','',0,'\\10202','\\102',0,'0',0),
        (14,0,'Allow Click New Button SK','',0,'\\10203','\\102',0,'0',0),
        (15,0,'Allow Click Save Button SK','',0,'\\10204','\\102',0,'0',0),
        (16,0,'Allow Click Change Barcode SK','',0,'\\10205','\\102',0,'0',0),
        (17,0,'Allow Click Delete Button SK','',0,'\\10206','\\102',0,'0',0),
        (18,0,'Allow Print Button SK','',0,'\\10207','\\102',0,'0',0),
        (19,0,'Allow View SRP Button SK','',0,'\\10208','\\102',0,'0',0),
        (3689,0,'Allow Edit UOM factor','',0,'\\10209','\\102',0,'0',0)";

        if ($this->companysetup->isrecalc($params)) {
            $qry .= ",(3690,0,'Allow Recalc','',0,'\\10210','\\102',0,'0',0)";
        }
        $this->insertattribute($params, $qry);

        return "($sort,$p,'stockcard','/ledgergrid/masterfile/stockcard','" . $label . "','fa fa-list-alt sub_menu_ico',11)";
    } //end function

    public function itemquery($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3625,0,'Item Query','',0,'\\123','$parent',0,'0',0),
        (3626,0,'Allow View Item Query','',0,'\\12301','\\123',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'stockcard','/ledgergrid/inquiry/stockcard','Item Query','fa fa-list-alt sub_menu_ico',3625)";
    }


    public function productinquiry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4451,0,'Product Inquiry','',0,'\\125','$parent',0,'0',0),
        (4452,0,'Allow View Product Inquiry','',0,'\\12501','\\125',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'stockcard','/ledgergrid/productinquiry/stockcard','Product Inquiry','fa fa-list-alt sub_menu_ico',4451)";
    }



    public function facard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2223,0,'Fixed Asset Card','',0,'\\109','$parent',0,'0',0),
          (2224,0,'Allow View FA Card','FA',0,'\\10901','\\109',0,'0',0),
          (2225,0,'Allow Click Edit Button FA','',0,'\\10902','\\109',0,'0',0),
          (2226,0,'Allow Click New Button FA','',0,'\\10903','\\109',0,'0',0),
          (2227,0,'Allow Click Save Button FA','',0,'\\10904','\\109',0,'0',0),
          (2228,0,'Allow Click Change Barcode FA','',0,'\\10905','\\109',0,'0',0),
          (2229,0,'Allow Click Delete Button FA','',0,'\\10906','\\109',0,'0',0),
          (2230,0,'Allow Print Button FA','',0,'\\10907','\\109',0,'0',0),
          (2231,0,'Allow View SRP Button FA','',0,'\\10908','\\109',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'facard','/ledgergrid/fixedasset/stockcard','Fixed Asset Card','fa fa-list-alt sub_menu_ico',2223)";
    }

    public function role($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2404,0,'Role Masterfile','',0,'\\110','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'role','/tableentries/tableentry/entryrole','Role Masterfile','fa fa-tasks sub_menu_ico',2404)";
    } //end function
    public function biometric($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4503,0,'Biometric Terminal','',0,'\\1148','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'biometric','/tableentries/tableentry/entrybiometric','Biometric Terminal','fa fa-tasks sub_menu_ico',4503)";
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
        $qry = "(41,0,'" . $modulename . " Ledger','',0,'\\105','$parent',0,'0',0),
        (42,0,'Allow View " . $modulename . " Ledger','AG',0,'\\10501','\\105',0,'0',0),
        (43,0,'Allow Click Edit Button AL','',0,'\\10502','\\105',0,'0',0),
        (44,0,'Allow Click New Button AL','',0,'\\10503','\\105',0,'0',0),
        (45,0,'Allow Click Save Button AL','',0,'\\10504','\\105',0,'0',0),        
        (47,0,'Allow Click Delete Button AL','',0,'\\10506','\\105',0,'0',0),
        (48,0,'Allow Click Print Button AL','',0,'\\10507','\\105',0,'0',0)";
        $this->insertattribute($params, $qry);
        if ($companyid == 34) { //evergreen
            return "($sort,$p,'agent','/ledger/masterfile/agent','Employee','fa fa-id-card-alt sub_menu_ico',41)";
        } else {
            return "($sort,$p,'agent','/ledger/masterfile/agent','Agent','fa fa-id-card-alt sub_menu_ico',41)";
        }
    } //end function

    public function warehouse($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(51,0,'Warehouse Ledger','',0,'\\106','$parent',0,'0',0),
        (52,0,'Allow View Warehouse','WH',0,'\\10601','\\106',0,'0',0),
        (53,0,'Allow Click Edit Button WL','',0,'\\10602','\\106',0,'0',0),
        (54,0,'Allow Click New Button WL','',0,'\\10603','\\106',0,'0',0),
        (55,0,'Allow Click Save Button WL','',0,'\\10604','\\106',0,'0',0),
        (56,0,'Allow Click Change Warehouse Code  WL','',0,'\\10605','\\106',0,'0',0),
        (57,0,'Allow Click Delete Button WL','',0,'\\10606','\\106',0,'0',0),
        (58,0,'Allow Click Print Button WL','',0,'\\10607','\\106',0,'0',0)";

        switch ($params['companyid']) {
            case 3: //conto
            case 43: //mighty
                $qry .= ", (2731,0,'Allow View Document Tab','',0,'\\10608','\\106',0,'0',0),
                     (2732,0,'Allow View NODS Tab','',0,'\\10609','\\106',0,'0',0),
                     (2733,0,'Allow View Job Request Tab','',0,'\\106010','\\106',0,'0',0)";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehouse','/ledgergrid/masterfile/warehouse','Warehouse','fa fa-warehouse sub_menu_ico',51)";
    } //end function

    public function branchledger($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2585,0,'Branch Ledger','',0,'\\111','$parent',0,'0',0),
        (2586,0,'Allow View Branch','BH',0,'\\11101','\\111',0,'0',0),
        (2587,0,'Allow Click Edit Button BH','',0,'\\11102','\\111',0,'0',0),
        (2588,0,'Allow Click New Button BH','',0,'\\11103','\\111',0,'0',0),
        (2589,0,'Allow Click Save Button BH','',0,'\\11104','\\111',0,'0',0),
        (2590,0,'Allow Click Change Branch Code BH','',0,'\\11105','\\111',0,'0',0),
        (2591,0,'Allow Click Delete Button BH','',0,'\\11106','\\111',0,'0',0),
        (2592,0,'Allow Click Print Button BH','',0,'\\11107','\\111',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'branch','/ledgergrid/masterfile/branch','Branch','fa fa-warehouse sub_menu_ico',2585)";
    } //end function

    public function parentpurchase($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(61,0,'PURCHASES','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        if ($params['companyid'] == 12) { //afti usd
            return "insert into left_parent(id,name,seq,class,doc) values($p,'PURCHASES',$sort,'shopping_basket',',PO')";
        } elseif ($params['companyid'] == 32) { //3m
            return "insert into left_parent(id,name,seq,class,doc) values($p,'PURCHASES',$sort,'shopping_basket',',PO,RR,DM')";
        } else {
            return "insert into left_parent(id,name,seq,class,doc) values($p,'PURCHASES',$sort,'shopping_basket',',PR,PO,RR,DM')";
        }
    } //end function

    public function ph($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4392,0,'Price Change','',0,'\\420','$parent',0,'0',0),
            (4393,0,'Allow View Transaction PH','PH',0,'\\42001','\\420',0,'0',0),
            (4394,0,'Allow Click Edit Button PH','',0,'\\42002','\\420',0,'0',0),
            (4395,0,'Allow Click New Button PH','',0,'\\42003','\\420',0,'0',0),
            (4396,0,'Allow Click Save Button PH','',0,'\\42004','\\420',0,'0',0),
            (4397,0,'Allow Click Delete Button PH','',0,'\\42005','\\420',0,'0',0),
            (4398,0,'Allow Click Print Button PH','',0,'\\42006','\\420',0,'0',0),
            (4399,0,'Allow Click Lock Button PH','',0,'\\42007','\\420',0,'0',0),
            (4400,0,'Allow Click UnLock Button PH','',0,'\\42008','\\420',0,'0',0),
            (4401,0,'Allow Click Post Button PH','',0,'\\42009','\\420',0,'0',0),
            (4402,0,'Allow Click UnPost Button PH','',0,'\\42010','\\420',0,'0',0),
            (4403,0,'Allow Click Add Item PH','',0,'\\42011','\\420',0,'0',0),
            (4404,0,'Allow Click Edit Item PH','',0,'\\42012','\\420',0,'0',0),
            (4405,0,'Allow Click Delete Item PH','',0,'\\42013','\\420',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PH','/module/purchase/ph','Price Change','fa fa-tasks sub_menu_ico',4392)";
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
        $qry = "(62,0,'" . $modulename . "','',0,'\\401','$parent',0,'0',0),
        (63,0,'Allow View Transaction PO','PO',0,'\\40101','\\401',0,'0',0),
        (64,0,'Allow Click Edit Button PO','',0,'\\40102','\\401',0,'0',0),
        (65,0,'Allow Click New Button PO','',0,'\\40103','\\401',0,'0',0),
        (66,0,'Allow Click Save Button PO','',0,'\\40104','\\401',0,'0',0),
        (68,0,'Allow Click Delete Button PO','',0,'\\40106','\\401',0,'0',0),
        (69,0,'Allow Click Print Button PO','',0,'\\40107','\\401',0,'0',0),
        (70,0,'Allow Click Lock Button PO','',0,'\\40108','\\401',0,'0',0),
        (71,0,'Allow Click UnLock Button PO','',0,'\\40109','\\401',0,'0',0),
        (72,0,'Allow Change Amount PO','',0,'\\40110','\\401',0,'0',0),
        (73,0,'Allow Click Post Button PO','',0,'\\40112','\\401',0,'0',0),
        (74,0,'Allow Click UnPost  Button PO','',0,'\\40113','\\401',0,'0',0),
        (808,1,'Allow Click Add Item PO','',0,'\\40114','\\401',0,'0',0),
        (809,1,'Allow Click Edit Item PO','',0,'\\40115','\\401',0,'0',0),
        (810,1,'Allow Click Delete Item PO','',0,'\\40116','\\401',0,'0',0),
        (843,1,'Allow View Amount','',0,'\\40117','\\401',0,'0',0),
        (2548,1,'Allow Click PR Button','',0,'\\40118','\\401',0,'0',0),
        (3592,1,'Allow Void Button','',0,'\\40119','\\401',0,'0',0)";

        switch ($companyid) {
            case 16: //ati
                $qry .= ", (4009,1,'Allow Clicked Approved','',0,'\\40120','\\401',0,'0',0)";
                $qry .= ", (4122,1,'Allow Multiple Printing','',0,'\\40121','\\401',0,'0',0)";
                $qry .= ", (4164,1,'Allow Click Ordered Button','',0,'\\40122','\\401',0,'0',0)";
                $qry .= ", (4310,1,'Allow Update Posted Details','',0,'\\40123','\\401',0,'0',0)";
                $qry .= ", (4480,1,'Allow View All Warehouse','',0,'\\40124','\\401',0,'0',0)";
                $qry .= ", (4368,1,'Allow Generate Temp. Barcode','',0,'\\40125','\\401',0,'0',0)";
                break;
            case 3: //conti
                $qry .= ", (4192,1,'Allow Click Canvass Button PO','',0,'\\40123','\\401',0,'0',0)";
                break;
        }

        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
        }
        return "($sort,$p,'PO','/module/" . $folder . "/po','" . $modulename . "','fa fa-tasks sub_menu_ico',62)";
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
        $qry = "(78,0,'" . $label . "','',0,'\\402','$parent',0,'0',0),
        (79,0,'Allow View Transaction RR','RR',0,'\\40201','\\402',0,'0',0),
        (80,0,'Allow Click Edit Button RR','',0,'\\40202','\\402',0,'0',0),
        (81,0,'Allow Click New Button RR','',0,'\\40203','\\402',0,'0',0),
        (82,0,'Allow Click Save Button RR','',0,'\\40204','\\402',0,'0',0),
        (84,0,'Allow Click Delete Button RR','',0,'\\40206','\\402',0,'0',0),
        (85,0,'Allow Click Print Button RR','',0,'\\40207','\\402',0,'0',0),
        (86,0,'Allow Click Lock Button RR','',0,'\\40208','\\402',0,'0',0),
        (87,0,'Allow Click UnLock Button RR','',0,'\\40209','\\402',0,'0',0),
        (88,0,'Allow Click Post Button RR','',0,'\\40210','\\402',0,'0',0),
        (89,0,'Allow Click UnPost Button RR','',0,'\\40211','\\402',0,'0',0),
        (90,0,'Allow View Transaction accounting RR','',0,'\\40212','\\402',0,'0',0),
        (91,0,'Allow Change Amount RR','',0,'\\40213','\\402',0,'0',0),
        (811,1,'Allow Click Add Item RR','',0,'\\40214','\\402',0,'0',0),
        (812,1,'Allow Click Edit Item RR','',0,'\\40215','\\402',0,'0',0),
        (813,1,'Allow Click Delete Item RR','',0,'\\40216','\\402',0,'0',0)";

        $systype = $this->companysetup->getsystemtype($params);
        if ($systype == 'CAIMS') {
            $qry = $qry . ",(2232,1,'Allow View All transaction RR','',0,'\\40217','\\402',0,'0',0)";
        }

        switch ($params['companyid']) {
            case 3: //conti
                $qry .= ", (2728,1,'Allow Click Received','',0,'\\40218','\\402',0,'0',0),
                       (2729,1,'Allow Click Unreceived','',0,'\\40219','\\402',0,'0',0)";
                break;
            case 10: //afti
                $qry .= ", (3577,1,'Allow Click Make Payment','',0,'\\40218','\\402',0,'0',0)";
                break;
            case 16: //ati
                $qry .= ", (3619,1,'Allow Generate Asset Tag','',0,'\\40220','\\402',0,'0',0)";
                $qry .= ", (4031,1,'Allow View All WH','',0,'\\40221','\\402',0,'0',0)";
                $qry .= ", (4140,1,'Allow Click for Checking Button','',0,'\\40222','\\402',0,'0',0)";
                break;
            case 8: //maxipro
                $qry .= ", (4449,1,'Allow Update Transaction Type','',0,'\\40218','\\402',0,'0',0)";
                break;
            case 43: //mighty
                $qry .= ", (4482,1,'Allow Access Tripping Tab','',0,'\\40217','\\402',0,'0',0)";
                $qry .= ", (4483,1,'Allow Access Arrived Tab','',0,'\\40218','\\402',0,'0',0)";
                $qry .= ", (4484,1,'Allow Trip Approved','',0,'\\40219','\\402',0,'0',0)";
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
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'RR','/module/" . $folder . "/rr','" . $label . "','fa fa-people-carry sub_menu_ico',78)";
    } //end function

    public function sn($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2239,0,'Supplier Invoice','',0,'\\406','$parent',0,'0',0),
        (2240,0,'Allow View Transaction','SN',0,'\\40601','\\406',0,'0',0),
        (2241,0,'Allow Click Edit Button','',0,'\\40602','\\406',0,'0',0),
        (2242,0,'Allow Click New Button','',0,'\\40603','\\406',0,'0',0),
        (2243,0,'Allow Click Save Button','',0,'\\40604','\\406',0,'0',0),
        (2245,0,'Allow Click Delete Button','',0,'\\40606','\\406',0,'0',0),
        (2246,0,'Allow Click Print Button','',0,'\\40607','\\406',0,'0',0),
        (2247,0,'Allow Click Lock Button','',0,'\\40608','\\406',0,'0',0),
        (2248,0,'Allow Click UnLock Button','',0,'\\40609','\\406',0,'0',0),
        (2249,0,'Allow Click Post Button','',0,'\\40610','\\406',0,'0',0),
        (2250,0,'Allow Click UnPost Button','',0,'\\40611','\\406',0,'0',0),
        (2251,0,'Allow View Transaction accounting','',0,'\\40612','\\406',0,'0',0),
        (2252,1,'Allow Click Add RR','',0,'\\40613','\\406',0,'0',0),
        (2253,1,'Allow Click Delete RR','',0,'\\40614','\\406',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'SN','/module/purchase/sn','Supplier Invoice','fa fa-file-invoice sub_menu_ico',2239)";
    } //end function


    public function dm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(97,0,'Purchase Return','',0,'\\403','$parent',0,'0',0),
        (98,0,'Allow View Transaction DM','DM',0,'\\40301','\\403',0,'0',0),
        (99,0,'Allow Click Edit Button DM','',0,'\\40302','\\403',0,'0',0),
        (100,0,'Allow Click New Button DM','',0,'\\40303','\\403',0,'0',0),
        (101,0,'Allow Click Save Button DM','',0,'\\40304','\\403',0,'0',0),
        (103,0,'Allow Click Delete Button DM','',0,'\\40306','\\403',0,'0',0),
        (104,0,'Allow Click Print Button DM','',0,'\\40307','\\403',0,'0',0),
        (105,0,'Allow Click Lock Button DM','',0,'\\40308','\\403',0,'0',0),
        (106,0,'Allow Click UnLock Button DM','',0,'\\40309','\\403',0,'0',0),
        (107,0,'Allow Click Post Button DM','',0,'\\40310','\\403',0,'0',0),
        (108,0,'Allow Click UnPost Button DM','',0,'\\40311','\\403',0,'0',0),
        (109,0,'Allow View Transaction accounting DM','',0,'\\40312','\\403',0,'0',0),
        (110,0,'Allow Change Amount DM','',0,'\\40313','\\403',0,'0',0),
        (820,1,'Allow Click Add Item DM','',0,'\\40314','\\403',0,'0',0),
        (821,1,'Allow Click Edit Item DM','',0,'\\40315','\\403',0,'0',0),
        (822,1,'Allow Click Delete Item DM','',0,'\\40316','\\403',0,'0',0)";

        $systype = $this->companysetup->getsystemtype($params);
        if ($systype == 'CAIMS') {
            $qry = $qry . ",(2233,1,'Allow View All transaction DM','',0,'\\40317','\\403',0,'0',0)";
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
        return "($sort,$p,'DM','/module/" . $folder . "/dm','Purchase Return','fa fa-retweet sub_menu_ico',97)";
    } //end function

    public function pr($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(618,0,'Purchase Requisition','',0,'\\404','$parent',0,'0',0),
        (619,0,'Allow View Transaction PR','PR',0,'\\40401','\\404',0,'0',0),
        (620,0,'Allow Click Edit Button PR','',0,'\\40402','\\404',0,'0',0),
        (621,0,'Allow Click New Button PR','',0,'\\40403','\\404',0,'0',0),
        (622,0,'Allow Click Save Button PR','',0,'\\40404','\\404',0,'0',0),
        (624,0,'Allow Click Delete Button PR','',0,'\\40406','\\404',0,'0',0),
        (625,0,'Allow Click Print Button PR','',0,'\\40407','\\404',0,'0',0),
        (626,0,'Allow Click Lock Button PR','',0,'\\40408','\\404',0,'0',0),
        (627,0,'Allow Click UnLock Button PR','',0,'\\40409','\\404',0,'0',0),
        (630,0,'Allow Click Post Button PR','',0,'\\40410','\\404',0,'0',0),
        (631,0,'Allow Click UnPost Button PR','',0,'\\40411','\\404',0,'0',0),
        (628,0,'Allow Change Amount PR','',0,'\\40413','\\404',0,'0',0),
        (814,1,'Allow Click Add Item PR','',0,'\\40414','\\404',0,'0',0),
        (815,1,'Allow Click Edit Item PR','',0,'\\40415','\\404',0,'0',0),
        (816,1,'Allow Click Delete Item PR','',0,'\\40416','\\404',0,'0',0),
        (3601,1,'Allow Void Button','',0,'\\40418','\\404',0,'0',0)";

        switch ($companyid) {
            case 10: //afti 
            case 12: //afti usd
                $qry .= ",(2873,1,'Allow Click Make PO Button','',0,'\\40417','\\404',0,'0',0)";
                $qry .= ",(3984,1,'Allow Click Make JO Button','',0,'\\40419','\\404',0,'0',0)";
                break;
            case 16: //ati
                $qry .= ",(3868,1,'Allow View All','',0,'\\40420','\\404',0,'0',0)";
                $qry .= ",(4029,1,'Allow Update Posted Details','',0,'\\40421','\\404',0,'0',0)";
                $qry .= ",(4190,1,'Allow Edit Colors','',0,'\\40422','\\404',0,'0',0)";
                break;
            case 40: //cdo
                $qry .= ",(4453,1,'Allow View all Branch PR','',0,'\\40423','\\404',0,'0',0)";
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
        }

        return "($sort,$p,'PR','/module/" . $folder . "/pr','Purchase Requisition','fa fa-list sub_menu_ico',618)";
    } //end function

    public function cd($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1427,0,'Canvass Sheet','',0,'\\405','$parent',0,'0',0),
        (1428,0,'Allow View Canvass Sheet','',0,'\\40501','\\405',0,'0',0),
        (1429,0,'Allow Click Edit Button Canvass Sheet','',0,'\\40502','\\405',0,'0',0),
        (1430,0,'Allow Click New Button Canvass Sheet','',0,'\\40503','\\405',0,'0',0),
        (1431,0,'Allow Click Save Button Canvass Sheet','',0,'\\40504','\\405',0,'0',0),
        (1432,0,'Allow Click Change Canvass Sheet','',0,'\\40505','\\405',0,'0',0),
        (1433,0,'Allow Click Delete Button Canvass Sheet','',0,'\\40506','\\405',0,'0',0),
        (1434,0,'Allow Click Print Button Canvass Sheet','',0,'\\40507','\\405',0,'0',0),
        (1435,0,'Allow Click Lock Button Canvass Sheet','',0,'\\40508','\\405',0,'0',0),
        (1436,0,'Allow Click UnLock Button Canvass Sheet','',0,'\\40509','\\405',0,'0',0),
        (1437,0,'Allow Change Amount Canvass Sheet','',0,'\\40510','\\405',0,'0',0),
        (1438,0,'Allow Click Post Button Canvass Sheet','',0,'\\40512','\\405',0,'0',0),
        (1439,0,'Allow Click UnPost  Button Canvass Sheet','',0,'\\40513','\\405',0,'0',0),
        (1440,1,'Allow Click Add Item Canvass Sheet','',0,'\\40514','\\405',0,'0',0),
        (1441,1,'Allow Click Edit Item Canvass Sheet','',0,'\\40515','\\405',0,'0',0),
        (1442,1,'Allow Click Delete Item Canvass Sheet','',0,'\\40516','\\405',0,'0',0),
        (1447,0,'Canvass Approval','',0,'\\40517','\\405',0,'0',0),
        (3600,0,'Allow Void Button','',0,'\\40518','\\405',0,'0',0),
        (3767,0,'Administrator','',0,'\\40519','\\405',0,'0',0)";

        if ($companyid == 16) { //ati
            $qry .= "
            ,(4008,0,'Approved Canvass','',0,'\\40520','\\405',0,'0',0)
            ,(4010,0,'Allow Click Done Checking','',0,'\\40521','\\405',0,'0',0)
            ,(4102,0,'Allow Click For Revision','',0,'\\40522','\\405',0,'0',0)
            ,(4166,0,'View Dashboard - Canvass for PO','',0,'\\40523','\\405',0,'0',0)
            ,(4218,0,'Waived Request Qty','',0,'\\40524','\\405',0,'0',0)
            ,(4481,0,'Allow Update Posted Details','',0,'\\40525','\\405',0,'0',0)";
        }

        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
        }

        return "($sort,$p,'CD','/module/" . $folder . "/cd','Canvass Sheet','fa fa-list sub_menu_ico',1427)";
    } //end function

    public function cd2($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'CD2','/actionlisting/actionlisting/canvassapproval','Canvass Approval','fa fa-check-double sub_menu_ico',1447)";
    } //end function

    public function cd3($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'CD3','/actionlisting/actionlisting/canvassapproval2','Approved Canvass','fa fa-check-double sub_menu_ico',4008)";
    } //end function

    public function sr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2643,0,'Service Receiving','',0,'\\408','$parent',0,'0',0) ,
        (2644,0,'Allow View Transaction SR','SR',0,'\\40801','\\408',0,'0',0) ,
        (2645,0,'Allow Click Edit Button SR','',0,'\\40802','\\408',0,'0',0) ,
        (2646,0,'Allow Click New  Button SR','',0,'\\40803','\\408',0,'0',0) ,
        (2647,0,'Allow Click Save Button SR','',0,'\\40804','\\408',0,'0',0) ,
        (2649,0,'Allow Click Delete Button SR','',0,'\\40806','\\408',0,'0',0) ,
        (2650,0,'Allow Click Print Button SR','',0,'\\40807','\\408',0,'0',0) ,
        (2651,0,'Allow Click Lock Button SR','',0,'\\40808','\\408',0,'0',0) ,
        (2652,0,'Allow Click UnLock Button SR','',0,'\\40809','\\408',0,'0',0) ,
        (2653,0,'Allow Click Post Button SR','',0,'\\40810','\\408',0,'0',0) ,
        (2654,0,'Allow Click UnPost  Button SR','',0,'\\40811','\\408',0,'0',0),
        (2655,0,'Allow Click Change Amount SR','',0,'\\40812','\\408',0,'0',0) ,
        (2656,0,'Allow Click Add Item SR','',0,'\\40813','\\408',0,'0',0) ,
        (2657,0,'Allow Click Edit Item SR','',0,'\\40814','\\408',0,'0',0) ,
        (3618,0,'Allow Edit Insurance','',0,'\\40816','\\408',0,'0',0) ,
        (2658,0,'Allow Click Delete Item SR','',0,'\\40815','\\408',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SR','/module/purchase/sr','Service Receiving','fa fa-people-carry sub_menu_ico',2643)";
    } //end function

    public function jb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2596,0,'Job Order','',0,'\\409','$parent',0,'0',0),
        (2597,0,'Allow View Transaction JB','JB',0,'\\40901','\\409',0,'0',0),
        (2598,0,'Allow Click Edit Button JB','',0,'\\40902','\\409',0,'0',0),
        (2599,0,'Allow Click New Button JB','',0,'\\40903','\\409',0,'0',0),
        (2600,0,'Allow Click Save Button JB','',0,'\\40904','\\409',0,'0',0),
        (2602,0,'Allow Click Delete Button JB','',0,'\\40906','\\409',0,'0',0),
        (2603,0,'Allow Click Print Button JB','',0,'\\40907','\\409',0,'0',0),
        (2604,0,'Allow Click Lock Button JB','',0,'\\40908','\\409',0,'0',0),
        (2605,0,'Allow Click UnLock Button JB','',0,'\\40909','\\409',0,'0',0),
        (2606,0,'Allow Change Amount JB','',0,'\\40910','\\409',0,'0',0),
        (2607,0,'Allow Click Post Button JB','',0,'\\40911','\\409',0,'0',0),
        (2608,0,'Allow Click UnPost  Button JB','',0,'\\40912','\\409',0,'0',0),
        (2609,1,'Allow Click Add Item JB','',0,'\\40913','\\409',0,'0',0),
        (2610,1,'Allow Click Edit Item JB','',0,'\\40914','\\409',0,'0',0),
        (2611,1,'Allow Click Delete Item JB','',0,'\\40915','\\409',0,'0',0),
        (4003,1,'Allow Click PR Button','',0,'\\40917','\\409',0,'0',0),
        (2612,1,'Allow View Amount JB','',0,'\\40916','\\409',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JB','/module/purchase/jb','Job Order','fa fa-tasks sub_menu_ico',2596)";
    } //end function

    public function ac($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2613,0,'Job Completion','',0,'\\410','$parent',0,'0',0),
        (2614,0,'Allow View Transaction AC','AC',0,'\\41001','\\410',0,'0',0),
        (2615,0,'Allow Click Edit Button AC','',0,'\\41002','\\410',0,'0',0),
        (2616,0,'Allow Click New Button AC','',0,'\\41003','\\410',0,'0',0),
        (2617,0,'Allow Click Save Button AC','',0,'\\41004','\\410',0,'0',0),
        (2619,0,'Allow Click Delete Button AC','',0,'\\41006','\\410',0,'0',0),
        (2620,0,'Allow Click Print Button AC','',0,'\\41007','\\410',0,'0',0),
        (2621,0,'Allow Click Lock Button AC','',0,'\\41008','\\410',0,'0',0),
        (2622,0,'Allow Click UnLock Button AC','',0,'\\41009','\\410',0,'0',0),
        (2623,0,'Allow Click Post Button AC','',0,'\\41010','\\410',0,'0',0),
        (2624,0,'Allow Click UnPost Button AC','',0,'\\41011','\\410',0,'0',0),
        (2625,0,'Allow View Transaction accounting AC','',0,'\\41012','\\410',0,'0',0),
        (2626,0,'Allow Change Amount AC','',0,'\\41013','\\410',0,'0',0),
        (2627,1,'Allow Click Add Item AC','',0,'\\41014','\\410',0,'0',0),
        (2628,1,'Allow Click Edit Item AC','',0,'\\41015','\\410',0,'0',0),
        (2629,1,'Allow Click Delete Item AC','',0,'\\41016','\\410',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'AC','/module/purchase/ac','Job Completion','fa fa-check-double sub_menu_ico',2613)";
    } //end function

    public function te($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2672,0,'Task/Errand','',0,'\\411','$parent',0,'0',0),
        (2673,0,'Allow View Transaction TE','TE',0,'\\41101','\\411',0,'0',0),
        (2674,0,'Allow Click Edit Button TE','',0,'\\41102','\\411',0,'0',0),
        (2675,0,'Allow Click New Button TE','',0,'\\41103','\\411',0,'0',0),
        (2676,0,'Allow Click Save Button TE','',0,'\\41104','\\411',0,'0',0),
        (2678,0,'Allow Click Delete Button TE','',0,'\\41106','\\411',0,'0',0),
        (2679,0,'Allow Click Print Button TE','',0,'\\41107','\\411',0,'0',0),
        (2680,0,'Allow Click Lock Button TE','',0,'\\41108','\\411',0,'0',0),
        (2681,0,'Allow Click UnLock Button TE','',0,'\\41109','\\411',0,'0',0),
        (2682,0,'Allow Change Amount TE','',0,'\\41110','\\411',0,'0',0),
        (2683,0,'Allow Click Post Button TE','',0,'\\41112','\\411',0,'0',0),
        (2684,0,'Allow Click UnPost  Button TE','',0,'\\41113','\\411',0,'0',0),
        (2685,1,'Allow Click Add Item TE','',0,'\\41114','\\411',0,'0',0),
        (2686,1,'Allow Click Edit Item TE','',0,'\\41115','\\411',0,'0',0),
        (2687,1,'Allow Click Delete Item TE','',0,'\\41116','\\411',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'TE','/module/sales/te','Task/Errand','fa fa-tasks sub_menu_ico',2672)";
    } //end function


    public function parentinventory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(556,0,'INVENTORY','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'INVENTORY',$sort,'fa fa-box-open',',IS,PC,AJ,TS,VA')";
    } //end function

    public function pc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(275,0,'Physical Count','',0,'\\602','$parent',0,'0',0) ,
        (276,0,'Allow View Transaction PC','',0,'\\60201','\\602',0,'0',0) ,
        (277,0,'Allow Click Edit Button  PC','',0,'\\60202','\\602',0,'0',0) ,
        (278,0,'Allow Click New Button PC','',0,'\\60203','\\602',0,'0',0) ,
        (279,0,'Allow Click Save Button PC','',0,'\\60204','\\602',0,'0',0) ,
        (280,0,'Allow Adjust PC','',0,'\\60205','\\602',0,'0',0) ,
        (281,0,'Allow Click Delete Button PC','',0,'\\60206','\\602',0,'0',0) ,
        (282,0,'Allow Click Print Button PC','',0,'\\60207','\\602',0,'0',0) ,
        (283,0,'Allow Click Lock Button PC','',0,'\\60208','\\602',0,'0',0) ,
        (284,0,'Allow Click UnLock Button PC','',0,'\\60209','\\602',0,'0',0) ,
        (285,0,'Allow Click Post Button PC','',0,'\\60210','\\602',0,'0',0) ,
        (286,0,'Allow Click UnPost Button PC','',0,'\\60211','\\602',0,'0',0) ,
        (837,1,'Allow Click Delete Item PC','',0,'\\60214','\\602',0,'0',0) ,
        (836,1,'Allow Click Edit Item PC','',0,'\\60213','\\602',0,'0',0) ,
        (835,1,'Allow Click Add Item PC','',0,'\\60212','\\602',0,'0',0) ,
        (838,1,'Allow Change Amount PC','',0,'\\60215','\\602',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PC','/module/inventory/pc','Physical Count','fa fa-list-ol sub_menu_ico',275)";
    } //end function

    public function aj($params, $parent, $sort)
    {
        $folder = 'inventory';
        if ($params['companyid'] == 39) $folder = 'cbbsi'; //cbbsi
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(290,0,'Inventory Adjustment','',0,'\\603','$parent',0,'0',0) ,
        (291,0,'Allow View Transaction AJ','AJ',0,'\\60301','\\603',0,'0',0) ,
        (292,0,'Allow Click Edit Button  AJ','',0,'\\60302','\\603',0,'0',0) ,
        (293,0,'Allow Click New Button AJ','',0,'\\60303','\\603',0,'0',0) ,
        (294,0,'Allow Click Save Button AJ','',0,'\\60304','\\603',0,'0',0) ,
        (296,0,'Allow Click Delete Button AJ','',0,'\\60306','\\603',0,'0',0) ,
        (297,0,'Allow Click Print Button AJ','',0,'\\60307','\\603',0,'0',0) ,
        (298,0,'Allow Click Lock Button AJ','',0,'\\60308','\\603',0,'0',0) ,
        (299,0,'Allow Click UnLock Button AJ','',0,'\\60309','\\603',0,'0',0) ,
        (300,0,'Allow Click Post Button AJ','',0,'\\60310','\\603',0,'0',0) ,
        (301,0,'Allow Click UnPost Button AJ','',0,'\\60311','\\603',0,'0',0) ,
        (302,0,'Allow View Transaction Accounting AJ','',0,'\\60312','\\603',0,'0',0) ,
        (823,1,'Allow Click Add Item AJ','',0,'\\60313','\\603',0,'0',0) ,
        (824,1,'Allow Click Edit Item AJ','',0,'\\60314','\\603',0,'0',0) ,
        (825,1,'Allow Click Delete Item AJ','',0,'\\60315','\\603',0,'0',0) ,
        (826,1,'Allow Change Amount AJ','',0,'\\60316','\\603',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AJ','/module/" . $folder . "/aj','Inventory Adjustment','fa fa-exchange-alt sub_menu_ico',290)";
    } //end function


    public function ts($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(308,0,'Transfer Slip','',0,'\\604','$parent',0,'0',0) ,
        (309,0,'Allow View Transaction TS','TS',0,'\\60401','\\604',0,'0',0) ,
        (310,0,'Allow Click Edit Button  TS','',0,'\\60402','\\604',0,'0',0) ,
        (311,0,'Allow Click New Button TS','',0,'\\60403','\\604',0,'0',0) ,
        (312,0,'Allow Click Save Button TS','',0,'\\60404','\\604',0,'0',0) ,
        (314,0,'Allow Click Delete Button TS','',0,'\\60406','\\604',0,'0',0) ,
        (315,0,'Allow Click Print Button TS','',0,'\\60407','\\604',0,'0',0) ,
        (316,0,'Allow Click Lock Button TS','',0,'\\60408','\\604',0,'0',0) ,
        (317,0,'Allow Click UnLock Button TS','',0,'\\60409','\\604',0,'0',0) ,
        (318,0,'Allow Click Post Button TS','',0,'\\60410','\\604',0,'0',0) ,
        (319,0,'Allow Click UnPost Button TS','',0,'\\60411','\\604',0,'0',0) ,
        (831,1,'Allow Click Add Item TS','',0,'\\60412','\\604',0,'0',0) ,
        (832,1,'Allow Click Edit Item TS','',0,'\\60413','\\604',0,'0',0) ,
        (833,1,'Allow Click Delete Item TS','',0,'\\60414','\\604',0,'0',0) ,
        (834,1,'Allow Change Amount TS','',0,'\\60415','\\604',0,'0',0),
        (3719,1,'Allow View Dashbaord Incoming Deliveries TS','',0,'\\60416','\\604',0,'0',0)";

        switch ($params['companyid']) {
            case 43: //mighty
                $qry .= ", (4492,1,'Allow Access Tripping Tab','',0,'\\60417','\\604',0,'0',0)";
                $qry .= ", (4493,1,'Allow Access Dispatch Tab','',0,'\\60418','\\604',0,'0',0)";
                $qry .= ", (4496,1,'Allow Trip Approved','',0,'\\60419','\\604',0,'0',0)";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'TS','/module/inventory/ts','Transfer Slip','fa fa-dolly-flatbed sub_menu_ico',308)";
    } //end function

    public function is($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(257,0,'Inventory Setup','',0,'\\601','$parent',0,'0',0) ,
        (258,0,'Allow View Transaction IS','IS',0,'\\60101','\\601',0,'0',0) ,
        (259,0,'Allow Click Edit Button  IS','',0,'\\60102','\\601',0,'0',0) ,
        (260,0,'Allow Click New Button IS','',0,'\\60103','\\601',0,'0',0) ,
        (261,0,'Allow Click Save Button IS','',0,'\\60104','\\601',0,'0',0) ,
        (263,0,'Allow Click Delete Button IS','',0,'\\60106','\\601',0,'0',0) ,
        (264,0,'Allow Click Print Button IS','',0,'\\60107','\\601',0,'0',0) ,
        (265,0,'Allow Click Lock Button IS','',0,'\\60108','\\601',0,'0',0) ,
        (266,0,'Allow Click UnLock Button IS','',0,'\\60109','\\601',0,'0',0) ,
        (267,0,'Allow Click Post Button IS','',0,'\\60110','\\601',0,'0',0) ,
        (268,0,'Allow Click UnPost Button IS','',0,'\\60111','\\601',0,'0',0) ,
        (269,0,'Allow View Transaction Accounting IS','',0,'\\60112','\\601',0,'0',0) ,
        (827,1,'Allow Click Add Item IS','',0,'\\60113','\\601',0,'0',0) ,
        (828,1,'Allow Click Edit Item IS','',0,'\\60114','\\601',0,'0',0) ,
        (829,1,'Allow Click Delete Item IS','',0,'\\60115','\\601',0,'0',0) ,
        (830,1,'Allow Change Amount IS','',0,'\\60116','\\601',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'IS','/module/inventory/is','Inventory Setup','fa fa-truck-loading sub_menu_ico',257)";
    } //end function

    public function va($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2204,0,'Voyage Report','',0,'\\605','$parent',0,'0',0) ,
        (2205,0,'Allow View Transaction VA','',0,'\\60501','\\605',0,'0',0) ,
        (2206,0,'Allow Click Edit Button  VA','',0,'\\60502','\\605',0,'0',0) ,
        (2207,0,'Allow Click New Button VA','',0,'\\60503','\\605',0,'0',0) ,
        (2208,0,'Allow Click Save Button VA','',0,'\\60504','\\605',0,'0',0) ,
        (2210,0,'Allow Click Delete Button VA','',0,'\\60506','\\605',0,'0',0) ,
        (2211,0,'Allow Click Print Button VA','',0,'\\60507','\\605',0,'0',0) ,
        (2212,0,'Allow Click Lock Button VA','',0,'\\60508','\\605',0,'0',0) ,
        (2213,0,'Allow Click UnLock Button VA','',0,'\\60509','\\605',0,'0',0) ,
        (2214,0,'Allow Click Post Button VA','',0,'\\60510','\\605',0,'0',0) ,
        (2215,0,'Allow Click UnPost Button VA','',0,'\\60511','\\605',0,'0',0)";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'VOYAGEREPORT','/module/inventory/va','Voyage Report','fa fa-chalkboard-teacher sub_menu_ico',2204)";
    } //end function

    public function parentpayable($params, $parent, $sort)
    {
        $p = $parent;
        $companyid = $params['companyid'];
        $parent = '\\' . $parent;
        $qry = "(547,0,'PAYABLE','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            return "insert into left_parent(id,name,seq,class,doc) values($p,'PAYABLES',$sort,'fa fa-university',',AP,PV,CV')";
        } elseif ($companyid == 8) { //maxipro
            return "insert into left_parent(id,name,seq,class,doc) values($p,'PAYABLES',$sort,'fa fa-university',',AP,PV,CV')";
        } else {
            return "insert into left_parent(id,name,seq,class,doc) values($p,'PAYABLES',$sort,'fa fa-university',',AP,PV,CV,PQ,SV,CHECKRELEASE')";
        }
    } //end function

    public function ap($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(133,0,'Payable Setup','',0,'\\201','$parent',0,'0',0) ,
        (134,0,'Allow View Transaction AP','AP',0,'\\20101','\\201',0,'0',0) ,
        (135,0,'Allow Click Edit Button  AP','',0,'\\20102','\\201',0,'0',0) ,
        (136,0,'Allow Click New Button AP','',0,'\\20103','\\201',0,'0',0) ,
        (137,0,'Allow Click Save Button AP','',0,'\\20104','\\201',0,'0',0) ,
        (139,0,'Allow Click Delete Button AP','',0,'\\20106','\\201',0,'0',0) ,
        (140,0,'Allow Click Print Button AP','',0,'\\20107','\\201',0,'0',0) ,
        (141,0,'Allow Click Lock Button AP','',0,'\\20108','\\201',0,'0',0) ,
        (142,0,'Allow Click UnLock Button AP','',0,'\\20109','\\201',0,'0',0) ,
        (143,0,'Allow Click Post Button AP','',0,'\\20110','\\201',0,'0',0) ,
        (144,0,'Allow Click UnPost Button AP','',0,'\\20111','\\201',0,'0',0) ,
        (145,0,'Allow Click Add Account AP','',0,'\\20112','\\201',0,'0',0) ,
        (146,0,'Allow Click Edit Account AP','',0,'\\20113','\\201',0,'0',0) ,
        (147,0,'Allow Click Delete Account AP','',0,'\\20114','\\201',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AP','/module/payable/ap','AP Setup','fa fa-coins sub_menu_ico',133)";
    } //end function

    public function pv($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(370,0,'Accounts Payable Voucher','',0,'\\202','$parent',0,'0',0) ,
        (371,0,'Allow View Transaction PV','APV',0,'\\20201','\\202',0,'0',0) ,
        (372,0,'Allow Click Edit Button  PV','',0,'\\20202','\\202',0,'0',0) ,
        (373,0,'Allow Click New Button PV','',0,'\\20203','\\202',0,'0',0) ,
        (374,0,'Allow Click Save Button PV','',0,'\\20204','\\202',0,'0',0) ,
        (376,0,'Allow Click Delete Button PV','',0,'\\20206','\\202',0,'0',0) ,
        (377,0,'Allow Click Print Button PV','',0,'\\20207','\\202',0,'0',0) ,
        (378,0,'Allow Click Lock Button PV','',0,'\\20208','\\202',0,'0',0) ,
        (379,0,'Allow Click UnLock Button PV','',0,'\\20209','\\202',0,'0',0) ,
        (380,0,'Allow Click Post Button PV','',0,'\\20210','\\202',0,'0',0) ,
        (381,0,'Allow Click UnPost Button PV','',0,'\\20211','\\202',0,'0',0) ,
        (382,0,'Allow Click Add Account PV','',0,'\\20212','\\202',0,'0',0) ,
        (383,0,'Allow Click Edit Account PV','',0,'\\20213','\\202',0,'0',0) ,
        (384,0,'Allow Click Delete Account PV','',0,'\\20214','\\202',0,'0',0) ";

        if ($params['companyid'] == 10) { //afti
            $qry .= ", (3579,1,'Allow Click Make Payment','',0,'\\20215','\\202',0,'0',0)";
            $qry .= ", (3591,1,'Allow Add Item','',0,'\\20216','\\202',0,'0',0)";
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'PV','/module/payable/pv','Accounts Payable Voucher','fa fa-credit-card sub_menu_ico',370)";
    } //end function


    public function cv($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(116,0,'Cash/Check Voucher','',0,'\\203','$parent',0,'0',0) ,
        (117,0,'Allow View Transaction CV','CV',0,'\\20301','\\203',0,'0',0) ,
        (118,0,'Allow Click Edit Button  CV','',0,'\\20302','\\203',0,'0',0) ,
        (119,0,'Allow Click New Button CV','',0,'\\20303','\\203',0,'0',0) ,
        (120,0,'Allow Click Save Button CV','',0,'\\20304','\\203',0,'0',0) ,
        (122,0,'Allow Click Delete Button CV','',0,'\\20306','\\203',0,'0',0) ,
        (123,0,'Allow Click Print Button CV','',0,'\\20307','\\203',0,'0',0) ,
        (124,0,'Allow Click Lock Button CV','',0,'\\20308','\\203',0,'0',0) ,
        (125,0,'Allow Click UnLock Button CV','',0,'\\20309','\\203',0,'0',0) ,
        (126,0,'Allow Click Post Button CV','',0,'\\20310','\\203',0,'0',0) ,
        (127,0,'Allow Click UnPost Button CV','',0,'\\20311','\\203',0,'0',0) ,
        (128,0,'Allow Click Add Account CV','',0,'\\20312','\\203',0,'0',0) ,
        (129,0,'Allow Click Edit Account CV','',0,'\\20313','\\203',0,'0',0) ,
        (130,0,'Allow Click Delete Account CV','',0,'\\20314','\\203',0,'0',0)";

        switch ($params['companyid']) {
            case 16: //ati
                $qry .= ",(3985,0,'Allow Click Approved CV','',0,'\\20315','\\203',0,'0',0),
                        (3986,0,'Allow Click Initial Checking CV','',0,'\\20316','\\203',0,'0',0),
                        (3987,0,'Allow Click Final Checking CV','',0,'\\20317','\\203',0,'0',0),
                        (3988,0,'Allow Click Payment Released CV','',0,'\\20318','\\203',0,'0',0),
                        (3989,0,'Allow Click Forwarded to Encoder CV','',0,'\\20319','\\203',0,'0',0),
                        (3990,0,'Allow Click Forwarded to WH CV','',0,'\\20320','\\203',0,'0',0),
                        (3991,0,'Allow Click Items Collected CV','',0,'\\20321','\\203',0,'0',0),
                        (3992,0,'Allow Click Forwarded to OP CV','',0,'\\20322','\\203',0,'0',0),
                        (3993,0,'Allow Click Forwarded to Asset CV','',0,'\\20323','\\203',0,'0',0),
                        (3994,0,'Allow Click For Liquidation CV','',0,'\\20324','\\203',0,'0',0),
                        (3995,0,'Allow Click Forwarded to Acctg CV','',0,'\\20325','\\203',0,'0',0),
                        (3996,0,'Allow Click For Checking CV','',0,'\\20326','\\203',0,'0',0),
                        (3997,0,'Allow Click Check Issued CV','',0,'\\20327','\\203',0,'0',0),
                        (3998,0,'Allow Click Paid CV','',0,'\\20328','\\203',0,'0',0),
                        (3999,0,'Allow Click Checked CV','',0,'\\20329','\\203',0,'0',0),
                        (4000,0,'Allow Click Advances Clearead CV','',0,'\\20330','\\203',0,'0',0),
                        (4001,0,'Allow Click SOA Received CV','',0,'\\20331','\\203',0,'0',0),
                        (4002,0,'Allow Click For Posting CV','',0,'\\20332','\\203',0,'0',0),
                        (4004,0,'Allow Click For Revision CV','',0,'\\20333','\\203',0,'0',0),
                        (4143,0,'Allow Edit Amount CV','',0,'\\20334','\\203',0,'0',0),
                        (4144,0,'Allow Edit Approved CV','',0,'\\20335','\\203',0,'0',0),
                        (4195,0,'Allow Generate Surcharge','',0,'\\20336','\\203',0,'0',0),
                        (4196,0,'Allow Void Entry CV','',0,'\\20337','\\203',0,'0',0),
                        (4387,0,'Admin','',0,'\\20338','\\203',0,'0',0),
                        (4406,0,'Allow Remove Tagging Payment Released','',0,'\\20339','\\203',0,'0',0)";
                break;
            case 39: //cbbsi
                $qry .= ",(4391,0,'Allow Update Release','',0,'\\20315','\\203',0,'0',0)";
                break;
        }



        $this->insertattribute($params, $qry);

        $folder = 'payable';
        switch ($params['companyid']) {
            case 16: //ati
                $folder = 'ati';
                break;
        }

        return "($sort,$p,'CV','/module/" . $folder . "/cv','Cash/Check Voucher','fa fa-money-check-alt sub_menu_ico',116)";
    } //end function


    public function checkrelease($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4024,0,'Check Releasing','',0,'\\210','$parent',0,'0',0) ,
        (4025,0,'Allow View Transaction','CHECKRELEASE',0,'\\21001','\\210',0,'0',0) ,
        (4026,0,'Allow Click Release Button','',0,'\\21002','\\210',0,'0',0),
        (4027,0,'Allow Click Print Button','',0,'\\21003','\\210',0,'0',0)";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'CHECKRELEASE','/headtable/payable/checkrelease','Check Releasing','fa fa-file-invoice-dollar sub_menu_ico',4024)";
    } //end function

    public function pq($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2060,0,'Petty Cash Request','',0,'\\204','$parent',0,'0',0) ,
        (2061,0,'Allow View Transaction PCR','PCR',0,'\\20401','\\204',0,'0',0) ,
        (2062,0,'Allow Click Edit Button  PCR','',0,'\\20402','\\204',0,'0',0) ,
        (2063,0,'Allow Click New Button PCR','',0,'\\20403','\\204',0,'0',0) ,
        (2064,0,'Allow Click Save Button PCR','',0,'\\20404','\\204',0,'0',0) ,
        (2066,0,'Allow Click Delete Button PCR','',0,'\\20406','\\204',0,'0',0) ,
        (2067,0,'Allow Click Print Button PCR','',0,'\\20407','\\204',0,'0',0) ,
        (2068,0,'Allow Click Lock Button PCR','',0,'\\20408','\\204',0,'0',0) ,
        (2069,0,'Allow Click UnLock Button PCR','',0,'\\20409','\\204',0,'0',0) ,
        (2070,0,'Allow Click Post Button PCR','',0,'\\20410','\\204',0,'0',0) ,
        (2071,0,'Allow Click UnPost Button PCR','',0,'\\20411','\\204',0,'0',0) ,
        (2072,0,'Allow Click Add Account PCR','',0,'\\20412','\\204',0,'0',0) ,
        (2073,0,'Allow Click Edit Account PCR','',0,'\\20413','\\204',0,'0',0) ,
        (2074,0,'Allow Click Delete Account PCR','',0,'\\20414','\\204',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PQ','/module/payable/pq','Petty Cash Request','fa fa-money-check-alt sub_menu_ico',2060)";
    } //end function

    public function sv($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2075,0,'Petty Cash Voucher','',0,'\\205','$parent',0,'0',0) ,
       (2076,0,'Allow View Transaction PCV','PCV',0,'\\20501','\\205',0,'0',0) ,
       (2077,0,'Allow Click Edit Button  PCV','',0,'\\20502','\\205',0,'0',0) ,
       (2078,0,'Allow Click New Button PCV','',0,'\\20503','\\205',0,'0',0) ,
       (2079,0,'Allow Click Save Button PCV','',0,'\\20504','\\205',0,'0',0) ,
       (2081,0,'Allow Click Delete Button PCV','',0,'\\20506','\\205',0,'0',0) ,
       (2082,0,'Allow Click Print Button PCV','',0,'\\20507','\\205',0,'0',0) ,
       (2083,0,'Allow Click Lock Button PCV','',0,'\\20508','\\205',0,'0',0) ,
       (2084,0,'Allow Click UnLock Button PCV','',0,'\\20509','\\205',0,'0',0) ,
       (2085,0,'Allow Click Post Button PCV','',0,'\\20510','\\205',0,'0',0) ,
       (2086,0,'Allow Click UnPost Button PCV','',0,'\\20511','\\205',0,'0',0) ,
       (2087,0,'Allow Click Add Account PCV','',0,'\\20512','\\205',0,'0',0) ,
       (2088,0,'Allow Click Edit Account PCV','',0,'\\20513','\\205',0,'0',0) ,
       (2089,0,'Allow Click Delete Account PCV','',0,'\\20514','\\205',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SV','/module/payable/sv','Petty Cash Voucher','fa fa-money-check-alt sub_menu_ico',2075)";
    } //end function

    public function parentreceivable($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(546,0,'RECEIVABLES','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'RECEIVABLES',$sort,'fa fa-hand-holding-usd',',AR,CR,KR')";
    } //end function

    public function dc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4409,0,'Daily Collection Report','',0,'\\312','$parent',0,'0',0),
        (4410,0,'Allow View Transaction DC','DC',0,'\\31201','\\312',0,'0',0),
        (4411,0,'Allow Click Edit Button DC','',0,'\\31202','\\312',0,'0',0),
        (4412,0,'Allow Click New Button DC','',0,'\\31203','\\312',0,'0',0),
        (4413,0,'Allow Click Save Button DC','',0,'\\31204','\\312',0,'0',0),
        (4414,0,'Allow Click Delete Button DC','',0,'\\31205','\\312',0,'0',0),
        (4415,0,'Allow Click Print Button DC','',0,'\\31206','\\312',0,'0',0),
        (4416,0,'Allow Click Lock Button DC','',0,'\\31207','\\312',0,'0',0),
        (4417,0,'Allow Click UnLock Button DC','',0,'\\31208','\\312',0,'0',0),
        (4418,0,'Allow Click Post Button DC','',0,'\\31209','\\312',0,'0',0),
        (4419,0,'Allow Click Add Account DC','',0,'\\31210','\\312',0,'0',0),
        (4420,0,'Allow Click Edit Account DC','',0,'\\31211','\\312',0,'0',0),
        (4421,0,'Allow Click Delete Account DC','',0,'\\31212','\\312',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DC','/module/receivable/dc','Daily Collection Report','fa fa-money-bill-alt sub_menu_ico',4409)";
    }

    public function ar($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(239,0,'Receivable Setup','',0,'\\301','$parent',0,'0',0) ,
        (240,0,'Allow View Transaction RS','AR',0,'\\30101','\\301',0,'0',0) ,
        (241,0,'Allow Click Edit Button  RS','',0,'\\30102','\\301',0,'0',0) ,
        (242,0,'Allow Click New Button RS','',0,'\\30103','\\301',0,'0',0) ,
        (243,0,'Allow Click Save Button RS','',0,'\\30104','\\301',0,'0',0) ,
        (245,0,'Allow Click Delete Button RS','',0,'\\30106','\\301',0,'0',0) ,
        (246,0,'Allow Click Print Button RS','',0,'\\30107','\\301',0,'0',0) ,
        (247,0,'Allow Click Lock Button RS','',0,'\\30108','\\301',0,'0',0) ,
        (248,0,'Allow Click UnLock Button RS','',0,'\\30109','\\301',0,'0',0) ,
        (249,0,'Allow Click Post Button RS','',0,'\\30110','\\301',0,'0',0) ,
        (250,0,'Allow Click UnPost Button RS','',0,'\\30111','\\301',0,'0',0) ,
        (251,0,'Allow Click Add Account RS','',0,'\\30112','\\301',0,'0',0) ,
        (252,0,'Allow Click Edit Account RS','',0,'\\30113','\\301',0,'0',0) ,
        (253,0,'Allow Click Delete Account RS','',0,'\\30114','\\301',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AR','/module/receivable/ar','AR Setup','fa fa-money-bill-alt sub_menu_ico',239)";
    } //end function


    public function cr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(223,0,'Received Payment','',0,'\\303','$parent',0,'0',0) ,
        (224,0,'Allow View Transaction CR ','CR',0,'\\30301','\\303',0,'0',0) ,
        (225,0,'Allow Click Edit Button  CR ','',0,'\\30302','\\303',0,'0',0) ,
        (226,0,'Allow Click New Button CR ','',0,'\\30303','\\303',0,'0',0) ,
        (227,0,'Allow Click Save Button CR ','',0,'\\30304','\\303',0,'0',0) ,
        (229,0,'Allow Click Delete Button CR ','',0,'\\30306','\\303',0,'0',0) ,
        (230,0,'Allow Click Print Button CR ','',0,'\\30307','\\303',0,'0',0) ,
        (231,0,'Allow Click Lock Button CR ','',0,'\\30308','\\303',0,'0',0) ,
        (232,0,'Allow Click UnLock Button CR ','',0,'\\30309','\\303',0,'0',0) ,
        (233,0,'Allow Click Post Button CR ','',0,'\\30310','\\303',0,'0',0) ,
        (234,0,'Allow Click UnPost Button CR ','',0,'\\30311','\\303',0,'0',0) ,
        (235,0,'Allow Click Add Account CR','',0,'\\30312','\\303',0,'0',0) ,
        (236,0,'Allow Click Edit Account CR','',0,'\\30313','\\303',0,'0',0) ,
        (4501,0,'Allow Click Void transaction CR','',0,'\\30315','\\303',0,'0',0) ,
        (237,0,'Allow Click Delete Account CR','',0,'\\30314','\\303',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CR','/module/receivable/cr','Received Payment','fa fa-file-invoice sub_menu_ico',223)";
    } //end function

    public function kr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(208,0,'Counter Receipt','',0,'\\302','$parent',0,'0',0) ,
        (209,0,'Allow View Transaction KR ','KR',0,'\\30201','\\302',0,'0',0) ,
        (210,0,'Allow Click Edit Button  KR ','',0,'\\30202','\\302',0,'0',0) ,
        (211,0,'Allow Click New Button KR ','',0,'\\30203','\\302',0,'0',0) ,
        (212,0,'Allow Click Save Button KR ','',0,'\\30204','\\302',0,'0',0) ,
        (214,0,'Allow Click Delete Button KR ','',0,'\\30206','\\302',0,'0',0) ,
        (215,0,'Allow Click Print Button KR ','',0,'\\30207','\\302',0,'0',0) ,
        (216,0,'Allow Click Lock Button KR ','',0,'\\30208','\\302',0,'0',0) ,
        (217,0,'Allow Click UnLock Button KR ','',0,'\\30209','\\302',0,'0',0) ,
        (218,0,'Allow Click Post Button KR ','',0,'\\30210','\\302',0,'0',0) ,
        (219,0,'Allow Click UnPost Button KR ','',0,'\\30211','\\302',0,'0',0) ,
        (220,0,'Allow Click Add Account KR','',0,'\\30212','\\302',0,'0',0) ,
        (221,0,'Allow Click Edit Account KR','',0,'\\30213','\\302',0,'0',0) ,
        (222,0,'Allow Click Delete Account KR','',0,'\\30214','\\302',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'KR','/module/receivable/kr','Counter Receipt','fa fa-calculator sub_menu_ico',208)";
    } //end function

    public function ka($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4220,0,'AR Audit','',0,'\\311','$parent',0,'0',0) ,
        (4221,0,'Allow View Transaction','KA',0,'\\31101','\\311',0,'0',0) ,
        (4222,0,'Allow Click Edit Button','',0,'\\31102','\\311',0,'0',0) ,
        (4223,0,'Allow Click New Button','',0,'\\31103','\\311',0,'0',0) ,
        (4224,0,'Allow Click Save Button','',0,'\\31104','\\311',0,'0',0) ,
        (4225,0,'Allow Click Delete Button','',0,'\\31106','\\311',0,'0',0) ,
        (4226,0,'Allow Click Print Button','',0,'\\31107','\\311',0,'0',0) ,
        (4227,0,'Allow Click Lock Button','',0,'\\31108','\\311',0,'0',0) ,
        (4228,0,'Allow Click UnLock Button','',0,'\\31109','\\311',0,'0',0) ,
        (4229,0,'Allow Click Post Button','',0,'\\31110','\\311',0,'0',0) ,
        (4230,0,'Allow Click UnPost Button','',0,'\\31111','\\311',0,'0',0) ,
        (4231,0,'Allow Click Add Account','',0,'\\31112','\\311',0,'0',0) ,
        (4232,0,'Allow Click Delete Account','',0,'\\31113','\\311',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'KA','/module/cbbsi/ka','AR Audit','fa fa-calculator sub_menu_ico',4220)";
    } //end function

    public function py($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4233,0,'Payment Listing','',0,'\\206','$parent',0,'0',0) ,
        (4234,0,'Allow View Transaction','PY',0,'\\20601','\\206',0,'0',0) ,
        (4235,0,'Allow Click Edit Button','',0,'\\20602','\\206',0,'0',0) ,
        (4236,0,'Allow Click New Button','',0,'\\20603','\\206',0,'0',0) ,
        (4237,0,'Allow Click Save Button','',0,'\\20604','\\206',0,'0',0) ,
        (4238,0,'Allow Click Delete Button','',0,'\\20606','\\206',0,'0',0) ,
        (4239,0,'Allow Click Print Button','',0,'\\20607','\\206',0,'0',0) ,
        (4240,0,'Allow Click Lock Button','',0,'\\20608','\\206',0,'0',0) ,
        (4241,0,'Allow Click UnLock Button','',0,'\\20609','\\206',0,'0',0) ,
        (4242,0,'Allow Click Post Button','',0,'\\20610','\\206',0,'0',0) ,
        (4243,0,'Allow Click UnPost Button','',0,'\\20611','\\206',0,'0',0) ,
        (4244,0,'Allow Click Add Account','',0,'\\20612','\\206',0,'0',0) ,
        (4245,0,'Allow Click Delete Account','',0,'\\20613','\\206',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PY','/module/cbbsi/py','Payment Listing','fa fa-calculator sub_menu_ico',4233)";
    } //end function

    public function ps($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4422,0,'Payment Listing Summary','',0,'\\207','$parent',0,'0',0),
        (4423,0,'Allow View Transaction PS','PS',0,'\\20701','\\207',0,'0',0),
        (4424,0,'Allow Click Edit Button PS','',0,'\\20702','\\207',0,'0',0),
        (4425,0,'Allow click New Button PS','',0,'\\20703','\\207',0,'0',0),
        (4426,0,'Allow Click Save Button PS','',0,'\\20704','\\207',0,'0',0),
        (4427,0,'Allow Click Delete Button PS','',0,'\\20705','\\207',0,'0',0),
        (4428,0,'Allow Click Print Button PS','',0,'\\20706','\\207',0,'0',0),
        (4429,0,'Allow Click Lock Button PS','',0,'\\20707','\\207',0,'0',0),
        (4430,0,'Allow Click UnLock Button PS','',0,'\\20708','\\207',0,'0',0),
        (4431,0,'Allow Click Post Button PS','',0,'\\20709','\\207',0,'0',0),
        (4432,0,'Allow Click UnPost Button PS','',0,'\\20710','\\207',0,'0',0),
        (4433,0,'Allow Click Add Account PS','',0,'\\20711','\\207',0,'0',0),
        (4434,0,'Allow Click Delete Account PS','',0,'\\20712','\\207',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PS','/module/cbbsi/ps','Payment Listing Summary','fa fa-calculator sub_menu_ico',4422)";
    }

    public function parentaccounting($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(548,0,'ACCOUNTING','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'ACCOUNTING',$sort,'local_atm',',GJ,DS,bankrecon')";
    } //end function

    public function coa($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2,0,'Chart of Accounts','',0,'\\701','$parent',0,'0',0) ,
        (3,0,'Allow View Chart of Accounts','COA',0,'\\70101','\\701',0,'0',0) ,
        (4,0,'Allow Click Edit Button  COA','',0,'\\70102','\\701',0,'0',0) ,
        (5,0,'Allow Click New Button COA','',0,'\\70103','\\701',0,'0',0) ,
        (6,0,'Allow Click Save Button COA','',0,'\\70104','\\701',0,'0',0) ,
        (7,0,'Allow Click Delete Button COA','',0,'\\70105','\\701',0,'0',0) ,
        (8,0,'Allow Click Print Button COA','',0,'\\70106','\\701',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'coa','/uniquecoa/unique/coa','Chart of Accounts','fa fa-file sub_menu_ico',2)";
    } //end function

    public function coaalias($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'coaalias','/tableentries/tableentry/coaalias','COA Default Alias','fa fa-file sub_menu_ico',4578)";
    }

    public function gj($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(343,0,'General Journal','',0,'\\702','$parent',0,'0',0) ,
       (344,0,'Allow View Transaction GJ','GJ',0,'\\70201','\\702',0,'0',0) ,
       (345,0,'Allow Click Edit Button  GJ','',0,'\\70202','\\702',0,'0',0) ,
       (346,0,'Allow Click New Button GJ','',0,'\\70203','\\702',0,'0',0) ,
       (347,0,'Allow Click Save Button GJ','',0,'\\70204','\\702',0,'0',0) ,
       (349,0,'Allow Click Delete Button GJ','',0,'\\70206','\\702',0,'0',0) ,
       (350,0,'Allow Click Print Button GJ','',0,'\\70207','\\702',0,'0',0) ,
       (351,0,'Allow Click Lock Button GJ','',0,'\\70208','\\702',0,'0',0) ,
       (352,0,'Allow Click UnLock Button GJ','',0,'\\70209','\\702',0,'0',0) ,
       (353,0,'Allow Click Post Button GJ','',0,'\\70210','\\702',0,'0',0) ,
       (354,0,'Allow Click UnPost Button GJ','',0,'\\70211','\\702',0,'0',0) ,
       (355,0,'Allow Click Add Account GJ','',0,'\\70212','\\702',0,'0',0) ,
       (356,0,'Allow Click Edit Account GJ','',0,'\\70213','\\702',0,'0',0) ,
       (357,0,'Allow Click Delete Account GJ','',0,'\\70214','\\702',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GJ','/module/accounting/gj','General Journal','fa fa-book sub_menu_ico',343)";
    } //end function


    public function gd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1740,0,'Debit Memo','',0,'\\703','$parent',0,'0',0) ,
        (1741,0,'Allow View Transaction GD','GD',0,'\\70301','\\703',0,'0',0) ,
        (1742,0,'Allow Click Edit Button  GD','',0,'\\70302','\\703',0,'0',0) ,
        (1743,0,'Allow Click New Button GD','',0,'\\70303','\\703',0,'0',0) ,
        (1744,0,'Allow Click Save Button GD','',0,'\\70304','\\703',0,'0',0) ,
        (1746,0,'Allow Click Delete Button GD','',0,'\\70306','\\703',0,'0',0) ,
        (1747,0,'Allow Click Print Button GD','',0,'\\70307','\\703',0,'0',0) ,
        (1748,0,'Allow Click Lock Button GD','',0,'\\70308','\\703',0,'0',0) ,
        (1749,0,'Allow Click UnLock Button GD','',0,'\\70309','\\703',0,'0',0) ,
        (1750,0,'Allow Click Post Button GD','',0,'\\70310','\\703',0,'0',0) ,
        (1751,0,'Allow Click UnPost Button GD','',0,'\\70311','\\703',0,'0',0) ,
        (1752,0,'Allow Click Add Account GD','',0,'\\70312','\\703',0,'0',0) ,
        (1753,0,'Allow Click Edit Account GD','',0,'\\70313','\\703',0,'0',0) ,
        (1754,0,'Allow Click Delete Account GD','',0,'\\70314','\\703',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GD','/module/accounting/gd','Debit Memo','fa fa-folder-plus sub_menu_ico',1740)";
    } //end function

    public function gc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1760,0,'Credit Memo','',0,'\\704','$parent',0,'0',0) ,
        (1761,0,'Allow View Transaction GC','GC',0,'\\70401','\\704',0,'0',0) ,
        (1762,0,'Allow Click Edit Button  GC','',0,'\\70402','\\704',0,'0',0) ,
        (1763,0,'Allow Click New Button GC','',0,'\\70403','\\704',0,'0',0) ,
        (1764,0,'Allow Click Save Button GC','',0,'\\70404','\\704',0,'0',0) ,
        (1766,0,'Allow Click Delete Button GC','',0,'\\70406','\\704',0,'0',0) ,
        (1767,0,'Allow Click Print Button GC','',0,'\\70407','\\704',0,'0',0) ,
        (1768,0,'Allow Click Lock Button GC','',0,'\\70408','\\704',0,'0',0) ,
        (1769,0,'Allow Click UnLock Button GC','',0,'\\70409','\\704',0,'0',0) ,
        (1770,0,'Allow Click Post Button GC','',0,'\\70410','\\704',0,'0',0) ,
        (1771,0,'Allow Click UnPost Button GC','',0,'\\70411','\\704',0,'0',0) ,
        (1772,0,'Allow Click Add Account GC','',0,'\\70412','\\704',0,'0',0) ,
        (1773,0,'Allow Click Edit Account GC','',0,'\\70413','\\704',0,'0',0) ,
        (1774,0,'Allow Click Delete Account GC','',0,'\\70414','\\704',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GC','/module/accounting/gc','Credit Memo','fa fa-folder-minus sub_menu_ico',1760)";
    } //end function

    public function ds($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(326,0,'Deposit Slip','',0,'\\304','$parent',0,'0',0) ,
        (327,0,'Allow View Transaction DS','DS',0,'\\30401','\\304',0,'0',0) ,
        (328,0,'Allow Click Edit Button  DS','',0,'\\30402','\\304',0,'0',0) ,
        (329,0,'Allow Click New Button DS','',0,'\\30403','\\304',0,'0',0) ,
        (330,0,'Allow Click Save Button DS','',0,'\\30404','\\304',0,'0',0) ,
        (332,0,'Allow Click Delete Button DS','',0,'\\30406','\\304',0,'0',0) ,
        (333,0,'Allow Click Print Button DS','',0,'\\30407','\\304',0,'0',0) ,
        (334,0,'Allow Click Lock Button DS','',0,'\\30408','\\304',0,'0',0) ,
        (335,0,'Allow Click UnLock Button DS','',0,'\\30409','\\304',0,'0',0) ,
        (336,0,'Allow Click Post Button DS','',0,'\\30410','\\304',0,'0',0) ,
        (337,0,'Allow Click UnPost Button DS','',0,'\\30411','\\304',0,'0',0) ,
        (338,0,'Allow Click Add Account DS','',0,'\\30412','\\304',0,'0',0) ,
        (339,0,'Allow Click Edit Account DS','',0,'\\30413','\\304',0,'0',0) ,
        (340,0,'Allow Click Delete Account DS','',0,'\\30414','\\304',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DS','/module/accounting/ds','Deposit Slip','fa fa-edit sub_menu_ico',326)";
    } //end function

    public function bankrecon($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2367,0,'Bank Reconciliation','',0,'\\305','$parent',0,'0',0) ,
        (2368,0,'Allow View Transaction','BANKRECON',0,'\\30501','\\305',0,'0',0) ,
        (2369,0,'Allow Click Clear Date Button','',0,'\\30502','\\305',0,'0',0),
        (3623,0,'Allow Click Print Button','',0,'\\30503','\\305',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BANKRECON','/headtable/accounting/bankrecon','Bank Reconciliation','fa fa-file-invoice-dollar sub_menu_ico',2367)";
    } //end function

    public function budget($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2370,0,'Budget Setup','',0,'\\306','$parent',0,'0',0) ,
        (2371,0,'Allow View Transaction','BUDGET',0,'\\30601','\\306',0,'0',0) ,
        (2372,0,'Allow Create Budget','',0,'\\30602','\\306',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BUDGET','/headtable/accounting/entrybudget','Budget Setup','fa fa-piggy-bank sub_menu_ico',2370)";
    } //end function

    public function checksetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2581,1,'Check Series Setup','',0,'\\307','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entrychecksetup','/tableentries/tableentry/entrychecksetup','Check Series Setup','fa fa-check sub_menu_ico',2581)";
    } //end function

    public function exchangerate($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2582,1,'Exchange Rate Setup','',0,'\\308','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entryexchangerate','/tableentries/tableentry/entryexchangerate','Exchange Rate Setup','fa fa-file-invoice-dollar sub_menu_ico',2582)";
    } //end function

    public function postdep($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2236,0,'Depreciation Schedule','',0,'\\705','$parent',0,'0',0),
           (2237,0,'Allow View Transaction Depreciation','',0,'\\70501','\\705',0,'0',0),
           (2238,0,'Allow Click Post Button','',0,'\\70502','\\705',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'postdep','/tableentries/fixedasset/postdep','Depreciation Schedule','fa fa-edit sub_menu_ico',2236)";
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
        $qry = "(1054,0,'OTHER MASTERS','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'OTHER MASTERS',$sort,'list_alt'," . $modules . ")";
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
        $qry = "(852,1,'" . $fieldLabel . "','',0,'\\1102','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'model','/tableentries/tableentry/entrymodel','" . $fieldLabel . "','fa fa-list sub_menu_ico',852)";
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

        $qry = "(853,1,'" . $fieldLabel . "','',0,'\\1103','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'part','/tableentries/tableentry/entrypart','" . $fieldLabel . "','fa fa-list sub_menu_ico',853)";
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
        $qry = "(857,1,'" . $fieldLabel . "','*129',0,'\\1105','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'stockgroup','/tableentries/tableentry/entrystockgroup','" . $fieldLabel . "','fa fa-list sub_menu_ico',857)";
    } //end function

    public function brand($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(856,1,'Item Brand','*129',0,'\\1101','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'brand','/tableentries/tableentry/entrybrand','Item Brand','fa fa-list sub_menu_ico',856)";
    } //end function

    public function itemclass($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(855,1,'Item Class','*129',0,'\\1104','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'itemclass','/tableentries/tableentry/entryitemclass','Item Class','fa fa-list sub_menu_ico',855)";
    } //end function

    public function clientcategories($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
            case 22: //eipi
                $qry = "(858,1,'Business Style','*129',0,'\\1106','$parent',0,'0',0)";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'categories','/tableentries/tableentry/entrycategories','Business Style','fa fa-list sub_menu_ico',858)";
                break;

            default:
                $qry = "(858,1,'Cust/Supp Categories','*129',0,'\\1106','$parent',0,'0',0)";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'categories','/tableentries/tableentry/entrycategories','Cust/Supp Categories','fa fa-list sub_menu_ico',858)";
                break;
        }
    } //end function

    public function industry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(4476,1,'Industry','*129',0,'\\1109','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'industry','/tableentries/tableentry/entryclientindustry','Industry','fa fa-list sub_menu_ico',4476)";
    } //end function


    public function project($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
                $qry = "(859,1,'Item Group','*129',0,'\\1107','$parent',0,'0',0)";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'project','/tableentries/tableentry/entryproject','Item Group','fa fa-list sub_menu_ico',859)";
                break;
            case 26: //bee healthy
                $qry = "(859,1,'Business Unit','*129',0,'\\1107','$parent',0,'0',0)";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'project','/tableentries/tableentry/entryproject','Business Unit','fa fa-list sub_menu_ico',859)";
                break;
            default:
                $qry = "(859,1,'Project','*129',0,'\\1107','$parent',0,'0',0)";
                $this->insertattribute($params, $qry);
                return "($sort,$p,'project','/tableentries/tableentry/entryproject','Project','fa fa-list sub_menu_ico',859)";
                break;
        }
    } //end function

    public function phase($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2601,1,'Phase','*129',0,'\\112','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'phase','/tableentries/mallentry/entryphase','Phase','fa fa-list sub_menu_ico',2601)";
    } //end function

    public function mms_section($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2618,1,'Section','*129',0,'\\114','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'section','/tableentries/mallentry/entrysection','Section','fa fa-list sub_menu_ico',2618)";
    } //end function

    public function electric_rate_category($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2677,1,'Electricity Rate Category','*129',0,'\\115','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'electricratecat','/tableentries/mallentry/entryelectricratecat','Electricity Rate Category','fa fa-lightbulb sub_menu_ico',2677)";
    } //end function

    public function water_rate_category($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(295,1,'Water Rate Category','*129',0,'\\116','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'waterratecat','/tableentries/mallentry/entrywaterratecat','Water Rate Category','fa fa-water sub_menu_ico',295)";
    } //end function

    public function waterrate($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(313,1,'Water Rate','*129',0,'\\117','$parent',0,'0',0),
        (4170,1,'Allow Click Save all Entry','',0,'\\11701','\\117',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'waterrate','/headtable/mallcustomform/waterrate','Water Rate','fa fa-tint sub_menu_ico',313)";
    } //end function

    public function electricityrate($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(262,1,'Electricity Rate','*129',0,'\\118','$parent',0,'0',0),
        (4168,1,'Allow Click Save all Entry','',0,'\\11801','\\118',0,0,0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'electricityrate','/headtable/mallcustomform/electricityrate','Electricity Rate','fa fa-plug sub_menu_ico',262)";
    } //end function

    public function storage_electricityrate($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2209,1,'Storage Electricity Rate','*129',0,'\\119','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'storage_electricityrate','/headtable/mallcustomform/storage_electricityrate','Storage Electricity Rate','fa fa-plug sub_menu_ico',2209)";
    } //end function

    public function location_ledger($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(375,1,'Location Ledger','',0,'\\1120','$parent',0,0,0) ,
      (121,0,'Allow View Location Ledger','',0,'\\112001','\\1120',0,'0',0) ,
      (2065,0,'Allow Click Edit Button Location Ledger','',0,'\\112002','\\1120',0,'0',0) ,
      (2080,0,'Allow Click New Button Location Ledger','',0,'\\112003','\\1120',0,'0',0) ,
      (244,0,'Allow Click Save Button Location Ledger','',0,'\\112004','\\1120',0,'0',0) ,
      (228,0,'Allow Click Delete Button Location Ledger','',0,'\\112005','\\1120',0,'0',0) ,
      (213,0,'Allow Click Print Button Location Ledger','',0,'\\112007','\\1120',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'locationledger','/ledgergrid/masterfile/locationledger','Location Ledger','fa fa-map-pin sub_menu_ico',375)";
    } //end function

    public function tenant($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(348,0,'Tenant Ledger','',0,'\\1121','$parent',0,'0',0),
      (1745,0,'Allow View Tenant Ledger','',0,'\\112101','\\1121',0,'0',0),
      (1765,0,'Allow Click Edit Button Tenant Ledger','',0,'\\112102','\\1121',0,'0',0),
      (331,0,'Allow Click New Button Tenant Ledger','',0,'\\112103','\\1121',0,'0',0),
      (789,0,'Allow Click Save Button Tenant Ledger','',0,'\\112104','\\1121',0,'0',0),
      (1685,0,'Allow Click Change Code Tenant Ledger','',0,'\\112105','\\1121',0,'0',0),
      (886,0,'Allow Click Delete Button Tenant Ledger','',0,'\\112106','\\1121',0,'0',0),
      (902,0,'Allow Click Print Button Tenant Ledger','',0,'\\112107','\\1121',0,'0',0),
      (4213,0,'Allow View AR History','',0,'\\112108','\\1121',0,'0',0),
      (4214,0,'Allow View AP History','',0,'\\112109','\\1121',0,'0',0),
      (4215,0,'Allow View PDC History','',0,'\\112110','\\1121',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tenant','/ledger/masterfile/tenant','Tenant','fa fa-house-user sub_menu_ico',348)";
    } //end function
    public function lp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1781,0,'Lease Provision','',0,'\\1122','$parent',0,'0',0),
      (2260,0,'Allow View Lease Provision','',0,'\\112201','\\1122',0,'0',0),
      (2278,0,'Allow Click Edit Button Lease Provision','',0,'\\112202','\\1122',0,'0',0),
      (1801,0,'Allow Click New Button Lease Provision','',0,'\\112203','\\1122',0,'0',0),
      (623,0,'Allow Click Save Button Lease Provision','',0,'\\112204','\\1122',0,'0',0),
      (2432,0,'Allow Click Change Code Lease Provision','',0,'\\112205','\\1122',0,'0',0),
      (1816,0,'Allow Click Delete Button Lease Provision','',0,'\\112206','\\1122',0,'0',0),
      (1831,0,'Allow Click Print Button Lease Provision','',0,'\\112207','\\1122',0,'0',0),
      (4051,0,'Allow Click Approve Button Lease Provision','',0,'\\112208','\\1122',0,'0',0),
      (4052,0,'Allow Click Post Button Lease Provision','',0,'\\112209','\\1122',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'LP','/module/operation/lp','Lease Provision','fa fa-street-view sub_menu_ico',1781)";
    } //end function

    public function waterreading($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2556,1,'Water Reading','*129',0,'\\1208','$parent',0,'0',0),
        (2568,1,'Allow Click Save all Entry','',0,'\\120801','\\1208',0,0,0),
        (4202,1,'Allow Edit Previous Reading','',0,'\\120802','\\1208',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'waterreading','/headtable/mallcustomform/waterreading','Water Reading','fa fa-faucet sub_menu_ico',2556)";
    } //end function

    public function electricityreading($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(2869,1,'Electricity Reading','*129',0,'\\1220','$parent',0,'0',0),
        (2870,1,'Allow Click Save all Entry','',0,'\\122001','\\1220',0,0,0),
        (4203,1,'Allow Edit Previous Reading','',0,'\\122002','\\1220',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'electricityreading','/headtable/mallcustomform/electricityreading','Electricity Reading','fa fa-bolt sub_menu_ico',2869)";
    } //end function

    public function gb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(4204,1,'Generate Billing','GB',0,'\\1221','$parent',0,'0',0)
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GB','/headtable/mall/gb','Generate Billing','fa fa-receipt sub_menu_ico',4204)";
    } //end function

    public function mb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4206,0,'Billing Entry','',0,'\\1222','$parent',0,'0',0) ,
        (4207,0,'Allow View Transaction MB','MB',0,'\\122201','\\1222',0,'0',0) ,
        (4208,0,'Allow Click New Button MB','',0,'\\122203','\\1222',0,'0',0) ,
        (4209,0,'Allow Click Delete Button MB','',0,'\\122206','\\1222',0,'0',0) ,
        (4210,0,'Allow Click Print Button MB','',0,'\\122207','\\1222',0,'0',0) ,
        (4211,0,'Allow Click Post Button MB','',0,'\\122210','\\1222',0,'0',0) ,
        (4212,0,'Allow Click UnPost Button MB','',0,'\\122211','\\1222',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'MB','/module/mall/mb','Billing Entry','fa fa-book sub_menu_ico',4206)";
    } //end function

    public function compatible($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1885,1,'Compatible Setup','*129',0,'\\1108','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'compatible','/tableentries/tableentry/entrycompatible','Compatible Setup','fa fa-list sub_menu_ico',1885)";
    } //end function



    public function parentissuance($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1052,0,'ISSUANCE','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'ISSUANCE',$sort,'fa fa-dolly',',TR,')";
    } //end function

    public function tr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(784,0,'Stock Request','',0,'\\1501','$parent',0,'0',0) ,
        (785,0,'Allow View Transaction S. Request','TR',0,'\\150101','\\1501',0,'0',0) ,
        (786,0,'Allow Click Edit Button  S. Request','',0,'\\150102','\\1501',0,'0',0) ,
        (787,0,'Allow Click New Button S. Request','',0,'\\150103','\\1501',0,'0',0) ,
        (788,0,'Allow Click Save Button S. Request','',0,'\\150104','\\1501',0,'0',0) ,
        (790,0,'Allow Click Delete Button S. Request','',0,'\\150106','\\1501',0,'0',0) ,
        (791,0,'Allow Click Print Button S. Request','',0,'\\150107','\\1501',0,'0',0) ,
        (792,0,'Allow Click Lock Button S. Request','',0,'\\150108','\\1501',0,'0',0) ,
        (793,0,'Allow Click UnLock Button S. Request','',0,'\\150109','\\1501',0,'0',0) ,
        (794,0,'Allow Click Post Button S. Request','',0,'\\150110','\\1501',0,'0',0) ,
        (795,0,'Allow Click UnPost Button S. Request','',0,'\\150111','\\1501',0,'0',0) ,
        (839,1,'Allow Click Add Item S. Request','',0,'\\150112','\\1501',0,'0',0) ,
        (840,1,'Allow Click Edit Item S. Request','',0,'\\150113','\\1501',0,'0',0) ,
        (841,1,'Allow Click Delete Item S. Request','',0,'\\150114','\\1501',0,'0',0) ,
        (842,1,'Allow Change Amount S. Request','',0,'\\150115','\\1501',0,'0',0),
        (3589,1,'Allow Click Disapproved','',0,'\\150116','\\1501',0,'0',0)";

        if ($params['companyid'] == 40) { //cdo
            $qry .= ",(4454,1,'Allow View all Branch TR','',0,'\\150117','\\1501',0,'0',0)";
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
        return "($sort,$p,'TR','/module/" . $folder . "/tr','Stock Request','fa fa-list sub_menu_ico',784)";
    } //end function

    public function trapproval($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1680,0,'Stock Request Approval','',0,'\\1504','$parent',0,'0',0) ,
        (1681,0,'Allow View Transaction S. Request Approval','SS',0,'\\150401','\\1504',0,'0',0) ,
        (1682,0,'Allow Click Edit Button  S. Request Approval','',0,'\\150402','\\1504',0,'0',0) ,
        (1683,0,'Allow Click New Button S. Request Approval','',0,'\\150403','\\1504',0,'0',0) ,
        (1684,0,'Allow Click Save Button S. Request Approval','',0,'\\150404','\\1504',0,'0',0) ,
        (1686,0,'Allow Click Delete Button S. Request Approval','',0,'\\150406','\\1504',0,'0',0) ,
        (1687,0,'Allow Click Print Button S. Request Approval','',0,'\\150407','\\1504',0,'0',0) ,
        (1688,0,'Allow Click Lock Button S. Request Approval','',0,'\\150408','\\1504',0,'0',0) ,
        (1689,0,'Allow Click UnLock Button S. Request Approval','',0,'\\150409','\\1504',0,'0',0) ,
        (1690,0,'Allow Click Post Button S. Request Approval','',0,'\\150410','\\1504',0,'0',0) ,
        (1691,0,'Allow Click UnPost Button S. Request Approval','',0,'\\150411','\\1504',0,'0',0) ,
        (1692,1,'Allow Click Add Item S. Request Approval','',0,'\\150412','\\1504',0,'0',0) ,
        (1693,1,'Allow Click Delete Item S. Request Approval','',0,'\\150414','\\1504',0,'0',0) ,
        (1694,1,'Allow Change Amount S. Request Approval','',0,'\\150415','\\1504',0,'0',0) ,
        (1695,1,'Allow Click Edit Item S. Request Approval','',0,'\\150413','\\1504',0,'0',0)";
        $this->insertattribute($params, $qry);
        $systemtype = $this->companysetup->getsystemtype($params);
        $folder = 'issuance';
        switch ($systemtype) {
            case 'MANUFACTURING':
                $folder = 'production';
                break;
        }
        return "($sort,$p,'TRAPPROVAL','/module/" . $folder . "/trapproval','Stock Request Approval','fa fa-check sub_menu_ico',1680)";
    } //end function


    public function st($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $folder = 'issuance';
        $qry = "(881,0,'Stock Transfer','',0,'\\1502','$parent',0,'0',0) ,
        (882,0,'Allow View Transaction S. Transfer','ST',0,'\\150201','\\1502',0,'0',0) ,
        (883,0,'Allow Click Edit Button  S. Transfer','',0,'\\150202','\\1502',0,'0',0) ,
        (884,0,'Allow Click New Button S. Transfer','',0,'\\150203','\\1502',0,'0',0) ,
        (885,0,'Allow Click Save Button S. Transfer','',0,'\\150204','\\1502',0,'0',0) ,
        (887,0,'Allow Click Delete Button S. Transfer','',0,'\\150206','\\1502',0,'0',0) ,
        (888,0,'Allow Click Print Button S. Transfer','',0,'\\150207','\\1502',0,'0',0) ,
        (889,0,'Allow Click Lock Button S. Transfer','',0,'\\150208','\\1502',0,'0',0) ,
        (890,0,'Allow Click UnLock Button S. Transfer','',0,'\\150209','\\1502',0,'0',0) ,
        (891,0,'Allow Click Post Button S. Transfer','',0,'\\150210','\\1502',0,'0',0) ,
        (892,0,'Allow Click UnPost Button S. Transfer','',0,'\\150211','\\1502',0,'0',0) ,
        (893,1,'Allow Click Add Item S. Transfer','',0,'\\150212','\\1502',0,'0',0) ,
        (896,1,'Allow Click Edit Item S. Transfer','',0,'\\150213','\\1502',0,'0',0) ,
        (894,1,'Allow Click Delete Item S. Transfer','',0,'\\150214','\\1502',0,'0',0) ,
        (895,1,'Allow Change Amount S. Transfer','',0,'\\150215','\\1502',0,'0',0)";
        $this->insertattribute($params, $qry);

        switch ($params['companyid']) {
            case 39: //cbbsi
                $folder = 'cbbsi';
                break;
            case 40: //cdo
                $folder = 'cdo';
                break;
        }

        return "($sort,$p,'ST','/module/" . $folder . "/st','Stock Transfer','fa fa-dolly-flatbed sub_menu_ico',881)";
    } //end function

    public function ss($params, $parent, $sort)
    {
        $systemtype = $this->companysetup->getsystemtype($params);

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(897,0,'Stock Issuance','',0,'\\1503','$parent',0,'0',0) ,
        (898,0,'Allow View Transaction S. Issuance','SS',0,'\\150301','\\1503',0,'0',0) ,
        (899,0,'Allow Click Edit Button  S. Issuance','',0,'\\150302','\\1503',0,'0',0) ,
        (900,0,'Allow Click New Button S. Issuance','',0,'\\150303','\\1503',0,'0',0) ,
        (901,0,'Allow Click Save Button S. Issuance','',0,'\\150304','\\1503',0,'0',0) ,
        (903,0,'Allow Click Delete Button S. Issuance','',0,'\\150306','\\1503',0,'0',0) ,
        (904,0,'Allow Click Print Button S. Issuance','',0,'\\150307','\\1503',0,'0',0) ,
        (905,0,'Allow Click Lock Button S. Issuance','',0,'\\150308','\\1503',0,'0',0) ,
        (906,0,'Allow Click UnLock Button S. Issuance','',0,'\\150309','\\1503',0,'0',0) ,
        (907,0,'Allow Click Post Button S. Issuance','',0,'\\150310','\\1503',0,'0',0) ,
        (908,0,'Allow Click UnPost Button S. Issuance','',0,'\\150311','\\1503',0,'0',0) ,
        (909,1,'Allow Click Add Item S. Issuance','',0,'\\150312','\\1503',0,'0',0) ,
        (910,1,'Allow Click Delete Item S. Issuance','',0,'\\150314','\\1503',0,'0',0) ,
        (911,1,'Allow Change Amount S. Issuance','',0,'\\150315','\\1503',0,'0',0) ,
        (912,1,'Allow Click Edit Item S. Issuance','',0,'\\150316','\\1503',0,'0',0)";

        if ($systemtype == "ATI") {
            $qry .= ", (4174,1,'Allow View All WH','',0,'\\150317','\\1503',0,'0',0)";
            $qry .= ", (4386,1,'Override Restrictions','',0,'\\150318','\\1503',0,'0',0)";
        }

        $this->insertattribute($params, $qry);

        $folder = 'issuance';
        switch ($systemtype) {
            case 'ATI':
                $folder = 'ati';
                break;
        }
        return "($sort,$p,'SS','/module/" . $folder . "/ss','Stock Issuance','fa fa-people-carry sub_menu_ico',897)";
    } //end function

    public function parentschoolsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1055,0,'SCHOOL SETUP','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'SCHOOL SETUP',$sort,'cast_for_education',',schoolyear,')";
    } //end function

    public function levels($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(914,0,'Levels','',0,'\\1202','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'LEVELS','/tableentries/enrollmententry/en_levels','Levels','fa fa-layer-group sub_menu_ico',914)";
    } //end function

    public function semester($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(915,0,'Semester','',0,'\\1203','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SEMESTER','/tableentries/enrollmententry/en_semester','Semester','fa fa-calendar-week sub_menu_ico',915)";
    } //end function


    public function roomlist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(933,0,'Room List','',0,'\\1216','$parent',0,'0',0) ,
        (1452,0,'Allow Edit Room List','',0,'\\121601','\\1216',0,'0',0) ,
        (1453,0,'Allow View Room List','',0,'\\121602','\\1216',0,'0',0) ,
        (1454,0,'Allow New Room List','',0,'\\121603','\\1216',0,'0',0) ,
        (1455,0,'Allow Save Room List','',0,'\\121604','\\1216',0,'0',0) ,
        (1316,0,'Allow Change Student List','',0,'\\121607','\\1216',0,'0',0) ,
        (1456,0,'Allow Delete Room List','',0,'\\121605','\\1216',0,'0',0) ,
        (1457,0,'Allow Print Room List','',0,'\\121606','\\1216',0,'0',0) ,
        (1327,0,'Allow Click Add Item Room List','',0,'\\121608','\\1216',0,'0',0) ,
        (1328,0,'Allow Click Edit Item Room List','',0,'\\121609','\\1216',0,'0',0) ,
        (1329,0,'Allow Click Delete Item Room List','',0,'\\121610','\\1216',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ROOMLIST','/ledgergrid/enrollmententry/en_roomlist','Room List','fa fa-chalkboard-teacher sub_menu_ico',933)";
    } //end function

    public function subject($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(920,0,'Subject List','',0,'\\1209','$parent',0,'0',0) ,
        (1308,0,'Allow Edit Subject List','',0,'\\120901','\\1209',0,'0',0) ,
        (1309,0,'Allow View Subject List','',0,'\\120902','\\1209',0,'0',0) ,
        (1310,0,'Allow New Subject List','',0,'\\120903','\\1209',0,'0',0) ,
        (1311,0,'Allow Save Subject List','',0,'\\120904','\\1209',0,'0',0) ,
        (1317,0,'Allow Change Subject List','',0,'\\120907','\\1209',0,'0',0) ,
        (1312,0,'Allow Delete Subject List','',0,'\\120905','\\1209',0,'0',0) ,
        (1313,0,'Allow Print Subject List','',0,'\\120906','\\1209',0,'0',0) ,
        (1324,0,'Allow Click Add Item Subject List','',0,'\\120910','\\1209',0,'0',0) ,
        (1325,0,'Allow Click Edit Item Subject List','',0,'\\120908','\\1209',0,'0',0) ,
        (1326,0,'Allow Click Delete Item Subject List','',0,'\\120909','\\1209',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SUBJECT','/ledgergrid/enrollmententry/en_subject','Subject List','fa fa-book sub_menu_ico',920)";
    } //end function

    public function student($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(922,0,'Student List','',0,'\\1211','$parent',0,'0',0) ,
        (923,0,'Allow Edit Student List','',0,'\\121101','\\1211',0,'0',0) ,
        (924,0,'Allow View Student List','',0,'\\121102','\\1211',0,'0',0) ,
        (925,0,'Allow New Student List','',0,'\\121103','\\1211',0,'0',0) ,
        (926,0,'Allow Save Student List','',0,'\\121104','\\1211',0,'0',0) ,
        (1315,0,'Allow Change Student List','',0,'\\121107','\\1211',0,'0',0) ,
        (927,0,'Allow Delete Student List','',0,'\\121105','\\1211',0,'0',0) ,
        (928,0,'Allow Print Student List','',0,'\\121106','\\1211',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'student','/ledgergrid/enrollmententry/en_student','Student List','fa fa-user sub_menu_ico',929)";
    } //end function

    public function new_student_requirement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(929,0,'New Student Requirements','',0,'\\1212','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'NEW_STUDENT_REQUIREMENTS','/tableentries/enrollmententry/en_new_student_requirements','New Student Requirements','fa fa-copy sub_menu_ico',929)";
    } //end function

    public function transfer_requirement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(930,0,'Transferee Requirements','',0,'\\1213','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'TRANSFEREE_REQUIREMENTS','/tableentries/enrollmententry/en_transferee_requirements','Transferee Requirements','fa fa-sticky-note sub_menu_ico',930)";
    } //end function


    public function instructor($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(917,0,'Instructor List','',0,'\\1206','$parent',0,'0',0) ,
        (1727,0,'Allow View Instructor List','',0,'\\120602','\\1206',0,'0',0) ,
        (1721,0,'Allow Edit Instructor List','',0,'\\120601','\\1206',0,'0',0) ,
        (1722,0,'Allow New Instructor List','',0,'\\120603','\\1206',0,'0',0) ,
        (1723,0,'Allow Save Instructor List','',0,'\\120604','\\1206',0,'0',0) ,
        (1724,0,'Allow Change Instructor List','',0,'\\120607','\\1206',0,'0',0) ,
        (1725,0,'Allow Delete Instructor List','',0,'\\120605','\\1206',0,'0',0) ,
        (1726,0,'Allow Print Instructor List','',0,'\\120606','\\1206',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'INSTRUCTOR','/ledgergrid/enrollmententry/en_instructor','Instructor List','fa fa-chalkboard-teacher sub_menu_ico',917)";
    } //end function

    public function schoolyear($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(913,0,'School Year','',0,'\\1201','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SCHOOLYEAR','/tableentries/enrollmententry/en_schoolyear','School Year','far fa-calendar-check sub_menu_ico',913)";
    } //end function

    public function cardremarks($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2519, 0, 'Card Remarks', '', 0, '\\1218', '$parent', 0, '0', 0)";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'CARD REMARKS', '/tableentries/enrollmententry/en_cardremarks', 'Card Remarks', 'fa fa-sticky-note sub_menu_ico', 2519)";
    }

    public function attendancesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2730, 0, 'Attendance Setup', '', 0, '\\1219', '$parent', 0, '0', 0)";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'Attendance Setup', '/tableentries/enrollmententry/en_attendancesetup', 'Attendance Setup', 'fa fa-sticky-note sub_menu_ico', 2730)";
    }

    public function scheme($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(931,0,'Scheme','',0,'\\1214','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SCHEME','/tableentries/enrollmententry/en_scheme','Scheme','fa fa-sticky-note sub_menu_ico',931)";
    } //end function

    public function period($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(916,0,'Period','',0,'\\1204','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PERIOD','/tableentries/enrollmententry/en_period','Period','fas fa-user-clock sub_menu_ico',916)";
    } //end function

    public function course($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(918,0,'Course List','',0,'\\1207','$parent',0,'0',0) ,
        (1458,0,'Allow Edit Course List','',0,'\\120701','\\1207',0,'0',0) ,
        (1459,0,'Allow View Course List','',0,'\\120702','\\1207',0,'0',0) ,
        (1460,0,'Allow New Course List','',0,'\\120703','\\1207',0,'0',0) ,
        (1461,0,'Allow Save Course List','',0,'\\120704','\\1207',0,'0',0) ,
        (1314,0,'Allow Change Course List','',0,'\\120707','\\1207',0,'0',0) ,
        (1462,0,'Allow Delete Course List','',0,'\\120705','\\1207',0,'0',0) ,
        (1463,0,'Allow Print Course List','',0,'\\120706','\\1207',0,'0',0) ,
        (1330,0,'Allow Click Add Item Course List','',0,'\\120708','\\1207',0,'0',0) ,
        (1331,0,'Allow Click Edit Item Course List','',0,'\\120709','\\1207',0,'0',0) ,
        (1332,0,'Allow Click Delete Item Course List','',0,'\\120710','\\1207',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'COURSE','/ledgergrid/enrollmententry/en_course','Course List','fas fa-scroll sub_menu_ico',918)";
    } //end function


    public function fees($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(934,0,'Fees','',0,'\\1217','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'FEES','/tableentries/enrollmententry/en_fees','Fees','fas fa-asterisk sub_menu_ico',934)";
    } //end function

    public function credentials($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(921,0,'Student Credentials','',0,'\\1210','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CREDENTIALS','/tableentries/enrollmententry/en_credentials','Credential List','fas fa-asterisk sub_menu_ico',921)";
    } //end function

    public function mode_of_payment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(932,0,'Mode of Payment','',0,'\\1215','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'MODE_OF_PAYMENT','/tableentries/enrollmententry/en_modeofpayment','Mode Of Payment','fas fa-asterisk sub_menu_ico',932)";
    } //end function

    public function grade_component($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(935,0,'Grade Component','',0,'\\1301','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GRADE COMPONENT','/tableentries/enrollmentgradeentry/en_gradecomponent','Grade Component','fa fa-sticky-note sub_menu_ico',935)";
    } //end function

    public function grade_equivalent($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(936,0,'Grade Equivalent','',0,'\\1302','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GRADE EQUIVALENT','/tableentries/enrollmentgradeentry/en_gradeequivalent','Grade Equivalent','fa fa-sticky-note sub_menu_ico',936)";
    } //end function

    public function grade_equivalentletters($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2726, 0, 'Grade Equivalent Letters', '', 0, '\\1307', '$parent', 0, '0', 0)";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'GRADE EQUIVALENT LETTERS', '/tableentries/enrollmentgradeentry/en_gradeequivalentletters', 'Grade Equivalent Letters', 'fa fa-sticky-note sub_menu_ico', 2726)";
    }


    public function grade_setup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(937,0,'Grade Setup','',0,'\\1303','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GRADE SETUP','/module/enrollment/ef','Grade Setup','fa fa-sticky-note sub_menu_ico',937)";
    } //end function

    public function quarter_setup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2506,0,'Quarter Setup','',0,'\\1304','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'QUARTER SETUP','/tableentries/enrollmentgradeentry/en_quartersetup','Quarter Setup','fa fa-sticky-note sub_menu_ico',2506)";
    } //end function

    public function honorroll_criteria($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2507,0,'Honor Roll Criteria','',0,'\\1305','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HONOR ROLL CRITERIA','/tableentries/enrollmentgradeentry/en_honorrollcriteria','Honor Roll Criteria','fa fa-sticky-note sub_menu_ico',2507)";
    } //end function

    public function conduct_grade($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2508,0,'Conduct Grade','',0,'\\1306','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CONDUCT GRADE','/tableentries/enrollmentgradeentry/en_conductgrade','Conduct Grade','fa fa-sticky-note sub_menu_ico',2508)";
    } //end function

    public function attendance_type($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(919,0,'Attendance Type','',0,'\\1205','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ATTENDANCE TYPE', '/tableentries/enrollmententry/en_attendancetype', 'Attendance Type', 'fa fa-sticky-note sub_menu_ico', 919)";
    } //end function

    public function parentschoolsystem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1057,0,'SCHOOL SYSTEM','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'SCHOOL SYSTEM',$sort,'cast_for_education',',schoolyear,')";
    } //end function


    public function reportcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2520, 0, 'Report Card Setup', '', 0, '\\1414', '$parent', 0, '0', 0),
        (2521, 0, 'Allow Edit Report Card', '', 0, '\\141401', '\\1414', 0, '0', 0),
        (2522, 0, 'Allow View Report Card', '', 0, '\\141402', '\\1414', 0, '0', 0),
        (2523, 0, 'Allow New Report Card', '', 0, '\\141403', '\\1414', 0, '0', 0),
        (2524, 0, 'Allow Save Report Card', '', 0, '\\141404', '\\1414', 0, '0', 0),
        (2525, 0, 'Allow Delete Report Card', '', 0, '\\141405', '\\1414', 0, '0', 0),
        (2526, 0, 'Allow Change Code Report Card', '', 0, '\\141406', '\\1414', 0, '0', 0),
        (2527, 0, 'Allow Lock Report Card', '', 0, '\\141407', '\\1414', 0, '0', 0),
        (2528, 0, 'Allow UnLock Report Card', '', 0, '\\141408', '\\1414', 0, '0', 0),
        (2529, 0, 'Allow Print Report Card', '', 0, '\\141409', '\\1414', 0, '0', 0),
        (2530, 0, 'Allow Click Add Item Report Card', '', 0, '\\141410', '\\1414', 0, '0', 0),
        (2531, 0, 'Allow Click Edit Item Report Card', '', 0, '\\141411', '\\1414', 0, '0', 0),
        (2532, 0, 'Allow Click Delete Item Report Card', '', 0, '\\141412', '\\1414', 0, '0', 0)";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'reportcard', '/module/enrollment/ej', 'Report Card Setup', 'fa fa-user sub_menu_ico', 2520)";
    }

    public function ec($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(938,0,'Curriculum Setup','',0,'\\1401','$parent',0,'0',0) ,
        (939,0,'Allow Edit Curriculum Setup','',0,'\\140101','\\1401',0,'0',0) ,
        (940,0,'Allow View Curriculum Setup','',0,'\\140102','\\1401',0,'0',0) ,
        (941,0,'Allow New Curriculum Setup','',0,'\\140103','\\1401',0,'0',0) ,
        (942,0,'Allow Save Curriculum Setup','',0,'\\140104','\\1401',0,'0',0) ,
        (943,0,'Allow Delete Curriculum Setup','',0,'\\140105','\\1401',0,'0',0) ,
        (944,0,'Allow Change Code Curriculum Setup','',0,'\\140106','\\1401',0,'0',0) ,
        (945,0,'Allow Post Curriculum Setup','',0,'\\140107','\\1401',0,'0',0) ,
        (946,0,'Allow UnPost Curriculum Setup','',0,'\\140108','\\1401',0,'0',0) ,
        (947,0,'Allow Lock Curriculum Setup','',0,'\\140109','\\1401',0,'0',0) ,
        (948,0,'Allow UnLock Curriculum Setup','',0,'\\140110','\\1401',0,'0',0) ,
        (1318,0,'Allow Click Add Item Curriculum Setup','',0,'\\140111','\\1401',0,'0',0) ,
        (1319,0,'Allow Click Edit Item Curriculum Setup','',0,'\\140112','\\1401',0,'0',0) ,
        (1320,0,'Allow Click Delete Item Curriculum Setup','',0,'\\140113','\\1401',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EC','/module/enrollment/ec','Curriculum Setup','fa fa-user sub_menu_ico',938)";
    } //end function

    public function assessmentsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(960,0,'Assessment Setup','',0,'\\1403','$parent',0,'0',0) ,
        (961,0,'Allow Edit Assessment Setup','',0,'\\140301','\\1403',0,'0',0) ,
        (962,0,'Allow View Assessment Setup','',0,'\\140302','\\1403',0,'0',0) ,
        (963,0,'Allow New Assessment Setup','',0,'\\140303','\\1403',0,'0',0) ,
        (964,0,'Allow Save Assessment Setup','',0,'\\140304','\\1403',0,'0',0) ,
        (965,0,'Allow Delete Assessment Setup','',0,'\\140305','\\1403',0,'0',0) ,
        (966,0,'Allow Change Code Assessment Setup','',0,'\\140306','\\1403',0,'0',0) ,
        (967,0,'Allow Post Assessment Setup','',0,'\\140307','\\1403',0,'0',0) ,
        (968,0,'Allow UnPost Assessment Setup','',0,'\\140308','\\1403',0,'0',0) ,
        (969,0,'Allow Lock Assessment Setup','',0,'\\140309','\\1403',0,'0',0) ,
        (970,0,'Allow UnLock Assessment Setup','',0,'\\140310','\\1403',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'assesmentsetup','/module/enrollment/et','Assessment Setup','fa fa-user sub_menu_ico',960)";
    } //end function


    public function schedule($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(949,0,'Schedule Setup','',0,'\\1402','$parent',0,'0',0) ,
        (950,0,'Allow Edit Schedule Setup','',0,'\\140201','\\1402',0,'0',0) ,
        (951,0,'Allow View Schedule Setup','',0,'\\140202','\\1402',0,'0',0) ,
        (952,0,'Allow New Schedule Setup','',0,'\\140203','\\1402',0,'0',0) ,
        (953,0,'Allow Save Schedule Setup','',0,'\\140204','\\1402',0,'0',0) ,
        (954,0,'Allow Delete Schedule Setup','',0,'\\140205','\\1402',0,'0',0) ,
        (955,0,'Allow Change Code Schedule Setup','',0,'\\140206','\\1402',0,'0',0) ,
        (956,0,'Allow Post Schedule Setup','',0,'\\140207','\\1402',0,'0',0) ,
        (957,0,'Allow UnPost Schedule Setup','',0,'\\140208','\\1402',0,'0',0) ,
        (958,0,'Allow Lock Schedule Setup','',0,'\\140209','\\1402',0,'0',0) ,
        (959,0,'Allow UnLock Schedule Setup','',0,'\\140210','\\1402',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SCHEDULE','/module/enrollment/es','Schedule','fa fa-user sub_menu_ico',949)";
    } //end function

    public function grade_school_assessment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry =  "(4013,0,'Grade School Assessment','',0,'\\1404','$parent',0,'0',0) ,
        (4014,0,'Allow Edit Grade School Assessment','',0,'\\140401','\\1404',0,'0',0) ,
        (4015,0,'Allow View Grade School Assessment','',0,'\\140402','\\1404',0,'0',0) ,
        (4015,0,'Allow New Grade School Assessment','',0,'\\140403','\\1404',0,'0',0) ,
        (4016,0,'Allow Save Grade School Assessment','',0,'\\140404','\\1404',0,'0',0) ,
        (4017,0,'Allow Delete Grade School Assessment','',0,'\\140405','\\1404',0,'0',0) ,
        (4018,0,'Allow Change Code Grade School Assessment','',0,'\\140406','\\1404',0,'0',0) ,
        (4019,0,'Allow Post Grade School Assessment','',0,'\\140407','\\1404',0,'0',0) ,
        (4020,0,'Allow UnPost Grade School Assessment','',0,'\\140408','\\1404',0,'0',0) ,
        (4021,0,'Allow Lock Grade School Assessment','',0,'\\140409','\\1404',0,'0',0) ,
        (4022,0,'Allow UnLock Grade School Assessment','',0,'\\140410','\\1404',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'elemassesment','/module/enrollment/ei','Grades School Assessment','fa fa-user sub_menu_ico',971)";
    } //end function

    public function college_assessment($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry =  "(971,0,'College Assessment','',0,'\\1407','$parent',0,'0',0) ,
        (972,0,'Allow Edit College Assessment','',0,'\\140701','\\1407',0,'0',0) ,
        (973,0,'Allow View College Assessment','',0,'\\140702','\\1407',0,'0',0) ,
        (974,0,'Allow New College Assessment','',0,'\\140703','\\1407',0,'0',0) ,
        (975,0,'Allow Save College Assessment','',0,'\\140704','\\1407',0,'0',0) ,
        (976,0,'Allow Delete College Assessment','',0,'\\140705','\\1407',0,'0',0) ,
        (977,0,'Allow Change Code College Assessment','',0,'\\140706','\\1407',0,'0',0) ,
        (978,0,'Allow Post College Assessment','',0,'\\140707','\\1407',0,'0',0) ,
        (979,0,'Allow UnPost College Assessment','',0,'\\140708','\\1407',0,'0',0) ,
        (980,0,'Allow Lock College Assessment','',0,'\\140709','\\1407',0,'0',0) ,
        (981,0,'Allow UnLock College Assessment','',0,'\\140710','\\1407',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'assesment','/module/enrollment/ea','College Assessment','fa fa-user sub_menu_ico',971)";
    } //end function

    public function registration($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(982,0,'Student Registration','',0,'\\1405','$parent',0,'0',0) ,
        (983,0,'Allow Edit Student Registration','',0,'\\140501','\\1405',0,'0',0) ,
        (984,0,'Allow View Student Registration','',0,'\\140502','\\1405',0,'0',0) ,
        (985,0,'Allow New Student Registration','',0,'\\140503','\\1405',0,'0',0) ,
        (986,0,'Allow Save Student Registration','',0,'\\140504','\\1405',0,'0',0) ,
        (987,0,'Allow Delete Student Registration','',0,'\\140505','\\1405',0,'0',0) ,
        (988,0,'Allow Change Code Student Registration','',0,'\\140506','\\1405',0,'0',0) ,
        (989,0,'Allow Post Student Registration','',0,'\\140507','\\1405',0,'0',0) ,
        (990,0,'Allow UnPost Student Registration','',0,'\\140508','\\1405',0,'0',0) ,
        (991,0,'Allow Lock Student Registration','',0,'\\140509','\\1405',0,'0',0) ,
        (992,0,'Allow UnLock Student Registration','',0,'\\140510','\\1405',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'registration','/module/enrollment/er','Student Registration','fa fa-user sub_menu_ico',982)";
    } //end function

    public function addordrop($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(993,0,'Add / Drop','',0,'\\1406','$parent',0,'0',0) ,
        (994,0,'Allow Edit Add / Drop','',0,'\\140601','\\1406',0,'0',0) ,
        (995,0,'Allow View Add / Drop','',0,'\\140602','\\1406',0,'0',0) ,
        (996,0,'Allow New Add / Drop','',0,'\\140603','\\1406',0,'0',0) ,
        (997,0,'Allow Save Add / Drop','',0,'\\140604','\\1406',0,'0',0) ,
        (998,0,'Allow Delete Add / Drop','',0,'\\140605','\\1406',0,'0',0) ,
        (999,0,'Allow Change Code Add / Drop','',0,'\\140606','\\1406',0,'0',0) ,
        (1000,0,'Allow Post Add / Drop','',0,'\\140607','\\1406',0,'0',0) ,
        (1001,0,'Allow UnPost Add / Drop','',0,'\\140608','\\1406',0,'0',0) ,
        (1002,0,'Allow Lock Add / Drop','',0,'\\140609','\\1406',0,'0',0) ,
        (1003,0,'Allow UnLock Add / Drop','',0,'\\140610','\\1406',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'addordrop','/module/enrollment/ed','Add / Drop','fa fa-user sub_menu_ico',993)";
    } //end function


    public function gradeentry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1015,0,'Grade Entry','',0,'\\1411','$parent',0,'0',0) ,
        (1016,0,'Allow Edit Grade Entry','',0,'\\141101','\\1411',0,'0',0) ,
        (1017,0,'Allow View Grade Entry','',0,'\\141102','\\1411',0,'0',0) ,
        (1018,0,'Allow New Grade Entry','',0,'\\141103','\\1411',0,'0',0) ,
        (1019,0,'Allow Save Grade Entry','',0,'\\141104','\\1411',0,'0',0) ,
        (1020,0,'Allow Delete Grade Entry','',0,'\\141105','\\1411',0,'0',0) ,
        (1021,0,'Allow Change Code Grade Entry','',0,'\\141106','\\1411',0,'0',0) ,
        (1022,0,'Allow Post Grade Entry','',0,'\\141107','\\1411',0,'0',0) ,
        (1023,0,'Allow UnPost Grade Entry','',0,'\\141108','\\1411',0,'0',0) ,
        (1024,0,'Allow Lock Grade Entry','',0,'\\141109','\\1411',0,'0',0) ,
        (1025,0,'Allow UnLock Grade Entry','',0,'\\141110','\\1411',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'gradeentry','/module/enrollment/eh','Grade Entry','fa fa-user sub_menu_ico',1015)";
    } //end function

    public function studentgradeentry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1026,0,'Student Grade Entry','',0,'\\1412','$parent',0,'0',0) ,
        (1027,0,'Allow Edit Student Grade Entry','',0,'\\141201','\\1412',0,'0',0) ,
        (1028,0,'Allow View Student Grade Entry','',0,'\\141202','\\1412',0,'0',0) ,
        (1029,0,'Allow New Student Grade Entry','',0,'\\141203','\\1412',0,'0',0) ,
        (1030,0,'Allow Save Student Grade Entry','',0,'\\141204','\\1412',0,'0',0) ,
        (1031,0,'Allow Delete Student Grade Entry','',0,'\\141205','\\1412',0,'0',0) ,
        (1032,0,'Allow Change Code Student Grade Entry','',0,'\\141206','\\1412',0,'0',0) ,
        (1033,0,'Allow Post Student Grade Entry','',0,'\\141207','\\1412',0,'0',0) ,
        (1034,0,'Allow UnPost Student Grade Entry','',0,'\\141208','\\1412',0,'0',0) ,
        (1035,0,'Allow Lock Student Grade Entry','',0,'\\141209','\\1412',0,'0',0) ,
        (1036,0,'Allow UnLock Student Grade Entry','',0,'\\141210','\\1412',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'studentgradeentry','/module/enrollment/eg','Student Grade Entry','fa fa-user sub_menu_ico',1026)";
    } //end function

    public function attendanceentry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1037,0,'Attendance Entry','',0,'\\1413','$parent',0,'0',0) ,
        (1038,0,'Allow Edit Attendance Entry','',0,'\\141301','\\1413',0,'0',0) ,
        (1039,0,'Allow View Attendance Entry','',0,'\\141302','\\1413',0,'0',0) ,
        (1040,0,'Allow New Attendance Entry','',0,'\\141303','\\1413',0,'0',0) ,
        (1041,0,'Allow Save Attendance Entry','',0,'\\141304','\\1413',0,'0',0) ,
        (1042,0,'Allow Delete Attendance Entry','',0,'\\141305','\\1413',0,'0',0) ,
        (1043,0,'Allow Change Code Attendance Entry','',0,'\\141306','\\1413',0,'0',0) ,
        (1044,0,'Allow Post Attendance Entry','',0,'\\141307','\\1413',0,'0',0) ,
        (1045,0,'Allow UnPost Attendance Entry','',0,'\\141308','\\1413',0,'0',0) ,
        (1046,0,'Allow Lock Attendance Entry','',0,'\\141309','\\1413',0,'0',0) ,
        (1047,0,'Allow UnLock Attendance Entry','',0,'\\141310','\\1413',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'attendanceentry','/module/enrollment/en','Attendance Entry','fa fa-user sub_menu_ico',1037)";
    } //end function

    public function en_levelup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2446,0,'Student Level Up','',0,'\\1414','$parent',0,'0',0),
        (2447,0,'Allow View Student Level Up','',0,'\\141401','\\1414',0,'0',0),
        (2448,0,'Allow Print Student Level Up','',0,'\\141402','\\1414',0,'0',0),
        (2449,0,'Allow Approved Student Level Up','',0,'\\141403','\\1414',0,'0',0),
        (2450,0,'Allow Disapproved Student Level Up','',0,'\\141404','\\1414',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'STUDENT LEVEL UP','/module/enrollment/en_levelup','Student Level Up','fa fa-sticky-note sub_menu_ico',2446)";
    } //end function

    public function parentroommanagement($params, $parent, $sort)
    {
        $p = $parent;
        return "insert into left_parent(id,name,seq,class,doc) values($p,'ROOM MANAGEMENT',$sort,'fa fa-hotel',',roommanagement,')";
    } //end function

    public function hmsratecode($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'ratecode','/tableentries/hmsentry/ratecodesetup','Rate Code Setup','fa fa-coins sub_menu_ico',3502)";
    } //end function


    public function hmsroomtype($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'roomtype','/ledgergrid/hmsentry/roomtype','Room Type Setup','fas fa-door-open sub_menu_ico',21)";
    } //end function

    public function hmsothercharges($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'otherchanges','/tableentries/hmsentry/othercharges','Other Charges','fa fa-concierge-bell sub_menu_ico',3502)";
    } //end function

    public function hmspackagesetup($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'packagesetup','/tableentries/hmsentry/packagesetup','Package Setup','fa fa-tags sub_menu_ico',3502)";
    } //end function

    public function parentfrontdesk($params, $parent, $sort)
    {
        $p = $parent;
        return "insert into left_parent(id,name,seq,class,doc) values($p,'FRONT DESK',$sort,'fa fa-hotel',',frontdesk,')";
    } //end function

    public function hmsreservation($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'reservation','/ledgergrid/hms/reservation','Reservation','fa fa-coins sub_menu_ico',3502)";
    } //end function


    public function hmstempreservation($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'tempreservation','/ledgergrid/hms/arrival','Pendinmg Reservation','fa fa-coins sub_menu_ico',3502)";
    } //end function

    public function hmswalkin($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'walkin','/ledgergrid/hms/walkin','Walk-in','fa fa-coins sub_menu_ico',3502)";
    } //end function

    public function hmsroomplan($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'roomplan','/uniqueroomplan/unique/roomplan','Room Plan','fa fa-tags sub_menu_ico',168)";
    } //end function

    public function parentcustomersupport($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1075,0,'CUSTOMER SUPPORT','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'CUSTOMER SUPPORT',$sort,'support_agent',',customersupport,')";
    } //end function

    public function create_ticket($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1059,0,'Create Ticket','',0,'\\1601','$parent',0,'0',0) ,
        (1060,0,'Allow View Create Ticket','',0,'\\160101','\\1601',0,'0',0) ,
        (1061,0,'Allow Click Edit Button Create Ticket','',0,'\\160102','\\1601',0,'0',0) ,
        (1062,0,'Allow Click New Button Create Ticket','',0,'\\160103','\\1601',0,'0',0) ,
        (1063,0,'Allow Click Save Button Create Ticket','',0,'\\160104','\\1601',0,'0',0) ,
        (1064,0,'Allow Click Change Code Create Ticket','',0,'\\160105','\\1601',0,'0',0) ,
        (1065,0,'Allow Click Delete Button Create Ticket','',0,'\\160106','\\1601',0,'0',0) ,
        (1066,0,'Allow Click Print Button Create Ticket','',0,'\\160107','\\1601',0,'0',0) ,
        (1067,0,'Allow Click Lock Button Create Ticket','',0,'\\160108','\\1601',0,'0',0) ,
        (1068,0,'Allow Click UnLock Button Create Ticket','',0,'\\160109','\\1601',0,'0',0) ,
        (1069,0,'Allow Click Change Amount Create Ticket','',0,'\\160110','\\1601',0,'0',0) ,
        (1070,0,'Allow Click Post Button Create Ticket','',0,'\\160111','\\1601',0,'0',0) ,
        (1071,0,'Allow Click UnPost Button Create Ticket','',0,'\\160112','\\1601',0,'0',0) ,
        (1072,0,'Allow Click Add Item Create Ticket','',0,'\\160113','\\1601',0,'0',0) ,
        (1073,0,'Allow Click Edit Item Create Ticket','',0,'\\160114','\\1601',0,'0',0) ,
        (1074,0,'Allow Click Delete Item Create Ticket','',0,'\\160115','\\1601',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CREATE TICKET','/module/customerservice/ca','Create Ticket','fa fa-sticky-note sub_menu_ico',1059)";
    } //end function


    public function update_ticket($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1076,0,'Update Ticket','',0,'\\1602','$parent',0,'0',0) ,
        (1077,0,'Allow View Update Ticket','',0,'\\160201','\\1602',0,'0',0) ,
        (1078,0,'Allow Click Edit Button Update Ticket','',0,'\\160202','\\1602',0,'0',0) ,
        (1079,0,'Allow Click New Button Update Ticket','',0,'\\160203','\\1602',0,'0',0) ,
        (1080,0,'Allow Click Save Button Update Ticket','',0,'\\160204','\\1602',0,'0',0) ,
        (1081,0,'Allow Click Change Code Update Ticket','',0,'\\160205','\\1602',0,'0',0) ,
        (1082,0,'Allow Click Delete Button Update Ticket','',0,'\\160206','\\1602',0,'0',0) ,
        (1083,0,'Allow Click Print Button Update Ticket','',0,'\\160207','\\1602',0,'0',0) ,
        (1084,0,'Allow Click Lock Button Update Ticket','',0,'\\160208','\\1602',0,'0',0) ,
        (1085,0,'Allow Click UnLock Button Update Ticket','',0,'\\160209','\\1602',0,'0',0) ,
        (1086,0,'Allow Click Change Amount Update Ticket','',0,'\\160210','\\1602',0,'0',0) ,
        (1087,0,'Allow Click Post Button Update Ticket','',0,'\\160211','\\1602',0,'0',0) ,
        (1088,0,'Allow Click UnPost Button Update Ticket','',0,'\\160212','\\1602',0,'0',0) ,
        (1089,0,'Allow Click Add Item Update Ticket','',0,'\\160213','\\1602',0,'0',0) ,
        (1090,0,'Allow Click Edit Item Update Ticket','',0,'\\160214','\\1602',0,'0',0) ,
        (1091,0,'Allow Click Delete Item Update Ticket','',0,'\\160215','\\1602',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'UPDATE TICKET','/module/customerservice/cb','Update Ticket','fa fa-sticky-note sub_menu_ico',1076)";
    } //end function

    public function ticket_history($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1092,0,'Ticket History','',0,'\\1603','\\16',0,'0',0) ,
        (1093,0,'Allow View Ticket History','',0,'\\160301','\\1603',0,'0',0) ,
        (1094,0,'Allow Click Edit Button Ticket History','',0,'\\160302','\\1603',0,'0',0) ,
        (1095,0,'Allow Click New Button Ticket History','',0,'\\160303','\\1603',0,'0',0) ,
        (1096,0,'Allow Click Save Button Ticket History','',0,'\\160304','\\1603',0,'0',0) ,
        (1097,0,'Allow Click Change Code Ticket History','',0,'\\160305','\\1603',0,'0',0) ,
        (1098,0,'Allow Click Delete Button Ticket History','',0,'\\160306','\\1603',0,'0',0) ,
        (1099,0,'Allow Click Print Button Ticket History','',0,'\\160307','\\1603',0,'0',0) ,
        (1100,0,'Allow Click Lock Button Ticket History','',0,'\\160308','\\1603',0,'0',0) ,
        (1101,0,'Allow Click UnLock Button Ticket History','',0,'\\160309','\\1603',0,'0',0) ,
        (1102,0,'Allow Click Change Amount Ticket History','',0,'\\160310','\\1603',0,'0',0) ,
        (1103,0,'Allow Click Post Button Ticket History','',0,'\\160311','\\1603',0,'0',0) ,
        (1104,0,'Allow Click UnPost Button Ticket History','',0,'\\160312','\\1603',0,'0',0) ,
        (1105,0,'Allow Click Add Item Ticket History','',0,'\\160313','\\1603',0,'0',0) ,
        (1106,0,'Allow Click Edit Item Ticket History','',0,'\\160314','\\1603',0,'0',0) ,
        (1107,0,'Allow Click Delete Item Ticket History','',0,'\\160315','\\1603',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'TICKET HISTORY','/module/customerservice/cc','Ticket History','fa fa-sticky-note sub_menu_ico',1092)";
    } //end function

    public function parenthrissetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1259,0,'HRIS SETUP','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'HRIS SETUP',$sort,'fa fa-users',',compcodeconduct,empstatusmaster,statchangemaster,skillreqmaster,jobtitlemaster,empreqmaster,preemptest,')";
    } //end function

    public function code_of_conduct($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1260,1,'Company Code of Conduct','',0,'\\1801','$parent',0,0,0) ,
        (1261,0,'Allow View Company Code of Conduct','',0,'\\180101','\\1801',0,'0',0) ,
        (1262,0,'Allow Click Edit Button Company Code of Conduct','',0,'\\180102','\\1801',0,'0',0) ,
        (1263,0,'Allow Click New Button Company Code of Conduct','',0,'\\180103','\\1801',0,'0',0) ,
        (1264,0,'Allow Click Save Button Company Code of Conduct','',0,'\\180104','\\1801',0,'0',0) ,
        (1265,0,'Allow Click Delete Button Company Code of Conduct','',0,'\\180105','\\1801',0,'0',0) ,
        (1336,0,'Allow Click Change Code Company Code of Conduct','',0,'\\180106','\\1801',0,'0',0) ,
        (1337,0,'Allow Click Print Button Item Company Code of Conduct','',0,'\\180107','\\1801',0,'0',0) ,
        (1338,0,'Allow Click Add Item Company Code of Conduct','',0,'\\180108','\\1801',0,'0',0) ,
        (1339,0,'Allow Click Edit Item Company Code of Conduct','',0,'\\180109','\\1801',0,'0',0) ,
        (1340,0,'Allow Click Delete Item Company Code of Conduct','',0,'\\180110','\\1801',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CODE OF CONDUCT','/ledgergrid/hrisentry/codeconduct','Code of Conduct','fa fa-user sub_menu_ico',1260)";
    } //end function

    public function employment_status($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1280,1,'Employment Status Master Entry','',0,'\\1803','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EMPLOYMENT STATUS MASTER ENTRY','/tableentries/hrisentry/empstatusmaster','Employment Status Master Entry','fa fa-user sub_menu_ico',1280)";
    } //end function


    public function status_change($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1281,1,'Status Change Master','',0,'\\1804','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'STATUS CHANGE MASTER','/tableentries/hrisentry/statchangemaster','Status Change Master','fa fa-user sub_menu_ico',1281)";
    } //end function

    public function skill_requirement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1282,1,'Skill Requirements Master','',0,'\\1805','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SKILL REQUIREMENT MASTER','/tableentries/hrisentry/skillreqmaster','Skill Requirements Master','fa fa-user sub_menu_ico',1282)";
    } //end function

    public function job_title($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1270,1,'Job Title Master','',0,'\\1802','$parent',0,0,0) ,
        (1271,0,'Allow View Job Title Master','',0,'\\180201','\\1802',0,'0',0) ,
        (1272,0,'Allow Click Edit Button Job Title Master','',0,'\\180202','\\1802',0,'0',0) ,
        (1273,0,'Allow Click New Button Job Title Master','',0,'\\180203','\\1802',0,'0',0) ,
        (1274,0,'Allow Click Save Button Job Title Master','',0,'\\180204','\\1802',0,'0',0) ,
        (1275,0,'Allow Click Delete Button Job Title Master','',0,'\\180205','\\1802',0,'0',0) ,
        (1717,0,'Allow Change Code Button Job Title Master','',0,'\\180206','\\1802',0,'0',0) ,
        (1718,0,'Allow Click Print Button Job Title Master','',0,'\\180207','\\1802',0,'0',0) ,
        (1341,0,'Allow Click Add Item Job Title Master','',0,'\\180208','\\1802',0,'0',0) ,
        (1342,0,'Allow Click Edit Item Job Title Master','',0,'\\180209','\\1802',0,'0',0) ,
        (1343,0,'Allow Click Delete Item Job Title Master','',0,'\\180210','\\1802',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JOB TITLE MASTER','/ledgergrid/hris/jobtitlemaster','Job Title Master','fa fa-user sub_menu_ico',1270)";
    } //end function

    public function employmentrequirements($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1283,1,'Employment Requirements Master','',0,'\\1806','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EMPLOYMENT REQUIREMENTS MASTER','/tableentries/hrisentry/empreqmaster','Employment Requirements Master','fa fa-user sub_menu_ico',1283)";
    } //end function

    public function pre_employment_test($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1284,1,'Pre Employment Test','',0,'\\1807','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PRE EMPLOYMENT TEST','/tableentries/hrisentry/preemptest','Pre Employment Test','fa fa-user sub_menu_ico',1284)";
    } //end function


    public function parenthris($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1152,0,'HRIS','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'HRIS',$sort,'fa fa-fingerprint',',applicantledger,reqtrainingdev,trainentry,turnoveritems,returnitems,empstatusentrychange,incidentreport,noticeexplain,noticedeciplinary,clearance')";
    } //end function


    public function applicant($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1108,0,'Applicant Ledger','',0,'\\1701','$parent',0,'0',0) ,
        (1109,0,'Allow View Applicant Ledger','',0,'\\170101','\\1701',0,'0',0) ,
        (1110,0,'Allow Click Edit Button Applicant Ledger','',0,'\\170102','\\1701',0,'0',0) ,
        (1111,0,'Allow Click New Button Applicant Ledger','',0,'\\170103','\\1701',0,'0',0) ,
        (1112,0,'Allow Click Save Button Applicant Ledger','',0,'\\170104','\\1701',0,'0',0) ,
        (1113,0,'Allow Click Change Code Applicant Ledger','',0,'\\170105','\\1701',0,'0',0) ,
        (1114,0,'Allow Click Delete Button Applicant Ledger','',0,'\\170106','\\1701',0,'0',0) ,
        (1115,0,'Allow Click Print Button Applicant Ledger','',0,'\\170107','\\1701',0,'0',0) ,
        (1116,0,'Allow Click Post Button Applicant Ledger','',0,'\\170108','\\1701',0,'0',0) ,
        (1117,0,'Allow Click UnPost Button Applicant Ledger','',0,'\\170109','\\1701',0,'0',0) ,
        (1670,0,'Allow Click Lock Button Applicant Ledger','',0,'\\170110','\\1701',0,'0',0) ,
        (1671,0,'Allow Click UnLock Button Applicant Ledger','',0,'\\170111','\\1701',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'APPLICANT LEDGER','/ledgergrid/hris/applicantledger','Applicant Ledger','fa fa-sticky-note sub_menu_ico',1108)";
    } //end function

    public function personnel_requisition($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(1239,0,'Personnel Requisition','',0,'\\1702','$parent',0,'0',0) ,
        (1240,0,'Allow View Personnel Requisition','',0,'\\170201','\\1702',0,'0',0) ,
        (1241,0,'Allow Click Edit Button Personnel Requisition','',0,'\\170202','\\1702',0,'0',0) ,
        (1242,0,'Allow Click New Button Personnel Requisition','',0,'\\170203','\\1702',0,'0',0) ,
        (1243,0,'Allow Click Save Button Personnel Requisition','',0,'\\170204','\\1702',0,'0',0) ,
        (1245,0,'Allow Click Delete Button Personnel Requisition','',0,'\\170206','\\1702',0,'0',0) ,
        (1246,0,'Allow Click Print Button Personnel Requisition','',0,'\\170207','\\1702',0,'0',0) ,
        (1247,0,'Allow Click Post Button Personnel Requisition','',0,'\\170208','\\1702',0,'0',0) ,
        (1248,0,'Allow Click UnPost Button Personnel Requisition','',0,'\\170209','\\1702',0,'0',0) ,
        (1711,0,'Allow Click Lock Button Personnel Requisition','',0,'\\170210','\\1702',0,'0',0) ,
        (1712,0,'Allow Click UnLock Button Personnel Requisition','',0,'\\170211','\\1702',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PERSONNEL REQUISITION','/module/hris/HQ','Personnel Requisition','fa fa-sticky-note sub_menu_ico',1239)";
    } //end function

    public function job_offer($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $qry = "(1249,0,'Job Offer','',0,'\\1703','$parent',0,'0',0) ,
        (1250,0,'Allow View Job Offer','',0,'\\170301','\\1703',0,'0',0) ,
        (1251,0,'Allow Click Edit Button Job Offer','',0,'\\170302','\\1703',0,'0',0) ,
        (1252,0,'Allow Click New Button Job Offer','',0,'\\170303','\\1703',0,'0',0) ,
        (1253,0,'Allow Click Save Button Job Offer','',0,'\\170304','\\1703',0,'0',0) ,
        (1255,0,'Allow Click Delete Button Job Offer','',0,'\\170306','\\1703',0,'0',0) ,
        (1256,0,'Allow Click Print Button Job Offer','',0,'\\170307','\\1703',0,'0',0) ,
        (1257,0,'Allow Click Post Button Job Offer','',0,'\\170308','\\1703',0,'0',0) ,
        (1713,0,'Allow Click Lock Button Job Offer','',0,'\\170310','\\1703',0,'0',0) ,
        (1714,0,'Allow Click UnLock Button Job Offer','',0,'\\170311','\\1703',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JOB OFFER','/module/hris/HJ','Job Offer','fa fa-sticky-note sub_menu_ico',1249)";
    } //end function

    public function ha($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1128,0,'Request For Training And Development','',0,'\\1705','$parent',0,'0',0) ,
        (1129,0,'Allow View Request For Training And Development','',0,'\\170501','\\1705',0,'0',0) ,
        (1130,0,'Allow Click Edit Button Request For Training And Development','',0,'\\170502','\\1705',0,'0',0) ,
        (1131,0,'Allow Click New Button Request For Training And Development','',0,'\\170503','\\1705',0,'0',0) ,
        (1132,0,'Allow Click Save Button Request For Training And Development','',0,'\\170504','\\1705',0,'0',0) ,
        (1133,0,'Allow Click Change Code Request For Training And Development','',0,'\\170505','\\1705',0,'0',0) ,
        (1134,0,'Allow Click Delete Button Request For Training And Development','',0,'\\170506','\\1705',0,'0',0) ,
        (1135,0,'Allow Click Print Button Request For Training And Development','',0,'\\170507','\\1705',0,'0',0) ,
        (1136,0,'Allow Click Post Button Request For Training And Development','',0,'\\170508','\\1705',0,'0',0) ,
        (1137,0,'Allow Click UnPost Button Request For Training And Development','',0,'\\170509','\\1705',0,'0',0) ,
        (1674,0,'Allow Click Lock Button Request For Training And Development','',0,'\\170510','\\1705',0,'0',0) ,
        (1675,0,'Allow Click UnLock Button Request For Training And Development','',0,'\\170511','\\1705',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HA','/module/hris/HA','Request Training and Development','fa fa-sticky-note sub_menu_ico',1128)";
    } //end function

    public function ht($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1138,0,'Training Entry','',0,'\\1706','$parent',0,'0',0) ,
        (1139,0,'Allow View Training Entry','',0,'\\170601','\\1706',0,'0',0) ,
        (1140,0,'Allow Click Edit Button Training Entry','',0,'\\170602','\\1706',0,'0',0) ,
        (1141,0,'Allow Click New Button Training Entry','',0,'\\170603','\\1706',0,'0',0) ,
        (1142,0,'Allow Click Save Button Training Entry','',0,'\\170604','\\1706',0,'0',0) ,
        (1143,0,'Allow Click Change Code Training Entry','',0,'\\170605','\\1706',0,'0',0) ,
        (1144,0,'Allow Click Delete Button Training Entry','',0,'\\170606','\\1706',0,'0',0) ,
        (1145,0,'Allow Click Print Button Training Entry','',0,'\\170607','\\1706',0,'0',0) ,
        (1146,0,'Allow Click Post Button Training Entry','',0,'\\170608','\\1706',0,'0',0) ,
        (1147,0,'Allow Click UnPost Button Training Entry','',0,'\\170609','\\1706',0,'0',0) ,
        (1676,0,'Allow Click Lock Button Training Entry','',0,'\\170610','\\1706',0,'0',0) ,
        (1677,0,'Allow Click UnLock Button Training Entry','',0,'\\170611','\\1706',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HT','/module/hris/HT','Training Entry','fa fa-sticky-note sub_menu_ico',1138)";
    } //end function


    public function ho($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1118,0,'Turn Over Of Items','',0,'\\1704','$parent',0,'0',0) ,
        (1119,0,'Allow View Turn Over Of Items','',0,'\\170401','\\1704',0,'0',0) ,
        (1120,0,'Allow Click Edit Button Turn Over Of Items','',0,'\\170402','\\1704',0,'0',0) ,
        (1121,0,'Allow Click New Button Turn Over Of Items','',0,'\\170403','\\1704',0,'0',0) ,
        (1122,0,'Allow Click Save Button Turn Over Of Items','',0,'\\170404','\\1704',0,'0',0) ,
        (1123,0,'Allow Click Change Code Turn Over Of Items','',0,'\\170405','\\1704',0,'0',0) ,
        (1124,0,'Allow Click Delete Button Turn Over Of Items','',0,'\\170406','\\1704',0,'0',0) ,
        (1125,0,'Allow Click Print Button Turn Over Of Items','',0,'\\170407','\\1704',0,'0',0) ,
        (1126,0,'Allow Click Post Button Turn Over Of Items','',0,'\\170408','\\1704',0,'0',0) ,
        (1127,0,'Allow Click UnPost Button Turn Over Of Items','',0,'\\170409','\\1704',0,'0',0) ,
        (1672,0,'Allow Click Lock Button Turn Over Of Items','',0,'\\170410','\\1704',0,'0',0) ,
        (1673,0,'Allow Click UnLock Button Turn Over Of Items','',0,'\\170411','\\1704',0,'0',0) ,
        (1321,0,'Allow Click Add Item Turn Over Of Items','',0,'\\170412','\\1704',0,'0',0) ,
        (1322,0,'Allow Click Edit Item Turn Over Of Items','',0,'\\170413','\\1704',0,'0',0) ,
        (1323,0,'Allow Click Delete Item Turn Over Of Items','',0,'\\170414','\\1704',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HO','/module/hris/HO','Turn Over of Items','fa fa-sticky-note sub_menu_ico',1118)";
    } //end function

    public function hr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1158,0,'Return Of Items','',0,'\\1707','$parent',0,'0',0) ,
        (1159,0,'Allow View Return Of Items','',0,'\\170701','\\1707',0,'0',0) ,
        (1160,0,'Allow Click Edit Button Return Of Items','',0,'\\170702','\\1707',0,'0',0) ,
        (1161,0,'Allow Click New Button Return Of Items','',0,'\\170703','\\1707',0,'0',0) ,
        (1162,0,'Allow Click Save Button Return Of Items','',0,'\\170704','\\1707',0,'0',0) ,
        (1163,0,'Allow Click Change Code Return Of Items','',0,'\\170705','\\1707',0,'0',0) ,
        (1164,0,'Allow Click Delete Button Return Of Items','',0,'\\170706','\\1707',0,'0',0) ,
        (1165,0,'Allow Click Print Button Return Of Items','',0,'\\170707','\\1707',0,'0',0) ,
        (1166,0,'Allow Click Post Button Return Of Items','',0,'\\170708','\\1707',0,'0',0) ,
        (1167,0,'Allow Click UnPost Button Return Of Items','',0,'\\170709','\\1707',0,'0',0) ,
        (1678,0,'Allow Click Lock Button Return Of Items','',0,'\\170710','\\1707',0,'0',0) ,
        (1679,0,'Allow Click UnLock Button Return Of Items','',0,'\\170711','\\1707',0,'0',0) ,
        (1333,0,'Allow Click Add Item Return Of Items','',0,'\\170712','\\1707',0,'0',0) ,
        (1334,0,'Allow Click Edit Item Return Of Items','',0,'\\170713','\\1707',0,'0',0) ,
        (1335,0,'Allow Click Delete Item Return Of Items','',0,'\\170714','\\1707',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HR','/module/hris/HR','Return of Items','fa fa-sticky-note sub_menu_ico',1158)";
    } //end function

    public function hi($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1178,0,'Incident Report','',0,'\\1708','$parent',0,'0',0) ,
        (1179,0,'Allow View Incident Report','',0,'\\170801','\\1708',0,'0',0) ,
        (1180,0,'Allow Click Edit Button Incident Report','',0,'\\170802','\\1708',0,'0',0) ,
        (1181,0,'Allow Click New Button Incident Report','',0,'\\170803','\\1708',0,'0',0) ,
        (1182,0,'Allow Click Save Button Incident Report','',0,'\\170804','\\1708',0,'0',0) ,
        (1183,0,'Allow Click Change Code Incident Report','',0,'\\170805','\\1708',0,'0',0) ,
        (1184,0,'Allow Click Delete Button Incident Report','',0,'\\170806','\\1708',0,'0',0) ,
        (1185,0,'Allow Click Print Button Incident Report','',0,'\\170807','\\1708',0,'0',0) ,
        (1186,0,'Allow Click Post Button Incident Report','',0,'\\170808','\\1708',0,'0',0) ,
        (1187,0,'Allow Click UnPost Button Incident Report','',0,'\\170809','\\1708',0,'0',0) ,
        (1703,0,'Allow Click Lock Button Incident Report','',0,'\\170810','\\1708',0,'0',0) ,
        (1704,0,'Allow Click UnLock Button Incident Report','',0,'\\170811','\\1708',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HI','/module/hris/HI','Incident Report','fa fa-sticky-note sub_menu_ico',1178)";
    } //end function

    public function hn($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1208,0,'Notice to Explain','',0,'\\1709','$parent',0,'0',0) ,
        (1209,0,'Allow View Notice to Explain','',0,'\\170901','\\1709',0,'0',0) ,
        (1210,0,'Allow Click Edit Button Notice to Explain','',0,'\\170902','\\1709',0,'0',0) ,
        (1211,0,'Allow Click New Button Notice to Explain','',0,'\\170903','\\1709',0,'0',0) ,
        (1212,0,'Allow Click Save Button Notice to Explain','',0,'\\170904','\\1709',0,'0',0) ,
        (1213,0,'Allow Click Change Code Notice to Explain','',0,'\\170905','\\1709',0,'0',0) ,
        (1214,0,'Allow Click Delete Button Notice to Explain','',0,'\\170906','\\1709',0,'0',0) ,
        (1215,0,'Allow Click Print Button Notice to Explain','',0,'\\170907','\\1709',0,'0',0) ,
        (1216,0,'Allow Click Post Button Notice to Explain','',0,'\\170908','\\1709',0,'0',0) ,
        (1217,0,'Allow Click UnPost Button Notice to Explain','',0,'\\170909','\\1709',0,'0',0) ,
        (1705,0,'Allow Click Lock Button Notice to Explain','',0,'\\170910','\\1709',0,'0',0) ,
        (1706,0,'Allow Click UnLock Button Notice to Explain','',0,'\\170911','\\1709',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HN','/module/hris/HN','Notice to Explain','fa fa-sticky-note sub_menu_ico',1208)";
    } //end function

    public function hd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1198,0,'Notice of Disciplinary Action','',0,'\\1710','$parent',0,'0',0) ,
        (1199,0,'Allow View Notice of Disciplinary Action','',0,'\\171001','\\1710',0,'0',0) ,
        (1200,0,'Allow Click Edit Button Notice of Disciplinary Action','',0,'\\171002','\\1710',0,'0',0) ,
        (1201,0,'Allow Click New Button Notice of Disciplinary Action','',0,'\\171003','\\1710',0,'0',0) ,
        (1202,0,'Allow Click Save Button Notice of Disciplinary Action','',0,'\\171004','\\1710',0,'0',0) ,
        (1203,0,'Allow Click Change Code Notice of Disciplinary Action','',0,'\\171005','\\1710',0,'0',0) ,
        (1204,0,'Allow Click Delete Button Notice of Disciplinary Action','',0,'\\171006','\\1710',0,'0',0) ,
        (1205,0,'Allow Click Print Button Notice of Disciplinary Action','',0,'\\171007','\\1710',0,'0',0) ,
        (1206,0,'Allow Click Post Button Notice of Disciplinary Action','',0,'\\171008','\\1710',0,'0',0) ,
        (1207,0,'Allow Click UnPost Button Notice of Disciplinary Action','',0,'\\171009','\\1710',0,'0',0) ,
        (1707,0,'Allow Click Lock Button Notice of Disciplinary Action','',0,'\\171010','\\1710',0,'0',0) ,
        (1708,0,'Allow Click UnLock Button Notice of Disciplinary Action','',0,'\\171011','\\1710',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HD','/module/hris/HD','Notice of Disciplinary Action','fa fa-sticky-note sub_menu_ico',1198)";
    } //end function


    public function hc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1228,0,'Clearance','',0,'\\1711','$parent',0,'0',0) ,
        (1229,0,'Allow View Clearance','',0,'\\171101','\\1711',0,'0',0) ,
        (1230,0,'Allow Click Edit Button Clearance','',0,'\\171102','\\1711',0,'0',0) ,
        (1231,0,'Allow Click New Button Clearance','',0,'\\171103','\\1711',0,'0',0) ,
        (1232,0,'Allow Click Save Button Clearance','',0,'\\171104','\\1711',0,'0',0) ,
        (1233,0,'Allow Click Change Code Clearance','',0,'\\171105','\\1711',0,'0',0) ,
        (1234,0,'Allow Click Delete Button Clearance','',0,'\\171106','\\1711',0,'0',0) ,
        (1235,0,'Allow Click Print Button Clearance','',0,'\\171107','\\1711',0,'0',0) ,
        (1236,0,'Allow Click Post Button Clearance','',0,'\\171108','\\1711',0,'0',0) ,
        (1237,0,'Allow Click UnPost Button Clearance','',0,'\\171109','\\1711',0,'0',0) ,
        (1709,0,'Allow Click Lock Button Clearance','',0,'\\171110','\\1711',0,'0',0) ,
        (1710,0,'Allow Click UnLock Button Clearance','',0,'\\171111','\\1711',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HC','/module/hris/HC','Clearance','fa fa-sticky-note sub_menu_ico',1228)";
    } //end function

    public function hs($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1168,0,'Employment Status Entry / Change','',0,'\\1712','$parent',0,'0',0) ,
        (1169,0,'Allow View Employment Status Entry / Change','',0,'\\171201','\\1712',0,'0',0) ,
        (1170,0,'Allow Click Edit Button Employment Status Entry / Change','',0,'\\171202','\\1712',0,'0',0) ,
        (1171,0,'Allow Click New Button Employment Status Entry / Change','',0,'\\171203','\\1712',0,'0',0) ,
        (1172,0,'Allow Click Save Button Employment Status Entry / Change','',0,'\\171204','\\1712',0,'0',0) ,
        (1173,0,'Allow Click Change Code Employment Status Entry / Change','',0,'\\171205','\\1712',0,'0',0) ,
        (1174,0,'Allow Click Delete Button Employment Status Entry / Change','',0,'\\171206','\\1712',0,'0',0) ,
        (1175,0,'Allow Click Print Button Employment Status Entry / Change','',0,'\\171207','\\1712',0,'0',0) ,
        (1176,0,'Allow Click Post Button Employment Status Entry / Change','',0,'\\171208','\\1712',0,'0',0) ,
        (1177,0,'Allow Click UnPost Button Employment Status Entry / Change','',0,'\\171209','\\1712',0,'0',0) ,
        (1701,0,'Allow Click Lock Button Employment Status Entry / Change','',0,'\\171210','\\1712',0,'0',0) ,
        (1702,0,'Allow Click UnLock Button Employment Status Entry / Change','',0,'\\171211','\\1712',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'hs','/module/hris/hs','Employment Status Change','fa fa-sticky-note sub_menu_ico',1168)";
    } //end function

    public function empndahistory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1448,0,'Employment NDA History','',0,'\\1713','$parent',0,'0',0),
        (1344,0,'Allow View Employment NDA History','',0,'\\171301','\\1713',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'empndahistory','/ledgergrid/hris/empndahistory','Employment NDA History','fa fa-sticky-note sub_menu_ico',1448)";
    } //end function

    public function empchangehistory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1450,0,'Employment Change History','',0,'\\1714','$parent',0,'0',0),
              (1345,0,'Allow View Employment Change History','',0,'\\171401','\\1714',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'empchangehistory','/ledgergrid/hris/empchangehistory','Employment Change History','fa fa-sticky-note sub_menu_ico',1450)";
    } //end function

    public function parentpayrollsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1269,0,'PAYROLL SETUP','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'PAYROLL SETUP',$sort,'fa fa-coins',',,')";
    } //end function


    public function division($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1410,1,'Division','',0,'\\1901','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'division','/tableentries/payrollsetup/division','Division','fa fa-boxes sub_menu_ico',1410)";
    } //end function

    public function rank($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2630,1,'Rank','',0,'\\1904','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entryrank','/tableentries/payrollsetup/entryrank','Rank','fa fa-boxes sub_menu_ico',2630)";
    } //end function

    public function section($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1470,1,'Section','',0,'\\1902','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'section','/tableentries/payrollsetup/section','Section','fa fa-boxes sub_menu_ico',1470)";
    } //end function

    public function paygroup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1480,1,'Pay Group','',0,'\\1903','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'paygroup','/tableentries/payrollsetup/paygroup','Pay Group','fa fa-users sub_menu_ico',1480)";
    } //end function

    public function annualtax($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1500,1,'Annual Tax','',0,'\\1905','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'annualtax','/tableentries/payrollsetup/annualtax','Annual Tax','fa fa-percent sub_menu_ico',1500)";
    } //end function

    public function philhealth($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1510,1,'Philhealth','',0,'\\1906','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'philhealth','/tableentries/payrollsetup/philhealth','Philhealth','fa fa-percent sub_menu_ico',1510)";
    } //end function


    public function sss($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1449,1,'SSS','',0,'\\1907','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'sss','/tableentries/payrollsetup/sss','SSS','fa fa-percent sub_menu_ico',1449)";
    } //end function

    public function pagibig($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1451,1,'Pag-ibig','',0,'\\1908','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pagibig','/tableentries/payrollsetup/pagibig','Pag-ibig','fa fa-percent sub_menu_ico',1451)";
    } //end function

    public function tax($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1520,1,'Tax','',0,'\\1909','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tax','/tableentries/payrollsetup/tax','Withholding Tax','fa fa-percent sub_menu_ico',1520)";
    } //end function

    public function holiday($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1530,1,'Holiday','',0,'\\1910','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'holiday','/tableentries/payrollsetup/holiday','Holiday','fa fa-boxes sub_menu_ico',1530)";
    } //end function

    public function leavesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1540,1,'Leave Setup','',0,'\\1911','$parent',0,0,0) ,
        (1541,1,'Allow Click New Button Leave Setup','',0,'\\191101','\\1911',0,0,0) ,
        (1542,1,'Allow Click Save Button Leave Setup','',0,'\\191102','\\1911',0,0,0) ,
        (1543,1,'Allow Click Delete Button Leave Setup','',0,'\\191103','\\1911',0,0,0) ,
        (1544,1,'Allow Click Print Button Leave Setup','',0,'\\191104','\\1911',0,0,0) ,
        (1545,1,'Allow View Leave Setup','',0,'\\191105','\\1911',0,0,0) ,
        (1728,1,'Allow Click Edit Button Leave Setup','',0,'\\191106','\\1911',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leavesetup','/ledgergrid/payrollsetup/leavesetup','Leave Setup','fa fa-calendar-alt sub_menu_ico',1540)";
    } //end function

    public function leavebatchcreation($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2797,1,'Leave Batch Creation','',0,'\\1914','$parent',0,0,0) ,
        (2798,1,'Allow Click New Button Leave Batch Creation','',0,'\\191401','\\1914',0,0,0) ,
        (2799,1,'Allow Click Save Button Leave Batch Creation','',0,'\\191402','\\1914',0,0,0) ,
        (2800,1,'Allow Click Delete Button Leave Batch Creation','',0,'\\191403','\\1914',0,0,0) ,
        (2801,1,'Allow Click Print Button Leave Batch Creation','',0,'\\191404','\\1914',0,0,0) ,
        (2802,1,'Allow View Leave Batch Creation','',0,'\\191405','\\1914',0,0,0) ,
        (2803,1,'Allow Click Edit Button Leave Batch Creation','',0,'\\191406','\\1914',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leavebatchcreation','/headtable/payrollcustomform/leavebatchcreation','Leave Batch Creation','fa fa-calendar-alt sub_menu_ico',2797)";
    } //end function


    public function payrollaccount($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1490,1,'Payroll Accounts','',0,'\\191107','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'payrollaccount','/tableentries/payrollsetup/payrollaccounts','Payroll Accounts','fa fa-list sub_menu_ico',1490)";
    } //end function

    public function ratesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1550,1,'Rate Setup','',0,'\\1912','$parent',0,0,0) ,
        (1551,1,'Allow Click Save Button Rate Setup','',0,'\\191201','\\1912',0,0,0) ,
        (1552,1,'Allow Click Print Button Rate Setup','',0,'\\191202','\\1912',0,0,0) ,
        (1553,1,'Allow View Rate Setup','',0,'\\191203','\\1912',0,0,0) ,
        (1554,1,'Allow Click New Button Rate Setup','',0,'\\191204','\\1912',0,0,0) ,
        (1555,1,'Allow Click Delete Button Rate Setup','',0,'\\191205','\\1912',0,0,0) ,
        (1556,1,'Allow Click Edit Button Rate Setup','',0,'\\191206','\\1912',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ratesetup','/ledgergrid/payrollsetup/ratesetup','Rate Setup','fa fa-money-bill sub_menu_ico',1550)";
    } //end function

    public function shiftsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1560,1,'Shift Setup','',0,'\\1913','$parent',0,0,0) ,
        (1561,1,'Allow Click Save Button Shift Setup','',0,'\\191301','\\1913',0,0,0) ,
        (1562,1,'Allow Click Print Button Shift Setup','',0,'\\191302','\\1913',0,0,0) ,
        (1563,1,'Allow Click New Button Shift Setup','',0,'\\191303','\\1913',0,0,0) ,
        (1564,1,'Allow Click Delete Button Shift Setup','',0,'\\191304','\\1913',0,0,0) ,
        (1565,1,'Allow View Shift Setup','',0,'\\191305','\\1913',0,0,0) ,
        (1346,1,'Allow Click Edit Button Shift Setup','',0,'\\191306','\\1913',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'shiftsetup','/ledgergrid/payrollsetup/shiftsetup','Shift Setup','fa fa-user-clock sub_menu_ico',1560)";
    } //end function

    public function parentpayrolltransaction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1266,0,'PAYROLL TRANSACTION','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'PAYROLL TRANSACTION',$sort,'fa fa-hand-holding-usd',',,')";
    } //end function


    public function parentpayrollportal($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(150,0,'PAYROLL PORTAL','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'PAYROLL PORTAL',$sort,'fa fa-hand-holding-usd',',,')";
    } //end function


    public function employeepayroll($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1720,1,'Employee','',0,'\\2010','$parent',0,0,0) ,
        (1290,1,'Allow Click Button General','',0,'\\201001','\\2010',0,0,0) ,
        (1291,1,'Allow Click Button Dependents','',0,'\\201002','\\2010',0,0,0) ,
        (1292,1,'Allow Click Button Education','',0,'\\201003','\\2010',0,0,0) ,
        (1293,1,'Allow Click Button Employment','',0,'\\201004','\\2010',0,0,0) ,
        (1294,1,'Allow Click Button Rate','',0,'\\201005','\\2010',0,0,0) ,
        (1295,1,'Allow Click Button Loans','',0,'\\201006','\\2010',0,0,0) ,
        (1296,1,'Allow Click Button Advances','',0,'\\201007','\\2010',0,0,0) ,
        (1297,1,'Allow Click Button Contract','',0,'\\201008','\\2010',0,0,0) ,
        (1298,1,'Allow Click Button Allowance','',0,'\\201009','\\2010',0,0,0) ,
        (1299,1,'Allow Click Button Training','',0,'\\201010','\\2010',0,0,0) ,
        (1300,1,'Allow Click Button Turn Over and Return Items','',0,'\\201011','\\2010',0,0,0) ,
        (1301,1,'Allow View Employee Ledger','',0,'\\201012','\\2010',0,'0',0) ,
        (1302,1,'Allow Click Edit Button EMP','',0,'\\201013','\\2010',0,'0',0) ,
        (1303,1,'Allow Click New Button EMP','',0,'\\201014','\\2010',0,'0',0) ,
        (1304,1,'Allow Click Save Button EMP','',0,'\\201015','\\2010',0,'0',0) ,
        (1305,1,'Allow Click Change Code EMP','',0,'\\201016','\\2010',0,'0',0) ,
        (1306,1,'Allow Click Delete Button EMP','',0,'\\201017','\\2010',0,'0',0) ,
        (1307,1,'Allow Click Print Button EMP','',0,'\\201018','\\2010',0,'0',0),

        (2410,1,'Payroll Level 1','',0,'\\201019','\\2010',0,0,0),
        (2411,1,'Payroll Level 2','',0,'\\201020','\\2010',0,0,0),
        (2412,1,'Payroll Level 3','',0,'\\201021','\\2010',0,0,0),
        (2413,1,'Payroll Level 4','',0,'\\201022','\\2010',0,0,0),
        (2414,1,'Payroll Level 5','',0,'\\201023','\\2010',0,0,0),
        (2415,1,'Payroll Level 6','',0,'\\201024','\\2010',0,0,0),
        (2416,1,'Payroll Level 7','',0,'\\201025','\\2010',0,0,0),
        (2417,1,'Payroll Level 8','',0,'\\201026','\\2010',0,0,0),
        (2418,1,'Payroll Level 9','',0,'\\201027','\\2010',0,0,0),
        (2419,1,'Payroll Level 10','',0,'\\201028','\\2010',0,0,0)
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'employeemasterfile','/ledgergrid/payroll/employee','Employee','fa fa-user sub_menu_ico',1720)";
    } //end function

    public function myinfo($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2804,1,'My Info','',0,'\\2016','$parent',0,0,0) ,
        (2805,1,'Allow View My Info','',0,'\\201601','\\2016',0,0,0) ,
        (2806,1,'Allow Click Button Print My Info','',0,'\\201602','\\2016',0,0,0) ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'myinfo','/ledgergrid/payroll/myinfo','My Info','fa fa-file-alt sub_menu_ico',2804)";
    } //end function


    public function earningdeductionsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1580,1,'Earning And Deduction Setup','',0,'\\2002','$parent',0,0,0) ,
        (1581,1,'Allow Click Save Button Earning And Deduction Setup','',0,'\\200201','\\2002',0,0,0) ,
        (1582,1,'Allow Click Print Button Earning And Deduction Setup','',0,'\\200202','\\2002',0,0,0) ,
        (1583,1,'Allow Click New Button Earning And Deduction Setup','',0,'\\200203','\\2002',0,0,0) ,
        (1584,1,'Allow Click Delete Button Earning And Deduction Setup','',0,'\\200204','\\2002',0,0,0) ,
        (1585,1,'Allow View Earning And Deduction Setup','',0,'\\200205','\\2002',0,0,0),
        (1586,1,'Allow Edit Earning And Deduction Setup','',0,'\\200206','\\2002',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'earningdeductionsetup','/ledgergrid/payrollsetup/earningdeductionsetup','Earning And Deduction Setup','fa fa-coins sub_menu_ico',1580)";
    } //end function

    public function advancesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2636,1,'Advance Setup','',0,'\\2005','$parent',0,0,0) ,
        (2637,1,'Allow Click Save Button Advance Setup','',0,'\\200501','\\2005',0,0,0) ,
        (2638,1,'Allow Click Print Button Advance Setup','',0,'\\200502','\\2005',0,0,0) ,
        (2639,1,'Allow Click New Button Advance Setup','',0,'\\200503','\\2005',0,0,0) ,
        (2640,1,'Allow Click Delete Button Advance Setup','',0,'\\200504','\\2005',0,0,0) ,
        (2641,1,'Allow View Advance Setup','',0,'\\200505','\\2005',0,0,0),
        (2642,1,'Allow Edit Advance Setup','',0,'\\200506','\\2005',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'advancesetup','/ledgergrid/payrollsetup/advancesetup','Advance Setup','fa fa-coins sub_menu_ico',2636)";
    } //end function

    public function leaveapplication($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1590,1,'Leave Application','',0,'\\2003','$parent',0,0,0) ,
        (1591,1,'Allow Click Save Button Leave Application','',0,'\\200301','\\2003',0,0,0) ,
        (1592,1,'Allow Click Print Button Leave Application','',0,'\\200302','\\2003',0,0,0) ,
        (1593,1,'Allow Click New Button Leave Application','',0,'\\200303','\\2003',0,0,0) ,
        (1594,1,'Allow Click Delete Button Leave Application','',0,'\\200304','\\2003',0,0,0) ,
        (1595,1,'Allow View Leave Application','',0,'\\200305','\\2003',0,0,0) ,
        (1596,1,'Allow Click Edit Button Leave Application','',0,'\\200306','\\2003',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leaveapplication','/ledgergrid/payroll/leaveapplication','Leave Application','fa fa-calendar-alt sub_menu_ico',1590)";
    } //end function

    public function pieceentry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1600,1,'Piece Entry','',0,'\\2004','$parent',0,0,0) ,
        (1601,1,'Allow View Piece Entry','',0,'\\200401','\\2004',0,0,0) ,
        (1602,1,'Allow Click Create Button','',0,'\\200402','\\2004',0,0,0) ,
        (1603,1,'Allow Edit Piece Entry','',0,'\\200403','\\2004',0,0,0) ,
        (1604,1,'Allow CLick Delete Button','',0,'\\200404','\\2004',0,0,0) ,
        (1605,1,'Allow Click Print Button','',0,'\\200405','\\2004',0,0,0) ,
        (1606,1,'Allow Click Save all Entry','',0,'\\200406','\\2004',0,0,0)
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pieceentry','/headtable/payrollcustomform/pieceentry','Piece Entry','fa fa-file-alt sub_menu_ico',1600)";
    } //end function

    public function batchsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1570,1,'Batch Setup','',0,'\\2001','$parent',0,0,0) ,
        (1347,1,'Allow Click Save Button Batch Setup','',0,'\\200101','\\2001',0,0,0) ,
        (1348,1,'Allow Click Print Button Batch Setup','',0,'\\200102','\\2001',0,0,0) ,
        (1349,1,'Allow Click New Button Batch Setup','',0,'\\200103','\\2001',0,0,0) ,
        (1350,1,'Allow Click Delete Button Batch Setup','',0,'\\200104','\\2001',0,0,0) ,
        (1351,1,'Allow View Batch Setup','',0,'\\200105','\\2001',0,0,0) ,
        (1352,1,'Allow Click Edit Button Batch Setup','',0,'\\200106','\\2001',0,0,0)
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'batchsetup','/ledger/payroll/batchsetup','Batch Setup','fa fa-list sub_menu_ico',1570)";
    } //end function

    public function emptimecard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1620,1,'Employee`s Timecard','',0,'\\2006','$parent',0,0,0) ,
        (1621,1,'Allow View Employee`s Timecard','',0,'\\200601','\\2006',0,0,0) ,
        (1622,1,'Allow Click Button Save Employee`s Timecard','',0,'\\200602','\\2006',0,0,0) ,
        (1623,1,'Allow Click Button Print Employee`s Timecard','',0,'\\200603','\\2006',0,0,0) ,
        (1624,1,'Allow Click Button Edit Employee`s Timecard','',0,'\\200604','\\2006',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'emptimecard','/headtable/payrollcustomform/emptimecard','Employee`s Timecard','fa fa-calendar-week sub_menu_ico',1620)";
    } //end function


    public function timecardsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2480,1,'Payroll Process','',0,'\\2007','$parent',0,0,0) ,
        (2481,1,'Allow View Payroll Process','',0,'\\200701','\\2007',0,0,0) ,
        (2482,1,'Allow Click Button Save Payroll Process','',0,'\\200702','\\2007',0,0,0) ,
        (2483,1,'Allow Click Button Print Payroll Process','',0,'\\200703','\\2007',0,0,0) ,
        (2484,1,'Allow Click Button Edit Payroll Process','',0,'\\200704','\\2007',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'timecardsetup','/headtable/payrollcustomform/payrollprocess','Payroll Process','fa fa-calculator sub_menu_ico',2480)";
    } //end function

    public function otapproval($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1630,1,'Overtime Approval','',0,'\\2008','$parent',0,0,0),
        (1631,1,'Allow View Overtime Approval','',0,'\\200801','\\2008',0,0,0),
        (1632,1,'Allow Click Button Save Overtime Approval','',0,'\\200802','\\2008',0,0,0),
        (1633,1,'Allow Click Button Print Overtime Approval','',0,'\\200803','\\2008',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'otapproval','/headtable/payrollentry/entryotapproval','Overtime Approval','fa fa-clock sub_menu_ico',1630)";
    } //end function

    public function payrollsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1650,1,'Payroll Setup','',0,'\\2009','$parent',0,0,0) ,
        (1651,1,'Allow View Payroll Setup','',0,'\\200901','\\2009',0,0,0) ,
        (1652,1,'Allow Click Button Entry Payroll Setup','',0,'\\200902','\\2009',0,0,0) ,
        (1653,1,'Allow Click Button Process Payroll Setup','',0,'\\200903','\\2009',0,0,0) ,
        (1353,1,'Allow Click Button Save Payroll Setup','',0,'\\200904','\\2009',0,0,0) ,
        (1354,1,'Allow Click Button Edit Payroll Setup','',0,'\\200905','\\2009',0,0,0) ,
        (1355,1,'Allow Click Add Item Payroll Setup','',0,'\\200906','\\2009',0,0,0) ,
        (1356,1,'Allow Click Button Print Payroll Setup','',0,'\\200907','\\2009',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'payrollsetup','/headtable/payrollcustomform/payrollentry','Payroll Entry','fa fa-sticky-note sub_menu_ico',1650)";
    } //end function

    public function obapplication($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2535,1,'OB Application','',0,'\\2011','$parent',0,0,0) ,
        (2536,1,'Allow Click Save Button OB Application','',0,'\\2011001','\\2011',0,0,0) ,
        (2537,1,'Allow Click Print Button OB Application','',0,'\\2011002','\\2011',0,0,0) ,
        (2538,1,'Allow Click New Button OB Application','',0,'\\2011003','\\2011',0,0,0) ,
        (2539,1,'Allow Click Delete Button OB Application','',0,'\\2011004','\\2011',0,0,0) ,
        (2540,1,'Allow View OB Application','',0,'\\2011005','\\2011',0,0,0),
        (2541,1,'Allow Click Edit Button OB Application','',0,'\\2011006','\\2011',0,0,0),
        (3627,1,'Allow View Dashboard OB Application','',0,'\\2011007','\\2011',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'obapplication','/ledger/payroll/obapplication','OB Application','fa fa-coins sub_menu_ico',2535)";
    } //end function

    public function otapplication($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3576,1,'OT Application','',0,'\\2017','$parent',0,0,0) ,
        (3581,1,'Allow View OT Application','',0,'\\2017005','\\2017',0,0,0),
        (3628,1,'Allow View Dashboard OT Application','',0,'\\2017006','\\2017',0,0,0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'otapplication','/ledger/payroll/otapplication','OT Application','fa fa-coins sub_menu_ico',3576)";
    } //end function

    public function leaveapplicationportal($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2375,1,'Leave Application Portal','',0,'\\2014','$parent',0,0,0) ,
      (2376,1,'Allow Click Save Button Leave Application Portal','',0,'\\201401','\\2014',0,0,0) ,
      (2377,1,'Allow Click Print Button Leave Application Portal','',0,'\\201402','\\2014',0,0,0) ,
      (2378,1,'Allow Click New Button Leave Application Portal','',0,'\\201403','\\2014',0,0,0) ,
      (2379,1,'Allow Click Delete Button Leave Application Portal','',0,'\\201404','\\2014',0,0,0) ,
      (2380,1,'Allow View Leave Application Portal','',0,'\\201405','\\2014',0,0,0) ,
      (2381,1,'Allow Click Edit Button Leave Application Portal','',0,'\\201406','\\2014',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leaveapplicationportal','/ledgergrid/payroll/leaveapplicationportal','Leave Application Portal','fa fa-calendar-alt sub_menu_ico',2375)";
    } //end function

    public function leaveapplicationportalapproval($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2864,1,'Leave Application Portal Approval','',0,'\\2015','$parent',0,0,0) ,
      (2865,1,'Allow View Leave Application Portal Approval','',0,'\\201501','\\\\2015',0,0,0) ,
      (2866,1,'Allow Click Save Button Leave Application Portal Approval','',0,'\\201502','\\2015',0,0,0) ,
      (2867,1,'Allow Click Print Button Leave Application Portal Approval','',0,'\\201503','\\2015',0,0,0),
      (3629,1,'Allow View Dashboard Leave Application Portal','',0,'\\201504','\\2015',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'leaveapplicationportalapproval','/headtable/payrollentry/leaveapplicationportalapproval','Leave Application Portal Approval','fa fa-calendar-alt sub_menu_ico',2864)";
    } //end function

    public function loanapplication($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2420,1,'Loan Application','',0,'\\2012','$parent',0,0,0) ,
        (2421,1,'Allow Click Save Button Loan Application','',0,'\\201201','\\2012',0,0,0) ,
        (2422,1,'Allow Click Print Button Loan Application','',0,'\\201202','\\2012',0,0,0) ,
        (2423,1,'Allow Click New Button Loan Application','',0,'\\201203','\\2012',0,0,0) ,
        (2424,1,'Allow Click Delete Button Loan Application','',0,'\\201204','\\2012',0,0,0) ,
        (2425,1,'Allow View Loan Application','',0,'\\201205','\\2012',0,0,0),
        (2426,1,'Allow Click Edit Button Loan Application','',0,'\\201206','\\2012',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'loanapplication','/ledgergrid/payroll/loanapplication','Loan Application','fa fa-coins sub_menu_ico',2420)";
    } //end function

    public function loanapplicationportal($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2452,1,'Loan Application Portal','',0,'\\2013','$parent',0,0,0) ,
        (2453,1,'Allow Click Save Button Loan Application Portal','',0,'\\201301','\\2013',0,0,0) ,
        (2454,1,'Allow Click Print Button Loan Application Portal','',0,'\\201302','\\2013',0,0,0) ,
        (2455,1,'Allow Click New Button Loan Application Portal','',0,'\\201303','\\2013',0,0,0) ,
        (2456,1,'Allow Click Delete Button Loan Application Portal','',0,'\\201304','\\2013',0,0,0) ,
        (2457,1,'Allow View Loan Application Portal','',0,'\\201305','\\2013',0,0,0),
        (2458,1,'Allow Click Edit Button Loan Application Portal','',0,'\\201306','\\2013',0,0,0),
        (3630,1,'Allow View Dashboard Loan Application Portal','',0,'\\201307','\\2013',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'loanapplicationportal','/ledgergrid/payroll/loanapplicationportal','Loan Application Portal','fa fa-coins sub_menu_ico',2452)";
    } //end function

    public function portalreports($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2780,1,'Portal Reports','',0,'\\2015','$parent',0,0,0) ,
        (2781,1,'Allow View Portal Reports','',0,'\\201501','\\2015',0,0,0) ,
        (2782,1,'Allow Click Button Print Portal Reports','',0,'\\201502','\\2015',0,0,0) ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'portalreports','/headtable/payrollcustomform/portalreports','Portal Reports','fa fa-file-alt sub_menu_ico',2780)";
    } //end function

    public function parentprojectsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1775,0,'PROJECT SETUP','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'PROJECT SETUP',$sort,'fa fa-sitemap sub_menu_ico',',,')";
    } //end function

    public function pm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1776,0,'Project Management','',0,'\\2201','$parent',0,'0',0),
        (1777,0,'Allow View Transaction PM','PM',0,'\\220101','\\2201',0,'0',0),
        (1778,0,'Allow Click Edit Button PM','',0,'\\220102','\\2201',0,'0',0),
        (1779,0,'Allow Click New Button PM','',0,'\\220103','\\2201',0,'0',0),
        (1780,0,'Allow Click Save Button PM','',0,'\\220104','\\2201',0,'0',0),
        (1782,0,'Allow Click Delete Button PM','',0,'\\220106','\\2201',0,'0',0),
        (1783,0,'Allow Click Print Button PM','',0,'\\220107','\\2201',0,'0',0),
        (1784,0,'Allow Click Lock Button PM','',0,'\\220108','\\2201',0,'0',0),
        (1785,0,'Allow Click UnLock Button PM','',0,'\\220109','\\2201',0,'0',0),
        (1786,0,'Allow Click Post Button PM','',0,'\\220110','\\2201',0,'0',0),
        (1787,0,'Allow Click UnPost  Button PM','',0,'\\220111','\\2201',0,'0',0),
        (1788,1,'Allow Click Add Subproject PM','',0,'\\220112','\\2201',0,'0',0),
        (1789,1,'Allow Click Edit Subproject PM','',0,'\\220113','\\2201',0,'0',0),
        (1790,1,'Allow Click Delete Subproject PM','',0,'\\220114','\\2201',0,'0',0),
        (1791,0,'Allow View Ref. Documents','',0,'\\220115','\\2201',0,'0',0),
        (1792,0,'Allow View Attachment PM','',0,'\\220116','\\2201',0,'0',0),
        (1793,0,'Allow View Summary PM','',0,'\\220117','\\2201',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PM','/module/construction/pm','Project Management','fa fa-tasks sub_menu_ico',1776)";
    } //end function


    public function stages($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1857,0,'Stage Setup','',0,'\\2202','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        $systemtype = $this->companysetup->getsystemtype($params);
        $label = 'Stage Setup';
        switch ($systemtype) {
            case 'MANUFACTURING':
                $label = 'Process';
                break;
        }
        return "($sort,$p,'stages','/tableentries/tableentry/entrystages','" . $label . "','fa fa-boxes sub_menu_ico',1857)";
    } //end function

    public function parentconstruction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1795,0,'CONSTRUCTION','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'CONSTRUCTION SYSTEM',$sort,'fa fa-hard-hat',',,')";
    } //end function

    public function al($params, $parent, $sort)
    {
        $p = $parent;
        return "($sort,$p,'AL','/actionlisting/actionlisting/approvallist','Approval List','fa fa-calendar-check sub_menu_ico',2235)";
    } //end function


    public function br($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2255,0,'Budget Request','',0,'\\2308','$parent',0,'0',0),
        (2256,0,'Allow View Transaction BR','BR',0,'\\230801','\\2308',0,'0',0),
        (2257,0,'Allow Click Edit Button BR','',0,'\\230802','\\2308',0,'0',0),
        (2258,0,'Allow Click New Button BR','',0,'\\230803','\\2308',0,'0',0),
        (2259,0,'Allow Click Save Button BR','',0,'\\230804','\\2308',0,'0',0),
        (2261,0,'Allow Click Delete Button BR','',0,'\\230806','\\2308',0,'0',0),
        (2262,0,'Allow Click Print Button BR','',0,'\\230807','\\2308',0,'0',0),
        (2263,0,'Allow Click Lock Button BR','',0,'\\230808','\\2308',0,'0',0),
        (2264,0,'Allow Click UnLock Button BR','',0,'\\230809','\\2308',0,'0',0),
        (2265,0,'Allow Click Post Button BR','',0,'\\230810','\\2308',0,'0',0),
        (2266,0,'Allow Click UnPost  Button BR','',0,'\\230811','\\2308',0,'0',0),
        (2267,1,'Allow Click Add Item BR','',0,'\\230812','\\2308',0,'0',0),
        (2268,1,'Allow Click Edit Item BR','',0,'\\230813','\\2308',0,'0',0),
        (2269,1,'Allow Click Delete Item BR','',0,'\\230814','\\2308',0,'0',0),
        (2270,1,'Allow Approve BR','',0,'\\230815','\\2308',0,'0',0),
        (2271,1,'Allow View All transaction BR','',0,'\\230816','\\2308',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BR','/module/construction/br','Budget Request','fa fa-hand-holding-usd sub_menu_ico',2255)";
    } //end function

    public function bl($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2273,0,'Budget Liquidation','',0,'\\2309','$parent',0,'0',0),
        (2274,0,'Allow View Transaction BL','BL',0,'\\230901','\\2309',0,'0',0),
        (2275,0,'Allow Click Edit Button BL','',0,'\\230902','\\2309',0,'0',0),
        (2276,0,'Allow Click New Button BL','',0,'\\230903','\\2309',0,'0',0),
        (2277,0,'Allow Click Save Button BL','',0,'\\230904','\\2309',0,'0',0),
        (2279,0,'Allow Click Delete Button BL','',0,'\\230906','\\2309',0,'0',0),
        (2280,0,'Allow Click Print Button BL','',0,'\\230907','\\2309',0,'0',0),
        (2281,0,'Allow Click Lock Button BL','',0,'\\230908','\\2309',0,'0',0),
        (2282,0,'Allow Click UnLock Button BL','',0,'\\230909','\\2309',0,'0',0),
        (2283,0,'Allow Click Post Button BL','',0,'\\230910','\\2309',0,'0',0),
        (2284,0,'Allow Click UnPost  Button BL','',0,'\\230911','\\2309',0,'0',0),
        (2285,1,'Allow Click Add Item BL','',0,'\\230912','\\2309',0,'0',0),
        (2286,1,'Allow Click Edit Item BL','',0,'\\230913','\\2309',0,'0',0),
        (2287,1,'Allow Click Delete Item BL','',0,'\\230914','\\2309',0,'0',0),
        (3575,1,'Allow View All transaction BL','',0,'\\230915','\\2309',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BL','/module/construction/bl','Budget Liquidation','fa fa-marker sub_menu_ico',2273)";
    } //end function

    public function prlisting($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3746,0,'Item Request Monitoring','',0,'\\2314','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'prlisting','/ledgergrid/ati/prlisting','Item Request Monitoring','fa fa-boxes sub_menu_ico',3746)";
    }

    public function barcodeassigning($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3887,0,'Item Barcode Assigning','',0,'\\2315','$parent',0,'0',0),
                (4388,0,'Allow View Only','',0,'\\231501','\\2315',0,'0',0)";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'barcodeassigning','/headtable/ati/barcodeassigning','Item Barcode Assigning','fa fa-barcode sub_menu_ico',3887)";
    }

    public function approversetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3888,0,'Approver Setup','',0,'\\897','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'entryapprovers','/tableentries/tableentry/entryapprovers','Approver Setup','fa fa-list sub_menu_ico',3888)";
    }

    public function packhouseloading($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3772,0,'Pack House Loading','',0,'\\517','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'packhouseloading','/module/sales/packhouseloading','Pack House Loading','fa fa-boxes sub_menu_ico',3772)";
    }

    public function released($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3773,0,'Releasing','',0,'\\518','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'released','/module/sales/released','Releasing','fa fa-check sub_menu_ico',3773)";
    }

    public function bq($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1796,0,'Bill of Quantity','',0,'\\2301','$parent',0,'0',0),
        (1797,0,'Allow View Transaction BQ','BQ',0,'\\230101','\\2301',0,'0',0),
        (1798,0,'Allow Click Edit Button BQ','',0,'\\230102','\\2301',0,'0',0),
        (1799,0,'Allow Click New Button BQ','',0,'\\230103','\\2301',0,'0',0),
        (1800,0,'Allow Click Save Button BQ','',0,'\\230104','\\2301',0,'0',0),
        (1802,0,'Allow Click Delete Button BQ','',0,'\\230106','\\2301',0,'0',0),
        (1803,0,'Allow Click Print Button BQ','',0,'\\230107','\\2301',0,'0',0),
        (1804,0,'Allow Click Lock Button BQ','',0,'\\230108','\\2301',0,'0',0),
        (1805,0,'Allow Click UnLock Button BQ','',0,'\\230109','\\2301',0,'0',0),
        (1806,0,'Allow Click Post Button BQ','',0,'\\230110','\\2301',0,'0',0),
        (1807,0,'Allow Click UnPost  Button BQ','',0,'\\230111','\\2301',0,'0',0),
        (1808,1,'Allow Click Add Item BQ','',0,'\\230112','\\2301',0,'0',0),
        (1809,1,'Allow Click Edit Item BQ','',0,'\\230113','\\2301',0,'0',0),
        (1810,1,'Allow Click Delete Item BQ','',0,'\\230114','\\2301',0,'0',0),
        (3595,1,'Allow Void Button','',0,'\\230115','\\2301',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BQ','/module/construction/bq','Bill of Quantity','fa fa-user sub_menu_ico',1796)";
    } //end function

    public function constructionpr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(618,0,'Purchase Requisition','',0,'\\407','$parent',0,'0',0),
        (619,0,'Allow View Transaction PR','PR',0,'\\40701','\\407',0,'0',0),
        (620,0,'Allow Click Edit Button PR','',0,'\\40702','\\407',0,'0',0),
        (621,0,'Allow Click New Button PR','',0,'\\40703','\\407',0,'0',0),
        (622,0,'Allow Click Save Button PR','',0,'\\40704','\\407',0,'0',0),
        (624,0,'Allow Click Delete Button PR','',0,'\\40706','\\407',0,'0',0),
        (625,0,'Allow Click Print Button PR','',0,'\\40707','\\407',0,'0',0),
        (626,0,'Allow Click Lock Button PR','',0,'\\40708','\\407',0,'0',0),
        (627,0,'Allow Click UnLock Button PR','',0,'\\40709','\\407',0,'0',0),
        (630,0,'Allow Click Post Button PR','',0,'\\40710','\\407',0,'0',0),
        (631,0,'Allow Click UnPost Button PR','',0,'\\40711','\\407',0,'0',0),
        (628,0,'Allow Change Amount PR','',0,'\\40713','\\407',0,'0',0),
        (814,1,'Allow Click Add Item PR','',0,'\\40714','\\407',0,'0',0),
        (815,1,'Allow Click Edit Item PR','',0,'\\40715','\\407',0,'0',0),
        (816,1,'Allow Click Delete Item PR','',0,'\\40716','\\407',0,'0',0),
        (2235,0,'Approval List','',0,'\\2312','$parent',0,'0',0),
        (2254,0,'Allow Approve PR','',0,'\\40717','\\407',0,'0',0),
        (2272,1,'Allow View All transaction PR','',0,'\\40718','\\407',0,'0',0),
        (3602,1,'Allow Void Button','',0,'\\40719','\\407',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PR','/module/construction/rq','Purchase Requisition','fa fa-list sub_menu_ico',618)";
    } //end function

    public function jr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2427,0,'Job Request','',0,'\\2311','$parent',0,'0',0),
        (2428,0,'Allow View Transaction JR','JR',0,'\\231101','\\2311',0,'0',0),
        (2429,0,'Allow Click Edit Button JR','',0,'\\231102','\\2311',0,'0',0),
        (2430,0,'Allow Click New Button JR','',0,'\\231103','\\2311',0,'0',0),
        (2431,0,'Allow Click Save Button JR','',0,'\\231104','\\2311',0,'0',0),
        (2433,0,'Allow Click Delete Button JR','',0,'\\231106','\\2311',0,'0',0),
        (2434,0,'Allow Click Print Button JR','',0,'\\231107','\\2311',0,'0',0),
        (2435,0,'Allow Click Lock Button JR','',0,'\\231108','\\2311',0,'0',0),
        (2436,0,'Allow Click UnLock Button JR','',0,'\\231109','\\2311',0,'0',0),
        (2437,0,'Allow Click Post Button JR','',0,'\\231110','\\2311',0,'0',0),
        (2438,0,'Allow Click UnPost Button JR','',0,'\\231111','\\2311',0,'0',0),
        (2439,0,'Allow Change Amount JR','',0,'\\231113','\\2311',0,'0',0),
        (2440,1,'Allow Click Add Item JR','',0,'\\231114','\\2311',0,'0',0),
        (2441,1,'Allow Click Edit Item JR','',0,'\\231115','\\2311',0,'0',0),
        (2442,1,'Allow Click Delete Item JR','',0,'\\231116','\\2311',0,'0',0),
        (2444,0,'Allow Approve JR','',0,'\\231117','\\2311',0,'0',0),
        (2445,1,'Allow View All transaction JR','',0,'\\231118','\\2311',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JR','/module/construction/jr','Job Request','fa fa-list sub_menu_ico',2427)";
    } //end function

    public function jo($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1811,0,'Job Order','',0,'\\2302','$parent',0,'0',0),
        (1812,0,'Allow View Transaction JO','JO',0,'\\230201','\\2302',0,'0',0),
        (1813,0,'Allow Click Edit Button JO','',0,'\\230202','\\2302',0,'0',0),
        (1814,0,'Allow Click New Button JO','',0,'\\230203','\\2302',0,'0',0),
        (1815,0,'Allow Click Save Button JO','',0,'\\230204','\\2302',0,'0',0),
        (1817,0,'Allow Click Delete Button JO','',0,'\\230206','\\2302',0,'0',0),
        (1818,0,'Allow Click Print Button JO','',0,'\\230207','\\2302',0,'0',0),
        (1819,0,'Allow Click Lock Button JO','',0,'\\230208','\\2302',0,'0',0),
        (1820,0,'Allow Click UnLock Button JO','',0,'\\230209','\\2302',0,'0',0),
        (1821,0,'Allow Click Post Button JO','',0,'\\230210','\\2302',0,'0',0),
        (1822,0,'Allow Click UnPost  Button JO','',0,'\\230211','\\2302',0,'0',0),
        (1823,1,'Allow Click Add Item JO','',0,'\\230212','\\2302',0,'0',0),
        (1824,1,'Allow Click Edit Item JO','',0,'\\230213','\\2302',0,'0',0),
        (1825,1,'Allow Click Delete Item JO','',0,'\\230214','\\2302',0,'0',0),        
        (3594,1,'Allow Void Button','',0,'\\230215','\\2302',0,'0',0)";


        $this->insertattribute($params, $qry);
        return "($sort,$p,'JO','/module/construction/jo','Job Order','fa fa-tasks sub_menu_ico',1811)";
    } //end function


    public function jc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1826,0,'Job Completion','',0,'\\2303','$parent',0,'0',0),
        (1827,0,'Allow View Transaction JC','JC',0,'\\230301','\\2303',0,'0',0),
        (1828,0,'Allow Click Edit Button JC','',0,'\\230302','\\2303',0,'0',0),
        (1829,0,'Allow Click New Button JC','',0,'\\230303','\\2303',0,'0',0),
        (1830,0,'Allow Click Save Button JC','',0,'\\230304','\\2303',0,'0',0),
        (1832,0,'Allow Click Delete Button JC','',0,'\\230306','\\2303',0,'0',0),
        (1833,0,'Allow Click Print Button JC','',0,'\\230307','\\2303',0,'0',0),
        (1834,0,'Allow Click Lock Button JC','',0,'\\230308','\\2303',0,'0',0),
        (1835,0,'Allow Click UnLock Button JC','',0,'\\230309','\\2303',0,'0',0),
        (1836,0,'Allow Click Post Button JC','',0,'\\230310','\\2303',0,'0',0),
        (1837,0,'Allow Click UnPost  Button JC','',0,'\\230311','\\2303',0,'0',0),
        (1838,1,'Allow Click Add Item JC','',0,'\\230312','\\2303',0,'0',0),
        (1839,1,'Allow Click Edit Item JC','',0,'\\230313','\\2303',0,'0',0),
        (1840,1,'Allow Click Delete Item JC','',0,'\\230314','\\2303',0,'0',0),
        (1841,0,'Allow View Transaction accounting JC','',0,'\\230315','\\2303',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'JC','/module/construction/jc','Job Completion','fa fa-check-double sub_menu_ico',1826)";
    } //end function

    public function mr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2288,0,'Material Request','',0,'\\2306','$parent',0,'0',0),
        (2289,0,'Allow View Transaction MR','MR',0,'\\230601','\\2306',0,'0',0),
        (2290,0,'Allow Click Edit Button MR','MR',0,'\\230602','\\2306',0,'0',0),
        (2291,0,'Allow Click New  Button MR','MI',0,'\\230603','\\2306',0,'0',0),
        (2292,0,'Allow Click Save Button MR','MR',0,'\\230604','\\2306',0,'0',0),
        (2294,0,'Allow Click Delete Button MR','MR',0,'\\230606','\\2306',0,'0',0),
        (2295,0,'Allow Click Print Button MR','MR',0,'\\230607','\\2306',0,'0',0),
        (2296,0,'Allow Click Lock Button MR','MR',0,'\\230608','\\2306',0,'0',0),
        (2297,0,'Allow Click UnLock Button MR','MR',0,'\\230609','\\2306',0,'0',0),
        (2298,0,'Allow Click Post Button MR','MR',0,'\\230610','\\2306',0,'0',0),
        (2299,0,'Allow Click UnPost  Button MR','MR',0,'\\230611','\\2306',0,'0',0),
        (2300,1,'Allow Click Add Item MR','',0,'\\230613','\\2306',0,'0',0),
        (2301,1,'Allow Click Edit Item MR','',0,'\\230614','\\2306',0,'0',0),
        (2302,1,'Allow Click Delete Item MR','',0,'\\230615','\\2306',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'MR','/module/construction/mr','Material Request','fa fa-tasks sub_menu_ico',2288)";
    } //end function

    public function wc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2303,0,'Work Accomplishment','',0,'\\2310','$parent',0,'0',0),
        (2304,0,'Allow View Transaction WC','WC',0,'\\231001','\\2310',0,'0',0),
        (2305,0,'Allow Click Edit Button WC','',0,'\\231002','\\2310',0,'0',0),
        (2306,0,'Allow Click New Button WC','',0,'\\231003','\\2310',0,'0',0),
        (2307,0,'Allow Click Save Button WC','',0,'\\231004','\\2310',0,'0',0),
        (2309,0,'Allow Click Delete Button WC','',0,'\\231006','\\2310',0,'0',0),
        (2310,0,'Allow Click Print Button WC','',0,'\\231007','\\2310',0,'0',0),
        (2311,0,'Allow Click Lock Button WC','',0,'\\231008','\\2310',0,'0',0),
        (2312,0,'Allow Click UnLock Button WC','',0,'\\231009','\\2310',0,'0',0),
        (2313,0,'Allow Click Post Button WC','',0,'\\231010','\\2310',0,'0',0),
        (2314,0,'Allow Click UnPost  Button WC','',0,'\\231011','\\2310',0,'0',0),
        (2315,1,'Allow Click Add Item WC','',0,'\\231012','\\2310',0,'0',0),
        (2316,1,'Allow Click Edit Item WC','',0,'\\231013','\\2310',0,'0',0),
        (2317,1,'Allow Click Delete Item WC','',0,'\\231014','\\2310',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'WC','/module/construction/wc','Work Accomplishment','fa fa-tasks sub_menu_ico',2303)";
    } //end function


    public function mi($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(768,0,'Material Issuance','',0,'\\2307','$parent',0,'0',0),
        (769,0,'Allow View Transaction MI','MI',0,'\\230701','\\2307',0,'0',0),
        (770,0,'Allow Click Edit Button MI','MI',0,'\\230702','\\2307',0,'0',0),
        (771,0,'Allow Click New  Button MI','MI',0,'\\230703','\\2307',0,'0',0),
        (772,0,'Allow Click Save Button MI','MI',0,'\\230704','\\2307',0,'0',0),
        (774,0,'Allow Click Delete Button MI','MI',0,'\\230706','\\2307',0,'0',0),
        (775,0,'Allow Click Print Button MI','MI',0,'\\230707','\\2307',0,'0',0),
        (776,0,'Allow Click Lock Button MI','MI',0,'\\230708','\\2307',0,'0',0),
        (777,0,'Allow Click UnLock Button MI','MI',0,'\\230709','\\2307',0,'0',0),
        (778,0,'Allow Click Post Button MI','MI',0,'\\230710','\\2307',0,'0',0),
        (779,0,'Allow Click UnPost  Button MI','MI',0,'\\230711','\\2307',0,'0',0),
        (783,0,'Allow View Transaction Accounting MI','MI',0,'\\230712','\\2307',0,'0',0),
        (2057,1,'Allow Click Add Item MI','',0,'\\230713','\\2307',0,'0',0),
        (2058,1,'Allow Click Edit Item MI','',0,'\\230714','\\2307',0,'0',0),
        (2059,1,'Allow Click Delete Item MI','',0,'\\230715','\\2307',0,'0',0)";

        $systype = $this->companysetup->getsystemtype($params);
        if ($systype == 'CAIMS') {
            $qry = $qry . ",(2234,1,'Allow View All transaction MI','',0,'\\230716','\\2307',0,'0',0)";
        }

        switch ($params['companyid']) {
            case 43: //mighty
                $qry .= ", (4490,1,'Allow Access Tripping Tab','',0,'\\230716','\\2307',0,'0',0)";
                $qry .= ", (4491,1,'Allow Access Dispatch Tab','',0,'\\230717','\\2307',0,'0',0)";
                $qry .= ", (4495,1,'Allow Trip Approved','',0,'\\230718','\\2307',0,'0',0)";
                break;
        }

        $this->insertattribute($params, $qry);
        return "($sort,$p,'MI','/module/construction/mi','Material Issuance','fa fa-people-carry sub_menu_ico',768)";
    } //end function

    public function mt($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2040,0,'Stock Transfer','',0,'\\2305','$parent',0,'0',0),
        (2041,0,'Allow View Transaction TS','TS',0,'\\230501','\\2305',0,'0',0),
        (2042,0,'Allow Click Edit Button  TS','',0,'\\230502','\\2305',0,'0',0),
        (2043,0,'Allow Click New Button TS','',0,'\\230503','\\2305',0,'0',0),
        (2044,0,'Allow Click Save Button TS','',0,'\\230504','\\2305',0,'0',0),
        (2046,0,'Allow Click Delete Button TS','',0,'\\230506','\\2305',0,'0',0),
        (2047,0,'Allow Click Print Button TS','',0,'\\230507','\\2305',0,'0',0),
        (2048,0,'Allow Click Lock Button TS','',0,'\\230508','\\2305',0,'0',0),
        (2049,0,'Allow Click UnLock Button TS','',0,'\\230509','\\2305',0,'0',0),
        (2050,0,'Allow Click Post Button TS','',0,'\\230510','\\2305',0,'0',0),
        (2051,0,'Allow Click UnPost Button TS','',0,'\\230511','\\2305',0,'0',0),
        (2052,1,'Allow Click Add Item TS','',0,'\\230512','\\2305',0,'0',0),
        (2053,1,'Allow Click Edit Item TS','',0,'\\230513','\\2305',0,'0',0),
        (2054,1,'Allow Click Delete Item TS','',0,'\\230514','\\2305',0,'0',0),
        (2055,1,'Allow Change Amount TS','',0,'\\230515','\\2305',0,'0',0),
        (2056,0,'Allow View Transaction accounting TS','',0,'\\230516','\\2305',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'MT','/module/construction/mt','Stock Transfer','fa fa-dolly-flatbed sub_menu_ico',2040)";
    } //end function

    public function pb($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1842,0,'Progress Billing','',0,'\\2304','$parent',0,'0',0),
        (1843,0,'Allow View Transaction PB','PB',0,'\\230401','\\2304',0,'0',0),
        (1844,0,'Allow Click Edit Button  PB','',0,'\\230402','\\2304',0,'0',0),
        (1845,0,'Allow Click New Button PB','',0,'\\230403','\\2304',0,'0',0),
        (1846,0,'Allow Click Save Button PB','',0,'\\230404','\\2304',0,'0',0),
        (1848,0,'Allow Click Delete Button PB','',0,'\\230406','\\2304',0,'0',0),
        (1849,0,'Allow Click Print Button PB','',0,'\\230407','\\2304',0,'0',0),
        (1850,0,'Allow Click Lock Button PB','',0,'\\230408','\\2304',0,'0',0),
        (1851,0,'Allow Click UnLock Button PB','',0,'\\230409','\\2304',0,'0',0),
        (1852,0,'Allow Click Post Button PB','',0,'\\230410','\\2304',0,'0',0),
        (1853,0,'Allow Click UnPost Button PB','',0,'\\230411','\\2304',0,'0',0),
        (1854,0,'Allow Click Add Account PB','',0,'\\230412','\\2304',0,'0',0),
        (1855,0,'Allow Click Edit Account PB','',0,'\\230413','\\2304',0,'0',0),
        (1856,0,'Allow Click Delete Account PB','',0,'\\230414','\\2304',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PB','/module/construction/pb','Progress Billing','fa fa-file-invoice-dollar sub_menu_ico',1842)";
    } //end function

    public function ba($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2747,0,'Billing Accomplishment','',0,'\\2313','$parent',0,'0',0),
        (2748,0,'Allow View Transaction BA','BS',0,'\\231301','\\2313',0,'0',0),
        (2749,0,'Allow Click Edit Button BA','',0,'\\231302','\\2313',0,'0',0),
        (2750,0,'Allow Click New Button BA','',0,'\\231303','\\2313',0,'0',0),
        (2751,0,'Allow Click Save Button BA','',0,'\\231304','\\2313',0,'0',0),
        (2753,0,'Allow Click Delete Button BA','',0,'\\231306','\\2313',0,'0',0),
        (2754,0,'Allow Click Print Button BA','',0,'\\231307','\\2313',0,'0',0),
        (2755,0,'Allow Click Lock Button BA','',0,'\\231308','\\2313',0,'0',0),
        (2756,0,'Allow Click UnLock Button BA','',0,'\\231309','\\2313',0,'0',0),
        (2757,0,'Allow Click Post Button BA','',0,'\\231310','\\2313',0,'0',0),
        (2758,0,'Allow Click UnPost  Button BA','',0,'\\231311','\\2313',0,'0',0),
        (2759,1,'Allow Click Add Item BA','',0,'\\231312','\\2313',0,'0',0),
        (2760,1,'Allow Click Edit Item BA','',0,'\\231313','\\2313',0,'0',0),
        (2761,1,'Allow Click Delete Item BA','',0,'\\231314','\\2313',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BA','/module/construction/ba','Billing Accomplishment','fa fa-user sub_menu_ico',2747)";
    } //end function

    public function parentannouncement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(385,0,'ANNOUNCEMENT','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'ANNOUNCEMENT',$sort,'support_agent',',,')";
    } //end function


    public function notice($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1362,0,'NOTICE','',0,'\\13001','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'NOTICE','/tableentries/announcemententry/entrynotice','Notice','fa fa-boxes sub_menu_ico',1362)";
    } //end function

    public function event($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1363,0,'EVENT','',0,'\\13002','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EVENT','/tableentries/announcemententry/entryevent','Event','fa fa-boxes sub_menu_ico',1363)";
    } //end function

    public function holidayannouncement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1364,0,'HOLIDAY','',0,'\\13003','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'HOLIDAY','/tableentries/announcemententry/entryholiday','Holiday','fa fa-boxes sub_menu_ico',1364)";
    } //end function

    public function parenttransactionutilities($params, $parent, $sort)
    {
        $systemtype = $this->companysetup->getsystemtype($params);

        $p = $parent;
        $parent = '\\' . $parent;
        switch ($systemtype) {
            case 'EAPPLICATION':
                $qry = "(360,0,'TRANSACTION UTILITIES','',0,'$parent','\\',0,'0',0),                
                (1730,1,'View Attach Documents','',0,'\\810','$parent',0,'0',0),
                (1731,1,'Attach Documents','',0,'\\81001','\\810',0,'0',0),
                (1732,1,'Download Documents','',0,'\\81002','\\810',0,'0',0),
                (1733,1,'Delete Documents','',0,'\\81003','\\810',0,'0',0),
                (4077,0,'Allow View All Application/Contracts','',0,'\\822','$parent',0,'0',0),
                (1729,1,'Allow Override Plan Limit','',0,'\\811','$parent',0,'0',0),
                (4098,0,'Allow to search & view transactions','',0,'\\823','$parent',0,'0',0)";
                break;
            default:
                $qry = "(360,0,'TRANSACTION UTILITIES','',0,'$parent','\\',0,'0',0),
                (368,0,'Allow View Transaction Cost','',0,'\\809','$parent',0,'0',0),
                (1729,1,'Allow Override Credit Limit','',0,'\\811','$parent',0,'0',0),
                (1730,1,'View Attach Documents','',0,'\\810','$parent',0,'0',0),
                (1731,1,'Attach Documents','',0,'\\81001','\\810',0,'0',0),
                (1732,1,'Download Documents','',0,'\\81002','\\810',0,'0',0),
                (1733,1,'Delete Documents','',0,'\\81003','\\810',0,'0',0),
                (1736,0,'Allow Override Below Cost','',0,'\\813','$parent',0,'0',0),
                (3687,0,'Allow View To Do','',0,'\\814','$parent',0,'0',0),
                (3723,0,'Restrict IP','',0,'\\818','$parent',0,'0',0)";
                break;
        }


        if ($this->companysetup->getserial($params)) {
            $qry .= ",(2999,0,'Allow Access enter Serial','',0,'\\815','$parent',0,'0',0)";
        }

        if ($params['companyid'] == 6) { //mitsukoshi
            $qry .= ",
            (2631,1,'Allow View SJ SRP','',0,'\\81004','$parent',0,'0',0),
            (2632,1,'Allow View SJ Lowest Price','',0,'\\81005','$parent',0,'0',0),
            (2633,1,'Allow View SJ Lower Price','',0,'\\81006','$parent',0,'0',0),
            (2634,1,'Allow View SJ Low Price','',0,'\\81007','$parent',0,'0',0)";
        }

        if ($this->companysetup->getmultibranch($params)) {
            $qry .= ",(4165,1,'Allow View All Branches (Reports)','',0,'\\81008','$parent',0,'0',0)";
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
        return "insert into left_parent(id,name,seq,class,doc) values($p,'TRANSACTION UTILITIES',$sort,'fa fa-cogs'," . $modules . ")";
    } //end function

    public function terms($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(598,0,'Terms','',0,'\\802','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'terms','/tableentries/tableentry/entryterms','Manage Terms','fa fa-calendar-check sub_menu_ico',598)";
    } //end function

    public function updatestd($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3743,0,'Update SGD Rates','',0,'\\820','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'updatestd','/headtable/othersettings/updatestd','Update SGD Rates','fas fa-funnel-dollar sub_menu_ico',3743)";
    } //end function

    public function ipsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3721,0,'IP Setup','',0,'\\817','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ipsetup','/tableentries/tableentry/ipsetup','IP Setup','fa fa-calendar-check sub_menu_ico',3721)";
    } //end function

    public function coagrouping($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3612,0,'COA Grouping','',0,'\\819','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'coagrouping','/tableentries/tableentry/coagrouping','COA Grouping','fa fa-calendar-check sub_menu_ico',3612)";
    } //end function


    public function prefix($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(599,0,'Document Prefix','',0,'\\803','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'prefix','/tableentries/tableentry/entryprefix','Manage Prefixes','fab fa-autoprefixer sub_menu_ico',599)";
    } //end function

    public function ewtsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(854,1,'EWT Setup','*129',0,'\\812','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ewtsetup','/tableentries/tableentry/entryewt','EWT Setup','fab fa-elementor sub_menu_ico',854)";
    } //end function

    public function audittrail($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(633,0,'Allow View Audit Trail','',0,'\\805','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'audittrail','/headtable/customformlisting/audittrail','Audit Trail','fa fa-user-shield sub_menu_ico ',633)";
    } //end function

    public function unposted_transaction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(652,0,'Unposted Transactions','',0,'\\806','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'unposted_transaction','/tableentries/tableentry/entryunposted_transaction','Unposted Transaction','fa fa-unlink  sub_menu_ico ',652)";
    } //end function

    public function forex($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1051,0,'Forex','',0,'\\807','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'forex','/tableentries/tableentry/entryforex','Forex','fas fa-funnel-dollar sub_menu_ico',1051)";
    } //end function


    public function executionlog($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4579,0,'Execution Logs','',0,'\\808','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'executionlog','/tableentries/tableentry/executionlog','Execution Logs','fas fa-unlink sub_menu_ico',4579)";
    } //end function

    public function changeitem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(632,0,'Change Item','',0,'\\804','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'changeitem','/tableentries/tableentry/entrychangeitem','Change Item','fa fa-undo sub_menu_ico',632)";
    } //end function


    public function parentdashboard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $companyid = $params['companyid'];

        $systype = $this->companysetup->getsystemtype($params);
        $qry = "(1053,0,'DASHBOARD','',0,'$parent','\\',0,'0',0),
                (876,1,'RR Transaction Dashboard','',0,'\\10101','$parent',0,'0',0),
                (877,1,'DM Transaction Dashboard','',0,'\\10102','$parent',0,'0',0),
                (878,1,'CV Transaction Dashboard','',0,'\\10103','$parent',0,'0',0),
                (879,1,'Account Payable Dashboard','',0,'\\10104','$parent',0,'0',0),
                (880,1,'Account Receivable Dashboard','',0,'\\10105','$parent',0,'0',0),
                (1734,1,'Purchase Yearly Graph Dashboard','',0,'\\10106','$parent',0,'0',0),
                (1735,1,'Sales Yearly Graph Dashboard','',0,'\\10107','$parent',0,'0',0),
                (3807,1,'Month To Date Sales Dashboard','',0,'\\10108','$parent',0,'0',0),                
                (3808,1,'Pending Canvass Sheet Dashboard','',0,'\\10109','$parent',0,'0',0),
                (3863,1,'Year To Date Sales Dashboard','',0,'\\10110','$parent',0,'0',0),
                (4011,1,'Sales per Branch Dashboard','',0,'\\10112','$parent',0,'0',0),
                (4012,1,'Monthly Sales Dashboard','',0,'\\10113','$parent',0,'0',0),
                (3894,1,'View Report Dashboard','',0,'\\10111','$parent',0,'0',0)";

        if ($systype == 'EAPPLICATION') {
            $qry = "(1053,0,'DASHBOARD','',0,'$parent','\\',0,'0',0),                
                (880,1,'Account Receivable Dashboard','',0,'\\10105','$parent',0,'0',0),
                (1735,1,'Sales Yearly Graph Dashboard','',0,'\\10107','$parent',0,'0',0),
                (4100,1,'Notice','',0,'\\10114','$parent',0,'0',0),
                (4101,1,'Calendar','',0,'\\10115','$parent',0,'0',0)";
        }

        if ($companyid == 40) { //cdo
            $qry = $qry . ", (4455,1,'Incoming Transfers Dashboard','',0,'\\10112','$parent',0,'0',0),
          (4456,1,'Request to Transfers Dashboard','',0,'\\10113','$parent',0,'0',0),
          (4457,1,'Request to PO Dashboard','',0,'\\10114','$parent',0,'0',0)";
        }

        $this->insertattribute($params, $qry);
        return "";
    } //end function




    public function parentbranch($params, $parent, $sort)
    {
        if ($params['companyid'] == 24) return ""; //goodfound

        if ($this->companysetup->getmultibranch($params)) {
            $p = $parent;
            return "insert into left_parent(id,name,seq,class,doc) values($p,'BRANCH',$sort,'fa fa-home',',branch')";
        } else {
            return "";
        }
    } //end function

    public function branch($params, $parent, $sort)
    {
        $p = $parent;
        if ($this->companysetup->getmultibranch($params)) {
            return "($sort,$p,'branch','/tableentries/tableentry/entrycenter','Branch Masterfile','fa fa-boxes sub_menu_ico',798)";
        } else {
            return "";
        }
    } //end function

    public function parentaccountutilities($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1700,0,'ACCOUNT UTILITIES','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'ACCOUNT UTILITIES',$sort,'fa fa-users-cog',',useraccess,branchaccess,projectaccess')";
    } //end function

    public function useraccess($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(362,0,'Manage useraccess','',0,'\\2101','$parent',0,'0',0),
        (2580,0,'Manage Masterfile User Accounts','',0,'\\2104','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'useraccess','/utilityuser/accutilities/useraccess','Manage useraccess','fa fa-users sub_menu_ico',362)";
    } //end function

    public function companyinfoaccess($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(798,0,'Company Information','',0,'\\2102','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'companyinfo','/tableentries/tableentry/entrycenter','Company Information','fa fa-sitemap sub_menu_ico',798)";
    }

    public function branchaccess($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(797,0,'Branch Access','',0,'\\2102','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'branchaccess','/utilitybranch/accutilities/branchaccess','Branch Access','fa fa-building sub_menu_ico',797)";
    } //end function

    public function projectaccess($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2162,0,'Project Access','',0,'\\2103','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'branchaccess','/headtable/construction/entryprojectaccess','Project Access','fa fa-building sub_menu_ico',2162)";
    } //end function

    public function othersettings($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;


        $qry = "(2221,0,'Other Settings','',0,'\\891','$parent',0,'0',0),
        (2222,0,'Allow Update System Lock Date','',0,'\\89101','\\891',0,'0',0)";

        if ($params['companyid'] == 16) { //ati
            $qry .= ", (4581,0,'Allow Update Surcharge','',0,'\\89102','\\891',0,'0',0)";
        };

        $this->insertattribute($params, $qry);
        return "($sort,$p,'othersettings','/headtable/othersettings/othersettings','Other Settings','fa fa-cog sub_menu_ico',2221)";
    } //end function

    public function gradeutility($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2727, 0, 'Grade Utility', '', 0, '\\893', '$parent', 0, '0', 0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'gradeutility','/tableentries/enrollmentgradeentry/en_gradeutility','Grade Utility','fa fa-sticky-note sub_menu_ico',2727)";
    }

    public function uploadingutililty($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2584,0,'Uploading Utilility','',0,'\\892','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'uploadingutililty','/headtable/othersettings/uploadingutililty','Uploading Utilility','fa fa-upload sub_menu_ico',2584)";
    } //end function       

    public function apiutility($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3813,0,'Downloading Utility','',0,'\\893','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'terms','/headtable/othersettings/downloadapi','Downloading Utility','fa fa-calendar-check sub_menu_ico',3813)";
    } //end function

    public function poterms($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2871,0,'PO Terms','',0,'\\894','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'poterms','/tableentries/tableentry/poterms','PO Terms','fa fa-upload sub_menu_ico',2871)";
    } //end function    

    public function billableitemssetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(138,0,'Billable Items Setup','',0,'\\814','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'billableitemssetup','/tableentries/mallsetup/billableitemssetup','Billable Items Setup','fa fa-coins sub_menu_ico',138)";
    } //end function 

    public function parentcrm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2549,0,'CRM','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'CRM',$sort,'fa fa-people-arrows',',crm')";
    } //end function

    public function parentoperation($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2293,0,'OPERATION','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'OPERATION',$sort,'fa fa-solid fa-splotch',',operation')";
    } //end function

    public function ld($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2551,0,'Lead','',0,'\\2901','$parent',0,'0',0),
        (2552,0,'Allow View Transaction LD','LD',0,'\\290101','\\2901',0,'0',0),
        (2553,0,'Allow Click Edit Button LD','',0,'\\290102','\\2901',0,'0',0),
        (2554,0,'Allow Click New Button LD','',0,'\\290103','\\2901',0,'0',0),
        (2555,0,'Allow Click Save Button LD','',0,'\\290104','\\2901',0,'0',0),
        (2557,0,'Allow Click Delete Button LD','',0,'\\290106','\\2901',0,'0',0),
        (2558,0,'Allow Click Print Button LD','',0,'\\290107','\\2901',0,'0',0),
        (2559,0,'Allow Click Lock Button LD','',0,'\\290108','\\2901',0,'0',0),
        (2560,0,'Allow Click UnLock Button LD','',0,'\\290109','\\2901',0,'0',0),
        (2561,0,'Allow Click Post Button LD','',0,'\\290111','\\2901',0,'0',0),
        (2562,0,'Allow Click UnPost Button LD','',0,'\\290112','\\2901',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'LD','/module/crm/ld','Lead','fa fa-tasks sub_menu_ico',2551)";
    } //end function

    public function op($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2563,0,'Sales Activity Module','',0,'\\2902','$parent',0,'0',0),
        (2564,0,'Allow View Transaction SA','OP',0,'\\290201','\\2902',0,'0',0),
        (2565,0,'Allow Click Edit Button SA','',0,'\\290202','\\2902',0,'0',0),
        (2566,0,'Allow Click New  Button SA','',0,'\\290203','\\2902',0,'0',0),
        (2567,0,'Allow Click Save Button SA','',0,'\\290204','\\2902',0,'0',0),
        (2569,0,'Allow Click Delete Button SA','',0,'\\290206','\\2902',0,'0',0),
        (2570,0,'Allow Click Print Button SA','',0,'\\290207','\\2902',0,'0',0),
        (2571,0,'Allow Click Lock Button SA','',0,'\\290208','\\2902',0,'0',0),
        (2572,0,'Allow Click UnLock Button SA','',0,'\\290209','\\2902',0,'0',0),
        (2573,0,'Allow Change Amount  SA','',0,'\\290210','\\2902',0,'0',0),
        (2575,0,'Allow Click Post Button SA','',0,'\\290212','\\2902',0,'0',0),
        (2576,0,'Allow Click UnPost  Button SA','',0,'\\290213','\\2902',0,'0',0),
        (2577,1,'Allow Click Add Item SA','',0,'\\290214','\\2902',0,'0',0),
        (2578,1,'Allow Click Edit Item SA','',0,'\\290215','\\2902',0,'0',0),
        (2579,1,'Allow Click Delete Item SA','',0,'\\290216','\\2902',0,'0',0),
        (2875,1,'Allow Create Profile','',0,'\\290217','\\2902',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'OP','/module/crm/op','Sales Activity','fa fa-people-arrows sub_menu_ico',2563)";
    } //end function


    public function salesgroup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2593,0,'Sales Group','',0,'\\2903','$parent',0,'0',0),
        (2843,0,'Allow Add','',0,'\\290301','\\2903',0,'0',0),
        (2844,0,'Allow Save All','',0,'\\290302','\\2903',0,'0',0),
        (2845,0,'Allow Print','',0,'\\290303','\\2903',0,'0',0),
        (2846,0,'Allow Save','',0,'\\290304','\\2903',0,'0',0),
        (2847,0,'Allow Delete','',0,'\\290305','\\2903',0,'0',0)
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SALESGROUP','/tableentries/crm/salesgroup','Sales Group','fa fa-sitemap sub_menu_ico',2593)";
    } //end function

    public function seminar($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2594,0,'Seminar','',0,'\\2904','$parent',0,'0',0),
        (2848,0,'Allow Add','',0,'\\290401','\\2904',0,'0',0),
        (2849,0,'Allow Save All','',0,'\\290402','\\2904',0,'0',0),
        (2850,0,'Allow Print','',0,'\\290403','\\2904',0,'0',0),
        (2851,0,'Allow Save','',0,'\\290404','\\2904',0,'0',0),
        (2852,0,'Allow Delete','',0,'\\290405','\\2904',0,'0',0),
        (3666,0,'Allow Editing of Marketing Remarks','',0,'\\290406','\\2904',0,'0',0),
        (3667,0,'Allow Editing of Sales Remarks','',0,'\\290407','\\2904',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SEMINAR','/tableentries/crm/seminar','Seminar','fa fa-sitemap sub_menu_ico',2594)";
    } //end function

    public function source($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2762,0,'Source','',0,'\\2906','$parent',0,'0',0),
        (2858,0,'Allow Add','',0,'\\290601','\\2906',0,'0',0),
        (2859,0,'Allow Save All','',0,'\\290602','\\2906',0,'0',0),
        (2860,0,'Allow Print','',0,'\\290603','\\2906',0,'0',0),
        (2861,0,'Allow Save','',0,'\\290604','\\2906',0,'0',0),
        (2862,0,'Allow Delete','',0,'\\290605','\\2906',0,'0',0),
        (3668,0,'Allow Editing of Marketing Remarks','',0,'\\290606','\\2906',0,'0',0),
        (3669,0,'Allow Editing of Sales Remarks','',0,'\\290607','\\2906',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SOURCE','/tableentries/crm/source','Source','fa fa-sitemap sub_menu_ico',2762)";
    } //end function

    public function exhibit($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2595,0,'Exhibit','',0,'\\2905','$parent',0,'0',0),
        (2853,0,'Allow Add','',0,'\\290501','\\2905',0,'0',0),
        (2854,0,'Allow Save All','',0,'\\290502','\\2905',0,'0',0),
        (2855,0,'Allow Print','',0,'\\290503','\\2905',0,'0',0),
        (2856,0,'Allow Save','',0,'\\290504','\\2905',0,'0',0),
        (2857,0,'Allow Delete','',0,'\\290505','\\2905',0,'0',0),
        (3670,0,'Allow Editing of Marketing Remarks','',0,'\\290506','\\2905',0,'0',0),
        (3671,0,'Allow Editing of Sales Remarks','',0,'\\290507','\\2905',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'EXHIBIT','/tableentries/crm/exhibit','Exhibit','fa fa-sitemap sub_menu_ico',2595)";
    } //end function

    public function parentdocumentmanagement($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2485,0,'DOCUMENT MANAGEMENT','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'DOCUMENT MANAGEMENT',$sort,'description',',documententry,issueslist,industrylist,documenttype,detailslist,divisionlist')";
    } //end function

    public function documententry($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2486,0,'Document Entry','',0,'\\3101','$parent',0,'0',0),
                (2487,0,'Allow View Transaction DE','DE',0,'\\ 3101001','\\3101',0,'0',0),
                (2488,0,'Allow Click Edit Button DE','',0,'\\3101002','\\3101',0,'0',0),
                (2489,0,'Allow Click New Button DE','',0,'\\310103','\\3101',0,'0',0),
                (2490,0,'Allow Click Save Button DE','',0,'\\310104','\\3101',0,'0',0),
                (2492,0,'Allow Click Delete Button DE','',0,'\\310106','\\3101',0,'0',0),
                (2493,0,'Allow Click Print Button DE','',0,'\\310107','\\3101',0,'0',0),
                (2494,0,'Allow Click Lock Button DE','',0,'\\310108','\\3101',0,'0',0),
                (2495,0,'Allow Click UnLock Button DE','',0,'\\310109','\\3101',0,'0',0),
                (2496,0,'Allow Click Post Button DE','',0,'\\310110','\\3101',0,'0',0),
                (2497,0,'Allow Click UnPost Button DE','',0,'\\310111','\\3101',0,'0',0),
                (2498,1,'Allow Click Add Item DE','',0,'\\310114','\\3101',0,'0',0)
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DT','/module/documentmanagement/dt','Document entry','fa fa-user sub_menu_ico',2486)";
    } //end function

    public function issueslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2499,0,'Issues List','',0,'\\3102','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ISSUESLIST','/tableentries/documentmanagement/dt_issues','Issues List','far fa-calendar-check sub_menu_ico',2499)";
    } //end function

    public function industrylist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2500,0,'Industry List','',0,'\\3103','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'INDUSTRYLIST','/tableentries/documentmanagement/dt_industry','Industry List','far fa-calendar-check sub_menu_ico',2500)";
    } //end function

    public function documenttype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2501,0,'Document Type List','',0,'\\3104','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DOCUMENTTYPE','/tableentries/documentmanagement/dt_documenttype','Document Type List','far fa-calendar-check sub_menu_ico',2501)";
    } //end function

    public function detailslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2502,0,'Details List','',0,'\\3105','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DETAILSLIST','/tableentries/documentmanagement/dt_details','Details List','far fa-calendar-check sub_menu_ico',2502)";
    } //end function

    public function divisionlist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2503,0,'Division List','',0,'\\3106','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'DIVISION LIST','/tableentries/documentmanagement/dt_division','Division List','far fa-calendar-check sub_menu_ico',2503)";
    } //end function

    public function statuslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2504,0,'Status List','',0,'\\3107','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'STATUS LIST','/tableentries/documentmanagement/dt_status','Status List','far fa-calendar-check sub_menu_ico',2504)";
    } //end function

    public function statusaccesslist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2505,0,'Status Access List','',0,'\\3108','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'STATUS ACCESS LIST','/tableentries/documentmanagement/dt_statusaccess','Status Access List','far fa-calendar-check sub_menu_ico',2505)";
    }

    public function parentpos($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2514,0,'POS','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'POS',$sort,'fa fa-cash-register',',branchledger,pospaymentsetup,extraction')";
    } //end function

    public function extraction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2515,0,'Sales Extraction','',0,'\\3201','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SALES EXTRACTION','/headtable/pos/extraction','Sales Extraction','fa fa-upload sub_menu_ico',2515)";
    }

    public function pospaymentsetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2574,0,'POS Payment Setup','',0,'\\3202','$parent',0,'0',0),
        (2868,0,'Allow View POS Payment Setup','',0,'\\320201','\\3202',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pospaymentsetup','/tableentries/pos/pospaymentsetup','POS Payment Setup','fa fa-cog sub_menu_ico',2574)";
    } //end function

    public function sjpos($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2323,0,'Sales Journal POS','',0,'\\513','$parent',0,'0',0),
      (156,0,'Allow View Transaction SJ POS','SJ',0,'\\51301','\\513',0,'0',0),
      (173,0,'Allow Click Edit Button SJ POS','',0,'\\51302','\\513',0,'0',0),
      (194,0,'Allow Click New  Button SJ POS','',0,'\\51303','\\513',0,'0',0),
      (2137,0,'Allow Click Save Button SJ POS','',0,'\\51304','\\513',0,'0',0),
      (2473,0,'Allow Click Delete Button SJ POS','',0,'\\51306','\\513',0,'0',0),
      (2768,0,'Allow Click Print Button SJ POS','',0,'\\51307','\\513',0,'0',0),
      (2785,0,'Allow Click Lock Button SJ POS','',0,'\\51308','\\513',0,'0',0),
      (2812,0,'Allow Click UnLock Button SJ POS','',0,'\\51309','\\513',0,'0',0),
      (2829,0,'Allow Click Post Button SJ POS','',0,'\\51310','\\513',0,'0',0),
      (2664,0,'Allow Click UnPost  Button SJ POS','',0,'\\51311','\\513',0,'0',0),
      (2711,0,'Allow Change Amount  SJ POS','',0,'\\51313','\\513',0,'0',0),
      (1864,0,'Allow Check Credit Limit SJ POS','',0,'\\51314','\\513',0,'0',0),
      (1891,0,'Allow SJ POS Amount Auto-Compute on UOM Change','',0,'\\51315','\\513',0,'0',0),
      (1906,0,'Allow View Transaction Accounting SJ POS','',0,'\\51316','\\513',0,'0',0),
      (1924,1,'Allow Click Add Item SJ POS','',0,'\\51317','\\513',0,'0',0),
      (1943,1,'Allow Click Edit Item SJ POS','',0,'\\51318','\\513',0,'0',0),
      (1961,1,'Allow Click Delete Item SJ POS','',0,'\\51319','\\513',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SJ','/module/pos/sj','Sales Journal POS','fa fa-file-invoice sub_menu_ico',2323)";
    } //end function

    public function cmpos($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(1990,0,'Sales Return POS','',0,'\\514','$parent',0,'0',0),
      (2008,0,'Allow View Transaction SR POS','CM',0,'\\51401','\\514',0,'0',0),
      (2155,0,'Allow Click Edit Button SR POS','',0,'\\51402','\\514',0,'0',0),
      (2173,0,'Allow Click New  Button SR POS','',0,'\\51403','\\514',0,'0',0),
      (2192,0,'Allow Click Save  Button SR POS','',0,'\\51404','\\514',0,'0',0),
      (2098,0,'Allow Click Delete Button SR POS','',0,'\\51406','\\514',0,'0',0),
      (2115,0,'Allow Click Print  Button SR POS','',0,'\\51407','\\514',0,'0',0),
      (2339,0,'Allow Click Lock Button SR POS','',0,'\\51408','\\514',0,'0',0),
      (2356,0,'Allow Click UnLock Button SR POS','',0,'\\51409','\\514',0,'0',0),
      (2387,0,'Allow Click Post Button SR POS','',0,'\\51410','\\514',0,'0',0),
      (67,0,'Allow Click UnPost  Button SR POS','',0,'\\51411','\\514',0,'0',0),
      (83,0,'Allow View Transaction Accounting SR POS','',0,'\\51412','\\514',0,'0',0),
      (2244,0,'Allow Change Amount SR POS','',0,'\\51413','\\514',0,'0',0),
      (102,1,'Allow Click Add Item SR POS','',0,'\\51414','\\514',0,'0',0),
      (623,1,'Allow Click Edit Item SR POS','',0,'\\51415','\\514',0,'0',0),
      (2648,1,'Allow Click Delete Item SR POS','',0,'\\51416','\\514',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CM','/module/pos/cm','Sales Return POS','fa fa-sync sub_menu_ico',1990)";
    } //end function

    public function outsourcestockcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2583,0,'Outsource Items','',0,'\\113','$parent',0,'0',0),
        (2671,0,'Allow View Outsource Items','A',0,'\\11301','\\113',0,'0',0),
        (131,0,'Allow Click Edit Button Outsource Items','A',0,'\\11302','\\113',0,'0',0),
        (132,0,'Allow Click New Button Outsource Items','A',0,'\\11303','\\113',0,'0',0),
        (148,0,'Allow Click Save Button Outsource Items','A',0,'\\11304','\\113',0,'0',0),
        (149,0,'Allow Click Change Barcode Outsource Items','A',0,'\\11305','\\113',0,'0',0),
        (165,0,'Allow Click Delete Button Outsource Items','A',0,'\\11306','\\113',0,'0',0),
        (166,0,'Allow Print Button Outsource Items','A',0,'\\11307','\\113',0,'0',0),
        (167,0,'Allow View SRP Button Outsource Items','A',0,'\\11308','\\113',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'outsourcestockcard','/ledgergrid/outsource/stockcard','Outsource Items','fa fa-box-open sub_menu_ico',2583)";
    } //end function

    public function os($params, $parent, $sort)
    { // Outsource
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2520,0,'Outsource Items','',0,'\\412','$parent',0,'0',0),
        (2521,0,'Allow View Transaction OS','OS',0,'\\41201','\\412',0,'0',0),
        (2522,0,'Allow Click Edit Button OS','',0,'\\41202','\\412',0,'0',0),
        (2523,0,'Allow Click New Button OS','',0,'\\41203','\\412',0,'0',0),
        (2524,0,'Allow Click Save Button OS','',0,'\\41204','\\412',0,'0',0),
        (2526,0,'Allow Click Delete Button OS','',0,'\\41206','\\412',0,'0',0),
        (2527,0,'Allow Click Print Button OS','',0,'\\41207','\\412',0,'0',0),
        (2528,0,'Allow Click Lock Button OS','',0,'\\41208','\\412',0,'0',0),
        (2529,0,'Allow Click UnLock Button OS','',0,'\\41209','\\412',0,'0',0),
        (2530,0,'Allow Change Amount OS','',0,'\\41210','\\412',0,'0',0),
        (2531,0,'Allow Click Post Button OS','',0,'\\41212','\\412',0,'0',0),
        (2532,0,'Allow Click UnPost  Button OS','',0,'\\41213','\\412',0,'0',0),
        (2533,1,'Allow Click Add Item OS','',0,'\\41214','\\412',0,'0',0),
        (2534,1,'Allow Click Edit Item OS','',0,'\\41215','\\412',0,'0',0),
        (2688,1,'Allow Click Delete Item OS','',0,'\\41216','\\412',0,'0',0),
        (2689,1,'Allow View Amount','',0,'\\41217','\\412',0,'0',0),
        (2690,1,'Allow Click PR Button','',0,'\\41218','\\412',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'OS','/module/purchase/os','Outsource','fa fa-border-none sub_menu_ico',2520)";
    } //end function

    public function studentreportcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2691, 0, 'Student Report Card', '', 0, '\\1415', '$parent', 0, '0', 0),
        (2692, 0, 'Allow Edit Student Report Card', '', 0, '\\141501', '\\1415', 0, '0', 0),
        (2693, 0, 'Allow View Student Report Card', '', 0, '\\141502', '\\1415', 0, '0', 0),
        (2694, 0, 'Allow New Student Report Card', '', 0, '\\141503', '\\1415', 0, '0', 0),
        (2695, 0, 'Allow Save Student Report Card', '', 0, '\\141504', '\\1415', 0, '0', 0),
        (2696, 0, 'Allow Delete Student Report Card', '', 0, '\\141505', '\\1415', 0, '0', 0),
        (2697, 0, 'Allow Change Code Student Report Card', '', 0, '\\141506', '\\1415', 0, '0', 0),
        (2698, 0, 'Allow Lock Student Report Card', '', 0, '\\141507', '\\1415', 0, '0', 0),
        (2699, 0, 'Allow UnLock Student Report Card', '', 0, '\\141508', '\\1415', 0, '0', 0),
        (2700, 0, 'Allow Post Student Report Card', '', 0, '\\141509', '\\1415', 0, '0', 0),
        (2701, 0, 'Allow UnPost Student Report Card', '', 0, '\\141510', '\\1415', 0, '0', 0),
        (2702, 0, 'Allow Print Student Report Card', '', 0, '\\141509', '\\1415', 0, '0', 0),
        (2703, 0, 'Allow Click Add Item Student Report Card', '', 0, '\\141510', '\\1415', 0, '0', 0),
        (2704, 0, 'Allow Click Edit Item Student Report Card', '', 0, '\\141511', '\\1415', 0, '0', 0),
        (2705, 0, 'Allow Click Delete Item Student Report Card', '', 0, '\\141512', '\\1415', 0, '0', 0)";
        $this->insertattribute($params, $qry);
        return "($sort, $p, 'studentreportcard', '/module/enrollment/ek', 'Student Report Card', 'fa fa-user sub_menu_ico', 2691)";
    }

    public function othercharges($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2308,0,'Other Charges','',0,'\\3001','$parent',0,'0',0),
        (773,0,'Allow Click Edit Button Other Charges','',0,'\\300101','\\3001',0,'0',0) ,
        (2045,0,'Allow Click New Button Other Charges','',0,'\\300102','\\3001',0,'0',0) ,
        (1847,0,'Allow Click Save Button Other Charges','',0,'\\300103','\\3001',0,'0',0) ,
        (2752,0,'Allow Click Delete Button Other Charges','',0,'\\300104','\\3001',0,'0',0) ,
        (2491,0,'Allow Click Post Button Other Charges','',0,'\\300105','\\3001',0,'0',0) ,
        (2525,0,'Allow Click UnPost Button Other Charges','',0,'\\300106','\\3001',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'othercharges','/headtable/operation/othercharges','Other Charges','fa fa-money-bill-wave sub_menu_ico',2308)";
    } //end function

    public function tenancy($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4216,0,'TACRF','',0,'\\1223','$parent',0,'0',0),
        (4217,0,'Allow View TACRF','',0,'\\122301','\\1223',0,'0',0),
        (4303,0,'Allow Edit TACRF','',0,'\\122302','\\1223',0,'0',0),
        (4304,0,'Allow Release TACRF','',0,'\\122303','\\1223',0,'0',0),
        (4305,0,'Allow Approve TACRF','',0,'\\122304','\\1223',0,'0',0),
        (4306,0,'Allow Cancel TACRF','',0,'\\122305','\\1223',0,'0',0) ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'tenancy','/ledgergrid/operation/tacrf','Create Tacrf','fa fa-address-card sub_menu_ico',4216)";
    } //end function

    public function cardtypes($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2635,1,'Card Types','',0,'\\515','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'cardtypes','/tableentries/tableentry/cardtypes','Card Types','fa fa-list sub_menu_ico',2635)";
    } //end function

    public function paymenttype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4502,1,'Payment Type','',0,'\\516','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'paymenttype','/tableentries/tableentry/paymenttype','Payment Type','fa fa-list sub_menu_ico',4502)";
    } //end function

    public function parentvehiclescheduling($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2876,0,'VEHICLE SCHEDULING','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'VEHICLE SCHEDULING',$sort,'fa fa-truck-pickup',',driver')";
    } //end function

    public function driver($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2877,0,'Driver Ledger','',0,'\\3301','$parent',0,'0',0),
        (2878,0,'Allow View Driver Ledger','',0,'\\330101','\\3301',0,'0',0),
        (2879,0,'Allow Click Edit Button DL','',0,'\\330102','\\3301',0,'0',0),
        (2880,0,'Allow Click New Button DL','',0,'\\330103','\\3301',0,'0',0),
        (2881,0,'Allow Click Save Button DL','',0,'\\330104','\\3301',0,'0',0),
        (2882,0,'Allow Click Change Code DL','',0,'\\330105','\\3301',0,'0',0),
        (2883,0,'Allow Click Delete Button DL','',0,'\\330106','\\3301',0,'0',0),
        (2884,0,'Allow Click Print Button DL','',0,'\\330107','\\3301',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'driver','/ledger/vehiclescheduling/driver','Driver','fa fa-car sub_menu_ico',2877)";
    } //end function

    public function passenger($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2885,0,'Passenger Ledger','',0,'\\3302','$parent',0,'0',0),
        (2886,0,'Allow View Passenger Ledger','',0,'\\330201','\\3302',0,'0',0),
        (2887,0,'Allow Click Edit Button PL','',0,'\\330202','\\3302',0,'0',0),
        (2888,0,'Allow Click New Button PL','',0,'\\330203','\\3302',0,'0',0),
        (2889,0,'Allow Click Save Button PL','',0,'\\330204','\\3302',0,'0',0),
        (2890,0,'Allow Click Change Code PL','',0,'\\330205','\\3302',0,'0',0),
        (2891,0,'Allow Click Delete Button PL','',0,'\\330206','\\3302',0,'0',0),
        (2892,0,'Allow Click Print Button PL','',0,'\\330207','\\3302',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'passenger','/ledger/vehiclescheduling/passenger','Passenger','fa fa-user-plus sub_menu_ico',2885)";
    } //end function

    public function vr($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2893,0,'Vehicle Scheduling Request','',0,'\\3303','$parent',0,'0',0) ,
        (2894,0,'Allow View VR','',0,'\\330301','\\3303',0,'0',0) ,
        (2895,0,'Allow Click Edit Button VR','',0,'\\330302','\\3303',0,'0',0) ,
        (2896,0,'Allow Click New Button VR','',0,'\\330303','\\3303',0,'0',0) ,
        (2897,0,'Allow Click Save Button VR','',0,'\\330304','\\3303',0,'0',0) ,
        (2898,0,'Allow Click Change Code VR','',0,'\\330305','\\3303',0,'0',0) ,
        (2899,0,'Allow Click Delete Button VR','',0,'\\330306','\\3303',0,'0',0) ,
        (2900,0,'Allow Click Print Button VR','',0,'\\330307','\\3303',0,'0',0) ,
        (2901,0,'Allow Click Post Button VR','',0,'\\330308','\\3303',0,'0',0) ,
        (2902,0,'Allow Click UnPost Button VR','',0,'\\330309','\\3303',0,'0',0) ,
        (2903,0,'Allow Click Lock Button VR','',0,'\\330310','\\3303',0,'0',0) ,
        (2904,0,'Allow Click UnLock Button VR','',0,'\\330311','\\3303',0,'0',0),
        (2905,1,'Allow Click Add Item VR','',0,'\\330312','\\3303',0,'0',0),
        (2906,1,'Allow Click Edit Item VR','',0,'\\330313','\\3303',0,'0',0),
        (2907,1,'Allow Click Delete Item VR','',0,'\\330314','\\3303',0,'0',0),
        (3590,1,'Allow View All Request','',0,'\\330315','\\3303',0,'0',0),
        (3722,1,'Allow View Dashboard Request','',0,'\\330316','\\3303',0,'0',0)
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'VR','/module/vehiclescheduling/vr','Vehicle Scheduling Request','fa fa-truck-moving sub_menu_ico',2893)";
    } //end function

    public function vl($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2929,0,'Logistics','',0,'\\3305','$parent',0,'0',0) ,
        (2930,0,'Allow View VL','',0,'\\330501','\\3305',0,'0',0) ,
        (2931,0,'Allow Click Edit Button VL','',0,'\\330502','\\3305',0,'0',0) ,
        (2932,0,'Allow Click New Button VL','',0,'\\330503','\\3305',0,'0',0) ,
        (2933,0,'Allow Click Save Button VL','',0,'\\330504','\\3305',0,'0',0) ,
        (2934,0,'Allow Click Change Code VL','',0,'\\330505','\\3305',0,'0',0) ,
        (2935,0,'Allow Click Delete Button VL','',0,'\\330506','\\3305',0,'0',0) ,
        (2936,0,'Allow Click Print Button VL','',0,'\\330507','\\3305',0,'0',0) ,
        (2937,0,'Allow Click Post Button VL','',0,'\\330508','\\3305',0,'0',0) ,
        (2938,0,'Allow Click UnPost Button VL','',0,'\\330509','\\3305',0,'0',0) ,
        (2939,0,'Allow Click Lock Button VL','',0,'\\330510','\\3305',0,'0',0) ,
        (2940,0,'Allow Click UnLock Button VL','',0,'\\330511','\\3305',0,'0',0),
        (2941,1,'Allow Click Add Item VL','',0,'\\330512','\\3305',0,'0',0),
        (2942,1,'Allow Click Edit Item VL','',0,'\\330513','\\3305',0,'0',0),
        (2943,1,'Allow Click Delete Item VL','',0,'\\330514','\\3305',0,'0',0),
        (3715,1,'View Dashboard Approved w/out Vehicle','',0,'\\330515','\\3305',0,'0',0),
        (3716,1,'View Dashboard Approved w/ Vehicle','',0,'\\330516','\\3305',0,'0',0),
        (3717,1,'Allow View All Dashboard Requests','',0,'\\330517','\\3305',0,'0',0)
        ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'VL','/module/vehiclescheduling/vl','Logistics','fa fa-truck-loading sub_menu_ico',2929)";
    } //end function

    public function vrapproval($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2911,0,'VR Approval List','',0,'\\3304','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'vrapproval','/ledgergrid/ati/requestapproval','Vehicle Request Approval List','fa fa-calendar-check sub_menu_ico',2911)";
    } //end function

    public function vehiclesched($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2996,1,'Vehicle Schedule','',0,'\\3306','$parent',0,0,0),
        (2997,1,'Create Vehicle Schedule','',0,'\\330601','\\3306',0,'0',0),
        (2998,1,'View Dashboard Vehicle Status','',0,'\\330602','\\3306',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'emptimecard','/headtable/vehiclescheduling/vehiclesched','Vehicle Schedule','fa fa-calendar-day sub_menu_ico',2996)";
    } //end function

    public function parentfams($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2908,0,'FAMS','',0,'$parent','\\',0,'',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'FAMS',$sort,'fa fa-warehouse',',issueitems')";
    } //end function

    public function issueitems($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2909,0,'Issue Items','',0,'\\3401','$parent',0,'0',0),
        (4155,0,'Allow View Transaction','FI',0,'\\340101','\\3401',0,'0',0),
        (4156,0,'Allow Click Edit Button','',0,'\\340102','\\3401',0,'0',0),
        (4157,0,'Allow Click New Button','',0,'\\340103','\\3401',0,'0',0),
        (4158,0,'Allow Click Save Button','',0,'\\340104','\\3401',0,'0',0),
        (4159,0,'Allow Click Delete Button','',0,'\\340105','\\3401',0,'0',0),
        (4160,0,'Allow Click Post Button','',0,'\\340106','\\3401',0,'0',0),
        (4161,0,'Allow Click Unpost Button','',0,'\\340107','\\3401',0,'0',0),
        (4200,0,'Allow Click Delete Item','',0,'\\340109','\\3401',0,'0',0),
        (4201,0,'Allow Click Add Item','',0,'\\340110','\\3401',0,'0',0),
        (4205,0,'Allow Click Print Button','',0,'\\340111','\\3401',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'FI','/module/fams/fi','Issue Items','fa fa-list sub_menu_ico',2909)";
    } //end function

    public function returnitems($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3965,0,'Return Items','',0,'\\3402','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'returnitems','/ledgergrid/fams/returnitems','Return Items','fa fa-retweet sub_menu_ico',3965)";
    } //end function

    public function generalitem($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2910,1,'General Item','',0,'\\2601','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'generalitem','/tableentries/tableentry/entrygeneralitem','General Item','fa fa-list sub_menu_ico',2910)";
    } //end function

    public function gp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = " (2913,0,'Gate Pass OUT','',0,'\\3403','$parent',0,'0',0),
      (2914,0,'Allow View Transaction GP','GP',0,'\\340301','\\3403',0,'0',0),
      (2915,0,'Allow Click Edit Button GP','',0,'\\340302','\\3403',0,'0',0),
      (2916,0,'Allow Click New  Button GP','',0,'\\340303','\\3403',0,'0',0),
      (2917,0,'Allow Click Save Button GP','',0,'\\340304','\\3403',0,'0',0),
      (2918,0,'Allow Click Delete Button GP','',0,'\\340305','\\3403',0,'0',0),
      (2919,0,'Allow Click Print Button GP','',0,'\\340306','\\3403',0,'0',0),
      (2920,0,'Allow Click Lock Button GP','',0,'\\340307','\\3403',0,'0',0),
      (2921,0,'Allow Click UnLock Button GP','',0,'\\340310','\\3403',0,'0',0),
      (2922,0,'Allow Change Amount  GP','',0,'\\340311','\\3403',0,'0',0),
      (2923,0,'Allow Check Credit Limit GP','',0,'\\340312','\\3403',0,'0',0),
      (2924,0,'Allow Click Post Button GP','',0,'\\340313','\\3403',0,'0',0),
      (2925,0,'Allow Click UnPost  Button GP','',0,'\\340314','\\3403',0,'0',0),
      (2926,1,'Allow Click Add Item GP','',0,'\\340315','\\3403',0,'0',0),
      (2927,1,'Allow Click Edit Item GP','',0,'\\340316','\\3403',0,'0',0),
      (2928,1,'Allow Click Delete Item GP','',0,'\\340317','\\3403',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'GP','/module/fams/gp','Gate Pass OUT','fa fa-clipboard-list sub_menu_ico',2913)";
    } //end function

    public function gatepassreturn($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2944,0,'Gate Pass Return','',0,'\\3404','$parent',0,'0',0),
        (2945,0,'Allow View Gate Pass Return','gpr',0,'\\340401','\\3404',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'warehousepicker','/ledgergrid/fams/gatepassreturn','Gate Pass Return','fa fa-clipboard-list sub_menu_ico',2944)";
    } //end function


    public function fc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3966,0,'Convert to Asset','',0,'\\3405','$parent',0,'0',0),
        (3967,0,'Allow View Transaction FC','FC',0,'\\340501','\\3405',0,'0',0),
        (3968,0,'Allow Click Edit Button FC','FC',0,'\\340502','\\3405',0,'0',0),
        (3969,0,'Allow Click New  Button FC','FC',0,'\\340503','\\3405',0,'0',0),
        (3970,0,'Allow Click Save Button FC','FC',0,'\\340504','\\3405',0,'0',0),
        (3971,0,'Allow Click Delete Button FC','FC',0,'\\340505','\\3405',0,'0',0),
        (3972,0,'Allow Click Print Button FC','FC',0,'\\340506','\\3405',0,'0',0),
        (3973,0,'Allow Click Lock Button FC','FC',0,'\\340507','\\3405',0,'0',0),
        (3974,0,'Allow Click UnLock Button FC','FC',0,'\\340508','\\3405',0,'0',0),
        (3975,0,'Allow Click Post Button FC','FC',0,'\\340509','\\3405',0,'0',0),
        (3976,0,'Allow Click UnPost  Button FC','FC',0,'\\340510','\\3405',0,'0',0),
        (3977,0,'Allow View Transaction Accounting FA','FC',0,'\\340511','\\3405',0,'0',0),
        (3978,1,'Allow Click Add Item FC','',0,'\\340512','\\3405',0,'0',0),
        (3979,1,'Allow Click Edit Item FC','',0,'\\340513','\\3405',0,'0',0),
        (3980,1,'Allow Click Delete Item FC','',0,'\\340514','\\3405',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'FC','/module/fams/fc','Convert to Asset','fa fa-boxes sub_menu_ico',3966)";
    } //end function

    public function gpal($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3981,0,'Gate Pass Asset Logs','',0,'\\3406','$parent',0,'0',0),
        (3982,0,'Allow View Gate Pass Asset Logs','gpal',0,'\\340601','\\3406',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'gpal','/ledgergrid/fams/gpal','Gate Pass Asset Logs','fa fa-clipboard-list sub_menu_ico',3982)";
    } //end function

    public function pf($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2946,0,'Purchase Order - General Item','',0,'\\25014','$parent',0,'0',0),
        (2947,0,'Allow View Transaction PO','PO',0,'\\2501401','\\25014',0,'0',0),
        (2948,0,'Allow Click Edit Button PO','',0,'\\2501402','\\25014',0,'0',0),
        (2949,0,'Allow Click New Button PO','',0,'\\2501403','\\25014',0,'0',0),
        (2950,0,'Allow Click Save Button PO','',0,'\\2501404','\\25014',0,'0',0),
        (2951,0,'Allow Click Delete Button PO','',0,'\\2501406','\\25014',0,'0',0),
        (2952,0,'Allow Click Print Button PO','',0,'\\2501407','\\25014',0,'0',0),
        (2953,0,'Allow Click Lock Button PO','',0,'\\2501408','\\25014',0,'0',0),
        (2954,0,'Allow Click UnLock Button PO','',0,'\\2501409','\\25014',0,'0',0),
        (2955,0,'Allow Change Amount PO','',0,'\\2501410','\\25014',0,'0',0),
        (2956,0,'Allow Click Post Button PO','',0,'\\2501412','\\25014',0,'0',0),
        (2957,0,'Allow Click UnPost  Button PO','',0,'\\2501413','\\25014',0,'0',0),
        (2958,1,'Allow Click Add Item PO','',0,'\\2501414','\\25014',0,'0',0),
        (2959,1,'Allow Click Edit Item PO','',0,'\\2501415','\\25014',0,'0',0),
        (2960,1,'Allow Click Delete Item PO','',0,'\\2501416','\\25014',0,'0',0),
        (2961,1,'Allow View Amount','',0,'\\2501417','\\25014',0,'0',0),
        (2962,1,'Allow Click PR Button','',0,'\\2501418','\\25014',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PF','/module/purchase/pf','Purchase Order - General Item','fa fa-tasks sub_menu_ico',2946)";
    } //end function

    public function ra($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Receiving Report - General Item';
        $qry = "(2963,0,'" . $label . "','',0,'\\413','$parent',0,'0',0),
        (2964,0,'Allow View Transaction RR','RR',0,'\\41301','\\413',0,'0',0),
        (2965,0,'Allow Click Edit Button RR','',0,'\\41302','\\413',0,'0',0),
        (2966,0,'Allow Click New Button RR','',0,'\\41303','\\413',0,'0',0),
        (2967,0,'Allow Click Save Button RR','',0,'\\41304','\\413',0,'0',0),
        (2968,0,'Allow Click Delete Button RR','',0,'\\41306','\\413',0,'0',0),
        (2969,0,'Allow Click Print Button RR','',0,'\\41307','\\413',0,'0',0),
        (2970,0,'Allow Click Lock Button RR','',0,'\\41308','\\413',0,'0',0),
        (2971,0,'Allow Click UnLock Button RR','',0,'\\41309','\\413',0,'0',0),
        (2972,0,'Allow Click Post Button RR','',0,'\\41310','\\413',0,'0',0),
        (2973,0,'Allow Click UnPost Button RR','',0,'\\41311','\\413',0,'0',0),
        (2974,0,'Allow View Transaction accounting RR','',0,'\\40212','\\413',0,'0',0),
        (2975,0,'Allow Change Amount RR','',0,'\\41313','\\413',0,'0',0),
        (2976,1,'Allow Click Add Item RR','',0,'\\41314','\\413',0,'0',0),
        (2977,1,'Allow Click Edit Item RR','',0,'\\41315','\\413',0,'0',0),
        (2978,1,'Allow Click Delete Item RR','',0,'\\41316','\\413',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RA','/module/purchase/ra','" . $label . "','fa fa-people-carry sub_menu_ico',2963)";
    } //end function

    public function genericitem($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Generic Item';
        $qry = "(2979,0,'" . $label . "','',0,'\\414','$parent',0,'0',0),
        (2980,0,'Allow View " . $label . "','Generic Item',0,'\\41401','\\414',0,'0',0),
        (2981,0,'Allow Click Edit Button Generic Item','',0,'\\41402','\\414',0,'0',0),
        (2982,0,'Allow Click New Button Generic Item','',0,'\\41403','\\414',0,'0',0),
        (2983,0,'Allow Click Save Button Generic Item','',0,'\\41404','\\414',0,'0',0),
        (2984,0,'Allow Click Change Barcode Generic Item','',0,'\\41405','\\414',0,'0',0),
        (2985,0,'Allow Click Delete Button Generic Item','',0,'\\41406','\\414',0,'0',0),
        (2986,0,'Allow Print Button Generic Item','',0,'\\41407','\\414',0,'0',0),
        (2987,0,'Allow View SRP Button Generic Item','',0,'\\41408','\\414',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'genericitem','/ledgergrid/genericitem/stockcard','" . $label . "','fa fa-list-alt sub_menu_ico',2979)";
    } //end function


    public function parentkwhmonitoring($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4055,0,'KWH MONITORING','',0,'$parent','\\',0,'',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'KWH MONITORING',$sort,'bolt',',')";
    } //end function

    public function powerconsumption($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4056,0,'Power Consumption Category','',0,'\\3601','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'powerconsumption','/tableentries/kwhmonitoring/powerconsumption','Power Consumption Category','fa fa-list sub_menu_ico',4056)";
    } //end function

    public function kwhratesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4123,0,'Rate Setup','',0,'\\3603','$parent',0,'0',0),
        (4140,0,'Allow delete rate','',0,'\\360301','\\3603',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'kwhratesetup','/headtable/kwhmonitoring/kwhratesetup','Rate Setup','fa fa-money-bill sub_menu_ico',4123)";
    }

    public function pw($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;

        $folder = 'kwhmonitoring';
        $modulename = 'Power Consumption Entry';

        $qry = " (4078,0,'" . $modulename . "','',0,'\\3602','$parent',0,'0',0),
        (4079,0,'Allow View Transaction PCE','PW',0,'\\360201','\\3602',0,'0',0),
        (4080,0,'Allow Click Edit Button PCE','',0,'\\360202','\\3602',0,'0',0),
        (4081,0,'Allow Click New  Button PCE','',0,'\\360203','\\3602',0,'0',0),
        (4082,0,'Allow Click Save Button PCE','',0,'\\360204','\\3602',0,'0',0),
        (4083,0,'Allow Click Delete Button PCE','',0,'\\360206','\\3602',0,'0',0),
        (4084,0,'Allow Click Print Button PCE','',0,'\\360207','\\3602',0,'0',0),
        (4085,0,'Allow Click Lock Button PCE','',0,'\\360208','\\3602',0,'0',0),
        (4086,0,'Allow Click UnLock Button PCE','',0,'\\360209','\\3602',0,'0',0),
        (4087,0,'Allow Change Amount  PCE','',0,'\\360210','\\3602',0,'0',0),
        (4088,0,'Allow Click Post Button PCE','',0,'\\360212','\\3602',0,'0',0),
        (4089,0,'Allow Click UnPost  Button PCE','',0,'\\360213','\\3602',0,'0',0),
        (4090,1,'Allow Click Edit Item PCE','',0,'\\360215','\\3602',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PW','/module/" . $folder . "/pw','" . $modulename . "','fa fa-clipboard-list sub_menu_ico',4078)";
    } //end function


    public function parentwaterbilling($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4103,0,'WATER BILLING','',0,'$parent','\\',0,'',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'WATER BILLING',$sort,'receipt',',')";
    } //end function

    public function purpose($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2988,0,'Purpose','',0,'\\1110','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'purpose','/tableentries/vehiclescheduling/entrypurpose','Purpose','fa fa-calendar-check sub_menu_ico',2988)";
    } //end function

    public function qtybracket($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2989,0,'Price Bracket Setup','',0,'\\895','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'qtybracket','/tableentries/othersettings/entryqtybracket','Quantity Bracket Setup','fa fa-list sub_menu_ico',2989)";
    } //end function

    public function pricelist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(2990,0,'Price List','',0,'\\896','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'pricelist','/headtable/othersettings/pricelist','Price List','fa fa-list sub_menu_ico',2990)";
    } //end function

    public function duration($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4580,0,'Duration Setup','',0,'\\816','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'audittrail','/tableentries/othersettings/entryduration','Duration','fa fa-calendar-day sub_menu_ico',4580)";
    } //end function


    public function requestcategory($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3742,0,'Request Category','',0,'\\820','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'audittrail','/tableentries/othersettings/entryrequestcategory','Request Category','fa fa-calendar-day sub_menu_ico',3742)";
    } //end function


    public function sp($params, $parent, $sort)
    {

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3560,0,'Stock Return','',0,'\\1505','$parent',0,'0',0) ,
        (3561,0,'Allow View Transaction S. Return','SS',0,'\\150501','\\1505',0,'0',0) ,
        (3562,0,'Allow Click Edit Button  S. Return','',0,'\\150502','\\1505',0,'0',0) ,
        (3563,0,'Allow Click New Button S. Return','',0,'\\150503','\\1505',0,'0',0) ,
        (3564,0,'Allow Click Save Button S. Return','',0,'\\150504','\\1505',0,'0',0) ,
        (3565,0,'Allow Click Delete Button S. Return','',0,'\\150505','\\1505',0,'0',0) ,
        (3566,0,'Allow Click Print Button S. Return','',0,'\\150506','\\1505',0,'0',0) ,
        (3567,0,'Allow Click Lock Button S. Return','',0,'\\150507','\\1505',0,'0',0) ,
        (3568,0,'Allow Click UnLock Button S. Return','',0,'\\150508','\\1505',0,'0',0) ,
        (3569,0,'Allow Click Post Button S. Return','',0,'\\150509','\\1505',0,'0',0) ,
        (3570,0,'Allow Click UnPost Button S. Return','',0,'\\150510','\\1505',0,'0',0) ,
        (3571,1,'Allow Click Add Item S. Return','',0,'\\150511','\\1505',0,'0',0) ,
        (3572,1,'Allow Click Delete Item S. Return','',0,'\\150512','\\1505',0,'0',0) ,
        (3573,1,'Allow Change Amount S. Return','',0,'\\150513','\\1505',0,'0',0) ,
        (3574,1,'Allow Click Edit Item S. Return','',0,'\\150514','\\1505',0,'0',0)";
        $this->insertattribute($params, $qry);

        $systemtype = $this->companysetup->getsystemtype($params);
        $folder = 'ati';

        return "($sort,$p,'SP','/module/" . $folder . "/sp','Stock Return','fa fa-sync sub_menu_ico',3560)";
    } //end function


    public function oq($params, $parent, $sort)
    {
        $companyid = $params['companyid'];

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3603,0,'Oracle Code Request','',0,'\\415','$parent',0,'0',0),
        (3604,0,'Allow View Transaction OQ','OQ',0,'\\41501','\\415',0,'0',0),
        (3605,0,'Allow Click Edit Button OQ','',0,'\\41502','\\415',0,'0',0),
        (3606,0,'Allow Click New Button OQ','',0,'\\41503','\\415',0,'0',0),
        (3607,0,'Allow Click Save Button OQ','',0,'\\41504','\\415',0,'0',0),
        (3608,0,'Allow Click Delete Button OQ','',0,'\\41506','\\415',0,'0',0),
        (3609,0,'Allow Click Print Button OQ','',0,'\\41507','\\415',0,'0',0),
        (3610,0,'Allow Click Lock Button OQ','',0,'\\41508','\\415',0,'0',0),
        (3611,0,'Allow Click UnLock Button OQ','',0,'\\41509','\\415',0,'0',0),
        (3613,0,'Allow Click Post Button OQ','',0,'\\41512','\\415',0,'0',0),
        (3614,0,'Allow Click UnPost  Button OQ','',0,'\\41513','\\415',0,'0',0),
        (3615,1,'Allow Click Add Item OQ','',0,'\\41514','\\415',0,'0',0),
        (3616,1,'Allow Click Edit Item OQ','',0,'\\41515','\\415',0,'0',0),
        (3617,1,'Allow Click Delete Item OQ','',0,'\\41516','\\415',0,'0',0),
        (3620,1,'Allow Click For Revision','',0,'\\41517','\\415',0,'0',0),
        (4030,1,'Allow Price Edit','',0,'\\41518','\\415',0,'0',0),
        (4169,1,'Allow Clicked Approved','',0,'\\41519','\\415',0,'0',0)";
        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
        }
        return "($sort,$p,'OQ','/module/" . $folder . "/oq','Oracle Code Request','fa fa-database sub_menu_ico',3603)";
    } //end function

    public function om($params, $parent, $sort)
    {
        $companyid = $params['companyid'];

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4176,0,'OSI','',0,'\\419','$parent',0,'0',0),
        (4177,0,'Allow View Transaction OSI','OM',0,'\\41901','\\419',0,'0',0),
        (4178,0,'Allow Click Edit Button OSI','',0,'\\41902','\\419',0,'0',0),
        (4179,0,'Allow Click New Button OSI','',0,'\\41903','\\419',0,'0',0),
        (4180,0,'Allow Click Save Button OSI','',0,'\\41904','\\419',0,'0',0),
        (4181,0,'Allow Click Delete Button OSI','',0,'\\41905','\\419',0,'0',0),
        (4182,0,'Allow Click Print Button OSI','',0,'\\41906','\\419',0,'0',0),
        (4183,0,'Allow Click Lock Button OSI','',0,'\\41907','\\419',0,'0',0),
        (4184,0,'Allow Click UnLock Button OSI','',0,'\\41908','\\419',0,'0',0),
        (4185,0,'Allow Click Post Button OSI','',0,'\\41909','\\419',0,'0',0),
        (4186,0,'Allow Click UnPost  Button OSI','',0,'\\41910','\\419',0,'0',0),
        (4187,1,'Allow Click Add Item OSI','',0,'\\41911','\\419',0,'0',0),
        (4188,1,'Allow Click Edit Item OSI','',0,'\\41912','\\419',0,'0',0),
        (4189,1,'Allow Click Delete Item OSI','',0,'\\41913','\\419',0,'0',0),
        (4197,1,'Allow Click For Receiving','',0,'\\41914','\\419',0,'0',0),
        (4198,1,'Allow Click For SO','',0,'\\41915','\\419',0,'0',0),
        (4120,1,'Allow Click For Posting','',0,'\\41916','\\419',0,'0',0),
        (4121,1,'Allow Input SO','',0,'\\41917','\\419',0,'0',0),
        (4508,1,'Allow Update Details','',0,'\\41918','\\419',0,'0',0)
        ";
        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
        }
        return "($sort,$p,'OM','/module/" . $folder . "/om','O S I','fa fa-database sub_menu_ico',4176)";
    } //end function




    public function lq($params, $parent, $sort)
    {
        $companyid = $params['companyid'];

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3724,0,'Cash Liquidation Form ','',0,'\\416','$parent',0,'0',0),
        (3725,0,'Allow View Transaction LQ','LQ',0,'\\41601','\\416',0,'0',0),
        (3726,0,'Allow Click Edit Button LQ','',0,'\\41602','\\416',0,'0',0),
        (3727,0,'Allow Click New Button LQ','',0,'\\41603','\\416',0,'0',0),
        (3728,0,'Allow Click Save Button LQ','',0,'\\41604','\\416',0,'0',0),
        (3729,0,'Allow Click Delete Button LQ','',0,'\\41605','\\416',0,'0',0),
        (3730,0,'Allow Click Print Button LQ','',0,'\\41606','\\416',0,'0',0),
        (3731,0,'Allow Click Lock Button LQ','',0,'\\41607','\\416',0,'0',0),
        (3732,0,'Allow Click UnLock Button LQ','',0,'\\41608','\\416',0,'0',0),
        (3733,0,'Allow Change Amount LQ','',0,'\\41609','\\416',0,'0',0),
        (3734,0,'Allow Click Post Button LQ','',0,'\\41610','\\416',0,'0',0),
        (3735,0,'Allow Click UnPost  Button LQ','',0,'\\41611','\\416',0,'0',0),
        (3736,1,'Allow Click Add Item LQ','',0,'\\41612','\\416',0,'0',0),
        (3737,1,'Allow Click Edit Item LQ','',0,'\\41613','\\416',0,'0',0),
        (3738,1,'Allow Click Delete Item LQ','',0,'\\41614','\\416',0,'0',0),
        (3739,1,'Allow View Amount','',0,'\\41615','\\416',0,'0',0),
        (3740,1,'Allow Click PR Button','',0,'\\41616','\\416',0,'0',0),
        (3741,1,'Allow Void Button','',0,'\\41617','\\416',0,'0',0)";
        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        switch ($companyid) {
            case 16: //ati
                $folder = 'ati';
                break;
        }
        return "($sort,$p,'LQ','/module/" . $folder . "/lq','Cash Liquidation Form','fa fa-money-bill sub_menu_ico',3724)";
    } //end function

    public function parentproduction($params, $parent, $sort)
    {
        $modules = "'prodinstruction,prodorder,finishgoodsentry'";
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3631,0,'PRODUCTION','',0,'$parent','\\',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "insert into left_parent(id,name,seq,class,doc) values($p,'PRODUCTION',$sort,'list_alt'," . $modules . ")";
    } //end function

    public function prodinstruction($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3632,0,'Production Instruction','',0,'\\2701','$parent',0,'0',0),
            (3633,0,'Allow View Transaction PI','PI',0,'\\270101','\\2701',0,'0',0),
            (3634,0,'Allow Click Edit Button PI','',0,'\\270102','\\2701',0,'0',0),
            (3635,0,'Allow Click New Button PI','',0,'\\270103','\\2701',0,'0',0),
            (3636,0,'Allow Click Save Button PI','',0,'\\270104','\\2701',0,'0',0),
            (3637,0,'Allow Click Delete Button PI','',0,'\\270105','\\2701',0,'0',0),
            (3638,0,'Allow Click Print Button PI','',0,'\\270106','\\2701',0,'0',0),
            (3639,0,'Allow Click Lock Button PI','',0,'\\270107','\\2701',0,'0',0),
            (3640,0,'Allow Click UnLock Button PI','',0,'\\270108','\\2701',0,'0',0),
            (3641,0,'Allow Click Post Button PI','',0,'\\270109','\\2701',0,'0',0),
            (3642,0,'Allow Click UnPost Button PI','',0,'\\270110','\\2701',0,'0',0),
            (3643,0,'Allow Click Add Item PI','',0,'\\270111','\\2701',0,'0',0),
            (3644,0,'Allow Click Delete Item PI','',0,'\\270112','\\2701',0,'0',0),
            (3645,0,'Allow Change Amount PI','',0,'\\270113','\\2701',0,'0',0),
            (3646,0,'Allow Click Edit Item PI','',0,'\\270114','\\2701',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PI','/module/production/pi','Production Instruction','fa fa-sync sub_menu_ico',3632)";
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
        $qry = "(3650,0,'" . $label . "','',0,'\\2704','$parent',0,'0',0),
            (3651,0,'Allow View Transaction RM','RM',0,'\\270401','\\2704',0,'0',0),
            (3652,0,'Allow Click Edit Button RM','',0,'\\270402','\\2704',0,'0',0),
            (3653,0,'Allow Click New Button RM','',0,'\\270403','\\2704',0,'0',0),
            (3654,0,'Allow Click Save Button RM','',0,'\\270404','\\2704',0,'0',0),
            (3655,0,'Allow Click Delete Button RM','',0,'\\270405','\\2704',0,'0',0),
            (3656,0,'Allow Click Print Button RM','',0,'\\270406','\\2704',0,'0',0),
            (3657,0,'Allow Click Lock Button RM','',0,'\\270407','\\2704',0,'0',0),
            (3658,0,'Allow Click UnLock Button RM','',0,'\\270408','\\2704',0,'0',0),
            (3659,0,'Allow Click Post Button RM','',0,'\\270409','\\2704',0,'0',0),
            (3660,0,'Allow Click UnPost Button RM','',0,'\\270410','\\2704',0,'0',0),
            (3661,0,'Allow Click Add Item RM','',0,'\\270411','\\2704',0,'0',0),
            (3662,0,'Allow Click Delete Item RM','',0,'\\270412','\\2704',0,'0',0),
            (3663,0,'Allow Change Amount RM','',0,'\\270413','\\2704',0,'0',0),
            (3664,0,'Allow Click Edit Item RM','',0,'\\270414','\\2704',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RM','/module/production/rm','" . $label . "','fa fa-sync sub_menu_ico',3650)";
    }

    public function rn($params, $parent, $sort)
    {
        $label = 'Supplies Issuance';

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3791,0,'" . $label . "','',0,'\\2705','$parent',0,'0',0),
            (3792,0,'Allow View Transaction SI','RN',0,'\\270501','\\2705',0,'0',0),
            (3793,0,'Allow Click Edit Button SI','',0,'\\270502','\\2705',0,'0',0),
            (3794,0,'Allow Click New Button SI','',0,'\\270503','\\2705',0,'0',0),
            (3795,0,'Allow Click Save Button SI','',0,'\\270504','\\2705',0,'0',0),
            (3796,0,'Allow Click Delete Button SI','',0,'\\270505','\\2705',0,'0',0),
            (3797,0,'Allow Click Print Button SI','',0,'\\270506','\\2705',0,'0',0),
            (3798,0,'Allow Click Lock Button SI','',0,'\\270507','\\2705',0,'0',0),
            (3799,0,'Allow Click UnLock Button SI','',0,'\\270508','\\2705',0,'0',0),
            (3800,0,'Allow Click Post Button SI','',0,'\\270509','\\2705',0,'0',0),
            (3801,0,'Allow Click UnPost Button SI','',0,'\\270510','\\2705',0,'0',0),
            (3802,0,'Allow Click Add Item SI','',0,'\\270511','\\2705',0,'0',0),
            (3803,0,'Allow Click Delete Item SI','',0,'\\270512','\\2705',0,'0',0),
            (3804,0,'Allow Change Amount SI','',0,'\\270513','\\2705',0,'0',0),
            (3805,0,'Allow Click Edit Item SI','',0,'\\270514','\\2705',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RN','/module/production/rn','" . $label . "','fa fa-sync sub_menu_ico',3791)";
    }

    public function prodorder($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3672,0,'Production Order','',0,'\\3002','$parent',0,'0',0),
            (3673,0,'Allow View Transaction PD','PD',0,'\\300201','\\3002',0,'0',0),
            (3674,0,'Allow Click Edit Button PD','',0,'\\300202','\\3002',0,'0',0),
            (3675,0,'Allow Click New Button PD','',0,'\\300203','\\3002',0,'0',0),
            (3676,0,'Allow Click Save Button PD','',0,'\\300204','\\3002',0,'0',0),
            (3677,0,'Allow Click Delete Button PD','',0,'\\300205','\\3002',0,'0',0),
            (3678,0,'Allow Click Print Button PD','',0,'\\300206','\\3002',0,'0',0),
            (3679,0,'Allow Click Lock Button PD','',0,'\\300207','\\3002',0,'0',0),
            (3680,0,'Allow Click UnLock Button PD','',0,'\\300208','\\3002',0,'0',0),
            (3681,0,'Allow Click Post Button PD','',0,'\\300209','\\3002',0,'0',0),
            (3682,0,'Allow Click UnPost Button PD','',0,'\\300210','\\3002',0,'0',0),
            (3683,0,'Allow Click Add Item PD','',0,'\\300211','\\3002',0,'0',0),
            (3684,0,'Allow Click Delete Item PD','',0,'\\300212','\\3002',0,'0',0),
            (3685,0,'Allow Click Edit Item PD','',0,'\\300213','\\3002',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PD','/module/production/pd','Production Order','fa fa-sync sub_menu_ico',3672)";
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
        $qry = "(3691,0,'" . $label . "','',0,'\\3003','$parent',0,'0',0),
            (3692,0,'Allow View Transaction FG','FG',0,'\\300301','\\3003',0,'0',0),
            (3693,0,'Allow Click Edit Button FG','',0,'\\300302','\\3003',0,'0',0),
            (3694,0,'Allow Click New Button FG','',0,'\\300303','\\3003',0,'0',0),
            (3695,0,'Allow Click Save Button FG','',0,'\\300304','\\3003',0,'0',0),
            (3696,0,'Allow Click Delete Button FG','',0,'\\300305','\\3003',0,'0',0),
            (3697,0,'Allow Click Print Button FG','',0,'\\300306','\\3003',0,'0',0),
            (3698,0,'Allow Click Lock Button FG','',0,'\\300307','\\3003',0,'0',0),
            (3699,0,'Allow Click UnLock Button FG','',0,'\\300308','\\3003',0,'0',0),
            (3700,0,'Allow Click Post Button FG','',0,'\\300309','\\3003',0,'0',0),
            (3701,0,'Allow Click UnPost Button FG','',0,'\\300310','\\3003',0,'0',0),
            (3702,0,'Allow Click Add Item FG','',0,'\\300311','\\3003',0,'0',0),
            (3703,0,'Allow Click Delete Item FG','',0,'\\300312','\\3003',0,'0',0),
            (3704,0,'Allow Change Amount FG','',0,'\\300313','\\3003',0,'0',0),
            (3705,0,'Allow Click Edit Item FG','',0,'\\300314','\\3003',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'FG','/module/production/fg','" . $label . "','fa fa-box sub_menu_ico',3691)";
    }

    public function bom($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3814,0,'Finished Goods - BOM','',0,'\\3004','$parent',0,'0',0),
            (3815,0,'Allow View Transaction BOM','PI',0,'\\300401','\\3004',0,'0',0),
            (3816,0,'Allow Click Edit Button BOM','',0,'\\300402','\\3004',0,'0',0),
            (3817,0,'Allow Click Save Button BOM','',0,'\\300403','\\3004',0,'0',0),
            (3818,0,'Allow Click Add Item BOM','',0,'\\300404','\\3004',0,'0',0),
            (3819,0,'Allow Click Delete Item BOM','',0,'\\300405','\\3004',0,'0',0),
            (3820,0,'Allow Change Amount BOM','',0,'\\300406','\\3004',0,'0',0),
            (3821,0,'Allow Click Edit Item BOM','',0,'\\300407','\\3004',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'BOM','/ledgergrid/production/bom','Finished Goods - BOM','fa fa-list-alt sub_menu_ico',3814)";
    }

    public function jp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3822,0,'Job Order','',0,'\\3005','$parent',0,'0',0),
        (3823,0,'Allow View Transaction JO','JP',0,'\\300501','\\3005',0,'0',0),
        (3824,0,'Allow Click Edit Button JO','JP',0,'\\300502','\\3005',0,'0',0),
        (3825,0,'Allow Click New  Button JO','JP',0,'\\300503','\\3005',0,'0',0),
        (3826,0,'Allow Click Save Button JO','JP',0,'\\300504','\\3005',0,'0',0),
        (3827,0,'Allow Click Delete Button JO','JP',0,'\\300506','\\3005',0,'0',0),
        (3828,0,'Allow Click Print Button JO','JP',0,'\\300507','\\3005',0,'0',0),
        (3829,0,'Allow Click Lock Button JO','JP',0,'\\300508','\\3005',0,'0',0),
        (3830,0,'Allow Click UnLock Button JO','JP',0,'\\300509','\\3005',0,'0',0),
        (3831,0,'Allow Click Post Button JO','JP',0,'\\300510','\\3005',0,'0',0),
        (3832,0,'Allow Click UnPost  Button JO','JP',0,'\\300511','\\3005',0,'0',0),
        (3833,0,'Allow View Transaction Accounting JO','JP',0,'\\300512','\\3005',0,'0',0),
        (3834,1,'Allow Click Add Item JO','',0,'\\300513','\\3005',0,'0',0),
        (3835,1,'Allow Click Edit Item JO','',0,'\\300514','\\3005',0,'0',0),
        (3836,1,'Allow Click Delete Item JO','',0,'\\300515','\\3005',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'JP','/module/production/jp','Job Order','fa fa-people-carry sub_menu_ico',3822)";
    } //end function

    public function pg($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3837,0,'Production Input','',0,'\\3006','$parent',0,'0',0) ,
        (3838,0,'Allow View Transaction Prod. Input','JP',0,'\\300601','\\3006',0,'0',0) ,
        (3839,0,'Allow Click Edit Button  Prod. Input','',0,'\\300602','\\3006',0,'0',0) ,
        (3840,0,'Allow Click New Button Prod. Input','',0,'\\300603','\\3006',0,'0',0) ,
        (3841,0,'Allow Click Save Button Prod. Input','',0,'\\300604','\\3006',0,'0',0) ,
        (3842,0,'Allow Click Delete Button Prod. Input','',0,'\\300606','\\3006',0,'0',0) ,
        (3843,0,'Allow Click Print Button Prod. Input','',0,'\\300607','\\3006',0,'0',0) ,
        (3844,0,'Allow Click Lock Button Prod. Input','',0,'\\300608','\\3006',0,'0',0) ,
        (3845,0,'Allow Click UnLock Button Prod. Input','',0,'\\300609','\\3006',0,'0',0) ,
        (3846,0,'Allow Click Post Button Prod. Input','',0,'\\300610','\\3006',0,'0',0) ,
        (3847,0,'Allow Click UnPost Button Prod. Input','',0,'\\300611','\\3006',0,'0',0) ,
        (3848,0,'Allow View Transaction Accounting Prod. Input','',0,'\\300612','\\3006',0,'0',0) ,
        (3849,1,'Allow Click Add Item Prod. Input','',0,'\\300613','\\3006',0,'0',0) ,
        (3850,1,'Allow Click Edit Item Prod. Input','',0,'\\300614','\\3006',0,'0',0) ,
        (3851,1,'Allow Click Delete Item Prod. Input','',0,'\\300615','\\3006',0,'0',0) ,
        (3852,1,'Allow Change Amount Prod. Input','',0,'\\300616','\\3006',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'PG','/module/production/pg','Production Input','fa fa-truck-loading sub_menu_ico',3837)";
    } //end function

    public function payments($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3756,1,'Payments','*129',0,'\\1111','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'payments','/tableentries/ati/entrypayments','Payments','fa fa-money-check-alt sub_menu_ico',3756)";
    } //end function

    public function uomlist($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4499,1,'UOM List','',0,'\\1112','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'uomlist','/tableentries/ati/entryuomlist','UOM List','fa fa-list sub_menu_ico',4499)";
    } //end function

    public function conversionuom($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4500,1,'Conversion UOM','',0,'\\1113','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'conversionuom','/tableentries/ati/entryconversionuom','Conversion UOM','fa fa-list sub_menu_ico',4500)";
    } //end function

    public function allowancesetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3757,1,'Allowance Setup','',0,'\\2018','$parent',0,0,0) ,
        (3758,1,'Allow Click Save Button Allowance Setup','',0,'\\201801','\\2018',0,0,0) ,
        (3759,1,'Allow Click Print Button Rate Setup','',0,'\\201802','\\2018',0,0,0) ,
        (3760,1,'Allow View Rate Setup','',0,'\\201803','\\2018',0,0,0) ,
        (3761,1,'Allow Click New Button Allowance Setup','',0,'\\201804','\\2018',0,0,0) ,
        (3762,1,'Allow Click Delete Button Allowance Setup','',0,'\\201805','\\2018',0,0,0) ,
        (3763,1,'Allow Click Edit Button Allowance Setup','',0,'\\201806','\\2018',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'allowancesetup','/ledgergrid/payroll/allowancesetup','Allowance Setup','fa fa-money-bill sub_menu_ico',3757)";
    } //end function

    public function requesttype($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3765,0,'Request Type','',0,'\\821','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'requesttype','/tableentries/othersettings/entryrequesttype','Request Type','fa fa-tags sub_menu_ico',3765)";
    } //end function

    public function itemgroupqoutasetup($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3857,0,'Item Group Qouta Setup','',0,'\\520','$parent',0,'0',0) ,
        (3858,0,'Allow View Transaction','Item Group Qouta Setup',0,'\\52001','\\520',0,'0',0) ,
        (3859,0,'Allow Create Item Group Qouta','',0,'\\52002','\\520',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'ITEMGROUP','/headtable/sales/itemgroupqoutasetup','Item Group Qouta Setup','fa fa-solid fa-object-ungroup sub_menu_ico',3857)";
    } //end function

    public function salesgroupqouta($params, $parent, $sort) // sales per item group per sales agent
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3860,0,'Sales Person Qouta','',0,'\\521','$parent',0,'0',0) ,
        (3861,0,'Allow View Transaction','Sales Group Qouta',0,'\\52101','\\521',0,'0',0) ,
        (3862,0,'Allow Create Sales Group Qouta','',0,'\\52102','\\521',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'SALESGROUP','/headtable/sales/salesgroupqouta','Sales Person Quota','fa fa-object-ungroup sub_menu_ico',3860)";
    } //end function

    public function costcodes($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3867,1,'Cost Codes','*129',0,'\\1112','$parent',0,0,0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'costcodes','/tableentries/tableentry/entrycostcodes','Cost Codes','fa fa-list sub_menu_ico',3867)";
    } //end function


    public function emptimecardperday($params, $parent, $sort)
    {
        return "($sort,$parent,'emptimecardperday','/headtable/payrollcustomform/emptimecardperday','Employee`s Timecard Per Day','fa fa-calendar-day sub_menu_ico',1620)";
    } //end function

    public function empprojectlog($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4525,1,'Work Detail','',0,'\\2019','$parent',0,0,0) ,
        (4526,1,'Allow View Work Detail','',0,'\\201901','\\2019',0,0,0) ,
        (4527,1,'Allow Click Button Save','',0,'\\201902','\\2019',0,0,0)";
        $this->insertattribute($params, $qry);

        return "($sort,$p,'empprojectlog','/headtable/payrollcustomform/empprojectlog','Work Detail','fa fa-calendar-day sub_menu_ico',4525)";
    } //end function

    public function fs($params, $parent, $sort) // financing setup
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3892,0,'Financing Setup','',0,'\\310','$parent',0,'0',0) ,
        (3893,0,'Allow View Transaction FS','FS',0,'\\31001','\\706',0,'0',0) ,
        (3895,0,'Allow Click Edit Button  FS','',0,'\\31002','\\706',0,'0',0) ,
        (3896,0,'Allow Click New Button FS','',0,'\\31003','\\706',0,'0',0) ,
        (3897,0,'Allow Click Save Button FS','',0,'\\31004','\\706',0,'0',0) ,
        (3898,0,'Allow Click Delete Button FS','',0,'\\31006','\\706',0,'0',0) ,
        (3899,0,'Allow Click Print Button FS','',0,'\\31007','\\706',0,'0',0) ,
        (3900,0,'Allow Click Lock Button FS','',0,'\\31008','\\706',0,'0',0) ,
        (3901,0,'Allow Click UnLock Button FS','',0,'\\31009','\\706',0,'0',0) ,
        (3902,0,'Allow Click Post Button FS','',0,'\\31010','\\706',0,'0',0) ,
        (3903,0,'Allow Click UnPost Button FS','',0,'\\31011','\\706',0,'0',0),
        (3905,0,'Allow Click Delete Accounts FS','',0,'\\31012','\\706',0,'0',0),
        (3904,0,'Allow Click Generate Sched Button FS','',0,'\\31013','\\706',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'FS','/module/receivable/fs','Financing Setup','fas fa-wallet sub_menu_ico',3892)";
    } //end function

    public function rc($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3906,0,'Received Checks','',0,'\\309','$parent',0,'0',0) ,
        (3907,0,'Allow View Transaction RC ','RC',0,'\\30901','\\309',0,'0',0) ,
        (3908,0,'Allow Click Edit Button  RC ','',0,'\\30902','\\309',0,'0',0) ,
        (3909,0,'Allow Click New Button RC ','',0,'\\30903','\\309',0,'0',0) ,
        (3910,0,'Allow Click Save Button RC ','',0,'\\30904','\\309',0,'0',0) ,
        (3911,0,'Allow Click Delete Button RC ','',0,'\\30906','\\309',0,'0',0) ,
        (3912,0,'Allow Click Print Button RC ','',0,'\\30907','\\309',0,'0',0) ,
        (3913,0,'Allow Click Lock Button RC ','',0,'\\30908','\\309',0,'0',0) ,
        (3914,0,'Allow Click UnLock Button RC ','',0,'\\30909','\\309',0,'0',0) ,
        (3915,0,'Allow Click Post Button RC ','',0,'\\30910','\\309',0,'0',0) ,
        (3916,0,'Allow Click UnPost Button RC ','',0,'\\30911','\\309',0,'0',0) ,
        (3917,0,'Allow Click Add Account RC','',0,'\\30912','\\309',0,'0',0) ,
        (3918,0,'Allow Click Edit Account RC','',0,'\\30913','\\309',0,'0',0) ,
        (3919,0,'Allow Click Delete Account RC','',0,'\\30914','\\309',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'RC','/module/receivable/rc','Received Checks','fa fa-money-check sub_menu_ico',3906)";
    } //end function

    public function pu($params, $parent, $sort)
    {
        $companyid = $params['companyid'];

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(3922,0,'Material Purchase Order','',0,'\\417','$parent',0,'0',0),
        (3923,0,'Allow View Transaction PU','PU',0,'\\41701','\\417',0,'0',0),
        (3924,0,'Allow Click Edit Button PU','',0,'\\41702','\\417',0,'0',0),
        (3925,0,'Allow Click New Button PU','',0,'\\41703','\\417',0,'0',0),
        (3926,0,'Allow Click Save Button PU','',0,'\\41704','\\417',0,'0',0),
        (3927,0,'Allow Click Delete Button PU','',0,'\\41706','\\417',0,'0',0),
        (3928,0,'Allow Click Print Button PU','',0,'\\41707','\\417',0,'0',0),
        (3929,0,'Allow Click Lock Button PU','',0,'\\41708','\\417',0,'0',0),
        (3930,0,'Allow Click UnLock Button PU','',0,'\\41709','\\417',0,'0',0),
        (3931,0,'Allow Change Amount PU','',0,'\\41710','\\417',0,'0',0),
        (3932,0,'Allow Click Post Button PU','',0,'\\41712','\\417',0,'0',0),
        (3933,0,'Allow Click UnPost  Button PU','',0,'\\41713','\\417',0,'0',0),
        (3934,1,'Allow Click Add Item PU','',0,'\\41714','\\417',0,'0',0),
        (3935,1,'Allow Click Edit Item PU','',0,'\\41715','\\417',0,'0',0),
        (3936,1,'Allow Click Delete Item PU','',0,'\\41716','\\417',0,'0',0),
        (3937,1,'Allow View Amount PU','',0,'\\41717','\\417',0,'0',0),
        (3938,1,'Allow Click PR Button PU','',0,'\\41718','\\417',0,'0',0)";
        $this->insertattribute($params, $qry);

        $folder = 'purchase';
        return "($sort,$p,'PU','/module/" . $folder . "/pu','Material Purchase Order','fa fa-folder sub_menu_ico',3922)";
    } //end function

    public function ru($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Material Receiving Report';

        $qry = "(3938,0,'" . $label . "','',0,'\\418','$parent',0,'0',0),
        (3939,0,'Allow View Transaction RU','RU',0,'\\41801','\\418',0,'0',0),
        (3940,0,'Allow Click Edit Button RU','',0,'\\41802','\\418',0,'0',0),
        (3941,0,'Allow Click New Button RU','',0,'\\41803','\\418',0,'0',0),
        (3942,0,'Allow Click Save Button RU','',0,'\\41804','\\418',0,'0',0),
        (3943,0,'Allow Click Delete Button RU','',0,'\\41806','\\418',0,'0',0),
        (3944,0,'Allow Click Print Button RU','',0,'\\41807','\\418',0,'0',0),
        (3945,0,'Allow Click Lock Button RU','',0,'\\41808','\\418',0,'0',0),
        (3946,0,'Allow Click UnLock Button RU','',0,'\\41809','\\418',0,'0',0),
        (3947,0,'Allow Click Post Button RU','',0,'\\41810','\\418',0,'0',0),
        (3948,0,'Allow Click UnPost Button RU','',0,'\\41811','\\418',0,'0',0),
        (3949,0,'Allow View Transaction accounting RU','',0,'\\41812','\\418',0,'0',0),
        (3950,0,'Allow Change Amount RU','',0,'\\41813','\\418',0,'0',0),
        (3951,1,'Allow Click Add Item RU','',0,'\\41814','\\418',0,'0',0),
        (3952,1,'Allow Click Edit Item RU','',0,'\\41815','\\418',0,'0',0),
        (3953,1,'Allow Click Delete Item RU','',0,'\\41816','\\418',0,'0',0)";

        $folder = 'purchase';

        $this->insertattribute($params, $qry);
        return "($sort,$p,'RU','/module/" . $folder . "/ru','" . $label . "','fa fa-folder-open sub_menu_ico',3938)";
    } //end function

    public function plangroup($params, $parent, $sort)
    {

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4038,1,'Plan Group','',0,'\\1113','$parent',0,'0',0),
        (4097,0,'Allow Add Plan Types','AR',0,'\\111301','\\1113',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'plangroup','/tableentries/tableentry/entryplangroup','Plan Group','fa fa-tasks sub_menu_ico',4038)";
    } //end function


    public function af($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4039,0,'Application Form','',0,'\\3007','$parent',0,'0',0) ,
        (4040,0,'Allow View Transaction AF','AR',0,'\\300701','\\3007',0,'0',0) ,
        (4041,0,'Allow Click Edit Button  AF','',0,'\\300702','\\3007',0,'0',0) ,
        (4042,0,'Allow Click New Button AF','',0,'\\300703','\\3007',0,'0',0) ,
        (4043,0,'Allow Click Save Button AF','',0,'\\300704','\\3007',0,'0',0) ,
        (4044,0,'Allow Click Delete Button AF','',0,'\\300706','\\3007',0,'0',0) ,
        (4045,0,'Allow Click Print Button AF','',0,'\\300707','\\3007',0,'0',0) ,
        (4046,0,'Allow Click Lock Button AF','',0,'\\300708','\\3007',0,'0',0) ,
        (4047,0,'Allow Click UnLock Button AF','',0,'\\300709','\\3007',0,'0',0) ,
        (4048,0,'Allow Click Post Button AF','',0,'\\300710','\\3007',0,'0',0) ,
        (4049,0,'Allow Click UnPost Button AF','',0,'\\300711','\\3007',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'AF','/module/operation/af','Application Form','fa fa-file-alt sub_menu_ico',4039)";
    } //end function

    public function cp($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4073,0,'Life Plan Agreement','',0,'\\3008','$parent',0,'0',0) ,
        (4060,0,'Allow View Transaction CP','CP',0,'\\300801','\\3008',0,'0',0) ,
        (4061,0,'Allow Click Edit Button  CP','',0,'\\300802','\\3008',0,'0',0) ,
        (4062,0,'Allow Click New Button CP','',0,'\\300803','\\3008',0,'0',0) ,
        (4063,0,'Allow Click Save Button CP','',0,'\\300804','\\3008',0,'0',0) ,
        (4064,0,'Allow Click Delete Button CP','',0,'\\300806','\\3008',0,'0',0) ,
        (4065,0,'Allow Click Print Button CP','',0,'\\300807','\\3008',0,'0',0) ,
        (4066,0,'Allow Click Lock Button CP','',0,'\\300808','\\3008',0,'0',0) ,
        (4067,0,'Allow Click UnLock Button CP','',0,'\\300809','\\3008',0,'0',0) ,
        (4068,0,'Allow Click Post Button CP','',0,'\\300810','\\3008',0,'0',0) ,
        (4069,0,'Allow Click UnPost Button CP','',0,'\\300811','\\3008',0,'0',0),
        (4172,0,'Allow Click Print Certificate of Full Payment Button CP','',0,'\\300811','\\3008',0,'0',0) ";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CP','/module/operation/cp','Life Plan Agreement','fa fa-clipboard-list sub_menu_ico',4073)";
    } //end function

    public function aquastockcard($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $label = 'Meter Master';

        $qry = "(4104,0,'" . $label . "','',0,'\\124','$parent',0,'0',0),
        (4105,0,'Allow View " . $label . "','SK',0,'\\12401','\\124',0,'0',0),
        (4106,0,'Allow Click Edit Button Meter Master','',0,'\\12402','\\124',0,'0',0),
        (4107,0,'Allow Click New Button Meter Master','',0,'\\12403','\\124',0,'0',0),
        (4108,0,'Allow Click Save Button Meter Master','',0,'\\12404','\\124',0,'0',0),
        (4110,0,'Allow Click Delete Button Meter Master','',0,'\\12406','\\124',0,'0',0),
        (4111,0,'Allow Print Button Meter Master','',0,'\\12407','\\124',0,'0',0)";

        $this->insertattribute($params, $qry);

        return "($sort,$p,'stockcard','/ledgergrid/waterbilling/stockcard','" . $label . "','fas fa-tachometer-alt sub_menu_ico',4104)";
    } //end function

    public function wn($params, $parent, $sort)
    {
        $companyid = $params['companyid'];
        $modulename = 'Water Connection';

        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4112,0,'" . $modulename . "','',0,'\\3009','$parent',0,'0',0),
        (4113,0,'Allow View Transaction WN','WN',0,'\\300901','\\3009',0,'0',0),
        (4114,0,'Allow Click Edit Button WN','',0,'\\300902','\\3009',0,'0',0),
        (4115,0,'Allow Click New Button WN','',0,'\\300903','\\3009',0,'0',0),
        (4116,0,'Allow Click Save Button WN','',0,'\\300904','\\3009',0,'0',0),
        (4117,0,'Allow Click Delete Button WN','',0,'\\300906','\\3009',0,'0',0),
        (4118,0,'Allow Click Print Button WN','',0,'\\300907','\\3009',0,'0',0),
        (4119,0,'Allow Click Lock Button WN','',0,'\\300908','\\3009',0,'0',0),
        (4120,0,'Allow Click UnLock Button WN','',0,'\\300909','\\3009',0,'0',0),
        (4121,0,'Allow Click Post Button WN','',0,'\\300910','\\3009',0,'0',0),
        (4109,0,'Allow Click UnPost  Button WN','',0,'\\300911','\\3009',0,'0',0)";

        $this->insertattribute($params, $qry);

        return "($sort,$p,'wn','/module/waterbilling/wn','" . $modulename . "','fa fa-shower sub_menu_ico',4112)";
    } //end function

    public function wm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4124,0,'Consumption','',0,'\\3010','$parent',0,'0',0),
        (4125,0,'Allow View Transaction','WM',0,'\\301001','\\3010',0,'0',0),
        (4126,0,'Allow Click Edit Button','',0,'\\301002','\\3010',0,'0',0),
        (4127,0,'Allow Click New  Button','',0,'\\301003','\\3010',0,'0',0),
        (4128,0,'Allow Click Save Button','',0,'\\301004','\\3010',0,'0',0),
        (4129,0,'Allow Click Delete Button','',0,'\\301006','\\3010',0,'0',0),
        (4130,0,'Allow Click Print Button','',0,'\\301007','\\3010',0,'0',0),
        (4131,0,'Allow Click Lock Button','',0,'\\301008','\\3010',0,'0',0),
        (4132,0,'Allow Click UnLock Button','',0,'\\301009','\\3010',0,'0',0),
        (4133,0,'Allow Click Post Button','',0,'\\301010','\\3010',0,'0',0),
        (4134,0,'Allow Click UnPost Button','',0,'\\301011','\\3010',0,'0',0),
        (4135,0,'Allow Change Amount','',0,'\\301013','\\3010',0,'0',0),
        (4136,0,'Allow View Transaction Accounting','',0,'\\301016','\\3010',0,'0',0),
        (4137,1,'Allow Click Add Item','',0,'\\301017','\\3010',0,'0',0),
        (4138,1,'Allow Click Edit Item','',0,'\\301018','\\3010',0,'0',0),
        (4139,1,'Allow Click Delete Item','',0,'\\301019','\\3010',0,'0',0)";

        $this->insertattribute($params, $qry);

        return "($sort,$p,'WM','/module/waterbilling/wm','Consumption','fas fa-tachometer-alt sub_menu_ico',4124)";
    } //end function


    public function mm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4436,0,'Merging Barcode','',0,'\\3026','$parent',0,'0',0),
        (4437,0,'Allow View Transaction','MM',0,'\\302601','\\3026',0,'0',0),
        (4438,0,'Allow Click Edit Button','',0,'\\302602','\\3026',0,'0',0),
        (4439,0,'Allow Click New  Button','',0,'\\302603','\\3026',0,'0',0),
        (4440,0,'Allow Click Save Button','',0,'\\302604','\\3026',0,'0',0),
        (4441,0,'Allow Click Delete Button','',0,'\\302605','\\3026',0,'0',0),
        (4442,0,'Allow Click Print Button','',0,'\\302606','\\3026',0,'0',0),
        (4443,0,'Allow Click Lock Button','',0,'\\302607','\\3026',0,'0',0),
        (4444,0,'Allow Click UnLock Button','',0,'\\302608','\\3026',0,'0',0),
        (4445,0,'Allow Click Post Button','',0,'\\302609','\\3026',0,'0',0),
        (4446,0,'Allow Click UnPost Button','',0,'\\302610','\\3026',0,'0',0),
        (4447,1,'Allow Click Add Item','',0,'\\302611','\\3026',0,'0',0),
        (4448,1,'Allow Click Delete Item','',0,'\\302612','\\3026',0,'0',0),
        (4450,1,'Allow Click Edit Item','',0,'\\302613','\\3026',0,'0',0)";

        $this->insertattribute($params, $qry);

        return "($sort,$p,'MM','/module/ati/mm','Merging Barcode','fas fa-clipboard-list sub_menu_ico',4436)";
    } //end function

    public function ci($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4458,0,'Spare Parts Issuance','',0,'\\3088','$parent',0,'0',0),
        (4459,0,'Allow View Transaction CI','CI',0,'\\308801','\\3088',0,'0',0),
        (4460,0,'Allow Click Edit Button CI','',0,'\\308802','\\3088',0,'0',0),
        (4461,0,'Allow Click New  Button CI','',0,'\\308803','\\3088',0,'0',0),
        (4462,0,'Allow Click Save Button CI','',0,'\\308804','\\3088',0,'0',0),
        (4463,0,'Allow Click Delete Button CI','',0,'\\308805','\\3088',0,'0',0),
        (4464,0,'Allow Click Print Button CI','',0,'\\308806','\\3088',0,'0',0),
        (4465,0,'Allow Click Lock Button CI','',0,'\\308807','\\3088',0,'0',0),
        (4466,0,'Allow Click UnLock Button CI','',0,'\\308808','\\3088',0,'0',0),
        (4467,0,'Allow Click Post Button CI','',0,'\\308809','\\3088',0,'0',0),
        (4468,0,'Allow Click UnPost  Button CI','',0,'\\308810','\\3088',0,'0',0),
        (4469,0,'Allow Change Amount  CI','',0,'\\308811','\\3088',0,'0',0),
        (4470,0,'Allow Check Credit Limit CI','',0,'\\308812','\\3088',0,'0',0),
        (4471,0,'Allow CI Amount Auto-Compute on UOM Change','',0,'\\308813','\\3088',0,'0',0),
        (4472,0,'Allow View Transaction Accounting CI','',0,'\\308814','\\3088',0,'0',0),
        (4473,1,'Allow Click Add Item CI','',0,'\\308815','\\3088',0,'0',0),
        (4474,1,'Allow Click Edit Item CI','',0,'\\308816','\\3088',0,'0',0),
        (4475,1,'Allow Click Delete Item CI','',0,'\\308817','\\3088',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'CI','/module/cdo/ci','Spare Parts Issuance','fa fa-boxes sub_menu_ico',4458)";
    } //end function

    public function ti($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4511,0,'Tripping Incentive','',0,'\\2019','$parent',0,'0',0) ,
        (4512,0,'Allow View Transaction TI ','TI',0,'\\201901','\\2019',0,'0',0) ,
        (4513,0,'Allow Click Edit Button  TI ','',0,'\\201902','\\2019',0,'0',0) ,
        (4514,0,'Allow Click New Button TI ','',0,'\\201903','\\2019',0,'0',0) ,
        (4515,0,'Allow Click Save Button TI ','',0,'\\201904','\\2019',0,'0',0) ,
        (4516,0,'Allow Click Delete Button TI ','',0,'\\201905','\\2019',0,'0',0) ,
        (4517,0,'Allow Click Print Button TI ','',0,'\\201906','\\2019',0,'0',0) ,
        (4518,0,'Allow Click Lock Button TI ','',0,'\\201907','\\2019',0,'0',0) ,
        (4519,0,'Allow Click UnLock Button TI ','',0,'\\201908','\\2019',0,'0',0) ,
        (4520,0,'Allow Click Post Button TI ','',0,'\\201909','\\2019',0,'0',0) ,
        (4521,0,'Allow Click UnPost Button TI ','',0,'\\201910','\\2019',0,'0',0) ,
        (4522,0,'Allow Click Add Account TI','',0,'\\201911','\\2019',0,'0',0) ,
        (4523,0,'Allow Click Edit Account TI','',0,'\\201912','\\2019',0,'0',0) ,
        (4524,0,'Allow Click Delete Account TI','',0,'\\201913','\\2019',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'TI','/module/payroll/ti','Tripping Incentive','fa fa-calculator sub_menu_ico',4511)";
    } //end function



    public function sm($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4529,0,'Supplier Invoice','',0,'\\2020','$parent',0,'0',0) ,
        (4530,0,'Allow View Transaction SM ','SM',0,'\\202001','\\2020',0,'0',0) ,
        (4531,0,'Allow Click Edit Button  SM ','',0,'\\202002','\\2020',0,'0',0) ,
        (4532,0,'Allow Click New Button SM ','',0,'\\202003','\\2020',0,'0',0) ,
        (4533,0,'Allow Click Save Button SM ','',0,'\\202004','\\2020',0,'0',0) ,
        (4534,0,'Allow Click Delete Button SM ','',0,'\\202005','\\2020',0,'0',0) ,
        (4535,0,'Allow Click Print Button SM ','',0,'\\202006','\\2020',0,'0',0) ,
        (4536,0,'Allow Click Lock Button SM ','',0,'\\202007','\\2020',0,'0',0) ,
        (4537,0,'Allow Click UnLock Button SM ','',0,'\\202008','\\2020',0,'0',0) ,
        (4538,0,'Allow Click Post Button SM ','',0,'\\202009','\\2020',0,'0',0) ,
        (4539,0,'Allow Click UnPost Button SM ','',0,'\\202010','\\2020',0,'0',0) ,
        (4540,0,'Allow View Transaction accounting','',0,'\\202011','\\2020',0,'0',0),
        (4541,1,'Allow Click Add RR','',0,'\\202012','\\2020',0,'0',0),
        (4542,1,'Allow Click Delete Item','',0,'\\202013','\\2020',0,'0',0),
        (4572,1,'Allow Click Edit Item','',0,'\\202014','\\2020',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'SM','/module/cbbsi/sm','Supplier Invoice','fa fa-calculator sub_menu_ico',4529)";
    } //end function
    public function activitymaster($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4543,0,'Activity Master','',0,'\\2021','$parent',0,'0',0)";
        $this->insertattribute($params, $qry);
        return "($sort,$p,'activitymaster','/tableentries/tableentry/entryactivitymaster','Activity Master','fa fa-tasks sub_menu_ico',4543)";
    } //end function
    public function eq($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4544,0,'Equipment Monitoring','',0,'\\2022','$parent',0,'0',0) ,
        (4545,0,'Allow View Transaction EQ ','EQ',0,'\\202201','\\2022',0,'0',0) ,
        (4546,0,'Allow Click Edit Button  EQ ','',0,'\\202202','\\2022',0,'0',0) ,
        (4547,0,'Allow Click New Button EQ ','',0,'\\202203','\\2022',0,'0',0) ,
        (4548,0,'Allow Click Save Button EQ ','',0,'\\202204','\\2022',0,'0',0) ,
        (4549,0,'Allow Click Delete Button EQ ','',0,'\\202205','\\2022',0,'0',0) ,
        (4550,0,'Allow Click Print Button EQ ','',0,'\\202206','\\2022',0,'0',0) ,
        (4551,0,'Allow Click Lock Button EQ ','',0,'\\202207','\\2022',0,'0',0) ,
        (4552,0,'Allow Click UnLock Button EQ ','',0,'\\202208','\\2022',0,'0',0) ,
        (4553,0,'Allow Click Post Button EQ ','',0,'\\202209','\\2022',0,'0',0) ,
        (4554,0,'Allow Click UnPost Button EQ ','',0,'\\202210','\\2022',0,'0',0),
        (4555,1,'Allow Click Add Activity EQ','',0,'\\202211','\\2022',0,'0',0),
        (4556,1,'Allow Click Edit Activity EQ','',0,'\\202212','\\2022',0,'0',0),
        (4557,1,'Allow Click Delete Activity EQ','',0,'\\202213','\\2022',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'EQ','/module/sales/eq','Equipment Monitoring','fa fa-calculator sub_menu_ico',4544)";
    } //end function

    public function oi($params, $parent, $sort)
    {
        $p = $parent;
        $parent = '\\' . $parent;
        $qry = "(4558,0,'Operator Incentive','',0,'\\2023','$parent',0,'0',0) ,
        (4559,0,'Allow View Transaction OI ','OI',0,'\\202301','\\2023',0,'0',0) ,
        (4560,0,'Allow Click Edit Button  OI ','',0,'\\202302','\\2023',0,'0',0) ,
        (4561,0,'Allow Click New Button OI ','',0,'\\202303','\\2023',0,'0',0) ,
        (4562,0,'Allow Click Save Button OI ','',0,'\\202304','\\2023',0,'0',0) ,
        (4563,0,'Allow Click Delete Button OI ','',0,'\\202305','\\2023',0,'0',0) ,
        (4564,0,'Allow Click Print Button OI ','',0,'\\202306','\\2023',0,'0',0) ,
        (4565,0,'Allow Click Lock Button OI ','',0,'\\202307','\\2023',0,'0',0) ,
        (4566,0,'Allow Click UnLock Button OI ','',0,'\\202308','\\2023',0,'0',0) ,
        (4567,0,'Allow Click Post Button OI ','',0,'\\202309','\\2023',0,'0',0) ,
        (4568,0,'Allow Click UnPost Button OI ','',0,'\\202310','\\2023',0,'0',0) ,
        (4569,0,'Allow Click Add Account OI','',0,'\\202311','\\2023',0,'0',0) ,
        (4570,0,'Allow Click Edit Account OI','',0,'\\202312','\\2023',0,'0',0) ,
        (4571,0,'Allow Click Delete Account OI','',0,'\\202313','\\2023',0,'0',0)";

        $this->insertattribute($params, $qry);
        return "($sort,$p,'OI','/module/payroll/oi','Operator Incentive','fa fa-street-view sub_menu_ico',4558)";
    } //end function




}//end class
