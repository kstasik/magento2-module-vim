<?php

namespace Kstasik\Vim\Model\Config\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Kstasik\Vim\Model\Config\ProcessorInterface;
use Magento\Framework\Filesystem\Driver\File;

class Links implements LoggerAwareInterface, ProcessorInterface
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

    public function __construct(
        \Magento\Framework\App\Utility\Files $filesUtility,
        \Magento\Framework\App\Filesystem\DirectoryList $dirList
    ) {
        $this->logger = new \Psr\Log\NullLogger();
        $this->filesUtility = $filesUtility;
        $this->dirList = $dirList;
    }

    public function run($configDirectory, $realpath = null)
    {
        $configFilePath = rtrim($configDirectory, '/').'/xsd';

        $configFileAbsolutePath = realpath($configDirectory);

        if ($realpath) {
            $configFileAbsolutePath = str_replace($this->dirList->getRoot(), rtrim($realpath, '/'), $configFileAbsolutePath);
        }

        // make it configurable, --vim-runtime
        $vimRuntime = '/usr/local/share/vim/vim74';

        $commands[] = sprintf('ln -s %s/xsd/autoload/magento* %s/autoload/xml/', $configFileAbsolutePath, $vimRuntime);

        $pluginFile = $this->filesUtility->getModuleFile('Kstasik', 'Vim', 'script/magento2.vim');

        if ($realpath) {
            $pluginFile = str_replace($this->dirList->getRoot(), rtrim($realpath, '/'), $pluginFile);
        }

        $commands[] = sprintf('ln -s %s ~/.vim/plugin/magento2.vim', $pluginFile);

        $this->logger->info('Please make sure following symlinks are set up properly:');

        foreach ($commands as $command) {
            $this->logger->notice($command);
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
