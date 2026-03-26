<?php
namespace App\Http\Classes\mobile\modules\collection;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class colstat {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['colstatlabel'];
    $cfHeadFields = $this->fieldClass->create($fields);
    return ['cfHeadFields'=>$cfHeadFields];
  }

  public function getFunc() {
    return '({
      docForm: { colstatlabel: "" },
      loadTableData: function () {
        cfunc.showLoading();
        // let clientAreas = [];
        let clientAreas = "";
        sbc.db.transaction(function (tx) {
          tx.executeSql("select clientid, phase, section from clientarea", [], function (tx, areaRes) {
            if (areaRes.rows.length > 0) {
              for (var x = 0; x < areaRes.rows.length; x++) {
                sbc.modulefunc.getCollectionStatus(areaRes.rows.item(x).phase, areaRes.rows.item(x).section).then(csRes => {
                  // clientAreas.push(csRes);
                  clientAreas += "<b>" + csRes.area + "</b><br>\
                  Rent (" + csRes.rent + "/" + csRes.totalRCount + ")<br>\
                  CUSA (" + csRes.cusa + "/" + csRes.totalCCount + ")<br>\
                  Electricity (" + csRes.elec + ")<br>\
                  Water (" + csRes.water + ")<br>\
                  Others (" + csRes.others + ")<br>\
                  Ambulant (" + csRes.amb + ")<br>";
                  sbc.modulefunc.docForm.colstatlabel += clientAreas;
                });
              }
              $q.loading.hide();
            }
          });
        });
      },
      getCollectionStatus: function (phase, section) {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            var data = { area: phase + "-" + section, totalRCount: 0, totalCCount: 0, rent: 0, cusa: 0, elec: 0, water: 0, others: 0, amb: 0 };
            tx.executeSql("select clientid, dailyrent, dcusa from client where phase=? and section=?", [phase, section], function (tx, res) {
              if (res.rows.length > 0) {
                data.rent = 0;
                for (var x = 0; x < res.rows.length; x++) {
                  if (res.rows.item(x).dailyrent > 0) data.totalRCount += 1;
                  if (res.rows.item(x).dcusa > 0) data.totalCCount += 1;
                  tx.executeSql("select line, type from dailycollection where clientid=?", [res.rows.item(x).clientid], function (tx, ress) {
                    if (ress.rows.length > 0) {
                      for (var xx = 0; xx < ress.rows.length; xx++) {
                        switch (ress.rows.item(xx).type) {
                          case "R": data.rent += 1; break;
                          case "C": data.cusa += 1; break;
                          case "E": data.elec += 1; break;
                          case "W": data.water += 1; break;
                          case "O": data.others += 1; break;
                          case "AMB": data.amb += 1; break;
                        }
                      }
                    }
                  });
                }
                resolve(data);
              } else {
                resolve(data);
              }
            }, function (tx, err) {
              reject(err);
            });
          }, function (err) {
            reject(err);
          });
        });
      }
    })';
  }
}