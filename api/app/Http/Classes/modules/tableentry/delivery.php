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


class delivery
{
    private $fieldClass;
    private $tabClass;
    public $modulename = 'Delivery Details';
    public $gridname = 'inventory';
    private $companysetup;
    private $coreFunctions;
    private $logger;
    public $tablelogs_del = 'del_table_log';
    private $table = '';
    private $othersClass;
    public $style = 'width:100%;max-width:70%;';
    private $fields = [];
    public $showclosebtn = true;


    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->companysetup = new companysetup;
        $this->coreFunctions = new coreFunctions;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 0);
        return $attrib;
    }

    public function createTab($config)
    {
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");

        $column = ['docno', 'clientname', 'addr', 'agentname', 'terms', 'tonnage', 'ext', 'distance', 'diesel', 'iseq'];
        $sortcolumn = ['docno', 'clientname', 'addr', 'agentname', 'terms', 'tonnage', 'ext', 'distance', 'diesel', 'iseq'];
        foreach ($column as $key => $value) {
            $$value = $key;
        }

        foreach ($sortcolumn as $key => $value) {
            $$value = $key;
        }

        $tab = [$this->gridname => ['gridcolumns' => $column, 'sortcolumns' => $sortcolumn]];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";
        $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';


        $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Customer Name';
        $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
        $obj[0][$this->gridname]['columns'][$clientname]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';



        $obj[0][$this->gridname]['columns'][$addr]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
        $obj[0][$this->gridname]['columns'][$addr]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$addr]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$terms]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$terms]['type'] = 'label';


        $obj[0][$this->gridname]['columns'][$tonnage]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$tonnage]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$tonnage]['style'] = "text:center; text-align:right; width:100px;whiteSpace: normal;min-width:100px;";
        $obj[0][$this->gridname]['columns'][$ext]['style'] = "text:center; text-align:right; width:100px;whiteSpace: normal;min-width:100px;";


        if ($isposted) {
            $obj[0][$this->gridname]['columns'][$iseq]['readonly'] = true;
            $obj[0][$this->gridname]['columns'][$iseq]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$iseq]['style'] = "text-align:center; width:100px;whiteSpace: normal;min-width:100px;";
            $obj[0][$this->gridname]['columns'][$distance]['readonly'] = true;
            $obj[0][$this->gridname]['columns'][$distance]['type'] = 'label';
            $obj[0][$this->gridname]['columns'][$distance]['style'] = "text:center; text-align:right; width:100px;whiteSpace: normal;min-width:100px;";
            $obj[0][$this->gridname]['columns'][$diesel]['readonly'] = true;
            $obj[0][$this->gridname]['columns'][$diesel]['style'] = "text:center; text-align:right; width:100px;whiteSpace: normal;min-width:100px;";
            $obj[0][$this->gridname]['columns'][$diesel]['type'] = 'label';
        } else {
            $obj[0][$this->gridname]['columns'][$iseq]['style'] = "text:center; width:100px;whiteSpace: normal;min-width:100px;";
            $obj[0][$this->gridname]['columns'][$distance]['style'] = "text:center; width:100px;whiteSpace: normal;min-width:100px;";
            $obj[0][$this->gridname]['columns'][$diesel]['style'] = "text:center; width:100px;whiteSpace: normal;min-width:100px;";
            $obj[0][$this->gridname]['columns'][$iseq]['style'] = "text-align:right; width:100px;whiteSpace: normal;min-width:100px;";
        }


        return $obj;
    }

    public function createtabbutton($config)
    {
        $trno = $config['params']['tableid'];
        $isposted = $this->othersClass->isposted2($trno, "transnum");
        if ($isposted) {
            $tbuttons = [];
        } else {
            $tbuttons = ['saveallentry'];
        }
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }


    private function selectqry($config)
    {
        $trno = $config['params']['tableid'];
        //  (select format(sum(stock.weight * stock.isqty),2) as totalestweight from hsostock as stock where stock.trno=so.trno and ) as tonnage,
        $sql = "select so.docno,cl.clientname,cl.addr,agent.clientname as agentname,so.terms,
              
                ifnull((select format(sum(totalestweight),2) as totalestweight from
                      (select sum(sostock.weight * sostock.isqty) as totalestweight, sostock.trno, ro.trno as rotrno
                       from hrostock as ro
                       left join hsostock as sostock on sostock.trno=ro.refx and sostock.line=ro.linex 
                       group by sostock.trno,ro.trno
                   union all
                    select sum(sostock.weight * sostock.isqty) as totalestweight, sostock.trno, ro.trno as rotrno
                        from rostock as ro
                        left join hsostock as sostock on sostock.trno=ro.refx and sostock.line=ro.linex 
                        group by sostock.trno,ro.trno)as z  where trno=so.trno and rotrno=roso.trno),0) as tonnage,

                roso.distance,roso.diesel,roso.iseq,roso.sotrno,roso.trno,'' as bgcolor, 
                  
                (select format(sum(ext),2) as ext from
                      (select sum(sostock.ext) as ext, sostock.trno, ro.trno as rotrno
                       from hrostock as ro
                       left join hsostock as sostock on sostock.trno=ro.refx and sostock.line=ro.linex
                       group by sostock.trno,ro.trno
                   union all
                    select sum(sostock.ext) as ext, sostock.trno, ro.trno as rotrno
                        from rostock as ro
                        left join hsostock as sostock on sostock.trno=ro.refx and sostock.line=ro.linex
                        group by sostock.trno,ro.trno)as z  where trno=so.trno and rotrno=roso.trno) as ext

                from roso as roso
                left join hsohead as so on so.trno =roso.sotrno
                left join client as cl on cl.client=so.client
                left join client as agent on agent.client=so.agent
                where roso.trno= $trno
                order by roso.iseq";
        return $sql;
    }

    public function loaddata($config)
    {
        $qry = $this->selectqry($config);
        $data = $this->coreFunctions->opentable($qry, [$config['params']['tableid'], $config['params']['tableid']]);
        return $data;
    }




    public function saveallentry($config)
    {
        $table = 'roso';
        foreach ($config['params']['data'] as $key => $value) {
            if ($value['bgcolor'] != '') {
                $update = [
                    'iseq' => $value['iseq'],
                    'distance' => $value['distance'],
                    'diesel' => $value['diesel']
                ];
                $this->coreFunctions->sbcupdate($table, $update, ['trno' => $value['trno'], 'sotrno' => $value['sotrno']]);
            }
        }

        $data = $this->loaddata($config);
        return ['status' => true, 'msg' => 'Data was refresh.', 'data' => $data];
    }
} //end class
