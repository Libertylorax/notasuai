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

global $DB, $PAGE, $OUTPUT, $USER;

$url_view= '/local/notasuai/index.php';

$context = context_system::instance();
$url = new moodle_url($url_view);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");

$category_id = optional_param("category_id", null, PARAM_INT);
$action = optional_param("action", "view", PARAM_TEXT);

require_login();
    
if (is_siteadmin($USER) || is_manager()){
    // show all
    $PAGE->set_title(get_string('title', 'local_notasuai'));
    $PAGE->set_heading(get_string('heading', 'local_notasuai'));

    $categoryform = new category();

    if ($category_id > 0){
        $error = 0;
        $courseform = new course(null, $category_id);
        if ($courses = $courseform->get_data()){
            $arcourses = (array)$courses;
            $num = 0;
            $arr = array();
            foreach ($arcourses as $class){
                if ($class != 0 && $num > 1){
                    $arr[$num] = $class;
                }
                $num += 1;
            }
            $coursesstring = json_encode($arr);
            redirect(new moodle_url("/local/notasuai/courses.php", array('courses'=>$coursesstring)));
        }
    }
}
else{
    print_error(get_string("no_access", "local_notasuai"));

}

echo $OUTPUT->header();

    $categoryform->display();if ($category_id > 0){
		$courseform->display();
	}

echo $OUTPUT->footer();