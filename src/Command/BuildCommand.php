<?php

namespace PremiumPluginPackagist\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\ArrayInput;
use CaseHelper\CaseHelperFactory;
use Webmozart\PathUtil\Path;
use PremiumPluginPackagist\VersionChecker;
use PremiumPluginPackagist\SatisJsonBuilder;

class BuildCommand extends Command
{
    protected function configure()
    {
        $this->setName("build")
                ->setDescription("Check for package updates and update satis config.")
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
        $config = require(getcwd() . DIRECTORY_SEPARATOR . 'config.php');
        $requiredPackages = $config['requiredPackages'];
        $packagesPath = $input->getOption('packagesPath');
        $packagesPath = Path::makeAbsolute($packagesPath, getcwd());
        array_map(function ($packageName) use ($packagesPath, $output) {
            $this->checkAndDownloadPackage($packageName, $packagesPath, $output);
        }, $requiredPackages);

        $satisJsonBuilder = new SatisJsonBuilder($config['satis'], $packagesPath);
        $satisJsonBuilder->run();

        // var_dump(1);
        // $satisApplication = new \Composer\Satis\Console\Application();
        // var_dump(2);
        // $satisCommand = 'build';
        // var_dump(3);
        // $satisBuild = $satisApplication->find($satisCommand);
        // var_dump(4);
        //
        // $satisArguments = [
        //     'command' => $satisCommand,
        //     'file' => Path::join(getcwd(), 'satis.json'),
        //     'output-dir' => Path::makeAbsolute('web', getcwd())
        // ];
        //
        // $satisInput = new ArrayInput($satisArguments);
        // var_dump(5);
        // var_dump($satisBuild->run());
        // // var_dump($satisInput);
        // $satisReturnCode = $satisBuild->run($satisInput, $output);
        // var_dump(6);
        //
        $helper = $this->getHelper('process');
        $satisPath = Path::makeAbsolute('vendor/bin/satis', getcwd());
        $satisFile = Path::join(getcwd(), 'satis.json');
        $satisOutputDir = Path::makeAbsolute('web', getcwd());
        var_dump("$satisPath $satisFile $satisOutputDir");
        $a = $helper->run($output, "$satisPath build $satisFile $satisOutputDir");
        // var_dump($a);
        //
        // if ($saticReturnCode === 0) {
        //     $output->writeln('Successfully built!');
        // } else {
        //     $output->writeln('Error building satis.');
        // }
    }

    protected function checkAndDownloadPackage($packageName, $packagesPath, $output)
    {
        $versionChecker = new VersionChecker($packageName, $packagesPath);
        $output->writeln("${packageName}: Checking latest version.");
        $latestVersion = $versionChecker->getLatestVersion();
        $output->writeln("${packageName}: ${latestVersion}");
        $latestVersionExists = $versionChecker->doesVersionExist($latestVersion);
        if ($latestVersionExists) {
            $output->writeln("${packageName}: Already exists locally.");
        } else {
          $output->writeln("${packageName}: Starting download.");
          $versionChecker->downloadPackage($latestVersion);
          $output->writeln("${packageName}: Download complete.");
        }
    }

    // protected function getPackageRepositoryConfig()
    // {
    //     return [
    //         'type' => 'package',
    //         'package' => [
    //             'name' => 'mbovel/acf-code',
    //             'version' => '%version%',
    //             'type' => 'wordpress-plugin',
    //             'dist' => [
    //                 'type' => 'zip',
    //                 'url' => './packages/mbovel/acf-code-%version%.zip'
    //             ],
    //             'require' => [
    //                 'composer/installers' => '^1.0'
    //             ]
    //         ]
    //     ];
    // }
}
