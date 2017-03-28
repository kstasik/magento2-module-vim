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

    private $filesUtility;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $file;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;

    public function __construct(
        \Magento\Framework\App\Utility\Files $filesUtility,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        $this->filesUtility = $filesUtility;
        $this->file = $file;
        $this->cache = $cache;
    }

    public function getPreferenceTypeAttribute(string $base = null)
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
}
