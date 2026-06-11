<?php

namespace App\Http\Classes\mobile\modules\timeinadminapp;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;
use Illuminate\Support\Facades\Storage;

class admin
{
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct()
  {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('appcompany', 'sbc');
    $img = '';
    if (Storage::disk('public')->exists('demo/nouserimg.jpg')) {
      $img = Storage::disk('public')->get('demo/nouserimg.jpg');
      $img = 'data:image/jpg;base64,'.base64_encode($img);
    }
    $this->nouserimg = $img;
  }
  public function getLayout()
  {
    $data = '';
    $image = '';
    if (Storage::disk('public')->exists('demo/sbclogo.png')) {
      $data = Storage::disk('public')->get('demo/sbclogo.png');
      $image = 'data:image/png;base64,' . base64_encode($data);
    }
    $fields = ['timeinimg', 'dateid', 'refresh'];
    $cfHeadFields = $this->fieldClass->create($fields);
    data_set($cfHeadFields, 'timeinimg.action', $image);
    data_set($cfHeadFields, 'timeinimg.style', 'width:200px;display:block;margin:auto;margin-bottom:50px;margin-top:20px;');
    data_set($cfHeadFields, 'refresh.label', 'load');
    data_set($cfHeadFields, 'refresh.func', 'loadUserLogs');

    $fields = ['tid', 'tname', 'ttimein', 'ttimeout'];
    $cfTableCols = $this->fieldClass->create($fields);

    // $cLookupHeadFields = [];
    // $fields = ['userimage'];
    // $tImageLookupHeadFields = $this->fieldClass->create($fields);
    // array_push($cLookupHeadFields, ['form' => 'tImageLookupHeadFields', 'fields' => $tImageLookupHeadFields]);
    $inputLookupFields = [];
    $fields = ['inpic', 'outpic'];
    $tImageLookupFields = $this->fieldClass->create($fields);
    data_set($tImageLookupFields, 'inpic.style', 'width:100%;min-width:100%;');
    data_set($tImageLookupFields, 'outpic.style', 'width:100%;min-width:100%;');
    array_push($inputLookupFields, ['form'=>'tImageLookupFields', 'fields'=>$tImageLookupFields]);

    $inputLookupButtons = [];
    $btns = ['cancelrecord'];
    $tImageLookupButtons = $this->buttonClass->create($btns);
    data_set($tImageLookupButtons, 'cancelrecord.label', 'Close');
    data_set($tImageLookupButtons, 'cancelrecord.form', 'tImageLookupButtons');
    data_set($tImageLookupButtons, 'cancelrecord.func', 'closeUserImg');
    data_set($tImageLookupButtons, 'cancelrecord.functype', 'module');
    array_push($inputLookupButtons, ['form'=>'tImageLookupButtons', 'btns'=>$tImageLookupButtons]);

    return ['cfHeadFields' => $cfHeadFields, 'cfTableCols' => $cfTableCols, 'inputLookupFields' => $inputLookupFields, 'inputLookupButtons' => $inputLookupButtons];
  }

  public function getFunc()
  {
    return '({
      nouserimg: "'.$this->nouserimg.'",
      docForm: { dateid: "" },
      docData: [],
      tableData: [],
      loadUserLogs: function () {
        if (sbc.modulefunc.docForm.dateid === "" || sbc.modulefunc.docForm.dateid === null) {
          cfunc.showMsgBox("Please enter date", "negative", "warning");
          return;
        }
        cfunc.showLoading();
        cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
          api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("getUserLogs"), date: sbc.modulefunc.docForm.dateid }, { headers: sbc.reqheader }).then(res => {
            sbc.modulefunc.tableData = [];
            if (res.data.logs.length > 0) {
              let hasrecord = false;
              for (var l in res.data.logs) {
                let waw = sbc.modulefunc.tableData.findIndex((x) => x.id === res.data.logs[l].email);
                if (waw === -1) {
                  sbc.modulefunc.tableData.push({
                    id: res.data.logs[l].email,
                    name: res.data.logs[l].name,
                    timein: res.data.logs[l].mode === "IN" ? res.data.logs[l].timeinout : "",
                    timeout: res.data.logs[l].mode === "OUT" ? res.data.logs[l].timeinout : "",
                    mode: res.data.logs[l].mode,
                    clientid: res.data.logs[l].clientid
                  });
                } else {
                  if (res.data.logs[l].mode === "IN") {
                    sbc.modulefunc.tableData[waw].timein = res.data.logs[l].timeinout;
                  } else {
                    sbc.modulefunc.tableData[waw].timeout = res.data.logs[l].timeinout;
                  }
                }
                if (parseInt(l) + 1 === res.data.logs.length) {
                  sbc.globalFunc.cfTableClick = true;
                  sbc.globalFunc.cfTableClickFunc = "clickLogs";
                  sbc.globalFunc.cfTableClickFunctype = "module";
                  cfunc.showLoading("Logs loaded", "positive");
                  $q.loading.hide();
                }
              }
            } else {
              cfunc.showLoading("Logs empty", "negative", "warning");
              $q.loading.hide();
            }
            $q.loading.hide();
          }).catch(err => {
            cfunc.showMsgBox("getUserLogs error1: " + err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      loadTableData: function () {
        cfunc.getTableDataCount("useraccess2").then(ucount => {
          if (ucount === 0) {
            $q.dialog({
              message: "User Account empty. Do you want to download now?",
              ok: { flat: true, color: "primary" },
              cancel: { flat: true, color: "negative" }
            }).onOk(() => {
              sbc.globalFunc.downloadTimeinAccounts();
            });
          }
        });
        sbc.isFormEdit = true;
        const remember = $q.localStorage.getItem("sbc_rememberUser");
        if (remember !== null) sbc.modulefunc.docForm = { idno: remember.idno, email: remember.email, password: remember.password, remember: true };
        for (var x in sbc.cfheadfields) {
          if (sbc.cfheadfields[x].name === "timeinorout") {
            sbc.cfheadfields[x].options = ["Time In", "Time Out"];
            sbc.modulefunc.docForm.timeinorout = "Time In";
          }
        }
      },
      clickLogs: function (row, index) {
        console.log("-----------clickLogs called: ", row, "----", index);
        cfunc.showLoading();
        cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
          api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("getUserImageLog"), date: sbc.modulefunc.docForm.dateid, user: row }, { headers: sbc.reqheader }).then(res => {
            if (res.data.status) {
            } else {
              cfunc.showMsgBox(res.data.msg, "negative", "warning");
              $q.loading.hide();
            }
            if (res.data.inpic === "" && res.data.outpic === "") cfunc.showMsgBox(res.data.msg, "negative", "warning");
            sbc.modulefunc.inputLookupForm = { inpic: res.data.inpic, outpic: res.data.outpic };
            sbc.globalFunc.inputLookupClass = "full-height";
            sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "tImageLookupFields", "inputFields");
            sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "tImageLookupFields", "inputPlot");
            sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "tImageLookupButtons", "buttons");
            // sbc.isFormEdit = true;
            sbc.inputLookupTitle = row.name;
            sbc.showInputLookup = true;
            // sbc.cLookupTitle = "User Logs";
            // sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "tImageLookupHeadFields", "inputFields");
            // sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "tImageLookupHeadFields", "inputPlot");
            // sbc.showCustomLookup = true;
            // sbc.modulefunc.docForm.userimage = res.data.picture;
            // sbc.modulefunc.cLookupForm = sbc.modulefunc.docForm;
            $q.loading.hide();
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      closeUserImg: function () {
        sbc.modulefunc.inputLookupForm.userimage = "";
        sbc.showInputLookup = false;
      }
    })';
  }
}
