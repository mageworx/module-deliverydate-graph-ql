# MageWorx_DeliveryDateGraphQl

GraphQL API module for Mageworx [Magento 2 Delivery Date](https://www.mageworx.com/delivery-date-magento-2.html) extension.

## Installation

**1) Installation using composer (from packagist)**
- Execute the following command: `composer require mageworx/module-deliverydate-graph-ql`

**2) Copy-to-paste method**
- Download this module and upload it to the `app/code/MageWorx/DeliveryDateGraphQl` directory *(create "DeliveryDateGraphQl" first if missing)*

## How to use

### 1. The **deliveryDate** query returns the information about the selected Delivery Date of the specified cart.

Query attribute is defined below:

```
cart_id: String! - The unique ID of the cart to query.
```

By default you can use the following attributes:

```
day: String     - Delivery day, like 2022-05-11
time: String    - Delivery time, like 00:00_23:59
comment: String - Delivery comment given by a customer
```

**Request:**

```graphql
query ($magentoCartId: String!) {
    deliveryDate(cart_id: $magentoCartId) {
        day
        time
        comment
    }
}
```

**Response:**

```json
{
    "data": {
        "deliveryDate": {
            "day": "2022-05-11",
            "time": "14:00_16:00",
            "comment": "Hello GraphQL"
        }
    }
}
```
---
> ***Note***
>
> We are using next variables in our examples:
>
> ```json
> {
>     "magentoCartId": "{{graphQLCartId}}"
> }
> ```
>
> where `{{graphQLCartId}}` is string like `VVEmRZMaLRgH1NZ7kkZXWJKIfZiIhbvP` (masked quote id).
---

### 2. The **availableDeliveryDates** query returns the information about the all available delivery dates (with time) for the cart.

Query attribute is defined below:

```
cart_id: String!        - The unique ID of the cart to query.
method: String          - Shipping method code in carriercode_methodcode format. Returns the result for all methods if not specified.
start_day_index: Int    - Offset of days from which the calculation starts. Returns the result from tooday if not specified.
end_day_index: Int      - Number of days to calculate. Returns the result till "Max Delivery Period" if not specified (could be different per Delivery Option configuration).
```

By default you can use the following attributes:

```
method: String                  - Shipping method code in carriercode_methodcode format.
day_limits: [DeliveryDayLimit]  - List of available limits for that method and cart
```

DeliveryDayLimit object:

```
day_index: Int                      - Index of the day from today (from 0).
date_formatted: String              - Date formatted using format selected in store configuration.
date: String                        - Date in standard Y-m-d format.
extra_charge: ExtraCharge           - Extra charge for that day.
time_limits: [DeliveryTimeLimit]    - Limits by time when available.
```

DeliveryTimeLimit object:

```
from: String              - Time from in hh:mm format.
to: String                - Time to in hh:mm format.
extra_charge: ExtraCharge - Extra charge for that time slot. Summed up with a surcharge from the delivery day settings.
```

ExtraCharge object:

```
amount: Float           - Amount in selected currency (in cart currency).
formatted: String       - Formatted according selected locale (with currency symbol).
currency_symbol: String - Currency symbol.
currency_code: String   - Currency code.
```

**Request:**

```graphql
query ($magentoCartId: String!) {
    availableDeliveryDates(
        cart_id: $magentoCartId
        method: "flatrate_flatrate"
        start_day_index: 2
        end_day_index: 10
    ) {
        method,
        day_limits {
            day_index
            date_formatted
            date
            extra_charge {
                amount
                formatted
                currency_code
                currency_symbol
            }
            time_limits {
                from
                to
                extra_charge {
                    amount
                    formatted
                    currency_code
                    currency_symbol
                }
            }
        }
    }
}
```

**Response:**

```json
{
  "data": {
    "availableDeliveryDates": [
      {
        "method": "flatrate_flatrate",
        "day_limits": [
          {
            "day_index": 5,
            "date_formatted": "14-June-2023",
            "date": "2023-06-14",
            "extra_charge": null,
            "time_limits": [
              {
                "from": "09:00",
                "to": "12:00",
                "extra_charge": {
                  "amount": 0,
                  "formatted": "",
                  "currency_code": "EUR",
                  "currency_symbol": "€"
                }
              },
              {
                "from": "11:00",
                "to": "16:00",
                "extra_charge": {
                  "amount": 0,
                  "formatted": "",
                  "currency_code": "EUR",
                  "currency_symbol": "€"
                }
              },
              {
                "from": "15:00",
                "to": "20:00",
                "extra_charge": {
                  "amount": 4.5409,
                  "formatted": "€4.54",
                  "currency_code": "EUR",
                  "currency_symbol": "€"
                }
              }
            ]
          },
          {
            "day_index": 6,
            "date_formatted": "15-June-2023",
            "date": "2023-06-15",
            "extra_charge": null,
            "time_limits": [
              {
                "from": "09:00",
                "to": "12:00",
                "extra_charge": {
                  "amount": 0,
                  "formatted": "",
                  "currency_code": "EUR",
                  "currency_symbol": "€"
                }
              },
              {
                "from": "11:00",
                "to": "16:00",
                "extra_charge": {
                  "amount": 0,
                  "formatted": "",
                  "currency_code": "EUR",
                  "currency_symbol": "€"
                }
              },
              {
                "from": "15:00",
                "to": "20:00",
                "extra_charge": {
                  "amount": 4.5409,
                  "formatted": "€4.54",
                  "currency_code": "EUR",
                  "currency_symbol": "€"
                }
              }
            ]
          }
        ]
      }
    ]
  }
}
```

### 3. The **setDeliveryDateOnCart** mutation allows you to set delivery date and time to the cart.

Syntax:
```
mutation: {setDeliveryDateOnCart(input: SetDeliveryDateOnCartInput): Cart}
```

The SetDeliveryDateOnCartInput object must contain the following attributes:
```
cart_id: String!                    - The unique ID of a `Cart` object.
delivery_date: DeliveryDateInput!   - Selected delivery date, time (optional) and comment (optional).
```

The DeliveryDateInput object must contain the following attributes:
```
day: String!    - A string that identifies a delivery day in standard format Y-m-d.
time: String    - A string that identifies a delivery time diapason in "12:00_23:59" fromat. Must be a valid time.
comment: String - Comment for delivery. Any additional information from customer. Visible to customer by default.
```

**Request:**

```graphql
mutation ($magentoCartId: String!) {
    setDeliveryDateOnCart(
        input: {
            cart_id: $magentoCartId,
            delivery_date: {
                day: "2022-05-11"
                time: "14:00_16:00"
                comment: "Hello GraphQL"
            }
        }
    ) {
        delivery_date {
            day
            time
            comment
        }
    }
}
```

**Response:**

```json
{
    "data": {
        "setDeliveryDateOnCart": {
            "delivery_date": {
                "day": "2022-05-11",
                "time": "14:00_16:00",
                "comment": "Hello GraphQL"
            }
        }
    }
}
```

### 4. The **removeDeliveryDateFromCart** mutation allows you to remove selected delivery date and time from the cart (if exists).

Syntax:
```
mutation: {removeDeliveryDateFromCart(cart_id: String!): Cart}
```

**Request:**

```graphql
mutation ($magentoCartId: String!) {
    removeDeliveryDateFromCart (cart_id: $magentoCartId) {
        delivery_date {
            day
            time
            comment
        }
    }
}
```

**Response:**

```json
{
    "data": {
        "removeDeliveryDateFromCart": {
            "delivery_date": {
                "day": "",
                "time": "",
                "comment": ""
            }
        }
    }
}
```

### 5. The **delivery_date** object is also available in the Cart type.

By default you can use the following attributes:

```
day: String     - Delivery day, like 2022-05-11
time: String    - Delivery time, like 00:00_23:59
comment: String - Delivery comment given by a customer
```

**Request**

```graphql
{
  customerCart {
    id
    items {
      id
      product {
        name
        sku
      }
      quantity
    }
    delivery_date {
        day 
        time
        comment
    }
  }
}
```

**Response**

```json
{
    "data": {
        "customerCart": {
            "id": "VVEmRZMaLRgH1NZ7kkZXWJKIfZiIhbvP",
            "items": [
                {
                    "id": "37",
                    "product": {
                        "name": "A",
                        "sku": "A"
                    },
                    "quantity": 1
                }
            ],
            "delivery_date": {
                "day": "2022-05-11",
                "time": "14:00_16:00",
                "comment": "Hello GraphQL"
            }
        }
    }
}
```
