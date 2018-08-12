<?php

namespace FlyntWP\PremiumPluginPackagist;

use CaseHelper\CaseHelperFactory;
use Webmozart\PathUtil\Path;

class SatisJsonBuilder
{
    protected $packagesPath;
    protected $recipe;

    public function __construct($config, $packagesPath)
    {
        $this->config = $config;
        $this->packagesPath = $packagesPath;
    }

    public function run()
    {
        $this->config['repositories'] = [];
        $vendors = scandir($this->packagesPath);
        foreach ($vendors as $vendor) {
            if (in_array($vendor, ['..', '.'])) {
                continue;
            }
            $vendorPath = Path::join($this->packagesPath, $vendor);
            $packages = scandir($vendorPath);
            foreach ($packages as $package) {
                if (in_array($package, ['..', '.'])) {
                    continue;
                }
                list($plugin, $version) = $this->extractPackageAndVersion($package);
                if (!empty($plugin) && !empty($version)) {
                    $extension = Path::getExtension($package);
                    $this->addToConfig($vendor, $plugin, $version, $extension);
                }
            }
        }

        $satisJson = json_encode($this->config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents(Path::join(getcwd(), 'satis.json'), $satisJson);
    }

    protected function extractPackageAndVersion($filename)
    {
        $pattern = '(.[^.]*).(([0-9]+)(\.[0-9]+)?(\.[0-9]+)?(\.[0-9]+)?)$';
        $matches = [];
        preg_match("/$pattern/", Path::getFilenameWithoutExtension($filename), $matches);
        if (empty($matches)) {
            return [null, null];
        } else {
            return [$matches[1], $matches[2]];
        }
    }

    protected function addToConfig($vendor, $plugin, $version, $extension)
    {
        $packagesPath = $this->packagesPath;
        $this->config['repositories'][] = [
            'type' => 'package',
            'package' => [
                'name' => "$vendor/$plugin",
                'version' => "$version",
                'type' => 'wordpress-plugin',
                'dist' => [
                    'type' => 'zip',
                    'url' => "$packagesPath/$vendor/$plugin.$version.$extension"
                ],
                'require' => [
                    'composer/installers' => '^1.0'
                ]
            ]
        ];
    }
}
