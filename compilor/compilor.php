<?php
require_once(dirname(__FILE__) . "/file/file.php");
require_once(dirname(__FILE__) . "/parser/parser.php");

class Compilor
{
    private $file;
    private $parser;
    private $form_server_content = "<?php\n\$the_forms = array('nodata');\n?>";
    private $files_contain_form = array();

    function __construct()
    {
        $this->parser = new Compilor_Parser();
        $this->file = new ciFile();
    }

    function main()
    {
        $files = $this->file->GetFolderFiles(APP_VIEW_ROOT . "/templates");
        foreach ($files as $file) {
            if (!preg_match('/\.html$/i', $file['name'])) {
                continue;
            }
            $this->compile($file['name'], $file['src']);
        }

        /*
        $this->file->Write(APP_VIEW_ROOT . "/v/form_server/validate_forms.php", $this->form_server_content);

        $content = "<?php\n" .
            "\$files_contain_form = array(";
        foreach ($this->files_contain_form as $file) {
            $content .= "'$file',";
            $flag = true;
        }
        if ($flag) {
            $content = substr($content, 0, -1);
        }
        $content .= ");\n?>";

        $this->file->Write(APP_VIEW_ROOT . "/v/form_server/files_contain_form.php", $content);
        */
    }

    function compile($filename, $src)
    {
        $user_classes = array();

        //list($files,$folders) = $this->file->ReadFolder($srcfolder);
        $f = array(
            'src' => $src,
            'name' => $filename
        );


        $content = $this->file->GetContent($f['src']);

        $this->parser->SetContent($filename, $content);

        // $element_tags = $this->parser->Process($f['name'],'Element');
        $this->parser->Process($f['name'], 'Var');
        // $this->parser->Process($f['name'],'List');

        // $form_tags = $this->parser->Process($f['name'],'Form');
        // $this->CreateFormServerContent($filename,$form_tags);

        // $this->parser->Process($f['name'],'Pager');


        //$this->CreatePHP($f['name']);

        $workspace_contents = $this->parser->GetWorkspaceContent();

        $tempate_c_contents = $this->parser->GetTemplateCContent();

        //一个模板文件 生成几个?或者一个workspace文件 workspace里面是抽象出来的框架代码
        //和两个template_c文件


        $this->file->Write(APP_VIEW_ROOT . "/v/template_c/{$f['name']}.php", $tempate_c_contents);

		$this->WriteWorkSpaceContentToFile($f['name'], $workspace_contents);

        $this->file->Write(APP_VIEW_ROOT . "/v/template_c/php/{$f['name']}.php", $this->parser->tcphp);
        // $file_classes = $this->parser->GetUserClasses();

        /*		foreach($element_tags as $tag)
                {
                    if(isset($tag['properties']['src']))
                    $this->compile($tag['properties']['src'],VIEW_ROOT."/templates/elements/{$tag['properties']['src']}");
                }
        */
    }


    function WriteWorkSpaceContentToFile($filename, $new_contents)
    {
        $classes_names = array();
        $file = APP_VIEW_ROOT . "/workspace/$filename.php";
        if (!file_exists($file)) {
            $new_file_content = '';
            foreach ($new_contents as $class) {
                $new_file_content .= $class['str'];
            }
            $new_file_content = str_replace('?>', "\n/*do not delete*/?>", $new_file_content);
            $this->file->Write($file, $new_file_content);
            return;
        }

        $former_content = $this->file->GetContent($file);
        $new_file_content = '';

        $user_classes = $this->parser->GetUserClasses();
        if (!AUTO_COMPILE) {
            var_dump($user_classes);
        }
        foreach ($user_classes[$filename] as $c => $prop) {
            if (in_array($c, $classes_names)) {
                echo "警告 ：发现重复 name $c in file :$filename\n";
                var_dump($classes_names);
                die();
            }
            $classes_names[] = $c;
            $flag = preg_match('/<\?php\s*class\s*' . $c . '\s*extends.*\/\*do not delete\*\/\s*\?>/sU',
                $former_content,
                $former_class_def);
            if (!$flag) {//新添加的标签
                $new_file_content .= str_replace('?>', "\n/*do not delete*/?>", $new_contents[$c]['str']);
                continue;
            }

            $new_class_def = $former_class_def[0];

            /*用户 定义属性 现在没用*/
            $flag = preg_match('/\/\*auto generate user properties \*\/(.*)\/\*user end \*\//sU', $new_class_def,
                $former_class_user_property);
            if ($flag) {
                if (!AUTO_COMPILE) {
                    var_dump($former_class_user_property);
                }
            }
            /*结束*/

            foreach ($new_contents[$c]['struct']['code_body']['functions'] as $fun_name => $fun_code) {
                if (!preg_match('/function\s*' . $fun_name . '\s*\(/', $former_class_def[0])) {
                    $new_class_def = preg_replace('/}\s*\/\*do not delete\*\/\s*\?>/',
                        "\t" . $fun_code . "\n}/*do not delete*/?>",
                        $new_class_def);
                }
            }
            $tmp_var_code = '';
            foreach ($new_contents[$c]['struct']['code_body']['vars'] as $var_code) {
                $tmp_var_code .= "\n\t" . $var_code;
            }
            $new_class_def = preg_replace('/\/\*auto generate properties \*\/.*\/\*end \*\//sU',
                '/*auto generate properties */' .
                $tmp_var_code . "\n\t" .
                '/*end */',
                $new_class_def);

            $new_file_content .= ($new_class_def);

        }
        $this->file->Write($file, $new_file_content);

    }

    function CreateFormServerContent($filename, $formtags)
    {
        //	var_dump($formtags);
        if (count($formtags) == 0) {
            return;
        }
        $this->files_contain_form[] = $filename;
        foreach ($formtags as $formtag) {
            if (strlen($formtag['properties']['name']) > 0) {

                $this->form_server_content = str_replace(')', ',"' . $formtag['properties']['name'] . '")',
                    $this->form_server_content);
            }
        }
    }

    function CreatePHP($filename)
    {
        $file = APP_VIEW_ROOT . "/v/template_c/php/$filename.php";

        $content = $this->file->GetContent(APP_VIEW_ROOT . "/v/template_c/php/$filename.php");

        $user_classes = $this->parser->GetUserClasses();
        //var_dump($user_classes);
        $user_files = $this->parser->GetUserFiles();

        $code1 = "include_once(APP_VIEW_ROOT.'/workspace/$filename.php');\n"
            . 'global $components;' . "\n";
        foreach ($user_classes[$filename] as $class => $prop) {
            //foreach($classes as $c)
            $code1 .= "if(!isset(\$components['$class']))\n{\n\t\$components['$class'] = new $class;\n}
	\$components['$class']->innerHTMLArray=" . $this->parser->CreateInnerHTMLArrayStr($prop['innerHTMLArray']) . ";
	\$components['$class']->innerVarArray=" . $this->parser->CreateInnerVarArrayStr($prop['innerVarArray'],
                    $prop['innerVarInitVal']) . ";\n";
        }
        $code1 .= "include_once(APP_VIEW_ROOT.'/v/template_c/$filename.php');\n";

        $code2 = '$user_files = array();' . "\n";

        foreach ($user_files as $c) {
            $code2 .= '$user_files["' . $c . '"][] = "' . $c . '";' . "\n";
        }

        $code = "<?php\n/*auto generate do not modify */\n" .
            $code1 .
            //	$code2.
            "/*auto generate do not modify */\n?>";

        $r = preg_replace('/\/\*auto generate do not modify \*\/.*\/\*auto generate do not modify \*\//s', $code,
            $content);
        $this->file->Write($file, $code);
    }
}
