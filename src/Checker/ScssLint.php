<?php

/*
 * This file is part of the CS library.
 *
 * Copyright (c) 2015 LIN3S <info@lin3s.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LIN3S\CS\Checker;

use LIN3S\CS\Error\Error;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Checker that automatizes all the logic about Scss Lint.
 *
 * @author Beñat Espiña <benatespina@gmail.com>
 */
final class ScssLint extends Checker
{
    /**
     * {@inheritdoc}
     */
    public static function check(array $files = [], array $parameters = null)
    {
        $scssLintYamlFile = array_replace_recursive(
            Yaml::parse(file_get_contents(__DIR__ . '/../.scss_lint.yml.dist')),
            $parameters['scsslint_rules']
        );
        file_put_contents(__DIR__ . '/../.scss_lint.yml', Yaml::dump($scssLintYamlFile));

        $excludes = [];
        if (true === array_key_exists('scsslint_exclude', $parameters)) {
            foreach ($parameters['scsslint_exclude'] as $key => $exclude) {
                $excludes[$key] = $parameters['scsslint_path'] . '/' . $exclude;
            }
        }

        $errors = [];
        foreach ($files as $file) {
            if (false === self::exist($file, $parameters['scsslint_path'], 'scss') || in_array($file, $excludes)) {
                continue;
            }

            $process = new Process(
                sprintf('scss-lint %s -c vendor/lin3s/cs/src/.scss_lint.yml', $file), $parameters['root_directory']
            );
            $process->run();
            if (!$process->isSuccessful()) {
                $errors[] = new Error(
                    $file,
                    sprintf('<error>%s</error>', trim($process->getErrorOutput())),
                    sprintf('<error>%s</error>', trim($process->getOutput()))
                );
            }
        }

        return $errors;
    }
}
