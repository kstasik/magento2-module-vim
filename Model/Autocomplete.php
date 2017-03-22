<?php

namespace Kstasik\Vim\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\ObjectManagerInterface;

/**
 * autocomplete
 *
 * @uses LoggerAwareInterface
 * @package
 * @author Name <address@domain>
 * @version $Id$
 */
class Autocomplete
{
    public function complete()
    {
        $result = [];

        $result[] = '\class\test\do\smth';
        $result[] = '\class';
        $result[] = '\todo';

        return $result;
    }
}
