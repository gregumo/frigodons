import Routing from "fos-router";
import {CalendarClass} from "../calendar";

export default class Modal {
    constructor(id) {
        this.modal = document.getElementById(id);
        this.backdrop = document.getElementById(id + 'Backdrop');
        this.title = this.modal.querySelector('.title');
        this.content = this.modal.querySelector('.content');
        this.closebtn = this.modal.querySelector('.close');
        this.sendbtn = this.modal.querySelector('.send');
    }

    init() {
        this.closebtn.addEventListener('click', this.close.bind(this), false);
        this.sendbtn.addEventListener('click', this.send.bind(this), false);
    }

    open(data) {
        console.log(data);
        if(!data.modal) {
            return;
        }

        this.title.innerHTML = data.title;
        this.content.innerHTML = data.content;
        this.closebtn.querySelector('.text').innerHTML = data.closebtn;

        if(data.displaysendbtn) {
            this.sendbtn.dataset.day = data.day;
            this.sendbtn.dataset.route = data.route;
            this.sendbtn.dataset.method = data.method;
            this.sendbtn.dataset.context = data.context;
            this.sendbtn.querySelector('.text').innerHTML = data.sendbtn;
            this.sendbtn.classList.remove('hidden');
        } else {
            this.sendbtn.classList.add('hidden');
        }

        this.modal.classList.remove('hidden');
        this.backdrop.classList.remove('hidden');
    }

    close() {
        this.modal.classList.add('hidden');
        this.backdrop.classList.add('hidden');
    }

    send() {
       let btnData = this.sendbtn.dataset;
       let calData = document.getElementById('calendarContent').dataset;

        let data = {
            context: btnData.context,
            day: btnData.day,
            month: calData.month,
            year: calData.year,
        };

        let url = Routing.generate(btnData.route) + '?XDEBUG_SESSION=PHPSTORM';

        this.startLoading();

        fetch(url, {
            method: btnData.method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data),
        })
        .then((response) => response.json())
        .then((data) => {
            console.log('Success:');
            document.getElementById('calendarContainer').innerHTML = data.html;
            this.stopLoading();
            this.close();
            CalendarClass.init();
        })
        .catch((error) => {
            console.error('Error:', error);
            this.stopLoading();
            this.close();
        });
    }

    startLoading() {
        this.sendbtn.querySelector('.spin').classList.remove('hidden');
        this.closebtn.disabled = true;
        this.sendbtn.disabled = true;
    }

    stopLoading() {
        this.sendbtn.querySelector('.spin').classList.add('hidden');
        this.closebtn.disabled = false;
        this.sendbtn.disabled = false;
    }
}
