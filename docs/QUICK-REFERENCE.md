# Documentation Quick Reference Guide

**Version:** 1.0.0  
**Date:** November 10, 2025  
**Purpose:** Quick lookup for documentation structure and naming conventions

---

## 🗂️ Directory Structure

```
/docs/
  ├── prd/                     # 📋 WHAT to build (Requirements)
  │   ├── PRD01-MVP.md
  │   ├── PRD01-SUB01-*.md
  │   └── README.md
  │
  ├── plan/                    # 🔧 HOW to build (Implementation)
  │   ├── PLAN01-implement-*.md
  │   ├── ROADMAP.md
  │   └── README.md
  │
  ├── architecture/            # 🏗️ Design decisions
  │   ├── PACKAGE-DECOUPLING-STRATEGY.md
  │   └── *.md
  │
  └── *.md                     # 📖 Technical guides
```

---

## 📝 File Naming Conventions

### Master PRDs
```
PRD{number}-{product-name}.md

Examples:
  PRD01-MVP.md
  PRD02-HEALTHCARE-INDUSTRY-MODULES.md
  PRD03-MANUFACTURING-MODULES.md
```

### Sub-PRDs
```
PRD{number}-SUB{subnumber}-{module-name}.md

Examples:
  PRD01-SUB01-multitenancy.md
  PRD01-SUB02-authentication.md
  PRD02-SUB01-patient-management.md
```

### Implementation Plans
```
PLAN{number}-{action}-{component}.md

Actions: implement, enhance, modify, remove, refactor, optimize, migrate

Examples:
  PLAN01-implement-multitenancy.md
  PLAN15-enhance-user-permissions.md
  PLAN23-refactor-repository-pattern.md
```

---

## 🔄 Document Flow

```
Master PRD → Sub-PRD → Implementation PLAN → GitHub Issues
```

**Example:**
```
PRD01-MVP.md
  ↓ (extract module)
PRD01-SUB01-multitenancy.md
  ↓ (create implementation)
PLAN01-implement-multitenancy.md
  ↓ (create issues for phases)
GitHub Issues: PLAN01-PHASE01, PLAN01-PHASE02, ...
```

---

## 🤖 AI Prompts

### Create Sub-PRD from Master PRD
```
Use .github/prompts/convert-prd-to-subprd.md to create 
PRD01-SUB01-multitenancy.md from PRD01-MVP.md
```

### Create GitHub Issues from PLAN
```
Use .github/prompts/create-issue-from-implementation-plan.prompt.md 
to create issues from PLAN01-implement-multitenancy.md
```

---

## 📍 Quick Navigation

| Need to... | Go to... |
|------------|----------|
| **Understand product requirements** | `/docs/prd/PRD01-MVP.md` |
| **Find module-specific requirements** | `/docs/prd/PRD01-SUB{XX}-*.md` |
| **See implementation details** | `/docs/plan/PLAN{XX}-*.md` |
| **View project roadmap** | `/docs/plan/ROADMAP.md` |
| **Check architecture decisions** | `/docs/architecture/` |
| **Learn coding standards** | `/CODING_GUIDELINES.md` |
| **Configure AI agents** | `/.github/copilot-instructions.md` |

---

## 🎯 Current State

### PRDs
- **PRD01-MVP.md** - Master requirements for MVP
- **Sub-PRDs:** To be created (14 planned)

### PLANs (Implementation Plans)
- PLAN01: Multi-Tenancy System
- PLAN02: Authentication & Authorization
- PLAN03: Audit Logging System
- PLAN04: Serial Numbering System
- PLAN05: Settings Management
- PLAN06: Unit of Measure (UOM)

### Milestones (8 total)
- M1-M3: Infrastructure (Nov-Dec 2025)
- M4-M7: Business Modules (Jan-Mar 2026)
- M8: Integration & Launch (Mar 2026)

---

## ✅ Checklist for Creating New Documents

### Creating a Master PRD
- [ ] Choose PRD number (PRD{XX})
- [ ] Use naming: `PRD{XX}-{PRODUCT-NAME}.md`
- [ ] Include all requirement types (FR-*, SR-*, PR-*)
- [ ] Save to `/docs/prd/`
- [ ] Update `/docs/prd/README.md`
- [ ] Link in copilot instructions if major product

### Creating a Sub-PRD
- [ ] Identify master PRD and module
- [ ] Use naming: `PRD{XX}-SUB{YY}-{module-name}.md`
- [ ] Use conversion prompt: `.github/prompts/convert-prd-to-subprd.md`
- [ ] Extract all relevant requirements from master
- [ ] Link back to master PRD
- [ ] Save to `/docs/prd/`
- [ ] Update `/docs/prd/README.md`

### Creating an Implementation PLAN
- [ ] Identify source sub-PRD
- [ ] Choose PLAN number (PLAN{XX})
- [ ] Choose action verb (implement, enhance, etc.)
- [ ] Use naming: `PLAN{XX}-{action}-{component}.md`
- [ ] Include: phases, tasks, files, tests
- [ ] Link to source sub-PRD
- [ ] Save to `/docs/plan/`
- [ ] Update `/docs/plan/README.md`
- [ ] Update `/docs/plan/ROADMAP.md`

### Creating GitHub Issues
- [ ] Use prompt: `.github/prompts/create-issue-from-implementation-plan.prompt.md`
- [ ] Follow naming: `PLAN{XX}-PHASE{YY}: Description`
- [ ] Link back to PLAN file
- [ ] Assign to appropriate milestone
- [ ] Add appropriate labels
- [ ] Include acceptance criteria

---

## 🚫 Common Mistakes to Avoid

1. ❌ Don't name implementation plans as "PRD"
   - ✅ Use "PLAN" for implementation plans

2. ❌ Don't put implementation details in PRDs
   - ✅ PRDs = requirements only, PLANs = implementation

3. ❌ Don't skip Sub-PRDs for complex modules
   - ✅ Break down Master PRD into manageable Sub-PRDs

4. ❌ Don't create PLANs without corresponding Sub-PRDs
   - ✅ Always: Master PRD → Sub-PRD → PLAN

5. ❌ Don't forget to update README files
   - ✅ Update both `/docs/prd/README.md` and `/docs/plan/README.md`

---

## 📚 Key Resources

- **Copilot Instructions:** `/.github/copilot-instructions.md`
- **Coding Guidelines:** `/CODING_GUIDELINES.md`
- **PRD Index:** `/docs/prd/README.md`
- **PLAN Index:** `/docs/plan/README.md`
- **Roadmap:** `/docs/plan/ROADMAP.md`
- **Conversion Prompt:** `/.github/prompts/convert-prd-to-subprd.md`

---

## 🔢 Requirement Prefixes

| Prefix | Type | Example |
|--------|------|---------|
| US-* | User Story | US-001: As a tenant admin... |
| FR-* | Functional Requirement | FR-023: System shall validate... |
| BR-* | Business Rule | BR-015: Tax calculation must... |
| DR-* | Data Requirement | DR-007: Customer records must... |
| IR-* | Integration Requirement | IR-004: Must integrate with... |
| PR-* | Performance Requirement | PR-012: Response time < 200ms... |
| SR-* | Security Requirement | SR-009: Passwords must be hashed... |
| SCR-* | Scalability Requirement | SCR-006: Support 10K users... |
| CR-* | Compliance Requirement | CR-002: Must comply with GDPR... |

---

**Last Updated:** November 10, 2025  
**Maintained By:** Laravel ERP Development Team
