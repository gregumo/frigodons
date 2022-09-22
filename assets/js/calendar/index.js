import Date from "./Date"

class CalendarInit {
    init() {
        this.allDates = document.querySelectorAll("div[data-day]")
        if (this.allDates) {
            this.allDates.forEach((item) => {
                const date = new Date(item)
                date.init(item)
            })
        }
    }
}

export const CalendarClass = new CalendarInit()
