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
        $this->assertKindSpecificShape($packet);
        return $packet;
    }

    public function packetPath(string $packetId): string
    {
        return $this->paths->packetDir() . '/' . $packetId . '.json';
    }

    private function assertKindSpecificShape(array $packet): void
    {
        $kind = (string) ($packet['kind'] ?? '');

        match ($kind) {
            'resource-contract-packet' => $this->assertResourceContractPacket($packet),
            'be-semantic-packet' => $this->assertBeSemanticPacket($packet),
            default => throw new \RuntimeException(sprintf('Unsupported packet kind: %s', $kind)),
        };
    }

    private function assertResourceContractPacket(array $packet): void
    {
        foreach (['state_descriptor', 'transition_descriptors', 'implement'] as $key) {
            if (!array_key_exists($key, $packet)) {
                throw new \RuntimeException(sprintf('Resource-contract packet missing required key: %s', $key));
            }
        }

        if (!isset($packet['implement']['proposed_targets']) || !is_array($packet['implement']['proposed_targets'])) {
            throw new \RuntimeException('Resource-contract packet requires implement.proposed_targets.');
        }

        if (!isset($packet['implement']['test_targets']) || !is_array($packet['implement']['test_targets'])) {
            throw new \RuntimeException('Resource-contract packet requires implement.test_targets.');
        }
    }

    private function assertBeSemanticPacket(array $packet): void
    {
        foreach (['semantic_variables', 'source_constraints', 'input', 'final', 'reason_dependencies', 'be_targets', 'be_test_targets'] as $key) {
            if (!array_key_exists($key, $packet)) {
                throw new \RuntimeException(sprintf('Be-semantic packet missing required key: %s', $key));
            }
        }
    }
}
