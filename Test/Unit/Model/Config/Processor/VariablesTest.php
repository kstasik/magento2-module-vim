<?php

namespace Kstasik\Vim\Test\Model\Config\Processor;

use Kstasik\Vim\Model\Config\Processor\Variables;
use Psr\Log\LoggerAwareInterface;

class VariablesTest extends \PHPUnit_Framework_TestCase
{
    private $processor;

    private $fileMock;

    public function setUp()
    {
        $this->fileMock = $this
            ->getMockBuilder('\Magento\Framework\Filesystem\Driver\File')
            ->disableOriginalConstructor()
            ->setMethods(['isExists', 'filePutContents'])
            ->getMock();

        $dirListMock = $this
            ->getMockBuilder('\Magento\Framework\App\Filesystem\DirectoryList')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new Variables(
            $this->fileMock,
            $dirListMock
        );
    }

    public function testLogger()
    {
        $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();

        $this->processor->setLogger($logger);

        $reflectionClass = new \ReflectionClass(\Kstasik\Vim\Model\Config\Processor\Variables::class);

        $property = $reflectionClass->getProperty('logger');
        $property->setAccessible(true);
        $value = $property->getValue($this->processor);

        $this->assertEquals($value, $logger);
    }

    public function testContents()
    {
        $constants = ['PHP_PATH', 'PATH_MAP'];

        $this->fileMock
            ->expects($this->once())
            ->method('isExists')
            ->will($this->returnValue(false));

        $this->fileMock
            ->expects($this->once())
            ->method('filePutContents')
            ->with(
                $this->stringContains('variables.vim'),
                $this->callback(function ($content) use ($constants) {
                    return preg_match('/('.implode('|', $constants).')/', $content);
                })
            );

        $this->processor->run([
            \Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG => '.vimconfig',
            \Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG => null
        ]);
    }

    public function testExists()
    {
        $this->fileMock
            ->expects($this->once())
            ->method('isExists')
            ->will($this->returnValue(true));

        $this->fileMock
            ->expects($this->never())
            ->method('filePutContents');

        $this->processor->run([
            \Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG => '.vimconfig',
            \Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG => null
        ]);
    }

    public function testDoesntExist()
    {
        $this->fileMock
            ->expects($this->once())
            ->method('isExists')
            ->will($this->returnValue(false));

        $this->fileMock
            ->expects($this->once())
            ->method('filePutContents')
            ->will($this->returnValue(true));

        $this->processor->run([
            \Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG => '.vimconfig',
            \Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG => null
        ]);
    }

    public function testRealpathExists()
    {
        $this->fileMock
            ->expects($this->once())
            ->method('isExists')
            ->will($this->returnValue(false));

        $this->fileMock
            ->expects($this->once())
            ->method('filePutContents')
            ->will($this->returnValue(true));

        $this->processor->run([
            \Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG => '.vimconfig',
            \Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG => '/asdasd/asdasd'
        ]);
    }
}
