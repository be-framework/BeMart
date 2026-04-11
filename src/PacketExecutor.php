<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class PacketExecutor
{
    private const STEP_NAMES = ['semantic', 'generate', 'implement', 'review', 'fix'];

    public function __construct(
        private readonly ProjectPaths $paths,
        private readonly SchemaValidator $validator,
        private readonly PacketRepository $packets
    ) {
    }

    public function run(string $step): int
    {
        if (!in_array($step, self::STEP_NAMES, true)) {
            throw new \RuntimeException(sprintf('Unknown packet step: %s', $step));
        }

        $task = JsonFile::decodeFile($this->requireEnv('ORCH_TASK_FILE'));
        $this->validator->assertValid('task', $task);

        $runState = JsonFile::decodeFile($this->requireEnv('ORCH_RUN_STATE_PATH'));
        $packet = $this->packets->loadPacket((string) $task['packet']);
        $summary = $this->buildSummary($task, $packet);

        $payload = match ($step) {
            'semantic' => $this->semanticPayload($summary),
            'generate' => $this->generatePayload($summary),
            'implement' => $this->implementPayload($summary),
            'review' => $this->reviewPayload($this->requireEnv('ORCH_RUN_DIR'), $runState, $summary),
            'fix' => $this->fixPayload($summary),
        };

        JsonFile::encodeFile($this->requireEnv('ORCH_RUN_DIR') . '/packet/' . $step . '.json', $payload);
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;

        if ($step === 'review' && ($payload['status'] ?? '') === 'needs-fix') {
            return 10;
        }

        return 0;
    }

    private function buildSummary(array $task, array $packet): array
    {
        $alpsPath = $this->resolveSourcePath((string) $packet['source_profile']);
        $alps = JsonFile::decodeFile($alpsPath);
        $descriptors = $alps['alps']['descriptor'] ?? null;
        if (!is_array($descriptors)) {
            throw new \RuntimeException('Invalid ALPS document: descriptor list not found.');
        }

        $state = $this->descriptorById($descriptors, (string) $packet['state_descriptor']);
        $transitions = [];
        foreach ($packet['transition_descriptors'] as $transitionId) {
            $descriptor = $this->descriptorById($descriptors, (string) $transitionId);
            $transitions[] = [
                'id' => (string) $descriptor['id'],
                'title' => (string) ($descriptor['title'] ?? ''),
            ];
        }

        $semanticNotes = [];
        foreach (($packet['semantic_notes'] ?? []) as $name => $definition) {
            $semanticNotes[$name] = $this->resolveSemanticNote($descriptors, (array) $definition);
        }

        return [
            'task_id' => (string) $task['id'],
            'packet_id' => (string) $packet['id'],
            'packet_type' => (string) $packet['type'],
            'bounded_context' => (string) $packet['bounded_context'],
            'resource' => (string) $packet['resource'],
            'alps_profile' => $alpsPath,
            'state' => [
                'id' => (string) $state['id'],
                'title' => (string) ($state['title'] ?? ''),
            ],
            'transitions' => $transitions,
            'semantic_notes' => $semanticNotes,
            'generate_flags' => (array) (($packet['generate']['flags'] ?? [])),
            'implement' => (array) $packet['implement'],
            'review_required_keys' => array_values((array) $packet['review']['required_keys']),
            'fix_message' => (string) $packet['fix']['message'],
            'success_criteria' => array_values((array) $task['success_criteria']),
        ];
    }

    private function semanticPayload(array $summary): array
    {
        return array_merge([
            'mode' => 'semantic',
            'status' => 'ok',
            'packet_type' => $summary['packet_type'],
            'resource' => $summary['resource'],
            'alps_profile' => $summary['alps_profile'],
            'state' => $summary['state'],
            'transitions' => $summary['transitions'],
            'success_criteria' => $summary['success_criteria'],
        ], $summary['semantic_notes']);
    }

    private function generatePayload(array $summary): array
    {
        $implementationBrief = [
            'bounded_context' => $summary['bounded_context'],
            'resource' => $summary['resource'],
            'state_title' => $summary['state']['title'],
            'transition_titles' => array_map(
                static fn (array $transition): string => (string) $transition['title'],
                $summary['transitions']
            ),
        ];

        foreach ($summary['generate_flags'] as $name => $value) {
            $implementationBrief[$name] = $value;
        }

        foreach ($summary['semantic_notes'] as $name => $value) {
            $implementationBrief[$name] = $value;
        }

        return [
            'mode' => 'generate',
            'status' => 'ok',
            'implementation_brief' => $implementationBrief,
        ];
    }

    private function implementPayload(array $summary): array
    {
        return [
            'mode' => 'implement',
            'status' => 'ok',
            'proposed_targets' => $summary['implement']['proposed_targets'],
            'test_targets' => $summary['implement']['test_targets'],
            'contract' => [
                'state_id' => $summary['state']['id'],
                'transition_ids' => array_map(
                    static fn (array $transition): string => (string) $transition['id'],
                    $summary['transitions']
                ),
            ],
        ];
    }

    private function reviewPayload(string $runDir, array $runState, array $summary): array
    {
        $implementEntry = null;
        foreach (array_reverse((array) ($runState['step_history'] ?? [])) as $entry) {
            if (($entry['step'] ?? '') === 'implement' && isset($entry['artifact_ref']) && $entry['artifact_ref'] !== '') {
                $implementEntry = $entry;
                break;
            }
        }

        if ($implementEntry === null) {
            return [
                'mode' => 'review',
                'status' => 'needs-fix',
                'message' => 'No implement artifact found for review.',
            ];
        }

        $artifact = JsonFile::decodeFile($runDir . '/' . $implementEntry['artifact_ref']);
        $stdoutPath = $runDir . '/' . $artifact['stdout_path'];
        $stdout = file_get_contents($stdoutPath);
        if ($stdout === false) {
            throw new \RuntimeException(sprintf('Failed to read implement stdout: %s', $stdoutPath));
        }

        $implementPayload = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($implementPayload)) {
            throw new \RuntimeException('Implement payload is not a JSON object.');
        }

        $missing = [];
        foreach ($summary['review_required_keys'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $implementPayload)) {
                $missing[] = $requiredKey;
            }
        }

        if ($missing !== []) {
            return [
                'mode' => 'review',
                'status' => 'needs-fix',
                'message' => sprintf('Implement payload missing keys: %s', implode(', ', $missing)),
            ];
        }

        return [
            'mode' => 'review',
            'status' => 'approved',
            'resource' => $summary['resource'],
            'checked_keys' => $summary['review_required_keys'],
        ];
    }

    private function fixPayload(array $summary): array
    {
        return [
            'mode' => 'fix',
            'status' => 'ok',
            'fix_applied' => $summary['fix_message'],
            'resource' => $summary['resource'],
        ];
    }

    private function resolveSemanticNote(array $descriptors, array $definition): string
    {
        $source = (string) ($definition['source'] ?? '');

        return match ($source) {
            'descriptor_doc' => (string) (($this->descriptorById($descriptors, (string) ($definition['descriptor'] ?? ''))['doc']['value'] ?? '')),
            'literal' => (string) ($definition['value'] ?? ''),
            default => throw new \RuntimeException(sprintf('Unsupported semantic note source: %s', $source)),
        };
    }

    private function descriptorById(array $descriptors, string $id): array
    {
        foreach ($descriptors as $descriptor) {
            if (($descriptor['id'] ?? null) === $id) {
                return $descriptor;
            }
        }

        throw new \RuntimeException(sprintf('ALPS descriptor not found: %s', $id));
    }

    private function resolveSourcePath(string $relativePath): string
    {
        $resolved = realpath($this->paths->root() . '/' . ltrim($relativePath, '/'));
        if ($resolved === false) {
            throw new \RuntimeException(sprintf('Expected source profile to exist: %s', $relativePath));
        }

        return $resolved;
    }

    private function requireEnv(string $name): string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            throw new \RuntimeException(sprintf('Missing required environment variable: %s', $name));
        }

        return $value;
    }
}
