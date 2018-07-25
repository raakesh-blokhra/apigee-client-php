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

namespace Apigee\Edge\Controller;

use Apigee\Edge\Structure\PagerInterface;

/**
 * Utility methods for those controllers that supports paginated listing.
 *
 * @see \Apigee\Edge\Controller\PaginatedEntityListingControllerInterface
 */
trait PaginationHelperTrait
{
    use EntityListingControllerTrait;

    /**
     * Loads entities from Apigee Edge.
     *
     * @param \Apigee\Edge\Structure\PagerInterface|null $pager
     *   Pager.
     * @param array $query_params
     *   Additional query parameters.
     * @param string $key_provider
     *   Getter method on the entity that should provide a unique array key.
     *
     * @return \Apigee\Edge\Entity\EntityInterface[]
     *   Array of entity objects.
     *
     * @psalm-suppress PossiblyNullArrayOffset $tmp->id() is always not null here.
     */
    private function listEntities(PagerInterface $pager = null, array $query_params = [], string $key_provider = 'id'): array
    {
        $query_params = [
            'expand' => 'true',
        ] + $query_params;

        if ($pager) {
            $responseArray = $this->getResultsInRange($pager, $query_params);
            // Ignore entity type key from response, ex.: developer,
            // apiproduct, etc.
            $responseArray = reset($responseArray);

            return $this->responseArrayToArrayOfEntities($responseArray, $key_provider);
        } else {
            $responseArray = $this->getResultsInRange($this->createPager(), $query_params);
            // Ignore entity type key from response, ex.: developer, apiproduct,
            // etc.
            $responseArray = reset($responseArray);
            $entities = $this->responseArrayToArrayOfEntities($responseArray, $key_provider);
            $lastEntity = end($entities);
            $lastId = $lastEntity->{$key_provider}();
            do {
                $tmp = $this->getResultsInRange($this->createPager(0, $lastId), $query_params);
                // Ignore entity type key from response, ex.: developer,
                // apiproduct, etc.
                $tmp = reset($tmp);
                // Remove the first item from the list because it is the same
                // as the last item of $entities at this moment.
                // Apigee Edge response always starts with the requested entity
                // (startKey).
                array_shift($tmp);
                $tmpEntities = $this->responseArrayToArrayOfEntities($tmp, $key_provider);
                // The returned entity array is keyed by entity id which
                // is unique so we can do this.
                $entities += $tmpEntities;

                if (count($tmpEntities) > 0) {
                    $lastEntity = end($tmpEntities);
                    $lastId = $lastEntity->{$key_provider}();
                } else {
                    $lastId = false;
                }
            } while ($lastId);

            return $entities;
        }
    }

    /**
     * Loads entity ids from Apigee Edge.
     *
     * @param \Apigee\Edge\Structure\PagerInterface|null $pager
     *   Pager.
     * @param array $query_params
     *   Additional query parameters.
     *
     * @return string[]
     *   Array of entity ids.
     */
    private function listEntityIds(PagerInterface $pager = null, array $query_params = []): array
    {
        $query_params = [
            'expand' => 'false',
        ] + $query_params;
        if ($pager) {
            return $this->getResultsInRange($pager, $query_params);
        } else {
            $ids = $this->getResultsInRange($this->createPager(), $query_params);
            $lastId = end($ids);
            do {
                $tmp = $this->getResultsInRange($this->createPager(0, $lastId), $query_params);
                // Remove the first item from the list because it is the same
                // as the current last item of $ids.
                // Apigee Edge response always starts with the requested entity
                // id (startKey).
                array_shift($tmp);
                $ids = array_merge($ids, $tmp);

                if (count($tmp) > 0) {
                    $lastId = end($tmp);
                } else {
                    $lastId = false;
                }
            } while ($lastId);

            return $ids;
        }
    }

    /**
     * Gets entities and entity ids in a provided range from Apigee Edge.
     *
     * @param \Apigee\Edge\Structure\PagerInterface $pager
     *   CPS limit object with configured startKey and limit.
     * @param array $query_params
     *   Query parameters for the API call.
     *
     * @return array
     *   API response parsed as an array.
     */
    private function getResultsInRange(PagerInterface $pager, array $query_params): array
    {
        $query_params['startKey'] = $pager->getStartKey();
        // Do not add 0 unnecessarily to the query parameters.
        if ($pager->getLimit() > 0) {
            $query_params['count'] = $pager->getLimit();
        }
        $uri = $this->getBaseEndpointUri()->withQuery(http_build_query($query_params));
        $response = $this->client->get($uri);

        return $this->responseToArray($response);
    }
}
