<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class monthly_trial_balance
{
    public $modulename = 'Monthly Trial Balance';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '2750'];

    public function __construct()
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass;
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {
        $companyid = $config['params']['companyid'];

        $fields = ['radioprint'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['dateid', 'due', 'dcentername', 'costcenter'];


        $col2 = $this->fieldClass->create($fields);


        data_set($col2, 'dateid.label', 'StartDate');
        data_set($col2, 'dateid.readonly', false);
        data_set($col2, 'due.label', 'EndDate');
        data_set($col2, 'due.readonly', false);
        $fields = ['radioposttype'];
        $col3 = $this->fieldClass->create($fields);
        data_set(
            $col3,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All Transactions', 'value' => '2', 'color' => 'teal']
            ]
        );


        $fields = ['print'];
        $col4 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    }

    public function paramsdata($config)
    {
        $paramstr = "select 
        'default' as print,
         adddate(left(now(),10),-360) as dateid
         ,left(now(),10) as due,'0' as posttype,
         '' as center,
         '' as code,
         '' as name,
         '' as centername,
         '' as dcentername,
         '' as costcenter,
         '0' as costcenterid 
        ";

        return $this->coreFunctions->opentable($paramstr);
    }

    // put here the plotting string if direct printing
    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        $str = $this->reportplotting($config);
        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function default_query($filters)
    {

        $company = $filters['params']['companyid'];

        $isposted = $filters['params']['dataparams']['posttype'];
        $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
        $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
        $filter = "";

        $center = $filters['params']['dataparams']['center'];
        $costcenter = $filters['params']['dataparams']['costcenter'];
        if ($center != '') {
            $filter .= " and cntnum.center='" . $center . "' ";
        }

        if ($costcenter != "") {
            $costcenterid = $filters['params']['dataparams']['costcenterid'];
            if ($company == 8) { //maxipro
                $filter .= " and detail.projectid = " . $costcenterid . "";
            } else {
                $filter .= " and head.projectid = " . $costcenterid . "";
            }
        }

        $hjc = '';
        $jc = '';

        if ($company == 8) { //maxipro
            $hjc = "union all 
                    select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,ifnull(sum(round(detail.cr,2)),0) as cr,
                                    ifnull(sum(case when month(head.dateid)=1 then detail.cr else 0 end),0) as jancr,
                                    ifnull(sum(case when month(head.dateid)=2 then detail.cr else 0 end),0) as febcr,
                                    ifnull(sum(case when month(head.dateid)=3 then detail.cr else 0 end),0) as marcr,
                                    ifnull(sum(case when month(head.dateid)=4 then detail.cr else 0 end),0) as aprcr,
                                    ifnull(sum(case when month(head.dateid)=5 then detail.cr else 0 end),0) as maycr,
                                    ifnull(sum(case when month(head.dateid)=6 then detail.cr else 0 end),0) as juncr,
                                    ifnull(sum(case when month(head.dateid)=7 then detail.cr else 0 end),0) as julcr,
                                    ifnull(sum(case when month(head.dateid)=8 then detail.cr else 0 end),0) as augcr,
                                    ifnull(sum(case when month(head.dateid)=9 then detail.cr else 0 end),0) as sepcr,
                                    ifnull(sum(case when month(head.dateid)=10 then detail.cr else 0 end),0)as octcr,
                                    ifnull(sum(case when month(head.dateid)=11 then detail.cr else 0 end),0)as novcr,
                                    ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as deccr,
                                    ifnull(sum(case when month(head.dateid)=1 then detail.db else 0 end),0) as jandb,
                                    ifnull(sum(case when month(head.dateid)=2 then detail.db else 0 end),0) as febdb,
                                    ifnull(sum(case when month(head.dateid)=3 then detail.db else 0 end),0) as mardb,
                                    ifnull(sum(case when month(head.dateid)=4 then detail.db else 0 end),0) as aprdb,
                                    ifnull(sum(case when month(head.dateid)=5 then detail.db else 0 end),0) as maydb,
                                    ifnull(sum(case when month(head.dateid)=6 then detail.db else 0 end),0) as jundb,
                                    ifnull(sum(case when month(head.dateid)=7 then detail.db else 0 end),0) as juldb,
                                    ifnull(sum(case when month(head.dateid)=8 then detail.db else 0 end),0) as augdb,
                                    ifnull(sum(case when month(head.dateid)=9 then detail.db else 0 end),0) as sepdb,
                                    ifnull(sum(case when month(head.dateid)=10 then detail.db else 0 end),0)as octdb,
                                    ifnull(sum(case when month(head.dateid)=11 then detail.db else 0 end),0)as novdb,
                                    ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as decdb
                                from ((hjchead as head
                                left join gldetail as detail on detail.trno=head.trno)
                                left join coa on coa.acnoid=detail.acnoid)
                                left join cntnum on cntnum.trno=head.trno
                                where head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
                                group by coa.acno";
            $jc = " union all
                    select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,ifnull(sum(round(detail.cr,2)),0) as cr,
                                        ifnull(sum(case when month(head.dateid)=1 then detail.cr else 0 end),0) as jancr,
                                        ifnull(sum(case when month(head.dateid)=2 then detail.cr else 0 end),0) as febcr,
                                        ifnull(sum(case when month(head.dateid)=3 then detail.cr else 0 end),0) as marcr,
                                        ifnull(sum(case when month(head.dateid)=4 then detail.cr else 0 end),0) as aprcr,
                                        ifnull(sum(case when month(head.dateid)=5 then detail.cr else 0 end),0) as maycr,
                                        ifnull(sum(case when month(head.dateid)=6 then detail.cr else 0 end),0) as juncr,
                                        ifnull(sum(case when month(head.dateid)=7 then detail.cr else 0 end),0) as julcr,
                                        ifnull(sum(case when month(head.dateid)=8 then detail.cr else 0 end),0) as augcr,
                                        ifnull(sum(case when month(head.dateid)=9 then detail.cr else 0 end),0) as sepcr,
                                        ifnull(sum(case when month(head.dateid)=10 then detail.cr else 0 end),0)as octcr,
                                        ifnull(sum(case when month(head.dateid)=11 then detail.cr else 0 end),0)as novcr,
                                        ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as deccr,
                                        ifnull(sum(case when month(head.dateid)=1 then detail.db else 0 end),0) as jandb,
                                        ifnull(sum(case when month(head.dateid)=2 then detail.db else 0 end),0) as febdb,
                                        ifnull(sum(case when month(head.dateid)=3 then detail.db else 0 end),0) as mardb,
                                        ifnull(sum(case when month(head.dateid)=4 then detail.db else 0 end),0) as aprdb,
                                        ifnull(sum(case when month(head.dateid)=5 then detail.db else 0 end),0) as maydb,
                                        ifnull(sum(case when month(head.dateid)=6 then detail.db else 0 end),0) as jundb,
                                        ifnull(sum(case when month(head.dateid)=7 then detail.db else 0 end),0) as juldb,
                                        ifnull(sum(case when month(head.dateid)=8 then detail.db else 0 end),0) as augdb,
                                        ifnull(sum(case when month(head.dateid)=9 then detail.db else 0 end),0) as sepdb,
                                        ifnull(sum(case when month(head.dateid)=10 then detail.db else 0 end),0)as octdb,
                                        ifnull(sum(case when month(head.dateid)=11 then detail.db else 0 end),0)as novdb,
                                        ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as decdb
                                from ((jchead as head
                                left join ladetail as detail on detail.trno=head.trno)
                                left join coa on coa.acnoid=detail.acnoid)
                                left join cntnum on cntnum.trno=head.trno
                                where head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
                                group by coa.acno";
        }

        switch ($isposted) {
            case 1:
                $query = "select  acno,acnoname, levelid, sum(amt) as amt, detail,sum(db) as db ,sum(cr) as cr,
                                    sum(jandb) as jandb,sum(febdb) as febdb,sum(mardb) as mardb,sum(aprdb) as aprdb,sum(maydb) as maydb, 
                                    sum(jundb) as jundb,sum(juldb) as juldb,sum(augdb) as augdb,sum(sepdb) as sepdb,sum(octdb) as octdb,
                                    sum(novdb) as novdb,sum(decdb) as decdb,sum(jancr) as jancr,sum(febcr) as febcr,sum(marcr) as marcr,
                                    sum(aprcr) as aprcr,sum(maycr) as maycr,sum(juncr)as juncr ,sum(julcr)as julcr,sum(augcr) as augcr,\
                                    sum(sepcr) as sepcr,sum(octcr) as octcr,sum(novcr) as novcr,sum(deccr) as deccr
                        from ( select 'u' as tr, coa.acno,coa.acnoname, coa.levelid, coa.detail,sum(db) as db ,sum(cr) as cr,
                                        sum(ifnull(db,0)-ifnull(cr,0)) as amt,sum(jandb) as jandb,sum(febdb) as febdb,
                                        sum(mardb) as mardb,sum(aprdb) as aprdb,sum(maydb) as maydb, sum(jundb) as jundb,
                                        sum(juldb) as juldb,sum(augdb) as augdb,sum(sepdb) as sepdb,sum(octdb) as octdb,
                                        sum(novdb) as novdb,sum(decdb) as decdb,sum(jancr) as jancr,sum(febcr) as febcr,
                                        sum(marcr) as marcr,sum(aprcr) as aprcr,sum(maycr) as maycr,sum(juncr)as juncr ,
                                        sum(julcr)as julcr,sum(augcr) as augcr,sum(sepcr) as sepcr,sum(octcr) as octcr,
                                        sum(novcr) as novcr,sum(deccr) as deccr
                                from coa
                                left join (select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,
                                                ifnull(sum(round(detail.cr,2)),0) as cr,
                                                ifnull(sum(case when month(head.dateid)=1 then detail.cr else 0 end),0) as jancr,
                                                ifnull(sum(case when month(head.dateid)=2 then detail.cr else 0 end),0) as febcr,
                                                ifnull(sum(case when month(head.dateid)=3 then detail.cr else 0 end),0) as marcr,
                                                ifnull(sum(case when month(head.dateid)=4 then detail.cr else 0 end),0) as aprcr,
                                                ifnull(sum(case when month(head.dateid)=5 then detail.cr else 0 end),0) as maycr,
                                                ifnull(sum(case when month(head.dateid)=6 then detail.cr else 0 end),0) as juncr,
                                                ifnull(sum(case when month(head.dateid)=7 then detail.cr else 0 end),0) as julcr,
                                                ifnull(sum(case when month(head.dateid)=8 then detail.cr else 0 end),0) as augcr,
                                                ifnull(sum(case when month(head.dateid)=9 then detail.cr else 0 end),0) as sepcr,
                                                ifnull(sum(case when month(head.dateid)=10 then detail.cr else 0 end),0)as octcr,
                                                ifnull(sum(case when month(head.dateid)=11 then detail.cr else 0 end),0)as novcr,
                                                ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as deccr,
                                ifnull(sum(case when month(head.dateid)=1 then detail.db else 0 end),0) as jandb,
                                ifnull(sum(case when month(head.dateid)=2 then detail.db else 0 end),0) as febdb,
                                ifnull(sum(case when month(head.dateid)=3 then detail.db else 0 end),0) as mardb,
                                ifnull(sum(case when month(head.dateid)=4 then detail.db else 0 end),0) as aprdb,
                                ifnull(sum(case when month(head.dateid)=5 then detail.db else 0 end),0) as maydb,
                                ifnull(sum(case when month(head.dateid)=6 then detail.db else 0 end),0) as jundb,
                                ifnull(sum(case when month(head.dateid)=7 then detail.db else 0 end),0) as juldb,
                                ifnull(sum(case when month(head.dateid)=8 then detail.db else 0 end),0) as augdb,
                                ifnull(sum(case when month(head.dateid)=9 then detail.db else 0 end),0) as sepdb,
                                ifnull(sum(case when month(head.dateid)=10 then detail.db else 0 end),0)as octdb,
                                ifnull(sum(case when month(head.dateid)=11 then detail.db else 0 end),0)as novdb,
                                ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as decdb
                            from ((lahead as head
                                left join ladetail as detail on detail.trno=head.trno)
                                left join coa on coa.acnoid=detail.acnoid)
                                left join cntnum on cntnum.trno=head.trno
                                where head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
                                group by coa.acno $jc ) as x on x.acno=coa.acno

                        group by coa.acno,coa.acnoname,coa.levelid,coa.detail ) as k
                 group by  acno, acnoname, levelid,detail
                 order by acno, acnoname, detail";
                break;
            case 0:
                $query = "select acno,acnoname, levelid, sum(amt) as amt, detail,sum(db) as db ,sum(cr) as cr,sum(jandb) as jandb,
                                sum(febdb) as febdb,sum(mardb) as mardb,sum(aprdb) as aprdb,sum(maydb) as maydb, sum(jundb) as jundb,
                                sum(juldb) as juldb,sum(augdb) as augdb,sum(sepdb) as sepdb,sum(octdb) as octdb,sum(novdb) as novdb,
                                sum(decdb) as decdb,sum(jancr) as jancr,sum(febcr) as febcr,sum(marcr) as marcr,sum(aprcr) as aprcr,
                                sum(maycr) as maycr,sum(juncr)as juncr,sum(julcr)as julcr,sum(augcr) as augcr,sum(sepcr) as sepcr,
                                sum(octcr) as octcr,sum(novcr) as novcr,sum(deccr) as deccr
                        from (select 'u' as tr, coa.acno,coa.acnoname, coa.levelid, coa.detail,sum(db) as db ,sum(cr) as cr,
                                    sum(ifnull(db,0)-ifnull(cr,0)) as amt,sum(jandb) as jandb,sum(febdb) as febdb,
                                    sum(mardb) as mardb,sum(aprdb) as aprdb,sum(maydb) as maydb, sum(jundb) as jundb,
                                    sum(juldb) as juldb,sum(augdb) as augdb,sum(sepdb) as sepdb,sum(octdb) as octdb,
                                    sum(novdb) as novdb,sum(decdb) as decdb,sum(jancr) as jancr,sum(febcr) as febcr,
                                    sum(marcr) as marcr,sum(aprcr) as aprcr,sum(maycr) as maycr,sum(juncr)as juncr ,
                                    sum(julcr)as julcr,sum(augcr) as augcr,sum(sepcr) as sepcr,sum(octcr) as octcr,
                                    sum(novcr) as novcr,sum(deccr) as deccr
                            from coa
                            left join (select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,
                                            ifnull(sum(round(detail.cr,2)),0) as cr,
                                            ifnull(sum(case when month(head.dateid)=1 then detail.cr else 0 end),0) as jancr,
                                            ifnull(sum(case when month(head.dateid)=2 then detail.cr else 0 end),0) as febcr,
                                            ifnull(sum(case when month(head.dateid)=3 then detail.cr else 0 end),0) as marcr,
                                            ifnull(sum(case when month(head.dateid)=4 then detail.cr else 0 end),0) as aprcr,
                                            ifnull(sum(case when month(head.dateid)=5 then detail.cr else 0 end),0) as maycr,
                                            ifnull(sum(case when month(head.dateid)=6 then detail.cr else 0 end),0) as juncr,
                                            ifnull(sum(case when month(head.dateid)=7 then detail.cr else 0 end),0) as julcr,
                                            ifnull(sum(case when month(head.dateid)=8 then detail.cr else 0 end),0) as augcr,
                                            ifnull(sum(case when month(head.dateid)=9 then detail.cr else 0 end),0) as sepcr,
                                            ifnull(sum(case when month(head.dateid)=10 then detail.cr else 0 end),0)as octcr,
                                            ifnull(sum(case when month(head.dateid)=11 then detail.cr else 0 end),0)as novcr,
                                            ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as deccr,
                                            ifnull(sum(case when month(head.dateid)=1 then detail.db else 0 end),0) as jandb,
                                            ifnull(sum(case when month(head.dateid)=2 then detail.db else 0 end),0) as febdb,
                                            ifnull(sum(case when month(head.dateid)=3 then detail.db else 0 end),0) as mardb,
                                            ifnull(sum(case when month(head.dateid)=4 then detail.db else 0 end),0) as aprdb,
                                            ifnull(sum(case when month(head.dateid)=5 then detail.db else 0 end),0) as maydb,
                                            ifnull(sum(case when month(head.dateid)=6 then detail.db else 0 end),0) as jundb,
                                            ifnull(sum(case when month(head.dateid)=7 then detail.db else 0 end),0) as juldb,
                                            ifnull(sum(case when month(head.dateid)=8 then detail.db else 0 end),0) as augdb,
                                            ifnull(sum(case when month(head.dateid)=9 then detail.db else 0 end),0) as sepdb,
                                            ifnull(sum(case when month(head.dateid)=10 then detail.db else 0 end),0)as octdb,
                                            ifnull(sum(case when month(head.dateid)=11 then detail.db else 0 end),0)as novdb,
                                            ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as decdb
                                        from ((glhead as head
                                        left join gldetail as detail on detail.trno=head.trno)
                                        left join coa on coa.acnoid=detail.acnoid)
                                        left join cntnum on cntnum.trno=head.trno
                                        where head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
                                        group by coa.acno $hjc ) as x on x.acno=coa.acno
                            group by coa.acno,coa.acnoname,coa.levelid,coa.detail ) as k
                        group by  acno, acnoname, levelid,detail
                        order by acno, acnoname, detail";
                break;

            case 2: //all

                $query = "select  acno,acnoname, levelid, sum(amt) as amt, detail,sum(db) as db ,sum(cr) as cr,
                                sum(jandb) as jandb,sum(febdb) as febdb,sum(mardb) as mardb,sum(aprdb) as aprdb,sum(maydb) as maydb, sum(jundb) as jundb,
                                sum(juldb) as juldb,sum(augdb) as augdb,sum(sepdb) as sepdb,sum(octdb) as octdb,sum(novdb) as novdb,sum(decdb) as decdb,
                                sum(jancr) as jancr,sum(febcr) as febcr,sum(marcr) as marcr,sum(aprcr) as aprcr,sum(maycr) as maycr,sum(juncr)as juncr ,
                                sum(julcr)as julcr,sum(augcr) as augcr,sum(sepcr) as sepcr,sum(octcr) as octcr,sum(novcr) as novcr,sum(deccr) as deccr
                        from (select 'p' as tr, coa.acno, coa.acnoname, coa.levelid, sum(ifnull(tb.db,0)-ifnull(tb.cr,0)) as amt ,coa.detail,tb.cr,tb.db,
                                    tb.jandb,tb.febdb,tb.mardb,tb.aprdb,tb.maydb,tb.jundb,tb.juldb,tb.augdb,tb.sepdb,tb.octdb,tb.novdb,tb.decdb,
                                    tb.jancr,tb.febcr,tb.marcr, tb.aprcr,tb. maycr, tb.juncr,tb.julcr,tb.augcr,tb.sepcr,tb.octcr,tb.novcr,tb.deccr
                            from coa
                            left join (select coa.acno, ifnull(sum(round(detail.db,2)),0) as db,ifnull(sum(round(detail.cr,2)),0) as cr,
                                            ifnull(sum(case when month(head.dateid)=1 then detail.cr else 0 end),0) as jancr,
                                            ifnull(sum(case when month(head.dateid)=2 then detail.cr else 0 end),0) as febcr,
                                            ifnull(sum(case when month(head.dateid)=3 then detail.cr else 0 end),0) as marcr,
                                            ifnull(sum(case when month(head.dateid)=4 then detail.cr else 0 end),0) as aprcr,
                                            ifnull(sum(case when month(head.dateid)=5 then detail.cr else 0 end),0) as maycr,
                                            ifnull(sum(case when month(head.dateid)=6 then detail.cr else 0 end),0) as juncr,
                                            ifnull(sum(case when month(head.dateid)=7 then detail.cr else 0 end),0) as julcr,
                                            ifnull(sum(case when month(head.dateid)=8 then detail.cr else 0 end),0) as augcr,
                                            ifnull(sum(case when month(head.dateid)=9 then detail.cr else 0 end),0) as sepcr,
                                            ifnull(sum(case when month(head.dateid)=10 then detail.cr else 0 end),0)as octcr,
                                            ifnull(sum(case when month(head.dateid)=11 then detail.cr else 0 end),0)as novcr,
                                            ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as deccr,
                                            ifnull(sum(case when month(head.dateid)=1 then detail.db else 0 end),0) as jandb,
                                            ifnull(sum(case when month(head.dateid)=2 then detail.db else 0 end),0) as febdb,
                                            ifnull(sum(case when month(head.dateid)=3 then detail.db else 0 end),0) as mardb,
                                            ifnull(sum(case when month(head.dateid)=4 then detail.db else 0 end),0) as aprdb,
                                            ifnull(sum(case when month(head.dateid)=5 then detail.db else 0 end),0) as maydb,
                                            ifnull(sum(case when month(head.dateid)=6 then detail.db else 0 end),0) as jundb,
                                            ifnull(sum(case when month(head.dateid)=7 then detail.db else 0 end),0) as juldb,
                                            ifnull(sum(case when month(head.dateid)=8 then detail.db else 0 end),0) as augdb,
                                            ifnull(sum(case when month(head.dateid)=9 then detail.db else 0 end),0) as sepdb,
                                            ifnull(sum(case when month(head.dateid)=10 then detail.db else 0 end),0)as octdb,
                                            ifnull(sum(case when month(head.dateid)=11 then detail.db else 0 end),0)as novdb,
                                            ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as decdb
                                    from ((glhead as head
                                    left join gldetail as detail on detail.trno=head.trno)
                                    left join coa on coa.acnoid=detail.acnoid)
                                    left join cntnum on cntnum.trno=head.trno
                                    where head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
                                    group by coa.acno $hjc) as tb on tb.acno=coa.acno
                            group by coa.acno, coa.acnoname, coa.levelid,coa.detail,tb.cr,tb.db,tb.jandb,tb.febdb,tb.mardb,
                                    tb.aprdb,tb.maydb,tb.jundb,tb.juldb,tb.augdb,tb.sepdb,tb.octdb,tb.novdb,tb.decdb,
                                    tb.jancr,tb.febcr,tb.marcr, tb.aprcr,tb. maycr, tb.juncr,tb.julcr,tb.augcr,tb.sepcr,tb.octcr,tb.novcr,tb.deccr
                            
                            union all
                            select 'u' as tr, coa.acno, coa.acnoname, coa.levelid, sum(ifnull(x.db,0)-ifnull(x.cr,0)) as amt,coa.detail,x.cr,x.db,
                                    x.jandb,x.febdb,x.mardb,x.aprdb,x.maydb,x.jundb,x.juldb,x.augdb,x.sepdb,x.octdb,x.novdb,x.decdb,
                                    x.jancr,x.febcr,x.marcr, x.aprcr,x.maycr,x.juncr,x.julcr,x.augcr,x.sepcr,x.octcr,x.novcr,x.deccr
                            from coa
                            left join (select  coa.acno, ifnull(sum(round(detail.db,2)),0) as db,
                                                ifnull(sum(round(detail.cr,2)),0) as cr,
                                                ifnull(sum(case when month(head.dateid)=1 then detail.cr else 0 end),0) as jancr,
                                                ifnull(sum(case when month(head.dateid)=2 then detail.cr else 0 end),0) as febcr,
                                                ifnull(sum(case when month(head.dateid)=3 then detail.cr else 0 end),0) as marcr,
                                                ifnull(sum(case when month(head.dateid)=4 then detail.cr else 0 end),0) as aprcr,
                                                ifnull(sum(case when month(head.dateid)=5 then detail.cr else 0 end),0) as maycr,
                                                ifnull(sum(case when month(head.dateid)=6 then detail.cr else 0 end),0) as juncr,
                                                ifnull(sum(case when month(head.dateid)=7 then detail.cr else 0 end),0) as julcr,
                                                ifnull(sum(case when month(head.dateid)=8 then detail.cr else 0 end),0) as augcr,
                                                ifnull(sum(case when month(head.dateid)=9 then detail.cr else 0 end),0) as sepcr,
                                                ifnull(sum(case when month(head.dateid)=10 then detail.cr else 0 end),0)as octcr,
                                                ifnull(sum(case when month(head.dateid)=11 then detail.cr else 0 end),0)as novcr,
                                                ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as deccr,
                                                ifnull(sum(case when month(head.dateid)=1 then detail.db else 0 end),0) as jandb,
                                                ifnull(sum(case when month(head.dateid)=2 then detail.db else 0 end),0) as febdb,
                                                ifnull(sum(case when month(head.dateid)=3 then detail.db else 0 end),0) as mardb,
                                                ifnull(sum(case when month(head.dateid)=4 then detail.db else 0 end),0) as aprdb,
                                                ifnull(sum(case when month(head.dateid)=5 then detail.db else 0 end),0) as maydb,
                                                ifnull(sum(case when month(head.dateid)=6 then detail.db else 0 end),0) as jundb,
                                                ifnull(sum(case when month(head.dateid)=7 then detail.db else 0 end),0) as juldb,
                                                ifnull(sum(case when month(head.dateid)=8 then detail.db else 0 end),0) as augdb,
                                                ifnull(sum(case when month(head.dateid)=9 then detail.db else 0 end),0) as sepdb,
                                                ifnull(sum(case when month(head.dateid)=10 then detail.db else 0 end),0)as octdb,
                                                ifnull(sum(case when month(head.dateid)=11 then detail.db else 0 end),0)as novdb,
                                                ifnull(sum(case when month(head.dateid)=12 then detail.cr else 0 end),0)as decdb
                                        from ((lahead as head
                                        left join ladetail as detail on detail.trno=head.trno)
                                        left join coa on coa.acnoid=detail.acnoid)
                                        left join cntnum on cntnum.trno=head.trno
                                        where head.dateid between  '" . $start . "' and '" . $end . "' " . $filter . "
                                        group by coa.acno $jc) as x on x.acno=coa.acno
                            group by coa.acno, coa.acnoname, coa.levelid,coa.detail,x.cr,x.db,x.jandb,x.febdb,x.mardb,x.aprdb,
                                    x.maydb,x.jundb,x.juldb,x.augdb,x.sepdb,x.octdb,x.novdb,x.decdb,
                                    x.jancr,x.febcr,x.marcr, x.aprcr,x.maycr,x.juncr,x.julcr,x.augcr,x.sepcr,x.octcr,x.novcr,x.deccr
                             ) as j
                        group by  acno, acnoname, levelid,detail
                        order by acno, acnoname, detail";
                break;
        }


        $result = $this->coreFunctions->opentable($query);
        $array = json_decode(json_encode($result), true); // for convert to array
        return $array;
    }

    public function reportplotting($config)
    {
        $result = $this->default_query($config);
        $reportdata =  $this->DEFAULT_TRIAL_BALANCE_LAYOUT($config, $result);
        return $reportdata;
    }

    private function DEFAULT_HEADER_LAYOUT($params)
    {
        $font = $this->companysetup->getrptfont($params['params']);
        $fontsize10 = '10';
        $str = '';

        $center1 = $params['params']['center'];
        $username = $params['params']['user'];

        $isposted = $params['params']['dataparams']['posttype'];
        $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
        $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));

        $center = $params['params']['dataparams']['center'];
        $costcenter = $params['params']['dataparams']['name'];

        if ($center == '') {
            $center = "ALL";
        }
        if ($costcenter == '') {
            $costcenter = 'ALL';
        }


        switch ($isposted) {
            case 0:
                $isposted = 'posted';
                break;
            case 1:
                $isposted = 'unposted';
            case 2:
                $isposted = 'all';
                break;
        }



        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center1, $username, $params);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($this->modulename, null, null, false, '1px solid ', '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('Center :' . $center, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('Cost Center :' . $costcenter, null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

        $str .= $this->reporter->col('Transaction :' . strtoupper($isposted), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');
        $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'L', $font, $fontsize10, '', '', '');

        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
    {
        $str = '';
        $companyid = $config['params']['companyid'];

        $month = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '100', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '310', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
        foreach ($month as $index => $value) {
            $str .= $this->reporter->col(strtoupper($value), '180', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
        }
        $str .= $this->reporter->col('TOTAL', '180', '', '', $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('ACCOUNT', '100', '', '', $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ACCOUNT TITLE', '310', '', '', $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('DEBIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CREDIT', '90', '', '', $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    private function DEFAULT_TRIAL_BALANCE_LAYOUT($params, $data)
    {

        $border = '1px solid';
        $border_line = '';
        $alignment = '';

        $font = $this->companysetup->getrptfont($params['params']);
        $font_size = '10';
        $fontsize11 = 11;

        $str = "";
        $count = 71;
        $page = 70;
        $this->reporter->linecounter = 0;

        if (empty($data)) {
            return $this->othersClass->emptydata($params);
        }

        $str .= $this->reporter->beginreport();

        $str .= $this->DEFAULT_HEADER_LAYOUT($params);
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        $totaldb = 0;
        $totalcr = 0;
        $total = 0;
        $grandtotaldb = 0;
        $grandtotalcr = 0;
        $jandb = 0;
        $jancr = 0;
        $febdb = 0;
        $febcr = 0;
        $mardb = 0;
        $marcr = 0;
        $aprdb = 0;
        $aprcr = 0;
        $maydb = 0;
        $maycr = 0;
        $jundb = 0;
        $juncr = 0;
        $juldb = 0;
        $julcr = 0;
        $augdb = 0;
        $augcr = 0;
        $sepdb = 0;
        $sepcr = 0;
        $octdb = 0;
        $octcr = 0;
        $novdb = 0;
        $novcr = 0;
        $decdb = 0;
        $deccr = 0;

        $align = 'C';
        if ($params['params']['companyid'] == 8) { //maxipro
            $align = 'R';
        }
        for ($i = 0; $i < count($data); $i++) {

            $totaldb = $data[$i]['jandb'] + $data[$i]['febdb']  + $data[$i]['mardb'] + $data[$i]['aprdb']
                + $data[$i]['maydb'] + $data[$i]['jundb'] + $data[$i]['juldb'] +  $data[$i]['augdb'] + $data[$i]['sepdb'] + $data[$i]['octdb'] + $data[$i]['novdb'] + $data[$i]['decdb'];


            $totalcr = $data[$i]['jancr'] + $data[$i]['febcr'] + $data[$i]['marcr'] + $data[$i]['aprcr']
                + $data[$i]['maycr'] + $data[$i]['juncr'] + $data[$i]['julcr'] + $data[$i]['augcr'] + $data[$i]['sepcr'] + $data[$i]['octcr'] + $data[$i]['novcr'] + $data[$i]['deccr'];

            $total = $totaldb + $totalcr;

            if ($total != 0) {
                $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data[$i]['acno'], '100', '', '', $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['acnoname'], '310', '', '', $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['jandb'] == 0 ? '-' : number_format($data[$i]['jandb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['jancr'] == 0 ? '-' : number_format($data[$i]['jancr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['febdb'] == 0 ? '-' : number_format($data[$i]['febdb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['febcr'] == 0 ? '-' : number_format($data[$i]['febcr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['mardb'] == 0 ? '-' : number_format($data[$i]['mardb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['marcr'] == 0 ? '-' : number_format($data[$i]['marcr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['aprdb'] == 0 ? '-' : number_format($data[$i]['aprdb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['aprcr'] == 0 ? '-' : number_format($data[$i]['aprcr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['maydb'] == 0 ? '-' : number_format($data[$i]['maydb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['maycr'] == 0 ? '-' : number_format($data[$i]['maycr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['jundb'] == 0 ? '-' : number_format($data[$i]['jundb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['juncr'] == 0 ? '-' : number_format($data[$i]['juncr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['juldb'] == 0 ? '-' : number_format($data[$i]['juldb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['julcr'] == 0 ? '-' : number_format($data[$i]['julcr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['augdb'] == 0 ? '-' : number_format($data[$i]['augdb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['augcr'] == 0 ? '-' : number_format($data[$i]['augcr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['sepdb'] == 0 ? '-' : number_format($data[$i]['sepdb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['sepcr'] == 0 ? '-' : number_format($data[$i]['sepcr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['octdb'] == 0 ? '-' : number_format($data[$i]['octdb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['octcr'] == 0 ? '-' : number_format($data[$i]['octcr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['novdb'] == 0 ? '-' : number_format($data[$i]['novdb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['novcr'] == 0 ? '-' : number_format($data[$i]['novcr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->col($data[$i]['decdb'] == 0 ? '-' : number_format($data[$i]['decdb'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($data[$i]['deccr'] == 0 ? '-' : number_format($data[$i]['deccr'], 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $jandb += $data[$i]['jandb'];
                $jancr += $data[$i]['jancr'];
                $febdb += $data[$i]['febdb'];
                $febcr += $data[$i]['febcr'];
                $mardb += $data[$i]['mardb'];
                $marcr += $data[$i]['marcr'];
                $aprdb += $data[$i]['aprdb'];
                $aprcr += $data[$i]['aprcr'];
                $maydb += $data[$i]['maydb'];
                $maycr += $data[$i]['maycr'];
                $jundb += $data[$i]['jundb'];
                $juncr += $data[$i]['juncr'];
                $juldb += $data[$i]['juldb'];
                $julcr += $data[$i]['julcr'];
                $augdb += $data[$i]['augdb'];
                $augcr += $data[$i]['augcr'];
                $sepdb += $data[$i]['sepdb'];
                $sepcr += $data[$i]['sepcr'];
                $octdb += $data[$i]['octdb'];
                $octcr += $data[$i]['octcr'];
                $novdb += $data[$i]['novdb'];
                $novcr += $data[$i]['novcr'];
                $decdb += $data[$i]['decdb'];
                $deccr += $data[$i]['deccr'];

                $grandtotaldb += $totaldb;
                $grandtotalcr += $totalcr;
                $str .= $this->reporter->col($totaldb == 0 ? '-' : number_format($totaldb, 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');
                $str .= $this->reporter->col($totalcr == 0 ? '-' : number_format($totalcr, 2), '90', '', '', $border, '', $align, $font, $font_size, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }



            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
                if (!$allowfirstpage) {
                    $str .= $this->DEFAULT_HEADER_LAYOUT($params);
                }
                $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);

                $page = $page + $count;
            }
        } //END FOR EACH

        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('     ', '100', '', '', '1px solid ', 'TB', '', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('GRAND TOTAL :', '310', '', '', '1px solid ', 'TB', 'L', $font, $font_size, 'B', '', '');


        $str .= $this->reporter->col($jandb == 0 ? '-' : number_format($jandb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($jancr == 0 ? '-' : number_format($jancr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($febdb == 0 ? '-' : number_format($febdb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($febcr == 0 ? '-' : number_format($febcr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($mardb == 0 ? '-' : number_format($mardb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($marcr == 0 ? '-' : number_format($marcr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($aprdb == 0 ? '-' : number_format($aprdb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($aprcr == 0 ? '-' : number_format($febcr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($maydb == 0 ? '-' : number_format($maydb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($maycr == 0 ? '-' : number_format($maycr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($jundb == 0 ? '-' : number_format($jundb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($juncr == 0 ? '-' : number_format($juncr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($juldb == 0 ? '-' : number_format($juldb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($julcr == 0 ? '-' : number_format($julcr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($augdb == 0 ? '-' : number_format($augdb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($augcr == 0 ? '-' : number_format($augcr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($sepdb == 0 ? '-' : number_format($sepdb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($sepcr == 0 ? '-' : number_format($sepcr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($octdb == 0 ? '-' : number_format($octdb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($octcr == 0 ? '-' : number_format($octcr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($novdb == 0 ? '-' : number_format($novdb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($novcr == 0 ? '-' : number_format($novcr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col($decdb == 0 ? '-' : number_format($decdb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col($deccr == 0 ? '-' : number_format($deccr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');

        $str .= $this->reporter->col(number_format($grandtotaldb, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '30', '', '', '1px solid ', 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(number_format($grandtotalcr, 2), '60', '', '', $border, 'TB', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();
        return $str;
    } //end fn

}//end class