<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\tests\config;

use rosasurfer\ministruts\config\PropertyConfig;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\tests\helper\TestCase;

/**
 *
 */
class PropertyConfigTest extends TestCase {

    private string $configFile;


    protected function setUp(): void {
        parent::setUp();

        $this->configFile = tempnam(sys_get_temp_dir(), 'ministruts-config-');
        file_put_contents($this->configFile, implode("\n", [
            'int.a = 42',
            'int.b = -7',
            'float.a = 3.14',
            'float.b = -2.5e3',
            'bool.true = yes',
            'bool.false = off',
            'text = abc',
        ]));
    }


    protected function tearDown(): void {
        if (isset($this->configFile) && is_file($this->configFile)) {
            unlink($this->configFile);
        }
        parent::tearDown();
    }


    public function testIntInterpretsStringRepresentations(): void {
        $config = new PropertyConfig([$this->configFile]);

        self::assertSame(42, $config->int('int.a'));
        self::assertSame(-7, $config->int('int.b'));
        self::assertSame(5, $config->int('missing.key', 5));
    }


    public function testFloatInterpretsStringRepresentations(): void {
        $config = new PropertyConfig([$this->configFile]);

        self::assertSame(3.14, $config->float('float.a'));
        self::assertSame(-2500.0, $config->float('float.b'));
        self::assertSame(1.25, $config->float('missing.key', 1.25));
    }


    public function testBoolInterpretsStringRepresentations(): void {
        $config = new PropertyConfig([$this->configFile]);

        self::assertTrue($config->bool('bool.true'));
        self::assertFalse($config->bool('bool.false'));
        self::assertTrue($config->bool('missing.key', true));
    }


    public function testTypedGettersRejectInvalidRepresentations(): void {
        $config = new PropertyConfig([$this->configFile]);

        $this->expectException(RuntimeException::class);
        $config->int('text');
    }
}
