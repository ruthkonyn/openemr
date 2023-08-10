<?php

/**
 * Upload and install a designated code set to the codes table.
 * used from 'admin/coding/native data loads'
 *
 * added code set ICD10_RVP Summary.
 * version 1.1
 *
 * code sets themselves are loaded from client system, can be stored in a folder contrib/<code set name in lower case>, e.g. contrib/icd10_rvp
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Rod Roark <rod@sunsetsystems.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @author    ruth moulton <ruth@muswell.me.uk>
 * @copyright Copyright (c) 2014 Rod Roark <rod@sunsetsystems.com>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

set_time_limit(0);

require_once('../globals.php');
require_once($GLOBALS['fileroot'] . '/custom/code_types.inc.php');

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Twig\TwigContainer;
use OpenEMR\Core\Header;

use OpenEMR\Common\Logging\SystemLogger;

if (!AclMain::aclCheckCore('admin', 'super')) {
    echo (new TwigContainer(null, $GLOBALS['kernel']))->getTwig()->render('core/unauthorized.html.twig', ['pageTitle' => xl("Install Code Set")]);
    exit;
}

$form_replace = !empty($_POST['form_replace']);
$code_type = empty($_POST['form_code_type']) ? '' : $_POST['form_code_type'];
global $eres; /* file handle*/

(new SystemLogger)->debug("_POST is:",  $_POST);
(new SystemLogger)->debug("_FILES is:", $_FILES);

/* (new SystemLogger)->debug("code_types array:", $code_types); */

?>
<html>

<head>
<title><?php echo xlt('Install Code Set'); ?></title>
<?php Header::setupHeader(); ?>

<style>
 .dehead {
   color: var(--black);
   font-family: sans-serif;
   font-size: 0.8125rem;
   font-weight: bold;
  }
 .detail {
   color: var(--black);
   font-family: sans-serif;
   font-size: 0.8125rem;
   font-weight:normal;
 }
</style>

</head>

<body class="body_top">

<?php

// Handle uploads.
if (!empty($_POST['bn_upload'])) {
    //verify csrf
    if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
        CsrfUtils::csrfNotVerified();
    }

    if (empty($code_types[$code_type])) {
        die(xlt('Code type not yet defined') . ": '" . text($code_type) . "'");
    }

    $code_type_id = $code_types[$code_type]['id'];
    $tmp_name = $_FILES['form_file']['tmp_name'];

    $full_path = $GLOBALS['fileroot']. '/contrib/'. strtolower($code_type) ;

    $inscount = 0;
    $repcount = 0;
    $seen_codes = array();

    /* whether or not it's a zip file   */
    $zip = $_FILES['form_file']['type'] == 'application/zip' ? TRUE : FALSE ;
    $csv = $_FILES['form_file']['type'] == 'text/csv' ? TRUE : FALSE ;
    $eres = null;

    if (is_uploaded_file($tmp_name) && $_FILES['form_file']['size']) {
      if ($zip) {
        $zipin = new ZipArchive();

        if ($zipin->open($tmp_name) === true) {
            // Must be a zip archive.
            for ($i = 0; $i < $zipin->numFiles; ++$i) {
                $ename = $zipin->getNameIndex($i);
                // TBD: Expand the following test as other code types are supported.
                if ($code_type == 'RXCUI' && basename($ename) == 'RXNCONSO.RRF') {
                    $eres = $zipin->getStream($ename);
                    $zip = TRUE;
                    break;
                }
            }
        }
      } /* not zip */
      else if ($csv) {
            $eres = fopen( $tmp_name, 'r');

            if (!$eres){
                die(xlt('Unable to open file ' . $_FILES['form_file']['name'] . " from: " . $tmp_name ));
            }
        }

        if (empty($eres)) {
            die(xlt('Unable to locate the data in this file.'));
        }

        if ($form_replace) {
            sqlStatement("DELETE FROM codes WHERE code_type = ?", array($code_type_id));
        }

        // Settings to drastically speed up import with InnoDB
        sqlStatementNoLog("SET autocommit=0");
        sqlStatementNoLog("START TRANSACTION");

        while (($line = fgets($eres)) !== false) {

            if ($code_type == 'RXCUI') {
                $a = explode('|', $line);
                if (count($a) < 18) {
                    continue;
                }

                if ($a[17] != '4096') {
                    continue;
                }

                if ($a[11] != 'RXNORM') {
                    continue;
                }
            }
            else if ( $code_type == 'ICD10_RVP')
                /* csv file - <code>:<description> */
                {
                    $a = explode(':', $line);
                    if (count($a) >= 3) {
                        die(xlt('bad record format ' . $line));
                    continue;
                    }
                }

                $code = $a[0];
                if (isset($seen_codes[$code])) {
                    continue;
                }

                $seen_codes[$code] = 1;
                ++$inscount;
                if (!$form_replace) {
                    $tmp = sqlQuery(
                        "SELECT id FROM codes WHERE code_type = ? AND code = ? LIMIT 1",
                        array($code_type_id, $code)
                    );
                    if ($tmp['id']) {
                              sqlStatementNoLog(
                                  "UPDATE codes SET code_text = ? WHERE code_type = ? AND code = ?",
                                  array($a[14], $code_type_id, $code)
                              );
                              ++$repcount;
                              continue;
                    }
                } /* end if not to be replaced */
                   if ($code_type == 'RXCUI') {
                sqlStatementNoLog(
                    "INSERT INTO codes SET code_type = ?, code = ?, code_text = ?, " .
                    "fee = 0, units = 0",
                    array($code_type_id, $code, $a[14])
                );
                   }
                   else if ($code_type == 'ICD10_RVP') {
                         sqlStatementNoLog(
                    "INSERT INTO codes SET code_type = ?, code = ?, code_text = ?, " .
                    "fee = 0, units = 0",
                    array($code_type_id, $code, $a[1])
                );
                   }
                ++$inscount;

            // TBD: Clone/adapt the above for each new code type.

        } /* while lines to read */

        // Settings to drastically speed up import with InnoDB
        sqlStatementNoLog("COMMIT");
        sqlStatementNoLog("SET autocommit=1");

        fclose($eres);
        if ($zip) {  $zipin->close(); }

    }


    echo "<p class='text-success'>" .
       xlt('LOAD SUCCESSFUL. Codes inserted') . ", Table: " . "codes" . ", record count:"  . text($inscount) . ", " .
       xlt('replaced') . ": " . text($repcount) .
       "</p>\n";
}

?>
    <div class="container">

        <form method='post' action='load_codes.php' enctype='multipart/form-data'
        onsubmit='return top.restoreSession()'>

            <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr class="dehead">
                            <th colspan="2" class='text-center'><?php echo xlt('Install Code Set'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <?php echo xlt('Code Type'); ?>
                            </td>
                            <td>
                                <select name='form_code_type'>
                                    <?php
                                    foreach (array('ICD10_RVP','RXCUI') as $codetype) {
                                        echo "    <option value='" . attr($codetype) . "'>" . text($codetype) . "</option>\n";
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="detail">
                                <?php echo xlt('Source File '); ?>
                                <input type="hidden" name="MAX_FILE_SIZE" value="350000000" />
                            </td>
                            <td class="detail">
                                <input type="file" name="form_file" size="40" />
                            </td>
                        </tr>
                        <tr>
                            <td class="detail">
                                <?php echo xlt('Replace entire code set'); ?>
                            </td>
                            <td class="detail">
                                <input type='checkbox' name='form_replace' value='1' checked />
                            </td>
                        </tr>
                        <tr class="bg-secondary">
                            <td colspan="2" class="text-center detail">
                                <input type='submit' class='btn btn-primary' name='bn_upload' value='<?php echo xlt('Upload and Install') ?>' />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class='font-weight-bold text-center'>
                <?php echo xlt('Be patient, some files can take several minutes to process!'); ?>
            </p>

            <!-- No translation because this text is long and US-specific and quotes other English-only text. -->
            <p class='text'>
            <span class="font-weight-bold">RXCUI codes</span> may be downloaded from
            <a href='https://www.nlm.nih.gov/research/umls/rxnorm/docs/rxnormfiles.html' rel="noopener" target='_blank'>
            www.nlm.nih.gov/research/umls/rxnorm/docs/rxnormfiles.html</a>.
            Get the "Current Prescribable Content Monthly Release" zip file, marked "no license required".
            Then you can upload that file as-is here, or extract the file RXNCONSO.RRF from it and upload just
            that (zipped or not). You may do the same with the weekly updates, but for those uncheck the
            "<?php echo xlt('Replace entire code set'); ?>" checkbox above.
            </p>

            <!-- TBD: Another paragraph of instructions here for each code type. -->
        </form>
    </div>
</body>
</html>
