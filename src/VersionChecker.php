<?php

namespace PremiumPluginPackagist;

use CaseHelper\CaseHelperFactory;
use Webmozart\PathUtil\Path;

class VersionChecker
{
    protected $packageName;
    protected $packagesPath;
    protected $author;
    protected $plugin;
    protected $recipe;

    function __construct($packageName, $packagesPath)
    {
        $this->packageName = $packageName;
        $this->packagesPath = $packagesPath;
        list($this->author, $this->plugin) = explode('/', $packageName);

        $caseHelper = CaseHelperFactory::make(CaseHelperFactory::INPUT_TYPE_KEBAB_CASE);
        $author = $caseHelper->toPascalCase($this->author);
        $plugin = $caseHelper->toPascalCase($this->plugin);
        $recipeClass = "\\PremiumPluginDownloader\\Recipes\\$author\\$plugin";
        $this->recipe = new $recipeClass();
    }

    public function getLatestVersion()
    {
        return $this->recipe->getLatestVersion();
    }

    public function doesVersionExist($version)
    {
        return file_exists($this->getPathForVersion($version));
    }

    public function downloadPackage($version)
    {
        mkdir($this->getPackagePath(), 0755, true);
        return $this->recipe->download($version, $this->getPathForVersion($version));
    }

    protected function getPathForVersion($version)
    {
        return Path::join(
            $this->getPackagePath(),
            $this->getFilenameForVersion($version)
        );
    }

    protected function getPackagePath()
    {
        return Path::join(
            $this->packagesPath,
            $this->author
        );
    }

    protected function getFilenameForVersion($version) {
        return $this->plugin . '.' . $version . $this->recipe->getFileExtension();
    }
}
