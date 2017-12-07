<?php
/**
 * Copyright: IMPER.INFO Adrian Szuszkiewicz
 * Date: 07.12.17
 * Time: 09:23
 */

namespace Imper86\OVHObjectStorage;


interface OVHObjectStorageCredentialsInterface
{
    public function getUsername(): string;

    public function getPassword(): string;

    public function getTenantName(): string;

    public function getRegion(): string;

    public function getSwiftUrl(): string;

    public function getContainerName(): string;
}