<?php

namespace Kstasik\Vim\Model\Config\Provider;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\ObjectManagerInterface;

class Urn
{
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

    public function __construct(
        \Magento\Framework\App\Utility\Files $filesUtility,
        \Magento\Framework\Config\Dom\UrnResolver $urnResolver,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
    ) {
        $this->logger = new \Psr\Log\NullLogger();

        $this->filesUtility = $filesUtility;
        $this->urnResolver = $urnResolver;
        $this->readFactory = $readFactory;
    }

    public function getDictionary()
    {
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

        return $paths;
    }
}
