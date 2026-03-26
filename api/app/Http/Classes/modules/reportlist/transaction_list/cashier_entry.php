<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class cashier_entry
{
    public $modulename = 'Cashier Entry';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1800px;max-width:1800px;';
    public $directprint = false;

    public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1800'];



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

        $fields = ['radioprint', 'start', 'end', 'dclientname',  'dcentername'];

        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'dcentername.required', true);
        data_set($col1, 'dclientname.lookupclass', 'lookupclient');
        data_set($col1, 'dclientname.label', 'Customer');
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);


        $fields = ['radioposttype'];
        $col2 = $this->fieldClass->create($fields);
        data_set(
            $col2,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );


        $fields = ['print'];
        $col3 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS

        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


        $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,
                        '' as userid,'0' as posttype,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                        '' as client,'' as clientname, '0' as clientid, '' as dclientname";
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

    public function reportplotting($config)
    {
        $result = $this->reportDefaultLayout_SUMMARIZED($config);

        return $result;
    }

    public function reportDefault($config)
    {

        $query = $this->default_QUERY($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $client     = $config['params']['dataparams']['client'];
        $clientid     = $config['params']['dataparams']['clientid'];
        $clientname     = $config['params']['dataparams']['clientname'];
        $posttype   = $config['params']['dataparams']['posttype'];
        $filter = "";

        if ($client != "") {
            $filter .= " and head.clientid = '$clientid' ";
        }

        $fcenter    = $config['params']['dataparams']['center'];
        if ($fcenter != "") {
            $filter .= " and num.center = '$fcenter'";
        }


        switch ($posttype) {
            case 0: // posted
                $query = " select head.docno,left(head.dateid,10) as dateid,head.clientname,
                            head.address,rc3.category as purposeofpayment,rc1.category as trnxtype2,
                            head.rem,head.yourref,head.ourref,head.checkinfo,format(head.amount,2) as amount,head.bank,
                             date(head.checkdate) as checkdate,head.rem2,head.sicsino,
                            head.drno,
                            rc2.category as modeofpayment2,
                            ifnull(ds.docno,'') as deposit

                        from hcehead as head
                        left join transnum as num on num.trno = head.trno
                            left join transnum as ds on ds.trno = num.dstrno
                            left join client as cl on cl.clientid = head.clientid
                            left join reqcategory as rc1 on rc1.line = head.trnxtid
                            left join reqcategory as rc2 on rc2.line = head.mpid
                            left join reqcategory as rc3 on rc3.line = head.ppid
                        where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                         group by head.docno,head.dateid,head.clientname,
                            head.address,rc3.category,rc1.category,
                            head.rem,head.yourref,head.ourref,head.checkinfo,head.amount,head.bank,
                            head.checkdate,head.rem2,head.sicsino,
                            head.drno,rc2.category,ds.docno
                            order by head.clientname";
                break;

            case 1: // unposted
                $query = " select head.docno,left(head.dateid,10) as dateid,head.clientname,
                            head.address,rc3.category as purposeofpayment,rc1.category as trnxtype2,
                            head.rem,head.yourref,head.ourref,head.checkinfo,format(head.amount,2) as amount,head.bank,
                             date(head.checkdate) as checkdate,head.rem2,head.sicsino,
                            head.drno,
                            rc2.category as modeofpayment2,
                            ifnull(ds.docno,'') as deposit

                        from cehead as head
                        left join transnum as num on num.trno = head.trno
                            left join transnum as ds on ds.trno = num.dstrno
                            left join client as cl on cl.clientid = head.clientid
                            left join reqcategory as rc1 on rc1.line = head.trnxtid
                            left join reqcategory as rc2 on rc2.line = head.mpid
                            left join reqcategory as rc3 on rc3.line = head.ppid
                        where  date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                         group by head.docno,head.dateid,head.clientname,
                            head.address,rc3.category,rc1.category,
                            head.rem,head.yourref,head.ourref,head.checkinfo,head.amount,head.bank,
                            head.checkdate,head.rem2,head.sicsino,
                            head.drno,rc2.category,ds.docno
                            order by head.clientname";
                break;

            default: // all
                $query = " select head.docno,left(head.dateid,10) as dateid,head.clientname,
                            head.address,rc3.category as purposeofpayment,rc1.category as trnxtype2,
                            head.rem,head.yourref,head.ourref,head.checkinfo,format(head.amount,2) as amount,head.bank,
                            date(head.checkdate) as checkdate,head.rem2,head.sicsino,
                            head.drno,
                            rc2.category as modeofpayment2,
                            ifnull(ds.docno,'') as deposit

                        from cehead as head
                        left join transnum as num on num.trno = head.trno
                            left join transnum as ds on ds.trno = num.dstrno
                            left join client as cl on cl.clientid = head.clientid
                            left join reqcategory as rc1 on rc1.line = head.trnxtid
                            left join reqcategory as rc2 on rc2.line = head.mpid
                            left join reqcategory as rc3 on rc3.line = head.ppid
                        where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
                         group by head.docno,head.dateid,head.clientname,
                            head.address,rc3.category,rc1.category,
                            head.rem,head.yourref,head.ourref,head.checkinfo,head.amount,head.bank,
                            head.checkdate,head.rem2,head.sicsino,
                            head.drno,rc2.category,ds.docno

                    union all

                    select head.docno,left(head.dateid,10) as dateid,head.clientname,
                            head.address,rc3.category as purposeofpayment,rc1.category as trnxtype2,
                            head.rem,head.yourref,head.ourref,head.checkinfo,format(head.amount,2) as amount,head.bank,
                             date(head.checkdate) as checkdate,head.rem2,head.sicsino,
                            head.drno,
                            rc2.category as modeofpayment2,
                            ifnull(ds.docno,'') as deposit

                        from hcehead as head
                        left join transnum as num on num.trno = head.trno
                            left join transnum as ds on ds.trno = num.dstrno
                            left join client as cl on cl.clientid = head.clientid
                            left join reqcategory as rc1 on rc1.line = head.trnxtid
                            left join reqcategory as rc2 on rc2.line = head.mpid
                            left join reqcategory as rc3 on rc3.line = head.ppid
                        where  date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
                        group by head.docno,head.dateid,head.clientname,
                            head.address,rc3.category,rc1.category,
                            head.rem,head.yourref,head.ourref,head.checkinfo,head.amount,head.bank,
                            head.checkdate,head.rem2,head.sicsino,
                            head.drno,rc2.category,ds.docno
                            order by clientname";

                break;
        } // end switch posttype
        return $query;
    }


    public function header_DEFAULT($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $posttype   = $config['params']['dataparams']['posttype'];

        switch ($posttype) {
            case 0:
                $posttype = 'Posted';
                break;

            case 1:
                $posttype = 'Unposted';
                break;

            default:
                $posttype = 'All';
                break;
        }


        $str = '';
        $layoutsize = '1800';
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


        $user = "ALL USERS";


        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Cashier Entry Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
        $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_SUMMARIZED($config)
    {
        $result = $this->reportDefault($config);
        $count = 61;
        $page = 60;
        $this->reporter->linecounter = 0;

        $str = '';
        $layoutsize = '1800';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $fontsizes = "13";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->header_DEFAULT($config);
        $str .= $this->tableheader($layoutsize, $config);
        $clientname = '';
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->begintable($layoutsize);
                if ($clientname == '' || $clientname != $data->clientname) {
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data->clientname, '1800', null, false, $border, '', 'L', $font, $fontsizes, '', '', '');
                    $clientname = $data->clientname;
                    $str .= $this->reporter->endrow();
                }
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->addline();

                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->address, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->purposeofpayment, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->trnxtype2, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->modeofpayment2, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->yourref, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->ourref, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->sicsino, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->drno, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->amount, '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

                $str .= $this->reporter->col($data->bank, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkinfo, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->checkdate, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');


                $str .= $this->reporter->col($data->rem, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->rem2, '90', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
                $str .= $this->reporter->col($data->deposit, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
                    if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
                    $str .= $this->tableheader($layoutsize, $config);
                    $page = $page + $count;
                } //end if
            }
        }

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function tableheader($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('Docno', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        // $str .= $this->reporter->col('Customer Name', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Address', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Payment Purpose', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Mode of Payment', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        // $str .= $this->reporter->col('Gcash ', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('CR#', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('OR#', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('SI/CSI#', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DR#', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Amount', '90', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Bank', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('Check Info', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Check Date ', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');


        $str .= $this->reporter->col('Notes', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('MC Unit', '90', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Deposit Slip', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }
}//end class