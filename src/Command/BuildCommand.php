<?php

namespace FlyntWP\PremiumPluginPackagist\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use CaseHelper\CaseHelperFactory;
use Webmozart\PathUtil\Path;
use FlyntWP\PremiumPluginPackagist\VersionChecker;
use FlyntWP\PremiumPluginPackagist\SatisJsonBuilder;

class BuildCommand extends Command
{
    protected function configure()
    {
        $this->setName("build")
                ->setDescription("Check for package updates and update satis config.")
                ->addOption(
                    'config',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Config path',
                    'config.php'
                )
                ->addOption(
                    'packagesPath',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Where to store the packages',
                    './packages'
                );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configPath = $input->getOption('config');
        $configPath = Path::makeAbsolute($configPath, getcwd());
        $config = require($configPath);
        $packages = $config['packages'];
        $packagesPath = $input->getOption('packagesPath');
        $packagesPath = Path::makeAbsolute($packagesPath, getcwd());
        foreach ($packages as $packageName => $versions) {
            foreach (($versions ?? []) as $version) {
                $this->checkAndDownloadPackage($packageName, $packagesPath, $output, $version);
            }
            $this->checkAndDownloadPackage($packageName, $packagesPath, $output);
        }
        $satisJsonBuilder = new SatisJsonBuilder($config['satis'], $packagesPath);
        $satisJsonBuilder->run();

        $helper = $this->getHelper('process');
        $satisPath = Path::makeAbsolute('vendor/bin/satis', getcwd());
        $satisFile = Path::join(getcwd(), 'satis.json');
        $satisOutputDir = Path::makeAbsolute('web', getcwd());
        $a = $helper->run($output, "$satisPath build $satisFile $satisOutputDir");
    }

    protected function checkAndDownloadPackage($packageName, $packagesPath, $output, $version = null)
    {
        $versionChecker = new VersionChecker($packageName, $packagesPath);
        if (empty($version)) {
            $output->writeln("${packageName}: Checking latest version.");
            $version = $versionChecker->getLatestVersion();
        }
        $output->writeln("$packageName: $version");
        $latestVersionExists = $versionChecker->doesVersionExist($version);
        if ($latestVersionExists) {
            $output->writeln("${packageName}: Already exists locally.");
        } else {
            $output->writeln("${packageName}: Starting download.");
            $versionChecker->downloadPackage($version);
            $output->writeln("${packageName}: Download complete.");
        }
    }
}
