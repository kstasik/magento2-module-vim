<?php

namespace Kstasik\Vim\Model\Config;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\ObjectManagerInterface;

/**
 * Generator
 *
 * @uses LoggerAwareInterface
 * @package
 * @author Name <address@domain>
 * @version $Id$
 */
class Generator implements LoggerAwareInterface
{
    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $processors;

    /**
     * @var string
     */
    protected $directory;

    /**
     * @var string
     */
    protected $realpath;

    /**
     * __construct
     *
     * @param array $processors List of processor objects
     *
     * @return void
     */
    public function __construct(array $processors)
    {
        $this->logger     = new \Psr\Log\NullLogger();
        $this->directory  = '.vimconfig';
        $this->processors = $processors;
    }

    public function run()
    {
        if (empty($this->directory)) {
            throw new \Exception('Directory name can\'t be empty');
        }

        $this->logger->notice(sprintf('directory: %s', $this->directory));

        if ($this->realpath) {
            $this->logger->notice(sprintf('real path: %s', $this->realpath));
        }

        foreach ($this->processors as $processor) {
            if (
                !($processor instanceof LoggerAwareInterface) ||
                !($processor instanceof ProcessorInterface)
            ) {
                throw new \Exception(sprintf('Processor "%s" doesnt inherit LoggerAwareInterface and ProcessorInterface', get_class($processor)));
            }

            $this->logger->notice(sprintf('running processor: %s', get_class($processor)));

            $processor
                ->setLogger($this->logger);

            $processor
                ->run($this->directory, $this->realpath);
        }
    }

    /**
     * setDirectory
     *
     * @param mixed $directory Directory name
     *
     * @return self
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
        return $this;
    }

    public function setRealpath($path)
    {
        $this->realpath = $path;
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
