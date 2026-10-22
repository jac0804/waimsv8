<?php

namespace App\Http\Classes\modules\reportlist\product_portal_report;

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
use PhpParser\Node\Stmt\Break_;
use Illuminate\Support\Facades\URL;

class catalog
{
    public $modulename = 'Catalog';
    private $companysetup;
    private $coreFunctions;
    private $fieldClass;
    private $othersClass;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $directprint = false;
    public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'brand', 'brand2', 'category', 'carbrand', 'radioreporttype'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'category.action', 'lookupcategoryitemstockcard');
        data_set($col1, 'category.name', 'categoryname');
        data_set($col1, 'radioprint.options', [
            ['label' => 'Default', 'value' => 'default', 'color' => 'red']
        ]);

        data_set($col1, 'radioreporttype.options', [
            ['label' => 'Catalog 1', 'value' => '1', 'color' => 'red'],
            ['label' => 'Catalog 2', 'value' => '2', 'color' => 'red'],
            ['label' => 'Catalog 3', 'value' => '3', 'color' => 'red'],
            ['label' => 'Catalog 4', 'value' => '4', 'color' => 'red']

        ]);
        $fields = ['print'];
        $col2 = $this->fieldClass->create($fields);


        return array('col1' => $col1, 'col2' => $col2);
    }

    public function paramsdata($config)
    {
        // NAME NG INPUT YUNG NAKA ALIAS
        return $this->coreFunctions->opentable("select 
    'default' as print,
     0 as category,
     '' as categoryname,
    
     '' as brand,
     0 as brandid,
     '' as brandname,
     
     '' as brand2,
     0 as brandid2,
     '' as brandname2,

     '' as carbrand,
     0 as carid,
    '1' as reporttype");
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
    // LAYOUT OF REPORT
    public function reportplotting($config)
    {
        $center = $config['params']['center'];
        $username = $config['params']['user'];
        $format = $config['params']['dataparams']['reporttype'];

        switch($format){
         case 1:
            $result = $this->option_1($config);
            break;
         case 2:
            $result = $this->option_2($config);
            break;
         case 3:
            $result = $this->option_3($config);
            break;
         case 4:
            $result = $this->option_4($config);
            break;
        }

        

        return $result;
    }
    // RESULT QUERY
    public function reportDefault($config)
    {
        $query = $this->reportQuery_DETAILED($config);
        return $this->coreFunctions->opentable($query);
    }
    public function reportQuery_DETAILED($config)
    {
        $brandname    = $config['params']['dataparams']['brandname'];
        $brandname2   = $config['params']['dataparams']['brandname2'];
        $brandid      = $config['params']['dataparams']['brandid'];
        $brandid2     = $config['params']['dataparams']['brandid2'];
        $catid        = $config['params']['dataparams']['category'];
        $carid        = $config['params']['dataparams']['carid'];
        $carbrand     = $config['params']['dataparams']['carbrand'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        $format       = $config['params']['dataparams']['reporttype'];

        $filter = '';
        // BRAND: kapag parehong may pinili sa dalawang Brand lookup, alinman sa dalawa
        if ($brandname != '' && $brandname2 != '') {
            $filter .= " and ( i.brand = '$brandid' or i.brand='$brandid2') ";
        } else {
            if ($brandname != '') {
                $filter .= " and i.brand = '$brandid'";
            }
            if ($brandname2 != '') {
                $filter .= " and i.brand = '$brandid2'";
            }
        }

        // CATEGORY
        if ($categoryname != '') {
            $filter .= " and i.category = '$catid'";
        }

        // CAR BRAND
        if ($carbrand != '') {
            $filter .= " and i.carid = $carid";
        }

        // brand -> category -> subcategory -> car brand, at partname sa loob ng group
        $orderby = "";
        switch ($format) {
            case 1:
            case 2:
                $orderby = " order by br1.brand_desc, cat.name, subcat.name, car.brand, part.part_name";
                break;
            case 3:
            case 4:
                $orderby = " order by cat.name,car.brand asc";
                break;
        }
        $query = "
        select i.itemname,i.barcode,
            ifnull(cat.name,'')        as categoryname,   
            ifnull(subcat.name,'')     as subcatname,     
            part.part_name             as partname,      
            concat('/images/product/',i.partno,'.jpg') as picture,                                    
            ifnull(br1.brand_desc,'')  as brand_desc,     
            ifnull(car.brand,'')       as carbrand,      
            i.isnewarrival,                               
            i.amt,                                       
            ifnull(i.sizeid,'')        as sizeid,i.partno, pos.positions as position,info.fyear,i.type    
        from item as i
        left join frontend_ebrands as br1    on br1.brandid  = i.brand
        left join itemcategory     as cat    on cat.line     = i.category
        left join itemsubcategory  as subcat on subcat.line  = i.subcat
        left join carbrand         as car    on car.id       = i.carid
        left join part_masterfile  as part   on part.part_id = i.part
        left join iteminfo as info on info.itemid=i.itemid
        left join positions as pos on pos.id = info.positionid
        where 1=1 $filter $orderby";

        return $query;
    }




    public function option_1($config)
    {
        $result = $this->reportDefault($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        
        // isang beses lang sinusuri bago ang loop, para mabilis
        $tagpath = '/images/bx/jdatag1.png';
        $tagimg = '';
        if (file_exists(public_path(ltrim($tagpath, '/')))) {
            $tagimg = '<img class="tg" src="' . asset('/public' . $tagpath) . '">';
        }

        $str = '<style>
            /* CARD (ang malaking box ng bawat item)  */
            .cd{
                position:relative;          /* basehan ng tag, image at amt na naka-absolute */
                box-sizing:border-box;      /* kasama na ang border at padding sa width/height */
                width:952px;                /* lapad ng card */
                height:258px;               /* taas ng card */
                margin:18px 0 35px 59px;    /* taas 18 | kaliwa 59 */
                border:2px solid #333;      /* guhit ng box */
                border-radius:18px;         /* kanto */
                padding:15px 0 0 20px;      /* layo ng text sa loob: 15px mula taas, 20px mula kaliwa */
                font-family:Arial;          /* pareho sa header */
            }

            /*  PARTNAME (hal. NRD-KI411)  */
            .pn{
                font-family:"Arial Black", Arial, sans-serif;   /* makapal na font, same ng reference */
                font-size:44px;           
                line-height:1.1;            /* para hindi sobrang taas ang linya ng malaking font */
                padding-left:141px;         /* space mula kaliwa  */
                margin-bottom:4px;          /* dikit sa unang linya */
            }

            /* ISANG CLASS PARA SA MGA LINYA (barcode/position, type, sizeid, itemname+fyear) */
            .ln{
                font-size:24px;           
                line-height:1.15;           /* dikit-dikit ang mga linya */
            }

            /* /// new - ITEMNAME + FYEAR: laging 2 linya ang taas (fixed), kahit maikli o mahaba */
            .in2{
                height:2.3em;               /* 2 linya x line-height 1.15 = 2.3 */
                overflow:hidden;            /* kapag lumampas sa 2 linya, puputulin */
                width:600px;                /* hindi aabot sa picture */
            }

            /*  CAR BRAND (pinakababa sa text)  */
            .bd{
                font-size:26px;           
                font-weight:bold;
                color:#666;                 /* gray */
                text-transform:uppercase; 
            }

            /*  PICTURE NG PRODUCT  */
            .im{
                position:absolute;          /* hindi gumagalaw kahit humaba ang text */
                top:12px;                  
                right:72px;                
                width:250px;               
                height:236px;               /* halos kasing-taas ng card (258), may 10px sa ilalim */
                object-fit:contain;         /* buong picture ang kita, hindi nababanat o napuputol */
            }

            /*  PRESYO / AMT  */
            .am{
                position:absolute;
                left:200px;                
                bottom:-21px;              
                width:210px;             
                height:42px;               
                line-height:38px;         
                box-sizing:border-box;     
                background:#fff;            /* puting background para matakpan ang border ng card sa likod */
                border:2px solid #333;
                text-align:center;
                font-size:28px;            
                font-weight:bold;          
            }

            /*  NEW ITEM TAG  */
            .tg{
                position:absolute;
                top:-20px;                  /* lampas ng 20px sa itaas ng card */
                right:-65px;                /* lampas ng 65px sa kanan ng card */
                width:125px;                /* laki ng tag (kusang susunod ang taas) */
            }

            /*  IMAGE UNAVAILABLE: kapareho ng puwesto at laki ng picture  */
            .na{
                position:absolute;
                top:12px;                   /* kapareho ng .im */
                right:72px;                 /* kapareho ng .im */
                width:250px;                /* kapareho ng .im */
                height:236px;               /* kapareho ng .im */
                display:flex;               /* para mai-gitna ang text */
                align-items:center;         /* gitna pataas-pababa */
                justify-content:center;     /* gitna pakaliwa-pakanan */
                font-size:20px;
                color:#999;                
            }
            </style>';

        $str .= $this->reporter->beginreport('1000');

        // Naka-order by na ang query: brand -> category -> subcategory -> car brand -> partname.
        // Magpe-page break kapag nagbago ang group O naka-4 na item sa page.
        // Sa bawat bagong page, ipi-print ulit ang header ng group.

        $prevkey = null;   // group ng naunang item (null = wala pa, ibig sabihin unang item)
        $count = 0;        // bilang ng item sa kasalukuyang page

        foreach ($result as $data) {

            // group: brand + category + subcategory + car brand
            $key = $data->brand_desc . '|' . $data->categoryname . '|' . $data->subcatname . '|' . $data->carbrand;

            // BAGONG PAGE kapag nagbago ang group O naka-4 na sa page
            if ($key !== $prevkey || $count == 4) {
                if ($prevkey !== null) {
                    $str .= $this->reporter->page_break();   // walang break bago ang pinakaunang page
                }
                // header ng page: brand | subcategory | car brand
                $str .= $this->default_displayHeader($config, $data->brand_desc, $data->subcatname, $data->carbrand);
                $prevkey = $key;
                $count = 0;   // bagong page, balik sa 0
            }

            // Blank Space KAPAG WALANG LAMAN
            // kapag empty ang field, &nbsp; ang ilalagay para may taas pa rin ang linya
            // at hindi umakyat ang mga kasunod (fixed ang puwesto ng bawat linya)
            $sizeid   = $data->sizeid   != '' ? $data->sizeid   : '&nbsp;';
            $carbrand = $data->carbrand != '' ? $data->carbrand : '&nbsp;';
            $type     = $data->type     != '' ? $data->type     : '&nbsp;';
            $position = $data->position != '' ? '<b>' . $data->position . '</b>' : '&nbsp;';
            $partno   = $data->partno   != '' ? $data->partno   : '&nbsp;';

            // Line 1: (barcode) naka-bold + position hindi bold
            // $line1 = '';
           
            // if ($data->position != '') {
            //     $line1 .= $data->position;
            // }
            // if ($line1 == '') {
            //     $line1 = '&nbsp;';   // walang pareho -> blangkong linya pa rin
            // }

            //  ITEMNAME + FYEAR: magkadugtong, fyear laging nasa dulo
            $itemfyear = trim($data->itemname . ' ' . $data->fyear);
            if ($itemfyear == '') {
                $itemfyear = '&nbsp;';
            }

            // PICTURE: default ay IMAGE UNAVAILABLE; papalitan lang ng <img> kapag may file talaga sa folder
            $picture = '<div class="na">IMAGE UNAVAILABLE</div>';
            if (!empty($data->picture)) {
                $src = asset('/public' . ltrim($data->picture));
                if (file_exists(public_path(ltrim($data->picture, '/')))) {
                    $picture = '<img class="im" src="' . $src . '">';
                }
            }

            // CARD - ang ayos ng loob:
            //   1. tag, picture, amt -> naka-absolute, kaya kahit saan isulat, pupunta sa puwesto nila
            //   2. partname -> (barcode) position -> type -> sizeid -> itemname+fyear (2 linya) -> carbrand
            $str .= '<div class="cd">'
                // NEW ITEM tag - lalabas lang kapag new arrival at may file ang tag
                . ($data->isnewarrival ? $tagimg : '')                       /// new - $tagimg (dating URL::to)
                . $picture
                // amount - &#8369; ay ang simbolong ₱
                . '<div class="am">&#8369;' . number_format((float) $data->amt, 2) . '</div>'
                // partname
                . '<div class="pn">' . $partno . '</div>'
                // position
                . '<div class="ln">' . $position . '</div>'
                // type
                . '<div class="ln">' . $type . '</div>'
                // sizeid 
                . '<div class="ln">' . $sizeid . '</div>'
                // /// new - itemname + fyear, laging 2 linya
                . '<div class="ln in2">' . $itemfyear . '</div>'
                // car brand
                . '<div class="bd">' . $carbrand . '</div>'
                . '</div>';
            $count++;
        }

        $str .= $this->reporter->endreport();
        return $str;
    }
   
    private function default_displayHeader($config, $brand = '', $subcat = '', $carbrand = '')
    {
        $str = '';
        $font = 'Arial';
        $big = 'font-family:\'Arial Black\', Arial, sans-serif; font-size:48px; line-height:1; color:#555;';
        $str .= '<br/><br/>';

        // BRAND
        $brandcol = '<span style="position:relative; left:72px; ' . $big . '">' . strtoupper($brand) . '</span>';

        // SUBCATEGORY
        $subcatcol = '<span style="position:relative; display:inline-block; width:384px; height:0;">'
            . '<span style="position:absolute; left:20px; bottom:-10px; white-space:nowrap; font-style:italic; ' . $big . '">' . strtoupper($subcat) . '</span>'
            . '<span style="position:absolute; left:61px; bottom:-48px; white-space:nowrap; line-height:1; font-size:20px;">USE FOR: ' . strtoupper($carbrand) . '</span>'
            . '</span>';

        $str .= $this->reporter->begintable('1044');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brandcol, '645', null, false, '', '', 'LB', $font, '40', 'B', '', '');
        $str .= $this->reporter->col($subcatcol, '384', null, false, '', '', 'LB', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '', '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

    private function default_displayHeader2($config, $brand = '', $subcat = '', $carbrand = '')
    {
        $str = '';
        $font = 'Arial';

        // BRAND: makapal (Arial Black), 48px, gray
        $big = 'font-family:\'Arial Black\', Arial, sans-serif; font-size:48px; line-height:1; color:#555;';
        $ref = 'font-family:Arial, sans-serif; font-weight:bold; font-size:48px; line-height:1; color:#222;';
        $box = 'position:absolute; right:18px; width:476px; text-align:right;';

        $str .= '<br/><br/>';

        // BRAND
        $brandcol = '<span style="position:relative; left:72px; top:36px; ' . $big . '">' . strtoupper($brand) . '</span>';

        $subcatcol = '<span style="position:relative; display:inline-block; width:384px; height:0;">'
            . '<span style="' . $box . ' bottom:-46px; ' . $ref . '">' . strtoupper($subcat) . '</span>'
            . '<span style="' . $box . ' bottom:-96px; line-height:1;">'
            .     '<span style="font-family:Arial; font-weight:bold; font-size:23px;">USE FOR: </span>'
            .     '<span style="' . $ref . '">' . strtoupper($carbrand) . '</span>'
            . '</span>'
            . '</span>';

        $str .= $this->reporter->begintable('1044');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($brandcol, '645', null, false, '', '', 'LB', $font, '40', 'B', '', '');
        $str .= $this->reporter->col($subcatcol, '384', null, false, '', '', 'LB', $font, '13', 'B', '', '');
        $str .= $this->reporter->col('', '15', null, false, '', '', 'L', $font, '13', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }


    public function option_2($config)
    {
        $result = $this->reportDefault($config);
        if (empty($result)) {
            return $this->othersClass->emptydata($config);
        }

        // isang beses lang sinusuri bago ang loop, para mabilis
        $tagpath = '/images/bx/jdatag2.png';
        $tagimg = '';
        if (file_exists(public_path(ltrim($tagpath, '/')))) {
            $tagimg = '<img class="o2tg" src="' . asset('/public' . $tagpath) . '">';
        }

        // ilang item kada page: 2 column x 9 row
        $perpage = 18;
        $rows = 9;            // ilang row kada page (para sa kuwenta ng puwesto ng page number)
        $rowheight = 142;     // taas ng bawat row

        $str = '<style>
            /* GRID (ang buong table ng page) */
            .o2g{
                display:grid;
                grid-template-columns:1fr 1fr;   /* 2 column na magkasing-lapad */
                box-sizing:border-box;
                width:952px;                     /* kapareho ng card sa Catalog 1 */
                margin:62px 0 0 59px;            /* kaliwa 59: kapareho ng card sa Catalog 1 */
                border-top:1px solid #333;       /* mas manipis sa print */
                border-left:1px solid #333;
                font-family:Arial;
            }

            /* CELL (isang item) */
            .o2c{
                position:relative;               /* basehan ng lahat ng laman na naka-absolute */
                box-sizing:border-box;
                height:' . $rowheight . 'px;     /* galing sa $rowheight sa itaas */
                border-right:1px solid #333;    
                border-bottom:1px solid #333;   
            }

            /* PICTURE (aqua) */
            .o2im{
                position:absolute;
                left:10px;                       /* 10px mula sa kaliwa ng cell */
                top:6px;
                width:150px;                    
                height:130px;
                object-fit:contain;              /* buong picture ang kita, hindi nababanat */
                object-position:left center;     /* idinikit sa kaliwa ng box */
            }

            /* PARTNAME (asul) */
            .o2pn{
                position:absolute;
                left:178px;                     
                top:10px;
                font-size:26px;
                font-weight:bold;
            }

            /* ITEMNAME (pula) */
            .o2si{
                position:absolute;
                left:178px;                     
                right:15px;                      /* hanggang 15px bago ang kanang gilid (babalot kapag mahaba) */
                top:46px;
                font-size:22px;
                font-weight:bold;
            }

            /* FYEAR (berde) */
            .o2um{
                position:absolute;
                left:178px;                     
                right:110px;                     /* hindi lalampas sa position sa kanan */
                bottom:8px;
                font-size:22px;
                font-weight:bold;
                white-space:nowrap;              /* isang linya lang */
                overflow:hidden;                 /* puputulin kapag sobrang haba, para hindi tumama sa position */
            }

            /* POSITION (fuchsia) */
            .o2bc{
                position:absolute;
                right:15px;                      /* ibaba-kanan */
                bottom:8px;
                font-size:22px;
                font-weight:bold;
            }

            /* NEW TAG (dilaw) */
            .o2tg{
                position:absolute;
                right:15px;                      /* kanang itaas ng cell */
                top:10px;
                width:75px;                      /* laki ng tag */
            }

            /* PAGE NUMBER sa ibaba ng table (ang margin-top ay kinukuwenta sa ibaba) */
            .o2pg{
                width:952px;                     /* kapareho ng lapad ng table */
                margin-left:59px;                /* kasabay ng table, para nasa gitna pa rin */
                height:0;                        /* walang taas: lumalabas pa rin ang number pero hindi kumukuha ng espasyo */
                overflow:visible;                /* kita pa rin ang number kahit 0 ang taas */
                text-align:center;               /* naka-gitna */
                font-family:Arial;
                font-size:25px;
                font-weight:bold;
            }

            /* IMAGE UNAVAILABLE */
            .o2na{
                position:absolute;
                left:10px;                       
                top:6px;                         
                width:150px;                     
                height:130px;                    
                display:flex;                    /* para mai-gitna ang text */
                align-items:center;              /* gitna pataas-pababa */
                justify-content:center;          /* gitna pakaliwa-pakanan */
                text-align:center;               /* kapag bumalot sa 2 linya, naka-gitna pa rin */
                font-size:14px;                  /* bagay sa 150px na lapad */
                color:#999;                      /* gray */
            }
            </style>';

        $str .= $this->reporter->beginreport('1000');

        $prevkey = null;   // group ng naunang item
        $count = 0;        // ilang item na sa page
        $pageno = 0;       // number ng page

        foreach ($result as $data) {

            // group ng item na ito
            $key = $data->brand_desc . '|' . $data->categoryname . '|' . $data->subcatname . '|' . $data->carbrand;

            // BAGONG GROUP o NAKA-18 NA? -> next page
            if ($key !== $prevkey || $count == $perpage) {

                // isara ang lumang page (kung may lumang page na)
                if ($prevkey !== null) {
                    // isang blank lang kapag odd, para buo ang huling row
                    if ($count % 2 == 1) {
                        $str .= '<div class="o2c"></div>';
                    }
                    $str .= '</div>';   // isara ang table

                    // space = 5px + (kulang na row x taas ng row), para laging nasa iisang puwesto ang page number
                    $space = 5 + ($rows - ceil($count / 2)) * $rowheight;
                    $str .= '<div class="o2pg" style="margin-top:' . $space . 'px;">' . $pageno . '</div>';

                    $str .= $this->reporter->page_break();
                }

                // buksan ang bagong page
                $pageno++;
                $str .= $this->default_displayHeader2($config, $data->brand_desc, $data->subcatname, $data->carbrand);
                $str .= '<div class="o2g">';   // buksan ang table
                $prevkey = $key;
                $count = 0;
            }

            //default ay IMAGE UNAVAILABLE; papalitan lang ng <img> kapag may file talaga sa folder
            $picture = '<div class="o2na">IMAGE UNAVAILABLE</div>';
            if (!empty($data->picture)) {
                $src = asset('/public' . ltrim($data->picture));
                if (file_exists(public_path(ltrim($data->picture, '/')))) {
                    $picture = '<img class="o2im" src="' . $src . '">';
                }
            }

            // ILAGAY ANG ITEM
            $str .= '<div class="o2c">'
                . ($data->isnewarrival ? $tagimg : '')          
                . $picture
                . '<div class="o2pn">' . $data->partno . '</div>'   // partname
                . '<div class="o2si">' . $data->itemname . '</div>'   // itemname
                . '<div class="o2um">' . $data->fyear . '</div>'      // fyear / year model
                . '<div class="o2bc">' . $data->position . '</div>'   // position
                . '</div>';

            $count++;
        }

        // ISARA ANG HULING PAGE
        // isang blangko lang kapag odd
        if ($count % 2 == 1) {
            $str .= '<div class="o2c"></div>';
        }
        $str .= '</div>';   // isara ang table

        // margin-bottom:-60px sa HULING page number lang: para walang sobrang blangkong page sa dulo
        $space = 5 + ($rows - ceil($count / 2)) * $rowheight;
        $str .= '<div class="o2pg" style="margin-top:' . $space . 'px; margin-bottom:-60px;">' . $pageno . '</div>';

        $str .= $this->reporter->endreport();
        return $str;
    }

    // public function reportDefaultLayout_DETAILED($config)
    // {
    //     $result = $this->reportDefault($config);
    //     $count = 8;
    //     $page = 8;
    //     $rowCount = 0;   // bilang ng rows na may 2 products
    //     $itemCount = 0;  // bilang ng valid products na na-display

    //     $str = '';
    //     $layoutsize = '1000';
    //     $font = $this->companysetup->getrptfont($config['params']);
    //     $fontsize = "12";

    //     if (empty($result)) {
    //         return $this->othersClass->emptydata($config);
    //     }

    //     $str .= $this->reporter->beginreport($layoutsize);
    //     $str .= $this->default_displayHeader($config);
    //     $str .= $this->reporter->begintable($layoutsize);

    //     foreach ($result as $key => $data) {

    //         // SKIP KAPAG WALANG LAMAN ANG PRODUCT
    //         // $hasData =
    //         //     !empty($data->itemname) ||
    //         //     !empty($data->picture) ||
    //         //     !empty($data->brand_desc) ||
    //         //     !empty($data->categoryname) ||
    //         //     (isset($data->carbrand) && !empty($data->carbrand)) ||
    //         //     !empty($data->partname);

    //         // if (!$hasData) {
    //         //     continue;
    //         // }

    //         // Ito ang frame ng picture.
    //         // Kung gusto mong palakihin o paliitin ang image box,
    //         // dito mo babaguhin:
    //         // width:175px;
    //         // height:135px;
    //         $picture = '
    //     <div style="width:180px; height:135px; border:1px solid #dcdcdc;background:#ffffff;text-align:center;  line-height:135px;font-size:11px; ">IMAGE UNAVAILABLE </div>';

    //         if (!empty($data->picture)) {
    //             // $src = asset(ltrim($data->picture, '/'));
    //             $src = asset('/public' . ltrim($data->picture));
    //             // ACTUAL IMAGE SETTINGS
    //             // width:165px  = lapad ng actual image
    //             // height:125px = taas ng actual image
    //             // margin-top:5px = konting center spacing sa taas

    //             if (file_exists(public_path(ltrim($data->picture, '/')))) {
    //                 $picture = '
    //                     <div style="width:180px; height:135px; border:1px solid #dcdcdc;background:#ffffff;  text-align:center; ">
    //                         <img src="' . $src . '" style="width:170px; height:125px; object-fit:contain; margin-top:5px;  ">
    //                     </div>';
    //             }
    //         }

    //         // Label = gray
    //         // Value = bold
    //         //
    //         // width:75px = lapad ng label column
    //         // width:8px  = lapad ng colon column
    //         $details  = '<table style="width:100%; border-collapse:collapse; font-size:13px; line-height:1.3;">';

    //         $details .= '
    //     <tr>
    //         <td style="width:100px;color:#666;">Item Brand</td>
    //         <td style="width:8px;">:</td>
    //         <td><b>' . (!empty($data->brand_desc) ? $data->brand_desc : '') . '</b></td>
    //     </tr>';

    //         $details .= '
    //     <tr>
    //         <td style="color:#666;">Item Category</td>
    //         <td>:</td>
    //         <td><b>' . (!empty($data->categoryname) ? $data->categoryname : '') . '</b></td>
    //     </tr>';

    //         $details .= '
    //     <tr>
    //         <td style="color:#666;">Car Brand</td>
    //         <td>:</td>
    //         <td><b>' . (!empty($data->carbrand) ? $data->carbrand : '') . '</b></td>
    //     </tr>';

    //         $details .= '
    //     <tr>
    //         <td style="color:#666;">Part No</td>
    //         <td>:</td>
    //         <td><b>' . (!empty($data->partname) ? $data->partname : '') . '</b></td>
    //     </tr>';


    //         $details .= '
    //     <tr>
    //         <td style="color:#666;">Description</td>
    //         <td>:</td>
    //         <td><b>' . (!empty($data->itemname) ? $data->itemname : '') . '</b></td>
    //     </tr>';

    //         $details .= '</table>';

    //         // PRODUCT CARD
    //         // 2 cards per row ang layout.
    //         //
    //         // width:490px = lapad ng bawat card
    //         // padding:8px = space sa loob ng card
    //         // margin-bottom:8px = pagitan ng bawat product row
    //         //
    //         // Binawasan ang width para magkaroon ng approx 25px margin
    //         // sa kaliwa at kanan ng buong page.
    //         //
    //         $card = '
    //     <div style="width:500px;  border:1px solid #d9d9d9;  background:#fafafa; padding:8px; margin-bottom:8px; "> 
    //         <table style="width:100%;border-collapse:collapse;">
    //             <tr>
    //                 <!-- PICTURE COLUMN -->
    //                 <td style="width:185px;vertical-align:top;">' . $picture . ' </td>

    //                 <!-- SPACE NG PICTURE AT DETAILS -->
    //                 <td style="width:5px;"></td>

    //                 <!-- DETAILS COLUMN -->
    //                 <td style="vertical-align:top;padding-top:12px;"> ' . $details . ' </td>
    //             </tr>
    //         </table>
    //     </div>';

    //         // DISPLAY: 2 PRODUCTS KADA ROW
    //         // Kapag even ang itemCount, ibig sabihin start ng bagong row.
    //         if ($itemCount % 2 == 0) {
    //             $str .= $this->reporter->addline();
    //             $str .= $this->reporter->startrow();
    //         }


    //         $str .= $this->reporter->col($card, '500', null,  false,   '',  '', 'L', $font,  $fontsize,  '', '',  '');

    //         $itemCount++;

    //         // Kapag pangalawang product na sa row, isara ang row.
    //         if ($itemCount % 2 == 0) {
    //             $str .= $this->reporter->endrow();
    //             $rowCount++;

    //             // 8 rows bawat page.
    //             // Dahil 2 products per row, 16 products per page.
    //             if ($rowCount == $page) {
    //                 $str .= $this->reporter->endtable();
    //                 $str .= $this->reporter->page_break();
    //                 $str .= $this->default_displayHeader($config);
    //                 $str .= $this->reporter->begintable($layoutsize);

    //                 $page += $count;
    //             }
    //         }
    //     }

    //     // ODD NUMBER OF PRODUCTS
    //     // Kapag odd ang valid products, halimbawa 15,
    //     // kailangan lagyan ng blank column sa kanan
    //     // para hindi masira ang last row.
    //     if ($itemCount % 2 == 1) {
    //         $str .= $this->reporter->col('',  '475',  null,  false,  '', '', 'L', $font, $fontsize,  '',  '', '');

    //         $str .= $this->reporter->endrow();
    //     }

    //     $str .= $this->reporter->endtable();
    //     $str .= $this->reporter->endreport();

    //     return $str;
    // }

        private function diplay_header_option3($config, $items, $category)
    {
        $carbrand     = $config['params']['dataparams']['carbrand'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        $str = '';
        $layoutsize = '1000';
        $font = 'Arial';
        $fontsize = "28";
        $border = "1px solid ";
        $brands = "";

        if(!empty($carbrand)){
            $brands = $carbrand;
        }else{
          // car brands ng mga item na nasa page lang na ito
          $brands = collect($items)->pluck('carbrand')->filter(function ($value) {
              return $value != '';
          })->unique()->implode(', ');
        }
        if(!empty($categoryname)){
          $category = $categoryname;
        }else{
            if(empty($category)){
               $category = "</br>";
            }
        }        
//category
        $str .= '<div style="position:relative;">';
        $str .= '<div style="position:absolute;margin: 15px 0px 0px 0px;">';
        $str .= $this->reporter->begintable('667');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($category,  null, null, false,  $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';
        $str .= '<div style="position:absolute;margin: 20px 0px 0px 667px;">';
        $str .= $this->reporter->begintable('333');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('USE FOR: '. $brands, null, null, false, false,'', 'C', $font, 12, 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';
        $str .= '</div>';
        return $str;
    }
    function option_3($config){
     $result = $this->reportDefault($config);
     if (empty($result)) {
         return $this->othersClass->emptydata($config);
     }
     $font = 'Arial';
     $str = $this->reporter->beginreport('1000', null, false,  false, '', '', '', '', '', '', '', '2px 130px 0px');

     // naka-order by category na ang query: hatiin per category, tapos tig-24 per page
     $pages = collect($result)->groupBy('categoryname')->flatMap(function ($group) {
         return $group->chunk(24)->map(function ($chunk) {
             return $chunk->values();
         });
     })->values();
     $totalpages = $pages->count();

     foreach ($pages as $p => $items) {
        $pageno = $p + 1;
        $islast = $pageno == $totalpages;

        $str .= '<div style="height:100px; overflow:hidden; box-sizing:border-box;">'
              . $this->diplay_header_option3($config, $items, $items->first()->categoryname)
              . '</div>';
        $str .= '<div style="display:grid;
                      grid-template-columns:repeat(3, minmax(0, 1fr));
                      width:100%;
                      gap:0;
                      margin:0;
                      padding:0;
                      box-sizing:border-box;
                      font-family:'.$font.';">';

        $str .= $items->map(function ($data, $k) {
            return $this->card_option3($data, $k + 1);
        })->implode('');

        // page number: last page naka-absolute sa loob ng grid
        $str .= $islast ? '<div style="position:absolute;margin: 1364px 0px;">' : '</div>';
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($pageno, null, null, false, false,'', 'C', $font, '18', 'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $islast ? '</div></div>' : $this->reporter->page_break();
     }
     $str .= $this->reporter->endreport();
     return $str;
    }

    private function card_option3($data, $i)
    {
        $borderTop = ($i > 3) ? '0' : '1px';
        $borderLeft = (($i - 1) % 3 != 0) ? '0' : '1px';

        $picture = file_exists(public_path(ltrim($data->picture,'/')))
                ? '<img src="' . asset('/public'.ltrim($data->picture)) . '" style="width:100%; height:135px; object-fit:contain; display:block;">'
                : '<div style="width:100%;height:135px; display:flex; align-items:center; justify-content:center; font-size:12px;">IMAGE UNAVAILABLE</div>';
        $itemaname = !empty($data->itemname) ? $data->itemname : '';
        $partno = !empty($data->partno) ? $data->partno : '</br>';
        $brand = !empty($data->carbrand) ? '<div style = "padding:3px 0px;font-weight:bold;"> '.$data->carbrand.' </div>' : '</br>';
        $size = !empty($data->sizeid) ? '<div style = "padding:3px 0px;"> '.$data->sizeid.' </div>' : '</br>';
        $postion = !empty($data->position) ? '<div style = "padding:3px 0px;"> '.$data->position.' </div>' : '</br>';

        return '
            <div style="
                width:100%;
                min-width:0;
                height:170px;
                box-sizing:border-box;
                border-top:'.$borderTop.' solid #111;
                border-left:'.$borderLeft.' solid #111;
                border-right:1px solid #111;
                border-bottom:1px solid #111;
                margin:0;
                padding:0;
                display:flex;
                flex-direction:column;
                background:#fff;
                page-break-inside:avoid;
                break-inside:avoid;
            ">

                <!-- PARTNO -->
                <div style="
                height:25px;
                min-height:25px;
                    box-sizing:border-box;
                    padding:5px 6px;
                    font-size:20px;
                    font-weight:bold;
                    line-height:1.15;
                    overflow:hidden;
                ">
                    '.$partno.'
                </div>
                <!-- IMAGE + SMALL DESCRIPTION -->
                <div style="
                    flex:1;
                    min-height:0;
                    display:flex;
                    flex-direction:row;
                    box-sizing:border-box;
                    overflow:hidden;
                ">

                    <!-- IMAGE: 70% -->
                    <div style="
                        width:60%;
                        height:100%;
                        box-sizing:border-box;
                        padding:3px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                    ">
                    '.$picture.'
                    </div>

                    <!-- DESCRIPTION: 30% -->
                    <div style="
                        width:40%;
                        height:100%;
                        box-sizing:border-box;
                        padding:3px;
                        font-size:20px;
                        line-height:1.2;
                        overflow:hidden;
                        overflow-wrap:break-word;
                        text-align:center;
                    ">
                        '.$brand.'
                        '.$itemaname.'
                        '.$size.'
                        '.$postion.'
                    </div>

                </div>
            </div>';
    }

    private function diplay_header_option4($config, $category = '', $carbrand = '')
    {
        $brandname    = $config['params']['dataparams']['carbrand'];
        $categoryname = $config['params']['dataparams']['categoryname'];
        // kapag walang pinili sa filter, category at car brand ng page ang gamitin
        if(empty($brandname)){
            $brandname = $carbrand;
        }
        if(empty($categoryname)){
            $categoryname = $category;
        }
        $str = '';
        $layoutsize = '1000';
        $font = 'Arial';
        $fontsize = "28";
        $border = "1px solid ";

        // ibaba nang konti ang header para pasok sa printable area; relative lang para hindi gumalaw ang items at pageno
        $str .= '<div style="position:relative; top:8px;">';
        $str .= $this->reporter->begintable('970');
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col(' ' . $categoryname, '650', null, false,  $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(' ' . $brandname, '320', null, false,  $border, '', 'R', $font, $fontsize, 'B', '', '');


        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '</div>';
        return $str;
    }

    function option_4($config){
     $result = $this->reportDefault($config);
     if (empty($result)) {
         return $this->othersClass->emptydata($config);
     }

     $str = '';
     $layoutsize = '1000';
     $font = 'Arial';
     $fontsize = "10";
     $border = "1px solid ";
     $pageno = 1;


     //  $str .= $this->reporter->beginreport($layoutsize);
     $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '5px 130px 0px');

     // naka-order by category, car brand na ang query: hatiin per category + car brand, tapos tig-12 per page
     $pages = collect($result)->groupBy(function ($row) {
         return $row->categoryname . '|' . $row->carbrand;
     })->flatMap(function ($group) {
         return $group->chunk(12)->map(function ($chunk) {
             return $chunk->values();
         });
     })->values();
     $totalpages = $pages->count();

     foreach ($pages as $p => $items) {
        $first = $items->first();
        $str .= $this->diplay_header_option4($config, $first->categoryname, $first->carbrand);
        // fixed height ang grid para pare-pareho ang taas ng bawat page kahit kaunti ang items (para sa pageno)
        $str .= '<div style="position:relative; height:1420px; align-content:start; display:grid; grid-template-columns:repeat(3, 300px);column-gap:20px;
     row-gap:27px; justify-content:center; margin-top:8px;font-family:'.$font.' ">';

        foreach ($items as $data) {

            $partno = '';
            $displaypos = '';
            if(!empty($data->partno)){
             $partno = $data->partno;
            }

            $getpicute = ltrim($data->picture);
            $picture =  file_exists(public_path(ltrim($data->picture,'/')))
                ? '<img src="' . asset('/public'.$getpicute) . '" style="width:100%; height:190px; object-fit:contain; display:block;">'
                : '<div style="height:190px; display:flex; align-items:center; justify-content:center; font-size:12px;">IMAGE UNAVAILABLE</div>';


            $position = "";
            if(!empty($data->position)){
             $position = $data->position;
            }

            if(!empty($data->sizeid)){
                if($position != ""){
                   $position = $data->position . '/' . $data->sizeid;
                }
            }
            $displaypos = !empty($partno) || !empty($position)
                ? '<div style="flex:1;font-weight:bold;">' . $partno . '</div>
                   <div style="flex:1;font-weight:bold;text-align:right;">' . $position . '</div>'
                : '<br>';

            $itemaname = !empty($data->itemname) ? $data->itemname .' ' . $data->fyear : '';

            $str .= '
            <div style="position:relative; box-sizing:border-box; width:300px; height:330px; border:1px solid #111; background:#fff; page-break-inside:avoid; break-inside:avoid;">

                <div style="padding:12px 5px 0; box-sizing:border-box;">
                    ' . $picture . '
                </div>

                <div style="display:flex; padding:0 5px 0; box-sizing:border-box;font-weight:bold;font-size:20px;line-height:1.2;">
                    '.$displaypos.'
                </div>

                <div style="height:60px; padding:0 5px 0; overflow-wrap:anywhere; box-sizing:border-box;font-size:18px;line-height:1.2;">
                    '.$itemaname.'
                </div>
                <div style="position:absolute;bottom:10px;left:50%;transform:translateX(-50%);">
                 
                      <span style="font-size:19px; line-height:1;">

               </span>
                </div>



              <div style="position:absolute;bottom:-11px;left:50%;transform:translateX(-50%);width:150px;height:25px;border:1px solid #111;background:#fff;display:flex;align-items:center;justify-content:center;box-sizing:border-box;">
               <span style="font-weight:bold; font-size:21px; line-height:1;">
                ₱'.number_format($data->amt,2).'
               </span>
              </div>

            </div>';
            }

            // page number: naka-absolute sa loob ng grid sa lahat ng page, para laging nasa baba kahit ilan ang laman
            $str .= '<div style="position:absolute;top:1420px;left:0;">';
            $str .= $this->reporter->begintable('1000');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col($pageno, null, null, false, false,'', 'C', $font, '18', 'B', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $str .= '</div>';
            $str .= '</div>';

            if ($pageno < $totalpages) {
                $str .= $this->reporter->page_break();
                $pageno++;
            }
     }
     $str .= $this->reporter->endreport();
     return $str;
   }

    // end class is below
}//end class