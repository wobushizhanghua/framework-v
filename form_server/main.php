<?php
$ff = $_GET['form_file'];
$fn = $_GET['form_name'];
if (!(in_array($ff, $files_contain_form) && in_array($fn, $the_forms))) {
    die("非法提交");
}
include_once(VIEW_ROOT . "/workspace/$ff.php");
$classname = "Form_$fn";
$f = new $classname;
$f->OnSubmit();
global $url;
$url->SetBack();
$url->SetParam("time", time());
$url->Go();
