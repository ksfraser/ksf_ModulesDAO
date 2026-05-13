# ksf_ModulesDAO - Test Plan

## Document Information

| Field | Value |
|-------|-------|
| **Document ID** | TP-DAO-001 |
| **Module** | ksf_ModulesDAO |
| **Project** | DAO Abstraction Layer |
| **Version** | 1.0.0 |
| **Author** | KS Fraser Development Team |
| **Created** | 2024-01-15 |

---

## 1. Test Scenarios

### 1.1 TC-001: Factory Creates PDO Adapter

**Test Case ID**: TC-001  
**Class**: `DatabaseAdapterFactory`  
**Method**: `create()`

#### Test Steps
1. Call `DatabaseAdapterFactory::create('pdo', 'test_')`
2. Assert instance is `PdoDbAdapter`
3. Assert `getTablePrefix()` returns 'test_'
4. Assert `getDialect()` returns appropriate value

#### Pass Criteria
- [ ] Correct adapter created
- [ ] Table prefix set
- [ ] Interface implemented

---

### 1.2 TC-002: Factory Creates FA Adapter

**Test Case ID**: TC-002  
**Class**: `DatabaseAdapterFactory`  
**Method**: `create()`

#### Test Steps
1. Call `DatabaseAdapterFactory::create('fa', '0_')`
2. Assert instance is `FrontAccountingDbAdapter`
3. Assert `getTablePrefix()` returns '0_'

#### Pass Criteria
- [ ] FA adapter created
- [ ] Table prefix set

---

### 1.3 TC-003: FrontAccounting Adapter Query

**Test Case ID**: TC-003  
**Class**: `FrontAccountingDbAdapter`  
**Method**: `query()`

#### Test Steps
1. Create adapter with prefix '0_'
2. Mock FA functions (db_query, db_fetch_assoc)
3. Call `query("SELECT * FROM :table", ['table' => 'customers'])`
4. Assert results returned as array

#### Pass Criteria
- [ ] Query executed
- [ ] Parameters substituted
- [ ] Results returned as associative arrays

---

### 1.4 TC-004: Build SELECT with Simple Where

**Test Case ID**: TC-004  
**Class**: `LegacyArraySqlBuilder`  
**Method**: `buildSelect()`

#### Test Data
```php
$query = LegacyArraySqlBuilder::buildSelect(
    ['id', 'name', 'email'],
    ['customers'],
    ['status' => ['eq', 'active']]
);
```

#### Test Steps
1. Call buildSelect with data
2. Assert SQL contains 'SELECT id, name, email FROM customers'
3. Assert SQL contains 'WHERE status = :w0'
4. Assert params has 'w0' => 'active'

#### Pass Criteria
- [ ] SELECT clause correct
- [ ] WHERE clause correct
- [ ] Parameters correct

---

### 1.5 TC-005: Build SELECT with LIKE

**Test Case ID**: TC-005  
**Class**: `LegacyArraySqlBuilder`  
**Method**: `buildSelect()`

#### Test Data
```php
$query = LegacyArraySqlBuilder::buildSelect(
    ['id', 'name'],
    ['customers'],
    ['name' => ['like', 'John']]
);
```

#### Test Steps
1. Assert SQL contains 'name LIKE :w0'
2. Assert params['w0'] is '%John%'

#### Pass Criteria
- [ ] LIKE operator correct
- [ ] Wildcards added

---

### 1.6 TC-006: Build SELECT with IN

**Test Case ID**: TC-006  
**Class**: `LegacyArraySqlBuilder`  
**Method**: `buildSelect()`

#### Test Data
```php
$query = LegacyArraySqlBuilder::buildSelect(
    ['id', 'name'],
    ['customers'],
    ['id' => ['in', [1, 2, 3]]]
);
```

#### Test Steps
1. Assert SQL contains 'id IN (:w0_0, :w0_1, :w0_2)'
2. Assert params contains all three values

#### Pass Criteria
- [ ] IN clause correct
- [ ] All values parameterized

---

### 1.7 TC-007: BuiltQuery Get Methods

**Test Case ID**: TC-007  
**Class**: `BuiltQuery`  
**Method**: `getSql()`, `getParams()`

#### Test Steps
1. Create BuiltQuery with SQL and params
2. Call getSql()
3. Assert returns SQL string
4. Call getParams()
5. Assert returns params array

#### Pass Criteria
- [ ] SQL retrievable
- [ ] Params retrievable
- [ ] Immutable

---

### 1.8 TC-008: KeyValueStore Get/Set

**Test Case ID**: TC-008  
**Class**: `JsonFileAdapter`  
**Method**: `get()`, `set()`

#### Test Steps
1. Create adapter with temp file path
2. Call set('key', 'value')
3. Call get('key')
4. Assert returns 'value'
5. Call exists('key')
6. Assert returns true

#### Pass Criteria
- [ ] Values stored
- [ ] Values retrieved
- [ ] Exists check works

---

### 1.9 TC-009: KeyValueStore Delete

**Test Case ID**: TC-009  
**Class**: `JsonFileAdapter`  
**Method**: `delete()`

#### Test Steps
1. Create adapter with temp file
2. Set key/value
3. Call delete('key')
4. Call exists('key')
5. Assert returns false

#### Pass Criteria
- [ ] Key deleted
- [ ] Exists returns false

---

### 1.10 TC-010: Build INSERT

**Test Case ID**: TC-010  
**Class**: `LegacyArraySqlBuilder`  
**Method**: `buildInsert()`

#### Test Data
```php
$fieldsArray = [
    ['name' => 'name', 'type' => 'VARCHAR(100)'],
    ['name' => 'email', 'type' => 'VARCHAR(255)'],
];
$objectVars = ['name' => 'John', 'email' => 'john@example.com'];
```

#### Test Steps
1. Call buildInsert
2. Assert SQL contains 'INSERT IGNORE INTO'
3. Assert SQL contains columns
4. Assert params populated

#### Pass Criteria
- [ ] INSERT syntax correct
- [ ] Parameters correct

---

## 2. Test Execution Matrix

| Test Case | Class | Priority | Status |
|-----------|-------|----------|--------|
| TC-001 | DatabaseAdapterFactory | High | Pending |
| TC-002 | DatabaseAdapterFactory | High | Pending |
| TC-003 | FrontAccountingDbAdapter | High | Pending |
| TC-004 | LegacyArraySqlBuilder | High | Pending |
| TC-005 | LegacyArraySqlBuilder | High | Pending |
| TC-006 | LegacyArraySqlBuilder | High | Pending |
| TC-007 | BuiltQuery | Medium | Pending |
| TC-008 | JsonFileAdapter | Medium | Pending |
| TC-009 | JsonFileAdapter | Medium | Pending |
| TC-010 | LegacyArraySqlBuilder | High | Pending |

---

**Document Owner**: KS Fraser Development Team  
**Review Status**: Pending