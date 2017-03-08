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
    const DIRECTORY_CONFIG = 'directory';

    const REALPATH_CONFIG = 'realpath';

    const VIMRUNTIME_CONFIG = 'vimruntime';

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $processors;

    /**
     * @var array
     */
    protected $default = array(
            self::DIRECTORY_CONFIG => '.vimconfig',
            self::REALPATH_CONFIG => null,
            self::VIMRUNTIME_CONFIG => null
        );

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
        $this->processors = $processors;
    }

    public function run($config)
    {
        if (empty($config[self::DIRECTORY_CONFIG])) {
            throw new \Exception('Directory name can\'t be empty');
        }

        $config = array_merge($this->default, array_filter($config));

        $this->logger->notice(sprintf('directory: %s', $config[self::DIRECTORY_CONFIG]));

        if ($config[self::REALPATH_CONFIG]) {
            $this->logger->notice(sprintf('real path: %s', $config[self::REALPATH_CONFIG]));
        }

        if ($config[self::VIMRUNTIME_CONFIG]) {
            $this->logger->notice(sprintf('vim runtime: %s', $config[self::VIMRUNTIME_CONFIG]));
        }

        foreach ($this->processors as $processor) {
            if (
                !($processor instanceof LoggerAwareInterface) ||
                !($processor instanceof ProcessorInterface)
            ) {
                throw new \Exception(sprintf('Processor "%s" doesnt inherit LoggerAwareInterface and ProcessorInterface', get_class($processor)));
            }

            $this
                ->logger
                ->notice(sprintf('running processor: %s', get_class($processor)));

            $processor
                ->setLogger($this->logger);

            $processor
                ->run($config);
        }
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
