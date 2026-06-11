<?php
namespace App\Http\Classes\mobile\modules\ordering;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class reports2 {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['salestype', 'salesmonth', 'salesyear', 'refresh'];
    $cfHeadFields = $this->fieldClass->create($fields);
    data_set($cfHeadFields, 'refresh.action', 'loadChart');
    data_set($cfHeadFields, 'salestype.enterfunc', 'salestypeChange');
    data_set($cfHeadFields, 'salesmonth.enterfunc', 'salesmonthChange');
    data_set($cfHeadFields, 'salesyear.enterfunc', 'salesyearChange');
    data_set($cfHeadFields, 'refresh.func', 'loadChart');
    data_set($cfHeadFields, 'refresh.functype', 'module');
    return ['cfHeadFields'=>$cfHeadFields];
  }

  public function getFunc() {
    return '({
      docForm: { salestype: "", salesmonth: "", salesyear: "", salestypes: [], salesmonths: [], salesyears: []},
      loadYears: function () {
        let currentYear = new Date().getFullYear();
        let years = [];
        let startYear = currentYear - 5;  
        while (startYear <= currentYear) {
          years.push(startYear++);
        }
        return years;
      },
      loadChart: function () {
        console.log("loadChart called");
        for (var b in sbc.footerbuttons) sbc.footerbuttons[b].show = "false";
        const thiss = this;
        let yearnow = new Date().getFullYear();
        if (thiss.docForm.salestype === "") thiss.docForm.salestype = "Daily Sales";
        if (thiss.docForm.salesmonth === "") thiss.docForm.salesmonth = "January";
        if (thiss.docForm.salesyear === "") thiss.docForm.salesyear = yearnow;
        let salestype = "salesmonth";
        if (thiss.docForm.salestype === "Monthly Sales") salestype = "salesyear";
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        for (var i in sbc.cfheadfields) {
          if (sbc.cfheadfields[i].name === "salesmonth") {
            sbc.cfheadfields[i].options = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            sbc.cfheadfields[i].show = "false";
            if (thiss.docForm.salestype === "Daily Sales") sbc.cfheadfields[i].show = "true";
          } else if (sbc.cfheadfields[i].name === "salesyear") {
            sbc.cfheadfields[i].options = thiss.loadYears();
            sbc.cfheadfields[i].show = "false";
            if (thiss.docForm.salestype === "Monthly Sales") sbc.cfheadfields[i].show = "true";
          } else {
            sbc.cfheadfields[i].show = "true";
            sbc.cfheadfields[i].options = ["Daily Sales", "Monthly Sales"];
          }
          if (parseInt(i) + 1 === sbc.cfheadfields.length) {
            switch (thiss.docForm.salestype) {
              case "Monthly Sales":
                thiss.loadMonthlySales();
                break;
              default:
                thiss.loadDailySales();
                break;
            }
          }
        }
      },
      salestypeChange: function () {
        const thiss = this;
        let salestype = thiss.docForm.salestype === "Daily Sales" ? "salesmonth" : "salesyear";
        for (var i in sbc.cfheadfields) {
          if (sbc.cfheadfields[i].name === "salesmonth" || sbc.cfheadfields[i].name === "salesyear") sbc.cfheadfields[i].show = "false";
          if (sbc.cfheadfields[i].name === salestype) sbc.cfheadfields[i].show = "true";
        }
      },
      salesmonthChange: function () {},
      salesyearChange: function () {},
      loadDailySales: function () {
        console.log("loadDailySales called");
        const thiss = this;
        if (thiss.docForm.salesmonth !== "") {
          cfunc.getTableData("config", "serveraddr").then(serveraddr => {
            if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
              cfunc.showMsgBox("Server Address not set", "negative", "warning");
              return;
            }
            cfunc.showLoading();
            api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("loadDailySales"), month: thiss.docForm.salesmonth, year: thiss.docForm.salesyear, wh: storage.center.warehouse, username: storage.user.username }, { headers: sbc.reqheader }).then(res => {
              var date = new Date(), y = date.getFullYear(), m = date.getMonth();
              let lastd = new Date(y, m +1, 0).getDate();
              sbc.cOptions.xaxis.categories = [];
              console.log(res.data);
              sbc.cSeries[0].data = [];
              let categories = [];
              for (var a = 1; a <= lastd; a++) {
                // sbc.cOptions.xaxis.categories[sbc.cOptions.xaxis.categories.length] = a;
                categories.push(a);
                sbc.cSeries[0].data[sbc.cSeries[0].data.length] = a;
              }
              sbc.cOptions = {
                chart: {
                  id: "vuechart-example",
                  zoom: {
                    enabled: true,
                    type: "xy",
                    autoScaleYaxis: false,
                    zoomedArea: {
                      fill: {
                        color: "#90CAF9",
                        opacity: 0.4
                      },
                      stroke: {
                        color: "#0D47A1",
                        opacity: 0.4,
                        width: 1
                      }
                    }
                  }
                },
                fill: {
                  colors: ["#F44336", "#E91E63", "#9C27B0"]
                },
                dataLabels: {
                  style: {
                    colors: ["#000", "#000", "#000"]
                  },
                  formatter: function (value) {
                    let val = parseFloat(value);
                    val = (value / 1);
                    return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                  }
                },
                yaxis: {
                  labels: {
                    formatter: function (value) {
                      let val = parseFloat(value);
                      val = (value / 1);
                      return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                  }
                },
                xaxis: {
                  categories: Object.values(categories)
                }
              };
              let ser = sbc.cSeries[0].data;
              for (var m in sbc.cOptions.xaxis.categories) {
                ser[m] = 0;
                if (res.data.length > 0) {
                  for (var aa in res.data) {
                    if (parseInt(res.data[aa].dayonly) === (parseInt(m) + 1)) {
                      ser[m] = res.data[aa].sales;
                    }
                  }
                } else {
                  ser[m] = 0;
                }
              }
              sbc.cSeries = [{
                data: ser
              }];
              $q.loading.hide();
            }).catch(err => {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          });
        }
      },
      loadMonthlySales: function () {
        const thiss = this;
        if (thiss.docForm.salesyear !== "") {
          cfunc.getTableData("config", "serveraddr").then(serveraddr => {
            if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
              cfunc.showMsgBox("Server Address not set", "negative", "warning");
              return;
            }
            cfunc.showLoading();
            api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("loadMonthlySales"), year: thiss.docForm.salesyear, wh: storage.center.warehouse, username: storage.user.username }, { headers: sbc.reqheader }).then(res => {
              sbc.cSeries[0].data = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
              var ser = sbc.cSeries[0].data;
              sbc.cOptions = {
                chart: {
                  id: "vuechart-example",
                  zoom: {
                    enabled: true,
                    type: "xy",
                    autoScaleYaxis: false,
                    zoomedArea: {
                      fill: {
                        color: "#90CAF9",
                        opacity: 0.4
                      },
                      stroke: {
                        color: "#0D47A1",
                        opacity: 0.4,
                        width: 1
                      }
                    }
                  }
                },
                fill: {
                  colors: ["#F44336", "#E91E63", "#9C27B0"]
                },
                dataLabels: {
                  style: {
                    colors: ["#000", "#000", "#000"]
                  },
                  formatter: function (value) {
                    let val = parseFloat(value);
                    val = (value / 1);
                    return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                  }
                },
                yaxis: {
                  labels: {
                    formatter: function (value) {
                      let val = parseFloat(value);
                      val = (value / 1);
                      return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                  }
                },
                xaxis: {
                  categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]
                }
              };
              for (var m in sbc.cOptions.xaxis.categories) {
                if (res.data.length > 0) {
                  for (var a in res.data) {
                    if (res.data[a].mon === (parseInt(m) + 1)) {
                      ser[m] = res.data[a].sales;
                    }
                  }
                }
              }
              sbc.cSeries = [{
                data: ser
              }];
              console.log(ser);
              $q.loading.hide();
            }).catch(err => {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          });
        }
      }
    })';
  }
}