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
extra_charge: Float                 - Extra charge for that day (may be overwritten by time limits extra charge).
extra_charge_message: String        - Extra charge formatted message.
time_limits: [DeliveryTimeLimit]    - Limits by time when available.
```

DeliveryTimeLimit object:

```
from: String            - Time from in hh:mm format.
to: String              - Time to in hh:mm format.
extra_charge: String    - Extra charge for that time slot.
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
            extra_charge
            extra_charge_message
            time_limits {
                from
                to
                extra_charge
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
                        "day_index": 3,
                        "date_formatted": "2022-05-11",
                        "date": "2022-05-11",
                        "extra_charge": 0,
                        "extra_charge_message": "",
                        "time_limits": [
                            {
                                "from": "10:00",
                                "to": "12:00",
                                "extra_charge": "US$2.00"
                            },
                            {
                                "from": "14:00",
                                "to": "16:00",
                                "extra_charge": "US$5.00"
                            },
                            {
                                "from": "17:05",
                                "to": "23:30",
                                "extra_charge": ""
                            }
                        ]
                    },
                    {
                        "day_index": 4,
                        "date_formatted": "2022-05-12",
                        "date": "2022-05-12",
                        "extra_charge": 0,
                        "extra_charge_message": "",
                        "time_limits": []
                    },
                    {
                        "day_index": 5,
                        "date_formatted": "2022-05-13",
                        "date": "2022-05-13",
                        "extra_charge": 0,
                        "extra_charge_message": "",
                        "time_limits": []
                    },
                    {
                        "day_index": 8,
                        "date_formatted": "2022-05-16<span class=\"data-item__price\"> +US$15.00</span>",
                        "date": "2022-05-16",
                        "extra_charge": 15,
                        "extra_charge_message": "<span class=\"data-item__price\"> +US$15.00</span>",
                        "time_limits": []
                    },
                    {
                        "day_index": 9,
                        "date_formatted": "2022-05-17",
                        "date": "2022-05-17",
                        "extra_charge": 0,
                        "extra_charge_message": "",
                        "time_limits": []
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
