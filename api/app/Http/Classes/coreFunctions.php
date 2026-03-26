<?php

namespace App\Http\Classes;

use Illuminate\Support\Facades\DB;
use App\Http\Classes\othersClass;
use Exception;
use Throwable;
use PDOException;

// IF USING MIGRATION
// use Illuminate\Support\Facades\Schema;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Database\Migrations\Migration;

class coreFunctions
{

	public $errmsg = '';

	//DO NOTE $connection is the one declared on database.php
	function opentable($qry, $params = [], $connection = '')
	{
		try {
			if ($connection == '') {
				return DB::select($qry, $params);
			} else {
				return DB::connection($connection)->select($qry, $params);
			} //end if
			return $qry;
		} catch (PDOException $e) {
			$this->create_Elog($qry . ' --- ' . substr($e->getMessage(), 0, 500));
			//throw new $e->getMessage;
			echo substr($e, 0, 500);
			// abort(401,$qry);

			$this->logFuncCallers();

			return ['status' => false, 'msg' => substr($e->getMessage(), 0, 500), 'qry' => $qry];
		}
	} //end fn

	function logFuncCallers()
	{
		// Get the trace to see who called this function
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
		$this->create_Elog('trace:' . json_encode($trace));
	}

	function opentablelogin($qry, $params = [], $connection = '')
	{
		try {
			if ($connection == '') {
				return DB::select($qry, $params);
			} else {
				return DB::connection($connection)->select($qry, $params);
			} //end if
		} catch (PDOException $e) {
			$this->create_Elog($qry . ' --- ' . substr($e->getMessage(), 0, 500));

			$this->logFuncCallers();

			return ['status' => false, 'msg' => substr($e->getMessage(), 0, 500)];
		}
	} //end fn


	function openqry($qry, $params = [], $connection = '')
	{
		try {
			if ($connection == '') {
				$data = DB::select($qry, $params);
			} else {
				$data = DB::connection($connection)->select($qry, $params);
			} //end if
			return ['status' => true, 'data' => $data];
		} catch (PDOException $e) {
			$this->create_Elog(substr($e->getMessage(), 0, 500));

			$this->logFuncCallers();

			return ['status' => false, 'msg' => substr($e->getMessage(), 0, 500), 'qry' => $qry];
		}
	}

	function getfieldvalue($table, $field, $condition, $params = [], $sort = '', $blnID = false)
	{
		if ($sort != '') {
			$qry = 'select ' . $field . ' as value from ' . $table . ' where ' . $condition . ' order by ' . $sort . ' limit 1';
		} else {
			$qry = 'select ' . $field . ' as value from ' . $table . ' where ' . $condition . ' limit 1';
		}

		$result = $this->datareader($qry, $params);
		if ($blnID) {
			if ($result  == '') $result = 0;
		}
		return $result;
	}

	function datareader($qry, $params = [], $connection = '', $blnID = false)
	{
		try {
			if ($connection == '') {
				$data = DB::select($qry, $params);
			} else {
				$data = DB::connection($connection)->select($qry, $params);
			} //end if
			if (!empty($data)) {
				$result = $data[0]->value;
				if ($blnID) {
					if ($result  == '') $result = 0;
				}
				return $result;
			} else {
				if ($blnID) {
					return 0;
				}
				return '';
			}
		} catch (PDOException $e) {
			$this->create_Elog($qry . '---' . substr($e->getMessage(), 0, 500));

			$this->logFuncCallers();

			return '';
		}
	} //end

	function sbcupdate($table, $columns, $condition)
	{
		if (empty($columns)) return 1;

		try {
			DB::table($table)->where($condition)->update($columns);
			return 1;
		} catch (PDOException $e) {
			$this->create_Elog($table . ' - ' . substr($e->getMessage(), 0, 10000));

			$this->logFuncCallers();

			return 0;
		}
	} //end func

	public function sbcinsert($table, $columns)
	{
		if (empty($columns)) return 1;

		$this->errmsg = '';
		try {
			DB::table($table)->insert($columns);
			return 1;
		} catch (PDOException $e) {
			$this->create_Elog($table . ' - ' . substr($e->getMessage(), 0, 10000));


			// $this->errmsg = substr($e->getMessage(), 0, 500);
			$this->errmsg = substr_replace(substr($e->getMessage(), 0, 500), "", 127 - 1, 500);

			// $pos = strpos($e->getMessage(), "SQL:");
			// $this->LogConsole($pos);

			$this->logFuncCallers();
			return 0;
		} //end catch
	} //end func

	public function sbclogger($txt, $type = 'DEBUG', $logConsole = true)
	{
		if ($logConsole) $this->LogConsole($txt);

		try {
			$othersClass = new othersClass;
			$current_timestamp = $othersClass->getCurrentTimeStamp();
			switch ($type) {
				case "DLOCK":
				case "MIRROR":
					$this->sbcinsert("pos_log", ['e_detail' => $type, 'querystring' => $txt, 'date_executed' => $current_timestamp]);
					break;
				default:
					$this->sbcinsert("execution_log", ['e_detail' => $type, 'querystring' => $txt, 'date_executed' => $current_timestamp]);
					break;
			}
			return 1;
		} catch (PDOException $e) {
			$this->create_Elog(substr($e->getMessage(), 0, 500));
			$this->logFuncCallers();
			return 0;
		} //end catch
	} //end func

	function execqry($qry, $type = '', $params = [], $connection = '')
	{
		$this->errmsg = '';

		switch (strtolower($type)) {
			case 'insert':
				try {
					if ($connection == '') {
						DB::insert($qry, $params);
					} else {
						DB::connection($connection)->insert($qry, $params);
					} //end if
					return 1;
				} catch (PDOException $e) {
					$this->create_Elog($qry . ' - ' . substr($e->getMessage(), 0, 500));
					$this->logFuncCallers();
					return 0;
				} //end catch
				break;

			case 'update':
				try {
					if ($connection == '') {
						DB::update($qry, $params);
					} else {
						DB::connection($connection)->update($qry, $params);
					} //end if
					return 1;
				} catch (PDOException $e) {
					$this->create_Elog($qry . ' - ' . substr($e->getMessage(), 0, 500));
					$this->logFuncCallers();
					return 0;
				} //end error
				break;

			case 'delete':
				try {
					if ($connection == '') {
						DB::delete($qry, $params);
					} else {
						DB::connection($connection)->delete($qry, $params);
					} //end if
					return 1;
				} catch (PDOException $e) {
					$this->create_Elog($qry . ' - ' . substr($e->getMessage(), 0, 500));
					$this->logFuncCallers();
					return 0;
				}
				break;

			case 'trigger':
			case 'procedure':
				try {
					if ($connection == '') {
						DB::unprepared($qry);
					} else {
						DB::connection($connection)->unprepared($qry);
					}
					return 1;
				} catch (PDOException $e) {
					$this->create_Elog($qry . ' - ' . substr($e->getMessage(), 0, 500));
					$this->logFuncCallers();
					return 0;
				}
				break;

			default:
				try {
					if ($connection == '') {
						DB::statement($qry, $params);
					} else {
						DB::connection($connection)->statement($qry, $params);
					}
					return 1;
				} catch (PDOException $e) {
					$this->create_Elog($qry . ' - ' . substr($e->getMessage(), 0, 500));
					$this->errmsg = substr($e->getMessage(), 0, 500);
					$this->logFuncCallers();
					return 0;
				}
				break;
		} //end switch
	} //end fn

	function execqrynolog($qry, $params = [], $connection = '')
	{
		try {
			if ($connection == '') {
				DB::statement($qry, $params);
			} else {
				DB::connection($connection)->statement($qry, $params);
			}
			return 1;
		} catch (PDOException $e) {
			//$this->logFuncCallers();
			return 0;
		}
	}

	function create_Elog($query)
	{

		$othersClass = new othersClass;
		$current_timestamp = $othersClass->getCurrentTimeStamp();
		$data = ['e_detail' => 'ERROR QUERY', 'date_executed' => $current_timestamp, 'querystring' => $query];
		return $this->sbcinsert('execution_log', $data);
	} //end e log function



	function insertGetId($table, $dataset)
	{
		$this->errmsg = '';

		try {
			$key = DB::table($table)->insertGetId($dataset);
			return $key;
		} catch (PDOException $e) {

			$this->create_Elog(substr($e->getMessage(), 0, 500));

			$this->errmsg = substr_replace(substr($e->getMessage(), 0, 500), "", 127 - 1, 500);
			$this->logFuncCallers();
			return 0;
		} //end try
	} //end fn

	function getSQLError()
	{
		return $this->errmsg;
	}

	//use for insert , update , and delete queries (creates transaction unit - sql)
	function constructTrans($qry_array)
	{
		DB::beginTransaction();
		try {
			foreach ($qry_array as $key => $value) {
				switch (strtolower($value['type'])) {
					case 'insert':
						if (!isset($value['conn'])) {
							DB::insert($value['qry']);
						} else {
							DB::connection($value['conn'])->insert($value['qry']);
						} //end if
						break;

					case 'update':
						if (!isset($value['conn'])) {
							DB::update($value['qry']);
						} else {
							DB::connection($value['conn'])->update($value['qry']);
						} //end if
						break;

					case 'delete':
						if (!isset($value['conn'])) {
							DB::delete($value['qry']);
						} else {
							DB::connection($value['conn'])->delete($value['qry']);
						} //end if
						break;
				} //end switch
			} //end for each

			DB::commit();
			return true;
		} catch (Exception $e) {
			DB::rollback();
			return false;
		} catch (Throwable $e) {
			DB::rollback();
			return false;
		} //end try
	} //end fn

	function sbcaddcolumn($table, $column, $type, $alter = 1)
	{
		$stat = '';
		try {
			if ($this->sbctableexist($table)) {
				if (!$this->sbccolumnexist($table, $column)) {
					$stat = 'Add column';
					return DB::statement('ALTER TABLE `' . $table . '` ADD `' . $column . '` ' . $type . ' ');
				} else {
					if ($alter) {
						$stat = 'Alter column';
						return DB::statement('ALTER TABLE `' . $table . '` MODIFY COLUMN `' . $column . '` ' . $type . ' ');
					} else {
						return 1;
					}
				}
			} else {
				$this->create_Elog("Table - " . $table . " doesn't exist - Field - " . $column);
			}
		} catch (PDOException $e) {
			$this->create_Elog("Table - " . $table . " - Field - " . $column . " - " . $stat . ' - ' . substr($e->getMessage(), 0, 500));
		}
	} //end fn

	function sbcaddcolumngrp($arr_table, $arr_column, $type, $alter = 1)
	{
		$stat = '';
		foreach ($arr_table as $table) {

			foreach ($arr_column as $column) {
				try {
					if ($this->sbctableexist($table)) {
						if (!$this->sbccolumnexist($table, $column)) {
							$stat = 'Add column';
							DB::statement('ALTER TABLE `' . $table . '` ADD `' . $column . '` ' . $type . ' ');
						} else {
							if ($alter) {
								$stat = 'Alter column';
								DB::statement('ALTER TABLE `' . $table . '` MODIFY COLUMN `' . $column . '` ' . $type . ' ');
							}
						}
					} else {
						$this->create_Elog("Table - " . $table . " doesn't exist - Field - " . $column);
					}
				} catch (PDOException $e) {
					$this->create_Elog("Table - " . $table . " - Field - " . $column . " - " . $stat . ' - ' . substr($e->getMessage(), 0, 500));
				}
			}
		}
	} //end fn

	function sbcdropcolumngrp($arr_table, $arr_column)
	{
		$stat = '';
		foreach ($arr_table as $table) {
			foreach ($arr_column as $column) {
				try {
					if ($this->sbctableexist($table)) {
						if ($this->sbccolumnexist($table, $column)) {
							$stat = 'Drop column';
							DB::statement('ALTER TABLE `' . $table . '` DROP `' . $column . '`');
						}
					} else {
						$this->create_Elog("Table - " . $table . " doesn't exist - Field - " . $column);
					}
				} catch (PDOException $e) {
					$this->create_Elog("Table - " . $table . " - Field - " . $column . " - " . $stat . ' - ' . substr($e->getMessage(), 0, 500));
				}
			}
		}
	} //end fn

	function sbcdropcolumn($table, $column)
	{
		$stat = '';
		try {
			if ($this->sbctableexist($table)) {
				if ($this->sbccolumnexist($table, $column)) {
					$stat = 'Drop column';
					return DB::statement('ALTER TABLE `' . $table . '` DROP `' . $column . '`');
				}
			}
		} catch (PDOException $e) {
			$this->create_Elog("Table - " . $table . " - Field - " . $column . " - " . $stat . " - " . substr($e->getMessage(), 0, 500));
		}
	}

	function sbcdroptableprimarykey($table)
	{
		try {

			$data = $this->opentable("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = schema() AND column_key = 'PRI' AND table_name = '" . $table . "'");
			if (!empty($data)) {
				$this->execqry("ALTER TABLE " . $table . " DROP PRIMARY KEY");
			}
		} catch (PDOException $e) {
			$this->create_Elog("Table - " . $table . ' - ' .  substr($e->getMessage(), 0, 500));
		}
	}

	function sbctableexist($table)
	{
		$result = DB::getSchemaBuilder()->hasTable($table);
		return $result;
	} //end fn

	function sbccolumnexist($table, $column)
	{
		$result = DB::getSchemaBuilder()->hasColumn($table, $column);
		return $result;
	} //end fn

	function sbccreatetable($table, $qry, $alter = 0)
	{
		try {

			// $url = 'App\Http\Classes\\' . 'companysetup';

			checkhere:
			if (!$this->sbctableexist($table)) {
				DB::statement($qry);
			} else {
			}
		} catch (PDOException $e) {
			$this->create_Elog($qry . ' => ' . substr($e->getMessage(), 0, 500));
			return 0;
		}
	} //end function

	function sbcdroptable($table)
	{
		try {
			if (!$this->sbctableexist($table)) {
				$qry = 'SELECT  COUNT(*) AS value  FROM `' . $table . '`';
				if ($this->datareader($qry) == 0) {
					DB::statement("DROP TABLE IF EXISTS `'.$table.'`");
				}
			}
		} catch (PDOException $e) {
			$this->create_Elog($qry . ' => ' . substr($e, 0, 500));
			return 0;
		}
	} //end function

	function sbcdroptriggers($table)
	{
		$stat = '';
		try {
			DB::unprepared('DROP TRIGGER IF EXISTS `' . $table . '`');
		} catch (PDOException $e) {
			$this->create_Elog("Drop Trigger - " . $table . " - Failed -    " . substr($e, 0, 500));
		}
	} //end fn

	function createindex($table, $indexname, $fields, $alter = 0)
	{
		try {
			if ($alter) {
				$this->opentable("DROP INDEX " . $indexname . " ON " . $table);
			}

			$qry = "SHOW INDEX FROM " . $table . " WHERE Key_name='" . $indexname . "'";
			$result = $this->opentable($qry);
			if (empty($result)) {
				$str = '';
				foreach ($fields as $key => $value) {
					if ($str == '') {
						$str = $value;
					} else {
						$str .= "," . $value;
					}
				}
				if ($str != '') {
					$this->execqry("ALTER TABLE " . $table . " ADD INDEX " . $indexname . " (" . $str . ")");
				}
			}
			return 1;
		} catch (PDOException $e) {
			return 0;
		}
	}

	public function LogConsole($msg)
	{
		if (env('APP_DEBUG')) {
			$out = new \Symfony\Component\Console\Output\ConsoleOutput();
			$out->writeln($msg);
		}
	}
}//end class