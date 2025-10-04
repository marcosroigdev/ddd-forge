# 🏗️ DDD-FORGE
## Scaffolding tool for DDD & Hexagonal Architecture - Complete Guide
### Generate contexts, aggregates, repositories, and command handlers — with Symfony & Laravel support

## 📚 Table of Contents
1. [Usage Modes](#usage-modes)
2. [Presets](#presets)
3. [YAML Export](#yaml-export)
4. [.gitkeep Files](#gitkeep-files)
5. [Complete Examples](#complete-examples)

---

## 🎯 Usage Modes

### 1️⃣ Interactive Mode (Wizard)

**For new users or when you want to explore options:**

```bash
# Launch wizard automatically
make:context

# Or explicitly
make:context --interactive
```

The wizard will guide you step by step:
- ✅ Context name
- ✅ Base directory
- ✅ Template selection or custom
- ✅ Sublayer configuration (if you choose custom)
- ✅ Preview (dry-run)
- ✅ Save as preset
- ✅ Create .gitkeep files

### 2️⃣ Quick Mode (Traditional CLI)

**For experienced users:**

```bash
# Basic structure (4 main layers)
make:context UserManagement

# With predefined template
make:context Orders --template=cqrs
make:context Billing -t event-sourcing

# With standard sublayers
make:context Catalog --with-sublayers
make:context Inventory -s

# Custom directory
make:context Shipping -d src/Contexts

# Preview without creating (dry-run)
make:context Payment --template=hexagonal --dry-run

# Force creation even if directories exist
make:context Legacy --force
```

### 3️⃣ Preset Mode

**Reuse saved configurations:**

```bash
# Use a preset
make:context Warehouse --use-preset=ecommerce
make:context Shipping -p ecommerce

# List available presets
make:context --use-preset=list
```

---

## 💾 Presets

### What are Presets?

Presets allow you to **save and reuse** structure configurations for different types of contexts.

### Creating a Preset

#### Option 1: During the wizard
```bash
make:context --interactive
# At the end of the wizard, you'll be asked if you want to save as a preset
```

#### Option 2: Command line
```bash
# Create and save as preset
make:context Catalog --template=cqrs --save-preset=ecommerce

# Custom configuration and save
make:context Analytics --with-sublayers --save-preset=reporting
```

### Using a Preset

```bash
# Create new context with existing preset
make:context Inventory --use-preset=ecommerce
make:context Warehouse -p ecommerce

# With .gitkeep files
make:context Shipping -p ecommerce --gitkeep
```

### List Presets

```bash
make:context --use-preset=list
```

Example output:
```
📋 Available Presets
┌─────────────┬──────────────┬───────────────┬──────────────────┐
│ Name        │ Template     │ Structure     │ Created          │
├─────────────┼──────────────┼───────────────┼──────────────────┤
│ ecommerce   │ cqrs         │ 14 sublayers  │ 2024-03-15 10:30 │
│ reporting   │ custom       │ 8 sublayers   │ 2024-03-16 14:20 │
│ microservice│ hexagonal    │ 11 sublayers  │ 2024-03-17 09:15 │
└─────────────┴──────────────┴───────────────┴──────────────────┘
```

### Preset Structure

Presets are saved in: `.ddd-forge/presets/{name}.json`

```json
{
    "name": "ecommerce",
    "template": "cqrs",
    "withSublayers": true,
    "baseDir": "src",
    "customSublayers": {
        "Domain": ["Read", "Write", "Event"],
        "Application": ["Command", "Query", "Handler", "Bus"],
        "Infrastructure": ["Read", "Write", "Persistence"],
        "UI": ["Controller", "Command"]
    },
    "createdAt": "2024-03-15 10:30:45"
}
```

---

## 📄 YAML Export

### What's it for?

YAML export allows you to:
- 📋 **Document** your context structure
- 🔄 **Share** configurations with your team
- 📖 **Quick reference** for architecture
- 🤖 **Automation** (use YAML for other scripts)

### Export Structure

```bash
# Export while creating
make:context Orders --template=cqrs --export=orders-structure.yaml

# Export in dry-run (without creating directories)
make:context Billing -t event-sourcing --dry-run --export=billing.yaml

# Export custom configuration
make:context Analytics --interactive --export=analytics.yaml
```

### Generated YAML Example

```yaml
# DDD Context Structure: Orders
# Generated: 2024-03-15 10:30:45
# Template: cqrs

context:
  name: Orders
  baseDir: src
  template: cqrs

structure:
  Domain:
    sublayers:
      - Read
      - Write
      - Event
  Application:
    sublayers:
      - Command
      - Query
      - Handler
      - Bus
  Infrastructure:
    sublayers:
      - Read
      - Write
      - Persistence
      - Resources
  UI:
    sublayers:
      - Controller
      - Command

# Usage:
# You can use this file as documentation or recreate the structure
# Command: make:context Orders --template=cqrs
```

### YAML Use Cases

1. **Architecture documentation:**
   ```bash
   # Create structure and document
   make:context Orders -t cqrs --export=docs/contexts/orders.yaml
   ```

2. **Team onboarding:**
   ```bash
   # Generate documentation for all contexts
   make:context UserManagement --export=docs/user-management.yaml
   make:context Billing --export=docs/billing.yaml
   make:context Inventory --export=docs/inventory.yaml
   ```

3. **CI/CD - Validate structure:**
   ```bash
   # In your pipeline, you can compare current vs expected structure
   make:context Orders --dry-run --export=expected.yaml
   ```

---

## 🗂️ .gitkeep Files

### Why are they necessary?

Git **doesn't track empty directories**. .gitkeep files ensure that:
- ✅ All directories are included in the repository
- ✅ Other developers get the complete structure when cloning
- ✅ Architecture is clear from the start

### Create .gitkeep

```bash
# During creation
make:context Orders --template=cqrs --gitkeep
make:context Billing -t event-sourcing -g

# With the wizard (it will ask at the end)
make:context --interactive

# All options combined
make:context Shipping \
  --template=cqrs \
  --gitkeep \
  --save-preset=logistics \
  --export=shipping.yaml
```

### Result

Each created directory will contain a .gitkeep file:

```
src/Orders/
├── .gitkeep
├── Domain/
│   ├── .gitkeep
│   ├── Read/
│   │   └── .gitkeep
│   ├── Write/
│   │   └── .gitkeep
│   └── Event/
│       └── .gitkeep
├── Application/
│   ├── .gitkeep
│   ├── Command/
│   │   └── .gitkeep
│   └── Query/
│       └── .gitkeep
└── ...
```

---

## 🚀 Complete Examples

### Example 1: E-commerce Startup

```bash
# 1. Create product context with CQRS
make:context Product \
  --template=cqrs \
  --gitkeep \
  --save-preset=ecommerce \
  --export=docs/product-context.yaml

# 2. Reuse configuration for other contexts
make:context Order -p ecommerce --gitkeep
make:context Inventory -p ecommerce --gitkeep
make:context Shipping -p ecommerce --gitkeep
make:context Payment -p ecommerce --gitkeep

# Result: 5 contexts with consistent structure
```

**Advantages:**
- ⚡ Fast creation of multiple contexts
- 🔄 Consistent structure across the project
- 📋 Automatic documentation (YAML)
- 🗂️ Directories tracked in Git

---

### Example 2: Legacy Migration

```bash
# 1. Use wizard to configure custom structure
make:context --interactive

# During the wizard:
# - Context name: UserManagement
# - Template: Custom
# - Domain sublayers: Entity, ValueObject, Service, Repository
# - Application sublayers: UseCase, DTO, Mapper
# - Infrastructure sublayers: Doctrine, Cache, Queue
# - UI sublayers: Controller, Form, Validator
# - Save preset: legacy-migration
# - Create .gitkeep: Yes

# 2. Create other legacy contexts
make:context Authentication -p legacy-migration
make:context Authorization -p legacy-migration
make:context Reporting -p legacy-migration

# 3. Preview before creating
make:context Billing -p legacy-migration --dry-run
```

---

### Example 3: Microservices with Event Sourcing

```bash
# 1. Create first microservice with event sourcing
make:context OrderService \
  --template=event-sourcing \
  --dir=services \
  --gitkeep \
  --save-preset=microservice \
  --export=docs/order-service.yaml

# 2. Create more microservices
make:context PaymentService -p microservice -d services --gitkeep
make:context NotificationService -p microservice -d services --gitkeep
make:context InventoryService -p microservice -d services --gitkeep

# Resulting structure:
# services/
# ├── OrderService/
# │   ├── Domain/
# │   │   ├── Aggregate/
# │   │   ├── Event/
# │   │   └── Projection/
# │   ├── Application/
# │   │   ├── Command/
# │   │   ├── Query/
# │   │   ├── EventHandler/
# │   │   └── Projector/
# │   └── Infrastructure/
# │       ├── EventStore/
# │       ├── Projection/
# │       └── Snapshot/
# └── PaymentService/
#     └── (same structure)
```

---

### Example 4: Hexagonal Architecture

```bash
# Interactive configuration for first context
make:context --interactive

# Select:
# - Template: Hexagonal Architecture
# - Base dir: src/Contexts
# - Preview: Yes
# - Save preset: hexagonal-standard
# - Gitkeep: Yes

# After validation, create without dry-run:
make:context UserManagement \
  -p hexagonal-standard \
  -d src/Contexts \
  --gitkeep \
  --export=docs/user-management.yaml

# Create more hexagonal contexts
make:context ProductCatalog -p hexagonal-standard -d src/Contexts -g
make:context OrderManagement -p hexagonal-standard -d src/Contexts -g
```

---

### Example 5: Complete Team Workflow

```bash
# === Developer 1: Initial setup ===

# 1. Create first context and save preset
make:context UserManagement \
  --template=cqrs \
  --save-preset=company-standard \
  --gitkeep \
  --export=docs/contexts/user-management.yaml

# 2. Commit and push
git add .
git commit -m "Add UserManagement context with company standard"
git push


# === Developer 2: Reuse configuration ===

# 1. Pull from repo
git pull

# 2. View available presets
make:context --use-preset=list

# 3. Create new context with preset
make:context Billing -p company-standard --gitkeep

# 4. Export for documentation
make:context Billing --dry-run --export=docs/contexts/billing.yaml


# === Tech Lead: Review architecture ===

# View structure before approving PR
make:context ProposedContext -p company-standard --dry-run

# Export all structures for review
for context in UserManagement Billing Inventory; do
  make:context $context --dry-run --export=docs/review/$context.yaml
done
```

---

## 🎓 Use Cases by Role

### 👨‍💻 Junior Developer

```bash
# Use wizard to learn
make:context

# Experiment with dry-run
make:context MyFirstContext --template=standard --dry-run

# Use team presets
make:context --use-preset=list
make:context MyFeature -p team-standard
```

### 👨‍💼 Senior Developer

```bash
# Quick creation with templates
make:context Orders -t cqrs -g
make:context Analytics -t event-sourcing -g

# Create custom and save
make:context --interactive --save-preset=my-pattern

# Export for documentation
make:context Orders -t cqrs --export=docs/orders.yaml
```

### 🏗️ Software Architect

```bash
# Define team standards
make:context --interactive --save-preset=enterprise-standard
make:context --interactive --save-preset=microservice-standard
make:context --interactive --save-preset=monolith-standard

# Export all for documentation
make:context Example -p enterprise-standard --dry-run --export=standards/enterprise.yaml
make:context Example -p microservice-standard --dry-run --export=standards/microservice.yaml

# Validate compliance
for context in $(ls src/); do
  make:context $context --dry-run --export=audit/$context.yaml
done
```

### 👥 Tech Lead / Team Lead

```bash
# New project setup
make:context Core -t hexagonal --save-preset=project-standard -g --export=docs/core.yaml

# Onboarding new members
cat docs/core.yaml  # Show structure
make:context --use-preset=list  # Show available presets

# Code review of structure
make:context NewFeature --dry-run  # Preview before approving
```

---

## 🔧 Tips and Tricks

### 1. Useful aliases (add to .bashrc or .zshrc)

```bash
# Aliases for common commands
alias ddd-new='make:context --interactive'
alias ddd-list='make:context --use-preset=list'
alias ddd-quick='make:context --template=standard --gitkeep'

# Function to create + document
ddd-create() {
  make:context "$1" --template="${2:-standard}" --gitkeep --export="docs/contexts/${1,,}.yaml"
}

# Usage:
# ddd-create Orders cqrs
# ddd-create UserManagement  # uses standard by default
```

### 2. Automation scripts

**create-all-contexts.sh**
```bash
#!/bin/bash
# Create multiple contexts at once

contexts=(
  "UserManagement:standard"
  "ProductCatalog:cqrs"
  "OrderManagement:cqrs"
  "Payment:hexagonal"
  "Notification:basic"
)

for ctx in "${contexts[@]}"; do
  IFS=':' read -r name template <<< "$ctx"
  echo "Creating $name with template $template..."
  make:context "$name" --template="$template" --gitkeep --export="docs/contexts/${name,,}.yaml"
done

echo "✅ All contexts created!"
```

### 3. Git Hooks integration

**.git/hooks/pre-commit**
```bash
#!/bin/bash
# Validate that new contexts have .gitkeep

new_contexts=$(git diff --cached --name-only --diff-filter=A | grep "src/.*/Domain" | cut -d'/' -f1-2 | sort -u)

for context in $new_contexts; do
  if [ ! -f "$context/.gitkeep" ]; then
    echo "❌ Error: $context missing .gitkeep files"
    echo "Run: make:context $(basename $context) --gitkeep"
    exit 1
  fi
done
```

### 4. Automatic documentation generation

**generate-docs.sh**
```bash
#!/bin/bash
# Generate documentation for all contexts

echo "# Project Architecture" > docs/ARCHITECTURE.md
echo "" >> docs/ARCHITECTURE.md
echo "## Bounded Contexts" >> docs/ARCHITECTURE.md
echo "" >> docs/ARCHITECTURE.md

for context in src/*/; do
  context_name=$(basename "$context")
  echo "### $context_name" >> docs/ARCHITECTURE.md
  
  # Generate temporary YAML
  make:context "$context_name" --dry-run --export="/tmp/$context_name.yaml"
  
  # Extract info from YAML
  echo "\`\`\`yaml" >> docs/ARCHITECTURE.md
  cat "/tmp/$context_name.yaml" >> docs/ARCHITECTURE.md
  echo "\`\`\`" >> docs/ARCHITECTURE.md
  echo "" >> docs/ARCHITECTURE.md
done

echo "✅ Documentation generated at docs/ARCHITECTURE.md"
```

### 5. Structure validation

**validate-structure.php**
```php
<?php
// Validate that a context complies with the preset

$context = $argv[1] ?? 'UserManagement';
$preset = $argv[2] ?? 'standard';

// Generate expected structure
exec("make:context $context -p $preset --dry-run --export=/tmp/expected.yaml");

// Compare with current structure
$expectedStructure = yaml_parse_file('/tmp/expected.yaml');
$actualStructure = scanDirectoryStructure("src/$context");

$diff = compareStructures($expectedStructure, $actualStructure);

if (empty($diff)) {
    echo "✅ Structure matches preset '$preset'\n";
    exit(0);
} else {
    echo "❌ Structure doesn't match preset:\n";
    print_r($diff);
    exit(1);
}
```

---

## 📊 Template Comparison

| Template | Use Cases | Complexity | Sublayers |
|----------|-----------|------------|-----------|
| **basic** | Small projects, prototypes | ⭐ | 0 |
| **standard** | Most DDD projects | ⭐⭐ | 11 |
| **cqrs** | Read/Write separation | ⭐⭐⭐ | 14 |
| **event-sourcing** | Event-driven, audit trail | ⭐⭐⭐⭐ | 12 |
| **hexagonal** | Ports & Adapters, testable | ⭐⭐⭐ | 11 |
| **custom** | Specific needs | Variable | Variable |

---

## 🎯 Best Practices

### ✅ DO

- **Use presets** to maintain consistency in the project
- **Document with YAML** each important context
- **Use .gitkeep** in collaborative projects
- **Start with `--dry-run`** to validate
- **Use standard templates** before creating custom
- **Save presets** for recurring patterns

### ❌ DON'T

- Don't mix different templates without reason
- Don't create too many unnecessary sublayers
- Don't ignore team presets
- Don't skip documentation (YAML)
- Don't use `--force` without checking what you're overwriting

---

## 🆘 Troubleshooting

### Problem: "Preset not found"

```bash
# Check available presets
make:context --use-preset=list

# Verify location
ls -la .ddd-forge/presets/

# Create the preset if it doesn't exist
make:context Example --template=standard --save-preset=mypreset
```

### Problem: "Failed to create directory"

```bash
# Check permissions
ls -ld src/

# Use directory with correct permissions
make:context MyContext --dir=custom/path

# Force creation (use with caution)
make:context MyContext --force
```

### Problem: "Invalid template"

```bash
# See available templates
make:context --help | grep -A 10 "Available Templates"

# Valid templates:
# basic, standard, cqrs, event-sourcing, hexagonal
make:context Orders --template=cqrs
```

---

## 📚 Additional Resources

### DDD Documentation
- [Domain-Driven Design Reference](https://www.domainlanguage.com/ddd/reference/)
- [DDD Community](https://github.com/ddd-crew)

### Architectural Patterns
- **CQRS**: [Martin Fowler - CQRS](https://martinfowler.com/bliki/CQRS.html)
- **Event Sourcing**: [Event Sourcing Pattern](https://martinfowler.com/eaaDev/EventSourcing.html)
- **Hexagonal**: [Alistair Cockburn - Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/)

### Custom Templates
Need a specific template for your industry or project? You can contribute:
1. Create your perfect configuration with the wizard
2. Save it as a preset
3. Export to YAML
4. Share with the community

---
**Happy Coding! 🚀**