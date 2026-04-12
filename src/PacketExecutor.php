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
        $summary = match ((string) $packet['kind']) {
            'resource-contract-packet' => $this->buildResourceContractSummary($task, $packet),
            'be-semantic-packet' => $this->buildBeSemanticSummary($task, $packet),
            default => throw new \RuntimeException(sprintf('Unsupported packet kind: %s', (string) $packet['kind'])),
        };

        $runDir = $this->requireEnv('ORCH_RUN_DIR');
        $payload = match ((string) $packet['kind']) {
            'resource-contract-packet' => $this->resourceContractPayload($step, $summary, $runState, $runDir),
            'be-semantic-packet' => $this->beSemanticPayload($step, $summary, $runState, $runDir),
        };

        JsonFile::encodeFile($runDir . '/packet/' . $step . '.json', $payload);
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;

        if ($step === 'review' && ($payload['status'] ?? '') === 'needs-fix') {
            return 10;
        }

        return 0;
    }

    private function buildResourceContractSummary(array $task, array $packet): array
    {
        [$alpsPath, $descriptors] = $this->loadDescriptors((string) $packet['source_profile']);

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
            $semanticNotes[$name] = $this->resolveValueDefinition($descriptors, (array) $definition);
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

    private function resourceContractPayload(string $step, array $summary, array $runState, string $runDir): array
    {
        return match ($step) {
            'semantic' => array_merge([
                'mode' => 'semantic',
                'status' => 'ok',
                'packet_type' => $summary['packet_type'],
                'resource' => $summary['resource'],
                'alps_profile' => $summary['alps_profile'],
                'state' => $summary['state'],
                'transitions' => $summary['transitions'],
                'success_criteria' => $summary['success_criteria'],
            ], $summary['semantic_notes']),
            'generate' => $this->resourceGeneratePayload($summary),
            'implement' => $this->resourceImplementPayload($summary),
            'review' => $this->reviewPayload($runDir, $runState, $summary),
            'fix' => $this->fixPayload($summary),
        };
    }

    private function buildBeSemanticSummary(array $task, array $packet): array
    {
        [$alpsPath, $descriptors] = $this->loadDescriptors((string) $packet['source_profile']);

        $sourceConstraints = [];
        foreach ($packet['source_constraints'] as $definition) {
            $definition = (array) $definition;
            $sourceConstraints[] = [
                'label' => (string) $definition['label'],
                'value' => $this->resolveValueDefinition($descriptors, $definition),
                'source' => (string) $definition['source'],
            ];
        }

        $semanticVariables = [];
        foreach ($packet['semantic_variables'] as $definition) {
            $definition = (array) $definition;
            $semanticVariables[] = [
                'name' => (string) $definition['name'],
                'type' => (string) $definition['type'],
                'meaning' => (string) $definition['meaning'],
                'constraints' => array_values((array) $definition['constraints']),
                'sources' => array_values((array) $definition['sources']),
            ];
        }

        return [
            'task_id' => (string) $task['id'],
            'packet_id' => (string) $packet['id'],
            'packet_type' => (string) $packet['type'],
            'bounded_context' => (string) $packet['bounded_context'],
            'subject' => (string) $packet['resource'],
            'alps_profile' => $alpsPath,
            'semantic_variables' => $semanticVariables,
            'source_constraints' => $sourceConstraints,
            'input' => (array) $packet['input'],
            'final' => (array) $packet['final'],
            'reason_dependencies' => array_values((array) $packet['reason_dependencies']),
            'be_targets' => array_values((array) $packet['be_targets']),
            'be_test_targets' => array_values((array) $packet['be_test_targets']),
            'generate_flags' => (array) (($packet['generate']['flags'] ?? [])),
            'review_required_keys' => array_values((array) $packet['review']['required_keys']),
            'fix_message' => (string) $packet['fix']['message'],
            'success_criteria' => array_values((array) $task['success_criteria']),
        ];
    }

    private function beSemanticPayload(string $step, array $summary, array $runState, string $runDir): array
    {
        return match ($step) {
            'semantic' => [
                'mode' => 'semantic',
                'status' => 'ok',
                'packet_type' => $summary['packet_type'],
                'subject' => $summary['subject'],
                'alps_profile' => $summary['alps_profile'],
                'semantic_variables' => $summary['semantic_variables'],
                'source_constraints' => $summary['source_constraints'],
                'input' => $summary['input'],
                'final' => $summary['final'],
                'reason_dependencies' => $summary['reason_dependencies'],
                'success_criteria' => $summary['success_criteria'],
            ],
            'generate' => $this->beGeneratePayload($summary),
            'implement' => $this->beImplementPayload($summary),
            'review' => $this->reviewPayload($runDir, $runState, $summary),
            'fix' => [
                'mode' => 'fix',
                'status' => 'ok',
                'fix_applied' => $summary['fix_message'],
                'subject' => $summary['subject'],
            ],
        };
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
            'subject' => $this->summarySubject($summary),
            'checked_keys' => $summary['review_required_keys'],
        ];
    }

    private function fixPayload(array $summary): array
    {
        return [
            'mode' => 'fix',
            'status' => 'ok',
            'fix_applied' => $summary['fix_message'],
            'subject' => $this->summarySubject($summary),
        ];
    }

    private function resourceGeneratePayload(array $summary): array
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

    private function resourceImplementPayload(array $summary): array
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

    private function beGeneratePayload(array $summary): array
    {
        $bePlan = [
            'bounded_context' => $summary['bounded_context'],
            'subject' => $summary['subject'],
            'semantic_variable_names' => array_map(
                static fn (array $variable): string => (string) $variable['name'],
                $summary['semantic_variables']
            ),
            'input' => $summary['input'],
            'final' => $summary['final'],
            'reason_dependencies' => $summary['reason_dependencies'],
        ];

        foreach ($summary['generate_flags'] as $name => $value) {
            $bePlan[$name] = $value;
        }

        return [
            'mode' => 'generate',
            'status' => 'ok',
            'be_plan' => $bePlan,
        ];
    }

    private function beImplementPayload(array $summary): array
    {
        return [
            'mode' => 'implement',
            'status' => 'ok',
            'semantic_variables' => $summary['semantic_variables'],
            'source_constraints' => $summary['source_constraints'],
            'input' => $summary['input'],
            'final' => $summary['final'],
            'reason_dependencies' => $summary['reason_dependencies'],
            'be_targets' => $summary['be_targets'],
            'be_test_targets' => $summary['be_test_targets'],
        ];
    }

    private function resolveValueDefinition(array $descriptors, array $definition): string
    {
        $source = (string) ($definition['source'] ?? '');

        return match ($source) {
            'descriptor_doc' => (string) (($this->descriptorById($descriptors, (string) ($definition['descriptor'] ?? ''))['doc']['value'] ?? '')),
            'descriptor_title' => (string) ($this->descriptorById($descriptors, (string) ($definition['descriptor'] ?? ''))['title'] ?? ''),
            'literal' => (string) ($definition['value'] ?? ''),
            default => throw new \RuntimeException(sprintf('Unsupported semantic note source: %s', $source)),
        };
    }

    private function loadDescriptors(string $sourceProfile): array
    {
        $alpsPath = $this->resolveSourcePath($sourceProfile);
        $alps = JsonFile::decodeFile($alpsPath);
        $descriptors = $alps['alps']['descriptor'] ?? null;
        if (!is_array($descriptors)) {
            throw new \RuntimeException('Invalid ALPS document: descriptor list not found.');
        }

        return [$alpsPath, $descriptors];
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

    private function summarySubject(array $summary): string
    {
        return (string) ($summary['resource'] ?? $summary['subject'] ?? '');
    }
}
