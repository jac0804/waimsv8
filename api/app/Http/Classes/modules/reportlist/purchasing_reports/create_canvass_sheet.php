<?php

namespace App\Http\Classes\modules\reportlist\purchasing_reports;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use DateTime;
use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class create_canvass_sheet
{
    public $modulename = 'Create Canvass Sheet';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1300'];

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
        $fields = ['radioprint', 'start', 'end', 'effectfromdate', 'effecttodate', 'reportusers', 'categoryname', 'statname', 'repsortby'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'effectfromdate.required', true);
        data_set($col1, 'effecttodate.required', true);
        data_set($col1, 'start.label', 'Transaction Start Date');
        data_set($col1, 'end.label', 'Transaction End Date');
        data_set($col1, 'effectfromdate.label', 'Deadline Start Date');
        data_set($col1, 'effecttodate.label', 'Deadline End Date');
        data_set($col1, 'reportusers.lookupclass', 'lookupusers2');
        data_set($col1, 'categoryname.action', 'lookupreqcategory');
        data_set($col1, 'categoryname.lookupclass', 'lookupreqcategory');
        data_set($col1, 'statname.label', 'Status');
        data_set($col1, 'statname.lookupclass', 'lookupcheckstatATI');
        data_set($col1, 'statname.required', false);

        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $user = $config['params']['user'];
        $center = $config['params']['center'];

        return $this->coreFunctions->opentable("select 
                'default' as print,
                adddate(left(now(),10),-360) as start,
                left(now(),10) as end,
                adddate(left(now(),10),-360) as effectfromdate,
                left(now(),10) as effecttodate,
                '' as userid,
                '' as username,
                '' as categoryname,
                '' as statname,
                '' as reportusers,
                '' as repsortby
                ");
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

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];


        $result = $this->reportDefaultLayout($config);


        return $result;
    }

    public function reportDefault($config)
    {
        // QUERY
        $query = $this->default_QUERY($config);


        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $adminid    = $config['params']['adminid'];

        $startd = date("Y-m-d", strtotime($config['params']['dataparams']['effectfromdate']));
        $endd = date("Y-m-d", strtotime($config['params']['dataparams']['effecttodate']));
        $date = "9998-12-31";
        if ($config['params']['dataparams']['effecttodate'] == $date) {
            $endd = new DateTime('9998-12-31');
            $endd = $endd->format('Y-m-d');
        }
        $filterusername  = $config['params']['dataparams']['username'];

        $category  = $config['params']['dataparams']['categoryname'];
        $stat  = $config['params']['dataparams']['statname'];
        $repsortby  = $config['params']['dataparams']['repsortby'];

        $filter = "";
        $filter1 = "";
        $orderby = "";
        $filterid = "";
        if ($adminid != 0) {
            $trnxtype = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$config['params']['adminid']]);
            if (!empty($trnxtype)) {
                $filterid = " and trnxinfo.trnxtype = '" . $trnxtype . "' ";
            }
        }
        if ($filterusername != "") {
            $filter .= " and user.username = '$filterusername' ";
        }
        if ($category != "") {
            $filter .= " and reqcat.category = '$category' ";
        }
        if ($stat != "") {
            $statid  = $config['params']['dataparams']['statid'];

            switch ($statid) {
                case 5: //Pending
                    $filter .= " and hcd.status = 0 ";
                    $filter1 .= " and stock.iscanvass =0";
                    break;
                case 36: //Approved
                    $filter .= " and hcd.status = 1 ";
                    $filter1 .= "";
                    break;
                case 77: //Rejected
                    $filter .= " and hcd.status = 2 ";
                    $filter1 .= " ";
                    break;
            }
        }

        if ($repsortby != "") {
            $repsortname  = $config['params']['dataparams']['name'];
            $orderby = " order by " . $repsortname;
        } else {
            $orderby = " order by head.dateid, head.docno";
        }

        $query = "select head.docno,left(head.dateid,10) as dateid,item.barcode,item.itemname,info.itemdesc,stock.rrqty,
                    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as pending,stock.qa,
                    info.deadline,stock.status,info.ctrlno,ifnull(emp.clientname,'') as empname,
                    case when hcd.status = 0 then 'Pending'
                            when hcd.status = 1 then 'Approved'
                            when hcd.status = 2 then 'Rejected' end as canvasstatus,
                    date(ifnull(info.deadline,'9998-12-31')) as deadline,reqcat.category,
                    ifnull(info.requestorname,'') as requestorname,dept.clientname as departmentname,
                    cd.docno as cddocno,
                    stock.iscanvass,left(t.postdate,10) as postdate
                from hprhead as head
                left join hprstock as stock on stock.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join hheadinfotrans as trnxinfo on trnxinfo.trno=head.trno
                left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line
                left join client as emp on emp.clientid=stock.suppid
                left join client as wh on wh.clientid=stock.whid
                left join reqcategory as reqcat on reqcat.line = head.ourref
                left join hcdstock as hcd on hcd.reqtrno=stock.trno and hcd.reqline=stock.line
                left join hcdhead as cd on cd.trno=hcd.trno
                left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
                left join client as dept on dept.clientid=head.deptid
                left join transnum as t on t.trno=head.trno
                left join uomlist as uom3 on uom3.uom=info.uom3 and uom3.isconvert=1
                left join uomlist as uom2 on uom2.uom=info.uom2 and uom2.isconvert=1
                where t.doc='PR' and stock.qty>((stock.qa+stock.cdqa)+stock.voidqty)
                    and stock.void = 0 and stock.status = 0
                    and date(head.dateid) between '$start' and '$end'
                    and ifnull(date(info.deadline),'9998-12-31') between '$startd' and '$endd' $filter $filterid $filter1
                group by head.docno,head.dateid,item.barcode,item.itemname,info.itemdesc,stock.rrqty,stock.qa,
                        info.deadline,stock.status,info.ctrlno,emp.clientname,
                        stock.qty,uom.factor,hcd.status,
                        reqcat.category,
                        info.requestorname,dept.clientname,cd.docno,
                        stock.iscanvass,t.postdate $orderby";

        return $query;
    }


    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $company   = $config['params']['companyid'];

        $count = 45;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '1400';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);




        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->addline();
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->ctrlno, '70', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->canvasstatus == '' ? 'PR' : 'Canvass', '70', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->dateid, '60', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->itemdesc, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col(number_format($data->rrqty, 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->pending == 0 ? '-' : number_format($data->pending, 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->qa == 0 ? '-' : number_format($data->qa, 2), '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->deadline, '60', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->canvasstatus, '60', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->category, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->requestorname, '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->departmentname, '130', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->postdate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];

        $str = '';
        $layoutsize = '1400';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        if ($filterusername != "") {
            $user = $filterusername;
        } else {
            $user = "ALL USERS";
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Create Canvass Sheet', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '700', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User : ' . $user, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->printline();
        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $company   = $config['params']['companyid'];

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CTRL NO.', '70', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '70', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BARCODE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('ITEM NAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('TEMP DESC', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('REQUEST QTY', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PENDING', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SERVED', '60', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEADLINE', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CATEGORY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('REQUESTOR', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DEPARTMENT', '130', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('POST DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class