<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * @package    local
 * @subpackage notasuai
 * @copyright  2019  Martin Fica (mafica@alumnos.uai.cl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to export the Grades's Summary to excel
 *
 * @param array $header
 *            Array containing the header of each row
 * @param varchar $filename
 *            Full name of the excel
 * @param array $data
 *            Array containing the data of each row
 * @param array $description
 *            Array containing the selected descriptions of attendances ???
 * @param array $dates
 *            Array containing the dates of each session
 * @param array $tabs
 *            Array containing the tabs of the excel  DELETE, DONT NEED IT
 */

/**
 * Exports all grades and scores in an exam in Excel format
 *
 * @param unknown $emarking
 * @param unknown $context
 */

require_once("$CFG->libdir/excellib.class.php");

function is_manager() {
	global $DB, $CFG, $USER;
	
	$category_query = "SELECT cc.*
                FROM {course_categories} cc
                INNER JOIN {role_assignments} ra ON (ra.userid = ?)
                INNER JOIN {role} r ON (r.id = ra.roleid AND r.shortname = ?)
                INNER JOIN {context} co ON (co.id = ra.contextid  AND  co.instanceid = cc.id  )";

	$queryparams = array($USER->id, "managerreport");
	// Get Records
	$category_sql = $DB->get_records_sql($category_query, $queryparams);
	
	if (count($category_sql) == 0){
		return false;
	}
	return true;
}

function export_to_excel($emarking, $context = null){
    global $DB, $CFG;

    $criteria = 0;
    $questions = array();
    $pos = 0;

    // Basic headers that go everytime
    $headers = array(
        '00course' => get_string('course', 'local_notasuai'),
        '01exam' => get_string('tests', 'local_notasuai'),
        '02idnumber' => get_string('idnumber'),
        '03lastname' => get_string('lastname'),
        '04firstname' => get_string('firstname'),
        '05grade'=>get_string('grade'),
    );
    $tabledata = array();
    $data = array();
    $part = 0;
    $crit = array();
		
    //Loop to determine questions and headers
    foreach ($emarking as $id){
        // get tests
        $testquery = "SELECT cc.fullname AS course,
			e.name AS exam,
			u.id,
			u.idnumber,
			u.lastname,
			u.firstname,
			cr.id criterionid,
			cr.description,
			l.id levelid,
			IFNULL(l.score, 0) AS score,
			IFNULL(c.bonus, 0) AS bonus,
			IFNULL(l.score,0) + IFNULL(c.bonus,0) AS totalscore,
			d.grade
			FROM {emarking} e
			INNER JOIN {emarking_submission} s ON (e.id = :emarkingid AND e.id = s.emarking)
			INNER JOIN {emarking_draft} d ON (d.submissionid = s.id AND d.qualitycontrol=0)
			INNER JOIN {course} cc ON (cc.id = e.course)
			INNER JOIN {user} u ON (s.student = u.id)
			INNER JOIN {emarking_page} p ON (p.submission = s.id)
			LEFT JOIN {emarking_comment} c ON (c.page = p.id AND d.id = c.draft AND c.levelid > 0)
			LEFT JOIN {gradingform_rubric_levels} l ON (c.levelid = l.id)
			LEFT JOIN {gradingform_rubric_criteria} cr ON (cr.id = l.criterionid)
			WHERE (d.status=20)
			ORDER BY cc.fullname ASC, e.name ASC, u.lastname ASC, u.firstname ASC, cr.sortorder";
		
        // Get data and generate a list of questions.
        $rows2 = $DB->get_recordset_sql($testquery, array(
            'emarkingid' => $id));
											
        // Make a list of all criteria
        foreach ($rows2 as $row) {
            if (array_search($row->description, $questions) === false && $row->description) {
                $questions [$pos] = $row->description;
                $pos++;
            }
        }
			
        if ($criteria <= count($questions)){
            $criteria = count($questions);
        }
			
        $part++;
    }
		
    $columns_total = $criteria + 6;
		
    $aux = 0;
    $part = 0;
    foreach($questions as $q){
        $index = 10 + $part;
        $keyquestion = $index . "" . $questions[$part];
        $crit[$part] = $keyquestion;
			
        $headers[$keyquestion] = $q;
			
        foreach($crit as $keyquestion){
            if (!isset($headers[$keyquestion]) && $aux == array_search($keyquestion,$crit) ){
                $headers[$keyquestion] = $q;
            }
        }
        $aux++;
        $part++;
    }

    $current_line = -1;

    //Loop to get the data
    foreach ($emarking as $id){
        // get tests
        $testquery = "SELECT cc.fullname AS course,
			e.name AS exam,
			u.id,
			u.idnumber,
			u.lastname,
			u.firstname,
			cr.id criterionid,
			cr.description,
			l.id levelid,
			IFNULL(l.score, 0) AS score,
			IFNULL(c.bonus, 0) AS bonus,
			IFNULL(l.score,0) + IFNULL(c.bonus,0) AS totalscore,
			d.grade,
			d.status status
			FROM {emarking} e
			INNER JOIN {emarking_submission} s ON (e.id = :emarkingid AND e.id = s.emarking)
			INNER JOIN {emarking_draft} d ON (d.submissionid = s.id AND d.qualitycontrol=0)
			INNER JOIN {course} cc ON (cc.id = e.course)
			INNER JOIN {user} u ON (s.student = u.id)
			INNER JOIN {emarking_page} p ON (p.submission = s.id)
			LEFT JOIN {emarking_comment} c ON (c.page = p.id AND d.id = c.draft AND c.levelid > 0)
			LEFT JOIN {gradingform_rubric_levels} l ON (c.levelid = l.id)
			LEFT JOIN {gradingform_rubric_criteria} cr ON (cr.id = l.criterionid)
			ORDER BY cc.fullname ASC, e.name ASC, u.lastname ASC, u.firstname ASC, cr.sortorder";
		
        // Get data.
        $rows2 = $DB->get_recordset_sql($testquery, array(
            'emarkingid' => $id));

        // Now iterate through students
        foreach ($rows2 as $row) {
								
            if($row->description){
					
                if($row->status >= 20){
                // compares the current row with the last one, if they match, it just assings the grade for the current criteria
				if(array_search($row->course,$data) && array_search($row->exam,$data) && array_search($row->idnumber,$data) && array_search($row->lastname,$data) && array_search($row->firstname,$data)){
						
				    $part1 = 0;
				    while($part1 < $part){
				        $index = 10 + $part1;
				        $P = $index . "" . $row->description;

				        if(array_search($P,$crit)){
				            $data [$P] = $row->totalscore;
				        }
				        $part1++;
				    }
				}
				// if they don't match, it means we are with a new student, and it creates a new line for that student
                else{
                    $current_line++;
						
                    $data = array(
					    '00course' => $row->course,
					    '01exam' => $row->exam,
					    '02idnumber' => $row->idnumber,
					    '03lastname' => $row->lastname,
					    '04firstname' => $row->firstname,
                        '05grade' => $row->grade
                    );
						
					$part1 = 0;
					while($part1 < $part){
					    $index = 10 + $part1;
					    $P = $index . "" . $row->description;

					    if(array_search($P,$crit)){
					        $data [$P] = $row->totalscore;
					    }
					    elseif($P == $crit[0]){
					        $data [$P] = $row->totalscore;
					    }
					    $part1++;
					}
                }
					
					$tabledata [$current_line] = $data;
				}
            }

        }
        // Add the grade if it's summative feedback
        if(!isset($CFG->emarking_formativefeedbackonly) || !$CFG->emarking_formativefeedbackonly) {
            $headers ['05grade'] = get_string('grade');
        }
    }

    ksort($tabledata);
    // Now pivot the table to form the Excel report
    $current = 0;
    $newtabledata = array();
    foreach ($tabledata as $data) {
        foreach ($questions as $q) {
            $index = 10 + array_search($q, $questions);
            if (! isset($data [$index . "" . $q])) {
                $data [$index . "" . $q] = '';
            }
        }
        ksort($data);
        $current ++;
        $newtabledata [] = $data;
    }
    $tabledata = $newtabledata;
		
    // The file name of the report
    $excelfilename = clean_filename("ReporteUAI" . "-grades.xlsx");
	// Creating a workbook.
    $workbook = new MoodleExcelWorkbook("-");
    // Sending HTTP headers.
    $workbook->send($excelfilename);
    // Adding the worksheet.
    $myxls = $workbook->add_worksheet("grade report");
    // Writing the headers in the first row.
    $row = 0;
    $col = 0;
	$titleformat = $workbook->add_format();
	$titleformat->set_bold(1);
	$titleformat->set_size(10);
    foreach (array_values($headers) as $d) {
        $myxls->write($row, $col, $d, $titleformat);

        $col ++;
    }
    // Writing the data.
    $row = 1;
    foreach ($tabledata as $data) {
        $col = 0;
        foreach (array_values($data) as $d) {
            $myxls->write($row, $col, $d);
            $col ++;
        }
        $row ++;
    }
    $workbook->close();
	exit;
}

?>