<?php
require_once('../../config.php');
include_once ('../../course/lib.php');

$sectionid = $_POST['sectionid'];
$moduleid = $_POST['moduleid'];
$type = $_POST['type'];

echo '';

switch ($type) {
    case 'assignment':
        echo install_assignment($sectionid, $moduleid);
        break;
    case 'book':
        echo install_book($sectionid, $moduleid);
        break;
    case 'book':
        echo install_chat($sectionid, $moduleid);
        break;
    case 'book':
        echo install_feedback($sectionid, $moduleid);
        break;
    case 'book':
        echo install_glossary($sectionid, $moduleid);
        break;
    case 'book':
        echo install_lesson($sectionid, $moduleid);
        break;
    case 'book':
        echo install_page($sectionid, $moduleid);
        break;
    case 'book':
        echo install_quiz($sectionid, $moduleid);
        break;
    case 'book':
        echo install_survey($sectionid, $moduleid);
        break;
    case 'book':
        echo install_wiki($sectionid, $moduleid);
        break;
    case 'book':
        echo install_workshop($sectionid, $moduleid);
        break;
    default:
        echo "";
}

//----------------------------------------------------------------------------------------------------------------------
function install_assignment($sectionid, $moduleid) {
    global $DB;

    return "";
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
//    $new_bookid = 666;
    $new_bookid = $DB->insert_record('book', $book);
    // now save copies of the chapters too
    foreach($chapters as $chapter) {
        unset($chapter->id);
        $chapter->bookid = $new_bookid;
        $new_chapterid = $DB->insert_record('book_chapters', $chapter);
    }

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
//    $target->sequence = implode(',', array_push(explode(',', $target->sequence), $cm_id));
    if($target->sequence === '' || $target->sequence == null) {
        $target->sequence = $cm_id;
    } else {
        $target->sequence .= ','.$cm_id;

    }
    $DB->update_record('course_sections', $target);

    rebuild_course_cache($courseid, true); // rebuild the cache for that course so the changes become effective

    return "Book $book->name installed";
}

//----------------------------------------------------------------------------------------------------------------------
function install_chat($sectionid, $moduleid) {
    global $DB;

    return "";
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


