# Notion Lite

This project contains a small PHP example that mimics a very lightweight note taking app similar to Notion. It now includes Tailwind styling and many additional features such as categories, priorities, due dates, attachments, layout toggle and more.

## Requirements
- PHP 8

## Running locally
From the repository root run:

```bash
php -S 0.0.0.0:8000 -t notion-lite
```

Then open [http://localhost:8000/index.php](http://localhost:8000/index.php) in your browser.

The interface uses Tailwind via CDN and supports features like:
- tag and category filtering
- priority and due date sorting
- attachment uploads
- dark mode and layout toggle
- archiving and marking notes as done
