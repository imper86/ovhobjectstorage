<?php
/**
 * Copyright: IMPER.INFO Adrian Szuszkiewicz
 * Date: 07.12.17
 * Time: 09:27
 */

namespace Imper86\OVHObjectStorage;


use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Stream;
use OpenStack\Identity\v2\Service as IdentityService;
use OpenStack\ObjectStore\v1\Models\Container;
use OpenStack\ObjectStore\v1\Models\Object;
use OpenStack\ObjectStore\v1\Service as ObjectService;
use OpenStack\OpenStack;

class OVHObjectStorageClient
{
    const AUTH_URL = 'https://auth.cloud.ovh.net/v2.0/';

    /**
     * @var OVHObjectStorageCredentialsInterface
     */
    private $credentials;
    /**
     * @var OpenStack
     */
    private $openStackClient;

    public function __construct(OVHObjectStorageCredentialsInterface $credentials)
    {
        $this->credentials = $credentials;
    }

    public function getOpenStackClient(): OpenStack
    {
        if (null === $this->openStackClient) {
            $httpClient = new Client([
                'base_uri' => self::AUTH_URL,
                'handler' => HandlerStack::create(),
            ]);

            $identityService = IdentityService::factory($httpClient);

            $this->openStackClient = new OpenStack([
                'authUrl' => self::AUTH_URL,
                'region' => $this->credentials->getRegion(),
                'username' => $this->credentials->getUsername(),
                'password' => $this->credentials->getPassword(),
                'tenantName' => $this->credentials->getTenantName(),
                'identityService' => $identityService,
            ]);
        }

        return $this->openStackClient;
    }

    public function getObjectService(): ObjectService
    {
        return $this->getOpenStackClient()->objectStoreV1();
    }

    public function getContainer(): Container
    {
        return $this->getObjectService()->getContainer($this->credentials->getContainerName());
    }

    /**
     * @param string $cloudPath
     * @return Object
     */
    public function getObject(string $cloudPath): Object
    {
        return $this->getContainer()->getObject($cloudPath);
    }

    public function uploadFromPath(string $filePath, string $cloudPath, bool $isLarge = false): Object
    {
        $stream = new Stream(fopen($filePath, 'r'));
        $options = [
            'name' => $cloudPath,
            'stream' => $stream,
        ];

        if (!$isLarge) {
            $object = $this->getContainer()->createObject($options);
        } else {
            $object = $this->getContainer()->createLargeObject($options);
        }

        return $object;
    }

    public function uploadFromString(string $content, string $cloudPath): Object
    {
        $object = $this->getContainer()->createObject([
            'name' => $cloudPath,
            'content' => $content,
        ]);

        return $object;
    }

    public function download(string $cloudPath): Stream
    {
        $stream = $this->getObject($cloudPath)->download();

        return $stream;
    }

    public function delete(string $cloudPath): void
    {
        $object = $this->getObject($cloudPath);
        $object->delete();
    }
}