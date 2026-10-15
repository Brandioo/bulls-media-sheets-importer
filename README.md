# Bulls Media Sheets Importer

A Laravel + React application for importing data from multiple Google Sheets into dynamically configured MySQL tables.

The application allows Google Sheets, tabs, database targets, and column mappings to be configured without hard-coding individual spreadsheet structures.

## Features

- Import data from multiple Google Sheets
- Configure multiple tabs per spreadsheet
- Configure multiple database targets per tab
- Map Google Sheet columns to different database column names
- Support Sheets where columns are in different positions
- Automatically create destination database tables
- Automatically add newly configured database columns
- Manual imports from the web interface
- Daily scheduled imports
- Import history with read/written row counts and status
- React administration interface
- Google Sheets API integration
- MySQL database
- phpMyAdmin integration with DDEV
- Automated tests
- Ubuntu setup script

## Tech Stack

- PHP 8.4
- Laravel 13
- MySQL 8.0
- React
- Vite
- Tailwind CSS
- Google Sheets API
- Docker
- DDEV

## Architecture

The main import services are located in:

```text
app/Services/GoogleSheets/GoogleSheetsService.php
app/Services/Import/ColumnMapper.php
app/Services/Import/TableSchemaManager.php
app/Services/Import/SheetImporter.php
```

The configuration structure is:

```text
SheetSource
└── SheetTab
    └── ImportTarget
        └── ColumnMapping
```

Import executions are stored in:

```text
ImportRun
```

A single Google Sheet tab can have multiple import targets with different database structures.

## Example Mapping

A Google Sheet may contain:

```text
date | product_name | price | amount
```

The database target may use:

```text
date         -> sale_date
product_name -> name
price        -> product_price
amount       -> amount
```

The source column names therefore do not need to match the database column names.

Column positions in the Google Sheet are not important because mapping is based on the header name.

## Supported Database Types

Configured mappings support:

```text
string
text
integer
bigInteger
decimal
boolean
date
datetime
json
```

## Import Modes

The application supports:

```text
insert
truncate_insert
upsert
```

### insert

Adds imported rows to the existing table.

### truncate_insert

Removes existing table data before inserting the latest Google Sheet data.

### upsert

Updates existing rows or inserts new rows using configured unique columns.

## Dynamic Database Schema

Destination tables do not require a Laravel migration for every imported structure.

`TableSchemaManager` automatically creates a configured target table if it does not exist.

If a new column mapping is added later and the database column does not exist, the application automatically adds that column.

Example:

```text
Google Sheet column: supplier
Database column: supplier_name
Type: string
```

The importer can automatically add:

```text
supplier_name VARCHAR(255)
```

to the target table.

## Local Installation

Clone the repository:

```bash
git clone https://github.com/Brandioo/bulls-media-sheets-importer.git
cd bulls-media-sheets-importer
```

Start DDEV:

```bash
ddev start
```

Install PHP dependencies:

```bash
ddev composer install
```

Install frontend dependencies:

```bash
ddev npm install
```

Create the Laravel environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
ddev artisan key:generate
```

Run database migrations:

```bash
ddev artisan migrate
```

Build the frontend:

```bash
ddev npm run build
```

The application is available at:

```text
http://bulls-media-sheets-importer.ddev.site/
```

## Database Configuration

DDEV uses the following MySQL configuration:

```text
Host: db
Port: 3306
Database: db
Username: db
Password: db
```

Laravel configuration:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=db
DB_USERNAME=db
DB_PASSWORD=db
```

Open the MySQL CLI:

```bash
ddev mysql
```

Example query:

```bash
ddev mysql -e "SELECT * FROM google_sales;"
```

## phpMyAdmin

phpMyAdmin is included as a DDEV add-on.

Open it with:

```bash
ddev phpmyadmin
```

Select the database:

```text
db
```

## Google Sheets API Setup

Create a project in Google Cloud Console.

Enable:

```text
Google Sheets API
```

Then create a Service Account:

```text
IAM & Admin
→ Service Accounts
→ Create Service Account
```

Open the created Service Account and create a JSON key:

```text
Keys
→ Add Key
→ Create New Key
→ JSON
```

Store the downloaded JSON file here:

```text
storage/app/private/google/service-account.json
```

Do not commit this file to Git.

The project ignores Google credential JSON files through `.gitignore`.

Configure the following values in `.env`:

```env
GOOGLE_SHEETS_APP_NAME="Bulls Media Sheets Importer"
GOOGLE_SHEETS_CREDENTIALS="storage/app/private/google/service-account.json"
```

The Service Account email can be found with:

```bash
grep '"client_email"' storage/app/private/google/service-account.json
```

Share every Google Sheet that should be imported with this email.

`Viewer` permission is sufficient because the application requests read-only access to Google Sheets.

## Example Google Sheet

Create a tab named:

```text
Sales
```

Example data:

| date | product_name | price | amount |
|---|---|---:|---:|
| 2026-10-15 | MacBook | 1500.50 | 2 |
| 2026-10-16 | Keyboard | 99.99 | 5 |
| 2026-10-17 | Monitor | 420.00 | 3 |

Each value must be stored in its own Google Sheet cell.

For example:

```text
A1 = date
B1 = product_name
C1 = price
D1 = amount
```

## Using the Web Interface

Open:

```text
http://bulls-media-sheets-importer.ddev.site/
```

Configure an import in this order:

```text
1. Add Google Sheet
2. Add Tab
3. Add Target
4. Add Column Mappings
5. Run Import
```

Example source:

```text
Name: Real Google Sales
Spreadsheet ID: Google spreadsheet ID
```

Example tab:

```text
Name: Sales
Header row: 1
```

Example target:

```text
Database table: google_sales
Write mode: truncate_insert
```

Example mappings:

```text
date         -> sale_date       -> date
product_name -> name            -> string
price        -> product_price   -> decimal
amount       -> amount          -> integer
```

After running the import, the result can be checked with:

```bash
ddev mysql -e "SELECT id, sale_date, name, product_price, amount FROM google_sales;"
```

Example result:

```text
+----+------------+----------+---------------+--------+
| id | sale_date  | name     | product_price | amount |
+----+------------+----------+---------------+--------+
|  1 | 2026-10-15 | MacBook  |       1500.50 |      2 |
|  2 | 2026-10-16 | Keyboard |         99.99 |      5 |
|  3 | 2026-10-17 | Monitor  |        420.00 |      3 |
+----+------------+----------+---------------+--------+
```

## Artisan Import Command

Import all active configured sources:

```bash
ddev artisan sheets:import
```

Import only one source:

```bash
ddev artisan sheets:import --source=2
```

## Scheduled Import

The Laravel scheduler is configured in:

```text
routes/console.php
```

The Google Sheets import runs daily at:

```text
02:00
```

Check the schedule with:

```bash
ddev artisan schedule:list
```

For local development:

```bash
ddev artisan schedule:work
```

For production, configure Laravel's scheduler using cron:

```cron
* * * * * cd /path/to/bulls-media-sheets-importer && php artisan schedule:run >> /dev/null 2>&1
```

Laravel will then execute `sheets:import` according to the configured daily schedule.

## Import History

Every import creates an `ImportRun`.

The application records information including:

```text
source
status
rows read
rows written
start time
finish time
error message
metadata
```

Import history is also visible in the React dashboard.

## Ubuntu Setup Script

An Ubuntu setup script is included:

```text
scripts/setup-ubuntu.sh
```

Make it executable:

```bash
chmod +x scripts/setup-ubuntu.sh
```

Run it:

```bash
./scripts/setup-ubuntu.sh
```

The script installs or prepares the required Ubuntu development environment, including Docker and DDEV, and then installs the project dependencies.

If the script adds the current user to the Docker group, log out and log back in before running the script again.

Google Service Account credentials are intentionally not created by the script because private credentials must never be stored in Git.

## Tests

Run all tests:

```bash
ddev artisan test
```

The test suite covers:

```text
Column mapping and type conversion
Dynamic target table creation
Automatic database column creation
Google Sheet import workflow
```

Current test result:

```text
Tests: 5 passed (14 assertions)
```

## Security

Never commit:

```text
.env
storage/app/private/google/service-account.json
vendor/
node_modules/
```

Verify that the Google Service Account file is ignored:

```bash
git check-ignore storage/app/private/google/service-account.json
```

Expected output:

```text
storage/app/private/google/service-account.json
```

## Development Commands

Start the project:

```bash
ddev start
```

Stop the project:

```bash
ddev stop
```

Run tests:

```bash
ddev artisan test
```

Build frontend:

```bash
ddev npm run build
```

Run frontend development server:

```bash
ddev npm run dev
```

Open phpMyAdmin:

```bash
ddev phpmyadmin
```

Run imports:

```bash
ddev artisan sheets:import
```

## Author

Brand Citozi
