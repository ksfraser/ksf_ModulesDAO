# ModulesDAO - Test Plan

**Document ID:** TP-MODULESDAO-001  
**Module:** ksf_ModulesDAO  
**Version:** 1.0.0  

---

## 1. Test Scope

- Key-value store operations
- Database adapter functionality
- File-based storage
- Factory pattern

## 2. Test Cases

### 2.1 KeyValueStore Tests

| ID | Test | Test Data | Pass Criteria |
|---------|-----------|-----------|---------------|
| TC-001 | testPdoTableStore_SetGet | key='test', val='value' | Correct retrieval |
| TC-002 | testPdoTableStore_Has | existing key | true returned |
| TC-003 | testPdoTableStore_Delete | key deleted | has() returns false |
| TC-004 | testJsonFileStore_SetGet | JSON storage | Correct serialization |
| TC-005 | testXmlFileStore_SetGet | XML storage | Valid XML output |
| TC-006 | testCsvFileStore_SetGet | CSV storage | Correct CSV format |

### 2.2 Database Adapter Tests

| ID | Test | Test Data | Pass Criteria |
|---------|-----------|-----------|---------------|
| TC-010 | testPdoDbAdapter_Query | valid SQL | Results returned |
| TC-011 | testMysqlDbAdapter_Connect | valid connection | Connection established |
| TC-012 | testFaDbAdapter_Query | FA-compatible SQL | Results in FA format |
| TC-013 | testDatabaseAdapterFactory | FA platform | FA adapter created |

### 2.3 Platform Store Tests

| ID | Test | Test Data | Pass Criteria |
|---------|-----------|-----------|---------------|
| TC-020 | testWordPressOptionsStore | option name/value | WP option API used |
| TC-021 | testSuiteCrmAdminStore | setting name/value | SuiteCRM API used |
| TC-022 | testFaSysPrefsStore | pref key/value | FA sys_prefs used |
| TC-023 | testFaDbTableStore | table/record data | FA db format |

### 2.4 Factory Tests

| ID | Test | Test Data | Pass Criteria |
|---------|-----------|-----------|---------------|
| TC-030 | testKeyValueStoreFactory_Pdo | type='pdo' | PdoTableStore created |
| TC-031 | testKeyValueStoreFactory_File | type='json' | JsonFileStore created |
| TC-032 | testKeyValueStoreFactory_Auto | auto-detect | Appropriate store created |