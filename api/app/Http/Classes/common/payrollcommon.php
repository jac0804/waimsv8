<?php

namespace App\Http\Classes\common;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\Logger;
use Carbon\Carbon;

class payrollcommon
{

    private $coreFunctions;
    private $othersClass;
    private $companysetup;
    private $tablename = 'paytrancurrent';
    private $logger;

    private $arrAccount = [];

    private $fields = ['dateid', 'db', 'cr', 'qty'];
    private $except = [];

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->companysetup = new companysetup;
        $this->logger = new Logger;
    }

    public function computeemptimesheet($batchid, $batchdate, $empid, $start, $end, $user, $batch, $params)
    {
        try {
            $qry = "delete from timesheet where empid=" . $empid . " and batchid=" . $batchid;
            $this->coreFunctions->execqry($qry);

            if (substr($batch, -2, 2) == '13') {
                return ['status' => true, 'msg' => ''];
            }

            //timecard data
            $qry = "select empid, sum(reghrs) as reghrs, sum(absdays) as absdays, sum(latehrs) as latehrs, sum(underhrs) as underhrs, sum(othrs) as othrs, sum(ndiffhrs) as ndiffhrs,
            sum(ndiffot) as ndiffot, sum(restday) as restday, sum(restot) as restot, sum(leg) as leg, sum(legot) as legot, sum(sp) as sp, sum(spot) as spot, sum(legabsent) as legabsent, sum(latehrs2) as latehrs2
            from (
            select empid, reghrs, absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='WORKING'
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, reghrs as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='RESTDAY' and RDapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, othrs as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='RESTDAY' and RDOTapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, reghrs-absdays as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG' and LEGapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, absdays as legabsent, latehrs2
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG' and LEGapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, othrs as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG' and LEGOTapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, reghrs as sp, 0 as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='SP' and SPapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, othrs as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='SP' and SPOTapprvd=1
            union all
            select  empid, 0 as reghrs, 0 as absdays, 0 as latehrs, 0 as underhrs, othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and dateid between '" . $start . "' and '" . $end . "' and otapproved=1
            union all
            select  empid, 0 as reghrs, 0 as absdays, 0 as latehrs, 0 as underhrs, 0 as othrs, ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and dateid between '" . $start . "' and '" . $end . "' and ndiffsapprvd=1
            union all
            select  empid, 0 as reghrs, 0 as absdays, 0 as latehrs, 0 as underhrs, 0 as othrs, 0 as ndiffhrs, ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and Ndiffapproved=1
            ) as t group by empid ";
            $timecard = $this->coreFunctions->opentable($qry);

            //all MDS account
            $qry = "select " . $batchid . " as batchid, " . $empid . " as empid, line as acnoid, '" . $batchdate . "' as dateid,
                alias, uom, qty as multiplier, code, seq from paccount where `type` = 'MDS' order by seq";
            $timesheet = $this->coreFunctions->opentable($qry);

            //inserting data from timecard
            foreach ($timesheet as $key =>  $val) {
                $qty = 0;
                $qty2 = 0;
                if (!empty($timecard)) {
                    switch ($val->alias) {
                        case 'WORKING':
                            if ($params['companyid'] == 25) { //sbc portal
                                $qty = 104;
                            } else {
                                $qty = $timecard[0]->reghrs;
                            }
                            break;
                        case 'ABSENT':
                            $qty = $timecard[0]->absdays;
                            break;
                        case 'LATE':
                            if ($params['companyid'] == 43) { //mighty
                                $qty = $timecard[0]->latehrs2;
                                $qty2 = $timecard[0]->latehrs;
                            } else {
                                $qty = $timecard[0]->latehrs;
                            }
                            break;
                        case 'UNDERTIME':
                            $qty = $timecard[0]->underhrs;
                            break;
                        case 'OTREG':
                            $qty = $timecard[0]->othrs;
                            break;
                        case 'NDIFF':
                            $qty = $timecard[0]->ndiffot;
                            break;
                        case 'NDIFFS':
                            $qty = $timecard[0]->ndiffhrs;
                            break;
                        case 'RESTDAY':
                            $qty = $timecard[0]->restday;
                            break;
                        case 'OTRES':
                            $qty = $timecard[0]->restot;
                            break;
                        case 'LEG':
                            $qty = $timecard[0]->leg;
                            break;
                        case 'LEGALOT':
                            $qty = $timecard[0]->legot;
                            break;
                        case 'SP':
                            $qty = $timecard[0]->sp;
                            break;
                        case 'SPECIALOT':
                            $qty = $timecard[0]->spot;
                            break;
                        case 'LEGUN':
                            $qty = $timecard[0]->legabsent;
                            break;
                        default:
                            $qty = 0;
                            break;
                    }
                }

                $this->addTimeSheetAccount($empid, $batchid, $val->acnoid, $val->dateid, $val->uom, $val->seq, $qty, $user, $qty2);
            }

            if ($params['resellerid'] == 2) { //mam joy
                $qry = "select empid, reghrs, isprevwork, dateid from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG'";
                $timecard = $this->coreFunctions->opentable($qry);

                if (!empty($timecard)) {
                    $legunwork = 0;
                    foreach ($timecard as $key =>  $val) {
                        if ($timecard[0]->isprevwork == 1) {
                            $legunwork = $legunwork + 8;
                        }
                    }
                    if ($legunwork != 0) {
                        $legaccnt = $this->coreFunctions->opentable("select line, uom, seq from paccount where alias='LEGUN'");
                        if (!empty($legaccnt)) {
                            $this->addTimeSheetAccount($empid, $batchid, $legaccnt[0]->line, $val->dateid, $legaccnt[0]->uom, $legaccnt[0]->seq, $legunwork, $user, 0);
                        }
                    }
                }
            }

            return ['status' => true, 'msg' => ''];
        } catch (Exception $e) {
            echo $e;
            return ['status' => false, 'msg' => $e];
        }
    }

    public function computeemptimesheet_cdo($batchid, $batchdate, $empid, $start, $end, $user, $batch, $params)
    {
        try {

            $latepenalty = $latepenaltyA = 0;
            $latePenalty_accnt = $noOutPenalty_accnt = $noBreakPenalty_accnt = $noUnderPenalty_accnt = [];
            $penalty_accnt = [];

            $noLoginPenalty = $this->coreFunctions->getfieldvalue("paccount", "penalty", "alias='NOLOGINPENALTY'", [], '', true);
            $nologinpenalty_accnt = $this->coreFunctions->opentable("select line, uom, seq from paccount where alias='NOLOGINPENALTY'");
            if (empty($nologinpenalty_accnt)) {
                return ['status' => false, 'msg' => 'Please setup No Login Penalty Account'];
            }

            $latepenalty = $this->coreFunctions->getfieldvalue("paccount", "penalty", "alias='LATEPENALTY'", [], '', true);
            $latePenalty_accnt = $this->coreFunctions->opentable("select line, uom, seq from paccount where alias='LATEPENALTY'");
            if (empty($latePenalty_accnt)) {
                return ['status' => false, 'msg' => 'Please setup Late Penalty Account.'];
            }

            $noBreakPenalty = $this->coreFunctions->getfieldvalue("paccount", "penalty", "alias='NOBREAKPENALTY'", [], '', true);
            $noBreakPenalty_accnt = $this->coreFunctions->opentable("select line, uom, seq from paccount where alias='NOBREAKPENALTY'");
            if (empty($noBreakPenalty_accnt)) {
                return ['status' => false, 'msg' => 'Please setup No Break Log-in/Log-out Penalty Account.'];
            }

            $noUnderPenalty = $this->coreFunctions->getfieldvalue("paccount", "penalty", "alias='NOUNDERPENALTY'", [], '', true);
            $noUnderPenalty_accnt = $this->coreFunctions->opentable("select line, uom, seq from paccount where alias='NOUNDERPENALTY'");
            if (empty($noUnderPenalty_accnt)) {
                return ['status' => false, 'msg' => 'Please setup No Undertime Penalty Account'];
            }

            $noOutPenalty = $this->coreFunctions->getfieldvalue("paccount", "penalty", "alias='NOOUTPENALTY'", [], '', true);
            $noOutPenalty_accnt = $this->coreFunctions->opentable("select line, uom, seq from paccount where alias='NOOUTPENALTY'");
            if (empty($noOutPenalty_accnt)) {
                return ['status' => false, 'msg' => 'Please setup No Log-out Penalty Account'];
            }

            $genericPenalty = $this->coreFunctions->getfieldvalue("paccount", "penalty", "alias='PENALTY'", [], '', true);
            $penalty_accnt = $this->coreFunctions->opentable("select line, uom, seq from paccount where alias='PENALTY'");
            if (empty($penalty_accnt)) {
                return ['status' => false, 'msg' => 'Please setup Penalty Account'];
            }

            $qry = "delete from timesheet where empid=" . $empid . " and batchid=" . $batchid;
            $this->coreFunctions->execqry($qry);

            if (substr($batch, -2, 2) == '13') {
                return ['status' => true, 'msg' => ''];
            }

            //timecard data
            $qry = "select empid, sum(reghrs) as reghrs, sum(absdays) as absdays, sum(latehrs) as latehrs, sum(underhrs) as underhrs, sum(othrs) as othrs, sum(ndiffhrs) as ndiffhrs,
            sum(ndiffot) as ndiffot, sum(restday) as restday, sum(restot) as restot, sum(leg) as leg, sum(legot) as legot, sum(sp) as sp, sum(spot) as spot, sum(legabsent) as legabsent, sum(latehrs2) as latehrs2, sum(sphrs) as sphrs
            from (
            select empid, reghrs, absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='WORKING'
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, reghrs as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='RESTDAY' and RDapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, othrs as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='RESTDAY' and RDOTapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, reghrs-absdays as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG' and LEGapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, absdays as legabsent, latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG' and LEGapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, othrs as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG' and LEGOTapprvd=1
            union all
            select empid, 0 as reghrs, absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, if(sphrs=0,reghrs-absdays,0) as sp, 0 as spot, 0 as legabsent, latehrs2, if(sphrs<>0,1,0) as sphrs
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='SP'
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, othrs as spot, 0 as legabsent, latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='SP' and SPOTapprvd=1
            union all
            select  empid, 0 as reghrs, 0 as absdays, 0 as latehrs, 0 as underhrs, othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, 0 as latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and dateid between '" . $start . "' and '" . $end . "' and otapproved=1
            union all
            select  empid, 0 as reghrs, 0 as absdays, 0 as latehrs, 0 as underhrs, 0 as othrs, ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, 0 as latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and dateid between '" . $start . "' and '" . $end . "' and ndiffsapprvd=1
            union all
            select  empid, 0 as reghrs, 0 as absdays, 0 as latehrs, 0 as underhrs, 0 as othrs, 0 as ndiffhrs, ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, 0 as latehrs2, 0 as sphrs
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and Ndiffapproved=1
            ) as t group by empid ";
            // $this->coreFunctions->LogConsole($qry);
            // Logger($qry);

            $timecard = $this->coreFunctions->opentable($qry);

            // and SPapprvd=1

            //all MDS account
            $qry = "select " . $batchid . " as batchid, " . $empid . " as empid, line as acnoid, '" . $batchdate . "' as dateid,
                alias, uom, qty as multiplier, code, seq from paccount where `type` = 'MDS' order by seq";
            $timesheet = $this->coreFunctions->opentable($qry);

            $halfday = 0;

            //inserting data from timecard
            foreach ($timesheet as $key =>  $val) {
                $qty = 0;
                $qty2 = 0;
                if (!empty($timecard)) {
                    switch ($val->alias) {
                        case 'WORKING':
                            $qty = $timecard[0]->reghrs + $timecard[0]->sp + $timecard[0]->sphrs;
                            break;
                        case 'ABSENT':
                            $qty = $timecard[0]->absdays;
                            break;
                        case 'LATE':
                            $qty = $timecard[0]->latehrs;
                            if ($timecard[0]->latehrs2 <= 59) {
                                $latepenaltyA = $timecard[0]->latehrs2 * $latepenalty; //cdo - compute late2(mins) penalty
                            }
                            break;
                        case 'UNDERTIME':
                            $qty = $timecard[0]->underhrs;
                            break;
                        case 'OTREG':
                            $qty = $timecard[0]->othrs;
                            break;
                        case 'NDIFF':
                            $qty = $timecard[0]->ndiffot;
                            break;
                        case 'NDIFFS':
                            $qty = $timecard[0]->ndiffhrs;
                            break;
                        case 'RESTDAY':
                            $qty = $timecard[0]->restday;
                            break;
                        case 'OTRES':
                            $qty = $timecard[0]->restot;
                            break;
                        case 'LEG':
                            $qty = $timecard[0]->leg;
                            break;
                        case 'LEGALOT':
                            $qty = $timecard[0]->legot;
                            break;
                        case 'SP':
                            $classrate = $this->coreFunctions->getfieldvalue("employee", "classrate", "empid=?", [$empid], '', true);
                            if ($classrate == 'D') $qty = ($timecard[0]->sphrs * 8);
                            break;
                        // case 'SP100':
                        //     $qty = $timecard[0]->sp;
                        //     break;
                        case 'SPECIALOT':
                            $qty = $timecard[0]->spot;
                            break;
                        case 'LEGUN':
                            $qty = $timecard[0]->legabsent;
                            break;
                        default:
                            $qty = 0;
                            break;
                    }
                }

                $this->addTimeSheetAccount($empid, $batchid, $val->acnoid, $val->dateid, $val->uom, $val->seq, $qty, $user, $qty2);
            }

            // for leg holiday daily rate employees
            $qry = "select tc.empid, tc.reghrs, tc.isprevwork, tc.dateid, emp.classrate from timecard  as tc join employee as emp on emp.empid=tc.empid 
                where tc.empid=" . $empid . " and date(tc.dateid) between '" . $start . "' and '" . $end . "' and tc.daytype='LEG' and emp.classrate='D'";
            $timecard = $this->coreFunctions->opentable($qry);

            if (!empty($timecard)) {
                $legunwork = 0;
                foreach ($timecard as $key =>  $val) {
                    if ($timecard[$key]->dateid <= '2026-01-15') { //due to initial computation, no data in previos period
                        $legunwork = $legunwork + 8;
                    } else {
                        if ($timecard[$key]->isprevwork == 1) {
                            $legunwork = $legunwork + 8;
                        }
                    }
                }
                if ($legunwork != 0) {
                    $legaccnt = $this->coreFunctions->opentable("select line, uom, seq from paccount where alias='LEG'");
                    if (!empty($legaccnt)) {
                        $this->addTimeSheetAccount($empid, $batchid, $legaccnt[0]->line, $val->dateid, $legaccnt[0]->uom, $legaccnt[0]->seq, $legunwork, $user, 0);
                    }
                }
            }

            if ($latepenaltyA != 0) { //cdo
                if (!empty($latePenalty_accnt)) {
                    $this->addTimeSheetAccount($empid, $batchid, $latePenalty_accnt[0]->line, $val->dateid, $latePenalty_accnt[0]->uom, $latePenalty_accnt[0]->seq, $latepenaltyA, $user, 0);
                }
            }

            $qry = "select sum(isnologin) as isnologin, sum(isnombrkout) as isnombrkout, sum(isnombrkin) as isnombrkin, sum(isnolunchout) as isnolunchout, sum(isnolunchin) as isnolunchin, 
                sum(isnopbrkout) as isnopbrkout, sum(isnopbrkin) as isnopbrkin, sum(isnologout) as isnologout, sum(isnologpin) as isnologpin, sum(isnologunder) as isnologunder
                from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "'";
            $penalties =  $this->coreFunctions->opentable($qry);


            if (!empty($penalties)) {
                // //no log-in penalty
                // if ($penalties[0]->isnologbreak != 0) {
                //     $noBreakPenaltyA = $penalties[0]->isnologbreak * $noBreakPenalty;
                //     $this->addTimeSheetAccount($empid, $batchid, $noBreakPenalty_accnt[0]->line, $val->dateid, $noBreakPenalty_accnt[0]->uom, $noBreakPenalty_accnt[0]->seq, $noBreakPenaltyA, $user, 0);
                // }

                //no log-in penalty
                $genPenaltyIn = $penalties[0]->isnologin;
                if ($genPenaltyIn != 0) {
                    $genPenaltyA = $genPenaltyIn;
                    $this->addTimeSheetAccount($empid, $batchid, $nologinpenalty_accnt[0]->line, $val->dateid, $nologinpenalty_accnt[0]->uom, $nologinpenalty_accnt[0]->seq, $genPenaltyA, $user, 0);
                }

                //no undertime
                if ($penalties[0]->isnologunder != 0) {
                    $noUnderPenaltyA = $penalties[0]->isnologunder * $noUnderPenalty;
                    $this->addTimeSheetAccount($empid, $batchid, $noUnderPenalty_accnt[0]->line, $val->dateid, $noUnderPenalty_accnt[0]->uom, $noUnderPenalty_accnt[0]->seq, $noUnderPenaltyA, $user, 0);
                }

                //no log-out penalty
                if ($penalties[0]->isnologout != 0) {
                    $noOutPenaltyA = $penalties[0]->isnologout * $noOutPenalty;
                    $this->addTimeSheetAccount($empid, $batchid, $noOutPenalty_accnt[0]->line, $val->dateid, $noOutPenalty_accnt[0]->uom, $noOutPenalty_accnt[0]->seq, $noOutPenaltyA, $user, 0);
                }

                //no generic penalty
                $genPenalty = $penalties[0]->isnombrkout + $penalties[0]->isnombrkin + $penalties[0]->isnolunchout + $penalties[0]->isnolunchin + $penalties[0]->isnopbrkout + $penalties[0]->isnopbrkin + $penalties[0]->isnologpin;
                if ($genPenalty != 0) {
                    $genPenaltyA = $genPenalty * $genericPenalty;
                    $this->addTimeSheetAccount($empid, $batchid, $penalty_accnt[0]->line, $val->dateid, $penalty_accnt[0]->uom, $penalty_accnt[0]->seq, $genPenaltyA, $user, 0);
                }
            }

            return ['status' => true, 'msg' => ''];
        } catch (Exception $e) {
            echo $e;
            return ['status' => false, 'msg' => $e];
        }
    }


    public function computeemptimesheet_onesky($batchid, $batchdate, $empid, $start, $end, $user, $batch, $params, $skiptimecard)
    {
        try {
            $classrate = $this->coreFunctions->getfieldvalue("employee", "classrate", "empid=?", [$empid]);

            if ($skiptimecard) goto skipTimecardHere;

            $qry = "delete from timesheet where empid=" . $empid . " and batchid=" . $batchid;
            $this->coreFunctions->execqry($qry);

            if (substr($batch, -2, 2) == '13') {
                return ['status' => true, 'msg' => ''];
            }

            //timecard data - no ndiff computation
            $qry = "select empid, sum(reghrs) as reghrs, sum(absdays) as absdays, sum(latehrs) as latehrs, sum(underhrs) as underhrs, sum(othrs) as othrs, sum(ndiffhrs) as ndiffhrs,
            sum(ndiffot) as ndiffot, sum(restday) as restday, sum(restot) as restot, sum(leg) as leg, sum(legot) as legot, sum(sp) as sp, sum(spot) as spot, sum(legabsent) as legabsent, sum(latehrs2) as latehrs2,
            sum(rdsat) as rdsat, sum(rdsatot) as rdsatot
            from (
            select empid, reghrs, absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='WORKING'
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, reghrs as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='RESTDAY' and RDapprvd=1  and dayname(dateid)<>'Saturday'
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, othrs as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='RESTDAY' and RDOTapprvd=1  and dayname(dateid)<>'Saturday'
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, reghrs-absdays as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG' and LEGapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, absdays as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG' and LEGapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, othrs as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='LEG' and LEGOTapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, reghrs as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='SP' and SPapprvd=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs,  0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, othrs as spot, 0 as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='SP' and SPOTapprvd=1
            union all
            select  empid, 0 as reghrs, 0 as absdays, 0 as latehrs, 0 as underhrs, othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and dateid between '" . $start . "' and '" . $end . "' and otapproved=1
            union all
            select  empid, 0 as reghrs, 0 as absdays, 0 as latehrs, 0 as underhrs, earlyothrs as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and dateid between '" . $start . "' and '" . $end . "' and earlyotapproved=1
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, reghrs as rdsat, 0 as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='RESTDAY' and RDapprvd=1 and dayname(dateid)='Saturday'
            union all
            select empid, 0 as reghrs, 0 as absdays, latehrs, underhrs, 0 as othrs, 0 as ndiffhrs, 0 as ndiffot, 0 as restday, 0 as leg, 0 as legot, 0 as restot, 0 as sp, 0 as spot, 0 as legabsent, latehrs2, 0 as rdsat, othrs as rdsatot
            from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and daytype='RESTDAY' and RDOTapprvd=1 and dayname(dateid)='Saturday'
            ) as t group by empid ";
            $timecard = $this->coreFunctions->opentable($qry);

            //all MDS account
            $qry = "select " . $batchid . " as batchid, " . $empid . " as empid, line as acnoid, '" . $batchdate . "' as dateid,
                alias, uom, qty as multiplier, code, seq from paccount where `type` = 'MDS' order by seq";
            $timesheet = $this->coreFunctions->opentable($qry);

            $blnAddOnReg = 0;
            $absDays = 0;

            //inserting data from timecard
            foreach ($timesheet as $key =>  $val) {
                $qty = 0;
                $qty2 = 0;

                if (!empty($timecard)) {
                    switch ($val->alias) {
                        case 'WORKING':
                            $qty = $timecard[0]->reghrs;
                            break;
                        case 'ABSENT':
                            $qty = $timecard[0]->absdays;
                            break;
                        case 'LATE':
                            $qty = $timecard[0]->latehrs;
                            break;
                        case 'UNDERTIME':
                            $qty = $timecard[0]->underhrs;
                            break;
                        case 'OTREG':
                            $qty = $timecard[0]->othrs;
                            break;
                        case 'NDIFF':
                            $qty = $timecard[0]->ndiffot;
                            break;
                        case 'NDIFFS':
                            $qty = $timecard[0]->ndiffhrs;
                            break;
                        case 'RESTDAY':
                            $qty = $timecard[0]->restday;
                            break;
                        case 'RESTDAYSAT':
                            $qty = $timecard[0]->rdsat;
                            break;
                        case 'OTRES':
                            $qty = $timecard[0]->restot;
                            break;
                        case 'OTSAT':
                            $qty = $timecard[0]->rdsatot;
                            break;
                        case 'LEG':
                            $qty = $timecard[0]->leg;
                            //if ($classrate == 'D') $blnAddOnReg += 8;
                            break;
                        case 'LEGALOT':
                            $qty = $timecard[0]->legot;
                            break;
                        case 'SP':
                            $qty = $timecard[0]->sp;
                            break;
                        case 'SPECIALOT':
                            $qty = $timecard[0]->spot;
                            break;
                        case 'LEGUN':
                            $qty = $timecard[0]->legabsent;
                            break;
                        default:
                            $qty = 0;
                            break;
                    }
                }

                $this->addTimeSheetAccount($empid, $batchid, $val->acnoid, $val->dateid, $val->uom, $val->seq, $qty, $user, $qty2);
            }

            skipTimecardHere:

            if ($skiptimecard) {
                $totalworking = $this->coreFunctions->datareader("select ts.qty as value from timesheet as ts left join paccount as pa on pa.line=ts.acnoid where ts.empid=" . $empid . " and pa.alias='WORKING' and ts.batchid=" . $params['dataparams']['batchid']);
                $restday = $this->coreFunctions->datareader("select ts.qty as value from timesheet as ts left join paccount as pa on pa.line=ts.acnoid where ts.empid=" . $empid . " and pa.alias='RESTDAY' and ts.batchid=" . $params['dataparams']['batchid']);
                $totalworking = (($totalworking + $restday) / 8);
                $absDays = $this->coreFunctions->datareader("select ts.qty as value from timesheet as ts left join paccount as pa on pa.line=ts.acnoid where ts.empid=" . $empid . " and pa.alias='ABSENT' and ts.batchid=" . $params['dataparams']['batchid'], [], '', true);
            } else {
                $totalworking = $this->coreFunctions->datareader("select count(reghrs) as value from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and reghrs<>0");
                $absDays = $this->coreFunctions->datareader("select ifnull(sum(absdays),0) as value from timecard where empid=" . $empid . " and date(dateid) between '" . $start . "' and '" . $end . "' and absdays<>0", [], '', true);
            }

            $mealdeduc = $this->coreFunctions->getfieldvalue("employee", "mealdeduc", "empid=" . $empid, [], '', true);
            if ($mealdeduc != 0) {
                $mealdeducdata = $this->coreFunctions->opentable("select * from paccount where code='PT37'");
                if (!empty($mealdeducdata)) {
                    if ($totalworking != 0) {
                        $this->addTimeSheetAccount($empid, $batchid, $mealdeducdata[0]->line, $batchdate, $mealdeducdata[0]->uom, $mealdeducdata[0]->seq, $totalworking, $user, 0);
                    }
                }
            }

            $qry = "select allowance from allowsetup where empid=" . $empid . " and date('" . $end . "') between date(dateeffect) and date(dateend) and allowance<>0 order by dateend desc";
            $allowance = $this->coreFunctions->opentable($qry);
            if (!empty($allowance)) {
                $allowdaysdata = $this->coreFunctions->opentable("select * from paccount where code='PT93'");
                if (!empty($allowdaysdata)) {
                    $netdays = ($totalworking - ($absDays / 8));
                    $this->addTimeSheetAccount($empid, $batchid, $allowdaysdata[0]->line, $batchdate, $allowdaysdata[0]->uom, $allowdaysdata[0]->seq, $netdays, $user, 0);
                }
            }

            if (!$skiptimecard) {
                if ($classrate == 'D') {
                    $blnAddOnReg = $this->coreFunctions->datareader("select count(reghrs) as value from timecard where empid=" . $empid . " and reghrs<>0 and daytype='LEG' and date(dateid) BETWEEN '" . $start . "' and '" . $end . "'", [], '', true);
                    if ($blnAddOnReg != 0) {
                        $blnAddOnReg = $blnAddOnReg * 8;
                        $qry = "update timesheet as ts join paccount as p on p.line=ts.acnoid set ts.qty=ts.qty+" . $blnAddOnReg . " where ts.empid=" . $empid . " and date(ts.dateid) BETWEEN '" . $start . "' and '" . $end . "' and p.alias='WORKING'";
                        $this->coreFunctions->execqry($qry);
                    }
                }
            }

            return ['status' => true, 'msg' => ''];
        } catch (Exception $e) {
            echo $e;
            return ['status' => false, 'msg' => $e];
        }
    }


    public function generatePayTranCurrent($config)
    {
        $companyid = $config['params']['companyid'];

        $checkacount = $this->checkpayrollacounts();
        if (!$checkacount['status']) {
            return ['status' => false, 'msg' => $checkacount['msg'], 'action' => 'load'];
        }

        $status = true;
        $msg = '';
        $batchid = $config['params']['dataparams']['batchid'];
        $batchcode = $config['params']['dataparams']['batch'];
        $batchdate = $config['params']['dataparams']['batchdate'];
        $checkall = $config['params']['dataparams']['checkall'];
        $empid = $config['params']['dataparams']['empid'];
        $start = $config['params']['dataparams']['startdate'];
        $end = $config['params']['dataparams']['enddate'];
        $is13th = $config['params']['dataparams']['is13'];
        $start13th = $config['params']['dataparams']['13start'];
        $end13th = $config['params']['dataparams']['13end'];
        $adjustm = $config['params']['dataparams']['adjustm'];
        $blnWholeDeduction = false;

        if (isset($config['params']['dataparams']['withoutdeduction'])) {
            $blndeduction = !$config['params']['dataparams']['withoutdeduction'];
        } else {
            if ($companyid == 30) { //RT
                if (substr($config['params']['dataparams']['batch'], -1) == "4") { //Week4 Govnt Deduction
                    $blndeduction = 1;
                    $blnWholeDeduction = true;
                } else {
                    $blndeduction = 0;
                }
            } else {
                $blndeduction = 1;
            }
        }

        $employee = $this->getAllowEmployees($config, 1);

        if (empty($employee)) {
            $pgroup = isset($config['params']['dataparams']['tpaygroupname']) ? $config['params']['dataparams']['tpaygroupname'] : $config['params']['dataparams']['pgroup'];
            $paymode = isset($config['params']['dataparams']['fullwordpaymode']) ? $config['params']['dataparams']['fullwordpaymode'] : $config['params']['dataparams']['paymode'];
            return ['status' => false, 'msg' => 'No employee exists in this selected payroll batch, PayGroup: ' . $pgroup . ', PayMode: ' . $paymode, 'action' => 'load'];
        }

        if ($checkall) {
            foreach ($employee as $key =>  $val) {
                switch ($companyid) {
                    case 58: //cdo
                        $result = $this->insertPayTranCurrent_cdo($config['params'], $val, $batchid, $batchdate, $is13th, $adjustm, $batchcode, $start, $end, $start13th,  $end13th, $blndeduction, $blnWholeDeduction);
                        break;
                    case 62: //onesky
                        $result = $this->insertPayTranCurrent_onesky($config['params'], $val, $batchid, $batchdate, $is13th, $adjustm, $batchcode, $start, $end, $start13th,  $end13th, $blndeduction, $blnWholeDeduction);
                        break;
                    default:
                        $result = $this->insertPayTranCurrent($config['params'], $val, $batchid, $batchdate, $is13th, $adjustm, $batchcode, $start, $end, $start13th,  $end13th, $blndeduction, $blnWholeDeduction);
                        break;
                }
                if (!$result['status']) {
                    if ($msg == '') {
                        $msg .= "Failed to compute the timesheet.<br>";
                    }
                    $msg .= $val->empname . " - " . $result['msg'] . ".<br>";
                    $status = false;
                }
            }
        } else {
            switch ($companyid) {
                case 58: //cdo
                    $result = $this->insertPayTranCurrent_cdo($config['params'], $employee[0], $batchid, $batchdate, $is13th, $adjustm, $batchcode, $start, $end, $start13th,  $end13th, $blndeduction, $blnWholeDeduction);
                    break;
                case 62: //onesky
                    $result = $this->insertPayTranCurrent_onesky($config['params'], $employee[0], $batchid, $batchdate, $is13th, $adjustm, $batchcode, $start, $end, $start13th,  $end13th, $blndeduction, $blnWholeDeduction);
                    break;
                default:
                    $result = $this->insertPayTranCurrent($config['params'], $employee[0], $batchid, $batchdate, $is13th, $adjustm, $batchcode, $start, $end, $start13th,  $end13th, $blndeduction, $blnWholeDeduction);
                    break;
            }
            if (!$result['status']) {
                $msg .= "Failed to compute " . $employee[0]->empname . " timesheet - " . $result['msg'] . ".<br>";
                $status = false;
            }
        }

        if ($msg == '') {
            $msg = 'Timesheet computations have been completed.';
        }

        return ['status' => $status, 'msg' => $msg, 'action' => 'load'];
    }

    public function getAllowEmployees($config, $paytran = 0, $processtype = '')
    {
        $companyid = $config['params']['companyid'];
        $pgroup = $config['params']['dataparams']['pgroup'];
        $checkall = $config['params']['dataparams']['checkall'];
        $paymode = $config['params']['dataparams']['paymode'];

        // from batch params
        $divid = isset($config['params']['dataparams']['divid']) ? $config['params']['dataparams']['divid'] : 0;
        $branchid = isset($config['params']['dataparams']['branchid']) ? $config['params']['dataparams']['branchid'] : 0;

        $filterEmp = '';
        $empid = 0;
        if (!$checkall) {
            $empid = $config['params']['dataparams']['empid'];
            $filterEmp = " and emp.empid=" . $empid;
        }

        $addon = "";

        $filtergrp = "";
        if ($pgroup != "") {
            $filtergrp = " and ifnull(payg.line,0)='" . $pgroup . "'";
            if ($empid != 0) $filtergrp = "";
        }

        if ($processtype != '') {
            switch ($processtype) {
                case 'POSTINOUT':
                    // from employee params
                    $divid = $config['params']['dataparams']['empdivid'];
                    $branchid = $config['params']['dataparams']['empbranchid'];
                    break;
            }
        }
        if ($companyid == 58) { //cdo
            if ($divid != 0)  $filterEmp .= " and emp.divid=" . $divid;
            if ($branchid != 0)  $filterEmp .= " and emp.branchid=" . $branchid;
        }

        if ($companyid == 62) { //onesky
            $divid = 0;
            if ($paytran == 0) $divid = $config['params']['dataparams']['empdivid'];
            if ($divid != 0)  $filterEmp .= " and emp.divid=" . $divid;
            if ($pgroup = "") $filtergrp = "";

            $addon = ", emp.mealdeduc";
        }

        if ($paytran) {
            $addon .= ", emp.chktin, emp.chksss, emp.chkphealth, emp.chkpibig, emp.paymode, emp.sssdef, emp.philhdef, emp.pibigdef, emp.wtaxdef, emp.cola,
            emp.sss, emp.tin, emp.phic, emp.hdmf,  emp.paymode, emp.teu, emp.nodeps ";
        }
        $emplvl = $this->othersClass->checksecuritylevel($config);
        $qry = "select emp.empid, concat(emp.emplast, ', ', emp.empfirst, ' ', emp.empmiddle) as empname, emp.classrate " . $addon . " 
        from employee as emp left join paygroup as payg on payg.line=emp.paygroup
        where emp.isactive=1 and emp.paymode = '" . $paymode . "' " . $filterEmp . $filtergrp . " and emp.level in $emplvl order by emp.empid";
        // $this->coreFunctions->LogConsole($qry);
        // Logger($qry);
        return $this->coreFunctions->opentable($qry);
    }

    private  function insertPayTranCurrent($params, $emp, $batchid, $batchdate, $is13th, $adjustm, $batchcode, $startdate, $enddate, $start13, $end13, $blndeduction, $blnWholeDeduction)
    {
        $msg = "";

        //$blnWholeDeduction - use in RT, only deduct on last payroll week 4

        try {

            $dcManualOtherEarn = isset($params['dataparams']['cur']) ? $params['dataparams']['cur'] : 0;

            if ($dcManualOtherEarn == '' || $dcManualOtherEarn == null) {
                $dcManualOtherEarn = 0;
            }

            $batch13 = false;

            if (substr($params['dataparams']['batch'], -2, 2) == '13') {
                $batch13 = true;
            }

            $this->coreFunctions->execqry("delete from " . $this->tablename . " where empid=? and batchid=?", "delete", [$emp->empid, $batchid]);

            if ($batch13) {
                $amt13th = $this->bonusC($emp->empid, $batchid, $batchdate, $startdate, $enddate);

                $earning = 0;
                $earningadv = 0;
                if ($amt13th > 0) {
                    $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                    $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                    if ($params['companyid'] == 43) { //mighty
                        $this->resetTripIncentive($emp->empid, $batchid);
                    }

                    $earning = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], false);
                    $earningadv = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], true);
                }

                $this->addProccessAccount($emp->empid, $batchid, 'PPBLE', $batchdate, $amt13th + $earning + $earningadv, 0, 99);
            } else {
                $rate = $this->coreFunctions->opentable("select basicrate, `type` from ratesetup where empid=" . $emp->empid . " and date('" . $enddate . "') between date(dateeffect) and date(dateend) order by dateend desc limit 1");

                if (empty($rate)) {
                    $rate = $this->coreFunctions->opentable("select basicrate, `type` from ratesetup where empid=" . $emp->empid . " and date(dateend)='9999-12-31' order by dateend desc limit 1");
                }

                if (!empty($rate)) {

                    if ($rate[0]->type == '') {
                        $msg = 'Missing CLASS RATE';
                        goto exitHere;
                    }

                    if ($emp->paymode == '') {
                        $msg = 'Missing PAYMODE';
                        goto exitHere;
                    }

                    $qry = "select t.dateid, t.qty, t.uom, t.acnoid, p.codename, p.qty as multiplier, p.istax, p.alias, p.pseq, t.qty2
                            from timesheet as t left join paccount as p on p.line=t.acnoid
                            where t.empid=" . $emp->empid . " and t.batchid=" . $batchid . " and t.qty<>0 order by p.pseq";
                    $data = $this->coreFunctions->opentable($qry);

                    $grossPay = [0, 0, 0];
                    $deduction = 0;

                    $contridivisor = 2;

                    switch ($emp->paymode) {
                        case 'S':
                            $contridivisor = 2;
                            break;
                        case 'W':
                            $contridivisor = 4;
                            break;
                        default:
                            $contridivisor = 1;
                            break;
                    }

                    //$blnWholeDeduction - use in RT, only deduct on last payroll week 4
                    $emp->philhdef = number_format($emp->philhdef / ($blnWholeDeduction ? 1 : $contridivisor), 2, '.', '');
                    $emp->pibigdef = number_format($emp->pibigdef / ($blnWholeDeduction ? 1 : $contridivisor), 2, '.', '');
                    $emp->wtaxdef = number_format($emp->wtaxdef / ($blnWholeDeduction ? 1 : $contridivisor), 2, '.', '');

                    $chksss = $emp->chksss;
                    $chktin = $emp->chktin;
                    $chkphic = $emp->chkphealth;
                    $chkhdmf = $emp->chkpibig;

                    $dayRate = 0;
                    $dayRate1 = 0;
                    $dayRate2 = 0;
                    $basicRate = $rate[0]->basicrate;
                    $basicRateNew = 0;
                    $pieceAmt = 0;
                    $salary = 0;
                    $cola = 0;
                    $amt13th = 0;
                    $daysInMonth = $this->companysetup->getpayroll_daysInMonth($params);

                    $msalary = 0;
                    $other = 0;
                    $amtDeduc = 0;
                    $qtywork = 0;
                    $qtyworking = 0;
                    $qtyabsent = 0;
                    $qtylate = 0;
                    $qtyundertime = 0;
                    $qtySL = 0;
                    $qtyVL = 0;
                    $amtabsent = 0;
                    $amtlate = 0;
                    $amtundertime = 0;
                    $amtSL = 0;
                    $amtVL = 0;
                    $amtLegSP = 0;
                    $hrsLegSP = 0;
                    $amtRestday = 0;
                    $amtOT = 0;
                    $amtSPUnwork = 0;
                    $amtLGUnwork = 0;

                    $tripIncentive = 0;
                    $operatorIncentive = 0;

                    $is13th = false;

                    // if ($rate[0]->type == 'M') { //8.10.2021 - change to paymode - scenario is government contributions were deducted on 2nd cut-off
                    switch ($emp->paymode) {
                        case 'M':
                            $dayRate = $basicRate / $daysInMonth;
                            $dayRate1 = $basicRate;
                            $dayRate2 = $basicRate / $daysInMonth;
                            $cola = $emp->cola / $daysInMonth;
                            break;

                        case 'S':
                            if ($rate[0]->type == 'D') {
                                goto dailyratehere;
                            }
                            $dayRate = $basicRate / $daysInMonth;
                            $dayRate1 = $basicRate / 2;
                            $dayRate2 = $basicRate / $daysInMonth;
                            $cola = $emp->cola / $daysInMonth;
                            break;

                        case 'D':
                            dailyratehere:
                            $dayRate = $basicRate;
                            $dayRate1 = $basicRate / 8;
                            $dayRate2 = $basicRate;
                            $cola = $emp->cola;
                            break;

                        default:
                            defaulthere:
                            $dayRate = $basicRate;
                            $dayRate1 = $basicRate / 8;
                            $dayRate2 = $basicRate;
                            $basicRate = $basicRate / ($daysInMonth / 2);
                            $basicRateNew = $basicRate / ($daysInMonth / 2);
                            $cola = $emp->cola;
                            break;
                    }


                    if (!$is13th) {
                        foreach ($data as $key =>  $val) {
                            $rawdata = [];
                            $rawdata['empid'] = $emp->empid;
                            $rawdata['batchid'] = $batchid;
                            $rawdata['qty'] = $val->qty;
                            $rawdata['qty2'] = $val->qty2;
                            $rawdata['uom'] = $val->uom;
                            $rawdata['dateid'] = $batchdate;
                            $rawdata['acnoid'] = $val->acnoid;
                            $rawdata['db'] = 0;
                            $rawdata['cr'] = 0;
                            $rawdata['torder'] = $val->pseq;


                            switch ($val->alias) {
                                case 'WORKING':
                                    switch ($emp->paymode) {
                                        case 'M':
                                        case 'S':
                                            if ($rate[0]->type == 'D') {
                                                goto defaulthere2;
                                            }
                                            $salary = $dayRate1;
                                            break;
                                        default:
                                            defaulthere2:
                                            $salary = $dayRate1 * $val->qty;
                                            break;
                                    }
                                    $qtywork = $val->qty;
                                    $qtyworking += $val->qty;
                                    $msalary = $salary;
                                    $rawdata['uom'] = 'PESO';
                                    $rawdata['db'] = $salary;
                                    $rawdata['torder'] = 0;
                                    $rawdata['acnoid'] = $this->coreFunctions->datareader("select line as value from paccount where alias='BSA'");
                                    break;

                                case 'PIECE':
                                    $rawdata['db'] = $pieceAmt;
                                    $rawdata['torder'] = 2;
                                    break;

                                case 'LATE':
                                    if ($params['companyid'] == 58) { //cdo - no lates converted due to penalty
                                        $rawdata['cr'] = 0;
                                    } else {
                                        $rawdata['cr'] = round(($dayRate / 8), 6) * $val->qty;
                                    }
                                    $qtylate += $val->qty;
                                    $amtlate += round($rawdata['cr'], 2);
                                    break;

                                case 'UNDERTIME':
                                    $rawdata['cr'] = round(($dayRate / 8), 6) * $val->qty;
                                    $qtyundertime += $val->qty;
                                    $amtundertime += round($rawdata['cr'], 2);
                                    break;

                                case 'ABSENT':
                                    $rawdata['cr'] = round(($dayRate / 8), 6) * $val->qty;
                                    $qtyabsent += $val->qty;
                                    $amtabsent += round($rawdata['cr'], 2);
                                    break;

                                case "SL":
                                case "ML":
                                case "PL":
                                case "BL":
                                case "FL":
                                case "EL":
                                case "VIL":
                                case "VL":
                                    $rawdata['db'] = round(($dayRate / 8), 6) * $val->qty;
                                    if ($val->alias == 'VL') {
                                        $qtyVL += $val->qty;
                                        $amtVL += round($rawdata['db'], 2);
                                    } else {
                                        $qtySL += $val->qty;
                                        $amtSL += round($rawdata['db'], 2);
                                    }
                                    break;

                                case 'OTREG':
                                case 'LEGALOT':
                                case 'NDIFF':
                                case 'SPECIALOT':
                                case 'OTRES':
                                case 'OTSAT':
                                    $rawdata['db'] = round(($dayRate / 8), 6) * $val->qty * $val->multiplier;
                                    $amtOT += round($rawdata['db'], 2);
                                    break;

                                case 'NDIFFS':
                                case 'LEG':
                                case 'LEGUN':
                                case 'SP':
                                case 'SP100':
                                case 'RESTDAY':
                                case 'RESTDAYSAT':
                                    $rawdata['db'] = round(($dayRate / 8), 6) * $val->qty * $val->multiplier;
                                    $qtyworking += $val->qty;
                                    if ($val->alias != 'RESTDAY' && $val->alias != 'NDIFFS') {
                                        $amtLegSP += round($rawdata['db'], 2);
                                        $hrsLegSP += $val->qty;
                                    } else {
                                        $amtRestday += round($rawdata['db'], 2);
                                    }
                                    switch ($emp->paymode) {
                                        case 'M':
                                        case 'S':
                                            break;
                                        default:
                                            $unwork = [];
                                            $unworkdata = [];
                                            switch ($val->alias) {
                                                case 'SP':
                                                    if ($val->qty > 0) {
                                                        $unwork = $this->coreFunctions->opentable("select line, qty, uom, pseq from paccount where alias='SPUN'");
                                                        $multiplierSPUnwork = $unwork[0]->qty;
                                                        $amtSPUnworkday = round(($dayRate / 8), 6) * $val->qty * $multiplierSPUnwork;
                                                        $amtSPUnwork += $amtSPUnworkday;
                                                        if ($amtSPUnworkday != 0) {
                                                            $unworkdata = [
                                                                'empid' => $emp->empid,
                                                                'batchid' => $batchid,
                                                                'dateid' => $batchdate,
                                                                'acnoid' => $unwork[0]->line,
                                                                'qty' => $val->qty,
                                                                'db' => $amtSPUnworkday,
                                                                'uom' => $unwork[0]->uom,
                                                                'torder' => $unwork[0]->pseq
                                                            ];
                                                            $this->coreFunctions->sbcinsert($this->tablename, $unworkdata);
                                                        }
                                                    }
                                                    break;
                                            }
                                            break;
                                    }
                                    break;

                                case 'EARNINGS':
                                    $rawdata['db'] = $val->qty;
                                    $other += $val->qty;
                                    break;

                                case 'OTHDEDUCT':
                                    $rawdata['cr'] = abs($val->qty);
                                    $amtDeduc += abs($val->qty);
                                    break;

                                default:
                                    if ($val->alias == '13PAY') {
                                        $is13th = false;
                                    }
                                    if ($val->multiplier > 0) {
                                        $rawdata['db'] = abs($val->qty);
                                        $other += $val->qty;
                                    } else {
                                        $rawdata['cr'] = abs($val->qty);
                                        $other -= $val->qty;
                                    }
                                    break;
                            } // end of switch

                            $insertdata = $this->sanitizelocal($rawdata);
                            if ($insertdata['db'] != 0 || $insertdata['cr'] != 0) {
                                // $this->othersClass->logConsole(json_encode($insertdata));
                                $this->coreFunctions->sbcinsert($this->tablename, $insertdata);
                            }
                        } // end of foreach
                    } // end of !13th

                    //Allowance Setup
                    $allow3 = 0;
                    $nethrs = 0;
                    $qry = "select allowance from allowsetup where empid=" . $emp->empid . " and date('" . $enddate . "') between date(dateeffect) and date(dateend) order by dateend desc limit 1";
                    $result_allowancesetup = $this->coreFunctions->opentable($qry);
                    if ($result_allowancesetup) {
                        $hrsLegSP1 = 0;
                        if ($params['companyid'] == 30) { //RT
                            $hrsLegSP1 = $hrsLegSP;
                        }

                        $nethrs = $qtyworking - $qtyabsent - $qtylate - $hrsLegSP1;
                        if ($nethrs  > 0) {
                            if ($salary != 0) {

                                $allow1 = 0;
                                $allow2 = 0;
                                $qtyAllow = 0;
                                foreach ($result_allowancesetup as $key => $aval) {
                                    $allow1 = $aval->allowance;

                                    if ($allow1 != 0) {
                                        $qtyAllow = $qtyworking;
                                        switch ($emp->paymode) {
                                            case 'M':
                                                $allow2 = $allow1;
                                                $allow3 = $allow3 + $allow2;
                                                $nethrs = 1;
                                                break;
                                            case 'S':
                                                $allow2 = $allow1 / 2;
                                                $allow3 = $allow3 + $allow2;
                                                $nethrs = 1;
                                                break;
                                            default:
                                                $allow2 = ($allow1 / 8) * $nethrs;
                                                $allow3 = $allow3 + $allow2;
                                                break;
                                        }

                                        if ($allow2 != 0) {
                                            $allowid = $this->coreFunctions->datareader("select line as value from paccount where code='PT31'");
                                            if ($allowid != '') {
                                                $this->addProccessAccount($emp->empid, $batchid, 'PT31', $batchdate, $allow2, 0, 0, $allowid, $nethrs);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //COLA
                    if ($cola != 0) {
                        if (($qtyworking - $qtyabsent) > 0) {
                            if ($salary != 0) {

                                if ($rate[0]->type == 'M') {
                                    $cola =  ($cola / 2)  - ((($cola / $daysInMonth) / 8) * ($qtyabsent + $qtylate + $qtyundertime));
                                } else {
                                    $cola = ($cola / 8) * ($qtyworking - $qtyabsent - $qtylate - $qtyundertime);
                                }

                                $this->addProccessAccount($emp->empid, $batchid, 'COLA', $batchdate, $cola, 0);
                            }
                        }
                    }

                    $pieceAmt = $this->getPieceAmt($emp->empid, $startdate, $enddate, $batchid);
                    if ($pieceAmt != 0) {
                        $this->addProccessAccount($emp->empid, $batchid, 'PIECE', $batchdate, $pieceAmt, 0);
                    }

                    if ($params['companyid'] == 43) { //mighty
                        $tripIncentive = $this->getTripIncentive($emp->empid, $startdate, $enddate, $batchid);
                        if ($tripIncentive != 0) {
                            $this->addProccessAccount($emp->empid, $batchid, 'INCENTIVE1', $batchdate, $tripIncentive, 0, 90);
                        }

                        $operatorIncentive = $this->getOperatorIncentive($emp->empid, $startdate, $enddate, $batchid);
                        if ($operatorIncentive != 0) {
                            $this->addProccessAccount($emp->empid, $batchid, 'INCENTIVE2', $batchdate, $operatorIncentive, 0, 90);
                        }
                    }


                    // COMPUTATION OF SSS,PHIC,HDMF,WHT AND TOTAL PAY
                    if (($qtyworking + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) <= 0) {
                        $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                        $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                        if ($params['companyid'] == 43) { //mighty
                            $this->resetTripIncentive($emp->empid, $batchid);
                            $this->resetOperatorIncentive($emp->empid, $batchid);
                        }

                        $this->coreFunctions->execqry("delete from " . $this->tablename . " where empid=? and batchid=?", "delete", [$emp->empid, $batchid]);
                        if (($qtyworking + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) == 0) {
                            $msg = '1. No details to compute';
                        } else {
                            $msg = 'err1. Computed amount is less than zero';
                        }

                        goto exitHere;
                    }

                    if (($qtywork + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) <= 0) {
                        $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                        $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                        if ($params['companyid'] == 43) { //mighty
                            $this->resetTripIncentive($emp->empid, $batchid);
                            $this->resetOperatorIncentive($emp->empid, $batchid);
                        }

                        $this->coreFunctions->execqry("update leavesetup set batchid=0 where batchid=" . $batchid . "  and empid=" . $emp->empid);

                        $this->coreFunctions->execqry("delete from " . $this->tablename . " where empid=? and batchid=?", "delete", [$emp->empid, $batchid]);
                        if (($qtywork + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) == 0) {
                            $msg = '2. No details to compute';
                        } else {
                            $msg = 'err2. Computed amount is less than zero';
                        }

                        goto exitHere;
                    }

                    if ($salary + $pieceAmt == 0) {
                        $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                        $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                        if ($params['companyid'] == 43) { //mighty
                            $this->resetTripIncentive($emp->empid, $batchid);
                            $this->resetOperatorIncentive($emp->empid, $batchid);
                        }

                        $this->coreFunctions->execqry("update leavesetup set batchid=0 where batchid=" . $batchid . "  and empid=" . $emp->empid);

                        if (!$is13th) {
                            $msg = 'err3. Computed salary + piece amount is zero';
                            goto exitHere;
                        }
                    }

                    if ($is13th) {
                        $amt13th = $this->bonusC($emp->empid, $batchid, $batchdate, $start13, $end13);
                    } else {

                        $grossPay[0] = $salary + $amtVL + $amtSL + $amtRestday - $amtabsent - $amtlate - $amtundertime;
                        $basicRate = $salary + $amtVL + $amtSL + $amtLegSP +  $amtSPUnwork + $amtLGUnwork - $amtabsent - $amtlate - $amtundertime;

                        if (($amtOT  + $amtLegSP +  $amtSPUnwork + $amtLGUnwork) == 0) {
                            $grossPay[1] = $grossPay[0];
                        } else {
                            $grossPay[1] += ($amtOT + $amtLegSP + $amtSPUnwork + $amtLGUnwork);
                            if ($grossPay[1] == 0) {
                                $grossPay[1] = $grossPay[0];
                            } else {
                                $grossPay[1] += $grossPay[0];
                            }
                        }

                        $grossPay[2] = $grossPay[1];

                        if ($blndeduction) {

                            $batch1Deduct = $this->vtranSelectQry($emp->empid, $enddate);
                            $deductionbaseSSSHDMF = $batch1Deduct;
                            $deductiontax = $deductionbaseSSSHDMF;

                            if ($emp->paymode == 'M') {
                                $taxamt = $deductionbaseSSSHDMF;
                            } else {
                                $taxamt = $this->vtranSelectQry($emp->empid, $enddate, $batchid);
                            }

                            //SSS
                            if ($chksss) {
                                if ($emp->sss != '') {
                                    if ($emp->sssdef != 0) {
                                        $this->addProccessAccount($emp->empid, $batchid, 'YSE', $batchdate, 0, $emp->sssdef / ($blnWholeDeduction ? 1 : 2), 87);
                                        $bracket = $this->coreFunctions->opentable("select sssee,ssser,eccer from ssstab where sssee=?", [$emp->sssdef]);

                                        if (!empty($bracket)) {
                                            $this->addProccessAccount($emp->empid, $batchid, 'YSR', $batchdate, 0, $bracket[0]->ssser / ($blnWholeDeduction ? 1 : 2), 88);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YER', $batchdate, 0, $bracket[0]->eccer / ($blnWholeDeduction ? 1 : 2), 89);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIS', $batchdate, ($bracket[0]->ssser + $bracket[0]->eccer) / ($blnWholeDeduction ? 1 : 2), 0, 90);
                                        }

                                        $grossPay[2] -= ($emp->sssdef / ($blnWholeDeduction ? 1 : $contridivisor));
                                    } else {

                                        $bracket = $this->coreFunctions->opentable("select sssee,ssser,eccer from ssstab where " . $deductionbaseSSSHDMF . " between range1 and range2");
                                        if (!empty($bracket)) {
                                            $ssse = $this->vtranSelectQryAlias($emp->empid, $enddate, "YSE", "cr");
                                            $sssr = $this->vtranSelectQryAlias($emp->empid, $enddate, "YSR", "cr");
                                            $ssser = $this->vtranSelectQryAlias($emp->empid, $enddate, "YER", "cr");

                                            $this->addProccessAccount($emp->empid, $batchid, 'YSE', $batchdate, 0, $bracket[0]->sssee - $ssse, 87);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YSR', $batchdate, 0, $bracket[0]->ssser - $sssr, 88);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YER', $batchdate, 0, $bracket[0]->eccer - $ssser, 89);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIS', $batchdate, ($bracket[0]->ssser - $sssr) + ($bracket[0]->eccer - $ssser), 0, 90);

                                            $deduction += ($bracket[0]->sssee - $ssse);
                                            $grossPay[2] -= ($bracket[0]->sssee - $ssse);
                                            $deductiontax -= ($bracket[0]->sssee - $ssse);
                                        }
                                    }
                                }
                            } // end of SSS

                            //PHILHEALTH
                            if ($chkphic) {
                                if ($emp->phic != '') {
                                    if ($emp->philhdef != 0) {
                                        $this->addProccessAccount($emp->empid, $batchid, 'YME', $batchdate, 0, $emp->philhdef, 91);
                                        $this->addProccessAccount($emp->empid, $batchid, 'YMR', $batchdate, 0, $emp->philhdef, 92);
                                        $this->addProccessAccount($emp->empid, $batchid, 'YIM', $batchdate, $emp->philhdef, 0, 93);
                                        $grossPay[2] -= $emp->philhdef;
                                    } else {
                                        $phicamt = $deductionbaseSSSHDMF;
                                        $bracket = $this->coreFunctions->opentable("select phicee,phicer from phictab where " . $phicamt . " BETWEEN range1 AND range2");

                                        $phie = $this->vtranSelectQryAlias($emp->empid, $enddate, "YME", "cr");
                                        $phir = $this->vtranSelectQryAlias($emp->empid, $enddate, "YMR", "cr");

                                        if (!empty($bracket)) {

                                            $this->addProccessAccount($emp->empid, $batchid, 'YME', $batchdate, 0, $bracket[0]->phicee - $phie, 91);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YMR', $batchdate, 0, $bracket[0]->phicee - $phir, 92);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIM', $batchdate, $bracket[0]->phicee - $phir, 0, 93);

                                            $deduction += ($bracket[0]->phicee - $phie);
                                            $deductiontax += ($bracket[0]->phicee - $phie);
                                            $grossPay[2] -= ($bracket[0]->phicee - $phie);
                                        } else {

                                            $phicmulti = $this->coreFunctions->datareader("select phictotal as value from phictab where range1=0");
                                            if ($phicmulti) {
                                                $phicmulti = $phicmulti / 100;
                                            } else {
                                                $phicmulti = 0;
                                            }

                                            $phicee = round(($phicamt * $phicmulti) / 2, 2);

                                            $this->addProccessAccount($emp->empid, $batchid, 'YME', $batchdate, 0, $phicee - $phie, 91);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YMR', $batchdate, 0, $phicee - $phir, 92);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIM', $batchdate, $phicee - $phir, 0, 93);

                                            $deduction += ($phicee - $phie);
                                            $deductiontax += ($phicee - $phie);
                                            $grossPay[2] -= ($phicee - $phie);
                                        }
                                    }
                                }
                            } // end of PHILHEALTH                        

                            //PAG-IBIG
                            if ($chkhdmf) {
                                if ($emp->hdmf != '') {
                                    if ($emp->pibigdef != 0) {
                                        $this->addProccessAccount($emp->empid, $batchid, 'YPE', $batchdate, 0, $emp->pibigdef, 94);
                                        $this->addProccessAccount($emp->empid, $batchid, 'YPR', $batchdate, 0, $emp->pibigdef, 95);
                                        $this->addProccessAccount($emp->empid, $batchid, 'YIP', $batchdate, $emp->pibigdef, 0, 96);

                                        $grossPay[2] -= $emp->pibigdef;
                                    } else {
                                        $hdmfamt = 0;
                                        $hdmfamt2 = 0;
                                        $prevhdmf = $this->vtranSelectQryAlias($emp->empid, $enddate, "YPE", "cr");

                                        if ($prevhdmf != 0) {
                                            $hdmfamt = $prevhdmf;
                                        }

                                        if ($hdmfamt < 100) {
                                            if ($deductionbaseSSSHDMF >= 5000) {
                                                $hdmfamt = 100 - $hdmfamt;
                                            } else {
                                                $hdmfamt2 = round($deductionbaseSSSHDMF * 0.02, 2);
                                                if (($hdmfamt + $hdmfamt2) > 100) {
                                                    $hdmfamt = 100 - $hdmfamt;
                                                } else {
                                                    $hdmfamt = $hdmfamt2;
                                                }
                                            }
                                        } elseif ($hdmfamt >= 100) {
                                            $hdmfamt = 0;
                                        }

                                        if ($hdmfamt > 0) {
                                            $this->addProccessAccount($emp->empid, $batchid, 'YPE', $batchdate, 0, $hdmfamt, 94);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YPR', $batchdate, 0, $hdmfamt, 95);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIP', $batchdate, $hdmfamt, 0, 96);

                                            $deduction += $hdmfamt;
                                            $deductiontax += $hdmfamt;
                                            $grossPay[2] -= $hdmfamt;
                                        }
                                    }
                                }
                            } // end of PAG IBIG

                            //TAX
                            if ($dayRate2 > 0) {
                                if ($chktin) {
                                    if ($emp->tin != '') {
                                        if ($adjustm) {
                                            $annualtax = $this->annualtax($emp->empid, date('Y', strtotime($batchdate)), $params);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YWT', $batchdate, 0, $annualtax, 97);
                                            $grossPay[2] -= $annualtax;
                                        } else {
                                            if ($emp->wtaxdef != 0) {
                                                // if (!str_ends_with($batchcode, '5')) { // not working on Php 8.0 below
                                                if (substr($batchcode, -1) != '5') {
                                                    $this->addProccessAccount($emp->empid, $batchid, 'YWT', $batchdate, 0, $emp->wtaxdef, 98);
                                                    $grossPay[2] -= $emp->wtaxdef;
                                                }
                                            } else {

                                                //from vtran
                                                if ($rate[0]->type == 'M') {
                                                    $phie = $this->vtranSelectQryAlias($emp->empid, $enddate, "YME", "cr");
                                                    $ssse = $this->vtranSelectQryAlias($emp->empid, $enddate, "YSE", "cr");
                                                    $hdmf = $this->vtranSelectQryAlias($emp->empid, $enddate, "YPE", "cr");
                                                    $whte = $this->vtranSelectQryAlias($emp->empid, $enddate, "'YWT'", "cr", 1);
                                                } else {
                                                    $phie = $this->vtranSelectQryAlias($emp->empid, "", "YME", "cr", 0, $batchid);
                                                    $ssse = $this->vtranSelectQryAlias($emp->empid, "", "YSE", "cr", 0, $batchid);
                                                    $hdmf = $this->vtranSelectQryAlias($emp->empid, "", "YPE", "cr", 0, $batchid);
                                                    $whte = $this->vtranSelectQryAlias($emp->empid, "", "'YWT'", "cr", 1, $batchid);
                                                }
                                                $wtax = 0;
                                                $lesstax = $this->gettax($taxamt - ($phie + $ssse + $hdmf), $emp->paymode);
                                                $this->addProccessAccount($emp->empid, $batchid, 'YWT', $batchdate, 0, $lesstax - $whte, 98);
                                                $wtax = $lesstax - $whte;
                                                $grossPay[2] -= $wtax;
                                            }
                                        }
                                    }
                                }
                            } // end of tax\


                        } //end of blndeduction


                    } //end of is13th

                    $earning = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], false);
                    $earningadv = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], true);
                    $leavetransamt = $this->getLeaveTrans($emp->empid, $startdate, $enddate, $dayRate, $batchid, $batchdate);

                    if ($dcManualOtherEarn != 0) {
                        $ManualOtherEarnId = $this->coreFunctions->datareader("select line as value from paccount where code='PT91'");
                        if ($ManualOtherEarnId != '') {
                            $this->addProccessAccount($emp->empid, $batchid, 'PT91', $batchdate, $dcManualOtherEarn, 0, 0, $ManualOtherEarnId);
                        }
                    }

                    $grossPay[2] = $grossPay[2] + $allow3 + $earning + $earningadv + $leavetransamt;
                    $totalpay = $grossPay[2] + $pieceAmt + $cola + $amt13th +  $other - $amtDeduc + $dcManualOtherEarn + $tripIncentive + $operatorIncentive;

                    $this->addProccessAccount($emp->empid, $batchid, 'PPBLE', $batchdate, $totalpay, 0, 99);
                } else {
                    $msg = 'Missing rate';
                }
            }



            $this->coreFunctions->execqry("delete from " . $this->tablename . " where db=0 and cr=0", "delete");
            $this->coreFunctions->sbcupdate("standardsetup", ['camt' => 0], ['empid' => $emp->empid]);
            $this->coreFunctions->sbcupdate("standardsetupadv", ['camt' => 0], ['empid' => $emp->empid]);
        } catch (Exception $e) {
            echo $e;
        }
        exitHere:
        return ['status' => $msg == '', 'msg' => $msg];
    }

    private  function insertPayTranCurrent_cdo($params, $emp, $batchid, $batchdate, $is13th, $adjustm, $batchcode, $startdate, $enddate, $start13, $end13, $blndeduction, $blnWholeDeduction)
    {
        $msg = "";

        try {

            $dcManualOtherEarn = isset($params['dataparams']['cur']) ? $params['dataparams']['cur'] : 0;

            if ($dcManualOtherEarn == '' || $dcManualOtherEarn == null) {
                $dcManualOtherEarn = 0;
            }

            $batch13 = false;

            if (substr($params['dataparams']['batch'], -2, 2) == '13') {
                $batch13 = true;
            }

            $this->coreFunctions->execqry("delete from " . $this->tablename . " where empid=? and batchid=?", "delete", [$emp->empid, $batchid]);

            if ($batch13) {
                $amt13th = $this->bonusC($emp->empid, $batchid, $batchdate, $startdate, $enddate);

                $earning = 0;
                $earningadv = 0;
                if ($amt13th > 0) {
                    $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                    $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                    $earning = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], false);
                    $earningadv = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], true);
                }

                $this->addProccessAccount($emp->empid, $batchid, 'PPBLE', $batchdate, $amt13th + $earning + $earningadv, 0, 99);
            } else {
                $rate = $this->coreFunctions->opentable("select basicrate, `type` from ratesetup where empid=" . $emp->empid . " and date('" . $enddate . "') between date(dateeffect) and date(dateend) order by dateend desc limit 1");

                if (empty($rate)) {
                    $rate = $this->coreFunctions->opentable("select basicrate, `type` from ratesetup where empid=" . $emp->empid . " and date(dateend)='9999-12-31' order by dateend desc limit 1");
                }

                if (!empty($rate)) {

                    if ($rate[0]->type == '') {
                        $msg = 'Missing CLASS RATE';
                        goto exitHere;
                    }

                    if ($emp->paymode == '') {
                        $msg = 'Missing PAYMODE';
                        goto exitHere;
                    }

                    $qry = "select t.dateid, t.qty, t.uom, t.acnoid, p.codename, p.qty as multiplier, p.istax, p.alias, p.pseq, t.qty2
                            from timesheet as t left join paccount as p on p.line=t.acnoid
                            where t.empid=" . $emp->empid . " and t.batchid=" . $batchid . " and t.qty<>0 order by p.pseq";
                    $data = $this->coreFunctions->opentable($qry);

                    $grossPay = [0, 0, 0];
                    $deduction = 0;

                    $contridivisor = 2;

                    switch ($emp->paymode) {
                        case 'S':
                            $contridivisor = 2;
                            break;
                        case 'W':
                            $contridivisor = 4;
                            break;
                        default:
                            $contridivisor = 1;
                            break;
                    }

                    //$blnWholeDeduction - use in RT, only deduct on last payroll week 4
                    // $emp->philhdef = number_format($emp->philhdef / ($blnWholeDeduction ? 1 : $contridivisor), 2, '.', '');
                    // $emp->pibigdef = number_format($emp->pibigdef / ($blnWholeDeduction ? 1 : $contridivisor), 2, '.', '');
                    // $emp->wtaxdef = number_format($emp->wtaxdef / ($blnWholeDeduction ? 1 : $contridivisor), 2, '.', '');

                    $chksss = $emp->chksss;
                    $chktin = $emp->chktin;
                    $chkphic = $emp->chkphealth;
                    $chkhdmf = $emp->chkpibig;

                    $dayRate = 0;
                    $dayRate1 = 0;
                    $dayRate2 = 0;
                    $basicRate = $rate[0]->basicrate;
                    $basicRateNew = 0;
                    $pieceAmt = 0;
                    $salary = 0;
                    $cola = $emp->cola;
                    $amt13th = 0;
                    $daysInMonth = $this->companysetup->getpayroll_daysInMonth($params);

                    $msalary = 0;
                    $other = 0;
                    $amtDeduc = 0;
                    $qtywork = 0;
                    $qtyworking = 0;
                    $qtyabsent = 0;
                    $qtylate = 0;
                    $qtyundertime = 0;
                    $qtySL = 0;
                    $qtyVL = 0;
                    $amtabsent = 0;
                    $amtlate = 0;
                    $amtundertime = 0;
                    $amtSL = 0;
                    $amtVL = 0;
                    $amtLegSP = 0;
                    $hrsLegSP = 0;
                    $amtRestday = 0;
                    $amtOT = 0;
                    $amtSPUnwork = 0;
                    $amtLGUnwork = 0;

                    $tripIncentive = 0;
                    $operatorIncentive = 0;

                    $is13th = false;

                    // if ($rate[0]->type == 'M') { //8.10.2021 - change to paymode - scenario is government contributions were deducted on 2nd cut-off
                    switch ($emp->paymode) {
                        case 'M':
                            $dayRate = ($basicRate / 313) * 12;
                            $dayRate1 = $basicRate;
                            $dayRate2 = ($basicRate / 313) * 12;
                            // $cola = ($emp->cola / 313) * 12;
                            break;

                        case 'S':
                            if ($rate[0]->type == 'D') {
                                goto dailyratehere;
                            }
                            $dayRate = ($basicRate / 313) * 12;
                            $dayRate1 = $basicRate / 2;
                            $dayRate2 = ($basicRate / 313) * 12;
                            // $cola = ($emp->cola / 313) * 12;
                            break;

                        case 'D':
                            dailyratehere:
                            $dayRate = $basicRate;
                            $dayRate1 = $basicRate / 8;
                            $dayRate2 = $basicRate;
                            $cola = $emp->cola;
                            break;

                        default:
                            defaulthere:
                            $dayRate = $basicRate;
                            $dayRate1 = $basicRate / 8;
                            $dayRate2 = $basicRate;
                            $basicRate = $basicRate / ($daysInMonth / 2);
                            $basicRateNew = $basicRate / ($daysInMonth / 2);
                            $cola = $emp->cola;
                            break;
                    }


                    if (!$is13th) {
                        foreach ($data as $key =>  $val) {
                            $rawdata = [];
                            $rawdata['empid'] = $emp->empid;
                            $rawdata['batchid'] = $batchid;
                            $rawdata['qty'] = $val->qty;
                            $rawdata['qty2'] = $val->qty2;
                            $rawdata['uom'] = $val->uom;
                            $rawdata['dateid'] = $batchdate;
                            $rawdata['acnoid'] = $val->acnoid;
                            $rawdata['db'] = 0;
                            $rawdata['cr'] = 0;
                            $rawdata['torder'] = $val->pseq;


                            switch ($val->alias) {
                                case 'WORKING':
                                    switch ($emp->paymode) {
                                        case 'M':
                                        case 'S':
                                            if ($rate[0]->type == 'D') {
                                                goto defaulthere2;
                                            }
                                            $salary = $dayRate1;
                                            break;
                                        default:
                                            defaulthere2:
                                            $salary = $dayRate1 * $val->qty;
                                            break;
                                    }
                                    $qtywork = $val->qty;
                                    $qtyworking += $val->qty;
                                    $msalary = $salary;
                                    $rawdata['uom'] = 'PESO';
                                    $rawdata['db'] = $salary;
                                    $rawdata['torder'] = 0;
                                    $rawdata['acnoid'] = $this->coreFunctions->datareader("select line as value from paccount where alias='BSA'");
                                    break;

                                case 'PIECE':
                                    $rawdata['db'] = $pieceAmt;
                                    $rawdata['torder'] = 2;
                                    break;

                                case 'LATE':
                                    $rawdata['cr'] = 0; //no lates converted due to penalty
                                    // $rawdata['cr'] = round(($dayRate / 8), 6) * $val->qty;
                                    $qtylate += $val->qty;
                                    $amtlate += round($rawdata['cr'], 2);
                                    break;

                                case 'UNDERTIME':
                                    $rawdata['cr'] = round(($dayRate / 8), 6) * $val->qty;
                                    $qtyundertime += $val->qty;
                                    $amtundertime += round($rawdata['cr'], 2);
                                    break;

                                case 'ABSENT':
                                    $rawdata['cr'] = round(($dayRate / 8), 6) * $val->qty;
                                    $qtyabsent += $val->qty;
                                    $amtabsent += round($rawdata['cr'], 2);
                                    break;

                                case "SL":
                                case "ML":
                                case "PL":
                                case "BL":
                                case "FL":
                                case "EL":
                                case "VIL":
                                case "VL":
                                    $rawdata['db'] = round(($dayRate / 8), 6) * $val->qty;
                                    if ($val->alias == 'VL') {
                                        $qtyVL += $val->qty;
                                        $amtVL += round($rawdata['db'], 2);
                                    } else {
                                        $qtySL += $val->qty;
                                        $amtSL += round($rawdata['db'], 2);
                                    }
                                    break;

                                case 'OTREG':
                                case 'LEGALOT':
                                case 'NDIFF':
                                case 'SPECIALOT':
                                case 'OTRES':
                                case 'OTSAT':
                                    $rawdata['db'] = round(($dayRate / 8), 6) * $val->qty * $val->multiplier;
                                    $amtOT += round($rawdata['db'], 2);
                                    break;

                                case 'NDIFFS':
                                case 'LEG':
                                case 'LEGUN':
                                case 'SP':
                                case 'SP100':
                                case 'RESTDAY':
                                case 'RESTDAYSAT':
                                    if ($val->alias == 'LEG' && $rate[0]->type == 'D') {
                                        $val->multiplier = 1;
                                    }
                                    $rawdata['db'] = round(($dayRate / 8), 6) * $val->qty * $val->multiplier;
                                    $qtyworking += $val->qty;
                                    if ($val->alias != 'RESTDAY' && $val->alias != 'NDIFFS') {
                                        $amtLegSP += round($rawdata['db'], 2);
                                        $hrsLegSP += $val->qty;
                                    } else {
                                        $amtRestday += round($rawdata['db'], 2);
                                    }
                                    switch ($emp->paymode) {
                                        case 'M':
                                        case 'S':
                                            break;
                                        default:
                                            $unwork = [];
                                            $unworkdata = [];
                                            switch ($val->alias) {
                                                case 'SP':
                                                    if ($val->qty > 0) {
                                                        $unwork = $this->coreFunctions->opentable("select line, qty, uom, pseq from paccount where alias='SPUN'");
                                                        $multiplierSPUnwork = $unwork[0]->qty;
                                                        $amtSPUnworkday = round(($dayRate / 8), 6) * $val->qty * $multiplierSPUnwork;
                                                        $amtSPUnwork += $amtSPUnworkday;
                                                        if ($amtSPUnworkday != 0) {
                                                            $unworkdata = [
                                                                'empid' => $emp->empid,
                                                                'batchid' => $batchid,
                                                                'dateid' => $batchdate,
                                                                'acnoid' => $unwork[0]->line,
                                                                'qty' => $val->qty,
                                                                'db' => $amtSPUnworkday,
                                                                'uom' => $unwork[0]->uom,
                                                                'torder' => $unwork[0]->pseq
                                                            ];
                                                            $this->coreFunctions->sbcinsert($this->tablename, $unworkdata);
                                                        }
                                                    }
                                                    break;
                                            }
                                            break;
                                    }
                                    break;

                                case 'EARNINGS':
                                    $rawdata['db'] = $val->qty;
                                    $other += $val->qty;
                                    break;

                                case 'OTHDEDUCT':
                                    $rawdata['cr'] = abs($val->qty);
                                    $amtDeduc += abs($val->qty);
                                    break;

                                case 'NOLOGINPENALTY':
                                    $rawdata['cr'] = round(($dayRate / 2), 6) * $val->qty;
                                    $other -= $rawdata['cr'];
                                    break;

                                default:
                                    if ($val->alias == '13PAY') {
                                        $is13th = false;
                                    }
                                    if ($val->multiplier > 0) {
                                        $rawdata['db'] = abs($val->qty);
                                        $other += $val->qty;
                                    } else {
                                        $rawdata['cr'] = abs($val->qty);
                                        $other -= $val->qty;
                                    }
                                    break;
                            } // end of switch

                            $insertdata = $this->sanitizelocal($rawdata);
                            if ($insertdata['db'] != 0 || $insertdata['cr'] != 0) {
                                // $this->othersClass->logConsole(json_encode($insertdata));
                                $this->coreFunctions->sbcinsert($this->tablename, $insertdata);
                            }
                        } // end of foreach
                    } // end of !13th

                    //Allowance Setup
                    $allow3 = 0;
                    $nethrs = 0;

                    // $qry = "select allowance, acnoid from allowsetup where empid=" . $emp->empid . " and date('" . $enddate . "') between date(dateeffect) and date(dateend) order by dateend desc";
                    $qry = "select s.allowance, s.acnoid from allowsetup as s join paccount as pa on pa.line=s.acnoid 
                        where s.empid=" . $emp->empid . " and date('" . $batchdate . "') between date(s.dateeffect) and date(s.dateend) and pa.ispayroll=1
                        order by s.dateend desc";

                    $result_allowancesetup = $this->coreFunctions->opentable($qry);
                    if ($result_allowancesetup) {
                        $hrsLegSP1 = 0;
                        $nethrs = $qtyworking - $qtyabsent - $qtylate - $hrsLegSP1;
                        if ($nethrs  > 0) {
                            if ($salary != 0) {

                                $allow1 = 0;
                                $allow2 = 0;
                                $qtyAllow = 0;
                                foreach ($result_allowancesetup as $key => $aval) {
                                    $allow1 = $aval->allowance;

                                    if ($allow1 != 0) {
                                        $qtyAllow = $qtyworking;
                                        switch ($emp->paymode) {
                                            case 'M':
                                                $allow2 = $allow1;
                                                $allow3 = $allow3 + $allow2;
                                                $nethrs = 1;
                                                break;
                                            case 'S':
                                                $allow2 = $allow1 / 2;
                                                $allow3 = $allow3 + $allow2;
                                                $nethrs = 1;
                                                break;
                                            default:
                                                $allow2 = ($allow1 / 8) * $nethrs;
                                                $allow3 = $allow3 + $allow2;
                                                break;
                                        }

                                        if ($allow2 != 0) {
                                            $allowcode = $this->coreFunctions->datareader("select code as value from paccount where line=" . $aval->acnoid, [], '', true);
                                            if ($allowcode != "") {
                                                $this->addProccessAccount($emp->empid, $batchid, $allowcode, $batchdate, $allow2, 0, 0, $aval->acnoid, $nethrs);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //COLA
                    if ($cola != 0) {
                        if (($qtyworking - $qtyabsent) > 0) {
                            if ($salary != 0) {

                                // if ($rate[0]->type == 'M') {
                                //     $cola =  ($cola / 2)  - ((($cola / $daysInMonth) / 8) * ($qtyabsent + $qtylate + $qtyundertime));
                                // } else {
                                //     $cola = ($cola / 8) * ($qtyworking - $qtyabsent - $qtylate - $qtyundertime);
                                // }
                                if ($rate[0]->type == 'D') {
                                    $cola = ($cola / 8) * ($qtyworking - $qtyabsent - $qtylate - $qtyundertime);
                                } else {
                                    $cola =  ($cola / 2);
                                }

                                $this->addProccessAccount($emp->empid, $batchid, 'COLA', $batchdate, $cola, 0);
                            }
                        }
                    }

                    $pieceAmt = $this->getPieceAmt($emp->empid, $startdate, $enddate, $batchid);
                    if ($pieceAmt != 0) {
                        $this->addProccessAccount($emp->empid, $batchid, 'PIECE', $batchdate, $pieceAmt, 0);
                    }

                    // COMPUTATION OF SSS,PHIC,HDMF,WHT AND TOTAL PAY
                    if (($qtyworking + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) <= 0) {
                        $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                        $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                        $this->coreFunctions->execqry("delete from " . $this->tablename . " where empid=? and batchid=?", "delete", [$emp->empid, $batchid]);
                        if (($qtyworking + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) == 0) {
                            $msg = '1. No details to compute';
                        } else {
                            $msg = 'err1. Computed amount is less than zero<br>';
                            $msg .= 'hrs: ' . $qtyworking . ' leave:' . $qtyVL + $qtySL . ' absent: ' . $qtyabsent;
                        }

                        goto exitHere;
                    }

                    if (($qtywork + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) <= 0) {
                        $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                        $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                        $this->coreFunctions->execqry("update leavesetup set batchid=0 where batchid=" . $batchid . "  and empid=" . $emp->empid);

                        $this->coreFunctions->execqry("delete from " . $this->tablename . " where empid=? and batchid=?", "delete", [$emp->empid, $batchid]);
                        if (($qtywork + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) == 0) {
                            $msg = '2. No details to compute';
                        } else {
                            $msg = 'err2. Computed amount is less than zero';
                        }

                        goto exitHere;
                    }

                    if ($salary + $pieceAmt == 0) {
                        $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                        $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                        $this->coreFunctions->execqry("update leavesetup set batchid=0 where batchid=" . $batchid . "  and empid=" . $emp->empid);

                        if (!$is13th) {
                            $msg = 'err3. Computed salary + piece amount is zero';
                            goto exitHere;
                        }
                    }

                    $leavetransamt = $this->getLeaveTrans($emp->empid, $startdate, $enddate, $dayRate, $batchid, $batchdate);

                    if ($is13th) {
                        $amt13th = $this->bonusC($emp->empid, $batchid, $batchdate, $start13, $end13);
                    } else {

                        //checking of absent amout of total days is equal to total leave days
                        $computed_absent_qty = $this->coreFunctions->datareader("select pay.qty as value from paytrancurrent as pay left join paccount as pa on pa.line=pay.acnoid where pay.empid=" . $emp->empid . " and pay.batchid=" . $batchid . " and pa.alias='ABSENT'", [], '', true);
                        $computed_leave_qty = $this->coreFunctions->datareader("select sum(pay.qty) as value from paytrancurrent as pay left join paccount as pa on pa.line=pay.acnoid where pay.empid=" . $emp->empid . " and pay.batchid=" . $batchid . " and pa.alias in ('SL','ML','BL','FL','EL','VIL','VL')", [], '', true);
                        if ($computed_absent_qty != 0 &&  ($computed_absent_qty / 8) == $computed_leave_qty) {
                            $computed_leave_amt = $this->coreFunctions->datareader("select sum(pay.db) as value from paytrancurrent as pay left join paccount as pa on pa.line=pay.acnoid where pay.empid=" . $emp->empid . " and pay.batchid=" . $batchid . " and pa.alias in ('SL','ML','BL','FL','EL','VIL','VL')", [], '', true);
                            $amtabsent = $computed_leave_amt;
                            $this->coreFunctions->execqry("update paytrancurrent as pay left join paccount as pa on pa.line=pay.acnoid set pay.cr=" . $amtabsent . " where pay.empid=" . $emp->empid . " and pay.batchid=" . $batchid . " and pa.alias='ABSENT'");
                        }


                        $grossPay[0] = $salary + $amtVL + $amtSL + $amtRestday - $amtabsent - $amtlate - $amtundertime;
                        $basicRate = $salary + $amtVL + $amtSL + $amtLegSP +  $amtSPUnwork + $amtLGUnwork - $amtabsent - $amtlate - $amtundertime;

                        if (($amtOT  + $amtLegSP +  $amtSPUnwork + $amtLGUnwork) == 0) {
                            $grossPay[1] = $grossPay[0];
                        } else {
                            $grossPay[1] += ($amtOT + $amtLegSP + $amtSPUnwork + $amtLGUnwork);
                            if ($grossPay[1] == 0) {
                                $grossPay[1] = $grossPay[0];
                            } else {
                                $grossPay[1] += $grossPay[0];
                            }
                        }

                        $grossPay[2] = $grossPay[1];

                        if ($blndeduction) {

                            $batch1Deduct = $this->vtranSelectQry($emp->empid, $enddate);
                            $deductionbaseSSSHDMF = $batch1Deduct;
                            $deductiontax = $deductionbaseSSSHDMF;

                            if ($emp->classrate == 'D') {
                                $sssamt = round(($rate[0]->basicrate * 313) / 12, 2);
                            } else {
                                $sssamt = $rate[0]->basicrate;
                            }

                            // if ($emp->paymode == 'M') {
                            //     $taxamt = $deductionbaseSSSHDMF;
                            // } else {
                            $taxamt = $this->vtranSelectQry($emp->empid, $enddate, $batchid);
                            // }

                            //SSS
                            if ($chksss) {
                                $weekno = substr($batchcode, -1);
                                if ($weekno <= 2) {
                                    goto SkipSSSHere;
                                }

                                if ($emp->sss != '') {
                                    if ($emp->sssdef != 0) {
                                        $this->addProccessAccount($emp->empid, $batchid, 'YSE', $batchdate, 0, $emp->sssdef / ($blnWholeDeduction ? 1 : 2), 87);
                                        $bracket = $this->coreFunctions->opentable("select sssee,ssser,eccer from ssstab where sssee=?", [$emp->sssdef]);

                                        if (!empty($bracket)) {
                                            $this->addProccessAccount($emp->empid, $batchid, 'YSR', $batchdate, 0, $bracket[0]->ssser / ($blnWholeDeduction ? 1 : 2), 88);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YER', $batchdate, 0, $bracket[0]->eccer / ($blnWholeDeduction ? 1 : 2), 89);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIS', $batchdate, ($bracket[0]->ssser + $bracket[0]->eccer) / ($blnWholeDeduction ? 1 : 2), 0, 90);
                                        }

                                        $grossPay[2] -= ($emp->sssdef / ($blnWholeDeduction ? 1 : $contridivisor));
                                    } else {

                                        $bracket = $this->coreFunctions->opentable("select sssee,ssser,eccer from ssstab where " . $sssamt . " between range1 and range2");
                                        if (!empty($bracket)) {
                                            $ssse = $this->vtranSelectQryAlias($emp->empid, $batchdate, "YSE", "cr");
                                            $sssr = $this->vtranSelectQryAlias($emp->empid, $batchdate, "YSR", "cr");
                                            $ssser = $this->vtranSelectQryAlias($emp->empid, $batchdate, "YER", "cr");

                                            $this->addProccessAccount($emp->empid, $batchid, 'YSE', $batchdate, 0, $bracket[0]->sssee - $ssse, 87);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YSR', $batchdate, 0, $bracket[0]->ssser - $sssr, 88);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YER', $batchdate, 0, $bracket[0]->eccer - $ssser, 89);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIS', $batchdate, ($bracket[0]->ssser - $sssr) + ($bracket[0]->eccer - $ssser), 0, 90);

                                            $deduction += ($bracket[0]->sssee - $ssse);
                                            $grossPay[2] -= ($bracket[0]->sssee - $ssse);
                                            $deductiontax -= ($bracket[0]->sssee - $ssse);
                                        }
                                    }
                                }
                            } // end of SSS
                            SkipSSSHere:

                            // skip deduction of phic and hdmf due to first cut-off already process in their old system
                            if ($params['dataparams']['batch'] == 'PS20260104')  goto SkipPagibigHere;

                            //PHILHEALTH
                            if ($chkphic) {
                                $phicamt = $deductionbaseSSSHDMF;

                                $weekno = substr($batchcode, -1);
                                // if ($weekno > 2) {
                                // } else {
                                //     if ($emp->classrate == 'D') {
                                //         $phicamt = round(($rate[0]->basicrate * 313) / 12, 2);
                                //     } else {
                                //         $phicamt = $rate[0]->basicrate;
                                //     }
                                // }

                                if ($emp->classrate == 'D') {
                                    $phicamt = round(($rate[0]->basicrate * 313) / 12, 2);
                                } else {
                                    $phicamt = $rate[0]->basicrate;
                                }

                                if ($emp->phic != '') {
                                    if ($emp->philhdef != 0) {
                                        if ($weekno > 2) {
                                        } else {
                                            $this->addProccessAccount($emp->empid, $batchid, 'YME', $batchdate, 0, $emp->philhdef, 91);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YMR', $batchdate, 0, $emp->philhdef, 92);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIM', $batchdate, $emp->philhdef, 0, 93);
                                            $grossPay[2] -= $emp->philhdef;
                                        }
                                    } else {

                                        $bracket = $this->coreFunctions->opentable("select phicee,phicer from phictab where " . $phicamt . " BETWEEN range1 AND range2");

                                        // $this->coreFunctions->LogConsole("select phicee,phicer from phictab where " . $phicamt . " BETWEEN range1 AND range2");

                                        $phie = 0;
                                        $phir = 0;

                                        if (!empty($bracket)) {

                                            $phie = $this->vtranSelectQryAlias($emp->empid, $batchdate, "YME", "cr");
                                            $phir = $this->vtranSelectQryAlias($emp->empid, $batchdate, "YMR", "cr");

                                            $this->coreFunctions->LogConsole($bracket[0]->phicee . ' : ' . $phie);

                                            $this->addProccessAccount($emp->empid, $batchid, 'YME', $batchdate, 0, $bracket[0]->phicee - $phie, 91);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YMR', $batchdate, 0, $bracket[0]->phicee - $phir, 92);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIM', $batchdate, $bracket[0]->phicee - $phir, 0, 93);

                                            $deduction += ($bracket[0]->phicee - $phie);
                                            $deductiontax += ($bracket[0]->phicee - $phie);
                                            $grossPay[2] -= ($bracket[0]->phicee - $phie);
                                        } else {

                                            $phicmulti = $this->coreFunctions->datareader("select phictotal as value from phictab where range1=0");
                                            if ($phicmulti) {
                                                $phicmulti = $phicmulti / 100;
                                            } else {
                                                $phicmulti = 0;
                                            }

                                            $phicee = round(($phicamt * $phicmulti) / 2, 2);

                                            $this->addProccessAccount($emp->empid, $batchid, 'YME', $batchdate, 0, $phicee - $phie, 91);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YMR', $batchdate, 0, $phicee - $phir, 92);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIM', $batchdate, $phicee - $phir, 0, 93);

                                            $deduction += ($phicee - $phie);
                                            $deductiontax += ($phicee - $phie);
                                            $grossPay[2] -= ($phicee - $phie);
                                        }
                                    }
                                }
                            } // end of PHILHEALTH      
                            SkipPhilHealthHere:

                            //PAG-IBIG
                            if ($chkhdmf) {

                                $pagibigamt = $deductionbaseSSSHDMF;

                                $weekno = substr($batchcode, -1);
                                // if ($weekno > 2) {
                                // } else {
                                //     if ($emp->classrate == 'D') {
                                //         $pagibigamt = round(($rate[0]->basicrate * 313) / 12, 2);
                                //     } else {
                                //         $pagibigamt = $rate[0]->basicrate;
                                //     }
                                // }

                                if ($emp->classrate == 'D') {
                                    $pagibigamt = round(($rate[0]->basicrate * 313) / 12, 2);
                                } else {
                                    $pagibigamt = $rate[0]->basicrate;
                                }

                                if ($emp->hdmf != '') {
                                    if ($emp->pibigdef != 0) {
                                        if ($weekno > 2) {
                                        } else {
                                            $this->addProccessAccount($emp->empid, $batchid, 'YPE', $batchdate, 0, $emp->pibigdef, 94);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YPR', $batchdate, 0, $emp->pibigdef, 95);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIP', $batchdate, $emp->pibigdef, 0, 96);
                                            $grossPay[2] -= $emp->pibigdef;
                                        }
                                    } else {
                                        $hdmfamt = 0;
                                        $hdmfamt2 = 0;
                                        $prevhdmf = $this->vtranSelectQryAlias($emp->empid, $batchdate, "YPE", "cr");

                                        if ($prevhdmf != 0) {
                                            $hdmfamt = $prevhdmf;
                                        }

                                        $maxpagigbig = 200;

                                        if ($hdmfamt < $maxpagigbig) {
                                            if ($pagibigamt >= 10000) {
                                                $hdmfamt = $maxpagigbig - $hdmfamt;
                                            } else {
                                                $hdmfamt2 = round($pagibigamt * 0.02, 2);
                                                if (($hdmfamt + $hdmfamt2) > $maxpagigbig) {
                                                    $hdmfamt = $maxpagigbig - $hdmfamt;
                                                } else {
                                                    $hdmfamt = $hdmfamt2;
                                                }
                                            }
                                        } elseif ($hdmfamt >= $maxpagigbig) {
                                            $hdmfamt = 0;
                                        }

                                        if ($hdmfamt > 0) {
                                            $this->addProccessAccount($emp->empid, $batchid, 'YPE', $batchdate, 0, $hdmfamt, 94);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YPR', $batchdate, 0, $hdmfamt, 95);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIP', $batchdate, $hdmfamt, 0, 96);

                                            $deduction += $hdmfamt;
                                            $deductiontax += $hdmfamt;
                                            $grossPay[2] -= $hdmfamt;
                                        }
                                    }
                                }
                            } // end of PAG IBIG
                            SkipPagibigHere:

                            //TAX
                            if ($dayRate2 > 0) {
                                if ($chktin) {
                                    if ($emp->tin != '') {
                                        if ($adjustm) {
                                            $annualtax = $this->annualtax($emp->empid, date('Y', strtotime($batchdate)), $params);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YWT', $batchdate, 0, $annualtax, 97);
                                            $grossPay[2] -= $annualtax;
                                        } else {
                                            if ($emp->wtaxdef != 0) {
                                                // if (!str_ends_with($batchcode, '5')) { // not working on Php 8.0 below
                                                if (substr($batchcode, -1) != '5') {
                                                    $this->addProccessAccount($emp->empid, $batchid, 'YWT', $batchdate, 0, $emp->wtaxdef, 98);
                                                    $grossPay[2] -= $emp->wtaxdef;
                                                }
                                            } else {

                                                //from vtran
                                                if ($rate[0]->type == 'M') {
                                                    $phie = $this->vtranSelectQryAlias($emp->empid, $batchdate, "YME", "cr");
                                                    $ssse = $this->vtranSelectQryAlias($emp->empid, $batchdate, "YSE", "cr");
                                                    $hdmf = $this->vtranSelectQryAlias($emp->empid, $batchdate, "YPE", "cr");
                                                    $whte = $this->vtranSelectQryAlias($emp->empid, $batchdate, "'YWT'", "cr", 1);
                                                } else {
                                                    $phie = $this->vtranSelectQryAlias($emp->empid, "", "YME", "cr", 0, $batchid);
                                                    $ssse = $this->vtranSelectQryAlias($emp->empid, "", "YSE", "cr", 0, $batchid);
                                                    $hdmf = $this->vtranSelectQryAlias($emp->empid, "", "YPE", "cr", 0, $batchid);
                                                    $whte = $this->vtranSelectQryAlias($emp->empid, "", "'YWT'", "cr", 1, $batchid);
                                                }
                                                $wtax = 0;
                                                $lesstax = $this->gettax($taxamt - ($phie + $ssse + $hdmf), $emp->paymode);
                                                $this->addProccessAccount($emp->empid, $batchid, 'YWT', $batchdate, 0, $lesstax - $whte, 98);
                                                $wtax = $lesstax - $whte;
                                                $grossPay[2] -= $wtax;
                                            }
                                        }
                                    }
                                }
                            } // end of tax\


                        } //end of blndeduction


                    } //end of is13th

                    $earning = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], false);
                    $earningadv = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], true);

                    if ($dcManualOtherEarn != 0) {
                        $ManualOtherEarnId = $this->coreFunctions->datareader("select line as value from paccount where code='PT91'");
                        if ($ManualOtherEarnId != '') {
                            $this->addProccessAccount($emp->empid, $batchid, 'PT91', $batchdate, $dcManualOtherEarn, 0, 0, $ManualOtherEarnId);
                        }
                    }

                    $grossPay[2] = $grossPay[2] + $allow3 + $earning + $earningadv + $leavetransamt;
                    $totalpay = $grossPay[2] + $pieceAmt + $cola + $amt13th +  $other - $amtDeduc + $dcManualOtherEarn + $tripIncentive + $operatorIncentive;

                    $this->addProccessAccount($emp->empid, $batchid, 'PPBLE', $batchdate, $totalpay, 0, 99);
                } else {
                    $msg = 'Missing rate';
                }
            }



            $this->coreFunctions->execqry("delete from " . $this->tablename . " where db=0 and cr=0", "delete");
            $this->coreFunctions->sbcupdate("standardsetup", ['camt' => 0], ['empid' => $emp->empid]);
            $this->coreFunctions->sbcupdate("standardsetupadv", ['camt' => 0], ['empid' => $emp->empid]);
        } catch (Exception $e) {
            echo $e;
        }
        exitHere:
        return ['status' => $msg == '', 'msg' => $msg];
    }

    public function insertPayTranCurrent_onesky($params, $emp, $batchid, $batchdate, $is13th, $adjustm, $batchcode, $startdate, $enddate, $start13, $end13, $blndeduction, $blnWholeDeduction)
    {
        $msg = "";

        //$blnWholeDeduction - use in RT, only deduct on last payroll week 4

        try {

            $dcManualOtherEarn = isset($params['dataparams']['cur']) ? $params['dataparams']['cur'] : 0;

            if ($dcManualOtherEarn == '' || $dcManualOtherEarn == null) {
                $dcManualOtherEarn = 0;
            }

            $batch13 = false;

            if (substr($params['dataparams']['batch'], -2, 2) == '13') {
                $batch13 = true;
            }

            $this->coreFunctions->execqry("delete from " . $this->tablename . " where empid=? and batchid=?", "delete", [$emp->empid, $batchid]);

            if ($batch13) {
                $amt13th = $this->bonusC($emp->empid, $batchid, $batchdate, $startdate, $enddate);

                $earning = 0;
                $earningadv = 0;
                if ($amt13th > 0) {
                    $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                    $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                    $earning = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], false);
                    $earningadv = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], true);
                }

                $this->addProccessAccount($emp->empid, $batchid, 'PPBLE', $batchdate, $amt13th + $earning + $earningadv, 0, 99);
            } else {
                $rate = $this->coreFunctions->opentable("select basicrate, `type` from ratesetup where empid=" . $emp->empid . " and date('" . $enddate . "') between date(dateeffect) and date(dateend) order by dateend desc limit 1");

                if (empty($rate)) {
                    $rate = $this->coreFunctions->opentable("select basicrate, `type` from ratesetup where empid=" . $emp->empid . " and date(dateend)='9999-12-31' order by dateend desc limit 1");
                }

                if (!empty($rate)) {

                    if ($rate[0]->type == '') {
                        $msg = 'Missing CLASS RATE';
                        goto exitHere;
                    }

                    if ($emp->paymode == '') {
                        $msg = 'Missing PAYMODE';
                        goto exitHere;
                    }

                    $qry = "select t.dateid, t.qty, t.uom, p.code, t.acnoid, p.codename, p.qty as multiplier, p.istax, p.alias, p.pseq, t.qty2
                            from timesheet as t left join paccount as p on p.line=t.acnoid
                            where t.empid=" . $emp->empid . " and t.batchid=" . $batchid . " and t.qty<>0 order by p.pseq";
                    $data = $this->coreFunctions->opentable($qry);

                    $grossPay = [0, 0, 0];
                    $deduction = 0;

                    $contridivisor = 2;

                    switch ($emp->paymode) {
                        case 'S':
                            $contridivisor = 2;
                            break;
                        case 'W':
                            $contridivisor = 4;
                            break;
                        default:
                            $contridivisor = 1;
                            break;
                    }

                    //$blnWholeDeduction - use in RT, only deduct on last payroll week 4
                    $emp->philhdef = number_format($emp->philhdef / ($blnWholeDeduction ? 1 : $contridivisor), 2, '.', '');
                    $emp->pibigdef = number_format($emp->pibigdef / ($blnWholeDeduction ? 1 : $contridivisor), 2, '.', '');
                    $emp->wtaxdef = number_format($emp->wtaxdef / ($blnWholeDeduction ? 1 : $contridivisor), 2, '.', '');

                    $chksss = $emp->chksss;
                    $chktin = $emp->chktin;
                    $chkphic = $emp->chkphealth;
                    $chkhdmf = $emp->chkpibig;

                    $dayRate = 0;
                    $dayRate1 = 0;
                    $dayRate2 = 0;
                    $basicRate = $rate[0]->basicrate;
                    $basicRateNew = 0;
                    $pieceAmt = 0;
                    $salary = 0;
                    $cola = 0;
                    $mealdeduc = $emp->mealdeduc;
                    $amt13th = 0;
                    $daysInMonth = $this->companysetup->getpayroll_daysInMonth($params);

                    $msalary = 0;
                    $other = 0;
                    $amtDeduc = 0;
                    $qtywork = 0;
                    $qtyworking = 0;
                    $qtyabsent = 0;
                    $qtylate = 0;
                    $qtyundertime = 0;
                    $qtySL = 0;
                    $qtyVL = 0;
                    $qtyDaysAllowance = 0;
                    $amtabsent = 0;
                    $amtlate = 0;
                    $amtundertime = 0;
                    $amtSL = 0;
                    $amtVL = 0;
                    $amtLegSP = 0;
                    $hrsLegSP = 0;
                    $amtRestday = 0;
                    $amtOT = 0;
                    $amtSPUnwork = 0;
                    $amtLGUnwork = 0;

                    $tripIncentive = 0;
                    $operatorIncentive = 0;

                    $is13th = false;

                    // if ($rate[0]->type == 'M') { //8.10.2021 - change to paymode - scenario is government contributions were deducted on 2nd cut-off
                    switch ($emp->paymode) {
                        case 'M':
                            $dayRate = ($basicRate / 313) * 12;
                            $dayRate1 = $basicRate;
                            $dayRate2 = ($basicRate / 313) * 12;
                            $cola = ($emp->cola / 313) * 12;
                            break;

                        case 'S':
                            if ($rate[0]->type == 'D') {
                                goto dailyratehere;
                            }
                            $dayRate = ($basicRate / 313) * 12;
                            $dayRate1 = $basicRate / 2;
                            $dayRate2 = ($basicRate / 313) * 12;
                            $cola = ($emp->cola / 313) * 12;
                            break;

                        case 'D':
                            dailyratehere:
                            $dayRate = $basicRate;
                            $dayRate1 = $basicRate / 8;
                            $dayRate2 = $basicRate;
                            $cola = $emp->cola;
                            break;

                        default:
                            defaulthere:
                            $dayRate = $basicRate;
                            $dayRate1 = $basicRate / 8;
                            $dayRate2 = $basicRate;
                            $basicRate = $basicRate / ($daysInMonth / 2);
                            $basicRateNew = $basicRate / ($daysInMonth / 2);
                            $cola = $emp->cola;
                            break;
                    }


                    if (!$is13th) {
                        foreach ($data as $key =>  $val) {
                            $rawdata = [];
                            $rawdata['empid'] = $emp->empid;
                            $rawdata['batchid'] = $batchid;
                            $rawdata['qty'] = $val->qty;
                            $rawdata['qty2'] = $val->qty2;
                            $rawdata['uom'] = $val->uom;
                            $rawdata['dateid'] = $batchdate;
                            $rawdata['acnoid'] = $val->acnoid;
                            $rawdata['db'] = 0;
                            $rawdata['cr'] = 0;
                            $rawdata['torder'] = $val->pseq;


                            switch ($val->alias) {
                                case 'WORKING':
                                    switch ($emp->paymode) {
                                        case 'M':
                                        case 'S':
                                            if ($rate[0]->type == 'D') {
                                                goto defaulthere2;
                                            }
                                            $salary = $dayRate1;
                                            break;
                                        default:
                                            defaulthere2:
                                            $salary = $dayRate1 * $val->qty;
                                            break;
                                    }
                                    $qtywork = $val->qty;
                                    $qtyworking += $val->qty;
                                    $msalary = $salary;
                                    $rawdata['uom'] = 'PESO';
                                    $rawdata['db'] = $salary;
                                    $rawdata['torder'] = 0;
                                    $rawdata['acnoid'] = $this->coreFunctions->datareader("select line as value from paccount where alias='BSA'");
                                    break;

                                case 'PIECE':
                                    $rawdata['db'] = $pieceAmt;
                                    $rawdata['torder'] = 2;
                                    break;

                                case 'LATE':
                                    $rawdata['cr'] = round(($dayRate / 8), 6) * $val->qty;
                                    $qtylate += $val->qty;
                                    $amtlate += round($rawdata['cr'], 2);
                                    break;

                                case 'UNDERTIME':
                                    $rawdata['cr'] = round(($dayRate / 8), 6) * $val->qty;
                                    $qtyundertime += $val->qty;
                                    $amtundertime += round($rawdata['cr'], 2);
                                    break;

                                case 'ABSENT':
                                    $rawdata['cr'] = round(($dayRate / 8), 6) * $val->qty;
                                    $qtyabsent += $val->qty;
                                    $amtabsent += round($rawdata['cr'], 2);
                                    break;

                                case "SL":
                                case "ML":
                                case "PL":
                                case "BL":
                                case "FL":
                                case "EL":
                                case "VIL":
                                case "VL":
                                case "SIL":
                                    $rawdata['db'] = round(($dayRate / 8), 6) * $val->qty;
                                    if ($val->alias == 'VL') {
                                        $qtyVL += $val->qty;
                                        $amtVL += round($rawdata['db'], 2);
                                    } else {
                                        $qtySL += $val->qty;
                                        $amtSL += round($rawdata['db'], 2);
                                    }
                                    break;

                                case 'OTREG':
                                case 'LEGALOT':
                                case 'NDIFF':
                                case 'SPECIALOT':
                                case 'OTRES':
                                case 'OTSAT':
                                    $rawdata['db'] = round(($dayRate / 8), 6) * $val->qty * $val->multiplier;
                                    $amtOT += round($rawdata['db'], 2);
                                    break;

                                case 'NDIFFS':
                                case 'LEG':
                                case 'LEGUN':
                                case 'SP':
                                case 'SP100':
                                case 'RESTDAY':
                                case 'RESTDAYSAT':
                                    $rawdata['db'] = round(($dayRate / 8), 6) * $val->qty * $val->multiplier;
                                    $qtyworking += $val->qty;
                                    if ($val->alias != 'RESTDAY' && $val->alias != 'NDIFFS') {
                                        $amtLegSP += round($rawdata['db'], 2);
                                        $hrsLegSP += $val->qty;
                                    } else {
                                        $amtRestday += round($rawdata['db'], 2);
                                    }
                                    switch ($emp->paymode) {
                                        case 'M':
                                        case 'S':
                                            break;
                                        default:
                                            $unwork = [];
                                            $unworkdata = [];
                                            switch ($val->alias) {
                                                case 'SP':
                                                    if ($val->qty > 0) {
                                                        $unwork = $this->coreFunctions->opentable("select line, qty, uom, pseq from paccount where alias='SPUN'");
                                                        $multiplierSPUnwork = $unwork[0]->qty;
                                                        $amtSPUnworkday = round(($dayRate / 8), 6) * $val->qty * $multiplierSPUnwork;
                                                        $amtSPUnwork += $amtSPUnworkday;
                                                        if ($amtSPUnworkday != 0) {
                                                            $unworkdata = [
                                                                'empid' => $emp->empid,
                                                                'batchid' => $batchid,
                                                                'dateid' => $batchdate,
                                                                'acnoid' => $unwork[0]->line,
                                                                'qty' => $val->qty,
                                                                'db' => $amtSPUnworkday,
                                                                'uom' => $unwork[0]->uom,
                                                                'torder' => $unwork[0]->pseq
                                                            ];
                                                            $this->coreFunctions->sbcinsert($this->tablename, $unworkdata);
                                                        }
                                                    }
                                                    break;
                                            }
                                            break;
                                    }
                                    break;

                                case 'EARNINGS':
                                    $rawdata['db'] = $val->qty;
                                    $other += $val->qty;
                                    break;

                                case 'OTHDEDUCT':
                                    $rawdata['cr'] = abs($val->qty);
                                    $amtDeduc += abs($val->qty);
                                    break;

                                default:
                                    if ($val->alias == '13PAY') {
                                        $is13th = false;
                                    }

                                    switch ($val->code) {
                                        case "PT37": // meal deduction days
                                            $totalmealdeduc = $mealdeduc  * abs($val->qty);
                                            if ($totalmealdeduc != 0) {
                                                $rawdata['cr'] = $totalmealdeduc;
                                                $amtDeduc += $totalmealdeduc;
                                            }
                                            break;
                                        case "PT93": // allowance days
                                            $qtyDaysAllowance += abs($val->qty);
                                            break;
                                        default:
                                            if ($val->multiplier > 0) {
                                                $rawdata['db'] = abs($val->qty);
                                                $other += $val->qty;
                                            } else {
                                                $rawdata['cr'] = abs($val->qty);
                                                $other -= $val->qty;
                                            }
                                            break;
                                    }


                                    break;
                            } // end of switch

                            $insertdata = $this->sanitizelocal($rawdata);
                            if ($insertdata['db'] != 0 || $insertdata['cr'] != 0) {
                                // $this->othersClass->logConsole(json_encode($insertdata));
                                $this->coreFunctions->sbcinsert($this->tablename, $insertdata);
                            }
                        } // end of foreach
                    } // end of !13th

                    //Allowance Setup
                    $allow3 = 0;
                    $nethrs = 0;
                    $qry = "select allowance, acnoid from allowsetup where empid=" . $emp->empid . " and date('" . $enddate . "') between date(dateeffect) and date(dateend) and allowance<>0 order by dateend desc";
                    $result_allowancesetup = $this->coreFunctions->opentable($qry);
                    if ($result_allowancesetup) {
                        $hrsLegSP1 = 0;

                        $nethrs = $qtyworking - $qtyabsent - $qtylate;
                        if ($nethrs  > 0) {
                            if ($salary != 0) {

                                $allow1 = 0;
                                $allow2 = 0;
                                $qtyAllow = 0;
                                foreach ($result_allowancesetup as $key => $aval) {
                                    $allow1 = $aval->allowance;

                                    if ($allow1 != 0) {
                                        $qtyAllow = 24;
                                        $dailyAllow = $allow1 / $daysInMonth;

                                        switch ($emp->classrate) {
                                            case 'M':
                                                $allow2 = $allow1;
                                                $allow3 = $allow3 + $allow2;
                                                $nethrs = 1;
                                                break;
                                            case 'S':
                                                $allow2 = $allow1 / 2;
                                                $allow2 = $allow2 - ($dailyAllow * ($qtyabsent / 8));
                                                $allow3 = $allow3 + $allow2;
                                                $nethrs = 1;
                                                break;
                                            default:
                                                $dailyAllow = $allow1;
                                                $allow2 = $dailyAllow * $qtyDaysAllowance;
                                                $allow3 = $allow3 + $allow2;
                                                $nethrs = $qtyDaysAllowance;
                                                break;
                                        }

                                        if ($allow2 != 0) {
                                            $allowcode = $this->coreFunctions->datareader("select code as value from paccount where line=" . $aval->acnoid, [], '', true);
                                            if ($allowcode != "") {
                                                $this->addProccessAccount($emp->empid, $batchid, $allowcode, $batchdate, $allow2, 0, 0, $aval->acnoid, $nethrs);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //COLA
                    if ($cola != 0) {
                        if (($qtyworking - $qtyabsent) > 0) {
                            if ($salary != 0) {

                                if ($rate[0]->type == 'M') {
                                    $cola =  ($cola / 2)  - ((($cola / $daysInMonth) / 8) * ($qtyabsent + $qtylate + $qtyundertime));
                                } else {
                                    $cola = ($cola / 8) * ($qtyworking - $qtyabsent - $qtylate - $qtyundertime);
                                }

                                $this->addProccessAccount($emp->empid, $batchid, 'COLA', $batchdate, $cola, 0);
                            }
                        }
                    }

                    // move to compute timesheet, need to display in payroll entry
                    // if ($mealdeduc != 0) {
                    //     $totalmealdeduc = $mealdeduc  * (($qtyworking - $qtyabsent) / 8);
                    //     if ($totalmealdeduc != 0) {
                    //         $this->addProccessAccount($emp->empid, $batchid, 'CODE~PT37', $batchdate, 0, $totalmealdeduc);
                    //         $amtDeduc += $totalmealdeduc;
                    //     }
                    // }

                    $pieceAmt = $this->getPieceAmt($emp->empid, $startdate, $enddate, $batchid);
                    if ($pieceAmt != 0) {
                        $this->addProccessAccount($emp->empid, $batchid, 'PIECE', $batchdate, $pieceAmt, 0);
                    }

                    // COMPUTATION OF SSS,PHIC,HDMF,WHT AND TOTAL PAY
                    if (($qtyworking + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) <= 0) {
                        $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                        $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                        $this->coreFunctions->execqry("delete from " . $this->tablename . " where empid=? and batchid=?", "delete", [$emp->empid, $batchid]);
                        if (($qtyworking + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) == 0) {
                            $msg = '1. No details to compute';
                        } else {
                            $msg = 'err1. Computed amount is less than zero';
                        }

                        goto exitHere;
                    }

                    if (($qtywork + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) <= 0) {
                        $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                        $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                        $this->coreFunctions->execqry("update leavesetup set batchid=0 where batchid=" . $batchid . "  and empid=" . $emp->empid);

                        $this->coreFunctions->execqry("delete from " . $this->tablename . " where empid=? and batchid=?", "delete", [$emp->empid, $batchid]);
                        if (($qtywork + $qtyVL + $qtySL - $qtyabsent + $pieceAmt + $hrsLegSP) == 0) {
                            $msg = '2. No details to compute';
                        } else {
                            $msg = 'err2. Computed amount is less than zero';
                        }

                        goto exitHere;
                    }

                    if ($salary + $pieceAmt == 0) {
                        $this->resetEarningDeduction($emp->empid, $batchid, $params['user']);
                        $this->resetEarningDeductionAdv($emp->empid, $batchid, $params['user']);

                        $this->coreFunctions->execqry("update leavesetup set batchid=0 where batchid=" . $batchid . "  and empid=" . $emp->empid);

                        if (!$is13th) {
                            $msg = 'err3. Computed salary + piece amount is zero';
                            goto exitHere;
                        }
                    }

                    if ($is13th) {
                        $amt13th = $this->bonusC($emp->empid, $batchid, $batchdate, $start13, $end13);
                    } else {

                        $grossPay[0] = $salary + $amtVL + $amtSL + $amtRestday - $amtabsent - $amtlate - $amtundertime;
                        $basicRate = $salary + $amtVL + $amtSL + $amtLegSP +  $amtSPUnwork + $amtLGUnwork - $amtabsent - $amtlate - $amtundertime;

                        if (($amtOT  + $amtLegSP +  $amtSPUnwork + $amtLGUnwork) == 0) {
                            $grossPay[1] = $grossPay[0];
                        } else {
                            $grossPay[1] += ($amtOT + $amtLegSP + $amtSPUnwork + $amtLGUnwork);
                            if ($grossPay[1] == 0) {
                                $grossPay[1] = $grossPay[0];
                            } else {
                                $grossPay[1] += $grossPay[0];
                            }
                        }

                        $grossPay[2] = $grossPay[1];

                        if ($blndeduction) {

                            $batch1Deduct = $this->vtranSelectQry($emp->empid, $enddate);
                            $deductionbaseSSSHDMF = $batch1Deduct;
                            $deductiontax = $deductionbaseSSSHDMF;

                            if ($emp->paymode == 'M') {
                                $taxamt = $deductionbaseSSSHDMF;
                            } else {
                                $taxamt = $this->vtranSelectQry($emp->empid, $enddate, $batchid);
                            }

                            //SSS
                            if ($chksss) {
                                if ($emp->sss != '') {
                                    if ($emp->sssdef != 0) {
                                        $this->addProccessAccount($emp->empid, $batchid, 'YSE', $batchdate, 0, $emp->sssdef / ($blnWholeDeduction ? 1 : 2), 84);
                                        $bracket = $this->coreFunctions->opentable("select sssee,ssser,eccer,mpfee,mpfer from ssstab where sssee=?", [$emp->sssdef]);

                                        if (!empty($bracket)) {
                                            $this->addProccessAccount($emp->empid, $batchid, 'YSR', $batchdate, 0, $bracket[0]->ssser / ($blnWholeDeduction ? 1 : 2), 85);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YER', $batchdate, 0, $bracket[0]->eccer / ($blnWholeDeduction ? 1 : 2), 86);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIS', $batchdate, ($bracket[0]->ssser + $bracket[0]->eccer) / ($blnWholeDeduction ? 1 : 2), 0, 87);
                                        }

                                        $grossPay[2] -= ($emp->sssdef / ($blnWholeDeduction ? 1 : $contridivisor));
                                    } else {

                                        $bracket = $this->coreFunctions->opentable("select sssee,ssser,eccer,mpfee,mpfer from ssstab where " . $deductionbaseSSSHDMF . " between range1 and range2");
                                        if (!empty($bracket)) {
                                            $ssse = $this->vtranSelectQryAlias($emp->empid, $enddate, "YSE", "cr");
                                            $sssr = $this->vtranSelectQryAlias($emp->empid, $enddate, "YSR", "cr");
                                            $ssser = $this->vtranSelectQryAlias($emp->empid, $enddate, "YER", "cr");

                                            $mpfee = $this->vtranSelectQryAlias($emp->empid, $enddate, "MPFEE", "cr");
                                            $mpfer = $this->vtranSelectQryAlias($emp->empid, $enddate, "MPFER", "cr");

                                            $this->addProccessAccount($emp->empid, $batchid, 'YSE', $batchdate, 0, $bracket[0]->sssee - $ssse, 84);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YSR', $batchdate, 0, $bracket[0]->ssser - $sssr, 85);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YER', $batchdate, 0, $bracket[0]->eccer - $ssser, 86);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIS', $batchdate, ($bracket[0]->ssser - $sssr) + ($bracket[0]->eccer - $ssser), 0, 87);

                                            $this->addProccessAccount($emp->empid, $batchid, 'MPF', $batchdate, $bracket[0]->mpfer - $mpfer, 0, 88);
                                            $this->addProccessAccount($emp->empid, $batchid, 'MPFER', $batchdate, 0, $bracket[0]->mpfer - $mpfer, 89);
                                            $this->addProccessAccount($emp->empid, $batchid, 'MPFEE', $batchdate, 0, $bracket[0]->mpfee - $mpfee, 90);



                                            $deduction += (($bracket[0]->sssee - $ssse) + ($bracket[0]->mpfee - $mpfee));
                                            $grossPay[2] -= (($bracket[0]->sssee - $ssse) + ($bracket[0]->mpfee - $mpfee));
                                            $deductiontax -= (($bracket[0]->sssee - $ssse) + ($bracket[0]->mpfee - $mpfee));
                                        }
                                    }
                                }
                            } // end of SSS

                            //PHILHEALTH
                            if ($chkphic) {
                                if ($emp->phic != '') {
                                    if ($emp->philhdef != 0) {
                                        $this->addProccessAccount($emp->empid, $batchid, 'YME', $batchdate, 0, $emp->philhdef, 91);
                                        $this->addProccessAccount($emp->empid, $batchid, 'YMR', $batchdate, 0, $emp->philhdef, 92);
                                        $this->addProccessAccount($emp->empid, $batchid, 'YIM', $batchdate, $emp->philhdef, 0, 93);
                                        $grossPay[2] -= $emp->philhdef;
                                    } else {
                                        $phicamt = $deductionbaseSSSHDMF;
                                        $bracket = $this->coreFunctions->opentable("select phicee,phicer from phictab where " . $phicamt . " BETWEEN range1 AND range2");

                                        $phie = $this->vtranSelectQryAlias($emp->empid, $enddate, "YME", "cr");
                                        $phir = $this->vtranSelectQryAlias($emp->empid, $enddate, "YMR", "cr");

                                        if (!empty($bracket)) {

                                            $this->addProccessAccount($emp->empid, $batchid, 'YME', $batchdate, 0, $bracket[0]->phicee - $phie, 91);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YMR', $batchdate, 0, $bracket[0]->phicee - $phir, 92);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIM', $batchdate, $bracket[0]->phicee - $phir, 0, 93);

                                            $deduction += ($bracket[0]->phicee - $phie);
                                            $deductiontax += ($bracket[0]->phicee - $phie);
                                            $grossPay[2] -= ($bracket[0]->phicee - $phie);
                                        } else {

                                            $phicmulti = $this->coreFunctions->datareader("select phictotal as value from phictab where range1=0");
                                            if ($phicmulti) {
                                                $phicmulti = $phicmulti / 100;
                                            } else {
                                                $phicmulti = 0;
                                            }

                                            $phicee = round(($phicamt * $phicmulti) / 2, 2);

                                            $this->addProccessAccount($emp->empid, $batchid, 'YME', $batchdate, 0, $phicee - $phie, 91);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YMR', $batchdate, 0, $phicee - $phir, 92);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIM', $batchdate, $phicee - $phir, 0, 93);

                                            $deduction += ($phicee - $phie);
                                            $deductiontax += ($phicee - $phie);
                                            $grossPay[2] -= ($phicee - $phie);
                                        }
                                    }
                                }
                            } // end of PHILHEALTH                        

                            //PAG-IBIG
                            if ($chkhdmf) {
                                if ($emp->hdmf != '') {
                                    if ($emp->pibigdef != 0) {
                                        $this->addProccessAccount($emp->empid, $batchid, 'YPE', $batchdate, 0, $emp->pibigdef, 94);
                                        $this->addProccessAccount($emp->empid, $batchid, 'YPR', $batchdate, 0, $emp->pibigdef, 95);
                                        $this->addProccessAccount($emp->empid, $batchid, 'YIP', $batchdate, $emp->pibigdef, 0, 96);

                                        $grossPay[2] -= $emp->pibigdef;
                                    } else {
                                        $hdmfamt = 0;
                                        $hdmfamt2 = 0;
                                        $prevhdmf = $this->vtranSelectQryAlias($emp->empid, $enddate, "YPE", "cr");

                                        if ($prevhdmf != 0) {
                                            $hdmfamt = $prevhdmf;
                                        }

                                        if ($hdmfamt < 100) {
                                            if ($deductionbaseSSSHDMF >= 5000) {
                                                $hdmfamt = 100 - $hdmfamt;
                                            } else {
                                                $hdmfamt2 = round($deductionbaseSSSHDMF * 0.02, 2);
                                                if (($hdmfamt + $hdmfamt2) > 100) {
                                                    $hdmfamt = 100 - $hdmfamt;
                                                } else {
                                                    $hdmfamt = $hdmfamt2;
                                                }
                                            }
                                        } elseif ($hdmfamt >= 100) {
                                            $hdmfamt = 0;
                                        }

                                        if ($hdmfamt > 0) {
                                            $this->addProccessAccount($emp->empid, $batchid, 'YPE', $batchdate, 0, $hdmfamt, 94);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YPR', $batchdate, 0, $hdmfamt, 95);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YIP', $batchdate, $hdmfamt, 0, 96);

                                            $deduction += $hdmfamt;
                                            $deductiontax += $hdmfamt;
                                            $grossPay[2] -= $hdmfamt;
                                        }
                                    }
                                }
                            } // end of PAG IBIG

                            //TAX
                            if ($dayRate2 > 0) {
                                if ($chktin) {
                                    if ($emp->tin != '') {
                                        if ($adjustm) {
                                            $annualtax = $this->annualtax($emp->empid, date('Y', strtotime($batchdate)), $params);
                                            $this->addProccessAccount($emp->empid, $batchid, 'YWT', $batchdate, 0, $annualtax, 97);
                                            $grossPay[2] -= $annualtax;
                                        } else {
                                            if ($emp->wtaxdef != 0) {
                                                // if (!str_ends_with($batchcode, '5')) { // not working on Php 8.0 below
                                                if (substr($batchcode, -1) != '5') {
                                                    $this->addProccessAccount($emp->empid, $batchid, 'YWT', $batchdate, 0, $emp->wtaxdef, 98);
                                                    $grossPay[2] -= $emp->wtaxdef;
                                                }
                                            } else {

                                                //from vtran
                                                if ($rate[0]->type == 'M') {
                                                    $phie = $this->vtranSelectQryAlias($emp->empid, $enddate, "YME", "cr");
                                                    $ssse = $this->vtranSelectQryAlias($emp->empid, $enddate, "YSE", "cr");
                                                    $hdmf = $this->vtranSelectQryAlias($emp->empid, $enddate, "YPE", "cr");
                                                    $whte = $this->vtranSelectQryAlias($emp->empid, $enddate, "'YWT'", "cr", 1);
                                                } else {
                                                    $phie = $this->vtranSelectQryAlias($emp->empid, "", "YME", "cr", 0, $batchid);
                                                    $ssse = $this->vtranSelectQryAlias($emp->empid, "", "YSE", "cr", 0, $batchid);
                                                    $hdmf = $this->vtranSelectQryAlias($emp->empid, "", "YPE", "cr", 0, $batchid);
                                                    $whte = $this->vtranSelectQryAlias($emp->empid, "", "'YWT'", "cr", 1, $batchid);
                                                }
                                                $wtax = 0;
                                                $lesstax = $this->gettax($taxamt - ($phie + $ssse + $hdmf), $emp->paymode);
                                                $this->addProccessAccount($emp->empid, $batchid, 'YWT', $batchdate, 0, $lesstax - $whte, 98);
                                                $wtax = $lesstax - $whte;
                                                $grossPay[2] -= $wtax;
                                            }
                                        }
                                    }
                                }
                            } // end of tax\


                        } //end of blndeduction


                    } //end of is13th

                    $earning = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], false);
                    $earningadv = $this->getEarningDeduction($emp->empid, $batchcode, $batchid, $enddate, $batchdate, $params['user'], true);
                    $leavetransamt = $this->getLeaveTrans($emp->empid, $startdate, $enddate, $dayRate, $batchid, $batchdate);

                    if ($dcManualOtherEarn != 0) {
                        $ManualOtherEarnId = $this->coreFunctions->datareader("select line as value from paccount where code='PT91'");
                        if ($ManualOtherEarnId != '') {
                            $this->addProccessAccount($emp->empid, $batchid, 'PT91', $batchdate, $dcManualOtherEarn, 0, 0, $ManualOtherEarnId);
                        }
                    }

                    $grossPay[2] = $grossPay[2] + $allow3 + $earning + $earningadv + $leavetransamt;
                    $totalpay = $grossPay[2] + $pieceAmt + $cola + $amt13th +  $other - $amtDeduc + $dcManualOtherEarn + $tripIncentive + $operatorIncentive;

                    $this->addProccessAccount($emp->empid, $batchid, 'PPBLE', $batchdate, $totalpay, 0, 99);
                } else {
                    $msg = 'Missing rate';
                }
            }



            $this->coreFunctions->execqry("delete from " . $this->tablename . " where db=0 and cr=0", "delete");
            $this->coreFunctions->sbcupdate("standardsetup", ['camt' => 0], ['empid' => $emp->empid]);
            $this->coreFunctions->sbcupdate("standardsetupadv", ['camt' => 0], ['empid' => $emp->empid]);
        } catch (Exception $e) {
            echo $e;
        }
        exitHere:
        return ['status' => $msg == '', 'msg' => $msg];
    }


    private function sanitizelocal($rawdata)
    {
        if (isset($rawdata['db'])) {
            $rawdata['db'] = number_format($rawdata['db'], 2);
        }
        if (isset($rawdata['cr'])) {
            $rawdata['cr'] = number_format($rawdata['cr'], 2);
        }

        $fields = [];

        foreach ($rawdata as $k => $v) {
            array_push($fields, $k);
        }

        foreach ($fields as $key) {
            $rawdata[$key] = $this->othersClass->sanitizekeyfield($key, $rawdata[$key]);
        }
        return $rawdata;
    }

    private function resetEarningDeduction($empid, $batchid, $user)
    {
        $this->coreFunctions->execqry("update standardtrans as t left join standardsetup as b on b.trno = t.trno set b.balance=(b.balance + t.cr), b.editby='" . $user . "', b.editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where t.empid=" . $empid . " and t.batchid=" . $batchid);
        $this->coreFunctions->execqry("delete from standardtrans where batchid=? and empid=?", "delete", [$batchid, $empid]);
    }

    private function resetEarningDeductionAdv($empid, $batchid, $user)
    {
        $this->coreFunctions->execqry("update standardtransadv as t left join standardsetupadv as b on b.trno = t.trno set b.balance=(b.balance + t.cr), b.editby='" . $user . "', b.editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where t.empid=" . $empid . " and t.batchid=" . $batchid);
        $this->coreFunctions->execqry("delete from standardtransadv where batchid=? and empid=?", "delete", [$batchid, $empid]);
    }

    private function getAccount($alias, $field = 'alias')
    {
        $this->arrAccount = $this->coreFunctions->opentable("select line, uom, qty, pseq from paccount where " . $field . "=?", [$alias]);
    }

    private function getAccountbyId($id)
    {
        $this->arrAccount = $this->coreFunctions->opentable("select line, uom, qty, pseq from paccount where line=?", [$id]);
    }

    private function getAccountbyCode($code)
    {
        $this->arrAccount = $this->coreFunctions->opentable("select line, uom, qty, pseq from paccount where code=?", [$code]);
    }

    public function addProccessAccount($empid, $batchid, $alias, $batchdate, $db, $cr, $seq = 0, $acnod = 0, $qty = 0, $sanitize = true, $aliasfield = 'alias')
    {
        if ($acnod != 0) {
            $this->getAccountbyId($acnod);
        } else {
            $aliasArray = explode("~", $alias);
            if ($aliasArray[0] == 'CODE') {
                $this->getAccountbyCode($aliasArray[1]);
            } else {
                $this->getAccount($alias, $aliasfield);
            }
        }

        $rawdata = [];
        $rawdata['empid'] = $empid;
        $rawdata['batchid'] = $batchid;
        $rawdata['qty'] = ($qty != 0 ? $qty : $this->arrAccount[0]->qty);
        $rawdata['uom'] =  $this->arrAccount[0]->uom;
        $rawdata['dateid'] = $batchdate;
        $rawdata['acnoid'] =  $this->arrAccount[0]->line;
        $rawdata['db'] = $db;
        $rawdata['cr'] = $cr;
        $rawdata['torder'] =  ($seq != 0 ? $seq : $this->arrAccount[0]->pseq);

        if ($sanitize) {
            $insertdata = $this->sanitizelocal($rawdata);
        } else {
            $insertdata = $rawdata;
        }
        if ($db != 0 || $cr != 0) {

            $this->coreFunctions->sbcinsert($this->tablename, $insertdata);
        }
    }

    //qty2 - used for actual latehrs in mighty setup
    public function addTimeSheetAccount($empid, $batchid, $acnoid, $batchdate, $uom, $seq, $qty, $user, $qty2)
    {
        $data = [];
        $data['empid'] = $empid;
        $data['batchid'] = $batchid;
        $data['acnoid'] = $acnoid;
        $data['dateid'] = $batchdate;
        $data['uom'] = $uom;
        $data['eorder'] = $seq;
        $data['qty'] = $qty;
        $data['qty2'] = $qty2;
        $data['editby'] = $user;
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();

        $this->coreFunctions->execqry("delete from timesheet where acnoid=" . $acnoid . " and empid=" . $empid . " and batchid=" . $batchid);

        $insertdata = $this->sanitizelocal($data);
        $this->coreFunctions->sbcinsert('timesheet', $insertdata);
    }

    private function annualtax($empid, $year, $params)
    {
        $prevYear = $year - 1;

        $bonus = round($this->bonus($empid, $prevYear, $year) / 2, 2);
        if ($bonus == 0) {
            return 0;
        }
        $thex13 = $bonus > $this->companysetup->getpayroll_bonusmax($params) ? $bonus - $this->companysetup->getpayroll_bonusmax($params) : 0;
        $contri = $this->contri($empid, $year);
        $compensation = $this->compensation($empid, $year);
        $exempt = 0;

        $taxdue = $this->taxdue($empid, $compensation + $thex13 - $contri - $exempt);
        $taxdue = $taxdue > 0 ? $taxdue : 0;

        $tax = $this->taxw($empid, $year);
        $taxdec = $this->taxd($empid, $year);
        $taxover = $tax + $taxdec - $taxdue;

        if ($taxover <= 0) {
            return 0;
        } else {
            return $taxover;
        }
    }

    private function gettax($gross, $paymode)
    {
        if ($gross == 0) {
            return 0;
        }

        $field = '';

        try {
            $qry = "
            select * from taxtab where paymode='" . $paymode . "' and teu='" . $paymode . "'
            union all select * from taxtab where paymode='" . $paymode . "' and teu='A'
            union all select * from taxtab where paymode='" . $paymode . "' and teu='P'";


            $data = $this->coreFunctions->opentable($qry);

            foreach ($data as $key => $value) {
                if (strtoupper($value->teu) == strtoupper($paymode)) {
                    foreach ($value as $k => $col) {
                        switch (strtolower($k)) {
                            case 'tax01':
                            case 'tax02':
                            case 'tax03':
                            case 'tax04':
                            case 'tax05':
                            case 'tax06':
                                if ($value->$k > $gross) {
                                } else {
                                    $field = $k;
                                }
                                break;
                        }
                    }
                }
            }

            $lesstax = ($field == '' ? 0 : $data[0]->$field);
            if ($field != '') {
                $amt = $this->coreFunctions->datareader("select " . $field . " as value from taxtab where paymode='" . $paymode . "'and teu='A'");
                $percent = $this->coreFunctions->datareader("select " . $field . " as value from taxtab where paymode='" . $paymode . "'and teu='P'");
            } else {
                $amt = 0;
                $percent = 0;
            }
            return (($gross - $lesstax) * $percent) + $amt;
        } catch (Exception $e) {

            echo $e;
            return $e;
        }
    }


    private function vtranSelectQry($empid, $enddate, $batchid = 0)
    {
        try {
            $filter_batch = '';
            if ($batchid != 0) {
                $filter_batch = ' and p.batchid=' . $batchid;
            }

            $qry = "
            select (ifnull(sum(db),0) - ifnull(sum(cr),0)) as value from (
            select `batchid`,`empid`,`dateid`,p.`acnoid`,p.`qty`,`db`,`cr`,`ltrno`
            from `paytrancurrent` as p left join paccount as c on c.line=p.acnoid
            where p.empid=" . $empid . " and MONTH(p.dateid)=MONTH('" . $enddate . "') AND YEAR(p.dateid)=YEAR('" . $enddate . "')
            and c.istax=1 and c.alias not in ('YSE','YSR','YER','YIS','YME','YMR','YIM','YPE','YPR','YIP','YWT','MPFEE','MPFER','MPF') " . $filter_batch . "
            union all
            select `batchid`,`empid`,`dateid`,p.`acnoid`,p.`qty`,`db`,`cr`,`ltrno`
            from `paytranhistory` as p left join paccount as c on c.line=p.acnoid
            where p.empid=" . $empid . " and MONTH(p.dateid)=MONTH('" . $enddate . "') AND YEAR(p.dateid)=YEAR('" . $enddate . "')
            and c.istax=1 and c.alias not in ('YSE','YSR','YER','YIS','YME','YMR','YIM','YPE','YPR','YIP','YWT','MPFEE','MPFER','MPF') " . $filter_batch . ") as vtran
            ";

            return $this->coreFunctions->datareader($qry);
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function vtranSelectQryAlias($empid, $enddate, $alias, $field, $aliasIN = 0, $batchid = 0)
    {
        try {
            $filterAlias = " and c.alias='" . $alias . "'";
            if ($aliasIN) {
                $filterAlias = " and c.alias in (" . $alias . ")";
            }

            $fitlerBatch = '';
            if ($batchid != 0) {
                $fitlerBatch = " and p.batchid=" . $batchid;
            }

            $filterDate = '';
            if ($enddate != '') {
                $filterDate = " and MONTH(p.dateid)=MONTH('" . $enddate . "') AND YEAR(p.dateid)=YEAR('" . $enddate . "') ";
            }

            $qry = "
            select ifnull(sum(" . $field . "),0) as value from (
            select `batchid`,`empid`,`dateid`,p.`acnoid`,p.`qty`,`db`,`cr`,`ltrno`
            from `paytrancurrent` as p left join paccount as c on c.line=p.acnoid
            where p.empid=" . $empid . $filterDate . $fitlerBatch . $filterAlias . "
            union all
            select `batchid`,`empid`,`dateid`,p.`acnoid`,p.`qty`,`db`,`cr`,`ltrno`
            from `paytranhistory` as p left join paccount as c on c.line=p.acnoid
            where p.empid=" . $empid . $filterDate . $fitlerBatch . $filterAlias . "
            ) as vtran";

            // $this->coreFunctions->LogConsole($qry);

            return $this->coreFunctions->datareader($qry);
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function getEarningDeduction($empid, $batch, $batchid, $startdate, $batchdate, $user, $advance = false)
    {

        $transtable = 'standardtrans';
        $setuptable = 'standardsetup';

        $blnEarning = true;

        if ($advance) {
            $transtable = 'standardtransadv';
            $setuptable = 'standardsetupadv';
            $blnEarning = false;
        }

        try {
            $week = substr($batch, -2);
            switch ($week) {
                case '01':
                    $week = " and w1=1";
                    break;

                case '02':
                    $week = " and w2=1";
                    break;

                case '03':
                    $week = " and w3=1";
                    break;

                case '04':
                    $week = " and w4=1";
                    break;

                case '05':
                    $week = " and w5=1";
                    break;

                // case '13':
                //     $week = " and w13=1";
                //     break;

                default:
                    $week = '';
                    break;
            }

            //Earnings
            $amtEarning = 0;
            if ($blnEarning) {
                $qry = "select trno,docno,dateid,acnoid,amt,w1,w2,w3,w4,w5,priority,amortization,effdate,balance from " . $setuptable . " where empid=" . $empid . " and halt=0 and amortization=0 and balance=0 and date(effdate) <= date('" . $startdate . "') " . $week;
                $earn = $this->coreFunctions->opentable($qry);

                foreach ($earn as $key => $value) {
                    $this->coreFunctions->execqry("delete from " . $transtable . " where trno=" . $value->trno . " and batchid=" . $batchid . " and empid=" . $empid, "delete");
                    $data = [];
                    $data['trno'] = $value->trno;
                    $data['batchid'] = $batchid;
                    $data['empid'] = $empid;
                    $data['dateid'] = $startdate;
                    $data['acnoid'] = $value->acnoid;
                    $data['db'] = $value->amt;
                    $data['cr'] = 0;
                    $data['docno'] = $value->docno;
                    $rawdata = $this->sanitizelocal($data);
                    $insert = $this->coreFunctions->sbcinsert($transtable, $rawdata);
                    if ($insert) {
                        $alias = $this->coreFunctions->datareader("select alias as value from paccount where line=" . $value->acnoid);
                        if ($alias != '') {
                            $amtEarning += $value->amt;
                            $this->addProccessAccount($empid, $batchid, $alias, $batchdate, $value->amt, 0, 50, $value->acnoid);
                        }
                    }
                }
                //end of Earnings
            }

            //Deductions
            $this->coreFunctions->execqry("update " . $transtable . " as t left join " . $setuptable . " as b on b.trno = t.trno set b.balance=(b.balance + t.cr) where t.empid=" . $empid . " and t.batchid=" . $batchid);
            $this->coreFunctions->execqry("delete from " . $transtable . " where empid=" . $empid . " and batchid=" . $batchid);

            $amtDeduction = 0;
            $qry = "select trno,docno,dateid,acnoid,amt,w1,w2,w3,w4,w5,priority,amortization,effdate,balance,camt from " . $setuptable . " where empid=" . $empid . "  and halt=0 and (amortization<>0 or camt<>0) and balance<>0 and date(effdate)<= date('" . $startdate . "') " . $week . " order by camt desc,priority";
            $deduct = $this->coreFunctions->opentable($qry);

            foreach ($deduct as $key => $value) {
                $prevDeduct = $this->coreFunctions->opentable("select trno,acnoid,cr from " . $transtable . " where ismanual=0 and trno=" . $value->trno . " and batchid=" . $batchid);
                foreach ($prevDeduct as $k => $val) {
                    $this->coreFunctions->execqry("update " . $setuptable . " set balance=balance + " . $val->cr . ", editby='" . $user . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno="  . $val->trno);
                }

                $this->coreFunctions->execqry("delete from " . $transtable . " where ismanual=0 and trno=" . $value->trno . " and batchid=" . $batchid);
                $data = [];
                $data['trno'] = $value->trno;
                $data['batchid'] = $batchid;
                $data['empid'] = $empid;
                $data['dateid'] = $startdate;
                $data['acnoid'] = $value->acnoid;
                $data['db'] = 0;
                if ($value->camt != 0) {
                    $value->amortization = $value->camt;
                }
                if ($value->balance > $value->amortization) {
                    $data['cr'] = $value->amortization;
                } else {
                    $data['cr'] = $value->balance;
                }
                $data['docno'] = $value->docno;
                $rawdata = $this->sanitizelocal($data);
                $insert = $this->coreFunctions->sbcinsert($transtable, $rawdata);
                if ($insert) {
                    $alias = $this->coreFunctions->datareader("select alias as value from paccount where line=" . $value->acnoid);
                    if ($alias != '') {
                        $amtDeduction += $data['cr'];
                        $this->addProccessAccount($empid, $batchid, $alias, $batchdate, 0, $data['cr'], 51, $value->acnoid);

                        $appliedamt = $this->coreFunctions->datareader("select ifnull(sum(cr),0) as value from " . $transtable . " where trno=?", [$value->trno], '', true);

                        $newbalance = $value->amt - $appliedamt;
                        $this->coreFunctions->execqry("update " . $setuptable . " set balance = " . $newbalance . ", editby='" . $user . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $value->trno);
                    } else {
                        return 'Missing alias for account id ' . $value->acnoid;
                    }
                }
            }
            //end of Deductions

            return $amtEarning - $amtDeduction;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function bonus($empid, $prevYear, $curYear)
    {
        try {
            $qry = "
        select ifnull(sum(db),0) as db from (
        select ifnull((p.db-p.cr),0) as db from paytrancurrent as p left join paccount as a on a.line=p.acnoid
        where p.empid=" . $empid . " and a.code in ('PT57','PT8','PT9','PT58','PT5','PT6','PT7','PT105','PT18','PT31','PT4') and dateid between '" . $prevYear . "-12-31' and '" . $curYear . "-11-30'
        union all
        select ifnull((p.db-p.cr),0) as db from paytranhistory as p left join paccount as a on a.line=p.acnoid
        where p.empid=" . $empid . " and a.code in ('PT57','PT8','PT9','PT58','PT5','PT6','PT7','PT105','PT18','PT31','PT4') and dateid between '" . $prevYear . "-12-31' and '" . $curYear . "-11-30'
        ) as a";

            $bonus = 0;
            $data = $this->coreFunctions->opentable($qry);
            foreach ($data as $key => $value) {
                $bonus += $value->db;
            }

            return $bonus;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function contri($empid, $curYear)
    {
        try {
            $qry = "
            select ifnull(a.cr,0) as cr from (
                select p.cr from paytrancurrent as p where empcode =" . $empid . " and p.acno in ('PT44','PT48','PT51') and dateid between '" . $curYear . "-01-01' and '"  . $curYear . "-12-31'
                union all
                select p.cr from paytranhistory as p where empcode =" . $empid . " and p.acno in ('PT44','PT48','PT51') and dateid between '" . $curYear . "-01-01' and '"  . $curYear . "-12-31'
            ) as a
            ";

            $amt = 0;
            $data = $this->coreFunctions->opentable($qry);
            foreach ($data as $key => $value) {
                $amt += $value->cr;
            }

            return $amt;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function compensation($empid, $curYear)
    {
        try {
            $qry = "
            select ifnull(a.db-a.cr,0) as db from (
                select p.db,p.cr from paytrancurrent as p left join paccount as pa on pa.line=p.acnoid
                where empcode =" . $empid . " and pa.alias in ('BSA','ABSENT','LATE','UNDERTIME','VL','SL','LEG', 'SP','LEGUN', 'SPUN') and dateid between '" . $curYear . "01-01' and '" . $curYear . "-12-31'
                union all
                select p.db,p.cr from paytranhistory as p left join paccount as pa on pa.line=p.acnoid
                where empcode =" . $empid . " and pa.alias in ('BSA','ABSENT','LATE','UNDERTIME','VL','SL','LEG', 'SP','LEGUN', 'SPUN') and dateid between '" . $curYear . "01-01' and '" . $curYear . "-12-31'
            ) as x
            ";

            $amt = 0;
            $data = $this->coreFunctions->opentable($qry);
            foreach ($data as $key => $value) {
                $amt += $value->db;
            }

            return $amt;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function taxdue($empid, $total)
    {
        try {
            $due = 0;
            $qry = "select range1,amt,percentage from annualtax where " . $total . " between range1 and range2";
            $data = $this->coreFunctions->opentable($qry);
            if (!empty($data)) {
                $due = round((($total - $data[0]->range1) * $data[0]->percentage) + $data[0]->amt, 2);
            }

            return $due;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function taxw($empid, $curYear)
    {
        try {
            $qry = "
            select ifnull(a.cr,0) as cr from (
                select p.cr from paytrancurrent as p where empcode =" . $empid . " and p.acno in ('PT42') and dateid between '" . $curYear . "01-01' and '" . $curYear . "-11-31'
                union all
                select p.cr from paytranhistory as p where empcode =" . $empid . " and p.acno in ('PT42') and dateid between '" . $curYear . "01-01' and '" . $curYear . "-11-31'
            ) as a
            ";

            $amt = 0;
            $data = $this->coreFunctions->opentable($qry);
            foreach ($data as $key => $value) {
                $amt += $value->cr;
            }

            return $amt;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function taxd($empid, $curYear)
    {
        try {
            $qry = "
            select ifnull(a.cr,0) as cr from (
                select p.cr from paytrancurrent as p where empcode =" . $empid . " and p.acno in ('PT42') and dateid between '" . $curYear . "12-01' and '" . $curYear . "-12-31'
                union all
                select p.cr from paytranhistory as p where empcode =" . $empid . " and p.acno in ('PT42') and dateid between '" . $curYear . "12-01' and '" . $curYear . "-12-31'
            ) as a
            ";

            $amt = 0;
            $data = $this->coreFunctions->opentable($qry);
            foreach ($data as $key => $value) {
                $amt += $value->cr;
            }

            return $amt;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }


    private function bonusC($empid, $batchid, $batchdate, $start, $end)
    {
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));

        try {
            $qry = "
            select ifnull(sum(db),0) as db from (
            select ifnull((p.db-p.cr),0) as db from paytrancurrent as p left join paccount as a on a.line=p.acnoid
            where p.empid=" . $empid . " and a.alias in ('BSA','ABSENT','LATE','UNDERTIME','VL','SL','SIL','ML','PL','BL','ADJUSTMENT') and dateid between '" . $start . "' and '" . $end . "'
            union all
            select ifnull((p.db-p.cr),0) as db from paytranhistory as p left join paccount as a on a.line=p.acnoid
            where p.empid=" . $empid . " and a.alias in ('BSA','ABSENT','LATE','UNDERTIME','VL','SL','SIL','ML','PL','BL','ADJUSTMENT') and dateid between '" . $start . "' and '" . $end . "'
            ) as a";

            // paccount.alias in ('BSA','ABSENT','LATE','UNDERTIME','VL','SL','SIL','ML','PL','BL','ADJUSTMENT')

            // and a.code in ('PT57','PT8','PT9','PT58','PT5','PT6','PT7','PT105','PT18','PT200','PT86','PT85','PT82','PT83')

            $amt13 = 0;
            // $this->coreFunctions->LogConsole($qry);
            $data = $this->coreFunctions->opentable($qry);
            foreach ($data as $key => $value) {
                $amt13 += $value->db;
            }

            $amt13 = $amt13 / 12;

            if ($amt13 > 0) {
                $this->addProccessAccount($empid, $batchid, "13PAY", $batchdate, number_format($amt13, 2, '.', ''), 0);
            }

            return $amt13;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function getLeaveTrans($empid, $start, $end, $dayRate, $batchid, $batchdate)
    {
        try {

            $leaveamt = 0;

            $this->coreFunctions->execqry("update leavetrans set batchid=0 where batchid=" . $batchid . " and empid=" . $empid);

            //approved leave
            $qry = "select p.code, p.line as acnoid, p.codename, sum(t.adays) as hours, p.seq, p.uom, p.alias, t.trno, t.line
            from leavetrans as t left join leavesetup as s on s.trno=t.trno left join paccount as p on p.line=s.acnoid
            where t.empid=" . $empid . " and s.isnopay=0 and t.`status`='A' and t.batchid=0 and date(t.effectivity) between date('" . $start . "') and date('" . $end . "')
            group by p.code, p.line, p.codename, p.seq, p.uom, p.alias, t.trno, t.line";
            $leave = $this->coreFunctions->opentable($qry);

            $db = 0;
            $arr_alias = '';
            foreach ($leave as $key => $value) {
                if ($value->uom == 'HRS') {
                    $db = ($dayRate / 8) * $value->hours;
                } else {
                    $db = $dayRate * $value->hours;
                }
                $this->addProccessAccount($empid, $batchid, $value->alias, $batchdate, $db, 0, 50, 0, $value->hours, false);
                $this->coreFunctions->execqry("update leavetrans set batchid=" . $batchid . " where trno=" . $value->trno . " and line=" . $value->line);
                // $leaveamt += $db;
                if ($arr_alias == '') {
                    $arr_alias = $arr_alias . "'" . $value->alias . "'";
                } else {
                    $arr_alias = $arr_alias . ",'" . $value->alias . "'";
                }
            }

            // if ($leaveamt != 0) {
            if ($arr_alias != '') {
                $qry = "select p.empid, p.dateid, sum(p.qty) as qty, p.unitamt, round(sum(p.db),2) as db, round(sum(p.cr),2) as cr, 
                p.torder, p.docno, p.type, p.uom, p.ltrno, p.doc, p.batchid, p.acnoid 
                from paytrancurrent as p left join paccount as a on a.line=p.acnoid
                where p.batchid=" . $batchid . " and p.empid=" . $empid . " and a.alias in (" . $arr_alias . ")
                group by p.empid, p.dateid, p.unitamt, p.torder, p.docno, p.type, p.uom, p.ltrno, p.doc, p.batchid, p.acnoid";

                $summdata = $this->coreFunctions->opentable($qry);
                foreach ($summdata as $key => $v) {
                    $this->coreFunctions->execqry("delete from paytrancurrent where batchid=" . $batchid . " and empid=" . $empid . " and acnoid=" . $v->acnoid);
                    $this->addProccessAccount($empid, $batchid, "", $batchdate, $v->db, 0, 50, $v->acnoid, $v->qty);

                    $leaveamt += $v->db;
                }
            }
            // }

            return $leaveamt;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function getPieceAmt($empid, $start, $end, $batchid)
    {
        try {
            $this->coreFunctions->execqry("update piecetrans set batchid=0
            where empid = " . $empid . " and dateid between date('" . $start . "') and date('" . $end . "') and batchid=" . $batchid, "update");

            $qry = "select line, damt from piecetrans
               where empid = " . $empid . " and dateid between date('" . $start . "') and date('" . $end . "') and batchid=0";
            $data = $this->coreFunctions->opentable($qry);
            $amt = 0;

            foreach ($data as $key => $val) {
                $amt += $val->damt;
                $this->coreFunctions->execqry("update piecetrans set batchid=" . $batchid . "
                where empid = " . $empid . " and dateid between date('" . $start . "') and date('" . $end . "') and batchid=0", "update");
            }

            return $amt;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    //Payroll Posting/Unposting
    public function postPayroll($config, $unpost = 0)
    {
        try {
            $batchid = $config['params']['dataparams']['batchid'];
            $user = $config['params']['user'];

            if ($batchid == 0) {
                return ['status' => false, 'msg' => 'Please select valid batch', 'action' => 'load'];
            }

            $postdate = $this->coreFunctions->datareader("select postdate as value from batch where line=" . $batchid);

            if (!$unpost) {
                if ($postdate  != null) {
                    return ['status' => false, 'msg' => 'Selected batch is already closed.', 'action' => 'load'];
                }
            } else {
                if ($postdate  == null) {
                    return ['status' => false, 'msg' => 'Selected batch is not yet closed.', 'action' => 'load'];
                }
            }

            $msg = '';
            $status = false;

            if ($unpost) {
                $timesheet1 = 'timesheethistory';
                $timesheet2 = 'timesheet';

                $paytran1 = 'paytranhistory';
                $paytran2 = 'paytrancurrent';
            } else {
                $timesheet1 = 'timesheet';
                $timesheet2 = 'timesheethistory';

                $paytran1 = 'paytrancurrent';
                $paytran2 = 'paytranhistory';
            }

            $qry = "insert into " . $timesheet2 . " (line, empid, dateid, qty, uom, eorder, acnoid, batchid, editby, editdate, qty2)
                select line, empid, dateid, qty, uom, eorder, acnoid, batchid, editby, editdate, qty2 from " . $timesheet1 . " where batchid=" . $batchid;
            $insert = $this->coreFunctions->execqry($qry);
            if ($insert) {
                $qry = "insert into " . $paytran2 . " (empid, dateid, qty, unitamt, db, cr, torder, batchid, acnoid, qty2)
            select empid, dateid, qty, unitamt, db, cr, torder, batchid, acnoid, qty2 from " . $paytran1 . " where batchid=" . $batchid;
                $insert = $this->coreFunctions->execqry($qry);
                if ($insert) {
                    $this->coreFunctions->execqry("delete from " . $timesheet1 . " where batchid=" . $batchid);
                    $this->coreFunctions->execqry("delete from " . $paytran1 . " where batchid=" . $batchid);

                    $status = true;
                    if ($unpost) {
                        $this->coreFunctions->execqry("update batch set postdate=null, postby='' where line=" . $batchid);
                        $msg = 'Successfully unclosed.';
                    } else {
                        $this->coreFunctions->execqry("update batch set postdate=now(), postby='" . $user . "' where line=" . $batchid);
                        $msg = 'Successfully closed.';
                    }
                } else {
                    $this->coreFunctions->execqry("delete from " . $timesheet2 . " where batchid=" . $batchid);
                    $this->coreFunctions->execqry("delete from " . $paytran2 . " where batchid=" . $batchid);
                    $msg = "Failed to insert " . $paytran2;
                }
            } else {
                $this->coreFunctions->execqry("delete from " . $timesheet2 . " where batchid=" . $batchid);
                $msg = "Failed to insert " . $timesheet2;
            }
        } catch (Exception $e) {
            $msg = $e;
            $status = false;
        }

        return ['status' => $status, 'msg' => $msg, 'action' => 'load'];
    }

    //end of Payroll Posting/Unposting

    private function checkpayrollacounts()
    {
        $account = [
            ['COLA', 'COLA'],
            ['PIECE', 'PIECE SALARY'],
            ['YSE', 'SSS-EMPLOYEE'],
            ['YSR', 'SSS-EMPLOYER'],
            ['YER', 'EC-EMPLOYER'],
            ['YIS', 'SSS EMPLOYER SHARE'],
            ['YME', 'PHILHEALTH-EMPLOYEE'],
            ['YMR', 'PHILHEALTH-EMPLOYER'],
            ['YIM', 'PHILHEALTH EMPLOYER SHARE'],
            ['YPE', 'PAG-IBIG EMPLOYEE'],
            ['YPR', 'PAG-IBIG EMPLOYER'],
            ['YIP', 'PAG-IBIG EMPLOYER SHARE'],
            ['YWT', 'WITHHOLDING TAX PAYABLE'],
            ['PPBLE', 'PAYROLL PAYABLE']
        ];

        foreach ($account as $key => $val) {
            $exist = $this->coreFunctions->datareader("select line as value from paccount where alias='" . $val[0] . "'");
            if (!$exist) {
                return ['status' => false, 'msg' => 'Missing account with alias `' . $val[0] . '` for payroll account `' . $val[1] . '` is missing.'];
            }
        }

        return ['status' => true, 'msg' => ''];
    }


    public function postactualinout($config, $empid, $start, $end, $checkall, $paygroup, $paymode, $blnExtract = false)
    {
        $filteremp = '';
        // if ($empid != 0) {
        $filteremp = ' and t.empid=' . $empid;
        // }

        $filteremplvl = '';
        if ($blnExtract) {
        } else {
            $emplvl = $this->othersClass->checksecuritylevel($config);
            $filteremplvl = " and e.level in " . $emplvl;
        }

        $qry = "select t.empid,t.schedin,t.schedout,e.idbarcode,t.dateid,s.gtin,s.sig,s.breakinam,s.breakoutam,s.breakinpm,s.breakoutpm,e.shiftid 
        from timecard as t left join employee as e on e.empid=t.empid left join tmshifts as s on s.line=e.shiftid
            where t.dateid between '" . $start . "' and '" . $end . "'" . $filteremplvl  . $filteremp;
        $emp = $this->coreFunctions->opentable($qry);

        $updatefields = 't.actualin=null, t.actualout=null, t.actualbrkout=null, t.actualbrkin=null';
        if ($config['params']['companyid'] == 45) { //pdpi payroll
            $updatefields = 't.actualin=null, t.actualout=null';
        }
        $qry = "update timecard as t left join employee as e on e.empid=t.empid set " . $updatefields . " where t.dateid between '" . $start . "' and '" . $end . "'" . $filteremplvl . $filteremp;

        $update = $this->coreFunctions->execqry($qry);

        if ($update) {

            foreach ($emp as $key => $val) {
                $gp = 0;
                $sig = 0;
                if ($config['params']['companyid'] == 45) { //pdpi payroll
                    $gp = $val->gtin;
                    $sig = $val->sig;
                }

                if ($val->schedin != null && $val->schedout != null) {

                    $s_schedin = Carbon::parse($val->schedin)->subHours(3);
                    $s_schedin = $s_schedin->format('Y-m-d H:i:s');

                    $s_dateid = Carbon::parse($val->dateid)->addDays(1);
                    $s_dateid = $s_dateid->format('Y-m-d');

                    $tc_schedout = Carbon::parse($val->schedout);
                    $tc_schedin = $this->coreFunctions->datareader("select schedin as value from timecard where empid=? and dateid=?", [$val->empid, $s_dateid]);

                    if ($tc_schedin == '' || $tc_schedin == null) {
                        $tc_schedout = $tc_schedout->addHours(8);
                    } else {
                        $tc_schedin = Carbon::parse($tc_schedin);
                        $dcAddHrs = $tc_schedout->diffInHours($tc_schedin, false);

                        if ($dcAddHrs == 8) {
                            $dcAddHrs = 6;
                        } else {
                            $dcAddHrs = $dcAddHrs - 4;
                        }
                        $tc_schedout = $tc_schedout->addHours($dcAddHrs);
                    }

                    $strIn = '';
                    $strOut = '';
                    $strBIn = '';
                    $strBOut = '';

                    $s_schedout = $tc_schedout->format('Y-m-d H:i:s');

                    $qry = "select distinct timeinout from (
                    select dateid as timeinout from obapplication where empid=" . $val->empid . " and dateid between '" . $s_schedin . "' and '" . $s_schedout . "' and approvedate is not null and status='A'
                        union all 
                        select timeinout from timerec where userid='" . $val->idbarcode . "' and timeinout between '" . $s_schedin . "' and '" . $s_schedout . "') as x order by timeinout";
                    $timerec = $this->coreFunctions->opentable($qry);

                    foreach ($timerec as $key => $tval) {
                        if ($strIn == '') {
                            $strIn = $tval->timeinout;
                        } elseif ($strBOut == '') {
                            $strBOut = $tval->timeinout;
                        } elseif ($strBIn == '') {
                            $strBIn = $tval->timeinout;
                        } elseif ($strOut == '') {
                            $strOut = $tval->timeinout;
                        }
                    }

                    if ($strOut == '') {
                        if ($strBIn == '') {
                            if ($strBOut != '') {
                                $strOut = $strBOut;
                                $strBOut = '';
                            }
                        } else {
                            $strOut = $strBIn;
                            $strBIn = '';
                        }
                    }

                    if ($strIn != '') {
                        $strIn = $this->othersClass->sanitizekeyfield('actualin', $strIn);
                        $strOut = $this->othersClass->sanitizekeyfield('actualin', $strOut);
                        $strBIn = $this->othersClass->sanitizekeyfield('actualin', $strBIn);
                        $strBOut = $this->othersClass->sanitizekeyfield('actualin', $strBOut);
                        $val->dateid = $this->othersClass->sanitizekeyfield('dateonly', $val->dateid);


                        if ($config['params']['companyid'] == 45) { //pdpi payroll

                            $rws = Carbon::parse($val->schedin)->addMinutes($gp);
                            $rws = $rws->format('Y-m-d H:i:s');

                            $rwe = Carbon::parse($val->schedout);
                            $rwe = $rwe->format('Y-m-d H:i:s');

                            $omb = date('Y-m-d', strtotime($val->schedin)) . " " . date('H:i', strtotime($val->breakoutam));
                            $omb = Carbon::parse($omb);
                            $omb = $omb->format('Y-m-d H:i:s');

                            $imb = date('Y-m-d', strtotime($val->schedin)) . " " . date('H:i', strtotime($val->breakinam));
                            $imb = Carbon::parse($imb);
                            $imb = $imb->format('Y-m-d H:i:s');

                            $noonbreak = $this->coreFunctions->opentable("select breakin,breakout from shiftdetail where shiftsid=" . $val->shiftid);
                            $onb = date('Y-m-d', strtotime($val->schedin)) . " " . date('H:i', strtotime($noonbreak[0]->breakout));
                            $onb = Carbon::parse($onb);
                            $onb = $onb->format('Y-m-d H:i:s');

                            $inb = date('Y-m-d', strtotime($val->schedin)) . " " . date('H:i', strtotime($noonbreak[0]->breakin));
                            $inb = Carbon::parse($inb);
                            $inb = $inb->format('Y-m-d H:i:s');

                            $oab = date('Y-m-d', strtotime($val->schedin)) . " " . date('H:i', strtotime($val->breakoutpm));
                            $oab = Carbon::parse($oab);
                            $oab = $oab->format('Y-m-d H:i:s');

                            $iab = date('Y-m-d', strtotime($val->schedin)) . " " . date('H:i', strtotime($val->breakinpm));
                            $iab = Carbon::parse($iab);
                            $iab = $iab->format('Y-m-d H:i:s');

                            // for timein
                            if ($strIn != '') {
                                $actual_in = Carbon::parse($strIn);
                                $actual_in = $actual_in->format('Y-m-d H:i:s');

                                //checking of actual in and schedule in + grace period
                                if ($actual_in <= $rws) {
                                    $rws = Carbon::parse($val->schedin);
                                    $rws = $rws->format('Y-m-d H:i:s');
                                    $strIn = $rws;
                                } else {

                                    //checking with out morning break and in morning break
                                    if ($actual_in >= $omb && $actual_in <= $imb) {
                                        $strIn = $imb;
                                    } else {

                                        //checking with out noon break and in noon break
                                        if ($actual_in >= $onb && $actual_in <= $inb) {
                                            $strIn = $inb;
                                        } else {

                                            //checking with out afternoon break and in afternoon break
                                            if ($actual_in >= $oab && $actual_in <= $iab) {
                                                $strIn = $iab;
                                            } else {
                                                $sig_ = $sig * 60;
                                                $strIn = date('Y-m-d H:i:s', ceil(strtotime($actual_in) / $sig_) * $sig_);
                                            }
                                        }
                                    }
                                }
                            } //end of timein

                            //for time out
                            if ($strOut != '') {
                                $actual_out = Carbon::parse($strOut);
                                $actual_out = $actual_out->format('Y-m-d H:i:s');

                                //checking actual out with out morning break and in morning break
                                if ($actual_out >= $omb && $actual_out <= $imb) {
                                    $strOut = $omb;
                                } else {
                                    //checking with out noon break and in noon break
                                    if ($actual_out >= $onb && $actual_out <= $inb) {
                                        $strOut = $onb;
                                    } else {

                                        //checking with out afternoon break and in afternoon break
                                        if ($actual_out >= $oab && $actual_out <= $iab) {
                                            $strOut = $oab;
                                        } else {
                                            $sig_ = $sig * 60;
                                            $strOut = date('Y-m-d H:i:s', floor(strtotime($actual_out) / $sig_) * $sig_);
                                        }
                                    }
                                }
                            } // end of time out
                        }

                        $strIn != null  ? $strIn = "'" . $strIn  . "'" : $strIn = "null";
                        $strOut != null  ? $strOut = "'" . $strOut  . "'" : $strOut = "null";
                        $strBIn != null  ? $strBIn = "'" . $strBIn  . "'" : $strBIn = "null";
                        $strBOut != null  ? $strBOut = "'" . $strBOut  . "'" : $strBOut = "null";

                        if ($config['params']['companyid'] == 45) { //pdpi payroll
                            if ($strOut == 'null' && $strIn != '') {
                                $emp_schedout = $this->coreFunctions->datareader("select schedout as value from timecard where empid=" . $val->empid . " and date(dateid)='" . $val->dateid . "'");
                                $emp_schedout = date('Y-m-d H:i', strtotime($emp_schedout));
                                $emp_schedout_ = Carbon::parse($emp_schedout);
                                $emp_schedout_ = $emp_schedout_->format('Y-m-d H:i:s');

                                $emp_actual = date('Y-m-d H:i', strtotime(str_replace("'", "", $strIn)));
                                $emp_actual = Carbon::parse($emp_actual);
                                $emp_actual_ = $emp_actual->format('Y-m-d H:i:s');
                                if ($emp_actual_ < $emp_schedout_) {
                                    $strIn = "null";
                                }
                            }
                        }

                        if ($config['params']['companyid'] == 45) { //pdpi payroll
                            $qryy = "update timecard set actualin=" . $strIn . ",actualout=" . $strOut . " where empid=" . $val->empid . " and date(dateid)='" . $val->dateid . "'";
                        } else {
                            $qryy = "update timecard set actualin=" . $strIn . ",actualbrkout=" . $strBOut . ",actualbrkin=" . $strBIn . ",actualout=" . $strOut . " where empid=" . $val->empid . " and date(dateid)='" . $val->dateid . "'";
                        }
                        $this->coreFunctions->execqry($qryy);
                    }
                }
            }
        } else {
            return ['status' => false, 'msg' => 'Failed to reset timecard'];
        }


        return ['status' => true, 'msg' => 'test'];
    }

    public function postactualinout_cdo($config, $empid, $start, $end, $checkall, $paygroup, $paymode, $blnExtract = false)
    {
        $filteremp = '';
        // if (!$checkall) {
        $filteremp = ' and t.empid=' . $empid;
        // }

        $filteremplvl = '';
        if ($blnExtract) {
        } else {
            $emplvl = $this->othersClass->checksecuritylevel($config);
            $filteremplvl = " and e.level in " . $emplvl;
        }

        $qry = "select t.empid,t.schedin,t.schedout,e.idbarcode,t.dateid,s.gtin,s.sig,s.breakinam,s.breakoutam,s.breakinpm,s.breakoutpm,e.shiftid,t.brk1stout,t.brk1stin,t.brk2ndout,t.brk2ndin,t.schedbrkin,t.schedbrkout,t.daytype
            from timecard as t left join employee as e on e.empid=t.empid left join tmshifts as s on s.line=e.shiftid where t.dateid between '" . $start . "' and '" . $end . "'" . $filteremplvl  . $filteremp;
        // $this->coreFunctions->LogConsole($qry);
        $emp = $this->coreFunctions->opentable($qry);

        $updatefields = 't.actualin=null, t.actualout=null, t.actualbrkout=null, t.actualbrkin=null, t.abrk1stout=null, t.abrk1stin=null,t.abrk2ndout=null,t.abrk2ndin=null,t.isitinerary=0,
                t.latehrs=0, t.underhrs=0, t.absdays=0, t.othrs=0, t.ndiffot=0, t.ndiffhrs=0,
                t.otapproved = 0, t.ndiffapproved = 0, t.isprevwork =0, t.rdapprvd=0, t.rdotapprvd=0, t.legapprvd=0, t.legotapprvd=0, t.spapprvd=0, t.spotapprvd=0, ndiffsapprvd=0 ';
        $qry = "update timecard as t left join employee as e on e.empid=t.empid set " . $updatefields . " where t.dateid between '" . $start . "' and '" . $end . "'" . $filteremplvl . $filteremp;

        $update = $this->coreFunctions->execqry($qry);

        if ($update) {

            foreach ($emp as $key => $val) {

                if ($val->schedin != null && $val->schedout != null) {

                    $s_schedin = Carbon::parse($val->schedin)->subHours(3);
                    $s_schedin = $s_schedin->format('Y-m-d H:i:s');

                    $s_dateid = Carbon::parse($val->dateid)->addDays(1);
                    $s_dateid = $s_dateid->format('Y-m-d');

                    $tc_schedout = Carbon::parse($val->schedout);
                    $tc_schedin = $this->coreFunctions->datareader("select schedin as value from timecard where empid=? and dateid=?", [$val->empid, $s_dateid]);

                    if ($tc_schedin == '' || $tc_schedin == null) {
                        $tc_schedout = $tc_schedout->addHours(8);
                    } else {
                        $tc_schedin = Carbon::parse($tc_schedin);
                        $dcAddHrs = $tc_schedout->diffInHours($tc_schedin, false);

                        if ($dcAddHrs == 8) {
                            $dcAddHrs = 6;
                        } else {
                            $dcAddHrs = $dcAddHrs - 4;
                        }
                        $tc_schedout = $tc_schedout->addHours($dcAddHrs);
                    }

                    $strIn = '';
                    $strOut = '';
                    $strBIn = '';
                    $strBOut = '';

                    $strBInAM = '';
                    $strBOutAM = '';
                    $strBInPM = '';
                    $strBOutPM = '';

                    $blnOBIn = $blnOBOut = $blnOBBin = $blnOBBout = $blnUndertime = false;

                    $s_schedout = $tc_schedout->format('Y-m-d H:i:s');

                    $latein = 0;
                    $earlyout = 0;
                    $latecusto = 0;

                    $isitinerary = 0;

                    $qry = "select distinct timeinout, DATE_FORMAT(timeinout, '%a') as dayname, isitinerary, trackingtype, doc from (
                        select dateid as timeinout, isitinerary, trackingtype, 'OB' as doc from obapplication where empid=" . $val->empid . " and dateid between ('" . $s_schedin . "') and ('" . $s_schedout . "') and approvedate is not null and status='A' and dateid is not null
                        union all
                        select dateid2 as timeinout, isitinerary, trackingtype, 'OB' as doc from obapplication where empid=" . $val->empid . " and dateid2 between ('" . $s_schedin . "') and ('" . $s_schedout . "') and approvedate is not null and status='A' and dateid2 is not null
                        union all
                        select timeinout, 0 as isitinerary, '' as trackingtype, 'TIMEREC' as doc from timerec where userid='" . $val->idbarcode . "' and timeinout between '" . $s_schedin . "' and '" . $s_schedout . "') as x order by timeinout";
                    $this->coreFunctions->LogConsole($qry);
                    $timerec = $this->coreFunctions->opentable($qry);

                    // $this->coreFunctions->LogConsole("=====================");

                    foreach ($timerec as $key => $tval) {
                        // $this->coreFunctions->LogConsole('LOG: ' . $tval->timeinout);

                        if ($tval->doc == 'OB') {
                            if ($tval->isitinerary == 1) {
                                $isitinerary = 1;
                                if ($val->daytype == 'RESTDAY') {
                                    goto continueTimeRecHere;
                                }
                            }

                            // $this->coreFunctions->LogConsole($tval->trackingtype);
                            switch ($tval->trackingtype) {
                                case 'EARLY TIME OUT':
                                    $latein = 1;
                                    break;
                                case 'LATE TIME IN':
                                    $earlyout = 1;
                                    break;
                                case 'KEY CUSTODIANS LATE':
                                    $latecusto = 1;
                                    break;
                            }
                        }

                        if ($strIn == '') {
                            $strIn = $tval->timeinout;
                        } elseif ($strBOutAM == '') {
                            // $this->coreFunctions->LogConsole('strBOutAM: ' . $tval->timeinout . ' < ' . $val->schedbrkout);
                            if ($tval->timeinout < $val->schedbrkout) {
                                // $this->coreFunctions->LogConsole(' -> strBOutAM log: ' . $tval->timeinout);
                                $strBOutAM = $tval->timeinout;
                            } else {
                                goto BreakInAM;
                            }
                        } elseif ($strBInAM == '') {
                            BreakInAM:
                            // $this->coreFunctions->LogConsole('strBInAM: ' . $tval->timeinout . ' < ' . $val->schedbrkout);
                            if ($strBInAM == '') {
                                if ($tval->timeinout < $val->schedbrkout) {
                                    // $this->coreFunctions->LogConsole(' -> strBInAM log: ' . $tval->timeinout);
                                    $strBInAM = $tval->timeinout;
                                } else {
                                    goto LunchOut;
                                }
                            } else {
                                goto LunchOut;
                            }
                        } elseif ($strBOut == '') {
                            LunchOut:
                            // $this->coreFunctions->LogConsole('LunchOut: ' . $tval->timeinout . ' >= ' . $val->schedbrkout . ' && ' . $tval->timeinout . '<' . $val->brk2ndout);
                            if ($strBOut == '') {
                                if ($tval->timeinout >= $val->schedbrkout && $tval->timeinout < $val->brk2ndout) {
                                    $strBOut = $tval->timeinout;
                                    // $this->coreFunctions->LogConsole(' -> LunchOut log: ' . $strBOut);
                                } else {
                                    goto LunchIn;
                                }
                            } else {
                                goto LunchIn;
                            }
                        } elseif ($strBIn == '') {
                            LunchIn:
                            // $this->coreFunctions->LogConsole('LunchIn: ' . $tval->timeinout . '<' . $val->brk2ndout);
                            if ($strBIn == '') {
                                if ($tval->timeinout < $val->brk2ndout) {
                                    $strBIn = $tval->timeinout;
                                    // $this->coreFunctions->LogConsole(' -> LunchIn log: ' . $strBIn);
                                } else {
                                    goto BreakOutPM;
                                }
                            } else {
                                goto BreakOutPM;
                            }
                        } elseif ($strBOutPM == '') {
                            BreakOutPM:
                            if ($strBOutPM == '') {
                                $strBOutPM = $tval->timeinout;
                                // $this->coreFunctions->LogConsole(' - > str/BOutPM log: ' . $strBOutPM);
                            } else {
                                goto BreakInPM;
                            }
                        } elseif ($strBInPM == '') {
                            BreakInPM:
                            if ($strBInPM == '') {
                                $strBInPM = $tval->timeinout;
                                // $this->coreFunctions->LogConsole(' -> strBInPM log: ' . $strBInPM);
                            } else {
                                goto LastOut;
                            }
                        } elseif ($strOut == '') {
                            LastOut:
                            $strOut = $tval->timeinout;
                            // $this->coreFunctions->LogConsole(' -> strOut log: ' . $strOut);
                        }
                        continueTimeRecHere:
                    }

                    // $this->coreFunctions->LogConsole('strOutxx: ' . $strOut);
                    if ($strOut == '') {
                        if ($strBInPM != '') {
                            $strOut = $strBInPM;
                            $strBInPM = '';
                            // $this->coreFunctions->LogConsole(' -> Out log (strBInPM): ' . $strOut);
                        } else {
                            if ($strBOutPM != '') {
                                $strOut = $strBOutPM;
                                $strBOutPM = '';
                                // $this->coreFunctions->LogConsole(' -> Out log (strBOutPM): ' . $strOut);
                            } else {
                                if ($strBIn != '') {
                                    $strOut = $strBIn;
                                    $strBIn = '';
                                    // $this->coreFunctions->LogConsole(' -> Out log (strBIn): ' . $strOut);
                                } else {
                                    if ($strBOut != '') {
                                        $strOut = $strBOut;
                                        $strBOut = '';
                                        // $this->coreFunctions->LogConsole(' -> Out log (strBOut): ' . $strOut);
                                    }
                                }
                            }
                        }
                    }

                    // if ($strOut == '') {
                    //     $qry = "select dateid from undertime where empid=" . $val->empid . " and dateid between '" . $s_schedin . "' and '" . $s_schedout . "' and status='A' order by dateid";
                    //     $undertime = $this->coreFunctions->opentable($qry);
                    //     if (!empty($undertime)) {
                    //         $strOut = $undertime[0]->dateid;
                    //         $blnUndertime = true;
                    //     }
                    // }

                    if ($strIn != '') {
                        $strIn = $this->othersClass->sanitizekeyfield('actualin', $strIn);
                        $strOut = $this->othersClass->sanitizekeyfield('actualin', $strOut);
                        $strBIn = $this->othersClass->sanitizekeyfield('actualin', $strBIn);
                        $strBOut = $this->othersClass->sanitizekeyfield('actualin', $strBOut);

                        $strBInAM = $this->othersClass->sanitizekeyfield('actualin', $strBInAM);
                        $strBOutAM = $this->othersClass->sanitizekeyfield('actualin', $strBOutAM);
                        $strBInPM = $this->othersClass->sanitizekeyfield('actualin', $strBInPM);
                        $strBOutPM = $this->othersClass->sanitizekeyfield('actualin', $strBOutPM);

                        $val->dateid = $this->othersClass->sanitizekeyfield('dateonly', $val->dateid);

                        $strIn != null  ? $strIn = "'" . $strIn  . "'" : $strIn = "null";
                        $strOut != null  ? $strOut = "'" . $strOut  . "'" : $strOut = "null";
                        $strBIn != null  ? $strBIn = "'" . $strBIn  . "'" : $strBIn = "null";
                        $strBOut != null  ? $strBOut = "'" . $strBOut  . "'" : $strBOut = "null";

                        $strBInAM != null  ? $strBInAM = "'" . $strBInAM  . "'" : $strBInAM = "null";
                        $strBOutAM != null  ? $strBOutAM = "'" . $strBOutAM  . "'" : $strBOutAM = "null";
                        $strBInPM != null  ? $strBInPM = "'" . $strBInPM  . "'" : $strBInPM = "null";
                        $strBOutPM != null  ? $strBOutPM = "'" . $strBOutPM  . "'" : $strBOutPM = "null";

                        $addupdate = '';
                        // move compute timeset tagging of no morning in
                        // if ($blnOBIn) {
                        //     $addupdate .= ',isnologin=1';
                        // }

                        if ($blnOBOut) {
                            $addupdate .= ',isnologout=1';
                        }

                        // if ($blnUndertime) {
                        //     $addupdate .= ',isnologunder=1';
                        // }
                        $addupdate .= ',islatein=' . $latein;
                        $addupdate .= ',isearlyout=' . $earlyout;
                        $addupdate .= ',islatecusto=' . $latecusto;

                        $qryy = "update timecard set actualin=" . $strIn . ",actualbrkout=" . $strBOut . ",actualbrkin=" . $strBIn . ",actualout=" . $strOut .
                            ",abrk1stout=" . $strBOutAM . ",abrk1stin=" . $strBInAM . ",abrk2ndout=" . $strBOutPM . ",abrk2ndin=" . $strBInPM .
                            ",isitinerary=" . $isitinerary . $addupdate . " where empid=" . $val->empid . " and date(dateid)='" . $val->dateid . "'";
                        // $this->coreFunctions->LogConsole($qryy);
                        $this->coreFunctions->execqry($qryy);
                    }
                }
            }
        } else {
            return ['status' => false, 'msg' => 'Failed to reset timecard'];
        }


        return ['status' => true, 'msg' => 'test'];
    }


    public function getTripIncentive($empid, $start, $end, $batchid)
    {
        try {
            $this->coreFunctions->execqry("update htripdetail set batchid=0 where clientid = " . $empid . " and batchid=" . $batchid, "update");

            $qry = "select t.trno, t.line, t.rate from htripdetail as t left join hcntnuminfo as hinfo on hinfo.trno=t.trno left join htihead as h on h.trno=t.titrno 
            where hinfo.trno<>0 and t.clientid=" . $empid . " and t.batchid=0 "; //and '" . $start . "' >= date(h.start) and '" . $end . "' <= date(h.enddate)
            $data = $this->coreFunctions->opentable($qry);
            $amt = 0;

            foreach ($data as $key => $val) {
                $amt += $val->rate;
                $this->coreFunctions->execqry("update htripdetail set batchid=" . $batchid . " where trno=" . $val->trno . " and line=" . $val->line, "update");
            }

            return $amt;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function resetTripIncentive($empid, $batchid)
    {
        try {
            $this->coreFunctions->execqry("update htripdetail set batchid=0 where clientid = " . $empid . " and batchid=" . $batchid, "update");
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    public function getOperatorIncentive($empid, $start, $end, $batchid)
    {
        try {
            $this->coreFunctions->execqry("update heqhead set batchid=0 where empid = " . $empid . " and batchid=" . $batchid, "update");

            $qry = "select t.trno, t.opincentive as rate from heqhead as t where t.oitrno<>0 and t.empid=" . $empid . " and t.batchid=0"; // and date(t.dateid) between '" . $start . "' and '" . $end . "'
            $this->coreFunctions->LogConsole($qry);
            $data = $this->coreFunctions->opentable($qry);
            $amt = 0;

            foreach ($data as $key => $val) {
                $amt += $val->rate;
                $this->coreFunctions->execqry("update heqhead set batchid=" . $batchid . " where trno=" . $val->trno, "update");
            }

            return $amt;
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    private function resetOperatorIncentive($empid, $batchid)
    {
        try {
            $this->coreFunctions->execqry("update heqhead set batchid=0 where empid = " . $empid . " and batchid=" . $batchid, "update");
        } catch (Exception $e) {
            echo $e;
            return $e;
        }
    }

    public function getShiftDetails($empid)
    {
        $qry = "select s.tschedin, s.tschedout, s.flexit, s.gtin, s.gbrkin, s.ndifffrom, s.ndiffto, s.elapse, s.isfixhrs, s.isonelog
      from tmshifts as s left join employee as e on e.shiftid=s.line where e.empid = ?  limit 1";
        return $this->coreFunctions->opentable($qry, [$empid]);
    }

    public function checktimecard($empid, $dateid)
    {
        $qrry = "select schedin,schedout,dateid from timecard where dateid = ? and empid = ?";
        return $this->coreFunctions->opentable($qrry, [date('Y-m-d', strtotime($dateid)), $empid]);
    }

    public function checkapprover($config)
    {
        $approver = $this->coreFunctions->datareader("select emp.isapprover as value from client as c left join employee as emp on emp.empid=c.clientid where c.client=?", [$config['params']['user']]);
        if ($approver == "1") {
            return true;
        } else {
            return false;
        }
    }


    public function checksupervisor($config)
    {
        $supervisor = $this->coreFunctions->datareader("select emp.issupervisor as value from client as c left join employee as emp on emp.empid=c.clientid where c.client=?", [$config['params']['user']]);
        if ($supervisor == "1") {
            return true;
        } else {
            return false;
        }
    }

    public function checkbatchsched($scheddate, $divid)
    {
        if ($scheddate != '' || $scheddate != null) {

            $qry = "select date(date_add(enddate, interval 2 day)) as cutoffdate,date(enddate)  as enddate,divid from batch 
            where '" . $scheddate . "' between date(startdate) and date(enddate) and divid = " . $divid . "";
            $data = $this->coreFunctions->opentable($qry);
            $currentdate = $this->othersClass->getCurrentDate();
            if (!empty($data)) {
                if ($currentdate > $data[0]->cutoffdate) {
                    return ['status' => false, 'msg' => 'Not allowed to create 2 days after cutoff date. ' . $data[0]->enddate];
                }
                return ['status' => true, 'msg' => ''];
            } else {
                return ['status' => false, 'msg' => 'No batch was created on this date ' . $scheddate];
            }
        } else {
            return ['status' => false, 'msg' => 'Schedule Date is Empty'];
        }
    }

    public function checkleave($data, $config)
    {
        $curdate = $this->othersClass->getCurrentDate();
        $diff = $this->coreFunctions->datareader("select DATEDIFF('" . $data['start'] . "', '" . $curdate . "') as value");
        if ($config['params']['companyid'] == 58 && $data['acno'] == 'PT106') { //cdo
            return ['status' => true];
        } else {
            if ($data['dateid'] <= $data['start'] && $data['dateid'] <= $data['effectivity']) {
                if ($diff <= 2) {
                    if ($data['hours'] == 0.5) {
                        return ['status' => true];
                    } else {
                        goto stat;
                    }
                }
            } else {
                stat:
                return ['status' => false, 'msg' => 'Leave should be filed three days before the scheduled date of leave. (Applied hrs: ' . $data['hours'] . ')'];
            }
        }
    }

    public function checkportalrestrict($data, $config)
    {
        $createdate = date('Y-m-d', strtotime($data['createdate']));

        $year = date('Y', strtotime($createdate));
        $month = date('n', strtotime($createdate));

        switch ($config['params']['doc']) {
            case 'OBAPPLICATION':
            case 'UNDERTIME':
            case 'RESTDAY':
            case 'WORD':
                $day = date('d', strtotime($data['dateid']));
                break;
            case 'OTAPPLICATIONADV':
                $day = date('d', strtotime($data['scheddate']));
                break;
            case 'ITINERARY': //hindi pa eto okay
                $startdate = date('Y-m-d', strtotime($data['startdate']));
                $enddate = date('Y-m-d', strtotime($data['enddate']));
                $startday = date('d', strtotime($data['startdate']));
                $endday = date('d', strtotime($data['enddate']));
                $createday = date('d', strtotime($data['createdate']));

                $startmonth = date('n', strtotime($startdate));
                $endmonth = date('n', strtotime($enddate));

                $day = date('t', strtotime("$year-$month-01"));
                break;
        }

        if ($config['params']['doc'] == 'ITINERARY') {
            if ($endmonth > $month) {
                if ($startmonth > $month) { //advance filing
                    return ['status' => true];
                }
                if ($startday >= 1 && $startday <= 15) {
                    $duedate = date('Y-m-d', strtotime("$year-$month-15" . ' +2 days'));
                } else if ($startday >= 16 && $startday <= 31) {
                    $duedate = date('Y-m-d', strtotime("$year-$month-$day" . ' +2 days'));
                }
            } else {
                if ($endday >= 1 && $endday <= 15) {
                    $duedate = date('Y-m-d', strtotime("$year-$month-15" . ' +2 days'));

                    $startmonth = date('n', strtotime($startdate));
                    $startlabel = date('Y-m-d', strtotime("$year-$month-1"));

                    if ($startday >= 16 && $startday <= 31) {
                        if ($createday >= 3 && $createday <= 17) {
                            return ['status' => false, 'msg' => 'Can`t apply leave. Start of leave should be ' . $startlabel . ' to ' . $enddate, 'clientid' => $config['params']['adminid']];
                        }
                    }
                } else if ($endday >= 16 && $endday <= 31) {
                    $duedate = date('Y-m-d', strtotime("$year-$month-$day" . ' +2 days'));

                    $startmonth = date('n', strtotime($startdate));
                    $startlabel = date('Y-m-d', strtotime("$year-$startmonth-16"));

                    if ($startday >= 1 && $startday <= 15) {
                        if ($createday >= 1 && $createday <= 17) {
                        } else {
                            return ['status' => false, 'msg' => 'Can`t apply leave. Start of leave should be ' . $startlabel . ' to ' . $enddate, 'clientid' => $config['params']['adminid']];
                        }
                    }
                }
            }
            if ($createdate > $duedate) {
                return ['status' => false, 'msg' => 'Final filing & approval of activities is every 2nd and 17th of the month. Ensure the form is completely filed out to avoid invalid activities.', 'clientid' => $config['params']['adminid']];
            }
        } else {
            if ($day >= 1 && $day <= 15) {
                $duedate = date('Y-m-d', strtotime("$year-$month-15" . ' +2 days'));
            } else if ($day >= 16 && $day <= 31) {
                $month = date('n', strtotime($createdate));
                $year = date('Y', strtotime($createdate));
                $day = date('t', strtotime("$year-$month-01"));
                $duedate = date('Y-m-d', strtotime("$year-$month-$day" . ' +2 days'));
            }

            if ($createdate > $duedate) {
                return ['status' => false, 'msg' => 'Final filing & approval of activities is every 2nd and 17th of the month. Ensure the form is completely filed out to avoid invalid activities.', 'clientid' => $config['params']['adminid']];
            }
        }
    }
    public function checkapplicationstatus($config, $trno, $url, $submitdate)
    {
        $array_status  = ['A', 'D'];
        $status = true;
        $return_status = '';
        $data = [];
        $initialdate = null;
        $filter = "";
        $msg = '';
        $filter_set = 'submitdate';
        $filter_line = "line='$trno'";
        $setstatus = false;

        switch ($config['params']['doc']) {
            case 'OBAPPLICATION':
                $filter = " doc in ('OB','INITIALOB') and line = $trno";
                $table = 'obapplication';
                $initialdate = $this->coreFunctions->datareader("select initialapp as value from $table where line='$trno'", [], '', true);
                break;
            case 'OTAPPLICATIONADV':
                $filter = " doc = 'OT' and line = $trno";
                $table = 'otapplication';
                $array_status  = [2, 3];
                break;
            case 'LOANAPPLICATIONPORTAL':
                $filter = " doc = 'LOAN' and trno = $trno";
                $table = 'loanapplication';
                $filter_line = "trno='$trno'";
                break;
            case 'CHANGESHIFTAPPLICATION':
                $filter = " doc = 'CHANGESHIFT' and line = $trno";
                $table = 'changeshiftapp';
                $setstatus = true;
                $array_status  = [1, 2];
                break;
        }

        $appdoc =  $this->coreFunctions->datareader("select doc as value from pendingapp where $filter");

        $this->coreFunctions->LogConsole($appdoc);
        if (!empty($appdoc)) {
            if ($submitdate != null || $initialdate != null) {

                $approversetup = $this->coreFunctions->datareader("select approverseq as value from moduleapproval where modulename= '$appdoc'");
                if (empty($approversetup)) {
                    $approversetup = app($url)->approvers($config['params']);
                }
                $count = explode(',', $approversetup);
                $count = count($count);

                $fields = "status";
                if ($count == 2) {
                    $fields = "status2";
                }
                switch ($appdoc) {
                    case 'INITIALOB':
                        $filter_set = 'initialapp';
                        $fields = "initialstatus";
                        break;
                    case 'OT':
                        $fields = "otstatus";
                        if ($count == 2) {
                            $fields = "otstatus2";
                        }
                        break;
                }

                if ($appdoc == 'LOAN') {
                    $data = $this->coreFunctions->opentable("select $fields as status from $table where trno='$trno'");
                } else {
                    $data = $this->coreFunctions->opentable("select $fields as status from $table where line='$trno'");
                }
                if (!empty($data)) {
                    if ($appdoc == 'INITIALOB') {
                        if ($data[0]->status == '') {
                            goto end;
                        }
                    }
                    if (in_array($data[0]->status, $array_status)) {
                        $return_status = $data[0]->status;
                        $status = false;
                        if ($setstatus) {
                            $return_status = 2; //1 set to 2 approved, change shift
                            if ($data[0]->status == 2) $return_status = 3; // 1 set to 2 disapproved
                        }
                    }
                }

                switch ($return_status) {
                    case 'A':
                    case '2':
                        $msg = 'Cannot update; already approved.';
                        break;
                    case 'D':
                    case '3':
                        $msg = 'Cannot update; already disapproved.';
                        break;
                }
                if (empty($return_status)) {
                    $this->coreFunctions->execqry("delete from pendingapp where $filter ", "delete");
                    $this->coreFunctions->execqry("update $table set $filter_set = null where $filter_line ", "update");
                    $this->logger->sbcmasterlog($trno, $config, "UPDATE STATUS READY TO SUBMIT OR FOR APPROVOAL");
                }
            }
        } else {
            $status = $this->coreFunctions->datareader("select status as value from $table where line='$trno'", [], '');
            if ($status == 'E') {
                goto end;
            }
            return ['status' => false, 'msg' => 'Application done and (approved or disapproved)'];
        }
        end:
        $this->coreFunctions->LogConsole($return_status . '-' . $msg . ' status: ' . $status);
        return ['status' => $status, 'msg' => $msg, 'arrstatus' => $return_status];
    } // end function
}
