<?php

/*
 * This file is part of the CS library.
 *
 * Copyright (c) 2015-present LIN3S <info@lin3s.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace LIN3S\CS\Checker;

use LIN3S\CS\Error\Error;
use LIN3S\CS\Exception\JsonParserErrorException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @author Beñat Espiña <benatespina@gmail.com>
 */
final class EsLint implements Checker
{
    use FileFinder;
    use ToolAvailability;

    public static function check(array $files = [], array $parameters = null)
    {
        self::isAvailable('eslint');
        self::file($parameters);

        $excludes = [];
        if (true === array_key_exists('eslint_exclude', $parameters)) {
            foreach ($parameters['eslint_exclude'] as $key => $exclude) {
                $excludes[$key] = $parameters['eslint_path'] . '/' . $exclude;
            }
        }

        $errors = [];
        foreach ($files as $file) {
            if (false === self::exist($file, $parameters['eslint_path'], 'js') || in_array($file, $excludes, true)) {
                continue;
            }

            $process = new Process(
                sprintf('eslint %s -c %s/.eslintrc.js', $file, self::location($parameters)),
                $parameters['root_directory']
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

    public static function file($parameters) : void
    {
        $jsContent = file_get_contents(__DIR__ . '/../.eslintrc.js.dist');

        $arrayContent = self::extractContent($jsContent);
        foreach ($parameters['eslint_rules'] as $ruleType => $rules) {
            if (!is_array($rules)) {
                $arrayContent[$ruleType] = $rules;
                continue;
            }

            if (self::isAssociativeArray($rules)) {
                foreach ($rules as $name => $rule) {
                    $arrayContent[$ruleType][$name] = $rule;
                }
                continue;
            }

            foreach ($rules as $rule) {
                if (in_array($rule, $arrayContent[$ruleType], true)) {
                    continue;
                }
                $arrayContent[$ruleType][] = $rule;
            }
        }

        $location = self::location($parameters) . '/.eslintrc.js';
        $fileSystem = new Filesystem();

        try {
            $fileSystem->remove($location);
            $fileSystem->touch($location);
            file_put_contents($location, self::buildEslintJsFile($arrayContent));
        } catch (\Exception $exception) {
            echo sprintf("Something wrong happens during the creating process: \n%s\n", $exception->getMessage());
        }
    }

    private static function extractContent($jsFileContent) : array
    {
        $position = mb_strpos($jsFileContent, 'module.exports = ');
        $position = $position + 17;
        $json = mb_substr($jsFileContent, $position);
        $json = rtrim(trim($json), ';');

        $result = json_decode($json, true);
        if (null === $result) {
            throw new JsonParserErrorException();
        }

        return $result;
    }

    private static function buildEslintJsFile(array $content) : string
    {
        return sprintf('module.exports = %s;', str_replace('\/', '/', json_encode($content)));
    }

    private static function isAssociativeArray(array $array)
    {
        return [] !== $array && array_keys($array) !== range(0, count($array) - 1);
    }

    private static function location($parameters) : string
    {
        return $parameters['root_directory'] . '/' . $parameters['eslint_file_location'];
    }
}
