[![Project Management](https://img.shields.io/badge/project-management-blue.svg)](https://waffle.io/limoncello-php/framework)
[![License](https://img.shields.io/packagist/l/limoncello-php/core.svg)](https://packagist.org/packages/limoncello-php/core)

## Testing

```
composer test
```

The command above will run

- Code coverage tests for all components (`phpunit`) except `Contracts`.
- Code style checks for for all components (`phpcs`).
- Code checks for all components (`phpmd`).

Requirements

- 100% test coverage.
- zero issues from both `phpcs` and `phpmd`.
