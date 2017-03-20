<?php

namespace Kstasik\Vim\Model\Config\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Kstasik\Vim\Model\Config\ProcessorInterface;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Xsd implements LoggerAwareInterface, ProcessorInterface
{
    /**
     * @var GoetasWebservices\XML\XSDReader\SchemaReader
     */
    protected $reader;

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    private $readFactory;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $dirList;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $file;

    /**
     * @var \Kstasik\Vim\Model\Config\Provider\Urn
     */
    private $urnProvider;

    public function __construct(
        \Kstasik\Vim\Model\Config\Provider\Urn $urnProvider,
        \GoetasWebservices\XML\XSDReader\SchemaReader $reader,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\App\Filesystem\DirectoryList $dirList
    ) {
        $this->logger = new \Psr\Log\NullLogger();

        $this->reader = $reader;
        $this->file = $file;
        $this->dirList = $dirList;
        $this->urnProvider = $urnProvider;
    }

    public function run(array $config)
    {
        $configDirectory = $config[\Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG];
        $realpath = $config[\Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG] ?: null;

        $configFilePath = rtrim($configDirectory, '/').'/xsd';

        $this->logger->notice('xsd configuration directory: '.$configFilePath);

        if ($realpath) {
            $this->logger->notice('configuration real path in IDE context: '.$realpath);
        }

        $this->file->createDirectory($configFilePath);

        $this->logger->notice('searching for xml files with xsd defined in the project');

        $dictionary = $this->urnProvider->getDictionary();

        $this->logger->notice('search done');

        $map     = array();
        foreach ($dictionary as $name => $filePath) {
            $newfile = str_replace(':', '/', $name);

            // create directory if needed
            $dir = $configFilePath.'/'.pathinfo($newfile, PATHINFO_DIRNAME);
            if (!$this->file->isExists($dir)) {
                $this->file->createDirectory($dir);
            }

            // create xsd
            $this->file->filePutContents($configFilePath.'/'.$newfile, $this->file->fileGetContents($filePath));

            // create new mapping
            $map[$name] = realpath($configFilePath.'/'.$newfile);
        }

        // map config file paths to real paths
        $this->logger->notice('mapping xsd config file paths to real absolute paths');
        $this->mapConfigFiles($configFilePath, $map);

        // create validator .vim files
        $this->createVimFiles($configFilePath, $map);

        // build paths for ide
        $this->logger->notice('building IDE paths mapping');

        $ideMap = array_map(
            function ($path) use ($realpath) {
                return str_replace($this->dirList->getRoot(), rtrim($realpath, '/'), $path);
            },
            $map
        );

        // save ide mapping
        $this->file->filePutContents($configFilePath.'/namespaces.map', json_encode($ideMap));

        // to ide map
        $toIdeMap =
            array_combine(
                array_values($map),
                array_map(
                    function ($path) use ($realpath) {
                        return str_replace($this->dirList->getRoot(), rtrim($realpath, '/'), $path);
                    },
                    $map
                )
            );

        // map config file paths to real paths
        $this->logger->notice('mapping xsd config file paths to IDE absolute paths');
        $this->mapConfigFiles($configFilePath, $toIdeMap);
    }

    private function createVimFiles($configFilePath, $dictionary)
    {
        $this->logger->notice('generating XSD validator files .vim');

        $this->file->createDirectory($configFilePath.'/autoload');

        foreach ($dictionary as $name => $filePath) {
            $newfile = str_replace(':', '/', $name);

            $reader = clone $this->reader;
            $schema = $reader->readFile($configFilePath.'/'.$newfile);

            $tree = array();
            $this->getVimSchemaStructure($tree, $schema);

            $this->file->filePutContents(
                $configFilePath.'/autoload/magento2'.md5($name).'.vim',
                PHP_EOL.'let g:xmldata_magento2'.md5($name).' = '.json_encode($tree)
            );
        }
    }

    private function mapConfigFiles($configFilePath, $map)
    {

        $result = $this->file->readDirectoryRecursively($configFilePath);
        foreach ($result as $filePath) {
            if ($this->file->isFile($filePath) && strpos($filePath, 'xsd') !== false) {
                $content = $this->file->fileGetContents($filePath);

                // map paths
                $content = strtr($content, $map);

                $this->file->filePutContents($filePath, $content);
            }
        }
    }

    protected function getVimSchemaStructure(array &$tree, $root, $parents = array())
    {
        $parent = end($parents);

        if (method_exists($root, 'isAbstract') && $root->isAbstract()) {
            foreach ($root->getSchema()->getTypes() as $type) {
                if ($type->getExtension() && $type->getExtension()->getBase() == $root) {
                    $this->getVimSchemaStructure($tree, $type, $parents);
                }
            }
        }

        if (method_exists($root, 'getElements')) {
            foreach ($root->getElements() as $element) {
                if ($element instanceof \GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef) {
                    /* @var $element \GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef */
//                    $parents[] = $element->getName();

  //                  var_dump($parents);
   //                 var_dump($element->getName());

                    /* @var $choice \GoetasWebservices\XML\XSDReader\Schema\Element\Element */
                    xdebug_break();
                    foreach ($element->getElements() as $choice) {
                      //  echo get_class($choice);
                        $tree[$parent][0][] = $choice->getName();

                        $this->getVimSchemaStructure($tree, $choice->getType(), $parents);
                        //$data['elements'][$choice->getName()] = $this->getOptions($choice->getType(), $parents);


                 //        echo $choice->getName(); //- skip group name
                //        var_dump($this->getOptions($choice->getType(), $parents));
                        //

                      //  echo get_class($choice->getType());
                    }
                } else {
                    /* @var $type \GoetasWebservices\XML\XSDReader\Schema\Type */
                    $type = $element->getType();

                    if (in_array($element->getName(), $parents)) {
                        if ($parent) {
                            $tree[$parent][0][] = $element->getName();
                        }

                        //$data['elements'][$element->getName()] = 'recursion';
                    } else {
                        if ($type instanceof \GoetasWebservices\XML\XSDReader\Schema\Type\ComplexType) {
                            if ($parent) {
                                $tree[$parent][0][] = $element->getName();
                            }

                            $parents[] = $element->getName();

                            if (!isset($tree[$element->getName()])) {
                                $tree[$element->getName()] = array(
                                    array(), // list of children
                                    array(), // map of attributes and values as list
                                );
                            }

                            $this->getVimSchemaStructure($tree, $type, $parents);
                            //$data['elements'][$element->getName()] = $this->getOptions($type, $parents);
                        } else {
                            if ($parent) {
                                $tree[$parent][0][] = $element->getName();
                            }

                            $tree[$element->getName()] = array();

                            //$data['elements'][$element->getName()] = 'simple';
                        }
                    }
                }
            }

            if (method_exists($root, 'getAttributes')) {
                if ($parent) {
                    foreach ($root->getAttributes() as $attribute) {
                        $tree[$parent][1][$attribute->getName()] = array();
                    }
                }
            }
        } else {
            throw new \Exception('xsd parsing library still doesnt support some xsd tags');
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
