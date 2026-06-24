<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use PHPUnit\Framework\TestCase;

use function basename;
use function file_get_contents;
use function glob;
use function in_array;
use function implode;
use function is_array;
use function json_decode;
use function sort;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Regression guard against the response-schema "opaque-bag" anti-pattern.
 *
 * A business-data projection typed as ["array","null","object"] with bare
 * `items: {type:"string"}` fixes ZERO business fields — it is an object
 * masquerading as a string array, so any shape passes response-schema
 * validation. That is exactly how a logged-in member's missing name shipped as
 * "- - 様" on /shopping/confirm (the `customer` node), and the same bug class
 * lived on the admin order / order-edit / category / delivery / payment /
 * product detail records. Those are now typed contracts.
 *
 * Response-schema validation IS enforced at runtime in every context
 * (JsonSchemaModule is bound in AppModule; a non-conforming body 500s the
 * page), so a STRONG contract guards every response automatically — but only
 * if the contract is actually specified. This test keeps it specified: it scans
 * var/json_schema/ for the bug-class shape and demands every match be an
 * ACKNOWLEDGED render-only node in
 * docs/eccube-spec-coverage/schema-render-only-baseline.json.
 *
 * A `form` / `searchForm` node legitimately keeps the loose shape: body['form']
 * is a Ray\WebFormModule AbstractForm OBJECT exposed only for HTML
 * <input>/<select> rendering and ignored by JSON contexts — it does not
 * serialize to clean business data (typing it 500s the page). Those, and only
 * those, belong in the baseline.
 *
 * The match is EXACT in both directions: a NEW bug-class node fails (type it as
 * a real contract, or — if it is a render-only form object — add it to the
 * baseline); a baseline entry that was hardened away fails (remove it).
 */
final class SchemaContractQualityTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';
    private const SCHEMA_DIR = self::ROOT . '/var/json_schema';
    private const BASELINE_PATH = self::ROOT . '/docs/eccube-spec-coverage/schema-render-only-baseline.json';

    public function testNoOpaqueBusinessDataBagOutsideTheRenderOnlyBaseline(): void
    {
        $found = $this->opaqueBagNodes();
        $baseline = $this->baseline();

        $newBags = [];
        foreach ($found as $key) {
            if (! in_array($key, $baseline, true)) {
                $newBags[] = $key;
            }
        }

        $healed = [];
        foreach ($baseline as $key) {
            if (! in_array($key, $found, true)) {
                $healed[] = $key;
            }
        }

        sort($newBags);
        sort($healed);

        self::assertSame(
            [],
            $newBags,
            "New opaque-bag node(s) — a business-data projection typed as an object|array string-bag. "
            . "Type it as a real contract (named properties, required, correct types — see get-shopping-confirm.json `customer`), "
            . "or, if it is a render-only Ray\\WebFormModule form object, add it to "
            . "docs/eccube-spec-coverage/schema-render-only-baseline.json:\n  - " . ($newBags === [] ? '' : implode("\n  - ", $newBags)),
        );

        self::assertSame(
            [],
            $healed,
            "Baseline entr(y/ies) no longer match the opaque-bag shape (they were hardened into a real contract). "
            . "Remove them from docs/eccube-spec-coverage/schema-render-only-baseline.json:\n  - "
            . ($healed === [] ? '' : implode("\n  - ", $healed)),
        );
    }

    /**
     * Every "file.json:property" whose node is the bug-class shape:
     * type is a list containing BOTH "object" and "array", with bare-string items.
     *
     * @return list<string>
     */
    private function opaqueBagNodes(): array
    {
        $hits = [];
        foreach ((array) glob(self::SCHEMA_DIR . '/*.json') as $file) {
            $json = file_get_contents((string) $file);
            if ($json === false) {
                continue;
            }

            /** @var array{properties?: array<string, mixed>} $schema */
            $schema = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $properties = $schema['properties'] ?? [];
            if (! is_array($properties)) {
                continue;
            }

            foreach ($properties as $name => $node) {
                if ($this->isOpaqueBag($node)) {
                    $hits[] = basename((string) $file) . ':' . $name;
                }
            }
        }

        sort($hits);

        return $hits;
    }

    private function isOpaqueBag(mixed $node): bool
    {
        if (! is_array($node)) {
            return false;
        }

        $type = $node['type'] ?? null;
        if (! is_array($type) || ! in_array('object', $type, true) || ! in_array('array', $type, true)) {
            return false;
        }

        // An object|array union that declares NO shape — either bare-string
        // items (an object masquerading as a string array, the get-shopping-
        // confirm `customer` bug) or no `properties` at all (the unspecified
        // render-form bag) — promises nothing to response-schema validation.
        $items = $node['items'] ?? null;
        if (is_array($items) && ($items['type'] ?? null) === 'string') {
            return true;
        }

        return ! isset($node['properties']);
    }

    /** @return list<string> */
    private function baseline(): array
    {
        $json = file_get_contents(self::BASELINE_PATH);
        self::assertIsString($json, sprintf('baseline must be readable: %s', self::BASELINE_PATH));

        /** @var array{renderOnlyFormNodes?: list<string>} $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $nodes = $data['renderOnlyFormNodes'] ?? [];
        self::assertIsArray($nodes, 'baseline.renderOnlyFormNodes must be an array');

        $keys = [];
        foreach ($nodes as $key) {
            $keys[] = (string) $key;
        }

        sort($keys);

        return $keys;
    }
}
