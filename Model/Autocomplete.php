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
    const CACHE_ALL = 'vim_cache_all';

    const CACHE_IMPLEMENTS = 'vim_cache_all_implements';

    const CACHE_EXTENDS = 'vim_cache_all_extends';

    private $filesUtility;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $file;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    /**
     * Object manager provider
     *
     * @var \Magento\Framework\Module\FullModuleList
     */
    private $moduleList;

    public function __construct(
        \Magento\Framework\App\Utility\Files $filesUtility,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Module\FullModuleList $moduleList
    ) {
        $this->filesUtility = $filesUtility;
        $this->file = $file;
        $this->cache = $cache;
        $this->moduleList = $moduleList;
    }

    public function getClassesExtending(string $base = null, string $additional = null)
    {
        $result = $this->getAllClassesExtending();

        if ($additional && isset($result[$additional])) {
            if ($base) {
                $result[$additional] = array_filter($result[$additional], function ($path) use ($base) {
                    return preg_match('/'.preg_quote($base).'/i', $path);
                });
            }

            return array_values($result[$additional]);
        }

        return [];
    }

    public function getPreferenceTypeAttribute(string $base = null, string $additional = null)
    {
        $result = $this->getAllClassesImplementing();

        if ($additional && isset($result[$additional])) {
            if ($base) {
                $result[$additional] = array_filter($result[$additional], function ($path) use ($base) {
                    return preg_match('/'.preg_quote($base).'/i', $path);
                });
            }

            return array_values($result[$additional]);
        }

        return [];
    }

    public function getPreferenceForAttribute(string $base = null)
    {
        $result = $this->getAllTags();

        // interfaces only
        $result = array_filter($result, function ($path) use ($base) {
            return preg_match('/Interface$/i', $path);
        });

        // base
        if ($base) {
            $result = array_filter($result, function ($path) use ($base) {
                return preg_match('/'.preg_quote($base).'/i', $path);
            });
        }

        $result = array_splice($result, 0, 1000);

        return $result;
    }

    public function getModules(string $base = null)
    {
        return $this->moduleList->getNames();
    }

    public function complete(string $base = null)
    {
        $result = $this->getAllTags();

        if ($base) {
            $result = array_filter($result, function ($path) use ($base) {
                return preg_match('/'.preg_quote($base).'/i', $path);
            });
        }

        $result = array_splice($result, 0, 1000);

        return $result;
    }

    protected function getAllTags()
    {
        $result = array();

        if (!($saved = $this->cache->load(self::CACHE_ALL))) {
            $files = array_keys($this->filesUtility->getPhpFiles());
            foreach ($files as $file) {
                $content = $this->file->fileGetContents($file);

                if (
                    preg_match('/namespace (.*);/', $content, $m) &&
                    preg_match('/(class|interface) ([A-z\_]+)/', $content, $c)
                ) {
                    $result[] = $m[1].'\\'.$c[2];
                }
            }

            $this->cache->save(serialize($result), self::CACHE_ALL);
        } else {
            $result = unserialize($saved);
        }

        return $result;
    }

    protected function getAllClassesImplementing()
    {
        $result = array();

        if (!($saved = $this->cache->load(self::CACHE_IMPLEMENTS))) {
            $files = array_keys($this->filesUtility->getPhpFiles());
            foreach ($files as $file) {
                $content = $this->file->fileGetContents($file);

                if (
                    preg_match('/namespace (.*);/', $content, $m) &&
                    preg_match('/'."\n".'class ([A-z0-9\_]+)/', $content, $c) &&
                    preg_match('/implements (.*)/', $content, $i)
                ) {
                    $class = $m[1].'\\'.$c[1];

                    if (strpos($class, 'Test') !== false || !class_exists($class)) {
                        continue;
                    }

                    try {
                        $rf = new \ReflectionClass($class);

                        foreach ($rf->getInterfaceNames() as $interface) {
                            $result[$interface][] = $class;
                        }

                    } catch (\ReflectionException $e) {

                    } /*catch (\RuntimeException $e) {

                    }*/
                }
            }

            $this->cache->save(serialize($result), self::CACHE_IMPLEMENTS);
        } else {
            $result = unserialize($saved);
        }

        return $result;
    }

    protected function getAllClassesExtending()
    {
        $result = array();

        if (true || !($saved = $this->cache->load(self::CACHE_EXTENDS))) {
            $files = array_keys($this->filesUtility->getPhpFiles());
            foreach ($files as $file) {
                $content = $this->file->fileGetContents($file);

                if (
                    preg_match('/namespace (.*);/', $content, $m) &&
                    preg_match('/'."\n".'class ([A-z0-9\_]+)/', $content, $c) &&
                    preg_match('/extends (.*)/', $content, $i)
                ) {
                    $class = $m[1].'\\'.$c[1];

                    if ($c[1] == 'Void' || strpos($class, 'Test') !== false || !class_exists($class)) {
                        continue;
                    }

                    try {
                        $rf = new \ReflectionClass($class);

                        if ($extends = $rf->getParentClass()) {
                            $result[$extends->getName()][] = $class;
                        }

                    } catch (\ReflectionException $e) {

                    } /*catch (\RuntimeException $e) {

                    }*/
                }
            }

            $this->cache->save(serialize($result), self::CACHE_EXTENDS);
        } else {
            $result = unserialize($saved);
        }

        return $result;
    }
}
