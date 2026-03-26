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

class roxasapiClass
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

    public function sbcroxasuploader($params)
    {
        ini_set('max_execution_time', 0);

        try {
            $action = $_SERVER['HTTP_SBC_ACTION'];
            switch ($action) {
                case "INSERT":
                    $data = [];
                    $table = $_SERVER['HTTP_SBC_TABLE'];
                    switch ($table) {
                        case 'projectroxas':
                            $data = [
                                'line' => $params['line'],
                                'compcode' => $params['compcode'],
                                'code' => $params['code'],
                                'name' => $params['name'],
                                'groupid' => $params['groupid'],
                                'bank' => $params['bank']
                            ];
                            break;
                        case 'subprojectroxas':
                            $data = [
                                'line' => $params['line'],
                                'compcode' => $params['compcode'],
                                'code' => $params['code'],
                                'name' => $params['name'],
                                'parent' => $params['parent']
                            ];
                            break;
                        case 'blocklotroxas':
                            $data = [
                                'line' => $params['line'],
                                'compcode' => $params['compcode'],
                                'code' => $params['code'],
                                'phase' => $params['phase'],
                                'block' => $params['block'],
                                'lot' => $params['lot'],
                                'subprojectcode' => $params['subprojectcode']
                            ];
                            break;
                        case 'amenityroxas':
                            $data = [
                                'line' => $params['line'],
                                'compcode' => $params['compcode'],
                                'code' => $params['code'],
                                'name' => $params['name'],
                                'groupid' => $params['groupid']
                            ];
                            break;
                        case 'subamenityroxas':
                            $data = [
                                'line' => $params['line'],
                                'compcode' => $params['compcode'],
                                'code' => $params['code'],
                                'name' => $params['name'],
                                'parent' => $params['parent']
                            ];
                            break;
                        case 'departmentroxas':
                            $data = [
                                'line' => $params['line'],
                                'compcode' => $params['compcode'],
                                'code' => $params['code'],
                                'name' => $params['name'],
                                'groupid' => $params['groupid']
                            ];
                            break;
                        default:
                            return json_encode(['status' => false, 'msg' => 'Invalid table']);
                            break;
                    }

                    if (!empty($data)) {
                        if ($this->checkexisting($table, $data)) return json_encode(['status' => true, 'msg' => 'Already exists', 'data' => $data]);

                        if ($this->coreFunctions->sbcinsert($table, $data)) {
                            return json_encode(['status' => true, 'msg' => 'Success', 'data' => $data]);
                        } else {
                            return json_encode(['status' => false, 'msg' => 'Failed to insert', 'data' => $data]);
                        }
                    } else {
                        return json_encode(['status' => false, 'msg' => 'No data to insert']);
                    }
                    break;

                case "DELETE":
                    try {
                        $table = $_SERVER['HTTP_SBC_TABLE'];
                        $this->coreFunctions->execqry("delete from " . $table . " where compcode='" . $params['compcode'] . "'");

                        return json_encode(['status' => true, 'msg' => 'Successfully deleted table ' . $table . ' - compcode ' . $params['compcode']]);
                    } catch (Exception $e) {
                        $this->coreFunctions->LogConsole('sbcroxasdelete - ' . $e);
                        return json_encode(['status' => false, 'msg' => 'sbcroxasdelete - ' . $e]);
                    }
                    break;
            }
        } catch (Exception $e) {
            $this->coreFunctions->LogConsole('sbcroxasupload - ' . $e);
            return json_encode(['status' => false, 'msg' => 'sbcroxasupload - ' . $e]);
        }
    }

    public function checkexisting($table, $params)
    {
        $filters = '';
        foreach ($params as $key => $val) {
            $filters .= " and " . $key . "='" . $val . "'";
        }

        $data = $this->coreFunctions->opentable("select * from " . $table . " where ''=''" . $filters);

        if (empty($data)) {
            return false;
        } else {
            return true;
        }
    }
}
