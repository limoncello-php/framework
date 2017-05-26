<?php namespace Limoncello\Application\Commands;

/**
 * Copyright 2015-2017 info@neomerx.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Limoncello\Application\Data\FileMigrationRunner;
use Limoncello\Application\Data\FileSeedRunner;
use Limoncello\Application\Packages\Data\DataSettings;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Application
 */
class DataCommand implements CommandInterface
{
    /**
     * Command name.
     */
    const NAME = 'l:db';

    /** Argument name */
    const ARG_ACTION = 'action';

    /** Command action */
    const ACTION_MIGRATE = 'migrate';

    /** Command action */
    const ACTION_SEED = 'seed';

    /** Command action */
    const ACTION_ROLLBACK = 'rollback';

    /** Option name */
    const OPT_PATH = 'path';

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return static::NAME;
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return 'Migrates and seeds application data.';
    }

    /**
     * @inheritdoc
     */
    public static function getHelp(): string
    {
        return 'This command migrates, seeds and resets application data.';
    }

    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        $migrate  = static::ACTION_MIGRATE;
        $seed     = static::ACTION_SEED;
        $rollback = static::ACTION_ROLLBACK;

        return [
            [
                static::ARGUMENT_NAME        => static::ARG_ACTION,
                static::ARGUMENT_DESCRIPTION => "Action such as `$migrate`, `$seed` or `$rollback` data.",
                static::ARGUMENT_MODE        => static::ARGUMENT_MODE__REQUIRED,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getOptions(): array
    {
        return [
            [
                static::OPTION_NAME        => static::OPT_PATH,
                static::OPTION_DESCRIPTION => 'Path to a list of migrations or seeds. ' .
                    'If not given a path from settings will be used.',
                static::OPTION_SHORTCUT    => 'i',
                static::OPTION_MODE        => static::OPTION_MODE__REQUIRED,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function execute(ContainerInterface $container, IoInterface $inOut)
    {
        $arguments = $inOut->getArguments();
        $options   = $inOut->getOptions();

        /** @var SettingsProviderInterface $provider */
        $provider = $container->get(SettingsProviderInterface::class);
        $settings = $provider->get(DataSettings::class);

        $path   = $options[static::OPT_PATH] ?? false;
        $action = $arguments[static::ARG_ACTION];
        switch ($action) {
            case static::ACTION_MIGRATE:
                $path = $path !== false ? $path : $settings[DataSettings::KEY_MIGRATIONS_PATH] ?? '';
                (new FileMigrationRunner($path))->migrate($container);
                break;
            case static::ACTION_ROLLBACK:
                $path = $path !== false ? $path : $settings[DataSettings::KEY_MIGRATIONS_PATH] ?? '';
                (new FileMigrationRunner($path))->rollback($container);
                break;
            case static::ACTION_SEED:
                $path     = $path !== false ? $path : $settings[DataSettings::KEY_SEEDS_PATH] ?? '';
                $seedInit = $settings[DataSettings::KEY_SEED_INIT] ?? null;
                (new FileSeedRunner($path, $seedInit))->run($container);
                break;
            default:
                $inOut->writeError("Unsupported action `$action`." . PHP_EOL);
                break;
        }
    }
}
