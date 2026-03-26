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

class construction_instruction_report
{
    public $modulename = 'Construction Instruction Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;

    public $reportParams  = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];



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
        $fields = ['radioprint', 'radioreporttype', 'radioposttype', 'dcentername', 'housemodel2', 'reportusers', 'start', 'end'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', true);
        data_set($col1, 'end.required', true);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);

        data_set(
            $col1,
            'radioposttype.options',
            [
                ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
                ['label' => 'All', 'value' => '2', 'color' => 'teal']
            ]
        );
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        return $this->coreFunctions->opentable(
            "select
            'default' as print, adddate(left(now(),10),-360) as start,left(now(),10) as `end`,
            '0' as posttype,'0' as reporttype,'' as userid,'' as username,'' as reportusers, 
             '0' as housemodel, '' as housemodel2 ,  '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername "
        );
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
        $reporttype = $config['params']['dataparams']['reporttype'];
        switch ($reporttype) {
            case 0: // summarized
                $result = $this->reportDefaultLayout_summarized($config);
                break;
            case 1: // detailed
                $result = $this->reportDefaultLayout_detailed($config);
                break;
        }
        return $result;
    }

    public function reportDefault($config)
    {

        $query = $this->default_QUERY($config);

        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $posttype   = $config['params']['dataparams']['posttype'];
        $reporttype = $config['params']['dataparams']['reporttype'];
        $hm = $config['params']['dataparams']['housemodel2'];

        $filter = "";
        if ($hm != "") {
            $filter .= " and house.model = '$hm' ";
        }
        $fcenter    = $config['params']['dataparams']['center'];
        if ($fcenter != "") {
            $filter .= " and num.center = '$fcenter'";
        }

        switch ($reporttype) {
            case 1: //detailed
                switch ($posttype) {
                    case 1: // unposted
                        $query = "select  'UNPOSTED' as statt, head.trno,  head.docno, head.ourref, left(head.dateid,10) as dateid,
                    head.rem as note, head.yourref,
                    ifnull(i.barcode,'') as barcode,ifnull(head.qty,'') as qty,ifnull(head.uom,'') as uom, 
                    ifnull(i.itemname,'') as itemname,
                     left(head.voiddate,10) as voiddate, ifnull(house.model,'') as housemodel2, num.center,
                    i.itemid, stock.itemname as itemname1, stock.line, stock.barcode as barcode1, stock.uom as uom1, 
                    stock.rem as note1,stock.qty as qty1, stock.rrqty,house.line as hm
            from cihead as head
                    left join cistock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=head.itemid
                    left join housemodel as house on house.line = head.housemodel
                    left join transnum as num on num.trno = head.trno where head.dateid between '$start' and '$end' and stock.rrqty <> 0  $filter ";
                        break;
                    case 0: // posted
                        $query = " select 'POSTED' as statt, head.trno,  head.docno, head.ourref, left(head.dateid,10) as dateid,
                    head.rem as note, head.yourref,
                    ifnull(i.barcode,'') as barcode, ifnull(head.qty,'') as qty,ifnull(head.uom,'') as uom, 
                    ifnull(i.itemname,'') as itemname,
                    left(head.voiddate,10) as voiddate, ifnull(house.model,'') as housemodel2, num.center,
                    i.itemid, stock.itemname as itemname1, stock.line, stock.barcode as barcode1, stock.uom as uom1, 
                    stock.rem as note1,stock.qty as qty1, stock.rrqty,house.line as hm
            from hcihead as head
                    left join hcistock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=head.itemid
                    left join housemodel as house on house.line = head.housemodel
                    left join transnum as num on num.trno = head.trno where head.dateid between '$start' and '$end' and stock.rrqty <> 0  $filter ";
                        break;
                    default: // all

                        $query = "select 'UNPOSTED' as statt, head.trno,  head.docno, head.ourref, left(head.dateid,10) as dateid,
                    head.rem as note, head.yourref,
                    ifnull(i.barcode,'') as barcode,ifnull(head.qty,'') as qty,ifnull(head.uom,'') as uom, 
                    ifnull(i.itemname,'') as itemname,
                     left(head.voiddate,10) as voiddate, ifnull(house.model,'') as housemodel2, num.center,
                    i.itemid, stock.itemname as itemname1, stock.line, stock.barcode as barcode1, stock.uom as uom1, 
                    stock.rem as note1,stock.qty as qty1, stock.rrqty,house.line as hm
            from cihead as head
                    left join cistock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=head.itemid
                    left join housemodel as house on house.line = head.housemodel
                    left join transnum as num on num.trno = head.trno where head.dateid between '$start' and '$end' and stock.rrqty <> 0  $filter
           union all 
          
                    select 'POSTED' as statt, head.trno, head.docno, head.ourref, left(head.dateid,10) as dateid,
                    head.rem as note, head.yourref,
                    ifnull(i.barcode,'') as barcode, ifnull(head.qty,'') as qty,ifnull(head.uom,'') as uom, 
                    ifnull(i.itemname,'') as itemname,
                    left(head.voiddate,10) as voiddate, ifnull(house.model,'') as housemodel2, num.center,
                    i.itemid, stock.itemname as itemname1, stock.line, stock.barcode as barcode1, stock.uom as uom1, 
                    stock.rem as note1,stock.qty as qty1, stock.rrqty,house.line as hm
            from hcihead as head
                    left join hcistock as stock on stock.trno=head.trno
                    left join item as i on i.itemid=head.itemid
                    left join housemodel as house on house.line = head.housemodel
                    left join transnum as num on num.trno = head.trno where head.dateid between '$start' and '$end' and stock.rrqty <> 0  $filter order by docno";
                        break;
                }
                break;

            default: //summarized
                switch ($posttype) {
                    case 1: // unposted
                        $query = "select  'UNPOSTED' as statt, head.trno,  head.docno, head.ourref, left(head.dateid,10) as dateid,
                    head.rem as note, head.yourref, ifnull(house.model,'') as housemodel2,house.line as hm
            from cihead as head
                    left join cistock as stock on stock.trno=head.trno
                    left join housemodel as house on house.line = head.housemodel 
                     left join transnum as num on num.trno = head.trno
                    where head.dateid between '$start' and '$end' and stock.rrqty <> 0  $filter ";
                        break;
                    case 0: // posted
                        $query = " select 'POSTED' as statt, head.trno,  head.docno, head.ourref, left(head.dateid,10) as dateid,
                    head.rem as note, head.yourref, ifnull(house.model,'') as housemodel2,house.line as hm
            from hcihead as head
                    left join hcistock as stock on stock.trno=head.trno
                    left join housemodel as house on house.line = head.housemodel
                     left join transnum as num on num.trno = head.trno
                    where head.dateid between '$start' and '$end' and stock.rrqty <> 0  $filter";
                        break;
                    default: // all
                        $query = "select  'UNPOSTED' as statt, head.trno,  head.docno, head.ourref, left(head.dateid,10) as dateid,
                    head.rem as note, head.yourref, ifnull(house.model,'') as housemodel2,house.line as hm
            from cihead as head
                    left join cistock as stock on stock.trno=head.trno
                    left join housemodel as house on house.line = head.housemodel 
                     left join transnum as num on num.trno = head.trno
                    where head.dateid between '$start' and '$end' and stock.rrqty <> 0  $filter
         
                    union all 
          
          select 'POSTED' as statt, head.trno,  head.docno, head.ourref, left(head.dateid,10) as dateid,
                    head.rem as note, head.yourref, ifnull(house.model,'') as housemodel2,house.line as hm
            from hcihead as head
                    left join hcistock as stock on stock.trno=head.trno
                    left join housemodel as house on house.line = head.housemodel
                     left join transnum as num on num.trno = head.trno
                    where head.dateid between '$start' and '$end' and stock.rrqty <> 0  $filter";
                        break;
                }
                break;
        }
        return $query;
    }


    public function reportDefaultLayout_detailed($config)
    {
        $result = $this->reportDefault($config);

        $count = 41;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }
        $str .= $this->reporter->beginreport($layoutsize);

        $docno = "";
        $first_docno = true;
        if (!empty($result)) {
            foreach ($result as $key => $data) {
                if ($docno != "" && $docno != $data->docno) {
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '1000', null, false, $border, 'T', 'R', $font, '14', 'B', '10px', '2px');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                } //end if
                if ($docno == "" || $docno != $data->docno) {
                    $docno = $data->docno;
                    if (!$first_docno) {
                        $str .= $this->reporter->endtable();
                        $str .= $this->reporter->page_break();
                    } else {
                        $first_docno = false;
                    }

                    $str .= $this->default_header($config);
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Doc#: ' . $data->docno, '233', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
                    $str .= $this->reporter->col('Date: ' . $data->dateid, '233', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
                    $str .= $this->reporter->col('', '233', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
                    $str .= $this->reporter->endrow();

                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Notes: ' . $data->note, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '10px', '2px');
                    $str .= $this->reporter->col('Item Name', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '10px', '2px');
                    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '10px', '2px');
                    $str .= $this->reporter->col('UOM', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '10px', '2px');
                    $str .= $this->reporter->col('Notes', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '10px', '2px');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->barcode1, '100', null, false, $border, '', 'C', $font, $fontsize, '', '10px', '2px');
                $str .= $this->reporter->col($data->itemname1, '250', null, false, $border, '', 'L', $font, $fontsize, '', '10px', '2px');
                $str .= $this->reporter->col(number_format($data->rrqty, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '10px', '2px');
                $str .= $this->reporter->col($data->uom1, '100', null, false, $border, '', 'C', $font, $fontsize, '', '10px', '2px');
                $str .= $this->reporter->col($data->note1, '150', null, false, $border, '', 'L', $font, $fontsize, '', '10px', '2px');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->addline();

                $str .= $this->reporter->endtable();


                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header($config);
                    $page = $page + $count;
                } //end if

            }
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1000', null, false, $border, 'T', 'R', $font, '14', 'B', '10px', '2px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();

        return $str;
    }

    public function reportDefaultLayout_summarized($config)
    {
        $result = $this->reportDefault($config);

        $count = 41;
        $page = 40;
        $this->reporter->linecounter = 0;
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header($config);

        $str .= $this->reporter->printline();
        $str .= $this->tableheader_summarized($layoutsize, $config);

        if (!empty($result)) {
            foreach ($result as $key => $data) {
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($data->dateid, '333', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->docno, '333', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->col($data->statt, '333', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                if ($this->reporter->linecounter == $page) {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header($config);
                    $str .= $this->tableheader_summarized($layoutsize, $config);
                    $page = $page + $count;
                } //end if
            }
        }
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '1000', null, false, $border, 'T', 'R', $font, '14', 'B', '10px', '2px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }


    public function default_header($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $filterusername  = $config['params']['dataparams']['username'];

        $posttype   = $config['params']['dataparams']['posttype'];

        $reporttype = $config['params']['dataparams']['reporttype'];
        $hm = $config['params']['dataparams']['housemodel2'];

        if ($reporttype == 0) {
            $reporttype = 'Summarized';
        } else {
            $reporttype = 'Detailed';
        }

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

        if ($hm != '') {
            $hmodel = $hm;
        } else {
            $hmodel = 'ALL';
        }

        $str = '';
        $layoutsize = '1000';
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
        $str .= $this->reporter->col('Construction Instruction Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('User: ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('House Model: ' . $hmodel, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function tableheader_summarized($layoutsize, $config)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $company   = $config['params']['companyid'];
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '333', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT #', '333', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('STATUS', '333', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}//end class