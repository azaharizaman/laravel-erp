# Nexus CRM Package

**Version:** 1.0.0  
**Status:** Phase 1 - Basic CRM (Trait-based)

A progressive CRM package for Nexus ERP that starts simple and scales with your needs.

## Quick Start (Level 1 - 5 minutes)

Add CRM functionality to any Laravel model without migrations:

```php
<?php

use Nexus\Crm\Traits\HasCrm;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasCrm;

    // Define your CRM fields
    public array $crmConfiguration = [
        'first_name' => ['type' => 'string', 'required' => true],
        'last_name' => ['type' => 'string', 'required' => true],
        'email' => ['type' => 'string', 'required' => false],
        'phone' => ['type' => 'string', 'required' => false],
        'company' => ['type' => 'string', 'required' => false],
        'notes' => ['type' => 'text', 'required' => false],
    ];
}
```

### Usage

```php
$user = User::find(1);

// Add a contact
$user->addContact([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'company' => 'Acme Corp',
]);

// Get all contacts
$contacts = $user->getContacts();

// Update a contact
$user->updateContact($contactId, [
    'email' => 'john.doe@example.com',
]);

// Delete a contact
$user->deleteContact($contactId);

// Check permissions
if ($user->crm()->can('create_contact')) {
    // Add contact
}

// Get audit history
$history = $user->crm()->history();
```

## Features (Level 1)

- ✅ **Zero Database Migrations** - Store CRM data in model attributes
- ✅ **Type Validation** - Automatic validation of contact fields
- ✅ **Permission Checks** - Declarative permission system
- ✅ **Event-Driven** - Fires events for all CRM operations
- ✅ **Audit Trail** - Track all changes and operations
- ✅ **Independent Testing** - Fully testable in isolation

## Architecture

### Progressive Disclosure

| Level | Database | Features |
|-------|----------|----------|
| **1** | No | Trait-based CRM with model attributes |
| **2** | Yes | Database-driven leads, opportunities, pipelines |
| **3** | Yes | Enterprise features (SLA, escalation, delegation) |

### Atomic Design

- **Independent Package** - Zero dependencies on other Nexus packages
- **Contract-Driven** - All integrations via interfaces
- **Event-Driven** - Domain events for cross-package communication
- **Headless** - Pure API/CLI, no frontend dependencies

## Installation

```bash
composer require nexus/crm
```

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --provider="Nexus\Crm\CrmServiceProvider"
```

## Testing

```bash
composer test
```

## Roadmap

- **Phase 1** ✅ Basic CRM (Traits)
- **Phase 2** ✅ Sales Automation (Database)
- **Phase 3** 📋 Enterprise Features
- **Phase 4** 📋 Extensibility & Polish

## License

MIT