# Contributing to DDD-Forge ⚒️

🚧 **Note:** At this stage, DDD-Forge is in early development, and I am not accepting external contributions yet.  
Once the project reaches a stable release (v1.0.0), contributions will be welcome!

---

Thank you for your interest in improving **DDD-Forge**. Feedback and ideas are always appreciated — feel free to open an issue if you want to share suggestions.


---

## 🚀 Getting Started

1. **Fork** the repository and clone your fork.
2. Install dependencies:
    ```bash
       make install
    ```
3. Verify everything is working:
```bash
    make qa
```

## 🧪 Development Workflow

Run tests:
```bash
make test
```

Run static analysis:
```bash
make stan
```

Check coding style:
```bash
make lint
```

Fix coding style automatically:
```bash
make csfix
```

You can also use Composer scripts if you don't have make:
```bash
composer run test
composer run stan
composer run lint
composer run csfix
composer run qa
```

## 📦 CLI Development

Run ddd-forge command:
```bash
make forge
```
```

## ✅ Commit Messages

We follow [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/).
Examples:

* feat: add make:context command
* fix: resolve namespace bug in InitCommand
* chore: update dev tools
* docs: improve README

## 🤝 Pull Requests

1. Create a branch from main.
2. Use a descriptive branch name, e.g.:
   * feat/make-context
   * fix/config-location
   * docs/readme-update

3. Open a Pull Request and describe your changes clearly.
4. Make sure CI checks pass before requesting review.