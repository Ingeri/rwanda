# Rwanda Administrative Navigator

A small, browser-based directory for exploring Rwanda's administrative hierarchy: **province -> district -> sector -> cell -> village**.

## Run locally

The page loads `data.json` with JavaScript, so it should be opened through a local web server rather than directly as a `file://` page.

```bash
python3 -m http.server 8000
```

Open http://localhost:8000 in your browser.

## Project files

- `index.html` - page structure and accessible navigation controls
- `index.css` - responsive visual design
- `app.js` - dependent selectors and directory loading
- `data.json` - hierarchical location data
- `save_to_mysql.py` - optional script for importing the data into MySQL

## Data model

Each location follows the same nesting used by the import script: province, district, sector, cell, then a list of villages. Changing a higher-level selection clears the levels below it to prevent invalid combinations.
