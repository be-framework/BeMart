---
layout: default
title: "/admin/order/mail-confirm"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/order/mail-confirm
EC-CUBE 受注メール確認 — Order Tier-2 (`admin/Order/mail_confirm.twig`).

GET /admin/order/mail-confirm?orderNo=…

The confirmation step shown between the mail composer
({@see \SendMail}) and the actual send: a read-only preview of the
subject / body the admin is about to send. EC-CUBE renders the
composed mail content here; the Be domain re-sends the
order-confirmation mail keyed by orderNo only
({@see \MyVendor\BeMart\Be\Input\AdminSendOrderMailInput}), so this
page carries the orderNo through to the send action and renders the
confirm-and-send shell.

AUTHZ is a direct admin-session check (Pattern B — this is a
read-only preview page, no Be transition is invoked); a non-admin
firewall is refused with 403.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Optional |  |  |


### Response

_Not available_