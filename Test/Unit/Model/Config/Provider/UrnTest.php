<?php

namespace Kstasik\Vim\Test\Model\Config\Provider;

use Kstasik\Vim\Model\Config\Provider\Urn;

class UrnTest extends \PHPUnit_Framework_TestCase
{
    private $provider;

    private $filesUtility;

    private $urnResolver;

    private $readFactory;

    public function setUp()
    {
        $this->filesUtility = $this
            ->getMockBuilder('\Magento\Framework\App\Utility\Files')
            ->disableOriginalConstructor()
            ->setMethods(['getXmlCatalogFiles'])
            ->getMock();

        $this->urnResolver = $this
            ->getMockBuilder('\Magento\Framework\Config\Dom\UrnResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->readFactory = $this
            ->getMockBuilder('\Magento\Framework\Filesystem\Directory\ReadFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->readFactory
            ->expects($this->any())
            ->method('create')
            ->will($this->returnCallback(function () {
                $file = $this
                    ->getMockBuilder('\Magento\Framework\Filesystem\Directory\Read')
                    ->disableOriginalConstructor()
                    ->setMethods(['readFile'])
                    ->getMock();

                $file
                    ->expects($this->once())
                    ->method('readFile')
                    ->will($this->returnValue('<xs:include schemaLocation="urn:magento:framework:test/path/test.xsd"/>'));

                return $file;
            }));

        $this->processor = new Urn(
            $this->filesUtility,
            $this->urnResolver,
            $this->readFactory
        );
    }

    public function testNotEmptyDictionary()
    {
        $this->filesUtility
            ->expects($this->exactly(2))
            ->method('getXmlCatalogFiles')
            ->with($this->logicalOr(
                $this->equalTo('*.xml'),
                $this->equalTo('*.xsd')
            ))
            ->will($this->returnCallback(function ($argument) {
                return array(array('/tmp/test'));
            }));
            ;

        $this->assertCount(1, $this->processor->getDictionary());
    }

    public function testEmptyDictionary()
    {
        $this->filesUtility
            ->expects($this->exactly(2))
            ->method('getXmlCatalogFiles')
            ->with($this->logicalOr(
                $this->equalTo('*.xml'),
                $this->equalTo('*.xsd')
            ))
            ->will($this->returnCallback(function ($argument) {
                return array();
            }));

            ;
        $this->assertCount(0, $this->processor->getDictionary());
    }
}
