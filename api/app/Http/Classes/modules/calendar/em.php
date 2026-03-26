<?php

namespace App\Http\Classes\modules\calendar;

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

//view
//1. month-scheduler
//2. week-scheduler
//3.month

class em
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ROOM PLAN';
  public $view = 'month';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $style = 'height: 400px;';
  private $divstyle = 'max-width: 1200px; width: 100%;';
  private $resourceheight = '40';
  private $form = 'ementry';
  private $classid = 'attendanceentry';



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }


  public function loadform($config)
  {
    switch ($config['params']['action']) {
      case 'load':
        return $this->load($config);
        break;
      case 'getdata':
        $data = $this->getdata($config);
        return ['status' => true, 'msg' => 'Successful.', 'data' => $data];
      default:
        # code...
        break;
    }
  } //end function

  private function load($config)
  {
    $document = $this->coreFunctions->opentable("select scheddocno, courseid, syid, sectionid, subjectid from en_athead where trno=? union all select scheddocno, courseid, syid, sectionid, subjectid from en_glhead where trno=?", [$config['params']['trno'], $config['params']['trno']]);
    $resources = [];
    if (!empty($document)) {
      $qry2 = "select concat({$config['params']['trno']}, '-', head.clientid) as id, concat(stud.fname,' ', stud.mname,' ', stud.lname) as label
        from glhead as head
        left join glsubject as subj on subj.trno=head.trno
        left join en_studentinfo as stud on stud.clientid = head.clientid
        left join en_subject as sub on sub.trno = subj.subjectid
        left join en_glsubject as scs on scs.trno=subj.screfx and scs.line=subj.sclinex
        left join en_glhead as sc on sc.trno=scs.trno
        left join client on client.clientid=head.clientid  left join transnum as t on t.trno=scs.trno
        where head.doc='ER' and head.courseid=? and head.syid=? and subj.subjectid=? and t.docno=? ";
      $resources = $this->coreFunctions->opentable($qry2, [$document[0]->courseid, $document[0]->syid, $document[0]->subjectid, $document[0]->scheddocno]);
    }

    $data = $this->getdata($config);

    $types = $this->coreFunctions->opentable("select * from en_attendancetype");
    $plots = [];
    if (!empty($types)) {
      foreach ($types as $t) {
        $plots[$t->line] = ['background' => $t->color, 'color' => '', 'label' => ''];
      }
    }
    $defaultplot = array('background' => 'white', 'color' => 'black', 'label' => '');
    switch ($config['params']['action2']) {
      case 'calendar':
        $view = 'month';
        break;
      case 'calendar2':
        $view = 'month-scheduler';
        break;
    }
    return [
      'status' => true,
      'msg' => 'Successfully loaded.',
      'view' => $view,
      'resources' => $resources,
      'plots' => $plots,
      'defaultplot' => $defaultplot,
      'style' => $this->style,
      'divstyle' => $this->divstyle,
      'resourceheight' => $this->resourceheight,
      'data' => $data,
      'form' => $this->form,
      'classid' => $this->classid
    ];
  } //end function


  private function getdata($config)
  {
    $qry = "select concat(trno, '-', clientid) as id, left(atdate,10) as dateid, status from en_atstudents where trno=? and year(atdate)=? and month(atdate)=?
  union all 
  select concat(trno, '-', clientid) as id, left(atdate,10) as dateid, status from en_glstudents where trno=? and year(atdate)=? and month(atdate)=?";
    $data = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['year'], $config['params']['month'], $config['params']['trno'], $config['params']['year'], $config['params']['month']]);
    return $data;
  } //end function


} //end class
