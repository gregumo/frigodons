class UserListInit {
    init() {
        this.allUsers = document.querySelectorAll("tr[data-user]")
        if (this.allUsers) {
            this.allUsers.forEach((item) => {
                item.addEventListener('click', (e) => {
                    if(window.innerWidth < 1280) {
                        window.dialogModal.open(e.target.closest('tr[data-user]').dataset);
                    }
                });
            })
        }
    }
}

export const UserList = new UserListInit()
