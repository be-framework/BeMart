<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /index
EC-CUBE goTop — トップページ (Wave 3H pure renderer).

Pure renderer: no Be Framework, no domain logic, no Reasons.
Anonymous-accessible (returns 200 regardless of session state).
Maps to `page://self/`.

The ALPS `#Top` resource lists 13 descriptors. In the production
frontend these are populated via Twig / EC-CUBE side queries (shop
message, news, recommended products, category nav, etc.). Wave 3H
deliberately limits this renderer to the link surface and a stub
`staticContent` shape — full data lookup (shop message, news,
recommended products, category navigation) is deferred and noted
inline as TODO until a dedicated Top aggregation lands.




## GET
EC-CUBE goTop — render the top page scaffolding.



### Request

_No parameters required_

### Response

_Not available_