<?php
include_once(APP_VIEW_ROOT.'/workspace/test.html.php');
global $components;
if(!isset($components['Var_title']))
{
	$components['Var_title'] = new Var_title;
	$components['Var_title']->innerHTMLArray = array();
}
$components['Var_title']->innerHTMLArray[]=array(
		 array(
				"str"=>"<h1>",
				"next_var"=>"{title}"),
			 array(
				"str"=>"</h1>",
				"next_var"=>"")
			);
if(!isset($components['Var_list']))
{
	$components['Var_list'] = new Var_list;
	$components['Var_list']->innerHTMLArray = array();
}
$components['Var_list']->innerHTMLArray[]=array(
		 array(
				"str"=>"
		<div>",
				"next_var"=>"{id}"),
			 array(
				"str"=>"</div>
		<div>",
				"next_var"=>"{content}"),
			 array(
				"str"=>"</div>
	",
				"next_var"=>"")
			);
if(!isset($components['Var_content']))
{
	$components['Var_content'] = new Var_content;
	$components['Var_content']->innerHTMLArray = array();
}
$components['Var_content']->innerHTMLArray[]=array(
		 array(
				"str"=>"
	<h1>this is content</h1>
	",
				"next_var"=>"")
			);
$components['Var_content']->innerHTMLArray[]=$components['Var_list'];
$components['Var_content']->innerHTMLArray[]=array(
		 array(
				"str"=>"
",
				"next_var"=>"")
			);
include_once(APP_VIEW_ROOT.'/v/template_c/test.html.php');
