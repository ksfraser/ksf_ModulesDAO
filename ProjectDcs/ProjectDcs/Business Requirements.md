# ksf_ModulesDAO - Business Requirements

## Document Information

| Field | Value |
|-------|-------|
| **Document ID** | BRD-DAO-001 |
| **Module** | ksf_ModulesDAO |
| **Project** | DAO Abstraction Layer |
| **Version** | 1.0.0 |
| **Author** | KS Fraser Development Team |
| **Created** | 2024-01-15 |
| **Status** | Draft |

---

## 1. Project Overview

### 1.1 Project Name
**ksf_ModulesDAO** - Cross-Platform DAO Abstraction Layer

### 1.2 Project Type
Data Access Layer Abstraction Framework

### 1.3 Core Functionality Summary
The ksf_ModulesDAO module provides a cross-platform data access abstraction layer that enables modules to read/write data via multiple backends including generic database tables, WordPress APIs, SuiteCRM settings, FrontAccounting databases, and file-backed key/value stores (INI/JSON/XML/CSV/YAML).

### 1.4 Target Users
- **Module Developers**: Building platform-agnostic modules
- **System Integrators**: Creating adapters for different platforms
- **Database Administrators**: Managing data access patterns

---

## 2. Problem Statement

### 2.1 Business Problem
Cross-platform module development requires different data access patterns:
- FrontAccounting uses custom database functions
- WordPress uses options/settings APIs
- SuiteCRM uses Administration settings
- Some modules use file-based storage

### 2.2 Current Solution Gaps

| Gap | Impact |
|-----|--------|
| Platform lock-in | Modules work only on one platform |
| Code duplication | Same queries written multiple times |
| Testing difficulty | Database dependencies hard to mock |
| Migration complexity | Moving between platforms requires rewrites |

### 2.3 Opportunity
ksf_ModulesDAO provides:
- Unified interface for data access
- Multiple adapter implementations
- Factory pattern for adapter selection
- Legacy migration helpers
- Easy mocking for testing

---

## 3. Project Scope

### 3.1 In-Scope Features

#### Adapter Implementations
1. **Generic Database (PDO)**
   - PDO-based database access
   - Parameterized queries
   - Transaction support

2. **MySQL/MariaDB**
   - Direct MySQLi access
   - Native prepared statements
   - Connection pooling

3. **FrontAccounting**
   - FA-specific adapter
   - Uses FA's db_query functions
   - Table prefix support

4. **Key/Value Stores**
   - INI file adapter
   - JSON file adapter
   - XML file adapter
   - CSV file adapter
   - YAML file adapter

5. **Platform Adapters**
   - WordPress options API
   - SuiteCRM Administration
   - FrontAccounting sys prefs

#### SQL Building
6. **LegacyArraySqlBuilder**
   - Compatible with historical array-based SQL
   - Supports operators: lt, lte, gt, gte, eq, neq, like, in, between
   - SELECT, INSERT, UPDATE, DELETE, CREATE TABLE generation

7. **BuiltQuery**
   - SQL string with parameters container
   - Easy debugging
   - Safe parameter substitution

#### Factories
8. **DatabaseAdapterFactory**
   - Creates appropriate DB adapter
   - Driver-based selection
   - Table prefix configuration

9. **KeyValueStoreFactory**
   - Creates appropriate KV store adapter
   - Format-based selection
   - File path configuration

#### Interfaces
10. **DbAdapterInterface**
    - Core database operations
    - Query execution
    - Transaction management

11. **RecordStoreInterface**
    - CRUD operations
    - Find by ID
    - Find all with filters

12. **KeyValueStoreInterface**
    - Get/Set operations
    - Delete operations
    - Exists checks

### 3.2 Out-of-Scope Features
- ORM functionality
- Query builder (beyond SQL building)
- Caching layer
- Connection pooling

### 3.3 Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    ksf_ModulesDAO Module                     │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────┐ │
│  │  Interfaces     │  │   Adapters     │  │  Factories  │ │
│  │ - DbAdapter     │  │ - PDO          │  │ - Database  │ │
│  │ - RecordStore   │  │ - MySQL        │  │ - KeyValue  │ │
│  │ - KeyValueStore │  │ - FA           │  │             │ │
│  └─────────────────┘  │ - File formats │  │             │ │
│                       └─────────────────┘ └─────────────┘ │
│                                                              │
│  ┌─────────────────────────────────────────────────────────┐│
│  │  SQL Building                                             ││
│  │  - LegacyArraySqlBuilder                                ││
│  │  - BuiltQuery                                           ││
│  └─────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
    ┌───────────┐       ┌───────────┐       ┌───────────┐
    │  MySQL    │       │ FrontAcc  │       │  Files    │
    └───────────┘       └───────────┘       └───────────┘
```

---

## 4. Module Features

### 4.1 Database Adapters

#### F-001: PDO Adapter
| Attribute | Value |
|-----------|-------|
| **Feature ID** | F-001 |
| **Priority** | High |
| **Complexity** | Medium |

**Specification**:
- PDO-based database access
- Supports MySQL, SQLite, PostgreSQL
- Prepared statement support
- Parameter binding

**Interface Methods**:
```php
public function getDialect(): string;
public function getTablePrefix(): string;
public function query(string $sql, array $params = []): array;
public function execute(string $sql, array $params = []): void;
public function lastInsertId(): ?int;
```

#### F-002: FrontAccounting Adapter
| Attribute | Value |
|-----------|-------|
| **Feature ID** | F-002 |
| **Priority** | High |

**Specification**:
- Uses FrontAccounting's db_query function
- Table prefix support
- Parameter substitution
- Transaction passthrough (no-op)

---

### 4.2 SQL Building

#### F-003: LegacyArraySqlBuilder
| Attribute | Value |
|-----------|-------|
| **Feature ID** | F-003 |
| **Priority** | High |

**Purpose**: Migrate legacy FA code to composable architecture

**Supported Operations**:
| Operator | SQL Equivalent | Notes |
|----------|---------------|-------|
| lt | < | Less than |
| lte | <= | Less than or equal |
| gt | > | Greater than |
| gte | >= | Greater than or equal |
| eq | = | Equals |
| neq | <> | Not equals |
| like | LIKE | Pattern match |
| in | IN | Set membership |
| between | BETWEEN | Range |
| betweenf | BETWEEN | Formatted range |

**Methods**:
```php
public static function buildSelect(...): BuiltQuery;
public static function buildInsert(...): BuiltQuery;
public static function buildUpdate(...): BuiltQuery;
public static function buildDelete(...): BuiltQuery;
public static function buildCreateTableSql(...): string;
public static function buildAlterTableAddColumnsSql(...): string;
```

---

### 4.3 Key/Value Stores

#### F-004: File-Based Stores
| Attribute | Value |
|-----------|-------|
| **Feature ID** | F-004 |
| **Priority** | Medium |

**Supported Formats**:
| Format | File Extension | Adapter |
|--------|---------------|---------|
| INI | .ini | IniFileAdapter |
| JSON | .json | JsonFileAdapter |
| XML | .xml | XmlFileAdapter |
| CSV | .csv | CsvFileAdapter |
| YAML | .yml, .yaml | YamlFileAdapter |

**Common Interface**:
```php
public function get(string $key): mixed;
public function set(string $key, mixed $value): void;
public function delete(string $key): void;
public function exists(string $key): bool;
public function getAll(): array;
```

---

### 4.4 Factory Patterns

#### F-005: DatabaseAdapterFactory
| Attribute | Value |
|-----------|-------|
| **Feature ID** | F-005 |
| **Priority** | High |

**Supported Drivers**:
| Driver | Adapter |
|--------|---------|
| pdo | PdoDbAdapter |
| mysql/mysqli | MysqlDbAdapter |
| fa/frontaccounting | FrontAccountingDbAdapter |

**Usage**:
```php
$adapter = DatabaseAdapterFactory::create('pdo', '0_');
$adapter = DatabaseAdapterFactory::create('fa', '0_');
```

#### F-006: KeyValueStoreFactory
| Attribute | Value |
|-----------|-------|
| **Feature ID** | F-006 |
| **Priority** | Medium |

**Usage**:
```php
$store = KeyValueStoreFactory::create('json', '/path/to/storage.json');
$store->set('key', 'value');
```

---

## 5. Integration Dependencies

### 5.1 Platform Support

| Platform | Database Adapter | Key/Value Store |
|----------|-----------------|-----------------|
| FrontAccounting | FA Adapter | N/A |
| WordPress | PDO/MySQL | WordPress Options API |
| SuiteCRM | PDO/MySQL | SuiteCRM Settings |
| Standalone | PDO/MySQL | File-based stores |

### 5.2 Database Support

| Database | Support Level |
|----------|---------------|
| MySQL 5.7+ | Primary |
| MariaDB 10.3+ | Full |
| SQLite 3 | Via PDO |
| PostgreSQL | Via PDO |

---

## 6. Usage Examples

### 6.1 Database Adapter Usage

```php
use Ksfraser\ModulesDAO\Factory\DatabaseAdapterFactory;

// Create adapter
$db = DatabaseAdapterFactory::create('pdo', '0_');

// Execute query
$results = $db->query(
    "SELECT * FROM :table WHERE status = :status",
    ['table' => 'customers', 'status' => 'active']
);

// Insert record
$db->execute(
    "INSERT INTO :table (name, email) VALUES (:name, :email)",
    ['name' => 'John', 'email' => 'john@example.com']
);

// Get last insert ID
$id = $db->lastInsertId();
```

### 6.2 Legacy SQL Builder Usage

```php
use Ksfraser\ModulesDAO\Sql\LegacyArraySqlBuilder;

// Build SELECT
$query = LegacyArraySqlBuilder::buildSelect(
    ['id', 'name', 'email'],
    ['customers'],
    ['status' => ['eq', 'active']],
    null, null,
    ['created_at' => 'DESC'],
    50
);

// Execute
$db = DatabaseAdapterFactory::create('fa');
$results = $db->query($query->getSql(), $query->getParams());
```

### 6.3 Key/Value Store Usage

```php
use Ksfraser\ModulesDAO\Factory\KeyValueStoreFactory;

// Create JSON store
$store = KeyValueStoreFactory::create('json', '/data/config.json');

// Set value
$store->set('api_key', 'secret123');

// Get value
$apiKey = $store->get('api_key');

// Check exists
if ($store->exists('api_key')) {
    // ...
}
```

---

## 7. Success Criteria

### 7.1 Functional Criteria
- [ ] All adapter types functional
- [ ] Factory pattern working correctly
- [ ] Legacy SQL builder compatible with array syntax
- [ ] All SQL operations (SELECT, INSERT, UPDATE, DELETE) working
- [ ] Key/value stores functional for all formats

### 7.2 Technical Criteria
- [ ] Interfaces properly defined
- [ ] Adapters implement full interface
- [ ] No breaking changes to existing code
- [ ] PHP 7.3+ compatibility

### 7.3 Testing Criteria
- [ ] Unit tests for all adapters
- [ ] Integration tests with actual databases
- [ ] Mock testing enabled
- [ ] Code coverage > 80%

---

**Document Owner**: KS Fraser Development Team  
**Approval Status**: Pending Review