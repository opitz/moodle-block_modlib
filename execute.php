<?php
require_once('../../config.php');
include_once ('../../course/lib.php');

$sectionid = $_POST['sectionid'];
$moduleid = $_POST['moduleid'];
$type = $_POST['type'];

echo '';

switch ($type) {
    case 'assign':
        echo install_assign($sectionid, $moduleid);
        break;
    case 'book':
        echo install_book($sectionid, $moduleid);
        break;
    case 'chat':
        echo install_chat($sectionid, $moduleid);
        break;
    case 'data':
        echo install_data($sectionid, $moduleid);
        break;
    case 'feedback':
        echo install_feedback($sectionid, $moduleid);
        break;
    case 'glossary':
        echo install_glossary($sectionid, $moduleid);
        break;
    case 'lesson':
        echo install_lesson($sectionid, $moduleid);
        break;
    case 'page':
        echo install_page($sectionid, $moduleid);
        break;
    case 'quiz':
        echo install_quiz($sectionid, $moduleid);
        break;
    case 'survey':
        echo install_survey($sectionid, $moduleid);
        break;
    case 'wiki':
        echo install_wiki($sectionid, $moduleid);
        break;
    case 'workshop':
        echo install_workshop($sectionid, $moduleid);
        break;
    default:
        echo "";
}

//----------------------------------------------------------------------------------------------------------------------
function get_module_files($moduleid) {
    global $DB;

}

//----------------------------------------------------------------------------------------------------------------------
function set_module_files($files, $moduleid) {
    global $DB;
    foreach($files as $file) {
        // do something here
    }
}

//----------------------------------------------------------------------------------------------------------------------
function save_module($courseid = false, $sectionid = false, $moduleid = false, $moduletype = false, $new_instanceid, $target) {
    global $DB;
    if(!$courseid || !$sectionid || !$moduleid || !$moduletype) {
        return false;
    }

    // get the module type code
    $type_code = $DB->get_record('modules', array('name' => $moduletype));
    // now relate the data to the target section
    $cm = $DB->get_record('course_modules', array('module' => $type_code->id, 'instance' => $moduleid));
    unset($cm->id);
    $cm->course = $courseid;
    $cm->instance = $new_instanceid;
    $cm->section = $sectionid;

    $cm_id = $DB->insert_record('course_modules', $cm);

    // finally update the module sequence of the target section
    if($target->sequence === '' || $target->sequence == null) {
        $target->sequence = $cm_id;
    } else {
        $target->sequence .= ','.$cm_id;

    }
    $DB->update_record('course_sections', $target);

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective
    return true;
}

//----------------------------------------------------------------------------------------------------------------------
function install_assign($sectionid, $moduleid) {
    global $DB;
    // get the base assign module
    $assign = $DB->get_record('assign', array('id' => $moduleid));
    $grade_item = $DB->get_record('grade_items', array('iteminstance' => $assign->id));
    unset($assign->id);

    // get the target section
    $target = $DB->get_record('course_sections', array('id' => $sectionid));
    $courseid = $target->course;

    // Save a new copy of the module
    $assign->course = $courseid;
    $new_assign_id = $DB->insert_record('assign', $assign);

    // now save a copy of the grade item too
    unset($grade_item->id);
    $grade_item->iteminstance = $new_assign_id;
    $new_grade_item_id = $DB->insert_record('grade_items', $grade_item);

    // finally save the new module and relate it to the target section
    save_module($courseid, $sectionid, $moduleid, 'assign', $new_assign_id, $target);

    // get any files related to the assignment module
    $files = get_module_files($assign->id);
    if($files) {
        set_module_files($files, $new_assign_id);
    }

    return "Assignment $assign->name installed";
}

//----------------------------------------------------------------------------------------------------------------------
function install_book($sectionid, $moduleid) {
    global $DB;

    // get the base book module
    $book = $DB->get_record('book', array('id' => $moduleid));
    $chapters = $DB->get_records('book_chapters', array('bookid' => $book->id));
    unset($book->id);

    // get the target section
    $target = $DB->get_record('course_sections', array('id' => $sectionid));
    $courseid = $target->course;

    // Save a new copy of the book
    $book->course = $courseid;
    $new_bookid = $DB->insert_record('book', $book);
    // now save copies of the chapters too
    foreach($chapters as $chapter) {
        unset($chapter->id);
        $chapter->bookid = $new_bookid;
        $new_chapterid = $DB->insert_record('book_chapters', $chapter);
    }

    // finally save the new module and relate it to the target section
    save_module($courseid, $sectionid, $moduleid, 'book', $new_bookid, $target);
/*
    // get the module type code for a book
    $type_code = $DB->get_record('modules', array('name' => 'book'));
    // now relate the book to the target section
    $cm = $DB->get_record('course_modules', array('module' => $type_code->id, 'instance' => $moduleid));
    unset($cm->id);
    $cm->course = $courseid;
    $cm->instance = $new_bookid;
    $cm->section = $sectionid;

    $cm_id = $DB->insert_record('course_modules', $cm);

    // finally update the module sequence of the target section
    if($target->sequence === '' || $target->sequence == null) {
        $target->sequence = $cm_id;
    } else {
        $target->sequence .= ','.$cm_id;

    }
    $DB->update_record('course_sections', $target);

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective
*/
    return "Book $book->name installed";
}

//----------------------------------------------------------------------------------------------------------------------
function install_chat($sectionid, $moduleid) {
    global $DB;

    return "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_data($sectionid, $moduleid) {
    global $DB;

    // get the target section
    $target = $DB->get_record('course_sections', array('id' => $sectionid));
    $courseid = $target->course;

    // get the base data record
    $data = $DB->get_record('data', array('id' => $moduleid));
    $data_fields = $DB->get_records('data_fields', array('dataid' => $data->id));
    $data_records = $DB->get_records('data_records', array('dataid' => $data->id));

    // save the new data record
    unset($data->id);
    $data->course = $courseid;
    $new_dataid = $DB->insert_record('data', $data);

    // now save the data fields for the new data
    $new_data_field_ids = array();
    foreach($data_fields as $data_field) {
        $old_field_id = $data_field->id;
        unset($data_field->id);
        $data_field->dataid = $new_dataid;
        $data_field->id = $DB->insert_record('data_fields', $data_field);
        $new_data_field_ids[$old_field_id] = $data_field->id;
    }

    // now save the records
    $new_data_records = array();
    foreach($data_records as $data_record) {
        $data_record_id = $data_record->id; // we need the original record ID
        // 1st get the data content of the original record
        $data_content = $DB->get_records('data_content', array('recordid' => $data_record->id));

        // save the new record
        unset($data_record->id);
        $data_record->dataid = $new_dataid;
        $new_data_record_id = $DB->insert_record('data_records', $data_record);

        // save the record data for the new record
        foreach($data_content as $content) {
            unset($content->id);
            $content->recordid = $new_data_record_id;
            $content->fieldid = $new_data_field_ids[$content->fieldid];
            $data_field->id = $DB->insert_record('data_content', $content);
        }
    }

    // finally save the new module and relate it to the target section
    save_module($courseid, $sectionid, $moduleid, 'data', $new_dataid, $target);
/*
    // get the module type code
    $type_code = $DB->get_record('modules', array('name' => 'data'));
    // now relate the data to the target section
    $cm = $DB->get_record('course_modules', array('module' => $type_code->id, 'instance' => $moduleid));
    unset($cm->id);
    $cm->course = $courseid;
    $cm->instance = $new_dataid;
    $cm->section = $sectionid;

    $cm_id = $DB->insert_record('course_modules', $cm);

    // finally update the module sequence of the target section
    if($target->sequence === '' || $target->sequence == null) {
        $target->sequence = $cm_id;
    } else {
        $target->sequence .= ','.$cm_id;

    }
    $DB->update_record('course_sections', $target);

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective
*/
    return "Data $data->name installed";
}

//----------------------------------------------------------------------------------------------------------------------
function install_feedback($sectionid, $moduleid) {
    global $DB;

    return "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_forum($sectionid, $moduleid) {
    global $DB;

    return "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_glossary($sectionid, $moduleid) {
    global $DB;

    return "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_lesson($sectionid, $moduleid) {
    global $DB;

    return "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_page($sectionid, $moduleid) {
    global $DB;

    return "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_quiz($sectionid, $moduleid) {
    global $DB;

    return "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_survey($sectionid, $moduleid) {
    global $DB;

    return "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_wiki($sectionid, $moduleid) {
    global $DB;

    return "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_workshop($sectionid, $moduleid) {
    global $DB;

    return "";
}


