<?php

namespace App\Http\Classes;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

use Exception;
use Throwable;
use Session;


// last attribute - 4192


class setreportlist
{
  private $othersClass;
  public $companysetup;

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
  }
  public function reportlist($params)
  {

    // AMS AND AIMS
    // MASTERFILE
    $general_item_list = "";
    $rep_plan_types = "";
    $rep_chartofaccounts = "";

    $rep_trucklist = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;

      default:
        $masterfile = "('','\\9','','','',0,0,0,'Masterfile Report','\\900',3101,'0'," . $params['levelid'] . ")";
        $rep_chartofaccounts = "('','\\900','','','',0,1,0,'Chart of Accounts','\\90006',3102,'0'," . $params['levelid'] . ")";
        $warehouse_list = "('','\\900','','','',0,1,0,'Warehouse List','\\90007',3103,'0'," . $params['levelid'] . ")";
        $employee_list = "('','\\900','','','',0,1,0,'Employee List','\\90008',3104,'0'," . $params['levelid'] . ")";
        $department_list = "('','\\900','','','',0,1,0,'Department List','\\90009',3105,'0'," . $params['levelid'] . ")";
        $project_list = "('','\\900','','','',0,1,0,'Project List','\\90010',3516,'0'," . $params['levelid'] . ")";

        if ($this->companysetup->getsystemtype($params) == 'FAMS' || $this->companysetup->getsystemtype($params) == 'ATI') {
          $general_item_list = "('','\\900','','','',0,1,0,'General Item List','\\90013',3534,'0'," . $params['levelid'] . ")";
        }

        if ($this->companysetup->getsystemtype($params) == 'FAMS') {
          $rep_chartofaccounts = "";
        }

        if ($params['companyid'] == 34) { //evergreen
          $rep_plan_types = "('','\\900','','','',0,1,0,'Plan Types','\\90014',4095,'0'," . $params['levelid'] . ")";
        }

        if ($params['companyid'] == 19) { //housegem
          $rep_trucklist = "('','\\900','','','',0,1,0,'Truck List','\\90015',5116,'0'," . $params['levelid'] . ")";
        }

        if ($params['companyid'] == 57) {
          $masterfile = "";
          $rep_chartofaccounts = "";
          $warehouse_list = "";
          $employee_list = "";
          $department_list = "";
          $project_list = "";
        }
        break;
    }


    // ACCOUNTING BOOKS
    $rep_general_ledger = '';
    $rep_daily_cash_flow = '';

    $parent_accountingbooks = "";
    $rep_cashdisbursementbook = "";
    $rep_cashreceiptbook = "";
    $rep_journalvoucher = "";
    $rep_purchasejournal = "";
    $rep_salesjournal = "";
    $rep_debit_memo = "";
    $rep_credit_memo = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'FAMS':
      case 'QUEUING':
        break;

      default:
        if ($params['companyid'] == 57) {
        } else {
          $parent_accountingbooks = "('','\\9','','','',0,0,0,'Accounting Books','\\901',3001,'0'," . $params['levelid'] . ")";
          $rep_cashdisbursementbook = "('','\\901','','','',0,1,0,'Cash Disbursement Book','\\90101',3002,'0'," . $params['levelid'] . ")";
          $rep_cashreceiptbook = "('','\\901','','','',0,1,0,'Cash Receipt Book','\\90102',3003,'0'," . $params['levelid'] . ")";

          $rep_journalvoucherlbl = "Journal Voucher";
          if ($params['companyid'] == 10 || $params['companyid'] == 12) { // afti,afti usd
            $rep_journalvoucherlbl = "General Journal";
          }

          $rep_journalvoucher = "('','\\901','','','',0,1,0,'" . $rep_journalvoucherlbl . "','\\90103',3004,'0'," . $params['levelid'] . ")";
          $rep_purchasejournal = "('','\\901','','','',0,1,0,'Purchase Journal','\\90104',3005,'0'," . $params['levelid'] . ")";
          $rep_salesjournal = "('','\\901','','','',0,1,0,'Sales Journal','\\90105',3006,'0'," . $params['levelid'] . ")";
          $rep_debit_memo = "('','\\901','','','',0,1,0,'Debit Memo','\\90106',3404,'0'," . $params['levelid'] . ")";
          $rep_credit_memo = "('','\\901','','','',0,1,0,'Credit Memo','\\90107',3405,'0'," . $params['levelid'] . ")";

          if ($params['companyid'] == 10 || $params['companyid'] == 12) { // afti,afti usd
            $rep_general_ledger = "('','\\901','','','',0,1,0,'General Ledger','\\90108',3747,'0'," . $params['levelid'] . ")";
          }

          if ($params['companyid'] == 32) { // 3m
            $rep_daily_cash_flow = "('','\\901','','','',0,1,0,'Daily Cash flow','\\90109',3983,'0'," . $params['levelid'] . ")";
          }
        }


        break;
    }


    $parent_checkmonitoringreports = "";
    $rep_bouncedchecks = "";
    $rep_issuedchecks = "";
    $rep_receivedchecks = "";
    $rep_undepositedchecks = "";
    $rep_list_of_printed_checks = "";

    // CHECK MONITORING REPORTS
    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'FAMS':
      case 'QUEUING':
        break;

      default:
        if ($params['companyid'] == 57) {
        } else {
          $parent_checkmonitoringreports = "('','\\9','','','',0,0,0,'Check Monitoring Reports','\\902',3008,'0'," . $params['levelid'] . ")";
          $rep_bouncedchecks = "('','\\902','','','',0,1,0,'Bounced Checks','\\90201',3009,'0'," . $params['levelid'] . ")";
          $rep_issuedchecks = "('','\\902','','','',0,1,0,'Issued Checks','\\90202',3010,'0'," . $params['levelid'] . ")";
          $rep_receivedchecks = "('','\\902','','','',0,1,0,'Received Checks','\\90203',3011,'0'," . $params['levelid'] . ")";
          $rep_undepositedchecks = "('','\\902','','','',0,1,0,'Undeposited Checks','\\90204',3012,'0'," . $params['levelid'] . ")";
          if ($params['companyid'] == 55) { // afli
            $rep_list_of_printed_checks = "('','\\902','','','',0,1,0,'List of Printed Checks','\\90205',5548,'0'," . $params['levelid'] . ")";
          }
        }

        break;
    }

    // FINANCIAL STATEMENTS
    $rep_perCostCenterReport = "";
    $rep_detailedPerAccountReport = "";
    $isperdept  = "";
    $isperbranch  = "";
    $isperproject  = "";
    $isperstatement  = "";
    $monthly_trial_balance  = "";
    $monthly_balancesheet = "";
    $rep_budgetreport = "";

    $parent_financialstatements = "";
    $rep_balancesheet = "";
    $rep_incomestatement = "";
    $rep_subsidiaryledger = "";
    $rep_trialbalance = "";
    $rep_comparativeincomestatment = "";
    $rep_comparativebalancesheet = "";
    $rep_comparativetrialbalance = "";
    $rep_monthlyincomestatement = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'FAMS':
      case 'QUEUING':
        break;

      default:
        if ($params['companyid'] == 57) {
        } else {
          $parent_financialstatements = "('','\\9','','','',0,0,0,'Financial Statements','\\903',3013,'0'," . $params['levelid'] . ")";
          $rep_balancesheet = "('','\\903','','','',0,1,0,'Balance Sheet','\\90301',3014,'0'," . $params['levelid'] . ")";
          $rep_incomestatement = "('','\\903','','','',0,1,0,'Income Statement','\\90302',3015,'0'," . $params['levelid'] . ")";
          $rep_subsidiaryledger = "('','\\903','','','',0,1,0,'Subsidiary Ledger','\\90303',3016,'0'," . $params['levelid'] . ")";
          $rep_trialbalance = "('','\\903','','','',0,1,0,'Trial Balance','\\90304',3017,'0'," . $params['levelid'] . ")";
          $rep_comparativeincomestatment = "('','\\903','','','',0,1,0,'Comparative Income Statement','\\90306',3083,'0'," . $params['levelid'] . ")";
          $rep_comparativebalancesheet = "('','\\903','','','',0,1,0,'Comparative Balance Sheet','\\90307',3084,'0'," . $params['levelid'] . ")";
          $rep_comparativetrialbalance = "('','\\903','','','',0,1,0,'Comparative Trial Balance','\\90309',3480,'0'," . $params['levelid'] . ")";
          $rep_monthlyincomestatement = "('','\\903','','','',0,1,0,'Monthly Income Statement','\\90308',3085,'0'," . $params['levelid'] . ")";

          if ($params['companyid'] == 24) { //goodfound
            $rep_perCostCenterReport = "('','\\903','','','',0,1,0,'Per Cost Center Report','\\90314',4032,'0'," . $params['levelid'] . ")";
            $rep_detailedPerAccountReport = "('','\\903','','','',0,1,0,'Detailed Per Account Report','\\90315',4033,'0'," . $params['levelid'] . ")";
          }
          if ($params['companyid'] == 10) { //afti
            $isperdept  = "('','\\903','','','',0,1,0,'Profit and Loss per Department','\\90310',3453,'0'," . $params['levelid'] . ")";
            $isperbranch  = "('','\\903','','','',0,1,0,'Profit and Loss per Branch','\\90311',3454,'0'," . $params['levelid'] . ")";
            $isperproject  = "('','\\903','','','',0,1,0,'Profit and Loss per Product Line','\\90312',3455,'0'," . $params['levelid'] . ")";
            $isperstatement  = "('','\\903','','','',0,1,0,'Profit and Loss Statement by Product','\\90313',3748,'0'," . $params['levelid'] . ")";
            $rep_budgetreport  = "('','\\903','','','',0,1,0,'Budget Report','\\90318',4893,'0'," . $params['levelid'] . ")";
          }

          switch ($params['companyid']) {
            case 40: //cdo
            case 8: //maxipro
              $monthly_trial_balance  = "('','\\903','','','',0,1,0,'Monthly Trial Balance','\\90316',4650,'0'," . $params['levelid'] . ")";
              break;
          }

          if ($params['companyid'] == 8 || $params['companyid'] == 55) { //maxipro //afli
            $monthly_balancesheet  = "('','\\903','','','',0,1,0,'Monthly Balance Sheet','\\90317',4855,'0'," . $params['levelid'] . ")";
          }
        }


        break;
    }


    // ITEMS
    $parent_items = "";
    $rep_inventorybalance = "";
    $rep_schedule_of_inventory = "";
    $rep_stock_on_hand_per_warehouse = "";
    $rep_item_min_max_listing = "";
    $rep_inventoryaging_persite = "";
    $rep_inventory_balance_for_accounting = "";
    $rep_item_list_with_cost = "";
    $rep_sales_return_per_item = $rep_sales_summary_per_item = $rep_expiry_report = "";
    $rep_inventory_per_wh_type = "";
    $rep_inventory_balance_per_engine = "";
    $rep_item_list_with_id = "";

    $rep_itemlist = "";
    $rep_fixedassetlist = "";

    $rep_ksi_reorder_report = "";
    $rep_inventoryreport = "";

    $rep_analyzeitempurchasemonthly = "";
    $rep_inv_balanceperwh = "";

    $rep_analyzeitemsalesmonthly = "";
    $rep_analyzeitemsaleswithprofitmarkup = "";
    $rep_salesperitempercustomer = "";

    $rep_currentinventoryaging = "";
    $rep_fastmovingitems = $rep_slowmovingitems = "";
    $rep_itempurchasereport = "";
    $rep_itemtoexpired = "";
    $rep_itembalance_belowminimum = $rep_itembalance_aboveminimum = "";
    $rep_reorder = "";
    $rep_sales_summary_per_item_per_price = "";
    $rep_item_sales_report = "";



    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;
      case 'FAMS':
        $parent_items = "('','\\9','','','',0,0,0,'Items','\\904',3018,'0'," . $params['levelid'] . ")";
        $rep_inventorybalance = "('','\\904','','','',0,1,0,'Inventory Balance','\\90401',3019,'0'," . $params['levelid'] . ")";
        $rep_itemlist = "('','\\904','','','',0,1,0,'Item List','\\90404',3022,'0'," . $params['levelid'] . ")";
        $rep_fixedassetlist = "('','\\904','','','',0,1,0,'Fixed Asset Card List','\\90450',4838,'0'," . $params['levelid'] . ")";
        break;
      default:
        if ($params['companyid'] == 57) {
        } else {
          $parent_items = "('','\\9','','','',0,0,0,'Items','\\904',3018,'0'," . $params['levelid'] . ")";
          $rep_inventorybalance = "('','\\904','','','',0,1,0,'Inventory Balance','\\90401',3019,'0'," . $params['levelid'] . ")";
          $rep_analyzeitempurchasemonthly = "('','\\904','','','',0,1,0,'Monthly Analyze Item Purchase','\\90402',3020,'0'," . $params['levelid'] . ")";
          $rep_inv_balanceperwh = "('','\\904','','','',0,1,0,'Inventory Balance Per Warehouse Report','\\90541',4479,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 8: //maxipro
              $rep_analyzeitemsalesmonthly = "";
              $rep_analyzeitemsaleswithprofitmarkup = "";
              $rep_salesperitempercustomer = "";
              break;
            default:
              $rep_analyzeitemsalesmonthly = "('','\\904','','','',0,1,0,'Monthly Analyze Item Sales','\\90403',3021,'0'," . $params['levelid'] . ")";
              $rep_analyzeitemsaleswithprofitmarkup = "('','\\904','','','',0,1,0,'Analyze Item Sales with Profit Markup','\\90406',3025,'0'," . $params['levelid'] . ")";
              $rep_salesperitempercustomer = "('','\\904','','','',0,1,0,'Sales Per Item Per Customer','\\90409',3028,'0'," . $params['levelid'] . ")";
              break;
          }

          if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti,afti usd
            $rep_inventory_balance_for_accounting = "('','\\904','','','',0,1,0,'Inventory Balance For Accounting','\\90426',3588,'0'," . $params['levelid'] . ")";
          }

          $rep_itemlist = "('','\\904','','','',0,1,0,'Item List','\\90404',3022,'0'," . $params['levelid'] . ")";

          if ($params['companyid'] == 39) { //CBBSI
            $rep_itemlist = "('','\\904','','','',0,1,0,'Item Master List','\\90404',3022,'0'," . $params['levelid'] . ")";
            $rep_item_list_with_cost = "('','\\904','','','',0,1,0,'Item List With Cost','\\90445',4408,'0'," . $params['levelid'] . ")";
            $rep_item_list_with_id  = "('','\\904','','','',0,1,0,'Item List With Item ID','\\90447',4726,'0'," . $params['levelid'] . ")";
          }

          $rep_currentinventoryaging = "('','\\904','','','',0,1,0,'Current Inventory Aging','\\9041',3023,'0'," . $params['levelid'] . ")";
          $rep_fastmovingitems = "('','\\904','','','',0,1,0,'Fast Moving Items','\\90405',3024,'0'," . $params['levelid'] . ")";
          $rep_slowmovingitems = "('','\\904','','','',0,1,0,'Slow Moving Items','\\90408',3027,'0'," . $params['levelid'] . ")";
          $rep_itempurchasereport = "('','\\904','','','',0,1,0,'Item Purchase Report','\\90425',3139,'1'," . $params['levelid'] . ")";
          $rep_itemtoexpired = "('','\\904','','','',0,1,0,'Item to Expired','\\90410',3029,'0'," . $params['levelid'] . ")";
          $rep_itembalance_belowminimum = "('','\\904','','','',0,1,0,'Item Balance Below Minimum','\\90411',3030,'0'," . $params['levelid'] . ")";
          $rep_itembalance_aboveminimum = "('','\\904','','','',0,1,0,'Item Balance Above Maximum','\\90412',3031,'0'," . $params['levelid'] . ")";
          $rep_reorder = "('','\\904','','','',0,1,0,'Reorder Report','\\90507',3112,'0'," . $params['levelid'] . ")";
          $rep_sales_summary_per_item_per_price = "('','\\904','','','',0,1,0,'Sales Summary per Item per Price','\\90429',3525,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 14: //MAJESTY
              $rep_sales_return_per_item = "('','\\904','','','',0,1,0,'Sales Return per Item','\\90420',3532,'0'," . $params['levelid'] . ")";
              $rep_sales_summary_per_item = "('','\\904','','','',0,1,0,'Sales Summary per Item','\\90421',3533,'0'," . $params['levelid'] . ")";
              $rep_expiry_report = "('','\\904','','','',0,1,0,'Expiry Report','\\90422',3545,'0'," . $params['levelid'] . ")";
              break;
            case 15: //nathina
              $rep_inventory_per_wh_type = "('','\\904','','','',0,1,0,'Inventory Per WH Type','\\90428',3751,'0'," . $params['levelid'] . ")";
              break;
            case 39: //cbbsi
              $rep_sales_return_per_item = "('','\\904','','','',0,1,0,'Sales Return per Item','\\90420',3532,'0'," . $params['levelid'] . ")";
              $rep_sales_summary_per_item = "('','\\904','','','',0,1,0,'Sales Summary per Item','\\90421',3533,'0'," . $params['levelid'] . ")";
              $rep_schedule_of_inventory = "('','\\904','','','',0,1,0,'Schedule of Inventory','\\90441',4369,'0'," . $params['levelid'] . ")";
              $rep_item_min_max_listing = "('','\\904','','','',0,1,0,'Item Min Max Listing','\\90443',4390,'0'," . $params['levelid'] . ")";
              $rep_stock_on_hand_per_warehouse = "('','\\904','','','',0,1,0,'Stock On Hand Per Warehouse','\\90444',4407,'0'," . $params['levelid'] . ")";
              break;
            default: // UNIHOME
              if ($this->companysetup->getsystemtype($params) == 'AIMS') {
                $rep_sales_summary_per_item = "";
                if ($params['companyid'] == 36 || $params['companyid'] == 27) { //rozlab,nte
                  $rep_sales_summary_per_item = "('','\\904','','','',0,1,0,'Sales Summary per Item','\\90421',3533,'0'," . $params['levelid'] . ")";
                }
              } else {
                $rep_sales_summary_per_item = "('','\\904','','','',0,1,0,'Sales Summary per Item','\\90421',3533,'0'," . $params['levelid'] . ")";
              }
              break;
          }

          if ($params['companyid']  == 42) { //pdpi
            $rep_inventoryaging_persite = "('','\\904','','','',0,1,0,'Inventory Aging per Site','\\90443',4576,'0'," . $params['levelid'] . ")";
          }
          if ($params['companyid']  == 40) { //cdo
            $rep_inventory_balance_per_engine = "('','\\904','','','',0,1,0,'Inventory Balance Per Engine','\\90446',4649,'0'," . $params['levelid'] . ")";
          }

          $rep_fixedassetlist = "('','\\904','','','',0,1,0,'Fixed Asset Card List','\\90450',4838,'0'," . $params['levelid'] . ")";

          if ($params['companyid'] == 47) { //kitchenstar
            $rep_ksi_reorder_report = "('','\\904','','','',0,1,0,'KSI Reorder Report','\\90451',4856,'0'," . $params['levelid'] . ")";
            $rep_inventoryreport = "('','\\904','','','',0,1,0,'Inventory Report','\\90452',4899,'0'," . $params['levelid'] . ")";
          }

          if ($params['companyid'] == 59) { //roosevelt
            $rep_item_sales_report = "('','\\904','','','',0,1,0,'Item Sales Report','\\90454',5457,'0'," . $params['levelid'] . ")";
          }
        }

        break;
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
      $rep_inv_balanceperwh = "";
    }

    $rep_physical_inventory_sheet = '';
    $rep_top_performing_item = '';
    $rep_comparative_report_sales = '';
    $rep_item_group_performance_report = '';
    $rep_salesreportitemhistory = "";
    $rep_daily_cement_withdrawal_with_total_form_report = '';
    $rep_daily_cement_withdrawal_without_total_form_report = '';
    $rep_sales_item_by_location = "";
    $rep_withdrawal_summary_as_per_cost_center_report = "";
    $rep_fuel_withdrawal_summary_report = "";
    $rep_lubricant_consumption_report = "";
    $rep_withdrawal_summary_report = "";
    $rep_summary_of_withdrawals_report = "";
    $rep_daily_bag_report = "";


    switch ($this->companysetup->getsystemtype($params)) {
      case 'MISPOS':
        $rep_physical_inventory_sheet = "('','\\904','','','',0,1,0,'Physical Inventory Sheet','\\90423',3551,'0'," . $params['levelid'] . ")";
        break;

      case 'AIMS':
        if ($params['companyid'] == 57) {
        } else {
          $rep_top_performing_item = "('','\\904','','','',0,1,0,'Top Performing Item','\\90424',3556,'0'," . $params['levelid'] . ")";

          if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti, afti usd
            $rep_fastmovingitems = "('','\\904','','','',0,1,0,'Best Seller Report','\\90427',3024,'0'," . $params['levelid'] . ")";
            $rep_item_group_performance_report = "('','\\904','','','',0,1,0,'Item Group Performance Report','\\90405',3707,'0'," . $params['levelid'] . ")";
          }
          if ($params['companyid'] == 24) { //goodfound
            $rep_daily_cement_withdrawal_with_total_form_report = "('','\\904','','','',0,1,0,'Daily Cement Withdrawal With Total Form Report','\\90431',3958,'0'," . $params['levelid'] . ")";
            $rep_daily_cement_withdrawal_without_total_form_report = "('','\\904','','','',0,1,0,'Daily Cement Withdrawal Without Total Form Report','\\90433',4034,'0'," . $params['levelid'] . ")";
            $rep_withdrawal_summary_as_per_cost_center_report = "('','\\904','','','',0,1,0,'Withdrawal Summary As Per Cost Center Report','\\90434',4053,'0'," . $params['levelid'] . ")";
            $rep_fuel_withdrawal_summary_report = "('','\\904','','','',0,1,0,'Fuel Withdrawal Summary Report','\\90435',4054,'0'," . $params['levelid'] . ")";
            $rep_lubricant_consumption_report = "('','\\904','','','',0,1,0,'Lubricant Consumption Report','\\90436',4057,'0'," . $params['levelid'] . ")";
            $rep_withdrawal_summary_report = "('','\\904','','','',0,1,0,'Withdrawal Summary Report','\\90437',4058,'0'," . $params['levelid'] . ")";
            $rep_summary_of_withdrawals_report = "('','\\904','','','',0,1,0,'Summary of Withdrawals Report','\\90438',4059,'0'," . $params['levelid'] . ")";
            $rep_daily_bag_report = "('','\\904','','','',0,1,0,'Daily Bag Report','\\90439',4074,'0'," . $params['levelid'] . ")";
          }
          $rep_salesreportitemhistory = "('','\\904','','','',0,1,0,'Sales Report Item History','\\90430',3811,'0'," . $params['levelid'] . ")";
        }

        break;
    }

    $rep_stocktransfer_summary_peritem = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;

      default:
        switch ($params['companyid']) {
          case 14: // majesty
            $rep_top_performing_item = "('','\\904','','','',0,1,0,'Top Performing Item','\\90424',3556,'0'," . $params['levelid'] . ")";
            $rep_comparative_report_sales = "('','\\904','','','',0,1,0,'Comparative Report Sales Qty VS Qty On Hand','\\904245',3557,'0'," . $params['levelid'] . ")";
            break;
          case 32: //3m
            $rep_sales_item_by_location = "('','\\904','','','',0,1,0,'Sales Item By Location','\\90432',3960,'0'," . $params['levelid'] . ")";
            break;
          case 39: //cbbsi
            $rep_stocktransfer_summary_peritem = "('','\\904','','','',0,1,0,'Stock Transfer Summary Per Item','\\90442',4389,'0'," . $params['levelid'] . ")";
            break;
        }
        break;
    }


    // CUSTOMER
    $rep_unservedsalesorder = "";
    $rep_unservedstockreq = "";
    $rep_sales_order_aftech = "";
    $rep_sales_per_product = "";
    $rep_sales_per_person = "";
    $rep_forecast_report = "";
    $rep_ar_per_collection_officers = "";
    $rep_collection_report = "";
    $rep_detailed_customer_ar = "";
    $monthly_sum_cwt = "";
    $rep_sawt_monitoring_report = "";
    $rep_summary_sales_report = "";
    $rep_sales_report_with_markup = "";
    $rep_monthlysummary_zerorated = "";
    $rep_customer_transaction_history = "";
    $rep_provisional_receipt_report = "";
    $rep_sales_monitoring_breakdown_output_report = "";
    $rep_sales_monitoring_report = "";
    $rep_account_receivables_register = "";
    $rep_water_bill = "";
    $rep_notice_of_disconnection = "";
    $rep_homeowners_list = "";
    $rep_eappsalesreport = "";
    $rep_sales_summary_per_vat_type = "";
    $rep_aging_of_accounts_receivable_report_without_udf = "";
    $rep_aging_of_accounts_receivable_report = "";
    $rep_mj_aging_of_accounts_receivable_report = "";
    $rep_sales_per_plan_type = "";
    $rep_sales_vs_collection = "";
    $rep_ar_vs_collection = "";
    $rep_lah_sales_collection_report = "";
    $rep_paid_accounts_report = "";

    $parent_customers = "";
    $rep_customerlist = "";
    $rep_currentcustomerreceivable = "";
    $rep_currentcustomerreceivableaging = "";

    $rep_analyzecustomersalesmonthly = "";
    $rep_customerperformancereport = "";
    $rep_salespercustomerperitem = "";

    $rep_customersalesreport = "";
    $rep_pendingsalesorders = "";

    $rep_analyzecustomercollectionmonthly = "";
    $rep_monthlysalesreport_graphy = "";
    $rep_salescomparison_graph = "";

    $rep_schedulear = "";
    $rep_pendingsomonitoring = "";


    $rep_collectionsummary = "";
    $rep_collectibles_report = "";
    $rep_salesman_cust_sales_summ = "";
    $rep_accounts_recievable_summ = "";
    $rep_aging_of_overdue_accounts_receivable_report = "";
    //roosevelt
    $rep_bunos_purchase_order_report = "";
    $rep_bunos_purchase_order_application_report = "";
    $rep_customer_account_analysis_report = "";
    $rep_bpo_application_by_customer_report = "";
    $rep_certificate_of_appreciation_report = "";
    $rep_brandsales_report = "";
    $rep_dsalessumm_report = "";
    $rep_brandsalespersize_report = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'FAMS':
      case 'QUEUING':
        break;

      default:
        if ($params['companyid'] == 57) {
          $parent_customers = "('','\\9','','','',0,0,0,'Customers','\\905',3032,'0'," . $params['levelid'] . ")";
          $rep_customerlist = "('','\\905','','','',0,1,0,'Customers List','\\90501',3033,'0'," . $params['levelid'] . ")";
        } else {
          $parent_customers = "('','\\9','','','',0,0,0,'Customers','\\905',3032,'0'," . $params['levelid'] . ")";
          $rep_customerlist = "('','\\905','','','',0,1,0,'Customers List','\\90501',3033,'0'," . $params['levelid'] . ")";
          $rep_currentcustomerreceivable = "('','\\905','','','',0,1,0,'Current Customer Receivables','\\90502',3034,'0'," . $params['levelid'] . ")";
          $rep_currentcustomerreceivableaging = "('','\\905','','','',0,1,0,'Current Customer Receivables Aging','\\90503',3035,'0'," . $params['levelid'] . ")";

          if ($params['companyid'] == 8) { //maxipro
          } else {
            $rep_analyzecustomersalesmonthly = "('','\\905','','','',0,1,0,'Analyze Customer Sales Monthly','\\90504',3036,'0'," . $params['levelid'] . ")";
            $rep_customerperformancereport = "('','\\905','','','',0,1,0,'Customer Performance Report','\\90509',3053,'0'," . $params['levelid'] . ")";
            $rep_salespercustomerperitem = "('','\\905','','','',0,1,0,'Sales Per Customer Per Item','\\90510',3082,'0'," . $params['levelid'] . ")";
          }

          $rep_customersalesreport = "('','\\905','','','',0,1,0,'Customer Sales Report','\\90505',3037,'0'," . $params['levelid'] . ")";
          $rep_pendingsalesorders = "('','\\905','','','',0,1,0,'Pending Sales Orders','\\90506',3038,'0'," . $params['levelid'] . ")";

          if ($params['companyid'] == 39) { //cbbsi
            $rep_unservedsalesorder = "('','\\905','','','',0,1,0,'Unserved Sales Orders','\\90515',4361,'0'," . $params['levelid'] . ")";
            $rep_unservedstockreq = "('','\\905','','','',0,1,0,'Unserved Stock Requests','\\90516',4367,'0'," . $params['levelid'] . ")";
          }

          $rep_analyzecustomercollectionmonthly = "('','\\905','','','',0,1,0,'Analyze Customer Collection Monthly','\\90519',3127,'0'," . $params['levelid'] . ")";
          $rep_monthlysalesreport_graphy = "('','\\905','','','',0,1,0,'Monthly Sales Report Graph','\\90511',3039,'0'," . $params['levelid'] . ")";
          $rep_salescomparison_graph = "('','\\905','','','',0,1,0,'Sales Comparison Graph','\\90512',3086,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 35: //aquamax
              $rep_water_bill = "('','\\905','','','',0,1,0,'Water Bill','\\90537',4142,'0'," . $params['levelid'] . ")";
              $rep_notice_of_disconnection = "('','\\905','','','',0,1,0,'Notice Of Disconnection','\\90538',4171,'0'," . $params['levelid'] . ")";
              $rep_homeowners_list = "('','\\905','','','',0,1,0,'Homeowners List','\\90539',4173,'0'," . $params['levelid'] . ")";
              break;
            case 8: // maxipro
              $rep_collection_report = "('','\\905','','','',0,1,0,'Collection Report','\\90522',3553,'0'," . $params['levelid'] . ")";
              break;
            case 10: //afti
            case 12: //afti usd
              $rep_monthlysummary_zerorated = "('','\\905','','','',0,1,0,'Zero Rated Report','\\90530',3770,'0'," . $params['levelid'] . ")";
              $rep_sales_order_aftech = "('','\\905','','','',0,1,0,'Sales Order Summary','\\90514',3502,'0'," . $params['levelid'] . ")";
              $rep_sales_per_product = "('','\\913','','','',0,1,0,'Sales Report Per Product','\\91305',3507,'0'," . $params['levelid'] . ")"; // change to mancom
              $rep_sales_per_person = "('','\\913','','','',0,1,0,'Detailed Sales Report','\\91306',3506,'0'," . $params['levelid'] . ")"; // change to mancom
              $rep_forecast_report = "('','\\905','','','',0,1,0,'Forecast Report','\\90517',3513,'0'," . $params['levelid'] . ")";
              $rep_ar_per_collection_officers = "('','\\905','','','',0,1,0,'AR Per Collection Officers','\\90518',3549,'0'," . $params['levelid'] . ")";
              $rep_collection_report = "('','\\905','','','',0,1,0,'Collection Report','\\90522',3553,'0'," . $params['levelid'] . ")";
              $rep_detailed_customer_ar = "('','\\905','','','',0,1,0,'Detailed Customer Receivables','\\90523',3559,'0'," . $params['levelid'] . ")";
              $monthly_sum_cwt = "('','\\905','','','',0,1,0,'Monthly Summary of Creditable Withholding Tax','\\90524',3561,'0'," . $params['levelid'] . ")";
              $rep_sawt_monitoring_report = "('','\\905','','','',0,1,0,'SAWT Monitoring Report','\\90525',3583,'0'," . $params['levelid'] . ")";
              $rep_summary_sales_report = "('','\\905','','','',0,1,0,'Summary Sales Report','\\90526',3709,'0'," . $params['levelid'] . ")";
              break;
              // case 21: // kinggeorge
              //   $rep_sales_report_with_markup = "('','\\905','','','',0,1,0,'Sales Report With Markup','\\90529',3755,'0'," . $params['levelid'] . ")";
              //   break;
            case 19: //housegem
              $rep_customer_transaction_history = "('','\\905','','','',0,1,0,'Customer Transaction History','\\90531',3812,'0'," . $params['levelid'] . ")";
              $rep_pendingsomonitoring = "('','\\905','','','',0,1,0,'Pending Sales Order Monitoring','\\90542',5023,'0'," . $params['levelid'] . ")";
              break;
            case 24: // good found cement
              $rep_provisional_receipt_report = "('','\\905','','','',0,1,0,'Provisional Receipt Report','\\90532',3962,'0'," . $params['levelid'] . ")";
              $rep_sales_monitoring_breakdown_output_report = "('','\\905','','','',0,1,0,'Sales Monitoring Breakdown Output Report','\\90533',3963,'0'," . $params['levelid'] . ")";
              $rep_sales_monitoring_report = "('','\\905','','','',0,1,0,'Sales Monitoring Report','\\90534',3964,'0'," . $params['levelid'] . ")";
              break;
            case 26: //bee healthy
              $rep_account_receivables_register = "('','\\905','','','',0,1,0,'Account Receivables Register','\\90535',4028,'0'," . $params['levelid'] . ")";
              break;
            case 34: //evergreen
              $rep_eappsalesreport = "('','\\905','','','',0,1,0,'Eapp Sales Report','\\90540',4366,'0'," . $params['levelid'] . ")";
              break;
            case 40: //CDO
              $rep_mj_aging_of_accounts_receivable_report = "('','\\905','','','',0,1,0,'MJ Aging Of Accounts Receivable Report','\\90545',4769,'0'," . $params['levelid'] . ")";
              break;
          }

          $rep_sales_report_with_markup = "('','\\905','','','',0,1,0,'Sales Report With Markup','\\90529',3755,'0'," . $params['levelid'] . ")";

          if ($this->companysetup->getsystemtype($params) == 'MISPOS') {
            $rep_sales_summary_per_vat_type = "('','\\905','','','',0,1,0,'Sales Summary Per Vat Type','\\90521',3550,'0'," . $params['levelid'] . ")";
          }

          $rep_schedulear = "('','\\905','','','',0,1,0,'Schedule of AR','\\90527',3749,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 39: //cbbsi
              $rep_aging_of_accounts_receivable_report = "('','\\905','','','',0,1,0,'Aging Of Accounts Receivable with UDF Report','\\90528',3750,'0'," . $params['levelid'] . ")";
              $rep_aging_of_accounts_receivable_report_without_udf = "('','\\905','','','',0,1,0,'Aging Of Accounts Receivable Report','\\90542',4435,'0'," . $params['levelid'] . ")";
              break;
            case 36: //rozlab
            case 27: //nte
            case 17: //unihome
              $rep_aging_of_accounts_receivable_report = "('','\\905','','','',0,1,0,'Aging Of Accounts Receivable Report','\\90542',4435,'0'," . $params['levelid'] . ")";
              break;
            case 34: //evergreen
              $rep_sales_per_plan_type = "('','\\905','','','',0,1,0,'Sales Per Plan Type','\\90536',4096,'0'," . $params['levelid'] . ")";
              break;
            case 29: //sbc main
              $rep_ar_vs_collection = "('','\\905','','','',0,1,0,'AR vs Collection','\\90543',4575,'0'," . $params['levelid'] . ")";
              $rep_sales_vs_collection = "('','\\905','','','',0,1,0,'Sales vs Collection','\\90544',4577,'0'," . $params['levelid'] . ")";
              $rep_lah_sales_collection_report = "('','\\905','','','',0,1,0,'LAH Sales Collection Report','\\90557',5565,'0'," . $params['levelid'] . ")";
              $rep_collection_report = "('','\\905','','','',0,1,0,'Collection Report','\\90558',5568,'0'," . $params['levelid'] . ")";
              $rep_paid_accounts_report = "('','\\905','','','',0,1,0,'Paid Accounts Report','\\90559',5569,'0'," . $params['levelid'] . ")";
              break;
            case 55: //afli
              $rep_aging_of_accounts_receivable_report = "('','\\905','','','',0,1,0,'Aging Of Accounts Receivable Report','\\90542',4435,'0'," . $params['levelid'] . ")";
              $rep_collection_report = "('','\\905','','','',0,1,0,'Collection Report','\\90522',3553,'0'," . $params['levelid'] . ")";
              $rep_collectibles_report = "('','\\905','','','',0,1,0,'Collectibles Report','\\90523',5371,'0'," . $params['levelid'] . ")";
              //$rep_collectionsummary = "('','\\905','','','',0,1,0,'Collection Summary','\\90528',5214,'0'," . $params['levelid'] . ")";
              break;
            case 59: //roosevelt
              $rep_salesman_cust_sales_summ = "('','\\905','','','',0,1,0,'Salesman Customer Sales Summary','\\90546',5438,'0'," . $params['levelid'] . ")";
              $rep_accounts_recievable_summ = "('','\\905','','','',0,1,0,'Accounts Receivable Reports','\\90547',5439,'0'," . $params['levelid'] . ")";
              $rep_aging_of_accounts_receivable_report = "('','\\905','','','',0,1,0,'Aging Of Accounts Receivable Report','\\90542',4435,'0'," . $params['levelid'] . ")";
              $rep_aging_of_overdue_accounts_receivable_report = "('','\\905','','','',0,1,0,'Aging of Over Due Accounts Receivable','\\90548',5440,'0'," . $params['levelid'] . ")";
              $rep_bunos_purchase_order_report = "('','\\905','','','',0,1,0,'Bonus Purchase Order Report','\\90549',5441,'0'," . $params['levelid'] . ")";
              $rep_bunos_purchase_order_application_report = "('','\\905','','','',0,1,0,'BPO Application','\\90550',5442,'0'," . $params['levelid'] . ")";

              $rep_customer_account_analysis_report = "('','\\905','','','',0,1,0,'Customer Account Analysis Report','\\90551',5443,'0'," . $params['levelid'] . ")";
              $rep_bpo_application_by_customer_report = "('','\\905','','','',0,1,0,'BPO Application by Customer','\\90552',5444,'0'," . $params['levelid'] . ")";
              $rep_certificate_of_appreciation_report = "('','\\905','','','',0,1,0,'Certificate of Appreciation Report','\\90553',5445,'0'," . $params['levelid'] . ")";
              $rep_brandsales_report = "('','\\905','','','',0,1,0,'Brand Sales Summary','\\90554',5446,'0'," . $params['levelid'] . ")";

              $rep_dsalessumm_report = "('','\\905','','','',0,1,0,'Daily Sales Summary','\\90555',5448,'0'," . $params['levelid'] . ")";
              $rep_brandsalespersize_report = "('','\\905','','','',0,1,0,'Brand Sales Summary per Size','\\90556',5449,'0'," . $params['levelid'] . ")";
              break;
          }
        }


        break;
    }

    // SUPPLIER
    $parent_supplier = "";
    $rep_supplierlist = "";
    $rep_item_received_per_supplier = $rep_rental_payment = "";
    $rep_po_vs_rr_serving_ratio = "";
    $rep_currentsupplierpayables = "";
    $rep_currentsupplierpayablesaging = "";
    $rep_analyzedsupplierpurchasesmonthly = "";
    $rep_supplierpurchasereport = "";
    $rep_pendingpurchaseorders = "";
    $rep_supplierperformancereport = "";
    $rep_purchasepersupp = "";
    $rep_monthlypurchasesreport_graph = "";
    $rep_purchasescomparison_graph = "";
    $rep_withholdingtax = "";
    $rep_cert_of_creditable_tax = "";


    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;
      case 'FAMS':
        if ($this->companysetup->getispurchases($params)) {
          $parent_supplier = "('','\\9','','','',0,0,0,'Supplier','\\906',3040,'0'," . $params['levelid'] . ")";
          $rep_supplierlist = "('','\\906','','','',0,1,0,'Supplier List','\\90601',3041,'0'," . $params['levelid'] . ")";
          $rep_currentsupplierpayables = "";
          $rep_currentsupplierpayablesaging = "";
          $rep_analyzedsupplierpurchasesmonthly = "('','\\906','','','',0,1,0,'Analyzed Supplier Purchases Monthly','\\90604',3044,'0'," . $params['levelid'] . ")";
          $rep_supplierpurchasereport = "('','\\906','','','',0,1,0,'Supplier Purchase Report','\\90605',3045,'0'," . $params['levelid'] . ")";
          $rep_pendingpurchaseorders = "('','\\906','','','',0,1,0,'Pending Purchase Orders','\\90606',3046,'0'," . $params['levelid'] . ")";
          $rep_supplierperformancereport = "('','\\906','','','',0,1,0,'Supplier Performance Report','\\90609',3054,'0'," . $params['levelid'] . ")";
          $rep_purchasepersupp = "('','\\906','','','',0,1,0,'Purchase Per Supplier','\\90612',3093,'0'," . $params['levelid'] . ")";
          $rep_monthlypurchasesreport_graph = "('','\\906','','','',0,1,0,'Monthly Purchases Report Graph','\\90607',3399,'0'," . $params['levelid'] . ")";
          $rep_purchasescomparison_graph = "('','\\906','','','',0,1,0,'Purchases Comparison Graph','\\90608',3398,'0'," . $params['levelid'] . ")";
        }
        break;
      default:
        if ($params['companyid'] == 57) {
        } else {
          $parent_supplier = "('','\\9','','','',0,0,0,'Supplier','\\906',3040,'0'," . $params['levelid'] . ")";
          $rep_supplierlist = "('','\\906','','','',0,1,0,'Supplier List','\\90601',3041,'0'," . $params['levelid'] . ")";
          $rep_currentsupplierpayables = "('','\\906','','','',0,1,0,'Current Supplier Payables','\\90602',3042,'0'," . $params['levelid'] . ")";
          $rep_currentsupplierpayablesaging = "('','\\906','','','',0,1,0,'Current Supplier Payables Aging','\\90603',3043,'0'," . $params['levelid'] . ")";
          $rep_analyzedsupplierpurchasesmonthly = "('','\\906','','','',0,1,0,'Analyzed Supplier Purchases Monthly','\\90604',3044,'0'," . $params['levelid'] . ")";
          $rep_supplierpurchasereport = "('','\\906','','','',0,1,0,'Supplier Purchase Report','\\90605',3045,'0'," . $params['levelid'] . ")";
          $rep_pendingpurchaseorders = "('','\\906','','','',0,1,0,'Pending Purchase Orders','\\90606',3046,'0'," . $params['levelid'] . ")";
          $rep_supplierperformancereport = "('','\\906','','','',0,1,0,'Supplier Performance Report','\\90609',3054,'0'," . $params['levelid'] . ")";
          $rep_purchasepersupp = "('','\\906','','','',0,1,0,'Purchase Per Supplier','\\90612',3093,'0'," . $params['levelid'] . ")";
          $rep_monthlypurchasesreport_graph = "('','\\906','','','',0,1,0,'Monthly Purchases Report Graph','\\90607',3399,'0'," . $params['levelid'] . ")";
          $rep_purchasescomparison_graph = "('','\\906','','','',0,1,0,'Purchases Comparison Graph','\\90608',3398,'0'," . $params['levelid'] . ")";
          $rep_withholdingtax = "('','\\906','','','',0,1,0,'Withholding Tax Report','\\90611',3400,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
              $rep_item_received_per_supplier = "('','\\906','','','',0,1,0,'Items Received Per Supplier Invoice','\\90616',3558,'0'," . $params['levelid'] . ")";
              break;
            case 26: //bee healthy
              $rep_rental_payment = "('','\\906','','','',0,1,0,'Rental Payment','\\90617',3866,'0'," . $params['levelid'] . ")";
              break;
            case 40: //cdo
              $rep_po_vs_rr_serving_ratio = "('','\\906','','','',0,1,0,'PO vs RR Serving Ratio','\\90618',4668,'0'," . $params['levelid'] . ")";
              break;
            case 56: //homeworks
              $rep_cert_of_creditable_tax = "('','\\906','','','',0,1,0,'Certificate of Creditable Tax','\\90619',5373,'0'," . $params['levelid'] . ")";
              break;
          }
        }

        break;
    }

    if ($this->companysetup->iswaterbilling($params)) {
      $parent_supplier = "";
      $rep_supplierlist = "";
      $rep_currentsupplierpayables = "";
      $rep_currentsupplierpayablesaging = "";
      $rep_analyzedsupplierpurchasesmonthly = "";
      $rep_supplierpurchasereport = "";
      $rep_pendingpurchaseorders = "";
      $rep_supplierperformancereport = "";
      $rep_purchasepersupp = "";
      $rep_monthlypurchasesreport_graph = "";
      $rep_purchasescomparison_graph = "";
      $rep_withholdingtax = "";
    }

    // SALES AGENT
    $rep_sales_per_agent_per_item = '';
    $rep_sales_per_agent = '';
    $rep_target_vs_actual_sales_report  = '';
    $rep_top_performing_sales_agent = "";

    $parent_salesagent = "";
    $rep_salesagentlist = "";
    $rep_analyzedagentsalesmonthly = "";
    $rep_top_performing_sales_agent = "";
    $rep_sales_per_agent_per_item = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'FAMS':
      case 'QUEUING':
        break;
      default:
        if ($params['companyid'] == 57) {
        } else {
          $parent_salesagent = "('','\\9','','','',0,0,0,'Sales Agent','\\907',3047,'0'," . $params['levelid'] . ")";
          $rep_salesagentlist = "('','\\907','','','',0,1,0,'Sales Agent List','\\90701',3048,'0'," . $params['levelid'] . ")";
          $rep_analyzedagentsalesmonthly = "('','\\907','','','',0,1,0,'Analyzed Agent Sales Monthly','\\90702',3049,'0'," . $params['levelid'] . ")";
          $rep_top_performing_sales_agent = "('','\\907','','','',0,1,0,'Top Performing Sales Agent','\\90705',3754,'0'," . $params['levelid'] . ")";
          $rep_sales_per_agent_per_item = "('','\\907','','','',0,1,0,'Sales Per Agent Per Item','\\90703',3552,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 32: //3m
              $rep_target_vs_actual_sales_report = "('','\\907','','','',0,1,0,'Target vs Actual Sales Report','\\90708',3961,'0'," . $params['levelid'] . ")";
              break;
            default:
              $rep_sales_per_agent = "('','\\907','','','',0,1,0,'Sales Per Agent','\\90704',3806,'0'," . $params['levelid'] . ")";
              break;
          } // end switch
        }

        break;
    }

    // OTHER REPORTS
    $rep_cashadvance = "";
    $power_consumption_daily_report = "";
    $gatepass_out = $gatepass_return = $asset_depreciation = $asset_location = "";
    $asset_receiving = $asset_issuance = "";
    $asset_accountability_report = "";
    $cost_adjustment_report = "";
    $outsource_summary_report = "";
    $rep_summary_of_invoices_report = "";
    $rep_statement_of_account_water_bill = '';
    $rep_soa_afti = "";
    $rep_unncollected_creditable_withholding_tax = "";
    $rep_detailed_quantity_sold = "";
    $rep_total_consumption_per_supplier = "";
    $rep_total_imported_goods = '';
    $rep_monthly_supplier_consumption_per_agent = "";

    $rep_sales_report = "";
    $rep_daily_sales_report = "";

    $sales_and_collection_remittance_report = "";
    $sales_transmittal_report = "";
    $lotavailabilityreport = "";

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
    $rep_approvedcanvass = "";
    $rep_generateassettag = "";
    $rep_paymentreleased = "";
    $rep_advancesmonitoring = "";
    $data_requirement_report = "";

    $parent_otherreports = "";
    $login_attempt_report = "";
    $rep_mc_unit_statement_of_account = "";

    $rep_summary_of_transaction_report = "";
    $uncleared_reports = "";

    $rep_outofstock_sj_pos = "";

    $rep_statementofaccount = '';
    $rep_expensesreport = '';
    $rep_receivingconsignmentreport = '';
    $user_access_report = '';
    $login_attempt_report = '';
    $data_requirement_report = '';

    $inhouse_sales_report = '';
    $homeworks_sales_report = '';
    $summary_vendor_report = '';
    $homeworks_for_sir_jon_report = '';
    $rep_supplier_price = '';
    $rep_comparative_total_loan_releases = '';

    $rep_doc_series_sales_journal_report = '';
    $rep_daily_sales_collection_report = '';
    $rep_requestorder_managementrep = '';
    $rep_so_and_ro_monitoring = '';
    $rep_inventorybal_with_pendingsalesorder = '';
    $rep_daily_sales_of_hgc = '';

    $rep_customer_registration_report = '';
    $rep_reseller_status_summary_report = '';
    $rep_dailtytask_report = '';
    $rep_task_monitoring_report = '';


    if ($this->companysetup->getispos($params)) {
      $rep_outofstock_sj_pos = "('','\\908','','','',0,1,0,'Out of Stock Sales Journal POS','\\90823',4757,'0'," . $params['levelid'] . ")";
    }

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;
      case 'FAMS':
        $parent_otherreports = "('','\\9','','','',0,0,0,'Other Reports','\\908',3050,'0'," . $params['levelid'] . ")";
        $gatepass_out = "('','\\908','','','',0,1,0,'Gatepass Out','\\90806',3535,'0'," . $params['levelid'] . ")";
        $gatepass_return = "('','\\908','','','',0,1,0,'Gatepass Return','\\90808',3536,'0'," . $params['levelid'] . ")";
        $asset_depreciation = "('','\\908','','','',0,1,0,'Asset Depreciation','\\90809',3541,'0'," . $params['levelid'] . ")";
        $asset_location = "('','\\908','','','',0,1,0,'Asset Location','\\908010',3542,'0'," . $params['levelid'] . ")";
        $asset_receiving = "('','\\908','','','',0,1,0,'Asset Receiving','\\908011',3543,'0'," . $params['levelid'] . ")";
        $asset_issuance = "('','\\908','','','',0,1,0,'Asset Issuance','\\908012',3544,'0'," . $params['levelid'] . ")";
        $asset_accountability_report = "('','\\908','','','',0,1,0,'Asset Accountability Report','\\908016',4703,'0'," . $params['levelid'] . ")";
        break;
      default:
        if ($params['companyid'] == 57) {
        } else {
          $parent_otherreports = "('','\\9','','','',0,0,0,'Other Reports','\\908',3050,'0'," . $params['levelid'] . ")";
          $rep_statementofaccount = "('','\\908','','','',0,1,0,'Statement of Account','\\90801',3051,'0'," . $params['levelid'] . ")";
          $rep_expensesreport = "('','\\908','','','',0,1,0,'Expenses Report','\\90804',3052,'0'," . $params['levelid'] . ")";
          $rep_receivingconsignmentreport = "('','\\906','','','',0,1,0,'Receiving Consignment Report','\\90610',3055,'0'," . $params['levelid'] . ")";
          $user_access_report = "('','\\908','','','',0,1,0,'User Access Report','\\90805',3100,'0'," . $params['levelid'] . ")";
          $login_attempt_report = "('','\\908','','','',0,1,0,'Login Attempt Report','\\90819',4005,'0'," . $params['levelid'] . ")";
          $data_requirement_report = "('','\\908','','','',0,1,0,'Data Requirement Report','\\90448',4727,'0'," . $params['levelid'] . ")";

          if ($this->companysetup->getsystemtype($params) == 'FAMS' || $this->companysetup->getsystemtype($params) == 'ATI') {
            $gatepass_out = "('','\\908','','','',0,1,0,'Gatepass Out','\\90806',3535,'0'," . $params['levelid'] . ")";
            $gatepass_return = "('','\\908','','','',0,1,0,'Gatepass Return','\\90808',3536,'0'," . $params['levelid'] . ")";
            $asset_depreciation = "('','\\908','','','',0,1,0,'Asset Depreciation','\\90809',3541,'0'," . $params['levelid'] . ")";
            $asset_location = "('','\\908','','','',0,1,0,'Asset Location','\\908010',3542,'0'," . $params['levelid'] . ")";
            $asset_receiving = "('','\\908','','','',0,1,0,'Asset Receiving','\\908011',3543,'0'," . $params['levelid'] . ")";
            $asset_issuance = "('','\\908','','','',0,1,0,'Asset Issuance','\\908012',3544,'0'," . $params['levelid'] . ")";
            $asset_accountability_report = "('','\\908','','','',0,1,0,'Asset Accountability Report','\\908016',4703,'0'," . $params['levelid'] . ")";
          }

          if ($this->companysetup->getsystemtype($params) == 'AIMS') {
            $cost_adjustment_report = "('','\\908','','','',0,1,0,'Cost Adjustment Report','\\908013',3647,'0'," . $params['levelid'] . ")";
            $outsource_summary_report = "('','\\908','','','',0,1,0,'Outsource Summary Report','\\908014',3686,'0'," . $params['levelid'] . ")";
            $rep_summary_of_invoices_report = "('','\\908','','','',0,1,0,'Summary of Invoices Report','\\90817',3752,'0'," . $params['levelid'] . ")";
            if ($params['companyid'] == 40) { // cdo
              $rep_summary_of_transaction_report = "('','\\908','','','',0,1,0,'Summary of Transaction Report','\\90824',4878,'0'," . $params['levelid'] . ")";
            }
            if ($params['companyid'] == 19) { // houssegem
              $rep_requestorder_managementrep = "('','\\908','','','',0,1,0,'Request Order Management Report','\\90833',5414,'0'," . $params['levelid'] . ")";
              $rep_so_and_ro_monitoring = "('','\\908','','','',0,1,0,'SO and RO Monitoring Daily Update','\\90834',5417,'0'," . $params['levelid'] . ")";
              $rep_inventorybal_with_pendingsalesorder = "('','\\908','','','',0,1,0,'Inventory Balance with Pending Sales Order','\\90835',5418,'0'," . $params['levelid'] . ")";
              $rep_daily_sales_of_hgc = "('','\\908','','','',0,1,0,'Daily Sales','\\90836',5419,'0'," . $params['levelid'] . ")";
            }
          }

          if ($this->companysetup->getsystemtype($params) == 'REALESTATE') {
            $lotavailabilityreport = "('','\\908','','','',0,1,0,'Lot Availability Report','\\908017',4723,'0'," . $params['levelid'] . ")";
          }

          if ($this->companysetup->iswaterbilling($params)) {
            $rep_statement_of_account_water_bill = "('','\\908','','','',0,1,0,'Statement Of Account Water Bill','\\90820',4154,'0'," . $params['levelid'] . ")";;
          }

          switch ($params['companyid']) {
            case 39: //cbbsi
              $sales_and_collection_remittance_report = "('','\\908','','','',0,1,0,'Sales And Collection Remittance Report','\\90821',4362,'0'," . $params['levelid'] . ")";
              $sales_transmittal_report = "('','\\908','','','',0,1,0,'Sales Transmittal Report','\\90822',4363,'0'," . $params['levelid'] . ")";
              break;
            case 35: //aquamax
              $rep_sales_report = "";
              $rep_daily_sales_report = "";
              $rep_soa_afti = "";
              $rep_unncollected_creditable_withholding_tax = "";

              break;
            case 1: //vitaline
            case 23: //labsol cebu
            case 41: //labsol manila
            case 52: //technolab
              $rep_daily_sales_report = "('','\\908','','','',0,1,0,'Daily Sales Report','\\90815',3408,'0'," . $params['levelid'] . ")";
              $rep_sales_report = "('','\\908','','','',0,1,0,'Sales Report','\\90807',3410,'0'," . $params['levelid'] . ")";
              $rep_detailed_quantity_sold = "('','\\908','','','',0,1,0,'Detailed Quantity Sold','\\90811',3853,'0'," . $params['levelid'] . ")";
              $rep_total_consumption_per_supplier = "('','\\908','','','',0,1,0,'Total Consumption Per Supplier','\\90812',3854,'0'," . $params['levelid'] . ")";
              $rep_total_imported_goods = "('','\\908','','','',0,1,0,'Total Imported Goods','\\90813',3855,'0'," . $params['levelid'] . ")";
              $rep_monthly_supplier_consumption_per_agent = "('','\\908','','','',0,1,0,'Monthly Supplier Consumption Per Agent','\\90814',3856,'0'," . $params['levelid'] . ")";
              break;

            case 10: // afti
              $rep_soa_afti = "('','\\908','','','',0,1,0,'Statement of Account Email','\\90810',3512,'0'," . $params['levelid'] . ")";
              $rep_sales_report = "";
              $rep_daily_sales_report = "";
              $rep_cashadvance = "('','\\908','','','',0,1,0,'Cash Advance Report','\\90816',3585,'0'," . $params['levelid'] . ")";
              $rep_unncollected_creditable_withholding_tax = "('','\\908','','','',0,1,0,'Uncollected Creditable Withholding Tax','\\90818',3753,'0'," . $params['levelid'] . ")";
              break;

            case 17: //unihome 
              $uncleared_reports = "('','\\908','','','',0,1,0,'Uncleared Report','\\90825',4900,'0'," . $params['levelid'] . ")";
              break;

            case 40: // CDO
              $rep_mc_unit_statement_of_account = "('','\\908','','','',0,1,0,'MC Unit Statement Of Account','\\90823',4757,'0'," . $params['levelid'] . ")";
              break;
            case 36: // rozlab
              $uncleared_reports = "('','\\908','','','',0,1,0,'Uncleared Report','\\90825',4900,'0'," . $params['levelid'] . ")";
              break;
            case 56: //homeworks
              $inhouse_sales_report = "('','\\908','','','',0,1,0,'Inhouse Sales Report','\\90826',5026,'0'," . $params['levelid'] . ")";
              $homeworks_sales_report = "('','\\908','','','',0,1,0,'Homeworks Sales Report','\\90827',5027,'0'," . $params['levelid'] . ")";
              $summary_vendor_report = "('','\\908','','','',0,1,0,'Summary Vendor Report','\\90828',5119,'0'," . $params['levelid'] . ")";
              $homeworks_for_sir_jon_report = "('','\\908','','','',0,1,0,'Homeworks For Sir Jon Report','\\90829',5120,'0'," . $params['levelid'] . ")";

              break;
            case 24: //goodfound
              $power_consumption_daily_report = "('','\\908','','','',0,1,0,'Power Consumption Daily Report','\\908015',4091,'0'," . $params['levelid'] . ")";
              break;
            case 19: //housegem
              $rep_supplier_price = "('','\\906','','','',0,1,0,'Supplier Price Per Item','\\90611',5160,'0'," . $params['levelid'] . ")";
              break;
            case 55: //afli
              $rep_comparative_total_loan_releases = "('','\\908','','','',0,1,0,'Comparative Total Loan Releases','\\90830',5238,'0'," . $params['levelid'] . ")";
              break;
            case 60: //transpower
              $rep_doc_series_sales_journal_report = "('','\\908','','','',0,1,0,'Document Series Sales Journal Report','\\90831',5360,'0'," . $params['levelid'] . ")";
              $rep_daily_sales_collection_report = "('','\\908','','','',0,1,0,'Daily Sales Collection Report','\\90832',5361,'0'," . $params['levelid'] . ")";
              break;
            case 29: //sbc
              $rep_reseller_status_summary_report = "('','\\908','','','',0,1,0,'Reseller Status Summary Report','\\90838',5566,'0'," . $params['levelid'] . ")";
              $rep_customer_registration_report = "('','\\908','','','',0,1,0,'Customer Registration Report','\\90837',5556,'0'," . $params['levelid'] . ")";
              $rep_dailytask_report = "('','\\908','','','',0,1,0,'DailyTask Report','\\90840',5586,'0'," . $params['levelid'] . ")";
              $rep_task_monitoring_report = "('','\\908','','','',0,1,0,'Task Monitoring Report','\\90839',5585,'0'," . $params['levelid'] . ")";
              break;
          }
        }

        break;
    }


    // PURCHASING REPORTS
    $rep_purchaseOrderAssignedPO = "";
    $rep_purchasemergingmodulereport = "";
    $rep_osi = "";
    $rep_ocr = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;

      default:
        if ($params['companyid'] == 16) { //ATI
          $parent_purchasingreports = "('','\\9','','','',0,0,0,'Purchasing Reports','\\9099',4145,'0'," . $params['levelid'] . ")";
          $rep_canvasssheet = "('','\\9099','','','',0,1,0,'Create Canvass Sheet','\\9099001',4146,'0'," . $params['levelid'] . ")";
          $rep_oraclecoderequest = "('','\\9099','','','',0,1,0,'Create Oracle Code Request','\\9099002',4147,'0'," . $params['levelid'] . ")";
          $rep_purchaseorderdraft = "('','\\9099','','','',0,1,0,'Create Purchase Order Draft','\\9099003',4148,'0'," . $params['levelid'] . ")";
          $rep_poforapproval = "('','\\9099','','','',0,1,0,'PO For Approval','\\9099004',4149,'0'," . $params['levelid'] . ")";
          $rep_createreceivingrep = "('','\\9099','','','',0,1,0,'Create Receiving Report','\\9099005',4150,'0'," . $params['levelid'] . ")";
          $rep_createpurchasereturn = "('','\\9099','','','',0,1,0,'Create Purchase Return','\\9099006',4151,'0'," . $params['levelid'] . ")";
          $rep_createcheckvoucher = "('','\\9099','','','',0,1,0,'Create Cash Check Voucher','\\9099007',4152,'0'," . $params['levelid'] . ")";
          $rep_createstockissuance = "('','\\9099','','','',0,1,0,'Create Stock Issuance','\\9099008',4153,'0'," . $params['levelid'] . ")";
          $rep_forsomonitoring = "('','\\9099','','','',0,1,0,'For SO Monitoring','\\9099009',4191,'0'," . $params['levelid'] . ")";
          $rep_approvedcanvass = "('','\\9099','','','',0,1,0,'Approved Canvass','\\9099010',4193,'0'," . $params['levelid'] . ")";
          $rep_generateassettag = "('','\\9099','','','',0,1,0,'Generate Asset Tag','\\9099011',4194,'0'," . $params['levelid'] . ")";
          $rep_paymentreleased = "('','\\9099','','','',0,1,0,'Payment Released','\\9099012',4477,'0'," . $params['levelid'] . ")";
          $rep_advancesmonitoring = "('','\\9099','','','',0,1,0,'Advances Monitoring','\\9099013',4485,'0'," . $params['levelid'] . ")";
        }
        break;
    }


    // TRANSACTION LIST
    $subparent_purchases = "";
    $rep_purchaserequisitionreport = "";
    $rep_purchaseorderreport = "";
    $rep_receivingreport = "";
    $rep_purchasereturnreport = "";
    $rep_canvass_sheet =

      $rep_servicereceivingreport = "";
    $rep_joborderreport = "";
    $rep_jobcompletionreport = "";

    $rep_outsourcereport = "";
    $rep_outsource_per_RFQ_report = "";
    $rep_canvass_sheet = "";
    $rep_sparepartsissuance = "";
    $rep_drreturnreport = "";
    $rep_deliveryreceiptreport = "";

    $subparent_sales = '';
    $rep_salesjournalreport = '';
    $rep_salesreturnreport = '';
    $rep_consign_and_outright_invoice = '';

    $rep_quotation = "";
    $rep_salesorderafti = "";
    $rep_salesorderreport = "";
    $rep_voidsalesorderreport = "";

    $rep_stockissuanceaftireport = "";
    $rep_requestforreplacementreturn = "";

    $rep_monthlysummary_outputtax = "";
    $rep_operatorhistory = "";
    $rep_operatorincentive = "";

    $rep_sjseriesreport = "";
    $rep_salesreportdetail = "";
    $rep_salesreportsummary = "";

    $rep_salesactivityreport = "";
    $rep_servicesalesorderreport = "";
    $rep_taskerrandreport = "";

    //Goodfound cement
    $subparent_production = "";

    $rep_material_issuance_report_list = "";
    $rep_supplies_issuance_report_list = "";
    $rep_finish_good_report_list = "";

    $rep_prod_joborderreport = "";
    $rep_prodinput_report = "";

    $rep_encashmentreport = "";
    $rep_onlineencashmentreport = "";
    $rep_mccollection = "";

    $rep_petty_cash_reconciliation = $rep_material_issuance_report = $rep_purchase_detailed = $rep_sched_fifo = $rep_inv_movement =
      $rep_items_negative_bal = $rep_quantity_on_hand = $rep_unserved_po = $rep_non_moving_items = $inv_checksheet = $price_list =
      $jobrequeststatus = $listcrewpervessel = $listvoyage = $listwarehousevessels = $subparent_voyage = $rep_voyagereport =
      $rep_listcrewpervessel = $rep_listvoyagereportpervessel = $rep_listwarehousevessels = $rep_supplierinvoicereport = "";
    $rep_material_request_report = "";

    $subparent_crm = "";

    $rep_Lead = "";
    $rep_opportunity_module = "";
    $rep_physical_count_sheet_per_batch_report = "";

    $subparent_pcf = "";
    $rep_pcf_summary = "";

    $subparent_operation = "";
    $rep_application_list = "";
    $rep_lifeplan_agrement_list = "";
    $monthly_sum_ewt = "";
    $rep_request_order_report = "";

    $subparent_inventory = "";
    $rep_inventorysetupreport = "";
    $rep_physicalcountreport = "";
    $rep_transferslipreport = "";
    $rep_inventoryadjustmentreport = "";

    $subparent_payables = "";
    $rep_apsetupreport = "";
    $rep_apvoucherreport = "";
    $rep_cashcheckvoucherreport = "";


    $subparent_receivables = "";
    $rep_arsetupreport = "";
    $rep_receivedpaymentreport = "";
    $rep_counterreceiptreport = "";

    $subparent_accounting = "";
    $rep_generaljournalreport = "";
    $rep_depositslipreport = "";

    $rep_request_order_report = "";

    $rep_debit_memo_sum = "";
    $rep_credit_memo_sum = "";

    $subparent_issuance = "";
    $rep_stockrequestreport = "";
    $rep_stocktransferreport = "";
    $rep_stockissuancereport = "";
    $rep_stockreturnreport = "";


    $monthly_sum_ewt = "";
    $rep_withholdingtax = "";
    $subparent_customer_support = "";
    $rep_ticket = "";


    //AFLI
    $subparent_lending = "";
    $rep_application_form = "";
    $rep_loan_approval = "";
    $rep_loan_summary = "";
    //cashier
    $subparent_cashier = "";
    $rep_cashier_entry_module = "";
    $rep_petty_cash_module = "";
    $rep_treceivedchecks = "";
    $rep_dx_depositslip = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;

      case 'FAMS':
        $parent_transactionlist = "('','\\9','','','',0,0,0,'Transaction List','\\909',3056,'0'," . $params['levelid'] . ")";
        if ($this->companysetup->getispurchases($params)) {
          $subparent_purchases = "('','\\909','','','',0,0,0,'Purchases','\\90901',3057,'0'," . $params['levelid'] . ")";
          $rep_purchaserequisitionreport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Requisition Report','\\9090101',3061,'0'," . $params['levelid'] . ")";
          $rep_purchaseorderreport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Order Report','\\9090102',3060,'0'," . $params['levelid'] . ")";
          $rep_receivingreport = "('','\\90901','','\\\\909','',0,1,0,'Receiving Report','\\9090103',3058,'0'," . $params['levelid'] . ")";
          $rep_purchasereturnreport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Return Report','\\9090104',3059,'0'," . $params['levelid'] . ")";
          $rep_canvass_sheet = "('','\\90901','','\\\\909','',0,1,0,'Canvass Sheet Report','\\9090106',3481,'0'," . $params['levelid'] . ")";
        }
        break;

      default:
        if ($params['companyid'] == 57) { //cdo financing
          $parent_transactionlist = "('','\\9','','','',0,0,0,'Transaction List','\\909',3056,'0'," . $params['levelid'] . ")";
          $subparent_cashier = "('','\\909','','','',0,0,0,'Cashier','\\90916',5186,'0'," . $params['levelid'] . ")";
          $rep_cashier_entry_module = "('','\\90916','','\\\\909','',0,1,0,'Cashier Entry','\\9091601',5187,'0'," . $params['levelid'] . ")";
          $rep_petty_cash_module = "('','\\90916','','\\\\909','',0,1,0,'Petty Cash','\\9091602',5188,'0'," . $params['levelid'] . ")";
          $rep_treceivedchecks = "('','\\90916','','\\\\909','',0,1,0,'Received Checks','\\9090505',5185,'0'," . $params['levelid'] . ")";
          $rep_dx_depositslip = "('','\\90916','','\\\\909','',0,1,0,'Deposit Slips','\\9090506',5196,'0'," . $params['levelid'] . ")";
        } else {
          $parent_transactionlist = "('','\\9','','','',0,0,0,'Transaction List','\\909',3056,'0'," . $params['levelid'] . ")";
          $subparent_purchases = "('','\\909','','','',0,0,0,'Purchases','\\90901',3057,'0'," . $params['levelid'] . ")";
          $rep_purchaserequisitionreport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Requisition Report','\\9090101',3061,'0'," . $params['levelid'] . ")";
          $rep_purchaseorderreport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Order Report','\\9090102',3060,'0'," . $params['levelid'] . ")";
          $rep_receivingreport = "('','\\90901','','\\\\909','',0,1,0,'Receiving Report','\\9090103',3058,'0'," . $params['levelid'] . ")";
          $rep_purchasereturnreport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Return Report','\\9090104',3059,'0'," . $params['levelid'] . ")";

          if ($params['companyid'] == 16) { //ATI
            $rep_purchaseOrderAssignedPO = "('','\\90901','','\\\\909','',0,1,0,'Purchase Order Assigned PO Report','\\9090116',4167,'0'," . $params['levelid'] . ")";
            $rep_purchasemergingmodulereport = "('','\\90901','','\\\\909','',0,1,0,'Purchase Merging Module Report','\\9090118',4478,'0'," . $params['levelid'] . ")";
            $rep_ocr = "('','\\90901','','\\\\909','',0,1,0,'Oracle Code Request Report','\\9090119',4705,'0'," . $params['levelid'] . ")";
            $rep_osi = "('','\\90901','','\\\\909','',0,1,0,'OSI Report','\\9090120',4704,'0'," . $params['levelid'] . ")";
          }

          $rep_packinglistreport = "('','\\90901','','\\\\909','',0,1,0,'Packing List Report','\\9090107',3495,'0'," . $params['levelid'] . ")";
          $rep_packinglistreceivingreport = "('','\\90901','','\\\\909','',0,1,0,'Packing List Receiving Report','\\9090108',3496,'0'," . $params['levelid'] . ")";
          $rep_warrantyrequestreport = "('','\\90901','','\\\\909','',0,1,0,'Warranty Request Report','\\9090109',3497,'0'," . $params['levelid'] . ")";
          $rep_warrantyreceivingreport = "('','\\90901','','\\\\909','',0,1,0,'Warranty Receiving Report','\\9090110',3498,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
              $rep_servicereceivingreport = "('','\\90901','','\\\\909','',0,1,0,'Service Receiving Report','\\9090111',3503,'0'," . $params['levelid'] . ")";
              $rep_joborderreport = "('','\\90901','','\\\\909','',0,1,0,'Job Order Report','\\9090112',3504,'0'," . $params['levelid'] . ")";
              $rep_jobcompletionreport = "('','\\90901','','\\\\909','',0,1,0,'Job Completion Report','\\9090113',3505,'0'," . $params['levelid'] . ")";
              $rep_outsourcereport = "('','\\90901','','\\\\909','',0,1,0,'Outsource Report','\\9090114',3529 ,'0'," . $params['levelid'] . ")";
              $rep_outsource_per_RFQ_report = "('','\\90901','','\\\\909','',0,1,0,'Outsource Per RFQ Report','\\9090115',3708 ,'0'," . $params['levelid'] . ")";
              break;
          }

          switch ($params['companyid']) {
            case 16: //ati
            case 10: //afti
            case 12: //afti usd
            case 3: //conti
              $rep_canvass_sheet = "('','\\90901','','\\\\909','',0,1,0,'Canvass Sheet Report','\\9090106',3481,'0'," . $params['levelid'] . ")";
              break;
          }

          $subparent_sales = "('','\\909','','','',0,0,0,'Sales','\\90902',3062,'0'," . $params['levelid'] . ")";
          $rep_salesjournalreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Journal Report','\\9090202',3064,'0'," . $params['levelid'] . ")";
          $rep_salesreturnreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Return Report','\\9090203',3065,'0'," . $params['levelid'] . ")";
          if ($params['companyid'] == 63) { //ericco
            $rep_consign_and_outright_invoice = "('','\\90902','','\\\\909','',0,1,0,'Consign and Outright Invoice Report','\\9090224',5587,'0'," . $params['levelid'] . ")";
          }
          if ($params['companyid'] == 40) { //cdo
            $rep_sparepartsissuance = "('','\\90902','','\\\\909','',0,1,0,'Spare Parts Issuance','\\9090223',4631,'0'," . $params['levelid'] . ")";
          }

          if ($params['companyid'] == 39) { //cbbsi
            $rep_drreturnreport = "('','\\90902','','\\\\909','',0,1,0,'DR Return Report','\\9090220',4364,'0'," . $params['levelid'] . ")";
            $rep_deliveryreceiptreport = "('','\\90902','','\\\\909','',0,1,0,'Delivery Receipt Report','\\9090221',4365,'0'," . $params['levelid'] . ")";
          }

          $rep_salesorderdealerreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Order Dealer Report','\\9090204',3486,'0'," . $params['levelid'] . ")";
          $rep_salesorderbranchreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Order Branch Report','\\9090205',3487,'0'," . $params['levelid'] . ")";
          $rep_salesorderonlinereport = "('','\\90902','','\\\\909','',0,1,0,'Sales Order Online Report','\\9090206',3488,'0'," . $params['levelid'] . ")";

          $rep_salesjournaldealerreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Journal Dealer Report','\\9090207',3489,'0'," . $params['levelid'] . ")";
          $rep_salesjournalbranchreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Journal Branch Report','\\9090208',3490,'0'," . $params['levelid'] . ")";
          $rep_salesjournalonlinereport = "('','\\90902','','\\\\909','',0,1,0,'Sales Journal Online Report','\\9090209',3491,'0'," . $params['levelid'] . ")";

          $rep_specialpartsrequestreport = "('','\\90902','','\\\\909','',0,1,0,'Special Parts Request Report','\\9090210',3492,'0'," . $params['levelid'] . ")";
          $rep_specialpartsissuancereport = "('','\\90902','','\\\\909','',0,1,0,'Special Parts Issuance Report','\\9090211',3493,'0'," . $params['levelid'] . ")";
          $rep_specialpartsreturnreport = "('','\\90902','','\\\\909','',0,1,0,'Special Parts Return Report','\\9090212',3494,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
              $rep_quotation = "('','\\90902','','\\\\909','',0,1,0,'Quotation Report','\\9090205',3482,'0'," . $params['levelid'] . ")";
              $rep_salesorderafti = "('','\\90902','','\\\\909','',0,1,0,'Sales Order Report Afti','\\9090206',3523,'0'," . $params['levelid'] . ")";
              $rep_voidsalesorderreport = "('','\\90902','','\\\\909','',0,1,0,'Void Sales Order Report','\\90902010',3524,'0'," . $params['levelid'] . ")";
              $rep_stockissuanceaftireport = "('','\\90902','','\\\\909','',0,1,0,'Stock Issuance Report','\\90902012',3526,'0'," . $params['levelid'] . ")";
              $rep_requestforreplacementreturn = "('','\\90902','','\\\\909','',0,1,0,'Request for Replacement or Return','\\909020123',3527,'0'," . $params['levelid'] . ")";
              break;
            case 43: //mighty
              $rep_operatorhistory = "('','\\90902','','\\\\909','',0,1,0,'Operator History','\\9090222',4574,'0'," . $params['levelid'] . ")";
              $rep_material_request_report = "('','\\90902','','\\\\909','',0,1,0,'Material Request Report','\\9090219',3528,'0'," . $params['levelid'] . ")";
              $rep_material_issuance_report = "('','\\90902','','\\\\909','',0,1,0,'Material Issuance Report','\\9090218',3450,'0'," . $params['levelid'] . ")";
              break;
            default:
              $rep_salesorderreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Order Report','\\9090201',3063,'0'," . $params['levelid'] . ")";
              break;
          }

          if ($params['companyid'] == 11) { //summit
            $rep_sjseriesreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Journal Series Report','\\9090217',3007,'0'," . $params['levelid'] . ")";
            $rep_salesreportdetail = "('','\\905','','','',0,1,0,'Sales Report','\\90508',3087,'0'," . $params['levelid'] . ")";
          }
          // break;

          if ($params['companyid'] == 19) { //housegem
            $rep_voidsalesorderreport = "('','\\90902','','\\\\909','',0,1,0,'Void Sales Order Report','\\90902010',3524,'0'," . $params['levelid'] . ")";
            $rep_request_order_report = "('','\\90902','','\\\\909','',0,1,0,'Request Order Report','\\9090216',3921,'0'," . $params['levelid'] . ")";
          }

          if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti, afti usd
            $rep_salesactivityreport = "('','\\90902','','\\\\909','',0,1,0,'Sales Activity Report','\\9090213',3499,'0'," . $params['levelid'] . ")";
            $rep_servicesalesorderreport = "('','\\90902','','\\\\909','',0,1,0,'Service Sales Order Report','\\9090214',3500,'0'," . $params['levelid'] . ")";
            $rep_taskerrandreport = "('','\\90902','','\\\\909','',0,1,0,'Task Errand Report','\\9090215',3501,'0'," . $params['levelid'] . ")";
          }



          if ($params['companyid'] == 24) { //goodfound
            $subparent_production = "('','\\909','','','',0,0,0,'Production','\\90911',3954,'0'," . $params['levelid'] . ")";
            $rep_material_issuance_report_list = "('','\\90911','','\\\\909','',0,1,0,'Material Issuance Report List','\\9091101',3955,'0'," . $params['levelid'] . ")";
            $rep_supplies_issuance_report_list = "('','\\90911','','\\\\909','',0,1,0,'Supplies Issuance Report List','\\9091102',3956,'0'," . $params['levelid'] . ")";
            $rep_finish_good_report_list = "('','\\90911','','\\\\909','',0,1,0,'Finish Good Report List','\\9091103',3957,'0'," . $params['levelid'] . ")";
          }

          if ($params['companyid'] == 27 || $params['companyid'] == 36) { //nte, rozlab
            $subparent_production = "('','\\909','','','',0,0,0,'Production','\\90911',3954,'0'," . $params['levelid'] . ")";
            $rep_prod_joborderreport = "('','\\90911','','\\\\909','',0,1,0,'Production Job Order Report','\\9091104',4075,'0'," . $params['levelid'] . ")";
            $rep_prodinput_report = "('','\\90911','','\\\\909','',0,1,0,'Production Input Report','\\9091105',4076,'0'," . $params['levelid'] . ")";
          }

          $subparent_inventory = "('','\\909','','','',0,0,0,'Inventory','\\90903',3066,'0'," . $params['levelid'] . ")";
          $rep_inventorysetupreport = "('','\\90903','','\\\\909','',0,1,0,'Inventory Setup Report','\\9090301',3067,'0'," . $params['levelid'] . ")";
          $rep_physicalcountreport = "('','\\90903','','\\\\909','',0,1,0,'Physical Count Report','\\9090302',3068,'0'," . $params['levelid'] . ")";
          $rep_transferslipreport = "('','\\90903','','\\\\909','',0,1,0,'Transfer Slip Report','\\9090303',3069,'0'," . $params['levelid'] . ")";
          $rep_inventoryadjustmentreport = "('','\\90903','','\\\\909','',0,1,0,'Inventory Adjustment Report','\\9090304',3070,'0'," . $params['levelid'] . ")";
          $subparent_payables = "('','\\909','','','',0,0,0,'Payables','\\90904',3071,'0'," . $params['levelid'] . ")";
          $rep_apsetupreport = "('','\\90904','','\\\\909','',0,1,0,'AP Setup','\\9090401',3072,'0'," . $params['levelid'] . ")";
          $rep_apvoucherreport = "('','\\90904','','\\\\909','',0,1,0,'AP Voucher','\\9090402',3073,'0'," . $params['levelid'] . ")";
          $rep_cashcheckvoucherreport = "('','\\90904','','\\\\909','',0,1,0,'Cash Check Voucher','\\9090403',3074,'0'," . $params['levelid'] . ")";

          if ($params['companyid'] == 16) { //ati
            $rep_cashcheckvoucherreport = "('','\\90901','','\\\\909','',0,1,0,'Cash Check Voucher','\\9090117',3074,'0'," . $params['levelid'] . ")";
          }

          if ($params['companyid'] == 10 || $params['companyid'] == 12) { //afti, afti usd
            $rep_encashmentreport = "('','\\90904','','\\\\909','',0,1,0,'Encashment Report','\\9090407',3560,'0'," . $params['levelid'] . ")";
            $rep_onlineencashmentreport = "('','\\90904','','\\\\909','',0,1,0,'Online Encashment Report','\\9090408',3582,'0'," . $params['levelid'] . ")";
          }

          $subparent_receivables = "('','\\909','','','',0,0,0,'Receivables','\\90905',3075,'0'," . $params['levelid'] . ")";
          $rep_arsetupreport = "('','\\90905','','\\\\909','',0,1,0,'AR Setup','\\9090501',3076,'0'," . $params['levelid'] . ")";
          $rep_receivedpaymentreport = "('','\\90905','','\\\\909','',0,1,0,'Received Payment','\\9090502',3077,'0'," . $params['levelid'] . ")";
          $rep_counterreceiptreport = "('','\\90905','','\\\\909','',0,1,0,'Counter Receipt','\\9090503',3078,'0'," . $params['levelid'] . ")";
          $rep_treceivedchecks = "('','\\90905','','\\\\909','',0,1,0,'Received Checks','\\9090505',5185,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 40: //cdo
              $rep_mccollection = "('','\\90905','','\\\\909','',0,1,0,'MC Collection','\\9090504',4632,'0'," . $params['levelid'] . ")";
              break;
          }


          $subparent_accounting = "('','\\909','','','',0,0,0,'Accounting','\\90906',3079,'0'," . $params['levelid'] . ")";
          $rep_generaljournalreport = "('','\\90906','','\\\\909','',0,1,0,'General Journal','\\9090601',3080,'0'," . $params['levelid'] . ")";
          $rep_depositslipreport = "('','\\90906','','\\\\909','',0,1,0,'Deposit Slip','\\9090602',3081,'0'," . $params['levelid'] . ")";
          $rep_debit_memo_sum = "('','\\90906','','\\\\909','',0,1,0,'Debit Memo Summary','\\9090603',3406,'0'," . $params['levelid'] . ")";
          $rep_credit_memo_sum = "('','\\90906','','\\\\909','',0,1,0,'Credit Memo Summary','\\9090604',3407,'0'," . $params['levelid'] . ")";

          $subparent_issuance = "('','\\909','','','',0,0,0,'Issuance','\\90910',3508,'0'," . $params['levelid'] . ")";
          $rep_stockrequestreport = "('','\\90910','','\\\\909','',0,1,0,'Stock Request Report','\\9091001',3509,'0'," . $params['levelid'] . ")";
          $rep_stocktransferreport = "('','\\90910','','\\\\909','',0,1,0,'Stock Transfer Report','\\9091002',3510,'0'," . $params['levelid'] . ")";
          $rep_stockissuancereport = "('','\\90910','','\\\\909','',0,1,0,'Stock Issuance Report','\\9091003',3511,'0'," . $params['levelid'] . ")";
          $rep_stockreturnreport = "('','\\90910','','\\\\909','',0,1,0,'Stock Return Report','\\9091004',4706,'0'," . $params['levelid'] . ")";


          if ($params['companyid'] == 8) { //maxipro
            $rep_monthlysummary_outputtax = "";
          } else {
            $rep_monthlysummary_outputtax = "('','\\905','','','',0,1,0,'Monthly Summary of Output Tax','\\90513',3401,'0'," . $params['levelid'] . ")";
          }

          $monthly_sum_ewt = "('','\\906','','','',0,1,0,'Monthly Summary of EWT Report','\\90613',3402,'0'," . $params['levelid'] . ")";
          $rep_withholdingtax = "('','\\906','','','',0,1,0,'Monthly Summary of Input Tax','\\90614',3403,'0'," . $params['levelid'] . ")";

          switch ($params['companyid']) {
            case 10: //afti
            case 12: //afti usd
              $subparent_crm = "('','\\909','','','',0,0,0,'CRM','\\90909',3483,'0'," . $params['levelid'] . ")";
              $rep_Lead = "('','\\90909','','\\\\909','',0,1,0,'Lead Report','\\9090901',3484,'0'," . $params['levelid'] . ")";
              $rep_opportunity_module = "('','\\90909','','\\\\909','',0,1,0,'Opportunity Module Report','\\9090902',3485,'0'," . $params['levelid'] . ")";

              $subparent_pcf = "('','\\909','','','',0,0,0,'PCF','\\90917',5409,'0'," . $params['levelid'] . ")";
              $rep_pcf_summary = "('','\\90917','','\\\\909','',0,1,0,'PCF Summary Report','\\9091701',5410,'0'," . $params['levelid'] . ")";
              break;
          }

          if ($params['companyid'] == 34) { //evergreen
            $subparent_operation = "('','\\909','','','',0,0,0,'Operation','\\90912',4092,'0'," . $params['levelid'] . ")";
            $rep_application_list = "('','\\90912','','\\\\909','',0,1,0,'Application List','\\9091201',4093,'0'," . $params['levelid'] . ")";
            $rep_lifeplan_agrement_list = "('','\\90912','','\\\\909','',0,1,0,'Life Plan Agreement List','\\9091202',4094,'0'," . $params['levelid'] . ")";
          }
          // CRM
          if ($params['companyid'] == 0) { //default
            $subparent_customer_support = "('','\\909','','','',0,0,0,'Customer Support','\\90914',4736,'0'," . $params['levelid'] . ")";
            $rep_ticket = "('','\\90914','','\\\\909','',0,1,0,'Ticket','\\9091401',4737,'0'," . $params['levelid'] . ")";
          }

          if ($params['companyid'] == 55) { //afli
            $subparent_lending = "('','\\909','','','',0,0,0,'Lending','\\90915',4998,'0'," . $params['levelid'] . ")";
            $rep_application_form = "('','\\90915','','\\\\909','',0,1,0,'Application Form','\\9091501',4999,'0'," . $params['levelid'] . ")";
            $rep_loan_approval = "('','\\90915','','\\\\909','',0,1,0,'Loan Approval','\\9091502',5000,'0'," . $params['levelid'] . ")";
            $rep_loan_summary = "('','\\90915','','\\\\909','',0,1,0,'Loan Summary','\\9091503',5001,'0'," . $params['levelid'] . ")";
          }
        }
        break;
    }

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
        $parent_mallreportlist = "('','\\9','','','',0,0,0,'Mall Report List','\\914',4281,'0'," . $params['levelid'] . ")";
        $rep_statement_of_account_report = "('','\\914','','','',0,1,0,'Statement of Account Report','\\91309',4282,'0'," . $params['levelid'] . ")";
        $rep_summary_of_other_charges_report = "('','\\914','','','',0,1,0,'Summary of Other Charges Report','\\91310',4283,'0'," . $params['levelid'] . ")";
        $rep_daily_collection_report = "('','\\914','','','',0,1,0,'Daily Collection Report','\\91311',4284,'0'," . $params['levelid'] . ")";
        $rep_comparative_data_of_billing_and_collection = "('','\\914','','','',0,1,0,'Comparative Data of Billing and Collection','\\91312',4285,'0'," . $params['levelid'] . ")";
        $rep_collection_summary_report = "('','\\914','','','',0,1,0,'Collection Summary','\\91313',4302,'0'," . $params['levelid'] . ")";
        $rep_billing_summary_report = "('','\\914','','','',0,1,0,'Billing Summary','\\91314',4307,'0'," . $params['levelid'] . ")";
        $rep_collection_letter_report = "('','\\914','','','',0,1,0,'Collection Letter','\\91315',4308,'0'," . $params['levelid'] . ")";
        $rep_billing_vs_collection_report =  "('','\\914','','','',0,1,0,'Billing vs Collection','\\91316',4309,'0'," . $params['levelid'] . ")";
        break;
    }

    // Motorcycle Reports-CDO
    $parent_motorcyclerep = "";
    $rep_soldout_unit = "";
    $rep_mc_availability = "";
    $rep_average_sales_per_mc_unit = "";
    $rep_total_sell = "";
    $rep_total_delivery_report = "";
    $rep_MC_sales_report = "";
    $rep_total_sales_per_mode = "";

    //Spareparts Reports
    $parent_sparepart_rep = "";
    $rep_category_sales_rep = "";
    $rep_daily_sales_rep = "";
    $rep_sell_per_item_rep = "";
    $rep_walkin_and_service_sales = "";
    $rep_salescategorydetailsinandout_rep = "";
    $rep_salesindetails_rep = "";
    $rep_salesperchannel_rep = "";
    $rep_comparison_rep = "";
    $rep_spareparts_rep_per_mechanic = "";



    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;

      default:
        if ($params['companyid'] == 40) { //cdo
          $parent_motorcyclerep = "('','\\9','','','',0,0,0,'Motorcycle Reports','\\915',4486,'0'," . $params['levelid'] . ")";
          $rep_soldout_unit = "('','\\915','','','',0,1,0,'Sold Out Unit','\\91501',4487,'0'," . $params['levelid'] . ")";
          $rep_mc_availability = "('','\\915','','','',0,1,0,'MC Availability','\\91502',4497,'0'," . $params['levelid'] . ")";
          $rep_average_sales_per_mc_unit = "('','\\915','','','',0,1,0,'Average Sales Per MC Unit','\\91503',4498,'0'," . $params['levelid'] . ")";
          $rep_total_sell = "('','\\915','','','',0,1,0,'Total Sell In','\\91504',4509,'0'," . $params['levelid'] . ")";
          $rep_total_delivery_report = "('','\\915','','','',0,1,0,'Total Delivery Report','\\91505',4510,'0'," . $params['levelid'] . ")";
          $rep_MC_sales_report = "('','\\915','','','',0,1,0,'Sales Report','\\91506',4641,'0'," . $params['levelid'] . ")";
          $rep_total_sales_per_mode = "('','\\915','','','',0,1,0,'Total Sales Per Mode of Sales','\\91507',5348,'0'," . $params['levelid'] . ")";

          // Spareparts reports
          $parent_sparepart_rep = "('','\\9','','','',0,0,0,'Spare Parts Reports','\\917',4634,'0'," . $params['levelid'] . ")";
          $rep_category_sales_rep = "('','\\917','','','',0,1,0,'Category Sales','\\91701',4635,'0'," . $params['levelid'] . ")";
          $rep_daily_sales_rep = "('','\\917','','','',0,1,0,'Daily Sales','\\91702',4636,'0'," . $params['levelid'] . ")";
          $rep_sell_per_item_rep = "('','\\917','','','',0,1,0,'Sell Out Per Item','\\91703',4637,'0'," . $params['levelid'] . ")";
          $rep_walkin_and_service_sales = "('','\\917','','','',0,1,0,'Walkin and Service sales','\\91704',4638,'0'," . $params['levelid'] . ")";
          $rep_salescategorydetailsinandout_rep = "('','\\917','','','',0,1,0,'Category Details In and Out','\\91705',4639,'0'," . $params['levelid'] . ")";
          $rep_salesindetails_rep = "('','\\917','','','',0,1,0,'Sales In Details','\\91706',4640,'0'," . $params['levelid'] . ")";
          $rep_salesperchannel_rep = "('','\\917','','','',0,1,0,'Sales Per Channel','\\91707',4643,'0'," . $params['levelid'] . ")";
          $rep_comparison_rep = "('','\\917','','','',0,1,0,'Comparison Report','\\91708',4648,'0'," . $params['levelid'] . ")";
          $rep_spareparts_rep_per_mechanic = "('','\\917','','','',0,1,0,'Spare Parts Report Per Mechanic','\\91709',4924,'0'," . $params['levelid'] . ")";
        }
        break;
    }



    // TRIP MIGHTY
    $parent_trip = "";
    $rep_trip_detailed = "";
    $rep_trip_summary = "";
    $rep_staff_trip_summary = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;

      default:
        if ($params['companyid'] == 43) { //mighty
          $parent_trip = "('','\\9','','','',0,0,0,'Trip Reports','\\910',4504,'0'," . $params['levelid'] . ")";
          $rep_trip_detailed = "('','\\910','','','',0,0,0,'Trip Detailed','\\91001',4505,'0'," . $params['levelid'] . ")";
          $rep_trip_summary = "('','\\910','','','',0,0,0,'Trip Summary','\\91002',4506,'0'," . $params['levelid'] . ")";
          $rep_staff_trip_summary = "('','\\910','','','',0,0,0,'Staff Trip Summary','\\91003',4507,'0'," . $params['levelid'] . ")";
        }
        break;
    }


    // SCHOOL SYSTEM
    $parent_school_system_reports = "";
    $rep_summaryofquarterlyaveragereport = "";
    $rep_classrecordreport = "";
    $rep_summaryofgradesreport = "";
    $rep_student_list = "";

    $parent_students = "";
    $rep_currentstudentreceivable = "";
    $rep_currentstudentreceivableaging = "";
    $rep_studentsalesreport = "";
    $rep_studentSOA = "";
    $rep_studentreceivedpayment = "";


    switch ($params['companyid']) {
      case 7: //enrollment
        $parent_school_system_reports = "('','\\9','','','',0,0,0,'School System Reports','\\910',3514,'0'," . $params['levelid'] . ")";
        $rep_summaryofquarterlyaveragereport = "('','\\910','','','',0,1,0,'Summary of Quarterly Average Report','\\91001',3515,'0'," . $params['levelid'] . ")";
        $rep_classrecordreport = "('','\\910','','','',0,1,0,'Class Record Report','\\91002',3517,'0'," . $params['levelid'] . ")";
        $rep_summaryofgradesreport = "('','\\910','','','',0,1,0,'Summary of Grades Report','\\91003',3519,'0'," . $params['levelid'] . ")";

        //STUDENT
        $parent_students = "('','\\9','','','',0,0,0,'Student','\\914',5110,'0'," . $params['levelid'] . ")";
        $rep_student_list = "('','\\914','','','',0,1,0,'Student List','\\91401',4023,'0'," . $params['levelid'] . ")";
        $rep_currentstudentreceivable = "('','\\914','','','',0,1,0,'Current Student Receivables','\\91402',5111,'0'," . $params['levelid'] . ")";
        $rep_currentstudentreceivableaging = "('','\\914','','','',0,1,0,'Current Student Receivables Aging','\\91403',5112,'0'," . $params['levelid'] . ")";
        $rep_studentsalesreport = "('','\\914','','','',0,1,0,'Student Sales Report','\\91404',5113,'0'," . $params['levelid'] . ")";
        $rep_studentSOA = "('','\\914','','','',0,1,0,'Student Statement of Account','\\91405',5114,'0'," . $params['levelid'] . ")";
        $rep_studentreceivedpayment = "('','\\914','','','',0,1,0,'Student Received Payment Report','\\91406',5115,'0'," . $params['levelid'] . ")";

        break;
    }

    // PAYABLES
    $rep_vessel_document = $rep_nods = $rep_jobreq = "";

    $rep_petty_cash = "";
    $rep_petty_cash_request = "";
    $rep_petty_cash_reconciliation = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'FAMS':
      case 'QUEUING':
        break;

      default:
        $rep_petty_cash = "('','\\90904','','\\\\909','',0,1,0,'Petty Cash Voucher Report','\\9090404',3451,'0'," . $params['levelid'] . ")";
        $rep_petty_cash_request = "('','\\90904','','\\\\909','',0,1,0,'Petty Cash Request Report','\\9090405',3456,'0'," . $params['levelid'] . ")";
        $rep_petty_cash_reconciliation = "('','\\90904','','\\\\909','',0,1,0,'Petty Cash Reconciliation','\\9090406',3518,'0'," . $params['levelid'] . ")";

        switch ($params['companyid']) {
          case 3: //conti
          case 14: //majesty
          case 15: //nathina
          case 17: //unihome
          case 28: //xcomp
          case 39: //CBBSI
          case 49: //hotmix
            // SALES
            $rep_material_issuance_report = "('','\\90902','','\\\\909','',0,1,0,'Material Issuance Report','\\9090218',3450,'0'," . $params['levelid'] . ")";
            $rep_material_request_report = "('','\\90902','','\\\\909','',0,1,0,'Material Request Report','\\9090219',3528,'0'," . $params['levelid'] . ")";
            break;
          case 19: //housegem
            $rep_material_issuance_report = "('','\\90903','','','',0,1,0,'Material Issuance Report','\\9090218',3450,'0'," . $params['levelid'] . ")";
            break;
        }
        break;
    }

    // SUPPLIER

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'QUEUING':
        break;

      default:
        $rep_purchase_detailed = "('','\\906','','','',0,1,0,'Purchases Detailed Report','\\90615',3455,'0'," . $params['levelid'] . ")";

        switch ($params['companyid']) {
          case 3: // conti
          case 14: //majesty
          case 15: // nathina
          case 17: // unihome
          case 27: //NTE
          case 28: //xcomp
          case 36: //ROZLAB
          case 39: //CBBSI
            $rep_sched_fifo = "('','\\904','','','',0,1,0,'Schedule of Inventory FIFO','\\90414',3457,'0'," . $params['levelid'] . ")";
            break;
        }
        switch ($params['companyid']) {
          case 14: //majesty
            $rep_physical_count_sheet_per_batch_report = "('','\\904','','','',0,1,0,'Physical Count Sheet Per Batch Report','\\90440',4099,'0'," . $params['levelid'] . ")";
            break;
        }

        $rep_inv_movement = "('','\\904','','','',0,1,0,'Inventory Movement Report','\\90415',3458,'0'," . $params['levelid'] . ")";

        switch ($params['companyid']) {
          case 3: // conti
            // ITEMS
            $rep_inv_movement = "('','\\904','','','',0,1,0,'Inventory Movement Report','\\90415',3458,'0'," . $params['levelid'] . ")";
            $rep_items_negative_bal = "('','\\904','','','',0,1,0,'Items With Negative Balance','\\90416',3459,'0'," . $params['levelid'] . ")";
            $rep_quantity_on_hand = "('','\\904','','','',0,1,0,'Quantity On Hand','\\90417',3462,'0'," . $params['levelid'] . ")";
            $rep_unserved_po = "('','\\904','','','',0,1,0,'Unserved Purchase Order','\\90418',3463,'0'," . $params['levelid'] . ")";
            $rep_non_moving_items = "('','\\904','','','',0,1,0,'Non Moving Items','\\90419',3465,'0'," . $params['levelid'] . ")";
            $inv_checksheet = "('','\\904','','','',0,1,0,'Inventory Checksheet','\\90413',3446,'0'," . $params['levelid'] . ")";
            $price_list = "('','\\904','','','',0,1,0,'Price List','\\90520',3464,'0'," . $params['levelid'] . ")";

            // JOB REQUEST REPORT
            $jobrequeststatus = "('','\\900','','','',0,1,0,'Job Request Status','\\3449',4583,'0'," . $params['levelid'] . ")";

            // VESSEL REPORT
            $subparent_voyage = "('','\\909','','','',0,0,0,'Vessel','\\90907',3466,'0'," . $params['levelid'] . ")";
            $rep_voyagereport = "('','\\90907','','\\\\909','',0,1,0,'Voyage Report','\\9090701',3467,'0'," . $params['levelid'] . ")";
            $rep_listcrewpervessel = "('','\\90907','','\\\\909','',0,1,0,'List of Crew per Vessel','\\9090702',3452,'0'," . $params['levelid'] . ")";
            $rep_listvoyagereportpervessel = "('','\\90907','','\\\\909','',0,1,0,'List of Voyage Report per Vessel','\\9090703',3460,'0'," . $params['levelid'] . ")";
            $rep_listwarehousevessels = "('','\\90907','','\\\\909','',0,1,0,'List of Warehouses or Vessels per Type','\\9090704',3461,'0'," . $params['levelid'] . ")";

            $rep_vessel_document = "('','\\90907','','\\\\909','',0,1,0,'Vessel Documents','\\9090705',3520,'0'," . $params['levelid'] . ")";
            $rep_nods = "('','\\90907','','\\\\909','',0,1,0,'NODS','\\9090706',3521,'0'," . $params['levelid'] . ")";
            $rep_jobreq = "('','\\90907','','\\\\909','',0,1,0,'Job Requests','\\9090707',3522,'0'," . $params['levelid'] . ")";

            // TRANSACTION LIST PURCHASES 
            $rep_supplierinvoicereport = "('','\\90901','','\\\\909','',0,1,0,'Supplier Invoice','\\9090105',3468,'0'," . $params['levelid'] . ")";

            break;
          case 14: //majesty
            $rep_inv_movement = "('','\\904','','','',0,1,0,'Inventory Movement Report','\\90415',3458,'0'," . $params['levelid'] . ")";
            $rep_items_negative_bal = "('','\\904','','','',0,1,0,'Items With Negative Balance','\\90416',3459,'0'," . $params['levelid'] . ")";
            $rep_non_moving_items = "('','\\904','','','',0,1,0,'Non Moving Items','\\90419',3465,'0'," . $params['levelid'] . ")";
            $price_list = "('','\\904','','','',0,1,0,'Price List','\\90520',3464,'0'," . $params['levelid'] . ")";
            break;
          case 40: // CDO
            $rep_non_moving_items = "('','\\904','','','',0,1,0,'Non Moving Items','\\90419',3465,'0'," . $params['levelid'] . ")";
            break;
        }

        break;
    }


    // HRIS
    $subparent_applicant = "";
    $rep_hris_applicant_listing = "";
    $rep_personnel_req = "";
    $rep_job_offer = "";
    $rep_req_train_dev = "";
    $rep_train_entry = "";

    $rep_turn_over_items = "";
    $rep_return_items = "";
    $rep_emp_stat_entry_change = "";
    $rep_incident_rep = "";
    $rep_notice_explain = "";
    $rep_notice_discip_action = "";
    $rep_length_service = "";
    $rep_gender_statistic = "";
    $rep_workplace_generation = "";
    $rep_timekeeping_graph_report = "";
    $rep_late_frequency_report = "";
    $rep_timekeeping_monthly_report = "";
    $rep_separated_employee_stat = "";
    $rep_monthly_turnover_rate = "";
    $rep_separation_breakdown_report = "";
    $rep_payroll_employee_listing = '';
    $rep_employee_count = "";
    $rep_employee_movement = "";
    $rep_reason_leaving_company = "";
    $rep_COE = "";
    $rep_Employee_Infraction_report = "";
    $rep_contract_monitoring = "";
    $rep_payroll_expense = "";
    $rep_recruitment_status_report = "";
    $rep_stayin_employee_report = "";
    $rep_wage_and_career_history_report = "";

    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'AIMSHRIS':
      case 'HRISPAYROLL':
        $label = "Employment Status Entry or Change";
        if ($params['companyid'] == 58) { // cdohris
          $label = "Employment Status";
        }
        $subparent_applicant = "('','\\9','','','',0,0,0,'HRIS Reports','\\A01',3413,'0'," . $params['levelid'] . ")";
        $rep_hris_applicant_listing = "('','\\A01','','','',0,1,0,'Applicant Listing','\\A0101',3414,'0'," . $params['levelid'] . ")";
        $rep_personnel_req = "('','\\A01','','','',0,1,0,'Personnel Requisition','\\A0102',3436,'0'," . $params['levelid'] . ")";
        $rep_job_offer = "('','\\A01','','','',0,1,0,'Job Offer','\\A0103',3437,'0'," . $params['levelid'] . ")";
        $rep_req_train_dev = "('','\\A01','','','',0,1,0,'Request for Training and Development','\\A0104',3438,'0'," . $params['levelid'] . ")";
        $rep_train_entry = "('','\\A01','','','',0,1,0,'Training Entry','\\A0105',3439,'0'," . $params['levelid'] . ")";

        $rep_turn_over_items = "('','\\A01','','','',0,1,0,'Turn Over of Items','\\A0106',3440,'0'," . $params['levelid'] . ")";
        $rep_return_items = "('','\\A01','','','',0,1,0,'Return of Items','\\A0107',3441,'0'," . $params['levelid'] . ")";
        $rep_emp_stat_entry_change = "('','\\A01','','','',0,1,0, '$label','\\A0108',3442,'0'," . $params['levelid'] . ")";
        $rep_incident_rep = "('','\\A01','','','',0,1,0,'Incident Report','\\A0109',3443,'0'," . $params['levelid'] . ")";
        $rep_notice_explain = "('','\\A01','','','',0,1,0,'Notice to Explain','\\A01010',3444,'0'," . $params['levelid'] . ")";
        $rep_notice_discip_action = "('','\\A01','','','',0,1,0,'Notice of Disciplinary Action','\\A01011',3445,'0'," . $params['levelid'] . ")";
        $rep_gender_statistic = "('','\\A01','','','',0,1,0,'Gender Statistic','\\A01012',5141,'0'," . $params['levelid'] . ")";
        $rep_length_service = "('','\\A01','','','',0,1,0,'Length of Service','\\A01013',5142,'0'," . $params['levelid'] . ")";
        $rep_workplace_generation = "('','\\A01','','','',0,1,0,'Generation Statistics Report','\\A01014',5206,'0'," . $params['levelid'] . ")";

        $rep_late_frequency_report = "('','\\A01','','','',0,1,0,'Late Frequency Report','\\A01016',5213,'0'," . $params['levelid'] . ")";

        $rep_separated_employee_stat = "('','\\A01','','','',0,1,0,'Separated Employees and Status','\\A01017',5232,'0'," . $params['levelid'] . ")";
        $rep_monthly_turnover_rate = "('','\\A01','','','',0,1,0,'Monthly Turnover Rate','\\A01018',5233,'0'," . $params['levelid'] . ")";
        $rep_separation_breakdown_report = "('','\\A01','','','',0,1,0,'Separation Breakdown Report','\\A01019',5235,'0'," . $params['levelid'] . ")";

        $rep_employee_count = "('','\\A01','','','',0,1,0,'Active Employee Count','\\A01021',5452,'0'," . $params['levelid'] . ")";
        $rep_employee_movement = "('','\\A01','','','',0,1,0,'Employee Movement Report','\\A01022',5453,'0'," . $params['levelid'] . ")";
        $rep_reason_leaving_company = "('','\\A01','','','',0,1,0,'Reason of Leaving the Company','\\A01023',5454,'0'," . $params['levelid'] . ")";
        $rep_COE = "('','\\A01','','','',0,1,0,'COE','\\A01024',5455,'0'," . $params['levelid'] . ")";
        // $rep_payroll_expense = "('','\\B01','','','',0,1,0,'Payroll Expense Report','\\B0116',5456,'0'," . $params['levelid'] . ")";

        if ($this->companysetup->getsystemtype($params) == 'HRISPAYROLL') {
          $rep_payroll_employee_listing = "('','\\A01','','','',0,1,0,'Employee Listing','\\A01020',3416,'0'," . $params['levelid'] . ")";
        }
        $rep_recruitment_status_report = "('','\\A01','','','',0,1,0,'Recruitment Status Report','\\A01026',5550,'0'," . $params['levelid'] . ")";

        $rep_Employee_Infraction_report = "('','\\A01','','','',0,1,0,'Employee Infraction Report','\\A01025',5551,'0'," . $params['levelid'] . ")";
        $rep_contract_monitoring = "('','\\A01','','','',0,1,0,'Contract Monitoring Report','\\A01027',5553,'0'," . $params['levelid'] . ")";
        $rep_stayin_employee_report = "('','\\A01','','','',0,1,0,'Stay In Employee Report','\\A01028',5555,'0'," . $params['levelid'] . ")";
        //3/7/2026 -rwn
        $rep_wage_and_career_history_report = "('','\\A01','','','',0,1,0,'Wage and Career History Report','\\A01029',5590,'0'," . $params['levelid'] . ")";
        break;
    }




    $subparent_construction = "";
    $rep_liquidation = "";
    $rep_jo_summ = "";
    $rep_jo_list = "";
    $rep_jo_completion_list = "";
    $rep_project_cost_summary = "";
    $trans_list_subparent_construction = "";
    $project_monitoring = "";
    $consolidated_project_cost_summary = "";
    $subcon_project_monitoring = "";
    $comparative_expense_report = "";
    $rep_project_cost_analysis = "";
    $accounts_receivable_subcontractor_report = "";
    $rep_site_monitoring = "";
    $rep_project_cost_and_expenses = "";
    $rep_construction_instruction = "";

    $rep_construction_order = '';
    $rep_project_completion_report = '';
    $rep_materialtransfer_report = '';

    $parent_cargologistics = "";
    $rep_waybillreport = "";
    $rep_cargomanifestsum = "";
    $rep_loadingguide = "";
    $rep_amortization = "";

    $parent_ticketingreports = "";
    $rep_ticketreports = "";

    switch ($params['companyid']) {
      case 8: //maxipro
        // CONSTRUCTION
        $subparent_construction = "('','\\D','','','',0,0,0,'Construction Reports','\\D01',4591,'0'," . $params['levelid'] . ")";
        $rep_liquidation = "('','\\D01','','','',0,1,0,'Liquidation Report','\\D0101',3470,'0'," . $params['levelid'] . ")";
        $rep_jo_summ = "('','\\D01','','','',0,1,0,'Job Order Reports','\\D0102',3471,'0'," . $params['levelid'] . ")";
        $rep_project_cost_summary = "('','\\D01','','','',0,1,0,'Project Cost Summary','\\D0105',3474,'0'," . $params['levelid'] . ")";
        $project_monitoring = "('','\\D01','','','',0,1,0,'Project Monitoring','\\D0106',3475,'0'," . $params['levelid'] . ")";
        $consolidated_project_cost_summary = "('','\\D01','','','',0,1,0,'Consolidated Project Cost Summary','\\D0107',3476,'0'," . $params['levelid'] . ")";
        $subcon_project_monitoring = "('','\\D01','','','',0,1,0,'Subcon Project Monitoring','\\D0108',3477,'0'," . $params['levelid'] . ")";
        $comparative_expense_report = "('','\\D01','','','',0,1,0,'Comparative Expense Report','\\D0109',3706,'0'," . $params['levelid'] . ")";
        $rep_project_cost_analysis = "('','\\D01','','','',0,1,0,'Project Cost Analysis','\\D0110',3771,'0'," . $params['levelid'] . ")";
        $accounts_receivable_subcontractor_report = "('','\\D01','','','',0,1,0,'Subcontractor Report','\\D0111',3806,'0'," . $params['levelid'] . ")";
        $rep_site_monitoring = "('','\\D01','','','',0,1,0,'Site Monitoring Report','\\D0112',3809,'0'," . $params['levelid'] . ")";
        $rep_project_cost_and_expenses = "('','\\D01','','','',0,1,0,'Project Cost and Expenses','\\D0113',3810,'0'," . $params['levelid'] . ")";


        //TRANSACTION LIST
        $trans_list_subparent_construction = "('','\\909','','','',0,0,0,'Construction','\\90908',3409,'0'," . $params['levelid'] . ")";
        $rep_jo_list = "('','\\90908','','\\\\909','',0,1,0,'JO List','\\9090801',3472,'0'," . $params['levelid'] . ")";
        $rep_jo_completion_list = "('','\\90908','','\\\\909','',0,1,0,'JO Completion List','\\9090802',3473,'0'," . $params['levelid'] . ")";

        $rep_material_issuance_report = "('','\\90908','','\\\\909','',0,1,0,'Material Issuance Report','\\9090803',3450,'0'," . $params['levelid'] . ")";
        $rep_stocktransferreport = "('','\\90908','','\\\\909','',0,1,0,'Stock Transfer Report','\\9090804',3510,'0'," . $params['levelid'] . ")";

        break;

      default:
        // switch ($this->companysetup->getsystemtype($params)) {
        //   case 'REALESTATE':
        //REALESTATE Construction Reports
        if ($this->companysetup->isconstruction($params)) {
          $trans_list_subparent_construction = "('','\\909','','','',0,0,0,'Construction Reports','\\90913',4700,'0'," . $params['levelid'] . ")";
          $rep_construction_instruction = "('','\\90913','','\\\\909','',0,1,0,'Construction Instruction Report','\\9091301',4701,'0'," . $params['levelid'] . ")";
          $rep_construction_order = "('','\\90913','','\\\\909','',0,1,0,'Construction Order Report','\\9091302',4702,'0'," . $params['levelid'] . ")";
          $rep_material_issuance_report = "('','\\90913','','\\\\909','',0,1,0,'Material Issuance Report','\\9091304',3450,'0'," . $params['levelid'] . ")";
          $rep_material_request_report = "('','\\90913','','\\\\909','',0,1,0,'Material Request Report','\\9091303',3528,'0'," . $params['levelid'] . ")";
          $rep_project_completion_report = "('','\\90913','','\\\\909','',0,1,0,'Project Completion Report','\\9091305',4756,'0'," . $params['levelid'] . ")";
          $rep_materialtransfer_report = "('','\\90913','','\\\\909','',0,1,0,'Material Transfer Report','\\9091306',4758,'0'," . $params['levelid'] . ")";
        }

        if ($params['companyid'] == 48) { //SEASTAR
          $parent_cargologistics = "('','\\9','','','',0,0,0,'Cargo Logistics','\\910',4766,'0'," . $params['levelid'] . ")";
          $rep_waybillreport = "('','\\910','','','',0,0,0,'Waybill Report','\\91001',4767,'0'," . $params['levelid'] . ")";
          $rep_cargomanifestsum = "('','\\910','','','',0,0,0,'Cargo Manifest Summary','\\91002',4770,'0'," . $params['levelid'] . ")";
          $rep_loadingguide = "('','\\910','','','',0,0,0,'Loading Guide','\\91003',4785,'0'," . $params['levelid'] . ")";
          $rep_amortization = "('','\\910','','','',0,0,0,'Amortization Report','\\91004',4805,'0'," . $params['levelid'] . ")";
        }

        if ($this->companysetup->isticketing($params)) {
          $parent_ticketingreports = "('','\\9','','','',0,0,0,'Ticketing Reports','\\910',4843,'0'," . $params['levelid'] . ")";
          $rep_ticketreports = "('','\\910','','','',0,0,0,'Ticket Report','\\91001',4844,'0'," . $params['levelid'] . ")";
        }

        break;
    }



    // PAYROLL
    $subparent_other_report = '';
    $rep_month_pay_13th = '';
    $rep_dep_advise = '';
    $rep_emp_loan_balance = '';
    $rep_emp_rate_ = '';
    $subparent_bir_tax_report = '';
    $rep_payroll_tax_witheld = '';
    $rep_bir_2316 = '';
    $subparent_pagibig_report = '';
    $rep_payroll_pagibig_remittance = '';
    $rep_payroll_pagibig_loan_payment = '';
    $rep_payroll_pagibig_calamity_loan_report = '';
    $subparent_philhealth_report = '';
    $rep_payroll_philhealth_remittance = '';
    $subparent_sss_report = '';
    $rep_payroll_sss_remittance = '';
    $rep_payroll_sss_loan_payment = '';
    $rep_payroll_sss_calamity_loan_report = '';
    $subparent_payroll_report = '';
    $rep_payslip = '';
    $rep_payregister = '';
    $rep_totalnet_pay = '';
    $rep_payroll_voucher = '';
    $rep_loan_deduction_report = '';
    $rep_employee_demography_report = '';
    $rep_empallow = '';
    $rep_employee_birthday_list  = '';
    $rep_remittance_report = '';
    $rep_loan_reports = '';
    $rep_loan_summary_report = '';

    $rep_paymonthly_summary = "";
    $rep_alphalist = "";
    $rep_emp_timein_out_logs = "";
    // POS REPORT
    $parent_pos_report = "";
    $rep_pos_detailed_sales_report = "";
    $rep_pos_summarized_sales_report = "";
    $rep_monthly_sales_analysis_report = "";
    $rep_daily_analysis_of_product = "";
    $rep_hourly_sales_summary_report = "";
    $rep_transaction_pos_report = "";
    $rep_audit_trail_report = "";

    $rep_annual_sales_analysis = "";
    $rep_earning_deduction_report = "";
    $rep_emp_adv_balance = "";
    $rep_signature_sheet = "";
    $rep_earning_and_deduction_transaction_history = "";
    $rep_earning_deduction_list_report = "";
    $rep_employee_balances_report = "";
    // PAYROLL PORTAL REPORT
    $subparent_payroll_portal_report = "";
    $rep_ot_application_report = "";
    $rep_leave_application_report = "";
    $rep_changes_schedules_shift_report = "";
    $rep_payroll_accounting_entry_report = "";
    $rep_ob_filling_report = "";
    $rep_loan_report = "";
    $rep_undertime_report = "";
    // mighty
    $rep_trip_incentive_detailed = "";
    $rep_operator_incentive_report = "";

    // TIME AND ATTENDANCE REPORT
    $subparent_time_attendance_report = '';
    $rep_dtr = '';
    $rep_dtr_absent_report = "";
    $rep_dtr_undertime_report = "";
    $rep_dtr_late_report = "";
    $rep_perfect_attendance_report = "";
    $rep_leave_balance_report = "";
    $rep_leave_details_report = "";
    $rep_time_events_report = "";
    $rep_attendance_summary_report = "";
    $rep_summary_of_absences_for_13th_report = "";
    $bir_e_sales = "";
    //blank the access here if not applicable
    switch ($params['companyid']) {
      case 35: //aquamax
        $employee_list = '';
        $department_list = '';
        $subparent_purchases = '';
        $rep_receivingreport = '';
        $subparent_payables = '';
        $rep_apsetupreport = '';
        $rep_apvoucherreport = '';
        $rep_cashcheckvoucherreport = '';
        break;
    }

    $subparent_payroll_portal_report = '';
    $rep_ot_application_report = '';
    $rep_leave_application_report = '';


    $rep_leave_category = "";
    switch ($this->companysetup->getsystemtype($params)) {
      case 'ALL':
      case 'PAYROLL':
      case 'AIMSPAYROLL':
      case 'HRISPAYROLL':
      case 'PAYROLLPORTAL':
      case 'HRIS':
        $subparent_other_report = "('','\\9','','','',0,0,0,'Payroll Other Report','\\B01',3415,'0'," . $params['levelid'] . ")";

        if ($this->companysetup->getsystemtype($params) != 'HRISPAYROLL') {
          $rep_payroll_employee_listing = "('','\\B01','','','',0,1,0,'Employee Listing','\\B0101',3416,'0'," . $params['levelid'] . ")";
        }

        $rep_month_pay_13th = "('','\\B01','','','',0,1,0,'Employee 13th Month Pay','\\B0102',3417,'0'," . $params['levelid'] . ")";
        $rep_dep_advise = "('','\\B01','','','',0,1,0,'Deposit Advise','\\B0103',3433,'0'," . $params['levelid'] . ")";
        $rep_emp_loan_balance = "('','\\B01','','','',0,1,0,'Employee Loan Balances','\\B0104',3434,'0'," . $params['levelid'] . ")";
        $rep_emp_rate_ = "('','\\B01','','','',0,1,0,'Employee Rate Report','\\B0105',3435,'0'," . $params['levelid'] . ")";
        $rep_emp_adv_balance = "('','\\B01','','','',0,1,0,'Employee Advance Balances','\\B0107',3764,'0'," . $params['levelid'] . ")";

        $rep_emp_timein_out_logs = "('','\\B01','','','',0,1,0,'Employee Time in and Time out Logs','\\B0106',3624,'0'," . $params['levelid'] . ")";

        $rep_earning_deduction_report = "('','\\B01','','','',0,1,0,'Earning and Deduction Report','\\B0108',3765,'0'," . $params['levelid'] . ")";

        if ($params['companyid'] == 28) { //xcomp
          $rep_earning_deduction_cash_advance_report = "('','\\B01','','','',0,1,0,'Earning and Deduction Per Payroll Report','\\B0109',3920,'0'," . $params['levelid'] . ")";
        } else {
          $rep_earning_deduction_cash_advance_report = "('','\\B01','','','',0,1,0,'Earning and Deduction Cash Advance Report','\\B0109',3920,'0'," . $params['levelid'] . ")";
        }

        $rep_earning_deduction_list_report = "('','\\B01','','','',0,1,0,'Earning and Deduction List Report','\\B0112',4035,'0'," . $params['levelid'] . ")";
        $rep_employee_balances_report = "('','\\B01','','','',0,1,0,'Employee Balances Report','\\B0113',4036,'0'," . $params['levelid'] . ")";

        $rep_signature_sheet = "('','\\B01','','','',0,1,0,'Signature Sheet','\\B0110',4006,'0'," . $params['levelid'] . ")";
        $rep_earning_and_deduction_transaction_history = "('','\\B01','','','',0,1,0,'Earning and Deduction Transaction History','\\B0111',4007,'0'," . $params['levelid'] . ")";

        $subparent_bir_tax_report = "('','\\9','','','',0,0,0,'Bir Tax Reports','\\B02',3418,'0'," . $params['levelid'] . ")";
        $rep_payroll_tax_witheld = "('','\\B02','','','',0,1,0,'Tax Withheld Report','\\B0201',3419,'0'," . $params['levelid'] . ")";
        $rep_bir_2316 = "('','\\B02','','','',0,1,0,'BIR 2316','\\B0202',3468,'0'," . $params['levelid'] . ")";
        $rep_alphalist = "('','\\B02','','','',0,1,0,'Alphalist','\\B0203',3479,'0'," . $params['levelid'] . ")";

        $subparent_pagibig_report = "('','\\9','','','',0,0,0,'Pag Ibig Reports','\\B03',3420,'0'," . $params['levelid'] . ")";
        $rep_payroll_pagibig_remittance = "('','\\B03','','','',0,1,0,'Pag Ibig Remittance Report','\\B0301',3421,'0'," . $params['levelid'] . ")";
        $rep_payroll_pagibig_loan_payment = "('','\\B03','','','',0,1,0,'Pag Ibig Loan Payment Report','\\B0302',3422,'0'," . $params['levelid'] . ")";
        $rep_payroll_pagibig_calamity_loan_report = "('','\\B03','','','',0,1,0,'Pag Ibig Calamity Loan Report','\\B0303',5591,'0'," . $params['levelid'] . ")";

        $subparent_philhealth_report = "('','\\9','','','',0,0,0,'Philhealth Reports','\\B04',3423,'0'," . $params['levelid'] . ")";
        $rep_payroll_philhealth_remittance = "('','\\B04','','','',0,1,0,'Philhealth Remittance Report','\\B0401',3424,'0'," . $params['levelid'] . ")";

        $subparent_sss_report = "('','\\9','','','',0,0,0,'SSS Reports','\\B05',3425,'0'," . $params['levelid'] . ")";
        $rep_payroll_sss_remittance = "('','\\B05','','','',0,1,0,'SSS Remittance Report','\\B0501',3426,'0'," . $params['levelid'] . ")";
        $rep_payroll_sss_loan_payment = "('','\\B05','','','',0,1,0,'SSS Loan Payment Report','\\B0502',3427,'0'," . $params['levelid'] . ")";
        $rep_payroll_sss_calamity_loan_report = "('','\\B05','','','',0,1,0,'SSS Calamity Loan Report','\\B0503',5589,'0'," . $params['levelid'] . ")";

        $subparent_payroll_report = "('','\\9','','','',0,0,0,'Payroll Reports','\\B06',3428,'0'," . $params['levelid'] . ")";
        $rep_payslip = "('','\\B06','','','',0,1,0,'Pay Slip','\\B0601',3429,'0'," . $params['levelid'] . ")";
        $rep_payregister = "('','\\B06','','','',0,1,0,'Payroll Register','\\B0602',3430,'0'," . $params['levelid'] . ")";
        $rep_paymonthly_summary = "('','\\B06','','','',0,1,0,'Payroll Monthly Summary','\\B0603',3478,'0'," . $params['levelid'] . ")";
        $rep_totalnet_pay = "('','\\B06','','','',0,1,0,'Total Net Pay','\\B0606',4763,'0'," . $params['levelid'] . ")";
        $rep_payroll_voucher = "('','\\B06','','','',0,1,0,'Payroll Voucher','\\B0607',4764,'0'," . $params['levelid'] . ")";
        $rep_loan_deduction_report = "('','\\B06','','','',0,1,0,'Loan Deduction Report','\\B0608',4768,'0'," . $params['levelid'] . ")";
        $rep_payroll_accounting_entry_report = "('','\\B06','','','',0,1,0,'Payroll Accounting Entry','\\B0609',4802,'0'," . $params['levelid'] . ")";
        $rep_employee_demography_report = "('','\\B06','','','',0,1,0,'Employee Demography Report','\\B06010',5588,'0'," . $params['levelid'] . ")";
        $rep_empallow = "('','\\B06','','','',0,1,0,'Allowance Report','\\B06011',5234,'0'," . $params['levelid'] . ")";
        $rep_employee_birthday_list = "('','\\B06','','','',0,1,0,'Employee Birthday List','\\B06012',5592,'0'," . $params['levelid'] . ")";
        $rep_remittance_report = "('','\\B06','','','',0,1,0,'SSS PHIP HDMF Remittance Report','\\B06013',5595,'0'," . $params['levelid'] . ")";
        $rep_loan_reports = "('','\\B06','','','',0,1,0,'Loan Reports','\\B06014',5596,'0'," . $params['levelid'] . ")";
        $rep_loan_summary_report = "('','\\B06','','','',0,1,0,'Loan Summary Report','\\B06015',5598,'0'," . $params['levelid'] . ")";

        // TIME AND ATTENDANCE REPORT
        $subparent_time_attendance_report = "('','\\9','','','',0,0,0,'Time and Attendance Report','\\B07',3431,'0'," . $params['levelid'] . ")";
        $rep_dtr = "('','\\B07','','','',0,1,0,'Daily Time Record','\\B0701',3432,'0'," . $params['levelid'] . ")";
        $rep_perfect_attendance_report = "('','\\B07','','','',0,1,0,'Perfect Attendance Report','\\B0703',4759,'0'," . $params['levelid'] . ")";
        $rep_leave_balance_report = "('','\\B07','','','',0,1,0,'Leave Balance Report','\\B0704',4760,'0'," . $params['levelid'] . ")";
        $rep_leave_details_report = "('','\\B07','','','',0,1,0,'Leave Details Report','\\B0705',4761,'0'," . $params['levelid'] . ")";
        $rep_time_events_report = "('','\\B07','','','',0,1,0,'Time Events Report','\\B0706',4762,'0'," . $params['levelid'] . ")";
        $rep_timekeeping_graph_report = "('','\\B07','','','',0,1,0,'Timekeeping Graph Report','\\B07015',5211,'0'," . $params['levelid'] . ")";
        $rep_timekeeping_monthly_report = "('','\\B07','','','',0,1,0,'Timekeeping Monthly Report','\\B07017',5212,'0'," . $params['levelid'] . ")";
        $rep_late_frequency_report = "('','\\B07','','','',0,1,0,'Late Frequency Report','\\B07018',5213,'0'," . $params['levelid'] . ")";
        // DAILY TIME RECORD
        $rep_dtr_absent_report = "('','\\B07','','','',0,1,0,'Daily Time Record Absent Report','\\B0707',4901,'0'," . $params['levelid'] . ")";
        $rep_dtr_undertime_report = "('','\\B07','','','',0,1,0,'Daily Time Record Undertime Report','\\B0708',4902,'0'," . $params['levelid'] . ")";
        $rep_dtr_late_report = "('','\\B07','','','',0,1,0,'Daily Time Record Late Report','\\B0709',4903,'0'," . $params['levelid'] . ")";

        $rep_attendance_summary_report = "('','\\B07','','','',0,1,0,'Attendance Summary Report','\\B07019',5578,'0'," . $params['levelid'] . ")";
        $rep_summary_of_absences_for_13th_report = "('','\\B07','','','',0,1,0,'Summary of Absences for 13th Report','\\B07020',5597,'0'," . $params['levelid'] . ")";
        $rep_ddr = "";

        switch ($params['companyid']) {
          case 29: //sbc portal
          case 44: //stonepro
            $subparent_payroll_portal_report = "('','\\9','','','',0,0,0,'Payroll Portal Reports','\\B08',4593,'0'," . $params['levelid'] . ")";
            $rep_ot_application_report = "('','\\B08','','','',0,1,0,'OT Filling Reports','\\B0801',4594,'0'," . $params['levelid'] . ")";
            $rep_leave_application_report = "('','\\B08','','','',0,1,0,'Leave Filling Reports','\\B0802',4595,'0'," . $params['levelid'] . ")";

            $rep_changes_schedules_shift_report = "('','\\B08','','','',0,1,0,'Change Shift Schedules Reports','\\B0803',4601,'0'," . $params['levelid'] . ")";
            $rep_undertime_report = "('','\\B08','','','',0,1,0,'Undertime Reports','\\B0806',4898,'0'," . $params['levelid'] . ")";

            $rep_ob_filling_report = "('','\\B08','','','',0,1,0,'OB Filling Reports','\\B0804',4854,'0'," . $params['levelid'] . ")";
            $rep_loan_report = "('','\\B08','','','',0,1,0,'Loan Reports','\\B0805',4897,'0'," . $params['levelid'] . ")";
            break;
          case 53: //CAMERA SOUND
            $subparent_payroll_portal_report = "('','\\9','','','',0,0,0,'Payroll Portal Reports','\\B08',4593,'0'," . $params['levelid'] . ")";
            $rep_ot_application_report = "('','\\B08','','','',0,1,0,'OT Filling Reports','\\B0801',4594,'0'," . $params['levelid'] . ")";
            $rep_leave_application_report = "('','\\B08','','','',0,1,0,'Leave Filling Reports','\\B0802',4595,'0'," . $params['levelid'] . ")";
            $rep_changes_schedules_shift_report = "('','\\B08','','','',0,1,0,'Change Shift Schedules Reports','\\B0803',4601,'0'," . $params['levelid'] . ")";
            $rep_ob_filling_report = "('','\\B08','','','',0,1,0,'OB Filling Reports','\\B0804',4854,'0'," . $params['levelid'] . ")";
            $rep_loan_report = "('','\\B08','','','',0,1,0,'Loan Reports','\\B0805',4897,'0'," . $params['levelid'] . ")";

            $rep_totalnet_pay = '';
            $rep_payroll_voucher = '';
            $rep_loan_deduction_report  = '';
            $rep_payroll_accounting_entry_report = '';
            break;
          case 51: //ulitc
            $subparent_payroll_portal_report = "('','\\9','','','',0,0,0,'Payroll Portal Reports','\\B08',4593,'0'," . $params['levelid'] . ")";
            $rep_ot_application_report = "('','\\B08','','','',0,1,0,'OT Filling Reports','\\B0801',4594,'0'," . $params['levelid'] . ")";
            $rep_leave_application_report = "('','\\B08','','','',0,1,0,'Leave Filling Reports','\\B0802',4595,'0'," . $params['levelid'] . ")";
            $rep_ob_filling_report = "('','\\B08','','','',0,1,0,'OB Filling Reports','\\B0804',4854,'0'," . $params['levelid'] . ")";
            $rep_loan_report = "('','\\B08','','','',0,1,0,'Loan Reports','\\B0805',4897,'0'," . $params['levelid'] . ")";

            $rep_emp_loan_balance = '';
            $rep_emp_timein_out_logs = '';
            $rep_totalnet_pay = '';
            $rep_payroll_voucher = '';
            $rep_loan_deduction_report  = '';
            $rep_payroll_accounting_entry_report = '';

            $subparent_time_attendance_report = '';
            $rep_perfect_attendance_report = "";
            $rep_leave_balance_report = "";
            $rep_leave_details_report = "";
            $rep_time_events_report = "";

            $rep_dtr_absent_report = "";
            $rep_dtr_undertime_report = "";
            $rep_dtr_late_report = "";
            $rep_attendance_summary_report = "";
            break;
          case 43: //mighty
            $rep_trip_incentive_detailed = "('','\\B06','','','',0,1,0,'Trip Incentive Report','\\B0604',4528,'0'," . $params['levelid'] . ")";
            $rep_operator_incentive_report = "('','\\B06','','','',0,1,0,'Operator Incentive Report','\\B0605',4573,'0'," . $params['levelid'] . ")";
            break;
          case 45: //pdpi payroll
            $rep_ddr = "('','\\B07','','','',0,1,0,'Daily Deployment Record','\\B0702',4725,'0'," . $params['levelid'] . ")";
            break;
          case 58: //cdohris
            $rep_leave_category = "('','\\B01','','','',0,1,0,'Leave Category Statistics Report','\\B0114',5028,'0'," . $params['levelid'] . ")";
            $rep_loan_report = "('','\\B08','','','',0,1,0,'Loan Reports','\\B0805',4897,'0'," . $params['levelid'] . ")";
            break;
        }


        if ($params['companyid'] == 44 || $params['companyid'] == 51 || $params['companyid'] == 53) { //stonepro payrollportal | ulitc | CAMERA SOUND
          $subparent_pagibig_report = "";
          $rep_payroll_pagibig_remittance = "";
          $rep_payroll_pagibig_loan_payment = "";
          if ($params['companyid'] != 53) {
            $rep_month_pay_13th = "";
          }

          $rep_dep_advise = "";
          $rep_emp_rate_ = "";
          $rep_emp_adv_balance = "";
          $rep_earning_deduction_report = "";
          $rep_earning_deduction_cash_advance_report = "";
          $rep_earning_deduction_list_report = "";
          $rep_employee_balances_report = "";
          $rep_signature_sheet = "";
          $rep_earning_and_deduction_transaction_history = "";

          $subparent_bir_tax_report = "";
          $rep_payroll_tax_witheld = "";
          $rep_bir_2316 = "";
          $rep_alphalist = "";

          $subparent_pagibig_report = "";
          $rep_payroll_pagibig_remittance = "";
          $rep_payroll_pagibig_loan_payment = "";

          $subparent_philhealth_report = "";
          $rep_payroll_philhealth_remittance = "";

          $subparent_sss_report = "";
          $rep_payroll_sss_remittance = "";
          $rep_payroll_sss_loan_payment = "";
          $rep_payroll_sss_calamity_loan_report = "";


          $rep_payregister = "";
          $rep_paymonthly_summary = "";
        }

        if ($params['companyid'] == 45) { //pdpi payroll
          $rep_month_pay_13th = "";

          $subparent_bir_tax_report = "";
          $rep_payroll_tax_witheld = "";
          $rep_bir_2316 = "";
          $rep_alphalist = "";

          $subparent_pagibig_report = "";
          $rep_payroll_pagibig_remittance = "";
          $rep_payroll_pagibig_loan_payment = "";

          $subparent_philhealth_report = "";
          $rep_payroll_philhealth_remittance = "";

          $subparent_sss_report = "";
          $rep_payroll_sss_remittance = "";
          $rep_payroll_sss_loan_payment = "";
          $rep_payroll_sss_calamity_loan_report = "";
        }

        break;
        // case 'AIMSPOS':
        // case 'MISPOS':
        //   $parent_pos_report = "('','\\9','','','',0,0,0,'POS Reports','\\909001',3648,'0'," . $params['levelid'] . ")";
        //   $rep_pos_detailed_sales_report = "('','\\909001','','','',0,1,0,'Detailed Sales Report','\\90900101',3649,'0'," . $params['levelid'] . ")";
        //   $rep_pos_summarized_sales_report = "('','\\909001','','','',0,1,0,'Summarized Sales Report','\\90900102',3665,'0'," . $params['levelid'] . ")";
        //   $bir_e_sales = "('','\\909001','','','',0,1,0,'BIR E Sales','\\90900103',5254,'0'," . $params['levelid'] . ")";
        //   $rep_monthly_sales_analysis_report = "('','\\909001','','','',0,1,0,'Monthly Sales Analysis Report','\\90900104',5255,'0'," . $params['levelid'] . ")";
        //   $rep_annual_sales_analysis = "('','\\909001','','','',0,1,0,'Annual Sales Analysis','\\90900105',5256,'0'," . $params['levelid'] . ")";
        //   break;
    }

    if ($this->companysetup->getispos($params)) {
      $parent_pos_report = "('','\\9','','','',0,0,0,'POS Reports','\\909001',3648,'0'," . $params['levelid'] . ")";
      $rep_pos_detailed_sales_report = "('','\\909001','','','',0,1,0,'Detailed Sales Report','\\90900101',3649,'0'," . $params['levelid'] . ")";
      $rep_pos_summarized_sales_report = "('','\\909001','','','',0,1,0,'Summarized Sales Report','\\90900102',3665,'0'," . $params['levelid'] . ")";
      $bir_e_sales = "('','\\909001','','','',0,1,0,'BIR E Sales','\\90900103',5254,'0'," . $params['levelid'] . ")";
      $rep_monthly_sales_analysis_report = "('','\\909001','','','',0,1,0,'Monthly Sales Analysis Report','\\90900104',5255,'0'," . $params['levelid'] . ")";
      $rep_annual_sales_analysis = "('','\\909001','','','',0,1,0,'Annual Sales Analysis','\\90900105',5256,'0'," . $params['levelid'] . ")";
      $rep_daily_analysis_of_product = "('','\\909001','','','',0,1,0,'Daily Analysis of Product','\\90900106',5573,'0'," . $params['levelid'] . ")";
      $rep_hourly_sales_summary_report = "('','\\909001','','','',0,1,0,'Hourly Sales Summary Report','\\90900107',5575,'0'," . $params['levelid'] . ")";
      $rep_transaction_pos_report = "('','\\909001','','','',0,1,0,'Transaction POS Report','\\90900108',5574,'0'," . $params['levelid'] . ")";
      $rep_audit_trail_report = "('','\\909001','','','',0,1,0,'Audit Trail Report','\\90900109',5576,'0'," . $params['levelid'] . ")";
    }


    switch ($this->companysetup->getsystemtype($params)) {
      case 'WAIMS':
      case 'ALL':
        // WAREHOUSING
        $rep_warehousing = "('','\\C','','','',0,0,0,'Warehousing Report','\\C01',3447,'0'," . $params['levelid'] . ")";
        $rep_qr_code_generator = "('','\\C01','','','',0,1,0,'QR Code Generator','\\C0101',3448,'0'," . $params['levelid'] . ")";
        break;
      default:
        $rep_warehousing = "";
        $rep_qr_code_generator = "";
        break;
    }

    $location_list = "";
    $tenant_list = "";
    switch ($this->companysetup->getsystemtype($params)) {
      case 'MMS':
        // MALL
        $tenant_list = "('','\\900','','','',0,1,0,'Tenant List','\\90011',3531,'0'," . $params['levelid'] . ")";
        $location_list = "('','\\900','','','',0,1,0,'Location List','\\90012',3530,'0'," . $params['levelid'] . ")";
        break;
    }

    $vsched_reports = "('','\\9','','','',0,0,0,'Vehicle Scheduling Report','\\911',3537,'0'," . $params['levelid'] . ")";
    $rep_driver_list = "('','\\911','','','',0,1,0,'Driver List','\\91101',3538,'0'," . $params['levelid'] . ")";
    $rep_passenger_list = "('','\\911','','','',0,1,0,'Passenger List','\\91102',3539,'0'," . $params['levelid'] . ")";
    $rep_vehicle_list = "('','\\911','','','',0,1,0,'Vehicle List','\\91103',3540,'0'," . $params['levelid'] . ")";
    $rep_appwovlist = "('','\\911','','','',0,1,0,'Approved Without Vehicle List','\\91104',3546,'0'," . $params['levelid'] . ")";
    $rep_appwvlist = "('','\\911','','','',0,1,0,'Approved With Vehicle List','\\91105',3547,'0'," . $params['levelid'] . ")";
    $rep_vslist = "('','\\911','','','',0,1,0,'Vehicle Schedule List','\\91106',3548,'0'," . $params['levelid'] . ")";

    $parent_documenttracking = "('','\\9','','','',0,0,0,'Document Tracking Reports','\\912',3554,'0'," . $params['levelid'] . ")";
    $rep_documenttracking = "('','\\912','','','',0,1,0,'Document Tracking','\\91201',3555,'0'," . $params['levelid'] . ")";

    $parent_mancom = '';
    $rep_salesReportPerSalesGroup = '';
    $rep_salesPerSalesGroupPerPerson = '';
    $rep_salesReportPerItemGroupGraph = '';
    $rep_ytdsalesReportPerItemGroup = '';
    $rep_salesReportPerSalesGroupPerItemGroupGraph = '';
    $rep_salesReportPerSalesAgentPerItemGroupGraph = '';
    $rep_sales_per_item_group_per_sales_person = '';
    switch ($this->companysetup->getsystemtype($params)) {
      case 'AIMS':
        switch ($params['companyid']) {
          case 10: //afti
          case 12: //afti usd
            $parent_mancom = "('','\\9','','','',0,0,0,'Mancom Reports','\\913',3710,'0'," . $params['levelid'] . ")";

            $rep_salesPerSalesGroupPerPerson = "('','\\913','','','',0,1,0,'Sales Per Sales Group Per Person','\\91301',3711,'0'," . $params['levelid'] . ")";
            $rep_salesReportPerItemGroupGraph = "('','\\913','','','',0,1,0,'Sales Report Per Item Group Graph','\\91302',3712,'0'," . $params['levelid'] . ")";
            $rep_salesReportPerSalesGroupPerItemGroupGraph = "('','\\913','','','',0,1,0,'Sales Report Per Sales Group Per Item Group Graph','\\91303',3713,'0'," . $params['levelid'] . ")";
            $rep_salesReportPerSalesAgentPerItemGroupGraph = "('','\\913','','','',0,1,0,'Sales Report Per Sales Agent Per Item Group Graph','\\91304',3714,'0'," . $params['levelid'] . ")";
            $rep_ytdsalesReportPerItemGroup = "('','\\913','','','',0,1,0,'YTD Sales Report Per Item Group','\\91307',3864,'0'," . $params['levelid'] . ")";
            $rep_sales_per_item_group_per_sales_person = "('','\\913','','','',0,1,0,'Sales Per Item Group Per Sales Person','\\91308',3865,'0'," . $params['levelid'] . ")";
            break;
        }

        break;
    }

    $parent_cashier = '';
    $rep_daily_cashier_position_report = '';
    $rep_postdatedchecks = "";
    $rep_replenishment_summary = '';
    $rep_customer_ledger = '';
    $rep_cashier_collection_rep = '';
    $rep_bank_accountcoll_rep = '';
    $rep_new_daily_cashier = '';
    if ($params['companyid'] == 57) { //cdo
      $parent_cashier = "('','\\9','','','',0,0,0,'Cashier Reports','\\916',5087,'0'," . $params['levelid'] . ")";
      $rep_daily_cashier_position_report = "('','\\916','','','',0,1,0,'Daily Cashiers Position Report','\\91601',5088,'0'," . $params['levelid'] . ")";
      $rep_postdatedchecks = "('','\\916','','','',0,1,0,'Post Dated Checks','\\91602',5183,'0'," . $params['levelid'] . ")";
      $rep_replenishment_summary = "('','\\916','','','',0,1,0,'Replenishment Summary','\\91603',5184,'0'," . $params['levelid'] . ")";
      $rep_customer_ledger = "('','\\916','','','',0,1,0,'Customer Ledger','\\91604',5224,'0'," . $params['levelid'] . ")";
      $rep_cashier_collection_rep = "('','\\916','','','',0,1,0,'Collection Summary Report','\\91605',5365,'0'," . $params['levelid'] . ")";
      $rep_bank_accountcoll_rep = "('','\\916','','','',0,1,0,'Bank Account Collection Report','\\91606',5411,'0'," . $params['levelid'] . ")";
      $rep_new_daily_cashier = "('','\\916','','','',0,1,0,'Daily Cashier Report','\\91607',5420,'0'," . $params['levelid'] . ")";
    }

    //BMS
    $parent_brgy = '';
    $local_clearance_summary_report = '';
    $rep_lc_clearance_summary = '';
    $rep_issued_id_summary_report = '';
    $rep_business_clearance_summary_report = '';
    $infrastructure_clearance_summary_report = '';

    // BMS LISTING REPORTS
    $rep_business_clearance_list_report = '';
    $rep_local_clearance_list_report = '';
    $rep_working_clearance_list_report = '';

    //QUEUING
    $parent_queuing = '';
    $rep_queuing_served_perhr_report = '';
    $rep_daily_queuing_summary_per_service = '';
    $rep_queuing_per_hour_per_service_report = '';
    $rep_queuing_summary_peruser_report = '';
    $rep_queuing_summary_per_counter = '';
    $rep_service_served_per_user_report = '';
    $rep_queuing_analysis_report = '';

    switch ($this->companysetup->getsystemtype($params)) {
      case 'BMS':
        // Barangay Reports
        $parent_brgy = "('','\\9','','','',0,0,0,'Barangay Reports','\\918',5602,'0'," . $params['levelid'] . ")";
        $local_clearance_summary_report = "('','\\918','','','',0,1,0,'Local Clearance Summary Report','\\91801',5603,'0'," . $params['levelid'] . ")";
        $rep_lc_clearance_summary = "('','\\918','','','',0,1,0,'Local Clearance Summary Report','\\91806',5603,'0'," . $params['levelid'] . ")";
        $rep_issued_id_summary_report = "('','\\918','','','',0,1,0,'Issued ID Summary Report','\\91802',5604,'0'," . $params['levelid'] . ")";
        $rep_business_clearance_summary_report = "('','\\918','','','',0,1,0,'Business Clearance Summary Report','\\91807',5642,'0'," . $params['levelid'] . ")";
        $infrastructure_clearance_summary_report = "('','\\918','','','',0,1,0,'Infrastructure Clearance Summary Report','\\91808',5639,'0'," . $params['levelid'] . ")";

        // Listing Reports
        $parent_transactionlist = "('','\\9','','','',0,0,0,'Transaction List','\\909',3056,'0'," . $params['levelid'] . ")";
        $rep_business_clearance_list_report = "('','\\909','','','',0,1,0,'Business Clearance List Report','\\90901',5609,'0'," . $params['levelid'] . ")";
        $rep_local_clearance_list_report = "('','\\909','','','',0,1,0,'Local Clearance List Report','\\90902',5608,'0'," . $params['levelid'] . ")";
        $rep_working_clearance_list_report = "('','\\909','','','',0,1,0,'Working Clearance List Report','\\90903',5622,'0'," . $params['levelid'] . ")";
        break;

      case 'QUEUING':
        $parent_queuing = "('','\\9','','','',0,0,0,'Queuing Reports','\\919',5625,'0'," . $params['levelid'] . ")";
        $rep_queuing_served_perhr_report =  "('','\\919','','','',0,1,0,'Queuing Served Per Hour Report','\\91901',5626,'0'," . $params['levelid'] . ")";
        $rep_daily_queuing_summary_per_service =  "('','\\919','','','',0,1,0,'Queuing Summary Per Service','\\91902',5629,'0'," . $params['levelid'] . ")";
        $rep_queuing_summary_peruser_report =  "('','\\919','','','',0,1,0,'Queuing Summary Per User Report','\\91904',5627,'0'," . $params['levelid'] . ")";
        $rep_queuing_summary_per_counter =  "('','\\919','','','',0,1,0,'Queuing Summary Per Counter','\\91905',5628,'0'," . $params['levelid'] . ")";
        $rep_queuing_per_hour_per_service_report =  "('','\\919','','','',0,1,0,'Queuing per Hour per Service Report','\\91903',5637,'0'," . $params['levelid'] . ")";
        $rep_service_served_per_user_report =  "('','\\919','','','',0,1,0,'Service Served Per User Report','\\91906',5643,'0'," . $params['levelid'] . ")";
        $rep_queuing_analysis_report =  "('','\\919','','','',0,1,0,'Queuing Analysis Report','\\91907',5644,'0'," . $params['levelid'] . ")";
        break;
    }

    switch ($this->companysetup->getsystemtype($params)) {
      case 'DTS':
        $report_sysmenu = [
          $parent_documenttracking,
          $rep_documenttracking
        ];
        break;
      case 'MIS':
      case 'MISPOS':
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
          $rep_inventoryaging_persite,
          $rep_fastmovingitems,
          $rep_analyzeitemsaleswithprofitmarkup,
          $rep_slowmovingitems,
          $rep_itempurchasereport,
          $rep_salesperitempercustomer,
          $rep_itemtoexpired,
          $rep_itembalance_belowminimum,
          $rep_itembalance_aboveminimum,
          $rep_reorder,
          $rep_expiry_report,
          $rep_physical_inventory_sheet,
          $rep_inv_movement,
          $rep_items_negative_bal,
          $rep_non_moving_items,
          $rep_top_performing_item,
          $price_list,
          $rep_comparative_report_sales,
          $rep_sales_summary_per_item,
          $rep_sales_return_per_item,
          $rep_sales_summary_per_item_per_price,
          $rep_sched_fifo,
          $rep_physical_count_sheet_per_batch_report,
          $rep_inv_balanceperwh,
          $rep_item_sales_report,

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
          $rep_monthlysummary_outputtax,
          $rep_sales_summary_per_vat_type,
          $rep_schedulear,


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
          $rep_purchase_detailed,

          // SALES AGENT
          $parent_salesagent,
          $rep_salesagentlist,
          $rep_analyzedagentsalesmonthly,
          $rep_sales_per_agent_per_item,
          $rep_sales_per_agent,

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
          $rep_material_issuance_report,
          $rep_material_request_report,
          $subparent_inventory,
          $rep_inventorysetupreport,
          $rep_physicalcountreport,
          $rep_transferslipreport,
          $rep_inventoryadjustmentreport,
          $subparent_issuance,
          $rep_stockrequestreport,
          $rep_stocktransferreport,
          $rep_stockissuancereport,
          $rep_stockreturnreport,

          // POS REPORTS
          $parent_pos_report,
          $rep_pos_detailed_sales_report,
          $rep_pos_summarized_sales_report,
          $bir_e_sales,
          $rep_monthly_sales_analysis_report,
          $rep_annual_sales_analysis,
          $rep_daily_analysis_of_product,
          $rep_hourly_sales_summary_report,
          $rep_transaction_pos_report,
          $rep_audit_trail_report,
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
          $isperdept,
          $isperbranch,
          $isperproject,
          $isperstatement,

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
          $rep_schedulear,
          $rep_account_receivables_register,
          $rep_water_bill,
          $rep_notice_of_disconnection,
          $rep_homeowners_list,

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
          $rep_rental_payment,

          // OTHER REPORTS
          $parent_otherreports,
          $rep_statementofaccount,
          $rep_expensesreport,
          $user_access_report,
          $rep_statement_of_account_water_bill,


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
          $subparent_purchases,
          $rep_receivingreport,
          $subparent_sales,
          $rep_salesjournalreport,

          //CARGO LOGISTICS
          $parent_cargologistics,
          $rep_waybillreport,
          $rep_cargomanifestsum,
          $rep_loadingguide,
          $rep_amortization,

          //TICKETING REPORTS
          $parent_ticketingreports,
          $rep_ticketreports,
        ];
        break;
      case 'AIMS':
        $report_sysmenu = [
          $masterfile,
          $rep_chartofaccounts,
          $warehouse_list,
          $employee_list,
          $department_list,
          $project_list,
          $rep_trucklist,
          // ACCOUNTING BOOKS
          $parent_accountingbooks,
          $rep_cashdisbursementbook,
          $rep_cashreceiptbook,
          $rep_journalvoucher,
          $rep_purchasejournal,
          $rep_salesjournal,
          $rep_general_ledger,
          $rep_daily_cash_flow,
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
          $isperdept,
          $isperbranch,
          $isperproject,
          $isperstatement,
          $rep_perCostCenterReport,
          $rep_detailedPerAccountReport,
          $monthly_trial_balance,
          $rep_budgetreport,

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
          $rep_top_performing_item,
          $rep_inventory_balance_for_accounting,
          $rep_item_group_performance_report,
          $rep_inventory_per_wh_type,
          $rep_sched_fifo,
          $rep_salesreportitemhistory,
          $rep_inv_movement,
          $rep_daily_cement_withdrawal_with_total_form_report,
          $rep_daily_cement_withdrawal_without_total_form_report,
          $rep_withdrawal_summary_as_per_cost_center_report,
          $rep_fuel_withdrawal_summary_report,
          $rep_lubricant_consumption_report,
          $rep_withdrawal_summary_report,
          $rep_summary_of_withdrawals_report,
          $rep_daily_bag_report,
          $rep_sales_item_by_location,
          $rep_sales_summary_per_item,
          $rep_inv_balanceperwh,
          $rep_non_moving_items,
          $rep_inventory_balance_per_engine,
          $rep_fixedassetlist,
          $rep_ksi_reorder_report,
          $rep_inventoryreport,
          $rep_item_sales_report,

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
          $rep_sales_order_aftech,
          $rep_sales_per_product,
          $rep_sales_per_person,
          $rep_forecast_report,
          $rep_ar_per_collection_officers,
          $rep_collection_report,
          $rep_detailed_customer_ar,
          $monthly_sum_cwt,
          $rep_monthlysummary_zerorated,
          $rep_sawt_monitoring_report,
          $rep_summary_sales_report,
          $rep_schedulear,
          $rep_sales_report_with_markup,
          $rep_customer_transaction_history,
          $rep_provisional_receipt_report,
          $rep_sales_monitoring_breakdown_output_report,
          $rep_sales_monitoring_report,
          $rep_water_bill,
          $rep_notice_of_disconnection,
          $rep_homeowners_list,
          $rep_sales_vs_collection,
          $rep_ar_vs_collection,
          $rep_aging_of_accounts_receivable_report,
          $rep_mj_aging_of_accounts_receivable_report,
          $rep_pendingsomonitoring,
          $rep_salesman_cust_sales_summ,
          $rep_accounts_recievable_summ,
          $rep_aging_of_overdue_accounts_receivable_report,
          $rep_bunos_purchase_order_report,
          $rep_bunos_purchase_order_application_report,
          $rep_customer_account_analysis_report,
          $rep_bpo_application_by_customer_report,
          $rep_certificate_of_appreciation_report,
          $rep_brandsales_report,
          $rep_dsalessumm_report,
          $rep_brandsalespersize_report,

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
          $rep_item_received_per_supplier,
          $rep_po_vs_rr_serving_ratio,
          $rep_supplier_price,

          // SALES AGENT
          $parent_salesagent,
          $rep_salesagentlist,
          $rep_analyzedagentsalesmonthly,
          $rep_sales_per_agent_per_item,
          $rep_sales_per_agent,
          $rep_target_vs_actual_sales_report,

          // OTHER REPORTS
          $parent_otherreports,
          $rep_statementofaccount,
          $rep_expensesreport,
          $rep_receivingconsignmentreport,
          $user_access_report,
          $rep_sales_report,
          $rep_daily_sales_report,
          $rep_soa_afti,
          $rep_cashadvance,
          $cost_adjustment_report,
          $outsource_summary_report,
          $rep_summary_of_invoices_report,
          $rep_unncollected_creditable_withholding_tax,
          $rep_detailed_quantity_sold,
          $rep_total_consumption_per_supplier,
          $rep_total_imported_goods,
          $rep_monthly_supplier_consumption_per_agent,
          $power_consumption_daily_report,
          $rep_mc_unit_statement_of_account,
          $rep_summary_of_transaction_report,
          $uncleared_reports,
          $rep_doc_series_sales_journal_report,
          $rep_daily_sales_collection_report,
          $rep_requestorder_managementrep,
          $rep_so_and_ro_monitoring,
          $rep_inventorybal_with_pendingsalesorder,
          $rep_daily_sales_of_hgc,




          // TRANSACTION LIST
          $parent_transactionlist,
          $subparent_purchases,
          $rep_purchaserequisitionreport,
          $rep_purchaseorderreport,
          $rep_receivingreport,
          $rep_purchasereturnreport,
          $rep_canvass_sheet,
          $rep_servicereceivingreport,
          $rep_joborderreport,
          $rep_jobcompletionreport,
          $rep_outsourcereport,
          $rep_outsource_per_RFQ_report,

          $subparent_sales,
          $rep_salesorderreport,
          $rep_salesjournalreport,
          $rep_salesreturnreport,
          $rep_consign_and_outright_invoice,

          $rep_sparepartsissuance,
          $rep_mccollection,
          $rep_quotation,
          $rep_salesorderafti,
          $rep_voidsalesorderreport,
          $rep_stockissuanceaftireport,
          $rep_requestforreplacementreturn,
          $rep_salesactivityreport,
          $rep_servicesalesorderreport,
          $rep_taskerrandreport,
          $rep_request_order_report,
          $subparent_inventory,
          $rep_inventorysetupreport,
          $rep_physicalcountreport,
          $rep_transferslipreport,
          $rep_inventoryadjustmentreport,
          $subparent_production,
          $rep_prod_joborderreport,
          $rep_prodinput_report,
          $rep_material_request_report,
          $rep_material_issuance_report,
          $rep_material_issuance_report_list,
          $rep_supplies_issuance_report_list,
          $rep_finish_good_report_list,
          $subparent_payables,
          $rep_petty_cash,
          $rep_petty_cash_request,
          $rep_apsetupreport,
          $rep_apvoucherreport,
          $rep_cashcheckvoucherreport,
          $rep_encashmentreport,
          $rep_onlineencashmentreport,
          $subparent_receivables,
          $rep_arsetupreport,
          $rep_receivedpaymentreport,
          $rep_counterreceiptreport,
          $subparent_accounting,
          $rep_generaljournalreport,
          $rep_depositslipreport,
          $subparent_pcf,
          $rep_pcf_summary,
          $subparent_crm,
          $rep_Lead,
          $rep_opportunity_module,
          $rep_sjseriesreport,
          $rep_salesreportdetail,
          $rep_treceivedchecks,
          $rep_dx_depositslip,

          //CDO financing
          $subparent_cashier,
          $rep_cashier_entry_module,
          $rep_petty_cash_module,

          // POS REPORTS
          $parent_pos_report,
          $rep_pos_detailed_sales_report,
          $rep_pos_summarized_sales_report,
          $bir_e_sales,

          // MANCOM REPORTS
          $parent_mancom,
          $rep_salesReportPerSalesGroup,
          $rep_salesPerSalesGroupPerPerson,
          $rep_salesReportPerItemGroupGraph,
          $rep_salesReportPerSalesGroupPerItemGroupGraph,
          $rep_salesReportPerSalesAgentPerItemGroupGraph,
          $rep_ytdsalesReportPerItemGroup,
          $rep_sales_per_item_group_per_sales_person,

          //motorcycle reports
          $parent_motorcyclerep,
          $rep_soldout_unit,
          $rep_mc_availability,
          $rep_average_sales_per_mc_unit,
          $rep_total_sell,
          $rep_total_delivery_report,
          $rep_MC_sales_report,
          $rep_total_sales_per_mode,

          //sparepart reports
          $parent_sparepart_rep,
          $rep_category_sales_rep,
          $rep_daily_sales_rep,
          $rep_sell_per_item_rep,
          $rep_walkin_and_service_sales,
          $rep_salescategorydetailsinandout_rep,
          $rep_salesindetails_rep,
          $rep_salesperchannel_rep,
          $rep_comparison_rep,
          $rep_spareparts_rep_per_mechanic,


          //TICKETING REPORTS
          $parent_ticketingreports,
          $rep_ticketreports,

          //CASHIER REPORTS
          $parent_cashier,
          $rep_daily_cashier_position_report,
          $rep_postdatedchecks,
          $rep_replenishment_summary,
          $rep_customer_ledger,
          $rep_cashier_collection_rep,
          $rep_bank_accountcoll_rep,
          $rep_new_daily_cashier,


        ];
        break;
      case "CAIMS":
        $report_sysmenu = [
          $masterfile,
          $rep_chartofaccounts,
          $warehouse_list,
          $employee_list,
          $department_list,
          $jobrequeststatus,
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
          $monthly_trial_balance,
          $monthly_balancesheet,


          // ITEMS
          $parent_items,
          $rep_inventorybalance,
          $rep_analyzeitempurchasemonthly,
          $rep_analyzeitemsalesmonthly,
          $rep_itemlist,
          $rep_currentinventoryaging,
          $rep_analyzeitemsaleswithprofitmarkup,
          $rep_itempurchasereport,
          $rep_salesperitempercustomer,
          $rep_unserved_po,
          $rep_inv_balanceperwh,


          // CUSTOMER
          $parent_customers,
          $rep_customerlist,
          $rep_currentcustomerreceivable,
          $rep_currentcustomerreceivableaging,
          $rep_analyzecustomersalesmonthly,
          $rep_customerperformancereport,
          $rep_analyzecustomercollectionmonthly,
          $rep_salespercustomerperitem,
          $rep_salescomparison_graph,
          $rep_monthlysalesreport_graphy,
          $rep_monthlysummary_outputtax,
          $monthly_sum_ewt,
          $rep_schedulear,
          $rep_collection_report,

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
          $rep_purchase_detailed,

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
          $rep_supplierinvoicereport,
          $subparent_sales,
          //$rep_salesorderreport,
          $rep_salesjournalreport,
          //$rep_salesreturnreport,
          $rep_material_issuance_report,
          $rep_material_request_report,
          $subparent_inventory,
          $rep_inventorysetupreport,
          $rep_physicalcountreport,
          $rep_transferslipreport,
          $rep_inventoryadjustmentreport,
          $subparent_payables,
          $rep_apsetupreport,
          $rep_apvoucherreport,
          $rep_cashcheckvoucherreport,
          $rep_petty_cash,
          $subparent_receivables,
          $rep_arsetupreport,
          $rep_receivedpaymentreport,
          $rep_counterreceiptreport,
          $subparent_accounting,
          $rep_generaljournalreport,
          $rep_depositslipreport,
          $rep_debit_memo_sum,
          $rep_credit_memo_sum,
          $trans_list_subparent_construction,
          $rep_jo_list,
          $rep_jo_completion_list,
          $project_monitoring,
          $consolidated_project_cost_summary,
          $subcon_project_monitoring,
          $rep_stocktransferreport,

          // CONSTRUCTION
          $subparent_construction,
          $rep_liquidation,
          $rep_jo_summ,
          $rep_project_cost_summary,
          $comparative_expense_report,
          $rep_project_cost_analysis,
          $accounts_receivable_subcontractor_report,
          $rep_site_monitoring,
          $rep_project_cost_and_expenses,


        ];
        break;
      case 'HRIS':
        $report_sysmenu = [
          //HRIS
          $subparent_applicant,
          $rep_hris_applicant_listing,
          $rep_personnel_req,
          $rep_job_offer,
          $rep_req_train_dev,
          $rep_train_entry,
          $rep_turn_over_items,
          $rep_return_items,
          $rep_emp_stat_entry_change,
          $rep_incident_rep,
          $rep_notice_explain,
          $rep_notice_discip_action,
          $rep_gender_statistic,
          $rep_length_service,
          $rep_workplace_generation,
          $rep_timekeeping_graph_report,
          $rep_late_frequency_report,
          $rep_timekeeping_monthly_report,
          $rep_separated_employee_stat,
          $rep_monthly_turnover_rate,
          $rep_separation_breakdown_report,
          $rep_employee_count,
          $rep_employee_movement,
          $rep_reason_leaving_company,
          $rep_COE,

          //PAYROLL
          $subparent_other_report,
          $rep_payroll_employee_listing,
          $rep_emp_rate_,
          $rep_emp_timein_out_logs,
          $subparent_time_attendance_report,
          $rep_dtr,
          $subparent_payroll_portal_report,
          $rep_ot_application_report,
          $rep_leave_application_report,
          $rep_changes_schedules_shift_report,
          $rep_payroll_expense,
          $rep_empallow,
          $rep_employee_birthday_list
        ];
        break;

      case 'HRISPAYROLL':
        $report_sysmenu = [
          //HRIS
          $subparent_applicant,
          $rep_hris_applicant_listing,
          $rep_personnel_req,
          $rep_job_offer,
          $rep_req_train_dev,
          $rep_train_entry,
          $rep_turn_over_items,
          $rep_return_items,
          $rep_emp_stat_entry_change,
          $rep_incident_rep,
          $rep_notice_explain,
          $rep_notice_discip_action,
          $rep_gender_statistic,
          $rep_length_service,
          $rep_workplace_generation,
          $rep_timekeeping_graph_report,
          $rep_late_frequency_report,
          $rep_timekeeping_monthly_report,
          $rep_separated_employee_stat,
          $rep_monthly_turnover_rate,
          $rep_separation_breakdown_report,
          $rep_payroll_employee_listing,
          $rep_employee_count,
          $rep_employee_movement,
          $rep_reason_leaving_company,
          $rep_COE,
          $rep_Employee_Infraction_report,
          $rep_contract_monitoring,
          $rep_recruitment_status_report,
          $rep_stayin_employee_report,
          $rep_wage_and_career_history_report,

          // PAYROLL
          $subparent_other_report,
          $rep_month_pay_13th,
          $rep_dep_advise,
          $rep_emp_loan_balance,
          $rep_emp_rate_,
          $rep_emp_timein_out_logs,
          $subparent_bir_tax_report,
          $rep_payroll_tax_witheld,
          $rep_bir_2316,
          $subparent_pagibig_report,
          $rep_payroll_pagibig_remittance,
          $rep_payroll_pagibig_loan_payment,
          $rep_payroll_pagibig_calamity_loan_report,
          $subparent_philhealth_report,
          $rep_payroll_philhealth_remittance,
          $subparent_sss_report,
          $rep_payroll_sss_remittance,
          $rep_payroll_sss_loan_payment,
          $subparent_payroll_report,
          $rep_payslip,
          $rep_payregister,
          $rep_totalnet_pay,
          $rep_payroll_voucher,
          $rep_loan_deduction_report,
          $rep_payroll_accounting_entry_report,
          $rep_payroll_sss_calamity_loan_report,
          $rep_employee_demography_report,
          $rep_empallow,
          $rep_employee_birthday_list,
          $rep_remittance_report,
          $rep_loan_reports,
          $rep_loan_summary_report,

          // TIME AND ATTENDANCE REPORT
          $subparent_time_attendance_report,
          $rep_dtr,
          $rep_ddr,
          $rep_alphalist,
          $rep_earning_deduction_report,
          $rep_earning_deduction_list_report,
          $rep_employee_balances_report,
          $rep_emp_adv_balance,
          $rep_signature_sheet,
          $rep_earning_and_deduction_transaction_history,
          $rep_perfect_attendance_report,
          $rep_leave_balance_report,
          $rep_leave_details_report,
          $rep_time_events_report,
          $rep_dtr_absent_report,
          $rep_dtr_undertime_report,
          $rep_dtr_late_report,
          $rep_attendance_summary_report,
          $rep_summary_of_absences_for_13th_report,

          // PAYROLL PORTAL REPORTS
          $subparent_payroll_portal_report,
          $rep_ot_application_report,
          $rep_leave_application_report,
          $rep_changes_schedules_shift_report,
          $rep_ob_filling_report,
          $rep_loan_report,
          $rep_undertime_report,
          $rep_paymonthly_summary,



          // OTHER REPORTS
          $parent_otherreports,
          $login_attempt_report,
          $rep_leave_category,
          $rep_payroll_expense,

          //mighty-payroll
          $rep_trip_incentive_detailed,
          $rep_operator_incentive_report

        ];
        break;
      case 'PAYROLL':
      case 'PAYROLLPORTAL':
        $report_sysmenu = [
          // OTHER REPORTS
          $parent_otherreports,
          $login_attempt_report,
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
          $rep_bir_2316,
          $subparent_pagibig_report,
          $rep_payroll_pagibig_remittance,
          $rep_payroll_pagibig_loan_payment,
          $subparent_philhealth_report,
          $rep_payroll_philhealth_remittance,
          $subparent_sss_report,
          $rep_payroll_sss_remittance,
          $rep_payroll_sss_loan_payment,
          $rep_payroll_sss_calamity_loan_report,
          $subparent_payroll_report,
          $rep_payslip,
          $rep_payregister,
          $rep_totalnet_pay,
          $rep_payroll_voucher,
          $rep_loan_deduction_report,
          $rep_payroll_accounting_entry_report,
          $subparent_time_attendance_report,
          $rep_dtr,
          $rep_ddr,
          $rep_paymonthly_summary,
          $rep_alphalist,
          $rep_earning_deduction_report,
          $rep_earning_deduction_list_report,
          $rep_employee_balances_report,
          $rep_emp_adv_balance,
          $rep_signature_sheet,
          $rep_earning_and_deduction_transaction_history,
          $rep_perfect_attendance_report,
          $rep_leave_balance_report,
          $rep_leave_details_report,
          $rep_time_events_report,
          $rep_empallow,
          $rep_employee_birthday_list,
          $rep_loan_reports,
          $rep_loan_summary_report,

          $subparent_payroll_portal_report,
          $rep_ot_application_report,
          $rep_leave_application_report,
          $rep_changes_schedules_shift_report,
          $rep_ob_filling_report,
          $rep_loan_report,
          $rep_undertime_report,

          $rep_dtr_absent_report,
          $rep_dtr_undertime_report,
          $rep_dtr_late_report,
          $rep_summary_of_absences_for_13th_report,

          //mighty-payroll
          $rep_trip_incentive_detailed,
          $rep_operator_incentive_report


        ];
        break;
      case 'WAIMS':
        $report_sysmenu = [
          $masterfile,
          $rep_chartofaccounts,
          $warehouse_list,
          $employee_list,
          $department_list,
          $jobrequeststatus,
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
          $monthly_trial_balance,
          $monthly_balancesheet,

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
          $inv_checksheet,
          $rep_sched_fifo,
          $rep_inv_movement,
          $price_list,
          $rep_items_negative_bal,
          $rep_quantity_on_hand,
          $rep_unserved_po,
          $rep_non_moving_items,
          $rep_inventory_per_wh_type,
          $rep_inv_balanceperwh,
          $rep_fixedassetlist,


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
          $rep_schedulear,


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
          $rep_purchase_detailed,

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
          $rep_supplierinvoicereport,

          $rep_packinglistreport,
          $rep_packinglistreceivingreport,
          $rep_warrantyrequestreport,
          $rep_warrantyreceivingreport,


          $subparent_sales,
          $rep_salesreturnreport,
          $rep_salesorderdealerreport,
          $rep_salesorderbranchreport,
          $rep_salesorderonlinereport,

          $rep_salesjournaldealerreport,
          $rep_salesjournalbranchreport,
          $rep_salesjournalonlinereport,

          $rep_specialpartsrequestreport,
          $rep_specialpartsissuancereport,
          $rep_specialpartsreturnreport,

          $rep_material_issuance_report,
          $rep_material_request_report,
          $subparent_inventory,
          $rep_inventorysetupreport,
          $rep_physicalcountreport,
          $rep_transferslipreport,
          $rep_inventoryadjustmentreport,
          $subparent_payables,
          $rep_apsetupreport,
          $rep_apvoucherreport,
          $rep_cashcheckvoucherreport,
          $rep_petty_cash,
          $rep_petty_cash_request,
          $rep_petty_cash_reconciliation,
          $subparent_receivables,
          $rep_arsetupreport,
          $rep_receivedpaymentreport,
          $rep_counterreceiptreport,
          $subparent_accounting,
          $rep_generaljournalreport,
          $rep_depositslipreport,
          $rep_debit_memo_sum,
          $rep_credit_memo_sum,
          $subparent_voyage,
          $rep_voyagereport,
          $rep_listcrewpervessel,
          $rep_listvoyagereportpervessel,
          $rep_listwarehousevessels,
          $rep_vessel_document,
          $rep_nods,
          $rep_jobreq,
          $trans_list_subparent_construction,
          $rep_jo_list,
          $rep_jo_completion_list,

          // HRIS
          $subparent_applicant,
          $rep_hris_applicant_listing,
          $rep_personnel_req,
          $rep_job_offer,
          $rep_req_train_dev,
          $rep_train_entry,
          $rep_turn_over_items,
          $rep_return_items,
          $rep_emp_stat_entry_change,
          $rep_incident_rep,
          $rep_notice_explain,
          $rep_notice_discip_action,
          $rep_gender_statistic,
          $rep_length_service,
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
          $rep_bir_2316,
          $subparent_pagibig_report,
          $rep_payroll_pagibig_remittance,
          $rep_payroll_pagibig_loan_payment,
          $rep_payroll_pagibig_calamity_loan_report,
          $subparent_philhealth_report,
          $rep_payroll_philhealth_remittance,
          $subparent_sss_report,
          $rep_payroll_sss_remittance,
          $rep_payroll_sss_loan_payment,
          $rep_payroll_sss_calamity_loan_report,
          $subparent_payroll_report,
          $rep_payslip,
          $rep_payregister,
          $subparent_time_attendance_report,
          $rep_dtr,
          $rep_paymonthly_summary,
          $rep_alphalist,
          $rep_earning_deduction_report,
          $rep_emp_adv_balance,
          // mighty- payroll rep
          $rep_trip_incentive_detailed,
          $rep_operator_incentive_report,

          // WAREHOUSING
          $rep_warehousing,
          $rep_qr_code_generator,

          // CONSTRUCTION
          $subparent_construction,
          $rep_liquidation,
          $rep_jo_summ,
          $rep_project_cost_summary,
          $rep_project_cost_analysis,

          // POS REPORTS
          $parent_pos_report,
          $rep_pos_detailed_sales_report,
          $rep_pos_summarized_sales_report,

          //TICKETING REPORTS
          $parent_ticketingreports,
          $rep_ticketreports,
        ];
        break;
      case 'VSCHED':
        $report_sysmenu = [
          // MASTERFILE
          $masterfile,
          $warehouse_list,
          $employee_list,
          $department_list,

          // VEHICLE REPORT
          $vsched_reports,
          $rep_driver_list,
          $rep_passenger_list,
          $rep_vehicle_list,
          $rep_appwovlist,
          $rep_appwvlist,
          $rep_vslist,
        ];
        break;

      case 'ATI':
        $report_sysmenu = [
          // MASTERFILE
          $masterfile,
          $rep_chartofaccounts,
          $warehouse_list,
          $employee_list,
          $department_list,
          $general_item_list,

          // ITEM
          $parent_items,
          $rep_itemlist,
          $rep_fixedassetlist,
          $rep_itempurchasereport,


          // CUSTOMER
          $parent_customers,
          $rep_customerlist,

          // SUPPLIER
          $parent_supplier,
          $rep_supplierlist,
          $rep_pendingpurchaseorders,
          $rep_purchasepersupp,

          // VEHICLE REPORT
          $vsched_reports,
          $rep_driver_list,
          $rep_passenger_list,
          $rep_vehicle_list,
          $rep_appwovlist,
          $rep_appwvlist,
          $rep_vslist,

          // TRANSACTION LIST
          $parent_transactionlist,
          $subparent_purchases,
          $rep_purchaserequisitionreport,
          $rep_purchaseorderreport,
          $rep_receivingreport,
          $rep_purchasereturnreport,
          $rep_canvass_sheet,
          $rep_cashcheckvoucherreport,
          $subparent_issuance,
          $rep_stockissuancereport,
          $rep_stockreturnreport,
          $rep_purchaseOrderAssignedPO,
          $rep_purchasemergingmodulereport,
          $rep_ocr,
          $rep_osi,

          // OTHER REPORTS
          $parent_otherreports,
          $gatepass_out,
          $gatepass_return,
          $asset_depreciation,
          $asset_location,
          $asset_receiving,
          $asset_issuance,
          $asset_accountability_report,

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
          $rep_approvedcanvass,
          $rep_generateassettag,
          $rep_paymentreleased,
          $rep_advancesmonitoring
        ];
        break;
      case 'AIMSPAYROLL':
        $report_sysmenu = [
          //AIMS
          $masterfile,
          $rep_chartofaccounts,
          $warehouse_list,
          $employee_list,
          $department_list,
          $project_list,
          // ACCOUNTING BOOKS
          $parent_accountingbooks,
          $rep_cashdisbursementbook,
          $rep_cashreceiptbook,
          $rep_journalvoucher,
          $rep_purchasejournal,
          $rep_salesjournal,
          $rep_general_ledger,
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
          $isperdept,
          $isperbranch,
          $isperproject,
          $isperstatement,

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
          $rep_top_performing_item,
          $rep_inventory_balance_for_accounting,
          $rep_item_group_performance_report,
          $rep_inventory_per_wh_type,
          $rep_sched_fifo,
          $rep_salesreportitemhistory,
          $rep_inv_movement,
          $rep_inv_balanceperwh,
          $rep_fixedassetlist,

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
          $rep_sales_order_aftech,
          $rep_sales_per_product,
          $rep_sales_per_person,
          $rep_forecast_report,
          $rep_ar_per_collection_officers,
          $rep_collection_report,
          $rep_detailed_customer_ar,
          $monthly_sum_cwt,
          $rep_monthlysummary_zerorated,
          $rep_sawt_monitoring_report,
          $rep_summary_sales_report,
          $rep_schedulear,
          $rep_sales_report_with_markup,
          $rep_customer_transaction_history,
          $rep_pendingsomonitoring,
          $rep_sales_vs_collection,
          $rep_ar_vs_collection,
          $rep_lah_sales_collection_report,
          $rep_paid_accounts_report,



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
          $rep_item_received_per_supplier,

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
          $rep_sales_report,
          $rep_daily_sales_report,
          $rep_soa_afti,
          $rep_cashadvance,
          $cost_adjustment_report,
          $outsource_summary_report,
          $rep_summary_of_invoices_report,
          $rep_unncollected_creditable_withholding_tax,
          $rep_detailed_quantity_sold,
          $rep_total_consumption_per_supplier,
          $rep_total_imported_goods,
          $rep_monthly_supplier_consumption_per_agent,
          $rep_customer_registration_report,
          $rep_reseller_status_summary_report,
          $rep_dailytask_report,
          $rep_task_monitoring_report,


          // TRANSACTION LIST
          $parent_transactionlist,
          $subparent_purchases,
          $rep_purchaserequisitionreport,
          $rep_purchaseorderreport,
          $rep_receivingreport,
          $rep_purchasereturnreport,
          $rep_canvass_sheet,
          $rep_servicereceivingreport,
          $rep_joborderreport,
          $rep_jobcompletionreport,
          $rep_outsourcereport,
          $rep_outsource_per_RFQ_report,
          $subparent_sales,
          $rep_salesorderreport,
          $rep_salesjournalreport,
          $rep_salesreturnreport,
          $rep_material_issuance_report,
          $rep_material_request_report,
          $rep_quotation,
          $rep_salesorderafti,
          $rep_voidsalesorderreport,
          $rep_stockissuanceaftireport,
          $rep_requestforreplacementreturn,
          $rep_salesactivityreport,
          $rep_servicesalesorderreport,
          $rep_taskerrandreport,
          $subparent_inventory,
          $rep_inventorysetupreport,
          $rep_physicalcountreport,
          $rep_transferslipreport,
          $rep_inventoryadjustmentreport,
          $subparent_payables,
          $rep_petty_cash,
          $rep_petty_cash_request,
          $rep_petty_cash_reconciliation,
          $rep_apsetupreport,
          $rep_apvoucherreport,
          $rep_cashcheckvoucherreport,
          $rep_encashmentreport,
          $rep_onlineencashmentreport,
          $subparent_receivables,
          $rep_arsetupreport,
          $rep_receivedpaymentreport,
          $rep_counterreceiptreport,
          $subparent_accounting,
          $rep_generaljournalreport,
          $rep_depositslipreport,
          $subparent_crm,
          $rep_Lead,
          $rep_opportunity_module,
          $rep_sjseriesreport,
          $rep_salesreportdetail,
          $rep_operatorhistory,

          // POS REPORTS
          $parent_pos_report,
          $rep_pos_detailed_sales_report,
          $rep_pos_summarized_sales_report,
          $bir_e_sales,
          $rep_monthly_sales_analysis_report,
          $rep_daily_analysis_of_product,
          $rep_hourly_sales_summary_report,
          $rep_transaction_pos_report,
          $rep_annual_sales_analysis,
          $rep_audit_trail_report,

          // MANCOM REPORTS
          $parent_mancom,
          $rep_salesReportPerSalesGroup,
          $rep_salesPerSalesGroupPerPerson,
          $rep_salesReportPerItemGroupGraph,
          $rep_salesReportPerSalesGroupPerItemGroupGraph,
          $rep_salesReportPerSalesAgentPerItemGroupGraph,
          $rep_ytdsalesReportPerItemGroup,
          $rep_sales_per_item_group_per_sales_person,



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
          $rep_bir_2316,
          $subparent_pagibig_report,
          $rep_payroll_pagibig_remittance,
          $rep_payroll_pagibig_loan_payment,
          $rep_payroll_pagibig_calamity_loan_report,
          $subparent_philhealth_report,
          $rep_payroll_philhealth_remittance,
          $subparent_sss_report,
          $rep_payroll_sss_remittance,
          $rep_payroll_sss_loan_payment,
          $rep_payroll_sss_calamity_loan_report,
          $subparent_payroll_report,
          $rep_payslip,
          $rep_payregister,
          $subparent_time_attendance_report,
          $rep_dtr,
          $rep_paymonthly_summary,
          $rep_alphalist,
          $rep_earning_deduction_report,
          $rep_earning_deduction_cash_advance_report,
          $rep_emp_adv_balance,
          $rep_employee_balances_report,
          $rep_earning_and_deduction_transaction_history,
          $rep_signature_sheet,
          $rep_time_events_report,
          $rep_empallow,
          $rep_employee_birthday_list,
          $rep_loan_reports,
          $rep_summary_of_absences_for_13th_report,
          $rep_loan_summary_report,



          // mighty- payroll rep
          $rep_trip_incentive_detailed,
          $rep_operator_incentive_report,

          // TRIP 
          $parent_trip,
          $rep_trip_detailed,
          $rep_trip_summary,
          $rep_staff_trip_summary
        ];
        break;

      case 'EAPPLICATION':
        $report_sysmenu = [
          $masterfile,
          $rep_plan_types,

          // ACCOUNTING BOOKS
          $parent_accountingbooks,
          $rep_cashreceiptbook,

          // CHECK MONITORING REPORTS
          $parent_checkmonitoringreports,
          $rep_receivedchecks,


          // CUSTOMER
          $parent_customers,
          $rep_customerlist,
          $rep_currentcustomerreceivable,
          $rep_currentcustomerreceivableaging,
          $rep_analyzecustomersalesmonthly,
          $rep_customersalesreport,
          $rep_analyzecustomercollectionmonthly,
          $rep_salespercustomerperitem,
          $rep_salescomparison_graph,
          $rep_monthlysalesreport_graphy,
          $rep_sales_per_plan_type,
          $rep_eappsalesreport,


          // SALES AGENT
          $parent_salesagent,
          $rep_salesagentlist,
          $rep_analyzedagentsalesmonthly,
          $rep_sales_per_agent,

          // OTHER REPORTS
          $parent_otherreports,
          $rep_statementofaccount,
          $user_access_report,
          $login_attempt_report,
          $data_requirement_report,



          // TRANSACTION LIST
          $parent_transactionlist,
          $subparent_receivables,
          $rep_receivedpaymentreport,

          $subparent_operation,
          $rep_application_list,
          $rep_lifeplan_agrement_list,
        ];
        break;
      case 'LENDING':
        $report_sysmenu = [
          $masterfile,
          $rep_chartofaccounts,
          // ACCOUNTING BOOKS
          $parent_accountingbooks,
          $rep_cashdisbursementbook,
          $rep_cashreceiptbook,
          $rep_journalvoucher,
          $rep_purchasejournal,
          $rep_salesjournal,
          $rep_general_ledger,
          $rep_daily_cash_flow,
          // CHECK MONITORING REPORTS
          $parent_checkmonitoringreports,
          $rep_bouncedchecks,
          $rep_issuedchecks,
          $rep_receivedchecks,
          $rep_undepositedchecks,
          $rep_list_of_printed_checks,
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
          $monthly_balancesheet,
          $monthly_trial_balance,

          // CUSTOMER
          $parent_customers,
          $rep_customerlist,
          $rep_currentcustomerreceivable,
          $rep_currentcustomerreceivableaging,
          $rep_analyzecustomersalesmonthly,
          $rep_analyzecustomercollectionmonthly,
          $rep_monthlysummary_outputtax,
          $monthly_sum_ewt,
          $rep_schedulear,
          $rep_aging_of_accounts_receivable_report,
          $rep_collectionsummary,
          $rep_collection_report,
          $rep_collectibles_report,

          // SUPPLIER
          $parent_supplier,
          $rep_supplierlist,
          $rep_currentsupplierpayables,
          $rep_currentsupplierpayablesaging,
          $rep_analyzedsupplierpurchasesmonthly,
          $rep_withholdingtax,

          // SALES AGENT
          $parent_salesagent,
          $rep_salesagentlist,
          $rep_analyzedagentsalesmonthly,
          $rep_sales_per_agent_per_item,
          $rep_sales_per_agent,

          // OTHER REPORTS
          $parent_otherreports,
          $rep_statementofaccount,
          $rep_expensesreport,
          $user_access_report,
          $rep_comparative_total_loan_releases,

          // TRANSACTION LIST
          $parent_transactionlist,
          $subparent_payables,
          $rep_petty_cash,
          $rep_petty_cash_request,
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

          // LENDING
          $subparent_lending,
          $rep_application_form,
          $rep_loan_summary,


        ];
        break;

      case 'BMS':
        $report_sysmenu = [
          $parent_brgy,
          // barangay Reports
          $local_clearance_summary_report,
          $rep_lc_clearance_summary,
          $rep_issued_id_summary_report,
          $rep_business_clearance_summary_report,
          $infrastructure_clearance_summary_report,

          // BMS LISTING REPORTS
          $parent_transactionlist,
          $rep_business_clearance_list_report,
          $rep_working_clearance_list_report,
          $rep_local_clearance_list_report,
        ];
        break;


      case 'QUEUING':
        $report_sysmenu = [
          $parent_queuing,

          $rep_queuing_served_perhr_report,
          $rep_daily_queuing_summary_per_service,
          $rep_queuing_per_hour_per_service_report,
          $rep_queuing_summary_per_counter,
          $rep_queuing_summary_peruser_report,
          $rep_queuing_summary_per_counter,
          $rep_service_served_per_user_report,
          $rep_queuing_analysis_report,
        ];
        break;
      default:
        $report_sysmenu = [
          $masterfile,
          $rep_chartofaccounts,
          $warehouse_list,
          $employee_list,
          $department_list,
          $project_list,
          $jobrequeststatus,
          $tenant_list,
          $location_list,
          $general_item_list,
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
          $monthly_trial_balance,
          $monthly_balancesheet,


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
          $inv_checksheet,
          $rep_sched_fifo,
          $rep_inv_movement,
          $price_list,
          $rep_items_negative_bal,
          $rep_quantity_on_hand,
          $rep_unserved_po,
          $rep_non_moving_items,
          $rep_sales_return_per_item,
          $rep_sales_summary_per_item,
          $rep_expiry_report,
          $rep_physical_inventory_sheet,
          $rep_inventory_per_wh_type,
          $rep_sales_summary_per_item_per_price,
          $rep_schedule_of_inventory,
          $rep_stock_on_hand_per_warehouse,
          $rep_stocktransfer_summary_peritem,
          $rep_item_min_max_listing,
          $rep_item_list_with_cost,
          $rep_inv_balanceperwh,
          $rep_inventory_balance_per_engine,
          $rep_item_list_with_id,
          $rep_fixedassetlist,

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
          $rep_schedulear,
          $rep_aging_of_accounts_receivable_report,
          $rep_aging_of_accounts_receivable_report_without_udf,
          $rep_unservedsalesorder,
          $rep_unservedstockreq,
          $rep_sales_vs_collection,
          $rep_ar_vs_collection,

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
          $rep_purchase_detailed,
          $rep_po_vs_rr_serving_ratio,
          $rep_cert_of_creditable_tax,

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
          $login_attempt_report,
          $rep_daily_sales_report,
          $rep_sales_report,
          $gatepass_out,
          $gatepass_return,
          $asset_depreciation,
          $asset_location,
          $asset_receiving,
          $asset_issuance,
          $sales_and_collection_remittance_report,
          $sales_transmittal_report,
          $lotavailabilityreport,
          $uncleared_reports,
          $rep_outofstock_sj_pos,
          $inhouse_sales_report,
          $homeworks_sales_report,
          $summary_vendor_report,
          $homeworks_for_sir_jon_report,
          $rep_requestorder_managementrep,
          $rep_so_and_ro_monitoring,
          $rep_inventorybal_with_pendingsalesorder,
          $rep_daily_sales_of_hgc,


          // TRANSACTION LIST
          $parent_transactionlist,
          $subparent_purchases,
          $rep_purchaserequisitionreport,
          $rep_purchaseorderreport,
          $rep_receivingreport,
          $rep_purchasereturnreport,
          $rep_supplierinvoicereport,
          $subparent_sales,
          $rep_salesorderreport,
          $rep_salesjournalreport,
          $rep_salesreturnreport,
          $rep_drreturnreport,
          $rep_deliveryreceiptreport,
          $rep_material_issuance_report,
          $rep_material_request_report,
          $subparent_inventory,
          $rep_inventorysetupreport,
          $rep_physicalcountreport,
          $rep_transferslipreport,
          $rep_inventoryadjustmentreport,
          $subparent_payables,
          $rep_apsetupreport,
          $rep_apvoucherreport,
          $rep_cashcheckvoucherreport,
          $rep_petty_cash,
          $rep_petty_cash_request,
          $rep_petty_cash_reconciliation,
          $subparent_receivables,
          $rep_arsetupreport,
          $rep_receivedpaymentreport,
          $rep_counterreceiptreport,
          $subparent_accounting,
          $rep_generaljournalreport,
          $rep_depositslipreport,
          $rep_debit_memo_sum,
          $rep_credit_memo_sum,
          $subparent_voyage,
          $rep_voyagereport,
          $rep_listcrewpervessel,
          $rep_listvoyagereportpervessel,
          $rep_listwarehousevessels,
          $rep_vessel_document,
          $rep_nods,
          $rep_jobreq,
          $trans_list_subparent_construction,
          $rep_jo_list,
          $rep_jo_completion_list,
          $rep_canvass_sheet,
          $rep_operatorhistory,
          //#1436
          $subparent_issuance,
          $rep_stockrequestreport,
          $rep_stocktransferreport,
          $rep_stockissuancereport,
          $rep_stockreturnreport,

          // LENDING
          $subparent_lending,
          $rep_application_form,
          $rep_loan_approval,
          $rep_loan_summary,
          //CDO financing
          $subparent_cashier,
          $rep_cashier_entry_module,
          $rep_petty_cash_module,


          //#1447
          $parent_school_system_reports,
          $rep_summaryofquarterlyaveragereport,
          $rep_classrecordreport,
          $rep_summaryofgradesreport,
          $rep_student_list,

          //STUDENT
          $parent_students,
          $rep_currentstudentreceivable,
          $rep_currentstudentreceivableaging,
          $rep_studentsalesreport,
          $rep_studentSOA,
          $rep_studentreceivedpayment,

          // HRIS
          $subparent_applicant,
          $rep_hris_applicant_listing,
          $rep_personnel_req,
          $rep_job_offer,
          $rep_req_train_dev,
          $rep_train_entry,
          $rep_turn_over_items,
          $rep_return_items,
          $rep_emp_stat_entry_change,
          $rep_incident_rep,
          $rep_notice_explain,
          $rep_notice_discip_action,
          $rep_gender_statistic,
          $rep_length_service,
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
          $rep_bir_2316,
          $subparent_pagibig_report,
          $rep_payroll_pagibig_remittance,
          $rep_payroll_pagibig_loan_payment,
          $rep_payroll_pagibig_calamity_loan_report,
          $subparent_philhealth_report,
          $rep_payroll_philhealth_remittance,
          $subparent_sss_report,
          $rep_payroll_sss_remittance,
          $rep_payroll_sss_loan_payment,
          $rep_payroll_sss_calamity_loan_report,
          $subparent_payroll_report,
          $rep_payslip,
          $rep_payregister,
          $rep_loan_summary_report,
          // TIME ATTENDANCE REPORT
          $subparent_time_attendance_report,
          $rep_dtr,
          $rep_paymonthly_summary,
          $rep_alphalist,
          $rep_earning_deduction_report,
          $rep_emp_adv_balance,
          $rep_payroll_accounting_entry_report,
          $rep_dtr_absent_report,
          $rep_dtr_undertime_report,
          $rep_dtr_late_report,
          $rep_empallow,
          $rep_employee_birthday_list,
          $rep_loan_reports,
          $rep_summary_of_absences_for_13th_report,



          $subparent_payroll_portal_report,
          $rep_ot_application_report,
          $rep_leave_application_report,
          $rep_changes_schedules_shift_report,
          $rep_ob_filling_report,

          // mighty- payroll rep
          $rep_trip_incentive_detailed,
          $rep_operator_incentive_report,

          // WAREHOUSING
          $rep_warehousing,
          $rep_qr_code_generator,

          // CONSTRUCTION
          $subparent_construction,
          $rep_liquidation,
          $rep_jo_summ,
          $rep_project_cost_summary,
          $rep_project_cost_analysis,
          $rep_construction_order,
          $rep_project_completion_report,


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

          // POS REPORTS
          $parent_pos_report,
          $rep_pos_detailed_sales_report,
          $rep_pos_summarized_sales_report,
          $bir_e_sales,
          $rep_monthly_sales_analysis_report,
          $rep_annual_sales_analysis,
          $rep_daily_analysis_of_product,
          $rep_transaction_pos_report,
          $rep_audit_trail_report,

          //motorcyle reports
          $parent_motorcyclerep,
          $rep_soldout_unit,
          $rep_mc_availability,
          $rep_average_sales_per_mc_unit,
          $rep_total_sell,
          $rep_total_delivery_report,
          $rep_MC_sales_report,
          $rep_total_sales_per_mode,

          // TRIP 
          $parent_trip,
          $rep_trip_detailed,
          $rep_trip_summary,
          $rep_staff_trip_summary,

          //sparepart reports
          $parent_sparepart_rep,
          $rep_category_sales_rep,
          $rep_daily_sales_rep,
          $rep_sell_per_item_rep,
          $rep_walkin_and_service_sales,
          $rep_comparison_rep,
          $rep_spareparts_rep_per_mechanic,

          //Realstate construction reports
          $rep_construction_instruction,
          $rep_construction_order,
          $rep_project_completion_report,
          $rep_materialtransfer_report,

          // CRM
          $subparent_customer_support,
          $rep_ticket,

          //CARGO LOGISTICS
          $parent_cargologistics,
          $rep_waybillreport,
          $rep_cargomanifestsum,
          $rep_loadingguide,
          $rep_amortization,

          //TICKETING REPORTS
          $parent_ticketingreports,
          $rep_ticketreports,
        ];
        break;
    }
    // PUSH HERE FOR STANDARD REPORTS

    // SALES AGENT
    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'HRISPAYROLL':
      case 'PAYROLL':
      case 'PAYROLLPORTAL':
      case 'FAMS':
      case 'DTS':
        break;

      default:
        array_push($report_sysmenu, $rep_top_performing_sales_agent);
        break;
    }

    return $report_sysmenu;
  } //end function



}//end class
