<?php

namespace Thtg88\MmCmsInstaller\Console;

use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use ZipArchive;

class NewCommand extends Command
{
    /**
     * The URL of the zipball file for the Admin.
     *
     * @var string
     */
    private $admin_zipball_url;

    /** @var string */
    private $api_directory;

    /**
     * The URL of the zipball file for the API.
     *
     * @var string
     */
    private $api_zipball_url;

    /** @var string */
    private static $github_owner_name = 'thtg88';

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('new')
            ->setDescription('Create a new mmCMS application')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Forces install even if the directory already exists'
            );
    }

    /**
     * Execute the command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        if (version_compare(PHP_VERSION, '7.4.6', '<')) {
            throw new RuntimeException(
                'The mmCMS installer requires PHP 7.4.6 or greater.'
            );
        }

        if (!extension_loaded('zip')) {
            throw new RuntimeException(
                'The Zip PHP extension is not installed. '.
                'Please install it and try again.'
            );
        }

        $name = $input->getArgument('name');

        if ($name && $name !== '.') {
            $directory = getcwd().DIRECTORY_SEPARATOR.$name;
        } else {
            $directory = getcwd();
        }

        $name_parts = explode(DIRECTORY_SEPARATOR, $name);
        $last_name_parts_idx = count($name_parts) - 1;

        $this->api_directory = $directory.DIRECTORY_SEPARATOR.
            $name_parts[$last_name_parts_idx].'-api';
        $admin_directory = $directory.DIRECTORY_SEPARATOR.
            $name_parts[$last_name_parts_idx].'-admin';

        if (!$input->getOption('force')) {
            $this->ensureApplicationDoesntExist($directory);
        }

        $output->writeln('<info>Creating application...</info>');

        $api_zip_file = $this->makeApiFilename();
        $admin_zip_file = $this->makeAdminFilename();

        $this->downloadApi($api_zip_file)
            ->extract($api_zip_file, $this->api_directory)
            ->prepareWritableApiDirectories($this->api_directory, $output)
            ->cleanUp($api_zip_file)
            ->downloadAdmin($admin_zip_file)
            ->extract($admin_zip_file, $admin_directory)
            ->cleanUp($admin_zip_file)
            ->installApiDependencies($this->api_directory, $input, $output)
            ->installAdminDependencies($admin_directory, $output);

        $output->writeln('<comment>mmCMS completed ready!</comment>');

        return 0;
    }

    /**
     * Ensure that the application does not already exist.
     *
     * @param string $directory
     *
     * @return void
     */
    protected function ensureApplicationDoesntExist(string $directory): void
    {
        if (
            (is_dir($directory) || is_file($directory)) &&
            $directory != getcwd()
        ) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Generate a random temporary filename for the API.
     *
     * @return string
     */
    protected function makeApiFilename(): string
    {
        return getcwd().'/mmcms_api-'.md5(time().uniqid()).'.zip';
    }

    /**
     * Generate a random temporary filename for the Admin.
     *
     * @return string
     */
    protected function makeAdminFilename(): string
    {
        return getcwd().'/mmcms_admin-'.md5(time().uniqid()).'.zip';
    }

    /**
     * Download the temporary Zip to the given file for the API.
     *
     * @param string $zipFile
     *
     * @return self
     */
    protected function downloadApi(string $zipFile): self
    {
        $client = new Client();

        // Retrieve latest tag from GitHub's API
        $github_tag_url = 'https://api.github.com/repos/thtg88/mmcms-demo-api'.
            '/tags?per_page=1';
        $response = $client->get($github_tag_url);
        $tags = json_decode((string) $response->getBody(), true);
        if (
            !is_array($tags) ||
            count($tags) === 0 ||
            !array_key_exists('zipball_url', $tags[0])
        ) {
            throw new RuntimeException(
                'Could not retrieve mmCMS latest version. '.
                'Please verify that you are able to access: '.$github_tag_url
            );
        }

        $this->api_zipball_url = $tags[0]['zipball_url'];

        // Downloaded zip file
        $response = $client->get($this->api_zipball_url);
        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

    /**
     * Download the temporary Zip to the given file for the Admin.
     *
     * @param string $zipFile
     *
     * @return self
     */
    protected function downloadAdmin(string $zipFile): self
    {
        $client = new Client();

        // Retrieve latest tag from GitHub's API
        $github_tag_url = 'https://api.github.com/repos/thtg88'.
            '/mmcms-demo-react-admin/tags?per_page=1';
        $response = $client->get($github_tag_url);
        $tags = json_decode((string) $response->getBody(), true);
        if (
            !is_array($tags) ||
            count($tags) === 0 ||
            !array_key_exists('zipball_url', $tags[0])
        ) {
            throw new RuntimeException(
                'Could not retrieve mmCMS latest version. '.
                'Please verify that you are able to access: '.$github_tag_url
            );
        }

        $this->admin_zipball_url = $tags[0]['zipball_url'];

        // Downloaded zip file
        $response = $client->get($this->admin_zipball_url);
        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

    /**
     * Extract the Zip file into the given directory.
     *
     * @param string $zipFile
     * @param string $directory
     *
     * @return self
     */
    protected function extract(string $zipFile, string $directory): self
    {
        $archive = new ZipArchive();

        $response = $archive->open($zipFile, ZipArchive::CHECKCONS);

        if ($response === ZipArchive::ER_NOZIP) {
            throw new RuntimeException(
                'The zip file could not download. '.
                'Verify that you are able to access: '.$this->api_zipball_url.
                ' and '.$this->admin_zipball_url
            );
        }

        $archive->extractTo($directory);
        $archive->close();

        // GitHub provides a whole folder zipped.
        // So we are going to find it and move all it's content up one level
        $found = false;
        $filesystem = new Filesystem();
        $finder = (new Finder())->directories()->in($directory)->depth('== 0');
        foreach ($finder as $_directory) {
            $path_name = $_directory->getPathname();

            if (strpos($path_name, static::$github_owner_name.'-mmcms-')) {
                $filesystem->mirror($path_name, $directory);

                $found = true;

                $filesystem->remove($path_name);

                break;
            }
        }
        if ($found === false) {
            throw new RuntimeException(
                'The zip file could not be extracted. '.
                'Verify that you are able to access: '.$this->api_zipball_url.
                ' and '.$this->admin_zipball_url
            );
        }

        return $this;
    }

    /**
     * Clean-up the Zip file.
     *
     * @param string $zipFile
     *
     * @return self
     */
    protected function cleanUp(string $zipFile): self
    {
        @chmod($zipFile, 0777);

        @unlink($zipFile);

        return $this;
    }

    /**
     * Make sure the storage and bootstrap cache directories are writable.
     *
     * @param string                                            $app_directory
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return self
     */
    protected function prepareWritableApiDirectories(
        string $app_directory,
        OutputInterface $output
    ): self {
        $filesystem = new Filesystem();

        try {
            $filesystem->chmod(
                $app_directory.DIRECTORY_SEPARATOR.'bootstrap/cache',
                0755,
                0000,
                true
            );
            $filesystem->chmod(
                $app_directory.DIRECTORY_SEPARATOR.'storage',
                0755,
                0000,
                true
            );
        } catch (IOExceptionInterface $e) {
            $output->writeln(
                '<comment>You should verify that the "storage" '.
                'and "bootstrap/cache" directories are writable.</comment>'
            );
        }

        return $this;
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer(): string
    {
        $composerPath = $this->api_directory.DIRECTORY_SEPARATOR.
            'composer.phar';

        if (file_exists($composerPath)) {
            return '"'.PHP_BINARY.'" '.$composerPath;
        }

        return 'composer';
    }

    /**
     * Install composer dependencies and run scripts.
     *
     * @param string          $api_directory
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return self
     */
    protected function installApiDependencies(
        string $api_directory,
        InputInterface $input,
        OutputInterface $output
    ): self {
        $composer = $this->findComposer();

        $commands = [
            $composer.' install --no-scripts',
            $composer.' run-script post-root-package-install',
            $composer.' run-script post-create-project-cmd',
            $composer.' run-script post-autoload-dump',
            // TODO: run mmcms:install command?
        ];

        if ($input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                return $value.' --no-ansi';
            }, $commands);
        }

        if ($input->getOption('quiet')) {
            $commands = array_map(function ($value) {
                return $value.' --quiet';
            }, $commands);
        }

        $process = Process::fromShellCommandline(
            implode(' && ', $commands),
            $api_directory,
            null,
            null,
            null
        );

        if (
            '\\' !== DIRECTORY_SEPARATOR &&
            file_exists('/dev/tty') &&
            is_readable('/dev/tty')
        ) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: '.$e->getMessage());
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        if ($process->isSuccessful()) {
            $output->writeln('<comment>API ready!</comment>');
        }

        return $this;
    }

    /**
     * Get the npm command for the environment.
     *
     * @return string
     */
    protected function findNpm(): string
    {
        return 'npm';
    }

    /**
     * Install npm dependencies.
     *
     * @return self
     */
    protected function installAdminDependencies(
        string $admin_directory,
        OutputInterface $output
    ): self {
        $npm = $this->findNpm();

        $process = Process::fromShellCommandline(
            $npm.' install',
            $admin_directory,
            null,
            null,
            null
        );

        if (
            '\\' !== DIRECTORY_SEPARATOR &&
            file_exists('/dev/tty') &&
            is_readable('/dev/tty')
        ) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $output->writeln('Warning: '.$e->getMessage());
            }
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        if ($process->isSuccessful()) {
            $output->writeln('<comment>Admin ready!</comment>');
        }

        return $this;
    }
}
