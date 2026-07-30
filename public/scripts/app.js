const form = document.querySelector('#contact-form');

if (form) {
    const status = document.querySelector('#form-status');
    const button = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        form.querySelectorAll('[data-error]').forEach((element) => {
            element.textContent = '';
        });

        status.className = 'form-status';
        status.textContent = '';
        button.disabled = true;
        button.classList.add('loading');

        const data = Object.fromEntries(new FormData(form).entries());

        try {
            const response = await fetch('/api/contact', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });
            const payload = await response.json();

            if (!response.ok) {
                if (payload.errors) {
                    Object.entries(payload.errors).forEach(([field, messages]) => {
                        const target = form.querySelector(`[data-error="${field}"]`);

                        if (target) {
                            target.textContent = messages[0];
                        }
                    });
                }

                throw new Error(payload.detail || 'Не удалось отправить обращение');
            }

            status.classList.add('success');
            status.textContent = payload.data.analysis.reply;
            form.reset();
        } catch (error) {
            status.classList.add('error');
            status.textContent = error.message || 'Сервис временно недоступен';
        } finally {
            button.disabled = false;
            button.classList.remove('loading');
        }
    });
}
