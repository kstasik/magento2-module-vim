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
use Kstasik\Vim\Model\Autocomplete;

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

    const TAG_OPTION = 'tag';

    const FILE_OPTION = 'file';

    const ATTRIBUTE_OPTION = 'attribute';

    const BASE_OPTION = 'base';

    const ADDITIONAL_OPTION = 'additional';

    protected $autocomplete;

    public function __construct(Autocomplete $autocomplete)
    {
        parent::__construct(self::COMMAND_NAME);

        $this->autocomplete = $autocomplete;
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
            ->setDescription('Returns a list for vim omnicomplete')
            ->addOption(self::TAG_OPTION, null, InputOption::VALUE_OPTIONAL, 'XML tag')
            ->addOption(self::FILE_OPTION, null, InputOption::VALUE_OPTIONAL, 'Filename with relative path')
            ->addOption(self::ATTRIBUTE_OPTION, null, InputOption::VALUE_OPTIONAL, 'XML attribute argument')
            ->addOption(self::BASE_OPTION, null, InputOption::VALUE_OPTIONAL, 'Already typed base in the file')
            ->addOption(self::ADDITIONAL_OPTION, null, InputOption::VALUE_OPTIONAL, 'Additional optional')
            ;
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
        if ($input->getOption(self::TAG_OPTION) == 'preference' && $input->getOption(self::ATTRIBUTE_OPTION) == 'for') {
            $result = $this->autocomplete->getPreferenceForAttribute(
                $input->getOption(self::BASE_OPTION)
            );
        } elseif ($input->getOption(self::TAG_OPTION) == 'frontend_model') {
            $result = $this->autocomplete->getClassesExtending(
                $input->getOption(self::BASE_OPTION),
                '\Magento\Config\Block\System\Config\Form\Fieldset'
            );
        } elseif ($input->getOption(self::TAG_OPTION) == 'module' && $input->getOption(self::ATTRIBUTE_OPTION) == 'name') {
            $result = $this->autocomplete->getModules(
                $input->getOption(self::BASE_OPTION)
            );

        } elseif ($input->getOption(self::TAG_OPTION) == 'preference' && in_array($input->getOption(self::ATTRIBUTE_OPTION), ['xsi:type', 'type'])) {
            $result = $this->autocomplete->getPreferenceTypeAttribute(
                $input->getOption(self::BASE_OPTION),
                $input->getOption(self::ADDITIONAL_OPTION)
            );
        } else {
            $result = $this->autocomplete->complete(
                $input->getOption(self::BASE_OPTION)
            );
        }

        $output->writeln(json_encode($result));
    }
}
