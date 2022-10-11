export default class Helper {
    init() {
        let phoneInput = document.getElementById('user_phone')
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                window.helper.phoneNumberFormatter(e);
            });
        }

        let icons = document.querySelectorAll('div[data-tooltip-target]')
        if (icons) {
            icons.forEach((item) => {
                item.addEventListener('mouseenter', (e) => {
                    window.helper.showTooltip(e.target);
                });
                item.addEventListener('mouseleave', (e) => {
                    window.helper.hideTooltip(e.target);
                });
            })
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

    showTooltip(icon) {
        let tooltip = document.getElementById(icon.getAttribute('data-tooltip-target'));
        let windowWidth = window.innerWidth;
        let tooltipWidth = tooltip.offsetWidth;
        let tooltipHeight = tooltip.offsetHeight;
        let iconLeft = icon.getBoundingClientRect().left;
        let iconTop = icon.getBoundingClientRect().top;
        let iconWidth = icon.offsetWidth;

        if(iconLeft + tooltipWidth < windowWidth) {
            tooltip.style.left = icon.offsetLeft + 'px';
        } else if(iconLeft - tooltipWidth > 0) {
            tooltip.style.left = (icon.offsetLeft - tooltipWidth + iconWidth) + 'px';
        } else {
            tooltip.style.left = '20px';
        }
        tooltip.style.top = (iconTop - tooltipHeight - 5) + 'px';
        tooltip.classList.remove('invisible');
    }

    hideTooltip(icon) {
        document.getElementById(icon.getAttribute('data-tooltip-target')).classList.add('invisible');
    }
}
