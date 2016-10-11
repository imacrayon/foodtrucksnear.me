<?php

date_default_timezone_set('America/Chicago');

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();
$dotenv->required('GOOGLE_CLIENT_TOKEN')->notEmpty();

class Geocoder
{
    protected $baseUrl = 'https://maps.google.com/maps/api/geocode/json';

    protected $client;

    private $key;

    public function __construct($key)
    {
        $this->key = $key;
        $this->client = new GuzzleHttp\Client;
    }

    public function getLocation($address)
    {
        $options = [
            'http_errors' => true,
            'query' => [
                'key' => $this->key,
                'address' => $address
            ]
        ];
        $location = new \stdClass();
        $location->name = $address;
        $response = json_decode($this->client->request('GET', $this->baseUrl, $options)->getBody());
        switch ($response->status) {
            case 'OK':
                $components = [];
                foreach($response->results[0]->address_components as $component) {
                    foreach($component->types as $type) {
                        // Grab the short name for states
                        $components[$type] = $component->short_name;
                    }
                }
                $location->street = $components['street_number'].' '.$components['route'];
                $location->city = $components['locality'];
                $location->state = $components['administrative_area_level_1'];
                $location->zip = $components['postal_code'];

                $coords = $response->results[0]->geometry->location;
                $location->latitude = $coords->lat;
                $location->longitude = $coords->lng;
                break;
            case 'ZERO_RESULTS':
                $location->street = null;
                $location->city = null;
                $location->state = null;
                $location->zip = null;
                $location->latitude = null;
                $location->longitude = null;
                break;
            default:
                throw new \Exception("Geocoder responded with a status of {$response->status}.");
        }

        return $location;
    }
}

class FoodTruck
{
    public $name;

    public $description;

    public $website;

    protected $calendar_id;

    public $events = [];

    private $key = '';

    protected $client;

    protected $geocoder;

    public function __construct()
    {
        $this->key = getenv('GOOGLE_CLIENT_TOKEN');
        $this->client = new GuzzleHttp\Client;
        $this->geocoder = new Geocoder($this->key);
    }

    public function setCalendar($id)
    {
        $this->calendar_id = $id;
    }

    public function events() {
        if ($this->calendar_id) {
            $options = [
                'http_errors' => true,
                'query' => [
                    'key' => $this->key,
                    'maxResults' => 10,
                    'orderBy' => 'startTime',
                    'singleEvents' => 'true',
                    'timeMin' => date('c'),
                ]
            ];

            $response = json_decode($this->client->request('GET', $this->getUrl(), $options)->getBody());

            if ($response->error) {
                throw new \Exception($response->error->message);
            }
            $parseEvent = function($item) {
                echo "[{$this->name}] Processing {$item->summary} ({$item->id})" . PHP_EOL;
                $event = new stdClass();
                $event->id = $item->id;
                $event->name = $item->summary;
                $event->start = $item->start;
                $event->end = $item->end;
                $event->description = $item->description;
                $event->food_truck = clone $this;
                unset($event->food_truck->events);
                if ($item->location) {
                    echo "[{$this->name}] Geolocating " . str_replace(PHP_EOL, '', $item->location) . PHP_EOL;
                    $event->location = $this->geocoder->getLocation($item->location);
                } else {
                    echo "[{$this->name}] NO LOCATION" . PHP_EOL . print_r($item, true) . PHP_EOL;
                }
                return $event;
            };
            if (!empty($response->items)) {
                $this->events = array_map($parseEvent, $response->items);
            } else {
                echo "[{$this->name}] NO EVENTS" . PHP_EOL;
            }
        } else {
            echo "[{$this->name}] NO CALENDAR ID" . PHP_EOL;
        }

        return $this->events;
    }

    public function getUrl()
    {
        return "https://www.googleapis.com/calendar/v3/calendars/{$this->calendar_id}/events";
    }
}

$foodTrucks = [];

$foodTruck = new FoodTruck();
$foodTruck->name = 'Brown Box Bakery';
$foodTruck->description = 'Gourmet Crème-Filled Cupcakes';
$foodTruck->website = 'http://www.thebrownboxbakery.com';
$foodTruck->setCalendar('ocbf0juu88opocri00fudvut20@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Brickhouse BBQ';
$foodTruck->description = 'Smoked, low & slow, mmmm….';
$foodTruck->website = 'http://www.brickhousebbq101.com';
$foodTruck->setCalendar('vdlp6fiscja2c7s32mbrqi54h8@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Charlie\'s Pizza Taco';
$foodTruck->description = 'Fresh. Baked. Food. Fast.';
$foodTruck->website = 'http://charliespizzataco.com';
$foodTruck->setCalendar(null);
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Funky Monkey Munchies';
$foodTruck->description = 'Funky Fusion';
$foodTruck->website = 'https://www.facebook.com/Funky-Monkey-Munchies-426857387410979';
$foodTruck->setCalendar('crk6tb583ait923iqe8b93r9rg@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Kamayan Truck';
$foodTruck->description = 'Asian Inspired Street Food';
$foodTruck->website = 'https://www.facebook.com/The-Kamayan-Truck-883352515033782';
$foodTruck->setCalendar('tqa0541umh0f2q6cheji9dqkg8@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Kind Kravings';
$foodTruck->description = 'Vegan. Organic. Non-GMO. Gluten Free Options';
$foodTruck->website = 'http://www.kindkravings.com';
$foodTruck->setCalendar('kindkravings@gmail.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Kona Ice';
$foodTruck->description = 'Nutritious & Delicious Shaved Ice Experience.';
$foodTruck->website = 'http://www.kona-ice.com';
$foodTruck->setCalendar(null);
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Let\'m Eat Brats';
$foodTruck->description = 'Authentic German Cuisine';
$foodTruck->website = 'http://www.letmeatbrats.net';
$foodTruck->setCalendar('letmeatbrats@gmail.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Noble House';
$foodTruck->description = 'Serving Authentic Hawaiian Food';
$foodTruck->website = 'https://www.facebook.com/Noble-House-Hawaiian-Plate-Lunch-360472477449560';
$foodTruck->setCalendar('ha3paav52ef5e4kfu6cfkr3vf0@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Sunflower Espresso';
$foodTruck->description = 'Coffee Shop on Wheels.';
$foodTruck->website = 'https://www.facebook.com/SunflowerEspressoICT';
$foodTruck->setCalendar('isvgoeosb3j0b3ihlhe81c2gv8@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'BS Sandwich Press';
$foodTruck->description = 'Sandwiches, Fries & More…';
$foodTruck->website = 'https://www.facebook.com/bssandwichpress';
$foodTruck->setCalendar('bv5h429nivnvi9dflvqoou3v14@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Lil Bit Burgers';
$foodTruck->description = 'Gourmet Burgers & More.';
$foodTruck->website = 'https://www.facebook.com/lilbitburgers';
$foodTruck->setCalendar(null);
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'The Flying Stove';
$foodTruck->description = 'Gourmet Street Cuisine';
$foodTruck->website = 'http://theflyingstove.com';
$foodTruck->setCalendar('8f3bhe4q87l236uaahg8mntqak@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'The Garden of Eatin\'';
$foodTruck->description = 'Natural & Organic. Healthy & Indulgent.';
$foodTruck->website = 'https://www.facebook.com/gardenofeatinks';
$foodTruck->setCalendar(null);
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'Big Chill';
$foodTruck->description = 'Soft Serve. Italian Ice.';
$foodTruck->website = 'http://www.bigchill-icecream.com';
$foodTruck->setCalendar('iperab2gsi7d3voboj3cm169ug@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'U Hungry';
$foodTruck->description = 'U Hungry? Eat Here!  810-210-2592';
$foodTruck->website = 'https://www.facebook.com/U-Hungry-TRUCK-258398337846258';
$foodTruck->setCalendar('c6csj2o3lmtrd65b1i1vs927n4@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$foodTruck = new FoodTruck();
$foodTruck->name = 'The Big Apple';
$foodTruck->description = 'NY Style Street Food';
$foodTruck->website = 'http://www.thebigapplefoodtruck.weebly.com';
$foodTruck->setCalendar('247kmp2t11jdlu1ibvmkfelvc4@group.calendar.google.com');
$foodTrucks[] = $foodTruck;

$events = [];
foreach($foodTrucks as $foodTruck) {
    try {
        foreach($foodTruck->events() as $event) {
            $events[] = $event;
        }
        sleep(1);
    } catch (\Exception $e) {
        echo $e->getMessage() . PHP_EOL;
    }
}
function sortByStart($a, $b) {
    return strtotime($a->start->dateTime) - strtotime($b->start->dateTime);
}
usort($events, 'sortByStart');

$fp = fopen(__DIR__ . '/../static/data/events.json', 'w');
fwrite($fp, json_encode($events));
fclose($fp);

echo "Events written to /static/data/events.json" . PHP_EOL;
