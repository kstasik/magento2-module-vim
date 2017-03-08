<?php

namespace Kstasik\Vim\Test\Model\Config\Processor;

use Kstasik\Vim\Model\Config\Processor\Install;
use Psr\Log\LoggerAwareInterface;

class InstallTest extends \PHPUnit_Framework_TestCase
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

        $filesUtilityMock = $this
            ->getMockBuilder('\Magento\Framework\App\Utility\Files')
            ->disableOriginalConstructor()
            ->setMethods(['getModuleFile'])
            ->getMock();

        $filesUtilityMock
            ->expects($this->any())
            ->method('getModuleFile')
            ->will($this->returnValue('asdasdasdas'));


        $dirListMock = $this
            ->getMockBuilder('\Magento\Framework\App\Filesystem\DirectoryList')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new Install(
            $this->fileMock,
            $filesUtilityMock,
            $dirListMock
        );
    }

    public function testLogger()
    {
        $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();

        $this->processor->setLogger($logger);

        $reflectionClass = new \ReflectionClass(\Kstasik\Vim\Model\Config\Processor\Install::class);

        $property = $reflectionClass->getProperty('logger');
        $property->setAccessible(true);
        $value = $property->getValue($this->processor);

        $this->assertEquals($value, $logger);
    }


    public function testContents()
    {
        $this->fileMock
            ->expects($this->once())
            ->method('filePutContents')
            ->with(
                $this->stringContains('install.sh'),
                $this->callback(function ($content) {
                    return strpos($content, '#!/bin/sh') === 0;
                })
            );

        $this->processor->run([
            \Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG => '.vimconfig',
            \Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG => null,
            \Kstasik\Vim\Model\Config\Generator::VIMRUNTIME_CONFIG => '/usr/share/vim/vim74'
        ]);
    }

    public function testContentsWithRealpath()
    {
        $this->fileMock
            ->expects($this->once())
            ->method('filePutContents')
            ->with(
                $this->stringContains('install.sh'),
                $this->callback(function ($content) {
                    return strpos($content, '#!/bin/sh') === 0;
                })
            );

        $this->processor->run([
            \Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG => '.vimconfig',
            \Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG => '/realpath',
            \Kstasik\Vim\Model\Config\Generator::VIMRUNTIME_CONFIG => '/usr/share/vim/vim74'
        ]);
    }
}
