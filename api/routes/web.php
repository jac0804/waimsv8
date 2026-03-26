<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ModuleController;
use App\Http\Middleware\checkHeader;
use App\Http\Middleware\checkTimeinHeader;
use App\Http\Middleware\checkPOSAppHeader;
use App\Http\Middleware\checkFinedineAppHeader;
use App\Http\Middleware\checkPayrollHeader;
use App\Http\Middleware\checkSbcRegHeader;
use App\Http\Middleware\checkRoxasHeader;
use App\Http\Middleware\checkSbcATIHeader;
use App\Http\Middleware\checkSBCMobilev2Header;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('waw', [ModuleController::class, 'waw']);

Route::middleware([checkHeader::class])->group(function () {
  Route::post('af4f683bcc0a068a54370bb64758a9e5', [ModuleController::class, 'sbcmodule']);
  Route::get('af4f683bcc0a068a54370bb64758a9e5', [ModuleController::class, 'sbcmodule']);
});

Route::middleware([checkTimeinHeader::class])->group(function () {
  Route::post(md5('sbctimein'), [ModuleController::class, 'sbcloginapp']);
});

Route::middleware([checkPOSAppHeader::class])->group(function () {
  Route::post('posapp/app', [ModuleController::class], 'sbcposapp');
});

Route::post('posapp/loadBranch', [ModuleController::class, 'loadposappbranch']);
Route::post('posapp/loadStation', [ModuleController::class, 'loadposappstation']);
Route::post('posapp/getUsers', [ModuleController::class, 'loadposappusers']);
Route::get('posapp/getVersion', function () {
  return env('APP_VERSION', '2021.12.13.1');
});
Route::post('finedineapp/fdlogin', [ModuleController::class, 'fdlogin']);
Route::post('finedineapp/checkOrders', [ModuleController::class, 'checkFinedineOrders']);
Route::post('finedineapp/getUsers', [ModuleController::class, 'loadfinedineappusers']);
Route::post('finedineapp/getVersion', function () {
  return env('APP_VERSION', '2025.04.30.1');
});
Route::post('finedineapp/checkconnection', function () {
  return json_encode(['status' => true]);
});

Route::middleware([checkFinedineAppHeader::class])->group(function () {
  Route::post('finedineapp/app', [ModuleController::class, 'sbcfinedineapp']);
});
Route::post('reportgen', [ModuleController::class, 'generatereport']);
Route::post('logsetcenter', [ModuleController::class, 'logsetcenter']);
Route::get('checkconnection', function () {
  return json_encode(['status' => true]);
});

Route::post('d56b699830e77ba53855679cb1d252da', [ModuleController::class, 'login']);
Route::get('sendmail', [ModuleController::class, 'sendmail']);
Route::middleware([checkPayrollHeader::class])->group(function () {
  Route::post('payrollapp/app', [Modulecontroller::class, 'sbcpayrollapp']);
});
Route::middleware([checkSbcRegHeader::class])->group(function () {
  Route::post('sbcappreg', [ModuleController::class, 'sbcappreg']);
});
Route::middleware([checkRoxasHeader::class])->group(function () {
  Route::post('sbcroxasuploader', [ModuleController::class, 'sbcroxasuploader']);
});
Route::middleware([checkSbcATIHeader::class])->group(function () {
  Route::post('sbcatiapp', [ModuleController::class, 'sbcatiapp']);
});
Route::get('sbcmobilev2/checkconnection', function () {
  return json_encode(['status' => true]);
});
Route::middleware([checkSBCMobilev2Header::class])->group(function () {
  Route::post('sbcmobilev2/admin', [Modulecontroller::class, 'mobilev2']);
});
Route::post('sbcmobilev2/userLogin', [ModuleController::class, 'mobilev2userlogin']);
Route::post('sbcmobilev2/loadcenters', [Modulecontroller::class, 'mobilev2v2loadcenters']);
Route::post('sbcmobilev2/download', [Modulecontroller::class, 'mobilev2download']);
Route::post('sbcmobilev2/upload', [Modulecontroller::class, 'mobilev2upload']);
Route::get('sbcmobilev2/getTemplate', [Modulecontroller::class, 'mobilev2gettemplate']);
Route::post('sbcmobilev2/saveSignature', [Modulecontroller::class, 'mobilev2savesignature']);
Route::post('sbcmobilev2/updateEToken', [Modulecontroller::class, 'updateEToken']);
Route::post('sbcmobilev2/sendSampleNotif', [Modulecontroller::class, 'sendSampleNotif']);

Route::get('getFile', [ModuleController::class, 'getFile']);
Route::get('apitrans', [ModuleController::class, 'apitrans']);

Route::get('getEmailExcel', [ModuleController::class, 'getEmailExcel']);
Route::post('sendEmailExcel', [ModuleController::class, 'sendEmailExcel']);
Route::get('linkEmail', [ModuleController::class, 'linkEmail']);

Route::get('sendlinkEmail', [ModuleController::class, 'sendlinkEmail']);

Route::post('imageapi', [ModuleController::class, 'imageapi']);

Route::get('sample', function () {
  $waw = true;
  if ($waw == 'false') {
    var_dump(true);
  } else {
    var_dump(false);
  }
});