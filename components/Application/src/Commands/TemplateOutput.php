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

/**
 * @package Limoncello\Application
 */
class TemplateOutput
{
    /**
     * @var string
     */
    private $outputRootFolder;

    /**
     * @var string
     */
    private $outputSubFolder;

    /**
     * @var string
     */
    private $outputFileName;

    /**
     * @var string
     */
    private $outputContent;

    /**
     * @var string|null
     */
    private $outputFolder = null;

    /**
     * @var string|null
     */
    private $outputPath = null;

    /**
     * @param string $outputRootFolder
     * @param string $outputFileName
     * @param string $outputContent
     * @param string $outputSubFolder
     */
    public function __construct(
        string $outputRootFolder,
        string $outputFileName,
        string $outputContent,
        string $outputSubFolder = ''
    ) {
        $this
            ->setOutputRootFolder($outputRootFolder)
            ->setOutputSubFolder($outputSubFolder)
            ->setOutputFileName($outputFileName)
            ->setOutputContent($outputContent);
    }


    /**
     * @return string
     */
    public function getOutputRootFolder(): string
    {
        return $this->outputRootFolder;
    }

    /**
     * @param string $outputRootFolder
     *
     * @return self
     */
    public function setOutputRootFolder(string $outputRootFolder): self
    {
        assert(empty($outputRootFolder) === false);

        $this->outputRootFolder = $outputRootFolder;

        $this->reset();

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputSubFolder(): string
    {
        return $this->outputSubFolder;
    }

    /**
     * @param string $outputSubFolder
     *
     * @return self
     */
    public function setOutputSubFolder(string $outputSubFolder): self
    {
        $this->outputSubFolder = $outputSubFolder;

        $this->reset();

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputFileName(): string
    {
        return $this->outputFileName;
    }

    /**
     * @param string $outputFileName
     *
     * @return self
     */
    public function setOutputFileName(string $outputFileName): self
    {
        assert(empty($outputFileName) === false);

        $this->outputFileName = $outputFileName;

        $this->reset();

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputContent(): string
    {
        return $this->outputContent;
    }

    /**
     * @param string $outputContent
     *
     * @return self
     */
    public function setOutputContent(string $outputContent): self
    {
        $this->outputContent = $outputContent;

        return $this;
    }

    /**
     * @return string
     */
    public function getOutputFolder(): string
    {
        if ($this->outputFolder === null) {
            $folder = $this->getOutputRootFolder();

            if (empty($this->getOutputSubFolder()) === false) {
                if ($folder[-1] !== DIRECTORY_SEPARATOR) {
                    $folder .= DIRECTORY_SEPARATOR;
                }
                $folder .= $this->getOutputSubFolder();
            }

            $this->outputFolder = $folder;
        }

        return $this->outputFolder;
    }

    /**
     * @return string
     */
    public function getOutputPath(): string
    {
        if ($this->outputPath === null) {
            $folder = $this->getOutputFolder();
            if ($folder[-1] !== DIRECTORY_SEPARATOR) {
                $folder .= DIRECTORY_SEPARATOR;
            }

            $this->outputPath = $folder . $this->getOutputFileName();
        }

        return $this->outputPath;
    }

    /**
     * @return void
     */
    private function reset(): void
    {
        $this->outputFolder = null;
        $this->outputPath   = null;
    }
}
