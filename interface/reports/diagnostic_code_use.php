<?php

/**
 * Version 1.0.0 september 2023
 *
 * Report of use of diagnostic codes - with option to output as a csv file
 *
 * created by Ruth Moulton originally from report Patient_list.php authors Rod Roark and Brady Miller September 2023
  *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Ruth Moulton <ruth@muswell.me.uk>
 * @author    Rod Roark <rod@sunsetsystems.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2006-2016 Rod Roark <rod@sunsetsystems.com>
 * @copyright Copyright (c) 2017-2018 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */
/*
 * 'lists' table in db holds info about medical issues per patient
 */

require_once("../globals.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/options.inc.php");

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;
use OpenEMR\Common\Logging\SystemLogger;

if (!empty($_POST)) {
    if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
        CsrfUtils::csrfNotVerified();
    }
}

$from_date  = (!empty($_POST['form_from_date'])) ? DateToYYYYMMDD($_POST['form_from_date']) : date('Y-01-01');
$to_date    = (!empty($_POST['form_to_date'])) ? DateToYYYYMMDD($_POST['form_to_date']) : date('Y-m-d');

$form_provider = empty($_POST['form_provider']) ? 0 : intval($_POST['form_provider']);
$form_gender = empty($_POST['form_sex']) ? 0 : text($_POST['form_sex']);

$form_code = empty($_POST['form_code']) ? 0 : text($_POST['form_code']);
$form_code_description = empty($_POST['form_code_description']) ? 'no description' : text($_POST['form_code_description']);

$form_age_range = empty($_POST['form_age_range']) ? 0 : intval($_POST['form_age_range']);

$report_title = xl("Diagnostic Code Use");

// address for find code pop up
$url = '';

(new SystemLogger())->debug("lets go: ",array( $form_gender, $form_age_range, $from_Date, $to_date, $form_code ));

?>
<script>

function selectCodes(msg) {
   // alert("in select codes" + msg);

            <?php
            $url = '../patient_file/encounter/select_codes.php?codetype=';

            ?>
            dlgopen(<?php echo js_escape($url); ?>, '_blank', 985, 800, '', <?php echo xlj("Select Codes"); ?> )
        }

 // call back for select_codes
 function OnCodeSelected(codetype, code, selector, codedesc) {
            var codeKey = codetype + ':' + code
 alert(code + " " + codedesc)
            var f = document.forms[0]
        //    if (f.form_title.value == '') {
        //        f.form_title.value = codedesc;
         //   }
            let data = new FormData();
 //   for (i = 0; i<f.length; i++){
 //       data.append(f[i].name, f[i].value);
 //   }
         //   $_POST['form_code'] = "123test";
    data.append("form_code", code);
    data.append("form_code_description",codedesc);
    fetch('#',
        {method: "POST",
            body: data
        })
    .then (response => response.text())
    .then ( (response) =>
            {
                document.body.innerHTML = response;
            });
   // alert("post response " + .then(response) );
   f.submit();
  //  dlgclose();
    }



</script>
<?php

// In the case of CSV export only, a download will be forced.
if (!empty($_POST['form_csvexport'])) {
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=diagnostic_code_use.csv");
    header("Content-Description: File Transfer");
} else {
    ?>
<html>
<head>

<title><?php echo text($report_title); ?></title>



    <?php Header::setupHeader(['datetime-picker', 'report-helper']); ?>

<script>

$(function () {
    oeFixedHeaderSetup(document.getElementById('mymaintable'));
    top.printLogSetup(document.getElementById('printbutton'));

    $('.datepicker').datetimepicker({
        <?php $datetimepicker_timepicker = false; ?>
        <?php $datetimepicker_showseconds = false; ?>
        <?php $datetimepicker_formatInput = true; ?>
        <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
        <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
    });
});

</script>

<style>

/* specifically include & exclude from printing */
@media print {
    #report_parameters {
        visibility: hidden;
        display: none;
    }
    #report_parameters_daterange {
        visibility: visible;
        display: inline;
        margin-bottom: 10px;
    }
    #report_results table {
       margin-top: 0px;
    }
}

/* specifically exclude some from the screen */
@media screen {
    #report_parameters_daterange {
        visibility: hidden;
        display: none;
    }
    #report_results {
        width: 100%;
    }
}

</style>

</head>

<body class="body_top">

<!-- Required for the popup date selectors -->
<div id="overDiv" style="position: absolute; visibility: hidden; z-index: 1000;"></div>

<span class='title'><?php echo xlt('Report'); ?> - <?php echo text($report_title);   ?></span>

<div id="report_parameters_daterange">
    <?php if (!(empty($to_date) && empty($from_date))) { ?>
        <?php echo text(oeFormatShortDate($from_date)) . " &nbsp; " . xlt('to{{Range}}') . " &nbsp; " . text(oeFormatShortDate($to_date)); ?>
<?php } ?>
</div>

<form name='theform' id='theform' method='post' action='diagnostic_code_use.php' onsubmit='return top.restoreSession()'>
<input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />

<div id="report_parameters">

<input type='hidden' name='form_refresh' id='form_refresh' value=''/>
<input type='hidden' name='form_csvexport' id='form_csvexport' value=''/>

<table>
 <tr>
  <td width='60%'>
    <div style='float:left'>

    <table class='text'>
        <tr>
      <td class='col-form-label'>
        <?php echo xlt('Provider'); ?>:
      </td>
      <td>
            <?php
            generate_form_field(array('data_type' => 10, 'field_id' => 'provider', 'empty_title' => '-- All --'), ($_POST['form_provider'] ?? ''));
            ?>
      </td>
            <td class='col-form-label'>
                <?php echo xlt('Visits From'); ?>:
            </td>
            <td>
               <input class='datepicker form-control' type='text' name='form_from_date' id="form_from_date" size='10' value='<?php echo attr(oeFormatShortDate($from_date)); ?>'>
            </td>
            <td class='col-form-label'>
                <?php echo xlt('To{{Range}}'); ?>:
            </td>
            <td>
               <input class='datepicker form-control' type='text' name='form_to_date' id="form_to_date" size='10' value='<?php echo attr(oeFormatShortDate($to_date)); ?>'>
            </td>
            <td class='col-form-label'>
                <?php echo xlt('Gender'); ?>:
            </td>
            <td>
                <?php
                generate_form_field(array('data_type' => 1, 'list_id' => 'sex', 'field_id' => 'sex', 'empty_title' => 'Any', 'description' => 'patient gender'), ($_POST['form_sex'] ?? ''));
                ?>
            </td>
             <td class='col-form-label'>
                <?php echo xlt('Age Group'); ?>:
            </td>
            <td>

                <select name="form_age_range" id="form_age_range">
                    <option value="1"> 30 or under</option>
                    <option value="2">31-40</option>
                    <option value="3">41-50</option>
                    <option value="4">51-60</option>
                    <option value="5">61-70</option>
                    <option value="6"> over 70 </option>
                </select>
            </td>
            </tr>
            <tr>
            <td class='col-form-label'>
                <?php echo xlt('Codes'); ?>:
            </td>
             <td>
              <div class="btn-group" role="group">
                <a href='#' class='btn btn-secondary' style="margin-right:5px;" onclick='selectCodes();'> <?php echo xlt('select codes');?>  </a>
                </div>
            </td>

        </tr>
    </table>

    </div>

  </td>
  <td class="h-100" align='left' valign='middle'>
    <table class="w-100 h-100" style='border-left: 1px solid;'>
        <tr>
            <td>
        <div class="text-center">
                  <div class="btn-group" role="group">
                    <a href='#' class='btn btn-secondary btn-save' onclick='$("#form_csvexport").val(""); $("#form_refresh").attr("value","true"); $("#theform").submit();'>
                        <?php echo xlt('Submit'); ?>
                    </a>
                    <?php if (!empty($_POST['form_refresh'])) { ?>
                    <a href='#' class='btn btn-secondary btn-transmit' onclick='$("#form_csvexport").attr("value","true");  alert(<?php echo xlj('The destination form was closed.'); ?>);'>
                        <?php echo xlt('Export to CSV'); ?>
                    </a>
                      <a href='#' id='printbutton' class='btn btn-secondary btn-print'>
                            <?php echo xlt('Print'); ?>
                      </a>
                    <?php } ?>
              </div>
        </div>
            </td>
        </tr>
    </table>
  </td>
 </tr>
</table>
</div> <!-- end of parameters -->

    <?php
} // end not form_csvexport

if (!empty($_POST['form_refresh']) || !empty($_POST['form_csvexport'])) {
    if ($_POST['form_csvexport']) {
        // CSV headers:
        echo csvEscape(xl('ID')) . ',';
        echo csvEscape(xl('Last Visit')) . ',';
        echo csvEscape(xl('Facility')) . ',';
        echo csvEscape(xl('Encounter Provider')) . ',';
        echo csvEscape(xl('Last{{Name}}')) . ',';
        echo csvEscape(xl('First{{Name}}')) . ',';
        echo csvEscape(xl('Date of Birth')) . ',';
        echo csvEscape(xl('Gender')) . ',';
        echo csvEscape(xl('Reason for encounter')) . "\n";
    } else {
        ?>

  <div id="report_results">
  <table class='table' id='mymaintable'>
   <thead class='thead-light'>
    <th> <?php echo xlt('ID'); ?> </th>
     <th> <?php echo xlt('Issue Date'); ?> </th>
    <th> <?php echo xlt('Issue Provider'); ?> </th>
    <th> <?php echo xlt('Patient'); ?> </th>
    <th> <?php echo xlt('Date of Birth'); ?> </th>
     <th> <?php echo xlt('Gender'); ?> </th>
     <th> <?php echo xlt('Code'); ?> </th>
     <th> <?php echo xlt('Description'); ?> </th>

   </thead>
 <tbody>
        <?php
    } // end not export
    $totalpts = 0;
    $sqlArrayBind = array();
    $query = "SELECT " .
    "p.fname, p.mname, p.lname, " .
   // "p.pid, p.pubpid, p.DOB, p.sex, " .
    "p.pid, p.pubpid, p.DOB, p.sex " .
   //  .   "count(l.date) AS lcount, max(l.date) AS ldate " .
   // "i1.date AS idate1, i2.date AS idate2, " .
   // "c1.name AS cname1, c2.name AS cname2 " .
    "FROM patient_data AS p " ;

    // can asign a medical problem directly from demographic, or else from an encounter. i think 'provider' is only recorded in an encounter
    // so only display if medical issue is in both 'lists' and and encounter - match by date perhaps?
  //  if (!empty($from_date)) {
    //    $query .= "JOIN lists AS l ON " .
    //    "l.pid = p.pid AND " .
    //    "l.date >= ? AND " .
     //   "l.date <= ? ";
    //    array_push($sqlArrayBind, $from_date . ' 00:00:00', $to_date . ' 23:59:59');
        // lists has field 'user', but not 'provider'
     //   if ($form_provider) {
   //         $query .= "AND l.user = ? ";
      //      array_push($sqlArrayBind, $form_provider);
     //   }
  //  } else {
    //    if ($form_provider) {
 //           $query .= "JOIN lists AS l ON " .
 //           "l.pid = p.pid AND e.provider_id = ? ";
 //           array_push($sqlArrayBind, $form_provider);
   //     } else {
  //          $query .= "LEFT OUTER JOIN lists AS l ON " .
  //          "l.pid = p.pid ";
      //  }
  //  }
    if ($form_gender != 'Any'){
          array_push($sqlArrayBind, $form_gender);
         $query .= "WHERE p.sex =? " ;
    }

   (new SystemLogger())->debug("diag Query: ",array( $query ));
    $res = sqlStatement($query, $sqlArrayBind);

    $prevpid = 0;
    while ($row = sqlFetchArray($res)) {

        (new SystemLogger())->debug("query res pid: ",$row );

        if ($row['pid'] == $prevpid) {
            continue;
        }
        $prevpid = $row['pid'];
        $age = '';
        if (!empty($row['DOB'])) {
            $dob = $row['DOB'];
            $tdy = $row['ldate'] ? $row['ldate'] : date('Y-m-d');
            $ageInMonths = (substr($tdy, 0, 4) * 12) + substr($tdy, 5, 2) -
                   (substr($dob, 0, 4) * 12) - substr($dob, 5, 2);
            $dayDiff = substr($tdy, 8, 2) - substr($dob, 8, 2);
            if ($dayDiff < 0) {
                --$ageInMonths;
            }

            $age = intval($ageInMonths / 12);
        }
        $sqlArrayBind = array();
        $en_pid = $row['pid'];
        $sqlArrayBind[] = $en_pid;
        $equery = "SELECT " .
            "facility, provider_id, reason, date " .
            "FROM form_encounter " .
            "WHERE pid = ? ";
        $eres = sqlStatement($equery, $sqlArrayBind);

        // get provider name
        $sqlArrayBind = array();
        $providerID = $erow['provider_id'];
        $sqlArrayBind[] = $providerID;
        $pquery = "SELECT " . "fname, lname FROM users WHERE id = ?";
        $pres = sqlStatement($pquery, $sqlArrayBind);
        $prow = sqlFetchArray($pres);

        if ($_POST['form_csvexport']) {
            echo csvEscape($row['pubpid']) . ',';
            // format dates by users preference
            echo csvEscape(oeFormatDateTime($row['edate'], "global", false)) . ',';
            echo csvEscape($erow['facility']) . ',';
            echo csvEscape($prow['fname'] . " " . $prow['lname']) . ',';
            echo csvEscape($row['lname']) . ',';
            echo csvEscape($row['fname']) . ',';
            echo csvEscape(oeFormatShortDate(substr($row['DOB'], 0, 10))) . ',';
            echo csvEscape($row['sex']) . ',';
            echo csvEscape($erow['reason']) . "\n";
        } else {
            ?>
        <tr>
            <td>
                <?php echo text($row['pubpid']); ?>
            </td>
            <td>
                <?php echo text(oeFormatDateTime($row['edate'], "global", false)) ;?>
            </td>

            <td>
                 <?php echo text($prow['fname'] . ' ' . $prow['lname']); ?>
            </td>
            <td>
                <?php echo text($row['lname'] . ', ' . $row['fname']); ?>
            </td>
            <td>
                <?php echo text(oeFormatShortDate(substr($row['DOB'], 0, 10))); ?>
            </td>
            <td>
            <?php echo text($row['sex']); ?>
            </td>
             <td>
                <?php echo text("code is ".$form_code); ?>
            </td> <td>
                <?php echo text("$form_code_description"); ?>
            </td>

            <td>
            <?php echo text($erow['reason']); ?>
            </td>

        </tr>
            <?php
        } // end not export
        ++$totalpts;
    } // end while
    if (!$_POST['form_csvexport']) {
        ?>

   <tr class="report_totals">
    <td colspan='9'>
        <?php echo xlt('Total Number of Patients'); ?>
   :
        <?php echo text($totalpts); ?>
  </td>
 </tr>

</tbody>
</table>
</div> <!-- end of results -->
        <?php
    } // end not export
} // end if refresh or export

if (empty($_POST['form_refresh']) && empty($_POST['form_csvexport'])) {
    ?>
<div class='text'>
    <?php echo xlt('Please input search criteria above, and click Submit to view results.'); ?>
</div>
    <?php
}

if (empty($_POST['form_csvexport'])) {
    ?>

</form>
</body>

</html>
    <?php
} // end not export
?>
