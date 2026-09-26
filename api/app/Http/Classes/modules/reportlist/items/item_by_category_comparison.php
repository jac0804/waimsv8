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
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;


class item_by_category_comparison
{
    public $modulename = 'Item by Category Comparison';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];
    
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
        $fields = ['radioprint', 'start', 'end','category'];
        $col1 = $this->fieldClass->create($fields);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);
        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        return $this->coreFunctions->opentable("select 
        'default' as print,
        adddate(left(now(),10),-30) as start,
        left(now(),10) as end,
        '' as category, 
        '' as categoryname
        ");
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
        return $this->reportDefaultLayout($config);
    }

    public function reportQuery($config)
    {
        $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
        $end  = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
        $category = $config['params']['dataparams']['category'];
        $filter = '';

        $query ="
        select item.itemname, item.category, item.class,
        (stock.ext-ifnull(info.lessvat,0)-ifnull(info.sramt,0)-ifnull(info.pwdamt,0)) as ext
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
        where 1=1 and item.category <> '' and item.category <> 0
        union all
        select item.itemname, item.category, item.class,
        (stock.ext-ifnull(info.lessvat,0)-ifnull(info.sramt,0)-ifnull(info.pwdamt,0)) as ext
        from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
        where 1=1 and item.category <> '' and item.category <> 0
        ";
        return $this->coreFunctions->opentable($query);        
    }

    public function header_layout($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid"; //dotted
        $str .= '<br><br>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('COMPANY NAME' , '1000', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ITEM SUMMARY COMPARISON', '1000', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CLASS / CATEGORY', '400', 30, false, $border, '', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('2024', '150', 30, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('2025', '150', 30, false, $border, 'TLR', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '300', 30, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function subTotal($config)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];

        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid"; //dotted

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', 30, false, $border, 'LB', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('CLASS SUBTOTAL', '250', 30, false, $border, 'BL', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('-', '150', 30, false, $border, 'BL', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('-', '150', 30, false, $border, 'BLR', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '300', 30, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    public function reportDefaultLayout($config)
    {
        $str = '';
        $layoutsize = '1000';
        $font = $this->companysetup->getrptfont($config['params']);
        $fontsize = "10";
        $border = "1px solid"; //dotted
        $str .= $this->reporter->beginreport();
        $str .= $this->header_layout($config);
        $query = $this->reportQuery($config);


        // foreach
        //category border TLR---> B- gets addded on the subtotal
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CLASS / CATEGORY', '150', 30, false, $border, 'TL', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('CLASS / CATEGORY', '250', 30, false, $border, 'TBL', 'C', $font, '10', 'B', '', '');
        $str .= $this->reporter->col('-', '150', 30, false, $border, 'TBL', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('-', '150', 30, false, $border, 'TBLR', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col('', '300', 30, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        //subtotal
        $str .= $this->subTotal($config);

        $str .= $this->reporter->endreport();
        return $str;
    }


}