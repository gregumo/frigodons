import './styles/app.css';

import Modal from './js/modal/Modal';

const routes = require('./js/routes.json');
import Routing from 'fos-router';
Routing.setRoutingData(routes);

import { CalendarClass } from "./js/calendar"

window.onload = (event) => {
    window.dialogModal = new Modal('dialogModal');
    window.dialogModal.init();
    CalendarClass.init();
};

