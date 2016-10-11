<template>
  <div v-if="sortedEvents.length" id="events">
    <h1 class="events-status">{{nearbyCount
        ? 'There ' + (nearbyCount > 1 ? 'are ' : 'is ') + nearbyCount + ' food truck' + (nearbyCount > 1 ? 's' : '') + ' within a mile of you right now!'
        : 'Sorry, there are no food trucks close right now.'}}</h1>
    <div class="event" v-for="event in sortedEvents">
      <div class="event-body">
        <h1 class="event-name">{{event.food_truck.name}}</h1>
        <div class="event-location">{{event.distance ? event.location.street : event.name}}</div>
        <div class="event-time">{{event.time}}</div>
      </div>
      <a v-if="event.distance" class="event-distance" target="_blank" :href="'https://www.google.com/maps/dir/Current+Location/' + event.location.latitude + ',' + event.location.longitude">
        {{event.distance}}<small>mi</small>
      </a>
    </div>
  </div>
</template>

<script>
import moment from 'moment'
import { currentPosition, distance } from '../lib/location'
import Http from '../lib/http'
export default {
  created () {
    currentPosition().then(({ latitude, longitude }) => {
      this.position.latitude = latitude
      this.position.longitude = longitude
      this.fetchEvents().then(events => {
        this.events = events
      })
    }, errors => {
      console.log('LOCATION ERROR')
      console.log(errors)
    })
  },
  data () {
    return {
      position: {
        latitude: 37.699011,
        longitude: -97.3439585
      },
      events: [],
      nearbyCount: 0
    }
  },
  computed: {
    sortedEvents () {
      const NOW = moment()
      return this.events.reduce((arr, event) => {
        // Exclude past events
        event.end.moment = moment(event.end.dateTime)
        if (!event.end.moment.isBefore()) {
          // Set distance
          if (event.location && event.location.latitude && event.location.longitude) {
            event.distance = distance(this.position.latitude, this.position.longitude, event.location.latitude, event.location.longitude).toFixed(1)
          }
          // Exclude events too far away
          if (!event.distance || event.distance < 50) {
            // Set time
            event.start.moment = moment(event.start.dateTime)
            event.time = ' - ' + event.end.moment.format('h:mm A')
            if (!event.start.moment.isBefore()) {
              event.time = event.start.moment.calendar() + event.time
            } else {
              this.nearbyCount = event.distance > 1 ? this.nearbyCount : this.nearbyCount + 1
              event.time = 'now' + event.time
              // Normalize all current events so they can be sorted by distance
              event.start.moment = NOW
            }
            arr.push(event)
          }
        }
        return arr
      }, []).sort((a, b) => {
        if (a.start.moment.isBefore(b.start.moment)) {
          return -1
        } else if (a.start.moment.isAfter(b.start.moment)) {
          return 1
        } else {
          if (a.distance < b.distance) {
            return -1
          } else if (a.distance > b.distance) {
            return 1
          } else {
            return 0
          }
        }
      })
    }
  },
  methods: {
    fetchEvents () {
      const http = new Http('/static/data/events.json')
      return http.get().then(response => {
        return response
      }, errors => {
        console.log('ERROR')
        console.log(errors)
      })
    }
  }
}
</script>

<style lang="scss" scoped>
@import '../scss/variables';

#events {
  width: 36em;
  max-width: 100%;
  height: 100%;
  margin: 0 auto;
}

.events-status {
  font-size: 5vw;
  line-height: 1.2;
  padding: 1rem;
  margin: 0;
}

.contact-form {
  margin: 2em 0;
}
#contact-status {
  color: $red;
}

.contact {
  padding: 0 1rem;
  text-align: center;
  .btn:hover {
    background: $blue;
  }
}

.modal {
  display: none;
  &.active {
    display: block;
  }
}

.event {
  font-size: 4vw;
  padding: 1rem;
  display: block;
  text-decoration: none;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}
.event-name {
  display: block;
  margin: 0;
  line-height: 1.2;
  color: $blue;
  font-weight: 100;
  font-size: 1.5em;
}
.event-distance {
  display: inline-block;
  color: lighten($blue, 20);
  border-radius: 2px;
  font-size: 2em;
  small {
    font-size: 10px;
  }
}
.event-location,
.event-time {
  display: block;
  color: $gray;
  text-transform: uppercase;
  font-size: .8em;
  text-decoration: none;
}
@media (min-width: 36em) {
  #status {
    font-size: 1.5em;
  }
  .event {
    font-size: 1.5em;
  }
}
</style>
