<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;

class setprefixdoc
{

  private $coreFunctions;
  private $companysetup;
  private $othersClass;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
  } //end fn



  public function setupdoc($params)
  {
    $qtmodule = 'Product Quotation';
    $somodule = 'Sales Order';
    $sjmodule = 'Sales Journal';

    $agentmod = 'Agent';

    switch ($params['companyid']) {
      case 20:
        $qtmodule = 'Quotation';
        $somodule = 'Job Order';
        break;
      case 34:
        $agentmod = 'Employee';
        break;
      case 48: //seastar
        $sjmodule = 'Way Bill';
        break;
    }

    $menu['masterfile'] = [
      'IT' => ['prefix' => 'IT', 'title' => 'Stockcard'],
      'CL' => ['prefix' => 'CL', 'title' => 'Customer'],
      'SL' => ['prefix' => 'SL', 'title' => 'Supplier'],
      'WH' => ['prefix' => 'WH', 'title' => 'Warehouse'],
      'AG' => ['prefix' => 'AG', 'title' => $agentmod],

      'DE' => ['prefix' => 'DEP', 'title' => 'Department'],
      'TL' => ['prefix' => 'TL', 'title' => 'Tenant'],
      'BH' => ['prefix' => 'BR', 'title' => 'Branch'],
      'BG' => ['prefix' => 'BG', 'title' => 'Barangay Ledger'],
      'EP' => ['prefix' => 'EM', 'title' => 'Employee Record'],

    ];
    //'EM' => ['prefix' => 'EM', 'title' => 'Employee'],
    if ($params['companyid'] == 58) { //cdo
      $menu['masterfile']['EM'] = ['prefix' => 'EM', 'title' => 'Employee Record'];
    } else {
      $menu['masterfile']['EM'] = ['prefix' => 'EM', 'title' => 'Employee'];
    }


    $systype = $this->companysetup->getsystemtype($params);
    if ($systype == 'BMS') { //cdo
      $menu['masterfile']['IF'] = ['prefix' => 'IF', 'title' => 'Infrastructure Ledger'];
      $menu['masterfile']['TL'] = ['prefix' => 'TL', 'title' => 'TRU Ledger'];
      $menu['masterfile']['BG'] = ['prefix' => 'BG', 'title' => 'Barangay Member'];
      $menu['masterfile']['BU'] = ['prefix' => 'BU', 'title' => 'Business Ledger'];
    }


    $menu['purchase'] = [
      'PR' => ['prefix' => 'PR', 'title' => 'Purchase Requisition'],
      'CD' => ['prefix' => 'CD', 'title' => 'Canvass Sheet'],
      'PO' => ['prefix' => 'PO', 'title' => 'Purchase Order'],
      'RR' => ['prefix' => 'RR', 'title' => 'Receiving Report'],
      'DM' => ['prefix' => 'DM', 'title' => 'Purchase Return'],
      'SN' => ['prefix' => 'SN', 'title' => 'Supplier Invoice'],
      'JB' => ['prefix' => 'JB', 'title' => 'Job Order'],
      'AC' => ['prefix' => 'AC', 'title' => 'Job Completion'],
      'SR' => ['prefix' => 'SR', 'title' => 'Service Receving'],
      'OS' => ['prefix' => 'OS', 'title' => 'Outsource Module'],
      // fams
      'PF' => ['prefix' => 'PF', 'title' => 'Purchase Order'],
      'RA' => ['prefix' => 'RA', 'title' => 'Receiving Report'],
      'OQ' => ['prefix' => 'OQ', 'title' => 'Oracle Code Request'],
      'OM' => ['prefix' => 'OSI', 'title' => 'OSI'],
      'LQ' => ['prefix' => 'LQ', 'title' => 'Cash Liquidation Form'],

      // goodfound
      'PU' => ['prefix' => 'PU', 'title' => 'Material Purchase Order'],
      'RU' => ['prefix' => 'RU', 'title' => 'Material Receiving Report'],
    ];

    $menu['payable'] = [
      'AP' => ['prefix' => 'AP', 'title' => 'AP Setup'],
      'PV' => ['prefix' => 'PV', 'title' => 'AP Voucher'],
      'CV' => ['prefix' => 'CV', 'title' => 'Cash/Check Voucher'],
      'PQ' => ['prefix' => 'PQ', 'title' => 'Petty Cash Request'],
      'SV' => ['prefix' => 'SV', 'title' => 'Petty Cash Voucher'],
      'KP' => ['prefix' => 'KP', 'title' => 'Counter Receipt (AP)']
    ];

    $menu['sales'] = [
      'SO' => ['prefix' => 'SO', 'title' => $somodule],
      'SQ' => ['prefix' => 'SO', 'title' => 'Sales Order'],
      'SJ' => ['prefix' => 'SJ', 'title' => $sjmodule],
      'MJ' => ['prefix' => 'SJ', 'title' => 'Sales Journal'],
      'BO' => ['prefix' => 'BO', 'title' => 'Bad Order'],
      'CM' => ['prefix' => 'CM', 'title' => 'Sales Return'],
      'QT' => ['prefix' => 'QT', 'title' => $qtmodule],
      'QS' => ['prefix' => 'QS', 'title' => 'Quotation'],
      'AO' => ['prefix' => 'SS', 'title' => 'Service Sales Order'],
      'AI' => ['prefix' => 'SI', 'title' => 'Service Invoice'],
      'TE' => ['prefix' => 'TE', 'title' => 'Tast/Errand'],
      'VT' => ['prefix' => 'VT', 'title' => 'Void Sales Order'],
      'VS' => ['prefix' => 'VS', 'title' => 'Void Service Sales Order'],
      'SU' => ['prefix' => 'SU', 'title' => 'Stock Issuance'],
      'RF' => ['prefix' => 'RF', 'title' => 'Request for Replacement/Return'],
      'RO' => ['prefix' => 'RP', 'title' => 'Request Order'],
      'CH' => ['prefix' => 'SI', 'title' => 'Consign Invoice'],
      'ON' => ['prefix' => 'OI', 'title' => 'Outright Invoice']
    ];

    switch ($params['companyid']) {

      case '40': //cdo
        $menu['receivable'] = [
          'AR' => ['prefix' => 'AR', 'title' => 'AR Setup'],
          'CR' => ['prefix' => 'CR', 'title' => 'Received Payment'],
          'KR' => ['prefix' => 'KR', 'title' => 'Counter Receipt'],
          'RC' => ['prefix' => 'RC', 'title' => 'Received Checks'],
          'MC' => ['prefix' => 'MC', 'title' => 'MC Collection']
        ];
        break;
      case 59: //roosevelt
        $menu['receivable'] = [
          'AR' => ['prefix' => 'AR', 'title' => 'AR Setup'],
          'CR' => ['prefix' => 'CR', 'title' => 'Received Payment'],
          'KR' => ['prefix' => 'KR', 'title' => 'Counter Receipt'],
          'RC' => ['prefix' => 'RC', 'title' => 'Received Checks'],
          'RD' => ['prefix' => 'RD', 'title' => 'Deposit Slip'],
          'BE' => ['prefix' => 'BE', 'title' => 'Bounced Cheque Entry'],
          'RE' => ['prefix' => 'RE', 'title' => 'Replacement Cheque'],
          'RH' => ['prefix' => 'RH', 'title' => 'Received Cash']
        ];
        break;
      default:
        $menu['receivable'] = [
          'AR' => ['prefix' => 'AR', 'title' => 'AR Setup'],
          'CR' => ['prefix' => 'CR', 'title' => 'Received Payment'],
          'KR' => ['prefix' => 'KR', 'title' => 'Counter Receipt'],
          'RC' => ['prefix' => 'RC', 'title' => 'Received Checks']
        ];
        break;
    }

    $menu['accounting'] = [
      'GJ' => ['prefix' => 'GJ', 'title' => 'General Journal'],
      'DS' => ['prefix' => 'DS', 'title' => 'Deposit Slip'],
      'GD' => ['prefix' => 'GD', 'title' => 'Debit Memo'],
      'GC' => ['prefix' => 'GC', 'title' => 'Credit Memo'],
      'FS' => ['prefix' => 'FS', 'title' => 'Financing Module']
    ];

    $menu['inventory'] = [
      'PC' => ['prefix' => 'PC', 'title' => 'Physical Count'],
      'AJ' => ['prefix' => 'AJ', 'title' => 'Inventory Adjustment'],
      'TS' => ['prefix' => 'TS', 'title' => 'Transfer Slip'], //Other prefix used in TS: TP (Replenish per Pallet), TI (Replenish per Item)
      'IS' => ['prefix' => 'IS', 'title' => 'Inventory Setup'],

      'VA' => ['prefix' => 'VA', 'title' => 'Voyage Report'],
      'AT' => ['prefix' => 'AT', 'title' => 'Actual Count']
    ];


    $menu['issuance'] = [
      'TR' => ['prefix' => 'TR', 'title' => 'Stock Request'],
      'ST' => ['prefix' => 'ST', 'title' => 'Stock Transfer'],
      'SS' => ['prefix' => 'SS', 'title' => 'Stock Issuance'],
      'SP' => ['prefix' => 'SS', 'title' => 'Stock RETURN'],
    ];


    $menu['customersupport'] = ['CA' => ['prefix' => 'CA', 'title' => 'Create Ticket']];


    $menu['schoolsystem'] = [
      'EC' => ['prefix' => 'EC', 'title' => 'Curriculum'],
      'ES' => ['prefix' => 'ES', 'title' => 'Schedule'],
      'ET' => ['prefix' => 'ET', 'title' => 'Assessment Setup'],
      'EA' => ['prefix' => 'EA', 'title' => 'College Assessment'],
      'EI' => ['prefix' => 'EI', 'title' => 'Grades School Assessment'],
      'ER' => ['prefix' => 'ER', 'title' => 'Registration'],
      'IN' => ['prefix' => 'IN', 'title' => 'Instructor'],
      'ED' => ['prefix' => 'ED', 'title' => 'Add/Drop'],
      'EG' => ['prefix' => 'EG', 'title' => 'Student Grade Entry'],
      'EF' => ['prefix' => 'EF', 'title' => 'Grade Setup'],
      'EH' => ['prefix' => 'EH', 'title' => 'Grade Entry'],
      'EN' => ['prefix' => 'EN', 'title' => 'Attendance Entry'],
      'EJ' => ['prefix' => 'EJ', 'title' => 'Report Card'],
      'EK' => ['prefix' => 'EK', 'title' => 'Student Report Card']
    ];

    $menu['payrolltransaction'] = [
      'EM' => ['prefix' => 'EM', 'title' => 'Employee'],
      'EL' => ['prefix' => 'EL', 'title' => 'LOAN APPLICATION'],
    ];

    $menu['hris'] = [
      'HA' => ['prefix' => 'HA', 'title' => 'REQUEST TRAINING AND DEVELOPMENT'],
      'HC' => ['prefix' => 'HC', 'title' => 'CLEARANCE'],
      'HD' => ['prefix' => 'HD', 'title' => 'NOTICE OF DISCIPLINARY ACTION'],
      'HI' => ['prefix' => 'HI', 'title' => 'INCIDENT REPORT'],
      'HO' => ['prefix' => 'HO', 'title' => 'TURN OVER OF ITEMS'],
      'HR' => ['prefix' => 'HR', 'title' => 'RETURN OF ITEMS'],
      'HT' => ['prefix' => 'HT', 'title' => 'TRAINING ENTRY'],
      'HQ' => ['prefix' => 'HQ', 'title' => 'PERSONNEL REQUISITION'],
      'HN' => ['prefix' => 'HN', 'title' => 'NOTICE TO EXPLAIN'],
      'HJ' => ['prefix' => 'HJ', 'title' => 'JOB OFFER'],
      'HS' => ['prefix' => 'HS', 'title' => 'EMPLOYEE STATUS CHANGE'],
      'RS' => ['prefix' => 'RS', 'title' => 'RE-ASSIGNMENT'],
      'QN' => ['prefix' => 'QN', 'title' => 'QUESTIONAIRE']
    ];

    $menu['construction'] = [
      'PM' => ['prefix' => 'PM', 'title' => 'PROJECT MANAGEMENT'],
      'BQ' => ['prefix' => 'BQ', 'title' => 'BILL OF QUANTITY'],
      'JR' => ['prefix' => 'JR', 'title' => 'JOB REQUEST'],
      'JO' => ['prefix' => 'JO', 'title' => 'JOB ORDER'],
      'JC' => ['prefix' => 'JC', 'title' => 'JOB COMPLETION'],
      'PB' => ['prefix' => 'PB', 'title' => 'PROGRESS BILLING'],
      'RQ' => ['prefix' => 'RQ', 'title' => 'PURCHASE REQUISITION(CONST)'],
      'MR' => ['prefix' => 'MR', 'title' => 'MATERIAL REQUEST'],
      'MI' => ['prefix' => 'MI', 'title' => 'MATERIAL ISSUANCE'],
      'MT' => ['prefix' => 'MT', 'title' => 'MATERIAL TRANSFER'],
      'BR' => ['prefix' => 'BR', 'title' => 'BUDGET REQUEST'],
      'BL' => ['prefix' => 'BL', 'title' => 'BUDGET LIQUIDATION'],
      'WC' => ['prefix' => 'WC', 'title' => 'WORK ACCOMPLISHMENT'],
      'BA' => ['prefix' => 'BA', 'title' => 'BILLING ACCOMPLISHMENT'],
      'CT' => ['prefix' => 'CT', 'title' => 'CONSTRUCTION INSTRUCTION'],
      'CC' => ['prefix' => 'CC', 'title' => 'CONSTRUCTION ORDER'],
      'PN' => ['prefix' => 'PN', 'title' => 'PROJECT COMPLETION']
    ];

    $menu['warehousing'] = [
      'PL' => ['prefix' => 'PL', 'title' => 'PACKING LIST'],
      'RP' => ['prefix' => 'RP', 'title' => 'PACKING LIST RECEIVING'],
      'FT' => ['prefix' => 'FT', 'title' => 'FORWARDER/TRUCK'],
      'SA' => ['prefix' => 'SA', 'title' => 'SALES ORDER DELEAR'],
      'SB' => ['prefix' => 'SB', 'title' => 'SALES ORDER BRANCH'],
      'SC' => ['prefix' => 'SC', 'title' => 'SALES ORDER ONLINE'],
      'SG' => ['prefix' => 'SG', 'title' => 'SPECIAL PARTS REQUEST'],
      'SH' => ['prefix' => 'SH', 'title' => 'SPECIAL PARTS ISSUANCE'],
      'SI' => ['prefix' => 'SR', 'title' => 'SPECIAL PARTS RETURN'],

      'SD' => ['prefix' => 'SD', 'title' => 'SALES JOURNAL DELEAR'],
      'SE' => ['prefix' => 'SE', 'title' => 'SALES JOURNAL BRANCH'],
      'SF' => ['prefix' => 'SF', 'title' => 'SALES JOURNAL ONLINE'],

      'WA' => ['prefix' => 'WA', 'title' => 'WARRANTY REQUEST'],
      'WB' => ['prefix' => 'WB', 'title' => 'WARRANTY RECEIVE']
    ];

    $menu['consignment'] = [
      'CN' => ['prefix' => 'CN', 'title' => 'CONSIGNMENT REQUEST'],
      'CO' => ['prefix' => 'CO', 'title' => 'CONSIGNMENT DR'],
      'CS' => ['prefix' => 'CS', 'title' => 'CONSIGNMENT SALES'],
    ];

    $menu['crm'] = [
      'LD' => ['prefix' => 'LD', 'title' => 'LEAD'],
      'OP' => ['prefix' => 'OP', 'title' => 'Sales Activity'],
    ];

    $menu['pcf'] = [
      'PX' => ['prefix' => 'PX', 'title' => 'Proj Costing Expenses Setup']
    ];

    $menu['documentmanagement'] = [
      'DT' => ['prefix' => 'DT', 'title' => 'DOCUMENT MANAGEMENT']
    ];

    $menu['vehiclescheduling'] = [
      'DL' => ['prefix' => 'DL', 'title' => 'DRIVER'],
      'PL' => ['prefix' => 'PL', 'title' => 'PASSENGER'],
      'VR' => ['prefix' => 'VR', 'title' => 'VEHICLE SCHEDULE REQUEST'],
      'VL' => ['prefix' => 'VR', 'title' => 'LOGISTIC'],
    ];

    $menu['fams'] = [
      'GP' => ['prefix' => 'GP', 'title' => 'GATE PASS HISTORY'],
      'FC' => ['prefix' => 'FC', 'title' => 'CONVERT TO ASSET'],
      'FI' => ['prefix' => 'FI', 'title' => 'ISSUE ITEMS'],
    ];

    $menu['production'] = [
      'PI' => ['prefix' => 'PI', 'title' => 'PRODUCTION INSTRUCTION'],
      'PD' => ['prefix' => 'PD', 'title' => 'PRODUCTION ORDER'],
      'RM' => ['prefix' => 'RM', 'title' => 'RAW MATERIAL USAGE'],
      'RN' => ['prefix' => 'RN', 'title' => 'SUPPLIES ISSUANCE'],
      'FG' => ['prefix' => 'FG', 'title' => 'FINISH GOODS ENTRY'],
      'JP' => ['prefix' => 'JP', 'title' => 'JOB ORDER'], //NTE
      'PG' => ['prefix' => 'PG', 'title' => 'PRODUCTION INPUT'], //NTE
    ];

    $menu['operation'] = [
      'AF' => ['prefix' => 'AF', 'title' => 'Application Form'],
      'CP' => ['prefix' => 'CP', 'title' => 'Life Plan Agreement'],
      'LP' => ['prefix' => 'LP', 'title' => 'Lease Provision'],
      'GB' => ['prefix' => 'GB', 'title' => 'Generate Billing'],
      'MB' => ['prefix' => 'MB', 'title' => 'Accounting Entry for Billing']
    ];

    $menu['kwhmonitoring'] = [
      'PW' => ['prefix' => 'PW', 'title' => 'POWER CONSUMPTION ENTRY'],
    ];

    $menu['waterbilling'] = [ //AQUAMAX
      'WM' => ['prefix' => 'WM', 'title' => 'WATER CONSUMPTION'],
      'WN' => ['prefix' => 'WN', 'title' => 'WATER CONNECTION'],
    ];

    switch ($params['companyid']) {
      case 16: //ati
        $menu['purchase']['MM'] = ['prefix' => 'MM', 'title' => 'Merging Barcode'];
        break;

      case 39:
        $menu['purchase']['RT'] = ['prefix' => 'RT', 'title' => 'Temporary RR'];
        $menu['purchase']['DI'] = ['prefix' => 'DI', 'title' => 'Discrepancy Notice'];
        $menu['purchase']['PH'] = ['prefix' => 'PH', 'title' => 'Price Change'];
        $menu['purchase']['SM'] = ['prefix' => 'SM', 'title' => 'Supplier Invoice'];

        $menu['sales']['CK'] = ['prefix' => 'CK', 'title' => 'Request For Sales Return'];
        $menu['sales']['DP'] = ['prefix' => 'DP', 'title' => 'Dispatch Schedule'];
        $menu['sales']['SK'] = ['prefix' => 'SK', 'title' => 'Sales Invoice'];
        $menu['sales']['DR'] = ['prefix' => 'DR', 'title' => 'Delivery Receipt'];
        $menu['sales']['DN'] = ['prefix' => 'DN', 'title' => 'DR Return'];

        $menu['payable']['PY'] = ['prefix' => 'PY', 'title' => 'Payment List'];
        $menu['payable']['PS'] = ['prefix' => 'PS', 'title' => 'PL Summary'];

        $menu['receivable']['KA'] = ['prefix' => 'KA', 'title' => 'AR Audit'];
        $menu['receivable']['DC'] = ['prefix' => 'DC', 'title' => 'Daily Collection Report'];

        break;

      case 40:
        $menu['sales']['CI'] = ['prefix' => 'CI', 'title' => 'Spare Parts Issuance'];
        $menu['masterfile']['FP'] = ['prefix' => 'FP', 'title' => 'Financing Partner'];
        break;

      case 43: //mighty
        $menu['sales']['MR'] = ['prefix' => 'MR', 'title' => 'MATERIAL REQUEST'];
        $menu['sales']['MI'] = ['prefix' => 'MI', 'title' => 'MATERIAL ISSUANCE'];
        $menu['sales']['EQ'] = ['prefix' => 'EQ', 'title' => 'Equipment Monitoring'];

        $menu['payrolltransaction']['TI'] = ['prefix' => 'TI', 'title' => 'Tripping Incentive'];
        $menu['payrolltransaction']['OI'] = ['prefix' => 'OI', 'title' => 'Operator Incentive'];
        break;

      case 48: //seastar
        $menu['sales']['LL'] = ['prefix' => 'LL', 'title' => 'Loading List'];
        $menu['accounting']['FA'] = ['prefix' => 'FA', 'title' => 'Fixed Asset Schedule'];
        break;

      case 50: //unitech industry
        $menu['production']['PE'] = ['prefix' => 'PE', 'title' => 'Production Request'];
        $menu['production']['PN'] = ['prefix' => 'PN', 'title' => 'Production Completion'];
        $menu['production']['PK'] = ['prefix' => 'PK', 'title' => 'Production Return'];
        $menu['production']['PI'] = ['prefix' => 'PI', 'title' => 'Production Instruction'];
        break;

      case 57: // cdo financing
        $menu['cashier']['CE'] = ['prefix' => 'CE', 'title' => 'Cashier Entry'];
        $menu['cashier']['TC'] = ['prefix' => 'TC', 'title' => 'Petty Cash Entry'];
        $menu['cashier']['DX'] = ['prefix' => 'DX', 'title' => 'Deposit Slip'];
        break;

      case 0:
        // STANDARD SERVICE TICKETING
        $menu['serviceticketing']['TA'] = ['prefix' => 'TA', 'title' => 'Ticket Application'];
        $menu['serviceticketing']['WO'] = ['prefix' => 'TA', 'title' => 'Work Order'];
        break;
    }

    //lending
    $menu['lending'] = [
      'LE' => ['prefix' => 'LE', 'title' => 'Lending Application'],
      'LA' => ['prefix' => 'LA', 'title' => 'Loan Approval']
    ];

    // pos
    $menu['pos'] = [
      'PA' => ['prefix' => 'PA', 'title' => 'Price Scheme'],
      'PP' => ['prefix' => 'PP', 'title' => 'Promo Per Item']
    ];

    $menu['othertransaction'] = [
      'VI' => ['prefix' => 'VI', 'title' => 'Violation']
    ];
    $menu['barangayoperation'] = [
      'BD' => ['prefix' => 'BD', 'title' => 'Local of Clearance'],
      'BC' => ['prefix' => 'BC', 'title' => 'Business Clearance'],
      'BT' => ['prefix' => 'BT', 'title' => 'T.R.U Clearace'],
      'BI' => ['prefix' => 'BI', 'title' => 'Infrastructure Clearace']

    ];

    $menu['accountutilities']['RG'] = ['prefix' => 'RG', 'title' => 'Company Rules and Guidelines'];



    $modules = $this->companysetup->getmodule($params);
    $prefix = $this->othersClass->array_only($menu, $modules);
    return $this->execute($prefix);
  } // end function


  private function execute($arr)
  {
    foreach ($arr as $key => $value) {
      // $this->coreFunctions->LogConsole(json_encode($value));
      foreach ($value as $key2 => $value2) {

        $check = $this->coreFunctions->datareader("select psection as value from profile where doc='SED' and psection=?", [$key2]);
        if ($check == '') {
          $data = ['doc' => 'SED', 'psection' => $key2, 'pvalue' => $value2['prefix'], 'master' => $value2['title']];
          $this->coreFunctions->sbcinsert('profile', $data);
        } else {
          $check = $this->coreFunctions->datareader("select master as value from profile where doc='SED' and psection=?", [$key2]);
          if ($check == '' || $check != $value2['title']) {
            $data = ['master' => $value2['title']];
            $this->coreFunctions->sbcupdate('profile', $data, ['doc' => 'SED', 'psection' => $key2]);
          }
        }
      }
    }
  }
}// end class
