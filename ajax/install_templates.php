<?php
require_once('../../../config.php');
include_once ('../../../course/lib.php');

$sectionid = $_POST['sectionid'];
$type = $_POST['type'];

if($type == 'sections') {
    echo install_sections($sectionid, $_POST['payload']);
} else {
    echo install_modules($sectionid, $_POST['payload']);
}

//----------------------------------------------------------------------------------------------------------------------
function install_sections ($sectionid, $sections) {
    if(is_array($sections)) {
        foreach($sections as $section) {
            install_section($sectionid, $section['id']);
        }
        if (count($sections) === 1) {
            return "Section installed.";
        } else {
            return "All sections installed.";
        }
    }
}

//----------------------------------------------------------------------------------------------------------------------
function install_modules ($sectionid, $modules) {
    if(is_array($modules)) {
        foreach($modules as $module) {
            install_module($sectionid, $module['cmid'], $module['type']);
        }
        if (count($modules) === 1) {
            return "Module installed.";
        } else {
            return "All modules installed.";
        }
    }
}
//----------------------------------------------------------------------------------------------------------------------
function add_section($course, $t_section) {
    global $DB;

    $numsections = course_get_format($course)->get_last_section_number();
    $section = course_create_section($course->id, $numsections+1, true);
    $section->name = $t_section->name;
    $section->summary = $t_section->name;
    $section->visible = $t_section->visible;
    $DB->update_record('course_sections', $section);
    return $section;
}

//----------------------------------------------------------------------------------------------------------------------
function install_section($target_sid, $template_sid) {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    require_once($CFG->libdir . '/filelib.php');

    // get the section after which the new section will be installed
    $pre_section = $DB->get_record('course_sections', array('id' => $target_sid));
    $courseid = $pre_section->course;
    $course = $DB->get_record('course', array('id' => $courseid));
    $keeptempdirectoriesonbackup = $CFG->keeptempdirectoriesonbackup;
    $CFG->keeptempdirectoriesonbackup = true;

    // add a copy of the template section at the end
    $t_section = $DB->get_record('course_sections', array('id' => $template_sid));
    $target_section = add_section($course, $t_section);

    // Backup the section modules
    $bc = new backup_controller(backup::TYPE_1SECTION, $template_sid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);

    $backupid       = $bc->get_backupid();
    $backupbasepath = $bc->get_plan()->get_basepath();

    $bc->execute_plan();
    $bc->destroy();

    // Restore the backup immediately.
    $rc = new restore_controller($backupid, $course->id,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id, backup::TARGET_EXISTING_ADDING);

    // Make sure that the restore_general_groups setting is always enabled when duplicating an activity.
    $the_tasks = $rc->get_plan()->get_tasks();
    foreach ($rc->get_plan()->get_tasks() as $task) {
        if ($task->setting_exists('overwrite_conf'))
            $task->get_setting('overwrite_conf')->set_value(false);
    }

    $rc->set_status(\backup::STATUS_AWAITING);
    $rc->execute_plan();

    // move the restored module to desired section
    $the_tasks2 = $rc->get_plan()->get_tasks();

    foreach ($rc->get_plan()->get_tasks() as $task) {
        if ($task instanceof \restore_activity_task) {
            $cmid = $task->get_moduleid();
            $cm = \get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
            \moveto_module($cm, $target_section);
        }
    }

    if (empty($CFG->keeptempdirectoriesonbackup)) {
        fulldelete($backupbasepath);
    }
    $rc->destroy();

    // move the new section to the desired position
    move_section_to($course, $target_section->section, $pre_section->section+1);

    $CFG->keeptempdirectoriesonbackup = $keeptempdirectoriesonbackup;

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective

    return get_string('installed', 'block_modlib', 'Section');
}

//----------------------------------------------------------------------------------------------------------------------
function install_module($sectionid, $cmid, $type) {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    require_once($CFG->libdir . '/filelib.php');

    // get course from sectionid
    $courseid = $DB->get_field('course_sections', 'course', array('id' => $sectionid));
    $course = $DB->get_record('course', array('id' => $courseid));
    $keeptempdirectoriesonbackup = $CFG->keeptempdirectoriesonbackup;
    $CFG->keeptempdirectoriesonbackup = true;

    // Backup the activity.
    $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cmid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);

    $backupid       = $bc->get_backupid();
    $backupbasepath = $bc->get_plan()->get_basepath();

    $bc->execute_plan();
    $bc->destroy();

    // Restore the backup immediately.
    $rc = new restore_controller($backupid, $course->id,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id, backup::TARGET_CURRENT_ADDING);

    // Make sure that the restore_general_groups setting is always enabled when duplicating an activity.
    $plan = $rc->get_plan();
    $groupsetting = $plan->get_setting('groups');
    if (empty($groupsetting->get_value())) {
        $groupsetting->set_value(true);
    }

    $cmcontext = context_module::instance($cmid);
    if (!$rc->execute_precheck()) {
        $precheckresults = $rc->get_precheck_results();
        if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
            if (empty($CFG->keeptempdirectoriesonbackup)) {
                fulldelete($backupbasepath);
            }
        }
    }

    $rc->execute_plan();

    // Now a bit hacky part follows - we try to get the cmid of the newly
    // restored copy of the module.
    $newcmid = null;
    $tasks = $rc->get_plan()->get_tasks();
    foreach ($tasks as $task) {
        if (is_subclass_of($task, 'restore_activity_task')) {
            if ($task->get_old_contextid() == $cmcontext->id) {
                $newcmid = $task->get_moduleid();
                break;
            }
        }
    }

    $rc->destroy();

    if (empty($CFG->keeptempdirectoriesonbackup)) {
        fulldelete($backupbasepath);
    }

    if ($newcmid) {
        // move the module to the destination section
        $newcm = get_coursemodule_from_id($type, $newcmid);
        $section = $DB->get_record('course_sections', array('id' => $sectionid));
        moveto_module($newcm, $section);

        // Update calendar events with the duplicated module.
        // The following line is to be removed in MDL-58906.
        course_module_update_calendar_events($newcm->modname, null, $newcm);

        // Trigger course module created event. We can trigger the event only if we know the newcmid.
        $newcm = get_fast_modinfo($course)->get_cm($newcmid);
        $event = \core\event\course_module_created::create_from_cm($newcm);
        $event->trigger();
    }

    $CFG->keeptempdirectoriesonbackup = $keeptempdirectoriesonbackup;

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective

    return get_string('installed', 'block_modlib', ucfirst($type));
}
