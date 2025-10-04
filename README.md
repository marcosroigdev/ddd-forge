![DDD-Forge social preview](assets/social-preview.png)
# DDD-Forge ⚒️

[![CI](https://github.com/marcosroigdev/ddd-forge/actions/workflows/ci.yml/badge.svg)](https://github.com/marcosroigdev/ddd-forge/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-%5E8.2-blue)
![License](https://img.shields.io/badge/license-MIT-green)
[![Packagist Version](https://img.shields.io/packagist/v/marcosroigdev/ddd-forge.svg)](https://packagist.org/packages/marcosroigdev/ddd-forge)
[![Packagist Downloads](https://img.shields.io/packagist/dt/marcosroigdev/ddd-forge.svg)](https://packagist.org/packages/marcosroigdev/ddd-forge)


**DDD-Forge** is a scaffolding tool for **Domain-Driven Design** and **Hexagonal Architecture** in PHP.  
Generate contexts, aggregates, repositories, and command handlers with a single command— with Symfony & Laravel support.

🚧 **Work in Progress** – expect rapid changes.

---

## ✨ Features

- ⏳ Generate bounded contexts
- ⏳ Generate aggregates, value objects and domain events
- ⏳ Generate repositories
- ⏳ Generate CQRS commands
- ⏳ Symfony & Laravel recipes
- ⏳ Test scaffolding

---

## 🚀 Installation

Require the package in your project (soon available via [Packagist](https://packagist.org)):

```bash
composer require --dev marcosroigdev/ddd-forge
```

Requires PHP >= 8.2.

## 📦 Usage example

Run the CLI:

```bash
# Using composer script
composer run forge make:context

# Or directly
bin/ddd-forge make:context

```

## See the [GUIDE](GUIDE.md) for more details and examples.

## 🤝 Contributing

Contributions, ideas and feedback are welcome!
Please check CONTRIBUTING.md

## 📜 License

Released under the MIT License.