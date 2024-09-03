<?php

/**
 * Sql functions/classes for OpenEMR.
 *
 * Things related to layout based forms in general.
 *
 * Copyright (C) 2017-2021 Rod Roark <rod@sunsetsystems.com>
 * Copyright (c) 2022 Stephen Nielson <snielson@discoverandchange.com>
 * Copyright (c) 2022 David Eschelbacher <psoas@tampabay.rr.com>
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://opensource.org/licenses/gpl-license.php>.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 */

// array of the data_types of the fields
// TODO: Move these all to a statically typed class with constants that can be referenced throughout the codebase!
$datatypes = array(
    "1"  => xl("List box"),
    "2"  => xl("Textbox"),
    "3"  => xl("Textarea"),
    "4"  => xl("Text-date"),
    "10" => xl("Providers"),
    "11" => xl("Providers NPI"),
    "12" => xl("Pharmacies"),
    "13" => xl("Squads"),
    "14" => xl("Organizations"),
    "15" => xl("Billing codes"),
    "16" => xl("Insurances"),
    "18" => xl("Visit Categories"),
    "21" => xl("Checkbox(es)"),
    "22" => xl("Textbox list"),
    "23" => xl("Exam results"),
    "24" => xl("Patient allergies"),
    "25" => xl("Checkboxes w/text"),
    "26" => xl("List box w/add"),
    "27" => xl("Radio buttons"),
    "28" => xl("Lifestyle status"),
    "31" => xl("Static Text"),
    "32" => xl("Smoking Status"),
    "33" => xl("Race/Ethnicity"),
    "34" => xl("NationNotes"),
    "35" => xl("Facilities"),
    "36" => xl("Multiple Select List"),
    "37" => xl("Lab Results"),
    "40" => xl("Image canvas"),
    "41" => xl("Patient Signature"),
    "42" => xl("User Signature"),
    "43" => xl("List box w/search"),
    "44" => xl("Multi-Select Facilties"),
    "45" => xl("Multi-Select Provider"),
    "46" => xl("List box w/comment"),
    "51" => xl("Patient"),
    "52" => xl("Previous Names"),
    "53" => xl("Patient Encounters List"),
    "54" => xl("Address List"),
    "55" => xlt("IE PPS Number")
);

// These are the data types that can reference a list.
$typesUsingList = array(1, 21, 22, 23, 25, 26, 27, 32, 33, 34, 36, 37, 43, 46);

$sources = array(
    'F' => xl('Form'),
    'D' => xl('Patient'),
    'H' => xl('History'),
    'E' => xl('Visit'),
    'V' => xl('VisForm'),
);

$UOR = array(
    0 => xl('Unused'),
    1 => xl('Optional'),
    2 => xl('Required'),
);

function generate_IE_PPSNumber ( $patient_id)
{
      /* use day of year (3 chars) and time of day in minutes (4 chars) and first character of patient's surname as last character, then generate the check character. return string of 9 characters
    * so potentially only unique within a minute for two people with the same initial character in their surname
    */

    $ppsnumber = 0;
     $multipliers = [8,7,6,5,4,3,2];
    /* generate a unique PPS number using IE format https://en.wikipedia.org/wiki/Personal_Public_Service_Number */
    $date = new DateTimeImmutable();
    $day = $date->format('zzz');
    $minutes = $date->format('h')*60 . $date->format('m');
    $string = $day . $minutes;


    //generate the check character
    for ($i=0; $i<7; $i++) {
        $checkno += int ($string[i]) * $multipliers[i];
    }
     // sql to get patient's surname
    // get first char and generate an integer from it

    $checkno += $charint;

    $checkno = gmp_mod( $checkno, 23 );
    // generate check character

    $string += $checkchar . $firstchar;

    return ($string);
}
