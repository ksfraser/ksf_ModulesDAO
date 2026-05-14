# ModulesDAO - Business Requirements

**Document ID:** BR-MODULESDAO-001  
**Module:** ksf_ModulesDAO  
**Version:** 1.0.0  

---

## 1. Overview

ModulesDAO provides a unified data access layer abstraction for FrontAccounting modules. It offers multiple storage implementations (PDO, MySQL, file-based) and allows modules to persist data without tight coupling to specific database technologies.

## 2. Purpose

The module enables framework-agnostic data persistence, allowing business logic to remain portable across FrontAccounting, WordPress, SuiteCRM, and other platforms while using consistent data access patterns.

## 3. Scope

### 3.1 Core Features

- **Key-Value Stores**
  - PDO table storage
  - File-based storage (JSON, XML, CSV, YAML, INI)
  - WordPress options storage
  - SuiteCRM administration storage
  - FrontAccounting sys_prefs storage
  - FrontAccounting db_table storage

- **Record Stores**
  - XML record storage
  - CSV record storage

- **Database Adapters**
  - PDO MySQL adapter
  - FrontAccounting DB adapter
  - Database adapter factory

- **SQL Builder**
  - Legacy array SQL builder
  - Query parameter binding

- **Value Codec**
  - Data encoding/decoding for storage

### 3.2 Out of Scope

- ORM functionality
- Query builder
- Migration support
- Connection pooling

## 4. Integration Dependencies

| Module | Dependency Type | Purpose |
|--------|-----------------|---------|
| FrontAccounting Core | Required | Database functions |
| PDO | Optional | PDO-based stores |

## 5. User Roles

| Role | Permissions |
|------|-------------|
| Module Developer | Use storage implementations |
| System Architect | Design storage strategies |

## 6. Acceptance Criteria

- [ ] Key-value stores implement common interface
- [ ] Multiple storage backends available
- [ ] Factory creates appropriate store by configuration
- [ ] Database adapter factory works correctly
- [ ] Record stores handle collections