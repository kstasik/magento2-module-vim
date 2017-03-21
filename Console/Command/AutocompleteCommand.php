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
class AutocompleteCommand extends Command
{
    const COMMAND_NAME = 'dev:vim:autocomplete';

    public function __construct()
    {
        parent::__construct(self::COMMAND_NAME);
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
            ->setDescription('Returns a list for vim omnicomplete');
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
        $output->writeln('["test1","test2","test3"]');
    }
}
