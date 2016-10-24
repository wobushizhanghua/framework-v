<?php
require_once(dirname(__FILE__) . '/var/var.php');
require_once(dirname(__FILE__) . '/form/form.php');
require_once(dirname(__FILE__) . '/element/element.php');
require_once(dirname(__FILE__) . '/list/list.php');
require_once(dirname(__FILE__) . '/pager/pager.php');


class Compilor_Parser
{
    private $content = "this is test
		<var name='a'>
        		a1
			<var name='a2'>a21</var>
			a3
			<var name='a4'>
				a41
				<var name='a42'>
					a421
				</var>
				a43
			</var>
			
	</var>";
    private $parser_var;
    private $user_classes = array();
    private $wc = array();
    public $filename;
    public $tcphp;


    function SearchTag($name)
    {
        $regexp = "/<{$name} ([^>]*)>(.*)<\\/{$name}>/siU";

        preg_match_all($regexp, $this->content, $out);
        $return = array();
        foreach ($out[0] as $o) {
            $return[]['former_str'] = $o;
        }
        for ($i = 0; $i < count($out[1]); $i++) {
            $properties = preg_split('/\s/', $out[1][$i]);
            foreach ($properties as $p) {
                preg_match('/"?[\s]*([^=]*)[\s]*"?[\s]*="?[\s]*([^"\s]*)[\s]*"?[\s]*/', $p, $property);

                if ($property[1] == '') {
                    continue;
                }
                if ($property[2][0] == '$') {
                    $property[2] = str_replace(array('.', 'equ'), array('->', '=='), $property[2]);
                }

                $return[$i]['properties'][$property[1]] = $property[2];
                $return[$i]['properties']['innerHTML'] = $out[2][$i];
                $return[$i]['properties']['basename'] = $name;
            }
        }
        return $return;
    }

    function SearchTag1($name, $offset = 0)
    {

        $reg = "/<{$name} ([^>]*)>|<\\/{$name}>/siU";

        preg_match($reg, $this->content, $out, PREG_OFFSET_CAPTURE, $offset);

        if (!is_array($out[0])) {
            //to end
            return array("name" => "EOF");
        }

        if ($out[0][0] == "</{$name}>") {
            //get a end token
            return array("name" => "TK_END", "offset" => $out[0][1], "end" => $out[0][1] + strlen($out[0][0]));
        } else {
            $ret = array();
            //get a tag
            $ret['name'] = $name;
            $ret['type'] = "tag";
            $ret['offset'] = $out[0][1];
            $ret['end'] = $out[0][1] + strlen($out[0][0]);

            $raw_end = $ret['end'];

            preg_match_all('/([^=\s"\']*)[\s]*=[\s]*["\']([^"\s]+)["\']/siU', $out[1][0], $property);

            $ret['properties'] = array();
            $ret['inner'] = array();

            foreach ($property[1] as $key => $prop_name) {
                $ret['properties'][$prop_name] = $property[2][$key];
            }
            $ret['properties']['file_name'] = $this->filename;
            $ret['properties']['class_name'] = $name . "_" . $ret['properties']['name'];

            for ($i = 0; $i < 1000; $i++) {
                $r = $this->SearchTag1($name, $ret['end']);

                if ($r['name'] == 'TK_END') {
                    $ret['inner'][] = array(
                        "type" => "html",
                        "content" => substr($this->content, $ret['end'], $r['offset'] - $ret['end'])
                    );
                    $ret['properties']['rawHTML'] = substr($this->content, $raw_end, $r['offset'] - $raw_end);
                    $ret['end'] = $r['end'];
                    return $ret;
                }

                if ($r['name'] == $name) {
                    //获得一个完整子标签
                    $ret['inner'][] = array(
                        "type" => "html",
                        "content" => substr($this->content, $ret['end'], $r['offset'] - $ret['end'])
                    );
                    $ret['inner'][] = $r;

                    $ret['end'] = $r['end'];
                    continue;
                }

                if ($r['name'] == 'EOF') {
                    //标签不闭合
                    var_dump($ret);
                    die("标签不闭合");
                }
            }
        }

        die("parse 执行流程有误或超过1000个子标签");
    }

    function SearchInnerPHP($str)
    {
        $regexp = "/<\\?php.*\\?>/siU";
        preg_match_all($regexp, $str, $out);
        $return = array();
        foreach ($out[0] as $o) {
            $return[] = $o;
        }
        return $return;
    }

    function GetInnerHTMLArray($str)
    {
        //用｛变量名｝把innerHTML分割成数组。
        $arr = preg_split('/(\{[A-z0-9]+\})/', $str, -1, PREG_SPLIT_DELIM_CAPTURE);
        $innerHTMLArr = array();
        $innerVarArr = array();
        $i = 0;
        for ($i = 0; $i < count($arr); $i += 2) {
            if ($i + 1 == count($arr)) {
                $next_var = '';
            } else {
                $next_var = $arr[$i + 1];
            }

            $innerHTMLArr[] = array("str" => addcslashes($arr[$i], '\"'), "next_var" => $next_var);
        }
        return $innerHTMLArr;

    }

    function CreateInnerHTMLArrayStr($arr)
    {
        $str = 'array(
		';
        $i = 0;
        for ($i = 0; $i < count($arr) - 1; $i++) {
            $str .= ' array(
				"str"=>"' . $arr[$i]['str'] . '",
				"next_var"=>"' . $arr[$i]['next_var'] . '"),
			';
        }
        $str .= ' array(
				"str"=>"' . $arr[$i]['str'] . '",
				"next_var"=>"' . $arr[$i]['next_var'] . '")
			';
        $str .= ")";
        return $str;
    }

    function GetInnerVarArray($str)
    {
        preg_match_all('/\{[A-z0-9]+\}/', $str, $out);
        return $out[0];
    }

    function CreateInnerVarArrayStr($arr, $init_val = 0)
    {
        $str = 'array(
		';
        $i = 0;
        for ($i = 0; $i < count($arr) - 1; $i++) {
            if ($init_val && $init_val[$arr[$i]]) {
                $value = $init_val[$arr[$i]];
            } else {
                $value = '""';
            }
            $str .= "\"{$arr[$i]}\"=>$value,
		";
        }
        if ($init_val && $init_val[$arr[$i]]) {
            $value = $init_val[$arr[$i]];
        } else {
            $value = '""';
        }

        $str .= "\"{$arr[$i]}\"=>$value
		)";
        return $str;
    }

    function SearchTagInStr($str, $name)
    {
        $regexp = "/<{$name} ([^>]*)>(.*)<\\/{$name}>/siU";

        preg_match_all($regexp, $str, $out);
        $return = array();
        foreach ($out[0] as $o) {
            $return[]['former_str'] = $o;
        }
        for ($i = 0; $i < count($out[1]); $i++) {
            $properties = preg_split('/\s/', $out[1][$i]);
            foreach ($properties as $p) {
                preg_match('/"?[\s]*([^=]*)[\s]*"?[\s]*="?[\s]*([^"\s]*)[\s]*"?[\s]*/', $p, $property);

                if ($property[1] == '') {
                    continue;
                }
                if ($property[2][0] == '$') {
                    $property[2] = str_replace(array('.', 'equ'), array('->', '=='), $property[2]);
                }

                $return[$i]['properties'][$property[1]] = $property[2];
                $return[$i]['properties']['innerHTML'] = $out[2][$i];
                $return[$i]['properties']['basename'] = $name;
            }
        }
        return $return;
    }

    function ReplaceTag($former_str, $new_str)
    {
        $this->content = str_replace($former_str, $new_str, $this->content);
    }

    function ReplaceTagByPos($new_str, $offset, $end)
    {
        $this->content = substr_replace($this->content, $new_str, $offset, $end - $offset);
    }

    function SetContent($filename, $content)
    {
        $this->filename = $filename;
        $this->wc = array();
        $this->tc = array();
        $this->tcphp = "";
        $this->user_classes = array();
        $this->content = $content;
    }

    function GetContent()
    {
        return $this->content;
    }

    function Process($filename, $tagname)
    {
        $this->filename = $filename;
        $this->user_files[] = $filename;
        $this->tagname = $tagname;

        $classname = "Compilor_Parser_$tagname";

        $tag_parser = new $classname($this);
        list($wc, $tc, $tcphp) = $tag_parser->main1($filename);
        //var_dump($wc);
        //var_dump($tc);
        //var_dump($tcphp);

        $this->wc = array_merge($this->wc, $wc);
        $this->tc = $tc;
        $this->tcphp .= $tcphp;

        $user_classes = $tag_parser->GetUserClasses();

        if (!isset($this->user_classes[$filename])) {
            $this->user_classes[$filename] = array();
        }
        if (!isset($user_classes[$filename])) {
            $user_classes[$filename] = array();
        }

        $this->user_classes[$filename] = array_merge($this->user_classes[$filename], $user_classes[$filename]);
        return $tag_parser->GetTags();

    }

    function ProcessInnerTag($tag)
    {
        $tags = $this->SearchTagInStr($tag['properties']['innerHTML'], "List");
        return $tags;
    }

    function GetWorkspaceContent()
    {
        return $this->wc;
    }

    function GetTemplateCContent()
    {
        return $this->tc;
    }

    function GetUserClasses()
    {
        return $this->user_classes;
    }

    function GetUserFiles()
    {
        return $this->user_files;
    }
}
