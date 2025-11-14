# Nexus ERP - Backoffice API Documentation

## Overview

The Nexus ERP Backoffice API provides RESTful endpoints for managing companies, offices, departments, and staff. All endpoints follow REST conventions and return JSON responses with consistent formatting.

## Base URL

```
/api/v1/backoffice
```

## Authentication

Authentication is handled by middleware. Include authentication headers as required by your authentication system.

## Response Format

All API responses follow a consistent format:

### Success Response
```json
{
  "success": true,
  "data": {...},
  "meta": {...},
  "links": {...}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {...}
}
```

## Endpoints

### Company Management

#### List Companies
```
GET /api/v1/backoffice/companies
```

**Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (max 100)
- `search` (string): Search in name or code
- `active` (boolean): Filter by active status
- `parent_id` (integer): Filter by parent company

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Acme Corporation",
      "code": "ACME",
      "description": "Main holding company",
      "is_active": true,
      "parent_company_id": null,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

#### Create Company
```
POST /api/v1/backoffice/companies
```

**Request Body:**
```json
{
  "name": "string (required)",
  "code": "string (optional, unique)",
  "description": "string (optional)",
  "parent_company_id": "integer (optional)",
  "is_active": "boolean (optional, default: true)"
}
```

#### Get Company
```
GET /api/v1/backoffice/companies/{id}
```

#### Update Company
```
PUT /api/v1/backoffice/companies/{id}
```

**Request Body:** Same as create, all fields optional

#### Delete Company
```
DELETE /api/v1/backoffice/companies/{id}
```

#### Company Hierarchy
```
PUT /api/v1/backoffice/companies/{id}/hierarchy
```

**Request Body:**
```json
{
  "parent_company_id": "integer (optional)"
}
```

#### Organizational Chart
```
GET /api/v1/backoffice/companies/{id}/organizational-chart
```

### Office Management

#### List Offices
```
GET /api/v1/backoffice/offices
```

**Parameters:**
- `company_id` (integer): Filter by company
- `search` (string): Search in name, code, or address
- `active` (boolean): Filter by active status

#### Create Office
```
POST /api/v1/backoffice/offices
```

**Request Body:**
```json
{
  "name": "string (required)",
  "code": "string (required, unique)",
  "description": "string (optional)",
  "address": "string (optional)",
  "phone": "string (optional)",
  "email": "string (optional)",
  "company_id": "integer (required)",
  "is_active": "boolean (optional)"
}
```

### Department Management

#### List Departments
```
GET /api/v1/backoffice/departments
```

**Parameters:**
- `office_id` (integer): Filter by office
- `company_id` (integer): Filter by company
- `parent_id` (integer): Filter by parent department

#### Create Department
```
POST /api/v1/backoffice/departments
```

**Request Body:**
```json
{
  "name": "string (required)",
  "code": "string (required, unique)",
  "description": "string (optional)",
  "office_id": "integer (required)",
  "parent_department_id": "integer (optional)",
  "is_active": "boolean (optional)"
}
```

### Staff Management

#### List Staff
```
GET /api/v1/backoffice/staff
```

**Parameters:**
- `company_id` (integer): Filter by company
- `office_id` (integer): Filter by office
- `department_id` (integer): Filter by department
- `search` (string): Search in name, employee ID, or email
- `active` (boolean): Filter by active status

#### Create Staff
```
POST /api/v1/backoffice/staff
```

**Request Body:**
```json
{
  "name": "string (required)",
  "email": "string (required, unique)",
  "phone": "string (optional)",
  "hire_date": "date (required)",
  "resignation_date": "date (optional)",
  "company_id": "integer (required)",
  "office_id": "integer (optional)",
  "department_id": "integer (optional)",
  "position_id": "integer (optional)",
  "supervisor_id": "integer (optional)",
  "is_active": "boolean (optional)"
}
```

#### Create Staff Transfer
```
POST /api/v1/backoffice/staff/{id}/transfers
```

**Request Body:**
```json
{
  "to_office_id": "integer (optional)",
  "to_department_id": "integer (optional)",
  "to_position_id": "integer (optional)",
  "to_supervisor_id": "integer (optional)",
  "effective_date": "date (required)",
  "reason": "string (optional)",
  "notes": "string (optional)"
}
```

## Error Codes

- `400` - Bad Request: Invalid request format
- `401` - Unauthorized: Authentication required
- `403` - Forbidden: Access denied
- `404` - Not Found: Resource not found
- `422` - Unprocessable Entity: Validation errors
- `500` - Internal Server Error: Server error

## Validation Rules

### Company Validation
- `name`: Required, string, max 255 characters
- `code`: Optional, string, max 50 characters, unique
- `parent_company_id`: Optional, must exist in companies table

### Office Validation
- `name`: Required, string, max 255 characters
- `code`: Required, string, max 50 characters, unique
- `company_id`: Required, must exist in companies table
- `email`: Optional, valid email format

### Department Validation
- `name`: Required, string, max 255 characters
- `code`: Required, string, max 50 characters, unique
- `office_id`: Required, must exist in offices table
- `parent_department_id`: Optional, must exist in departments table

### Staff Validation
- `name`: Required, string, max 255 characters
- `email`: Required, valid email, unique
- `hire_date`: Required, date, cannot be in future
- `resignation_date`: Optional, date, must be after hire_date
- `company_id`: Required, must exist in companies table