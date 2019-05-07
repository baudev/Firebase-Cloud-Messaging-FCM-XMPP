<?php

namespace FCMStream\tests;

use FCMStream\tests\CoreInstance;
use ReflectionClass;

class CoreTest extends \PHPUnit\Framework\TestCase
{

    private static $obj;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$obj = new CoreInstance(00000, "test", 'debugfile.txt', \FCMStream\helpers\Logs::DEBUG);
    }

    protected static function getMethodOfCore($name) {
        $class = new ReflectionClass('FCMStream\tests\CoreInstance');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function testKeepAliveMessageBehavior()
    {
        $method = self::getMethodOfCore('analyzeData');
        // we mock the write method of Core object to avoid fwrite error
        $stub = $this->createMock(CoreInstance::class);
        $stub->method('write')
            ->willReturn("");
        $method->invokeArgs($stub, array('')); // we invoke the method
        $this->expectOutputRegex('#=== Keepalive exchange === \d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{4} ===\\n#'); // check the output
    }

}