import Routing from "fos-router";

class Date {
    init (date) {
        date.addEventListener('click', (e) => {
            window.dialogModal.open(e.target.closest('.dayCell').dataset);
        });
    }
}

export default Date;
