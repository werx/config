<?php
namespace werx\Config\Tests;

use werx\Config\Providers\JsonProvider;
use werx\Config\Tests\ArrayProviderTests;

class JsonProviderTests extends AbstractProviderTests
{
	public $resource_directory = null;

	public function __construct()
	{
		$this->resource_directory = __DIR__ . DIRECTORY_SEPARATOR . 'resources/config_json';
	}

	public function testInvalidJsonShouldReturnEmptyArray()
	{
		$provider = $this->getProvider();
		$items = $provider->load('invalid');
		$this->assertEquals(0, count($items));
	}

	/**
	 * @expectedException \Exception
	 */
	public function testInvalidPathShouldThrowException()
	{
		$provider = new JsonProvider;
		$provider->setPath('/path/to/nowhere');
	}

	public function getProvider()
	{
		$provider = new JsonProvider($this->resource_directory);
		return $provider;
	}
}
