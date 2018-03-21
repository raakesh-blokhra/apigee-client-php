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

namespace Apigee\Edge\Tests\HttpClient\Utility;

use Apigee\Edge\HttpClient\Utility\Builder;
use GuzzleHttp\Psr7\Request;
use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\CachePlugin;
use Http\Discovery\UriFactoryDiscovery;
use Http\Message\StreamFactory;
use Http\Mock\Client as MockClient;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class BuilderTest.
 *
 *
 * @group client
 * @group mock
 * @group offline
 * @small
 */
class BuilderTest extends TestCase
{
    /** @var \Http\Mock\Client */
    protected static $httpClient;

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void
    {
        // Use the Mock HTTP Client for all requests.
        self::$httpClient = new MockClient();
        parent::setUpBeforeClass();
    }

    public function testShouldReturnTheSameInstance(): void
    {
        $builder = new Builder(self::$httpClient);
        $client = $builder->getHttpClient();
        $this->assertEquals($client, $builder->getHttpClient());
    }

    public function testShouldReturnANewInstance(): void
    {
        $builder = new Builder();
        $this->assertNotNull($builder->getHttpClient());
    }

    public function testShouldSetHeaders()
    {
        $headers = ['Foo' => 'bar', 'Bar' => 'baz'];
        $builder = new Builder(self::$httpClient);
        $client = $builder->getHttpClient();
        $builder->setHeaders($headers);
        $this->assertNotEquals($client, $builder->getHttpClient());
        $builder->getHttpClient()->sendRequest(new Request('GET', 'http://example.com'));
        $sent_request = self::$httpClient->getLastRequest();
        $this->assertEquals($sent_request->getHeaderLine('Foo'), $headers['Foo']);

        return $builder;
    }

    /**
     * @depends testShouldSetHeaders
     *
     * @param \Apigee\Edge\HttpClient\Utility\Builder $builder
     */
    public function testShouldSetHeaderValue(Builder $builder): void
    {
        $client = $builder->getHttpClient();
        $builder->setHeaderValue('Foo', 'baz');
        $builder->setHeaderValue('Apigee', 'Edge');
        $this->assertNotEquals($client, $builder->getHttpClient());
        $request = new Request('GET', 'http://example.com');
        $builder->getHttpClient()->sendRequest($request);
        $sent_request = self::$httpClient->getLastRequest();
        $this->assertEquals('bar, baz', $sent_request->getHeaderLine('Foo'));
        $this->assertEquals('Edge', $sent_request->getHeaderLine('Apigee'));
    }

    /**
     * @depends testShouldSetHeaders
     *
     * @param \Apigee\Edge\HttpClient\Utility\Builder $builder
     */
    public function testShouldRemoveHeader(Builder $builder): void
    {
        $client = $builder->getHttpClient();
        $builder->removeHeader('Foo');
        $this->assertNotEquals($client, $builder->getHttpClient());
        $request = new Request('GET', 'http://example.com');
        $builder->getHttpClient()->sendRequest($request);
        $sent_request = self::$httpClient->getLastRequest();
        $this->assertArrayNotHasKey('Foo', $sent_request->getHeaders());
    }

    /**
     * @depends testShouldSetHeaders
     *
     * @param \Apigee\Edge\HttpClient\Utility\Builder $builder
     */
    public function testShouldRemoveAllHeaders(Builder $builder): void
    {
        $client = $builder->getHttpClient();
        $builder->clearHeaders();
        $this->assertNotEquals($client, $builder->getHttpClient());
        $request = new Request('GET', 'http://example.com');
        $builder->getHttpClient()->sendRequest($request);
        $sent_request = self::$httpClient->getLastRequest();
        $this->assertArrayNotHasKey('Foo', $sent_request->getHeaders());
        $this->assertArrayNotHasKey('Bar', $sent_request->getHeaders());
        $this->assertArrayNotHasKey('Apigee', $sent_request->getHeaders());
    }

    public function testShouldAddPlugin()
    {
        $builder = new Builder(self::$httpClient);
        $client = $builder->getHttpClient();
        $uriFactory = UriFactoryDiscovery::find();
        $addPathPlugin = new Plugin\AddPathPlugin($uriFactory->createUri('edge'));
        $builder->addPlugin($addPathPlugin);
        $this->assertNotEquals($client, $builder->getHttpClient());
        $request = new Request('GET', 'http://example.com');
        $builder->getHttpClient()->sendRequest($request);
        $sent_request = self::$httpClient->getLastRequest();
        $this->assertEquals('/edge', $sent_request->getUri()->getPath());

        return $builder;
    }

    /**
     * @depends testShouldAddPlugin
     *
     * @param \Apigee\Edge\HttpClient\Utility\Builder $builder
     */
    public function testShouldRemovePlugin(Builder $builder): void
    {
        $client = $builder->getHttpClient();
        $builder->removePlugin(Plugin\AddPathPlugin::class);
        $this->assertNotEquals($client, $builder->getHttpClient());
        $request = new Request('GET', 'http://example.com');
        $builder->getHttpClient()->sendRequest($request);
        $sent_request = self::$httpClient->getLastRequest();
        $this->assertEmpty($sent_request->getUri()->getPath());
    }

    public function testShouldRemoveAllPlugins(): void
    {
        $builder = new Builder(self::$httpClient);
        $uriFactory = UriFactoryDiscovery::find();
        $addPathPlugin = new Plugin\AddPathPlugin($uriFactory->createUri('edge'));
        $builder->addPlugin($addPathPlugin);
        $headers = ['Foo' => 'bar'];
        $builder->setHeaders($headers);
        $client = $builder->getHttpClient();
        $builder->getHttpClient()->sendRequest(new Request('GET', 'http://example.com'));
        $sent_request = self::$httpClient->getLastRequest();
        $this->assertEquals($sent_request->getHeaderLine('Foo'), $headers['Foo']);
        $this->assertEquals('/edge', $sent_request->getUri()->getPath());
        $builder->clearPlugins();
        $builder->getHttpClient()->sendRequest(new Request('GET', 'http://example.com'));
        $sent_request = self::$httpClient->getLastRequest();
        $this->assertNotEquals($client, $builder->getHttpClient());
        $this->assertEmpty($sent_request->getHeaderLine('Foo'));
        $this->assertEmpty($sent_request->getUri()->getPath());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testShouldNotAddCachePlugin(): void
    {
        $cachePoolMock = $this->createMock(CacheItemPoolInterface::class);
        $streamFactoryMock = $this->createMock(StreamFactory::class);
        $builder = new Builder();
        $builder->addPlugin(new CachePlugin($cachePoolMock, $streamFactoryMock));
    }

    public function testShouldAddCachePlugin()
    {
        $cachePoolMock = $this->createMock(CacheItemPoolInterface::class);
        $builder = new Builder();
        $client = $builder->getHttpClient();
        $builder->addCache($cachePoolMock);
        $this->assertNotEquals($client, $builder->getHttpClient());

        return $builder;
    }

    /**
     * @depends testShouldAddCachePlugin
     */
    public function testShouldRemoveCachePlugin(Builder $builder): void
    {
        $client = $builder->getHttpClient();
        $builder->removeCache();
        $this->assertNotEquals($client, $builder->getHttpClient());
    }
}
