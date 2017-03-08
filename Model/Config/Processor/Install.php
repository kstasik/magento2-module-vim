<?php

namespace Kstasik\Vim\Model\Config\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Kstasik\Vim\Model\Config\ProcessorInterface;
use Magento\Framework\Filesystem\Driver\File;

class Install implements LoggerAwareInterface, ProcessorInterface
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
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $dirList;

    /**
     * @var  \Magento\Framework\Filesystem\Driver\File
     */
    private $file;

    public function __construct(
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\App\Utility\Files $filesUtility,
        \Magento\Framework\App\Filesystem\DirectoryList $dirList
    ) {
        $this->logger = new \Psr\Log\NullLogger();
        $this->filesUtility = $filesUtility;
        $this->dirList = $dirList;
        $this->file = $file;
    }

    public function run(array $config)
    {
        $configDirectory = $config[\Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG];
        $realpath = $config[\Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG] ?: null;
        $vimRuntime = $config[\Kstasik\Vim\Model\Config\Generator::VIMRUNTIME_CONFIG];

        $configFilePath = rtrim($configDirectory, '/').'/xsd';

        $configFileAbsolutePath = realpath($configDirectory);

        if ($realpath) {
            $configFileAbsolutePath = str_replace($this->dirList->getRoot(), rtrim($realpath, '/'), $configFileAbsolutePath);
        }

        $commands[] = sprintf('rm -f %s/autoload/xml/magento2*', $vimRuntime);

        $commands[] = sprintf('ln -s %s/xsd/autoload/magento* %s/autoload/xml/', $configFileAbsolutePath, $vimRuntime);

        $pluginFile = $this->filesUtility->getModuleFile('Kstasik', 'Vim', 'script/magento2.vim');

        if ($realpath) {
            $pluginFile = str_replace($this->dirList->getRoot(), rtrim($realpath, '/'), $pluginFile);
        }

        $commands[] = sprintf('rm ~/.vim/plugin/magento2.vim');

        $commands[] = sprintf('ln -s %s ~/.vim/plugin/magento2.vim', $pluginFile);

        $this->logger->info('Please run following command:');

        $this->logger->notice(rtrim($config[\Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG], '/').'/install.sh');

        $this->file->filePutContents($configDirectory.'/install.sh', '#!/bin/sh'.PHP_EOL.PHP_EOL.implode(PHP_EOL, $commands));
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
