<?php

namespace App\Http\Classes\modules\modulereport\mighty;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use BcMath\Number;
use Illuminate\Support\Facades\URL;

class mr
{

    private $modulename;
    private $fieldClass;
    private $companysetup;
    private $coreFunctions;
    private $othersClass;
    private $logger;
    private $reporter;
    public $style = 'width:1200px;max-width:1200px;';
    public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'refresh'];
        $col1 = $this->fieldClass->create($fields);
        return array('col1' => $col1);
    }

    public function reportparamsdata($config)
    {
        return $this->coreFunctions->opentable(
            "select 
      'default' as print,
      '0' as reporttype,
      '' as prepared,
      '' as approved,
      '' as received
      "
        );
    }

    public function report_default_query($trno)
    {
        $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
    item.sizeid, ag.clientname as agname, item.brand, project.name as project,
    wh.client as whcode, wh.clientname as whname 
    from mrhead as head
    left join mrstock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
    left join projectmasterfile as project on project.line=head.projectid 
    where head.doc='MR' and head.trno=$trno
    UNION ALL
    select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
    item.sizeid, ag.clientname as agname, item.brand, project.name as project,
    wh.client as whcode, wh.clientname as whname 
    from hmrhead as head
    left join hmrstock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
    left join projectmasterfile as project on project.line=head.projectid 
    where head.doc='MR' and head.trno=$trno order by line";

        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
        return $result;
    } //end fn


    public function reportplotting($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $decimal = $this->companysetup->getdecimal('currency', $params['params']);

        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = '';
        $count = 35;
        $page = 35;
        $font = "Century Gothic";
        $fontsize = "11";
        $border = "1px solid ";
        $layoutsize = '720';

        $str .= $this->reporter->beginreport($layoutsize, null, false,  false, '', '', '', '', '', '', '', '50px;margin-top:5px;');
        $str .= $this->report_default_header($params, $data);
        $gtotal = 0;
        $totalext = 0;
        for ($i = 0; $i < count($data); $i++) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();

            $str .= $this->reporter->col($data[$i]['barcode'], '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['qty'], 2), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col($data[$i]['itemname'], '270', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['amt'], 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->col(number_format($data[$i]['ext'], 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            if ($this->reporter->linecounter == $page) {
                $str .= $this->reporter->page_break();
                $str .= $this->report_default_header($params, $data);
                $str .= $this->reporter->printline();
                $page = $page + $count;
            } //end if
            $gtotal += $data[$i]['ext'];
        } //end for 

        $str .= $this->reporter->printline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col('GRAND TOTAL: ', '170', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '2px');
        $str .= $this->reporter->col(number_format($gtotal, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();



        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/><br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
        $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->endreport();
        return $str;
    }

    private function report_default_header($params, $data)
    {
        $companyid = $params['params']['companyid'];
        $center = $params['params']['center'];
        $username = $params['params']['user'];

        $str = '';
        $font = "Century Gothic";
        $fontsize = "9";
        $border = "1px solid ";
        $layoutsize = '720';

        $str .= $this->reporter->begintable($layoutsize);
        // $str .= $this->reporter->startrow();
        $str .= $this->reporter->letterhead($center, $username, $params) . '<br/>';
        // $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        // $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->modulename, null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '520', null, false, $border, '', 'L', $font, '18', 'B', '', '');
        $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('NAME/ASSET: ', '90', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '4px');
        $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '460', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('DATE : ', '70', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '100', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        $str .= $this->reporter->col('WAREHOUSE : ', '90', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '4px');
        $str .= $this->reporter->col((isset($data[0]['whname']) ? $data[0]['whname'] : ''), '460', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('YOURREF : ', '70', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '100', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PROJECT : ', '90', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '4px');
        $str .= $this->reporter->col((isset($data[0]['project']) ? $data[0]['project'] : ''), '460', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '4px');
        $str .= $this->reporter->col('OURREF : ', '70', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col((isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '100', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('BARCODE', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->col('QTY', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->col('DESCRPTION', '270', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        return $str;
    }
}
