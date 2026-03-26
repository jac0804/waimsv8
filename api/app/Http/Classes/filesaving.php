<?php

namespace App\Http\Classes;

use Request;
use App\Http\Requests;
use App\Http\Classes\coreFunctions;
use Illuminate\Support\Facades\Storage;


use Exception;
use Throwable;

use Illuminate\Support\Str;

class filesaving
{

    private $coreFunctions;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
    }

    public function sbc($params)
      {
        $this->config['params'] = $params;
        return $this;
      }



    function lookupqrysave($config, $qry){
        $filename = 'lookup/'.$config['params']['action'].$config['params']['user'].'.sbc';
        // Create directory if it doesn't exist
        if (!Storage::disk('sbcpath')->exists(dirname($filename))) {
            Storage::disk('sbcpath')->makeDirectory(dirname($filename));
        }
        $putResult = Storage::disk('sbcpath')->put($filename, $qry);
        return ['filename'=>$filename,'status'=>$putResult];
        
    }

    public function checkfunction(){
      switch ($this->config['params']['action']) {
        case 'additem':
          $this->additem();
          break;
        case 'reportstr':
          $this->reportstr();
          break;
        case 'csvbatch':
          $this->csvbatch();
          break;
      }
        return $this;
    }

    private function additem()
    {
        if (Storage::disk('sbcpath')->exists($this->config['params']['path'])) {
          $content = Storage::disk('sbcpath')->get($this->config['params']['path']);
          $qry = $content.' limit '.(($this->config['params']['loop'] + 1)*$this->config['params']['rowcount']).','.$this->config['params']['rowcount'];
          $data = $this->coreFunctions->opentable($qry);
          $this->coreFunctions->LogConsole($qry);
          $count = count($data);
        }
        if ($count>=$this->config['params']['rowcount']){
          $this->config['return'] = ['status'=>true,'msg'=>'Success','action'=>$this->config['params']['action'],'path'=>$this->config['params']['path'],'loop'=>$this->config['params']['loop']+1,'callback'=>true,'data'=>$data];
 
        } else {
          $this->config['return'] = ['status'=>true,'msg'=>'Success','action'=>$this->config['params']['action'],'path'=>$this->config['params']['path'],'loop'=>$this->config['params']['loop']+1,'callback'=>false,'data'=>$data];
            Storage::disk('sbcpath')->delete($this->config['params']['path']);
            
        }
        return $this;
    }

    private function reportstr()    
    {
      ini_set('memory_limit', '-1');
       $content = '';      
       $this->coreFunctions->LogConsole($this->config['params']['path'].$this->config['params']['count2'].'.sbc');
      if (Storage::disk('sbcpath')->exists($this->config['params']['path'].$this->config['params']['count2'].'.sbc')) {
        $content = Storage::disk('sbcpath')->get($this->config['params']['path'].$this->config['params']['count2'].'.sbc');
        Storage::disk('sbcpath')->delete($this->config['params']['path'].$this->config['params']['count2'].'.sbc');
      } else {
        $this->config['params']['errorcount'] = $this->config['params']['errorcount']+1;
      }

      $this->config['return'] = ['status'=>true,'msg'=>'Success','action'=>$this->config['params']['action'],'path'=>$this->config['params']['path'],'callback'=>true,'report'=>$content,'count2'=>$this->config['params']['count2']+1,'errorcount'=>$this->config['params']['errorcount']];
      return $this;
    }

    private function csvbatch()
    {
      ini_set('memory_limit', '-1');
      if (Storage::disk('sbcpath')->exists($this->config['params']['path'])) {
        $content = Storage::disk('sbcpath')->get($this->config['params']['path']);
        Storage::disk('sbcpath')->delete($this->config['params']['path']);
      } else {
        $this->config['params']['errorcount'] = $this->config['params']['errorcount']+1;
      }
      $this->config['return'] = ['status'=>true,'msg'=>'Success','action'=>$this->config['params']['action'],'data'=>$content,'count2'=>$this->config['params']['count2']+1,'errorcount'=>$this->config['params']['errorcount']];
      return $this;
    }

    public function execute()
      {
        return response()->json($this->config['return'], 200);
      } // end function


}//end class
