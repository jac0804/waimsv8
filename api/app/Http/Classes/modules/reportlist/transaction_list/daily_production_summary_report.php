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

class daily_production_summary_report
{
    public $modulename = 'Daily Production Summary Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1850px;max-width:1850px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1850'];

    public function __construct($params = null)
    {
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->fieldClass = new txtfieldClass($params);
        $this->reporter = new SBCPDF;
    }

    public function createHeadField($config)
    {

        $fields = ['radioprint', 'start', 'end',  'itemcategoryname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'start.required', false);
        data_set($col1, 'type.label', 'Type');
        data_set($col1, 'type.type', 'lookup');
        data_set($col1, 'type.action', 'lookuprandom');
        data_set($col1, 'type.lookupclass', 'lookup_shifttype');
        data_set($col1, 'type.class', 'sbccsreadonly');
        data_set($col1, 'type.required', false);
        data_set($col1, 'itemcategoryname.action', 'lookupcategory');
        data_set($col1, 'itemcategoryname.lookupclass', 'lookupcategoryitemcategory');
        data_set($col1, 'itemcategoryname.class', 'cscategory sbccsreadonly');


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);

        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        $qry = "select 'default' as print,
        '" . $this->othersClass->getCurrentDate() . "' as start,
         '" . $this->othersClass->getCurrentDate() . "' as end, 
                '' as type, '' as category, 0 as catid, '' as itemcategoryname";

        return $this->coreFunctions->opentable($qry);
    }


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
        $result = $this->reportDefaultLayout_DPR($config);
        return $result;
    }

    public function reportDefault($config)
    {
        $query = $this->default_QUERY_DPR($config);
        return $this->coreFunctions->opentable($query);
    }

    public function default_QUERY_DPR($config)
    {
        // lahat ng piece rate (draft + posted) ng napiling date
        $center   = $config['params']['center'];
        $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $type     = $config['params']['dataparams']['type'];
        $catid    = $config['params']['dataparams']['catid'];
        $category = $config['params']['dataparams']['itemcategoryname'];

        $filter = "";
        if ($category != "") {
            $catids = implode(',', array_map('intval', explode('~', $catid)));
            $filter .= " and cat.line in ($catids)";
        }
        // if ($type != "") {
        //     $filter .= " and head.type = '$type'";
        // }

        $query = "select item.shortname, head.type, head.dateid, stock.pcswork
        from dahead as head
        left join piecetrans as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join hrisnum as num on num.trno = head.trno 
        left join itemcategory as cat on cat.line = item.category
        where date(head.dateid) between '$start' and '$end' $filter

        union all

        select item.shortname, head.type, head.dateid, stock.pcswork
        from hdahead as head
        left join hpiecetrans as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join hrisnum as num on num.trno = head.trno
        left join itemcategory as cat on cat.line = item.category
        where date(head.dateid) between '$start' and '$end' $filter
        ";
        return $query;
    }

    public function default_header_dpr($config, $layoutsize)
    {
        $center = $config['params']['center'];
        $start  = date("d-M-Y", strtotime($config['params']['dataparams']['start']));
        $end    = !empty($config['params']['dataparams']['end']) ? date("d-M-Y", strtotime($config['params']['dataparams']['end'])) : $start;

        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $company = $this->coreFunctions->datareader("select name as value from center where code = '$center'");

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($company), null, null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DAILY PRODUCTION SUMMARY', null, null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Date : ' . $start . ' &nbsp;to&nbsp; ' . $end, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';

        return $str;
    }

    public function default_tableheader_dpr($config, $items, $layoutsize, $datew, $typew, $totalw, $itemw)
    {
        $str = '';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', $datew + $typew, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        // blank header = qty of items with no shortname (no bottom border, the data cell below draws it)
        $str .= $this->reporter->col('', $totalw, null, false, $border, 'LRT', 'C', $font, $fontsize, 'B', '', '8px');
        foreach ($items as $shortname) {
            $str .= $this->reporter->col($shortname, $itemw, null, false, $border, 'LRT', 'C', $font, $fontsize, 'B', '', '8px');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout_DPR($config)
    {
        $result = $this->reportDefault($config);

        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }


        $matrix = [];       // [date][type][shortname] = qty
        $noshort = [];      // [date][type] = qty (no shortname)
        $items = [];        // only items that have a shortname
        $itemtotals = [];
        $noshorttotal = 0;

        foreach ($result as $r) {
            $d = date('Y-m-d', strtotime($r->dateid));
            $t = $r->type;
            $s = trim((string)$r->shortname);
            $q = (float)$r->pcswork;

            if (!isset($matrix[$d][$t])) {
                $matrix[$d][$t] = [];
            }
            if (!isset($noshort[$d][$t])) {
                $noshort[$d][$t] = 0;
            }

            if ($s == '') {
                $noshort[$d][$t] += $q;
                $noshorttotal += $q;
            } else {
                if (!isset($matrix[$d][$t][$s])) {
                    $matrix[$d][$t][$s] = 0;
                }
                $matrix[$d][$t][$s] += $q;

                $items[$s] = $s;
                if (!isset($itemtotals[$s])) {
                    $itemtotals[$s] = 0;
                }
                $itemtotals[$s] += $q;
            }
        }

        ksort($matrix);
        ksort($items);

        // fixed column widths
        $datew = 60;
        $typew = 50;
        $totalw = 90;
        $itemw = 90;


        $tablesize = $datew + $typew + $totalw + (count($items) * $itemw);

        $layoutsize = max(1000, $tablesize);

        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid ";
        $pad = '8px';

        $page = 30;
        $line = 0;

        $str = '';
        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->default_header_dpr($config, $layoutsize);
        $str .= $this->default_tableheader_dpr($config, $items, $tablesize, $datew, $typew, $totalw, $itemw);

        foreach ($matrix as $date => $types) {
            ksort($types);
            $ti = 0;

            foreach ($types as $type => $itemqty) {
                $ti++;

                if ($line >= $page) {
                    $str .= $this->reporter->page_break();
                    $str .= $this->default_header_dpr($config, $layoutsize);
                    $str .= $this->default_tableheader_dpr($config, $items, $tablesize, $datew, $typew, $totalw, $itemw);
                    $line = 0;
                }


                $dateborder = ($ti == 1) ? 'LRT' : 'LR';
                $datetext = ($ti == 1) ? date('d - M', strtotime($date)) : '';

                $nsqty = $noshort[$date][$type];

                $str .= $this->reporter->begintable($tablesize);
                $str .= $this->reporter->startrow();
                $str .= $this->reporter->col($datetext, $datew, null, false, $border, $dateborder, 'C', $font, $fontsize, '', '', $pad);
                $str .= $this->reporter->col($type, $typew, null, false, $border, 'LRT', 'C', $font, $fontsize, '', '', $pad);
                $str .= $this->reporter->col($nsqty == 0 ? '' : number_format($nsqty, 0), $totalw, null, false, $border, 'LRT', 'C', $font, $fontsize, '', '', $pad);
                foreach ($items as $shortname) {
                    $qty = isset($itemqty[$shortname]) ? $itemqty[$shortname] : 0;
                    $str .= $this->reporter->col($qty == 0 ? '' : number_format($qty, 0), $itemw, null, false, $border, 'LRT', 'C', $font, $fontsize, '', '', $pad);
                }
                $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

                $line++;
            }
        }

        $str .= $this->reporter->begintable($tablesize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('GRAND TOTAL', $datew + $typew, null, false, $border, 'LRTB', 'L', $font, $fontsize, 'B', '', $pad);
        $str .= $this->reporter->col(number_format($noshorttotal, 0), $totalw, null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', $pad);
        foreach ($items as $shortname) {
            $str .= $this->reporter->col(number_format($itemtotals[$shortname], 0), $itemw, null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', $pad);
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endreport();

        return $str;
    }
}//end class