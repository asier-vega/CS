<?php

/*
 * This file is part of the CS library.
 *
 * Copyright (c) 2015 LIN3S <info@lin3s.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LIN3S\CS;

use LIN3S\CS\Checker\Composer;
use LIN3S\CS\Checker\PhpCsFixer;
use LIN3S\CS\Checker\PhpFormatter;
use LIN3S\CS\Checker\Phpmd;
use LIN3S\CS\Exception\CheckFailException;
use LIN3S\CS\Git\Git;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Application extends BaseApplication
{
    /**
     * The input.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * The name of application.
     *
     * @var string
     */
    protected $name;

    /**
     * The output.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * Array which contains the different parameters defined inside the .lin3s_cs.yml
     *
     * @var array
     */
    protected $parameters;

    /**
     * Constructor.
     *
     * @param string|null $name          The name
     * @param string|null $version       The version
     * @param string|null $rootDirectory The root directory
     */
    public function __construct($name = null, $version = null, $rootDirectory = null)
    {
        if (null === $this->name = $name) {
            $this->name = 'LIN3S CS';
        }
        if (null === $version) {
            $version = '0.0.1';
        }
        parent::__construct($name, $version);

        $rootDirectory = $rootDirectory ?: realpath(__DIR__ . '/../../../../');
        $this->parameters = Yaml::parse(file_get_contents($rootDirectory . '/.lin3s_cs.yml'))['parameters'];
        $this->parameters['root_directory'] = $rootDirectory;
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $output->writeln(sprintf('<fg=white;options=bold;bg=red>%s</fg=white;options=bold;bg=red>', $this->name));
        $output->writeln('<info>Fetching files...</info>');
        $files = Git::committedFiles();

        $output->writeln('<info>Check composer</info>');
        Composer::check($files);

        $output->writeln('<info>Checking uses and license headers with PHP-formatter</info>');
        PHPFormatter::check([], $this->parameters);

        $output->writeln('<info>Fixing PHP code style with PHP-CS-Fixer</info>');
        PhpCsFixer::check([], $this->parameters);

        $output->writeln('<info>Checking code mess with PHPMD</info>');
        $phpmdResult = Phpmd::check($files, $this->parameters);
        if (count($phpmdResult) > 0) {
            foreach ($phpmdResult as $error) {
                $output->writeln($error->output());
            }
            throw new CheckFailException('PHPMD');
        }

        Git::addFiles($files, $this->parameters['root_directory']);
        $output->writeln('<info>Nice commit man!</info>');
    }
}