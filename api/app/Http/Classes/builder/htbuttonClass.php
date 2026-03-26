<?php

namespace App\Http\Classes\builder;
use DB;
use Exception;
use Throwable;
use App\Http\Classes\othersClass;

class htbuttonClass{
    protected $othersClass;

        public function __construct() {
        $this->othersClass = new othersClass;
    }//end fn

    //actiontype
    //1.function
    //2.lookup(not yet)
	private $buttons = array(
       'save' => array(
            'name'=>'save',
        	'icon'=>'save',
        	'label' => 'Save',
        	'class' => 'btnhead',
            'tooltip' => 'Save Changes',
            'disable' => false,
            'actiontype' => 'function',
            'action' => 'save',
            'action2' => 'save',
            'visible' => true,
            'confirm' => true,
            'confirmlabel' => 'Are you sure you want to save?'
       ),
       'cancel' => array(
        'name'=>'cancel',
        'icon'=>'cancel',
        'label' => 'Cancel',
        'class' => 'btnhead',
        'tooltip' => 'Cancel',
        'disable' => false,
        'actiontype' => 'function',
        'action' => 'cancel',
        'action2' => 'cancel',
        'visible' => true,
        'confirm' => true,
        'confirmlabel' => 'Are you sure you want to exit?'
       )
	);


	public function create($btns){
      $btn = [];
      foreach($btns as $key => $value){
        $btn = $this->othersClass->array_add($btn,$value,$this->buttons[$value]);
      }
      return $btn;
	} // create
}