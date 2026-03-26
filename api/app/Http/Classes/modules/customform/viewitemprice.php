<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewitemprice
{
    private $fieldClass;
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;
    private $warehousinglookup;

    public $modulename = 'Item Price';
    public $gridname = 'tableentry';
    private $fields = [
        'amt',
        'amt2',
        'famt',
        'amt4',
        'amt5',
        'amt6',
        'amt7',
        'amt8',
        'amt9',
        'disc',
        'disc2',
        'disc3',
        'disc4',
        'disc5',
        'disc6',
        'disc7',
        'disc8',
        'disc9',
        'markup',
        'insurance',
        'delcharge',
        'namt',
        'namt2',
        'nfamt',
        'namt4',
        'namt5',
        'namt6',
        'namt7'
    ];
    private $table = 'item';

    public $tablelogs = 'item_log';
    public $tablelogs_del = 'del_item_log';

    public $style = 'width:100%;max-width:70%;';
    public $issearchshow = true;
    public $showclosebtn = true;

    public function __construct()
    {
        $this->fieldClass = new txtfieldClass;
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
        $this->logger = new Logger;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 12, 'save' => 15);
        return $attrib;
    }

    public function createHeadField($config)
    {
        $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4861);
        if (isset($config['params']['clientid'])) {
            if ($config['params']['clientid'] != 0) {
                $itemid = $config['params']['clientid'];
                $item = $this->othersClass->getitemname($itemid);
                $prevAndLatestCost = '';
                if ($config['params']['companyid'] == 17) { //unihome
                    $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'itemid=?', [$itemid]);
                    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);


                    if ($viewcost) {

                        $qry = "select rrstatus.cost as latest,rrstatus.dateid,cntnum.doc from rrstatus
                        left join cntnum on cntnum.trno=rrstatus.trno
                        where rrstatus.itemid=" . $itemid . " and rrstatus.uom='" . $uom . "'
                        and cntnum.doc='RR'
                        order by rrstatus.dateid desc
                        limit 1";

                        $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
                        if (!empty($result)) {
                            $cost = $result[0]['latest'];
                            $prevAndLatestCost .= " - - - Last Cost: " . number_format($cost, 6);
                        }

                        $qry2 = "select rrstatus.cost,rrstatus.dateid,cntnum.doc from rrstatus
                        left join cntnum on cntnum.trno=rrstatus.trno
                        where rrstatus.itemid=" . $itemid . " and rrstatus.uom='" . $uom . "'
                        and cntnum.doc='RR'
                        group by rrstatus.cost, rrstatus.dateid,cntnum.doc
                        order by rrstatus.dateid desc
                        limit 2";

                        $result2 = json_decode(json_encode($this->coreFunctions->opentable($qry2)), true);
                        if (!empty($result2)) {
                            if (count($result2) == 2) {
                                $prevcost = $result2[1]['cost'];
                                $prevAndLatestCost .= " - - - Previous: " . number_format($prevcost, 6);
                            } else {
                                $prevAndLatestCost .= "";
                            }
                        }
                    }
                }
                $this->modulename = 'ITEM PRICE - ' . $item[0]->barcode . ' - - - ' . $item[0]->itemname . $prevAndLatestCost;
            } else {
                return [];
            }
        } else {
            return [];
        }

        switch ($config['params']['companyid']) {
            case 10:
            case 12: //for aftech
                $fields = ['amt', 'amt2', 'famt', 'amt4', 'refresh'];
                $col1 = $this->fieldClass->create($fields);
                data_set($col1, 'amt.readonly', false);
                data_set($col1, 'amt2.readonly', false);
                data_set($col1, 'famt.readonly', false);
                data_set($col1, 'amt4.readonly', false);
                data_set($col1, 'amt.label', 'PHP Amount');
                data_set($col1, 'amt2.label', 'DOLLAR Amount');
                if ($config['params']['companyid'] == 12) { //afti usd
                    data_set($col1, 'amt.label', 'Dollar Amount');
                    data_set($col1, 'amt2.label', 'PHP Amount');
                }
                data_set($col1, 'famt.label', 'TP dollar');
                data_set($col1, 'amt4.label', 'TP Peso');
                data_set($col1, 'refresh.label', 'Save');
                $fields = ['disc', 'disc2', 'disc3', 'insurance', 'delcharge'];
                $col2 = $this->fieldClass->create($fields);
                data_set($col2, 'disc.readonly', false);
                data_set($col2, 'disc2.readonly', false);
                data_set($col2, 'disc3.readonly', false);
                data_set($col2, 'insurance.readonly', false);
                data_set($col2, 'disc.label', 'DISCOUNT - PHP Amount');
                data_set($col2, 'disc2.label', 'DISCOUNT - DOLLAR Amount');
                data_set($col2, 'disc3.label', 'DISCOUNT - TP dollar');
                data_set($col2, 'delcharge.label', 'Local Delivery Charge');

                return array('col1' => $col1, 'col2' => $col2);
                break;
            case 60: //transpower
                $allowview = $this->othersClass->checkAccess($config['params']['user'], 5488);
                $fields = [['amt5', 'disc5', 'namt5'],['amt7', 'disc7', 'namt7'],['amt2', 'disc2', 'namt2'],['amt', 'disc', 'namt'],  ['famt', 'disc3', 'nfamt'], ['amt6', 'disc6', 'namt6'],['amt4', 'disc4', 'namt4'], 
                'refresh'];
                if(!$allowview){
                    $fields = [['amt5', 'disc5', 'namt5'],['amt7', 'disc7', 'namt7'],['amt2', 'disc2', 'namt2'],['amt', 'disc', 'namt'], 
                    'refresh'];
                }
                
                $col1 = $this->fieldClass->create($fields); 
                data_set($col1, 'amt.readonly', false);
                data_set($col1, 'amt.label', 'Base Price');
                data_set($col1, 'disc.readonly', false);
                data_set($col1, 'disc.label', 'Base Discount');
                data_set($col1, 'amt2.readonly', false);
                data_set($col1, 'amt2.label', 'Wholesale Price');
                data_set($col1, 'disc2.readonly', false);
                data_set($col1, 'disc2.label', 'Wholesale Discount');
                data_set($col1, 'namt2.label', 'Net Wholesale');
                data_set($col1, 'famt.readonly', false);
                data_set($col1, 'famt.label', 'Distributor');
                data_set($col1, 'disc3.readonly', false);
                data_set($col1, 'disc3.label', 'Distributor Discount');
                data_set($col1, 'nfamt.label', 'Net Distributor');
                data_set($col1, 'amt4.readonly', false);
                data_set($col1, 'amt4.label', 'Cost');
                data_set($col1, 'disc4.readonly', false);
                data_set($col1, 'disc4.label', 'Cost Discount');
                data_set($col1, 'namt4.label', 'Net Cost');
                data_set($col1, 'amt5.readonly', false);
                data_set($col1, 'amt5.label', 'Invoice Price');
                data_set($col1, 'disc5.readonly', false);
                data_set($col1, 'disc5.label', 'Invoice Discount');
                data_set($col1, 'namt5.label', 'Net Invoice');

                data_set($col1, 'amt6.readonly', false);
                data_set($col1, 'amt6.label', 'Lowest Price');
                data_set($col1, 'disc6.readonly', false);
                data_set($col1, 'disc6.label', 'Lowest Discount');
                data_set($col1, 'namt6.label', 'Net Lowest');
                data_set($col1, 'amt7.readonly', false);
                data_set($col1, 'amt7.label', 'DR Price');
    
                data_set($col1, 'disc7.readonly', false);
                data_set($col1, 'disc7.label', 'DR Discount');
                data_set($col1, 'namt7.label', 'Net DR');
                data_set($col1, 'refresh.label', 'Save');
                return ['col1' => $col1];
                break;
            default:
                //1st col
                switch ($config['params']['companyid']) {
                    case 28: //xcomp
                        $fields = ['amt', 'famt', 'amt2', 'amt4', 'amt5', 'refresh'];
                        break;
                    case 21: // kinggeorge
                        $fields = ['amt', 'famt', 'amt2', 'amt4', 'amt5'];
                        if ($allow_update) {
                            array_push($fields, 'refresh');
                        }
                        break;
                    default:
                        $fields = ['amt', 'amt2', 'famt', 'amt4', 'amt5', 'refresh'];
                        break;
                }
                $col1 = $this->fieldClass->create($fields);
                data_set($col1, 'amt.readonly', false);
                data_set($col1, 'amt2.readonly', false);
                data_set($col1, 'famt.readonly', false);
                data_set($col1, 'amt4.readonly', false);
                data_set($col1, 'amt5.readonly', false);
                data_set($col1, 'refresh.label', 'Save');
                data_set($col1, 'refresh.isclose', true);

                if ($config['params']['companyid'] == 19) { //housegem
                    data_set($col1, 'famt.label', 'RJC (A)');
                    data_set($col1, 'amt4.label', 'Freelance (B)');
                    data_set($col1, 'amt5.label', 'Chinese Agent (C)');

                    data_set($col3, 'disc3.label', 'RJC Disc (A)');
                    data_set($col3, 'disc4.label', 'Freelance Disc (B)');
                    data_set($col3, 'disc5.label', 'Chinese Agent Disc (C)');
                }

                if ($config['params']['companyid'] == 28) { //xcomp
                    data_set($col1, 'amt.label', 'SI Regular Price(R)');
                    data_set($col1, 'amt2.label', 'DR Cash Price (W)');
                    data_set($col1, 'famt.label', 'SI Cash Price (A)');
                }

                if ($config['params']['companyid'] == 39) { //cbbsi
                    data_set($col1, 'famt.label', 'Outlet Price');
                }

                if ($config['params']['companyid'] == 59) { //roosevelt
                    data_set($col1, 'amt.label', 'Dealer Price');
                    data_set($col1, 'amt2.label', 'Dealer 2 Price');
                    data_set($col1, 'famt.label', 'Industrial Price');
                    data_set($col1, 'amt4.label', 'Walk-In Price');
                }

                // 2nd col
                if ($config['params']['companyid'] == 28) { //xcomp
                    $fields = ['disc', 'disc3', 'disc2', 'disc4', 'disc5'];
                } else {
                    $fields = ['disc', 'disc2', 'disc3', 'disc4', 'disc5'];
                }

                $col2 = $this->fieldClass->create($fields);
                data_set($col2, 'disc.readonly', false);
                data_set($col2, 'disc2.readonly', false);
                data_set($col2, 'disc3.readonly', false);
                data_set($col2, 'disc4.readonly', false);
                data_set($col2, 'disc5.readonly', false);

                if ($config['params']['companyid'] == 19) { //housegem
                    data_set($col2, 'disc3.label', 'Disc RJC (A)');
                    data_set($col2, 'disc4.label', 'Freelance Disc (B)');
                    data_set($col2, 'disc5.label', 'Chinese Agent Disc (C)');
                }

                // 3rd col
                $fields = ['amt6', 'amt7', 'amt8', 'amt9'];
                $col3 = $this->fieldClass->create($fields);
                data_set($col3, 'amt6.readonly', false);
                data_set($col3, 'amt7.readonly', false);
                data_set($col3, 'amt8.readonly', false);
                data_set($col3, 'amt9.readonly', false);

                if ($config['params']['companyid'] == 19) { //housegem 
                    data_set($col3, 'amt6.label', 'TSC (D)');
                    data_set($col3, 'amt7.label', 'Retail Price COD (E)');
                    data_set($col3, 'amt8.label', 'Retail Price 30 Days (F)');
                    data_set($col3, 'amt9.label', 'Retail Price 90 Days (G)');
                }

                if ($config['params']['companyid'] == 1) { //vitaline
                    data_set($col3, 'amt9.label', 'Unit Price In USD');
                }

                if ($config['params']['companyid'] == 39) { //cbbsi
                    data_set($col3, 'amt7.label', 'Max Discount');
                    data_set($col3, 'amt8.label', 'Prev Landed Cost');
                    data_set($col3, 'amt9.label', 'Last Landed Cost');
                }

                $fields = ['disc6', 'disc7', 'disc8', 'disc9']; //, 'uploadexcel', 'exportcsv','readfile'

                if ($config['params']['companyid'] == 6) { //mitsukoshi
                    array_push($fields, 'markup');
                }
                $col4 = $this->fieldClass->create($fields);
                data_set($col4, 'disc6.readonly', false);
                data_set($col4, 'disc7.readonly', false);
                data_set($col4, 'disc8.readonly', false);
                data_set($col4, 'disc9.readonly', false);
                data_set($col4, 'markup.readonly', false);

                if ($config['params']['companyid'] == 19) { //housegem
                    data_set($col4, 'disc6.label', 'TSC Disc (D)');
                    data_set($col4, 'disc7.label', 'Retail Price COD Disc (E)');
                    data_set($col4, 'disc8.label', 'Retail Price 30 Days Disc (F)');
                    data_set($col4, 'disc9.label', 'Retail Price 90 Days Disc (G)');
                }


                return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
                break;
        }
    }

    private function selectqry()
    {
        $qry = "itemid,";
        foreach ($this->fields as $key => $value) {
            $qry = $qry . ',' . $value;
        }
        return $qry;
    }

    public function paramsdata($config)
    {
        $itemid = $config['params']['clientid'];

        $qry = "itemid, format(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,  
                format(amt2," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt2, 
                format(famt," . $this->companysetup->getdecimal('price', $config['params']) . ") as famt, 
                format(amt4," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt4, 
                format(amt5," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt5, 
                format(amt6," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt6, 
                format(amt7," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt7,
                format(amt8," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt8, 
                format(amt9," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt9,
                disc, disc2, disc3, disc4, disc5, disc6, disc7, disc8, disc9, markup, insurance, delcharge";

        $qry .= ", format(namt, " . $this->companysetup->getdecimal('price', $config['params']) . ") as namt, 
            format(namt2, " . $this->companysetup->getdecimal('price', $config['params']) . ") as namt2, 
            format(nfamt, " . $this->companysetup->getdecimal('price', $config['params']) . ") as nfamt, 
            format(namt4, " . $this->companysetup->getdecimal('price', $config['params']) . ") as namt4, 
            format(namt5, " . $this->companysetup->getdecimal('price', $config['params']) . ") as namt5, 
            format(namt6, " . $this->companysetup->getdecimal('price', $config['params']) . ") as namt6, 
            format(namt7, " . $this->companysetup->getdecimal('price', $config['params']) . ") as namt7 ";

        $qry = "select " . $qry . " from " . $this->table . " 
        where itemid = ?";
        $data = $this->coreFunctions->opentable($qry, [$itemid]);

        return $data;
    }

    public function data()
    {
        return [];
    }

    public function createTab($config)
    {
        $tab = [];
        $stockbuttons = [];
        $obj = $this->tabClass->createtab($tab, $stockbuttons);
        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];
        $obj = $this->tabClass->createtabbutton($tbuttons);
        return $obj;
    }

    public function loaddata($config)
    {
        $data = [];
        $itemid = $config['params']['itemid'];

        $row = $config['params']['dataparams'];


        foreach ($this->fields as $key => $value) {
            $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
        }

        
        if($config['params']['companyid']==60){//transpower
            if($data['disc']!=""){
                $discper ="";
                if (!str_contains($data['disc'], '%')) {
                  $d = explode("/",$data['disc']);
                  foreach ($d as $k => $x) {
                    if($discper !=""){
                      $discper .="/";
                    }
          
                    $discper .= $x.'%';
                    
                  }
                  $data['disc'] = $discper;
                }
            }

            if($data['disc2']!=""){
                $discper ="";
                if (!str_contains($data['disc2'], '%')) {
                  $d = explode("/",$data['disc2']);
                  foreach ($d as $k => $x) {
                    if($discper !=""){
                      $discper .="/";
                    }
          
                    $discper .= $x.'%';
                    
                  }
                  $data['disc2'] = $discper;
                }
            }

            if($data['disc3']!=""){
                $discper ="";
                if (!str_contains($data['disc3'], '%')) {
                  $d = explode("/",$data['disc3']);
                  foreach ($d as $k => $x) {
                    if($discper !=""){
                      $discper .="/";
                    }
          
                    $discper .= $x.'%';
                    
                  }
                  $data['disc3'] = $discper;
                }
            }

            if($data['disc4']!=""){
                $discper ="";
                if (!str_contains($data['disc4'], '%')) {
                  $d = explode("/",$data['disc4']);
                  foreach ($d as $k => $x) {
                    if($discper !=""){
                      $discper .="/";
                    }
          
                    $discper .= $x.'%';
                    
                  }
                  $data['disc4'] = $discper;
                }
            }

            if($data['disc5']!=""){
                $discper ="";
                if (!str_contains($data['disc5'], '%')) {
                  $d = explode("/",$data['disc5']);
                  foreach ($d as $k => $x) {
                    if($discper !=""){
                      $discper .="/";
                    }
          
                    $discper .= $x.'%';
                    
                  }
                  $data['disc5'] = $discper;
                }
            }

            if($data['disc6']!=""){
                $discper ="";
                if (!str_contains($data['disc6'], '%')) {
                  $d = explode("/",$data['disc6']);
                  foreach ($d as $k => $x) {
                    if($discper !=""){
                      $discper .="/";
                    }
          
                    $discper .= $x.'%';
                    
                  }
                  $data['disc6'] = $discper;
                }
            }

            if($data['disc7']!=""){
                $discper ="";
                if (!str_contains($data['disc7'], '%')) {
                  $d = explode("/",$data['disc7']);
                  foreach ($d as $k => $x) {
                    if($discper !=""){
                      $discper .="/";
                    }
          
                    $discper .= $x.'%';
                    
                  }
                  $data['disc7'] = $discper;
                }
            }
        }
        
        $namt = $this->othersClass->computestock($data['amt'], $data['disc'], 1, 1);
        $namt2 = $this->othersClass->computestock($data['amt2'], $data['disc2'], 1, 1);
        $nfamt = $this->othersClass->computestock($data['famt'], $data['disc3'], 1, 1);
        $namt4 = $this->othersClass->computestock($data['amt4'], $data['disc4'], 1, 1);
        $namt5 = $this->othersClass->computestock($data['amt5'], $data['disc5'], 1, 1);
        $namt6 = $this->othersClass->computestock($data['amt6'], $data['disc6'], 1, 1);
        $namt7 = $this->othersClass->computestock($data['amt7'], $data['disc7'], 1, 1);
        $data['namt'] = $namt['ext'];
        $data['namt2'] = $namt2['ext'];
        $data['nfamt'] = $nfamt['ext'];
        $data['namt4'] = $namt4['ext'];
        $data['namt5'] = $namt5['ext'];
        $data['namt6'] = $namt6['ext'];
        $data['namt7'] = $namt7['ext'];

        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['dlock'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];

        $this->coreFunctions->sbcupdate($this->table, $data, ['itemid' => $itemid]);

        $config['params']['clientid'] = $config['params']['itemid'];
        $txtdata = $this->paramsdata($config);

        return ['status' => true, 'msg' => 'Save Item Price Success', 'data' => [], 'txtdata' => $txtdata];
    }
}
