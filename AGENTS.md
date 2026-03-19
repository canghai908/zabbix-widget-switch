# Repository Guidelines

## Project Structure & Module Organization

This repository is currently a minimal scaffold with no application source checked in yet. Keep top-level documentation files such as `README.md` and `AGENTS.md` in the repository root. When adding code, use a predictable layout:

- `src/` for application or plugin code
- `tests/` for automated tests
- `assets/` for static files such as screenshots or icons
- `scripts/` for local development helpers

Group code by feature or integration area, and keep related tests close to the feature they cover when practical.

## Build, Test, and Development Commands

No build system or package manifest is present yet, so contributors should document new commands as soon as tooling is introduced. If you add a standard toolchain, prefer simple root-level entry points such as:

- `make build` - build the project
- `make test` - run the full test suite
- `make lint` - run formatting and lint checks

If you use a language-specific runner instead, mirror the same responsibilities in `package.json`, `Makefile`, or equivalent.

## Coding Style & Naming Conventions

Use 4 spaces for indentation in prose-like config files and follow the formatter standard for the chosen language in code files. Prefer:

- descriptive, lowercase directory names: `src/widget_switch/`
- clear file names based on responsibility: `toggle_handler.ts`, `switch_test.go`
- short, imperative function names for actions and nouns for data models

Add a formatter and linter early, and commit their config with the first production code.

## Testing Guidelines

Create automated tests alongside new functionality. Use a mirrored naming pattern such as `tests/test_switch.py`, `switch.test.ts`, or `switch_test.go`, depending on the language. Aim to cover core behavior, edge cases, and configuration parsing before opening a pull request.

## Commit & Pull Request Guidelines

No Git history is available in this workspace yet, so use concise imperative commit messages such as `Add widget toggle handler` or `Document local setup`. Keep pull requests focused and include:

- a short description of the change
- linked issue or task reference when available
- test notes describing what was run
- screenshots for UI changes

## Configuration & Documentation

Do not commit secrets, API tokens, or environment-specific credentials. Document required configuration in `README.md` and provide sanitized example files such as `.env.example` when needed.
