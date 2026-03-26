<?php

namespace App\Http\Classes\modules\tableentry;

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
use Carbon\Carbon;

class viewsobreakdown
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'SO BREAKDOWN';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $table = '';
    private $othersClass;
    public $style = 'width:100%;max-width:30%;';
    private $fields = [];
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $sono = 0;
        $rtno = 1;
        $qty = 2;
        $tab = [$this->gridname => ['gridcolumns' => ['sono', 'rtno', 'qty']]];


        $stockbuttons = ['stockinfo'];
        if ($this->companysetup->getisiteminfo($config['params'])) {
            $stockbuttons = [];
            array_push($stockbuttons, 'iteminfo');
        }
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$qty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

        $obj[0][$this->gridname]['columns'][$sono]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$rtno]['type'] = "label";
        $obj[0][$this->gridname]['columns'][$qty]['type'] = "label";

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    private function selectqry($config)
    {
        switch ($config['params']['doc']) {
            case 'CV':
                $sql = "select osi.trno, osi.line, osi.soline, osi.sono, osi.rtno, osi.qty, hcv.trno
                from hcvitems as hcv 
                left join (
                    select om.reqtrno, om.reqline, so.trno, so.line, so.soline, so.sono, so.rtno, so.qty
                    from omstock as om
                    left join omhead as omh on omh.trno = om.trno
                    left join omso as so on so.trno = om.trno and so.line = om.line
                    union all
                    select om.reqtrno, om.reqline, so.trno, so.line, so.soline, so.sono, so.rtno, so.qty
                    from homstock as om
                    left join homhead as omh on omh.trno = om.trno
                    left join homso as so on so.trno = om.trno and so.line = om.line
                ) as osi on osi.reqtrno = hcv.reqtrno and osi.reqline = hcv.reqline
                where osi.trno <> 0 and hcv.trno = ?";
                break;
            default:
                switch ($config['params']['doc']) {
                    case 'PO':
                        $head = "hpohead";
                        $stock = "hpostock";
                        break;
                    default:
                        $head = "glhead";
                        $stock = "glstock";
                        break;
                }

                $sql = "select trno,line,soline,sono,rtno,qty,'' as bgcolor,pono,potrno from (
                select so.trno, so.line, so.soline, so.sono, so.rtno, so.qty,
                    (select group_concat(distinct yourref separator ', ')
                        from (select yourref,pos.reqtrno,pos.reqline
                            from $head as po
                            left join $stock as pos on pos.trno=po.trno where pos.void=0) as k
                        where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as pono,
                    (select trno
                        from (select pos.trno,pos.reqtrno,pos.reqline
                            from $head as po
                            left join $stock as pos on pos.trno=po.trno where pos.void=0) as k
                        where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as potrno
                from omso as so
                left join omstock as stock on stock.trno=so.trno and so.line=stock.line
                ) as k
                where potrno= ?
                order by soline";
                break;
        }

        return $sql;
    }

    public function loaddata($config)
    {
        $qry = $this->selectqry($config);
        $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid']]);
        return $data;
    }
} //end class
