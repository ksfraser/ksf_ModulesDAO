# ksf_ModulesDAO - Functional Requirements

## Document Information

| Field | Value |
|-------|-------|
| **Document ID** | FRD-DAO-001 |
| **Module** | ksf_ModulesDAO |
| **Project** | DAO Abstraction Layer |
| **Version** | 1.0.0 |
| **Author** | KS Fraser Development Team |
| **Created** | 2024-01-15 |

---

## 1. Functional Requirements

### 2.1 Database Adapters

#### FR-001: PDO Database Adapter
| Requirement ID | FR-001 |
|----------------|--------|
| **Priority** | High |
| **Complexity** | Medium |

**Description**: Provide PDO-based database access.

**Interface Methods**:
```php
public function getDialect(): string;          // Returns 'mysql', 'sqlite', etc.
public function getTablePrefix(): string;      // Returns table prefix
public function query(string $sql, array $params = []): array;
public function execute(string $sql, array $params = []): void;
public function lastInsertId(): ?int;
```

**Business Rules**:
- Uses prepared statements for all queries
- Parameters bound via PDO::PARAM_* types
- Returns array of associative arrays
- Throws exception on SQL error

---

#### FR-002: MySQL Database Adapter
| Requirement ID | FR-002 |
|----------------|--------|
| **Priority** | High |

**Description**: Provide MySQLi-based database access.

**Features**:
- Native MySQLi prepared statements
- Same interface as PDO adapter
- Connection persistence option

---

#### FR-003: FrontAccounting Adapter
| Requirement ID | FR-003 |
|----------------|--------|
| **Priority** | High |

**Description**: Provide FrontAccounting database access using FA's functions.

**Features**:
- Uses FA's `db_query()` function
- Uses FA's `db_escape()` function
- Table prefix support
- Transaction support (no-op for FA compatibility)

**Additional Methods**:
```php
public function escape(string $value): string;
public function beginTransaction(): void;
public function commit(): void;
public function rollback(): void;
```

---

### 2.2 SQL Building

#### FR-004: LegacyArraySqlBuilder - SELECT
| Requirement ID | FR-004 |
|----------------|--------|
| **Priority** | High |

**Description**: Build SELECT statements from legacy array format.

**Method Signature**:
```php
public static function buildSelect(
    array $select,
    array $from,
    ?array $where = null,
    ?array $groupBy = null,
    ?array $having = null,
    ?array $orderBy = null,
    ?int $limit = null,
    ?int $offset = null
): BuiltQuery;
```

**Where Operators**:
| Operator | SQL | Example |
|----------|-----|---------|
| eq | = | `['status' => ['eq', 'active']]` |
| neq | <> | `['status' => ['neq', 'deleted']]` |
| lt | < | `['age' => ['lt', 18]]` |
| lte | <= | `['age' => ['lte', 65]]` |
| gt | > | `['score' => ['gt', 100]]` |
| gte | >= | `['score' => ['gte', 50]]` |
| like | LIKE | `['name' => ['like', 'John']]` |
| in | IN | `['id' => ['in', [1,2,3]]]` |
| between | BETWEEN | `['date' => ['between', '2024-01-01', '2024-12-31']]` |

**Usage**:
```php
$query = LegacyArraySqlBuilder::buildSelect(
    ['id', 'name', 'email'],
    ['customers'],
    ['status' => ['eq', 'active'], 'city' => ['like', 'New']],
    null, null,
    ['created_at' => 'DESC'],
    50
);
```

---

#### FR-005: LegacyArraySqlBuilder - INSERT
| Requirement ID | FR-005 |
|----------------|--------|
| **Priority** | High |

**Description**: Build INSERT statements from legacy format.

**Method Signature**:
```php
public static function buildInsert(
    string $tableName,
    array $fieldsArray,
    array $objectVars,
    bool $ignore = true
): BuiltQuery;
```

**Usage**:
```php
$query = LegacyArraySqlBuilder::buildInsert(
    'customers',
    $fieldsArray,  // From legacy fields_array
    ['name' => 'John', 'email' => 'john@example.com']
);
// Result: INSERT IGNORE INTO customers (name, email) VALUES (:v0, :v1)
```

---

#### FR-006: LegacyArraySqlBuilder - UPDATE
| Requirement ID | FR-006 |
|----------------|--------|
| **Priority** | High |

**Description**: Build UPDATE statements from legacy format.

**Method Signature**:
```php
public static function buildUpdate(
    string $tableName,
    string $primaryKey,
    array $fieldsArray,
    array $objectVars
): BuiltQuery;
```

---

#### FR-007: BuiltQuery Class
| Requirement ID | FR-007 |
|----------------|--------|
| **Priority** | High |

**Description**: Container for built SQL with parameters.

**Properties**:
```php
private string $sql;           // The SQL string with placeholders
private array $params;         // Named parameters
```

**Methods**:
```php
public function getSql(): string;
public function getParams(): array;
```

---

### 2.3 Key/Value Stores

#### FR-008: Key Value Store Interface
| Requirement ID | FR-008 |
|----------------|--------|
| **Priority** | Medium |

**Interface**:
```php
interface KeyValueStoreInterface {
    public function get(string $key): mixed;
    public function set(string $key, mixed $value): void;
    public function delete(string $key): void;
    public function exists(string $key): bool;
}
```

---

#### FR-009: JSON File Adapter
| Requirement ID | FR-009 |
|----------------|--------|
| **Priority** | Medium |

**Description**: File-based key/value store using JSON format.

**Features**:
- Stores data as JSON object
- Auto-save on set
- Load on first access
- File locking for concurrent access

**Example File**:
```json
{
    "api_key": "secret123",
    "settings": {
        "theme": "dark",
        "language": "en"
    }
}
```

---

#### FR-010: Other File Adapters
| Requirement ID | FR-010 |
|----------------|--------|
| **Priority** | Low |

**Supported Formats**:
| Format | Extension | Notes |
|--------|----------|-------|
| INI | .ini | PHP ini format |
| CSV | .csv | Key,value per line |
| XML | .xml | Simple XML structure |
| YAML | .yml, .yaml | If yaml extension available |

---

### 2.4 Factory Classes

#### FR-011: DatabaseAdapterFactory
| Requirement ID | FR-011 |
|----------------|--------|
| **Priority** | High |

**Description**: Factory for creating database adapters.

**Method**:
```php
public static function create(
    ?string $driver = null,
    string $tablePrefix = '0_'
): DbAdapterInterface;
```

**Driver Mapping**:
| Driver | Adapter | Notes |
|--------|---------|-------|
| null | FA (default) | FrontAccounting |
| 'pdo' | PdoDbAdapter | PDO wrapper |
| 'mysql' | MysqlDbAdapter | MySQLi wrapper |
| 'mysqli' | MysqlDbAdapter | MySQLi alias |
| 'fa' | FrontAccountingDbAdapter | FA wrapper |
| 'frontaccounting' | FrontAccountingDbAdapter | FA alias |

---

#### FR-012: KeyValueStoreFactory
| Requirement ID | FR-012 |
|----------------|--------|
| **Priority** | Medium |

**Description**: Factory for creating key/value stores.

**Method**:
```php
public static function create(string $format, string $filePath): KeyValueStoreInterface;
```

**Format Mapping**:
| Format | Adapter |
|--------|---------|
| 'json' | JsonFileAdapter |
| 'ini' | IniFileAdapter |
| 'csv' | CsvFileAdapter |
| 'xml' | XmlFileAdapter |
| 'yaml' | YamlFileAdapter |

---

## 2. Data Requirements

### 2.1 Input: SQL Building

| Parameter | Type | Description |
|-----------|------|-------------|
| select | array | Column names |
| from | array | Table names |
| where | array | Filter conditions |
| groupBy | array | GROUP BY columns |
| having | array | HAVING conditions |
| orderBy | array | ORDER BY columns |
| limit | int | LIMIT count |
| offset | int | OFFSET count |

### 2.2 Output: BuiltQuery

| Field | Type | Description |
|-------|------|-------------|
| sql | string | SQL with placeholders |
| params | array | Named parameters |

---

## 3. Non-Functional Requirements

### 3.1 Performance
| Metric | Target |
|--------|--------|
| Adapter creation | < 10ms |
| Simple query | < 50ms |
| Batch query (100 rows) | < 200ms |
| Key/value get/set | < 10ms |

### 3.2 Compatibility
| Requirement | Specification |
|------------|---------------|
| PHP Version | 7.3+ |
| MySQL | 5.7+ |
| PDO | All drivers |

---

**Document Owner**: KS Fraser Development Team  
**Review Status**: Pending