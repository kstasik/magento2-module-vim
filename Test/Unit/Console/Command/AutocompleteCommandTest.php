<?php
/**
 *
 * PHP Version 5
 *
 * @category  Kstasik
 * @package   Vim
 * @author    Kacper Stasik <kacper.stasik@polcode.com>
 */
namespace Kstasik\Vim\Test\Unit\Console\Command;

use Symfony\Component\Console\Tester\CommandTester;
use Kstasik\Vim\Console\Command\AutocompleteCommand;

class AutocompleteCommandTest extends \PHPUnit_Framework_TestCase
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
        $this->command = new AutocompleteCommand();
    }

    public function testName()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertEquals($this->command->getName(), 'dev:vim:autocomplete');
    }

    public function testDescription()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertEquals($this->command->getDescription(), 'Returns a list for vim omnicomplete');
    }

    public function testResponse()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertContains('["test1","test2","test3"]', $commandTester->getDisplay());
    }
}
