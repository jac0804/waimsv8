<?php

namespace App\Http\Classes\mobile;

class mobileCommonFunctions
{
  private $company;
  private $config;
  public function __construct()
  {
    $this->company = env('appcompany', 'mbs');
    $this->config = env('apptype', 'inventoryapp');
  }

  public function getModuleTemplate($layouts)
  {
    $docListHead = [];
    $docListHeadPlot = [];
    $headButtons = [];
    $headFields = [];
    $stockCols = [];
    $stockColsPlot = [];
    $stockButtons = [];
    $stockHeadButtons = [];
    $headFieldsPlot = [];
    $cfHeadButtons = [];
    $cfHeadFields = [];
    $cfHeadFieldsPlot = [];
    $cfTableHeadFields = [];
    $cfTableHeadFieldsPlot = [];
    $cfTableCols = [];
    $cfTableColsPlot = [];
    $cfTableButtons = [];
    $cfTableHeadButtons = [];
    $docListCols = [];
    $docListColsPlot = [];
    $inputLookupFields = [];
    $inputLookupFieldsPlot = [];
    $inputLookupButtons = [];
    $cLookupHeadFields = [];
    $cLookupHeadFieldsPlot = [];
    $cLookupTableCols = [];
    $cLookupTableColsPlot = [];
    $cLookupTableButtons = [];
    $cLookupFooterFields = [];
    $cLookupFooterFieldsPlot = [];
    $cLookupHeadButtons = [];
    $cLookupButtons = [];
    $editItemFields = [];
    $editItemFieldsPlot = [];
    $editItemButtons = [];
    $footerFields = [];
    $footerFieldsPlot = [];
    $checkoutFields = [];
    $checkoutFieldsPlot = [];
    $checkoutButtons = [];
    foreach ($layouts['layouts'] as $lkey => $l) {
      if (isset($l['inputLookupButtons'])) {
        foreach ($l['inputLookupButtons'] as $ilb) {
          foreach ($ilb['btns'] as $ilbkey => $ilb2) {
            $ilb2['doc'] = $lkey;
            $ilb2['form'] = $ilb['form'];
            array_push($inputLookupButtons, $ilb2);
          }
        }
      }
      if (isset($l['docListCols'])) {
        foreach ($l['docListCols'] as $dlckey => $dlc) {
          if ($dlckey == 'plot') {
            $plotfields3 = [];
            $plotfieldsmulti3 = "";
            foreach ($dlc as $dlcc) {
              if (gettype(dlcc) == "string") {
                array_push($plotfields3, $dlcc);
              } else {
                if (count($dlcc) > 1) {
                  foreach ($dlcc as $dlccc) {
                    if ($plotfieldsmulti3 != "") {
                      $plotfieldsmulti3 .= "," . $dlccc;
                    } else {
                      $plotfieldsmulti3 = $dlccc;
                    }
                  }
                  array_push($plotfields3, $plotfieldsmulti3);
                  $plotfieldsmulti3 = "";
                } else {
                  array_push($plotfields3, $dlcc);
                }
              }
            }
            array_push($docListColsPlot, ['doc' => $lkey, 'fields' => $plotfields3]);
            $plotfields3 = [];
          } else {
            $dlc['doc'] = $lkey;
            array_push($docListCols, $dlc);
          }
        }
      }
      if (isset($l['docListHead'])) {
        foreach ($l['docListHead'] as $dlhkey => $dlh) {
          if ($dlhkey == 'plot') {
            $plotfields2 = [];
            $plotfieldsmulti2 = "";
            foreach ($dlh as $dlhh) {
              if (gettype($dlhh) == "string") {
                array_push($plotfields2, $dlhh);
              } else {
                if (count($dlhh) > 1) {
                  foreach ($dlhh as $dlhhh) {
                    if ($plotfieldsmulti2 != "") {
                      $plotfieldsmulti2 .= "," . $dlhhh;
                    } else {
                      $plotfieldsmulti2 = $dlhhh;
                    }
                  }
                  array_push($plotfields2, $plotfieldsmulti2);
                  $plotfieldsmulti2 = "";
                } else {
                  array_push($plotfields2, $dlhh);
                }
              }
            }
            array_push($docListHeadPlot, ['doc' => $lkey, 'fields' => $plotfields2]);
            $plotfields2 = [];
          } else {
            $dlh['doc'] = $lkey;
            array_push($docListHead, $dlh);
          }
        }
      }
      if (isset($l['headButtons'])) {
        foreach ($l['headButtons'] as $hb) {
          $hb['doc'] = $lkey;
          array_push($headButtons, $hb);
        }
      }
      if (isset($l['cfHeadButtons'])) {
        foreach ($l['cfHeadButtons'] as $cfhb) {
          $cfhb['doc'] = $lkey;
          array_push($cfHeadButtons, $cfhb);
        }
      }
      if (isset($l['stockCols'])) {
        foreach ($l['stockCols'] as $sckey => $sc) {
          if ($sckey == 'plot') {
            $plotfields4 = [];
            $plotfieldsmulti4 = "";
            foreach ($sc as $scc) {
              if (gettype($scc) == "string") {
                array_push($plotfields4, $scc);
              } else {
                if (count($scc) > 1) {
                  foreach ($scc as $sccc) {
                    if ($plotfieldsmulti4 != "") {
                      $plotfieldsmulti4 .= "," . $sccc;
                    } else {
                      $plotfieldsmulti4 = $sccc;
                    }
                  }
                  array_push($plotfields4, $plotfieldsmulti4);
                  $plotfieldsmulti4 = "";
                } else {
                  array_push($plotfields4, $scc);
                }
              }
            }
            array_push($stockColsPlot, ['doc' => $lkey, 'fields' => $plotfields4]);
            $plotfields4 = [];
          } else {
            $sc['doc'] = $lkey;
            array_push($stockCols, $sc);
          }
        }
      }
      if (isset($l['headFields'])) {
        foreach ($l['headFields'] as $hfkey => $hf) {
          if ($hfkey == 'plot') {
            $plotfields = [];
            $plotfieldsmulti = "";
            foreach ($hf as $hff) {
              if (gettype($hff) == "string") {
                array_push($plotfields, $hff);
              } else {
                if (count($hff) > 1) {
                  foreach ($hff as $hfff) {
                    if ($plotfieldsmulti != "") {
                      $plotfieldsmulti .= "," . $hfff;
                    } else {
                      $plotfieldsmulti = $hfff;
                    }
                  }
                  array_push($plotfields, $plotfieldsmulti);
                  $plotfieldsmulti = "";
                } else {
                  array_push($plotfields, $hff);
                }
              }
            }
            array_push($headFieldsPlot, ['doc' => $lkey, 'fields' => $plotfields]);
            $plotfields = [];
          } else {
            $hf['doc'] = $lkey;
            array_push($headFields, $hf);
          }
        }
      }
      if (isset($l['footerFields'])) {
        foreach ($l['footerFields'] as $ffkey => $ff) {
          if ($ffkey == 'plot') {
            $plotfields11 = [];
            $plotfieldsmulti11 = "";
            foreach ($ff as $fff) {
              if (gettype($fff) == "string") {
                array_push($plotfields11, $fff);
              } else {
                if (count($fff) > 1) {
                  foreach ($fff as $ffff) {
                    if ($plotfieldsmulti11 != "") {
                      $plotfieldsmulti11 .= "," . $ffff;
                    } else {
                      $plotfieldsmulti11 = $ffff;
                    }
                  }
                  array_push($plotfields11, $plotfieldsmulti11);
                  $plotfieldsmulti11 = "";
                } else {
                  array_push($plotfields11, $fff);
                }
              }
            }
            array_push($footerFieldsPlot, ['doc' => $lkey, 'fields' => $plotfields11]);
            $plotfields11 = [];
          } else {
            $ff['doc'] = $lkey;
            array_push($footerFields, $ff);
          }
        }
      }
      if (isset($l['stockButtons'])) {
        foreach ($l['stockButtons'] as $sb) {
          $sb['doc'] = $lkey;
          array_push($stockButtons, $sb);
        }
      }
      if (isset($l['stockHeadButtons'])) {
        foreach ($l['stockHeadButtons'] as $shb) {
          $shb['doc'] = $lkey;
          array_push($stockHeadButtons, $shb);
        }
      }
      if (isset($l['cfTableHeadFields'])) {
        foreach ($l['cfTableHeadFields'] as $cfthfkey => $cfthf) {
          if ($cfthfkey == 'plot') {
            $plotfields13 = [];
            $plotfieldsmulti13 = '';
            foreach ($cfthf as $cfthff) {
              if (gettype($cfthff) == "string") {
                array_push($plotfields13, $cfthff);
              } else {
                if (count($cfthff) > 1) {
                  foreach ($cfthff as $cfthfff) {
                    if ($plotfieldsmulti13 != "") {
                      $plotfieldsmulti13 .= "," . $cfthfff;
                    } else {
                      $plotfieldsmulti13 = $cfthfff;
                    }
                  }
                  array_push($plotfields13, $plotfieldsmulti13);
                  $plotfieldsmulti13 = '';
                } else {
                  array_push($plotfields13, $cfthff);
                }
              }
            }
            array_push($cfTableHeadFieldsPlot, ['doc' => $lkey, 'fields' => $plotfields13]);
            $plotfields13 = [];
          } else {
            $cfthf['doc'] = $lkey;
            array_push($cfTableHeadFields, $cfthf);
          }
        }
      }
      if (isset($l['cfTableHeadButtons'])) {
        foreach ($l['cfTableHeadButtons'] as $cfthb) {
          $cfthb['doc'] = $lkey;
          array_push($cfTableHeadButtons, $cfthb);
        }
      }
      if (isset($l['cfTableCols'])) {
        foreach ($l['cfTableCols'] as $cftckey => $cftc) {
          if ($cftckey == 'plot') {
            $plotfields5 = [];
            $plotfieldsmulti5 = "";
            foreach ($cftc as $cftcc) {
              if (gettype($cftcc) == "string") {
                array_push($plotfields5, $cftcc);
              } else {
                if (count($cftcc) > 1) {
                  foreach ($cftcc as $cftccc) {
                    if ($plotfieldsmulti5 != "") {
                      $plotfieldsmulti5 .= "," . $cftccc;
                    } else {
                      $plotfieldsmulti5 = $cftccc;
                    }
                  }
                  array_push($plotfields5, $plotfieldsmulti5);
                  $plotfieldsmulti5 = "";
                } else {
                  array_push($plotfields5, $cftcc);
                }
              }
            }
            array_push($cfTableColsPlot, ['doc' => $lkey, 'fields' => $plotfields5]);
            $plotfields5 = [];
          } else {
            $cftc['doc'] = $lkey;
            array_push($cfTableCols, $cftc);
          }
        }
      }
      if (isset($l['cfTableButtons'])) {
        foreach ($l['cfTableButtons'] as $cftb) {
          $cftb['doc'] = $lkey;
          array_push($cfTableButtons, $cftb);
        }
      }
      if (isset($l['cfHeadFields'])) {
        foreach ($l['cfHeadFields'] as $cfhfkey => $cfhf) {
          if ($cfhfkey == 'plot') {
            $plotfields2 = [];
            $plotfieldsmulti2 = "";
            foreach ($cfhf as $cfhff) {
              if (gettype($cfhff) == "string") {
                array_push($plotfields2, $cfhff);
              } else {
                if (count($cfhff) > 1) {
                  foreach ($cfhff as $cfhfff) {
                    if ($plotfieldsmulti2 != "") {
                      $plotfieldsmulti2 .= "," . $cfhfff;
                    } else {
                      $plotfieldsmulti2 = $cfhfff;
                    }
                  }
                  array_push($plotfields2, $plotfieldsmulti2);
                  $plotfieldsmulti2 = "";
                } else {
                  array_push($plotfields2, $cfhff);
                }
              }
            }
            array_push($cfHeadFieldsPlot, ['doc' => $lkey, 'fields' => $plotfields2]);
            $plotfields2 = [];
          } else {
            $cfhf['doc'] = $lkey;
            array_push($cfHeadFields, $cfhf);
          }
        }
      }
      if (isset($l['inputLookupFields'])) {
        foreach ($l['inputLookupFields'] as $ilf) {
          foreach ($ilf['fields'] as $ilfkey2 => $ilf2) {
            if ($ilfkey2 == 'plot') {
              $plotfields6 = [];
              $plotfieldsmulti6 = "";
              foreach ($ilf2 as $ilff2) {
                if (gettype($ilff2) == "string") {
                  array_push($plotfields6, $ilff2);
                } else {
                  if (count($ilff2) > 1) {
                    foreach ($ilff2 as $ilfff2) {
                      if ($plotfieldsmulti6 != "") {
                        $plotfieldsmulti6 .= "," . $ilfff2;
                      } else {
                        $plotfieldsmulti6 = $ilfff2;
                      }
                    }
                    array_push($plotfields6, $plotfieldsmulti6);
                    $plotfieldsmulti6 = "";
                  } else {
                    array_push($plotfields6, $ilff2);
                  }
                }
              }
              array_push($inputLookupFieldsPlot, ['doc' => $lkey, 'form' => $ilf['form'], 'fields' => $plotfields6]);
              $plotfields6 = [];
            } else {
              $ilf2['doc'] = $lkey;
              $ilf2['form'] = $ilf['form'];
              array_push($inputLookupFields, $ilf2);
            }
          }
        }
      }
      if (isset($l['cLookupHeadFields'])) {
        foreach ($l['cLookupHeadFields'] as $clhf) {
          foreach ($clhf['fields'] as $clhfkey2 => $clhf2) {
            if ($clhfkey2 == 'plot') {
              $plotfields8 = [];
              $plotfieldsmulti8 = "";
              foreach ($clhf2 as $clhff2) {
                if (gettype($clhff2) == "string") {
                  array_push($plotfields8, $clhff2);
                } else {
                  if (count($clhff2) > 1) {
                    foreach ($clhff2 as $clhfff2) {
                      if ($plotfieldsmulti8 != "") {
                        $plotfieldsmulti8 .= "," . $clhfff2;
                      } else {
                        $plotfieldsmulti8 = $clhfff2;
                      }
                    }
                    array_push($plotfields8, $plotfieldsmulti8);
                    $plotfieldsmulti8 = "";
                  } else {
                    array_push($plotfields8, $clhff2);
                  }
                }
              }
              array_push($cLookupHeadFieldsPlot, ['doc' => $lkey, 'form' => $clhf['form'], 'fields' => $plotfields8]);
              $plotfields8 = [];
            } else {
              $clhf2['doc'] = $lkey;
              $clhf2['form'] = $clhf['form'];
              array_push($cLookupHeadFields, $clhf2);
            }
          }
        }
      }
      if (isset($l['cLookupTableCols'])) {
        foreach ($l['cLookupTableCols'] as $iltc) {
          foreach ($iltc['fields'] as $iltckey2 => $iltc2) {
            if ($iltckey2 == 'plot') {
              $plotfields7 = [];
              $plotfieldsmulti7 = "";
              foreach ($iltc2 as $iltcc) {
                if (gettype($iltcc) == "string") {
                  array_push($plotfields7, $iltcc);
                } else {
                  if (count($iltcc) > 1) {
                    foreach ($iltcc as $iltccc) {
                      if ($plotfieldsmulti7 != "") {
                        $plotfieldsmulti7 .= "," . $iltccc;
                      } else {
                        $plotfieldsmulti7 = $iltccc;
                      }
                    }
                    array_push($plotfields7, $plotfieldsmulti7);
                    $plotfieldsmulti7 = "";
                  } else {
                    array_push($plotfields7, $iltcc);
                  }
                }
              }
              array_push($cLookupTableColsPlot, ['doc' => $lkey, 'form' => $iltc['form'], 'fields' => $plotfields7]);
              $plotfields7 = [];
            } else {
              $iltc2['doc'] = $lkey;
              $iltc2['form'] = $iltc['form'];
              array_push($cLookupTableCols, $iltc2);
            }
          }
        }
      }
      if (isset($l['cLookupTableButtons'])) {
        foreach ($l['cLookupTableButtons'] as $cltb) {
          foreach ($cltb['btns'] as $cltbkey2 => $cltb2) {
            $cltb2['doc'] = $lkey;
            $cltb2['form'] = $cltb['form'];
            array_push($cLookupTableButtons, $cltb2);
          }
        }
      }
      if (isset($l['cLookupButtons'])) {
        foreach ($l['cLookupButtons'] as $clb) {
          foreach ($clb['btns'] as $clbkey => $clb2) {
            $clb2['doc'] = $lkey;
            $clb2['form'] = $clb['form'];
            array_push($cLookupButtons, $clb2);
          }
        }
      }
      if (isset($l['cLookupHeadButtons'])) {
        foreach ($l['cLookupHeadButtons'] as $clhb) {
          foreach ($clhb['btns'] as $clhbkey => $clhb2) {
            $clhb2['doc'] = $lkey;
            $clhb2['form'] = $clhb['form'];
            array_push($cLookupHeadButtons, $clhb2);
          }
        }
      }
      if (isset($l['cLookupFooterFields'])) {
        foreach ($l['cLookupFooterFields'] as $clff) {
          foreach ($clff['fields'] as $clffkey2 => $clff2) {
            if ($clffkey2 == 'plot') {
              $plotfields10 = [];
              $plotfieldsmulti10 = "";
              foreach ($clff2 as $clfff2) {
                if (gettype($clfff2) == "string") {
                  array_push($plotfields10, $clfff2);
                } else {
                  if (count($clfff2) > 1) {
                    foreach ($clfff2 as $clffff2) {
                      if ($plotfieldsmulti10 != "") {
                        $plotfieldsmulti10 .= "," . $clffff2;
                      } else {
                        $plotfieldsmulti10 = $clffff2;
                      }
                    }
                    array_push($plotfields10, $plotfieldsmulti10);
                    $plotfieldsmulti10 = "";
                  } else {
                    array_push($plotfields10, $clfff2);
                  }
                }
              }
              array_push($cLookupFooterFieldsPlot, ['doc' => $lkey, 'form' => $clff['form'], 'fields' => $plotfields10]);
              $plotfields10 = [];
            } else {
              $clff2['doc'] = $lkey;
              $clff2['form'] = $clff['form'];
              array_push($cLookupFooterFields, $clff2);
            }
          }
        }
      }
      if (isset($l['editItemFields'])) {
        foreach ($l['editItemFields'] as $eifkey => $eif) {
          if ($eifkey == 'plot') {
            $plotfields9 = [];
            $plotfieldsmulti9 = "";
            foreach ($eif as $eiff) {
              if (gettype($eiff) == "string") {
                array_push($plotfields9, $eiff);
              } else {
                if (count($eiff) > 1) {
                  foreach ($eiff as $eifff) {
                    if ($plotfieldsmulti9 != "") {
                      $plotfieldsmulti9 .= "," . $eifff;
                    } else {
                      $plotfieldsmulti9 = $eifff;
                    }
                  }
                  array_push($plotfields9, $plotfieldsmulti9);
                  $plotfieldsmulti9 = "";
                } else {
                  array_push($plotfields9, $eiff);
                }
              }
            }
            array_push($editItemFieldsPlot, ['doc' => $lkey, 'fields' => $plotfields9]);
            $plotfields9 = [];
          } else {
            $eif['doc'] = $lkey;
            array_push($editItemFields, $eif);
          }
        }
      }
      if (isset($l['editItemButtons'])) {
        foreach ($l['editItemButtons'] as $eib) {
          $eib['doc'] = $lkey;
          array_push($editItemButtons, $eib);
        }
      }
      if (isset($l['checkoutFields'])) {
        foreach ($l['checkoutFields'] as $cofkey => $cof) {
          if ($cofkey == 'plot') {
            $plotfields12 = [];
            $plotfieldsmulti12 = "";
            foreach ($cof as $coff) {
              if (gettype($coff) == "string") {
                array_push($plotfields12, $coff);
              } else {
                if (count($coff) > 1) {
                  foreach ($coff as $cofff) {
                    if ($plotfieldsmulti12 != "") {
                      $plotfieldsmulti12 .= "," . $cofff;
                    } else {
                      $plotfieldsmulti12 = $cofff;
                    }
                  }
                  array_push($plotfields12, $plotfieldsmulti12);
                  $plotfieldsmulti12 = "";
                } else {
                  array_push($plotfields12, $coff);
                }
              }
            }
            array_push($checkoutFieldsPlot, ['doc' => $lkey, 'fields' => $plotfields12]);
            $plotfields12 = [];
          } else {
            $cof['doc'] = $lkey;
            array_push($checkoutFields, $cof);
          }
        }
      }
      if (isset($l['checkoutButtons'])) {
        foreach ($l['checkoutButtons'] as $cob) {
          $cob['doc'] = $lkey;
          array_push($checkoutButtons, $cob);
        }
      }
    }
    return ['docListHead' => $docListHead, 'docListHeadPlot' => $docListHeadPlot, 'headButtons' => $headButtons, 'headFields' => $headFields, 'headFieldsPlot' => $headFieldsPlot, 'stockButtons' => $stockButtons, 'stockHeadButtons' => $stockHeadButtons, 'funcs' => $layouts['funcs'], 'cfHeadFields' => $cfHeadFields, 'cfHeadFieldsPlot' => $cfHeadFieldsPlot, 'cfTableButtons' => $cfTableButtons, 'cfHeadButtons' => $cfHeadButtons, 'cfTableHeadButtons' => $cfTableHeadButtons, 'stockCols' => $stockCols, 'docListCols' => $docListCols, 'cfTableCols' => $cfTableCols, 'docListColsPlot' => $docListColsPlot, 'stockColsPlot' => $stockColsPlot, 'cfTableColsPlot' => $cfTableColsPlot, 'inputLookupFields' => $inputLookupFields, 'inputLookupFieldsPlot' => $inputLookupFieldsPlot, 'inputLookupButtons' => $inputLookupButtons, 'cLookupTableCols' => $cLookupTableCols, 'cLookupTableColsPlot' => $cLookupTableColsPlot, 'cLookupHeadFields' => $cLookupHeadFields, 'cLookupHeadFieldsPlot' => $cLookupHeadFieldsPlot, 'cLookupButtons' => $cLookupButtons, 'editItemFields' => $editItemFields, 'editItemFieldsPlot' => $editItemFieldsPlot, 'editItemButtons' => $editItemButtons, 'cLookupTableButtons' => $cLookupTableButtons, 'cLookupFooterFields' => $cLookupFooterFields, 'cLookupFooterFieldsPlot' => $cLookupFooterFieldsPlot, 'footerFields' => $footerFields, 'footerFieldsPlot' => $footerFieldsPlot, 'checkoutFields' => $checkoutFields, 'checkoutFieldsPlot' => $checkoutFieldsPlot, 'checkoutButtons' => $checkoutButtons, 'cLookupHeadButtons' => $cLookupHeadButtons, 'cfTableHeadFields' => $cfTableHeadFields, 'cfTableHeadFieldsPlot' => $cfTableHeadFieldsPlot];
  }

  public function getCommonFunc()
  {
    $handleLogin = 'function (data, cfunc, router) {
      const thiss = this;
      const prevAgent = $q.localStorage.getItem("sbcPrevAgent");
      cfunc.getTableData("config", "serveraddr").then(serveraddr => {
        if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
          cfunc.showMsgBox("Server Address not set", "negative", "warning");
          $q.loading.hide();
          return;
        }
        cfunc.getTableData("config", "hastemplate", false).then(hastemplate => {
          if (hastemplate === null || hastemplate === "" || hastemplate === 0 || typeof(hastemplate) === "undefined") {
            cfunc.showMsgBox("Please download template first.", "negative", "warning");
          } else {
            contLogin(serveraddr);
          }
        });
      });
      function paddUsername (u) {
        console.log("paddUsername");
        let padLen = 10;
        switch (thiss.company) {
          case "shinzen": padLen = 7; break;
          case "sbc": padLen = 15; break;
        }
        if (thiss.company === "shinzen") padLen = 7;
        let pref = u.replace(/[0-9]/g, "").toUpperCase();
        let suff = parseInt(u.replace(/\D/g, ""));
        let len = suff.toString().length;
        let padd = padLen - pref.length;
        if (len < padd) {
          return pref + "0".repeat(padd - len) + suff;
        } else {
          return pref + suff;
        }
      }
      function contLogin (serveraddr) {
        console.log("contlogin");
        let pwd = "";
        if (data.username !== "" && data.password !== "") {
          let username = data.username;
          if (thiss.config !== "production" && thiss.config !== "sapint") {
            switch (thiss.company) {
              case "fastrax": case "shinzen": case "sbc": username = paddUsername(username.toUpperCase()); break;
            }
          }
          if (thiss.config === "inventoryapp") {
            if ($q.localStorage.has("invPCDate")) {
              if (sbc.globalFunc.company === "mbs") {
                cfunc.getTableData("config", "branchaddr").then(branchaddr => {
                  if (branchaddr === null || branchaddr === undefined || branchaddr === "") {
                    sbc.globalFunc.showErrMsg("Branch address not set");
                    $q.loading.hide();
                  } else {
                    contLogin2(serveraddr, username, data.password);
                  }
                });
              } else {
                contLogin2(serveraddr, username, data.password);
              }
            } else {
              // cfunc.showMsgBox("Physical Count Date not set.", "negative", "warning");
              sbc.globalFunc.showErrMsg("Physical Count Date not set.");
              $q.loading.hide();
            }
          } else {
            if (prevAgent !== null && prevAgent !== [] && typeof(prevAgent) !== "undefined") {
              if (prevAgent.username !== username) {
                $q.dialog({
                  message: prevAgent.name + " is using this app, do you want to continue login using other account?",
                  ok: { flat: true, color: "primary" },
                  cancel: { flat: true, color: "negative" }
                }).onOk(() => {
                  cfunc.showLoading();
                  sbc.globalFunc.checkAgentTrans(prevAgent).then(res => {
                    if (res) {
                      cfunc.showMsgBox(prevAgent.name + " has existing transactions, cannot proceed login", "negative", "warning");
                      $q.loading.hide();
                    } else {
                      contLogin2(serveraddr, username, data.password, "new");
                    }
                  }).catch(err => {
                    cfunc.showMsgBox(err.message, "negative", "warning");
                    $q.loading.hide();
                  });
                });
              } else {
                contLogin2(serveraddr, username, data.password);
              }
            } else {
              contLogin2(serveraddr, username, data.password);
            }
          }
        } else {
          cfunc.showMsgBox("Please enter username and password.", "negative", "warning");
          $q.loading.hide();
        }
      }
      function contLogin2 (serveraddr, username, password, type = "") {
        sbc.db.transaction(function (tx) {
          tx.executeSql("select ua.userid, ua.accessid, u.attributes, ua.username, ua.password, ua.wh, ua.name from useraccess as ua left join users as u on u.idno=ua.accessid where ua.username=? and ua.password=?", [username, password], function (tx, res) {
            if (res.rows.length > 0) {
              if (type === "new") {
                sbc.globalFunc.clearAgentTables().then(() => {
                  $q.localStorage.remove("sbcmobilev2Data");
                  let storage = res.rows.item(0);
                  $q.localStorage.set("sbcmobilev2Data", { user: storage, url: serveraddr, center: [], type: sbc.globalFunc.config });
                  $q.localStorage.set("sbcPrevAgent", storage);
                  cfunc.showMsgBox("Login Success", "positive");
                  $q.loading.hide();
                  router.push("/");
                }).catch(err => {
                  if (thiss.config === "inventoryapp") {
                    sbc.globalFunc.showErrMsg(err.message);
                  } else {
                    cfunc.showMsgBox(err.message, "negative", "warning");
                  }
                  $q.loading.hide();
                });
              } else {
                $q.localStorage.remove("sbcmobilev2Data");
                let storage = res.rows.item(0);
                $q.localStorage.set("sbcmobilev2Data", { user: storage, url: serveraddr, center: [], type: sbc.globalFunc.config });
                $q.localStorage.set("sbcPrevAgent", storage);
                cfunc.showMsgBox("Login Success", "positive");
                $q.loading.hide();
                router.push("/");
              }
            } else {
              if (thiss.config === "inventoryapp") {
                sbc.globalFunc.showErrMsg("Invalid username or password");
              } else {
                cfunc.showMsgBox("Invalid username or password", "negative", "warning");
              }
              $q.loading.hide();
            }
          });
        });
      }
    }';
    $downloadOthers = 'function() {
      console.log("'.$downloadOthers.'");
      const config = "' . $this->config . '";
      let idate = "";
      let colorsCount = 0;
      let designationCount = 0;
      let paintsuppCount = 0;
      let othersData = {};
      let user = $q.localStorage.getItem("sbcmobilev2Data");
      cfunc.showLoading();
      cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
        if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
          cfunc.showMsgBox("Server Address not set", "negative", "warning");
          $q.loading.hide();
          return;
        }
        downloadColors(serveraddr).then(c => {
          downloadDesignations(serveraddr).then(d => {
            downloadPaintSuppliers(serveraddr);
          });
        });
      });

      function downloadColors (serveraddr) {
        return new Promise((resolve) => {
          api.post(serveraddr + "/sbcmobilev2/download", { type: "colors" }).then(res => {
            if (res.data.colors.length > 0) {
              cfunc.clearTable("colors").then(() => {
                let d = res.data.colors;
                let dd = [];
                colorsCount = res.data.colors.length;
                while (d.length) dd.push(d.splice(0, 100));
                cfunc.showLoading();
                save1(dd);
              });
            } else {
              cfunc.showMsgBox("No colors to save", "negative", "warning");
              resolve("continue");
            }
          });

          function save1 (colors, index = 0) {
            cfunc.showLoading("Saving Colors (Batch " + index + " of " + colors.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === colors.length) {
              cfunc.showLoading("Successfully imported " + colorsCount + " Colors");
              setTimeout(function () {
                resolve("continue");
                $q.loading.hide();
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var c in colors[index]) {
                  saveColor(colors[index][c]);
                  if (parseInt(c) + 1 === colors[index].length) save1(colors, parseInt(index) + 1);
                }
              });
            }
          }

          function saveColor (data) {
            sbc.db.transaction(function (tx) {
              let qry = "insert into colors(line, color) values(?, ?)";
              let param = [data.line, data.color];
              tx.executeSql(qry, param, null, function (tx, err) {
                cfunc.saveErrLog(qry, parm, err.message);
              })
            });
          }
        });
      }
      function downloadDesignations (serveraddr) {
        return new Promise((resolve) => {
          api.post(serveraddr + "/sbcmobilev2/download", { type: "designations" }).then(res => {
            cfunc.clearTable("designations").then(() => {
              let d = res.data.designations;
              let dd = [];
              designationCount = res.data.designations.length;
              while (d.length) dd.push(d.splice(0, 100));
              cfunc.showLoading();
              save2(dd);
            });
          });

          function save2 (des, index = 0) {
            cfunc.showLoading("Saving Designations (Batch " + index + " of " + des.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === des.length) {
              cfunc.showLoading("Successfully imported " + designationCount + " Designations");
              setTimeout(function () {
                $q.loading.hide();
                resolve("continue");
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var d in des[index]) {
                  saveDes(des[index][d]);
                  if (parseInt(d) + 1 === des[index].length) save2(des, parseInt(index) + 1);
                }
              });
            }
          }

          function saveDes (data) {
            sbc.db.transaction(function (tx) {
              let qry = "insert into designations(line, designation) values(?, ?)";
              let param = [data.line, data.designation];
              tx.executeSql(qry, param, null, function (tx, err) {
                cfunc.saveErrLog(qry, param, err.message);
              })
            });
          }
        });
      }
      function downloadPaintSuppliers (serveraddr) {
        return new Promise((resolve) => {
          api.post(serveraddr + "/sbcmobilev2/download", { type: "paintsuppliers" }).then(res => {
            if (res.data.paintsuppliers.length > 0) {
              cfunc.clearTable("paintsupplier").then(() => {
                let d = res.data.paintsuppliers;
                let dd = [];
                paintsuppCount = res.data.paintsuppliers.length;
                while (d.length) dd.push(d.splice(0, 100));
                cfunc.showLoading();
                save3(dd);
              });
            }
          });

          function save3 (paintsuppliers, index = 0) {
            cfunc.showLoading("Saving Paint Suppliers (Batch " + index + " of " + paintsuppliers.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === paintsuppliers.length) {
              cfunc.showLoading("Successfully imported " + paintsuppCount + " Paint Suppliers");
              setTimeout(function () {
                $q.loading.hide();
                resolve("continue");
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var p in paintsuppliers[index]) {
                  savePaintSupplier(paintsuppliers[index][p]);
                  if (parseInt(p) + 1 === paintsuppliers[index].length) save3(paintsuppliers, parseInt(index) + 1);
                }
              });
            }
          }

          function savePaintSupplier (data) {
            sbc.db.transaction(function (tx) {
              let qry = "insert into paintsupplier(code, paintsupplier) values(?, ?)";
              let param = [data.code, data.paintsupplier];
              tx.executeSql(qry, param, null, function (tx, err) {
                cfunc.saveErrLog(qry, param, err.message);
              })
            });
          }
        });
      }
    }';
    $downloadItems = 'function() {
      const config = "' . $this->config . '";
      let hasrecord = false;
      let idate = "";
      let itemsCount = 0;
      let iend = 0;
      let itemData = {};
      let user = $q.localStorage.getItem("sbcmobilev2Data");
      cfunc.showLoading()
      cfunc.getTableDataCount("item").then(icount => {
        console.log("1111111111111", icount);
        if (icount > 0) hasrecord = true;
        cfunc.getTableData("config", ["serveraddr", "idlock"], true).then(configdata => {
          if (configdata.serveraddr === "" || configdata.serveraddr === null || typeof(configdata.serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            $q.loading.hide();
            return;
          }
          getItems(configdata)
        });

        function saveItems (data, configdata) {
          itemData = { data: { inserts: { item: data } } }
          cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, itemData, {
            successFn: function () {
              getItems(configdata)
            },
            errorFn: function (error) {
              cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning");
              console.log(error.message);
              $q.loading.hide();
            },
            progressFn: function (current, total) {
              cfunc.showLoading("Saving Items (Batch " + current + " of " + total + ")");
            }
          })
        }

        function getItems (configdata) {
          console.log("getItems");
          api.post(configdata.serveraddr + "/sbcmobilev2/download", { type: "items", hasrecord: hasrecord, date: configdata.idlock, iend: iend, wh: user.wh }).then(res => {
            console.log("items: ", res.data);
            if (res) {
              if (res.data.items.length > 0) {
                iend = res.data.iend;
                idate = res.data.date;
                itemsCount = res.data.icount;
                saveItems(res.data.items, configdata);
              } else {
                cfunc.showLoading(`Successfully imported ${itemsCount} Items`);
                sbc.db.transaction(function (tx) {
                  tx.executeSql("update config set idlock=?", [idate], function (tx, res) {
                    setTimeout(function () {
                      if (sbc.globalFunc.config !== "production") {
                        if (sbc.globalFunc.company === "sbc") {
                          sbc.globalFunc.downloadLastOrderno();
                          sbc.globalFunc.downloadItemBal();
                        } else if (sbc.globalFunc.config === "marswin") {
                          sbc.globalFunc.downloadItemBal();
                        } else {
                          if (sbc.selDoc.name === "items") sbc.modulefunc.loadTableData();
                        }
                      }
                      $q.loading.hide();
                    }, 1500);
                  });
                });
              }
            } else {
              console.log("done2");
            }
          }).catch(err => {
            console.log(err.message);
          })
        }
      })
    }';
    $downloadTerms = 'function () {
      let tend = 0;
      let termsData = {};
      let tcounts = 0;
      cfunc.getTableData("config", "serveraddr").then(serveraddr => {
        if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
          cfunc.showMsgBox("Server Address not set", "negative", "warning");
          return;
        }
        getTerms(serveraddr);
      });
      function saveTerms (data, serveraddr) {
        termsData = { data: { inserts: { terms: data } } };
        cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, termsData, {
          successFn: function () {
            getTerms(serveraddr);
          },
          errorFn: function (error) {
            cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning");
            console.log(error.message);
            $q.loading.hide();
          },
          progressFn: function (current, total) {
            cfunc.showLoading("Saving Terms (Batch " + current + " of " + total + ")");
          }
        })
      }
      function getTerms (serveraddr) {
        cfunc.showLoading("Downloading Terms, Please wait...");
        api.post(serveraddr + "/sbcmobilev2/download", { type: "terms", tend: tend }).then(res => {
          if (res) {
            if (res.data.terms.length > 0) {
              tend = res.data.tend;
              tcounts = res.data.tcount;
              saveTerms(res.data.terms, serveraddr);
            } else {
              cfunc.showLoading(`Successfully imported ${tcounts} Terms`);
              setTimeout(function () {
                $q.loading.hide();
              }, 1500);
            }
          }
        });
      }
    }';
    $downloadUOM = 'function () {
      const thiss = this;
      let uomdate = "";
      let uend = 0;
      let uomData = {};
      let uomCounts = 0;
      let hasrecord = false;
      cfunc.getTableDataCount("uom").then(ucount => {
        if (ucount > 0) hasrecord = true;
        cfunc.getTableData("config", ["serveraddr", "uomlock"], true).then(configdata => {
          console.log("configdata: ", configdata);
          if (configdata.serveraddr === "" || configdata.serveraddr === null || typeof(configdata.serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            return;
          }
          getUOMs(configdata);
        });
      });
      function saveUOM (data, configdata) {
        console.log("saveuom");
        uomData = { data: { inserts: { uom: data } } };
        cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, uomData, {
          successFn: function () {
            getUOMs(configdata);
          },
          errorFn: function (error) {
            cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning");
            console.log(error.message);
            $q.loading.hide();
          },
          progressFn: function (current, total) {
            cfunc.showLoading("Saving UOMs (Batch " + current + " of " + total + ")");
          }
        });
      }
      function getUOMs (configdata) {
        console.log("getuoms");
        cfunc.showLoading("Downloading UOMs, Please wait...");
        api.post(configdata.serveraddr + "/sbcmobilev2/download", { type: "uom", date: configdata.uomlock, uend: uend, hasrecord: hasrecord }).then(res => {
          if (res) {
            if (res.data.uom.length > 0) {
              uomdate = res.data.date;
              uend = res.data.uend;
              uomCounts = res.data.ucount;
              saveUOM(res.data.uom, configdata);
            } else {
              cfunc.showLoading(`Successfully imported ${uomCounts} UOMs`);
              sbc.db.transaction(function (tx) {
                tx.executeSql("update config set uomlock=?", [uomdate], function (tx, res) {
                  setTimeout(function () {
                    if (sbc.globalFunc.company === "sbc") {
                      if (sbc.selDoc.name === "items") sbc.modulefunc.loadTableData();
                    } else {
                      $q.loading.hide();
                    }
                  }, 1500);
                });
              });
            }
          }
        });
      }
    }';
    $downloadItemBal = 'function () {
      let ibend = 0;
      let ibData = {};
      let ibCounts = 0;
      cfunc.getTableDataCount("item").then(icount => {
        if (icount > 0) {
          cfunc.getTableData("config", "serveraddr").then(serveraddr => {
            if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
              cfunc.showMsgBox("Server Address not set", "negative", "warning");
              return;
            }
            sbc.db.transaction(function (tx) {
              tx.executeSql("delete from itemstat");
              getItemBal(serveraddr);
            });
          });
        } else {
          cfunc.showMsgBox("Please download items first.", "negative", "warning");
        }
      });
      function saveItemBal (data, serveraddr) {
        ibData = { data: { inserts: { itemstat: data } } };
        cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, ibData, {
          successFn: function () {
            getItemBal(serveraddr);
          },
          errorFn: function (error) {
            cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning");
            console.log(error.message);
            $q.loading.hide();
          },
          progressFn: function (current, total) {
            cfunc.showLoading("Saving ItemBal (Batch " + current + " of " + total + ")");
          }
        });
      }
      function getItemBal (serveraddr) {
        cfunc.showLoading("Downloading ItemBal, Please wait...");
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        api.post(serveraddr + "/sbcmobilev2/download", { type: "itembal", ibend: ibend, wh: storage.user.wh }).then(res => {
          if (res) {
            if (res.data.itembal.length > 0) {
              ibend = res.data.ibend;
              ibCounts = res.data.ibcount;
              saveItemBal(res.data.itembal, serveraddr);
            } else {
              cfunc.showLoading(`Successfully imported ${ibCounts} Itembal`);
              setTimeout(function () {
                if (sbc.globalFunc.company === "sbc") {
                  sbc.globalFunc.downloadUOM();
                } else {
                  if (sbc.selDoc.name === "items") sbc.modulefunc.loadTableData();
                }
                $q.loading.hide();
              }, 1500);
            }
          }
        });
      }
    }';
    $loadLookup = 'function (action, fields = "") {
      const thiss = this;
      thiss.lookupAction = action;
      thiss.lookupFields = fields;
      console.log("loadLookup: ", action);
      switch (action) {
        case "disclookup":
          sbc.modulefunc.inputLookupForm = { disc: sbc.selItem.disc };
          sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "discLookupFields", "inputFields");
          sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "discLookupFields", "inputPlot");
          sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "discLookupButtons", "buttons");
          console.log("selInputLookupFields: ", sbc.selinputlookupfields);
          sbc.isFormEdit = true;
          sbc.inputLookupTitle = "Edit Discount";
          sbc.showInputLookup = true;
          break;
        case "remlookup":
          sbc.modulefunc.inputLookupForm = { rem: sbc.selItem.rem };
          sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "remLookupFields", "inputFields");
          sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "remLookupFields", "inputPlot");
          sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "remLookupButtons", "buttons");
          console.log("fields: ", sbc.inputlookupfields);
          sbc.isFormEdit = true;
          sbc.inputLookupTitle = "Edit Remarks";
          sbc.showInputLookup = true;
          break;
        case "scanItem":
          if (sbc.isFormEdit) {
            cfunc.showMsgBox("Please save head first", "negative", "warning");
            return;
          }
          sbc.showScanItem = true;
          sbc.txtScan = "";
          break;
        case "whlookup": case "supplookup": case "clientlookup":
          switch (thiss.config) {
            case "ordering":
              let sql = "select * from customers where isinactive=0";
              let strs = [];
              let f = "";
              let d = [];
              cfunc.showLoading();
              thiss.lookupTableSelect = false;
              switch (thiss.company) {
                case "sbc":
                  thiss.lookupCols = [
                    { name: "clientname", label: "Name", align: "left", field: "clientname", style: "white-space:normal;min-width:250px;" },
                    { name: "brgy", label: "Barangay", align: "left", field: "brgy" },
                    { name: "area", label: "Area", align: "left", field: "area" },
                    { name: "province", label: "Province", align: "left", field: "province" },
                    { name: "client", label: "Code", align: "left", field: "client" },
                  ];
                  break;
                default:
                  thiss.lookupCols = [
                    { name: "client", label: "Code", align: "left", field: "client" },
                    { name: "clientname", label: "Name", align: "left", field: "clientname", style: "white-space:normal;" },
                    { name: "addr", label: "Address", align: "left", field: "addr", style: "white-space:normal;" },
                    { name: "tel", label: "Tel #", align: "left", field: "tel" }
                  ];
                  break;
              }
              if (action === "clientlookup") sbc.modulefunc.lookupTableFilter = { type: "searchCustomer", field: "txtSearchCustomer", label: "Search Customer", func: "searchCustomer" };
              // sbc.modulefunc.tableFilter = [];
              console.log("str: ", sbc.modulefunc.txtSearchCustomer);
              if (sbc.modulefunc.txtSearchCustomer !== "") strs = sbc.modulefunc.txtSearchCustomer.split(",");
              if (strs.length > 0) {
                for (var s in strs) {
                  strs[s] = strs[s].trim();
                  if (strs[s] !== "") {
                    if (f !== "") {
                      if (thiss.company === "sbc") {
                        f = f.concat(" and ((clientname like ?) or (brgy like ?) or (area like ?) or (province like ?)) ");
                      } else {
                        f = f.concat(" and ((clientname like ?) or (addr like ?) or (tel like ?))");
                      }
                    } else {
                      if (thiss.company === "sbc") {
                        f = f.concat(" ((clientname like ?) or (brgy like ?) or (area like ?) or (province like ?)) ");
                      } else {
                        f = f.concat(" ((clientname like ?) or (addr like ?) or (tel like ?))");
                      }
                    }
                    if (thiss.company === "sbc") {
                      d.push(["%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%"]);
                    } else {
                      d.push(["%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%"]);
                    }
                  }
                }
              }
              if (d.length === 0) {
                d = [];
                sql = sql.concat(" order by clientname ");
              } else {
                sql = sql.concat(" and (" + f + ") order by clientname");
              }
              var dd = [].concat.apply([], d);
              thiss.lookupData = [];
              sbc.db.transaction(function (tx) {
                tx.executeSql(sql, dd, function (tx, res) {
                  sbc.showLookup = true;
                  if (res.rows.length > 0) {
                    for (var x = 0; x < res.rows.length; x++) {
                      thiss.lookupData.push(res.rows.item(x));
                    }
                    thiss.checkDuplicates("customers");
                    $q.loading.hide();
                  } else {
                    $q.loading.hide();
                  }
                }, function (tx, err) {
                  cfunc.showMsgBox(err.message, "negative", "warning");
                  $q.loading.hide();
                });
              }, function (err) {
                console.log("error: ", err.message);
              });
              break;
            default:
              let filter = "";
              switch (action) {
                case "whlookup":
                  filter = " and iswarehouse=1 ";
                  thiss.lookupCols = [
                    { name: "whname", label: "Name", align: "left", field: "whname", sortable: true },
                    { name: "whaddr", label: "Address", align: "left", field: "whaddr", sortable: true }
                  ];
                  break;
                case "supplookup":
                  filter = " and issupplier=1";
                  thiss.lookupCols = [
                    { name: "clientname", label: "Name", align: "left", field: "clientname", sortable: true },
                    { name: "supaddr", label: "Address", align: "left", field: "supaddr", sortable: true }
                  ];
                  break;
                case "clientlookup":
                  filter = " and iscustomer=1";
                  thiss.lookupCols = [
                    { name: "clientname", label: "Name", align: "left", field: "clientname", sortable: true },
                    { name: "supaddr", label: "Address", align: "left", field: "supaddr", sortable: true }
                  ];
                  break;
              }
              sbc.db.transaction(function (tx) {
                tx.executeSql("select clientid, clientname, clientid as whid, clientname as whname, addr as supaddr, addr as whaddr from client where isinactive=0 " + filter, [], function (tx, res) {
                  thiss.lookupData = [];
                  sbc.showLookup = true;
                  if (res.rows.length > 0) {
                    for (var x = 0; x < res.rows.length; x++) {
                      thiss.lookupData.push(res.rows.item(x));
                    }
                  }
                  $q.loading.hide()
                }, function (tx, err) {
                  cfunc.showMsgBox(err.message + " loadLookup Err#1", "negative", "warning");
                  $q.loading.hide();
                });
              }, function (err) {
                cfunc.showMsgBox(err.message + " loadLookup Err#2", "negative", "warning");
                $q.loading.hide();
              });
              break;
          }
          break;
        case "uomlookup":
          thiss.lookupCols = [
            { name: "uom", label: "UOM", align: "left", field: "uom", sortable: true }
          ];
          cfunc.showLoading();
          sbc.db.transaction(function (tx) {
            tx.executeSql(`select "PCS" as uom union all select "BOX" as uom`, [], function (tx, res) {
              thiss.lookupData = [];
              sbc.showLookup = true;
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  thiss.lookupData.push(res.rows.item(x));
                }
              }
              $q.loading.hide();
            }, function (tx, err) {
              console.log(err.message);
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          });
          break;
        case "itemlookup":
          thiss.lookupCols = [
            { name: "barcode", label: "Barcode", align: "left", field: "barcode", sortable: true },
            { name: "itemname", label: "Item Name", align: "left", field: "itemname", sortable: true },
            { name: "uom", label: "UOM", align: "left", field: "uom" }
          ];
          switch(thiss.config) {
            case "production":
              if (sbc.globalFunc.selDocProd.isexit === 0) {
                cfunc.showLoading();
                sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Item", func: "" };
                sbc.globalFunc.lookupTableSelect = false;
                sbc.db.transaction(function (tx) {
                  tx.executeSql("select * from item", [], function (tx, res) {
                    thiss.lookupData = [];
                    sbc.showLookup = true;
                    if (res.rows.length > 0) {
                      for (var x = 0; x < res.rows.length; x++) {
                        thiss.lookupData.push(res.rows.item(x));
                      }
                    }
                    $q.loading.hide();
                  }, function (tx, err) {
                    cfunc.showMsgBox(err.message + " loadItemsLookup error", "negative", "warning");
                    $q.loading.hide();
                  });
                });
              }
              break;
            default:
              if (sbc.isFormEdit) {
                cfunc.showMsgBox("Please save head first", "negative", "warning");
                return;
              } else {
                sbc.showHeadForm = false;
              }
              cfunc.showLoading();
              sbc.db.transaction(function (tx) {
                let qry = "";
                qry = "select * from (\
                  select itemid, barcode, itemname, uom, brand, factor from item where (isinactive=0 or isinactive is null or isinactive <> ?) union all \
                  select itemid, barcode2 as barcode, itemname, uom2 as uom, brand, factor2 as factor from item where (isinactive=0 or isinactive is null or isinactive <> ?) and (uom2 <> ? and uom2 is not null) union all \
                  select itemid, barcode3 as barcode, itemname, uom3 as uom, brand, factor3 as factor from item where (isinactive=0 or isinactive is null or isinactive <> ?) and (uom3 <> ? and uom3 is not null) union all \
                  select itemid, barcode4 as barcode, itemname, uom4 as uom, brand, factor4 as factor from item where (isinactive=0 or isinactive is null or isinactive <> ?) and (uom4 <> ? and uom4 is not null) union all \
                  select itemid, barcode5 as barcode, itemname, uom5 as uom, brand, factor5 as factor from item where (isinactive=0 or isinactive is null or isinactive <> ?) and (uom5 <> ? and uom5 is not null) union all \
                  select itemid, barcode6 as barcode, itemname, uom6 as uom, brand, factor6 as factor from item where (isinactive=0 or isinactive is null or isinactive <> ?) and (uom6 <> ? and uom6 is not null)\
                  ) as t order by itemid";
                tx.executeSql(qry, ["", "", "", "", "", "", "", "", "", "", ""], function (tx, res) {
                  thiss.lookupData = [];
                  sbc.showLookup = true;
                  if (res.rows.length > 0) {
                    for (var x = 0; x < res.rows.length; x++) {
                      thiss.lookupData.push(res.rows.item(x));
                    }
                  }
                  $q.loading.hide();
                }, function (tx, err) {
                  cfunc.showMsgBox(err.message + " loadLookup Err#3", "negative", "warning");
                  $q.loading.hide();
                });
              });
              break;
          }
          break;
        case "arealookup":
          sbc.globalFunc.loadAreasLookup();
          break;
        case "transtypeslookup":
          sbc.globalFunc.loadTransTypesLookup();
          break;
        case "transtypeslookup2":
          sbc.globalFunc.loadTransTypesLookup();
          break;
        case "categorylookup":
          sbc.globalFunc.lookupCols = [
            { name: "category", label: "Category", field: "category", align: "left", sortable: true }
          ];
          sbc.globalFunc.lookupTableSelect = false;
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Category", func: "" };
          sbc.lookupTitle = "Select Category";
          sbc.showLookup = true;
          sbc.globalFunc.lookupData = [{ category: "Single" }, { category: "Batch" }];
          break;
        case "tenantslookup":
          sbc.globalFunc.lookupCols = [
            { name: "label", label: "Name", field: "label", align: "left", sortable: true }
          ];
          if (sbc.modulefunc.docForm.area === "" && sbc.selDoc.name !== "admin") {
            cfunc.showMsgBox("No Area selected", "negative", "warning");
            return;
          }
          if (sbc.selDoc.name === "admin") {
            sbc.globalFunc.lookupTableSelect = false;
          }
          cfunc.showLoading();
          sbc.showLookup = true;
          let qry = "";
          let data = [];
          if (sbc.selDoc.name === "admin") {
            qry = "select clientid, clientname, dailyrent, dcusa, section, center, loc, outar, outcusa, outelec, outwater from client";
          } else {
            qry = "select * from client where phase=? and section=?";
            data = [sbc.modulefunc.docForm.selArea.phase, sbc.modulefunc.docForm.selArea.section];
          }
          sbc.db.transaction(function (tx) {
            tx.executeSql(qry, data, function (tx, res) {
              sbc.globalFunc.lookupData = [];
              for (var x = 0; x < res.rows.length; x++) {
                sbc.globalFunc.lookupData.push(res.rows.item(x));
                sbc.globalFunc.lookupData[x].label = res.rows.item(x).loc + " - " + res.rows.item(x).clientname;
                if (parseInt(x) + 1 === res.rows.length) $q.loading.hide();
              }
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          }, function (err) {
            console.log("1aaaaaaaaa", err.message);
          });
          break;
        case "shiftlookup":
          sbc.globalFunc.lookupCols = [
            { name: "shiftt", label: "Shift", field: "shiftt", align: "left", sortable: true }
          ];
          sbc.globalFunc.lookupTableSelect = false;
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Shift", func: "" };
          sbc.lookupTitle = "Select Shift";
          sbc.showLookup = true;
          sbc.globalFunc.lookupData = [{ shiftt: "SHIFT 1" }, { shiftt: "SHIFT 2" }, { shiftt: "SHIFT 3" }];
          break;
        case "seltypelookup":
          sbc.globalFunc.lookupCols = [
            { name: "seltype", label: "Type", field: "seltype", align: "left", sortable: true }
          ];
          sbc.globalFunc.lookupTableSelect = false;
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Type", func: "" };
          sbc.lookupTitle = "Select Type";
          sbc.showLookup = true;
          sbc.globalFunc.lookupData = [{ seltype: "PRIME" }, { seltype: "SECONDS" }, { seltype: "SCAP-ENTRY" }, { seltype: "SCRAP-EXIT" }, { seltype: "REJECT" }, { seltype: "IN PROCESS" }];
          break;
        case "designationlookup":
          sbc.globalFunc.lookupCols = [
            { name: "designation", label: "Designation", field: "designation", align: "left", sortable: true }
          ];
          sbc.globalFunc.lookupTableSelect = false;
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Designation", func: "" };
          sbc.lookupTitle = "Select Designation";
          sbc.showLookup = true;
          sbc.globalFunc.lookupData = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from designations", [], function (tx, res) {
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.globalFunc.lookupData.push(res.rows.item(x));
                }
              }
            });
          });
          break;
        case "colorlookup":
          sbc.globalFunc.lookupCols = [
            { name: "color", label: "Color", field: "color", align: "left", sortable: true }
          ];
          sbc.globalFunc.lookupTableSelect = false;
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Color", func: "" };
          sbc.lookupTitle = "Select Color";
          sbc.showLookup = true;
          sbc.globalFunc.lookupData = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from colors", [], function (tx, res) {
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.globalFunc.lookupData.push(res.rows.item(x));
                }
              }
            });
          });
          break;
        case "paintsupplookup":
          sbc.globalFunc.lookupCols = [
            { name: "paintsupp", label: "Paint Supplier", field: "paintsupp", align: "left", sortable: true }
          ];
          sbc.globalFunc.lookupTableSelect = false;
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Paint Supplier", func: "" };
          sbc.lookupTitle = "Select Paint Supplier";
          sbc.showLookup = true;
          sbc.globalFunc.lookupData = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select paintsupplier as paintsupp from paintsupplier", [], function (tx, res) {
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.globalFunc.lookupData.push(res.rows.item(x));
                }
              }
            });
          });
          break;
        case "locationlookup":
          console.log("waw");
          sbc.globalFunc.lookupCols = [
            { name: "location", label: "Location", field: "location", align: "left", sortable: true }
          ];
          sbc.globalFunc.lookupTableSelect = false;
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Location", func: "" };
          sbc.lookupTitle = "Select Location";
          sbc.showLookup = true;
          sbc.globalFunc.lookupData = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select distinct location from items group by location order by location", [], function (tx, res) {
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.globalFunc.lookupData.push(res.rows.item(x));
                }
              }
            });
          });
          break;
        case "divisionlookup":
          console.log("wew");
          sbc.globalFunc.lookupCols = [
            { name: "division", label: "Division", field: "division", align: "left", sortable: true }
          ];
          sbc.globalFunc.lookupTableSelect = false;
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Division", func: "" };
          sbc.lookupTitle = "Select Division";
          sbc.showLookup = true;
          sbc.globalFunc.lookupData = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select distinct division from items group by division order by division", [], function (tx, res) {
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.globalFunc.lookupData.push(res.rows.item(x));
                }
              }
            });
          });
          break;
        case "emaillookup":
          sbc.globalFunc.lookupCols = [
            { name: "email", label: "ID", align: "left", field: "email", sortable: true },
            { name: "name", label: "Name", align: "left", field: "name", sortable: true }
          ];
          sbc.globalFunc.lookupTableSelect = false;
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search User", func: "" };
          sbc.lookupTitle = "Select User";
          sbc.showLookup = true;
          sbc.globalFunc.lookupData = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select id, email, name from useraccess2 where isactive=1", [], function (tx, res) {
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.globalFunc.lookupData.push({ id: res.rows.item(x).id, email: res.rows.item(x).email, name: res.rows.item(x).name });
                }
              }
            });
          });
          break;
        case "sapScanList":
          sbc.globalFunc.lookupAction = "sapScanList";
          sbc.globalFunc.lookupFields = "";
          sbc.globalFunc.lookupCols = [
            { name: "barcode", label: "Item Code", field: "barcode", align: "left", sortable: true },
            { name: "batchcode", label: "Batch Code", field: "batchcode", align: "left", sortable: true },
            { name: "rtrno", label: "Line No", field: "rtrno", align: "left", sortable: true },
            { name: "qty", label: "Qty", field: "qty", align: "center", sortable: true },
            { name: "isverified", label: "Verified", field: "isverified", align: "center", sortable: true }
          ];
          cfunc.showLoading();
          sbc.globalFunc.lookupData = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select d.barcode, d.batchcode, dd.rtrno, dd.rline, dd.qtyreleased as qty, case dd.isverified when 1 then ? else ? end as isverified from tempdetail as d left join detail as dd on dd.trno=d.trno and dd.line=d.line where d.trno=? and dd.uploaded is null and dd.doc=?", ["Yes", "No", sbc.modulefunc.cLookupForm.trno, sbc.doc], function (tx, res) {
              sbc.globalFunc.lookupTableSelect = false;
              sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search", func: "" };
              sbc.lookupTitle = "Scan List";
              sbc.showLookup = true;
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.globalFunc.lookupData.push(res.rows.item(x));
                  if (parseInt(x) + 1 == res.rows.length) $q.loading.hide();
                }
              } else {
                sbc.globalFunc.lookupData = [];
                $q.loading.hide();
              }
            }, function (tx, err) {
              sbc.globalFunc.lookupData = [];
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          });
          break;
        case "invItemLookup":
          sbc.modulefunc.loadItems();
          break;
    }';
    $selectLookup = 'function (row) {
      let fields = [];
      if (sbc.globalFunc.lookupFields !== undefined && sbc.globalFunc.lookupFields !== null) {
        if (sbc.globalFunc.lookupFields.length > 0) fields = sbc.globalFunc.lookupFields.split(",");
      }
      console.log("selectLookup: ", sbc.globalFunc.lookupAction, "---", this.lookupFields);
      switch (sbc.globalFunc.lookupAction) {
        case "whlookup": case "supplookup": case "clientlookup":
          for (var f in fields) {
            sbc.modulefunc.docForm[fields[f]] = row[fields[f]];
          }
          if (sbc.globalFunc.config === "ordering") {
            if (!$q.localStorage.has("selCustomer")) $q.localStorage.set("selCustomer", row);
          }
          sbc.showLookup = false;
          break;
        case "uomlookup":
          for (var f in fields) {
            sbc.modulefunc.inputLookupForm[fields[f]] = row[fields[f]];
          }
          sbc.showLookup = false;
          break;
        case "itemlookup":
          switch(sbc.globalFunc.config) {
            case "production":
              sbc.modulefunc.cLookupForm.itemid = row.itemid;
              sbc.modulefunc.cLookupForm.itemname = row.itemname;
              sbc.modulefunc.cLookupForm.barcode = row.barcode;
              sbc.modulefunc.cLookupForm.groupid = row.groupid;
              sbc.modulefunc.cLookupForm.class = row.class;
              sbc.modulefunc.cLookupForm.thickness = row.thickness;
              sbc.modulefunc.cLookupForm.width = row.width;
              sbc.modulefunc.cLookupForm.mass = row.mass;
              sbc.showLookup = false;
              break;
            default:
              sbc.selItem = { trno: sbc.modulefunc.docForm.trno, line: "", itemid: row.itemid, barcode: row.barcode, itemname: row.itemname, rrqty: 1, qty: parseInt(row.factor), uom: row.uom };
              var type = eval(sbc.globalFunc.lookupFields)[0].value;
              if (type === "doc") {
                sbc.showEditItem = true;
                sbc.globalFunc.iType = "new";
              } else {
                sbc.globalFunc.searchItem();
              }
              break;
          }
          break;
        case "lookupPrinters":
          console.log("Printer clicked waw: ", row.name);
          sbc.globalFunc.continuePrint(row.name);
          break;
        case "userAgentLookup":
          sbc.globalFunc.continueDownloadAgent(row);
          break;
        case "centerLookup":
          cfunc.showLoading();
          sbc.db.transaction(function (tx) {
            tx.executeSql("update config set center=?", [row.code], function (tx, res) {
              $q.localStorage.remove("sbcmobilev2Data");
              $q.localStorage.set("sbcmobilev2Data", sbc.globalFunc.logStorage);
              cfunc.showMsgBox(sbc.globalFunc.logMsg, "positive");
              $q.loading.hide();
              sbc.showLookup = false;
              router.push("/");
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          });
          break;
        case "printerLookup":
          sbc.modulefunc.selectPrinter(row);
          break;
        case "collectorsLookup":
          sbc.modulefunc.selectCollector(row);
          break;
        case "operationTypeLookup":
          sbc.modulefunc.selectOperationType(row);
          break;
        case "printTypeLookup":
          sbc.modulefunc.selectPrintType(row);
          break;
        case "transTypeLookup":
          sbc.modulefunc.selectTransType(row);
          break;
        case "arealookup":
          sbc.modulefunc.docForm.area = row.label;
          sbc.modulefunc.docForm.selArea = row;
          sbc.showLookup = false;
          switch (sbc.selDoc.name) {
            case "ambulant":
              sbc.globalFunc.loadLookup("transtypeslookup");
              break;
            default:
              sbc.globalFunc.loadLookup("categorylookup");
              break;
          }
          break;
        case "transtypeslookup":
          sbc.modulefunc.docForm.transtype = row.label;
          sbc.showLookup = false;
          break;
        case "transtypeslookup2":
          sbc.modulefunc.inputLookupForm.transtype = row.label;
          sbc.showLookup = false;
          break;
        case "categorylookup":
          sbc.modulefunc.docForm.category = row.category;
          sbc.showLookup = false;
          sbc.globalFunc.loadTransTypes();
          if (row.category === "Single") {
            for (var cfhf in sbc.cfheadfields) {
              if (sbc.cfheadfields[cfhf].name === "tenant") sbc.cfheadfields[cfhf].show = "true";
            }
          } else {
            for (var cfhf in sbc.cfheadfields) {
              if (sbc.cfheadfields[cfhf].name === "tenant") sbc.cfheadfields[cfhf].show = "false";
            }
          }
          sbc.globalFunc.loadLookup("tenantslookup");
          break;
        case "tenantslookup":
          sbc.showLookup = false;
          if (sbc.selDoc.name === "admin") {
            sbc.modulefunc.inputLookupForm.selTenant = row;
            sbc.modulefunc.inputLookupForm.tenant = row.label;
          } else {
            sbc.modulefunc.docForm.selTenant = row;
            sbc.modulefunc.docForm.tenant = row.label;
            sbc.modulefunc.inputLookupForm = [];
            cfunc.showLoading();
            if (sbc.modulefunc.docForm.transtype === "Electric Reading" || sbc.modulefunc.docForm.transtype === "Water Reading") {
              cfunc.getTableData("config", "collectiondate").then(colDate => {
                if (colDate === "" || colDate === null || typeof(colDate) === "undefined") {
                  cfunc.showMsgBox("Collection date not set", "negative", "warning");
                  $q.loading.hide();
                  return;
                }
                sbc.modulefunc.inputLookupForm = {
                  arealabel: sbc.modulefunc.docForm.area,
                  transtype: sbc.modulefunc.docForm.transtype,
                  tenant: sbc.modulefunc.docForm.selTenant.clientname,
                  loc: sbc.modulefunc.docForm.selTenant.loc,
                  clientid: sbc.modulefunc.docForm.selTenant.clientid,
                  dateid: colDate,
                  beginning: (sbc.modulefunc.docForm.transtype === "Electric Reading" ? sbc.numeral(sbc.modulefunc.docForm.selTenant.last_eending).value() : sbc.numeral(sbc.modulefunc.docForm.selTenant.last_wending).value()),
                  rate: (sbc.modulefunc.docForm.transtype === "Electric Reading" ? sbc.numeral(sbc.modulefunc.docForm.selTenant.erate).value() : sbc.numeral(sbc.modulefunc.docForm.selTenant.wrate).value()),
                  type: (sbc.modulefunc.docForm.transtype === "Electric Reading" ? "E" : "W"),
                  ending: "",
                  consumption: "",
                  remarks: "",
                  hasrecord: false
                };
                sbc.globalFunc.checkTrans("reading").then(res => {
                  if (res.hasRecord) {
                    sbc.modulefunc.inputLookupForm.remarks = res.remarks;
                    sbc.modulefunc.inputLookupForm.beginning = sbc.numeral(res.prev).value(),
                    sbc.modulefunc.inputLookupForm.ending = sbc.numeral(res.current).value(),
                    sbc.modulefunc.inputLookupForm.consumption = sbc.numeral(res.consumption).value()
                    sbc.modulefunc.inputLookupForm.hasrecord = true;
                  } else {
                    sbc.modulefunc.inputLookupForm.hasrecord = false;
                  }
                });
                sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "readingLookupFields", "inputFields");
                sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "readingLookupFields", "inputPlot");
                sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "readingLookupButtons", "buttons");
                if (sbc.selinputlookupfields.length > 0) {
                  for (var ilf in sbc.selinputlookupfields) {
                    if (sbc.selinputlookupfields[ilf].name === "transtype") {
                      sbc.selinputlookupfields[ilf].options = sbc.modulefunc.docForm.transtypeOpts;
                    }
                  }
                }
                sbc.isFormEdit = true;
                sbc.inputLookupTitle = "";
                sbc.showInputLookup = true;
                $q.loading.hide();
              });
            } else {
              cfunc.getTableData("config", "collectiondate").then(colDate => {
                if (colDate === "" || colDate === null || typeof(colDate) === "undefined") {
                  cfunc.showMsgBox("Collection date not set", "negative", "warning");
                  $q.loading.hide();
                  return;
                }

                sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "collectLookupFields", "inputFields");
                sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "collectLookupFields", "inputPlot");
                sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "collectLookupButtons", "buttons");
                if (sbc.selinputlookupfields.length > 0) {
                  for (var ilf in sbc.selinputlookupfields) {
                    if (sbc.selinputlookupfields[ilf].name === "transtype") {
                      sbc.selinputlookupfields[ilf].options = sbc.modulefunc.docForm.transtypeOpts;
                    }
                  }
                }
                sbc.isFormEdit = true;
                sbc.inputLookupTitle = "";

                sbc.modulefunc.inputLookupForm.area = sbc.modulefunc.docForm.area;
                sbc.modulefunc.inputLookupForm.transtype = sbc.modulefunc.docForm.transtype;
                sbc.modulefunc.inputLookupForm.tenant = sbc.modulefunc.docForm.selTenant.clientname;
                sbc.modulefunc.inputLookupForm.loc = sbc.modulefunc.docForm.selTenant.loc;
                sbc.modulefunc.inputLookupForm.rent = "";
                sbc.modulefunc.inputLookupForm.cusa = "";
                if (sbc.modulefunc.docForm.selTenant.daiylrent !== "") sbc.modulefunc.inputLookupForm.rent = sbc.numeral(sbc.modulefunc.docForm.selTenant.dailyrent).format("0,0.00");
                if (sbc.modulefunc.docForm.selTenant.dcusa !== "") sbc.modulefunc.inputLookupForm.cusa = sbc.numeral(sbc.modulefunc.docForm.selTenant.dcusa).format("0,0.00");
                let amtdue = 0;
                let payment = 0;
                switch (sbc.modulefunc.docForm.transtype) {
                  case "Rent":
                    sbc.modulefunc.inputLookupForm.outstandingbal = sbc.numeral(sbc.modulefunc.docForm.selTenant.outar).format("0,0.00");
                    if (sbc.modulefunc.docForm.selTenant.outar < 0) {
                      payment = sbc.numeral(sbc.numeral(sbc.modulefunc.docForm.selTenant.outar).value() + sbc.numeral(sbc.modulefunc.docForm.selTenant.dailyrent).value()).format("0,0.00");
                      amtdue = sbc.numeral(sbc.numeral(sbc.modulefunc.docForm.selTenant.outar).value() + sbc.numeral(sbc.modulefunc.docForm.selTenant.dailyrent).value()).format("0,0.00");
                    } else {
                      payment = sbc.modulefunc.docForm.selTenant.dailyrent;
                      amtdue = sbc.numeral(sbc.modulefunc.docForm.selTenant.outar).format("0,0.00");
                    }
                    sbc.globalFunc.formtype = "form1";
                    sbc.globalFunc.showCollectForm("form1");
                    break;
                  case "CUSA":
                    sbc.modulefunc.inputLookupForm.outstandingbal = sbc.numeral(sbc.modulefunc.docForm.selTenant.outcusa).format("0,0.00");
                    if (sbc.modulefunc.docForm.selTenant.outcusa < 0) {
                      payment = sbc.numeral(sbc.numeral(sbc.modulefunc.docForm.selTenant.outcusa).value() + sbc.numeral(sbc.modulefunc.docForm.selTenant.dcusa).value()).format("0,0.00");
                      amtdue = sbc.numeral(sbc.numeral(sbc.modulefunc.docForm.selTenant.outcusa).value() + sbc.numeral(sbc.modulefunc.docForm.selTenant.dcusa).value()).format("0,0.00");
                    } else {
                      payment = sbc.modulefunc.docForm.selTenant.dcusa;
                      amtdue = sbc.numeral(sbc.modulefunc.docForm.selTenant.outcusa).format("0,0.00");
                    }
                    sbc.globalFunc.formtype = "form1";
                    sbc.globalFunc.showCollectForm("form1");
                    break;
                  case "Electricity":
                    sbc.modulefunc.inputLookupForm.outstandingbal = sbc.numeral(sbc.modulefunc.docForm.selTenant.outelec).format("0,0.00");
                    amtdue = "";
                    sbc.globalFunc.formtype = "form2";
                    sbc.globalFunc.showCollectForm("form2");
                    break;
                  case "Water":
                    sbc.modulefunc.inputLookupForm.outstandingbal = sbc.numeral(sbc.modulefunc.docForm.selTenant.outwater).format("0,0.00");
                    amtdue = "";
                    sbc.globalFunc.formtype = "form2";
                    sbc.globalFunc.showCollectForm("form2");
                    break;
                  case "Others":
                    amtdue = "";
                    sbc.globalFunc.formtype = "form3";
                    sbc.globalFunc.showCollectForm("form3");
                    break;
                }
                if (sbc.modulefunc.docForm.selTenant.clientid !== "" && sbc.modulefunc.docForm.selTenant.clientid !== 0 && typeof(sbc.modulefunc.docForm.selTenant.clientid) !== "undefined") {
                  sbc.globalFunc.checkTrans("payment").then(res => {
                    if (res.hasRecord) {
                      sbc.modulefunc.inputLookupForm.hasrecord = true;
                      if (res.status === "NP") {
                        sbc.modulefunc.inputLookupForm.payment = res.status;
                        sbc.modulefunc.inputLookupForm.amt = "";
                        sbc.modulefunc.inputLookupForm.balance = "";
                      } else {
                        switch (sbc.modulefunc.docForm.transtype) {
                          case "Rent": case "CUSA":
                            sbc.modulefunc.inputLookupForm.payment = sbc.numeral(res.amt).format("0,0.00");
                            if(sbc.numeral(sbc.modulefunc.inputLookupForm.outstandingbal).value() < 0) {
                              sbc.modulefunc.inputLookupForm.amt = sbc.numeral(sbc.numeral(sbc.modulefunc.inputLookupForm.outstandingbal).value() + sbc.numeral(res.amt).value()).format("0,0.00");
                            } else {
                              sbc.modulefunc.inputLookupForm.amt = sbc.numeral(sbc.numeral(sbc.modulefunc.inputLookupForm.outstandingbal).value() - sbc.numeral(res.amt).value()).format("0,0.00");
                            }
                            if(sbc.modulefunc.docForm.transtype === "Rent") {
                              if (Math.abs(sbc.numeral(sbc.modulefunc.inputLookupForm.outstandingbal).value()) < sbc.numeral(sbc.modulefunc.docForm.selTenant.dailyrent).value()) {
                                if (sbc.numeral(sbc.modulefunc.inputLookupForm.amt).value() < 0) sbc.modulefunc.inputLookupForm.amt = "0.00";
                              }
                            } else {
                              if (Math.abs(sbc.numeral(sbc.modulefunc.inputLookupForm.outstandingbal).value()) < sbc.numeral(sbc.modulefunc.docForm.selTenant.dcusa).value()) {
                                if (sbc.numeral(sbc.modulefunc.inputLookupForm.amt).value() < 0) sbc.modulefunc.inputLookupForm.amt = "0.00";
                              }
                            }
                            break;
                          case "Others":
                            sbc.modulefunc.inputLookupForm.remarks = res.remarks;
                            sbc.modulefunc.inputLookupForm.payment = res.amt;
                            break;
                          case "Electricity": case "Water":
                            sbc.modulefunc.inputLookupForm.payment = "";
                            sbc.modulefunc.inputLookupForm.balance = sbc.numeral(sbc.numeral(sbc.modulefunc.inputLookupForm.outstandingbal).value() - res.amt).format("0,0.00");
                            if(sbc.numerl(sbc.modulefunc.inputLookupForm.balance).value() < 0) sbc.modulefunc.inputLookupForm.balance = "0.00";
                            break;
                        }
                      }
                    } else {
                      sbc.modulefunc.inputLookupForm.hasrecord = false;
                      sbc.modulefunc.inputLookupForm.balance = sbc.numeral(sbc.modulefunc.inputLookupForm.outstandingbal).format("0,0.00");
                      sbc.modulefunc.inputLookupForm.payment = payment;
                      sbc.modulefunc.inputLookupForm.amt = amtdue;
                      sbc.modulefunc.inputLookupForm.remarks = "";
                    }
                  });
                  $q.loading.hide();
                }
                sbc.showInputLookup = true;
                console.log("waws: ", sbc.modulefunc.inputLookupForm);
              });
            }
          }
          break;
        case "shiftlookup":
          sbc.modulefunc.cLookupForm.shiftt = row.shiftt;
          sbc.showLookup = false;
          break;
        case "seltypelookup":
          sbc.modulefunc.cLookupForm.seltype = row.seltype;
          sbc.showLookup = false;
          break;
        case "designationlookup":
          sbc.modulefunc.cLookupForm.designation = row.designation;
          sbc.showLookup = false;
          break;
        case "colorlookup":
          sbc.modulefunc.cLookupForm.color = row.color;
          sbc.showLookup = false;
          break;
        case "paintsupplookup":
          sbc.modulefunc.cLookupForm.paintsupp = row.paintsupp;
          sbc.showLookup = false;
          break;
        case "locationlookup":
          sbc.modulefunc.cLookupForm.location = row.location;
          sbc.showLookup = false;
          break;
        case "divisionlookup":
          sbc.modulefunc.cLookupForm.division = row.division;
          sbc.showLookup = false;
          break;
        case "emaillookup":
          sbc.modulefunc.docForm.email = row.email;
          sbc.modulefunc.docForm.idno = row.id;
          sbc.showLookup = false;
          break;
        case "sapScanList":
          break;
        case "invItemLookup":
          sbc.modulefunc.selectItem(row);
          break;
        case "invWhsLookup":
          sbc.modulefunc.selectWhs(row);
          // sbc.globalFunc.selectInvWhs(row);
          break;
        case "mbsGenerate":
          sbc.modulefunc.gtype = row.gtype;
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from wh limit 1", [], function (tx, res) {
              if (res.rows.length > 0) {
                sbc.showLookup = false;
                sbc.modulefunc.cLookupForm = { generatetype: "all", wh: res.rows.item(0).client, branch: res.rows.item(0).branch, generated: res.rows.item(0).generated, filename: res.rows.item(0).filename };
                sbc.modulefunc.generateReport();
              } else {
                sbc.globalFunc.showErrMsg("err1: Error generating report, Please try again.");
              }
            }, function (tx, err) {
              sbc.globalFunc.showErrMsg("err2: Error generating report, Please try again.");
            });
          });
          break;
        case "mbsUpload":
          sbc.modulefunc.gtype = row.gtype;
          sbc.modulefunc.uploadMBSDoc();
          break;
        default:
          cfunc.showMsgBox("Lookup not set", "negative", "warning");
          break;
      }
    }';
    $functions = '({
      backendVersion: "' . env('appversion') . '",
      manualLoginForm: [],
      adminLoginForm: { username: "", password: "" },
      operationLoginForm: { username: "", password: "" },
      selectLookupType: "",
      printLayout: [],
      printData: [],
      recon: 0,
      receiptData: [],
      lookupTableSelect: true,
      lookupTableSelection: "single",
      lookupTableRowKey: "",
      lookupSelected: [],
      customLookupGrid: false,
      lookupCols: [],
      lookupData: [],
      lookupFields: [],
      lookupAction: "",
      cartICount: 0,
      itemLookupType: "",
      iType: "",
      dType: "",
      docSeq: "",
      lastSeq: 0,
      logStorage: [],
      logMsg: "",
      config: "' . $this->config . '",
      company: "' . $this->company . '",
      downloadItems: ' . $downloadItems . ',
      downloadUOM: ' . $downloadUOM . ',
      downloadTerms: ' . $downloadTerms . ',
      downloadItemBal: ' . $downloadItemBal . ',
      downloadOthers: ' . $downloadOthers . ',
      handleLogin: ' . $handleLogin . ',
      operationTab: function () {
        console.log("operationTab function called from api");
        cfunc.getTableData("config", "username").then(username => {
          console.log("collector: ", username);
          if(username === "") {
            cfunc.showMsgBox("Collector not set", "negative", "warning");
          } else {
            sbc.globalFunc.operationLoginForm.username = username;
          }
        });
      },
      adminLogin: function () {
        console.log("adminLogin function called from api");
        cfunc.showLoading();
        cfunc.getTableData("config", "serveraddr").then(serveraddr => {
          if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            $q.loading.hide();
          } else {
            cfunc.encryptCode(sbc.globalFunc.adminLoginForm.password).then(pwd => {
              const params = { username: md5(sbc.globalFunc.adminLoginForm.username), password: md5(sbc.globalFunc.adminLoginForm.password), type: "admin", pwd: md5(pwd) };
              api.post(serveraddr + "/sbcmobilev2/userLogin", { params: params })
                .then(res => {
                  if (res.data.status) {
                    sbc.globalFunc.logStorage = { user: res.data.user[0], type: "admin", url: serveraddr + "/collectionadmin" };
                    sbc.globalFunc.logMsg = res.data.msg;
                    loadCenters(serveraddr);
                    $q.loading.hide();
                  } else {
                    cfunc.showMsgBox(res.data.msg, "negative", "warning");
                    $q.loading.hide();
                  }
                })
                .catch(err => {
                  cfunc.showMsgBox(err.message, "negative", "warning");
                  $q.loading.hide();
                });
            });
          }
        });

        function loadCenters (serveraddr) {
          cfunc.showLoading();
          api.post(serveraddr + "/sbcmobilev2/loadcenters")
            .then(res => {
              console.log("centers: ", res.data.centers);
              sbc.globalFunc.lookupTableSelect = false;
              sbc.globalFunc.lookupCols = [{ name: "code", label: "Code", align: "left", field: "code", sortable: true }, { name: "name", label: "Name", align: "left", field: "name", sortable: true }];
              sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Center", func: "" };
              sbc.lookupTitle = "Select Center";
              sbc.showLookup = true;
              sbc.globalFunc.lookupAction = "centerLookup";
              sbc.globalFunc.lookupData = res.data.centers;
            })
            .catch(err => {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
        }
      },
      operationLogin: function () {
        console.log("operationLogin function called from api");
        if (sbc.globalFunc.operationLoginForm.username !== "" && sbc.globalFunc.operationLoginForm.password !== "") {
          cfunc.showLoading();
          cfunc.getTableData("config", ["collectorid", "username", "password", "serveraddr"], true).then(configdata => {
            if (configdata.username !== sbc.globalFunc.operationLoginForm.username) {
              cfunc.showMsgBox("Invalid username", "negative", "warning");
              $q.loading.hide();
              return;
            }
            if (configdata.password !== sbc.globalFunc.operationLoginForm.password) {
              cfunc.showMsgBox("Invalid password", "negative", "warning");
              $q.loading.hide();
              return;
            }
            $q.localStorage.remove("sbcmobilev2Data");
            let data = {  user: { username: configdata.username, password: configdata.password, name: configdata.username }, collectorid: configdata.collectorid, type: "operation", url: configdata.serveraddr };
            $q.localStorage.set("sbcmobilev2Data", data);
            $q.loading.hide();
            router.push("/");
          });
        }
      },
      downloadUsers: function () {
        cfunc.getTableData("config", "serveraddr").then(serveraddr => {
          if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            $q.loading.hide();
          } else {
            console.log("---------------asd-", serveraddr);
            let url = serveraddr + "/sbcmobilev2/download";
            // let url = "";
            // if (serveraddr.includes("https://")) {
            //   url = serveraddr;
            // } else {
            //   url = "https://" + serveraddr + "/mobileapi/sbcmobilev2/download";
            // }
            api.post(url, { type: "users" }).then(res => {
              if (sbc.settings.downloadAllUsers) {
                if (res.data.users.length > 0) {
                  cfunc.clearTable("users");
                  saveUsers(res.data.users).then(() => {
                    if (res.data.useraccess.length > 0) {
                      cfunc.clearTable("useraccess").then(() => {
                        saveUserAccess(res.data.useraccess);
                      });
                    } else {
                      cfunc.showMsgBox("No Users to save", "negative", "warning");
                      $q.loading.hide();
                    }
                  });
                } else {
                  if (res.data.useraccess.length > 0) {
                    cfunc.clearTable("useraccess").then(() => {
                      saveUserAccess(res.data.useraccess);
                    });
                  } else {
                    cfunc.showMsgBox("No Users to save", "negative", "warning");
                    $q.loading.hide();
                  }
                }
              } else {
                if (res.data.useraccess.length > 0) {
                  sbc.globalFunc.lookupTableSelect = false;
                  sbc.globalFunc.lookupCols = [{ name: "name", label: "Name", align: "left", field: "name", sortable: true }];
                  sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Agent", func: "" };
                  sbc.lookupTitle = "Select Agent";
                  sbc.showLookup = true;
                  sbc.globalFunc.lookupRowsPerPage = 20;
                  sbc.globalFunc.lookupAction = "userAgentLookup";
                  sbc.globalFunc.lookupData = res.data.useraccess;
                  sbc.globalFunc.usersData = res.data.users;
                  $q.loading.hide();
                } else {
                  cfunc.showMsgBox("No Users to save", "negative", "warning");
                  $q.loading.hide();
                }
              }
            }).catch(err => {
              cfunc.showMsgBox("error downloading users: " + err.message + "(" + url + ")");
              $q.loading.hide();
            });
          }
        });
        function saveUserAccess (data) {
          var d = data;
          var dd = [];
          var useraccesscount = data.length;
          while (d.length) dd.push(d.splice(0, 100));
          save(dd);

          function save (users, index = 0) {
            cfunc.showLoading("Saving Useraccess (Batch " + index + " of " + users.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === users.length) {
              cfunc.showLoading("Successfully imported " + useraccesscount + " Useraccess");
              setTimeout(function () {
                $q.loading.hide();
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in users[index]) {
                  insertUser(users[index][a]);
                  if (parseInt(a) + 1 === users[index].length) save(users, parseInt(index) + 1);
                }
              });
            }
          }
          function insertUser (data) {
            sbc.db.transaction(function (tx) {
              var qry = "insert into useraccess(userid, accessid, username, password, name, wh) values(?, ?, ?, ?, ?, ?)";
              var param = [data.userid, data.accessid, data.username, data.password, data.name, data.wh];
              tx.executeSql(qry, param, function (tx, res) {
                console.log("useraccess saved: ", data);
              }, function (tx, err) {
                cfunc.saveErrLog(qry, param, err.message);
              });
            });
          }
        }

        function saveUsers (data) {
          return new Promise((resolve, reject) => {
            var d = data;
            var dd = [];
            var usercount = data.length;
            while (d.length) dd.push(d.splice(0, 100));
            save(dd);

            function save (users, index = 0) {
              cfunc.showLoading("Saving Userssss (Batch " + index + " of " + users.length + ")");
              if (index === 0) $q.loading.hide();
              if (index === users.length) {
                cfunc.showLoading("Successfully imported " + usercount + " Users");
                setTimeout(function () {
                  $q.loading.hide();
                  resolve();
                }, 1500);
              } else {
                sbc.db.transaction(function (tx) {
                  for (var a in users[index]) {
                    insertUser(users[index][a]);
                    if (parseInt(a) + 1 === users[index].length) save(users, parseInt(index) + 1);
                  }
                });
              }
            }

            function insertUser (data) {
              console.log("insertUser called");
              sbc.db.transaction(function (tx) {
                var qry = "insert into users(idno, attributes) values(?, ?)";
                var param = [data.idno, data.attributes];
                tx.executeSql(qry, param, [], function (tx, err) {
                  console.log("error saving user: ", err.message);
                  cfunc.saveErrLog(qry, param, err.message);
                });
              });
            }
          });
        }
      },
      continueDownloadAgent: function (agent) {
        const prevAgent = $q.localStorage.getItem("sbcPrevAgent");
        if (prevAgent !== null && prevAgent !== [] && typeof(prevAgent) !== "undefined") {
          if (prevAgent.username !== agent.username) {
            $q.loading.hide();
            $q.dialog({
              message: prevAgent.name + " is using this app, Do you want to continue?",
              ok: { flat: true, color: "primary" },
              cancel: { flat: true, color: "negative" }
            }).onOk(() => {
              cfunc.showLoading();
              sbc.globalFunc.checkAgentTrans().then(res => {
                if (res) {
                  cfunc.showMsgBox("Agent " + prevAgent.name + " already have transactions, cannot download other agent.", "negative", "warning");
                  $q.loading.hide();
                } else {
                  saveAgent();
                }
              })
            });
          } else {
            cfunc.showMsgBox("Agent already saved", "negative", "warning");
            $q.loading.hide();
          }
        } else {
          saveAgent();
        }
        function saveAgent () {
          sbc.globalFunc.clearAgentTables().then(() => {
            cfunc.clearTable("useraccess");
            sbc.db.transaction(function (tx) {
              tx.executeSql("insert into useraccess(userid, accessid, username, password, name, wh) values(?, ?, ?, ?, ?, ?)", [agent.userid, agent.accessid, agent.username, agent.password, agent.name, agent.wh], function (tx, res) {
                cfunc.showMsgBox("User saved", "positive");
                tx.executeSql("delete from users", [], function (tx, res2) {
                  console.log("users table cleared");
                });
                if (sbc.globalFunc.usersData.length > 0) {
                  for (var x = 0; x < sbc.globalFunc.usersData.length; x++) {
                    if (sbc.globalFunc.usersData[x].idno === agent.accessid) {
                      tx.executeSql("insert into users(idno, attributes) values(?, ?)", [sbc.globalFunc.usersData[x].idno, sbc.globalFunc.usersData[x].attributes], function (tx, res) {
                        console.log("UsersData saved");
                      })
                    }
                  }
                }
                sbc.showLookup = false;
                $q.loading.hide();
              }, function (tx, err) {
                cfunc.showMsgBox(err.message, "negative", "warning");
                $q.loading.hide();
              });
            });
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        }
      },
      clearAgentTables: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("delete from item");
            tx.executeSql("delete from uom");
            tx.executeSql("delete from itemstat");
            tx.executeSql("delete from customers");
            tx.executeSql("delete from cart");
            tx.executeSql("delete from transhead");
            tx.executeSql("delete from transstock");
            tx.executeSql("delete from transhistoryhead");
            tx.executeSql("delete from transhistorystock");
            tx.executeSql("update config set lastorderno=null, cdlock=null");
            resolve();
          }, function (err) {
            reject(err);
          });
        });
      },
      checkAgentTrans: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from cart", [], function (tx, cartres) {
              if (cartres.rows.length > 0) resolve(true);
              tx.executeSql("select * from transhead", [], function (tx, thres) {
                if (thres.rows.length > 0) resolve(true);
                resolve(false);
              }, function (tx, err2) {
                reject(err2);
              });
            }, function (tx, err1) {
              reject(err1);
            });
          });
        });
      },
      downloadLastOrderno: function () {
        console.log("getLastOrderno called");
        cfunc.getTableData("config", ["serveraddr", "deviceid"], true).then(configdata => {
          if (configdata.serveraddr === "" || configdata.serveraddr === null || typeof(configdata.serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            return;
          }
          api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("getLastOrderno"), deviceid: configdata.deviceid }, { headers: sbc.reqheader }).then(res => {
            if (res.data.orderno !== "") {
              console.log("orderno: ", res.data.orderno);
              sbc.db.transaction(function (tx) {
                tx.executeSql("update config set lastorderno=?", [res.data.orderno], function (tx, res) {
                  console.log("lastorderno saved");
                }, function (tx, err) {
                  console.log("save lastorderno error: ", err.message);
                });
              });
            }
          });
        });
      },
      downloadCustomers: function () {
        console.log("downloadCustomers called");
        const thiss = this;
        let hasrecord = false;
        let cdate = "";
        let clientCount = 0;
        let user = $q.localStorage.getItem("sbcmobilev2Data");
        user = user.user;
        cfunc.showLoading();
        switch (thiss.config) {
          case "ordering":
            cfunc.getTableDataCount("customers").then(ccount => {
              if (ccount > 0) hasrecord = true;
            });
            break;
          default:
            cfunc.getTableDataCount("client").then(ccount => {
              if (ccount > 0) hasrecord = true;
            });
            break;
        }
        cfunc.getTableData("config", ["serveraddr", "cdlock"], true).then(configdata => {
          if (configdata.serveraddr === "" || configdata.serveraddr === null || typeof(configdata.serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            $q.loading.hide();
          }
          if (sbc.settings.custDownloadType === "area") {
            sbc.db.transaction(function (tx) {
              tx.executeSql("delete from customers");
              api.post(configdata.serveraddr + "/sbcmobilev2/download", { type: "client", hasrecord: hasrecord, date: configdata.cdlock, user: user, area: thiss.lookupSelected }).then(res => {
                if (res) {
                  if (res.data.client.length > 0) {
                    cdate = res.data.date;
                    saveClient(res.data.client)
                  } else {
                    cfunc.showMsgBox("No " + (sbc.globalFunc.config === "ordering" ? "Customers" : "Warehouse/Supplier") + " to save", "negative", "warning");
                    $q.loading.hide();
                  }
                } else {
                  cfunc.showMsgBox("Error fetching " + (sbc.globalFunc.config === "ordering" ? "Customers" : "Warehouse/Supplier") + " data, Please try again.", "negative", "warning");
                  $q.loading.hide();
                }
              });
            });
          } else {
            api.post(configdata.serveraddr + "/sbcmobilev2/download", { type: "client", hasrecord: hasrecord, date: configdata.cdlock, user: user, area: thiss.lookupSelected }).then(res => {
              if (res) {
                if (res.data.client.length > 0) {
                  cdate = res.data.date;
                  saveClient(res.data.client)
                } else {
                  cfunc.showMsgBox("No " + (sbc.globalFunc.config === "ordering" ? "Customers" : "Warehouse/Supplier") + " to save", "negative", "warning");
                  $q.loading.hide();
                }
              } else {
                cfunc.showMsgBox("Error fetching " + (sbc.globalFunc.config === "ordering" ? "Customers" : "Warehouse/Supplier") + " data, Please try again.", "negative", "warning");
                $q.loading.hide();
              }
            });
          }
        });
        function saveClient (data) {
          if (data.length > 0) {
            var d = data;
            var dd = [];
            clientCount = data.length;
            while (d.length) dd.push(d.splice(0, 100));
            save(dd);
          } else {
            cfunc.showMsgBox("No " + (sbc.globalFunc.config === "ordering" ? "Customers" : "Warehouse/Supplier") + " to save", "negative", "warning");
            $q.loading.hide();
          }
        }
        function save (client, index = 0) {
          cfunc.showLoading(`Saving ` + (sbc.globalFunc.config === "ordering" ? "Customers" : "Warehouse/Supplier") + `(Batch ${index} of ${client.length})`);
          if (index === 0) $q.loading.hide();
          if (index === client.length) {
            cfunc.showLoading(`Successfully imported ${clientCount} ` + (sbc.globalFunc.config === "ordering" ? "Customers" : "Warehouse/Supplier"));
            sbc.db.transaction(function (tx) {
              tx.executeSql("update config set cdlock=?", [cdate], function (tx, res) {
                setTimeout(function () {
                  $q.loading.hide();
                  sbc.showSelectLookup = false;
                }, 1500);
              });
            });
          } else {
            sbc.db.transaction(function (tx) {
              for (var a in client[index]) {
                saveWhSupp(client[index][a]);
                if (parseInt(a) + 1 === client[index].length) save(client, parseInt(index) + 1);
              }
            });
          }
        }
        function saveWhSupp (data) {
          sbc.db.transaction(function (tx) {
            var qry = "";
            var param = [];
            switch (sbc.globalFunc.config) {
              case "ordering":
                if (!hasrecord) {
                  qry = "insert into customers(clientid, client, clientname, addr, tel, isinactive, terms, flr, brgy, area, province, region) values(" + data.clientid + ", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                  param = [data.client, data.clientname, data.addr, data.tel, data.isinactive, data.terms, data.flr, data.brgy, data.area, data.province, data.region];
                  tx.executeSql(qry, param, null, function (tx, err) {
                    cfunc.saveErrLog(qry, param, err.message + " err1");
                  });
                } else {
                  tx.executeSql("select clientid from customers where clientid=" + data.clientid, [], function (tx, res) {
                    if (res.rows.length > 0) {
                      qry = "update customers set client=?, clientname=?, addr=?, tel=?, isinactive=?, terms=?, flr=?, brgy=?, area=?, province=?, region=? where clientid=" + data.clientid;
                      param = [data.client, data.clientname, data.addr, data.tel, data.isinactive, data.terms, data.flr, data.brgy, data.area, data.province, data.region];
                      tx.executeSql(qry, param, null, function (tx, err) {
                        cfunc.saveErrLog(qry, param, err.message + " err2");
                      });
                    } else {
                      qry = "insert into customers(clientid, client, clientname, addr, tel, isinactive, terms, flr, brgy, area, province, region) values(" + data.clientid + ", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                      param = [data.client, data.clientname, data.addr, data.tel, data.isinactive, data.terms, data.flr, data.brgy, data.area, data.province, data.region];
                      tx.executeSql(qry, param, null, function (tx, err) {
                        cfunc.saveErrLog(qry, param, err.message + " err3");
                      });
                    }
                  });
                }
                break;
              default:
                if (!hasrecord) {
                  qry = "insert into client(clientid, client, clientname, addr, tel, tel2, isinactive, iswarehouse, issupplier) values(" + data.clientid + ", ?, ?, ?, ?, ?, ?, ?, ?)";
                  param = [data.client, data.clientname, data.addr, data.tel, data.tel2, data.isinactive, data.iswarehouse, data.issupplier];
                  tx.executeSql(qry, param, null, function (tx, err) {
                    cfunc.saveErrLog(qry, param, err.message + " err1");
                  })
                } else {
                  tx.executeSql("select clientid from client where clientid=" + data.clientid, [], function (tx, res) {
                    if (res.rows.length > 0) {
                      qry = "update client set client=?, clientname=?, addr=?, tel=?, tel2=?, isinactive=" + data.isinactive + ", iswarehouse=" + data.iswarehouse + ", issupplier=" + data.issupplier + " where clientid=" + data.clientid;
                      tx.executeSql(qry, [data.client, data.clientname, data.addr, data.tel, data.tel2], null, function (tx, err) {
                        cfunc.saveErrLog(qry, [], err.message + " err2");
                      });
                    } else {
                      qry = "insert into client(clientid, client, clientname, addr, tel, tel2, isinactive, iswarehouse, issupplier) values(" + data.clientid + ", ?, ?, ?, ?, ?, ?, ?, ?)";
                      param = [data.client, data.clientname, data.addr, data.tel, data.tel2, data.isinactive, data.iswarehouse, data.issupplier];
                      tx.executeSql(qry, param, null, function (tx, err) {
                        cfunc.saveErrLog(qry, param, err.message + " err3");
                      });
                    }
                  });
                }
                break;
            }
          });
        }
      },
      loadAreaLookup: function () {
        cfunc.getTableData("config", "serveraddr").then(serveraddr => {
          if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            return;
          }
          sbc.globalFunc.lookupTableSelect = true;
          sbc.globalFunc.lookupTableSelection = "multiple";
          sbc.globalFunc.lookupTableRowKey = "area";
          sbc.globalFunc.lookupCols = [{ name: "area", label: "Area", align: "left", field: "area" }];
          let areas = [];
          api.post(serveraddr + "/sbcmobilev2/download", { type: "area" }).then(res => {
            areas = res.data.areas;
            sbc.modulefunc.selectLookupTableFilter = { type: "filter", field: "", label: "Search Area", func: "" };
            sbc.globalFunc.lookupData = areas;
            sbc.globalFunc.lookupAction = "arealookup";
            sbc.globalFunc.selectLookupType = "area";
            sbc.lookupTitle = "Select Area";
            sbc.showSelectLookup = true;
            $q.loading.hide();
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      downloadClient: function() {
        switch (sbc.settings.custDownloadType) {
          case "area":
            sbc.globalFunc.loadAreaLookup();
            break;
          default:
            sbc.globalFunc.downloadCustomers();
            break;
        }
      },
      loadLookup: ' . $loadLookup . '
      },
      getItemFactor: function (item) {
        const thiss = this;
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select uom, factor, uom2, factor2, uom3, factor3, uom4, factor4, uom5, factor5, uom6, factor6 from item where itemid=" + item.itemid + " and (uom=? or uom2=? or uom3=? or uom4=? or uom5=? or uom6=?)", [item.uom, item.uom, item.uom, item.uom, item.uom, item.uom], function (tx, res) {
              if (res.rows.length > 0) {
                if (item.uom === res.rows.item(0).uom) {
                  resolve(res.rows.item(0).factor);
                } else if (item.uom === res.rows.item(0).uom2) {
                  resolve(res.rows.item(0).factor2);
                } else if (item.uom === res.rows.item(0).uom3) {
                  resolve(res.rows.item(0).factor3);
                } else if (item.uom === res.rows.item(0).uom4) {
                  resolve(res.rows.item(0).factor4);
                } else if (item.uom === res.rows.item(0).uom5) {
                  resolve(res.rows.item(0).factor5);
                } else if (item.uom === res.rows.item(0).uom6) {
                  resolve(res.rows.item(0).factor6);
                } else {
                  resolve(1);
                }
              } else {
                resolve(1);
              }
            }, function (tx, err) {
              reject(err);
            });
          });
        });
      },
      selectLookup: ' . $selectLookup . ',
      checkDuplicates: function (type) {
        console.log("checkDuplicates");
        const thiss = this;
        let obj = {};
        let datas = { data: "", field: "", sort: "" };
        switch (type) {
          case "customers": datas = { data: thiss.lookupData, field: "clientid", sort: "clientname" }; break;
          case "items": datas = { data: thiss.lookupData, field: "itemid", sort: "itemid" }; break;
          case "uom": datas = { data: thiss.lookupData, field: "line", sort: "uom" }; break;
        }
        for (var i = 0, len = datas.data.length; i < len; i++) {
          obj[datas.data[i][datas.field]] = datas.data[i];
        }
        datas.data = [];
        for (var key in obj) datas.data.push(obj[key]);
        datas.data.sort((a, b) => (a[datas.sort] > b[datas.sort]) ? 1 : ((b[datas.sort] > a[datas.sort]) ? -1 : 0));
        thiss.lookupData = datas.data;
        console.log(thiss.lookupData);
      },
      searchItem: function (type = "") {
        const thiss = this;
        let barcode = "";
        if (type === "scan") {
          barcode = sbc.modulefunc[sbc.modulefunc.tableFilter.field];
        } else {
          barcode = sbc.selItem.barcode;
        }
        if (barcode !== "") {
          cfunc.showLoading();
          cfunc.getTableData("config", "serveraddr").then(serveraddr => {
            if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
              cfunc.showMsgBox("Server Address not set", "negative", "warning");
              $q.loading.hide();
              return;
            }
            // const url = "https://" + serveraddr + "/mobileapi/sbcmobilev2/admin";
            const url = serveraddr + "/sbcmobilev2/admin";
            api.post(url, { id: md5("searchItem"), barcode: barcode }, { headers: sbc.reqheader }).then(res => {
              if (type === "scan") {
                sbc.modulefunc.tableData = [];
                sbc.modulefunc[sbc.modulefunc.tableFilter.field] = "";
                // sbc.modulefunc.balInqData = [];
                // sbc.modulefunc.docListForm.barcode = "";
                // sbc.modulefunc.balInqInfo = "<span>";
                // if (res.data.itemname !== "") sbc.modulefunc.balInqInfo += "Item Name: <b>" + res.data.itemname + "</b>";
                // if (res.data.barcode !== "") sbc.modulefunc.balInqInfo += "<br>Barcode: <b>" + res.data.barcode + "</b>";
                // sbc.modulefunc.balInqInfo += "</span>";
              }
              if (res.data.success) {
                sbc.modulefunc.tableData = res.data.info;
                if (sbc.modulefunc.tableData.length > 0) {
                  for (var d in sbc.modulefunc.tableData) {
                    sbc.modulefunc.tableData[d].uom = res.data.uom;
                  }
                }
                // sbc.modulefunc.balInqData = res.data.info;
                // if (sbc.modulefunc.balInqData.length > 0) {
                //   for (var d in sbc.modulefunc.balInqData) {
                //     sbc.modulefunc.balInqData[d].uom = res.data.uom;
                //   }
                // }
                sbc.showLookup = false;
                $q.loading.hide();
              } else {
                cfunc.showMsgBox(res.data.msg, "negative", "warning");
                $q.loading.hide();
              }
            }).catch(err => {
              cfunc.showMsgBox(err.message + " searchItem Err#1", "negative", "warning");
              $q.loading.hide();
            });
          })
        }
      },
      subtractQty: function () {
        cfunc.showLoading();
        switch (sbc.globalFunc.config) {
          case "ordering":
            sbc.globalFunc.updateQty("subtract");
            break;
          default:
            sbc.globalFunc.getItemFactor(sbc.selItem).then(factor => {
              let qty = parseInt(sbc.selItem.rrqty);
              if (qty <= 0) {
                sbc.selItem.rrqty = 0;
                sbc.selItem.qty = 0;
              } else {
                qty -= 1;
                sbc.selItem.rrqty = qty;
                sbc.selItem.qty = qty * parseInt(factor);
              }
              $q.loading.hide();
            }).catch(err => {
              cfunc.showMsgBox(err.message + " subtractQty Err#1", "negative", "warning");
              $q.loading.hide();
            });
            break;
        }
      },
      addQty: function () {
        cfunc.showLoading();
        switch (sbc.globalFunc.config) {
          case "ordering":
            sbc.globalFunc.updateQty("add");
            break;
          default:
            sbc.globalFunc.getItemFactor(sbc.selItem).then(factor => {
              let qty = parseInt(sbc.selItem.rrqty);
              qty += 1;
              sbc.selItem.rrqty = qty;
              sbc.selItem.qty = qty * parseInt(factor);
              $q.loading.hide();
            }).catch(err => {
              cfunc.showMsgBox(err.message + " addQty Err#2", "negative", "warning");
              $q.loading.hide();
            });
            break;
        }
      },
      updateQty: function (type) {
        let index = sbc.globalFunc.getIndex(sbc.modulefunc.tableData, sbc.selItem, "itemid");
        let index2 = sbc.globalFunc.getIndex(sbc.modulefunc.lookupTableData, sbc.selItem, "itemid");
        let qty;
        let amt;
        let discount;
        switch (sbc.globalFunc.company) {
          case "sbc":
            discount = sbc.globalFunc.computeDiscount(sbc.numeral(sbc.selItem.iamt).value(), sbc.selItem.disc);
            amt = sbc.numeral(sbc.numeral(discount).value() * sbc.numeral(sbc.selItem.factor).value()).format("0,0.00");
            break;
          default:
            amt = sbc.numeral(sbc.selItem.newamt).format("0,0.00");
            discount = sbc.globalFunc.computeDiscount(sbc.numeral(sbc.selItem.newamt).value(), sbc.selItem.newdisc);
            break;
        }
        let total;
        switch (type) {
          case "add":
            qty = parseInt(sbc.selItem.qty) + 1;
            break;
          case "subtract":
            qty = parseInt(sbc.selItem.qty) - 1;
            if (qty <= 0) qty = 0;
            break;
          case "checkqty":
            qty = parseInt(sbc.selItem.qty);
            if (qty <= 0 && isNaN(qty)) qty = 0;
            break;
        }
        if (qty <= 0 || isNaN(qty)) {
          $q.dialog({
            message: "Do you want to remove this item from cart?",
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            sbc.db.transaction(function (tx) {
              let sql;
              let data = [];
              if (sbc.globalFunc.company === "sbc") {
                console.log("delete cart");
                sql = "delete from cart where itemid=" + sbc.selItem.itemid;
              } else {
                sql = "update item set newamt=amt, newuom=uom, newfactor=factor, rem=?, newdisc=disc, seq=0 where itemid=" + sbc.selItem.itemid;
                data = [""];
              }
              tx.executeSql(sql, data, function (tx, res) {
                if (typeof(sbc.modulefunc.tableData[index]) !== "undefined") {
                  sbc.modulefunc.tableData[index].hasqty = false;
                  if (sbc.globalFunc.company === "sbc") {
                    sbc.modulefunc.tableData[index].isamt = 0;
                    sbc.modulefunc.tableData[index].amt = 0;
                    sbc.modulefunc.tableData[index].factor = 0;
                    sbc.modulefunc.tableData[index].rem = "";
                    sbc.modulefunc.tableData[index].isqty = 0;
                    sbc.modulefunc.tableData[index].qty = 0;
                    sbc.modulefunc.tableData[index].iss = 0;
                    sbc.modulefunc.tableData[index].ext = 0;
                    sbc.modulefunc.tableData[index].bgColor = "";
                    sbc.modulefunc.tableData[index].disc = "";
                  } else {
                    sbc.modulefunc.tableData[index].newamt = sbc.selItem.amt;
                    sbc.modulefunc.tableData[index].newuom = sbc.selItem.uom;
                    sbc.modulefunc.tableData[index].newfactor = sbc.selItem.factor;
                    sbc.modulefunc.tableData[index].newdisc = sbc.selItem.newdisc;
                  }
                  sbc.modulefunc.tableData[index].newitembal = sbc.selItem.itembal;
                }
                sbc.showEditItem = false;
                if (typeof(sbc.modulefunc.lookupTableData[index2]) !== "undefined") {
                  sbc.modulefunc.lookupTableData.splice(index2, 1);
                }
                sbc.globalFunc.refreshCart();
              });
            });
          }).onCancel(() => {
            qty = 1;
          });
        } else {
          if (qty > sbc.selItem.newitembal) {
            cfunc.showMsgBox("Insufficient item balance", "negative", "warning");
            switch (type) {
              case "add": qty -= 1; break;
              case "checkqty": qty = Math.floor(sbc.numeral(sbc.selItem.newitembal).value()); break;
            }
          }
          total = sbc.numeral(sbc.numeral(amt).value() * qty).format("0,0.00");
          sbc.selItem.newamt = amt;
          sbc.selItem.total = total;
          sbc.selItem.qty = qty;
          if (typeof(sbc.modulefunc.tableData[index]) !== "undefined") {
            sbc.modulefunc.tableData[index].qty = qty;
            if (qty > 0) sbc.modulefunc.tableData[index].hasqty = true;
            if (sbc.globalFunc.company === "sbc") {
              sbc.modulefunc.tableData[index].isamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() * sbc.numeral(sbc.selItem.factor).value()).format("0,0.00");
              sbc.modulefunc.tableData[index].amt = sbc.numeral(sbc.numeral(discount).value() * sbc.numeral(sbc.selItem.factor).value()).value();
              sbc.modulefunc.tableData[index].ext = total;
              sbc.modulefunc.tableData[index].factor = sbc.selItem.factor;
              sbc.modulefunc.tableData[index].uom = sbc.selItem.uom;
              if (qty > 0) sbc.modulefunc.tableData[index].bgColor = "bg-blue-2";
            }
          }
          if (typeof(sbc.modulefunc.lookupTableData[index2]) !== "undefined") {
            sbc.modulefunc.lookupTableData[index2].qty = qty;
            if (sbc.globalFunc.company === "sbc") {
              sbc.modulefunc.lookupTableData[index2].isamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() * sbc.numeral(sbc.selItem.factor).value()).format("0,0.00");
              sbc.modulefunc.lookupTableData[index2].amt = sbc.numeral(sbc.numeral(discount).value() * sbc.numeral(sbc.selItem.factor).value()).value();
              sbc.modulefunc.lookupTableData[index2].ext = total;
              sbc.modulefunc.lookupTableData[index2].factor = sbc.selItem.factor;
              sbc.modulefunc.lookupTableData[index2].uom = sbc.selItem.uom;
            }
          }
          switch(type) {
            case "add":
              if (qty === 1) {
                sbc.globalFunc.updateItem(index, sbc.selItem, qty, "new", sbc.globalFunc.lastSeq + 1);
              } else {
                sbc.globalFunc.updateItem(index, sbc.selItem, qty);
              }
              break;
            case "subtract": case "checkqty":
              sbc.globalFunc.updateItem(index, sbc.selItem, qty, "checkqty");
              break;
          }
        }
        // sbc.globalFunc.refreshCart();
        // sbc.globalFunc.getLastSeq();
        $q.loading.hide();
      },
      updateItem: function (index, row, qty, type = "", seq = 0) {
        let sql;
        let data;
        console.log("updateItem called");
        switch (sbc.globalFunc.company) {
          case "sbc":
            sbc.db.transaction(function (tx) {
              tx.executeSql("select itemid from cart where itemid=" + row.itemid, [], function (tx, res) {
                let discount = sbc.globalFunc.computeDiscount(sbc.numeral(row.iamt).value(), row.disc);
                let isqty = qty;
                let iss = qty * row.factor;
                let isamt = sbc.numeral(sbc.numeral(row.iamt).value() * sbc.numeral(row.factor).value()).value();
                let amt = sbc.numeral(sbc.numeral(discount).value() * sbc.numeral(row.factor).value()).value();
                let total = sbc.numeral(amt * row.qty).value();
                if (res.rows.length > 0) {
                  sql = "update cart set isqty=?, iss=?, isamt=?, amt=?, ext=? where itemid=" + row.itemid;
                  data = [isqty, iss, isamt, amt, total];
                } else {
                  if (type === "new" || type === "checkqty") {
                    sql = "insert into cart(itemid, isqty, iss, isamt, amt, ext, disc, uom, factor, rem) values(" + row.itemid + ", ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    data = [isqty, iss, isamt, amt, total, row.disc, row.uom, row.factor, row.rem];
                  } else {
                    sql = "update cart set isqty=?, iss=?, isamt=?, amt=?, ext=? where itemid=" + row.itemid;
                    data = [isqty, iss, isamt, amt, total];
                  }
                }
                console.log("save cart");
                tx.executeSql(sql, data, function (tx, res) {
                  sbc.globalFunc.getLastSeq();
                  sbc.globalFunc.refreshCart();
                  console.log("cart saved/updated");
                }, function (tx, err) {
                  cfunc.showMsgBox(err.message, "negative", "warning");
                  cfunc.saveErrLog(sql, data, err.message);
                });
              });
            }, function (err) {
              console.log("updateitem error: ", err.message);
            });
            break;
          default:
            sql = "update item set qty=? where itemid=" + row.itemid;
            data = [qty];
            if (type === "new") {
              sql = "update item set qty=?, seq=? where itemid=" + row.itemid;
              data = [qty, seq];
            }
            sbc.db.transaction(function (tx) {
              tx.executeSql(sql, data, function (tx, res) {
                sbc.globalFunc.getLastSeq();
              }, function (tx, err) {
                cfunc.showMsgBox(err.message, "negative", "warning");
                cfunc.saveErrLog(sql, data, err.message);
              });
            });
            break;
        }
      },
      getLastSeq: function () {
        sbc.db.transaction(function (tx) {
          tx.executeSql("select max(seq) as seq from item", [], function (tx, res) {
            if (res.rows.length > 0) sbc.globalFunc.lastSeq = res.rows.item(0).seq;
          });
        });
      },
      computeDiscount: function (amount, disc) {
        let m = -1;
        let amtdisc = 0;
        let discv = "";
        let amt = 0;
        let a;
        if (disc !== "" && disc !== null) {
          disc = disc + "";
          disc = disc.split("/");
        } else { disc = 0; }
        if (disc !== 0 && disc !== null) {
          for (a in disc) {
            amt = 0;
            discv = disc[a];
            if (disc[a].substr(0, 1) === "+") {
              discv = disc[a].substr(1);
              m = 1;
            }
            if (discv.substr(-1) === "%") {
              amtdisc = parseFloat(amount * (discv.substr(0, discv.length - 1) / 100));
            } else {
              amtdisc = parseFloat(discv);
            }
            if (!isNaN(amtdisc)) {
              amt = parseFloat(sbc.numeral(amt).value() + (amtdisc * m));
              amount = sbc.numeral(amount).value() + amt;
              console.log("amtdisc: ", amtdisc, " amount:", amount);
            }
          }
        }
        return amount;
      },
      checkQty: function () {
        let qty = 0;
        switch (sbc.globalFunc.config) {
          case "ordering":
            sbc.globalFunc.updateQty("checkqty");
            break;
          default:
            qty = parseInt(sbc.selItem.rrqty);
            cfunc.showLoading();
            if (qty <= 0) {
              sbc.selItem.rrqty = 0;
              sbc.selItem.qty = 0;
              $q.loading.hide();
            } else {
              sbc.globalFunc.getItemFactor(sbc.selItem).then(factor => {
                sbc.selItem.rrqty = qty;
                sbc.selItem.qty = qty * parseInt(factor);
                $q.loading.hide();
              }).catch(err => {
                cfunc.showMsgBox(err.message + " checkQty Err#1", "negative", "warning");
                $q.loading.hide()
              });
            }
            break;
        }
      },
      saveItem: function () {
        const thiss = this;
        cfunc.showLoading();
        switch (sbc.globalFunc.iType) {
          case "new":
            cfunc.getLastStockLine(sbc.modulefunc.docForm.trno).then(line => {
              sbc.db.transaction(function (tx) {
                tx.executeSql("select line, qty, rrqty from stock where trno=" + sbc.selItem.trno + " and itemid=" + sbc.selItem.itemid + " and uom=?", [sbc.selItem.uom], function (tx, res) {
                  if (res.rows.length > 0) {
                    let qty = parseInt(parseInt(res.rows.item(0).qty) + parseInt(sbc.selItem.qty));
                    let rrqty = parseInt(parseInt(res.rows.item(0).rrqty) + parseInt(sbc.selItem.rrqty));
                    tx.executeSql("update stock set qty=" + qty + ", rrqty=" + rrqty + " where trno=" + sbc.selItem.trno + " and line=" + res.rows.item(0).line, [], function (tx, res2) {
                      if (res2.rowsAffected > 0) {
                        cfunc.showMsgBox("Stock updated", "positive");
                        sbc.showEditItem = false;
                        $q.loading.hide();
                        sbc.modulefunc.loadStock(sbc.modulefunc.docForm.trno);
                      } else {
                        cfunc.showMsgBox("Error updating stock, Please try again.", "negative", "warning");
                        $q.loading.hide();
                      }
                    });
                  } else {
                    tx.executeSql("insert into stock(trno, line, itemid, qty, rrqty, uom, devid, station) values(" + sbc.modulefunc.docForm.trno + ", " + line + ", " + sbc.selItem.itemid + ", " + sbc.selItem.qty + ", " + sbc.selItem.rrqty + ", ?, ?, ?)", [sbc.selItem.uom, sbc.modulefunc.docForm.devid, sbc.modulefunc.docForm.station], function (tx, res) {
                      if (res.rowsAffected > 0) {
                        cfunc.showMsgBox("Stock saved", "positive");
                        sbc.modulefunc.docForm.icount += 1;
                        sbc.showEditItem = false;
                        $q.loading.hide();
                        sbc.modulefunc.loadStock(sbc.modulefunc.docForm.trno);
                      } else {
                        $q.loading.hide();
                      }
                    }, function (tx, err) {
                      cfunc.showMsgBox(err.message + " saveItem Err#1", "negative", "warning");
                      $q.loading.hide();
                    });
                  }
                });
              });
            }).catch(err => {
              cfunc.showMsgBox(err.message + " saveItem Err#2", "negative", "warning");
              $q.loading.hide();
            });
            break;
          case "edit":
            sbc.db.transaction(function (tx) {
              tx.executeSql("update stock set qty=" + sbc.selItem.qty + ", rrqty=" + sbc.selItem.rrqty + ", uom=? where trno=" + sbc.selItem.trno + " and line=" + sbc.selItem.line, [sbc.selItem.uom], function (tx, res) {
                if (res.rowsAffected > 0) {
                  cfunc.showMsgBox("Stock updated", "positive");
                  sbc.showEditItem = false;
                  sbc.modulefunc.loadStock(sbc.modulefunc.docForm.trno);
                  $q.loading.hide();
                } else {
                  cfunc.showMsgBox("Nothing to update", "negative", "warning");
                  $q.loading.hide();
                }
              }, function (tx, err) {
                cfunc.showMsgBox(err.message + " saveItem Err#3", "negative", "warning");
                $q.loading.hide();
              });
            });
            break;
        }
      },
      cancelItem: function () {
        sbc.showEditItem = false;
      },
      showButtons: function (buttons) {
        if (sbc.headbuttons.length > 0) {
          var hb = null;
          for (hb in sbc.headbuttons) {
            sbc.headbuttons[hb].show = false;
          }
          for (var hb in sbc.headbuttons) {
            for (var b in buttons) {
              if (sbc.headbuttons[hb].name === buttons[b]) {
                sbc.headbuttons[hb].show = true;
              }
            }
          }
        }
      },
      createDoc: function () {
        const thiss = this;
        return new Promise((resolve) => {
          let trno = 0;
          let docno = "";
          sbc.isFormEdit = true;
          sbc.showHeadForm = true;
          thiss.dType = "new";
          cfunc.showLoading();
          sbc.modulefunc.clearForm();
          cfunc.getTableData("config", sbc.doc.toLowerCase() + "seq").then(seq => {
            if (seq === null || seq === "") seq = 0;
            thiss.docSeq = seq + 1;
            sbc.showDocs = false;
            sbc.modulefunc.docForm.docno = sbc.doc + "" + (seq + 1);
            sbc.modulefunc.docForm.doc = sbc.doc;
            thiss.showButtons(["save","cancel"]);
            sbc.modulefunc.loadStock();
            $q.loading.hide();
          });
        });
      },
      editDoc: function () {
        const thiss = this;
        return new Promise((resolve) => {
          sbc.isFormEdit = true;
          thiss.dType = "edit";
          thiss.showButtons(["save","cancel"]);
        });
      },
      deleteDoc: function () {
        const thiss = this;
        return new Promise((resolve) => {
          $q.dialog({
            message: "Do you want to delete this Document?",
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            cfunc.showLoading();
            sbc.db.transaction(function (tx) {
              tx.executeSql("delete from head where trno=" + sbc.modulefunc.docForm.trno, [], function (tx, res) {
                if (res.rowsAffected > 0) {
                  tx.executeSql("delete from stock where trno=" + sbc.modulefunc.docForm.trno, [], function (tx, res) {
                    cfunc.showMsgBox("Document deleted.", "positive");
                    thiss.loadDoc("last");
                    resolve();
                  }, function (tx, err) {
                    cfunc.showMsgBox(err.message + " deleteDoc Err#1", "negative", "warning");
                    $q.loading.hide();
                  });
                } else {
                  cfunc.showMsgBox("Error deleting Document, Please try again.", "negative", "warning");
                  $q.loading.hide();
                }
              }, function (tx, err) {
                cfunc.showMsgBox(err.message + " deleteDoc Err#2", "negative", "warning");
                $q.loading.hide();
              });
            });
          });
        });
      },
      saveDoc: function () {
        const thiss = this;
        return new Promise((resolve) => {
          let addCol = "";
          let addData = "";
          if (sbc.modulefunc.docForm.whid == "" || sbc.modulefunc.docForm.whid === 0) {
            cfunc.showMsgBox("Please select Warehouse", "negative", "warning");
            return;
          }
          cfunc.showLoading();
          switch (thiss.dType) {
            case "new":
              cfunc.getTableData("config", ["deviceid", "stationname"], true).then(res => {
                sbc.modulefunc.docForm.devid = res.deviceid;
                sbc.modulefunc.docForm.station = res.stationname;
                sbc.db.transaction(function (tx) {
                  let trno = 1;
                  let qry = "";
                  tx.executeSql("select trno from (select trno from head union all select trno from hhead) as t order by trno desc limit 1", [], function (tx, htrno) {
                    if (htrno.rows.length > 0) trno = parseInt(htrno.rows.item(0).trno) + 1;
                    if (sbc.modulefunc.docForm.whid === "") sbc.modulefunc.docForm.whid = 0;
                    if (sbc.modulefunc.docForm.clientid === "") sbc.modulefunc.docForm.clientid = 0;
                    tx.executeSql("insert into head(trno, docno, dateid, doc, devid, station, whid, clientid) values(" + trno + ", ?, ?, ?, ?, ?, " + sbc.modulefunc.docForm.whid + ", " + sbc.modulefunc.docForm.clientid + ")", [sbc.modulefunc.docForm.docno, sbc.modulefunc.docForm.dateid, sbc.modulefunc.docForm.doc, sbc.modulefunc.docForm.devid, sbc.modulefunc.docForm.station], function (tx, res) {
                      if (res.rowsAffected > 0) {
                        tx.executeSql("update config set rrseq=" + thiss.docSeq, [], function (tx, res) {
                          cfunc.showMsgBox("Document saved", "positive");
                          sbc.modulefunc.docForm.status = "draft";
                          sbc.modulefunc.docForm.trno = trno;
                          sbc.isFormEdit = false;
                          thiss.dType = "";
                          thiss.showButtons(["create", "edit", "delete", "post", "back"]);
                          $q.loading.hide();
                          resolve();
                        });
                      } else {
                        console.log("document not saved");
                      }
                    }, function (tx, err) {
                      cfunc.showMsgBox(err.message + " saveDoc Err#1", "negative", "warning");
                      $q.loading.hide();
                    });
                  });
                });
              });
              break;
            case "edit":
              sbc.db.transaction(function (tx) {
                tx.executeSql("update head set dateid=?, whid=" + sbc.modulefunc.docForm.whid + ", clientid=" + sbc.modulefunc.docForm.clientid + " where trno=" + sbc.modulefunc.docForm.trno, [sbc.modulefunc.docForm.dateid], function (tx, res) {
                  if (res.rowsAffected > 0) {
                    cfunc.showMsgBox("Document updated", "positive");
                    sbc.isFormEdit = false;
                    thiss.dType = "";
                    thiss.showButtons(["create", "edit", "delete", "post", "back"]);
                    $q.loading.hide();
                    resolve();
                  } else {
                    cfunc.showMsgBox("Nothing to update", "negative", "warning");
                    $q.loading.hide();
                  }
                }, function (tx, err) {
                  cfunc.showMsgBox(err.message + " saveDoc Err#2", "negative", "warning");
                  $q.loading.hide();
                });
              });
              break;
          }
        });
      },
      postDoc: function () {
        const thiss = this;
        return new Promise((resolve) => {
          $q.dialog({
            message: "Do you want to post this Document?",
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            cfunc.showLoading();
            sbc.db.transaction(function (tx) {
              tx.executeSql("insert into hhead select * from head where trno=" + sbc.modulefunc.docForm.trno, [], function (tx, res) {
                if (res.rowsAffected > 0) {
                  if (sbc.modulefunc.docForm.icount > 0) {
                    tx.executeSql("insert into hstock select * from stock where trno=" + sbc.modulefunc.docForm.trno, [], function (tx, res) {
                      if (res.rowsAffected > 0) {
                        tx.executeSql("delete from head where trno=" + sbc.modulefunc.docForm.trno);
                        tx.executeSql("delete from stock where trno=" + sbc.modulefunc.docForm.trno);
                        cfunc.showMsgBox("Document Posted", "positive");
                        sbc.modulefunc.docForm.docstatus = "posted";
                        thiss.showButtons(["create","unpost","back"]);
                        $q.loading.hide();
                        resolve();
                      } else {
                        cfunc.showMsgBox("An error occurred; please try again. error: #1", "negative", "warning");
                        tx.executeSql("delete from hhead where trno=" + sbc.modulefunc.docForm.trno)
                        $q.loading.hide();
                      }
                    }, function (tx, err) {
                      tx.executeSql("delete from hhead where trno=" + sbc.modulefunc.docForm.trno);
                      cfunc.showMsgBox(err.message + " postDoc Err#1", "negative", "warning");
                      $q.loading.hide();
                    });
                  } else {
                    tx.executeSql("delete from head where trno=" + sbc.modulefunc.docForm.trno);
                    cfunc.showMsgBox("Document Posted", "positive");
                    thiss.showButtons(["create","unpost","back"]);
                    sbc.modulefunc.docForm.docstatus = "posted";
                    $q.loading.hide();
                    resolve();
                  }
                } else {
                  cfunc.showMsgBox("An error occurred; please try again. error: #3", "negative", "warning");
                  $q.loading.hide();
                }
              }, function (tx, err) {
                cfunc.showMsgBox(err.message + " postDoc Err#2", "negative", "warning");
                $q.loading.hide();
              });
            });
          });
        });
      },
      unPostDoc: function () {
        const thiss = this;
        return new Promise((resolve) => {
          $q.dialog({
            message: "Do you want to unpost this Document?",
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            cfunc.showLoading();
            sbc.db.transaction(function (tx) {
              tx.executeSql("select isok from hhead where trno=" + sbc.modulefunc.docForm.trno, [], function (tx, res) {
                if (res.rows.item(0).isok === 1 || res.rows.item(0).isok === "1") {
                  cfunc.showMsgBox("Document already uploaded, cannot UnPost", "negative", "warning");
                  $q.loading.hide();
                } else {
                  tx.executeSql("insert into head select * from hhead where trno=" + sbc.modulefunc.docForm.trno, [], function (tx, res) {
                    if (res.rowsAffected > 0) {
                      if (sbc.modulefunc.docForm.icount > 0) {
                        tx.executeSql("insert into stock select * from hstock where trno=" + sbc.modulefunc.docForm.trno, [], function (tx, res) {
                          if (res.rowsAffected > 0) {
                            tx.executeSql("delete from hhead where trno=" + sbc.modulefunc.docForm.trno);
                            tx.executeSql("delete from hstock where trno=" + sbc.modulefunc.docForm.trno);
                            cfunc.showMsgBox("Document UnPosted", "positive");
                            thiss.showButtons(["create","edit","delete","post","back"]);
                            sbc.modulefunc.docForm.docstatus = "draft";
                            $q.loading.hide();
                            resolve();
                          } else {
                            cfunc.showMsgBox("An error occurred; please try again. eror: #1", "negative", "warning");
                            $q.loading.hide();
                          }
                        }, function (tx, err) {
                          tx.executeSql("delete from head where trno=" + sbc.modulefunc.docForm.trno);
                          cfunc.showMsgBox(err.message + " unPost Err#1", "negative", "warning");
                          $q.loading.hide();
                        });
                      } else {
                        tx.executeSql("delete from hhead where trno=" + sbc.modulefunc.docForm.trno);
                        cfunc.showMsgBox("Document UnPosted", "positive");
                        thiss.showButtons(["create","edit","delete","post","back"]);
                        sbc.modulefunc.docForm.docstatus = "draft";
                        $q.loading.hide();
                        resolve();
                      }
                    } else {
                      cfunc.showMsgBox("An error occurred; please try again. error: #3", "negative", "warning");
                      $q.loading.hide();
                    }
                  }, function (tx, err) {
                    cfunc.showMsgBox(err.message + " unPost Err#2", "negative", "warning");
                    $q.loading.hide()
                  });
                }
              });
            });
          });
        });
      },
      cancelDoc: function () {
        const thiss = this;
        return new Promise((resolve) => {
          if (thiss.dType === "new") {
            sbc.isFormEdit = false;
            thiss.dType = "";
            thiss.loadDoc("last");
            resolve();
          } else {
            sbc.isFormEdit = false;
            thiss.dType = "";
            thiss.loadDoc("", sbc.modulefunc.docForm.trno);
            resolve();
          }
        });
      },
      clickDoc: function (doc) {
        const thiss = this;
        return new Promise((resolve) => {
          sbc.modulefunc.docForm = { trno: doc.trno, docno: doc.docno, dateid: doc.dateid, doc: doc.doc, devid: doc.devid, station: doc.station, whid: doc.whid, whname: doc.whname, clientid: doc.clientid, clientname: doc.clientname, docstatus: doc.docstatus, icount: doc.icount };
          sbc.showHeadForm = true;
          sbc.showDocs = false;
          thiss.dType = "";
          sbc.isFormEdit = false;
          thiss.loadStock(doc.trno);
          let showbuttons = ["create","back"];
          if (doc.docstatus === "posted") {
            showbuttons.push("unpost");
          } else {
            showbuttons.push("post","edit","delete");
          }
          thiss.showButtons(showbuttons);
          resolve();
        });
      },
      loadDoc: function (type, trno = 0) {
        const thiss = this;
        return new Promise((resolve) => {
          cfunc.showLoading();
          let filter = "";
          if (type === "") filter = " and head.trno=" + trno;
          sbc.db.transaction(function (tx) {
            let qry = "select * from (select 0 as docstatus, head.*, client.clientname, wh.clientname as whname, client.addr as supaddr, wh.addr as whaddr, (select sum(line) as icount from stock where trno=head.trno) as icount from head left join client on client.clientid=head.clientid left join client as wh on wh.clientid=head.whid where head.doc=? " + filter + " union all select 1 as docstatus, head.*, client.clientname, wh.clientname as whname, client.addr as supaddr, wh.addr as whaddr, (select sum(line) as icount from hstock as stock where trno=head.trno) as icount from hhead as head left join client on client.clientid=head.clientid left join client as wh on wh.clientid=head.whid where head.doc=? " + filter + ") as t order by trno desc limit 1";
            tx.executeSql(qry, [sbc.doc, sbc.doc], function (tx, res) {
              if (res.rows.length > 0) {
                sbc.modulefunc.docForm = res.rows.item(0);
                if (res.rows.item(0).docstatus === 1) {
                  sbc.modulefunc.docForm.docstatus = "posted";
                  thiss.showButtons(["create","unpost","back"]);
                } else {
                  sbc.modulefunc.docForm.docstatus = "draft";
                  thiss.showButtons(["create","edit","delete","post","back"]);
                }
                $q.loading.hide();
                thiss.loadStock(res.rows.item(0).trno);
                resolve();
              } else {
                cfunc.showMsgBox("No Document found.", "negative", "warning");
                $q.loading.hide();
                thiss.loadDocList();
              }
            }, function (tx, err) {
              cfunc.showMsgBox(err.message + " loadDoc Err#1", "negative", "warning");
              $q.loading.hide();
            });
          });
        });
      },
      loadStock: function (trno) {
        const thiss = this;
        return new Promise((resolve) => {
          sbc.modulefunc.stocks = [];
          if (trno !== 0) {
            cfunc.showLoading();
            sbc.db.transaction(function (tx) {
              tx.executeSql("select stock.*, item.itemname, item.barcode, item.barcode2, item.barcode3, item.barcode4, item.barcode5, item.barcode6, item.uom as uom1, item.uom2, item.uom3, item.uom4, item.uom5, item.uom6 from stock left join item on item.itemid=stock.itemid where stock.trno=" + trno + " union all select stock.*, item.itemname, item.barcode, item.barcode2, item.barcode3, item.barcode4, item.barcode5, item.barcode6, item.uom as uom1, item.uom2, item.uom3, item.uom4, item.uom5, item.uom6 from hstock as stock left join item on item.itemid=stock.itemid where stock.trno=" + trno, [], function (tx, res) {
                sbc.modulefunc.stocks = [];
                sbc.modulefunc.stockCount = res.rows.length;
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    sbc.modulefunc.stocks.push(res.rows.item(x));
                    if (res.rows.item(x).uom === res.rows.item(x).uom1) {
                      sbc.modulefunc.stocks[x].barcode = res.rows.item(x).barcode;
                    } else if (res.rows.item(x).uom === res.rows.item(x).uom2) {
                      sbc.modulefunc.stocks[x].barcode = res.rows.item(x).barcode2;
                    } else if (res.rows.item(x).uom === res.rows.item(x).uom3) {
                      sbc.modulefunc.stocks[x].barcode = res.rows.item(x).barcode3;
                    } else if (res.rows.item(x).uom === res.rows.item(x).uom4) {
                      sbc.modulefunc.stocks[x].barcode = res.rows.item(x).barcode4;
                    } else if (res.rows.item(x).uom === res.rows.item(x).uom5) {
                      sbc.modulefunc.stocks[x].barcode = res.rows.item(x).barcode5;
                    } else if (res.rows.item(x).uom === res.rows.item(x).uom6) {
                      sbc.modulefunc.stocks[x].barcode = res.rows.item(x).barcode6;
                    }
                  }
                }
                $q.loading.hide();
                resolve();
              }, function (tx, err) {
                cfunc.showMsgBox(err.message + " loadStock Err#1", "negative", "warning");
                $q.loading.hide();
              });
            });
          } else {
            $q.loading.hide();
          }
        });
      },
      loadDocList: function () {
        console.log("loadDocList called");
        return new Promise((resolve) => {
          sbc.showDocs = true;
          let qry = "";
          let data = [];
          switch (sbc.modulefunc.docListForm.docstatus) {
            case "draft":
              qry = "select 0 as docstatus, head.*, client.clientname, wh.clientname as whname, (select sum(line) as icount from stock where trno=head.trno) as icount from head left join client on client.clientid=head.clientid left join client as wh on wh.clientid=head.whid where head.doc=? and head.dateid between ? and ? order by docno desc";
              data = [sbc.doc, sbc.modulefunc.docListForm.startdate, sbc.modulefunc.docListForm.enddate];
              break;
            case "posted":
              qry = "select 1 as docstatus, head.*, client.clientname, wh.clientname as whname, (select sum(line) as icount from hstock where trno=head.trno) as icount from hhead as head left join client on client.clientid=head.clientid left join client as wh on wh.clientid=head.whid where head.doc=? and head.dateid between ? and ? order by docno desc";
              data = [sbc.doc, sbc.modulefunc.docListForm.startdate, sbc.modulefunc.docListForm.enddate];
              break;
          }
          sbc.db.transaction(function (tx) {
            tx.executeSql(qry, data, function (tx, res) {
              sbc.modulefunc.docList = [];
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.modulefunc.docList.push(res.rows.item(x));
                  if (res.rows.item(x).docstatus === 1) {
                    sbc.modulefunc.docList[x].docstatus = "posted";
                  } else {
                    sbc.modulefunc.docList[x].docstatus = "draft";
                  }
                }
              }
              resolve();
            }, function (tx, err) {
              cfunc.showMsgBox(err.message + " loadDocList Err#1", "negative", "warning");
            });
          }, function (err) {
            console.log("loadDocList error:", err.message);
          });
        });
      },
      scanItem: function () {
        const thiss = this;
        let item = [];
        if (sbc.isFormEdit) {
          cfunc.showMsgBox("Please save head first", "negative", "warning");
          return;
        } else {
          sbc.showHeadForm = false;
        }
        if (sbc.txtScan === "") {
          cfunc.showMsgBox("Please enter/scan barcode.", "negative", "warning");
          return;
        }
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select itemid, itemname, barcode, bcode, barcode2, barcode3, barcode4, barcode5, barcode6, factor2, factor3, factor4, factor5, factor6, uom, uom2, uom3, uom4, uom5, uom6 from item where (barcode=? or bcode=? or barcode2=? or barcode3=? or barcode4=? or barcode5=? or barcode6=?)", [sbc.txtScan, sbc.txtScan, sbc.txtScan, sbc.txtScan, sbc.txtScan, sbc.txtScan, sbc.txtScan], function (tx, res) {
            if (res.rows.length > 0) {
              item = { trno: sbc.modulefunc.docForm.trno, line: "", itemid: res.rows.item(0).itemid, barcode: sbc.txtScan, rrqty: 1, qty: 1, uom: "", itemname: res.rows.item(0).itemname };
              if (sbc.txtScan === res.rows.item(0).barcode || sbc.txtScan === res.rows.item(0).bcode) {
                item.uom = res.rows.item(0).uom;
                item.qty = 1;
              } else if (sbc.txtScan === res.rows.item(0).barcode2) {
                item.uom = res.rows.item(0).uom2;
                item.qty = res.rows.item(0).factor2;
              } else if (sbc.txtScan === res.rows.item(0).barcode3) {
                item.uom = res.rows.item(0).uom3;
                item.qty = res.rows.item(0).factor3;
              } else if (sbc.txtScan === res.rows.item(0).barcode4) {
                item.uom = res.rows.item(0).uom4;
                item.qty = res.rows.item(0).factor4;
              } else if (sbc.txtScan === res.rows.item(0).barcode5) {
                item.uom = res.rows.item(0).uom5;
                item.qty = res.rows.item(0).factor5;
              } else if (sbc.txtScan === res.rows.item(0).barcode6) {
                item.uom = res.rows.item(0).uom6;
                item.qty = res.rows.item(0).factor6;
              }
              tx.executeSql("select line, qty, rrqty from stock where itemid=" + item.itemid + " and uom=? and trno=" + sbc.modulefunc.docForm.trno, [item.uom], function (tx, sres) {
                if (sres.rows.length > 0) {
                  let stock = sres.rows.item(0);
                  stock.rrqty = parseInt(stock.rrqty) + 1;
                  stock.qty = parseInt(stock.qty) + parseInt(item.qty);
                  tx.executeSql("update stock set qty=" + stock.qty + ", rrqty=" + stock.rrqty + " where trno=" + item.trno + " and line=" + stock.line, [], function (tx, sres2) {
                    if (sres2.rowsAffected > 0) {
                      sbc.modulefunc.loadStock(item.trno);
                      cfunc.showMsgBox("Item updated (Itemname: " + item.itemname + ", Barcode: " + sbc.txtScan + ", Qty: " + stock.rrqty + ")", "positive");
                      sbc.txtScan = "";
                      sbc.modulefunc.inputLookupForm.barcode = "";
                      $q.loading.hide();
                    } else {
                      cfunc.showMsgBox("An error occurred; please try again. error#1", "negative", "warning");
                      $q.loading.hide();
                    }
                  }, function (tx, err) {
                    cfunc.showMsgBox(err.message + " scanItem Err#1", "negative", "warning");
                    $q.loading.hide();
                  });
                } else {
                  cfunc.getLastStockLine(sbc.modulefunc.docForm.trno).then(line => {
                    sbc.db.transaction(function (tx) {
                      tx.executeSql("insert into stock(trno, line, itemid, qty, rrqty, uom, devid, station) values(" + item.trno + ", " + line + ", " + item.itemid + ", " + item.qty + ", 1, ?, ?, ?)", [item.uom, sbc.modulefunc.docForm.devid, sbc.modulefunc.docForm.station], function (tx, res) {
                        if (res.rowsAffected > 0) {
                          sbc.modulefunc.loadStock(item.trno);
                          cfunc.showMsgBox("Item added (Itemname: " + item.itemname + ", Barcode: " + sbc.txtScan + ")", "positive");
                          sbc.modulefunc.docForm.icount += 1;
                          sbc.txtScan = "";
                          sbc.modulefunc.inputLookupForm.barcode = "";
                          $q.loading.hide();
                        } else {
                          cfunc.showMsgBox("An error occurred; please try again. error#3", "negative", "warning");
                          $q.loading.hide();
                        }
                      }, function (tx, err) {
                        cfunc.showMsgBox(err.message + " scanItem Err#2", "negative", "warning");
                        $q.loading.hide();
                      });
                    });
                  }).catch(err => {
                    cfunc.showMsgBox(err.message + " scanItem Err#3", "negative", "warning");
                    $q.loading.hide();
                  });
                }
              }, function (tx, err) {
                cfunc.showMsgBox(err.message + " scanItem Err#4", "negative", "warning");
                $q.loading.hide();
              });
            } else {
              cfunc.showMsgBox("Item not found", "negative", "warning");
              $q.loading.hide();
            }
          }, function (tx, err) {
            cfunc.showMsgBox(err.message + " scanItem Err#5", "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      editStock: function (stock) {
        const thiss = this;
        if (sbc.isFormEdit) {
          cfunc.showMsgBox("Please save head first.", "negative", "warning");
          return;
        }
        sbc.selItem = { trno: stock.trno, line: stock.line, itemid: stock.itemid, barcode: stock.barcode, itemname: stock.itemname, rrqty: stock.rrqty, qty: stock.qty, uom: stock.uom };
        sbc.showEditItem = true;
        thiss.iType = "edit";
      },
      deleteStock: function (stock) {
        const thiss = this;
        if (sbc.isFormEdit) {
          cfunc.showMsgBox("Please save head first.", "negative", "warning");
          return;
        }
        const index = sbc.modulefunc.stocks.indexOf(stock);
        $q.dialog({
          message: "Do you want to delete this record?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          cfunc.showLoading();
          sbc.db.transaction(function (tx) {
            tx.executeSql("delete from stock where trno=" + stock.trno + " and line=" + stock.line, [], function (tx, res) {
              if (res.rowsAffected > 0) {
                cfunc.showMsgBox("Stock deleted", "positive");
                $q.loading.hide();
                sbc.modulefunc.stocks.splice(index, 1);
                sbc.modulefunc.docForm.icount -= 1;
              } else {
                cfunc.showMsgBox("Nothing to delete, Please try again.", "negative", "warning");
                $q.loading.hide();
              }
            }, function (tx, err) {
              cfunc.showMsgBox(err.message + " deleteStock Err#1", "negative", "warning");
              $q.loading.hide();
            });
          });
        });
      },
      uploadDocs: function () {
        const thiss = this;
        $q.dialog({
          message: "Do you watn to upload Documents?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          cfunc.showLoading();
          sbc.db.transaction(function (tx) {
            tx.executeSql("select count(*) as counts from hhead where doc=? and (isok is null or isok=0)", [sbc.doc], function (tx, res) {
              if (res.rows.item(0).counts > 0) {
                cfunc.showLoading("Uplaoding Documents, Please wait.");
                loadDocs().then(docs => {
                  if (docs.length > 0) {
                    uploadDoc(docs);
                  } else {
                    cfunc.showMsgBox("No Documents to upload.", "negative", "warning");
                    $q.loading.hide();
                  }
                }).catch(err => {
                  cfunc.showMsgBox(err.message + " uploadDocs Err#1", "negative", "warning");
                  $q.loading.hide();
                });
              } else {
                cfunc.showMsgBox("No Documents to upload", "negative", "warning");
                $q.loading.hide();
              }
            }, function (tx, err) {
              cfunc.showMsgBox(err.message + " upload Docs Err#2", "negative", "warning");
              $q.loading.hide();
            });
          });
        });

        function loadDocs () {
          let orders = [];
          return new Promise((resolve, reject) => {
            sbc.db.transaction(function (tx) {
              tx.executeSql("select * from hhead where doc=? and (isok is null or isok=0)", [sbc.doc], function (tx, res) {
                orders = [];
                for (var x =0; x < res.rows.length; x++) {
                  orders.push(res.rows.item(x));
                  if (parseInt(x) + 1 === res.rows.length) resolve(orders)
                }
              }, function (tx, err) {
                reject(err);
              });
            });
          });
        }

        function uploadDoc (documents, index = 0) {
          if (parseInt(index) === documents.length) {
            cfunc.showMsgBox("Documents finished uploading", "positive");
            sbc.globalFunc.loadDocList();
            $q.loading.hide();
          } else {
            cfunc.showLoading("Uploading Document " + documents[index].docno);
            loadDocStocks(documents[index].trno).then(stock => {
              documents[index].stocks = stock;
              cfunc.getTableData("config", ["serveraddr", "idlock"], true).then(configdata => {
                if (configdata.serveraddr === "" || configdata.serveraddr === null || typeof(configdata.serveraddr) === "undefined") {
                  cfunc.showMsgBox("Server Address not set", "negative", "warning");
                  $q.loading.hide();
                  return;
                }
                api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("uploadDocStocks"), doc: documents[index] }, { headers: sbc.reqheader }).then(res => {
                  if (res.data.status) {
                    sbc.db.transaction(function (tx) {
                      tx.executeSql("update hhead set uploaddate=?, isok=1 where trno=" + documents[index].trno, [res.data.date], function (tx, res) {
                        uploadDoc(documents, parseInt(index) + 1);
                      });
                    });
                  } else {
                    cfunc.showMsgBox(res.data.msg, "negative", "warning");
                    uploadDoc(documents, parseInt(index) + 1);
                  }
                })
              })
            });
          }
        }

        function loadDocStocks (trno) {
          let stocks = [];
          return new Promise((resolve) => {
            sbc.db.transaction(function (tx) {
              tx.executeSql("select * from hstock where trno=" + trno, [], function (tx, res) {
                stocks = [];
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    stocks.push(res.rows.item(x));
                    if (parseInt(x) + 1 === res.rows.length) {
                      resolve(stocks);
                    }
                  }
                } else {
                  resolve([]);
                }
              });
            });
          });
        }
      },
      getLookupForm: function (data, form, type) {
        let data2 = [];
        switch (type) {
          case "inputFields":
            data2 = [];
            if (data.length > 0) {
              for (var ilf in data) {
                if (data[ilf].form === form) {
                  data2.push(data[ilf]);
                }
              }
            }
            break;
          case "inputPlot":
            data2 = [];
            if (data.length > 0) {
              for (var ilfp in data) {
                if (data[ilfp].form === form) {
                  data2.push(data[ilfp].fields);
                }
              }
            }
            break;
          case "buttons":
            data2 = [];
            if (data.length > 0) {
              for (var ilb in data) {
                if (data[ilb].form === form) {
                  data2.push(data[ilb]);
                }
              }
            }
            break;
        }
        return data2;
      },
      addItemOk: function () {
        sbc.selItem = [];
        sbc.showEditItem = false;
        sbc.globalFunc.refreshCart();
      },
      refreshCart: function () {
        const thiss = this;
        sbc.db.transaction(function (tx) {
          switch (sbc.globalFunc.company) {
            case "sbc":
              tx.executeSql("select * from cart", [], function (tx, res) {
                sbc.globalFunc.cartICount = res.rows.length;
                sbc.modulefunc.docForm.itemcount = res.rows.length;
                let total = 0;
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    console.log("newCarts: ", res.rows.item(x));
                    total = sbc.numeral(sbc.numeral(total).value() + sbc.numeral(res.rows.item(x).ext).value()).format("0,0.00");
                    if (parseInt(x) + 1 === res.rows.length) sbc.modulefunc.docForm.total = total;
                  }
                } else {
                  sbc.modulefunc.docForm.total = "0.00";
                }
              });
              // tx.executeSql("select count(*) as count from cart", [], function (tx, res) {
              //   sbc.globalFunc.cartICount = res.rows.item(0).count;
              //   sbc.modulefunc.docForm.itemcount = res.rows.item(0).count;
              // });
              break;
            default:
              tx.executeSql("select count(*) as count from item where (qty <> 0 and qty <> ?)", ["0"], function (tx, res) {
                sbc.globalFunc.cartICount = res.rows.item(0).count;
                sbc.modulefunc.docForm.itemcount = res.rows.item(0).count;
              });
              break;
          }
        });
      },
      syncOrders: function () {
        console.log("syncOrders");
        const thiss = this;
        $q.dialog({
          message: "Do you want to sync orders to server?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          thiss.syncOrders2();
        });
      },
      syncOrders2: function () {
        cfunc.showLoading();
        sbc.globalFunc.loadTransactions().then(res => {
          sbc.globalFunc.loadOrderStocks().then(res => {
            let items = [];
            for (var o in sbc.modulefunc.orders) {
              items = [];
              for (var i in sbc.modulefunc.orderStocks) {
                if (sbc.modulefunc.orderStocks[i].orderno === sbc.modulefunc.orders[o].orderno) {
                  items.push(sbc.modulefunc.orderStocks[i]);
                }
              }
              sbc.modulefunc.orders[o].items = items;
            }
            sbc.globalFunc.syncTrans();
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        }).catch(err => {
          cfunc.showMsgBox(err.message, "negative", "warning");
          $q.loading.hide();
        });
      },
      loadTransactions: function () {
        let centercode = "";
        let warehouse = "";
        let warehousename = "";
        let addr = "";
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        if (sbc.globalFunc.company === "sbc") {
          warehouse = storage.user.wh;
        } else {
          if (typeof(storage.center) !== "undefined") {
            centercode = storage.center.centercode;
            warehouse = storage.center.warehouse;
            warehousename = storage.center.warehousename;
            addr = storage.center.addr;
          }
        }
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select h.*, c.clientname as clientname2, c.addr as clientaddr from transhead as h left join customers as c on c.client=h.client where h.ishold is null and h.userid=?", [storage.user.userid], function (tx, res) {
              if (res.rows.length > 0) {
                sbc.modulefunc.orders = [];
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.modulefunc.orders.push(res.rows.item(x));
                  sbc.modulefunc.orders[x].items = [];
                  sbc.modulefunc.orders[x].username = storage.user.username;
                  sbc.modulefunc.orders[x].addr = res.rows.item(x).clientaddr;
                  sbc.modulefunc.orders[x].center = centercode;
                  sbc.modulefunc.orders[x].warehouse = warehouse;
                  sbc.modulefunc.orders[x].warehousename = warehousename;
                  sbc.modulefunc.orders[x].address = addr;
                  sbc.modulefunc.orders[x].total = sbc.numeral(sbc.modulefunc.orders[x].total).value();
                  sbc.modulefunc.orders[x].tendered = sbc.numeral(sbc.modulefunc.orders[x].tendered).value();
                  sbc.modulefunc.orders[x].change = sbc.numeral(sbc.modulefunc.orders[x].change).value();
                  // if (sbc.modulefunc.orders[x].transtype === "CASH") {
                  //   sbc.modulefunc.orders[x].transtype = 0;
                  // } else if (sbc.modulefunc.orders[x].transtype === "AR") {
                  //   sbc.modulefunc.orders[x].transtype = 1;
                  // } else {
                  //   sbc.modulefunc.orders[x].transtype = "";
                  // }
                }
              }
              resolve("done");
            }, function (tx, err) {
              reject(err);
            });
          });
        });
      },
      loadOrderStocks: function () {
        const thiss = this;
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from transstock where userid=? order by line asc", [storage.user.userid], function (tx, res) {
              if (res.rows.length > 0) {
                sbc.modulefunc.orderStocks = [];
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.modulefunc.orderStocks.push(res.rows.item(x));
                }
              }
              resolve("done");
            }, function (tx, err) {
              reject(err);
            });
          });
        });
      },
      syncTrans: function (index = 0) {
        const thiss = this;
        if (index === 0) cfunc.showLoading();
        if (index <= sbc.modulefunc.orders.length - 1) {
          thiss.saveTransHead(sbc.modulefunc.orders[index]).then(res => {
            if (res.data.status) {
              thiss.saveTranHistory(sbc.modulefunc.orders[index], res.data);
              thiss.syncTrans(index + 1);
            } else {
              $q.loading.hide();
              cfunc.showMsgBox("Failed to sync order (" + sbc.modulefunc.orders[index].orderno + "), Please try again.", "negative", "warning");
              thiss.syncTrans(index + 1);
            }
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            thiss.syncTrans(index + 1);
          });
        } else {
          cfunc.showMsgBox("Sync Finished", "positive");
          sbc.globalFunc.downloadLastOrderno();
          sbc.modulefunc.loadTableData();
          $q.loading.hide();
        }
      },
      saveTranHistory: function (order, data) {
        console.log("saveTranshistory called");
        let datenow = cfunc.getDateTime("date");
        let centercode = "";
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        if (typeof(storage.center) !== "undefined") centercode = storage.center.centercode;
        let head = [];
        sbc.db.transaction(function (tx) {
          if (sbc.globalFunc.company === "sbc") {
            head = data.head;
          } else {
            head = data.head.data[0];
          }
          tx.executeSql("insert into transhistoryhead(trno, docno, userid, center, doc, dateid, itemcount, total, client, datesynced, rem, terms, shipto) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [head.trno, head.docno, order.userid, order.center, order.doc, order.dateid, data.itemcount, data.gtotal, order.client, datenow, order.rem, order.terms, order.shipto], function (tx, res) {
            console.log("transhistoryhead saved");
            tx.executeSql("delete from transhead where orderno=?", [order.orderno], function (tx, res) {
              console.log("transhead deleted");
              tx.executeSql("delete from transstock where orderno=?", [order.orderno], function (tx, res) {
                console.log("transstock deleted");
                if (data.stocks.length > 0) {
                  for (var i in data.stocks) {
                    if (data.stocks[i].status) {
                      tx.executeSql("insert into transhistorystock(line, userid, center, trno, barcode, itemname, amt, qty, total, origqty, uom, factor, rem, disc) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [data.stocks[i].data[0].line, storage.user.userid, centercode, head.trno, data.stocks[i].data[0].barcode, data.stocks[i].data[0].itemname, data.stocks[i].data[0].isamt, data.stocks[i].data[0].isqty, data.stocks[i].data[0].ext, data.stocks[i].data[0].original_qty, data.stocks[i].data[0].uom, data.stocks[i].data[0].uomfactor, data.stocks[i].data[0].rem, data.stocks[i].data[0].disc]);
                    }
                  }
                }
              }, function (tx, err) {
                console.log("delete transstock error: ", err.message);
              });
            }, function (tx, err) {
              console.log("delete transhead error: ", err.message);
            });
          }, function (tx, err) {
            console.log("error saving transhistoryhead: ", err.message);
          });
        }, function (err) {
          console.log("saveTranHistory error: ", err.message);
        });
      },
      saveTransHead: function (order) {
        return new Promise((resolve, reject) => {
          cfunc.getTableData("config", "serveraddr").then(serveraddr => {
            if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") reject("Server Address not set");
            api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("saveTransactions"), order: order, deviceid: window.device.uuid }, { headers: sbc.reqheader }).then(res => {
              resolve(res);
            }).catch(err => {
              reject(err);
            });
          })
        });
      },
      loadCart: function () {
        const thiss = this;
        console.log("loadCart called");
        thiss.customLookupGrid = true;
        sbc.cLookupTitle = "Cart";
        sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "cartHeadFields", "inputFields");
        sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "cartHeadFields", "inputPlot");
        sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, "cartTableCols", "inputFields");
        sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, "cartTableCols", "inputPlot");
        sbc.selclookuptablebuttons = sbc.globalFunc.getLookupForm(sbc.clookuptablebuttons, "cartTableButtons", "buttons");
        sbc.selclookupfooterfields = sbc.globalFunc.getLookupForm(sbc.clookupfooterfields, "cartFooterFields", "inputFields");
        sbc.selclookupfooterfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupfooterfieldsplot, "cartFooterFields", "inputPlot");
        sbc.selclookupbuttons = sbc.globalFunc.getLookupForm(sbc.clookupbuttons, "cartButtons", "buttons");
        if (sbc.selclookupheadfields.length > 0) {
          for (var i in sbc.selclookupheadfields) {
            if (sbc.selclookupheadfields[i].name === "paytype") sbc.selclookupheadfields[i].options = ["CASH", "AR"];
          }
        }
        sbc.showCustomLookup = true;
        sbc.modulefunc.cLookupForm = sbc.modulefunc.docForm;
        sbc.isFormEdit = true;
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          if (sbc.globalFunc.company === "sbc") {
            tx.executeSql("select cart.line, cart.itemid, cart.isamt, cart.amt, cart.isqty as qty, cart.iss, cart.ext, cart.disc, cart.uom, cart.factor, cart.rem, item.amt as iamt, item.itemname, item.barcode, item.istaxable from cart left join item on item.itemid=cart.itemid", [], function (tx, res) {
              sbc.modulefunc.lookupTableData = [];
              if (res.rows.length > 0) {
                let tot;
                let discount;
                sbc.modulefunc.docForm.total = 0;
                let row = [];
                for (var x = 0; x < res.rows.length; x++) {
                  row = res.rows.item(x);
                  row.ext = sbc.numeral(row.ext).format("0,0.00");
                  sbc.modulefunc.docForm.total = sbc.numeral(sbc.numeral(sbc.modulefunc.docForm.total).value() + sbc.numeral(row.ext).value()).format("0,0.00");
                  sbc.modulefunc.lookupTableData.push(row);
                  if (parseInt(x) + 1 === res.rows.length) sbc.modulefunc.updateItemBal();
                }
                $q.loading.hide();
              } else {
                cfunc.showMsgBox("Cart empty", "negative", "warning");
                $q.loading.hide();
              }
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          } else {
            tx.executeSql("select * from item where (qty <> 0 and qty <> ?) order by seq asc", ["0"], function (tx, res) {
              console.log("waw");
              sbc.modulefunc.lookupTableData = [];
              if (res.rows.length > 0) {
                let tot = "";
                let discount = "";
                let row = [];
                sbc.modulefunc.docForm.total = 0;
                for (var x = 0; x < res.rows.length; x++) {
                  row = res.rows.item(x);
                  console.log("item: ", row);
                  row.amt = sbc.numeral(row.amt).format("0,0.00");
                  row.newamt = sbc.numeral(row.newamt).format("0,0.00");
                  discount = sbc.globalFunc.computeDiscount(sbc.numeral(row.newamt).value(), sbc.numeral(row.newdisc).value());
                  tot = sbc.numeral(sbc.numeral(discount).value() * row.qty).format("0,0.00");
                  row.total = tot;
                  sbc.modulefunc.docForm.total = sbc.numeral(sbc.numeral(sbc.modulefunc.docForm.total).value() + sbc.numeral(tot).value()).format("0,0.00");
                  sbc.modulefunc.lookupTableData.push(row);
                  if (parseInt(x) + 1 === res.rows.length) sbc.modulefunc.updateItemBal();
                }
                $q.loading.hide();
              } else {
                cfunc.showMsgBox("Cart empty", "negative", "warning");
                $q.loading.hide();
              }
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          }
        }, function (err) {
          console.log(err.message);
        });
      },
      editCart: function (row, index) {
        sbc.isFormEdit = true;
        let discount;
        let isamt;
        let amt;
        let total;
        sbc.selItem = row;
        if (sbc.globalFunc.company === "sbc") {
          discount = sbc.globalFunc.computeDiscount(sbc.numeral(sbc.selItem.iamt).value(), sbc.selItem.disc);
          isamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() * sbc.numeral(sbc.selItem.factor).value()).format("0,0.00");
          amt = sbc.numeral(sbc.numeral(discount).value() * sbc.numeral(sbc.selItem.factor).value()).value();
          total = sbc.numeral(sbc.numeral(amt).value() * sbc.selItem.qty).format("0,0.00");
          console.log("editCart: ", sbc.selItem);
          sbc.selItem.isamt = isamt;
          sbc.selItem.amt = amt;
          sbc.selItem.total = total;
        } else {
          let discount = sbc.globalFunc.computeDiscount(sbc.numeral(sbc.selItem.newamt).value(), sbc.selItem.newdisc);
          let total = sbc.numeral(sbc.numeral(discount).value() * sbc.selItem.qty).format("0,0.00");
          sbc.selItem.total = total;
        }
        sbc.selItemIndex = index;
        sbc.showEditItem = true;
        if (sbc.globalFunc.company === "sbc") sbc.modulefunc.loadUOMs();
      },
      deleteCart: function (row, index) {
        const thiss = this;
        $q.dialog({
          message: "Do you want to remove this item?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          cfunc.showLoading();
          let index2 = sbc.globalFunc.getIndex(sbc.modulefunc.tableData, row, "itemid");
          sbc.db.transaction(function (tx) {
            if (sbc.globalFunc.company === "sbc") {
              tx.executeSql("delete from cart where itemid=" + row.itemid, [], function (tx, res) {
                // if (sbc.modulefunc.docForm.itemcount === 0) {
                //   sbc.modulefunc.docForm.total = "0.00";
                // } else {
                //   sbc.modulefunc.docForm.total = sbc.numeral(sbc.numeral(sbc.modulefunc.docForm.total).value() - sbc.numeral(row.ext).value()).format("0,0.00");
                // }
                sbc.modulefunc.tableData[index2].qty = 0;
                sbc.modulefunc.tableData[index2].hasqty = false;
                sbc.modulefunc.tableData[index2].bgColor = "";
                sbc.modulefunc.tableData[index2].disc = "";
                sbc.modulefunc.tableData[index2].rem = "";
                sbc.modulefunc.lookupTableData.splice(index, 1);
                sbc.globalFunc.refreshCart();
                cfunc.showMsgBox("Item removed", "positive");
                $q.loading.hide();
              }, function (tx, err) {
                console.log("deleteCart error: ", err.message);
                cfunc.showMsgBox(err.message, "negative", "warning");
                $q.loading.hide();
              });
            } else {
              tx.executeSql("update item set newamt=amt, newuom=uom, newfactor=factor, rem=?, newdisc=disc, seq=0, qty=0 where itemid=" + row.itemid, [""], function (tx, res) {
                sbc.modulefunc.docForm.total = sbc.numeral(sbc.numeral(sbc.modulefunc.docForm.total).value() - (sbc.numeral(row.newamt).value() * sbc.numeral(row.qty).value())).format("0,0.00");
                sbc.modulefunc.tableData[sbc.selItemIndex].newamt = sbc.modulefunc.tableData[sbc.selItemIndex].amt;
                sbc.modulefunc.tableData[sbc.selItemIndex].newuom = sbc.modulefunc.tableData[sbc.selItemIndex].uom;
                sbc.modulefunc.tableData[sbc.selItemIndex].newfactor = sbc.modulefunc.tableData[sbc.selItemIndex].factor;
                sbc.modulefunc.tableData[sbc.selItemIndex].newdisc = sbc.modulefunc.tableData[sbc.selItemIndex].disc;
                sbc.modulefunc.tableData[sbc.selItemIndex].qty = 0;
                sbc.modulefunc.tableData[sbc.selItemIndex].hasqty = false;
                sbc.modulefunc.lookupTableData.splice(index, 1);
                thiss.refreshCart();
                cfunc.showMsgBox("Item removed", "positive");
                $q.loading.hide();
              }, function (tx, err) {
                console.log("deleteCart error: ", err);
                cfunc.showMsgBox(err.message, "negative", "warning");
                $q.loading.hide();
              });
            }
          }, function (err) {
            console.log("deleteCart error1: ", err.message);
          });
        });
      },
      checkout: function () {
        const selCustomer = $q.localStorage.getItem("selCustomer");
        console.log("selCustomer: ", selCustomer);
        if (selCustomer === [] || typeof(selCustomer) === "undefined" || selCustomer === null || selCustomer === "") {
          cfunc.showMsgBox("No customer selected, Unable to continue checkout", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.docForm.itemcount === 0) {
          cfunc.showMsgBox("Cart empty", "negative", "warning");
          return;
        }
        $q.dialog({
          message: "Do you want to continue checkout?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          switch (sbc.globalFunc.company) {
            case "sbc":
              if (sbc.modulefunc.docForm.paytype === "CASH") {
                sbc.showCheckoutForm = true;
                sbc.modulefunc.checkoutForm = { total: "<b>" + sbc.numeral(sbc.modulefunc.docForm.total).format("0,0.00") + "</b>", payment: "", payment2: "", change: "" };
              } else {
                sbc.globalFunc.continueCheckout();
              }
              break;
            default:
              sbc.globalFunc.continueCheckout();
              break;
          }
        });
      },
      continueCheckout: function () {
        let datenow = cfunc.getDateTime("datetime");
        let centercode = "";
        const selCustomer = $q.localStorage.getItem("selCustomer");
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        if (typeof(storage.center) !== "undefined") centercode = storage.center.centercode;
        const shipto = $q.localStorage.getItem("orderShipto");
        cfunc.showLoading();
        cfunc.getTableDataCount("customers", [{ field: "clientid", value: selCustomer.clientid }]).then(ccount => {
          if (ccount > 0) {
            sbc.globalFunc.getLastOrderno().then(res => {
              let cust = selCustomer.client;
              let docno = res;
              let seq;
              let total = sbc.numeral(sbc.modulefunc.docForm.total).value();
              let datas = [docno, storage.user.userid, centercode, "SJ", datenow, sbc.modulefunc.docForm.itemcount, total, total, cust];
              sbc.db.transaction(function (tx) {
                let sql = "insert into transhead(orderno, userid, doc, dateid, itemcount, total, client, rem, terms, shipto) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                let data = [docno, storage.user.userid, "SJ", datenow, sbc.modulefunc.docForm.itemcount, total, cust, sbc.modulefunc.docForm.doctype, sbc.modulefunc.docForm.terms, shipto];
                if (sbc.globalFunc.company === "sbc") {
                  sql = "insert into transhead(orderno, userid, doc, dateid, itemcount, total, client, rem, terms, shipto, transtype, tendered, change) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                  data = [docno, storage.user.userid, "SJ", datenow, sbc.modulefunc.docForm.itemcount, total, cust, sbc.modulefunc.docForm.rem, sbc.modulefunc.docForm.terms, shipto, sbc.modulefunc.docForm.paytype, sbc.numeral(sbc.modulefunc.checkoutForm.payment).value(), sbc.numeral(sbc.modulefunc.checkoutForm.change).value()];
                }
                tx.executeSql(sql, data, function (tx, ress) {
                  sbc.globalFunc.getStockItems().then(res2 => {
                    if (res2.length > 0) {
                      sbc.globalFunc.saveTransStock(res2, docno).then(res => {
                        let order = {
                          date: datenow,
                          orderno: docno,
                          customer: selCustomer.clientname,
                          address: selCustomer.addr,
                          agent: storage.user.name,
                          area: selCustomer.area,
                          rem: sbc.modulefunc.docForm.rem,
                          province: selCustomer.province,
                          items: res2,
                          total: total,
                          paymenttype: sbc.modulefunc.docForm.paytype,
                          payment: sbc.numeral(sbc.modulefunc.checkoutForm.payment).format("0,0.00"),
                          change: sbc.numeral(sbc.modulefunc.checkoutForm.change).format("0,0.00"),
                          itemcount: sbc.modulefunc.docForm.itemcount
                        };
                        sbc.globalFunc.updateLastOrderno(order.orderno);
                        console.log("order details: ", order);
                        if (sbc.globalFunc.company === "sbc") {
                          if (sbc.settings.printreceipt) sbc.globalFunc.printReceipt(order);
                        }
                        cfunc.showMsgBox("Order saved", "positive");
                        $q.localStorage.remove("selCustomer");
                        sbc.modulefunc.docForm.clientid = "";
                        sbc.modulefunc.docForm.clientname = "";
                        sbc.modulefunc.docForm.addr = "";
                        sbc.modulefunc.docForm.client = "";
                        sbc.modulefunc.docForm.tel = "";
                        sbc.modulefunc.docForm.itemcount = 0;
                        sbc.modulefunc.docForm.total = "";
                        sbc.modulefunc.docForm.brgy = "";
                        sbc.modulefunc.docForm.area = "";
                        sbc.modulefunc.docForm.province = "";
                        sbc.modulefunc.docForm.rem = "";
                        sbc.globalFunc.refreshCart();
                        sbc.modulefunc.loadTableData();
                        sbc.showCheckoutForm = false;
                        sbc.showCustomLookup = false;
                        $q.loading.hide();
                      });
                    } else {
                      sbc.showMsgBox("Cart empty", "negative", "warning");
                      $q.loading.hide();
                    }
                  }).catch(err => {
                    tx.executeSql("delete from transhead where docno=?", [docno]);
                    cfunc.showMsgBox(err, "negative", "warning");
                    $q.loading.hide();
                  });
                }, function (tx, err) {
                  cfunc.showMsgBox(err.message, "negative", "warning");
                  $q.loading.hide();
                });
              });
            }).catch(err => {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          } else {
            cfunc.showMsgBox("Customer not found.", "negative", "warning");
            $q.loading.hide();
          }
        });
      },
      updateLastOrderno: function (orderno) {
        sbc.db.transaction(function (tx) {
          tx.executeSql("update config set lastorderno=?", [orderno])
        });
      },
      printReceipt: function (order) {
        console.log("printReceipt called");
        const thiss = this;
        let str = [];
        let printerLen = 32;
        str.push(thiss.mrow([order.agent, "0", "C"]));
        str.push(thiss.mrow(["-".repeat(printerLen), "1"]));
        str.push(thiss.mrow(["ORDER NO.: " + order.orderno, "1"]));
        str.push(thiss.mrow(["DATE: " + order.date, "1"]));
        str.push(thiss.mrow(["-".repeat(printerLen), "1"]));
        str.push(thiss.mrow(["CUSTOMER INFORMATION", "1", "C"]));
        str.push(thiss.mrow(["NAME: ", "1"], [order.customer, "1", "R"]));
        str.push(thiss.mrow(["ADDRESS: ", "1"], [order.address, "1", "R"]));
        str.push(thiss.mrow(["-".repeat(printerLen), "1"]));
        str.push(thiss.mrow(["NOTE: " + order.rem, "1"]));
        str.push(thiss.mrow(["-".repeat(printerLen), "1"]));
        for (var i in order.items) {
          str.push(thiss.mrow([order.items[i].itemname]));
          let col1 = sbc.numeral(order.items[i].isqty).format("0,0.0");
          let col2 = sbc.numeral(order.items[i].isamt).format("0,0.00");
          let col3 = sbc.numeral(order.items[i].ext).format("0,0.00");
          let col11 = col1 + "&nbsp;&nbsp;x" + "&nbsp;".repeat((Math.floor(printerLen / 3) - col1.length) - 3);
          let col21 = "&nbsp;".repeat(Math.floor((Math.floor(printerLen / 3) - col2.length) / 2)) + col2 + "&nbsp;".repeat(Math.floor((Math.floor(printerLen / 3) - col2.length) / 2));
          let col31 = "&nbsp;".repeat(Math.floor(printerLen / 3) - col3.length) + col3;
          let col12 = col1 + "  x" + " ".repeat((Math.floor(printerLen / 3) - col1.length) - 3);
          let col22 = " ".repeat(Math.floor((Math.floor(printerLen / 3) - col2.length) / 2)) + col2 + " ".repeat(Math.floor((Math.floor(printerLen / 3) - col2.length) / 2));
          let col32 = " ".repeat(Math.floor(printerLen / 3) - col3.length) + col3;
          let cols1 = col11 + "" + col21 + "" + col31;
          let cols2 = col12 + "" + col22 + "" + col32;
          str.push(thiss.mrow([cols1, "1", "", "", true], [cols2]));
        }
        str.push(thiss.mrow(["-".repeat(printerLen), "1"]));
        str.push(thiss.mrow([order.itemcount, "1"], ["Items(s)", "1", "C"]));
        str.push(thiss.mrow(["=".repeat(printerLen), "1"]));
        str.push(thiss.mrow(["SUBTOTAL", "1"], [sbc.numeral(order.total).format("0,0.00"), "1", "R"]));
        str.push(thiss.mrow(["TOTAL", "0"], [sbc.numeral(order.total).format("0,0.00"), "0", "R"]));
        str.push(thiss.mrow(["-".repeat(printerLen), "1"]));
        if (order.paymenttype == "CASH") {
          str.push(thiss.mrow(["PAYMENT RECEIVED:", "1"], [order.payment, "1", "R"]));
          str.push(thiss.mrow([order.paymenttype, "1"]));
          str.push(thiss.mrow(["CHANGE AMOUNT:", "1"], [order.change, "1", "R"]));
        } else {
          str.push(thiss.mrow(["PAYMENT RECEIVED:", "1"], ["0.00", "1", "R"]));
          str.push(thiss.mrow([order.paymenttype, "1"]));
          str.push(thiss.mrow(["CHANGE AMOUNT:", "1"], ["0.00", "1", "R"]));
        }
        str.push(thiss.mrow(["Acknowledgement Receipt", "1", "C"]));
        str.push(thiss.mrow(["Thank You!", "1", "C"]));

        thiss.generateReport(str, printerLen).then(res => {
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "", func: "" };
          sbc.globalFunc.printLayout = res.view;
          sbc.globalFunc.printData = res.print;
          sbc.globalFunc.mprint();

          // console.log("printReceipt: ", res);
          // sbc.globalFunc.printLayout = res.view;
          // sbc.showReceiptLookup = true;
        });
      },
      isBTEnabled: function () {
        return new Promise((resolve, reject) => {
          cordova.plugins.diagnostic.isBluetoothEnabled(function (enabled) {
            console.log("Bluetooth is " + (enabled ? "enabled" : "disabled"));
            resolve(enabled);
          }, function (error) {
            console.error("The following error occurred: " + error);
            reject(error);
          });
        });
      },
      getBTState: function () {
        return new Promise((resolve, reject) => {
          cordova.plugins.diagnostic.getBluetoothState((state) => {
            if (state === cordova.plugins.diagnostic.bluetoothState.POWERED_ON) {
              resolve(true);
            } else {
              resolve(false);
            }
          }, err => {
            reject(err);
          });
        });
      },
      enableBT: function () {
        return new Promise((resolve, reject) => {
          cordova.plugins.diagnostic.setBluetoothState(() => {
            resolve();
          }, err => {
            reject(err);
          }, true);
        });
      },
      continuePrint: function (printername) {
        console.log("continuePrint");
        cfunc.showLoading("Connecting to printer");
        sbc.globalFunc.connectPrinter(printername).then(res => {
          if (res) {
            $q.loading.hide();
            sbc.globalFunc.recon = 0;
            sbc.globalFunc.continuePrint2();
          } else {
            if (sbc.globalFunc.recon >= 3) {
              cfunc.showMsgBox("Cant connect to printer", "negative", "warning");
              sbc.globalFunc.recon = 0;
              $q.loading.hide();
            } else {
              sbc.globalFunc.recon += 1
              cfunc.showLoading("Reconnecting to printer: " + sbc.globalFunc.recon);
              sbc.globalFunc.continuePrint(printername);
            }
          }
        }).catch(err => {
          console.log(err);
          if (sbc.globalFunc.recon >= 3) {
            cfunc.showMsgBox("Cant connect to printer", "negative", "warning");
            sbc.globalFunc.recon = 0;
            $q.loading.hide();
          } else {
            sbc.globalFunc.recon += 1;
            sbc.globalFunc.continuePrint(printername);
          }
        });
      },
      printText: function (data) {
        console.log("printText");
        switch (data.ftype) {
          // case "0": case 0: case "":
          //   window.BTPrinter.printText(function (d) {
          //     console.log("text printed: ", d, "--- str:", data.str, "--- ftype:", data.ftype);
          //   }, function (err) {
          //     console.log("error printing text: ", err);
          //   }, "\x1B\x61\x00" + data.str + "\n");
          //   break;
          default:
            window.BTPrinter.printTextSizeAlign(function (d) {
              console.log("text printed: ", d, "--- str:", data.str, "--- ftype:", data.ftype);
            }, function (err) {
              console.log("error printing text: ", err.message);
            }, "\x1B\x61\x00" + data.str, data.ftype, "0"); // string, size, align
            break;
        }
      },
      continuePrint2: function () {
        console.log("continuePrint2");
        sbc.showReceiptLookup = true;
        console.log("showreceipt:", sbc.showReceiptLookup);
        if (sbc.globalFunc.printData.length > 0) {
          for (var s in sbc.globalFunc.printData) {
            sbc.globalFunc.printText(sbc.globalFunc.printData[s]);
            if (parseInt(s) + 1 === sbc.globalFunc.printData.length) console.log("Print done");
          }
        }
      },
      connectPrinter: function (printername) {
        console.log("connectPrinter");
        return new Promise((resolve, reject) => {
          window.BTPrinter.connect(function (data) {
            window.BTPrinter.connected(function (data2) {
              if (data2) {
                resolve(true);
              } else {
                resolve(false);
              }
            }, function (err) {
              reject(err);
            })
          }, function (err) {
            reject(err);
          }, printername);
        })
      },
      mprint: function () {
        sbc.globalFunc.getBTState().then(res => {
          console.log("getBTSTate: ", res);
          if (res) {
            sbc.globalFunc.continueMPrint();
          } else {
            sbc.globalFunc.enableBT().then(() => {
              setTimeout(function () {
                sbc.globalFunc.isBTEnabled().then(res => {
                  if (res) {
                    sbc.globalFunc.continueMPrint();
                  } else {
                    setTimeout(function () {
                      sbc.globalFunc.continueMPrint();
                    }, 1000);
                  }
                }).catch(err => {
                  console.log("isBTEnabled err: ", err);
                });
              }, 1000);
            }).catch(err => {
              cfunc.showMsgBox(err, "negative", "warning");
            });
          }
        }).catch(err => {
          cfunc.showMsgBox(err, "negative", "warning");
        });
      },
      continueMPrint: function () {
        sbc.showLookup = true;
        sbc.globalFunc.lookupTableSelect = false;
        sbc.globalFunc.lookupAction = "lookupPrinters";
        sbc.lookupTitle = "Select Printer";
        sbc.globalFunc.lookupCols = [{ name: "printername", label: "Name", align: "left", field: "name", sortable: true }];
        window.BTPrinter.list(function (data) {
          sbc.globalFunc.lookupData = [];
          if (data.length > 0) {
            while (data.length) sbc.globalFunc.lookupData.push({ name: data.splice(0, 3)[0] });
          }
          $q.loading.hide();
        }, function (err) {
          console.log("Error: ", err);
          $q.loading.hide();
        });
      },
      mrow: function (val, val2 = []) {
        return {
          val1: {
            val: val[0],
            size: typeof(val[1]) !== "undefined" ? val[1] : "",
            align: typeof(val[2]) !== "undefined" ? val[2] : "",
            type: typeof(val[3]) !== "undefined" ? val[3] : "",
            skip: typeof(val[4]) !== "undefined" ? val[4] : false
          },
          val2: {
            val: typeof(val2[0]) !== "undefined" ? val2[0] : "",
            size: typeof(val2[1]) !== "undefined" ? val2[1] : "",
            align: typeof(val2[2]) !== "undefined" ? val2[2] : ""
          }
        };
      },
      generateReport: function (str, printerLen) {
        return new Promise((resolve) => {
          let mview = [];
          let mprint = [];
          let str1;
          let str1size;
          let str1align;
          let str1type;
          let str2;
          let str2align;
          let strs1;
          let strs11;
          for (var s in str) {
            str1 = str[s].val1.val;
            str1size = str[s].val1.size;
            str1align = str[s].val1.align;
            str1type = str[s].val1.type;
            str2 = str[s].val2.val;
            str2align = str[s].val2.align;
            if (str[s].val1.skip) {
              mprint.push({ str: str2, ftype: str1size, type: "text" });
              mview.push({ str: str1, ftype: str1size, type: "text" });
            } else {
              if ((str1.length + str2.length) >= printerLen) {
                mprint.push({ str: str1 + "" + str2, ftype: str1size, type: "text" });
                mview.push({ str: str1 + "" + str2, ftype: str1size, type: "text" });
              } else {
                if (str2 === "") {
                  switch (str1align.toLowerCase()) {
                    case "c": case "r":
                      let len = Math.floor(printerLen - str1.length);
                      if (str1align.toLowerCase() === "c") len = Math.floor((printerLen - str1.length) / 2);
                      strs1 = "&nbsp;".repeat(len) + str1;
                      strs11 = " ".repeat(len) + str1;
                      mview.push({ str: strs1, ftype: str1size, type: "text" });
                      mprint.push({ str: strs11, ftype: str1size, type: "text" });
                      break;
                    default:
                      mview.push({ str: str1, ftype: str1size, type: "text" });
                      mprint.push({ str: str1, ftype: str1size, type: "text" });
                      break;
                  }
                } else {
                  switch (str2align.toLowerCase()) {
                    case "c": case "r":
                      let len = Math.floor(printerLen - ((str1 + "").length + (str2 + "").length));
                      if (str2align.toLowerCase() === "c") len = Math.floor((printerLen - ((str1 + "").length + (str2 + "").length)) / 2);
                      console.log("str1: ", str1, " str2: ", str2, " len: ", len, " str1len: ", (str1 + "").length, " str2len: ", str2.length);
                      strs1 = str1 + "&nbsp;".repeat(len) + str2;
                      strs11 = str1 + " ".repeat(len) + str2;
                      mview.push({ str: strs1, ftype: str1size, type: "text" });
                      mprint.push({ str: strs11, ftype: str1size, type: "text" });
                      break;
                    default:
                      mview.push({ str: str1 + str2, ftype: str1size, type: "text" });
                      mprint.push({ str: str1 + str2, ftype: str1size, type: "text" });
                      break;
                  }
                }
              }
            }
            if (parseInt(s) + 1 === str.length) {
              resolve({ view: mview, print: mprint });
            }
          }
        });
      },
      saveTransStock: function (stocks, orderno) {
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        let sql;
        let data = [];
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            for (var i in stocks) {
              sql = "insert into transstock(userid, orderno, barcode, itemname, amt, qty, total, uom, factor, rem, disc) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              data = [storage.user.userid, orderno, stocks[i].barcode, stocks[i].itemname, stocks[i].newamt, stocks[i].qty, stocks[i].total, stocks[i].newuom, stocks[i].newfactor, stocks[i].rem, stocks[i].disc];
              if (sbc.globalFunc.company === "sbc") {
                sql = "insert into transstock(userid, orderno, barcode, itemname, isamt, amt, isqty, iss, total, uom, factor, rem, disc) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                data = [storage.user.userid, orderno, stocks[i].barcode, stocks[i].itemname, stocks[i].isamt, stocks[i].amt, stocks[i].isqty, stocks[i].iss, stocks[i].ext, stocks[i].uom, stocks[i].factor, stocks[i].rem, stocks[i].disc];
              }
              tx.executeSql(sql, data, function (tx, res) {
                console.log("transstock saved");
                if (parseInt(i) + 1 === stocks.length) {
                  sbc.globalFunc.updateItemStat(stocks).then(() => {
                    resolve("done");
                  }).catch(err => {
                    console.log("saveTransstock error: ", err);
                    reject(err);
                  });
                }
              }, function (tx, err) {
                console.log("error transstock save: ", err.message);
              });
            }
          }, function (err) {
            console.log("saveTransstock error: ", err.message);
          });
        });
      },
      updateItemStat: function (stocks) {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            let itembal;
            for (var i in stocks) {
              itembal = sbc.numeral(stocks[i].itembal).value() - sbc.numeral(stocks[i].iss).value();
              if (sbc.globalFunc.company === "sbc") {
                tx.executeSql("delete from cart where itemid=" + stocks[i].itemid);
                tx.executeSql("update itemstat set qty=" + itembal + " where itemid=" + stocks[i].itemid);
              } else {
                tx.executeSql("update item set qty=?, newuom=uom, newamt=amt, newfactor=factor, rem=?, seq=0, newdisc=disc where barcode=?", ["0", "", stocks[i].barcode]);
              }
              if (parseInt(i) + 1 === stocks.length) resolve("done");
            }
          }, function (err) {
            reject(err.message);
          });
        });
      },
      getStockItems: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            let sql = "select item.*, itemstat.qty as itembal from item left join itemstat on itemstat.itemid=item.itemid where (item.qty <> 0 and item.qty <> ?)";
            let data = ["0"];
            if (sbc.globalFunc.company === "sbc") {
              sql = "select ifnull(itemstat.qty,0) as itembal, item.itemid, item.barcode, item.itemname, cart.isamt, cart.amt, cart.isqty, cart.iss, cart.ext, cart.uom, cart.factor, cart.rem, cart.disc from cart left join item on item.itemid=cart.itemid left join itemstat on itemstat.itemid=cart.itemid";
              data = [];
            }
            tx.executeSql(sql, data, function (tx, res) {
              if (res.rows.length > 0) {
                let items = [];
                let discount;
                let tot;
                for (var x = 0; x < res.rows.length; x++) {
                  if (res.rows.item(x).qty < 0) {
                    reject("Checkout error, negative items are not allowed");
                  } else {
                    items.push(res.rows.item(x));
                    if (sbc.globalFunc.company !== "sbc") {
                      discount = sbc.globalFunc.computeDiscount(sbc.numeral(res.rows.item(x).newamt).value(), sbc.numeral(res.rows.item(x).newdisc).value());
                      tot = sbc.numeral(sbc.numeral(discount).value() * res.rows.item(x).qty).format("0,0.00");
                      items[x].total = tot;
                    }
                    resolve(items);
                  }
                }
              } else {
                reject("Cart empty");
              }
            }, function (tx, err) {
              reject(err.message);
            });
          });
        });
      },
      getLastOrderno: function (type = "trans") {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select orderno from transhead order by orderno desc limit 1", [], function (tx, res) {
              let orderno = 1;
              if (res.rows.length > 0) {
                orderno = parseInt(res.rows.item(0).orderno) + 1;
                if (isNaN(orderno)) orderno = 1;
                resolve(orderno.toString().padStart(10, "0"));
              } else {
                tx.executeSql("select lastorderno from config", [], function (tx, res2) {
                  if (res2.rows.length > 0) orderno = parseInt(res2.rows.item(0).lastorderno) + 1;
                  if (isNaN(orderno)) orderno = 1;
                  resolve(orderno.toString().padStart(10, "0"));
                }, function (tx, err) {
                  console.log(err);
                  reject(2 + "" + err.message);
                });
              }
            }, function (tx, err) {
              reject(1 + "" + err.message);
            });
          });
        });
      },
      keypadClick: function (n) {
        console.log(n, " clicked");
        let payment = sbc.modulefunc.checkoutForm.payment2;
        if (n === "x" || n === "X") {
          payment = payment.substring(0, payment.length - 1);
        } else if (n === ".") {
          if (payment !== "undefined" && payment !== "" && payment !== null && typeof(payment) !== "undefined") {
            if (!payment.match(/\./g)) payment = payment + ".";
          } else {
            if (!payment.match(/\./g)) payment = "0.";
          }
        } else {
          console.log("payment: ", payment);
          if (payment !== "undefined" && payment !== "" && payment !== null && typeof(payment) !== "undefined") {
            payment = payment + "" + n;
          } else {
            payment = "" + n;
          }
        }
        sbc.modulefunc.checkoutForm.payment2 = payment;
        sbc.modulefunc.checkoutForm.payment = sbc.numeral(payment).format("0,0.00");
      },
      selectLookupBtnClick: function () {
        console.log("selectLookupBtnClick called", sbc.globalFunc.lookupSelected);
        switch (sbc.globalFunc.selectLookupType) {
          case "downloadSAPDoc":
            if (sbc.globalFunc.lookupSelected.length > 0) {
              sbc.globalFunc.downloadSelectedSAPDocs();
            } else {
              cfunc.showMsgBox("No Document selected", "negative", "warning");
            }
            break;
          case "downloadDoc":
            if (sbc.globalFunc.lookupSelected.length > 0) {
              sbc.globalFunc.downloadSelectedDocs();
            } else {
              cfunc.showMsgBox("No Document selected", "negative", "warning");
            }
            break;
          case "area":
            if (sbc.globalFunc.lookupSelected.length > 0) {
              sbc.globalFunc.downloadCustomers();
            } else {
              cfunc.showMsgBox("No area selected", "negative", "warning");
            }
            break;
          case "rmstocklookup":
            if (sbc.globalFunc.lookupSelected.length > 0) {
              sbc.globalFunc.verifySAPStock();
            } else {
              cfunc.showMsgBox("No item(s) selected", "negative", "warning");
            }
            break;
          case "inventorywh":
            sbc.globalFunc.downloadSelectedWH();
            break;
        }
      },
      getIndex: function (data, selItem, col) {
        if (data.length > 0) {
          console.log(selItem, " col: ", col);
          for (var x = 0; x < data.length; x++) {
            if (data[x][col] === selItem[col]) return x;
            if (parseInt(x) + 1 === data.length) return "";
          }
        } else {
          return "";
        }
      },
      checkTransactions: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            if (sbc.globalFunc.config === "ordering") {
              tx.executeSql("select count(*) as tcount from (select * from transhead union all select * from transhistoryhead)", [], function (tx, res) {
                if (res.rows.item(0).tcount > 0) {
                  resolve(true);
                } else {
                  resolve(false);
                }
              }, function (tx, err) {
                reject(err);
              });
            } else {
              resolve(false);
            }
          }, function (err) {
            reject(err);
          });
        });
      },
      loadTransTypes: function () {
        cfunc.getTableData("config", "operationtype").then(optype => {
          if (optype === "" || optype === null || typeof(optype) === "undefined") {
            cfunc.showMsgBox("Operation type not set", "negative", "warning");
            return;
          }
          switch (optype) {
            case "Both":
              switch (sbc.selDoc.name) {
                case "regtenants":
                  sbc.modulefunc.docForm.transtypeOpts = ["Rent", "CUSA", "Electricity", "Water", "Others", "Electric Reading", "Water Reading"];
                  break;
                case "ambulant":
                  sbc.modulefunc.docForm.transtypeOpts = ["Rent", "CUSA", "Electricity", "Water"];
                  break;
              }
              sbc.modulefunc.docForm.transtype = "Rent";
              break;
            case "Collecting":
              switch (sbc.selDoc.name) {
                case "regtenants":
                  sbc.modulefunc.docForm.transtypeOpts = ["Rent", "CUSA", "Electricity", "Water", "Others"];
                  break;
                case "ambulant":
                  sbc.modulefunc.docForm.transtypeOpts = ["Rent", "CUSA", "Electricity", "Water", "Others"];
                  break;
              }
              sbc.modulefunc.docForm.transtype = "Rent";
              break;
            case "Reading":
              sbc.modulefunc.docForm.transtypeOpts = ["Electric Reading", "Water Reading"];
              sbc.modulefunc.docForm.transtype = "Electric Reading";
              break;
            default:
              sbc.modulefunc.docForm.transtypeOpts = [];
              sbc.modulefunc.docForm.transtype = "";
              break;
          }
        });
      },
      checkTrans: function (type) {
        return new Promise((resolve, reject) => {
          let transtype = "";
          switch (sbc.modulefunc.docForm.transtype) {
            case "Rent": transtype = "R"; break;
            case "CUSA": transtype = "C"; break;
            case "Electricity": transtype = "E"; break;
            case "Water": transtype = "W"; break;
            case "Others": transtype = "O"; break;
            case "Electric Reading": transtype = "E"; break;
            case "Water Reading": transtype = "W"; break;
          }
          cfunc.getTableData("config", "collectiondate").then(colDate => {
            if (colDate !== "" && colDate !== null && typeof(colDate) !== "undefined") {
              sbc.db.transaction(function (tx) {
                let qry = "";
                if (type === "payment") {
                  qry = "select a.amount, a.dateid, a.status, a.remarks, a.prev, a.current, a.consumption from (select amount, dateid, status, remarks, 0 as prev, 0 as current, 0 as consumption from dailycollection where dateid=? and type=? union all select amount, dateid, status, remarks, 0 as prev, 0 as current, 0 as consumption from hdailycollection where clientid=? and type=?) as a";
                } else {
                  qry = "select a.amount, a.status, a.remarks, a.prev, a.current, a.consumption from (select ending as amount, null as status, remarks, beginning as prev, ending as current, consumption from reading where clientid=? and type=? union all select ending as amount, null as status, remarks, beginning as prev, ending as current, consumption from hreading where clientid=? and type=?) as a";
                }
                let hasrecord = false;
                tx.executeSql(qry, [sbc.modulefunc.docForm.selTenant.clientid, transtype, sbc.modulefunc.docForm.selTenant.clientid, transtype], function (tx, res) {
                  if (res.rows.length > 0) {
                    hasrecord = false;
                    for (var x = 0; x < res.rows.length; x++) {
                      if (res.rows.item(x).dateid === colDate) {
                        hasrecord = true;
                        resolve({
                          hasRecord: hasrecord,
                          amt: res.rows.item(x).amount,
                          type: "trans",
                          status: res.rows.item(x).status,
                          remarks: res.rows.item(x).remarks,
                          prev: res.rows.item(x).prev,
                          current: res.rows.item(x).current,
                          consumption: res.rows.item(x).consumption
                        });
                      }
                      if (parseInt(x) + 1 === res.rows.length) {
                        resolve({ hasRecord: hasrecord });
                      }
                    }
                  } else {
                    resolve({ hasRecord: false });
                  }
                }, function (tx, err) {
                  reject(err);
                });
              });
            }
          });
        });
      },
      saveReading: function () {},
      cancelReading: function () {
        sbc.showInputLookup = false;
      },
      saveCollection: function () {
        switch (sbc.globalFunc.formtype) {
          case "form1": case "form2":
            $q.dialog({
              title: "Payment Options",
              message: "Choose your option)",
              options: {
                type: "radio",
                model: "op",
                items: [
                  { label: "OP", value: "op", color: "primary" },
                  { label: "NP", value: "np", color: "primary" }
                ]
              },
              ok: { flat: true, color: "primary" },
              cancel: { flat: true, color: "negative" }
            }).onOk(data => {
              sbc.globalFunc.continueSaveCollection(data);
            });
            break;
          default:
            break;
        }
      },
      cancelCollection: function () {
        sbc.showInputLookup = false;
      },
      continueSaveCollection: function (type) {
        let user = $q.localStorage.getItem("sbcmobilev2Data");
        if (sbc.modulefunc.docForm.selArea.phase === "" && sbc.modulefunc.docForm.selArea.section === "") {
          cfunc.showMsgBox("Please select Area", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.docForm.selTenant.clientid === "" || typeof(sbc.modulefunc.docForm.selTenant.clientid) === "undefined") {
          cfunc.showMsgBox("Please select Tenant", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.docForm.transtype === "") {
          cfunc.showLoading("Please select Transaction type", "negative", "warning");
          return;
        }
        let transtype = "";
        switch (sbc.modulefunc.docForm.transtype) {
          case "Rent": transtype = "R"; break;
          case "CUSA": transtype = "C"; break;
          case "Electricity": transtype = "E"; break;
          case "Water": transtype = "W"; break;
          case "Others": transtype = "O"; break;
          case "Electric Reading": transtype = "E"; break;
          case "Water Reading": transtype = "W"; break;
        }
        cfunc.getTableData("config", "collectiondate").then(colDate => {
          if (colDate === "" || colDate === null || typeof(colDate) === "undefined") {
            cfunc.showMsgBox("Collection date not set", "negative", "warning");
            $q.loading.hide();
            return;
          }
          switch (type) {
            case "op":
              cfunc.showLoading();
              sbc.globalFunc.tenantHasRecord("collection", { clientid: sbc.modulefunc.docForm.selTenant.clientid, type: transtype, dateid: colDate }).then(hasRecord => {
                const datenow = cfunc.getDateTime("datetime");
                if (hasRecord) {
                  cfunc.showMsgBox("Tenant already have collection", "negative", "warning");
                  $q.loading.hide();
                  return;
                }
                const params = {
                  clientid: sbc.modulefunc.docForm.selTenant.clientid,
                  clientname: sbc.modulefunc.docForm.selTenant.clientname,
                  amount: sbc.numeral(sbc.modulefunc.inputLookupForm.payment).format("0.00"),
                  status: "OP",
                  dateid: colDate,
                  center: sbc.modulefunc.docForm.selTenant.center,
                  type: transtype,
                  remarks: sbc.modulefunc.inputLookupForm.remarks,
                  collectorid: user.collectorid,
                  collectorname: user.user.name,
                  transtime: datenow,
                  stallnum: sbc.modulefunc.docForm.selTenant.loc,
                  transtype: sbc.modulefunc.docForm.transtype,
                  outstandingbal: sbc.numeral(sbc.modulefunc.inputLookupForm.outstandingbal).format("0.00"),
                  rent: sbc.numeral(sbc.modulefunc.docForm.selTenant.dailyrent).format("0.00"),
                  cusa: sbc.numeral(sbc.modulefunc.docForm.selTenant.dcusa).format("0.00"),
                  section: sbc.modulefunc.docForm.selTenant.section,
                  receiptTitle: "Acknowledgement Receipt",
                  receiptType: "payment",
                  phase: sbc.modulefunc.docForm.selArea.phase,
                  section: sbc.modulefunc.docForm.selArea.section
                };
                sbc.globalFunc.savePayment(params);
              });
              break;
            case "np":
              $q.dialog({
                message: "Do you want to tag this tenant as NP?",
                ok: { flat: true, color: "primary", label: "Yes" },
                cancel: { flat: true, color: "negative", label: "No" }
              }).onOk(() => {
                cfunc.showLoading();
                sbc.globalFunc.tenantHasRecord("collection", { clientid: sbc.modulefunc.docForm.selTenant.clientid, type: transtype, dateid: colDate }).then(hasRecord => {
                  if (hasRecord) {
                    cfunc.showMsgBox("Tenant already have collection", "negative", "warning");
                    $q.loading.hide();
                    return;
                  }
                  const params = { clientid: sbc.modulefunc.docForm.selTenant.clientid, center: sbc.modulefunc.docForm.selTenant.center, colDate: colDate };
                  sbc.globalFunc.saveNP(params, "single", transtype);
                });
              });
              break;
          }
        });
      },
      saveNP: function (params, type, transtype) {
        let user = $q.localStorage.getItem("sbcmobilev2Data");
        const datenow = cfunc.getDateTime("datetime");
        let lastLine = 0;
        const qry = "insert into dailycollection(clientid, amount, status, dateid, center, type, collectorid, transtime, phase, section) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        const data = [params.clientid, 0, "NP", params.colDate, params.center, transtype, storage.collectorid, datenow, sbc.modulefunc.docForm.selArea.phase, sbc.modulefunc.docForm.selArea.section];
        sbc.db.transaction(function (tx) {
          tx.executeSql(qry, data, function (tx, res2) {
            if (type === "single") {
              cfunc.showMsgBox("Tagging successful", "positive");
              // loadtenants
              sbc.showInputLookup = false;
              $q.loading.hide();
            }
          }, function (tx, err) {
            cfunc.saveErrLog(qry, data, err.message);
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        }, function (err) {
          cfunc.showMsgBox(err.message, "negative", "warning");
          $q.loading.hide();
        });
      },
      savePayment: function (params) {
        let lastLine = 0;
        const qry = "insert into dailycollection(clientid, amount, status, dateid, center, type, remarks, collectorid, transtime, phase, section) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        const data = [params.clientid, params.amount, params.status, params.dateid, params.center, params.type, params.remarks, params.collectorid, params.transtime, params.phase, params.section];
        sbc.db.transaction(function (tx) {
          tx.executeSql(qry, data, function (tx, res) {
            params.line = res.insertId;
            params.reprint = false;
            cfunc.showMsgBox("Payment saved", "positive");
            sbc.showInputLookup = false;
            sbc.modulefunc.docForm.selTenant = [];
            sbc.modulefunc.docForm.tenant = "";
            sbc.modulefunc.docForm.category = "";
            if (sbc.selDoc.name !== "ambulant") {
              sbc.globalFunc.loadLookup("categorylookup");
            }
            sbc.globalFunc.printCollectionReceipt(params, "payment");
            $q.loading.hide();
          }, function (tx, err) {
            cfunc.saveErrLog(qry, data, err.message);
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        }, function (err) {
          cfunc.showMsgBox(err.message, "negative", "warning");
          $q.loading.hide();
        });
      },
      tenantHasRecord: function (type, params) {
        return new Promise((resolve, reject) => {
          let qry;
          let qry2;
          let data = [params.clientid, params.type];
          let data2 = [params.clientid, params.type, params.dateid];
          switch (type) {
            case "reading":
              qry = "select line from reading where clientid=? and type=?";
              qry2 = "select line from hreading where clientid=? and type=? and dateid=?";
              break;
            case "collection":
              qry = "select line from dailycollection where clientid=? and type=?";
              qry2 = "select line from hdailycollection where clientid=? and type=? and dateid=?";
              break;
          }
          sbc.db.transaction(function (tx) {
            tx.executeSql(qry, data, function (tx, trans) {
              if (trans.rows.length > 0) {
                resolve(true);
              } else {
                tx.executeSql(qry2, data2, function (tx, htrans) {
                  if (htrans.rows.length > 0) {
                    resolve(true);
                  } else {
                    resolve(false);
                  }
                }, function (tx, err) {
                  reject(err);
                });
              }
            }, function (tx, err) {
              reject(err);
            });
          });
        });
      },
      showCollectForm: function (form) {
        for (var a in sbc.selinputlookupfields) sbc.selinputlookupfields[a].show = "false";
        for (var a in sbc.selinputlookupfields) {
          switch (sbc.selinputlookupfields[a].name) {
            case "arealabel": case "transtype": case "tenant": case "loc": sbc.selinputlookupfields[a].show = "true"; break;
          }
          switch (form) {
            case "form1":
              switch (sbc.selinputlookupfields[a].name) {
                case "rent":
                  if (sbc.modulefunc.docForm.transtype === "Rent") sbc.selinputlookupfields[a].show = "true";
                  break;
                case "cusa":
                  if (sbc.modulefunc.docForm.transtype === "CUSA") sbc.selinputlookupfields[a].show = "true";
                  break;
                case "outstandingbal": case "payment": case "amt":
                  sbc.selinputlookupfields[a].show = "true";
                  break;
              }
              break;
            case "form2":
              switch (sbc.selinputlookupfields[a].name) {
                case "outstandingbal": case "payment": case "balance": sbc.selinputlookupfields[a].show = "true"; break;
              }
              break;
            case "form3":
              switch (sbc.selinputlookupfields[a].name) {
                case "remarks": case "payment": sbc.selinputlookupfields[a].show = "true"; break;
              }
              break;
          }
        }
      },
      loadAreasLookup: function () {
        sbc.globalFunc.lookupAction = "arealookup";
        sbc.globalFunc.lookupFields = "";
        sbc.globalFunc.lookupCols = [
          { name: "label", label: "Area", field: "label", align: "left", sortable: true }
        ];
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select * from clientarea", [], function (tx, res) {
            sbc.globalFunc.lookupData = [];
            sbc.globalFunc.lookupTableSelect = false;
            sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Area", func: "" };
            sbc.lookupTitle = "Select Area";
            sbc.showLookup = true;
            for (var x = 0; x < res.rows.length; x++) {
              sbc.globalFunc.lookupData.push(res.rows.item(x));
              sbc.globalFunc.lookupData[x].label = res.rows.item(x).phase + "-" + res.rows.item(x).sectionname
              if (parseInt(x) + 1 == res.rows.length) $q.loading.hide()
            }
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      loadTransTypesLookup: function () {
        // sbc.globalFunc.lookupAction = "transtypeslookup";
        sbc.globalFunc.lookupFields = "";
        sbc.globalFunc.lookupCols = [{ name: "label", label: "Transaction Type", field: "label", align: "left", sortable: true }];
        sbc.globalFunc.lookupData = [];
        sbc.globalFunc.lookupTableSelect = false;
        sbc.globalFunc.lookupTableFilter = { type: "filter", field: "", label: "Search Transaction Type", func: "" };
        sbc.globalFunc.lookupTitle = "Select Transaction Type";
        sbc.showLookup = true;
        cfunc.getTableData("config", "operationtype").then(optype => {
          if (optype === "" || optype === null || typeof(optype) === "undefined") {
            cfunc.showMsgBox("Operation type not set", "negative", "warning");
            $q.loading.hide()
            return;
          }
          switch (optype) {
            case "Both":
              switch (sbc.selDoc.name) {
                case "regtenants": case "admin":
                  sbc.globalFunc.lookupData = [{ label: "Rent" }, { label: "CUSA" }, { label: "Electricity" }, { label: "Water" }, { label: "Others" }, { label: "Electric Reading" }, { label: "Water Reading" }];
                  break;
                case "ambulant":
                  sbc.globalFunc.lookupData = [{ label: "Rent" }, { label: "CUSA" }, { label: "Electricity" }, { label: "Water" }];
                  break;
              }
              sbc.modulefunc.docForm.transtype = "Rent";
              break;
            case "Collecting":
              switch (sbc.selDoc.name) {
                case "regtenants": case "ambulant": case "admin":
                  sbc.globalFunc.lookupData = [{ label: "Rent" }, { label: "CUSA" }, { label: "Electricity" }, { label: "Water" }, { label: "Others" }];
                  break;
              }
              sbc.modulefunc.docForm.transtype = "Rent";
              break;
            case "Reading":
              sbc.globalFunc.lookupData = [{ label: "Electric Reading" }, { label: "Water Reading" }];
              sbc.modulefunc.docForm.transtype = "Electric Reading";
              break;
            default:
              sbc.globalFunc.lookupData = [];
              sbc.modulefunc.docForm.transtype = "";
              break;
          }
        });
      },
      getTransType: function (type) {
        switch (type) {
          case "Rent": return "R"; break;
          case "CUSA": return "C"; break;
          case "Electricity": return "E"; break;
          case "Water": return "W"; break;
          case "Others": return "O"; break;
          case "Electric Reading": return "E"; break;
          case "Water Reading": return "W"; break;
          default: return ""; break;
        }
      },
      printCollectionReceipt: function (data, type) {
        let collectionReportTotal = 0;
        let collectionReportData = [];
        let vat = 0;
        let payment1 = 0;
        let netbal = 0;
        let outstandingbal = 0;
        let cusa = 0;
        let rent = 0;
        let printer = "";
        let printerlen = 0;
        let printtype = "";
        switch (data.receiptType) {
          case "collectionreport":
            let datas = [];
            let check = [];
            for (var a in data.data) {
              check = datas.filter(d => d.group === data.data[a].type + "-" + data.data[a].status);
              if (check.length === 0) datas.push({ group: data.data[a].type + "-" + data.data[a].status });
            }
            if (datas.length > 0) {
              let dd = [];
              let subtotal = 0;
              for (var d in datas) {
                subtotal = 0;
                for (var aa in data.data) {
                  if ((data.data[aa].type + "-" + data.data[aa].status) === datas[d].group) {
                    subtotal = sbc.numeral(sbc.numeral(subtotal).value() + sbc.numeral(data.data[aa].amount).value()).format("0,0.00");
                    collectionReportTotal = sbc.numeral(sbc.numeral(collectionReportTotal).value() + sbc.numeral(data.data[aa].amount).value()).format("0,0.00");
                  }
                }
                dd = data.data.filter(f => f.type + "-" + f.status === datas[d].group);
                datas[d].total = subtotal;
                datas[d].data = dd;
              }
            }
            collectionReportData = datas;
            break;
          default:
            vat = sbc.numeral(sbc.numeral(data.amount).value() * 0.12).format("0,0.00");
            payment1 = sbc.numeral(sbc.numeral(data.amount).value() - sbc.numeral(vat).value()).format("0,0.00");
            if (sbc.numeral(data.outstandingbal).value() < 0) {
              netbal = sbc.numeral(sbc.numeral(data.outstandingbal).value() + sbc.numeral(data.amount).value()).format("0,0.00");
            } else {
              netbal = sbc.numeral(sbc.numeral(data.outstandingbal).value() - sbc.numeral(data.amount).value()).format("0,0.00");
            }
            outstandingbal = sbc.numeral(data.outstandingbal).format("0,0.00");
            cusa = sbc.numeral(data.cusa).format("0,0.00");
            rent = sbc.numeral(data.rent).format("0,0.00");
            switch (data.type) {
              case "R":
                if (Math.abs(sbc.numeral(data.outstandingbal).value()) < sbc.numeral(data.rent).value()) {
                  if (sbc.numeral(netbal).value() < 0) netbal = "0.00";
                }
                break;
              case "C":
                if (Math.abs(sbc.numeral(data.outstandingbal).value()) < sbc.numeral(data.cusa).value()) {
                  if (sbc.numeral(netbal).value() < 0) netbal = "0.00";
                }
                break;
            }
            break;
        }

        cfunc.getTableData("config", ["printtype", "printerlen", "printer"], true).then(config => {
          if (config.printer !== "" && config.printer !== null && typeof (config.printer) !== "undefined") {
            printer = config.printer;
          }
          if (config.printerlen !== "" && config.printerlen !== null && typeof (config.printerlen) !== "undefined") {
            printerlen = config.printerlen
          }
          if (config.printtype !== "" && config.printtype !== null && typeof (config.printtype) !== "undefined") {
            printtype = config.printtype;
          }
          const thiss = this;
          let str = [];
          str.push(thiss.mrow([data.receiptTitle, "0", "C"]));
          switch (data.receiptType) {
            case "payment":
              if (data.status === "AMB") {
                str.push(thiss.mrow(["Date:    " + data.transtime, "0"]));
                str.push(thiss.mrow(["Collector:    " + data.collectorname, "0"]));
                str.push(thiss.mrow(["Ticket No.:    " + data.line, "0"]));
                str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
                str.push(thiss.mrow([data.status + "-" + data.transtype, "0"]));
                str.push(thiss.mrow(["Payment:    " + sbc.numeral(data.amount).format("0,0.00"), "0"]));
                str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
                str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
                str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              } else {
                str.push(thiss.mrow(["Tenant:    " + data.clientname, "0"]));
                str.push(thiss.mrow(["Stall No.:    " + data.stallnum, "0"]));
                str.push(thiss.mrow(["Location Code:    " + data.section, "0"]));
                str.push(thiss.mrow(["Date:    " + data.dateid, "0"]));
                str.push(thiss.mrow(["Collector:    " + data.collectorname, "0"]));
                str.push(thiss.mrow(["Ticket No.:    " + data.line, "0"]));
                str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
                if (data.reprint) {
                  str.push(thiss.mrow(["DUPPLICATE COPY", "0"]));
                }
                if (data.type === "0") {
                  str.push(thiss.mrow(["OTHER", "0"]));
                  str.push(thiss.mrow(["Payment:    " + sbc.numeral(data.amount).format("0,0.00"), "0"]));
                  str.push(thiss.mrow(["Remarks:    " + data.remarks, "0"]));
                } else {
                  str.push(thiss.mrow(["Outstanding Balance:    " + outstandingbal, "0"]));
                  if (data.type === "R") {
                    str.push(thiss.mrow(["Rent:    " + rent, "0"]));
                  } else if (data.type === "C") {
                    str.push(thiss.mrow(["CUSA:    " + cusa, "0"]));
                  }
                  str.push(thiss.mrow(["Payment:    " + sbc.numeral(data.amount).format("0,0.00"), "0"]));
                  str.push(thiss.mrow(["Net Balance:    " + netbal, "0"]));
                }
                str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              }
              str.push(thiss.mrow(["Payment:    " + payment1, "0"]));
              str.push(thiss.mrow(["VAT:    " + vat, "0"]));
              str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["TOTAL:    " + sbc.numeral(data.amount).format("0,0.00"), "0"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Thank you for your payment", "0", "C"]));
              str.push(thiss.mrow([data.transtime, "0", "C"]));
              break;
            case "reading":
              str.push(thiss.mrow(["Tenant:    " + data.clientname, "0"]));
              str.push(thiss.mrow(["Stall No.:    " + data.stallnum, "0"]));
              str.push(thiss.mrow(["Location Code:    " + data.section, "0"]));
              str.push(thiss.mrow(["Date:    " + data.dateid, "0"]));
              str.push(thiss.mrow(["Collector:    " + data.collectorname, "0"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              if (data.reprint) {
                if (data.type === "E") {
                  str.push(thiss.mrow(["DUPLICATE COPY ELECTRICITY READING:", "0"]));
                } else {
                  str.push(thiss.mrow(["DUPLICATE COPY WATER READING:", "0"]));
                }
              }
              str.push(thiss.mrow(["Beginning:    " + sbc.numeral(data.beginning).format("0,0.00"), "0"]));
              str.push(thiss.mrow(["Ending:    " + sbc.numeral(data.ending).format("0,0.00"), "0"]));
              str.push(thiss.mrow(["Consumption:    " + sbc.numeral(data.consumption).format("0,0.00"), "0"]));
              str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Rate:    " + sbc.numeral(data.rate).format("0,0.00"), "0"]));
              str.push(thiss.mrow(["Amount Due:    " + sbc.numeral(data.amtdue).format("0,0.00"), "0"]));
              break;
            case "collectionreport":
              str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Client " + "-".repeat((printerlen - 10)) + " Amount", "0"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              for (var d in collectionReportData) {
                str.push(thiss.mrow(["(" + collectionReportData[d].group + ")", "0"]));
                for (var dd in collectionReportData[d].data) {
                  if (collectionReportData[d].data[dd].status === "AMB") {
                    str.push(thiss.mrow([collectionReportData[d].data[dd].counts + " - " + sbc.numeral(collectionReportData[d].data[dd].amount).format("0,0.00"), " 0"]));
                  } else {
                    str.push(thiss.mrow([collectionReportData[d].data[dd].line + " - " + collectionReportData[d].data[dd].clientname + " " + collectionReportData[d].data[dd].loc + " - " + sbc.numeral(collectionReportData[d].data[dd].amount).format("0,0.00"), "0"]));
                  }
                }
                str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
                str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
                str.push(thiss.mrow(["Sub Total: " + sbc.numeral(collectionReportData[d].total).format("0,0.00"), "0"]));
                str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              }
              str.push(thiss.mrow(["=".repeat(printerlen), "1"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Grand Total: " + sbc.numeral(collectionReportTotal).format("0,0.00"), "0"]));
              str.push(thiss.mrow(["=".repeat(printerlen), "1"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Collected By: " + data.collectorname, "0"]));
              str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Print Date and Time:", "0"]));
              str.push(thiss.mrow([data.transtime, "0"]));
              break;
            default:
              str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Ticket No.:    " + data.ticket1 + " - " + data.ticket2, "0"]));
              str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Date:    " + data.collDate, "0"]));
              str.push(thiss.mrow(["Amount:    " + sbc.numeral(data.amt).format("0,0.00"), "0"]));
              str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Collected By:    " + data.collectorname, "0"]));
              str.push(thiss.mrow(["-".repeat(printerlen), "1"]));
              str.push(thiss.mrow(["Print Date and Time:", "0", "C"]));
              str.push(thiss.mrow([data.dateid, "0", "C"]));
              break;
          }

          str.push(thiss.mrow([" ".repeat(printerlen), "1"]));
          thiss.generateReport(str, printerlen).then(res => {
            sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "", func: "" };
            sbc.globalFunc.printLayout = res.view;
            sbc.globalFunc.printData = res.print;
            sbc.showReceiptLookup = true;
            if (printtype === "auto") {
              sbc.globalFunc.mprint();
            } else if (printtype === "") {
              cfunc.showMsgBox("Print type not set", "negative", "warning");
            }

            // console.log("printReceipt: ", res);
            // sbc.globalFunc.printLayout = res.view;
          });
        });

      },
      downloadSAPDoc: function () {
        cfunc.getTableData("config", ["deviceid", "serveraddr"], true).then(configdata => {
          if (configdata.serveraddr === "" || configdata.serveraddr === null || typeof (configdata.serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            return;
          }
          cfunc.showLoading();
          api.post(configdata.serveraddr + "/sbcmobilev2/download", { type: "downloadsapdoc", doc: sbc.doc, access: sbc.selDoc.access, devid: configdata.deviceid }).then(res => {
            sbc.globalFunc.lookupTableRowKey = "trno";
            switch(sbc.doc) {
              case "fg":
                sbc.globalFunc.lookupCols = [
                  { name: "ourref", label: "Docnum", field: "ourref", align: "left", sortable: true },
                  { name: "dateid", label: "Date", field: "dateid", align: "left", sortable: true },
                  { name: "yourref", label: "Num at Card", field: "yourref", align: "left", sortable: true },
                  { name: "docno", label: "Trans No.", field: "docno", align: "left", sortable: true }
                ];
                break;
              case "rr":
                sbc.globalFunc.lookupCols = [
                  { name: "ourref", label: "Docnum", field: "ourref", align: "left", sortable: true },
                  { name: "dateid", label: "Date", field: "dateid", align: "left", sortable: true },
                  { name: "client", label: "Supplier Code", field: "client", align: "left", sortable: true },
                  { name: "clientname", label: "Supplier Name", field: "clientname", align: "left", sortable: true },
                  { name: "yourref", label: "Num at Card", field: "yourref", align: "left", sortable: true },
                  { name: "docno", label: "Trans No.", field: "docno", align: "left", sortable: true }
                ];
                break;
              case "rl": case "rm": case "tr": case "tm": case "dr":
                sbc.globalFunc.lookupCols = [
                  { name: "ourref", label: "Docnum", field: "ourref", align: "left", sortable: true },
                  { name: "dateid", label: "Date", field: "dateid", align: "left", sortable: true },
                  { name: "yourref", label: "Prod Order No.", field: "yourref", align: "left", sortable: true },
                  { name: "wh", label: "WH", field: "wh", align: "left", sortable: true },
                  { name: "docno", label: "Trans No.", field: "docno", align: "left", sortable: true }
                ];
                break;
            }
            sbc.globalFunc.selectLookupRowsPerPage = 20;
            sbc.globalFunc.lookupTableSelect = true;
            sbc.globalFunc.lookupTableSelection = "multiple";
            sbc.globalFunc.selectLookupType = "downloadSAPDoc";
            sbc.lookupTitle = "Download Transactions";
            sbc.globalFunc.lookupData = [];
            if (res.data.head.length > 0) {
              for (var x = 0; x < res.data.head.length; x++) {
                sbc.globalFunc.lookupData.push(res.data.head[x]);
                if (parseInt(x) + 1 === res.data.head.length) {
                  console.log("xxx", sbc.globalFunc.lookupData);
                }
              }
            }
            sbc.globalFunc.selectLookupBtnLabel = "Download";
            sbc.showSelectLookup = true;
            $q.loading.hide();
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      downloadDoc: function () {
        cfunc.getTableData("config", ["deviceid", "serveraddr"], true).then(configdata => {
          if (configdata.serveraddr === "" || configdata.serveraddr === null || typeof (configdata.serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            return;
          }
          cfunc.showLoading();
          api.post(configdata.serveraddr + "/sbcmobilev2/download", { type: "downloaddoc", doc: sbc.doc, access: sbc.selDoc.access, devid: configdata.deviceid }).then(res => {
            sbc.globalFunc.lookupTableRowKey = "trno";
            sbc.globalFunc.lookupCols = [
              { name: "docno", label: "Document #", field: "docno", align: "left", sortable: true },
              { name: "dateid", label: "Date", field: "dateid", align: "left", sortable: true },
              { name: "clientname", label: "Supplier", field: "clientname", align: "left", sortable: true },
              { name: "ctr", label: "Item Count", field: "ctr", align: "center", sortable: true }
            ];
            sbc.globalFunc.lookupTableSelect = true;
            sbc.globalFunc.lookupTableSelection = "multiple";
            sbc.globalFunc.selectLookupType = "downloadDoc";
            sbc.globalFunc.lookupData = [];
            if (res.data.head.length > 0) {
              for (var x = 0; x < res.data.head.length; x++) {
                sbc.globalFunc.lookupData.push(res.data.head[x]);
              }
            }
            sbc.showSelectLookup = true;
            $q.loading.hide();
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      downloadSelectedSAPDocs: function () {
        let trnos = [];
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        for (var d in sbc.globalFunc.lookupSelected) trnos.push(sbc.globalFunc.lookupSelected[d].trno);
        cfunc.showLoading("Downloading stocks, Please wait...");
        cfunc.getTableData("config", ["deviceid", "serveraddr"], true).then(configdata => {
          api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("loadSAPStocks"), doc: sbc.doc, trno: trnos.join(), devid: configdata.deviceid, user: storage.user.username }, { headers: sbc.reqheader }).then(res => {
            saveHeads().then(() => {
              if (res.data.stocks.length > 0) {
                sbc.globalFunc.saveSAPStocks(res.data.stocks, res.data.details);
              } else {
                cfunc.showMsgBox("No stocks to save", "negative", "warning");
                sbc.showSelectLookup = false;
                sbc.modulefunc.loadTableData();
                $q.loading.hide();
              }
            });
          });
        });

        function saveHeads () {
          return new Promise((resolve) => {
            let d = sbc.globalFunc.lookupSelected;
            let dd = [];
            let docCount = sbc.globalFunc.lookupSelected.length;
            while (d.length) dd.push(d.splice(0, 100));
            cfunc.showLoading();
            save(dd);

            function save (docs, index = 0) {
              cfunc.showLoading("Saving Documents (Batch " + index + " of " + docs.length + ")");
              // if (index === 0) $q.loading.hide();
              if (index === docs.length) {
                cfunc.showLoading("Successfully imported " + docCount + " Documents");
                setTimeout(function () {
                  $q.loading.hide();
                  resolve("continue");
                }, 1500);
              } else {
                sbc.db.transaction(function (tx) {
                  for (var d in docs[index]) {
                    saveDoc(docs[index][d]);
                    if (parseInt(d) + 1 === docs[index].length) save(docs, parseInt(index) + 1);
                  }
                });
              }
            }

            function saveDoc (data) {
              sbc.db.transaction(function (tx) {
                let qry = "insert into head(trno, doc, docno, dateid, client, clientname, yourref, ourref, wh) values(?, ?, ?, ?, ?, ?, ?, ?, ?)";
                let param = [data.trno, sbc.doc, data.docno, data.dateid, data.client, data.clientname, data.yourref, data.ourref, data.wh];
                tx.executeSql("select trno from head where trno=? and doc=?", [data.trno, sbc.doc], function (tx, res) {
                  if(res.rows.length === 0) {
                    tx.executeSql(qry, param, null, function (tx, err) {
                      cfunc.saveErrLog(qry, param, err.message);
                    });
                  }                  
                });
              });
            }
          });
        }
      },
      downloadSelectedDocs: function () {
        const docbref = sbc.globalFunc.getDocBref();
        let doc = docbref.doc;
        cfunc.showLoading();
        let trnos = [];
        sbc.showSelectLookup = false;
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        for (var d in sbc.globalFunc.lookupSelected) trnos.push(sbc.globalFunc.lookupSelected[d].trno);
        cfunc.getTableData("config", ["deviceid", "serveraddr"], true).then(configdata => {
          switch (docbref.type) {
            case "RR": case "Transfer":
              sbc.globalFunc.saveProdDocHead(sbc.globalFunc.lookupSelected).then(hres => {
                contSaveDoc(configdata);
              });
              break;
            default:
              contSaveDoc(configdata);
              break;
          }
        });

        function contSaveDoc (configdata) {
          if (docbref.type !== "Entry" && docbref.type !== "Exit") {
            api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("updateProdCntnum"), doc: sbc.doc, trno: trnos.join(), devid: configdata.deviceid, user: storage.user.username }, { headers: sbc.reqheader });
          }
          api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("loadProdStocks"), access: sbc.selDoc.access, doc: sbc.doc, trno: trnos.join(), devid: configdata.deviceid }, { headers: sbc.reqheader }).then(res => {
            console.log("loadProdStocks called", res.data.stocks.length);
            if (res.data.stocks.length > 0) {
              sbc.globalFunc.saveProdStocks(res.data.stocks, trnos.join(), configdata.serveraddr, configdata.deviceid);
            } else {
              cfunc.showMsgBox("No stocks to save", "negative", "warning");
              sbc.modulefunc.loadTableData();
              $q.loading.hide();
            }
          }).catch(() => {
            $q.loading.hide();
          });
        }
      },
      saveProdDocHead: function (data) {
        return new Promise((resolve) => {
          let d = data;
          let dd = [];
          let docCount = data.length;
          while (d.length) dd.push(d.splice(0, 100));
          cfunc.showLoading();
          save(dd);

          function save (docs, index = 0) {
            cfunc.showLoading("Saving Documents (Batch " + index + " of " + docs.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === docs.length) {
              cfunc.showLoading("Successfully imported " + docCount + " Documents");
              setTimeout(function () {
                $q.loading.hide();
                resolve("continue");
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var d in docs[index]) {
                  saveDoc(docs[index][d]);
                  if (parseInt(d) + 1 === docs[index].length) save(docs, parseInt(index) + 1);
                }
              });
            }
          }

          function saveDoc (data) {
            sbc.db.transaction(function (tx) {
              let qry = "insert into head(trno, doc, bref, docno, client, clientname, wh, whname, dateid, lcno, ourref, yourref, isselected, isposted, prdno, isok, redl, ctr) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              let param = [data.trno, data.doc, data.bref, data.docno, data.client, data.clientname, data.wh, data.whname, data.dateid, data.lcno, data.ourref, data.yourref, 0, 0, data.prdno, 0, data.ctr];
              tx.executeSql(qry, param, null, function (tx, err) {
                cfunc.saveErrLog(qry, param, err.message);
              });
            });
          }
        });
      },
      saveProdStocks: function (data, trnos, serveraddr, devid) {
        console.log("saveProdStocks called");
        const docbref = sbc.globalFunc.getDocBref();
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        let doc = docbref.doc;
        let bref = docbref.bref;
        let d = data;
        let dd = [];
        let stockCount = data.length;
        while (d.length) dd.push(d.splice(0, 100));
        cfunc.showLoading();
        save(dd);

        function save (stocks, index = 0) {
          console.log("saveStock called", stocks[index]);
          cfunc.showLoading("Saving Stocks (Batch " + index + " of " + stocks.length + ")");
          if (index === 0) $q.loading.hide();
          if (index === stocks.length) {
            cfunc.showLoading("Successfully imported " + stockCount + " Stocks");
            setTimeout(function () {
              switch (docbref.type) {
                case "Entry": case "Exit":
                  api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("updateProdGLStocks"), type: docbref.type, doc: sbc.doc, devid: devid, trno: trnos, user: storage.user.username }, { headers: sbc.reqheader });
                  break;
              }
              sbc.modulefunc.loadTableData();
              $q.loading.hide();
            }, 1500);
          } else {
            sbc.db.transaction(function (tx) {
              for (var s in stocks[index]) {
                saveStock(stocks[index][s]);
                if (parseInt(s) + 1 === stocks[index].length) save(stocks, parseInt(index) + 1);
              }
            }, function (err) {
              console.log("111111111111111", err);
              $q.loading.hide();
            });
          }
        }

        function saveStock (data) {
          sbc.db.transaction(function (tx) {
            let qry = "";
            let param = [];
            switch (docbref.type) {
              case "Entry":
                qry = "insert into myentry(trno, line, docno, bref, barcode, itemname, bundleno, isentry, ismanual, scandate, frrefx, frlinex) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                param = [data.trno, data.line, data.docno, bref, data.barcode, data.itemname, data.bundleno, 0, 0, "", data.frrefx, data.frlinex];
                break;
              case "Exit":
                qry = "insert into myexit(trno, line, docno, bref, barcode, itemname, rrqty, bundleno, clientname, thickness, designation, color, coating) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                param = [data.trno, data.line, data.docno, data.bref, data.barcode, data.itemname, data.rrqty, data.bundleno, data.clientname, data.thickness, data.designation, data.color, data.coating];
                break;
              default:
                qry = "insert into stock(trno, line, barcode, itemname, itemno, itemcoilcnt, bundleno, itemlen, itemnetweight, itemgrossweight, rrqty, scannedcode, isscanned, rem, dr, ref, sorefx, solinex, frrefx, frlinex) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                param = [data.trno, data.line, data.barcode, data.itemname, data.itemno, data.itemcoilcnt, data.bundleno, data.itemlen, data.itemnetweight, data.itemgrossweight, data.rrqty, "", 0, "", "", "", "", "", data.frrefx, data.frlinex];
                break;
            }
            tx.executeSql(qry, param, function (tx, res) {
              console.log("stock saved", param);
            }, function (tx, err) {
              cfunc.saveErrLog(qry, param, err.message);
            });
          }, function (err) {
            console.log("abcdefg", err);
          });
        }
      },
      saveSAPStocks: function (data, detail = []) {
        let d = data;
        let dd = [];
        let stockCount = data.length;
        while (d.length) dd.push(d.splice(0, 100));
        cfunc.showLoading();
        save(dd);

        function save (stocks, index = 0) {
          cfunc.showLoading("Saving Stocks (Batch " + index + " of " + stocks.length + ")");
          if (index === 0) $q.loading.hide();
          if (index === stocks.length) {
            cfunc.showLoading("Successfully imported " + stockCount + " Stocks");
            setTimeout(function () {
              if (sbc.doc === "rm" || sbc.doc === "tm") {
                if (detail.length > 0) {
                  sbc.globalFunc.saveSAPDetail(detail);
                } else {
                  sbc.modulefunc.loadTableData();
                  sbc.showSelectLookup = false;
                  $q.loading.hide();
                }
              } else {
                sbc.modulefunc.loadTableData();
                sbc.showSelectLookup = false;
                $q.loading.hide();
              }
            }, 1500);
          } else {
            sbc.db.transaction(function (tx) {
              for (var s in stocks[index]) {
                saveStock(stocks[index][s]);
                if (parseInt(s) + 1 === stocks[index].length) save(stocks, parseInt(index) + 1);
              }
            });
          }
        }

        function saveStock (data) {
          sbc.db.transaction(function (tx) {
            let qry = "insert into stock(trno, line, rtrno, rline, rrrefx, rrlinex, barcode, itemname, batchcode, qty, uom, printdate, printby, iscomplete, wht, doc) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            let param = [data.trno, data.line, data.rtrno, data.rline, data.rrrefx, data.rrlinex, data.barcode, data.itemname, data.batchcode, data.qty, data.uom, data.printdate, data.printby, 0, data.wht, data.doc];
            tx.executeSql("select trno from stock where trno=? and line=? and doc=?", [data.trno, data.line, sbc.doc], function (tx, res) {
              if (res.rows.length === 0) {
                tx.executeSql(qry, param, function (tx, res) {
                  console.log("stock saved");
                }, function (tx, err) {
                  console.log("error saving stock: ", err.message);
                  cfunc.saveErrLog(qry, param, err.message);
                })
              }
            });
          }, function (err) {
            console.log("xxxxxxx", err.message);
          });
        }
      },
      saveSAPDetail: function (data) {
        let d = data;
        let dd = [];
        let dCount = data.length;
        while (d.length) dd.push(d.splice(0, 100));
        cfunc.showLoading();
        save(dd);

        function save (details, index = 0) {
          cfunc.showLoading("Saving Details (Batch " + index + " of " + details.length + ")");
          if (index === 0) $q.loading.hide();
          if (index === details.length) {
            cfunc.showLoading("Successfully imported " + dCount + " Details");
            setTimeout(function () {
              sbc.modulefunc.loadTableData();
              sbc.showSelectLookup = false;
              $q.loading.hide();
            }, 1500);
          } else {
            sbc.db.transaction(function (tx) {
              for (var d in details[index]) {
                saveDetail(details[index][d]);
                if (parseInt(d) + 1 === details[index].length) save(details, parseInt(index) + 1);
              }
            });
          }
        }

        function saveDetail (data) {
          sbc.db.transaction(function (tx) {
            tx.executeSql("insert into detail(trno, sline, rtrno, rline, rrrefx, rrlinex, pickscanneddate, pickscannedby, qtyreleased, isverified, barcode, batchcode, doc, dline) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [data.trno, data.sline, data.rtrno, data.rline, data.rrrefx, data.rrlinex, data.pickscanneddate, data.pickscannedby, data.qtyreleased, data.isverified, data.barcode, data.batchcode, data.doc, data.line], function (tx, res) {
              console.log("detail saved");
            });
          });
        }
      },
      getDocBref: function () {
        let doc = "TS";
        let doc2 = "";
        let bref = "";
        let type = "";
        switch (parseInt(sbc.selDoc.access)) {
          case 305: doc = "RR"; bref = "RRDOC"; type = "RR"; doc2 = "rr"; break;
          case 557: bref = "TSCGL"; type = "Transfer"; doc2 = "tscgl"; break;
          case 568: bref = "TSCCL"; type = "Transfer"; doc2 = "tsccl"; break;
          case 597: bref = "TSGA"; type = "Transfer"; doc2 = "tsga"; break;
          case 579: bref = "TSCBA"; type = "Transfer"; doc2 = "tscba"; break;
          case 680: bref = "TSCGL"; type = "Entry"; doc2 = "cglentry"; break;
          case 682: bref = "TSCCL"; type = "Entry"; doc2 = "cclentry"; break;
          case 684: bref = "TSGA"; type = "Entry"; doc2 = "gaentry"; break;
          case 686: bref = "TSCBA"; type = "Entry"; doc2 = "cbaentry"; break;
          case 681: bref = "TSCGL"; type = "Exit"; doc2 = "cglexit"; break;
          case 683: bref = "TSCCL"; type = "Exit"; doc2 = "cclexit"; break;
          case 685: bref = "TSGA"; type = "Exit"; doc2 = "gaexit"; break;
          case 687: bref = "TSCBA"; type = "Exit"; doc2 = "cbaexit"; break;
          case 667: bref = "TSDP"; type = "Transfer"; doc2 = "dispatch"; break;
          case 677: bref = "TSDP"; type = "Entry"; doc2 = "dispatchexit"; break;
        }
        return { doc: doc, bref: bref, type: type, doc2: doc2 };
      },
      viewSAPDoc: function (row, index) {
        cfunc.showLoading();
        sbc.globalFunc.cLookupRowsPerPage = 20;
        sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, "docLookupTableCols", "inputPlot");
        sbc.selclookuptablebuttons = sbc.globalFunc.getLookupForm(sbc.clookuptablebuttons, "docLookupTableButtons", "buttons");
        sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, "docLookupTableCols", "inputFields");
        sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "docLookupHeadFields", "inputFields");
        sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "docLookupHeadFields", "inputPlot");
        if (sbc.selclookuptablebuttons.length > 0) {
          sbc.selclookuptablecols.unshift({ doc: sbc.doc, field: "", form: "docLookupTableCols", label: "Actions", name: "actions", show: null, sortable: null, align: "center" });
        }
        sbc.modulefunc.cLookupFooterButtonsFab = true;
        sbc.modulefunc.txtSearchStock = "";
        sbc.modulefunc.cLookupTableFilter = { type: "searchStock", field: "cLookupForm.searchstock", label: "Search", func: "" };
        sbc.selclookupbuttons = sbc.globalFunc.getLookupForm(sbc.clookupbuttons, "docLookupButtons", "buttons");
        sbc.globalFunc.customLookupGrid = true;
        sbc.cLookupTitle = "Doc num: " + row.ourref;
        sbc.isFormEdit = true;
        if (sbc.doc !== "rm" && sbc.doc !== "tm") {
          sbc.globalFunc.cLookupSelect = true;
          sbc.globalFunc.cLookupSelected = [];
          sbc.globalFunc.cLookupSelection = "multiple";
          sbc.globalFunc.cLookupSelectRowKey = "line";
          sbc.globalFunc.cLookupTableClick = true;
          sbc.globalFunc.cLookupTableClickFunc = "selectSAPCode";
          sbc.globalFunc.cLookupTableClickFunctype = "global";
        } else {
          sbc.modulefunc.cLookupFooterButtonsLeftAlign = false;
          sbc.globalFunc.cLookupTableClick = true;
          sbc.globalFunc.cLookupTableClickFunc = "selectRMStock";
          sbc.globalFunc.cLookupTableClickFunctype = "global";
        }
        sbc.globalFunc.cLookupMaximized = true;
        sbc.modulefunc.cLookupForm = { trno: row.trno, searchstock: "", row: row, wht: "" };
        sbc.globalFunc.loadSAPStocks(row).then(() => {
          sbc.showCustomLookup = true;
        });
      },
      loadSAPStocks: function (row) {
        return new Promise((resolve) => {
          sbc.db.transaction(function (tx) {
            let qry = "select * from stock where trno=? and doc=?";
            let data = [row.trno, sbc.doc];
            sbc.modulefunc.detailsList = [];
            switch (sbc.doc) {
              case "rl": case "tr": case "dr":
                data = [sbc.doc, "Yes", "No", "bg-blue-2", "", row.trno, sbc.doc];
                tx.executeSql("select * from detail where trno=? and uploaded is null and doc=?", [row.trno, sbc.doc], function (tx, resd) {
                  let details = [];
                  if (resd.rows.length > 0) {
                    for (var x = 0; x < resd.rows.length; x++) {
                      details.push(resd.rows.item(x));
                      if (parseInt(x) + 1 === resd.rows.length) sbc.modulefunc.detailsList = details;
                    }
                  }
                });
                qry = "select stock.*, ifnull((select sum(d.qtyreleased) as qtyreleased from detail as d where d.trno=stock.trno and d.sline=stock.line and d.doc=?), 0) as qtyreleased, case when stock.printdate is not null then ? else ? end as isprinted, case stock.iscomplete when 1 then ? else ? end as bgColor from stock where stock.trno=? and stock.uploaded is null and stock.doc=? ";
                tx.executeSql(qry, data, function (tx, res) {
                  sbc.modulefunc.lookupTableData = [];
                  if (res.rows.length > 0) {
                    for (var x = 0; x < res.rows.length; x++) {
                      sbc.modulefunc.lookupTableData.push(res.rows.item(x));
                      sbc.modulefunc.lookupTableData[x].qtyreleased = parseFloat(parseFloat(res.rows.item(x).qtyreleased).toFixed(4));
                    }
                  }
                  resolve()
                  $q.loading.hide();
                }, function (tx, err) {
                  console.log("errwaw: ", err.message);
                  $q.loading.hide();
                });
                break;
              case "rm": case "tm":
                tx.executeSql("select * from detail where trno=? and uploaded is null and doc=?", [row.trno, sbc.doc], function (tx, res) {
                  if (res.rows.length > 0) {
                    let details = [];
                    let dd = [];
                    for (var d = 0; d < res.rows.length; d++) {
                      details.push(res.rows.item(d));
                      dd.push(res.rows.item(d).sline);
                      data = [sbc.doc];
                      if (parseInt(d) + 1 === res.rows.length) {
                        qry = "select * from stock where trno=" + row.trno + " and line in (" + dd.join(",") + ") and doc=? ";
                        sbc.modulefunc.detailsList = details;
                        tx.executeSql(qry, data, function (tx, res3) {
                          sbc.modulefunc.lookupTableData = [];
                          if (res3.rows.length > 0) {
                            for (var x = 0; x < res3.rows.length; x++) {
                              sbc.modulefunc.lookupTableData.push(res3.rows.item(x));
                            }
                          }
                          resolve()
                          $q.loading.hide();
                        }, function (tx, err) {
                          console.log("errwaw: ", err.message);
                          $q.loading.hide();
                        });
                      }
                    }
                  }
                });
                break;
              default:
                tx.executeSql(qry, data, function (tx, res) {
                  sbc.modulefunc.lookupTableData = [];
                  if (res.rows.length > 0) {
                    for (var x = 0; x < res.rows.length; x++) {
                      sbc.modulefunc.lookupTableData.push(res.rows.item(x));
                    }
                  }
                  resolve()
                  $q.loading.hide();
                }, function (tx, err) {
                  console.log("errwaw: ", err.message);
                  $q.loading.hide();
                });
                break;
            }
          }, function (err) {
            console.log("loadsapstocks error: ", err.message);
          });
        });
      },
      viewDoc: function (row, index) {
        const docbref = sbc.globalFunc.getDocBref();
        switch (docbref.type) {
          case "Entry":
            $q.dialog({
              message: "Are you sure you want to manually tag this item?",
              ok: { flat: true, color: "primary" },
              cancel: { flat: true, color: "negative" }
            }).onOk(() => {
              sbc.globalFunc.manualUpdateItem(row);
            });
            break;
          case "Exit":
            console.log("clickDoc: ", row);
            if (docbref.bref === "TSGA") sbc.modulefunc.docForm.mass = row.coating;
            sbc.modulefunc.docForm.docno = row.docno;
            sbc.modulefunc.docForm.dateid = row.dateid;
            sbc.modulefunc.docForm.clientname = row.clientname;
            sbc.modulefunc.docForm.remaining = "";
            sbc.modulefunc.docForm.mass = "";
            sbc.modulefunc.docForm.weight = "";
            sbc.modulefunc.docForm.len = "";
            sbc.modulefunc.docForm.prd = "";
            sbc.modulefunc.docForm.qty = "";
            sbc.modulefunc.docForm.scsono = "";
            sbc.modulefunc.docForm.custname = "";
            sbc.modulefunc.docForm.dpr = "";
            sbc.modulefunc.docForm.rem = "";
            sbc.modulefunc.docForm.width = "";
            sbc.modulefunc.docForm.fgtag = "";
            sbc.modulefunc.cLookupForm = sbc.modulefunc.docForm;

            sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "exitStockLookupFields", "inputFields");
            sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "exitStockLookupFields", "inputPlot");
            sbc.selclookupbuttons = sbc.globalFunc.getLookupForm(sbc.clookupbuttons, "exitStockLookupButtons", "buttons");

            sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, "exitLookupTableCols", "inputFields");
            sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, "exitLookupTableCols", "inputPlot");
            sbc.showCustomLookup = true;
            sbc.cLookupTitle = "Coil No.: " + row.bundleno;
            sbc.isFormEdit = true;

            sbc.globalFunc.selDocProd = row;
            sbc.globalFunc.readonlyExit = false;
            if (row.isexit === 1) sbc.globalFunc.readonlyExit = true;
            sbc.globalFunc.loadExitDatasProd();
            break;
          default:
            sbc.modulefunc.docForm.docno = row.docno;
            sbc.modulefunc.docForm.dateid = row.dateid;
            sbc.modulefunc.docForm.clientname = row.clientname;
            sbc.modulefunc.docForm.lcno = row.lcno;
            sbc.modulefunc.docForm.scanstat = "0";
            sbc.modulefunc.docForm.searchcoil = "";
            sbc.modulefunc.cLookupForm = sbc.modulefunc.docForm;
            cfunc.showLoading();
            sbc.cLookupTitle = sbc.globalFunc.company;
            sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, docbref.doc2 + "docLookupFields", "inputFields");
            sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, docbref.doc2 + "docLookupFields", "inputPlot");
            sbc.selclookupheadbuttons = sbc.globalFunc.getLookupForm(sbc.clookupheadbuttons, docbref.doc2 + "docLookupHeadButtons", "buttons");
            sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, docbref.doc2 + "docLookupTableCols", "inputFields");
            sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, docbref.doc2 + "docLookupTableCols", "inputPlot");
            sbc.selclookuptablebuttons = sbc.globalFunc.getLookupForm(sbc.clookuptablebuttons, docbref.doc2 + "docLookupTableButtons", "buttons");
            sbc.showCustomLookup = true;
            console.log("qweasdzxc", docbref.doc2);
            sbc.globalFunc.selDocProd = row;
            sbc.modulefunc.loadDocStocksProd();
            break;
        }
      },
      loadExitDatasProd: function () {
        sbc.db.transaction(function (tx) {
          let sql = "select ed.myline, ed.trno, ed.line, ed.bundleno, ed.barcode, item.groupid, item.thickness, item.width, item.class, ed.strshift, ed.strtype, ed.designation, ed.coating, ed.weight, item.itemname, ed.color, ed.paintcode, ed.length, ed.uom, ed.prd, ed.qty, ed.sc, ed.clientname, ed.fg from myexitdata as ed left join item on item.barcode=ed.barcode where ed.trno=? and ed.line=?";
          let data = [sbc.globalFunc.selDocProd.trno, sbc.globalFunc.selDocProd.line];
          tx.executeSql(sql, data, function (tx, res) {
            console.log("111111111111111", res.rows.length);
            sbc.modulefunc.lookupTableData = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.modulefunc.lookupTableData.push(res.rows.item(x));
              }
            }
          }, function (tx, err ){
            console.log("1111111111111111", err);
            cfunc.showMsgBox("Error loading exit data, Please try again.", "negative", "warning");
          });
        })
      },
      manualUpdateItem: function (row) {
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        if (row.isentry === 1 || row.isentry === "1") {
          cfunc.showMsgBox("Item already scanned.", "negative", "warning");
          return;
        }
        cfunc.showLoading();
        let datenow = cfunc.getDateTime("datetime");
        cfunc.getTableData("config", "serveraddr").then(serveraddr => {
          api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("updateProdScanned"), stype: "manual", trno: row.trno, line: row.line, date: datenow, user: storage.user.username }, { headers: sbc.reqheader }).then(res => {
            cfunc.globalFunc.deleteProdEntry(row.trno, row.line).then(res => {
              cfunc.showMsgBox("Item updated", "positive");
              $q.loadig.hide();
              sbc.modulefunc.loadTableData();
            }).catch(err => {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
              sbc.modulefunc.loadTableData();
            });
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
            sbc.globalFunc.contUpdateItemOfflineProd(row.trno, row.line, "manual");
          });
        });
      },
      contUpdateItemOfflineProd: function (trno, line, type = "scan") {
        const datenow = cfunc.getDateTime("datetime");
        let manual = "";
        if (type === "manual") manual = ", ismanual=1";
        sbc.globalFunc.scanType = type;
        sbc.db.transaction(function (tx) {
          tx.executeSql("update myentry set isentry=1, scandate=?" + manual + " where trno=? and line=?", [datenow, trno, line], function (tx, res) {
            cfunc.showMsgBox("Item updated locally", "positive");
            $q.loading.hide();
            sbc.modulefunc.loadTableData();
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      clickStockProd: function (row, type = "manual") {
        const docbref = sbc.globalFunc.getDocBref();
        if (row.isscanned !== 1 && row.rem === "") {
          sbc.globalFunc.stockLookupType = type;
          sbc.modulefunc.inputLookupForm = { trno: row.trno, line: row.line, bundleno: row.bundleno, itemname: row.itemname, itemnetweight: row.itemnetweight, itemgrossweight: row.itemgrossweight, drno: row.dr, rem: row.rem };
          sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, docbref.doc2 + "stockLookupFields", "inputFields");
          sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, docbref.doc2 + "stockLookupFields", "inputPlot");
          sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, docbref.doc2 + "stockLookupButtons", "buttons");
          sbc.isFormEdit = true;
          if (sbc.selinputlookupfields.length > 0) {
            for (var x = 0; x < sbc.selinputlookupfields.length; x++) {
              switch (sbc.selinputlookupfields[x].name) {
                case "bundleno": case "itemname": case "itemnetweight": case "itemgrossweight":
                  sbc.selinputlookupfields[x].show = "false";
                  break;
              }
            }
          }
          sbc.inputLookupTitle = row.itemname;
          sbc.showInputLookup = true;
        } else {
          console.log("isscanned is 1 and rem not null");
        }
      },
      scanItemProd: function () {
        sbc.isScan = true;
        QRScanner.scan(displayContents);
        function displayContents (err, text) {
          if (err) {
            console.log("an error occurred, or the scan was canceled (error code `6`)");
          } else {
            console.log("The scan completed, display the contents of the QR code: ", text);
            sbc.globalFunc.searchItemProd(text, "scan");
          }
        }

        // Make the webview transparent so the video preview is visible behind it.
        QRScanner.show();
        // Be sure to make any opaque HTML elements transparent here to avoid
        // covering the video.
      },
      searchItemProd: function (code, type = "manual") {
        let str = code;
        let docbref = sbc.globalFunc.getDocBref();
        if (type === "manual") {
          if (code === "") str = sbc.globalFunc.selCode;
        }
        cfunc.showLoading();
        if (sbc.modulefunc.lookupTableData.length > 0) {
          let found = false;
          switch (docbref.type) {
            case "RR":
              cfunc.getTableData("stock", ["trno", "line", "itemname", "itemnetweight", "itemgrossweight", "bundleno", "isscanned", "rem"], true, [{ field: "trno", value: sbc.globalFunc.selDocProd.trno }, { field: "bundleno", value: str }]).then(stock => {
                itemSearched(stock);
              });
              break;
            case "Transfer": case "Entry":
              let codeData = code.split(";");
              let bundleno = codeData[0];
              let weight = codeData[1];
              let trno = codeData[2];
              let line = codeData[3];
              if (typeof(bundleno) === "undefined") bundleno = "";
              if (typeof(weight) === "undefined") weight = "";
              if (typeof(trno) === "undefined") trno = "";
              if (typeof(line) === "undefined") line = "";
              if (docbref.doc === "TS") {
                if (sbc.globalFunc.selDocProd.trno !== parseInt(trno)) {
                  cfunc.showMsgBox("Item scanned is not in this document", "negative", "warning");
                  return;
                }
                cfunc.getTableData("stock", ["trno", "line", "itemname", "itemnetweight", "itemgrossweight", "bundleno", "isscanned", "rem"], true, [{ field: "bundleno", value: bundleno }, { field: "itemnetweight", value: weight }, { field: "trno", value: trno }, { field: "line", value: line }]).then(stock => {
                  itemSearched(stock);
                });
              } else {
                cfunc.getTableData("myentry", ["trno", "line", "itemnetweight", "bundleno", "isentry", "ismanual"], true, [{ field: "bundleno", value: bundleno }, { field: "trno", value: trno }, { field: "line", value: line }]).then(entry => {
                    itemSearched(entry);
                  });
              }
              break;
          }
        } else {
          cfunc.showMsgBox("No record found", "negative", "warning");
        }

        function itemSearched (stock) {
          console.log("itemSearched ", stock);
          if (stock.line !== "" && stock.line !== null && typeof(stock.line) !== "undefined" && stock.line !== 0) {
            let scanned = 0;
            switch (docbref.type) {
              case "Transfer": case "RR":
                scanned = stock.isscanned;
                break;
              case "Entry": scanned = stock.isentry; break;
            }
            if (scanned === 1) {
              cfunc.showMsgBox("Item is already scanned", "negative", "warning");
              $q.loading.hide();
            } else {
              switch (docbref.type) {
                case "Transfer": case "RR":
                  if (stock.rem !== "") {
                    cfunc.showMsgBox("Item already tagged as with issue", "negative", "warning");
                    return;
                  }
                  contFunc(stock);
                  break;
                case "Entry":
                  contFunc(stock);
                  break;
              }
            }
          } else {
            if (type === "scan") {
              sbc.modulefunc.inputLookupForm = { codenotlocate: str, searchcode: "" };
              sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, docbref.doc2 + "codeNotLocatedFields", "inputFields");
              sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, docbref.doc2 + "codeNotLocatedFields", "inputPlot");
              sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, docbref.doc2 + "codeNotLocatedButtons", "buttons");
              console.log("selInputLookupFields: ", sbc.selinputlookupfields);
              sbc.isFormEdit = true;
              sbc.inputLookupTitle = "Item not found";
              sbc.showInputLookup = true;
              $q.loading.hide();
            } else {
              sbc.showInputLookup = false;
              $q.loading.hide();
              cfunc.showMsgBox("No record found", "negative", "warning");
            }
          }
        }

        function contFunc (stock) {
          let datenow = cfunc.getDateTime("datetime");
          switch (docbref.type) {
            case "RR":
              sbc.globalFunc.selStockProd = stock;
              sbc.globalFunc.clickStockProd(stock, type);
              // showstockinfo = true;
              // stocklookuptype = type;
              break;
            case "Transfer":
              sbc.db.transaction(function (tx) {
                tx.executeSql("update stock set isscanned=1 where trno=? and line=? and bundleno=? and itemnetweight=?", [stock.trno, stock.line, stock.bundleno, stock.itemnetweight], function (tx, res) {
                  if (res.rowsAffected === 1) {
                    cfunc.showMsgBox("Item scanned", "positive");
                    sbc.modulefunc.loadDocStocksProd();
                  }
                });
              });
              break;
            case "Entry":
              break;
          }
        }
      },
      uploadDocProd: function () {
        console.log("uploadDocProd called");
        let qry = "";
        let data = [];
        const docbref = sbc.globalFunc.getDocBref();
        console.log("zzzzzzzzzzzzzzz", docbref);
        sbc.db.transaction(function (tx) {
          let stocks = [];
          cfunc.showLoading();
          switch (docbref.type) {
            case "RR": case "Transfer":
              qry = "select trno, line, isscanned, rem, dr from stock where trno=? and (isscanned=1 or rem<>?)";
              data = [sbc.globalFunc.selDocProd.trno, ""];
              break;
            case "Entry":
              qry = "select trno, line, isentry, ismanual, scandate from myentry where isentry=1";
              break;
            case "Exit":
              qry = "select * from myexit where isexit=1";
              break;
          }
          tx.executeSql(qry, data, function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                stocks.psuh(res.rows.item(x));
                if (parseInt(x) + 1 === res.rows.length) {
                  getOtherDataProd(stocks);
                }
              }
            } else {
              getOtherDataProd();
            }
          }, function (tx, err) {
            console.log(err);
          });
        });

        function getOtherDataProd (stocks = []) {
          console.log("getOtherDataProd called");
          let addeddata = [];
          switch (docbref.type) {
            case "Exit":
              if (stocks.length > 0) {
                sbc.db.transaction(function (tx) {
                  $q.loading.hide();
                  let addeds = "";
                  for (var s in stocks) {
                    if (addeds === "") {
                      addeds = " trno=" + stocks[s].trno + " and line=" + stocks[s].line;
                    } else {
                      addeds += " or trno=" + stocks[s].trno + " and line=" + stocks[s].line;
                    }
                  }
                  tx.executeSql("select * from myexitdata where " + addeds, [], function (tx, res) {
                    if (res.rows.length > 0) {
                      for (var x = 0; x < res.rows.length; x++) {
                        addeddata.push(res.rows.item(x));
                        if (parseInt(x) + 1 === res.rows.length) {
                          upoadDocStock(stocks, addeddata);
                        }
                      }
                    } else {
                      uploadDocStock(stocks);
                    }
                  });
                });
              } else {
                cfunc.showMsgBox("No record to upload", "negative", "warning");
                $q.loading.hide();
              }
              break;
            default:
              uploadDocStock(stocks);
              break;
          }
        }

        function uploadDocStock (stocks, addeddata = []) {
          console.log("uploadDocStock called");
          $q.loading.hide();
          $q.dialog({
            message: "Are you sure you want to upload?",
            ok: { flat: true, color: "primary", label: "Yes" },
            cancel: { flat: true, color: "negative", label: "No" }
          }).onOk(() => {
            cfunc.showLoading();
            cfunc.getTableData("config", ["serveraddr", "deviceid", "username"], true).then(configdata => {
              api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("uploadDocStocksProd"), doc: docbref.doc, access: sbc.selDoc.access, stocks: stocks, user: configdata.username, addeddata: addeddata, selDoc: sbc.globalFunc.selDocProd, devid: configdata.deviceid }, { headers: sbc.reqheader }).then(res => {
                console.log(res.data);
                removeDocStocksProd(stocks);
              }).catch(err => {
                cfunc.showMsgBox(err.message, "negative", "warning");
                $q.loading.hide();
              });
            });
          });
        }

        function removeDocStocksProd (stock) {
          console.log("removeDocStocksProd called");
          sbc.db.transaction(function (tx) {
            switch (docbref.type) {
              case "RR":
                tx.executeSql("delete from head where trno=?", [sbc.globalFunc.selDocProd.trno], function (tx, res) {
                  tx.executeSql("delete from stock where trno=?", [sbc.globalFunc.selDocProd.trno], function (tx, res) {
                    sbc.showCustomLookup = false;
                    $q.loading.hide();
                    cfunc.showMsgBox("Transaction has been uploaded", "positive");
                    sbc.modulefunc.loadTableData();
                  }, function (tx, err) {
                    console.log("removeDocStocksProd Error#1: ", err);
                  });
                }, function (tx, err) {
                  console.log("removeDocStocksProd Error#2: ", err);
                });
                break;
              case "Transfer":
                for (var s in stock) {
                  tx.executeSql("delete from stock where trno=? and line=?", [sbc.globalFunc.selDocProd.trno, stock[s].line], function (tx, res) {
                    if (parseInt(s) + 1 === stocks.length) {
                      cfunc.getTableDataCount("stock", [{ field: "trno", value: sbc.globalFunc.selDocProd.trno }]).then(scount => {
                        if (scount === 0) {
                          removeHead();
                        } else {
                          sbc.showCustomLookup = false;
                          $q.loading.hide();
                          cfunc.showMsgBox("Item(s) uploaded", "positive");
                          sbc.modulefunc.loadTableData();
                        }
                      });
                    }
                  });
                }
                break;
              case "Entry":
                for (var e in stock) {
                  tx.executeSql("delete from myentry where trno=? and line=?", [stock[e].trno, stock[e].line], function (tx, res) {
                    if (parseInt(e) + 1 === stock.length) {
                      sbc.showCustomLookup = false;
                      $q.loading.hide();
                      cfunc.showMsgBox("Item(s) uploaded", "positive");
                      sbc.modulefunc.loadTableData();
                    }
                  });
                }
                break;
              case "Exit":
                for (var ex in stock) {
                  tx.executeSql("delete from myexit where trno=? and line=?", [stock[ex].trno, stock[ex].line])
                  tx.executeSql("delete from myexitdata where trno=? and line=?", [stock[ex].trno, stock[ex].line])
                  if (parseInt(ex) + 1 == stock.length) {
                    sbc.showCustomLookup = false;
                    cfunc.showMsgBox("Item(s) uploaded", "positive");
                    $q.loading.hide();
                    sbc.modulefunc.loadTableData();
                  }
                }
                break;
            }
          }, function (err) {
            console.log("removeDocStocksProd Error#3: ", err);
          });
        }

        function removeHead () {
          sbc.db.transaction(function (tx) {
            tx.executeSql("delete from head where trno=?", [sbc.globalFunc.selDocProd.trno], function (tx, res) {
              sbc.showCustomLookup = false;
              $q.loading.hide();
              cfunc.showMsgBox("Transaction has been uploaded", "positive");
              sbc.modulefunc.loadTableData();
            }, function (tx, err) {
              console.load(err);
            });
          });
        }
      },
      submitDR: function () {
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        const datenow = cfunc.getDateTime("datetime");
        if (sbc.globalFunc.stockLookupType === "manual") {
          if (sbc.modulefunc.inputLookupForm.rem === "") {
            cfunc.showMsgBox("Please enter issue remarks", "negative", "warning");
            return;
          }
        }
        if (parseInt(sbc.selDoc.access) === 305) {
          if (sbc.modulefunc.inputLookupForm.dr === "") {
            cfunc.showMsgBox("Please enter DR #", "negative", "warning");
            return;
          }
        }
        let qry = "";
        let data = [];
        const docbref = sbc.globalFunc.getDocBref();
        switch (sbc.globalFunc.stockLookupType) {
          case "manual":
            switch (docbref.type) {
              case "RR":
                qry = "update stock set rem=?, dr=? where trno=? and line=?";
                data = [sbc.modulefunc.inputLookupForm.rem, sbc.modulefunc.inputLookupForm.dr, sbc.modulefunc.inputLookupForm.trno, sbc.modulefunc.inputLookupForm.line];
                break;
              case "Transfer":
                qry = "update stock set rem=? where trno=? and line=?";
                data = [sbc.modulefunc.inputLookupForm.rem, sbc.modulefunc.inputLookupForm.trno, sbc.modulefunc.inputLookupForm.line];
                break;
            }
            break;
          case "scan":
            switch (docbref.type) {
              case "RR":
                qry = "update stock set isscanned=1, dr=? where trno=? and line=?";
                data = [sbc.modulefunc.inputLookupForm.dr, sbc.modulefunc.inputLookupForm.trno, sbc.modulefunc.inputLookupForm.line];
                break;
              case "Transfer":
                qry = "update stock set isscanned=1 where trno=? and line=?";
                data = [sbc.modulefunc.inputLookupForm.trno, sbc.modulefunc.inputLookupForm.line];
                break;
            }
            break;
        }
        sbc.db.transaction(function (tx) {
          tx.executeSql(qry, data, function (tx, res) {
            if (res.rowsAffected > 0) {
              tx.executeSql("update head set scanneddate=?, scannedby=? where trno=?", [datenow, storage.user.username, sbc.modulefunc.inputLookupForm.trno]);
              // sbc.selDoc.scanneddate = datenow;
              switch (docbref.type) {
                case "RR":
                  sbc.modulefunc.uploadRRStock();
                  break;
                case "Transfer":
                  cfunc.showMsgBox("Record manually tagged", "positive");
                  sbc.modulefunc.inputLookupForm.rem = "";
                  sbc.modulefunc.inputLookupForm.dr = "";
                  break;
              }
              sbc.showInputLookup = false;
              sbc.modulefunc.loadDocStocksProd();
            }
          });
        });
      },
      cancelDR: function () {
        sbc.modulefunc.inputLookupForm = [];
        sbc.showInputLookup = false;
      },
      addExitDataProd: function () {
        if (sbc.modulefunc.cLookupForm.itemid === 0 || sbc.modulefunc.cLookupForm.itemid === null || typeof (sbc.modulefunc.cLookupForm.itemid) === "undefined") {
          cfunc.showMsgBox("Please select item first", "negative", "warning");
          return;
        }
        const exitdata = sbc.modulefunc.cLookupForm;
        if (exitdata.shiftt === "" || exitdata.shiftt === null || typeof (exitdata.shiftt) === "undefined") {
          cfunc.showMsgBox("Please select shift", "negative", "warning");
          return;
        }
        if (exitdata.seltype === "" || exitdata.seltype === null || typeof (exitdata.seltype) === "undefined") {
          cfunc.showMsgBox("Please select type", "negative", "warning");
          return;
        }
        if (exitdata.designation === "" || exitdata.designation === null || typeof (exitdata.designation) === "undefined") {
          cfunc.showMsgBox("Please select designation", "negative", "warning");
          return;
        }
        if (exitdata.mass === "" || exitdata.mass === null || typeof (exitdata.mass) === "undefined") {
          cfunc.showMsgBox("Please enter coating mass", "negative", "warning");
          return;
        }
        if (sbc.selDoc.access === "683") {
          if (exitdata.paintsupp === "" || exitdata.paintsupp === null || typeof (exitdata.paintsupp) === "undefined") {
            cfunc.showMsgBox("Please select paint supplier", "negative", "warning");
            return;
          }
        }
        if (sbc.selDoc.access === "685" || sbc.selDoc.access === "687") {
          if (exitdata.weight === "" || exitdata.weight === null || typeof (exitdata.weight) === "undefined") {
            cfunc.showMsgBox("Please enter weight", "negative", "warning");
            return;
          }
          if (exitdata.len === "" || exitdata.len === null || typeof (exitdata.len) === "undefined") {
            cfunc.showMsgBox("Please enter length", "negative", "warning");
            return;
          }
          if (exitdata.prd === "" || exitdata.prd === null || typeof (exitdata.prd) === "undefined") {
            cfunc.showMsgBox("Please enter PRD no.", "negative", "warning");
            return;
          }
          if (exitdata.qty === "" || exitdata.qty === null || typeof (exitdata.qty) === "undefined") {
            cfunc.showMsgBox("Please enter quantity", "negative", "warning");
            return;
          }
        }
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          let sql = "";
          let data = [];
          const docbref = sbc.globalFunc.getDocBref();
          switch (docbref.bref) {
            case "TSCGL":
              sql = "insert into myexitdata(trno, line, bundleno, barcode, strshift, strtype, designation, coating, weight) values(?, ?, ?, ?, ?, ?, ?, ?, ?)";
              data = [sbc.globalFunc.selDocProd.trno, sbc.globalFunc.selDocProd.line, sbc.globalFunc.selDocProd.bundleno, sbc.modulefunc.cLookupForm.barcode, sbc.modulefunc.cLookupForm.shiftt, sbc.modulefunc.cLookupForm.seltype, sbc.modulefunc.cLookupForm.designation, sbc.modulefunc.cLookupForm.mass, sbc.modulefunc.cLookupForm.weight];
              break;
            case "TSCCL":
              sql = "insert into myexitdata(trno, line, bundleno, barcode, strshift, strtype, designation, coating, weight, color, paintcode) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              data = [sbc.globalFunc.selDocProd.trno, sbc.globalFunc.selDocProd.line, sbc.globalFunc.selDocProd.bundleno, sbc.modulefunc.cLookupForm.barcode, sbc.modulefunc.cLookupForm.shiftt, sbc.modulefunc.cLookupForm.seltype, sbc.modulefunc.cLookupForm.designation, sbc.modulefunc.cLookupForm.mass, sbc.modulefunc.cLookupForm.weight, sbc.modulefunc.cLookupForm.color, sbc.modulefunc.cLookupForm.paintsuppcode];
              break;
            case "TSGA":
              sql = "insert into myexitdata(trno, line, bundleno, barcode, strshift, strtype, designation, coating, color, length, uom, prd, qty, sc, clientname, fb) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              data = [sbc.globalFunc.selDocProd.trno, sbc.globalFunc.selDocProd.line, sbc.globalFunc.selDocProd.bundleno, sbc.modulefunc.cLookupForm.barcode, sbc.modulefunc.cLookupForm.shiftt, sbc.modulefunc.cLookupForm.seltype, sbc.modulefunc.cLookupForm.designation, sbc.modulefunc.cLookupForm.mass, sbc.modulefunc.cLookupForm.color, sbc.modulefunc.cLookupForm.len, sbc.modulefunc.cLookupForm.lenu, sbc.modulefunc.cLookupForm.prd, sbc.modulefunc.cLookupForm.qty, sbc.modulefunc.cLookupForm.scsono, sbc.modulefunc.cLookupForm.custname, sbc.modulefunc.cLookupForm.fgtag];
              break;
            case "TSCBA":
              sql = "insert into myexitdata(trno, line, bundleno, barcode, strshift, strtype, designation, coating, color, length, uom, prd, qty, sc, clientname, consumed, dpr, remarks, weight) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              data = [sbc.globalFunc.selDocProd.trno, sbc.globalFunc.selDocProd.line, sbc.globalFunc.selDocProd.bundleno, sbc.modulefunc.cLookupForm.barcode, sbc.modulefunc.cLookupForm.shiftt, sbc.modulefunc.cLookupForm.seltype, sbc.modulefunc.cLookupForm.designation, sbc.modulefunc.cLookupForm.mass, sbc.modulefunc.cLookupForm.color, sbc.modulefunc.cLookupForm.len, sbc.modulefunc.cLookupForm.lenu, sbc.modulefunc.cLookupForm.prd, sbc.modulefunc.cLookupForm.qty, sbc.modulefunc.cLookupForm.scsono, sbc.modulefunc.cLookupForm.custname, sbc.modulefunc.cLookupForm.remaining, sbc.modulefunc.cLookupForm.dpr, sbc.modulefunc.cLookupForm.rem, sbc.modulefunc.cLookupForm.weight];
              break;
          }
          tx.executeSql(sql, data, function (tx, res) {
            cfunc.showMsgBox("Item added", "positive");
            switch (docbref.bref) {
              case "TSCGL": sbc.modulefunc.cLookupForm.mass = ""; break;
              case "TSGA":
                sbc.modulefunc.cLookupForm.len = "";
                sbc.modulefunc.cLookupForm.qty = "";
                sbc.modulefunc.cLookupForm.prd = "";
                sbc.modulefunc.cLookupForm.scsono = "";
                sbc.modulefunc.cLookupForm.custname = "";
                sbc.modulefunc.cLookupForm.fgtag = "";
                break;
              case "TSCBA":
                  sbc.modulefunc.cLookupForm.len = "";
                  sbc.modulefunc.cLookupForm.qty = "";
                  sbc.modulefunc.cLookupForm.rem = "";
                break;
            }
            sbc.modulefunc.cLookupForm.weight = "";
            sbc.globalFunc.loadExitDatasProd();
            $q.loading.hide();
          }, function (tx, err) {
            console.log("error1: ", err);
            $q.loading.hide();
          });
        }, function (err) {
          console.log("error2: ", err);
          $q.loading.hide();
        });
      },
      submitExitProd: function () {
        cfunc.showLoading();
        const datenow = cfunc.getDateTime("datetime");
        cfunc.getTableData("config", ["deviceid", "serveraddr", "username"], true).then(configdata => {
          api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("uploadExitDataProd"), doc: sbc.selDoc.name, access: sbc.selDoc.access, data: sbc.modulefunc.lookupTableData, selDoc: sbc.globalFunc.selDocProd, user: configdata.username, devid: configdata.deviceid }, { headers: sbc.reqheader }).then(res => {
            sbc.db.transaction(function (tx) {
              tx.executeSql("delete from myexit where trno=? and line=?", [sbc.globalFunc.selDocProd.trno, sbc.globalFunc.selDocProd.line]);
              tx.executeSql("delete from myexitdata where trno=? and line=?", [sbc.globalFunc.selDocProd.trno, sbc.globalFunc.selDocProd.line]);
              cfunc.showMsgBox("Item Uploaded", "positive");
              sbc.showCustomLookup = false;
              sbc.modulefunc.loadTableData();
            });
            $q.loading.hide();
          }).catch(err => {
            cfunc.showMsgBox("Error uploading, Saving locally", "negative", "warning");
            sbc.db.transaction(function (tx) {
              tx.executeSql("update myexit set isexit=1, exitdate=? where trno=? and line=?", [datenow, sbc.globalFunc.selDocProd.trno, sbc.globalFunc.selDocProd.line], function (tx, res) {
                sbc.modulefunc.loadTableData();
                sbc.showCustomLookup = false;
              });
            });
            $q.loading.hide();
          });
        });
      },
      cancelExitProd: function () {
        sbc.showCustomLookup = false;
      },
      downloadUserImages: function () {
        console.log("downloadUserImages called");
        let uiCount = 0;
        let uiData = [];
        cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
          if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            $q.loading.hide();
            return;
          }
          cfunc.showLoading();
          api.post(serveraddr + "/sbcmobilev2/download", { type: "timeinUserImages" }).then(res => {
            if (res.data.images.length > 0) {
              uiCount = res.data.images.length;
              uiData = res.data.images;
              let d = uiData;
              let dd = [];
              while (d.length) {
                dd.push(d.splice(0, 100));
              }
              saveDownloadedUserImages(dd);
            } else {
              cfunc.showMsgBox("No image(s) to download", "negative", "warning");
              $q.loading.hide();
            }
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });

        function saveDownloadedUserImages (data, index = 0) {
          cfunc.showLoading("Saving Images data (" + index + " of " + data.length + ")");
          if (index === 0) $q.loading.hide();
          if (index === data.length) {
            cfunc.showMsgBox(uiCount + " Images data successfully saved", "positive");
            setTimeout(function () {
              $q.loading.hide();
            }, 1500);
          } else {
            for (var a in data[index]) {
              insertUpdateUserImage(data[index][a]);
              if (parseInt(a) + 1 === data[index].length) {
                saveDownloadedUserImages(data, parseInt(index) + 1);
              }
            }
          }
        }

        function insertUpdateUserImage (image) {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from userimg where id=?", [image.id], function (tx, res) {
              if (res.rows.length > 0) {
                tx.executeSql("update userimg set img=? where id=?", [image.img, image.id], function (tx, res) {
                  console.log("updated 1 image");
                }, function (tx, err) {
                  console.log("update error 1 image: ", err.message);
                });
              } else {
                tx.executeSql("insert into userimg(id, img) values(?, ?)", [image.id, image.img], function (tx, res) {
                  console.log("insert 1 image");
                }, function (tx, err) {
                  console.log("insert error 1 image: ", err.message);
                });
              }
            });
          });
        }
      },
      downloadTimeinAccounts: function () {
        console.log("downloadTimeinAccounts called");
        let userCount = 0;
        let userData = [];
        cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
          if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            $q.loading.hide();
            return;
          }
          cfunc.showLoading();
          api.post(serveraddr + "/sbcmobilev2/download", { type: "timeinAccounts" }).then(res => {
            if (res.data.users.length > 0) {
              userCount = res.data.users.length;
              userData = res.data.users;
              let d = userData;
              let dd = [];
              while (d.length) {
                dd.push(d.splice(0, 100));
              }
              clearUsersTemp();
              saveDownloadedUsers(dd);
            } else {
              cfunc.showMsgBox("No account(s) to download", "negative", "warning");
              $q.loading.hide();
            }
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });

        function clearUsersTemp () {
          sbc.db.transaction(function (tx) {
            tx.executeSql("delete from useraccess2", [], function (tx, res) {
              console.log("useraccess cleared temp");
            });
          });
        }

        function saveDownloadedUsers (data, index = 0) {
          cfunc.showLoading("Saving Users data (" + index + " of " + data.length + ")");
          if (index === 0) $q.loading.hide();
          if (index === data.length) {
            cfunc.showMsgBox(userCount + " Users data successfully saved", "positive");
            setTimeout(function () {
              if (sbc.globalFunc.company === "sbc2")sbc.globalFunc.downloadUserImages();
              $q.loading.hide();
            }, 1500);
          } else {
            for (var a in data[index]) {
              insertUpdateUser(data[index][a]);
              if (parseInt(a) + 1 === data[index].length) {
                saveDownloadedUsers(data, parseInt(index) + 1);
              }
            }
          }
        }

        function insertUpdateUser (user) {
          let data1 = [user.email, user.name, user.password, user.isactive, user.empcode];
          let data2 = [user.empcode, user.email, user.name, user.password, user.isactive];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from useraccess2 where id=?", [user.empcode], function (tx, res) {
              if (res.rows.length > 0) {
                if (sbc.globalFunc.company === "sbc2") data1 = [user.idbarcode, user.name, user.password, user.isactive, user.empcode];
                tx.executeSql("update useraccess2 set email=?, name=?, password=?, isactive=? where id=?", data1, function (tx, res) {
                  console.log("updated 1 user");
                }, function (tx, err) {
                  console.log("update error 1 user: ", err.message);
                });
              } else {
                if (sbc.globalFunc.company === "sbc2") data2 = [user.empcode, user.idbarcode, user.name, user.password, user.isactive];
                tx.executeSql("insert into useraccess2(id, email, name, password, isactive) values(?, ?, ?, ?, ?)", data2, function (tx, res) {
                  console.log("insert 1 user");
                }, function (tx, err) {
                  console.log("save error 1 user: ", err.message);
                });
              }
            });
          });
        }
      },
      loadTimeinoutLogs: function () {
        sbc.modulefunc.loadLogs();
      },
      showSignaturePad: function () {
        showSigPad.value = true;
      },
      saveSignature: function (data) {
        switch (sbc.globalFunc.config) {
          case "inventoryapp":
            sbc.modulefunc.signatureDone(data);
            break;
          default:
            const permissions = cordova.plugins.permissions;
            permissions.checkPermission(permissions.WRITE_EXTERNAL_STORAGE, (status) => {
              let errorCallback = () => {
                console.log("error permission");
              }
              if(!status.hasPermission) {
                permissions.requestPermission(permissions.WRITE_EXTERNAL_STORAGE, function (status) {
                  if(!status.hasPermission) {
                    errorCallback();
                  } else {
                    contSave();
                  }
                }, errorCallback());
              } else {
                contSave();
              }
            }, null);
            break;
        }

        function contSave () {
          const filePath = cordova.file.externalRootDirectory + "Download/signatures/waw.png";
          const fileTransfer = new window.FileTransfer();
          fileTransfer.download(data, filePath, (entry) => {
            console.log("Signature successfully saved, full path: ", entry.fullPath);
          }, (error) => {
            console.log("error saving signature error: ", error);
          });
        }
        // cfunc.showLoading();
        // cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
        //   if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
        //     cfunc.showMsgBox("Server Address not set", "negative", "warning");
        //     return;
        //   }
        //   api.post(serveraddr + "/sbcmobilev2/saveSignature", { img: data, filename: "waw1" }).then(res => {
        //     if (res.data.status) {
        //       cfunc.showMsgBox(res.data.msg, "positive");
        //     } else {
        //       cfunc.showMsgBox(res.data.msg, "negative", "warning");
        //     }
        //     console.log(res.data);
        //     $q.loading.hide();
        //   }).catch(err => {
        //     console.log(err.message);
        //     $q.loading.hide();
        //   })
        // })
      },
      getSignature: function () {
        window.resolveLocalFileSystemURL(cordova.file.externalRootDirectory + "Download/signatures/waw.png", function success (fileEntry) {
          fileEntry.file(function (file) {
            var reader = new FileReader();
            reader.onloadend = function() {
              if (this.result) {
                var blob = new Blob([new Uint8Array(this.result)], { type: "image/png" });
                signatureImg.value = window.URL.createObjectURL(blob);
                showSigImg.value = true;
                cfunc.blobToBase64(blob).then(data => {
                  console.log("11111111111111111111", data);
                });
              }
            };
            reader.readAsArrayBuffer(file);
          });
        }, function () {
          console.log("File not found: ");
        });
      },
      setuplocation: function () {
        let siteLocation = "";
        if ($q.localStorage.has("siteLocation")) siteLocation = $q.localStorage.getItem("siteLocation");
        $q.dialog({
          title: "Please enter site location",
          prompt: { model: siteLocation, type: "text", outlined: true, isValid: val => val.length > 0 },
          ok: { flat: true, color: "primary", label: "Ok" },
          cancel: { flat: true, color: "negative", label: "Cancel" },
          persistent: true
        }).onOk(data => {
          if(data !== null && data !== "") {
            $q.localStorage.set("siteLocation", data);
            cfunc.showMsgBox("Site Location Set", "positive");
          } else {
            cfunc.showMsgBox("Please enter site location", "negative", "warning");
          }
        })
      },
      uploadSAPDoc: function () {
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        let isok = true;
        $q.dialog({
          message: "Do you want to upload transactions?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          cfunc.showLoading();

          loadStocks().then(stocks => {
            switch(sbc.doc) {
              case "rm": case "tm":
                loadDetails().then(details => {
                  if (details.length === 0) {
                    cfunc.showMsgBox("No items to upload", "negative", "warning");
                    $q.loading.hide();
                  } else {
                    contUploadSAPDoc(stocks, details);
                  }
                });
                break;
              case "rl": case "tr": case "dr":
                loadDetails().then(details => {
                  contUploadSAPDoc(stocks, details);
                });
                break;
              default:
                contUploadSAPDoc(stocks);
                break;
            }
          });
        });

        function loadDetails () {
          return new Promise((resolve) => {
            sbc.db.transaction(function (tx) {
              let sql = "select * from detail where trno=? and uploaded is null and doc=?";
              if (sbc.doc === "rm" || sbc.doc === "tm") sql = "select * from detail where isverified=1 and uploaded is null and trno=? and doc=?";
              tx.executeSql(sql, [sbc.modulefunc.cLookupForm.trno, sbc.doc], function (tx, res) {
                let details = [];
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    details.push(res.rows.item(x));
                    if (parseInt(x) + 1 == res.rows.length) resolve(details);
                  }
                } else {
                  resolve([]);
                }
              });
            });
          });
        }

        function loadStocks () {
          return new Promise((resolve) => {
            sbc.db.transaction(function (tx) {
              let qry = "select * from stock where trno=? and doc=?";
              if (sbc.doc === "rl" || sbc.doc === "tr" || sbc.doc === "dr") qry = "select * from stock where trno=? and uploaded is null and doc=?";
              // switch (sbc.doc) {
              //   case "rr": case "rl":
              //     qry = "select * from stock where trno=? and printdate is not null";
              //     break;
              // }
              tx.executeSql(qry, [sbc.modulefunc.cLookupForm.trno, sbc.doc], function (tx, res) {
                let stocks = [];
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    stocks.push(res.rows.item(x));
                    if (parseInt(x) + 1 === res.rows.length) resolve(stocks);
                  }
                } else {
                  resolve([]);
                }
              });
            });
          });
        }

        function contUploadSAPDoc (stocks, details = []) {
          console.log("stocks: ", stocks);
          cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
            if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
              cfunc.showMsgBox("Server Address not set", "negative", "warning");
              $q.loading.hide();
              return;
            }
            cfunc.showLoading();
            api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("uploadSAPDoc"), stocks: stocks, details: details, user: storage.user.username, doc: sbc.doc, trno: sbc.modulefunc.cLookupForm.trno }, { headers: sbc.reqheader }).then(res => {
              if (res.data.status) {
                sbc.db.transaction(function (tx) {
                  switch (sbc.doc) {
                    case "rl": case "tr": case "dr":
                      if (details.length > 0) {
                        for (var d in details) {
                          tx.executeSql("update detail set uploaded=1 where trno=? and line=? and doc=?", [details[d].trno, details[d].line, sbc.doc]);
                          if (parseInt(d) + 1 === details.length) {
                            let completes = stocks.filter(waw => waw.iscomplete === 0);
                            stocks.filter(waw => {
                              if (waw.iscomplete === 1) tx.executeSql("update stock set uploaded=1 where trno=? and line=? and doc=?", [waw.trno, waw.line, sbc.doc]);
                            })
                            if (completes.length > 0) {
                              cfunc.showMsgBox(details.length + " Items uploaded", "positive");
                              sbc.showCustomLookup = false;
                              sbc.globalFunc.cLookupSelect = false;
                              sbc.globalFunc.cLookupSelected = [];
                              sbc.globalFunc.lookupTableData = [];
                              sbc.modulefunc.loadTableData();
                            } else {
                              contFunc();
                            }
                          }
                        }
                      }
                      break;
                    case "rm": case "tm":
                      if (details.length > 0) {
                        for (var d in details) {
                          tx.executeSql("update detail set uploaded=1 where trno=? and line=? and doc=?", [details[d].trno, details[d].line, sbc.doc]);
                          if (parseInt(d) + 1 === details.length) {
                            tx.executeSql("select line from detail where trno=? and uploaded is null", [sbc.modulefunc.cLookupForm.trno], function (tx, res2) {
                              if (res2.rows.length > 0) {
                                cfunc.showMsgBox(details.length + " Items uploaded", "positive");
                                sbc.showCustomLookup = false;
                                sbc.globalFunc.cLookupSelect = false;
                                sbc.globalFunc.cLookupSelected = [];
                                sbc.modulefunc.lookupTableData = [];
                                sbc.modulefunc.loadTableData();
                              } else {
                                contFunc(res.data.msg);
                              }
                            }, function (tx, err) {
                              console.log("err1: ", err.message);
                            });
                          }
                        }
                      } else {
                        cfunc.showMsgBox("No item to upload", "negative", "warning");
                      }
                      break;
                    default:
                      contFunc(res.data.msg);
                      break;
                  }
                }, function (err) {
                  console.log("error updating head2: ", err.message);
                });
              } else {
                cfunc.showMsgBox(res.data.msg, "negative", "warning");
              }
              $q.loading.hide();
            }).catch(err => {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          });
        }

        function contFunc (msg = "") {
          console.log("d");
          sbc.db.transaction(function (tx) {
            tx.executeSql("delete from head where trno=? and doc=?", [sbc.modulefunc.cLookupForm.trno, sbc.doc], function (tx, res2) {
              tx.executeSql("delete from stock where trno=? and doc=?", [sbc.modulefunc.cLookupForm.trno, sbc.doc]);
              tx.executeSql("delete from detail where trno=? and doc=?", [sbc.modulefunc.cLookupForm.trno, sbc.doc]);
              tx.executeSql("delete from tempdetail where trno=?", [sbc.modulefunc.cLookupForm.trno]);
              cfunc.showMsgBox(msg, "positive");
              sbc.showCustomLookup = false;
              sbc.globalFunc.cLookupSelect = false;
              sbc.globalFunc.cLookupSelected = [];
              sbc.modulefunc.lookupTableData = [];
              sbc.modulefunc.loadTableData();
            }, function (tx, err) {
              console.log("error updating head: ", err.message);
            });
          }, function (err) {
            console.log("e", err.message);
          });
        }
      },
      cancelUploadSAPDoc: function () {
        console.log("cancel uploadsapdoc called");
        sbc.showCustomLookup = false;
      },
      printSAPCode: function () {
        console.log("print sap code called");
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        const datenow = cfunc.getDateTime("datetime");
        let itemsCount = 0;
        let pCount = 0;
        let eCount = 0;
        cfunc.showLoading();
        if (sbc.globalFunc.cLookupSelected.length > 0) {
          sbc.db.transaction(function (tx) {
            sbc.globalFunc.codestoPrint = [];
            contPrintSAPCode(sbc.globalFunc.cLookupSelected);
            // switch (sbc.doc) {
            //   case "rl":
            //     checkIfItemsComplete(sbc.globalFunc.cLookupSelected);
            //     break;
            //   default:
            //     contPrintSAPCode(sbc.globalFunc.cLookupSelected);
            //     break;
            // }
          });
        } else {
          cfunc.showMsgBox("Please select item(s) to print", "negative", "warning");
          $q.loading.hide();
        }

        function checkIfItemsComplete(data, index = 0) {
          sbc.db.transaction(function (tx) {
            if (index === data.length) {
              if (pCount > 0) cfunc.showMsgBox(pCount + " Item(s) printed.", "positive");
              if (eCount > 0) cfunc.showMsgBox("Cannot print " + eCount + " Item(s), Not yet complete", "negative", "warning", 4000, "eCount", [{ icon: "close", color: "white", round: true }]);
              $q.loading.hide();
              if (sbc.globalFunc.codestoPrint.length > 0) contPrintSAPCode2();
              sbc.globalFunc.cLookupSelected = [];
            } else {
              if (data[index].iscomplete === 1 || data[index].iscomplete === "1") {
                pCount++;
                getDetail(data[index]);
                updateSAPCode(data[index]);
                checkIfItemsComplete(data, parseInt(index) + 1);
              } else {
                eCount++;
                checkIfItemsComplete(data, parseInt(index) + 1);
              }
            }
          });
        }

        function getDetail (data) {
          let details = sbc.modulefunc.detailsList.filter(waw => waw.trno === data.trno && waw.sline === data.line && (waw.batchcode !== "" && waw.batchcode !== null));
          if (details.length > 0) {
            for (var d in details) {
              details[d].qty = details[d].qtyreleased;
              saveCodetoPrint(details[d]);
            }
          }
          // sbc.db.transaction(function (tx) {
          //   tx.executeSql("select rtrno, rline, barcode, batchcode, qtyreleased as qty from detail where trno=? and sline=?", [data.trno, data.line], function (tx, res) {
          //     if (res.rows.length > 0) {
          //       let d = { rtrno: res.rows.item(0).rtrno, rline: res.rows.item(0).rline, barcode: res.rows.item(0).barcode, batchcode: res.rows.item(0).batchcode, qty: res.rows.item(0).qty };
          //       saveCodetoPrint(d);
          //     }
          //   });
          // });
        }

        function contPrintSAPCode (data, index = 0) {
          sbc.db.transaction(function (tx) {
            if (index === data.length) {
              contPrintSAPCode2();
              cfunc.showMsgBox("Item(s) printed.", "positive");
              $q.loading.hide();
              sbc.globalFunc.cLookupSelected = [];
            } else {
              getDetail(data[index]);
              // saveCodetoPrint(data[index]);
              updateSAPCode(data[index]);
              contPrintSAPCode(data, parseInt(index) + 1);
            }
          });
        }

        function saveCodetoPrint (data) {
          let code = data.rtrno + ";" + data.rline + ";" + data.barcode + ";" + data.batchcode + ";" + data.qty;
          var data2 = [
            "^XA",
            "^FO20,260^A0N,25,17^FDQty: " + data.qty + "^FS",
            "^FO20,240^A0N,25,17^FDbatchcode: " + data.batchcode + "^FS",
            "^FO20,220^A0N,25,17^FDBarcode: " + data.barcode + "^FS",
            "^FO20,200^A0N,25,17^FDDocnum: " + data.rtrno + "^FS",
            "^FO20,10^BQ,2,6^FD" + code + "^FS",
            "^XZ"
          ];
          sbc.globalFunc.codestoPrint.push(data2);
        }

        function contPrintSAPCode2 () {
          console.log("print na yung code na nagenerate", sbc.globalFunc.codestoPrint);
          if (sbc.settings.hasprinting !== undefined && sbc.settings.hasprinting) {
            if (sbc.globalFunc.codestoPrint.length > 0) {
              qz.security.setCertificatePromise(function (resolve, reject) {
                resolve("-----BEGIN CERTIFICATE-----\n" +
                  "MIIECzCCAvOgAwIBAgIGAY5Q07tYMA0GCSqGSIb3DQEBCwUAMIGiMQswCQYDVQQG\n" +
                  "EwJVUzELMAkGA1UECAwCTlkxEjAQBgNVBAcMCUNhbmFzdG90YTEbMBkGA1UECgwS\n" +
                  "UVogSW5kdXN0cmllcywgTExDMRswGQYDVQQLDBJRWiBJbmR1c3RyaWVzLCBMTEMx\n" +
                  "HDAaBgkqhkiG9w0BCQEWDXN1cHBvcnRAcXouaW8xGjAYBgNVBAMMEVFaIFRyYXkg\n" +
                  "RGVtbyBDZXJ0MB4XDTI0MDMxNzA5MTAzN1oXDTQ0MDMxNzA5MTAzN1owgaIxCzAJ\n" +
                  "BgNVBAYTAlVTMQswCQYDVQQIDAJOWTESMBAGA1UEBwwJQ2FuYXN0b3RhMRswGQYD\n" +
                  "VQQKDBJRWiBJbmR1c3RyaWVzLCBMTEMxGzAZBgNVBAsMElFaIEluZHVzdHJpZXMs\n" +
                  "IExMQzEcMBoGCSqGSIb3DQEJARYNc3VwcG9ydEBxei5pbzEaMBgGA1UEAwwRUVog\n" +
                  "VHJheSBEZW1vIENlcnQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCd\n" +
                  "86zcj8v3s9oQpaD6noH07lfjZyN0+DQa3L1g77/RV0ZvTEjlOh5r+hEp4nGeru6E\n" +
                  "rW7T2Z3kC+D0tkgvCHbvheqTCAnGjApXCFkIAh7oDvkvaUYGLGfsOVqJh80daumT\n" +
                  "jG7P36JaMivjfMyL0u9kNUZCRIif3bS4I4rP2sByB8SxosLIXriMZQT7xN1pbyAc\n" +
                  "8K7r5jzEuvSHWWBPhlJnH25hzpIC9CAXiK0lNNfRp4VQAAb8eMeVxoSrtWEp7kPt\n" +
                  "+Ma+HtfKz6yI4fqIYquUEk7zOWlHPbMBS/DpVQFELhISIBxuINsUIc5PtAQZ4rnP\n" +
                  "nbIBXizVsTMeoDiF0ZGvAgMBAAGjRTBDMBIGA1UdEwEB/wQIMAYBAf8CAQEwDgYD\n" +
                  "VR0PAQH/BAQDAgEGMB0GA1UdDgQWBBQtl95llFrE0vO3KBf2gwMbWdotJjANBgkq\n" +
                  "hkiG9w0BAQsFAAOCAQEAJIkXSM5sSZS8DbBbpGgZyVmhckJEaeU1b323mT9G2q+T\n" +
                  "mn95rp4AG7BNBrPaT2kYFp1vkJJBmuaTk907WrMvBiU39mktB9Sm8wU6V+I4FoW+\n" +
                  "ug5z25bRMwkpYm6CZIB9s2k1ZDJ+t/Y0lcTSGmiGDW11EZBjMx+kpBxnO7tW4VgA\n" +
                  "ZYDg1Fg0pwn2lCcXCZ9h4CskVaPZLPJkppWPItvSsVvx5LJmE4bDOFbCLKSR9pSx\n" +
                  "rJfhAo7Qx1zv0f7yhGBWDqdhJVRIgI9IKUmZth9+bBX1Ft160wMvKp5wI12Gw6uD\n" +
                  "mufvEYJK1rnPFR0KZCBRL9OuAyaXioZdCmLhY3Ti9A==\n" +
                  "-----END CERTIFICATE-----");
              });
              let privateKey = "-----BEGIN PRIVATE KEY-----\n" +
                "MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCd86zcj8v3s9oQ\n" +
                "paD6noH07lfjZyN0+DQa3L1g77/RV0ZvTEjlOh5r+hEp4nGeru6ErW7T2Z3kC+D0\n" +
                "tkgvCHbvheqTCAnGjApXCFkIAh7oDvkvaUYGLGfsOVqJh80daumTjG7P36JaMivj\n" +
                "fMyL0u9kNUZCRIif3bS4I4rP2sByB8SxosLIXriMZQT7xN1pbyAc8K7r5jzEuvSH\n" +
                "WWBPhlJnH25hzpIC9CAXiK0lNNfRp4VQAAb8eMeVxoSrtWEp7kPt+Ma+HtfKz6yI\n" +
                "4fqIYquUEk7zOWlHPbMBS/DpVQFELhISIBxuINsUIc5PtAQZ4rnPnbIBXizVsTMe\n" +
                "oDiF0ZGvAgMBAAECggEADdMtnSLrUmOOjm7aas7QelSzCQQwNhiHaJZ+vB5TJr3B\n" +
                "1eBsCb91hc2EdRFzdzuOd7rXL+Ak3rhfcmvSxsyx3eZVxGEriEWEaviGRzPDhFvT\n" +
                "JnnLZDzv96m2QyiWWAdq9e9dWTQUxlo/1yoUNf5t1gW65lzDRi+mL4iedlgj1TC2\n" +
                "DajZGLHSoVdYxDUm/kYQx4QjGZgvG9HxsFuOui82lMFS/auVDkvEhCzi94gJVE5H\n" +
                "CsSM7nkvLUuL6+fgdmehbfpaLZEtij2o/1gmwJQcmkUlAkXR/zOe18lEboM2FSPN\n" +
                "tcCCxB0C8YURvMFM1M0QNvrje4TDIbKk0zl+hl1IqQKBgQDQE/c4PQWuViS9jbHQ\n" +
                "qRaJFl6wwoj5toIZpoFchRwsyskl6pPqMseMj9ywOvcibwbfmtUU1+ZMlrHWX8yn\n" +
                "y7XkZfqdgMJEaT3C4IU97SsWBTBUVg3cRL0s7PQ/zLeTg9wIt8nWnQtkglgaI3cZ\n" +
                "FVB9d7RAs49Bzl5oWiqR0hN3xQKBgQDCVFTY3tocIKbZYt4FkulIgakOaNczgbXU\n" +
                "KV0V11eApWIRRyf337Qz629d3/VagXKp/7vlXC75Rk/UyDflp3Fs1tN2UTt7rGdT\n" +
                "f85LhRrqXNjjla4a3bWRTsrgy0ydcZ47zaXpfxVzos8MO72HskDpjhGfpW45FO+x\n" +
                "U2+nwvzG4wKBgE98QQUXsnLdb12FojZuUTB+/h8RwRd7E4nO5D2+j3vA30P/rw9Y\n" +
                "5IeacRhU/hEGTp7eW6WBr/Tz3+1fXSOAGvSrzCechxlxBmnKMLvuPHZF9ydQVC0f\n" +
                "iRB/V/KDNmFAjq453v1/sllrvVIG2DkZvkyfjJjmvsPJnKDKSNa5ZDxtAoGAU2zu\n" +
                "y/fH/QFLf9HA7PWn7rezQvthP7x0ufNUAfdjmlflpPM+RlykORHeypdF9qfR+QdP\n" +
                "u+R6SguUZA3caVwcBpSnTYkMF0jpRuB8SNGIv4pCllmA2AnMU+hWknDUoFbRjmz3\n" +
                "yCkFpZIEfwT6ldHBqkKScE4N5rWFPURLj+LWr+8CgYBIbKQiFG86cRnRpxLVDELo\n" +
                "hM+wqYCH1PEvdmNC6nofsGu4CNxpk8gEmMZxn5l6BNSQMlg2oPZ3xCt9d06e1MoA\n" +
                "4URe6ErEdW8wIJ2j1P8jz1pbJEdS3ZoMNjUh7ty3FhiGBF0JeHCNfEPkzkfbU6FQ\n" +
                "Hb+o4RCTxaxHnxEJ9BnVNw==\n" +
                "-----END PRIVATE KEY-----";
              qz.security.setSignatureAlgorithm("SHA512"); // Since 2.1
              qz.security.setSignaturePromise(function (toSign) {
                return function(resolve, reject) {
                  try {
                    var pk = KEYUTIL.getKey(privateKey);
                    var sig = new KJUR.crypto.Signature({"alg": "SHA512withRSA"});  // Use "SHA1withRSA" for QZ Tray 2.0 and older
                    sig.init(pk); 
                    sig.updateString(toSign);
                    var hex = sig.sign();
                    console.log("DEBUG: \n\n" + stob64(hextorstr(hex)));
                    resolve(stob64(hextorstr(hex)));
                  } catch (err) {
                    console.error(err);
                    reject(err);
                  }
                };
              });
              qz.websocket.connect({ host: "192.168.0.153" }).then(() => {
                return qz.printers.find("ZDesigner GC420t (EPL) (1)");
              }).then((printer) => {
                let config = qz.configs.create(printer);
                return qz.print(config, sbc.globalFunc.codestoPrint);
                $q.loading.hide();
              }).then(() => {
                return qz.websocket.disconnect();
              }).catch((err) => {
                console.error("waw", err);
              });
            }
          } else {
            cfunc.showMsgBox("Printer not set", "negative", "warning");
            $q.loading.hide();
          }
        }

        function updateSAPCode (c) {
          // let stock = sbc.modulefunc.lookupTableData.find(waw => waw.trno === c.trno && waw.line === c.line);
          // console.log("111111111", stock);

          sbc.db.transaction(function (tx) {
            let printed = sbc.modulefunc.lookupTableData.find(waw => waw.trno === c.trno && waw.line === c.line).printed;
            if (printed === "" || printed === null) printed = 0;
            printed = parseInt(printed) + 1;
            sbc.modulefunc.lookupTableData.find(waw => waw.trno === c.trno && waw.line === c.line).printed = printed;
            tx.executeSql("update stock set printdate=?, printby=?, printed=? where trno=? and line=? and doc=?", [datenow, storage.user.username, printed, c.trno, c.line, sbc.doc], function (tx, res) {
              itemsCount++;
              switch(sbc.doc) {
                case "rl": case "tr": case "dr":
                  sbc.modulefunc.lookupTableData.find(waw => waw.trno === c.trno && waw.line === c.line).isprinted = "Yes";
                  sbc.modulefunc.detailsList.filter(waw => waw.trno === c.trno && waw.sline === c.line).forEach(waw => {
                    let printed2 = waw.printed;
                    printed2++;
                    waw.printed = printed2;
                    tx.executeSql("update detail set printed=?, printdate=?, printby=? where trno=? and sline=? and doc=?", [printed2, datenow, storage.user.username, waw.trno, waw.sline, sbc.doc], function (tx, res2) {
                    }, function (tx, err) {
                      console.log("error updating detail on printing: ", err.message);
                    });
                  });
                  break;
                case "rm": case "tm":
                  // let storage = $q.localStorage.getItem("sbcmobilev2Data");
                  // let datenow = cfunc.getDateTime("datetime");
                  tx.executeSql("update detail set printdate=?, printedby=? where trno=? and sline=? and doc=?", [datenow, storage.user.username, c.trno, c.line, sbc.doc]);
                  break;
                case "rr": case "fg":
                  sbc.modulefunc.lookupTableData.find(waw => waw.trno === c.trno && waw.line === c.line).printed = printed;
                  break;
              }
            }, function (tx, err) {
              console.log(err.message);
            });
          }, function (err) {
            console.log("errorwawsss: ", err.message);
          });
        }
      },
      scanSAPCode: function (row, index) {
        console.log("scanSAPCode: ", row, "----", index);
        // if ((row.qty - row.qtyreleased) === 0) {
        //   cfunc.showMsgBox("Scan complete for this item", "negative", "warning");
        //   $q.loading.hide();
        // } else {
        // }
        sbc.modulefunc.inputLookupForm = { barcode: "201;1;RMI-ZINC;12345;25", stock: row, qty: 0, type: "scan" };
        // sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "scanQRCodeFields", "inputFields");
        // sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "scanQRCodeFields", "inputPlot");
        // sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "scanQRCodeButtons", "buttons");
        // sbc.isFormEdit = true;
        // sbc.inputLookupTitle = "Scan";
        // sbc.showInputLookup = true;
        sbc.showCustomLookup = false;
        sbc.showInputLookup = false;
        sbc.globalFunc.scanCallback().then(text => {
          sbc.showCustomLookup = true;
          sbc.modulefunc.inputLookupForm.barcode = text;
          sbc.globalFunc.scanQRCode();
        }).catch(err => {
          cfunc.showMsgBox(err, "negative", "warning");
        });
      },
      saveSAPQtyRelease: function () {
        console.log("saveSAPQtyRelease called");
        let stock = sbc.modulefunc.inputLookupForm.stock;
        let datenow = cfunc.getDateTime("datetime");
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        // const code = sbc.modulefunc.inputLookupForm.barcode.split(";");
        if (sbc.modulefunc.inputLookupForm.qty !== "") {
          let qty = parseFloat(parseFloat(sbc.modulefunc.inputLookupForm.qty).toFixed(4));
          if (qty <= 0) {
            cfunc.showMsgBox("Item already released", "negative", "warning");
            return;
          }
          if ((qty > sbc.modulefunc.inputLookupForm.scannedqty) && sbc.modulefunc.inputLookupForm.type === "scan") {
            cfunc.showMsgBox("Quantity entered is greater than quantity from scanned item", "negative", "warning");
            return;
          }
          let stockqty = parseFloat(parseFloat(stock.qty).toFixed(4));
          cfunc.showLoading();
          let sqtyreleased = parseFloat(parseFloat(stock.qtyreleased).toFixed(4));
          let qty1 = parseFloat(parseFloat(qty).toFixed(4));
          let stockqty1 = parseFloat(parseFloat(stockqty).toFixed(4));
          if (parseFloat(parseFloat(sqtyreleased + qty1).toFixed(4)) >= stockqty1) {
            sbc.db.transaction(function (tx) {
              tx.executeSql("update stock set iscomplete=1 where trno=? and line=? and doc=?", [stock.trno, stock.line, sbc.doc], function (tx, res1) {
                sbc.modulefunc.lookupTableData.find(waw => waw.trno === stock.trno && waw.line === stock.line).iscomplete = 1;
                sbc.modulefunc.lookupTableData.find(waw => waw.trno === stock.trno)
                let sindex = sbc.modulefunc.lookupTableData.findIndex(waw => waw.trno === stock.trno && waw.line === stock.line);
                sbc.modulefunc.lookupTableData[sindex].iscomplete = 1;
                sbc.modulefunc.lookupTableData[sindex].bgColor = "bg-blue-2";
                console.log("waw", sbc.modulefunc.lookupTableData);
              }, function (tx, err) {
                console.log("error1: ", err.message);
              });
            });
          }
          let line = 0;
          sbc.db.transaction(function (tx) {
            tx.executeSql("select line from detail where trno=? order by line desc limit 1", [stock.trno], function (tx, res2) {
              if (res2.rows.length > 0) line = parseInt(res2.rows.item(0).line);
              line++;
              qty = parseFloat(parseFloat(qty).toFixed(4));
              tx.executeSql("insert into detail(trno, sline, rtrno, rline, rrrefx, rrlinex, pickscanneddate, pickscannedby, qtyreleased, isverified, barcode, batchcode, doc) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [stock.trno, stock.line, stock.rtrno, stock.rline, stock.rrrefx, stock.rrlinex, datenow, storage.user.username, qty, 0, stock.barcode, sbc.modulefunc.inputLookupForm.batchcode, sbc.doc], function (tx, res) {
                let qindex = sbc.modulefunc.lookupTableData.findIndex(waw => waw.trno === stock.trno && waw.line === stock.line);
                let qtyreleased = sbc.modulefunc.lookupTableData[qindex].qtyreleased;
                qtyreleased = parseFloat(parseFloat(qtyreleased).toFixed(4));
                sbc.modulefunc.lookupTableData[qindex].qtyreleased = parseFloat(parseFloat(qtyreleased + qty).toFixed(4));
                if (sbc.modulefunc.inputLookupForm.type === "scan") {
                  $q.dialog({
                    title: "Code Scanned",
                    message: "Do you want to scan again?",
                    ok: { flat: true, color: "primary", label: "Yes" },
                    cancel: { flat: true, color: "negative", label: "No" }
                  }).onOk(() => {
                    sbc.showCustomLookup = false;
                    sbc.showInputLookup = false;
                    sbc.globalFunc.scanCallback().then(text => {
                      sbc.showCustomLookup = true;
                      sbc.modulefunc.inputLookupForm.barcode = text;
                      sbc.globalFunc.scanQRCode();
                    }).catch(err => {
                      cfunc.showMsgBox(err, "negative", "warning");
                    });
                  }).onCancel(() => {
                    sbc.showInputLookup = false;
                  });
                } else {
                  cfunc.showMsgBox("Qty released", "positive");
                  sbc.showInputLookup = false;
                }
                $q.loading.hide();
              });
            });
          });
          // cfunc.getTableData("detail", "qtyreleased", false, [{ field: "trno", value: stock.trno }, { field: "sline", value: stock.line }]).then(qtyreleased => {
          //   if (qtyreleased === "") {
          //     qtyreleased = parseFloat(parseFloat(qtyreleased).toFixed(4));
          //     if (qty === stockqty) {
          //       sbc.db.transaction(function (tx) {
          //         tx.executeSql("update stock set iscomplete=1 where trno=? and line=?", [stock.trno, stock.line], function (tx, res1) {
          //           tx.executeSql("update detail set isverified=1 where trno=? and sline=?", [stock.trno, stock.line], null, function (tx, err) {
          //             console.log("error updating detail: ", err.message);
          //           });
          //           sbc.modulefunc.lookupTableData.find(waw => waw.trno === stock.trno && waw.line === stock.line).iscomplete = 1;
          //         }, function (tx, err) {
          //           console.log("error updating stock: ", err.message);
          //         });
          //       });
          //     }
          //     let line = 0;
          //     sbc.db.transaction(function (tx) {
          //       tx.executeSql("select line from detail where trno=? order by line desc limit 1", [stock.trno], function (tx, res2) {
          //         if (res2.rows.length > 0) line = parseInt(res2.rows.item(0).line);
          //         line++;
          //         tx.executeSql("insert into detail(trno, sline, rtrno, rline, rrrefx, rrlinex, pickscanneddate, pickscannedby, qtyreleased, isverified, barcode, batchcode) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [stock.trno, stock.line, stock.rtrno, stock.rline, stock.rrrefx, stock.rrlinex, datenow, storage.user.username, qty, 0, stock.barcode, sbc.modulefunc.inputLookupForm.batchcode], function (tx, res) {
          //           cfunc.showMsgBox("detail saved", "positive");
          //           sbc.modulefunc.lookupTableData.find(waw => waw.trno === stock.trno && waw.line === stock.line).qtyreleased += qty;
          //           $q.dialog({
          //             message: "Do you want to scan again?",
          //             ok: { flat: true, color: "primary" },
          //             cancel: { flat: true, color: "negative" }
          //           }).onOk(() => {
          //             sbc.showCustomLookup = false;
          //             sbc.showInputLookup = false;
          //             sbc.globalFunc.scanCallback().then(text => {
          //               sbc.showCustomLookup = true;
          //               sbc.modulefunc.inputLookupForm.barcode = text;
          //               sbc.globalFunc.scanQRCode();
          //             }).catch(err => {
          //               cfunc.showMsgBox(err, "negative", "warning");
          //             });
          //             // sbc.showInputLookup = false;
          //             // sbc.modulefunc.inputLookupForm.qty = "";
          //             // sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "scanQRCodeFields", "inputFields");
          //             // sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "scanQRCodeFields", "inputPlot");
          //             // sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "scanQRCodeButtons", "buttons");
          //             // sbc.inputLookupTitle = "Scan";
          //             // sbc.showInputLookup = true;
          //           }).onCancel(() => {
          //             sbc.showInputLookup = false;
          //           });
          //         });
          //         $q.loading.hide();
          //       }, function (tx, err) {
          //         console.log(err.message);
          //         cfunc.showMsgBox("err1: ", err.message);
          //         $q.loading.hide();
          //       });
          //     });
          //   } else {
          //     if (parseFloat(parseFloat(qtyreleased + qty).toFixed(4)) > stockqty) {
          //       cfunc.showMsgBox("Quantity entered is greater than quantity needed.", "negative", "warning");
          //       $q.loading.hide();
          //       return;
          //     }
          //     if ((parseFloat(qtyreleased) + qty) === stockqty) {
          //       sbc.db.transaction(function (tx) {
          //         tx.executeSql("update stock set iscomplete=1 where trno=? and line=?", [stock.trno, stock.line]);
          //         sbc.modulefunc.lookupTableData.find(waw => waw.trno === stock.trno && waw.line === stock.line).iscomplete = 1;
          //       });
          //     }
          //     let qtyreleased2 = parseFloat(parseFloat(parseFloat(qtyreleased) + qty).toFixed(4));
          //     sbc.db.transaction(function (tx) {
          //       tx.executeSql("update detail set qtyreleased=? where trno=? and sline=?", [qtyreleased2, stock.trno, stock.line], function (tx, res) {
          //         cfunc.showMsgBox("Detail updated", "positive");
          //         sbc.modulefunc.lookupTableData.find(waw => waw.trno === stock.trno && waw.line === stock.line).qtyreleased = qtyreleased2;
          //         $q.loading.hide();
          //         $q.dialog({
          //           message: "Do you want to scan again?",
          //           ok: { flat: true, color: "primary" },
          //           cancel: { flat: true, color: "negative" }
          //         }).onOk(() => {
          //           sbc.showInputLookup = false;
          //           sbc.showCustomLookup = false;
          //           sbc.globalFunc.scanCallback().then(text => {
          //             sbc.showCustomLookup = true;
          //             sbc.modulefunc.inputLookupForm.barcode = text;
          //             sbc.globalFunc.scanQRCode();
          //           }).catch(err => {
          //             cfunc.showMsgBox(err, "negative", "warning");
          //           });
          //           // sbc.showInputLookup = false;
          //           // sbc.modulefunc.inputLookupForm.qty = "";
          //           // sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "scanQRCodeFields", "inputFields");
          //           // sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "scanQRCodeFields", "inputPlot");
          //           // sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "scanQRCodeButtons", "buttons");
          //           // sbc.inputLookupTitle = "Scan";
          //           // sbc.showInputLookup = true;
          //         }).onCancel(() => {
          //           sbc.showInputLookup = false;
          //         });
          //       });
          //     });
          //   }
          // });
        } else {
          cfunc.showMsgBox("Please enter quantity", "negative", "warning");
        }
      },
      cancelSAPQtyRelease: function () {
        console.log("cancelSAPQtyRelease called");
        if (sbc.modulefunc.inputLookupForm.type === "scan") {
          $q.dialog({
            message: "Do you want to scan again?",
            ok: { flat: true, color: "primary", label: "Yes" },
            cancel: { flat: true, color: "negative", label: "No" }
          }).onOk(() => {
            sbc.showInputLookup = false;
            sbc.showCustomLookup = false;
            sbc.globalFunc.scanCallback().then(text => {
              sbc.showCustomLookup = true;
              sbc.modulefunc.inputLookupForm.barcode = text;
              sbc.globalFunc.scanQRCode();
            }).catch(err => {
              cfunc.showMsgBox(err, "negative", "warning");
            });
            // sbc.showInputLookup = false;
            // sbc.modulefunc.inputLookupForm.qty = "";
            // sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "scanQRCodeFields", "inputFields");
            // sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "scanQRCodeFields", "inputPlot");
            // sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "scanQRCodeButtons", "buttons");
            // sbc.inputLookupTitle = "Scan";
            // sbc.showInputLookup = true;
          }).onCancel(() => {
            sbc.showInputLookup = false;
          });
        } else {
          sbc.showInputLookup = false;
        }
      },
      selectRMStock: function (row, index) {
        console.log("selectRMStock called");
        sbc.globalFunc.lookupTableSelect = true;
        sbc.globalFunc.lookupTableSelection = "multiple";
        sbc.globalFunc.lookupTableRowKey = "line";
        sbc.globalFunc.lookupCols = [
          { name: "barcode", label: "Item Code", align: "left", field: "barcode" },
          { name: "rtrno", label: "Line No", align: "left", field: "rtrno" },
          { name: "qtyreleased", label: "Qty", align: "left", field: "qtyreleased" }
        ];
        sbc.modulefunc.selectLookupTableFilter = { type: "filter", field: "", label: "Search Item", func: "" };
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno, doc, line, rtrno, rline, sline, qtyreleased, barcode, batchcode from detail where trno=? and sline=? and uploaded is null and doc=? and ifnull(batchcode, ?)=? and isverified=0", [row.trno, row.line, sbc.doc, "", ""], function (tx, res) {
            sbc.globalFunc.lookupData = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.globalFunc.lookupData.push(res.rows.item(x));
              }
            }
          });
        });
        sbc.globalFunc.lookupSelected = [];
        sbc.globalFunc.selectLookupBtnLabel = "Verify/Receive";
        sbc.globalFunc.lookupAction = "rmstocklookup";
        sbc.globalFunc.selectLookupType = "rmstocklookup";
        sbc.lookupTitle = "Select Items";
        sbc.showSelectLookup = true;
      },
      selectSAPCode: function (row, index) {
        console.log("seledctSAPCode called");
        const sindex = sbc.globalFunc.cLookupSelected.indexOf(row);
        if (sindex < 0) {
          sbc.globalFunc.cLookupSelected.push(row);
        } else {
          sbc.globalFunc.cLookupSelected.splice(sindex, 1);
        }
      },
      scanSAPList: function () {
        console.log("scanSAPList called");
        sbc.modulefunc.inputLookupForm = { barcode: "201;1;RMI-ZINC;12345;25", trno: sbc.modulefunc.cLookupForm.trno };
        // sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "scanListFields", "inputFields");
        // sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "scanListFields", "inputPlot");
        // sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "scanListButtons", "buttons");
        // sbc.isFormEdit = true;
        // sbc.inputLookupTitle = "Scan Code";
        // sbc.showInputLookup = true;
        sbc.showCustomLookup = false;

        sbc.globalFunc.scanCallback().then(text => {
          sbc.showCustomLookup = true;
          sbc.modulefunc.inputLookupForm.barcode = text;
          sbc.globalFunc.scanListBarcode();
        }).catch(err => {
          cfunc.showMsgBox(err, "negative", "warning");
        });
      },
      scanSAPActualCode: function () {
        console.log("scanSAPActualCode called");
        sbc.modulefunc.inputLookupForm = { barcode: "201;1;RMI-ZINC;12345;25", trno: sbc.modulefunc.cLookupForm.trno };
        // sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "scanItemFields", "inputFields");
        // sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "scanItemFields", "inputPlot");
        // sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "scanItemButtons", "buttons");
        // sbc.isFormEdit = true;
        // sbc.inputLookupTitle = "Scan Code";
        // sbc.showInputLookup = true;
        sbc.showCustomLookup = false;
        sbc.globalFunc.scanCallback().then(text => {
          sbc.showCustomLookup = true;
          sbc.modulefunc.inputLookupForm.barcode = text;
          sbc.globalFunc.scanItemBarcode();
        }).catch(err => {
          cfunc.showMsgBox(err, "negative", "warning");
        });
      },
      scanListBarcode: function () {
        console.log("scanListBarcode called");
        if (sbc.modulefunc.inputLookupForm.barcode === "") {
          cfunc.showMsgBox("Please enter/scan item", "negative", "warning");
          return;
        }
        let code = sbc.modulefunc.inputLookupForm.barcode.split(";");
        let barcode = code[2];
        let batchcode = code[3];
        let item = sbc.modulefunc.detailsList.find(waw => waw.barcode === barcode && waw.batchcode === batchcode);
        if (item !== undefined) {
          cfunc.showLoading();
          cfunc.getTableData("tempdetail", "line", false, [{ field: "trno", value: item.trno }, { field: "line", value: item.line }]).then(titem => {
            if (titem === "") {
              sbc.db.transaction(function (tx) {
                tx.executeSql("insert into tempdetail(trno, line, sline, barcode, batchcode) values(?, ?, ?, ?, ?)", [item.trno, item.line, item.sline, item.barcode, item.batchcode], function (tx, res) {
                  $q.loading.hide();
                  contFunc("Item scanned");
                }, function (tx, err) {
                  console.log("g", err.message);
                  $q.loading.hide();
                });
              }, function (err) {
                console.log("f", err.message);
                $q.loading.hide();
              });
            } else {
              $q.loading.hide();
              contFunc("Item already scanned");
            }
          });
        } else {
          contFunc("Item not found");
        }

        function contFunc (msg = "") {
          setTimeout(function () {
            $q.dialog({
              title: msg,
              message: "<span>Barcode: " + code[2] + "</span><br>\
                        <span>Batchcode: " + code[3] + "</span><br>\
                        <span>Docnum: " + code[0] + "</span><br>\
                        Do you want to scan another item?",
              // message: msg + ", Do you want to scan another item?",
              html: true,
              ok: { flat: true, color: "primary", label: "Yes" },
              cancel: { flat: true, color: "negative", label: "No" }
            }).onOk(() => {
              sbc.showCustomLookup = false;
              sbc.globalFunc.scanCallback().then(text => {
                sbc.showCustomLookup = true;
                sbc.modulefunc.inputLookupForm.barcode = text;
                sbc.globalFunc.scanListBarcode();
              }).catch(err => {
                cfunc.showMsgBox(err, "negative", "warning");
              });
            }).onCancel(() => {
              sbc.showCustomLookup = true;
            });
          }, 500);
        }
      },
      cancelScanListBarcode: function () {
        console.log("cancelScanListBarcode called");
        sbc.showInputLookup = false;
      },
      scanItemBarcode: function () {
        console.log("scanItemBarcode called");
        if (sbc.modulefunc.inputLookupForm.barcode === "") {
          cfunc.showMsgBox("Please enter/scan item", "negative", "warning");
          $q.loading.hide();
        }
        let code = sbc.modulefunc.inputLookupForm.barcode.split(";");
        let barcode = code[2];
        let batchcode = code[3];
        let item = sbc.modulefunc.detailsList.find(waw => waw.barcode === barcode && waw.batchcode === batchcode);
        if (item !== undefined) {
          cfunc.showLoading();
          sbc.db.transaction(function (tx) {
            console.log("waw", item);
            tx.executeSql("select * from tempdetail where trno=? and line=? and sline=?", [item.trno, item.line, item.sline], function (tx, res) {
              if (res.rows.length > 0) {
                let isverified = sbc.modulefunc.detailsList.find(waw => waw.barcode === barcode && waw.batchcode === batchcode).isverified;
                if (isverified === 1) {
                  contFunc("Item already verified");
                  $q.loading.hide();
                } else {
                  tx.executeSql("update detail set isverified=1 where trno=? and line=? and sline=? and doc=?", [item.trno, item.line, item.sline, sbc.doc], function (tx, res2) {
                    // sbc.modulefunc.lookupTableData.find(waw => waw.dbarcode === barcode && waw.dbatchcode === batchcode).isverified = 1;
                    sbc.modulefunc.detailsList.find(waw => waw.barcode === barcode && waw.batchcode === batchcode).isverified = 1;
                    // tx.executeSql("select isverified from detail where trno=? and line=? and sline=? and isverified=0", [item.trno, item.line, item.sline], function (tx, res3) {
                    //   if (res3.rows.length === 0) {
                    //     let sindex = sbc.modulefunc.lookupTableData.findIndex(waw => waw.trno === item.trno && waw.line === item.sline);
                    //     sbc.modulefunc.lookupTableData.splice(sindex, 1);
                    //   }
                    // });
                    $q.loading.hide();
                    contFunc("Item  verified");
                  });
                }
              } else {
                contFunc("Item does not exist in the scan list");
                $q.loading.hide();
              }
            }, function (tx, err) {
              console.log("error updating detail err: ", err.message);
              $q.loading.hide();
            });
          }, function (err) {
            console.log("error: ", err.message);
            $q.loading.hide();
          });
        } else {
          contFunc("Item does not exist in the scan list");
        }

        function contFunc (msg = "") {
          setTimeout(function () {
            $q.dialog({
              title: msg,
              message: "<span>Barcode: " + code[2] + "</span><br>\
                        <span>Batchcode: " + code[3] + "</span><br>\
                        <span>Docnum: " + code[0] + "</span><br>\
                        Do you want to scan another item?",
              html: true,
              // message: msg + ", Do you want to scan another item?",
              ok: { flat: true, color: "primary", label: "Yes" },
              cancel: { flat: true, color: "negative", label: "No" }
            }).onOk(() => {
              sbc.showCustomLookup = false;
              sbc.globalFunc.scanCallback().then(text => {
                sbc.showCustomLookup = true;
                sbc.modulefunc.inputLookupForm.barcode = text;
                sbc.globalFunc.scanItemBarcode();
              }).catch(err => {
                cfunc.showMsgBox(err, "negative", "warning");
              });
            }).onCancel(() => {
              sbc.showCustomLookup = true;
            });
          }, 500);
        }
      },
      cancelScanItemBarcode: function () {
        console.log("cancelScanItemBarcode called");
        sbc.showInputLookup = false;
      },
      scanQRCode: function () {
        console.log("scanQRCode called");
        if (sbc.modulefunc.inputLookupForm.barcode !== "") {
          let qrcode = sbc.modulefunc.inputLookupForm.barcode.split(";");
          let barcode = qrcode[2];
          let batchcode = qrcode[3];
          let qty = parseFloat(qrcode[4]);
          sbc.modulefunc.inputLookupForm.scannedqty = qty;
          sbc.modulefunc.inputLookupForm.barcode1 = barcode;
          sbc.modulefunc.inputLookupForm.batchcode = batchcode;
          sbc.modulefunc.inputLookupForm.rtrno = qrcode[0];
          let stock = sbc.modulefunc.inputLookupForm.stock;
          let sqty = parseFloat(stock.qty);
          let rqty = parseFloat(stock.qtyreleased);
          // sbc.modulefunc.inputLookupForm.codeqty = parseFloat(parseFloat(sqty - rqty).toFixed(4));
          sbc.modulefunc.inputLookupForm.codeqty = sqty;
          sbc.modulefunc.inputLookupForm.qtyreleased = rqty;
          let qtyneeded = parseFloat(parseFloat(sqty - rqty).toFixed(4));
          if (qtyneeded < 0) qtyneeded = 0;
          sbc.modulefunc.inputLookupForm.qtyneeded = qtyneeded;
          switch (sbc.doc) {
            case "rl": case "tr": case "dr":
              if (stock.barcode === barcode) {
                sbc.db.transaction(function (tx) {
                  tx.executeSql("select batchcode from detail where trno=? and sline=? and barcode=? and batchcode=?", [stock.trno, stock.line, barcode, batchcode], function (tx, res1) {
                    if (res1.rows.length > 0) {
                      showContScan("Code already scanned");
                    } else {
                      sbc.showCustomLookup = true;
                      contFunc();
                    }
                  });
                });
              } else {
                showContScan("Barcode not match");
              }
              break;
            default:
              if (stock.barcode === barcode && stock.batchcode === batchcode) {
                contFunc(qty, sqty, rqty);
              } else {
                cfunc.showMsgBox("Barcode not match", "negative", "warning");
              }
              break;
          }

          function showContScan (msg) {
            setTimeout(function () {
              $q.dialog({
                title: msg,
                message: "<span>Barcode: " + qrcode[2] + "</span><br>\
                          <span>Batchcode: " + qrcode[3] + "</span><br>\
                          <span>Docnum: " + qrcode[0] + "</span><br>\
                          <span>Qty Required: " + qrcode[4] + "</span><br>\
                          Do you want to scan again?",
                html: true,
                // message: msg,
                ok: { flat: true, color: "primary", label: "Yes" },
                cancel: { flat: true, color: "negative", label: "No" }
              }).onOk(() => {
                sbc.showCustomLookup = false;
                sbc.globalFunc.scanCallback().then(text => {
                  sbc.showCustomLookup = true;
                  sbc.modulefunc.inputLookupForm.barcode = text;
                  sbc.globalFunc.scanQRCode();
                }).catch(err => {
                  cfunc.showMsgBox(err, "negative", "warning");
                });
              });
            }, 500);
          }

          function contFunc () {
            // if ((sqty - rqty) === 0) {
            //   showContScan("Item already released, Continue scanning?");
            // } else {
            // }
            sbc.modulefunc.inputLookupForm.qty = qty;
            // if (qty < (sqty - rqty)) {
            //   if (qty < 0) qty = 0;
            //   sbc.modulefunc.inputLookupForm.qty = qty;
            // } else {
            //   sbc.modulefunc.inputLookupForm.qty = parseFloat(parseFloat(sqty - rqty).toFixed(4));
            // }
            sbc.modulefunc.inputLookupForm.uom = stock.uom;
            sbc.modulefunc.inputLookupForm.batchcode = batchcode;
            sbc.showInputLookup = false;
            sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "scanQtyReleaseFields", "inputFields");
            sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "scanQtyReleaseFields", "inputPlot");
            sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "scanQtyReleaseButtons", "buttons");
            sbc.inputLookupTitle = "Enter Qty";
            setTimeout(function () {
              sbc.showInputLookup = true;
            }, 500);
          }
        }
      },
      cancelScanQRCode: function () {
        console.log("cancelScanQRCode called");
        sbc.showInputLookup = false;
      },
      scanCallback: function () {
        return new Promise((resolve, reject) => {
          isScan.value = true;
          isFlash.value = false;
          QRScanner.prepare(onDone);

          function onDone (err, status) {
            if (err) reject(err);
            if (status.authorized) {
              QRScanner.useBackCamera();
              QRScanner.scan(displayContents);
              function displayContents (err, text) {
                QRScanner.destroy();
                if (err) {
                  console.log("an error occurred, or the scan was canceled (error code 6)");
                  reject("An error occurred or the scan was cancelled.");
                } else {
                  isScan.value = false;
                  isFlash.value = false;
                  console.log("The scan completed, display the contents of the QR code: ", text);
                  resolve(text);
                }
              }
              QRScanner.show();
            } else if (status.denied) {
              reject("Access denied, Please try again.");
            }
          }
        });
      },
      viewSAPScanList: function () {
        console.log("viewSAPScanList called");
        sbc.globalFunc.loadLookup("sapScanList");
      },
      cancelScanner: function () {
        if (sbc.globalFunc.config === "timeinapp") {
          sbc.showCustomLookup = false;
          sbc.showInputLookup = false;
        } else {
          sbc.showCustomLookup = true;
          sbc.showInputLookup = false;
        }
      },
      addSAPQty: function (row, index) {
        console.log("addSAPQty called");
        // if (row.iscomplete === 1 || row.iscomplete === "1") {
        //   cfunc.showMsgBox("Item already released", "negative", "warning");
        //   return;
        // }
        let qty = row.qty;
        let qtyreleased = parseFloat(row.qtyreleased);
        let qtyneeded = parseFloat(parseFloat(row.qty - row.qtyreleased).toFixed(4));
        if (qtyneeded < 0) qtyneeded = 0;
        sbc.modulefunc.inputLookupForm = { barcode: "", stock: row, qty: qtyneeded, qtyneeded: qtyneeded, qtyreleased: qtyreleased, type: "manual", uom: row.uom, batchcode: "", barcode1: row.barcode, codeqty: qty, rtrno: row.rtrno };
        sbc.showInputLookup = false;
        sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "scanQtyReleaseFields", "inputFields");
        sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "scanQtyReleaseFields", "inputPlot");
        sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "scanQtyReleaseButtons", "buttons");
        sbc.inputLookupTitle = "Enter Qty";
        setTimeout(function () {
          sbc.showInputLookup = true;
        }, 500);
      },
      verifySAPStock: function () {
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          sbc.globalFunc.lookupSelected.map((waw, i, row) => {
            tx.executeSql("update detail set isverified=1 where trno=? and line=? and doc=?", [waw.trno, waw.line, waw.doc], function (tx, res) {
              if (i + 1 === row.length) {
                cfunc.showMsgBox(row.length + " Item(s) verified/received", "positive");
                sbc.showSelectLookup = false;
                $q.loading.hide();
              }
            }, function (tx, err) {
              console.log("error verifying item", err.message);
            });
          })
        });
      },
      downloadInventoryItems: function () {
        console.log("downloadInventoryItems called");
        // sbc.modulefunc.downloadInventoryItems();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno from head union all select trno from hhead where uploaded is null or uploaded = 0", [], function (tx, res) {
            if (res.rows.length > 0) {
              sbc.globalFunc.showErrMsg("Please upload all transactions first before downloading items");
            } else {
              tx.executeSql("select count(itemid) as icount from item", [], function (tx, ires) {
                if (ires.rows.item(0).icount > 0) {
                  $q.dialog({
                    message: "Downloading new items will delete transaction history, Do you want to continue?",
                    ok: { flat: true, color: "primary" },
                    cancel: { flat: true, color: "negative" }
                  }).onOk(() => {
                    contDownload();
                  });
                } else {
                  contDownload();
                }
              }, function (tx, err) {
                sbc.globalFunc.showErrMsg("Err1: " + err.message);
              });
            }
          }, function (tx, err) {
            sbc.globalFunc.showErrMsg("Err3: " + err.message);
          });
        }, function (err) {
          console.log("err2: ", err.message);
        });

        function contDownload () {
          try {
            cfunc.showLoading();
            cfunc.getTableData("config", "serveraddr").then(serveraddr => {
              if (serveraddr === "" || serveraddr === null || serveraddr === undefined) {
                sbc.globalFunc.showErrMsg("Server Address not set");
                return;
              }
              switch (sbc.globalFunc.company) {
                case "mbs":
                  $q.loading.hide();
                  sbc.globalFunc.clearInvItems().then(() => {
                    sbc.globalFunc.saveMBSItems(serveraddr);
                  });
                  break;
                default:
                  sbc.globalFunc.lookupTableSelect = true;
                  sbc.globalFunc.lookupTableSelection = "multiple";
                  sbc.globalFunc.lookupTableRowKey = "client";
                  sbc.globalFunc.lookupCols = [
                    { name: "client", label: "Code", align: "left", field: "client" },
                    { name: "clientname", label: "Name", align: "left", field: "clientname" }
                  ];
                  let wh = [];
                  api.post(serveraddr + "/sbcmobilev2/download", { type: "wh" }).then(res => {
                    wh = res.data.wh;
                    if (wh.length > 0) {
                      sbc.db.transaction(function (tx) {
                        tx.executeSql("delete from tempwh");
                        let tempwhdata = { data: { inserts: { tempwh: wh } } };
                        cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, tempwhdata, {
                          successFn: function () {
                            $q.loading.hide();
                            sbc.modulefunc.selectLookupTableFilter = { type: "searchWarehouse", field: "txtSearchWarehouse", label: "Search Warehousessss", func: "searchInvWarehouse", functype: "global", livesearch: true, debounce: "500" };
                            sbc.globalFunc.lookupData = wh;
                            sbc.globalFunc.lookupAction = "whlookup";
                            sbc.globalFunc.selectLookupBtnLabel = "Download";
                            sbc.globalFunc.selectLookupType = "inventorywh";
                            sbc.lookupTitle = "Select Warehouse";
                            sbc.showSelectLookup = true;
                          },
                          errorFn: function (error) {
                            sbc.globalFunc.showErrMsg("Import Error: " + error.message);
                            $q.loading.hide();
                          },
                          progressFn: function (current, total) {
                            cfunc.showLoading("Loading Warehouse (Batch " + current + " of " + total + ")");
                          }
                        });
                      });
                    } else {
                      sbc.globalFunc.showErrMsg("No warehouse to download");
                    }
                    $q.loading.hide();
                  }).catch(err => {
                    sbc.globalFunc.showErrMsg(err.message);
                    $q.loading.hide();
                  });
                  break;
              }
            });
          } catch(err) {
            console.log("error downloading warehouse");
          }
        }
      },
      saveMBSItems: function (serveraddr) {
        let idata = [];
        let iend = 0;
        let isavedcount = 0;
        let icount = 0;
        let whsaved = 0;
        console.log("saveMBSItems called");
        // let branchaddr = $q.localStorage.getItem("mbsBranchAddr");
        cfunc.getTableData("config", "branchaddr").then(branchaddr => {
          if (branchaddr === "" || branchaddr === null || branchaddr === undefined) {
            sbc.globalFunc.showErrMsg("Branch Address not set, Please try again.");
          } else {
            downloadItems(branchaddr);
          }
        });

        function downloadItems(branchaddr) {
          cfunc.showLoading("Downloading Items, Please wait...");
          let url = branchaddr + "/sbcmobilev2/download";
          // if (!branchaddr.includes("https://")) url = "https://" + branchaddr + "/mobileapi/sbcmobilev2/download";
          api.post(url, { type: "mbsitems", iend: iend }).then(res => {
            if (whsaved === 0) {
              if (res.data.whs.length > 0) {
                sbc.db.transaction(function (tx) {
                  tx.executeSql("insert into wh(client, clientname, branch) values(?, ?, ?)", [res.data.whs[0].wh, res.data.whs[0].whname, res.data.whs[0].branch], function (tx, res2) {
                    cfunc.showMsgBox("Warehouse saved", "positive");
                    whsaved = 1;
                    if (router.currentRoute.fullPath === "/login") {
                      if (mbsWH.value !== undefined) mbsWh.value = res.data.whs[0].wh;
                    }
                    saveItems(res.data, branchaddr);
                  }, function (tx, err) {
                    sbc.globalFunc.showErrMsg(err.message + "(1: " + url + ")");
                    $q.loading.hide();
                  });
                });
              } else {
                sbc.globalFunc.showErrMsg("No warehouse to save, Please try again.");
                $q.loading.hide();
              }
            } else {
              saveItems(res.data, branchaddr);
            }
          }).catch(err => {
            sbc.globalFunc.showErrMsg(err.message + "(" + url + ")");
            $q.loading.hide();
          });
        }

        function saveItems (data, branchaddr) {
          if (iend === 0) icount = data.icount;
          iend = data.iend;
          if (data.items.length > 0) {
            if (isavedcount !== icount) {
              contSaveItems(data.items, branchaddr);
            } else {
              cfunc.showLoading(`Successfully imported ${icount} Items`);
              setTimeout(function () {
                $q.loading.hide();
              }, 1500);
            }
            isavedcount += data.items.length;
          } else if (data.items.length === 0 && icount !== 0) {
            cfunc.showLoading(`Successfully imported ${icount} Items`);
            setTimeout(function () {
              $q.loading.hide();
            }, 1500);
          } else {
            sbc.globalFunc.showErrMsg("No items to save");
            $q.loading.hide();
          }
        }

        function contSaveItems(itemss, branchaddr) {
          idata = { data: { inserts: { item: itemss } } };
          cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, idata, {
            successFn: function () {
              downloadItems(branchaddr);
            },
            errorFn: function (error) {
              sbc.globalFunc.showErrMsg("Import Error: " + error.message);
              $q.loading.hide();
            },
            progressFn: function (current, total) {
              cfunc.showLoading("Saving Items (Batch " + current + " of " + total + ")");
            }
          });
        }
      },
      setPCDate: function () {
        console.log("setPCDate called");
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno from head union all select trno from hhead where uploaded is null or uploaded = 0", [], function (tx, res) {
            console.log("==============", res.rows.length);
            if (res.rows.length > 0) {
              // cfunc.showMsgBox("Cannot change date, this device already has transaction(s). Please upload it first before changing.", "negative", "warning");
              sbc.globalFunc.showErrMsg("Cannot change date, this device already has transaction(s). Please upload it first before changing.");
              $q.loading.hide();
            } else {
              $q.loading.hide();
              contFunc();
            }
          }, function (tx, err) {
            // cfunc.showMsgBox("err1: " + err.message, "negative", "warning");
            sbc.globalFunc.showErrMsg("Err1: " + err.message);
            $q.loading.hide();
          });
        }, function (err) {
          // cfunc.showMsgBox("err2: " + err.message, "negative", "warning");
          sbc.globalFunc.showErrMsg("Err2: " + err.message);
          $q.loading.hide();
        });
        function contFunc () {
          $q.dialog({
            message: "Please enter Physical Count Date",
            prompt: {
              model: "pcdate",
              type: "date",
              outlined: true,
              dense: true
            }
          }).onOk((data) => {
            if ($q.localStorage.has("invPCDate")) $q.localStorage.remove("invPCDate");
            $q.localStorage.set("invPCDate", data);
            mbsPCDate.value = data;
            $q.loading.hide();
            cfunc.showMsgBox("Physical date saved.", "positive");
          });
        }
      },
      invAdminLogin: function () {
        // sbc.inputlookupfields = [];
        // sbc.inputlookupfieldsplot = [];
        // sbc.inputlookupbuttons = [];

        sbc.inputlookupfields = [
          { doc: "inventory", form: "adminLoginForm", type: "input", style: null, class: null, show: null, name: "adminloginuser", label: "Username", enterfunc: null, functype: null, dense: null, autofocus: true },
          { doc: "inventory", form: "adminLoginForm", type: "password", style: null, class: null, show: null, name: "adminloginpass", label: "Password", enterfunc: "handleInvAdminLogin", functype: "global", dense: null, autofocus: null }
        ];
        sbc.inputlookupfieldsplot = [
          { doc: "inventory", form: "adminLoginForm", fields: ["adminloginuser"] },
          { doc: "inventory", form: "adminLoginForm", fields: ["adminloginpass"] }
        ];
        sbc.inputlookupbuttons = [
          { doc: "inventory", form: "adminLoginButton", name: "adminLoginBtn", label: "Ok", icon: null, func: "handleInvAdminLogin", functype: "global", color: "primary", action: null },
          { doc: "inventory", form: "adminLoginButton", name: "cancelAdminLogin", label: "Cancel", icon: null, func: "cancelAdminLogin", functype: "global", color: "negative", action: null }
        ];
        // sbc.inputlookupfields.push({ doc: "inventory", form: "adminLoginForm", type: "input", style: null, class: null, show: null, name: "adminloginuser", label: "Username", enterfunc: null, functype: null, dense: null, autofocus: true });
        // sbc.inputlookupfields.push({ doc: "inventory", form: "adminLoginForm", type: "password", style: null, class: null, show: null, name: "adminloginpass", label: "Password", enterfunc: "handleAdminLogin", functype: "global", dense: null, autofocus: null });
        // sbc.inputlookupfieldsplot.push({ doc: "inventory", form: "adminLoginForm", fields: ["adminloginuser"] });
        // sbc.inputlookupfieldsplot.push({ doc: "inventory", form: "adminLoginForm", fields: ["adminloginpass"] });
        // sbc.inputlookupbuttons.push({ doc: "inventory", form: "adminLoginButton", name: "adminLoginBtn", label: "Ok", icon: null, func: "handleAdminLogin", functype: "global", color: "primary", action: null });
        // sbc.inputlookupbuttons.push({ doc: "inventory", form: "adminLoginButton", name: "cancelAdminLogin", label: "Cancel", icon: null, func: "cancelAdminLogin", functype: "global", color: "negative", action: null });
        sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "adminLoginForm", "inputFields");
        sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "adminLoginForm", "inputPlot");
        sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "adminLoginButton", "buttons");
        sbc.modulefunc.inputLookupForm = { adminloginuser: "", adminloginpass: "" };
        sbc.isFormEdit = true;
        sbc.inputLookupTitle = "Enter Password";
        sbc.showInputLookup = true;
      },
      handleInvAdminLogin: function () {
        console.log("handleAdminLogin called", sbc.modulefunc.inputLookupForm);
        if (sbc.modulefunc.inputLookupForm.adminloginuser === "") {
          sbc.globalFunc.showErrMsg("Please enter username");
          return;
        }
        if (sbc.modulefunc.inputLookupForm.adminloginpass === "") {
          sbc.globalFunc.showErrMsg("Please enter password");
          return;
        }
        const password = sbc.modulefunc.inputLookupForm.adminloginpass.split(".");
        let datenow = cfunc.getDateTime("date");
        datenow = datenow.replace(/\//g, "-");
        if (datenow !== password[1]) {
          sbc.globalFunc.showErrMsg("Invalid password");
          return;
        }
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select ua.userid, ua.accessid, u.attributes, ua.username, ua.password, ua.wh, ua.name from useraccess as ua left join users as u on u.idno=ua.accessid where ua.username=? and ua.password=?", [sbc.modulefunc.inputLookupForm.adminloginuser, password[0]], function (tx, res) {
            if (res.rows.length > 0) {
              $q.loading.hide();
              $q.dialog({
                message: "Do you want to clear transactions?",
                ok: { flat: true, color: "primary" },
                cancel: { flat: true, color: "negative" }
              }).onOk(() => {
                sbc.showInputLookup = false;
                sbc.globalFunc.clearInvTransactions();
              });
            } else {
              sbc.globalFunc.showErrMsg("Invalid username or password");
              $q.loading.hide();
            }
          });
        });
      },
      cancelAdminLogin: function () {
        sbc.showInputLookup = false;
      },
      clearInvTransactions: function () {
        cfunc.showLoading("Clearing transactions, Please wait...");
        sbc.db.sqlBatch([
          "delete from head",
          "delete from hhead",
          "delete from stock",
          "delete from hstock",
          "update wh set uploaded=0, generated=0, filename=null"
        ], function () {
          cfunc.showMsgBox("Transactions cleared", "positive");
          $q.loading.hide();
        }, function (err) {
          // cfunc.showMsgBox(err.message, "negative", "warning");
          sbc.globalFunc.showErrMsg(err.message);
          $q.loading.hide();
          console.log("error: ", err);
        });
        // sbc.db.transaction(function (tx) {
        //   tx.executeSql("delete from head");
        //   tx.executeSql("delete from hhead");
        // });
      },
      downloadSelectedWH: function () {
        let idata = [];
        let iend = 0;
        let isavedcount = 0;
        let icount = 0;

        let ibdata = [];
        let ibend = 0;
        let ibsavedcount = 0;
        let ibcount = 0;

        let icdata = [];
        let icend = 0;
        let icsavedcount = 0;
        let iccount = 0;
        sbc.globalFunc.icdata = [];
        sbc.globalFunc.icend = 0;
        sbc.globalFunc.icsavedcount = 0;
        sbc.globalFunc.iccount = 0;

        let whs = [];
        let whscount = 0;
        if (sbc.globalFunc.lookupSelected.length > 0) {
          sbc.globalFunc.clearInvItems().then(() => {
            saveWH();
          });
        } else {
          // cfunc.showMsgBox("No WH(s) selected", "negative", "warning");
          sbc.globalFunc.showErrMsg("No WH(s) selected");
        }

        function saveWH () {
          let swhs = [];
          sbc.globalFunc.lookupSelected.map((waw, i, rows) => {
            swhs.push({ client: waw.client, clientname: waw.clientname });
            if (i + 1 === rows.length) {
              whscount = rows.length;
              let d = swhs;
              let dd = [];
              while (d.length) dd.push(d.splice(0, 100));
              save(dd);
            }
          });

          function save (whd, index = 0) {
            cfunc.showLoading("Saving Warehouse Data (Batch " + index + " of " + whd.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === whd.length) {
              cfunc.showLoading("Successfully imported " + whscount + " Warehouse");
              setTimeout(function () {
                $q.loading.hide();
                contDownloadDetails();
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in whd[index]) {
                  insertWH(whd[index][a]);
                  if (parseInt(a) + 1 === whd[index].length) save(whd, parseInt(index) + 1);
                }
              });
            }
          }

          function insertWH (data) {
            sbc.db.transaction(function (tx) {
              let qry = "insert into wh(client, clientname) values(?, ?)";
              let param = [data.client, data.clientname];
              tx.executeSql(qry, param, function (tx, res) {
                console.log("=========", res);
              }, function (tx, err) {
                cfunc.saveErrLog(qry, param, err.message);
              });
            });
          }
        }

        function contDownloadDetails () {
          sbc.globalFunc.lookupSelected.map((waw, i, rows) => {
            whs.push(waw.client);
            if (i + 1 === rows.length) {
              cfunc.getTableData("config", "serveraddr").then(serveraddr => {
                if (serveraddr === "" || serveraddr === null || serveraddr === undefined) {
                  // cfunc.showMsgBox("Server Address not set", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                  sbc.globalFunc.showErrMsg("Server Address not set");
                  return;
                }
                getItems(serveraddr);
              });
            }
          });
        }

        function getItems (serveraddr) {
          cfunc.showLoading("Downloading Items, Please wait...");
          api.post(serveraddr + "/sbcmobilev2/download", { type: "invItems", iend: iend, whs: whs }).then(res => {
            if (res) {
              if (res.data.items.length > 0) {
                if (iend === 0) icount = res.data.icount;
                if (isavedcount !== icount) {
                  saveItems(res.data.items, serveraddr);
                } else {
                  cfunc.showLoading(`Successfully imported ${icount} Items`);
                  setTimeout(function () {
                    $q.loading.hide();
                    getItemBal(serveraddr);
                  }, 1500);
                }
                isavedcount += res.data.items.length;
                iend = res.data.iend;
              } else {
                cfunc.showLoading(`Successfully imported ${icount} Items`);
                setTimeout(function () {
                  $q.loading.hide();
                  getItemBal(serveraddr);
                }, 1500);
              }
            } else {
              $q.loading.hide();
            }
          });
        }

        function saveItems (data, serveraddr) {
          idata = { data: { inserts: { item: data } } };
          cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, idata, {
            successFn: function () {
              getItems(serveraddr);
            },
            errorFn: function (error) {
              cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              $q.loading.hide();
            },
            progressFn: function (current, total) {
              cfunc.showLoading("Saving Items (Batch " + current + " of " + total + ")");
            }
          });
        }

        function getItemBal (serveraddr) {
          cfunc.showLoading("Downloading Item Balance, Please wait...");
          api.post(serveraddr + "/sbcmobilev2/download", { type: "invItemBal", whs: whs }).then(res => {
            if (res) {
              if (res.data.itembal.length > 0) {
                ibcount = res.data.itembal.length;
                saveItemBal(res.data.itembal, serveraddr);
              } else {
                cfunc.showMsgBox("No Item Balance to download", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                sbc.globalFunc.getClientItems(serveraddr, whs);
              }
            } else {
              $q.loading.hide();
            }
          }).catch(err => {
            cfunc.showMsgBox("error downloading item balance. " + err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
            $q.loading.hide();
          });
        }

        function saveItemBal (data, serveraddr) {
          var d = data;
          var dd = [];
          while (d.length) dd.push(d.splice(0, 100));
          save(dd);

          function save (itembal, index = 0) {
            cfunc.showLoading("Saving Item Balance (Batch " + index + " of " + itembal.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === itembal.length) {
              cfunc.showLoading("Successfully imported " + ibcount + " Item Balance");
              setTimeout(function () {
                $q.loading.hide();
                sbc.globalFunc.getClientItems(serveraddr, whs);
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in itembal[index]) {
                  insertItemBal(itembal[index][a]);
                  if (parseInt(a) + 1 === itembal[index].length) save(itembal, parseInt(index) + 1);
                }
              });
            }
          }

          function insertItemBal (data) {
            sbc.db.transaction(function (tx) {
              let qry = "insert into itembal(itemid, bal, wh) values(?, ?, ?)";
              let param = [data.itemid, data.bal, data.wh];
              tx.executeSql(qry, param, function (tx, res) {
                console.log("itembal saved");
              }, function (tx, err) {
                cfunc.saveErrLog(qry, param, err.message);
              });
            });
          }
        }

        // function getClientItems (serveraddr) {
        //   console.log("----getClientItems called");
        //   cfunc.showLoading("Downloading Client Items, Please wait...");
        //   api.post(serveraddr + "/sbcmobilev2/download", { type: "invClientItem", whs: whs, icend: icend }).then(res => {
        //     if (res) {
        //       if (res.data.clientitem.length > 0) {
        //         console.log("------icend: ", icend, "-----icsavedcount: ", icsavedcount);
        //         if (icend === 0) iccount = res.data.clientitem.length;
        //         if (icsavedcount !== iccount) {
        //           saveClientItem(res.data.clientitem, serveraddr);
        //         } else {
        //           cfunc.showLoading(`Successfully imported ${iccount} Client Items`);
        //           setTimeout(function () {
        //             $q.loading.hide();
        //             sbc.showSelectLookup = false;
        //             sbc.globalFunc.lookupSelected = [];
        //           }, 1500);
        //         }
        //         icend = res.data.icend;
        //         icsavedcount += res.data.clientitem.length;
        //       } else {
        //         if (icsavedcount !== iccount) {
        //           $q.loading.hide();
        //         } else {
        //           cfunc.showLoading(`Successfully imported ${iccount} Client Items`);
        //           setTimeout(function () {
        //             $q.loading.hide();
        //             sbc.showSelectLookup = false;
        //             sbc.globalFunc.lookupSelected = [];
        //           }, 1500);
        //         }
        //       }
        //     } else {
        //       $q.loading.hide();
        //     }
        //   }).catch(err => {
        //     cfunc.showMsgBox("Error downloading client items. " + err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
        //     $q.loading.hide();
        //   });
        // }

        // function saveClientItem (data) {
        //   console.log("----===----saveClientItem called");
        //   try {
        //     icdata = { data: { inserts: { clientitem: data } } };
        //     cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, icdata, {
        //       successFn: function () {
        //         console.log("------asdqwe----");
        //         getClientItems(serveraddr);
        //       },
        //       errorFn: function (error) {
        //         cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
        //         $q.loading.hide();
        //       },
        //       progressFn: function (current, total) {
        //         cfunc.showLoading("Saving Client Items (Batch " + current + " of " + total + ")");
        //       }
        //     })
        //   } catch (err) {
        //     console.log("-----------err: ", err);
        //     $q.loading.hide();
        //   }
        // }
      },
      saveClientItem: function (data, serveraddr, whs) {
        // console.log("----===----saveClientItem calledsss");
        // sbc.globalFunc.icdata = { data: { inserts: { clientitem: data } } };
        // cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, sbc.globalFunc.icdata, {
        //   successFn: function () {
        //     console.log("------asdqwe----");
        //     sbc.globalFunc.getClientItems(serveraddr, whs);
        //   },
        //   errorFn: function (error) {
        //     cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
        //     $q.loading.hide();
        //   },
        //   progressFn: function (current, total) {
        //     cfunc.showLoading("Saving Client Items (Batch " + current + " of " + total + ")");
        //   }
        // });

        let d = data;
        let dd = [];
        while (d.length) dd.push(d.splice(0, 100));
        save(dd);

        function save (clientitem, index = 0) {
          cfunc.showLoading("Saving Client Items (Batch " + index + " of " + clientitem.length + ")");
          if (index === 0) $q.loading.hide();
          if (index === clientitem.length) {
            cfunc.showLoading("Successfully imported " + sbc.globalFunc.iccount + " Client Items");
            setTimeout(function () {
              $q.loading.hide();
              sbc.showSelectLookup = false;
              sbc.globalFunc.lookupSelected = [];
            }, 1500);
          } else {
            sbc.db.transaction(function (tx) {
              for (var a in clientitem[index]) {
                insertClientItem(clientitem[index][a]);
                if (parseInt(a) + 1 === clientitem[index].length) save(clientitem, parseInt(index) + 1);
              }
            });
          }
        }

        function insertClientItem (data) {
          sbc.db.transaction(function (tx) {
            let qry = "insert into clientitem(wh, barcode, sku) values(?, ?, ?)";
            let param = [data.wh, data.barcode, data.sku];
            tx.executeSql(qry, param, null, function (tx, err) {
              cfunc.saveErrLog(qry, param, err.message);
            });
          });
        }
      },
      getClientItems: function (serveraddr, whs) {
        cfunc.showLoading("Downloading Client Items, Please wait...");
        api.post(serveraddr + "/sbcmobilev2/download", { type: "invClientItem", whs: whs }).then(res => {
          if (res) {
            if (res.data.clientitem.length > 0) {
              sbc.globalFunc.iccount = res.data.clientitem.length;
              sbc.globalFunc.saveClientItem(res.data.clientitem, serveraddr);
            } else {
              sbc.globalFunc.showErrMsg("No Client Item to download");
              $q.loading.hide();
              sbc.showSelectLookup = false;
              sbc.globalFunc.lookupSelected = [];
            }
          } else {
            $q.loading.hide();
          }
        });


        // let icdata = [];
        // let icend = 0;
        // let icsavedcount = 0;
        // let iccount = 0;

        // console.log("----getClientItems called---whs:", whs);
        // cfunc.showLoading("Downloading Client Items, Please wait...");
        // api.post(serveraddr + "/sbcmobilev2/download", { type: "invClientItem", whs: whs, icend: sbc.globalFunc.icend }).then(res => {
        //   if (res) {
        //     if (res.data.clientitem.length > 0) {
        //       if (sbc.globalFunc.icend === 0) sbc.globalFunc.iccount = res.data.clientitem.length;
        //       if (sbc.globalFunc.icsavedcount !== sbc.globalFunc.iccount) {
        //         sbc.globalFunc.saveClientItem(res.data.clientitem, serveraddr, whs);
        //       } else {
        //         cfunc.showLoading(`Successfully imported ${iccount} Client Items`);
        //         setTimeout(function () {
        //           $q.loading.hide();
        //           sbc.showSelectLookup = false;
        //           sbc.globalFunc.lookupSelected = [];
        //         }, 1500);
        //       }
        //       sbc.globalFunc.icend = res.data.icend;
        //       sbc.globalFunc.icsavedcount += res.data.clientitem.length;
        //     } else {
        //       if (sbc.globalFunc.icsavedcount !== sbc.globalFunc.iccount) {
        //         $q.loading.hide();
        //       } else {
        //         cfunc.showLoading(`Successfully imported ${sbc.globalFunc.iccount} Client Items`);
        //         setTimeout(function () {
        //           $q.loading.hide();
        //           sbc.showSelectLookup = false;
        //           sbc.globalFunc.lookupSelected = [];
        //         }, 1500);
        //       }
        //     }
        //   } else {
        //     $q.loading.hide();
        //   }
        // }).catch(err => {
        //   cfunc.showMsgBox("Error downloading client items. " + err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
        //   $q.loading.hide();
        // });
      },
      clearInvItems: function () {
        return new Promise((resolve) => {
          sbc.db.transaction(function (tx) {
            if (sbc.globalFunc.company === "mbs") {
              tx.executeSql("delete from head");
              tx.executeSql("delete from hhead");
              tx.executeSql("delete from stock");
              tx.executeSql("delete from hstock");
            }
            tx.executeSql("delete from wh");
            tx.executeSql("delete from item");
            tx.executeSql("delete from itembal");
            tx.executeSql("delete from clientitem");
            tx.executeSql("delete from mbssettings");
            resolve();
          });
        });
      },
      showErrMsg: function (msg = "") {
        $q.dialog({
          message: msg,
          dark: true,
          persistent: true,
          class: "bg-red-10",
          ok: { flat: true, color: "white" }
        });
      },
      playSound: function (err) {
        try {
          // let sounds = cfunc.errorSounds();
          // console.log("---===----: ", sounds["error1"]);
          // document.getElementById("soundDiv").innerHTML = `<audio id="audio-player" controls="controls" src=`" + sounds["error1"] + "` type="audio/mpeg" auto-play>`;
          // console.log("----errorSounds: ", sounds);

          // let my_media = new Media("assets/error1.mp3", function () {
          //   console.log("load sounds success");
          // },
          // function (err) {
          //   console.log("load sounds error: ", err);
          // });
          // my_media.play();

          // document.getElementById("soundPlayer").play();
          // document.write("<audio preload=`auto` autoplay><source src=`" + src + "` /></audio>");
          // document.getElementById("soundDiv").innerHtml = "<audio preload=`auto`><source src=`" + src + "` /></audio>";

          // const filePath = "/android_asset/www/sounds/error1.mp3";
          // let my_media = new Media(filePath, function () {
          //   console.log("error sound success");
          // }, function (err) {
          //   console.log("error playing sound: ", err);
          // }, function (waw) {
          //   console.log("media status: ", waw);
          // });
          // my_media.play();
        } catch (err) {
          console.log("----------------sound error: ", err);
          $q.loading.hide();
        }
      },
      searchInvWarehouse: function () {
        console.log("searchInvWarehouse called", sbc.modulefunc.txtSearchWarehouse);
        let sql = "select * from tempwh where 1=1 ";
        let strs = [];
        let f = "";
        let d = [];
        if (sbc.modulefunc.txtSearchWarehouse !== "") strs = sbc.modulefunc.txtSearchWarehouse.split(",");
          if (strs.length > 0) {
            for (var s in strs) {
              strs[s] = strs[s].trim();
              if (strs[s] !== "") {
                if (f !== "") {
                  f = f.concat(" and ((client like ?) or (clientname like ?)) ");
                } else {
                  f = f.concat(" ((client like ?) or (clientname like ?)) ");
                }
                d.push(["%" + strs[s] + "%", "%" + strs[s] + "%"]);
              }
            }
          }
          if (d.length === 0) {
            d = [];
            sql = sql.concat(" order by clientname ");
          } else {
            sql = sql.concat(" and (" + f + ") order by clientname");
          }
          var dd = [].concat.apply([], d);
          sbc.globalFunc.lookupData = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql(sql, dd, function (tx, res) {
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  sbc.globalFunc.lookupData.push(res.rows.item(x));
                }
                $q.loading.hide();
              } else {
                $q.loading.hide();
              }
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          }, function (err) {
            console.log("error: ", err.message);
          });
      },
      logout: function () {
        $q.dialog({
          message: "Do you want to logout?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          if (sbc.globalFunc.config === "timeinapp" && sbc.globalFunc.company === "sbc2") {
            cfunc.showLoading();
            sbc.db.transaction(function (tx) {
              const datenow = cfunc.getDateTime("datetime");
              const storage = $q.localStorage.getItem("sbcmobilev2Data");
              tx.executeSql("update guardlog set timeout=? where line=?", [datenow, storage.user.id], function (tx, res) {
                contFunc();
              }, function (tx, err) {
                $q.loading.hide();
                sbc.globalFunc.showErrMsg(err.message);
              });
            });
          } else {
            contFunc();
          }
        })

        function contFunc () {
          $q.loading.hide();
          $q.localStorage.remove("sbcmobilev2Data");
          $q.localStorage.remove("sbcmobilev2SelDoc");
          sbc.selDoc = [];
          sbc.doc = "";
          router.push({ path: "/" });
        }
      },
      timeinAppGuardLogin: function () {
        console.log("timeinAppGuardLogin called, data: ", sbc.globalFunc.manualLoginForm);
        if (sbc.globalFunc.manualLoginForm.guardname !== "" && sbc.globalFunc.manualLoginForm.guardname !== null && sbc.globalFunc.manualLoginForm.guardname !== undefined) {
          navigator.camera.getPicture(
            data => {
              cfunc.showLoading();
              const datenow = cfunc.getDateTime("datetime");
              sbc.db.transaction(function (tx) {
                tx.executeSql("insert into guardlog(name, timein, loginPic) values(?, ?, ?)", [sbc.globalFunc.manualLoginForm.guardname, datenow, data], function (tx, res) {
                  cfunc.getTableData("config", "serveraddr").then(serveraddr => {
                    $q.localStorage.remove("sbcmobilev2Data");
                    $q.localStorage.set("sbcmobilev2Data", { user: { id: res.insertId, username: sbc.globalFunc.manualLoginForm.guardname, password: "" }, url: serveraddr, center: [], type: sbc.globalFunc.config });
                    cfunc.showMsgBox("Login Success", "positive");
                    $q.loading.hide();
                    router.push("/");
                  });
                }, function (tx, err) {
                  $q.loading.hide();
                  sbc.globalFunc.showErrMsg(err.message);
                });
              });
            },
            () => {
              sbc.globalFunc.showErrMsg("Could not access device camera/image, capture cancelled.");
            },
            {
              destinationType: Camera.DestinationType.DATA_URL,
              cameraDirection: Camera.Direction.BACK,
              encodingType: Camera.EncodingType.JPEG
            }
          )
        }
      },
      viewheadtable: function () {
        sbc.lookupTitle = "Head Table";
        sbc.globalFunc.lookupData = [];
        sbc.globalFunc.lookupCols = [
          { name: "trno", label: "Trno", field: "trno", align: "left", sortable: true },
          { name: "wh", label: "Warehouse", field: "wh", align: "left", sortable: true },
          { name: "loc", label: "Location", field: "loc", align: "left", sortable: true },
          { name: "brand", label: "Brand", field: "brand", align: "left", sortable: true },
          { name: "dateid", label: "Date", field: "dateid", align: "left", sortable: true }
        ];
        sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search", func: "" };
        sbc.globalFunc.lookupTableSelect = false;
        sbc.showLookup = true;
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno, wh, loc, brand, dateid from head", [], function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.globalFunc.lookupData.push(res.rows.item(x));
              }
            }
          });
        });
      },
      viewhheadtable: function () {
        sbc.lookupTitle = "HHead Table";
        sbc.globalFunc.lookupData = [];
        sbc.globalFunc.lookupCols = [
          { name: "trno", label: "Trno", field: "trno", align: "left", sortable: true },
          { name: "wh", label: "Warehouse", field: "wh", align: "left", sortable: true },
          { name: "loc", label: "Location", field: "loc", align: "left", sortable: true },
          { name: "brand", label: "Brand", field: "brand", align: "left", sortable: true },
          { name: "dateid", label: "Date", field: "dateid", align: "left", sortable: true }
        ];
        sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search", func: "" };
        sbc.globalFunc.lookupTableSelect = false;
        sbc.showLookup = true;
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno, wh, loc, brand, dateid from hhead", [], function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.globalFunc.lookupData.push(res.rows.item(x));
              }
            }
          });
        });
      },
      viewstocktable: function () {
        sbc.lookupTitle = "Stock Table";
        sbc.globalFunc.lookupData = [];
        sbc.globalFunc.lookupCols = [
          { name: "trno", label: "Trno", field: "trno", align: "left" },
          { name: "line", label: "Line", field: "line", align: "left" },
          { name: "barcode", label: "Barcode", field: "barcode", align: "left" },
          { name: "sku", label: "SKU", field: "sku", align: "left" },
          { name: "itemname", label: "Itemname", field: "itemname", align: "left" },
          { name: "brand", label: "Brand", field: "brand", align: "left" },
          { name: "syscount", label: "Syscount", field: "syscount", align: "left" },
          { name: "qty", label: "Qty", field: "qty", align: "left" },
          { name: "variance", label: "variance", field: "variance", align: "left" },
          { name: "seq", label: "Seq", field: "seq", align: "left" },
          { name: "wh", label: "Warehouse", field: "wh", align: "left" },
          { name: "dateid", label: "Date", field: "dateid", align: "left" }
        ];
        sbc.globalFunc.lookupTableSelect = false;
        sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search", func: "" };
        sbc.showLookup = true;
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno, line, barcode, sku, itemname, brand, syscount, qty, variance, seq, wh, dateid from stock", [], function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.globalFunc.lookupData.push(res.rows.item(x));
              }
            }
          });
        });
      },
      viewhstocktable: function () {
        sbc.lookupTitle = "HStock Table";
        sbc.globalFunc.lookupData = [];
        sbc.globalFunc.lookupCols = [
          { name: "trno", label: "Trno", field: "trno", align: "left" },
          { name: "line", label: "Line", field: "line", align: "left" },
          { name: "barcode", label: "Barcode", field: "barcode", align: "left" },
          { name: "sku", label: "SKU", field: "sku", align: "left" },
          { name: "itemname", label: "Itemname", field: "itemname", align: "left" },
          { name: "brand", label: "Brand", field: "brand", align: "left" },
          { name: "syscount", label: "Syscount", field: "syscount", align: "left" },
          { name: "qty", label: "Qty", field: "qty", align: "left" },
          { name: "variance", label: "variance", field: "variance", align: "left" },
          { name: "seq", label: "Seq", field: "seq", align: "left" },
          { name: "wh", label: "Warehouse", field: "wh", align: "left" },
          { name: "dateid", label: "Date", field: "dateid", align: "left" }
        ];
        sbc.globalFunc.lookupTableSelect = false;
        sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search", func: "" };
        sbc.showLookup = true;
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno, line, barcode, sku, itemname, brand, syscount, qty, variance, seq, wh, dateid from hstock", [], function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.globalFunc.lookupData.push(res.rows.item(x));
              }
            }
          });
        });
      },
      onlinebranchAddress: function () {
        // let branchaddr = $q.localStorage.getItem("mbsBranchAddr");
        // if (branchaddr === undefined) branchaddr = "";
        let has1 = false;
        if (sbc.inputlookupfields.length > 0) {
          for (var x = 0; x < sbc.inputlookupfields.length; x++) {
            if (sbc.inputlookupfields[x].form === "serverbranchconfields") has1 = true;
            if (parseInt(x) + 1 === sbc.inputlookupfields.length) {
              if (!has1) {
                contFunc();
              } else {
                contFunc2();
              }
            }
          }
        } else {
          contFunc();
        }
        function contFunc () {
          sbc.inputlookupfields.push(
            { action: null, autofocus: false, class: null, dense: null, doc: "serverbranchcon", enterfunc: "checkOnlineConnection", fields: null, form: "serverbranchconfields", func: null, functype: "global", label: "Online Address", name: "onlineaddr", readonly: null, show: null, style: null, type: "inputbutton", icon: "check" },
            // { action: null, autofocus: false, class: null, dense: null, doc: "serverbranchcon", enterfunc: null, fields: null, form: "serverbranchconfields", func: "checkOnlineConnection", functype: "global", label: "check", name: "conlineaddr", readonly: null, show: null, style: "width:25px;", type: "button" },
            { action: null, autofocus: false, class: null, dense: null, doc: "serverbranchcon", enterfunc: "checkBranchConnection", fields: null, form: "serverbranchconfields", func: null, functype: "global", label: "Branch Address", name: "branchaddr", readonly: null, show: null, style: null, type: "inputbutton", icon: "check" },
            // { action: null, autofocus: false, class: null, dense: null, doc: "serverbranchcon", enterfunc: null, fields: null, form: "serverbranchconfields", func: "checkBranchConnection", functype: "global", label: "check", name: "cbranchaddr", readonly: null, show: null, style: null, type: "button" },
          );
          // sbc.inputlookupfieldsplot.push({ form: "serverbranchconfields", fields: ["onlineaddr", "conlineaddr"] });
          // sbc.inputlookupfieldsplot.push({ form: "serverbranchconfields", fields: ["branchaddr", "cbranchaddr"] });
          sbc.inputlookupfieldsplot.push({ form: "serverbranchconfields", fields: ["onlineaddr"] });
          sbc.inputlookupfieldsplot.push({ form: "serverbranchconfields", fields: ["branchaddr"] });
          sbc.inputlookupbuttons.push({ action: null, color: "primary", doc: "serverbranchcon", form: "serverbranchconbuttons", func: "okonlinebranchaddr", functype: "global", icon: null, label: "Ok", name: "okonlinebranchaddr", params: null });
          contFunc2();
        }

        function contFunc2 () {
          sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "serverbranchconfields", "inputFields");
          sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "serverbranchconfields", "inputPlot");
          sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "serverbranchconbuttons", "buttons");
          cfunc.getTableData("config", ["serveraddr", "branchaddr"], true).then(configdata => {
            let onlinecon = "";
            let branchcon = "";
            if (configdata.serveraddr !== undefined && configdata.serveraddr !== null) {
              onlinecon = configdata.serveraddr.replace("/mobileapi", "").replace("/waimsv2_backend/laravels", "").replace("https://", "").replace("http://", "");
            }
            if (configdata.branchaddr !== undefined && configdata.branchaddr !== null) {
              branchcon = configdata.branchaddr.replace("/mobileapi", "").replace("/waimsv2_backend/laravels", "").replace("https://", "").replace("http://", "");
            }
            sbc.modulefunc.inputLookupForm = { onlineaddr: onlinecon, branchaddr: branchcon };
            sbc.isFormEdit = true;
            sbc.inputLookupTitle = "Edit Server Address";
            sbc.showInputLookup = true;
          });
        }
      },
      checkOnlineConnection: function () {
        console.log("checkOnlineConnection called");
        if (sbc.modulefunc.inputLookupForm.onlineaddr !== "" && sbc.modulefunc.inputLookupForm.onlineaddr !== undefined && sbc.modulefunc.inputLookupForm.onlineaddr !== null) {
          cfunc.showLoading();
          const onlinecon = "http://" + sbc.modulefunc.inputLookupForm.onlineaddr + "/mobileapi";
          // const onlinecon = "http://" + sbc.modulefunc.inputLookupForm.onlineaddr + "/waimsv2_backend/laravels";
          // onlinecon = sbc.modulefunc.inputLookupForm.onlineaddr;
          api.get(onlinecon + "/checkconnection").then(res => {
            sbc.db.transaction(function (tx) {
              tx.executeSql("update config set serveraddr=?", [onlinecon], function (tx, res) {
                cfunc.showMsgBox("Online Address updated", "positive");
                mbsOnlineAddr.value = sbc.modulefunc.inputLookupForm.onlineaddr;
                $q.loading.hide();
              }, function (tx, err) {
                sbc.globalFunc.showErrMsg("Error updating online address: " + err.message);
                $q.loading.hide();
              });
            });
          }).catch(err => {
            sbc.globalFunc.showErrMsg(err.message + " err#1");
            $q.loading.hide();
          })
        }
      },
      checkBranchConnection: function () {
        if (sbc.modulefunc.inputLookupForm.branchaddr !== "" && sbc.modulefunc.inputLookupForm.branchaddr !== undefined && sbc.modulefunc.inputLookupForm.branchaddr !== null) {
          cfunc.showLoading();
          const branchcon = "http://" + sbc.modulefunc.inputLookupForm.branchaddr + "/mobileapi";
          // const branchcon = sbc.modulefunc.inputLookupForm.branchaddr;
          // const branchcon = "http://" + sbc.modulefunc.inputLookupForm.branchaddr + "/waimsv2_backend/laravels";
          api.get(branchcon + "/checkconnection").then(res => {
            sbc.db.transaction(function (tx) {
              tx.executeSql("update config set branchaddr=?", [branchcon], function (tx, res) {
                cfunc.showMsgBox("Branch Address updated", "positive");
                // mbsBranchAddr.value = sbc.modulefunc.inputLookupForm.branchaddr;
                $q.loading.hide();
              }, function (tx, err) {
                sbc.globalFunc.showErrMsg("Error updating branch address: " + err.message);
                $q.loading.hide();
              });
            });
          }).catch(err => {
            sbc.globalFunc.showErrMsg(err.message + "err#1");
            $q.loading.hide();
          });
        }
      },
      okonlinebranchaddr: function () {
        sbc.showInputLookup = false;
      },
      checkBackendVersion: function () {
        sbc.globalFunc.showErrMsg(sbc.globalFunc.backendVersion);
      },
      usersList: function () {
        sbc.lookuptitle = "Users List";
        sbc.globalFunc.lookupData = [];
        sbc.globalFunc.lookupCols = [
          { name: "username", label: "Username", field: "username", align: "left" },
          { name: "password", label: "Password", field: "password", align: "left" }
        ];
        sbc.globalFunc.lookupTableSelect = false;
        sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search", func: "" };
        sbc.showLookup = true;
        sbc.db.transaction(function (tx) {
          tx.executeSql("select username, password from useraccess", [], function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.globalFunc.lookupData.push(res.rows.item(x));
              }
            }
          });
        });
      },
      timeinAdminAppLogin: function () {
        console.log("--------------timeinAdminAppLogin called: ", sbc.globalFunc.manualLoginForm);
        if (sbc.globalFunc.manualLoginForm.email === "") {
          cfunc.showMsgBox("Please enter ID", "negative", "warning");
          return;
        }
        if (sbc.globalFunc.manualLoginForm.email === 0 || sbc.globalFunc.manualLoginForm.email === "0") {
          cfunc.showMsgBox("Invalid ID", "negative", "warning");
          return;
        }
        const regex = /[(]/;
        if (regex.test(sbc.globalFunc.manualLoginForm.email)) {
          let id = sbc.globalFunc.manualLoginForm.email.split("(");
          contFunc(id[0].trim());
        } else {
          contFunc(sbc.globalFunc.manualLoginForm.email);
        }
        function contFunc(idbarcode) {
          cfunc.showLoading();
          cfunc.getTableData("config", "serveraddr").then(serveraddr => {
            if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
              cfunc.showMsgBox("Server Address not set", "negative", "warning");
              $q.loading.hide();
              return;
            }
            sbc.db.transaction(function (tx) {
              tx.executeSql("select * from useraccess2 where cast(email as text)=? and password=? and isactive=1", [idbarcode.toString(), sbc.globalFunc.manualLoginForm.password], function (tx, res) {
                if (res.rows.length > 0) {
                  cfunc.getTableData("config", "serveraddr", false).then(serveraddr => {
                    let storage = {
                      username: res.rows.item(0).email,
                      password: res.rows.item(0).password,
                      id: res.rows.item(0).id,
                      name: res.rows.item(0).name
                    };
                    api.post(serveraddr + "/sbcmobilev2/updateEToken", { token: fcmToken.value, storage: storage }).then(res2 => {
                      if (res2.data.status) {
                        cfunc.showMsgBox("Login success", "positive");
                        $q.localStorage.set("sbcmobilev2Data", { user: storage, url: serveraddr, center: [], type: sbc.globalFunc.config });
                        router.push("/");
                      } else {
                        cfunc.showMsgBox("Login error: failed to update Token: " + res2.data.msg, "negative", "warning");
                        $q.loading.hide();
                      }
                    }).catch(err => {
                      cfunc.showMsgBox(err.message, "negative", "warning");
                      $q.loading.hide();
                    });
                  });
                } else {
                  cfunc.showMsgBox("Invalid user", "negative", "warning");
                  $q.loading.hide();
                }
              }, function (tx, err) {
                cfunc.showMsgBox("Login error1: " + err.message, "negative", "warning");
                $q.loading.hide();
              });
            });
          });
        }
      }
    })';
    return $functions;
  }
}
