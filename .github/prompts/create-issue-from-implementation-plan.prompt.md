---
mode: 'agent'
description: 'Create GitHub Issues from implementation plan phases using feature_request.yml or chore_request.yml templates.'
tools: ['search/codebase', 'search', 'github/github-mcp-server/*']
---
# Create GitHub Issue from Implementation Plan

Create GitHub Issues for the implementation plan at `${file}`.

## Process

1. Analyze plan file to identify phases
2. Check existing issues using `github/github-mcp-server/search_issues`
3. Check existing labels to determine if there are enough labels for new issues
4. Assign labels to issues based on the contents of each phase, you can create new labels if necessary that better reflects the issues if the existing ones are not sufficient
5. Create new issue per GOAL using `github/github-mcp-server/issue_write` or update existing with `github/github-mcp-server/issue_write`
6. Test with creating one issue first to see if you have access to the right tools for this job. If successful, proceed to create the remaining issues. and if you fail, stop the process and report the error.

## Requirements

- One issue per implementation GOAL (Each implementation plan may have one or more GOALS)
- Clear, structured titles and descriptions that ensure the implementation plan phase is fully understood
- Appropriate labels based on GOAL description of nature
- Include only changes required by the plan
- Verify against existing issues before creation

## Issue Content

- Title: Use a format like `PRD01-SUB01-PLAN01-GOAL01: Feature Description`. The title must include the Sub-PRD number, the PLAN number, and the GOAL number. For example: "PRD01-SUB01-PLAN01-GOAL01: Setup Multi-Tenancy Database Schema"
- Description: GOAL details, requirements, and context. Make sure Issue description link backs to the implementation plan file and GOAL section. To keep the context clear and Issue Description concise, summarize the GOAL details effectively or make a checklist that link back to the exact lines in the implementation plan file.
- Labels: Appropriate for issue nature
- Milestone: Assign to appropriate milestone from ROADMAP.md

## Note on Naming Convention

Implementation plans now use the format: `PLAN{number}-{action}-{component}.md`
- Example: `PLAN01-implement-multitenancy.md`
- Old format like `PRD-01-infrastructure-multitenancy-1.md` is deprecated

When creating issues, extract the PLAN number from the filename and use it consistently:
- From `PLAN01-implement-multitenancy.md` → Issue titles start with `PLAN01-`
- From `PLAN15-enhance-permissions.md` → Issue titles start with `PLAN15-`