# ModulesDAO - Use Cases

**Document ID:** UC-MODULESDAO-001  
**Module:** ksf_ModulesDAO  
**Version:** 1.0.0  

---

## 1. Use Case Overview

### UC-001: Store Configuration Data

**Description:** Module stores configuration using appropriate storage backend.

**Primary Flow:**
1. Module determines available storage backend
2. Module creates KeyValueStore via factory
3. Module stores configuration key-value pairs
4. Later, module retrieves configuration

---

### UC-002: Access FrontAccounting Settings

**Description:** Module reads/writes FrontAccounting system preferences.

**Primary Flow:**
1. Module uses FrontAccountingSysPrefsStore
2. Module calls get('setting_key')
3. System returns setting value
4. Module calls set('setting_key', value)
5. System persists to FA sys_prefs table

---

### UC-003: Use Platform-Agnostic Storage

**Description:** Module persists data regardless of platform.

**Primary Flow:**
1. Module uses KeyValueStoreFactory
2. Factory detects current platform
3. Factory creates appropriate store implementation
4. Module uses standard KeyValueStoreInterface
5. Storage backend transparent to module

---

### UC-004: Export Data to File

**Description:** Module exports records to CSV format.

**Primary Flow:**
1. Module creates CsvRecordStore
2. Module calls findAll() to get records
3. Store writes records to CSV file
4. User downloads CSV file

## 2. Actors

| Actor | Role |
|-------|------|
| Module | Uses storage abstraction |
| Factory | Creates appropriate store |
| Storage Backend | Persists data |
| Platform | Provides platform-specific APIs |