import '../styles/app.css';

import Modal from './Modal';
import Helper from "./Helper";
import { Calendar } from "./Calendar";

const routes = require('./routes.json');
import Routing from 'fos-router';
Routing.setRoutingData(routes);

window.onload = (event) => {
    window.dialogModal = new Modal('dialogModal');
    window.dialogModal.init();
    window.helper = new Helper();
    window.helper.init();
    Calendar.init();
};
