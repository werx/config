<?php
namespace werx\Config\Tests;

use werx\Config\Container;
use werx\Config\Providers\ArrayProvider;

class ConfigTests extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \werx\Config\Container
	 */
	public $config = null;

	public function setUp()
	{
		$provider = new ArrayProvider(__DIR__ . DIRECTORY_SEPARATOR . 'resources/config_array');
		$this->config = new Container($provider);
	}

	public function testEmptyProviderShouldDefaultToArrayProvider()
	{
		$config = new Container;

		$this->assertInstanceOf(
			'werx\Config\Providers\ArrayProvider',
			$config->provider,
			'Should default to array provider if no provider specified.'
		);
	}

	public function testCanLoadDefault()
	{
		$this->config->load('default');

		$this->assertEquals('default', $this->config->get('name'));
	}

	public function testLoadEmptyGroupShouldLoadDefault()
	{
		$this->config->load(null);
		$this->assertEquals('default', $this->config->get('name'));
	}

	public function testCanLoadEnvironmentOverride()
	{
		$this->config->clear();
		$this->config->setEnvironment('test');
		$this->config->load('default');

		$this->assertEquals('test', $this->config->get('name'));
	}

	public function testCanLoadEnvironmentOverrideRecursive()
	{
		$this->config->clear();
		$this->config->setEnvironment('test');
		$this->config->load('default');

		$recursive_values = $this->config->get('recursive_test');

		// test top-level of recursive replacements
		$this->assertEquals('new_value', $recursive_values['foo']);
		$this->assertEquals('test', $this->config->get('name'));
		$this->assertEquals('qux', $recursive_values['only_in_env']);
		$this->assertEquals('baz', $recursive_values['only_in_parent']);

		// test the inner recursive replacements
		$this->assertEquals('new_value', $recursive_values['bar']['baz']);
		$this->assertEquals('original', $recursive_values['bar']['qux']);
		$this->assertEquals('sting', $recursive_values['bar']['bee']);
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

	/**
	 * @expectedException \BadMethodCallException
	 */
	public function testMagicMethodShouldThrowException()
	{
		// More than 2 arguements should throw an exception.
		$this->config->foo('key', null, null);

	}

	public function testCanWalkContainer()
	{
		$this->config->load('default', true);
		$this->config->load('walk', true);
		$this->assertEquals('dead', $this->config->walk('walkers:are', 'not dead'));
		$this->assertEquals('default', $this->config->walk('default'));
		$this->assertTrue($this->config->walk('missing', true));
		$this->assertEquals($this->config->default(), $this->config->walk('alias'));
		$this->assertEquals("default", $this->config->walk('alias:name'));
		$this->assertEquals("could", $this->config->walk("not:that:you:would:but:you"));
		$this->assertNull($this->config->walk("not:that:you:would:but:you:could"));
		$this->assertEquals("forgot 'you'", $this->config->walk("not:that:would:but:you:could", "forgot 'you'"));
	}

	public function testCanUseCallable()
	{
		$this->config->load('callable');
		$this->assertEquals('default', $this->config->get('foo'));
	}

	public function testCanUseCallableDefault()
	{
		$this->config->load('callable');
		$this->assertEquals(
			'default',
			$this->config->get(
				'doesnotexist',
				function () {
					return "default";
				}
			)
		);
	}

	public function testCanUseCallableSingleton()
	{
		$this->config->load('callable');
		$bar = $this->config->get('bar');
		$this->assertTrue($bar === $this->config->get('bar'));
	}

	public function testCanUseCallableNotSingleton()
	{
		$this->config->load('callable');
		$bar = $this->config->get('bar2');
		$this->assertTrue($bar !== $this->config->get('bar2'));
	}

	public function testShouldNotHaveKey()
	{
		$this->config->clear();
		$this->config->set('foo', 'Foo');

		$this->assertFalse($this->config->has('bar'));
		$this->assertFalse($this->config->has('default.bar'));
		$this->assertFalse($this->config->has('bar', 'default'));
	}

	public function testShouldHaveHaveKey()
	{
		$this->config->clear();
		$this->config->set('bar', 'Bar');

		$this->assertTrue($this->config->has('bar'));
		$this->assertTrue($this->config->has('default.bar'));
		$this->assertTrue($this->config->has('bar', 'default'));
	}

	public function testCanGetArrayAccess()
	{
		$this->config->clear();
		$this->config->set('foo', 'Foo', 'test');

		$this->assertEquals('Foo', $this->config['test.foo']);
	}

	public function testCanSetArrayAccessCompoundKey()
	{
		$this->config->clear();
		$this->config['test.foo'] = 'Foo';

		$this->assertEquals('Foo', $this->config['test.foo'], 'Get via array access');
		$this->assertEquals('Foo', $this->config->get('test.foo'), 'Get via method With Compound Key');
		$this->assertEquals('Foo', $this->config->get('foo', null, 'test'), 'Get via method with index param');
		$this->assertEquals('Foo', $this->config->test('foo'), 'Get via magic method');
	}

	public function testCanForgetItem()
	{
		$this->config->clear();
		$this->config['test.foo'] = 'Foo';
		$this->assertTrue($this->config->has('test.foo'));

		$this->config->forget('test.foo');
		$this->assertFalse($this->config->has('test.foo'));

	}

	public function testCanRemoveItemArrayAccess()
	{
		$this->config->clear();
		$this->config['test.foo'] = 'Foo';
		$this->assertTrue($this->config->has('test.foo'));

		unset($this->config['test.foo']);

		$this->assertFalse($this->config->has('test.foo'));
	}

	public function testArrayKeyShouldExist()
	{
		$this->config->clear();
		$this->config['test.foo'] = 'Foo';
		$this->assertTrue(isset($this->config['test.foo']));
		$this->assertFalse(empty($this->config['test.foo']));
	}

	public function testCanSetWithAddAlias()
	{
		$this->config->clear();
		$this->config->add('foo', 'Foo', 'test');

		$this->assertEquals('Foo', $this->config->get('test.foo'));
	}

	public function testCanEvaluateUnsetDefault()
	{
		$this->config->clear();
		$result = $this->config->get('foo', 'Foo', 'doesnotexist');

		$this->assertEquals('Foo', $result);
	}

	public function testAllWithNonexistantIndexShouldReturnEmptyArray()
	{
		$this->config->clear();
		$this->assertEquals([], $this->config->all('indexdoesnotexist'));
	}

	public function testArrayMergeDeepReturnsExpectedResultNumericIndex()
	{
		$array_1 = ['foo' => 'Foo'];
		$array_2 = [0 => 'Numeric Index'];

		$merged = Container::array_merge_deep($array_1, $array_2);

		$this->assertTrue(in_array('Numeric Index', $merged));
		$this->assertArrayHasKey('foo', $merged);
		$this->assertEquals('Foo', $merged['foo']);
	}
}
