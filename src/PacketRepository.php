<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class PacketRepository
{
    public function __construct(
        private readonly ProjectPaths $paths,
        private readonly SchemaValidator $validator
    ) {
    }

    public function loadPacket(string $packetId): array
    {
        $packet = JsonFile::decodeFile($this->packetPath($packetId));
        $this->validator->assertValid('packet', $packet);
        return $packet;
    }

    public function packetPath(string $packetId): string
    {
        return $this->paths->packetDir() . '/' . $packetId . '.json';
    }
}

