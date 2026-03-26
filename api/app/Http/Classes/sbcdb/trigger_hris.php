<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class trigger_hris
{

	private $coreFunctions;

	public function __construct()
	{
		$this->coreFunctions = new coreFunctions;
	} //end fn

	private function settriggerlogs($triggername, $type, $tablename, $table_log, $data = [], $keys, $level = '', $customizetrigger = '')
	{
		$str = '';

		if (strtoupper($level) == 'STOCK') {
			$line = "( select concat(itemname,'(',barcode,')',' - ') from item where itemid = OLD.itemid )";
		} else if (strtoupper($level) == 'DETAIL') {
			$line = "( select concat(acnoname,'(',acno,')',' - ') from coa where acnoid = OLD.acnoid )";
		} else {
			$line = "''";
		}

		if (is_array($data)) {
			foreach ($data as $key => $value) {
				foreach ($value as $col => $value2) {

					if (isset($value2[0]) && $value2[0] == true) {

						$str .= "
	                if OLD.$col<>NEW.$col then
	                insert into $table_log(trno,field,oldversion,userid,dateid) 
	                values (OLD.$keys,'$level',concat('" . strtoupper($key) . " - ',$line,
	                ifnull((select " . $value2[1] . " from " . $value2[2] . " WHERE " . $value2[3] . " = OLD.$col),''), ' => ',
	                ifnull((select " . $value2[1] . " FROM " . $value2[2] . " WHERE " . $value2[3] . " = NEW.$col),'')),
	                New.editby,New.editdate);
	                end if;
	              ";
					} else {

						$str .= "
	                if OLD.$col<>NEW.$col then
	                insert into $table_log(trno,field,oldversion,userid,dateid) 
	                values (OLD.$keys,'$level',concat('" . strtoupper($key) . " - ',$line, 
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

	public function createtriggers_hris($config)
	{
		$this->hq_triggers();
		$this->hj_triggers();
		$this->ha_triggers();
		$this->ht_triggers();
		$this->ho_triggers();
		$this->hr_triggers();
		$this->hi_triggers();
		$this->hn_triggers();
		$this->hd_triggers();
		$this->hc_triggers();
		$this->hs_triggers();
	}

	// HRIS TRIGGERS LOGS
	private function hq_triggers()
	{ // personnel requisition triggers
		$fields = [
			'doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Department' => ['dept' => [true, "concat(client, '-', clientname)", 'client', 'client']],
			'Personnel' => ['personnel' => [true, "concat(client, '-', clientname)", 'client', 'client']],
			'Date Need' => ['dateneed' => []],
			'Job Title' => ['job' => [true, "jobtitle", 'jobthead', 'docno']],
			'Class' => ['class' => []],
			'Head Count' => ['headcount' => []],
			'Hiring Preference' => ['hpref' => []],
			'Age Range' => ['agerange' => []],
			'Gpref' => ['gpref' => []],
			'Rank' => ['rank' => []],
			'Reason' => ['reason' => []],
			'Remarks' => ['remark' => []],
			'Qualification' => ['qualification' => []],
			'Employee Status' => ['empstatusid' => [true, 'empstatus', 'empstatentry', 'line']],
		];
		$this->settriggerlogs('hq_triggers', 'AFTER UPDATE', 'personreq', 'hrisnum_log', $fields, 'trno', 'HEAD');

		$fields = [
			'Job Title Info' => ['job' => []]
		];
		$this->settriggerlogs('hpq_triggers', 'AFTER UPDATE', 'hpersonreq', 'hrisnum_log', $fields, 'trno', 'HEAD');

		$qry = "create TRIGGER personreq_update BEFORE UPDATE on personreq FOR EACH ROW
		  BEGIN
		   if New.QA>New.headcount then
		      CALL Applied_Count_is_Greater_Than_HeadCount_Requested;
		    end if;         
		  END";
		$this->coreFunctions->execqry($qry, 'trigger');

		$qry = "create TRIGGER hpersonreq_update BEFORE UPDATE on hpersonreq FOR EACH ROW
		  BEGIN
		   if New.QA>New.headcount then
		      CALL Applied_Count_is_Greater_Than_HeadCount_Requested;
		    end if;         
		  END";
		$this->coreFunctions->execqry($qry, 'trigger');
	}

	private function hj_triggers()
	{ // job offer triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Role' => ['roleid' => [true, "name", 'rolesetup', 'line']],
			'Department' => ['deptid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'To Pay Group' => ['paygroupid' => [true, "paygroup", 'paygroup', 'line']],
			'Section' => ['sectid' => [true, "sectname", 'section', 'sectid']],
			'Job Title' => ['emptitle' => [true, "jobtitle", 'jobthead', 'docno']],
			'Effect Date' => ['effectdate' => []],
			'Class Rate' => ['classrate' => []],
			'Rate' => ['rate' => []],
			'Employee Status' => ['empstat' => [true, "empstatus", 'empstatentry', 'line']],
			'Months #' => ['monthsno' => []],
			'Employee' => ['empname' => []],
		];
		$this->settriggerlogs('hj_triggers', 'AFTER UPDATE', 'joboffer', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}

	private function ha_triggers()
	{ // request training development triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Employee ' => ['empid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Department' => ['deptid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Job Title' => ['jobtitle' => []],
			'Training Type' => ['type' => []],
			'Traning Title' => ['title' => []],
			'Start Date' => ['date1' => []],
			'End Date' => ['date2' => []],
			'Purpose' => ['purpose' => []],
			'Budget' => ['budget' => []],
			'Venue' => ['venue' => []]
		];
		$this->settriggerlogs('ha_triggers', 'AFTER UPDATE', 'traindev', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}

	private function ht_triggers()
	{ // training entry triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Traning Title' => ['title' => []],
			'Training Type' => ['ttype' => []],
			'Venue' => ['venue' => []],
			'Training Date From' => ['tdate1' => []],
			'Training Date To' => ['tdate2' => []],
			'Speaker' => ['speaker' => []],
			'Amount' => ['amt' => []],
			'Cost' => ['cost' => []],
			'Remarks' => ['remarks' => []],
			'Attendees' => ['attendees' => []]
		];
		$this->settriggerlogs('ht_triggers', 'AFTER UPDATE', 'traininghead', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}

	private function ho_triggers()
	{ // turn over of items triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Employee ' => ['empid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Job Title' => ['jobtitle' => []],
			'Department' => ['dept' => []],
			'Notes' => ['rem' => []],
		];
		$this->settriggerlogs('ho_triggers', 'AFTER UPDATE', 'turnoveritemhead', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}

	private function hr_triggers()
	{ // return of items triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Employee ' => ['empid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Job Title' => ['jobtitle' => []],
			'Department' => ['dept' => []],
			'Notes' => ['rem' => []],
		];
		$this->settriggerlogs('hr_triggers', 'AFTER UPDATE', 'returnitemhead', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}

	private function hi_triggers()
	{ // incident report triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Incident Description' => ['idescription' => []],
			'Incident Date' => ['idate' => []],
			'Incident Place' => ['iplace' => []],
			'Incident Details' => ['idetails' => []],
			'Incident Comments' => ['icomments' => []],
			'To Employee ' => ['tempid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'From Employee ' => ['fempid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'To Employee Job Title' => ['tempjobid' => [true, "jobtitle", 'jobthead', 'line']],
			'From Employee Job Title' => ['fempjobid' => [true, "jobtitle", 'jobthead', 'line']],
		];
		$this->settriggerlogs('hi_triggers', 'AFTER UPDATE', 'incidenthead', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}

	private function hn_triggers()
	{ // notice to explain triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Employee ' => ['empid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Job Title' => ['empjob' => []],
			'From Employee ' => ['fempid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Deadline' => ['ddate' => []],
			'Hearing Date' => ['hdatetime' => []],
			'Hearing Place' => ['hplace' => []],
			'Hearing Time' => ['htime' => []],
			'Explanation' => ['explanation' => []],
			'Comments' => ['comments' => []],
			'Remarks' => ['remarks' => []],
			'Department' => ['deptid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Article Code' => ['artid' => [true, "code", 'codehead', 'artid']],
			'Article Name' => ['artid' => [true, "description", 'codehead', 'artid']],
			'Section Code' => ['line' => [true, "section", 'codedetail', 'line']],
			'Section Name' => ['line' => [true, "description", 'codedetail', 'line']],
			'Incident' => ['refx' => [true, "concat(docno, '-', idescription)", 'hincidenthead', 'trno']]
		];
		$this->settriggerlogs('hn_triggers', 'AFTER UPDATE', 'notice_explain', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}

	private function hd_triggers()
	{ // notice of disciplinary action triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Employee ' => ['empid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Article Code' => ['artid' => [true, "code", 'codehead', 'artid']],
			'Article Name' => ['artid' => [true, "description", 'codehead', 'artid']],
			'Section Code' => ['sectionno' => [true, "section", 'codedetail', 'line']],
			'Section Name' => ['sectionno' => [true, "description", 'codedetail', 'line']],
			'Times Violated' => ['violationno' => []],
			'Start Date' => ['startdate' => []],
			'End Date' => ['enddate' => []],
			'Amount' => ['amt' => []],
			'Details' => ['detail' => []],
			'Job Title' => ['jobtitle' => []],
			'Penalty' => ['penalty' => []],
			'Number of Days' => ['numdays' => []],
			'Department' => ['deptid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
		];
		$this->settriggerlogs('hd_triggers', 'AFTER UPDATE', 'disciplinary', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}

	private function hc_triggers()
	{ // clearance triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Employee ' => ['empid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Department' => ['deptid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Date Hired' => ['hired' => []],
			'Last Day of Work' => ['lastdate' => []],
			'Cause of Separation' => ['cause' => []],
			'Immediate Head' => ['empheadid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Status' => ['status' => []],
		];
		$this->settriggerlogs('hc_triggers', 'AFTER UPDATE', 'clearance', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}

	private function hs_triggers()
	{ // employment status change triggers
		$fields = [
			'Doc #' => ['docno' => []],
			'Date' => ['dateid' => []],
			'Employee ' => ['empid' => [true, "concat(client, '-', clientname)", 'client', 'clientid']],
			'Effect Date of Rate/Allowance' => ['effdate' => []],
			'Status Change' => ['statcode' => [true, "stat", 'statchange', 'code']],
			'Description' => ['description' => []],
			'Hired' => ['hired' => []],
			'Contract Start' => ['constart' => []],
			'Contract End' => ['conend' => []],
			'Resigned' => ['resigned' => []],
			'Remarks' => ['remarks' => []],
			'From Type' => ['ftype' => []],
			'From Level' => ['flevel' => []],
			'From Jobtitle' => ['fjobcode' => [true, "jobtitle", 'jobthead', 'docno']],
			'From Rank' => ['frank' => []],
			'From Job Grade' => ['fjobgrade' => []],
			'From Department' => ['fdeptcode' => [true, "concat(client, '-', clientname)", 'client', 'client']],
			'From Location' => ['flocation' => []],
			'From Paymode' => ['fpaymode' => []],
			'From Pay Group' => ['fpaygroup' => []],
			'From Pay Rate' => ['fpayrate' => []],
			'To Type' => ['ttype' => []],
			'To Level' => ['tlevel' => []],
			'To Jobtitle' => ['tjobcode' => [true, "jobtitle", 'jobthead', 'docno']],
			'To Rank' => ['trank' => []],
			'To Job Grade' => ['tjobgrade' => []],
			'To Department' => ['tdeptcode' => [true, "concat(client, '-', clientname)", 'client', 'client']],
			'To Location' => ['tlocation' => []],
			'To Paymode' => ['tpaymode' => []],
			'To Pay Group' => ['tpaygroup' => []],
			'To Pay Rate' => ['tpayrate' => []],
			'To Allowance' => ['tallowrate' => []],
			'To Basic Salary' => ['tbasicrate' => []],
		];
		$this->settriggerlogs('hs_triggers', 'AFTER UPDATE', 'eschange', 'hrisnum_log', $fields, 'trno', 'HEAD');
	}
}// end class