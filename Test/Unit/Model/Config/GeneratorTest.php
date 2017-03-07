<?php

namespace Kstasik\Vim\Test\Model\Config;

use Kstasik\Vim\Model\Config\Generator;
use Psr\Log\LoggerAwareInterface;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    private $generator;

    public function setUp()
    {
        $this->generator = new Generator([]);

        $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
        $this->generator->setLogger($logger);
    }

    public function testRunMethodExists()
    {
        $this->assertTrue(method_exists($this->generator, 'run'), true);
    }

    public function testIsLoggerAware()
    {
        $this->assertInstanceOf(LoggerAwareInterface::class, $this->generator);
    }

    public function testEmptyConfiguration()
    {
        $this->generator->run();
    }

    public function testLackOfDirectory()
    {
        $this->setExpectedException(\Exception::class);

        $this->assertTrue(method_exists($this->generator, 'setDirectory'));
        $this->assertEquals($this->generator, $this->generator->setDirectory(''));

        $this->generator->run();
    }
}
