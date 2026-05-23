# G-24 — SQL境界はRay.MediaQuery interface + SQLファイルにする

## Context

BeMartのEC-CUBE移植では、既存のPhase 2 SQL実装に `Sql*Query` / `Sql*Command` のPHP実クラスとPDO prepared statementが存在する。これは既存分として後でまとめて移行する。

今後の新規SQL境界では、PHP実クラスにPDOクエリを書かず、Ray.MediaQueryでQuery/Command interfaceを実装する。

## Problem

新規Query/CommandごとにPHP実クラスでPDOを直接書くと、以下が起きる。

- SQLがPHP文字列に埋まり、EC-CUBEスキーマとの差分確認がしづらい。
- Fake → SQLの境界が実装クラスの都合に引っ張られる。
- DI/AOPで差し替えるべきinfra境界が、手続き的なPDOコードとして増殖する。
- SQLレビュー、Ray.Di wiring、テスト粒度が一貫しなくなる。

## Solution

新規SQL境界は Ray.MediaQuery を使う。

- PHPには `#[DbQuery('sql_id')]` を付けたinterfaceを書く。
- SQLは `{sqlDir}/{sql_id}.sql` に置く。
- メソッド引数名とSQLの `:named` placeholderを一致させる。
- return typeでfetch/hydration/exec結果を決める。
- Entity constructor hydrationを使う場合、`SELECT` のカラム順をconstructor引数順に合わせる。
- FactoryでEntityを復元する場合も、SQLのSELECT順とEntity constructor順を合わせ、単純な橋渡しではnamed argumentsを使わない。
  - named argumentsは、順序を意図的に崩す／一部defaultを飛ばす／同型引数の取り違えを避ける必要がある時だけ使う。
  - `new CustomerEntity(customerId: ..., email: ...)` のような全項目列挙は、FactoryとEntity定義を二重管理にするため避ける。
- `void`, `?Entity`, `array<Entity>`, `AffectedRows`, `InsertedRow`, `PostQueryInterface`, `Pages` を意図に応じて使い分ける。

## Code example

```php
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\Pages;

interface AdminProductMediaQuery
{
    #[DbQuery('admin_product_find', type: 'row')]
    public function find(string $productCode): ?AdminProductRow;

    /** @return array<AdminProductRow> */
    #[DbQuery('admin_product_list')]
    public function list(int $limit, int $offset): array;

    #[DbQuery('admin_product_update')]
    public function update(
        string $productCode,
        string $productName,
        int $price02,
    ): void;
}
```

`sql/admin_product_find.sql`:

```sql
SELECT
  p.product_code,
  p.name,
  pc.price02
FROM dtb_product p
JOIN dtb_product_class pc ON pc.product_id = p.id
WHERE p.product_code = :productCode
```

Module wiringは `MediaQuerySqlModule(interfaceDir: ..., sqlDir: ...)` と `AuraSqlModule(...)` をinstallする。

## Anti-pattern

```php
final class SqlProductQuery implements ProductQueryInterface
{
    public function __construct(private PDO $pdo) {}

    public function find(string $productCode): ?ProductEntity
    {
        $stmt = $this->pdo->prepare('SELECT ... WHERE product_code = :productCode');
        $stmt->execute(['productCode' => $productCode]);
        // hydration...
    }
}
```

これは既存分には残っているが、新規追加は禁止する。既存 `Sql*` 実装は別フェーズでまとめてRay.MediaQueryへ移行する。

## Where this matters

- Productの規格行列、画像、カテゴリ/タグ実編集
- Orderの新規登録、詳細検索、配送/明細/ステータス
- Customerの新規登録、詳細検索、購入履歴/配送先/お気に入り
- Content/Settingのファイル管理、ログ、マスタデータ

これらの新規SQLは **Fake → EC-CUBEスキーマ照合 → Ray.MediaQuery SQL → Resource/Form → Twig/Browser** の順で作る。

## Related

- G-23: Hypermedia tests are the storage-migration contract
- Ray.MediaQuery official llms reference: `https://ray-di.github.io/Ray.MediaQuery/llms-full.txt`
