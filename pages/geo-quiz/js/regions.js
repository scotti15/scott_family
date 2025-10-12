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

  africa: {
    displayName: 'Africa',
    mapFile: '../geo-quiz/maps/africa.svg',
    names: {
      DZ: 'Algeria', AO: 'Angola', BJ: 'Benin', BW: 'Botswana', BF: 'Burkina Faso',
      BI: 'Burundi', CV: 'Cabo Verde', CM: 'Cameroon', CF: 'Central African Republic',
      TD: 'Chad', KM: 'Comoros', CI: 'Côte d\'Ivoire', CD: 'Democratic Republic of the Congo',
      DJ: 'Djibouti', EG: 'Egypt', GQ: 'Equatorial Guinea', ER: 'Eritrea', SZ: 'Eswatini',
      ET: 'Ethiopia', GA: 'Gabon', GM: 'The Gambia', GH: 'Ghana', GN: 'Guinea',
      GW: 'Guinea-Bissau', KE: 'Kenya', LS: 'Lesotho', LR: 'Liberia', LY: 'Libya',
      MG: 'Madagascar', MW: 'Malawi', ML: 'Mali', MR: 'Mauritania', MU: 'Mauritius',
      MA: 'Morocco', MZ: 'Mozambique', NA: 'Namibia', NE: 'Niger', NG: 'Nigeria',
      RW: 'Rwanda', ST: 'São Tomé and Príncipe', SN: 'Senegal', SC: 'Seychelles',
      SL: 'Sierra Leone', SO: 'Somalia', ZA: 'South Africa', SS: 'South Sudan',
      SD: 'Sudan', TZ: 'Tanzania', TG: 'Togo', TN: 'Tunisia', UG: 'Uganda',
      ZM: 'Zambia', ZW: 'Zimbabwe'
    },
    capitals: {
      DZ: 'Algiers', AO: 'Luanda', BJ: 'Porto-Novo', BW: 'Gaborone', BF: 'Ouagadougou',
      BI: 'Bujumbura', CV: 'Praia', CM: 'Yaoundé', CF: 'Bangui', TD: 'N\'Djamena',
      KM: 'Moroni', CI: 'Yamoussoukro', CD: 'Kinshasa', DJ: 'Djibouti', EG: 'Cairo',
      GQ: 'Malabo', ER: 'Asmara', SZ: 'Mbabane', ET: 'Addis Ababa', GA: 'Libreville',
      GM: 'Banjul', GH: 'Accra', GN: 'Conakry', GW: 'Bissau', KE: 'Nairobi',
      LS: 'Maseru', LR: 'Monrovia', LY: 'Tripoli', MG: 'Antananarivo', MW: 'Lilongwe',
      ML: 'Bamako', MR: 'Nouakchott', MU: 'Port Louis', MA: 'Rabat', MZ: 'Maputo',
      NA: 'Windhoek', NE: 'Niamey', NG: 'Abuja', RW: 'Kigali', ST: 'São Tomé',
      SN: 'Dakar', SC: 'Victoria', SL: 'Freetown', SO: 'Mogadishu', ZA: 'Pretoria',
      SS: 'Juba', SD: 'Khartoum', TZ: 'Dodoma', TG: 'Lomé', TN: 'Tunis',
      UG: 'Kampala', ZM: 'Lusaka', ZW: 'Harare'
    }
  },

  europe: {
    displayName: 'Europe',
    mapFile: '../geo-quiz/maps/europe.svg',
    names: {
      AL: 'Albania', AD: 'Andorra', AM: 'Armenia', AT: 'Austria', AZ: 'Azerbaijan',
      BY: 'Belarus', BE: 'Belgium', BA: 'Bosnia and Herzegovina', BG: 'Bulgaria',
      HR: 'Croatia', CY: 'Cyprus', CZ: 'Czech Republic', DK: 'Denmark', EE: 'Estonia',
      FI: 'Finland', FR: 'France', GE: 'Georgia', DE: 'Germany', GR: 'Greece',
      HU: 'Hungary', IS: 'Iceland', IE: 'Ireland', IT: 'Italy', KZ: 'Kazakhstan',
      XK: 'Kosovo', LV: 'Latvia', LI: 'Liechtenstein', LT: 'Lithuania', LU: 'Luxembourg',
      MK: 'North Macedonia', MT: 'Malta', MD: 'Moldova', MC: 'Monaco', ME: 'Montenegro',
      NL: 'Netherlands', NO: 'Norway', PL: 'Poland', PT: 'Portugal', RO: 'Romania',
      RU: 'Russia', RS: 'Serbia', SK: 'Slovakia', SI: 'Slovenia', ES: 'Spain',
      SE: 'Sweden', CH: 'Switzerland', UA: 'Ukraine', GB: 'United Kingdom', VA: 'Vatican City'
    },
    capitals: {
      AL: 'Tirana', AD: 'Andorra la Vella', AM: 'Yerevan', AT: 'Vienna', AZ: 'Baku',
      BY: 'Minsk', BE: 'Brussels', BA: 'Sarajevo', BG: 'Sofia', HR: 'Zagreb',
      CY: 'Nicosia', CZ: 'Prague', DK: 'Copenhagen', EE: 'Tallinn', FI: 'Helsinki',
      FR: 'Paris', GE: 'Tbilisi', DE: 'Berlin', GR: 'Athens', HU: 'Budapest',
      IS: 'Reykjavík', IE: 'Dublin', IT: 'Rome', KZ: 'Astana', XK: 'Pristina',
      LV: 'Riga', LI: 'Vaduz', LT: 'Vilnius', LU: 'Luxembourg City', MK: 'Skopje',
      MT: 'Valletta', MD: 'Chi?in?u', MC: 'Monaco', ME: 'Podgorica', NL: 'Amsterdam',
      NO: 'Oslo', PL: 'Warsaw', PT: 'Lisbon', RO: 'Bucharest', RU: 'Moscow',
      RS: 'Belgrade', SK: 'Bratislava', SI: 'Ljubljana', ES: 'Madrid', SE: 'Stockholm',
      CH: 'Bern', UA: 'Kyiv', GB: 'London', VA: 'Vatican City'
    }
  },

  asia: {
    displayName: 'Asia',
    mapFile: '../geo-quiz/maps/asia.svg',
    names: {
      AF: 'Afghanistan', AM: 'Armenia', AZ: 'Azerbaijan', BH: 'Bahrain', BD: 'Bangladesh',
      BT: 'Bhutan', BN: 'Brunei', KH: 'Cambodia', CN: 'China', CY: 'Cyprus',
      GE: 'Georgia', IN: 'India', ID: 'Indonesia', IR: 'Iran', IQ: 'Iraq',
      IL: 'Israel', JP: 'Japan', JO: 'Jordan', KZ: 'Kazakhstan', KW: 'Kuwait',
      KG: 'Kyrgyzstan', LA: 'Laos', LB: 'Lebanon', MY: 'Malaysia', MV: 'Maldives',
      MN: 'Mongolia', MM: 'Myanmar', NP: 'Nepal', KP: 'North Korea', OM: 'Oman',
      PK: 'Pakistan', PH: 'Philippines', QA: 'Qatar', SA: 'Saudi Arabia', SG: 'Singapore',
      KR: 'South Korea', LK: 'Sri Lanka', SY: 'Syria', TW: 'Taiwan', TJ: 'Tajikistan',
      TH: 'Thailand', TL: 'Timor-Leste', TR: 'Turkey', TM: 'Turkmenistan', AE: 'United Arab Emirates',
      UZ: 'Uzbekistan', VN: 'Vietnam', YE: 'Yemen'
    },
    capitals: {
      AF: 'Kabul', AM: 'Yerevan', AZ: 'Baku', BH: 'Manama', BD: 'Dhaka',
      BT: 'Thimphu', BN: 'Bandar Seri Begawan', KH: 'Phnom Penh', CN: 'Beijing', CY: 'Nicosia',
      GE: 'Tbilisi', IN: 'New Delhi', ID: 'Jakarta', IR: 'Tehran', IQ: 'Baghdad',
      IL: 'Jerusalem', JP: 'Tokyo', JO: 'Amman', KZ: 'Astana', KW: 'Kuwait City',
      KG: 'Bishkek', LA: 'Vientiane', LB: 'Beirut', MY: 'Kuala Lumpur', MV: 'Malé',
      MN: 'Ulaanbaatar', MM: 'Naypyidaw', NP: 'Kathmandu', KP: 'Pyongyang', OM: 'Muscat',
      PK: 'Islamabad', PH: 'Manila', QA: 'Doha', SA: 'Riyadh', SG: 'Singapore',
      KR: 'Seoul', LK: 'Colombo', SY: 'Damascus', TW: 'Taipei', TJ: 'Dushanbe',
      TH: 'Bangkok', TL: 'Dili', TR: 'Ankara', TM: 'Ashgabat', AE: 'Abu Dhabi',
      UZ: 'Tashkent', VN: 'Hanoi', YE: 'Sana\'a'
    }
  },

  southAmerica: {
  displayName: 'South America',
  mapFile: '../geo-quiz/maps/south_america.svg',
  names: {
    AR: 'Argentina',
    BO: 'Bolivia',
    BR: 'Brazil',
    CL: 'Chile',
    CO: 'Colombia',
    EC: 'Ecuador',
    GY: 'Guyana',
    PY: 'Paraguay',
    PE: 'Peru',
    SR: 'Suriname',
    UY: 'Uruguay',
    VE: 'Venezuela'
  },
  capitals: {
    AR: 'Buenos Aires',
    BO: 'Sucre',          // Constitutional capital
    BR: 'Brasília',
    CL: 'Santiago',
    CO: 'Bogotá',
    EC: 'Quito',
    GY: 'Georgetown',
    PY: 'Asunción',
    PE: 'Lima',
    SR: 'Paramaribo',
    UY: 'Montevideo',
    VE: 'Caracas'
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
