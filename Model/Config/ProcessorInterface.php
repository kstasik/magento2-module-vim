<?php
namespace Kstasik\Vim\Model\Config;

interface ProcessorInterface
{
    public function run(array $config);
}
