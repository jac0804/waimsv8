<?php

namespace App\Http\Controllers;

use App\Http\Classes\api\imageapi;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Classes\moduleClass;
use App\Http\Classes\ledgerClass;
use App\Http\Classes\tableentryClass;
use App\Http\Classes\headtableClass;
use App\Http\Classes\reportlistClass;
use App\Http\Classes\uniqueClass;
use App\Http\Classes\dashboardClass;
use App\Http\Classes\adashboardClass;
use App\Http\Classes\calendarClass;
use App\Http\Classes\imageuploader;
use App\Http\Classes\messageClass;

use App\Http\Classes\loginappClass;
use App\Http\Classes\posappClass;
use App\Http\Classes\finedineappClass;
use App\Http\Classes\payrollappClass;
use App\Http\Classes\appregClass;
use App\Http\Classes\atiapiClass;
use App\Http\Classes\mobile\mobileappv2Class;
use App\Http\Classes\sbcatiappClass;
use App\Http\Classes\othersClass;
use App\Http\Classes\roxasapiClass;
use Illuminate\Support\Facades\Storage;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\common\linkemail;
use App\Http\Classes\filesaving;
use App\Http\Classes\queuingClass;

use Exception;
use Throwable;

use Mail;
use App\Mail\SendMail;
use DateTime;
use App;

class ModuleController extends Controller
{

  private $moduleClass;
  private $ledgerClass;
  private $tableentryClass;
  private $headtableClass;
  private $reportlistClass;
  private $uniqueClass;
  private $dashboardClass;
  private $adashboardClass;
  private $calendarClass;
  private $imageuploader;
  private $messageClass;
  private $loginappClass;
  private $posappClass;
  private $finedineappClass;
  private $payrollappClass;
  private $appregClass;
  private $mobileappv2Class;
  private $sbcatiappClass;
  private $othersClass;
  private $roxasapiClass;
  private $coreFunctions;
  private $linkemail;
  private $imageapi;
  private $filesaving;
  private $queuing;

  public function __construct()
  {
    $this->moduleClass = new moduleClass;
    $this->ledgerClass = new ledgerClass;
    $this->tableentryClass = new tableentryClass;
    $this->headtableClass = new headtableClass;
    $this->reportlistClass = new reportlistClass;
    $this->uniqueClass = new uniqueClass;
    $this->dashboardClass = new dashboardClass;
    $this->adashboardClass = new adashboardClass;
    $this->calendarClass = new calendarClass;
    $this->imageuploader = new imageuploader;
    $this->messageClass = new messageClass;
    $this->loginappClass = new loginappClass;
    $this->posappClass = new posappClass;
    $this->finedineappClass = new finedineappClass;
    $this->payrollappClass = new payrollappClass;
    $this->appregClass = new appregClass;
    $this->mobileappv2Class = new mobileappv2Class;
    $this->sbcatiappClass = new sbcatiappClass;
    $this->othersClass = new othersClass;
    $this->roxasapiClass = new roxasapiClass;
    $this->coreFunctions = new coreFunctions;
    $this->linkemail = new linkemail;
    $this->imageapi = new imageapi;
    $this->filesaving = new filesaving;
    $this->queuing = new queuingClass;
  } //end construct

  public function apitrans(Request $request)
  {
    $data = $request->all();
    switch ($data['action']) {
      case md5('post'):
        $trno = $data['trno'];
        $doc = $data['doc'];
        $moduletype = $data['moduletype'];
        $user = $data['user'];
        $companyid = $data['companyid'];

        $classname = 'App\Http\Classes\modules\\' . strtolower($moduletype) . '\\' . strtolower($doc);
        $this->config['classname'] = $classname;
        $this->config['docmodule'] = new $classname();
        $tablenum = $this->config['docmodule']->tablenum;
        $trno_ = $this->coreFunctions->getfieldvalue($tablenum, 'trno', 'md5(trno)="' . $trno . '"');
        $user_ = $this->coreFunctions->getfieldvalue('useraccess', 'username', 'md5(username)="' . $user . '"');
        $params = [
          'trno' => $trno_,
          'user' => $user_,
          'companyid' => $companyid,
          'doc' => $doc
        ];
        $this->config['params'] = $params;
        return $this->config['docmodule']->posttrans($this->config);
        break;
    }
  }

  public function getFile()
  {
    $string = Input::get('id');
    $decrypted = json_decode($this->othersClass->decryptString($string));
    $params = [];
    if (gettype($decrypted) == 'object') {
      $params = json_decode(json_encode($decrypted->params), true);
      if (isset($params['expiration'])) {
        $datenow = new DateTime($this->othersClass->getCurrentDate());
        $expiration = new DateTime($params['expiration']);
        $expired = $datenow->diff($expiration)->format('%r%a');
        if ($expired < 0) {
          return 'Link expired.';
        } else {
          if (isset($decrypted->reporttype)) {
            switch ($decrypted->reporttype) {
              case 'reportlist':
                $classname = $decrypted->classname;
                break;
              case 'module_attachment':
                $attachment_data = $params['attachment_data'];
                $filename = str_replace('/images/', '', $attachment_data['picture']);
                if (Storage::disk('sbcpath')->exists($filename)) {
                  $ext = explode('.', $attachment_data['picture']);
                  $path = $attachment_data['picture'];
                  $headers = array(
                    'Content-Type: ' . mime_content_type(database_path() . $path),
                  );
                  $filename2 = $attachment_data['title'] . '.' . end($ext);
                  // return response()->download(database_path() . $path, $filename2, $headers, 'attachment');
                  return response(Storage::disk('sbcpath')->get($filename), 200)->header('Content-Type', Storage::disk('sbcpath')->mimeType($filename));
                } else {
                  return 'File not found.';
                }
                return;
                break;
              default:
                $doc = strtolower($params['doc']);
                $type = strtolower($params['moduletype']);
                $classname = 'App\Http\Classes\modules\\' . $type . '\\' . $doc;
                break;
            }
          } else {
            $doc = strtolower($params['doc']);
            $type = strtolower($params['moduletype']);
            $classname = 'App\Http\Classes\modules\\' . $type . '\\' . $doc;
          }
          try {
            $this->config['classname'] = $classname;
            $this->config['docmodule'] = new $classname();
            $this->config['params'] = $params;
            $this->config['params']['dataparams'] = json_decode(json_encode(json_decode($params['dataparams'])), true);
            return $this->config['docmodule']->reportdata($this->config);
          } catch (Exception $e) {
            echo $e;
            return $this;
          }
        }
      } else {
        return 'Link expired.';
      }
    }
  }

  public function mobilev2(Request $request)
  {
    $params = $request->all();
    return $this->mobileappv2Class->admin($params);
  }

  public function mobilev2loadcenters(Request $request)
  {
    return $this->mobileappv2Class->loadcenters();
  }

  public function mobilev2userlogin(Request $request)
  {
    $params = $request->all();
    return $this->mobileappv2Class->userLogin($params);
  }

  public function mobilev2download(Request $request)
  {
    $params = $request->all();
    return $this->mobileappv2Class->download($params);
  }

  public function mobilev2upload(Request $request)
  {
    $params = $request->all();
    return $this->mobileappv2Class->upload($params);
  }

  public function mobilev2gettemplate()
  {
    return $this->mobileappv2Class->getTemplate();
  }

  public function mobilev2savesignature(Request $request)
  {
    $params = $request->all();
    return $this->mobileappv2Class->saveSignature($params);
  }

  public function emailPdf(Request $request)
  {
    $params = $request->all();
    $info['companyid'] = 10;
    $info['subject'] = 'Module Report';
    $info['view'] = 'emails.firstnotice';
    $info['msg'] = '<div>Good Day!</div><br></div>Module Report</div>';
    $info['filename'] = $params['filename'];
    $info['pdf'] = $request->file('file');
    Mail::to($params['email'])->send(new SendMail($info));
    return $request->file('pdf');
  }

  public function sbcloginapp(Request $request)
  {
    $params = $request->all();
    switch ($params['id']) {
      case md5('downloadUserImages'):
        return $this->loginappClass->downloadUserImages($params);
        break;
      case md5('downloadAccounts'):
        return $this->loginappClass->downloadAccounts($params);
        break;
      case md5('uploadLog'):
        return $this->loginappClass->uploadLog($params);
        break;
      case md5('uploadLogs'):
        return $this->loginappClass->uploadLogs($params);
        break;
    }
  }

  public function loadposappbranch()
  {
    return $this->posappClass->loadposappbranch();
  }
  public function loadposappstation(Request $request)
  {
    $params = $request->all();
    return $this->posappClass->loadposappstation($params);
  }
  public function loadposappusers(Request $request)
  {
    $params = $request->all();
    return $this->posappClass->loadposappusers($params);
  }
  public function sbcposapp(Request $request)
  {
    $params = $request->all();
    return $this->posappClass->sbcposapp($params);
  }

  public function fdlogin(Request $request)
  {
    $params = $request->all();
    return $this->finedineappClass->fdlogin($params);
  }
  public function loadfinedineappusers(Request $request)
  {
    $params = $request->all();
    return $this->finedineappClass->loadfinedineappusers($params);
  }
  public function sbcfinedineapp(Request $request)
  {
    $params = $request->all();
    return $this->finedineappClass->sbcfinedineapp($params);
  }
  public function checkFinedineOrders(Request $request)
  {
    $params = $request->all();
    return $this->finedineappClass->checkFinedineOrders($params);
  }

  public function sbcpayrollapp(Request $request)
  {
    $params = $request->all();
    return $this->payrollappClass->sbcpayrollapp($params);
  }

  public function sbcappreg(Request $request)
  {
    $params = $request->all();
    return $this->appregClass->sbcappreg($params);
  }

  public function sbcroxasuploader(Request $request)
  {
    $params = $request->all();
    return $this->roxasapiClass->sbcroxasuploader($params);
  }

  public function sbcatiapp(Request $request)
  {
    $params = $request->all();
    return $this->sbcatiappClass->sbcatiapp($params);
  }

  public function sbcmodule(Request $request)
  {
    //$defaultparams = ['doc','id','center','user','moduletype'];
    //$params = $request->only($defaultparams);
    //return ['qqq'=>md5('getimagebypath')];
    $params = $request->all();
    $params['ip'] = $request->ip();
    if(isset($params['language'])){
      App::setLocale($params['language']);
    }else{
      App::setLocale('en');
    }    
    switch ($params['id']) {
      case md5('checkpostrans'):
        return $this->moduleClass->sbc($params)->checksecurity('view')->checkpostrans()->execute();
        break;
      case md5('newpostrans'):
        return $this->moduleClass->sbc($params)->checksecurity('view')->newpostrans()->execute();
        break;
      case md5('selectFilter'):
        return $this->moduleClass->sbc($params)->checksecurity('view')->selectFilter()->execute();
        break;
      case md5('load'): //ec4d1eb36b22d19728e9d1d23ca84d1c
        return $this->moduleClass->sbc($params)->checksecurity('view')->loadform()->execute();
        break;
      case md5('loadheaddata'): //a257581b74b6da6bc9eeae30d20844c0
        return $this->moduleClass->sbc($params)->checksecurity('view')->loadheaddata()->execute();
        break;
      case md5('loadstock'): //fa1eef0b8465574742f94ddb22a32683
        return $this->moduleClass->sbc($params)->openstock()->execute();
        break;
      case md5('checkperinventory'): //906eb334bb19b141752deac8f3852d51
        return $this->moduleClass->sbc($params)->isposted()->checkperstock()->execute();
        break;
      case md5('checkperaccounting'): //51214ddc0d69f3f696b6693cd54017c4
        return $this->moduleClass->sbc($params)->isposted()->checkperacctg()->execute();
        break;
      case md5('searchbarcode'): //94ef33f4fea71fcfef9367954cfa4086
        return $this->moduleClass->sbc($params)->checksecurity('new')->searchbarcode()->execute();
        break;
      case md5('searchclient'): //857ae238bc974e0c705b64041e4fe907
        return $this->moduleClass->sbc($params)->checksecurity('new')->searchclient()->execute();
        break;
      case md5('searchdocno'): //a5e58342db2015d5c4d56ee38a8c2e80
        return $this->moduleClass->sbc($params)->checksecurity('new')->searchdocno()->execute();
        break;
      case md5('newclient'): //54c20b07810e3b1245e79525f7780eee
        return $this->moduleClass->sbc($params)->checksecurity('new')->newclient()->execute();
        break;
      case md5('newstockcard'): //e0d4adfbab2f2cd1b90541f68be5c58a
        return $this->moduleClass->sbc($params)->checksecurity('new')->newstockcard()->execute();
        break;
      case md5('newtransaction'): //146b1a47185cd27d6b9633c2edd40b91
        return $this->moduleClass->sbc($params)->checksecurity('new')->newtransaction()->execute();
        break;
      case md5('savestockcard'): //91baa15ac9afba20890ffad3e96b35b5
        return $this->moduleClass->sbc($params)->checksecurity('save')->savestockcard()->execute();
        break;
      case md5('saveledgerhead'): //f9a272947fe20bafc85c491f387822ec
        return $this->moduleClass->sbc($params)->checksecurity('save')->saveledgerhead()->execute();
        break;
      case md5('savehead'): //af4e19153ebe54f173208161cb40f841
        return $this->moduleClass->sbc($params)->isposted()->checksecurity('save')->savehead()->execute();
        break;
      case md5('stockstatus'): //39245c9ab9a0579a4e3355c1ad5d1381
        return $this->moduleClass->sbc($params)->isposted()->checksecurity($params['access'])->stockstatus()->execute();
        break;
      case md5('qcardstatus'): //1e626734a3882184b96d3136e5a7e7bf
        return $this->moduleClass->sbc($params)->checksecurity($params['access'])->qcardstatus()->execute();
        break;
      case md5('stockstatusposted'):  //7e4972e897218b4206585c5f4ea28fde
        return $this->moduleClass->sbc($params)->checksecurity($params['access'])->stockstatusposted()->execute();
        break;
      case md5('getlatestprice'): //7e8307da3f569da554dcf963416a09e1
        return $this->moduleClass->sbc($params)->isposted()->getlatestprice()->execute();
        break;
      case md5('deletetrans'): //c24fcc71cb4ff0958782601bb7441c3a
        return $this->moduleClass->sbc($params)->isposted()->checksecurity('delete')->deletetrans()->execute();
        break;
      case md5('deletestockcard'): //ef584d2c5ec2b912a1dff90d0677b583"
        return $this->moduleClass->sbc($params)->checksecurity('delete')->deletestockcard()->execute();
        break;
      case md5('deleteclient'): //30d9a0a534cff90af5a84c2413aef5ee
        return $this->moduleClass->sbc($params)->checksecurity('delete')->deleteclient()->execute();
        break;
      case md5('posttrans'): //f4f4589a40318dfa856d2894d46bc446"
        return $this->moduleClass->sbc($params)->checksecurity($params['action'])->posttrans()->execute();
        break;
      case md5('lockunlock'):  //eb74de0df1d77a4d4cc184c92b5585c5"
        return $this->moduleClass->sbc($params)->checksecurity($params['action'])->isposted()->lockunlock()->execute();
        break;
      case md5('lookupsetupdirect'): //32d529cf0b3b15dc101ec5e8d08d21cb"
        return $this->moduleClass->txtlookupdirect($params)->execute();
        break;
      case md5('lookupcallbackdirect'): //b89dbb80dbf1ff8132547a2f1af70b20"
        return $this->moduleClass->lookupcallbackdirect($params)->execute();
        break;
      case md5('lookupsetup'): //0203e66a945e66e270d61668704afb16"
        return $this->moduleClass->sbc($params)->txtlookup()->execute();
        break;
      case md5('generic'): //3d517f8924ac7fd03699a29d97dc52d9
        return $this->moduleClass->sbc($params)->checksecurity('view')->generic()->execute();
        break;
      case md5('lookupcallback'): //b075be576c9ce2488f70181ecc1bac93"
        return $this->moduleClass->sbc($params)->lookupcallback()->execute();
        break;
      case md5('lookupsearch'): //20b1be5149436b6f50647d2c0fa6dd5d"
        return $this->moduleClass->sbc($params)->lookupsearch()->execute();
        break;
      case md5('inquiry'); //5311173563ec0769cd7ae3fa3e7e0b10"
        return $this->moduleClass->sbc($params)->inquiry()->execute();
        break;
      case md5('doclisting'); //7e9e7125b4f5fd8cd60e3674595ed5d5"
        return $this->moduleClass->sbc($params)->checksecurity('view')->loaddoclisting()->execute();
        break;
      case md5('doclistingreport'): //0be32cd17a3c4c32310b514da880677d
        return $this->moduleClass->sbc($params)->doclistingreport()->execute();
        break;
      case md5('customaform'): //9cd097e702714c647fe9bd6e423fafdc
        return $this->ledgerClass->sbc($params)->loadaform()->execute();
        break;
      case md5('customform'): //2673a414e640483054b47e92bc5f899a"
        return $this->ledgerClass->sbc($params)->loadform()->execute();
        break;
      case md5('optioncustomform'):  //8f570e2634c8b99e2948e641f19369c4
        return $this->ledgerClass->optionloadform($params)->execute();
        break;
      case md5('printcustomform'):  //136e47c012015ab6b694e095af652af5
        return $this->ledgerClass->printcustomform($params)->execute();
        break;
      case md5('customdata'): //b1c3da78dde491884442161481ebb8cf"
        return $this->ledgerClass->sbc($params)->loaddata()->execute();
        break;
      case md5('tableentry'): //8a056c2e527d377c1e8313f22ed478dc"
        return $this->tableentryClass->sbc($params)->checksecurity('load')->loadform()->execute();
        break;
      case md5('tableentrygetdata'): //4196e4670ac36d70ab88ce563af26abe
        return $this->tableentryClass->sbc($params)->checksecurity('load')->getdata()->execute();
        break;
      case md5('tableentryreport'): //85f6d194f12ef450923636332459b445
        return $this->tableentryClass->sbc($params)->reportsetup()->execute();
        break;
      case md5('tableentryreportdata'): //608346a5ea707e6aef182d3486451ff3
        return $this->tableentryClass->sbc($params)->reportdata()->execute();
        break;
      case md5('headtable'): //03168051f05f6d98ec102dd9065af154"
        return $this->headtableClass->sbc($params)->checksecurity('view')->loadform()->execute();
        break;
      case md5('headtablestatus'): //fae9a6de96b84a725efb6badc6c5f72b
        return $this->headtableClass->sbc($params)->headtablestatus()->execute();
        break;
      case md5('tableentrystatus'): //59b16858bbbc2af22648c1fa258f52ee"
        return $this->tableentryClass->sbc($params)->tableentrystatus()->execute();
        break;
      case md5('modulereport'): //14ef77d308d46728830a26fc7c1d1a70
        return $this->moduleClass->sbc($params)->checksecurity('print')->reportsetup()->execute();
        break;
      case md5('modulereportdata'): //fb6cc88d6d169c32a88b3e2a75d8cd2f"
        return $this->moduleClass->sbc($params)->reportdata()->execute();
        break;
      case md5('reportlookupsetup'): //cc34015b0bb959976848faaacb224479"
        return $this->reportlistClass->sbc($params)->lookupsetup()->execute();
        break;
      case md5('reportlist'): //7b5110734dc87a82f7adc45e6efb9a3b"
        return $this->reportlistClass->sbc($params)->loadform()->execute();
        break;
      case md5('reportdata'): //be24f243a2d7e90a44592a838cc718c1"
        return $this->reportlistClass->sbc($params)->reportdata()->execute();
        break;
      case md5('reportlookupsearch'): //3081ec9b5161c95d090ce76145622230"
        return $this->reportlistClass->sbc($params)->reportlookupsearch()->execute();
        break;
      case md5('unique'): //673eb027e9c056f57140322807351dd5"
        return $this->uniqueClass->sbc($params)->checksecurity('load')->loadform()->execute();
        break;
      case md5('uniquestatus'): //da950dae65abf3cd07da3da1d135281b"
        return $this->uniqueClass->sbc($params)->checksecurity('load')->status()->execute();
        break;
      case md5('dashboard'): //dc7161be3dbf2250c8954e560cc35060"
        return $this->dashboardClass->sbc($params)->loadform()->execute();
        break;
      case md5('adashboard'): //109c3122c207ef1820991be46177440e
        return $this->adashboardClass->sbc($params)->checksecurity('view')->loadaform()->execute();
        break;
      case md5('dashboardgetdata'): //e2968e6389677a8b0d83655730721ffa"
        return $this->dashboardClass->sbc($params)->getdata()->execute();
        break;
      case md5('calendar'): //a0e7b2a565119c0a7ec3126a16016113"
        return $this->calendarClass->sbc($params)->loadform()->execute();
        break;
      case md5('changeTheme'): //1ecf81fd76ad0fb685b176f942738068"
        return $this->dashboardClass->sbc($params)->changeTheme()->execute();
        break;
      case md5('loadDefaultTheme'): //bc17a9b6c411c756f4499ce54088802a"
        return $this->dashboardClass->sbc($params)->loadDefaultTheme()->execute();
        break;
      case md5('saveDefaultTheme'): //57eafbd363c7528e659cda9f81adc572"
        return $this->dashboardClass->sbc($params)->saveDefaultTheme()->execute();
        break;
      case md5('imageupload'): //0b40147d19b01f98fb4081c272c314f7"
        return $this->imageuploader->sbc($params)->imagestatus($request)->execute();
        break;
      case md5('messagestatus'): //b608a9950e185319daf7e841ba8eca2e"
        return $this->messageClass->sbc($params)->messagestatus($request)->execute();
        break;
      case md5('imagedownload'): //ea629d0748fb15dbbee25e38f0aa09db"
        return $this->imageuploader->sbc($params)->attachmentdownload($request);
        break;
      case md5('imagefilename'): //9675bb9f9619296b9463ff7ba637e793"
        return $this->imageuploader->sbc($params)->imagefilename($request);
        break;
      case md5('getimagebypath'): //0a20b377360b7be3ea10a46ea67753e5"
        return $this->imageuploader->getimagebypath($params['pathid']);
        break;
      case md5('queuing'): //6f8dc2b043342485ba00a5ff68bb5226
        return $this->queuing->getstatus($params);
        break;
      case md5('escposprintsample'):
        return $this->moduleClass->escposprintsample($params);
        break;
      case md5('getviberusers'):
        return $this->moduleClass->getviberusers();
        break;
      case md5('sendvibermsg'):
        return $this->moduleClass->sendvibermsg($params);
        break;
      case md5('setviberwebhook'):
        return $this->moduleClass->setviberwebhook($params);
        break;
      case md5('getcenter'):
        return $this->moduleClass->getcenter($params);
        break;
      case md5('getGCmsg'):
        return $this->moduleClass->getGCmsg($params);
        break;
      case md5('getPMmsg'):
        return $this->moduleClass->getPMmsg($params);
        break;
      case md5('getUsersList'):
        return $this->moduleClass->getUsersList($params);
        break;
      case md5('seenMsg'):
        return $this->moduleClass->seenMsg($params);
        break;
      case md5('sendemail'):
        return $this->moduleClass->sbc($params)->sendemail()->execute();
        // return $this->sendemail($params);
        break;
      case md5('sendemailreportlist'):
        return $this->reportlistClass->sbc($params)->sendemail()->execute();
        break;
      case md5('submitquestionnaire'):
        return $this->moduleClass->sbc($params)->submitquestionnaire()->execute();
        break;
      case md5('loadnmodule'):
        return $this->moduleClass->sbc($params)->checksecurity('view')->loadnmodule()->execute();
        break;
      case md5('filesaving'):
        return $this->filesaving->sbc($params)->checkfunction()->execute();
      default:
        return 'not found';
        break;
    } // end switch
  }

  public function sbcdownload(Request $request, $id, $trno, $line)
  {
    return $this->imageuploader->imagedownload($request, $id, $trno, $line);
  }

  public function login(Request $request)
  {
    $defaultparams = ['username', 'password', 'pwd', 'companyid', 'mobile'];
    $params = $request->only($defaultparams);
    $params['ip'] = $request->ip();
    return $this->moduleClass->login($params);
  } // end function

  public function sendemail($data)
  {
    Mail::to($data['email'])->send(new SendMail($data));
    return ['status' => true, 'msg' => 'Send Success'];
  }

  public function sendemailpdf($data, $request)
  {
    $info['companyid'] = $data['companyid'];
    $info['subject'] = 'second Offense';
    $info['view'] = 'emails.firstnotice';
    $info['msg'] = '<div>Good Day!</div><br></div>This is friendly reminder that your account balance of ______</div><br><br><br><br><br></div>Thank You,</div><Br><div>xxxxxxx</div>';
    $info['filename'] = 'ddd';
    $info['pdf'] = $request->file('pdf'); //$data['pdf'];
    Mail::to('erick0601@yahoo.com')->send(new SendMail($info));

    //Mail::to($data['email'])->send(new SendMail($data));
    return $request->file('pdf');
    // return ['status'=>true, 'msg'=>'Send Success','pdf'=>$data['pdf']];
  }

  public function viberbot()
  {
    $data = Input::all();
    return $this->moduleClass->viberbot($data);
  }

  public function getEmailExcel(Request $request)
  {
    //function name
    $params = $request->all();
    return $this->moduleClass->getEmailExcel($params);
  }

  public function sendEmailExcel(Request $request)
  {
    try {
      $current_timestamp = date('Y-m-d H:i:s');
      $params = $request->all();
      if (isset($params['emailinfo'])) {
        $info = json_decode($params['emailinfo'], true);
        $info['excel'] = $request->file('file');
        if (isset($info['cc'])) {
          return Mail::to($info['email'])->cc($info['cc'])->send(new SendMail($info));
        } else {
          return Mail::to($info['email'])->send(new SendMail($info));
        }
      } else {
        return 'Email Info not set';
      }
    } catch (Exception $e) {
      var_dump($e);
    }
  }

  public function linkEmail(Request $request)
  {
    $params = $request->all();
    return $this->linkemail->linkemailfunc($params);
  }

  public function sendlinkEmail(Request $request)
  {
    $params = $request->all();
    return $this->linkemail->sendlinkemailfunc($params);
  }

  public function imageAPI(Request $request)
  {
    $params = $request->all();
    return $this->imageapi->imageapifunc($params);
  }

  public function updateEToken(Request $request)
  {
    $params = $request->all();
    return $this->mobileappv2Class->updateEToken($params);
  }

  public function sendSampleNotif(Request $request)
  {
    $params = $request->all();
    return $this->mobileappv2Class->sendSampleNotif($params);
  }
} // end class