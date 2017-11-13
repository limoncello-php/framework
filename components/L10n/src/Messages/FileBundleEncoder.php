<?php namespace Limoncello\l10n\Messages;

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
use Limoncello\Contracts\L10n\MessageStorageInterface;
use Limoncello\l10n\Contracts\Messages\ResourceBundleInterface;

/**
 * @package Limoncello\l10n
 */
class FileBundleEncoder extends BundleEncoder
{
    /**
     * @var string
     */
    private $globMessagePatterns;

    /**
     * @param iterable|null $messageDescriptions
     * @param string        $localesDir
     * @param string        $globMessagePatterns
     */
    public function __construct(
        ?iterable $messageDescriptions,
        string $localesDir,
        $globMessagePatterns = '*.php'
    ) {
        $this
            ->setGlobMessagePatterns($globMessagePatterns)
            ->loadDescriptions($messageDescriptions)
            ->loadBundles($localesDir);
    }

    /**
     * Method is used for loading resources from packages.
     *
     * @see ProvidesMessageResourcesInterface
     *
     * @param iterable|null $messageDescriptions
     *
     * @return FileBundleEncoder
     */
    protected function loadDescriptions(?iterable $messageDescriptions): self
    {
        if ($messageDescriptions !== null) {
            foreach ($messageDescriptions as list($locale, $namespace, $messageStorage)) {
                assert(is_string($locale) === true && empty($locale) === false);
                assert(is_string($namespace) === true && empty($namespace) === false);
                assert(is_string($messageStorage) === true && empty($messageStorage) === false);
                assert(class_exists($messageStorage) === true);
                assert(in_array(MessageStorageInterface::class, class_implements($messageStorage)) === true);

                /** @var MessageStorageInterface $messageStorage */

                $properties = $messageStorage::getMessages();
                $bundle     = new ResourceBundle($locale, $namespace, $properties);
                $this->addBundle($bundle);
            }
        }

        return $this;
    }

    /**
     * @param string $localesDir
     *
     * @return self
     */
    protected function loadBundles(string $localesDir): self
    {
        assert(is_string($localesDir) === true && empty($localesDir) === false);

        $localesDir = realpath($localesDir);
        assert($localesDir !== false);

        foreach (scandir($localesDir) as $fileOrDir) {
            if ($fileOrDir !== '.' && $fileOrDir !== '..' &&
                is_dir($localeDirFullPath = $localesDir . DIRECTORY_SEPARATOR . $fileOrDir . DIRECTORY_SEPARATOR)
            ) {
                $localeDir = $fileOrDir;
                foreach (glob($localeDirFullPath . $this->getGlobMessagePatterns()) as $messageFile) {
                    $namespace = pathinfo($messageFile, PATHINFO_FILENAME);
                    $bundle    = $this->loadBundleFromFile($messageFile, $localeDir, $namespace);
                    $this->addBundle($bundle);
                }
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function getGlobMessagePatterns(): string
    {
        return $this->globMessagePatterns;
    }

    /**
     * @param string $globMessagePatterns
     *
     * @return self
     */
    protected function setGlobMessagePatterns(string $globMessagePatterns): self
    {
        assert(is_string($globMessagePatterns) === true && empty($globMessagePatterns) === false);
        $this->globMessagePatterns = $globMessagePatterns;

        return $this;
    }

    /**
     * @param string $fileFullPath
     * @param string $localeDir
     * @param string $messageFile
     *
     * @return ResourceBundleInterface
     */
    protected function loadBundleFromFile(
        string $fileFullPath,
        string $localeDir,
        string $messageFile
    ): ResourceBundleInterface {
        /** @noinspection PhpIncludeInspection */
        $properties = require $fileFullPath;
        $bundle     = new ResourceBundle($localeDir, $messageFile, $properties);

        return $bundle;
    }
}
