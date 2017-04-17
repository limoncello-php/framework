<?php namespace Limoncello\Application\Packages\Data;

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

use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Contracts\Application\ModelInterface;
use Limoncello\Application\Data\ModelSchemeInfo;
use Limoncello\Application\Traits\SelectClassesTrait;
use Limoncello\Contracts\Data\RelationshipTypes;

/**
 * @package Limoncello\Application
 */
abstract class DataSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_MIGRATIONS_PATH = 0;

    /** Settings key */
    const KEY_SEEDS_PATH = self::KEY_MIGRATIONS_PATH + 1;

    /** Settings key */
    const KEY_SEED_INIT = self::KEY_SEEDS_PATH + 1;

    /** Settings key */
    const KEY_MODELS_SCHEME_INFO = self::KEY_SEED_INIT + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_MODELS_SCHEME_INFO + 1;

    use SelectClassesTrait;

    /**
     * @return string
     */
    abstract protected function getModelsPath(): string;

    /**
     * @return string
     */
    abstract protected function getMigrationsPath(): string;

    /**
     * @return string
     */
    abstract protected function getSeedsPath(): string;

    /**
     * @return array
     */
    abstract protected function getSeedInit(): array;

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            static::KEY_MIGRATIONS_PATH    => $this->getMigrationsPath(),
            static::KEY_SEEDS_PATH         => $this->getSeedsPath(),
            static::KEY_MODELS_SCHEME_INFO => $this->getModelsSchemeInfo($this->getModelsPath()),
            static::KEY_SEED_INIT          => $this->getSeedInit(),
        ];
    }

    /**
     * @param string $modelsPath
     *
     * @return array
     */
    private function getModelsSchemeInfo(string $modelsPath): array
    {
        // check reverse relationships
        $requireReverseRel = true;

        $registered    = [];
        $modelSchemes  = new ModelSchemeInfo();
        $registerModel = function ($modelClass) use ($modelSchemes, &$registered, $requireReverseRel) {
            /** @var ModelInterface $modelClass */
            $modelSchemes->registerClass(
                $modelClass,
                $modelClass::getTableName(),
                $modelClass::getPrimaryKeyName(),
                $modelClass::getAttributeTypes(),
                $modelClass::getAttributeLengths()
            );

            $relationships = $modelClass::getRelationships();

            if (array_key_exists(RelationshipTypes::BELONGS_TO, $relationships) === true) {
                foreach ($relationships[RelationshipTypes::BELONGS_TO] as $relName => list($rClass, $fKey, $rRel)) {
                    /** @var string $rClass */
                    $modelSchemes->registerBelongsToOneRelationship($modelClass, $relName, $fKey, $rClass, $rRel);
                    $registered[(string)$modelClass][$relName] = true;
                    $registered[$rClass][$rRel]                = true;

                    // Sanity check. Every `belongs_to` should be paired with `has_many` on the other side.
                    /** @var ModelInterface $rClass */
                    $rRelationships   = $rClass::getRelationships();
                    $isRelationshipOk = $requireReverseRel === false ||
                        (isset($rRelationships[RelationshipTypes::HAS_MANY][$rRel]) === true &&
                            $rRelationships[RelationshipTypes::HAS_MANY][$rRel] === [$modelClass, $fKey, $relName]);
                    /** @var string $modelClass */

                    assert($isRelationshipOk, "`belongsTo` relationship `$relName` of class $modelClass " .
                        "should be paired with `hasMany` relationship.");
                }
            }

            if (array_key_exists(RelationshipTypes::HAS_MANY, $relationships) === true) {
                foreach ($relationships[RelationshipTypes::HAS_MANY] as $relName => list($rClass, $fKey, $rRel)) {
                    // Sanity check. Every `has_many` should be paired with `belongs_to` on the other side.
                    /** @var ModelInterface $rClass */
                    $rRelationships   = $rClass::getRelationships();
                    $isRelationshipOk = $requireReverseRel === false ||
                        (isset($rRelationships[RelationshipTypes::BELONGS_TO][$rRel]) === true &&
                            $rRelationships[RelationshipTypes::BELONGS_TO][$rRel] === [$modelClass, $fKey, $relName]);
                    /** @var string $modelClass */
                    assert($isRelationshipOk, "`hasMany` relationship `$relName` of class $modelClass " .
                        "should be paired with `belongsTo` relationship.");
                }
            }

            if (array_key_exists(RelationshipTypes::BELONGS_TO_MANY, $relationships) === true) {
                foreach ($relationships[RelationshipTypes::BELONGS_TO_MANY] as $relName => $data) {
                    if (isset($registered[(string)$modelClass][$relName]) === true) {
                        continue;
                    }
                    /** @var string $rClass */
                    list($rClass, $iTable, $fKeyPrimary, $fKeySecondary, $rRel) = $data;
                    $modelSchemes->registerBelongsToManyRelationship(
                        $modelClass,
                        $relName,
                        $iTable,
                        $fKeyPrimary,
                        $fKeySecondary,
                        $rClass,
                        $rRel
                    );
                    $registered[(string)$modelClass][$relName] = true;
                    $registered[$rClass][$rRel]                = true;
                }
            }
        };

        $modelClasses = iterator_to_array($this->selectClasses($modelsPath, ModelInterface::class));
        array_map($registerModel, $modelClasses);

        $data = $modelSchemes->getData();

        return $data;
    }
}
