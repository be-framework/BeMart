---
layout: default
title: "Query / Storage method naming"
---

# Query / Storage method naming

BeMart の Ray.MediaQuery 境界では、SQL ID / fixture 名は検索性のために既存の snake_case を維持し、PHP メソッド名だけを呼び出し側の語彙として整える。

## 規約

| 操作 | メソッド名 | 例 |
| --- | --- | --- |
| 主キー・代表キーの単件取得 | `item()` | `$products->item($productCode)` |
| 代替キーの単件取得 | `byXxx()` | `$customers->byEmail($email)` |
| 全件・範囲取得 | `list()` | `$orders->list($limit, $offset)` |
| 属性別の複数件取得 | `listByXxx()` | `$orders->listByCustomer($customerId)` |
| 検索 | `search()` | `$products->search($keyword)` |
| ID 採番 | `next()` | `$customerIds->next()` |
| 存在確認 | `exists()` | `$favorites->exists($customerId, $productCode)` |
| 状態変更・特定列更新 | `updateXxx()` / domain verb | `updatePassword()`, `setVisible()` |

## 境界

- `#[DbQuery('...')]` の SQL ID は原則変更しない。
- `*StorageInterface` の Query / Command 分離はこの規約の対象外。分離が必要な場合は別計画で扱う。
- MyVendor.Cms と同じく、インタフェース名がドメイン名を担うため、メソッド名でドメイン名を反復しない。

## 今回の非適用範囲

既存 `*StorageInterface` には、読み書き混在の過渡期 API として `put()`, `delete()`, `create()` などが残る。これは CQRS 分離を今回のリファクタリング対象外にしたためで、SQL ID / fixture 名と同じく一括 rename しない。

次に分離する場合は、`*QueryInterface` / `*CommandInterface` へ責務を分けたうえで、読み取りは `item()` / `byXxx()` / `list()`、書き込みは `save()` / `updateXxx()` / domain verb へ寄せる。
