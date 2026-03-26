<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class trigger_masterfile
{

	private $coreFunctions;
	private $othersClass;
	private $trigger;

	public function __construct()
	{
		$this->coreFunctions = new coreFunctions;
		$this->othersClass = new othersClass;
	} //end fn

	public function createtriggers_masterfile($config)
	{
		$this->chargesbilling_triggers($config);
		$this->entryplangroup_triggers($config);
		$this->entryplantype_triggers($config);
		$this->entrycostcodes_triggers($config);
		$this->itemgroupqoutasetup_triggers($config);
		$this->salesgroupqouta_triggers($config);
		$this->entrymodel_triggers($config);
		$this->entrystockgroup_triggers($config);
		$this->entrybrand_triggers($config);
		$this->entrycategories_triggers($config);
		$this->entryproject_triggers($config);
		$this->entryterms_triggers($config);
		$this->entryewt_triggers($config);
		$this->entryforex_triggers($config);
		$this->entrynotice_triggers($config);
		$this->entryevent_triggers($config);
		$this->entryholiday_triggers($config);
		$this->entrycompatible_triggers($config);
		$this->entrydeliverytype_triggers($config);
		$this->entryitemcategory_triggers($config);
		$this->entryitemsubcat_triggers($config);
		$this->entryuomlist_triggers($config);
		$this->entrywhrem_triggers($config);
		$this->paygroup_triggers($config);
		$this->empstatusmaster_triggers($config);
		$this->statchangemaster_triggers($config);
		$this->generationmaster_triggers($config);
		$this->regularizationprocess_triggers($config);
		$this->skillreqmaster_triggers($config);
		$this->empreqmaster_triggers($config);
		$this->preemptest_triggers($config);
		$this->division_triggers($config);
		$this->section_triggers($config);
		$this->annualtax_triggers($config);
		$this->philhealth_triggers($config);
		$this->sss_triggers($config);
		$this->pagibig_triggers($config);
		$this->tax_triggers($config);
		$this->holiday_triggers($config);
		$this->holidayloc_triggers($config);
		$this->payrollaccounts_triggers($config);
		$this->leavesetup_triggers($config);
		$this->rank_triggers($config);
		$this->entryrole_triggers($config);
		$this->contactperson_tab_triggers($config);
		$this->billing_add_tab_triggers($config);
		$this->skuentry_tab_triggers($config);
		$this->entryuom_tab_triggers($config);
		$this->entrycomponent_tab_triggers($config);
		$this->empprojdetail_triggers($config);

		$this->entrypowercat_triggers($config);
		$this->entrysubpowercat_triggers($config);
		$this->entrysubpowercat2_triggers($config);

		$this->applicant_triggers($config);
		$this->app_contacts_triggers($config);
		$this->app_dependents_triggers($config);
		$this->app_education_triggers($config);
		$this->app_employment_triggers($config);
		$this->app_req_triggers($config);
		$this->app_emptest_triggers($config);
		// $this->employee_triggers($config);
		$this->emp_dependents_triggers($config);
		// $this->emp_education_triggers($config);
		// $this->emp_employment_triggers($config);
		// $this->emp_contract_triggers($config);
		$this->emp_rolesetup_triggers($config);
		$this->earningdeductionsetup_triggers($config);
		$this->earningdeductionsetup_manual_payment_triggers($config);
		$this->advancesetup_triggers($config);
		$this->entryadvancepayment_manual_payment_triggers($config);
		$this->leaveapplication_triggers($config);
		$this->obapplication_triggers($config);
		$this->loanapplicationportal_triggers($config);
		$this->tmshifts_triggers($config);
		$this->shiftdetail_triggers($config);

		// CRM
		$this->salesgroup_triggers($config);
		$this->seminar_triggers($config);
		$this->exhibit_triggers($config);
		$this->source_triggers($config);
		$this->checksetup_triggers($config);
		$this->exchangerate_triggers($config);
		$this->entryattendee_triggers($config);
		// $this->sqcomments_triggers($config);

		// Accounting
		$this->entryempbudget_triggers($config);
		// Room Management
		$this->ratecodesetup_triggers($config);
		$this->othercharges_triggers($config);
		$this->packagesetup_triggers($config);

		$this->roomtype_triggers($config);
		$this->entryroomlist($config);

		// Project Setup
		$this->entrystages_triggers($config);
		// Document Management
		$this->dt_details_triggers($config);



		// School Setup
		$this->en_levels_triggers($config);
		$this->en_semester_triggers($config);
		$this->en_roomlist_triggers($config);
		$this->entryroom($config);
		$this->en_course_triggers($config);
		$this->entrysection($config);
		// $this->en_transferee_requirements_triggers($config);
		$this->en_schoolyear_triggers($config);
		$this->en_period_triggers($config);
		$this->en_fees_triggers($config);
		$this->en_credentials_triggers($config);
		$this->en_modeofpayment_triggers($config);
		$this->en_quartersetup_triggers($config);
		$this->en_honorrollcriteria_triggers($config);
		$this->en_gradecomponent_triggers($config);
		$this->en_gradeequivalent_triggers($config);
		$this->en_attendancetype_triggers($config);
		$this->en_conductgrade_triggers($config);
		$this->en_cardremarks_triggers($config);
		$this->en_attendancesetup_triggers($config);
		$this->en_subject_triggers($config);
		$this->entry_subject_triggers($config);
		$this->dt_issues_triggers($config);
		$this->dt_industry_triggers($config);
		$this->dt_documenttype_triggers($config);
		$this->dt_division_triggers($config);
		$this->dt_status_triggers($config);
		$this->dt_statusaccess_triggers($config);

		$this->jobtitlemaster_triggers($config);
		$this->jobtitlemaster_detail_triggers($config);
		$this->codeconduct_triggers($config);
		$this->codeconduct_detail_triggers($config);
		$this->en_requirements_triggers($config);
		$this->en_gradeequivalentletters_triggers($config);

		$this->poterms_triggers($config);
		$this->purpose_triggers($config);



		// MMS
		$this->entryphase_triggers($config);
		$this->entrysection_triggers($config);
		$this->entryratecat_triggers($config);
		$this->loc_mms_trigger($config);
		$this->entryescalation_tab_trigger($config);
		$this->billableitemssetup_triggers($config);

		// POS
		$this->branchbank_tab_trigger($config);
		$this->bankcharges_tab_trigger($config);

		//POS
		$this->cardtypes_triggers($config);
		$this->paymenttype_triggers($config);

		// maxipro project
		$this->entryprojectstages_triggers($config);
		$this->addsubstages_triggers($config);
		$this->addsubactivity_triggers($config);
		$this->addsubitems_triggers($config);
		$this->entrysubprojectactivity_triggers($config);

		// employee - tab itemgroup
		$this->entryitemgroup_triggers($config);
		$this->entryduration_triggers($config);
		// $this->entrycalllog($config);

		// $this->entryvrpassenger($config);
		// $this->entryvritems($config);

		// $this->entrygeneralitem($config);

		$this->qtybracket_triggers($config);
		$this->stockprice_triggers($config);
		$this->customersa_tab_trigger($config);
		$this->reqcategory_triggers($config);
		$this->entryagentquota_triggers($config);

		//POS
		$this->pricelist_triggers($config);
		$this->commissionlist_triggers($config);
		$this->supplierlist_triggers($config);

		//household
		$this->householdcontacts_triggers($config);
		//employee timecard viewallapp 
		$this->all_applications($config);

		$this->certrate_triggers($config);
		$this->entrytask_triggers($config);
		$this->tmhead_triggers($config);
		$this->moduleapproval_triggers($config);
		$this->timesetup_triggers($config);
		$this->entrydailytask_triggers($config);

		$this->branchjoblist_triggers($config);
		$this->allowancesetup_triggers($config);
		$this->locclearance_triggers($config);
	}

	private function settriggermasterfilelogs($config, $doc, $tablename, $table_log, $data = [], $keys, $keys2 = '', $label = '', $fieldlabel = '', $trno2 = "")
	{
		// delete triggers
		$triggername = $doc . '_update';
		$this->coreFunctions->sbcdroptriggers($triggername);

		$str = '';
		$doc = strtoupper($doc);
		$user = $config['params']['user'];

		// para sa trno ng modules yun yung mag insert sa logs
		$newkey = $keys2 != "" ? $keys2 : $keys;

		// more on label nung field para readable sa logs kung anong field
		// for developer's hint (ex: LINE) what line sya naedit
		$newlabel = $label != "" ? $label : $keys;
		$fieldlabel = $fieldlabel != "" ? $fieldlabel : $keys;

		// skuentry_tab_triggers yung sample nito
		// para ito sa nareuse na tab tapos magka iba yung naeedit na clientid at itemid
		// kaya nag add nalang ng new field para maseperate yung naiinsert sa tablelogs
		$addedcol = "";
		$addedfield = "";
		if ($trno2 != "") {
			$addedcol = ", trno2";
			$addedfield = ", OLD.$trno2";
		}

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				foreach ($value as $col => $value2) {

					if (isset($value2[0]) && $value2[0] == true) {

						$str .= "
		            if OLD.$col<>NEW.$col then
			            insert into $table_log(trno" . $addedcol . ",doc,task,dateid,user) 
			            values (OLD.$newkey" . $addedfield . ",'$doc',concat('UPDATE : " . strtoupper($key) . " at " . strtoupper($newlabel) . " : ',OLD.$fieldlabel, ' - ',
			            ifnull((select " . $value2[1] . " from " . $value2[2] . " WHERE " . $value2[3] . " = OLD.$col),''), ' => ',
			            ifnull((select " . $value2[1] . " FROM " . $value2[2] . " WHERE " . $value2[3] . " = NEW.$col),'')),
			           	NEW.editdate, New.editby);
		            end if;
		          ";
					} else {

						$str .= "
		            if OLD.$col<>NEW.$col then
			            insert into $table_log(trno" . $addedcol . ",doc,task,dateid,user) 
			            values (OLD.$newkey" . $addedfield . ",'$doc',concat('UPDATE : " . strtoupper($key) . " at " . strtoupper($newlabel) . " : ',OLD.$fieldlabel, ' - ', 
			            OLD.$col,' => ',NEW.$col),NEW.editdate, New.editby);
		            end if;
		          ";
					}
				}
			} // end foreach
		} // end if

		$qry = "create TRIGGER $triggername AFTER UPDATE on $tablename FOR EACH ROW
		        BEGIN
		        " . $str . "
		        END";

		$this->coreFunctions->execqry($qry, 'trigger');
	} // end fn

	// MASTERFILE LOGS
	private function entrymodel_triggers($config)
	{
		$fields = [
			'Model Code' => ['model_code' => []],
			'Model Name' => ['model_name' => []]
		];

		$this->settriggermasterfilelogs($config, 'entrymodel', 'model_masterfile', 'masterfile_log', $fields, 'model_id');
	}

	private function entrystockgroup_triggers($config)
	{
		$fields = [
			'Group Code' => ['stockgrp_code' => []],
			'Group Name' => ['stockgrp_name' => []]
		];

		$this->settriggermasterfilelogs($config, 'stockgrp_masterfile', 'masterfile_log', $fields, 'stockgrp_id', '');
	}

	private function entrybrand_triggers($config)
	{
		$fields = [
			'Brand Name' => ['brand_desc' => []]
		];

		$this->settriggermasterfilelogs($config, 'entrybrand', 'frontend_ebrands', 'masterfile_log', $fields, 'brandid');
	}

	private function entrycategories_triggers($config)
	{
		$fields = [
			'Category Name' => ['cat_name' => []]
		];

		$this->settriggermasterfilelogs($config, 'entrycategories', 'category_masterfile', 'masterfile_log', $fields, 'cat_id');
	}

	private function entryproject_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Name' => ['name' => []],
			'Is Head Office' => ['isho' => []],
			'Agent' => ['agentid' => [true, "clientname", "client", "clientid"]],
			'Rate' => ['comrate' => []],
			'Asset' => ['assetid' => [true, "acnoname", "coa", "acnoid"]],
			'Liability' => ['liabilityid' => [true, "acnoname", "coa", "acnoid"]],
			'Revenue' => ['revenueid' => [true, "acnoname", "coa", "acnoid"]],
			'Expense' => ['expenseid' => [true, "acnoname", "coa", "acnoid"]],
		];

		$this->settriggermasterfilelogs($config, 'entryproject', 'projectmasterfile', 'masterfile_log', $fields, 'line');
	}

	private function entryterms_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Terms' => ['terms' => []],
			'Days' => ['days' => []],
			'With DP' => ['isdp' => []],
			'Admin Only' => ['isnotallow' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryterms', 'terms', 'masterfile_log', $fields, 'line');
	}

	private function entryewt_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Code' => ['code' => []],
			'Rate' => ['rate' => []],
			'Description' => ['description' => []]
		];

		$this->settriggermasterfilelogs($config, 'entryewt', 'ewtlist', 'masterfile_log', $fields, 'line');
	}

	private function entryforex_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Currency' => ['cur' => []],
			'Currency to Peso' => ['curtopeso' => []],
			'Dollar to Currency' => ['dollartocur' => []]
		];

		$this->settriggermasterfilelogs($config, 'entryforex', 'forex_masterfile', 'masterfile_log', $fields, 'line');
	}

	private function entrynotice_triggers($config)
	{
		$fields = [
			'Dateid' => ['dateid' => []],
			'Title' => ['title' => []],
			'Remarks' => ['rem' => []]
		];

		$this->settriggermasterfilelogs($config, 'entrynotice', 'waims_notice', 'masterfile_log', $fields, 'line');
	}

	private function entryevent_triggers($config)
	{
		$fields = [
			'Date Start' => ['datestart' => []],
			'Date End' => ['dateend' => []],
			'Title' => ['title' => []],
			'Remarks' => ['rem' => []],
			'Color' => ['color' => []]
		];

		$this->settriggermasterfilelogs($config, 'entryevent', 'waims_event', 'masterfile_log', $fields, 'line');
	}

	private function entryholiday_triggers($config)
	{
		$fields = [
			'Date Start' => ['datestart' => []],
			'Date End' => ['dateend' => []],
			'Title' => ['title' => []],
			'Remarks' => ['rem' => []],
			'Color' => ['color' => []]
		];

		$this->settriggermasterfilelogs($config, 'entryholiday', 'waims_holiday', 'masterfile_log', $fields, 'line');
	}

	private function entrycompatible_triggers($config)
	{
		$fields = [
			'brand' => ['brand' => []],
			'model' => ['model' => []],
			'classification' => ['classification' => []],
		];

		$this->settriggermasterfilelogs($config, 'entrycompatible', 'cmodels', 'masterfile_log', $fields, 'line');
	}

	private function entrycheckerlocation_triggers($config)
	{
		$fields = [
			'Name' => ['name' => []],
		];

		$this->settriggermasterfilelogs($config, 'entrycheckerlocation', 'checkerloc', 'masterfile_log', $fields, 'line');
	}

	private function entrydeliverytype_triggers($config)
	{
		$fields = [
			'Name' => ['name' => []],
		];

		$this->settriggermasterfilelogs($config, 'entrydeliverytype', 'deliverytype', 'masterfile_log', $fields, 'line');
	}

	private function entryitemcategory_triggers($config)
	{
		$fields = [
			'Name' => ['name' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryitemcategory', 'itemcategory', 'masterfile_log', $fields, 'line');
	}

	private function entryitemsubcat_triggers($config)
	{
		$fields = [
			'Name' => ['name' => []],
			'categoryid' => ['categoryid' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryitemsubcategory', 'itemsubcategory', 'masterfile_log', $fields, 'line');
	}

	private function entrywhrem_triggers($config)
	{
		$fields = [
			'Remarks' => ['rem' => []],
			'For Return/Adjustment' => ['forreturn' => []],
		];

		$this->settriggermasterfilelogs($config, 'entrywhrem', 'whrem', 'masterfile_log', $fields, 'line');
	}

	private function paygroup_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Name' => ['paygroup' => []],
		];

		$this->settriggermasterfilelogs($config, 'paygroup', 'paygroup', 'masterfile_log', $fields, 'line');
	}

	private function empstatusmaster_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Name' => ['empstatus' => []],
		];

		$this->settriggermasterfilelogs($config, 'empstatusmaster', 'empstatentry', 'masterfile_log', $fields, 'line');
	}

	private function statchangemaster_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Name' => ['stat' => []],
		];

		$this->settriggermasterfilelogs($config, 'statchangemaster', 'statchange', 'masterfile_log', $fields, 'line');
	}

	private function generationmaster_triggers($config)
	{
		$fields = [
			'Generation' => ['generation' => []],
			'Start Year' => ['startyear' => []],
			'End Year' => ['endyear' => []],
		];

		$this->settriggermasterfilelogs($config, 'generationmaster', 'generation', 'masterfile_log', $fields, 'line');
	}

	private function regularizationprocess_triggers($config)
	{
		$fields = [
			'Description' => ['description' => []],
			'Days' => ['num' => []],
			'Sortline' => ['sortline' => []],
		];

		$this->settriggermasterfilelogs($config, 'regularizationprocess', 'regularization', 'masterfile_log', $fields, 'line');
	}


	private function skillreqmaster_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Name' => ['skill' => []],
		];

		$this->settriggermasterfilelogs($config, 'skillreqmaster', 'skillrequire', 'masterfile_log', $fields, 'line');
	}

	private function empreqmaster_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Name' => ['req' => []],
		];

		$this->settriggermasterfilelogs($config, 'empreqmaster', 'emprequire', 'masterfile_log', $fields, 'line');
	}

	private function preemptest_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Name' => ['test' => []],
		];

		$this->settriggermasterfilelogs($config, 'preemptest', 'preemp', 'masterfile_log', $fields, 'line');
	}

	private function division_triggers($config)
	{
		$fields = [
			'Code' => ['divcode' => []],
			'Name' => ['divname' => []],
		];

		$this->settriggermasterfilelogs($config, 'division', 'division', 'masterfile_log', $fields, 'divid');
	}

	private function rank_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Name' => ['rank' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryrank', 'rank', 'masterfile_log', $fields, 'line');
	}

	private function section_triggers($config)
	{
		$fields = [
			'Code' => ['sectcode' => []],
			'Name' => ['sectname' => []],
		];

		$this->settriggermasterfilelogs($config, 'section', 'section', 'masterfile_log', $fields, 'sectid');
	}

	private function annualtax_triggers($config)
	{
		$fields = [
			'Bracket' => ['bracket' => []],
			'Range 1' => ['range1' => []],
			'Range 2' => ['range2' => []],
			'Amt' => ['amt' => []],
			'Percentage' => ['percentage' => []],
		];

		$this->settriggermasterfilelogs($config, 'annualtax', 'annualtax', 'masterfile_log', $fields, 'line');
	}

	private function philhealth_triggers($config)
	{
		$fields = [
			'Bracket' => ['bracket' => []],
			'Range 1' => ['range1' => []],
			'Range 2' => ['range2' => []],
			'Phic ee' => ['phicee' => []],
			'Phic er' => ['phicer' => []],
			'Multiplier' => ['phictotal' => []],
		];

		$this->settriggermasterfilelogs($config, 'philhealth', 'phictab', 'masterfile_log', $fields, 'line');
	}

	private function sss_triggers($config)
	{
		$fields = [
			'Bracket' => ['bracket' => []],
			'Range 1' => ['range1' => []],
			'Range 2' => ['range2' => []],
			'SSS ee' => ['sssee' => []],
			'SSS er' => ['ssser' => []],
			'SSS ec' => ['eccer' => []],
			'MPF EE' => ['mpfee' => []],
			'MPF ER' => ['mpfer' => []],
			'Total' => ['ssstotal' => []],
		];

		$this->settriggermasterfilelogs($config, 'sss', 'ssstab', 'masterfile_log', $fields, 'line');
	}

	private function pagibig_triggers($config)
	{
		$fields = [
			'Bracket' => ['bracket' => []],
			'Range 1' => ['range1' => []],
			'Range 2' => ['range2' => []],
			'Multiplier' => ['hdmfmulti' => []],
		];

		$this->settriggermasterfilelogs($config, 'pagibig', 'hdmftab', 'masterfile_log', $fields, 'line');
	}

	private function tax_triggers($config)
	{
		$fields = [
			'Paymode' => ['paymode' => []],
			'TEU' => ['teu' => []],
			'Dependents' => ['depnum' => []],
			'Tax 1' => ['tax01' => []],
			'Tax 2' => ['tax02' => []],
			'Tax 3' => ['tax03' => []],
			'Tax 4' => ['tax04' => []],
			'Tax 5' => ['tax05' => []],
			'Tax 6' => ['tax06' => []],
		];

		$this->settriggermasterfilelogs($config, 'tax', 'taxtab', 'masterfile_log', $fields, 'line');
	}

	private function holiday_triggers($config)
	{
		$fields = [
			'Date' => ['dateid' => []],
			'Description' => ['description' => []],
			'Day Type' => ['daytype' => []],
		];

		$this->settriggermasterfilelogs($config, 'holiday', 'holiday', 'masterfile_log', $fields, 'line');
	}


	private function holidayloc_triggers($config)
	{
		$fields = [
			'Date' => ['dateid' => []],
			'Description' => ['description' => []],
			'Day Type' => ['daytype' => []],
			'Location' => ['location' => []],
		];

		$this->settriggermasterfilelogs($config, 'holidayloc', 'holidayloc', 'masterfile_log', $fields, 'line');
	}

	private function payrollaccounts_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Code Name' => ['codename' => []],
			'Alias' => ['alias' => []],
			'Alias2' => ['alias2' => []],
			'Type' => ['type' => []],
			'Uom' => ['uom' => []],
			'Seq' => ['seq' => []],
			'Multiplier' => ['qty' => []],
			'Taxable' => ['istax' => []],
			'Payroll' => ['ispayroll' => []],
		];

		$this->settriggermasterfilelogs($config, 'payrollaccounts', 'paccount', 'masterfile_log', $fields, 'line');
	}

	private function leavesetup_triggers($config)
	{
		$fields = [
			'Document No' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Remarks' => ['remarks' => []],
			'Employee' => ['empid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Period From' => ['prdstart' => []],
			'Period To' => ['prdend' => []],
			'Entitled' => ['days' => []],
			'Balance' => ['bal' => []],
			'Account' => ['acnoid' => [true, "concat(code, '-', codename)", 'paccount', 'line']],
			'No Pay' => ['isnopay' => []],
			'Convertible' => ['isconvert' => []],
			'balance' => ['bal' => []],
		];

		$this->settriggermasterfilelogs($config, 'leavesetup', 'leavesetup', 'masterfile_log', $fields, 'trno');
	}

	private function applicant_triggers($config)
	{
		$fields = [
			'empcode' => ['empcode' => []],
			'fname' => ['empfirst' => []],
			'mname' => ['empmiddle' => []],
			'lname' => ['emplast' => []],
			'portfolio status' => ['jstatus' => []],
			'jobcode' => ['jobcode' => []],
			'jobtitle' => ['jobtitle' => []],
			'job_desc' => ['jobdesc' => []],
			'applied_date' => ['appdate' => []],
			'address' => ['address' => []],
			'city' => ['city' => []],
			'country' => ['country' => []],
			'zipcode' => ['zipcode' => []],
			'telno' => ['telno' => []],
			'mobileno' => ['mobileno' => []],
			'email' => ['email' => []],
			'citizenship' => ['citizenship' => []],
			'religion' => ['religion' => []],
			'alias' => ['alias' => []],
			'bday' => ['bday' => []],
			'jobcode' => ['jobcode' => []],
			'maiden_name' => ['maidname' => []],
			'remarks' => ['remarks' => []],
			'type' => ['type' => []],
			'application_mode' => ['mapp' => []],
			'birthplace' => ['bplace' => []],
			'child' => ['child' => []],
			'status' => ['status' => []],
			'gender' => ['gender' => []],
			'ishired' => ['ishired' => []],
			'idno' => ['idno' => []],
			'Branch' => ['branchid' => [true, "clientname", 'client', 'clientid']],
			// 'contact1'=> ['empid' => [true, "contact1","acontacts","empid"]],
		];

		$this->settriggermasterfilelogs($config, 'applicantledger', 'app', 'masterfile_log', $fields, 'empid');
	}

	private function app_contacts_triggers($config)
	{
		$fields = [
			'empid' => ['empid' => []],
			'contact1' => ['contact1' => []],
			'relation1' => ['relation1' => []],
			'addr1' => ['addr1' => []],
			'homeno1' => ['homeno1' => []],
			'mobileno1' => ['mobileno1' => []],
			'officeno1' => ['officeno1' => []],
			'ext1' => ['ext1' => []],
			'notes1' => ['notes1' => []],
			'contact2' => ['contact2' => []],
			'relation2' => ['relation2' => []],
			'addr2' => ['addr2' => []],
			'homeno2' => ['homeno2' => []],
			'mobileno2' => ['mobileno2' => []],
			'officeno2' => ['officeno2' => []],
			'ext2' => ['ext2' => []],
			'notes2' => ['notes2' => []],
		];
		$this->settriggermasterfilelogs($config, 'applicantledger_acontacts', 'acontacts', 'masterfile_log', $fields, 'empid');
	}

	private function app_dependents_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'name' => ['name' => []],
			'relations' => ['relation' => []],
			'bday' => ['bday' => []],
		];

		$this->settriggermasterfilelogs($config, 'app_dependents', 'adependents', 'masterfile_log', $fields, 'line', 'empid');
	}

	private function app_education_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'school' => ['school' => []],
			'address' => ['address' => []],
			'course' => ['course' => []],
			'sy' => ['sy' => []],
			'gpa' => ['gpa' => []],
			'honor' => ['honor' => []],
		];

		$this->settriggermasterfilelogs($config, 'app_education', 'aeducation', 'masterfile_log', $fields, 'line', 'empid');
	}

	private function app_employment_triggers($config)
	{
		// $this->coreFunctions->sbcdroptriggers('app_employement_update');

		$fields = [
			'line' => ['line' => []],
			'company' => ['company' => []],
			'address' => ['address' => []],
			'jobtitle' => ['jobtitle' => []],
			'salary' => ['salary' => []],
			'period' => ['period' => []],
			'reason' => ['reason' => []],
		];


		$this->settriggermasterfilelogs($config, 'app_employment', 'aemployment', 'masterfile_log', $fields, 'line', 'empid');
	}

	private function app_req_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'submitdate' => ['submitdate' => []],
			'notes' => ['notes' => []],
		];

		$this->settriggermasterfilelogs($config, 'app_req', 'arequire', 'masterfile_log', $fields, 'line', 'empid');
	}

	private function app_emptest_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'result' => ['result' => []],
			'notes' => ['notes' => []],
		];

		$this->settriggermasterfilelogs($config, 'app_emptest', 'apreemploy', 'masterfile_log', $fields, 'line', 'empid');
	}

	// private function employee_triggers($config) {
	// 	$fields = [
	// 		'empid' => ['empid' => []],
	// 		'empfirst' => ['empfirst' => []],
	// 		'empmiddle' => ['empmiddle' => []],
	// 		'emplast' => ['emplast' => []],
	// 		'role' => ['roleid' => [true, "name", "rolesetup", "line"]],
	// 		'dept' => ['deptid' => [true, "clientname", "client", "clientid"]],
	// 		'section' => ['sectid' => [true, "sectname", "section", "sectid"]],
	// 		'division' => ['divid' => [true, "divname", "division", "divid"]],
	// 		'shiftcode' => ['shiftid' => [true, "shftcode", "tmshifts", "line"]],
	// 		'jobtitle' => ['jobid' => [true, "jobtitle", "jobthead", "line"]],
	// 	];

	// 	$this->settriggermasterfilelogs($config,'employee', 'employee', 'client_log', $fields, 'empid');
	// }

	private function emp_dependents_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'name' => ['name' => []],
			'relations' => ['relation' => []],
			'bday' => ['bday' => []],
		];

		$this->settriggermasterfilelogs($config, 'emp_dependents', 'dependents', 'masterfile_log', $fields, 'line');
	}

	// private function emp_education_triggers($config)
	// {
	// $fields = [
	// 	'line' => ['line' => []],
	// 	'school' => ['school' => []],
	// 	'address' => ['address' => []],
	// 	'course' => ['course' => []],
	// 	'sy' => ['sy' => []],
	// 	'gpa' => ['gpa' => []],
	// 	'honor' => ['honor' => []],
	// ];

	// $this->settriggermasterfilelogs($config, 'emp_education', 'education', 'client_log', $fields, 'line', '', '','empid');
	// }

	// private function emp_employment_triggers($config)
	// {
	// 	$fields = [
	// 		'line' => ['line' => []],
	// 		'company' => ['company' => []],
	// 		'address' => ['address' => []],
	// 		'jobtitle' => ['jobtitle' => []],
	// 		'salary' => ['salary' => []],
	// 		'period' => ['period' => []],
	// 		'reason' => ['reason' => []],
	// 	];

	// 	$this->settriggermasterfilelogs($config, 'emp_employment', 'employment', 'payroll_log', $fields, 'line');
	// }

	// private function emp_contract_triggers($config)
	// {
	// 	$fields = [
	// 		'line' => ['line' => []],
	// 		'contract_no' => ['contractn' => []],
	// 		'description' => ['descr' => []],
	// 		'from' => ['datefrom' => []],
	// 		'to' => ['dateto' => []],
	// 	];

	// 	$this->settriggermasterfilelogs($config, 'emp_contract', 'contracts', 'payroll_log', $fields, 'line');
	// }

	private function emp_rolesetup_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'role' => ['roleid' => [true, "name", "rolesetup", "line"]],
		];

		$this->settriggermasterfilelogs($config, 'emp_rolesetup', 'emprole', 'payroll_log', $fields, 'line');
	}

	private function earningdeductionsetup_triggers($config)
	{
		$fields = [
			'trno' => ['trno' => []],
			'Employee' => ['empid' => [true, "clientname", "client", "clientid"]],
			'account' => ['acnoid' => [true, "acnoname", "coa", "acnoid"]],
			'amt' => ['amt' => []],
			'amortization' => ['amortization' => []],
			'balance' => ['balance' => []],
			'effectivity' => ['effdate' => []],
			'remarks' => ['remarks' => []],
			'week1' => ['w1' => []],
			'week2' => ['w2' => []],
			'week3' => ['w3' => []],
			'week4' => ['w4' => []],
			'week5' => ['w5' => []],
			'13th' => ['w13' => []],
			'void' => ['halt' => []],
			'priority' => ['priority' => []],
			'Temp Amount' => ['camt' => []]
		];

		$this->settriggermasterfilelogs($config, 'earningdeductionsetup', 'standardsetup', 'payroll_log', $fields, 'trno');
	}

	private function earningdeductionsetup_manual_payment_triggers($config)
	{
		$fields = [
			'trno' => ['trno' => []],
			'Manual Payment Docno' => ['docno' => []],
			'Manual Payment date' => ['dateid' => []],
			'Manual Payment payment' => ['cr' => []],
		];

		$this->settriggermasterfilelogs($config, 'earningdeductionsetup_manualpayment', 'standardtrans', 'payroll_log', $fields, 'trno');
	}

	private function advancesetup_triggers($config)
	{
		$fields = [
			'trno' => ['trno' => []],
			'Employee' => ['empid' => [true, "clientname", "client", "clientid"]],
			'account' => ['acnoid' => [true, "acnoname", "coa", "acnoid"]],
			'amt' => ['amt' => []],
			'amortization' => ['amortization' => []],
			'balance' => ['balance' => []],
			'effectivity' => ['effdate' => []],
			'remarks' => ['remarks' => []],
			'week1' => ['w1' => []],
			'week2' => ['w2' => []],
			'week3' => ['w3' => []],
			'week4' => ['w4' => []],
			'week5' => ['w5' => []],
			'13th' => ['w13' => []],
			'void' => ['halt' => []],
			'priority' => ['priority' => []],
			'Temp Amount' => ['camt' => []]
		];

		$this->settriggermasterfilelogs($config, 'advancesetup', 'standardsetupadv', 'payroll_log', $fields, 'trno');
	}

	private function entryadvancepayment_manual_payment_triggers($config)
	{
		$fields = [
			'trno' => ['trno' => []],
			'Manual Payment Docno' => ['docno' => []],
			'Manual Payment date' => ['dateid' => []],
			'Manual Payment payment' => ['cr' => []],
		];

		$this->settriggermasterfilelogs($config, 'advancesetup_manualpayment', 'standardtransadv', 'payroll_log', $fields, 'trno');
	}

	private function leaveapplication_triggers($config)
	{
		$fields = [
			'trno' => ['trno' => []],
			'details status' => ['status' => []]
		];

		$this->settriggermasterfilelogs($config, 'leaveapplication', 'leavetrans', 'payroll_log', $fields, 'trno', '', 'LINE', 'line');
	}

	private function obapplication_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'date' => ['dateid' => []],
			'time' => ['dateid' => [true, "TIME(dateid) as itime", "obapplication", "line"]],
			'type' => ['type' => []],
			'status' => ['status' => []],
			'notes' => ['rem' => []],
		];

		$this->settriggermasterfilelogs($config, 'obapplication', 'obapplication', 'payroll_log', $fields, 'line');
	}

	private function loanapplicationportal_triggers($config)
	{
		$fields = [
			'trno' => ['trno' => []],
			'Employee' => ['empid' => [true, "clientname", "client", "clientid"]],
			'amt' => ['amt' => []],
			'amortization' => ['amortization' => []],
			'balance' => ['balance' => []],
			'effectivity' => ['effdate' => []],
			'termfrom' => ['termfrom' => []],
			'termto' => ['termto' => []],
			'payrolldate' => ['payrolldate' => []],
			'w1' => ['w1' => []],
			'w2' => ['w2' => []],
			'w3' => ['w3' => []],
			'w4' => ['w4' => []],
			'w5' => ['w5' => []],
			'remarks' => ['remarks' => []],
			'status' => ['status' => []],
		];

		$this->settriggermasterfilelogs($config, 'loanapplicationportal', 'loanapplication', 'payroll_log', $fields, 'trno');
	}

	private function tmshifts_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'CODE' => ['shftcode' => []],
			'SCHED IN' => ['tschedin' => []],
			'SCHED OUT' => ['tschedout' => []],
			'GRACE PERIOD' => ['gtin' => []],
			'GRACE PERIOD OT' => ['gbrkin' => []],
			'NDIFF FROM' => ['ndifffrom' => []],
			'NDIFF TO' => ['ndiffto' => []],
			'ONE LOG ONLY' => ['isonelog' => []],
			'FLEXIBLE TIME' => ['flexit' => []]
		];

		$this->settriggermasterfilelogs($config, 'shiftsetup', 'tmshifts', 'payroll_log', $fields, 'line');
	}

	private function shiftdetail_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'SCHED IN' => ['schedin' => []],
			'SCHED OUT' => ['schedout' => []],
			'BREAK IN' => ['breakin' => []],
			'BREAK OUT' => ['breakout' => []],
			'TOTAL HRS' => ['tothrs' => []],
		];

		$this->settriggermasterfilelogs($config, 'shiftsetup_detail', 'shiftdetail', 'payroll_log', $fields, 'shiftsid');
	}


	// CRM
	private function salesgroup_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'groupname' => ['groupname' => []],
			'leader' => ['leader' => []],
		];

		$this->settriggermasterfilelogs($config, 'salesgroup', 'salesgroup', 'masterfile_log', $fields, 'line');
	}

	private function seminar_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'title' => ['title' => []],
			'description' => ['description' => []],
			'date' => ['dateid' => []],
			'product' => ['product' => []],
			'location' => ['location' => []],
			'presenter' => ['presenter' => []],
		];

		$this->settriggermasterfilelogs($config, 'seminar', 'seminar', 'masterfile_log', $fields, 'line');
	}

	private function source_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'title' => ['title' => []],
			'description' => ['description' => []],
			'date' => ['dateid' => []],
		];

		$this->settriggermasterfilelogs($config, 'source', 'source', 'masterfile_log', $fields, 'line');
	}

	private function exhibit_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'title' => ['title' => []],
			'description' => ['description' => []],
			'startdate' => ['startdate' => []],
			'enddate' => ['enddate' => []],
			'product' => ['product' => []],
			'location' => ['location' => []],
		];

		$this->settriggermasterfilelogs($config, 'exhibit', 'exhibit', 'masterfile_log', $fields, 'line');
	}

	private function checksetup_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'account' => ['acnoid' => [true, "concat(acno, '-', acnoname)", "coa", "acnoid"]],
			'start' => ['start' => []],
			'end' => ['end' => []],
			'current' => ['current' => []],
		];

		$this->settriggermasterfilelogs($config, 'entrychecksetup', 'checksetup', 'masterfile_log', $fields, 'line');
	}

	private function exchangerate_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'curfrom' => ['curfrom' => []],
			'curto' => ['curto' => []],
			'rate' => ['rate' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryexchangerate', 'exchangerate', 'masterfile_log', $fields, 'line');
	}

	private function entryattendee_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'client status' => ['clientstatus' => []],
			'companyname' => ['companyname' => []],
			'contactname' => ['contactname' => []],
			'contactno' => ['contactno' => []],
			'department' => ['department' => []],
			'designation' => ['designation' => []],
			'email' => ['email' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryattendee', 'attendee', 'masterfile_log', $fields, 'line', 'exhibitid');
	}

	private function sqcomments_triggers($config)
	{
		// $fields = [
		// 	'line' => ['line' => []],
		// 	'comment' => ['comment' => []],
		// ];

		// $this->settriggermasterfilelogs($config, 'entrysqcomment', 'sqcomments', 'masterfile_log', $fields, 'line', 'trno');
	}


	private function entryempbudget_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'year' => ['year' => []],
			'clientid' => ['clientid' => []],
			'branchid' => ['branchid' => []],
			'deptid' => ['deptid' => []],
			'projectid' => ['projectid' => []],
			'acnoid' => ['acnoid' => []],
			'janamt' => ['janamt' => []],
			'febamt' => ['febamt' => []],
			'maramt' => ['maramt' => []],
			'apramt' => ['apramt' => []],
			'mayamt' => ['mayamt' => []],
			'junamt' => ['junamt' => []],

			'julamt' => ['julamt' => []],
			'augamt' => ['augamt' => []],
			'sepamt' => ['sepamt' => []],
			'octamt' => ['octamt' => []],
			'novamt' => ['novamt' => []],
			'decamt' => ['decamt' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryempbudget', 'empbudget', 'masterfile_log', $fields, 'line');
	}


	private function entryrole_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'Role Name' => ['name' => []],
			'Division' => ['divid' => [true, "divname", "division", "divid"]],
			'Department' => ['deptid' => [true, "clientname", "client", "clientid"]],
			'Section' => ['sectionid' => [true, "sectname", "section", "sectid"]],
			'Supervisor' => ['supervisorid' => [true, "clientname", "client", "clientid"]],
		];

		$this->settriggermasterfilelogs($config, 'entryrole', 'rolesetup', 'masterfile_log', $fields, 'line');
	}

	private function contactperson_tab_triggers($config)
	{
		$trigger_name = "contactperson_tab";
		$table = "contactperson";
		$table_log = "masterfile_log";
		$key = "clientid";

		// $this->coreFunctions->sbcdroptriggers("supplier_contactperson_tab_update");

		$fields = [
			'line' => ['line' => []],
			'clientid' => ['clientid' => []],
			'salutation' => ['salutation' => []],
			'fname' => ['fname' => []],
			'mname' => ['mname' => []],
			'lname' => ['lname' => []],
			'email' => ['email' => []],
			'contactno' => ['contactno' => []],
			'bday' => ['bday' => []],
			'department' => ['department' => []],
			'designation' => ['designation' => []],
		];

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key);
	}

	private function billing_add_tab_triggers($config)
	{
		$trigger_name = "billing_add_tab";
		$table = "billingaddr";
		$table_log = "masterfile_log";
		$key = "clientid";

		$fields = [
			'line' => ['line' => []],
			'clientid' => ['clientid' => []],
			'ADDR' => ['addr' => []],
			'SHIPPING' => ['isshipping' => []],
			'BILLING' => ['isbilling' => []],
			'INACTIVE' => ['isinactive' => []],
			'ADDR TYPE' => ['addrtype' => []],
			'ADDR LINE1' => ['addrline1' => []],
			'ADDR LINE2' => ['addrline2' => []],
			'CITY' => ['city' => []],
			'PROVINCE' => ['province' => []],
			'COUNTRY' => ['country' => []],
			'ZIP' => ['zipcode' => []],
			'FAX' => ['fax' => []],
		];

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key);
	}

	private function skuentry_tab_triggers($config)
	{
		$companyid = $config['params']['companyid'];
		$trigger_name = "skuentry_tab";
		$table = "sku";
		$table_log = "masterfile_log";
		$key = "line";
		$key2 = "itemid";
		$trno2 = "clientid";

		$fields = [
			'line' => ['line' => []],
			'item' => ['itemid' => [true, "concat(barcode, '-', itemname)", "item", "itemid"]],
			'sku' => ['sku' => []],
			'amt' => ['amt' => []],
			'disc' => ['disc' => []],
			'groupid' => [],
			'uom' => ['uom2' => []],
			'printed uom' => ['uom3' => []]

		];

		if ($companyid == 63) {
			$fields['client'] = ['clientid' => [true, "concat(client, '-', clientname)", "client", "clientid"]];
		} else {
			$fields['clientid'] = ['clientid' => []];
		}

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key, $key2, 'clientid', 'clientid', $trno2);
	}

	private function entryuom_tab_triggers($config)
	{
		$trigger_name = "entryuom_tab";
		$table = "uom";
		$table_log = "masterfile_log";
		$key = "line";
		$key2 = "itemid";

		$fields = [
			'line' => ['line' => []],
			'item' => ['itemid' => [true, "concat(barcode, '-', itemname)", "item", "itemid"]],
			'uom' => ['uom' => []],
			'factor' => ['factor' => []],
			'allow in SO/SJ' => ['issales' => []],
			'default in SO/SJ' => ['issalesdef' => []],
			'Printed UOM' => ['printuom' => []],
		];

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key, $key2);
	}

	private function entrycomponent_tab_triggers($config)
	{
		$trigger_name = "entrycomponent_tab";
		$table = "component";
		$table_log = "masterfile_log";
		$key = "line";
		$key2 = "itemid";

		$fields = [
			'line' => ['line' => []],
			'Itemname' => ['itemid' => [true, "concat(barcode, '-', itemname)", "item", "itemid"]],
			'Barcode' => ['barcode' => []],
			'UOM' => ['uom' => []],
			'Qty' => ['isqty' => []],
			'Cost' => ['cost' => []],
		];

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key, $key2);
	}

	private function entryuomlist_triggers($config)
	{
		$fields = [
			'UOM' => ['uom' => []],
			'Factor' => ['factor' => []]
		];

		$this->settriggermasterfilelogs($config, 'entryuomlist', 'uomlist', 'masterfile_log', $fields, 'line');
	}

	private function entrypowercat_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'Name' => ['name' => []],
			'Group' => ['groupid' => []],
		];

		$this->settriggermasterfilelogs($config, 'entrypowercat', 'powercat', 'masterfile_log', $fields, 'line');
	}

	private function entrysubpowercat_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'Name' => ['name' => []],
		];

		$this->settriggermasterfilelogs($config, 'entrysubpowercat', 'subpowercat', 'masterfile_log', $fields, 'line');
	}

	private function entrysubpowercat2_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'Name' => ['name' => []],
		];

		$this->settriggermasterfilelogs($config, 'entrysubpowercat2', 'subpowercat2', 'masterfile_log', $fields, 'line');
	}

	private function en_levels_triggers($config)
	{
		$fields = [
			'Levels' => ['levels' => []],
			'Order Levels' => ['orderlevels' => []],
			'Is Grade School' => ['isgradeschool' => []],
			'Is High School' => ['ishighschool' => []],
			'Is Convert Grade English' => ['isenconvertgrade' => []],
			'Is Convert Grade Chinese' => ['ischiconvertgrade' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_levels', 'en_levels', 'masterfile_log', $fields, 'line');
	}

	// Room Management

	private function ratecodesetup_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Code' => ['code' => []],
			'Description' => ['description' => []],
			'isinactive' => ['isinactive' => []]
		];
		$this->settriggermasterfilelogs($config, 'ratecodesetup', 'hmsratesetup', 'masterfile_log', $fields, 'line');
	}

	private function othercharges_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Amount' => ['amt' => []],
			'Description' => ['description' => []],
			'isinactive' => ['isinactive' => []]

		];
		$this->settriggermasterfilelogs($config, 'othercharges', 'hmscharges', 'masterfile_log', $fields, 'line');
	}

	private function packagesetup_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Description' => ['packname' => []],
			'isinactive' => ['isinactive' => []]
		];
		$this->settriggermasterfilelogs($config, 'packagesetup', 'hmspackage', 'masterfile_log', $fields, 'line');
	}

	private function roomtype_triggers($config)
	{
		$fields = [
			'Id' => ['id' => []],
			'Room Type' => ['roomtype' => []],
			'Inactive' => ['inactive' => []],
			'Category' => ['category' => []],
			'Extra Pax Rate' => ['additional' => []],
			'No. of Pax' => ['maxadult' => []],
			'No. of Beds' => ['beds' => []],
			'Is Smoking' => ['issmoking' => []]
		];

		$this->settriggermasterfilelogs($config, 'roomtype', 'tblroomtype', 'masterfile_log', $fields, 'id');
	}

	private function entryroomlist($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Room No.' => ['roomno' => []],
			'Room Type Id' => ['roomtypeid' => []],
			'Is Inactive' => ['isinactive' => []]
		];
		$this->settriggermasterfilelogs($config, 'entryroomlist', 'hmsrooms', 'masterfile_log', $fields, 'roomtypeid');
	}



	private function entrystages_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Stage' => ['stage' => []],
			'Description' => ['description' => []],
		];


		$this->settriggermasterfilelogs($config, 'entrystages', 'stagesmasterfile', 'masterfile_log', $fields, 'line');
	}

	private function entryphase_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Code' => ['code' => []],
			'Name' => ['name' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryphase', 'phase', 'masterfile_log', $fields, 'line');
	}

	private function entrysection_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Code' => ['code' => []],
			'Name' => ['name' => []],
			'Phase' => ['phaseid' => [true, "name", "phase", "line"]],
		];

		$this->settriggermasterfilelogs($config, 'entrysection', 'locsection', 'masterfile_log', $fields, 'line');
	}

	private function entryratecat_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Category' => ['category' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryratecat', 'ratecategory', 'masterfile_log', $fields, 'line');
	}

	private function dt_details_triggers($config)
	{
		$fields = [
			'ID' => ['id' => []],
			'Details' => ['details' => []]
		];
		$this->settriggermasterfilelogs($config, 'dt_details', 'dt_details', 'masterfile_log', $fields, 'id');
	}



	private function en_semester_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Term' => ['term' => []],
			'Order Term' => ['orderterm' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_semester', 'en_term', 'masterfile_log', $fields, 'line');
	}

	private function en_roomlist_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Building Code' => ['bldgcode' => []],
			'Building Name' => ['bldgname' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_roomlist', 'en_bldg', 'masterfile_log', $fields, 'line');
	}

	private function entryroom($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Room Code' => ['roomcode' => []],
			'Room Name' => ['roomname' => []]
		];
		$this->settriggermasterfilelogs($config, 'entryroom', 'en_rooms', 'masterfile_log', $fields, 'bldgid');
	}


	private function en_course_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Course Code' => ['coursecode' => []],
			'Course Name' => ['coursename' => []],
			'Is Inactive' => ['isinactive' => []],
			'Level' => ['level' => []],
			'Dean Name' => ['deanname' => []],
			'Department Code' => ['deptcode' => []],
			'Is Degree' => ['isdegree' => []],
			'Is Undergraduate' => ['isundergraduate' => []],
			'Account' => ['tfaccount' => []],
			'Level ID' => ['levelid' => []],
			'Is Chinese' => ['ischinese' => []]
		];
		$this->settriggermasterfilelogs($config, 'en_course', 'en_course', 'masterfile_log', $fields, 'line');
	}

	private function entrysection($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Section' => ['section' => []],
			'Is Inactive' => ['isinactive' => []]
		];
		$this->settriggermasterfilelogs($config, 'entrysection', 'en_section', 'masterfile_log', $fields, 'courseid');
	}

	// private function en_transferee_requirements_triggers($config){
	// 	$fields = [
	// 		'Line' => ['line' => []],
	// 		'Requirements' => ['requirements' => []],
	// 		'Student Type' => ['studenttype' => []]
	// 	];
	// 	$this->settriggermasterfilelogs($config,'en_transferee_requirements', 'en_requirements', 'masterfile_log', $fields, 'line');
	// }

	private function en_requirements_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Requirements' => ['requirements' => []],
			'Student Type' => ['studenttype' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_new_student_requirements', 'en_requirements', 'masterfile_log', $fields, 'line');
	}

	private function en_schoolyear_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'School Year' => ['sy' => []],
			'SY' => ['issy' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_schoolyear', 'en_schoolyear', 'masterfile_log', $fields, 'line');
	}

	private function en_period_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Code' => ['code' => []],
			'Name' => ['name' => []],
			'Isactive' => ['isactive' => []],
			'School Year' => ['sy' => []],
			'Semester' => ['semid' => []],
			'Start Date' => ['sstart' => []],
			'End Date' => ['send' => []],
			'Principal Code' => ['principalid' => []],
			'Ext Date' => ['sext' => []],
			'Enrollment Start Date' => ['estart' => []],
			'Enrollment End Date' => ['eend' => []],
			'Enrollment Ext Date' => ['eext' => []],
			'Add/Drop Start Date' => ['astart' => []],
			'Add/Drop End Date' => ['aend' => []],
			'Add/Drop Ext Date' => ['aext' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_period', 'en_period', 'masterfile_log', $fields, 'line');
	}

	private function en_fees_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Code' => ['feescode' => []],
			'Description' => ['feesdesc' => []],
			'Type' => ['feestype' => []],
			'Acount Code' => ['acnoid' => [true, "acnoname", "coa", "acnoid"]],
			'Vat' => ['vat' => []],
			'Amount' => ['amount' => []]
		];
		$this->settriggermasterfilelogs($config, 'en_fees', 'en_fees', 'masterfile_log', $fields, 'line');
	}

	private function en_credentials_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Credentials' => ['credentials' => []],
			'Amount' => ['amt' => []],
			'Particulars' => ['particulars' => []],
			'Percent Discount' => ['percentdisc' => []],
			'Credential Code' => ['credentialcode' => []],
			'Acount Code' => ['acnoid' => [true, "acnoname", "coa", "acnoid"]],
			'Deduct to Fee' => ['feesid' => []],
			'Subject ID' => ['subjectid' => []],
			'Scheme' => ['scheme' => []]
		];
		$this->settriggermasterfilelogs($config, 'en_credentials', 'en_credentials', 'masterfile_log', $fields, 'line');
	}

	private function en_modeofpayment_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Code' => ['code' => []],
			'Interest %' => ['deductpercent' => []],
			'Mode of Payment' => ['modeofpayment' => []],
			'Number of Months' => ['months' => []],
			'1st %' => ['perc1' => []],
			'Date 1' => ['date1' => []],
			'2nd %' => ['perc2' => []],
			'Date 2' => ['date2' => []],
			'3rd %' => ['perc3' => []],
			'Date 3' => ['date3' => []],
			'4th %' => ['perc4' => []],
			'Date 4' => ['date4' => []],
			'5th %' => ['perc5' => []],
			'Date 5' => ['date5' => []],
			'6th %' => ['perc6' => []],
			'Date 6' => ['date6' => []],
			'7th %' => ['perc7' => []],
			'Date 7' => ['date7' => []],
			'8th %' => ['perc8' => []],
			'Date 8' => ['date8' => []],
			'9th %' => ['perc9' => []],
			'Date 9' => ['date9' => []],
			'10th %' => ['perc10' => []],
			'Date 10' => ['date10' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_modeofpayment', 'en_modeofpayment', 'masterfile_log', $fields, 'line');
	}

	private function en_quartersetup_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Code' => ['code' => []],
			'Name' => ['name' => []],
			'Chinese Code' => ['chinesecode' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_quartersetup', 'en_quartersetup', 'masterfile_log', $fields, 'line');
	}

	private function en_honorrollcriteria_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Rank Criteria' => ['rankcriteria' => []],
			'Title' => ['title' => []],
			'Low Grade' => ['lowgrade' => []],
			'High Grade' => ['highgrade' => []],
			'Encoded By' => ['encodedby' => []],
			'Encoded Date' => ['encodeddate' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_honorrollcriteria', 'en_honorrollcriteria', 'masterfile_log', $fields, 'line');
	}

	private function en_gradecomponent_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Code' => ['gccode' => []],
			'Description' => ['gcname' => []],
			'Percentage' => ['gcpercent' => []],
			'Is Conduct' => ['isconduct' => []]
		];
		$this->settriggermasterfilelogs($config, 'en_gradecomponent', 'en_gradecomponent', 'masterfile_log', $fields, 'line');
	}

	private function en_gradeequivalent_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Range 1' => ['range1' => []],
			'Range 2' => ['range2' => []],
			'Equivalent' => ['equivalent' => []],
			'Action Taken' => ['actiontaken' => []],
			'English Equivalent' => ['gradeequivalent' => []],
			'Chinese Equivalent' => ['chineseequivalent' => []]
		];
		$this->settriggermasterfilelogs($config, 'en_gradeequivalent', 'en_gradeequivalent', 'masterfile_log', $fields, 'line');
	}

	private function en_gradeequivalentletters_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Range 1' => ['range1' => []],
			'Range 2' => ['range2' => []],
			'Equivalent' => ['equivalent' => []],
			'Action Taken' => ['actiontaken' => []],
			'English Equivalent' => ['gradeequivalent' => []],
			'Chinese Equivalent' => ['chineseequivalent' => []]
		];
		$this->settriggermasterfilelogs($config, 'en_gradeequivalentletters', 'en_gradeequivalentletters', 'masterfile_log', $fields, 'line');
	}

	private function en_attendancetype_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Type' => ['type' => []],
			'Color' => ['color' => []]
		];
		$this->settriggermasterfilelogs($config, 'en_attendancetype', 'en_attendancetype', 'masterfile_log', $fields, 'line');
	}

	private function en_conductgrade_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Conduct English' => ['conductenglish' => []],
			'Conduct Chinese' => ['conductchinese' => []],
			'Low' => ['lowgrade' => []],
			'High' => ['highgrade' => []],
			'Encoded By' => ['encodedby' => []],
			'Encoded Date' => ['encodeddate' => []]
		];
		$this->settriggermasterfilelogs($config, 'en_conductgrade', 'en_conductgrade', 'masterfile_log', $fields, 'line');
	}

	private function en_cardremarks_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Remarks' => ['remarks' => []],
			'Is Chinese' => ['ischinese' => []]
		];
		$this->settriggermasterfilelogs($config, 'en_cardremarks', 'en_cardremarks', 'masterfile_log', $fields, 'line');
	}

	private function en_attendancesetup_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'School Year' => ['syid' => [true, "sy", "en_schoolyear", "line"]],
			'Level' => ['levelid' => [true, "levels", "en_levels", "line"]],
			'January' => ['jan' => []],
			'February' => ['feb' => []],
			'March' => ['mar' => []],
			'April' => ['apr' => []],
			'May' => ['may' => []],
			'June' => ['jun' => []],
			'July' => ['jul' => []],
			'August' => ['aug' => []],
			'September' => ['sep' => []],
			'October' => ['oct' => []],
			'November' => ['nov' => []],
			'December' => ['dec' => []],
			'December' => ['totaldays' => []],
			'Start Month' => ['startmonth' => []],
			'End Month' => ['endmonth' => []]
		];

		$this->settriggermasterfilelogs($config, 'en_attendancesetup', 'en_attendancesetup', 'masterfile_log', $fields, 'line');
	}

	private function en_subject_triggers($config)
	{
		$fields = [
			'Subject Code' => ['subjectcode' => []],
			'Subject Name' => ['subjectname' => []],
			'Units' => ['units' => []],
			'Isinactive' => ['isinactive' => []],
			'Ismajor' => ['ismajor' => []],
			'Lecture' => ['lecture' => []],
			'Laboratory' => ['laboratory' => []],
			'Hours' => ['hours' => []],
			'Level' => ['level' => []],
			'Pre Requisite 1' => ['prereq1' => []],
			'Pre Requisite 2' => ['prereq2' => []],
			'Pre Requisite 3' => ['prereq3' => []],
			'Pre Requisite 4' => ['prereq4' => []],
			'TF' => ['tf' => []],
			'Loadx' => ['loadx' => []],
			'Co req' => ['coreq' => []],
			'Ischinese' => ['ischinese' => []]

		];
		$this->settriggermasterfilelogs($config, 'en_subject', 'en_subject', 'masterfile_log', $fields, 'trno');
	}

	private function entry_subject_triggers($config)
	{
		$fields = [
			'Subject Code' => ['subjectcode' => []],
			'Subject Name' => ['subjectname' => []],
			'Units' => ['units' => []],
			'Hours' => ['hours' => []],
			'Load' => ['load' => []],
			'Laboratory' => ['laboratory' => []],
			'Lecture' => ['lecture' => []],
			'Subject Main' => ['subjectmain' => []],
			'subjectid' => ['subjectid' => []]

		];
		$this->settriggermasterfilelogs($config, 'entry_subject_triggers', 'en_subjectequivalent', 'masterfile_log', $fields, 'line');
	}

	private function dt_issues_triggers($config)
	{
		$fields = [
			'Id' => ['id' => []],
			'Issues' => ['issues' => []]
		];
		$this->settriggermasterfilelogs($config, 'dt_issues', 'dt_issues', 'masterfile_log', $fields, 'id');
	}

	private function dt_industry_triggers($config)
	{
		$fields = [
			'Id' => ['id' => []],
			'Industry' => ['industry' => []]
		];
		$this->settriggermasterfilelogs($config, 'dt_industry', 'dt_industry', 'masterfile_log', $fields, 'id');
	}

	private function dt_documenttype_triggers($config)
	{
		$fields = [
			'Id' => ['id' => []],
			'Document Type' => ['documenttype' => []]
		];
		$this->settriggermasterfilelogs($config, 'dt_documenttype', 'dt_documenttype', 'masterfile_log', $fields, 'id');
	}

	private function dt_division_triggers($config)
	{
		$fields = [
			'ID' => ['id' => []],
			'Division' => ['division' => []]
		];
		$this->settriggermasterfilelogs($config, 'dt_division', 'dt_division', 'masterfile_log', $fields, 'id');
	}

	private function dt_status_triggers($config)
	{
		$fields = [
			'ID' => ['id' => []],
			'Status' => ['status' => []]
		];
		$this->settriggermasterfilelogs($config, 'dt_status', 'dt_statuslist', 'masterfile_log', $fields, 'id');
	}

	private function dt_statusaccess_triggers($config)
	{
		$fields = [
			'ID' => ['id' => []],
			'User' => ['userid' => [true, "username", "users", "idno"]],
			'Status' => ['statusdoc' => [true, "status", "dt_statuslist", "id"]],
			'Sort' => ['statussort' => []]
		];
		$this->settriggermasterfilelogs($config, 'dt_statusaccess', 'dt_status', 'masterfile_log', $fields, 'id');
	}

	private function loc_mms_trigger($config)
	{
		$fields = [
			'LINE' => ['line' => []],
			'CODE' => ['code' => []],
			'NAME' => ['name' => []],
			'ELECTRIC METER #' => ['emeter' => []],
			'S. ELECTRIC METER #' => ['wmeter' => []],
			'WATER METER #' => ['semeter' => []],
			'SQM' => ['area' => []],
		];
		$this->settriggermasterfilelogs($config, 'locationledger', 'loc', 'masterfile_log', $fields, 'line');
	}

	private function entryescalation_tab_trigger($config)
	{
		$fields = [
			'LINE' => ['line' => []],
			'DATE' => ['dateid' => []],
			'RATE' => ['rate' => []],
		];
		$this->settriggermasterfilelogs($config, 'entryescalation_tab', 'escalation', 'masterfile_log', $fields, 'line');
	}

	private function branchbank_tab_trigger($config)
	{
		$fields = [
			'LINE' => ['line' => []],
			'ACCOUNT #' => ['acnoid' => [true, "concat(acno, '-', acnoname)", "coa", "acnoid"]],
			'TERMINAL ID' => ['terminalid' => []],
			'BANK' => ['bank' => []],
			'ACTIVE' => ['isinactive' => []],
		];
		$this->settriggermasterfilelogs($config, 'entrybankterminal_tab', 'branchbank', 'masterfile_log', $fields, 'line');
	}

	private function bankcharges_tab_trigger($config)
	{
		$fields = [
			'LINE' => ['line' => []],
			'TYPE' => ['type' => []],
			'RATE' => ['rate' => []],
			'EWT' => ['ewt' => []],
			'ACTIVE' => ['inactive' => []],
		];
		$this->settriggermasterfilelogs($config, 'entrybankcharges', 'bankcharges', 'masterfile_log', $fields, 'line');
	}

	private function cardtypes_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Card Type' => ['cardtype' => []],
			'Last Update' => ['dlock' => []],
			'Is Inactive' => ['isinactive' => []]
		];

		$this->settriggermasterfilelogs($config, 'cardtypes', 'cardtype', 'masterfile_log', $fields, 'line');
	}

	private function jobtitlemaster_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'DOCNO' => ['docno' => []],
			'JOB TITLE' => ['jobtitle' => []],
		];

		$this->settriggermasterfilelogs($config, 'jobtitlemaster', 'jobthead', 'masterfile_log', $fields, 'line');
	}

	private function jobtitlemaster_detail_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'DESCRIPTION' => ['description' => []],
		];

		$this->settriggermasterfilelogs($config, 'jobtitledesc', 'jobtdesc', 'masterfile_log', $fields, 'trno');
	}

	private function codeconduct_triggers($config)
	{
		$fields = [
			'Line' => ['artid' => []],
			'CODE' => ['code' => []],
			'DESCRIPTION' => ['description' => []],
		];

		$this->settriggermasterfilelogs($config, 'codeconduct', 'codehead', 'masterfile_log', $fields, 'artid');
	}

	private function codeconduct_detail_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'SECTION' => ['section' => []],
			'DESCRIPTION' => ['description' => []],
			'FIRST' => ['d1a' => []],
			'# OF DAYS' => ['d1b' => []],
			'SECOND' => ['d2a' => []],
			'# OF DAYS' => ['d2b' => []],
			'THIRD' => ['d3a' => []],
			'# OF DAYS' => ['d3b' => []],
			'4TH' => ['d4a' => []],
			'# OF DAYS' => ['d4b' => []],
			'4TH' => ['d5a' => []],
			'# OF DAYS' => ['d5b' => []],
		];

		$this->settriggermasterfilelogs($config, 'codeconduct_list', 'codedetail', 'masterfile_log', $fields, 'artid');
	}

	private function paymenttype_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Payment Type' => ['type' => []],
			'Client' => ['clientid' => [true, "concat(client, '-', clientname)", "client", "clientid"]],
			'Account Name' => ['acnoid' => [true, "concat(acno, '-', acnoname)", "coa", "acnoid"]],
			'Last Update' => ['dlock' => []],
			'Is Inactive' => ['inactive' => []]
		];

		$this->settriggermasterfilelogs($config, 'paymenttype', 'checktypes', 'masterfile_log', $fields, 'line');
	}

	private function billableitemssetup_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'Description' => ['description' => []],
			'Asset' => ['asset' => [true, "concat(acno, '-', acnoname)", "coa", "acnoid"]],
			'Revenue' => ['revenue' => [true, "concat(acno, '-', acnoname)", "coa", "acnoid"]],
			'Is Vat' => ['isvat' => []]
		];

		$this->settriggermasterfilelogs($config, 'billableitemssetup', 'ocharges', 'masterfile_log', $fields, 'line');
	}

	private function poterms_triggers($config)
	{
		$fields = [
			'Line' => ['line' => []],
			'PO Terms' => ['poterms' => []],
		];

		$this->settriggermasterfilelogs($config, 'poterms', 'poterms', 'masterfile_log', $fields, 'line');
	}

	private function entryprojectstages_triggers($config)
	{
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
			'jc' => ['jc' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryprojectstages', 'stages', 'masterfile_log', $fields, 'subproject');
	}

	private function addsubstages_triggers($config)
	{
		$fields = [
			'Activity' => ['substage' => []]
		];

		$this->settriggermasterfilelogs($config, 'addsubstages', 'substages', 'masterfile_log', $fields, 'stage');
	}

	private function addsubactivity_triggers($config)
	{
		$fields = [
			'Sub Activity' => ['subactivity' => []],
			'Task' => ['description' => []]
		];

		$this->settriggermasterfilelogs($config, 'addsubactivity', 'subactivity', 'masterfile_log', $fields, 'substage');
	}

	private function addsubitems_triggers($config)
	{
		$fields = [
			'Item' => ['itemid' => [true, "concat(barcode, '-', itemname)", "item", "itemid"]],
			'Quantity' => ['qty' => []],
			'Amount' => ['amt' => []]
		];

		$this->settriggermasterfilelogs($config, 'addsubitems', 'subitems', 'masterfile_log', $fields, 'subactivity');
	}

	private function entryprojectactivity_triggers($config)
	{
		$fields = [
			'Item' => ['itemid' => [true, "concat(barcode, '-', itemname)", "item", "itemid"]],
			'Quantity' => ['qty' => []],
			'Amount' => ['amt' => []]
		];

		$this->settriggermasterfilelogs($config, 'entryprojectactivity', 'activity', 'masterfile_log', $fields, 'subactivity');
	}

	private function entrysubprojectactivity_triggers($config)
	{
		// $triggername = 'entryprojectsubactivity_update';
		// $this->coreFunctions->sbcdroptriggers($triggername);
		// $fields = [
		// 	'Quantity' => ['rrqty' => []],
		// 	'Amount' => ['rrcost' => []],
		// 	'Total' => ['ext' => []],
		// 	'Unit Of Price' => ['uom' => []]
		// ];

		// $this->settriggermasterfilelogs($config, 'entryprojectsubactivity', 'psubactivity', 'transnum_log', $fields, 'substage', 'trno');
	}

	private function entryitemgroup_triggers($config)
	{
		$trigger_name = "entryitemgroup";
		$table = "itemgroup";
		$table_log = "masterfile_log";
		$key = "clientid";

		$fields = [
			'project' => ['projectid' => [true, "name", "projectmasterfile", "line"]],
		];

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key);
	}

	// private function entrycalllog($config)
	// {
	// 	$trigger_name = "entrycalllog";
	// 	$table = "calllogs";
	// 	$table_log = "masterfile_log";
	// 	$key = "trno";

	// 	$fields = [
	// 		'date' => ['dateid' => []],
	// 		'Contact Person' => ['contact' => []],
	// 		'Start Time' => ['starttime' => []],
	// 		'End Time' => ['endtime' => []],
	// 		'Remarks' => ['rem' => []],
	// 		'Call Type' => ['calltype' => []],
	// 	];

	// 	$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key);
	// }

	private function entrygeneralitem($config)
	{
		$trigger_name = "entrygeneralitem";
		$table = "generalitem";
		$table_log = "masterfile_log";
		$key = "line";

		$fields = [
			'barcode' => ['barcode' => []],
			'uom' => ['uom' => []],
			'itemname' => ['itemname' => []],
			'subgroup' => ['subgroup' => []],
			'company' => ['company' => []],
			'size' => ['sizeid' => []],
			'group' => ['groupid' => [true, "stockgrp_name", "stockgrp_masterfile", "stockgrp_id"]],
			'model' => ['modelid' => [true, "model_name", "model_masterfile", "model_id"]],
			'class' => ['classid' => [true, "cl_name", "item_class", "cl_id"]],
			'brand' => ['brandid' => [true, "brand_desc", "frontend_ebrands", "brandid"]],
		];

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key);
	}

	private function qtybracket_triggers($config)
	{
		$trigger_name = "entryqtybracket";
		$table = "qtybracket";
		$table_log = "masterfile_log";
		$key = "line";

		$fields = [
			'Maximum Qty' => ['maximum' => []],
			'Minimum Qty' => ['minimum' => []]
		];

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key, '', 'NAME', 'name');
	}

	private function purpose_triggers($config)
	{
		$trigger_name = "purpose_triggers";
		$table = "purpose_masterfile";
		$table_log = "masterfile_log";
		$key = "line";

		$fields = [
			'Purpose' => ['purpose' => []]
		];

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key);
	}

	private function stockprice_triggers($config)
	{
		$trigger_name = "stockprice";
		$table = "itemprice";
		$table_log = "masterfile_log";
		$key = "itemid";

		$fields = [
			'line' => ['line' => []],
			'item' => ['itemid' => [true, "concat(barcode, '-', itemname)", "item", "itemid"]],
			'start qty' => ['startqty' => []],
			'end qty' => ['endqty' => []],
			'amt' => ['amt' => []],
		];
		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key);
	}

	private function customersa_tab_trigger($config)
	{
		$trigger_name = "customersa_tab";
		$table = "clientsano";
		$table_log = "masterfile_log";
		$key = "clientid";

		// $this->coreFunctions->sbcdroptriggers("supplier_contactperson_tab_update");

		$fields = [
			'line' => ['line' => []],
			'SA #' => ['sano' => []],
		];

		$this->settriggermasterfilelogs($config, $trigger_name, $table, $table_log, $fields, $key);
	}

	private function entryduration_triggers($config)
	{
		$fields = [
			'Duration' => ['duration' => []],
			'Days' => ['days' => []]
		];

		$this->settriggermasterfilelogs($config, 'entryduration', 'duration', 'masterfile_log', $fields, 'line');
	}

	private function reqcategory_triggers($config)
	{
		$companyid = $config['params']['companyid'];
		$labelcat = 'Description';
		$labeldesc = '';
		$labelreq = 'Request Type';
		$labelcode = 'Code';
		$labelposition = 'Position';
		$doc = 'entryrequestcategory';
		$addf = [];
		switch ($companyid) {
			case 10: //afti
				$labelcat = 'Industry';
				$labelreq = 'Sub Industry';
				$doc = 'entryclientindustry';
				break;

			case 43:
				$labelcat = 'Activity';
				$labelreq = 'Acvity Master';
				$doc = 'entryactivitymaster';
				break;
			case 58: //cdohris
				$labelcat = 'Category';
				$doc = 'entryreassignmentcategory';
				break;
			case 63: //ericco
				$labelreq = 'Repacker 1';
				$labelcat = 'Group';
				$doc = 'entryrepacker';
				$labelcode = 'Repacker 2';
				$labelposition = 'Repacker 3';
				break;
			case 62: // one sky
				$doc = 'entryreasonforhiring';
				$labelcat = 'Category';
				break;
			default: // BMS
				$labeldesc = 'description';
				break;
		}

		$fields = [
			$labeldesc => ['description' => []],
			$labelcat => ['category' => []],
			$labelreq => ['reqtype' => []],
			$labelcode => ['code' => []],
			$labelposition => ['position' => []],
			'Request Oracle Code' => ['isoracle' => []],
			'No System Input' => ['isnsi' => []],
			'Required Client Details' => ['iscldetails' => []],
			'Activity' => ['isactivity' => []],
			'Sort' => ['sortline' => []],
			'Commission' => ['iscomm' => []],
			'Isinactive' => ['isinactive' => []]
		];

		$this->settriggermasterfilelogs($config, $doc, 'reqcategory', 'masterfile_log', $fields, 'line');
	}

	private function itemgroupqoutasetup_triggers($config)
	{
		$fields = [
			'Year' => ['yr' => []],
			'Item Group' => ['projectid' => [true, "name", "projectmasterfile", "line"]],
			'Monthly Qouta' => ['amt' => []]
		];

		$this->settriggermasterfilelogs($config, 'itemgroupqoutasetup', 'itemgroupqouta', 'masterfile_log', $fields, 'line');
	}

	private function empprojdetail_triggers($config)
	{
		$fields = [
			'rem' => ['rem' => []],
			'Total Hours' => ['tothrs' => []]
		];

		$this->settriggermasterfilelogs($config, 'EMPPROJECTLOG', 'empprojdetail', 'masterfile_log', $fields, 'empid', '', '', '', 'dateno');
	}

	private function salesgroupqouta_triggers($config)
	{
		$fields = [
			'Year' => ['yr' => []],
			'Item Group' => ['projectid' => [true, "name", "projectmasterfile", "line"]],
			'Monthly Qouta' => ['amt' => []],
			'Agent' => ['agentid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
		];

		$this->settriggermasterfilelogs($config, 'salesgroupqouta', 'salesgroupqouta', 'masterfile_log', $fields, 'line');
	}

	private function entrycostcodes_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Name' => ['name' => []]
		];

		$this->settriggermasterfilelogs($config, 'entrycostcodes', 'costcode_masterfile', 'masterfile_log', $fields, 'line');
	}

	private function entryagentquota_triggers($config)
	{
		$fields = [
			'Project' => ['projectid' => [true, "code", "projectmasterfile", "line"]],
			'Year' => ['yr' => []],
			'Amount' => ['amount' => []],
			'January' => ['janamt' => []],
			'February' => ['febamt' => []],
			'March' => ['maramt' => []],
			'April' => ['apramt' => []],
			'May' => ['mayamt' => []],
			'June' => ['junamt' => []],
			'July' => ['julamt' => []],
			'August' => ['augamt' => []],
			'September' => ['sepamt' => []],
			'October' => ['octamt' => []],
			'November' => ['novamt' => []],
			'December' => ['decamt' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryagentquota', 'agentquota', 'masterfile_log', $fields, 'line', 'clientid');
	}


	private function entryplantype_triggers($config)
	{
		$fields = [
			'Plan Code' => ['code' => []],
			'Plan Name' => ['name' => []],
			'Amount' => ['amount' => []],
			'Cash' => ['cash' => []],
			'Annual' => ['annual' => []],
			'Semi Annual' => ['semi' => []],
			'Quarterly' => ['quarterly' => []],
			'Monthly' => ['monthly' => []],
			'Processing Fee' => ['processfee' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryplantype', 'plantype', 'masterfile_log', $fields, 'line');
	}

	private function entryplangroup_triggers($config)
	{
		$fields = [
			'Code' => ['code' => []],
			'Amount' => ['amt' => []],
			'Inactive' => ['inactive' => []],
		];

		$this->settriggermasterfilelogs($config, 'entryplangroup', 'plangrp', 'masterfile_log', $fields, 'line');
	}

	private function chargesbilling_triggers($config)
	{
		$fields = [
			'line' => ['line' => []],
			'cline' => ['cline' => []],
			'amt' => ['amt' => []],
			'Month' => ['bmonth' => []],
			'Year' => ['byear' => []],
			'Remarks' => ['rem' => []],
		];

		$this->settriggermasterfilelogs($config, 'chargesbilling', 'chargesbilling', 'masterfile_log', $fields, 'line');
	}

	private function pricelist_triggers($config)
	{
		$fields = [
			'Start Date' => ['startdate' => []],
			'End Date' => ['enddate' => []],
			'Remarks' => ['remarks' => []],
			'Price 1' => ['amount' => []],
			'Price 2' => ['amount2' => []],
			'Cost' => ['cost' => []],
			'Client' => ['clientid' => [true, "clientname", "client", "clientid"]]
		];
		$this->settriggermasterfilelogs($config, 'pospricelist', 'pricelist', 'masterfile_log', $fields, 'line', 'itemid');
	}

	private function commissionlist_triggers($config)
	{
		$fields = [
			'Start Date' => ['startdate' => []],
			'End Date' => ['enddate' => []],
			'Remarks' => ['remarks' => []],
			'Commission 1' => ['comm1' => []],
			'Commission 2' => ['comm2' => []],
			'Commission 3' => ['comm3' => []]
		];
		$this->settriggermasterfilelogs($config, 'commissionlist', 'commissionlist', 'masterfile_log', $fields, 'line', 'clientid');
	}

	private function supplierlist_triggers($config)
	{
		$fields = [
			'Start Date' => ['startdate' => []],
			'End Date' => ['enddate' => []],
			'Remarks' => ['remarks' => []],
			'Client' => ['clientid' => [true, "clientname", "client", "clientid"]]
		];
		$this->settriggermasterfilelogs($config, 'supplierlist', 'supplierlist', 'masterfile_log', $fields, 'line', 'itemid');
	}


	private function householdcontacts_triggers($config)
	{
		$companyid = $config['params']['companyid'];
		if ($companyid == 58) { //cdohris
			$doc = 'contactinfoentry';
		} else {
			$doc = 'entryhouseholdd';
		}
		$fields = [
			'ownertype' => ['ownertype' => []],
			'ownername' => ['ownername' => []],
			'addr2' => ['addr2' => []],
			'contact1' => ['contact1' => []]
		];

		$this->settriggermasterfilelogs($config, $doc, 'contacts', 'masterfile_log', $fields, 'line');
	}


	private function all_applications($config)
	{
		$fields = ['Applied Hours' => ['othrs' => []]];
		$this->settriggermasterfilelogs($config, 'ALL_APPLICATION', 'otapplication', 'masterfile_log', $fields, 'empid', 'dateid');
	}


	private function certrate_triggers($config)
	{
		$doc = 'entrycertrate';
		$fields = [
			'Amount 1' => ['amt1' => []],
			'Amount 2' => ['amt2' => []],
			'Rate' => ['crate' => []]
		];
		$this->settriggermasterfilelogs($config, $doc, 'certrate', 'masterfile_log', $fields, 'line');
	}

	private function entrytask_triggers($config)
	{
		$doc = 'ENTRYTASK';
		$fields = [
			'Task' => ['title' => []],
			'Percentage' => ['percentage' => []],
			'Assigned To' => ['userid' => [true, "clientname", "client", "clientid"]],
			'Start Date' => ['startdate' => []],
			'End Date' => ['enddate' => []]
		];
		$this->settriggermasterfilelogs($config, $doc, 'tmdetail', 'masterfile_log', $fields, 'line', 'trno');
	}

	private function tmhead_triggers($config)
	{
		$doc = 'tm';
		$fields = [
			'Customer' => ['clientid' => [true, "clientname", "client", "clientid"]],
			'System Type' => ['systype' => [true, "itemname", "item", "itemid"]],
			'Task Type' => ['tasktype' => [true, "category", "reqcategory", "line"]],
			'Rate' => ['rate' => []],
			'Date' => ['dateid' => []],
			'Request by' => ['requestby' => [true, "username", "useraccess", "userid"]],
			'Notes' => ['rem' => []]
		];
		$this->settriggermasterfilelogs($config, $doc, 'tmhead', 'masterfile_log', $fields, 'trno');
	}
	private function moduleapproval_triggers($config)
	{
		$doc = 'MODULEAPPROVAL';
		$fields = [
			'modulename' => ['modulename' => []],
			'labelname' => ['labelname' => []],
			'approverseq' => ['approverseq' => []],
			'countsupervisor' => ['countsupervisor' => []],
			'countapprover' => ['countapprover' => []]
		];
		$this->settriggermasterfilelogs($config, $doc, 'moduleapproval', 'masterfile_log', $fields, 'line');
	}

	private function timesetup_triggers($config)
	{
		$fields = [
			'time' => ['times' => []]
		];

		$this->settriggermasterfilelogs($config, 'entrytimesetup', 'timesetup', 'masterfile_log', $fields, 'line');
	}


	private function entrydailytask_triggers($config)
	{
		$doc = 'DY';
		$fields = [
			'Clientname' => ['clientid' => [true, "clientname", "client", "clientid"]],
			'Remarks' => ['rem' => []],
			'Amount' => ['amt' => []],
			'Jo#' => ['jono' => []],
			'Create Date' => ['dateid' => []],
			'Done Date' => ['donedate' => []]
		];
		$this->settriggermasterfilelogs($config, $doc, 'dailytask', 'task_log', $fields, 'trno');
	}
	private function branchjoblist_triggers($config)
	{
		$doc = 'BRANCH_JOB_LIST';
		$fields = [
			'Allocation' => ['qty' => []],
			'Job Title' => ['jobid' => []]
		];
		$this->settriggermasterfilelogs($config, $doc, 'cljobs', 'masterfile_log', $fields, 'line', '', '', '', 'clientid');
	}
	private function allowancesetup_triggers($config)
	{
		$doc = 'ALLOWANCESETUP';
		$fields = [
			'End Period' => ['dateend' => []]
		];
		$this->settriggermasterfilelogs($config, $doc, 'allowsetup', 'masterfile_log', $fields, 'empid', '', '', '', '');
	}

	private function locclearance_triggers($config)
	{
		$doc = 'clearancetype';
		$fields = [
			'Clearance' => ['clearance' => []],
			'Price' => ['price' => []]
		];
		$this->settriggermasterfilelogs($config, $doc, 'locclearance', 'masterfile_log', $fields, 'line');
	}
}// end class