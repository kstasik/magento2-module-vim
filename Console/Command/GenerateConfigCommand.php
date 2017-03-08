<?php
/**
 * Vim configuration generator
 *
 * PHP Version 5
 *
 * @category  Kstasik
 * @package   Vim
 * @author    Kacper Stasik <kacper.stasik@polcode.com>
 * @copyright 2017 Kacper Stasik
 * @license   MIT License
 * @link      http://github.com/kstasik/vim
 */
namespace Kstasik\Vim\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Kstasik\Vim\Model\Config\Generator;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\InputOption;
use Psr\Log\LogLevel;

/**
 * GenerateConfigCommand
 *
 * @category  Kstasik
 * @package   Vim
 * @author    Kacper Stasik <kacper.stasik@polcode.com>
 * @copyright 2017 Kacper Stasik
 * @license   MIT License
 * @link      http://github.com/kstasik/vim
 */
class GenerateConfigCommand extends Command
{
    const COMMAND_NAME = 'dev:vim:generate-config';

    const DIR_ARGUMENT = 'dir';

    const REAL_PATH_OPTION = 'real-path';

    const VIM_RUNTIME_OPTION = 'vim-runtime';

    protected $generator;

    /**
     * __construct
     *
     * @param Generator $generator Generator object
     *
     * @return void
     */
    public function __construct(Generator $generator)
    {
        parent::__construct(self::COMMAND_NAME);

        $this->generator = $generator;
    }

    /**
     * configure
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Generates config for vim plugin')
            ->addArgument(self::DIR_ARGUMENT, InputArgument::OPTIONAL, 'Directory with vim plugin configuration files', './.vimconfig')
            ->addOption(self::REAL_PATH_OPTION, null, InputOption::VALUE_OPTIONAL, 'Real absolute path to project in IDE context, useful for docker and vagrant')
            ->addOption(self::VIM_RUNTIME_OPTION, null, InputOption::VALUE_OPTIONAL, 'Absolute path to vim runtime directory in IDE context');
    }

    /**
     * execute
     *
     * @param InputInterface  $input  Input interface
     * @param OutputInterface $output Output interface
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verbosityLevelMap = array(
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO   => OutputInterface::VERBOSITY_NORMAL,
        );

        $logger = new ConsoleLogger($output, $verbosityLevelMap);

        $this
            ->generator
            ->setLogger($logger)
            ->run(array(
                Generator::DIRECTORY_CONFIG  => $input->getArgument(self::DIR_ARGUMENT),
                Generator::REALPATH_CONFIG   => $input->getOption(self::REAL_PATH_OPTION),
                Generator::VIMRUNTIME_CONFIG => $input->getOption(self::VIM_RUNTIME_OPTION)
            ));

        $output->writeln('Config files generated!');
    }
}
