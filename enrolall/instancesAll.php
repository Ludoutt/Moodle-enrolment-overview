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

$idCategory = optional_param('id', '', PARAM_INT); // optionnal category id

$PAGE->set_url('/enrol/instancesAll.php', array('id' => $idCategory));
$PAGE->set_pagelayout('admin');
$PAGE->set_title("View all enrolment methods");

echo $OUTPUT->header();

$edit_str = get_string('edit');
$hide_str = get_string('disable');
$show_str = get_string('enable');

// getting all courses
$courses = get_courses();
// for each course found
foreach ($courses as $course) {
    // if a optional parameter was given
    // we test it
    if (!empty($idCategory)) {
        // if the current course category is different
        if ($idCategory != $course->category) {
            // we skip this course
            continue;
        }
    }
    // we check that user is an admin that can review enrolment
    $context = context_course::instance($course->id, MUST_EXIST);
    require_capability('moodle/course:enrolreview', $context);

    // si ok we continue therefore an error will appear
    $tablels = new html_table();
    $tablels->head = array(get_string('name'), get_string('users'), $show_str . ' / ' . $hide_str, $edit_str);
    $tablels->align = array('left', 'center', 'center', 'center', 'center');
    $tablels->width = '100%';
    $tablels->data = array();

    $instances = enrol_get_instances($course->id, false);
    $plugins = enrol_get_plugins(false);

    // for each instance
    foreach ($instances as $instance) {
        //  count number of user
        $users = $DB->count_records('user_enrolments', array('enrolid' => $instance->id));
        // make moodleurl to edit the course enrolment
        $url = new moodle_url('/enrol/instances.php', array('sesskey' => sesskey(), 'id' => $course->id));
        $link = $url->out(false);
        $plugin = $plugins[$instance->enrol];
        // getting the name of the enrolment method
        $displayname = $plugin->get_instance_name($instance);

        // init with empyt array
        $edit = array();

        if (enrol_is_enabled($instance->enrol) && $plugin->can_hide_show_instance($instance)) {
            if ($instance->status == ENROL_INSTANCE_ENABLED) {
                $edit[] = $OUTPUT->render(new pix_icon('t/hide', $strdisable, 'core', array('class' => 'iconsmall')));
            } else if ($instance->status == ENROL_INSTANCE_DISABLED) {
                // changing the span css class
                $displayname = html_writer::tag('span', $displayname, array('class' => 'dimmed_text'));
                $edit[] = $OUTPUT->render(new pix_icon('t/show', $strenable, 'core', array('class' => 'iconsmall')));
            } else {
                // plugin specific state - do not mess with it!
                $edit[] = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/show'), 'alt' => '', 'class' => 'iconsmall'));
            }
        }
        // link to instance management
        if (enrol_is_enabled($instance->enrol) && $canconfig) {
            if ($icons = $plugin->get_action_icons($instance)) {
                $edit = array_merge($edit, $icons);
            }
        }

        // Add a row to the table.
        $tablels->data[] = array($displayname, $users, implode('', $edit), "<a href='$link' target='edit'>$edit_str</a> ");
    }
    // persist into a temporary array
    $coursesList[$course->category][$course->fullname] = $tablels;
}
// getting category string
$categories_str = get_string('categories');
$category_str = get_string('category');
$back_str = get_string('back');

if ($idCategory == '' || empty($idCategory)) {
    echo "<h2>$categories_str : </h2>";
} else {
    echo "<a href=?id=>$back_str</a>";
}

// for each category
foreach ($coursesList as $oneCategoryKey => $oneCategory) {
    // skipping root category
    if ($oneCategoryKey == 0) {
        continue;
    }
    // getting category
    $category = $DB->get_record('course_categories', array('id' => $oneCategoryKey));

    if ($idCategory == '' || empty($idCategory)) {
        // if we have a true category, we display it
        echo "<span> * <a href=?id=$oneCategoryKey>$category->name</a></span><br>";
        continue;
    } else {
        // a category waw chosen : building details
        echo "<h2>$category_str : $category->name</h2>";
        // building a new box
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
        foreach ($oneCategory as $oneCourseKey => $oneCourse) {
            echo "<h3>$oneCourseKey</h3>";
            echo html_writer::table($oneCourse);
        }
        echo $OUTPUT->box_end();
        echo '<hr>';
    }
}
echo $OUTPUT->footer();
