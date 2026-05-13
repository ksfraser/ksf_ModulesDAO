# ksf_ModulesDAO - Architecture

## Document Information

| Field | Value |
|-------|-------|
| **Document ID** | ARCH-DAO-001 |
| **Module** | ksf_ModulesDAO |
| **Project** | DAO Abstraction Layer |
| **Version** | 1.0.0 |
| **Author** | KS Fraser Development Team |
| **Created** | 2024-01-15 |

---

## 1. Technical Architecture Overview

### 1.1 Architecture Pattern
The ksf_ModulesDAO module follows **Adapter Pattern** combined with **Factory Pattern** and **Strategy Pattern** for database abstraction. It uses interface contracts to ensure adapter interoperability.

### 1.2 Module Classification
- **Type**: Data Access Layer Abstraction
- **Namespace**: `Ksfraser\ModulesDAO`
- **Platform**: Cross-platform (PHP 7.3+)

### 1.3 Architecture Layers

```
┌──────────────────────────────────────────────────────────────┐
│                    CONSUMER LAYER                           │
│  ┌────────────────────────────────────────────────────────┐  │
│  │  Modules using DAO abstraction                         │  │
│  │  - FA modules                                          │  │
│  │  - WordPress plugins                                   │  │
│  │  - Standalone applications                            │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────┐
│                    FACTORY LAYER                            │
│  ┌────────────────────────────────────────────────────────┐  │
│  │  DatabaseAdapterFactory                               │  │
│  │  - Creates database adapters by driver               │  │
│  │  KeyValueStoreFactory                                 │  │
│  │  - Creates key/value stores by format                 │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────┐
│                    ADAPTER LAYER                             │
│  ┌────────────────────────────────────────────────────────┐  │
│  │  Database Adapters:                                   │  │
│  │  - PdoDbAdapter                                       │  │
│  │  - MysqlDbAdapter                                     │  │
│  │  - FrontAccountingDbAdapter                            │  │
│  │                                                        │  │
│  │  Key/Value Store Adapters:                           │  │
│  │  - JsonFileAdapter                                    │  │
│  │  - IniFileAdapter                                     │  │
│  │  - CsvFileAdapter                                     │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────┐
│                    INTERFACE LAYER                           │
│  ┌────────────────────────────────────────────────────────┐  │
│  │  DbAdapterInterface                                  │  │
│  │  RecordStoreInterface                                │  │
│  │  KeyValueStoreInterface                               │  │
│  │  StoreAvailabilityInterface                          │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────┐
│                    INFRASTRUCTURE LAYER                     │
│  ┌────────────────────────────────────────────────────────┐  │
│  │  Databases: MySQL, PostgreSQL, SQLite                 │  │
│  │  Files: INI, JSON, XML, CSV, YAML                     │  │
│  │  Platform APIs: FrontAccounting, WordPress            │  │
│  └────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────┘
```

---

## 2. Class Diagram

### 2.1 Core Interfaces

```
┌─────────────────────────────────────────────────────────────┐
│                  DbAdapterInterface                        │
├─────────────────────────────────────────────────────────────┤
│ + getDialect(): string                                     │
│ + getTablePrefix(): string                                 │
│ + query(sql, params): array                                │
│ + execute(sql, params): void                              │
│ + lastInsertId(): ?int                                    │
└─────────────────────────────────────────────────────────────┘
         ▲
         │ implements
         │
┌────────┴────────────────────────────────────────────────┐
│         PdoDbAdapter / MysqlDbAdapter / FA Adapter       │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│               RecordStoreInterface                         │
├─────────────────────────────────────────────────────────────┤
│ + find(id): ?array                                        │
│ + findAll(filters): array                                 │
│ + insert(record): string                                   │
│ + update(id, record): void                                │
│ + delete(id): void                                        │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              KeyValueStoreInterface                        │
├─────────────────────────────────────────────────────────────┤
│ + get(key): mixed                                          │
│ + set(key, value): void                                   │
│ + delete(key): void                                       │
│ + exists(key): bool                                       │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Adapter Implementations

```
┌─────────────────────────────────────────────────────────────┐
│                    PdoDbAdapter                            │
├─────────────────────────────────────────────────────────────┤
│ - pdo: PDO                                                 │
│ - tablePrefix: string                                      │
├─────────────────────────────────────────────────────────────┤
│ + getDialect(): string                                     │
│ + getTablePrefix(): string                                 │
│ + query(sql, params): array                                │
│ + execute(sql, params): void                              │
│ + lastInsertId(): ?int                                    │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│               FrontAccountingDbAdapter                     │
├─────────────────────────────────────────────────────────────┤
│ - tablePrefix: string                                      │
├─────────────────────────────────────────────────────────────┤
│ + getDialect(): string                                     │
│ + getTablePrefix(): string                                 │
│ + query(sql, params): array                                │
│ + execute(sql, params): void                              │
│ + lastInsertId(): ?int                                    │
│ + escape(value): string                                  │
│ + beginTransaction(): void                                │
│ + commit(): void                                          │
│ + rollback(): void                                         │
└─────────────────────────────────────────────────────────────┘
```

### 2.3 Factory Classes

```
┌─────────────────────────────────────────────────────────────┐
│              DatabaseAdapterFactory                        │
├─────────────────────────────────────────────────────────────┤
│ + create(driver, tablePrefix): DbAdapterInterface          │
├─────────────────────────────────────────────────────────────┤
│ Supported drivers:                                         │
│   - 'pdo' → PdoDbAdapter                                 │
│   - 'mysql', 'mysqli' → MysqlDbAdapter                    │
│   - 'fa', 'frontaccounting' → FrontAccountingDbAdapter    │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              KeyValueStoreFactory                          │
├─────────────────────────────────────────────────────────────┤
│ + create(format, filePath): KeyValueStoreInterface         │
├─────────────────────────────────────────────────────────────┤
│ Supported formats:                                         │
│   - 'json' → JsonFileAdapter                              │
│   - 'ini' → IniFileAdapter                                │
│   - 'csv' → CsvFileAdapter                                │
│   - 'xml' → XmlFileAdapter                                │
│   - 'yaml' → YamlFileAdapter                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Data Flow

### 3.1 Query Execution Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  Module     │    │  Factory   │    │  Adapter    │    │  Database   │
│  (Client)   │───▶│  (Create)  │───▶│  (Execute)  │───▶│  (Query)    │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
     │                  │                  │                  │
     │ 1. getAdapter()  │                  │                  │
     │ 2. driver='fa'   │                  │                  │
     │                  │ 3. Create        │                  │
     │                  │    FrontAccounting│                  │
     │                  │    DbAdapter     │                  │
     │                  │                  │ 4. query(sql)    │
     │                  │                  │                  │
     │ 5. Results       │                  │                  │
     │    returned      │                  │                  │
     └──────────────────┴──────────────────┴──────────────────┘
```

### 3.2 SQL Building Flow

```
┌─────────────┐    ┌────────────────────┐    ┌─────────────┐
│  Legacy    │    │ LegacyArraySql     │    │  Adapter    │
│  fields_    │───▶│ Builder            │───▶│  (Execute)  │
│  array      │    │ (BuildQuery)      │    │             │
└─────────────┘    └────────────────────┘    └─────────────┘
                         │
                         │ buildSelect()
                         │ buildInsert()
                         │ buildUpdate()
                         ▼
                  ┌─────────────┐
                  │ BuiltQuery  │
                  │ - sql       │
                  │ - params    │
                  └─────────────┘
```

### 3.3 Key/Value Store Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  Module     │    │  Factory   │    │  File       │
│  (Client)   │───▶│  (Create)  │───▶│  (Read/    │
└─────────────┘    └─────────────┘    │  Write)     │
                                       └─────────────┘
```

---

## 4. Database Schema

### 4.1 No Native Tables
ksf_ModulesDAO is a pure adapter layer - it does not create its own database tables. It provides adapters to work with whatever tables exist in the target platform.

### 4.2 Example Usage Tables

#### FrontAccounting Style
```sql
CREATE TABLE IF NOT EXISTS 0_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    active INT DEFAULT 1
);
```

#### WordPress Style (via PDO)
```sql
CREATE TABLE IF NOT EXISTS wp_ksf_customers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) DEFAULT 1
);
```

---

## 5. File Structure

### 5.1 Module Directory Structure

```
ksf_ModulesDAO/
├── ProjectDcs/
│   ├── ProjectDcs/
│   │   ├── Business Requirements.md
│   │   ├── Architecture.md
│   │   ├── Functional Requirements.md
│   │   ├── Use Case.md
│   │   ├── Test Plan.md
│   │   └── UAT Plan.md
│   ├── BABOK/
│   ├── UML/
│   └── RTM/
├── src/
│   └── Ksfraser/
│       └── ModulesDAO/
│           ├── ksf_ModulesDAO.php      (Main entry)
│           ├── Contracts/
│           │   ├── KeyValueStoreInterface.php
│           │   ├── RecordStoreInterface.php
│           │   └── StoreAvailabilityInterface.php
│           ├── Db/
│           │   ├── DbAdapterInterface.php
│           │   ├── PdoDbAdapter.php
│           │   ├── MysqlDbAdapter.php
│           │   └── FrontAccountingDbAdapter.php
│           ├── Factory/
│           │   ├── DatabaseAdapterFactory.php
│           │   └── KeyValueStoreFactory.php
│           ├── Sql/
│           │   ├── BuiltQuery.php
│           │   └── LegacyArraySqlBuilder.php
│           └── Stores/
│               ├── JsonFileAdapter.php
│               └── ...
├── tests/
├── composer.json
└── README.md
```

---

## 6. Design Patterns

### 6.1 Adapter Pattern
Each database/backend has a dedicated adapter implementing a common interface:

```php
interface DbAdapterInterface {
    public function query(string $sql, array $params = []): array;
    public function execute(string $sql, array $params = []): void;
}

// Multiple implementations
class PdoDbAdapter implements DbAdapterInterface { ... }
class FrontAccountingDbAdapter implements DbAdapterInterface { ... }
```

### 6.2 Factory Pattern
Factories create the appropriate adapter based on configuration:

```php
class DatabaseAdapterFactory {
    public static function create(?string $driver, string $tablePrefix): DbAdapterInterface {
        return match($driver) {
            'pdo' => new PdoDbAdapter($tablePrefix),
            'fa' => new FrontAccountingDbAdapter($tablePrefix),
            default => throw new InvalidArgumentException(...),
        };
    }
}
```

### 6.3 Strategy Pattern
Different SQL building strategies handled by the same interface.

### 6.4 Repository Pattern (Enabling)
Modules can implement repositories using the DAO adapters:

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
}
```

---

## 7. Integration Points

### 7.1 FrontAccounting Integration

```php
// In FA context
$db = DatabaseAdapterFactory::create('fa', '0_');
$results = $db->query("SELECT * FROM customers WHERE active = 1");
```

### 7.2 WordPress Integration

```php
// In WordPress context
$db = DatabaseAdapterFactory::create('pdo', $wpdb->prefix);
$results = $db->query("SELECT * FROM customers WHERE active = 1");
```

### 7.3 Standalone Usage

```php
// Standalone PHP
$db = DatabaseAdapterFactory::create('pdo', 'app_');
$results = $db->query("SELECT * FROM app_customers");
```

---

## 8. Security Considerations

### 8.1 SQL Injection Prevention
- All adapters use parameterized queries
- LegacyArraySqlBuilder generates parameterized SQL
- No string concatenation in SQL construction

### 8.2 File Access Control
- Key/value stores should be in non-web-accessible directories
- File permission checks on write operations
- Path traversal prevention

### 8.3 Credential Management
- No credentials stored in code
- Use environment variables or secure config files
- Factory methods accept credentials as parameters

---

**Document Owner**: KS Fraser Development Team  
**Review Status**: Pending