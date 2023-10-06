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
/* srcdir is openemr/library */
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

$form_codes = empty($_POST['form_codes']) ? 0 : $_POST['form_codes'];
$form_code_types = empty($_POST['form_code_types']) ? 'no description' : $_POST['form_code_types'];

$form_age_range = empty($_POST['form_age_range']) ? 0 : text($_POST['form_age_range']);

$report_title = xl("Diagnostic Code Use");

// address for find code pop up
$url = '';

(new SystemLogger())->debug("lets go: ",array( $form_gender, $form_age_range, $from_Date, $to_date, $form_codes, $form_code_description ));

if (empty($_POST['form_csvexport'])) {

?>
<script>

function selectCodes() {
   // alert("in select codes" );
            <?php
            $url = '../patient_file/encounter/select_codes.php?codetype=';
            ?>
            dlgopen(<?php echo js_escape($url); ?>, '_blank', 985, 800, '', <?php echo xlj("Select Codes"); ?> )
 }

 var form_code_list = [];
 var form_code_type_list = []

 // call back for select_codes
 function OnCodeSelected(codetype, code, selector, codedesc) {
 //   alert(codetype + " " + code + " " + selector + " " + codedesc)
       var f = document.forms[0]

       form_code_list.push(code)
        form_code_type_list.push(codetype)
       f['form_codes'].value = form_code_list
       f['form_code_types'].value = form_code_type_list

      }

</script>
<?php
}
// In the case of CSV export only, a download will be forced.
if (!empty($_POST['form_csvexport'])) {
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/force-download");
    $today = getdate()['year']  . getdate()['mon'] . getdate()['mday'] ;
    $today = text($today);
    $filename = "diagnostic_code_use" . "_" . $GLOBALS['openemr_name'] . "_" .  $today . ".csv" ;
    header("Content-Disposition: attachment; filename=" . $filename . '"');
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
    <?php if (!(empty($to_date) && empty($from_date))) {
         echo text(oeFormatShortDate($from_date)) . " &nbsp; " . xlt('to{{Range}}') . " &nbsp; " . text(oeFormatShortDate($to_date));
         } ?>
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

                <select name="form_age_range" id="form_age_range" class="form-control">
                    <option value="0" > All ages </option>
                    <option> -------
                     <option value="0-05"> Under-fives (5 years or  younger)</option>
                     <option value="00-15">Children (0-15)</option>
                     <option value="16-00">Adults (16 or older)</option>
                     <option value="65-00">Elderly (65+)</option>
                     <option> -------
                    <option value="00-02">0-2 years of age</option>
                    <option value="03-05">3-5 years of age</option>
                    <option value="06-15">6-15 years of age </option>
                    <option value="16-25">16-25 years of age</option>
                    <option value="26-40">26-40 years of age</option>
                    <option value="41-60">41-60 years of age</option>
                    <option value="61-80">61-80 years of age</option>
                    <option value="81-00"> 81 years of age or older </option>
                    <option> -------
                   </select>
            </td>
            </tr>
            <tr>
            <td class='col-form-label'>
                <?php echo xlt('Codes'); ?>:
            </td>
             <td>
             <input type='hidden' name='form_codes' id='form_codes' value='' />
                <input type='hidden' name='form_code_types' id='form_code_types' value='' />
             <input type='hidden' name='form_code_description' id='form_code_description' value='' />
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
                    <a href='#' class='btn btn-secondary btn-transmit' onclick='$("#form_csvexport").attr("value","true"); $("#theform").submit();' >
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
        echo csvEscape(xl('Issue Date')) . ',';
        echo csvEscape(xl('Provider')) . ',';
        echo csvEscape(xl('Patient Last Name')) . ',';
        echo csvEscape(xl('Paient First Name')) . ',';
        echo csvEscape(xl('Date of Birth')) . ',';
        echo csvEscape(xl('Gender')) . ',';
        echo csvEscape(xl('Code set')) . ',';
          echo csvEscape(xl('Code')) . ',';
        echo csvEscape(xl('Description')) . "\n";
    } else {
        ?>
        <br> To sort on other columns please use the CSV file </br>
  <script>
        var sel = document.getElementById('form_age_range');
        val = sel.value
     /*   document.getElementById('form_age_range').onclick = function() {
            var opts = sel.options;
            for (var opt, j = 0; opt = opts[j]; j++) {
                if (opt.value == val) {
                    sel.selectedIndex = j;
                    break;
                }
            }
        }
        */
</script>

  <div id="report_results">
  <table class='table' id='mymaintable'>
   <thead class='thead-light'>
    <th> <?php echo xlt('ID'); ?> </th>
     <th> <?php echo xlt('Issue Date'); ?> </th>
    <th> <?php echo xlt('Provider'); ?> </th>
    <th> <?php echo xlt('Patient'); ?> </th>
    <th> <?php echo xlt('Date of Birth'); ?> </th>
     <th> <?php echo xlt('Gender'); ?> </th>
     <th> <?php echo xlt('Code Set'); ?> </th>
      <th> <?php echo xlt('Code'); ?> </th>
     <th> <?php echo xlt('Description'); ?> </th>

   </thead>
 <tbody>
        <?php

    // disply chosen codes etc
    echo ("<br>" . $form_codes . " " . $form_age_range . "</>");
    } //end not csv export

    $totalpts = 0;
    $sqlArrayBind = array();
    $query = "SELECT " .
    "p.fname, p.mname, p.lname, p.providerID, " .
   // "p.pid, p.pubpid, p.DOB, p.sex, " .
    "p.pid, p.pubpid, p.DOB, p.sex, l.diagnosis, l.title, l.date " ;

   $query .= "FROM patient_data AS p " .
             "JOIN lists AS l ON " .
            "l.pid = p.pid " ;

    if (!empty($from_date)) {
        $query .= "AND l.date >= ? AND  l.date <= ? ";
        array_push($sqlArrayBind, $from_date . ' 00:00:00', $to_date . ' 23:59:59');
    }
    if (!empty($form_codes)){
       // make an array of desired codes
       $req_codes = explode(",", $form_codes);
       $first = true;
       $query .= " WHERE " ;
       foreach ($req_codes as $value){
            if ($first){
            $query .= " l.diagnosis LIKE " . "'" . "%" . $value . "'" . ' ';
            $first = false;
         } else {
            $query .= "OR l.diagnosis LIKE " . "'" . "%" . $value . "'" . ' ';
        }
     }
    }
    if (!empty($form_gender) ){
        if ( empty($form_codes)){
          array_push($sqlArrayBind, $form_gender);
          $query .= "WHERE p.sex =? " ;
        }
        else {
            array_push($sqlArrayBind, $form_gender);
            $query .= "AND p.sex =? " ;
        }
    }
    $query .= "ORDER BY p.lname ASC";
   (new SystemLogger())->debug("Query: ",array( $query , $sqlArrayBind));
    $res = sqlStatement($query, $sqlArrayBind);


    while ($row = sqlFetchArray($res)) {

        (new SystemLogger())->debug("query res pid: ",$row );

        // calculate patient's age in years and compare to selected ages if requested
        if (!empty($form_age_range) && $form_age_range != "0"){
                (new SystemLogger())->debug("dob ",$row );
            if (empty($row['DOB'])) {
             continue; //ignore this record as no dob to check against requested age range
            }
            $dob = $row['DOB'];
            $tdy = date('Y-m-d');
            $age = '';
            $ageInMonths = (substr($tdy, 0, 4) * 12) + substr($tdy, 5, 2) -
                   (substr($dob, 0, 4) * 12) - substr($dob, 5, 2);
            $dayDiff = substr($tdy, 8, 2) - substr($dob, 8, 2);
            if ($dayDiff < 0) {
                --$ageInMonths;
            }
            $age = intval($ageInMonths / 12);

            $upper_range = intval(substr($form_age_range,strpos($form_age_range,"-")+1,2));
            $lower_range = intval(substr($form_age_range,0,2));

    (new SystemLogger())->debug("age & ranges ",array($age,$upper_range,$lower_range, $form_age_range) );
            if ($upper_range != 0){
                 if ($age > $upper_range || $age <= $lower_range || $age < $lower_range){
                         continue;
                 }
            }
        }
// get provider name
        if (!empty($form_provider)) {
            if ($form_provider != $row['providerID'])
                continue;
        }
        $sqlArrayBind = array();
        $providerID = $row['providerID'];
        $sqlArrayBind[] = $providerID;
        $pquery = "SELECT " . "fname, lname FROM users WHERE id = ?";
        $pres = sqlStatement($pquery, $sqlArrayBind);
        $prow = sqlFetchArray($pres);

        $prfname = $prow['fname'];
        $prlname = $prow['lname'];

        // get code type label

        // if more than one issue is recorded at same time, they are recorded in a single record - each separated by ';'
        // get ';' separated list of codes stripped of code type info
        // generate a record for each of the codes in the list
        $code = '';
       // $codeType = '';
        $diagnoses = explode(';', $row['diagnosis']);
        foreach ($diagnoses as $value) {
            $str = explode (':', $value);
            $code = $str[1];
            if (!empty ($form_codes)){
                if (!str_contains($form_codes,$code )){
                    continue;
                } //is each code in the list of required codes
            }
            $codeKey = $str[0];
            $sqlArrayBind = array();
            $sqlArrayBind[] = $codeKey;
     //    (new SystemLogger())->debug("codes: ",array( $codeType , $sqlArrayBind));
            $cquery = "SELECT ct_label FROM code_types WHERE ct_key = ?";
            $cres = sqlStatement($cquery, $sqlArrayBind);
            $crow = sqlFetchArray($cres);
           // $codeType = $crow['ct_label'];

            if ($_POST['form_csvexport']) {
                echo csvEscape($row['pubpid']) . ',';
                // format dates by users preference
                echo csvEscape(oeFormatDateTime($row['date'], "global", false)) . ',';
                echo csvEscape($prfname . " " . $prlname) . ',';
                echo csvEscape($row['lname']) . ',';
                echo csvEscape($row['fname']) . ',';
                echo csvEscape($row['mname']) . ',';
                echo csvEscape(oeFormatShortDate(substr($row['DOB'], 0, 10))) . ',';
                echo csvEscape($row['sex']) . ',';
                echo csvEscape($crow['ct_label']) . ',';
                echo csvEscape($code) . ',';
                echo csvEscape($row['title']) . "\n";
            } else {
            ?>
        <tr>
            <td>
                <?php echo text($row['pubpid']); ?>
            </td>
            <td>
                <?php echo text(oeFormatShortDate($row['date'], "global", false)) ;?>
            </td>
            <td>
                <?php echo text($prfname . " " . $prlname); ?>
            </td>

            <td>
                <?php echo text($row['lname'] . ', ' . $row['fname'] . ' ' . $row['mname']); ?>
            </td>
            <td>
                <?php echo text(oeFormatShortDate(substr($row['DOB'], 0, 10))); ?>
            </td>
            <td>
            <?php echo text($row['sex']); ?>
            </td>
             <td>
                <?php echo text($crow['ct_label']); /* code */?>
            </td>
             <td>
                <?php echo /*text($row['diagnosis']);*/ text($code) ; /* code */?>
            </td> <td>
                <?php echo text($row['title']); /* description */ ?>
            </td>
        </tr>
            <?php
        } // end not export
        ++$totalpts;
        } //end each diagnosis code
    } // end while
    if (!$_POST['form_csvexport']) {
        ?>

   <tr class="report_totals">
    <td colspan='9'>
        <?php echo xlt('Total Number of Records'); ?>
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
