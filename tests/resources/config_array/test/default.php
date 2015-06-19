<?php
return [
	'name' => 'test',
	'recursive_test'    => [
		'foo'   => 'new_value',
		'only_in_env'    => 'qux',
		'bar'   => [
			'baz' => 'new_value',
			'bee' => 'sting'
		]
	]
];
