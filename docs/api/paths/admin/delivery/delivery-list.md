---
layout: default
title: "/admin/delivery/delivery-list"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/delivery/delivery-list
EC-CUBE goDeliveryList + doCreateDelivery — collection endpoint
(Wave 9θ).

- GET  → goDeliveryList    (admin lists delivery masters — safe read)
  - POST → doCreateDelivery  (admin adds a new delivery master)

Single-row affordances live at `page://self/admin/delivery/delivery`.




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| deliveryName | string | 配送業者名 |  | Required |  |  |
| visible | bool |  | 1 | Optional |  |  |


### Response

_Not available_