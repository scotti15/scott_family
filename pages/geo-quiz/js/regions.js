// regions.js
// Flat list of regions for the quiz with capitals

const REGIONS = {
  usa: {
    displayName: 'United States',
    mapFile: '../geo-quiz/maps/us.svg',
    names: {
      AL: 'Alabama', AK: 'Alaska', AZ: 'Arizona', AR: 'Arkansas',
      CA: 'California', CO: 'Colorado', CT: 'Connecticut', DE: 'Delaware',
      FL: 'Florida', GA: 'Georgia', HI: 'Hawaii', ID: 'Idaho', IL: 'Illinois',
      IN: 'Indiana', IA: 'Iowa', KS: 'Kansas', KY: 'Kentucky', LA: 'Louisiana',
      ME: 'Maine', MD: 'Maryland', MA: 'Massachusetts', MI: 'Michigan',
      MN: 'Minnesota', MS: 'Mississippi', MO: 'Missouri', MT: 'Montana',
      NE: 'Nebraska', NV: 'Nevada', NH: 'New Hampshire', NJ: 'New Jersey',
      NM: 'New Mexico', NY: 'New York', NC: 'North Carolina', ND: 'North Dakota',
      OH: 'Ohio', OK: 'Oklahoma', OR: 'Oregon', PA: 'Pennsylvania', RI: 'Rhode Island',
      SC: 'South Carolina', SD: 'South Dakota', TN: 'Tennessee', TX: 'Texas',
      UT: 'Utah', VT: 'Vermont', VA: 'Virginia', WA: 'Washington',
      WV: 'West Virginia', WI: 'Wisconsin', WY: 'Wyoming'
    },
    capitals: {
      AL: 'Montgomery', AK: 'Juneau', AZ: 'Phoenix', AR: 'Little Rock',
      CA: 'Sacramento', CO: 'Denver', CT: 'Hartford', DE: 'Dover',
      FL: 'Tallahassee', GA: 'Atlanta', HI: 'Honolulu', ID: 'Boise',
      IL: 'Springfield', IN: 'Indianapolis', IA: 'Des Moines', KS: 'Topeka',
      KY: 'Frankfort', LA: 'Baton Rouge', ME: 'Augusta', MD: 'Annapolis',
      MA: 'Boston', MI: 'Lansing', MN: 'Saint Paul', MS: 'Jackson',
      MO: 'Jefferson City', MT: 'Helena', NE: 'Lincoln', NV: 'Carson City',
      NH: 'Concord', NJ: 'Trenton', NM: 'Santa Fe', NY: 'Albany',
      NC: 'Raleigh', ND: 'Bismarck', OH: 'Columbus', OK: 'Oklahoma City',
      OR: 'Salem', PA: 'Harrisburg', RI: 'Providence', SC: 'Columbia',
      SD: 'Pierre', TN: 'Nashville', TX: 'Austin', UT: 'Salt Lake City',
      VT: 'Montpelier', VA: 'Richmond', WA: 'Olympia', WV: 'Charleston',
      WI: 'Madison', WY: 'Cheyenne'
    }
  },

  canada: {
    displayName: 'Canada',
    mapFile: '../geo-quiz/maps/canada.svg',
    names: {
      AB: 'Alberta', BC: 'British Columbia', MB: 'Manitoba', NB: 'New Brunswick',
      NL: 'Newfoundland and Labrador', NS: 'Nova Scotia', ON: 'Ontario',
      PE: 'Prince Edward Island', QC: 'Quebec', SK: 'Saskatchewan',
      NT: 'Northwest Territories', NU: 'Nunavut', YT: 'Yukon'
    },
    capitals: {
      AB: 'Edmonton', BC: 'Victoria', MB: 'Winnipeg', NB: 'Fredericton',
      NL: 'St. John\'s', NS: 'Halifax', ON: 'Toronto', PE: 'Charlottetown',
      QC: 'Quebec City', SK: 'Regina', NT: 'Yellowknife', NU: 'Iqaluit',
      YT: 'Whitehorse'
    }
  },

  europe: {
    displayName: 'Europe',
    mapFile: '../geo-quiz/maps/europe.svg',
    names: {
      FR: 'France', DE: 'Germany', IT: 'Italy', ES: 'Spain', PT: 'Portugal',
      NL: 'Netherlands', BE: 'Belgium', LU: 'Luxembourg', CH: 'Switzerland',
      AT: 'Austria', DK: 'Denmark', NO: 'Norway', SE: 'Sweden', FI: 'Finland',
      EE: 'Estonia', LV: 'Latvia', LT: 'Lithuania', PL: 'Poland', CZ: 'Czech Republic',
      SK: 'Slovakia', HU: 'Hungary', SI: 'Slovenia', HR: 'Croatia', BA: 'Bosnia and Herzegovina',
      RS: 'Serbia', ME: 'Montenegro', MK: 'North Macedonia', GR: 'Greece', BG: 'Bulgaria',
      RO: 'Romania', IE: 'Ireland', UK: 'United Kingdom', IS: 'Iceland'
    },
    capitals: {
      FR: 'Paris', DE: 'Berlin', IT: 'Rome', ES: 'Madrid', PT: 'Lisbon',
      NL: 'Amsterdam', BE: 'Brussels', LU: 'Luxembourg', CH: 'Bern', AT: 'Vienna',
      DK: 'Copenhagen', NO: 'Oslo', SE: 'Stockholm', FI: 'Helsinki', EE: 'Tallinn',
      LV: 'Riga', LT: 'Vilnius', PL: 'Warsaw', CZ: 'Prague', SK: 'Bratislava',
      HU: 'Budapest', SI: 'Ljubljana', HR: 'Zagreb', BA: 'Sarajevo', RS: 'Belgrade',
      ME: 'Podgorica', MK: 'Skopje', GR: 'Athens', BG: 'Sofia', RO: 'Bucharest',
      IE: 'Dublin', UK: 'London', IS: 'Reykjavik'
    }
  },

  africa: {
    displayName: 'Africa',
    mapFile: '../geo-quiz/maps/africa.svg',
    names: {
      NG: 'Nigeria', EG: 'Egypt', ZA: 'South Africa', KE: 'Kenya', DZ: 'Algeria'
    },
    capitals: {
      NG: 'Abuja', EG: 'Cairo', ZA: 'Pretoria', KE: 'Nairobi', DZ: 'Algiers'
    }
  },

  asia: {
    displayName: 'Asia',
    mapFile: '../geo-quiz/maps/asia.svg',
    names: {
      CN: 'China', IN: 'India', JP: 'Japan', KR: 'South Korea', ID: 'Indonesia'
    },
    capitals: {
      CN: 'Beijing', IN: 'New Delhi', JP: 'Tokyo', KR: 'Seoul', ID: 'Jakarta'
    }
  },

  southAmerica: {
    displayName: 'South America',
    mapFile: '../geo-quiz/maps/south_america.svg',
    names: {
      BR: 'Brazil', AR: 'Argentina', CO: 'Colombia', PE: 'Peru', VE: 'Venezuela'
    },
    capitals: {
      BR: 'Brasília', AR: 'Buenos Aires', CO: 'Bogotá', PE: 'Lima', VE: 'Caracas'
    }
  },

  barbados: {
    displayName: 'Barbados',
    mapFile: '../geo-quiz/maps/barbados.svg',
    names: {
      BB01: 'Christ Church', BB02: 'Saint Andrew', BB03: 'Saint George',
      BB04: 'Saint James', BB05: 'Saint John', BB06: 'Saint Joseph',
      BB07: 'Saint Lucy', BB08: 'Saint Michael', BB09: 'Saint Peter',
      BB10: 'Saint Philip', BB11: 'Saint Thomas'
    },
    capitals: {
      BB01: 'Gibbs', BB02: 'Belleplaine', BB03: 'Parish Church',
      BB04: 'Holetown', BB05: 'Bathsheba', BB06: 'Farley Hill',
      BB07: 'Hothersal', BB08: 'Bridgetown', BB09: 'Speightstown',
      BB10: 'Oistins', BB11: 'Welchman Hall'
    }
  }
};
