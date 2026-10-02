<?php

namespace App\Http\Classes\modules\modulereport\hahsy;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class so
{

    private $modulename = "Sales Order";
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
        $this->reporter = new SBCPDF;
    }

    public function createreportfilter()
    {
    
        $fields = ['prepared', 'approved', 'checked', 'radioreporttype', 'radiolayoutformat', 'radioitemsort', 'radiosorting', 'print'];
        $col1 = $this->fieldClass->create($fields);

        data_set($col1, 'prepared.label', 'Prepared By');
        data_set($col1, 'approved.label', 'Approved By');
        data_set($col1, 'checked.label', 'Verified By');

        data_set($col1, 'radioreporttype.label', 'Option');
        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Show Other Detail', 'value' => '0', 'color' => 'red'],
            ['label' => 'Hide Other Detail', 'value' => '1', 'color' => 'red'],
        ]);

        data_set($col1, 'radiolayoutformat.label', 'Layout');
        data_set($col1, 'radiolayoutformat.options', [
            ['label' => 'Default Layout', 'value' => '0', 'color' => 'red'],
            ['label' => 'Pending Order', 'value' => '1', 'color' => 'red'],
        ]);

        data_set($col1, 'radioitemsort.label', 'Format');
        data_set($col1, 'radioitemsort.options', [
            ['label' => 'With Price', 'value' => '0', 'color' => 'red'],
            ['label' => 'W/out Price', 'value' => '1', 'color' => 'red'],
        ]);

        data_set($col1, 'radiosorting.label', 'Sort By');
        data_set($col1, 'radiosorting.options', [
            ['label' => 'Warehouse Code', 'value' => '0', 'color' => 'red'],
            ['label' => 'Original Entry', 'value' => '1', 'color' => 'red'],
        ]);

        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        $signatories = $this->othersClass->getSignatories($config);
        $prepared = '';
        $checked = '';
        $approved = '';
        foreach ($signatories as $key => $value) {
            switch ($value->fieldname) {
                case 'prepared':
                    $prepared = $value->fieldvalue;
                    break;
                case 'checked':
                    $checked = $value->fieldvalue;
                    break;
                case 'approved':
                    $approved = $value->fieldvalue;
                    break;
            }
        }

        return $this->coreFunctions->opentable(
            "select 
      'PDFM' as print,
      '" . $prepared . "' as prepared,
      '" . $checked . "' as checked,
      '" . $approved . "' as approved,
      '0' as reporttype,
      '0' as layoutformat,
      '1' as itemsort,
      '0' as sorting"
        );
    }

    public function report_default_query($trno, $params = null)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);

        $orderby = 'line';
        if ($params !== null && $this->sortbywarehouse($params)) {
            $orderby = 'whcode, line';
        }

        $query = "select head.clientname, date(head.dateid) as dateid, head.docno, head.address, head.terms, head.rem,
        agent.clientname as agentname, head.wh as whcode,
        stock.line, stock.isqty as qty, stock.qa, stock.uom, item.barcode, item.itemname,
        stock.isamt as amt, stock.disc, stock.ext as total, stock.cbm
        from sohead as head
        left join sostock as stock on stock.trno = head.trno
        left join client as agent on agent.client = head.agent
        left join item on item.itemid = stock.itemid
        where head.trno = '$trno'

        union all

        select head.clientname, date(head.dateid) as dateid, head.docno, head.address, head.terms, head.rem,
        agent.clientname as agentname, head.wh as whcode,
        stock.line, stock.isqty as qty, stock.qa, stock.uom, item.barcode, item.itemname,
        stock.isamt as amt, stock.disc, stock.ext as total, stock.cbm
        from hsohead as head
        left join hsostock as stock on stock.trno = head.trno
        left join client as agent on agent.client = head.agent
        left join item on item.itemid = stock.itemid
        where head.doc = 'so' and head.trno = '$trno'

        order by $orderby";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        if (!empty($result)) {
            $custname = isset($result[0]['clientname']) ? $result[0]['clientname'] : '';
            $other = $this->getotherdetail($custname);
            foreach ($result as $k => $row) {
                foreach ($other as $key => $val) {
                    $result[$k][$key] = $val;
                }
            }
        }

        return $result;
    } //end fn  

    public function getotherdetail($clientname)
    {
        $outstanding = 0;
        $outstanding30 = 0;
        $firsttrans = '';
        $transcount = 0;
        $pdc = 0;
        $returncheck = 0;
        $bounced = 0;
        $asof = date('Y-m-d');
        $custname = addslashes($clientname);


        $ids = [];
        if ($custname != '') {
            $idrows = json_decode(json_encode($this->coreFunctions->opentable("select clientid from client where clientname = '$custname'")), true);
            if (!empty($idrows)) {
                foreach ($idrows as $idrow) {
                    $ids[] = intval($idrow['clientid']);
                }
            }
        }

        if (!empty($ids)) {
            $idlist = implode(',', $ids);

            $query = "select
                a.outstanding_ar, a.outstanding_ar30, a.firsttransdate, a.transcount,
                p.pdc, (a.returncheck_posted + l.ret_unposted) as returncheck, b.bounced
                from (select 1 as one) as base
                left join (
                    select
                    ifnull(sum(if(left(coa.alias, 2) = 'AR' and ar.bal <> 0 and date(ar.dateid) <= '$asof', ar.bal, 0)), 0) as outstanding_ar,
                    ifnull(sum(if(left(coa.alias, 2) = 'AR' and ar.bal <> 0 and datediff('$asof', date(ar.dateid)) > 30, ar.bal, 0)), 0) as outstanding_ar30,
                    min(if(left(coa.alias, 2) = 'AR', date(ar.dateid), null)) as firsttransdate,
                    count(distinct if(left(coa.alias, 2) = 'AR', ar.trno, null)) as transcount,
                    ifnull(sum(if(coa.alias = 'arb', ar.bal, 0)), 0) as returncheck_posted
                    from arledger as ar
                    left join coa on coa.acnoid = ar.acnoid
                    where ar.clientid in ($idlist)
                    and (left(coa.alias, 2) = 'AR' or coa.alias = 'arb')
                ) as a on 1 = 1
                left join (
                    select ifnull(sum(x.amt), 0) as pdc from (
                        select (d.db - d.cr) as amt
                            from glhead as h
                            left join gldetail as d on d.trno = h.trno
                            left join crledger on crledger.trno = d.trno and crledger.line = d.line
                            left join coa on coa.acnoid = d.acnoid
                            where left(coa.alias, 2) = 'cr' and crledger.depodate is null and h.doc = 'cr'
                            and d.clientid in ($idlist)
                            and date(d.postdate) >= '$asof'
                        union all
                        select (d.db - d.cr) as amt
                            from lahead as h
                            left join ladetail as d on d.trno = h.trno
                            left join client as cl on cl.client = d.client
                            left join coa on coa.acnoid = d.acnoid
                            where left(coa.alias, 2) = 'cr'
                            and cl.clientid in ($idlist)
                            and date(d.postdate) >= '$asof'
                    ) as x
                ) as p on 1 = 1
                left join (
                    select ifnull(sum(abs(d.db - d.cr)), 0) as ret_unposted
                        from lahead as h
                        left join ladetail as d on d.trno = h.trno
                        left join client as cl on cl.client = d.client
                        left join coa on coa.acnoid = d.acnoid
                        where coa.alias = 'arb'
                        and cl.clientid in ($idlist)
                ) as l on 1 = 1
                left join (
                    select ifnull(sum(if(coa.alias = 'arb', d.db, 0)), 0) as bounced
                        from glhead as head
                        left join gldetail as d on d.trno = head.trno
                        left join coa on coa.acnoid = d.acnoid
                        where head.clientid in ($idlist)
                ) as b on 1 = 1";
            $res = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
            if (isset($res[0])) {
                $outstanding = $res[0]['outstanding_ar'];
                $outstanding30 = $res[0]['outstanding_ar30'];
                $firsttrans = $res[0]['firsttransdate'];
                $transcount = $res[0]['transcount'];
                $pdc = $res[0]['pdc'];
                $returncheck = $res[0]['returncheck'];
                $bounced = $res[0]['bounced'];
            }
        }

        return [
            'totalar' => $outstanding != 0 ? number_format($outstanding, 2) : '-',
            'totalar30' => $outstanding30 != 0 ? number_format($outstanding30, 2) : '-',
            'firsttransdate' => ($firsttrans != '' && $firsttrans != null) ? $firsttrans : '-',
            'transcount' => $transcount > 0 ? number_format($transcount, 2) : '-',
            'totalpdc' => $pdc != 0 ? number_format($pdc, 2) : '-',
            'returncheck' => $returncheck != 0 ? number_format($returncheck, 2) : '-',
            'bouncecheck' => $bounced > 0 ? number_format($bounced, 2) : '-',
        ];
    }

    public function reportplotting($params, $data)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', -1);

        return $this->default_so_PDF($params, $data);
    }

    private function sortbywarehouse($params)
    {
        $sorting = isset($params['params']['dataparams']['sorting']) ? $params['params']['dataparams']['sorting'] : '0';
        return $sorting == '0';
    }

    private function showotherdetail($params)
    {
        $option = isset($params['params']['dataparams']['reporttype']) ? $params['params']['dataparams']['reporttype'] : '0';
        return $option == '0';
    }

    private function pendinglayout($params)
    {
        $layout = isset($params['params']['dataparams']['layoutformat']) ? $params['params']['dataparams']['layoutformat'] : '0';
        return $layout == '1';
    }

    private function withprice($params)
    {
        $format = isset($params['params']['dataparams']['itemsort']) ? $params['params']['dataparams']['itemsort'] : '1';
        return $format == '0';
    }

    private function reporttitle($params)
    {
        return $this->pendinglayout($params) ? 'Pending Order' : $this->modulename;
    }

    private function getcolumns($params)
    {
        if ($this->withprice($params)) {
            $cols = [
                ['qty', 'QTY', 50, 'C', 'C', 13],
                ['uom', 'UNIT', 50, 'C', 'C', 13],
                ['code', 'SHORT CODE', 90, 'C', 'C', 13],
                ['desc', 'DESCRIPTION', 250, 'L', 'C', 40],
                ['amt', 'AMOUNT', 85, 'R', 'R', 13],
                ['disc', 'D %', 45, 'C', 'C', 8],
                ['total', 'TOTAL', 90, 'R', 'R', 13],
                ['cbm', 'CBM', 40, 'R', 'R', 8],
            ];
        } else {
            $cols = [
                ['qty', 'QTY', 50, 'C', 'C', 13],
                ['uom', 'UNIT', 50, 'C', 'C', 13],
                ['code', 'SHORT CODE', 110, 'L', 'C', 15],
                ['desc', 'DESCRIPTION', 280, 'L', 'C', 50],
                ['wh', 'WH', 110, 'C', 'C', 15],
                ['cbm', 'CBM', 100, 'R', 'R', 12],
            ];
        }

        if ($this->pendinglayout($params)) {
            $cols[0] = ['qty', 'PEND. QTY', 65, 'C', 'C', 13];
            $cols[3][2] -= 15;
            $cols[3][5] -= 2;
        }

        return $cols;
    }

    public function default_so_header_PDF($params, $data)
    {
        $center = $params['params']['center'];
        $username = $params['params']['user'];
        $showother = $this->showotherdetail($params);

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $font = "";
        $fontbold = "";
        $fontsize = 11;
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }

        PDF::SetTitle($this->modulename);
        PDF::SetAuthor('Solutionbase Corp.');
        PDF::SetCreator('Solutionbase Corp.');
        PDF::SetSubject($this->modulename . ' Module Report');
        PDF::setPageUnit('px');
        PDF::AddPage('p', [800, 1000]);
        PDF::SetMargins(40, 40);

        PDF::SetFont($font, '', 9);
        $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
        PDF::MultiCell(350, 0, $reporttimestamp, '', 'L', false, 0);
        PDF::MultiCell(350, 0, 'Page ' . PDF::getAliasNumPage() . ' of ' . PDF::getAliasNbPages(), '', 'R');

        // company
        PDF::SetFont($fontbold, '', 14);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
        PDF::SetFont($fontbold, '', 13);
        PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

        // title / docno
        PDF::SetFont($fontbold, '', 18);
        PDF::MultiCell(520, 0, $this->reporttitle($params), '', 'L', false, 0, '',  '100');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '');

        PDF::SetFont($font, '', $fontsize);
        if ($showother) {
            PDF::MultiCell(0, 30, "\nReceiving Time :", '', 'C');
        } else {
            PDF::MultiCell(0, 30, "", '', 'L');
        }

        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

        PDF::MultiCell(0, 0, "\n");

        $address = isset($data[0]['address']) ? $data[0]['address'] : '';
        $arr_address = $this->reporter->fixcolumn([$address], '80', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_address]);
        if ($maxrow < 1) {
            $maxrow = 1;
        }

        for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(470, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), 'B', 'L', false, 0, '',  '');
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(50, 0, 'Terms: ', '', 'L', false, 0, '',  '');
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '');
            } else {
                PDF::SetFont($font, '', $fontsize);
                PDF::MultiCell(80, 0, '', '', 'L', false, 0, '',  '');
                PDF::MultiCell(470, 0, (isset($arr_address[$r]) ? $arr_address[$r] : ''), '', 'L', false, 1, '',  '');
            }
        }

        PDF::MultiCell(550, 0, '', '', 'L', false, 0, '',  '');
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(50, 0, 'Agent: ', '', 'L', false, 0, '',  '');
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 0, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''), 'B', 'L', false, 1, '',  '');

        if ($showother) {
            PDF::SetFont($font, '', 6);
            PDF::MultiCell(700, 0, '', '');
            PDF::SetFont($font, '', 10);
            $other = [
                [['TOTAL A/R', 'totalar'], ['1ST TRANS. DATE', 'firsttransdate'], ['TRANS. COUNT', 'transcount']],
                [['TOTAL A/R > 30', 'totalar30'], ['RETURN CHECK', 'returncheck'], ['DEFFERED', 'deffered']],
                [['TOTAL PDC', 'totalpdc'], ['BOUNCE CHECK', 'bouncecheck'], ['BACK LOAD', 'backload']],
            ];
            foreach ($other as $row) {
                foreach ($row as $ci => $cell) {
                    $val = (isset($data[0][$cell[1]]) && $data[0][$cell[1]] !== '') ? $data[0][$cell[1]] : '-';
                    $last = ($ci == 2) ? 1 : 0;
                    PDF::MultiCell(120, 0, $cell[0] . ' :', '', 'L', false, 0);
                    PDF::MultiCell(113, 0, $val, '', 'L', false, $last);
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');
        PDF::MultiCell(700, 0, '', '');
        PDF::MultiCell(700, 0, '', 'T');

        $columns = $this->getcolumns($params);
        PDF::SetFont($font, 'B', 11);
        foreach ($columns as $ci => $col) {
            $ln = ($ci == count($columns) - 1) ? 1 : 0;
            PDF::MultiCell($col[2], 0, $col[1], '', $col[4], false, $ln);
        }
        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
    }

    public function default_so_PDF($params, $data)
    {
        $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
        $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
        $withprice = $this->withprice($params);
        $columns = $this->getcolumns($params);
        $totalext = 0;
        $totalcbm = 0;

        $lines = $data;
        if ($this->pendinglayout($params)) {
            $lines = [];
            if (!empty($data)) {
                foreach ($data as $row) {
                    $ordered = (float)$row['qty'];
                    $pend = $ordered - (float)$row['qa'];
                    if ($pend <= 0) {
                        continue;
                    }
                    $ratio = $ordered != 0 ? $pend / $ordered : 0;
                    $row['qty'] = $pend;
                    $row['total'] = (float)$row['total'] * $ratio;
                    $row['cbm'] = (float)$row['cbm'] * $ratio;
                    $lines[] = $row;
                }
            }
        }

        $font = "";
        $fontbold = "";
        $fontsize = "11";
        if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
            $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
            $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
        }
        $this->default_so_header_PDF($params, $data);

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', '');

        if (!empty($lines)) {
            for ($i = 0; $i < count($lines); $i++) {
                $vals = [
                    'qty' => number_format($lines[$i]['qty'], $decimalqty),
                    'uom' => $lines[$i]['uom'],
                    'code' => $lines[$i]['barcode'],
                    'desc' => $lines[$i]['itemname'],
                    'amt' => number_format($lines[$i]['amt'], $decimalcurr),
                    'disc' => $lines[$i]['disc'],
                    'total' => number_format($lines[$i]['total'], $decimalcurr),
                    'wh' => $lines[$i]['whcode'],
                    'cbm' => number_format($lines[$i]['cbm'], 2),
                ];

                $arr = [];
                foreach ($columns as $col) {
                    $arr[$col[0]] = $this->reporter->fixcolumn([$vals[$col[0]]], (string)$col[5], 0);
                }

                $maxrow = $this->othersClass->getmaxcolumn(array_values($arr));
                for ($r = 0; $r < $maxrow; $r++) {
                    PDF::SetFont($font, '', $fontsize);
                    foreach ($columns as $ci => $col) {
                        $ln = ($ci == count($columns) - 1) ? 1 : 0;
                        PDF::MultiCell($col[2], 15, ' ' . (isset($arr[$col[0]][$r]) ? $arr[$col[0]][$r] : ''), '', $col[3], false, $ln, '',  '', true, 0, false, true, 0, 'M', false);
                    }
                }

                $totalext += $lines[$i]['total'];
                $totalcbm += $lines[$i]['cbm'];
                if (PDF::getY() > 900) {
                    $this->default_so_header_PDF($params, $data);
                }
            }
        }

        PDF::SetFont($font, '', 5);
        PDF::MultiCell(700, 0, '', 'B');
        PDF::MultiCell(700, 0, '', '');

        PDF::SetFont($fontbold, '', $fontsize);
        if ($withprice) {
            PDF::MultiCell(570, 0, 'GRAND TOTAL: ', 'B', 'R', false, 0);
            PDF::MultiCell(90, 0, 'P ' . number_format($totalext, $decimalcurr), 'B', 'R', false, 0);
            PDF::MultiCell(40, 0, number_format($totalcbm, 2), 'B', 'R');
        } else {
            PDF::MultiCell(490, 0, 'GRAND TOTAL: ', 'B', 'R', false, 0);
            PDF::MultiCell(110, 0, 'P ' . number_format($totalext, $decimalcurr), 'B', 'R', false, 0);
            PDF::MultiCell(100, 0, number_format($totalcbm, 2), 'B', 'R');
        }

        PDF::MultiCell(0, 0, "\n");

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
        PDF::MultiCell(560, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L');

        $prepared = isset($params['params']['dataparams']['prepared']) ? $params['params']['dataparams']['prepared'] : '';
        if ($prepared == '') {
            $user = $params['params']['user'];
            $qry = "select name from useraccess where username='$user'";
            $username = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
            $prepared = (isset($username[0]['name']) && $username[0]['name'] != '') ? $username[0]['name'] : $user;
        }

        PDF::MultiCell(0, 0, "\n\n\n");
        PDF::MultiCell(233, 0, 'Prepared By: ', '', 'L', false, 0);
        PDF::MultiCell(233, 0, 'Verified By: ', '', 'L', false, 0);
        PDF::MultiCell(233, 0, 'Approved By: ', '', 'L');

        PDF::MultiCell(0, 0, "\n");
        PDF::MultiCell(233, 0, $prepared, '', 'L', false, 0);
        PDF::MultiCell(233, 0, $params['params']['dataparams']['checked'], '', 'L', false, 0);
        PDF::MultiCell(233, 0, $params['params']['dataparams']['approved'], '', 'L');

        return PDF::Output($this->modulename . '.pdf', 'S');
    }
}
