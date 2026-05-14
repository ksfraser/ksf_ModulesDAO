# ModulesDAO - Architecture

**Document ID:** ARCH-MODULESDAO-001  
**Module:** ksf_ModulesDAO  
**Version:** 1.0.0  

---

## 1. Module Overview

ModulesDAO implements a storage abstraction layer with multiple backend implementations connected through factory patterns and interfaces.

## 2. Interface Diagram

```
┌─────────────────────────────────────────────────────────────┐
│              KeyValueStoreInterface                         │
├─────────────────────────────────────────────────────────────┤
│ + get(key): mixed                                           │
│ + set(key, value): self                                    │
│ + has(key): bool                                           │
│ + delete(key): self                                        │
│ + all(): array                                             │
└─────────────────────────────────────────────────────────────┘
                              △
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
┌───────┴───────┐    ┌────────┴────────┐    ┌──────┴──────┐
│ PdoTableStore│    │JsonFileStore    │    │ FA SysPrefs │
└──────────────┘    └────────────────┘    └─────────────┘

┌─────────────────────────────────────────────────────────────┐
│              RecordStoreInterface                           │
├─────────────────────────────────────────────────────────────┤
│ + find(id): ?array                                         │
│ + findAll(filters): array                                  │
│ + save(data): array                                        │
│ + delete(id): bool                                        │
└─────────────────────────────────────────────────────────────┘
```

## 3. Directory Structure

```
ksf_ModulesDAO/
├── src/Ksfraser/
│   ├── ksf_ModulesDAO.php
│   ├── Contracts/
│   │   ├── KeyValueStoreInterface.php
│   │   ├── RecordStoreInterface.php
│   │   └── StoreAvailabilityInterface.php
│   ├── Factory/
│   │   ├── KeyValueStoreFactory.php
│   │   └── DatabaseAdapterFactory.php
│   ├── Stores/
│   │   ├── KeyValue/
│   │   │   ├── AbstractFileKeyValueStore.php
│   │   │   ├── PdoTableStore.php
│   │   │   ├── FrontAccountingSysPrefsStore.php
│   │   │   ├── FrontAccountingDbTableStore.php
│   │   │   ├── WordPressOptionsStore.php
│   │   │   ├── SuiteCrmAdministrationStore.php
│   │   │   ├── JsonFileStore.php
│   │   │   ├── XmlFileStore.php
│   │   │   ├── CsvFileStore.php
│   │   │   ├── YamlFileStore.php
│   │   │   └── IniFileStore.php
│   │   └── Record/
│   │       ├── AbstractFileRecordStore.php
│   │       ├── XmlRecordStore.php
│   │       └── CsvRecordStore.php
│   ├── Db/
│   │   ├── DbAdapterInterface.php
│   │   ├── PdoDbAdapter.php
│   │   ├── MysqlDbAdapter.php
│   │   └── FrontAccountingDbAdapter.php
│   ├── Sql/
│   │   ├── LegacyArraySqlBuilder.php
│   │   └── BuiltQuery.php
│   └── Codec/
│       └── ValueCodec.php
├── tests/
└── doc/ProjectDcs/
```

## 4. Technology Stack

| Component | Technology |
|-----------|------------|
| Language | PHP 7.3+ |
| Database | PDO, MySQL, FA db functions |
| Serialization | JSON, XML, CSV, YAML, INI |