<?php

/*
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Apigee\Edge\Entity\Property;

use Apigee\Edge\Structure\PropertiesProperty;

/**
 * Trait PropertiesPropertyAwareTrait.
 */
trait PropertiesPropertyAwareTrait
{
    /** @var \Apigee\Edge\Structure\PropertiesProperty */
    protected $properties;

    /**
     * @inheritdoc
     */
    public function getProperties(): array
    {
        return $this->properties->values();
    }

    /**
     * @inheritdoc
     */
    public function setProperties(PropertiesProperty $properties): void
    {
        $this->properties = $properties;
    }

    /**
     * @inheritdoc
     */
    public function getPropertyValue(string $property): ?string
    {
        return $this->properties->getValue($property);
    }

    /**
     * @inheritdoc
     */
    public function addProperty(string $name, string $value): void
    {
        $this->properties->add($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function hasProperty(string $name): bool
    {
        return $this->properties->has($name);
    }

    /**
     * @inheritdoc
     */
    public function deleteProperty(string $name): void
    {
        $this->properties->delete($name);
    }
}
