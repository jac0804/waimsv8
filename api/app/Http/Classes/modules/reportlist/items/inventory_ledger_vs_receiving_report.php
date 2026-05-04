<?php

namespace App\Http\Classes\modules\reportlist\items;

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
use App\Http\Classes\modules\consignment\co;
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;
use Illuminate\Support\Facades\URL;

class inventory_ledger_vs_receiving_report
{
    public $modulename = 'Inventory Ledger vs Receiving Report';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:3500px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'asofdate', 'dwhname', 'ditemname'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);
        data_set($col1, 'asofdate.readonly', false);
        data_set($col1, 'dwhname.required', true);


        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {

        $center = $config['params']['center'];
        $companyid = $config['params']['companyid'];
        $dcenter = $this->coreFunctions->opentable("select name,code,concat(code,'~',name) as dcentername from center where code =? ", [$center]);
        $paramstr = "select 
      'default' as print,
        '" . $this->othersClass->getCurrentDate() . "' as asofdate, 
    '' as client,
    '' as clientname,
    '0' as clientid,
    '' as client,
    '' as clientname,
    '' as itemname,
       '' as wh,
    '' as whname,
    '' as dwhname,
    '' as barcode,
    '' as ditemname,
      '" . $center . "' as center,
      '" . $dcenter[0]->dcentername . "' as dcentername,
      '" . $dcenter[0]->name . "' as centername,
      '' as prefix
      ";
        return $this->coreFunctions->opentable($paramstr);
    }


    public function getloaddata($config)
    {
        return [];
    }

    public function reportdata($config)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');

        $str = $this->reportplotting($config);

        return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
    }

    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $companyid = $config['params']['companyid'];

        return $this->reportDefault_Layout($config);
    }


    public function reportDefault_query($config)
    {
        $center = $config['params']['dataparams']['center'];
        $dwhname = $config['params']['dataparams']['wh'];
        $itemname = $config['params']['dataparams']['itemname'];
        $asofdate = $config['params']['dataparams']['asofdate'];
        $filter = "";



        if ($dwhname != "") {
            $filter .= " and warehouse.client ='$dwhname'";
        }

        if ($itemname != "") {
            $filter .= " and item.itemname =  '$itemname'";
        }

        $query = "
           select barcode, itemname, ledgerbal, bal, ledgerbal - bal as difference
        from (
        select x.barcode, x.itemname, sum(x.ledgerbal) as ledgerbal,
        (select sum(bal) from rrstatus as rr where rr.itemid = x.itemid and rr.whid = x.whid) as bal
        from (
            select item.barcode, item.itemname, item.itemid, warehouse.clientid as whid, sum(stock.qty - stock.iss) as ledgerbal
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client as warehouse on warehouse.clientid = stock.whid
            left join cntnum as cnum on cnum.trno = head.trno
            where item.itemname <> '' and date(head.dateid) <= '$asofdate' and cnum.center = '$center' $filter
            group by item.barcode, item.itemname, item.itemid, warehouse.clientid

            union all

            select item.barcode, item.itemname, item.itemid, warehouse.clientid as whid, sum(stock.qty - stock.iss) as ledgerbal
            from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client as warehouse on warehouse.clientid = stock.whid
            left join cntnum as cnum on cnum.trno = head.trno
            where item.itemname <> '' and date(head.dateid) <= '$asofdate' and cnum.center = '$center' $filter
            group by item.barcode, item.itemname, item.itemid, warehouse.clientid

        ) as x
        group by x.barcode, x.itemname, x.itemid, x.whid
        ) as y
        order by barcode, itemname
        ";
        // var_dump($query);
        return $this->coreFunctions->opentable($query);
    }


    public function displayHeader($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid = $config['params']['companyid'];
        $result = $this->reportDefault_query($config);
        $this->reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
        $str = '';
        $layoutsize = '1000';
        $font = "Tahoma";
        $fontsize = "11";
        $border = "1px solid ";

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);


        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Inventory Ledger VS Receiving Reports', null, null, false, '2px solid', '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br></br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('As of Date: ' . $config['params']['dataparams']['asofdate'], null, null, false, '', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('Warehouse: ' . $config['params']['dataparams']['whname'], null, null, false, '', '', 'L', $font, $fontsize, 'B', '', '');
        if ($config['params']['dataparams']['itemname'] != "") {
            $str .= $this->reporter->col('Item: ' . $config['params']['dataparams']['itemname'], null, null, false, '', '', 'L', $font, $fontsize, 'B', '', '');
        } else {
            $str .= $this->reporter->col('Item: All ITEM', null, null, false, '', '', 'L', $font, '10', 'B', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Item Code', '200', null, false, '2px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Itemname', '350', null, false, '2px solid', 'BT', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Ledger Balance', '150', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Receiving Balance', '150', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('Difference', '150', null, false, '2px solid', 'TB', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        return $str;
    }

    public function reportDefault_Layout($config)
    {
        $str = '';
        $filter = "";
        $layoutsize = '1000';
        $font = 'Tahoma';
        $fontsize = "10";
        $border = "1px solid";
        $this->reporter->linecounter = 0;

        $result = $this->reportDefault_query($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        $str .= $this->reporter->beginreport($layoutsize);
        $str .= $this->displayHeader($config);

        $limitPerPage = 30;
        $rowCount = 0;

        foreach ($result as $row) {

            // ITEM ROW
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($row->barcode, '200', '30', false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col($row->itemname, '350', '30', false, '', '', 'LT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($row->ledgerbal, 2), '150', '30', false, '', '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($row->bal, 2), '150', '30', false, '', '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($row->difference, 2), '150', '30', false, '', '', 'RT', $font, $fontsize, '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->endreport();
        return $str;
    }
}//end class
