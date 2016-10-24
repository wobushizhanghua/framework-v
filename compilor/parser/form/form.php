<?php

class Compilor_Parser_Form
{
    public $wc = array();
    private $user_classes = array();
    private $parser;
    private $dom;

    function __construct($common_parser_object)
    {
        $this->parser = $common_parser_object;
        if (!method_exists($this->parser, 'SearchTag')) {
            die('error in Compilor_Parser_Form');
        }
        $this->dom = new DOMDocument();
    }

    function main()
    {
        $tags = $this->parser->SearchTag('form');
        $this->tags = $tags;
        foreach ($tags as $t) {
            $innerTags = $this->parser->ProcessInnerTag($t);
            if (count($innerTags) > 0) {
                var_dump($innerTags);//die();
            }
            foreach ($innerTags as $it) {
                $t['properties']['innerHTML'] = str_replace($it['former_str'],
                    "{innertag_" . $it['properties']['name'] . "}",
                    $t['properties']['innerHTML']);
                $this->GCode($it['properties']['name'], $it['properties']['basename'], $it['properties'], null, false);
            }
            //处理可能预先生成的php，或者用户自己定义的php
            $t['properties']['innerPHP'] = $this->parser->SearchInnerPHP($t['properties']['innerHTML']);
            //end

            $this->GCode($t['properties']['name'], 'Form', $t['properties']);
            $name = $t['properties']['name'];
            $this->parser->ReplaceTag($t['former_str'], '<?php $components["Form_' . $name . '"]->OutPutContent();?>');

        }
        return array($this->wc, $this->parser->GetContent());
    }

    function GetTags()
    {
        return $this->tags;

    }

    function GCode($name, $basename, $properties, $innertags = null, $need_initial = true)
    {
        //记录生成的类
        if ($need_initial) {
            $this->user_classes[$this->parser->filename][$basename . '_' . $name] = array();
        }

        $code_begin =
            "<?php
class {$basename}_$name extends Component$basename{";

        $code_body = array(
            "var_start" => array("/*auto generate properties */"),
            "vars" => array(),
            "var_end" => array("/*end */\n"),
            "functions" => array()
        );

        $code_end = "\n}?>";
        if (count($properties['innerPHP']) > 0) {
            $init_inner_var = array();
            foreach ($properties['innerPHP'] as $key => $innerPHP) {
                $properties['innerHTML'] = str_replace($innerPHP, '{PHP' . $key . '}', $properties['innerHTML']);
                $init_inner_var['{PHP' . $key . '}'] = str_replace('OutPutContent', 'GetOutPutContent',
                    substr($innerPHP, 6, -3));
            }
        }
        $this->user_classes[$this->parser->filename][$basename . '_' . $name]['innerHTMLArray'] = $this->parser->GetInnerHTMLArray($properties['innerHTML']);
        $this->user_classes[$this->parser->filename][$basename . '_' . $name]['innerVarArray'] = $this->parser->GetInnerVarArray($properties['innerHTML']);
        $this->user_classes[$this->parser->filename][$basename . '_' . $name]['innerVarInitVal'] = $init_inner_var;

        $have_action = false;
        foreach ($properties as $p => $value) {
            if ($p == 'action') {
                $have_action = true;
            }
            $value = str_replace('"', '\"', $value);
            $code_body['vars'][] = "public \${$p} = \"$value\";";
        }
        if (!$have_action) {
            $code_body['vars'][] = "public \$action = '/main.php?action=server&form_name=$name&form_file={$this->parser->filename}';";
        }
        $code_body['functions']['OnSubmit'] = "function OnSubmit()\n{\n\t}";

        $this->wc["{$basename}_$name"]['struct'] = array(
            "code_begin" => $code_begin,
            "code_body" => $code_body,
            "code_end" => $code_end
        );
        $this->wc["{$basename}_$name"]['str'] = $code_begin;
        foreach ($code_body as $units) {
            foreach ($units as $str) {
                $this->wc["{$basename}_$name"]['str'] .= "\n\t" . $str;
            }
        }
        $this->wc["{$basename}_$name"]['str'] .= $code_end;
    }

    function GetUserClasses()
    {
        return $this->user_classes;
    }

}
