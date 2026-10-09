import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Confirm before submitting a profile update that changes the username.
 */
Alpine.data('profileForm', (originalUsername) => ({
    originalUsername,
    showConfirm: false,

    submit(event) {
        const usernameInput = this.$refs.form.querySelector('input[name="username"]');
        const usernameChanged = usernameInput && usernameInput.value !== this.originalUsername;

        if (usernameChanged) {
            this.showConfirm = true;

            return;
        }

        this.$refs.form.submit();
    },

    confirmSubmit() {
        this.showConfirm = false;
        this.$refs.form.submit();
    },

    cancel() {
        this.showConfirm = false;
    },
}));

/**
 * Disable the username field and show a live countdown while the cooldown is active.
 */
Alpine.data('usernameCooldown', (canChange, availableAtIso) => ({
    canChange,
    remaining: '',
    timer: null,

    init() {
        if (this.canChange) {
            return;
        }

        this.tick();
        this.timer = setInterval(() => this.tick(), 1000);
    },

    tick() {
        const diff = new Date(availableAtIso) - Date.now();

        if (diff <= 0) {
            this.canChange = true;
            clearInterval(this.timer);

            return;
        }

        const totalSeconds = Math.ceil(diff / 1000);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;

        this.remaining = `${minutes}m ${seconds}s`;
    },
}));

Alpine.start();
