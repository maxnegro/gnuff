import { mount } from '@vue/test-utils';
import Welcome from '@/Pages/Welcome.vue';

describe('Welcome page', () => {
    it('mostra i CTA pubblici quando l\'utente non è autenticato', () => {
        const wrapper = mount(Welcome, {
            props: {
                canLogin: true,
                canRegister: true,
                laravelVersion: '13.0.0',
                phpVersion: '8.4.0',
            },
            global: {
                mocks: {
                    $page: {
                        props: {
                            auth: {
                                user: null,
                            },
                        },
                    },
                },
            },
        });

        expect(wrapper.text()).toContain('Benvenuto su Gnuff');
        expect(wrapper.text()).toContain('Accedi');
        expect(wrapper.text()).toContain('Registrati');
    });
});