<?php
/*
 * © Copyright by Laboratorio de Redes 2012
 */

$mark = elgg_extract('mark', $vars, false);
$user_type = elgg_extract('user_type', $vars, 'student');

$view = '';
if ($mark) {
    if (!is_array($mark)) {
        $mark_id = $mark->getGUID();
        $mark_type = socialwire_marks_get_mark_type(get_entity($mark->task_guid));
        $mark_value = $mark->value;
        $input_name = "mark_value_{$mark_id}";
    } else if ($mark['average']) {
        $mark_type = $mark['type'];
        $mark_value = $mark['average'];
        $input_name = "mark_average_value_{$mark['student']}";
    } else {
        $mark_type = socialwire_marks_get_mark_type(get_entity($mark['task']));
        $mark_value = $mark['value'];
        $input_name = "new_mark_{$mark['student']}_{$mark['task']}";
    }

    if (!$mark_type)
        $mark_type = NUMERIC10;
    if ($mark_value)
        $mark_value = socialwire_marks_convert_mark_value($mark_value, $mark_type, true);

    switch ($mark_type) {
        case NUMERIC10: case NUMERIC100:
            $input_type = 'text';
            $options = array('onchange' => 'check_{$input_name}({$mark_value})');
            break;
        case BOOLEAN:
            $input_type = 'dropdown';
            $options = array('options_values' => array(
                    -1 => '',
                    PASS => elgg_echo('mark:pass'),
                    FAIL => elgg_echo('mark:fail'),
                    ));
            break;
        case STRINGHSC:
            $input_type = 'dropdown';
            $options = array('options_values' => array(
                    -1 => '',
                    HONOURS => elgg_echo('mark:honours'),
                    OUTSTANDING => elgg_echo('mark:outstanding'),
                    VERYGOOD => elgg_echo('mark:verygood'),
                    GOOD => elgg_echo('mark:good'),
                    SUFFICIENT => elgg_echo('mark:sufficient'),
                    INSUFFICIENT => elgg_echo('mark:insufficient'),
                    VERYDEFICIENT => elgg_echo('mark:verydeficient'),
                    ));
            break;
        case STRINGUNI:
            $input_type = 'dropdown';
            $options = array('options_values' => array(
                    -1 => '',
                    HONOURS => elgg_echo('mark:honours'),
                    OUTSTANDING => elgg_echo('mark:outstanding'),
                    VERYGOOD => elgg_echo('mark:verygood'),
                    SUFFICIENT => elgg_echo('mark:pass'),
                    INSUFFICIENT => elgg_echo('mark:fail'),
                    ));
            break;
    }

    $options['name'] = $input_name;

    if ($user_type == 'professor') {
        $options['value'] = $mark_value;
        $view = elgg_view("input/{$input_type}", $options);
    } else {
        if ($mark_value!="not_proceed") 
           $options['value'] = socialwire_marks_mark_value_to_string($mark_value, $mark_type);
	else 
	   $options['value']="";
        $view = elgg_view("output/{$input_type}", $options);
    }
}

echo $view;

if ($user_type == 'professor' && ($mark_type == NUMERIC10 || $mark_type == NUMERIC100)) {
    ?>

    <script type="application/javascript">
        function check_<?php echo $input_name; ?>(prevmark) {
            var newmark = document.getElementsByName("<?php echo $input_name; ?>").item(0).value;
            var maxvalue = 10;
            if (<?php echo $mark_type; ?> == <?php echo NUMERIC100; ?>)
                maxvalue = 100;
            if (isNaN(newmark) || newmark < 0 || newmark > maxvalue) {
                if (prevmark === undefined)
                    prevmark = "";
                document.getElementsByName("<?php echo $input_name; ?>").item(0).value = prevmark;
                alert("<?php echo elgg_echo('mark:invalid:value'); ?>");
            }
        }
    </script>
    <?php
}
?>