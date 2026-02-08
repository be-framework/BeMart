# alps.json セマンティックID 分析レポート — 未解決の問題

EC-CUBE 4.3 のソースコードを検証した結果、以下の点が未解決。

## 1. deviceType の値（10と2）の由来が不明

`mtb_device_type.csv` で `2=モバイル`, `10=PC` と定義されているが、なぜ連番（1, 2）ではなく 2 と 10 なのか、ソースコードからは由来を特定できなかった。EC-CUBE 2.x のコードやドキュメントの調査が必要。

## 2. shopEmail02 の役割に確信が持てない

MailService.php の検証で以下を確認した:

- email01: From + BCC（ほぼ全メール）
- email02: お問い合わせメールの From/BCC/ReplyTo（MailService.php 302-306行目のみ）
- email03: Reply-To
- email04: Return-Path

ただし email02 が「お問い合わせ専用」というのはMailService.phpの現在の実装からの観察であり、プラグインや過去バージョンでの使われ方は未確認。管理画面のラベルやヘルプテキストでの記述も未確認。
