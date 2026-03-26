<?php

namespace App\Http\Classes\modules\warehousingentry;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

class entryincentivedocno
{
    private $tabClass;
    private $coreFunctions;
    private $companysetup;
    private $othersClass;

    public $modulename = 'SALES DOCUMENTS';
    public $gridname = 'inventory';
    private $fields = ['incentives'];
    private $table = 'inc';

    public $style = 'width:100%;';
    public $showclosebtn = true;

    public function __construct()
    {
        $this->tabClass = new tabClass;
        $this->coreFunctions = new coreFunctions;
        $this->companysetup = new companysetup;
        $this->othersClass = new othersClass;
    }

    public function getAttrib()
    {
        $attrib = array('load' => 2518, 'edit' => 2518);
        return $attrib;
    }

    public function createTab($config)
    {
        $isselected = 0;
        $docno = 1;
        $amt = 2;
        $clientcom = 3;
        $clientcomamt = 4;
        $released = 5;
        $releaseby = 6;

        $tab = [$this->gridname => ['gridcolumns' => ['isselected', 'docno', 'amt', 'clientcom', 'clientcomamt', 'released', 'releaseby']]];
        $stockbuttons = [];

        $obj = $this->tabClass->createtab($tab, $stockbuttons);

        $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
        $obj[0][$this->gridname]['columns'][$amt]['type'] = 'label';

        $obj[0][$this->gridname]['columns'][$isselected]['align'] = 'text-left';

        $obj[0][$this->gridname]['columns'][$isselected]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;;max-width:40px;';
        $obj[0][$this->gridname]['columns'][$amt]['style'] = 'text-align:right; width:40px;whiteSpace: normal;min-width:40px;;max-width:40px;';

        $obj[0][$this->gridname]['showtotal'] = false;

        return $obj;
    }

    public function createtabbutton($config)
    {
        $tbuttons = [];

        $isquota = false;
        if ($config['params']['row']['isquota'] == "true") {
            array_push($tbuttons, 'saveallentry');
            $isquota = true;
        }

        $obj = $this->tabClass->createtabbutton($tbuttons);
        if ($isquota) {
            $obj[0]['label'] = 'RELEASE';
        }
        return $obj;
    }

    public function loaddata($config)
    {
        $clientid = $config['params']['row']['clientid'];
        $start = $config['params']['row']['startdate'];
        $end = $config['params']['row']['releasedate'];

        return $this->getdata($clientid, $start, $end);
    }

    public function getdata($clientid, $start, $end)
    {
        $qry = "select i.ptrno, i.trno, i.line, i.acnoid, ar.docno, FORMAT(i.amt,2) as amt, i.clientid, client.clientname, 'false' as isselected,
        client.quota as clientquota, client.comm as clientcom, FORMAT(i.clientcomamt,2) as clientcomamt, 
        '" . $start . "' as startdate, '" . $end . "' as releasedate, i.clientrelease as released, i.clientreleaseby as releaseby
        from incentives as i left join arledger as ar on ar.trno=i.trno and ar.line=i.line 
        left join client on client.clientid=i.clientid
        where i.clientid=? and date(i.depodate) between ? and ?";
        return $this->coreFunctions->opentable($qry, [$clientid, $start, $end]);
    }

    public function saveallentry($config)
    {
        $msg = '';
        $status = true;
        $return = [];
        $data = $config['params']['data'];
        $user = $config['params']['user'];

        if ($config['params']['sourcerow']['isquota'] == "false") {
            $msg = 'Quota is not met.';
            goto reloadhere;
        }

        $releasedate = $this->othersClass->getCurrentTimeStamp();

        foreach ($data as $key => $value) {
            if ($value['isselected'] == 'true') {
                if (!$value['released']) {
                    $trno = $value['trno'];
                    $line = $value['line'];
                    $this->coreFunctions->execqry("update incentives set clientrelease='" . $releasedate . "', clientreleaseby='" . $user . "' where clientrelease is null and trno=? and line=?", "UPDATE", [$trno, $line]);
                } else {
                    $msg .= $value['docno'] . ' was already released. ';
                }
            }
        }

        reloadhere:
        $clientid = $config['params']['sourcerow']['clientid'];
        $start = $config['params']['sourcerow']['startdate'];
        $end = $config['params']['sourcerow']['releasedate'];

        $return = $this->getdata($clientid, $start, $end);

        if ($msg == '') {
            $msg = 'Successfully tagged as released';
        } else {
            $msg = 'Failed to release. ' . $msg;
        }

        return ['status' => $status, 'msg' => $msg, 'data' => $return];
    }
}
