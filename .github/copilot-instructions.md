Before you begin;
1. Always refer to docs/SYSTEM ARCHITECHTURAL DOCUMENT.md for architectural principles, and pay extra attention to the coding and system architecture principles outlined there, what is mandated and what is forbidden.
2. Follow the coding standards in CODING_GUIDELINES.md. Where there are conflicts, the architectural document takes precedence.
4. Understand the project purpose by reading the README.md in the repository root.
3. If you need to do integration tests, ensure they are ochestrated through the core package nexus/erp and implemented by Edward demo application. Edward must not interface directly with any other package other then Nexus/erp.
4. All new packages must adhere to the independent testability criterion outlined in the architectural document.