<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\sbcdb\trigger_masterfile;
use App\Http\Classes\sbcdb\trigger_hris;

class trigger
{

  private $coreFunctions;
  private $companysetup;
  private $trigger_masterfile;
  private $trigger_hris;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->trigger_masterfile = new trigger_masterfile;
    $this->trigger_hris = new trigger_hris;
    $this->companysetup = new companysetup;
  } //end fn

  public function deletetriggers()
  {
    ini_set('memory_limit', '-1');

    $arr = $this->coreFunctions->opentable("SHOW TRIGGERS");

    foreach ($arr as $key => $value) {
      // $this->coreFunctions->LogConsole($value->Trigger);
      $this->coreFunctions->sbcdroptriggers($value->Trigger);
    }
  }

  private function costing_triggers()
  {
    //COSTING TRIGGER ==================================================================================================================
    $qry = "create TRIGGER costing_insert BEFORE INSERT on costing FOR EACH ROW
                BEGIN
                  declare iBal decimal(18,6);

                  if NEW.REFX<>0 then
                      update rrstatus set bal=bal - New.Served where trno=new.refx and line=NEW.LINEX;
                  end if;
                END";
    $this->coreFunctions->execqry(strtolower($qry), 'trigger');

    $qry = "create TRIGGER costing_update BEFORE UPDATE on costing FOR EACH ROW
                BEGIN
                    declare iBal decimal(18,6);

                    if NEW.REFX<>0 then
                      select Bal into iBal from rrstatus where Trno=NEW.REFX and line=NEW.LINEX;
                      if ((iBal+OLD.Served)-NEW.SERVEd)>=0 then
                        update rrstatus set bal=((bal + Old.Served) - New.Served) where Trno=NEW.REFX and line=NEW.LINEX;
                      else  
                        CALL No_Stock_costing;
                      end if;
                    end if;
                END";
    $this->coreFunctions->execqry(strtolower($qry), 'trigger');

    $qry = "create TRIGGER costing_delete BEFORE DELETE on costing FOR EACH ROW
                BEGIN
                 if OLD.SERVED<>0 then
                   update rrstatus set bal=bal+OLD.Served where trno=OLD.refx and line=OLD.linex;
                 end if;
                END";
    $this->coreFunctions->execqry(strtolower($qry), 'trigger');
  }

  private function apledger_triggers()
  {
    //APLEDGER TRIGGER =================================================================================================================
    $qry = "create TRIGGER apledger_update BEFORE UPDATE on apledger FOR EACH ROW
            BEGIN
              if New.bal<0 then
                CALL BAL_IS_GREATER_THAN_REQUIRED_BAL;
              end if;
            END";
    $this->coreFunctions->execqry(strtolower($qry), 'trigger');
    //END OF APLEDGER TRIGGER ====================================================================================================================================    
  }

  private function rrstatus_triggers()
  {
    //rrstatus TRIGGER ==========================================================================================================================================
    $qry = "create TRIGGER rrstatus_update BEFORE UPDATE on rrstatus FOR EACH ROW
        BEGIN
           if New.Bal<0 then
             CALL BELOW_QTY_RECEIVED;
           elseif New.QA>New.QTY then
             CALL DM_QTY_GREATER_THAN_RR_QTY;
           end if; 
         END";
    $this->coreFunctions->execqry(strtolower($qry), 'trigger');

    $qry = "create TRIGGER rrstatus_delete BEFORE DELETE on rrstatus FOR EACH ROW
        BEGIN

          if (old.qty<>old.bal) or old.qa<>0 then
            Call ITEM_ALREADY_SERVED_RRSTATUS;
          end if;
        END";
    $this->coreFunctions->execqry(strtolower($qry), 'trigger');
    //END OF rrstatus TRIGGER ====================================================================================================================================
  }

  private function arledger_triggers()
  {
    //ARLEDGER TRIGGER =================================================================================================================
    $qry = "create TRIGGER arledger_update BEFORE UPDATE on arledger FOR EACH ROW
            BEGIN
              if New.bal<0 then
                CALL BAL_IS_GREATER_THAN_REQUIRED_BAL;
              end if;
            END";
    $this->coreFunctions->execqry(strtolower($qry), 'trigger');
  }

  private function center_triggers()
  {
    //CENTER TRIGGER ====================================================================================================================
    $fields = [
      'code' => ['code' => []],
      'name' => ['name' => []],
      'address' => ['address' => []],
      'tel' => ['tel' => []],
      'warehouse' => ['warehouse' => []],
      'station' => ['station' => []],
      'tin' => ['tin' => []],
      'zipcode' => ['zipcode' => []]
    ];

    $this->settriggerlogs('center_update', 'AFTER UPDATE', 'center', 'center_log', $fields, 'code', 'CENTER');
  }

  private function client_triggers($config)
  {
    $deptcode = 'Dept Code';
    switch ($config['params']['companyid']) {
      case 16:
        $deptcode = 'PO Type';
        break;
    }
    //CLIENT TRIGGER ==================================================================================================================
    $fields = [
      'client' => ['client' => []],
      'clientname' => ['clientname' => []],
      'address' => ['addr' => []],
      'email' => ['email' => []],
      'telephone' => ['tel' => []],
      'mobile' => ['tel2' => []],
      'fax' => ['fax' => []],
      'Tin' => ['tin' => []],
      'contact' => ['contact' => []],
      'agent' => ['agent' => []],
      'terms' => ['terms' => []],
      'discount' => ['disc' => []],
      'class' => ['class' => []],
      'type' => ['type' => []],
      'Remarks' => ['rem' => []],
      'Start Date' => ['start' => []],
      'Enddate Date' => ['enddate' => []],
      'status' => ['status' => []],
      'province' => ['province' => []],
      'area' => ['area' => []],
      'iscustomer' => ['iscustomer' => []],
      'zipcode' => ['zipcode' => []],
      'quota' => ['quota' => []],
      'category' => ['category' => [true, 'cat_name', 'category_masterfile', 'cat_id']],
      'Cur' => ['forexid' => [true, 'cur', 'forex_masterfile', 'line']],
      'Is Supplier' => ['issupplier' => []],
      'Is Agent' => ['isagent' => []],
      'Is Warehouse' => ['iswarehouse' => []],
      'Is Employee' => ['isemployee' => []],
      'Is Inactive' => ['isinactive' => []],
      'Is Department' => ['isdepartment' => []],
      'Is No Cr Limit' => ['isnocrlimit' => []],
      'Cr Limit' => ['crlimit' => []],
      'Sales Account' => ['rev' => []],
      'Group' => ['groupid' => []],
      'Region' => ['region' => []],
      'Parent Code' => ['grpcode' => []],
      'User Level' => ['userid' => [true, 'username', 'users', 'idno']],
      // 'E-Mail' => ['email' => []],
      'Password' => ['password' => []],
      'Warehouse' => ['wh' => []],
      'Parent Dept' => ['department' => []],
      'Dean' => ['code' => []],
      'Level' => ['intclient' => []],
      'Tax Code' => ['ewtid' => [true, "concat(code, '~', rate)", 'ewtlist', 'line']],
      'bstyle' => ['bstyle' => []],
      'Is Contractor' => ['iscontractor' => []],
      'Is Administrator' => ['isadmin' => []],
      'Parent Code' => ['parent' => [true, "concat(client, '~', clientname)", 'client', 'client']],
      'Location' => ['locid' => [true, 'name', 'loc', 'line']],
      'Is Tetant' => ['istenant' => []],
      'Is Lease Provision' => ['istmptenant' => []],
      'Position' => ['position' => []],
      'Escalation' => ['escalation' => []],
      'Contract' => ['contract' => []],
      'VAT Type' => ['vattype' => []],
      'Ship/Delivered to' => ['shipto' => []],
      'Drop-off WH' => ['dropoffwh' => [true, "concat(client, '~', clientname)", 'client', 'clientid']],
      $deptcode => ['deptcode' => []],
      
      'Make' => ['make' => []],
      'Motor No.' => ['motorno' => []],
      'Plate No.' => ['plateno' => []],
      'Color Code' => ['color' => []]
    ];

    $customtrigger = "if OLD.dlock<>NEW.dlock and New.issynced = 1 then  delete from clientdlock where clientid = Old.clientid;  insert into clientdlock(clientid,dlock)values(Old.clientid,New.dlock);  end if;";

    $this->settriggerlogs('client_update', 'AFTER UPDATE', 'client', 'client_log', $fields, 'clientid', 'CLIENT', $customtrigger);
  }

  private function clientinfo_triggers($config)
  {
    $trigger_name = "clientinfo_update";
    $table = "clientinfo";
    $table_log = "client_log";
    $key = "clientid";

    $fields = [
      'Names' => ['names' => []],
      'Address' => ['addr' => []],
      'State' => ['state' => []],
      'Country' => ['country' => []],
      'Fax' => ['fax' => []],
      'Business' => ['business' => []],
      'Room' => ['room' => []],
      'Capacity' => ['capacity' => []],
      'Last Name' => ['lname' => []],
      'First Name' => ['fname' => []],
      'Middle Name' => ['mname' => []],
      'Ext' => ['ext' => []],
      'Address No.' => ['addressno' => []],
      'Street' => ['street' => []],
      'Town' => ['subdistown' => []],
      'City' => ['city' => []],
      'Zipcode' => ['Zipcode' => []],
      'Birth place' => ['bplace' => []],
      'Citizenship' => ['citizenship' => []],
      'Civil Status' => ['civilstatus' => []],
      'Father' => ['father' => []],
      'Mother' => ['mother' => []],
      'Height' => ['height' => []],
      'Weight' => ['weight' => []],

      'Side Car No.' => ['sidecarno' => []],
      'Chassis No.' => ['chassisno' => []]
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', $table, $table_log, $fields, $key, "CLIENT INFO");
  }

  private function employee_triggers()
  {
    $fields = [
      'empid' => ['empid' => []],
      // 'empfirst' => ['empfirst' => []],
      // 'empmiddle' => ['empmiddle' => []],
      // 'emplast' => ['emplast' => []],
      'role' => ['roleid' => [true, "name", "rolesetup", "line"]],
      'dept' => ['deptid' => [true, "clientname", "client", "clientid"]],
      'supervisor' => ['supervisorid' => [true, "clientname", "client", "clientid"]],
      'section' => ['sectid' => [true, "sectname", "section", "sectid"]],
      'division' => ['divid' => [true, "divname", "division", "divid"]],
      'shiftcode' => ['shiftid' => [true, "shftcode", "tmshifts", "line"]],
      'jobtitle' => ['jobid' => [true, "jobtitle", "jobthead", "line"]],
      'biometric' => ['biometricid' => [true, "terminal", "biometric", "line"]],
      'project' => ['projectid' => [true, "code", "projectmasterfile", "line"]],
      'ditemname' => ['itemid' => [true, "barcode", "item", "itemid"]],
      'city' => ['city' => []],
      'country' => ['country' => []],
      'zipcode' => ['zipcode' => []],
      'blood type' => ['blood' => []],
      'citizenship' => ['citizenship' => []],
      'religion' => ['religion' => []],
      'telno' => ['telno' => []],
      'mobileno' => ['mobileno' => []],
      'status' => ['status' => []],
      'bday' => ['bday' => []],
      'maiden name' => ['maidname' => []],
      'no. of child' => ['nochild' => []],
      'gender' => ['gender' => []],
      'alias' => ['alias' => []],
      'tax status' => ['teu' => []],
      'no. of dependents' => ['nodeps' => []],
      'pay mode' => ['paymode' => []],
      'class rate' => ['classrate' => []],
      'level' => ['level' => []],
      'hired' => ['hired' => []],
      'id barcode' => ['idbarcode' => []],
      'bank account' => ['bankacct' => []],
      'atm' => ['atm' => []],
      'is approver' => ['isapprover' => []],
      'tin' => ['tin' => []],
      'sss' => ['sss' => []],
      'hdmf' => ['hdmf' => []],
      'phic' => ['phic' => []],
      'chk tin' => ['chktin' => []],
      'chk sss' => ['chksss' => []],
      'chk philhealth' => ['chkphealth' => []],
      'chk HDMF' => ['chkpibig' => []],
      'days' => ['dyear' => []],
      'cola' => ['cola' => []],
      'sss def' => ['sssdef' => []],
      'philhealth def' => ['philhdef' => []],
      'pagibig def' => ['pibigdef' => []],
      'wtax def' => ['wtaxdef' => []],
      'agency' => ['agency' => []],
      'prob' => ['prob' => []],
      'regular' => ['regular' => []],
      'trainee' => ['trainee' => []],
      'emprate' => ['emprate' => []],
      'No Biometric' => ['isnobio' => []],
      'Meal Deduction' => ['mealdeduc' => []],
      'Branch' => ['branchid' => [true, "clientname", "client", "clientid"]],
    ];


    $this->settriggerlogs('employee_update', 'AFTER UPDATE', 'employee', 'client_log', $fields, 'empid', 'UPDATE');
  }

  private function item_triggers($config)
  {
    switch ($config['params']['companyid']) {
      case 16:
        $shortname = 'Specifications';
        $othcode = 'Barcode Name';
        break;
      case 60://transpower
        $shortname = 'Shortname';
        $othcode = 'Other Code';
        $retail = 'base price';
        $pricea ='distributor price';
        $priceb ='cost';
        $pricec ='invoice price';
        $priced = 'Lowest price';
        $pricee = 'dr price';
        $discountr= 'base discount';
        $discountw = 'wholesale discount';
        $discounta = 'distributor discount';
        $discountb = 'cost discount';
        $discountc = 'invoice disc';
        $discountd ='lowest disc';
        $discounte ='dr discount';
        break;
      default:
        $shortname = 'Shortname';
        $othcode = 'Other Code';
        $retail = 'retail price';
        $pricea ='price A';
        $priceb ='price B';
        $pricec ='price C';
        $priced = 'price D';
        $pricee = 'price E';
        $discountr= 'discount R';
        $discountw = 'discount W';
        $discounta = 'discount A';
        $discountb = 'discount B';
        $discountc = 'discount C';
        $discountd ='discount D';
        $discounte ='discount E';
        break;
    }



    //ITEM TRIGGER =====================================================================================================================
    $fields = [
      'code' => ['barcode' => []],
      'item name' => ['itemname' => []],
      'group' => ['groupid' => [true, 'stockgrp_name', 'stockgrp_masterfile', 'stockgrp_id']],
      'part' => ['part' => [true, 'part_name', 'part_masterfile', 'part_id']],
      'model' => ['model' => [true, 'model_name', 'model_masterfile', 'model_id']],
      'brand' => ['brand' => [true, 'brand_desc', 'frontend_ebrands', 'brandid']],
      'class' => ['class' => [true, 'cl_name', 'item_class', 'cl_id']],
      'Category' => ['category' => [true, 'name', 'itemcategory', 'line']],
      'Sub Category' => ['subcat' => [true, 'name', 'itemsubcategory', 'line']],
      'body' => ['body' => []],
      'size' => ['sizeid' => []],
      'Unit of Mesurement' => ['uom' => []],
      'minimum' => ['minimum' => []],
      'maximum' => ['maximum' => []],
      'Ave. Cost' => ['avecost' => []],
      $retail => ['amt' => []],
      'wholesale price' => ['amt2' => []],
      $pricea => ['famt' => []],
      $priceb => ['amt4' => []],
      $pricec => ['amt5' => []],
      $priced => ['amt6' => []],
      $pricee => ['amt7' => []],
      'price F' => ['amt8' => []],
      'price G' => ['amt9' => []],
      'markup' => ['markup' => []],
      $discountr => ['disc' => []],
      $discountw => ['disc2' => []],
      $discounta => ['disc3' => []],
      $discountb => ['disc4' => []],
      $discountc => ['disc5' => []],
      $discountd => ['disc6' => []],
      $discounte => ['disc7' => []],
      'discount F' => ['disc8' => []],
      'discount G' => ['disc9' => []],
      'foreign amount' => ['foramt' => []],
      'assets' => ['asset' => [true, 'acnoname', 'coa', 'acno']],
      'liability' => ['liability' => [true, 'acnoname', 'coa', 'acno']],
      'revenue' => ['revenue' => [true, 'acnoname', 'coa', 'acno']],
      'expense' => ['expense' => [true, 'acnoname', 'coa', 'acno']],
      'Critical' => ['critical' => []],
      'Reorder' => ['reorder' => []],
      'Supplier name' => ['supplier' => [true, 'clientname', 'client', 'clientid']],
      'SKU' => ['partno' => []],
      'Color' => ['color' => []],
      'Is Inactive' => ['isinactive' => []],
      'No System Input (NSI)' => ['isnsi' => []],
      'Vatable' => ['isvat' => []],
      'Imported' => ['isimport' => []],
      'Finished goods' => ['fg_isfinishedgood' => []],
      'Equipment Tool' => ['fg_isequipmenttool' => []],
      'Non Inventory' => ['isnoninv' => []], //isofficesupplies
      'Office Supplies' => ['isofficesupplies' => []],
      'Labor' => ['islabor' => []],
      'POS' => ['ispositem' => []],
      'Life Of Asset' => ['loa' => []],
      'Purchase Date' => ['dateid' => []],
      'Item Remarks' => ['itemrem' => []],
      'Channel' => ['channel' => []],
      $othcode => ['othcode' => []],
      $shortname => ['shortname' => []]
    ];

    $customtrigger = "if OLD.dlock<>NEW.dlock and New.ispositem = 1 then  delete from itemdlock where itemid = Old.itemid; insert into itemdlock(itemid,dlock)values(Old.itemid,New.dlock); end if;                       
                      if OLD.amt<>NEW.amt then insert into prchange (itemid, dateid, prgroup, price, oldprice, userid) values (Old.itemid,New.editdate,'amt',New.amt,Old.amt,New.editby); delete from priceupdate where itemid=Old.itemid; insert into priceupdate (itemid,dateid) values (Old.itemid,New.editdate); end if; 
                      if OLD.amt2<>NEW.amt2 then insert into prchange (itemid, dateid, prgroup, price, oldprice, userid) values (Old.itemid,New.editdate,'amt2',New.amt2,Old.amt2,New.editby); delete from priceupdate where itemid=Old.itemid; insert into priceupdate (itemid,dateid) values (Old.itemid,New.editdate); end if; 
                      if OLD.famt<>NEW.famt then insert into prchange (itemid, dateid, prgroup, price, oldprice, userid) values (Old.itemid,New.editdate,'famt',New.famt,Old.famt,New.editby); delete from priceupdate where itemid=Old.itemid; insert into priceupdate (itemid,dateid) values (Old.itemid,New.editdate); end if; 
                      if OLD.amt4<>NEW.amt4 then insert into prchange (itemid, dateid, prgroup, price, oldprice, userid) values (Old.itemid,New.editdate,'amt4',New.amt4,Old.amt4,New.editby); delete from priceupdate where itemid=Old.itemid; insert into priceupdate (itemid,dateid) values (Old.itemid,New.editdate); end if; 
                      if OLD.amt5<>NEW.amt5 then insert into prchange (itemid, dateid, prgroup, price, oldprice, userid) values (Old.itemid,New.editdate,'amt5',New.amt5,Old.amt5,New.editby); delete from priceupdate where itemid=Old.itemid; insert into priceupdate (itemid,dateid) values (Old.itemid,New.editdate); end if; 
                      if OLD.amt6<>NEW.amt6 then insert into prchange (itemid, dateid, prgroup, price, oldprice, userid) values (Old.itemid,New.editdate,'amt6',New.amt6,Old.amt6,New.editby); delete from priceupdate where itemid=Old.itemid; insert into priceupdate (itemid,dateid) values (Old.itemid,New.editdate); end if; 
                      if OLD.amt7<>NEW.amt7 then insert into prchange (itemid, dateid, prgroup, price, oldprice, userid) values (Old.itemid,New.editdate,'amt7',New.amt7,Old.amt7,New.editby); delete from priceupdate where itemid=Old.itemid; insert into priceupdate (itemid,dateid) values (Old.itemid,New.editdate); end if; 
                      if OLD.amt8<>NEW.amt8 then insert into prchange (itemid, dateid, prgroup, price, oldprice, userid) values (Old.itemid,New.editdate,'amt8',New.amt8,Old.amt8,New.editby); delete from priceupdate where itemid=Old.itemid; insert into priceupdate (itemid,dateid) values (Old.itemid,New.editdate); end if; 
                      if OLD.amt9<>NEW.amt9 then insert into prchange (itemid, dateid, prgroup, price, oldprice, userid) values (Old.itemid,New.editdate,'amt9',New.amt9,Old.amt9,New.editby); delete from priceupdate where itemid=Old.itemid; insert into priceupdate (itemid,dateid) values (Old.itemid,New.editdate); end if; ";

    $this->settriggerlogs('ITEM_update', 'AFTER UPDATE', 'item', 'item_log', $fields, 'itemid', 'STOCKCARD', $customtrigger);
    //END OF ITEM TRIGGER ====================================================================================================================================    

    //ITEMBAL TRIGGER ==========================================================================================================================================
    $qry = "create TRIGGER itembal_update BEFORE UPDATE on itembal FOR EACH ROW
        BEGIN
          if NEW.BAL<0 then
            CALL OUT_OF_STOCK;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF ITEMBAL TRIGGER ====================================================================================================================================

    //ITEM INSERT TRIGGER ==========================================================================================================================================
    $qry = "create TRIGGER item_insert AFTER INSERT on item FOR EACH ROW
        BEGIN
          if NEW.ispositem = 1 then
            insert into itemdlock(itemid,dlock)values(nEW.itemid,New.dlock);
          end if;

        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF ITEMBAL TRIGGER ====================================================================================================================================  
  }

  // private  function pricelist_trigger()  ===existing sa masterfile trigger
  // {
  //   $fields = [
  //     'Start Date' => ['startdate' => []],
  //     'End Date' => ['enddate' => []],
  //     'Price 1' => ['amount' => []],
  //     'Price 2' => ['amount2' => []],
  //     'Remarks' => ['remarks' => []],
  //     'Cost' => ['cost' => []],
  //     'Customer Code' => ['clientid' => [true, 'client', 'client', 'clientid']],
  //   ];
  //   $this->settriggerlogs('pricelist_update', 'AFTER UPDATE', 'pricelist', 'item_log', $fields, 'itemid', 'pricelist');
  // }

  private  function bom_trigger()
  {
    $fields = [
      'Date' => ['dateid' => []],
      'UOM' => ['uom2' => []],
      'Batch size' => ['batchsize' => []],
      'Yield' => ['yield' => []],
      'Rem' => ['rem' => []],
      'Customer Code' => ['bclientid' => [true, 'client', 'client', 'clientid']],
      'Customer Name' => ['bclientname' => []],
    ];
    $this->settriggerlogs('bom_update', 'AFTER UPDATE', 'bom', 'item_log', $fields, 'itemid', 'BOM');
  }

  private function la_triggers($config)
  {
    $companyid = $config['params']['companyid'];
    $crref = 'crref';
    $priority = 'priority level';
    $sdate1 = 'Start Date';
    $sdate2 = 'End Date';
    $strdate1 = 'Start Date';
    $strdate2 = 'End Date';
    switch ($companyid) {
      case 19:
        $crref = 'Request Order No.';
        break;
      case 24:
        $priority = 'type';
        break;
      case 43: //mighty
        $strdate1 = 'Arrived/Dispatch Arrive Date';
        $strdate2 = 'Arrived/Dispatch Depart Date';
        break;
    }

    // GLEN 11.23.2021
    // cntnuminfo TRIGGER
    $fields = [
      'forwarder' => ['truckid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'Remarks' => ['rem2' => []],
      'Remarks' => ['rem3' => []],
      'Release date' => ['releasedate' => []],
      $sdate1 => ['sdate1' => []],
      $sdate2 => ['sdate2' => []],
      $strdate1 => ['strdate1' => []],
      $strdate2 => ['strdate2' => []],
      'Approved' => ['isapproved' => []],
      'Returned' => ['isreturned' => []],
      'refunded' => ['isrefunded' => []],
      'Ship date' => ['shipdate' => []],
      'Trip Date' => ['tripdate' => []],
      'Received Date' => ['receivedate' => []],
    ];
    $this->settriggerlogs('cntnuminfo_update', 'AFTER UPDATE', 'cntnuminfo', 'table_log', $fields, 'trno', 'HEAD');

    $this->settriggerlogs('hcntnuminfo_update', 'AFTER UPDATE', 'hcntnuminfo', 'table_log', $fields, 'trno', 'HEAD');

    //LADETAIl TRIGGER =================================================================================================================
    $fields = [
      'debit' => ['db' => []],
      'credit' => ['cr' => []],
      'client code' => ['client' => []],
      'account #' => ['acnoid' => [true, 'acno', 'coa', 'acnoid']],
      'account name' => ['acnoid' => [true, 'acnoname', 'coa', 'acnoid']],
      'check #' => ['checkno' => []],
      'remarks' => ['rem' => []],
      'reference' => ['ref' => []],
      'Date' => ['postdate' => []],
      'ewt code' => ['ewtcode' => []],
      'ewt rate' => ['ewtrate' => []],
      'Full Payment' => ['isdp' => []],
      'Approved Amt' => ['appamt' => []],
    ];
    $this->settriggerlogs('ladetail_update', 'AFTER UPDATE', 'ladetail', 'table_log', $fields, 'trno', 'DETAIL');
    //END OF LADETAIL TRIGGER ====================================================================================================================================

    //LAHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client code' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'contra account' => ['contra' => [true, 'concat(acno,"~",acnoname)', 'coa', 'acno']],
      'address' => ['address' => []],
      'ship to' => ['shipto' => []],
      'warehouse' => ['wh' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'tax' => ['tax' => []],
      'date' => ['dateid' => []],
      'due' => ['due' => []],
      'ewt' => ['ewt' => []],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']],
      'vattype' => ['vattype' => []],
      'agent' => ['agent' =>  [true, 'concat(client,"~",clientname)', 'client', 'client']],
      'deliverytype' => ['deliverytype' => [true, "name", "deliverytype", "line"]],
      'customername' => ['customername' => []],
      $priority => ['statid' => [true, 'status', 'trxstatus', 'line']],
      'Shipping Address' => ['shipid' => [true, 'addr', 'billingaddr', 'line']],
      'Billing Address' => ['billid' => [true, 'addr', 'billingaddr', 'line']],
      'Shipping Contact Person' => ['shipcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      'Billing Contact Person' => ['billcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      // 'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
      'sub project' => ['subproject' => [true, 'subproject', 'subproject', 'line']],
      $crref => ['crref' => []],
      'Delivery date' => ['deldate' => []],
      'Return date' => ['returndate' => []],
      'Refund date' => ['refunddate' => []],
      'amount' => ['amount' => []],
      'Check #' => ['checkno' => []],
      'Check date' => ['checkdate' => []],
      'Tripping' => ['istrip' => []],
      'Start Date' => ['sdate1' => []],
      'End Date' => ['sdate2' => []],
      $strdate1 => ['strdate1' => []],
      $strdate2 => ['strdate2' => []],
      'Fixed Asset' => ['isfa' => []],
      'Cost Code' => ['costcodeid' => [true, 'name', 'costcode_masterfile', 'line']],
      'Employee' => ['empid' =>  [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
    ];

    $this->settriggerlogs('lahead_update', 'AFTER UPDATE', 'lahead', 'table_log', $fields, 'trno', 'HEAD');
    //END OF LAHEAD TRIGGER ====================================================================================================================================

    //LASTOCK TRIGGER ==================================================================================================================
    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'uom' => ['uom' => []],
      'amount' => ['rrcost' => []],
      'amount' => ['isamt' => []],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'qty' => ['isqty' => []],
      'note' => ['rem' => []],
      'location' => ['loc' => []],
      'ref' => ['ref' => []],
      'freight' => ['freight' => []],
      'expiry date' => ['expiry' => []],
      'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
      'Agent Amount' => ['agentamt' => []],
      'Start Wire' => ['startwire' => []],
      'End Wire' => ['endwire' => []]
    ];

    $this->settriggerlogs('lastock_update', 'AFTER UPDATE', 'lastock', 'table_log', $fields, 'trno', 'STOCK');
    //END OF LASTOCK TRIGGER ====================================================================================================================================

    //PARTICULARS TRIGGER =================================================================================================================
    $fields = [
      'rem' => ['rem' => []],
      'amount' => ['amount' => []],
      'station' => ['station' => []],
      'serial' => ['serial' => []],
      'remarks' => ['remarks' => []],
      'others' => ['others' => []],
    ];
    $this->settriggerlogs('particular_update', 'AFTER UPDATE', 'particulars', 'table_log', $fields, 'trno', 'PARTICULARS');
    //END OF PARTICULARS TRIGGER ====================================================================================================================================
  }

  private function cd_triggers($config)
  {
    $companyid = $config['params']['companyid'];
    //HCDSTOCK TRIGGER canvass==========================================================================================================================================
    $qry = "create TRIGGER hcdstock_update BEFORE UPDATE on hcdstock FOR EACH ROW
        BEGIN

          if New.qa>New.qty then
            CALL QTY_IS_GREATER_THAN_CANVASS;
          end if;

          if OLD.oqqa<>NEW.oqqa then
            if New.oqqa>New.qty then
              CALL QTY_IS_GREATER_THAN_CANVAS;
            end if;
          end if;

        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'cost' => ['rrcost' => []],
      'total' => ['ext' => []],
      'approved qty' => ['rrqty' => []],
      'uom' => ['uom' => []]
    ];

    $this->settriggerlogs('hcdstock_update_after', 'AFTER UPDATE', 'hcdstock', 'transnum_log', $fields, 'trno', 'STOCK');
    //END OF HCDSTOCK TRIGGER ====================================================================================================================================

    $yourref = 'your ref';
    if ($companyid == 16) {
      $yourref = 'po type';
    }
    //CDHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      $yourref => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
    ];
    $this->settriggerlogs('cdhead_update', 'AFTER UPDATE', 'cdhead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF CDHEAD TRIGGER ====================================================================================================================================    

    //CDSTOCK TRIGGER canvass ==========================================================================================================
    $qry = "create TRIGGER cdstock_update_before BEFORE UPDATE on cdstock FOR EACH ROW
        BEGIN

         if New.QA>New.QTY then
            CALL QTY_IS_GREATER_THAN_CANVASS;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'product id' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'product name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'amount' => ['rrcost' => []],
      'notes' => ['rem' => []],
      'uom' => ['uom' => []],
    ];

    $this->settriggerlogs('cdstock_update_after', 'AFTER UPDATE', 'cdstock', 'transnum_log', $fields, 'trno', 'STOCK');

    $qry = "create TRIGGER cdstock_delete BEFORE DELETE on cdstock FOR EACH ROW
        BEGIN

          if OLD.QA<>0 then
            CALL QTY_SERVED_CANNOT_DELETE_CDSTOCK;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
  }

  private function hsgstock_triggers()
  {
    $qry = "create TRIGGER hsgstock_before_update BEFORE UPDATE on hsgstock FOR EACH ROW
        BEGIN
          if OLD.qa<>NEW.qa then
          if New.qa>Old.iss then
              CALL QTY_IS_GREATER_THAN_PARTS_REQUEST;
            end if;
          end if;

          if OLD.waqa<>NEW.waqa then
            if (New.qa + New.waqa)>New.iss then
              CALL QTY_IS_GREATER_THAN_PARTS_REQUEST;
            end if;
          end if;

        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $qry = "create TRIGGER hsgstock_delete BEFORE DELETE on hsgstock FOR EACH ROW
        BEGIN

          if OLD.QA<>0 then
            CALL QTY_SERVED_CANNOT_DELETE;
          end if;

          if OLD.WAQA<>0 then
            CALL QTY_SERVED_CANNOT_DELETE;
          end if;          
        END";

    $this->coreFunctions->execqry($qry, 'trigger');
  }

  private function pr_triggers($config)
  {

    $cdqa = "if OLD.cdqa<>NEW.cdqa then
         if (New.qa + New.cdqa)>New.qty then
            CALL CANVASS_QTY_IS_GREATER_THAN_PURCHASE_REQUEST;
          end if;
        end if;";

    if ($config['params']['companyid'] == 16) {
      $cdqa = "";
    }

    //HPRSTOCK TRIGGER =================================================================================================================
    $qry = "create TRIGGER hprstock_update_before BEFORE UPDATE on hprstock FOR EACH ROW
        BEGIN

        if OLD.qa<>NEW.qa then
         if (New.qa)>New.qty then
            CALL QTY_IS_GREATER_THAN_PURCHASE_REQUEST;
          end if;
        end if;

        " . $cdqa . "

        if OLD.oqqa<>NEW.oqqa then
         if (New.qa + New.oqqa)>New.qty then
            CALL OCR_QTY_IS_GREATER_THAN_PURCHASE_REQUEST;
          end if;
        end if;        

        if OLD.tsqa<>NEW.tsqa then
         if New.tsqa>New.qty then
            CALL TRANSFER_QTY_IS_GREATER_THAN_PURCHASE_REQUEST;
          end if;
        end if;

        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $ourref = 'Our ref';
    if ($config['params']['companyid'] == 16) {
      $ourref = 'Category';
    }

    //PRHEAD TRIGGER ===================================================================================================================
    $fields = [
      'department' => ['client' => []],
      'department name' => ['clientname' => []],
      'address' => ['address' => []],
      'date' => ['dateid' => []],
      'required Date' => ['due' => []],
      'warehouse' => ['wh' => []],
      'notes' => ['rem' => []],
      'your ref' => ['yourref' => []],
      $ourref => ['ourref' => []],
      'sub contractor' => ['subcontractorid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']],
      'sub project' => ['subproject' => [true, 'subproject', 'subproject', 'line']],
      'SA No.' => ['sano' => [true, 'sano', 'clientsano', 'line']],
      'SVS No.' => ['svsno' => [true, 'sano', 'clientsano', 'line']],
      'PO No.' => ['pono' => [true, 'sano', 'clientsano', 'line']],
      'PO Type' => ['potype' => []],
    ];
    $this->settriggerlogs('prhead_update', 'AFTER UPDATE', 'prhead', 'transnum_log', $fields, 'trno', 'HEAD');
    // END PRHEAD TRIGGER =============================================================================================================+


    //PRSTOCK TRIGGER ==================================================================================================================
    $qry = "create TRIGGER prstock_update_before BEFORE UPDATE on prstock FOR EACH ROW
        BEGIN

          if Old.rqty<>New.rqty and Old.rrqty<>0 then
            CALL QTY_ALREADY_APPROVE;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'qty' => ['rrqty' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
      'item status' => ['status' => [true, 'status', 'trxstatus', 'line']],
      'assigned user' => ['suppid' => [true, 'clientname', 'client', 'clientid']],
      'notes' => ['rem' => []],
      'qty' => ['rrqty' => []],
      'amount' => ['rrcost' => []],
      'uom' => ['uom' => []],
      'total' => ['ext' => []]
    ];

    $this->settriggerlogs('prstock_update_after', 'AFTER UPDATE', 'prstock', 'transnum_log', $fields, 'trno', 'STOCK');
    //END PRSTOCK TRIGGER ==============================================================================================================

    //hPRSTOCK TRIGGER ==================================================================================================================
    $fields = [
      'item status' => ['status' => [true, 'status', 'trxstatus', 'line']],
      'assigned user' => ['suppid' => [true, 'clientname', 'client', 'clientid']],
      'Product ID' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'Product Name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
    ];

    $this->settriggerlogs('hprstock_update_after', 'AFTER UPDATE', 'hprstock', 'transnum_log', $fields, 'trno', 'STOCK');
    //END hPRSTOCK TRIGGER ==============================================================================================================
  }

  private function hpr_triggers($config)
  {
    $ourref = 'Our ref';
    if ($config['params']['companyid'] == 16) {
      $ourref = 'Category';
    }
    $fields = [
      $ourref => ['ourref' => [true, 'category', 'reqcategory', 'line']],
      'SA No' => ['sano' => [true, 'sano', 'clientsano', 'line']],
      'SVS No' => ['svsno' => [true, 'sano', 'clientsano', 'line']],
      'PO No' => ['pono' => [true, 'sano', 'clientsano', 'line']],
      'Customer' => ['client' => [true, 'clientname', 'client', 'client']],
      'notes' => ['rem' => []],
      'po type' => ['potype' => []],
    ];
    $this->settriggerlogs('hprhead_update', 'AFTER UPDATE', 'hprhead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function glhead_triggers($config)
  {
    $companyid = $config['params']['companyid'];
    $sdate1 = 'Start Date';
    $sdate2 = 'End Date';
    $strdate1 = 'Start Date';
    $strdate2 = 'End Date';
    switch ($companyid) {
      case 43: //mighty
        $strdate1 = 'Arrived/Dispatch Arrive Date';
        $strdate2 = 'Arrived/Dispatch Depart Date';
        break;
    }

    $fields = [
      'Yourref' => ['yourref' => []],
      $sdate1 => ['sdate1' => []],
      $sdate2 => ['sdate2' => []],
      $strdate1 => ['strdate1' => []],
      $strdate2 => ['strdate2' => []],
      'Rem' => ['rem' => []],
    ];
    $this->settriggerlogs('glhead_update', 'AFTER UPDATE', 'glhead', 'table_log', $fields, 'trno', 'HEAD');
  }
  private function hparticulars_triggers($config)
  {
    //HPARTICULARS TRIGGER =================================================================================================================
    $fields = [
      'station' => ['station' => []],
      'serial' => ['serial' => []],
      'remarks' => ['remarks' => []],
      'others' => ['others' => []],
    ];
    $this->settriggerlogs('hparticular_update', 'AFTER UPDATE', 'hparticulars', 'table_log', $fields, 'trno', 'PARTICULARS');
    //END OF HPARTICULARS TRIGGER ====================================================================================================================================
  }


  private function serialin_triggers($config)
  {
    //serialin TRIGGER =================================================================================================================
    $field = "Serial";
    if ($config['params']['companyid'] == 40) {
      $field = "Engine#";
    }
    $fields = [
      $field => ['serial' => []],
      'Chassis#' => ['chassis' => []],
      'Color' => ['color' => []],
      'PNP#' => ['pnp' => []],
      'CSR#' => ['csr' => []],
      'Remarks' => ['remarks' => []],

    ];
    $this->settriggerlogs('serialin_update', 'AFTER UPDATE', 'serialin', 'table_log', $fields, 'trno', 'SERIALIN');
    //END OF serialin TRIGGER ====================================================================================================================================
  }



  private function glstock_triggers()
  {
    $qry = "create TRIGGER glstock_update BEFORE UPDATE on glstock FOR EACH ROW
        BEGIN

          if New.qa>(New.qty+New.iss) then
            CALL QTY_IS_GREATER_THAN_REF;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'uom' => ['uom' => []],
    ];

    $this->settriggerlogs('glstock_after_update', 'AFTER UPDATE', 'glstock', 'table_log', $fields, 'trno', 'STOCK');
  }

  private function hcnstock_triggers()
  {
    $qry = "create TRIGGER hcnstock_update BEFORE UPDATE on hcnstock FOR EACH ROW
        BEGIN

          if New.qa>(New.iss) then
            CALL QTY_IS_GREATER_THAN_REF;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
  }
  private function hpo_triggers($config)
  {
    $yourref = 'your ref';
    $ourref = 'our ref';
    if ($config['params']['companyid'] == 16) {
      $yourref = 'PO No.';
      $ourref = 'po type';
    }
    //POHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      $yourref => ['yourref' => []],
      $ourref => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []]
    ];
    $this->settriggerlogs('hpohead_update', 'AFTER UPDATE', 'hpohead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF POHEAD TRIGGER ====================================================================================================================================    
  }

  private function po_triggers($config)
  {
    //HPOSTOCK TRIGGER =================================================================================================================
    $qry = "create TRIGGER hpostock_update BEFORE UPDATE on hpostock FOR EACH ROW
        BEGIN

          if New.qa>New.qty then
            CALL QTY_IS_GREATER_THAN_PO;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF HPOSTOCK TRIGGER ====================================================================================================================================

    $yourref = 'your ref';
    $ourref = 'our ref';
    if ($config['params']['companyid'] == 16) {
      $yourref = 'PO No.';
      $ourref = 'po type';
    }
    //POHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      $yourref => ['yourref' => []],
      $ourref => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
      'Fixed Asset' => ['isfa' => []],
    ];
    $this->settriggerlogs('pohead_update', 'AFTER UPDATE', 'pohead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF POHEAD TRIGGER ====================================================================================================================================    

    //POSTOCK TRIGGER ==================================================================================================================
    $qry = "create TRIGGER postock_update_before BEFORE UPDATE on postock FOR EACH ROW
        BEGIN

         if New.QA>New.QTY then
            CALL QTY_IS_GREATER_THAN_PO;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'qty' => ['rrqty' => []],
      'amount' => ['rrcost' => []],
      'discount' => ['disc' => []],
      'uom' => ['uom' => []],
      'warehouse' => ['whid' => []],
      'Product ID' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'Product Name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'notes' => ['rem' => []],
      'total' => ['ext' => []],
    ];
    $this->settriggerlogs('postock_update_after', 'AFTER UPDATE', 'postock', 'transnum_log', $fields, 'trno', 'STOCK');


    $qry = "create TRIGGER postock_delete BEFORE DELETE on postock FOR EACH ROW
        BEGIN

          if OLD.QA<>0 then
            CALL QTY_SERVED_CANNOT_DELETE_POSTOCK;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF postock TRIGGER ====================================================================================================================================    
  }



  private function pf_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []]
    ];
    $this->settriggerlogs('pfhead_update', 'AFTER UPDATE', 'pfhead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => []],
      'Product ID' => ['itemid' => []],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'amount' => ['rrcost' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('pfstock_update_after', 'AFTER UPDATE', 'pfstock', 'transnum_log', $fields, 'trno', 'STOCK');
  }

  private function ra_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'client code' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'contra account' => ['contra' => [true, 'concat(acno,"~",acnoname)', 'coa', 'acno']],
      'address' => ['address' => []],
      'ship to' => ['shipto' => []],
      'warehouse' => ['wh' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'tax' => ['tax' => []],
      'date' => ['dateid' => []],
      'due' => ['due' => []],
      'ewt' => ['ewt' => []],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']],
      'vattype' => ['vattype' => []],
      'agent' => ['agent' =>  [true, 'concat(client,"~",clientname)', 'client', 'client']],
      'deliverytype' => ['deliverytype' => [true, "name", "deliverytype", "line"]],
      'customername' => ['customername' => []],
      'priority level' => ['statid' => [true, 'status', 'trxstatus', 'line']],
      'Shipping Address' => ['shipid' => [true, 'addr', 'billingaddr', 'line']],
      'Billing Address' => ['billid' => [true, 'addr', 'billingaddr', 'line']],
      'Shipping Contact Person' => ['shipcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      'Billing Contact Person' => ['billcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      // 'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
      'sub project' => ['subproject' => [true, 'subproject', 'subproject', 'line']],
    ];

    $this->settriggerlogs('rahead_update', 'AFTER UPDATE', 'rahead', 'table_log', $fields, 'trno', 'HEAD');

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'uom' => ['uom' => []],
      'rrcost' => ['rrcost' => []],
      'isamt' => ['isamt' => []],
      'discount' => ['disc' => []],
      'rrqty' => ['rrqty' => []],
      'isqty' => ['isqty' => []],
      'note' => ['rem' => []],
      'location' => ['loc' => []],
      'ref' => ['ref' => []],
      'expiry date' => ['expiry' => []],
      'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
    ];

    $this->settriggerlogs('rastock_update', 'AFTER UPDATE', 'rastock', 'table_log', $fields, 'trno', 'STOCK');
  }

  private function pl_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'date' => ['dateid' => []],
      'Remarks' => ['rem' => []],
      'Packing List No.' => ['plno' => []],
      'Shipment No.' => ['shipmentno' => []],
      'Proforma Invoice No.' => ['invoiceno' => []],
    ];
    $this->settriggerlogs('plhead_update', 'AFTER UPDATE', 'plhead', 'transnum_log', $fields, 'trno', 'HEAD');

    $qry = "create TRIGGER hplstock_update BEFORE UPDATE on hplstock FOR EACH ROW
        BEGIN

          if New.qa>New.qty then
            CALL QTY_IS_GREATER_THAN_PL;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $qry = "create TRIGGER hplstock_delete BEFORE DELETE on hplstock FOR EACH ROW
    BEGIN

      if OLD.QA<>0 then
        CALL QTY_SERVED_CANNOT_DELETE_PLSTOCK;
      end if;
    END";
    $this->coreFunctions->execqry($qry, 'trigger');
  }

  private function wa_triggers()
  {

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['clientid' => [true, 'client', 'client', 'clientid']],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'date' => ['dateid' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'Notes' => ['rem' => []],
      'currency' => ['cur' => []],
      'forex' => ['forex' => []],
    ];
    $this->settriggerlogs('wahead_update', 'AFTER UPDATE', 'wahead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'qty' => ['rrqty' => []],
      'qty' => ['qty' => []],
      'uom' => ['uom' => []],
      'amount' => ['cost' => []],
      'amount' => ['rrcost' => []],
      'discount' => ['disc' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'total' => ['ext' => []],
      'Product ID' => ['itemid' => []],
      'notes' => ['rem' => []],
      'reference' => ['ref' => []],
      'void' => ['void' => []],
    ];
    $this->settriggerlogs('wastock_update_after', 'AFTER UPDATE', 'wastock', 'transnum_log', $fields, 'trno', 'STOCK');

    $qry = "create TRIGGER hwastock_update BEFORE UPDATE on hwastock FOR EACH ROW
        BEGIN

          if New.qa>New.qty then
            CALL QTY_IS_GREATER_THAN_PO;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
  }

  private function frontend_triggers()
  {
    $qry = "create TRIGGER frontend_highlights_update BEFORE UPDATE on frontend_highlights FOR EACH ROW
        BEGIN
          if NEW.promostart <> '0000-00-00' AND NEW.promostart <> '0000-00-00' AND NEW.promoend <> '1900-01-01' AND NEW.promoend <> '1900-01-01' then
                 if NEW.promostart <> OLD.promostart OR NEW.promoend <> OLD.promoend then
                      insert into frontend_endedsale (itemid,datestart,dateend,highlight)
                      select hitem.itemid,highlights.promostart,left(highlights.promoend,10),hitem.highid from frontend_highlightitems as hitem
                      left join frontend_highlights as highlights on highlights.highid = hitem.highid
                      where hitem.highid = OLD.highid;
                 end if;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
  }


  private function sa_triggers()
  {

    //SAHEAD TRIGGER =============================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client code' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'forwarder' => ['truckid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'date' => ['dateid' => []],
      'terms' => ['terms' => []],
      'due' => ['due' => []],
      'wh' => ['wh' => [true, 'concat(client,"~",clientname)', 'client', 'client']],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'currency' => ['cur' => []],
      'cur' => ['forex' => []],
      'deliverytype' => ['deliverytype' => [true, "name", "deliverytype", "line"]],
      'remarks' => ['rem' => []],
    ];
    $this->settriggerlogs('sahead_update', 'AFTER UPDATE', 'sahead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF sahead TRIGGER ====================================================================================================================================


    //sastock TRIGGER ================================================================================================================== 
    $qry = "create TRIGGER sastock_update_before BEFORE UPDATE on sastock FOR EACH ROW
        BEGIN

         if New.QA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SA;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['isqty' => []],
      'amount' => ['isamt' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('sastock_update_after', 'AFTER UPDATE', 'sastock', 'transnum_log', $fields, 'trno', 'STOCK');

    $qry = "create TRIGGER sastock_delete BEFORE DELETE on sastock FOR EACH ROW
        BEGIN
         
           if OLD.QA<>0 then
             CALL QTY_SERVED_CANNOT_DELETE;
           end if;
         END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF sastock TRIGGER ====================================================================================================================================


    //HSASTOCK TRIGGER =================================================================================================================
    $qry = "create TRIGGER hsastock_update BEFORE UPDATE on hsastock FOR EACH ROW
      BEGIN
       if New.QA>New.ISS then
          CALL QTY_IS_GREATER_THAN_SO;
        end if;
      END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF HSASTOCK TRIGGER ====================================================================================================================================    

  }

  private function sb_triggers()
  {

    //SBHEAD TRIGGER =============================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'agent' => ['agent' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
      'customername' => ['customername' => []],
      'forwarder' => ['truckid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
    ];
    $this->settriggerlogs('sbhead_update', 'AFTER UPDATE', 'sbhead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF sbhead TRIGGER ====================================================================================================================================


    //sbstock TRIGGER ================================================================================================================== 
    $qry = "create TRIGGER sbstock_update_before BEFORE UPDATE on sbstock FOR EACH ROW
        BEGIN

         if New.QA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SO;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['isqty' => []],
      'amount' => ['isamt' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('sbstock_update_after', 'AFTER UPDATE', 'sbstock', 'transnum_log', $fields, 'trno', 'STOCK');

    $qry = "create TRIGGER sbstock_delete BEFORE DELETE on sbstock FOR EACH ROW
        BEGIN
         
           if OLD.QA<>0 then
             CALL QTY_SERVED_CANNOT_DELETE;
           end if;
         END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF sbstock TRIGGER ====================================================================================================================================


    //HSBSTOCK TRIGGER =================================================================================================================
    $qry = "create TRIGGER hsbstock_update BEFORE UPDATE on hsbstock FOR EACH ROW
      BEGIN
       if New.QA>New.ISS then
          CALL QTY_IS_GREATER_THAN_SB;
        end if;
      END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF HSBSTOCK TRIGGER ====================================================================================================================================    

  } // end

  private function sc_triggers()
  {

    //SCHEAD TRIGGER =============================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'agent' => ['agent' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
      'tel' => ['tel' => []],
      'customername' => ['customername' => []],
    ];
    $this->settriggerlogs('schead_update', 'AFTER UPDATE', 'schead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF schead TRIGGER ====================================================================================================================================


    //scstock TRIGGER ================================================================================================================== 
    $qry = "create TRIGGER scstock_update_before BEFORE UPDATE on scstock FOR EACH ROW
        BEGIN

         if New.QA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SC;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['isqty' => []],
      'amount' => ['isamt' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('scstock_update_after', 'AFTER UPDATE', 'scstock', 'transnum_log', $fields, 'trno', 'STOCK');

    $qry = "create TRIGGER scstock_delete BEFORE DELETE on scstock FOR EACH ROW
        BEGIN
         
           if OLD.QA<>0 then
             CALL QTY_SERVED_CANNOT_DELETE;
           end if;
         END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF scstock TRIGGER ====================================================================================================================================


    //HSCSTOCK TRIGGER =================================================================================================================
    $qry = "create TRIGGER hscstock_update BEFORE UPDATE on hscstock FOR EACH ROW
      BEGIN
       if New.QA>New.ISS then
          CALL QTY_IS_GREATER_THAN_SC;
        end if;
      END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF HSCSTOCK TRIGGER ====================================================================================================================================    

  } // end

  private function mr_triggers()
  {
    $qry = "create TRIGGER hmrstock_update BEFORE UPDATE on hmrstock FOR EACH ROW
        BEGIN
         if New.QA>New.ISS then
            CALL QTY_IS_GREATER_THAN_MR;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
  }


  // private function mi_triggers()
  // {
  //   $qry = "create TRIGGER hprstock_update BEFORE UPDATE on hprstock FOR EACH ROW
  //       BEGIN
  //        if New.MAXQTY<>0 then 
  //         if New.QA>New.MAXQTY then
  //             CALL QA_IS_GREATER_THAN_MAXQTY;
  //         end if;
  //        end if;
  //       END";
  //   $this->coreFunctions->execqry($qry, 'trigger');
  // }

  private function qs_triggers()
  {
    //hqsstock TRIGGER ================================================================================================================== 
    $qry = "create TRIGGER hqsstock_update_before BEFORE UPDATE on hqsstock FOR EACH ROW
        BEGIN

          if New.SJQA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SO;
          end if;

        
          if Old.POQA <> New.POQA then
            if New.POQA > New.ISS then
              CALL QTY_IS_GREATER_THAN_SO;
            end if;          
          end if;

          if Old.voidqty <> New.voidqty then
            if (New.voidqty + New.sjqa) >New.iss then
              CALL QTY_IS_GREATER_THAN_SO;
            end if;          
          end if;
          
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $qry = "create TRIGGER hqsstock_delete BEFORE DELETE on hqsstock FOR EACH ROW
        BEGIN
         
           if OLD.SJQA<>0 then
             CALL QTY_SERVED_IN_SALES_JOURNAL_CANNOT_DELETE;
           end if;

           if OLD.POQA<>0 then
             CALL QTY_SERVED_IN_PURCHASE_ORDER_CANNOT_DELETE;
           end if;
           
           if OLD.voidqty<>0 then
             CALL QTY_VOID_CANNOT_DELETE;
           end if;
         END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF hsqstock TRIGGER ====================================================================================================================================
  }

  private function sr_triggers()
  {
    //hqsstock TRIGGER ================================================================================================================== 
    $qry = "create TRIGGER hsrstock_update_before BEFORE UPDATE on hsrstock FOR EACH ROW
        BEGIN

          if New.SJQA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SO;
          end if;

        
          if Old.POQA <> New.POQA then
            if New.POQA >New.ISS then
              CALL QTY_IS_GREATER_THAN_SO;
            end if;          
          end if;

          if Old.voidqty <> New.voidqty then
            if New.voidqty + New.sjqa>New.iss then
              CALL QTY_IS_GREATER_THAN_SO;
            end if;          
          end if;
          
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $qry = "create TRIGGER hsrstock_delete BEFORE DELETE on hsrstock FOR EACH ROW
        BEGIN
         
           if OLD.SJQA<>0 then
             CALL QTY_SERVED_IN_SALES_JOURNAL_CANNOT_DELETE;
           end if;

           if OLD.POQA<>0 then
             CALL QTY_SERVED_IN_PURCHASE_ORDER_CANNOT_DELETE;
           end if;
           
           if OLD.voidqty<>0 then
             CALL QTY_VOID_CANNOT_DELETE;
           end if;
         END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF hsqstock TRIGGER ====================================================================================================================================
  }

  private function so_triggers()
  {
    //HSOSTOCK TRIGGER =================================================================================================================
    $qry = "create TRIGGER hsostock_update BEFORE UPDATE on hsostock FOR EACH ROW
        BEGIN
         if New.QA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SO;
          end if;

         if New.MRSQA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SO;
          end if;

         if New.roqa>New.iss then
            CALL RO_QTY_IS_GREATER_THAN_SO;
          end if;          
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF HSOSTOCK TRIGGER ====================================================================================================================================    



    //SOHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'agent' => ['agent' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']],
      'sub project' => ['subproject' => [true, 'subproject', 'subproject', 'line']],
    ];
    $this->settriggerlogs('sohead_update', 'AFTER UPDATE', 'sohead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF sohead TRIGGER ====================================================================================================================================

    //sostock TRIGGER ================================================================================================================== 
    $qry = "create TRIGGER sostock_update_before BEFORE UPDATE on sostock FOR EACH ROW
        BEGIN

         if New.QA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SO;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['isqty' => []],
      'amount' => ['isamt' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
      'Actual Weight' => ['weight2' => []],
    ];
    $this->settriggerlogs('sostock_update_after', 'AFTER UPDATE', 'sostock', 'transnum_log', $fields, 'trno', 'STOCK');

    $qry = "create TRIGGER sostock_delete BEFORE DELETE on sostock FOR EACH ROW
        BEGIN
         
           if OLD.QA<>0 then
             CALL QTY_SERVED_CANNOT_DELETE;
           end if;
         END";
    $this->coreFunctions->execqry($qry, 'trigger');


    $qry = "create TRIGGER sostock_before_insert BEFORE INSERT on sostock FOR EACH ROW
        BEGIN
          DECLARE exist INT;
          SELECT COUNT(trno) INTO exist FROM sohead WHERE trno=NEW.trno; 
          IF exist = 0 THEN
            CALL CANNOT_ADD_ITEM_NO_HEAD_REFERENCE;
          END IF; 
        END;";
    $this->coreFunctions->execqry($qry, 'trigger');

    //END OF sostock TRIGGER ====================================================================================================================================
  }

  private function eschange_triggers()
  {
    //SOHEAD TRIGGER ===================================================================================================================
    $fields = [
      'date' => ['dateid' => []],
      'employee' => ['empid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'effectivity date' => ['effdate' => []],
    ];
    $this->settriggerlogs('eschange_update', 'AFTER UPDATE', 'eschange', 'hrisnum_log', $fields, 'trno', 'HEAD');

    // $docno        = $qryResult[0]->docno;
    // $description  = $qryResult[0]->description;
    // $ttype        = $qryResult[0]->ttype;
    // $tlevel       = $qryResult[0]->tlevel;
    // $tjobcode     = $qryResult[0]->tjobcode;
    // $tempstatcode = $qryResult[0]->tempstatcode;
    // $trank        = $qryResult[0]->trank;
    // $tjobgrade    = $qryResult[0]->tjobgrade;
    // $tlocation    = $qryResult[0]->tlocation;
    // $tpaymode     = $qryResult[0]->tpaymode;
    // $tpaygroup    = $qryResult[0]->tpaygroup;
    // $tpayrate     = $qryResult[0]->tpayrate;
    // $resigned     = $qryResult[0]->resigned;
    // $tbasicrate   = $qryResult[0]->tbasicrate;
    // $tcola   = $qryResult[0]->tcola;
    // $tallowrate   = $qryResult[0]->tallowrate;
    // $isactive     = $qryResult[0]->isactive;
    // $dateid       = date('Y-m-d', strtotime($qryResult[0]->dateid));
    // $effdate      = date('Y-m-d', strtotime($qryResult[0]->effdate));
    // $constart     = date('Y-m-d', strtotime($qryResult[0]->constart));
    // $conend       = date('Y-m-d', strtotime($qryResult[0]->conend));
    // $resigned     = date('Y-m-d', strtotime($qryResult[0]->resigned));
    // $empstart     = date('Y-m-d', strtotime($qryResult[0]->empstart));
    // $toprojectid    = $qryResult[0]->toprojectid;
    // $totrucknameid    = $qryResult[0]->totrucknameid;

  }

  private function ro_triggers()
  {
    //SOHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'driver code' => ['client' => []],
      'driver name' => ['clientname' => []],
      'agent' => ['agent' => []],
      'remarks' => ['rem' => []],
      'date' => ['dateid' => []],
    ];
    $this->settriggerlogs('rohead_update', 'AFTER UPDATE', 'rohead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function gp_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'date' => ['dateid' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'remarks' => ['rem' => []],
    ];
    $this->settriggerlogs('gphead_update', 'AFTER UPDATE', 'gphead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      // 'total' => ['ext' => []],
      // 'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'qty' => ['isqty' => []],
      'amount' => ['isamt' => []],
      'notes' => ['rem' => []],
      'serialno' => ['serialno' => []],
    ];
    $this->settriggerlogs('gpstock_update_after', 'AFTER UPDATE', 'gpstock', 'transnum_log', $fields, 'trno', 'STOCK');
  }

  private function pw_triggers()
  {
    //PWHEAD TRIGGER ===================================================================================================================
    $fields = [
      'Notes' => ['rem' => []],
      'Date' => ['dateid' => []],
      'Category' => ['pwrcat' => [true, 'name', 'powercat', 'pwrcat']],
    ];
    $this->settriggerlogs('pwhead_update', 'AFTER UPDATE', 'pwhead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF sohead TRIGGER ====================================================================================================================================

    //pwstock TRIGGER ================================================================================================================== 
    $fields = [
      'Current Reading (m)' => ['isqty3' => []],
      'Consumed (m)' => ['isqty' => []],
      'Rate' => ['isamt' => []],
    ];
    $this->settriggerlogs('pwstock_update_after', 'AFTER UPDATE', 'pwstock', 'transnum_log', $fields, 'trno', 'STOCK');
    //END OF pwstock TRIGGER ====================================================================================================================================
  }

  private function hpw_triggers()
  {
    //PWHEAD TRIGGER ===================================================================================================================
    $fields = [
      'Notes' => ['rem' => []],
      'Date' => ['dateid' => []],
      'Category' => ['pwrcat' => [true, 'name', 'powercat', 'pwrcat']],
    ];
    $this->settriggerlogs('hpwhead_update', 'AFTER UPDATE', 'hpwhead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF sohead TRIGGER ====================================================================================================================================

    //pwstock TRIGGER ================================================================================================================== 
    $fields = [
      'Current Reading (m)' => ['isqty3' => []],
      'Consumed (m)' => ['isqty' => []],
      'Rate' => ['isamt' => []],
    ];
    $this->settriggerlogs('hpwstock_update_after', 'AFTER UPDATE', 'hpwstock', 'transnum_log', $fields, 'trno', 'STOCK');
    //END OF pwstock TRIGGER ====================================================================================================================================
  }

  private function pc_triggers()
  {
    //PCHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'agent' => ['agent' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
    ];
    $this->settriggerlogs('pchead_update', 'AFTER UPDATE', 'pchead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF PCHEAD TRIGGER ====================================================================================================================================

    //PCSTOCK TRIGGER ==================================================================================================================
    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'amount' => ['rrcost' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('pcstock_update', 'AFTER UPDATE', 'pcstock', 'transnum_log', $fields, 'trno', 'STOCK');
    //END OF PCSTOCK TRIGGER ====================================================================================================================================
  }

  private function at_triggers()
  {
    //ATHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'agent' => ['agent' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
    ];
    $this->settriggerlogs('athead_update', 'AFTER UPDATE', 'athead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF ATHEAD TRIGGER ====================================================================================================================================

    //ATSTOCK TRIGGER ==================================================================================================================
    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'amount' => ['rrcost' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('atstock_update', 'AFTER UPDATE', 'atstock', 'transnum_log', $fields, 'trno', 'STOCK');
    //END OF PCSTOCK TRIGGER ====================================================================================================================================
  }

  private function kr_triggers()
  {
    //KRHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'agent' => ['agent' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
    ];
    $this->settriggerlogs('krhead_update', 'AFTER UPDATE', 'krhead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF KRHEAD TRIGGER ====================================================================================================================================    
  }

  private function tr_triggers()
  {
    //TRHEAD TRIGGER ================================================================================================================
    $fields = [
      'department' => ['client' => []],
      'department name' => ['clientname' => []],
      'date' => ['dateid' => []],
      'warehouse' => ['wh' => []],
      'notes' => ['rem' => []],
      'yourref' => ['yourref' => []],
      'ourref' => ['ourref' => []]
    ];

    $this->settriggerlogs('trhead_update', 'AFTER UPDATE', 'trhead', 'transnum_log', $fields, 'trno', 'HEAD');
    // END TRHEAD TRIGGER ===========================================================================================================

    // TRSTOCK TRIGGER ===============================================================================================================
    $fields = [
      'request qty' => ['reqqty' => []],
      'warehouse' => ['whid' => [TRUE, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [TRUE, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [TRUE, 'itemname', 'item', 'itemid']],
      'notes' => ['rem' => []]
    ];

    $this->settriggerlogs('trstock_update_after', 'AFTER UPDATE', 'trstock', 'transnum_log', $fields, 'trno', 'STOCK');
    //END TRSTOCK TRIGGER =============================================================================================================
  }

  // private function terms_triggers()
  // {
  //   //TERMS TRIGGER ====================================================================================================================
  //   $fields = [
  //     'terms' => ['terms' => []],
  //     'days name' => ['days' => []],
  //     'with dp' => ['isdp' => []],
  //     'Not allowed' => ['isnotallow' => []]
  //   ];

  //   $this->settriggerlogs('term_update', 'AFTER UPDATE', 'terms', 'terms_log', $fields, 'line', 'TERMS');
  //   //END OF terms TRIGGER ====================================================================================================================================
  // }

  private function users_triggers()
  {
    //useraccess TRIGGER ===============================================================================================================
    $fields = [
      'username' => ['username' => []],
      'name' => ['name' => []],
      'change password' => ['pwd' => []],
      'name' => ['name' => []],
      'picture' => ['pic' => []],
    ];

    $this->settriggerlogs('useraccess_update', 'AFTER UPDATE', 'useraccess', 'useraccess_log', $fields, 'accessid', 'USERACCESS');
    //END OF useraccess TRIGGER ====================================================================================================================================

    //USERS TRIGGER ====================================================================================================================
    $fields = [
      'user levels' => ['username' => []],
      'class' => ['class' => []],
    ];

    $this->settriggerlogs('users_update', 'AFTER UPDATE', 'users', 'userlog', $fields, 'idno', 'USERS');

    //  if OLD.attributes<>NEW.attributes then
    //   insert into userlog(trno,field,oldversion,userid,dateid) values(OLD.idno,'USERS',concat('Attributes - ',OLD.attributes,'=>',NEW.attributes),New.editby,New.editdate);
    // end if;    
    //END OF users TRIGGER ====================================================================================================================================
  }

  private function jo_triggers()
  {
    //JOHEAD TRIGGER ==================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'voiddate' => ['voiddate' => []],
      'branch' => ['branch' => []],
      'agent' => ['agent' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'date' => ['dateid' => []],
      'due' => ['due' => []],
      'print time' => ['printtime' => []],
      'lockuser' => ['lockuser' => []],
      'lockdate' => ['lockdate' => []],
      'open by' => ['openby' => []],
      'users' => ['users' => []],
      'create date' => ['createdate' => []],
      'create by' => ['createby' => []],
      'edit by' => ['editby' => []],
      'edit date' => ['editdate' => []],
      'view by' => ['viewby' => []],
      'view date' => ['viewdate' => []],
      'work location' => ['workloc' => []],
      'work description' => ['workdesc' => []],
      'notes' => ['rem' => []],
      'WH name' => ['wh' => [true, 'concat(client,"~",clientname)', 'client', 'client']],
      'yourref' => ['jrtrno' => [true, 'docno', 'hprhead', 'trno']],
      'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
      'sub project' => ['subproject' => [true, 'subproject', 'subproject', 'line']],
      'Shipping Address' => ['shipid' => [true, 'addr', 'billingaddr', 'line']],
      'Billing Address' => ['billid' => [true, 'addr', 'billingaddr', 'line']],
      'Shipping Contact Person' => ['shipcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      'Billing Contact Person' => ['billcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
    ];

    $this->settriggerlogs('johead_update', 'AFTER UPDATE', 'johead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF JOHEAD TRIGGER ====================================================================================================================================    

    //JOSERVICE TRIGGER ================================================================================================================
    $fields = [
      'sreceived by' => ['sreceivedby' => []],
      'sreceived date' => ['sreceiveddate' => []],
      'problem' => ['problem' => []],
      'accessories' => ['accessories' => []],
      'contact' => ['contact' => []],
      'fax' => ['fax' => []],
      'email' => ['email' => []],
      'hreceived by' => ['hreceivedby' => []],
      'hreceived date' => ['hreceiveddate' => []],
      'hremarks' => ['hremarks' => []],
      'sureceived by' => ['sureceivedby' => []],
      'sureceived date' => ['sureceiveddate' => []],
      'suremarks' => ['suremarks' => []],
      'retdatesutoho' => ['retdatesutoho' => []],
      'retremarks' => ['retremarks' => []],
      'recfromsutoho' => ['recfromsutoho' => []],
      'fromhtosdate' => ['fromhtosdate' => []],
      'recfromhtosby' => ['recfromhtosby' => []],
      'fromstocdate' => ['fromstocdate' => []],
      'sissuedby' => ['sissuedby' => []],
      'postdate' => ['postdate' => []],
      'deltoho' => ['deltoho' => []],
      'rettostore' => ['rettostore' => []],
      'issuedtostoreby' => ['issuedtostoreby' => []],
    ];

    $this->settriggerlogs('joservice_update', 'AFTER UPDATE', 'joservice', 'transnum_log', $fields, 'trno', 'HEAD');

    $qry = "create TRIGGER hjostock_update BEFORE UPDATE on hjostock FOR EACH ROW
        BEGIN

          if New.qa>New.qty then
            CALL QTY_IS_GREATER_THAN_PO;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $qry = "create TRIGGER jostock_update_before BEFORE UPDATE on jostock FOR EACH ROW
        BEGIN

         if New.QA>New.QTY then
            CALL QTY_IS_GREATER_THAN_PO;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => []],
      'Product ID' => ['itemid' => []],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'amount' => ['rrcost' => []],
      'uom' => ['uom' => []],
      'ext' => ['ext' => []],
      'notes' => ['rem' => []],
      'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
      'WH name' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
    ];
    $this->settriggerlogs('jostock_update_after', 'AFTER UPDATE', 'jostock', 'transnum_log', $fields, 'trno', 'STOCK');

    $qry = "create TRIGGER jostock_delete BEFORE DELETE on jostock FOR EACH ROW
        BEGIN

          if OLD.QA<>0 then
            CALL QTY_SERVED_CANNOT_DELETE_POSTOCK;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
  }

  private function jc_triggers()
  {
    //JOHEAD TRIGGER ==================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'voiddate' => ['voiddate' => []],
      'branch' => ['branch' => []],
      'agent' => ['agent' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'date' => ['dateid' => []],
      'due' => ['due' => []],
      'terms' => ['terms' => []],
      'print time' => ['printtime' => []],
      'work location' => ['workloc' => []],
      'work description' => ['workdesc' => []],
      'retention' => ['retention' => []],
      'notes' => ['rem' => []],
      'AP account' => ['contra' => [true, 'acnoname', 'coa', 'acno']],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']],
      'WH name' => ['wh' => [true, 'concat(client,"~",clientname)', 'client', 'client']],
      'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
      'sub project' => ['subproject' => [true, 'subproject', 'subproject', 'line']],
    ];

    $this->settriggerlogs('jchead_update', 'AFTER UPDATE', 'jchead', 'table_log', $fields, 'trno', 'HEAD');
    //END OF JOHEAD TRIGGER ====================================================================================================================================    

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => []],
      'Product ID' => ['itemid' => []],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'amount' => ['rrcost' => []],
      'uom' => ['uom' => []],
      'ext' => ['ext' => []],
      'notes' => ['rem' => []],
      'void' => ['void' => []],
      'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
      'WH name' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
    ];
    $this->settriggerlogs('jcstock_update_after', 'AFTER UPDATE', 'jcstock', 'table_log', $fields, 'trno', 'STOCK');
  }

  private function srhead_trigger()
  {
    // SERVICE RECEIVING
    $trigger_name = "srhead_update";
    $fields = [
      'document #' => ['docno' => []],
      'client code' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'address' => ['address' => []],
      'ship to' => ['shipto' => []],
      'warehouse' => ['wh' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'due' => ['due' => []],
      'branch' => ['branch' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'dept' => ['deptid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'Shipping Address' => ['shipid' => [true, 'addr', 'billingaddr', 'line']],
      'Billing Address' => ['billid' => [true, 'addr', 'billingaddr', 'line']],
      'Shipping Contact Person' => ['shipcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      'Billing Contact Person' => ['billcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'srhead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function srstock_trigger()
  {
    // SERVICE RECEIVING
    $trigger_name = "srstock_update";

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'uom' => ['uom' => []],
      'amount' => ['rrcost' => []],
      'amount' => ['isamt' => []],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'qty' => ['isqty' => []],
      'location' => ['loc' => []],
      'expiry date' => ['expiry' => []],
      'note' => ['rem' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'srstock', 'transnum_log', $fields, 'trno', 'STOCK');
  }

  private function en_instructor_trigger()
  {
    $fields = [
      'Line' => ['line' => []],
      'Department' => ['department' => []],
      'Telephone' => ['telno' => []],
      'Call Name' => ['callname' => []],
      'Dean Name' => ['deanname' => []],
      'Rank' => ['rank' => []],
      'Levels' => ['levels' => []]
    ];

    $this->settriggerlogs('en_instructor_update', 'AFTER UPDATE', 'en_instructor', 'client_log', $fields, 'instructorid', 'UPDATE');
  }

  private function ec_triggers()
  {
    // CURRICULUM
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Level' => ['levelid' => [true, "levels", "en_levels", "line"]],
      'Curriculum Code' => ['curriculumcode' => []],
      'Curriculum Name' => ['curriculumname' => []],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
      'Start Date' => ['effectfromdate' => []],
      'End Date' => ['effecttodate' => []],
      'Date' => ['dateid' => []],
      'Is Chinese' => ['ischinese' => []]
    ];
    $this->settriggerlogs('echead_update', 'AFTER UPDATE', 'en_cchead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Trno' => ['trno' => []],
      'Line' => ['line' => []],
      'Year' => ['year' => []],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Levelup' => ['levelup' => []]
    ];
    $this->settriggerlogs('ecyear_update_after', 'AFTER UPDATE', 'en_ccyear', 'transnum_log', $fields, 'trno', 'YEAR');
  }

  private function et_triggers()
  {
    // ASSESSMENT SETUP //ATTENDANCE ENTRY
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Date' => ['dateid' => []],
      'Period' => ['periodid' => [true, "concat(code, '-', name)", "en_period", "line"]],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
      'Adviser' => ['adviserid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Schedule' => ['scheddocno' => []],
      'Subject' => ['subjectid' => [true, "concat(subjectcode, '-', subjectname)", "en_subject", "trno"]],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Section' => ['sectionid' => [true, "section", "en_section", "line"]],
      'Curriculum' => ['curriculumcode' => []],
      'Room' => ['roomid' => [true, "concat(roomcode, '-', roomname)", "en_rooms", "line"]],
      'Sched Days' => ['schedday' => []],
      'Sched Time' => ['schedtime' => []],
      'Is Chinese' => ['ischinese' => []],
      'Grade/Year' => ['yr' => []]
    ];
    $this->settriggerlogs('ethead_update', 'AFTER UPDATE', 'en_athead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Trno' => ['trno' => []],
      'Level' => ['levelid' => [true, "levels", "en_levels", "line"]],
      'Department' => ['departid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Year/Grade' => ['yr' => []],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Section' => ['section' => []],
      'Subject' => ['subjectid' => [true, "concat(subjectcode, '-', subjectname)", "en_subject", "trno"]],
      'Gender' => ['sex' => []],
      'Fees' => ['feesid' => [true, "concat(feescode, '-', feesdesc)", "en_fees", "line"]],
      'Scheme' => ['schemeid' => [true, "scheme", "en_scheme", "line"]],
      'Rate' => ['rate' => []],
      'Is New' => ['isnew' => []],
      'Is Foreign' => ['isforeign' => []],
      'Is Add/Drop' => ['isadddrop' => []],
      'Is Cross' => ['iscrossenrollee' => []],
      'Is Late' => ['islateenrollee' => []],
      'Is Transfer' => ['istransferee' => []]
    ];

    $this->settriggerlogs('etfees_update_after', 'AFTER UPDATE', 'en_atfees', 'transnum_log', $fields, 'trno', 'DETAILS');
  }

  private function es_triggers()
  {
    // SCHEDULE
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Adviser' => ['adviserid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Curriculum Docno' => ['curriculumdocno' => []],
      'Grade/Year' => ['yr' => []],
      'Date' => ['dateid' => []],
      'Curriculum Code' => ['curriculumcode' => []],
      'Curriculum Name' => ['curriculumname' => []],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Period' => ['periodid' => [true, "concat(code, '-', name)", "en_period", "line"]],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
      'Section' => ['sectionid' => [true, "section", "en_section", "line"]],
      'Notes' => ['rem' => []],
      'Is Chinese' => ['ischinese' => []]
    ];

    $this->settriggerlogs('eshead_update', 'AFTER UPDATE', 'en_schead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Trno' => ['trno' => []],
      'Subject' => ['subjectid' => [true, "concat(subjectcode, '-', subjectname)", "en_subject", "trno"]],
      'Units' => ['units' => []],
      'Laboratory' => ['laboratory' => []],
      'Lecture' => ['lecture' => []],
      'Hours' => ['hours' => []],
      'Instructor' => ['instructorid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Building' => ['bldgid' => [true, "concat(bldgcode, '-', bldgname)", "en_bldg", "line"]],
      'Room' => ['roomid' => [true, "concat(roomcode, '-', roomname)", "en_rooms", "line"]],
      'Schedule' => ['schedday' => []],
      'Start Time' => ['schedstarttime' => []],
      'End Time' => ['schedendtime' => []],
      'Minimum Slot' => ['minslot' => []],
      'Maximum Slot' => ['maxslot' => []]
    ];
    $this->settriggerlogs('essubject_update_after', 'AFTER UPDATE', 'en_scsubject', 'transnum_log', $fields, 'trno', 'DETAILS');
  }

  private function ei_triggers()
  {
    // GRADE SCHOOL ASSESSMENT
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Student ID' => ['client' => []],
      'Student Name' => ['clientname' => []],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Curriculum' => ['curriculumtrno' => [true, "concat(docno, '-', curriculumcode, '-', curriculumname)", "en_glhead", "trno"]],
      'Grade/Year' => ['yr' => []],
      'Date' => ['dateid' => []],
      'Department' => ['deptid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Period' => ['periodid' => [true, "concat(code, '-', name)", "en_period", "line"]],
      'Level' => ['levelid' => [true, "levels", "en_levels", "line"]],
      'MOP' => ['modeofpayment' => []],
      'Account' => ['contra' => []],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
      'Section' => ['sectionid' => [true, "section", "en_section", "line"]],
      'Notes' => ['rem' => []],
      'Discount' => ['disc' => []],
      'Is Chinese' => ['ischinese' => []]
    ];
    $this->settriggerlogs('eihead_update', 'AFTER UPDATE', 'en_sohead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Trno' => ['trno' => []],
      'Subject' => ['subjectid' => [true, "subjectcode", "en_subject", "trno"]],
      'Units' => ['units' => []],
      'Lecture' => ['lecture' => []],
      'Laboratory' => ['laboratory' => []],
      'Hours' => ['hours' => []],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Instructor' => ['instructorid' => [true, "clientname", "client", "clientid"]],
      'Building' => ['bldgid' => [true, "bldgcode", "en_bldg", "line"]],
      'Schedule' => ['schedday' => []],
      'Start Time' => ['schedstarttime' => []],
      'End Time' => ['schedendtime' => []]
    ];

    $this->settriggerlogs('eisubject_update_after', 'AFTER UPDATE', 'en_sosubject', 'transnum_log', $fields, 'trno', 'DETAILS');
  }

  private function er_triggers()
  {
    // STUDENT REGISTRATION
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Student ' => ['client' => [true, "concat(client, '-', clientname)", "client", "client"]],
      'Assessment' => ['assessref' => []],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Grade/Year' => ['yr' => []],
      'Section' => ['sectionid' => [true, "section", "en_section", "line"]],
      'Date' => ['dateid' => []],
      'Department' => ['deptid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Period' => ['periodid' => [true, "concat(code, '-', name)", "en_period", "line"]],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
      'Level' => ['levelid' => [true, "levels", "en_levels", "line"]],
      'MOP' => ['modeofpayment' => []],
      'Account' => ['contra' => []],
      'Notes' => ['rem' => []],
      'Discount' => ['disc' => []],
      'Is Chinese' => ['ischinese' => []]
    ];
    $this->settriggerlogs('erhead_update', 'AFTER UPDATE', 'en_sjhead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Trno' => ['trno' => []],
      'Subject' => ['subjectid' => [true, "concat(subjectcode, '-', subjectname)", "en_subject", "trno"]],
      'Units' => ['units' => []],
      'Lecture' => ['lecture' => []],
      'Laboratory' => ['laboratory' => []],
      'Hours' => ['hours' => []],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Instructor' => ['instructorid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Building' => ['bldgid' => [true, "concat(bldgcode, '-', bldgname)", "en_bldg", "line"]],
      'Room' => ['roomid' => [true, "concat(roomcode, '-', roomname)", "en_rooms", "line"]],
      'Schedule' => ['schedday' => []],
      'Start Time' => ['schedstarttime' => []],
      'End Time' => ['schedendtime' => []]
    ];
    $this->settriggerlogs('ersubject_update_after', 'AFTER UPDATE', 'en_sjsubject', 'transnum_log', $fields, 'trno', 'DETAILS');
  }

  private function ed_triggers()
  {
    // ADD/DROP
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Student ID' => ['client' => []],
      'Student Name' => ['clientname' => []],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Grade/Year' => ['yr' => []],
      'Section' => ['section' => []],
      'Schedule' => ['schedcode' => []],
      'Date' => ['dateid' => []],
      'Department' => ['deptid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Curriculum' => ['curriculumdocno' => [true, "concat(docno, '-', curriculumcode, '-', curriculumname)", "en_glhead", "docno"]],
      'Period' => ['periodid' => [true, "concat(code, '-', name)", "en_period", "line"]],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
      'Level' => ['levelid' => [true, "levels", "en_levels", "line"]],
      'MOP' => ['modeofpayment' => []],
      'Account' => ['contra' => []],
      'Notes' => ['rem' => []],
    ];
    $this->settriggerlogs('edhead_update', 'AFTER UPDATE', 'en_adhead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function eh_triggers()
  {
    // GRADE ENTRY
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Adviser' => ['adviserid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
      'Schedule' => ['scheddocno' => []],
      'Subject' => ['subjectid' => [true, "concat(subjectcode, '-', subjectname)", "en_subject", "trno"]],
      'Year/Grade' => ['yr' => []],
      'Date' => ['dateid' => []],
      'Period' => ['periodid' => [true, "concat(code, '-', name)", "en_period", "line"]],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Quarter' => ['quarterid' => [true, "concat(code, '-', name)", "en_quartersetup", "line"]],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Section' => ['sectionid' => [true, "section", "en_section", "line"]],
      'Curriculum Code' => ['curriculumcode' => []],
      'Building' => ['bldgid' => [true, "concat(bldgcode, '-', bldgname)", "en_bldg", "line"]],
      'Room' => ['roomid' => [true, "concat(roomcode, '-', roomname)", "en_rooms", "line"]],
      'Sched Days' => ['schedday' => []],
      'Sched Time' => ['schedtime' => []],
      'Is Chinese' => ['ischinese' => []]
    ];
    $this->settriggerlogs('ehhead_update', 'AFTER UPDATE', 'en_gehead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Trno' => ['trno' => []],
      'Code' => ['gccode' => []],
      'Sub Code' => ['gcsubcode' => []],
      'Remarks' => ['topic' => []],
      'Total Score' => ['noofitems' => []],
      'Quarter' => ['quarterid' => [true, "concat(code, '-', name)", "en_quartersetup", "line"]]
    ];
    $this->settriggerlogs('ehcomponent_update_after', 'AFTER UPDATE', 'en_gesubcomponent', 'transnum_log', $fields, 'trno', 'DETAILS');
  }

  private function eg_triggers()
  {
    // STUDENT GRADE ENTRY
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Student' => ['clientid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Date' => ['dateid' => []],
      'Level' => ['levelid' => [true, "levels", "en_levels", "line"]],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]]
    ];
    $this->settriggerlogs('eghead_update', 'AFTER UPDATE', 'en_sgshead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Trno' => ['trno' => []],
      'Subject' => ['subjectid' => [true, "concat(subjectcode, '-', subjectname)", "en_subject", "trno"]],
      'Units' => ['units' => []],
      'Lecture' => ['lecture' => []],
      'Laboratory' => ['laboratory' => []],
      'Year' => ['yearnum' => []],
      'Terms' => ['terms' => []],
      'Grade' => ['grade' => []],
      'Equivalent' => ['equivalent' => []]
    ];
    $this->settriggerlogs('egsubject_update_after', 'AFTER UPDATE', 'en_sgssubject', 'transnum_log', $fields, 'trno', 'DETAILS');
  }

  private function ej_triggers()
  {
    // REPORT CARD SETUP
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Date' => ['dateid' => []],
      'Level' => ['levelid' => [true, "levels", "en_levels", "line"]],
      'Is Chinese' => ['ischinese' => []]
    ];
    $this->settriggerlogs('ejhead_update', 'AFTER UPDATE', 'en_rchead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Trno' => ['trno' => []],
      'Code' => ['code' => []],
      'Title' => ['title' => []],
      'Year/Grade' => ['yr' => []],
      'Section' => ['sectionid' => [true, "section", "en_section", "line"]],
      'Times' => ['times' => []],
      'Order' => ['order' => []]
    ];
    $this->settriggerlogs('ejdetail_update_after', 'AFTER UPDATE', 'en_rcdetail', 'transnum_log', $fields, 'trno', 'DETAILS');
  }

  private function ek_triggers()
  {
    // STUDENT REPORT CARD
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Adviser' => ['adviserid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
      'Schedule' => ['schedtrno' => [true, "docno", "en_glhead", "trno"]],
      'Date' => ['dateid' => []],
      'Period' => ['periodid' => [true, "concat(code, '-', name)", "en_period", "line"]],
      'Course' => ['courseid' => [true, "concat(coursecode, '-', coursename)", "en_course", "line"]],
      'Year/Grade' => ['yr' => []],
      'Section' => ['sectionid' => [true, "section", "en_section", "line"]],
      'Level' => ['levelid' => [true, "levels", "en_levels", "line"]],
      'Notes' => ['rem' => []]
    ];
    $this->settriggerlogs('ekhead_update', 'AFTER UPDATE', 'en_srchead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function ca_triggers()
  {
    // CREATE TICKET
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Customer' => ['client' => [true, "concat(client, '-', clientname)", "client", "client"]],
      'Department' => ['dept' => [true, "concat(client, '-', clientname)", "client", "client"]],
      'Date' => ['dateid' => []],
      'Concern' => ['rem' => []],
      'Yourref' => ['yourref' => []],
      'Ourref' => ['ourref' => []],
      'SI Trno' => ['sitrno' => []]
    ];
    $this->settriggerlogs('cahead_update', 'AFTER UPDATE', 'csstickethead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function ht_triggers()
  {
    $fields = [
      'trno' => ['trno' => []],
      'empname' => ['empid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'notes' => ['notes' => []],
    ];
    $this->settriggerlogs('trainingdetail_update', 'AFTER UPDATE', 'trainingdetail', 'hrisnum_log', $fields, 'line', 'EMPLOYEE', '', 'trno');
  }

  private function ho_triggers()
  {
    $fields = [
      'trno' => ['trno' => []],
      'itemname' => ['itemname' => []],
      'amount' => ['amt' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('turnoveritemdetail_update', 'AFTER UPDATE', 'turnoveritemdetail', 'hrisnum_log', $fields, 'line', 'ITEM', '', 'trno');
  }

  private function hr_triggers()
  {
    $fields = [
      'trno' => ['trno' => []],
      'itemname' => ['itemname' => []],
      'amount' => ['amt' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('returnitemdetail_update', 'AFTER UPDATE', 'returnitemdetail', 'hrisnum_log', $fields, 'line', 'ITEM', '', 'trno');
  }

  private function dt_triggers()
  {
    // DOCUMENT ENTRY
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Vendor' => ['clientid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
      'Forex' => ['forex' => []],
      'Document Type' => ['doctypeid' => []],
      'Date' => ['dateid' => []],
      'Terms' => ['terms' => []],
      'Due' => ['due' => []],
      'Amount' => ['amt' => []],
      'Invoice Date' => ['invdate' => []],
      'Invoice No.' => ['invoiceno' => []],
      'Division' => ['divid' => []],
      'PO Reference' => ['poref' => []],
      'Title' => ['title' => []],
      'Cost Center' => ['costcenter' => [true, "concat(code, '-', name)", "projectmasterfile", "line"]],
      'Is Approved' => ['isapproved' => []]
    ];

    $this->settriggerlogs('dthead_update', 'AFTER UPDATE', 'dt_dthead', 'docunum_log', $fields, 'trno', 'HEAD');
  }


  private function rg_triggers()
  {
    $fields = [
      'Document #' => ['docno' => []],
      'Date' => ['dateid' => []],
      'Notes' => ['rem' => []]
    ];
    $this->settriggerlogs('rghead_update', 'AFTER UPDATE', 'rghead', 'transnum_log', $fields, 'trno', 'HEAD');
  }



  public function createtriggers($config)
  {
    $this->apledger_triggers();
    $this->arledger_triggers();
    $this->center_triggers();
    $this->bom_trigger();
    $this->cd_triggers($config);
    $this->client_triggers($config);
    $this->clientinfo_triggers($config);
    $this->employee_triggers();
    $this->costing_triggers();
    $this->en_triggers();
    $this->frontend_triggers();
    $this->glhead_triggers($config);
    $this->glstock_triggers();
    $this->hcnstock_triggers();
    $this->hsgstock_triggers();
    $this->htr_triggers();
    $this->item_triggers($config);
    $this->jo_triggers();
    $this->kr_triggers();
    $this->la_triggers($config);
    $this->mr_triggers();
    //$this->mi_triggers();
    $this->pc_triggers();
    $this->at_triggers();
    $this->pl_triggers();
    $this->pm_triggers();
    $this->po_triggers($config);
    $this->hpo_triggers($config);
    $this->pf_triggers();
    $this->ra_triggers();
    $this->pr_triggers($config);
    $this->hpr_triggers($config);
    $this->qs_triggers();
    $this->sr_triggers();
    $this->qt_triggers();
    $this->rrstatus_triggers();
    $this->sa_triggers();
    $this->sb_triggers();
    $this->sc_triggers();
    $this->so_triggers();
    //$this->terms_triggers();
    $this->tr_triggers();
    $this->users_triggers();
    $this->wa_triggers();
    $this->whdoc_triggers();
    $this->whnods_triggers();
    $this->whjobreq_triggers();
    $this->ld_triggers();
    $this->op_triggers();
    $this->opstock_trigger();
    $this->qshead_triggers();
    $this->qsstock_trigger();
    $this->sqcomments_trigger();
    $this->tehead_triggers();
    $this->pqhead_triggers();
    $this->pqdetail_trigger();
    $this->svhead_triggers();
    $this->svdetail_trigger();

    $this->vthead_trigger();
    $this->vtstock_trigger();
    $this->vshead_trigger();
    $this->vsstock_trigger();
    $this->sghead_trigger();

    $this->os_triggers();
    $this->srhead_trigger();
    $this->srstock_trigger();
    $this->trigger_masterfile->createtriggers_masterfile($config);
    $this->trigger_hris->createtriggers_hris($config);

    $this->en_instructor_trigger();
    $this->en_student_trigger();

    $this->ec_triggers();
    $this->et_triggers();
    $this->es_triggers();
    $this->ei_triggers();
    $this->er_triggers();
    $this->ed_triggers();
    $this->eh_triggers();
    $this->ef_triggers();
    $this->eg_triggers();
    $this->ej_triggers();
    $this->ek_triggers();
    $this->ca_triggers();
    $this->ht_triggers();
    $this->ho_triggers();
    $this->hr_triggers();
    $this->dt_triggers();
    $this->rf_triggers();

    $this->br_triggers();
    $this->bl_triggers();
    $this->jc_triggers();
    $this->ba_triggers();
    $this->headinfotrans_triggers();
    $this->stockinfo_triggers();
    $this->stockinfotrans_triggers();
    $this->hstockinfotrans_triggers($config);
    $this->hstockinfo_triggers($config);
    $this->detailinfo_triggers();

    $this->entryvrpassenger();
    $this->entryvritems();
    $this->iteminfo_triggers($config);

    $this->vr_triggers();

    $this->va_triggers();
    $this->gp_triggers();
    $this->sjdeliverystat_triggers();

    $this->entrycalllog();
    $this->emp_education_triggers();
    $this->emp_employment_triggers();
    $this->emp_contract_triggers();
    $this->entrypricebracket_tab_triggers();
    $this->oq_triggers();

    $this->ro_triggers();
    $this->rc_triggers();
    $this->af_triggers($config);
    $this->wn_triggers();
    $this->lp_triggers();
    $this->hparticulars_triggers($config);
    $this->serialin_triggers($config);

    $this->rg_triggers();
    $this->si_triggers();
    $this->px_triggers();
    // $this->pw_triggers();
    // $this->hpw_triggers();
    // ADD HERE-->


  }

  // FOR TESTING -- JIKS [11.24.2020]
  // SET TRIGGERS FOR HEAD LOGS
  // UPDATE ADD ON STOCK LOGS AND IF A HAVE A MASTERFILE
  // ADD PARAMS FOR CUSTOMIZE TRIGGERS

  public function settriggerlogs($triggername, $type, $tablename, $table_log, $data = [], $keys, $level = '', $customizetrigger = '', $keys2 = '')
  {

    $str = '';

    switch (strtoupper($level)) {
      case "STOCK":
        $line = "' Line:',OLD.line,' - ',ifnull(( select concat(itemname,'(',barcode,')',' - ') from item where itemid = OLD.itemid ),'')";
        break;
      case "DETAIL":
        $line = "ifnull(( select concat(acnoname,'(',acno,')',' - ') from coa where acnoid = OLD.acnoid ),'')";
        break;
      case "PRICELIST":
        $line = "ifnull((select concat(barcode,' - ') from item where itemid = OLD.itemid),'')";
        break;
      case "VSTOCK":
        $line = "ifnull(( select concat(clientname,'(',client,')',' - ') from client where clientid = OLD.clientid ),'')";
        break;
      case "HSTOCKINFO":
        $line = "ifnull(( select concat(itemname,'(',barcode,')',' - ') from glstock as info left join item on item.itemid=info.itemid where info.trno=OLD.trno and info.line=OLD.line ),'')";
        break;
      case "SERIALIN":
        $line = "ifnull(( select concat(' Line:',OLD.line,' - ',itemname,'(',barcode,')',' - ') from glstock as info left join item on item.itemid=info.itemid where info.trno=OLD.trno and info.line=OLD.line ),'')";
        break;
      case "PWSTOCK":
      case "HPWSTOCK":
        $line = "ifnull(( select name from subpowercat2 where line = OLD.subcat2 ),'')";
        break;
      default:
        $line = "''";
        break;
    }

    // if (strtoupper($level) == 'STOCK') {
    //   // $line = "ifnull(( select concat(itemname,'(',barcode,')',' - ') from item where itemid = OLD.itemid ),'')";
    // } else if (strtoupper($level) == 'DETAIL') {
    //   // $line = "ifnull(( select concat(acnoname,'(',acno,')',' - ') from coa where acnoid = OLD.acnoid ),'')";
    // } else if (strtoupper($level) == 'PRICELIST') {
    //   // $line = "ifnull((select concat(barcode,' - ') from item where itemid = OLD.itemid),'')";
    // } else if (strtoupper($level) == 'VSTOCK') {
    //   // $line = "ifnull(( select concat(clientname,'(',client,')',' - ') from client where clientid = OLD.clientid ),'')";
    // } else if (strtoupper($level) == 'HSTOCKINFO') {
    //   $line = "ifnull(( select concat(itemname,'(',barcode,')',' - ') from glstock as info left join item on item.itemid=info.itemid where info.trno=OLD.trno and info.line=OLD.line ),'')";
    // } else {
    //   // $line = "''";
    // }

    $newkey = $keys2 != "" ? $keys2 : $keys;

    if (is_array($data)) {
      foreach ($data as $key => $value) {
        foreach ($value as $col => $value2) {
          if (isset($value2[0]) && $value2[0] == true) {

            $str .= "
                if OLD.$col<>NEW.$col then
                insert into $table_log(trno,field,oldversion,userid,dateid) 
                values (OLD.$newkey,'$level',concat('" . strtoupper($key) . " - ',$line,
                ifnull((select " . $value2[1] . " from " . $value2[2] . " WHERE " . $value2[3] . " = OLD.$col),''), ' => ',
                ifnull((select " . $value2[1] . " FROM " . $value2[2] . " WHERE " . $value2[3] . " = NEW.$col),'')),
                New.editby,New.editdate);
                end if;
              ";
          } else {

            $str .= "
                if OLD.$col<>NEW.$col then
                insert into $table_log(trno,field,oldversion,userid,dateid) 
                values (OLD.$newkey,'$level',concat('" . strtoupper($key) . " - ',$line, 
                OLD.$col,' => ',NEW.$col),New.editby,New.editdate);
                end if;
              ";
          }
        }
      } // end foreach
    } // end if


    $qry = "create TRIGGER $triggername $type on $tablename FOR EACH ROW
            BEGIN
            " . $str . "
            " . $customizetrigger . "
            END";
    $this->coreFunctions->execqry($qry, 'trigger');
  }

  private function en_triggers()
  {
    //HSOSTOCK TRIGGER =================================================================================================================
    $qry = "create TRIGGER en_glsubject_update BEFORE UPDATE on en_glsubject FOR EACH ROW
        BEGIN
          if New.maxslot>0 then
             if New.ASQA>New.maxslot then
                CALL ASSESSMENT_REACH_MAXIMUM_STUDENT_SLOTS;
              end if;
              if New.QA>New.maxslot then
                CALL REGISTRATION_REACH_MAXIMUM_STUDENT_SLOTS;
              end if;
          END IF;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF EN_GLSUBJECT TRIGGER ====================================================================================================================================    


  }



  private function htr_triggers()
  {

    //HPOSTOCK TRIGGER =================================================================================================================
    $qry = "create TRIGGER htrstock_update BEFORE UPDATE on htrstock FOR EACH ROW
        BEGIN

          if New.qa>New.qty then
            CALL QTY_IS_GREATER_THAN_REQUEST;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF HTRSTOCK TRIGGER ====================================================================================================================================

    $qry = "create TRIGGER trstock_delete BEFORE DELETE on trstock FOR EACH ROW
        BEGIN

          if OLD.QA<>0 then
            CALL QTY_SERVED_CANNOT_DELETE_TRSTOCK;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF trstock TRIGGER ====================================================================================================================================    
  }

  private function pm_triggers()
  {
    //PMHEAD TRIGGER ===================================================================================================================
    $fields = [
      'Document #' => ['docno' => []],
      'Customer Code' => ['client' => []],
      'Customer Name' => ['clientname' => []],
      'Project Code' => ['projectid' => [true, 'code', 'projectmasterfile', 'line']],
      'Total Contract Price' => ['tcp' => []],
      'Estimated Cost' => ['cost' => []],
      'Closing Date' => ['closedate' => []],
      'Completed(%)' => ['completed' => []],
      'Retention(%)' => ['retention' => []],
      'Downpayment(%)' => ['dp' => []],
      'Notes' => ['rem' => []],
      'Date' => ['dateid' => []],
      'Due' => ['due' => []],
      'Warehouse' => ['wh' => []]
    ];
    $this->settriggerlogs('pmhead_update', 'AFTER UPDATE', 'pmhead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF sohead TRIGGER ====================================================================================================================================

    //subproject TRIGGER ================================================================================================================== 

    $fields = [
      'subproject' => ['subproject' => []],
      'projpercent' => ['projpercent' => []],
      'completed' => ['completed' => []],
    ];
    $this->settriggerlogs('subproject_update_after', 'AFTER UPDATE', 'subproject', 'transnum_log', $fields, 'line', 'SUBPROJECT', '', 'trno');

    $fields = [
      'line' => ['line' => []],
      'variation' => ['variation' => []],
      'amount' => ['amount' => []],
    ];
    $this->settriggerlogs('entryvaration_update', 'AFTER UPDATE', 'projectvar', 'transnum_log', $fields, 'trno', 'VARIATION');

    //END OF subproject TRIGGER ====================================================================================================================================

    $fields = [
      'Quantity' => ['rrqty' => []],
      'Estimate Quantity' => ['qty' => []],
      'Amount' => ['rrcost' => []],
      'Estimate Cost' => ['cost' => []],
      'Total' => ['ext' => []],
      'Unit Of Price' => ['uom' => []]
    ];

    $this->settriggerlogs('entryprojectsubactivity_update', 'AFTER UPDATE', 'psubactivity', 'transnum_log', $fields, 'substage', 'SUB ACTIVITY', '', 'trno');

    $fields = [
      'Activity' => ['line' => [true, "substage", "substages", "line"]],
    ];

    $this->settriggerlogs('entryprojectactivity_update', 'AFTER UPDATE', 'activity', 'transnum_log', $fields, 'line', 'ACTIVITY', '', 'trno');


    //stages TRIGGER ================================================================================================================== 

    $fields = [
      'cost' => ['cost' => []],
      'stage' => ['stage' => []],
      'projpercent' => ['projpercent' => []],
      'completed' => ['completed' => []],
      'ar' => ['ar' => []],
      'ap' => ['ap' => []],
      'paid' => ['paid' => []],
      'boq' => ['boq' => []],
      'po' => ['po' => []],
      'pr' => ['pr' => []],
      'rr' => ['rr' => []],
      'jo' => ['jo' => []],
      'jc' => ['jc' => []]
    ];
    $this->settriggerlogs('entryprojectstages_update', 'AFTER UPDATE', 'stages', 'transnum_log', $fields, 'line', 'STAGES', '', 'trno');

    // move to masterfile_log
    //END OF subproject TRIGGER ====================================================================================================================================
  }

  private function qt_triggers()
  {
    //HSOSTOCK TRIGGER =================================================================================================================
    $qry = "create TRIGGER hqtstock_update BEFORE UPDATE on hqtstock FOR EACH ROW
        BEGIN
         if New.QA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SO;
          end if;

         if Old.voidqty <> New.voidqty then
            if (New.voidqty + New.sjqa) >New.iss then
              CALL QTY_IS_GREATER_THAN_SO;
            end if;          
          end if;

        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF HQTSTOCK TRIGGER ====================================================================================================================================    

    //QTHEAD TRIGGER ===================================================================================================================
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'agent' => ['agent' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
      'project' => ['projectid' => []],
      'markup' => ['markup' => []],
      'tax' => ['tax' => []],
    ];
    $this->settriggerlogs('qthead_update', 'AFTER UPDATE', 'qthead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END OF qthead TRIGGER ====================================================================================================================================

    //qtstock TRIGGER ================================================================================================================== 
    $qry = "create TRIGGER qtstock_update_before BEFORE UPDATE on qtstock FOR EACH ROW
        BEGIN

         if New.QA>New.ISS then
            CALL QTY_IS_GREATER_THAN_SO;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['isqty' => []],
      'amount' => ['isamt' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('qtstock_update_after', 'AFTER UPDATE', 'qtstock', 'transnum_log', $fields, 'trno', 'STOCK');

    $qry = "create TRIGGER qtstock_delete BEFORE DELETE on qtstock FOR EACH ROW
        BEGIN
         
           if OLD.QA<>0 then
             CALL QTY_SERVED_CANNOT_DELETE;
           end if;
         END";
    $this->coreFunctions->execqry($qry, 'trigger');
    //END OF qtstock TRIGGER ====================================================================================================================================
  }

  private function whdoc_triggers()
  {
    //WHDOC TRIGGER ==================================================================================================================
    $fields = [
      'docno' => ['docno' => []],
      'issued' => ['issued' => []],
      'expiry' => ['expiry' => []],
      'dateid' => ['dateid' => []],
      'oic1' => ['oic1' => []],
      'oic2' => ['oic2' => []],
      'rem' => ['rem' => []],
      'status' => ['status' => []],
    ];

    $this->settriggerlogs('whdoc_update', 'AFTER UPDATE', 'whdoc', 'wh_log', $fields, 'whid', 'WH DOCUMENTS');
  }

  private function whnods_triggers()
  {
    //WHDOC TRIGGER ==================================================================================================================
    $fields = [
      'docno' => ['docno' => []],
      'issued' => ['issued' => []],
      'expiry' => ['expiry' => []],
      'dateid' => ['dateid' => []],
      'oic1' => ['oic1' => []],
      'oic2' => ['oic2' => []],
      'rem' => ['rem' => []],
      'status' => ['status' => []],
    ];

    $this->settriggerlogs('whnods_update', 'AFTER UPDATE', 'whnods', 'wh_log', $fields, 'whid', 'WH NODS');
  }

  private function whjobreq_triggers()
  {
    //WHDOC TRIGGER ==================================================================================================================
    $fields = [
      'docno' => ['docno' => []],
      'issued' => ['issued' => []],
      'expiry' => ['expiry' => []],
      'dateid' => ['dateid' => []],
      'oic1' => ['oic1' => []],
      'oic2' => ['oic2' => []],
      'rem' => ['rem' => []],
      'status' => ['status' => []],
    ];

    $this->settriggerlogs('whjobreq_update', 'AFTER UPDATE', 'whjobreq', 'wh_log', $fields, 'whid', 'WH JOB REQUESTS');
  }

  private function ld_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'tel' => ['tel' => []],
      'contact' => ['contact' => []],
      'date' => ['dateid' => []],
    ];
    $this->settriggerlogs('lead_update', 'AFTER UPDATE', 'lead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function op_triggers()
  {
    // SALES ACTIVITY
    $trigger_name = "ophead_update";

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'ship to' => ['shipto' => []],
      'telephone' => ['tel' => []],
      'date' => ['dateid' => []],
      'due' => ['due' => []],
      'warehouse' => ['wh' => []],
      'terms' => ['terms' => []],
      'remarks' => ['rem' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'forex' => ['forex' => []],
      'currency' => ['cur' => []],
      'agent' => ['agent' => []],
      'compname' => ['compname' => []],
      'designation' => ['designation' => []],
      'contactname' => ['contactname' => []],
      'contactno' => ['contactno' => []],
      'email' => ['email' => []],
      'source' => ['source' => []],
    ];
    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'ophead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function opstock_trigger()
  {
    // SALES ACTIVITY
    $trigger_name = "opstock_update";

    $fields = [
      'total' => ['ext' => []],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'uom' => ['uom' => []],
      'amount' => ['rrcost' => []],
      'amount' => ['isamt' => []],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'qty' => ['isqty' => []],
      'ref' => ['ref' => []],
      'void' => ['void' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'opstock', 'transnum_log', $fields, 'trno', 'STOCK');
  }

  private function qshead_triggers()
  {
    // QUOTATION
    $trigger_name = "qshead_update";

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'industry' => ['industry' => []],
      'date' => ['dateid' => []],
      'po date' => ['due' => []],
      'delivery date' => ['deldate' => []],
      'sales person' => ['agent' => [true, 'concat(client,"~",clientname)', 'client', 'client']],
      'position' => ['position' => []],
      'branch' => ['branch' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'department' => ['deptid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'po number' => ['yourref' => []],
      'currency' => ['cur' => []],
      'forex' => ['forex' => []],
      'probability' => ['probability' => []],
      'Shipping Address' => ['shipid' => [true, 'addr', 'billingaddr', 'line']],
      'Billing Address' => ['billid' => [true, 'addr', 'billingaddr', 'line']],
      'Shipping Contact Person' => ['shipcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      'Billing Contact Person' => ['billcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      'probability' => ['probability' => []],
      'vattype' => ['vattype' => []],
      'tax' => ['tax' => []],
      'terms' => ['terms' => []],
    ];
    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'qshead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function qsstock_trigger()
  {
    // QUOTATION
    $trigger_name = "qsstock_update";

    $fields = [
      'total' => ['ext' => []],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'uom' => ['uom' => []],
      'amount' => ['rrcost' => []],
      'amount' => ['isamt' => []],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'qty' => ['isqty' => []],
      'noprint' => ['noprint' => []],
      'projectid' => ['projectid' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'qsstock', 'transnum_log', $fields, 'trno', 'STOCK');
  }

  private function sqcomments_trigger()
  {
    // QUOTATION
    $trigger_name = "sqcomments_update";

    $fields = [
      'comment' => ['comment' => []],
      'username' => ['userid' => [true, 'username', 'useraccess', 'userid']],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'sqcomments', 'transnum_log', $fields, 'trno', 'COMMENTS');
  }

  private function tehead_triggers()
  {
    // TASK/ERRAND MODULE
    $trigger_name = "tehead_update";

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['clientid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'date' => ['dateid' => []],
      'errand type' => ['errandtype' => []],
      'ppio' => ['trno' => [true, "docno", "ppio_series", "trno"]],
      'date request' => ['datereq' => []],
      'date needed' => ['dateneed' => []],
      'date due' => ['due' => []],
      'assign to' => ['assignid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'company' => ['company' => []],
      'company address' => ['companyaddress' => []],
      'contact' => ['contact' => []],
      'contact person' => ['contactperson' => []],
    ];
    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'tehead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function pqhead_triggers()
  {
    // PETTY CASH REQUEST
    $trigger_name = "pqhead_update";

    $fields = [
      'document #' => ['docno' => []],
      'client code' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'date' => ['dateid' => []],
      'contra account' => ['contra' => []],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'remarks' => ['rem' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'pqhead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function pqdetail_trigger()
  {
    // PETTY CASH REQUEST
    $trigger_name = "pqdetail_update";

    $fields = [
      'amt' => ['amt' => []],
      'date' => ['postdate' => []],
      'rem' => ['rem' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'pqdetail', 'transnum_log', $fields, 'trno', 'DETAIL');
  }

  private function svhead_triggers()
  {
    // PETTY CASH VOUCHER
    $trigger_name = "svhead_update";

    $fields = [
      'document #' => ['docno' => []],
      'client code' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'contra account' => ['contra' => []],
      'date' => ['dateid' => []],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']],
      'ewtrate' => ['ewt' => [true, 'concat(code,"~",rate)', 'ewtlist', 'code']],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'vattype' => ['vattype' => []],
      'amount released' => ['amt' => []],
      'remarks' => ['rem' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'svhead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function svdetail_trigger()
  {
    // PETTY CASH VOUCHER
    $trigger_name = "svdetail_update";

    $fields = [
      'isvat' => ['isvat' => []],
      'isewt' => ['isewt' => []],
      'isvewt' => ['isvewt' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'svdetail', 'transnum_log', $fields, 'trno', 'DETAIL');
  }

  private function vthead_trigger()
  {
    // VOID SALES ORDER
    $trigger_name = "vthead_update";

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'date' => ['dateid' => []],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']]
    ];
    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'vthead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function vtstock_trigger()
  {
    // VOID SALES ORDER
    $trigger_name = "vtstock_update";

    $fields = [
      // 'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'qty' => ['isqty' => []],
      // 'qty' => ['iss' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'vtstock', 'transnum_log', $fields, 'trno', 'STOCK');
  }

  private function vshead_trigger()
  {
    // VOID SERVICE SALES ORDER
    $trigger_name = "vshead_update";

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'date' => ['dateid' => []],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']]
    ];
    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'vshead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function vsstock_trigger()
  {
    // VOID SERVICE SALES ORDER
    $trigger_name = "vstock_update";

    $fields = [
      // 'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'qty' => ['isqty' => []],
      // 'qty' => ['iss' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'vsstock', 'transnum_log', $fields, 'trno', 'STOCK');
  }

  private function sghead_trigger()
  {
    // MITSUKOSHI - SPECIAL PARTS REQUEST
    $trigger_name = "sghead_update";

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'wh' => ['wh' => [true, 'concat(client,"~",clientname)', 'client', 'client']],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'agent' => ['agent' => []],
      'notes' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
      'deliverytype' => ['deliverytype' => [true, "name", "deliverytype", "line"]],
      'part request type' => ['partreqtypeid' => [true, "name", "partrequest", "line"]],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', 'sghead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function en_student_trigger()
  {
    $fields = [
      'Client ID' => ['clientid' => []],
      'Client' => ['client' => []],
      'First Name' => ['fname' => []],
      'Last Name' => ['lname' => []],
      'Middle Name' => ['mname' => []],
      'Student ID' => ['studentid' => []],
      'Course' => ['course' => []],
      'Course Name' => ['coursename' => []],
      'Major' => ['major' => []],
      'Major Name' => ['majorname' => []],
      'Is New' => ['isnew' => []],
      'Is Old' => ['isold' => []],
      'Is Cross Enrollee' => ['iscrossenrollee' => []],
      'Is Foreign' => ['isforeign' => []],
      'is Add Drop' => ['isadddrop' => []],
      'Is Late Enrollee' => ['islateenrollee' => []],
      'is Transferee' => ['istransferee' => []],
      'Curriculum Code' => ['curriculumcode' => []],
      'Curriculum Docno' => ['curriculumdocno' => []],
      'Is Dept' => ['isdept' => []],
      'Branch' => ['branch' => []],
      'Gender' => ['gender' => []],
      'Civil Status' => ['civilstatus' => []],
      'Birth Place' => ['bplace' => []],
      'Student Type' => ['studenttype' => []],
      'City' => ['city' => []],
      'Relation' => ['relation' => []],
      'G address' => ['gaddr' => []],
      'Batch' => ['batch' => []],
      'Nationality' => ['nationality' => []],
      'Is Regular' => ['isregular' => []],
      'Is Irregular' => ['isirregular' => []],
      'Is Extra Mural' => ['isextramural' => []],
      'Extra Mural' => ['extramural' => []],
      'H Addr' => ['haddr' => []],
      'B addr' => ['baddr' => []],
      'Guardian' => ['guardian' => []],
      'H tel' => ['htel' => []],
      'B tel' => ['btel' => []],
      'G tel' => ['gtel' => []],
      'Elementary' => ['elementary' => []],
      'High School' => ['highschool' => []],
      'College' => ['college' => []],
      'Post School' => ['postschool' => []],
      'E Year' => ['eyear' => []],
      'H Year' => ['hyear' => []],
      'C Year' => ['cyear' => []],
      'P Year' => ['pyear' => []],
      'Company' => ['company' => []],
      'Curriculum Trno' => ['curriculumtrno' => []],
      'Course ID' => ['courseid' => []],
      'Section ID' => ['sectionid' => []],
      'YR' => ['yr' => []],
      'Level Up' => ['levelup' => []],
      'Sched Trno' => ['schedtrno' => []],
      'Reg Trno' => ['regtrno' => []],
      'Assess Trno' => ['assesstrno' => []],
      'Chinese Name' => ['chinesename' => []],
      'Chinese Course ID' => ['chinesecourseid' => []],
      'Chinese Level Up' => ['chineselevelup' => []],

    ];

    $this->settriggerlogs('en_student_update', 'AFTER UPDATE', 'en_studentinfo', 'client_log', $fields, 'clientid', 'HEAD');
  }

  private function ef_triggers()
  {
    $fields = [
      'Trno' => ['TrNo' => []],
      'Doc' => ['DOC' => []],
      'Document No' => ['DocNo' => []],
      'Date' => ['DateID' => []],
      'Curriculum Name' => ['curriculumname' => []],
      'Terms' => ['terms' => []],
      'YR' => ['yr' => []],
      'Curriculum Code' => ['curriculumcode' => []],
      'Schedule Day' => ['schedday' => []],
      'Schedule Time' => ['schedtime' => []],
      'Schedule Docno' => ['scheddocno' => []],
      'Course' => ['courseid' => [true, "coursecode", "en_course", "line"]],
      'Adviser' => ['adviserid' => [true, "clientname", "client", "clientid"]],
      'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
      'Period' => ['periodid' => [true, "concat(code, '-', name)", "en_period", "line"]],
      'Section' => ['sectionid' => [true, "section", "en_section", "line"]],
      'Semester' => ['semid' => [true, "term", "en_term", "line"]],
      'Building' => ['bldgid' => [true, "concat(bldgcode, '-', bldgname)", "en_bldg", "line"]],
      'Room' => ['roomid' => [true, "concat(roomcode, '-', roomname)", "en_rooms", "line"]],
      'Subject' => ['subjectid' => [true, "concat(subjectcode, '-', subjectname)", "en_subject", "trno"]],
      'Lock Date' => ['lockdate' => []],
      'Lock User' => ['lockuser' => []],
      'View Date' => ['viewdate' => []],
      'View By' => ['viewby' => []],
      'Schedule Trno' => ['schedtrno' => []],
      'Schedule Line' => ['schedline' => []],
      'Is Chinese' => ['ischinese' => []],
    ];

    $this->settriggerlogs('efhead_update', 'AFTER UPDATE', 'en_gshead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Trno' => ['trno' => []],
      'Line' => ['line' => []],
      'GC Code' => ['gccode' => []],
      'GC Name' => ['gcname' => []],
      'GC Percent' => ['gcpercent' => []],
      'Comp ID' => ['compid' => []],

    ];

    $this->settriggerlogs('efcomponent_update_after', 'AFTER UPDATE', 'en_gscomponent', 'transnum_log', $fields, 'trno', 'DETAILS');
  }





  private function os_triggers()
  {
    // drop triggers before create
    // $this->coreFunctions->sbcdroptriggers('oshead_update');
    // $this->coreFunctions->sbcdroptriggers('osstock_update_before');
    // $this->coreFunctions->sbcdroptriggers('osstock_update_after');
    // $this->coreFunctions->sbcdroptriggers('osstock_delete');
    // $this->coreFunctions->sbcdroptriggers('hosstock_update');

    $qry = "create TRIGGER hosstock_update BEFORE UPDATE on hosstock FOR EACH ROW
        BEGIN

          if New.qa>New.qty then
            CALL QTY_IS_GREATER_THAN_OS;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    // ----- > END

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'due' => ['due' => []],
    ];
    $this->settriggerlogs('oshead_update', 'AFTER UPDATE', 'oshead', 'transnum_log', $fields, 'trno', 'HEAD');
    // ----- > END

    $qry = "create TRIGGER osstock_update_before BEFORE UPDATE on osstock FOR EACH ROW
        BEGIN

         if New.QA>New.QTY then
            CALL QTY_IS_GREATER_THAN_OS;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    // ----- > END

    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => []],
      'Product ID' => ['itemid' => []],
      'discount' => ['disc' => []],
      'qty' => ['rrqty' => []],
      'amount' => ['rrcost' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('osstock_update_after', 'AFTER UPDATE', 'osstock', 'transnum_log', $fields, 'trno', 'STOCK');
    // ----- > END

    $qry = "create TRIGGER osstock_delete BEFORE DELETE on osstock FOR EACH ROW
        BEGIN

          if OLD.QA<>0 then
            CALL QTY_SERVED_CANNOT_DELETE_OSSTOCK;
          end if;
        END";
    $this->coreFunctions->execqry($qry, 'trigger');
    // ----- > END
  }

  private function br_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'project' => ['projectid' => [true, 'concat(code,"~",name)', 'projectmasterfile', 'line']],
      'sub project' => ['subproject' => [true, 'subproject', 'subproject', 'line']],
      'dateid' => ['dateid' => []],
      'yourref' => ['yourref' => []],
      'ourref' => ['ourref' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('brhead_update', 'AFTER UPDATE', 'brhead', 'transnum_log', $fields, 'trno', 'HEAD');
    // ----- > END


    $fields = [
      'particulars' => ['particulars' => []],
      'amount' => ['rrcost' => []],
      'qty' => ['qty' => []],
      'uom' => ['uom' => []],
      'total' => ['ext' => []],
      'notes' => ['rem' => []],
      'status' => ['status' => []],
    ];
    $this->settriggerlogs('brstock_update_after', 'AFTER UPDATE', 'brstock', 'transnum_log', $fields, 'trno', 'INV_TAB');
    // ----- > END
  }

  private function bl_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'project' => ['projectid' => [true, 'concat(code,"~",name)', 'projectmasterfile', 'line']],
      'sub project' => ['subproject' => [true, 'subproject', 'subproject', 'line']],
      'BR #' => ['brtrno' => [true, 'docno', 'hbrhead', 'trno']],
      'dateid' => ['dateid' => []],
      'yourref' => ['yourref' => []],
      'ourref' => ['ourref' => []],
      'balance' => ['bal' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('blhead_update', 'AFTER UPDATE', 'blhead', 'transnum_log', $fields, 'trno', 'HEAD');
    // ----- > END


    $fields = [
      'location' => ['location' => []],
      'supplier' => ['supplier' => []],
      'address' => ['address' => []],
      'particulars' => ['particulars' => []],
      'amount' => ['rrcost' => []],
      'qty' => ['qty' => []],
      'uom' => ['uom' => []],
      'total' => ['ext' => []],
      'notes' => ['rem' => []],
      'tin' => ['tin' => []],
      'OR #' => ['ref' => []],
      'input vat' => ['vat' => []],
      'purchase' => ['purchase' => []],
    ];
    $this->settriggerlogs('blstock_update_after', 'AFTER UPDATE', 'blstock', 'transnum_log', $fields, 'trno', 'INV_TAB');
    // ----- > END
  }

  private function ba_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'clientname' => ['clientname' => []],
      'address' => ['address' => []],
      'project' => ['projectid' => [true, 'concat(code,"~",name)', 'projectmasterfile', 'line']],
      'sub project' => ['subproject' => [true, 'subproject', 'subproject', 'line']],
      'stage name' => ['stageid' => [true, 'stage', 'stagesmasterfile', 'line']],
      'dateid' => ['dateid' => []],
      'yourref' => ['yourref' => []],
      'ourref' => ['ourref' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('bahead_update', 'AFTER UPDATE', 'bahead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'CONTRACT QTY' => ['rrqty' => []],
    ];
    $this->settriggerlogs('bastock_update', 'AFTER UPDATE', 'bastock', 'transnum_log', $fields, 'trno', 'SUB_ACTIVITY');
    // ----- > END
  }

  private function rf_triggers()
  {
    // drop triggers before create
    // $this->coreFunctions->sbcdroptriggers('rfhead_update');
    // $this->coreFunctions->sbcdroptriggers('rfstock_update_after');

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'awb' => ['awb' => []],
      // 'shipdate' => ['shipdate' => []],
      'Filed Date' => ['dateid' => []],
      'Filed By' => ['empid' => [true, "concat(client, '~', clientname)", 'client', 'clientid']],
      'Contact Person' => ['cperson' => []],
      'Contact #' => ['tel' => []],
      'Email Address' => ['email' => []],
      'Ship Address' => ['shipaddress' => []],
      'Shipping Address' => ['shipid' => [true, 'addr', 'billingaddr', 'line']],
      'Billing Address' => ['billid' => [true, 'addr', 'billingaddr', 'line']],
      'Shipping Contact Person' => ['shipcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      'Billing Contact Person' => ['billcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']],
      'Action Taken' => ['action' => []],
      'Supplier name' => ['supplierid' => [true, 'clientname', 'client', 'clientid']],
      'Return to Supplier Date' => ['returndate_sup' => []],
      'Return to Supplier By' => ['returndate_supby' => []],
      'Return to Customer Date' => ['returndate_cust' => []],
      'Return to Customer By' => ['returndate_custby' => []],
    ];
    $this->settriggerlogs('rfhead_update', 'AFTER UPDATE', 'rfhead', 'transnum_log', $fields, 'trno', 'HEAD');
    // ----- > END
    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => []],
      // 'Product ID' => ['itemid' => []],
      'Item Name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['isqty' => []],
      'amount' => ['isamt' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
      'Reference' => ['ref' => []],
    ];
    $this->settriggerlogs('rfstock_update_after', 'AFTER UPDATE', 'rfstock', 'transnum_log', $fields, 'trno', 'STOCK');
    // ----- > END
  }

  private function va_triggers()
  {
    $fields = [
      'Trno' => ['trno' => []],
      'Document No' => ['docno' => []],
      'Date' => ['dateid' => []],
      'Warehouse' => ['whid' => [true, "concat(client, '~', clientname)", 'client', 'clientid']],
      'Yourref' => ['yourref' => []],
      'Ourref' => ['ourref' => []],
      'Notes' => ['notes' => []],

      'Port' => ['port' => []],
      'Time Arrival' => ['arrival' => []],
      'Time Departure' => ['departure' => []],
      'Main Engine RPM' => ['enginerpm' => []],
      'Time At Sea' => ['timeatsea' => []],
      'Average Speed' => ['avespeed' => []],
      'Main Engine Fuel Oil Consumption' => ['enginefueloil' => []],
      'Cylinder Oil Consumption' => ['cylinderoil' => []],
      'Main Engine Lube Oil Sump Tank Sounding' => ['enginelubeoil' => []],
      'Highest Exhaust Temp/Cyl Nr.' => ['hiexhaust' => []],
      'Lowest Exhaust Temp/Cyl Nr.' => ['loexhaust' => []],
      'T/C Exhaust Gas Outlet Temperature' => ['exhaustgas' => []],
      'Cool Water Highest/Cyl Nr.' => ['hicoolwater' => []],
      'Cool Water Lowest/Cyl Nr.' => ['locoolwater' => []],
      'L.O. Press' => ['lopress' => []],
      'Cool F.W. Press' => ['fwpress' => []],
      'Scay. Air Press' => ['airpress' => []],
      'Scay. Air Inlet Temp' => ['airinletpress' => []],
      'LO. Cooler In' => ['coolerin' => []],
      'LO. Cooler Out' => ['coolerout' => []],
      'F.W. Cooler F.W. In' => ['coolerfwin' => []],
      'F.W. Cooler F.W. Out' => ['coolerfwout' => []],
      'Sea Water Temp' => ['seawatertemp' => []],
      'Eng Room Temp' => ['engroomtemp' => []],

      'Cash Beginning' => ['begcash' => []],
      'Add Cash Received' => ['addcash' => []],
      'Usage Fee/PPA Clearance Amount' => ['usagefeeamt' => []],
      'Usage Fee/PPA Clearance Notes' => ['usagefee' => []],
      'Mooring/Unmooring Amount' => ['mooringamt' => []],
      'Mooring/Unmooring Notes' => ['mooring' => []],
      'Coast Guard Clearance Amount' => ['coastguardclearanceamt' => []],
      'Coast Guard Clearance Notes' => ['coastguardclearance' => []],
      'Pilotage Amount' => ['pilotageamt' => []],
      'Pilotage Notes' => ['pilotage' => []],
      'Life Bouy/Marker Amount' => ['lifebouyamt' => []],
      'Life Bouy/Marker Notes' => ['lifebouy' => []],
      'Bunkering Permit Amount' => ['bunkeringamt' => []],
      'Bunkering Permit Notes' => ['bunkering' => []],
      'SOP Amount' => ['sopamt' => []],
      'SOP Notes' => ['sop' => []],
      'Others Amount' => ['othersamt' => []],
      'Others Notes' => ['others' => []],
      'Purchases Amount' => ['purchaseamt' => []],
      'Purchases Notes' => ['purchase' => []],
      'Crew Subsistence Amount' => ['crewsubsistenceamt' => []],
      'Crew Subsistence Notes' => ['crewsubsistence' => []],
      'Water Expense Amount' => ['waterexpamt' => []],
      'Water Expense Notes' => ['waterexp' => []],
      'Local Transportation Amount' => ['localtranspoamt' => []],
      'Local Transportation Notes' => ['localtranspo' => []],
      'Others Amount' => ['others2amt' => []],
      'Others Notes' => ['others2' => []],
      'Requested Cash' => ['reqcash' => []],
      'Total Cash' => ['totalcash' => []],
      'Total Expenses' => ['totalexpenses' => []],
      'Cash Balance' => ['cashbalance' => []]
    ];

    $this->settriggerlogs('vahead_update', 'AFTER UPDATE', 'rvoyage', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function entrycalllog()
  {
    $trigger_name = "entrycalllog_update";
    $table = "calllogs";
    $table_log = "transnum_log";
    $key = "trno";

    $fields = [
      'date' => ['dateid' => []],
      'Contact Person' => ['contact' => []],
      'Start Time' => ['starttime' => []],
      'End Time' => ['endtime' => []],
      'Remarks' => ['rem' => []],
      'Status' => ['status' => []],
      'Call Type' => ['calltype' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', $table, $table_log, $fields, 'trno', 'CALL_LOG');
  }

  private function emp_education_triggers()
  {
    $trigger_name = "emp_education_update";
    $table = "education";
    $table_log = "client_log";

    $fields = [
      'line' => ['line' => []],
      'school' => ['school' => []],
      'address' => ['address' => []],
      'course' => ['course' => []],
      'sy' => ['sy' => []],
      'gpa' => ['gpa' => []],
      'honor' => ['honor' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', $table, $table_log, $fields, 'empid', 'EDUCATION');
  }

  private function emp_employment_triggers()
  {
    $trigger_name = "emp_employment_update";
    $table = "employment";
    $table_log = "client_log";

    $fields = [
      'line' => ['line' => []],
      'company' => ['company' => []],
      'address' => ['address' => []],
      'jobtitle' => ['jobtitle' => []],
      'salary' => ['salary' => []],
      'period' => ['period' => []],
      'reason' => ['reason' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', $table, $table_log, $fields, 'empid', 'EMPLOYEMENT');
  }

  private function emp_contract_triggers()
  {
    $trigger_name = "emp_contract_update";
    $table = "contracts";
    $table_log = "client_log";

    $fields = [
      'line' => ['line' => []],
      'contract_no' => ['contractn' => []],
      'description' => ['descr' => []],
      'from' => ['datefrom' => []],
      'to' => ['dateto' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', $table, $table_log, $fields, 'empid', 'CONTRACTS');
  }

  private function vr_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'Employee' => ['clientid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'Department' => ['deptid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'Driver' => ['driverid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'Vehicle' => ['vehicleid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'Date' => ['dateid' => []],
      'Schedule In' => ['schedin' => []],
      'Schedule Out' => ['schedout' => []],
      'Notes' => ['rem' => []],
    ];
    $this->settriggerlogs('vrhead_update', 'AFTER UPDATE', 'vrhead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Schedule In' => ['schedin' => []],
      'Schedule Out' => ['schedout' => []],
      'Purpose' => ['purposeid' => [true, 'purpose', 'purpose_masterfile', 'line']],
      'Address' => ['shipid' => [true, 'addr', 'billingaddr', 'line']],
      'Shipping Contact Person' => ['shipcontactid' => [true, 'concat(lname,", ",fname," ",mname)', 'contactperson', 'line']]
    ];
    $this->settriggerlogs('vrstock_update', 'AFTER UPDATE', 'vrstock', 'transnum_log', $fields, 'trno', 'VSTOCK');
  }

  private function headinfotrans_triggers()
  {
    $fields = [
      'reference #' => ['inspo' => []],
      'delivery date' => ['deldate' => []],
      'ispartial' => ['ispartial' => []],
      'instructions' => ['instructions' => []],
      'validity period' => ['period' => []],
      'is valid' => ['isvalid' => []],
      'other validity date' => ['ovaliddate' => []],
      'terms details' => ['termsdetails' => []],
      'proforma invoice' => ['proformainvoice' => []],
      'proforma date' => ['proformadate' => []],
      'lead time from' => ['leadfrom' => []],
      'lead time To' => ['leadto' => []],
      'lead duration' => ['leaddur' => []],
      'advised' => ['advised' => []],
      'tax rate' => ['taxdef' => []],
      'PO deadline' => ['deadline' => []],
      'sent date' => ['sentdate' => []],
      'pick update' => ['pickupdate' => []],
      'Paymnt Deadline' => ['pdeadline' => []],
      'Plate No' => ['plateno' => []],
      'Truck' => ['truckid' => [true, 'clientname', 'client', 'clientid']],
      'Helper' => ['helperid' => [true, 'clientname', 'client', 'clientid']],
      'Cheker' => ['checkerid' => [true, 'clientname', 'client', 'clientid']],
      'internal notes' => ['rem2' => []],
      'Approval Reason' => ['approvalreason' => []],
    ];
    $this->settriggerlogs('headinfotrans_update', 'AFTER UPDATE', 'headinfotrans', 'transnum_log', $fields, 'trno', 'HEADINFO');
  }

  private function stockinfo_triggers()
  {
    $fields = [
      'line' => ['line' => []],
      'notes' => ['rem' => []],
      'Received 1 Qty' => ['qty1' => []],
      'Received 2 Qty' => ['qty2' => []],
      'Transmittal Qty' => ['tqty' => []],
      'Item description' => ['itemdesc' => []],
      'Received Status 1' => ['status1' => [true, 'status', 'trxstatus', 'line']],
      'Received Status 2' => ['status2' => [true, 'status', 'trxstatus', 'line']],
      // 'amt1' => ['amt1' => []],
      // 'amt2' => ['amt2' => []],
      // 'famt' => ['famt' => []],
      // 'amt3' => ['amt3' => []],
      // 'lamt' => ['lamt' => []],
      // 'amt4' => ['amt4' => []],
      // 'amt5' => ['amt5' => []],
      // 'leaddur' => ['leaddur' => []],
      // 'advised' => ['advised' => []],
      // 'leadfrom' => ['leadfrom' => []],
      // 'leadto' => ['leadto' => []],
      // 'validity' => ['validity' => []],
      // 'nvat' => ['nvat' => []],
      // 'vatamt' => ['vatamt' => []],
      // 'vatex' => ['vatex' => []],
      // 'sramt' => ['sramt' => []],
      // 'pwdamt' => ['pwdamt' => []],
      // 'lessvat' => ['lessvat' => []],
      // 'discamt' => ['discamt' => []],
      // 'vipdisc' => ['vipdisc' => []],
      // 'empdisc' => ['empdisc' => []],
      // 'oddisc' => ['oddisc' => []],
      // 'smacdisc' => ['smacdisc' => []],
      // 'pickerid' => ['pickerid' => []],
      // 'checkerid' => ['checkerid' => []],
    ];
    $this->settriggerlogs('stockinfo_update', 'AFTER UPDATE', 'stockinfo', 'table_log', $fields, 'trno', 'STOCKINFO');
  }

  private function stockinfotrans_triggers()
  {
    $fields = [
      'line' => ['line' => []],
      'Notes' => ['rem' => []],
      'Item description' => ['itemdesc' => []],
      'unit' => ['unit' => []],
      'Purpose' => ['purpose' => []],
      'Requestor' => ['requestorname' => []],
      'Date needed' => ['dateneeded' => []],
      'Specifications' => ['specs' => []],
      'Deadline' => ['ovaliddate' => []],
      'is valid' => ['isvalid' => []],
      'Lead Time Settings' => ['leadtimesettings' => []],
      'Duration' => ['durationid' => [true, 'duration', 'duration', 'line']],
      'Customer Currency' => ['customercur' => []],
      'Vendor Currency' => ['vendorcur' => []],
      'Vendor Costprice' => ['vendorcostprice' => []],
      'Quantity' => ['quantity' => []],
      'Freight' => ['freight' => []],
      'Markup' => ['markup' => []],
      'Selling Price' => ['amt1' => []],
      'Exchange Rate' => ['exchangerate' => []],
      'Ref. Conversion UOM' => ['uom2' => []],
      'Ref. Conversion' => ['uom3' => []]
    ];
    $this->settriggerlogs('stockinfotrans_update', 'AFTER UPDATE', 'stockinfotrans', 'transnum_log', $fields, 'trno', 'STOCKINFOTRANS');
  }

  private function hstockinfotrans_triggers($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    switch ($systemtype) {
      case 'ATI':
        $amt1 = 'Freight fees';
        $amt2 = 'Installation fees';
        break;
      default:
        $amt1 = 'amt1';
        $amt2 = 'amt2';
        break;
    }

    $fields = [
      $amt1 => ['amt1' => []],
      $amt2 => ['amt2' => []],
      'Deadline' => ['ovaliddate' => []],
      'Duration' => ['durationid' => [true, 'duration', 'duration', 'line']],
    ];
    $this->settriggerlogs('hstockinfotrans_update', 'AFTER UPDATE', 'hstockinfotrans', 'transnum_log', $fields, 'trno', 'STOCKINFOTRANS');
  }

  private function hstockinfo_triggers($config)
  {
    $fields = [
      'Payment Notes' => ['payrem' => []],
    ];
    $this->settriggerlogs('hstockinfo_update', 'AFTER UPDATE', 'hstockinfo', 'table_log', $fields, 'trno', 'HSTOCKINFO');
  }

  private function detailinfo_triggers()
  {
    $fields = [
      'line' => ['line' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('detailinfo_update', 'AFTER UPDATE', 'detailinfo', 'table_log', $fields, 'trno', 'DETAILINFO');
  }

  private function entryvritems()
  {
    $trigger_name = "entryvritems_update";
    $table = "vritems";
    $table_log = "transnum_log";
    $key = "trno";

    $fields = [
      'Itemname' => ['itemname' => []],
      'Uom' => ['uom' => []],
      'Quantity' => ['qty' => []],

    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', $table, $table_log, $fields, $key, 'HEAD');
  }

  private function entryvrpassenger()
  {
    $trigger_name = "entryvrpassenger_update";
    $table = "vrpassenger";
    $table_log = "transnum_log";
    $key = "trno";

    $fields = [
      'Passenger' => ['passengerid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', $table, $table_log, $fields, $key, 'HEAD');
  }

  private function sjdeliverystat_triggers()
  {
    $fields = [
      'trno' => ['trno' => []],
      'modeofdelivery' => ['modeofdelivery' => []],
      'driver' =>  ['driver' => []],
      'receiveby' => ['receiveby' => []],
      'receivedate' => ['receivedate' => []],
      'remarks' => ['remarks' => []],
      'couriername' => ['couriername' => []],
      'trackingno' => ['trackingno' => []],
      'releaseby' => ['releaseby' => []],
      'releasedate' => ['releasedate' => []],
      'delcharge' => ['delcharge' => []]
    ];

    $this->settriggerlogs('delstatus_update', 'AFTER UPDATE', 'delstatus', 'table_log', $fields, 'trno', 'DELIVERY STATUS');
  }

  private function entrypricebracket_tab_triggers()
  {
    $trigger_name = "pricebracket_update";
    $table = "pricebracket";
    $table_log = "item_log";
    $key = "itemid";

    $fields = [
      'Retail' => ['r' => []],
      'Wholesale' => ['w' => []],
      'Price A' => ['a' => []],
      'Price B' => ['b' => []],
      'Price C' => ['c' => []],
      'Price D' => ['d' => []],
      'Price E' => ['e' => []],
      'Price F' => ['f' => []],
      'Price G' => ['g' => []]
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', $table, $table_log, $fields, $key, "PRICE BRACKET");
  }

  private function iteminfo_triggers($config)
  {
    $trigger_name = "iteminfo_update";
    $table = "iteminfo";
    $table_log = "item_log";
    $key = "itemid";

    $endinsuredlbl = 'End Insured';
    $dateacquiredlbl = 'Date Acquired';
    $leasedatelbl = 'lease date';
    switch ($config['params']['companyid']) {
      case 43: //mighty
        $endinsuredlbl = 'Insurance Expiry';
        $dateacquiredlbl = 'Acquired Date';
        $leasedatelbl = 'In Service Date';
        break;
    }

    $fields = [
      'Item Description' => ['itemdescription' => []],
      'Accessories' => ['accessories' => []],
      'Sub Group' => ['subgroup' => []],
      'Company' => ['company' => []],
      'serial no' => ['serialno' => []],
      'i condition' => ['icondition' => []],
      'Disposal Date' => ['disposaldate' => []],
      'Insurance' => ['insurance' => []],
      'Start insured' => ['startinsured' => []],
      $endinsuredlbl => ['endinsured' => []],
      $dateacquiredlbl => ['dateacquired' => []],
      'invoice no' => ['invoiceno' => []],
      'invoice date' => ['invoicedate' => []],
      'warranty expiry' => ['warrantyend' => []],
      'renewal date' => ['renewaldate' => []],
      'po no' => ['pono' => []],
      'po date' => ['podate' => []],
      $leasedatelbl => ['leasedate' => []],
      'depreyrs' => ['depreyrs' => []],
      'Plate no' => ['plateno' => []],
      'vin no' => ['vinno' => []],
      'manufacturer' => ['manufacturer' => []],
      'year' => ['fyear' => []],
      'fueltype' => ['fueltype' => []],
      'engine' => ['engine' => []],
      'tranfser date' => ['tranfserdate' => []],
      'issue date' => ['issuedate' => []],
      'Volume' => ['volume' => []],
      'Weight' => ['weight' => []],
      'Chassis No' => ['chassisno' => []],
    ];

    $this->settriggerlogs($trigger_name, 'AFTER UPDATE', $table, $table_log, $fields, $key, "ITEM INFO");
  }

  private function oq_triggers()
  {

    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'terms' => ['terms' => []],
      'warehouse' => ['wh' => []],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'serialized' => ['ourref' => []],
      'ship to' => ['shipto' => []],
      'remarks' => ['rem' => []],
      'forex' => ['forex' => []],
      'date' => ['dateid' => []],
      'currency' => ['cur' => []],
      'Invoice Not Required' => ['invnotrequired' => []],
      'Serialized' => ['serialized' => []],
      'due' => ['due' => []]
    ];
    $this->settriggerlogs('oqhead_update', 'AFTER UPDATE', 'oqhead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Total' => ['ext' => []],
      'warehouse' => ['whid' => []],
      'Product ID' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'Product Name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'Disc.' => ['disc' => []],
      'Qty' => ['rrqty' => []],
      'Amout' => ['rrcost' => []],
      'Uom' => ['uom' => []],
      'Notes' => ['rem' => []],
      'Oracle Code' => ['oraclecode' => []],
      'Partial' => ['ispartial' => []],
      'No Edit Price' => ['ispa' => []],
    ];
    $this->settriggerlogs('oqstock_update_after', 'AFTER UPDATE', 'oqstock', 'transnum_log', $fields, 'trno', 'STOCK');
  }

  private function rc_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'Date' => ['dateid' => []],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'check #' => ['checkno' => []],
      'check date' => ['checkdate' => []],
      'agent' => ['agent' =>  [true, 'concat(client,"~",clientname)', 'client', 'client']],
      'amount' => ['amount' => []],
      'remarks' => ['rem' => []],
      'phase' => ['phaseid' =>  [true, 'code', 'phase', 'line']],
      'house model' => ['modelid' =>  [true, 'model', 'housemodel', 'line']],
      'blk/lot' => ['phaseid' =>  [true, 'concat(blk," and ",lot)', 'blklot', 'line']],
    ];
    $this->settriggerlogs('rchead_update', 'AFTER UPDATE', 'rchead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'check #' => ['checkno' => []],
      'amount' => ['amount' => []],
      'check date' => ['checkdate' => []],
      'remarks' => ['rem' => []]
    ];
    $this->settriggerlogs('rcdetail_update_after', 'AFTER UPDATE', 'rcdetail', 'transnum_log', $fields, 'trno', 'ACCOUNTING');
  }

  private function af_triggers($config)
  {
    $companyid = ($config['params']['companyid']);

    if ($companyid == 55) { //afli
      $fields = [
        //borower
        'document #' => ['docno' => []],
        'effectivity date' => ['dateid' => []],
        'application date' => ['releasedate' => []],
        'type of loan' => ['planid' => [true, "reqtype", "reqcategory", "line"]],
        'purpose of loan' => ['purpose' => []],
        'terms' => ['terms' => []],

        'client' => ['client' => []],
        'client name' => ['clientname' => []],
        'payor first name' => ['fname' => []],
        'payor middle name' => ['mname' => []],
        'payor last name' => ['lname' => []],
        'mothers maiden name' => ['mmname' => []],
        'present address' => ['address' => []],
        'provincial address' => ['province' => []],
        'mailing address' => ['addressno' => []],
        'telephone# ' => ['contactno' => []],
        'date & place of birth' => ['street' => []],
        'civil status' => ['civilstatus' => []],
        'no. of dependents' => ['dependentsno' => []],
        'nationality' => ['nationality' => []],
        'borrower addr & tel. #' => ['subdistown' => []],
        'borrower employer name/business name' => ['employer' => []],
        'position held' => ['country' => []],
        'length of stay' => ['brgy' => []],

        //spouse of bro
        'spouse name' => ['sname' => []],
        'spouse employer name/business name' => ['ename' => []],
        'spouse addr & tel. #' => ['city' => []],
        'spouse monthly income' => ['monthly' => []],

        //other info ng borrower
        'vehicle year' => ['zipcode' => []],
        'vehicle model' => ['yourref' => []],
        'savings account#' => ['email' => []],
        'current account#' => ['current1' => []],
        'current bank' => ['current2' => []],
        'others account#' => ['others1' => []],
        'others bank' => ['others2' => []],
        'monthly income (applicant)' => ['mincome' => []],
        'monthly expenses' => ['mexp' => []],

        'date of issue' => ['voiddate' => []],
        'residence number' => ['num' => []],
        'place of issue' => ['pliss' => []],
        'tin' => ['tin' => []],
        's.s.s/g.s.i.s #' => ['sssgsis' => []],

        //co-maker
        'co-maker telephone#' => ['contactno2' => []],
        'co-maker vehicle model' => ['ourref' => []],

        ///LOAN COMPUTATION
        'interest' => ['interest' => []]

      ];
    } else {
      $fields = [
        'document #' => ['docno' => []],
        'client' => ['client' => []],
        'client name' => ['clientname' => []],
        'payor first name' => ['fname' => []],
        'payor middle name' => ['mname' => []],
        'payor last name' => ['lname' => []],
        'payor Extension Name' => ['ext' => []],
        'address' => ['address' => []],
        'address #' => ['addressno' => []],
        'street' => ['street' => []],
        'Sub./Dist./Town' => ['subdistown' => []],
        'city' => ['city' => []],
        'country' => ['country' => []],
        'zipcode' => ['zipcode' => []],
        'Date' => ['dateid' => []],
        'terms' => ['terms' => []],
        'otherterms' => ['otherterms' => []],
        'Remarks' => ['rem' => []],
        'voiddate' => ['voiddate' => []],
        'agent' => ['agent' =>  [true, 'concat(client,"~",clientname)', 'client', 'client']],
        'Method' => ['yourref' => []],
        'ourref' => ['ourref' => []],
        'primary contact #' => ['contactno' => []],
        'alternative contact #' => ['contactno2' => []],
        'email' => ['email' => []],
        'vattype' => ['vattype' => []],
        'Plan Type' => ['planid' =>  [true, 'name', 'plantype', 'line']],
        'tax' => ['tax' => []],
      ];
    }
    $this->settriggerlogs('eahead_update', 'AFTER UPDATE', 'eahead', 'transnum_log', $fields, 'trno', 'HEAD');

    if ($companyid == 55) { //afli
      $fields = [

        'loan amount' => ['amount' => []],
        //borrower
        'owned' => ['issameadd' => []],
        'rented' => ['isbene' => []],
        'free' => ['ispf' => []],

        //borrower spouse 
        'spouse date & place of birth' => ['paddress' => []],
        'position held' => ['pcity' => []],
        'length of stay' => ['pcountry' => []],
        'immediate superior' => ['pprovince' => []],

        //other info ng borrower
        'property location' => ['idno' => []],
        'property value' => ['value' => []],
        'not mortgaged' => ['isdp' => []],
        'mortgaged' => ['isotherid' => []],
        'savings Bank' => ['bank1' => []],
        'personal references 1' => ['pob' => []],
        'personal references 2' => ['otherplan' => []],
        'attorney-in-fact' => ['attorneyinfact' => []],
        'attorney address' => ['attorneyaddress' => []],

        //comaker
        'co-maker last name' => ['lname' => []],
        'co-maker first name' => ['fname' => []],
        'co-maker middle name' => ['mname' => []],
        'co-maker mothers maiden name' => ['mmname' => []],
        'co-maker present address' => ['address' => []],
        'co-maker provincial address' => ['province' => []],
        'co-maker mailing address' => ['addressno' => []],
        'owned' => ['iswife' => []],
        'rented' => ['isretired' => []],
        'free' => ['isofw' => []],
        'co-maker date & place of birth' => ['street' => []],
        'co-maker civil status' => ['civilstat' => []],
        'co-maker no. of dependents' => ['dependentsno' => []],
        'nationality' => ['nationality' => []],
        'co-maker name of employer/business' => ['employer' => []],
        'co-maker address & tel. #' => ['subdistown' => []],
        'co-maker position held' => ['country' => []],
        'co-maker length of stay' => ['brgy' => []],

        //spouse of co-maker
        'co-maker spouse name' => ['sname' => []],
        'co-maker spouse date & place of birth' => ['pstreet' => []],
        'co-maker spouse name of employer/business' => ['ename' => []],
        'co-maker spouse address & tel#' => ['city' => []],
        'co-maker spouse position held' => ['paddressno' => []],
        'co-maker spouse monthly income' => ['dp' => []],
        'co-maker spouse length of stay' => ['psubdistown' => []],
        'co-maker spouse immediate superior' => ['othersource' => []],

        //other info ng co-maker
        'co-maker property location' => ['ext' => []],
        'co-maker property value' => ['value2' => []],
        'co-maker property not morgaged' => ['isprc' => []],
        'co-maker property morgaged' => ['isdriverlisc' => []],
        'co-maker vehicle year' => ['zipcode' => []],
        'co-maker savings account#' => ['savings1' => []],
        'co-maker savings bank' => ['savings2' => []],
        'co-maker current account#' => ['current1' => []],
        'co-maker current bank' => ['current2' => []],
        'co-maker others account#' => ['others1' => []],
        'co-maker others bank' => ['others2' => []],
        'co-maker monthly income' => ['mincome' => []],
        'co-maker monthly expenses' => ['mexp' => []],
        'co-maker personal references 1' => ['pbrgy' => []],
        'co-maker personal references 2' => ['appref' => []],

        'co-maker residence number' => ['num' => []],
        'co-maker residence date of issue' => ['bday' => []],
        'co-maker residence place of issue' => ['pliss' => []],
        'co-maker tin' => ['tin' => []],
        'co-maker sss/gsis no.' => ['sssgsis' => []],


        //checklist-

        ///approving body
        'payroll type' => ['payrolltype' => []],
        'employee type' => ['employeetype' => []],
        'expiration' => ['expiration' => []],
        'loan limit(% of salary)' => ['loanlimit' => []],
        'loanable amount' => ['loanamt' => []],

        //for housing loan only
        'tct#' => ['tct' => []],
        'subdivision' => ['subdivision' => []],
        'blk & lot no./ address' => ['blklot' => []],
        'area' => ['area' => []],
        'price per sqm.' => ['pricesqm' => []],
        'contract price' => ['tcp' => []],
        'discount' => ['disc' => []],
        'outstanding balance' => ['outstanding' => []],
        'penalty' => ['penaltyamt' => []],


        ////TAKEOUT/OTHER FEES logs
        'entry fee' => ['entryfee' => []],
        'legal research fee' => ['lrf' => []],
        'it fee/ computer fee' => ['itfee' => []],
        'registration fee' => ['regfee' => []],
        'documentary stamps' => ['docstamp1' => []],
        'legal & notarial fee' => ['nf' => []],
        'annotation of special power of attorney' => ['annotationfee' => []],
        'articles of inc. & by laws' => ['articles' => []],
        'annotation expenses' => ['annotationexp' => []],
        'transfer of ownership' => ['otransfer' => []],
        'service fee' => ['pf' => []],
        'real property tax' => ['rpt' => []],
        'documentary stamp tax' => ['docstamp' => []],
        'mri' => ['mri' => []],
        'handling fee' => ['handling' => []],
        'appraisal fee' => ['appraisal' => []],
        'processing fee/filing fee' => ['filing' => []],
        'notarial fee: deed of undertaking' => ['nf2' => []],
        'notarial fee: deed of assignment' => ['nf3' => []],
        'other fees' => ['ofee' => []],
        'referral fee' => ['referral' => []],
        'cancellation : sec 4 rule 74' => ['cancellation4' => []],
        'cancellation : sec 7 RA 26' => ['cancellation7' => []],
        'annotation of correct tech description' => ['annotationoc1' => []],
        'annotation of Aff of one and the same person' => ['annotationoc2' => []],
        'cancellation: ULAMA' => ['cancellationu' => []],

        /////loan
        'interest per annum' => ['intannum' => []],
        'processing fee' => ['pf' => []],
        'notarial fee' => ['nf' => []],
        'amortization' => ['amortization' => []],
        'penalty' => ['penalty' => []],
        'terms allowance (no. of mos.)' => ['voidint' => []],
        'ma factor1' => ['fmons' => []],
        'ma factor2' => ['fannum' => []],
        'ma factor3' => ['frate' => []]

      ];
    } else {

      $fields = [
        'client' => ['client' => []],
        'client name' => ['clientname' => []],
        'plan holder first name' => ['fname' => []],
        'plan holder middle name' => ['mname' => []],
        'plan holder last name' => ['lname' => []],
        'plan holder Extension Name' => ['ext' => []],
        'isplanholder' => ['isplanholder' => []],
        'gender' => ['gender' => []],
        'civilstat' => ['civilstat' => []],
        'residential address' => ['address' => []],
        'residential address #' => ['addressno' => []],
        'residential street' => ['street' => []],
        'residential Sub./Dist./Town' => ['subdistown' => []],
        'residential city' => ['city' => []],
        'residential country' => ['country' => []],
        'residential zipcode' => ['zipcode' => []],
        'permanent address' => ['paddress' => []],
        'permanent address #' => ['paddressno' => []],
        'permanent street' => ['pstreet' => []],
        'permanent Sub./Dist./Town' => ['psubdistown' => []],
        'permanent city' => ['pcity' => []],
        'permanent country' => ['pcountry' => []],
        'permanent zipcode' => ['pzipcode' => []],
        'birthday' => ['bday' => []],
        'place of birth' => ['pob' => []],
        'nationality' => ['nationality' => []],
        'rem' => ['rem' => []],
        'ispassport' => ['ispassport' => []],
        'isprc' => ['isprc' => []],
        'isdriverlisc' => ['isdriverlisc' => []],
        'other id' => ['isotherid' => []],
        'id #' => ['idno' => []],
        'expiration' => ['expiration' => []],
        'isemployment' => ['isemployment' => []],
        'isinvestment' => ['isinvestment' => []],
        'isbusiness' => ['isbusiness' => []],
        'isothersource' => ['isothersource' => []],
        'othersource' => ['othersource' => []],
        'isemployed' => ['isemployed' => []],
        'isselfemployed' => ['isselfemployed' => []],
        'isofw' => ['isofw' => []],
        'isretired' => ['isretired' => []],
        'iswife' => ['iswife' => []],
        'isnotemployed' => ['isnotemployed' => []],
        'employer' => ['employer' => []],
        'tin' => ['tin' => []],
        'sss/gsis' => ['sssgsis' => []],
        'less 10k' => ['lessten' => []],
        '10,001 - 30k' => ['tenthirty' => []],
        '30,001 - 50k' => ['thirtyfifty' => []],
        '50,001 - 100k' => ['fiftyhundred' => []],
        '100,00 - 250k' => ['hundredtwofifty' => []],
        '250,001 - 500k' => ['twofiftyfivehundred' => []],
        'more than 500,001' => ['fivehundredup' => []],
        'otherplan' => ['otherplan' => []],
        'amount' => ['amount' => []],
      ];
    }
    $this->settriggerlogs('eainfo_update', 'AFTER UPDATE', 'eainfo', 'transnum_log', $fields, 'trno', 'INFO');
  }

  private function wn_triggers()
  {
    $fields = [
      'document #' => ['docno' => []],
      'client' => ['client' => []],
      'client name' => ['clientname' => []],
      'address' => ['address' => []],
      'Date' => ['dateid' => []],
      'project' => ['projectid' => [true, 'name', 'projectmasterfile', 'line']],
      'address' => ['address' => []],
      'your ref' => ['yourref' => []],
      'our ref' => ['ourref' => []],
      'agent' => ['agent' =>  [true, 'concat(client,"~",clientname)', 'client', 'client']],
      'remarks' => ['rem' => []],
      'Meter No.' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
    ];
    $this->settriggerlogs('wnhead_update', 'AFTER UPDATE', 'wnhead', 'transnum_log', $fields, 'trno', 'HEAD');
  }

  private function lp_triggers()
  {
    $fields = [
      'Document #' => ['docno' => []],
      'Client name' => ['clientname' => []],
      'Address' => ['address' => []],
      'Bus. Style' => ['bstyle' => []],
      'Category' => ['category' => []],
      'Email' => ['email' => []],
      'Start Date' => ['start' => []],
      'End Date' => ['enddate' => []],
      'Contract' => ['contract' => []],
      'Date' => ['dateid' => []],
      'Tin' => ['tin' => []],
      'contact' => ['contact' => []],
      'NonVAT' => ['isnonvat' => []],
      'Position' => ['position' => []],
      'Location' => ['locid' =>  [true, 'concat(code,"~",name)', 'loc', 'line']],
    ];
    $this->settriggerlogs('lphead_update', 'AFTER UPDATE', 'lphead', 'transnum_log', $fields, 'trno', 'HEAD');

    $fields = [
      'Lease Rate' => ['leaserate' => []],
      'ACRate' => ['acrate' => []],
      'cusarate' => ['cusarate' => []],
      'Bill Type' => ['billtype' => []],
      'Rent Category' => ['rentcat' => []],
      'MonthlyCharge' => ['mcharge' => []],
      'PercentageSales' => ['percentsales' => []],
      'TenantType' => ['tenanttype' => []],
      'ElecMultiplier' => ['emulti' => []],
      'WaterMultiplier' => ['wmulti' => []],
      'SelecMultiplier' => ['semulti' => []],
      'ElecWaterCharges' => ['ewcharges' => []],
      'ConstructionCharges' => ['concharges' => []],
      'PlywoodFencing' => ['fencecharge' => []],
      'PowerCharges' => ['powercharges' => []],
      'WaterCharges' => ['watercharges' => []],
      'Housekeeping' => ['housekeeping' => []],
      'DocStamp' => ['docstamp' => []],
      'ConstructionBond' => ['consbond' => []],
      'ElecMeterDeposit' => ['emeterdep' => []],
      'ServiceBillDeposit' => ['servicedep' => []],
      'SecurityDeposit' => ['secdep' => []],
      'SecurityDepositMos' => ['secdepmos' => []],
      'Remakrs' => ['rem' => []],
    ];
    $this->settriggerlogs('tenantinfo_update', 'AFTER UPDATE', 'tenantinfo', 'transnum_log', $fields, 'trno', 'INFO');
  }

  private function si_triggers()
  {
    $fields = [
      'total' => ['ext' => []],
      'warehouse' => ['whid' => [true, 'concat(client,"~",clientname)', 'client', 'clientid']],
      'barcode' => ['itemid' => [true, 'barcode', 'item', 'itemid']],
      'item name' => ['itemid' => [true, 'itemname', 'item', 'itemid']],
      'discount' => ['disc' => []],
      'qty' => ['isqty' => []],
      'amount' => ['isamt' => []],
      'uom' => ['uom' => []],
      'notes' => ['rem' => []],
    ];
    $this->settriggerlogs('sistock_update_after', 'AFTER UPDATE', 'sistock', 'table_log', $fields, 'trno', 'STOCK');

    $qry = "create TRIGGER sistock_before_insert BEFORE INSERT on sistock FOR EACH ROW
        BEGIN
          DECLARE exist INT;
          SELECT COUNT(trno) INTO exist FROM lahead WHERE trno=NEW.trno; 
          IF exist = 0 THEN
            CALL CANNOT_ADD_ITEM_NO_HEAD_REFERENCE;
          END IF; 
        END;";
    $this->coreFunctions->execqry($qry, 'trigger');

    //END OF sistock TRIGGER ====================================================================================================================================
  }


  private function px_triggers()
  {
    // PXHEAD TRIGGER ===================================================================================================================
    $fields = [
      'project' => ['project' => []],
      'document #' => ['docno' => []],
      'date' => ['dateid' => []],
      'dtc #' => ['dtcno' => []],
      'pcf #' => ['pcfno' => []],
      'po ref' => ['poref' => []],
      'aftistock' => ['aftistock' => []],

      'fullcomm' => ['fullcomm' => []],
      'rem' => ['rem' => []],
      'clientname' => ['clientname' => []],
      'oandaphpusd' => ['oandaphpusd' => []],
      'oandausdphp' => ['oandausdphp' => []],
      'osphpusd' => ['osphpusd' => []],
      'percentage' => ['percentage' => []],

      'commamt' => ['commamt' => []],
      'remarks' => ['remarks' => []],
      'terms' => ['terms' => []],
      'termsdetails' => ['termsdetails' => []],
      'checkdate' => ['checkdate' => []],
      'lostdate' => ['lostdate' => []],
      'reason' => ['reason' => []]
    ];
    $this->settriggerlogs('pxhead_update', 'AFTER UPDATE', 'pxhead', 'transnum_log', $fields, 'trno', 'HEAD');
    //END PXHEAD TRIGGER ===================================================================================================================

    // pxchecking TRIGGER ===================================================================================================================
    $fields = [
      'budget' => ['budget' => []],
      'actual' => ['actual' => []],
      'rem' => ['rem' => []],
      'expenseid' => ['expenseid' => []]
    ];
    $this->settriggerlogs('pxchecking_update', 'AFTER UPDATE', 'pxchecking', 'transnum_log', $fields, 'trno', 'HEAD');
    //END pxchecking TRIGGER ===================================================================================================================

    // PXSTOCK TRIGGER ===================================================================================================================
    $fields = [
      'rrqty' => ['rrqty' => []],
      'rrcost' => ['rrcost' => []],
      'ext' => ['ext' => []],
      'srp' => ['srp' => []],
      'totalsrp' => ['totalsrp' => []],
      'tp' => ['tp' => []],
      'totaltp' => ['totaltp' => []]
    ];
    $this->settriggerlogs('pxstock_update_after', 'AFTER UPDATE', 'pxstock', 'transnum_log', $fields, 'trno', 'STOCK');
    //END PXSTOCK TRIGGER ===================================================================================================================
  }

  public function cleardb_proc()
  {

    $qry = "DROP PROCEDURE IF EXISTS `ClearDB`;
      CREATE PROCEDURE `ClearDB`()
      BEGIN
        delete from mcdetail;
        delete from hmcdetail;
        delete from snstock;
        delete from hsnstock;
        delete from hphstock;
        delete from hphhead;
        delete from phhead;
        delete from phstock;
        delete from pyhead;
        delete from hpyhead;
        delete from wnhead;
        delete from hwnhead;
        delete from eahead;
        delete from heahead;
        delete from eainfo;
        delete from heainfo;
        delete from beneficiary;
        delete from plantype;
        delete from plangrp;
        delete from acontacts;
        delete from adependents;
        delete from adminlog;
        delete from aeducation;
        delete from aemployment;
        delete from agentcommtbl;
        truncate allowsetup;
        delete from apledger;
        delete from app;
        delete from app_picture;
        truncate approverinfo;
        truncate apreemploy;
        delete from arequire;
        delete from arledger;
        delete from attemptolog;
        delete from attendee;
        delete from bank;
        delete from batch;
        delete from bom;
        delete from boxinginfo;
        truncate bankcharges;
        delete from branchagent;
        delete from branchbank;
        delete from branchbrand;
        delete from branchstation;
        delete from branchtables;
        delete from branchusers;
        delete from branchwh;
        delete from brecon;
        delete from blocklotroxas;
        delete from subamenityroxas;
        delete from subprojectroxas;
        delete from amenityroxas;
        delete from projectroxas;
        delete from departmentroxas;  
        delete from caledger;
        delete from calllogs;
        delete from canvasshead;
        delete from canvasslist;  
        delete from category_masterfile;
        delete from cbledger;
        delete from cdhead;
        delete from cdstock;
        delete from center_log;
        truncate changeshiftapp;
        delete from checkerloc;
        truncate checktypes;        
        delete from clearance;
        delete from client;
        delete from clientdlock;
        delete from clientprojects;
        delete from client_log;
        delete from client_picture;
        delete from clientsano;
        delete from clientinfo;  
        truncate cmodels;
        delete from cntnum_picture;
        delete from cntnuminfo;
        -- delete from coa;
        truncate codedetail;
        truncate codehead;
        delete from component;
        truncate commissionlist;
        delete from contacts;
        delete from contracts;
        delete from contactperson;
        delete from conversation_user;
        truncate conversation_msg;
        truncate conversation_msg_info;  
        delete from costing;
        delete from crledger;
        delete from ckhead;
        delete from ckstock;
        delete from daysched;  
        delete from deposit;
        delete from del_client_log;
        delete from del_center_log;
        delete from del_item_log;
        delete from del_hrisnum_log;
        delete from del_masterfile_log;
        delete from del_table_log;
        delete from del_terms_log;
        delete from del_transnum_log;
        delete from del_useraccess_log;
        delete from del_users_log;
        truncate deliverytype;
        delete from department;
        delete from dependents;
        delete from division;
        delete from disciplinary;
        delete from docprefix_log;
        truncate docunum;
        delete from docunum_log;
        delete from docunum_picture;
        delete from del_docunum_log;
        truncate dt_documenttype;
        delete from dt_details;
        delete from dt_dtstock;
        delete from dt_issues;
        truncate dt_status;
        truncate dt_statuslist;
        truncate duration;
        delete from dchead;
        delete from dcdetail;
        delete from dphead;  
        delete from education;
        delete from employee;
        delete from employment;
        delete from ewtlist;
        delete from emprequire;
        truncate empprojdetail;
        truncate emprole;
        delete from empstatentry;
        delete from en_addetail;
        delete from en_adhead;
        delete from en_adotherfees;
        delete from en_adsubject;
        delete from en_atfees;
        delete from en_athead;
        delete from en_atstudents;
        delete from en_bldg;
        delete from en_cchead;
        delete from en_ccsubject;
        delete from en_course;
        delete from en_credentials;
        delete from en_dept;
        delete from en_fees;
        delete from en_gegrades;
        delete from en_gehead;
        delete from en_glcredentials;
        delete from en_gldetail;
        delete from en_glfees;
        delete from en_glhead;
        delete from en_glotherfees;
        delete from en_glsubject;
        delete from en_glsummary;
        delete from en_gradecomponent;
        delete from en_gradeequivalent;
        delete from en_gscomponent;
        delete from en_instructor;
        delete from en_levels;
        delete from en_modeofpayment;
        delete from en_period;
        delete from en_requirements;
        delete from en_rooms;
        delete from en_schead;
        delete from en_scheme;
        delete from en_schoolyear;
        delete from en_scsubject;
        delete from en_section;
        delete from en_sgshead;
        delete from en_sgssubject;
        delete from en_rchead;
        delete from en_rcdetail;
        delete from en_srchead;
        delete from csstickethead;
        delete from dt_dthead;
        delete from en_sjcredentials;
        delete from en_sjdetail;
        delete from en_sjhead;
        delete from en_sjotherfees;
        delete from en_sjsubject;
        delete from en_sjsummary;
        delete from en_socredentials;
        delete from en_sodetail;
        delete from en_sohead;
        delete from en_sootherfees;
        delete from en_sosubject;
        delete from en_sosummary;
        delete from en_studentcredentials;
        delete from en_studentinfo;
        delete from en_subject;
        delete from en_subjectcurriculum;
        delete from en_subjectequivalent;
        delete from en_term;
        delete from eod;
        delete from eschange;
        delete from execution_log;
        delete from exhibit;
        delete from eqhead;
        delete from eqstock;
        truncate examinees;  
        delete from fgi_colors;
        delete from fgi_cylinder;
        delete from fg_colors;
        delete from fg_cylinder;
        truncate floor;
        delete from forex_masterfile;
        delete from frontend_ebrands;
        delete from frontend_endedsale;
        delete from fsitedetails;  
        delete from generalitem;
        delete from gldetail;
        delete from glhead;
        delete from glstock;
        delete from gphead;
        delete from gpstock;
  
        delete from hboxinginfo;
        delete from hclearance;
        delete from hcntnuminfo;
        delete from cehead;
        delete from hcehead;
        delete from hcnhead;
        delete from hcnstock;

        delete from tripdetail;
        delete from htripdetail;

        delete from hdt_dthead;
        delete from hdt_dtstock;

        delete from headrem;
        delete from hheadrem;

        delete from head;
        delete from headprrem;
        delete from heschange;
        delete from hheadinfotrans;
        delete from headinfotrans;
        delete from hgphead;
        delete from hgpstock;
        delete from hkrhead;
        delete from hlead;
        delete from hlqhead;
        delete from hlqstock;        
        delete from hdisciplinary;
        delete from hincidentdtail;
        delete from hincidenthead;
        delete from ophead;
        delete from hophead;
        delete from hopstock;
        delete from hnotice_explain;
        delete from hreturnitemdetail;
        delete from hreturnitemhead;
        delete from hrvoyage;
        delete from htrainingdetail;
        delete from htraininghead;
        delete from htraindev;
        truncate hmsrooms;
        truncate hmscharges;
        truncate hmspackage;
        truncate hmsrates;
        truncate hmsratesetup;
        truncate hms_log;
        truncate hrisnum_log;
        truncate hrisnum_picture;
        truncate holiday;
        truncate holidayloc;
        delete from hplhead;
        update hplstock set qa=0;
        delete from hpahead;
        delete from hpastock;
        truncate hpphead;
        truncate hppstock;
        delete from hplstock;
        delete from hpohead;
        update hpostock set qa = 0;
        delete from hpostock;
        delete from hpchead;
        delete from hpcstock;
        delete from hreplacestock;

        delete from hpostdatedchecks;
        delete from hprhead;
        delete from hprstock;
        delete from hpschemehead;
        delete from hpschemestock;
        delete from hqahead;
        delete from hqastock;

        delete from hoqhead;
        delete from hoqstock;
        delete from hrohead;
        delete from hrostock;
        delete from hrrfams;

        delete from hqshead;
        delete from hqthead;
        update hqtstock set qa=0;
        update hqsstock set qa=0;
        update hqsstock set sjqa=0;
        update hqsstock set poqa=0;
        delete from hqsstock;
        delete from hqtstock;
        delete from hsrstock;
        delete from hsqhead;
        delete from hsrhead;
        delete from hsshead;
        delete from hrppallet;
        delete from hsahead;
        delete from hsbhead;
        delete from hschead;
        delete from hsastock;
        delete from hsbstock;
        delete from hscstock;
        delete from hsghead;
        delete from hsgstock;
  
        delete from hsohead;
        delete from hsostock;
        delete from hspchead;
        delete from hspcstock;
        delete from hstockinfo;
        delete from hstockinfotrans;
        delete from hwahead;
        delete from hwastock;
  
        delete from htrans_asset;
        delete from htrhead;
        delete from htrstock;
        delete from hturnoveritemdetail;
        delete from hturnoveritemhead;

        delete from hmchead;
        delete from heqhead;
        delete from heqstock;
        delete from hoihead;
        delete from htihead;
        delete from hmmhead;
        delete from hmmstock;
        delete from hpshead;
        delete from hdchead;
        delete from hdcdetail;
        delete from hdphead;
        delete from hkahead;
        delete from hckhead;
        delete from hckstock;
        delete from hsistock;

  
        delete from incidentdtail;
        delete from incidenthead;
        delete from iplog;
        truncate ipsetup;
        truncate table item;
        truncate table iteminfo;
        delete from itemamthistory;
        delete from itemdlock;
        delete from itemlevel;
        delete from item_class;
        delete from item_gallery;
        delete from itemcmodels;

        truncate issueitem;
        truncate itemcategory;
        truncate itemsubcategory;
  
        delete from item_log;
        delete from itimages;
  
        delete from jobtdesc;
        delete from jobthead;
        delete from jobtskills;

        delete from journal;
  
        delete from kldetail;
        delete from klhead;
        delete from krhead;
        delete from kahead;
  
        delete from ladetail;
        delete from lahead;
        delete from lastock;
        delete from location;
        delete from lqhead;
        delete from lqstock;
        delete from lphead;
        delete from hlphead;

        truncate layaway;

        truncate loanapplication;
        truncate loginpic;
  
        delete from mrshead;
        delete from masterfile_log;
        delete from model_masterfile;

        delete from mchead;
        delete from mmhead;
        delete from mmstock;

        delete from mrhead;
        delete from mrstock;

        truncate notice_explain;
  
        delete from odr_table_log;
        truncate obapplication;
        truncate otapplication;
        truncate undertime;
        delete from opstock;

        delete from oqhead;
        delete from oqstock;
        delete from oihead;
        truncate othermaster;

        -- delete from paccount;
        delete from pallet;  
        delete from pahead;
        delete from pastock;      
        delete from part_masterfile;
        delete from partrequest;
        delete from paydeletelogs;
        truncate paygroup;
        truncate payroll_log;
        truncate personreq;
        truncate piecetrans;

        -- delete from phictab;
        delete from plhead;
        delete from plstock;
        delete from pohead;
        update postock set qa=0;
        delete from postock;
  
        truncate ppbranch;
        truncate pphead;
        truncate ppstock;

        delete from preemp;
        delete from prhead;
        delete from priceupdate;
  
        delete from projectmasterfile;
        delete from prstock;
        delete from prchange;
        delete from proformainv;
        delete from psbatch;
        delete from pschemehead;
        delete from pschemestock;
        delete from payment;
        truncate paytrancurrent;
        truncate paytranhistory;
        delete from pchead;
        delete from pcstock;
        delete from pqdetail;
        delete from pqhead;
        delete from pricebracket;
        delete from pshead;
        truncate purpose_masterfile;  
        delete from qadetail;
        delete from qahead;
        delete from qastock;
        truncate qnhead;
        truncate qnstock;
        delete from qshead;
        delete from qsstock;
        delete from qthead;    
        delete from qtstock; 
        delete from qtybracket;  
        delete from quohead;
        delete from quostock;
        delete from quotehead;
        delete from quotestock;  
        truncate `rank`;
        delete from replacestock;
        delete from replenishstock;
        truncate reportlog;
        delete from returnitemdetail;
        delete from returnitemhead;
        delete from reschedule;
        truncate reqcategory;
        delete from roomplan;
        truncate rolesetup;
        delete from rohead;
        delete from rostock;
        delete from rrfams;
        delete from rppallet;        
        delete from rqhead;
        delete from rqstock;
        update rrstatus set qa=0,bal=qty;
        delete from rrstatus;
        delete from rthead;
        delete from rtrate;
        delete from rvoyage;
        delete from rghead;
        delete from hrghead;  
        delete from sahead;
        delete from salesgroup;
        delete from sbhead;
        delete from schead;
        delete from sastock;
        delete from sbstock;
        delete from scstock;
        delete from scheddr;
        delete from scheduler_anon;
        delete from scheduler_reminder;
        delete from schedule_logs;
        delete from schedule_notes;
        delete from sched_allowedcustomer;
        delete from sched_alloweduser;
        delete from sched_private;
        delete from sched_projects;
        delete from section;
        delete from setmenu;
        delete from setmenuchoices;  
        delete from series;
        delete from serialin;
        delete from serialout;
        delete from sghead;
        delete from sgstock;
        truncate shiftdetail;
        delete from sistock;
        delete from skillrequire;
        truncate sku;
        delete from sohead;
        update sostock set qa=0;
        delete from sostock;
        delete from spcstock;
        delete from spchead;
        delete from splitqty;
        delete from splitstock;
        delete from spstock;
        delete from sqhead;
        delete from srhead;
        delete from sshead;
        -- delete from ssstab;
  
        truncate standardtrans;
        truncate standardtransadv;
        truncate statchange;

        delete from stock;
        delete from stockgrp_masterfile;
        delete from stockinfo;
        delete from stockinfotrans;

        truncate supplieritem;
        truncate supplierlist;

        delete from table_log;
        delete from htable_log;
  
        -- delete from taxtab;
        delete from tehead;
        delete from tchead;
        delete from tcdetail;
         delete from htchead;
        delete from htcdetail;
        delete from tcoll;
        delete from htehead;
        delete from testock;
        delete from terms;
        delete from terms_log;
        delete from htestock;
        truncate tblroomtype;
        truncate timecard;
        truncate temptimecard;
        truncate timerec;
        truncate timesheet;
        truncate tmshifts;
        delete from tblbnr;
        delete from tblevnt;
        delete from tblpricesetter;
        delete from tp_shippingfees;
        delete from trainingdetail;
        delete from traininghead;
        delete from tihead;
        truncate traindev;

        delete from transnum_log;
        delete from htransnum_log;
        delete from trans_asset;
        delete from turnoveritemdetail;
        delete from turnoveritemhead;

        delete from violation;

        truncate whrem;
  
        delete from uchead;
        delete from ucstock;
        delete from uom;
        delete from uv_principal;
        delete from user_log;
        truncate table user_log;
        delete from userlog;
        delete from useraccess_log;
        truncate table useraccess_log;
        delete from budget;
        delete from fasched;
        delete from hpqdetail;
        delete from hqtstock;
        delete from stages;
        delete from hsvdetail;
        delete from stagesmasterfile;
        delete from annualtax;
        delete from colltype_masterfile;
        delete from hjostock;
        delete from hqthead;
        delete from subproject;
        delete from hpqhead;
        delete from jcstock;
        delete from svdetail;
        delete from billingaddr;
        delete from hjcstock;
        delete from hjohead;
        delete from jchead;
        delete from pmhead;
        delete from blstock;
        delete from hblstock;
        delete from brstock;
        delete from hbrstock;
        delete from hcdhead;
        delete from hcdstock;
        delete from hjchead;
        delete from hsvhead;
        delete from hwcstock;
        delete from johead;
        truncate joboffer;
        delete from leavesetup;
        delete from ratesetup;
        delete from blhead;
        delete from hblhead;
        delete from brhead;
        delete from checksetup;
        delete from del_client_log;
        delete from en_gshead;
        delete from voiddetail;
        delete from hvoiddetail;
        delete from detailinfo;
        delete from hdetailinfo;        
        delete from voidstock;
        delete from hvoidstock;
        delete from en_gssubcomponent;
        delete from hbrhead;
        delete from hjoboffer;
        delete from hpersonreq;
        delete from hwchead;
        delete from jostock;
        delete from leavetrans;
        truncate standardsetup;
        truncate standardsetupadv;
        delete from svhead;
        delete from trhead;
        delete from trstock;
        delete from wahead;
        delete from wastock;
        delete from wchead;
        delete from wcstock;
        delete from pricelist;
        delete from subitems;
        delete from activity;
        delete from psubactivity;
        delete from subactivity;
        delete from hbastock;
        delete from substages;
        delete from hbahead;
        delete from bahead;
        delete from bastock;
        delete from qscalllogs;
        delete from hqscalllogs;
        delete from ppio_series;
        delete from seminar;
        delete from source;
        delete from sqcomments;

        delete from rfhead;
        delete from hrfhead;
        delete from rfstock;
        delete from hrfstock;

        delete from vrhead;
        delete from hvrhead;
        delete from vrstock;
        delete from hvrstock;
        delete from vritems;
        delete from hvritems;
        delete from vrpassenger;
        delete from hvrpassenger;
  
        delete from cntnum;
        truncate table cntnum;
        delete from cntnum_picture;
        truncate cntnumtodo;
  
        delete from transnum;
        truncate table transnum;
        delete from transnum_picture;
        truncate transnumtodo;

        truncate vehiclesched;
  
        truncate hrisnum;

        truncate waims_event;
        truncate waims_holiday;
        truncate waims_notice;

        delete from groupchat;
        delete from temptrans;
        delete from hpdstock;
        delete from hpistock;
        delete from hpihead;
        delete from hpdhead;
        delete from hpiprocess;
        delete from pdstock;
        delete from pdhead;
        delete from piprocess;

        delete from rchead;
        delete from rcdetail;
        delete from hrchead;
        delete from hrcdetail;

        delete from hmrhead;
        delete from hmrstock;

        delete from blklot;
        delete from housemodel;

        delete from dxhead;
        delete from hdxhead;

        delete from tmhead;
        delete from tmdetail;

        truncate whnods;
        truncate whdoc;
        delete from wh_log;
        truncate arservicedetail;
        delete from signatories;

        update profile set yr=0 where doc ='SED';
        delete from profile where psection in ('TRNO','CLIENTID', 'ITEM', 'CLIENT', 'itemID');
        delete from profile where doc='SK' and psection='StartDate';
        delete from profile where doc='CL' and psection='StartDate';
        delete from profile where doc='SL' and psection='StartDate';
        delete from profile where doc='COA' and psection='StartDate';
        delete from profile where psection like '%TRNO%' and doc in ('UP1', 'UP2');
        delete from profile where doc in ('UP1', 'UP2') and psection='DOCTYPE';
        delete from profile where doc='APPLICANTL' and psection='APPLICANTLEDGER';
        delete from profile where doc='EN_INSTRUC' and psection='EN_INSTRUCTOR';

        delete from profile where psection='INVCUTOFF';
        delete from profile where psection='ACCTGCUTOFF';
        delete from timesetup;
        delete from cljobs;

      END";

    $this->coreFunctions->execqry($qry, 'procedure');
  }
}//end class
