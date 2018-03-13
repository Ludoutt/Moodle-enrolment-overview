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
 * Listing of enrolment method for all courses
 * inspired by Main course enrolment management UI.
 * by  Ludovic STIOT (UTT) 2018-03-12
 *
 * @package    core_enrol
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
require('../config.php');
// Optionnal category id.
$idcategory = optional_param('id', '', PARAM_INT);

$PAGE->set_url('/enrol/instancesAll.php', array('id' => $idcategory));
$PAGE->set_pagelayout('admin');
$PAGE->set_title("View all enrolment methods");

if ($course->id == SITEID) {
    redirect("$CFG->wwwroot/");
}

require_login($course);

echo $OUTPUT->header();

$editstr = get_string('edit');
$hidestr = get_string('disable');
$showstr = get_string('enable');

// Getting all courses.
$courses = get_courses();
// For each course found.
foreach ($courses as $course) {
    // If a optional parameter was given,
    // We test it!
    if (!empty($idcategory)) {
        // If the current course category is different.
        if ($idcategory != $course->category) {
            // We skip this course.
            continue;
        }
    }
    // We check that user is an admin that can review enrolment.
    $context = context_course::instance($course->id, MUST_EXIST);
    require_capability('moodle/course:enrolreview', $context);

    // If ok we continue therefore an error will appear!
    $tablels = new html_table();
    $tablels->head = array(get_string('name'), get_string('users'), $showstr . ' / ' . $hidestr, $editstr);
    $tablels->align = array('left', 'center', 'center', 'center', 'center');
    $tablels->width = '100%';
    $tablels->data = array();

    $instances = enrol_get_instances($course->id, false);
    $plugins = enrol_get_plugins(false);

    // For each instance.
    foreach ($instances as $instance) {
        // Count number of user.
        $users = $DB->count_records('user_enrolments', array('enrolid' => $instance->id));
        // Make moodleurl to edit the course enrolment.
        $url = new moodle_url('/enrol/instances.php', array('sesskey' => sesskey(), 'id' => $course->id));
        $link = $url->out(false);
        $plugin = $plugins[$instance->enrol];
        // Getting the name of the enrolment method.
        $displayname = $plugin->get_instance_name($instance);

        // Init with empty array.
        $edit = array();

        if (enrol_is_enabled($instance->enrol) && $plugin->can_hide_show_instance($instance)) {
            if ($instance->status == ENROL_INSTANCE_ENABLED) {
                $edit[] = $OUTPUT->render(new pix_icon('t/hide', $strdisable, 'core', array('class' => 'iconsmall')));
            } else if ($instance->status == ENROL_INSTANCE_DISABLED) {
                // Changing the span css class.
                $displayname = html_writer::tag('span', $displayname, array('class' => 'dimmed_text'));
                $edit[] = $OUTPUT->render(new pix_icon('t/show', $strenable, 'core', array('class' => 'iconsmall')));
            } else {
                // Plugin specific state - do not mess with it!
                $show = $OUTPUT->pix_url('t/show');
                $edit[] = html_writer::empty_tag('img', array('src' => $show, 'alt' => '', 'class' => 'iconsmall'));
            }
        }
        // Link to instance management.
        if (enrol_is_enabled($instance->enrol) && $canconfig) {
            if ($icons = $plugin->get_action_icons($instance)) {
                $edit = array_merge($edit, $icons);
            }
        }

        // Add a row to the table.
        $tablels->data[] = array($displayname, $users, implode('', $edit), "<a href='$link' target='edit'>$editstr</a> ");
    }
    // Persist into a temporary array.
    $courseslist[$course->category][$course->fullname] = $tablels;
}
// Getting category string.
$categoriesstr = get_string('categories');
$categorystr = get_string('category');
$backstr = get_string('back');

if ($idcategory == '' || empty($idcategory)) {
    echo "<h2>$categoriesstr : </h2>";
} else {
    echo "<a href=?id=>$backstr</a>";
}

// For each category.
foreach ($courseslist as $onecategorykey => $onecategory) {
    // Skipping root category!
    if ($onecategorykey == 0) {
        continue;
    }
    // Getting category.
    $category = $DB->get_record('course_categories', array('id' => $onecategorykey));

    if ($idcategory == '' || empty($idcategory)) {
        // If we have a true category, we display it!
        echo "<span> * <a href=?id=$onecategorykey>$category->name</a></span><br>";
        continue;
    } else {
        // A category waw chosen : building details.
        echo "<h2>$categorystr : $category->name</h2>";
        // Building a new box.
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
        foreach ($onecategory as $onecoursekey => $onecourse) {
            echo "<h3>$onecoursekey</h3>";
            echo html_writer::table($onecourse);
        }
        echo $OUTPUT->box_end();
        echo '<hr>';
    }
}
echo $OUTPUT->footer();
