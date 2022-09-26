class CalendarInit {
    init() {
        this.allDates = document.querySelectorAll("div[data-day]")
        if (this.allDates) {
            this.allDates.forEach((item) => {
                item.addEventListener('click', (e) => {
                    window.dialogModal.open(e.target.closest('.dayCell').dataset);
                });
            })
        }
    }
}

export const Calendar = new CalendarInit()
