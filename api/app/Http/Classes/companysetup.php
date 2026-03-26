<?php

namespace App\Http\Classes;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class companysetup
{

  private $coreFunctions;

  private $multibranch;
  private $clientlength;
  private $locationlength;
  private $invdoc;
  public $documentlength = 15;
  private $barcodelength;
  private $tax;
  private $serial;
  public $isexpiry;
  private $checkbelowcost;
  private $wh;
  private $module;
  private $companyname;
  private $systemtype;
  private $branchaccess;
  private $payroll_bonusmax;
  private $payroll_daysInMonth;
  private $sjitemlimit;
  private $ispallet;
  private $iscreateversion;
  private $issupplierinvoice;
  public $ispricescheme;
  public $isproject;
  private $isconsign;
  private $iseditsortline;
  private $isissuance;
  private $ispurchasedisc;
  private $ispurchases;
  private $isrecalc;
  private $isshortcutjo;
  private $isshortcutpo;
  private $isshortcutso;
  private $isshortcutdr;
  private $isshortcutcd;
  private $isshortcutmr;
  private $istodo;
  private $isuomamt;
  private $isiteminfo;
  private $ismirrormasters = false;
  private $isacctgentry = true;
  private $fifoexpiration = false;
  private $defaultcurrency;
  private $reportpathdefault = "\Http\Classes\modules\modulereport\main\\";
  private $manualpath = "/images/manual/sbc/pdf/";
  private $logopath = "/images/reports/";
  private $isshowmanual = false;
  private $reportpath;
  public $leavestart = '2022-01-01';
  public $leaveend = '2022-12-31';
  public $autoaj;
  public $generateapv = false;
  public $isvatexpurch = false;
  public $isvatexsales = false;
  public $isdocyr = false;
  public $iskgs = false;
  public $restrictip = false;
  public $periodic;
  public $issalesdisc;
  public $isfa;
  public $iscrm;
  public $ispcf;
  public $ispos;
  public $ispr;
  public $isautoaj;
  public $isrecalcamt_changeuom = false;
  public $isdefaultuominout;
  public $isshareinv;
  public $masterlimit = 25;
  public $transactionlimit = 150;
  public $pricetype = 'LatestPrice';
  public $rptfont = 'Century Gothic';
  public $isrefillts = false;
  public $isdiscperqty = false;
  public $isfirstpageheader = false;
  public $isglc = false;
  public $invonly = false;
  public $waterbilling = false;
  public $clientitem = false;
  public $posmodule = [];
  public $isautosaveacctgstock = false;
  public $showselectadd = false;
  public $collapsiblehead = true;
  public $showloading = false;
  public $daysdue = 1;
  public $peraccountar = false;
  public $usecamera = false;
  public $validfiledate = 1;
  public $timekeeping = false;
  public $mobilemodules = [];
  public $mobileparents = [];
  public $showserialrem = false;
  public $isconstruction = false;
  public $crforcebal = false;
  public $customerperagent = false;
  private $linearapproval = false;
  private $isticketing = false;
  private $isserviceticketing = false;
  private $showdept = false;
  public $leavelabel = 'Hours';
  public $autosendemail = false;
  public $isshowtsso = false;
  public $multiallow = false;
  public $isnonserial = false;
  public $isWindowsPayroll = false;
  public $itembatch = 0;
  public $dashboardwh = true;
  public $isupdatabasebtable = false;
  public $istaskmonitoring = false;
  public $ispendingapp = false;
  public $ismysql8 = false;
  public $socketserver = '';
  public $socketnotify = false;
  public $lookupclientpermodule = true;
  public $ispayrollportal = false;


  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
  } //end fn

  //put here for documentation
  // Listing of company id and name
  // default = 0
  //1 = vitaline


  private function companylist($params)
  {
    $this->multibranch = false;
    $this->branchaccess = 0;
    $this->fifoexpiration = false;
    $this->isfa = true;
    $this->ispallet = false;
    $this->defaultcurrency = 'P';
    $this->issupplierinvoice = false;
    $this->ispricescheme = false;
    $this->isconsign = false;
    $this->iscrm = false;
    $this->ispcf = false;
    $this->isshortcutpo = false;
    $this->isshortcutso = false;
    $this->isshortcutdr = false;
    $this->isshortcutcd = false;
    $this->isshortcutmr = false;
    $this->isissuance = false;
    $this->ispurchases = false;
    $this->payroll_bonusmax = 10000;
    $this->payroll_daysInMonth = 26;
    $this->sjitemlimit = 0;
    $this->reportpath = $this->reportpathdefault;
    $this->ispos = false;
    $this->isrecalc = false;
    $this->isuomamt = false;
    $this->isshortcutjo = false;
    $this->isiteminfo = false;
    $this->istodo = false;
    $this->isvatexsales = false;
    $this->isvatexpurch = false;
    $this->serial = false;
    $this->autoaj = false;
    $this->isacctgentry = true;
    $this->isdocyr = false;
    $this->iskgs = false;
    $this->restrictip = false;
    $this->ispurchasedisc = true; //if perpetual this should be false
    $this->issalesdisc = true; //if perpetual this should be false
    $this->iseditsortline = false;
    $this->periodic = false;
    $this->isrecalcamt_changeuom = false;
    $this->isdefaultuominout = false;
    $this->masterlimit = 500;
    $this->transactionlimit = 150;
    $this->isshareinv = false;
    $this->rptfont = 'Century Gothic';
    $this->pricetype = 'LatestPrice';
    $this->isglc = false;
    $this->invonly = false;
    $this->waterbilling = false;
    $this->isautosaveacctgstock = false;
    $this->showselectadd = false;
    $this->collapsiblehead = true;
    $this->showloading = false;
    $this->daysdue = 8;
    $this->peraccountar = true;
    $this->usecamera = false;
    $this->validfiledate = 1;
    $this->timekeeping = false;
    $this->mobilemodules = [];
    $this->mobileparents = [];
    $this->showserialrem = false;
    $this->isconstruction = false;
    $this->linearapproval = false;
    $this->isticketing = false;
    $this->leavelabel = 'Hours';
    $this->autosendemail = false;
    $this->ispr = false;
    $this->isshowtsso = false;
    $this->isnonserial = false;
    $this->isWindowsPayroll = false;
    $this->itembatch = 0;
    $this->dashboardwh = true;
    $this->isupdatabasebtable = false;
    $this->ispendingapp = false;
    $this->istaskmonitoring = false;
    $this->ismysql8 = false;
    $this->socketserver = '';
    $this->socketnotify = false;
    $this->lookupclientpermodule = true;
    $this->ispayrollportal = false;

    switch ($params['companyid']) {
      case 64:  //excilin
        $this->clientlength = 8;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'EXCILIN';
        $this->systemtype = 'AIMS';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->masterlimit = 500;
        $this->isglc = true;
        $this->branchaccess = 0;
        $this->multibranch = false;
        $this->reportpath = "\Http\Classes\modules\modulereport\\excilin\\";
        $this->logopath = "public/images/excilin/";
        $this->ismysql8 = true;
        break;
      case 63:  //ericco
        $this->clientlength = 8;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'EVERYTHING RETAIL INDL. & COML.';
        $this->systemtype = 'AIMS';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->masterlimit = 500;
        $this->isglc = true;
        $this->branchaccess = 0;
        $this->multibranch = false;
        $this->reportpath = "\Http\Classes\modules\modulereport\\ericco\\";
        $this->logopath = "public/images/ericco/";
        $this->restrictip = true;
        $this->ismysql8 = true;
        $this->iseditsortline = true;
        break;
      case 62: //onesky
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 10;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'ONESKY';
        $this->systemtype = 'HRISPAYROLL';
        $this->isexpiry = false;
        $this->payroll_daysInMonth = 24;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->multiallow = true;
        $this->leavelabel = 'Days';
        $this->reportpath = "\Http\Classes\modules\modulereport\onesky\\";
        $this->logopath = "public/images/onesky/";
        $this->istodo = false;
        $this->dashboardwh = false;
        $this->isupdatabasebtable = true;
        $this->ismysql8 = true;
        $this->ispendingapp = true;
        break;
      case 61: //bytesized - ms joy company
        $this->multibranch = true;
        $this->clientlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'Bytesized IT Solutions';
        $this->systemtype = 'AIMSHRISPAYROLL';
        $this->branchaccess = 1;
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->issupplierinvoice = true;
        $this->isshortcutpo = true;
        $this->isshareinv = true;
        $this->ispr = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\bytesized\\";
        $this->logopath = "public/images/bytesized/";
        $this->ismysql8 = true;
        break;
      case 60:  //transpower
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 10;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'Transpower';
        $this->systemtype = 'AIMS';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->masterlimit = 500;
        $this->isglc = true;
        $this->branchaccess = 1;
        $this->multibranch = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\transpower\\";
        $this->logopath = "public/images/demo/";
        $this->restrictip = true;
        $this->isshareinv = true;
        $this->pricetype = 'CustomerGroup';
        $this->istaskmonitoring = false;
        $this->ismysql8 = true;
        break;
      case 59:  //roosevelt
        $this->clientlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'Roosevelt Chemical Inc.';
        $this->systemtype = 'AIMS';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->iscreateversion = false;
        $this->pricetype = 'CustomerGroup';
        $this->isfa = false;
        $this->masterlimit = 500;
        $this->isglc = true;
        $this->isshortcutpo = true;
        $this->isshortcutso = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\roosevelt\\";
        $this->logopath = "public/images/roosevelt/";
        $this->ismysql8 = true;
        $this->socketserver = ''; //http://localhost:3000 ; http://nodejs.sbc.ph:25384
        break;
      case 58: //cdocycles hrispayroll 'portal email pword: @CDOportal2025
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 10;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'CDO2CYCLES';
        $this->systemtype = 'HRISPAYROLL';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->multiallow = true;
        $this->leavelabel = 'Days';
        $this->reportpath = "\Http\Classes\modules\modulereport\cdohris\\";
        $this->logopath = "public/images/cdohris/";
        $this->istodo = false;
        $this->dashboardwh = false;
        $this->ispendingapp = true;
        $this->ispayrollportal = true;
        break;
      case 57: //cdocycles financing
        $this->clientlength = 15;
        $this->locationlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = true;
        $this->companyname = 'CDO2CYCLES';
        $this->systemtype = 'AIMS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->ispricescheme = false;
        $this->isconsign = false;
        $this->isshortcutpo = true;
        $this->iscrm = false;
        $this->ispos = false;
        $this->isshortcutjo = false;
        $this->autoaj = false;
        $this->istodo = false;
        $this->restrictip = false;
        $this->periodic = false;
        $this->ispurchasedisc = true;
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->invonly = false;
        $this->clientitem = false;
        $this->daysdue = 7;
        $this->isvatexsales = false;
        $this->multibranch = true;
        $this->branchaccess = 1;
        $this->reportpath = "\Http\Classes\modules\modulereport\cdo\\";
        $this->showserialrem = false;
        $this->isshareinv = true;
        break;
      case 56: //HOMEWORKS
        $this->clientlength = 0;
        $this->documentlength = 20;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'HOMEWORKS';
        $this->systemtype = 'AIMSPOS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isshortcutso = true;
        $this->isshortcutpo = true;
        $this->ispricescheme = true;
        $this->isconsign = false;
        $this->ispos = true;
        $this->isglc = true;
        $this->multibranch = true;
        $this->isdocyr = true;
        $this->generateapv = true;
        $this->branchaccess = 1;
        $this->showloading = false;
        $this->isshareinv = true;
        $this->pricetype = 'CustomerGroupLatest';
        $this->reportpath = "\Http\Classes\modules\modulereport\homeworks\\";
        $this->logopath = "public/images/homeworks/";
        break;
      case 55: //AFLI Lending
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'AFLI';
        $this->systemtype = 'LENDING';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->iscrm = false;
        $this->logopath = "/images/afli/";
        $this->reportpath = "\Http\Classes\modules\modulereport\afli\\";
        $this->crforcebal = true;
        $this->isautosaveacctgstock = false;
        $this->showloading = true;
        break;
      case 54: //CABIAWAN ENTERPISES
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'CABIAWAN ENT.';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->iscrm = false;
        $this->pricetype = 'CustomerGroup';
        break;
      case 53: //CAMERA
        $this->clientlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'EPTMI';
        $this->systemtype = 'PAYROLLPORTAL';
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->leavelabel = 'Days';
        $this->isWindowsPayroll = true;
        $this->dashboardwh = false;
        $this->reportpath = "\Http\Classes\modules\modulereport\\camera\\";
        $this->ispayrollportal = true;
        break;
      case 52:  //technolab
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'Technolab Diagnostic Solutions Inc.';
        $this->systemtype = 'AIMS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->masterlimit = 500;
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->iseditsortline = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\technolab\\";
        break;
      case 51: //ULITC PORTAL -single approver "loginlogostyle":"width:240px;","mainlogostyle":"width:230px;margin:auto;","mainlogodivmargin":"margin-top:150px;","mainlogodivheight":"height:120px"
        $this->clientlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'ULITC';
        $this->systemtype = 'PAYROLLPORTAL';
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->leavelabel = 'Days';
        $this->isWindowsPayroll = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\ulitc\\";
        $this->logopath = "public/images/ulitc/";
        $this->ispayrollportal = true;
        break;
      case 50: //UNITECH
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'UNITECH PLASTIC INDUSTRY CORP.';
        $this->systemtype = 'AIMS';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isexpiry = false;
        $this->fifoexpiration = false;
        $this->multibranch = false;
        $this->branchaccess = 0;
        $this->isshortcutpo = true;
        $this->isshortcutso = true;
        $this->isrecalcamt_changeuom = true;
        $this->isdefaultuominout = true;
        $this->pricetype = 'CustomerGroupLatest';
        $this->reportpath = "\Http\Classes\modules\modulereport\\unitech\\";
        $this->logopath = "public/images/unitech/";
        break;
      case 49: //hotmix - ms joy cebu
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'HOTMIX';
        $this->systemtype = 'AIMS';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isexpiry = true;
        $this->fifoexpiration = true;
        $this->multibranch = true;
        $this->branchaccess = 1;
        $this->isshareinv = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\hotmix\\";
        break;
      case 48: //seastar - ms joy cebu
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'R.ERP';
        $this->systemtype = 'AMS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->restrictip = true;
        $this->showloading = false;
        $this->reportpath = "\Http\Classes\modules\modulereport\\seastar\\";
        break;
      case 47: //kitchenstar
        $this->clientlength = 10;
        $this->locationlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'Kitchen Star Corp.';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->ispricescheme = false;
        $this->isconsign = false;
        $this->isshortcutpo = true;
        $this->iscrm = false;
        $this->ispos = false;
        $this->isshortcutjo = false;
        $this->autoaj = false;
        $this->istodo = false;
        $this->restrictip = false;
        $this->periodic = false;
        $this->ispurchasedisc = true;
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->invonly = false;
        $this->clientitem = false;
        $this->daysdue = 7;
        $this->isvatexsales = false;
        $this->timekeeping = false;
        $this->crforcebal = true;
        $this->customerperagent = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\kitchenstar\\";
        $this->logopath = "public/images/kitchenstar/";
        $this->isshortcutso = true;
        $this->pricetype = 'CustomerGroupLatest';
        $this->rptfont = 'Arial';
        //$this->sjitemlimit = 15;
        break;
      case 46: //morningsteel
        $this->clientlength = 15;
        $this->locationlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'Morning Steel Sales Enterprises';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->ispricescheme = false;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->iscrm = false;
        $this->ispos = false;
        $this->isshortcutjo = false;
        $this->autoaj = false;
        $this->istodo = false;
        $this->restrictip = false;
        $this->periodic = false;
        $this->ispurchasedisc = true;
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->invonly = false;
        $this->isvatexsales = false;
        $this->reportpath = "\Http\Classes\modules\modulereport\\msse\\";
        break;
      case 45: //PDPI Payroll
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'PDPI';
        $this->systemtype = 'PAYROLL';
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        break;
      case 44: //STONEPRO "loginlogostyle":"width:250px;","mainlogostyle":"width:240px;margin:auto;","mainlogodivmargin":"margin-top:80px;","mainlogodivheight":"height:55px"
        $this->clientlength = 10;
        $this->documentlength = 0;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'STONEPRO TRADING CORP.';
        $this->systemtype = 'PAYROLLPORTAL';
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->leavelabel = 'Days';
        $this->isWindowsPayroll = true;
        $this->ispayrollportal = true;
        break;
      case 43: //mighty
        $this->multibranch = true;
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'R.ERP';
        $this->systemtype = 'AIMSPAYROLL';
        $this->branchaccess = 1;
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 26;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isshortcutpo = true;
        $this->isshortcutmr = true;
        $this->isshareinv = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\mighty\\";
        break;
      case 42: //pdpi
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 10;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'PDPI';
        $this->systemtype = 'MIS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->masterlimit = 500;
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->invonly = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\pdpi\\";
        $this->logopath = "/images/pdpi/";
        break;
      case 41: //labsolparanaque //manila
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'LabSolution Technologies Inc.';
        $this->systemtype = 'AIMS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->masterlimit = 500;
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->iseditsortline = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\labsol\\";
        break;
      case 40: //cdocycles aims
        $this->clientlength = 15;
        $this->locationlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = true;
        $this->companyname = 'CDO2CYCLES';
        $this->systemtype = 'AIMS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->ispricescheme = false;
        $this->isconsign = false;
        $this->isshortcutpo = true;
        $this->iscrm = false;
        $this->ispos = false;
        $this->isshortcutjo = false;
        $this->autoaj = false;
        $this->istodo = false;
        $this->restrictip = false;
        $this->periodic = false;
        $this->ispurchasedisc = true;
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->invonly = false;
        $this->clientitem = false;
        $this->daysdue = 7;
        $this->isvatexsales = false;
        $this->multibranch = true;
        $this->branchaccess = 1;
        $this->posmodule = ['ci'];
        $this->reportpath = "\Http\Classes\modules\modulereport\cdo\\";
        $this->showserialrem = false;
        $this->isshareinv = true;
        break;
      case 39: //CBBSI
        $this->clientlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'CBBSI';
        $this->systemtype = 'AIMSPOS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isissuance = true;
        $this->isshortcutpo = true;
        $this->isshortcutso = true;
        $this->isshortcutdr = true;
        $this->isshortcutpo = true;
        $this->ispricescheme = true;
        $this->isconsign = false;
        $this->ispos = true;
        $this->autoaj = true;
        $this->isrecalc = true;
        $this->isglc = true;
        $this->ispr = true;
        $this->pricetype = 'CustomerGroupLatest';
        $this->reportpath = "\Http\Classes\modules\modulereport\cbbsi\\";
        $this->isshowmanual = true;
        $this->multibranch = true;
        $this->branchaccess = 1;
        break;
      case 38: //CHEFS and BAKERS
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'Chefs N Bakers';
        $this->systemtype = 'MISPOS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isissuance = false;
        $this->ispricescheme = true;
        $this->isconsign = false;
        $this->ispos = true;
        $this->isacctgentry = false;
        $this->autoaj = true;
        $this->invonly = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\main\\";
        break;
      case 37: //MEGA CRYSTAL PACKAGING CORPORATION
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'MCPC';
        $this->systemtype = 'AIMSPOS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->iscrm = false;
        $this->ispos = true;
        $this->isuomamt = true;
        $this->isdefaultuominout = true;
        $this->isfirstpageheader = true;
        $this->ismirrormasters = true;
        $this->pricetype = 'CustomerGroup';
        $this->reportpath = "\Http\Classes\modules\modulereport\mcpc\\";
        break;
      case 36: //ROZLAB
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'ROZ LAB';
        $this->systemtype = 'AIMS';
        $this->isexpiry = true;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isshortcutso = true;
        $this->isshortcutpo = true;
        $this->isconsign = false;
        $this->fifoexpiration = true;
        $this->isdiscperqty = true;
        $this->masterlimit = 5000;
        $this->manualpath = "/images/manual/sbc/pdf/";
        $this->isshowmanual = true;
        $this->pricetype = 'CustomerGroup';
        $this->reportpath = "\Http\Classes\modules\modulereport\\rozlab\\";
        $this->isglc = true;
        break;
      case 35: //AQUAMAX
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'AQUAMAX';
        $this->systemtype = 'AMS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isissuance = true;
        $this->isshortcutso = true;
        $this->ispricescheme = true;
        $this->isconsign = false;
        $this->waterbilling = true;
        $this->pricetype = 'CustomerGroup';
        $this->reportpath = "\Http\Classes\modules\modulereport\aquamax\\";
        break;
      case 34: //evergreen
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 10;
        $this->tax = 12;
        $this->companyname = 'ELSI';
        $this->systemtype = 'EAPPLICATION';
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isconsign = false;
        $this->reportpath = "\Http\Classes\modules\modulereport\\eapp\\";
        $this->logopath = "/images/evergreen/";
        $this->crforcebal = true;
        $this->customerperagent = true;
        break;
      case 33: //fifipet
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'FIFIPET';
        $this->systemtype = 'AIMS';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isconsign = false;
        break;
      case 32: //3m
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = '3M';
        $this->systemtype = 'AIMS';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isconsign = false;
        $this->isrefillts = true;
        $this->isuomamt = true;
        $this->isrecalcamt_changeuom = true;
        $this->isdefaultuominout = true;
        $this->isdiscperqty = true;
        $this->pricetype = 'CustomerGroup';
        $this->reportpath = "\Http\Classes\modules\modulereport\\mmm\\";
        $this->masterlimit = 5000;
        break;
      case 31: //JLI
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'JLI';
        $this->systemtype = 'AIMS';
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isconsign = false;
        $this->checkbelowcost = true;
        break;
      case 30: //RT
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'RT Trading';
        $this->systemtype = 'PAYROLL';
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->rptfont = 'Helvetica Neue';
        break;
      case 29: //SBC MAIN
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = false;
        $this->multibranch = true;
        $this->branchaccess = 1;
        $this->companyname = 'SBC';
        $this->systemtype = 'AIMSPAYROLL';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->ispricescheme = false;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->iscrm = false;
        $this->ispos = false;
        $this->isshortcutjo = false;
        $this->istodo = true;
        $this->restrictip = false;
        $this->periodic = true;
        $this->ispurchasedisc = true;
        $this->istaskmonitoring = true;
        $this->itembatch = 0;
        $this->reportpath = "\Http\Classes\modules\modulereport\sbc\\";
        $this->logopath = "public/images/sbc/";
        $this->socketserver = 'https://op.sbc.ph:25384'; //http://localhost:3000 ; http://nodejs.sbc.ph:25384
        $this->socketnotify = true;
        $this->ispayrollportal = true;
        break;
      case 28: //XCOMP
        $this->clientlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'R.ERP';
        $this->systemtype = 'AIMSPAYROLL';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isissuance = true;
        $this->isshortcutpo = true;
        $this->isshortcutso = true;
        $this->isconsign = false;
        $this->isrecalc = true;
        $this->multibranch = true;
        $this->branchaccess = 1;
        $this->serial = false;
        $this->masterlimit = 5000;
        $this->fifoexpiration = true;
        $this->isshareinv = true;
        $this->ispurchasedisc = false;
        $this->isglc = true;
        $this->pricetype = 'CustomerGroup';
        $this->reportpath = "\Http\Classes\modules\modulereport\\xcomp\\";
        break;
      case 27: //NTE
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'NTE';
        $this->systemtype = 'AIMS';
        $this->isexpiry = true;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isshortcutso = true;
        $this->isconsign = false;
        $this->fifoexpiration = true;
        $this->masterlimit = 5000;
        $this->reportpath = "\Http\Classes\modules\modulereport\\nte\\";
        $this->isglc = true;
        $this->mobilemodules = ['so', 'sj'];
        $this->mobileparents = ['sales'];
        break;
      case 26: //bee healthy
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'Bee Healthy';
        $this->systemtype = 'AMS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->ispricescheme = false;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->iscrm = false;
        $this->ispos = false;
        $this->isshortcutjo = false;
        $this->autoaj = false;
        $this->istodo = false;
        $this->restrictip = false;
        $this->periodic = false;
        $this->ispurchasedisc = true;
        $this->manualpath = "/images/manual/sbc/pdf/";
        $this->isshowmanual = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\bee\\";
        $this->isautosaveacctgstock = true;
        $this->showselectadd = true;
        $this->collapsiblehead = false;
        $this->showloading = false;
        break;
      // case 25: //SBC PORTAL - merge to SBC MAIN
      //   $this->clientlength = 15;
      //   $this->documentlength = 15;
      //   $this->barcodelength = 15;
      //   $this->tax = 12;
      //   $this->serial = false;
      //   $this->companyname = 'SolutionBase Corp.';
      //   $this->systemtype = 'HRISPAYROLL';
      //   $this->isexpiry = true;
      //   $this->checkbelowcost = true;
      //   $this->isproject = false;
      //   $this->iscreateversion = false;
      //   $this->isfa = false;
      //   $this->ispricescheme = false;
      //   $this->isconsign = false;
      //   $this->isshortcutpo = false;
      //   $this->iscrm = false;
      //   $this->ispos = false;
      //   $this->isshortcutjo = false;
      //   $this->autoaj = true;
      //   $this->istodo = true;
      //   $this->restrictip = false;
      //   $this->periodic = true;
      //   $this->ispurchasedisc = true;
      //   $this->usecamera = true;
      //   break;
      case 24: //GFC
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'GOODFOUND CEMENT';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->istodo = false;
        $this->ispurchasedisc = false;
        $this->iseditsortline = true;
        $this->isdefaultuominout = true;
        $this->multibranch = true;
        $this->branchaccess = 1;
        $this->masterlimit = 500;
        $this->isglc = true;
        $this->ispr = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\goodfound\\";
        $this->isfirstpageheader = true;
        break;
      case 23:  //lab sol //cebu
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'LabSolution Technologies Inc.';
        $this->systemtype = 'AIMS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->masterlimit = 500;
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\labsolcebu\\";
        break;
      case 22: // petfoods eipi
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 10;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'East Innovention Philippines Inc.';
        $this->systemtype = 'AIMS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->ispricescheme = false;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->iscrm = false;
        $this->ispos = false;
        $this->isshortcutjo = false;
        $this->istodo = false;
        $this->restrictip = false;
        $this->masterlimit = 500;
        $this->isshowmanual = true;
        $this->isdefaultuominout = true;
        $this->isrecalcamt_changeuom = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\eipi\\";
        break;
      case 21: //kinggeorge
        $this->clientlength = 7;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->sjitemlimit = 0;
        $this->tax = 12;
        $this->companyname = 'KINGGEORGE';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->istodo = false;
        $this->ispurchasedisc = false;
        $this->iseditsortline = true;
        $this->isrecalcamt_changeuom = true;
        $this->pricetype = 'CustomerGroup';
        $this->isrecalc = false;
        $this->isglc = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\kinggeorge\\";
        break;
      case 20: //proline
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'PROLINE INDUSTRIES';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->isissuance = true;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isshortcutso = true;
        $this->isshortcutpo = true;
        $this->isconsign = false;
        $this->reportpath = "\Http\Classes\modules\modulereport\proline\\";
        break;
      case 19: //housegem
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'HOUSEGEM'; //T4TRIUMPH, TAITAFALCN, TEMPLEWIN
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->istodo = false;
        $this->iskgs = true;
        $this->ispurchasedisc = false;
        $this->iseditsortline = true;
        $this->isrecalc = false;
        $this->pricetype = 'CustomerGroupLatest';
        $this->reportpath = "\Http\Classes\modules\modulereport\housegem\\"; // SJ printing setup - Custom 75    
        $this->rptfont = 'Arial';
        break;
      case 18: // DTS
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'SolutionBase Corp.';
        $this->systemtype = 'DTS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = true;
        $this->ispricescheme = false;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->iscrm = false;
        $this->ispos = true;
        $this->isshortcutjo = false;
        $this->isautoaj = true;
        break;
      case 17: //UNIHOME
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'UNIHOME';
        $this->systemtype = 'AIMSPOS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isissuance = true;
        $this->isshortcutso = true;
        $this->ispricescheme = true;
        $this->isconsign = false;
        $this->ispos = true;
        $this->autoaj = true;
        $this->isrecalc = true;
        $this->isglc = true;
        $this->pricetype = 'CustomerGroupLatest';
        $this->reportpath = "\Http\Classes\modules\modulereport\unihome\\";
        break;
      case 16: //ATI
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'AMERICAN TECHNOLOGIES, INC.';
        $this->systemtype = 'ATI';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = true;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->isshortcutcd = true;
        $this->isacctgentry = true;
        $this->ispr = true;
        $this->isnonserial = true;
        $this->masterlimit = 5000;
        $this->reportpath = "\Http\Classes\modules\modulereport\ati\\"; // PO printing setup - Print to printable area  
        break;
      case 15: //NATHINA
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->companyname = 'NATHIÑA';
        $this->systemtype = 'AIMS';
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isissuance = true;
        $this->isexpiry = true;
        $this->issupplierinvoice = true;
        $this->isshortcutso = true;
        $this->fifoexpiration = true;
        $this->isrecalc = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\nathina\\";
        break;
      case 14: //MAJESTY
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->companyname = 'MAJESTY';
        $this->systemtype = 'MISPOS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isissuance = true;
        $this->ispricescheme = true;
        $this->isconsign = false;
        $this->ispos = true;
        $this->isacctgentry = false;
        $this->autoaj = true;
        $this->invonly = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\majesty\\";
        break;
      case 12: //afti usd
        $this->clientlength = 10;
        $this->documentlength = 12;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = true;
        $this->companyname = 'AFTI';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isshortcutpo = true;
        $this->iscrm = true;
        $this->ispcf = true;
        $this->ispos = false;
        $this->isshortcutjo = true;
        $this->isiteminfo = true;
        $this->defaultcurrency = 'USD';
        $this->reportpath = "\Http\Classes\modules\modulereport\afti\\";
        $this->isvatexsales = true;
        $this->isvatexpurch = true;
        $this->isdocyr = true;
        $this->istodo = false;
        $this->iseditsortline = true;
        $this->manualpath = "/images/manual/afti/pdf/";
        $this->isshowmanual = true;
        $this->masterlimit = 25;
        $this->isfirstpageheader = true;
        $this->logopath = "/images/afti/";
        break;
      case 11: //SUMMIT
        $this->clientlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'SUMMIT';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isconsign = false;
        $this->isshortcutpo = false;
        $this->iscrm = false;
        $this->isuomamt = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\summit\\";
        break;
      case 10: //AFTI
        $this->itembatch = 0;
        $this->clientlength = 10;
        $this->documentlength = 12;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = true;
        $this->companyname = 'AFTI';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->isshortcutpo = true;
        $this->iscrm = true;
        $this->ispcf = true;
        $this->ispos = false;
        $this->isshortcutjo = true;
        $this->isiteminfo = true;
        $this->defaultcurrency = 'PHP';
        $this->reportpath = "\Http\Classes\modules\modulereport\afti\\";
        $this->isvatexsales = true;
        $this->isvatexpurch = true;
        $this->isdocyr = true;
        $this->istodo = false;
        $this->issalesdisc = false;
        $this->iseditsortline = true;
        $this->manualpath = "/images/manual/afti/pdf/";
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->masterlimit = 25;
        $this->isautosaveacctgstock = false;
        $this->logopath = "public/images/afti/";
        $this->isfirstpageheader = true;
        $this->restrictip = true;
        $this->ispr = true;
        break;
      case 9: //PAYROLL PORTAL
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 10;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'XYZ Corp.';
        $this->systemtype = 'HRISPAYROLL';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->multiallow = true;
        break;
      case 8: //MAXIPRO
        $this->clientlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'MAXIPRO Development Corporation';
        $this->systemtype = 'CAIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->isproject = true;
        $this->iscreateversion = false;
        $this->ispos = false;
        $this->iscrm = false;
        $this->reportpath = "\Http\Classes\modules\modulereport\maxipro\\";
        $this->logopath = "/images/maxipro/";
        $this->isglc = true;
        $this->manualpath = "public/images/manual/maxipro/pdf/";
        $this->isshowmanual = true;
        $this->ispr = true;
        break;
      case 7: //Enrollment
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'SolutionBase Corp.';
        $this->systemtype = 'SSMS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        break;
      case 6: //MITSUKOSHI
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 0;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'MITSUKOSHI';
        $this->systemtype = 'WAIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->ispallet = true;
        $this->isproject = false;
        $this->iscreateversion = true;
        $this->isfa = false;
        $this->isshortcutpo = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\mitsukoshi\\";
        break;
      case 5: //HMS
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'XYZ Corp.';
        $this->systemtype = 'HMS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->isproject = false;
        $this->iscreateversion = false;
        break;
      case 4: //MIS Client
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'XYZ Corp.';
        $this->systemtype = 'MIS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->isproject = false;
        $this->isacctgentry = false;
        $this->iscreateversion = false;
        $this->invonly = true;
        break;
      case 3: //conti - client ni joy cebu
        $this->multibranch = true;
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'R.ERP';
        $this->systemtype = 'AIMSHRIS';
        $this->branchaccess = 1;
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->issupplierinvoice = true;
        $this->isshortcutpo = true;
        $this->isshareinv = true;
        $this->ispr = true;
        $this->reportpath = "\Http\Classes\modules\modulereport\\conti\\";
        break;
      case 2: //mis - serial in and serial out
        $this->clientlength = 15;
        $this->documentlength = 15;
        $this->barcodelength = 15;
        $this->tax = 12;
        $this->serial = true;
        $this->companyname = 'Finden';
        $this->systemtype = 'AIMS';
        $this->isexpiry = false;
        $this->checkbelowcost = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->isproject = false;
        $this->iscreateversion = true;
        $this->isfa = true;
        break;
      case 1: //vitaline
        $this->clientlength = 10;
        $this->documentlength = 15;
        $this->barcodelength = 13;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'Vitaline';
        $this->systemtype = 'AIMS';
        $this->isexpiry = true;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->payroll_bonusmax = 0;
        $this->payroll_daysInMonth = 0;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->reportpath = "\Http\Classes\modules\modulereport\\vitaline\\";
        break;
      default:
        $this->clientlength = 15;
        $this->locationlength = 0;
        $this->documentlength = 15;
        $this->barcodelength = 20;
        $this->tax = 12;
        $this->serial = false;
        $this->companyname = 'ABC Corp.';
        $this->systemtype = 'BMS';
        $this->isexpiry = false;
        $this->checkbelowcost = true;
        $this->isproject = false;
        $this->iscreateversion = false;
        $this->isfa = false;
        $this->ispricescheme = false;
        $this->isconsign = false;
        $this->isshortcutpo = true;
        $this->iscrm = false;
        $this->ispos = false;
        $this->isshortcutjo = false;
        $this->autoaj = false;
        $this->istodo = false;
        $this->restrictip = false;
        $this->periodic = false;
        $this->ispurchasedisc = true;
        $this->isshowmanual = true;
        $this->isglc = true;
        $this->invonly = false;
        $this->clientitem = false;
        $this->daysdue = 7;
        $this->isvatexsales = false;
        $this->timekeeping = false;
        $this->isconstruction = false;
        $this->ispurchases = false;
        $this->linearapproval = false;
        $this->isticketing = false;
        $this->isserviceticketing = false;
        $this->showdept = false;
        $this->isshowtsso = false;
        $this->showloading = true;
        $this->socketserver = 'https://op.sbc.ph:25384'; //'http://localhost:3000';//http://demo.queuing.solutionbasecorp.com:25763
        $this->socketnotify = true;
        break;
    }
  }
  public function getistaskmonitor($params)
  {
    $this->companylist($params);
    return $this->istaskmonitoring;
  }

  public function getisshowmanual($params)
  {
    $this->companylist($params);
    return $this->isshowmanual;
  }

  public function getmultibranch($params)
  {
    $this->companylist($params);
    return $this->multibranch;
  }

  public function getdefaultcurrency($params)
  {
    $this->companylist($params);
    return $this->defaultcurrency;
  }


  public function getclientlength($params)
  {
    $this->companylist($params);
    switch ($params['doc']) {
      case 'EARNINGDEDUCTIONSETUP':
      case 'ADVANCESETUP':
      case 'LOANAPPLICATION':
      case 'LOANAPPLICATIONPORTAL':
      case 'LEAVESETUP':
        return $this->documentlength;
        break;
      case 'LOCATIONLEDGER':
        return $this->locationlength;
        break;
      case 'DEPARTMENT':
        if ($params['companyid'] == 62) { //onesky
          return 0;
        } else {
          return $this->clientlength;
        }
        break;
      default:
        return $this->clientlength;
        break;
    }
  }

  public function getinvdoc($params)
  {
    switch ($params['companyid']) {
      default:
        return "'RR','DM','SJ','CM','IS','AJ','TS','PK','MI'";
        break;
    }
  }

  public function getdocumentlength($params)
  {
    $this->companylist($params);
    return $this->documentlength;
  }

  public function getbarcodelength($params)
  {
    $this->companylist($params);
    return $this->barcodelength;
  }

  public function getserial($params)
  {
    $this->companylist($params);
    return $this->serial;
  }

  public function getiscreateversion($params)
  {
    $this->companylist($params);
    return $this->iscreateversion;
  }

  public function getcompanyname($params)
  {
    $this->companylist($params);
    return $this->companyname;
  }

  public function getcompanyalias($params)
  {
    $result = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", [$params['center']]);
    if ($result == '') {
      $this->companylist($params);
      $result = $this->companyname;
    }
    return $result;
  }
  public function gettax($params)
  {
    $this->companylist($params);
    return $this->tax;
  }

  public function getmanualpath($params)
  {
    $this->companylist($params);
    return $this->manualpath;
  }

  public function getlogopath($params)
  {
    $this->companylist($params);
    return $this->logopath;
  }

  public function getwh($params)
  {
    return $this->coreFunctions->getfieldvalue("center", "warehouse", "code=?", [$params['center']]);
  }

  public function getproject($params)
  {
    return $this->coreFunctions->getfieldvalue("center", "project", "code=?", [$params['center']]);
  }

  public function getbranchcenter($params)
  {
    return $this->coreFunctions->datareader("select ifnull(client.client,'') AS value FROM center AS c LEFT JOIN client ON client.clientid=c.branchid WHERE c.code=?", [$params['center']]);
  }

  public function getaddress($params)
  {
    return $this->coreFunctions->getfieldvalue("center", "address", "code=?", [$params['center']]);
  }

  public function getmodule($params)
  {
    //List of modules
    //['masterfile','purchase','sales','inventory','issuance','payable','receivable','accounting','itemmaster','schoolsetup',
    //'transactionutilities','accountutilities','schoolsystem','roommanagement','customersupport','hris',
    //'hrissetup','payrollsetup','payrolltransaction','announcement','dashboard'];

    $modulelist = [];
    $systemtype = $this->getsystemtype($params);
    switch ($params['companyid']) {
      case 57:
        $modulelist = ['masterfile', 'cashier', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
        break;
      case 24: //goodfound
        $modulelist = ['masterfile', 'purchase', 'sales', 'production', 'inventory', 'payable', 'receivable', 'accounting', 'kwhmonitoring', 'itemmaster', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
        break;
      case 27: //NTE
      case 36: //rozlab
        $modulelist = ['masterfile', 'purchase', 'sales', 'production', 'inventory', 'issuance', 'payable', 'receivable', 'accounting', 'itemmaster', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
        break;
      case 28: //xcomp
        $modulelist = ['masterfile', 'itemmaster', 'purchase', 'sales', 'inventory', 'issuance', 'payable', 'receivable', 'accounting', 'payrollsetup', 'payrolltransaction', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
        break;
      case 43: //mighty
        $modulelist = ['masterfile', 'itemmaster', 'purchase', 'sales', 'inventory', 'payable', 'receivable', 'accounting', 'payrollsetup', 'payrolltransaction', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
        break;
      case 10: //afti
        $modulelist = ['masterfile', 'itemmaster', 'purchase', 'crm', 'sales', 'inventory', 'payable', 'receivable', 'accounting', 'pcf', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
        break;
      case 12: //afti usd
        $modulelist = ['masterfile', 'itemmaster', 'purchase',  'crm', 'sales', 'accounting', 'pcf', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
        break;
      case 29: //sbc main
        $modulelist = ['payrollportal', 'masterfile', 'itemmaster', 'purchase', 'sales', 'inventory', 'issuance', 'payable', 'receivable', 'accounting', 'payrollsetup', 'payrolltransaction', 'taskmonitoring', 'queuing', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
        break;
      case 35: //aquamax
        $modulelist = ['masterfile', 'waterbilling', 'receivable', 'accounting', 'transactionutilities', 'accountutilities', 'announcement', 'dashboard'];
        break;
      case 44: //stonepro
      case 51: //ulitc
      case 53: //camera
        $modulelist = ['payrollportal', 'payrolltransaction', 'othertransaction', 'transactionutilities', 'accountutilities', 'announcement'];
        break;
      case 45: //pdpi payroll
        $modulelist = ['payrollsetup', 'payrolltransaction', 'dashboard', 'announcement', 'transactionutilities', 'accountutilities'];
        break;
      case 58: //cdo
        $modulelist = ['payrollportal', 'recruitment', 'employment', 'contractmonitoring', 'discipline', 'timekeeping', 'payrolltransaction', 'benefits', 'monitoring', 'trainingdev', 'dashboard', 'announcement', 'transactionutilities', 'accountutilities', 'masterfilerecruitment', 'masterfileemployment', 'masterfiletimekeeping', 'masterfilepayroll'];
        break;
      default:
        switch ($systemtype) {
          case 'QUEUING':
            $modulelist = ['queuing',  'transactionutilities', 'accountutilities', 'dashboard'];
            break;
          case 'LENDING':
            $modulelist = ['masterfile', 'lending', 'payable', 'receivable', 'accounting', 'transactionutilities', 'accountutilities', 'announcement', 'dashboard'];
            break;
          case 'BMS':
            $modulelist = ['masterfile', 'itemmaster', 'barangaysetup', 'barangayoperation', 'transactionutilities', 'accountutilities', 'announcement', 'dashboard'];
            break;
          case 'EAPPLICATION':
            $modulelist = ['operation', 'receivable', 'reportlist', 'masterfile', 'announcement', 'dashboard'];
            break;
          case 'REALESTATE':
            $modulelist = ['masterfile', 'payable', 'receivable', 'accounting', 'itemmaster', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            if ($this->isconstruction($params)) {
              $modulelist = ['masterfile', 'purchase', 'sales', 'construction', 'inventory', 'payable', 'receivable', 'accounting', 'itemmaster', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            }
            break;
          case 'MANUFACTURING':
            $modulelist = ['masterfile', 'purchase', 'sales', 'production', 'inventory', 'payable', 'receivable', 'accounting', 'itemmaster', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'ATI':
            $modulelist = ['masterfile', 'itemmaster', 'fams', 'purchase', 'issuance', 'sales', 'inventory', 'vehiclescheduling', 'accounting', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'PAYROLLPORTAL':
            $modulelist = ['payrollportal'];
            break;

          case 'PAYROLL':
            $modulelist = ['payrollportal', 'payrollsetup', 'payrolltransaction', 'dashboard', 'announcement', 'transactionutilities', 'accountutilities'];
            if ($params['companyid'] == 30) { //RT
              if (($key = array_search('payrollportal', $modulelist)) !== false) {
                unset($modulelist[$key]);
              }
            }
            break;

          case 'HRIS':
            $modulelist = ['hrissetup', 'hris', 'dashboard'];
            if ($this->istimekeeping($params)) {
              array_push($modulelist, 'timekeeping');
            }
            array_push($modulelist,  'announcement', 'transactionutilities', 'accountutilities');
            break;

          case 'HRISPAYROLL':
            $modulelist = ['hrissetup', 'hris', 'payrollportal', 'payrollsetup', 'payrolltransaction', 'dashboard', 'announcement', 'transactionutilities', 'accountutilities'];
            if ($params['companyid'] == 62) { //onesky
              if (($key = array_search('payrollportal', $modulelist)) !== false) {
                unset($modulelist[$key]);
              }
            }
            break;

          case 'ALL':
            $modulelist = [
              'masterfile',
              'purchase',
              'sales',
              'inventory',
              'issuance',
              'payable',
              'receivable',
              'accounting',
              'itemmaster',
              'schoolsetup',
              'schoolsystem',
              'hris',
              'hrissetup',
              'payrollsetup',
              'payrolltransaction',
              'roommanagement',
              'frontdesk',
              'announcement',
              'projectsetup',
              'construction',
              'warehousing',
              'branch',
              'dashboard',
              'payrollportal',
              'documentmanagement',
              'transactionutilities',
              'accountutilities',
              'production',
              'lending',
            ];
            break;
          case 'DTS': // DOCUMENT MANAGEMENT
            $modulelist = ['masterfile', 'transactionutilities', 'accountutilities', 'dashboard', 'documentmanagement', 'announcement'];
            break;
          case 'AIMSHRIS':
            $modulelist = ['masterfile', 'itemmaster', 'purchase', 'sales', 'inventory', 'issuance', 'payable', 'receivable', 'accounting', 'hrissetup', 'hris', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'AIMSHRISPAYROLL':
            $modulelist = ['masterfile', 'itemmaster', 'purchase', 'sales', 'inventory', 'issuance', 'payable', 'receivable', 'accounting', 'hrissetup', 'hris', 'payrollsetup', 'payrolltransaction', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'AIMS':
            switch ($params['companyid']) {
              case 50: //unitech 
                $modulelist = ['masterfile', 'purchase', 'sales', 'production', 'inventory', 'payable', 'receivable', 'accounting', 'itemmaster', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
                break;
              default:
                $modulelist = ['masterfile', 'purchase', 'sales', 'inventory', 'payable', 'receivable', 'accounting', 'itemmaster', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
                if ($this->istaskmonitoring) {
                  array_push($modulelist, 'taskmonitoring');
                }
                break; // end switch
            }
            break;
          case 'AIMSPOS':
            $modulelist = ['masterfile', 'itemmaster', 'purchase', 'sales', 'pos', 'inventory', 'payable', 'receivable', 'accounting', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'MISPOS':
            $modulelist = ['masterfile', 'itemmaster', 'purchase', 'sales', 'pos', 'inventory', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'MIS':
            $modulelist = ['masterfile', 'itemmaster', 'purchase', 'sales', 'inventory', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'FAMS':
            $modulelist = ['masterfile', 'purchase', 'fams', 'itemmaster', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard']; //'purchase', 'sales', 'inventory', 
            if (!$this->ispurchases) {
              if (($key = array_search('purchase', $modulelist)) !== false) {
                unset($modulelist[$key]);
              }
            }
            break;
          case 'HMS':
            $modulelist = ['frontdesk', 'roommanagement', 'transactionutilities'];
            break;
          case 'AMS':

            switch ($params['companyid']) {
              case 26: //bee healthy
                $modulelist = ['masterfile', 'purchase', 'sales', 'payable', 'receivable', 'accounting', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
                break;

              case 48: //seastar
                $modulelist = ['masterfile', 'itemmaster', 'sales', 'receivable', 'payable', 'accounting', 'transactionutilities', 'accountutilities', 'announcement', 'dashboard'];
                break;

              default:
                $modulelist = ['masterfile', 'payable', 'receivable', 'accounting', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
                break;
            } // end switch
            break;
          case 'CAIMS':
            $modulelist = ['masterfile', 'itemmaster', 'projectsetup', 'construction', 'purchase', 'payable', 'receivable', 'inventory', 'accounting', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'WAIMS': // Warehousing
            $modulelist = ['announcement', 'purchase', 'sales', 'warehousing', 'inventory', 'payable', 'receivable', 'accounting', 'masterfile', 'itemmaster', 'transactionutilities', 'accountutilities', 'branch', 'dashboard'];
            break;
          case 'SSMS': //enrollment
            $modulelist = ['masterfile', 'purchase', 'sales', 'inventory', 'issuance', 'payable', 'receivable', 'accounting', 'itemmaster', 'schoolsetup', 'schoolsystem', 'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'MMS':
            $modulelist = ['masterfile', 'itemmaster', 'operation', 'payable', 'receivable', 'accounting',  'transactionutilities', 'accountutilities', 'announcement', 'branch', 'dashboard'];
            break;
          case 'VSCHED':
            $modulelist = ['masterfile', 'itemmaster', 'vehiclescheduling', 'transactionutilities', 'accountutilities', 'branch', 'dashboard'];
            break;
        }

        if ($this->getisconsign($params)) {
          array_push($modulelist, 'consignment');
        }

        if ($this->getiscrm($params)) {
          array_push($modulelist, 'crm');
        }


        if ($this->getispcf($params)) {
          array_push($modulelist, 'pcf');
        }

        if ($this->isticketing($params)) {
          array_push($modulelist, 'customersupport');
        }

        if ($this->isserviceticketing($params)) {
          array_push($modulelist, 'serviceticketing');
        }

        if ($this->getisissuance($params)) {
          $pos = 0;
          foreach ($modulelist as $key => $value) {
            if ($value == 'inventory') {
              $modulelist = array_merge(array_slice($modulelist, 0, $pos + 1), array('issuance'), array_slice($modulelist, $pos + 1));
              break;
            }
            $pos = $pos + 1;;
          }
        }
        break;
    }

    if ($params['companyid'] != 34) { //not evergreen
      array_push($modulelist, 'reportlist');
    }

    return $modulelist;
  } //end function

  public function getsystemtype($params)
  {
    $this->companylist($params);
    return $this->systemtype;
  } //end function

  public function getistodo($params)
  {
    $this->companylist($params);
    return $this->istodo;
  } //end function

  public function getbranchaccess($params)
  {
    //set 1 to enable branch
    //set 0 to disable branch
    // return 0;
    $this->companylist($params);
    return $this->branchaccess;
  }


  public function getdecimal($action, $params)
  {
    switch ($params['companyid']) {
      case 50: //unitech
        switch ($action) {
          case 'price':
            return 2;
            break;
          case 'qty':
            return 4;
            break;
          case 'currency':
            return 2;
            break;
          default:
            return 2;
            break;
        }
        break;
      case 12: //afti usd
        switch ($action) {
          case 'price':
            return 6;
            break;
          case 'qty':
            return 2;
            break;
          case 'currency':
            return 6;
            break;
          default:
            return 2;
            break;
        }
        break;
      case 10: //afti
        switch ($action) {
          case 'price':
            return 4;
            break;
          case 'qty':
            return 2;
            break;
          case 'currency':
            return 4;
            break;
          default:
            return 2;
            break;
        }
        break;
      case 8: //maxipro
        switch ($action) {
          case 'price':
            switch ($params['doc']) {
              case 'BQ':
              case 'PM':
              case 'BA':
              case 'RR':
              case 'RQ':
              case 'PO':
                return 7;
                break;
              default:
                return 2;
                break;
            }
            break;
          case 'qty':
            return 6;
            break;
          case 'currency':
            switch ($params['doc']) {
              case 'BQ':
              case 'BA':
              case 'PM':

                return 5;
                break;
              default:
                return 2;
                break;
            }
            break;
          default:
            return 0;
            break;
        }
        break;
      case 19: //housegem
        switch ($action) {
          case 'price':
            switch ($params['doc']) {
              case 'PO':
              case 'RR':
              case 'DM':
                return 6;
                break;
              default:
                return 2;
                break;
            }
            break;
          case 'qty':
            return 2;
            break;
          case 'currency':
            return 2;
            break;
          default:
            return 0;
            break;
        }
        break;
      case 21: //kinggeorge
        switch ($action) {
          case 'price':
            return 6;
            break;
          case 'qty':
            return 2;
            break;
          case 'currency':
            return 2;
            break;
          default:
            return 0;
            break;
        }
        break;
      case 24: // good found cement
        switch ($action) {
          case 'price':
            switch ($params['doc']) {
              case 'PO':
              case 'RR':
              case 'PU':
              case 'RU':
                return 4;
                break;
              default:
                return 2;
                break;
            }

            break;
          case 'qty':
            return 3;
            break;
          case 'currency':
            return 2;
            break;
          default:
            return 0;
            break;
        }
        break;
      case 27: //NTE
      case 36: //rozlab
        switch ($action) {
          case 'price':
            return 4;
            break;
          case 'qty':
            return 4;
            break;
          case 'currency':
            return 2;
            break;
          default:
            return 0;
            break;
        }
        break;
      case 16: //ati
        switch ($action) {
          case 'price':
            return 5;
            break;
          case 'qty':
            return 2;
            break;
          case 'currency':
            return 5;
            break;
          default:
            return 0;
            break;
        }
        break;
      default:
        switch ($action) {
          case 'price':
            return 2;
            break;
          case 'qty':
            return 2;
            break;
          case 'currency':
            return 2;
            break;
          default:
            return 0;
            break;
        }
        break;
    }
  }

  public function getisexpiry($params)
  {
    $this->companylist($params);
    return $this->isexpiry;
  }

  public function getispallet($params)
  {
    $this->companylist($params);
    return $this->ispallet;
  }


  public function checkbelowcost($params)
  {
    $this->companylist($params);
    return $this->checkbelowcost;
  }

  public function getmasterlimit($params)
  {
    $this->companylist($params);
    return $this->masterlimit;
  }

  public function gettransactionlimit($params)
  {
    $this->companylist($params);
    return $this->transactionlimit;
  }

  public function getpayroll_bonusmax($params)
  {
    $this->companylist($params);
    return $this->payroll_bonusmax;
  }

  public function getpayroll_daysInMonth($params)
  {
    $this->companylist($params);
    return $this->payroll_daysInMonth;
  }

  public function getsupplierinvoice($params)
  {
    $this->companylist($params);
    return $this->issupplierinvoice;
  }

  public function getispricescheme($params)
  {
    $this->companylist($params);
    return $this->ispricescheme;
  }

  public function getpricetype($params)
  {
    $this->companylist($params);
    // Stockcard = Retial Price (stockcard only)
    // LatestPrice = Get Customer Latest price (Customer latest price -> Stockcard)
    // CustomerGroup = Customer Price Group (Customer Group -> Stockcard)
    // CustomerGroupLatest = Customer Price Group (Customer Group -> Latest Price -> Stockcard)
    return $this->pricetype;
  }

  public function getisconsign($params)
  {
    $this->companylist($params);
    return $this->isconsign;
  }

  public function getisshortcutpo($params)
  {
    $this->companylist($params);
    return $this->isshortcutpo;
  }

  public function getisshortcutcd($params)
  {
    $this->companylist($params);
    return $this->isshortcutcd;
  }

  public function getisshortcutso($params)
  {
    $this->companylist($params);
    return $this->isshortcutso;
  }

  public function getisshortcutdr($params)
  {
    $this->companylist($params);
    return $this->isshortcutdr;
  }

  public function getiscrm($params)
  {
    $this->companylist($params);
    return $this->iscrm;
  }

  public function getispcf($params)
  {
    $this->companylist($params);
    return $this->ispcf;
  }

  public function getisissuance($params)
  {
    $this->companylist($params);
    return $this->isissuance;
  }

  public function getisacctgentry($params)
  {
    $this->companylist($params);
    return $this->isacctgentry;
  }

  public function getispos($params)
  {
    $this->companylist($params);
    return $this->ispos;
  }

  public function getispr($params)
  {
    $this->companylist($params);
    return $this->ispr;
  }

  public function isticketing($params)
  {
    $this->companylist($params);
    return $this->isticketing;
  }

  public function isshowdept($params)
  {
    $this->companylist($params);
    return $this->showdept;
  }

  public function isserviceticketing($params)
  {
    $this->companylist($params);
    return $this->isserviceticketing;
  }

  public function getrestrictip($params)
  {
    $this->companylist($params);
    return $this->restrictip;
  }

  public function getisuomamt($params)
  {
    $this->companylist($params);
    return $this->isuomamt; //default price from UOM
  }

  public function getisshortcutjo($params)
  {
    $this->companylist($params);
    return $this->isshortcutjo;
  }

  public function getisshortcutmr($params)
  {
    $this->companylist($params);
    return $this->isshortcutmr;
  }

  public function getiskgs($params)
  {
    $this->companylist($params);
    return $this->iskgs;
  } //end function

  public function getispurchasedisc($params)
  {
    $this->companylist($params);
    return $this->ispurchasedisc;
  } //en

  public function getissalesdisc($params)
  {
    $this->companylist($params);
    return $this->issalesdisc;
  } //en

  public function getiseditsortline($params)
  {
    $this->companylist($params);
    return $this->iseditsortline;
  } //en

  public function getfifoexpiration($params)
  {
    $this->companylist($params);
    return $this->fifoexpiration;
  }

  public function getreportpath($params)
  {
    $this->companylist($params);

    $report_path = str_replace('\\', '/', $this->reportpath);
    $path = app_path() . $report_path . strtolower($params['doc']) . '.php';

    if (file_exists($path)) {
      return "App" . $this->reportpath . strtolower($params['doc']);
    } else {
      return "App" . $this->reportpathdefault . strtolower($params['doc']);
    }
  }

  public function getisproject($params)
  {
    $this->companylist($params);
    return $this->isproject;
  }
  public function getisfixasset($params)
  {
    $this->companylist($params);
    return $this->isfa;
  }

  public function getisiteminfo($params)
  {
    $this->companylist($params);
    return $this->isiteminfo;
  }

  public function getvatexsales($params)
  {
    $this->companylist($params);
    return $this->isvatexsales;
  }

  public function getsjitemlimit($params)
  {
    $this->companylist($params);
    return $this->sjitemlimit;
  }

  public function isrecalc($params)
  {
    $this->companylist($params);
    return $this->isrecalc;
  }

  public function isglc($params)
  {
    $this->companylist($params);
    return $this->isglc;
  }

  public function isinvonly($params)
  {
    $this->companylist($params);
    return $this->invonly;
  }

  public function iswaterbilling($params)
  {
    $this->companylist($params);
    return $this->waterbilling;
  }

  public function istodo($params)
  {
    $this->companylist($params);
    return $this->istodo;
  }

  public function linearapproval($params)
  {
    $this->companylist($params);
    return $this->linearapproval;
  }

  public function autoaj($params)
  {
    $this->companylist($params);
    return $this->autoaj;
  }

  public function isgenerateapv($params)
  {
    $this->companylist($params);
    return $this->generateapv;
  }


  public function getvatexpurch($params)
  {
    $this->companylist($params);
    return $this->isvatexpurch;
  }

  public function getdocyr($params)
  {
    $this->companylist($params);
    return $this->isdocyr;
  }

  public function getisperiodic($params)
  {
    $this->companylist($params);
    return $this->periodic;
  }

  public function getisrecalcamtchangeuom($params)
  {
    $this->companylist($params);
    return $this->isrecalcamt_changeuom; //recompute amt field from UOM table based on UOM
  }

  public function getisdefaultuominout($params)
  {
    $this->companylist($params);
    return $this->isdefaultuominout; //tagging default uom for IN/OUT transaction
  }

  public function getisshareinv($params)
  {
    $this->companylist($params);
    return $this->isshareinv;
  }

  public function getrptfont($params)
  {
    $this->companylist($params);
    return $this->rptfont;
  }

  public function getisrefillts($params)
  {
    $this->companylist($params);
    return $this->isrefillts;
  }

  public function getisdiscperqty($params)
  {
    $this->companylist($params);
    return $this->isdiscperqty;
  }

  public function getisfirstpageheader($params)
  {
    $this->companylist($params);
    return $this->isfirstpageheader;
  }

  public function isclientitem($params)
  {
    $this->companylist($params);
    return $this->clientitem;
  }

  public function isdashboardwh($params)
  {
    $this->companylist($params);
    return $this->dashboardwh;
  }

  public function getisautosaveacctgstock($params)
  {
    $this->companylist($params);
    return $this->isautosaveacctgstock;
  }

  public function getshowselectadd($params)
  {
    $this->companylist($params);
    return $this->showselectadd;
  }

  public function getcollapsiblehead($params)
  {
    $this->companylist($params);
    return $this->collapsiblehead;
  }

  public function getshowloading($params)
  {
    $this->companylist($params);
    return $this->showloading;
  }

  public function getdaysdue($params)
  {
    $this->companylist($params);
    return $this->daysdue;
  }

  public function peracctar($params)
  {
    $this->companylist($params);
    return $this->peraccountar;
  }

  public function getusecamera($params)
  {
    $this->companylist($params);
    return $this->usecamera;
  }

  public function getvalidfiledate($params)
  {
    $this->companylist($params);
    return $this->validfiledate;
  }

  public function istimekeeping($params)
  {
    $this->companylist($params);
    return $this->timekeeping;
  }

  public function getmobilemodules($params)
  {
    $this->companylist($params);
    return $this->mobilemodules;
  }

  public function getmobileparents($params)
  {
    $this->companylist($params);
    return $this->mobileparents;
  }

  public function getshowserialrem($params)
  {
    $this->companylist($params);
    return $this->showserialrem;
  }

  public function isconstruction($params)
  {
    $this->companylist($params);
    return $this->isconstruction;
  }
  public function crforcebal($params)
  {
    $this->companylist($params);
    return $this->crforcebal;
  }

  public function customerperagent($params)
  {
    $this->companylist($params);
    return $this->customerperagent;
  }
  public function getleavelabel($params)
  {
    $this->companylist($params);
    return $this->leavelabel;
  }

  public function getautosendemail($params)
  {
    $this->companylist($params);
    return $this->autosendemail;
  }

  public function getisshowtsso($params)
  {
    $this->companylist($params);
    return $this->isshowtsso;
  }

  public function getiswindowspayroll($params)
  {
    $this->companylist($params);
    return $this->isWindowsPayroll;
  }

  public function getisnonserial($params)
  {
    $this->companylist($params);
    return $this->isnonserial;
  }

  public function getispurchases($params)
  {
    $this->companylist($params);
    return $this->ispurchases;
  }

  public function multiallow($params)
  {
    $this->companylist($params);
    return $this->multiallow;
  }

  public function getitembatch($params)
  {
    $this->companylist($params);
    return $this->itembatch;
  }

  public function getisupdatabasetable($params)
  {
    $this->companylist($params);
    return $this->isupdatabasebtable;
  }

  public function getismirrormasters($params)
  {
    $this->companylist($params);
    return $this->ismirrormasters;
  }

  public function getispendingapp($params)
  {
    $this->companylist($params);
    return $this->ispendingapp;
  }

  public function getismysql8($params)
  {
    $this->companylist($params);
    return $this->ismysql8;
  }

  public function getispayrollportal($params)
  {
    $this->companylist($params);
    return $this->ispayrollportal;
  }

  public function getsocketserver($params)
  {
    $this->companylist($params);
    return $this->socketserver;
  }
  public function getsocketnotify($params)
  {
    $this->companylist($params);
    return $this->socketnotify;
  }

  public function getlookupclientpermodule($params)
  {
    $this->companylist($params);
    return $this->lookupclientpermodule;
  }
}
