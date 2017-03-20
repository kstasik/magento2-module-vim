<?php

namespace Kstasik\Vim\Test\Model\Config\Processor;

use GoetasWebservices\XML\XSDReader\SchemaReader;
use Kstasik\Vim\Model\Config\Processor\Xsd;
use Psr\Log\LoggerAwareInterface;

class XsdTest extends \PHPUnit_Framework_TestCase
{
    private $schemaReader;

    private $file;

    private $dirList;

    private $filesUtility;

    private $urnResolver;

    private $readFactory;

    private $urn;

    private $processor;

    public function setUp()
    {
        $this->urn = $this
            ->getMockBuilder('\Kstasik\Vim\Model\Config\Provider\Urn')
            ->disableOriginalConstructor()
            ->setMethods(['getDictionary'])
            ->getMock();

        $this->urn
            ->expects($this->any())
            ->method('getDictionary')
            ->will($this->returnValue(array(
                'urn:magento:module:Magento_Store:etc/test.xsd' => '/my/root/path/etc/test.xsd'
            )));

        $this->schemaReader = $this
            ->getMockBuilder('\GoetasWebservices\XML\XSDReader\SchemaReader')
            ->disableOriginalConstructor()
            ->setMethods(['readFile'])
            ->getMock();

        $emptySchema = $this
            ->getMockBuilder('\GoetasWebservices\XML\XSDReader\Schema\Schema')
            ->disableOriginalConstructor()
            ->setMethods(['getElements'])
            ->getMock();

        $emptySchema
            ->expects($this->any())
            ->method('getElements')
            ->will($this->returnValue(array()));

        $this->schemaReader
            ->expects($this->any())
            ->method('readFile')
            ->will($this->returnValue(
                $emptySchema
            ));

        $this->file = $this
            ->getMockBuilder('\Magento\Framework\Filesystem\Driver\File')
            ->disableOriginalConstructor()
            ->setMethods(['readDirectoryRecursively', 'fileGetContents', 'filePutContents', 'isFile', 'isExists'])
            ->getMock();

        $this->file
            ->expects($this->any())
            ->method('readDirectoryRecursively')
            ->will($this->returnValue(array(
                '/path/to/file/test.xsd'
            )));

        $this->file
            ->expects($this->any())
            ->method('isFile')
            ->will($this->returnValue(true));

        $this->file
            ->expects($this->any())
            ->method('isExists')
            ->will($this->returnValue(false));

        $this->file
            ->expects($this->any())
            ->method('fileGetContents')
            ->will($this->returnValue('test contents'));

        $this->file
            ->expects($this->any())
            ->method('filePutContents')
            ->will($this->returnValue(true));

        $this->dirList = $this
            ->getMockBuilder('\Magento\Framework\App\Filesystem\DirectoryList')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dirList
            ->expects($this->any())
            ->method('getRoot')
            ->will($this->returnValue('/my/root/path'));

        $this->processor = new Xsd(
            $this->urn,
            $this->schemaReader,
            $this->file,
            $this->dirList
        );
    }

    public function testRun()
    {
        $this->processor->run([
            \Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG => '.vimconfig',
            \Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG => null
        ]);
    }

    public function testRunWithRealpath()
    {
        $this->processor->run([
            \Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG => '.vimconfig',
            \Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG => '/realpath/test'
        ]);
    }

    public function testLogger()
    {
        $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();

        $this->processor->setLogger($logger);

        $reflectionClass = new \ReflectionClass('\Kstasik\Vim\Model\Config\Processor\Xsd');

        $property = $reflectionClass->getProperty('logger');
        $property->setAccessible(true);
        $value = $property->getValue($this->processor);

        $this->assertEquals($value, $logger);
    }

    public function getVimSchemaValidationData()
    {
        $reader = new SchemaReader();

        return [
            'simple test' => [
                $reader->readString(
                    '
            <xs:schema targetNamespace="http://www.example.com" xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="myType">
                    <xs:sequence>
                        <xs:element name="el1" type="xs:int"></xs:element>
                    </xs:sequence>
                </xs:complexType>

                <xs:element name="myElement" type="myType"></xs:element>
            </xs:schema>'
                ),
                [
                    'myElement' => [['el1'], []],
                    'el1' => []
                ]
            ]
        ];
    }

    public function getVimInheritedSchemaValidationData()
    {
        $reader = new SchemaReader();

        return [
            'simple test' => [
                $reader->readString(
                    '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
                <xs:complexType name="argumentType" abstract="true" mixed="true">
                    <xs:attribute name="name" use="required"/>
                </xs:complexType>

                <xs:complexType name="array" mixed="true">
                    <xs:complexContent>
                        <xs:extension base="argumentType">
                            <xs:sequence>
                                <xs:element name="item" type="argumentType" minOccurs="0" maxOccurs="unbounded">
                                    <xs:key name="itemName">
                                        <xs:selector xpath="item"></xs:selector>
                                        <xs:field xpath="@name"></xs:field>
                                    </xs:key>
                                </xs:element>
                            </xs:sequence>
                        </xs:extension>
                    </xs:complexContent>
                </xs:complexType>

                <xs:complexType name="object">
                    <xs:complexContent>
                        <xs:extension base="argumentType"/>
                    </xs:complexContent>
                </xs:complexType>

                <xs:complexType name="argumentsType">
                    <xs:sequence>
                        <xs:element name="argument" type="argumentType" minOccurs="1" maxOccurs="unbounded">
                            <xs:key name="argumentItemName">
                                <xs:selector xpath="item"></xs:selector>
                                <xs:field xpath="@name"></xs:field>
                            </xs:key>
                        </xs:element>
                    </xs:sequence>
                </xs:complexType>
            </xs:schema>',
                    'http://www.example.com/types.xsd'
                ),
                $reader->readString(
                    '
            <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">

                <xs:redefine schemaLocation="http://www.example.com/types.xsd">
                    <xs:complexType name="argumentType" abstract="true" mixed="false">
                        <xs:complexContent>
                            <xs:extension base="argumentType" />
                        </xs:complexContent>
                    </xs:complexType>

                    <xs:complexType name="object">
                        <xs:complexContent>
                            <xs:extension base="object">
                                <xs:attribute name="shared" use="optional" type="xs:boolean"/>
                            </xs:extension>
                        </xs:complexContent>
                    </xs:complexType>
                </xs:redefine>

                <xs:element name="config">
                    <xs:complexType>
                        <xs:choice maxOccurs="unbounded">
                            <xs:element name="type" type="typeType" minOccurs="0" maxOccurs="unbounded">
                                <xs:unique name="uniqueTypeParam">
                                    <xs:selector xpath="param" />
                                    <xs:field xpath="@name" />
                                </xs:unique>
                            </xs:element>
                        </xs:choice>
                    </xs:complexType>
                </xs:element>

                <xs:complexType name="typeType">
                    <xs:choice maxOccurs="unbounded">
                        <xs:element name="arguments" type="argumentsType" minOccurs="0" maxOccurs="1">
                            <xs:key name="argumentName">
                                <xs:selector xpath="argument"></xs:selector>
                                <xs:field xpath="@name"></xs:field>
                            </xs:key>
                        </xs:element>
                        <xs:element name="plugin" type="xs:string" minOccurs="0" maxOccurs="unbounded" />
                    </xs:choice>
                    <xs:attribute name="name" type="xs:string" use="required" />
                    <xs:attribute name="shared" type="xs:boolean" use="optional" />
                </xs:complexType>

            </xs:schema>'
                ),
                [
                    'config' => [['type'], []],
                    'type' => [['arguments','plugin'], ['name' => [], 'shared' => []]],
                    'arguments' => [['argument'], []],
                    'argument' => [['item'], ['name' => []]],
                    'plugin' => [],
                    'item' => [['item'], ['name' => []]]
                ]
            ]
        ];

    }

    /**
     *
     * @dataProvider getVimInheritedSchemaValidationData
     */
    public function testVimInheritedSchemaStructure(
        \GoetasWebservices\XML\XSDReader\Schema\Schema $remoteSchema,
        \GoetasWebservices\XML\XSDReader\Schema\Schema $schema,
        $expected
    ) {
        $class = new \ReflectionClass('\Kstasik\Vim\Model\Config\Processor\Xsd');
        $method = $class->getMethod('getVimSchemaStructure');
        $method->setAccessible(true);

        $result = [];

        $method->invokeArgs(
            $this->processor,
            [
                &$result,
                $schema
            ]
        );

        $this->assertEquals($expected, $result);
    }

    /**
     *
     * @dataProvider getVimSchemaValidationData
     */
    public function testVimSchemaStructure(\GoetasWebservices\XML\XSDReader\Schema\Schema $schema, $expected)
    {
        $class = new \ReflectionClass('\Kstasik\Vim\Model\Config\Processor\Xsd');
        $method = $class->getMethod('getVimSchemaStructure');
        $method->setAccessible(true);

        $result = [];

        $method->invokeArgs(
            $this->processor,
            [
                &$result,
                $schema
            ]
        );

        $this->assertEquals($expected, $result);
    }
}
