---
layout: default
title: "/admin/content/cache"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/cache
EC-CUBE キャッシュ管理 — admin CMS page.

Hard ActionRedirect completion: `onPut` drives the Be `doClearCache`
transition ({@see \ClearCacheInput} → {@see \CacheCleared}); the actual
cache-directory purge is isolated behind
{@see \MyVendor\BeMart\Be\Reason\Service\CacheClearerInterface}. ALPS
marks the transition `idempotent` → PUT. `onGet` renders the screen.




## GET


### Request

_No parameters required_

### Response

_Not available_
## PUT
Clears the application cache (doClearCache).



### Request

_No parameters required_

### Response

_Not available_