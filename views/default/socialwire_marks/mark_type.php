<?php

/*
 * © Copyright by Laboratorio de Redes 2012
 */

$internalname = elgg_extract('internalname', $vars, 'mark_type');
$mark_type = elgg_extract('mark_type', $vars, NUMERIC10);

$options = array('name' => $internalname, 'value' => $mark_type, 'options_values' => array(
        NUMERIC10 => elgg_echo('mark:numeric10'),
        NUMERIC100 => elgg_echo('mark:numeric100'),
        BOOLEAN => elgg_echo('mark:boolean'),
        STRINGUNI => elgg_echo('mark:stringuni'),
        STRINGHSC => elgg_echo('mark:stringhsc')));

$view = elgg_view('input/dropdown', $options);
echo $view;
?>