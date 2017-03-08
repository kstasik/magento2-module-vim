<?php

namespace Kstasik\Vim\Model\Config\Processor;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Kstasik\Vim\Model\Config\ProcessorInterface;
use Magento\Framework\Filesystem\Driver\File;

class Variables implements LoggerAwareInterface, ProcessorInterface
{
    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

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
        \Magento\Framework\App\Filesystem\DirectoryList $dirList
    ) {
        $this->logger = new \Psr\Log\NullLogger();
        $this->dirList = $dirList;
        $this->file = $file;
    }

    protected function getVariables(array $config)
    {
        $variables = [
            'PHP_PATH' => 'php',
            'PATH_MAP' => []
        ];

        if ($config[\Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG]) {
            $variables['PATH_MAP'][$config[\Kstasik\Vim\Model\Config\Generator::REALPATH_CONFIG]]
                = $this->dirList->getRoot();
        }

        return $variables;
    }

    public function run(array $config)
    {
        $configDirectory = $config[\Kstasik\Vim\Model\Config\Generator::DIRECTORY_CONFIG];

        $variables = $this->getVariables($config);

        $file = '" vim magento2 plugin env variables';

        foreach ($variables as $name => $value) {
            if (is_array($value)) {
                $file .= PHP_EOL.PHP_EOL
                    . sprintf('let g:%s = %s', $name, json_encode($value));
            } else {
                $file .= PHP_EOL.PHP_EOL
                    . sprintf('let g:%s = "%s"', $name, $value);
            }
        }

        $this->logger->info('variables.vim has been created');

        $configPath = $configDirectory.'/variables.vim';

        if (!$this->file->isExists($configPath)) {
            $this->file->filePutContents($configPath, $file);
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
