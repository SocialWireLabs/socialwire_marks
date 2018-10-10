<?php

/*
 * © Copyright by Laboratorio de Redes 2012
 */

$task_guid = get_input('guid');
$task = get_entity($task_guid);
$group_guid = $task->container_guid;
$group = get_entity($group_guid);
$group_owner_guid = $group->owner_guid; 
$user_guid = elgg_get_logged_in_user_guid();
$operator=false;
if (($group_owner_guid==$user_guid)||(check_entity_relationship($user_guid,'group_admin',$group_guid))){
   $operator=true;
}
if (($group->socialwire_marks_enable != 'no') && $task) {
   if ((($task->getSubtype() == 'task') && ($task->assessable) && ($task->type_grading == 'task_type_grading_marks') && (($operator) || ((!$operator) && ($task->grading_visibility) && ($task->public_global_marks)))) || (($task->getSubtype() == 'test') && ($task->assessable) && ($task->type_grading == 'test_type_grading_marks') && (($operator) || ((!$operator) && ($task->public_global_marks)))) || (($task->getSubtype() == 'questionnaire') && ($task->assessable) && ($task->type_grading == 'questionnaire_type_grading_marks') && (($operator) || ((!$operator) && ($task->grading_visibility) && ($task->public_global_marks))))) {
       $text = elgg_echo('item:object:socialwire_mark');
       elgg_register_menu_item('title', array('name' => 'socialwire_marks_title','text' => $text,'href' => "socialwire_marks/show/task/$task_guid",'link_class' => 'elgg-button elgg-button-action'));
   }
}
?>
