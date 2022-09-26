export default class Helper {
    init() {
        let phoneInput = document.getElementById('user_phone')
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                window.helper.phoneNumberFormatter(e);
            });
        }
    }

    phoneNumberFormatter(e) {
        let phoneInput = e.target;
        let value = phoneInput.value;
        if (!value) return value;
        const phoneNumber = value.replace(/[^\d]/g, '');

        let refacto = `${phoneNumber.slice(0, 2)}`;
        if (phoneNumber.length > 2) {
            refacto += ` ${phoneNumber.slice(2, 4)}`;
        }
        if (phoneNumber.length > 4) {
            refacto += ` ${phoneNumber.slice(4, 6)}`;
        }
        if (phoneNumber.length > 6) {
            refacto += ` ${phoneNumber.slice(6, 8)}`;
        }
        if (phoneNumber.length > 8) {
            refacto += ` ${phoneNumber.slice(8, 10)}`;
        }

        phoneInput.value = refacto;
    }
}
