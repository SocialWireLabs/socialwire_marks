<?php
/*
 * © Copyright by Laboratorio de Redes 2012
 */

$url = $_SERVER['REDIRECT_URL'];
$student_str = "<span style=\"font-weight:bold;\"><a href=\"{$url}?sort_by=student\">" . elgg_echo('student') . '</a></span>';
$average_str = "<span style=\"font-weight:bold;\"><a href=\"{$url}?sort_by=value\">" . elgg_echo('mark:average') . '</a></span>';
$tasks_header = '';


$user_guid = elgg_get_logged_in_user_guid();

$user_type = elgg_extract('user_type', $vars, 'student');
$subject_id = elgg_extract('subject_id', $vars, false);

if ($subject_id) {
    $subject = get_entity($subject_id);
    $tasks = array();
    if ($subject->task_enable != 'no')
       $tasks = elgg_get_entities_from_metadata(array('type' => 'object','subtype' => 'task','container_guid' => $subject_id,'reverse_order_by' => true,'limit' => 0,'metadata_name_value_pairs' => array(array('name' => 'assessable', 'value' => 1),array('name' => 'type_grading', 'value' => 'task_type_grading_marks'))));
    
    /*if ($subject->test_enable != 'no') {
       $tests = elgg_get_entities_from_metadata(array('type' => 'object','subtype' => 'test','container_guid' => $subject_id,'reverse_order_by' => true,'limit' => 0,'metadata_name_value_pairs' => array(array('name' => 'assessable', 'value' => 1),array('name' => 'type_grading', 'value' => 'test_type_grading_marks'))));
       if ($tasks)
          $tasks = array_merge($tasks,$tests);
       else
          $tasks = $tests;
    }*/
    /*if ($subject->questionnaire_enable != 'no') {
       $questionnaires = elgg_get_entities_from_metadata(array('type' => 'object','subtype' => 'questionnaire','container_guid' => $subject_id,'reverse_order_by' => true,'limit' => 0,'metadata_name_value_pairs' => array(array('name' => 'assessable', 'value' => 1),array('name' => 'type_grading', 'value' => 'questionnaire_type_grading_marks'))));
       if ($tasks)
          $tasks = array_merge($tasks,$questionnaires);
       else
	  $tasks = $questionnaires; 
    }*/
    if ($subject->e_portfolio_enable != 'no') {
        $options = array('type_subtype_pairs' => array('object' => 'e_portfolio_group_setup'), 'limit' => false, 'container_guid' => $subject_id);
        $e_portfolio_group_setup = elgg_get_entities_from_metadata($options);
        $e_portfolio_group_setup = $e_portfolio_group_setup[0];
	if (($e_portfolio_group_setup)&&($e_portfolio_group_setup->rating_type == 'e_portfolio_rating_type_marks')) {
	   $e_portfolio_group_setup_guid = $e_portfolio_group_setup->getGUID();
	   $e_portfolio_tasks = array();
	   if ($e_portfolio_group_setup->var_pages) {
              $task_title = elgg_echo('e_portfolio');
	      $task_id = $e_portfolio_group_setup_guid . "_-1";
	      $task_mark_weight = $e_portfolio_group_setup->mark_weight;
	      $e_portfolio_task = array('type' => 'e_portfolio_task', 'title' => $task_title, 'page_number' => '-1', 'task_id' => $task_id, 'mark_weight' => $task_mark_weight,'rating_visibility' => $e_portfolio_group_setup->grading_visibility, 'public_global_marks' => $e_portfolio_group_setup->public_global_marks);
	      $e_portfolio_tasks[] = $e_portfolio_task;
	   } else {
	      $i=1;
	      while ($i<=$e_portfolio_group_setup->num_pages) {
	         $task_title = elgg_echo('e_portfolio') . " (#" . $i . ")";
	         $task_id = $e_portfolio_group_setup_guid . "_" . $i;
		 $mark_weight_stream = $e_portfolio_group_setup->mark_weight;
		 $mark_weight_array = explode(Chr(26),$mark_weight_stream);
		 $task_mark_weight = $mark_weight_array[$i-1];
	         $e_portfolio_task = array('type' => 'e_portfolio_page_task', 'title' => $task_title, 'page_number' => $i, 'task_id' => $task_id, 'mark_weight' => $task_mark_weight, 'rating_visibility' => $e_portfolio_group_setup->grading_visibility, 'public_global_marks' => $e_portfolio_group_setup->public_global_marks);
		 $i=$i+1;
		 $e_portfolio_tasks[$i] = $e_portfolio_task;
              }
	   }
	   if ($tasks)
              $tasks = array_merge($tasks,$e_portfolio_tasks);
           else
	      $tasks = $e_portfolio_tasks; 
	}
    }

    if ($tasks) {
        $task_column_width = round(70 / count($tasks));
	$index=1;
	$my_sum_weights=0;
        foreach ($tasks as $task) {
	    if (($task['type']!='e_portfolio_task')&&($task['type']!='e_portfolio_page_task')) {
               $task_id = $task->getGUID();
               $task_url = $task->getURL();
               $task_weight = $task->mark_weight;
	       $task_title = $task->title;
            } else {
	       $task_id = $task['task_id'];
	       $task_url = '';
	       $task_weight = $task['mark_weight'];
	       $task_title = $task['title'];
	    }

	    if ($task_weight === null)
               $task_weight = 1;
 
	    $task_weights[$task_id] = $task_weight;
	    $task_visibilities[$task_id] = true;
	    if (($task['type']=='e_portfolio_task')||($task['type']=='e_portfolio_page_task')) {
	       if (!$e_portfolio_group_setup->public_global_marks) {
	             $task_visibilities[$task_id] = false;
	       }
	    } else {
	       if (elgg_instanceof($task, 'object', 'test')) {
	          if (!$task->public_global_marks)
	             $task_visibilities[$task_id] = false;
	       } else {
	          if ((!$task->grading_visibility) || (!$task->public_global_marks)) {
	             $task_visibilities[$task_id] = false;
		  }
	       }
	    } 
	    $title = "T " . $index;
	    $tasks_header .= "<th width=\"{$task_column_width}%\"><a href=\"{$task_url}\"><img border=\"0\" width=\"16\" height=\"16\" title=\"{$task_title}\" alt=\"{$title}\"/></a> ({$task_weight}%)</th>";
	    $index=$index+1;
        }

        $students_info = array();
        $student_id = elgg_extract('student_id', $vars, null);
        $marks = socialwire_marks_get_marks(null, $student_id, null, $subject_id);

        if ($marks) {
            foreach ($marks as $mark) {
	        $student_id = $mark->student_guid;
		$student = get_entity($student_id);
		if (elgg_instanceof($student, 'group')){
		   $subgroups=elgg_get_entities(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'),'limit' => 0,'container_guids' => $subject_id));
	           if ($subgroups) {
		      $is_group_member_or_subgroup=in_array($student,$subgroups);
                   } else {
		      $is_group_member_or_subgroup=false;
		   }
	        } else {
		   $is_group_member_or_subgroup=check_entity_relationship($student_id, 'member', $subject_id);
		}
		if ($is_group_member_or_subgroup){ 
		   $mark_value = $mark->value;
                   $task_id = $mark->task_guid;
		   $task = get_entity($task_id);
		   if (elgg_instanceof($task, 'object', 'e_portfolio_group_setup')) {
		      $task_id = $mark->task_guid . "_" . $mark->task_page_number;
		   }
		   $visibility = $task_visibilities[$task_id];
		   
		   if (($user_type != 'professor') && (!$visibility)) {
		      $is_owner = false;
	              if (elgg_instanceof($student, 'group')){
	                 $group_members = get_group_members($student_id, 0);
	                 if ($group_members) {
                            foreach ($group_members as $one_group_member) {
	                       $one_group_member_guid = $one_group_member->getGUID();
                               if ($one_group_member_guid == $user_guid) {
		                  $is_owner = true;
		                  break;
		               }
                            }            
                         }
	              } else {
	                 if ($student_id == $user_guid)
	                    $is_owner = true;
	              }
		      if (($is_owner)&&((elgg_instanceof($task, 'object', 'e_portfolio_group_setup'))||(elgg_instanceof($task, 'object', 'test'))||($task->grading_visibility))) {
		         $visibility = true;
		      }
		   }
		
                   if (($user_type == 'professor') || $visibility ) {          
		      if ($mark->group_members) {
		         if (is_array($mark->group_members)) {
		            foreach ($mark->group_members as $one_group_member_id) {
			       
                               if (!$students_info[$one_group_member_id]['student']){
                                  $students_info[$one_group_member_id]['student'] = get_user($one_group_member_id)->name;
                               }
			       if ($mark_value){
                                  $students_marks[$one_group_member_id][$task_id] = $mark_value; 
			       } else {
		                  $students_marks[$one_group_member_id][$task_id] = "0";
			       }
                               $marks_sum[$one_group_member_id] += $task_weights[$task_id] * $mark_value;
                               $task_weights_sum[$one_group_member_id] += $task_weights[$task_id];
                               $students_info[$one_group_member_id]['value'] = $marks_sum[$one_group_member_id] / $task_weights_sum[$one_group_member_id];
			    }
			 } else {
			    $one_group_member_id = $mark->group_members;
			    if (!$students_info[$one_group_member_id]['student']){
                               $students_info[$one_group_member_id]['student'] = get_user($one_group_member_id)->name;
                            }
			    if ($mark_value){
                               $students_marks[$one_group_member_id][$task_id] = $mark_value; 
			    } else {
		               $students_marks[$one_group_member_id][$task_id] = "0";
			    }
                            $marks_sum[$one_group_member_id] += $task_weights[$task_id] * $mark_value;
                            $task_weights_sum[$one_group_member_id] += $task_weights[$task_id];
                            $students_info[$one_group_member_id]['value'] = $marks_sum[$one_group_member_id] / $task_weights_sum[$one_group_member_id];
			 }
		      } else {
		          if (!$students_info[$student_id]['student'])
                            $students_info[$student_id]['student'] = get_user($student_id)->name;
			 if ($mark_value){
                            $students_marks[$student_id][$task_id] = $mark_value; 
			 } else {
		            $students_marks[$student_id][$task_id] = "0";
			 }
                         $marks_sum[$student_id] += $task_weights[$task_id] * $mark_value;
                         $task_weights_sum[$student_id] += $task_weights[$task_id];
                         $students_info[$student_id]['value'] = $marks_sum[$student_id] / $task_weights_sum[$student_id];
		      }
                  }
               }
	    }
        }
	
        $sort_by = elgg_extract('sort_by', $vars, 'student');
        $students_info = socialwire_marks_sort_marks($students_info, $sort_by);
        $count = count($students_info);
        $offset = get_input('offset', 0);
        $limit = get_input('limit', 20);
        $students_info = array_slice($students_info, $offset, $limit, true);
        $marks_view = '';
        foreach ($students_info as $student_id => $student_info) {
            $student = get_entity($student_id);
            $student_icon = elgg_view_entity_icon($student, 'tiny');
	    $student_name = $student->name;
	    $student_information = $student_icon.$student_name;
            $marks_view .= "<tr><th width=\"20%\">{$student_information}</th>";
            $change_sum_weights = false;
	    foreach ($task_weights as $task_id => $task_weight) {
	        $task = get_entity($task_id);		
		$mark_array = array('student' => $student_id, 'task' => $task_id, 'value' => $students_marks[$student_id][$task_id]);
                $mark_value_view = elgg_view('socialwire_marks/mark_value', array('mark' => $mark_array, 'user_type' => 'student'));
                $marks_view .= "<th width=\"{$task_column_width}%\">$mark_value_view</th>";
		
		$visibility = $task_visibilities[$task_id];
		   
		if (($user_type != 'professor') && (!$visibility)) {
		   $is_owner = false;
	           if (elgg_instanceof($student, 'group')){
	              $group_members = get_group_members($student_id, 0);
	              if ($group_members) {
                         foreach ($group_members as $one_group_member) {
	                    $one_group_member_guid = $one_group_member->getGUID();
                            if ($one_group_member_guid == $user_guid) {
		               $is_owner = true;
		               break;
		            }
                         }            
                      }
	           } else {
	              if ($student_id == $user_guid)
	                 $is_owner = true;
	           }
		   if (($is_owner)&&((elgg_instanceof($task, 'object', 'e_portfolio_group_setup'))||(elgg_instanceof($task, 'object', 'test'))||($task->grading_visibility))) {
		      $visibility = true;
		   }
		}
		if (($task->not_response_is_zero)&&(($visibility)||($user_type == 'professor'))) {
		   if ($task->getSubtype() == 'task')
	              $student_response_subtype = 'task_answer';
	           elseif ($task->getSubtype() == 'test')
	              $student_response_subtype = 'test_answer';
	           elseif ($task->getSubtype() == 'questionnaire')
	              $student_response_subtype = 'questionnaire_answer';
	   
	           if (!$task->subgroups) {
                      $student_response = elgg_get_entities_from_relationship(array('type' => 'object','subtype' => $student_response_subtype,'relationship' => $student_response_subtype,'relationship_guid' => $task_id,'inverse_relationship' => false,'owner_guid' => $student_id));
	           } else {
		      $user_subgroup = elgg_get_entities_from_relationship(array('type_subtype_pairs' => array('group' => 'lbr_subgroup'),'container_guids' => $subject_id,'relationship' => 'member','inverse_relationship' => false,'relationship_guid' => $student_id));
                      $user_subgroup=$user_subgroup[0];
		      $student_response="";
		      if ($user_subgroup) {
                         $user_subgroup_guid=$user_subgroup->getGUID();
	                 $student_response = elgg_get_entities_from_relationship(array('type' => 'object','subtype' => $student_response_subtype,'relationship' => $student_response_subtype,'relationship_guid' => $task_id,'inverse_relationship' => false,'container_guid' => $user_subgroup_guid));
		      }
	           }
		
		   //if ((empty($students_marks[$student_id][$task_id]))&&($students_marks[$student_id][$task_id]!="0")&&(empty($student_response))){
		   if (((strcmp($task->type_delivery,"online")==0)&&(empty($students_marks[$student_id][$task_id]))&&($students_marks[$student_id][$task_id]!="0")&&(empty($student_response)))||((strcmp($task->type_delivery,"online")!=0)&&((empty($student_response))||((empty($students_marks[$student_id][$task_id]))&&($students_marks[$student_id][$task_id]!="0"))||($students_marks[$student_id][$task_id]=="not_qualified")))){
		      $task_weights_sum[$student_id] += $task_weights[$task_id];
		      if (!$change_sum_weights)
		         $change_sum_weights=true;
		   }
		}
            }
	    if ($change_sum_weights){
	       $student_info['value'] = $marks_sum[$student_id] / $task_weights_sum[$student_id];
	    }
	    
            $mark_average_array = array('student' => $student_id, 'average' => $student_info['value'], 'type' => NUMERIC10);
            $mark_average_view = elgg_view('socialwire_marks/mark_value', array('mark' => $mark_average_array));
            $marks_view .= "<th>$mark_average_view</th></tr>";
        }
        $marks_pagination = elgg_view('navigation/pagination', array('base_url' => $_SERVER['REQUEST_URI'], 'offset' => $offset, 'count' => $count, 'limit' => $limit));
    }
}
?>
<div class="contentWrapper">
   <div style="overflow-x:auto">
    <table width="100%">
        <tr>
            <th><?php echo $student_str; ?></th>
            <?php echo $tasks_header; ?>
            <th><?php echo $average_str; ?></th>
        </tr>    
        <?php echo $marks_view; ?>
    </table>
    <?php echo $marks_pagination; ?>
</div>
</div>