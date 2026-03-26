<?php

namespace App\Http\Classes;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;

use Exception;
use Throwable;
use Session;



class menusetupclient
{

  private $othersClass;
  private $coreFunctions;
  public $companysetup;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
  }


  public function setupparentmenu($params)
  {


    $solabel = "Sales Order";
    $sjlabel = "Sales Journal";
    $sjdoc = "SJ";

    $stockgrplabel = "Item Group Master";
    $partlabel = "Part Master";
    $modellabel = "Model Master";
    $classlabel = "Item Class Master";
    $transferlabel = "Transfer Slip";
    $brandlabel = "Brand Master";
    $categorieslabel = "Cust/Supp Categories Master";
    $projectlabel = "Project Master";

    $leftmenu = [];
    $this->coreFunctions->execqry('truncate left_parent', 'truncate');
    $this->coreFunctions->execqry('truncate left_menu', 'truncate');




    //sales
    $left_parent = "insert into left_parent(id,name,seq,class,doc) values(3,'SALES',4,'trending_up',',SO,SJ,CM')";
    $left_menu = "insert into left_menu (seq,parent_id,doc,url,module,class,access) 
    values(1,3,'SO','/module/sales/so','" . $solabel . "','fa fa-clipboard-list sub_menu_ico',151)";

    $menu['sales'] = [$left_parent, $left_menu];


    $modules = $this->companysetup->getmodule($params);
    $leftmenu = $this->othersClass->array_only($menu, $modules);

    foreach ($leftmenu as $key => $value) {
      $this->execute($value);
    }
  } //end function







  private function execute($arr)
  {
    foreach ($arr as $key) {
      $this->coreFunctions->execqry($key, 'insert');
    }
  } //end function

  public function setupparentmenuhms($params)
  {

    $leftparents  = [];
    $leftchilds = [];

    array_push($leftparents, "insert into left_parent(id,name,seq,class,doc) values(1,'MASTERFILE',1,'description',',roomtype')");
    array_push($leftparents, "insert into left_parent(id,name,seq,class,doc) values(11,'TRANSACTION UTILITIES',11,'fa fa-cogs',',docprefix,terms,changeitem,audittrail,notification')");

    array_push($leftchilds, "insert into left_menu (parent_id,doc,url,module,class,access) values(1,'roomtype','/ledger/masterfile/roomtype','Room Type','fa fa-list sub_menu_ico',21)");

    array_push($leftchilds, "insert into left_menu (parent_id,doc,url,module,class,access) values(11,'prefix','/tableentries/tableentry/entryprefix','Manage Prefixes','fab fa-autoprefixer sub_menu_ico',599)");

    $this->coreFunctions->execqry('truncate left_parent', 'truncate');
    $this->coreFunctions->execqry('truncate left_menu', 'truncate');
    foreach ($leftparents as $key) {
      $this->coreFunctions->execqry($key, 'insert');
    }

    foreach ($leftchilds as $key) {
      $this->coreFunctions->execqry($key, 'insert');
    }
  }

  // DEFAULT REPORTS
  public function setupreportmenulist($params)
  {

    // AMS AND AIMS
    // MASTERFILE
    $masterfile = "('','\\9','','','',0,0,0,'Masterfile Report','\\900',3101,'0')";
    $rep_chartofaccounts = "('','\\900','','','',0,1,0,'Chart of Accounts','\\90006',3102,'0')";
    $warehouse_list = "('','\\900','','','',0,1,0,'Warehouse List','\\90007',3103,'0')";
    $employee_list = "('','\\900','','','',0,1,0,'Employee List','\\90008',3104,'0')";
    $department_list = "('','\\900','','','',0,1,0,'Department List','\\90009',3105,'0')";

    // ACCOUNTING BOOKS
    $parent_accountingbooks = "('','\\9','','','',0,0,0,'Accounting Books','\\901',3001,'0')";
    $rep_cashdisbursementbook = "('','\\901','','','',0,1,0,'Cash Disbursement Book','\\90101',3002,'0')";
    $rep_cashreceiptbook = "('','\\901','','','',0,1,0,'Cash Receipt Book','\\90102',3003,'0')";
    $rep_journalvoucher = "('','\\901','','','',0,1,0,'Journal Voucher','\\90103',3004,'0')";
    $rep_purchasejournal = "('','\\901','','','',0,1,0,'Purchase Journal','\\90104',3005,'0')";
    $rep_salesjournal = "('','\\901','','','',0,1,0,'Sales Journal','\\90105',3006,'0')";
    $rep_debit_memo = "('','\\901','','','',0,1,0,'Debit Memo','\\90106',3404,'0')";
    $rep_credit_memo = "('','\\901','','','',0,1,0,'Credit Memo','\\90107',3405,'0')";

    // CHECK MONITORING REPORTS
    $parent_checkmonitoringreports = "('','\\9','','','',0,0,0,'Check Monitoring Reports','\\902',3008,'0')";
    $rep_bouncedchecks = "('','\\902','','','',0,1,0,'Bounced Checks','\\90201',3009,'0')";
    $rep_issuedchecks = "('','\\902','','','',0,1,0,'Issued Checks','\\90202',3010,'0')";
    $rep_receivedchecks = "('','\\902','','','',0,1,0,'Received Checks','\\90203',3011,'0')";
    $rep_undepositedchecks = "('','\\902','','','',0,1,0,'Undeposited Checks','\\90204',3012,'0')";

    // FINANCIAL STATEMENTS
    $parent_financialstatements = "('','\\9','','','',0,0,0,'Financial Statements','\\903',3013,'0')";
    $rep_balancesheet = "('','\\903','','','',0,1,0,'Balance Sheet','\\90301',3014,'0')";
    $rep_incomestatement = "('','\\903','','','',0,1,0,'Income Statement','\\90302',3015,'0')";
    $rep_subsidiaryledger = "('','\\903','','','',0,1,0,'Subsidiary Ledger','\\90303',3016,'0')";
    $rep_trialbalance = "('','\\903','','','',0,1,0,'Trial Balance','\\90304',3017,'0')";
    $rep_comparativeincomestatment = "('','\\903','','','',0,1,0,'Comparative Income Statement','\\90306',3083,'0')";
    $rep_comparativebalancesheet = "('','\\903','','','',0,1,0,'Comparative Balance Sheet','\\90307',3084,'0')";
    $rep_comparativetrialbalance = "('','\\903','','','',0,1,0,'Comparative Trial Balance','\\90309',3480,'0')";
    $rep_monthlyincomestatement = "('','\\903','','','',0,1,0,'Monthly Income Statement','\\90308',3085,'0')";

    // ITEMS
    $parent_items = "('','\\9','','','',0,0,0,'Items','\\904',3018,'0')";
    $rep_inventorybalance = "('','\\904','','','',0,1,0,'Inventory Balance','\\90401',3019,'0')";
    $rep_analyzeitempurchasemonthly = "('','\\904','','','',0,1,0,'Monthly Analyze Item Purchase','\\90402',3020,'0')";
    $rep_analyzeitemsalesmonthly = "('','\\904','','','',0,1,0,'Monthly Analyze Item Sales','\\90403',3021,'0')";
    $rep_itemlist = "('','\\904','','','',0,1,0,'Item List','\\90404',3022,'0')";
    $rep_currentinventoryaging = "('','\\904','','','',0,1,0,'Current Inventory Aging','\\9041',3023,'0')";
    $rep_fastmovingitems = "('','\\904','','','',0,1,0,'Fast Moving Items','\\90405',3024,'0')";
    $rep_slowmovingitems = "('','\\904','','','',0,1,0,'Slow Moving Items','\\90408',3027,'0')";
    $rep_analyzeitemsaleswithprofitmarkup = "('','\\904','','','',0,1,0,'Analyze Item Sales with Profit Markup','\\90406',3025,'0')";
    $rep_itempurchasereport = "('','\\904','','','',0,1,0,'Item Purchase Report','\\90425',3139,'1')";
    $rep_salesperitempercustomer = "('','\\904','','','',0,1,0,'Sales Per Item Per Customer','\\90409',3028,'0')";
    $rep_itemtoexpired = "('','\\904','','','',0,1,0,'Item to Expired','\\90410',3029,'0')";
    $rep_itembalance_belowminimum = "('','\\904','','','',0,1,0,'Item Balance Below Minimum','\\90411',3030,'0')";
    $rep_itembalance_aboveminimum = "('','\\904','','','',0,1,0,'Item Balance Above Maximum','\\90412',3031,'0')";
    $rep_reorder = "('','\\904','','','',0,1,0,'Reorder Report','\\90507',3112,'0')";
    if ($params['companyid'] == 39) { //cbbsi
      $rep_item_list_with_cost = "('','\\904','','','',0,1,0,'Item List With Cost','\\90445',4408,'0')";
    } else {
      $rep_item_list_with_cost = "";
    }
    if ($this->companysetup->getsystemtype($params) == 'AMS') {
      $parent_items = "";
      $rep_inventorybalance = "";
      $rep_analyzeitempurchasemonthly = "";
      $rep_analyzeitemsalesmonthly = "";
      $rep_itemlist = "";
      $rep_currentinventoryaging = "";
      $rep_fastmovingitems = "";
      $rep_slowmovingitems = "";
      $rep_analyzeitemsaleswithprofitmarkup = "";
      $rep_itempurchasereport = "";
      $rep_salesperitempercustomer = "";
      $rep_itemtoexpired = "";
      $rep_itembalance_belowminimum = "";
      $rep_itembalance_aboveminimum = "";
      $rep_reorder = "";
    }

    $rep_unservedsalesorder = "";
    $rep_unservedstockreq = "";
    // CUSTOMER
    $parent_customers = "('','\\9','','','',0,0,0,'Customers','\\905',3032,'0')";
    $rep_customerlist = "('','\\905','','','',0,1,0,'Customers List','\\90501',3033,'0')";
    $rep_currentcustomerreceivable = "('','\\905','','','',0,1,0,'Current Customer Receivables','\\90502',3034,'0')";
    $rep_currentcustomerreceivableaging = "('','\\905','','','',0,1,0,'Current Customer Receivables Aging','\\90503',3035,'0')";
    $rep_analyzecustomersalesmonthly = "('','\\905','','','',0,1,0,'Analyze Customer Sales Monthly','\\90504',3036,'0')";
    $rep_customersalesreport = "('','\\905','','','',0,1,0,'Customer Sales Report','\\90505',3037,'0')";
    $rep_pendingsalesorders = "('','\\905','','','',0,1,0,'Pending Sales Orders','\\90506',3038,'0')";
    $rep_customerperformancereport = "('','\\905','','','',0,1,0,'Customer Performance Report','\\90509',3053,'0')";
    $rep_analyzecustomercollectionmonthly = "('','\\905','','','',0,1,0,'Analyze Customer Collection Monthly','\\90519',3127,'0')";
    $rep_salespercustomerperitem = "('','\\905','','','',0,1,0,'Sales Per Customer Per Item','\\90510',3082,'0')";
    $rep_monthlysalesreport_graphy = "('','\\905','','','',0,1,0,'Monthly Sales Report Graph','\\90511',3039,'0')";
    $rep_salescomparison_graph = "('','\\905','','','',0,1,0,'Sales Comparison Graph','\\90512',3086,'0')";

    switch ($params['companyid']) {
      case 39: //cbbsi
        $rep_unservedsalesorder = "('','\\905','','','',0,1,0,'Unserved Sales Orders','\\90515',4361,'0')";
        $rep_unservedstockreq = "('','\\905','','','',0,1,0,'Unserved Stock Requests','\\90516',4367,'0')";
        break;
      case 19: //housegem
        $rep_pendingsomonitoring  = "('','\\905','','','',0,1,0,'Pending Sales Order Monitoring','\\90542',5023,'0')";
        break;
    }

    // SUPPLIER
    $parent_supplier = "('','\\9','','','',0,0,0,'Supplier','\\906',3040,'0')";
    $rep_supplierlist = "('','\\906','','','',0,1,0,'Supplier List','\\90601',3041,'0')";
    $rep_currentsupplierpayables = "('','\\906','','','',0,1,0,'Current Supplier Payables','\\90602',3042,'0')";
    $rep_currentsupplierpayablesaging = "('','\\906','','','',0,1,0,'Current Supplier Payables Aging','\\90603',3043,'0')";
    $rep_analyzedsupplierpurchasesmonthly = "('','\\906','','','',0,1,0,'Analyzed Supplier Purchases Monthly','\\90604',3044,'0')";
    $rep_supplierpurchasereport = "('','\\906','','','',0,1,0,'Supplier Purchase Report','\\90605',3045,'0')";
    $rep_pendingpurchaseorders = "('','\\906','','','',0,1,0,'Pending Purchase Orders','\\90606',3046,'0')";
    $rep_supplierperformancereport = "('','\\906','','','',0,1,0,'Supplier Performance Report','\\90609',3054,'0')";
    $rep_purchasepersupp = "('','\\906','','','',0,1,0,'Purchase Per Supplier','\\90612',3093,'0')";
    $rep_monthlypurchasesreport_graph = "('','\\906','','','',0,1,0,'Monthly Purchases Report Graph','\\90607',3399,'0')";
    $rep_purchasescomparison_graph = "('','\\906','','','',0,1,0,'Purchases Comparison Graph','\\90608',3398,'0')";
    $rep_withholdingtax = "('','\\906','','','',0,1,0,'Withholding Tax Report','\\90611',3400,'0')";

    // SALES AGENT
    $parent_salesagent = "('','\\9','','','',0,0,0,'Sales Agent','\\907',3047,'0')";
    $rep_salesagentlist = "('','\\907','','','',0,1,0,'Sales Agent List','\\90701',3048,'0')";
    $rep_analyzedagentsalesmonthly = "('','\\907','','','',0,1,0,'Analyzed Agent Sales Monthly','\\90702',3049,'0')";

    // OTHER REPORTS
    $parent_otherreports = "('','\\9','','','',0,0,0,'Other Reports','\\908',3050,'0')";
    $rep_statementofaccount = "('','\\908','','','',0,1,0,'Statement of Account','\\90801',3051,'0')";
    $rep_expensesreport = "('','\\908','','','',0,1,0,'Expenses Report','\\90804',3052,'0')";
    $rep_receivingconsignmentreport = "('','\\906','','','',0,1,0,'Receiving Consignment Report','\\90610',3055,'0')";
    $user_access_report = "('','\\908','','','',0,1,0,'User Access Report','\\90805',3100,'0')";

    switch ($params['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
        $rep_daily_sales_report = "('','\\908','','','',0,1,0,'Daily Sales Report','\\90815',3408,'0')";
        $rep_sales_report = "('','\\908','','','',0,1,0,'Sales Report','\\90807',3410,'0')";
        break;

      default:
        $rep_sales_report = "";
        $rep_daily_sales_report = "";
        break;
    }

    $parent_purchasingreports = "";
    $rep_canvasssheet = "";
    $rep_oraclecoderequest = "";
    $rep_purchaseorderdraft = "";
    $rep_poforapproval = "";
    $rep_createreceivingrep = "";
    $rep_createpurchasereturn = "";
    $rep_createcheckvoucher = "";
    $rep_createstockissuance = "";
    $rep_forsomonitoring = "";
    $rep_generateassettag = "";
    $rep_paymentreleased = "";
    $rep_advancesmonitoring = "";

    if ($params['companyid'] == 16) { //ATI
      // PURCHASING REPORTS
      $parent_purchasingreports = "('','\\9','','','',0,0,0,'Purchasing Reports','\\9099',4145,'0')";
      $rep_canvasssheet = "('','\\9099','','','',0,1,0,'Create Canvass Sheet','\\9099001',4146,'0')";
      $rep_oraclecoderequest = "('','\\9099','','','',0,1,0,'Create Oracle Code Request','\\9099002',4147,'0')";
      $rep_purchaseorderdraft = "('','\\9099','','','',0,1,0,'Create Purchase Order Draft','\\9099003',4148,'0')";
      $rep_poforapproval = "('','\\9099','','','',0,1,0,'PO For Approval','\\9099004',4149,'0')";

      $rep_createreceivingrep = "('','\\9099','','','',0,1,0,'Create Receiving Report','\\9099005',4150,'0')";
      $rep_createpurchasereturn = "('','\\9099','','','',0,1,0,'Create Purchase Return','\\9099006',4151,'0')";
      $rep_createcheckvoucher = "('','\\9099','','','',0,1,0,'Create Cash Check Voucher','\\9099007',4152,'0')";
      $rep_createstockissuance = "('','\\9099','','','',0,1,0,'Create Stock Issuance','\\9099008',4153,'0')";
      $rep_forsomonitoring = "('','\\9099','','','',0,1,0,'For SO Monitoring','\\9099009',4191,'0')";
      $rep_generateassettag = "('','\\9099','','','',0,1,0,'Generate Asset Tag','\\9099011',4194,'0')";
      $rep_paymentreleased = "('','\\9099','','','',0,1,0,'Payment Released','\\9099012',4477,'0')";
      $rep_advancesmonitoring = "('','\\9099','','','',0,1,0,'Advances Monitoring','\\9099013',4485,'0')";
    }

    // TRANSACTION LIST
    $parent_transactionlist = "('','\\9','','','',0,0,0,'Transaction List','\\909',3056,'0')";
    $subparent_purchases = "('','\\909','','','',0,0,0,'Purchases','\\90901',3057,'0')";
    $rep_purchaserequisitionreport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Requisition Report','\\9090101',3061,'0')";
    $rep_purchaseorderreport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Order Report','\\9090102',3060,'0')";
    $rep_receivingreport = "('','\\90901','','\\\\909','',0,1,0,'Receiving Report','\\9090103',3058,'0')";
    $rep_purchasereturnreport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Return Report','\\9090104',3059,'0')";

    $subparent_sales = "('','\\909','','','',0,0,0,'Sales','\\90902',3062,'0')";
    $rep_salesorderreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Order Report','\\9090201',3063,'0')";
    $rep_salesjournalreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Journal Report','\\9090202',3064,'0')";
    $rep_salesreturnreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Return Report','\\9090203',3065,'0')";

    $subparent_inventory = "('','\\909','','','',0,0,0,'Inventory','\\90903',3066,'0')";
    $rep_inventorysetupreport = "('','\\90903','','\\\\909','',0,1,0,'Inventory Setup Report','\\9090301',3067,'0')";
    $rep_physicalcountreport = "('','\\90903','','\\\\909','',0,1,0,'Physical Count Report','\\9090302',3068,'0')";
    $rep_transferslipreport = "('','\\90903','','\\\\909','',0,1,0,'Transfer Slip Report','\\9090303',3069,'0')";
    $rep_inventoryadjustmentreport = "('','\\90903','','\\\\909','',0,1,0,'Inventory Adjustment Report','\\9090304',3070,'0')";

    $subparent_payables = "('','\\909','','','',0,0,0,'Payables','\\90904',3071,'0')";
    $rep_apsetupreport = "('','\\90904','','\\\\909','',0,1,0,'AP Setup','\\9090401',3072,'0')";
    $rep_apvoucherreport = "('','\\90904','','\\\\909','',0,1,0,'AP Voucher','\\9090402',3073,'0')";
    $rep_cashcheckvoucherreport = "('','\\90904','','\\\\909','',0,1,0,'Cash Check Voucher','\\9090403',3074,'0')";

    $subparent_receivables = "('','\\909','','','',0,0,0,'Receivables','\\90905',3075,'0')";
    $rep_arsetupreport = "('','\\90905','','\\\\909','',0,1,0,'AR Setup','\\9090501',3076,'0')";
    $rep_receivedpaymentreport = "('','\\90905','','\\\\909','',0,1,0,'Received Payment','\\9090502',3077,'0')";
    $rep_counterreceiptreport = "('','\\90905','','\\\\909','',0,1,0,'Counter Receipt','\\9090503',3078,'0')";

    $subparent_accounting = "('','\\909','','','',0,0,0,'Accounting','\\90906',3079,'0')";
    $rep_generaljournalreport = "('','\\90906','','\\\\909','',0,1,0,'General Journal','\\9090601',3080,'0')";
    $rep_depositslipreport = "('','\\90906','','\\\\909','',0,1,0,'Deposit Slip','\\9090602',3081,'0')";
    $rep_debit_memo_sum = "('','\\90906','','\\\\909','',0,1,0,'Debit Memo Summary','\\9090603',3406,'0')";
    $rep_credit_memo_sum = "('','\\90906','','\\\\909','',0,1,0,'Credit Memo Summary','\\9090604',3407,'0')";

    $rep_monthlysummary_outputtax = "('','\\905','','','',0,1,0,'Monthly Summary of Output Tax','\\90513',3401,'0')";
    $monthly_sum_ewt = "('','\\906','','','',0,1,0,'Monthly Summary of EWT Report','\\90613',3402,'0')";
    $rep_withholdingtax = "('','\\906','','','',0,1,0,'Monthly Summary of Input Tax','\\90614',3403,'0')";

    $rep_operatorhistory = "('','\\90902','','\\\\909','',0,1,0,'Operator History','\\9090222',4574,'0')"; //migty

    //Mall Report List

    $parent_mallreportlist = "";
    $rep_statement_of_account_report = "";
    $rep_summary_of_other_charges_report = "";
    $rep_daily_collection_report = "";
    $rep_comparative_data_of_billing_and_collection = "";
    $rep_collection_summary_report = "";
    $rep_billing_summary_report = "";
    $rep_collection_letter_report = "";
    $rep_billing_vs_collection_report = "";
    switch ($this->companysetup->getsystemtype($params)) {
      case 'MMS':
        //Mall Reports

        $parent_mallreportlist = "('','\\9','','','',0,0,0,'Mall Report List','\\914',4281,'0')";
        $rep_statement_of_account_report = "('','\\914','','','',0,1,0,'Statement of Account Report','\\91309',4282,'0')";
        $rep_summary_of_other_charges_report = "('','\\914','','','',0,1,0,'Summary of Other Charges Report','\\91310',4283,'0')";
        $rep_daily_collection_report = "('','\\914','','','',0,1,0,'Daily Collection Report','\\91311',4284,'0')";
        $rep_comparative_data_of_billing_and_collection = "('','\\914','','','',0,1,0,'Comparative Data of Billing and Collection','\\91312',4285,'0')";
        $rep_collection_summary_report = "('','\\914','','','',0,1,0,'Collection Summary','\\91313',4302,'0')";
        $rep_billing_summary_report = "('','\\914','','','',0,1,0,'Billing Summary','\\91314',4307,'0')";
        $rep_collection_letter_report = "('','\\914','','','',0,1,0,'Collection Letter','\\91315',4308,'0')";
        $rep_billing_vs_collection_report = "('','\\914','','','',0,1,0,'Billing vs Collection','\\91316',4309,'0')";
        break;
    }


    // Motorcycle Reports-CDO
    $parent_motorcyclerep = "";
    $rep_soldout_unit = "";
    $rep_mc_availability = "";
    $rep_average_sales_per_mc_unit = "";
    $rep_total_sell = "";
    $rep_total_delivery_report = "";
    if ($params['companyid'] == 40) { //cdo
      $parent_motorcyclerep = "('','\\9','','','',0,0,0,'Motorcycle Reports','\\915',4486,'0')";
      $rep_soldout_unit = "('','\\915','','','',0,1,0,'Sold Out Unit','\\91501',4487,'0')";
      $rep_mc_availability = "('','\\915','','','',0,1,0,'MC Availability','\\91502',4497,'0')";
      $rep_average_sales_per_mc_unit = "('','\\915','','','',0,1,0,'Average Sales Per MC Unit','\\91503',4498,'0')";
      $rep_total_sell = "('','\\915','','','',0,1,0,'Total Sell','\\91504',4509,'0')";
      $rep_total_delivery_report = "('','\\915','','','',0,1,0,'Total Delivery Report','\\91505',4510,'0')";
    }


    // TRIP MIGHTY
    $parent_trip = "";
    $rep_trip_detailed = "";
    $rep_trip_summary = "";
    $rep_staff_trip_summary = "";

    if ($params['companyid'] == 43) { //mighty
      $parent_trip = "('','\\T1','','','',0,0,0,'Trip Reports','\\916',4504,'0')";
      $rep_trip_detailed = "('','\\T2','','','',0,0,0,'Trip Detailed','\\91601',4505,'0')";
      $rep_trip_summary = "('','\\T3','','','',0,0,0,'Trip Summary','\\91602',4506,'0')";
      $rep_staff_trip_summary = "('','\\T4','','','',0,0,0,'Staff Trip Summary','\\91603',4507,'0')";
    }

    // CARGO LOGISTICS
    $parent_cargologistics = "";
    $rep_waybillreport = "";

    if ($params['companyid'] == 48) { //seastar
      $parent_cargologistics = "('','\\','','','',0,0,0,'Cargo Logistics','\\910',4766,'0')";
      $rep_waybillreport = "('','\\','','','',0,0,0,'Waybill Report','\\91001',4767,'0')";
    }

    // HRIS
    $subparent_applicant = "('','\\A','','','',0,0,0,'licant','\\A01',3413,'0')";
    $rep_hris_applicant_listing = "('','\\A01','','','',0,1,0,'Applicant Listing','\\A0101',3414,'0')";

    if ($this->companysetup->getsystemtype($params) == 'HRISPAYROLL') {
      $rep_payroll_employee_listing = "('','\\A01','','','',0,1,0,'Employee Listing','\\A0120',3416,'0')";
    }

    // PAYROLL
    $subparent_other_report = "('','\\B','','','',0,0,0,'Payroll Other Report','\\B01',3415,'0')";

    if ($this->companysetup->getsystemtype($params) != 'HRISPAYROLL') {
      $rep_payroll_employee_listing = "('','\\B01','','','',0,1,0,'Employee Listing','\\B0101',3416,'0')";
    }
    $rep_month_pay_13th = "('','\\B01','','','',0,1,0,'Employee 13th Month Pay','\\B0102',3417,'0')";
    $rep_dep_advise = "('','\\B01','','','',0,1,0,'Deposit Advice','\\B0103',3433,'0')";
    $rep_emp_loan_balance = "('','\\B01','','','',0,1,0,'Employee Loan Balances','\\B0104',3434,'0')";
    $rep_emp_rate_ = "('','\\B01','','','',0,1,0,'Employee Rate Report','\\B0105',3435,'0')";

    $rep_emp_timein_out_logs = "('','\\B01','','','',0,1,0,'Employee Time in and Time out Logs','\\B0106',3624,'0')";

    $subparent_bir_tax_report = "('','\\B','','','',0,0,0,'Bir Tax Reports','\\B02',3418,'0')";
    $rep_payroll_tax_witheld = "('','\\B02','','','',0,1,0,'Tax Withheld Report','\\B0201',3419,'0')";

    $subparent_pagibig_report = "('','\\B','','','',0,0,0,'Pag Ibig Reports','\\B03',3420,'0')";
    $rep_payroll_pagibig_remittance = "('','\\B03','','','',0,1,0,'Pag Ibig Remittance Report','\\B0301',3421,'0')";
    $rep_payroll_pagibig_loan_payment = "('','\\B03','','','',0,1,0,'Pag Ibig Loan Payment Report','\\B0302',3422,'0')";

    $subparent_philhealth_report = "('','\\B','','','',0,0,0,'Philhealth Reports','\\B04',3423,'0')";
    $rep_payroll_philhealth_remittance = "('','\\B04','','','',0,1,0,'Philhealth Remittance Report','\\B0401',3424,'0')";

    $subparent_sss_report = "('','\\B','','','',0,0,0,'SSS Reports','\\B05',3425,'0')";
    $rep_payroll_sss_remittance = "('','\\B05','','','',0,1,0,'SSS Remittance Report','\\B0501',3426,'0')";
    $rep_payroll_sss_loan_payment = "('','\\B05','','','',0,1,0,'SSS Loan Payment Report','\\B0502',3427,'0')";

    $subparent_payroll_report = "('','\\B','','','',0,0,0,'Payroll Reports','\\B06',3428,'0')";
    $rep_payslip = "('','\\B06','','','',0,1,0,'Pay Slip','\\B0601',3429,'0')";
    $rep_payregister = "('','\\B06','','','',0,1,0,'Pay Register','\\B0602',3430,'0')";

    $subparent_time_attendance_report = "('','\\B','','','',0,0,0,'Time and Attendance Report','\\B07',3431,'0')";
    $rep_dtr = "('','\\B07','','','',0,1,0,'Daily Time Record','\\B0701',3432,'0')";

    $subparent_production = '';
    $rep_prod_joborderreport = "";
    $rep_prodinput_report = "";

    if ($params['companyid'] == 27 && $params['companyid'] == 36) { //nte, rozlab
      $subparent_production = "('','\\909','','','',0,0,0,'Production','\\90911',3954,'0')";
      $rep_prod_joborderreport = "('','\\90911','','\\\\909','',0,1,0,'Production Job Order Report','\\9091104',4075,'0')";
      $rep_prodinput_report = "('','\\90911','','\\\\909','',0,1,0,'Production Input Report','\\9091105',4076,'0')";
    }



    switch ($this->companysetup->getsystemtype($params)) {
      case 'MIS':
        $report_sysmenu = [
          $masterfile,
          $warehouse_list,
          $employee_list,
          $department_list,

          // ITEMS
          $parent_items,
          $rep_inventorybalance,
          $rep_analyzeitempurchasemonthly,
          $rep_analyzeitemsalesmonthly,
          $rep_itemlist,
          $rep_currentinventoryaging,
          $rep_fastmovingitems,
          $rep_analyzeitemsaleswithprofitmarkup,
          $rep_slowmovingitems,
          $rep_itempurchasereport,
          $rep_salesperitempercustomer,
          $rep_itemtoexpired,
          $rep_itembalance_belowminimum,
          $rep_itembalance_aboveminimum,
          $rep_reorder,

          // CUSTOMER
          $parent_customers,
          $rep_customerlist,
          $rep_analyzecustomersalesmonthly,
          $rep_customersalesreport,
          $rep_pendingsalesorders,
          $rep_customerperformancereport,
          $rep_salespercustomerperitem,
          $rep_salescomparison_graph,
          $rep_monthlysalesreport_graphy,


          // SUPPLIER
          $parent_supplier,
          $rep_supplierlist,
          $rep_analyzedsupplierpurchasesmonthly,
          $rep_supplierpurchasereport,
          $rep_pendingpurchaseorders,
          $rep_supplierperformancereport,
          $rep_purchasepersupp,
          $rep_monthlypurchasesreport_graph,
          $rep_purchasescomparison_graph,

          // SALES AGENT
          $parent_salesagent,
          $rep_salesagentlist,
          $rep_analyzedagentsalesmonthly,

          // OTHER REPORTS
          $parent_otherreports,
          $rep_receivingconsignmentreport,
          $user_access_report,

          // TRANSACTION LIST
          $parent_transactionlist,
          $subparent_purchases,
          $rep_purchaserequisitionreport,
          $rep_purchaseorderreport,
          $rep_receivingreport,
          $rep_purchasereturnreport,
          $subparent_sales,
          $rep_salesorderreport,
          $rep_salesjournalreport,
          $rep_salesreturnreport,
          $subparent_inventory,
          $rep_inventorysetupreport,
          $rep_physicalcountreport,
          $rep_transferslipreport,
          $rep_inventoryadjustmentreport,
        ];

        break;
      case 'AMS':
        $report_sysmenu = [
          $masterfile,
          $rep_chartofaccounts,
          $employee_list,
          $department_list,
          // ACCOUNTING BOOKS
          $parent_accountingbooks,
          $rep_cashdisbursementbook,
          $rep_cashreceiptbook,
          $rep_journalvoucher,
          $rep_purchasejournal,
          $rep_salesjournal,
          // CHECK MONITORING REPORTS
          $parent_checkmonitoringreports,
          $rep_bouncedchecks,
          $rep_issuedchecks,
          $rep_receivedchecks,
          $rep_undepositedchecks,
          // FINANCIAL STATEMENTS
          $parent_financialstatements,
          $rep_balancesheet,
          $rep_incomestatement,
          $rep_subsidiaryledger,
          $rep_trialbalance,
          $rep_comparativeincomestatment,
          $rep_comparativebalancesheet,
          $rep_comparativetrialbalance,
          $rep_monthlyincomestatement,

          // CUSTOMER
          $parent_customers,
          $rep_customerlist,
          $rep_currentcustomerreceivable,
          $rep_currentcustomerreceivableaging,
          $rep_analyzecustomersalesmonthly,
          $rep_customersalesreport,
          $rep_customerperformancereport,
          $rep_analyzecustomercollectionmonthly,
          $rep_salescomparison_graph,
          $rep_monthlysalesreport_graphy,
          $rep_monthlysummary_outputtax,
          $monthly_sum_ewt,

          // SUPPLIER
          $parent_supplier,
          $rep_supplierlist,
          $rep_currentsupplierpayables,
          $rep_currentsupplierpayablesaging,
          $rep_analyzedsupplierpurchasesmonthly,
          $rep_supplierpurchasereport,
          $rep_supplierperformancereport,
          $rep_purchasepersupp,
          $rep_monthlypurchasesreport_graph,
          $rep_purchasescomparison_graph,
          $rep_withholdingtax,

          // OTHER REPORTS
          $parent_otherreports,
          $rep_statementofaccount,
          $rep_expensesreport,
          $user_access_report,

          // TRANSACTION LIST
          $parent_transactionlist,
          $subparent_payables,
          $rep_apsetupreport,
          $rep_apvoucherreport,
          $rep_cashcheckvoucherreport,
          $subparent_receivables,
          $rep_arsetupreport,
          $rep_receivedpaymentreport,
          $rep_counterreceiptreport,
          $subparent_accounting,
          $rep_generaljournalreport,
          $rep_depositslipreport,

          //CARGO LOGISTICS
          $parent_cargologistics,
          $rep_waybillreport,
        ];
        break;
      default:
        $report_sysmenu = [
          $masterfile,
          $rep_chartofaccounts,
          $warehouse_list,
          $employee_list,
          $department_list,
          // ACCOUNTING BOOKS
          $parent_accountingbooks,
          $rep_cashdisbursementbook,
          $rep_cashreceiptbook,
          $rep_journalvoucher,
          $rep_purchasejournal,
          $rep_salesjournal,
          // CHECK MONITORING REPORTS
          $parent_checkmonitoringreports,
          $rep_bouncedchecks,
          $rep_issuedchecks,
          $rep_receivedchecks,
          $rep_undepositedchecks,

          // FINANCIAL STATEMENTS
          $parent_financialstatements,
          $rep_balancesheet,
          $rep_incomestatement,
          $rep_subsidiaryledger,
          $rep_trialbalance,
          $rep_comparativeincomestatment,
          $rep_comparativebalancesheet,
          $rep_comparativetrialbalance,
          $rep_monthlyincomestatement,

          // ITEMS
          $parent_items,
          $rep_inventorybalance,
          $rep_analyzeitempurchasemonthly,
          $rep_analyzeitemsalesmonthly,
          $rep_itemlist,
          $rep_currentinventoryaging,
          $rep_fastmovingitems,
          $rep_analyzeitemsaleswithprofitmarkup,
          $rep_slowmovingitems,
          $rep_itempurchasereport,
          $rep_salesperitempercustomer,
          $rep_itemtoexpired,
          $rep_itembalance_belowminimum,
          $rep_itembalance_aboveminimum,
          $rep_reorder,
          $rep_item_list_with_cost,

          // CUSTOMER
          $parent_customers,
          $rep_customerlist,
          $rep_currentcustomerreceivable,
          $rep_currentcustomerreceivableaging,
          $rep_analyzecustomersalesmonthly,
          $rep_customersalesreport,
          $rep_pendingsalesorders,
          $rep_customerperformancereport,
          $rep_analyzecustomercollectionmonthly,
          $rep_salespercustomerperitem,
          $rep_salescomparison_graph,
          $rep_monthlysalesreport_graphy,
          $rep_monthlysummary_outputtax,
          $monthly_sum_ewt,
          $rep_unservedsalesorder,
          $rep_unservedstockreq,
          $rep_pendingsomonitoring,

          // SUPPLIER
          $parent_supplier,
          $rep_supplierlist,
          $rep_currentsupplierpayables,
          $rep_currentsupplierpayablesaging,
          $rep_analyzedsupplierpurchasesmonthly,
          $rep_supplierpurchasereport,
          $rep_pendingpurchaseorders,
          $rep_supplierperformancereport,
          $rep_purchasepersupp,
          $rep_monthlypurchasesreport_graph,
          $rep_purchasescomparison_graph,
          $rep_withholdingtax,

          // SALES AGENT
          $parent_salesagent,
          $rep_salesagentlist,
          $rep_analyzedagentsalesmonthly,

          // OTHER REPORTS
          $parent_otherreports,
          $rep_statementofaccount,
          $rep_expensesreport,
          $rep_receivingconsignmentreport,
          $user_access_report,

          // TRANSACTION LIST
          $parent_transactionlist,
          $subparent_purchases,
          $rep_purchaserequisitionreport,
          $rep_purchaseorderreport,
          $rep_receivingreport,
          $rep_purchasereturnreport,
          $subparent_sales,
          $rep_salesorderreport,
          $rep_salesjournalreport,
          $rep_salesreturnreport,
          $subparent_inventory,
          $rep_inventorysetupreport,
          $rep_physicalcountreport,
          $rep_transferslipreport,
          $rep_inventoryadjustmentreport,
          $subparent_payables,
          $rep_apsetupreport,
          $rep_apvoucherreport,
          $rep_cashcheckvoucherreport,
          $subparent_receivables,
          $rep_arsetupreport,
          $rep_receivedpaymentreport,
          $rep_counterreceiptreport,
          $subparent_accounting,
          $rep_generaljournalreport,
          $rep_depositslipreport,
          $subparent_production,
          $rep_prod_joborderreport,
          $rep_prodinput_report,
          $rep_operatorhistory,

          //Mall Report
          $parent_mallreportlist,
          $rep_statement_of_account_report,
          $rep_summary_of_other_charges_report,
          $rep_daily_collection_report,
          $rep_comparative_data_of_billing_and_collection,
          $rep_collection_summary_report,
          $rep_billing_summary_report,
          $rep_collection_letter_report,
          $rep_billing_vs_collection_report,

          //PURCHASING REPORTS
          $parent_purchasingreports,
          $rep_canvasssheet,
          $rep_oraclecoderequest,
          $rep_purchaseorderdraft,
          $rep_poforapproval,
          $rep_createreceivingrep,
          $rep_createpurchasereturn,
          $rep_createcheckvoucher,
          $rep_createstockissuance,
          $rep_forsomonitoring,
          $rep_generateassettag,
          $rep_paymentreleased,
          $rep_advancesmonitoring,

          //motorcyle reports
          $parent_motorcyclerep,
          $rep_soldout_unit,
          $rep_mc_availability,
          $rep_average_sales_per_mc_unit,
          $rep_total_sell,
          $rep_total_delivery_report,

          // TRIP REPORT
          $parent_trip,
          $rep_trip_detailed,
          $rep_trip_summary,
          $rep_staff_trip_summary


        ];
        break;
    }

    $report_sysmenu = [
      $masterfile,
      $rep_chartofaccounts,
      $warehouse_list,
      $employee_list,
      $department_list,
      // ACCOUNTING BOOKS
      $parent_accountingbooks,
      $rep_cashdisbursementbook,
      $rep_cashreceiptbook,
      $rep_journalvoucher,
      $rep_purchasejournal,
      $rep_salesjournal,
      $rep_debit_memo,
      $rep_credit_memo,
      // CHECK MONITORING REPORTS
      $parent_checkmonitoringreports,
      $rep_bouncedchecks,
      $rep_issuedchecks,
      $rep_receivedchecks,
      $rep_undepositedchecks,
      // FINANCIAL STATEMENTS
      $parent_financialstatements,
      $rep_balancesheet,
      $rep_incomestatement,
      $rep_subsidiaryledger,
      $rep_trialbalance,
      $rep_comparativeincomestatment,
      $rep_comparativebalancesheet,
      $rep_comparativetrialbalance,
      $rep_monthlyincomestatement,

      // ITEMS
      $parent_items,
      $rep_inventorybalance,
      $rep_analyzeitempurchasemonthly,
      $rep_analyzeitemsalesmonthly,
      $rep_itemlist,
      $rep_currentinventoryaging,
      $rep_fastmovingitems,
      $rep_analyzeitemsaleswithprofitmarkup,
      $rep_slowmovingitems,
      $rep_itempurchasereport,
      $rep_salesperitempercustomer,
      $rep_itemtoexpired,
      $rep_itembalance_belowminimum,
      $rep_itembalance_aboveminimum,
      $rep_reorder,
      $rep_item_list_with_cost,

      // CUSTOMER
      $parent_customers,
      $rep_customerlist,
      $rep_currentcustomerreceivable,
      $rep_currentcustomerreceivableaging,
      $rep_analyzecustomersalesmonthly,
      $rep_customersalesreport,
      $rep_pendingsalesorders,
      $rep_customerperformancereport,
      $rep_analyzecustomercollectionmonthly,
      $rep_salespercustomerperitem,
      $rep_salescomparison_graph,
      $rep_monthlysalesreport_graphy,
      $rep_monthlysummary_outputtax,
      $monthly_sum_ewt,
      $rep_unservedsalesorder,
      $rep_unservedstockreq,
      $rep_pendingsomonitoring,

      // SUPPLIER
      $parent_supplier,
      $rep_supplierlist,
      $rep_currentsupplierpayables,
      $rep_currentsupplierpayablesaging,
      $rep_analyzedsupplierpurchasesmonthly,
      $rep_supplierpurchasereport,
      $rep_pendingpurchaseorders,
      $rep_supplierperformancereport,
      $rep_purchasepersupp,
      $rep_monthlypurchasesreport_graph,
      $rep_purchasescomparison_graph,
      $rep_withholdingtax,

      // SALES AGENT
      $parent_salesagent,
      $rep_salesagentlist,
      $rep_analyzedagentsalesmonthly,

      // OTHER REPORTS
      $parent_otherreports,
      $rep_statementofaccount,
      $rep_expensesreport,
      $rep_receivingconsignmentreport,
      $user_access_report,
      $rep_daily_sales_report,
      $rep_sales_report,

      // TRANSACTION LIST
      $parent_transactionlist,
      $subparent_purchases,
      $rep_purchaserequisitionreport,
      $rep_purchaseorderreport,
      $rep_receivingreport,
      $rep_purchasereturnreport,
      $subparent_sales,
      $rep_salesorderreport,
      $rep_salesjournalreport,
      $rep_salesreturnreport,
      $subparent_inventory,
      $rep_inventorysetupreport,
      $rep_physicalcountreport,
      $rep_transferslipreport,
      $rep_inventoryadjustmentreport,
      $subparent_payables,
      $rep_apsetupreport,
      $rep_apvoucherreport,
      $rep_cashcheckvoucherreport,
      $subparent_receivables,
      $rep_arsetupreport,
      $rep_receivedpaymentreport,
      $rep_counterreceiptreport,
      $subparent_accounting,
      $rep_generaljournalreport,
      $rep_depositslipreport,
      $rep_debit_memo_sum,
      $rep_credit_memo_sum,
      $rep_operatorhistory,

      //PRODUCTION
      $subparent_production,
      $rep_prod_joborderreport,
      $rep_prodinput_report,

      //Mall Report
      $parent_mallreportlist,
      $rep_statement_of_account_report,
      $rep_summary_of_other_charges_report,
      $rep_comparative_data_of_billing_and_collection,
      $rep_billing_summary_report,
      $rep_collection_letter_report,


      // HRIS
      $subparent_applicant,
      $rep_hris_applicant_listing,
      // PAYROLL
      $subparent_other_report,
      $rep_payroll_employee_listing,
      $rep_month_pay_13th,
      $rep_dep_advise,
      $rep_emp_loan_balance,
      $rep_emp_rate_,
      $rep_emp_timein_out_logs,
      $subparent_bir_tax_report,
      $rep_payroll_tax_witheld,
      $subparent_pagibig_report,
      $rep_payroll_pagibig_remittance,
      $rep_payroll_pagibig_loan_payment,
      $subparent_philhealth_report,
      $rep_payroll_philhealth_remittance,
      $subparent_sss_report,
      $rep_payroll_sss_remittance,
      $rep_payroll_sss_loan_payment,
      $subparent_payroll_report,
      $rep_payslip,
      $rep_payregister,
      $subparent_time_attendance_report,
      $rep_dtr,

      //PURCHASING REPORTS
      $parent_purchasingreports,
      $rep_canvasssheet,
      $rep_oraclecoderequest,
      $rep_purchaseorderdraft,
      $rep_poforapproval,
      $rep_createreceivingrep,
      $rep_createpurchasereturn,
      $rep_createcheckvoucher,
      $rep_createstockissuance,
      $rep_forsomonitoring,
      $rep_generateassettag,
      $rep_paymentreleased,
      $rep_advancesmonitoring,

      //motorcyle reports
      $parent_motorcyclerep,
      $rep_soldout_unit,
      $rep_mc_availability,
      $rep_average_sales_per_mc_unit,
      $rep_total_sell,
      $rep_total_delivery_report,

      // TRIP
      $parent_trip,
      $rep_trip_detailed,
      $rep_trip_summary,
      $rep_staff_trip_summary

    ];


    return $report_sysmenu;
  }

  public function generatereportlist($params)
  {
    $qryparent = "insert into `attributes` 
                (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`) 
                values (3000,0,'REPORTS','',0,'\\\\9','\\\\',0,'0',0)";
    $s = $this->coreFunctions->execqry($qryparent, 'insert');

    // HRIS
    $qryparent = "insert into `attributes` 
                (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`) 
                values (3411,0,'HRIS REPORTS','',0,'\\\\A','\\\\',0,'0',0)";
    $s = $this->coreFunctions->execqry($qryparent, 'insert');

    // PAYROLL
    $qryparent = "insert into `attributes` 
                (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`) 
                values (3412,0,'PAYROLL REPORTS','',0,'\\\\B','\\\\',0,'0',0)";
    $s = $this->coreFunctions->execqry($qryparent, 'insert');

    $generalmenu = $this->setupreportmenulist($params);

    $this->coreFunctions->execqry('truncate menu', 'truncate');

    foreach ($generalmenu as $key => $value) {
      if ($value != "") {
        $nipps = explode(',', $value);
        $nipps[1] = str_replace("'", "", $nipps[1]);
        $nipps[9] = str_replace("'", "", $nipps[9]);

        $qry = "insert into `menu` 
                    (`menu`,`parent`,`title`,`alias`,`icon`,`isexpanded`,`seq`,`isok`,`description`,`code`,`attribute`,`ismodified`) 
                    values " . $nipps[0] . "," . "'\\" . $nipps[1] . "'" . "," . $nipps[2] . "," . $nipps[3] . "," . $nipps[4] . "," .
          $nipps[5] . "," . $nipps[6] . "," . $nipps[7] . "," . $nipps[8] . "," . "'\\" . $nipps[9] . "'" . "," . $nipps[10] . "," . $nipps[11];
        $this->coreFunctions->execqry($qry, 'insert');

        $qry = "insert into `attributes` 
                  (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`) 
                  values (" . $nipps[10] . ",1," . $nipps[8] . "," . $nipps[3] . ",0," .
          "'\\" . $nipps[9] . "','\\" . $nipps[1] . "'" . ",0,0,0)";

        $this->coreFunctions->execqry($qry, 'insert');
      } //end if
    } //end each
  }

  public function generateaccesslist($params)
  {


    $systype = $this->companysetup->getsystemtype($params);
    $accesslist = $this->setupaccesslist($params);
    $this->coreFunctions->execqry('truncate attributes', 'truncate');

    foreach ($accesslist as $key) {
      foreach ($key as $key2) {
        foreach ($key2 as $key3) {
          $key3 = str_replace("\\", '\\\\', $key3);
          $qry = "insert into `attributes` (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`) values " . $key3;
          $this->coreFunctions->execqry($qry, 'insert');
        }
      }
    }
  } //end function



  private function setupaccesslist($params)
  {
    //1
    $menu['masterfile'] = [
      'parent' => ["(1,0,'MASTER FILE','',0,'\\1','\\',0,'',0)"],
      'children' => [
        //$stockcard_access
        "(11,0,'StockCard','',0,'\\102','\\1',0,'0',0)",
        "(12,0,'Allow View Stockcard','SK',0,'\\10201','\\102',0,'0',0)",
        "(13,0,'Allow Click Edit Button SK','',0,'\\10202','\\102',0,'0',0)",
        "(14,0,'Allow Click New Button SK','',0,'\\10203','\\102',0,'0',0)",
        "(15,0,'Allow Click Save Button SK','',0,'\\10204','\\102',0,'0',0)",
        "(16,0,'Allow Click Change Barcode SK','',0,'\\10205','\\102',0,'0',0)",
        "(17,0,'Allow Click Delete Button SK','',0,'\\10206','\\102',0,'0',0)",
        "(18,0,'Allow Print Button SK','',0,'\\10207','\\102',0,'0',0)",
        "(19,0,'Allow View SRP Button SK','',0,'\\10208','\\102',0,'0',0)",
        // $customer_access
        "(21,0,'Customer Ledger','',0,'\\103','\\1',0,'0',0)",
        "(22,0,'Allow View Customer Ledger','CUSTOMER',0,'\\10301','\\103',0,'0',0)",
        "(23,0,'Allow Click Edit Button CL','',0,'\\10302','\\103',0,'0',0)",
        "(24,0,'Allow Click New Button CL','',0,'\\10303','\\103',0,'0',0)",
        "(25,0,'Allow Click Save Button CL','',0,'\\10304','\\103',0,'0',0)",
        "(26,0,'Allow Click Change Customer  Code CL','',0,'\\10305','\\103',0,'0',0)",
        "(27,0,'Allow Click Delete Button CL','',0,'\\10306','\\103',0,'0',0)",
        "(28,0,'Allow Click Print Button CL','',0,'\\10307','\\103',0,'0',0)",
        // $supplier_access
        "(31,0,'Supplier Ledger','',0,'\\104','\\1',0,'0',0)",
        "(32,0,'Allow View Supplier Ledger','SU',0,'\\10401','\\104',0,'0',0)",
        "(33,0,'Allow Click Edit Button SL','',0,'\\10402','\\104',0,'0',0)",
        "(34,0,'Allow Click New Button SL','',0,'\\10403','\\104',0,'0',0)",
        "(35,0,'Allow Click Save Button SL','',0,'\\10404','\\104',0,'0',0)",
        "(36,0,'Allow Click Change Supplier Code SL','',0,'\\10405','\\104',0,'0',0)",
        "(37,0,'Allow Click Delete  Button SL','',0,'\\10406','\\104',0,'0',0)",
        "(38,0,'Allow Click Print Button SL','',0,'\\10407','\\104',0,'0',0)",
        // $agent_access
        "(41,0,'Agent Ledger','',0,'\\105','\\1',0,'0',0)",
        "(42,0,'Allow View Agent Ledger','AG',0,'\\10501','\\105',0,'0',0)",
        "(43,0,'Allow Click Edit Button AL','',0,'\\10502','\\105',0,'0',0)",
        "(44,0,'Allow Click New Button AL','',0,'\\10503','\\105',0,'0',0)",
        "(45,0,'Allow Click Save Button AL','',0,'\\10504','\\105',0,'0',0)",
        "(46,0,'Allow Click Change Agent Code  AL','',0,'\\10505','\\105',0,'0',0)",
        "(47,0,'Allow Click Delete Button AL','',0,'\\10506','\\105',0,'0',0)",
        "(48,0,'Allow Click Print Button AL','',0,'\\10507','\\105',0,'0',0)",
        //$warehouse_access
        "(51,0,'Warehouse Ledger','',0,'\\106','\\1',0,'0',0)",
        "(52,0,'Allow View Warehouse','WH',0,'\\10601','\\106',0,'0',0)",
        "(53,0,'Allow Click Edit Button WL','',0,'\\10602','\\106',0,'0',0)",
        "(54,0,'Allow Click New Button WL','',0,'\\10603','\\106',0,'0',0)",
        "(55,0,'Allow Click Save Button WL','',0,'\\10604','\\106',0,'0',0)",
        "(56,0,'Allow Click Change Warehouse Code  WL','',0,'\\10605','\\106',0,'0',0)",
        "(57,0,'Allow Click Delete Button WL','',0,'\\10606','\\106',0,'0',0)",
        "(58,0,'Allow Click Print Button WL','',0,'\\10607','\\106',0,'0',0)",
        //$employee_access
        "(868,0,'Employee Ledger','',0,'\\107','\\1',0,'0',0)",
        "(869,0,'Allow View Employee Ledger','',0,'\\10701','\\107',0,'0',0)",
        "(870,0,'Allow Click Edit Button EMP','',0,'\\10702','\\107',0,'0',0)",
        "(871,0,'Allow Click New Button EMP','',0,'\\10703','\\107',0,'0',0)",
        "(872,0,'Allow Click Save Button EMP','',0,'\\10704','\\107',0,'0',0)",
        "(873,0,'Allow Click Change Code EMP','',0,'\\10705','\\107',0,'0',0)",
        "(874,0,'Allow Click Delete Button EMP','',0,'\\10706','\\107',0,'0',0)",
        "(875,0,'Allow Click Print Button EMP','',0,'\\10707','\\107',0,'0',0)",
        //$department_access
        "(860,0,'Department Ledger','',0,'\\108','\\1',0,'0',0)",
        "(861,0,'Allow View Department Ledger','',0,'\\10801','\\108',0,'0',0)",
        "(862,0,'Allow Click Edit Button DEPT','',0,'\\10802','\\108',0,'0',0)",
        "(863,0,'Allow Click New Button DEPT','',0,'\\10803','\\108',0,'0',0)",
        "(864,0,'Allow Click Save Button DEPT','',0,'\\10804','\\108',0,'0',0)",
        "(865,0,'Allow Click Change Code DEPT','',0,'\\10805','\\108',0,'0',0)",
        "(866,0,'Allow Click Delete Button DEPT','',0,'\\10806','\\108',0,'0',0)",
        "(867,0,'Allow Click Print Button DEPT','',0,'\\10807','\\108',0,'0',0)",



      ]
    ];


    //2
    $menu['payable'] = [
      'parent' => ["(547,0,'PAYABLE','',0,'\\2','\\',0,'0',0)"],
      'children' => [
        //$ap_access 
        "(133,0,'Payable Setup','',0,'\\201','\\2',0,'0',0)",
        "(134,0,'Allow View Transaction AP','AP',0,'\\20101','\\201',0,'0',0)",
        "(135,0,'Allow Click Edit Button  AP','',0,'\\20102','\\201',0,'0',0)",
        "(136,0,'Allow Click New Button AP','',0,'\\20103','\\201',0,'0',0)",
        "(137,0,'Allow Click Save Button AP','',0,'\\20104','\\201',0,'0',0)",
        "(138,0,'Allow Click Change Document# AP','',0,'\\20105','\\201',0,'0',0)",
        "(139,0,'Allow Click Delete Button AP','',0,'\\20106','\\201',0,'0',0)",
        "(140,0,'Allow Click Print Button AP','',0,'\\20107','\\201',0,'0',0)",
        "(141,0,'Allow Click Lock Button AP','',0,'\\20108','\\201',0,'0',0)",
        "(142,0,'Allow Click UnLock Button AP','',0,'\\20109','\\201',0,'0',0)",
        "(143,0,'Allow Click Post Button AP','',0,'\\20110','\\201',0,'0',0)",
        "(144,0,'Allow Click UnPost Button AP','',0,'\\20111','\\201',0,'0',0)",
        "(145,0,'Allow Click Add Account AP','',0,'\\20112','\\201',0,'0',0)",
        "(146,0,'Allow Click Edit Account AP','',0,'\\20113','\\201',0,'0',0)",
        "(147,0,'Allow Click Delete Account AP','',0,'\\20114','\\201',0,'0',0)",
        //$pv_access
        "(370,0,'Accounts Payable Voucher','',0,'\\202','\\2',0,'0',0)",
        "(371,0,'Allow View Transaction PV','APV',0,'\\20201','\\202',0,'0',0)",
        "(372,0,'Allow Click Edit Button  PV','',0,'\\20202','\\202',0,'0',0)",
        "(373,0,'Allow Click New Button PV','',0,'\\20203','\\202',0,'0',0)",
        "(374,0,'Allow Click Save Button PV','',0,'\\20204','\\202',0,'0',0)",
        "(375,0,'Allow Click Change Document# PV','',0,'\\20205','\\202',0,'0',0)",
        "(376,0,'Allow Click Delete Button PV','',0,'\\20206','\\202',0,'0',0)",
        "(377,0,'Allow Click Print Button PV','',0,'\\20207','\\202',0,'0',0)",
        "(378,0,'Allow Click Lock Button PV','',0,'\\20208','\\202',0,'0',0)",
        "(379,0,'Allow Click UnLock Button PV','',0,'\\20209','\\202',0,'0',0)",
        "(380,0,'Allow Click Post Button PV','',0,'\\20210','\\202',0,'0',0)",
        "(381,0,'Allow Click UnPost Button PV','',0,'\\20211','\\202',0,'0',0)",
        "(382,0,'Allow Click Add Account PV','',0,'\\20212','\\202',0,'0',0)",
        "(383,0,'Allow Click Edit Account PV','',0,'\\20213','\\202',0,'0',0)",
        "(384,0,'Allow Click Delete Account PV','',0,'\\20214','\\202',0,'0',0)",
        //$cv_access
        "(116,0,'Cash/Check Voucher','',0,'\\203','\\2',0,'0',0)",
        "(117,0,'Allow View Transaction CV','CV',0,'\\20301','\\203',0,'0',0)",
        "(118,0,'Allow Click Edit Button  CV','',0,'\\20302','\\203',0,'0',0)",
        "(119,0,'Allow Click New Button CV','',0,'\\20303','\\203',0,'0',0)",
        "(120,0,'Allow Click Save Button CV','',0,'\\20304','\\203',0,'0',0)",
        "(121,0,'Allow Click Change Document# CV','',0,'\\20305','\\203',0,'0',0)",
        "(122,0,'Allow Click Delete Button CV','',0,'\\20306','\\203',0,'0',0)",
        "(123,0,'Allow Click Print Button CV','',0,'\\20307','\\203',0,'0',0)",
        "(124,0,'Allow Click Lock Button CV','',0,'\\20308','\\203',0,'0',0)",
        "(125,0,'Allow Click UnLock Button CV','',0,'\\20309','\\203',0,'0',0)",
        "(126,0,'Allow Click Post Button CV','',0,'\\20310','\\203',0,'0',0)",
        "(127,0,'Allow Click UnPost Button CV','',0,'\\20311','\\203',0,'0',0)",
        "(128,0,'Allow Click Add Account CV','',0,'\\20312','\\203',0,'0',0)",
        "(129,0,'Allow Click Edit Account CV','',0,'\\20313','\\203',0,'0',0)",
        "(130,0,'Allow Click Delete Account CV','',0,'\\20314','\\203',0,'0',0)"
      ]
    ];


    //3
    $menu['receivable'] = [
      'parent' => ["(546,0,'RECEIVABLES','',0,'\\3','\\',0,'0',0)"],
      'children' => [
        //$ar_access
        "(239,0,'Receivable Setup','',0,'\\301','\\3',0,'0',0)",
        "(240,0,'Allow View Transaction RS','AR',0,'\\30101','\\301',0,'0',0)",
        "(241,0,'Allow Click Edit Button  RS','',0,'\\30102','\\301',0,'0',0)",
        "(242,0,'Allow Click New Button RS','',0,'\\30103','\\301',0,'0',0)",
        "(243,0,'Allow Click Save Button RS','',0,'\\30104','\\301',0,'0',0)",
        "(244,0,'Allow Click Change Document# RS','',0,'\\30105','\\301',0,'0',0)",
        "(245,0,'Allow Click Delete Button RS','',0,'\\30106','\\301',0,'0',0)",
        "(246,0,'Allow Click Print Button RS','',0,'\\30107','\\301',0,'0',0)",
        "(247,0,'Allow Click Lock Button RS','',0,'\\30108','\\301',0,'0',0)",
        "(248,0,'Allow Click UnLock Button RS','',0,'\\30109','\\301',0,'0',0)",
        "(249,0,'Allow Click Post Button RS','',0,'\\30110','\\301',0,'0',0)",
        "(250,0,'Allow Click UnPost Button RS','',0,'\\30111','\\301',0,'0',0)",
        "(251,0,'Allow Click Add Account RS','',0,'\\30112','\\301',0,'0',0)",
        "(252,0,'Allow Click Edit Account RS','',0,'\\30113','\\301',0,'0',0)",
        "(253,0,'Allow Click Delete Account RS','',0,'\\30114','\\301',0,'0',0)",

        //$kr_access
        "(208,0,'Counter Receipt','',0,'\\302','\\3',0,'0',0)",
        "(209,0,'Allow View Transaction KR ','KR',0,'\\30201','\\302',0,'0',0)",
        "(210,0,'Allow Click Edit Button  KR ','',0,'\\30202','\\302',0,'0',0)",
        "(211,0,'Allow Click New Button KR ','',0,'\\30203','\\302',0,'0',0)",
        "(212,0,'Allow Click Save Button KR ','',0,'\\30204','\\302',0,'0',0)",
        "(213,0,'Allow Click Change Document# KR ','',0,'\\30205','\\302',0,'0',0)",
        "(214,0,'Allow Click Delete Button KR ','',0,'\\30206','\\302',0,'0',0)",
        "(215,0,'Allow Click Print Button KR ','',0,'\\30207','\\302',0,'0',0)",
        "(216,0,'Allow Click Lock Button KR ','',0,'\\30208','\\302',0,'0',0)",
        "(217,0,'Allow Click UnLock Button KR ','',0,'\\30209','\\302',0,'0',0)",
        "(218,0,'Allow Click Post Button KR ','',0,'\\30210','\\302',0,'0',0)",
        "(219,0,'Allow Click UnPost Button KR ','',0,'\\30211','\\302',0,'0',0)",
        "(220,0,'Allow Click Add Account KR','',0,'\\30212','\\302',0,'0',0)",
        "(221,0,'Allow Click Edit Account KR','',0,'\\30213','\\302',0,'0',0)",
        "(222,0,'Allow Click Delete Account KR','',0,'\\30214','\\302',0,'0',0)",

        //$cr_access
        "(223,0,'Received Payment','',0,'\\303','\\3',0,'0',0)",
        "(224,0,'Allow View Transaction CR ','CR',0,'\\30301','\\303',0,'0',0)",
        "(225,0,'Allow Click Edit Button  CR ','',0,'\\30302','\\303',0,'0',0)",
        "(226,0,'Allow Click New Button CR ','',0,'\\30303','\\303',0,'0',0)",
        "(227,0,'Allow Click Save Button CR ','',0,'\\30304','\\303',0,'0',0)",
        "(228,0,'Allow Click Change Document# CR ','',0,'\\30305','\\303',0,'0',0)",
        "(229,0,'Allow Click Delete Button CR ','',0,'\\30306','\\303',0,'0',0)",
        "(230,0,'Allow Click Print Button CR ','',0,'\\30307','\\303',0,'0',0)",
        "(231,0,'Allow Click Lock Button CR ','',0,'\\30308','\\303',0,'0',0)",
        "(232,0,'Allow Click UnLock Button CR ','',0,'\\30309','\\303',0,'0',0)",
        "(233,0,'Allow Click Post Button CR ','',0,'\\30310','\\303',0,'0',0)",
        "(234,0,'Allow Click UnPost Button CR ','',0,'\\30311','\\303',0,'0',0)",
        "(235,0,'Allow Click Add Account CR','',0,'\\30312','\\303',0,'0',0)",
        "(236,0,'Allow Click Edit Account CR','',0,'\\30313','\\303',0,'0',0)",
        "(237,0,'Allow Click Delete Account CR','',0,'\\30314','\\303',0,'0',0)"
      ]
    ];

    //4
    $parent = "(61,0,'PURCHASES','',0,'\\4','\\',0,'0',0)";
    $children = "(62,0,'Purchase Order','',0,'\\401','\\4',0,'0',0),
               (63,0,'Allow View Transaction PO','PO',0,'\\40101','\\401',0,'0',0),
               (64,0,'Allow Click Edit Button PO','',0,'\\40102','\\401',0,'0',0),
               (65,0,'Allow Click New Button PO','',0,'\\40103','\\401',0,'0',0),
               (66,0,'Allow Click Save Button PO','',0,'\\40104','\\401',0,'0',0),
               (67,0,'Allow Click Change Document#  PO','',0,'\\40105','\\401',0,'0',0),
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

               (78,0,'Receiving Report','',0,'\\402','\\4',0,'0',0),
               (79,0,'Allow View Transaction RR','RR',0,'\\40201','\\402',0,'0',0),
               (80,0,'Allow Click Edit Button RR','',0,'\\40202','\\402',0,'0',0),
               (81,0,'Allow Click New Button RR','',0,'\\40203','\\402',0,'0',0),
               (82,0,'Allow Click Save Button RR','',0,'\\40204','\\402',0,'0',0),
               (83,0,'Allow Click Change Document# RR','',0,'\\40205','\\402',0,'0',0),
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
               (813,1,'Allow Click Delete Item RR','',0,'\\40216','\\402',0,'0',0),

               (97,0,'Purchase Return','',0,'\\403','\\4',0,'0',0),
               (98,0,'Allow View Transaction DM','DM',0,'\\40301','\\403',0,'0',0),
               (99,0,'Allow Click Edit Button DM','',0,'\\40302','\\403',0,'0',0),
               (100,0,'Allow Click New Button DM','',0,'\\40303','\\403',0,'0',0),
               (101,0,'Allow Click Save Button DM','',0,'\\40304','\\403',0,'0',0),
               (102,0,'Allow Click Change Document# DM','',0,'\\40305','\\403',0,'0',0),
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
               (822,1,'Allow Click Delete Item DM','',0,'\\40316','\\403',0,'0',0),

               (618,0,'Purchase Requisition','',0,'\\404','\\4',0,'0',0),
               (619,0,'Allow View Transaction PR','PR',0,'\\40401','\\404',0,'0',0),
               (620,0,'Allow Click Edit Button PR','',0,'\\40402','\\404',0,'0',0),
               (621,0,'Allow Click New Button PR','',0,'\\40403','\\404',0,'0',0),
               (622,0,'Allow Click Save Button PR','',0,'\\40404','\\404',0,'0',0),
               (623,0,'Allow Click Change Document# PR','',0,'\\40405','\\404',0,'0',0),
               (624,0,'Allow Click Delete Button PR','',0,'\\40406','\\404',0,'0',0),
               (625,0,'Allow Click Print Button PR','',0,'\\40407','\\404',0,'0',0),
               (626,0,'Allow Click Lock Button PR','',0,'\\40408','\\404',0,'0',0),
               (627,0,'Allow Click UnLock Button PR','',0,'\\40409','\\404',0,'0',0),
               (630,0,'Allow Click Post Button PR','',0,'\\40410','\\404',0,'0',0),
               (631,0,'Allow Click UnPost Button PR','',0,'\\40411','\\404',0,'0',0),
               (628,0,'Allow Change Amount PR','',0,'\\40413','\\404',0,'0',0),
               (814,1,'Allow Click Add Item PR','',0,'\\40414','\\404',0,'0',0),
               (815,1,'Allow Click Edit Item PR','',0,'\\40415','\\404',0,'0',0),
               (816,1,'Allow Click Delete Item PR','',0,'\\40416','\\404',0,'0',0)";

    switch ($params['companyid']) {
      case 1: //vitaline
      case 23: //labsol cebu
        break;

      default:
        $children = $children . ",(1427,0,'Canvass Sheet','',0,'\\405','\\4',0,'0',0),
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
            (1447,0,'Canvass Approval','',0,'\\406','\\4',0,'0',0)";
        break;
    }


    $menu['purchase'] = [
      'parent' => [$parent],
      'children' => [$children]
    ];


    //5
    $menu['sales'] = [
      'parent' => ["(150,0,'SALES','',0,'\\5','\\',0,'0',0)"],
      'children' => [
        //$so_access
        "(151,0,'Sales Order','',0,'\\501','\\5',0,'0',0)",
        "(152,0,'Allow View Transaction SO','SO',0,'\\50101','\\501',0,'0',0)",
        "(153,0,'Allow Click Edit Button SO','',0,'\\50102','\\501',0,'0',0)",
        "(154,0,'Allow Click New  Button SO','',0,'\\50103','\\501',0,'0',0)",
        "(155,0,'Allow Click Save Button SO','',0,'\\50104','\\501',0,'0',0)",
        "(156,0,'Allow Click Change Document#  SO','',0,'\\50105','\\501',0,'0',0)",
        "(157,0,'Allow Click Delete Button SO','',0,'\\50106','\\501',0,'0',0)",
        "(158,0,'Allow Click Print Button SO','',0,'\\50107','\\501',0,'0',0)",
        "(159,0,'Allow Click Lock Button SO','',0,'\\50108','\\501',0,'0',0)",
        "(160,0,'Allow Click UnLock Button SO','',0,'\\50109','\\501',0,'0',0)",
        "(161,0,'Allow Change Amount  SO','',0,'\\50110','\\501',0,'0',0)",
        "(162,0,'Allow Check Credit Limit SO','',0,'\\50111','\\501',0,'0',0)",
        "(163,0,'Allow Click Post Button SO','',0,'\\50112','\\501',0,'0',0)",
        "(164,0,'Allow Click UnPost  Button SO','',0,'\\50113','\\501',0,'0',0)",
        "(805,1,'Allow Click Add Item SO','',0,'\\50114','\\501',0,'0',0)",
        "(806,1,'Allow Click Edit Item SO','',0,'\\50115','\\501',0,'0',0)",
        "(807,1,'Allow Click Delete Item SO','',0,'\\50116','\\501',0,'0',0)",
        //$sj_access
        "(168,0,'Sales Journal','',0,'\\502','\\5',0,'0',0)",
        "(169,0,'Allow View Transaction SJ','SJ',0,'\\50201','\\502',0,'0',0)",
        "(170,0,'Allow Click Edit Button SJ','',0,'\\50202','\\502',0,'0',0)",
        "(171,0,'Allow Click New  Button SJ','',0,'\\50203','\\502',0,'0',0)",
        "(172,0,'Allow Click Save Button SJ','',0,'\\50204','\\502',0,'0',0)",
        "(173,0,'Allow Click Change Document#  SJ','',0,'\\50205','\\502',0,'0',0)",
        "(174,0,'Allow Click Delete Button SJ','',0,'\\50206','\\502',0,'0',0)",
        "(175,0,'Allow Click Print Button SJ','',0,'\\50207','\\502',0,'0',0)",
        "(176,0,'Allow Click Lock Button SJ','',0,'\\50208','\\502',0,'0',0)",
        "(177,0,'Allow Click UnLock Button SJ','',0,'\\50209','\\502',0,'0',0)",
        "(178,0,'Allow Click Post Button SJ','',0,'\\50210','\\502',0,'0',0)",
        "(179,0,'Allow Click UnPost  Button SJ','',0,'\\50211','\\502',0,'0',0)",
        "(180,0,'Allow Change Amount  SJ','',0,'\\50213','\\502',0,'0',0)",
        "(181,0,'Allow Check Credit Limit SJ','',0,'\\50214','\\502',0,'0',0)",
        "(182,0,'Allow SJ Amount Auto-Compute on UOM Change','',0,'\\50215','\\502',0,'0',0)",
        "(183,0,'Allow View Transaction Accounting SJ','',0,'\\50216','\\502',0,'0',0)",
        "(802,1,'Allow Click Add Item SJ','',0,'\\50217','\\502',0,'0',0)",
        "(803,1,'Allow Click Edit Item SJ','',0,'\\50218','\\502',0,'0',0)",
        "(804,1,'Allow Click Delete Item SJ','',0,'\\50219','\\502',0,'0',0)",
        "(2509,1,'Allow View Fields for Gate 2 Users SJ','',0,'\\50224','\\502',0,'0',0)",
        //$cm_access
        "(189,0,'Sales Return','',0,'\\503','\\5',0,'0',0)",
        "(190,0,'Allow View Transaction SR','CM',0,'\\50301','\\503',0,'0',0)",
        "(191,0,'Allow Click Edit Button SR','',0,'\\50302','\\503',0,'0',0)",
        "(192,0,'Allow Click New  Button SR','',0,'\\50303','\\503',0,'0',0)",
        "(193,0,'Allow Click Save  Button SR','',0,'\\50304','\\503',0,'0',0)",
        "(194,0,'Allow Click Change Document#  SR','',0,'\\50305','\\503',0,'0',0)",
        "(195,0,'Allow Click Delete Button SR','',0,'\\50306','\\503',0,'0',0)",
        "(196,0,'Allow Click Print  Button SR','',0,'\\50307','\\503',0,'0',0)",
        "(197,0,'Allow Click Lock Button SR','',0,'\\50308','\\503',0,'0',0)",
        "(198,0,'Allow Click UnLock Button SR','',0,'\\50309','\\503',0,'0',0)",
        "(199,0,'Allow Click Post Button SR','',0,'\\50310','\\503',0,'0',0)",
        "(200,0,'Allow Click UnPost  Button SR','',0,'\\50311','\\503',0,'0',0)",
        "(201,0,'Allow View Transaction Accounting SR','',0,'\\50312','\\503',0,'0',0)",
        "(202,0,'Allow Change Amount SR','',0,'\\50313','\\503',0,'0',0)",
        "(817,1,'Allow Click Add Item SR','',0,'\\50314','\\503',0,'0',0)",
        "(818,1,'Allow Click Edit Item SR','',0,'\\50315','\\503',0,'0',0)",
        "(819,1,'Allow Click Delete Item SR','',0,'\\50316','\\503',0,'0',0)",
        //$mi_access 
        "(768,0,'Material Issuance','',0,'\\504','\\5',0,'0',0)",
        "(769,0,'Allow View Transaction MI','MI',0,'\\50401','\\504',0,'0',0)",
        "(770,0,'Allow Click Edit Button MI','MI',0,'\\50402','\\504',0,'0',0)",
        "(771,0,'Allow Click New  Button MI','MI',0,'\\50403','\\504',0,'0',0)",
        "(772,0,'Allow Click Save Button MI','MI',0,'\\50404','\\504',0,'0',0)",
        "(773,0,'Allow Click Change Document#  MI','MI',0,'\\50405','\\504',0,'0',0)",
        "(774,0,'Allow Click Delete Button MI','MI',0,'\\50406','\\504',0,'0',0)",
        "(775,0,'Allow Click Print Button MI','MI',0,'\\50407','\\504',0,'0',0)",
        "(776,0,'Allow Click Lock Button MI','MI',0,'\\50408','\\504',0,'0',0)",
        "(777,0,'Allow Click UnLock Button MI','MI',0,'\\50409','\\504',0,'0',0)",
        "(778,0,'Allow Click Post Button MI','MI',0,'\\50410','\\504',0,'0',0)",
        "(779,0,'Allow Click UnPost  Button MI','MI',0,'\\50411','\\504',0,'0',0)",
        "(780,0,'Allow Change Amount  MI','MI',0,'\\50413','\\504',0,'0',0)",
        "(781,0,'Allow Check Credit Limit MI','MI',0,'\\50414','\\504',0,'0',0)",
        "(782,0,'Allow SI Amount Auto-Compute on UOM Change','MI',0,'\\50415','\\504',0,'0',0)",
        "(783,0,'Allow View Transaction Accounting MI','MI',0,'\\50416','\\504',0,'0',0)",
        "(3292,1,'Allow Click Add Item MI','',0,'\\50417','\\504',0,'0',0)",
        "(3293,1,'Allow Click Edit Item MI','',0,'\\50418','\\504',0,'0',0)",
        "(3294,1,'Allow Click Delete Item MI','',0,'\\50419','\\504',0,'0',0)"
      ]
    ];


    //6
    $parent = "(556,0,'INVENTORY','',0,'\\6','\\',0,'0',0)";
    $children = "(257,0,'Inventory Setup','',0,'\\601','\\6',0,'0',0),
              (258,0,'Allow View Transaction IS','IS',0,'\\60101','\\601',0,'0',0),
              (259,0,'Allow Click Edit Button  IS','',0,'\\60102','\\601',0,'0',0),
              (260,0,'Allow Click New Button IS','',0,'\\60103','\\601',0,'0',0),
              (261,0,'Allow Click Save Button IS','',0,'\\60104','\\601',0,'0',0),
              (262,0,'Allow Click Change Document# IS','',0,'\\60105','\\601',0,'0',0),
              (263,0,'Allow Click Delete Button IS','',0,'\\60106','\\601',0,'0',0),
              (264,0,'Allow Click Print Button IS','',0,'\\60107','\\601',0,'0',0),
              (265,0,'Allow Click Lock Button IS','',0,'\\60108','\\601',0,'0',0),
              (266,0,'Allow Click UnLock Button IS','',0,'\\60109','\\601',0,'0',0),
              (267,0,'Allow Click Post Button IS','',0,'\\60110','\\601',0,'0',0),
              (268,0,'Allow Click UnPost Button IS','',0,'\\60111','\\601',0,'0',0),
              (269,0,'Allow View Transaction Accounting IS','',0,'\\60112','\\601',0,'0',0),
              (827,1,'Allow Click Add Item IS','',0,'\\60113','\\601',0,'0',0),
              (828,1,'Allow Click Edit Item IS','',0,'\\60114','\\601',0,'0',0),
              (829,1,'Allow Click Delete Item IS','',0,'\\60115','\\601',0,'0',0),
              (830,1,'Allow Change Amount IS','',0,'\\60116','\\601',0,'0',0),

              (275,0,'Physical Count','',0,'\\602','\\6',0,'0',0),
              (276,0,'Allow View Transaction PC','',0,'\\60201','\\602',0,'0',0),
              (277,0,'Allow Click Edit Button  PC','',0,'\\60202','\\602',0,'0',0),
              (278,0,'Allow Click New Button PC','',0,'\\60203','\\602',0,'0',0),
              (279,0,'Allow Click Save Button PC','',0,'\\60204','\\602',0,'0',0),
              (280,0,'Allow Adjust PC','',0,'\\60205','\\602',0,'0',0),
              (281,0,'Allow Click Delete Button PC','',0,'\\60206','\\602',0,'0',0),
              (282,0,'Allow Click Print Button PC','',0,'\\60207','\\602',0,'0',0),
              (283,0,'Allow Click Lock Button PC','',0,'\\60208','\\602',0,'0',0),
              (284,0,'Allow Click UnLock Button PC','',0,'\\60209','\\602',0,'0',0),
              (285,0,'Allow Click Post Button PC','',0,'\\60210','\\602',0,'0',0),
              (286,0,'Allow Click UnPost Button PC','',0,'\\60211','\\602',0,'0',0),
              (837,1,'Allow Click Delete Item PC','',0,'\\60214','\\602',0,'0',0),
              (836,1,'Allow Click Edit Item PC','',0,'\\60213','\\602',0,'0',0),
              (835,1,'Allow Click Add Item PC','',0,'\\60212','\\602',0,'0',0),
              (838,1,'Allow Change Amount PC','',0,'\\60215','\\602',0,'0',0),

              (290,0,'Inventory Adjustment','',0,'\\603','\\6',0,'0',0),
              (291,0,'Allow View Transaction AJ','AJ',0,'\\60301','\\603',0,'0',0),
              (292,0,'Allow Click Edit Button  AJ','',0,'\\60302','\\603',0,'0',0),
              (293,0,'Allow Click New Button AJ','',0,'\\60303','\\603',0,'0',0),
              (294,0,'Allow Click Save Button AJ','',0,'\\60304','\\603',0,'0',0),
              (295,0,'Allow Click Change Document# AJ','',0,'\\60305','\\603',0,'0',0),
              (296,0,'Allow Click Delete Button AJ','',0,'\\60306','\\603',0,'0',0),
              (297,0,'Allow Click Print Button AJ','',0,'\\60307','\\603',0,'0',0),
              (298,0,'Allow Click Lock Button AJ','',0,'\\60308','\\603',0,'0',0),
              (299,0,'Allow Click UnLock Button AJ','',0,'\\60309','\\603',0,'0',0),
              (300,0,'Allow Click Post Button AJ','',0,'\\60310','\\603',0,'0',0),
              (301,0,'Allow Click UnPost Button AJ','',0,'\\60311','\\603',0,'0',0),
              (302,0,'Allow View Transaction Accounting AJ','',0,'\\60312','\\603',0,'0',0),
              (823,1,'Allow Click Add Item AJ','',0,'\\60313','\\603',0,'0',0),
              (824,1,'Allow Click Edit Item AJ','',0,'\\60314','\\603',0,'0',0),
              (825,1,'Allow Click Delete Item AJ','',0,'\\60315','\\603',0,'0',0),
              (826,1,'Allow Change Amount AJ','',0,'\\60316','\\603',0,'0',0),

              (308,0,'Transfer Slip','',0,'\\604','\\6',0,'0',0),
              (309,0,'Allow View Transaction TS','TS',0,'\\60401','\\604',0,'0',0),
              (310,0,'Allow Click Edit Button  TS','',0,'\\60402','\\604',0,'0',0),
              (311,0,'Allow Click New Button TS','',0,'\\60403','\\604',0,'0',0),
              (312,0,'Allow Click Save Button TS','',0,'\\60404','\\604',0,'0',0),
              (313,0,'Allow Click Change Document# TS','',0,'\\60405','\\604',0,'0',0),
              (314,0,'Allow Click Delete Button TS','',0,'\\60406','\\604',0,'0',0),
              (315,0,'Allow Click Print Button TS','',0,'\\60407','\\604',0,'0',0),
              (316,0,'Allow Click Lock Button TS','',0,'\\60408','\\604',0,'0',0),
              (317,0,'Allow Click UnLock Button TS','',0,'\\60409','\\604',0,'0',0),
              (318,0,'Allow Click Post Button TS','',0,'\\60410','\\604',0,'0',0),
              (319,0,'Allow Click UnPost Button TS','',0,'\\60411','\\604',0,'0',0),
              (831,1,'Allow Click Add Item TS','',0,'\\60412','\\604',0,'0',0),
              (832,1,'Allow Click Edit Item TS','',0,'\\60413','\\604',0,'0',0),
              (833,1,'Allow Click Delete Item TS','',0,'\\60414','\\604',0,'0',0),
              (834,1,'Allow Change Amount TS','',0,'\\60415','\\604',0,'0',0)";

    switch ($params['companyid']) {
      case 17: //unihome
        $children = $children . ",(4905,0,'Actual Count','',0,'\\605','\\6',0,'0',0),
              (4906,0,'Allow View Transaction AT','',0,'\\60501','\\605',0,'0',0),
              (4907,0,'Allow Click Edit Button AT','',0,'\\60502','\\605',0,'0',0),
              (4908,0,'Allow Click New Button AT','',0,'\\60503','\\605',0,'0',0),
              (4909,0,'Allow Click Save Button AT','',0,'\\60504','\\605',0,'0',0),
              (4910,0,'Allow Adjust AT','',0,'\\60505','\\605',0,'0',0),
              (4911,0,'Allow Click Delete Button AT','',0,'\\60506','\\605',0,'0',0),
              (4912,0,'Allow Click Print Button AT','',0,'\\60507','\\605',0,'0',0),
              (4913,0,'Allow Click Lock Button AT','',0,'\\60508','\\605',0,'0',0),
              (4914,0,'Allow Click UnLock Button AT','',0,'\\60509','\\605',0,'0',0),
              (4915,0,'Allow Click Post Button AT','',0,'\\60510','\\605',0,'0',0),
              (4916,0,'Allow Click UnPost Button AT','',0,'\\60511','\\605',0,'0',0),
              (4917,1,'Allow Click Delete Item AT','',0,'\\60512','\\605',0,'0',0),
              (4918,1,'Allow Click Edit Item AT','',0,'\\60513','\\605',0,'0',0),
              (4919,1,'Allow Click Add Item AT','',0,'\\60514','\\605',0,'0',0),
              (4920,1,'Allow Change Amount AT','',0,'\\60515','\\605',0,'0',0)";
        break;
    }


    $menu['inventory'] = [
      'parent' => [$parent],
      'children' => [$children]
    ];


    //7
    $menu['accounting'] = [
      'parent' => ["(548,0,'ACCOUNTING','',0,'\\7','\\',0,'0',0)"],
      'children' => [
        //$coa_access
        "(2,0,'Chart of Accounts','',0,'\\701','\\7',0,'0',0)",
        "(3,0,'Allow View Chart of Accounts','COA',0,'\\70101','\\701',0,'0',0)",
        "(4,0,'Allow Click Edit Button  COA','',0,'\\70102','\\701',0,'0',0)",
        "(5,0,'Allow Click New Button COA','',0,'\\70103','\\701',0,'0',0)",
        "(6,0,'Allow Click Save Button COA','',0,'\\70104','\\701',0,'0',0)",
        "(7,0,'Allow Click Delete Button COA','',0,'\\70105','\\701',0,'0',0)",
        "(8,0,'Allow Click Print Button COA','',0,'\\70106','\\701',0,'0',0)",
        //$ds_access
        "(326,0,'Deposit Slip','',0,'\\304','\\7',0,'0',0)",
        "(327,0,'Allow View Transaction DS','DS',0,'\\30401','\\304',0,'0',0)",
        "(328,0,'Allow Click Edit Button  DS','',0,'\\30402','\\304',0,'0',0)",
        "(329,0,'Allow Click New Button DS','',0,'\\30403','\\304',0,'0',0)",
        "(330,0,'Allow Click Save Button DS','',0,'\\30404','\\304',0,'0',0)",
        "(331,0,'Allow Click Change Document# DS','',0,'\\30405','\\304',0,'0',0)",
        "(332,0,'Allow Click Delete Button DS','',0,'\\30406','\\304',0,'0',0)",
        "(333,0,'Allow Click Print Button DS','',0,'\\30407','\\304',0,'0',0)",
        "(334,0,'Allow Click Lock Button DS','',0,'\\30408','\\304',0,'0',0)",
        "(335,0,'Allow Click UnLock Button DS','',0,'\\30409','\\304',0,'0',0)",
        "(336,0,'Allow Click Post Button DS','',0,'\\30410','\\304',0,'0',0)",
        "(337,0,'Allow Click UnPost Button DS','',0,'\\30411','\\304',0,'0',0)",
        "(338,0,'Allow Click Add Account DS','',0,'\\30412','\\304',0,'0',0)",
        "(339,0,'Allow Click Edit Account DS','',0,'\\30413','\\304',0,'0',0)",
        "(340,0,'Allow Click Delete Account DS','',0,'\\30414','\\304',0,'0',0)",

        //$gj_access
        "(343,0,'General Journal','',0,'\\702','\\7',0,'0',0)",
        "(344,0,'Allow View Transaction GJ','GJ',0,'\\70201','\\702',0,'0',0)",
        "(345,0,'Allow Click Edit Button  GJ','',0,'\\70202','\\702',0,'0',0)",
        "(346,0,'Allow Click New Button GJ','',0,'\\70203','\\702',0,'0',0)",
        "(347,0,'Allow Click Save Button GJ','',0,'\\70204','\\702',0,'0',0)",
        "(348,0,'Allow Click Change Document# GJ','',0,'\\70205','\\702',0,'0',0)",
        "(349,0,'Allow Click Delete Button GJ','',0,'\\70206','\\702',0,'0',0)",
        "(350,0,'Allow Click Print Button GJ','',0,'\\70207','\\702',0,'0',0)",
        "(351,0,'Allow Click Lock Button GJ','',0,'\\70208','\\702',0,'0',0)",
        "(352,0,'Allow Click UnLock Button GJ','',0,'\\70209','\\702',0,'0',0)",
        "(353,0,'Allow Click Post Button GJ','',0,'\\70210','\\702',0,'0',0)",
        "(354,0,'Allow Click UnPost Button GJ','',0,'\\70211','\\702',0,'0',0)",
        "(355,0,'Allow Click Add Account GJ','',0,'\\70212','\\702',0,'0',0)",
        "(356,0,'Allow Click Edit Account GJ','',0,'\\70213','\\702',0,'0',0)",
        "(357,0,'Allow Click Delete Account GJ','',0,'\\70214','\\702',0,'0',0)",

        //$gd_access
        "(1740,0,'Debit Memo','',0,'\\703','\\7',0,'0',0)",
        "(1741,0,'Allow View Transaction GD','GD',0,'\\70301','\\703',0,'0',0)",
        "(1742,0,'Allow Click Edit Button  GD','',0,'\\70302','\\703',0,'0',0)",
        "(1743,0,'Allow Click New Button GD','',0,'\\70303','\\703',0,'0',0)",
        "(1744,0,'Allow Click Save Button GD','',0,'\\70304','\\703',0,'0',0)",
        "(1745,0,'Allow Click Change Document# GD','',0,'\\70305','\\703',0,'0',0)",
        "(1746,0,'Allow Click Delete Button GD','',0,'\\70306','\\703',0,'0',0)",
        "(1747,0,'Allow Click Print Button GD','',0,'\\70307','\\703',0,'0',0)",
        "(1748,0,'Allow Click Lock Button GD','',0,'\\70308','\\703',0,'0',0)",
        "(1749,0,'Allow Click UnLock Button GD','',0,'\\70309','\\703',0,'0',0)",
        "(1750,0,'Allow Click Post Button GD','',0,'\\70310','\\703',0,'0',0)",
        "(1751,0,'Allow Click UnPost Button GD','',0,'\\70311','\\703',0,'0',0)",
        "(1752,0,'Allow Click Add Account GD','',0,'\\70312','\\703',0,'0',0)",
        "(1753,0,'Allow Click Edit Account GD','',0,'\\70313','\\703',0,'0',0)",
        "(1754,0,'Allow Click Delete Account GD','',0,'\\70314','\\703',0,'0',0)",

        //$gc_access
        "(1760,0,'Credit Memo','',0,'\\704','\\7',0,'0',0)",
        "(1761,0,'Allow View Transaction GC','GC',0,'\\70401','\\704',0,'0',0)",
        "(1762,0,'Allow Click Edit Button  GC','',0,'\\70402','\\704',0,'0',0)",
        "(1763,0,'Allow Click New Button GC','',0,'\\70403','\\704',0,'0',0)",
        "(1764,0,'Allow Click Save Button GC','',0,'\\70404','\\704',0,'0',0)",
        "(1765,0,'Allow Click Change Document# GC','',0,'\\70405','\\704',0,'0',0)",
        "(1766,0,'Allow Click Delete Button GC','',0,'\\70406','\\704',0,'0',0)",
        "(1767,0,'Allow Click Print Button GC','',0,'\\70407','\\704',0,'0',0)",
        "(1768,0,'Allow Click Lock Button GC','',0,'\\70408','\\704',0,'0',0)",
        "(1769,0,'Allow Click UnLock Button GC','',0,'\\70409','\\704',0,'0',0)",
        "(1770,0,'Allow Click Post Button GC','',0,'\\70410','\\704',0,'0',0)",
        "(1771,0,'Allow Click UnPost Button GC','',0,'\\70411','\\704',0,'0',0)",
        "(1772,0,'Allow Click Add Account GC','',0,'\\70412','\\704',0,'0',0)",
        "(1773,0,'Allow Click Edit Account GC','',0,'\\70413','\\704',0,'0',0)",
        "(1774,0,'Allow Click Delete Account GC','',0,'\\70414','\\704',0,'0',0)"

      ]
    ];


    //8
    $parent = "(360,0,'SYSTEM','',0,'\\8','\\',0,'0',0)";
    $children = "(767,1,'Themer Customizer','',0,'\\801','\\8',0,'0',0),
    (598,0,'Terms','',0,'\\802','\\8',0,'0',0),
    (599,0,'Document Prefix','',0,'\\803','\\8',0,'0',0),
    (632,0,'Change Item','',0,'\\804','\\8',0,'0',0),
    (633,0,'Allow View Audit Trail','',0,'\\805','\\8',0,'0',0),
    (652,0,'Unposted Transactions','',0,'\\806','\\8',0,'0',0),
    (1051,0,'Forex','',0,'\\807','\\8',0,'0',0),
    (385,0,'Execution Logs','',0,'\\808','\\8',0,'0',0),
    (368,0,'Allow View Transaction Cost','',0,'\\809','\\8',0,'0',0),
    (854,1,'EWT Setup','*129',0,'\\812','\\8',0,'0',0), 
    (1729,1,'Allow Override Credit Limit','',0,'\\811','\\8',0,'0',0), 
    (1730,1,'View Attach Documents','',0,'\\810','\\8',0,'0',0), 
    (1731,1,'Attach Documents','',0,'\\81001','\\810',0,'0',0), 
    (1732,1,'Download Documents','',0,'\\81002','\\810',0,'0',0), 
    (1733,1,'Delete Documents','',0,'\\81003','\\810',0,'0',0), 
    (1736,0,'Allow Override Below Cost','',0,'\\813','\\8',0,'0',0),
    (3687,0,'Allow View To Do','',0,'\\814','\\8',0,'0',0)";

    //10
    $children = $children . ",(1053,0,'DASHBOARD','',0,'\\101','\\',0,'0',0),
  (876,1,'RR Transaction Dashboard','',0,'\\10101','\\101',0,'0',0),
    (877,1,'DM Transaction Dashboard','',0,'\\10102','\\101',0,'0',0),
    (878,1,'CV Transaction Dashboard','',0,'\\10103','\\101',0,'0',0),
    (879,1,'Account Payable Dashboard','',0,'\\10104','\\101',0,'0',0),
    (880,1,'Account Receivable Dashboard','',0,'\\10105','\\101',0,'0',0),
    (1734,1,'Purchase Yearly Graph Dashboard','',0,'\\10106','\\101',0,'0',0),
    (1735,1,'Sales Yearly Graph Dashboard','',0,'\\10107','\\101',0,'0',0),
    (3807,1,'Month To Date Sales Dashboard','',0,'\\10108','\\101',0,'0',0)";


    $menu['transactionutilities']  = [
      'parent' => [$parent],
      'children' => [$children]
    ];

    //11
    $menu['itemmaster'] = [
      'parent' => ["(1054,0,'ITEM MASTERS','',0,'\\11','\\',0,'0',0)"],
      'children' => [
        //$mini_masterfiles
        "(856,1,'Brand Manager','*129',0,'\\1101','\\11',0,0,0)",
        "(852,1,'Model Master','',0,'\\1102','\\11',0,'0',0)",
        "(853,1,'Part Master','',0,'\\1103','\\11',0,'0',0)",
        "(855,1,'Item Class Master','*129',0,'\\1104','\\11',0,'0',0)",
        "(857,1,'Item Group Master','*129',0,'\\1105','\\11',0,'0',0)",
        "(858,1,'Cust/Supp Categories Master','*129',0,'\\1106','\\11',0,'0',0)",
        "(859,1,'Project Master','*129',0,'\\1107','\\11',0,'0',0)"
      ]
    ];


    //12
    $menu['schoolsetup'] = [
      'parent' => ["(1055,0,'SCHOOL SETUP','',0,'\\12','\\',0,'0',0)"],
      'children' => [
        //$ensetup_access
        "(913,0,'School Year','',0,'\\1201','\\12',0,'0',0)",
        "(914,0,'Levels','',0,'\\1202','\\12',0,'0',0)",
        "(915,0,'Semester','',0,'\\1203','\\12',0,'0',0)",
        "(916,0,'Period','',0,'\\1204','\\12',0,'0',0)",

        "(917,0,'Instructor List','',0,'\\1206','\\12',0,'0',0)",
        "(1727,0,'Allow View Instructor List','',0,'\\120602','\\1206',0,'0',0)",
        "(1721,0,'Allow Edit Instructor List','',0,'\\120601','\\1206',0,'0',0)",
        "(1722,0,'Allow New Instructor List','',0,'\\120603','\\1206',0,'0',0)",
        "(1723,0,'Allow Save Instructor List','',0,'\\120604','\\1206',0,'0',0)",
        "(1724,0,'Allow Change Instructor List','',0,'\\120607','\\1206',0,'0',0)",
        "(1725,0,'Allow Delete Instructor List','',0,'\\120605','\\1206',0,'0',0)",
        "(1726,0,'Allow Print Instructor List','',0,'\\120606','\\1206',0,'0',0)",

        "(918,0,'Course List','',0,'\\1207','\\12',0,'0',0)",
        "(1458,0,'Allow Edit Course List','',0,'\\120701','\\1207',0,'0',0)",
        "(1459,0,'Allow View Course List','',0,'\\120702','\\1207',0,'0',0)",
        "(1460,0,'Allow New Course List','',0,'\\120703','\\1207',0,'0',0)",
        "(1461,0,'Allow Save Course List','',0,'\\120704','\\1207',0,'0',0)",
        "(1314,0,'Allow Change Course List','',0,'\\120707','\\1207',0,'0',0)",
        "(1462,0,'Allow Delete Course List','',0,'\\120705','\\1207',0,'0',0)",
        "(1463,0,'Allow Print Course List','',0,'\\120706','\\1207',0,'0',0)",
        "(1330,0,'Allow Click Add Item Course List','',0,'\\120708','\\1207',0,'0',0)",
        "(1331,0,'Allow Click Edit Item Course List','',0,'\\120709','\\1207',0,'0',0)",
        "(1332,0,'Allow Click Delete Item Course List','',0,'\\120710','\\1207',0,'0',0)",

        "(920,0,'Subject List','',0,'\\1209','\\12',0,'0',0)",
        "(1308,0,'Allow Edit Subject List','',0,'\\120901','\\1209',0,'0',0)",
        "(1309,0,'Allow View Subject List','',0,'\\120902','\\1209',0,'0',0)",
        "(1310,0,'Allow New Subject List','',0,'\\120903','\\1209',0,'0',0)",
        "(1311,0,'Allow Save Subject List','',0,'\\120904','\\1209',0,'0',0)",
        "(1317,0,'Allow Change Subject List','',0,'\\120907','\\1209',0,'0',0)",
        "(1312,0,'Allow Delete Subject List','',0,'\\120905','\\1209',0,'0',0)",
        "(1313,0,'Allow Print Subject List','',0,'\\120906','\\1209',0,'0',0)",
        "(1324,0,'Allow Click Add Item Subject List','',0,'\\120910','\\1209',0,'0',0)",
        "(1325,0,'Allow Click Edit Item Subject List','',0,'\\120908','\\1209',0,'0',0)",
        "(1326,0,'Allow Click Delete Item Subject List','',0,'\\120909','\\1209',0,'0',0)",

        "(921,0,'Student Credentials','',0,'\\1210','\\12',0,'0',0)",

        "(922,0,'Student List','',0,'\\1211','\\12',0,'0',0)",
        "(923,0,'Allow Edit Student List','',0,'\\121101','\\1211',0,'0',0)",
        "(924,0,'Allow View Student List','',0,'\\121102','\\1211',0,'0',0)",
        "(925,0,'Allow New Student List','',0,'\\121103','\\1211',0,'0',0)",
        "(926,0,'Allow Save Student List','',0,'\\121104','\\1211',0,'0',0)",
        "(1315,0,'Allow Change Student List','',0,'\\121107','\\1211',0,'0',0)",
        "(927,0,'Allow Delete Student List','',0,'\\121105','\\1211',0,'0',0)",
        "(928,0,'Allow Print Student List','',0,'\\121106','\\1211',0,'0',0)",

        "(929,0,'New Student Requirements','',0,'\\1212','\\12',0,'0',0)",
        "(930,0,'Transferee Requirements','',0,'\\1213','\\12',0,'0',0)",
        "(931,0,'Scheme','',0,'\\1214','\\12',0,'0',0)",
        "(932,0,'Mode of Payment','',0,'\\1215','\\12',0,'0',0)",

        "(933,0,'Room List','',0,'\\1216','\\12',0,'0',0)",
        "(1452,0,'Allow Edit Room List','',0,'\\121601','\\1216',0,'0',0)",
        "(1453,0,'Allow View Room List','',0,'\\121602','\\1216',0,'0',0)",
        "(1454,0,'Allow New Room List','',0,'\\121603','\\1216',0,'0',0)",
        "(1455,0,'Allow Save Room List','',0,'\\121604','\\1216',0,'0',0)",
        "(1316,0,'Allow Change Student List','',0,'\\121607','\\1216',0,'0',0)",
        "(1456,0,'Allow Delete Room List','',0,'\\121605','\\1216',0,'0',0)",
        "(1457,0,'Allow Print Room List','',0,'\\121606','\\1216',0,'0',0)",
        "(1327,0,'Allow Click Add Item Room List','',0,'\\121608','\\1216',0,'0',0)",
        "(1328,0,'Allow Click Edit Item Room List','',0,'\\121609','\\1216',0,'0',0)",
        "(1329,0,'Allow Click Delete Item Room List','',0,'\\121610','\\1216',0,'0',0)",

        "(934,0,'Fees','',0,'\\1217','\\12',0,'0',0)",
        "(935,0,'Grade Component','',0,'\\1301','\\12',0,'0',0)",
        "(936,0,'Grade Equivalent','',0,'\\1302','\\12',0,'0',0)",
        "(937,0,'Grade Setup','',0,'\\1303','\\12',0,'0',0)",
      ]
    ];

    //13
    $menu['announcement'] =  [
      'parent' => ["(385,0,'ANNOUNCEMENT','',0,'\\13','\\',0,'0',0)"],
      'children' => [
        "(1362,0,'NOTICE','',0,'\\13001','\\13',0,'0',0)",
        "(1363,0,'EVENT','',0,'\\13002','\\13',0,'0',0)",
        "(1364,0,'HOLIDAY','',0,'\\13003','\\13',0,'0',0)",
      ]
    ];

    //14
    $menu['schoolsystem'] = [
      'parent' => ["(1057,0,'SCHOOL SYSTEM','',0,'\\14','\\',0,'0',0)"],
      'children' => [
        //$encurriculum_access
        "(938,0,'Curriculum Setup','',0,'\\1401','\\14',0,'0',0)",
        "(939,0,'Allow Edit Curriculum Setup','',0,'\\140101','\\1401',0,'0',0)",
        "(940,0,'Allow View Curriculum Setup','',0,'\\140102','\\1401',0,'0',0)",
        "(941,0,'Allow New Curriculum Setup','',0,'\\140103','\\1401',0,'0',0)",
        "(942,0,'Allow Save Curriculum Setup','',0,'\\140104','\\1401',0,'0',0)",
        "(943,0,'Allow Delete Curriculum Setup','',0,'\\140105','\\1401',0,'0',0)",
        "(944,0,'Allow Change Code Curriculum Setup','',0,'\\140106','\\1401',0,'0',0)",
        "(945,0,'Allow Post Curriculum Setup','',0,'\\140107','\\1401',0,'0',0)",
        "(946,0,'Allow UnPost Curriculum Setup','',0,'\\140108','\\1401',0,'0',0)",
        "(947,0,'Allow Lock Curriculum Setup','',0,'\\140109','\\1401',0,'0',0)",
        "(948,0,'Allow UnLock Curriculum Setup','',0,'\\140110','\\1401',0,'0',0)",
        "(1318,0,'Allow Click Add Item Curriculum Setup','',0,'\\140111','\\1401',0,'0',0)",
        "(1319,0,'Allow Click Edit Item Curriculum Setup','',0,'\\140112','\\1401',0,'0',0)",
        "(1320,0,'Allow Click Delete Item Curriculum Setup','',0,'\\140113','\\1401',0,'0',0)",
        //$enschedule_access
        "(949,0,'Schedule Setup','',0,'\\1402','\\14',0,'0',0)",
        "(950,0,'Allow Edit Schedule Setup','',0,'\\140201','\\1402',0,'0',0)",
        "(951,0,'Allow View Schedule Setup','',0,'\\140202','\\1402',0,'0',0)",
        "(952,0,'Allow New Schedule Setup','',0,'\\140203','\\1402',0,'0',0)",
        "(953,0,'Allow Save Schedule Setup','',0,'\\140204','\\1402',0,'0',0)",
        "(954,0,'Allow Delete Schedule Setup','',0,'\\140205','\\1402',0,'0',0)",
        "(955,0,'Allow Change Code Schedule Setup','',0,'\\140206','\\1402',0,'0',0)",
        "(956,0,'Allow Post Schedule Setup','',0,'\\140207','\\1402',0,'0',0)",
        "(957,0,'Allow UnPost Schedule Setup','',0,'\\140208','\\1402',0,'0',0)",
        "(958,0,'Allow Lock Schedule Setup','',0,'\\140209','\\1402',0,'0',0)",
        "(959,0,'Allow UnLock Schedule Setup','',0,'\\140210','\\1402',0,'0',0)",
        //ENROLLMENT ASSESSMENT SETUP
        //$enassessmentsetup_access
        "(960,0,'Assessment Setup','',0,'\\1403','\\14',0,'0',0)",
        "(961,0,'Allow Edit Assessment Setup','',0,'\\140301','\\1403',0,'0',0)",
        "(962,0,'Allow View Assessment Setup','',0,'\\140302','\\1403',0,'0',0)",
        "(963,0,'Allow New Assessment Setup','',0,'\\140303','\\1403',0,'0',0)",
        "(964,0,'Allow Save Assessment Setup','',0,'\\140304','\\1403',0,'0',0)",
        "(965,0,'Allow Delete Assessment Setup','',0,'\\140305','\\1403',0,'0',0)",
        "(966,0,'Allow Change Code Assessment Setup','',0,'\\140306','\\1403',0,'0',0)",
        "(967,0,'Allow Post Assessment Setup','',0,'\\140307','\\1403',0,'0',0)",
        "(968,0,'Allow UnPost Assessment Setup','',0,'\\140308','\\1403',0,'0',0)",
        "(969,0,'Allow Lock Assessment Setup','',0,'\\140309','\\1403',0,'0',0)",
        "(970,0,'Allow UnLock Assessment Setup','',0,'\\140310','\\1403',0,'0',0)",
        //ENROLLMENT ASSESSMENT
        //$enassessment_access
        "(971,0,'Student Assessment','',0,'\\1404','\\14',0,'0',0)",
        "(972,0,'Allow Edit Student Assessment','',0,'\\140401','\\1404',0,'0',0)",
        "(973,0,'Allow View Student Assessment','',0,'\\140402','\\1404',0,'0',0)",
        "(974,0,'Allow New Student Assessment','',0,'\\140403','\\1404',0,'0',0)",
        "(975,0,'Allow Save Student Assessment','',0,'\\140404','\\1404',0,'0',0)",
        "(976,0,'Allow Delete Student Assessment','',0,'\\140405','\\1404',0,'0',0)",
        "(977,0,'Allow Change Code Student Assessment','',0,'\\140406','\\1404',0,'0',0)",
        "(978,0,'Allow Post Student Assessment','',0,'\\140407','\\1404',0,'0',0)",
        "(979,0,'Allow UnPost Student Assessment','',0,'\\140408','\\1404',0,'0',0)",
        "(980,0,'Allow Lock Student Assessment','',0,'\\140409','\\1404',0,'0',0)",
        "(981,0,'Allow UnLock Student Assessment','',0,'\\140410','\\1404',0,'0',0)",
        //ENROLLMENT REGISTRATION
        //$enregistration_access
        "(982,0,'Student Registration','',0,'\\1405','\\14',0,'0',0)",
        "(983,0,'Allow Edit Student Registration','',0,'\\140501','\\1405',0,'0',0)",
        "(984,0,'Allow View Student Registration','',0,'\\140502','\\1405',0,'0',0)",
        "(985,0,'Allow New Student Registration','',0,'\\140503','\\1405',0,'0',0)",
        "(986,0,'Allow Save Student Registration','',0,'\\140504','\\1405',0,'0',0)",
        "(987,0,'Allow Delete Student Registration','',0,'\\140505','\\1405',0,'0',0)",
        "(988,0,'Allow Change Code Student Registration','',0,'\\140506','\\1405',0,'0',0)",
        "(989,0,'Allow Post Student Registration','',0,'\\140507','\\1405',0,'0',0)",
        "(990,0,'Allow UnPost Student Registration','',0,'\\140508','\\1405',0,'0',0)",
        "(991,0,'Allow Lock Student Registration','',0,'\\140509','\\1405',0,'0',0)",
        "(992,0,'Allow UnLock Student Registration','',0,'\\140510','\\1405',0,'0',0)",
        //ENROLLMENT ADD/DROP
        //$enadddrop_access
        "(993,0,'Add / Drop','',0,'\\1406','\\14',0,'0',0)",
        "(994,0,'Allow Edit Add / Drop','',0,'\\140601','\\1406',0,'0',0)",
        "(995,0,'Allow View Add / Drop','',0,'\\140602','\\1406',0,'0',0)",
        "(996,0,'Allow New Add / Drop','',0,'\\140603','\\1406',0,'0',0)",
        "(997,0,'Allow Save Add / Drop','',0,'\\140604','\\1406',0,'0',0)",
        "(998,0,'Allow Delete Add / Drop','',0,'\\140605','\\1406',0,'0',0)",
        "(999,0,'Allow Change Code Add / Drop','',0,'\\140606','\\1406',0,'0',0)",
        "(1000,0,'Allow Post Add / Drop','',0,'\\140607','\\1406',0,'0',0)",
        "(1001,0,'Allow UnPost Add / Drop','',0,'\\140608','\\1406',0,'0',0)",
        "(1002,0,'Allow Lock Add / Drop','',0,'\\140609','\\1406',0,'0',0)",
        "(1003,0,'Allow UnLock Add / Drop','',0,'\\140610','\\1406',0,'0',0)",
        //GRADE ENTRY
        //$gradeentry_access = [
        "(1015,0,'Grade Entry','',0,'\\1411','\\14',0,'0',0)",
        "(1016,0,'Allow Edit Grade Entry','',0,'\\141101','\\1411',0,'0',0)",
        "(1017,0,'Allow View Grade Entry','',0,'\\141102','\\1411',0,'0',0)",
        "(1018,0,'Allow New Grade Entry','',0,'\\141103','\\1411',0,'0',0)",
        "(1019,0,'Allow Save Grade Entry','',0,'\\141104','\\1411',0,'0',0)",
        "(1020,0,'Allow Delete Grade Entry','',0,'\\141105','\\1411',0,'0',0)",
        "(1021,0,'Allow Change Code Grade Entry','',0,'\\141106','\\1411',0,'0',0)",
        "(1022,0,'Allow Post Grade Entry','',0,'\\141107','\\1411',0,'0',0)",
        "(1023,0,'Allow UnPost Grade Entry','',0,'\\141108','\\1411',0,'0',0)",
        "(1024,0,'Allow Lock Grade Entry','',0,'\\141109','\\1411',0,'0',0)",
        "(1025,0,'Allow UnLock Grade Entry','',0,'\\141110','\\1411',0,'0',0)",
        //STUDENT GRADE ENTRY
        //$studentgradeentry_access
        "(1026,0,'Student Grade Entry','',0,'\\1412','\\14',0,'0',0)",
        "(1027,0,'Allow Edit Student Grade Entry','',0,'\\141201','\\1412',0,'0',0)",
        "(1028,0,'Allow View Student Grade Entry','',0,'\\141202','\\1412',0,'0',0)",
        "(1029,0,'Allow New Student Grade Entry','',0,'\\141203','\\1412',0,'0',0)",
        "(1030,0,'Allow Save Student Grade Entry','',0,'\\141204','\\1412',0,'0',0)",
        "(1031,0,'Allow Delete Student Grade Entry','',0,'\\141205','\\1412',0,'0',0)",
        "(1032,0,'Allow Change Code Student Grade Entry','',0,'\\141206','\\1412',0,'0',0)",
        "(1033,0,'Allow Post Student Grade Entry','',0,'\\141207','\\1412',0,'0',0)",
        "(1034,0,'Allow UnPost Student Grade Entry','',0,'\\141208','\\1412',0,'0',0)",
        "(1035,0,'Allow Lock Student Grade Entry','',0,'\\141209','\\1412',0,'0',0)",
        "(1036,0,'Allow UnLock Student Grade Entry','',0,'\\141210','\\1412',0,'0',0)",
        //ATTENDANCE ENTRY
        //$enattendance_access = [
        "(1037,0,'Attendance Entry','',0,'\\1413','\\14',0,'0',0)",
        "(1038,0,'Allow Edit Attendance Entry','',0,'\\141301','\\1413',0,'0',0)",
        "(1039,0,'Allow View Attendance Entry','',0,'\\141302','\\1413',0,'0',0)",
        "(1040,0,'Allow New Attendance Entry','',0,'\\141303','\\1413',0,'0',0)",
        "(1041,0,'Allow Save Attendance Entry','',0,'\\141304','\\1413',0,'0',0)",
        "(1042,0,'Allow Delete Attendance Entry','',0,'\\141305','\\1413',0,'0',0)",
        "(1043,0,'Allow Change Code Attendance Entry','',0,'\\141306','\\1413',0,'0',0)",
        "(1044,0,'Allow Post Attendance Entry','',0,'\\141307','\\1413',0,'0',0)",
        "(1045,0,'Allow UnPost Attendance Entry','',0,'\\141308','\\1413',0,'0',0)",
        "(1046,0,'Allow Lock Attendance Entry','',0,'\\141309','\\1413',0,'0',0)",
        "(1047,0,'Allow UnLock Attendance Entry','',0,'\\141310','\\1413',0,'0',0)"

      ]
    ];


    //15
    $menu['issuance'] = [
      'parent' => ["(1052,0,'ISSUANCE','',0,'\\15','\\',0,'0',0)"],
      'children' => [
        //$tr_access
        "(784,0,'Stock Request','',0,'\\1501','\\15',0,'0',0)",
        "(785,0,'Allow View Transaction S. Request','TR',0,'\\150101','\\1501',0,'0',0)",
        "(786,0,'Allow Click Edit Button  S. Request','',0,'\\150102','\\1501',0,'0',0)",
        "(787,0,'Allow Click New Button S. Request','',0,'\\150103','\\1501',0,'0',0)",
        "(788,0,'Allow Click Save Button S. Request','',0,'\\150104','\\1501',0,'0',0)",
        "(789,0,'Allow Click Change Document# S. Request','',0,'\\150105','\\1501',0,'0',0)",
        "(790,0,'Allow Click Delete Button S. Request','',0,'\\150106','\\1501',0,'0',0)",
        "(791,0,'Allow Click Print Button S. Request','',0,'\\150107','\\1501',0,'0',0)",
        "(792,0,'Allow Click Lock Button S. Request','',0,'\\150108','\\1501',0,'0',0)",
        "(793,0,'Allow Click UnLock Button S. Request','',0,'\\150109','\\1501',0,'0',0)",
        "(794,0,'Allow Click Post Button S. Request','',0,'\\150110','\\1501',0,'0',0)",
        "(795,0,'Allow Click UnPost Button S. Request','',0,'\\150111','\\1501',0,'0',0)",
        "(839,1,'Allow Click Add Item S. Request','',0,'\\150112','\\1501',0,'0',0)",
        "(840,1,'Allow Click Edit Item S. Request','',0,'\\150113','\\1501',0,'0',0)",
        "(841,1,'Allow Click Delete Item S. Request','',0,'\\150114','\\1501',0,'0',0)",
        "(842,1,'Allow Change Amount S. Request','',0,'\\150115','\\1501',0,'0',0)",

        //$st_access
        "(881,0,'Stock Transfer','',0,'\\1502','\\15',0,'0',0)",
        "(882,0,'Allow View Transaction S. Transfer','ST',0,'\\150201','\\1502',0,'0',0)",
        "(883,0,'Allow Click Edit Button  S. Transfer','',0,'\\150202','\\1502',0,'0',0)",
        "(884,0,'Allow Click New Button S. Transfer','',0,'\\150203','\\1502',0,'0',0)",
        "(885,0,'Allow Click Save Button S. Transfer','',0,'\\150204','\\1502',0,'0',0)",
        "(886,0,'Allow Click Change Document# S. Transfer','',0,'\\150205','\\1502',0,'0',0)",
        "(887,0,'Allow Click Delete Button S. Transfer','',0,'\\150206','\\1502',0,'0',0)",
        "(888,0,'Allow Click Print Button S. Transfer','',0,'\\150207','\\1502',0,'0',0)",
        "(889,0,'Allow Click Lock Button S. Transfer','',0,'\\150208','\\1502',0,'0',0)",
        "(890,0,'Allow Click UnLock Button S. Transfer','',0,'\\150209','\\1502',0,'0',0)",
        "(891,0,'Allow Click Post Button S. Transfer','',0,'\\150210','\\1502',0,'0',0)",
        "(892,0,'Allow Click UnPost Button S. Transfer','',0,'\\150211','\\1502',0,'0',0)",
        "(893,1,'Allow Click Add Item S. Transfer','',0,'\\150212','\\1502',0,'0',0)",
        "(896,1,'Allow Click Edit Item S. Transfer','',0,'\\150213','\\1502',0,'0',0)",
        "(894,1,'Allow Click Delete Item S. Transfer','',0,'\\150214','\\1502',0,'0',0)",
        "(895,1,'Allow Change Amount S. Transfer','',0,'\\150215','\\1502',0,'0',0)",

        //$sis_access 
        "(897,0,'Stock Issuance','',0,'\\1503','\\15',0,'0',0)",
        "(898,0,'Allow View Transaction S. Issuance','SS',0,'\\150301','\\1503',0,'0',0)",
        "(899,0,'Allow Click Edit Button  S. Issuance','',0,'\\150302','\\1503',0,'0',0)",
        "(900,0,'Allow Click New Button S. Issuance','',0,'\\150303','\\1503',0,'0',0)",
        "(901,0,'Allow Click Save Button S. Issuance','',0,'\\150304','\\1503',0,'0',0)",
        "(902,0,'Allow Click Change Document# S. Issuance','',0,'\\150305','\\1503',0,'0',0)",
        "(903,0,'Allow Click Delete Button S. Issuance','',0,'\\150306','\\1503',0,'0',0)",
        "(904,0,'Allow Click Print Button S. Issuance','',0,'\\150307','\\1503',0,'0',0)",
        "(905,0,'Allow Click Lock Button S. Issuance','',0,'\\150308','\\1503',0,'0',0)",
        "(906,0,'Allow Click UnLock Button S. Issuance','',0,'\\150309','\\1503',0,'0',0)",
        "(907,0,'Allow Click Post Button S. Issuance','',0,'\\150310','\\1503',0,'0',0)",
        "(908,0,'Allow Click UnPost Button S. Issuance','',0,'\\150311','\\1503',0,'0',0)",
        "(909,1,'Allow Click Add Item S. Issuance','',0,'\\150312','\\1503',0,'0',0)",
        "(910,1,'Allow Click Delete Item S. Issuance','',0,'\\150314','\\1503',0,'0',0)",
        "(911,1,'Allow Change Amount S. Issuance','',0,'\\150315','\\1503',0,'0',0)",
        "(912,1,'Allow Click Edit Item S. Issuance','',0,'\\150313','\\1503',0,'0',0)",
        //$trapproval_access
        "(1680,0,'Stock Request Approval','',0,'\\1504','\\15',0,'0',0)",
        "(1681,0,'Allow View Transaction S. Request Approval','SS',0,'\\150401','\\1504',0,'0',0)",
        "(1682,0,'Allow Click Edit Button  S. Request Approval','',0,'\\150402','\\1504',0,'0',0)",
        "(1683,0,'Allow Click New Button S. Request Approval','',0,'\\150403','\\1504',0,'0',0)",
        "(1684,0,'Allow Click Save Button S. Request Approval','',0,'\\150404','\\1504',0,'0',0)",
        "(1685,0,'Allow Click Change Document# S. Request Approval','',0,'\\150405','\\1504',0,'0',0)",
        "(1686,0,'Allow Click Delete Button S. Request Approval','',0,'\\150406','\\1504',0,'0',0)",
        "(1687,0,'Allow Click Print Button S. Request Approval','',0,'\\150407','\\1504',0,'0',0)",
        "(1688,0,'Allow Click Lock Button S. Request Approval','',0,'\\150408','\\1504',0,'0',0)",
        "(1689,0,'Allow Click UnLock Button S. Request Approval','',0,'\\150409','\\1504',0,'0',0)",
        "(1690,0,'Allow Click Post Button S. Request Approval','',0,'\\150410','\\1504',0,'0',0)",
        "(1691,0,'Allow Click UnPost Button S. Request Approval','',0,'\\150411','\\1504',0,'0',0)",
        "(1692,1,'Allow Click Add Item S. Request Approval','',0,'\\150412','\\1504',0,'0',0)",
        "(1693,1,'Allow Click Delete Item S. Request Approval','',0,'\\150414','\\1504',0,'0',0)",
        "(1694,1,'Allow Change Amount S. Request Approval','',0,'\\150415','\\1504',0,'0',0)",
        "(1695,1,'Allow Click Edit Item S. Request Approval','',0,'\\150413','\\1504',0,'0',0)",
      ]
    ];


    //16
    $menu['customersupport'] = [
      'parent' => ["(1075,0,'CUSTOMER SUPPORT','',0,'\\16','\\',0,'0',0)"],
      'children' => [

        //$create_ticket_access
        "(1059,0,'Create Ticket','',0,'\\1601','\\16',0,'0',0)",
        "(1060,0,'Allow View Create Ticket','',0,'\\160101','\\1601',0,'0',0)",
        "(1061,0,'Allow Click Edit Button Create Ticket','',0,'\\160102','\\1601',0,'0',0)",
        "(1062,0,'Allow Click New Button Create Ticket','',0,'\\160103','\\1601',0,'0',0)",
        "(1063,0,'Allow Click Save Button Create Ticket','',0,'\\160104','\\1601',0,'0',0)",
        "(1064,0,'Allow Click Change Code Create Ticket','',0,'\\160105','\\1601',0,'0',0)",
        "(1065,0,'Allow Click Delete Button Create Ticket','',0,'\\160106','\\1601',0,'0',0)",
        "(1066,0,'Allow Click Print Button Create Ticket','',0,'\\160107','\\1601',0,'0',0)",
        "(1067,0,'Allow Click Lock Button Create Ticket','',0,'\\160108','\\1601',0,'0',0)",
        "(1068,0,'Allow Click UnLock Button Create Ticket','',0,'\\160109','\\1601',0,'0',0)",
        "(1069,0,'Allow Click Change Amount Create Ticket','',0,'\\160110','\\1601',0,'0',0)",
        "(1070,0,'Allow Click Post Button Create Ticket','',0,'\\160111','\\1601',0,'0',0)",
        "(1071,0,'Allow Click UnPost Button Create Ticket','',0,'\\160112','\\1601',0,'0',0)",
        "(1072,0,'Allow Click Add Item Create Ticket','',0,'\\160113','\\1601',0,'0',0)",
        "(1073,0,'Allow Click Edit Item Create Ticket','',0,'\\160114','\\1601',0,'0',0)",
        "(1074,0,'Allow Click Delete Item Create Ticket','',0,'\\160115','\\1601',0,'0',0)",

        //$update_ticket_access 
        "(1076,0,'Update Ticket','',0,'\\1602','\\16',0,'0',0)",
        "(1077,0,'Allow View Update Ticket','',0,'\\160201','\\1602',0,'0',0)",
        "(1078,0,'Allow Click Edit Button Update Ticket','',0,'\\160202','\\1602',0,'0',0)",
        "(1079,0,'Allow Click New Button Update Ticket','',0,'\\160203','\\1602',0,'0',0)",
        "(1080,0,'Allow Click Save Button Update Ticket','',0,'\\160204','\\1602',0,'0',0)",
        "(1081,0,'Allow Click Change Code Update Ticket','',0,'\\160205','\\1602',0,'0',0)",
        "(1082,0,'Allow Click Delete Button Update Ticket','',0,'\\160206','\\1602',0,'0',0)",
        "(1083,0,'Allow Click Print Button Update Ticket','',0,'\\160207','\\1602',0,'0',0)",
        "(1084,0,'Allow Click Lock Button Update Ticket','',0,'\\160208','\\1602',0,'0',0)",
        "(1085,0,'Allow Click UnLock Button Update Ticket','',0,'\\160209','\\1602',0,'0',0)",
        "(1086,0,'Allow Click Change Amount Update Ticket','',0,'\\160210','\\1602',0,'0',0)",
        "(1087,0,'Allow Click Post Button Update Ticket','',0,'\\160211','\\1602',0,'0',0)",
        "(1088,0,'Allow Click UnPost Button Update Ticket','',0,'\\160212','\\1602',0,'0',0)",
        "(1089,0,'Allow Click Add Item Update Ticket','',0,'\\160213','\\1602',0,'0',0)",
        "(1090,0,'Allow Click Edit Item Update Ticket','',0,'\\160214','\\1602',0,'0',0)",
        "(1091,0,'Allow Click Delete Item Update Ticket','',0,'\\160215','\\1602',0,'0',0)",

        //$ticket_history_access
        "(1092,0,'Ticket History','',0,'\\1603','\\16',0,'0',0)",
        "(1093,0,'Allow View Ticket History','',0,'\\160301','\\1603',0,'0',0)",
        "(1094,0,'Allow Click Edit Button Ticket History','',0,'\\160302','\\1603',0,'0',0)",
        "(1095,0,'Allow Click New Button Ticket History','',0,'\\160303','\\1603',0,'0',0)",
        "(1096,0,'Allow Click Save Button Ticket History','',0,'\\160304','\\1603',0,'0',0)",
        "(1097,0,'Allow Click Change Code Ticket History','',0,'\\160305','\\1603',0,'0',0)",
        "(1098,0,'Allow Click Delete Button Ticket History','',0,'\\160306','\\1603',0,'0',0)",
        "(1099,0,'Allow Click Print Button Ticket History','',0,'\\160307','\\1603',0,'0',0)",
        "(1100,0,'Allow Click Lock Button Ticket History','',0,'\\160308','\\1603',0,'0',0)",
        "(1101,0,'Allow Click UnLock Button Ticket History','',0,'\\160309','\\1603',0,'0',0)",
        "(1102,0,'Allow Click Change Amount Ticket History','',0,'\\160310','\\1603',0,'0',0)",
        "(1103,0,'Allow Click Post Button Ticket History','',0,'\\160311','\\1603',0,'0',0)",
        "(1104,0,'Allow Click UnPost Button Ticket History','',0,'\\160312','\\1603',0,'0',0)",
        "(1105,0,'Allow Click Add Item Ticket History','',0,'\\160313','\\1603',0,'0',0)",
        "(1106,0,'Allow Click Edit Item Ticket History','',0,'\\160314','\\1603',0,'0',0)",
        "(1107,0,'Allow Click Delete Item Ticket History','',0,'\\160315','\\1603',0,'0',0)"

      ]
    ];


    //17
    $menu['hris'] = [
      'parent' => ["(1152,0,'HRIS','',0,'\\17','\\',0,'0',0)"],
      'children' => [
        //$hris_applicant_ledger_access
        "(1108,0,'Applicant Ledger','',0,'\\1701','\\17',0,'0',0)",
        "(1109,0,'Allow View Applicant Ledger','',0,'\\170101','\\1701',0,'0',0)",
        "(1110,0,'Allow Click Edit Button Applicant Ledger','',0,'\\170102','\\1701',0,'0',0)",
        "(1111,0,'Allow Click New Button Applicant Ledger','',0,'\\170103','\\1701',0,'0',0)",
        "(1112,0,'Allow Click Save Button Applicant Ledger','',0,'\\170104','\\1701',0,'0',0)",
        "(1113,0,'Allow Click Change Code Applicant Ledger','',0,'\\170105','\\1701',0,'0',0)",
        "(1114,0,'Allow Click Delete Button Applicant Ledger','',0,'\\170106','\\1701',0,'0',0)",
        "(1115,0,'Allow Click Print Button Applicant Ledger','',0,'\\170107','\\1701',0,'0',0)",
        "(1116,0,'Allow Click Post Button Applicant Ledger','',0,'\\170108','\\1701',0,'0',0)",
        "(1117,0,'Allow Click UnPost Button Applicant Ledger','',0,'\\170109','\\1701',0,'0',0)",
        "(1670,0,'Allow Click Lock Button Applicant Ledger','',0,'\\170110','\\1701',0,'0',0)",
        "(1671,0,'Allow Click UnLock Button Applicant Ledger','',0,'\\170111','\\1701',0,'0',0)",

        //$hris_personelreq_access
        "(1239,0,'Personnel Requisition','',0,'\\1702','\\17',0,'0',0)",
        "(1240,0,'Allow View Personnel Requisition','',0,'\\170201','\\1702',0,'0',0)",
        "(1241,0,'Allow Click Edit Button Personnel Requisition','',0,'\\170202','\\1702',0,'0',0)",
        "(1242,0,'Allow Click New Button Personnel Requisition','',0,'\\170203','\\1702',0,'0',0)",
        "(1243,0,'Allow Click Save Button Personnel Requisition','',0,'\\170204','\\1702',0,'0',0)",

        "(1245,0,'Allow Click Delete Button Personnel Requisition','',0,'\\170206','\\1702',0,'0',0)",
        "(1246,0,'Allow Click Print Button Personnel Requisition','',0,'\\170207','\\1702',0,'0',0)",
        "(1247,0,'Allow Click Post Button Personnel Requisition','',0,'\\170208','\\1702',0,'0',0)",
        "(1248,0,'Allow Click UnPost Button Personnel Requisition','',0,'\\170209','\\1702',0,'0',0)",
        "(1711,0,'Allow Click Lock Button Personnel Requisition','',0,'\\170210','\\1702',0,'0',0)",
        "(1712,0,'Allow Click UnLock Button Personnel Requisition','',0,'\\170211','\\1702',0,'0',0)",

        //$hris_joboffer_access
        "(1249,0,'Job Offer','',0,'\\1703','\\17',0,'0',0)",
        "(1250,0,'Allow View Job Offer','',0,'\\170301','\\1703',0,'0',0)",
        "(1251,0,'Allow Click Edit Button Job Offer','',0,'\\170302','\\1703',0,'0',0)",
        "(1252,0,'Allow Click New Button Job Offer','',0,'\\170303','\\1703',0,'0',0)",
        "(1253,0,'Allow Click Save Button Job Offer','',0,'\\170304','\\1703',0,'0',0)",

        "(1255,0,'Allow Click Delete Button Job Offer','',0,'\\170306','\\1703',0,'0',0)",
        "(1256,0,'Allow Click Print Button Job Offer','',0,'\\170307','\\1703',0,'0',0)",
        "(1257,0,'Allow Click Post Button Job Offer','',0,'\\170308','\\1703',0,'0',0)",

        "(1713,0,'Allow Click Lock Button Job Offer','',0,'\\170310','\\1703',0,'0',0)",
        "(1714,0,'Allow Click UnLock Button Job Offer','',0,'\\170311','\\1703',0,'0',0)",

        //$hris_turnoveritems_access
        "(1118,0,'Turn Over Of Items','',0,'\\1704','\\17',0,'0',0)",
        "(1119,0,'Allow View Turn Over Of Items','',0,'\\170401','\\1704',0,'0',0)",
        "(1120,0,'Allow Click Edit Button Turn Over Of Items','',0,'\\170402','\\1704',0,'0',0)",
        "(1121,0,'Allow Click New Button Turn Over Of Items','',0,'\\170403','\\1704',0,'0',0)",
        "(1122,0,'Allow Click Save Button Turn Over Of Items','',0,'\\170404','\\1704',0,'0',0)",
        "(1123,0,'Allow Click Change Code Turn Over Of Items','',0,'\\170405','\\1704',0,'0',0)",
        "(1124,0,'Allow Click Delete Button Turn Over Of Items','',0,'\\170406','\\1704',0,'0',0)",
        "(1125,0,'Allow Click Print Button Turn Over Of Items','',0,'\\170407','\\1704',0,'0',0)",
        "(1126,0,'Allow Click Post Button Turn Over Of Items','',0,'\\170408','\\1704',0,'0',0)",
        "(1127,0,'Allow Click UnPost Button Turn Over Of Items','',0,'\\170409','\\1704',0,'0',0)",
        "(1672,0,'Allow Click Lock Button Turn Over Of Items','',0,'\\170410','\\1704',0,'0',0)",
        "(1673,0,'Allow Click UnLock Button Turn Over Of Items','',0,'\\170411','\\1704',0,'0',0)",
        "(1321,0,'Allow Click Add Item Turn Over Of Items','',0,'\\170412','\\1704',0,'0',0)",
        "(1322,0,'Allow Click Edit Item Turn Over Of Items','',0,'\\170413','\\1704',0,'0',0)",
        "(1323,0,'Allow Click Delete Item Turn Over Of Items','',0,'\\170414','\\1704',0,'0',0)",

        //$hris_reqtrainingdev_access
        "(1128,0,'Request For Training And Development','',0,'\\1705','\\17',0,'0',0)",
        "(1129,0,'Allow View Request For Training And Development','',0,'\\170501','\\1705',0,'0',0)",
        "(1130,0,'Allow Click Edit Button Request For Training And Development','',0,'\\170502','\\1705',0,'0',0)",
        "(1131,0,'Allow Click New Button Request For Training And Development','',0,'\\170503','\\1705',0,'0',0)",
        "(1132,0,'Allow Click Save Button Request For Training And Development','',0,'\\170504','\\1705',0,'0',0)",
        "(1133,0,'Allow Click Change Code Request For Training And Development','',0,'\\170505','\\1705',0,'0',0)",
        "(1134,0,'Allow Click Delete Button Request For Training And Development','',0,'\\170506','\\1705',0,'0',0)",
        "(1135,0,'Allow Click Print Button Request For Training And Development','',0,'\\170507','\\1705',0,'0',0)",
        "(1136,0,'Allow Click Post Button Request For Training And Development','',0,'\\170508','\\1705',0,'0',0)",
        "(1137,0,'Allow Click UnPost Button Request For Training And Development','',0,'\\170509','\\1705',0,'0',0)",
        "(1674,0,'Allow Click Lock Button Request For Training And Development','',0,'\\170510','\\1705',0,'0',0)",
        "(1675,0,'Allow Click UnLock Button Request For Training And Development','',0,'\\170511','\\1705',0,'0',0)",

        //$hris_trainentry_access
        "(1138,0,'Training Entry','',0,'\\1706','\\17',0,'0',0)",
        "(1139,0,'Allow View Training Entry','',0,'\\170601','\\1706',0,'0',0)",
        "(1140,0,'Allow Click Edit Button Training Entry','',0,'\\170602','\\1706',0,'0',0)",
        "(1141,0,'Allow Click New Button Training Entry','',0,'\\170603','\\1706',0,'0',0)",
        "(1142,0,'Allow Click Save Button Training Entry','',0,'\\170604','\\1706',0,'0',0)",
        "(1143,0,'Allow Click Change Code Training Entry','',0,'\\170605','\\1706',0,'0',0)",
        "(1144,0,'Allow Click Delete Button Training Entry','',0,'\\170606','\\1706',0,'0',0)",
        "(1145,0,'Allow Click Print Button Training Entry','',0,'\\170607','\\1706',0,'0',0)",
        "(1146,0,'Allow Click Post Button Training Entry','',0,'\\170608','\\1706',0,'0',0)",
        "(1147,0,'Allow Click UnPost Button Training Entry','',0,'\\170609','\\1706',0,'0',0)",
        "(1676,0,'Allow Click Lock Button Training Entry','',0,'\\170610','\\1706',0,'0',0)",
        "(1677,0,'Allow Click UnLock Button Training Entry','',0,'\\170611','\\1706',0,'0',0)",

        //$hris_returnitems_access
        "(1158,0,'Return Of Items','',0,'\\1707','\\17',0,'0',0)",
        "(1159,0,'Allow View Return Of Items','',0,'\\170701','\\1707',0,'0',0)",
        "(1160,0,'Allow Click Edit Button Return Of Items','',0,'\\170702','\\1707',0,'0',0)",
        "(1161,0,'Allow Click New Button Return Of Items','',0,'\\170703','\\1707',0,'0',0)",
        "(1162,0,'Allow Click Save Button Return Of Items','',0,'\\170704','\\1707',0,'0',0)",
        "(1163,0,'Allow Click Change Code Return Of Items','',0,'\\170705','\\1707',0,'0',0)",
        "(1164,0,'Allow Click Delete Button Return Of Items','',0,'\\170706','\\1707',0,'0',0)",
        "(1165,0,'Allow Click Print Button Return Of Items','',0,'\\170707','\\1707',0,'0',0)",
        "(1166,0,'Allow Click Post Button Return Of Items','',0,'\\170708','\\1707',0,'0',0)",
        "(1167,0,'Allow Click UnPost Button Return Of Items','',0,'\\170709','\\1707',0,'0',0)",
        "(1678,0,'Allow Click Lock Button Return Of Items','',0,'\\170710','\\1707',0,'0',0)",
        "(1679,0,'Allow Click UnLock Button Return Of Items','',0,'\\170711','\\1707',0,'0',0)",
        "(1333,0,'Allow Click Add Item Return Of Items','',0,'\\170712','\\1707',0,'0',0)",
        "(1334,0,'Allow Click Edit Item Return Of Items','',0,'\\170713','\\1707',0,'0',0)",
        "(1335,0,'Allow Click Delete Item Return Of Items','',0,'\\170714','\\1707',0,'0',0)",

        //$hris_incidentreport_access 
        "(1178,0,'Incident Report','',0,'\\1708','\\17',0,'0',0)",
        "(1179,0,'Allow View Incident Report','',0,'\\170801','\\1708',0,'0',0)",
        "(1180,0,'Allow Click Edit Button Incident Report','',0,'\\170802','\\1708',0,'0',0)",
        "(1181,0,'Allow Click New Button Incident Report','',0,'\\170803','\\1708',0,'0',0)",
        "(1182,0,'Allow Click Save Button Incident Report','',0,'\\170804','\\1708',0,'0',0)",
        "(1183,0,'Allow Click Change Code Incident Report','',0,'\\170805','\\1708',0,'0',0)",
        "(1184,0,'Allow Click Delete Button Incident Report','',0,'\\170806','\\1708',0,'0',0)",
        "(1185,0,'Allow Click Print Button Incident Report','',0,'\\170807','\\1708',0,'0',0)",
        "(1186,0,'Allow Click Post Button Incident Report','',0,'\\170808','\\1708',0,'0',0)",
        "(1187,0,'Allow Click UnPost Button Incident Report','',0,'\\170809','\\1708',0,'0',0)",
        "(1703,0,'Allow Click Lock Button Incident Report','',0,'\\170810','\\1708',0,'0',0)",
        "(1704,0,'Allow Click UnLock Button Incident Report','',0,'\\170811','\\1708',0,'0',0)",


        //$hris_HN_access
        "(1208,0,'Notice to Explain','',0,'\\1709','\\17',0,'0',0)",
        "(1209,0,'Allow View Notice to Explain','',0,'\\170901','\\1709',0,'0',0)",
        "(1210,0,'Allow Click Edit Button Notice to Explain','',0,'\\170902','\\1709',0,'0',0)",
        "(1211,0,'Allow Click New Button Notice to Explain','',0,'\\170903','\\1709',0,'0',0)",
        "(1212,0,'Allow Click Save Button Notice to Explain','',0,'\\170904','\\1709',0,'0',0)",
        "(1213,0,'Allow Click Change Code Notice to Explain','',0,'\\170905','\\1709',0,'0',0)",
        "(1214,0,'Allow Click Delete Button Notice to Explain','',0,'\\170906','\\1709',0,'0',0)",
        "(1215,0,'Allow Click Print Button Notice to Explain','',0,'\\170907','\\1709',0,'0',0)",
        "(1216,0,'Allow Click Post Button Notice to Explain','',0,'\\170908','\\1709',0,'0',0)",
        "(1217,0,'Allow Click UnPost Button Notice to Explain','',0,'\\170909','\\1709',0,'0',0)",
        "(1705,0,'Allow Click Lock Button Notice to Explain','',0,'\\170910','\\1709',0,'0',0)",
        "(1706,0,'Allow Click UnLock Button Notice to Explain','',0,'\\170911','\\1709',0,'0',0)",

        //$hris_HD_access 
        "(1198,0,'Notice of Disciplinary Action','',0,'\\1710','\\17',0,'0',0)",
        "(1199,0,'Allow View Notice of Disciplinary Action','',0,'\\171001','\\1710',0,'0',0)",
        "(1200,0,'Allow Click Edit Button Notice of Disciplinary Action','',0,'\\171002','\\1710',0,'0',0)",
        "(1201,0,'Allow Click New Button Notice of Disciplinary Action','',0,'\\171003','\\1710',0,'0',0)",
        "(1202,0,'Allow Click Save Button Notice of Disciplinary Action','',0,'\\171004','\\1710',0,'0',0)",
        "(1203,0,'Allow Click Change Code Notice of Disciplinary Action','',0,'\\171005','\\1710',0,'0',0)",
        "(1204,0,'Allow Click Delete Button Notice of Disciplinary Action','',0,'\\171006','\\1710',0,'0',0)",
        "(1205,0,'Allow Click Print Button Notice of Disciplinary Action','',0,'\\171007','\\1710',0,'0',0)",
        "(1206,0,'Allow Click Post Button Notice of Disciplinary Action','',0,'\\171008','\\1710',0,'0',0)",
        "(1207,0,'Allow Click UnPost Button Notice of Disciplinary Action','',0,'\\171009','\\1710',0,'0',0)",
        "(1707,0,'Allow Click Lock Button Notice of Disciplinary Action','',0,'\\171010','\\1710',0,'0',0)",
        "(1708,0,'Allow Click UnLock Button Notice of Disciplinary Action','',0,'\\171011','\\1710',0,'0',0)",

        //$hris_HC_access 
        "(1228,0,'Clearance','',0,'\\1711','\\17',0,'0',0)",
        "(1229,0,'Allow View Clearance','',0,'\\171101','\\1711',0,'0',0)",
        "(1230,0,'Allow Click Edit Button Clearance','',0,'\\171102','\\1711',0,'0',0)",
        "(1231,0,'Allow Click New Button Clearance','',0,'\\171103','\\1711',0,'0',0)",
        "(1232,0,'Allow Click Save Button Clearance','',0,'\\171104','\\1711',0,'0',0)",
        "(1233,0,'Allow Click Change Code Clearance','',0,'\\171105','\\1711',0,'0',0)",
        "(1234,0,'Allow Click Delete Button Clearance','',0,'\\171106','\\1711',0,'0',0)",
        "(1235,0,'Allow Click Print Button Clearance','',0,'\\171107','\\1711',0,'0',0)",
        "(1236,0,'Allow Click Post Button Clearance','',0,'\\171108','\\1711',0,'0',0)",
        "(1237,0,'Allow Click UnPost Button Clearance','',0,'\\171109','\\1711',0,'0',0)",
        "(1709,0,'Allow Click Lock Button Clearance','',0,'\\171110','\\1711',0,'0',0)",
        "(1710,0,'Allow Click UnLock Button Clearance','',0,'\\171111','\\1711',0,'0',0)",


        //$hris_empstatusentrychange_access
        "(1168,0,'Employment Status Entry / Change','',0,'\\1712','\\17',0,'0',0)",
        "(1169,0,'Allow View Employment Status Entry / Change','',0,'\\171201','\\1712',0,'0',0)",
        "(1170,0,'Allow Click Edit Button Employment Status Entry / Change','',0,'\\171202','\\1712',0,'0',0)",
        "(1171,0,'Allow Click New Button Employment Status Entry / Change','',0,'\\171203','\\1712',0,'0',0)",
        "(1172,0,'Allow Click Save Button Employment Status Entry / Change','',0,'\\171204','\\1712',0,'0',0)",
        "(1173,0,'Allow Click Change Code Employment Status Entry / Change','',0,'\\171205','\\1712',0,'0',0)",
        "(1174,0,'Allow Click Delete Button Employment Status Entry / Change','',0,'\\171206','\\1712',0,'0',0)",
        "(1175,0,'Allow Click Print Button Employment Status Entry / Change','',0,'\\171207','\\1712',0,'0',0)",
        "(1176,0,'Allow Click Post Button Employment Status Entry / Change','',0,'\\171208','\\1712',0,'0',0)",
        "(1177,0,'Allow Click UnPost Button Employment Status Entry / Change','',0,'\\171209','\\1712',0,'0',0)",
        "(1701,0,'Allow Click Lock Button Employment Status Entry / Change','',0,'\\171210','\\1712',0,'0',0)",
        "(1702,0,'Allow Click UnLock Button Employment Status Entry / Change','',0,'\\171211','\\1712',0,'0',0)",

        "(1448,0,'Employment NDA History','',0,'\\1713','\\17',0,'0',0)",
        "(1344,0,'Allow View Employment NDA History','',0,'\\171301','\\1713',0,'0',0)",

        "(1450,0,'Employment Change History','',0,'\\1714','\\17',0,'0',0)",
        "(1345,0,'Allow View Employment Change History','',0,'\\171401','\\1714',0,'0',0)",


      ]

    ];


    //18
    $menu['hrissetup'] = [
      'parent' => ["(1259,0,'HRIS SETUP','',0,'\\18','\\',0,'0',0)"],
      'children' => [
        //$hris_compcodeconduct_access
        "(1260,1,'Company Code of Conduct','',0,'\\1801','\\18',0,0,0)",
        "(1261,0,'Allow View Company Code of Conduct','',0,'\\180101','\\1801',0,'0',0)",
        "(1262,0,'Allow Click Edit Button Company Code of Conduct','',0,'\\180102','\\1801',0,'0',0)",
        "(1263,0,'Allow Click New Button Company Code of Conduct','',0,'\\180103','\\1801',0,'0',0)",
        "(1264,0,'Allow Click Save Button Company Code of Conduct','',0,'\\180104','\\1801',0,'0',0)",
        "(1265,0,'Allow Click Delete Button Company Code of Conduct','',0,'\\180105','\\1801',0,'0',0)",
        "(1336,0,'Allow Click Change Code Company Code of Conduct','',0,'\\180106','\\1801',0,'0',0)",
        "(1337,0,'Allow Click Print Button Item Company Code of Conduct','',0,'\\180107','\\1801',0,'0',0)",
        "(1338,0,'Allow Click Add Item Company Code of Conduct','',0,'\\180108','\\1801',0,'0',0)",
        "(1339,0,'Allow Click Edit Item Company Code of Conduct','',0,'\\180109','\\1801',0,'0',0)",
        "(1340,0,'Allow Click Delete Item Company Code of Conduct','',0,'\\180110','\\1801',0,'0',0)",

        //$hris_jobtitlemaster_access
        "(1270,1,'Job Title Master','',0,'\\1802','\\18',0,0,0)",
        "(1271,0,'Allow View Job Title Master','',0,'\\180201','\\1802',0,'0',0)",
        "(1272,0,'Allow Click Edit Button Job Title Master','',0,'\\180202','\\1802',0,'0',0)",
        "(1273,0,'Allow Click New Button Job Title Master','',0,'\\180203','\\1802',0,'0',0)",
        "(1274,0,'Allow Click Save Button Job Title Master','',0,'\\180204','\\1802',0,'0',0)",
        "(1275,0,'Allow Click Delete Button Job Title Master','',0,'\\180205','\\1802',0,'0',0)",
        "(1717,0,'Allow Change Code Button Job Title Master','',0,'\\180206','\\1802',0,'0',0)",
        "(1718,0,'Allow Click Print Button Job Title Master','',0,'\\180207','\\1802',0,'0',0)",
        "(1341,0,'Allow Click Add Item Job Title Master','',0,'\\180208','\\1802',0,'0',0)",
        "(1342,0,'Allow Click Edit Item Job Title Master','',0,'\\180209','\\1802',0,'0',0)",
        "(1343,0,'Allow Click Delete Item Job Title Master','',0,'\\180210','\\1802',0,'0',0)",

        //$hris_master_setup_access
        "(1280,1,'Employment Status Master Entry','',0,'\\1803','\\18',0,0,0)",
        "(1281,1,'Status Change Master','',0,'\\1804','\\18',0,0,0)",
        "(1282,1,'Skill Requirements Master','',0,'\\1805','\\18',0,0,0)",
        "(1283,1,'Employment Requirements Master','',0,'\\1806','\\18',0,0,0)",
        "(1284,1,'Pre Employment Test','',0,'\\1807','\\18',0,0,0)"
      ]

    ];


    //19
    // PAYROLL
    $menu['payrollsetup'] = [
      'parent' => ["(1269,0,'PAYROLL SETUP','',0,'\\19','\\',0,'0',0)"],
      'children' => [
        "(1410,1,'Division','',0,'\\1901','\\19',0,0,0)",
        "(1470,1,'Section','',0,'\\1902','\\19',0,0,0)",
        "(1480,1,'Pay Group','',0,'\\1903','\\19',0,0,0)",
        //$department_access
        "(860,0,'Department Ledger','',0,'\\1904','\\19',0,'0',0)",
        "(861,0,'Allow View Department Ledger','',0,'\\190401','\\1904',0,'0',0)",
        "(862,0,'Allow Click Edit Button DEPT','',0,'\\190402','\\1904',0,'0',0)",
        "(863,0,'Allow Click New Button DEPT','',0,'\\190403','\\1904',0,'0',0)",
        "(864,0,'Allow Click Save Button DEPT','',0,'\\190404','\\1904',0,'0',0)",
        "(865,0,'Allow Click Change Code DEPT','',0,'\\190405','\\1904',0,'0',0)",
        "(866,0,'Allow Click Delete Button DEPT','',0,'\\190406','\\1904',0,'0',0)",
        "(867,0,'Allow Click Print Button DEPT','',0,'\\190407','\\1904',0,'0',0)",
        "(1500,1,'Annual Tax','',0,'\\1905','\\19',0,0,0)",
        "(1510,1,'Philhealth','',0,'\\1906','\\19',0,0,0)",
        "(1449,1,'SSS','',0,'\\1907','\\19',0,0,0)",
        "(1451,1,'Pag-ibig','',0,'\\1908','\\19',0,0,0)",
        "(1520,1,'Tax','',0,'\\1909','\\19',0,0,0)",
        "(1530,1,'Holiday','',0,'\\1910','\\19',0,0,0)",
        // LEAVE SETUP
        "(1540,1,'Leave Setup','',0,'\\1911','\\19',0,0,0)",
        "(1541,1,'Allow Click New Button Leave Setup','',0,'\\191101','\\1911',0,0,0)",
        "(1542,1,'Allow Click Save Button Leave Setup','',0,'\\191102','\\1911',0,0,0)",
        "(1543,1,'Allow Click Delete Button Leave Setup','',0,'\\191103','\\1911',0,0,0)",
        "(1544,1,'Allow Click Print Button Leave Setup','',0,'\\191104','\\1911',0,0,0)",
        "(1545,1,'Allow View Leave Setup','',0,'\\191105','\\1911',0,0,0)",
        "(1728,1,'Allow Click Edit Button','',0,'\\191106','\\1911',0,0,0)",
        "(1490,1,'Payroll Accounts','',0,'\\191107','\\1911',0,0,0)",
        // RATE SETUP
        "(1550,1,'Rate Setup','',0,'\\1912','\\19',0,0,0)",
        "(1551,1,'Allow Click Save Button Rate Setup','',0,'\\191201','\\1912',0,0,0)",
        "(1552,1,'Allow Click Print Button Rate Setup','',0,'\\191202','\\1912',0,0,0)",
        "(1553,1,'Allow View Rate Setup','',0,'\\191203','\\1912',0,0,0)",
        "(1554,1,'Allow Click New Button Rate Setup','',0,'\\191204','\\1912',0,0,0)",
        "(1555,1,'Allow Click Delete Button Rate Setup','',0,'\\191205','\\1912',0,0,0)",
        "(1556,1,'Allow Click Edit Button Rate Setup','',0,'\\191206','\\1912',0,0,0)",
        // SHIFT SETUP
        "(1560,1,'Shift Setup','',0,'\\1913','\\19',0,0,0)",
        "(1561,1,'Allow Click Save Button Shift Setup','',0,'\\191301','\\1913',0,0,0)",
        "(1562,1,'Allow Click Print Button Shift Setup','',0,'\\191302','\\1913',0,0,0)",
        "(1563,1,'Allow Click New Button Shift Setup','',0,'\\191303','\\1913',0,0,0)",
        "(1564,1,'Allow Click Delete Button Shift Setup','',0,'\\191304','\\1913',0,0,0)",
        "(1565,1,'Allow View Shift Setup','',0,'\\191305','\\1913',0,0,0)",
        "(1346,1,'Allow Click Edit Button Shift Setup','',0,'\\191306','\\1913',0,0,0)",
      ]
    ];


    //20
    $menu['payrolltransaction'] = [
      'parent' => ["(1266,0,'PAYROLL TRANSACTION','',0,'\\20','\\',0,'0',0)"],
      'children' => [
        "(1570,1,'Batch Setup','',0,'\\2001','\\20',0,0,0)",
        "(1347,1,'Allow Click Save Button Batch Setup','',0,'\\200101','\\2001',0,0,0)",
        "(1348,1,'Allow Click Print Button Batch Setup','',0,'\\200102','\\2001',0,0,0)",
        "(1349,1,'Allow Click New Button Batch Setup','',0,'\\200103','\\2001',0,0,0)",
        "(1350,1,'Allow Click Delete Button Batch Setup','',0,'\\200104','\\2001',0,0,0)",
        "(1351,1,'Allow View Batch Setup','',0,'\\200105','\\2001',0,0,0)",
        "(1352,1,'Allow Click Edit Button Batch Setup','',0,'\\200106','\\2001',0,0,0)",

        // EARNING DEDUCTION SETUP
        "(1580,1,'Earning And Deduction Setup','',0,'\\2002','\\20',0,0,0)",
        "(1581,1,'Allow Click Save Button Earning And Deduction Setup','',0,'\\200201','\\2002',0,0,0)",
        "(1582,1,'Allow Click Print Button Earning And Deduction Setup','',0,'\\200202','\\2002',0,0,0)",
        "(1583,1,'Allow Click New Button Earning And Deduction Setup','',0,'\\200203','\\2002',0,0,0)",
        "(1584,1,'Allow Click Delete Button Earning And Deduction Setup','',0,'\\200204','\\2002',0,0,0)",
        "(1585,1,'Allow View Earning And Deduction Setup','',0,'\\200205','\\2002',0,0,0)",
        // LEAVE APPLICATION
        "(1590,1,'Leave Application','',0,'\\2003','\\20',0,0,0)",
        "(1591,1,'Allow Click Save Button Leave Application','',0,'\\200301','\\2003',0,0,0)",
        "(1592,1,'Allow Click Print Button Leave Application','',0,'\\200302','\\2003',0,0,0)",
        "(1593,1,'Allow Click New Button Leave Application','',0,'\\200303','\\2003',0,0,0)",
        "(1594,1,'Allow Click Delete Button Leave Application','',0,'\\200304','\\2003',0,0,0)",
        "(1595,1,'Allow View Leave Application','',0,'\\200305','\\2003',0,0,0)",
        "(1596,1,'Allow Click Edit Button Leave Application','',0,'\\200306','\\2003',0,0,0)",

        "(1600,1,'Piece Entry','',0,'\\2004','\\20',0,0,0)",
        "(1601,1,'Allow View Piece Entry','',0,'\\200401','\\2004',0,0,0)",
        "(1602,1,'Allow Click Create Button','',0,'\\200402','\\2004',0,0,0)",
        "(1603,1,'Allow Edit Piece Entry','',0,'\\200403','\\2004',0,0,0)",
        "(1604,1,'Allow CLick Delete Button','',0,'\\200404','\\2004',0,0,0)",
        "(1605,1,'Allow Click Print Button','',0,'\\200405','\\2004',0,0,0)",
        "(1606,1,'Allow Click Save all Entry','',0,'\\200406','\\2004',0,0,0)",
        // PROCESSING MODULE
        "(1610,1,'Processing Module','',0,'\\2005','\\20',0,0,0)",
        "(1611,1,'Allow Click Button Time and Attendance','',0,'\\200501','\\2005',0,0,0)",
        "(1613,1,'Allow View Processing Module','',0,'\\200503','\\2005',0,0,0)",
        // EMPLOYEE SCHEDULE
        "(1620,1,'Employee`s Schedule','',0,'\\2006','\\20',0,0,0)",
        "(1621,1,'Allow View Employee`s Schedule','',0,'\\200601','\\2006',0,0,0)",
        "(1622,1,'Allow Click Button Save Employee`s Schedule','',0,'\\200602','\\2006',0,0,0)",
        "(1623,1,'Allow Click Button Print Employee`s Schedule','',0,'\\200603','\\2006',0,0,0)",
        "(1624,1,'Allow Click Button Edit Employee`s Schedule','',0,'\\200604','\\2006',0,0,0)",
        // SCHEDULE SETUP
        "(1357,1,'Payroll Process','',0,'\\2007','\\20',0,0,0)",
        "(1358,1,'Allow View Payroll Process','',0,'\\200701','\\2007',0,0,0)",
        "(1359,1,'Allow Create Schedule','',0,'\\200702','\\2007',0,0,0)",
        "(1360,1,'Allow Post Actual In/Out','',0,'\\200703','\\2007',0,0,0)",
        "(1361,1,'Allow Click Button Edit Schedule Setup','',0,'\\200704','\\2007',0,0,0)",
        // OVERTIME APPROVAL
        "(1630,1,'Overtime Approval','',0,'\\2008','\\20',0,0,0)",
        "(1631,1,'Allow View Overtime Approval','',0,'\\200801','\\2008',0,0,0)",
        "(1632,1,'Allow Click Button Save Overtime Approval','',0,'\\200802','\\2008',0,0,0)",
        "(1633,1,'Allow Click Button Print Overtime Approval','',0,'\\200803','\\2008',0,0,0)",
        // PAYROLL SETUP
        "(1650,1,'Payroll Setup','',0,'\\2009','\\20',0,0,0)",
        "(1651,1,'Allow View Payroll Setup','',0,'\\200901','\\2009',0,0,0)",
        "(1652,1,'Allow Click Button Entry Payroll Setup','',0,'\\200902','\\2009',0,0,0)",
        "(1653,1,'Allow Click Button Process Payroll Setup','',0,'\\200903','\\2009',0,0,0)",
        "(1353,1,'Allow Click Button Save Payroll Setup','',0,'\\200904','\\2009',0,0,0)",
        "(1354,1,'Allow Click Button Edit Payroll Setup','',0,'\\200905','\\2009',0,0,0)",
        "(1355,1,'Allow Click Add Item Payroll Setup','',0,'\\200906','\\2009',0,0,0)",
        "(1356,1,'Allow Click Button Print Payroll Setup','',0,'\\200907','\\2009',0,0,0)",
        // employee
        "(1720,1,'Employee','',0,'\\2010','\\20',0,0,0)",
        "(1290,1,'Allow Click Button General','',0,'\\201001','\\2010',0,0,0)",
        "(1291,1,'Allow Click Button Dependents','',0,'\\201002','\\2010',0,0,0)",
        "(1292,1,'Allow Click Button Education','',0,'\\201003','\\2010',0,0,0)",
        "(1293,1,'Allow Click Button Employment','',0,'\\201004','\\2010',0,0,0)",
        "(1294,1,'Allow Click Button Rate','',0,'\\201005','\\2010',0,0,0)",
        "(1295,1,'Allow Click Button Loans','',0,'\\201006','\\2010',0,0,0)",
        "(1296,1,'Allow Click Button Advances','',0,'\\201007','\\2010',0,0,0)",
        "(1297,1,'Allow Click Button Contract','',0,'\\201008','\\2010',0,0,0)",
        "(1298,1,'Allow Click Button Allowance','',0,'\\201009','\\2010',0,0,0)",
        "(1299,1,'Allow Click Button Training','',0,'\\201010','\\2010',0,0,0)",
        "(1300,1,'Allow Click Button Turn Over and Return Items','',0,'\\201011','\\2010',0,0,0)",
        "(1301,1,'Allow View Employee Ledger','',0,'\\201012','\\2010',0,'0',0)",
        "(1302,1,'Allow Click Edit Button EMP','',0,'\\201013','\\2010',0,'0',0)",
        "(1303,1,'Allow Click New Button EMP','',0,'\\201014','\\2010',0,'0',0)",
        "(1304,1,'Allow Click Save Button EMP','',0,'\\201015','\\2010',0,'0',0)",
        "(1305,1,'Allow Click Change Code EMP','',0,'\\201016','\\2010',0,'0',0)",
        "(1306,1,'Allow Click Delete Button EMP','',0,'\\201017','\\2010',0,'0',0)",
        "(1307,1,'Allow Click Print Button EMP','',0,'\\201018','\\2010',0,'0',0)",
      ]
    ];



    //21
    $parent = "(1700,0,'ACCOUNT UTILITIES','',0,'\\21','\\',0,'0',0)";
    $children = "(362,0,'Manage useraccess','',0,'\\2101','\\21',0,'0',0)";

    if ($this->companysetup->getbranchaccess($params)) {
      $children = $children . ",(797,0,'Branch Access','',0,'\\2102','\\21',0,'0',0)";
    }
    $children = $children . ",(798,0,'Branch','',0,'\\2103','\\21',0,'0',0)";

    $menu['accountutilities'] =  [
      'parent' => [$parent],
      'children' => [$children]
    ];



    //22
    $parent = "(1775,0,'PROJECT SETUP','',0,'\\22','\\',0,'0',0)";
    $children = "(1776,0,'Project Management','',0,'\\2201','\\22',0,'0',0),
               (1777,0,'Allow View Transaction PM','PM',0,'\\220101','\\2201',0,'0',0),
               (1778,0,'Allow Click Edit Button PM','',0,'\\220102','\\2201',0,'0',0),
               (1779,0,'Allow Click New Button PM','',0,'\\220103','\\2201',0,'0',0),
               (1780,0,'Allow Click Save Button PM','',0,'\\220104','\\2201',0,'0',0),
               (1781,0,'Allow Click Change Document# PM','',0,'\\220105','\\2201',0,'0',0),
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
               (1793,0,'Allow View Summary PM','',0,'\\220117','\\2201',0,'0',0),

               (1857,0,'Stage Setup','',0,'\\2202','\\22',0,'0',0)";

    //23
    $children = $children . ",(1795,0,'CONSTRUCTION','',0,'\\23','\\',0,'0',0),
               (1796,0,'Bill of Quantity','',0,'\\2301','\\23',0,'0',0),
               (1797,0,'Allow View Transaction BQ','BQ',0,'\\230101','\\2301',0,'0',0),
               (1798,0,'Allow Click Edit Button BQ','',0,'\\230102','\\2301',0,'0',0),
               (1799,0,'Allow Click New Button BQ','',0,'\\230103','\\2301',0,'0',0),
               (1800,0,'Allow Click Save Button BQ','',0,'\\230104','\\2301',0,'0',0),
               (1801,0,'Allow Click Change Document# BQ','',0,'\\230105','\\2301',0,'0',0),
               (1802,0,'Allow Click Delete Button BQ','',0,'\\230106','\\2301',0,'0',0),
               (1803,0,'Allow Click Print Button BQ','',0,'\\230107','\\2301',0,'0',0),
               (1804,0,'Allow Click Lock Button BQ','',0,'\\230108','\\2301',0,'0',0),
               (1805,0,'Allow Click UnLock Button BQ','',0,'\\230109','\\2301',0,'0',0),
               (1806,0,'Allow Click Post Button BQ','',0,'\\230110','\\2301',0,'0',0),
               (1807,0,'Allow Click UnPost  Button BQ','',0,'\\230111','\\2301',0,'0',0),
               (1808,1,'Allow Click Add Item BQ','',0,'\\230112','\\2301',0,'0',0),
               (1809,1,'Allow Click Edit Item BQ','',0,'\\230113','\\2301',0,'0',0),
               (1810,1,'Allow Click Delete Item BQ','',0,'\\230114','\\2301',0,'0',0),

               (1811,0,'Job Order','',0,'\\2302','\\23',0,'0',0),
               (1812,0,'Allow View Transaction JO','JO',0,'\\230201','\\2302',0,'0',0),
               (1813,0,'Allow Click Edit Button JO','',0,'\\230202','\\2302',0,'0',0),
               (1814,0,'Allow Click New Button JO','',0,'\\230203','\\2302',0,'0',0),
               (1815,0,'Allow Click Save Button JO','',0,'\\230204','\\2302',0,'0',0),
               (1816,0,'Allow Click Change Document# JO','',0,'\\230205','\\2302',0,'0',0),
               (1817,0,'Allow Click Delete Button JO','',0,'\\230206','\\2302',0,'0',0),
               (1818,0,'Allow Click Print Button JO','',0,'\\230207','\\2302',0,'0',0),
               (1819,0,'Allow Click Lock Button JO','',0,'\\230208','\\2302',0,'0',0),
               (1820,0,'Allow Click UnLock Button JO','',0,'\\230209','\\2302',0,'0',0),
               (1821,0,'Allow Click Post Button JO','',0,'\\230210','\\2302',0,'0',0),
               (1822,0,'Allow Click UnPost  Button JO','',0,'\\230211','\\2302',0,'0',0),
               (1823,1,'Allow Click Add Item JO','',0,'\\230212','\\2302',0,'0',0),
               (1824,1,'Allow Click Edit Item JO','',0,'\\230213','\\2302',0,'0',0),
               (1825,1,'Allow Click Delete Item JO','',0,'\\230214','\\2302',0,'0',0),

               (1826,0,'Job Completion','',0,'\\2303','\\23',0,'0',0),
               (1827,0,'Allow View Transaction JC','JC',0,'\\230301','\\2303',0,'0',0),
               (1828,0,'Allow Click Edit Button JC','',0,'\\230302','\\2303',0,'0',0),
               (1829,0,'Allow Click New Button JC','',0,'\\230303','\\2303',0,'0',0),
               (1830,0,'Allow Click Save Button JC','',0,'\\230304','\\2303',0,'0',0),
               (1831,0,'Allow Click Change Document# JC','',0,'\\230305','\\2303',0,'0',0),
               (1832,0,'Allow Click Delete Button JC','',0,'\\230306','\\2303',0,'0',0),
               (1833,0,'Allow Click Print Button JC','',0,'\\230307','\\2303',0,'0',0),
               (1834,0,'Allow Click Lock Button JC','',0,'\\230308','\\2303',0,'0',0),
               (1835,0,'Allow Click UnLock Button JC','',0,'\\230309','\\2303',0,'0',0),
               (1836,0,'Allow Click Post Button JC','',0,'\\230310','\\2303',0,'0',0),
               (1837,0,'Allow Click UnPost  Button JC','',0,'\\230311','\\2303',0,'0',0),
               (1838,1,'Allow Click Add Item JC','',0,'\\230312','\\2303',0,'0',0),
               (1839,1,'Allow Click Edit Item JC','',0,'\\230313','\\2303',0,'0',0),
               (1840,1,'Allow Click Delete Item JC','',0,'\\230314','\\2303',0,'0',0),
               (1841,0,'Allow View Transaction accounting JC','',0,'\\230315','\\2303',0,'0',0),

               (1842,0,'Progress Billing','',0,'\\2304','\\23',0,'0',0),
               (1843,0,'Allow View Transaction PB','PB',0,'\\230401','\\2304',0,'0',0),
               (1844,0,'Allow Click Edit Button  PB','',0,'\\230402','\\2304',0,'0',0),
               (1845,0,'Allow Click New Button PB','',0,'\\230403','\\2304',0,'0',0),
               (1846,0,'Allow Click Save Button PB','',0,'\\230404','\\2304',0,'0',0),
               (1847,0,'Allow Click Change Document# PB','',0,'\\230405','\\2304',0,'0',0),
               (1848,0,'Allow Click Delete Button PB','',0,'\\230406','\\2304',0,'0',0),
               (1849,0,'Allow Click Print Button PB','',0,'\\230407','\\2304',0,'0',0),
               (1850,0,'Allow Click Lock Button PB','',0,'\\230408','\\2304',0,'0',0),
               (1851,0,'Allow Click UnLock Button PB','',0,'\\230409','\\2304',0,'0',0),
               (1852,0,'Allow Click Post Button PB','',0,'\\230410','\\2304',0,'0',0),
               (1853,0,'Allow Click UnPost Button PB','',0,'\\230411','\\2304',0,'0',0),
               (1854,0,'Allow Click Add Account PB','',0,'\\230412','\\2304',0,'0',0),
               (1855,0,'Allow Click Edit Account PB','',0,'\\230413','\\2304',0,'0',0),
               (1856,0,'Allow Click Delete Account PB','',0,'\\230414','\\2304',0,'0',0)";



    $menu['construction'] = [
      'parent' => [$parent],
      'children' => [$children]
    ];





    $modules = $this->companysetup->getmodule($params);
    $attrmenu = $this->othersClass->array_only($menu, $modules);

    return $attrmenu;
  } // end function













} //end class
