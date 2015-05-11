<?php
namespace werx\Config\Tests;

use werx\Config\Container;
use werx\Config\Providers\ArrayProvider;

class ConfigTests extends \PHPUnit_Framework_TestCase
{
	public $config = null;
	
	public function setUp()
	{
		$provider = new ArrayProvider(__DIR__ . DIRECTORY_SEPARATOR . 'resources/config_array');
		$this->config = new Container($provider);
	}

	public function testEmptyProviderShouldDefaultToArrayProvider()
	{
		$config = new Container;

		$this->assertInstanceOf('werx\Config\Providers\ArrayProvider', $config->provider, 'Should default to array provider if no provider specified.');
	}

	public function testCanLoadDefault()
	{
		$this->config->clear();
		$this->config->load('default');
		$this->assertEquals('default', $this->config->get('name'));
	}

	public function testCanLoadEnvironmentOverride()
	{
		$this->config->clear();
		$this->config->setEnvironment('test');
		$this->config->load('default');

		$this->assertEquals('test', $this->config->get('name'));
	}

	public function testCanLoadMultipleWithIndex()
	{
		$this->config->clear();
		$this->config->load(['default', 'extra'], true);
		$this->assertEquals('default', $this->config->get('name'));
		$this->assertEquals('Foo', $this->config->get('foo', null, 'extra'));
	}

	public function testCanLoadMultipleWithoutIndex()
	{
		$this->config->clear();
		$this->config->load(['default', 'extra'], false);
		$this->assertEquals('default', $this->config->get('name'));
		$this->assertEquals('Foo', $this->config->get('foo'));
	}

	public function testGetMissingKeyReturnsDefaultValue()
	{
		$this->config->clear();
		$this->config->load('default');
		$this->assertEquals('foo', $this->config->get('doesnotexist', 'foo'));
	}

	public function testLoadConfigShouldReturnArray()
	{
		$this->config->clear();
		$config = $this->config->load('default');
		$this->assertInternalType('array', $config);
		$this->assertArrayHasKey('name', $config);
		$this->assertEquals('default', $config['name']);
	}

	public function testCanGetAllConfigItems()
	{
		$this->config->clear();
		$this->config->load('default');
		$items = $this->config->all();
		$this->assertArrayHasKey('default', $items);
	}

	public function testCanGetItemMagicMethod()
	{
		$this->config->clear();
		$this->config->load('extra', true);
		$this->assertEquals('Foo', $this->config->extra('foo'), 'Should return "Foo"');
		$this->assertEquals(null, $this->config->extra('doesnotexist'), 'Should return default null.');
		$this->assertEquals(false, $this->config->extra('doesnotexist', false), 'Should return default: false.');
		$this->assertArrayHasKey('foo', $this->config->extra(), 'Should return all items from the extra index.');
	}

	public function testCanWalkContainer()
	{
		$this->config->load('walk', true);
		$this->assertEquals('dead', $this->config->walk('walkers:are','not dead'));
		$this->assertEquals('default', $this->config->walk('default'));
		$this->assertTrue($this->config->walk('missing', true));
		$this->assertEquals(['name'=>'default'], $this->config->walk('alias'));
		$this->assertEquals("default", $this->config->walk('alias:name'));
		$this->assertEquals("could", $this->config->walk("not:that:you:would:but:you"));
		$this->assertNull($this->config->walk("not:that:you:would:but:you:could"));
		$this->assertEquals("forgot 'you'", $this->config->walk("not:that:would:but:you:could", "forgot 'you'"));
	}
}
