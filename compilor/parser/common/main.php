<?php

class Compilor_Parser_Common
{
    private $content = "this is test <var name='a' p='bb'>
	{hi}</var>
	<var name=bv >
		asdf</var>";


    function SearchTag($name)
    {
        $regexp = "/<{$name} ([^>]*)>([^<>]*)<\\/{$name}>/iU";

        preg_match_all($regexp, $this->content, $out);
        $return = array();
        foreach ($out[0] as $o) {
            $return[]['fromer_str'] = $o;
        }
        for ($i = 0; $i < count($out[1]); $i++) {
            $properties = preg_split('/\s/', $out[1][$i]);
            foreach ($properties as $p) {
                $property = preg_split('/[\s]*=[\s]*/', $p);
                $return[$i]['property'][$property[0]] = $property[1];
            }
        }
    }

    function ReplaceTag($former_str, $new_str)
    {
        str_replace($former_str, $new_str, $this->content);
    }

    function SetContent($content)
    {
        $this->content = $content;
    }

}

$t = new Compilor_Parser_Common();
$t->SearchTag('var');

?>
