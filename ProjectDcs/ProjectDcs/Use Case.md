# ksf_ModulesDAO - Use Case Specification

## Document Information

| Field | Value |
|-------|-------|
| **Document ID** | UCD-DAO-001 |
| **Module** | ksf_ModulesDAO |
| **Project** | DAO Abstraction Layer |
| **Version** | 1.0.0 |
| **Author** | KS Fraser Development Team |
| **Created** | 2024-01-15 |

---

## 1. Use Case Overview

### 1.1 Actor Definitions

| Actor | Description |
|-------|-------------|
| **Module Developer** | Uses DAO abstraction for data access |
| **System Integrator** | Configures adapters for platforms |
| **Legacy Migrator** | Migrating old code to DAO pattern |

### 1.2 Use Case Summary

| UC ID | Use Case | Actor | Priority |
|-------|----------|-------|----------|
| UC-001 | Create Database Adapter | Module Developer | High |
| UC-002 | Execute Query | Module Developer | High |
| UC-003 | Build SELECT Query | Legacy Migrator | High |
| UC-004 | Build INSERT Query | Legacy Migrator | High |
| UC-005 | Use Key/Value Store | Module Developer | Medium |
| UC-006 | Create Repository | Module Developer | Medium |

---

## 2. Use Case Details

### 2.1 UC-001: Create Database Adapter

**Primary Actor**: Module Developer  
**Priority**: High

#### Description
Create appropriate database adapter for target platform.

#### Basic Flow
```
1. Developer calls DatabaseAdapterFactory::create()
2. System determines adapter type from driver parameter
3. System creates adapter instance
4. System returns adapter implementing DbAdapterInterface
```

#### Example
```php
// FrontAccounting context
$db = DatabaseAdapterFactory::create('fa', '0_');

// WordPress context
$db = DatabaseAdapterFactory::create('pdo', 'wp_');

// Standalone context
$db = DatabaseAdapterFactory::create('mysql', 'app_');
```

#### Post-Conditions
- Adapter instance returned
- Interface contract satisfied

---

### 2.2 UC-002: Execute Query

**Primary Actor**: Module Developer  
**Priority**: High

#### Description
Execute database query using adapter.

#### Basic Flow
```
1. Developer has DbAdapterInterface instance
2. Developer calls query() with SQL and parameters
3. Adapter prepares statement
4. Adapter binds parameters
5. Adapter executes query
6. Adapter returns results as array
```

#### Example
```php
$db = DatabaseAdapterFactory::create('fa', '0_');

// SELECT query
$results = $db->query(
    "SELECT * FROM :table WHERE status = :status",
    ['table' => 'customers', 'status' => 'active']
);

// INSERT query
$db->execute(
    "INSERT INTO :table (name, email) VALUES (:name, :email)",
    ['table' => 'customers', 'name' => 'John', 'email' => 'john@example.com']
);

$id = $db->lastInsertId();
```

#### Post-Conditions
- Query executed against database
- Results returned as associative arrays
- Last insert ID available after INSERT

---

### 2.3 UC-003: Build SELECT Query

**Primary Actor**: Legacy Migrator  
**Priority**: High

#### Description
Convert legacy array-based SQL to parameterized queries.

#### Basic Flow
```
1. Migrator has legacy fields_array and where conditions
2. Migrator calls LegacyArraySqlBuilder::buildSelect()
3. Builder converts arrays to SQL and parameters
4. Builder returns BuiltQuery instance
5. Migrator executes via adapter
```

#### Example
```php
// Old legacy code
$sql = "SELECT id, name FROM customers 
        WHERE status = " . db_escape($status) . " 
        AND city LIKE '%" . db_escape($city) . "%'";

// New DAO approach
$query = LegacyArraySqlBuilder::buildSelect(
    ['id', 'name'],
    ['customers'],
    [
        'status' => ['eq', $status],
        'city' => ['like', $city]
    ]
);

$db = DatabaseAdapterFactory::create('fa');
$results = $db->query($query->getSql(), $query->getParams());
```

#### Post-Conditions
- SQL injection prevented
- Legacy code migrated to DAO pattern

---

### 2.4 UC-004: Build INSERT Query

**Primary Actor**: Legacy Migrator  
**Priority**: High

#### Description
Build INSERT from legacy object to DAO pattern.

#### Basic Flow
```
1. Migrator has object with properties
2. Migrator has legacy fields_array
3. Migrator calls buildInsert()
4. Builder generates parameterized INSERT
5. Migrator executes via adapter
```

#### Example
```php
// Legacy array
$fieldsArray = [
    ['name' => 'id', 'type' => 'INT', 'auto_increment' => true],
    ['name' => 'name', 'type' => 'VARCHAR(100)'],
    ['name' => 'email', 'type' => 'VARCHAR(255)'],
];

// Object properties
$objectVars = [
    'name' => 'John Smith',
    'email' => 'john@example.com'
];

// Build and execute
$query = LegacyArraySqlBuilder::buildInsert('customers', $fieldsArray, $objectVars);
$db->execute($query->getSql(), $query->getParams());
```

#### Post-Conditions
- Record inserted with proper escaping
- Last insert ID retrieved

---

### 2.5 UC-005: Use Key/Value Store

**Primary Actor**: Module Developer  
**Priority**: Medium

#### Description
Store and retrieve configuration using file-based stores.

#### Basic Flow
```
1. Developer calls KeyValueStoreFactory::create()
2. Developer performs get/set/delete operations
3. Store persists to file
```

#### Example
```php
// Create JSON store
$store = KeyValueStoreFactory::create('json', '/config/app.json');

// Set values
$store->set('api_key', 'secret123');
$store->set('theme', 'dark');

// Get values
$apiKey = $store->get('api_key');
$theme = $store->get('theme', 'light'); // with default

// Check existence
if ($store->exists('api_key')) {
    // ...
}

// Delete
$store->delete('old_key');
```

#### Post-Conditions
- Data persisted to file
- Configuration available across requests

---

### 2.6 UC-006: Create Repository

**Primary Actor**: Module Developer  
**Priority**: Medium

#### Description
Create repository class using DAO adapter.

#### Basic Flow
```
1. Developer creates repository class
2. Injects DbAdapterInterface via constructor
3. Repository implements CRUD methods using adapter
4. Consumer uses repository without database knowledge
```

#### Example
```php
class CustomerRepository {
    private DbAdapterInterface $db;
    
    public function __construct(DbAdapterInterface $db) {
        $this->db = $db;
    }
    
    public function find(int $id): ?array {
        $results = $this->db->query(
            "SELECT * FROM customers WHERE id = :id",
            ['id' => $id]
        );
        return $results[0] ?? null;
    }
    
    public function findAll(array $filters = []): array {
        $where = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }
        
        $sql = "SELECT * FROM customers";
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        return $this->db->query($sql, $params);
    }
    
    public function save(array $data): int {
        $query = LegacyArraySqlBuilder::buildInsert(
            'customers',
            $this->fieldsArray,
            $data
        );
        $this->db->execute($query->getSql(), $query->getParams());
        return $this->db->lastInsertId();
    }
}
```

#### Post-Conditions
- Repository fully testable with mocks
- Business logic isolated from data access

---

## 3. Requirements Traceability

| Use Case | Requirements | Test Cases |
|----------|--------------|------------|
| UC-001 | FR-011 | TC-011 |
| UC-002 | FR-001, FR-002, FR-003 | TC-001, TC-002, TC-003 |
| UC-003 | FR-004 | TC-004 |
| UC-004 | FR-005, FR-006 | TC-005, TC-006 |
| UC-005 | FR-008, FR-009 | TC-008, TC-009 |
| UC-006 | FR-001, FR-007 | TC-001, TC-007 |

---

**Document Owner**: KS Fraser Development Team  
**Review Status**: Pending