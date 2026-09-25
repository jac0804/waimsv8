<?php

namespace App\Http\Classes\modules\tableentry;

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

class viewarmonthly
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'AR Monthly';
    public $gridname = 'inventory';
    private $coreFunctions;
    public $style = 'width:100%';
    public $tablelogs = 'masterfile_log';
    public $tablelogs_del = 'del_masterfile_log';
    public $showclosebtn = false;
    private $reporter;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->reporter = new SBCPDF;
    }

    public function getAttrib()
    {
        $attrib = array(
            'load' => 0
        );
        return $attrib;
    }

    public function createTab($config)
    {
         $columns = ['year', 'total', 'janamt', 'febamt', 'maramt', 'apramt', 'mayamt', 'junamt', 'julamt', 'augamt', 'sepamt', 'octamt', 'novamt', 'decamt'];

        foreach ($columns as $key => $value) {
            $$value = $key;
        }
        $tab = [$this->gridname => ['gridcolumns' => $columns ]];

        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
    

        $obj[0][$this->gridname]['columns'][$year]['label'] = "Year";
        $obj[0][$this->gridname]['columns'][$year]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$year]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$year]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$total]['label'] = "Total AR";
        $obj[0][$this->gridname]['columns'][$total]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$total]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$total]['style'] = 'width:20px;whiteSpace: normal;min-width:20px;';


        $obj[0][$this->gridname]['columns'][$janamt]['label'] = "Jan";
        $obj[0][$this->gridname]['columns'][$janamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$janamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$janamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$febamt]['label'] = "Feb";
        $obj[0][$this->gridname]['columns'][$febamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$febamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$febamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$maramt]['label'] = "Mar";
        $obj[0][$this->gridname]['columns'][$maramt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$maramt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$maramt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$apramt]['label'] = "Apr";
        $obj[0][$this->gridname]['columns'][$apramt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$apramt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$apramt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$mayamt]['label'] = "May";
        $obj[0][$this->gridname]['columns'][$mayamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$mayamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$mayamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$junamt]['label'] = "Jun";
        $obj[0][$this->gridname]['columns'][$junamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$junamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$junamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$julamt]['label'] = "Jul";
        $obj[0][$this->gridname]['columns'][$julamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$julamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$julamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$augamt]['label'] = "Aug";
        $obj[0][$this->gridname]['columns'][$augamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$augamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$augamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$sepamt]['label'] = "Sep";
        $obj[0][$this->gridname]['columns'][$sepamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$sepamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$sepamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$octamt]['label'] = "Oct";
        $obj[0][$this->gridname]['columns'][$octamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$octamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$octamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$novamt]['label'] = "Nov";
        $obj[0][$this->gridname]['columns'][$novamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$novamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$novamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        $obj[0][$this->gridname]['columns'][$decamt]['label'] = "Dec";
        $obj[0][$this->gridname]['columns'][$decamt]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$decamt]['align'] = 'left';
        $obj[0][$this->gridname]['columns'][$decamt]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

        return $obj;
    }


    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['tableid'];
        $center = $config['params']['center'];
     
            $qry= "select
                    year(dateid) as year,
                    round(sum(balance), 2) as total,
                    case when sum(if(month(dateid) = 1,  balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 1,  balance, 0)), 2) end as janamt,
                    case when sum(if(month(dateid) = 2,  balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 2,  balance, 0)), 2) end as febamt,
                    case when sum(if(month(dateid) = 3,  balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 3,  balance, 0)), 2) end as maramt,
                    case when sum(if(month(dateid) = 4,  balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 4,  balance, 0)), 2) end as apramt,
                    case when sum(if(month(dateid) = 5,  balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 5,  balance, 0)), 2) end as mayamt,
                    case when sum(if(month(dateid) = 6,  balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 6,  balance, 0)), 2) end as junamt,
                    case when sum(if(month(dateid) = 7,  balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 7,  balance, 0)), 2) end as julamt,
                    case when sum(if(month(dateid) = 8,  balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 8,  balance, 0)), 2) end as augamt,
                    case when sum(if(month(dateid) = 9,  balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 9,  balance, 0)), 2) end as sepamt,
                    case when sum(if(month(dateid) = 10, balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 10, balance, 0)), 2) end as octamt,
                    case when sum(if(month(dateid) = 11, balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 11, balance, 0)), 2) end as novamt,
                    case when sum(if(month(dateid) = 12, balance, 0)) = 0 then '-' else round(sum(if(month(dateid) = 12, balance, 0)), 2) end as decamt
                from (
                    select 'p' as tr, date(ar.dateid) as dateid,
                        (case when ar.db>0 then ar.bal else (ar.bal*-1) end) as balance, ar.docno
                    from (arledger as ar
                    left join client on client.clientid=ar.clientid)
                    left join cntnum on cntnum.trno=ar.trno
                    left join glhead as head on head.trno=ar.trno
                    left join gldetail as detail on detail.trno=ar.trno and detail.line=ar.line
                    left join coa on coa.acnoid=detail.acnoid
                    where ar.bal<>0 and left(coa.alias,2)='AR' and client.clientid=$clientid and cntnum.center='$center'

                    union all

                    select 'u' as tr, date(head.dateid) as dateid, detail.db as balance, head.docno
                    from (((lahead as head
                    left join ladetail as detail on detail.trno=head.trno)
                    left join client on client.client=head.client)
                    left join coa on coa.acnoid=detail.acnoid)
                    left join cntnum on cntnum.trno=head.trno
                    where head.doc in ('ar','gj') and left(coa.alias,2)='AR' and detail.refx = 0 and client.clientid=$clientid and cntnum.center='$center'

                    union all

                    select 'u' as tr, date(head.dateid) as dateid, sum(stock.ext) as balance, head.docno
                    from (((lahead as head
                    left join lastock as stock on stock.trno=head.trno)
                    left join client on client.client=head.client))
                    left join cntnum on cntnum.trno=head.trno
                    where head.doc = 'sj' and client.clientid=$clientid and cntnum.center='$center'
                    group by tr, head.dateid, docno
                ) as base
                group by year(dateid)
                order by year(dateid)";
        $data = $this->coreFunctions->opentable($qry);
        return $data;
    }
} //end class