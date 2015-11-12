<?php 

return [
	"foo" => function(\werx\Config\Container $container) {
		$container->load('default');
		return $container->get('name');
	},
	"bar" => $this->singleton( function($container) {
		return (object)['test' => true];
	}),
	"bar2" => function($container) {
		return (object)['test' => true];
	}
];