# Routing Plan Flow

This document explains the flow for generating a routing plan for sales representatives based on store visit frequency and proximity. The plan divides visits among 10 sales reps, ensuring no more than 30 stores per day for each rep.

## Table of Contents
1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Usage](#usage)
    - [Run with Docker Compose](#run-with-docker)
    - [Run Locally](#run-locally)
4. [Generating the Routing Plan](#generating-the-routing-plan)
5. [System Flow](#flow-overview)

## Requirements
- Docker
- Docker Compose
- PHP 8.4
- Composer
- Laravel 10.x

## Installation

### Clone the Repository
```bash
git clone https://github.com/Arufiand/TMS
cd TMS
```
### Run with Docker
1. Ensure that Docker and Docker Compose are installed on your machine.
2. Build and start the Docker containers:

```bash
docker compose up -d
```

3. Run the Composer commands inside the Docker container:

```bash
docker compose run --rm composer install
```

4. Copy and paste the stores.csv onto :
```
storage/app
```

5. Run Artisan migrations:
```bash
docker compose run --rm artisan generate:routing-plan
```

### Run Locally
If you're not using Docker, you can run the project on your local machine:

1. Install the project Dependencies :
```bash
composer install
```

2. Copy and paste the stores.csv onto :
```
storage/app
```

3. Generate Routing plan : 
```bash
php artisan generate:routing-plan
```

### Generating the routing plan
The generated file of excel can be found in Storage
```
storage/app/private/routing_plan.xlsx
```
## Flow Overview

1. **Load Data:**
    - Read store data from the CSV file.
    - Extract and categorize stores into three frequency types:
        - Weekly
        - Biweekly
        - Monthly

2. **Date Generation:**
    - Generate a list of dates from October 1st to October 31st, excluding Sundays.
    - Each date represents a potential day for store visits.

3. **Haversine Distance Calculation:**
    - Use the HQ’s latitude and longitude as the starting point.
    - For each sales representative, calculate the Haversine distance between the HQ and the stores to assign the closest stores to each rep.

4. **Store Assignment:**
    - Distribute stores among 10 sales reps.
    - Each sales rep visits a maximum of 30 stores per day.
    - Store visits are divided according to the frequency (weekly, biweekly, monthly):
        - Weekly stores are visited 4 times a month.
        - Biweekly stores are visited 2 times a month.
        - Monthly stores are visited once a month.

5. **Excel Export:**
    - The final routing plan is exported to an Excel file. It contains:
        - Date of the visit
        - Sales representative assigned to visit
        - List of stores assigned to the rep on that day.

## Flowchart

Below is a simplified flowchart of the routing plan:

```plaintext
+-------------------------+
|    Load CSV Data         |
+-------------------------+
            |
            v
+-------------------------+
|   Categorize Stores by   |
|   Visit Frequency        |
+-------------------------+
            |
            v
+-------------------------+
| Generate Date Range      |
| (Oct 1 - Oct 31, excl.   |
| Sundays)                |
+-------------------------+
            |
            v
+-------------------------+
|  Calculate Haversine     |
|  Distance from HQ        |
+-------------------------+
            |
            v
+-------------------------+
|  Assign Stores to Sales  |
|  Reps (Max 30 per day)   |
+-------------------------+
            |
            v
+-------------------------+
| Export Routing Plan to   |
| Excel                   |
+-------------------------+
