<?php

/*
 * © Copyright by Laboratorio de Redes 2012
 */

elgg_set_page_owner_guid(elgg_get_logged_in_user_guid());
elgg_push_breadcrumb('Test');

$professor_id = get_input('professor_guid', null);
$student_id = get_input('student_guid', null);
$task_id = get_input('task_guid', null);
$subject_id = get_input('subject_guid', null);

$access = elgg_set_ignore_access(true);
$marks = socialwire_marks_get_marks($professor_id, $student_id, $task_id, $subject_id);
$content = socialwire_marks_print_marks($marks);
elgg_set_ignore_access($access);

$title = elgg_echo('item:object:socialwire_mark');
$body = elgg_view_layout('one_sidebar', array(
    'content' => $content,
    'title' => $title));

echo elgg_view_page($title, $body);
?>
