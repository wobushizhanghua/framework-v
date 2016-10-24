# this is a php html template parse and php code generator

* see example:
````
cd example
php compile.php

````

* that can convert your html template code to php code, orgniazed by classes so that you can write your code binding data and functions in the generated php files

from 
```html
<html>
<body>
<var name="title"><h1>{title}</h1></var>
<var name="content">
	<h1>this is content</h1>
	<var name="list" count="10">
		<div>{id}</div>
		<div>{content}</div>
	</var>
</var>
</body>
</html>
```

to 
```php
<?php
class Var_title extends ComponentVar{
	/*auto generate properties */
	public $name = "title";
	public $file_name = "test.html";
	public $class_name = "var_title";
	/*end */

	function OutPut($args = null) {
		$this->EchoContent();
	}
}
/*do not delete*/?><?php
class Var_list extends ComponentVar{
	/*auto generate properties */
	public $name = "list";
	public $count = "10";
	public $file_name = "test.html";
	public $total_count = "10";
	/*end */

	function __construct() {
		$this->total_count = 10;	//modify to real count
	}
	function OutPut($args = null) {
		for($i = 0; $i <10; $i++) {
			$this->EchoContent();
		}
	}
}
/*do not delete*/?><?php
class Var_content extends ComponentVar{
	/*auto generate properties */
	public $name = "content";
	public $file_name = "test.html";
	public $class_name = "var_content";
	/*end */

	function OutPut($args = null) {
		$this->EchoContent();
	}
}
/*do not delete*/?>
```
