<?php

/*
 * © Copyright by Laboratorio de Redes 2012
 */

$result = true;
$user_id = elgg_get_logged_in_user_guid();
$page_number = get_input('page_number');

$keys = array_keys($_POST);
foreach ($keys as $key) {
    $access = elgg_set_ignore_access(true);
    if (strstr($key, 'mark_value_')) {
        $mark_id = substr($key, 11);
        $mark = get_entity($mark_id);
        if ($mark && socialwire_marks_is_professor($user_id, $mark->container_guid)) {
            $task_id = $mark->task_guid;
	    $task = get_entity($task_id);
            $student_id = $mark->student_guid;
            $mark_value = $_POST[$key];
	    
	    if ($task->getSubtype() == 'e_portfolio_group_setup') {
	       $student_e_portfolio = elgg_get_entities(array('type'=>'object','subtype'=>'e_portfolio','limit'=>false,'container_guid'=>$task->container_guid,'owner_guid' => $student_id));
	       $student_e_portfolio = $student_e_portfolio[0];
	       $options = array('type_subtype_pairs' => array('object' => 'e_portfolio_page'), 'limit' => false, 'container_guid' => $student_e_portfolio->getGUID(),'metadata_name_value_pairs' => array('name'=>'page_number','value' =>$page_number));
               $student_response = elgg_get_entities_from_metadata($options);
	    } else {
	       if ($task->getSubtype() == 'task')
	          $student_response_subtype = 'task_answer';
	       elseif ($task->getSubtype() == 'test')
	          $student_response_subtype = 'test_answer';
	       elseif ($task->getSubtype() == 'questionnaire')
	          $student_response_subtype = 'questionnaire_answer';
	   
	       if (!$task->subgroups) {
                  $student_response = elgg_get_entities_from_relationship(array('type' => 'object','subtype' => $student_response_subtype,'relationship' => $student_response_subtype,'relationship_guid' => $task_id,'inverse_relationship' => false,'owner_guid' => $student_id));
	       } else {
	          $student_response = elgg_get_entities_from_relationship(array('type' => 'object','subtype' => $student_response_subtype,'relationship' => $student_response_subtype,'relationship_guid' => $task_id,'inverse_relationship' => false,'container_guid' => $student_id));
	       }	    
	    } 

            if ($mark_value != '' && $mark_value >= 0) {
	       if ($task->getSubtype() == 'e_portfolio_group_setup') {
	          $result = socialwire_marks_update_mark($mark_id, $mark_value, null,$page_number);
		  if ($result && $student_response)
	             $student_response[0]->rating = $mark_value;
	       } else {
                  $result = socialwire_marks_update_mark($mark_id, $mark_value);
                  if ($result && $student_response)
	             $student_response[0]->grading = $mark_value;		
	       }
            } else {
               $result = socialwire_marks_delete_marks(array($mark));
	       if ($task->getSubtype() == 'e_portfolio_group_setup') {
                  if ($result && $student_response)
                     $student_response[0]->rating = 'not_qualified';
	       } else {
	          if ($result && $student_response)
                     $student_response[0]->grading = 'not_qualified';
	       }
            }
	    
        } else
            $result = false;
    } else if (strstr($key, 'new_mark_')) {
        $info = explode('_', $key);
        $student_id = $info[2];
        $task_id = $info[3];
        $mark_value = $_POST[$key];
        if ($mark_value != '' && $mark_value >= 0 && socialwire_marks_is_professor($user_id, get_entity($task_id)->container_guid)) {
	    $task = get_entity($task_id);

	    if ($task->getSubtype() == 'e_portfolio_group_setup') {
	       $student_e_portfolio = elgg_get_entities(array('type'=>'object','subtype'=>'e_portfolio','limit'=>false,'container_guid'=>$task->container_guid,'owner_guid' => $student_id));
	       $student_e_portfolio = $student_e_portfolio[0];
	       $options = array('type_subtype_pairs' => array('object' => 'e_portfolio_page'), 'limit' => false, 'container_guid' => $student_e_portfolio->getGUID(),'metadata_name_value_pairs' => array('name'=>'page_number','value' =>$page_number));
               $student_response = elgg_get_entities_from_metadata($options);
	    } else {
	       if ($task->getSubtype() == 'task')
	          $student_response_subtype = 'task_answer';
	       elseif ($task->getSubtype() == 'test')
	          $student_response_subtype = 'test_answer';
	       elseif ($task->getSubtype() == 'questionnaire')
	          $student_response_subtype = 'questionnaire_answer';
	   
	       if (!$task->subgroups) {
	          $student_response = elgg_get_entities_from_relationship(array('type' => 'object','subtype' => $student_response_subtype,'owner_guid' => $student_id,'relationship' => $student_response_subtype,'relationship_guid' => $task_id,'inverse_relationship' => false));
	       } else {
	          $student_response = elgg_get_entities_from_relationship(array('type' => 'object','subtype' => $student_response_subtype,'container_guid' => $student_id,'relationship' => $student_response_subtype,'relationship_guid' => $task_id,'inverse_relationship' => false));
	       }
	    }
	    
	    if ($mark_value != '' && $mark_value >= 0) {
	       $result = socialwire_marks_create_mark($user_id, $student_id, $task_id, $mark_value, $page_number);
               if ($result && $student_response) 
                  $student_response[0]->rating = $mark_value;
	    } else {
               $result = socialwire_marks_create_mark($user_id, $student_id, $task_id, $mark_value);
               if ($result && $student_response) 
                  $student_response[0]->grading = $mark_value;
	    }	    
        }
    }
    elgg_set_ignore_access($access);
    if ($result == false) {
        register_error(elgg_echo('save:mark:error'));
        break;
    }
}

if ($result)
    system_message(elgg_echo('save:mark:success'));

forward($_SERVER['HTTP_REFERER']);
?>
