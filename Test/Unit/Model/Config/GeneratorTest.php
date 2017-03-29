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

        xdebug_break();
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
        $this->setExpectedException(\Exception::class);

        $this->generator->run(array());
    }

    public function testLackOfDirectory()
    {
        $this->setExpectedException(\Exception::class);

        $this->generator->run(array(
            Generator::DIRECTORY_CONFIG => ''
        ));
    }

    public function getProcessorSets()
    {
        $sets = [];

        $processor = $this->getMockBuilder(\Kstasik\Vim\Model\Config\Processor\Variables::class)
            ->disableOriginalConstructor()
            ->getMock();

        $processor
            ->expects($this->once())
            ->method('run')
            ->will($this->returnValue(true));

        $sets[0][] = $processor;

        return $sets;

        // https://github.com/sebastianbergmann/phpunit-mock-objects/issues/280
        for ($i = 0; $i < 3; $i++) {
            $processor = $this->getMockBuilder([
                    \Kstasik\Vim\Model\Config\ProcessorInterface::class,
                    \Psr\Log\LoggerAwareInterface::class
                ])
                ->setMethods(['run'])
                ->getMock();

            $processor
                ->expects($this->once())
                ->method('run')
                ->with($this->equalTo('foo'))
                ->will($this->returnValue(true));

            $sets[0][] = $processor;
        }

        return $sets;
    }

    public function getWrongProcessorSets()
    {
        $sets = [];

        for ($i = 0; $i < 3; $i++) {
            $processor = $this->getMockBuilder(\Kstasik\Vim\Model\Config\ProcessorInterface::class)
                ->setMethods(['run'])
                ->getMock();

            $sets[0][] = $processor;
        }

        return $sets;
    }

    /**
     * @dataProvider getWrongProcessorSets
     */
    public function testRunWithWrongProcessors(...$processorSet)
    {
        $this->setExpectedException(\Exception::class);

        $generator = new Generator($processorSet);

        $generator->run([
            Generator::DIRECTORY_CONFIG => '.vimconfig'
        ]);
    }

    /**
     * @dataProvider getProcessorSets
     */
    public function testRunValid(...$processorSet)
    {
        $generator = new Generator($processorSet);

        $generator->run([
            Generator::DIRECTORY_CONFIG => '.vimconfig'
        ]);
    }

    /**
     * @dataProvider getProcessorSets
     */
    public function testWithRealpath(...$processorSet)
    {
        $generator = new Generator($processorSet);

        $generator->run([
            Generator::DIRECTORY_CONFIG => '.vimconfig',
            Generator::REALPATH_CONFIG => '/realpath/to/smth'
        ]);
    }

    /**
     * @dataProvider getProcessorSets
     */
    public function testWithRuntime(...$processorSet)
    {
        $generator = new Generator($processorSet);

        $generator->run([
            Generator::DIRECTORY_CONFIG => '.vimconfig',
            Generator::VIMRUNTIME_CONFIG => '/vim/runtime/path'
        ]);
    }

    /*
    public function testDirectory()
    {
        $this->generator->setDirectory('/test/path');

        $reflectionClass = new \ReflectionClass('\Kstasik\Vim\Model\Config\Generator');

        $property = $reflectionClass->getProperty('directory');
        $property->setAccessible(true);
        $value = $property->getValue($this->generator);

        $this->assertEquals($value, '/test/path');
    }
*/
}
