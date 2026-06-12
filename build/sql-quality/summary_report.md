# SQL Analysis Summary

## Query Analysis

| SQL File | Cost | Exec Time (ms) | Level | Issues | Report |
|----------|------|----------------|-------|---------|--------|
| address\_next\_id.sql | 1.00 | 0.11 | Medium (μ ± σ) | - | [Details](address\_next\_id.md) |
| admin\_next\_id.sql | 1.00 | 0.09 | Medium (μ ± σ) | - | [Details](admin\_next\_id.md) |
| block\_next\_id.sql | 1.00 | 0.08 | Medium (μ ± σ) | - | [Details](block\_next\_id.md) |
| category\_next\_id.sql | 1.00 | 0.07 | Medium (μ ± σ) | - | [Details](category\_next\_id.md) |
| classCategory\_next\_id.sql | 1.00 | 0.10 | Medium (μ ± σ) | - | [Details](classCategory\_next\_id.md) |
| className\_next\_id.sql | 1.00 | 0.08 | Medium (μ ± σ) | - | [Details](className\_next\_id.md) |
| customer\_next\_id.sql | 1.00 | 0.09 | Medium (μ ± σ) | - | [Details](customer\_next\_id.md) |
| delivery\_next\_id.sql | 1.00 | 0.07 | Medium (μ ± σ) | - | [Details](delivery\_next\_id.md) |
| news\_next\_id.sql | 1.00 | 0.12 | Medium (μ ± σ) | - | [Details](news\_next\_id.md) |
| page\_next\_id.sql | 1.00 | 0.07 | Medium (μ ± σ) | - | [Details](page\_next\_id.md) |
| paymentMethodAdmin\_next\_id.sql | 1.00 | 0.07 | Medium (μ ± σ) | - | [Details](paymentMethodAdmin\_next\_id.md) |
| tag\_next\_id.sql | 1.00 | 0.07 | Medium (μ ± σ) | - | [Details](tag\_next\_id.md) |
| taxRule\_next\_id.sql | 1.00 | 0.08 | Medium (μ ± σ) | - | [Details](taxRule\_next\_id.md) |
| product\_get.sql | 0.70 | 0.33 | Medium (μ ± σ) | FullTableScan, TemporaryTableGrouping | [Details](product\_get.md) |
| product\_class\_get.sql | 0.42 | 0.26 | Medium (μ ± σ) | - | [Details](product\_class\_get.md) |
| product\_list.sql | 8.12 | 4.00 | ⚠️⚠️Very High (> μ + 2σ) | FullTableScan, TemporaryTableGrouping | [Details](product\_list.md) |
| product\_search.sql | 8.12 | 3.53 | ⚠️⚠️Very High (> μ + 2σ) | FullTableScan, TemporaryTableGrouping | [Details](product\_search.md) |
| product\_export.sql | 13.75 | 0.77 | ⚠️⚠️Very High (> μ + 2σ) | FullTableScan, TemporaryTableGrouping | [Details](product\_export.md) |
| tag\_get.sql | 1.00 | 0.12 | Medium (μ ± σ) | - | [Details](tag\_get.md) |
| tag\_list.sql | 2.25 | 0.14 | Medium (μ ± σ) | - | [Details](tag\_list.md) |
| customer\_find\_by\_email.sql | 0.35 | 0.18 | Medium (μ ± σ) | - | [Details](customer\_find\_by\_email.md) |
| customer\_email\_exists.sql | 0.35 | 0.14 | Medium (μ ± σ) | - | [Details](customer\_email\_exists.md) |
| customer\_find\_by\_id.sql | 1.00 | 0.14 | Medium (μ ± σ) | - | [Details](customer\_find\_by\_id.md) |
| customer\_find\_by\_secret\_key.sql | 1.00 | 0.12 | Medium (μ ± σ) | - | [Details](customer\_find\_by\_secret\_key.md) |
| customer\_search.sql | 4.75 | 0.25 | ⚠️High (μ + σ to μ + 2σ) | FullTableScan, IneffectiveSort, TemporaryTableGrouping | [Details](customer\_search.md) |
| address\_get.sql | 1.00 | 0.11 | Medium (μ ± σ) | - | [Details](address\_get.md) |
| address\_list\_by\_customer.sql | 0.70 | 0.13 | Medium (μ ± σ) | - | [Details](address\_list\_by\_customer.md) |
| favorite\_has.sql | 0.70 | 0.36 | Medium (μ ± σ) | - | [Details](favorite\_has.md) |
| favorite\_list.sql | 1.05 | 0.25 | Medium (μ ± σ) | - | [Details](favorite\_list.md) |
| password\_reset\_get.sql | 0.35 | 0.19 | Medium (μ ± σ) | - | [Details](password\_reset\_get.md) |
| admin\_find\_by\_id.sql | 1.00 | 0.15 | Medium (μ ± σ) | - | [Details](admin\_find\_by\_id.md) |
| admin\_find\_by\_login.sql | 0.75 | 0.14 | Medium (μ ± σ) | FullTableScan | [Details](admin\_find\_by\_login.md) |
| admin\_two\_factor\_secret.sql | 0.75 | 0.11 | Medium (μ ± σ) | FullTableScan, ImplicitTypeConversion | [Details](admin\_two\_factor\_secret.md) |
| admin\_list.sql | 5.75 | 0.14 | ⚠️High (μ + σ to μ + 2σ) | FullTableScan | [Details](admin\_list.md) |
| admin\_search.sql | 5.75 | 0.14 | ⚠️High (μ + σ to μ + 2σ) | FullTableScan | [Details](admin\_search.md) |
| order\_by\_order\_no.sql | 0.35 | 0.36 | Medium (μ ± σ) | - | [Details](order\_by\_order\_no.md) |
| order\_by\_pre\_order\_id.sql | 1.00 | 0.20 | Medium (μ ± σ) | - | [Details](order\_by\_pre\_order\_id.md) |
| order\_history\_by\_order\_no.sql | 0.70 | 0.42 | Medium (μ ± σ) | - | [Details](order\_history\_by\_order\_no.md) |
| order\_items\_by\_order\_no.sql | 0.70 | 0.18 | Medium (μ ± σ) | - | [Details](order\_items\_by\_order\_no.md) |
| order\_list\_all.sql | 1.41 | 0.23 | Medium (μ ± σ) | - | [Details](order\_list\_all.md) |
| order\_list\_by\_customer.sql | 0.35 | 0.15 | Medium (μ ± σ) | - | [Details](order\_list\_by\_customer.md) |
| shipping\_get\_by\_order\_no.sql | 1.70 | 0.13 | Medium (μ ± σ) | TemporaryTableGrouping | [Details](shipping\_get\_by\_order\_no.md) |
| shipping\_tracking\_by\_order\_no.sql | 1.70 | 0.12 | Medium (μ ± σ) | TemporaryTableGrouping | [Details](shipping\_tracking\_by\_order\_no.md) |
| shipping\_list\_all.sql | 0.70 | 0.11 | Medium (μ ± σ) | - | [Details](shipping\_list\_all.md) |
| cart\_by\_key.sql | 0.70 | 0.24 | Medium (μ ± σ) | FullTableScan, TemporaryTableGrouping | [Details](cart\_by\_key.md) |
| cart\_by\_session\_prefix.sql | 1.70 | 0.24 | Medium (μ ± σ) | FullTableScan, TemporaryTableGrouping | [Details](cart\_by\_session\_prefix.md) |
| plugin\_find\_by\_code.sql | 0.35 | 0.10 | Medium (μ ± σ) | FullTableScan | [Details](plugin\_find\_by\_code.md) |
| plugin\_list\_all.sql | 1.35 | 0.09 | Medium (μ ± σ) | FullTableScan | [Details](plugin\_list\_all.md) |
| csv\_column\_list\_by\_type.sql | 0.35 | 0.09 | Medium (μ ± σ) | - | [Details](csv\_column\_list\_by\_type.md) |
| tbase\_info\_get.sql | 1.00 | 0.15 | Medium (μ ± σ) | - | [Details](tbase\_info\_get.md) |
| ttrade\_law\_get.sql | 1.00 | 0.08 | Medium (μ ± σ) | - | [Details](ttrade\_law\_get.md) |
| ttemplate\_list.sql | 0.35 | 0.08 | Medium (μ ± σ) | - | [Details](ttemplate\_list.md) |
| tblock\_get.sql | 1.00 | 0.12 | Medium (μ ± σ) | - | [Details](tblock\_get.md) |
| tblock\_list.sql | 0.35 | 0.08 | Medium (μ ± σ) | - | [Details](tblock\_list.md) |
| tcategory\_get.sql | 1.00 | 0.10 | Medium (μ ± σ) | - | [Details](tcategory\_get.md) |
| tcategory\_list.sql | 8.12 | 0.70 | ⚠️⚠️Very High (> μ + 2σ) | FullTableScan, TemporaryTableGrouping | [Details](tcategory\_list.md) |
| tclass\_category\_get.sql | 1.00 | 0.12 | Medium (μ ± σ) | - | [Details](tclass\_category\_get.md) |
| tclass\_category\_list.sql | 2.25 | 0.57 | Medium (μ ± σ) | - | [Details](tclass\_category\_list.md) |
| tclass\_category\_list\_by\_class\_name.sql | 0.90 | 0.30 | Medium (μ ± σ) | - | [Details](tclass\_category\_list\_by\_class\_name.md) |
| tclass\_name\_get.sql | 1.00 | 0.12 | Medium (μ ± σ) | - | [Details](tclass\_name\_get.md) |
| tclass\_name\_list.sql | 0.75 | 0.11 | Medium (μ ± σ) | - | [Details](tclass\_name\_list.md) |
| tdelivery\_get.sql | 1.00 | 0.15 | Medium (μ ± σ) | - | [Details](tdelivery\_get.md) |
| tdelivery\_list.sql | 0.35 | 0.10 | Medium (μ ± σ) | - | [Details](tdelivery\_list.md) |
| tlayout\_get.sql | 1.00 | 0.11 | Medium (μ ± σ) | - | [Details](tlayout\_get.md) |
| tlayout\_list.sql | 0.35 | 0.13 | Medium (μ ± σ) | - | [Details](tlayout\_list.md) |
| tlogin\_history\_list.sql | 1.35 | 0.10 | Medium (μ ± σ) | FullTableScan | [Details](tlogin\_history\_list.md) |
| tmail\_template\_get.sql | 1.00 | 0.10 | Medium (μ ± σ) | - | [Details](tmail\_template\_get.md) |
| tmail\_template\_list.sql | 0.35 | 0.12 | Medium (μ ± σ) | - | [Details](tmail\_template\_list.md) |
| tnews\_get.sql | 1.00 | 0.13 | Medium (μ ± σ) | - | [Details](tnews\_get.md) |
| tnews\_list.sql | 0.35 | 0.12 | Medium (μ ± σ) | - | [Details](tnews\_list.md) |
| tpage\_get.sql | 1.00 | 0.11 | Medium (μ ± σ) | - | [Details](tpage\_get.md) |
| tpage\_list.sql | 0.35 | 0.11 | Medium (μ ± σ) | - | [Details](tpage\_list.md) |
| tpayment\_get.sql | 1.00 | 0.19 | Medium (μ ± σ) | - | [Details](tpayment\_get.md) |
| tpayment\_list.sql | 0.45 | 0.11 | Medium (μ ± σ) | - | [Details](tpayment\_list.md) |
| ttax\_rule\_get.sql | 1.00 | 0.11 | Medium (μ ± σ) | - | [Details](ttax\_rule\_get.md) |
| ttax\_rule\_list.sql | 0.35 | 0.11 | Medium (μ ± σ) | - | [Details](ttax\_rule\_list.md) |

## Queries with Optimizer Impact

| SQL File | Base Access | Optimized Access | Cost Impact | Base Issues | Plan Changes |
|:----------|:------------|:----------------|:------------|:------------|:-------------|
| product\_class\_get.sql | Unknown | Unknown | -60.0% | - | - |
| cart\_by\_key.sql | ALL, 2 rows, 100.0% → eq\_ref, using PRIMARY, 1 rows, 100.0% | ALL, 1 rows, 100.0% → eq\_ref, using PRIMARY, 1 rows, 100.0% | -79.5% | FullTableScan, TemporaryTableGrouping | - |
| cart\_by\_session\_prefix.sql | Unknown | Unknown | -68.6% | FullTableScan, TemporaryTableGrouping | - |

## Statistics

- Total queries analyzed: 76
- Average query cost: 1.52
- Standard deviation: 2.20
