<?php

namespace Kstasik\Vim\Model\Config\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;
use Kstasik\Vim\Model\Config\ProcessorInterface;
use GoetasWebservices\XML\XSDReader\SchemaReader;

class Xsd implements LoggerAwareInterface, ProcessorInterface
{
    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\Utility\Files
     */
    private $filesUtility;

    /**
     * @var \Magento\Framework\Config\Dom\UrnResolver
     */
    private $urnResolver;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    private $readFactory;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $dirList;

    public function __construct(
        DirectoryList $dirList,
        \Magento\Framework\App\Utility\Files $filesUtility,
        \Magento\Framework\Config\Dom\UrnResolver $urnResolver,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
    ) {
        $this->logger = new \Psr\Log\NullLogger();
        $this->dirList = $dirList;
        $this->filesUtility = $filesUtility;
        $this->urnResolver = $urnResolver;
        $this->readFactory = $readFactory;
    }

    public function run($configDirectory, $realpath = null)
    {
        $configFilePath = rtrim($configDirectory, '/').'/xsd';

        $this->logger->notice('xsd configuration directory: '.$configFilePath);

        if ($realpath) {
            $this->logger->notice('configuration real path in IDE context: '.$realpath);
        }

        $file = new File;
        $file->createDirectory($configFilePath);

        $dictionary = $this->getUrnDictionary();

        $map     = array();
        foreach ($dictionary as $name => $filePath) {
            $newfile = str_replace(':', '/', $name);

            // create directory if needed
            $dir = $configFilePath.'/'.pathinfo($newfile, PATHINFO_DIRNAME);
            if (!$file->isExists($dir)) {
                $file->createDirectory($dir);
            }

            // create xsd
            $file->filePutContents($configFilePath.'/'.$newfile, $file->fileGetContents($filePath));

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
        $file->filePutContents($configFilePath.'/namespaces.map', json_encode($ideMap));

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

        $file = new File;
        $file->createDirectory($configFilePath.'/autoload');

        foreach ($dictionary as $name => $filePath) {
            $newfile = str_replace(':', '/', $name);

            $reader = new SchemaReader();
            $schema = $reader->readFile($configFilePath.'/'.$newfile);

            $tree = array();
            $this->buildTree($tree, $schema);

            file_put_contents(
                $configFilePath.'/autoload/magento2'.md5($name).'.vim',
                PHP_EOL.'let g:xmldata_magento2'.md5($name).' = '.json_encode($tree)
            );
        }
    }

    private function mapConfigFiles($configFilePath, $map)
    {
        $file = new File;

        $result = $file->readDirectoryRecursively($configFilePath);
        foreach ($result as $filePath) {
            if ($file->isFile($filePath) && strpos($filePath, 'xsd') !== false) {
                $content = $file->fileGetContents($filePath);

                // map paths
                $content = strtr($content, $map);

                $file->filePutContents($filePath, $content);
            }
        }
    }

    private function getUrnDictionary()
    {
        $this->logger->notice('search for xml files with xsd defined in the project');

        $files = $this->filesUtility->getXmlCatalogFiles('*.xml');
        $files = array_merge($files, $this->filesUtility->getXmlCatalogFiles('*.xsd'));

        $urns = [];
        foreach ($files as $file) {
            $fileDir = dirname($file[0]);
            $fileName = basename($file[0]);
            $read = $this->readFactory->create($fileDir);
            $content = $read->readFile($fileName);
            $matches = [];
            preg_match_all('/schemaLocation="(urn\:magento\:[^"]*)"/i', $content, $matches);
            if (isset($matches[1])) {
                $urns = array_merge($urns, $matches[1]);
            }
        }
        $urns = array_unique($urns);
        $paths = [];
        foreach ($urns as $urn) {
            try {
                $paths[$urn] = $this->urnResolver->getRealPath($urn);
            } catch (\Exception $e) {

            }
        }

        $this->logger->notice('search done');

        return $paths;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    protected function buildTree(array &$tree, $root, $parents = array())
    {
        $parent = end($parents);

        if (method_exists($root, 'getElements')) {
            foreach ($root->getElements() as $element) {

                if ($element instanceof \GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef) {
                    /* @var $element \GoetasWebservices\XML\XSDReader\Schema\Element\GroupRef */
//                    $parents[] = $element->getName();

  //                  var_dump($parents);
   //                 var_dump($element->getName());

                    /* @var $choice \GoetasWebservices\XML\XSDReader\Schema\Element\Element */
                    foreach ($element->getElements() as $choice) {
                      //  echo get_class($choice);
                        $tree[$parent][0][] = $choice->getName();

                        $this->buildTree($tree, $choice->getType(), $parents);
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

                            $tree[$element->getName()] = array(
                                array(), // list of children
                                array(), // map of attributes and values as list
                            );

                            $this->buildTree($tree, $type, $parents);
                            //$data['elements'][$element->getName()] = $this->getOptions($type, $parents);
                        } else {
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
}
