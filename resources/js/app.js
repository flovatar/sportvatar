require('./actions');
require('./utils');

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist'
import Clipboard from "@ryangjchandler/alpine-clipboard"

Alpine.plugin(persist);
Alpine.plugin(Clipboard);


require('./app/app');
require('./app/home');
require('./app/marketplace');
require('./app/meme');
require('./app/showroom');
require('./app/leaderboard');
require('./app/builder');
require('./app/collection');
require('./app/sales');
require('./app/sportvatar');
require('./app/sportbit');
require('./app/chart');
require('./app/getstarted');
require('./app/pack');
require('./app/packSale');

window.Alpine = Alpine;

Alpine.start();