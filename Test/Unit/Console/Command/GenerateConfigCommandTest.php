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
namespace Kstasik\Vim\Test\Unit\Console\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Kstasik\Vim\Console\Command\GenerateConfigCommand;

/**
 * GenerateConfigCommandTest
 *
 * @category  Kstasik
 * @package   Vim
 * @author    Kacper Stasik <kacper.stasik@polcode.com>
 * @copyright 2017 Kacper Stasik
 * @license   MIT License
 * @link      http://github.com/kstasik/vim
 */
class GenerateConfigCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * command
     *
     * @var mixed
     */
    private $command;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        $generator = $this->getMockBuilder(\Kstasik\Vim\Model\Config\Generator::class)
                     ->disableOriginalConstructor()
                     ->setMethods(['run'])
                     ->getMock();
        $generator
            ->expects($this->once())
            ->method('run')
            ->will($this->returnValue(true));

        $this->command = new GenerateConfigCommand($generator);
    }

    public function testName()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertEquals($this->command->getName(), 'dev:vim:generate-config');
    }

    public function testDescription()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertEquals($this->command->getDescription(), 'Generates config for vim plugin');
    }

    public function testArguments()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([
                GenerateConfigCommand::DIR_ARGUMENT => 'test',
                '--'.GenerateConfigCommand::REAL_PATH_OPTION => 'test2',
                '--'.GenerateConfigCommand::VIM_RUNTIME_OPTION => 'test3'
        ]);

        $arg1 = $commandTester->getInput()->getArgument(
            GenerateConfigCommand::DIR_ARGUMENT
        );

        $this->assertEquals($arg1, 'test');

        $arg2 = $commandTester->getInput()->getOption(
            GenerateConfigCommand::REAL_PATH_OPTION
        );

        $this->assertEquals($arg2, 'test2');

        $arg3 = $commandTester->getInput()->getOption(
            GenerateConfigCommand::VIM_RUNTIME_OPTION
        );

        $this->assertEquals($arg3, 'test3');
    }

    public function testResponse(){
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertContains('Config files generated', $commandTester->getDisplay());
    }
}
