---
layout: default
title: "G-25 — BDRはdomain noun + readonly propertyで表す"
---

# G-25 — BDRはdomain noun + readonly propertyで表す

## Context

Ray.MediaQuery移行で、bool / int / string / conditional guard / trailing SELECT の戻り値を `PostQueryInterface` 実装クラスにした。

初期実装では `GeneratedId`, `ProductCopyResult`, `BulkStatusUpdateResult` のような機械的な名前や、`changedCount()` / `exists()` / `value()` のような単純getterを作った。これは動くが、BDRの値オブジェクトとしては余計な抽象と語彙の揺れを生む。

## Problem

BDRクラスを「実装都合」や「結果であること」で命名すると、以下が起きる。

- `GeneratedId` は「どう作ったか」しか言わない。IDは基本的に全部 generated なので情報量が少ない。
- `*Result` suffix は `PostQueryInterface` の戻り値であることを二重に説明するだけになりやすい。
- `FooData` / `FooManager` と同じく、domain vocabulary ではなく実装分類語になりやすい。
- `public function value(): string { return $this->value; }` のようなメソッドは、振る舞いではなくgetterでしかない。
- 呼び出し側が `->changedCount()` / `->exists()` になり、readonly DTOを読むだけの箇所まで手続きっぽく見える。

## Solution

BDRは **domain noun + `final readonly` + public property** を基本形にする。

```php
use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class ProductStatusUpdate implements PostQueryInterface
{
    public function __construct(public int $changedCount) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $changedCount = is_array($row)
            ? (int) ($row['changed_count'] ?? 0)
            : $context->statement->rowCount();

        return new static($changedCount);
    }
}
```

呼び出し側:

```php
$update = $productCommand->bulkUpdateStatus($codes, $newStatus);
$changedCount = $update->changedCount;
```

### Naming rules

| 避ける | 代替 |
|---|---|
| `GeneratedId` | `AllocatedId` |
| `ProductCopyResult` | `CopiedProduct` |
| `BulkStatusUpdateResult` | `ProductStatusUpdate` |
| `TrackingNumberResult` | `ShippingTrackingNumber` |
| `PaymentVerifyResult` | `PaymentVerification` |
| `PurchaseFlowResult` | `PurchaseTotals` |
| `FooData` | domain noun |
| `FooManager` | domain action / capability noun |

原則:

- 「結果であること」ではなく、**業務上それが何か** をクラス名にする。
- `Generated*`, `Fetched*`, `Loaded*` などの生成手段名は、domain上その言葉が意味を持つ場合だけ使う。
- `Result` postfix は原則使わない。外部ライブラリ名や既存公的語彙が `Result` を要求する場合だけ例外。
- 値を読むだけなら public readonly property にする。
- メソッドは `assertUnique()` のように、例外・検証・派生判断などの振る舞いがある場合だけ追加する。

## Code example

```php
final readonly class FavoritePresence implements PostQueryInterface
{
    public function __construct(public bool $exists) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        return new static($context->rows !== []);
    }
}

final readonly class EmailUniqueness implements PostQueryInterface
{
    public function __construct(public bool $unique) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        return new static($context->rows === []);
    }

    public function assertUnique(): void
    {
        if (! $this->unique) {
            throw new EmailAlreadyRegisteredException();
        }
    }
}
```

`FavoritePresence::$exists` はただの値なので property。`EmailUniqueness::assertUnique()` は例外を投げる domain guard なのでメソッドとして残す。

## Anti-pattern

```php
final class BulkStatusUpdateResult implements PostQueryInterface
{
    public function __construct(private readonly int $changedCount) {}

    public function changedCount(): int
    {
        return $this->changedCount;
    }
}
```

これは `Result` suffix + private property + getter になっていて、domain objectというより JavaBeans 風DTOになっている。

```php
final readonly class GeneratedId implements PostQueryInterface
{
    public function __construct(public string $value) {}
}
```

`GeneratedId` も弱い。IDはほぼ常に生成されるため、「割り当て済み」「次に使う」「予約済み」などの domain semantics を名前に出す。

## Where this matters

- Ray.MediaQuery `PostQueryInterface` の戻り値
- trailing SELECT で DML の要約を返す SQL
- bool / int / string scalar をそのまま返すと意味が欠ける箇所
- 条件付き例外を concrete adapter に戻さず、BDRで表現する箇所
- Be Framework の Being / Final 間で運ぶ composite value

## Related

- G-24: Ray.MediaQuery boundary。BDRは `#[DbQuery]` direct proxy を concrete adapter なしで安全に成立させるための戻り値設計。
- G-16: server-derived Semantic registration。`PaymentVerification` / `PurchaseTotals` のような composite value は semantic vocabularyにも名前が出る。

## Where surfaced

Ray.MediaQuery全面移行で `MediaQueryExecutor` / internal proxy を撤去した後、BDRの初期名が `GeneratedId`, `ProductCopyResult`, `BulkStatusUpdateResult` などに揺れた。レビューで「`GeneratedId` は `Data` / `Manager` と同じ匂いがある」「単純getterより public readonly property が自然」と判明し、`AllocatedId`, `CopiedProduct`, `ProductStatusUpdate`, `PaymentVerification`, `PurchaseTotals` へ整理した。
