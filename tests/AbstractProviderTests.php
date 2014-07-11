<?php
namespace werx\Config\Tests;

use werx\Config\Providers\ArrayProvider;

abstract class AbstractProviderTests extends \PHPUnit_Framework_TestCase
{
	public $resource_directory = null;

	public function testCanSetPath()
	{
		$provider = $this->getProvider();
		$this->assertEquals($this->resource_directory, $provider->path);
	}

	public function testCanLoadBaseConfig()
	{
		$provider = $this->getProvider();
		$items = $provider->load('default');

		$this->assertArrayHasKey('name', $items);
		$this->assertEquals('default', $items['name']);
	}

	public function testCanLoadEnvironmentConfig()
	{
		$provider = $this->getProvider();
		$items = $provider->load('default', 'test');

		$this->assertArrayHasKey('name', $items);
		$this->assertEquals('test', $items['name']);
	}

	public function testLoadEmptyFileShouldReturnArray()
	{
		$provider = $this->getProvider();
		$items = $provider->load('empty');

		$this->assertInternalType('array', $items);
		$this->assertEquals(0, count($items), 'Items should be an empty array for empty file.');
	}

	public function testLoadMissingFileShouldReturnArray()
	{
		$provider = $this->getProvider();
		$items = $provider->load('doesnotexist');

		$this->assertInternalType('array', $items);
		$this->assertEquals(0, count($items), 'Items should be an empty array for missing file.');
	}

	/**
	 * @expectedException \Exception
	 */
	public function testLoadNoConfigSpecifiedShouldThrowException()
	{
		$provider = $this->getProvider();
		$items = $provider->load(null);
	}

	abstract public function getProvider();
}
