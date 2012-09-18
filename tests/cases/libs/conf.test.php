<?php
/**
 * ConfigureTest file
 *
 * Holds several tests
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 *  Licensed under The Open Group Test Suite License
 *  Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       http://www.opensource.org/licenses/opengroup.php The Open Group Test Suite License
 */
App::import('Core', 'Configure');
App::import('Lib', 'Icing.Conf');

/**
 * ConfigureTest
 *
 * @package       cake
 * @subpackage    cake.tests.cases.libs
 */
class ConfigureTest extends CakeTestCase {

/**
 * setUp method
 *
 * @access public
 * @return void
 */
	function setUp() {
		$this->_cacheDisable = Configure::read('Cache.disable');
		$this->_debug = Configure::read('debug');

		Configure::write('Cache.disable', true);
	}

/**
 * endTest
 *
 * @access public
 * @return void
 */
	function endTest() {
		App::build();
	}

/**
 * tearDown method
 *
 * @access public
 * @return void
 */
	function tearDown() {
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_dir_map')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_dir_map');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_file_map')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_file_map');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_object_map')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_object_map');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'test.config.php')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'test.config.php');
		}
		if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'test.php')) {
			unlink(TMP . 'cache' . DS . 'persistent' . DS . 'test.php');
		}
		if (file_exists(APP . 'config' . DS . 'conftest.php')) {
			unlink(APP . 'config' . DS . 'conftest.php');
		}
		Configure::write('debug', $this->_debug);
		Configure::write('Cache.disable', $this->_cacheDisable);
	}

	function makeConftestConfigFile() {
		if (file_exists(APP . 'config' . DS . 'conftest.php')) {
			#return false;
		}
		$value = '<?php
		$config = array(
			"Conf" => array("model" => "Icing.DatabaseConfiguration", "fieldKey" => "key", "fieldValue" => "value", "fieldActive" => "is_active"),
			"Conftest" => array(
				"a" => "alpha",
				"b" => "beta",
				"nest" => array(
					"junk",
					"more" => "junk",
					"nested" => array(
						"stuff",
						"more" => "stuff",
					)
				)
			),
		);
		';
		$saved = file_put_contents(APP . 'config' . DS . 'conftest.php', $value);
		$this->assertTrue($saved);
	}

/**
 * testRead method
 *
 * @access public
 * @return void
 */
	function testRead() {
		// very basic, ensure we can read values explicitly set
		$expected = 'ok';
		Configure::write('level1.level2.level3_1', $expected);
		Configure::write('level1.level2.level3_2', 'something_else');
		$result = Conf::read('level1.level2.level3_1');
		$this->assertEqual($expected, $result);

		$result = Conf::read('level1.level2.level3_2');
		$this->assertEqual($result, 'something_else');

		$result = Conf::read('debug');
		$this->assertTrue($result >= 0);

		// attempt a value that doesn't exist, because the configuration for it doesn't exist
		$result = Conf::read('Conftest.a');
		$this->assertFalse($result);
		// now, attempt to setup a configuration file
		$this->makeConftestConfigFile();
		// and now try again, should find it, because it will autoload the
		// newly created config file
		$result = Conf::read('Conftest.a');
		$this->assertEqual($result, 'alpha');
	}

/**
 * testWrite method
 *
 * @access public
 * @return void
 */
	function testWrite() {
		$writeResult = Configure::write('SomeName.someKey', 'myvalue');
		$this->assertTrue($writeResult);
		$result = Configure::read('SomeName.someKey');
		$this->assertEqual($result, 'myvalue');

		$writeResult = Configure::write('SomeName.someKey', null);
		$this->assertTrue($writeResult);
		$result = Configure::read('SomeName.someKey');
		$this->assertEqual($result, null);

		$expected = array('One' => array('Two' => array('Three' => array('Four' => array('Five' => 'cool')))));
		$writeResult = Configure::write('Key', $expected);
		$this->assertTrue($writeResult);

		$result = Configure::read('Key');
		$this->assertEqual($expected, $result);

		$result = Configure::read('Key.One');
		$this->assertEqual($expected['One'], $result);

		$result = Configure::read('Key.One.Two');
		$this->assertEqual($expected['One']['Two'], $result);

		$result = Configure::read('Key.One.Two.Three.Four.Five');
		$this->assertEqual('cool', $result);
	}

/**
 * testSetErrorReporting Level
 *
 * @return void
 */
	function testSetErrorReportingLevel() {
		Configure::write('log', false);

		Configure::write('debug', 0);
		$result = ini_get('error_reporting');
		$this->assertEqual($result, 0);

		Configure::write('debug', 2);
		$result = ini_get('error_reporting');
		$this->assertEqual($result, E_ALL & ~E_DEPRECATED);

		$result = ini_get('display_errors');
		$this->assertEqual($result, 1);

		Configure::write('debug', 0);
		$result = ini_get('error_reporting');
		$this->assertEqual($result, 0);
	}

/**
 * test that log and debug configure values interact well.
 *
 * @return void
 */
	function testInteractionOfDebugAndLog() {
		Configure::write('log', false);

		Configure::write('debug', 0);
		$this->assertEqual(ini_get('error_reporting'), 0);
		$this->assertEqual(ini_get('display_errors'), 0);

		Configure::write('log', E_WARNING);
		Configure::write('debug', 0);
		$this->assertEqual(ini_get('error_reporting'), E_WARNING);
		$this->assertEqual(ini_get('display_errors'), 0);

		Configure::write('debug', 2);
		$this->assertEqual(ini_get('error_reporting'), E_ALL & ~E_DEPRECATED);
		$this->assertEqual(ini_get('display_errors'), 1);

		Configure::write('debug', 0);
		Configure::write('log', false);
		$this->assertEqual(ini_get('error_reporting'), 0);
		$this->assertEqual(ini_get('display_errors'), 0);
	}

/**
 * testDelete method
 *
 * @access public
 * @return void
 */
	function testDelete() {
		Configure::write('SomeName.someKey', 'myvalue');
		$result = Configure::read('SomeName.someKey');
		$this->assertEqual($result, 'myvalue');

		Configure::delete('SomeName.someKey');
		$result = Configure::read('SomeName.someKey');
		$this->assertTrue($result === null);

		Configure::write('SomeName', array('someKey' => 'myvalue', 'otherKey' => 'otherValue'));

		$result = Configure::read('SomeName.someKey');
		$this->assertEqual($result, 'myvalue');

		$result = Configure::read('SomeName.otherKey');
		$this->assertEqual($result, 'otherValue');

		Configure::delete('SomeName');

		$result = Configure::read('SomeName.someKey');
		$this->assertTrue($result === null);

		$result = Configure::read('SomeName.otherKey');
		$this->assertTrue($result === null);
	}

/**
 * testLoad method
 *
 * @access public
 * @return void
 */
	function testLoad() {
		$result = Configure::load('non_existing_configuration_file');
		$this->assertFalse($result);

		$result = Configure::load('config');
		$this->assertTrue($result);

		$result = Configure::load('../../index');
		$this->assertFalse($result);
	}

/**
 * testLoad method
 *
 * @access public
 * @return void
 */
	function testLoadPlugin() {
		App::build(array('plugins' => array(TEST_CAKE_CORE_INCLUDE_PATH . 'tests' . DS . 'test_app' . DS . 'plugins' . DS)), true);
		$result = Configure::load('test_plugin.load');
		$this->assertTrue($result);
		$expected = '/test_app/plugins/test_plugin/config/load.php';
		$config = Configure::read('plugin_load');
		$this->assertEqual($config, $expected);

		$result = Configure::load('test_plugin.more.load');
		$this->assertTrue($result);
		$expected = '/test_app/plugins/test_plugin/config/more.load.php';
		$config = Configure::read('plugin_more_load');
		$this->assertEqual($config, $expected);
	}

/**
 * testStore method
 *
 * @access public
 * @return void
 */
	function testStoreAndLoad() {
		Configure::write('Cache.disable', false);

		$expected = array('data' => 'value with backslash \, \'singlequote\' and "doublequotes"');
		Configure::store('SomeExample', 'test', $expected);

		Configure::load('test');
		$config = Configure::read('SomeExample');
		$this->assertEqual($config, $expected);

		$expected = array(
			'data' => array('first' => 'value with backslash \, \'singlequote\' and "doublequotes"', 'second' => 'value2'),
			'data2' => 'value'
		);
		Configure::store('AnotherExample', 'test_config', $expected);

		Configure::load('test_config');
		$config = Configure::read('AnotherExample');
		$this->assertEqual($config, $expected);
	}

/**
 * testVersion method
 *
 * @access public
 * @return void
 */
	function testVersion() {
		$result = Configure::version();
		$this->assertTrue(version_compare($result, '1.2', '>='));
	}

}
