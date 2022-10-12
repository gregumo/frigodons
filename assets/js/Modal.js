import Routing from "fos-router";
import {Calendar} from "./Calendar";

export default class Modal {
    constructor(id) {
        this.id = id;
        this.modal = document.getElementById(id);
        this.backdrop = document.getElementById(id + 'Backdrop');
        this.title = this.modal.querySelector('.title');
        this.content = this.modal.querySelector('.content');
        this.closebtn = this.modal.querySelector('.close');
        this.sendbtn = this.modal.querySelector('.send');
    }

    init() {
        let helpButtons = document.querySelectorAll("button[data-help-modal]");
        if (helpButtons) {
            helpButtons.forEach((item) => {
                item.addEventListener('click', (e) => {
                    window.dialogModal.open(item.dataset);
                });
            });
        }

        this.modal.addEventListener('click', function(e){
            if(e.target.id === window.dialogModal.id) {
                window.dialogModal.close();
            }
        }, false);
        this.closebtn.addEventListener('click', this.close.bind(this), false);
        this.sendbtn.addEventListener('click', this.send.bind(this), false);
    }

    open(data) {
        console.log(data);
        if (!data.modal) {
            return;
        }

        this.title.innerHTML = data.title;
        this.content.innerHTML = data.content;
        this.closebtn.querySelector('.text').innerHTML = data.closebtn;

        if (data.displaysendbtn) {
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

        let url = Routing.generate(btnData.route);

        this.startLoading();

        fetch(url, {
            method: btnData.method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data),
        })
            .then(
                function(response) {
                    if (response.status !== 200) {
                        window.dialogModal.error();
                        return;
                    }

                    response.json().then(function(data) {
                        document.getElementById('calendarContainer').innerHTML = data.html;
                        window.dialogModal.stopLoading();
                        window.dialogModal.close();
                        Calendar.init();
                    });
                }
            )
            .catch((error) => {
                window.dialogModal.error();
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

    error() {
        document.getElementById('fetchErrorMessage').classList.remove('hidden');
        document.getElementById('modalButtons').classList.add('hidden');
    }
}
