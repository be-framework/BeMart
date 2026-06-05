---
layout: default
title: "/admin/log"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/log
EC-CUBE ログ表示 — Setting/System Tier-2.

Thin GET renderer for `Setting/System/log.twig`. EC-CUBE reads log
files from Symfony's log directory; BeMart has no ALPS transition for
log inspection, so this resource exposes a stable form and a bounded
sample body without adding a file-read mutation surface.




## GET


### Request

_No parameters required_

### Response

_Not available_