# Convert PRD to Sub-PRD Prompt

**Purpose:** Extract a specific module from a Master PRD and create a focused Sub-PRD  
**Version:** 1.0.0  
**Date:** November 10, 2025

---

## Prompt for AI Agent

```
I need you to create a Sub-PRD by extracting module-specific requirements from a Master PRD.

**Master PRD:** [Specify file path, e.g., /docs/prd/PRD01-MVP.md]
**Target Module:** [Specify module name, e.g., Multi-Tenancy System, Authentication, etc.]
**Sub-PRD ID:** [Specify ID, e.g., PRD01-SUB01, PRD02-SUB03, etc.]

Please follow these steps:

1. **Read the Master PRD** to understand the complete context and dependencies

2. **Extract Module Requirements** - Identify and extract ALL requirements related to the target module:
   - User stories (US-*)
   - Functional requirements (FR-*)
   - Business rules (BR-*)
   - Data requirements (DR-*)
   - Integration requirements (IR-*)
   - Performance requirements (PR-*)
   - Security requirements (SR-*)
   - Scalability requirements (SCR-*)
   - Compliance requirements (CR-*)

3. **Create Sub-PRD File** with naming convention: `PRD{number}-SUB{subnumber}-{module-name}.md`
   - Example: `PRD01-SUB01-multitenancy.md`
   - Save to: `/docs/prd/`

4. **Include in Sub-PRD:**
   ```markdown
   # PRD{number}-SUB{subnumber}: {Module Name}

   **Master PRD:** [Link to master PRD file]
   **Module Code:** {Module.Code from master PRD}
   **Version:** 1.0.0
   **Status:** Draft
   **Implementation Plan:** [Link to corresponding PLAN file if exists]

   ---

   ## Executive Summary

   [Brief module overview extracted from master PRD]

   ## User Requirements

   ### User Stories
   [Extract relevant US-* items]

   ### User Personas
   [Extract relevant personas]

   ### Use Cases
   [Extract relevant use cases]

   ## Functional Requirements

   [Extract all FR-* related to this module]

   ## Non-Functional Requirements

   ### Performance
   [Extract PR-* items]

   ### Security
   [Extract SR-* items]

   ### Scalability
   [Extract SCR-* items]

   ## Technical Requirements

   [Extract technical specs, data models, API specifications]

   ## Business Rules

   [Extract BR-* items]

   ## Data Requirements

   [Extract DR-* items]

   ## Integration Requirements

   [Extract IR-* items]

   ## Acceptance Criteria

   [Define what "done" means for this module]

   ## Dependencies

   ### Prerequisites
   [List what must be completed before this module]

   ### Related Modules
   [List modules that interact with this one]

   ### External Systems
   [List external integrations]

   ## Testing Requirements

   [Specify test scenarios and coverage expectations]

   ## Assumptions & Constraints

   [List assumptions and constraints specific to this module]

   ## Success Metrics

   [Define measurable success criteria]

   ---

   **Next Steps:**
   1. Review and approve this Sub-PRD
   2. Create implementation plan: PLAN{number}-implement-{component}.md
   3. Break down into GitHub issues
   4. Assign to milestone
   ```

5. **Maintain Traceability:**
   - Link back to master PRD
   - Link forward to implementation plan (if exists)
   - Cross-reference related sub-PRDs
   - Preserve all requirement IDs from master PRD

6. **Update Master PRD** (optional):
   - Add reference to new sub-PRD in the module section
   - Mark module as "Detailed in PRD{number}-SUB{subnumber}"

7. **Update /docs/prd/README.md:**
   - Add new sub-PRD to the table
   - Update sub-PRD count

Please create the Sub-PRD now and confirm when complete.
```

---

## Usage Examples

### Example 1: Extract Multi-Tenancy from MVP PRD

```
I need you to create a Sub-PRD by extracting module-specific requirements from a Master PRD.

**Master PRD:** /docs/prd/PRD01-MVP.md
**Target Module:** Multi-Tenancy System (Core.001)
**Sub-PRD ID:** PRD01-SUB01

Please follow the steps in .github/prompts/convert-prd-to-subprd.md to create PRD01-SUB01-multitenancy.md
```

### Example 2: Extract Healthcare Module

```
I need you to create a Sub-PRD by extracting module-specific requirements from a Master PRD.

**Master PRD:** /docs/prd/PRD02-HEALTHCARE-INDUSTRY-MODULES.md
**Target Module:** Patient Management
**Sub-PRD ID:** PRD02-SUB01

Please follow the steps in .github/prompts/convert-prd-to-subprd.md to create PRD02-SUB01-patient-management.md
```

### Example 3: Extract Financial Module

```
I need you to create a Sub-PRD by extracting module-specific requirements from a Master PRD.

**Master PRD:** /docs/prd/PRD01-MVP.md
**Target Module:** Chart of Accounts (Accounting.001)
**Sub-PRD ID:** PRD01-SUB07

Please follow the steps in .github/prompts/convert-prd-to-subprd.md to create PRD01-SUB07-chart-of-accounts.md
```

---

## Quality Checklist

Before considering a Sub-PRD complete, verify:

- [ ] All relevant requirements extracted from master PRD
- [ ] No conflicting requirements
- [ ] Clear acceptance criteria defined
- [ ] Dependencies identified
- [ ] Link to master PRD included
- [ ] Unique sub-PRD ID assigned
- [ ] Proper naming convention followed
- [ ] Saved to `/docs/prd/` directory
- [ ] Listed in `/docs/prd/README.md`
- [ ] All requirement IDs preserved (FR-*, SR-*, etc.)
- [ ] Technical specifications included
- [ ] Testing requirements specified

---

## After Creating Sub-PRD

### 1. Review
- Product Manager reviews for completeness
- Stakeholders approve requirements
- Technical team validates feasibility

### 2. Create Implementation Plan
Use the prompt: `.github/prompts/create-implementation-plan.prompt.md`

```
Create an implementation plan for the following Sub-PRD:

**Sub-PRD:** /docs/prd/PRD01-SUB01-multitenancy.md
**Plan ID:** PLAN01
**Action:** implement
**Component:** multitenancy

Follow the template in .github/prompts/create-implementation-plan.prompt.md
```

### 3. Break into GitHub Issues
Use the prompt: `.github/prompts/create-issue-from-implementation-plan.prompt.md`

### 4. Assign to Milestone
Add to appropriate milestone in ROADMAP.md

---

## Sub-PRD Numbering Guidelines

### Format: `PRD{MasterNumber}-SUB{SubNumber}`

**Master Number:** Matches the parent master PRD
- `PRD01-SUB*` = Sub-PRDs from PRD01-MVP.md
- `PRD02-SUB*` = Sub-PRDs from PRD02-HEALTHCARE-INDUSTRY-MODULES.md
- `PRD03-SUB*` = Sub-PRDs from PRD03-MANUFACTURING-MODULES.md

**Sub Number:** Sequential within each master PRD
- `PRD01-SUB01`, `PRD01-SUB02`, `PRD01-SUB03`, ... (from PRD01)
- `PRD02-SUB01`, `PRD02-SUB02`, `PRD02-SUB03`, ... (from PRD02)

### Suggested Sub-PRD Breakdown for PRD01-MVP

| Sub-PRD ID | Module | Priority |
|------------|--------|----------|
| PRD01-SUB01 | Multi-Tenancy System | P0 - Critical |
| PRD01-SUB02 | Authentication & Authorization | P0 - Critical |
| PRD01-SUB03 | Audit Logging System | P0 - Critical |
| PRD01-SUB04 | Serial Numbering System | P0 - Critical |
| PRD01-SUB05 | Settings Management | P1 - High |
| PRD01-SUB06 | Unit of Measure (UOM) | P0 - Critical |
| PRD01-SUB07 | Chart of Accounts | P0 - Critical |
| PRD01-SUB08 | General Ledger | P0 - Critical |
| PRD01-SUB09 | Journal Entries | P0 - Critical |
| PRD01-SUB10 | Banking Module | P1 - High |
| PRD01-SUB11 | Accounts Payable | P0 - Critical |
| PRD01-SUB12 | Accounts Receivable | P0 - Critical |
| PRD01-SUB13 | HCM (Human Capital Management) | P1 - High |
| PRD01-SUB14 | Inventory Management | P0 - Critical |

---

## Related Prompts

- [create-implementation-plan.prompt.md](./create-implementation-plan.prompt.md) - Create PLAN from Sub-PRD
- [create-issue-from-implementation-plan.prompt.md](./create-issue-from-implementation-plan.prompt.md) - Create GitHub issues from PLAN

---

**Version:** 1.0.0  
**Maintained By:** Laravel ERP Development Team  
**Last Updated:** November 10, 2025
