<?php

class Compilor_Parser_Var
{
    public $wc = array();
    private $user_classes = array();
    private $parser;
    private $tcphp;
    private $tc;
    private $tcarr = array();
    private $filename;

    function __construct($common_parser_object)
    {
        $this->parser = $common_parser_object;
        if (!method_exists($this->parser, 'SearchTag')) {
            die('error in Compilor_Parser_Var');
        }
    }

    function main()
    {
        $tags = $this->parser->SearchTag('var');
        $this->tags = $tags;
        foreach ($tags as $t) {
            $innerTags = $this->parser->ProcessInnerTag($t);
            foreach ($innerTags as $it) {
                $t['properties']['innerHTML'] = str_replace($it['former_str'],
                    "{innertag_" . $it['properties']['name'] . "}",
                    $t['properties']['innerHTML']);
                $this->GCode($it['properties']['name'], $it['properties']['basename'], $it['properties'], null, false);
            }
            $this->GCode($t['properties']['name'], 'Var', $t['properties']);
            $name = $t['properties']['name'];
            $this->parser->ReplaceTag($t['former_str'], '<?php $components["Var_' . $name . '"]->OutPutContent();?>');

        }
        return array($this->wc, $this->parser->GetContent());
    }

    function main1($filename)
    {
        if (!AUTO_COMPILE) {
            echo "process $filename \n";
        }
        $this->filename = $filename;

        $this->tc = $this->parser->GetContent();

        $this->tcphp = "<?php\ninclude_once(APP_VIEW_ROOT.'/workspace/{$filename}.php');\n"
            . 'global $components;' . "\n";

        $offset = 0;
        while (true) {
            $r = $this->parser->SearchTag1("var", $offset);

            if ($r['name'] == 'EOF') {
                $this->tcarr[] = substr($this->tc, $offset);
                break;
            }

            $this->GCodeR($r);

            $this->tcarr[] = substr($this->tc, $offset, $r['offset'] - $offset);
            $this->tcarr[] = '<?php $components["Var_' . $r['properties']['name'] . '"]->OutPutContent();?>';

            $offset = $r['end'];
        }

        $this->tcphp .= "include_once(APP_VIEW_ROOT.'/v/template_c/$filename.php');\n";

        return array($this->wc, join("", $this->tcarr), $this->tcphp);
    }

    function GCodeR($r)
    {
        if (defined("AUTO_COMPILE") && AUTO_COMPILE) {
            if ($r['properties']['type'] == 'element') {
                auto_compile($r['properties']['src']);
            }
        }

        if (is_array($r['inner'])) {
            foreach ($r['inner'] as $in) {
                if ($in['type'] == 'tag') {
                    $this->GCodeR($in);
                }
            }
        }

        if ($r['name'] == "var") {
            $this->GCode($r['properties']['name'], "Var", $r['properties']);
            $this->GTcphp($r);
        }
    }

    function GTcphp($r)
    {
        $filename = $this->filename;
        $class = "Var_" . $r['properties']['name'];

        $this->tcphp .= "if(!isset(\$components['$class']))\n{\n\t\$components['$class'] = new $class;
	\$components['$class']->innerHTMLArray = array();\n}\n";

        foreach ($r['inner'] as $obj) {
            if ($obj['type'] == 'html') {
                $this->tcphp .= "\$components['$class']->innerHTMLArray[]=" .
                    $this->parser->CreateInnerHTMLArrayStr($this->parser->GetInnerHTMLArray($obj['content'])) . ";\n";
            }

            if ($obj['type'] == 'tag') {
                $c = "Var_" . $obj['properties']['name'];
                $this->tcphp .= "\$components['$class']->innerHTMLArray[]=\$components['$c'];\n";
            }
        }

        $code = "<?php\n/*auto generate do not modify */\n" .
            $code1 .
            //	$code2.
            "/*auto generate do not modify */\n?>";

        return $code;

        //$this->file->Write($file,$code);
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

        // $this->user_classes[$this->parser->filename][$basename.'_'.$name]['innerHTMLArray'] = $this->parser->GetInnerHTMLArray($properties['innerHTML']);
        // $this->user_classes[$this->parser->filename][$basename.'_'.$name]['innerVarArray'] = $this->parser->GetInnerVarArray($properties['innerHTML']);
        //$code_body['functions']['auto_test']="function auto_test(){\n\t}";
        if ($properties['type'] == 'element') {
            $code_body['functions']['OutPut'] = "function OutPut(\$args = null) {
		auto_compile(\$this->src);
		require_once(APP_VIEW_ROOT . \"/v/template_c/php/{\$this->src}.php\");
	}";

        } else if ($properties['type'] == 'ciphpjs') {

            $code_body['functions']['OutPut'] = "function OutPut(\$args = null) {
        \$this->OutPutCiphpJs();
    }";
        } else {
            if ($properties['count'] > 0) {
                $code_body['functions']['__construct'] = "function __construct() {
		\$this->total_count = 10;	//modify to real count
	}";
                $properties['total_count'] = 10;

                $code_body['functions']['OutPut'] = "function OutPut(\$args = null) {
		for(\$i = 0; \$i <{$properties['count']}; \$i++) {
			\$this->EchoContent();
		}
	}";
            } else {
                if ($properties['type'] == 'pager') {
                    $code_body['functions']['__construct'] = "function __construct() {
		\$this->GetComp('Var_{$properties['target']}')->start = (int)\$_GET['p'];
		\$this->total_count = \$this->GetComp('Var_{$properties['target']}')->total_count;
	}";
                    $properties['total_count'] = 10;

                    $code_body['functions']['OutPut'] = "function OutPut(\$args = null) {
		global \$components;
		\$p = new ComponentPager();
		\$url = preg_replace(\"/&p=[0-9]*/\",'',\$_SERVER['REQUEST_URI']);
		\$url = preg_replace(\"/\?p=[0-9]*/\",'',\$url);
		\$p->init(\$url, \$components['Var_' . \$this->target]->total_count,\$components['Var_' . \$this->target]->count,(int)\$_GET['p']);

		echo \$p->getBar();
	}";
                } else {
                    $code_body['functions']['OutPut'] = "function OutPut(\$args = null) {
		\$this->EchoContent();
	}";
                }
            }
        }

        foreach ($properties as $p => $value) {
            $value = str_replace('"', '\"', $value);
            $code_body['vars'][] = "public \${$p} = \"$value\";";
        }

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

    function GetTags()
    {
        return $this->tags;
    }

}
