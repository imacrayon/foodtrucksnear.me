# foodtrucksnear.me

A food truck locator for Wichita, KS.

## Setup

### .env

Copy the `.env.example` file and rename it to `.env`. This file stores all your special environment stuff.

### Google API

You'll need a Google API Key to do the fancy location stuff.

Create a new project in the [Developer's Console](https://console.developers.google.com). Ensure that both the "Google Calendar API" and "Google Maps Geocoding API" are both enabled on the "API Manager" page.

On the "Credentials" page follow the steps to generate a new browser key.

Paste your generated API key onto the `GOOGLE_CLIENT_TOKEN` line in your `.env` file.

Run some install commands:
``` bash
# install JavaScript dependencies
npm install

# install PHP dependencies
composer install
```

## Data

Food truck information is pulled from Google Calendar. All the magic for collecting this data is in `service/foodoo.php`. Running this script will generate a JSON file in `static/data/events.json` with all the food truck data awesomeness.
``` bash
# generate food truck data
php -f service/foodoo.php
```

## Build

Once you've got all your dependencies installed and your food truck data generated, you're ready to fire up the app.

``` bash
# serve with hot reload at localhost:8080
npm run dev

# build for production with minification
npm run build
```
