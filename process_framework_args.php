<?php
function _parser_framework_args_($arg)
{
    $arr = preg_split('/,/i', $arg);
    return array($arr[0], $arr[1], $arr[2], array_slice($arr, 3));
}

function _build_framework_args_($file, $component, $fun, $args)
{
    return join(',', array($file, $component, $fun)) . ',' . join(',', $args);
}
