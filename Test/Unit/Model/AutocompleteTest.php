<?php

namespace Kstasik\Vim\Test\Model;

use Kstasik\Vim\Model\Autocomplete;

class AutocompleteTest extends \PHPUnit_Framework_TestCase
{
    private $autocomplete;

    public function setUp()
    {
        $this->autocomplete = new Autocomplete();
    }

    public function testClassExists()
    {
        $this->assertEquals(get_class($this->autocomplete), 'Kstasik\Vim\Model\Autocomplete');
    }
}
