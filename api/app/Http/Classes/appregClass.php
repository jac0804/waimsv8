<?php

namespace App\Http\Classes;

use Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\Logger;

use Exception;
use Throwable;

use Illuminate\Support\Str;

class appregClass
{
    private $othersClass;
    private $coreFunctions;
    private $logger;

    public function __construct()
    {
        $this->othersClass = new othersClass;
        $this->coreFunctions = new coreFunctions;
        $this->logger = new Logger;
    }

    public function sbcappreg($params)
    {
        ini_set('max_execution_time', 0);

        try {
            switch ($params['action']) {
                case md5('downloaduser'):
                    $sql = "select userid as id, username, password, pwd, name, isinactive from useraccess";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;

                case md5('downloadcenter'):
                    $sql = "select line as id, code, name from center order by code";
                    $data = $this->coreFunctions->opentable($sql);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;

                case md5('searchpo'):
                    if (!isset($params['center'])) {
                        return json_encode(['status' => false, 'msg' => 'Please select valid Center.']);
                    }

                    $searchkey = "";
                    if (isset($params['searchkey'])) {
                        $str_arr = explode(",", $params['searchkey']);
                        foreach ($str_arr as $value) {
                            if ($searchkey == "") {
                                $searchkey = " (h.docno like '%" . $value . "%' or h.clientname like '%" . $value . "%' or h.address like '%" . $value . "%' or h.rem like '%" . $value . "%' or h.yourref like '%" . $value . "%') ";
                            } else {
                                $searchkey .= " and (h.docno like '%" . $value . "%' or h.clientname like '%" . $value . "%' or h.address like '%" . $value . "%' or h.rem like '%" . $value . "%' or h.yourref like '%" . $value . "%') ";
                            }
                        }
                    }

                    $filter = "";
                    if ($searchkey != "") {
                        $filter = " and (" . $searchkey . ")";
                    }

                    $sql = "select h.trno, h.docno, h.dateid, h.clientname, h.address, h.rem, h.yourref from lahead as h left join cntnum as c on c.trno=h.trno where h.doc='SJ' and c.center=? " . $filter . "
                        union all
                        select h.trno, h.docno, h.dateid, h.clientname, h.address, h.rem, h.yourref from glhead as h left join cntnum as c on c.trno=h.trno where h.doc='SJ' and c.center=? " . $filter . " order by dateid, docno desc";
                    $data = $this->coreFunctions->opentable($sql, [$params['center'], $params['center']]);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;

                case md5('getregistration'):
                    if (!isset($params['trno'])) {
                        return json_encode(['status' => false, 'msg' => 'Please select DR/SJ ref']);
                    }
                    $sql = "select trno, line, station, serialno, rem, others, dateid from sbc_so_notes where trno=? order by line desc";
                    $data = $this->coreFunctions->opentable($sql, [$params['trno']]);
                    return json_encode(['status' => true, 'msg' => '', 'data' => $data]);
                    break;

                case md5('uploadreg'):

                    break;
            }
        } catch (Exception $e) {
            $this->coreFunctions->LogConsole('sbcappreg - ' . $e);
            return json_encode(['status' => false, 'msg' => 'sbcappreg - ' . $e]);
        }
    }
}
