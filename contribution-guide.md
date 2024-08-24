# Contribution Guide

Welcome to our TodoList project! This document outlines the best practices to follow to contribute effectively to the development. We aim to maintain clean, organized, and consistent code. By following these guidelines, you'll help ensure the overall quality of the project.

---

## 1. **Code Structure**

- **Naming Convention:** Use `camelCase` for naming variables and methods. For classes, use `PascalCase`. Example:

  ```php
  class UserController {
      private $userRepository;

      public function getUserDetails() {
          // ...
      }
  }

- **Clear and Concise Methods:** Each method should have a single responsibility. If a method does multiple things, consider splitting it.

- **Comments:** Comment your code where necessary, especially in complex parts. Comments should explain why the code does something rather than how.

## 2. **Branch Management**

- **Master/Develop:** The master branch contains the stable version of the project. The develop branch is used for ongoing development.

- **Feature Branches:** For each new feature or bug fix, create a new branch from develop with a descriptive name:

  ```bash
  git checkout -b feature/feature-name
  git checkout -b fix/bug-name
  ```

- **Pull Requests:** Once the work on a feature branch is complete, submit a Pull Request to develop. Ensure your code has been tested and follows the project's standards.

## 3. Commit Standards

- **Commit Messages:** Commit messages should be clear and concise. Use the present imperative tense (e.g., "Add login functionality").

Structure:

```
type(scope): short description

Optional commit body. Explain why this change is necessary.
```

- **Type:** The type of change

```
9 types are available:

- build: changes that affect the build system or external dependencies (npm, make…)
- ci: changes related to integration files and configuration scripts (Travis, Ansible, BrowserStack…)
- feat: adding a new feature
- fix: bug fixing
- perf: performance improvement
- refactor: modification that neither adds a new feature nor improves performance
- style: changes that do not affect functionality or semantics (indentation, formatting, adding spaces, renaming variables…)
- docs: writing or updating documentation
- test: adding or modifying tests
```

- **Description:** A summary of what has been changed.

## 4. Testing and Validation

- **Unit Tests:** Each new feature or bug fix must be accompanied by unit tests. Use PHPUnit to write and run these tests.

- **Code Quality Tests:** Before submitting a Pull Request, run code quality tests, including PHPStan and PHP-CS-Fixer, to ensure your code adheres to quality standards.

## 5. Code Review

- **Peer Review:** Pull Requests must be reviewed by another team member before merging. Reviewers should ensure that the code is clear, well-structured, and compliant with standards.

- **Constructive Feedback:** When reviewing code, provide constructive feedback and encourage best practices.

## 6. Documentation

- **Code Documentation:** Use PHPDoc blocks to document important classes, methods, and properties.
- **Updates:** If you modify an existing feature, ensure to update the associated documentation.
