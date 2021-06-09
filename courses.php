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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 *
 * @package    local
 * @subpackage notasuai
 * @copyright  2019  Martin Fica (mafica@alumnos.uai.cl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once ($CFG->dirroot."/local/notasuai/forms.php");
require_once($CFG->dirroot . '/local/notasuai/locallib.php');

global $DB, $PAGE, $OUTPUT, $USER;

$context = context_system::instance();

require_login();
if (isguestuser()){
    die();
}

$url_view= '/local/notasuai/courses.php';
$url = new moodle_url($url_view);

// Possible actions -> view, add. Standard is view mode
$courses = optional_param("courses", null, PARAM_TEXT);
$exam_check = optional_param('exam_check', null, PARAM_TEXT);
$exam_blah = optional_param('exams', null, PARAM_TEXT);
$all_none = optional_param('all_none', 0, PARAM_INT);
	
if(!is_null($exam_blah)){
    $exam_aux = json_decode($exam_blah);

    if ($exam_check == 'export'){
        export_to_excel($exam_aux, $context);
    }
}
	
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
	
$PAGE->set_title(get_string('title', 'local_notasuai'));
$PAGE->set_heading(get_string('heading', 'local_notasuai'));
	
$arcourses = json_decode($courses);
$classes = (array) $arcourses;
	
$testsform = new tests(null, $classes);

if ($testsform->is_cancelled()) {
    redirect(new moodle_url("/local/notasuai/index.php"));
}
else if($formdata = $testsform->get_data()) {
		
    //form data analysis
    $exams = array();
    foreach ($formdata->emarking_checkbox as $valor) {

        if($valor != 0 || $valor != ''){
            $exams[] = $valor;
        }
    }
		
    $exam_string = json_encode($exams);
    redirect(new moodle_url("/local/notasuai/courses.php", array('exam_check' => 'export', 'exams' => $exam_string, 'courses' => $courses)));
}

echo $OUTPUT->header();
	
$testsform->display();
	
echo $OUTPUT->footer();

?>

<script>
    //Select all/none checkboxes function
    document.getElementById("id_select-all").addEventListener("click", function() {
        var checkboxes = document.querySelectorAll(".form-check-input");
        var enabled = 0;
        var checked = 0;
        var unchecked = 0;

        checkboxes.forEach(checkbox => {
            if(checkbox.disabled != true){
                enabled++;
                if(checkbox.checked == false){
                    unchecked++;
                }
                else if(checkbox.checked == true){
                    checked++;
                }
            }
        });

			if(enabled == checked){
				checkboxes.forEach(checkbox => {
					if(checkbox.disabled != true){
						checkbox.checked = false;
					}	
				});
			}
			else if(enabled == unchecked || checked >0){
				checkboxes.forEach(checkbox => {
					if(checkbox.disabled != true){
						checkbox.checked = true;
					}	
				});
			}
		});
    //Enable download and cancel button after downloading excel
    document.getElementById("id_submitbutton").addEventListener("click", function() {
        var cancel_btn = document.getElementById("id_cancel")
        var submit_btn = document.getElementById("id_submitbutton")
        var delayInMilliseconds = 2000; //2 seconds

        setTimeout(function() {
            console.log("delay")
            cancel_btn.disabled = false;
            submit_btn.disabled = false;
        }, delayInMilliseconds);
    });
</script>