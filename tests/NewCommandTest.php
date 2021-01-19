<?php

namespace Thtg88\MmCmsInstaller\Console\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Thtg88\MmCmsInstaller\Console\NewCommand;

class NewCommandTest extends TestCase
{
    public function test_it_can_scaffold_a_new_mmcms_app()
    {
        $appName = 'my-app';
        $scaffoldDirectoryName = 'tests-output'.DIRECTORY_SEPARATOR.$appName;
        $apiName = $appName.'-api';
        $adminName = $appName.'-admin';
        $scaffoldDirectory = __DIR__.DIRECTORY_SEPARATOR.'..'.
            DIRECTORY_SEPARATOR.$scaffoldDirectoryName;
        $scaffoldApiDirectory = $scaffoldDirectory.DIRECTORY_SEPARATOR.$apiName;
        $scaffoldAdminDirectory = $scaffoldDirectory.DIRECTORY_SEPARATOR.
            $adminName;

        // var_dump(
        //     $appName,
        //     $scaffoldDirectoryName,
        //     $apiName,
        //     // $adminName,
        //     $scaffoldDirectory,
        //     $scaffoldApiDirectory
        // );
        // die();

        if (file_exists($scaffoldDirectory)) {
            (new Filesystem())->remove($scaffoldDirectory);
        }

        $app = new Application('mmCMS Installer');
        $app->add(new NewCommand());

        $tester = new CommandTester($app->find('new'));

        $statusCode = $tester->execute(['name' => $scaffoldDirectoryName]);

        $this->assertEquals($statusCode, 0);
        $this->assertDirectoryExists($scaffoldDirectory);
        $this->assertDirectoryExists($scaffoldApiDirectory);
        $this->assertDirectoryExists($scaffoldAdminDirectory);
        $this->assertDirectoryExists($scaffoldApiDirectory.'/vendor');
        $this->assertDirectoryExists(
            $scaffoldAdminDirectory.'/node_modules'
        );
        $this->assertFileExists($scaffoldApiDirectory.'/.env');
    }
}
