---
layout: default
title: "/admin/calendar"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/calendar
EC-CUBE 定休日カレンダー設定 — Setting/Shop Tier-2.

Thin renderer / action surface for `Setting/Shop/calendar.twig`.
BeMart has no holiday-calendar storage in this wave, so POST/DELETE
deliberately expose a concrete, CSRF-protected Resource surface that
proves the EC-CUBE aliases no longer fall back to ActionRedirect.




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST
EC-CUBE doUpdateCalendar / doCreateCalendarHoliday.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| operation | string |  | update | Optional |  |  |
| title | string |  |  | Optional |  |  |
| holiday | string |  |  | Optional |  |  |
| calendarId | int |  |  | Optional |  |  |


### Response

_Not available_
## DELETE
EC-CUBE doDeleteCalendarHoliday.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| calendarId | int |  |  | Optional |  |  |


### Response

_Not available_