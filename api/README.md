# POS-Namin API Documentation

## Authentication

All API endpoints require authentication except for the login endpoint. Use session-based authentication.

### Login
```
POST /auth.php
Content-Type: application/json

{
    "username": "string",
    "password": "string"
}
```

## Products API

### List All Products
```
GET /api/products.php
Response: {
    "products": [
        {
            "id": "integer",
            "category_id": "integer",
            "name": "string",
            "description": "string",
            "price": "decimal",
            "stock_quantity": "integer",
            "created_at": "datetime",
            "updated_at": "datetime"
        }
    ]
}
```

### Get Single Product
```
GET /api/products.php?id={product_id}
Response: {
    "product": {
        "id": "integer",
        "category_id": "integer",
        "name": "string",
        "description": "string",
        "price": "decimal",
        "stock_quantity": "integer",
        "created_at": "datetime",
        "updated_at": "datetime"
    }
}
```

### Get Products by Category
```
GET /api/products.php?action=category&id={category_id}
Response: {
    "products": [
        {
            "id": "integer",
            "category_id": "integer",
            "category_name": "string",
            "name": "string",
            "description": "string",
            "price": "decimal",
            "stock_quantity": "integer"
        }
    ]
}
```

### Get Low Stock Products
```
GET /api/products.php?action=low_stock&threshold={number}
Response: {
    "products": [
        {
            "id": "integer",
            "name": "string",
            "stock_quantity": "integer"
        }
    ]
}
```

### Create Product (Admin Only)
```
POST /api/products.php
Content-Type: application/json

{
    "category_id": "integer",
    "name": "string",
    "description": "string",
    "price": "decimal",
    "stock_quantity": "integer"
}

Response: {
    "product": {
        "id": "integer",
        "category_id": "integer",
        "name": "string",
        "description": "string",
        "price": "decimal",
        "stock_quantity": "integer",
        "created_at": "datetime"
    }
}
```

### Update Product (Admin Only)
```
PUT /api/products.php?id={product_id}
Content-Type: application/json

{
    "category_id": "integer",
    "name": "string",
    "description": "string",
    "price": "decimal",
    "stock_quantity": "integer"
}

Response: {
    "product": {
        "id": "integer",
        "category_id": "integer",
        "name": "string",
        "description": "string",
        "price": "decimal",
        "stock_quantity": "integer",
        "updated_at": "datetime"
    }
}
```

### Delete Product (Admin Only)
```
DELETE /api/products.php?id={product_id}
Response: {
    "message": "Product deleted successfully"
}
```

## Categories API

### List All Categories
```
GET /api/categories.php
Response: {
    "categories": [
        {
            "id": "integer",
            "name": "string",
            "description": "string",
            "created_at": "datetime"
        }
    ]
}
```

### Get Categories with Product Count
```
GET /api/categories.php?action=with_products
Response: {
    "categories": [
        {
            "id": "integer",
            "name": "string",
            "product_count": "integer"
        }
    ]
}
```

### Create Category (Admin Only)
```
POST /api/categories.php
Content-Type: application/json

{
    "name": "string",
    "description": "string"
}

Response: {
    "category": {
        "id": "integer",
        "name": "string",
        "description": "string",
        "created_at": "datetime"
    }
}
```

### Delete Category (Admin Only)
```
DELETE /api/categories.php?id={category_id}
Response: {
    "message": "Category and associated products deleted successfully"
}
```

## Orders API

### Create Order
```
POST /api/orders.php
Content-Type: application/json

{
    "items": [
        {
            "product_id": "integer",
            "quantity": "integer"
        }
    ],
    "payment_method": "enum('cash', 'card')"
}

Response: {
    "order": {
        "id": "integer",
        "user_id": "integer",
        "total_amount": "decimal",
        "payment_method": "string",
        "status": "string",
        "created_at": "datetime",
        "items": [
            {
                "product_id": "integer",
                "product_name": "string",
                "quantity": "integer",
                "unit_price": "decimal",
                "subtotal": "decimal"
            }
        ]
    }
}
```

### Get Order Details
```
GET /api/orders.php?id={order_id}
Response: {
    "order": {
        "id": "integer",
        "user_id": "integer",
        "total_amount": "decimal",
        "payment_method": "string",
        "status": "string",
        "created_at": "datetime"
    },
    "items": [
        {
            "product_id": "integer",
            "product_name": "string",
            "quantity": "integer",
            "unit_price": "decimal",
            "subtotal": "decimal"
        }
    ]
}
```

### Update Order Status (Admin Only)
```
POST /api/orders.php?action=update_status
Content-Type: application/json

{
    "order_id": "integer",
    "status": "enum('pending', 'completed', 'cancelled')"
}

Response: {
    "order": {
        "id": "integer",
        "status": "string",
        "updated_at": "datetime"
    }
}
```

### Get Daily Sales (Admin Only)
```
GET /api/orders.php?action=daily_sales&date={YYYY-MM-DD}
Response: {
    "sales": {
        "sale_date": "date",
        "total_orders": "integer",
        "total_sales": "decimal"
    }
}
```

## Error Responses

All endpoints may return error responses in the following format:
```
{
    "error": "Error message description"
}
```

Common HTTP status codes:
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 405: Method Not Allowed
- 500: Internal Server Error
