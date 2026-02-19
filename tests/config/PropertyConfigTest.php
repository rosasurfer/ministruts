<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\tests\config;

use stdClass;

use rosasurfer\ministruts\config\PropertyConfig;
use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\proxy\Config;
use rosasurfer\ministruts\tests\helper\TestCase;

use const rosasurfer\ministruts\NL;

/**
 * PropertyConfigTest
 */
class PropertyConfigTest extends TestCase
{
    protected string $configFile;

    protected function setUp(): void {
        parent::setUp();

        $this->configFile = tempnam(Config::string('app.dir.tmp', sys_get_temp_dir()), 'ministruts-config-');
        file_put_contents($this->configFile, join(NL, [
            'int.a = 42',
            'int.b = -7',
            'float.a = 3.14',
            'float.b = -2.5e3',
            'bool.true = yes',
            'bool.false = off',
            'bool.falsy = -1',
            'text = abc',
        ]));
    }


    protected function tearDown(): void {
        if (is_file($this->configFile)) {
            unlink($this->configFile);
        }
        parent::tearDown();
    }


    public function testGetBool(): void {
        $config = new PropertyConfig([$this->configFile]);

        $this->assertTrue($config->bool('bool.true'));
        $this->assertFalse($config->bool('bool.false'));
        $this->assertFalse($config->bool('bool.falsy'));
        $this->assertTrue($config->bool('missing.key', false, true));

        $this->expectException();
        $config->bool('text');
        $config->bool('bool.falsy', true);
        $this->expectException();
        $config->bool('text');
        $this->expectException();
        $config->bool('missing.key');
    }


    public function testGetInteger(): void {
        $config = new PropertyConfig([$this->configFile]);

        $this->assertSame(42, $config->int('int.a'));
        $this->assertSame(-7, $config->int('int.b'));
        $this->assertSame(5, $config->int('missing.key', 5));

        $this->expectException();
        $config->int('float.a');
        $this->expectException();
        $config->int('text');
        $this->expectException();
        $config->int('missing.key');
    }


    public function testGetFloat(): void {
        $config = new PropertyConfig([$this->configFile]);

        $this->assertSame(3.14, $config->float('float.a'));
        $this->assertSame(-2_500.0, $config->float('float.b'));
        $this->assertSame(42.0, $config->float('int.a'));
        $this->assertSame(1.25, $config->float('missing.key', 1.25));

        $this->expectException();
        $config->float('text');
        $this->expectException();
        $config->float('missing.key');
    }


    public function testGetString(): void {
        $config = new PropertyConfig([$this->configFile]);

        $this->assertSame('abc', $config->string('text'));

        $this->expectException();
        $config->string('missing.key');
    }


    public function testGetArray(): void {
        $config = new PropertyConfig([$this->configFile]);

        $array = [
            'a' => '42',
            'b' => '-7',
        ];
        $this->assertSame($array, $config->array('int'));
        $this->assertSame($array, $config->array('missing.key', $array));

        $this->expectException();
        $config->array('text');
        $this->expectException();
        $config->array('missing.key');
    }


    public function testGetObject(): void {
        $config = new PropertyConfig([$this->configFile]);

        $obj = new stdClass();
        $config->set('object', $obj);

        $this->assertSame($obj, $config->object('object'));
        $this->assertSame($obj, $config->object('missing.key', $obj));

        $this->expectException();
        $config->object('text');
        $this->expectException();
        $config->object('missing.key');
    }


    public function testGetInstance(): void {
        $config = new PropertyConfig([$this->configFile]);

        $obj1 = new stdClass();
        $config->set('object.1', $obj1);

        $obj2 = new CObject();
        $config->set('object.2', $obj2);

        $this->assertSame($obj1, $config->instance('object.1', stdClass::class));
        $this->assertSame($obj1, $config->instance('missing.key', stdClass::class, $obj1));

        $this->expectException();
        $config->$config->instance('object.2', stdClass::class);
        $this->expectException();
        $config->$config->instance('object.2', stdClass::class, $obj2);
        $this->expectException();
        $config->instance('text', stdClass::class);
        $this->expectException();
        $config->instance('missing.key', stdClass::class);
    }
}
