<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/delivery/delivery
EC-CUBE doUpdateDelivery + doDeleteDelivery — single-row endpoint
(Wave 9θ).

- GET    → goDeliveryEdit (safe read, admin AUTHZ, Setting/Shop Tier-2)
- PUT    → doUpdateDelivery (admin edits a delivery master — idempotent)
- DELETE → doDeleteDelivery (admin removes a delivery master — idempotent)




## GET
EC-CUBE 配送方法設定（編集） — Setting/Shop Tier-2.

Thin GET renderer for `Setting/Shop/delivery_edit.twig`. An empty
`$deliveryId` renders a blank "new delivery" form; a known id
pre-fills the editor; an unknown id is 404. The delivery-master
list doubles as the AUTHZ gate — no admin session → 403.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| deliveryId | string | 配送方法ID |  | Optional |  |  |


### Response

_Not available_
## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| deliveryId | string | 配送方法ID |  | Required |  |  |
| deliveryName | string | 配送業者名 |  | Optional |  |  |
| visible | bool |  |  | Optional |  |  |


### Response

_Not available_
## DELETE


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| deliveryId | string | 配送方法ID |  | Required |  |  |


### Response

_Not available_