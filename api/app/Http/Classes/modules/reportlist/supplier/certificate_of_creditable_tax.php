<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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
use Illuminate\Support\Facades\URL;

class certificate_of_creditable_tax
{
    public $modulename = 'Certificate of Creditable Tax (2307)';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1000px;max-width:1000px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'payor', 'position', 'tin', 'dcentername'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'radioprint.options', [['label' => 'Default', 'value' => 'default', 'color' => 'blue']]);
        data_set($col1, 'dclientname.label', 'Supplier');
        data_set($col1, 'position.label', 'Title');
        data_set($col1, 'dcentername.required', true);
        data_set($col1, 'dclientname.required', true);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
        $paramstr = "select 'default' as print, 
        adddate(left(now(),10),-360) as start, 
        left(now(),10) as end,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
        '' as payor, '' as position, '' as tin,
        '0' as clientid,
        '' as client,
        '' as clientname, 
        '' as dclientname ";

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
        return $this->reportDefaultLayout($config);
    }

    public function reportDefault($config)
    {

        $client    = $config['params']['dataparams']['client'];
        $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end       = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $center    = $config['params']['dataparams']['center'];

        $filter   = "";
        if ($client != "") {
            $filter .= " and client.client = '$client'";
        }

        if ($center != "") {
            $filter .= " and cntnum.center = '$center'";
        }

        $query = "select * from(
        select month(head.dateid) as month,right(year(head.dateid),2) as yr, client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref,detail.postdate,
        sum(detail.db) as db, sum(detail.cr) as cr, detail.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname,
        client.clientid
        
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        left join coa on coa.acnoid=detail.acnoid
        where head.doc in ('cv','pv')  and (detail.isewt = 1 or detail.isvewt=1) and date(head.dateid)  between '$start' and '$end' $filter
         group by head.dateid,  client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref,detail.postdate,
         detail.client, detail.checkno,
        detail.ewtcode,ewtlist.description,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin, center.address, center.zipcode, center.name,
        client.clientid
        


        union all
        select month(head.dateid) as month,right(year(head.dateid),2) as yr,  client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref, detail.postdate,
        sum(detail.db) as db, sum(detail.cr) as cr, dclient.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname,
        client.clientid
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join coa on coa.acnoid=detail.acnoid
        left join client as dclient on dclient.clientid=detail.clientid
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join cntnum on cntnum.trno = head.trno
        left join center on center.code = cntnum.center
        where head.doc in ('cv','pv')  and (detail.isewt = 1 or detail.isvewt=1) and date(head.dateid)  between '$start' and '$end' $filter
        group by  head.dateid,  client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref,detail.postdate,
        dclient.client, detail.checkno,
        detail.ewtcode,ewtlist.description,detail.ewtrate,detail.isvewt,
        client.zipcode, center.tin, center.address, center.zipcode, center.name,
        client.clientid) 
        as tbl order by tbl.ewtdesc,tbl.clientid";
        // var_dump($query);
        // $result1 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);


        // $arrs = [];
        // $arrss = [];
        // $ewt = '';
        // foreach ($result1 as $key => $value) {
        //     $ewtrateval = floatval($value['ewtrate']) / 100;
        //     if ($value['db'] == 0) {
        //         //FOR CR
        //         if ($value['cr'] < 0) {
        //             $db = $value['cr'];
        //         } else {
        //             $db = floatval($value['cr']) * -1;
        //         } //end if

        //         if ($value['isvewt'] == 1) {
        //             $db = $db / 1.12;
        //         }

        //         $ewtamt = $db * $ewtrateval;
        //     } else {
        //         //FOR DB
        //         if ($value['db'] < 0) {
        //             $db = floatval($value['db']) * -1;
        //         } else {
        //             $db = $value['db'];
        //         } //end if

        //         if ($value['isvewt'] == 1) {
        //             $db = $db / 1.12;
        //         }
        //         $ewtamt = $db * $ewtrateval;
        //     } //end if

        //     if ($ewt != $value['ewtcode']) {
        //         $arrs[$value['ewtcode']]['oamt'] = $db;
        //         $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
        //         $arrs[$value['ewtcode']]['month'] = $value['month'];
        //         $arrs[$value['ewtcode']]['clientid'] = $value['clientid'];
        //         $arrs[$value['ewtcode']]['docno'] = $value['docno'];
        //     } else {
        //         array_push($arrss, $arrs);
        //         $arrs[$value['ewtcode']]['oamt'] = $db;
        //         $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
        //         $arrs[$value['ewtcode']]['month'] = $value['month'];
        //         $arrs[$value['ewtcode']]['clientid'] = $value['clientid'];
        //         $arrs[$value['ewtcode']]['docno'] = $value['docno'];
        //     }

        //     $ewt = $value['ewtcode'];
        // } //end for each

        // array_push($arrss, $arrs);
        // $keyers = '';
        // $finalarrs = [];
        // foreach ($arrss as $key => $value) {
        //     foreach ($value as $key => $y) {
        //         if ($keyers == '') {
        //             $keyers = $key;
        //             $finalarrs[$key]['oamt'] = $y['oamt'];
        //             $finalarrs[$key]['xamt'] = $y['xamt'];
        //         } else {
        //             if ($keyers == $key) {
        //                 $finalarrs[$key]['oamt'] = floatval($finalarrs[$key]['oamt']) + floatval($y['oamt']);
        //                 $finalarrs[$key]['xamt'] = floatval($finalarrs[$key]['xamt']) + floatval($y['xamt']);
        //             } else {
        //                 $finalarrs[$key]['oamt'] = $y['oamt'];
        //                 $finalarrs[$key]['xamt'] = $y['xamt'];
        //             } //end if
        //         } //end if
        //         $finalarrs[$key]['month'] = $y['month'];
        //         $finalarrs[$key]['clientid'] = $y['clientid'];
        //         $finalarrs[$key]['docno'] = $y['docno'];
        //     }
        // } //end for each

        // // var_dump($finalarrs);
        // //sample result 
        // //     array(7){
        // // [
        // //     ""
        // // ]=>array(4){
        // //     [
        // //         "oamt"
        // //     ]=>float(27723.214285714)[
        // //         "xamt"
        // //     ]=>float(0)[
        // //         "month"
        // //     ]=>int(9)[
        // //         "clientid"
        // //     ]=>int(4312)
        // // }[
        // //     "WC158"
        // // ]=>array(4){
        // //     [
        // //         "oamt"
        // //     ]=>float(46831842.366072)[
        // //         "xamt"
        // //     ]=>float(468318.42366071)[
        // //         "month"
        // //     ]=>int(10)[
        // //         "clientid"
        // //     ]=>int(3414)
        // // }[



        // if (empty($result1)) {
        //     $returnarr[0]['payee'] = '';
        //     $returnarr[0]['tin'] = '';
        //     $returnarr[0]['payortin'] = '';
        //     $returnarr[0]['address'] = '';
        //     $returnarr[0]['month'] = '';
        //     $returnarr[0]['yr'] = '';
        //     $returnarr[0]['payorcompname'] = '';
        //     $returnarr[0]['payoraddress'] = '';
        //     $returnarr[0]['payorzipcode'] = '';
        //     $returnarr[0]['clientid'] = '';
        //     $returnarr[0]['zipcode'] = '';
        // } else {
        //     $returnarr[0]['payee'] = $result1[0]['clientname'];
        //     $returnarr[0]['tin'] = $result1[0]['tin'];
        //     $returnarr[0]['payortin'] = $result1[0]['payortin'];
        //     $returnarr[0]['address'] = $result1[0]['address'];
        //     $returnarr[0]['month'] = $result1[0]['month'];
        //     $returnarr[0]['yr'] = $result1[0]['yr'];
        //     $returnarr[0]['payorcompname'] = $result1[0]['payorcompname'];
        //     $returnarr[0]['payoraddress'] = $result1[0]['payoraddress'];
        //     $returnarr[0]['payorzipcode'] = $result1[0]['payorzipcode'];
        //     $returnarr[0]['clientid'] = $result1[0]['clientid'];
        //     $returnarr[0]['zipcode'] = $result1[0]['zipcode'];
        // }

        // $result = ['head' => $returnarr, 'detail' => $finalarrs, 'res' => $result1];
        // var_dump($query);
        // return $result;

        return $this->coreFunctions->opentable($query);
    }



    public function reportDefaultLayouts($config)
    {
        $result = $this->reportDefault($config);
        // var_dump($result);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $border = '1px solid';
        // $font = $this->companysetup->getrptfont($config['params']);
        $font = 'verdana';
        $font_size = '10';
        $count = 15;
        $page = 15;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $ewtdata = [];
        foreach ($result as $array_index => $array) {
            // unique key per clientid + docno
            $lookupKey = $array->clientid . '_' . $array->docno;

            // iipunin lahat ng   field
            $ewtdata[$lookupKey][] = [
                'ewtrate' => $array->ewtrate,
                'ewtcode'   => $array->ewtcode,
                'db'      => $array->db,
                'cr'      => $array->cr,
                'isvewt'  => $array->isvewt,
                'ewtdesc' => $array->ewtdesc,
                'month'   => $array->month
            ];
        }


        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '20px;margin-top:10px;margin-left:95px');
        $str .= $this->displayHeader($config);

        $cname = '';
        $docno = '';
        foreach ($result as $key => $data) {

            //pag magkaiba ng clientid ay mgnenext page na  at pag parehas ng clientid pero magkaiba ng docno
            if ($cname == '' || $cname != $data->clientid || ($cname == $data->clientid && $docno != $data->docno)) {
                if ($cname != '') {
                    $str .= $this->reporter->endtable();
                    $str .= $this->reporter->page_break();
                    $str .= $this->displayHeader($config, $layoutsize);
                }

                //  Update cname para sa bagong group
                $cname = $data->clientid;
                $docno = $data->docno;
                $d1 = '';
                $m1 = '';
                $y1 = '';

                $d2 = '';
                $m2 = '';
                $y2 = '';
                $months = $data->month;
                $year = $data->yr;


                switch ($months) {
                    case '1':
                    case '2':
                    case '3':
                        $d1 = '01';
                        $m1 = '01';
                        $y1 = $year;

                        $d2 = '03';
                        $m2 = '31';
                        $y2 = $year;
                        break;

                    case '4':
                    case '5':
                    case '6':
                        $d1 = '04';
                        $m1 = '01';
                        $y1 = $year;

                        $d2 = '06';
                        $m2 = '30';
                        $y2 = $year;
                        break;

                    case '7':
                    case '8':
                    case '9':
                        $d1 = '07';
                        $m1 = '01';
                        $y1 = $year;

                        $d2 = '09';
                        $m2 = '30';
                        $y2 = $year;
                        break;

                    default:
                        $d1 = '10';
                        $m1 = '01';
                        $y1 = $year;

                        $d2 = '12';
                        $m2 = '31';
                        $y2 = $year;
                        break;
                }


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->addline();
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, '10', '', '', '');
                $str .= $this->reporter->col('1.', '20', null, false, $border, '', 'L', $font, '10', '', '', '');
                $str .= $this->reporter->col('For the Period', '170', null, false, $border, '', 'L', $font, '10', '', '', '');
                $str .= $this->reporter->col('From', '40', null, false, $border, '', 'L', $font, '10', 'B', '', '');


                $str .= $this->reporter->col("<div style='margin-top:10px;'>
            <input readonly type='text' style='width:40px;' value=$d1>
            <input readonly type='text' style='width:40px;' value=$m1>
            <input readonly type='text' style='width:40px;' value= $y1> (MM/DD/YY)</div>", '260', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '70', null, false, $border, '', 'L', $font, '10', '', '', '');
                $str .= $this->reporter->col('To', '20', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col("<div style='margin-top:10px;'>
            <input readonly type='text' style='width:40px;' value=$d2>
            <input readonly type='text' style='width:40px;' value=$m2>
            <input readonly type='text' style='width:40px;' value=$y2> (MM/DD/YY)</div>", '280', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '130', null, false, $border, 'R', 'L', $font, '10', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'L', $font, '9', '', '2px', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Part I - Employee Information', '1000', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'L', $font, '15', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();















                //  Simulan ulit table para sa bagong client
                $str .= $this->reporter->begintable($layoutsize);
                // $str .= $this->reporter->addline();
                $str .= $this->reporter->startrow();
                $tin = $data->tin;
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('2.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '250', null, false, $border, '', 'L', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $tin . "' >", '190', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '470', null, false, $border, 'R', 'L', $font, '9', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('3.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Payee\'s Name (Last Name, First Name, Middle Name for Individual OR Registered Name for Non-Individual)', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $payee = $data->clientname;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payee . "' >", '960', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // //ADDRESS
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('4.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Registered Address', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $addr = $data->address;
                $zipcode = $data->zipcode;
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                // $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $addr . "' >", '690', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col(
                    "<div style='width:100%; white-space:normal; word-wrap:break-word; font-weight:bold;'>" . $addr . "</div>",
                    '690',
                    null,
                    false,
                    $border,
                    'TBLR',
                    'L',
                    $font,
                    '10',
                    '',
                    '',
                    ''
                );

                $str .= $this->reporter->col('4A', '30', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Zip Code', '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $zipcode . "' >", '160', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // //foreign add
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('5.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Foreign Address , if applicable', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='' >", '960', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // //PART 2
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Part II - Payor Information', '1000', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // $payortin = $row['payortin'];
                // $payortin = $data->payortin;
                $payortin = '213-362-385-00000';

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('2.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '250', null, false, $border, '', 'L', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payortin . "' >", '190', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '470', null, false, $border, 'R', 'L', $font, '9', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('3.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Payor\'s Name  (Last Name, First Name, Middle Name for Individual OR Registered Name for Non-Individual)', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // $payorname = $row['payorcompname'];
                // $payorname = $data->payorcompname;
                $payorname = 'HOMEWORKS THE HOMECENTER INCORPORATED';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payorname . "' >", '960', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // // //ADDRESS
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('4.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Registered Address', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // $payoraddr = $data->payoraddress;
                $payoraddr = 'BASEMENT EVER GOTESCO COMMONWEALTH AVENUE CORNER DN MARIA MATANDANG BALARA, 1119 QUEZON CITY NCR, 2ND DISTRICT PHILS.';
                // $payorzipcode = $data->payorzipcode;
                $payorzipcode = '';
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
                // $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payoraddr . "' >", '690', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col(
                    "<div style='width:100%; white-space:normal; word-wrap:break-word; font-weight:bold;'>" . $payoraddr . "</div>",
                    '690',
                    null,
                    false,
                    $border,
                    'TBLR',
                    'L',
                    $font,
                    '10',
                    '',
                    '',
                    ''
                );

                $str .= $this->reporter->col('8A', '30', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('Zip Code', '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payorzipcode . "' >", '160', null, false, $border, '', 'L', $font, '10', 'B', '', '');
                $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                // //PART 3
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Part III - Details of Monthly Income Payments and Taxes Withheld', '1000', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '320', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS', '400', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Income Payments Subject to', '320', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('ATC', '80', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('1st Month of', '100', null, false, $border, 'LT', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('2nd Month of', '100', null, false, $border, 'LT', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('3rd Month of', '100', null, false, $border, 'LT', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('Total', '100', null, false, $border, 'LRT', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('Tax Withheld', '200', null, false, $border, 'R', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Expanded Witholding Tax', '320', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('the Quarter', '100', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('the Quarter', '100', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('the Quarter', '100', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('For The Quarter', '200', null, false, $border, 'R', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '320', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'LRB', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->col('', '200', null, false, $border, 'RB', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                // $ewtrate = isset($data->ewtrate) ? $data->ewtrate : 0;
                // $db = isset($data->db) ? $data->db : 0;
                // $cr = isset($data->cr) ? $data->cr : 0;
                // $isvewt = isset($data->isvewt) ? $data->isvewt : 0;
                // $ewtrateval = floatval($ewtrate) / 100;
                // $ewtdesc = isset($data->ewtdesc) ? $data->ewtdesc : '';
                // $month = isset($data->month) ? $data->month : 0;


                $totaltax = 0;
                $totalwtx1 = 0;
                $totalwtx2 = 0;
                $totalwtx3 = 0;
                $totalwtx = 0;

                if (isset($ewtdata[$lookupKey])) {
                    foreach ($ewtdata[$lookupKey] as $ewtRow) {

                        $ewtrate = isset($ewtRow['ewtrate']) && $ewtRow['ewtrate'] !== '' ? $ewtRow['ewtrate'] : 0;
                        $db = isset($ewtRow['db']) && $ewtRow['db'] !== '' ? $ewtRow['db'] : 0;
                        $cr = isset($ewtRow['cr']) && $ewtRow['cr'] !== '' ? $ewtRow['cr'] : 0;
                        $isvewt = isset($ewtRow['isvewt']) && $ewtRow['isvewt'] !== '' ? $ewtRow['isvewt'] : 0;
                        $ewtdesc = isset($ewtRow['ewtdesc']) && $ewtRow['ewtdesc'] !== '' ? $ewtRow['ewtdesc'] : '';
                        $month = isset($ewtRow['month']) && $ewtRow['month'] !== '' ? $ewtRow['month'] : 0;
                        $ewtcode = isset($ewtRow['ewtcode']) && $ewtRow['ewtcode'] !== '' ? $ewtRow['ewtcode'] : '';
                        $ewtrateval = floatval($ewtrate) / 100;

                        // var_dump($ewtrate);
                        // var_dump($ewtrateval);
                        if ($db == 0) {
                            //FOR CR
                            if ($cr < 0) {
                                $db = $cr;
                            } else {
                                $db = floatval($cr) * -1;
                            } //end if

                            if ($isvewt == 1) {
                                $db = $db / 1.12;
                            }

                            $ewtamt = $db * $ewtrateval;
                        } else {
                            //FOR DB
                            if ($db < 0) {
                                $db = floatval($db) * -1;
                            } else {
                                $db = $db;
                            } //end if

                            if ($isvewt == 1) {
                                $db = $db / 1.12;
                            }
                            $ewtamt = $db * $ewtrateval;
                        } //end if

                        $ewtamt2 = $ewtamt == 0 ? '-' : number_format($ewtamt, 2);
                        $dbhere = $db == 0 ? '-' : number_format($db, 2);

                        switch ($month) {
                            case '1':
                            case '4':
                            case '7':
                            case '10':
                                $str .= $this->reporter->begintable($layoutsize);
                                $str .= $this->reporter->startrow();
                                $str .= $this->reporter->col($ewtdesc, '320', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col($ewtcode, '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LT', 'R', $font, $font_size);
                                $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LRT', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col($ewtamt2, '200', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->endrow();
                                $str .= $this->reporter->endtable();
                                $totalwtx1 +=  $db;
                                $totalwtx += $db;
                                $totaltax += $ewtamt;
                                break;
                            case '2':
                            case '5':
                            case '8':
                            case '11':
                                $str .= $this->reporter->begintable($layoutsize);
                                $str .= $this->reporter->startrow();
                                $str .= $this->reporter->col($ewtdesc, '320', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col($ewtcode, '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LT', 'R', $font, $font_size);
                                $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LRT', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col($ewtamt2, '200', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->endrow();
                                $str .= $this->reporter->endtable();
                                $totalwtx2 +=  $db;
                                $totalwtx += $db;
                                $totaltax += $ewtamt;
                                break;

                            default:
                                $str .= $this->reporter->begintable($layoutsize);
                                $str .= $this->reporter->startrow();
                                $str .= $this->reporter->col($ewtdesc, '320', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col($ewtcode, '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                                $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, $font_size);
                                $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LT', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LRT', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->col($ewtamt2, '200', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');
                                $str .= $this->reporter->endrow();
                                $str .= $this->reporter->endtable();
                                $totalwtx3 +=  $db;
                                $totalwtx += $db;
                                $totaltax += $ewtamt;
                                break;
                        }
                    }


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total', '320', null, false, $border, 'TL', 'L', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), '100', null, false, $border, 'LT', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), '100', null, false, $border, 'LT', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), '100', null, false, $border, 'LT', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($totalwtx != 0 ? number_format($totalwtx, 2) : ''), '100', null, false, $border, 'LRT', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->col(($totaltax != 0 ? number_format($totaltax, 2) : ''), '200', null, false, $border, 'RT', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    //SPACE FOR TOTL
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('', '320', null, false, $border, 'L', 'L', $font, '6', '', '', '');
                    $str .= $this->reporter->col('', '80', null, false, $border, 'L', 'C', $font, '6', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'R', $font, '6', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'R', $font, '6', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'L', 'R', $font, '6', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LR', 'R', $font, '6', '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'C', $font, '6', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax  (Government & Private)', '320', null, false, $border, 'TL', 'L', $font, '', '', '', '');
                    $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LRT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '9', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    //1
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '320', null, false, $border, 'TL', 'L', $font, '', '', '', '');
                    $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LRT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '9', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    //2
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '320', null, false, $border, 'TL', 'L', $font, '', '', '', '');
                    $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LRT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '9', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();

                    //3
                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '320', null, false, $border, 'TL', 'L', $font, '', '', '', '');
                    $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LRT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '9', '', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();


                    $str .= $this->reporter->begintable($layoutsize);
                    $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col('Total', '320', null, false, $border, 'TL', 'L', $font, '', 'B', '', '');
                    $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col('', '100', null, false, $border, 'LRT', 'R', $font, '9', '', '', '');
                    $str .= $this->reporter->col(($totaltax != 0 ? number_format($totaltax, 2) : ''), '200', null, false, $border, 'RT', 'R', $font, $font_size, 'B', '', '');
                    $str .= $this->reporter->endrow();
                    $str .= $this->reporter->endtable();
                }



                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '5', null, false, $border, 'TL', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct,
                pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to the processing
                of our information as contemplated under the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.
                ', '990', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '5', null, false, $border, 'TR', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // $str .= $this->reporter->begintable($layoutsize);
                // $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '320', null, false, $border, 'TL', 'L', $font, '', '', '', '');
                // $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                // $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                // $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                // $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
                // $str .= $this->reporter->col('', '100', null, false, $border, 'LRT', 'R', $font, '9', '', '', '');
                // $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '9', '', '', '');
                // $str .= $this->reporter->endrow();
                // $str .= $this->reporter->endtable();



                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '55', null, false, $border, 'LT', 'L', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '260', null, false, $border, 'T', 'C', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'R', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '260', null, false, $border, 'T', 'R', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'R', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '260', null, false, $border, 'T', 'R', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '55', null, false, $border, 'TR', 'C', $font, '3', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                // $str .= '<div style="position: relative;margin:0px 0 10px 0;">';
                // $str .= "<div style='position:absolute; bottom:-15px'>";
                // $sign = URL::to('/images/homeworks/checked.png');
                // $str .= $this->reporter->begintable($layoutsize);
                // $str .= $this->reporter->startrow();
                // $str .= $this->reporter->col('', '55', null, false, $border, 'L', 'L', $font, '', '', '', '');
                // // $str .= $this->reporter->col('<img src ="' . $sign . '" alt="BIR" width="60px" height ="60px">', '260', null, false, $border, '', 'R', $font, '15', 'B', '', '');
                // $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="70px">', '260', null, false, '1px solid ', '', 'C', 'Century Gothic', '100', 'B', '', '');
                // $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, '9', '', '', '');
                // $str .= $this->reporter->col('', '260', null, false, $border, '', 'R', $font, '9', '', '', '');
                // $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, '9', '', '', '');
                // $str .= $this->reporter->col('', '260', null, false, $border, '', 'R', $font, '9', '', '', '');
                // $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'C', $font, '9', '', '', '');
                // $str .= $this->reporter->endrow();
                // $str .= $this->reporter->endtable();
                // $str .= "</div>";

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('', '55', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('MARIETTA Y. JOSE', '260', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('908-572-911-000', '260', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('AUDIT MANAGER', '260', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
                $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '55', null, false, $border, 'LB', 'L', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '260', null, false, $border, 'B', 'C', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '55', null, false, $border, 'B', 'R', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '260', null, false, $border, 'B', 'R', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '55', null, false, $border, 'B', 'R', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '260', null, false, $border, 'B', 'R', $font, '3', '', '', '');
                $str .= $this->reporter->col('', '55', null, false, $border, 'RB', 'C', $font, '3', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                // $str .= "</div>";

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Signature over Printed Name of Payor/Payor\'s Authorized Representative/Tax Agent', '1000', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('(Indicate Title/Designation and TIN)', '1000', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '1000', null, false, $border, 'LRT', 'C', $font, '3', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $sample = '';

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Tax Agent Accreditation No./', '250', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("Date of", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("Date of Expiry", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Attorney\'s Roll No. (if ', '250', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, 'TBR', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Issuance', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

                $str .= $this->reporter->col('(MM/DD/YYY)', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');


                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

                $str .= $this->reporter->col('', '20', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();



                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('applicable)', '250', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("(MM/DD/YYY)", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('CONFORME :', '1000', null, false, $border, 'LTR', 'C', $font, '9', 'B', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                //2
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("<p style='color:white;; margin:0px;'>.</p>", '320', null, false, $border, 'LT', 'L', $font, '', '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '9', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                //3
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("<p style='color:white; margin:0px;'>.</p>", '320', null, false, $border, 'BL', 'L', $font, '', '', '', '');
                $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
                $str .= $this->reporter->col('', '200', null, false, $border, 'BR', 'C', $font, '9', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Signature over Printed Name of Payee/Payee\'s Authorized Representative/Tax Agent', '1000', null, false, $border, 'LR', 'C', $font, '', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('(Indicate Title/Designation and TIN)', '1000', null, false, $border, 'LR', 'C', $font, '', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();



                ////////SECONDDDDDDDD
                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '1000', null, false, $border, 'LRT', 'C', $font, '3', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $sample = '';

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Tax Agent Accreditation No./', '250', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("Date of", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("Date of Expiry", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Attorney\'s Roll No. (if ', '250', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, 'TBR', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('Issuance', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

                $str .= $this->reporter->col('(MM/DD/YYY)', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');


                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
                $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

                $str .= $this->reporter->col('', '20', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();



                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('applicable)', '250', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("(MM/DD/YYY)", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
                $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                //////////////LASTTTTTTTTTTTT


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '1000', null, false, $border, 'T', 'C', $font, '3', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();


                $str .= $this->reporter->begintable($layoutsize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('*NOTE: The BIR Data Privacy is in the BIR website (www.bir.gov.ph)  ', '1000', null, false, $border, '', 'L', $font, '7', '', '', '');
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();
            }
        } //end foreach

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }


    public function reportDefaultLayouthere($config)
    {
        $result = $this->reportDefault($config);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $border = '1px solid';
        $font = 'verdana';
        $font_size = '10';
        $count = 15;
        $page = 15;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $ewtdata = [];
        foreach ($result as $array_index => $array) {
            // unique key per month + ewtcode
            $lookupKey = $array->month . '_' . $array->ewtcode;

            $ewtdata[$lookupKey][] = [
                'ewtrate' => $array->ewtrate,
                'ewtcode'   => $array->ewtcode,
                'db'      => $array->db,
                'cr'      => $array->cr,
                'isvewt'  => $array->isvewt,
                'ewtdesc' => $array->ewtdesc,
                'month'   => $array->month
            ];
        }

        $first = reset($result);


        $str = '';
        $layoutsize = '1000';
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '20px;margin-top:10px;margin-left:95px');
        $str .= $this->displayHeader($config);


        foreach ($result as $key => $data) {



            $d1 = '';
            $m1 = '';
            $y1 = '';

            $d2 = '';
            $m2 = '';
            $y2 = '';
            $months = $data->month;
            $year = $data->yr;


            switch ($months) {
                case '1':
                case '2':
                case '3':
                    $d1 = '01';
                    $m1 = '01';
                    $y1 = $year;

                    $d2 = '03';
                    $m2 = '31';
                    $y2 = $year;
                    break;

                case '4':
                case '5':
                case '6':
                    $d1 = '04';
                    $m1 = '01';
                    $y1 = $year;

                    $d2 = '06';
                    $m2 = '30';
                    $y2 = $year;
                    break;

                case '7':
                case '8':
                case '9':
                    $d1 = '07';
                    $m1 = '01';
                    $y1 = $year;

                    $d2 = '09';
                    $m2 = '30';
                    $y2 = $year;
                    break;

                default:
                    $d1 = '10';
                    $m1 = '01';
                    $y1 = $year;

                    $d2 = '12';
                    $m2 = '31';
                    $y2 = $year;
                    break;
            }


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col('1.', '20', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col('For the Period', '170', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col('From', '40', null, false, $border, '', 'L', $font, '10', 'B', '', '');


            $str .= $this->reporter->col("<div style='margin-top:10px;'>
            <input readonly type='text' style='width:40px;' value=$d1>
            <input readonly type='text' style='width:40px;' value=$m1>
            <input readonly type='text' style='width:40px;' value= $y1> (MM/DD/YY)</div>", '260', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '70', null, false, $border, '', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->col('To', '20', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col("<div style='margin-top:10px;'>
            <input readonly type='text' style='width:40px;' value=$d2>
            <input readonly type='text' style='width:40px;' value=$m2>
            <input readonly type='text' style='width:40px;' value=$y2> (MM/DD/YY)</div>", '280', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '130', null, false, $border, 'R', 'L', $font, '10', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'L', $font, '9', '', '2px', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Part I - Employee Information', '1000', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'L', $font, '15', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();






            //  Simulan ulit table para sa bagong client
            $str .= $this->reporter->begintable($layoutsize);
            // $str .= $this->reporter->addline();
            $str .= $this->reporter->startrow();
            $tin = $data->tin;
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('2.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '250', null, false, $border, '', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $tin . "' >", '190', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '470', null, false, $border, 'R', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('3.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Payee\'s Name (Last Name, First Name, Middle Name for Individual OR Registered Name for Non-Individual)', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $payee = $data->clientname;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payee . "' >", '960', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // //ADDRESS
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('4.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Registered Address', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $addr = $data->address;
            $zipcode = $data->zipcode;
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $addr . "' >", '690', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col(
                "<div style='width:100%; white-space:normal; word-wrap:break-word; font-weight:bold;'>" . $addr . "</div>",
                '690',
                null,
                false,
                $border,
                'TBLR',
                'L',
                $font,
                '10',
                '',
                '',
                ''
            );

            $str .= $this->reporter->col('4A', '30', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Zip Code', '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $zipcode . "' >", '160', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // //foreign add
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('5.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Foreign Address , if applicable', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='' >", '960', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // //PART 2
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Part II - Payor Information', '1000', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // $payortin = $row['payortin'];
            // $payortin = $data->payortin;
            $payortin = '213-362-385-00000';

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('2.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '250', null, false, $border, '', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payortin . "' >", '190', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '470', null, false, $border, 'R', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('3.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Payor\'s Name  (Last Name, First Name, Middle Name for Individual OR Registered Name for Non-Individual)', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // $payorname = $row['payorcompname'];
            // $payorname = $data->payorcompname;
            $payorname = 'HOMEWORKS THE HOMECENTER INCORPORATED';
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payorname . "' >", '960', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // // //ADDRESS
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('4.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Registered Address', '970', null, false, $border, 'R', 'L', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // $payoraddr = $data->payoraddress;
            $payoraddr = 'BASEMENT EVER GOTESCO COMMONWEALTH AVENUE CORNER DN MARIA MATANDANG BALARA, 1119 QUEZON CITY NCR, 2ND DISTRICT PHILS.';
            // $payorzipcode = $data->payorzipcode;
            $payorzipcode = '';
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payoraddr . "' >", '690', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col(
                "<div style='width:100%; white-space:normal; word-wrap:break-word; font-weight:bold;'>" . $payoraddr . "</div>",
                '690',
                null,
                false,
                $border,
                'TBLR',
                'L',
                $font,
                '10',
                '',
                '',
                ''
            );

            $str .= $this->reporter->col('8A', '30', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('Zip Code', '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payorzipcode . "' >", '160', null, false, $border, '', 'L', $font, '10', 'B', '', '');
            $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            // //PART 3
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Part III - Details of Monthly Income Payments and Taxes Withheld', '1000', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS', '400', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '200', null, false, $border, 'R', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Income Payments Subject to', '320', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('ATC', '80', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('1st Month of', '100', null, false, $border, 'LT', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('2nd Month of', '100', null, false, $border, 'LT', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('3rd Month of', '100', null, false, $border, 'LT', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('Total', '100', null, false, $border, 'LRT', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('Tax Withheld', '200', null, false, $border, 'R', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Expanded Witholding Tax', '320', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('the Quarter', '100', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('the Quarter', '100', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('the Quarter', '100', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('For The Quarter', '200', null, false, $border, 'R', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '320', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'LRB', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->col('', '200', null, false, $border, 'RB', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();





            $totaltax = 0;
            $totalwtx1 = 0;
            $totalwtx2 = 0;
            $totalwtx3 = 0;
            $totalwtx = 0;


            foreach ($ewtdata as $lookupKey => $rows) {
                foreach ($rows as $ewtRow) {
                    $ewtrate = isset($ewtRow['ewtrate']) && $ewtRow['ewtrate'] !== '' ? $ewtRow['ewtrate'] : 0;
                    $db = isset($ewtRow['db']) && $ewtRow['db'] !== '' ? $ewtRow['db'] : 0;
                    $cr = isset($ewtRow['cr']) && $ewtRow['cr'] !== '' ? $ewtRow['cr'] : 0;
                    $isvewt = isset($ewtRow['isvewt']) && $ewtRow['isvewt'] !== '' ? $ewtRow['isvewt'] : 0;
                    $ewtdesc = isset($ewtRow['ewtdesc']) && $ewtRow['ewtdesc'] !== '' ? $ewtRow['ewtdesc'] : '';
                    $month = isset($ewtRow['month']) && $ewtRow['month'] !== '' ? $ewtRow['month'] : 0;
                    $ewtcode = isset($ewtRow['ewtcode']) && $ewtRow['ewtcode'] !== '' ? $ewtRow['ewtcode'] : '';
                    $ewtrateval = floatval($ewtrate) / 100;

                    if ($db == 0) {
                        //FOR CR
                        if ($cr < 0) {
                            $db = $cr;
                        } else {
                            $db = floatval($cr) * -1;
                        } //end if

                        if ($isvewt == 1) {
                            $db = $db / 1.12;
                        }

                        $ewtamt = $db * $ewtrateval;
                    } else {
                        //FOR DB
                        if ($db < 0) {
                            $db = floatval($db) * -1;
                        } else {
                            $db = $db;
                        } //end if

                        if ($isvewt == 1) {
                            $db = $db / 1.12;
                        }
                        $ewtamt = $db * $ewtrateval;
                    } //end if

                    $ewtamt2 = $ewtamt == 0 ? '-' : number_format($ewtamt, 2);
                    $dbhere = $db == 0 ? '-' : number_format($db, 2);

                    switch ($month) {
                        case '1':
                        case '4':
                        case '7':
                        case '10':
                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col($ewtdesc, '320', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col($ewtcode, '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LT', 'R', $font, $font_size);
                            $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                            $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                            $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LRT', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col($ewtamt2, '200', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                            $totalwtx1 +=  $db;
                            $totalwtx += $db;
                            $totaltax += $ewtamt;
                            break;
                        case '2':
                        case '5':
                        case '8':
                        case '11':
                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col($ewtdesc, '320', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col($ewtcode, '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                            $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LT', 'R', $font, $font_size);
                            $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                            $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LRT', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col($ewtamt2, '200', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                            $totalwtx2 +=  $db;
                            $totalwtx += $db;
                            $totaltax += $ewtamt;
                            break;

                        default:
                            $str .= $this->reporter->begintable($layoutsize);
                            $str .= $this->reporter->startrow();
                            $str .= $this->reporter->col($ewtdesc, '320', null, false, $border, 'LT', 'L', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col($ewtcode, '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'C', $font, $font_size, 'B', '', '');
                            $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, $font_size);
                            $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LT', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col($dbhere, '100', null, false, $border, 'LRT', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->col($ewtamt2, '200', null, false, $border, 'RT', 'R', $font, $font_size, '', '', '');
                            $str .= $this->reporter->endrow();
                            $str .= $this->reporter->endtable();
                            $totalwtx3 +=  $db;
                            $totalwtx += $db;
                            $totaltax += $ewtamt;
                            break;
                    }
                }
            }





            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '5', null, false, $border, 'TL', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct,
                pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to the processing
                of our information as contemplated under the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.
                ', '990', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '5', null, false, $border, 'TR', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // $str .= $this->reporter->begintable($layoutsize);
            // $str .= $this->reporter->startrow();
            // $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '320', null, false, $border, 'TL', 'L', $font, '', '', '', '');
            // $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
            // $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col('', '100', null, false, $border, 'LRT', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '9', '', '', '');
            // $str .= $this->reporter->endrow();
            // $str .= $this->reporter->endtable();



            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '55', null, false, $border, 'LT', 'L', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '260', null, false, $border, 'T', 'C', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'R', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '260', null, false, $border, 'T', 'R', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'T', 'R', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '260', null, false, $border, 'T', 'R', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'TR', 'C', $font, '3', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            // $str .= '<div style="position: relative;margin:0px 0 10px 0;">';
            // $str .= "<div style='position:absolute; bottom:-15px'>";
            // $sign = URL::to('/images/homeworks/checked.png');
            // $str .= $this->reporter->begintable($layoutsize);
            // $str .= $this->reporter->startrow();
            // $str .= $this->reporter->col('', '55', null, false, $border, 'L', 'L', $font, '', '', '', '');
            // // $str .= $this->reporter->col('<img src ="' . $sign . '" alt="BIR" width="60px" height ="60px">', '260', null, false, $border, '', 'R', $font, '15', 'B', '', '');
            // $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="70px">', '260', null, false, '1px solid ', '', 'C', 'Century Gothic', '100', 'B', '', '');
            // $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col('', '260', null, false, $border, '', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col('', '260', null, false, $border, '', 'R', $font, '9', '', '', '');
            // $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'C', $font, '9', '', '', '');
            // $str .= $this->reporter->endrow();
            // $str .= $this->reporter->endtable();
            // $str .= "</div>";

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '55', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('MARIETTA Y. JOSE', '260', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('908-572-911-000', '260', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('AUDIT MANAGER', '260', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '55', null, false, $border, 'LB', 'L', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '260', null, false, $border, 'B', 'C', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'B', 'R', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '260', null, false, $border, 'B', 'R', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'B', 'R', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '260', null, false, $border, 'B', 'R', $font, '3', '', '', '');
            $str .= $this->reporter->col('', '55', null, false, $border, 'RB', 'C', $font, '3', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            // $str .= "</div>";

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Signature over Printed Name of Payor/Payor\'s Authorized Representative/Tax Agent', '1000', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('(Indicate Title/Designation and TIN)', '1000', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '1000', null, false, $border, 'LRT', 'C', $font, '3', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $sample = '';

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Tax Agent Accreditation No./', '250', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("Date of", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("Date of Expiry", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Attorney\'s Roll No. (if ', '250', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, 'TBR', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Issuance', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

            $str .= $this->reporter->col('(MM/DD/YYY)', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');


            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

            $str .= $this->reporter->col('', '20', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();



            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('applicable)', '250', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("(MM/DD/YYY)", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('CONFORME :', '1000', null, false, $border, 'LTR', 'C', $font, '9', 'B', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            //2
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("<p style='color:white;; margin:0px;'>.</p>", '320', null, false, $border, 'LT', 'L', $font, '', '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //3
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("<p style='color:white; margin:0px;'>.</p>", '320', null, false, $border, 'BL', 'L', $font, '', '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
            $str .= $this->reporter->col('', '200', null, false, $border, 'BR', 'C', $font, '9', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Signature over Printed Name of Payee/Payee\'s Authorized Representative/Tax Agent', '1000', null, false, $border, 'LR', 'C', $font, '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('(Indicate Title/Designation and TIN)', '1000', null, false, $border, 'LR', 'C', $font, '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();



            ////////SECONDDDDDDDD
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '1000', null, false, $border, 'LRT', 'C', $font, '3', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $sample = '';

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Tax Agent Accreditation No./', '250', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("Date of", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("Date of Expiry", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Attorney\'s Roll No. (if ', '250', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, 'TBR', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Issuance', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

            $str .= $this->reporter->col('(MM/DD/YYY)', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');


            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
            $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

            $str .= $this->reporter->col('', '20', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();



            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('applicable)', '250', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("(MM/DD/YYY)", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //////////////LASTTTTTTTTTTTT


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '1000', null, false, $border, 'T', 'C', $font, '3', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('*NOTE: The BIR Data Privacy is in the BIR website (www.bir.gov.ph)  ', '1000', null, false, $border, '', 'L', $font, '7', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        } //end foreach

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $result = $this->reportDefault($config);
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $border = '1px solid';
        $font = 'verdana';
        $font_size = '10';
        $count = 15;
        $page = 15;
        $this->reporter->linecounter = 0;

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $ewtdata = [];
        foreach ($result as $array_index => $array) {
            // unique key per month + ewtcode
            // $lookupKey = $array->month . '_' . $array->ewtcode;
            $lookupKey = $array->ewtcode;

            $ewtdata[$lookupKey][] = [
                'ewtrate' => $array->ewtrate,
                'ewtcode'   => $array->ewtcode,
                'db'      => $array->db,
                'cr'      => $array->cr,
                'isvewt'  => $array->isvewt,
                'ewtdesc' => $array->ewtdesc,
                'month'   => $array->month
            ];
        }

        // var_dump($ewtdata);


        $first = reset($result);
        $d1 = '';
        $m1 = '';
        $y1 = '';

        $d2 = '';
        $m2 = '';
        $y2 = '';
        $months = $first->month;   // gamitin lang ang month ng first row
        $year   = $first->yr;
        $tin     = $first->tin;
        $payee   = $first->clientname;
        $addr = $first->address;
        $zipcode = $first->zipcode;

        switch ($months) {
            case '1':
            case '2':
            case '3':
                $d1 = '01';
                $m1 = '01';
                $y1 = $year;

                $d2 = '03';
                $m2 = '31';
                $y2 = $year;
                break;

            case '4':
            case '5':
            case '6':
                $d1 = '04';
                $m1 = '01';
                $y1 = $year;

                $d2 = '06';
                $m2 = '30';
                $y2 = $year;
                break;

            case '7':
            case '8':
            case '9':
                $d1 = '07';
                $m1 = '01';
                $y1 = $year;

                $d2 = '09';
                $m2 = '30';
                $y2 = $year;
                break;

            default:
                $d1 = '10';
                $m1 = '01';
                $y1 = $year;

                $d2 = '12';
                $m2 = '31';
                $y2 = $year;
                break;
        }

        $str = '';
        $layoutsize = '960';
        // $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '20px;margin-top:10px;margin-left:95px');
        $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:20px');
        $str .= $this->displayHeader($config);

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('1.', '20', null, false, $border, '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('For the Period', '170', null, false, $border, '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('From', '40', null, false, $border, '', 'L', $font, '10', 'B', '', '');


        $str .= $this->reporter->col("<div style='margin-top:10px;'>
            <input readonly type='text' style='width:40px;border:1px solid black;' value=$d1>
            <input readonly type='text' style='width:40px;border:1px solid black;' value=$m1>
            <input readonly type='text' style='width:40px;border:1px solid black;' value= $y1> (MM/DD/YY)</div>", '260', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '70', null, false, $border, '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('To', '20', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col("<div style='margin-top:10px;'>
            <input readonly type='text' style='width:40px;border:1px solid black;' value=$d2>
            <input readonly type='text' style='width:40px;border:1px solid black;' value=$m2>
            <input readonly type='text' style='width:40px;border:1px solid black;' value=$y2> (MM/DD/YY)</div>", '260', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '110', null, false, $border, 'R', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '960', null, false, $border, 'LR', 'L', $font, '9', '', '2px', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Part I - Employee Information', '960', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '960', null, false, $border, 'LR', 'L', $font, '15', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();




        $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $tin = $tin;
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('2.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '250', null, false, $border, '', 'L', $font, '9', '', '', '');
        // $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $tin . "' >", '190', null, false, $border, '', 'L', $font, '10', 'B', '', '');

        $str .= $this->reporter->col("<input readonly type='text' style='width:100%; padding-left:10px; border:1px solid black;' value='" . $tin . "' >", '670', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'L', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('3.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Payee\'s Name (Last Name, First Name, Middle Name for Individual OR Registered Name for Non-Individual)', '930', null, false, $border, 'R', 'L', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $payee = $payee;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;border:1px solid black;padding-left:10px' value='" . $payee . "' >", '920', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // //ADDRESS
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('4.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Registered Address', '930', null, false, $border, 'R', 'L', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $addr = $addr;
        $zipcode = $zipcode;
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $addr . "' >", '690', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(
            "<div style='width:100%; white-space:normal; word-wrap:break-word; font-weight:bold;padding-left:10px'>" . $addr . "</div>",
            '650',
            null,
            false,
            $border,
            'TBLR',
            'L',
            $font,
            '10',
            '',
            '',
            ''
        );

        $str .= $this->reporter->col('4A', '30', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Zip Code', '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;border:1px solid black;padding-left:10px' value='" . $zipcode . "' >", '160', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // //foreign add
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('5.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Foreign Address , if applicable', '930', null, false, $border, 'R', 'L', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;border:1px solid black;padding-left:10px' value='' >", '920', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '960', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // //PART 2
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Part II - Payor Information', '960', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $payortin = $row['payortin'];
        // $payortin = $data->payortin;
        $payortin = '213-362-385-00000';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('6.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '250', null, false, $border, '', 'L', $font, '9', '', '', '');
        // $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;border:1px solid black;padding-left:10px' value='" . $payortin . "' >", '670', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'L', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('7.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Payor\'s Name  (Last Name, First Name, Middle Name for Individual OR Registered Name for Non-Individual)', '930', null, false, $border, 'R', 'L', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $payorname = $row['payorcompname'];
        // $payorname = $data->payorcompname;
        $payorname = 'HOMEWORKS THE HOMECENTER INCORPORATED';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;border:1px solid black;padding-left:10px' value='" . $payorname . "' >", '920', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // // //ADDRESS
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('8.', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Registered Address', '930', null, false, $border, 'R', 'L', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $payoraddr = $data->payoraddress;
        $payoraddr = 'BASEMENT EVER GOTESCO COMMONWEALTH AVENUE CORNER DN MARIA MATANDANG BALARA, 1119 QUEZON CITY NCR, 2ND DISTRICT PHILS.';
        // $payorzipcode = $data->payorzipcode;
        $payorzipcode = '';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        // $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;' value='" . $payoraddr . "' >", '690', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col(
            "<div style='width:100%; white-space:normal; word-wrap:break-word; font-weight:bold;padding-left:10px'>" . $payoraddr . "</div>",
            '650',
            null,
            false,
            $border,
            'TBLR',
            'L',
            $font,
            '10',
            '',
            '',
            ''
        );

        $str .= $this->reporter->col('8A', '30', null, false, $border, '', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Zip Code', '80', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("<input readonly type='text' style='width: 100%;border:1px solid black;padding-left:10px' value='" . $payorzipcode . "' >", '160', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('', '10', null, false, $border, 'R', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // //PART 3
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '960', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Part III - Details of Monthly Income Payments and Taxes Withheld', '960', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '320', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS', '400', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, 'R', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Income Payments Subject to', '320', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('ATC', '80', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('1st Month of', '100', null, false, $border, 'LT', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('2nd Month of', '100', null, false, $border, 'LT', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('3rd Month of', '100', null, false, $border, 'LT', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('Total', '100', null, false, $border, 'LRT', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('Tax Withheld', '160', null, false, $border, 'R', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Expanded Witholding Tax', '320', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('the Quarter', '100', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('the Quarter', '100', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('the Quarter', '100', null, false, $border, 'L', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LR', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('For The Quarter', '160', null, false, $border, 'R', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '320', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LB', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LRB', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, 'RB', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $totaltax = 0;
        $totaltaxx = 0;
        $tlm1 = 0;
        $tlm2 = 0;
        $tlm3 = 0;
        $tl = 0;
        foreach ($ewtdata as $lookupKey => $rows) {
            $desc = $rows[0]['ewtdesc'];
            $code = $rows[0]['ewtcode'];

            // default values
            $m1 = $m2 = $m3 = 0;
            $tax1 = $tax2 = $tax3 = 0;

            foreach ($rows as $ewtRow) {
                $ewtrateval = floatval($ewtRow['ewtrate']) / 100;


                // if ($db == 0) {
                //             //FOR CR
                //             if ($cr < 0) {
                //                 $db = $cr;
                //             } else {
                //                 $db = floatval($cr) * -1;
                //             } //end if

                //             if ($isvewt == 1) {
                //                 $db = $db / 1.12;
                //             }

                //             $ewtamt = $db * $ewtrateval;
                //         } else {
                //             //FOR DB
                //             if ($db < 0) {
                //                 $db = floatval($db) * -1;
                //             } else {
                //                 $db = $db;
                //             } //end if

                //             if ($isvewt == 1) {
                //                 $db = $db / 1.12;
                //             }
                //             $ewtamt = $db * $ewtrateval;
                //         } //end if

                //         $ewtamt2 = $ewtamt == 0 ? '-' : number_format($ewtamt, 2);
                //         $dbhere = $db == 0 ? '-' : number_format($db, 2);
                //     }

                if ((float)$ewtRow['db'] == 0) {
                    // CR case
                    $cr = (float)$ewtRow['cr'];
                    $db = ($cr < 0) ? $cr : $cr * -1;
                } else {
                    // DB case
                    $db = (float)$ewtRow['db'];
                    $db = ($db < 0) ? $db * -1 : $db;
                }

                if ($ewtRow['isvewt'] == 1) {
                    $db = $db / 1.12;
                }

                $ewtamt = $db * $ewtrateval;

                switch ($ewtRow['month']) {
                    case 1:
                    case 4:
                    case 7:
                    case 10:
                        $m1 += $db;
                        $tax1 += $ewtamt;
                        break;
                    case 2:
                    case 5:
                    case 8:
                    case 11:
                        $m2 += $db;
                        $tax2 += $ewtamt;
                        break;
                    default:
                        $m3 += $db;
                        $tax3 += $ewtamt;
                        break;
                }
            }

            $total = $m1 + $m2 + $m3;
            $totaltax = $tax1 + $tax2 + $tax3;

            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '10', null, false, $border, 'LT', 'L', $font, $font_size);
            $str .= $this->reporter->col($desc, '310', null, false, $border, 'T', 'L', $font, $font_size);
            $str .= $this->reporter->col($code, '80', null, false, $border, 'LT', 'C', $font, $font_size);
            // $str .= $this->reporter->col($m1 ? number_format($m1, 2) : ' ', '100', null, false, $border, 'LT', 'R', $font, $font_size);
            // $str .= $this->reporter->col($m2 ? number_format($m2, 2) : ' ', '100', null, false, $border, 'LT', 'R', $font, $font_size);
            // $str .= $this->reporter->col($m3 ? number_format($m3, 2) : '', '100', null, false, $border, 'LT', 'R', $font, $font_size);
            $str .= $this->reporter->col($m1 != 0 ? number_format($m1, 2) : '', '100', null, false, $border, 'LT', 'R', $font, $font_size);
            $str .= $this->reporter->col($m2 != 0 ? number_format($m2, 2) : '', '100', null, false, $border, 'LT', 'R', $font, $font_size);
            $str .= $this->reporter->col($m3 != 0 ? number_format($m3, 2) : '', '100', null, false, $border, 'LT', 'R', $font, $font_size);
            $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, 'LRT', 'R', $font, $font_size);
            $str .= $this->reporter->col(number_format($totaltax, 2), '160', null, false, $border, 'RT', 'R', $font, $font_size);
            $tlm1 += $m1;
            $tlm2 += $m2;
            $tlm3 += $m3;
            $totaltaxx += $totaltax;
            $tl += $total;
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'TL', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Total', '310', null, false, $border, 'T', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(($tlm1 != 0 ? number_format($tlm1, 2) : ''), '100', null, false, $border, 'LT', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($tlm2 != 0 ? number_format($tlm2, 2) : ''), '100', null, false, $border, 'LT', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($tlm3 != 0 ? number_format($tlm3, 2) : ''), '100', null, false, $border, 'LT', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($tl != 0 ? number_format($tl, 2) : ''), '100', null, false, $border, 'LRT', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col(($totaltaxx != 0 ? number_format($totaltaxx, 2) : ''), '160', null, false, $border, 'RT', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();




        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'TL', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax  (Government & Private)', '310', null, false, $border, 'T', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LRT', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, 'RT', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        //1 space
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '320', null, false, $border, 'TL', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'LT', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LT', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LRT', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, 'RT', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'TLB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('Total', '310', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'LTB', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LTB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LTB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LTB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'LRTB', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col(($totaltaxx != 0 ? number_format($totaltaxx, 2) : ''), '160', null, false, $border, 'RTB', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->bottom($config);

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }



    private function displayHeader($config)
    {
        //bir2307
        $birlogo = URL::to('/images/afti/birlogo.png');
        $logo2 = URL::to('/images/afti/bir2307.png');
        $barcode = URL::to('/images/afti/birbarcode.png');
        $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

        $stday = date_format(date_create($start), 'd');
        $stmonth = date_format(date_create($start), 'm');
        $styear = date_format(date_create($start), 'Y');


        $endday = date_format(date_create($end), 'd');
        $endmonth = date_format(date_create($end), 'm');
        $endyear = date_format(date_create($end), 'Y');

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '960';
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->letterhead($center, $username, $config);
        // $str .= $this->reporter->endtable();
        $str .= '<br/>';


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col("<p style='font-size:5px; color:white; font-weight:bold; margin:0px;'>.</p>  
          <small style='display:block; font-size:12px; font-weight:bold; color:black; margin-left:10px; margin-top:25px;'>
          For BIR&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;BCS<br>Use Only&nbsp;&nbsp;Item:</small>", '60', null, false, $border, '', 'L', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<img src ="' . $birlogo . '" alt="BIR" width="70px" height ="70px">', '10', null, false, $border, '', 'R', $font, '15', 'B', '', '');
        $str .= $this->reporter->col('Republic of the Philippines<br />Department of Finance<br />Bureau of Internal Revenue', '60', null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, '10', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('<img src ="' . $logo2 . '" alt="bir2307" width="200px" height ="57px" style="margin-left: 10px;">', '130', null, false, $border, 'LRT', 'L', 'Century Gothic', '15', 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('Certificate of Credible Tax Withheld at Source', '320', null, false, $border, 'T', 'C', $font, '16', 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, '10', '', '', '');
        $str .= $this->reporter->col('<img src ="' . $barcode . '" alt="barcode" width="200px" height ="57px" style="margin-left: 4px; margin-top:4px;">', '210', null, false, $border, 'LRT', 'L', 'Century Gothic', '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '10', null, false, $border, 'LTB', 'L', $font, '9', 'B', '', '');
        $str .= $this->reporter->col('Fill in all applicable spaces. Mark all appropriate boxes with an "X"', '960', null, false, $border, 'RTB', 'L', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //2ND ROW

        // $year = '2025';
        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '10', null, false, $border, 'L', 'L', $font, '10', '', '', '');
        // $str .= $this->reporter->col('1.', '20', null, false, $border, '', 'L', $font, '10', '', '', '');
        // $str .= $this->reporter->col('For the Period', '170', null, false, $border, '', 'L', $font, '10', '', '', '');
        // $str .= $this->reporter->col('From', '40', null, false, $border, '', 'L', $font, '10', 'B', '', '');


        // $str .= $this->reporter->col("<div style='margin-top:10px;'>
        //     <input readonly type='text' style='width:40px;' value=$stmonth>
        //     <input readonly type='text' style='width:40px;' value=$stday>
        //     <input readonly type='text' style='width:40px;' value= $styear> (MM/DD/YY)</div>", '260', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        // $str .= $this->reporter->col('', '70', null, false, $border, '', 'L', $font, '10', '', '', '');
        // $str .= $this->reporter->col('To', '20', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        // $str .= $this->reporter->col("<div style='margin-top:10px;'>
        //     <input readonly type='text' style='width:40px;' value=$endmonth>
        //     <input readonly type='text' style='width:40px;' value=$endday>
        //     <input readonly type='text' style='width:40px;' value=$endyear> (MM/DD/YY)</div>", '280', null, false, $border, '', 'L', $font, '10', 'B', '', '');
        // $str .= $this->reporter->col('', '130', null, false, $border, 'R', 'L', $font, '10', '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();


        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'L', $font, '9', '', '2px', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('Part I - Employee Information', '1000', null, false, $border, 'LTBR', 'C', $font, '9', 'B', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col('', '1000', null, false, $border, 'LR', 'L', $font, '15', '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        return $str;
    }


    private function bottom($config)
    {

        $border = '1px solid';
        $font = $this->companysetup->getrptfont($config['params']);
        $font_size = '10';

        $center = $config['params']['center'];
        $username = $config['params']['user'];

        $str = '';
        $layoutsize = '960';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '5', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct,
                pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent to the processing
                of our information as contemplated under the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful purposes.
                ', '955', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '5', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '320', null, false, $border, 'TL', 'L', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '3', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '55', null, false, $border, 'L', 'L', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '245', null, false, $border, '', 'C', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'R', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '245', null, false, $border, '', 'R', $font, '3', '', '', '');
        $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'C', $font, '3', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<div style="position: relative;margin:0px 0 10px 0;">';
        $str .= "<div style='position:absolute; bottom:320px'>";
        $sign = URL::to('/images/homeworks/checked.png');
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '55', null, false, $border, 'L', 'L', $font, '', '', '', '');
        // $str .= $this->reporter->col('<img src ="' . $sign . '" alt="BIR" width="60px" height ="60px">', '260', null, false, $border, '', 'R', $font, '15', 'B', '', '');
        // $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="70px">', '260', null, false, '1px solid ', '', 'C', 'Century Gothic', '100', 'B', '', '');
        $str .= $this->reporter->col('<img src ="' . $sign . '" width="100px" height ="70px">', '245', null, false, '1px solid ', '', 'C', 'Century Gothic', '5', 'B', '', '1px');
        $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '250', null, false, $border, '', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '55', null, false, $border, '', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '245', null, false, $border, '', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '55', null, false, $border, 'R', 'C', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= "</div>";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '55', null, false, $border, 'LB', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('MARIETTA Y. JOSE', '245', null, false, $border, 'B', 'C', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '55', null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('908-572-911-000', '250', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '55', null, false, $border, 'B', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('AUDIT MANAGER', '245', null, false, $border, 'B', 'R', $font, $font_size, 'B', '', '');
        $str .= $this->reporter->col('', '55', null, false, $border, 'BR', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        // $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '55', null, false, $border, 'LB', 'L', $font, '3', '', '', '');
        // $str .= $this->reporter->col('', '245', null, false, $border, 'B', 'C', $font, '3', '', '', '');
        // $str .= $this->reporter->col('', '55', null, false, $border, 'B', 'R', $font, '3', '', '', '');
        // $str .= $this->reporter->col('', '250', null, false, $border, 'B', 'R', $font, '3', '', '', '');
        // $str .= $this->reporter->col('', '55', null, false, $border, 'B', 'R', $font, '3', '', '', '');
        // $str .= $this->reporter->col('', '245', null, false, $border, 'B', 'R', $font, '3', '', '', '');
        // $str .= $this->reporter->col('', '55', null, false, $border, 'RB', 'C', $font, '3', '', '', '');
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();


        // $str .= "</div>";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Signature over Printed Name of Payor/Payor\'s Authorized Representative/Tax Agent', '960', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('(Indicate Title/Designation and TIN)', '960', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '960', null, false, $border, 'LRT', 'C', $font, '3', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $sample = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Tax Agent Accreditation No./', '210', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("Date of", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("Date of Expiry", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Attorney\'s Roll No. (if ', '210', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TBR', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Issuance', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

        $str .= $this->reporter->col('(MM/DD/YYY)', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');


        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

        $str .= $this->reporter->col('', '20', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('applicable)', '210', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("(MM/DD/YYY)", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CONFORME :', '960', null, false, $border, 'LTR', 'C', $font, '9', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        //2
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("<p style='color:white;; margin:0px;'>.</p>", '280', null, false, $border, 'LT', 'L', $font, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'RT', 'C', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //3
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("<p style='color:white; margin:0px;'>.</p>", '280', null, false, $border, 'BL', 'L', $font, '', '', '', '');
        $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'R', $font, '9', '', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'BR', 'C', $font, '9', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Signature over Printed Name of Payee/Payee\'s Authorized Representative/Tax Agent', '960', null, false, $border, 'LR', 'C', $font, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('(Indicate Title/Designation and TIN)', '960', null, false, $border, 'LR', 'C', $font, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        ////////SECONDDDDDDDD
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '960', null, false, $border, 'LRT', 'C', $font, '3', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $sample = '';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Tax Agent Accreditation No./', '210', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("Date of", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("Date of Expiry", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Attorney\'s Roll No. (if ', '210', null, false, $border, 'LR', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'TBR', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('Issuance', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

        $str .= $this->reporter->col('(MM/DD/YYY)', '100', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');


        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '23', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');
        $str .= $this->reporter->col('', '24', null, false, $border, 'RTB', 'C', $font, '12', '', '', '');

        $str .= $this->reporter->col('', '20', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('applicable)', '210', null, false, $border, 'L', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("(MM/DD/YYY)", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '190', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '100', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col("", '210', null, false, $border, 'R', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //////////////LASTTTTTTTTTTTT


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("<p style='color:white; font-weight:bold; margin:0px;'>.</p>", '960', null, false, $border, 'T', 'C', $font, '3', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('*NOTE: The BIR Data Privacy is in the BIR website (www.bir.gov.ph)  ', '960', null, false, $border, '', 'L', $font, '7', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // } //end foreach



        return $str;
    }
}
