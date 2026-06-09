---
layout: default
title: "adminId"
---

# adminId

`adminId` は EC-CUBE の管理者メンバーを識別する不透明な文字列ハンドルである。ALPS では数値 ID ではなく、Fake と SQL の ID 形状差を隠す semantic identifier として扱う。

## Meaning

`adminId` は `Member`、管理者更新、削除、権限更新で使われる管理者メンバーの識別子である。BeMart の `AdminEntity` 層では数値ではなく文字列として保持する。

## ID Shape

Fake 実装は 32桁 hex を `ad` プレフィックス付きで生成する。シード値には `ad000000000000000000000000000001` などがある。`customerId` と形は似ているが、識別空間は別である。

SQL 実装は `dtb_member.id` の `int unsigned AUTO_INCREMENT` を文字列化して使う。同じ interface の下で Fake と SQL の ID 形状が異なる。

## SQL Behavior

非数値 ID は `SqlAdminQuery::findById` では miss として扱う。`SqlAdminCommand` の create / update / delete / updateAuthority でも no-op に倒す。

そのため Fake ハンドルを SQL バックエンドに渡しても、404 / 403 経路は Fake と SQL で同形になる。代表的には `MemberDeleted` や `AuthorityRoleUpdated` が同じ失敗経路を踏む。

## Related ID Pattern

この扱いは `addressId`、`pageId`、`blockId`、`tagId` と同じ Fake / SQL 二重性である。
