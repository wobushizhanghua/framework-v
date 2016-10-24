<?php

class Compilor_Parser_List
{
    public $wc = array();
    private $user_classes = array();
    private $parser;

    function __construct($common_parser_object)
    {
        $this->parser = $common_parser_object;
        if (!method_exists($this->parser, 'SearchTag')) {
            die('error in Compilor_Parser_Form');
        }
    }

    function main()
    {
        $tags = $this->parser->SearchTag('list');
        $this->tags = $tags;
        foreach ($tags as $t) {
            //处理可能预先生成的php，或者用户自己定义的php
            $t['properties']['innerPHP'] = $this->parser->SearchInnerPHP($t['properties']['innerHTML']);
            //end

            $this->GCode($t['properties']['name'], 'List', $t['properties']);
            $name = $t['properties']['name'];
            $this->parser->ReplaceTag($t['former_str'], '<?php $components["List_' . $name . '"]->OutPutContent();?>');

        }
        return array($this->wc, $this->parser->GetContent());
    }

    function GetTags()
    {
        return $this->tags;
    }

    function GCode($name, $basename, $properties)
    {
        //内部标签
        if (count($properties['innerPHP']) > 0) {
            $init_inner_var = array();
            foreach ($properties['innerPHP'] as $key => $innerPHP) {
                $properties['innerHTML'] = str_replace($innerPHP, '{PHP' . $key . '}', $properties['innerHTML']);
                $init_inner_var['{PHP' . $key . '}'] = '';//str_replace('OutPutContent','GetOutPutContent', substr($innerPHP , 6,-3));
            }
        }

        //记录生成的类
        $this->user_classes[$this->parser->filename][$basename . '_' . $name] = array();

        $this->user_classes[$this->parser->filename][$basename . '_' . $name]['innerHTMLArray'] = $this->parser->GetInnerHTMLArray($properties['innerHTML']);
        $this->user_classes[$this->parser->filename][$basename . '_' . $name]['innerVarArray'] = $this->parser->GetInnerVarArray($properties['innerHTML']);
        $this->user_classes[$this->parser->filename][$basename . '_' . $name]['innerVarInitVal'] = $init_inner_var;

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

        $code_body['functions']['__construct'] = "\n\tfunction __construct(){" .
            "\n\tglobal \$url;";
        foreach ($properties as $p => $value) {
            $value = str_replace('"', '\"', $value);
            if (isset($value[0]) && $value[0] == '$') {
                $code_body['functions']['__construct'] .= "\n\t\t\$this->$p=$value;";
            } else {
                $code_body['vars'][] = "public \${$p} = \"$value\";";
            }
        }
        $code_body['functions']['__construct'] .= "\n\t}";
        $code_body['functions']['OutPut'] = "\n\tfunction OutPut(){" .
            "\n\t\tfor(\$i=0;\$i<\$this->count;\$i++){" .
            "\n\t\t\t echo \"" . addslashes($properties['innerHTML']) . "\";" .
            "\n\t\t}" .
            "\n\t}";


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
