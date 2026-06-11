<?php

namespace App\Http\Classes\mobile\modules\timeinapp;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;
use Illuminate\Support\Facades\Storage;

class timeinout
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
    switch ($this->company) {
      case 'sbc2':
        $fields = ['timeinimg', 'timeinorout', 'email', 'scanid', 'login'];
        $cfHeadFields = $this->fieldClass->create($fields);
        data_set($cfHeadFields, 'timeinimg.action', $image);
        data_set($cfHeadFields, 'timeinimg.style', 'width:200px;display:block;margin:auto;margin-bottom:50px;margin-top:20px;');
        data_set($cfHeadFields, 'email.style', 'margin-top:40px;font-size:150%;');
        data_set($cfHeadFields, 'email.label', 'ID');
        data_set($cfHeadFields, 'email.dense', false);
        data_set($cfHeadFields, 'email.enterfunc', 'loginAccount2');
        data_set($cfHeadFields, 'email.functype', 'module');
        data_set($cfHeadFields, 'timeinorout.style', 'font-size:150%;');
        data_set($cfHeadFields, 'timeinorout.dense', false);
        data_set($cfHeadFields, 'login.style', 'font-size:150%;margin-top:40px;margin-bottom:20px;');
        data_set($cfHeadFields, 'login.func', 'loginAccount2');
        break;
      default:
        $fields = ['timeinimg', 'email', 'password', 'remember', 'login'];
        $cfHeadFields = $this->fieldClass->create($fields);
        data_set($cfHeadFields, 'timeinimg.action', $image);
        data_set($cfHeadFields, 'timeinimg.style', 'width:200px;display:block;margin:auto;margin-bottom:50px;margin-top:20px;');
        data_set($cfHeadFields, 'password.type', 'password');
        data_set($cfHeadFields, 'password.label', 'Enter Password');
        data_set($cfHeadFields, 'password.readonly', false);
        break;
    }

    $cLookupHeadFields = [];
    $fields = ['startdate', 'enddate', 'refresh'];
    $logsHeadFields = $this->fieldClass->create($fields);
    data_set($logsHeadFields, 'refresh.label', 'load logs');
    data_set($logsHeadFields, 'refresh.func', 'refreshLogs');
    data_set($logsHeadFields, 'refresh.functype', 'module');
    array_push($cLookupHeadFields, ['form' => 'logsHeadFields', 'fields' => $logsHeadFields]);

    $cLookupButtons = [];
    $btns = ['saverecord', 'cancelrecord', 'deleterecord'];
    $logsButtons = $this->buttonClass->create($btns);
    data_set($logsButtons, 'saverecord.func', 'uploadLogs');
    data_set($logsButtons, 'saverecord.functype', 'module');
    data_set($logsButtons, 'saverecord.label', 'Upload');
    data_set($logsButtons, 'saverecord.icon', 'upload');
    data_set($logsButtons, 'cancelrecord.func', 'cancelLogs');
    data_set($logsButtons, 'cancelrecord.functype', 'module');
    data_set($logsButtons, 'cancelrecord.label', 'Cancel');
    data_set($logsButtons, 'cancelrecord.icon', 'close');
    data_set($logsButtons, 'deleterecord.func', 'clearLogs');
    data_set($logsButtons, 'deleterecord.functype', 'module');
    data_set($logsButtons, 'deleterecord.label', 'Clear Logs');
    array_push($cLookupButtons, ['form' => 'logsButtons', 'btns' => $logsButtons]);

    $inputLookupFields = [];
    $inputLookupButtons = [];
    $fields = ['userimage', 'usercapimage'];
    $timeinoutFields = $this->fieldClass->create($fields);
    // data_set($timeinoutFields, 'userimage.style', 'aspect-ratio:0.25/0.5;');
    // data_set($timeinoutFields, 'usercapimage.style', 'aspect-ratio:0.25/0.5;');
    data_set($timeinoutFields, 'userimage.style', 'width:50%;height:auto;margin-left:25%;');
    data_set($timeinoutFields, 'usercapimage.style', 'width:50%;height:auto;margin-left:25%;margin-top:10px;');
    array_push($inputLookupFields, ['form' => 'timeinoutFields', 'fields' => $timeinoutFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $timeinoutButtons = $this->buttonClass->create($btns);
    data_set($timeinoutButtons, 'saverecord.func', 'contLog');
    data_set($timeinoutButtons, 'saverecord.functype', 'module');
    data_set($timeinoutButtons, 'saverecord.label', 'Continue');
    data_set($timeinoutButtons, 'cancelrecord.func', 'cancelLog');
    data_set($timeinoutButtons, 'cancelrecord.functype', 'module');
    data_set($timeinoutButtons, 'cancelrecord.label', 'Cancel');
    array_push($inputLookupButtons, ['form' => 'timeinoutButtons', 'btns' => $timeinoutButtons]);

    $fields = ['lbllogs'];
    $logsErrLookup = $this->fieldClass->create($fields);
    array_push($inputLookupFields, ['form' => 'logsErrLookupFields', 'fields' => $logsErrLookup]);

    $btns = ['cancelrecord'];
    $logsErrLookupBtn = $this->buttonClass->create($btns);
    data_set($logsErrLookupBtn, 'cancelrecord.func', 'cancelErrLogs');
    data_set($logsErrLookupBtn, 'cancelrecord.functype', 'module');
    data_set($logsErrLookupBtn, 'cancelrecord.label', 'cancel');
    array_push($inputLookupButtons, ['form' => 'logsErrLookupBtn', 'btns' => $logsErrLookupBtn]);

    $cLookupTableCols = [];
    $fields = ['tdateid', 'tid', 'tname', 'ttimein', 'tstatus', 'tuploaddate', 'ttimeout', 'tstatus2', 'tuploaddate2'];
    $logsTableCols = $this->fieldClass->create($fields);
    array_push($cLookupTableCols, ['form' => 'logsTableCols', 'fields' => $logsTableCols]);

    return ['cfHeadFields' => $cfHeadFields, 'cLookupHeadFields' => $cLookupHeadFields, 'cLookupButtons' => $cLookupButtons, 'cLookupTableCols' => $cLookupTableCols, 'inputLookupFields' => $inputLookupFields, 'inputLookupButtons' => $inputLookupButtons];
  }

  public function getFunc()
  {
    return '({
      nouserimg: "'.$this->nouserimg.'",
      docForm: { idno: "", email: "", password: "", remember: false, timeinorout: [] },
      docData: [],
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
      loginAccount2: function () {
        console.log("loginaccount2 called");
        if (sbc.modulefunc.docForm.email === "") {
          cfunc.showMsgBox("Please enter ID", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.docForm.email === 0 || sbc.modulefunc.docForm.email === "0") {
          cfunc.showMsgBox("Invalid ID", "negative", "warning");
          return;
        }
        const regex = /[(]/;
        if (regex.test(sbc.modulefunc.docForm.email)) {
          console.log("id contains parenthesis");
          let id = sbc.modulefunc.docForm.email.split("(");
          contFunc(id[0].trim());
        } else {
          contFunc(sbc.modulefunc.docForm.email);
        }
        
        function contFunc(idbarcode) {
          cfunc.showLoading();
          let datenow = cfunc.getDateTime("date");
          let timenow = cfunc.getDateTime("time");
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from useraccess2 where cast(email as text)=? and isactive=1", [idbarcode.toString()], function (tx, res) {
              if (res.rows.length > 0) {
                let id = res.rows.item(0).id;
                let name = res.rows.item(0).name;
                let data = { id: res.rows.item(0).id, name: res.rows.item(0).name, datenow: datenow, timenow: timenow, idno: res.rows.item(0).email };
                tx.executeSql("select * from log where id=? and dateid=?", [id, datenow], function (tx, res2) {
                  if (res2.rows.length > 0) {
                    data.line = res2.rows.item(0).line;
                    if (sbc.modulefunc.docForm.timeinorout === "Time In") {
                      if (res2.rows.item(0).timein !== null) {
                        cfunc.showMsgBox("Already timed-in", "negative", "warning");
                        $q.loading.hide();
                      } else {
                        sbc.modulefunc.timeIn(data);
                      }
                    } else {
                      if (res2.rows.item(0).timeout !== null) {
                        cfunc.showMsgBox("Already timed-out", "negative", "warning");
                        $q.loading.hide();
                      } else {
                        sbc.modulefunc.timeOut(data);
                      }
                    }
                  } else {
                    data.line = 0;
                    if (sbc.modulefunc.docForm.timeinorout === "Time In") {
                      sbc.modulefunc.timeIn(data);
                    } else {
                      sbc.modulefunc.timeOut(data);
                    }
                  }
                });
              } else {
                cfunc.showMsgBox("Invalid ID", "negative", "warning");
                if (sbc.modulefunc.loginType === "scan") sbc.modulefunc.docForm.email = "";
                $q.loading.hide();
              }
            });
          });
        }
      },
      loginAccount: function () {
        if (sbc.modulefunc.docForm.email === "") {
          cfunc.showMsgBox("Please enter email address", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.docForm.password === "") {
          cfunc.showMsgBox("Please enter Password", "negative", "warning");
          return;
        }
        cfunc.showLoading();
        let datenow = cfunc.getDateTime("date");
        let timenow = cfunc.getDateTime("time");
        sbc.db.transaction(function (tx) {
          tx.executeSql("select * from useraccess2 where email=? and password=? and isactive=1", [sbc.modulefunc.docForm.email, sbc.modulefunc.docForm.password], function (tx, res) {
            if (res.rows.length > 0) {
              let id = res.rows.item(0).id;
              let name = res.rows.item(0).name;
              let data = { id: res.rows.item(0).id, name: res.rows.item(0).name, datenow: datenow, timenow: timenow, idno: res.rows.item(0).email };
              tx.executeSql("select * from log where id=? and dateid=?", [id, datenow], function (tx, res2) {
                if (res2.rows.length > 0) {
                  let timein = res2.rows.item(0).timein;
                  let timeout = res2.rows.item(0).timeout;
                  let line = res2.rows.item(0).line;
                  data.line = res2.rows.item(0).line;
                  if (timein !== null && timeout === null) {
                    $q.loading.hide();
                    $q.dialog({
                      title: "Time-Out - " + name,
                      message: "Are you sure you want to time-out? (" + timenow + ")",
                      ok: { flat: true, color: "green-9" },
                      cancel: { flat: true, color: "negative" }
                    }).onOk(() => {
                      sbc.modulefunc.timeOut(data);
                    });
                  } else if (timein === null && timeout === null) {
                    sbc.modulefunc.timeIn(data);
                  } else {
                    cfunc.showMsgBox("Already timed-in/out.", "negative", "warning");
                    $q.loading.hide();
                  }
                } else {
                  sbc.modulefunc.timeIn(data);
                }
              }, function (tx, err) {
                $q.loading.hide();
                console.log(err);
              });
            } else {
              cfunc.showMsgBox("Invalid password", "negative", "warning");
              $q.loading.hide();
            }
          });
        });
      },
      timeOut: function (data) {
        cfunc.showLoading();
        sbc.modulefunc.captureImage().then(img => {
          data.img = img;
          data.logType = "timeout";
          sbc.modulefunc.docData = data;
          if (sbc.globalFunc.company === "sbc2") {
            sbc.modulefunc.getUserImage().then(uimg => {
              let uimg2 = uimg;
              if (uimg === "" || uimg === undefined || uimg === null) uimg2 = sbc.modulefunc.nouserimg;
              sbc.modulefunc.inputLookupForm = { userimage: uimg2, usercapimage: "data:image/jpeg;base64," + img };
              sbc.showInputLookup = true;
              sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "timeinoutFields", "inputFields");
              sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "timeinoutFields", "inputPlot");
              sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "timeinoutButtons", "buttons");
              sbc.inputLookupTitle = data.name;
              sbc.globalFunc.inputLookupClass = "full-height";
              $q.loading.hide();
            });
          } else {
            sbc.modulefunc.contTimeout();
          }
        }).catch(err => {
          cfunc.showMsgBox(err.message, "negative", "warning");
          $q.loading.hide();
        });
      },
      contTimeout: function () {
        cfunc.showLoading();
        sbc.modulefunc.saveLog(sbc.modulefunc.docData, true).then(res => {
          sbc.modulefunc.saveRemember(sbc.modulefunc.docData);
          sbc.modulefunc.uploadLog(sbc.modulefunc.docData, "timeout").then(res2 => {
            if (res2.data.status) {
              cfunc.showMsgBox("Time-Out success", "positive");
              sbc.modulefunc.setUploadedLog(sbc.modulefunc.docData.id, res2.data.date, "timeout");
            } else {
              cfunc.showMsgBox("Network error, Record saved locally", "negative", "warning");
            }
            sbc.modulefunc.docForm.idno = "";
            sbc.modulefunc.docForm.email = "";
            sbc.modulefunc.docForm.password = "";
            $q.loading.hide();
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      contTimein: function () {
        cfunc.showLoading();
        sbc.modulefunc.saveLog(sbc.modulefunc.docData).then(slres => {
          sbc.modulefunc.saveRemember(sbc.modulefunc.docData);
          sbc.modulefunc.uploadLog(sbc.modulefunc.docData, "timein").then(res => {
            if (res.data.status) {
              cfunc.showMsgBox("Time-In success", "positive");
              sbc.modulefunc.setUploadedLog(slres.insertId, res.data.date, "timein");
            } else {
              cfunc.showMsgBox("Network error, Record saved locally", "negative", "warning");
            }
            sbc.modulefunc.docForm.email = "";
            sbc.modulefunc.docForm.password = "";
            sbc.modulefunc.docForm.idno = "";
            $q.loading.hide();
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        }).catch(err => {
          cfunc.showMsgBox(err.message + " Record saved locally", "negative", "warning");
          $q.loading.hide();
        });
      },
      timeIn: function (data) {
        cfunc.showLoading();
        console.log("-----------timein");
        sbc.modulefunc.captureImage().then(img => {
          data.img = img;
          data.logType = "timein";
          sbc.modulefunc.docData = data;
          if (sbc.globalFunc.company === "sbc2") {
            sbc.modulefunc.getUserImage().then(uimg => {
              let uimg2 = uimg;
              if (uimg === "" || uimg === undefined || uimg === null) uimg2 = sbc.modulefunc.nouserimg;
              sbc.modulefunc.inputLookupForm = { userimage: uimg2, usercapimage: "data:image/jpeg;base64," + img };
              console.log("--------------------asd", sbc.modulefunc.inputLookupForm);
              sbc.showInputLookup = true;
              sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "timeinoutFields", "inputFields");
              sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "timeinoutFields", "inputPlot");
              sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "timeinoutButtons", "buttons");
              sbc.inputLookupTitle = data.name;
              sbc.globalFunc.inputLookupClass = "full-height";
              $q.loading.hide();
            }).catch(err => {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          } else {
            sbc.modulefunc.contTimein();
          }
        }).catch(err => {
          cfunc.showMsgBox(err, "negative", "warning");
          $q.loading.hide();
        });
      },
      getUserImage: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select img from userimg where id=?", [sbc.modulefunc.docData.id], function (tx, res) {
              if (res.rows.length > 0) {
                resolve(res.rows.item(0).img);
              } else {
                resolve("");
              }
            }, function (tx, err) {
              reject(err);
            });
          }, function (err) {
            reject(err);
          });
        });
      },
      captureImage: function () {
        return new Promise((resolve, reject) => {
          navigator.camera.getPicture(
            data => {
              resolve(data);
            },
            () => {
              reject("Could not access device camera/Image capture cancelled.");
            },
            {
              destinationType: Camera.DestinationType.DATA_URL,
              cameraDirection: Camera.Direction.BACK,
              encodingType: Camera.EncodingType.JPEG,
              correctOrientation: true
            }
          )
        });
      },
      saveLog: function (data, isupdate = false) {
        return new Promise((resolve, reject) => {
          let sitelocation = "";
          if ($q.localStorage.has("siteLocation")) sitelocation = $q.localStorage.getItem("siteLocation");
          sbc.db.transaction(function (tx) {
            switch (sbc.globalFunc.company) {
              case "sbc2":
                if (data.line === 0) {
                  let iqry = "";
                  let idata = [];
                  if (sbc.modulefunc.docForm.timeinorout === "Time In") {
                    iqry = "insert into log(id, dateid, timein, loginPic, sitelocation) values(?, ?, ?, ?, ?)";
                    idata = [data.id, data.datenow, data.timenow, data.img, sitelocation];
                  } else {
                    iqry = "insert into log(id, dateid, timeout, logoutPic, siteLocation) values(?, ? ,? ,? ,?)";
                    idata = [data.id, data.datenow, data.timenow, data.img, sitelocation];
                  }
                  tx.executeSql(iqry, idata, function (tx, res) {
                    resolve(res);
                  }, function (tx, err) {
                    reject(err.message);
                  });
                } else {
                  let uqry = "";
                  let udata = [data.timenow, data.img, data.line];
                  if (sbc.modulefunc.docForm.timeinorout === "Time In") {
                    uqry = "update log set timein=?, loginPic=? where line=?";
                  } else {
                    uqry = "update log set timeout=?, logoutPic=? where line=?";
                  }
                  tx.executeSql(uqry, udata, function (tx, res) {
                    resolve(res);
                  }, function (tx, err) {
                    reject(err.message);
                  });
                }
                break;
              default:
                if (isupdate) {
                  tx.executeSql("update log set timeout=?, logoutPic=? where line=?", [data.timenow, data.img, data.line], function (tx, res) {
                    resolve("done");
                  }, function (tx, err) {
                    reject(err.message);
                  });
                } else {
                  let lqry = "insert into log(id, dateid, timein, loginPic) values(?, ?, ?, ?)";
                  let ldata = [data.id, data.datenow, data.timenow, data.img];
                  if (sbc.globalFunc.company === "sbc2") {
                    lqry = "insert into log(id, dateid, timein, loginPic, sitelocation) values(?, ?, ?, ?, ?)";
                    ldata = [data.id, data.datenow, data.timenow, data.img, sitelocation];
                  }
                  tx.executeSql(lqry, ldata, function (tx, res) {
                    resolve(res);
                  }, function (tx, err) {
                    reject(err.message);
                  });
                }
                break;
            }
          });
        });
      },
      saveRemember: function (data) {
        if (sbc.modulefunc.docForm.remember) {
          const user = { id: data.id, email: sbc.modulefunc.docForm.email, password: sbc.modulefunc.docForm.password };
          $q.localStorage.set("sbc_rememberUser", user);
        }
      },
      uploadLog: function (data2, type) {
        return new Promise((resolve, reject) => {
          let data = { dateid: data2.datenow, time: data2.timenow, pic: data2.img, id: data2.id, type: type };
          if (sbc.globalFunc.company === "sbc2") {
            let siteLocation = "";
            if ($q.localStorage.has("siteLocation")) siteLocation = $q.localStorage.getItem("siteLocation");
            data.siteLocation = siteLocation;
          }
          cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
            api.post(serveraddr + "/sbcmobilev2/upload", { id: md5("uploadTimeinoutLog"), data: data }).then(res => {
              resolve(res);
            }).catch(err => {
              reject(err);
            });
          });
        });
      },
      setUploadedLog: function (line, date = "", type = "") {
        sbc.db.transaction(function (tx) {
          let line2 = line;
          let qry = "update log set isok=1, uploaddate=? where line=?";
          if (type === "timeout") {
           qry = "update log set isok2=1, uploaddate2=? where line=?";
           line2 = sbc.modulefunc.docData.line;
          }
          tx.executeSql(qry, [date, line2], function (tx, res) {
            console.log("setUploadedLog success, ", type);
          }, function (tx, err) {
            console.log("setUploadedLog error: ", err);
          });
        });
      },
      loadLogs: function () {
        console.log("loadLogs called");
        sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "logsHeadFields", "inputFields");
        sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "logsHeadFields", "inputPlot");
        sbc.selclookupbuttons = sbc.globalFunc.getLookupForm(sbc.clookupbuttons, "logsButtons", "buttons");
        sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, "logsTableCols", "inputFields");
        sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, "logsTableCols", "inputPlot");
        sbc.showCustomLookup = true;
        sbc.cLookupTitle = "Logs";
        sbc.modulefunc.lookupTableData = [];
        let date = new Date();
        let fday = new Date(date.getFullYear(), date.getMonth(), 1);
        let lday = new Date(date.getFullYear(), date.getMonth() + 1, 0);
        let date1 = fday.getFullYear() + "/" + String(fday.getMonth() + 1).padStart(2, "0") + "/" + String(fday.getDate()).padStart(2, "0");
        let date2 = lday.getFullYear() + "/" + String(lday.getMonth() + 1).padStart(2, "0") + "/" + String(lday.getDate()).padStart(2, "0");
        sbc.modulefunc.cLookupForm = { startdate: date1, enddate: date2 };
        sbc.isFormEdit = true;
      },
      refreshLogs: function () {
        console.log("refreshLogs called");
        if (sbc.modulefunc.cLookupForm.startdate === "") {
          cfunc.showMsgBox("Please enter start date to continue", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.cLookupForm.enddate === "") {
          cfunc.showMsgBox("Please enter end date to continue", "negative", "warning");
          return;
        }
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select log.*, u.name from log left join useraccess2 as u on u.id=log.id where log.dateid>=? and log.dateid<=? order by line asc", [sbc.modulefunc.cLookupForm.startdate, sbc.modulefunc.cLookupForm.enddate], function (tx, res) {
            sbc.modulefunc.lookupTableData = [];
            if (res.rows.length > 0) {
              let timein = "";
              let timeout = "";
              let status = "";
              let status2 = "";
              for (var x = 0; x < res.rows.length; x++) {
                timeout = res.rows.item(x).timeout;
                timein = res.rows.item(x).timein;
                if (timein === "null") timein = "";
                if (timeout === "null") timeout = "";
                if (res.rows.item(x).isok) status = "Uploaded";
                if (res.rows.item(x).isok2) status2 = "Uploaded";
                sbc.modulefunc.lookupTableData.push({
                  line: res.rows.item(x).line,
                  id: res.rows.item(x).id,
                  name: res.rows.item(x).name,
                  dateid: res.rows.item(x).dateid,
                  timein: timein,
                  timeout: timeout,
                  inPic: res.rows.item(x).loginPic,
                  outPic: res.rows.item(x).logoutPic,
                  isok: res.rows.item(x).isok,
                  isok2: res.rows.item(x).isok2,
                  uploaddate: res.rows.item(x).uploaddate,
                  status: status,
                  status2: status2,
                  uploaddate2: res.rows.item(x).uploaddate2
                });
              }
            }
            $q.loading.hide();
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        }, function (err) {
          console.log("refreshlogs error", err);
          cfunc.showMsgBox(err.message, "negative", "warning");
        });
      },
      uploadLogs: function () {
        console.log("uploadLogs called");
        let hasUploaded = false;
        let datas = [];
        if (sbc.modulefunc.lookupTableData.length > 0) {
          for (var d in sbc.modulefunc.lookupTableData) {
            console.log("---------------uploadLogs:", sbc.modulefunc.lookupTableData);
            if (sbc.modulefunc.lookupTableData[d].isok) {
              hasUploaded = true;
            }
            if ((sbc.modulefunc.lookupTableData[d].isok === 0 || sbc.modulefunc.lookupTableData[d].isok === null || sbc.modulefunc.lookupTableData[d].isok === undefined) || (sbc.modulefunc.lookupTableData[d].isok2 === 0 || sbc.modulefunc.lookupTableData[d].isok2 === null || sbc.modulefunc.lookupTableData[d].isok2 === undefined)) {
              datas.push(sbc.modulefunc.lookupTableData[d]);
            }
            if (parseInt(d) + 1 === sbc.modulefunc.lookupTableData.length) {
              if (hasUploaded) {
                if (datas.length === 0) {
                  sbc.globalFunc.showErrMsg("No record(s) to upload.");
                } else {
                  $q.dialog({
                    message: "There are records that`s already uploaded, do you want to continue?",
                    ok: { flat: true, color: "primary" },
                    cancel: { flat: true, color: "negative" }
                  }).onOk(() => {
                    contUpload();
                  });
                }
              } else {
                $q.dialog({
                  message: "Do you want to upload records?",
                  ok: { flat: true, color: "primary" },
                  cancel: { flat: true, color: "negative" }
                }).onOk(() => {
                  contUpload();
                });
              }
            }
          }
        } else {
          cfunc.showMsgBox("Nothing to upload", "negative", "warning");
        }

        function contUpload () {
          let siteLocation = "";
          let glogs = [];
          if (sbc.globalFunc.company === "sbc2") {
            if ($q.localStorage.has("siteLocation")) siteLocation = $q.localStorage.getItem("siteLocation");
            sbc.db.transaction(function (tx) {
              tx.executeSql("select * from guardlog where uploaddate is null", [], function (tx, res) {
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    glogs.push(res.rows.item(x));
                    if (parseInt(x) + 1 === res.rows.length) {
                      contFunc();
                    }
                  }
                } else {
                  contFunc();
                }
              });
            });
          } else {
            contFunc();
          }

          function contFunc () {
            cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
              cfunc.showLoading();
              api.post(serveraddr + "/sbcmobilev2/upload", { id: md5("uploadTimeinoutLogs"), data: datas, siteLocation: siteLocation, guardlogs: glogs }).then(res => {
                let errCount = 0;
                let errs = [];
                let uploaded = [];
                for (var a in res.data.data) {
                  if (res.data.data[a].success) {
                    uploaded.push(res.data.data[a].line);
                  } else {
                    errCount += 1;
                    errs.push(res.data.data[a].msg);
                  }
                }
                if (uploaded.length > 0) sbc.modulefunc.setUploaded(uploaded, res.data.date);
                if (sbc.globalFunc.company === "sbc2") sbc.modulefunc.setGuardUploaded(glogs);
                if (errCount > 0) {
                  sbc.showInputLookup = true;
                  let lbllogs = "";
                  for (var x in errs) {
                    lbllogs += errs[x] + "<br>";
                  }
                  sbc.modulefunc.inputLookupForm = { lbllogs: lbllogs };
                  sbc.inputLookupTitle = "";
                  sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "logsErrLookupFields", "inputFields");
                  sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "logsErrLookupFields", "inputPlot");
                  sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "logsErrLookupBtn", "buttons");
                  uploadRes = errs;
                  showUpRes = true
                } else {
                  cfunc.showMsgBox("Successfully uploaded.", "positive");
                  $q.loading.hide();
                }
                sbc.modulefunc.refreshLogs();
                $q.loading.hide();
              }).catch(err => {
                cfunc.showMsgBox(err.message, "negative", "warning");
                $q.loading.hide();
              });
            });
          }
        }
      },
      setGuardUploaded: function (glogs) {
        const datenow = cfunc.getDateTime("datetime");
        sbc.db.transaction(function (tx) {
          glogs.map(waw => {
            tx.executeSql("update guardlog set uploaddate=? where line=?", [datenow, waw.line]);
          });
        });
      },
      setUploaded: function (uploaded, date) {
        if (sbc.modulefunc.lookupTableData.length > 0) {
          sbc.db.transaction(function (tx) {
            for (var a in sbc.modulefunc.lookupTableData) {
              tx.executeSql("update log set isok=1, isok2=1, uploaddate=?, uploaddate2=? where line=?", [date, date, sbc.modulefunc.lookupTableData[a].line]);
            }
          });
        }
      },
      cancelLogs: function () {
        sbc.showCustomLookup = false;
      },
      contLog: function () {
        sbc.modulefunc.inputLookupForm = [];
        sbc.showInputLookup = false;
        if (sbc.modulefunc.docData.logType === "timein") {
          sbc.modulefunc.contTimein();
        } else {
          sbc.modulefunc.contTimeout();
        }
      },
      cancelLog: function () {
        sbc.showInputLookup = false;
      },
      cancelErrLogs: function () {
        sbc.showInputLookup = false;
      },
      clearLogs: function () {
        if (sbc.modulefunc.lookupTableData.length > 0) {
          $q.dialog({
            message: "Do you want to clear logs?",
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            cfunc.showLoading();
            sbc.db.transaction(function (tx) {
              tx.executeSql("delete from log where dateid>=? and dateid<=? and isok=1", [sbc.modulefunc.cLookupForm.startdate, sbc.modulefunc.cLookupForm.enddate], function (tx, res) {
                cfunc.showMsgBox("Logs cleared", "positive");
                sbc.modulefunc.refreshLogs();
                $q.loading.hide();
              }, function (tx, err) {
                cfunc.showMsgBox(err.message, "negative", "warning");
                $q.loading.hide();
              });
            });
          });
        } else {
          cfunc.showMsgBox("Logs empty", "negative", "warning");
        }
      },
      scantimeinoutid: function () {
        console.log("scantimeinoutid called");
        sbc.globalFunc.scanCallback().then(res => {
          const code = res.split("(");
          sbc.modulefunc.docForm.email = code[0].trim();
          sbc.modulefunc.loginType = "scan";
          sbc.modulefunc.loginAccount2();
        }).catch(err => {
          console.log("---=====---", err);
        });
      }
    })';
  }
}
