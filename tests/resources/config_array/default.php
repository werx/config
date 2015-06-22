<?php
return [
	'name' => 'default',
	'recursive_test'    => [
		'foo'   => 'bar',
		'only_in_parent'    => 'baz',
		'bar'   => [
			'baz' => 'original',
			'qux' => 'original'
		]
	]
];
